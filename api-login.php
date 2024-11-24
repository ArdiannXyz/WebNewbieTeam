<?php
// Memuat file koneksi
include 'koneksi.php';

// Membuat objek koneksi
$db = new Koneksi();
$koneksi = $db->getKoneksi();

// Ambil data dari request
$nama = isset($_GET['nama']) ? $_GET['nama'] : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';

if (!empty($nama) && !empty($password)) {
    // Membuat prepared statement
    $stmt = $koneksi->prepare("SELECT * FROM users WHERE nama = ?");
    if (!$stmt) {
        die(json_encode([
            "success" => false,
            "message" => "Error prepare statement: " . $koneksi->error
        ]));
    }

    $stmt->bind_param("s", $nama);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verifikasi password
        if (password_verify($password, $user['password'])) {
            $response = [
                "success" => true,
                "message" => "Selamat Datang",
                "role" => isset($user['role_user']) ? $user['role_user'] : null
            ];
        } else {
            $response = [
                "success" => false,
                "message" => "Password salah",
                "role" => null
            ];
        }
    } else {
        $response = [
            "success" => false,
            "message" => "Pengguna tidak ditemukan",
            "role" => null
        ];
    }

    $stmt->close();
} else {
    $response = [
        "success" => false,
        "message" => "Ada data yang kosong",
        "role" => null
    ];
}

// Tutup koneksi
$db->tutupKoneksi();

// Kirim respons JSON
echo json_encode($response);
