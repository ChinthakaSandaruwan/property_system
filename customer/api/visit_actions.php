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
            $visit_id = $input['visit_id'] ?? 0;
            if (!$visit_id) {
                echo json_encode(['success' => false, 'message' => 'Visit ID is required']);
                exit();
            }
            
            // Verify visit belongs to customer and is scheduled
            $checkQuery = "SELECT status, visit_date, visit_time FROM visits WHERE id = ? AND customer_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$visit_id, $customer_id]);
            $visit = $checkStmt->fetch();
            
            if (!$visit) {
                echo json_encode(['success' => false, 'message' => 'Visit not found']);
                exit();
            }
            
            if ($visit['status'] !== 'scheduled') {
                echo json_encode(['success' => false, 'message' => 'Only scheduled visits can be cancelled']);
                exit();
            }
            
            // Check if visit is in the future
            $visit_datetime = $visit['visit_date'] . ' ' . $visit['visit_time'];
            if (strtotime($visit_datetime) <= time()) {
                echo json_encode(['success' => false, 'message' => 'Cannot cancel past visits']);
                exit();
            }
            
            // Update visit status to cancelled
            $updateQuery = "UPDATE visits SET status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$visit_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Visit cancelled successfully'
            ]);
            break;
            
        case 'reschedule':
            $visit_id = $input['visit_id'] ?? 0;
            $new_date = $input['visit_date'] ?? '';
            $new_time = $input['visit_time'] ?? '';
            
            if (!$visit_id || !$new_date || !$new_time) {
                echo json_encode(['success' => false, 'message' => 'Visit ID, date, and time are required']);
                exit();
            }
            
            // Verify visit belongs to customer and is scheduled
            $checkQuery = "SELECT status, property_id FROM visits WHERE id = ? AND customer_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$visit_id, $customer_id]);
            $visit = $checkStmt->fetch();
            
            if (!$visit) {
                echo json_encode(['success' => false, 'message' => 'Visit not found']);
                exit();
            }
            
            if ($visit['status'] !== 'scheduled') {
                echo json_encode(['success' => false, 'message' => 'Only scheduled visits can be rescheduled']);
                exit();
            }
            
            // Validate new date/time is in the future
            $new_datetime = $new_date . ' ' . $new_time;
            if (strtotime($new_datetime) <= time()) {
                echo json_encode(['success' => false, 'message' => 'Visit must be scheduled for a future date and time']);
                exit();
            }
            
            // Check for conflicts with existing visits for the same property
            $conflictQuery = "SELECT id FROM visits 
                             WHERE property_id = ? 
                             AND visit_date = ? 
                             AND visit_time = ? 
                             AND status = 'scheduled' 
                             AND id != ?";
            $conflictStmt = $pdo->prepare($conflictQuery);
            $conflictStmt->execute([$visit['property_id'], $new_date, $new_time, $visit_id]);
            
            if ($conflictStmt->fetch()) {
                echo json_encode(['success' => false, 'message' => 'This time slot is already booked']);
                exit();
            }
            
            // Update visit with new date and time
            $updateQuery = "UPDATE visits SET visit_date = ?, visit_time = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$new_date, $new_time, $visit_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Visit rescheduled successfully',
                'new_date' => $new_date,
                'new_time' => $new_time
            ]);
            break;
            
        case 'complete':
            $visit_id = $input['visit_id'] ?? 0;
            $feedback = $input['feedback'] ?? '';
            $rating = $input['rating'] ?? 0;
            
            if (!$visit_id) {
                echo json_encode(['success' => false, 'message' => 'Visit ID is required']);
                exit();
            }
            
            // Verify visit belongs to customer
            $checkQuery = "SELECT status, visit_date, visit_time FROM visits WHERE id = ? AND customer_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$visit_id, $customer_id]);
            $visit = $checkStmt->fetch();
            
            if (!$visit) {
                echo json_encode(['success' => false, 'message' => 'Visit not found']);
                exit();
            }
            
            // Update visit status to completed with feedback
            $updateQuery = "UPDATE visits SET status = 'completed', feedback = ?, rating = ?, updated_at = NOW() WHERE id = ?";
            $updateStmt = $pdo->prepare($updateQuery);
            $updateStmt->execute([$feedback, $rating, $visit_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Visit marked as completed with feedback'
            ]);
            break;
            
        case 'get_available_times':
            $property_id = $input['property_id'] ?? 0;
            $date = $input['date'] ?? '';
            
            if (!$property_id || !$date) {
                echo json_encode(['success' => false, 'message' => 'Property ID and date are required']);
                exit();
            }
            
            // Get booked time slots for this property on this date
            $bookedQuery = "SELECT visit_time FROM visits 
                           WHERE property_id = ? 
                           AND visit_date = ? 
                           AND status = 'scheduled'";
            $bookedStmt = $pdo->prepare($bookedQuery);
            $bookedStmt->execute([$property_id, $date]);
            $booked_times = $bookedStmt->fetchAll(PDO::FETCH_COLUMN);
            
            // Generate available time slots (9 AM to 6 PM, every hour)
            $available_times = [];
            for ($hour = 9; $hour <= 18; $hour++) {
                $time = sprintf('%02d:00:00', $hour);
                if (!in_array($time, $booked_times)) {
                    $available_times[] = $time;
                }
            }
            
            echo json_encode([
                'success' => true, 
                'available_times' => $available_times
            ]);
            break;
            
        case 'get_visit':
            $visit_id = $input['visit_id'] ?? 0;
            if (!$visit_id) {
                echo json_encode(['success' => false, 'message' => 'Visit ID is required']);
                exit();
            }
            
            $query = "SELECT v.*, p.title as property_title, p.location, p.images, 
                            p.type as property_type, p.address, p.bedrooms, p.bathrooms,
                            o.name as owner_name, o.phone as owner_phone, o.email as owner_email
                     FROM visits v
                     JOIN properties p ON v.property_id = p.id
                     JOIN owners o ON p.owner_id = o.id
                     WHERE v.id = ? AND v.customer_id = ?";
            
            $stmt = $pdo->prepare($query);
            $stmt->execute([$visit_id, $customer_id]);
            $visit = $stmt->fetch();
            
            if ($visit) {
                echo json_encode([
                    'success' => true, 
                    'visit' => $visit
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Visit not found']);
            }
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>