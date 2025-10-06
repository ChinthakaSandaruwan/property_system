<?php
require_once 'includes/config.php';
require_once 'includes/payhere.php';

// Test customer data
$test_data = [
    'customer_id' => 1,
    'property_id' => 13,
    'customer_name' => 'Test Customer',
    'customer_email' => 'test@example.com',
    'customer_phone' => '0771234567',
    'property_title' => 'Test Property - Rental',
    'rent_amount' => 50000,
    'security_deposit' => 25000,
    'return_url' => 'http://localhost/rental_system/customer/payment_return.php?property_id=13',
    'notify_url' => 'http://localhost/rental_system/customer/payment_notify.php'
];

// Generate PayHere checkout data
try {
    $payhere_data = PayHere::generateRentalPaymentURL(
        $test_data['customer_id'],
        $test_data['property_id'], 
        $test_data['customer_name'],
        $test_data['customer_email'],
        $test_data['customer_phone'],
        $test_data['property_title'],
        $test_data['rent_amount'],
        $test_data['security_deposit'],
        $test_data['return_url'],
        $test_data['notify_url']
    );
} catch (Exception $e) {
    die("Error generating PayHere data: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complete PayHere Flow Test</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 0 auto; padding: 20px; }
        .info-box { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 5px solid #007bff; }
        .warning-box { background: #fff3cd; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 5px solid #ffc107; }
        .success-box { background: #d4edda; padding: 15px; border-radius: 5px; margin: 15px 0; border-left: 5px solid #28a745; }
        table { border-collapse: collapse; width: 100%; }
        table, th, td { border: 1px solid #ddd; }
        th, td { padding: 10px; text-align: left; }
        th { background: #f8f9fa; }
        .btn { display: inline-block; padding: 15px 30px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; margin: 10px 0; }
        .btn-success { background: #28a745; }
        .btn-warning { background: #ffc107; color: black; }
        .btn:hover { opacity: 0.9; }
    </style>
</head>
<body>
    <h1>🧪 Complete PayHere Flow Test</h1>
    
    <div class="info-box">
        <strong>🎯 Test Objective:</strong> Verify the complete PayHere payment flow from start to finish, including proper return handling.
    </div>

    <h2>📋 Test Configuration</h2>
    <table>
        <tr><th>Parameter</th><th>Value</th></tr>
        <tr><td><strong>Customer ID</strong></td><td><?= htmlspecialchars($test_data['customer_id']) ?></td></tr>
        <tr><td><strong>Property ID</strong></td><td><?= htmlspecialchars($test_data['property_id']) ?></td></tr>
        <tr><td><strong>Customer Name</strong></td><td><?= htmlspecialchars($test_data['customer_name']) ?></td></tr>
        <tr><td><strong>Email</strong></td><td><?= htmlspecialchars($test_data['customer_email']) ?></td></tr>
        <tr><td><strong>Property</strong></td><td><?= htmlspecialchars($test_data['property_title']) ?></td></tr>
        <tr><td><strong>Monthly Rent</strong></td><td>LKR <?= number_format($test_data['rent_amount']) ?></td></tr>
        <tr><td><strong>Security Deposit</strong></td><td>LKR <?= number_format($test_data['security_deposit']) ?></td></tr>
        <tr><td><strong>Total Amount</strong></td><td>LKR <?= number_format($test_data['rent_amount'] + $test_data['security_deposit']) ?></td></tr>
    </table>

    <h2>🔧 Generated PayHere Data</h2>
    <table>
        <tr><th>PayHere Parameter</th><th>Value</th></tr>
        <?php foreach ($payhere_data['data'] as $key => $value): ?>
        <tr>
            <td><strong><?= htmlspecialchars($key) ?></strong></td>
            <td><?= $key === 'hash' ? '<code>' . htmlspecialchars($value) . '</code>' : htmlspecialchars($value) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="warning-box">
        <strong>⚠️ Important Test Instructions:</strong>
        <ol>
            <li><strong>Do NOT use real payment information</strong> - This is PayHere sandbox mode</li>
            <li><strong>Complete the full payment process</strong> - Don't close the PayHere window</li>
            <li><strong>Use PayHere test cards:</strong>
                <ul>
                    <li>Visa: <code>4916217501611292</code> (Expiry: Any future date, CVV: Any 3 digits)</li>
                    <li>MasterCard: <code>5313581000123430</code> (Expiry: Any future date, CVV: Any 3 digits)</li>
                </ul>
            </li>
            <li><strong>Expected flow:</strong> PayHere Form → Payment → Success/Cancel → Return to Application</li>
        </ol>
    </div>

    <h2>🚀 Start PayHere Test</h2>
    <form action="<?= htmlspecialchars($payhere_data['url']) ?>" method="POST" id="payhere-test-form">
        <?php foreach ($payhere_data['data'] as $key => $value): ?>
            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
        <?php endforeach; ?>
        
        <div style="text-align: center; margin: 30px 0;">
            <button type="submit" class="btn btn-success" style="font-size: 1.2rem; font-weight: bold;">
                🧪 Start PayHere Test Payment (LKR <?= number_format($test_data['rent_amount'] + $test_data['security_deposit']) ?>)
            </button>
        </div>
    </form>

    <div class="success-box">
        <strong>✅ What Should Happen:</strong>
        <ol>
            <li>Click the button above to go to PayHere</li>
            <li>PayHere will show a payment form</li>
            <li>Use the test card numbers provided</li>
            <li>Complete the payment</li>
            <li>PayHere redirects back with <code>status_code=2</code> (success)</li>
            <li>Application shows "Payment successful!" message</li>
        </ol>
    </div>

    <div class="info-box">
        <strong>🔍 Debugging Your Current Issue:</strong>
        <p>Your URLs like <code>payment_return.php?property_id=13&order_id=RENTAL_7_13_1759750808</code> show that:</p>
        <ul>
            <li>✅ PayHere integration is working (generates order_id)</li>
            <li>✅ PayHere redirects back to your application</li>
            <li>❌ Missing <code>status_code</code> means payment wasn't completed</li>
            <li>🎯 <strong>Solution:</strong> Complete the full PayHere payment form</li>
        </ul>
    </div>

    <h2>📖 Alternative Test Scenarios</h2>
    <div style="text-align: center;">
        <a href="customer/payment_return.php?property_id=13&order_id=TEST_SUCCESS&payment_id=PH_TEST_123&status_code=2&payhere_amount=75000.00&payhere_currency=LKR" class="btn btn-success">
            ✅ Test Successful Payment Return
        </a>
        <a href="customer/payment_return.php?property_id=13&order_id=TEST_CANCEL&status_code=0" class="btn btn-warning">
            ❌ Test Cancelled Payment Return  
        </a>
        <a href="customer/payment_return.php?property_id=13&order_id=TEST_INCOMPLETE" class="btn" style="background: #6c757d;">
            ⚠️ Test Incomplete Return (Your Current Issue)
        </a>
    </div>

    <script>
        document.getElementById('payhere-test-form').addEventListener('submit', function() {
            // Add a small delay to show the user what's happening
            const button = this.querySelector('button');
            button.innerHTML = '🔄 Redirecting to PayHere...';
            button.disabled = true;
        });
    </script>

</body>
</html>