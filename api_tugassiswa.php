<?php
include 'koneksi.php';

header('Content-Type: application/json');

$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data dari tabel mapel
    $sql = "SELECT deskripsi FROM materi";
    $result = $koneksi->query($sql);

    if ($result === false) {
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $tugassiswaModel = []; 

    if ($result->num_rows > 0) {
        // Jika data ditemukan
        while ($row = $result->fetch_assoc()) {
            $tugassiswaModel[] = $row;
        }
        echo json_encode([
            "status" => "success",
            "tugas_siswa_model" => $tugassiswaModel
        ]);
    } else {
        // Jika tidak ada data
        echo json_encode([
            "status" => "success",
            "tugas_siswa_model" => [] 
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