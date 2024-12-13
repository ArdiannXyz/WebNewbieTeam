<?php
require_once 'Koneksi.php';

header('Content-Type: application/json');

// Validasi method request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed']);
    exit;
}

// Debugging: Log semua data yang diterima
error_log("POST data received: " . print_r($_POST, true));
error_log("FILES data received: " . print_r($_FILES, true));

// Ambil data dari request
$id_pengumpulan = $_POST['id_pengumpulan'] ?? '';
$nilai = floatval($_POST['nilai'] ?? 0);

// Handle file upload if exists
$file_path = '';
if (isset($_FILES['file_nilai']) && $_FILES['file_nilai']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = 'uploads/'; // Sesuaikan dengan direktori upload Anda
    $file_name = time() . '_' . basename($_FILES['file_nilai']['name']);
    $file_path = $upload_dir . $file_name;
    
    if (!move_uploaded_file($_FILES['file_nilai']['tmp_name'], $file_path)) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Gagal upload file']);
        exit;
    }
}

// Validasi input
if (empty($id_pengumpulan) || $nilai <= 0 || $nilai > 100) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error', 
        'message' => 'Invalid input parameters',
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
    
    // Prepare statement untuk update
    $query = "UPDATE pengumpulan SET 
              nilai = ?, 
              waktu_penilaian = CURRENT_TIMESTAMP";
    
    // Tambahkan update file jika ada file yang diupload
    $params = [];
    $types = "d"; // untuk nilai (double)
    $params[] = $nilai;
    
    if (!empty($file_path)) {
        $query .= ", file_nilai = ?";
        $types .= "s"; // untuk file_path (string)
        $params[] = $file_path;
    }
    
    $query .= " WHERE id = ?";
    $types .= "i"; // untuk id_pengumpulan (integer)
    $params[] = $id_pengumpulan;
    
    $stmt = mysqli_prepare($conn, $query);
    
    // Bind parameters dinamis
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_stmt_affected_rows($stmt) > 0) {
            echo json_encode([
                'status' => 'success',
                'message' => 'Nilai berhasil diupdate'
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Data pengumpulan tidak ditemukan'
            ]);
        }
    } else {
        throw new Exception(mysqli_error($conn));
    }
    
    mysqli_stmt_close($stmt);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Server error: ' . $e->getMessage()
    ]);
} finally {
    if (isset($db)) {
        $db->tutupKoneksi();
    }
}
?>