<?php
require_once '../includes/functions.php';
require_once '../includes/payhere.php';

// This file handles PayHere IPN (Instant Payment Notifications)
// It should be accessible without authentication as PayHere will call it directly

$log_file = '../logs/payhere_ipn.log';

// Create logs directory if it doesn't exist
if (!is_dir('../logs')) {
    mkdir('../logs', 0755, true);
}

// Log the IPN request
$log_data = [
    'timestamp' => date('Y-m-d H:i:s'),
    'post_data' => $_POST,
    'get_data' => $_GET
];

file_put_contents($log_file, json_encode($log_data) . "\n", FILE_APPEND | LOCK_EX);

try {
    if ($_POST) {
        // Verify the IPN
        if (PayHere::verifyIPN($_POST, PAYHERE_MERCHANT_SECRET)) {
            $merchant_id = $_POST['merchant_id'] ?? '';
            $order_id = $_POST['order_id'] ?? '';
            $payment_id = $_POST['payment_id'] ?? '';
            $payhere_amount = $_POST['payhere_amount'] ?? '';
            $payhere_currency = $_POST['payhere_currency'] ?? '';
            $status_code = $_POST['status_code'] ?? '';
            $custom_1 = $_POST['custom_1'] ?? '';
            $custom_2 = $_POST['custom_2'] ?? '';
            
            // Log verified IPN
            $verified_log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'verified',
                'order_id' => $order_id,
                'payment_id' => $payment_id,
                'status_code' => $status_code,
                'amount' => $payhere_amount
            ];
            
            file_put_contents($log_file, "VERIFIED: " . json_encode($verified_log) . "\n", FILE_APPEND | LOCK_EX);
            
            // Handle different status codes
            if ($status_code == '2') {
                // Successful payment
                $payment_token = $_POST['payment_token'] ?? '';
                
                if (strpos($order_id, 'TOKEN_') === 0) {
                    // This is a tokenization notification
                    $customer_id = intval(str_replace(['TOKEN_', '_' . time()], '', $order_id));
                    
                    if ($payment_token && $customer_id) {
                        $card_holder_name = $_POST['card_holder_name'] ?? '';
                        $card_no = $_POST['card_no'] ?? '';
                        $card_last4 = substr($card_no, -4);
                        
                        // Store the token
                        PayHere::storeToken($customer_id, $payment_token, $card_last4, '', $card_holder_name);
                        
                        file_put_contents($log_file, "TOKEN_STORED: Customer ID $customer_id, Token: $payment_token\n", FILE_APPEND | LOCK_EX);
                    }
                } else if (strpos($order_id, 'RENT_') === 0) {
                    // This is a recurring payment notification
                    // Update payment record if exists
                    $stmt = $pdo->prepare("UPDATE payments SET status = 'successful', payhere_response = ? WHERE payhere_payment_id = ?");
                    $stmt->execute([json_encode($_POST), $payment_id]);
                    
                    file_put_contents($log_file, "PAYMENT_UPDATED: Payment ID $payment_id\n", FILE_APPEND | LOCK_EX);
                }
            } else if ($status_code == '0') {
                // Payment cancelled
                if (strpos($order_id, 'RENT_') === 0) {
                    $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', payhere_response = ? WHERE payhere_payment_id = ?");
                    $stmt->execute([json_encode($_POST), $payment_id]);
                }
                
                file_put_contents($log_file, "PAYMENT_CANCELLED: Payment ID $payment_id\n", FILE_APPEND | LOCK_EX);
            } else if ($status_code == '-1') {
                // Payment failed
                if (strpos($order_id, 'RENT_') === 0) {
                    $stmt = $pdo->prepare("UPDATE payments SET status = 'failed', payhere_response = ? WHERE payhere_payment_id = ?");
                    $stmt->execute([json_encode($_POST), $payment_id]);
                }
                
                file_put_contents($log_file, "PAYMENT_FAILED: Payment ID $payment_id\n", FILE_APPEND | LOCK_EX);
            }
            
            // Send successful response to PayHere
            http_response_code(200);
            echo "OK";
            
        } else {
            // Invalid signature
            $error_log = [
                'timestamp' => date('Y-m-d H:i:s'),
                'status' => 'invalid_signature',
                'post_data' => $_POST
            ];
            
            file_put_contents($log_file, "INVALID_SIGNATURE: " . json_encode($error_log) . "\n", FILE_APPEND | LOCK_EX);
            
            http_response_code(400);
            echo "Invalid signature";
        }
    } else {
        // No POST data
        file_put_contents($log_file, "NO_POST_DATA: " . date('Y-m-d H:i:s') . "\n", FILE_APPEND | LOCK_EX);
        
        http_response_code(400);
        echo "No data received";
    }
    
} catch (Exception $e) {
    // Log the error
    $error_log = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    file_put_contents($log_file, "ERROR: " . json_encode($error_log) . "\n", FILE_APPEND | LOCK_EX);
    
    http_response_code(500);
    echo "Internal server error";
}
?>