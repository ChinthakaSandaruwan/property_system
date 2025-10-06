<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'User not authenticated'
        ]);
        exit;
    }

    // Get user information
    $user_id = $_SESSION['user_id'];
    $stmt = $pdo->prepare("SELECT id, full_name, email, phone, user_type, created_at FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    if (!$user) {
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'User not found'
        ]);
        exit;
    }

    // Return user info
    echo json_encode([
        'success' => true,
        'data' => [
            'id' => $user['id'],
            'name' => $user['full_name'],
            'email' => $user['email'],
            'phone' => $user['phone'],
            'user_type' => $user['user_type'],
            'joined' => date('M j, Y', strtotime($user['created_at']))
        ]
    ]);

} catch (Exception $e) {
    error_log('User Info API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>