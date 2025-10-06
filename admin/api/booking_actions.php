<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || !isset($input['booking_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$action = $input['action'];
$bookingId = $input['booking_id'];

try {
    $response = ['success' => false, 'message' => 'Unknown action'];

    switch ($action) {
        case 'confirm':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'confirmed' WHERE id = ?");
            $stmt->execute([$bookingId]);
            $response = ['success' => true, 'message' => 'Booking confirmed successfully'];
            break;

        case 'cancel':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = ?");
            $stmt->execute([$bookingId]);
            $response = ['success' => true, 'message' => 'Booking cancelled'];
            break;

        case 'activate':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'active' WHERE id = ?");
            $stmt->execute([$bookingId]);
            $response = ['success' => true, 'message' => 'Booking activated (checked in)'];
            break;

        case 'complete':
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'completed' WHERE id = ?");
            $stmt->execute([$bookingId]);
            $response = ['success' => true, 'message' => 'Booking completed (checked out)'];
            break;

        case 'view':
            $stmt = $pdo->prepare("
                SELECT 
                    b.*,
                    p.title as property_name,
                    p.address as property_address,
                    u.full_name as customer_name,
                    u.email as customer_email,
                    u.phone as customer_phone
                FROM bookings b
                JOIN properties p ON b.property_id = p.id
                JOIN users u ON b.customer_id = u.id
                WHERE b.id = ?
            ");
            $stmt->execute([$bookingId]);
            $booking = $stmt->fetch();
            $response = ['success' => true, 'data' => $booking];
            break;

        case 'delete':
            // Only allow deletion of cancelled bookings
            $stmt = $pdo->prepare("SELECT status FROM bookings WHERE id = ?");
            $stmt->execute([$bookingId]);
            $currentStatus = $stmt->fetchColumn();
            
            if ($currentStatus === 'cancelled' || $currentStatus === 'completed') {
                $stmt = $pdo->prepare("DELETE FROM bookings WHERE id = ?");
                $stmt->execute([$bookingId]);
                $response = ['success' => true, 'message' => 'Booking deleted'];
            } else {
                $response = ['success' => false, 'message' => 'Can only delete cancelled or completed bookings'];
            }
            break;

        case 'refund':
            // Handle refund logic here
            $stmt = $pdo->prepare("UPDATE bookings SET status = 'refunded' WHERE id = ?");
            $stmt->execute([$bookingId]);
            $response = ['success' => true, 'message' => 'Booking refunded'];
            break;
    }

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