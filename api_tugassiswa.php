<?php
include 'koneksi.php';

header('Content-Type: application/json');

$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query hanya untuk mengambil judul tugas
    $sql = "SELECT judul_tugas FROM tugas WHERE is_active = 1 ORDER BY deadline ASC";
            
    $result = $koneksi->query($sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $judulTugas = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $judulTugas[] = [
                "judul_tugas" => $row['judul_tugas']
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "judul_tugas" => $judulTugas
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "judul_tugas" => []
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method Not Allowed"
    ]);
}

$database->tutupKoneksi();
?>
