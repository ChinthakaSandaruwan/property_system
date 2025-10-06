<?php
session_start();
require_once '../../includes/config.php';

header('Content-Type: application/json');

// Check if customer is logged in
if (!isset($_SESSION['customer_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please log in to continue']);
    exit();
}

$customer_id = $_SESSION['customer_id'];

// Get request data
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

try {
    switch ($action) {
        case 'toggle':
            $property_id = $input['property_id'] ?? 0;
            if (!$property_id) {
                echo json_encode(['success' => false, 'message' => 'Property ID is required']);
                exit();
            }
            
            // Check if property exists in wishlist
            $checkQuery = "SELECT id FROM wishlists WHERE customer_id = ? AND property_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$customer_id, $property_id]);
            $existingWishlist = $checkStmt->fetch();
            
            if ($existingWishlist) {
                // Remove from wishlist
                $deleteQuery = "DELETE FROM wishlists WHERE customer_id = ? AND property_id = ?";
                $deleteStmt = $pdo->prepare($deleteQuery);
                $deleteStmt->execute([$customer_id, $property_id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property removed from wishlist',
                    'added' => false
                ]);
            } else {
                // Add to wishlist
                $insertQuery = "INSERT INTO wishlists (customer_id, property_id, created_at) VALUES (?, ?, NOW())";
                $insertStmt = $pdo->prepare($insertQuery);
                $insertStmt->execute([$customer_id, $property_id]);
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property added to wishlist',
                    'added' => true
                ]);
            }
            break;
            
        case 'remove':
            $property_id = $input['property_id'] ?? 0;
            if (!$property_id) {
                echo json_encode(['success' => false, 'message' => 'Property ID is required']);
                exit();
            }
            
            $deleteQuery = "DELETE FROM wishlists WHERE customer_id = ? AND property_id = ?";
            $deleteStmt = $pdo->prepare($deleteQuery);
            $deleteStmt->execute([$customer_id, $property_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Property removed from wishlist'
            ]);
            break;
            
        case 'clear':
            $clearQuery = "DELETE FROM wishlists WHERE customer_id = ?";
            $clearStmt = $pdo->prepare($clearQuery);
            $clearStmt->execute([$customer_id]);
            
            echo json_encode([
                'success' => true, 
                'message' => 'Wishlist cleared successfully'
            ]);
            break;
            
        case 'get_count':
            $countQuery = "SELECT COUNT(*) FROM wishlists WHERE customer_id = ?";
            $countStmt = $pdo->prepare($countQuery);
            $countStmt->execute([$customer_id]);
            $count = $countStmt->fetchColumn();
            
            echo json_encode([
                'success' => true, 
                'count' => $count
            ]);
            break;
            
        case 'check_property':
            $property_id = $input['property_id'] ?? 0;
            if (!$property_id) {
                echo json_encode(['success' => false, 'message' => 'Property ID is required']);
                exit();
            }
            
            $checkQuery = "SELECT id FROM wishlists WHERE customer_id = ? AND property_id = ?";
            $checkStmt = $pdo->prepare($checkQuery);
            $checkStmt->execute([$customer_id, $property_id]);
            $inWishlist = $checkStmt->fetch() ? true : false;
            
            echo json_encode([
                'success' => true, 
                'in_wishlist' => $inWishlist
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>