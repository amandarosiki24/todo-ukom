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

// Ambil daftar role
$roles_result = $conn->query("SELECT id, name FROM roles ORDER BY name");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $new_username = trim($_POST['username'] ?? '');
  $new_email = trim($_POST['email'] ?? '');
  $new_password = $_POST['password'] ?? '';
  $new_role_id = (int)($_POST['role_id'] ?? 0);
  $avatar_name = 'default.png';

  // Validasi (TANPA batas minimal password)
  if (empty($new_username)) {
    $message = "Username wajib diisi!";
  } elseif (empty($new_email) || !filter_var($new_email, FILTER_VALIDATE_EMAIL)) {
    $message = "Email tidak valid!";
  } elseif (empty($new_password)) {
    $message = "Password wajib diisi!";
  } elseif ($new_role_id <= 0) {
    $message = "Pilih role!";
  } else {
    // Cek duplikat
    $check = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $check->bind_param("ss", $new_username, $new_email);
    $check->execute();
    $check->store_result();
    if ($check->num_rows > 0) {
      $message = "Username atau email sudah digunakan!";
    }
    $check->close();
  }

  // === UPLOAD AVATAR (DIPERBAIKI TOTAL) ===
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
      // PATH AMAN DENGAN __DIR__ (bukan DOCUMENT_ROOT)
      $uploadDir = realpath(__DIR__ . '/../uploads/avatars') . DIRECTORY_SEPARATOR;

      // BUAT FOLDER OTOMATIS
      if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
          $message = "Gagal membuat folder upload.";
        }
      }

      // CEK PERMISSION
      if (!$message && !is_writable($uploadDir)) {
        $message = "Folder upload tidak bisa ditulis! Klik kanan folder → Properties → Security → Full control untuk Everyone.";
      }

      if (!$message) {
        $avatar_name = 'ava_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_path = $uploadDir . $avatar_name;

        if (move_uploaded_file($file['tmp_name'], $upload_path)) {
          // SUKSES
        } else {
          $message = "Gagal upload avatar. Cek permission folder.";
          $avatar_name = 'default.png';
        }
      }
    }
  }

  // Simpan user
  if (!$message) {
    $hashed = password_hash($new_password, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, ava) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $new_username, $new_email, $hashed, $new_role_id, $avatar_name);
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
    * { margin: 0; padding: 0; box-sizing: border-box; }
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
      display: flex;
      flex-direction: column;
    }
    .sidebar h4 {
      text-align: center;
      font-weight: 700;
      margin-bottom: 30px;
      font-size: 1.5rem;
      color: var(--logo) !important;
    }
    .sidebar h4 i { color: var(--logo) !important; margin-right: 8px; }
    .sidebar .menu-link {
      display: flex; align-items: center; color: #fff; padding: 12px 20px;
      text-decoration: none; font-weight: 500; border-left: 4px solid transparent;
      position: relative;
    }
    .sidebar .menu-link i { width: 25px; margin-right: 12px; font-size: 1.1rem; }
    .sidebar .menu-link:hover, .sidebar .menu-link.active {
      background-color: var(--secondary); border-left-color: #fff;
    }
    .sidebar .menu-link.has-submenu::after {
      content: '\f078'; font-family: 'Font Awesome 6 Free'; font-weight: 900;
      position: absolute; right: 20px; font-size: 0.8rem;
    }
    .sidebar .menu-link.active.has-submenu::after { transform: rotate(180deg); }
    .submenu {
      background-color: #fbe7e7; max-height: 0; overflow: hidden; opacity: 0;
      transition: max-height 0.4s ease, opacity 0.3s ease;
    }
    .submenu.active { max-height: 300px; opacity: 1; padding: 8px 0; margin-bottom: 15px; }
    .submenu a {
      display: block; color: var(--secondary); padding: 8px 20px 8px 57px;
      font-size: 0.9rem; text-decoration: none;
    }
    .submenu a i { margin-right: 8px; font-size: 0.8rem; color: #A46C4E; }
    .submenu a:hover { background-color: #f8d7d7; color: #7a4e2f; }
    .logout-btn {
      display: block; background-color: #A46C4E; color: #fff; border-radius: 8px;
      text-align: center; margin: 25px 20px; padding: 10px 0; text-decoration: none;
      font-weight: 600;
    }
    .logout-btn i { margin-right: 10px; }
    .topbar {
      height: 65px; background-color: var(--dark); border-bottom: 3px solid var(--primary);
      padding: 0 25px; display: flex; align-items: center; justify-content: space-between;
      position: fixed; left: 250px; right: 0; top: 0; z-index: 999; color: #fff;
    }
    .logo-section { display: flex; align-items: center; gap: 12px; }
    .logo-icon { font-size: 1.6rem; color: var(--logo) !important; }
    .brand { font-weight: 700; font-size: 1.3rem; color: var(--logo) !important; }
    .motto { font-size: 0.75rem; color: var(--logo); font-style: italic; margin-top: -2px; }
    .user-section { display: flex; align-items: center; gap: 12px; }
    .username { font-weight: 600; font-size: 1rem; }
    .profile-icon {
      width: 42px; height: 42px; border-radius: 50%; background-color: #452c2c;
      display: flex; align-items: center; justify-content: center; overflow: hidden;
      box-shadow: 0 2px 5px rgba(0,0,0,0.15);
    }
    .profile-icon:hover { transform: scale(1.1); }
    .avatar-img { width: 100%; height: 100%; object-fit: cover; border-radius: 50%; }
    .content {
      margin-left: 250px; margin-top: 80px; padding: 40px;
      min-height: calc(100vh - 80px); background-color: var(--light);
    }
    .page-title {
      font-weight: 700; color: var(--primary); margin-bottom: 20px;
      display: flex; align-items: center; gap: 10px;
    }
    .page-title i { color: var(--primary) !important; font-size: 1.4rem; }
    .card {
      background: #fff; border-radius: 16px; box-shadow: 0 10px 30px rgba(0,0,0,0.1);
      padding: 30px; max-width: 500px; margin: 0 auto;
    }
    .form-label { font-weight: 600; color: var(--primary); margin-bottom: 8px; display: block; }
    .form-control, .form-select {
      border-radius: 10px; padding: 12px 14px; border: 1.5px solid #e3c4b7;
      background: #fffaf8; font-size: 15px;
    }
    .form-control:focus, .form-select:focus {
      border-color: var(--logo); box-shadow: 0 0 0 0.2rem rgba(249, 182, 165, 0.25);
    }
    .btn-primary {
      background: var(--secondary); border: none; padding: 12px;
      border-radius: 10px; font-weight: 600; width: 100%;
      transition: all 0.2s ease;
    }
    .btn-primary:hover { background: #7a4e2f; transform: translateY(-2px); }
    .btn-secondary {
      background: #6c757d; border: none; padding: 12px;
      border-radius: 10px; font-weight: 600; width: 100%;
    }
    .btn-secondary:hover { background: #5a6268; }

    /* AVATAR UPLOAD MODERN */
    .avatar-upload {
      display: flex;
      justify-content: center;
      align-items: center;
      margin-bottom: 24px;
      position: relative;
    }
    .avatar-upload input { display: none; }
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
      box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .avatar-preview:hover {
      transform: scale(1.05);
      box-shadow: 0 6px 16px rgba(0,0,0,0.15);
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
    .avatar-preview:hover .overlay { opacity: 1; }
    .avatar-preview .overlay i { font-size: 28px; }

    /* IKON PUTIH: Hanya sidebar, topbar, tombol, dan logout */
    .sidebar .menu-link i,
    .topbar i:not(.logo-icon),
    .logout-btn i,
    .profile-icon i,
    .btn-primary i,
    .btn-secondary i {
      color: white !important;
    }

    /* IKON PAGE TITLE: Coklat seperti teks */
    .page-title i {
      color: var(--primary) !important;
    }

    /* IKON KAMERA: Pink seperti logo */
    .avatar-preview .overlay i {
      color: var(--logo) !important;
    }

    /* LOGOUT DI BAWAH */
    .sidebar-footer {
      margin-top: auto;
      padding: 20px 20px 30px;
    }
  </style>
</head>
<body>
  <!-- SIDEBAR -->
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

  <!-- TOPBAR -->
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
  </div>

  <!-- CONTENT -->
  <div class="content">
    <h3 class="page-title"><i class="fa-solid fa-user-plus"></i> Tambah User Baru</h3>

    <?php if ($message): ?>
      <div class="alert alert-<?= $message_type ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($message) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card">
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
            <?php while ($role = $roles_result->fetch_assoc()): ?>
              <option value="<?= $role['id'] ?>" <?= (isset($_POST['role_id']) && $_POST['role_id'] == $role['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($role['name']) ?>
              </option>
            <?php endwhile; ?>
          </select>
        </div>

        <div class="d-flex gap-2">
          <button type="submit" class="btn btn-primary">
            <i class="fa-solid fa-save"></i> Simpan User
          </button>
          <a href="list.php" class="btn btn-secondary">
            <i class="fa-solid fa-arrow-left"></i> Kembali
          </a>
        </div>
      </form>
    </div>
  </div>

  <!-- JavaScript untuk Preview Avatar -->
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
  </script>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>