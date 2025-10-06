<?php
header('Content-Type: application/json');
require_once '../../includes/config.php';

// Start session if not started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit;
}

$customer_id = $_SESSION['user_id'];

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid request']);
    exit;
}

try {
    switch ($input['action']) {
        case 'update_profile':
            if (!isset($input['data'])) {
                throw new Exception('Profile data is required');
            }
            
            $data = $input['data'];
            
            // Validate required fields
            $required = ['name', 'email', 'phone'];
            foreach ($required as $field) {
                if (empty($data[$field])) {
                    throw new Exception(ucfirst(str_replace('_', ' ', $field)) . ' is required');
                }
            }
            
            // Check if email is already taken by another user
            $emailCheck = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $emailCheck->execute([$data['email'], $customer_id]);
            if ($emailCheck->fetch()) {
                throw new Exception('Email address is already in use');
            }
            
            // Check if phone is already taken by another user  
            $phoneCheck = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
            $phoneCheck->execute([$data['phone'], $customer_id]);
            if ($phoneCheck->fetch()) {
                throw new Exception('Phone number is already in use');
            }
            
            // Build update query
            $updateFields = [
                'full_name = ?',
                'email = ?', 
                'phone = ?',
                'address = ?',
                'city = ?',
                'state = ?',
                'zip_code = ?',
                'updated_at = NOW()'
            ];
            
            $params = [
                $data['name'],
                $data['email'],
                $data['phone'],
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['state'] ?? null,
                $data['zip_code'] ?? null
            ];
            
            // Handle password change if provided
            if (!empty($data['current_password']) && !empty($data['new_password'])) {
                // Verify current password
                $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
                $stmt->execute([$customer_id]);
                $user = $stmt->fetch();
                
                if (!$user || !password_verify($data['current_password'], $user['password_hash'])) {
                    throw new Exception('Current password is incorrect');
                }
                
                // Add password update to query
                $updateFields[] = 'password_hash = ?';
                $params[] = password_hash($data['new_password'], PASSWORD_DEFAULT);
            }
            
            // Execute update
            $params[] = $customer_id; // for WHERE clause
            $query = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            
            // Update session data
            $_SESSION['user_name'] = $data['name'];
            
            echo json_encode([
                'success' => true,
                'message' => 'Profile updated successfully'
            ]);
            break;
            
        case 'delete_account':
            // For security, we'll just mark the account as inactive instead of deleting
            $reason = $input['reason'] ?? null;
            
            // Update user status to suspended
            $stmt = $pdo->prepare("UPDATE users SET status = 'suspended', updated_at = NOW() WHERE id = ?");
            $stmt->execute([$customer_id]);
            
            // Log the deletion request (you might want to create a separate log table)
            // For now, we'll just mark it as suspended
            
            // Clear session
            session_destroy();
            
            echo json_encode([
                'success' => true,
                'message' => 'Account has been deactivated'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action');
    }
    
} catch (Exception $e) {
    error_log("Profile action error: " . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>