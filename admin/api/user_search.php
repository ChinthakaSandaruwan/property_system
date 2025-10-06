<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

try {
    // Get search parameters
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $type = isset($_GET['type']) ? trim($_GET['type']) : '';
    $status = isset($_GET['status']) ? trim($_GET['status']) : '';
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 50;
    $offset = ($page - 1) * $limit;
    
    // Build the WHERE clause
    $whereConditions = [];
    $params = [];
    
    // Search query
    if (!empty($search)) {
        $whereConditions[] = "(full_name LIKE ? OR email LIKE ? OR phone LIKE ?)";
        $searchParam = "%{$search}%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    // User type filter
    if (!empty($type) && in_array($type, ['admin', 'owner', 'customer'])) {
        $whereConditions[] = "user_type = ?";
        $params[] = $type;
    }
    
    // Status filter
    if (!empty($status) && in_array($status, ['active', 'inactive', 'suspended'])) {
        $whereConditions[] = "status = ?";
        $params[] = $status;
    }
    
    // Build the final WHERE clause
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Get total count for pagination
    $countSql = "SELECT COUNT(*) as total FROM users {$whereClause}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalUsers = $countStmt->fetch()['total'];
    
    // Get users with search and filters
    $sql = "
        SELECT 
            id,
            full_name,
            email,
            phone,
            user_type,
            status,
            created_at,
            updated_at as last_login
        FROM users 
        {$whereClause}
        ORDER BY 
            CASE 
                WHEN ? != '' AND full_name LIKE ? THEN 1
                WHEN ? != '' AND email LIKE ? THEN 2
                WHEN ? != '' AND phone LIKE ? THEN 3
                ELSE 4
            END,
            created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    // Add search relevance parameters
    $searchRelevanceParams = array_merge($params, [
        $search, "%{$search}%",
        $search, "%{$search}%", 
        $search, "%{$search}%"
    ]);
    
    // Modify SQL to use proper placeholders for LIMIT and OFFSET
    $sql = str_replace(['LIMIT ?', 'OFFSET ?'], ["LIMIT {$limit}", "OFFSET {$offset}"], $sql);
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($searchRelevanceParams);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate pagination info
    $totalPages = ceil($totalUsers / $limit);
    $hasMore = $page < $totalPages;
    
    // Response
    $response = [
        'success' => true,
        'data' => [
            'users' => $users,
            'pagination' => [
                'current_page' => $page,
                'total_pages' => $totalPages,
                'total_users' => $totalUsers,
                'limit' => $limit,
                'has_more' => $hasMore
            ],
            'filters' => [
                'search' => $search,
                'type' => $type,
                'status' => $status
            ]
        ],
        'message' => "Found {$totalUsers} user(s)"
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database error occurred',
        'error' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred',
        'error' => $e->getMessage()
    ]);
}
?>