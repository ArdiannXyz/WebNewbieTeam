<?php
include 'koneksi.php';

$db = new Koneksi();
$koneksi = $db->getKoneksi();

$identifier = isset($_POST['identifier']) ? $_POST['identifier'] : '';
$password = isset($_POST['password']) ? $_POST['password'] : '';

if (!empty($identifier) && !empty($password)) {
    // Query dimodifikasi untuk mencari berdasarkan email atau nama
    $stmt = $koneksi->prepare("SELECT id, email, password, role, nama, is_active FROM users WHERE email = ? OR nama = ?");
    if (!$stmt) {
        die(json_encode([
            "success" => false, 
            "message" => "Error prepare statement: " . $koneksi->error
        ]));
    }

    // Bind parameter identifier dua kali karena digunakan untuk email dan nama
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        if (!$user['is_active']) {
            $response = [
                "success" => false, 
                "message" => "Akun tidak aktif"
            ];
        }
        else if (password_verify($password, $user['password'])) {
            $response = [
                "success" => true,
                "message" => "Selamat Datang " . $user['nama'],
                "user_id" => $user['id'],
                "role" => $user['role'],
                "nama" => $user['nama'],
                "email" => $user['email']
            ];
        } else {
            $response = [
                "success" => false, 
                "message" => "Password salah"
            ];
        }
    } else {
        $response = [
            "success" => false, 
            "message" => "Email atau Nama tidak ditemukan"
        ];
    }

    $stmt->close();
} else {
    $response = [
        "success" => false, 
        "message" => "Email/Nama dan password harus diisi"
    ];
}

$db->tutupKoneksi();
echo json_encode($response);
?>

