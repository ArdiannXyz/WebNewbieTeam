<?php
include_once 'Koneksi.php';

$koneksiObj = new Koneksi();
$koneksi = $koneksiObj->getKoneksi();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $id_materi = $_POST['id_materi'];
    $judul_materi = $_POST['judul_materi'];
    $jenis_materi = $_POST['jenis_materi'];
    $komentar = $_POST['komentar'];

    if (empty($id_materi) || empty($judul_materi) || empty($jenis_materi) || empty($komentar)) {
        echo json_encode(["success" => false, "message" => "Semua field harus diisi."]);
        exit;
    }

    $query = "UPDATE materi SET judul_tugas='$judul_materi', jenis_materi='$jenis_materi', deskripsi='$komentar' WHERE id_materi='$id_materi'";
    if (mysqli_query($koneksi, $query)) {
        echo json_encode(["success" => true, "message" => "Materi berhasil diperbarui."]);
    } else {
        echo json_encode(["success" => false, "message" => "Gagal memperbarui materi."]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Metode tidak didukung."]);
}
?>
