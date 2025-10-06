<?php
require_once '../includes/functions.php';
require_auth('customer');

$property_id = intval($_GET['id'] ?? 0);
if (!$property_id) {
    header('Location: dashboard.php');
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];

// Handle visit booking
if ($_POST && isset($_POST['book_visit'])) {
    $visit_date = sanitize_input($_POST['visit_date'] ?? '');
    $visit_time = sanitize_input($_POST['visit_time'] ?? '');
    $notes = sanitize_input($_POST['notes'] ?? '');
    
    if (empty($visit_date) || empty($visit_time)) {
        $error = 'Please select date and time for your visit.';
    } else if (strtotime($visit_date) < strtotime('today')) {
        $error = 'Visit date cannot be in the past.';
    } else {
        // Check if user already has a pending/confirmed visit for this property
        $check_stmt = $pdo->prepare("SELECT id FROM property_visits WHERE property_id = ? AND customer_id = ? AND status IN ('requested', 'confirmed')");
        $check_stmt->execute([$property_id, $user_id]);
        
        if ($check_stmt->fetch()) {
            $error = 'You already have a pending or confirmed visit for this property.';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO property_visits (property_id, customer_id, visit_date, visit_time, notes) VALUES (?, ?, ?, ?, ?)");
                if ($stmt->execute([$property_id, $user_id, $visit_date, $visit_time, $notes])) {
                    $success = 'Visit request submitted successfully! The property owner will confirm your visit shortly.';
                } else {
                    $error = 'Failed to book visit. Please try again.';
                }
            } catch (Exception $e) {
                $error = 'An error occurred while booking the visit.';
            }
        }
    }
}

