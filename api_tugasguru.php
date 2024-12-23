<?php
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
        
        // Query dasar dengan penambahan status pengumpulan dan ID yang dibutuhkan
        $sql = "SELECT 
                t.id_tugas,
                t.id_mapel,
                t.id_kelas,
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
                    AND p.status != 'belum_mengumpulkan'
                ) as jumlah_mengumpulkan,
                (
                    SELECT COUNT(*)
                    FROM pengumpulan p 
                    WHERE p.id_tugas = t.id_tugas
                ) as total_siswa,
                (
                    SELECT COUNT(*)
                    FROM pengumpulan p 
                    WHERE p.id_tugas = t.id_tugas 
                    AND p.status = 'tepat_waktu'
                ) as tepat_waktu,
                (
                    SELECT COUNT(*)
                    FROM pengumpulan p 
                    WHERE p.id_tugas = t.id_tugas 
                    AND p.status = 'terlambat'
                ) as terlambat
            FROM tugas t
            JOIN users u ON t.id_guru = u.id
            JOIN mapel mp ON t.id_mapel = mp.id_mapel
            JOIN kelas k ON t.id_kelas = k.id_kelas
            WHERE t.is_active = 1";

        // Tambahkan filter jika parameter tersedia
        $params = array();
        $types = "";

        if ($id_kelas) {
            $sql .= " AND t.id_kelas = ?";
            $params[] = $id_kelas;
            $types .= "i";
        }
        if ($id_mapel) {
            $sql .= " AND t.id_mapel = ?";
            $params[] = $id_mapel;
            $types .= "i";
        }
        
        $sql .= " ORDER BY t.deadline ASC";

        $stmt = $koneksi->prepare($sql);

        // Bind parameter jika ada
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }

        $stmt->execute();
        $result = $stmt->get_result();

        if ($result === false) {
            throw new Exception("Terjadi kesalahan saat mengeksekusi query: " . $koneksi->error);
        }

        $daftarTugas = [];
        while ($row = $result->fetch_assoc()) {
            // Hitung persentase pengumpulan
            $persentase_pengumpulan = $row['total_siswa'] > 0 
                ? round(($row['jumlah_mengumpulkan'] / $row['total_siswa']) * 100, 2)
                : 0;

            // Tambahkan id_mapel dan id_kelas ke level utama
            $daftarTugas[] = [
                'id_tugas' => (int)$row['id_tugas'],
                'id_mapel' => (int)$row['id_mapel'],
                'id_kelas' => (int)$row['id_kelas'],
                'judul_tugas' => $row['judul_tugas'],
                'deskripsi' => $row['deskripsi'],
                'file_tugas' => $row['file_tugas'],
                'deadline' => $row['deadline'],
                'nama_guru' => $row['nama_guru'],
                'nama_mapel' => $row['nama_mapel'],
                'nama_kelas' => $row['nama_kelas'],
                'statistik_pengumpulan' => [
                    'total_siswa' => (int)$row['total_siswa'],
                    'sudah_mengumpulkan' => (int)$row['jumlah_mengumpulkan'],
                    'tepat_waktu' => (int)$row['tepat_waktu'],
                    'terlambat' => (int)$row['terlambat'],
                    'belum_mengumpulkan' => (int)($row['total_siswa'] - $row['jumlah_mengumpulkan']),
                    'persentase_pengumpulan' => $persentase_pengumpulan
                ],
                'created_at' => $row['created_at']
            ];
        }

        echo json_encode([
            'status' => 'sukses',
            'pesan' => 'Data tugas berhasil diambil',
            'tugas_data' => $daftarTugas
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
