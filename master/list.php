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
$sql = "
  SELECT
    users.id,
    users.username,
    users.email,
    roles.name AS role_name,
    users.created_at
  FROM users
  LEFT JOIN roles ON users.role_id = roles.id
  ORDER BY users.id DESC
";
$result = $conn->query($sql);
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
      background: var(--primary);
      color: #fff;
      position: fixed;
      top: 0;
      left: 0;
      padding: 25px 0;
      overflow-y: auto;
      box-shadow: 4px 0 15px rgba(0,0,0,0.1);
      z-index: 1000;
      transition: all 0.3s ease;
    }
    .sidebar h4 {
      text-align: center;
      font-weight: 700;
      margin-bottom: 30px;
      font-size: 1.5rem;
      color: var(--logo) !important;
      animation: fadeSlideIn 1s ease forwards;
    }
    .sidebar h4 i {
      color: var(--logo) !important;
      margin-right: 8px;
      animation: bloom 1.6s ease-in-out forwards;
    }
    @keyframes fadeSlideIn {
      0% { opacity: 0; transform: translateY(-15px) scale(0.9); }
      100% { opacity: 1; transform: translateY(0) scale(1); }
    }
    @keyframes bloom {
      0% { transform: scale(0) rotate(-45deg); opacity: 0; }
      60% { transform: scale(1.2) rotate(10deg); opacity: 1; }
      100% { transform: scale(1) rotate(0); }
    }
    .sidebar .menu-link {
      display: flex;
      align-items: center;
      color: #fff;
      padding: 12px 20px;
      text-decoration: none;
      font-weight: 500;
      transition: all 0.3s ease;
      border-left: 4px solid transparent;
      position: relative;
    }
    .sidebar .menu-link i {
      width: 25px;
      margin-right: 12px;
      font-size: 1.1rem;
    }
    .sidebar .menu-link:hover,
    .sidebar .menu-link.active {
      background-color: var(--secondary);
      border-left-color: #fff;
    }
    .sidebar .menu-link.has-submenu::after {
      content: '\f078';
      font-family: 'Font Awesome 6 Free';
      font-weight: 900;
      position: absolute;
      right: 20px;
      font-size: 0.8rem;
      transition: transform 0.3s ease;
    }
    .sidebar .menu-link.active.has-submenu::after {
      transform: rotate(180deg);
    }
    .submenu {
      background-color: #fbe7e7;
      max-height: 0;
      overflow: hidden;
      opacity: 0;
      transition: max-height 0.4s ease, opacity 0.3s ease, padding 0.4s ease;
      border-top: 1px solid #f3d1c8;
      border-bottom: 1px solid #f3d1c8;
    }
    .submenu.active {
      max-height: 300px;
      opacity: 1;
      padding: 8px 0;
    }
    .submenu a {
      display: block;
      color: var(--secondary);
      padding: 8px 20px 8px 57px;
      font-size: 0.9rem;
      text-decoration: none;
      transition: background 0.2s ease;
    }
    .submenu a i {
      margin-right: 8px;
      font-size: 0.8rem;
      color: #A46C4E;
    }
    .submenu a:hover {
      background-color: #f8d7d7;
      color: #7a4e2f;
    }
    .sidebar-footer {
      position: absolute;
      bottom: 20px;
      width: 100%;
      padding: 0 20px;
    }
    .logout-btn {
      display: block;
      background-color: #A46C4E;
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
      background-color: rgba(255, 255, 255, 0.1);
      color: #fff;
    }
    .logout-btn i {
      margin-right: 10px;
    }
    .topbar {
      height: 65px;
      background-color: var(--dark);
      border-bottom: 3px solid var(--primary);
      padding: 0 25px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: fixed;
      left: 250px;
      right: 0;
      top: 0;
      z-index: 999;
      color: #fff;
    }
    .logo-section {
      display: flex;
      align-items: center;
      gap: 12px;
      animation: fadeInLogo 1.2s ease forwards;
    }
    @keyframes fadeInLogo {
      from { opacity: 0; transform: translateX(-15px); }
      to { opacity: 1; transform: translateX(0); }
    }
    .logo-icon {
      font-size: 1.6rem;
      color: var(--logo) !important;
      animation: bounceGrow 1.5s infinite alternate ease-in-out;
    }
    @keyframes bounceGrow {
      0% { transform: scale(1) translateY(0); }
      50% { transform: scale(1.1) translateY(-2px); }
      100% { transform: scale(1) translateY(0); }
    }
    .brand {
      font-weight: 700;
      font-size: 1.3rem;
      color: var(--logo) !important;
    }
    .motto {
      font-size: 0.75rem;
      color: var(--logo);
      font-style: italic;
      opacity: 0.9;
      display: block;
      margin-top: -2px;
    }
    .user-section {
      display: flex;
      align-items: center;
      gap: 12px;
    }
    .username {
      font-weight: 600;
      font-size: 1rem;
      white-space: nowrap;
    }
    .profile-icon {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background-color: #452c2c;
      display: flex;
      align-items: center;
      justify-content: center;
      overflow: hidden;
      box-shadow: 0 2px 5px rgba(0,0,0,0.15);
      transition: all 0.3s ease;
    }
    .profile-icon:hover {
      transform: scale(1.1);
      box-shadow: 0 4px 10px rgba(0,0,0,0.2);
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
    }
    .table-container {
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 15px rgba(0,0,0,0.08);
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
    .btn-edit, .btn-delete, .btn-detail {
      padding: 6px 10px;
      border: none;
      border-radius: 6px;
      font-size: 0.9rem;
      margin: 0 4px;
    }
    .btn-detail { background-color: #4CAF50; color: #fff; }
    .btn-detail:hover { background-color: #388E3C; }
    .btn-edit { background-color: var(--accent); color: #fff; }
    .btn-edit:hover { background-color: #E39E5D; }
    .btn-delete { background-color: var(--danger); color: #fff; }
    .btn-delete:hover { background-color: #C62828; }
    .badge {
      font-size: 0.8rem;
      padding: 6px 10px;
      border-radius: 20px;
    }
    .sidebar::-webkit-scrollbar {
      width: 6px;
    }
    .sidebar::-webkit-scrollbar-thumb {
      background-color: #A46C4E;
      border-radius: 3px;
    }
    .sidebar::-webkit-scrollbar-track {
      background-color: #e7c9b3;
    }
   
    @media (max-width: 992px) {
      .sidebar {
        transform: translateX(-100%);
      }
      .topbar, .content {
        left: 0 !important;
      }
      .sidebar.active {
        transform: translateX(0);
      }
    }
    @media (max-width: 576px) {
      .user-section {
        gap: 8px;
      }
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
    }

    /* PERBAIKAN UTAMA: Semua ikon putih, KECUALI logo */
    .sidebar i:not(.sidebar h4 i):not(.logo-icon),
    .topbar i:not(.logo-icon),
    .logout-btn i,
    .profile-icon i,
    .btn-add i,
    .btn-detail i,
    .btn-edit i,
    .btn-delete i,
    .btn-secondary i {
      color: white !important;
    }

    .sidebar .menu-link:hover i,
    .submenu a:hover i,
    .logout-btn:hover i {
      color: white !important;
    }

    #master-submenu a i {
      color: #A46C4E !important;
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>
    <a href="../pages/dashboard.php" class="menu-link" data-target="dashboard">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <a href="../todo/personal.php" class="menu-link" data-target="todo">
      <i class="fa-solid fa-list-check"></i> To Do List
    </a>
    <div class="submenu" id="todo-submenu">
      <a href="../todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
      <a href="../todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
      <a href="../todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
    </div>
    <a href="../notes/personal.php" class="menu-link" data-target="notes">
      <i class="fa-solid fa-note-sticky"></i> Notes
    </a>
    <div class="submenu" id="notes-submenu">
      <a href="../notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
      <a href="../notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
      <a href="../notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
    </div>
    <?php if (strtolower($role_name) === 'admin'): ?>
      <a href="../master/list.php" class="menu-link active" data-target="master">
        <i class="fa-solid fa-gear"></i> Master
      </a>
      <div class="submenu active" id="master-submenu">
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
    <div class="logo-section">
      <i class="fa-solid fa-seedling logo-icon"></i>
      <div>
        <div class="brand">Sprinklist</div>
        <div class="motto">Grow your day, one task at a time.</div>
      </div>
    </div>
    <div class="user-section">
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
  </div>

  <div class="content">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h3 class="page-title"><i class="fa-solid fa-users-gear"></i> Manage Accounts</h3>
      <a href="tambah.php" class="btn-add"><i class="fa-solid fa-plus"></i> Tambah User</a>
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
              <?php $no = 1; while($row = $result->fetch_assoc()): ?>
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
                      <a href="?delete_id=<?= $row['id']; ?>" class="btn btn-sm btn-delete" title="Hapus"
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
                <td colspan="6" class="text-muted py-4">Belum ada data user.</td>
              </tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const menuLinks = document.querySelectorAll('.menu-link.has-submenu');
      menuLinks.forEach(link => {
        link.addEventListener('click', function (e) {
          e.preventDefault();
          const target = this.dataset.target;
          const submenu = document.getElementById(target + '-submenu');
          const isActive = this.classList.contains('active');
          document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
          document.querySelectorAll('.submenu').forEach(sm => sm.classList.remove('active'));
          if (!isActive && submenu) {
            this.classList.add('active');
            submenu.classList.add('active');
          }
        });
      });
      const currentFile = window.location.pathname.split('/').pop();
      const masterFiles = ['list.php', 'detail.php', 'edit.php', 'add-user.php'];
      if (masterFiles.includes(currentFile)) {
        const masterMenu = document.querySelector('[data-target="master"]');
        const masterSubmenu = document.getElementById('master-submenu');
        if (masterMenu && masterSubmenu) {
          masterMenu.classList.add('active');
          masterSubmenu.classList.add('active');
        }
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>