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
?>

<html>
  <head>
    <title>Data Diri</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;400;600;700&display=swap" rel="stylesheet" />
    <link rel="stylesheet" href="css/profile.css">
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
        <a href="panduan.html"><i class="fas fa-book"></i> Panduan</a>
    </div>
    <div class="container">
      <div class="card">
        <h2>Data Diri</h2>
        <div class="profile">
          <div class="left">
            <img alt="Profile picture placeholder" height="100" src="https://storage.googleapis.com/a1aa/image/EQfmn4Xbi2waCCbUEeHlR3z5FdjWdqNHXR3RVeCZ1uCD1CQnA.jpg" width="100" />
            <button>
              <i class="fas fa-camera"> </i>
              Edit Foto
            </button>
            <button>
              <i class="fas fa-edit"> </i>
              Edit Profil
            </button>
          </div>
          <div class="right">
            <div class="info">
              <div>
                <b> Nama </b>
                Ryan Adi Saputra
              </div>
              <div>
                <b> Kelas </b>
                XII MM 1
              </div>
            </div>
            <div class="info">
              <div>
                <b> Kelamin </b>
                Laki-laki
              </div>
              <div>
                <b> Email </b>
                ryannnnsop@gmail.com
              </div>
            </div>
            <div class="info">
              <div>
                <b> No.Hp </b>
                +6281-6664-5555
              </div>
            </div>
            <div class="info">
              <div>
                <b> Alamat </b>
                Jalan jaksel no.12
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  <script src="js/script.js"></script>    
  </body>
</html>
