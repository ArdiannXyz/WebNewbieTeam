<?php
include_once 'Koneksi.php';

$koneksiObj = new Koneksi();
$koneksi = $koneksiObj->getKoneksi();

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil parameter POST
    $judul_materi = $_POST['judul_materi'] ?? '';
    $komentar = $_POST['komentar'] ?? '';
    $id_guru = $_POST['id_guru'] ?? '';
    $id_kelas = $_POST['id_kelas'] ?? '';
    $jenis_materi = $_POST['jenis_materi'] ?? '';

    // Debug log untuk memeriksa data yang diterima
    file_put_contents("debug_log.txt", print_r($_POST, true), FILE_APPEND);

    // Validasi data
    if (empty($judul_materi)) {
        $response['success'] = false;
        $response['message'] = "Judul materi tidak boleh kosong.";
        echo json_encode($response);
        exit;
    }
    if (empty($komentar)) {
        $response['success'] = false;
        $response['message'] = "Komentar tidak boleh kosong.";
        echo json_encode($response);
        exit;
    }
    if (empty($id_guru)) {
        $response['success'] = false;
        $response['message'] = "ID Guru tidak boleh kosong.";
        echo json_encode($response);
        exit;
    }
    if (empty($id_kelas) || $id_kelas == '0') {
        $response['success'] = false;
        $response['message'] = "ID Kelas tidak valid atau kosong.";
        echo json_encode($response);
        exit;
    }
    if (empty($jenis_materi)) {
        $response['success'] = false;
        $response['message'] = "Jenis materi tidak boleh kosong.";
        echo json_encode($response);
        exit;
    }

    // Validasi id_kelas di tabel kelas
    $checkKelasQuery = "SELECT id_kelas FROM kelas WHERE id_kelas = '$id_kelas'";
    $result = mysqli_query($koneksi, $checkKelasQuery);

    if (mysqli_num_rows($result) == 0) {
        $response['success'] = false;
        $response['message'] = "ID Kelas tidak valid atau tidak ditemukan.";
        echo json_encode($response);
        exit;
    }

    $tanggal_dibuat = date("Y-m-d H:i:s");

    // Query untuk memasukkan data ke tabel materi
    $query = "INSERT INTO materi (id_guru, jenis_materi, judul_tugas, deskripsi, id_kelas, tanggal_dibuat)
              VALUES ('$id_guru', '$jenis_materi', '$judul_materi', '$komentar', '$id_kelas', '$tanggal_dibuat')";

    // Eksekusi query
    if (mysqli_query($koneksi, $query)) {
        $response['success'] = true;
        $response['message'] = "Materi berhasil diupload.";
    } else {
        $response['success'] = false;
        $response['message'] = "Gagal mengupload materi: " . mysqli_error($koneksi);
    }
} else {
    $response['success'] = false;
    $response['message'] = "Metode tidak didukung!";
}

// Debug log untuk respons
file_put_contents("debug_log.txt", print_r($response, true), FILE_APPEND);

echo json_encode($response);
$koneksiObj->tutupKoneksi();
?>
