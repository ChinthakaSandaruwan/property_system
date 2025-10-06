<?php
// Include database configuration
require_once '../../includes/config.php';

try {
    // Get bookings with property and user details
    $stmt = $pdo->prepare("
        SELECT 
            b.id,
            b.status,
            b.created_at,
            b.start_date as check_in_date,
            b.end_date as check_out_date,
            b.total_amount,
            p.title as property_name,
            p.property_type as property_type,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        JOIN users u ON b.customer_id = u.id
        ORDER BY b.created_at DESC
    ");
    $stmt->execute();
    $bookings = $stmt->fetchAll();
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Bookings Management</h2>
    <div style="display: flex; gap: 10px;">
        <select class="form-input" style="width: 150px;" id="booking-status-filter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="confirmed">Confirmed</option>
            <option value="active">Active</option>
            <option value="completed">Completed</option>
            <option value="cancelled">Cancelled</option>
        </select>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Booking ID</th>
                <th>Property</th>
                <th>Customer</th>
                <th>Dates</th>
                <th>Amount</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="bookings-table-body">
            <?php foreach ($bookings as $booking): ?>
            <tr data-booking-status="<?php echo $booking['status']; ?>">
                <td>
                    <strong>#<?php echo str_pad($booking['id'], 6, '0', STR_PAD_LEFT); ?></strong><br>
                    <small class="text-muted"><?php echo date('M j, Y', strtotime($booking['created_at'])); ?></small>
                </td>
                <td>
                    <div>
                        <strong><?php echo htmlspecialchars($booking['property_name']); ?></strong><br>
                        <small class="text-muted"><?php echo ucfirst($booking['property_type']); ?></small>
                    </div>
                </td>
                <td>
                    <div>
                        <?php echo htmlspecialchars($booking['customer_name']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($booking['customer_email']); ?></small><br>
                        <small class="text-muted"><?php echo htmlspecialchars($booking['customer_phone']); ?></small>
                    </div>
                </td>
                <td>
                    <div>
                        <strong>Check-in:</strong> <?php echo date('M j, Y', strtotime($booking['check_in_date'])); ?><br>
                        <strong>Check-out:</strong> <?php echo date('M j, Y', strtotime($booking['check_out_date'])); ?>
                    </div>
                </td>
                <td>Rs. <?php echo number_format($booking['total_amount'], 2); ?></td>
                <td>
                    <span class="status-badge status-<?php echo strtolower($booking['status']); ?>">
                        <?php echo ucfirst($booking['status']); ?>
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 5px; flex-direction: column;">
                        <button class="btn btn-secondary booking-action" data-action="view" data-booking-id="<?php echo $booking['id']; ?>">
                            View Details
                        </button>
                        <?php if ($booking['status'] === 'pending'): ?>
                        <button class="btn btn-primary booking-action" data-action="confirm" data-booking-id="<?php echo $booking['id']; ?>">
                            Confirm
                        </button>
                        <button class="btn btn-danger booking-action" data-action="cancel" data-booking-id="<?php echo $booking['id']; ?>">
                            Cancel
                        </button>
                        <?php elseif ($booking['status'] === 'confirmed'): ?>
                        <button class="btn btn-primary booking-action" data-action="activate" data-booking-id="<?php echo $booking['id']; ?>">
                            Check In
                        </button>
                        <button class="btn btn-danger booking-action" data-action="cancel" data-booking-id="<?php echo $booking['id']; ?>">
                            Cancel
                        </button>
                        <?php elseif ($booking['status'] === 'active'): ?>
                        <button class="btn btn-primary booking-action" data-action="complete" data-booking-id="<?php echo $booking['id']; ?>">
                            Check Out
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($bookings)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-calendar-check" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096;">No Bookings Found</h3>
    <p class="text-muted">There are no bookings in the system yet.</p>
</div>
<?php endif; ?>

<script>
// Add filtering functionality
document.getElementById('booking-status-filter').addEventListener('change', filterBookings);

function filterBookings() {
    const statusFilter = document.getElementById('booking-status-filter').value;
    const rows = document.querySelectorAll('#bookings-table-body tr');

    rows.forEach(row => {
        const bookingStatus = row.getAttribute('data-booking-status');
        
        const statusMatch = !statusFilter || bookingStatus === statusFilter;
        
        if (statusMatch) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
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