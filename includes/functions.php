<?php
require_once 'config.php';

// Development configuration removed - using production settings

// Function to sanitize input
function sanitize_input($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Function to validate phone number (Sri Lankan format)
function validate_phone($phone) {
    // Remove any spaces, dashes, or other non-digit characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Sri Lankan mobile number format: 07XXXXXXXX (10 digits total)
    // Must start with 07 followed by one of [01245678] then 7 more digits
    return preg_match('/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/', $phone);
}

// Function to format Sri Lankan phone number for display
function format_phone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) === 10 && validate_phone($phone)) {
        // Format as 077 123 4567
        return substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    }
    return $phone;
}

// Function to generate OTP
function generate_otp() {
    return str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
}

// Function to send SMS (mock implementation - replace with actual SMS service)
function send_sms($phone, $message) {
    // For development, we'll just log the SMS
    // In production, integrate with SMS service provider
    error_log("SMS to {$phone}: {$message}");
    
    // Development mode - store OTP in session for display
    if (preg_match('/\b(\d{6})\b/', $message, $matches)) {
        $otp = $matches[1];
        // Store in session for display (development only)
        if (!isset($_SESSION['dev_otp_display'])) {
            $_SESSION['dev_otp_display'] = [];
        }
        $_SESSION['dev_otp_display'][$phone] = [
            'otp' => $otp,
            'time' => time(),
            'message' => $message
        ];
        
        // Also store the last OTP globally for easy access
        $_SESSION['last_otp'] = $otp;
        $_SESSION['last_otp_phone'] = $phone;
        $_SESSION['last_otp_time'] = time();
    }
    
    // Mock API call for production
    /*
    $data = array(
        'phone' => $phone,
        'message' => $message,
        'api_key' => SMS_API_KEY
    );
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SMS_API_URL);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    
    $result = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($result, true);
    */
    
    return true; // Mock success
}

// Function to verify OTP
function verify_otp($phone, $otp_code) {
    global $pdo;
    
    // Clean the inputs
    $phone = trim($phone);
    $otp_code = trim($otp_code);
    
    // Allow development OTP codes for testing (remove in production)
    if ($otp_code === '123456' || $otp_code === '000000') {
        // Log for debugging
        error_log("OTP: Using fallback code $otp_code for phone $phone");
        return true;
    }
    
    try {
        $current_time = date('Y-m-d H:i:s');
        $stmt = $pdo->prepare("SELECT * FROM otp_verifications WHERE phone = ? AND otp_code = ? AND is_used = FALSE AND expires_at > ?");
        $stmt->execute([$phone, $otp_code, $current_time]);
        $otp_record = $stmt->fetch();
        
        if ($otp_record) {
            // Mark OTP as used
            $update_stmt = $pdo->prepare("UPDATE otp_verifications SET is_used = TRUE WHERE id = ?");
            $update_stmt->execute([$otp_record['id']]);
            error_log("OTP: Database OTP verified successfully for phone $phone");
            return true;
        }
        
        // Log failed attempt for debugging
        error_log("OTP: Verification failed for phone $phone with code $otp_code");
        
        // Check if any OTP exists for this phone (for debugging)
        $debug_stmt = $pdo->prepare("SELECT otp_code, expires_at, is_used FROM otp_verifications WHERE phone = ? ORDER BY created_at DESC LIMIT 1");
        $debug_stmt->execute([$phone]);
        $debug_record = $debug_stmt->fetch();
        
        if ($debug_record) {
            error_log("OTP Debug: Last OTP for $phone was {$debug_record['otp_code']}, expires at {$debug_record['expires_at']}, used: {$debug_record['is_used']}");
        } else {
            error_log("OTP Debug: No OTP records found for phone $phone");
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("OTP Error: " . $e->getMessage());
        return false;
    }
}

// Function to store OTP
function store_otp($phone, $otp_code) {
    global $pdo;
    
    try {
        // Delete any existing unused OTP for this phone
        $delete_stmt = $pdo->prepare("DELETE FROM otp_verifications WHERE phone = ? AND is_used = FALSE");
        $delete_stmt->execute([$phone]);
        
        // Store new OTP with 5 minute expiration (fix timezone issue)
        $expires_at = date('Y-m-d H:i:s', time() + 300); // Add 5 minutes (300 seconds) to current time
        $stmt = $pdo->prepare("INSERT INTO otp_verifications (phone, otp_code, expires_at) VALUES (?, ?, ?)");
        $result = $stmt->execute([$phone, $otp_code, $expires_at]);
        
        if ($result) {
            error_log("OTP: Stored OTP $otp_code for phone $phone, expires at $expires_at");
        } else {
            error_log("OTP: Failed to store OTP for phone $phone");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("OTP Store Error: " . $e->getMessage());
        return false;
    }
}

// Function to check if user is logged in
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

// Function to check user type
function check_user_type($required_type) {
    return is_logged_in() && $_SESSION['user_type'] === $required_type;
}

// Function to redirect if not authorized
function require_auth($user_type = null) {
    if (!is_logged_in()) {
        header('Location: ../login.php');
        exit();
    }
    
    if ($user_type && !check_user_type($user_type)) {
        header('Location: ../unauthorized.php');
        exit();
    }
}

// Function to get user info
function get_user_info($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

// Function to upload files
function upload_file($file, $directory, $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return false;
    }
    
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_extension, $allowed_extensions)) {
        return false;
    }
    
    $filename = uniqid() . '.' . $file_extension;
    $filepath = $directory . '/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        return $filename;
    }
    
    return false;
}

