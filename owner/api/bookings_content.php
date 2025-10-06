<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 1;

try {
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.status,
            b.start_date,
            b.end_date,
            b.total_amount,
            b.created_at,
            p.title as property_name,
            p.rent_amount,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        JOIN users u ON b.customer_id = u.id
        WHERE p.owner_id = ?
        ORDER BY b.created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $bookings = $stmt->fetchAll();
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Property Bookings</h2>
    <div class="quick-actions">
        <select class="form-input" style="width: 150px;" id="booking-status-filter">
            <option value="">All Bookings</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<div class="bookings-grid" style="display: grid; gap: 20px;">
    <?php foreach ($bookings as $booking): ?>
    <div class="booking-card" data-booking-status="<?php echo $booking['status']; ?>" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $booking['status'] === 'active' ? '#38a169' : ($booking['status'] === 'pending' ? '#ed8936' : '#4a5568'); ?>;">
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
            <div>
                <h3 style="font-size: 1.2rem; color: #2d3748; margin-bottom: 5px;"><?php echo htmlspecialchars($booking['property_name']); ?></h3>
                <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                    <?php echo ucfirst($booking['status']); ?>
                </span>
            </div>
            <div class="text-right">
                <div style="font-size: 1.1rem; font-weight: 600; color: #38a169;">Rs. <?php echo number_format($booking['total_amount'], 2); ?></div>
                <small class="text-muted">Booking #<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></small>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <strong>Customer Details:</strong><br>
                <span><?php echo htmlspecialchars($booking['customer_name']); ?></span><br>
                <small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small><br>
                <small class="text-muted"><?php echo htmlspecialchars($booking['customer_phone']); ?></small>
            </div>
            <div>
                <strong>Booking Period:</strong><br>
                <span><?php echo date('M j, Y', strtotime($booking['start_date'])); ?></span>
                <?php if ($booking['end_date']): ?>
                    to <?php echo date('M j, Y', strtotime($booking['end_date'])); ?>
                <?php else: ?>
                    <span class="text-muted">(Ongoing)</span>
                <?php endif; ?>
                <br>
                <small class="text-muted">Booked on <?php echo date('M j, Y', strtotime($booking['created_at'])); ?></small>
            </div>
        </div>

        <div class="booking-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="btn btn-secondary booking-action" data-action="view" data-booking-id="<?php echo $booking['id']; ?>">
                <i class="fas fa-eye"></i> View Details
            </button>
            
            <?php if ($booking['status'] === 'pending'): ?>
                <button class="btn btn-success booking-action" data-action="approve" data-booking-id="<?php echo $booking['id']; ?>">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn btn-danger booking-action" data-action="reject" data-booking-id="<?php echo $booking['id']; ?>">
                    <i class="fas fa-times"></i> Reject
                </button>
            <?php elseif ($booking['status'] === 'approved'): ?>
                <button class="btn btn-primary booking-action" data-action="activate" data-booking-id="<?php echo $booking['id']; ?>">
                    <i class="fas fa-play"></i> Start Rental
                </button>
            <?php elseif ($booking['status'] === 'active'): ?>
                <button class="btn btn-warning booking-action" data-action="complete" data-booking-id="<?php echo $booking['id']; ?>">
                    <i class="fas fa-flag-checkered"></i> End Rental
                </button>
            <?php endif; ?>
            
            <button class="btn btn-secondary booking-action" data-action="contact" data-booking-id="<?php echo $booking['id']; ?>">
                <i class="fas fa-phone"></i> Contact Customer
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($bookings)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-calendar-alt" style="font-size: 4rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096; margin-bottom: 20px;">No Bookings Yet</h3>
    <p class="text-muted" style="margin-bottom: 30px;">When customers book your properties, they'll appear here for you to manage.</p>
    <a href="#properties" class="btn btn-primary" onclick="window.ownerDashboard?.navigateToSection('properties')">
        <i class="fas fa-home"></i> View My Properties
    </a>
</div>
<?php endif; ?>

<script>
// Add filtering functionality
document.getElementById('booking-status-filter').addEventListener('change', filterBookings);

function filterBookings() {
    const statusFilter = document.getElementById('booking-status-filter').value;
    const bookingCards = document.querySelectorAll('.booking-card');

    bookingCards.forEach(card => {
        const bookingStatus = card.getAttribute('data-booking-status');
        
        if (!statusFilter || bookingStatus === statusFilter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}
</script>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load bookings: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>