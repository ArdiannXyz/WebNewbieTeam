<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Initialize response array
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

    // Validate id_tugas
    $id_tugas = isset($input['id_tugas']) ? filter_var($input['id_tugas'], FILTER_VALIDATE_INT) : 0;
    if (!$id_tugas) {
        throw new Exception('Invalid tugas ID', 400);
    }

    // Optional: validate user permissions if provided
    $user_id = $input['user_id'] ?? null;
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
        SELECT t.*, m.file_materi 
        FROM tugas t
        LEFT JOIN materi m ON t.id_tugas = m.id_materi
        WHERE t.id_tugas = ? AND t.is_active = TRUE
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
        throw new Exception('Tugas not found or already inactive', 404);
    }

    $tugas = $result->fetch_assoc();

    // Optional: Check if user has permission to delete this tugas
    if ($user_id && $role !== 'admin') {
        if ($tugas['id_guru'] != $user_id) {
            throw new Exception('Unauthorized to delete this tugas', 403);
        }
    }

    // Soft delete by setting is_active to FALSE instead of actual deletion
    $update_query = $koneksi->prepare("
        UPDATE tugas 
        SET is_active = FALSE, 
            updated_at = CURRENT_TIMESTAMP
        WHERE id_tugas = ?
    ");

    if (!$update_query) {
        throw new Exception('Failed to prepare update query', 500);
    }

    $update_query->bind_param("i", $id_tugas);
    if (!$update_query->execute()) {
        throw new Exception('Failed to deactivate tugas', 500);
    }

    if ($update_query->affected_rows === 0) {
        throw new Exception('No changes made to tugas', 400);
    }

    // Log the deletion
    $log_query = $koneksi->prepare("
        INSERT INTO activity_log (
            user_id, 
            activity_type, 
            resource_type, 
            resource_id, 
            details,
            created_at
        ) VALUES (?, 'delete', 'tugas', ?, ?, CURRENT_TIMESTAMP)
    ");

    if ($log_query) {
        $details = json_encode([
            'tugas_title' => $tugas['judul_tugas'],
            'class_id' => $tugas['id_kelas'],
            'subject_id' => $tugas['id_mapel']
        ]);
        $log_query->bind_param("iis", $user_id, $id_tugas, $details);
        $log_query->execute();
    }

    // Commit transaction
    $koneksi->commit();

    // Set success response
    $response['status'] = 'success';
    $response['message'] = 'Tugas berhasil dihapus';
    $response['code'] = 'SUCCESS';
    $response['data'] = [
        'id_tugas' => $id_tugas,
        'judul_tugas' => $tugas['judul_tugas']
    ];

} catch (Exception $e) {
    // Rollback transaction if active
    if (isset($koneksi) && $koneksi->connect_errno === 0) {
        $koneksi->rollback();
    }

    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
    $response['code'] = $e->getCode() ?: 'SERVER_ERROR';

    // Set appropriate HTTP status code
    $http_code = is_numeric($e->getCode()) ? $e->getCode() : 500;
    http_response_code($http_code);

    // Add debug information in development environment
    if (getenv('ENVIRONMENT') === 'development') {
        $response['debug'] = [
            'trace' => $e->getTrace(),
            'raw_input' => $raw_input ?? null
        ];
    }

} finally {
    // Clean up resources
    if (isset($log_query)) {
        $log_query->close();
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
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
?>
