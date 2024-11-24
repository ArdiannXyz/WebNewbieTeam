<?php
include 'koneksi.php';

// Create an instance of the Koneksi class
$koneksiObj = new Koneksi();
$koneksi = $koneksiObj->getKoneksi();

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!$koneksi) {
    die("Connection failed: " . mysqli_connect_error());
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $tables = ['kelas', 'materi', 'tugas', 'siswa'];
    $response = [];
    $error_flag = false;

    foreach ($tables as $table) {
        $sql = "SELECT COUNT(*) as total FROM $table";
        $result = mysqli_query($koneksi, $sql);

        if ($result === false) {
            $error_flag = true;
            $response['errors'][] = [
                "table" => $table,
                "message" => "SQL Error: " . mysqli_error($koneksi)
            ];
            continue;
        }

        $row = mysqli_fetch_assoc($result);
        $response['data'][$table] = [
            "table_name" => $table,
            "total_records" => $row['total']
        ];
    }

    if ($error_flag) {
        $response['status'] = 'error';
    } else {
        $response['status'] = 'success';
    }

    echo json_encode($response);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "Metode tidak didukung"
    ]);
}

// Use the method from the Koneksi class to close the connection
$koneksiObj->tutupKoneksi();
?>