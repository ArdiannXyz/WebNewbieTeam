<?php
include 'koneksi.php';

header('Content-Type: application/json');

$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data dari tabel materi
    $sql = "SELECT id_tugas, judul_tugas, deskripsi FROM materi";
    $result = $koneksi->query($sql);  // Eksekusi query dulu

    if ($result === false) {
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $tugasModel = []; 

    if ($result->num_rows > 0) {
        // Jika data ditemukan
        while ($row = $result->fetch_assoc()) {
            $tugasModel[] = [
                "id_tugas" => $row["id_tugas"],
                "judul_tugas" => $row["judul_tugas"],
                "deskripsi" => $row["deskripsi"]
            ];
        }
        echo json_encode([
            "status" => "success",
            "tugas_model" => $tugasModel
        ]);
    } else {
        // Jika tidak ada data
        echo json_encode([
            "status" => "success",
            "tugas_model" => []
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
<<<<<<< Updated upstream
?>

=======
?>
>>>>>>> Stashed changes
