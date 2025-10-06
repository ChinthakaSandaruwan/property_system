<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$owner_id = $_POST['owner_id'] ?? null;
$action = $_POST['action'] ?? null;

if (!$owner_id) {
    echo json_encode(['success' => false, 'message' => 'Owner ID is required']);
    exit;
}

try {
    if ($action === 'update_profile') {
        $full_name = $_POST['full_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $phone = $_POST['phone'] ?? '';
        $address = $_POST['address'] ?? '';

        // Validation
        if (empty($full_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'Full name and email are required']);
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['success' => false, 'message' => 'Invalid email format']);
            exit;
        }

        // Validate phone number if provided
        if (!empty($phone) && !preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone)) {
            echo json_encode(['success' => false, 'message' => 'Invalid Sri Lankan phone number format']);
            exit;
        }

        // Check if email is already taken by another user
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $stmt->execute([$email, $owner_id]);
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Email address is already in use']);
            exit;
        }

        // Update profile
        $stmt = $pdo->prepare("
            UPDATE users 
            SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
            WHERE id = ? AND user_type = 'owner'
        ");
        
        $result = $stmt->execute([$full_name, $email, $phone, $address, $owner_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update profile']);
        }

    } elseif ($action === 'change_password') {
        $current_password = $_POST['current_password'] ?? '';
        $new_password = $_POST['new_password'] ?? '';

        // Validation
        if (empty($current_password) || empty($new_password)) {
            echo json_encode(['success' => false, 'message' => 'Current password and new password are required']);
            exit;
        }

        if (strlen($new_password) < 6) {
            echo json_encode(['success' => false, 'message' => 'New password must be at least 6 characters long']);
            exit;
        }

        // Verify current password
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ? AND user_type = 'owner'");
        $stmt->execute([$owner_id]);
        $user = $stmt->fetch();

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Owner not found']);
            exit;
        }

        if (!password_verify($current_password, $user['password_hash'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

        // Update password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = ?, updated_at = NOW()
            WHERE id = ? AND user_type = 'owner'
        ");
        
        $result = $stmt->execute([$hashed_password, $owner_id]);

        if ($result) {
            echo json_encode(['success' => true, 'message' => 'Password changed successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to change password']);
        }

    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>