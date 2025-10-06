<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$action = $input['action'];
$userId = isset($input['user_id']) ? $input['user_id'] : null;

// Validate user_id for actions that require it
$actions_requiring_user_id = ['activate', 'suspend', 'deactivate', 'view', 'delete', 'reset_password', 'edit', 'update'];
if (in_array($action, $actions_requiring_user_id) && !$userId) {
    echo json_encode(['success' => false, 'message' => 'User ID is required for this action']);
    exit;
}

try {
    $response = ['success' => false, 'message' => 'Unknown action'];

    switch ($action) {
        case 'activate':
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE id = ?");
            $stmt->execute([$userId]);
            $response = ['success' => true, 'message' => 'User activated successfully'];
            break;

        case 'suspend':
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspended' WHERE id = ?");
            $stmt->execute([$userId]);
            $response = ['success' => true, 'message' => 'User suspended'];
            break;

        case 'deactivate':
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE id = ?");
            $stmt->execute([$userId]);
            $response = ['success' => true, 'message' => 'User deactivated'];
            break;

        case 'view':
            $stmt = $pdo->prepare("
                SELECT 
                    u.id, 
                    u.full_name, 
                    u.email, 
                    u.phone, 
                    u.user_type, 
                    u.status, 
                    u.is_phone_verified,
                    u.profile_image,
                    u.address,
                    u.city,
                    u.state,
                    u.zip_code,
                    u.created_at, 
                    u.updated_at,
                    (SELECT COUNT(*) FROM properties WHERE owner_id = u.id) as properties_count,
                    (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) as bookings_count,
                    (SELECT COUNT(*) FROM property_visits WHERE customer_id = u.id) as visits_count
                FROM users u 
                WHERE u.id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $response = ['success' => true, 'data' => $user];
            } else {
                $response = ['success' => false, 'message' => 'User not found'];
            }
            break;

        case 'delete':
            // Check if user has dependencies (properties, bookings, etc.)
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM properties WHERE owner_id = ?");
            $stmt->execute([$userId]);
            $propertiesCount = $stmt->fetch()['count'];

            // Check bookings as customer or owner
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM bookings WHERE customer_id = ? OR owner_id = ?");
            $stmt->execute([$userId, $userId]);
            $bookingsCount = $stmt->fetch()['count'];
            
            // Check property visits
            $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM property_visits WHERE customer_id = ? OR owner_id = ?");
            $stmt->execute([$userId, $userId]);
            $visitsCount = $stmt->fetch()['count'];

            if ($propertiesCount > 0 || $bookingsCount > 0 || $visitsCount > 0) {
                $dependencies = [];
                if ($propertiesCount > 0) $dependencies[] = "$propertiesCount properties";
                if ($bookingsCount > 0) $dependencies[] = "$bookingsCount bookings";
                if ($visitsCount > 0) $dependencies[] = "$visitsCount property visits";
                
                $response = ['success' => false, 'message' => 'Cannot delete user with existing: ' . implode(', ', $dependencies)];
            } else {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $response = ['success' => true, 'message' => 'User deleted successfully'];
            }
            break;

        case 'reset_password':
            // Generate a temporary password
            $tempPassword = bin2hex(random_bytes(8));
            $hashedPassword = password_hash($tempPassword, PASSWORD_DEFAULT);
            
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$hashedPassword, $userId]);
            
            $response = [
                'success' => true, 
                'message' => 'Password reset successfully',
                'temp_password' => $tempPassword
            ];
            break;
            
        case 'edit':
            // Get user data for editing
            $stmt = $pdo->prepare("
                SELECT id, full_name, email, phone, user_type, status, is_phone_verified, 
                       address, city, state, zip_code, profile_image
                FROM users 
                WHERE id = ?
            ");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                $response = ['success' => true, 'data' => $user];
            } else {
                $response = ['success' => false, 'message' => 'User not found'];
            }
            break;
            
        case 'update':
            // Update user information
            $updates = [];
            $params = [];
            
            // Validate and prepare update fields
            if (isset($input['full_name']) && !empty(trim($input['full_name']))) {
                $updates[] = 'full_name = ?';
                $params[] = trim($input['full_name']);
            }
            
            if (isset($input['email']) && !empty(trim($input['email']))) {
                // Validate email format
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    $response = ['success' => false, 'message' => 'Invalid email format'];
                    break;
                }
                
                // Check if email already exists for other users
                $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                $stmt->execute([$input['email'], $userId]);
                if ($stmt->fetch()) {
                    $response = ['success' => false, 'message' => 'Email already exists'];
                    break;
                }
                
                $updates[] = 'email = ?';
                $params[] = trim($input['email']);
            }
            
            if (isset($input['phone']) && !empty(trim($input['phone']))) {
                // Validate Sri Lankan phone number
                $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
                if (!preg_match($phone_pattern, $input['phone'])) {
                    $response = ['success' => false, 'message' => 'Invalid Sri Lankan phone number format'];
                    break;
                }
                
                // Check if phone already exists for other users
                $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
                $stmt->execute([$input['phone'], $userId]);
                if ($stmt->fetch()) {
                    $response = ['success' => false, 'message' => 'Phone number already exists'];
                    break;
                }
                
                $updates[] = 'phone = ?';
                $params[] = trim($input['phone']);
            }
            
            if (isset($input['user_type']) && in_array($input['user_type'], ['admin', 'owner', 'customer'])) {
                $updates[] = 'user_type = ?';
                $params[] = $input['user_type'];
            }
            
            if (isset($input['status']) && in_array($input['status'], ['active', 'inactive', 'suspended'])) {
                $updates[] = 'status = ?';
                $params[] = $input['status'];
            }
            
            if (isset($input['address'])) {
                $updates[] = 'address = ?';
                $params[] = trim($input['address']) ?: null;
            }
            
            if (isset($input['city'])) {
                $updates[] = 'city = ?';
                $params[] = trim($input['city']) ?: null;
            }
            
            if (isset($input['state'])) {
                $updates[] = 'state = ?';
                $params[] = trim($input['state']) ?: null;
            }
            
            if (isset($input['zip_code'])) {
                $updates[] = 'zip_code = ?';
                $params[] = trim($input['zip_code']) ?: null;
            }
            
            if (empty($updates)) {
                $response = ['success' => false, 'message' => 'No valid fields to update'];
                break;
            }
            
            // Add updated_at timestamp
            $updates[] = 'updated_at = NOW()';
            $params[] = $userId;
            
            $sql = "UPDATE users SET " . implode(', ', $updates) . " WHERE id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($params)) {
                $response = ['success' => true, 'message' => 'User updated successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update user'];
            }
            break;
            
        case 'toggle_verification':
            // Toggle phone verification status
            $stmt = $pdo->prepare("UPDATE users SET is_phone_verified = NOT is_phone_verified, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
            
            // Get new status
            $stmt = $pdo->prepare("SELECT is_phone_verified FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            $result = $stmt->fetch();
            
            $status = $result['is_phone_verified'] ? 'verified' : 'unverified';
            $response = ['success' => true, 'message' => "User phone number marked as {$status}"];
            break;
            
        case 'get_activity':
            // Get user activity (properties, bookings, visits)
            $stmt = $pdo->prepare("
                SELECT 
                    'property' as type,
                    p.title as title,
                    p.created_at as date,
                    p.status as status
                FROM properties p 
                WHERE p.owner_id = ?
                
                UNION ALL
                
                SELECT 
                    'booking' as type,
                    CONCAT('Booking for ', prop.title) as title,
                    b.created_at as date,
                    b.status as status
                FROM bookings b
                JOIN properties prop ON b.property_id = prop.id
                WHERE b.customer_id = ?
                
                UNION ALL
                
                SELECT 
                    'visit' as type,
                    CONCAT('Visit request for ', prop.title) as title,
                    pv.created_at as date,
                    pv.status as status
                FROM property_visits pv
                JOIN properties prop ON pv.property_id = prop.id
                WHERE pv.customer_id = ?
                
                ORDER BY date DESC
                LIMIT 20
            ");
            $stmt->execute([$userId, $userId, $userId]);
            $activity = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $response = ['success' => true, 'data' => $activity];
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