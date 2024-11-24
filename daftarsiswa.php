<?php
session_start();
require_once 'dbconfig.php';

$db = new Database();
$conn = $db->getConnection();

// Mengecek apakah form telah dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Mengambil data dari form
    $nama = isset($_POST["nama"]) ? trim($_POST["nama"]) : '';
    $password = isset($_POST["password"]) ? $_POST["password"] : '';
    $confirm_password = isset($_POST["confirm-password"]) ? $_POST["confirm-password"] : '';
    $role = isset($_POST["role_user"]) ? $_POST["role_user"] : '';
    $email = isset($_POST["email"]) ? trim($_POST["email"]) : '';
    $nisn = isset($_POST["nisn"]) ? $_POST["nisn"] : '';
    $telp = isset($_POST["telp"]) ? $_POST["telp"] : '';

    // Validasi data
    $errors = [];

    if (empty($nama)) {
        $errors[] = "Nama tidak boleh kosong !";
    }
    if (empty($password) || strlen($password) < 6) {
        $errors[] = "Password harus minimal 6 karakter";
    }
    if ($password !== $confirm_password) {
        $errors[] = "Password dan Ulangi Password tidak cocok";
    }
    if (empty($role)) {
        $errors[] = "Silakan pilih role";
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Email tidak valid atau kosong";
    }
    if (empty($nisn)) {
        $errors[] = "NISN tidak boleh kosong !";
    }
    if (empty($telp)) {
        $errors[] = "Nomor Telepon tidak boleh kosong !";
    }

    // Cek apakah email sudah terdaftar
    if (empty($errors)) {
        $stmt = $conn->prepare("SELECT COUNT(*) FROM users WHERE email = :email");
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->execute();
        
        $emailCount = $stmt->fetchColumn();

        if ($emailCount > 0) {
            // Jika email sudah terdaftar, tambahkan error
            $errors[] = "Email sudah digunakan !";
        }
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header("Location: daftarsiswa.php");
        exit();
    } else {
        // Jika tidak ada error, hash password dan simpan ke database
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Menggunakan prepared statement dengan PDO
        $sql = "INSERT INTO siswa (nama, password, email, role_user, nisn, telp) VALUES (:nama, :password, :email, :role_user, :nisn, :telp)";
        $stmt = $conn->prepare($sql);

        // Bind parameters
        $stmt->bindValue(':nama', $nama, PDO::PARAM_STR);
        $stmt->bindValue(':password', $hashedPassword, PDO::PARAM_STR);
        $stmt->bindValue(':email', $email, PDO::PARAM_STR);
        $stmt->bindValue(':role_user', $role, PDO::PARAM_STR);
        $stmt->bindValue(':nisn', $nisn, PDO::PARAM_STR);
        $stmt->bindValue(':telp', $telp, PDO::PARAM_STR);

        if ($stmt->execute()) {
            $_SESSION['user_info'] = [
                'nama' => $nama,
                'password' => $password,
                'email' => $email,
                'role_user' => $role
            ];
            header("Location: prosesdaftar.php?status=success");
            exit();
        } else {
            $errorInfo = $stmt->errorInfo(); // Mendapatkan informasi error
            $_SESSION['errors'] = ["Error: Gagal menyimpan data. " . $errorInfo[2]];
            header("Location: daftarsiswa.php");
            exit();
        }        
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - SMK Negeri 7 Jember</title>
    <!-- icon, font dll -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;400;600;700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,100;0,200;0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,100;1,200;1,300;1,400;1,500;1,600;1,700;1,800;1,900&display=swap" rel="stylesheet" />
    <!-- boostrap alert -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
    <!-- css, js dll -->
    <link rel="stylesheet" href="css/daftarsiswa.css">
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
        <div class="register-box">
            <h2>Daftar Akun Siswa</h2>
            <?php if (isset($_SESSION['errors']) && !empty($_SESSION['errors'])): ?>
                <div class="alert alert-danger alert-custom" role="alert">
                        <?php 
                            foreach ($_SESSION['errors'] as $error) {
                                echo "<li>$error</li>";
                            }
                            unset($_SESSION['errors']); // Hapus pesan error setelah ditampilkan
                        ?>
                </div>
            <?php endif; ?>
            <form action="daftarsiswa.php" method="POST">
                <div class="input-group">
                    <label for="nama">Nama</label>
                    <input type="text" id="nama" name="nama" placeholder="Masukkan nama anda" required>
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="********" required>
                </div>
                <div class="input-group">
                    <label for="confirm-password">Ulangi password</label>
                    <input type="password" id="confirm-password" name="confirm-password" placeholder="********" required>
                </div>
                <div class="input-group">
                    <label for="role_user">Role User</label>
                    <input type="text" id="role_user" name="role_user" value="siswa" readonly>
                </div>
                <div class="input-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" placeholder="Masukkan email anda" required>
                </div>
                <div class="input-group">
                    <label for="nisn">NISN</label>
                    <input type="number" id="nisn" name="nisn" placeholder="Masukkan NISN anda" required>
                </div>
                <div class="input-group">
                    <label for="telp">No.Telp</label>
                    <input type="number" id="telp" name="telp" placeholder="Masukkan Nomor Telepon anda" required>
                </div>
                <button type="submit" class="btn-submit">Daftar</button>
            </form>
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
    </footer>
    <script src="js/script.js"></script>
</body>
</html>
