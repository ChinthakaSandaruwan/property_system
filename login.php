<?php
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_POST) {
    $phone = sanitize_input($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $step = $_POST['step'] ?? 'phone';
    
    if ($step === 'phone') {
        // Step 1: Phone number verification
        if (empty($phone) || !validate_phone($phone)) {
            $error = 'Please enter a valid Sri Lankan mobile number (e.g., 0771234567).';
        } else {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Send OTP
                $otp_code = generate_otp();
                if (store_otp($phone, $otp_code)) {
                    send_sms($phone, "Your login OTP is: $otp_code");
                    
                    // Show OTP on screen for development
                    $dev_otp = get_dev_otp($phone);
                    if ($dev_otp) {
                        $success = '<strong>📱 OTP sent to your phone: ' . $phone . '</strong><br>';
                        $success .= '<div style="background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #38a169;">';
                        $success .= '<strong>🔐 Your OTP Code: <span style="font-family: monospace; font-size: 20px; color: #38a169; background: white; padding: 5px 10px; border-radius: 3px;">' . $dev_otp . '</span></strong><br>';
                        $success .= '<small>⏰ This code will expire in 5 minutes</small>';
                        $success .= '</div>';
                        $success .= '<p><strong>Alternative codes:</strong> You can also use <code>123456</code> or <code>000000</code></p>';
                    } else {
                        $success = 'OTP sent to your phone number. Use <code>123456</code> or <code>000000</code> if you don\'t receive it.';
                    }
                    
                    $step = 'otp';
                } else {
                    $error = 'Failed to send OTP. Please try again.';
                }
            } else {
                $error = 'Phone number not found. Please register first.';
            }
        }
    } else if ($step === 'otp') {
        // Step 2: OTP verification
        $otp_code = sanitize_input($_POST['otp_code'] ?? '');
        
        if (empty($otp_code) || !preg_match('/^\d{6}$/', $otp_code)) {
            $error = 'Please enter a valid 6-digit OTP.';
        } else if (verify_otp($phone, $otp_code)) {
            // Get user info and login
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch();
            
            if ($user) {
                // Check user status before allowing login
                if ($user['user_type'] === 'owner' && $user['status'] === 'pending') {
                    $error = '<div style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffeaa7; margin: 15px 0;">';
                    $error .= '<h4 style="color: #856404; margin-bottom: 10px;">⏳ Account Pending Approval</h4>';
                    $error .= '<p style="color: #856404; margin-bottom: 10px;">Your property owner account is waiting for admin approval.</p>';
                    $error .= '<p style="color: #856404; margin-bottom: 0;">You will receive a notification when your account is approved. Please check back later or contact support if you have questions.</p>';
                    $error .= '</div>';
                } else if ($user['user_type'] === 'owner' && $user['status'] === 'rejected') {
                    $error = '<div style="background: #f8d7da; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 15px 0;">';
                    $error .= '<h4 style="color: #721c24; margin-bottom: 10px;">❌ Account Rejected</h4>';
                    $error .= '<p style="color: #721c24; margin-bottom: 10px;">Unfortunately, your property owner account has been rejected.</p>';
                    if (!empty($user['rejection_reason'])) {
                        $error .= '<p style="color: #721c24; margin-bottom: 10px;"><strong>Reason:</strong> ' . htmlspecialchars($user['rejection_reason']) . '</p>';
                    }
                    $error .= '<p style="color: #721c24; margin-bottom: 0;">Please contact support for more information or to reapply.</p>';
                    $error .= '</div>';
                } else if ($user['status'] === 'suspended') {
                    $error = '<div style="background: #f8d7da; padding: 20px; border-radius: 8px; border: 1px solid #f5c6cb; margin: 15px 0;">';
                    $error .= '<h4 style="color: #721c24; margin-bottom: 10px;">⚠️ Account Suspended</h4>';
                    $error .= '<p style="color: #721c24; margin-bottom: 0;">Your account has been suspended. Please contact support for assistance.</p>';
                    $error .= '</div>';
                } else if (in_array($user['status'], ['active', 'approved'])) {
                    // Allow login for active/approved users
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['user_name'] = $user['full_name'];
                    
                    // Redirect based on user type
                    switch ($user['user_type']) {
                        case 'admin':
                            header('Location: admin/dashboard.php');
                            break;
                        case 'owner':
                            header('Location: owner/dashboard.php');
                            break;
                        case 'customer':
                            header('Location: /rental_system/');
                            break;
                        default:
                            header('Location: /rental_system/');
                            break;
                    }
                    exit();
                } else {
                    $error = 'Account status not recognized. Please contact support.';
                }
            } else {
                $error = 'User not found.';
            }
        } else {
            $error = 'Invalid OTP. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Property Rental System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Login</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" data-validate="true">
                <?php if (($step ?? 'phone') === 'phone'): ?>
                    <!-- Step 1: Phone Number -->
                    <div class="form-group">
                        <label for="phone" class="form-label">Mobile Number</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="0771234567 (Sri Lankan mobile number)"
                               pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$"
                               title="Enter a valid Sri Lankan mobile number (e.g., 0771234567)"
                               value="<?= htmlspecialchars($phone ?? '') ?>"
                               maxlength="10"
                               required>
                        <small class="form-text">Format: 07XXXXXXXX</small>
                    </div>
                    
                    <input type="hidden" name="step" value="phone">
                    <button type="submit" class="btn btn-primary btn-full">Send OTP</button>
                    
                <?php else: ?>
                    <!-- Step 2: OTP Verification -->
                    <div class="form-group">
                        <label for="otp_code" class="form-label">Enter OTP</label>
                        <input type="text" 
                               id="otp_code" 
                               name="otp_code" 
                               class="form-control" 
                               placeholder="Enter 6-digit OTP" 
                               maxlength="6"
                               pattern="\d{6}"
                               required>
                        <small>OTP sent to <?= htmlspecialchars($phone) ?></small>
                    </div>
                    
                    <input type="hidden" name="phone" value="<?= htmlspecialchars($phone) ?>">
                    <input type="hidden" name="step" value="otp">
                    
                    <button type="submit" class="btn btn-primary btn-full">Verify OTP</button>
                    
                    <div class="auth-link">
                        <a href="login.php">← Back to phone number</a>
                    </div>
                <?php endif; ?>
            </form>
            
            <div class="auth-link">
                Don't have an account? <a href="register.php">Register here</a>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>