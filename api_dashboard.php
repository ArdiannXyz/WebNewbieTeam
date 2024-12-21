<?php
declare(strict_types=1);

include 'koneksi.php';

// Set headers for JSON response
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET');

// Response handler function
function sendResponse($status, $message = '', $data = null, $errors = null, $httpCode = 200) {
    http_response_code($httpCode);
    $response = ['status' => $status];
    
    if (!empty($message)) $response['message'] = $message;
    if ($data !== null) $response['data'] = $data;
    if ($errors !== null) $response['errors'] = $errors;
    
    echo json_encode($response);
    exit;
}

try {
    $database = new Koneksi();
    $koneksi = $database->getKoneksi();

    if (!$koneksi) {
        throw new Exception('Koneksi database gagal');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        sendResponse('gagal', 'Metode tidak diizinkan', null, null, 405);
    }

    // Define queries for each metric we want to count
    $queries = [
        'users' => [
            'query' => "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN role = 'admin' THEN 1 ELSE 0 END) as total_admin,
                SUM(CASE WHEN role = 'guru' THEN 1 ELSE 0 END) as total_guru,
                SUM(CASE WHEN role = 'siswa' THEN 1 ELSE 0 END) as total_siswa,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_users
            FROM users",
            'error_msg' => 'Error counting users'
        ],
        'kelas' => [
            'query' => "SELECT COUNT(*) as total FROM kelas",
            'error_msg' => 'Error counting kelas'
        ],
        'mapel' => [
            'query' => "SELECT COUNT(*) as total FROM mapel",
            'error_msg' => 'Error counting mapel'
        ],
        'materi' => [
            'query' => "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_materi
            FROM materi",
            'error_msg' => 'Error counting materi'
        ],
        'tugas' => [
            'query' => "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_tugas
            FROM tugas",
            'error_msg' => 'Error counting tugas'
        ],
        'pengumpulan' => [
            'query' => "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'tepat_waktu' THEN 1 ELSE 0 END) as tepat_waktu,
                SUM(CASE WHEN status = 'terlambat' THEN 1 ELSE 0 END) as terlambat,
                SUM(CASE WHEN status = 'belum_mengumpulkan' THEN 1 ELSE 0 END) as belum_mengumpulkan
            FROM pengumpulan",
            'error_msg' => 'Error counting pengumpulan'
        ]
    ];

    $statistics = [];
    $errors = [];

    foreach ($queries as $key => $queryData) {
        $result = $koneksi->query($queryData['query']);
        
        if ($result === false) {
            $errors[] = [
                'table' => $key,
                'message' => $queryData['error_msg'],
                'error' => $koneksi->error
            ];
            continue;
        }

        $row = $result->fetch_assoc();
        $statistics[$key] = $row;
        $result->free();
    }

    // Additional calculations if needed
    if (isset($statistics['users'])) {
        $statistics['users']['percentage_active'] = 
            ($statistics['users']['total'] > 0) 
            ? round(($statistics['users']['active_users'] / $statistics['users']['total']) * 100, 2)
            : 0;
    }

    if (isset($statistics['pengumpulan'])) {
        $total_submissions = $statistics['pengumpulan']['total'];
        if ($total_submissions > 0) {
            $statistics['pengumpulan']['persentase'] = [
                'tepat_waktu' => round(($statistics['pengumpulan']['tepat_waktu'] / $total_submissions) * 100, 2),
                'terlambat' => round(($statistics['pengumpulan']['terlambat'] / $total_submissions) * 100, 2),
                'belum_mengumpulkan' => round(($statistics['pengumpulan']['belum_mengumpulkan'] / $total_submissions) * 100, 2)
            ];
        }
    }

    if (!empty($errors)) {
        sendResponse(
            'partial',
            'Beberapa data berhasil diambil dengan error',
            $statistics,
            $errors,
            207
        );
    } else {
        sendResponse(
            'sukses',
            'Data statistik berhasil diambil',
            $statistics
        );
    }

} catch (Exception $e) {
    sendResponse('gagal', $e->getMessage(), null, null, 500);
} finally {
    if (isset($database)) {
        $database->tutupKoneksi();
    }
}

