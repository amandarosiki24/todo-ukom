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

// === AJAX: Selesaikan To-Do + Catat Waktu ===
if (isset($_POST['action']) && $_POST['action'] === 'complete_todo') {
  $todo_id = (int)$_POST['todo_id'];
  $stmt = $conn->prepare("UPDATE todos SET status = 'complete', selesai_at = NOW() WHERE id = ? AND user_id = ? AND status != 'complete'");
  $stmt->bind_param("ii", $todo_id, $user_id);
  $success = $stmt->execute();
  echo json_encode(['success' => $success]);
  exit;
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard - Sprinklist</title>

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
      box-shadow: 4px 0 10px rgba(0, 0, 0, 0.1);
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
      color: #fff; padding: 10px 20px;
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

    .sidebar a:hover,
    .sidebar a.active {
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
      transition: all 0.4s ease;
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
      max-height: 200px;
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
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .content {
      margin-left: 250px;
      margin-top: 80px;
      padding: 40px;
    }

    #today-date {
      font-size: 18px;
      color: #8B5E3C;
      margin-bottom: 10px;
      font-weight: 600;
    }

    #clock {
      font-size: 55px;
      font-weight: 700;
      color: #A46C4E;
      letter-spacing: 2px;
      text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.15);
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

    .dashboard-card {
      background: #fff;
      border-radius: 16px;
      padding: 24px;
      box-shadow: 0 8px 25px rgba(139, 94, 60, 0.08);
      border: 1px solid #f3d1c8;
      margin-bottom: 24px;
    }

    .text-brown { color: #8B5E3C; }
    .btn-brown {
      background-color: #A46C4E;
      color: white;
      border: none;
      border-radius: 8px;
      font-weight: 600;
      font-size: 0.875rem;
      transition: all 0.3s ease;
    }
    .btn-brown:hover {
      background-color: #7a4e2f;
      transform: translateY(-1px);
    }
    .btn-outline-brown {
      border: 1.5px solid #A46C4E;
      color: #A46C4E;
      font-size: 0.875rem;
      border-radius: 8px;
    }
    .btn-outline-brown:hover {
      background-color: #A46C4E;
      color: white;
    }

    .item-card {
      opacity: 0;
      animation: fadeUp 0.5s ease forwards;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(15px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .hover-lift {
      transition: all 0.3s ease;
    }
    .hover-lift:hover {
      transform: translateY(-4px);
      box-shadow: 0 12px 20px rgba(139, 94, 60, 0.15) !important;
    }

    .empty-icon i {
      animation: float 3s ease-in-out infinite;
    }
    @keyframes float {
      0%, 100% { transform: translateY(0); }
      50% { transform: translateY(-8px); }
    }

    .note-content {
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .progress-bar {
      transition: width 0.6s ease;
    }

    .btn-complete {
      background: none;
      border: none;
      color: #A46C4E;
      font-size: 1.2rem;
      cursor: pointer;
      padding: 4px 8px;
      border-radius: 6px;
      transition: all 0.2s ease;
    }
    .btn-complete:hover {
      background-color: #f3d1c8;
      color: #7a4e2f;
    }
    .btn-complete.completed {
      color: #28a745;
    }

    .waktu-selesai {
      font-size: 0.75rem;
      color: #28a745;
      font-weight: 500;
    }
  </style>
</head>

<body>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <h4><i class="fa-solid fa-seedling"></i> Sprinklist</h4>

    <a href="../pages/dashboard.php" class="menu-link active" data-target="dashboard">
      <i class="fa-solid fa-gauge-high"></i> Dashboard
    </a>

    <a href="#" class="menu-link" data-target="todo">
      <i class="fa-solid fa-list-check"></i> To Do List
    </a>
    <div class="submenu" id="todo-submenu">
      <a href="../todo/personal.php"><i class="fa-solid fa-user"></i> Personal</a>
      <a href="../todo/work.php"><i class="fa-solid fa-briefcase"></i> Work</a>
      <a href="../todo/act.php"><i class="fa-solid fa-calendar-check"></i> Activities</a>
    </div>

    <a href="#" class="menu-link" data-target="notes">
      <i class="fa-solid fa-note-sticky"></i> Notes
    </a>
    <div class="submenu" id="notes-submenu">
      <a href="../notes/personal.php"><i class="fa-solid fa-user-pen"></i> Personal</a>
      <a href="../notes/work.php"><i class="fa-solid fa-file-lines"></i> Work</a>
      <a href="../notes/act.php"><i class="fa-solid fa-calendar-days"></i> Activities</a>
    </div>

    <?php if (strtolower($role_name) === 'admin'): ?>
      <a href="#" class="menu-link" data-target="master">
        <i class="fa-solid fa-gear"></i> Master
      </a>
      <div class="submenu" id="master-submenu">
        <a href="../master/list.php"><i class="fa-solid fa-users-gear"></i> User</a>
      </div>
    <?php endif; ?>

    <div class="sidebar-footer">
      <a href="../logout.php" class="logout-btn">
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
    <div class="profile-icon"><i class="fa-solid fa-user"></i></div>
  </div>

  <!-- CONTENT -->
  <div class="content">
    <div class="text-center mb-5">
      <div id="today-date"></div>
      <div id="clock"></div>
      <h3 class="mt-4">Selamat datang, <?= htmlspecialchars($username); ?> (<?= htmlspecialchars($role_name); ?>)</h3>
    </div>

    <!-- TO-DO LIST HARI INI -->
    <div class="dashboard-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-brown">
          <i class="fa-solid fa-list-check"></i> To-Do List Hari Ini
        </h4>
        <a href="../todo/tambah.php" class="btn btn-sm btn-outline-brown">
          <i class="fa-solid fa-plus"></i> Tambah
        </a>
      </div>

      <?php
      $todo_query = "SELECT *, IFNULL(DATE_FORMAT(selesai_at, '%H:%i'), NULL) as waktu_selesai 
                     FROM todos 
                     WHERE user_id = ? AND DATE(created_at) = CURDATE() 
                     ORDER BY created_at DESC";
      $stmt = $conn->prepare($todo_query);
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $todo_result = $stmt->get_result();
      $total_todo = $todo_result->num_rows;
      $completed_todo = 0;
      $todos = [];
      while ($row = $todo_result->fetch_assoc()) {
        if ($row['status'] === 'complete') $completed_todo++;
        $todos[] = $row;
      }
      $progress_todo = $total_todo > 0 ? ($completed_todo / $total_todo) * 100 : 0;
      ?>

      <div class="progress-container mb-4">
        <div class="d-flex justify-content-between text-brown small mb-1">
          <span><strong id="completed-count"><?= $completed_todo ?></strong> selesai</span>
          <span><strong><?= $total_todo ?></strong> total</span>
        </div>
        <div class="progress" style="height: 10px; border-radius: 10px; background-color: #f3d1c8;">
          <div class="progress-bar" id="progress-bar" role="progressbar" 
               style="width: <?= $progress_todo ?>%; background: linear-gradient(90deg, #A46C4E, #da5e17); border-radius: 10px;"
               aria-valuenow="<?= $progress_todo ?>" aria-valuemin="0" aria-valuemax="100">
          </div>
        </div>
        <small class="text-muted d-block mt-1 text-end" id="progress-text"><?= round($progress_todo) ?>% tercapai</small>
      </div>

      <?php if ($total_todo > 0): ?>
        <div class="row g-3">
          <?php foreach ($todos as $i => $t): ?>
            <div class="col-md-6 col-lg-4 item-card" style="animation-delay: <?= $i * 0.1 ?>s;">
              <div class="card h-100 border-0 shadow-sm hover-lift <?= $t['status'] === 'complete' ? 'border-start border-success border-5' : 'border-start border-warning border-5' ?>">
                <div class="card-body p-3">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="fw-bold text-brown text-truncate" style="max-width: 160px;"><?= htmlspecialchars($t['title']) ?></h6>
                    <button class="btn-complete <?= $t['status'] === 'complete' ? 'completed' : '' ?>" 
                            data-id="<?= $t['id'] ?>" <?= $t['status'] === 'complete' ? 'disabled' : '' ?>>
                      <i class="fa-solid <?= $t['status'] === 'complete' ? 'fa-check-circle' : 'fa-circle' ?>"></i>
                    </button>
                  </div>
                  <?php if (!empty($t['description'])): ?>
                    <p class="text-muted small mb-2 text-truncate"><?= htmlspecialchars($t['description']) ?></p>
                  <?php endif; ?>
                  <div class="d-flex justify-content-between align-items-center">
                    <small class="text-muted"><i class="fa-regular fa-clock"></i> <?= date('H:i', strtotime($t['created_at'])) ?></small>
                    <?php if ($t['status'] === 'complete' && $t['waktu_selesai']): ?>
                      <small class="waktu-selesai"><i class="fa-solid fa-check"></i> <?= $t['waktu_selesai'] ?></small>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($total_todo > 6): ?>
          <div class="text-center mt-3">
            <a href="../todo/personal.php" class="btn btn-sm btn-brown">Lihat Semua (<?= $total_todo ?>)</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="text-center py-5">
          <div class="empty-icon mb-3"><i class="fa-solid fa-seedling fa-3x text-brown opacity-25"></i></div>
          <h5 class="text-brown mb-2">Belum ada tugas hari ini</h5>
          <p class="text-muted small">Mulai hari dengan menambahkan to-do!</p>
          <a href="../todo/tambah.php" class="btn btn-brown btn-sm"><i class="fa-solid fa-plus"></i> Buat To-Do</a>
        </div>
      <?php endif; ?>
    </div>

    <!-- NOTES TERBARU -->
    <div class="dashboard-card">
      <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0 fw-bold text-brown">
          <i class="fa-solid fa-note-sticky"></i> Notes Terbaru
        </h4>
        <a href="../notes/tambah.php" class="btn btn-sm btn-outline-brown">
          <i class="fa-solid fa-plus"></i> Tambah
        </a>
      </div>

      <?php
      $note_query = "SELECT * FROM notes WHERE user_id = ? ORDER BY created_at DESC LIMIT 6";
      $stmt = $conn->prepare($note_query);
      $stmt->bind_param("i", $user_id);
      $stmt->execute();
      $note_result = $stmt->get_result();
      $notes = $note_result->fetch_all(MYSQLI_ASSOC);
      $total_notes = count($notes);
      ?>

      <?php if ($total_notes > 0): ?>
        <div class="row g-3">
          <?php foreach ($notes as $i => $n): ?>
            <div class="col-md-6 col-lg-4 item-card" style="animation-delay: <?= $i * 0.1 ?>s;">
              <div class="card h-100 border-0 shadow-sm hover-lift border-start border-primary border-5">
                <div class="card-body p-3">
                  <h6 class="fw-bold text-brown mb-2 text-truncate"><?= htmlspecialchars($n['title']) ?></h6>
                  <p class="note-content text-muted small mb-2">
                    <?= !empty($n['content']) ? htmlspecialchars($n['content']) : '<em>Tanpa isi</em>' ?>
                  </p>
                  <small class="text-muted d-block">
                    <i class="fa-regular fa-calendar"></i> <?= date('d M H:i', strtotime($n['created_at'])) ?>
                  </small>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <?php if ($total_notes >= 6): ?>
          <div class="text-center mt-3">
            <a href="../notes/personal.php" class="btn btn-sm btn-brown">Lihat Semua Notes</a>
          </div>
        <?php endif; ?>
      <?php else: ?>
        <div class="text-center py-5">
          <div class="empty-icon mb-3"><i class="fa-solid fa-sticky-note fa-3x text-brown opacity-25"></i></div>
          <h5 class="text-brown mb-2">Belum ada catatan</h5>
          <p class="text-muted small">Simpan ide atau pengingatmu!</p>
          <a href="../notes/tambah.php" class="btn btn-brown btn-sm"><i class="fa-solid fa-plus"></i> Buat Note</a>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- SCRIPTS -->
  <script>
    // Sidebar
    document.querySelectorAll('.menu-link').forEach(link => {
      link.addEventListener('click', e => {
        if (link.getAttribute('href') === '#') e.preventDefault();
        document.querySelectorAll('.menu-link').forEach(l => l.classList.remove('active'));
        link.classList.add('active');
        const submenu = document.getElementById(link.dataset.target + '-submenu');
        document.querySelectorAll('.submenu').forEach(sm => sm.classList.remove('active-menu'));
        if (submenu) submenu.classList.toggle('active-menu');
      });
    });

    // Tanggal & Jam
    const today = new Date();
    const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
    document.getElementById('today-date').textContent = today.toLocaleDateString('id-ID', options);

    function updateClock() {
      const now = new Date();
      const h = String(now.getHours()).padStart(2, '0');
      const m = String(now.getMinutes()).padStart(2, '0');
      const s = String(now.getSeconds()).padStart(2, '0');
      document.getElementById('clock').textContent = `${h}:${m}:${s}`;
    }
    setInterval(updateClock, 1000);
    updateClock();

    // === FITUR SELESAIKAN + TAMPILKAN WAKTU ===
    document.querySelectorAll('.btn-complete').forEach(btn => {
      btn.addEventListener('click', function() {
        if (this.disabled) return;

        const todoId = this.dataset.id;
        const card = this.closest('.card');
        const timeEl = card.querySelector('.waktu-selesai');

        fetch('', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'action=complete_todo&todo_id=' + todoId
        })
        .then(res => res.json())
        .then(data => {
          if (data.success) {
            this.innerHTML = '<i class="fa-solid fa-check-circle"></i>';
            this.classList.add('completed');
            this.disabled = true;

            card.classList.remove('border-warning');
            card.classList.add('border-success');

            const now = new Date();
            const time = now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');

            if (!timeEl) {
              const div = document.createElement('div');
              div.className = 'd-flex justify-content-between align-items-center';
              div.innerHTML = `<small class="text-muted"><i class="fa-regular fa-clock"></i> ${card.querySelector('.text-muted').innerText.split(' ')[1]}</small>
                               <small class="waktu-selesai"><i class="fa-solid fa-check"></i> ${time}</small>`;
              card.querySelector('.card-body > div:last-child').replaceWith(div);
            } else {
              timeEl.innerHTML = `<i class="fa-solid fa-check"></i> ${time}`;
            }

            const completed = parseInt(document.getElementById('completed-count').textContent) + 1;
            document.getElementById('completed-count').textContent = completed;
            const total = <?= $total_todo ?>;
            const progress = (completed / total) * 100;
            document.getElementById('progress-bar').style.width = progress + '%';
            document.getElementById('progress-text').textContent = Math.round(progress) + '% tercapai';
          }
        });
      });
    });
  </script>

</body>
</html>