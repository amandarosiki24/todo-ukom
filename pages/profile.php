<?php
session_start();
require_once '../db.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../index.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$success = '';
$error = '';

$uploadDir = '../uploads/avatars/';
if (!file_exists($uploadDir)) {
    mkdir($uploadDir, 0777, true);
}

$stmt = $conn->prepare("SELECT username, email, password, ava FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $ava = $user['ava'];

    if ($username === '' || $email === '') {
        $error = "Username dan email tidak boleh kosong.";
    } else {
        if (!empty($_FILES['ava']['name'])) {
            $fileTmp  = $_FILES['ava']['tmp_name'];
            $fileName = basename($_FILES['ava']['name']);
            $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];

            if (in_array($ext, $allowed)) {
                $newName = 'ava_' . $user_id . '.' . $ext;
                $targetPath = $uploadDir . $newName;

                if (!empty($user['ava']) && file_exists($uploadDir . $user['ava'])) {
                    unlink($uploadDir . $user['ava']);
                }

                if (move_uploaded_file($fileTmp, $targetPath)) {
                    $ava = $newName;
                } else {
                    $error = "Gagal mengunggah foto profil.";
                }
            } else {
                $error = "Format file tidak didukung (gunakan JPG, PNG, atau GIF).";
            }
        }

        if (empty($error)) {
            if ($password === '') {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, ava = ? WHERE id = ?");
                $stmt->bind_param("sssi", $username, $email, $ava, $user_id);
            } else {
                $stmt = $conn->prepare("UPDATE users SET username = ?, email = ?, password = ?, ava = ? WHERE id = ?");
                $stmt->bind_param("ssssi", $username, $email, $password, $ava, $user_id);
            }

            if ($stmt->execute()) {
                $success = "Profil berhasil diperbarui!";
                $_SESSION['username'] = $username;
                $user['username'] = $username;
                $user['email'] = $email;
                $user['ava'] = $ava;
                if ($password !== '') $user['password'] = $password;
            } else {
                $error = "Terjadi kesalahan saat menyimpan data.";
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Edit Profil - Todo UKOM</title>
    <link href="https://fonts.googleapis.com/css2?family=Quicksand:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box
        }

        body {
            font-family: 'Quicksand', sans-serif;
            background: linear-gradient(135deg, #DF6D99 0%, #C97C5D 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 25px;
        }

        .container {
            background: #fffaf8;
            padding: 35px;
            border-radius: 16px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 460px;
            animation: fadeIn .5s ease;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        h2 {
            text-align: center;
            color: #C97C5D;
            margin-bottom: 20px;
            font-weight: 700;
        }

        .alert {
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 8px;
            font-size: 14px;
        }

        .success {
            background: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #a5d6a7
        }

        .error {
            background: #ffebee;
            color: #b71c1c;
            border: 1px solid #ffcdd2
        }

        .avatar-upload {
            display: flex;
            justify-content: center;
            align-items: center;
            position: relative;
            margin-bottom: 22px;
        }

        .avatar-upload input {
            display: none;
        }

        .avatar-preview {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            border: 4px solid #DF6D99;
            background-color: #f8e9e5;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
            cursor: pointer;
            position: relative;
            transition: all .3s ease;
        }

        .avatar-preview:hover {
            transform: scale(1.05);
            background: #f5d1c7;
        }

        .avatar-preview img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        .overlay {
            position: absolute;
            inset: 0;
            background: rgba(223, 109, 153, 0.45);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            opacity: 0;
            transition: opacity .3s ease;
        }

        .avatar-preview:hover .overlay {
            opacity: 1;
        }

        .form-group {
            margin-bottom: 15px;
        }

        label {
            color: #C97C5D;
            font-weight: 600;
            margin-bottom: 6px;
            display: block;
        }

        input {
            width: 100%;
            padding: 10px;
            border: 1px solid #e3c4b7;
            border-radius: 8px;
            background: #fffaf8;
            font-family: 'Quicksand', sans-serif;
        }

        input:focus {
            outline: none;
            border-color: #DF6D99;
            box-shadow: 0 0 0 3px rgba(223, 109, 153, 0.15);
        }

        button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 8px;
            background: #DF6D99;
            color: white;
            font-weight: 700;
            cursor: pointer;
            transition: all .2s ease;
        }

        button:hover {
            background: #C97C5D;
            transform: translateY(-2px);
        }

        a.back {
            display: inline-block;
            text-align: center;
            width: 100%;
            margin-top: 16px;
            color: #C97C5D;
            text-decoration: none;
            font-weight: 600;
            transition: color .2s ease;
        }

        a.back:hover {
            color: #DF6D99;
        }
    </style>
</head>

<body>
    <div class="container">
        <h2><i class="fas fa-user-edit"></i> Edit Profil</h2>

        <?php if ($success): ?>
            <div class="alert success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="post" enctype="multipart/form-data">
            <div class="avatar-upload">
                <label for="ava" class="avatar-preview" id="avatarBox">
                    <img id="preview"
                        src="<?= !empty($user['ava'])
                                    ? '../uploads/avatars/' . htmlspecialchars($user['ava'])
                                    : 'https://via.placeholder.com/120x120?text=+' ?>"
                        alt="Avatar">
                    <div class="overlay"><i class="fas fa-camera"></i></div>
                </label>
                <input type="file" name="ava" id="ava" accept="image/*" onchange="previewImage(event)">
            </div>

            <div class="form-group">
                <label for="username">Username</label>
                <input id="username" name="username" type="text" value="<?= htmlspecialchars($user['username']) ?>" required>
            </div>

            <div class="form-group">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="<?= htmlspecialchars($user['email']) ?>" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input id="password" name="password" type="password" placeholder="Kosongkan jika tidak ingin mengubah">
            </div>

            <button type="submit"><i class="fas fa-save"></i> Simpan Perubahan</button>
        </form>

        <a href="dashboard.php" class="back"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
    </div>

    <script>
        function previewImage(event) {
            const reader = new FileReader();
            reader.onload = function() {
                const output = document.getElementById('preview');
                output.src = reader.result;
                output.style.display = 'block';
            };
            reader.readAsDataURL(event.target.files[0]);
        }
    </script>
</body>

</html>