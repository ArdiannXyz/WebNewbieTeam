<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

// Initialize response array
$response = [
    "success" => false,
    "message" => "",
    "data" => null,
    "error" => null
];

try {
    // Validate email
    $email = $_POST['email'] ?? '';
    
    if (empty($email)) {
        throw new Exception('Email is required');
    }

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Create database connection
    $koneksi = new Koneksi();
    $conn = $koneksi->getKoneksi();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Prepare query to check email and get minimal user info
    $query = $conn->prepare("
        SELECT 
            id,
            role,
            is_active
        FROM users 
        WHERE email = ?
        LIMIT 1
    ");

    if (!$query) {
        throw new Exception('Query preparation failed');
    }

    $query->bind_param("s", $email);
    
    if (!$query->execute()) {
        throw new Exception('Query execution failed');
    }
    
    $result = $query->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Check if user is active
        if (!$user['is_active']) {
            $response['success'] = false;
            $response['message'] = 'Account is inactive';
            $response['data'] = [
                'status' => 'inactive',
                'role' => $user['role']
            ];
        } else {
            $response['success'] = true;
            $response['message'] = 'Email found';
            $response['data'] = [
                'status' => 'active',
                'role' => $user['role']
            ];
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Email not found';
        $response['data'] = [
            'status' => 'not_found'
        ];
    }

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Only include error details in development environment
    if (getenv('ENVIRONMENT') === 'development') {
        $response['error'] = $e->getTrace();
    }

} finally {
    // Clean up resources
    if (isset($query)) {
        $query->close();
    }
    
    if (isset($koneksi)) {
        $koneksi->tutupKoneksi();
    }

    // Set appropriate HTTP status code
    if (!$response['success']) {
        if ($response['message'] === 'Email not found') {
            http_response_code(404);
        } else if (in_array($response['message'], ['Email is required', 'Invalid email format'])) {
            http_response_code(400);
        } else {
            http_response_code(500);
        }
    }

    // Return JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
?>
