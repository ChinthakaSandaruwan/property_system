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
    
    // Verify the response
    if (PayHere::verifyIPN($_GET, PAYHERE_MERCHANT_SECRET)) {
        if ($status_code == '2') {
            // Successful tokenization
            $payment_token = $_GET['payment_token'] ?? '';
            $card_holder_name = $_GET['card_holder_name'] ?? '';
            $card_no = $_GET['card_no'] ?? '';
            
            if ($payment_token) {
                // Store the token
                $card_last4 = substr($card_no, -4);
                $card_brand = '';
                
                // Determine card brand
                if (substr($card_no, 0, 1) == '4') {
                    $card_brand = 'Visa';
                } else if (preg_match('/^5[1-5]/', $card_no)) {
                    $card_brand = 'Mastercard';
                } else if (preg_match('/^3[47]/', $card_no)) {
                    $card_brand = 'American Express';
                }
                
                if (PayHere::storeToken($user_id, $payment_token, $card_last4, $card_brand, $card_holder_name)) {
                    $success = 'Payment method successfully set up! Your monthly rent will be automatically charged.';
                    
                    // Process first month's payment
                    $property_stmt = $pdo->prepare("SELECT rent_amount FROM properties WHERE id = ?");
                    $property_stmt->execute([$property_id]);
                    $property = $property_stmt->fetch();
                    
                    if ($property) {
                        $payment_result = PayHere::processRecurringPayment($payment_token, $property['rent_amount'], $user_id, $property_id);
                        
                        if ($payment_result['success']) {
                            $success .= ' First month\'s rent payment has been processed successfully.';
                        } else {
                            $error = 'Payment method set up, but first payment failed: ' . $payment_result['error'];
                        }
                    }
                } else {
                    $error = 'Failed to store payment method. Please try again.';
                }
            } else {
                $error = 'Payment token not received. Please try again.';
            }
        } else if ($status_code == '0') {
            $error = 'Payment was cancelled. Please try again to complete your rental setup.';
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