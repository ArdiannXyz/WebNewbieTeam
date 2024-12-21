<?php
include 'koneksi.php';

header('Content-Type: application/json');

$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data tugas dengan informasi terkait
    $sql = "SELECT 
                t.id_tugas,
                t.judul_tugas,
                t.deskripsi,
                t.file_tugas,
                t.deadline,
                m.nama_mapel,
                k.nama_kelas,
                u.nama as nama_guru,
                (SELECT COUNT(*) FROM pengumpulan p 
                 WHERE p.id_tugas = t.id_tugas 
                 AND p.status != 'belum_mengumpulkan') as jumlah_mengumpulkan
            FROM tugas t
            JOIN mapel m ON t.id_mapel = m.id_mapel
            JOIN kelas k ON t.id_kelas = k.id_kelas
            JOIN users u ON t.id_guru = u.id
            WHERE t.is_active = 1
            ORDER BY t.deadline ASC";
            
    $result = $koneksi->query($sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $tugasModel = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            // Konversi deadline ke format yang lebih readable
            $deadline = new DateTime($row['deadline']);
            
            $tugasModel[] = [
                'id_tugas' => $row['id_tugas'],
                'judul_tugas' => $row['judul_tugas'],
                'deskripsi' => $row['deskripsi'],
                'file_tugas' => $row['file_tugas'],
                'deadline' => $deadline->format('Y-m-d H:i:s'),
                'nama_mapel' => $row['nama_mapel'],
                'nama_kelas' => $row['nama_kelas'],
                'nama_guru' => $row['nama_guru'],
                'jumlah_mengumpulkan' => $row['jumlah_mengumpulkan'],
                'status_deadline' => new DateTime() > $deadline ? 'Telah lewat' : 'Masih berlaku'
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => $tugasModel
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "data" => []
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
