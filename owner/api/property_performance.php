<?php
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.title as property_name,
            COUNT(b.id) as bookings_count,
            COALESCE(SUM(b.total_amount), 0) as revenue
        FROM properties p
        LEFT JOIN bookings b ON p.id = b.property_id AND b.status IN ('active', 'completed')
        WHERE p.owner_id = ?
        GROUP BY p.id, p.title
        ORDER BY revenue DESC
        LIMIT 5
    ");
    $stmt->execute([$owner_id]);
    $performance = $stmt->fetchAll();

    $formattedPerformance = [];
    foreach ($performance as $property) {
        $formattedPerformance[] = [
            'property_name' => $property['property_name'],
            'bookings_count' => $property['bookings_count'],
            'revenue' => number_format($property['revenue'], 0)
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedPerformance
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>