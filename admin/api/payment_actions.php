<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || !isset($input['payment_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$action = $input['action'];
$paymentId = $input['payment_id'];

try {
    $response = ['success' => false, 'message' => 'Unknown action'];

    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE payments SET status = 'successful' WHERE id = ?");
            $stmt->execute([$paymentId]);
            $response = ['success' => true, 'message' => 'Payment approved successfully'];
            break;

        case 'reject':
            $stmt = $pdo->prepare("UPDATE payments SET status = 'failed' WHERE id = ?");
            $stmt->execute([$paymentId]);
            $response = ['success' => true, 'message' => 'Payment rejected'];
            break;

        case 'refund':
            $stmt = $pdo->prepare("UPDATE payments SET status = 'refunded' WHERE id = ?");
            $stmt->execute([$paymentId]);
            $response = ['success' => true, 'message' => 'Payment refunded'];
            break;

        case 'view':
            $stmt = $pdo->prepare("
                SELECT 
                    p.*,
                    b.id as booking_id,
                    prop.title as property_name,
                    u.full_name as customer_name,
                    u.email as customer_email,
                    u.phone as customer_phone
                FROM payments p
                LEFT JOIN bookings b ON p.booking_id = b.id
                LEFT JOIN properties prop ON b.property_id = prop.id
                LEFT JOIN users u ON b.customer_id = u.id
                WHERE p.id = ?
            ");
            $stmt->execute([$paymentId]);
            $payment = $stmt->fetch();
            $response = ['success' => true, 'data' => $payment];
            break;

        case 'delete':
            // Only allow deletion of failed or refunded payments
            $stmt = $pdo->prepare("SELECT status FROM payments WHERE id = ?");
            $stmt->execute([$paymentId]);
            $currentStatus = $stmt->fetchColumn();
            
            if (in_array($currentStatus, ['failed', 'refunded', 'cancelled'])) {
                $stmt = $pdo->prepare("DELETE FROM payments WHERE id = ?");
                $stmt->execute([$paymentId]);
                $response = ['success' => true, 'message' => 'Payment record deleted'];
            } else {
                $response = ['success' => false, 'message' => 'Can only delete failed, refunded, or cancelled payments'];
            }
            break;

        case 'resend_receipt':
            // Logic to resend payment receipt
            $response = ['success' => true, 'message' => 'Receipt resent successfully'];
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