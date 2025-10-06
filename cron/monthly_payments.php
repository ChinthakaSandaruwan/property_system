<?php
/**
 * Monthly Recurring Payments CRON Job
 * 
 * This script should be run monthly via CRON to process recurring rent payments
 * 
 * CRON setup example (run on 1st of each month at 9:00 AM):
 * 0 9 1 * * /usr/bin/php /path/to/rental_system/cron/monthly_payments.php
 */

require_once dirname(__DIR__) . '/includes/functions.php';
require_once dirname(__DIR__) . '/includes/payhere.php';

// Set up logging
$log_file = dirname(__DIR__) . '/logs/monthly_payments.log';
$log_dir = dirname($log_file);

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

function log_message($message) {
    global $log_file;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($log_file, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

function send_notification_email($to, $subject, $message) {
    // Simple email notification (replace with your preferred email service)
    $headers = "From: noreply@smartrent.com\r\n";
    $headers .= "Reply-To: support@smartrent.com\r\n";
    $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
    
    return mail($to, $subject, $message, $headers);
}

try {
    log_message("=== Starting Monthly Payments CRON Job ===");
    
    // Process monthly payments
    $results = PayHere::processMonthlyPayments();
    
    log_message("Payment processing completed:");
    log_message("- Processed: {$results['processed']} payments");
    log_message("- Failed: {$results['failed']} payments");
    
    if (!empty($results['errors'])) {
        log_message("Errors encountered:");
        foreach ($results['errors'] as $error) {
            log_message("- $error");
        }
    }
    
    // Send summary email to admin
    if ($results['processed'] > 0 || $results['failed'] > 0) {
        $admin_email = 'admin@smartrent.com'; // Replace with actual admin email
        $subject = 'SmartRent - Monthly Payment Processing Summary';
        
        $email_body = "
        <h2>Monthly Payment Processing Summary</h2>
        <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
        <p><strong>Successfully Processed:</strong> {$results['processed']} payments</p>
        <p><strong>Failed:</strong> {$results['failed']} payments</p>
        ";
        
        if (!empty($results['errors'])) {
            $email_body .= "<h3>Errors:</h3><ul>";
            foreach ($results['errors'] as $error) {
                $email_body .= "<li>" . htmlspecialchars($error) . "</li>";
            }
            $email_body .= "</ul>";
        }
        
        send_notification_email($admin_email, $subject, $email_body);
        log_message("Summary email sent to admin");
    }
    
    // Send payment confirmation emails to customers and owners
    if ($results['processed'] > 0) {
        log_message("Sending payment confirmation emails...");
        
        // Get today's successful payments
        $stmt = $pdo->query("
            SELECT p.*, pr.title as property_title, pr.rent_amount,
                   c.full_name as customer_name, c.email as customer_email,
                   o.full_name as owner_name, o.email as owner_email
            FROM payments p
            JOIN properties pr ON p.property_id = pr.id
            JOIN users c ON p.customer_id = c.id
            JOIN users o ON pr.owner_id = o.id
            WHERE DATE(p.payment_date) = CURDATE() 
            AND p.status = 'successful'
            AND p.payment_type = 'rent'
        ");
        
        $todays_payments = $stmt->fetchAll();
        
        foreach ($todays_payments as $payment) {
            // Email to customer
            if ($payment['customer_email']) {
                $customer_subject = 'SmartRent - Monthly Rent Payment Confirmation';
                $customer_message = "
                <h2>Payment Confirmation</h2>
                <p>Dear {$payment['customer_name']},</p>
                <p>Your monthly rent payment has been successfully processed.</p>
                <ul>
                    <li><strong>Property:</strong> {$payment['property_title']}</li>
                    <li><strong>Amount:</strong> LKR " . number_format($payment['amount'], 2) . "</li>
                    <li><strong>Payment Date:</strong> " . date('M j, Y', strtotime($payment['payment_date'])) . "</li>
                    <li><strong>Payment ID:</strong> {$payment['payhere_payment_id']}</li>
                </ul>
                <p>Thank you for your payment!</p>
                <p>SmartRent Team</p>
                ";
                
                send_notification_email($payment['customer_email'], $customer_subject, $customer_message);
            }
            
            // Email to owner
            if ($payment['owner_email']) {
                $owner_subject = 'SmartRent - Rent Payment Received';
                $owner_message = "
                <h2>Rent Payment Received</h2>
                <p>Dear {$payment['owner_name']},</p>
                <p>A rent payment has been received for your property.</p>
                <ul>
                    <li><strong>Property:</strong> {$payment['property_title']}</li>
                    <li><strong>Gross Amount:</strong> LKR " . number_format($payment['amount'], 2) . "</li>
                    <li><strong>Commission:</strong> LKR " . number_format($payment['commission'], 2) . "</li>
                    <li><strong>Your Payout:</strong> LKR " . number_format($payment['owner_payout'], 2) . "</li>
                    <li><strong>Payment Date:</strong> " . date('M j, Y', strtotime($payment['payment_date'])) . "</li>
                    <li><strong>Tenant:</strong> {$payment['customer_name']}</li>
                </ul>
                <p>The payment will be transferred to your account within 2-3 business days.</p>
                <p>SmartRent Team</p>
                ";
                
                send_notification_email($payment['owner_email'], $owner_subject, $owner_message);
            }
        }
        
        log_message("Payment confirmation emails sent");
    }
    
    // Check for failed payments and send reminders
    $failed_payments_stmt = $pdo->query("
        SELECT p.*, pr.title as property_title, c.full_name as customer_name, c.email as customer_email
        FROM payments p
        JOIN properties pr ON p.property_id = pr.id
        JOIN users c ON p.customer_id = c.id
        WHERE p.status = 'failed' 
        AND DATE(p.created_at) = CURDATE()
        AND p.payment_type = 'rent'
    ");
    
    $failed_payments = $failed_payments_stmt->fetchAll();
    
    if (!empty($failed_payments)) {
        log_message("Sending payment failure notifications...");
        
        foreach ($failed_payments as $payment) {
            if ($payment['customer_email']) {
                $failure_subject = 'SmartRent - Payment Failed - Action Required';
                $failure_message = "
                <h2>Payment Failed</h2>
                <p>Dear {$payment['customer_name']},</p>
                <p>We were unable to process your monthly rent payment.</p>
                <ul>
                    <li><strong>Property:</strong> {$payment['property_title']}</li>
                    <li><strong>Amount:</strong> LKR " . number_format($payment['amount'], 2) . "</li>
                    <li><strong>Attempted Date:</strong> " . date('M j, Y') . "</li>
                </ul>
                <p>Please log in to your account and update your payment method or contact our support team.</p>
                <p>SmartRent Team</p>
                ";
                
                send_notification_email($payment['customer_email'], $failure_subject, $failure_message);
            }
        }
        
        log_message("Payment failure notifications sent");
    }
    
    log_message("=== Monthly Payments CRON Job Completed Successfully ===");
    
} catch (Exception $e) {
    $error_message = "CRON Job Error: " . $e->getMessage();
    log_message($error_message);
    
    // Send error notification to admin
    $admin_email = 'admin@smartrent.com';
    $subject = 'SmartRent - Monthly Payment CRON Job Error';
    $message = "
    <h2>CRON Job Error</h2>
    <p><strong>Date:</strong> " . date('Y-m-d H:i:s') . "</p>
    <p><strong>Error:</strong> " . htmlspecialchars($e->getMessage()) . "</p>
    <p><strong>File:</strong> " . htmlspecialchars($e->getFile()) . "</p>
    <p><strong>Line:</strong> " . $e->getLine() . "</p>
    <p>Please investigate and resolve this issue immediately.</p>
    ";
    
    send_notification_email($admin_email, $subject, $message);
    
    // Exit with error code
    exit(1);
}

// Exit successfully
exit(0);
?>