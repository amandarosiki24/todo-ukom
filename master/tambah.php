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
$roles_result = $conn->query("SELECT id, name FROM roles ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_username = trim($_POST['username'] ?? '');
  $new_email = trim($_POST['email'] ?? '');
  $new_password = $_POST['password'] ?? ''; // Password tidak di-hash
  $new_role_id = (int)($_POST['role_id'] ?? 0);
  $avatar_name = 'default.png';

  if (empty($new_username)) {
    $message = "Username wajib diisi!";
  } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    $message = "Email tidak valid!";
  } elseif (empty($new_password)) {
    $message = "Password wajib diisi!";
  } elseif ($new_role_id <= 0) {
    $message = "Pilih role!";
  } else {
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $new_username, $new_email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
      $message = "Username atau email sudah digunakan!";
    }
    $check->close();
  }

  if (!$message && isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['avatar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $size = $file['size'];

    if (!in_array($ext, $allowed)) {
      $message = "Format file tidak didukung! Gunakan JPG, PNG, atau GIF.";
    } elseif ($size > 2 * 1024 * 1024) {
      $message = "Ukuran file maksimal 2MB!";
    } else {
      $uploadDir = realpath(__DIR__ . '/../uploads/avatars') . DIRECTORY_SEPARATOR;

      if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
          $message = "Gagal membuat folder upload.";
        }
      }

      if (!$message && !is_writable($uploadDir)) {
        $message = "Folder upload tidak bisa ditulis! Klik kanan folder → Properties → Security → Full control untuk Everyone.";
      }

      if (!$message) {
        $avatar_name = 'ava_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_path = $uploadDir . $avatar_name;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        } else {
          $message = "Gagal upload avatar. Cek permission folder.";
          $avatar_name = 'default.png';
        }
      }
    }
  }

  if (!$message) {
    // Password disimpan tanpa hash (plain text)
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, ava) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $new_username, $new_email, $new_password, $new_role_id, $avatar_name);
    if ($stmt->execute()) {
      $message = "User berhasil ditambahkan!";
      $message_type = "success";
      $_POST = [];
    } else {
      $message = "Gagal menyimpan user.";
    }
    $stmt->close();
  } else {
    $message_type = "danger";
  }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sprinklist - Tambah User</title>
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

    .card {
      background: #fff;
      border-radius: 16px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
      padding: 30px;
      max-width: 500px;
      margin: 0 auto;
      margin-top: 20px;
    }
    
    .form-label {
      font-weight: 600;
      color: var(--primary);
      margin-bottom: 8px;
      display: block;
    }

    .form-control,
    .form-select {
      border-radius: 10px;
      padding: 12px 14px;
      border: 1.5px solid #e3c4b7;
      background: #fffaf8;
      font-size: 15px;
    }

    .form-control:focus,
    .form-select:focus {
      border-color: var(--logo);
      box-shadow: 0 0 0 0.2rem rgba(249, 182, 165, 0.25);
    }

    .btn-save {
      background: var(--secondary) !important;
      border: none !important;
      color: #fff !important;
      padding: 12px;
      border-radius: 10px;
      font-weight: 600;
      width: 100%;
      transition: all 0.2s ease;
      box-shadow: none !important;
      outline: none !important;
    }

    .btn-save:hover,
    .btn-save:active,
    .btn-save:focus,
    .btn-save:focus-visible {
      background: #7a4e2f !important;
      color: #fff !important;
      transform: translateY(-2px);
      box-shadow: none !important;
    }

    .btn-back {
      background: #6c757d !important;
      border: none !important;
      color: #fff !important;
      padding: 12px;
      border-radius: 10px;
      font-weight: 600;
      width: 100%;
      text-decoration: none;
      display: inline-block;
      text-align: center;
      transition: all 0.2s ease;
    }

    .btn-back:hover,
    .btn-back:active,
    .btn-back:focus {
      background: #5a6268 !important;
      color: #fff !important;
      transform: translateY(-2px);
    }

    .avatar-upload {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 24px;
      position: relative;
    }

    .avatar-upload input {
      display: none;
    }

    .avatar-preview {
      width: 110px;
      height: 110px;
      border-radius: 50%;
      background-color: #f8e9e5;
      border: 4px solid var(--logo);
      overflow: hidden;
      position: relative;
      cursor: pointer;
      display: flex;
      justify-content: center;
      align-items: center;
      transition: all .3s ease;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    }

    .avatar-preview:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
    }

    .avatar-preview img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: none;
    }

    .avatar-preview .overlay {
      position: absolute;
      width: 100%;
      height: 100%;
      background: rgba(249, 182, 165, 0.7);
      color: white;
      display: flex;
      justify-content: center;
      align-items: center;
      opacity: 0;
      transition: opacity .3s ease;
      border-radius: 50%;
    }

    .avatar-preview:hover .overlay {
      opacity: 1;
    }

    .avatar-preview .overlay i {
      font-size: 28px;
    }

    .sidebar .menu-link i,
    .topbar i:not(.logo-icon),
    .logout-btn i,
    .profile-icon i,
    .btn-save i,
    .btn-back i {
      color: white !important;
    }

    .page-title i {
      color: var(--primary) !important;
    }

    .avatar-preview .overlay i {
      color: var(--logo) !important;
    }

    .sidebar-footer {
      margin-top: auto;
      padding: 20px 20px 30px;
    }

    .form-header {
      background-color: var(--primary);
      color: white;
      padding: 15px 20px;
      border-radius: 10px 10px 0 0;
      margin: -30px -30px 25px -30px;
      text-align: center;
    }

    .form-header h4 {
      font-weight: 700;
      display: flex;
      align-items: center;
      gap: 10px;
      justify-content: center;
      margin-bottom: 0;
      color: white !important;
    }

    .form-header i {
      color: white !important;
    }
    
    .button-group {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 12px;
    }
    
    .alert-container {
      max-width: 500px;
      margin: 0 auto 20px auto;
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
        $full_path = realpath(__DIR__ . '/../uploads/avatars/' . $ava_file);
        $web_path = '/uploads/avatars/' . $ava_file;
        if ($full_path && file_exists($full_path) && !empty($ava_file)) {
          echo '<img src="' . htmlspecialchars($web_path) . '" alt="Avatar" class="avatar-img">';
        } else {
          echo '<i class="fa-solid fa-user"></i>';
        }
        ?>
      </div>
    </a>
  </div>

  <div class="content">
    <?php if ($message): ?>
      <div class="alert-container">
        <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
          <?= htmlspecialchars($message) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="form-header" style="background-color: var(--dark); color: white; padding: 15px 20px; border-radius: 10px 10px 0 0; margin: -30px -30px 25px -30px;">
        <h4 class="mb-0" style="font-weight: 700; display: flex; align-items: center; gap: 10px;">
          <i class="fa-solid fa-user-plus"></i> Form Tambah User
        </h4>
      </div>

      <form method="POST" enctype="multipart/form-data">
        <div class="avatar-upload">
          <label for="avatar" class="avatar-preview" id="avatarBox">
            <img id="preview" src="" alt="" style="display:none;">
            <div class="overlay"><i class="fas fa-camera"></i></div>
          </label>
          <input type="file" name="avatar" id="avatar" accept="image/*" onchange="previewImage(event)">
        </div>

        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" name="username" class="form-control" value="<?= $_POST['username'] ?? '' ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="email" name="email" class="form-control" value="<?= $_POST['email'] ?? '' ?>" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Role</label>
          <select name="role_id" class="form-select" required>
            <option value="">-- Pilih Role --</option>
            <?php
            $roles_result->data_seek(0);
            while ($role = $roles_result->fetch_assoc()): ?>
              <option value="<?= $role['id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($role['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="button-group">
          <a href="list.php" class="btn-back">
            <i class="fa-solid fa-arrow-left"></i> Kembali
          </a>
          <button type="submit" class="btn-save">
            <i class="fa-solid fa-save"></i> Simpan
          </button>
        </div>
      </form>
    </div>
  </div>
  
  <script>
    function previewImage(event) {
      const reader = new FileReader();
      reader.onload = function() {
        const output = document.getElementById('preview');
        output.src = reader.result;
        output.style.display = 'block';
      };
      if (event.target.files[0]) {
        reader.readAsDataURL(event.target.files[0]);
      }
    }

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