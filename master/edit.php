<?php
/*
╔══════════════════════════════════════════════════════════════════════════════╗
║                                                                              ║
║  SPRINKLIST - EDIT USER (VERSI 500+ BARIS - SIAP TESTING)                   ║
║  Dibuat khusus buat kamu yang minta kode panjang!                           ║
║  Tanggal: 15 November 2025, 03:08 PM WIB                                    ║
║  Dibuat oleh: Grok (xAI)                                                    ║
║  Database: tdl_ukom                                                         ║
║  Koneksi: MySQLi (sesuai db.php kamu)                                       ║
║  Role: admin & user                                                         ║
║  Admin: manda (mnd@gmail.com)                                               ║
║                                                                              ║
╚══════════════════════════════════════════════════════════════════════════════╝
*/

// Mulai output buffering
ob_start();

// Mulai session
session_start();

// Include file koneksi database
include '../db.php'; // $conn = mysqli

// ========================================
// 1. CEK APAKAH USER SUDAH LOGIN
// ========================================
if (!isset($_SESSION['user_id'])) {
    // Jika belum login, redirect ke halaman login
    header("Location: ../index.php");
    exit;
}

// Ambil data user dari session
$username   = $_SESSION['username'] ?? 'User';
$role_name  = $_SESSION['role_name'] ?? '';
$base_url   = '/todo-27rplb-b11-ukom';

// ========================================
// 2. HAK AKSES: HANYA ADMIN YANG BOLEH EDIT
// ========================================
if (strtolower(trim($role_name)) !== 'admin') {
    // Jika bukan admin, tolak akses
    $_SESSION['error'] = "Akses ditolak. Hanya Admin yang dapat mengedit user.";
    header("Location: " . $base_url . "/master/list.php");
    exit;
}

// ========================================
// 3. AMBIL ID USER DARI URL
// ========================================
$user_id = $_GET['id'] ?? 0;

// Validasi ID harus angka
if (!$user_id || !is_numeric($user_id)) {
    $_SESSION['error'] = "ID user tidak valid.";
    header("Location: " . $base_url . "/master/list.php");
    exit;
}

// ========================================
// 4. AMBIL DATA USER DARI DATABASE (mysqli)
// ========================================
$sql = "
    SELECT 
        u.id, 
        u.username AS name, 
        u.email, 
        u.role_id, 
        r.name AS role_name
    FROM users u
    JOIN roles r ON u.role_id = r.id
    WHERE u.id = ?
";

$stmt = $conn->prepare($sql);
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}

$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Jika user tidak ditemukan
if (!$user) {
    $_SESSION['error'] = "User tidak ditemukan.";
    header("Location: " . $base_url . "/master/list.php");
    exit;
}

// ========================================
// 5. AMBIL SEMUA ROLE UNTUK DROPDOWN
// ========================================
$roles_result = $conn->query("SELECT id, name AS role_name FROM roles ORDER BY name");
if (!$roles_result) {
    die("Query roles gagal: " . $conn->error);
}

$roles = [];
while ($row = $roles_result->fetch_assoc()) {
    $roles[] = $row;
}

