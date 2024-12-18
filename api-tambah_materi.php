<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Error handling function
function sendError($code, $message) {
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit();
}

try {
    // Buat instansi dari kelas Koneksi
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    // Validate required POST data
    $required_fields = ['id_guru', 'id_mapel', 'id_kelas', 'judul_materi', 'deskripsi'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            sendError(400, "Field '$field' is required");
        }
    }

    // Get POST data
    $id_guru = $_POST['id_guru'];
    $id_mapel = $_POST['id_mapel'];
    $id_kelas = $_POST['id_kelas'];
    $judul_materi = $_POST['judul_materi'];
    $deskripsi = $_POST['deskripsi'];
    
    // Validate that the guru exists and has the correct role
    $stmt = $koneksi->prepare("SELECT role FROM users WHERE id = ? AND role = 'guru' AND is_active = TRUE");
    $stmt->bind_param("i", $id_guru);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        sendError(403, "Invalid or unauthorized teacher ID");
    }

    // Validate that the mapel exists
    $stmt = $koneksi->prepare("SELECT id_mapel FROM mapel WHERE id_mapel = ?");
    $stmt->bind_param("i", $id_mapel);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        sendError(404, "Subject not found");
    }

    // Validate that the kelas exists
    $stmt = $koneksi->prepare("SELECT id_kelas FROM kelas WHERE id_kelas = ?");
    $stmt->bind_param("i", $id_kelas);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        sendError(404, "Class not found");
    }

    // Handle file upload if present
    $file_materi = null;
    if (isset($_FILES['file_materi']) && $_FILES['file_materi']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = 'uploads/materi/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_extension = pathinfo($_FILES['file_materi']['name'], PATHINFO_EXTENSION);
        $file_name = uniqid('materi_') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Validate file type (you can modify allowed types as needed)
        $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'txt'];
        if (!in_array(strtolower($file_extension), $allowed_types)) {
            sendError(400, "Invalid file type. Allowed types: " . implode(', ', $allowed_types));
        }

        if (!move_uploaded_file($_FILES['file_materi']['tmp_name'], $file_path)) {
            sendError(500, "Failed to upload file");
        }

        $file_materi = $file_path;
    }

    // Insert data into materi table
    $stmt = $koneksi->prepare("INSERT INTO materi (id_guru, id_mapel, id_kelas, judul_materi, deskripsi, file_materi) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiisss", $id_guru, $id_mapel, $id_kelas, $judul_materi, $deskripsi, $file_materi);

    if (!$stmt->execute()) {
        // If file was uploaded but database insert failed, remove the uploaded file
        if ($file_materi && file_exists($file_materi)) {
            unlink($file_materi);
        }
        sendError(500, "Failed to create materi: " . $stmt->error);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Materi berhasil ditambahkan',
        'data' => [
            'id_materi' => $stmt->insert_id,
            'judul_materi' => $judul_materi,
            'file_materi' => $file_materi
        ]
    ]);

} catch (Exception $e) {
    sendError(500, "Server error: " . $e->getMessage());
} finally {
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
}
