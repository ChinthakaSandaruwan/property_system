<?php
require_once 'includes/config.php';
require_once 'includes/settings_helper.php';

echo "<h2>Admin Settings Debug</h2>";

// Test database connection
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    $count = $stmt->fetchColumn();
    echo "<p>✅ Database connected. Found $count settings.</p>";
} catch (Exception $e) {
    echo "<p>❌ Database error: " . $e->getMessage() . "</p>";
}

// Test settings retrieval
try {
    $all_settings = getAllSettings();
    echo "<h3>All Settings:</h3><pre>";
    print_r($all_settings);
    echo "</pre>";
} catch (Exception $e) {
    echo "<p>❌ Settings error: " . $e->getMessage() . "</p>";
}

// Test site name specifically
$site_name = getSiteName();
echo "<h3>Site Name: $site_name</h3>";

// Check if the system_settings table has the right data
try {
    $stmt = $pdo->query("SELECT * FROM system_settings WHERE setting_name = 'site_name'");
    $site_name_setting = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($site_name_setting) {
        echo "<h3>Site Name Setting in DB:</h3><pre>";
        print_r($site_name_setting);
        echo "</pre>";
    } else {
        echo "<p>⚠️ No site_name setting found in database</p>";
        
        // Insert it
        $stmt = $pdo->prepare("INSERT INTO system_settings (setting_name, setting_value, description) VALUES (?, ?, ?)");
        if ($stmt->execute(['site_name', 'SmartRent', 'The name of the website'])) {
            echo "<p>✅ Inserted site_name setting</p>";
        }
    }
} catch (Exception $e) {
    echo "<p>❌ Error checking site_name: " . $e->getMessage() . "</p>";
}

// Test the settings API
echo "<h3>Test Settings API:</h3>";
echo "<p><a href='admin/api/settings_actions.php' target='_blank'>Test Settings Actions API</a></p>";
echo "<p><a href='admin/api/settings_content.php' target='_blank'>Test Settings Content API</a></p>";

// Show direct form
?>
<h3>Direct Settings Update Form:</h3>
<form action="admin/api/settings_actions.php" method="POST">
    <input type="hidden" name="type" value="general">
    <label>Site Name:</label>
    <input type="text" name="site_name" value="<?= htmlspecialchars($site_name) ?>" required>
    <br><br>
    <label>Site URL:</label>
    <input type="text" name="site_url" value="http://localhost/rental_system">
    <br><br>
    <label>Commission Percentage:</label>
    <input type="number" name="commission_percentage" value="10">
    <br><br>
    <label>Currency:</label>
    <select name="currency">
        <option value="LKR">LKR</option>
        <option value="USD">USD</option>
    </select>
    <br><br>
    <button type="submit">Update Settings</button>
</form>