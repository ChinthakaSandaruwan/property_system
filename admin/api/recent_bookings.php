<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

try {
    // Get recent bookings with property and user details
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.status,
            b.created_at,
            p.title as property_name,
            u.full_name as customer_name,
            b.start_date as check_in_date,
            b.end_date as check_out_date,
            b.total_amount
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        JOIN users u ON b.customer_id = u.id
        ORDER BY b.created_at DESC
        LIMIT 10
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();

    // Format the data
    $formattedBookings = [];
    foreach ($bookings as $booking) {
        $formattedBookings[] = [
            'id' => $booking['id'],
            'property_name' => $booking['property_name'],
            'customer_name' => $booking['customer_name'],
            'status' => ucfirst($booking['status']),
            'created_date' => date('M j, Y', strtotime($booking['created_at'])),
            'check_in' => date('M j', strtotime($booking['check_in_date'])),
            'check_out' => date('M j', strtotime($booking['check_out_date'])),
            'amount' => number_format($booking['total_amount'], 2)
        ];
    }

    $response = [
        'success' => true,
        'data' => $formattedBookings
    ];

} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => []
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => []
    ];
}

echo json_encode($response);
?>