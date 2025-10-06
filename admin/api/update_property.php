<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Handle both JSON and FormData requests
$input = null;
$new_images = [];

// Check if this is a FormData request (with file uploads)
if (isset($_POST['update_data'])) {
    // FormData request with potential file uploads
    $input = json_decode($_POST['update_data'], true);
    
    // Handle new image uploads
    if (isset($_FILES['new_images'])) {
        $new_images = $_FILES['new_images'];
    }
} else {
    // Standard JSON request
    $input = json_decode(file_get_contents('php://input'), true);
}

// Validate required data
if (!$input || !isset($input['property_id'])) {
    echo json_encode(['success' => false, 'message' => 'Property ID is required']);
    exit;
}

$propertyId = $input['property_id'];

// Initialize validation errors array
$validation_errors = [];

// Validate required fields
$required_fields = [
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
        
        // Optional numeric field - area
        $area_sqft = null;
        if (!empty($input['area_sqft'])) {
            $area_sqft = filter_var($input['area_sqft'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 100, "max_range" => 10000]]);
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
    
    // Check if property exists and get current images
    $stmt = $pdo->prepare("SELECT id, images FROM properties WHERE id = ?");
    $stmt->execute([$propertyId]);
    $property = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property not found']);
        exit;
    }
    
    // Handle image operations
    $current_images = json_decode($property['images'] ?? '[]', true);
    if (!is_array($current_images)) {
        $current_images = [];
    }
    
    // Handle image removal
    if (isset($input['images_to_remove']) && is_array($input['images_to_remove'])) {
        foreach ($input['images_to_remove'] as $image_to_remove) {
            // Remove from current images array
            $current_images = array_filter($current_images, function($img) use ($image_to_remove) {
                return $img !== $image_to_remove;
            });
            
            // Delete physical file
            $file_path = '../../uploads/properties/' . $image_to_remove;
            if (file_exists($file_path)) {
                unlink($file_path);
                error_log("Deleted image file: " . $file_path);
            }
        }
        // Re-index array
        $current_images = array_values($current_images);
    }
    
    // Handle new image uploads
    if (!empty($new_images['name']) && is_array($new_images['name'])) {
        $upload_dir = '../../uploads/properties/';
        
        // Ensure upload directory exists
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        for ($i = 0; $i < count($new_images['name']); $i++) {
            if ($new_images['error'][$i] === UPLOAD_ERR_OK) {
                $file_tmp = $new_images['tmp_name'][$i];
                $file_name = $new_images['name'][$i];
                $file_size = $new_images['size'][$i];
                $file_type = $new_images['type'][$i];
                
                // Validate file type
                $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                if (!in_array($file_type, $allowed_types)) {
                    $validation_errors['images'] = "Invalid file type for {$file_name}. Only JPEG, PNG, GIF, and WebP are allowed.";
                    continue;
                }
                
                // Validate file size (5MB limit)
                if ($file_size > 5 * 1024 * 1024) {
                    $validation_errors['images'] = "File {$file_name} is too large. Maximum size is 5MB.";
                    continue;
                }
                
                // Generate unique filename
                $file_extension = pathinfo($file_name, PATHINFO_EXTENSION);
                $unique_filename = 'property_' . $propertyId . '_' . uniqid() . '.' . $file_extension;
                $destination = $upload_dir . $unique_filename;
                
                // Move uploaded file
                if (move_uploaded_file($file_tmp, $destination)) {
                    $current_images[] = $unique_filename;
                    error_log("Uploaded new image: " . $unique_filename);
                } else {
                    $validation_errors['images'] = "Failed to upload {$file_name}.";
                }
            }
        }
    }
    
    // Check for image upload validation errors
    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Please correct the following errors:',
            'errors' => $validation_errors
        ]);
        exit;
    }
    
    // Validate status and availability if provided
    $status = 'approved'; // default
    if (isset($input['status'])) {
        $valid_statuses = ['pending', 'approved', 'rejected', 'rented'];
        if (in_array($input['status'], $valid_statuses)) {
            $status = $input['status'];
        } else {
            $validation_errors['status'] = 'Invalid status value';
        }
    }
    
    $is_available = 1; // default
    if (isset($input['is_available'])) {
        $is_available = in_array($input['is_available'], [0, 1, '0', '1']) ? (int)$input['is_available'] : 1;
    }
    
    // If there are validation errors from status/availability, return them
    if (!empty($validation_errors)) {
        echo json_encode([
            'success' => false, 
            'message' => 'Please correct the following errors:',
            'errors' => $validation_errors
        ]);
        exit;
    }
    
    // Prepare data for update
    $update_data = [
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
        'status' => $status,
        'is_available' => $is_available,
        'images' => json_encode($current_images),
        'updated_at' => date('Y-m-d H:i:s'),
        'property_id' => $propertyId
    ];
    
    // Handle amenities if provided
    if (isset($input['amenities']) && is_array($input['amenities'])) {
        $update_data['amenities'] = json_encode(array_values($input['amenities']));
    }
    
    // Update property in database
    $sql = "UPDATE properties SET 
                title = :title,
                description = :description,
                property_type = :property_type,
                bedrooms = :bedrooms,
                bathrooms = :bathrooms,
                area_sqft = :area_sqft,
                address = :address,
                city = :city,
                state = :state,
                zip_code = :zip_code,
                rent_amount = :rent_amount,
                security_deposit = :security_deposit,
                contact_phone = :contact_phone,
                status = :status,
                is_available = :is_available,
                images = :images,
                updated_at = :updated_at";
    
    // Add amenities to update if provided
    if (isset($update_data['amenities'])) {
        $sql .= ", amenities = :amenities";
    }
    
    $sql .= " WHERE id = :property_id";
    
    $stmt = $pdo->prepare($sql);
    
    if ($stmt->execute($update_data)) {
        // Log the action
        error_log("Admin updated property ID: {$propertyId} - Title: {$update_data['title']}");
        
        echo json_encode([
            'success' => true, 
            'message' => "Property '{$update_data['title']}' updated successfully",
            'data' => [
                'property_id' => $propertyId,
                'title' => $update_data['title'],
                'status' => $update_data['status'],
                'is_available' => $update_data['is_available'],
                'updated_at' => $update_data['updated_at']
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update property. Please try again.']);
    }
    
} catch (PDOException $e) {
    // Log detailed database error for debugging
    error_log("Property update database error: " . $e->getMessage());
    error_log("Update data: " . json_encode($update_data));
    
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage(),
        'debug' => [
            'error_code' => $e->getCode(),
            'error_message' => $e->getMessage(),
            'update_data' => $update_data
        ]
    ]);
} catch (Exception $e) {
    // Log general error for debugging
    error_log("Property update general error: " . $e->getMessage());
    error_log("Update data: " . json_encode($update_data));
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while updating the property: ' . $e->getMessage(),
        'debug' => [
            'error_message' => $e->getMessage(),
            'update_data' => $update_data
        ]
    ]);
}
?>