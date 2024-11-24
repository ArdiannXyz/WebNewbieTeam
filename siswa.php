<?php
session_start();
require_once "Auth.php";
require_once "dbconfig.php"; 

$user = new Auth();
// Cek status login user
if (!$user->isLoggedIn()) {  
    header("location: login.php"); //Redirect ke halaman login  
    exit; // Tambahkan exit setelah header
}

// Ambil data user saat ini
$currentUser = $user->getCurrentUser();
if (!$currentUser) {
    echo "Error: Gagal mengambil data pengguna.";
    exit;
}

?>

<html>
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>E - Learning</title>
        <!-- Font Awesome for icons -->
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">        
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
        <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />
        <!-- Custom CSS -->
        <link rel="stylesheet" href="css/siswa.css">
    </head>
  <body>
  <div class="header">
    <div class="logo">
        <i class="fas fa-bars menu-icon"></i>
        <img src="Foto/smk7 jember.png" alt="School Logo" />
        <span class="text-elearning">E-Learning</span>
    </div>
    <a href="logout.php" class="logout">Keluar</a>
</div>
    <div class="sidebar">
        <a href="admin.php"><i class="fas fa-home"></i> Dashboard</a>
        <a href="kalender.php"><i class="bi bi-calendar-date"></i> Kalender</a>
        <a href="profile.php"><i class="bi bi-person-fill"></i> Profile</a>
        <a href="panduan.php"><i class="fas fa-book"></i> Panduan</a>
        <a class="dropdown-btn" href="javascript:void(0);" id="dropdown-btn" onclick="toggleDropdown()">
        Kelas
        <i class="fas fa-caret-down"> </i>
      </a>
      <div class="dropdown" id="dropdown">
        <a href="materi_dosen.html"> Matematika </a>
        <a href="#"> Penjaskes </a>
        <a href="#"> B.Jawa </a>
        <a href="#"> B.Inggris </a>
      </div>
    </div>
    <div class="content">
    <div class="profile-card">
            <div class="profile-info">
                <i class="fas fa-user-circle profile-icon"></i>
                <div class="profile-text">
                    <div class="name"><?php echo htmlspecialchars($currentUser['nama']); ?></div>
                    <div class="role"><?php echo htmlspecialchars($currentUser['sebagai']); ?></div>
                </div>
            </div>
            <button class="profile-button">Profile</button>
        </div>
      <div class="grid-container">
        <div class="card">
          <a href="materi."><h3>Matematika</h3></a>
          <ul>
            <li><i class="fas fa-user"></i> Ismail Bin Mail</li>
          </ul>
        </div>
        <div class="card">
          <h3>Penjaskes</h3>
          <ul>
            <li><i class="fas fa-user"></i> Susanti</li>
          </ul>
        </div>
        <div class="card">
          <h3>Tugas</h3>
          <ul>
            <li><i class="fas fa-folder"></i> Tugas 1</li>
            <li><i class="fas fa-folder"></i> Tugas 2</li>
            <li><i class="fas fa-folder"></i> Tugas 3</li>
          </ul>
        </div>
        <div class="card">
          <h3>B.Inggris</h3>
          <ul>
            <li><i class="fas fa-user"></i> Steven hendoyono</li>
          </ul>
        </div>
        <div class="card">
          <h3>B.Jawa</h3>
          <ul>
            <li><i class="fas fa-user"></i> Rosalina Muti</li>
          </ul>
        </div>
      </div>
    </div>
    <script src="js/script.js"></script>
  </body>
</html>
