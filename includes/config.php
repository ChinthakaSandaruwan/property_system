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

// PayHere Configuration
define('PAYHERE_MERCHANT_ID', 'your_payhere_merchant_id');
define('PAYHERE_MERCHANT_SECRET', 'your_payhere_merchant_secret');
define('PAYHERE_API_URL', 'https://sandbox.payhere.lk');
define('PAYHERE_CHECKOUT_URL', 'https://sandbox.payhere.lk/pay/checkout');
define('PAYHERE_RECURRING_API_URL', 'https://sandbox.payhere.lk/pay/recurring');
define('PAYHERE_BUSINESS_APP_CODE', 'your_business_app_code');
define('PAYHERE_BUSINESS_APP_SECRET', 'your_business_app_secret');

// Database Connection
try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Start session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>