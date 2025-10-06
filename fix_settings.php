<?php
/**
 * Comprehensive Settings Fix Script
 * This script addresses the site name update issue and provides debugging tools
 */

require_once 'includes/config.php';
require_once 'includes/settings_helper.php';

$action = $_GET['action'] ?? 'test';
$message = '';
$error = '';

switch ($action) {
    case 'test':
        // Test current setup
        break;
        
    case 'fix_site_name':
        // Direct site name update
        if ($_POST && isset($_POST['new_site_name'])) {
            $new_site_name = trim($_POST['new_site_name']);
            if (!empty($new_site_name)) {
                try {
                    $stmt = $pdo->prepare("
                        INSERT INTO system_settings (setting_name, setting_value, description) 
                        VALUES ('site_name', ?, 'The name of the website')
                        ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)
                    ");
                    
                    if ($stmt->execute([$new_site_name])) {
                        $message = "✅ Site name updated successfully to: " . htmlspecialchars($new_site_name);
                    } else {
                        $error = "❌ Failed to update site name in database";
                    }
                } catch (Exception $e) {
                    $error = "❌ Database error: " . $e->getMessage();
                }
            } else {
                $error = "❌ Site name cannot be empty";
            }
        }
        break;
        
    case 'fix_database':
        // Ensure all required settings exist
        $default_settings = [
            'site_name' => 'SmartRent',
            'site_url' => 'http://localhost/rental_system',
            'commission_percentage' => '10',
            'currency' => 'LKR',
            'admin_email' => 'admin@smartrent.com',
            'support_phone' => '+94 77 123 4567',
            'company_address' => '123 Main Street, Colombo, Sri Lanka'
        ];
        
        $added_count = 0;
        $updated_count = 0;
        
        foreach ($default_settings as $name => $value) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO system_settings (setting_name, setting_value, description) 
                    VALUES (?, ?, ?) 
                    ON DUPLICATE KEY UPDATE 
                    setting_value = IF(setting_value = '' OR setting_value IS NULL, VALUES(setting_value), setting_value)
                ");
                
                $description = ucwords(str_replace('_', ' ', $name));
                $result = $stmt->execute([$name, $value, $description]);
                
                if ($result) {
                    if ($stmt->rowCount() > 0) {
                        $added_count++;
                    }
                }
            } catch (Exception $e) {
                $error .= "Error with $name: " . $e->getMessage() . "<br>";
            }
        }
        
        if (!$error) {
            $message = "✅ Database fixed! Added/Updated $added_count settings.";
        }
        break;
}

