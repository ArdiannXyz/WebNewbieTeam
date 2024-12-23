<?php
include 'koneksi.php';

header('Content-Type: application/json');

// Membuat koneksi menggunakan kelas Koneksi
$database = new Koneksi();
$koneksi = $database->getKoneksi();

// Periksa metode permintaan
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // Query untuk mendapatkan data materi dengan informasi terkait
    $sql = "SELECT 
                m.id_materi,
                m.judul_materi,
                m.deskripsi,
                m.file_materi,
                mp.nama_mapel,
                k.nama_kelas,
                u.nama as nama_guru
            FROM materi m
            JOIN mapel mp ON m.id_mapel = mp.id_mapel
            JOIN kelas k ON m.id_kelas = k.id_kelas
            JOIN users u ON m.id_guru = u.id
            WHERE m.is_active = 1
            ORDER BY m.created_at DESC";
            
    $result = $koneksi->query($sql);

    if ($result === false) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "SQL Error: " . $koneksi->error
        ]);
        exit;
    }

    $materiModel = [];

    if ($result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $materiModel[] = [
                'id_materi' => $row['id_materi'],
                'judul_materi' => $row['judul_materi'],
                'deskripsi' => $row['deskripsi'],
                'file_materi' => $row['file_materi'],
                'nama_mapel' => $row['nama_mapel'],
                'nama_kelas' => $row['nama_kelas'],
                'nama_guru' => $row['nama_guru']
            ];
        }
        
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "materi_siswa" => $materiModel
        ]);
    } else {
        http_response_code(200);
        echo json_encode([
            "status" => "success",
            "materi_siswa" => []
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        "status" => "error",
        "message" => "Method Not Allowed"
    ]);
}

// Tutup koneksi
$database->tutupKoneksi();
?>
