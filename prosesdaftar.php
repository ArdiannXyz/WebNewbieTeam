<?php
session_start();

// Mengecek apakah data user_info ada di session
if (isset($_SESSION['user_info'])) {
    $user_info = $_SESSION['user_info'];
    unset($_SESSION['user_info']); // Menghapus data setelah ditampilkan
} else {
    // Redirect jika tidak ada data
    header("Location: daftar.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar User</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
    <!-- SVG untuk ikon warning -->
    <svg xmlns="http://www.w3.org/2000/svg" class="d-none">
  <symbol id="check-circle-fill" viewBox="0 0 16 16">
    <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z"/>
  </symbol>
    </svg>
    <link rel="stylesheet" href="css/prosesdaftar.css">
    <script src="js/script.js"></script>
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
    <div class="container">
      <div class="container-proses">
        <h2>Daftar User</h2>
        <p>Proses daftar User</p>
        <div class="alert alert-success d-flex align-items-center" role="alert" style="font-size: 1rem; padding: 5px 10px;">
  <svg class="bi flex-shrink-0 me-2" role="img" aria-label="Success:" style="width: 24px; height: 24px; margin-right: 5px;">
    <use xlink:href="#check-circle-fill"/>
  </svg>
  <div>Selamat proses daftar User berhasil!</div>
</div>
<div class="user-info">
    <div class="info-item"><span>Username</span> : <?php echo htmlspecialchars($user_info['nama']); ?></div>
    <div class="info-item1"><span>Password</span> : <?php echo '********'; ?></div> 
    <div class="info-item"><span>Email</span> : <?php echo htmlspecialchars($user_info['email']); ?></div>
    <div class="info-item1"><span>Level User</span> : <?php echo htmlspecialchars($user_info['role_user']); ?></div>
</div>
        <button class="back-button">Kembali</button>
      </div>
    </div>

    <div class="footer">
        <div class="school-info">
          <img src="Foto/smk7 jember.png" alt="School Emblem" />
          <p>SMK Negeri 7 Jember</p>
        </div>
        <div class="contact-info">
          <p id="footer-contact">Contact</p>
          <p><i class="fas fa-envelope"></i> smkn7jember@gmail.com</p>
          <p><i class="fas fa-globe"></i> https://smkn7jember.sch.id/</p>
          <p><i class="fas fa-phone"></i> +6281-8094-0000</p>
        </div>
      </div>
</body>
</html>