// Get current settings
$current_site_name = getSiteName();
$all_settings = getAllSettings();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings Fix Tool</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .card { background: white; border-radius: 8px; padding: 20px; margin: 20px 0; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; border-radius: 5px; padding: 15px; margin: 10px 0; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; border-radius: 5px; padding: 15px; margin: 10px 0; }
        .warning { background: #fff3cd; color: #856404; border: 1px solid #ffeeba; border-radius: 5px; padding: 15px; margin: 10px 0; }
        .btn { padding: 10px 20px; margin: 5px; background: #007bff; color: white; border: none; border-radius: 5px; cursor: pointer; text-decoration: none; display: inline-block; }
        .btn:hover { background: #0056b3; }
        .btn-success { background: #28a745; }
        .btn-success:hover { background: #1e7e34; }
        .btn-warning { background: #ffc107; color: #212529; }
        .btn-warning:hover { background: #e0a800; }
        .form-group { margin: 15px 0; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; font-size: 12px; }
        .navbar-preview { background: #667eea; color: white; padding: 15px; border-radius: 5px; margin: 10px 0; display: flex; align-items: center; gap: 10px; }
        .navbar-preview .logo { font-size: 24px; font-weight: bold; }
        iframe { width: 100%; height: 400px; border: 1px solid #ddd; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Settings Fix Tool</h1>
        
        <?php if ($message): ?>
            <div class="success"><?= $message ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error"><?= $error ?></div>
        <?php endif; ?>
        
        <div class="grid">
            <!-- Current Status -->
            <div class="card">
                <h2>📊 Current Status</h2>
                <p><strong>Current Site Name:</strong> <?= htmlspecialchars($current_site_name) ?></p>
                <p><strong>Settings in Database:</strong> <?= count($all_settings) ?></p>
                
                <h3>🌐 How it appears in navbar:</h3>
                <div class="navbar-preview">
                    <i>🏠</i>
                    <span class="logo"><?= htmlspecialchars($current_site_name) ?></span>
                </div>
                
                <h3>🗄️ All Settings:</h3>
                <pre><?= htmlspecialchars(json_encode($all_settings, JSON_PRETTY_PRINT)) ?></pre>
            </div>
            
            <!-- Quick Fixes -->
            <div class="card">
                <h2>⚡ Quick Fixes</h2>
                
                <!-- Direct Site Name Update -->
                <form method="POST" action="?action=fix_site_name">
                    <h3>Update Site Name</h3>
                    <div class="form-group">
                        <label>New Site Name:</label>
                        <input type="text" name="new_site_name" value="<?= htmlspecialchars($current_site_name) ?>" required>
                    </div>
                    <button type="submit" class="btn btn-success">Update Site Name</button>
                </form>
                
                <hr>
                
                <!-- Database Fix -->
                <h3>Fix Database Settings</h3>
                <p>This will add any missing default settings to the database.</p>
                <a href="?action=fix_database" class="btn btn-warning" onclick="return confirm('Add missing default settings?')">Fix Database Settings</a>
                
                <hr>
                
                <!-- Admin Dashboard -->
                <h3>Admin Dashboard</h3>
                <a href="admin/dashboard.php#settings" target="_blank" class="btn">Open Admin Settings</a>
                <a href="admin/settings_debug.html" target="_blank" class="btn">Debug Panel</a>
            </div>
        </div>
        
        <!-- Testing Section -->
        <div class="card">
            <h2>🧪 Test Results</h2>
            <div class="grid">
                <div>
                    <h3>Homepage Test</h3>
                    <iframe src="index.php"></iframe>
                </div>
                <div>
                    <h3>Admin Settings Test</h3>
                    <iframe src="admin/api/settings_content.php"></iframe>
                </div>
            </div>
        </div>
        
        <!-- Instructions -->
        <div class="card">
            <h2>📋 Step-by-Step Instructions</h2>
            <div class="warning">
                <h3>If you still can't change the site name in the admin dashboard:</h3>
                <ol>
                    <li><strong>Update directly here:</strong> Use the "Update Site Name" form above</li>
                    <li><strong>Check admin login:</strong> Make sure you're logged in as an admin user</li>
                    <li><strong>Clear browser cache:</strong> Press Ctrl+F5 to refresh completely</li>
                    <li><strong>Check JavaScript console:</strong>
                        <ul>
                            <li>Go to Admin Dashboard</li>
                            <li>Press F12 → Console tab</li>
                            <li>Look for any red error messages</li>
                        </ul>
                    </li>
                    <li><strong>Try the debug panel:</strong> Use the "Debug Panel" link above</li>
                </ol>
            </div>
        </div>
        
        <!-- Debug Information -->
        <div class="card">
            <h2>🔍 Debug Information</h2>
            <h3>Database Connection:</h3>
            <?php 
            try {
                $test_query = $pdo->query("SELECT COUNT(*) as count FROM system_settings");
                $count = $test_query->fetchColumn();
                echo "<div class='success'>✅ Database connected. Found $count settings.</div>";
            } catch (Exception $e) {
                echo "<div class='error'>❌ Database error: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
            ?>
            
            <h3>Required Files:</h3>
            <?php
            $required_files = [
                'includes/settings_helper.php' => 'Settings helper functions',
                'admin/api/settings_content.php' => 'Settings form content',
                'admin/api/settings_actions.php' => 'Settings API actions',
                'admin/js/dashboard-simple.js' => 'Dashboard JavaScript'
            ];
            
            foreach ($required_files as $file => $desc) {
                if (file_exists($file)) {
                    echo "<div class='success'>✅ $desc: $file</div>";
                } else {
                    echo "<div class='error'>❌ Missing: $desc ($file)</div>";
                }
            }
            ?>
        </div>
    </div>
</body>
</html>