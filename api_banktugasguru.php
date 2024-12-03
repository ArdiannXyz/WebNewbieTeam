<?php
include 'koneksi.php';

header('Content-Type: application/json');

// Membuat koneksi ke database
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query SQL untuk mengambil data nama siswa dan status tugas dengan JOIN
    $sql = "SELECT siswa.nama, pengumpulan.status
            FROM pengumpulan
            JOIN siswa ON pengumpulan.id_siswa = siswa.id_siswa";

    $result = $koneksi->query($sql);

    // Periksa apakah query berhasil
    if ($result === false) {
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $banktugasModel = [];  // Array untuk menyimpan data tugas

    // Periksa apakah ada data yang ditemukan
    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $banktugasModel[] = $row;
        }
        // Mengirim data dalam format JSON
        echo json_encode([
            "status" => "success",
            "bank_tugas_model" => $banktugasModel
        ]);
    } else {
        // Jika tidak ada data ditemukan
        echo json_encode([
            "status" => "success",
            "bank_tugas_model" => []  // Mengembalikan array kosong jika tidak ada data
        ]);
    }
} else {
    // Jika metode bukan GET
    echo json_encode([
        "status" => "error",
        "message" => "Metode tidak didukung"
    ]);
}

// Tutup koneksi database
$database->tutupKoneksi();
?>