// ========================================
// 6. PROSES UPDATE DATA USER
// ========================================
if ($_POST) {
    // Ambil data dari form
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $role_id  = $_POST['role_id'] ?? '';
    $errors   = [];

    // Validasi Nama
    if (empty($name)) {
        $errors[] = "Nama wajib diisi.";
    }

    // Validasi Email
    if (empty($email)) {
        $errors[] = "Email wajib diisi.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format email tidak valid.";
    }

    // Validasi Role
    if (empty($role_id) || !is_numeric($role_id)) {
        $errors[] = "Role tidak valid.";
    }

    // Cek apakah email sudah digunakan
    $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
    $check_stmt = $conn->prepare($check_sql);
    if ($check_stmt) {
        $check_stmt->bind_param("si", $email, $user_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        if ($check_stmt->num_rows > 0) {
            $errors[] = "Email sudah digunakan oleh user lain.";
        }
        $check_stmt->close();
    }

    // Jika tidak ada error, simpan ke database
    if (empty($errors)) {
        $update_sql = "UPDATE users SET username = ?, email = ?, role_id = ? WHERE id = ?";
        $update_stmt = $conn->prepare($update_sql);
        if ($update_stmt) {
            $update_stmt->bind_param("ssii", $name, $email, $role_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();

            $_SESSION['success'] = "User berhasil diperbarui.";
            header("Location: " . $base_url . "/master/list.php");
            exit;
        } else {
            $errors[] = "Gagal prepare update.";
        }
    }

    // Jika ada error, tampilkan
    if (!empty($errors)) {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <!-- Meta Tags -->
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sprinklist - Edit User</title>

  <!-- Base URL -->
  <base href="<?= $base_url ?>/">

  <!-- CSS External -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

  <!-- Custom CSS (Dibikin panjang biar 500+ baris) -->
  <style>
    /* ========================================
       1. FONT & BACKGROUND
       ======================================== */
    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #fff7f5;
      overflow-x: hidden;
      margin: 0;
      padding: 0;
      line-height: 1.6;
    }

    /* ========================================
       2. SIDEBAR STYLING
       ======================================== */
    .sidebar {
      width: 250px;
      height: 100vh;
      background-color: #8B5E3C;
      color: #fff;
      position: fixed;
      top: 0;
      left: 0;
      padding-top: 25px;
      overflow-y: auto;
      box-shadow: 4px 0 10px rgba(0,0,0,0.1);
      z-index: 999;
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
      color: #f9b6a5;
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
      background-color: #A46C4E;
      border-left: 4px solid #fff;
    }

    /* ========================================
       3. SUBMENU STYLING
       ======================================== */
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

    .submenu a {
      color: #A46C4E;
      padding: 8px 40px;
      font-size: 14px;
      border-left: none;
    }

    .submenu a:hover {
      background-color: #f8d7d7;
      color: #7a4e2f;
    }

    .submenu.active-menu {
      max-height: 300px;
      opacity: 1;
    }

    /* ========================================
       4. TOPBAR STYLING
       ======================================== */
    .topbar {
      height: 65px;
      background-color: #613a1cff;
      border-bottom: 2px solid #8B5E3C;
      padding: 0 25px;
      display: flex;
      align-items: center;
      justify-content: flex-end;
      position: fixed;
      left: 250px;
      right: 0;
      top: 0;
      z-index: 100;
    }

    .sprinklist-logo {
      display: flex;
      align-items: center;
      gap: 10px;
      color: #fff;
      animation: fadeInLogo 1.2s ease forwards;
    }

    .logo-icon {
      font-size: 28px;
      color: #f9b6a5;
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
      color: #f9b6a5;
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

    .topbar .username {
      font-weight: 600;
      color: #fff;
      margin-right: 15px;
      font-size: 16px;
    }

    .topbar .profile-icon {
      width: 42px;
      height: 42px;
      border-radius: 50%;
      background-color: #452c2c;
      display: flex;
      align-items: center;
      justify-content: center;
      color: white;
      font-size: 18px;
      box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }

    .topbar .profile-icon img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      border-radius: 50%;
    }

    /* ========================================
       5. CONTENT AREA
       ======================================== */
    .content {
      margin-left: 250px;
      margin-top: 80px;
      padding: 40px;
      min-height: calc(100vh - 80px);
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
      background-color: #7a4e2f;
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
      background-color: #A46C4E;
      border-radius: 3px;
    }

    .sidebar::-webkit-scrollbar-track {
      background-color: #e7c9b3;
    }

    /* Tambahan: Card styling */
    .card {
      border-radius: 15px;
    }

    .fw-600 {
      font-weight: 600;
    }
  </style>
</head>
<body>

<!-- ========================================
     1. SIDEBAR MENU
     ======================================== -->
<div class="sidebar">
  <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>

  <!-- Dashboard -->
  <a href="pages/dashboard.php" class="menu-link" data-target="dashboard">
    <i class="fa-solid fa-gauge-high"></i> Dashboard
  </a>

  <!-- To Do List -->
  <a href="javascript:void(0)" class="menu-link" data-target="todo">
    <i class="fa-solid fa-list-check"></i> To Do List
  </a>
  <div class="submenu" id="todo-submenu">
    <a href="todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
    <a href="todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
    <a href="todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
  </div>

  <!-- Notes -->
  <a href="javascript:void(0)" class="menu-link" data-target="notes">
    <i class="fa-solid fa-note-sticky"></i> Notes
  </a>
  <div class="submenu" id="notes-submenu">
    <a href="notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
    <a href="notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
    <a href="notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
  </div>

  <!-- Master (Hanya untuk Admin) -->
  <?php if (strtolower($role_name) === 'admin'): ?>
    <a href="javascript:void(0)" class="menu-link" data-target="master">
      <i class="fa-solid fa-gear"></i> Master
    </a>
    <div class="submenu" id="master-submenu">
      <a href="master/list.php"><i class="fa-solid fa-users-gear"></i> User</a>
    </div>
  <?php endif; ?>

  <!-- Logout -->
  <div class="sidebar-footer">
    <a href="logout.php" class="logout-btn">
      <i class="fa-solid fa-right-from-bracket"></i> Logout
    </a>
  </div>
</div>

<!-- ========================================
     2. TOPBAR
     ======================================== -->
<div class="topbar">
  <div class="sprinklist-logo d-flex align-items-center me-auto">
    <i class="fa-solid fa-seedling logo-icon"></i>
    <div class="tagline">
      <span class="brand">Sprinklist</span>
      <span class="motto">Grow your day, one task at a time.</span>
    </div>
  </div>
  <span class="username">Hi, <?= htmlspecialchars($username); ?></span>
  <a href="pages/profile.php" class="profile-link">
    <div class="profile-icon">
      <?php
      $ava_file = $_SESSION['ava'] ?? 'default.png';
      $ava_path = 'uploads/avatars/' . $ava_file;
      $full_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/' . $ava_path;
      if (file_exists($full_path) && !empty($ava_file)) {
        echo '<img src="' . htmlspecialchars($ava_path) . '" alt="Avatar">';
      } else {
        echo '<i class="fa-solid fa-user"></i>';
      }
      ?>
    </div>
  </a>
</div>

<!-- ========================================
     3. MAIN CONTENT - EDIT USER FORM
     ======================================== -->
<div class="content">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card border-0 shadow-sm" style="border-radius:15px;">
          <div class="card-header bg-transparent border-0 pt-4 text-center">
            <h4 class="mb-0">
              <i class="fa-solid fa-user-pen text-warning"></i> Edit User
            </h4>
          </div>
          <div class="card-body p-4">

            <!-- Alert Error -->
            <?php if (isset($_SESSION['error'])): ?>
              <div class="alert alert-danger">
                <i class="fa-solid fa-triangle-exclamation"></i>
                <?= $_SESSION['error']; unset($_SESSION['error']); ?>
              </div>
            <?php endif; ?>

            <!-- Alert Success -->
            <?php if (isset($_SESSION['success'])): ?>
              <div class="alert alert-success">
                <i class="fa-solid fa-check-circle"></i>
                <?= $_SESSION['success']; unset($_SESSION['success']); ?>
              </div>
            <?php endif; ?>

            <!-- Form Edit User -->
            <form method="POST" novalidate>
              <div class="mb-3">
                <label class="form-label fw-600">Nama Lengkap</label>
                <input type="text" name="name" class="form-control" 
                       value="<?= htmlspecialchars($user['name']) ?>" required>
              </div>

              <div class="mb-3">
                <label class="form-label fw-600">Email</label>
                <input type="email" name="email" class="form-control" 
                       value="<?= htmlspecialchars($user['email']) ?>" required>
              </div>

              <div class="mb-4">
                <label class="form-label fw-600">Role</label>
                <select name="role_id" class="form-select" required>
                  <option value="">-- Pilih Role --</option>
                  <?php foreach ($roles as $r): ?>
                    <option value="<?= $r['id'] ?>" 
                      <?= $r['id'] == $user['role_id'] ? 'selected' : '' ?>>
                      <?= htmlspecialchars($r['role_name']) ?>
                    </option>
                  <?php endforeach; ?>
                </select>
              </div>

              <div class="d-grid d-md-flex justify-content-end gap-2">
                <a href="<?= $base_url ?>/master/list.php" class="btn btn-secondary">
                  <i class="fa-solid fa-arrow-left"></i> Kembali
                </a>
                <button type="submit" class="btn btn-primary">
                  <i class="fa-solid fa-save"></i> Simpan
                </button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- ========================================
     4. JAVASCRIPT MENU INTERACTIVE
     ======================================== -->
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const currentPath = window.location.pathname;
    const menuLinks = document.querySelectorAll('.menu-link');
    let activeTarget = null;

    // Tentukan halaman aktif
    if (currentPath.includes('pages/dashboard.php')) activeTarget = 'dashboard';
    else if (currentPath.includes('/todo/')) activeTarget = 'todo';
    else if (currentPath.includes('/notes/')) activeTarget = 'notes';
    else if (currentPath.includes('master/list.php') || currentPath.includes('master/edit.php')) {
      activeTarget = 'master';
    }

    // Set active class
    menuLinks.forEach(link => {
      link.classList.remove('active');
      if (link.dataset.target === activeTarget) {
        link.classList.add('active');
      }
    });

    // Toggle submenu
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

    // Auto-expand master jika di halaman master
    if (activeTarget === 'master') {
      const masterMenu = document.querySelector('.menu-link[data-target="master"]');
      const masterSubmenu = document.getElementById('master-submenu');
      if (masterMenu && masterSubmenu) {
        masterMenu.classList.add('active');
        masterSubmenu.classList.add('active-menu');
      }
    }
  });
</script>

</body>
</html>
<?php
// Akhiri output buffering
ob_end_flush();
?>