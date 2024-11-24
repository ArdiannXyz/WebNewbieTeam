<?php
session_start();

// Koneksi ke database
$host = 'localhost';
$db   = 'e_learning';
$user = 'root'; // Ganti dengan username database Anda
$pass = ''; // Ganti dengan password database Anda

$conn = new mysqli($host, $user, $pass, $db);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

// Mengambil data dari form
$nama = $_POST["nama"];
$password = $_POST["password"];
$confirm_password = $_POST["confirm-password"];
$role = $_POST["role"];
$email = $_POST["email"];

// Validasi input
$errors = [];

if (empty($nama)) {
    $errors[] = "Nama tidak boleh kosong";
}
if (empty($password) || strlen($password) < 6) {
    $errors[] = "Password harus minimal 6 karakter";
}
if ($password !== $confirm_password) {
    $errors[] = "Password dan Ulangi Password tidak cocok";
}
if (empty($role)) {
    $errors[] = "Silakan pilih peran";
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "Email tidak valid";
}

// Jika ada error, tampilkan pesan error
if (!empty($errors)) {
    foreach ($errors as $error) {
        echo "<p style='color:red;'>$error</p>";
    }
} else {
    // Menyiapkan dan mengeksekusi pernyataan
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$stmt = $conn->prepare("INSERT INTO users (nama, password, sebagai, email) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nama, $hashedPassword, $role, $email);


    if ($stmt->execute()) {
        // Redirect ke halaman login setelah pendaftaran berhasil
        header("Location: login.html");
        exit(); // Pastikan untuk keluar setelah redirect
    } else {
        echo "Pendaftaran Gagal: " . $stmt->error;
    }
    $stmt->close();
}

// Menutup koneksi
$conn->close();

?>
