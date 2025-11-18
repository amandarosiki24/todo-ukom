<?php
session_start();
include '../db.php';
if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}
$username = $_SESSION['username'];
$role_name = $_SESSION['role_name'] ?? '';
if (strtolower($role_name) !== 'admin') {
  echo "<script>alert('Akses ditolak! Hanya admin yang bisa mengakses halaman ini.'); window.location='../pages/dashboard.php';</script>";
  exit;
}
$message = '';
$message_type = '';
if (isset($_GET['delete_id'])) {
  $delete_id = (int)$_GET['delete_id'];
  if ($delete_id === $_SESSION['user_id']) {
    $message = "Anda tidak bisa menghapus akun sendiri!";
    $message_type = "danger";
  } else {
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $delete_id);
    if ($stmt->execute()) {
      $message = "User berhasil dihapus!";
      $message_type = "success";
    } else {
      $message = "Gagal menghapus user.";
      $message_type = "danger";
    }
    $stmt->close();
  }
}

$search = '';
if (isset($_GET['search'])) {
  $search = trim($_GET['search']);
}

$sql = "
  SELECT
    users.id,
    users.username,
    users.email,
    roles.name AS role_name,
    users.created_at
  FROM users
  LEFT JOIN roles ON users.role_id = roles.id
";
if ($search !== '') {
  $sql .= " WHERE users.username LIKE ?";
  $searchParam = "%$search%";
}
$sql .= " ORDER BY users.id DESC";

