<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

$email = $_POST['email'] ?? '';
$new_password = $_POST['new_password'] ?? '';

if (empty($email) || empty($new_password)) {
    echo json_encode(["success" => false, "message" => "Email and new password are required"]);
    exit();
}

$koneksi = new Koneksi();
$conn = $koneksi->getKoneksi();

// Hash password baru
$hashed_password = password_hash($new_password, PASSWORD_BCRYPT);

$query = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
$query->bind_param("ss", $hashed_password, $email);

if ($query->execute() && $query->affected_rows > 0) {
    echo json_encode(["success" => true, "message" => "Password updated successfully"]);
} else {
    echo json_encode(["success" => false, "message" => "Failed to update password or email not found"]);
}

$query->close();
$koneksi->tutupKoneksi();
?>
