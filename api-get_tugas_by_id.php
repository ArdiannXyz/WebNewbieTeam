<?php
header('Content-Type: application/json');
include 'koneksi.php';

// Response array initialization
$response = [
    "success" => false,
    "message" => "",
    "data" => null,
    "error" => null
];

try {
    // Validate input ID tugas
    if (!isset($_GET['id_tugas']) || empty($_GET['id_tugas'])) {
        throw new Exception('ID Tugas is required');
    }

    $id_tugas = filter_var($_GET['id_tugas'], FILTER_VALIDATE_INT);
    if ($id_tugas === false) {
        throw new Exception('ID Tugas must be a number');
    }

    // Create database connection
    $koneksiObj = new Koneksi();
    $koneksi = $koneksiObj->getKoneksi();

    if (!$koneksi) {
        throw new Exception('Database connection failed: ' . mysqli_connect_error());
    }

    // Query to get tugas details based on ID
    $query = "SELECT 
                t.id_tugas,
                t.judul_tugas,
                t.deskripsi,
                t.file_tugas,
                t.deadline,
                t.is_active,
                t.created_at,
                t.updated_at,
                k.id_kelas,
                k.nama_kelas,
                m.id_mapel,
                m.nama_mapel,
                u.id as guru_id,
                u.nama as nama_guru
              FROM 
                tugas t
              JOIN 
                kelas k ON t.id_kelas = k.id_kelas
              JOIN
                mapel m ON t.id_mapel = m.id_mapel
              JOIN
                users u ON t.id_guru = u.id
              WHERE 
                t.id_tugas = ? AND t.is_active = TRUE";

    // Prepare statement
    $stmt = mysqli_prepare($koneksi, $query);
    if (!$stmt) {
        throw new Exception('Failed to prepare query: ' . mysqli_error($koneksi));
    }

    // Bind parameter
    if (!mysqli_stmt_bind_param($stmt, "i", $id_tugas)) {
        throw new Exception('Failed to bind parameter: ' . mysqli_stmt_error($stmt));
    }
    
    // Execute statement
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
    }
    
    // Get result
    $result = mysqli_stmt_get_result($stmt);
    if (!$result) {
        throw new Exception('Failed to get result: ' . mysqli_stmt_error($stmt));
    }
    
    // Check if data exists
    if (mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        
        // Format dates
        $created_at = date('Y-m-d H:i:s', strtotime($row['created_at']));
        $updated_at = date('Y-m-d H:i:s', strtotime($row['updated_at']));
        $deadline = date('Y-m-d H:i:s', strtotime($row['deadline']));
        
        // Get submission statistics
        $stats_query = "SELECT 
                         COUNT(*) as total_submissions,
                         SUM(CASE WHEN status = 'tepat_waktu' THEN 1 ELSE 0 END) as on_time,
                         SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as late,
                         SUM(CASE WHEN status = 'belum_mengumpulkan' THEN 1 ELSE 0 END) as not_submitted
                       FROM pengumpulan
                       WHERE id_tugas = ?";
        
        $stats_stmt = mysqli_prepare($koneksi, $stats_query);
        mysqli_stmt_bind_param($stats_stmt, "i", $id_tugas);
        mysqli_stmt_execute($stats_stmt);
        $stats_result = mysqli_stmt_get_result($stats_stmt);
        $stats = mysqli_fetch_assoc($stats_result);
        
        // Prepare tugas data
        $tugasData = [
            "id_tugas" => (int)$row['id_tugas'],
            "judul_tugas" => $row['judul_tugas'],
            "deskripsi" => $row['deskripsi'],
            "file_tugas" => $row['file_tugas'],
            "deadline" => $deadline,
            "is_active" => (bool)$row['is_active'],
            "created_at" => $created_at,
            "updated_at" => $updated_at,
            "kelas" => [
                "id_kelas" => (int)$row['id_kelas'],
                "nama_kelas" => $row['nama_kelas']
            ],
            "mapel" => [
                "id_mapel" => (int)$row['id_mapel'],
                "nama_mapel" => $row['nama_mapel']
            ],
            "guru" => [
                "id" => (int)$row['guru_id'],
                "nama" => $row['nama_guru']
            ],
            "statistics" => [
                "total_submissions" => (int)$stats['total_submissions'],
                "on_time" => (int)$stats['on_time'],
                "late" => (int)$stats['late'],
                "not_submitted" => (int)$stats['not_submitted']
            ]
        ];
        
        // Set success response
        $response['success'] = true;
        $response['message'] = 'Successfully retrieved tugas data';
        $response['data'] = $tugasData;
    } else {
        throw new Exception('Tugas not found or inactive');
    }
    
} catch (Exception $e) {
    // Catch all errors and add to response
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    if (getenv('ENVIRONMENT') === 'development') {
        $response['error'] = $e->getTrace();
    }
} finally {
    // Close statement if exists
    if (isset($stats_stmt) && $stats_stmt) {
        mysqli_stmt_close($stats_stmt);
    }
    if (isset($stmt) && $stmt) {
        mysqli_stmt_close($stmt);
    }
    
    // Close connection if exists
    if (isset($koneksiObj)) {
        $koneksiObj->tutupKoneksi();
    }
    
    // Return response in JSON format
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
?>
