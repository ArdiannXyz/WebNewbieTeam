<?php
ob_start(); // Start output buffering
error_reporting(0); // Disable error reporting
ini_set('display_errors', 0);

// Prevent any output before JSON response
header('Content-Type: application/json');

include 'koneksi.php';

// Error handling function
function sendError($code, $message) {
    ob_clean(); // Clean output buffer before sending
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    ob_end_flush();
    exit();
}

try {
    // Buat instansi dari kelas Koneksi
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    if (!$koneksi) {
        sendError(500, "Database connection failed");
    }

    // Validate required POST data
    $required_fields = ['id_guru', 'id_mapel', 'id_kelas', 'judul_materi', 'deskripsi'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            sendError(400, "Field '$field' is required");
        }
    }

    // Get POST data
    $id_guru = $koneksi->real_escape_string($_POST['id_guru']);
    $id_mapel = $koneksi->real_escape_string($_POST['id_mapel']);
    $id_kelas = $koneksi->real_escape_string($_POST['id_kelas']);
    $judul_materi = $koneksi->real_escape_string($_POST['judul_materi']);
    $deskripsi = $koneksi->real_escape_string($_POST['deskripsi']);
    
    // Validate that the guru exists and has the correct role
    $stmt = $koneksi->prepare("SELECT role FROM users WHERE id = ? AND role = 'guru' AND is_active = TRUE");
    if (!$stmt) {
        sendError(500, "Database error: " . $koneksi->error);
    }
    
    $stmt->bind_param("i", $id_guru);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        sendError(403, "Invalid or unauthorized teacher ID");
    }
    $stmt->close();

    // Validate that the mapel exists
    $stmt = $koneksi->prepare("SELECT id_mapel FROM mapel WHERE id_mapel = ?");
    if (!$stmt) {
        sendError(500, "Database error: " . $koneksi->error);
    }
    
    $stmt->bind_param("i", $id_mapel);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        sendError(404, "Subject not found");
    }
    $stmt->close();

    // Validate that the kelas exists
    $stmt = $koneksi->prepare("SELECT id_kelas FROM kelas WHERE id_kelas = ?");
    if (!$stmt) {
        sendError(500, "Database error: " . $koneksi->error);
    }
    
    $stmt->bind_param("i", $id_kelas);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        sendError(404, "Class not found");
    }
    $stmt->close();

    // Handle file upload if present
    $file_materi = null;
    $original_filename = null;
    
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/materi/';
        if (!file_exists($upload_dir)) {
            if (!mkdir($upload_dir, 0777, true)) {
                sendError(500, "Failed to create upload directory");
            }
        }

        $original_filename = $_FILES['file_materi']['name'];
        $file_extension = strtolower(pathinfo($original_filename, PATHINFO_EXTENSION));
        $file_name = uniqid('materi_') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Validate file type
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'xlsx', 'xls'];
        if (!in_array($file_extension, $allowed_types)) {
            sendError(400, "Invalid file type. Allowed types: " . implode(', ', $allowed_types));
        }

        // Validate file size (max 10MB)
        $max_size = 10 * 1024 * 1024; // 10MB in bytes
        if ($_FILES['file_materi']['size'] > $max_size) {
            sendError(400, "File too large. Maximum size is 10MB");
        }

        if (!move_uploaded_file($_FILES['file_materi']['tmp_name'], $file_path)) {
            sendError(500, "Failed to upload file");
        }

        $file_materi = $file_path;
    }

    // Begin transaction
    $koneksi->begin_transaction();

    try {
        // Insert data into materi table
        $stmt = $koneksi->prepare("INSERT INTO materi (id_guru, id_mapel, id_kelas, judul_materi, deskripsi, file_materi, original_filename, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        if (!$stmt) {
            throw new Exception("Database error: " . $koneksi->error);
        }

        $stmt->bind_param("iiissss", $id_guru, $id_mapel, $id_kelas, $judul_materi, $deskripsi, $file_materi, $original_filename);

        if (!$stmt->execute()) {
            throw new Exception("Failed to create materi: " . $stmt->error);
        }

        $id_materi = $stmt->insert_id;
        $stmt->close();

        // Commit transaction
        $koneksi->commit();

        // Return success response
        ob_clean(); // Clean output buffer before sending response
        echo json_encode([
            'success' => true,
            'message' => 'Materi berhasil ditambahkan',
            'data' => [
                'id_materi' => $id_materi,
                'judul_materi' => $judul_materi,
                'file_materi' => $file_materi,
                'original_filename' => $original_filename
            ]
        ]);
        ob_end_flush();

    } catch (Exception $e) {
        // Rollback transaction if error occurs
        $koneksi->rollback();
        
        // Delete uploaded file if exists
        if ($file_materi && file_exists($file_materi)) {
            unlink($file_materi);
        }
        
        sendError(500, $e->getMessage());
    }

} catch (Exception $e) {
    sendError(500, "Server error: " . $e->getMessage());
} finally {
    // Close database connection
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
}
