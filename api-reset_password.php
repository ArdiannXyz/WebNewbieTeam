<?php
header('Content-Type: application/json');
require_once 'koneksi.php';

// Initialize response array
$response = [
    "success" => false,
    "message" => "",
    "error" => null
];

try {
    // Get and validate inputs
    $email = $_POST['email'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validate email
    if (empty($email)) {
        throw new Exception('Email is required');
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Invalid email format');
    }

    // Validate password
    if (empty($new_password)) {
        throw new Exception('New password is required');
    }
    if (empty($confirm_password)) {
        throw new Exception('Password confirmation is required');
    }
    if ($new_password !== $confirm_password) {
        throw new Exception('Passwords do not match');
    }

    // Password strength validation
    if (strlen($new_password) < 8) {
        throw new Exception('Password must be at least 8 characters long');
    }
    if (!preg_match('/[A-Z]/', $new_password)) {
        throw new Exception('Password must contain at least one uppercase letter');
    }
    if (!preg_match('/[a-z]/', $new_password)) {
        throw new Exception('Password must contain at least one lowercase letter');
    }
    if (!preg_match('/[0-9]/', $new_password)) {
        throw new Exception('Password must contain at least one number');
    }
    
    // Create database connection
    $koneksi = new Koneksi();
    $conn = $koneksi->getKoneksi();

    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // First check if user exists and is active
    $check_query = $conn->prepare("SELECT id, is_active FROM users WHERE email = ? LIMIT 1");
    if (!$check_query) {
        throw new Exception('Query preparation failed');
    }

    $check_query->bind_param("s", $email);
    if (!$check_query->execute()) {
        throw new Exception('Query execution failed');
    }

    $result = $check_query->get_result();
    if ($result->num_rows === 0) {
        throw new Exception('Email not found');
    }

    $user = $result->fetch_assoc();
    if (!$user['is_active']) {
        throw new Exception('Account is inactive');
    }

    // Hash new password
    $hashed_password = password_hash($new_password, PASSWORD_BCRYPT, ['cost' => 12]);

    // Update password
    $update_query = $conn->prepare("UPDATE users SET password = ?, updated_at = CURRENT_TIMESTAMP WHERE email = ?");
    if (!$update_query) {
        throw new Exception('Update query preparation failed');
    }

    $update_query->bind_param("ss", $hashed_password, $email);
    if (!$update_query->execute()) {
        throw new Exception('Failed to update password');
    }

    if ($update_query->affected_rows === 0) {
        throw new Exception('No changes made to password');
    }

    // Log password change (optional)
    $user_id = $user['id'];
    $log_query = $conn->prepare("INSERT INTO password_change_log (user_id, changed_at, ip_address) VALUES (?, NOW(), ?)");
    if ($log_query) {
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $log_query->bind_param("is", $user_id, $ip_address);
        $log_query->execute();
    }

    $response['success'] = true;
    $response['message'] = 'Password updated successfully';

} catch (Exception $e) {
    $response['success'] = false;
    $response['message'] = $e->getMessage();
    
    // Only include error details in development environment
    if (getenv('ENVIRONMENT') === 'development') {
        $response['error'] = $e->getTrace();
    }

    // Set appropriate HTTP status code
    if (in_array($response['message'], ['Email not found', 'Account is inactive'])) {
        http_response_code(404);
    } else if (strpos($response['message'], 'Password must') !== false || 
               $response['message'] === 'Passwords do not match' ||
               $response['message'] === 'Invalid email format') {
        http_response_code(400);
    } else {
        http_response_code(500);
    }

} finally {
    // Clean up resources
    if (isset($log_query)) {
        $log_query->close();
    }
    if (isset($update_query)) {
        $update_query->close();
    }
    if (isset($check_query)) {
        $check_query->close();
    }
    if (isset($koneksi)) {
        $koneksi->tutupKoneksi();
    }

    // Return JSON response
    echo json_encode($response, JSON_PRETTY_PRINT);
    exit();
}
?>
