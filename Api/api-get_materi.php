<?php
// Aktifkan pelaporan error untuk debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Pastikan header JSON selalu dikirim
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // Jika perlu
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

include 'koneksi.php';

// Log untuk debugging
function logError($message) {
    error_log(date('[Y-m-d H:i:s] ') . $message . "\n", 3, 'api_error_log.txt');
}

try {
    // Buat instansi dari kelas Koneksi
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi(); 

    // Debugging: Log semua parameter yang diterima
    logError('Received POST data: ' . print_r($_POST, true));

    // Tangkap parameter yang dikirim dengan validasi tambahan
    $materi_id = filter_input(INPUT_POST, 'id_tugas', FILTER_VALIDATE_INT);
    $id_kelas = filter_input(INPUT_POST, 'id_kelas', FILTER_VALIDATE_INT);

    // Validasi input
    if ($materi_id === false || $materi_id === null) {
        throw new Exception('Invalid id_tugas');
    }

    // Query dengan validasi id_kelas tambahan
    $query = "SELECT * FROM materi WHERE id_tugas = ? AND id_kelas = ?";
    $stmt = $koneksi->prepare($query);
    
    if (!$stmt) {
        throw new Exception('Prepare statement failed: ' . $koneksi->error);
    }

    $stmt->bind_param("ii", $materi_id, $id_kelas);
    $stmt->execute();
    
    if ($stmt->errno) {
        throw new Exception('Execute statement failed: ' . $stmt->error);
    }

    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $materi = $result->fetch_assoc();
        
        $response = [
            'success' => true,
            'data' => $materi
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Materi tidak ditemukan',
            'details' => [
                'id_tugas' => $id_materi,
                'id_kelas' => $id_kelas
            ]
        ];
    }

    // Encode dengan opsi untuk menangani karakter khusus
    echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

} catch (Exception $e) {
    // Tangani error dengan response JSON
    $errorResponse = [
        'success' => false,
        'message' => 'Terjadi kesalahan',
        'error' => $e->getMessage()
    ];

    // Log error
    logError('API Error: ' . $e->getMessage());
    
    // Kirim response error
    echo json_encode($errorResponse, JSON_UNESCAPED_UNICODE);
} finally {
    // Pastikan koneksi ditutup
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
}

exit();
?>