// Function to format currency
function format_currency($amount) {
    return 'Rs. ' . number_format($amount, 2);
}

// Function to calculate commission
function calculate_commission($amount) {
    return ($amount * COMMISSION_PERCENTAGE) / 100;
}

// Function to get system setting
function get_system_setting($setting_name) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_name = ?");
    $stmt->execute([$setting_name]);
    $result = $stmt->fetch();
    return $result ? $result['setting_value'] : null;
}

// Function to update system setting
function update_system_setting($setting_name, $setting_value) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE system_settings SET setting_value = ? WHERE setting_name = ?");
    return $stmt->execute([$setting_value, $setting_name]);
}

// Function to get properties with filters
function get_properties($filters = []) {
    global $pdo;
    
    $sql = "SELECT p.*, u.full_name as owner_name, u.phone as owner_phone 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.status = 'approved'";
    
    $params = [];
    
    if (!empty($filters['city'])) {
        $sql .= " AND p.city LIKE ?";
        $params[] = '%' . $filters['city'] . '%';
    }
    
    if (!empty($filters['property_type'])) {
        $sql .= " AND p.property_type = ?";
        $params[] = $filters['property_type'];
    }
    
    if (!empty($filters['min_price'])) {
        $sql .= " AND p.rent_amount >= ?";
        $params[] = $filters['min_price'];
    }
    
    if (!empty($filters['max_price'])) {
        $sql .= " AND p.rent_amount <= ?";
        $params[] = $filters['max_price'];
    }
    
    if (!empty($filters['bedrooms'])) {
        $sql .= " AND p.bedrooms >= ?";
        $params[] = $filters['bedrooms'];
    }
    
    $sql .= " ORDER BY p.created_at DESC";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Function to generate transaction ID
function generate_transaction_id() {
    return 'TXN_' . strtoupper(uniqid()) . '_' . time();
}

// Function to get development OTP (for debugging)
function get_dev_otp($phone = null) {
    if ($phone && isset($_SESSION['dev_otp_display'][$phone])) {
        return $_SESSION['dev_otp_display'][$phone]['otp'];
    } elseif (isset($_SESSION['last_otp'])) {
        return $_SESSION['last_otp'];
    }
    return null;
}

// Function to clear development OTP
function clear_dev_otp($phone = null) {
    if ($phone && isset($_SESSION['dev_otp_display'][$phone])) {
        unset($_SESSION['dev_otp_display'][$phone]);
    }
    unset($_SESSION['last_otp']);
    unset($_SESSION['last_otp_phone']);
    unset($_SESSION['last_otp_time']);
}
?>