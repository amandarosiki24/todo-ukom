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

  if (!empty($title)) {
    $stmt = $conn->prepare("UPDATE todos SET title = ?, description = ?, category_id = ? WHERE id = ? AND user_id = ?");
    $stmt->bind_param("ssiii", $title, $description, $category_id, $todo_id, $user_id);
    
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

    /* Form Styles */
    .form-card {
      background: white;
      border-radius: 15px;
      padding: 40px;
      box-shadow: 0 4px 16px rgba(0,0,0,0.1);
      max-width: 700px;
      margin: 0 auto;
    }

    .form-header {
      display: flex;
      align-items: center;
      gap: 12px;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #f0f0f0;
    }

    .form-header i {
      font-size: 32px;
      color: #8B5E3C;
    }

    .form-header h2 {
      font-size: 28px;
      font-weight: 700;
      color: #8B5E3C;
      margin: 0;
    }

    .form-label {
      font-weight: 600;
      color: #8B5E3C;
      margin-bottom: 8px;
    }

    .form-control, .form-select {
      border: 2px solid #e0d5d0;
      border-radius: 8px;
      padding: 12px 15px;
      font-size: 15px;
      transition: 0.3s;
    }

    .form-control:focus, .form-select:focus {
      border-color: #A46C4E;
      box-shadow: 0 0 0 0.2rem rgba(139, 94, 60, 0.25);
    }

    .btn-group-form {
      display: flex;
      gap: 12px;
      margin-top: 30px;
    }

    .btn-submit {
      flex: 1;
      background-color: #8B5E3C;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      transition: 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
    }

    .btn-submit:hover {
      background-color: #A46C4E;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(139, 94, 60, 0.3);
    }

    .btn-cancel {
      flex: 1;
      background-color: #6c757d;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 8px;
      font-weight: 600;
      font-size: 16px;
      transition: 0.3s;
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-cancel:hover {
      background-color: #5a6268;
      color: white;
      transform: translateY(-2px);
      box-shadow: 0 4px 12px rgba(108, 117, 125, 0.3);
    }

    .required {
      color: #E24A4A;
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
  <div class="form-card">
    <div class="form-header">
      <i class="fa-solid fa-pen-to-square"></i>
      <h2>Edit To Do</h2>
    </div>

    <form method="POST">
      <div class="mb-4">
        <label class="form-label">Judul <span class="required">*</span></label>
        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($todo['title']) ?>" placeholder="Masukkan judul to-do..." required>
      </div>

      <div class="mb-4">
        <label class="form-label">Kategori <span class="required">*</span></label>
        <select class="form-select" name="category_id" required>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= $cat['id'] ?>" <?= $todo['category_id'] == $cat['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($cat['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="mb-4">
        <label class="form-label">Deskripsi</label>
        <textarea class="form-control" name="description" rows="6" placeholder="Masukkan deskripsi to-do..."><?= htmlspecialchars($todo['description']) ?></textarea>
      </div>

      <div class="btn-group-form">
        <?php
        // Determine back URL based on category
        $back_url = 'todo/personal.php';
        if ($todo['category_id'] == 2) {
          $back_url = 'todo/work.php';
        } elseif ($todo['category_id'] == 3) {
          $back_url = 'todo/act.php';
        }
        ?>
        <a href="<?= $back_url ?>" class="btn-cancel">
          <i class="fa-solid fa-arrow-left"></i> Batal
        </a>
        <button type="submit" name="update_todo" class="btn-submit">
          <i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan
        </button>
      </div>
    </form>
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
  });
</script>

</body>
</html>