<?php
include 'koneksi.php';

header('Content-Type: application/json');

// Membuat koneksi menggunakan kelas Koneksi
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data dari tabel mapel
    $sql = "SELECT nama_mapel FROM mapel";
    $result = $koneksi->query($sql);

    if ($result === false) {
        // Jika query gagal
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $mapelModel = []; // Ganti nama variabel dari "data" ke "materiModel"

    if ($result->num_rows > 0) {
        // Jika data ditemukan
        while ($row = $result->fetch_assoc()) {
            $mapelModel[] = $row;
        }
        echo json_encode([
            "status" => "success",
            "mapel_model" => $mapelModel // Ganti "data" ke "materi_model"
        ]);
    } else {
        // Jika tidak ada data
        echo json_encode([
            "status" => "success",
            "mapel_model" => [] // Ganti "data" ke "materi_model"
        ]);
    }
} else {
    // Jika metode bukan GET
    echo json_encode([
        "status" => "error",
        "message" => "Metode tidak didukung"
    ]);
}

// Tutup koneksi
$database->tutupKoneksi();
?>
