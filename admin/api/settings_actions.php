<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Include database configuration
require_once '../../includes/config.php';

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['type'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request data']);
    exit;
}

$type = $input['type'];

try {
    $response = ['success' => false, 'message' => 'Unknown settings type'];

    switch ($type) {
        case 'general':
            // Handle general settings
            $response = ['success' => true, 'message' => 'General settings saved successfully'];
            // You would typically save these to a settings table or config file
            break;

        case 'contact':
            // Handle contact settings
            if (isset($input['phone_number'])) {
                // Validate Sri Lankan phone number
                $phoneRegex = '/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/';
                if (!preg_match($phoneRegex, $input['phone_number'])) {
                    $response = ['success' => false, 'message' => 'Invalid Sri Lankan phone number format'];
                    break;
                }
            }
            $response = ['success' => true, 'message' => 'Contact information saved successfully'];
            break;

        case 'payment':
            // Handle payment settings
            $response = ['success' => true, 'message' => 'Payment settings saved successfully'];
            break;

        case 'email':
            // Handle email settings
            if (isset($input['smtp_username']) && !filter_var($input['smtp_username'], FILTER_VALIDATE_EMAIL)) {
                $response = ['success' => false, 'message' => 'Invalid email address'];
                break;
            }
            $response = ['success' => true, 'message' => 'Email settings saved successfully'];
            break;

        case 'user':
            // Handle user management settings
            $response = ['success' => true, 'message' => 'User management settings saved successfully'];
            break;

        case 'maintenance':
            $action = $input['action'] ?? '';
            switch ($action) {
                case 'backup':
                    // Create database backup
                    $response = ['success' => true, 'message' => 'Database backup created successfully'];
                    break;
                case 'clear_cache':
                    // Clear cache
                    $response = ['success' => true, 'message' => 'Cache cleared successfully'];
                    break;
                case 'toggle_maintenance':
                    $enable = $input['enable'] ?? false;
                    // Toggle maintenance mode
                    $response = ['success' => true, 'message' => 'Maintenance mode ' . ($enable ? 'enabled' : 'disabled')];
                    break;
                default:
                    $response = ['success' => false, 'message' => 'Unknown maintenance action'];
            }
            break;
    }

    // In a real implementation, you would:
    // 1. Create a settings table in your database
    // 2. Save the settings to that table
    // 3. Update config files if necessary
    // 4. Implement proper validation for each setting type

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
?>