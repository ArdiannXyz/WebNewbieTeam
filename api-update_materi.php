<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Enable error logging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    // Get raw post data for debugging
    $raw_post = file_get_contents('php://input');
    error_log("Raw POST data: " . $raw_post);
    error_log("POST variables: " . print_r($_POST, true));
    error_log("FILES variables: " . print_r($_FILES, true));

    // Validate and sanitize input
    $id_materi = isset($_POST['id_materi']) ? filter_var($_POST['id_materi'], FILTER_VALIDATE_INT) : null;
    $id_mapel = isset($_POST['id_mapel']) ? filter_var($_POST['id_mapel'], FILTER_VALIDATE_INT) : null;
    $id_kelas = isset($_POST['id_kelas']) ? filter_var($_POST['id_kelas'], FILTER_VALIDATE_INT) : null;
    $id_guru = isset($_POST['id_guru']) ? filter_var($_POST['id_guru'], FILTER_VALIDATE_INT) : null;
    $judul_materi = isset($_POST['judul_materi']) ? filter_var($_POST['judul_materi'], FILTER_SANITIZE_STRING) : null;
    $deskripsi = isset($_POST['deskripsi']) ? filter_var($_POST['deskripsi'], FILTER_SANITIZE_STRING) : null;

    // Log received values
    error_log("Received values: " . json_encode([
        'id_materi' => $id_materi,
        'id_mapel' => $id_mapel,
        'id_kelas' => $id_kelas,
        'id_guru' => $id_guru,
        'judul_materi' => $judul_materi,
        'deskripsi' => $deskripsi
    ]));

    // Validate required fields
    if (!$id_materi || !$id_mapel || !$id_kelas || !$id_guru || !$judul_materi) {
        throw new Exception('Semua field wajib diisi. ID Guru: ' . $id_guru);
    }

    // Verify guru exists
    $stmt = $koneksi->prepare("SELECT id_guru FROM guru WHERE id_guru = ?");
    $stmt->bind_param("i", $id_guru);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('ID Guru tidak ditemukan: ' . $id_guru);
    }

    // Handle file upload if exists
    $file_materi = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = 'uploads/materi/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . $_FILES['file']['name'];
        $file_path = $upload_dir . $file_name;

        if (move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            $file_materi = $file_path;
        } else {
            throw new Exception('Gagal upload file');
        }
    }

    // Start transaction
    $koneksi->begin_transaction();

    // Build update query
    $query = "UPDATE materi SET 
        id_mapel = ?,
        id_kelas = ?,
        id_guru = ?,
        judul_materi = ?,
        deskripsi = ?";
    
    $params = [$id_mapel, $id_kelas, $id_guru, $judul_materi, $deskripsi];
    $types = "iiiss";

    if ($file_materi) {
        $query .= ", file_materi = ?";
        $params[] = $file_materi;
        $types .= "s";
    }

    $query .= " WHERE id_materi = ?";
    $params[] = $id_materi;
    $types .= "i";

    $stmt = $koneksi->prepare($query);
    $stmt->bind_param($types, ...$params);
    
    if ($stmt->execute()) {
        $koneksi->commit();
        echo json_encode([
            'success' => true,
            'message' => 'Berhasil update materi'
        ]);
    } else {
        throw new Exception('Gagal update materi: ' . $stmt->error);
    }

} catch (Exception $e) {
    if (isset($koneksi)) {
        $koneksi->rollback();
    }
    error_log("Error in update_materi.php: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        $stmt->close();
    }
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
}
?>
