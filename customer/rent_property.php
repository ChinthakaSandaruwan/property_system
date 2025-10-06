<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';
require_once '../includes/payhere.php';
require_once '../includes/settings_helper.php';

// Get site name
$site_name = getSiteName();

// Check if user is logged in and is a customer
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header('Location: ../login.php');
    exit();
}

$property_id = $_GET['property_id'] ?? null;
if (!$property_id) {
    header('Location: ../index.php');
    exit();
}

$customer_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Get property details
$stmt = $pdo->prepare("SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                       FROM properties p 
                       JOIN users u ON p.owner_id = u.id 
                       WHERE p.id = ? AND p.status = 'approved' AND p.is_available = 1");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: ../index.php');
    exit();
}

// Get customer details for payment
$customer_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$customer_stmt->execute([$customer_id]);
$customer = $customer_stmt->fetch(PDO::FETCH_ASSOC);

// Handle PayHere payment initiation
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'pay_now') {
    $return_url = SITE_URL . '/customer/payment_return.php?property_id=' . $property_id;
    $notify_url = SITE_URL . '/customer/payment_notify.php';
    
    try {
        $payhere_data = PayHere::generateRentalPaymentURL(
            $customer_id,
            $property_id,
            $customer['full_name'],
            $customer['email'],
            $customer['phone'],
            $property['title'],
            $property['rent_amount'],
            $property['security_deposit'],
            $return_url,
            $notify_url
        );
        
        // Store payment data in session for reference
        $_SESSION['payhere_checkout'] = $payhere_data;
        $_SESSION['rental_property_id'] = $property_id;
        
        $show_payhere = true;
        
    } catch (Exception $e) {
        $error = 'Failed to initiate payment. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Property - <?= htmlspecialchars($site_name) ?></title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .rental-header {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            text-align: center;
        }

        .rental-title {
            font-size: 32px;
            margin-bottom: 10px;
        }

        .rental-subtitle {
            font-size: 16px;
            opacity: 0.9;
        }

        .property-summary {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
            border-left: 5px solid #28a745;
        }

        .property-title {
            font-size: 24px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .property-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .detail-item {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .rental-form {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .form-section {
            margin-bottom: 30px;
        }

        .section-title {
            font-size: 20px;
            font-weight: bold;
            color: #333;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #28a745;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-label {
            display: block;
            margin-bottom: 8px;
            font-weight: bold;
            color: #555;
        }

        .required {
            color: #dc3545;
        }

        .form-control {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 14px;
            transition: border-color 0.3s ease;
            box-sizing: border-box;
        }

        .form-control:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .form-select {
            width: 100%;
            padding: 12px;
            border: 2px solid #e9ecef;
            border-radius: 5px;
            font-size: 14px;
            background-color: white;
            transition: border-color 0.3s ease;
        }

        .form-select:focus {
            outline: none;
            border-color: #28a745;
            box-shadow: 0 0 0 3px rgba(40, 167, 69, 0.1);
        }

        .textarea {
            min-height: 80px;
            resize: vertical;
        }

        .btn {
            padding: 12px 30px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: all 0.3s ease;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #1e7e34;
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
            margin-right: 15px;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .application-status {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            text-align: center;
        }

        .status-icon {
            font-size: 64px;
            margin-bottom: 20px;
        }

        .status-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 15px;
        }

        .status-pending {
            color: #ffc107;
        }

        .status-approved {
            color: #28a745;
        }

        .status-rejected {
            color: #dc3545;
        }

        .application-details {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: left;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
            margin-bottom: 10px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
        }

        .alert {
            padding: 15px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .income-requirement {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .requirement-text {
            color: #856404;
            font-weight: bold;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        @media (max-width: 768px) {
            .form-grid {
                grid-template-columns: 1fr;
            }
            
            .property-details {
                grid-template-columns: 1fr;
            }
            
            .detail-grid {
                grid-template-columns: 1fr;
                gap: 5px;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="header-content">
                <div class="logo">SmartRent</div>
                <nav>
                    <ul class="nav-links">
                        <li><a href="../index.php">Home</a></li>
                        <li><a href="../properties.php">Properties</a></li>
                        <li><a href="dashboard.php">Dashboard</a></li>
                        <li><a href="wishlist.php">Wishlist</a></li>
                        <li><a href="my_visits.php">My Visits</a></li>
                        <li><a href="../logout.php">Logout</a></li>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <div class="rental-header">
        <div class="container">
            <h1 class="rental-title">🏠 Rent This Property</h1>
            <p class="rental-subtitle">Complete your rental application to start your journey</p>
        </div>
    </div>

    <main class="container">
        <a href="../property_details.php?id=<?= $property_id ?>" class="back-link">← Back to Property Details</a>

        <?php if ($message): ?>
            <div class="alert alert-success"><?= $message ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>

        <!-- Property Summary -->
        <div class="property-summary">
            <h2 class="property-title"><?= htmlspecialchars($property['title']) ?></h2>
            <div class="property-details">
                <div class="detail-item">
                    <strong>📍 Location:</strong> <?= htmlspecialchars($property['address'] . ', ' . $property['city']) ?>
                </div>
                <div class="detail-item">
                    <strong>💰 Rent:</strong> Rs. <?= number_format($property['rent_amount'], 2) ?> / month
                </div>
                <div class="detail-item">
                    <strong>🏠 Type:</strong> <?= ucfirst($property['property_type']) ?>
                </div>
                <div class="detail-item">
                    <strong>🛏️ Bedrooms:</strong> <?= $property['bedrooms'] ?>
                </div>
                <div class="detail-item">
                    <strong>🚿 Bathrooms:</strong> <?= $property['bathrooms'] ?>
                </div>
                <div class="detail-item">
                    <strong>👨‍💼 Owner:</strong> <?= htmlspecialchars($property['owner_name']) ?>
                </div>
            </div>
        </div>

        <?php if (isset($show_payhere) && $show_payhere): ?>
            <!-- PayHere Payment Form -->
            <div class="rental-form">
                <h3 style="color: #28a745; margin-bottom: 20px; text-align: center;">Complete Your Rental Payment</h3>
                
                <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
                    <h4>Payment Summary</h4>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Monthly Rent:</span>
                        <span>Rs. <?= number_format($property['rent_amount'], 2) ?></span>
                    </div>
                    <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                        <span>Security Deposit:</span>
                        <span>Rs. <?= number_format($property['security_deposit'], 2) ?></span>
                    </div>
                    <hr>
                    <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.1rem;">
                        <span>Total Amount:</span>
                        <span>Rs. <?= number_format($property['rent_amount'] + $property['security_deposit'], 2) ?></span>
                    </div>
                </div>
                
                <div style="background: #e7f5e7; padding: 15px; border-radius: 5px; margin-bottom: 20px; text-align: center;">
                    <i class="fas fa-shield-alt" style="color: #28a745; margin-right: 5px;"></i>
                    <strong>Secure Payment with PayHere</strong><br>
                    <small>Your payment is protected by bank-level security</small>
                </div>
                
                <form action="<?= $_SESSION['payhere_checkout']['url'] ?>" method="POST" id="payhere-form" style="text-align: center;">
                    <?php foreach ($_SESSION['payhere_checkout']['data'] as $key => $value): ?>
                        <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
                    <?php endforeach; ?>
                    
                    <button type="submit" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 40px; margin: 10px;">
                        <i class="fas fa-credit-card"></i> Pay with PayHere
                    </button>
                    
                    <br><br>
                    <a href="?property_id=<?= $property_id ?>" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back
                    </a>
                </form>
            </div>
        <?php else: ?>
            <!-- Rental Payment Form -->
            <div class="rental-form">
                <div style="text-align: center; padding: 20px;">
                    <div style="font-size: 3rem; margin-bottom: 20px;">🏠</div>
                    <h2 style="color: #28a745; margin-bottom: 15px;">Ready to Rent This Property?</h2>
                    <p style="color: #666; font-size: 1.1rem; margin-bottom: 30px;">Secure your rental with a safe and easy payment process.</p>
                    
                    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px; text-align: left;">
                        <h4>What you're paying for:</h4>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><i class="fas fa-home" style="color: #28a745;"></i> Monthly Rent:</span>
                            <span><strong>Rs. <?= number_format($property['rent_amount'], 2) ?></strong></span>
                        </div>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span><i class="fas fa-shield-alt" style="color: #28a745;"></i> Security Deposit:</span>
                            <span><strong>Rs. <?= number_format($property['security_deposit'], 2) ?></strong></span>
                        </div>
                        <hr>
                        <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 1.2rem; color: #28a745;">
                            <span>Total Amount:</span>
                            <span>Rs. <?= number_format($property['rent_amount'] + $property['security_deposit'], 2) ?></span>
                        </div>
                    </div>
                    
                    <form method="POST">
                        <input type="hidden" name="action" value="pay_now">
                        <button type="submit" class="btn btn-primary" style="font-size: 1.2rem; padding: 15px 40px; margin: 10px;">
                            <i class="fas fa-credit-card"></i> Proceed to Payment
                        </button>
                    </form>
                    
                    <div style="display: flex; gap: 15px; justify-content: center; flex-wrap: wrap; margin-top: 20px;">
                        <a href="../property_details.php?id=<?= $property_id ?>" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Back to Property Details
                        </a>
                        <a href="../properties.php" class="btn btn-outline">
                            <i class="fas fa-search"></i> Browse More Properties
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 SmartRent Property Management System. All rights reserved.</p>
        </div>
    </footer>

</body>
</html>