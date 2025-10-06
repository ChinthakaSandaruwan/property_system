<?php
require_once 'includes/config.php';

echo "Checking system_settings table structure...\n\n";

try {
    // Check table structure
    $stmt = $pdo->query("DESCRIBE system_settings");
    $columns = $stmt->fetchAll();
    
    echo "Table columns:\n";
    foreach ($columns as $col) {
        echo "- " . $col['Field'] . " (" . $col['Type'] . ") " . ($col['Key'] ? "[" . $col['Key'] . "]" : "") . "\n";
    }
    
    // Check indexes
    $stmt = $pdo->query("SHOW INDEX FROM system_settings");
    $indexes = $stmt->fetchAll();
    
    echo "\nTable indexes:\n";
    foreach ($indexes as $idx) {
        echo "- " . $idx['Key_name'] . " on " . $idx['Column_name'] . " (Unique: " . ($idx['Non_unique'] ? 'No' : 'Yes') . ")\n";
    }
    
    // Check if setting_name has unique constraint
    $hasUnique = false;
    foreach ($indexes as $idx) {
        if ($idx['Column_name'] === 'setting_name' && $idx['Non_unique'] == 0) {
            $hasUnique = true;
            break;
        }
    }
    
    if (!$hasUnique) {
        echo "\nAdding unique constraint to setting_name...\n";
        $pdo->exec("ALTER TABLE system_settings ADD UNIQUE KEY unique_setting_name (setting_name)");
        echo "Unique constraint added successfully!\n";
    } else {
        echo "\nUnique constraint already exists on setting_name.\n";
    }
    
    // Test the update function
    echo "\nTesting site_name update...\n";
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_name, setting_value, description) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value),
        description = COALESCE(NULLIF(VALUES(description), ''), description)
    ");
    
    $result = $stmt->execute(['site_name', 'Test Site Name', 'Updated test description']);
    
    if ($result) {
        echo "Update test successful!\n";
        
        // Check the updated value
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_name = 'site_name'");
        $stmt->execute();
        $value = $stmt->fetchColumn();
        
        echo "Current site_name value: " . $value . "\n";
        
        // Restore original value
        $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = 'SmartRent' WHERE setting_name = 'site_name'");
        $stmt->execute();
        echo "Restored original value.\n";
    } else {
        echo "Update test failed!\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>