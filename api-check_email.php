<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

$koneksi = new Koneksi();
$conn = $koneksi->getKoneksi();

$email = $_POST['email'] ?? '';

if ($email === '') {
    echo json_encode(["success" => false, "message" => "Email is required"]);
    exit();
}

// Query untuk cek email
$query = $conn->prepare("SELECT email FROM users WHERE email = ?");
$query->bind_param("s", $email);
$query->execute();
$result = $query->get_result();

if ($result->num_rows > 0) {
    echo json_encode(["success" => true, "message" => "Email found"]);
} else {
    echo json_encode(["success" => false, "message" => "Email not found"]);
}

$query->close();
$koneksi->tutupKoneksi();
?>
