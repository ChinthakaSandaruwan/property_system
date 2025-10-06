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
    // Since we don't have a property views tracking table, 
    // we'll return recent properties from wishlist as a substitute
    $query = "SELECT p.id, p.title as property_title, 
                     CONCAT(p.address, ', ', p.city) as location,
                     p.rent_amount, w.created_at,
                     TIMESTAMPDIFF(HOUR, w.created_at, NOW()) as hours_ago
              FROM wishlists w
              JOIN properties p ON w.property_id = p.id
              WHERE w.customer_id = ? AND p.status = 'approved' AND p.is_available = TRUE
              ORDER BY w.created_at DESC
              LIMIT 5";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$customer_id]);
    $views = $stmt->fetchAll();
    
    // Format the time ago
    foreach ($views as &$view) {
        if ($view['hours_ago'] < 1) {
            $view['viewed_time'] = 'Just now';
        } elseif ($view['hours_ago'] < 24) {
            $view['viewed_time'] = $view['hours_ago'] . ' hours ago';
        } else {
            $days = floor($view['hours_ago'] / 24);
            $view['viewed_time'] = $days . ' days ago';
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $views
    ]);

} catch (Exception $e) {
    error_log("Recent views error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>