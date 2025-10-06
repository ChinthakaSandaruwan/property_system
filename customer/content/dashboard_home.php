<?php
require_once '../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer information
$customerQuery = "SELECT * FROM users WHERE id = ? AND user_type = 'customer'";
$customerStmt = $pdo->prepare($customerQuery);
$customerStmt->execute([$customer_id]);
$customer = $customerStmt->fetch();

// Get recent property views (if we track this)
$recentViewsQuery = "SELECT p.*, 
                     CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END as in_wishlist
                     FROM properties p
LEFT JOIN wishlists w ON p.id = w.property_id AND w.customer_id = ?
                     WHERE p.status = 'approved' AND p.is_available = TRUE
                     ORDER BY p.created_at DESC
                     LIMIT 6";
$recentViewsStmt = $pdo->prepare($recentViewsQuery);
$recentViewsStmt->execute([$customer_id]);
$recentViews = $recentViewsStmt->fetchAll();

// Get upcoming visits
$upcomingVisitsQuery = "SELECT v.*, p.title as property_title, CONCAT(p.address, ', ', p.city) as location, p.images
                        FROM property_visits v
                        JOIN properties p ON v.property_id = p.id
                        WHERE v.customer_id = ? 
                        AND v.status IN ('pending', 'approved')
                        AND v.requested_date > NOW()
                        ORDER BY v.requested_date ASC
                        LIMIT 3";
$upcomingVisitsStmt = $pdo->prepare($upcomingVisitsQuery);
$upcomingVisitsStmt->execute([$customer_id]);
$upcomingVisits = $upcomingVisitsStmt->fetchAll();

// Get active bookings
$activeBookingsQuery = "SELECT b.*, p.title as property_title, CONCAT(p.address, ', ', p.city) as location, p.images
                        FROM bookings b
                        JOIN properties p ON b.property_id = p.id
                        WHERE b.customer_id = ? AND b.status = 'active'
                        ORDER BY b.start_date DESC
                        LIMIT 3";
$activeBookingsStmt = $pdo->prepare($activeBookingsQuery);
$activeBookingsStmt->execute([$customer_id]);
$activeBookings = $activeBookingsStmt->fetchAll();

// Get featured properties (most popular or recently added)
$featuredPropertiesQuery = "SELECT p.*, 
                            CONCAT(p.address, ', ', p.city) as location,
                            p.rent_amount as price,
                            CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END as in_wishlist,
                            (SELECT COUNT(*) FROM property_visits pv WHERE pv.property_id = p.id) as visit_count
                            FROM properties p
LEFT JOIN wishlists w ON p.id = w.property_id AND w.customer_id = ?
                            WHERE p.status = 'approved' AND p.is_available = TRUE
                            ORDER BY visit_count DESC, p.created_at DESC
                            LIMIT 4";
$featuredPropertiesStmt = $pdo->prepare($featuredPropertiesQuery);
$featuredPropertiesStmt->execute([$customer_id]);
$featuredProperties = $featuredPropertiesStmt->fetchAll();
?>

