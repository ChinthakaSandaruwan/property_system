<?php
session_start();
require_once '../includes/config.php';
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

// Get property details
$stmt = $pdo->prepare("SELECT p.*, u.full_name as owner_name, u.phone as owner_phone 
                       FROM properties p 
                       JOIN users u ON p.owner_id = u.id 
                       WHERE p.id = ? AND p.status = 'approved' AND p.is_available = 1");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: ../index.php');
    exit();
}

$message = '';
$error = '';

// Handle visit booking
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] == 'book_visit') {
    $requested_date = $_POST['visit_date'];
    $requested_time = $_POST['visit_time'];
    $customer_notes = $_POST['customer_notes'] ?? '';
    
    // Combine date and time
    $requested_datetime = $requested_date . ' ' . $requested_time;
    
    // Validate future date
    if (strtotime($requested_datetime) <= time()) {
        $error = "Please select a future date and time.";
    } else {
        // Check if customer already has pending visit for this property
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits 
                               WHERE property_id = ? AND customer_id = ? AND status = 'pending'");
        $stmt->execute([$property_id, $_SESSION['user_id']]);
        $existingVisit = $stmt->fetchColumn();
        
        if ($existingVisit > 0) {
            $error = "You already have a pending visit request for this property.";
        } else {
            // Insert visit request
            $stmt = $pdo->prepare("INSERT INTO property_visits (property_id, customer_id, owner_id, requested_date, status, customer_notes) 
                                   VALUES (?, ?, ?, ?, 'pending', ?)");
            
            if ($stmt->execute([$property_id, $_SESSION['user_id'], $property['owner_id'], $requested_datetime, $customer_notes])) {
                $message = "Visit request submitted successfully! The property owner will review and respond to your request.";
                
                // TODO: Send notification to property owner (SMS/Email)
                
            } else {
                $error = "Failed to submit visit request. Please try again.";
            }
        }
    }
}

// Get customer's visit history for this property
$stmt = $pdo->prepare("SELECT * FROM property_visits 
                       WHERE property_id = ? AND customer_id = ? 
                       ORDER BY created_at DESC");
$stmt->execute([$property_id, $_SESSION['user_id']]);
$visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Book Property Visit - <?= htmlspecialchars($site_name) ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            margin: 0;
            padding: 20px;
            background-color: #f4f4f4;
        }
        .container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        .property-info {
            background: #e8f5e8;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        input, textarea, select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            box-sizing: border-box;
        }
        textarea {
            height: 80px;
            resize: vertical;
        }
        .btn {
            background: #007bff;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .btn:hover {
            background: #0056b3;
        }
        .btn-secondary {
            background: #6c757d;
            margin-left: 10px;
        }
        .btn-secondary:hover {
            background: #545b62;
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
        }
        .success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .visit-history {
            margin-top: 30px;
        }
        .visit-item {
            background: #f8f9fa;
            padding: 15px;
            margin-bottom: 10px;
            border-radius: 5px;
            border-left: 4px solid #007bff;
        }
        .visit-status {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .status-pending { background: #fff3cd; color: #856404; }
        .status-approved { background: #d4edda; color: #155724; }
        .status-rejected { background: #f8d7da; color: #721c24; }
        .status-completed { background: #d1ecf1; color: #0c5460; }
        .visit-date {
            font-weight: bold;
            color: #007bff;
        }
        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #007bff;
            text-decoration: none;
        }
        .back-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="../property_details.php?id=<?php echo $property_id; ?>" class="back-link">← Back to Property Details</a>
        
        <h1>Book Property Visit</h1>
        
        <div class="property-info">
            <h3><?php echo htmlspecialchars($property['title']); ?></h3>
            <p><strong>Property Type:</strong> <?php echo ucfirst($property['property_type']); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($property['address'] . ', ' . $property['city']); ?></p>
            <p><strong>Rent:</strong> Rs. <?php echo number_format($property['rent_amount'], 2); ?> per month</p>
            <p><strong>Owner:</strong> <?php echo htmlspecialchars($property['owner_name']); ?></p>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo $message; ?></div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="message error"><?php echo $error; ?></div>
        <?php endif; ?>

        <?php
        // Check if customer can book a new visit (no pending requests)
        $canBookVisit = true;
        foreach ($visits as $visit) {
            if ($visit['status'] == 'pending') {
                $canBookVisit = false;
                break;
            }
        }
        ?>

        <?php if ($canBookVisit): ?>
        <form method="POST" action="">
            <input type="hidden" name="action" value="book_visit">
            
            <div class="form-group">
                <label for="visit_date">Preferred Visit Date:</label>
                <input type="date" id="visit_date" name="visit_date" 
                       min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" 
                       max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>" required>
            </div>
            
            <div class="form-group">
                <label for="visit_time">Preferred Time:</label>
                <select id="visit_time" name="visit_time" required>
                    <option value="">Select Time</option>
                    <option value="09:00">9:00 AM</option>
                    <option value="10:00">10:00 AM</option>
                    <option value="11:00">11:00 AM</option>
                    <option value="14:00">2:00 PM</option>
                    <option value="15:00">3:00 PM</option>
                    <option value="16:00">4:00 PM</option>
                    <option value="17:00">5:00 PM</option>
                    <option value="18:00">6:00 PM</option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="customer_notes">Additional Notes (Optional):</label>
                <textarea id="customer_notes" name="customer_notes" 
                          placeholder="Any specific requirements or questions about the property..."></textarea>
            </div>
            
            <button type="submit" class="btn">Submit Visit Request</button>
            <a href="../property_details.php?id=<?php echo $property_id; ?>" class="btn btn-secondary">Cancel</a>
        </form>
        <?php else: ?>
            <div class="message error">
                You already have a pending visit request for this property. Please wait for the owner's response before booking another visit.
            </div>
        <?php endif; ?>

        <?php if (!empty($visits)): ?>
        <div class="visit-history">
            <h3>Your Visit History</h3>
            <?php foreach ($visits as $visit): ?>
            <div class="visit-item">
                <div class="visit-date"><?php echo date('M j, Y g:i A', strtotime($visit['requested_date'])); ?></div>
                <span class="visit-status status-<?php echo $visit['status']; ?>">
                    <?php echo ucfirst($visit['status']); ?>
                </span>
                <?php if ($visit['customer_notes']): ?>
                    <p><strong>Your Notes:</strong> <?php echo htmlspecialchars($visit['customer_notes']); ?></p>
                <?php endif; ?>
                <?php if ($visit['owner_notes']): ?>
                    <p><strong>Owner's Response:</strong> <?php echo htmlspecialchars($visit['owner_notes']); ?></p>
                <?php endif; ?>
                <small>Requested on: <?php echo date('M j, Y g:i A', strtotime($visit['created_at'])); ?></small>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <script>
        // Set minimum date to tomorrow
        document.getElementById('visit_date').min = new Date(Date.now() + 86400000).toISOString().split('T')[0];
    </script>
</body>
</html>