<?php
require_once '../includes/functions.php';
require_once '../includes/payhere.php';
require_auth('customer');

$property_id = intval($_GET['property_id'] ?? 0);
if (!$property_id) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Check if customer has a confirmed visit for this property
$visit_check = $pdo->prepare("SELECT * FROM property_visits WHERE property_id = ? AND customer_id = ? AND status = 'confirmed'");
$visit_check->execute([$property_id, $user_id]);

if (!$visit_check->fetch()) {
    header('Location: property_details.php?id=' . $property_id);
    exit();
}

// Get property details
$property_stmt = $pdo->prepare("
    SELECT p.*, u.full_name as owner_name, u.phone as owner_phone 
    FROM properties p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.id = ? AND p.status = 'approved'
");
$property_stmt->execute([$property_id]);
$property = $property_stmt->fetch();

if (!$property) {
    header('Location: dashboard.php');
    exit();
}

// Handle rental initiation with PayHere tokenization
if ($_POST && isset($_POST['start_rental'])) {
    $lease_start_date = sanitize_input($_POST['lease_start_date'] ?? '');
    $lease_duration = intval($_POST['lease_duration'] ?? 12);
    
    if (empty($lease_start_date)) {
        $error = 'Please select a lease start date.';
    } else if (strtotime($lease_start_date) < strtotime('today')) {
        $error = 'Lease start date cannot be in the past.';
    } else {
        try {
            $pdo->beginTransaction();
            
            // Calculate lease end date
            $lease_end_date = date('Y-m-d', strtotime($lease_start_date . ' + ' . $lease_duration . ' months'));
            
            // Create rental agreement
            $rental_stmt = $pdo->prepare("
                INSERT INTO rental_agreements (property_id, customer_id, owner_id, monthly_rent, security_deposit, lease_start_date, lease_end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $rental_stmt->execute([
                $property_id, 
                $user_id, 
                $property['owner_id'], 
                $property['rent_amount'], 
                $property['security_deposit'], 
                $lease_start_date, 
                $lease_end_date
            ]);
            
            // Update property status to rented
            $update_property = $pdo->prepare("UPDATE properties SET status = 'rented' WHERE id = ?");
            $update_property->execute([$property_id]);
            
            $pdo->commit();
            
            // Redirect to PayHere tokenization
            $user_info = get_user_info($user_id);
            $return_url = SITE_URL . '/customer/payment_return.php?property_id=' . $property_id;
            $notify_url = SITE_URL . '/customer/payment_notify.php';
            
            $payhere_data = PayHere::generateTokenizationURL(
                $user_id,
                $user_info['full_name'],
                $user_info['email'],
                $user_info['phone'],
                $return_url,
                $notify_url
            );
            
            // Store PayHere checkout data in session
            $_SESSION['payhere_checkout'] = $payhere_data;
            $_SESSION['rental_property_id'] = $property_id;
            
            $success = 'Rental agreement created! Please complete the payment setup to activate your rental.';
            $show_payhere = true;
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Failed to create rental agreement. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Start Rental Process - <?= htmlspecialchars($property['title']) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .payment-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .card-input {
            background: #f8f9fa;
            border: 2px solid #dee2e6;
            font-family: monospace;
            font-size: 1.1rem;
        }
        
        .security-notice {
            background: #e7f5e7;
            border-left: 4px solid #28a745;
            padding: 1rem;
            margin: 1rem 0;
        }
        
        .rental-summary {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }
    </style>
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
        <div style="margin-bottom: 2rem;">
            <a href="property_details.php?id=<?= $property['id'] ?>" class="btn btn-secondary">← Back to Property Details</a>
        </div>

        <h1>Start Rental Process</h1>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
            <div style="text-align: center; margin: 2rem 0;">
                <a href="my_rentals.php" class="btn btn-primary">View My Rentals</a>
                <a href="dashboard.php" class="btn btn-secondary">Browse More Properties</a>
            </div>
        <?php else: ?>

        <div class="payment-form">
            <!-- Rental Summary -->
            <div class="rental-summary">
                <h3>Rental Summary</h3>
                <div style="margin-top: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><strong>Property:</strong></span>
                        <span><?= htmlspecialchars($property['title']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><strong>Location:</strong></span>
                        <span><?= htmlspecialchars($property['city'] . ', ' . $property['state']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><strong>Monthly Rent:</strong></span>
                        <span><?= format_currency($property['rent_amount']) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span><strong>Security Deposit:</strong></span>
                        <span><?= format_currency($property['security_deposit']) ?></span>
                    </div>
                    <hr>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                        <span>Total Due Today:</span>
                        <span><?= format_currency($property['rent_amount'] + $property['security_deposit']) ?></span>
                    </div>
                </div>
            </div>

            <div class="security-notice">
                <strong>🔒 Secure Payment Processing</strong><br>
                Your payment information is encrypted and secure. We guarantee payment delivery to property owners.
            </div>

            <form method="POST" data-validate="true">
                <!-- Lease Details -->
                <h3>Lease Details</h3>
                
                <div class="row">
                    <div class="col-2">
                        <div class="form-group">
                            <label for="lease_start_date" class="form-label">Lease Start Date *</label>
                            <input type="date" 
                                   id="lease_start_date" 
                                   name="lease_start_date" 
                                   class="form-control" 
                                   min="<?= date('Y-m-d', strtotime('+1 week')) ?>"
                                   value="<?= $_POST['lease_start_date'] ?? '' ?>"
                                   required>
                        </div>
                    </div>
                    
                    <div class="col-2">
                        <div class="form-group">
                            <label for="lease_duration" class="form-label">Lease Duration (months) *</label>
                            <select id="lease_duration" name="lease_duration" class="form-select" required>
                                <option value="6" <?= ($_POST['lease_duration'] ?? '12') == '6' ? 'selected' : '' ?>>6 months</option>
                                <option value="12" <?= ($_POST['lease_duration'] ?? '12') == '12' ? 'selected' : '' ?>>12 months</option>
                                <option value="18" <?= ($_POST['lease_duration'] ?? '12') == '18' ? 'selected' : '' ?>>18 months</option>
                                <option value="24" <?= ($_POST['lease_duration'] ?? '12') == '24' ? 'selected' : '' ?>>24 months</option>
                            </select>
                        </div>
                    </div>
                </div>

                <?php if (isset($show_payhere) && $show_payhere): ?>
                    <!-- PayHere Checkout -->
                    <h3 style="margin-top: 2rem;">Complete Payment Setup</h3>
                    
                    <div class="security-notice">
                        <strong>🔒 Secure PayHere Payment Setup</strong><br>
                        You will be redirected to PayHere's secure payment gateway to set up your payment method for automatic rent collection.
                    </div>
                    
                    <form action="<?= $_SESSION['payhere_checkout']['url'] ?>" method="POST" id="payhere-form">
                        <?php foreach ($_SESSION['payhere_checkout']['data'] as $key => $value): ?>
                            <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                        <?php endforeach; ?>
                        
                        <div style="text-align: center; margin: 2rem 0;">
                            <button type="submit" class="btn btn-primary" style="font-size: 1.2rem; padding: 1rem 2rem;">
                                Continue to PayHere Payment Setup
                            </button>
                        </div>
                    </form>
                <?php endif; ?>

                <!-- Terms and Conditions -->
                <div style="margin: 2rem 0; padding: 1rem; background: #f8f9fa; border-radius: 5px;">
                    <h4>Terms and Conditions</h4>
                    <ul style="margin-top: 1rem; line-height: 1.6;">
                        <li>Monthly rent will be automatically charged on the same date each month</li>
                        <li>Security deposit will be refunded after lease termination (minus any damages)</li>
                        <li>Service provider charges <?= COMMISSION_PERCENTAGE ?>% commission on monthly rent</li>
                        <li>Payment failure may result in lease termination</li>
                        <li>All payments are guaranteed to be delivered to property owners</li>
                    </ul>
                    
                    <label style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
                        <input type="checkbox" required>
                        <span>I agree to the terms and conditions above</span>
                    </label>
                </div>

                <div style="text-align: center;">
                    <button type="submit" name="start_rental" class="btn btn-primary" style="font-size: 1.1rem; padding: 1rem 3rem;">
                        Process Payment & Start Rental
                    </button>
                </div>
            </form>
        </div>

        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Property Rental System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
    <script>
        // Format card number input
        document.getElementById('card_number').addEventListener('input', function(e) {
            let value = e.target.value.replace(/\s+/g, '').replace(/[^0-9]/gi, '');
            let formattedValue = value.match(/.{1,4}/g)?.join(' ') || value;
            
            if (value.length > 16) {
                formattedValue = formattedValue.substring(0, 19);
            }
            
            e.target.value = formattedValue;
        });

        // Validate CVV based on card type
        document.getElementById('cvv').addEventListener('input', function(e) {
            let value = e.target.value.replace(/[^0-9]/gi, '');
            let cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
            
            // American Express uses 4-digit CVV
            let maxLength = (cardNumber.startsWith('34') || cardNumber.startsWith('37')) ? 4 : 3;
            
            if (value.length > maxLength) {
                value = value.substring(0, maxLength);
            }
            
            e.target.value = value;
        });
    </script>
</body>
</html>