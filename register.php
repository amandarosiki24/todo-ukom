<?php
session_start();
require_once 'db.php';

if (isset($_SESSION['user_id'])) {
  header("Location: pages/dashboard.php");
  exit();
}

$errors = [];
$success = '';

$uploadDir = 'uploads/avatars/';
if (!file_exists($uploadDir)) {
  mkdir($uploadDir, 0777, true);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $username = trim($_POST['username'] ?? '');
  $email    = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $role_id  = 2;
  $ava = null;

  if ($username === '') $errors[] = "Username wajib diisi.";
  if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Email tidak valid.";

  if (empty($errors)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows > 0) {
      $errors[] = "Username atau Email sudah digunakan.";
    }
    $stmt->close();
  }

  // Upload foto profil
  if (empty($errors) && !empty($_FILES['ava']['name'])) {
    $fileTmp  = $_FILES['ava']['tmp_name'];
    $fileName = basename($_FILES['ava']['name']);
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];

    if (in_array($ext, $allowed)) {
      $newName = 'ava_' . time() . '.' . $ext;
      $targetPath = $uploadDir . $newName;

      if (move_uploaded_file($fileTmp, $targetPath)) {
        $ava = $newName;
      } else {
        $errors[] = "Gagal mengunggah foto profil.";
      }
    } else {
      $errors[] = "Format file tidak didukung (gunakan JPG, PNG, atau GIF).";
    }
  }

  if (empty($errors)) {
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id, ava) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("sssis", $username, $email, $password, $role_id, $ava);
    if ($stmt->execute()) {
      $success = "Registrasi berhasil! Silakan <a href='index.php'>login</a>.";
      $_POST = [];
    } else {
      $errors[] = "Gagal menyimpan data. Silakan coba lagi.";
    }
    $stmt->close();
  }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Register - Todo UKOM</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;500;600;700&display=swap" rel="stylesheet">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{
  font-family: 'Quicksand', sans-serif;
  background: linear-gradient(135deg, #DF6D99 0%, #C97C5D 100%);
  min-height:100vh;display:flex;align-items:center;justify-content:center;padding:20px;
  color:#5a3b2e;
}
.card{
  background:#fffaf8;padding:35px;border-radius:16px;
  box-shadow:0 15px 35px rgba(0,0,0,0.1);
  width:100%;max-width:420px;animation:fadeIn 0.5s ease-in-out;
}
@keyframes fadeIn {
  from {opacity:0; transform:translateY(15px);}
  to {opacity:1; transform:translateY(0);}
}
h2{
  text-align:center;margin-bottom:20px;color:#C97C5D;
  font-size:26px;font-weight:700;
}
.form-group{margin-bottom:14px;}
label{
  display:block;margin-bottom:6px;
  color:#C97C5D;font-weight:600;font-size:15px;
}
input{
  width:100%;padding:10px;border:1px solid #e3c4b7;
  border-radius:8px;background:#fffaf8;
  font-family:'Quicksand',sans-serif;font-size:15px;
}
input:focus{
  outline:none;border-color:#DF6D99;
  box-shadow:0 0 0 3px rgba(223,109,153,0.15);
}
button{
  width:100%;padding:12px;border-radius:8px;border:none;
  background:#DF6D99;color:#fff;font-weight:700;
  cursor:pointer;font-family:'Quicksand',sans-serif;font-size:15px;
  transition:all 0.2s ease-in-out;
}
button:hover{background:#C97C5D;transform:translateY(-2px);}
.alert{
  padding:12px;margin:12px 0;border-radius:8px;font-size:14px;line-height:1.4;
}
.error{background:#ffebee;color:#b71c1c;border:1px solid #ffcdd2}
.success{background:#e8f5e9;color:#2e7d32;border:1px solid #a5d6a7}
.login-link{
  text-align:center;margin-top:14px;color:#C97C5D;font-size:14px;
}
.login-link a{
  color:#DF6D99;font-weight:700;text-decoration:none;
  transition:color 0.2s ease;
}
.login-link a:hover{color:#C97C5D;}
.avatar-upload {
  display: flex;
  justify-content: center;
  align-items: center;
  position: relative;
  margin-bottom: 20px;
}
.avatar-upload input {
  display: none;
}
.avatar-preview {
  width: 100px;
  height: 100px;
  border-radius: 50%;
  background-color: #f8e9e5;
  border: 3px solid #DF6D99;
  overflow: hidden;
  position: relative;
  cursor: pointer;
  display: flex;
  justify-content: center;
  align-items: center;
  transition: all .3s ease;
}
.avatar-preview:hover {
  background-color: #f5d1c7;
  transform: scale(1.05);
}
.avatar-preview img {
  width: 100%;
  height: 100%;
  object-fit: cover;
}
.avatar-preview .overlay {
  position: absolute;
  width: 100%;
  height: 100%;
  background: rgba(223,109,153,0.4);
  color: white;
  display: flex;
  justify-content: center;
  align-items: center;
  opacity: 0;
  transition: opacity .3s ease;
}
.avatar-preview:hover .overlay {
  opacity: 1;
}
.avatar-preview .overlay i {
  font-size: 28px;
}
</style>
</head>
<body>
<div class="card">
  <h2><i class="fas fa-user-plus"></i> Daftar Akun Baru</h2>

  <?php if ($success): ?>
    <div class="alert success"><?= $success ?></div>
  <?php endif; ?>

  <?php if (!empty($errors)): ?>
    <div class="alert error">
      <ul style="padding-left:18px;margin:6px 0">
        <?php foreach ($errors as $e): ?>
          <li><?= htmlspecialchars($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="post" action="" enctype="multipart/form-data">
    <div class="avatar-upload">
      <label for="ava" class="avatar-preview" id="avatarBox">
        <img id="preview" src="" alt="" style="display:none;">
        <div class="overlay"><i class="fas fa-camera"></i></div>
      </label>
      <input type="file" name="ava" id="ava" accept="image/*" onchange="previewImage(event)">
    </div>

    <div class="form-group">
      <label for="username">Username</label>
      <input id="username" name="username" type="text" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="email">Email</label>
      <input id="email" name="email" type="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="password">Password</label>
      <input id="password" name="password" type="password" value="<?= htmlspecialchars($_POST['password'] ?? '') ?>">
    </div>

    <button type="submit"><i class="fas fa-check-circle"></i> Daftar Sekarang</button>
  </form>

  <div class="login-link">Sudah punya akun? <a href="index.php">Login di sini</a></div>
</div>

<script>
function previewImage(event) {
  const reader = new FileReader();
  reader.onload = function(){
    const output = document.getElementById('preview');
    output.src = reader.result;
    output.style.display = 'block';
  };
  reader.readAsDataURL(event.target.files[0]);
}
</script>
</body>
</html>
