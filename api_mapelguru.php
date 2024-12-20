<?php
include 'koneksi.php';

header('Content-Type: application/json');

// Membuat koneksi menggunakan kelas Koneksi
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data dari tabel mapel
    $sql = "SELECT id_mapel, kode_mapel, nama_mapel, deskripsi, created_at, updated_at 
            FROM mapel 
            ORDER BY nama_mapel ASC";
    
    $result = $koneksi->query($sql);

    if ($result === false) {
        // Jika query gagal
        echo json_encode([
            "status" => "error",
            "success" => false,
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $mapelData = array();

    if ($result->num_rows > 0) {
        // Jika data ditemukan
        while ($row = $result->fetch_assoc()) {
            $mapelData[] = [
                'id_mapel' => $row['id_mapel'],
                'kode_mapel' => $row['kode_mapel'],
                'nama_mapel' => $row['nama_mapel'],
                'deskripsi' => $row['deskripsi'],
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }
        
        echo json_encode([
            "status" => "success",
            "success" => true,
            "message" => "Data mata pelajaran berhasil diambil",
            "mapel_model" => $mapelData  // Menggunakan mapel_model sesuai dengan ApiResponse.java
        ]);
    } else {
        // Jika tidak ada data
        echo json_encode([
            "status" => "success",
            "success" => true,
            "message" => "Tidak ada data mata pelajaran",
            "mapel_model" => []
        ]);
    }
} else {
    // Jika metode bukan GET
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "success" => false,
        "message" => "Metode HTTP tidak diizinkan",
        "mapel_model" => null
    ]);
}

// Tutup koneksi
$database->tutupKoneksi();
?>
