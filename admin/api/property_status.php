<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

try {
    // Get property status counts
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM properties 
        GROUP BY status
    ");
    $stmt->execute();
    $results = $stmt->fetchAll();

    // Initialize counts
    $statusCounts = [
        'active' => 0,
        'pending' => 0,
        'inactive' => 0,
        'approved' => 0
    ];

    // Map the database results
    foreach ($results as $row) {
        $status = strtolower($row['status']);
        if (in_array($status, ['approved'])) {
            $statusCounts['active'] += $row['count'];
        } elseif ($status === 'pending') {
            $statusCounts['pending'] += $row['count'];
        } elseif (in_array($status, ['rejected', 'rented'])) {
            $statusCounts['inactive'] += $row['count'];
        }
    }

    $response = [
        'success' => true,
        'data' => $statusCounts
    ];

} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'data' => [
            'active' => 0,
            'pending' => 0,
            'inactive' => 0
        ]
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage(),
        'data' => [
            'active' => 0,
            'pending' => 0,
            'inactive' => 0
        ]
    ];
}

echo json_encode($response);
?>