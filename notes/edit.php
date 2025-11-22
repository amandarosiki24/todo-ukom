<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../index.php");
  exit;
}

$username  = $_SESSION['username'];
$role_name = $_SESSION['role_name'];
$user_id   = $_SESSION['user_id'];
$base_url  = '/todo-27rplb-b11-ukom';

// CSRF Token
if (empty($_SESSION['csrf_token'])) {
  $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Ambil ID note
if (!isset($_GET['id'])) {
  header("Location: personal.php");
  exit;
}
$note_id = (int)$_GET['id'];

// Fetch note (bisa dari semua type: personal/work/act)
$stmt = $conn->prepare("SELECT * FROM notes WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $note_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
  header("Location: personal.php");
  exit;
}
$note = $result->fetch_assoc();

// Tentukan halaman kembali sesuai category_id dengan FULL PATH
$origin_page = $base_url . '/notes/personal.php';
if ($note['category_id'] == 2) {
  $origin_page = $base_url . '/notes/work.php';
} elseif ($note['category_id'] == 3) {
  $origin_page = $base_url . '/notes/act.php';
}

// Handle update note
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_note'])) {
  if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Invalid request.";
  } else {
    $title   = trim($_POST['title']);
    $content = trim($_POST['content'] ?? '');
    $foto_name = $note['foto']; // default: foto lama

    // Proses upload foto baru
    if (isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
      $upload_dir = '../uploads/notes/';
      if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);

      $file_ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
      $allowed  = ['jpg', 'jpeg', 'png', 'gif'];

      if (in_array($file_ext, $allowed)) {
        // Hapus foto lama jika ada
        if (!empty($note['foto']) && file_exists($upload_dir . $note['foto'])) {
          unlink($upload_dir . $note['foto']);
        }
        $foto_name = uniqid('note_') . '.' . $file_ext;
        move_uploaded_file($_FILES['foto']['tmp_name'], $upload_dir . $foto_name);
      }
    }

    if (!empty($title)) {
      $stmt = $conn->prepare("UPDATE notes SET title = ?, description = ?, foto = ? WHERE id = ? AND user_id = ?");
      $stmt->bind_param("sssii", $title, $content, $foto_name, $note_id, $user_id);

      if ($stmt->execute()) {
        $_SESSION['success'] = "Note berhasil diperbarui!";
        header("Location: " . $origin_page);
        exit;
      } else {
        $_SESSION['error'] = "Gagal menyimpan perubahan.";
      }
    } else {
      $_SESSION['error'] = "Judul wajib diisi.";
    }
  }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Edit Note - Sprinklist</title>
  <base href="<?= $base_url ?>/">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
  <style>
    body{font-family:'Quicksand',sans-serif;background-color:#fff7f5;overflow-x:hidden}
    .sidebar{width:250px;height:100vh;background-color:#8B5E3C;color:#fff;position:fixed;top:0;left:0;padding-top:25px;overflow-y:auto;box-shadow:4px 0 10px rgba(0,0,0,0.1)}
    .sidebar h4{text-align:center;font-weight:700;margin-bottom:25px;color:#fff;animation:fadeSlideIn 1s ease forwards}
    @keyframes fadeSlideIn{0%{opacity:0;transform:translateY(-15px) scale(0.9)}100%{opacity:1;transform:translateY(0) scale(1)}}
    .sidebar h4 i{margin-right:8px;color:#f9b6a5;animation:bloom 1.6s ease-in-out forwards}
    @keyframes bloom{0%{transform:scale(0) rotate(-45deg);opacity:0}60%{transform:scale(1.2) rotate(10deg);opacity:1}100%{transform:scale(1) rotate(0)}}
    .sidebar a{display:flex;align-items:center;color:#fff;padding:10px 20px;text-decoration:none;font-weight:500;transition:all .3s;border-left:4px solid transparent}
    .sidebar a i{width:25px;text-align:center;margin-right:10px}
    .sidebar a:hover,.sidebar a.active{background-color:#A46C4E;border-left:4px solid #fff}
    .submenu{background-color:#fbe7e7;border-top:1px solid #f3d1c8;border-bottom:1px solid #f3d1c8;overflow:hidden;max-height:0;opacity:0;transition:max-height .4s,opacity .4s}
    .submenu a{color:#A46C4E;padding:8px 40px;font-size:14px}
    .submenu a:hover{background-color:#f8d7d7;color:#7a4e2f}
    .submenu.active-menu{max-height:300px;opacity:1}
    .topbar{height:65px;background-color:#613a1cff;border-bottom:2px solid #8B5E3C;padding:0 25px;display:flex;align-items:center;justify-content:flex-end;position:fixed;left:250px;right:0;top:0;z-index:100}
    .sprinklist-logo{display:flex;align-items:center;gap:10px;color:#fff;animation:fadeInLogo 1.2s ease forwards}
    .logo-icon{font-size:28px;color:#f9b6a5;animation:bounceGrow 1.5s infinite alternate ease-in-out}
    .tagline{display:flex;flex-direction:column;line-height:1.2}
    .tagline .brand{font-weight:700;font-size:18px;letter-spacing:.5px;color:#ffe5df}
    .tagline .motto{font-size:12px;color:#f9b6a5;opacity:.9;font-style:italic}
    @keyframes fadeInLogo{from{opacity:0;transform:translateX(-15px)}to{opacity:1;transform:translateX(0)}}
    @keyframes bounceGrow{0%{transform:scale(1) translateY(0)}50%{transform:scale(1.1) translateY(-2px)}100%{transform:scale(1) translateY(0)}}
    .topbar .username{font-weight:600;color:#fff;margin-right:15px;font-size:16px}
    .topbar .profile-icon{width:42px;height:42px;border-radius:50%;background-color:#452c2c;display:flex;align-items:center;justify-content:center;color:white;font-size:18px;box-shadow:0 2px 5px rgba(0,0,0,.15)}
    .avatar-img{width:100%;height:100%;border-radius:50%;object-fit:cover}
    .content{margin-left:250px;margin-top:80px;padding:40px;min-height:calc(100vh - 80px)}
    .logout-btn{display:block;background-color:#A46C4E;color:#fff;border-radius:8px;text-align:center;margin:25px 20px;padding:10px 0;text-decoration:none;font-weight:600;transition:.3s}
    .logout-btn:hover{background-color:#7a4e2f}
    .sidebar-footer{position:absolute;bottom:0;width:100%}
    .sidebar::-webkit-scrollbar{width:6px}
    .sidebar::-webkit-scrollbar-thumb{background-color:#A46C4E;border-radius:3px}
    .sidebar::-webkit-scrollbar-track{background-color:#e7c9b3}

    .form-container{max-width:800px;margin:0 auto;background:white;border-radius:15px;padding:40px;box-shadow:0 4px 16px rgba(0,0,0,.1)}
    .form-header{display:flex;align-items:center;gap:12px;margin-bottom:30px;padding-bottom:20px;border-bottom:2px solid #f0f0f0}
    .form-header i{font-size:32px;color:#8B5E3C}
    .form-header h2{font-size:28px;font-weight:700;color:#8B5E3C;margin:0}
    .form-label{font-weight:600;color:#8B5E3C;margin-bottom:8px}
    .form-control{border:2px solid #e0e0e0;border-radius:8px;padding:12px 15px;font-size:14px;transition:.3s}
    .form-control:focus{border-color:#A46C4E;box-shadow:0 0 0 .2rem rgba(139,94,60,.25)}
    textarea.form-control{min-height:150px;resize:vertical}
    .upload-area{border:2px dashed #d0b8a8;border-radius:10px;padding:40px 20px;text-align:center;background-color:#fef9f6;cursor:pointer;transition:.3s;position:relative}
    .upload-area:hover{border-color:#A46C4E;background-color:#fef4ed}
    .upload-area i{font-size:48px;color:#d0b8a8;margin-bottom:15px;display:block}
    .upload-area p{color:#666;margin:0;font-size:14px}
    .upload-area small{color:#999;font-size:12px;display:block;margin-top:5px}
    .upload-area input[type=file]{display:none}
    .preview-image{max-width:100%;max-height:300px;border-radius:8px;margin-top:15px;display:none}
    .remove-image{position:absolute;top:10px;right:10px;background:#E24A4A;color:white;border:none;border-radius:50%;width:35px;height:35px;cursor:pointer;display:none;font-size:16px;transition:.3s;z-index:10}
    .remove-image:hover{background:#c93434;transform:scale(1.1)}
    .upload-area.has-image .remove-image{display:flex;align-items:center;justify-content:center}
    .upload-area.has-image i.fa-camera,.upload-area.has-image>p,.upload-area.has-image>small{display:none}
    .upload-area.has-image .preview-image{display:block}
    .current-image{margin-top:10px;padding:15px;background:#f8f9fa;border-radius:8px}
    .current-image img{max-width:100%;max-height:200px;border-radius:8px}
    .current-image-label{font-size:13px;color:#666;margin-bottom:10px;display:block}
    .form-actions{display:flex;gap:15px;margin-top:30px;justify-content:flex-end}
    .btn{padding:12px 30px;border-radius:8px;font-weight:600;transition:.3s;border:none;display:flex;align-items:center;gap:8px;text-decoration:none}
    .btn-secondary{background:#6c757d;color:white}
    .btn-secondary:hover{background:#5a6268}
    .btn-primary{background:#8B5E3C;color:white}
    .btn-primary:hover{background:#7a4e2f}
    .required{color:#E24A4A}
    .profile-link{text-decoration:none}
  </style>
</head>
<body>

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
    <a href="notes/personal.php" <?= $note['category_id']==1 ? 'style="background-color:#f8d7d7;color:#7a4e2f;"' : '' ?>><i class="fa-solid fa-user-pen"></i> Personal</a>
    <a href="notes/work.php" <?= $note['category_id']==2 ? 'style="background-color:#f8d7d7;color:#7a4e2f;"' : '' ?>><i class="fa-solid fa-file-lines"></i> Work</a>
    <a href="notes/act.php" <?= $note['category_id']==3 ? 'style="background-color:#f8d7d7;color:#7a4e2f;"' : '' ?>><i class="fa-solid fa-calendar-days"></i> Activities</a>
  </div>

  <?php if (strtolower($role_name) === 'admin'): ?>
    <a href="javascript:void(0)" class="menu-link" data-target="master"><i class="fa-solid fa-gear"></i> Master</a>
    <div class="submenu" id="master-submenu"><a href="master/list.php"><i class="fa-solid fa-users-gear"></i> User</a></div>
  <?php endif; ?>

  <div class="sidebar-footer"><a href="logout.php" class="logout-btn"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></div>
</div>

<div class="topbar">
  <div class="sprinklist-logo d-flex align-items-center me-auto">
    <i class="fa-solid fa-seedling logo-icon"></i>
    <div class="tagline"><span class="brand">Sprinklist</span><span class="motto">Grow your day, one task at a time.</span></div>
  </div>
  <span class="username">Hi, <?= htmlspecialchars($username); ?></span>
  <a href="pages/profile.php" class="profile-link">
    <div class="profile-icon">
      <?php
      $ava_file = $_SESSION['ava'] ?? 'default.png';
      $ava_path = 'uploads/avatars/' . $ava_file;
      $full_path = $_SERVER['DOCUMENT_ROOT'] . $base_url . '/' . $ava_path;
      if (file_exists($full_path) && !empty($ava_file)) {
        echo '<img src="'.htmlspecialchars($ava_path).'" alt="Avatar" class="avatar-img">';
      } else {
        echo '<i class="fa-solid fa-user"></i>';
      }
      ?>
    </div>
  </a>
</div>

<div class="content">
  <?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($_SESSION['success']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>
  <?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
      <?= htmlspecialchars($_SESSION['error']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>

  <div class="form-container">
    <div class="form-header">
      <i class="fa-solid fa-pen-to-square"></i>
      <h2>Edit Note</h2>
    </div>

    <form method="POST" enctype="multipart/form-data">
      <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

      <div class="mb-4">
        <label class="form-label">Judul <span class="required">*</span></label>
        <input type="text" class="form-control" name="title" value="<?= htmlspecialchars($note['title']) ?>" required>
      </div>

      <div class="mb-4">
        <label class="form-label">Foto (Opsional)</label>
        <?php if (!empty($note['foto'])): ?>
          <div class="current-image">
            <span class="current-image-label"><i class="fa-solid fa-image"></i> Foto saat ini:</span>
            <img src="uploads/notes/<?= htmlspecialchars($note['foto']) ?>" alt="Current Photo">
          </div>
          <div style="margin:10px 0;font-size:13px;color:#666;">
            <i class="fa-solid fa-circle-info"></i> Upload foto baru untuk mengganti foto yang ada
          </div>
        <?php endif; ?>

        <div class="upload-area" id="uploadArea" onclick="handleUploadClick(event)">
          <i class="fa-solid fa-camera"></i>
          <p>Klik untuk upload foto baru</p>
          <small>atau drag & drop file di sini (JPG, JPEG, PNG, GIF)</small>
          <input type="file" id="fileInput" name="foto" accept="image/*" onchange="previewImage(event)">
          <img id="imagePreview" class="preview-image">
          <button type="button" class="remove-image" onclick="removeImage(event)"><i class="fa-solid fa-times"></i></button>
        </div>
      </div>

      <div class="mb-4">
        <label class="form-label">Isi Note (Opsional)</label>
        <textarea class="form-control" name="content" placeholder="Tulis isi note di sini..."><?= htmlspecialchars($note['description'] ?? '') ?></textarea>
      </div>

      <div class="form-actions">
        <a href="<?= htmlspecialchars($origin_page) ?>" class="btn btn-secondary"><i class="fa-solid fa-arrow-left"></i> Kembali</a>
        <button type="submit" name="edit_note" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Simpan Perubahan</button>
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

    // Drag & Drop
    const uploadArea = document.getElementById('uploadArea');
    ['dragenter','dragover','dragleave','drop'].forEach(ev => {
      uploadArea.addEventListener(ev, e => {
        e.preventDefault();
        e.stopPropagation();
      });
    });
    
    ['dragenter','dragover'].forEach(ev => {
      uploadArea.addEventListener(ev, () => {
        uploadArea.style.borderColor = '#A46C4E';
        uploadArea.style.backgroundColor = '#fef4ed';
      });
    });
    
    ['dragleave','drop'].forEach(ev => {
      uploadArea.addEventListener(ev, () => {
        uploadArea.style.borderColor = '#d0b8a8';
        uploadArea.style.backgroundColor = '#fef9f6';
      });
    });
    
    uploadArea.addEventListener('drop', e => {
      const files = e.dataTransfer.files;
      if (files.length > 0) {
        document.getElementById('fileInput').files = files;
        previewImage({target: {files}});
      }
    });
  });

  function handleUploadClick(event) {
    // Jangan trigger jika klik tombol remove
    if (event.target.closest('.remove-image')) {
      return;
    }
    document.getElementById('fileInput').click();
  }

  function previewImage(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = e => {
        document.getElementById('imagePreview').src = e.target.result;
        document.getElementById('uploadArea').classList.add('has-image');
      };
      reader.readAsDataURL(file);
    }
  }

  function removeImage(e) {
    e.stopPropagation();
    e.preventDefault();
    document.getElementById('imagePreview').src = '';
    document.getElementById('fileInput').value = '';
    document.getElementById('uploadArea').classList.remove('has-image');
  }
</script>
</body>
</html>