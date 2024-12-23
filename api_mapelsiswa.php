<?php
include 'koneksi.php';

header('Content-Type: application/json');

// Membuat koneksi menggunakan kelas Koneksi
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data lengkap dari tabel mapel
    $sql = "SELECT id_mapel, kode_mapel, nama_mapel, deskripsi FROM mapel";
    $result = $koneksi->query($sql);

    if ($result === false) {
        // Jika query gagal
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $mapelModel = [];

    if ($result->num_rows > 0) {
        // Jika data ditemukan
        while ($row = $result->fetch_assoc()) {
            $mapelModel[] = [
                'id_mapel' => $row['id_mapel'],
                'kode_mapel' => $row['kode_mapel'],
                'nama_mapel' => $row['nama_mapel'],
                'deskripsi' => $row['deskripsi']
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "mapel_siswa" => $mapelModel
        ]);
    } else {
        // Jika tidak ada data
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "mapel_siswa" => []
        ]);
    }
} else {
    // Jika metode bukan GET
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method Not Allowed"
    ]);
}

// Tutup koneksi
$database->tutupKoneksi();
?>