// Get property details
$stmt = $pdo->prepare("
    SELECT p.*, u.full_name as owner_name, u.phone as owner_phone 
    FROM properties p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.id = ? AND p.status = 'approved'
");
$stmt->execute([$property_id]);
$property = $stmt->fetch();

if (!$property) {
    header('Location: dashboard.php');
    exit();
}

// Check if user already has pending visit
$existing_visit_stmt = $pdo->prepare("SELECT * FROM property_visits WHERE property_id = ? AND customer_id = ? AND status IN ('requested', 'confirmed') ORDER BY created_at DESC LIMIT 1");
$existing_visit_stmt->execute([$property_id, $user_id]);
$existing_visit = $existing_visit_stmt->fetch();

// Parse amenities and images
$amenities = json_decode($property['amenities'] ?? '[]', true);
$images = json_decode($property['images'] ?? '[]', true);

// Amenity labels
$amenity_labels = [
    'parking' => 'Parking Available',
    'elevator' => 'Elevator',
    'balcony' => 'Balcony',
    'garden' => 'Garden/Yard',
    'wifi' => 'WiFi Included',
    'cable_tv' => 'Cable TV',
    'utilities_included' => 'Utilities Included',
    'furnished' => 'Fully Furnished',
    'air_conditioning' => 'Air Conditioning',
    'heating' => 'Heating',
    'washer_dryer' => 'Washer/Dryer',
    'dishwasher' => 'Dishwasher'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> - Property Details</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .image-gallery {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            grid-template-rows: 1fr 1fr;
            gap: 10px;
            height: 400px;
            margin-bottom: 2rem;
        }
        
        .image-gallery img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .image-gallery img:first-child {
            grid-row: 1 / 3;
        }
        
        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 0.5rem;
            margin-top: 1rem;
        }
        
        .amenity-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .amenity-item::before {
            content: "✓";
            color: #28a745;
            font-weight: bold;
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
            <a href="dashboard.php" class="btn btn-secondary">← Back to Properties</a>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-error"><?= $error ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?= $success ?></div>
        <?php endif; ?>

        <div class="row">
            <!-- Property Details -->
            <div class="col-2" style="flex: 2;">
                <div class="card">
                    <div class="card-body">
                        <!-- Property Images -->
                        <?php if (!empty($images)): ?>
                            <div class="image-gallery">
                                <?php foreach (array_slice($images, 0, 5) as $index => $image): ?>
                                    <img src="../uploads/properties/<?= htmlspecialchars($image) ?>" 
                                         alt="Property Image <?= $index + 1 ?>"
                                         onerror="this.src='../images/placeholder.jpg'">
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <!-- Property Title and Price -->
                        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 2rem;">
                            <div>
                                <h1 style="margin-bottom: 0.5rem;"><?= htmlspecialchars($property['title']) ?></h1>
                                <p style="color: #666; margin-bottom: 1rem;">
                                    📍 <?= htmlspecialchars($property['address']) ?><br>
                                    <?= htmlspecialchars($property['city'] . ', ' . $property['state'] . ' ' . $property['zipcode']) ?>
                                </p>
                            </div>
                            <div style="text-align: right;">
                                <div class="property-price" style="font-size: 2rem; margin-bottom: 0.5rem;">
                                    <?= format_currency($property['rent_amount']) ?>/month
                                </div>
                                <?php if ($property['security_deposit']): ?>
                                    <p style="color: #666;">Security deposit: <?= format_currency($property['security_deposit']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Property Details -->
                        <div class="row" style="margin-bottom: 2rem;">
                            <div class="col-4">
                                <div class="stat-card">
                                    <div class="stat-number"><?= $property['bedrooms'] ?></div>
                                    <div class="stat-label">Bedrooms</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-card">
                                    <div class="stat-number"><?= $property['bathrooms'] ?></div>
                                    <div class="stat-label">Bathrooms</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-card">
                                    <div class="stat-number"><?= number_format($property['area_sqft']) ?></div>
                                    <div class="stat-label">Square Feet</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="stat-card">
                                    <div class="stat-number"><?= ucfirst($property['property_type']) ?></div>
                                    <div class="stat-label">Property Type</div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <?php if ($property['description']): ?>
                            <div style="margin-bottom: 2rem;">
                                <h3>Description</h3>
                                <p style="line-height: 1.6; color: #666;">
                                    <?= nl2br(htmlspecialchars($property['description'])) ?>
                                </p>
                            </div>
                        <?php endif; ?>

                        <!-- Amenities -->
                        <?php if (!empty($amenities)): ?>
                            <div style="margin-bottom: 2rem;">
                                <h3>Amenities</h3>
                                <div class="amenities-grid">
                                    <?php foreach ($amenities as $amenity): ?>
                                        <div class="amenity-item">
                                            <?= $amenity_labels[$amenity] ?? ucfirst(str_replace('_', ' ', $amenity)) ?>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Owner Contact -->
                        <div>
                            <h3>Property Owner</h3>
                            <p>
                                <strong>Name:</strong> <?= htmlspecialchars($property['owner_name']) ?><br>
                                <strong>Phone:</strong> <?= htmlspecialchars($property['owner_phone']) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visit Booking Sidebar -->
            <div class="col-2" style="flex: 1;">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Schedule a Visit</h3>
                    </div>
                    <div class="card-body">
                        <?php if ($existing_visit): ?>
                            <div class="alert alert-info">
                                <strong>Existing Visit Request</strong><br>
                                Date: <?= date('M j, Y', strtotime($existing_visit['visit_date'])) ?><br>
                                Time: <?= date('g:i A', strtotime($existing_visit['visit_time'])) ?><br>
                                Status: <span class="badge badge-<?= $existing_visit['status'] === 'confirmed' ? 'success' : 'warning' ?>">
                                    <?= ucfirst($existing_visit['status']) ?>
                                </span>
                            </div>
                        <?php else: ?>
                            <form method="POST" data-validate="true">
                                <div class="form-group">
                                    <label for="visit_date" class="form-label">Preferred Date *</label>
                                    <input type="date" 
                                           id="visit_date" 
                                           name="visit_date" 
                                           class="form-control" 
                                           min="<?= date('Y-m-d', strtotime('tomorrow')) ?>"
                                           required>
                                </div>

                                <div class="form-group">
                                    <label for="visit_time" class="form-label">Preferred Time *</label>
                                    <select id="visit_time" name="visit_time" class="form-select" required>
                                        <option value="">Select time</option>
                                        <option value="09:00">9:00 AM</option>
                                        <option value="10:00">10:00 AM</option>
                                        <option value="11:00">11:00 AM</option>
                                        <option value="12:00">12:00 PM</option>
                                        <option value="13:00">1:00 PM</option>
                                        <option value="14:00">2:00 PM</option>
                                        <option value="15:00">3:00 PM</option>
                                        <option value="16:00">4:00 PM</option>
                                        <option value="17:00">5:00 PM</option>
                                        <option value="18:00">6:00 PM</option>
                                    </select>
                                </div>

                                <div class="form-group">
                                    <label for="notes" class="form-label">Additional Notes</label>
                                    <textarea id="notes" 
                                              name="notes" 
                                              class="form-control" 
                                              rows="3"
                                              placeholder="Any special requirements or questions..."></textarea>
                                </div>

                                <a href="book_visit.php?property_id=<?= $property['id'] ?>" class="btn btn-primary btn-full">
                                    Request Visit
                                </a>
                            </form>
                        <?php endif; ?>

                        <hr style="margin: 2rem 0;">

                        <div style="text-align: center;">
                            <p style="color: #666; font-size: 0.9rem; margin-bottom: 1rem;">
                                Interested in renting this property?
                            </p>
                            
                            <?php if ($existing_visit && $existing_visit['status'] === 'confirmed'): ?>
                                <a href="start_rental.php?property_id=<?= $property['id'] ?>" class="btn btn-success btn-full">
                                    Start Rental Process
                                </a>
                            <?php else: ?>
                                <button type="button" class="btn btn-secondary btn-full" disabled>
                                    Visit Required First
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Property Stats -->
                <div class="card" style="margin-top: 1rem;">
                    <div class="card-body">
                        <h4>Property Information</h4>
                        <div style="display: flex; flex-direction: column; gap: 0.5rem; margin-top: 1rem;">
                            <div style="display: flex; justify-content: space-between;">
                                <span>Property ID:</span>
                                <strong>#<?= $property['id'] ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Listed:</span>
                                <strong><?= date('M j, Y', strtotime($property['created_at'])) ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between;">
                                <span>Status:</span>
                                <span class="badge badge-success">Available</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 Property Rental System. All rights reserved.</p>
        </div>
    </footer>

    <script src="../js/main.js"></script>
</body>
</html>