if ($search !== '') {
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $searchParam);
  $stmt->execute();
  $result = $stmt->get_result();
} else {
  $result = $conn->query($sql);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sprinklist - Manage Account</title>
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
      --logo: #f9b6a5;
    }

    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: 'Quicksand', sans-serif;
      background-color: var(--light);
      color: #333;
      overflow-x: hidden;
    }

    .sidebar {
      width: 250px;
      height: 100vh;
      background-color: var(--primary);
      color: #fff;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 25px;
      overflow-y: auto;
      box-shadow: 4px 0 10px rgba(0,0,0,0.1);
      z-index: 1000;
    }

    .sidebar h4 {
      text-align: center;
      font-weight: 700;
      margin-bottom: 25px;
      color: #fff;
      animation: fadeSlideIn 1s ease forwards;
    }

    @keyframes fadeSlideIn {
      0% { opacity: 0; transform: translateY(-15px) scale(0.9); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }

    .sidebar h4 i {
      margin-right: 8px;
      color: var(--logo);
      animation: bloom 1.6s ease-in-out forwards;
    }

    @keyframes bloom {
      0% { transform: scale(0) rotate(-45deg); opacity: 0; }
      60% { transform: scale(1.2) rotate(10deg); opacity: 1; }
      100% { transform: scale(1) rotate(0); }
    }

    .sidebar a {
      display: flex;
      align-items: center;
      color: #fff;
      padding: 10px 20px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
    }

    .sidebar a i {
      width: 25px;
      text-align: center;
      margin-right: 10px;
    }

    .sidebar a:hover, .sidebar a.active {
      background-color: var(--secondary);
      border-left: 4px solid #fff;
    }

    .submenu {
      background-color: #fbe7e7;
      margin-left: 0;
      border-top: 1px solid #f3d1c8;
      border-bottom: 1px solid #f3d1c8;
      overflow: hidden;
      max-height: 0;
      opacity: 0;
      transition: max-height 0.4s ease, opacity 0.4s ease;
    }

    .submenu.active-menu {
      max-height: 300px;
      opacity: 1;
    }

    .submenu a {
      color: var(--secondary);
      padding: 8px 40px;
      font-size: 14px;
      border-left: none;
    }

    .submenu a:hover {
      background-color: #f8d7d7;
      color: #7a4e2f;
    }

    .logout-btn {
      display: block;
      background-color: var(--secondary);
      color: #fff;
      border-radius: 8px;
      text-align: center;
      margin: 25px 20px;
      padding: 10px 0;
      text-decoration: none;
      font-weight: 600;
      transition: 0.3s;
    }

    .logout-btn:hover {
      background-color: #7a4e2f;
    }

    .logout-btn i {
      margin-right: 10px;
    }

    .sidebar-footer {
      position: absolute;
      bottom: 0;
      width: 100%;
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

    .topbar {
      height: 65px;
      background-color: var(--dark);
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
      color: #fff;
      animation: fadeInLogo 1.2s ease forwards;
      margin-right: auto;
    }

    .logo-icon {
      font-size: 28px;
      color: var(--logo);
      animation: bounceGrow 1.5s infinite alternate ease-in-out;
    }

    .tagline {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
    }

    .tagline .brand {
      font-weight: 700;
      font-size: 18px;
      letter-spacing: 0.5px;
      color: #ffe5df;
    }

    .tagline .motto {
      font-size: 12px;
      color: var(--logo);
      opacity: 0.9;
      font-style: italic;
    }

    @keyframes fadeInLogo {
      from { opacity: 0; transform: translateX(-15px); }
      to { opacity: 1; transform: translateX(0); }
    }

    @keyframes bounceGrow {
      0% { transform: scale(1) translateY(0); }
      50% { transform: scale(1.1) translateY(-2px); }
      100% { transform: scale(1) translateY(0); }
    }

    .username {
      font-weight: 600;
      color: #fff;
      margin-right: 15px;
      font-size: 16px;
    }

    .profile-icon {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background-color: #452c2c;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .avatar-img {
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
      background-color: var(--light);
    }

    .page-title {
      font-weight: 700;
      color: var(--primary);
      margin-bottom: 20px;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .page-title i {
      color: var(--primary) !important;
      font-size: 1.4rem;
    }

    .btn-add {
      background-color: var(--secondary);
      color: #fff;
      border: none;
      font-weight: 600;
      padding: 8px 16px;
      border-radius: 8px;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: background 0.3s ease;
    }

    .btn-add:hover {
      background-color: #7a4e2f;
      color: #fff;
    }

    .table-container {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.08);
    }

    .table th {
      background-color: var(--primary);
      color: #fff;
      font-weight: 600;
      text-align: center;
      vertical-align: middle;
    }

    .table td {
      vertical-align: middle;
      text-align: center;
    }

    .btn-edit,
    .btn-delete,
    .btn-detail {
      padding: 6px 10px;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      margin: 0 4px;
    }

    .btn-detail {
      background-color: #4CAF50;
      color: #fff;
    }

    .btn-detail:hover {
      background-color: #388E3C;
    }

    .btn-edit {
      background-color: var(--accent);
      color: #fff;
    }

    .btn-edit:hover {
      background-color: #E39E5D;
    }

    .btn-delete {
      background-color: var(--danger);
      color: #fff;
    }

    .btn-delete:hover {
      background-color: #C62828;
    }

    .badge {
      font-size: 0.8rem;
      padding: 6px 10px;
      border-radius: 20px;
    }

    .actions-container {
      display: flex;
      flex-direction: column;
      align-items: flex-end;
      gap: 12px;
      margin-bottom: 20px;
    }

    .search-form {
      display: flex;
      align-items: center;
      gap: 8px;
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
      .username {
        font-size: 0.9rem;
      }

      .profile-icon {
        width: 36px;
        height: 36px;
      }

      .topbar {
        padding: 0 15px;
      }

      .actions-container {
        align-items: stretch;
      }

      .search-form {
        width: 100%;
      }

      .search-form input {
        flex: 1;
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
        <a href="../master/list.php"><i class="fa-solid fa-users-gear"></i> User</a>
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
    <h3 class="page-title"><i class="fa-solid fa-users-gear"></i> Manage Accounts</h3>

    <div class="actions-container">
      <a href="tambah.php" class="btn-add"><i class="fa-solid fa-plus"></i> Tambah User</a>
      
      <form method="GET" class="search-form">
        <input type="text" name="search" class="form-control" placeholder="Cari username..." value="<?= htmlspecialchars($search) ?>" style="width: 300px;" autofocus>
        <button type="submit" class="btn" style="background-color: var(--secondary); border: none; color: white;">
          <i class="fa-solid fa-magnifying-glass"></i>
        </button>
        <?php if ($search !== ''): ?>
          <a href="list.php" class="btn" style="background-color: #8B5E3C; border: none; color: white;">
            <i class="fa-solid fa-times"></i>
          </a>
        <?php endif; ?>
      </form>
    </div>

    <?php if ($message): ?>
      <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="table-container">
      <div class="table-responsive">
        <table class="table table-bordered align-middle">
          <thead>
            <tr>
              <th>No</th>
              <th>Username</th>
              <th>Email</th>
              <th>Role</th>
              <th>Tanggal Dibuat</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result->num_rows > 0): ?>
              <?php $no = 1;
              while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td><?= $no++; ?></td>
                  <td><?= htmlspecialchars($row['username']); ?></td>
                  <td><?= htmlspecialchars($row['email']); ?></td>
                  <td>
                    <?php if (strtolower($row['role_name']) === 'admin'): ?>
                      <span class="badge bg-danger"><?= htmlspecialchars($row['role_name']); ?></span>
                    <?php else: ?>
                      <span class="badge bg-success"><?= htmlspecialchars($row['role_name']); ?></span>
                    <?php endif; ?>
                  </td>
                  <td><?= date('d M Y', strtotime($row['created_at'])); ?></td>
                  <td>
                    <a href="../master/detail.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-detail" title="Detail">
                      <i class="fa-solid fa-eye"></i>
                    </a>
                    <a href="../master/edit.php?id=<?= $row['id']; ?>" class="btn btn-sm btn-edit" title="Edit">
                      <i class="fa-solid fa-pen-to-square"></i>
                    </a>
                    <?php if ($row['id'] != $_SESSION['user_id']): ?>
                      <a href="?delete_id=<?= $row['id']; ?>&search=<?= urlencode($search) ?>" class="btn btn-sm btn-delete" title="Hapus"
                        onclick="return confirm('Yakin ingin menghapus user <?= htmlspecialchars($row['username']); ?>?')">
                        <i class="fa-solid fa-trash"></i>
                      </a>
                    <?php else: ?>
                      <button class="btn btn-sm btn-secondary" disabled title="Tidak bisa hapus diri sendiri">
                        <i class="fa-solid fa-ban"></i>
                      </button>
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr>
                <td colspan="6" class="text-muted py-4">Belum ada data user<?php if ($search !== ''): ?> yang cocok dengan pencarian "<?= htmlspecialchars($search) ?>"<?php endif; ?>.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const currentPath = window.location.pathname;
      const menuLinks = document.querySelectorAll('.menu-link');
      let activeTarget = null;

      if (currentPath.includes('pages/dashboard.php')) {
        activeTarget = 'dashboard';
      } else if (currentPath.includes('/todo/')) {
        activeTarget = 'todo';
      } else if (currentPath.includes('/notes/')) {
        activeTarget = 'notes';
      } else if (currentPath.includes('master/')) {
        activeTarget = 'master';
      }

      menuLinks.forEach(link => {
        link.classList.remove('active');
        if (link.dataset.target === activeTarget) {
          link.classList.add('active');
        }
      });

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