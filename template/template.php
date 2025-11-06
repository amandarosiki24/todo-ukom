<!-- template/template.php -->
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$username  = $_SESSION['username'];
$role_name = $_SESSION['role_name'];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sprinklist</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
      /* gaya sidebar, topbar, content dst (punya kamu) */
      body { font-family:'Quicksand', sans-serif; background-color:#fff7f5; }
      .content { margin-left:250px; margin-top:80px; padding:40px; min-height:calc(100vh - 80px); }
    </style>
</head>
<body>

<div class="sidebar">
  <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>
  <a href="../pages/dashboard.php" class="menu-link"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>
  <a href="../todo/personal.php" class="menu-link"><i class="fa-solid fa-list-check"></i> To Do</a>
  <a href="../notes/personal.php" class="menu-link"><i class="fa-solid fa-note-sticky"></i> Notes</a>
  <a href="../logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
</div>

<div class="topbar">
  <div class="sprinklist-logo"><i class="fa-solid fa-seedling"></i> Sprinklist</div>
  <span class="username">Hi, <?= htmlspecialchars($username); ?></span>
</div>

<div class="content">
    <?php 
    // 🔹 Slot tempat halaman lain nyisipin isi
    if (isset($content)) {
        echo $content;
    }
    ?>
</div>

</body>
</html>
