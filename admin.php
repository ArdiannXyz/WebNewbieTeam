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

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Learning Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/admin.css">
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
        <a href="daftar.php"><i class="bi bi-person-plus-fill"></i> Daftar Akun</a>
        <a href="profile.php"><i class="bi bi-person-fill"></i> Profile</a>
        <a href="panduan.php"><i class="fas fa-book"></i> Panduan</a>
    </div>
    <div class="content">
        <div class="profile-card">
            <div class="profile-info">
                <i class="fas fa-user-circle profile-icon"></i>
                <div class="profile-text">
                    <div class="name"><?php echo htmlspecialchars($currentUser['nama']); ?></div>
                    <div class="role"><?php echo htmlspecialchars($currentUser['role_user']); ?></div>
                </div>
            </div>
            <button class="profile-button">Profile</button>
        </div>

        <h1 class="stats-akun">Akun yang terdaftar</h1>
        <div class="stats">
            <div class="stat-card">
                <div class="stat-title">Akun Admin</div>
                <div class="stat-value">4</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Akun Siswa</div>
                <div class="stat-value">240</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Akun Guru</div>
                <div class="stat-value">36</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Mapel</div>
                <div class="stat-value">12</div>
            </div>
            <div class="stat-card">
                <div class="stat-title">Kelas</div>
                <div class="stat-value">8</div>
            </div>
        </div>

        <h1 class="stats-akun">Menu admin</h1>
        <div class="admin-menu">
            <div class="menu-title">Daftar Akun</div>
            <div class="menu-description">Menambahkan akun seperti Admin, Siswa, dan Guru untuk memulai pembelajaran</div>
            <a href="daftar.html"><button class="menu-button">Daftar</button></a>
        </div>
        <div class="admin-menu">
            <div class="menu-title">Profile</div>
            <div class="menu-description">Melihat data pribadi serta mengubah tampilan pada data diri</div>
            <a href="profile.html"><button class="menu-button">profile</button></a>
        </div>
        <div class="admin-menu">
            <div class="menu-title">Panduan</div>
            <div class="menu-description">Melihat panduan untuk masing - masing dari user</div>
            <a href="panduan.html"><button class="menu-button">Panduan</button></a>
        </div>
    </div>
    <script src="js/script.js"></script>
</body>
</html>