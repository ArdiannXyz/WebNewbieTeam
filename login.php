<?php
session_start();
require_once 'Auth.php';

$auth = new Auth();

// Logout
if (isset($_GET['logout'])) {
    $auth->logout();
    header("Location: login.php");
    exit();
}

// Cek apakah pengguna sudah login
if ($auth->isLoggedIn()) {
    // Jika sudah login, ambil informasi pengguna
    $user = $auth->getCurrentUser();

    // Redirect berdasarkan role
    switch ($user['role_user']) {
        case 'admin':
            header("Location: admin.php");
            exit();
        case 'guru':
            header("Location: guru.php");
            exit();
        case 'siswa':
            header("Location: siswa.php");
            exit();
    }
}

// Login
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama = $_POST['nama'];
    $password = $_POST['password'];

    if ($auth->login($nama, $password)) {
        // Login berhasil, ambil informasi pengguna
        $user = $auth->getCurrentUser();

        // Periksa apakah $user bukan null
        if ($user) {
            // Redirect berdasarkan role
            switch ($user['role_user']) {
                case 'admin':
                    header("Location: admin.php");
                    break;
                case 'guru':
                    header("Location: guru.php");
                    break;
                case 'siswa':
                    header("Location: siswa.php");
                    break;
                default:
                    // Tangani role yang tidak dikenal
                    header("Location: login.php?error=unknown_role");
                    exit();
            }
            exit(); // Tambahkan exit setelah redirect
        } else {
            $error = "Pengguna tidak ditemukan.";
        }
    } else {
        $error = $auth->getLastError();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - SMK Negeri 7 Jember</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap" rel="stylesheet" />
    <script src="js/jquery.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-..." crossorigin="anonymous">
    <link rel="stylesheet" href="css/style-login.css" />
    <!-- SVG untuk ikon warning -->
    <svg xmlns="http://www.w3.org/2000/svg" style="display: none;">
        <symbol id="exclamation-triangle-fill" viewBox="0 0 16 16">
            <path d="M8.982 1.566a1.13 1.13 0 0 0-1.96 0L.165 13.233c-.457.778.091 1.767.98 1.767h13.713c.889 0 1.438-.99.98-1.767L8.982 1.566zM8 5c.535 0 .954.462.9.995l-.35 3.507a.552.552 0 0 1-1.1 0L7.1 5.995A.905.905 0 0 1 8 5zm.002 6a1 1 0 1 1 0 2 1 1 0 0 1 0-2z"/>
        </symbol>
    </svg>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <img src="Foto/smk7 jember.png" alt="Logo SMK Negeri 7 Jember" />
                <h2>SMK Negeri 7 Jember</h2>
            </div>
            <form action="login.php" method="post">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger d-flex align-items-center" role="alert">
                        <svg class="bi flex-shrink-0 me-2" width="24" height="24" role="img" aria-label="Warning:"><use xlink:href="#exclamation-triangle-fill"/></svg>
                        <div><?php echo htmlspecialchars($error); ?></div>
                    </div>
                <?php endif; ?>
                <div class="input-group">
                    <label for="nama-siswa">Nama Siswa</label>
                    <input type="text" id="nama-siswa" name="nama" placeholder="Masukkan nama anda" required />
                </div>
                <div class="input-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="********" required />
                    <span class="show-password" onclick="togglePassword()">Show</span>
                </div>
                <div class="forgot-password">
                    <a href="#">Lupa Nama siswa atau password?</a>
                    <p>Kuki harus diaktifkan pada browser anda</p>
                </div>
                <button type="submit" class="btn_input" id="submit" name="kirim">Masuk</button>
            </form>
        </div>
    </div>

    <footer>
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
</body>
</html>
