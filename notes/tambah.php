<?php
session_start();
include '../db.php';

// Cek koneksi database
if (!isset($conn) || $conn->connect_error) {
  die("Koneksi database gagal: " . (isset($conn) ? $conn->connect_error : "Variable \$conn tidak ditemukan"));
}

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

$username  = $_SESSION['username'];
$role_name = $_SESSION['role_name'];
$user_id = $_SESSION['user_id'];
$base_url = '/todo-27rplb-b11-ukom';

// ===== PROSES SIMPAN NOTES =====
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $title = trim($_POST['judul'] ?? '');
  $description = trim($_POST['note'] ?? '');
  $kategori = trim($_POST['kategori'] ?? '');
  $type = 'notes'; // Nilai tetap untuk kolom type
  
  $error = '';
  $foto_name = null;

  // Validasi input
  if (empty($title) || empty($description) || empty($kategori)) {
    $error = "Semua field wajib diisi!";
  } elseif (!in_array($kategori, ['personal', 'work', 'activities'])) {
    $error = "Kategori tidak valid!";
  }

  // Mapping kategori ke category_id
  $category_map = [
    'personal' => 1,
    'work' => 2,
    'activities' => 3
  ];
  $category_id = $category_map[$kategori] ?? null;

  // Proses upload foto
  if (!$error && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['foto'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $size = $file['size'];

    if (!in_array($ext, $allowed)) {
      $error = "Format file tidak didukung! Gunakan JPG, PNG, atau GIF.";
    } elseif ($size > 5 * 1024 * 1024) {
      $error = "Ukuran file maksimal 5MB!";
    } else {
      $uploadDir = realpath(__DIR__ . '/../uploads/notes') . DIRECTORY_SEPARATOR;
      
      if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true)) {
          $error = "Gagal membuat folder upload.";
        }
      }

      if (!$error) {
        $foto_name = 'notes_' . time() . '_' . uniqid() . '.' . $ext;
        $upload_path = $uploadDir . $foto_name;

        if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
          $error = "Gagal upload foto. Cek permission folder.";
          $foto_name = null;
        }
      }
    }
  }

  // Simpan ke database
  if (!$error) {
    try {
      $query = "INSERT INTO notes (user_id, title, description, category_id, foto, type, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())";
      $stmt = $conn->prepare($query);
      
      if ($stmt === false) {
        throw new Exception("Prepare failed: " . $conn->error);
      }
      
      $stmt->bind_param("ississ", $user_id, $title, $description, $category_id, $foto_name, $type);
      
      if ($stmt->execute()) {
        $_SESSION['success_message'] = "Notes berhasil ditambahkan!";
        $stmt->close();
        
        // Redirect sesuai kategori
        if ($kategori === 'personal') {
          header("Location: " . $base_url . "/notes/personal.php");
        } elseif ($kategori === 'work') {
          header("Location: " . $base_url . "/notes/work.php");
        } elseif ($kategori === 'activities') {
          header("Location: " . $base_url . "/notes/act.php");
        }
        exit;
      } else {
        throw new Exception("Execute failed: " . $stmt->error);
      }
    } catch (Exception $e) {
      // Hapus foto jika gagal simpan
      if ($foto_name && isset($uploadDir) && file_exists($uploadDir . $foto_name)) {
        unlink($uploadDir . $foto_name);
      }
      $error = "Gagal menyimpan notes: " . $e->getMessage();
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Sprinklist - Tambah Notes</title>

  <base href="<?= $base_url ?>/">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body { font-family: 'Quicksand', sans-serif; background-color: #fff7f5; overflow-x: hidden; }

    /* ==== SIDEBAR & TOPBAR ==== */
    .sidebar { width: 250px; height: 100vh; background-color: #8B5E3C; color: #fff; position: fixed; top: 0; left: 0; padding-top: 25px; overflow-y: auto; box-shadow: 4px 0 10px rgba(0,0,0,0.1); }
    .sidebar h4 { text-align: center; font-weight: 700; margin-bottom: 25px; color: #fff; animation: fadeSlideIn 1s ease forwards; }
    @keyframes fadeSlideIn { 0% { opacity: 0; transform: translateY(-15px) scale(0.9); } 100% { opacity: 1; transform: translateY(0) scale(1); } }
    .sidebar h4 i { margin-right: 8px; color: #f9b6a5; animation: bloom 1.6s ease-in-out forwards; }
    @keyframes bloom { 0% { transform: scale(0) rotate(-45deg); opacity: 0; } 60% { transform: scale(1.2) rotate(10deg); opacity: 1; } 100% { transform: scale(1) rotate(0); } }
    .sidebar a { display: flex; align-items: center; color: #fff; padding: 10px 20px; text-decoration: none; font-weight: 500; transition: all 0.3s ease; border-left: 4px solid transparent; }
    .sidebar a i { width: 25px; text-align: center; margin-right: 10px; }
    .sidebar a:hover, .sidebar a.active { background-color: #A46C4E; border-left: 4px solid #fff; }
    .submenu { background-color: #fbe7e7; margin-left: 0; border-top: 1px solid #f3d1c8; border-bottom: 1px solid #f3d1c8; overflow: hidden; max-height: 0; opacity: 0; transition: max-height 0.4s ease, opacity 0.4s ease; }
    .submenu a { color: #A46C4E; padding: 8px 40px; font-size: 14px; border-left: none; }
    .submenu a:hover { background-color: #f8d7d7; color: #7a4e2f; }
    .submenu.active-menu { max-height: 300px; opacity: 1; }
    .topbar { height: 65px; background-color: #613a1cff; border-bottom: 2px solid #8B5E3C; padding: 0 25px; display: flex; align-items: center; justify-content: flex-end; position: fixed; left: 250px; right: 0; top: 0; z-index: 100; }
    .sprinklist-logo { display: flex; align-items: center; gap: 10px; color: #fff; animation: fadeInLogo 1.2s ease forwards; }
    .logo-icon { font-size: 28px; color: #f9b6a5; animation: bounceGrow 1.5s infinite alternate ease-in-out; }
    .tagline { display: flex; flex-direction: column; line-height: 1.2; }
    .tagline .brand { font-weight: 700; font-size: 18px; letter-spacing: 0.5px; color: #ffe5df; }
    .tagline .motto { font-size: 12px; color: #f9b6a5; opacity: 0.9; font-style: italic; }
    @keyframes fadeInLogo { from { opacity: 0; transform: translateX(-15px); } to { opacity: 1; transform: translateX(0); } }
    @keyframes bounceGrow { 0% { transform: scale(1) translateY(0); } 50% { transform: scale(1.1) translateY(-2px); } 100% { transform: scale(1) translateY(0); } }
    .topbar .username { font-weight: 600; color: #fff; margin-right: 15px; font-size: 16px; }
    .topbar .profile-icon { width: 42px; height: 42px; border-radius: 50%; background-color: #452c2c; display: flex; align-items: center; justify-content: center; color: white; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.15); }
    .content { margin-left: 250px; margin-top: 80px; padding: 40px; min-height: calc(100vh - 80px); }
    .logout-btn { display: block; background-color: #A46C4E; color: #fff; border-radius: 8px; text-align: center; margin: 25px 20px; padding: 10px 0; text-decoration: none; font-weight: 600; transition: 0.3s; }
    .logout-btn:hover { background-color: #7a4e2f; }
    .sidebar-footer { position: absolute; bottom: 0; width: 100%; }
    .sidebar::-webkit-scrollbar { width: 6px; }
    .sidebar::-webkit-scrollbar-thumb { background-color: #A46C4E; border-radius: 3px; }
    .sidebar::-webkit-scrollbar-track { background-color: #e7c9b3; }

    /* ==== FORM STYLE ==== */
    .card-tambah { max-width: 560px; margin: 0 auto; background: #fff; border-radius: 20px; box-shadow: 0 10px 30px rgba(122,78,47,0.15); overflow: hidden; }
    .card-header-tambah { background-color: #7a4e2f; padding: 20px; text-align: center; font-weight: 700; font-size: 1.4rem; color: #fff; }
    .form-control, .form-select { border: 1.5px solid #ddd; border-radius: 10px; padding: 12px 15px; }
    .form-control:focus, .form-select:focus { border-color: #7a4e2f; box-shadow: 0 0 0 0.2rem rgba(122,78,47,0.25); }
    .upload-area { border: 2px dashed #a67c5b; border-radius: 15px; background-color: #faf2ed; height: 180px; display: flex; flex-direction: column; align-items: center; justify-content: center; cursor: pointer; transition: all 0.3s; overflow: hidden; position: relative; }
    .upload-area:hover, .upload-area.dragover { background-color: #f0e6e0; border-color: #7a4e2f; }
    .upload-area i { font-size: 42px; color: #a67c5b; margin-bottom: 12px; }
    .btn-kembali { background-color: #ddd; color: #6c757d; border: none; border-radius: 12px; padding: 10px 30px; font-weight: 600; }
    .btn-simpan { background-color: #7a4e2f; color: #fff; border: none; border-radius: 12px; padding: 10px 40px; font-weight: 600; }
    .btn-simpan:hover { background-color: #5c3a21; }
    .alert { border-radius: 12px; margin-bottom: 20px; }
  </style>
</head>
<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>
    <a href="pages/dashboard.php" class="menu-link" data-target="dashboard"><i class="fa-solid fa-gauge-high"></i> Dashboard</a>

    <a href="javascript:void(0)" class="menu-link" data-target="todo"><i class="fa-solid fa-list-check"></i> To Do List</a>
    <div class="submenu" id="todo-submenu">
      <a href="todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
      <a href="todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
      <a href="todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
    </div>

    <a href="javascript:void(0)" class="menu-link active" data-target="notes"><i class="fa-solid fa-note-sticky"></i> Notes</a>
    <div class="submenu active-menu" id="notes-submenu">
      <a href="notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
      <a href="notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
      <a href="notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
    </div>

    <?php if (strtolower($role_name) === 'admin'): ?>
      <a href="javascript:void(0)" class="menu-link" data-target="master"><i class="fa-solid fa-gear"></i> Master</a>
      <div class="submenu" id="master-submenu">
        <a href="master/list.php"><i class="fa-solid fa-users-gear"></i> User</a>
      </div>
    <?php endif; ?>

    <div class="sidebar-footer">
      <a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
    </div>
  </div>

  <!-- TOPBAR -->
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
          echo '<img src="' . htmlspecialchars($ava_path) . '" alt="Avatar" style="width:100%;height:100%;object-fit:cover;border-radius:50%;">';
        } else {
          echo '<i class="fa-solid fa-user"></i>';
        }
        ?>
      </div>
    </a>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <?php if (isset($error) && $error): ?>
      <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fa-solid fa-circle-exclamation me-2"></i>
        <strong>Error!</strong> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <div class="card card-tambah">
      <div class="card-header card-header-tambah">TAMBAH NOTES</div>
      <div class="card-body p-4">
        <form id="formTambahNotes" method="POST" enctype="multipart/form-data">
          <div class="mb-3">
            <label class="form-label">Judul :</label>
            <input type="text" class="form-control" name="judul" placeholder="Masukkan judul..." value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Kategori :</label>
            <select class="form-select" name="kategori" id="kategoriSelect" required>
              <option value="" disabled <?= empty($_POST['kategori']) ? 'selected' : '' ?>>Pilih Kategori</option>
              <option value="personal" <?= (isset($_POST['kategori']) && $_POST['kategori'] === 'personal') ? 'selected' : '' ?>>Personal</option>
              <option value="work" <?= (isset($_POST['kategori']) && $_POST['kategori'] === 'work') ? 'selected' : '' ?>>Work</option>
              <option value="activities" <?= (isset($_POST['kategori']) && $_POST['kategori'] === 'activities') ? 'selected' : '' ?>>Activities</option>
            </select>
          </div>

          <div class="mb-3">
            <label class="form-label">Foto (Opsional):</label>
            <div class="upload-area text-center" id="uploadArea">
              <i class="fa-solid fa-camera"></i>
              <p class="mb-0">Klik untuk upload foto<br><small>atau drag & drop file di sini</small></p>
              <input type="file" name="foto" id="foto" accept="image/*" style="display:none;">
            </div>
          </div>

          <div class="mb-4">
            <label class="form-label">Note :</label>
            <textarea class="form-control" name="note" rows="5" placeholder="Masukkan notes..." required><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
          </div>

          <div class="d-flex justify-content-between">
            <a href="notes/personal.php" id="btnKembali" class="btn btn-kembali">
              <i class="fa-solid fa-arrow-left me-2"></i>Kembali
            </a>
            <button type="submit" class="btn btn-simpan">
              <i class="fa-solid fa-save me-2"></i>Simpan
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function () {
      // Sidebar aktif
      const currentPath = window.location.pathname;
      const menuLinks = document.querySelectorAll('.menu-link');
      let activeTarget = null;
      if (currentPath.includes('/notes/')) activeTarget = 'notes';
      menuLinks.forEach(link => {
        link.classList.remove('active');
        if (link.dataset.target === activeTarget) link.classList.add('active');
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

      // === LOGIKA KATEGORI DINAMIS ===
      const kategoriSelect = document.getElementById('kategoriSelect');
      const btnKembali     = document.getElementById('btnKembali');

      // Update tombol Kembali berdasarkan kategori
      kategoriSelect.addEventListener('change', function () {
        const kat = this.value;
        if (kat === 'personal') btnKembali.href = 'notes/personal.php';
        else if (kat === 'work') btnKembali.href = 'notes/work.php';
        else if (kat === 'activities') btnKembali.href = 'notes/act.php';
      });

      // Set default href berdasarkan kategori terpilih saat load
      if (kategoriSelect.value) {
        kategoriSelect.dispatchEvent(new Event('change'));
      }

      // Preview gambar
      const uploadArea = document.getElementById('uploadArea');
      const fileInput  = document.getElementById('foto');
      uploadArea.addEventListener('click', () => fileInput.click());

      ['dragover', 'dragenter'].forEach(e => uploadArea.addEventListener(e, ev => { ev.preventDefault(); uploadArea.classList.add('dragover'); }));
      ['dragleave', 'dragend', 'drop'].forEach(e => uploadArea.addEventListener(e, ev => { ev.preventDefault(); uploadArea.classList.remove('dragover'); }));

      uploadArea.addEventListener('drop', e => {
        if (e.dataTransfer.files.length) {
          fileInput.files = e.dataTransfer.files;
          handleFiles(e.dataTransfer.files);
        }
      });
      fileInput.addEventListener('change', () => { if (fileInput.files.length) handleFiles(fileInput.files); });

      function handleFiles(files) {
        const file = files[0];
        if (file && file.type.startsWith('image/')) {
          const reader = new FileReader();
          reader.onload = function (e) {
            uploadArea.innerHTML = `
              <div style="position:relative;width:100%;height:100%;border-radius:15px;overflow:hidden;">
                <img src="${e.target.result}" style="width:100%;height:100%;object-fit:cover;">
                <div style="position:absolute;bottom:0;left:0;right:0;background:rgba(122,78,47,0.9);color:white;padding:8px 12px;text-align:center;font-size:13px;">
                  ${file.name.length > 30 ? file.name.substring(0,27)+'...' : file.name}<br>
                  <span style="cursor:pointer;text-decoration:underline;font-weight:600;" onclick="removeImage()">Ganti foto</span>
                </div>
              </div>`;
          };
          reader.readAsDataURL(file);
        } else {
          alert('Harap pilih file gambar!');
          removeImage();
        }
      }

      window.removeImage = function () {
        fileInput.value = '';
        uploadArea.innerHTML = `<i class="fa-solid fa-camera"></i><p class="mb-0">Klik untuk upload foto<br><small>atau drag & drop file di sini</small></p>`;
      };
    });
  </script>
</body>
</html>