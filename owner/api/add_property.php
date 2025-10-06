<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

header('Content-Type: application/json');

// Check if user is logged in and is an owner
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try {
    $owner_id = $_SESSION['user_id'];
    
    // Validate required fields
    $required_fields = ['title', 'property_type', 'bedrooms', 'bathrooms', 'rent_amount', 'security_deposit', 'address', 'city', 'state', 'contact_phone'];
    $missing_fields = [];
    
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || trim($_POST[$field]) === '') {
            $missing_fields[] = $field;
        }
    }
    
    if (!empty($missing_fields)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Missing required fields: ' . implode(', ', $missing_fields)
        ]);
        exit;
    }
    
    // Validate Sri Lankan phone number using the pattern from rules
    $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
    if (!preg_match($phone_pattern, $_POST['contact_phone'])) {
        echo json_encode([
            'success' => false, 
            'message' => 'Please enter a valid Sri Lankan mobile number (e.g., 0771234567)'
        ]);
        exit;
    }
    
    // Validate numeric fields
    $bedrooms = filter_var($_POST['bedrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 10]]);
    $bathrooms = filter_var($_POST['bathrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 10]]);
    $rent_amount = filter_var($_POST['rent_amount'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
    $security_deposit = filter_var($_POST['security_deposit'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
    
    if ($bedrooms === false || $bathrooms === false || $rent_amount === false || $security_deposit === false) {
        echo json_encode(['success' => false, 'message' => 'Invalid numeric values provided']);
        exit;
    }
    
    // Optional numeric field - area
    $area_sqft = null;
    if (!empty($_POST['area_sqft'])) {
        $area_sqft = filter_var($_POST['area_sqft'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 100, "max_range" => 10000]]);
        if ($area_sqft === false) {
            echo json_encode(['success' => false, 'message' => 'Invalid area value provided']);
            exit;
        }
    }
    
    // Process amenities
    $amenities = [];
    if (!empty($_POST['amenities']) && is_array($_POST['amenities'])) {
        $amenities = array_map('trim', $_POST['amenities']);
        $amenities = array_filter($amenities); // Remove empty values
    }
    
    // Handle image uploads
    $uploaded_images = [];
    if (!empty($_FILES['property_images'])) {
        $upload_dir = dirname(dirname(__DIR__)) . '/uploads/properties/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $files = $_FILES['property_images'];
        $file_count = count($files['name']);
        
        for ($i = 0; $i < $file_count; $i++) {
            if ($files['error'][$i] === UPLOAD_ERR_OK) {
                $temp_file = $files['tmp_name'][$i];
                $original_name = $files['name'][$i];
                $file_size = $files['size'][$i];
                
                // Validate file size (5MB max)
                if ($file_size > 5 * 1024 * 1024) {
                    echo json_encode(['success' => false, 'message' => 'Image file too large. Maximum size is 5MB.']);
                    exit;
                }
                
                // Validate file type
                $file_info = finfo_open(FILEINFO_MIME_TYPE);
                $mime_type = finfo_file($file_info, $temp_file);
                finfo_close($file_info);
                
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                if (!in_array($mime_type, $allowed_types)) {
                    echo json_encode(['success' => false, 'message' => 'Invalid file type. Only JPG, PNG, and GIF are allowed.']);
                    exit;
                }
                
                // Generate unique filename
                $file_extension = pathinfo($original_name, PATHINFO_EXTENSION);
                $new_filename = 'property_' . $owner_id . '_' . time() . '_' . $i . '.' . $file_extension;
                $destination = $upload_dir . $new_filename;
                
                if (move_uploaded_file($temp_file, $destination)) {
                    $uploaded_images[] = $new_filename;
                }
            }
        }
    }
    
    // Prepare data for insertion
    $property_data = [
        'owner_id' => $owner_id,
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
        'images' => json_encode($uploaded_images),
        'amenities' => json_encode($amenities),
        'status' => 'pending' // Properties need admin approval
    ];
    
    // First, check if we need to add contact_phone column to properties table
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
    
    // Add contact_phone to the data
    $property_data['contact_phone'] = $_POST['contact_phone'];
    
    // Insert property into database
    $columns = implode(', ', array_keys($property_data));
    $placeholders = ':' . implode(', :', array_keys($property_data));
    
    $sql = "INSERT INTO properties ({$columns}) VALUES ({$placeholders})";
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($property_data)) {
        echo json_encode([
            'success' => true, 
            'message' => 'Property submitted successfully! It will be reviewed by admin before being listed.'
        ]);
    } else {
        // If database insert fails, clean up uploaded images
        foreach ($uploaded_images as $image) {
            $image_path = $upload_dir . $image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
        
        echo json_encode(['success' => false, 'message' => 'Failed to save property. Please try again.']);
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Property creation error: " . $e->getMessage());
    
    // Clean up any uploaded images if there was an error
    if (!empty($uploaded_images)) {
        foreach ($uploaded_images as $image) {
            $image_path = '../../uploads/properties/' . $image;
            if (file_exists($image_path)) {
                unlink($image_path);
            }
        }
    }
    
    echo json_encode(['success' => false, 'message' => 'An error occurred while processing your request.']);
}
?>