<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Check if user is logged in as admin (add your authentication logic here)
// For now, we'll proceed without authentication check

try {
    // Initialize response
    $response = [
        'success' => false,
        'data' => [
            'total_properties' => 0,
            'total_bookings' => 0,
            'total_users' => 0,
            'monthly_revenue' => 0
        ]
    ];

    // Get total properties
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE status = 'approved'");
    $stmt->execute();
    $result = $stmt->fetch();
    $response['data']['total_properties'] = $result['count'];

    // Get active bookings
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE status IN ('approved', 'active')");
    $stmt->execute();
    $result = $stmt->fetch();
    $response['data']['total_bookings'] = $result['count'];

    // Get total users (customers + owners)
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'active'");
    $stmt->execute();
    $result = $stmt->fetch();
    $response['data']['total_users'] = $result['count'];

    // Get monthly revenue (current month)
    $currentMonth = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(amount), 0) as revenue 
        FROM payments 
        WHERE status = 'successful' 
        AND DATE_FORMAT(created_at, '%Y-%m') = ?
    ");
    $stmt->execute([$currentMonth]);
    $result = $stmt->fetch();
    $response['data']['monthly_revenue'] = number_format($result['revenue'], 2);

    $response['success'] = true;

} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>