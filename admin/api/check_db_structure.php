<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

try {
    // Get the table structure
    $stmt = $pdo->query("DESCRIBE properties");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $columnNames = array_column($columns, 'Field');
    
    // Check if contact_phone column exists
    $contactPhoneExists = in_array('contact_phone', $columnNames);
    
    // Add contact_phone column if it doesn't exist
    if (!$contactPhoneExists) {
        $alterQuery = "ALTER TABLE properties ADD COLUMN contact_phone VARCHAR(20) AFTER zip_code";
        $pdo->exec($alterQuery);
        $columnNames[] = 'contact_phone';
        error_log("Added contact_phone column to properties table");
    }
    
    // Check for other potentially missing columns
    $expectedColumns = [
        'id', 'owner_id', 'title', 'description', 'property_type', 'bedrooms', 
        'bathrooms', 'area_sqft', 'address', 'city', 'state', 'zip_code', 
        'contact_phone', 'rent_amount', 'security_deposit', 'images', 
        'amenities', 'status', 'is_available', 'created_at', 'updated_at'
    ];
    
    $missingColumns = array_diff($expectedColumns, $columnNames);
    $extraColumns = array_diff($columnNames, $expectedColumns);
    
    // Try to add commonly missing columns
    foreach ($missingColumns as $column) {
        try {
            switch ($column) {
                case 'updated_at':
                    $pdo->exec("ALTER TABLE properties ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
                    error_log("Added updated_at column to properties table");
                    break;
                case 'images':
                    $pdo->exec("ALTER TABLE properties ADD COLUMN images TEXT");
                    error_log("Added images column to properties table");
                    break;
                case 'amenities':
                    $pdo->exec("ALTER TABLE properties ADD COLUMN amenities TEXT");
                    error_log("Added amenities column to properties table");
                    break;
            }
        } catch (Exception $e) {
            error_log("Failed to add column $column: " . $e->getMessage());
        }
    }
    
    // Get updated structure
    $stmt = $pdo->query("DESCRIBE properties");
    $updatedColumns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'message' => 'Database structure checked and updated',
        'data' => [
            'original_columns' => $columns,
            'updated_columns' => $updatedColumns,
            'contact_phone_existed' => $contactPhoneExists,
            'missing_columns' => $missingColumns,
            'extra_columns' => $extraColumns
        ]
    ]);
    
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage(),
        'error_code' => $e->getCode()
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ]);
}
?>