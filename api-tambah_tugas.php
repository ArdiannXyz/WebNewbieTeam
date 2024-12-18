<?php
header('Content-Type: application/json');
include 'koneksi.php';

try {
    // Buat koneksi database
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();
    
    // Mulai transaksi
    $koneksi->begin_transaction();

    // Validasi method request
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed');
    }

    // Validasi input yang diperlukan
    $required_fields = ['id_guru', 'id_mapel', 'id_kelas', 'judul_tugas', 'deskripsi', 'deadline'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field $field harus diisi");
        }
    }

    // Sanitasi input
    $id_guru = filter_input(INPUT_POST, 'id_guru', FILTER_VALIDATE_INT);
    $id_mapel = filter_input(INPUT_POST, 'id_mapel', FILTER_VALIDATE_INT);
    $id_kelas = filter_input(INPUT_POST, 'id_kelas', FILTER_VALIDATE_INT);
    $judul_tugas = filter_var($_POST['judul_tugas'], FILTER_SANITIZE_STRING);
    $deskripsi = filter_var($_POST['deskripsi'], FILTER_SANITIZE_STRING);
    $deadline = filter_var($_POST['deadline'], FILTER_SANITIZE_STRING);

    // Validasi format tanggal deadline
    $deadline_timestamp = strtotime($deadline);
    if ($deadline_timestamp === false) {
        throw new Exception('Format deadline tidak valid');
    }
    $deadline_formatted = date('Y-m-d H:i:s', $deadline_timestamp);

    // Handle file upload jika ada
    $file_tugas = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Validasi ukuran file (maksimal 10MB)
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar (maksimal 10MB)');
        }

        // Validasi tipe file
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('Tipe file tidak diizinkan');
        }

        // Buat direktori upload jika belum ada
        $upload_dir = 'uploads/tugas/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate nama file unik
        $file_name = uniqid() . '_' . date('Ymd_His') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Pindahkan file
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            throw new Exception('Gagal mengupload file');
        }

        $file_tugas = $file_path;
    }

    // Validasi relasi data
    // Cek apakah guru ada
    $stmt = $koneksi->prepare("SELECT id FROM users WHERE id = ? AND role = 'guru'");
    $stmt->bind_param("i", $id_guru);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('ID Guru tidak valid');
    }

    // Cek apakah kelas ada
    $stmt = $koneksi->prepare("SELECT id_kelas FROM kelas WHERE id_kelas = ?");
    $stmt->bind_param("i", $id_kelas);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('ID Kelas tidak valid');
    }

    // Cek apakah mapel ada
    $stmt = $koneksi->prepare("SELECT id_mapel FROM mapel WHERE id_mapel = ?");
    $stmt->bind_param("i", $id_mapel);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception('ID Mapel tidak valid');
    }

    // Siapkan query insert
    $query = "INSERT INTO tugas (id_guru, id_mapel, id_kelas, judul_tugas, deskripsi, file_tugas, deadline, is_active) 
              VALUES (?, ?, ?, ?, ?, ?, ?, TRUE)";
    
    $stmt = $koneksi->prepare($query);
    $stmt->bind_param("iiissss", 
        $id_guru, 
        $id_mapel, 
        $id_kelas, 
        $judul_tugas, 
        $deskripsi, 
        $file_tugas, 
        $deadline_formatted
    );

    // Eksekusi query
    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan tugas: ' . $stmt->error);
    }

    // Dapatkan ID tugas yang baru dibuat
    $id_tugas = $stmt->insert_id;

    // Buat record pengumpulan untuk setiap siswa di kelas tersebut
    $query_siswa = "INSERT INTO pengumpulan (id_tugas, id_siswa, status) 
                   SELECT ?, ds.user_id, 'belum_mengumpulkan'
                   FROM detail_siswa ds
                   WHERE ds.id_kelas = ?";
    
    $stmt_pengumpulan = $koneksi->prepare($query_siswa);
    $stmt_pengumpulan->bind_param("ii", $id_tugas, $id_kelas);
    
    if (!$stmt_pengumpulan->execute()) {
        throw new Exception('Gagal membuat record pengumpulan: ' . $stmt_pengumpulan->error);
    }

    // Commit transaksi
    $koneksi->commit();

    // Kirim response sukses
    echo json_encode([
        'success' => true,
        'message' => 'Tugas berhasil ditambahkan',
        'data' => [
            'id_tugas' => $id_tugas,
            'judul_tugas' => $judul_tugas,
            'file_tugas' => $file_tugas
        ]
    ]);

} catch (Exception $e) {
    // Rollback transaksi jika ada error
    if (isset($koneksi)) {
        $koneksi->rollback();
    }

    // Hapus file yang terupload jika ada error
    if (isset($file_path) && file_exists($file_path)) {
        unlink($file_path);
    }

    // Kirim response error
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);

} finally {
    // Tutup koneksi
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
}
?>