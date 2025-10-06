<?php
// Include database configuration
require_once '../../includes/config.php';

try {
    // Get payments with booking and user details
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.amount,
            p.status,
            p.payment_method,
            p.transaction_id,
            p.created_at,
            b.id as booking_id,
            prop.title as property_name,
            u.full_name as customer_name,
            u.email as customer_email
        FROM payments p
        LEFT JOIN bookings b ON p.booking_id = b.id
        LEFT JOIN properties prop ON b.property_id = prop.id
        LEFT JOIN users u ON b.customer_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll();
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Payments Management</h2>
    <div style="display: flex; gap: 10px;">
        <select class="form-input" style="width: 150px;" id="payment-status-filter">
            <option value="">All Status</option>
            <option value="pending">Pending</option>
            <option value="completed">Completed</option>
            <option value="failed">Failed</option>
            <option value="refunded">Refunded</option>
        </select>
        <select class="form-input" style="width: 150px;" id="payment-method-filter">
            <option value="">All Methods</option>
            <option value="payhere">PayHere</option>
            <option value="bank_transfer">Bank Transfer</option>
            <option value="cash">Cash</option>
            <option value="card">Card</option>
        </select>
    </div>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Payment ID</th>
                <th>Customer</th>
                <th>Property</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Status</th>
                <th>Date</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody id="payments-table-body">
            <?php foreach ($payments as $payment): ?>
            <tr data-payment-status="<?php echo $payment['status']; ?>" data-payment-method="<?php echo $payment['payment_method']; ?>">
                <td>
                    <strong>#<?php echo str_pad($payment['id'], 6, '0', STR_PAD_LEFT); ?></strong><br>
                    <?php if ($payment['transaction_id']): ?>
                        <small class="text-muted">TXN: <?php echo htmlspecialchars($payment['transaction_id']); ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($payment['customer_name']): ?>
                    <div>
                        <?php echo htmlspecialchars($payment['customer_name']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($payment['customer_email']); ?></small>
                    </div>
                    <?php else: ?>
                    <span class="text-muted">N/A</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($payment['property_name']): ?>
                    <div>
                        <?php echo htmlspecialchars($payment['property_name']); ?><br>
                        <small class="text-muted">Booking #<?php echo str_pad($payment['booking_id'], 6, '0', STR_PAD_LEFT); ?></small>
                    </div>
                    <?php else: ?>
                    <span class="text-muted">Direct Payment</span>
                    <?php endif; ?>
                </td>
                <td>Rs. <?php echo number_format($payment['amount'], 2); ?></td>
                <td>
                    <span class="status-badge">
                        <?php echo ucfirst(str_replace('_', ' ', $payment['payment_method'])); ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge status-<?php echo strtolower($payment['status']); ?>">
                        <?php echo ucfirst($payment['status']); ?>
                    </span>
                </td>
                <td><?php echo date('M j, Y', strtotime($payment['created_at'])); ?></td>
                <td>
                    <div style="display: flex; gap: 5px; flex-direction: column;">
                        <button class="btn btn-secondary payment-action" data-action="view" data-payment-id="<?php echo $payment['id']; ?>">
                            View
                        </button>
                        <?php if ($payment['status'] === 'pending'): ?>
                        <button class="btn btn-primary payment-action" data-action="approve" data-payment-id="<?php echo $payment['id']; ?>">
                            Approve
                        </button>
                        <button class="btn btn-danger payment-action" data-action="reject" data-payment-id="<?php echo $payment['id']; ?>">
                            Reject
                        </button>
                        <?php elseif ($payment['status'] === 'completed'): ?>
                        <button class="btn btn-warning payment-action" data-action="refund" data-payment-id="<?php echo $payment['id']; ?>">
                            Refund
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($payments)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-credit-card" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096;">No Payments Found</h3>
    <p class="text-muted">There are no payments in the system yet.</p>
</div>
<?php endif; ?>

<script>
// Add filtering functionality
document.getElementById('payment-status-filter').addEventListener('change', filterPayments);
document.getElementById('payment-method-filter').addEventListener('change', filterPayments);

function filterPayments() {
    const statusFilter = document.getElementById('payment-status-filter').value;
    const methodFilter = document.getElementById('payment-method-filter').value;
    const rows = document.querySelectorAll('#payments-table-body tr');

    rows.forEach(row => {
        const paymentStatus = row.getAttribute('data-payment-status');
        const paymentMethod = row.getAttribute('data-payment-method');
        
        const statusMatch = !statusFilter || paymentStatus === statusFilter;
        const methodMatch = !methodFilter || paymentMethod === methodFilter;
        
        if (statusMatch && methodMatch) {
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
        <p>Unable to load payments: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>