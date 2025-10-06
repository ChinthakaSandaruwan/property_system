<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 0;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID is required']);
    exit;
}

try {
    $response = [
        'success' => false,
        'data' => [
            'total_properties' => 0,
            'active_bookings' => 0,
            'pending_visits' => 0,
            'monthly_earnings' => 0,
            'notifications' => 0
        ]
    ];

    // Get total properties for this owner
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ?");
    $stmt->execute([$owner_id]);
    $result = $stmt->fetch();
    $response['data']['total_properties'] = $result['count'];

    // Get active bookings for this owner's properties
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM bookings b 
        JOIN properties p ON b.property_id = p.id 
        WHERE p.owner_id = ? AND b.status IN ('approved', 'active')
    ");
    $stmt->execute([$owner_id]);
    $result = $stmt->fetch();
    $response['data']['active_bookings'] = $result['count'];

    // Get pending visits for this owner's properties
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM property_visits pv 
        JOIN properties p ON pv.property_id = p.id 
        WHERE p.owner_id = ? AND pv.status = 'pending'
    ");
    $stmt->execute([$owner_id]);
    $result = $stmt->fetch();
    $response['data']['pending_visits'] = $result['count'];

    // Get monthly earnings (current month)
    $currentMonth = date('Y-m');
    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(p.owner_payout), 0) as earnings 
        FROM payments p 
        WHERE p.owner_id = ? 
        AND p.status = 'successful' 
        AND DATE_FORMAT(p.created_at, '%Y-%m') = ?
    ");
    $stmt->execute([$owner_id, $currentMonth]);
    $result = $stmt->fetch();
    $response['data']['monthly_earnings'] = number_format($result['earnings'], 2);

    // Get notification count (pending visits + new bookings)
    $response['data']['notifications'] = $response['data']['pending_visits'];

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