<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

$customer_id = $_GET['customer_id'] ?? null;

if (!$customer_id) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Customer ID is required']);
    exit;
}

try {
    // Get upcoming visits
    $query = "SELECT pv.id, pv.requested_date, pv.status, pv.customer_notes,
                     p.title as property_name, p.id as property_id,
                     CONCAT(p.address, ', ', p.city) as property_location,
                     u.full_name as owner_name
              FROM property_visits pv
              JOIN properties p ON pv.property_id = p.id
              JOIN users u ON pv.owner_id = u.id
              WHERE pv.customer_id = ? 
                AND pv.status IN ('pending', 'approved')
                AND pv.requested_date > NOW()
              ORDER BY pv.requested_date ASC
              LIMIT 5";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$customer_id]);
    $visits = $stmt->fetchAll();
    
    // Format the data
    foreach ($visits as &$visit) {
        $visit['visit_date'] = date('M j, Y g:i A', strtotime($visit['requested_date']));
        $visit['status_class'] = strtolower($visit['status']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $visits
    ]);

} catch (Exception $e) {
    error_log("Upcoming visits error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>