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

//fungsi untuk inisial pada profile
function getInitials($name)
{
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

// mengambil data pengguna untuk topbar
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

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: var(--light);
            color: #333;
            overflow-x: hidden;
        }

        .sidebar {
            width: 250px;
            height: 100vh;
            background: var(--primary);
            color: #fff;
            position: fixed;
            top: 0;
            left: 0;
            padding-top: 25px;
            overflow-y: auto;
            box-shadow: 4px 0 10px rgba(0, 0, 0, .1);
            z-index: 1000;
            transition: .3s;
        }

        .sidebar h4 {
            text-align: center;
            font-weight: 700;
            margin-bottom: 25px;
            font-size: 1.5rem;
            animation: fadeSlideIn 1s forwards;
        }

        @keyframes fadeSlideIn {
            0% {
                opacity: 0;
                transform: translateY(-15px) scale(.9);
            }

            100% {
                opacity: 1;
                transform: none;
            }
        }

        .sidebar h4 i {
            margin-right: 8px;
            color: var(--pink);
            animation: bloom 1.6s forwards;
        }

        @keyframes bloom {
            0% {
                transform: scale(0) rotate(-45deg);
                opacity: 0;
            }

            60% {
                transform: scale(1.2) rotate(10deg);
                opacity: 1;
            }

            100% {
                transform: scale(1) rotate(0);
            }
        }

        .sidebar a {
            display: flex;
            align-items: center;
            color: #fff;
            padding: 10px 20px;
            text-decoration: none;
            font-weight: 500;
            transition: .3s;
            border-left: 4px solid transparent;
        }

        .sidebar a i {
            width: 25px;
            text-align: center;
            margin-right: 10px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: var(--secondary);
            border-left-color: #fff;
        }

        .submenu {
            background: #fbe7e7;
            max-height: 0;
            overflow: hidden;
            opacity: 0;
            transition: max-height .4s, opacity .4s;
            border-top: 1px solid #f3d1c8;
            border-bottom: 1px solid #f3d1c8;
            margin-left: 0;
        }

        .submenu.active-menu {
            max-height: 300px;
            opacity: 1;
        }

        .submenu a {
            display: block;
            color: var(--secondary);
            padding: 8px 40px;
            font-size: 14px;
            transition: .2s;
            border-left: none;
        }

        .submenu a:hover {
            background: #f8d7d7;
            color: #7a4e2f;
        }

        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background-color: var(--secondary);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-track {
            background-color: #e7c9b3;
        }

        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
        }

        .logout-btn {
            display: block;
            background: var(--secondary);
            color: #fff;
            border-radius: 8px;
            text-align: center;
            margin: 25px 20px;
            padding: 10px 0;
            text-decoration: none;
            font-weight: 600;
            transition: .3s;
        }

        .logout-btn:hover {
            background: #7a4e2f;
        }

        .topbar {
            height: 65px;
            background: var(--dark);
            border-bottom: 2px solid var(--primary);
            padding: 0 25px;
            display: flex;
            align-items: center;
            justify-content: flex-end;
            position: fixed;
            left: 250px;
            right: 0;
            top: 0;
            z-index: 999;
            color: #fff;
        }

        .sprinklist-logo {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-right: auto;
            animation: fadeInLogo 1.2s forwards;
        }

        @keyframes fadeInLogo {
            from {
                opacity: 0;
                transform: translateX(-15px);
            }

            to {
                opacity: 1;
                transform: none;
            }
        }

        .logo-icon {
            font-size: 28px;
            color: var(--pink);
            animation: bounceGrow 1.5s infinite alternate ease-in-out;
        }

        @keyframes bounceGrow {
            0% {
                transform: scale(1) translateY(0);
            }

            50% {
                transform: scale(1.1) translateY(-2px);
            }

            100% {
                transform: scale(1) translateY(0);
            }
        }

        .tagline {
            display: flex;
            flex-direction: column;
            line-height: 1.2;
        }

        .brand {
            font-weight: 700;
            font-size: 18px;
            letter-spacing: .5px;
            color: #ffe5df;
        }

        .motto {
            font-size: 12px;
            color: var(--pink);
            font-style: italic;
            opacity: .9;
        }

        .username {
            font-weight: 600;
            font-size: 16px;
            margin-right: 15px;
        }

        .profile-icon {
            width: 42px;
            height: 42px;
            border-radius: 50%;
            background: #452c2c;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 18px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, .15);
            transition: .3s;
        }

        .profile-icon:hover {
            transform: scale(1.1);
            box-shadow: 0 4px 10px rgba(0, 0, 0, .2);
        }

        .profile-icon img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 50%;
        }

        .content {
            margin-left: 250px;
            margin-top: 80px;
            padding: 40px;
            min-height: calc(100vh - 80px);
            background: var(--light);
        }

        .page-title {
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .btn-back {
            background: var(--secondary);
            color: #fff;
            border: none;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 8px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: .3s;
            text-decoration: none;
        }

        .btn-back:hover {
            background: #7a4e2f;
            color: #fff;
        }

        .card {
            border-radius: 15px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, .1);
            background: #fff;
            padding: 2rem;
        }

        .avatar-container {
            width: 140px;
            height: 140px;
            border-radius: 50%;
            border: 6px solid var(--pink);
            overflow: hidden;
            box-shadow: 0 4px 15px rgba(0, 0, 0, .15);
            margin: 0 auto;
        }

        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .avatar-initial {
            width: 100%;
            height: 100%;
            background: #DF6D99;
            color: var(--dark);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 48px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .info-label {
            font-weight: 600;
            color: var(--dark);
        }

        .badge {
            font-size: .9rem;
            padding: 6px 12px;
            border-radius: 20px;
        }

        @media (max-width: 992px) {
            .sidebar {
                transform: translateX(-100%);
            }

            .topbar,
            .content {
                left: 0 !important;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        @media (max-width: 576px) {
            .avatar-container {
                width: 110px;
                height: 110px;
                border-width: 5px;
            }

            .avatar-initial {
                font-size: 36px;
            }
        }
    </style>
</head>

<body>

    <div class="sidebar">
        <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>

        <a href="../pages/dashboard.php" class="menu-link" data-target="dashboard">
            <i class="fa-solid fa-gauge-high"></i> Dashboard
        </a>

        <a href="javascript:void(0)" class="menu-link" data-target="todo">
            <i class="fa-solid fa-list-check"></i> To Do List
        </a>
        <div class="submenu" id="todo-submenu">
            <a href="../todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
            <a href="../todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
            <a href="../todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
        </div>

        <a href="javascript:void(0)" class="menu-link" data-target="notes">
            <i class="fa-solid fa-note-sticky"></i> Notes
        </a>
        <div class="submenu" id="notes-submenu">
            <a href="../notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
            <a href="../notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
            <a href="../notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
        </div>

        <?php if (strtolower($role_name) === 'admin'): ?>
            <a href="javascript:void(0)" class="menu-link active" data-target="master">
                <i class="fa-solid fa-gear"></i> Master
            </a>
            <div class="submenu active-menu" id="master-submenu">
                <a href="list.php"><i class="fa-solid fa-users-gear"></i> User</a>
            </div>
        <?php endif; ?>

        <div class="sidebar-footer">
            <a href="../logout.php" class="logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        </div>
    </div>

    <div class="topbar">
        <div class="sprinklist-logo">
            <i class="fa-solid fa-seedling logo-icon"></i>
            <div class="tagline">
                <span class="brand">Sprinklist</span>
                <span class="motto">Grow your day, one task at a time.</span>
            </div>
        </div>
        <span class="username">Hi, <?= htmlspecialchars($username); ?></span>
        <a href="../pages/profile.php" class="profile-link">
            <div class="profile-icon">
                <?php
                $ava_file = $_SESSION['ava'] ?? 'default.png';
                $full_path = $_SERVER['DOCUMENT_ROOT'] . '/uploads/avatars/' . $ava_file;
                $web_path = '/uploads/avatars/' . $ava_file;

                if (file_exists($full_path) && !empty($ava_file)) {
                    echo '<img src="' . htmlspecialchars($web_path) . '" alt="Avatar" class="avatar-img">';
                } else {
                    echo '<i class="fa-solid fa-user"></i>';
                }
                ?>
            </div>
        </a>
    </div>

    <div class="content">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h3 class="page-title"><i class="fa-solid fa-user"></i> Detail Profile</h3>
            <a href="list.php" class="btn-back"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        </div>

        <div class="card">
            <div class="row g-4 align-items-center">
                <!-- ava -->
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
            const menuLinks = document.querySelectorAll('.menu-link');

            menuLinks.forEach(link => {
                link.addEventListener('click', function (e) {
                    const target = this.dataset.target;
                    const submenu = document.getElementById(target + '-submenu');

                    if (submenu) {
                        e.preventDefault();
                        menuLinks.forEach(l => l.classList.remove('active'));
                        document.querySelectorAll('.submenu').forEach(sm => sm.classList.remove('active-menu'));
                        this.classList.add('active');
                        submenu.classList.add('active-menu');
                    }
                });
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>