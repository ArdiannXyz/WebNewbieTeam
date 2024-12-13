<?php
//api-hapus_tugas.php

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

$id_tugas = isset($input['id_tugas']) ? intval($input['id_tugas']) : 0;
error_log("ID Tugas received: " . $id_tugas);

// Validasi input
if ($id_tugas <= 0) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'error' => 'ID tidak valid',
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

// Periksa keberadaan data sebelum dihapus
$cekData = $koneksi->prepare("SELECT id_tugas FROM materi WHERE id_tugas = ?");
$cekData->bind_param("i", $id_tugas);
$cekData->execute();
$result = $cekData->get_result();

if ($result->num_rows == 0) {
    http_response_code(404);
    echo json_encode([
        'status' => 'error',
        'error' => 'Tugas tidak ditemukan',
        'code' => 'NOT_FOUND'
    ]);
    $cekData->close();
    $koneksiObj->tutupKoneksi();
    exit();
}
$cekData->close();

// Hapus data
$query = $koneksi->prepare("DELETE FROM materi WHERE id_tugas = ?");
$query->bind_param("i", $id_tugas);

try {
    $deleteResult = $query->execute();
    $affectedRows = $query->affected_rows;
    
    error_log("Delete result: " . ($deleteResult ? 'true' : 'false'));
    error_log("Affected rows: " . $affectedRows);
    
    if ($deleteResult && $affectedRows > 0) {
        echo json_encode([
            'status' => 'success',
            'message' => 'Berhasil hapus Tugas',
            'id_tugas' => $id_tugas
        ]);
    } else {
        http_response_code(500);
        echo json_encode([
            'status' => 'error',
            'error' => 'Gagal hapus Tugas',
            'code' => 'DELETE_FAILED'
        ]);
    }
} catch (Exception $e) {
    error_log("Delete error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'code' => 'SERVER_ERROR'
    ]);
}

$query->close();
$koneksiObj->tutupKoneksi();
?>