<?php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '123321555');
define('DB_NAME', 'rental_system');

// System Configuration
define('SITE_URL', 'http://localhost/rental_system');
define('UPLOAD_PATH', 'uploads/');
define('COMMISSION_PERCENTAGE', 10);

// SMS Configuration (Replace with your SMS service provider details)
define('SMS_API_URL', 'https://api.example.com/sms/send');
define('SMS_API_KEY', 'your_sms_api_key_here');

// PayHere Configuration - Load from database
// Initialize PayHere settings after database connection is established
function loadPayHereSettings() {
    global $pdo;
    
    try {
        // Get PayHere settings from database
        $stmt = $pdo->prepare("SELECT setting_name, setting_value FROM system_settings WHERE setting_name LIKE 'payhere%'");
        $stmt->execute();
        $settings = $stmt->fetchAll();
        
        $payhere_config = [
            'payhere_merchant_id' => 'your_payhere_merchant_id',
            'payhere_merchant_secret' => 'your_payhere_merchant_secret',
            'payhere_mode' => 'sandbox',
            'payhere_app_code' => 'your_business_app_code',
            'payhere_app_secret' => 'your_business_app_secret'
        ];
        
        // Override with database values
        foreach ($settings as $setting) {
            if (isset($payhere_config[$setting['setting_name']])) {
                $payhere_config[$setting['setting_name']] = $setting['setting_value'];
            }
        }
        
        // Define constants based on database settings
        define('PAYHERE_MERCHANT_ID', $payhere_config['payhere_merchant_id']);
        define('PAYHERE_MERCHANT_SECRET', $payhere_config['payhere_merchant_secret']);
        
        // Set API URLs based on mode (sandbox or live)
        $api_base = ($payhere_config['payhere_mode'] === 'live') 
            ? 'https://www.payhere.lk' 
            : 'https://sandbox.payhere.lk';
            
        define('PAYHERE_API_URL', $api_base);
        define('PAYHERE_CHECKOUT_URL', $api_base . '/pay/checkout');
        define('PAYHERE_RECURRING_API_URL', $api_base . '/pay/recurring');
        define('PAYHERE_BUSINESS_APP_CODE', $payhere_config['payhere_app_code']);
        define('PAYHERE_BUSINESS_APP_SECRET', $payhere_config['payhere_app_secret']);
        
    } catch (Exception $e) {
        // Fallback to default sandbox configuration if database fails
        define('PAYHERE_MERCHANT_ID', 'your_payhere_merchant_id');
        define('PAYHERE_MERCHANT_SECRET', 'your_payhere_merchant_secret');
        define('PAYHERE_API_URL', 'https://sandbox.payhere.lk');
        define('PAYHERE_CHECKOUT_URL', 'https://sandbox.payhere.lk/pay/checkout');
        define('PAYHERE_RECURRING_API_URL', 'https://sandbox.payhere.lk/pay/recurring');
        define('PAYHERE_BUSINESS_APP_CODE', 'your_business_app_code');
        define('PAYHERE_BUSINESS_APP_SECRET', 'your_business_app_secret');
        
        error_log('Failed to load PayHere settings from database: ' . $e->getMessage());
    }
}

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Load PayHere settings from database
loadPayHereSettings();

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>