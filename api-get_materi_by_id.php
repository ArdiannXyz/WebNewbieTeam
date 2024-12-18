<?php
declare(strict_types=1);

// Define debug mode
define('DEBUG_MODE', true); // Set to false in production

// Set headers
header('Content-Type: application/json');
http_response_code(200); // Default response code

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
    if (!isset($_GET['id_materi']) || empty($_GET['id_materi'])) {
        throw new Exception('ID Materi tidak valid');
    }

    // Ambil ID materi dari parameter dan validasi
    $id_materi = filter_var($_GET['id_materi'], FILTER_VALIDATE_INT);
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
                m.id_materi,
                m.judul_materi,
                m.deskripsi,
                m.file_materi,
                m.is_active,
                m.created_at,
                m.updated_at,
                k.id_kelas,
                k.nama_kelas,
                mp.id_mapel,
                mp.nama_mapel,
                u.nama as nama_guru
              FROM 
                materi m
              JOIN 
                kelas k ON m.id_kelas = k.id_kelas
              JOIN 
                mapel mp ON m.id_mapel = mp.id_mapel
              JOIN 
                users u ON m.id_guru = u.id
              WHERE 
                m.id_materi = ? 
                AND m.is_active = TRUE";

    // Persiapkan statement
    $stmt = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        throw new Exception('Gagal mempersiapkan query: ' . mysqli_error($koneksi));
    }

    // Bind parameter
    mysqli_stmt_bind_param($stmt, "i", $id_materi);
    
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
        
        // Siapkan data materi
        $materiData = array(
            "id_materi" => (int)$row['id_materi'],
            "judul_materi" => $row['judul_materi'],
            "deskripsi" => $row['deskripsi'],
            "file_materi" => $row['file_materi'],
            "is_active" => (bool)$row['is_active'],
            "created_at" => $row['created_at'],
            "updated_at" => $row['updated_at'],
            "kelas" => array(
                "id_kelas" => (int)$row['id_kelas'],
                "nama_kelas" => $row['nama_kelas']
            ),
            "mapel" => array(
                "id_mapel" => (int)$row['id_mapel'],
                "nama_mapel" => $row['nama_mapel']
            ),
            "guru" => array(
                "nama" => $row['nama_guru']
            )
        );
        
        // Set response sukses
        $response['success'] = true;
        $response['message'] = 'Berhasil mengambil data materi';
        $response['data'] = $materiData;
    } else {
        throw new Exception('Materi tidak ditemukan atau tidak aktif');
    }
    
} catch (Exception $e) {
    // Set HTTP response code
    http_response_code(404);
    
    // Tangkap semua error dan masukkan ke response
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    $response['error'] = DEBUG_MODE ? $e->getTrace() : null;
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
