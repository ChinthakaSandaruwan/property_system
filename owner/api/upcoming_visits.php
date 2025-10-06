<?php
header('Content-Type: application/json');

require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 0;

try {
    $stmt = $pdo->prepare("
        SELECT 
            pv.id,
            pv.requested_date,
            p.title as property_name,
            u.full_name as customer_name,
            pv.customer_notes
        FROM property_visits pv
        JOIN properties p ON pv.property_id = p.id
        JOIN users u ON pv.customer_id = u.id
        WHERE p.owner_id = ? AND pv.status = 'pending'
        AND pv.requested_date >= NOW()
        ORDER BY pv.requested_date ASC
        LIMIT 5
    ");
    $stmt->execute([$owner_id]);
    $visits = $stmt->fetchAll();

    $formattedVisits = [];
    foreach ($visits as $visit) {
        $formattedVisits[] = [
            'id' => $visit['id'],
            'property_name' => $visit['property_name'],
            'customer_name' => $visit['customer_name'],
            'visit_date' => date('M j, g:i A', strtotime($visit['requested_date'])),
            'notes' => $visit['customer_notes'] ?? 'No notes provided'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $formattedVisits
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'data' => []
    ]);
}
?>