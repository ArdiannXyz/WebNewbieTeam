<?php
include 'koneksi.php';

header('Content-Type: application/json');
$db = new Koneksi();
$koneksi = $db->getKoneksi();

$id_user = isset($_GET['user_id']) ? $_GET['user_id'] : '';

if (!empty($id_user)) {
    // Query yang menggabungkan tabel users dan detail_siswa
    $sql = "SELECT u.id, u.nama, u.email, ds.nisn, ds.telp, k.nama_kelas 
            FROM users u 
            JOIN detail_siswa ds ON u.id = ds.user_id 
            LEFT JOIN kelas k ON ds.id_kelas = k.id_kelas 
            WHERE u.id = ? AND u.role = 'siswa' AND u.is_active = 1";
            
    $stmt = $koneksi->prepare($sql);
    if (!$stmt) {
        http_response_code(500);
        echo json_encode([
            "status" => "error",
            "message" => "Error prepare statement: " . $koneksi->error
        ]);
        exit;
    }

    $stmt->bind_param("i", $id_user);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $siswa = $result->fetch_assoc();
        
        // Menyusun data respons
        $response = [
            "status" => "success",
            "message" => "Data siswa ditemukan",
            "data" => [
                "id" => $siswa['id'],
                "nama" => $siswa['nama'],
                "email" => $siswa['email'],
                "nisn" => $siswa['nisn'],
                "telp" => $siswa['telp'],
                "kelas" => $siswa['nama_kelas']
            ]
        ];
        
        http_response_code(200);
    } else {
        http_response_code(404);
        $response = [
            "status" => "error",
            "message" => "Siswa tidak ditemukan atau tidak aktif"
        ];
    }

    echo json_encode($response);
    $stmt->close();
} else {
    http_response_code(400);
    echo json_encode([
        "status" => "error",
        "message" => "ID User tidak diberikan"
    ]);
}

$db->tutupKoneksi();
?>
