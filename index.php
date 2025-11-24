<?php
session_start(); //menyiapkan session untuk simpan data login user
require_once 'db.php'; //include file pada db.php 
//melakukan pengecekan apakah submit menggunakan metode post
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $email    = trim($_POST['email']); //ambil data 
  $password = $_POST['password'];
//query untuk mengambil data pada user
//query untuk mengambil data pada user
//role join pada tabel user untuk mengetahui role user yang sedang login
  $sql = "SELECT u.*, r.name AS role_name
          FROM users u 
          JOIN roles r ON u.role_id = r.id  
          WHERE u.email = ? 
          LIMIT 1";
  //megatur keamanan sql injection
  $stmt = $conn->prepare($sql);
  $stmt->bind_param("s", $email);
  $stmt->execute(); //jalankan query 
  $result = $stmt->get_result(); //mengambil hasil query 
   
  if ($result->num_rows > 0) {
    //ambil data user sebagai array
    $user = $result->fetch_assoc();
    if ($password === $user['password']) { //melakukan pengecekan, apakah password yang di input sesuai dengan data pada database 
      //simpan data user ke session
      $_SESSION['user_id']   = $user['id'];
      $_SESSION['email']     = $user['email'];
      $_SESSION['username']  = $user['username'];
      $_SESSION['role_id']   = $user['role_id'];
      $_SESSION['role_name'] = $user['role_name'];

      header("Location: pages/dashboard.php"); //redirect ke halaman dashboard
      exit;
    } else { //tampilan pesan password salah jika tidak sesuai dengan data pada database
      $error = "Password salah!";
    }
  } else { //tampilan pesan saat email tidak ada pada database
    $error = "Email tidak ditemukan!";
  }
  //tutup prepared statement
  $stmt->close();
}
$conn->close(); //tutup koneksi db
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sprinklist - Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body {
      min-height: 100vh;
      background-color: #fff4f4;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Quicksand', sans-serif;
      margin: 0;
      padding: 20px;
    }

    .register-container {
      display: flex;
      background-color: #ffffff;
      border-radius: 25px;
      box-shadow: 0 6px 24px rgba(0, 0, 0, 0.15);
      max-width: 1000px;
      width: 100%;
      transition: all 0.3s ease;
    }

    .register-form {
      width: 50%;
      padding: 35px;
      background-color: #fff7f5;
    }

    .register-form h2 {
      font-weight: 700;
      color: #8B4513;
      text-align: center;
      margin-bottom: 5px;
    }

    .register-form h3 {
      font-weight: 700;
      color: #DF6D99;
      margin-bottom: 25px;
      font-size: 1.6rem;
      text-align: center;
    }

    .form-control {
      border: 2px solid #E8B7A8;
      border-radius: 12px;
      padding: 10px;
      transition: all 0.3s ease;
    }

    .form-control:focus {
      border-color: #C97C5D;
      box-shadow: 0 0 0 4px rgba(201, 124, 93, 0.25);
      outline: none;
    }

    .btn-register {
      background-color: #DF6D99;
      border: none;
      border-radius: 12px;
      padding: 12px;
      font-weight: 600;
      color: #ffffff;
      transition: all 0.3s ease;
    }

    .btn-register:hover {
      background-color: #C97C5D;
      transform: translateY(-2px);
    }

    .alert {
      border-radius: 12px;
      padding: 10px;
      font-size: 0.9rem;
      background-color: #fff0f3;
      border: 1px solid #E8B7A8;
      color: #C97C5D;
    }

    .activity-side {
      width: 50%;
      background: linear-gradient(160deg, #DF6D99 0%, #C97C5D 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      overflow: visible;
      padding: 25px;
    }

    .activity-grid {
      display: grid;
      grid-template-columns: repeat(3, 1fr);
      gap: 15px;
      padding: 15px;
    }

    .activity-card {
      background-color: #ffffffcc;
      border-radius: 20px;
      padding: 15px;
      text-align: center;
      transition: all 0.4s ease;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      min-height: 110px;
      border: 1px solid #E8B7A8;
    }

    .activity-card i {
      font-size: 3rem;
      color: #DF6D99;
      margin-bottom: 8px;
      transition: all 0.4s ease;
    }

    .activity-card span {
      color: #C97C5D;
      font-size: 0.85rem;
      font-weight: 500;
    }

    .activity-card:hover {
      transform: translateY(-8px) scale(1.05);
      box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
      background-color: #ffffff;
    }

    .activity-card:hover i {
      transform: rotate(-8deg);
    }

    @media (max-width: 768px) {
      .register-container {
        flex-direction: column;
        max-width: 90%;
      }

      .register-form,
      .activity-side {
        width: 100%;
      }

      .activity-grid {
        grid-template-columns: repeat(2, 1fr);
      }
    }

    .text-primary {
      color: #DF6D99 !important;
    }

    .text-primary:hover {
      color: #C97C5D !important;
    }
  </style>
</head>

<body>

  <div class="register-container">
    <div class="register-form">
      <h2>Sprinklist</h2>
      <h3>Login Akun</h3>
      <?php if (!empty($error)): ?>
        <div class="alert alert-danger py-2"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      <form method="POST">
        <div class="mb-3">
          <label class="form-label fw-semibold">Email</label>
          <input type="email" class="form-control" name="email" required placeholder="Masukkan email...">
        </div>
        <div class="mb-3">
          <label class="form-label fw-semibold">Password</label>
          <input type="password" class="form-control" name="password" required placeholder="Masukkan password...">
        </div>
        <button type="submit" class="btn btn-register w-100">Login</button>
        <p class="text-center mt-3 mb-0">
          Belum punya akun? <a href="register.php" class="text-primary fw-semibold">Daftar di sini</a>
        </p>
      </form>
    </div>

    <div class="activity-side">
      <div class="activity-grid">
        <div class="activity-card"><i class="fa-solid fa-dumbbell"></i><span>Latihan Gym</span></div>
        <div class="activity-card"><i class="fa-solid fa-bicycle"></i><span>Bersepeda</span></div>
        <div class="activity-card"><i class="fa-solid fa-book"></i><span>Membaca</span></div>
        <div class="activity-card"><i class="fa-solid fa-pen-nib"></i><span>Menulis</span></div>
        <div class="activity-card"><i class="fa-solid fa-person-running"></i><span>Berlari</span></div>
        <div class="activity-card"><i class="fa-solid fa-mountain"></i><span>Mendaki</span></div>
        <div class="activity-card"><i class="fa-solid fa-futbol"></i><span>Sepak Bola</span></div>
        <div class="activity-card"><i class="fa-solid fa-calendar-check"></i><span>Perencanaan</span></div>
        <div class="activity-card"><i class="fa-solid fa-list-check"></i><span>Manajemen Tugas</span></div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>