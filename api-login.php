<?php
include 'koneksi.php';

$db = new Koneksi();
$koneksi = $db->getKoneksi();

$nama = isset($_GET['nama']) ? $_GET['nama'] : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';

if (!empty($nama) && !empty($password)) {
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE nama = ?");
    if (!$stmt) {
        die(json_encode(["success" => false, "message" => "Error prepare statement: " . $koneksi->error]));
    }

    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        if (password_verify($password, $user['password'])) {
            $response = [
                "success" => true,
                "message" => "Selamat Datang",
                "user_id" => $user['id'], // Mengembalikan user_id
                "role" => $user['role_user']
            ];
        } else {
            $response = ["success" => false, "message" => "Password salah"];
        }
    } else {
        $response = ["success" => false, "message" => "Pengguna tidak ditemukan"];
    }

    $stmt->close();
} else {
    $response = ["success" => false, "message" => "Ada data yang kosong"];
}

$db->tutupKoneksi();
echo json_encode($response);
?>
