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
    // Get featured properties (most visited and recently added)
    $query = "SELECT p.id, p.title, p.rent_amount,
                     CONCAT(p.address, ', ', p.city) as location,
                     p.images, p.bedrooms, p.bathrooms,
                     (SELECT COUNT(*) FROM property_visits pv WHERE pv.property_id = p.id) as visit_count,
                     CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END as in_wishlist
              FROM properties p
              LEFT JOIN wishlists w ON p.id = w.property_id AND w.customer_id = ?
              WHERE p.status = 'approved' AND p.is_available = TRUE
              ORDER BY visit_count DESC, p.created_at DESC
              LIMIT 6";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$customer_id]);
    $properties = $stmt->fetchAll();
    
    // Format the data
    foreach ($properties as &$property) {
        $property['rent_amount'] = number_format($property['rent_amount']);
        $property['images'] = $property['images'] ? json_decode($property['images'], true) : [];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $properties
    ]);

} catch (Exception $e) {
    error_log("Featured properties error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}
?>