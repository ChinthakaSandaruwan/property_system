<?php
require_once '../../includes/functions.php';

// Authentication check for admin
require_auth('admin');

header('Content-Type: application/json');

$response = ['success' => false, 'message' => ''];

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? '';

    switch ($action) {
        case 'get_pending_owners':
            $stmt = $pdo->prepare("
                SELECT u.*, 
                       DATE_FORMAT(u.created_at, '%Y-%m-%d %H:%i') as registration_date
                FROM users u 
                WHERE u.user_type = 'owner' 
                AND u.status = 'pending' 
                ORDER BY u.created_at ASC
            ");
            $stmt->execute();
            $pending_owners = $stmt->fetchAll();
            
            $response = [
                'success' => true,
                'data' => $pending_owners,
                'count' => count($pending_owners)
            ];
            break;

        case 'get_all_owners':
            $stmt = $pdo->prepare("
                SELECT u.*, 
                       DATE_FORMAT(u.created_at, '%Y-%m-%d %H:%i') as registration_date,
                       DATE_FORMAT(u.approved_at, '%Y-%m-%d %H:%i') as approval_date,
                       approver.full_name as approved_by_name
                FROM users u 
                LEFT JOIN users approver ON u.approved_by = approver.id
                WHERE u.user_type = 'owner' 
                ORDER BY 
                    CASE u.status 
                        WHEN 'pending' THEN 1 
                        WHEN 'approved' THEN 2 
                        WHEN 'rejected' THEN 3 
                        ELSE 4 
                    END,
                    u.created_at DESC
            ");
            $stmt->execute();
            $all_owners = $stmt->fetchAll();
            
            // Group by status
            $grouped_owners = [
                'pending' => [],
                'approved' => [],
                'rejected' => [],
                'other' => []
            ];
            
            foreach ($all_owners as $owner) {
                $status = $owner['status'];
                if (isset($grouped_owners[$status])) {
                    $grouped_owners[$status][] = $owner;
                } else {
                    $grouped_owners['other'][] = $owner;
                }
            }
            
            $response = [
                'success' => true,
                'data' => $grouped_owners,
                'total_count' => count($all_owners)
            ];
            break;

        case 'approve_owner':
            $owner_id = intval($data['owner_id'] ?? 0);
            $admin_id = $_SESSION['user_id'];
            
            if ($owner_id <= 0) {
                $response['message'] = 'Invalid owner ID';
                break;
            }
            
            // Check if owner exists and is pending
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'owner' AND status = 'pending'");
            $stmt->execute([$owner_id]);
            $owner = $stmt->fetch();
            
            if (!$owner) {
                $response['message'] = 'Owner not found or not in pending status';
                break;
            }
            
            // Approve the owner
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status = 'approved', 
                    approved_by = ?, 
                    approved_at = CURRENT_TIMESTAMP,
                    rejection_reason = NULL
                WHERE id = ?
            ");
            
            if ($stmt->execute([$admin_id, $owner_id])) {
                $response = [
                    'success' => true,
                    'message' => 'Owner approved successfully',
                    'owner_name' => $owner['full_name']
                ];
                
                // TODO: Send email notification to owner
            } else {
                $response['message'] = 'Failed to approve owner';
            }
            break;

        case 'reject_owner':
            $owner_id = intval($data['owner_id'] ?? 0);
            $rejection_reason = sanitize_input($data['rejection_reason'] ?? '');
            $admin_id = $_SESSION['user_id'];
            
            if ($owner_id <= 0) {
                $response['message'] = 'Invalid owner ID';
                break;
            }
            
            if (empty($rejection_reason)) {
                $response['message'] = 'Rejection reason is required';
                break;
            }
            
            // Check if owner exists and is pending
            $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND user_type = 'owner' AND status = 'pending'");
            $stmt->execute([$owner_id]);
            $owner = $stmt->fetch();
            
            if (!$owner) {
                $response['message'] = 'Owner not found or not in pending status';
                break;
            }
            
            // Reject the owner
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status = 'rejected', 
                    approved_by = ?, 
                    approved_at = CURRENT_TIMESTAMP,
                    rejection_reason = ?
                WHERE id = ?
            ");
            
            if ($stmt->execute([$admin_id, $rejection_reason, $owner_id])) {
                $response = [
                    'success' => true,
                    'message' => 'Owner rejected',
                    'owner_name' => $owner['full_name']
                ];
                
                // TODO: Send email notification to owner
            } else {
                $response['message'] = 'Failed to reject owner';
            }
            break;

        case 'reactivate_owner':
            $owner_id = intval($data['owner_id'] ?? 0);
            $admin_id = $_SESSION['user_id'];
            
            if ($owner_id <= 0) {
                $response['message'] = 'Invalid owner ID';
                break;
            }
            
            // Reactivate the owner (move from rejected back to pending)
            $stmt = $pdo->prepare("
                UPDATE users 
                SET status = 'pending', 
                    approved_by = NULL, 
                    approved_at = NULL,
                    rejection_reason = NULL
                WHERE id = ? AND user_type = 'owner'
            ");
            
            if ($stmt->execute([$owner_id])) {
                $response = [
                    'success' => true,
                    'message' => 'Owner reactivated and moved to pending status'
                ];
            } else {
                $response['message'] = 'Failed to reactivate owner';
            }
            break;

        case 'get_stats':
            $stmt = $pdo->prepare("
                SELECT 
                    status,
                    COUNT(*) as count
                FROM users 
                WHERE user_type = 'owner' 
                GROUP BY status
            ");
            $stmt->execute();
            $stats = $stmt->fetchAll();
            
            $formatted_stats = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
            
            foreach ($stats as $stat) {
                if (isset($formatted_stats[$stat['status']])) {
                    $formatted_stats[$stat['status']] = intval($stat['count']);
                }
                $formatted_stats['total'] += intval($stat['count']);
            }
            
            $response = [
                'success' => true,
                'data' => $formatted_stats
            ];
            break;

        default:
            $response['message'] = 'Invalid action';
            break;
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ];
}

echo json_encode($response);
?>