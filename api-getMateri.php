<?php
require_once 'koneksi.php';

$koneksi = new Koneksi();
$conn = $koneksi->getKoneksi();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_kelas = isset($_GET['id_kelas']) ? $_GET['id_kelas'] : 0;

    $query = "SELECT m.id_tugas, m.id_guru, m.jenis_materi, m.judul_tugas, m.deskripsi, m.tanggal_dibuat, m.deadline, m.video_url 
              FROM materi AS m
              WHERE m.id_kelas = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_kelas);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }

    echo json_encode([
        'success' => true,
        'materi' => $response
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$koneksi->tutupKoneksi();
?>
