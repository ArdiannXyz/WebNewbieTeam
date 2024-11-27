<?php
// File: api_materi_by_kelas.php

require_once 'koneksi.php'; // File koneksi ke database

// Cek apakah parameter id_kelas dikirim
if (!isset($_GET['id_kelas']) || empty($_GET['id_kelas'])) {
    echo json_encode([
        "success" => false,
        "message" => "Parameter id_kelas tidak ditemukan atau kosong"
    ]);
    exit;
}

$id_kelas = $_GET['id_kelas'];

// Buat koneksi database
$db = new Koneksi();
$conn = $db->getKoneksi();

try {
    // Query untuk mengambil data materi berdasarkan id_kelas
    $sql = "SELECT id_tugas, judul_tugas, jenis_materi, deskripsi, id_kelas, tanggal_dibuat, deadline, video_url
            FROM materi 
            WHERE id_kelas = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $id_kelas);
    $stmt->execute();
    $result = $stmt->get_result();

    $materiList = [];
    while ($row = $result->fetch_assoc()) {
        $materiList[] = $row;
    }

    // Jika data ditemukan, kirim response JSON
    if (count($materiList) > 0) {
        echo json_encode([
            "success" => true,
            "materi" => $materiList
        ]);
    } else {
        echo json_encode([
            "success" => false,
            "message" => "Tidak ada data materi untuk kelas ini"
        ]);
    }
} catch (Exception $e) {
    // Tangkap kesalahan dan kirim pesan error
    echo json_encode([
        "success" => false,
        "message" => "Terjadi kesalahan: " . $e->getMessage()
    ]);
}
?>
