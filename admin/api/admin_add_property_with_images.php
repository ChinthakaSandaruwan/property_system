<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Check if user is logged in as admin
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

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
    if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
        $validation_errors[$field] = $label . ' is required';
    }
}

// Validate images
if (!isset($_FILES['property_images']) || empty($_FILES['property_images']['name'][0])) {
    $validation_errors['property_images'] = 'At least one property image is required';
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
        $stmt->execute([$_POST['owner_id']]);
        $owner = $stmt->fetch();
        
        if (!$owner) {
            $validation_errors['owner_id'] = 'Invalid or inactive property owner selected';
        }
        
        // Validate Sri Lankan phone number
        $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
        if (isset($_POST['contact_phone']) && !preg_match($phone_pattern, $_POST['contact_phone'])) {
            $validation_errors['contact_phone'] = 'Please enter a valid Sri Lankan phone number (e.g., 0771234567)';
        }
        
        // Validate numeric fields
        $bedrooms = filter_var($_POST['bedrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 10]]);
        if ($bedrooms === false) {
            $validation_errors['bedrooms'] = 'Number of bedrooms must be between 0 and 10';
        }
        
        $bathrooms = filter_var($_POST['bathrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 10]]);
        if ($bathrooms === false) {
            $validation_errors['bathrooms'] = 'Number of bathrooms must be between 1 and 10';
        }
        
        $rent_amount = filter_var($_POST['rent_amount'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
        if ($rent_amount === false) {
            $validation_errors['rent_amount'] = 'Monthly rent must be at least LKR 5,000';
        }
        
        $security_deposit = filter_var($_POST['security_deposit'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
        if ($security_deposit === false) {
            $validation_errors['security_deposit'] = 'Security deposit must be at least LKR 5,000';
        }
        
        // Optional numeric field - area
        $area_sqft = null;
        if (!empty($_POST['area_sqft'])) {
            $area_sqft = filter_var($_POST['area_sqft'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 100, "max_range" => 10000]]);
            if ($area_sqft === false) {
                $validation_errors['area_sqft'] = 'Area must be between 100 and 10,000 square feet';
            }
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
    
    // Handle image uploads
    $uploaded_images = [];
    $upload_dir = '../../uploads/properties/';
    
    // Create upload directory if it doesn't exist
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }
    
    if (isset($_FILES['property_images']) && !empty($_FILES['property_images']['name'][0])) {
        $files = $_FILES['property_images'];
        
        for ($i = 0; $i < count($files['name']); $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $temp_file = $files['tmp_name'][$i];
                $original_name = $files['name'][$i];
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
                $file_type = mime_content_type($temp_file);
                
                if (!in_array($file_type, $allowed_types)) {
                    continue; // Skip invalid files
                }
                
                // Validate file size (5MB)
                if ($files['size'][$i] > 5 * 1024 * 1024) {
                    continue; // Skip large files
                }
                
                // Generate unique filename
                $file_extension = strtolower(pathinfo($original_name, PATHINFO_EXTENSION));
                $unique_filename = uniqid('property_', true) . '.' . $file_extension;
                $upload_path = $upload_dir . $unique_filename;
                
                // Move uploaded file
                if (move_uploaded_file($temp_file, $upload_path)) {
                    $uploaded_images[] = $unique_filename;
                }
            }
        }
    }
    
    if (empty($uploaded_images)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Failed to upload images. Please try again.'
        ]);
        exit;
    }
    
    // Prepare amenities
    $amenities = [];
    if (isset($_POST['amenities'])) {
        $amenities = json_decode($_POST['amenities'], true) ?? [];
    }
    
    // Check if contact_phone column exists, add it if it doesn't
    $check_column_query = "SHOW COLUMNS FROM properties LIKE 'contact_phone'";
    $column_result = $pdo->query($check_column_query);
    
    if ($column_result->rowCount() == 0) {
        // Add contact_phone column if it doesn't exist
        $alter_table_query = "ALTER TABLE properties ADD COLUMN contact_phone VARCHAR(20) AFTER zip_code";
        $pdo->exec($alter_table_query);
    }
    
    // Prepare data for insertion
    $property_data = [
        'owner_id' => $_POST['owner_id'],
        'title' => trim($_POST['title']),
        'description' => !empty($_POST['description']) ? trim($_POST['description']) : null,
        'property_type' => $_POST['property_type'],
        'bedrooms' => $bedrooms,
        'bathrooms' => $bathrooms,
        'area_sqft' => $area_sqft,
        'address' => trim($_POST['address']),
        'city' => trim($_POST['city']),
        'state' => trim($_POST['state']),
        'zip_code' => !empty($_POST['zip_code']) ? trim($_POST['zip_code']) : null,
        'rent_amount' => $rent_amount,
        'security_deposit' => $security_deposit,
        'contact_phone' => $_POST['contact_phone'],
        'images' => json_encode($uploaded_images),
        'amenities' => json_encode($amenities),
        'status' => $_POST['status'] ?? 'pending',
        'is_available' => true,
        'created_at' => date('Y-m-d H:i:s')
    ];
    
    // Insert property into database
    $columns = implode(', ', array_keys($property_data));
    $placeholders = ':' . implode(', :', array_keys($property_data));
    
    $sql = "INSERT INTO properties ({$columns}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($property_data)) {
        $property_id = $pdo->lastInsertId();
        
        // Log the action
        error_log("Admin created property ID: $property_id with " . count($uploaded_images) . " images for owner: {$owner['full_name']} (ID: {$_POST['owner_id']})");
        
        echo json_encode([
            'success' => true, 
            'message' => "Property created successfully with " . count($uploaded_images) . " images for {$owner['full_name']}",
            'data' => [
                'property_id' => $property_id,
                'owner_name' => $owner['full_name'],
                'images_count' => count($uploaded_images),
                'amenities_count' => count($amenities)
            ]
        ]);
    } else {
        // Clean up uploaded images if database insert fails
        foreach ($uploaded_images as $image) {
            $file_path = $upload_dir . $image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Failed to create property. Please try again.']);
    }
    
} catch (Exception $e) {
    // Clean up uploaded images on error
    if (isset($uploaded_images)) {
        foreach ($uploaded_images as $image) {
            $file_path = $upload_dir . $image;
            if (file_exists($file_path)) {
                unlink($file_path);
            }
        }
    }
    
    // Log error for debugging
    error_log("Admin property creation error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while creating the property'
    ]);
}
?>