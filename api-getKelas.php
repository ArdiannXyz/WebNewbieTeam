<?php
require_once 'koneksi.php';

$koneksi = new Koneksi();
$conn = $koneksi->getKoneksi();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $id_mapel = isset($_GET['id_mapel']) ? $_GET['id_mapel'] : 0;

    $query = "SELECT k.id_kelas, k.nama_kelas, k.tahun_ajaran, k.wali_kelas 
              FROM kelas AS k
              INNER JOIN kelas_mapel AS km ON k.id_kelas = km.id_kelas
              WHERE km.id_mapel = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id_mapel);
    $stmt->execute();
    $result = $stmt->get_result();

    $response = [];
    while ($row = $result->fetch_assoc()) {
        $response[] = $row;
    }

    echo json_encode([
        'success' => true,
        'kelas' => $response
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}

$koneksi->tutupKoneksi();
?>
