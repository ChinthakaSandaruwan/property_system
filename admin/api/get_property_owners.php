<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in as admin (you can add authentication here)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// You can add admin authentication check here
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
//     exit;
// }

try {
    // Get all users with user_type = 'owner' and status = 'approved'
    $stmt = $pdo->prepare("
        SELECT 
            id, 
            full_name, 
            email,
            phone,
            status
        FROM users 
        WHERE user_type = 'owner' AND status IN ('approved', 'active')
        ORDER BY full_name ASC
    ");
    $stmt->execute();
    $owners = $stmt->fetchAll();
    
    echo json_encode([
        'success' => true,
        'data' => $owners,
        'message' => 'Property owners loaded successfully'
    ]);
    
} catch (PDOException $e) {
    error_log("Get property owners error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred'
    ]);
}
?>