<?php
require_once 'Koneksi.php';

header('Content-Type: application/json');

// Validasi method request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Ambil data dari request
$id_pengumpulan = isset($_POST['id_pengumpulan']) ? intval($_POST['id_pengumpulan']) : 0;
$nilai = isset($_POST['nilai']) ? floatval($_POST['nilai']) : 0;
$komentar = isset($_POST['komentar']) ? $_POST['komentar'] : null;

// Validasi input
if ($id_pengumpulan <= 0 || $nilai < 0 || $nilai > 100) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Parameter tidak valid',
        'debug' => [
            'id_pengumpulan' => $id_pengumpulan,
            'nilai' => $nilai
        ]
    ]);
    exit;
}

try {
    $db = new Koneksi();
    $conn = $db->getKoneksi();
    
    // Set user_id untuk nilai_log (sesuaikan dengan sistem autentikasi Anda)
    mysqli_query($conn, "SET @current_user_id = 1"); // Ganti dengan ID guru yang sedang login
    
    // Mulai transaksi
    mysqli_begin_transaction($conn);

    // Update pengumpulan
    $query = "UPDATE pengumpulan SET nilai = ?, updated_at = CURRENT_TIMESTAMP";
    $params = [$nilai];
    $types = "d";

    if ($komentar !== null) {
        $query .= ", komentar = ?";
        $params[] = $komentar;
        $types .= "s";
    }

    $query .= " WHERE id_pengumpulan = ?";
    $params[] = $id_pengumpulan;
    $types .= "i";

    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception(mysqli_error($conn));
    }

    if (mysqli_stmt_affected_rows($stmt) === 0) {
        throw new Exception('Data pengumpulan tidak ditemukan');
    }

    // Commit transaksi
    mysqli_commit($conn);

    echo json_encode([
        'status' => 'success',
        'message' => 'Nilai berhasil diupdate'
    ]);

} catch (Exception $e) {
    // Rollback jika terjadi error
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($db)) {
        $db->tutupKoneksi();
    }
}
?>
