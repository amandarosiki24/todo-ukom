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

// Handle Delete Note
if (isset($_GET['delete'])) {
  $delete_id = intval($_GET['delete']);
  
  // Get foto filename before delete
  $stmt = $conn->prepare("SELECT foto FROM notes WHERE id = ? AND user_id = ? AND category_id = 1");
  $stmt->bind_param("ii", $delete_id, $user_id);
  $stmt->execute();
  $result = $stmt->get_result();
  
  if ($result->num_rows > 0) {
    $note = $result->fetch_assoc();
    
    // Delete from database
    $stmt = $conn->prepare("DELETE FROM notes WHERE id = ? AND user_id = ? AND category_id = 1");
    $stmt->bind_param("ii", $delete_id, $user_id);
    $stmt->execute();
    
    // Delete foto file if exists
    if (!empty($note['foto'])) {
      $foto_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/uploads/notes/' . $note['foto'];
      if (file_exists($foto_path)) {
        unlink($foto_path);
      }
    }
  }

  header("Location: personal.php");
  exit;
}

// Fetch Notes with category_id = 1 (Personal)
$stmt = $conn->prepare("SELECT * FROM notes WHERE user_id = ? AND category_id = 1 ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$notes = $result->fetch_all(MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Notes Personal - Sprinklist</title>

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

    /* Notes Styles */
    .notes-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 30px;
    }

    .notes-title {
      display: flex;
      align-items: center;
      gap: 12px;
      font-size: 28px;
      font-weight: 700;
      color: #8B5E3C;
    }

    .notes-title i {
      color: #A46C4E;
    }

    .btn-add-note {
      background-color: #8B5E3C;
      color: white;
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      transition: 0.3s;
      display: flex;
      align-items: center;
      gap: 8px;
      text-decoration: none;
    }

    .btn-add-note:hover {
      background-color: #A46C4E;
      color: white;
    }

    .notes-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
      gap: 20px;
      margin-top: 20px;
    }

    .note-card {
      background: white;
      border-radius: 12px;
      padding: 0;
      border-left: 4px solid;
      box-shadow: 0 2px 8px rgba(0,0,0,0.08);
      transition: transform 0.3s, box-shadow 0.3s;
      position: relative;
      overflow: hidden;
    }

    .note-card:nth-child(4n+1) {
      border-left-color: #4A90E2;
    }

    .note-card:nth-child(4n+2) {
      border-left-color: #E2A74A;
    }

    .note-card:nth-child(4n+3) {
      border-left-color: #50C878;
    }

    .note-card:nth-child(4n+4) {
      border-left-color: #E24A4A;
    }

    .note-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 4px 16px rgba(0,0,0,0.12);
    }

    .note-title {
      font-size: 18px;
      font-weight: 700;
      color: #333;
      margin-bottom: 8px;
    }

    .note-image {
      width: 100%;
      height: 180px;
      object-fit: cover;
      border-radius: 8px 8px 0 0;
      margin-bottom: 0;
    }

    .note-body {
      padding: 20px;
    }

    .note-body-no-image {
      padding: 20px;
      padding-top: 50px;
    }

    .note-content {
      font-size: 14px;
      color: #666;
      font-style: italic;
      margin-bottom: 12px;
      overflow: hidden;
      text-overflow: ellipsis;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
    }

    .note-date {
      display: flex;
      align-items: center;
      gap: 6px;
      font-size: 13px;
      color: #999;
    }

    .note-actions {
      position: absolute;
      top: 10px;
      right: 10px;
      display: flex;
      gap: 8px;
      z-index: 10;
    }

    .btn-note-action {
      width: 32px;
      height: 32px;
      border-radius: 50%;
      border: none;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 14px;
      cursor: pointer;
      transition: 0.3s;
      text-decoration: none;
      backdrop-filter: blur(10px);
      box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    .btn-edit {
      background-color: rgba(240, 240, 240, 0.9);
      color: #666;
    }

    .btn-edit:hover {
      background-color: #4A90E2;
      color: white;
    }

    .btn-delete {
      background-color: rgba(240, 240, 240, 0.9);
      color: #666;
    }

    .btn-delete:hover {
      background-color: #E24A4A;
      color: white;
    }

    .empty-state {
      text-align: center;
      padding: 60px 20px;
      color: #999;
    }

    .empty-state i {
      font-size: 64px;
      margin-bottom: 20px;
      color: #ddd;
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

  <a href="javascript:void(0)" class="menu-link active" data-target="notes">
    <i class="fa-solid fa-note-sticky"></i> Notes
  </a>
  <div class="submenu active-menu" id="notes-submenu">
    <a href="notes/personal.php" style="background-color: #f8d7d7; color: #7a4e2f;"><i class="fa-solid fa-user-pen"></i> Personal</a>
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
  <div class="notes-header">
    <div class="notes-title">
      <i class="fa-solid fa-note-sticky"></i>
      Notes Personal
    </div>
    <a href="notes/tambah.php?category=3" class="btn-add-note">
      <i class="fa-solid fa-plus"></i> Tambah
    </a>
  </div>

  <?php if (count($notes) > 0): ?>
    <div class="notes-grid">
      <?php foreach ($notes as $note): ?>
        <div class="note-card">
          <div class="note-actions">
            <a href="notes/edit.php?id=<?= $note['id']; ?>" class="btn-note-action btn-edit">
              <i class="fa-solid fa-pen"></i>
            </a>
            <button class="btn-note-action btn-delete" onclick="confirmDelete(<?= $note['id'] ?>)">
              <i class="fa-solid fa-trash"></i>
            </button>
          </div>
          
          <?php if (!empty($note['foto'])): ?>
  <?php 
    $foto_path = $base_url . '/uploads/notes/' . htmlspecialchars($note['foto']);
    $full_foto_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/uploads/notes/' . $note['foto'];
  ?>
  <img src="<?= $foto_path ?>" alt="<?= htmlspecialchars($note['title']) ?>" class="note-image">
<?php endif; ?>
          
          <div class="<?= !empty($note['foto']) ? 'note-body' : 'note-body-no-image' ?>">
            <div class="note-title"><?= htmlspecialchars($note['title']) ?></div>
            <div class="note-content">
              <?= empty($note['description']) ? 'Tanpa isi' : nl2br(htmlspecialchars($note['description'])) ?>
            </div>
            <div class="note-date">
              <i class="fa-regular fa-calendar"></i>
              <?= date('d M H:i', strtotime($note['created_at'])) ?>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="empty-state">
      <i class="fa-solid fa-note-sticky"></i>
      <h4>Belum ada notes</h4>
      <p>Klik tombol "Tambah" untuk membuat note pertama Anda</p>
    </div>
  <?php endif; ?>
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

  function confirmDelete(id) {
    if (confirm('Apakah Anda yakin ingin menghapus note ini?')) {
      window.location.href = 'notes/personal.php?delete=' + id;
    }
  }
</script>

</body>
</html>