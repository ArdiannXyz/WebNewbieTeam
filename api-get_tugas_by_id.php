<?php

//api-get_tugas_by_id.php


header('Content-Type: application/json');
include 'koneksi.php';

// Response array dengan tambahan field error untuk debugging
$response = array(
    "success" => false,
    "message" => "",
    "data" => null,
    "error" => null
);

try {
    // Validasi input ID materi
    if (!isset($_GET['id_tugas']) || empty($_GET['id_tugas'])) {
        throw new Exception('ID Materi tidak valid');
    }

    // Ambil ID materi dari parameter dan validasi
    $id_materi = filter_var($_GET['id_tugas'], FILTER_VALIDATE_INT);
    if ($id_materi === false) {
        throw new Exception('ID Materi harus berupa angka');
    }

    // Buat objek koneksi
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    if (!$koneksi) {
        throw new Exception('Koneksi database gagal: ' . mysqli_connect_error());
    }

    // Query untuk mengambil detail materi berdasarkan ID
    $query = "SELECT 
                m.id_tugas, 
                m.judul_tugas, 
                m.deskripsi as keterangan,  
                m.jenis_materi, 
                m.tanggal_dibuat,
                m.deadline,
                k.nama_kelas,
                k.id_kelas
              FROM 
                materi m
              JOIN 
                kelas k ON m.id_kelas = k.id_kelas
              WHERE 
                m.id_tugas = ?";

    // Persiapkan statement
    $stmt = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        throw new Exception('Gagal mempersiapkan query: ' . mysqli_error($koneksi));
    }

    // Bind parameter
    if (!mysqli_stmt_bind_param($stmt, "i", $id_materi)) {
        throw new Exception('Gagal binding parameter: ' . mysqli_stmt_error($stmt));
    }
    
    // Eksekusi statement
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Gagal eksekusi query: ' . mysqli_stmt_error($stmt));
    }
    
    // Ambil hasil
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception('Gagal mengambil hasil: ' . mysqli_stmt_error($stmt));
    }
    
    // Cek apakah ada data
    if (mysqli_num_rows($result) > 0) {
        // Ambil data
        $row = mysqli_fetch_assoc($result);
        
        // Konversi format tanggal jika perlu
        $tanggal_dibuat = date('Y-m-d H:i:s', strtotime($row['tanggal_dibuat']));
        $deadline = $row['deadline'] ? date('Y-m-d H:i:s', strtotime($row['deadline'])) : null;
        
        // Siapkan data materi
        $materiData = array(
            "id_tugas" => (int)$row['id_tugas'],
            "judul_tugas" => $row['judul_tugas'],
            "keterangan" => $row['keterangan'],
            "jenis_materi" => $row['jenis_materi'],
            "tanggal_dibuat" => $tanggal_dibuat,
            "deadline" => $deadline,
            "nama_kelas" => $row['nama_kelas'],
            "id_kelas" => (int)$row['id_kelas']
        );
        
        // Set response sukses
        $response['success'] = true;
        $response['message'] = 'Berhasil mengambil data materi';
        $response['data'] = $materiData;
    } else {
        throw new Exception('Materi tidak ditemukan');
    }
    
} catch (Exception $e) {
    // Tangkap semua error dan masukkan ke response
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['error'] = $e->getTrace();
} finally {
    // Tutup statement jika ada
    if (isset($stmt) && $stmt) {
        mysqli_stmt_close($stmt);
    }
    
    // Tutup koneksi jika ada
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
    
    // Kembalikan response dalam format JSON
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
?>