<?php
/**
 * Settings Helper Functions
 * Provides easy access to system settings from the database
 */

require_once 'config.php';

/**
 * Get a specific setting value from the database
 * 
 * @param string $setting_name The name of the setting to retrieve
 * @param string $default_value Default value to return if setting doesn't exist
 * @return string The setting value or default value
 */
function getSetting($setting_name, $default_value = '') {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_name = ?");
        $stmt->execute([$setting_name]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            return $result['setting_value'];
        }
        
        return $default_value;
    } catch (PDOException $e) {
        error_log("Error getting setting $setting_name: " . $e->getMessage());
        return $default_value;
    }
}

/**
 * Get all settings as an associative array
 * 
 * @return array Array of setting_name => setting_value pairs
 */
function getAllSettings() {
    global $pdo;
    
    try {
        $stmt = $pdo->query("SELECT setting_name, setting_value FROM system_settings");
        $settings = [];
        
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['setting_name']] = $row['setting_value'];
        }
        
        return $settings;
    } catch (PDOException $e) {
        error_log("Error getting all settings: " . $e->getMessage());
        return [];
    }
}

/**
 * Get common site settings
 * 
 * @return array Common settings with fallback defaults
 */
function getSiteSettings() {
    return [
        'site_name' => getSetting('site_name', 'PropertyRental'),
        'site_url' => getSetting('site_url', 'http://localhost/rental_system'),
        'commission_percentage' => getSetting('commission_percentage', '10'),
        'currency' => getSetting('currency', 'LKR'),
        'admin_email' => getSetting('admin_email', 'admin@smartrent.com'),
        'support_phone' => getSetting('support_phone', '+94 11 234 5678'),
        'company_address' => getSetting('company_address', '123 Main Street, Colombo, Sri Lanka'),
        // PayHere Settings
        'payhere_merchant_id' => getSetting('payhere_merchant_id', ''),
        'payhere_merchant_secret' => getSetting('payhere_merchant_secret', ''),
        'payhere_mode' => getSetting('payhere_mode', 'sandbox'),
        'payhere_currency' => getSetting('payhere_currency', 'LKR'),
        'payhere_enabled' => getSetting('payhere_enabled', '1')
    ];
}

/**
 * Get the site name (most commonly used setting)
 * 
 * @return string The site name
 */
function getSiteName() {
    return getSetting('site_name', 'PropertyRental');
}
?>