<?php
session_start();
require_once 'includes/config.php';
require_once 'includes/functions.php';
require_once 'includes/settings_helper.php';

// Get site name
$site_name = getSiteName();

$property_id = $_GET['id'] ?? null;
if (!$property_id) {
    header('Location: index.php');
    exit();
}

// Get property details with owner information
$stmt = $pdo->prepare("SELECT p.*, u.full_name as owner_name, u.email as owner_email, u.phone as owner_phone 
                       FROM properties p 
                       JOIN users u ON p.owner_id = u.id 
                       WHERE p.id = ? AND p.status = 'approved'");
$stmt->execute([$property_id]);
$property = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$property) {
    header('Location: index.php');
    exit();
}

// Get property images from the properties table (images stored as JSON)
$images = [];
if (!empty($property['images'])) {
    // Try to decode JSON
    $decoded_images = json_decode($property['images'], true);
    if ($decoded_images && is_array($decoded_images)) {
        // Convert to the expected format with image_path
        $images = [];
        foreach ($decoded_images as $image_filename) {
            $images[] = [
                'image_path' => 'uploads/properties/' . $image_filename,
                'filename' => $image_filename
            ];
        }
    }
}

// Check if user is logged in and is a customer
$is_customer = isset($_SESSION['user_id']) && $_SESSION['user_type'] === 'customer';

// If customer, check if they have any visits for this property
$customer_visits = [];
$is_in_wishlist = false;
if ($is_customer) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM property_visits 
                               WHERE property_id = ? AND customer_id = ? 
                               ORDER BY created_at DESC");
        $stmt->execute([$property_id, $_SESSION['user_id']]);
        $customer_visits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // property_visits table doesn't exist yet, use empty array
        $customer_visits = [];
    }
    
    // Check if property is in customer's wishlist
    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ? AND property_id = ?");
        $stmt->execute([$_SESSION['user_id'], $property_id]);
        $is_in_wishlist = $stmt->fetchColumn() > 0;
    } catch (PDOException $e) {
        // wishlists table doesn't exist yet
        $is_in_wishlist = false;
    }
}

