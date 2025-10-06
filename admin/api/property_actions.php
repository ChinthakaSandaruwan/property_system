<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action']) || !isset($input['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$action = $input['action'];
$propertyId = $input['property_id'];

try {
    $response = ['success' => false, 'message' => 'Unknown action'];

    switch ($action) {
        case 'approve':
            $stmt = $pdo->prepare("UPDATE properties SET status = 'approved' WHERE id = ?");
            $stmt->execute([$propertyId]);
            $response = ['success' => true, 'message' => 'Property approved successfully'];
            break;

        case 'reject':
            $stmt = $pdo->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$propertyId]);
            $response = ['success' => true, 'message' => 'Property rejected'];
            break;

        case 'suspend':
            $stmt = $pdo->prepare("UPDATE properties SET status = 'rejected' WHERE id = ?");
            $stmt->execute([$propertyId]);
            $response = ['success' => true, 'message' => 'Property suspended (marked as rejected)'];
            break;

        case 'activate':
            $stmt = $pdo->prepare("UPDATE properties SET status = 'approved' WHERE id = ?");
            $stmt->execute([$propertyId]);
            $response = ['success' => true, 'message' => 'Property activated'];
            break;

        case 'view':
            $stmt = $pdo->prepare("
                SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                FROM properties p 
                JOIN users u ON p.owner_id = u.id 
                WHERE p.id = ?
            ");
            $stmt->execute([$propertyId]);
            $property = $stmt->fetch();
            if ($property) {
                // Decode JSON fields
                $property['amenities'] = json_decode($property['amenities'] ?? '[]', true);
                $property['images'] = json_decode($property['images'] ?? '[]', true);
                $response = ['success' => true, 'data' => $property];
            } else {
                $response = ['success' => false, 'message' => 'Property not found'];
            }
            break;

        case 'delete':
            // Check if property has active bookings before deleting
            $stmt = $pdo->prepare("SELECT COUNT(*) as active_bookings FROM bookings WHERE property_id = ? AND status IN ('confirmed', 'pending')");
            $stmt->execute([$propertyId]);
            $bookings = $stmt->fetch();
            
            if ($bookings['active_bookings'] > 0) {
                $response = ['success' => false, 'message' => 'Cannot delete property with active bookings'];
            } else {
                $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ?");
                $stmt->execute([$propertyId]);
                $response = ['success' => true, 'message' => 'Property deleted successfully'];
            }
            break;
            
        case 'mark-available':
            $stmt = $pdo->prepare("UPDATE properties SET is_available = 1 WHERE id = ?");
            $stmt->execute([$propertyId]);
            $response = ['success' => true, 'message' => 'Property marked as available'];
            break;
            
        case 'mark-unavailable':
            $stmt = $pdo->prepare("UPDATE properties SET is_available = 0 WHERE id = ?");
            $stmt->execute([$propertyId]);
            $response = ['success' => true, 'message' => 'Property marked as unavailable'];
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