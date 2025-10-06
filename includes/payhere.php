<?php
require_once 'config.php';

class PayHere {
    
    /**
     * Generate PayHere checkout URL for tokenization
     */
    public static function generateTokenizationURL($customer_id, $customer_name, $customer_email, $customer_phone, $return_url, $notify_url) {
        $merchant_id = PAYHERE_MERCHANT_ID;
        $merchant_secret = PAYHERE_MERCHANT_SECRET;
        
        // Generate unique order ID for tokenization
        $order_id = 'TOKEN_' . $customer_id . '_' . time();
        
        $data = array(
            'merchant_id' => $merchant_id,
            'return_url' => $return_url,
            'cancel_url' => $return_url,
            'notify_url' => $notify_url,
            'order_id' => $order_id,
            'items' => 'Card Tokenization',
            'currency' => 'LKR',
            'amount' => '0.00', // Zero amount for tokenization
            'first_name' => $customer_name,
            'last_name' => '',
            'email' => $customer_email,
            'phone' => $customer_phone,
            'address' => '',
            'city' => '',
            'country' => 'Sri Lanka',
            'recurrence' => 'Month',
            'duration' => '12',
            'startup_fee' => '0.00'
        );
        
        // Generate hash
        $hash = self::generateHash($data, $merchant_secret);
        $data['hash'] = $hash;
        
        return array('url' => PAYHERE_CHECKOUT_URL, 'data' => $data);
    }
    
    /**
     * Process recurring payment using stored token
     */
    public static function processRecurringPayment($token, $amount, $customer_id, $property_id) {
        global $pdo;
        
        try {
            // Get token details
            $token_stmt = $pdo->prepare("SELECT * FROM card_tokens WHERE payhere_token = ? AND customer_id = ? AND status = 'active'");
            $token_stmt->execute([$token, $customer_id]);
            $token_data = $token_stmt->fetch();
            
            if (!$token_data) {
                throw new Exception('Invalid or expired token');
            }
            
            // Generate unique payment ID
            $payment_id = 'RENT_' . $customer_id . '_' . $property_id . '_' . time();
            
            // Prepare PayHere recurring payment request
            $recurring_data = array(
                'payment_token' => $token,
                'amount_detail' => array(
                    'currency' => 'LKR',
                    'gross_amount' => number_format($amount, 2, '.', '')
                ),
                'payment_id' => $payment_id
            );
            
            // Get authorization token
            $auth_token = self::getAuthorizationToken();
            
            if (!$auth_token) {
                throw new Exception('Failed to get PayHere authorization token');
            }
            
            // Make recurring payment API call
            $response = self::makeRecurringPaymentRequest($recurring_data, $auth_token);
            
            if ($response && $response['status'] === 'success') {
                // Calculate commission and owner payout
                $commission = ($amount * COMMISSION_PERCENTAGE) / 100;
                $owner_payout = $amount - $commission;
                
                // Get property owner ID
                $owner_stmt = $pdo->prepare("SELECT owner_id FROM properties WHERE id = ?");
                $owner_stmt->execute([$property_id]);
                $property_info = $owner_stmt->fetch();
                $owner_id = $property_info['owner_id'];
                
                // Store payment in database
                $payment_stmt = $pdo->prepare("
                    INSERT INTO payments (customer_id, property_id, owner_id, token_id, amount, commission, owner_payout, payhere_payment_id, status, payhere_response, due_date)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'successful', ?, ?)
                ");
                
                $due_date = date('Y-m-d');
                $payment_stmt->execute([
                    $customer_id,
                    $property_id,
                    $owner_id,
                    $token_data['id'],
                    $amount,
                    $commission,
                    $owner_payout,
                    $response['payment_id'] ?? $payment_id,
                    json_encode($response),
                    $due_date
                ]);
                
                $payment_db_id = $pdo->lastInsertId();
                
                // Record commission
                $commission_stmt = $pdo->prepare("INSERT INTO commissions (payment_id, percentage, amount) VALUES (?, ?, ?)");
                $commission_stmt->execute([$payment_db_id, COMMISSION_PERCENTAGE, $commission]);
                
                return array(
                    'success' => true,
                    'payment_id' => $payment_db_id,
                    'amount' => $amount,
                    'commission' => $commission,
                    'owner_payout' => $owner_payout
                );
            } else {
                throw new Exception('Payment failed: ' . ($response['message'] ?? 'Unknown error'));
            }
            
        } catch (Exception $e) {
            error_log('PayHere recurring payment error: ' . $e->getMessage());
            return array(
                'success' => false,
                'error' => $e->getMessage()
            );
        }
    }
    
    /**
     * Get PayHere authorization token for API calls
     */
    private static function getAuthorizationToken() {
        $app_id = PAYHERE_BUSINESS_APP_CODE;
        $app_secret = PAYHERE_BUSINESS_APP_SECRET;
        
        $auth_url = PAYHERE_API_URL . '/oauth/token';
        
        $post_data = array(
            'grant_type' => 'client_credentials',
            'client_id' => $app_id,
            'client_secret' => $app_secret
        );
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $auth_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/x-www-form-urlencoded'
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            $data = json_decode($response, true);
            return $data['access_token'] ?? null;
        }
        
