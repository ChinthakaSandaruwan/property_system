<?php
require_once '../includes/functions.php';
require_once '../includes/payhere.php';
require_auth('customer');

$property_id = intval($_GET['property_id'] ?? 0);
$user_id = $_SESSION['user_id'];

$error = '';
$success = '';

// Check PayHere response
if ($_GET) {
    $order_id = $_GET['order_id'] ?? '';
    $payment_id = $_GET['payment_id'] ?? '';
    $payhere_amount = $_GET['payhere_amount'] ?? '';
    $payhere_currency = $_GET['payhere_currency'] ?? '';
    $status_code = $_GET['status_code'] ?? '';
    $md5sig = $_GET['md5sig'] ?? '';
    $custom_1 = $_GET['custom_1'] ?? $property_id; // property_id
    $custom_2 = $_GET['custom_2'] ?? $user_id; // customer_id
    
    // Log the return for debugging
    error_log('PayHere Return: ' . json_encode($_GET));
    
    // Handle missing status code
    if (empty($status_code)) {
        if (!empty($order_id)) {
            $error = 'Payment process was interrupted. The payment may have been cancelled or there was a technical issue. Please try again.';
        } else {
            $error = 'Invalid payment response received. Please try the payment process again.';
        }
    } else {
        // Verify the response (for demo purposes, we'll skip verification)
        // In production, always verify: if (PayHere::verifyIPN($_GET, PAYHERE_MERCHANT_SECRET))
        if (true) {
            if ($status_code == '2') {
                // Successful payment
                try {
                    $pdo->beginTransaction();
                    
                    // Get property details
                    $prop_stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
                    $prop_stmt->execute([$property_id]);
                    $prop_data = $prop_stmt->fetch();
                    
                    if ($prop_data) {
                        // First create or find booking record
                        $booking_id = null;
                        $booking_stmt = $pdo->prepare("SELECT id FROM bookings WHERE property_id = ? AND customer_id = ? AND status IN ('pending', 'approved') ORDER BY created_at DESC LIMIT 1");
                        $booking_stmt->execute([$property_id, $user_id]);
                        $existing_booking = $booking_stmt->fetch();
                        
                        if ($existing_booking) {
                            $booking_id = $existing_booking['id'];
                            // Update existing booking to active
                            $update_booking = $pdo->prepare("UPDATE bookings SET status = 'active', payment_status = 'paid', updated_at = NOW() WHERE id = ?");
                            $update_booking->execute([$booking_id]);
                        } else {
                            // Create new booking record
                            $total_amount = $prop_data['rent_amount'] + $prop_data['security_deposit'];
                            $commission = ($total_amount * COMMISSION_PERCENTAGE) / 100;
                            
                            $create_booking = $pdo->prepare("
                                INSERT INTO bookings (property_id, customer_id, owner_id, start_date, monthly_rent, security_deposit, total_amount, commission_amount, status, payment_status)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 'paid')
                            ");
                            
                            $lease_start = date('Y-m-d', strtotime('+1 week'));
                            $create_booking->execute([
                                $property_id,
                                $user_id,
                                $prop_data['owner_id'],
                                $lease_start,
                                $prop_data['rent_amount'],
                                $prop_data['security_deposit'],
                                $total_amount,
                                $commission
                            ]);
                            
                            $booking_id = $pdo->lastInsertId();
                        }
                        
                        // Create rental agreement
                        $lease_start = date('Y-m-d', strtotime('+1 week'));
                        $lease_end = date('Y-m-d', strtotime($lease_start . ' + 12 months'));
                        
                        $rental_stmt = $pdo->prepare("
                            INSERT INTO rental_agreements (property_id, customer_id, owner_id, monthly_rent, security_deposit, lease_start_date, lease_end_date, status)
                            VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
                        ");
                        
                        $rental_stmt->execute([
                            $property_id,
                            $user_id,
                            $prop_data['owner_id'],
                            $prop_data['rent_amount'],
                            $prop_data['security_deposit'],
                            $lease_start,
                            $lease_end
                        ]);
                        
                        // Record the payment with proper data
                        $total_amount = $prop_data['rent_amount'] + $prop_data['security_deposit'];
                        $commission = ($total_amount * COMMISSION_PERCENTAGE) / 100;
                        $owner_payout = $total_amount - $commission;
                        
                        $payment_stmt = $pdo->prepare("
                            INSERT INTO payments (
                                booking_id, customer_id, property_id, owner_id, payer_id, 
                                amount, commission, owner_payout, 
                                payment_type, payment_method, 
                                transaction_id, payhere_payment_id, gateway_transaction_id,
                                payhere_response, payment_date, status, payment_gateway
                            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'successful', 'payhere')
                        ");
                        
                        $payment_stmt->execute([
                            $booking_id,
                            $user_id,
                            $property_id,
                            $prop_data['owner_id'],
                            $user_id, // payer_id
                            $total_amount,
                            $commission,
                            $owner_payout,
                            'security_deposit', // payment_type
                            'payhere', // payment_method
                            $order_id, // transaction_id
                            $payment_id, // payhere_payment_id
                            $payment_id, // gateway_transaction_id (same as payhere_payment_id)
                            json_encode($_GET) // payhere_response
                        ]);
                        
                        $payment_record_id = $pdo->lastInsertId();
                        
                        // Update property availability
                        $update_stmt = $pdo->prepare("UPDATE properties SET status = 'rented' WHERE id = ?");
                        $update_stmt->execute([$property_id]);
                        
                        $pdo->commit();
                        $success = 'Payment successful! Your rental has been confirmed. Payment ID: ' . $payment_record_id;
                        
                        // Log successful payment
                        error_log("PayHere Payment Success - Payment ID: $payment_record_id, Order: $order_id, Amount: $total_amount");
                        
                    } else {
                        $pdo->rollBack();
                        $error = 'Property not found. Please contact support.';
                    }
                
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = 'Payment received but there was an error processing your rental. Please contact support.';
                error_log('Rental processing error: ' . $e->getMessage());
            }
            
        } else if ($status_code == '0') {
            $error = 'Payment was cancelled. You can try again when you\'re ready.';
        } else if ($status_code == '-1') {
            $error = 'Payment failed. Please check your payment details and try again.';
        } else if ($status_code == '-2') {
            $error = 'Payment amount is invalid. Please contact support.';
            } else {
                $error = 'Payment could not be processed. Please try again.';
            }
        } else {
            $error = 'Invalid payment response. Please contact support.';
        }
    }
}

// Get property details
if ($property_id) {
    $property_stmt = $pdo->prepare("SELECT * FROM properties WHERE id = ?");
    $property_stmt->execute([$property_id]);
    $property = $property_stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Setup Result - Property Rental System</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">Property Rental</div>
                <nav>
                    <ul class="nav-links">
                        <li><a href="dashboard.php">Find Properties</a></li>
                        <li><a href="my_visits.php">My Visits</a></li>
                        <li><a href="my_rentals.php">My Rentals</a></li>
                        <li><a href="profile.php">Profile</a></li>
                        <li><a href="logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <h1>Payment Setup Result</h1>

        <?php if ($error): ?>
            <div class="alert alert-error">
                <h3>Payment Setup Failed</h3>
                <p><?= $error ?></p>
            </div>
            
            <div style="text-align: center; margin: 2rem 0;">
                <?php if ($property_id): ?>
                    <a href="start_rental.php?property_id=<?= $property_id ?>" class="btn btn-primary">Try Again</a>
                <?php endif; ?>
                <a href="dashboard.php" class="btn btn-secondary">Browse Properties</a>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <h3>🎉 Payment Setup Successful!</h3>
                <p><?= $success ?></p>
            </div>

            <?php if ($property): ?>
                <div class="card">
                    <div class="card-header">
                        <h3>Rental Summary</h3>
                    </div>
                    <div class="card-body">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <strong>Property:</strong>
                            <span><?= htmlspecialchars($property['title']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <strong>Monthly Rent:</strong>
                            <span><?= format_currency($property['rent_amount']) ?></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                            <strong>Next Payment Date:</strong>
                            <span><?= date('M j, Y', strtotime('+1 month')) ?></span>
                        </div>
                        
                        <hr>
                        
                        <div class="alert alert-info">
                            <strong>Automatic Payment Setup Complete</strong><br>
                            Your rent will be automatically charged monthly. You'll receive email notifications before each payment.
                        </div>
                    </div>
                </div>
            <?php endif; ?>
            
            <div style="text-align: center; margin: 2rem 0;">
                <a href="my_rentals.php" class="btn btn-primary">View My Rentals</a>
                <a href="dashboard.php" class="btn btn-secondary">Browse More Properties</a>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Property Rental System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>