<div class="dashboard-home">
    <!-- Welcome Section -->
    <div class="welcome-section">
        <div class="welcome-content">
            <h2>Welcome back, <?php echo htmlspecialchars($customer['full_name'] ?? 'Customer'); ?>!</h2>
            <p>Here's what's happening with your rentals and property searches.</p>
        </div>
        <div class="welcome-actions">
            <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">
                <i class="fas fa-search"></i> Browse Properties
            </button>
            <button class="btn-secondary" onclick="loadContent('my-visits', 'content/my_visits.php')">
                <i class="fas fa-calendar"></i> My Visits
            </button>
        </div>
    </div>

    <!-- Quick Stats -->
    <div class="quick-stats-grid">
        <div class="stat-card" onclick="loadContent('wishlist', 'content/my_wishlist.php')">
            <div class="stat-icon">
                <i class="fas fa-heart"></i>
            </div>
            <div class="stat-content">
                <h3 id="wishlist-count">0</h3>
                <p>Properties in Wishlist</p>
            </div>
        </div>
        
        <div class="stat-card" onclick="loadContent('my-bookings', 'content/my_bookings.php')">
            <div class="stat-icon">
                <i class="fas fa-key"></i>
            </div>
            <div class="stat-content">
                <h3 id="active-bookings-count">0</h3>
                <p>Active Rentals</p>
            </div>
        </div>
        
        <div class="stat-card" onclick="loadContent('my-visits', 'content/my_visits.php')">
            <div class="stat-icon">
                <i class="fas fa-calendar-check"></i>
            </div>
            <div class="stat-content">
                <h3 id="scheduled-visits-count">0</h3>
                <p>Scheduled Visits</p>
            </div>
        </div>
        
        <div class="stat-card" onclick="loadContent('my-payments', 'content/my_payments.php')">
            <div class="stat-icon">
                <i class="fas fa-credit-card"></i>
            </div>
            <div class="stat-content">
                <h3>LKR <span id="total-spent">0</span></h3>
                <p>Total Spent</p>
            </div>
        </div>
    </div>

    <!-- Main Dashboard Content -->
    <div class="dashboard-grid">
        <!-- Upcoming Visits -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3><i class="fas fa-calendar-alt"></i> Upcoming Visits</h3>
                <button class="btn-link" onclick="loadContent('my-visits', 'content/my_visits.php')">View All</button>
            </div>
            <div class="widget-content">
                <?php if (!empty($upcomingVisits)): ?>
                    <?php foreach ($upcomingVisits as $visit): ?>
                        <div class="visit-item">
                            <div class="visit-image">
                                <?php if (!empty($visit['images'])): ?>
                                    <?php 
                                    $images = json_decode($visit['images'], true);
                                    if ($images && is_array($images) && count($images) > 0) {
                                        $imagePath = $images[0];
                                        if (strpos($imagePath, 'uploads/properties/') === false) {
                                            $imagePath = '../uploads/properties/' . $images[0];
                                        } else {
                                            $imagePath = '../' . $images[0];
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Property" onerror="this.src='../assets/images/no-image.svg'">
                                    <?php } else { ?>
                                        <div class="no-image"><i class="fas fa-home"></i></div>
                                    <?php } ?>
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-home"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="visit-details">
                                <h4><?php echo htmlspecialchars($visit['property_title']); ?></h4>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($visit['location']); ?></p>
                                <p><i class="fas fa-calendar"></i> <?php echo date('M j, Y g:i A', strtotime($visit['requested_date'])); ?></p>
                            </div>
                            <div class="visit-actions">
                                <button class="btn-sm btn-primary" onclick="viewProperty(<?php echo $visit['property_id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">
                        <i class="fas fa-calendar-times"></i>
                        <p>No upcoming visits</p>
                        <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">Browse Properties</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Active Rentals -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <h3><i class="fas fa-key"></i> Active Rentals</h3>
                <button class="btn-link" onclick="loadContent('my-bookings', 'content/my_bookings.php')">View All</button>
            </div>
            <div class="widget-content">
                <?php if (!empty($activeBookings)): ?>
                    <?php foreach ($activeBookings as $booking): ?>
                        <div class="booking-item">
                            <div class="booking-image">
                                <?php if (!empty($booking['images'])): ?>
                                    <?php 
                                    $images = json_decode($booking['images'], true);
                                    if ($images && is_array($images) && count($images) > 0) {
                                        $imagePath = $images[0];
                                        if (strpos($imagePath, 'uploads/properties/') === false) {
                                            $imagePath = '../uploads/properties/' . $images[0];
                                        } else {
                                            $imagePath = '../' . $images[0];
                                        }
                                    ?>
                                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Property" onerror="this.src='../assets/images/no-image.svg'">
                                    <?php } else { ?>
                                        <div class="no-image"><i class="fas fa-home"></i></div>
                                    <?php } ?>
                                <?php else: ?>
                                    <div class="no-image"><i class="fas fa-home"></i></div>
                                <?php endif; ?>
                            </div>
                            <div class="booking-details">
                                <h4><?php echo htmlspecialchars($booking['property_title']); ?></h4>
                                <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($booking['location']); ?></p>
                                <p><i class="fas fa-money-bill-wave"></i> LKR <?php echo number_format($booking['monthly_rent']); ?>/month</p>
                                <?php if ($booking['end_date']): ?>
                                    <p><i class="fas fa-calendar-times"></i> Ends: <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="booking-actions">
                                <button class="btn-sm btn-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="no-items">
                        <i class="fas fa-key"></i>
                        <p>No active rentals</p>
                        <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">Find a Property</button>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Featured Properties -->
        <div class="dashboard-widget featured-properties">
            <div class="widget-header">
                <h3><i class="fas fa-star"></i> Featured Properties</h3>
            </div>
            <div class="widget-content">
                <?php if (!empty($featuredProperties)): ?>
                    <div class="featured-grid">
                        <?php foreach ($featuredProperties as $property): ?>
                            <div class="featured-property-card">
                                <div class="property-image">
                                    <?php if (!empty($property['images'])): ?>
                                        <?php 
                                        $images = json_decode($property['images'], true);
                                        if ($images && is_array($images) && count($images) > 0) {
                                            $imagePath = $images[0];
                                            if (strpos($imagePath, 'uploads/properties/') === false) {
                                                $imagePath = '../uploads/properties/' . $images[0];
                                            } else {
                                                $imagePath = '../' . $images[0];
                                            }
                                        ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Property" onerror="this.src='../assets/images/no-image.svg'">
                                        <?php } else { ?>
                                            <div class="no-image"><i class="fas fa-home"></i></div>
                                        <?php } ?>
                                    <?php else: ?>
                                        <div class="no-image"><i class="fas fa-home"></i></div>
                                    <?php endif; ?>
                                    
                                    <button class="wishlist-btn <?php echo $property['in_wishlist'] ? 'active' : ''; ?>" 
                                            onclick="toggleWishlist(<?php echo $property['id']; ?>)">
                                        <i class="<?php echo $property['in_wishlist'] ? 'fas' : 'far'; ?> fa-heart"></i>
                                    </button>
                                </div>
                                <div class="property-details">
                                    <h4><?php echo htmlspecialchars($property['title']); ?></h4>
                                    <p><i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['location']); ?></p>
                                    <p class="price">LKR <?php echo number_format($property['price']); ?>/month</p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="no-items">
                        <i class="fas fa-home"></i>
                        <p>No featured properties available</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- AJAX Widget Containers (for JavaScript functionality) -->
        <div class="dashboard-widget" style="display: none;">
            <div class="widget-header">
                <h3><i class="fas fa-history"></i> Recent Views</h3>
            </div>
            <div class="widget-content" id="recent-views">
                <!-- Populated by JavaScript -->
            </div>
        </div>
        
        <div class="dashboard-widget" style="display: none;">
            <div class="widget-header">
                <h3><i class="fas fa-star"></i> Featured Properties (AJAX)</h3>
            </div>
            <div class="widget-content" id="featured-properties">
                <!-- Populated by JavaScript -->
            </div>
        </div>
        
        <div class="dashboard-widget" style="display: none;">
            <div class="widget-header">
                <h3><i class="fas fa-calendar"></i> Upcoming Visits (AJAX)</h3>
            </div>
            <div class="widget-content" id="upcoming-visits">
                <!-- Populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<script>
function viewProperty(propertyId) {
    window.open(`property_details.php?id=${propertyId}`, '_blank');
}

function viewBooking(bookingId) {
    window.open(`booking_details.php?id=${bookingId}`, '_blank');
}

function bookVisit(propertyId) {
    window.open(`book_visit.php?property_id=${propertyId}`, '_blank');
}

function toggleWishlist(propertyId) {
    fetch('api/wishlist_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'toggle',
            property_id: propertyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const wishlistBtn = document.querySelector(`button[onclick="toggleWishlist(${propertyId})"]`);
            const icon = wishlistBtn.querySelector('i');
            
            if (data.added) {
                wishlistBtn.classList.add('active');
                icon.className = 'fas fa-heart';
            } else {
                wishlistBtn.classList.remove('active');
                icon.className = 'far fa-heart';
            }
            
            showNotification(data.message, 'success');
            updateStats();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

// Load stats on page load
document.addEventListener('DOMContentLoaded', function() {
    updateStats();
});
</script>