<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

$username  = $_SESSION['username'];
$role_name = $_SESSION['role_name'];
$user_id = $_SESSION['user_id'];
$base_url = '/todo-27rplb-b11-ukom';

// Get todo ID from URL
if (!isset($_GET['id'])) {
  header("Location: personal.php");
  exit;
}

$todo_id = intval($_GET['id']);

// Fetch todo data
$stmt = $conn->prepare("SELECT * FROM todos WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $todo_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: personal.php");
  exit;
}

$todo = $result->fetch_assoc();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_todo'])) {
  $title = trim($_POST['title']);
  $description = trim($_POST['description']);
  $category_id = intval($_POST['category_id']);
  $photo_name = $todo['photo']; // Keep existing photo by default

  // Handle photo upload
  if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/todos/';
    
    // Create folder if not exists
    if (!is_dir($upload_dir)) {
      mkdir($upload_dir, 0777, true);
    }

    $file_tmp = $_FILES['photo']['tmp_name'];
    $file_ext = strtolower(pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION));
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($file_ext, $allowed_ext)) {
      // Delete old photo if exists
      if (!empty($todo['photo']) && file_exists($upload_dir . $todo['photo'])) {
        unlink($upload_dir . $todo['photo']);
      }

      // Generate unique filename
      $photo_name = 'todo_' . time() . '_' . uniqid() . '.' . $file_ext;
      $photo_path = $upload_dir . $photo_name;

      // Upload file
      if (!move_uploaded_file($file_tmp, $photo_path)) {
        $photo_name = $todo['photo']; // Keep old photo if upload fails
      }
    }
  }

  // Handle photo removal
  if (isset($_POST['remove_photo']) && $_POST['remove_photo'] === '1') {
    if (!empty($todo['photo'])) {
      $upload_dir = '../uploads/todos/';
      if (file_exists($upload_dir . $todo['photo'])) {
        unlink($upload_dir . $todo['photo']);
      }
    }
    $photo_name = null;
  }

  if (!empty($title)) {
    $stmt = $conn->prepare("UPDATE todos SET title = ?, description = ?, category_id = ?, photo = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("sssiii", $title, $description, $category_id, $photo_name, $todo_id, $user_id);
    
    if ($stmt->execute()) {
      // Redirect based on category
      if ($category_id == 1) {
        header("Location: personal.php");
      } elseif ($category_id == 2) {
        header("Location: work.php");
      } else {
        header("Location: act.php");
      }
      exit;
    }
  }
}

