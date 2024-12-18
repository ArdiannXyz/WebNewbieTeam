<?php
header('Content-Type: application/json');
include 'koneksi.php';

try {
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    // Validate input
    $id_materi = isset($_POST['id_materi']) ? (int)$_POST['id_materi'] : 0;
    $id_mapel = isset($_POST['id_mapel']) ? (int)$_POST['id_mapel'] : 0;
    $id_kelas = isset($_POST['id_kelas']) ? (int)$_POST['id_kelas'] : 0;
    $id_guru = isset($_POST['id_guru']) ? (int)$_POST['id_guru'] : 0;
    $judul_materi = isset($_POST['judul_materi']) ? 
        htmlspecialchars($_POST['judul_materi'], ENT_QUOTES, 'UTF-8') : '';
    $deskripsi = isset($_POST['deskripsi']) ? 
        htmlspecialchars($_POST['deskripsi'], ENT_QUOTES, 'UTF-8') : '';

    if (!$id_materi || !$id_mapel || !$id_kelas || !$id_guru || !$judul_materi) {
        throw new Exception('Semua field wajib diisi');
    }

    // Verify guru exists in users table with role 'guru'
    $stmt = $koneksi->prepare("SELECT id FROM users WHERE id = ? AND role = 'guru'");
    if (!$stmt) {
        throw new Exception('Error preparing statement: ' . $koneksi->error);
    }
    
    $stmt->bind_param("i", $id_guru);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('ID Guru tidak ditemukan atau tidak valid: ' . $id_guru);
    }
    $stmt->close();

    // Handle file upload
    $file_materi = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $upload_dir = 'uploads/materi/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = time() . '_' . $_FILES['file']['name'];
        $file_path = $upload_dir . $file_name;

        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            throw new Exception('Gagal upload file');
        }
        $file_materi = $file_path;
    }

    // Start transaction
    $koneksi->begin_transaction();

    // Update query
    $query = "UPDATE materi SET 
        id_mapel = ?, 
        id_kelas = ?, 
        id_guru = ?, 
        judul_materi = ?, 
        deskripsi = ?, 
        updated_at = CURRENT_TIMESTAMP";
    
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
    if (!$stmt) {
        throw new Exception('Error preparing update statement: ' . $koneksi->error);
    }

    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('Gagal update materi: ' . $stmt->error);
    }

    $koneksi->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Berhasil update materi'
    ]);

} catch (Exception $e) {
    if (isset($koneksi)) {
        $koneksi->rollback();
    }
    error_log("Error in update_materi.php: " . $e->getMessage());
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
