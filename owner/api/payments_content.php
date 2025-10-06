<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 1;

try {
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.amount,
            p.owner_payout,
            p.commission,
            p.status,
            p.payment_date,
            p.created_at,
            b.id as booking_id,
            prop.title as property_name,
            u.full_name as customer_name
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN properties prop ON b.property_id = prop.id
        LEFT JOIN users u ON b.customer_id = u.id
        WHERE p.owner_id = ?
        ORDER BY p.created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $payments = $stmt->fetchAll();
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>My Payments</h2>
    <div class="quick-actions">
        <select class="form-input" style="width: 150px;" id="payment-status-filter">
            <option value="">All Payments</option>
            <option value="pending">Pending</option>
            <option value="successful">Successful</option>
            <option value="failed">Failed</option>
        </select>
    </div>
</div>

<div class="payments-grid" style="display: grid; gap: 20px;">
    <?php foreach ($payments as $payment): ?>
    <div class="payment-card" data-payment-status="<?php echo $payment['status']; ?>" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $payment['status'] === 'successful' ? '#38a169' : ($payment['status'] === 'pending' ? '#ed8936' : '#e53e3e'); ?>;">
        
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
            <div>
                <h3 style="font-size: 1.2rem; color: #2d3748; margin-bottom: 5px;">Payment #<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></h3>
                <span class="status-badge status-<?php echo strtolower($payment['status']); ?>">
                    <?php echo ucfirst($payment['status']); ?>
                </span>
            </div>
            <div class="text-right">
                <div style="font-size: 1.3rem; font-weight: 700; color: #38a169;">Rs. <?php echo number_format($payment['owner_payout'], 2); ?></div>
                <small class="text-muted">Your Payout</small>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <strong>Payment Details:</strong><br>
                <span>Total Amount: Rs. <?php echo number_format($payment['amount'], 2); ?></span><br>
                <small class="text-muted">Commission: Rs. <?php echo number_format($payment['commission'], 2); ?></small><br>
                <?php if ($payment['payment_date']): ?>
                    <small class="text-success">Paid on <?php echo date('M j, Y', strtotime($payment['payment_date'])); ?></small>
                <?php else: ?>
                    <small class="text-muted">Payment pending</small>
                <?php endif; ?>
            </div>
            <div>
                <strong>Booking Info:</strong><br>
                <?php if ($payment['property_name']): ?>
                    <span><?php echo htmlspecialchars($payment['property_name']); ?></span><br>
                    <small class="text-muted">by <?php echo htmlspecialchars($payment['customer_name']); ?></small><br>
                    <small class="text-muted">Booking #<?php echo str_pad($payment['booking_id'], 6, '0', STR_PAD_LEFT); ?></small>
                <?php else: ?>
                    <span class="text-muted">Direct Payment</span>
                <?php endif; ?>
            </div>
        </div>

        <div style="background: #f7fafc; padding: 15px; border-radius: 8px; margin-bottom: 15px;">
            <div style="display: flex; justify-content: space-between;">
                <span>Total Payment:</span>
                <strong>Rs. <?php echo number_format($payment['amount'], 2); ?></strong>
            </div>
            <div style="display: flex; justify-content: space-between; color: #e53e3e;">
                <span>Platform Commission:</span>
                <strong>- Rs. <?php echo number_format($payment['commission'], 2); ?></strong>
            </div>
            <hr style="margin: 10px 0; border: none; border-top: 1px solid #e2e8f0;">
            <div style="display: flex; justify-content: space-between; font-size: 1.1rem; color: #38a169;">
                <span><strong>Your Earnings:</strong></span>
                <strong>Rs. <?php echo number_format($payment['owner_payout'], 2); ?></strong>
            </div>
        </div>

        <div class="payment-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="btn btn-secondary payment-action" data-action="view" data-payment-id="<?php echo $payment['id']; ?>">
                <i class="fas fa-eye"></i> View Details
            </button>
            
            <?php if ($payment['status'] === 'successful'): ?>
                <button class="btn btn-primary payment-action" data-action="receipt" data-payment-id="<?php echo $payment['id']; ?>">
                    <i class="fas fa-receipt"></i> Download Receipt
                </button>
            <?php endif; ?>
            
            <?php if ($payment['booking_id']): ?>
                <button class="btn btn-secondary payment-action" data-action="booking" data-booking-id="<?php echo $payment['booking_id']; ?>">
                    <i class="fas fa-calendar-alt"></i> View Booking
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($payments)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-money-bill-wave" style="font-size: 4rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096; margin-bottom: 20px;">No Payments Yet</h3>
    <p class="text-muted" style="margin-bottom: 30px;">Your payment history will appear here once you start earning from bookings.</p>
    <a href="#properties" class="btn btn-primary" onclick="window.ownerDashboard?.navigateToSection('properties')">
        <i class="fas fa-home"></i> View My Properties
    </a>
</div>
<?php endif; ?>

<script>
// Add filtering functionality
document.getElementById('payment-status-filter').addEventListener('change', filterPayments);

function filterPayments() {
    const statusFilter = document.getElementById('payment-status-filter').value;
    const paymentCards = document.querySelectorAll('.payment-card');

    paymentCards.forEach(card => {
        const paymentStatus = card.getAttribute('data-payment-status');
        
        if (!statusFilter || paymentStatus === statusFilter) {
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
        <p>Unable to load payments: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>