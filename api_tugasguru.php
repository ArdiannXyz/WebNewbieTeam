<?php

//API Tugas Guru

include 'koneksi.php';

// Atur header untuk respons JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

try {
    $database = new Koneksi();
    $koneksi = $database->getKoneksi();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Ambil parameter query
        $id_kelas = isset($_GET['id_kelas']) ? (int)$_GET['id_kelas'] : null;
        $id_mapel = isset($_GET['id_mapel']) ? (int)$_GET['id_mapel'] : null;
        
        // Query dasar
        $sql = "SELECT 
                t.id_tugas,
                t.judul_tugas,
                t.deskripsi,
                t.file_tugas,
                t.deadline,
                t.created_at,
                u.nama as nama_guru,
                mp.nama_mapel,
                k.nama_kelas,
                (
                    SELECT COUNT(*) 
                    FROM pengumpulan p 
                    WHERE p.id_tugas = t.id_tugas
                ) as jumlah_pengumpulan
            FROM tugas t
            JOIN users u ON t.id_guru = u.id
            JOIN mapel mp ON t.id_mapel = mp.id_mapel
            JOIN kelas k ON t.id_kelas = k.id_kelas
            WHERE t.is_active = TRUE";

        // Tambahkan filter jika parameter tersedia
        if ($id_kelas) {
            $sql .= " AND t.id_kelas = ?";
        }
        if ($id_mapel) {
            $sql .= " AND t.id_mapel = ?";
        }
        
        $sql .= " ORDER BY t.deadline ASC";

        $stmt = $koneksi->prepare($sql);

        // Ikat parameter jika ada
        if ($id_kelas && $id_mapel) {
            $stmt->bind_param("ii", $id_kelas, $id_mapel);
        } elseif ($id_kelas) {
            $stmt->bind_param("i", $id_kelas);
        } elseif ($id_mapel) {
            $stmt->bind_param("i", $id_mapel);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("Terjadi kesalahan saat mengeksekusi query: " . $koneksi->error);
        }

        $daftarTugas = [];
        while ($row = $result->fetch_assoc()) {
            $daftarTugas[] = [
                'id_tugas' => $row['id_tugas'],
                'judul_tugas' => $row['judul_tugas'],
                'deskripsi' => $row['deskripsi'],
                'file_tugas' => $row['file_tugas'],
                'batas_waktu' => $row['deadline'],
                'nama_guru' => $row['nama_guru'],
                'nama_mapel' => $row['nama_mapel'],
                'nama_kelas' => $row['nama_kelas'],
                'jumlah_pengumpulan' => $row['jumlah_pengumpulan'],
                'tanggal_dibuat' => $row['created_at']
            ];
        }

        echo json_encode([
            'status' => 'sukses',
            'pesan' => 'Data tugas berhasil diambil',
            'data' => $daftarTugas
        ]);

    } else {
        throw new Exception("Metode tidak diizinkan");
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'gagal',
        'pesan' => $e->getMessage()
    ]);
} finally {
    if (isset($database)) {
        $database->tutupKoneksi();
    }
}