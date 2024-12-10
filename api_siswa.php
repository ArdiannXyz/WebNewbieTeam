<?php
include 'koneksi.php';

header('Content-Type: application/json');
$db = new Koneksi();
$koneksi = $db->getKoneksi();

$id_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (!empty($id_user)) {
    $stmt = $koneksi->prepare("SELECT nama, nisn FROM siswa WHERE id_user = ?");
    if (!$stmt) {
        die(json_encode(["success" => false, "message" => "Error prepare statement: " . $koneksi->error]));
    }

    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $siswa = $result->fetch_assoc();
        $response = ["success" => true, "message" => "Data siswa ditemukan", "data" => $siswa];
    } else {
        $response = ["success" => false, "message" => "Siswa tidak ditemukan"];
    }

    echo json_encode($response);
} else {
    echo json_encode(["success" => false, "message" => "ID User tidak diberikan"]);
}
?>
