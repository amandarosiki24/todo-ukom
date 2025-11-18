<?php
ob_start();
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

$username = $_SESSION['username'] ?? 'User';
$role_name = $_SESSION['role_name'] ?? '';
$base_url = '/todo-27rplb-b11-ukom';

if (strtolower(trim($role_name)) !== 'admin') {
  $_SESSION['error'] = "Akses ditolak. Hanya Admin yang dapat mengedit user.";
  header("Location: " . $base_url . "/master/list.php");
  exit;
}

$user_id = $_GET['id'] ?? 0;
if (!$user_id || !is_numeric($user_id)) {
  $_SESSION['error'] = "ID user tidak valid.";
  header("Location: " . $base_url . "/master/list.php");
  exit;
}

$sql = "SELECT u.id, u.username AS name, u.email, u.role_id, u.ava, r.name AS role_name
        FROM users u JOIN roles r ON u.role_id = r.id WHERE u.id = ?";
$stmt = $conn->prepare($sql);
if (!$stmt) die("Prepare failed: " . $conn->error);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
  $_SESSION['error'] = "User tidak ditemukan.";
  header("Location: " . $base_url . "/master/list.php");
  exit;
}

$roles_result = $conn->query("SELECT id, name AS role_name FROM roles ORDER BY name");
if (!$roles_result) die("Query roles gagal: " . $conn->error);
$roles = [];
while ($row = $roles_result->fetch_assoc()) {
  $roles[] = $row;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $role_id = $_POST['role_id'] ?? '';
  $errors = [];
  
  if (empty($name)) $errors[] = "Nama wajib diisi.";
  elseif (strlen($name) < 3) $errors[] = "Nama minimal 3 karakter.";
  
  if (empty($email)) $errors[] = "Email wajib diisi.";
  elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Format email tidak valid.";
  
  if (empty($role_id) || !is_numeric($role_id)) $errors[] = "Role tidak valid.";
  
  $check_sql = "SELECT id FROM users WHERE email = ? AND id != ?";
  $check_stmt = $conn->prepare($check_sql);
  if ($check_stmt) {
    $check_stmt->bind_param("si", $email, $user_id);
    $check_stmt->execute();
    $check_stmt->store_result();
    if ($check_stmt->num_rows > 0) $errors[] = "Email sudah digunakan oleh user lain.";
    $check_stmt->close();
  }
  
  $avatar_name = $user['ava'];
  
  if (!empty($_FILES['avatar']['name'])) {
    $file = $_FILES['avatar'];
    $allowed_types = ['image/jpeg', 'image/png', 'image/jpg'];
    $allowed_extensions = ['jpg', 'jpeg', 'png'];
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file['type'], $allowed_types)) {
      $errors[] = "Format gambar harus JPG atau PNG.";
    } elseif (!in_array($file_extension, $allowed_extensions)) {
      $errors[] = "Ekstensi file harus jpg, jpeg, atau png.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
      $errors[] = "Ukuran gambar maksimal 2MB.";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
      $errors[] = "Terjadi kesalahan saat upload file.";
    } else {
      $upload_dir = $_SERVER['DOCUMENT_ROOT'] . $base_url . "/uploads/avatars/";
      if (!is_dir($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
          $errors[] = "Gagal membuat folder upload.";
        }
      }
      
      $new_name = "ava_" . time() . "_" . rand(1000, 9999) . "." . $file_extension;
      $destination = $upload_dir . $new_name;
      
      if (move_uploaded_file($file['tmp_name'], $destination)) {
        if (!empty($user['ava']) && $user['ava'] !== 'default.png') {
          $old_file = $upload_dir . $user['ava'];
          if (file_exists($old_file)) unlink($old_file);
        }
        $avatar_name = $new_name;
      } else {
        $errors[] = "Gagal mengupload gambar.";
      }
    }
  }
  
  if (empty($errors)) {
    $update_sql = "UPDATE users SET username = ?, email = ?, role_id = ?, ava = ? WHERE id = ?";
    $update_stmt = $conn->prepare($update_sql);
    
    if ($update_stmt) {
      $update_stmt->bind_param("ssisi", $name, $email, $role_id, $avatar_name, $user_id);
      
      if ($update_stmt->execute()) {
        $update_stmt->close();
        
        if ($_SESSION['user_id'] == $user_id) {
          $_SESSION['username'] = $name;
          $_SESSION['ava'] = $avatar_name;
        }
        
        $_SESSION['success'] = "User berhasil diperbarui.";
        header("Location: " . $base_url . "/master/list.php");
        exit;
      } else {
        $errors[] = "Gagal memperbarui data user.";
      }
    }
  }
  
  if (!empty($errors)) {
    $_SESSION['error'] = implode("<br>", $errors);
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sprinklist - Edit User</title>
  <base href="<?= $base_url ?>/">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      font-family: 'Quicksand', sans-serif;
      background-color: #fff7f5;
      overflow-x: hidden;
    }
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
      overflow: hidden;
    }
    .avatar-img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
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
    .avatar-edit-container {
      position: relative;
      display: inline-block;
    }
    .avatar-edit-preview {
      width: 120px;
      height: 120px;
      object-fit: cover;
      border-radius: 50%;
      border: 4px solid #8B5E3C;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
      transition: all 0.3s ease;
    }
    .avatar-edit-preview:hover {
      transform: scale(1.05);
    }
    .avatar-edit-btn {
      position: absolute;
      bottom: 0;
      right: 0;
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #8B5E3C 0%, #A46C4E 100%);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
      transition: all 0.3s ease;
      color: #fff;
      border: 3px solid #fff;
    }
    .avatar-edit-btn:hover {
      transform: scale(1.1);
      background: linear-gradient(135deg, #6d4a2f 0%, #8B5E3C 100%);
    }
    .avatar-edit-btn i {
      font-size: 16px;
    }
    .card {
      border: none;
      border-radius: 15px;
      box-shadow: 0 5px 20px rgba(0, 0, 0, 0.08);
    }
    .card-header {
      background: linear-gradient(135deg, #8B5E3C 0%, #A46C4E 100%);
      color: #fff;
      padding: 20px;
      border-bottom: none;
      border-radius: 15px 15px 0 0;
    }
    .card-header h4 {
      margin: 0;
      font-weight: 700;
    }
    .card-body {
      padding: 35px;
    }
    .form-label {
    color: #8B5E3C !important;
}

    .form-control, .form-select {
      border: 2px solid #e0e0e0;
      border-radius: 8px;
      padding: 12px 15px;
      transition: all 0.3s ease;
    }
    .form-control:focus, .form-select:focus {
      border-color: #8B5E3C;
      box-shadow: 0 0 0 0.2rem rgba(139, 94, 60, 0.15);
    }
    .btn {
      padding: 12px 30px;
      font-weight: 600;
      border-radius: 8px;
      transition: all 0.3s ease;
      border: none;
    }
    .btn-back {
      background-color: #6c757d;
      color: #fff;
    }
    .btn-back:hover {
      background-color: #5a6268;
      color: #fff;
    }
    .btn-save {
      background: linear-gradient(135deg, #8B5E3C 0%, #A46C4E 100%);
      color: #fff;
    }
    .btn-save:hover {
      background: linear-gradient(135deg, #6d4a2f 0%, #8B5E3C 100%);
      color: #fff;
    }
    .alert {
      border-radius: 10px;
      padding: 15px 20px;
      margin-bottom: 25px;
      border: none;
      animation: slideDown 0.5s ease;
    }
    @keyframes slideDown {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<body>
  <div class="sidebar">
    <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>
    <a href="pages/dashboard.php" class="menu-link" data-target="dashboard">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>
    <a href="javascript:void(0)" class="menu-link" data-target="todo">
      <i class="fa-solid fa-list-check"></i> To Do List
    </a>
    <div class="submenu" id="todo-submenu">
      <a href="todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
      <a href="todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
      <a href="todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
    </div>
    <a href="javascript:void(0)" class="menu-link" data-target="notes">
      <i class="fa-solid fa-note-sticky"></i> Notes
    </a>
    <div class="submenu" id="notes-submenu">
      <a href="notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
      <a href="notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
      <a href="notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
    </div>
    <?php if (strtolower($role_name) === 'admin'): ?>
      <a href="javascript:void(0)" class="menu-link active" data-target="master">
        <i class="fa-solid fa-gear"></i> Master
      </a>
      <div class="submenu active-menu" id="master-submenu">
        <a href="master/list.php"><i class="fa-solid fa-users-gear"></i> User</a>
      </div>
    <?php endif; ?>
    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn">
        <i class="fa-solid fa-right-from-bracket"></i> Logout
      </a>
    </div>
  </div>

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
          echo '<img src="' . htmlspecialchars($ava_path) . '" alt="Avatar" class="avatar-img">';
        } else {
          echo '<i class="fa-solid fa-user"></i>';
        }
        ?>
      </div>
    </a>
  </div>

  <div class="content">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8">
          <div class="card">
            <div class="card-header text-center">
              <h4><i class="fa-solid fa-user-pen"></i> Edit User</h4>
            </div>
            <div class="card-body">
              <div class="text-center mb-4">
                <div class="avatar-edit-container">
                  <?php
                    $ava_now = $user['ava'] ?? 'default.png';
                    $ava_path = 'uploads/avatars/' . $ava_now;
                    $ava_full = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/' . $ava_path;
                    if (file_exists($ava_full) && !empty($ava_now)) {
                      echo '<img src="'. $base_url . '/' . $ava_path .'" class="avatar-edit-preview" id="avatarPreview">';
                    } else {
                      echo '<img src="'. $base_url .'/uploads/avatars/default.png" class="avatar-edit-preview" id="avatarPreview">';
                    }
                  ?>
                  <label for="avatarInput" class="avatar-edit-btn">
                    <i class="fa-solid fa-camera"></i>
                  </label>
                </div>
                <small class="text-muted d-block mt-2">Klik icon kamera untuk ganti foto</small>
              </div>

              <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger">
                  <i class="fa-solid fa-circle-exclamation"></i>
                  <?= $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
              <?php endif; ?>
              
              <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success">
                  <i class="fa-solid fa-circle-check"></i>
                  <?= $_SESSION['success']; unset($_SESSION['success']); ?>
                </div>
              <?php endif; ?>
              
              <form method="POST" enctype="multipart/form-data" novalidate>
                <input type="file" name="avatar" accept="image/jpeg,image/png,image/jpg" class="d-none" id="avatarInput">
                
                <div class="mb-3">
                  <label class="form-label"></i> Nama Lengkap</label>
                  <input type="text" name="name" class="form-control" value="<?= htmlspecialchars($user['name']) ?>" required>
                </div>
                
                <div class="mb-3">
                  <label class="form-label"></i> Email</label>
                  <input type="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
                </div>
                
                <div class="mb-4">
                  <label class="form-label"></i> Role</label>
                  <select name="role_id" class="form-select" required>
                    <option value="">-- Pilih Role --</option>
                    <?php foreach ($roles as $r): ?>
                      <option value="<?= $r['id'] ?>" <?= $r['id'] == $user['role_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($r['role_name']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
                
                <div class="d-flex justify-content-between mt-4">
                  <a href="<?= $base_url ?>/master/list.php" class="btn btn-back">
                    <i class="fa-solid fa-arrow-left"></i> Kembali
                  </a>
                  <button type="submit" class="btn btn-save">
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

  <script>
    document.getElementById('avatarInput').addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = function(e) {
          document.getElementById('avatarPreview').src = e.target.result;
        }
        reader.readAsDataURL(file);
      }
    });

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
</body>
</html>
<?php ob_end_flush(); ?>