<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log input yang diterima
$raw_input = file_get_contents('php://input');
error_log("Raw input received: " . $raw_input);

// Tangkap input JSON
$input = json_decode($raw_input, true);
error_log("Decoded input: " . print_r($input, true));

// Validasi JSON decode
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => 'Invalid JSON format: ' . json_last_error_msg(),
        'code' => 'INVALID_JSON'
    ]);
    exit();
}

// Menggunakan id_materi sesuai dengan struktur database
$id_materi = isset($input['id_materi']) ? intval($input['id_materi']) : 0;
error_log("ID Materi received: " . $id_materi);

// Validasi input
if ($id_materi <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => 'ID materi tidak valid',
        'code' => 'INVALID_ID'
    ]);
    exit();
}

// Buat instansi dari kelas Koneksi
$koneksiObj = new Koneksi();
$koneksi = $koneksiObj->getKoneksi();

if (!$koneksi) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => 'Koneksi database gagal',
        'code' => 'DB_CONNECTION_FAILED'
    ]);
    exit();
}

try {
    // Mulai transaksi
    $koneksi->begin_transaction();

    // Periksa keberadaan data sebelum dihapus
    $cekData = $koneksi->prepare("SELECT id_materi, file_materi FROM materi WHERE id_materi = ?");
    $cekData->bind_param("i", $id_materi);
    $cekData->execute();
    $result = $cekData->get_result();

    if ($result->num_rows == 0) {
        $koneksi->rollback();
        http_response_code(404);
        echo json_encode([
            'status' => 'error',
            'error' => 'Materi tidak ditemukan',
            'code' => 'NOT_FOUND'
        ]);
        $cekData->close();
        $koneksiObj->tutupKoneksi();
        exit();
    }

    // Ambil informasi file materi jika ada
    $row = $result->fetch_assoc();
    $file_materi = $row['file_materi'];
    $cekData->close();

    // Delete the record instead of updating is_active
    $query = $koneksi->prepare("DELETE FROM materi WHERE id_materi = ?");
    $query->bind_param("i", $id_materi);
    $deleteResult = $query->execute();
    $affectedRows = $query->affected_rows;
    
    error_log("Delete result: " . ($deleteResult ? 'true' : 'false'));
    error_log("Affected rows: " . $affectedRows);
    
    if ($deleteResult && $affectedRows > 0) {
        // Commit transaksi jika berhasil
        $koneksi->commit();

        // Hapus file fisik jika ada
        if ($file_materi && file_exists($file_materi)) {
            unlink($file_materi);
        }

        echo json_encode([
            'success' => true,
            'message' => 'Berhasil menghapus materi',
            'id_materi' => $id_materi
        ]);
    } else {
        $koneksi->rollback();
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal menghapus materi',
            'code' => 'DELETE_FAILED'
        ]);
    }
} catch (Exception $e) {
    $koneksi->rollback();
    error_log("Delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}

$query->close();
$koneksiObj->tutupKoneksi();
?>
