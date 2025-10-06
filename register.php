<?php
require_once 'includes/functions.php';

$error = '';
$success = '';

if ($_POST) {
    $step = $_POST['step'] ?? 'details';
    
    if ($step === 'details') {
        // Step 1: User details
        $full_name = sanitize_input($_POST['full_name'] ?? '');
        $phone = sanitize_input($_POST['phone'] ?? '');
        $email = sanitize_input($_POST['email'] ?? '');
        $user_type = sanitize_input($_POST['user_type'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        
        // Validation
        if (empty($full_name) || empty($phone) || empty($email) || empty($user_type) || empty($password)) {
            $error = 'All fields are required.';
                        } else if (!validate_phone($phone)) {
                            $error = 'Please enter a valid Sri Lankan mobile number (e.g., 0771234567).';
        } else if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else if (!in_array($user_type, ['customer', 'owner'])) {
            $error = 'Invalid user type.';
        } else if (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters long.';
        } else if ($password !== $confirm_password) {
            $error = 'Passwords do not match.';
        } else {
            // Check if user already exists
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? OR email = ?");
            $stmt->execute([$phone, $email]);
            
            if ($stmt->fetch()) {
                $error = 'User with this phone number or email already exists.';
            } else {
                // Send OTP for verification
                $otp_code = generate_otp();
                if (store_otp($phone, $otp_code)) {
                    send_sms($phone, "Your registration OTP is: $otp_code");
                    
                    // Development mode: show the actual OTP on screen
                    $dev_otp = get_dev_otp($phone);
                    if ($dev_otp) {
                        $success = '<strong>📱 OTP sent to your phone: ' . $phone . '</strong><br>';
                        $success .= '<div style="background: #f0fff4; padding: 15px; border-radius: 5px; margin: 10px 0; border-left: 4px solid #38a169;">';
                        $success .= '<strong>🔐 Your OTP Code: <span style="font-family: monospace; font-size: 20px; color: #38a169; background: white; padding: 5px 10px; border-radius: 3px;">' . $dev_otp . '</span></strong><br>';
                        $success .= '<small>⏰ This code will expire in 5 minutes</small>';
                        $success .= '</div>';
                        $success .= '<p><strong>Alternative codes:</strong> You can also use <code>123456</code> or <code>000000</code></p>';
                    } else {
                        $success = 'OTP sent to your phone number for verification. Use <code>123456</code> or <code>000000</code> if you don\'t receive it.';
                    }
                    
                    $step = 'otp';
                    
                    // Store user data in session temporarily
                    $_SESSION['temp_user'] = [
                        'full_name' => $full_name,
                        'phone' => $phone,
                        'email' => $email,
                        'user_type' => $user_type,
                        'password' => password_hash($password, PASSWORD_DEFAULT)
                    ];
                } else {
                    $error = 'Failed to send OTP. Please try again.';
                }
            }
        }
    } else if ($step === 'otp') {
        // Step 2: OTP verification
        $otp_code = sanitize_input($_POST['otp_code'] ?? '');
        
        if (empty($otp_code) || !preg_match('/^\d{6}$/', $otp_code)) {
            $error = 'Please enter a valid 6-digit OTP.';
        } else if (!isset($_SESSION['temp_user'])) {
            $error = 'Session expired. Please start registration again.';
        } else {
            $temp_user = $_SESSION['temp_user'];
            
            if (verify_otp($temp_user['phone'], $otp_code)) {
                // Create user account
                try {
                    // Set status based on user type
                    $status = ($temp_user['user_type'] === 'owner') ? 'pending' : 'active';
                    
                    $stmt = $pdo->prepare("INSERT INTO users (full_name, phone, email, user_type, password_hash, is_phone_verified, status) VALUES (?, ?, ?, ?, ?, TRUE, ?)");
                    
                    if ($stmt->execute([
                        $temp_user['full_name'],
                        $temp_user['phone'],
                        $temp_user['email'],
                        $temp_user['user_type'],
                        $temp_user['password'],
                        $status
                    ])) {
                        unset($_SESSION['temp_user']);
                        
                        if ($temp_user['user_type'] === 'owner') {
                            $success = '<div style="background: #fff3cd; padding: 20px; border-radius: 8px; border: 1px solid #ffeaa7; margin: 15px 0;">';
                            $success .= '<h4 style="color: #856404; margin-bottom: 10px;">🎉 Registration Successful!</h4>';
                            $success .= '<p style="color: #856404; margin-bottom: 10px;">Your property owner account has been created successfully.</p>';
                            $success .= '<p style="color: #856404; margin-bottom: 10px;"><strong>⏳ Account Status:</strong> Pending Admin Approval</p>';
                            $success .= '<p style="color: #856404; margin-bottom: 0;">You will be able to login once an administrator approves your account. You may receive an email notification when this happens.</p>';
                            $success .= '</div>';
                            $success .= '<p><a href="login.php" style="color: #38a169; text-decoration: none; font-weight: bold;">← Return to Login</a></p>';
                        } else {
                            $success = 'Registration successful! You can now login.';
                            
                            // Auto-login customers
                            $user_id = $pdo->lastInsertId();
                            $_SESSION['user_id'] = $user_id;
                            $_SESSION['user_type'] = $temp_user['user_type'];
                            $_SESSION['user_name'] = $temp_user['full_name'];
                            
                            // Redirect customers to dashboard
                            header('Location: /rental_system/');
                            exit();
                        }
                    } else {
                        $error = 'Registration failed. Please try again.';
                    }
                } catch (Exception $e) {
                    $error = 'Registration failed. Please try again.';
                }
            } else {
                $error = 'Invalid OTP. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Property Rental System</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="auth-container">
        <div class="auth-card">
            <h2 class="auth-title">Register</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?= $error ?></div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success"><?= $success ?></div>
            <?php endif; ?>
            
            <form method="POST" data-validate="true">
                <?php if (($step ?? 'details') === 'details'): ?>
                    <!-- Step 1: User Details -->
                    <div class="form-group">
                        <label for="full_name" class="form-label">Full Name</label>
                        <input type="text" 
                               id="full_name" 
                               name="full_name" 
                               class="form-control" 
                               placeholder="Enter your full name"
                               value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="phone" class="form-label">Mobile Number</label>
                        <input type="tel" 
                               id="phone" 
                               name="phone" 
                               class="form-control" 
                               placeholder="0771234567 (Sri Lankan mobile number)"
                               pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$"
                               title="Enter a valid Sri Lankan mobile number (e.g., 0771234567)"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               maxlength="10"
                               required>
                        <small class="form-text">Format: 07XXXXXXXX (Mobitel, Dialog, Hutch, Airtel)</small>
                    </div>
                    
                    <div class="form-group">
                        <label for="email" class="form-label">Email Address</label>
                        <input type="email" 
                               id="email" 
                               name="email" 
                               class="form-control" 
                               placeholder="Enter your email address"
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="user_type" class="form-label">I am a</label>
                        <select id="user_type" name="user_type" class="form-select" required>
                            <option value="">Select user type</option>
                            <option value="customer" <?= ($_POST['user_type'] ?? '') === 'customer' ? 'selected' : '' ?>>Customer (Looking for property)</option>
                            <option value="owner" <?= ($_POST['user_type'] ?? '') === 'owner' ? 'selected' : '' ?>>Property Owner (Have property to rent)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" 
                               id="password" 
                               name="password" 
                               class="form-control" 
                               placeholder="Create a password (min 6 characters)"
                               required>
                    </div>
                    
                    <div class="form-group">
                        <label for="confirm_password" class="form-label">Confirm Password</label>
                        <input type="password" 
                               id="confirm_password" 
                               name="confirm_password" 
                               class="form-control" 
                               placeholder="Confirm your password"
                               required>
                    </div>
                    
                    <input type="hidden" name="step" value="details">
                    <button type="submit" class="btn btn-primary btn-full">Register & Send OTP</button>
                    
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
                        <small>OTP sent to <?= htmlspecialchars($_SESSION['temp_user']['phone'] ?? '') ?></small>
                    </div>
                    
                    <input type="hidden" name="step" value="otp">
                    
                    <button type="submit" class="btn btn-primary btn-full">Verify & Complete Registration</button>
                    
                    <div class="auth-link">
                        <a href="register.php">← Back to registration form</a>
                    </div>
                <?php endif; ?>
            </form>
            
            <div class="auth-link">
                Already have an account? <a href="login.php">Login here</a>
            </div>
        </div>
    </div>
    
    <script src="js/main.js"></script>
</body>
</html>