// Parse amenities if stored as JSON or comma-separated
$amenities = [];
if (isset($property['amenities']) && $property['amenities']) {
    // Try to decode as JSON first
    $decoded = json_decode($property['amenities'], true);
    if ($decoded) {
        $amenities = $decoded;
    } else {
        // Fallback to comma-separated
        $amenities = array_map('trim', explode(',', $property['amenities']));
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($property['title']) ?> - <?= htmlspecialchars($site_name) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <style>
        .property-hero {
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }

        .property-images {
            position: relative;
            height: 400px;
            overflow: hidden;
        }

        .property-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: none;
        }

        .property-image.active {
            display: block;
        }

        .image-nav {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
        }

        .image-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s;
        }

        .image-dot.active {
            background: white;
        }

        .property-info {
            padding: 30px;
        }

        .property-title {
            font-size: 28px;
            font-weight: bold;
            color: #333;
            margin-bottom: 10px;
        }

        .property-location {
            color: #666;
            font-size: 16px;
            margin-bottom: 20px;
        }

        .property-price {
            font-size: 24px;
            font-weight: bold;
            color: #38a169;
            margin-bottom: 20px;
        }

        .property-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .detail-section {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }

        .detail-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 15px;
            color: #333;
        }

        .detail-grid {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 10px;
            margin-bottom: 15px;
        }

        .detail-label {
            font-weight: bold;
            color: #666;
        }

        .detail-value {
            color: #333;
        }

        .amenities-list {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 10px;
        }

        .amenity-tag {
            background: #38a169;
            color: white;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
        }

        .owner-info {
            background: #e8f5e8;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 30px;
            flex-wrap: wrap;
        }

        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: none;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-primary {
            background: #38a169;
            color: white;
        }

        .btn-primary:hover {
            background: #2f855a;
        }

        .btn-success {
            background: #38a169;
            color: white;
        }

        .btn-success:hover {
            background: #2f855a;
        }

        .btn-secondary {
            background: #6c757d;
            color: white;
        }

        .btn-secondary:hover {
            background: #545b62;
        }

        .btn-wishlist {
            background: #fff;
            color: #666;
            border: 2px solid #ddd;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .btn-wishlist:hover {
            border-color: #ff6b6b;
            color: #ff6b6b;
        }

        .btn-wishlist.in-wishlist {
            background: #ff6b6b;
            color: white;
            border-color: #ff6b6b;
        }

        .btn-wishlist.in-wishlist:hover {
            background: #e55a5a;
            border-color: #e55a5a;
        }

        .heart-icon {
            font-size: 18px;
            transition: transform 0.3s ease;
        }

        .btn-wishlist:hover .heart-icon {
            transform: scale(1.2);
        }

        .wishlist-loading {
            opacity: 0.7;
            pointer-events: none;
        }

        .visits-section {
            background: #fff3cd;
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }

        .visit-item {
            background: white;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 10px;
            border-left: 4px solid #38a169;
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

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #38a169;
            text-decoration: none;
            font-weight: bold;
        }

        .back-link:hover {
            text-decoration: underline;
        }

        .not-available {
            background: #f8d7da;
            color: #721c24;
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            text-align: center;
        }

        @media (max-width: 768px) {
            .property-details {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
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
                        <li><a href="index.php">Home</a></li>
                        <li><a href="properties.php">Properties</a></li>
                        <?php if (isset($_SESSION['user_id'])): ?>
                            <?php if ($_SESSION['user_type'] === 'customer'): ?>
                                <li><a href="customer/dashboard.php">Dashboard</a></li>
                            <?php elseif ($_SESSION['user_type'] === 'owner'): ?>
                                <li><a href="owner/dashboard.php">Dashboard</a></li>
                            <?php elseif ($_SESSION['user_type'] === 'admin'): ?>
                                <li><a href="admin/dashboard.php">Admin</a></li>
                            <?php endif; ?>
                            <li><a href="logout.php">Logout</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </header>

    <main class="container">
        <a href="index.php" class="back-link">← Back to Properties</a>

        <div class="property-hero">
            <div class="property-images">
                <?php if (!empty($images)): ?>
                    <?php foreach ($images as $index => $image): ?>
                        <img src="<?= htmlspecialchars($image['image_path']) ?>" 
                             alt="Property Image <?= $index + 1 ?>" 
                             class="property-image <?= $index === 0 ? 'active' : '' ?>"
                             loading="lazy"
                             onerror="this.style.display='none'; if(this.classList.contains('active') && this.nextElementSibling) this.nextElementSibling.classList.add('active');">
                    <?php endforeach; ?>
                    
                    <?php if (count($images) > 1): ?>
                        <div class="image-nav">
                            <?php foreach ($images as $index => $image): ?>
                                <div class="image-dot <?= $index === 0 ? 'active' : '' ?>" 
                                     onclick="showImage(<?= $index ?>)"></div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <!-- Fallback placeholder image -->
                    <div class="property-image active" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); display: flex; align-items: center; justify-content: center; color: white; font-size: 24px; font-weight: bold;">
                        <div style="text-align: center;">
                            <i class="fas fa-home" style="font-size: 48px; margin-bottom: 10px; display: block;"></i>
                            <div>No Images Available</div>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <div class="property-info">
                <h1 class="property-title"><?= htmlspecialchars($property['title']) ?></h1>
                <div class="property-location">
                    📍 <?= htmlspecialchars($property['address'] . ', ' . $property['city']) ?>
                </div>
                <div class="property-price">
                    Rs. <?= number_format($property['rent_amount'], 2) ?> / month
                </div>

                <?php if (!$property['is_available']): ?>
                    <div class="not-available">
                        <strong>⚠️ This property is currently not available for rent</strong>
                    </div>
                <?php endif; ?>

                <div class="property-details">
                    <div class="detail-section">
                        <div class="detail-title">Property Details</div>
                        <div class="detail-grid">
                            <span class="detail-label">Property Type:</span>
                            <span class="detail-value"><?= ucfirst($property['property_type']) ?></span>
                        </div>
                        <div class="detail-grid">
                            <span class="detail-label">Bedrooms:</span>
                            <span class="detail-value"><?= $property['bedrooms'] ?></span>
                        </div>
                        <div class="detail-grid">
                            <span class="detail-label">Bathrooms:</span>
                            <span class="detail-value"><?= $property['bathrooms'] ?></span>
                        </div>
                        <?php if (isset($property['area_sqft']) && $property['area_sqft']): ?>
                            <div class="detail-grid">
                                <span class="detail-label">Floor Area:</span>
                                <span class="detail-value"><?= number_format($property['area_sqft']) . ' sq ft' ?></span>
                            </div>
                        <?php elseif (isset($property['floor_area']) && $property['floor_area']): ?>
                            <div class="detail-grid">
                                <span class="detail-label">Floor Area:</span>
                                <span class="detail-value"><?= $property['floor_area'] . ' sq ft' ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($property['is_furnished'])): ?>
                            <div class="detail-grid">
                                <span class="detail-label">Furnished:</span>
                                <span class="detail-value"><?= $property['is_furnished'] ? 'Yes' : 'No' ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($property['pets_allowed'])): ?>
                            <div class="detail-grid">
                                <span class="detail-label">Pets Allowed:</span>
                                <span class="detail-value"><?= $property['pets_allowed'] ? 'Yes' : 'No' ?></span>
                            </div>
                        <?php endif; ?>
                        <?php if (isset($property['zip_code']) && $property['zip_code']): ?>
                            <div class="detail-grid">
                                <span class="detail-label">Zip Code:</span>
                                <span class="detail-value"><?= htmlspecialchars($property['zip_code']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($amenities)): ?>
                        <div class="detail-section">
                            <div class="detail-title">Amenities</div>
                            <div class="amenities-list">
                                <?php foreach ($amenities as $amenity): ?>
                                    <span class="amenity-tag"><?= htmlspecialchars($amenity) ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <?php if (isset($property['description']) && $property['description']): ?>
                    <div class="detail-section">
                        <div class="detail-title">Description</div>
                        <p><?= nl2br(htmlspecialchars($property['description'])) ?></p>
                    </div>
                <?php endif; ?>

                <div class="owner-info">
                    <div class="detail-title">Property Owner</div>
                    <p><strong>Name:</strong> <?= htmlspecialchars($property['owner_name']) ?></p>
                    <p><strong>Phone:</strong> <?= htmlspecialchars($property['owner_phone']) ?></p>
                    <p><strong>Email:</strong> <?= htmlspecialchars($property['owner_email']) ?></p>
                </div>

                <?php if ($property['is_available']): ?>
                    <div class="action-buttons">
                        <?php if ($is_customer): ?>
                            <!-- Wishlist Button -->
                            <button id="wishlist-btn" 
                                    class="btn btn-wishlist <?= $is_in_wishlist ? 'in-wishlist' : '' ?>" 
                                    onclick="toggleWishlist(<?= $property['id'] ?>)"
                                    data-property-id="<?= $property['id'] ?>">
                                <span class="heart-icon">❤️</span>
                                <span class="wishlist-text"><?= $is_in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist' ?></span>
                            </button>
                            
                            <?php
                            // Check if customer has pending visit
                            $has_pending = false;
                            foreach ($customer_visits as $visit) {
                                if ($visit['status'] === 'pending') {
                                    $has_pending = true;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if (!$has_pending): ?>
                                <a href="customer/book_visit.php?property_id=<?= $property['id'] ?>" class="btn btn-primary">
                                    📅 Book a Visit
                                </a>
                            <?php else: ?>
                                <span class="btn btn-secondary" style="opacity: 0.6;">
                                    📅 Visit Request Pending
                                </span>
                            <?php endif; ?>
                            
                            <a href="customer/rent_property.php?property_id=<?= $property['id'] ?>" class="btn btn-success">
                                🏠 Rent This Property
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=<?= urlencode($_SERVER['REQUEST_URI']) ?>" class="btn btn-primary">
                                Login to Book Visit
                            </a>
                            <a href="register.php" class="btn btn-secondary">
                                Register as Customer
                            </a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($customer_visits)): ?>
                    <div class="visits-section">
                        <div class="detail-title">Your Visit History</div>
                        <?php foreach ($customer_visits as $visit): ?>
                            <div class="visit-item">
                                <div>
                                    <strong>Visit Date:</strong> <?= date('M j, Y g:i A', strtotime($visit['requested_date'])) ?>
                                    <span class="visit-status status-<?= $visit['status'] ?>">
                                        <?= ucfirst($visit['status']) ?>
                                    </span>
                                </div>
                                <?php if ($visit['customer_notes']): ?>
                                    <p><strong>Your Notes:</strong> <?= htmlspecialchars($visit['customer_notes']) ?></p>
                                <?php endif; ?>
                                <?php if ($visit['owner_notes']): ?>
                                    <p><strong>Owner's Response:</strong> <?= htmlspecialchars($visit['owner_notes']) ?></p>
                                <?php endif; ?>
                                <small>Requested: <?= date('M j, Y g:i A', strtotime($visit['created_at'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; 2024 SmartRent Property Management System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        let currentImageIndex = 0;
        const images = document.querySelectorAll('.property-image');
        const dots = document.querySelectorAll('.image-dot');

        function showImage(index) {
            // Hide all images
            images.forEach(img => img.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));
            
            // Show selected image
            if (images[index]) {
                images[index].classList.add('active');
                dots[index].classList.add('active');
                currentImageIndex = index;
            }
        }

        // Auto-advance images every 5 seconds if there are multiple
        if (images.length > 1) {
            setInterval(() => {
                currentImageIndex = (currentImageIndex + 1) % images.length;
                showImage(currentImageIndex);
            }, 5000);
        }

        // Wishlist functionality
        function toggleWishlist(propertyId) {
            const btn = document.getElementById('wishlist-btn');
            const heartIcon = btn.querySelector('.heart-icon');
            const textSpan = btn.querySelector('.wishlist-text');
            
            // Add loading state
            btn.classList.add('wishlist-loading');
            textSpan.textContent = 'Processing...';
            
            fetch('api/wishlist.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `action=toggle&property_id=${propertyId}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (data.in_wishlist) {
                        btn.classList.add('in-wishlist');
                        textSpan.textContent = 'Remove from Wishlist';
                        heartIcon.style.animation = 'heartBeat 0.6s ease-in-out';
                    } else {
                        btn.classList.remove('in-wishlist');
                        textSpan.textContent = 'Add to Wishlist';
                    }
                    
                    // Update wishlist count in navigation if exists
                    const wishlistCount = document.querySelector('.wishlist-count');
                    if (wishlistCount) {
                        wishlistCount.textContent = data.wishlist_count;
                    }
                    
                    // Show success message
                    showNotification(data.message, 'success');
                } else {
                    showNotification(data.message || 'Error updating wishlist', 'error');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showNotification('Error updating wishlist', 'error');
            })
            .finally(() => {
                btn.classList.remove('wishlist-loading');
            });
        }
        
        // Show notification function
        function showNotification(message, type = 'info') {
            // Create notification element if it doesn't exist
            let notification = document.getElementById('notification');
            if (!notification) {
                notification = document.createElement('div');
                notification.id = 'notification';
                notification.style.cssText = `
                    position: fixed;
                    top: 20px;
                    right: 20px;
                    padding: 15px 20px;
                    border-radius: 5px;
                    color: white;
                    font-weight: bold;
                    z-index: 1000;
                    opacity: 0;
                    transform: translateX(100%);
                    transition: all 0.3s ease;
                `;
                document.body.appendChild(notification);
            }
            
            // Set message and style based on type
            notification.textContent = message;
            notification.className = `notification-${type}`;
            
            switch (type) {
                case 'success':
                    notification.style.background = '#28a745';
                    break;
                case 'error':
                    notification.style.background = '#dc3545';
                    break;
                default:
                    notification.style.background = '#007bff';
            }
            
            // Show notification
            setTimeout(() => {
                notification.style.opacity = '1';
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Hide notification after 3 seconds
            setTimeout(() => {
                notification.style.opacity = '0';
                notification.style.transform = 'translateX(100%)';
            }, 3000);
        }
        
        // Add heart beat animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes heartBeat {
                0% { transform: scale(1); }
                20% { transform: scale(1.3); }
                40% { transform: scale(1); }
                60% { transform: scale(1.3); }
                80% { transform: scale(1); }
                100% { transform: scale(1); }
            }
        `;
        document.head.appendChild(style);
    </script>
</body>
</html>