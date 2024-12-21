<?php
header('Content-Type: application/json');
include 'koneksi.php';

$response = [
    'status' => 'error',
    'message' => '',
    'code' => '',
    'data' => null
];

try {
    // Get and validate JSON input
    $raw_input = file_get_contents('php://input');
    $input = json_decode($raw_input, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('Invalid JSON format: ' . json_last_error_msg(), 400);
    }

    // Validate required parameters
    $id_tugas = isset($input['id_tugas']) ? filter_var($input['id_tugas'], FILTER_VALIDATE_INT) : 0;
    if (!$id_tugas) {
        throw new Exception('Invalid tugas ID', 400);
    }

    // Validate user credentials if provided
    $id_guru = $input['id_guru'] ?? null;
    $role = $input['role'] ?? null;

    // Create database connection
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    if (!$koneksi) {
        throw new Exception('Database connection failed', 500);
    }

    // Start transaction
    $koneksi->begin_transaction();

    // Check if tugas exists and get related data
    $check_query = $koneksi->prepare("
        SELECT t.*, u.role as user_role
        FROM tugas t
        JOIN users u ON t.id_guru = u.id
        WHERE t.id_tugas = ? AND t.is_active = 1
    ");
    
    if (!$check_query) {
        throw new Exception('Failed to prepare check query', 500);
    }

    $check_query->bind_param("i", $id_tugas);
    if (!$check_query->execute()) {
        throw new Exception('Failed to execute check query', 500);
    }

    $result = $check_query->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Tugas tidak ditemukan atau sudah tidak aktif', 404);
    }

    $tugas = $result->fetch_assoc();

    // Verify user authorization
    if ($id_guru && $role !== 'admin') {
        if ($tugas['id_guru'] != $id_guru) {
            throw new Exception('Anda tidak memiliki akses untuk menghapus tugas ini', 403);
        }
    }

    // Soft delete the tugas
    $update_query = $koneksi->prepare("
        UPDATE tugas 
        SET is_active = 0,
            updated_at = CURRENT_TIMESTAMP
        WHERE id_tugas = ?
    ");

    if (!$update_query) {
        throw new Exception('Failed to prepare update query', 500);
    }

    $update_query->bind_param("i", $id_tugas);
    if (!$update_query->execute()) {
        throw new Exception('Gagal menonaktifkan tugas', 500);
    }

    if ($update_query->affected_rows === 0) {
        throw new Exception('Tidak ada perubahan pada tugas', 400);
    }

    // Update related pengumpulan records status if needed
    $update_pengumpulan = $koneksi->prepare("
        UPDATE pengumpulan
        SET status = 'belum_mengumpulkan'
        WHERE id_tugas = ? AND status = 'terlambat'
    ");

    if ($update_pengumpulan) {
        $update_pengumpulan->bind_param("i", $id_tugas);
        $update_pengumpulan->execute();
    }

    // Commit transaction
    $koneksi->commit();

    // Set success response
    $response['status'] = 'sukses';
    $response['message'] = 'Tugas berhasil dihapus';
    $response['code'] = 'SUCCESS';
    $response['data'] = [
        'id_tugas' => $id_tugas,
        'judul_tugas' => $tugas['judul_tugas'],
        'id_mapel' => $tugas['id_mapel'],
        'id_kelas' => $tugas['id_kelas']
    ];

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($koneksi) && $koneksi->connect_errno === 0) {
        $koneksi->rollback();
    }

    $response['status'] = 'gagal';
    $response['message'] = $e->getMessage();
    $response['code'] = $e->getCode() ?: 'SERVER_ERROR';

    // Set appropriate HTTP status code
    $http_code = is_numeric($e->getCode()) ? $e->getCode() : 500;
    http_response_code($http_code);

} finally {
    // Clean up resources
    if (isset($update_pengumpulan)) {
        $update_pengumpulan->close();
    }
    if (isset($update_query)) {
        $update_query->close();
    }
    if (isset($check_query)) {
        $check_query->close();
    }
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }

    // Return JSON response
    echo json_encode($response);
    exit();
}
