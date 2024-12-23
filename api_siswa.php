<?php
include 'koneksi.php';

header('Content-Type: application/json');
$db = new Koneksi();
$koneksi = $db->getKoneksi();

// Ambil ID User dari parameter GET
$id_user = isset($_GET['id']) ? $_GET['id'] : '';

if (!empty($id_user)) {
    // Query untuk mendapatkan data nisn dari detail_siswa dan nama dari users
    $query = "
        SELECT 
            detail_siswa.nisn, 
            users.nama 
        FROM 
            detail_siswa 
        JOIN 
            users 
        ON 
            detail_siswa.user_id = users.id 
        WHERE 
            users.id = ?
    ";

    // Logging query untuk debugging
    error_log("Query: $query, ID User: $id_user");

    $stmt = $koneksi->prepare($query);
    if (!$stmt) {
        die(json_encode(["success" => false, "message" => "Error prepare statement: " . $koneksi->error]));
    }

    // Bind parameter
    $stmt->bind_param("i", $id_user);

    // Eksekusi query
    $stmt->execute();

    // Ambil hasil
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $data = $result->fetch_assoc();
        $response = [
            "success" => true,
            "message" => "Data siswa ditemukan",
            "data" => $data
        ];
    } else {
        // Logging untuk debugging
        error_log("Data tidak ditemukan untuk ID User: $id_user");
        
        $response = [
            "success" => false,
            "message" => "Siswa tidak ditemukan"
        ];
    }

    // Tutup statement
    $stmt->close();
} else {
    $response = ["success" => false, "message" => "ID User tidak diberikan"];
}

// Tutup koneksi
$koneksi->close();

// Return response sebagai JSON
echo json_encode($response);
?>
