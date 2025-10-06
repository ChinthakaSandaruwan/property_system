<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

try {
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        
        // Get basic user statistics
        $stats = [];
        
        // Total users by type
        $stmt = $pdo->prepare("
            SELECT 
                user_type,
                COUNT(*) as count
            FROM users 
            GROUP BY user_type
        ");
        $stmt->execute();
        $userTypes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['user_types'] = [];
        $totalUsers = 0;
        foreach ($userTypes as $type) {
            $stats['user_types'][$type['user_type']] = intval($type['count']);
            $totalUsers += intval($type['count']);
        }
        $stats['total_users'] = $totalUsers;
        
        // Users by status
        $stmt = $pdo->prepare("
            SELECT 
                status,
                COUNT(*) as count
            FROM users 
            GROUP BY status
        ");
        $stmt->execute();
        $userStatuses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['user_statuses'] = [];
        foreach ($userStatuses as $status) {
            $stats['user_statuses'][$status['status']] = intval($status['count']);
        }
        
        // Phone verification stats
        $stmt = $pdo->prepare("
            SELECT 
                is_phone_verified,
                COUNT(*) as count
            FROM users 
            GROUP BY is_phone_verified
        ");
        $stmt->execute();
        $verificationStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['phone_verification'] = [
            'verified' => 0,
            'unverified' => 0
        ];
        foreach ($verificationStats as $verif) {
            if ($verif['is_phone_verified']) {
                $stats['phone_verification']['verified'] = intval($verif['count']);
            } else {
                $stats['phone_verification']['unverified'] = intval($verif['count']);
            }
        }
        
        // Registration trends (last 12 months)
        $stmt = $pdo->prepare("
            SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as registrations
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
            GROUP BY DATE_FORMAT(created_at, '%Y-%m')
            ORDER BY month ASC
        ");
        $stmt->execute();
        $trends = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $stats['registration_trends'] = $trends;
        
        // Recent registrations (last 30 days)
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as count
            FROM users 
            WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        ");
        $stmt->execute();
        $stats['recent_registrations'] = intval($stmt->fetchColumn());
        
        // Most active users (users with most properties or bookings)
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.user_type,
                COALESCE(p.property_count, 0) as properties,
                COALESCE(b.booking_count, 0) as bookings,
                (COALESCE(p.property_count, 0) + COALESCE(b.booking_count, 0)) as total_activity
            FROM users u
            LEFT JOIN (
                SELECT owner_id, COUNT(*) as property_count
                FROM properties 
                GROUP BY owner_id
            ) p ON u.id = p.owner_id
            LEFT JOIN (
                SELECT customer_id, COUNT(*) as booking_count
                FROM bookings 
                GROUP BY customer_id
            ) b ON u.id = b.customer_id
            WHERE u.status = 'active'
            ORDER BY total_activity DESC
            LIMIT 10
        ");
        $stmt->execute();
        $stats['most_active_users'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Property owners statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT u.id) as total_owners,
                COUNT(p.id) as total_properties,
                ROUND(AVG(property_counts.property_count), 2) as avg_properties_per_owner
            FROM users u
            LEFT JOIN properties p ON u.id = p.owner_id
            LEFT JOIN (
                SELECT owner_id, COUNT(*) as property_count
                FROM properties
                GROUP BY owner_id
            ) property_counts ON u.id = property_counts.owner_id
            WHERE u.user_type = 'owner' AND u.status = 'active'
        ");
        $stmt->execute();
        $ownerStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['owner_statistics'] = $ownerStats;
        
        // Customer statistics
        $stmt = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT u.id) as total_customers,
                COUNT(b.id) as total_bookings,
                COUNT(pv.id) as total_visits,
                ROUND(AVG(booking_counts.booking_count), 2) as avg_bookings_per_customer
            FROM users u
            LEFT JOIN bookings b ON u.id = b.customer_id
            LEFT JOIN property_visits pv ON u.id = pv.customer_id
            LEFT JOIN (
                SELECT customer_id, COUNT(*) as booking_count
                FROM bookings
                GROUP BY customer_id
            ) booking_counts ON u.id = booking_counts.customer_id
            WHERE u.user_type = 'customer' AND u.status = 'active'
        ");
        $stmt->execute();
        $customerStats = $stmt->fetch(PDO::FETCH_ASSOC);
        $stats['customer_statistics'] = $customerStats;
        
        // Users by city (top 10)
        $stmt = $pdo->prepare("
            SELECT 
                city,
                COUNT(*) as user_count
            FROM users 
            WHERE city IS NOT NULL AND city != ''
            GROUP BY city
            ORDER BY user_count DESC
            LIMIT 10
        ");
        $stmt->execute();
        $stats['users_by_city'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Recent user activity
        $stmt = $pdo->prepare("
            SELECT 
                u.id,
                u.full_name,
                u.email,
                u.user_type,
                u.created_at,
                u.updated_at,
                DATEDIFF(NOW(), u.created_at) as days_since_registration
            FROM users u
            WHERE u.status = 'active'
            ORDER BY u.updated_at DESC
            LIMIT 20
        ");
        $stmt->execute();
        $stats['recent_activity'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response = [
            'success' => true,
            'data' => $stats
        ];
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