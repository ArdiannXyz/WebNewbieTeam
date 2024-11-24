<?php
include 'koneksi.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Membuat koneksi menggunakan kelas Koneksi
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa koneksi
if (!$koneksi) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data kelas
    $sql = "SELECT id_kelas, nama_kelas, tahun_ajaran, wali_kelas FROM kelas";
    $result = $koneksi->query($sql);

    if ($result === false) {
        // Jika terjadi kesalahan pada query
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $data = array();

    if ($result->num_rows > 0) {
        // Looping data hasil query
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        echo json_encode([
            "status" => "success",
            "data" => $data
        ]);
    } else {
        // Jika tidak ada data
        echo json_encode([
            "status" => "error",
            "message" => "Data tidak ditemukan"
        ]);
    }
} else {
    // Jika metode bukan GET
    echo json_encode([
        "status" => "error",
        "message" => "Metode tidak didukung"
    ]);
}

// Menutup koneksi
$koneksi->close();
?>
