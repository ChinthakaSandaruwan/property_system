<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/config.php';

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$action = $_POST['action'] ?? $_GET['action'] ?? null;
$property_id = $_POST['property_id'] ?? $_GET['property_id'] ?? null;
$customer_id = $_SESSION['user_id'];

if (!$action || !$property_id) {
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    switch ($action) {
        case 'add':
            // Add property to wishlist
            $stmt = $pdo->prepare("INSERT IGNORE INTO wishlists (customer_id, property_id) VALUES (?, ?)");
            $result = $stmt->execute([$customer_id, $property_id]);
            
            if ($result) {
                // Get wishlist count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
                $countStmt->execute([$customer_id]);
                $count = $countStmt->fetchColumn();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property added to wishlist',
                    'in_wishlist' => true,
                    'wishlist_count' => $count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to add to wishlist']);
            }
            break;
            
        case 'remove':
            // Remove property from wishlist
            $stmt = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND property_id = ?");
            $result = $stmt->execute([$customer_id, $property_id]);
            
            if ($result) {
                // Get wishlist count
                $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
                $countStmt->execute([$customer_id]);
                $count = $countStmt->fetchColumn();
                
                echo json_encode([
                    'success' => true, 
                    'message' => 'Property removed from wishlist',
                    'in_wishlist' => false,
                    'wishlist_count' => $count
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to remove from wishlist']);
            }
            break;
            
        case 'check':
            // Check if property is in wishlist
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ? AND property_id = ?");
            $stmt->execute([$customer_id, $property_id]);
            $exists = $stmt->fetchColumn() > 0;
            
            // Get wishlist count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
            $countStmt->execute([$customer_id]);
            $count = $countStmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'in_wishlist' => $exists,
                'wishlist_count' => $count
            ]);
            break;
            
        case 'count':
            // Get wishlist count only
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
            $stmt->execute([$customer_id]);
            $count = $stmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'wishlist_count' => $count
            ]);
            break;
            
        case 'toggle':
            // Toggle wishlist status
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ? AND property_id = ?");
            $stmt->execute([$customer_id, $property_id]);
            $exists = $stmt->fetchColumn() > 0;
            
            if ($exists) {
                // Remove from wishlist
                $stmt = $pdo->prepare("DELETE FROM wishlists WHERE customer_id = ? AND property_id = ?");
                $stmt->execute([$customer_id, $property_id]);
                $message = 'Property removed from wishlist';
                $in_wishlist = false;
            } else {
                // Add to wishlist
                $stmt = $pdo->prepare("INSERT INTO wishlists (customer_id, property_id) VALUES (?, ?)");
                $stmt->execute([$customer_id, $property_id]);
                $message = 'Property added to wishlist';
                $in_wishlist = true;
            }
            
            // Get updated wishlist count
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
            $countStmt->execute([$customer_id]);
            $count = $countStmt->fetchColumn();
            
            echo json_encode([
                'success' => true,
                'message' => $message,
                'in_wishlist' => $in_wishlist,
                'wishlist_count' => $count
            ]);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            break;
    }
    
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?>