// Fetch categories
$categories_result = $conn->query("SELECT * FROM categories ORDER BY id ASC");
$categories = [];
if ($categories_result) {
  $categories = $categories_result->fetch_all(MYSQLI_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit To Do - Sprinklist</title>

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
    }

    .avatar-img {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      object-fit: cover;
    }

    .profile-link {
      text-decoration: none;
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
    .sidebar::-webkit-scrollbar-track {
      background-color: #e7c9b3;
    }

    /* Card Tambah Style (sama dengan tambah.php) */
    .card-tambah {
      max-width: 560px;
      margin: 0 auto;
      background: #fff;
      border-radius: 20px;
      box-shadow: 0 10px 30px rgba(122,78,47,0.15);
      overflow: hidden;
    }

    .card-header-tambah {
      background-color: #7a4e2f;
      padding: 20px;
      text-align: center;
      font-weight: 700;
      font-size: 1.4rem;
      color: #fff;
    }

    .form-label {
      font-weight: 600;
      color: #333;
      margin-bottom: 8px;
    }

    .form-control, .form-select {
      border: 1.5px solid #ddd;
      border-radius: 10px;
      padding: 12px 15px;
      transition: 0.3s;
    }

    .form-control:focus, .form-select:focus {
      border-color: #7a4e2f;
      box-shadow: 0 0 0 0.2rem rgba(122,78,47,0.25);
    }

    textarea.form-control {
      resize: vertical;
    }

    .btn-kembali {
      background-color: #ddd;
      color: #6c757d;
      border: none;
      border-radius: 12px;
      padding: 10px 30px;
      font-weight: 600;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 8px;
      transition: 0.3s;
    }

    .btn-kembali:hover {
      background-color: #ccc;
      color: #5a6268;
    }

    .btn-simpan {
      background-color: #7a4e2f;
      color: #fff;
      border: none;
      border-radius: 12px;
      padding: 10px 40px;
      font-weight: 600;
      transition: 0.3s;
    }

    .btn-simpan:hover {
      background-color: #5c3a21;
    }

    .required {
      color: #E24A4A;
    }

    /* Upload Photo Styles */
    .upload-photo-wrapper {
      border: 2px dashed #ddd;
      border-radius: 10px;
      padding: 20px;
      text-align: center;
      transition: 0.3s;
      cursor: pointer;
      background-color: #fafafa;
    }

    .upload-photo-wrapper:hover {
      border-color: #7a4e2f;
      background-color: #fff7f5;
    }

    .upload-photo-wrapper.drag-over {
      border-color: #7a4e2f;
      background-color: #fff7f5;
    }

    .upload-icon {
      font-size: 3rem;
      color: #7a4e2f;
      margin-bottom: 10px;
    }

    .upload-text {
      color: #666;
      font-size: 14px;
      margin-bottom: 5px;
    }

    .upload-info {
      color: #999;
      font-size: 12px;
    }

    #photoInput {
      display: none;
    }

    .preview-container {
      display: none;
      margin-top: 15px;
      position: relative;
    }

    .preview-image {
      width: 100%;
      max-height: 250px;
      object-fit: cover;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }

    .remove-photo-btn {
      position: absolute;
      top: 10px;
      right: 10px;
      background-color: rgba(226, 74, 74, 0.9);
      color: white;
      border: none;
      border-radius: 50%;
      width: 35px;
      height: 35px;
      display: flex;
      align-items: center;
      justify-content: center;
      cursor: pointer;
      transition: 0.3s;
      font-size: 18px;
    }

    .remove-photo-btn:hover {
      background-color: rgba(226, 74, 74, 1);
      transform: scale(1.1);
    }

    .photo-filename {
      margin-top: 10px;
      color: #666;
      font-size: 13px;
      font-weight: 500;
    }

    .existing-photo-note {
      margin-top: 8px;
      color: #666;
      font-size: 13px;
      font-style: italic;
    }
  </style>
</head>
<body>

<div class="sidebar">
  <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>

  <a href="pages/dashboard.php" class="menu-link" data-target="dashboard">
    <i class="fa-solid fa-gauge-high"></i> Dashboard
  </a>

  <a href="javascript:void(0)" class="menu-link active" data-target="todo">
    <i class="fa-solid fa-list-check"></i> To Do List
  </a>
  <div class="submenu active-menu" id="todo-submenu">
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
    <a href="javascript:void(0)" class="menu-link" data-target="master">
      <i class="fa-solid fa-gear"></i> Master
    </a>
    <div class="submenu" id="master-submenu">
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
  <div class="card card-tambah">
    <div class="card-header card-header-tambah">EDIT TO DO</div>
    <div class="card-body p-4">
      <form method="POST" enctype="multipart/form-data">

        <div class="mb-3">
          <label class="form-label">Judul <span class="required">*</span></label>
          <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($todo['title']) ?>" placeholder="Masukkan judul..." required>
        </div>

        <div class="mb-3">
          <label class="form-label">Kategori <span class="required">*</span></label>
          <select class="form-select" name="category_id" required>
            <?php foreach ($categories as $cat): ?>
              <option value="<?= $cat['id'] ?>" <?= $todo['category_id'] == $cat['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($cat['name']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="mb-3">
          <label class="form-label">Foto</label>
          
          <?php if (!empty($todo['photo'])): ?>
            <div class="existing-photo-note">
              <i class="fa-solid fa-image"></i> Foto saat ini: <?= htmlspecialchars($todo['photo']) ?>
            </div>
          <?php endif; ?>

          <div class="upload-photo-wrapper" id="uploadPhotoWrapper" style="<?= !empty($todo['photo']) ? '' : '' ?>">
            <i class="fa-solid fa-cloud-arrow-up upload-icon"></i>
            <div class="upload-text">Klik atau drag foto ke sini</div>
            <div class="upload-info">Format: JPG, JPEG, PNG, GIF (Max: 5MB)</div>
            <?php if (!empty($todo['photo'])): ?>
              <div class="upload-info mt-2" style="color: #7a4e2f;">
                <i class="fa-solid fa-info-circle"></i> Upload foto baru untuk mengganti
              </div>
            <?php endif; ?>
          </div>
          
          <input type="file" name="photo" id="photoInput" accept="image/jpeg,image/jpg,image/png,image/gif">
          <input type="hidden" name="remove_photo" id="removePhotoFlag" value="0">
          
          <div class="preview-container" id="previewContainer" style="<?= !empty($todo['photo']) ? 'display: block;' : '' ?>">
            <img src="<?= !empty($todo['photo']) ? 'uploads/todos/' . htmlspecialchars($todo['photo']) : '' ?>" 
                 alt="Preview" class="preview-image" id="previewImage">
            <button type="button" class="remove-photo-btn" id="removePhotoBtn">
              <i class="fa-solid fa-xmark"></i>
            </button>
            <div class="photo-filename" id="photoFilename"><?= !empty($todo['photo']) ? htmlspecialchars($todo['photo']) : '' ?></div>
          </div>
        </div>

        <div class="mb-4">
          <label class="form-label">Deskripsi</label>
          <textarea class="form-control" name="description" rows="6" placeholder="Masukkan deskripsi..."><?= htmlspecialchars($todo['description']) ?></textarea>
        </div>

        <div class="d-flex justify-content-between">
          <?php
          // Determine back URL based on category
          $back_url = 'todo/personal.php';
          if ($todo['category_id'] == 2) {
            $back_url = 'todo/work.php';
          } elseif ($todo['category_id'] == 3) {
            $back_url = 'todo/act.php';
          }
          ?>
          <a href="<?= $back_url ?>" class="btn-kembali">
            <i class="fa-solid fa-arrow-left"></i> Batal
          </a>
          <button type="submit" name="update_todo" class="btn-simpan">
            <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
          </button>
        </div>

      </form>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
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

    // Photo Upload Features
    const uploadWrapper = document.getElementById('uploadPhotoWrapper');
    const photoInput = document.getElementById('photoInput');
    const previewContainer = document.getElementById('previewContainer');
    const previewImage = document.getElementById('previewImage');
    const removePhotoBtn = document.getElementById('removePhotoBtn');
    const photoFilename = document.getElementById('photoFilename');
    const removePhotoFlag = document.getElementById('removePhotoFlag');

    // Click to upload
    uploadWrapper.addEventListener('click', function() {
      photoInput.click();
    });

    // Handle file input change
    photoInput.addEventListener('change', function(e) {
      const file = e.target.files[0];
      if (file) {
        handleFileUpload(file);
      }
    });

    // Drag and drop
    uploadWrapper.addEventListener('dragover', function(e) {
      e.preventDefault();
      uploadWrapper.classList.add('drag-over');
    });

    uploadWrapper.addEventListener('dragleave', function(e) {
      e.preventDefault();
      uploadWrapper.classList.remove('drag-over');
    });

    uploadWrapper.addEventListener('drop', function(e) {
      e.preventDefault();
      uploadWrapper.classList.remove('drag-over');
      
      const file = e.dataTransfer.files[0];
      if (file && file.type.startsWith('image/')) {
        photoInput.files = e.dataTransfer.files;
        handleFileUpload(file);
      } else {
        alert('Harap upload file gambar (JPG, PNG, GIF)');
      }
    });

    // Remove photo
    removePhotoBtn.addEventListener('click', function() {
      if (confirm('Apakah Anda yakin ingin menghapus foto ini?')) {
        photoInput.value = '';
        removePhotoFlag.value = '1';
        previewContainer.style.display = 'none';
        uploadWrapper.style.display = 'block';
        photoFilename.textContent = '';
      }
    });

    // Handle file upload
    function handleFileUpload(file) {
      const maxSize = 5 * 1024 * 1024; // 5MB
      const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];

      if (!allowedTypes.includes(file.type)) {
        alert('Format file tidak didukung. Gunakan JPG, PNG, atau GIF');
        return;
      }

      if (file.size > maxSize) {
        alert('Ukuran file terlalu besar. Maksimal 5MB');
        return;
      }

      const reader = new FileReader();
      reader.onload = function(e) {
        previewImage.src = e.target.result;
        photoFilename.textContent = file.name;
        removePhotoFlag.value = '0';
        uploadWrapper.style.display = 'none';
        previewContainer.style.display = 'block';
      };
      reader.readAsDataURL(file);
    }
  });
</script>

</body>
</html>