        return null;
    }
    
    /**
     * Make recurring payment API request
     */
    private static function makeRecurringPaymentRequest($payment_data, $auth_token) {
        $api_url = PAYHERE_RECURRING_API_URL;
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payment_data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Content-Type: application/json',
            'Authorization: Bearer ' . $auth_token
        ));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($http_code === 200) {
            return json_decode($response, true);
        }
        
        return null;
    }
    
    /**
     * Generate PayHere hash for checkout
     */
    private static function generateHash($data, $merchant_secret) {
        $hash_string = $data['merchant_id'] . 
                      $data['order_id'] . 
                      $data['amount'] . 
                      $data['currency'] . 
                      strtoupper(md5($merchant_secret));
        
        return strtoupper(md5($hash_string));
    }
    
    /**
     * Verify PayHere IPN notification
     */
    public static function verifyIPN($post_data, $merchant_secret) {
        $merchant_id = $post_data['merchant_id'] ?? '';
        $order_id = $post_data['order_id'] ?? '';
        $payhere_amount = $post_data['payhere_amount'] ?? '';
        $payhere_currency = $post_data['payhere_currency'] ?? '';
        $status_code = $post_data['status_code'] ?? '';
        $md5sig = $post_data['md5sig'] ?? '';
        
        $local_md5sig = strtoupper(md5($merchant_id . $order_id . $payhere_amount . $payhere_currency . $status_code . strtoupper(md5($merchant_secret))));
        
        return ($local_md5sig === $md5sig);
    }
    
    /**
     * Store successful tokenization
     */
    public static function storeToken($customer_id, $payhere_token, $card_last4, $card_brand = '', $card_holder_name = '') {
        global $pdo;
        
        try {
            // Deactivate old tokens for this customer
            $deactivate_stmt = $pdo->prepare("UPDATE card_tokens SET status = 'disabled' WHERE customer_id = ?");
            $deactivate_stmt->execute([$customer_id]);
            
            // Store new token
            $stmt = $pdo->prepare("
                INSERT INTO card_tokens (customer_id, payhere_token, card_last4, card_brand, card_holder_name, status)
                VALUES (?, ?, ?, ?, ?, 'active')
            ");
            
            return $stmt->execute([$customer_id, $payhere_token, $card_last4, $card_brand, $card_holder_name]);
            
        } catch (Exception $e) {
            error_log('Error storing PayHere token: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get active token for customer
     */
    public static function getActiveToken($customer_id) {
        global $pdo;
        
        $stmt = $pdo->prepare("SELECT * FROM card_tokens WHERE customer_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$customer_id]);
        
        return $stmt->fetch();
    }
    
    /**
     * Generate PayHere checkout for one-time rental payment
     */
    public static function generateRentalPaymentURL($customer_id, $property_id, $customer_name, $customer_email, $customer_phone, $property_title, $rent_amount, $security_deposit, $return_url, $notify_url) {
        $merchant_id = PAYHERE_MERCHANT_ID;
        $merchant_secret = PAYHERE_MERCHANT_SECRET;
        
        // Generate unique order ID for rental payment
        $order_id = 'RENTAL_' . $customer_id . '_' . $property_id . '_' . time();
        
        // Calculate total amount (rent + security deposit)
        $total_amount = $rent_amount + $security_deposit;
        
        $data = array(
            'merchant_id' => $merchant_id,
            'return_url' => $return_url,
            'cancel_url' => $return_url,
            'notify_url' => $notify_url,
            'order_id' => $order_id,
            'items' => 'Rental Payment for ' . $property_title,
            'currency' => 'LKR',
            'amount' => number_format($total_amount, 2, '.', ''),
            'first_name' => $customer_name,
            'last_name' => '',
            'email' => $customer_email,
            'phone' => $customer_phone,
            'address' => '',
            'city' => '',
            'country' => 'Sri Lanka',
            'custom_1' => $property_id,
            'custom_2' => $customer_id
        );
        
        // Generate hash
        $hash = self::generateHash($data, $merchant_secret);
        $data['hash'] = $hash;
        
        return array('url' => PAYHERE_CHECKOUT_URL, 'data' => $data);
    }
    
    /**
     * Process monthly recurring payments (for CRON job)
     */
    public static function processMonthlyPayments() {
        global $pdo;
        
        $results = array(
            'processed' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        try {
            // Get all active rental agreements that need payment
            $stmt = $pdo->query("
                SELECT ra.*, p.rent_amount, p.title as property_title, ct.payhere_token
                FROM rental_agreements ra
                JOIN properties p ON ra.property_id = p.id
                JOIN card_tokens ct ON ct.customer_id = ra.customer_id AND ct.status = 'active'
                WHERE ra.status = 'active' 
                AND ra.lease_start_date <= CURDATE()
                AND ra.lease_end_date >= CURDATE()
                AND NOT EXISTS (
                    SELECT 1 FROM payments pay 
                    WHERE pay.customer_id = ra.customer_id 
                    AND pay.property_id = ra.property_id 
                    AND MONTH(pay.payment_date) = MONTH(CURDATE())
                    AND YEAR(pay.payment_date) = YEAR(CURDATE())
                    AND pay.status = 'successful'
                )
            ");
            
            $rentals = $stmt->fetchAll();
            
            foreach ($rentals as $rental) {
                $payment_result = self::processRecurringPayment(
                    $rental['payhere_token'],
                    $rental['rent_amount'],
                    $rental['customer_id'],
                    $rental['property_id']
                );
                
                if ($payment_result['success']) {
                    $results['processed']++;
                } else {
                    $results['failed']++;
                    $results['errors'][] = "Rental ID {$rental['id']}: " . $payment_result['error'];
                }
            }
            
        } catch (Exception $e) {
            $results['errors'][] = 'General error: ' . $e->getMessage();
        }
        
        return $results;
    }
}
?>