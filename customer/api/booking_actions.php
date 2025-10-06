<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'cancel':
            $booking_id = $input['booking_id'] ?? 0;
            if (!$booking_id) {
                echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
                exit();
            }
            
            // Verify booking belongs to customer and is pending
            $checkQuery = "SELECT status FROM bookings WHERE id = ? AND customer_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$booking_id, $customer_id]);
            $booking = $checkStmt->fetch();
            
            if (!$booking) {
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                exit();
            }
            
            if ($booking['status'] !== 'pending') {
                echo json_encode(['success' => false, 'message' => 'Only pending bookings can be cancelled']);
                exit();
            }
            
            // Update booking status to cancelled
            $updateQuery = "UPDATE bookings SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$booking_id]);
            
            // Log the cancellation
            $logQuery = "INSERT INTO booking_logs (booking_id, action, description, created_at) 
                        VALUES (?, 'cancelled', 'Booking cancelled by customer', NOW())";
            $logStmt = $pdo->prepare($logQuery);
            $logStmt->execute([$booking_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Booking cancelled successfully'
            ]);
            break;
            
        case 'end_rental':
            $booking_id = $input['booking_id'] ?? 0;
            if (!$booking_id) {
                echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
                exit();
            }
            
            // Verify booking belongs to customer and is active
            $checkQuery = "SELECT status FROM bookings WHERE id = ? AND customer_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$booking_id, $customer_id]);
            $booking = $checkStmt->fetch();
            
            if (!$booking) {
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                exit();
            }
            
            if ($booking['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Only active rentals can be ended']);
                exit();
            }
            
            // Update booking status to completed and set end date
            $updateQuery = "UPDATE bookings SET status = 'completed', end_date = CURDATE(), updated_at = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$booking_id]);
            
            // Update property status back to active
            $updatePropertyQuery = "UPDATE properties p 
                                  JOIN bookings b ON p.id = b.property_id 
                                  SET p.status = 'active' 
                                  WHERE b.id = ?";
            $updatePropertyStmt = $pdo->prepare($updatePropertyQuery);
            $updatePropertyStmt->execute([$booking_id]);
            
            // Log the rental end
            $logQuery = "INSERT INTO booking_logs (booking_id, action, description, created_at) 
                        VALUES (?, 'completed', 'Rental ended by customer', NOW())";
            $logStmt = $pdo->prepare($logQuery);
            $logStmt->execute([$booking_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Rental ended successfully'
            ]);
            break;
            
        case 'get_booking':
            $booking_id = $input['booking_id'] ?? 0;
            if (!$booking_id) {
                echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
                exit();
            }
            
            $query = "SELECT b.*, p.title as property_title, p.location, p.images, 
                            p.type as property_type, p.address, p.bedrooms, p.bathrooms,
                            o.name as owner_name, o.phone as owner_phone, o.email as owner_email
                     FROM bookings b
                     JOIN properties p ON b.property_id = p.id
                     JOIN owners o ON p.owner_id = o.id
                     WHERE b.id = ? AND b.customer_id = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$booking_id, $customer_id]);
            $booking = $stmt->fetch();
            
            if ($booking) {
                echo json_encode([
                    'success' => true, 
                    'booking' => $booking
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
            }
            break;
            
        case 'extend_rental':
            $booking_id = $input['booking_id'] ?? 0;
            $months = $input['months'] ?? 1;
            
            if (!$booking_id) {
                echo json_encode(['success' => false, 'message' => 'Booking ID is required']);
                exit();
            }
            
            // Verify booking belongs to customer and is active
            $checkQuery = "SELECT status, end_date FROM bookings WHERE id = ? AND customer_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$booking_id, $customer_id]);
            $booking = $checkStmt->fetch();
            
            if (!$booking) {
                echo json_encode(['success' => false, 'message' => 'Booking not found']);
                exit();
            }
            
            if ($booking['status'] !== 'active') {
                echo json_encode(['success' => false, 'message' => 'Only active rentals can be extended']);
                exit();
            }
            
            // Calculate new end date
            $current_end = $booking['end_date'];
            $new_end_date = date('Y-m-d', strtotime($current_end . " +{$months} months"));
            
            // Update booking end date
            $updateQuery = "UPDATE bookings SET end_date = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$new_end_date, $booking_id]);
            
            // Log the extension
            $logQuery = "INSERT INTO booking_logs (booking_id, action, description, created_at) 
                        VALUES (?, 'extended', 'Rental extended by {$months} months', NOW())";
            $logStmt = $pdo->prepare($logQuery);
            $logStmt->execute([$booking_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => "Rental extended by {$months} months",
                'new_end_date' => $new_end_date
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>