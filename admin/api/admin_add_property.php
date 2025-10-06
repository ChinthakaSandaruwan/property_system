<?php
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in as admin (you can add authentication here)
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// You can add admin authentication check here
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
//     http_response_code(401);
//     echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
//     exit;
// }

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Get input data
$input = json_decode(file_get_contents('php://input'), true);

// Initialize validation errors array
$validation_errors = [];

// Validate required fields
$required_fields = [
    'owner_id' => 'Property owner',
    'title' => 'Property title',
    'property_type' => 'Property type',
    'bedrooms' => 'Number of bedrooms',
    'bathrooms' => 'Number of bathrooms',
    'rent_amount' => 'Monthly rent',
    'security_deposit' => 'Security deposit',
    'address' => 'Address',
    'city' => 'City',
    'state' => 'State/Province',
    'contact_phone' => 'Contact phone'
];

foreach ($required_fields as $field => $label) {
    if (!isset($input[$field]) || trim($input[$field]) === '') {
        $validation_errors[$field] = $label . ' is required';
    }
}

// If there are validation errors, return them
if (!empty($validation_errors)) {
    echo json_encode([
        'success' => false, 
        'message' => 'Please correct the following errors:',
        'errors' => $validation_errors
    ]);
    exit;
}

try {
    // Continue validation if basic required fields passed
    if (empty($validation_errors)) {
        
        // Validate that the owner exists and is an approved owner
        $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE id = ? AND user_type = 'owner' AND status IN ('approved', 'active')");
        $stmt->execute([$input['owner_id']]);
        $owner = $stmt->fetch();
        
        if (!$owner) {
            $validation_errors['owner_id'] = 'Invalid or inactive property owner selected';
        }
        
        // Validate Sri Lankan phone number
        $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
        if (isset($input['contact_phone']) && !preg_match($phone_pattern, $input['contact_phone'])) {
            $validation_errors['contact_phone'] = 'Please enter a valid Sri Lankan phone number (e.g., 0771234567)';
        }
        
        // Validate numeric fields
        $bedrooms = filter_var($input['bedrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 10]]);
        if ($bedrooms === false) {
            $validation_errors['bedrooms'] = 'Number of bedrooms must be between 0 and 10';
        }
        
        $bathrooms = filter_var($input['bathrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 10]]);
        if ($bathrooms === false) {
            $validation_errors['bathrooms'] = 'Number of bathrooms must be between 1 and 10';
        }
        
        $rent_amount = filter_var($input['rent_amount'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
        if ($rent_amount === false) {
            $validation_errors['rent_amount'] = 'Monthly rent must be at least LKR 5,000';
        }
        
        $security_deposit = filter_var($input['security_deposit'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
        if ($security_deposit === false) {
            $validation_errors['security_deposit'] = 'Security deposit must be at least LKR 5,000';
        }
        
        // If there are additional validation errors, return them
        if (!empty($validation_errors)) {
            echo json_encode([
                'success' => false, 
                'message' => 'Please correct the following errors:',
                'errors' => $validation_errors
            ]);
            exit;
        }
    }
    
    // Optional numeric field - area
    $area_sqft = null;
    if (!empty($input['area_sqft'])) {
        $area_sqft = filter_var($input['area_sqft'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 100, "max_range" => 10000]]);
        if ($area_sqft === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid area value provided']);
            exit;
        }
    }
    
    // Check if contact_phone column exists, add it if it doesn't
    $check_column_query = "SHOW COLUMNS FROM properties LIKE 'contact_phone'";
    $column_result = $pdo->query($check_column_query);
    
    if ($column_result->rowCount() == 0) {
        // Add contact_phone column if it doesn't exist
        $alter_table_query = "ALTER TABLE properties ADD COLUMN contact_phone VARCHAR(20) AFTER zip_code";
        $pdo->exec($alter_table_query);
        
        // Add index for the new column
        $add_index_query = "ALTER TABLE properties ADD INDEX idx_properties_contact_phone (contact_phone)";
        $pdo->exec($add_index_query);
    }
    
    // Prepare data for insertion
    $property_data = [
        'owner_id' => $input['owner_id'],
        'title' => trim($input['title']),
        'description' => !empty($input['description']) ? trim($input['description']) : null,
        'property_type' => $input['property_type'],
        'bedrooms' => $bedrooms,
        'bathrooms' => $bathrooms,
        'area_sqft' => $area_sqft,
        'address' => trim($input['address']),
        'city' => trim($input['city']),
        'state' => trim($input['state']),
        'zip_code' => !empty($input['zip_code']) ? trim($input['zip_code']) : null,
        'rent_amount' => $rent_amount,
        'security_deposit' => $security_deposit,
        'contact_phone' => $input['contact_phone'],
        'images' => json_encode([]), // Empty array for now, admin can add images later
        'amenities' => json_encode([]), // Empty array for now, admin can add amenities later
        'status' => $input['status'] ?? 'pending', // Use provided status or default to pending
        'is_available' => true
    ];
    
    // Insert property into database
    $columns = implode(', ', array_keys($property_data));
    $placeholders = ':' . implode(', :', array_keys($property_data));
    
    $sql = "INSERT INTO properties ({$columns}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($property_data)) {
        $property_id = $pdo->lastInsertId();
        
        // Log the action (you can expand this for audit trail)
        error_log("Admin created property ID: $property_id for owner: {$owner['full_name']} (ID: {$input['owner_id']})");
        
        echo json_encode([
            'success' => true, 
            'message' => "Property created successfully for {$owner['full_name']}",
            'data' => [
                'property_id' => $property_id,
                'owner_name' => $owner['full_name']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to create property. Please try again.']);
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Admin property creation error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while creating the property'
    ]);
}
?>