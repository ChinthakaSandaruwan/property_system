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

// Get input data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$property_id = $input['property_id'] ?? 0;
$owner_id = $_SESSION['user_id'];

// Validate input
if (empty($action) || empty($property_id)) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit;
}

try {
    // First, verify the property belongs to the owner
    $stmt = $pdo->prepare("SELECT id, title, status, is_available FROM properties WHERE id = ? AND owner_id = ?");
    $stmt->execute([$property_id, $owner_id]);
    $property = $stmt->fetch();
    
    if (!$property) {
        echo json_encode(['success' => false, 'message' => 'Property not found or access denied']);
        exit;
    }
    
    switch ($action) {
        case 'view':
            // Return property details for viewing
            $stmt = $pdo->prepare("
                SELECT p.*, COUNT(b.id) as booking_count 
                FROM properties p 
                LEFT JOIN bookings b ON p.id = b.property_id 
                WHERE p.id = ? AND p.owner_id = ?
                GROUP BY p.id
            ");
            $stmt->execute([$property_id, $owner_id]);
            $propertyDetails = $stmt->fetch();
            
            // Get images
            $images = json_decode($propertyDetails['images'] ?? '[]', true);
            $propertyDetails['image_urls'] = array_map(function($img) {
                return '/rental_system/uploads/properties/' . $img;
            }, $images);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Property details retrieved',
                'data' => $propertyDetails
            ]);
            break;
            
        case 'edit':
            // Return property data for editing
            $stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ? AND owner_id = ?");
            $stmt->execute([$property_id, $owner_id]);
            $propertyData = $stmt->fetch();
            
            if (!$propertyData) {
                echo json_encode(['success' => false, 'message' => 'Property not found']);
                exit;
            }
            
            // Parse images and amenities for easier handling
            $images = json_decode($propertyData['images'] ?? '[]', true);
            $amenities = json_decode($propertyData['amenities'] ?? '[]', true);
            
            $propertyData['image_urls'] = array_map(function($img) {
                return '/rental_system/uploads/properties/' . $img;
            }, $images);
            $propertyData['amenities_array'] = $amenities;
            
            echo json_encode([
                'success' => true, 
                'message' => 'Property data retrieved for editing',
                'data' => $propertyData
            ]);
            break;
            
        case 'update':
            // Check if property can be edited (only pending, rejected, or approved properties)
            if (!in_array($property['status'], ['pending', 'rejected', 'approved'])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'This property cannot be edited in its current status'
                ]);
                exit;
            }
            
            // Handle property update
            $required_fields = ['title', 'property_type', 'bedrooms', 'bathrooms', 'rent_amount', 'security_deposit', 'address', 'city', 'state', 'contact_phone'];
            $missing_fields = [];
            
            foreach ($required_fields as $field) {
                if (!isset($input[$field]) || trim($input[$field]) === '') {
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
            
            // Validate Sri Lankan phone number
            $phone_pattern = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
            if (!preg_match($phone_pattern, $input['contact_phone'])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Please enter a valid Sri Lankan mobile number (e.g., 0771234567)'
                ]);
                exit;
            }
            
            // Validate numeric fields
            $bedrooms = filter_var($input['bedrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 10]]);
            $bathrooms = filter_var($input['bathrooms'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 1, "max_range" => 10]]);
            $rent_amount = filter_var($input['rent_amount'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
            $security_deposit = filter_var($input['security_deposit'], FILTER_VALIDATE_FLOAT, ["options" => ["min_range" => 5000]]);
            
            if ($bedrooms === false || $bathrooms === false || $rent_amount === false || $security_deposit === false) {
                echo json_encode(['success' => false, 'message' => 'Invalid numeric values provided']);
                exit;
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
            
            // Process amenities
            $amenities = [];
            if (!empty($input['amenities']) && is_array($input['amenities'])) {
                $amenities = array_map('trim', $input['amenities']);
                $amenities = array_filter($amenities); // Remove empty values
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
                'amenities' => json_encode($amenities),
                'contact_phone' => $input['contact_phone']
            ];
            
            // Build UPDATE query
            $set_clauses = [];
            $values = [];
            foreach ($update_data as $key => $value) {
                $set_clauses[] = "$key = ?";
                $values[] = $value;
            }
            $values[] = $property_id;
            $values[] = $owner_id;
            
            $sql = "UPDATE properties SET " . implode(', ', $set_clauses) . " WHERE id = ? AND owner_id = ?";
            $stmt = $pdo->prepare($sql);
            
            if ($stmt->execute($values)) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property updated successfully'
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update property']);
            }
            break;
            
        case 'delete':
            // Only allow deletion if property is pending or rejected
            if (!in_array($property['status'], ['pending', 'rejected'])) {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Can only delete pending or rejected properties'
                ]);
                exit;
            }
            
            // Get images for deletion
            $stmt = $pdo->prepare("SELECT images FROM properties WHERE id = ? AND owner_id = ?");
            $stmt->execute([$property_id, $owner_id]);
            $prop = $stmt->fetch();
            $images = json_decode($prop['images'] ?? '[]', true);
            $upload_dir = dirname(dirname(__DIR__)) . '/uploads/properties/';
            foreach ($images as $image) {
                $image_path = $upload_dir . $image;
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            // Delete property from database
            $stmt = $pdo->prepare("DELETE FROM properties WHERE id = ? AND owner_id = ?");
            $stmt->execute([$property_id, $owner_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property deleted successfully'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to delete property'
                ]);
            }
            break;
            
        case 'mark-available':
            // Mark property as available
            $stmt = $pdo->prepare("UPDATE properties SET is_available = 1 WHERE id = ? AND owner_id = ?");
            $stmt->execute([$property_id, $owner_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property marked as available'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to update property availability'
                ]);
            }
            break;
            
        case 'mark-unavailable':
            // Mark property as unavailable/occupied
            $stmt = $pdo->prepare("UPDATE properties SET is_available = 0 WHERE id = ? AND owner_id = ?");
            $stmt->execute([$property_id, $owner_id]);
            
            if ($stmt->rowCount() > 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property marked as occupied'
                ]);
            } else {
                echo json_encode([
                    'success' => false, 
                    'message' => 'Failed to update property availability'
                ]);
            }
            break;
            
        default:
            echo json_encode([
                'success' => false, 
                'message' => 'Invalid action specified'
            ]);
            break;
    }
    
} catch (Exception $e) {
    // Log error for debugging
    error_log("Property action error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'message' => 'An error occurred while processing your request'
    ]);
}
?>