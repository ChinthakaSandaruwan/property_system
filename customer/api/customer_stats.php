<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';

$customer_id = $_GET['customer_id'] ?? 0;

try {
    // Get wishlist count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $wishlist_count = $stmt->fetchColumn();

    // Get active bookings count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status IN ('pending', 'approved', 'active')");
    $stmt->execute([$customer_id]);
    $active_bookings = $stmt->fetchColumn();

    // Get scheduled visits count
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits WHERE customer_id = ? AND status IN ('pending', 'approved')");
    $stmt->execute([$customer_id]);
    $scheduled_visits = $stmt->fetchColumn();

    // Get total amount spent (successful payments)
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(amount), 0) FROM payments WHERE customer_id = ? AND status = 'successful'");
    $stmt->execute([$customer_id]);
    $total_spent = $stmt->fetchColumn();

    // Get notification count (pending visits + booking updates)
    $stmt = $pdo->prepare("
        SELECT 
            (SELECT COUNT(*) FROM property_visits WHERE customer_id = ? AND status = 'pending') +
            (SELECT COUNT(*) FROM bookings WHERE customer_id = ? AND status = 'approved' AND updated_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) as notification_count
    ");
    $stmt->execute([$customer_id, $customer_id]);
    $notifications = $stmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'data' => [
            'wishlist_count' => (int)$wishlist_count,
            'active_bookings' => (int)$active_bookings,
            'scheduled_visits' => (int)$scheduled_visits,
            'total_spent' => number_format($total_spent, 2),
            'notifications' => (int)$notifications
        ]
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => [
            'wishlist_count' => 0,
            'active_bookings' => 0,
            'scheduled_visits' => 0,
            'total_spent' => '0.00',
            'notifications' => 0
        ]
    ]);
}
?>