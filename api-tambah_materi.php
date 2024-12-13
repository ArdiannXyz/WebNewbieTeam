<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Buat instansi dari kelas Koneksi
$koneksiObj = new Koneksi();
$koneksi = $koneksiObj->getKoneksi(); // Mendapatkan koneksi

// Ambil data dari POST
$id_guru = $_POST['id_guru'];
$jenis_materi = $_POST['jenis_materi']; // Pastikan ini sesuai dengan format yang diinginkan (BLOB)
$judul_tugas = $_POST['judul_tugas'];
$deskripsi = $_POST['deskripsi'];
$id_kelas = $_POST['id_kelas'];
$deadline = $_POST['deadline']; // Pastikan ini dalam format datetime yang benar
$video_url = $_POST['video_url'];

// Validasi input
if (empty($id_guru) || empty($jenis_materi) || empty($judul_tugas) || empty($id_kelas)) {
    http_response_code(400);
    echo json_encode(['error' => 'Data tidak lengkap']);
    exit();
}

// Insert data ke tabel materi
$query = $koneksi->prepare("INSERT INTO materi (id_guru, jenis_materi, judul_tugas, deskripsi, id_kelas, deadline, video_url) VALUES (?, ?, ?, ?, ?, ?, ?)");
$query->bind_param("issssss", $id_guru, $jenis_materi, $judul_tugas, $deskripsi, $id_kelas, $deadline, $video_url);

if ($query->execute()) {
    echo json_encode(['message' => 'Berhasil tambah materi']);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Gagal tambah materi']);
}

// Tutup koneksi
$koneksiObj->tutupKoneksi();
exit();
?>