<?php
session_start();
$host = 'localhost';
$db   = 'e_learning';
$user = 'root'; // Sesuaikan dengan username database Anda
$pass = ''; // Sesuaikan dengan password MySQL Anda

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Cek apakah kedua field telah diisi
    if (isset($_POST['nama']) && isset($_POST['password'])) {
        $nama = $conn->real_escape_string($_POST['nama']);
        $password = $_POST['password']; // Jangan escape password di sini karena akan diverifikasi
        
        // Query untuk cek username
        $sql = "SELECT * FROM users WHERE nama='$nama'";
        $result = $conn->query($sql);

        if ($result->num_rows > 0) {
            // Ambil data user
            $user = $result->fetch_assoc();

            // Verifikasi password menggunakan password_verify()
            if (password_verify($password, $user['password'])) {
                // Simpan nama dan sebagai ke session
                $_SESSION['nama'] = $user['nama'];
                $_SESSION['sebagai'] = $user['sebagai'];
                
                // Redirect berdasarkan "sebagai"
                if ($user['sebagai'] === 'admin') {
                    header("Location: admin.html"); // Halaman untuk admin
                } else if ($user['sebagai'] === 'guru') {
                    header("Location: guru.html"); // Halaman untuk guru
                } else if ($user['sebagai'] === 'siswa') {
                    header("Location: siswa.html"); // Halaman untuk siswa
                } else {
                    header("Location: login.html?error=unknown_sebagai"); // Jika "sebagai" tidak diketahui
                }
                exit();
            } else {
                // Password salah
                header("Location: login.html?error=wrong_credentials");
                exit();
            }
        } else {
            // Nama pengguna tidak ditemukan
            header("Location: login.html?error=wrong_credentials");
            exit();
        }
    } else {
        // Jika field tidak diisi
        header("Location: login.html?error=empty_fields");
        exit();
    }
}
?>
