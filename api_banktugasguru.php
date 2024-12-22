<?php
declare(strict_types=1);
ob_start();
error_reporting(0);

include 'koneksi.php';

// Konstanta
define('DEBUG_MODE', false);
define('MAX_LIMIT', 100);
define('DEFAULT_LIMIT', 10);

// Atur header
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Fungsi untuk mengirim response
function sendResponse($status, $message, $data = null, $httpCode = 200) {
    ob_clean();
    http_response_code($httpCode);
    $response = [
        'status' => $status,
        'message' => $message,
        'timestamp' => date('Y-m-d H:i:s')
    ];
    
    if ($data !== null) {
        $response['data'] = $data;
    }
    
    echo json_encode($response);
    exit;
}

try {
    // Cek metode request
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse('error', 'Metode tidak diizinkan', null, 405);
    }

    // Inisialisasi koneksi database
    $database = new Koneksi();
    $koneksi = $database->getKoneksi();

    if (!$koneksi) {
        throw new Exception('Koneksi database gagal');
    }

    // Validasi dan sanitasi parameter
    $id_guru = filter_var($_GET['id_guru'] ?? null, FILTER_VALIDATE_INT);
    $id_kelas = filter_var($_GET['id_kelas'] ?? null, FILTER_VALIDATE_INT);
    $id_mapel = filter_var($_GET['id_mapel'] ?? null, FILTER_VALIDATE_INT);
    $tahun_ajaran = isset($_GET['tahun_ajaran']) ? htmlspecialchars(strip_tags($_GET['tahun_ajaran'])) : null;
    $page = filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT);
    $limit = filter_var($_GET['limit'] ?? DEFAULT_LIMIT, FILTER_VALIDATE_INT);

    // Validasi parameter
    if ($id_guru !== false && $id_guru !== null && $id_guru <= 0) {
        sendResponse('error', 'ID Guru tidak valid', null, 400);
    }
    if ($id_kelas !== false && $id_kelas !== null && $id_kelas <= 0) {
        sendResponse('error', 'ID Kelas tidak valid', null, 400);
    }
    if ($id_mapel !== false && $id_mapel !== null && $id_mapel <= 0) {
        sendResponse('error', 'ID Mapel tidak valid', null, 400);
    }
    if ($tahun_ajaran !== null && !preg_match("/^\d{4}\/\d{4}$/", $tahun_ajaran)) {
        sendResponse('error', 'Format tahun ajaran tidak valid', null, 400);
    }

    // Validasi pagination
    if ($page < 1) $page = 1;
    if ($limit < 1 || $limit > MAX_LIMIT) $limit = DEFAULT_LIMIT;
    $offset = ($page - 1) * $limit;

    // Query dasar
    $sql = "SELECT 
        p.id_pengumpulan,
        p.id_siswa,
        u.nama as nama_siswa,
        p.status,
        p.file_tugas,
        p.nilai,
        p.komentar,
        t.id_tugas,
        t.judul_tugas,
        t.deadline,
        k.id_kelas,
        k.nama_kelas,
        m.id_mapel,
        m.nama_mapel,
        m.kode_mapel
    FROM pengumpulan p
    JOIN users u ON p.id_siswa = u.id
    JOIN tugas t ON p.id_tugas = t.id_tugas
    JOIN kelas k ON t.id_kelas = k.id_kelas
    JOIN mapel m ON t.id_mapel = m.id_mapel
    WHERE t.is_active = TRUE";

    $params = [];
    $types = "";

    // Tambahkan filter
    if ($id_guru !== false && $id_guru !== null) {
        $sql .= " AND t.id_guru = ?";
        $params[] = $id_guru;
        $types .= "i";
    }
    if ($id_kelas !== false && $id_kelas !== null) {
        $sql .= " AND t.id_kelas = ?";
        $params[] = $id_kelas;
        $types .= "i";
    }
    if ($id_mapel !== false && $id_mapel !== null) {
        $sql .= " AND t.id_mapel = ?";
        $params[] = $id_mapel;
        $types .= "i";
    }
    if ($tahun_ajaran !== null) {
        $sql .= " AND k.tahun_ajaran = ?";
        $params[] = $tahun_ajaran;
        $types .= "s";
    }

    // Count total records untuk pagination
    $count_sql = "SELECT COUNT(*) as total FROM (" . $sql . ") as count_table";
    $count_stmt = $koneksi->prepare($count_sql);
    if (!empty($params)) {
        $count_stmt->bind_param($types, ...$params);
    }
    $count_stmt->execute();
    $total_records = $count_stmt->get_result()->fetch_assoc()['total'];
    $total_pages = ceil($total_records / $limit);

    // Tambahkan ordering dan pagination
    $sql .= " ORDER BY t.deadline DESC, p.created_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";

    // Prepare dan execute query utama
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        throw new Exception("Gagal mempersiapkan query: " . $koneksi->error);
    }

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    if (!$stmt->execute()) {
        error_log("Query Error: " . $stmt->error . " for params: " . json_encode($params));
        throw new Exception("Gagal mengeksekusi query");
    }

    $result = $stmt->get_result();
    if ($result === false) {
        throw new Exception("Gagal mengambil hasil query: " . $koneksi->error);
    }

    $daftarBankTugas = [];
    while ($row = $result->fetch_assoc()) {
        // Format status
        $statusTampilan = match($row['status']) {
            'belum_mengumpulkan' => 'Belum Mengumpulkan',
            'tepat_waktu' => 'Tepat Waktu',
            'terlambat' => 'Terlambat',
            default => $row['status']
        };

        // Format nilai
        $statusNilai = $row['nilai'] !== null ? number_format((float)$row['nilai'], 1) : 'Belum dinilai';

        $daftarBankTugas[] = [
            'id_pengumpulan' => (int)$row['id_pengumpulan'],
            'id_siswa' => (int)$row['id_siswa'],
            'nama_siswa' => $row['nama_siswa'] ?? 'Nama tidak tersedia',
            'nilai' => $statusNilai,
            'komentar' => $row['komentar'],
            'status_pengumpulan' => $statusTampilan,
            'file_tugas' => $row['file_tugas'],
            'tugas' => [
                'id_tugas' => (int)$row['id_tugas'],
                'judul_tugas' => $row['judul_tugas'],
                'deadline' => $row['deadline']
            ],
            'kelas' => [
                'id_kelas' => (int)$row['id_kelas'],
                'nama_kelas' => $row['nama_kelas']
            ],
            'mapel' => [
                'id_mapel' => (int)$row['id_mapel'],
                'kode_mapel' => $row['kode_mapel'],
                'nama_mapel' => $row['nama_mapel']
            ]
        ];
    }

    // Siapkan response
    $response = [
        'status' => 'success',
        'message' => empty($daftarBankTugas) ? 'Tidak ada data bank tugas' : 'Data bank tugas berhasil diambil',
        'pagination' => [
            'current_page' => $page,
            'total_pages' => $total_pages,
            'total_records' => $total_records,
            'limit' => $limit
        ],
        'bank_tugas_model' => $daftarBankTugas
    ];
    
    echo json_encode($response);

} catch (Exception $e) {
    error_log($e->getMessage());
    $response = [
        'status' => 'error',
        'message' => DEBUG_MODE ? $e->getMessage() : 'Terjadi kesalahan pada server',
        'timestamp' => date('Y-m-d H:i:s')
    ];
    http_response_code(500);
    echo json_encode($response);

} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($count_stmt)) {
        $count_stmt->close();
    }
    if (isset($database)) {
        $database->tutupKoneksi();
    }
}
?>
