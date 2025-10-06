<?php
require_once 'includes/config.php';

echo "Checking site_name setting...\n\n";

// Check if site_name exists
$stmt = $pdo->prepare("SELECT * FROM system_settings WHERE setting_name = 'site_name'");
$stmt->execute();
$setting = $stmt->fetch();

if ($setting) {
    echo "Site name setting found:\n";
    echo "Name: " . $setting['setting_name'] . "\n";
    echo "Value: " . $setting['setting_value'] . "\n";
    echo "Description: " . $setting['description'] . "\n";
} else {
    echo "Site name setting not found. Creating it...\n";
    
    // Insert site_name setting
    $stmt = $pdo->prepare("INSERT INTO system_settings (setting_name, setting_value, description) VALUES (?, ?, ?)");
    $result = $stmt->execute(['site_name', 'SmartRent', 'Name of the website']);
    
    if ($result) {
        echo "Site name setting created successfully!\n";
    } else {
        echo "Failed to create site name setting.\n";
    }
}

// List all settings
echo "\n\nAll settings:\n";
$stmt = $pdo->query("SELECT setting_name, setting_value FROM system_settings ORDER BY setting_name");
$settings = $stmt->fetchAll();

foreach ($settings as $setting) {
    echo $setting['setting_name'] . " = " . $setting['setting_value'] . "\n";
}
?>