<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true);

try {
    $response = ['success' => false, 'message' => 'Invalid request'];

    switch ($method) {
        case 'GET':
            // Get users list or specific user
            if (isset($_GET['id'])) {
                // Get specific user
                $userId = $_GET['id'];
                $stmt = $pdo->prepare("
                    SELECT 
                        u.*,
                        (SELECT COUNT(*) FROM properties WHERE owner_id = u.id) as properties_count,
                        (SELECT COUNT(*) FROM bookings WHERE customer_id = u.id) as bookings_count,
                        (SELECT COUNT(*) FROM property_visits WHERE customer_id = u.id) as visits_count
                    FROM users u 
                    WHERE u.id = ?
                ");
                $stmt->execute([$userId]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if ($user) {
                    // Don't return password hash
                    unset($user['password_hash']);
                    $response = ['success' => true, 'data' => $user];
                } else {
                    $response = ['success' => false, 'message' => 'User not found'];
                }
                
            } else {
                // Get all users with filters
                $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
                $limit = isset($_GET['limit']) ? max(1, min(100, intval($_GET['limit']))) : 20;
                $offset = ($page - 1) * $limit;
                
                $whereConditions = [];
                $params = [];
                
                // Filter by user type
                if (isset($_GET['user_type']) && !empty($_GET['user_type'])) {
                    $whereConditions[] = "user_type = ?";
                    $params[] = $_GET['user_type'];
                }
                
                // Filter by status
                if (isset($_GET['status']) && !empty($_GET['status'])) {
                    $whereConditions[] = "status = ?";
                    $params[] = $_GET['status'];
                }
                
                // Search by name or email
                if (isset($_GET['search']) && !empty($_GET['search'])) {
                    $whereConditions[] = "(full_name LIKE ? OR email LIKE ?)";
                    $searchTerm = '%' . $_GET['search'] . '%';
                    $params[] = $searchTerm;
                    $params[] = $searchTerm;
                }
                
                $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
                
                // Get total count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM users $whereClause");
                $countStmt->execute($params);
                $totalUsers = $countStmt->fetchColumn();
                
                // Get users
                $orderBy = isset($_GET['order_by']) ? $_GET['order_by'] : 'created_at';
                $orderDir = isset($_GET['order_dir']) && strtoupper($_GET['order_dir']) === 'ASC' ? 'ASC' : 'DESC';
                
                $params[] = $limit;
                $params[] = $offset;
                
                $stmt = $pdo->prepare("
                    SELECT 
                        id, full_name, email, phone, user_type, status, 
                        is_phone_verified, created_at, updated_at
                    FROM users 
                    $whereClause 
                    ORDER BY $orderBy $orderDir 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute($params);
                $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                $response = [
                    'success' => true,
                    'data' => $users,
                    'pagination' => [
                        'current_page' => $page,
                        'per_page' => $limit,
                        'total' => $totalUsers,
                        'total_pages' => ceil($totalUsers / $limit)
                    ]
                ];
            }
            break;
            
        case 'POST':
            // Create new user
            if (!$input) {
                $response = ['success' => false, 'message' => 'Invalid input data'];
                break;
            }
            
            // Validate required fields
            $required_fields = ['full_name', 'email', 'phone', 'password', 'user_type'];
            $validation_errors = [];
            
            foreach ($required_fields as $field) {
                if (!isset($input[$field]) || empty(trim($input[$field]))) {
                    $validation_errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required';
                }
            }
            
            // Validate email format
            if (isset($input['email']) && !filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                $validation_errors['email'] = 'Invalid email format';
            }
            
            // Validate phone number (Sri Lankan format)  
            if (isset($input['phone'])) {
                $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
                if (!preg_match($phone_pattern, $input['phone'])) {
                    $validation_errors['phone'] = 'Invalid Sri Lankan phone number format (e.g., 0771234567)';
                }
            }
            
            // Validate user type
            if (isset($input['user_type']) && !in_array($input['user_type'], ['admin', 'owner', 'customer'])) {
                $validation_errors['user_type'] = 'Invalid user type';
            }
            
            // Validate password strength
            if (isset($input['password']) && strlen($input['password']) < 8) {
                $validation_errors['password'] = 'Password must be at least 8 characters long';
            }
            
            if (!empty($validation_errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validation_errors
                ];
                break;
            }
            
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$input['email']]);
            if ($stmt->fetch()) {
                $response = ['success' => false, 'message' => 'Email already exists'];
                break;
            }
            
            // Check if phone already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$input['phone']]);
            if ($stmt->fetch()) {
                $response = ['success' => false, 'message' => 'Phone number already exists'];
                break;
            }
            
            // Create user
            $hashedPassword = password_hash($input['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (
                    full_name, email, phone, password_hash, user_type, 
                    status, address, city, state, zip_code, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $status = isset($input['status']) && in_array($input['status'], ['active', 'inactive', 'suspended']) 
                     ? $input['status'] : 'active';
                     
            $success = $stmt->execute([
                trim($input['full_name']),
                trim($input['email']),
                trim($input['phone']),
                $hashedPassword,
                $input['user_type'],
                $status,
                isset($input['address']) ? trim($input['address']) : null,
                isset($input['city']) ? trim($input['city']) : null,
                isset($input['state']) ? trim($input['state']) : null,
                isset($input['zip_code']) ? trim($input['zip_code']) : null
            ]);
            
            if ($success) {
                $userId = $pdo->lastInsertId();
                $response = [
                    'success' => true,
                    'message' => 'User created successfully',
                    'data' => ['user_id' => $userId]
                ];
            } else {
                $response = ['success' => false, 'message' => 'Failed to create user'];
            }
            break;
            
        case 'PUT':
            // Update user
            if (!$input || !isset($input['user_id'])) {
                $response = ['success' => false, 'message' => 'User ID is required'];
                break;
            }
            
            $userId = $input['user_id'];
            
            // Check if user exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            if (!$stmt->fetch()) {
                $response = ['success' => false, 'message' => 'User not found'];
                break;
            }
            
            $updates = [];
            $params = [];
            $validation_errors = [];
            
            // Update fields if provided
            if (isset($input['full_name']) && !empty(trim($input['full_name']))) {
                $updates[] = 'full_name = ?';
                $params[] = trim($input['full_name']);
            }
            
            if (isset($input['email']) && !empty(trim($input['email']))) {
                if (!filter_var($input['email'], FILTER_VALIDATE_EMAIL)) {
                    $validation_errors['email'] = 'Invalid email format';
                } else {
                    // Check if email exists for other users
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                    $stmt->execute([$input['email'], $userId]);
                    if ($stmt->fetch()) {
                        $validation_errors['email'] = 'Email already exists';
                    } else {
                        $updates[] = 'email = ?';
                        $params[] = trim($input['email']);
                    }
                }
            }
            
            if (isset($input['phone']) && !empty(trim($input['phone']))) {
                $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
                if (!preg_match($phone_pattern, $input['phone'])) {
                    $validation_errors['phone'] = 'Invalid phone number format';
                } else {
                    // Check if phone exists for other users
                    $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
                    $stmt->execute([$input['phone'], $userId]);
                    if ($stmt->fetch()) {
                        $validation_errors['phone'] = 'Phone number already exists';
                    } else {
                        $updates[] = 'phone = ?';
                        $params[] = trim($input['phone']);
                    }
                }
            }
            
            if (isset($input['user_type']) && in_array($input['user_type'], ['admin', 'owner', 'customer'])) {
                $updates[] = 'user_type = ?';
                $params[] = $input['user_type'];
            }
            
            if (isset($input['status']) && in_array($input['status'], ['active', 'inactive', 'suspended'])) {
                $updates[] = 'status = ?';
                $params[] = $input['status'];
            }
            
            // Optional fields
            $optional_fields = ['address', 'city', 'state', 'zip_code'];
            foreach ($optional_fields as $field) {
                if (array_key_exists($field, $input)) {
                    $updates[] = "$field = ?";
                    $params[] = !empty(trim($input[$field])) ? trim($input[$field]) : null;
                }
            }
            
            // Update password if provided
            if (isset($input['password']) && !empty($input['password'])) {
                if (strlen($input['password']) < 8) {
                    $validation_errors['password'] = 'Password must be at least 8 characters long';
                } else {
                    $updates[] = 'password_hash = ?';
                    $params[] = password_hash($input['password'], PASSWORD_DEFAULT);
                }
            }
            
            if (!empty($validation_errors)) {
                $response = [
                    'success' => false,
                    'message' => 'Validation errors',
                    'errors' => $validation_errors
                ];
                break;
            }
            
            if (empty($updates)) {
                $response = ['success' => false, 'message' => 'No fields to update'];
                break;
            }
            
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
            
        case 'DELETE':
            // Delete user
            if (!isset($_GET['id'])) {
                $response = ['success' => false, 'message' => 'User ID is required'];
                break;
            }
            
            $userId = $_GET['id'];
            
            // Check if user has dependencies
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ?");
            $stmt->execute([$userId]);
            $propertiesCount = $stmt->fetchColumn();
            
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE customer_id = ?");
            $stmt->execute([$userId]);
            $bookingsCount = $stmt->fetchColumn();
            
            if ($propertiesCount > 0 || $bookingsCount > 0) {
                $response = [
                    'success' => false,
                    'message' => 'Cannot delete user with existing properties or bookings',
                    'details' => [
                        'properties' => $propertiesCount,
                        'bookings' => $bookingsCount
                    ]
                ];
                break;
            }
            
            // Delete user
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            if ($stmt->execute([$userId])) {
                $response = ['success' => true, 'message' => 'User deleted successfully'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to delete user'];
            }
            break;
    }

} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>