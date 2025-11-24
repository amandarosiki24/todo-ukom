<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit;
}

$username = $_SESSION['username'];
$role_name = $_SESSION['role_name'];
$user_id = $_SESSION['user_id'];
$base_url = '/todo-27rplb-b11-ukom';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul']);
    $note = trim($_POST['note']);
    $kategori = trim($_POST['kategori']);
    
    $error = '';
    $foto_name = NULL;
    
    if (empty($judul) || empty($note) || empty($kategori)) {
        $error = "Semua field wajib diisi!";
    }
    
    $kategori_map = [
        'personal' => 1,
        'work' => 2,
        'activities' => 3
    ];
    $category_id = $kategori_map[$kategori] ?? 1;
    
    // Handle foto upload
    if (!$error && isset($_FILES['foto']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/notes/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }
        
        $file = $_FILES['foto'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        
        if (!in_array($ext, $allowed)) {
            $error = "Format file tidak didukung! Gunakan JPG, PNG, atau GIF.";
        } elseif ($file['size'] > 5 * 1024 * 1024) {
            $error = "Ukuran file maksimal 5MB!";
        } else {
            $foto_name = 'note_' . $user_id . '_' . time() . '_' . rand(1000, 9999) . '.' . $ext;
            $upload_path = $uploadDir . $foto_name;
            
            if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
                $error = "Gagal upload foto!";
                $foto_name = NULL;
            }
        }
    }
    
    // Insert to database
    if (!$error) {
        if (!$conn) {
            $error = "Koneksi database gagal!";
        } else {
            $stmt = $conn->prepare("INSERT INTO notes (user_id, title, description, category_id, foto, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            
            if ($stmt === false) {
                $error = "Error preparing statement: " . $conn->error;
                if ($foto_name && file_exists($uploadDir . $foto_name)) {
                    unlink($uploadDir . $foto_name);
                }
            } else {
                $stmt->bind_param("issis", $user_id, $judul, $note, $category_id, $foto_name);
                
                if ($stmt->execute()) {
                    $_SESSION['success_message'] = "Notes berhasil ditambahkan!";
                    $redirect_map = [
                        'personal' => 'personal.php',
                        'work' => 'work.php',
                        'activities' => 'act.php'
                    ];
                    $redirect = $redirect_map[$kategori] ?? 'personal.php';
                    
                    $stmt->close();
                    header("Location: $redirect");
                    exit;
                } else {
                    if ($foto_name && file_exists($uploadDir . $foto_name)) {
                        unlink($uploadDir . $foto_name);
                    }
                    $error = "Gagal menyimpan notes: " . $stmt->error;
                }
                
                $stmt->close();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tambah Notes - Sprinklist</title>
    
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
            transition: all 0.3s; 
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
            max-height: 0; 
            opacity: 0; 
            overflow: hidden; 
            transition: all 0.4s; 
        }
        .submenu a { 
            color: #A46C4E; 
            padding: 8px 40px; 
            font-size: 14px; 
            border-left: none; 
        }
        .submenu a:hover { 
            background-color: #f8d7d7; 
        }
        .submenu.active-menu { 
            max-height: 300px; 
            opacity: 1; 
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
        }
        .sidebar-footer { 
            position: absolute; 
            bottom: 0; 
            width: 100%; 
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
            color: #ffe5df; 
            letter-spacing: 0.5px;
        }
        .tagline .motto { 
            font-size: 12px; 
            color: #f9b6a5; 
            font-style: italic; 
            opacity: 0.9;
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
        }
        
        .card-tambah { 
            max-width: 600px; 
            margin: 0 auto; 
            background: #fff; 
            border-radius: 20px; 
            box-shadow: 0 10px 30px rgba(122,78,47,0.15); 
        }
        .card-header-tambah { 
            background-color: #7a4e2f; 
            padding: 20px; 
            text-align: center; 
            font-weight: 700; 
            font-size: 1.4rem; 
            color: #fff; 
            border-radius: 20px 20px 0 0; 
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
        }
        .form-control:focus, .form-select:focus { 
            border-color: #7a4e2f; 
            box-shadow: 0 0 0 0.2rem rgba(122,78,47,0.25); 
        }
        
        .upload-area { 
            border: 2px dashed #a67c5b; 
            border-radius: 15px; 
            background-color: #faf2ed; 
            min-height: 180px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            justify-content: center; 
            cursor: pointer; 
            transition: all 0.3s; 
            padding: 20px; 
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        .upload-area:hover { 
            background-color: #f0e6e0; 
            border-color: #7a4e2f; 
        }
        .upload-area.has-image {
            padding: 0;
            border: 2px solid #28a745;
            background-color: transparent;
        }
        .upload-area.dragover {
            background-color: #e8ddd5;
            border-color: #7a4e2f;
            border-style: solid;
        }
        .upload-area i.fa-camera { 
            font-size: 42px; 
            color: #a67c5b; 
            margin-bottom: 12px; 
        }
        .upload-area p {
            margin: 0;
            color: #333;
        }
        .upload-area small {
            color: #666;
            line-height: 1.4;
        }
        
        .preview-container {
            position: relative;
            width: 100%;
            height: 100%;
            min-height: 180px;
            border-radius: 15px;
            overflow: hidden;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: #f8f9fa;
        }
        .preview-container img {
            width: 100%;
            height: 100%;
            max-height: 280px;
            object-fit: contain;
            display: block;
        }
        
        .preview-info {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(to top, rgba(122,78,47,0.95), rgba(122,78,47,0.85));
            color: white;
            padding: 12px 15px;
            font-size: 13px;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .preview-info .file-name {
            display: flex;
            align-items: center;
            font-weight: 500;
        }
        .preview-info .file-name i {
            font-size: 14px;
            margin-right: 6px;
        }
        .change-btn {
            cursor: pointer;
            text-decoration: none;
            font-weight: 600;
            font-size: 12px;
            color: #f9b6a5;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: color 0.2s;
            width: fit-content;
        }
        .change-btn:hover {
            color: #ffd4c4;
            text-decoration: underline;
        }
        .change-btn i {
            font-size: 11px;
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
        }
        .btn-simpan:hover { 
            background-color: #5c3a21; 
        }
        .required { 
            color: #E24A4A; 
        }
        .alert { 
            border-radius: 12px; 
            margin-bottom: 20px; 
        }
        
        @media (max-width: 768px) {
            .preview-container {
                min-height: 200px;
            }
            .preview-container img {
                max-height: 240px;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
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
        <div class="card-header card-header-tambah">
            <i class="fa-solid fa-note-sticky me-2"></i>TAMBAH NOTES
        </div>
        <div class="card-body p-4">
            <form method="POST" enctype="multipart/form-data">
                
                <div class="mb-3">
                    <label class="form-label">Judul <span class="required">*</span></label>
                    <input type="text" class="form-control" name="judul" 
                           placeholder="Masukkan judul notes..." 
                           value="<?= htmlspecialchars($_POST['judul'] ?? '') ?>" required>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">Kategori <span class="required">*</span></label>
                    <select class="form-select" name="kategori" id="kategoriSelect" required>
                        <option value="" disabled <?= empty($_POST['kategori']) ? 'selected' : '' ?>>
                            Pilih Kategori
                        </option>
                        <option value="personal" <?= (isset($_POST['kategori']) && $_POST['kategori'] === 'personal') ? 'selected' : '' ?>>
                            Personal
                        </option>
                        <option value="work" <?= (isset($_POST['kategori']) && $_POST['kategori'] === 'work') ? 'selected' : '' ?>>
                            Work
                        </option>
                        <option value="activities" <?= (isset($_POST['kategori']) && $_POST['kategori'] === 'activities') ? 'selected' : '' ?>>
                            Activities
                        </option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-label">
                        Foto <small class="text-muted">(Opsional - Max 5MB)</small>
                    </label>
                    <div class="upload-area" id="uploadArea">
                        <i class="fa-solid fa-camera"></i>
                        <p class="mb-1"><strong>Klik untuk upload foto</strong></p>
                        <small class="text-muted">atau drag & drop file di sini<br>Format: JPG, PNG, GIF</small>
                    </div>
                    <input type="file" name="foto" id="fotoInput" accept="image/*" style="display:none;">
                </div>
                
                <div class="mb-4">
                    <label class="form-label">Note <span class="required">*</span></label>
                    <textarea class="form-control" name="note" rows="6" 
                              placeholder="Tulis catatan Anda di sini..." required><?= htmlspecialchars($_POST['note'] ?? '') ?></textarea>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="button" onclick="window.history.back()" class="btn btn-kembali">
                        <i class="fa-solid fa-arrow-left"></i>Kembali
                    </button>
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
document.addEventListener('DOMContentLoaded', function() {
    const menuLinks = document.querySelectorAll('.menu-link');
    menuLinks.forEach(link => {
        link.addEventListener('click', function(e) {
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
    
    const kategoriSelect = document.getElementById('kategoriSelect');
    
    // Tidak perlu lagi update href tombol kembali karena menggunakan history.back()
    
    const uploadArea = document.getElementById('uploadArea');
    const fotoInput = document.getElementById('fotoInput');
    
    uploadArea.addEventListener('click', function(e) {
        if (!e.target.closest('.change-btn')) {
            fotoInput.click();
        }
    });
    
    ['dragover', 'dragenter'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });
    });
    
    ['dragleave', 'dragend', 'drop'].forEach(eventName => {
        uploadArea.addEventListener(eventName, function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });
    });
    
    uploadArea.addEventListener('drop', function(e) {
        const files = e.dataTransfer.files;
        if (files.length > 0) {
            fotoInput.files = files;
            handleFileSelect(files[0]);
        }
    });
    
    fotoInput.addEventListener('change', function() {
        if (this.files.length > 0) {
            handleFileSelect(this.files[0]);
        }
    });
    
    function handleFileSelect(file) {
        if (!file.type.startsWith('image/')) {
            alert('Harap pilih file gambar (JPG, PNG, atau GIF)');
            fotoInput.value = '';
            return;
        }
        
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file terlalu besar! Maksimal 5MB');
            fotoInput.value = '';
            return;
        }
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const fileName = file.name.length > 35 ? file.name.substring(0, 32) + '...' : file.name;
            const fileSize = file.size > 1024 * 1024 
                ? (file.size / (1024 * 1024)).toFixed(2) + ' MB'
                : (file.size / 1024).toFixed(1) + ' KB';
            
            uploadArea.classList.add('has-image');
            uploadArea.innerHTML = `
                <div class="preview-container">
                    <img src="${e.target.result}" alt="Preview Foto">
                    <div class="preview-info">
                        <div class="file-name">
                            <i class="fa-solid fa-image"></i>
                            <span>${fileName} (${fileSize})</span>
                        </div>
                        <a href="javascript:void(0)" class="change-btn" onclick="removeImage()">
                            <i class="fa-solid fa-sync"></i>
                            <span>Ganti Foto</span>
                        </a>
                    </div>
                </div>
            `;
        };
        reader.readAsDataURL(file);
    }
    
    window.removeImage = function() {
        fotoInput.value = '';
        uploadArea.classList.remove('has-image');
        uploadArea.innerHTML = `
            <i class="fa-solid fa-camera"></i>
            <p class="mb-1"><strong>Klik untuk upload foto</strong></p>
            <small class="text-muted">atau drag & drop file di sini<br>Format: JPG, PNG, GIF</small>
        `;
    };
});
</script>

</body>
</html>