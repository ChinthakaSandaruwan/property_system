<?php
/**
 * Test script to verify site name is being read correctly
 */

require_once 'includes/config.php';
require_once 'includes/settings_helper.php';

// Get site name
$site_name = getSiteName();
$all_settings = getAllSettings();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Name Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .test-result { padding: 15px; margin: 10px 0; border-radius: 5px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 15px; border-radius: 5px; overflow-x: auto; }
        .navbar-demo { 
            background: #fff; 
            padding: 10px 20px; 
            border: 1px solid #ddd; 
            border-radius: 5px; 
            margin: 20px 0; 
            display: flex; 
            align-items: center; 
            gap: 10px;
        }
        .logo { font-size: 24px; font-weight: bold; color: #333; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Site Name Configuration Test</h1>
        
        <div class="test-result success">
            <h3>✓ Site Name Retrieved Successfully</h3>
            <p><strong>Current Site Name:</strong> <?= htmlspecialchars($site_name) ?></p>
        </div>
        
        <div class="navbar-demo">
            <i class="fas fa-home" style="font-size: 20px; color: #667eea;"></i>
            <span class="logo"><?= htmlspecialchars($site_name) ?></span>
            <span style="margin-left: auto; color: #666;">This is how it appears in the navbar</span>
        </div>
        
        <div class="test-result info">
            <h3>📋 All System Settings</h3>
            <pre><?= htmlspecialchars(json_encode($all_settings, JSON_PRETTY_PRINT)) ?></pre>
        </div>
        
        <div class="test-result info">
            <h3>🔗 Pages Updated</h3>
            <ul>
                <li>✓ index.php (homepage navbar, footer, title)</li>
                <li>✓ properties.php (title)</li>
                <li>✓ property_details.php (title)</li>
                <li>✓ customer/rent_property.php (title)</li>
                <li>✓ customer/book_visit.php (title)</li>
                <li>✓ api/sidebar.php (admin/owner/customer sidebars)</li>
            </ul>
        </div>
        
        <div class="test-result info">
            <h3>📝 Instructions</h3>
            <ol>
                <li>Go to Admin Dashboard → Settings</li>
                <li>Change the "Site Name" to your desired name</li>
                <li>Click "Save General Settings"</li>
                <li>Refresh your homepage to see the change</li>
                <li>The site name will now appear in navbar, footer, and page titles</li>
            </ol>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="index.php" style="background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">← Back to Homepage</a>
            <a href="admin/dashboard.php" style="background: #28a745; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-left: 10px;">Admin Settings</a>
        </div>
    </div>
</body>
</html>