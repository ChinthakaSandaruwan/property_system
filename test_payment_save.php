<?php
require_once 'includes/config.php';

echo "<h1>Test Payment Database Saving</h1>";
echo "<p>This script will simulate a successful PayHere payment and show the database records created.</p>";
echo "<hr>";

// Test parameters that simulate a successful PayHere return
$test_params = [
    'property_id' => '13',
    'order_id' => 'RENTAL_7_13_' . time(),
    'payment_id' => 'PH_TEST_' . time(),
    'payhere_amount' => '75000.00',
    'payhere_currency' => 'LKR',
    'status_code' => '2', // Success
    'md5sig' => 'test_signature_' . time(),
    'custom_1' => '13', // property_id
    'custom_2' => '7'   // customer_id
];

echo "<h2>Simulated PayHere Response</h2>";
echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
echo "<tr style='background: #f0f0f0;'><th>Parameter</th><th>Value</th></tr>";
foreach ($test_params as $key => $value) {
    echo "<tr><td><strong>$key</strong></td><td>$value</td></tr>";
}
echo "</table>";

// Build the test URL
$query_string = http_build_query($test_params);
$test_url = "customer/payment_return.php?" . $query_string;

echo "<h2>Test Actions</h2>";
echo "<div style='text-align: center; margin: 30px 0;'>";
echo "<a href='$test_url' target='_blank' style='display: inline-block; padding: 15px 30px; background: #28a745; color: white; text-decoration: none; border-radius: 5px; margin: 10px;'>🧪 Test Payment Processing (Opens in New Tab)</a>";
echo "</div>";

// Show current payment records before test
echo "<h2>Current Payment Records (Before Test)</h2>";
try {
    $stmt = $pdo->query("SELECT id, booking_id, customer_id, property_id, amount, payment_type, payment_method, status, created_at FROM payments ORDER BY created_at DESC LIMIT 10");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Booking ID</th><th>Customer</th><th>Property</th><th>Amount</th><th>Type</th><th>Method</th><th>Status</th><th>Created</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['booking_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['property_id']) . "</td>";
            echo "<td>LKR " . number_format($row['amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_type']) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_method']) . "</td>";
            echo "<td><span style='padding: 3px 8px; border-radius: 3px; background: " . ($row['status'] === 'successful' ? '#d4edda' : '#f8d7da') . ";'>" . htmlspecialchars($row['status']) . "</span></td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No payment records found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

// Show current booking records
echo "<h2>Current Booking Records (Before Test)</h2>";
try {
    $stmt = $pdo->query("SELECT id, property_id, customer_id, monthly_rent, security_deposit, total_amount, status, payment_status, created_at FROM bookings ORDER BY created_at DESC LIMIT 10");
    if ($stmt->rowCount() > 0) {
        echo "<table border='1' cellpadding='10' cellspacing='0' style='border-collapse: collapse; width: 100%;'>";
        echo "<tr style='background: #f0f0f0;'><th>ID</th><th>Property</th><th>Customer</th><th>Monthly Rent</th><th>Security Deposit</th><th>Total</th><th>Status</th><th>Payment Status</th><th>Created</th></tr>";
        while ($row = $stmt->fetch()) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($row['id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['property_id']) . "</td>";
            echo "<td>" . htmlspecialchars($row['customer_id']) . "</td>";
            echo "<td>LKR " . number_format($row['monthly_rent'], 2) . "</td>";
            echo "<td>LKR " . number_format($row['security_deposit'], 2) . "</td>";
            echo "<td>LKR " . number_format($row['total_amount'], 2) . "</td>";
            echo "<td>" . htmlspecialchars($row['status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['payment_status']) . "</td>";
            echo "<td>" . htmlspecialchars($row['created_at']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>No booking records found.</p>";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
}

echo "<hr>";
echo "<h2>Instructions</h2>";
echo "<div style='background: #e7f3ff; padding: 15px; border-radius: 5px;'>";
echo "<ol>";
echo "<li>Click the 'Test Payment Processing' button above</li>";
echo "<li>The system will simulate a successful PayHere payment</li>";
echo "<li>Check if payment and booking records are created in the database</li>";
echo "<li>Refresh this page to see the new records</li>";
echo "</ol>";
echo "</div>";

echo "<div style='text-align: center; margin: 20px 0;'>";
echo "<a href='check_payment_tables.php' style='padding: 10px 20px; background: #17a2b8; color: white; text-decoration: none; border-radius: 5px;'>📊 View Database Structure</a>";
echo "<a href='javascript:location.reload()' style='padding: 10px 20px; background: #6c757d; color: white; text-decoration: none; border-radius: 5px; margin-left: 10px;'>🔄 Refresh Page</a>";
echo "</div>";
?>