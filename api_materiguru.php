<?php
//api materi guru
include 'koneksi.php';

// Atur header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $database = new Koneksi();
    $koneksi = $database->getKoneksi();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Query untuk mengambil data materi dengan JOIN ke tabel terkait
        $sql = "SELECT 
                m.id_materi,
                m.judul_materi,
                m.deskripsi,
                m.file_materi,
                m.created_at,
                m.is_active,
                u.nama as nama_guru,
                mp.nama_mapel,
                k.nama_kelas,
                m.id_mapel,
                m.id_kelas,
                m.id_guru
            FROM materi m
            JOIN users u ON m.id_guru = u.id
            JOIN mapel mp ON m.id_mapel = mp.id_mapel
            JOIN kelas k ON m.id_kelas = k.id_kelas
            WHERE m.is_active = TRUE
            ORDER BY m.created_at DESC";

        $result = $koneksi->query($sql);

        if ($result === false) {
            throw new Exception("Error executing query: " . $koneksi->error);
        }

        $materiList = [];
        while ($row = $result->fetch_assoc()) {
            $materiList[] = [
                'id_materi' => (int)$row['id_materi'],
                'id_guru' => (int)$row['id_guru'],
                'id_mapel' => (int)$row['id_mapel'],
                'id_kelas' => (int)$row['id_kelas'],
                'judul_materi' => $row['judul_materi'],
                'deskripsi' => $row['deskripsi'],
                'file_materi' => $row['file_materi'],
                'nama_guru' => $row['nama_guru'],
                'nama_mapel' => $row['nama_mapel'],
                'nama_kelas' => $row['nama_kelas'],
                'tanggal_dibuat' => $row['created_at'],
                'is_active' => (bool)$row['is_active']
            ];
        }

        // Format response sesuai dengan ApiResponse.java
        $response = [
            'status' => 'success',
            'success' => true,
            'message' => 'Data materi berhasil diambil',
            'materi_model' => $materiList
        ];

        echo json_encode($response);

    } else {
        throw new Exception("Method not allowed");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'success' => false,
        'message' => $e->getMessage(),
        'materi_model' => null
    ]);
} finally {
    if (isset($database)) {
        $database->tutupKoneksi();
    }
}