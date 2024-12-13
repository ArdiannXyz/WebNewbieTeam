<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Fungsi validasi input
function validateInput($input) {
    return trim($input) !== '';
}

// Buat instansi dari kelas Koneksi
$koneksiObj = new Koneksi();
$koneksi = $koneksiObj->getKoneksi(); // Mendapatkan koneksi

try {
    // Ambil dan filter input
    $id_tugas = filter_input(INPUT_POST, 'id_tugas', FILTER_VALIDATE_INT);
    $id_guru = filter_input(INPUT_POST, 'id_guru', FILTER_VALIDATE_INT);
    $jenis_materi = filter_input(INPUT_POST, 'jenis_materi', FILTER_SANITIZE_STRING);
    $judul_tugas = filter_input(INPUT_POST, 'judul_tugas', FILTER_SANITIZE_STRING);
    $deskripsi = filter_input(INPUT_POST, 'deskripsi', FILTER_SANITIZE_STRING);
    $id_kelas = filter_input(INPUT_POST, 'id_kelas', FILTER_VALIDATE_INT);
    $deadline = filter_input(INPUT_POST, 'deadline', FILTER_SANITIZE_STRING);
    $video_url = filter_input(INPUT_POST, 'video_url', FILTER_VALIDATE_URL, FILTER_FLAG_PATH_REQUIRED);

    // Log input yang diterima untuk debugging
    error_log("Received POST data: " . print_r($_POST, true));

    // Validasi input yang lebih komprehensif
    $errors = [];

    if ($id_tugas === false || $id_tugas === null || $id_tugas <= 0) {
        $errors[] = "ID Tugas tidak valid";
    }

    if ($id_guru === false || $id_guru === null || $id_guru <= 0) {
        $errors[] = "ID Guru tidak valid";
    }

    if (empty($jenis_materi)) {
        $errors[] = "Jenis materi harus diisi";
    }

    if (empty($judul_tugas)) {
        $errors[] = "Judul tugas harus diisi";
    }

    if ($id_kelas === false || $id_kelas === null || $id_kelas <= 0) {
        $errors[] = "ID Kelas tidak valid";
    }

    // Validasi deadline (opsional: sesuaikan format yang diinginkan)
    if (!empty($deadline)) {
        $deadline_time = strtotime($deadline);
        if ($deadline_time === false) {
            $errors[] = "Format deadline tidak valid";
        }
    }

    // Jika ada error, kembalikan response error
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'errors' => $errors
        ]);
        exit();
    }

    // Cek duplikasi judul tugas (kecuali data saat ini)
    $cek_duplikasi = $koneksi->prepare("SELECT * FROM materi WHERE judul_tugas = ? AND id_tugas != ?");
    $cek_duplikasi->bind_param("si", $judul_tugas, $id_tugas);
    $cek_duplikasi->execute();
    $result_duplikasi = $cek_duplikasi->get_result();

    if ($result_duplikasi->num_rows > 0) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'Judul tugas sudah terdaftar'
        ]);
        exit();
    }

    // Persiapkan query update
    $query = $koneksi->prepare("UPDATE materi SET 
        id_guru = ?, 
        jenis_materi = ?, 
        judul_tugas = ?, 
        deskripsi = ?, 
        id_kelas = ?, 
        deadline = ?, 
        video_url = ? 
        WHERE id_tugas = ?");

    $query->bind_param(
        "issssssi", 
        $id_guru, 
        $jenis_materi, 
        $judul_tugas, 
        $deskripsi, 
        $id_kelas, 
        $deadline, 
        $video_url, 
        $id_tugas
    );

    // Eksekusi query
    if ($query->execute()) {
        // Cek apakah ada baris yang ter-update
        if ($query->affected_rows > 0) {
            echo json_encode([
                'success' => true,
                'message' => 'Berhasil update materi',
                'updated_rows' => $query->affected_rows
            ]);
        } else {
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'message' => 'Tidak ada data yang diupdate, mungkin ID tidak ditemukan'
            ]);
        }
    } else {
        // Error eksekusi query
        http_response_code(500);
        error_log("Update Error: " . $query->error);
        echo json_encode([
            'success' => false,
            'message' => 'Gagal update materi',
            'error' => $query->error
        ]);
    }

} catch (Exception $e) {
    // Tangani exception
    http_response_code(500);
    error_log("Exception: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Terjadi kesalahan',
        'error' => $e->getMessage()
    ]);
} finally {
    // Tutup koneksi
    $koneksiObj->tutupKoneksi();
}
exit();
?>