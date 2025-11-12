<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id']) || strtolower($_SESSION['role_name'] ?? '') !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: list.php");
    exit;
}

$id = (int)$_GET['id'];
$sql = "SELECT u.*, r.name AS role_name 
        FROM users u 
        LEFT JOIN roles r ON u.role_id = r.id 
        WHERE u.id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
$user = $res->fetch_assoc();

if (!$user) {
    echo "<script>alert('User tidak ditemukan.'); window.location='list.php';</script>";
    exit;
}

// Fungsi inisial dari username
function getInitials($name) {
    $parts = explode(' ', trim($name));
    $first = strtoupper(substr($parts[0] ?? '', 0, 1));
    $second = strtoupper(substr($parts[1] ?? '', 0, 1));
    return $first . ($second ?: $first);
}

$initials = getInitials($user['username'] ?? 'U');
$hasAvatar = !empty($user['ava']) && file_exists("../uploads/avatars/{$user['ava']}");
$avatarPath = $hasAvatar
    ? "../uploads/avatars/{$user['ava']}"
    : "../assets/img/default-avatar.png";

// Ambil data session untuk topbar
$username = $_SESSION['username'];
$role_name = $_SESSION['role_name'] ?? '';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Profile - Sprinklist</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --primary: #8B5E3C;
            --secondary: #A46C4E;
            --accent: #F2B880;
            --danger: #E57373;
            --dark: #613a1cff;
            --light: #fff7f5;
            --pink: #f9b6a5;
        }

        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Quicksand', sans-serif; background: var(--light); color: #333; overflow-x: hidden; }

        .sidebar {
            width: 250px; height: 100vh; background: var(--primary); color: #fff; position: fixed; top: 0; left: 0;
            padding: 25px 0; overflow-y: auto; box-shadow: 4px 0 15px rgba(0,0,0,.1); z-index: 1000; transition: .3s;
        }
        .sidebar h4 { text-align: center; font-weight: 700; margin-bottom: 30px; font-size: 1.5rem; animation: fadeSlideIn 1s forwards; }
        @keyframes fadeSlideIn { 0% { opacity: 0; transform: translateY(-15px) scale(.9); } 100% { opacity: 1; transform: none; } }
        .sidebar h4 i { margin-right: 8px; color: var(--pink); animation: bloom 1.6s forwards; }
        @keyframes bloom { 0% { transform: scale(0) rotate(-45deg); opacity: 0; } 60% { transform: scale(1.2) rotate(10deg); opacity: 1; } 100% { transform: scale(1) rotate(0); } }

        .sidebar .menu-link { display: flex; align-items: center; color: #fff; padding: 12px 20px; text-decoration: none; font-weight: 500;
                              transition: .3s; border-left: 4px solid transparent; position: relative; }
        .sidebar .menu-link i { width: 25px; margin-right: 12px; font-size: 1.1rem; }
        .sidebar .menu-link:hover, .sidebar .menu-link.active { background: var(--secondary); border-left-color: #fff; }
        .sidebar .menu-link.has-submenu::after { content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900; position: absolute; right: 20px; font-size: .8rem; transition: .3s; }
        .sidebar .menu-link.active.has-submenu::after { transform: rotate(180deg); }

        .submenu { background: #fbe7e7; max-height: 0; overflow: hidden; opacity: 0; transition: max-height .4s, opacity .3s, padding .4s;
                   border-top: 1px solid #f3d1c8; border-bottom: 1px solid #f3d1c8; }
        .submenu.active { max-height: 300px; opacity: 1; padding: 8px 0; }
        .submenu a { display: block; color: var(--secondary); padding: 8px 20px 8px 57px; font-size: .9rem; transition: .2s; }
        .submenu a i { margin-right: 8px; font-size: .8rem; color: #da5e17; }
        .submenu a:hover { background: #f8d7d7; color: #7a4e2f; }

        .sidebar-footer { position: absolute; bottom: 20px; width: 100%; padding: 0 20px; }
        .logout-btn { display: flex; align-items: center; color: #ffcccc; padding: 10px 20px; text-decoration: none; font-weight: 500;
                      border-radius: 8px; transition: .3s; }
        .logout-btn:hover { background: rgba(255,255,255,.1); color: #fff; }
        .logout-btn i { margin-right: 10px; }

        .topbar {
            height: 65px; background: var(--dark); border-bottom: 3px solid var(--primary); padding: 0 25px;
            display: flex; align-items: center; justify-content: space-between; position: fixed; left: 250px; right: 0; top: 0;
            z-index: 999; color: #fff;
        }
        .logo-section { display: flex; align-items: center; gap: 12px; animation: fadeInLogo 1.2s forwards; }
        @keyframes fadeInLogo { from { opacity: 0; transform: translateX(-15px); } to { opacity: 1; transform: none; } }
        .logo-icon { font-size: 1.6rem; color: var(--pink); animation: bounceGrow 1.5s infinite alternate ease-in-out; }
        @keyframes bounceGrow { 0% { transform: scale(1) translateY(0); } 50% { transform: scale(1.1) translateY(-2px); } 100% { transform: scale(1) translateY(0); } }
        .brand { font-weight: 700; font-size: 1.3rem; color: #ffe5df; }
        .motto { font-size: .75rem; color: var(--pink); font-style: italic; opacity: .9; margin-top: -2px; }

        .user-section { display: flex; align-items: center; gap: 12px; cursor: pointer; }
        .username { font-weight: 600; font-size: 1rem; }
        .profile-icon {
            width: 42px; height: 42px; border-radius: 50%; background: #452c2c; display: flex; align-items: center;
            justify-content: center; color: #fff; font-size: 1.1rem; box-shadow: 0 2px 5px rgba(0,0,0,.15); transition: .3s;
        }
        .profile-icon:hover { transform: scale(1.1); box-shadow: 0 4px 10px rgba(0,0,0,.2); }
        .content { margin-left: 250px; margin-top: 80px; padding: 40px; min-height: calc(100vh - 80px); background: var(--light); }

        .page-title { font-weight: 700; color: var(--primary); margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }

        .btn-back {
            background: var(--secondary); color: #fff; border: none; font-weight: 600; padding: 8px 16px;
            border-radius: 8px; display: inline-flex; align-items: center; gap: 8px; transition: .3s;
        }
        .btn-back:hover { background: #7a4e2f; color: #fff; }

        .card { border-radius: 15px; box-shadow: 0 4px 20px rgba(0,0,0,.1); background: #fff; padding: 2rem; }
        .avatar-container {
            width: 140px; height: 140px; border-radius: 50%; border: 6px solid var(--pink);
            overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,.15); margin: 0 auto;
        }
        .avatar-img { width: 100%; height: 100%; object-fit: cover; }
        .avatar-initial {
            width: 100%; height: 100%; background: #DF6D99; color: var(--dark); display: flex;
            align-items: center; justify-content: center; font-size: 48px; font-weight: 700; text-transform: uppercase;
        }

        .info-label { font-weight: 600; color: var(--dark); }
        .badge { font-size: .9rem; padding: 6px 12px; border-radius: 20px; }

        @media (max-width: 992px) {
            .sidebar { transform: translateX(-100%); }
            .topbar, .content { left: 0 !important; }
            .sidebar.active { transform: translateX(0); }
        }
        @media (max-width: 576px) {
            .avatar-container { width: 110px; height: 110px; border-width: 5px; }
            .avatar-initial { font-size: 36px; }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>

        <a href="../pages/dashboard.php" class="menu-link" data-target="dashboard"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

        <a href="../todo/personal.php" class="menu-link" data-target="todo"><i class="fa-solid fa-list-check"></i> To Do List</a>
        <div class="submenu" id="todo-submenu">
            <a href="../todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
            <a href="../todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
            <a href="../todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
        </div>

        <a href="../notes/personal.php" class="menu-link" data-target="notes"><i class="fa-solid fa-note-sticky"></i> Notes</a>
        <div class="submenu" id="notes-submenu">
            <a href="../notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
            <a href="../notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
            <a href="../notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
        </div>

        <?php if (strtolower($role_name) === 'admin'): ?>
            <a href="list.php" class="menu-link active" data-target="master"><i class="fa-solid fa-gear"></i> Master</a>
            <div class="submenu active" id="master-submenu">
                <a href="list.php"><i class="fa-solid fa-users-gear"></i> User</a>
            </div>
        <?php endif; ?>

        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
        </div>
    </div>
    <div class="topbar">
        <div class="logo-section">
            <i class="fa-solid fa-seedling logo-icon"></i>
            <div><div class="brand">Sprinklist</div><div class="motto">Grow your day, one task at a time.</div></div>
        </div>
        <div class="user-section" onclick="window.location.href='pages/profile.php'">
            <span class="username">Hi, <?= htmlspecialchars($username); ?></span>
            <div class="profile-icon"><i class="fa-solid fa-user"></i></div>
        </div>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="page-title"><i class="fa-solid fa-user"></i> Detail Profile</h3>
            <a href="list.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="card">
            <div class="row g-4 align-items-center">
                <!-- Avatar -->
                <div class="col-md-4 text-center">
                    <div class="avatar-container">
                        <?php if ($hasAvatar): ?>
                            <img src="<?= htmlspecialchars($avatarPath); ?>" alt="Avatar" class="avatar-img">
                        <?php else: ?>
                            <div class="avatar-initial"><?= htmlspecialchars($initials); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-md-8">
                    <div class="row g-3">
                        <div class="col-12"><span class="info-label">Username :</span> <strong><?= htmlspecialchars($user['username']); ?></strong></div>
                        <div class="col-12"><span class="info-label">Email :</span> <?= htmlspecialchars($user['email']); ?></div>
                        <div class="col-12">
                            <span class="info-label">Role :</span>
                            <span class="badge <?= strtolower($user['role_name']) === 'admin' ? 'bg-danger' : 'bg-success'; ?>">
                                <?= htmlspecialchars($user['role_name']); ?>
                            </span>
                        </div>
                        <div class="col-12">
                            <span class="info-label">Bergabung pada :</span>
                            <?= date('d M Y H:i', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {

            document.querySelectorAll('.menu-link.has-submenu').forEach(link => {
                link.addEventListener('click', e => {
                    e.preventDefault();
                    const target = link.dataset.target;
                    const submenu = document.getElementById(target + '-submenu');
                    const active = link.classList.contains('active');

                    document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
                    document.querySelectorAll('.submenu').forEach(s => s.classList.remove('active'));

                    if (!active && submenu) {
                        link.classList.add('active');
                        submenu.classList.add('active');
                    }
                });
            });

            const currentFile = location.pathname.split('/').pop();
            const masterFiles = ['list.php', 'detail.php', 'edit.php', 'add-user.php'];
            if (masterFiles.includes(currentFile)) {
                const m = document.querySelector('[data-target="master"]');
                const s = document.getElementById('master-submenu');
                if (m && s) { m.classList.add('active'); s.classList.add('active'); }
            }
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>