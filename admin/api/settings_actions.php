<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST; // Fallback to POST data
}

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    $response = ['success' => false, 'message' => 'Invalid request'];
    
    switch ($method) {
        case 'GET':
            // Read all settings
            $stmt = $pdo->query("SELECT setting_name, setting_value, description FROM system_settings ORDER BY setting_name");
            $settings = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $response = ['success' => true, 'data' => $settings];
            break;
            
        case 'POST':
            // Create or Update settings
            if (!isset($input['type'])) {
                $response = ['success' => false, 'message' => 'Setting type is required'];
                break;
            }
            
            $type = $input['type'];
            $response = handleSettingsUpdate($pdo, $type, $input);
            break;
            
        case 'PUT':
            // Update specific setting
            if (!isset($input['setting_name']) || !isset($input['setting_value'])) {
                $response = ['success' => false, 'message' => 'Setting name and value are required'];
                break;
            }
            
            $response = updateSetting($pdo, $input['setting_name'], $input['setting_value'], $input['description'] ?? '');
            break;
            
        case 'DELETE':
            // Delete setting
            if (!isset($input['setting_name'])) {
                $response = ['success' => false, 'message' => 'Setting name is required'];
                break;
            }
            
            $response = deleteSetting($pdo, $input['setting_name']);
            break;
    }
    
} catch (PDOException $e) {
    $response = [
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ];
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ];
}

echo json_encode($response);

/**
 * Handle settings update based on type
 */
function handleSettingsUpdate($pdo, $type, $input) {
    $pdo->beginTransaction();
    
    try {
        switch ($type) {
            case 'general':
                $settings = [
                    'site_name' => $input['site_name'] ?? '',
                    'site_url' => $input['site_url'] ?? '',
                    'commission_percentage' => $input['commission_percentage'] ?? '',
                    'currency' => $input['currency'] ?? 'LKR'
                ];
                
                foreach ($settings as $name => $value) {
                    if ($value !== '') {
                        updateOrInsertSetting($pdo, $name, $value);
                    }
                }
                break;
                
            case 'contact':
                // Validate phone number
                if (isset($input['phone_number'])) {
                    $phoneRegex = '/^[\+]?[0-9\s\-\(\)]+$/';
                    if (!preg_match($phoneRegex, $input['phone_number'])) {
                        throw new Exception('Invalid phone number format');
                    }
                }
                
                // Validate email
                if (isset($input['admin_email']) && !filter_var($input['admin_email'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid email address');
                }
                
                $settings = [
                    'admin_email' => $input['admin_email'] ?? '',
                    'phone_number' => $input['phone_number'] ?? '',
                    'address' => $input['address'] ?? ''
                ];
                
                foreach ($settings as $name => $value) {
                    if ($value !== '') {
                        updateOrInsertSetting($pdo, $name, $value);
                    }
                }
                break;
                
            case 'payment':
                $settings = [
                    'payhere_merchant_id' => $input['payhere_merchant_id'] ?? '',
                    'payhere_merchant_secret' => $input['payhere_merchant_secret'] ?? '',
                    'payhere_mode' => $input['payhere_mode'] ?? 'sandbox',
                    'payhere_currency' => $input['payhere_currency'] ?? 'LKR',
                    'payhere_enabled' => $input['payhere_enabled'] ?? '1'
                ];
                
                foreach ($settings as $name => $value) {
                    // Save all PayHere settings, including empty values to clear them if needed
                    updateOrInsertSetting($pdo, $name, $value);
                }
                break;
                
            case 'email':
                // Validate email
                if (isset($input['smtp_username']) && $input['smtp_username'] !== '' && !filter_var($input['smtp_username'], FILTER_VALIDATE_EMAIL)) {
                    throw new Exception('Invalid SMTP username (email address)');
                }
                
                $settings = [
                    'smtp_host' => $input['smtp_host'] ?? '',
                    'smtp_port' => $input['smtp_port'] ?? '587',
                    'smtp_username' => $input['smtp_username'] ?? '',
                    'smtp_password' => $input['smtp_password'] ?? ''
                ];
                
                foreach ($settings as $name => $value) {
                    updateOrInsertSetting($pdo, $name, $value);
                }
                break;
                
            case 'user':
                $settings = [
                    'auto_approve_properties' => $input['auto_approve_properties'] ?? '0',
                    'registration_approval' => $input['registration_approval'] ?? '0',
                    'max_properties_per_owner' => $input['max_properties_per_owner'] ?? '10'
                ];
                
                foreach ($settings as $name => $value) {
                    updateOrInsertSetting($pdo, $name, $value);
                }
                break;
                
            default:
                throw new Exception('Unknown settings type: ' . $type);
        }
        
        $pdo->commit();
        return ['success' => true, 'message' => ucfirst($type) . ' settings saved successfully'];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

/**
 * Update or insert a setting
 */
function updateOrInsertSetting($pdo, $name, $value, $description = '') {
    $stmt = $pdo->prepare("
        INSERT INTO system_settings (setting_name, setting_value, description) 
        VALUES (?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value),
        description = COALESCE(NULLIF(VALUES(description), ''), description)
    ");
    
    return $stmt->execute([$name, $value, $description]);
}

/**
 * Update specific setting
 */
function updateSetting($pdo, $name, $value, $description = '') {
    if (updateOrInsertSetting($pdo, $name, $value, $description)) {
        return ['success' => true, 'message' => 'Setting updated successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to update setting'];
    }
}

/**
 * Delete setting
 */
function deleteSetting($pdo, $name) {
    $stmt = $pdo->prepare("DELETE FROM system_settings WHERE setting_name = ?");
    
    if ($stmt->execute([$name])) {
        return ['success' => true, 'message' => 'Setting deleted successfully'];
    } else {
        return ['success' => false, 'message' => 'Failed to delete setting'];
    }
}
?>
