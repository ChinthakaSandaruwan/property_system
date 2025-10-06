<?php
// Test the settings update functionality
require_once 'includes/config.php';

echo "Testing settings update...\n\n";

// Simulate the form data that would be sent from the frontend
$test_data = [
    'type' => 'general',
    'site_name' => 'My Updated Site Name',
    'site_url' => 'http://localhost/rental_system',
    'commission_percentage' => '12.5',
    'currency' => 'LKR'
];

echo "Test data to save:\n";
print_r($test_data);

// Test the same logic that's used in settings_actions.php
try {
    $pdo->beginTransaction();
    
    $type = $test_data['type'];
    
    if ($type === 'general') {
        $settings = [
            'site_name' => $test_data['site_name'] ?? '',
            'site_url' => $test_data['site_url'] ?? '',
            'commission_percentage' => $test_data['commission_percentage'] ?? '',
            'currency' => $test_data['currency'] ?? 'LKR'
        ];
        
        foreach ($settings as $name => $value) {
            if ($value !== '') {
                echo "Updating setting: $name = $value\n";
                
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_name, setting_value, description) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    setting_value = VALUES(setting_value),
                    description = COALESCE(NULLIF(VALUES(description), ''), description)
                ");
                
                $result = $stmt->execute([$name, $value, '']);
                
                if (!$result) {
                    echo "Failed to update $name\n";
                } else {
                    echo "Successfully updated $name\n";
                }
            }
        }
    }
    
    $pdo->commit();
    echo "\nTransaction committed successfully!\n";
    
    // Verify the changes
    echo "\nVerifying changes:\n";
    $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM system_settings WHERE setting_name IN ('site_name', 'site_url', 'commission_percentage', 'currency')");
    $stmt->execute();
    $updated_settings = $stmt->fetchAll();
    
    foreach ($updated_settings as $setting) {
        echo $setting['setting_name'] . " = " . $setting['setting_value'] . "\n";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "Error: " . $e->getMessage() . "\n";
}
?>