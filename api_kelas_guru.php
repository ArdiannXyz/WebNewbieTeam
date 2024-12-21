<?php
include 'koneksi.php';
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Membuat koneksi menggunakan kelas Koneksi
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa koneksi
if (!$koneksi) {
    http_response_code(500);
    echo json_encode([
        "status" => "error",
        "message" => "Koneksi database gagal: " . mysqli_connect_error()
    ]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data kelas dengan informasi wali kelas
    $sql = "SELECT 
                k.id_kelas,
                k.nama_kelas,
                k.tahun_ajaran,
                k.wali_kelas,
                k.created_at,
                k.updated_at,
                u.nama as nama_wali_kelas,
                u.email as email_wali_kelas,
                dg.nip as nip_wali_kelas
            FROM kelas k
            LEFT JOIN users u ON k.wali_kelas = u.id
            LEFT JOIN detail_guru dg ON u.id = dg.user_id
            ORDER BY k.tahun_ajaran DESC, k.nama_kelas ASC";
    
    $result = $koneksi->query($sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error pada query: " . $koneksi->error
        ]);
        exit;
    }

    $kelasData = array();

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Menyusun data wali kelas
            $waliKelas = null;
            if ($row['wali_kelas']) {
                $waliKelas = [
                    'id' => $row['wali_kelas'],
                    'nama' => $row['nama_wali_kelas'],
                    'email' => $row['email_wali_kelas'],
                    'nip' => $row['nip_wali_kelas']
                ];
            }

            // Menyusun data kelas
            $kelasData[] = [
                'id_kelas' => $row['id_kelas'],
                'nama_kelas' => $row['nama_kelas'],
                'tahun_ajaran' => $row['tahun_ajaran'],
                'wali_kelas' => $waliKelas,
                'created_at' => $row['created_at'],
                'updated_at' => $row['updated_at']
            ];
        }

        echo json_encode([
            "status" => "success",
            "message" => "Data kelas berhasil diambil",
            "total_data" => $result->num_rows,
            "data" => $kelasData
        ]);
    } else {
        echo json_encode([
            "status" => "success",
            "message" => "Tidak ada data kelas",
            "total_data" => 0,
            "data" => []
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Metode HTTP tidak diizinkan"
    ]);
}

// Menutup koneksi
$database->tutupKoneksi();
?>

