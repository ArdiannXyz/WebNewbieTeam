
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
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Guru</title>
</head>
<body>
    <h1>ini halaman Guru</h1>
</body>
</html>