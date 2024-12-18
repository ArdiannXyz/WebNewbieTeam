<?php
declare(strict_types=1);

include 'koneksi.php';

// Atur header untuk respons JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Response handler function
function sendResponse($status, $message, $data = null, $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode([
        'status' => $status,
        'pesan' => $message,
        'data' => $data
    ]);
    exit;
}

try {
    $database = new Koneksi();
    $koneksi = $database->getKoneksi();

    if (!$koneksi) {
        throw new Exception('Koneksi database gagal');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse('gagal', 'Metode tidak diizinkan', null, 405);
    }

    // Validasi dan sanitasi parameter
    $id_guru = filter_var($_GET['id_guru'] ?? null, FILTER_VALIDATE_INT);
    $id_kelas = filter_var($_GET['id_kelas'] ?? null, FILTER_VALIDATE_INT);
    $id_mapel = filter_var($_GET['id_mapel'] ?? null, FILTER_VALIDATE_INT);
    $id_tugas = filter_var($_GET['id_tugas'] ?? null, FILTER_VALIDATE_INT);
    $tahun_ajaran = filter_var($_GET['tahun_ajaran'] ?? null, FILTER_SANITIZE_STRING);

    // Query dasar dengan JOIN yang diperlukan
    $sql = "SELECT 
            p.id_pengumpulan,
            p.file_tugas as file_pengumpulan,
            p.nilai,
            p.komentar,
            p.status,
            p.created_at as waktu_pengumpulan,
            t.id_tugas,
            t.judul_tugas,
            t.deskripsi as deskripsi_tugas,
            t.deadline,
            t.file_tugas,
            u_siswa.nama as nama_siswa,
            u_siswa.email as email_siswa,
            ds.nisn,
            k.nama_kelas,
            k.tahun_ajaran,
            mp.kode_mapel,
            mp.nama_mapel,
            u_guru.nama as nama_guru,
            u_guru.email as email_guru,
            dg.nip as nip_guru
        FROM pengumpulan p
        JOIN tugas t ON p.id_tugas = t.id_tugas
        JOIN users u_siswa ON p.id_siswa = u_siswa.id
        JOIN detail_siswa ds ON u_siswa.id = ds.user_id
        JOIN kelas k ON ds.id_kelas = k.id_kelas
        JOIN mapel mp ON t.id_mapel = mp.id_mapel
        JOIN users u_guru ON t.id_guru = u_guru.id
        JOIN detail_guru dg ON u_guru.id = dg.user_id
        WHERE t.is_active = TRUE";

    $params = [];
    $types = "";

    // Tambahkan filter berdasarkan parameter yang valid
    if ($id_guru !== false && $id_guru !== null) {
        $sql .= " AND t.id_guru = ?";
        $params[] = $id_guru;
        $types .= "i";
    }
    if ($id_kelas !== false && $id_kelas !== null) {
        $sql .= " AND ds.id_kelas = ?";
        $params[] = $id_kelas;
        $types .= "i";
    }
    if ($id_mapel !== false && $id_mapel !== null) {
        $sql .= " AND t.id_mapel = ?";
        $params[] = $id_mapel;
        $types .= "i";
    }
    if ($id_tugas !== false && $id_tugas !== null) {
        $sql .= " AND t.id_tugas = ?";
        $params[] = $id_tugas;
        $types .= "i";
    }
    if ($tahun_ajaran !== false && $tahun_ajaran !== null) {
        $sql .= " AND k.tahun_ajaran = ?";
        $params[] = $tahun_ajaran;
        $types .= "s";
    }

    $sql .= " ORDER BY t.deadline DESC, p.created_at DESC";

    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan query: " . $koneksi->error);
    }

    // Bind parameter jika ada
    if (!empty($params)) {
        if (!$stmt->bind_param($types, ...$params)) {
            throw new Exception("Gagal binding parameter: " . $stmt->error);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Gagal mengeksekusi query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Gagal mengambil hasil query: " . $koneksi->error);
    }

    $daftarPengumpulan = [];
    while ($row = $result->fetch_assoc()) {
        try {
            $deadline = new DateTime($row['deadline']);
            $waktuKumpul = new DateTime($row['waktu_pengumpulan']);
            $selisih = $deadline->diff($waktuKumpul);
            
            $keterlambatan = null;
            if ($row['status'] === 'terlambat') {
                $keterlambatan = [
                    'hari' => $selisih->d,
                    'jam' => $selisih->h,
                    'menit' => $selisih->i
                ];
            }

            $daftarPengumpulan[] = [
                'id_pengumpulan' => (int)$row['id_pengumpulan'],
                'id_tugas' => (int)$row['id_tugas'],
                'judul_tugas' => $row['judul_tugas'],
                'deskripsi_tugas' => $row['deskripsi_tugas'],
                'file_tugas' => $row['file_tugas'],
                'deadline' => $row['deadline'],
                'siswa' => [
                    'nama' => $row['nama_siswa'],
                    'email' => $row['email_siswa'],
                    'nisn' => $row['nisn']
                ],
                'pengumpulan' => [
                    'status' => $row['status'],
                    'waktu' => $row['waktu_pengumpulan'],
                    'file' => $row['file_pengumpulan'],
                    'nilai' => $row['nilai'] !== null ? (float)$row['nilai'] : null,
                    'komentar' => $row['komentar'],
                    'keterlambatan' => $keterlambatan
                ],
                'kelas' => [
                    'nama' => $row['nama_kelas'],
                    'tahun_ajaran' => $row['tahun_ajaran']
                ],
                'mapel' => [
                    'kode' => $row['kode_mapel'],
                    'nama' => $row['nama_mapel']
                ],
                'guru' => [
                    'nama' => $row['nama_guru'],
                    'email' => $row['email_guru'],
                    'nip' => $row['nip_guru']
                ]
            ];
        } catch (Exception $e) {
            // Log error but continue processing other records
            error_log("Error processing row: " . $e->getMessage());
            continue;
        }
    }

    // Hitung statistik
    $statistik = [
        'total_siswa' => count($daftarPengumpulan),
        'sudah_mengumpulkan' => count(array_filter($daftarPengumpulan, function($item) {
            return $item['pengumpulan']['status'] !== 'belum_mengumpulkan';
        })),
        'terlambat' => count(array_filter($daftarPengumpulan, function($item) {
            return $item['pengumpulan']['status'] === 'terlambat';
        })),
        'tepat_waktu' => count(array_filter($daftarPengumpulan, function($item) {
            return $item['pengumpulan']['status'] === 'tepat_waktu';
        }))
    ];

    sendResponse('sukses', 'Data pengumpulan tugas berhasil diambil', [
        'statistik' => $statistik,
        'data' => $daftarPengumpulan
    ]);

} catch (Exception $e) {
    sendResponse('gagal', $e->getMessage(), null, 500);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($database)) {
        $database->tutupKoneksi();
    }
}

