<?php
require_once '../../includes/config.php';

$customer_id = $_SESSION['customer_id'];
$booking_id = $_GET['booking_id'] ?? null;

// Build query based on filters
$whereClause = "WHERE p.customer_id = ?";
$params = [$customer_id];

if ($booking_id) {
    $whereClause .= " AND p.booking_id = ?";
    $params[] = $booking_id;
}

// Get customer payments
$query = "SELECT p.*, b.id as booking_id, pr.title as property_title, 
                 CONCAT(pr.address, ', ', pr.city) as location
          FROM payments p
          LEFT JOIN bookings b ON p.booking_id = b.id
          LEFT JOIN properties pr ON b.property_id = pr.id
          $whereClause
          ORDER BY p.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$payments = $stmt->fetchAll();

// Calculate totals
$totalPaid = array_sum(array_column($payments, 'amount'));
$successfulPayments = array_filter($payments, function($p) { return $p['status'] === 'completed'; });
$pendingPayments = array_filter($payments, function($p) { return $p['status'] === 'pending'; });
$failedPayments = array_filter($payments, function($p) { return $p['status'] === 'failed'; });
?>

<div class="my-payments">
    <div class="page-header">
        <h2><i class="fas fa-credit-card"></i> My Payments</h2>
        <p>View your payment history and manage transactions</p>
        <?php if ($booking_id): ?>
            <div class="filter-info">
                <i class="fas fa-filter"></i> Showing payments for Booking #<?php echo $booking_id; ?>
                <button class="btn-link" onclick="loadContent('my-payments', 'content/my_payments.php')">
                    <i class="fas fa-times"></i> Clear Filter
                </button>
            </div>
        <?php endif; ?>
        <div class="header-stats">
            <span class="stat-item">
                <strong><?php echo count($successfulPayments); ?></strong> Successful
            </span>
            <span class="stat-item">
                <strong><?php echo count($pendingPayments); ?></strong> Pending
            </span>
            <span class="stat-item">
                <strong>LKR <?php echo number_format($totalPaid); ?></strong> Total Paid
            </span>
        </div>
    </div>

    <!-- Payment Summary -->
    <div class="payment-summary">
        <div class="summary-cards">
            <div class="summary-card success">
                <div class="card-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
                <div class="card-content">
                    <h3>LKR <?php echo number_format(array_sum(array_column($successfulPayments, 'amount'))); ?></h3>
                    <p>Successfully Paid</p>
                    <span class="count"><?php echo count($successfulPayments); ?> payments</span>
                </div>
            </div>
            
            <div class="summary-card pending">
                <div class="card-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="card-content">
                    <h3>LKR <?php echo number_format(array_sum(array_column($pendingPayments, 'amount'))); ?></h3>
                    <p>Pending Payments</p>
                    <span class="count"><?php echo count($pendingPayments); ?> payments</span>
                </div>
            </div>
            
            <div class="summary-card failed">
                <div class="card-icon">
                    <i class="fas fa-times-circle"></i>
                </div>
                <div class="card-content">
                    <h3>LKR <?php echo number_format(array_sum(array_column($failedPayments, 'amount'))); ?></h3>
                    <p>Failed Payments</p>
                    <span class="count"><?php echo count($failedPayments); ?> payments</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Filters -->
    <div class="payment-filters">
        <button class="filter-btn active" onclick="filterPayments('all')">All Payments</button>
        <button class="filter-btn" onclick="filterPayments('completed')">Successful (<?php echo count($successfulPayments); ?>)</button>
        <button class="filter-btn" onclick="filterPayments('pending')">Pending (<?php echo count($pendingPayments); ?>)</button>
        <button class="filter-btn" onclick="filterPayments('failed')">Failed (<?php echo count($failedPayments); ?>)</button>
    </div>

    <?php if (!empty($payments)): ?>
        <div class="payments-list">
            <?php foreach ($payments as $payment): ?>
                <div class="payment-card" data-status="<?php echo $payment['status']; ?>">
                    <div class="payment-header">
                        <div class="payment-info">
                            <h3>Payment #<?php echo $payment['id']; ?></h3>
                            <?php if ($payment['property_title']): ?>
                                <p class="property-title">
                                    <i class="fas fa-home"></i> <?php echo htmlspecialchars($payment['property_title']); ?>
                                </p>
                            <?php endif; ?>
                        </div>
                        <div class="payment-amount">
                            <div class="amount">LKR <?php echo number_format($payment['amount']); ?></div>
                            <span class="status-badge status-<?php echo $payment['status']; ?>">
                                <?php echo ucfirst($payment['status']); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="payment-details">
                        <div class="detail-row">
                            <span class="label">Payment Date:</span>
                            <span class="value"><?php echo date('F j, Y g:i A', strtotime($payment['created_at'])); ?></span>
                        </div>
                        
                        <div class="detail-row">
                            <span class="label">Payment Method:</span>
                            <span class="value"><?php echo ucfirst($payment['payment_method'] ?? 'N/A'); ?></span>
                        </div>
                        
                        <?php if ($payment['transaction_id']): ?>
                            <div class="detail-row">
                                <span class="label">Transaction ID:</span>
                                <span class="value"><?php echo htmlspecialchars($payment['transaction_id']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($payment['booking_id']): ?>
                            <div class="detail-row">
                                <span class="label">Booking:</span>
                                <span class="value">
                                    <a href="#" onclick="loadContent('my-bookings', 'content/my_bookings.php')" class="booking-link">
                                        Booking #<?php echo $payment['booking_id']; ?>
                                    </a>
                                </span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($payment['payment_purpose']): ?>
                            <div class="detail-row">
                                <span class="label">Purpose:</span>
                                <span class="value"><?php echo ucfirst($payment['payment_purpose']); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($payment['notes']): ?>
                            <div class="detail-row">
                                <span class="label">Notes:</span>
                                <span class="value"><?php echo htmlspecialchars($payment['notes']); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="payment-actions">
                        <?php if ($payment['status'] === 'completed'): ?>
                            <button class="btn-secondary" onclick="downloadReceipt(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-download"></i> Download Receipt
                            </button>
                        <?php elseif ($payment['status'] === 'pending'): ?>
                            <button class="btn-warning" onclick="checkPaymentStatus(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-sync"></i> Check Status
                            </button>
                        <?php elseif ($payment['status'] === 'failed'): ?>
                            <button class="btn-primary" onclick="retryPayment(<?php echo $payment['id']; ?>)">
                                <i class="fas fa-redo"></i> Retry Payment
                            </button>
                        <?php endif; ?>
                        
                        <button class="btn-secondary" onclick="viewPaymentDetails(<?php echo $payment['id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Export Options -->
        <div class="export-options">
            <h3>Export Payment History</h3>
            <div class="export-buttons">
                <button class="btn-secondary" onclick="exportPayments('pdf')">
                    <i class="fas fa-file-pdf"></i> Export as PDF
                </button>
                <button class="btn-secondary" onclick="exportPayments('csv')">
                    <i class="fas fa-file-csv"></i> Export as CSV
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="no-payments">
            <i class="fas fa-credit-card"></i>
            <h3>No Payment History</h3>
            <p>You haven't made any payments yet. Your payment history will appear here once you start renting properties.</p>
            <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">
                <i class="fas fa-search"></i> Browse Properties
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
function filterPayments(status) {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const paymentCards = document.querySelectorAll('.payment-card');
    
    // Update active filter button
    filterButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide payment cards
    paymentCards.forEach(card => {
        if (status === 'all' || card.dataset.status === status) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
}

function downloadReceipt(paymentId) {
    window.open(`api/download_receipt.php?payment_id=${paymentId}`, '_blank');
}

function checkPaymentStatus(paymentId) {
    fetch('api/payment_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'check_status',
            payment_id: paymentId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`Payment status updated: ${data.status}`, 'success');
            if (data.status_changed) {
                loadContent('my-payments', 'content/my_payments.php');
            }
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while checking payment status', 'error');
    });
}

function retryPayment(paymentId) {
    if (confirm('Are you sure you want to retry this payment?')) {
        window.open(`retry_payment.php?payment_id=${paymentId}`, '_blank');
    }
}

function viewPaymentDetails(paymentId) {
    window.open(`payment_details.php?id=${paymentId}`, '_blank');
}

function exportPayments(format) {
    const urlParams = new URLSearchParams(window.location.search);
    const bookingId = urlParams.get('booking_id');
    
    let url = `api/export_payments.php?format=${format}`;
    if (bookingId) {
        url += `&booking_id=${bookingId}`;
    }
    
    window.open(url, '_blank');
}
</script>