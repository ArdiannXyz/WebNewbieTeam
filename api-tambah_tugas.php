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
        throw new Exception('Method not allowed', 405);
    }

    // Validasi input yang diperlukan
    $required_fields = ['id_guru', 'id_mapel', 'id_kelas', 'judul_tugas', 'deskripsi', 'deadline'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Field $field harus diisi", 400);
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
        throw new Exception('Format deadline tidak valid', 400);
    }
    $deadline_formatted = date('Y-m-d H:i:s', $deadline_timestamp);

    // Validasi guru mengajar di kelas dan mapel tersebut (sesuai dengan trigger trg_validate_teacher_class)
    $stmt = $koneksi->prepare("
        SELECT COUNT(*) as is_teaching 
        FROM kelas_mapel 
        WHERE id_guru = ? AND id_kelas = ? AND id_mapel = ?
    ");
    $stmt->bind_param("iii", $id_guru, $id_kelas, $id_mapel);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    
    if ($row['is_teaching'] == 0) {
        throw new Exception('Guru tidak mengajar mata pelajaran ini di kelas tersebut', 403);
    }

    // Handle file upload jika ada
    $file_tugas = null;
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        // Validasi ukuran file (maksimal 10MB)
        if ($_FILES['file']['size'] > 10 * 1024 * 1024) {
            throw new Exception('Ukuran file terlalu besar (maksimal 10MB)', 400);
        }

        // Validasi tipe file
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar'];
        $file_extension = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_types)) {
            throw new Exception('Tipe file tidak diizinkan', 400);
        }

        // Buat direktori upload jika belum ada
        $upload_dir = 'uploads/tugas/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        // Generate nama file unik
        $original_filename = $_FILES['file']['name'];
        $file_name = uniqid() . '_' . date('Ymd_His') . '.' . $file_extension;
        $file_path = $upload_dir . $file_name;

        // Pindahkan file
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
            throw new Exception('Gagal mengupload file', 500);
        }

        $file_tugas = $file_path;
    }

    // Insert tugas baru
    $query = "INSERT INTO tugas (id_guru, id_mapel, id_kelas, judul_tugas, deskripsi, file_tugas, deadline, is_active) 
              VALUES (?, ?, ?, ?, ?, ?, ?, 1)";
    
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

    if (!$stmt->execute()) {
        throw new Exception('Gagal menyimpan tugas: ' . $stmt->error, 500);
    }

    $id_tugas = $stmt->insert_id;

    // Trigger trg_create_submission_records akan otomatis membuat record pengumpulan

    // Commit transaksi
    $koneksi->commit();

    // Kirim response sukses
    echo json_encode([
        'status' => 'sukses',
        'pesan' => 'Tugas berhasil ditambahkan',
        'tugas_data' => [
            'id_tugas' => $id_tugas,
            'judul_tugas' => $judul_tugas,
            'deskripsi' => $deskripsi,
            'deadline' => $deadline_formatted,
            'file_tugas' => $file_tugas,
            'id_mapel' => $id_mapel,
            'id_kelas' => $id_kelas,
            'id_guru' => $id_guru
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
    $http_code = $e->getCode() ?: 500;
    http_response_code($http_code);
    
    echo json_encode([
        'status' => 'gagal',
        'pesan' => $e->getMessage()
    ]);

} finally {
    // Tutup koneksi
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
}
