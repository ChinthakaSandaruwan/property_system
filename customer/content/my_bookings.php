<?php
require_once '../../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer bookings
$query = "SELECT b.*, p.title as property_title, 
                 CONCAT(p.address, ', ', p.city) as location, 
                 p.images, p.rent_amount as price,
                 p.property_type, p.address, p.bedrooms, p.bathrooms,
                 u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email
          FROM bookings b
          JOIN properties p ON b.property_id = p.id
          JOIN users u ON p.owner_id = u.id
          WHERE b.customer_id = ?
          ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$customer_id]);
$bookings = $stmt->fetchAll();

// Group bookings by status
$active_bookings = array_filter($bookings, function($b) { return $b['status'] === 'active'; });
$pending_bookings = array_filter($bookings, function($b) { return $b['status'] === 'pending'; });
$completed_bookings = array_filter($bookings, function($b) { return $b['status'] === 'completed'; });
$cancelled_bookings = array_filter($bookings, function($b) { return $b['status'] === 'cancelled'; });
?>

<div class="my-bookings">
    <div class="page-header">
        <h2><i class="fas fa-calendar-check"></i> My Bookings</h2>
        <p>Manage your property bookings and rentals</p>
        <div class="header-stats">
            <span class="stat-item">
                <strong><?php echo count($active_bookings); ?></strong> Active
            </span>
            <span class="stat-item">
                <strong><?php echo count($pending_bookings); ?></strong> Pending
            </span>
            <span class="stat-item">
                <strong><?php echo count($bookings); ?></strong> Total
            </span>
        </div>
    </div>

    <!-- Booking Filters -->
    <div class="booking-filters">
        <button class="filter-btn active" onclick="filterBookings('all')">All Bookings</button>
        <button class="filter-btn" onclick="filterBookings('active')">Active (<?php echo count($active_bookings); ?>)</button>
        <button class="filter-btn" onclick="filterBookings('pending')">Pending (<?php echo count($pending_bookings); ?>)</button>
        <button class="filter-btn" onclick="filterBookings('completed')">Completed (<?php echo count($completed_bookings); ?>)</button>
        <button class="filter-btn" onclick="filterBookings('cancelled')">Cancelled (<?php echo count($cancelled_bookings); ?>)</button>
    </div>

    <?php if (!empty($bookings)): ?>
        <div class="bookings-list">
            <?php foreach ($bookings as $booking): ?>
                <div class="booking-card" data-status="<?php echo $booking['status']; ?>">
                    <div class="booking-image">
                        <?php if (!empty($booking['images'])): ?>
                            <?php $images = json_decode($booking['images'], true); ?>
                            <img src="../uploads/<?php echo htmlspecialchars($images[0]); ?>" alt="Property Image">
                        <?php else: ?>
                            <img src="../assets/images/no-image.jpg" alt="No Image">
                        <?php endif; ?>
                        
                        <div class="booking-status">
                            <span class="status-badge status-<?php echo $booking['status']; ?>">
                                <?php echo ucfirst($booking['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="booking-details">
                        <div class="booking-header">
                            <h3><?php echo htmlspecialchars($booking['property_title']); ?></h3>
                            <div class="booking-id">#<?php echo $booking['id']; ?></div>
                        </div>
                        
                        <div class="booking-info">
                            <div class="info-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($booking['location']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-home"></i>
                                <span><?php echo ucfirst($booking['property_type']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-calendar"></i>
                                <span>Booked: <?php echo date('M j, Y', strtotime($booking['created_at'])); ?></span>
                            </div>
                            <?php if ($booking['start_date']): ?>
                                <div class="info-row">
                                    <i class="fas fa-play"></i>
                                    <span>Start: <?php echo date('M j, Y', strtotime($booking['start_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <?php if ($booking['end_date']): ?>
                                <div class="info-row">
                                    <i class="fas fa-stop"></i>
                                    <span>End: <?php echo date('M j, Y', strtotime($booking['end_date'])); ?></span>
                                </div>
                            <?php endif; ?>
                            <div class="info-row">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>LKR <?php echo number_format($booking['monthly_rent']); ?>/month</span>
                            </div>
                        </div>
                        
                        <div class="owner-info">
                            <h4>Owner Contact</h4>
                            <div class="info-row">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($booking['owner_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($booking['owner_phone']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-envelope"></i>
                                <span><?php echo htmlspecialchars($booking['owner_email']); ?></span>
                            </div>
                        </div>
                        
                        <div class="booking-actions">
                            <?php if ($booking['status'] === 'pending'): ?>
                                <button class="btn-danger" onclick="cancelBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel Booking
                                </button>
                                <button class="btn-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                            <?php elseif ($booking['status'] === 'active'): ?>
                                <button class="btn-secondary" onclick="viewPayments(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-credit-card"></i> View Payments
                                </button>
                                <button class="btn-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <button class="btn-warning" onclick="endRental(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-sign-out-alt"></i> End Rental
                                </button>
                            <?php else: ?>
                                <button class="btn-primary" onclick="viewBooking(<?php echo $booking['id']; ?>)">
                                    <i class="fas fa-eye"></i> View Details
                                </button>
                                <?php if ($booking['status'] === 'completed'): ?>
                                    <button class="btn-secondary" onclick="downloadReceipt(<?php echo $booking['id']; ?>)">
                                        <i class="fas fa-download"></i> Download Receipt
                                    </button>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-bookings">
            <i class="fas fa-calendar-times"></i>
            <h3>No Bookings Yet</h3>
            <p>You haven't made any property bookings yet. Start browsing to find your perfect rental.</p>
            <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">
                <i class="fas fa-search"></i> Browse Properties
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
function filterBookings(status) {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const bookingCards = document.querySelectorAll('.booking-card');
    
    // Update active filter button
    filterButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide booking cards
    bookingCards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function cancelBooking(bookingId) {
    if (!confirm('Are you sure you want to cancel this booking? This action cannot be undone.')) {
        return;
    }
    
    fetch('api/booking_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'cancel',
            booking_id: bookingId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadContent('my-bookings', 'content/my_bookings.php');
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

function endRental(bookingId) {
    if (!confirm('Are you sure you want to end this rental? This will complete your tenancy.')) {
        return;
    }
    
    fetch('api/booking_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'end_rental',
            booking_id: bookingId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadContent('my-bookings', 'content/my_bookings.php');
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

function viewBooking(bookingId) {
    // Open booking details in a modal or new page
    window.open(`booking_details.php?id=${bookingId}`, '_blank');
}

function viewPayments(bookingId) {
    loadContent('my-payments', `content/my_payments.php?booking_id=${bookingId}`);
}

function downloadReceipt(bookingId) {
    window.open(`api/download_receipt.php?booking_id=${bookingId}`, '_blank');
}
</script>