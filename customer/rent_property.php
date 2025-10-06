<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/functions.php';

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

// Check if customer already has an active rental application for this property
$existing_application = null;
try {
    $stmt = $pdo->prepare("SELECT * FROM rental_applications WHERE property_id = ? AND customer_id = ? AND status IN ('pending', 'approved')");
    $stmt->execute([$property_id, $customer_id]);
    $existing_application = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Table doesn't exist yet, will be created when first application is submitted
    $existing_application = null;
}

// Handle rental application submission
if ($_POST && isset($_POST['action']) && $_POST['action'] === 'submit_application') {
    if ($existing_application) {
        $error = "You already have an active application for this property.";
    } else {
        try {
            // Get form data
            $employment_status = $_POST['employment_status'] ?? '';
            $monthly_income = floatval($_POST['monthly_income'] ?? 0);
            $employer_name = $_POST['employer_name'] ?? '';
            $employer_contact = $_POST['employer_contact'] ?? '';
            $previous_address = $_POST['previous_address'] ?? '';
            $reference_name = $_POST['reference_name'] ?? '';
            $reference_contact = $_POST['reference_contact'] ?? '';
            $additional_notes = $_POST['additional_notes'] ?? '';
            $move_in_date = $_POST['move_in_date'] ?? '';
            
            // Basic validation
            if (empty($employment_status) || empty($monthly_income) || empty($move_in_date)) {
                $error = "Please fill in all required fields.";
            } else if ($monthly_income < ($property['rent_amount'] * 2)) {
                $error = "Your monthly income should be at least 2 times the rent amount (Rs. " . number_format($property['rent_amount'] * 2, 2) . ").";
            } else {
                // Create rental application table if it doesn't exist
                $create_table_sql = "
                CREATE TABLE IF NOT EXISTS rental_applications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    property_id INT NOT NULL,
                    customer_id INT NOT NULL,
                    owner_id INT NOT NULL,
                    employment_status VARCHAR(100) NOT NULL,
                    monthly_income DECIMAL(10,2) NOT NULL,
                    employer_name VARCHAR(255),
                    employer_contact VARCHAR(20),
                    previous_address TEXT,
                    reference_name VARCHAR(255),
                    reference_contact VARCHAR(20),
                    additional_notes TEXT,
                    move_in_date DATE NOT NULL,
                    status ENUM('pending', 'approved', 'rejected', 'cancelled') DEFAULT 'pending',
                    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    owner_notes TEXT,
                    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
                    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE
                )";
                $pdo->exec($create_table_sql);
                
                // Insert rental application
                $stmt = $pdo->prepare("
                    INSERT INTO rental_applications 
                    (property_id, customer_id, owner_id, employment_status, monthly_income, employer_name, 
                     employer_contact, previous_address, reference_name, reference_contact, additional_notes, move_in_date) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                
                $result = $stmt->execute([
                    $property_id, $customer_id, $property['owner_id'], $employment_status, $monthly_income,
                    $employer_name, $employer_contact, $previous_address, $reference_name, 
                    $reference_contact, $additional_notes, $move_in_date
                ]);
                
                if ($result) {
                    $message = "Your rental application has been submitted successfully! The property owner will review your application and contact you soon.";
                    
                    // Refresh to show the submitted application
                    try {
                        $stmt = $pdo->prepare("SELECT * FROM rental_applications WHERE property_id = ? AND customer_id = ? AND status IN ('pending', 'approved')");
                        $stmt->execute([$property_id, $customer_id]);
                        $existing_application = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (PDOException $e) {
                        $existing_application = null;
                    }
                } else {
                    $error = "Failed to submit rental application. Please try again.";
                }
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Get customer profile for pre-filling form
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$customer_id]);
$customer = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rent Property - SmartRent</title>
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

        <?php if ($existing_application): ?>
            <!-- Show existing application status -->
            <div class="application-status">
                <div class="status-icon status-<?= $existing_application['status'] ?>">
                    <?php
                    $icons = [
                        'pending' => '⏳',
                        'approved' => '✅',
                        'rejected' => '❌',
                        'cancelled' => '🚫'
                    ];
                    echo $icons[$existing_application['status']] ?? '📄';
                    ?>
                </div>
                <h2 class="status-title status-<?= $existing_application['status'] ?>">
                    Application <?= ucfirst($existing_application['status']) ?>
                </h2>
                <p>
                    <?php
                    switch ($existing_application['status']) {
                        case 'pending':
                            echo "Your rental application is being reviewed by the property owner. You will be contacted soon.";
                            break;
                        case 'approved':
                            echo "Congratulations! Your rental application has been approved. The property owner will contact you to finalize the rental agreement.";
                            break;
                        case 'rejected':
                            echo "We're sorry, but your rental application was not approved this time.";
                            break;
                        case 'cancelled':
                            echo "Your rental application has been cancelled.";
                            break;
                    }
                    ?>
                </p>

                <div class="application-details">
                    <h3>Application Details</h3>
                    <div class="detail-grid">
                        <span class="detail-label">Application Date:</span>
                        <span><?= date('M j, Y g:i A', strtotime($existing_application['application_date'])) ?></span>
                    </div>
                    <div class="detail-grid">
                        <span class="detail-label">Employment Status:</span>
                        <span><?= htmlspecialchars($existing_application['employment_status']) ?></span>
                    </div>
                    <div class="detail-grid">
                        <span class="detail-label">Monthly Income:</span>
                        <span>Rs. <?= number_format($existing_application['monthly_income'], 2) ?></span>
                    </div>
                    <div class="detail-grid">
                        <span class="detail-label">Preferred Move-in Date:</span>
                        <span><?= date('M j, Y', strtotime($existing_application['move_in_date'])) ?></span>
                    </div>
                    <?php if ($existing_application['owner_notes']): ?>
                        <div class="detail-grid">
                            <span class="detail-label">Owner's Notes:</span>
                            <span><?= nl2br(htmlspecialchars($existing_application['owner_notes'])) ?></span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <!-- Show rental application form -->
            <div class="income-requirement">
                <div class="requirement-text">
                    💡 Income Requirement: Your monthly income should be at least Rs. <?= number_format($property['rent_amount'] * 2, 2) ?> 
                    (2 times the monthly rent) to qualify for this property.
                </div>
            </div>

            <div class="rental-form">
                <form method="POST">
                    <input type="hidden" name="action" value="submit_application">

                    <!-- Personal & Employment Information -->
                    <div class="form-section">
                        <h3 class="section-title">Employment Information</h3>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="employment_status" class="form-label">Employment Status <span class="required">*</span></label>
                                <select name="employment_status" id="employment_status" class="form-select" required>
                                    <option value="">Select Status</option>
                                    <option value="employed_fulltime">Full-time Employee</option>
                                    <option value="employed_parttime">Part-time Employee</option>
                                    <option value="self_employed">Self-employed</option>
                                    <option value="student">Student</option>
                                    <option value="retired">Retired</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="monthly_income" class="form-label">Monthly Income (Rs.) <span class="required">*</span></label>
                                <input type="number" 
                                       name="monthly_income" 
                                       id="monthly_income" 
                                       class="form-control" 
                                       placeholder="e.g., 75000"
                                       min="<?= $property['rent_amount'] * 2 ?>"
                                       required>
                                <small style="color: #666; font-size: 12px;">
                                    Minimum required: Rs. <?= number_format($property['rent_amount'] * 2, 2) ?>
                                </small>
                            </div>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="employer_name" class="form-label">Employer/Company Name</label>
                                <input type="text" 
                                       name="employer_name" 
                                       id="employer_name" 
                                       class="form-control" 
                                       placeholder="Company or organization name">
                            </div>
                            
                            <div class="form-group">
                                <label for="employer_contact" class="form-label">Employer Contact</label>
                                <input type="text" 
                                       name="employer_contact" 
                                       id="employer_contact" 
                                       class="form-control" 
                                       placeholder="Phone number or email">
                            </div>
                        </div>
                    </div>

                    <!-- Previous Address & References -->
                    <div class="form-section">
                        <h3 class="section-title">Background Information</h3>
                        <div class="form-group">
                            <label for="previous_address" class="form-label">Previous Address</label>
                            <textarea name="previous_address" 
                                      id="previous_address" 
                                      class="form-control textarea" 
                                      placeholder="Your current or most recent address"></textarea>
                        </div>
                        
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="reference_name" class="form-label">Reference Name</label>
                                <input type="text" 
                                       name="reference_name" 
                                       id="reference_name" 
                                       class="form-control" 
                                       placeholder="Name of a professional or personal reference">
                            </div>
                            
                            <div class="form-group">
                                <label for="reference_contact" class="form-label">Reference Contact</label>
                                <input type="text" 
                                       name="reference_contact" 
                                       id="reference_contact" 
                                       class="form-control" 
                                       placeholder="Phone number or email of reference">
                            </div>
                        </div>
                    </div>

                    <!-- Move-in Details -->
                    <div class="form-section">
                        <h3 class="section-title">Rental Details</h3>
                        <div class="form-group">
                            <label for="move_in_date" class="form-label">Preferred Move-in Date <span class="required">*</span></label>
                            <input type="date" 
                                   name="move_in_date" 
                                   id="move_in_date" 
                                   class="form-control" 
                                   min="<?= date('Y-m-d') ?>"
                                   required>
                        </div>
                        
                        <div class="form-group">
                            <label for="additional_notes" class="form-label">Additional Notes</label>
                            <textarea name="additional_notes" 
                                      id="additional_notes" 
                                      class="form-control textarea" 
                                      placeholder="Any additional information you'd like to share with the property owner"></textarea>
                        </div>
                    </div>

                    <div style="text-align: center; margin-top: 30px;">
                        <a href="../property_details.php?id=<?= $property_id ?>" class="btn btn-secondary">Cancel</a>
                        <button type="submit" class="btn btn-success">Submit Rental Application</button>
                    </div>
                </form>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 SmartRent Property Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Set minimum date to tomorrow
        document.getElementById('move_in_date').min = new Date(Date.now() + 86400000).toISOString().split('T')[0];
    </script>
</body>
</html>