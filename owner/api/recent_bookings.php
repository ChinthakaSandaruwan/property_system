<?php
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.status,
            b.total_amount,
            b.created_at,
            p.title as property_name,
            u.full_name as customer_name
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        JOIN users u ON b.customer_id = u.id
        WHERE p.owner_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$owner_id]);
    $bookings = $stmt->fetchAll();

    $formattedBookings = [];
    foreach ($bookings as $booking) {
        $formattedBookings[] = [
            'id' => $booking['id'],
            'property_name' => $booking['property_name'],
            'customer_name' => $booking['customer_name'],
            'status' => ucfirst($booking['status']),
            'amount' => number_format($booking['total_amount'], 2),
            'created_date' => date('M j, Y', strtotime($booking['created_at']))
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedBookings
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>