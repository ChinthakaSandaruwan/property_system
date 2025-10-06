<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 1;

try {
    // Get monthly revenue data
    $stmt = $pdo->prepare("
        SELECT 
            MONTH(p.created_at) as month,
            YEAR(p.created_at) as year,
            SUM(p.owner_payout) as revenue,
            COUNT(p.id) as payment_count
        FROM payments p
        WHERE p.owner_id = ? AND p.status = 'successful'
        GROUP BY YEAR(p.created_at), MONTH(p.created_at)
        ORDER BY year DESC, month DESC
        LIMIT 12
    ");
    $stmt->execute([$owner_id]);
    $monthly_revenue = $stmt->fetchAll();

    // Get property performance
    $stmt = $pdo->prepare("
        SELECT 
            prop.title,
            prop.id,
            COUNT(DISTINCT b.id) as total_bookings,
            COUNT(CASE WHEN b.status IN ('approved', 'active', 'completed') THEN 1 END) as confirmed_bookings,
            COALESCE(SUM(CASE WHEN p.status = 'successful' THEN p.owner_payout END), 0) as total_revenue,
            AVG(CASE WHEN b.status IN ('approved', 'active', 'completed') THEN b.total_amount END) as avg_booking_value
        FROM properties prop
        LEFT JOIN bookings b ON prop.id = b.property_id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE prop.owner_id = ?
        GROUP BY prop.id, prop.title
        ORDER BY total_revenue DESC
    ");
    $stmt->execute([$owner_id]);
    $property_performance = $stmt->fetchAll();

    // Get booking trends
    $stmt = $pdo->prepare("
        SELECT 
            DATE(b.created_at) as booking_date,
            COUNT(b.id) as bookings_count
        FROM bookings b
        JOIN properties p ON b.property_id = p.id
        WHERE p.owner_id = ? AND b.created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 30 DAY)
        GROUP BY DATE(b.created_at)
        ORDER BY booking_date DESC
    ");
    $stmt->execute([$owner_id]);
    $booking_trends = $stmt->fetchAll();

    // Get top customers
    $stmt = $pdo->prepare("
        SELECT 
            u.full_name,
            u.email,
            COUNT(b.id) as booking_count,
            COALESCE(SUM(CASE WHEN p.status = 'successful' THEN p.amount END), 0) as total_spent
        FROM users u
        JOIN bookings b ON u.id = b.customer_id
        JOIN properties prop ON b.property_id = prop.id
        LEFT JOIN payments p ON b.id = p.booking_id
        WHERE prop.owner_id = ?
        GROUP BY u.id, u.full_name, u.email
        ORDER BY booking_count DESC, total_spent DESC
        LIMIT 10
    ");
    $stmt->execute([$owner_id]);
    $top_customers = $stmt->fetchAll();
?>

<div class="analytics-container">
    <div class="section-header" style="margin-bottom: 30px;">
        <h2>Analytics & Performance</h2>
        <p class="text-muted">Comprehensive insights into your property performance and earnings</p>
    </div>

    <!-- Revenue Chart -->
    <div class="chart-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h3 style="margin-bottom: 20px; color: #2d3748;">Monthly Revenue Trend</h3>
        
        <?php if (!empty($monthly_revenue)): ?>
        <div style="position: relative; height: 300px;">
            <canvas id="revenueChart" width="100%" height="300"></canvas>
        </div>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 20px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <div class="text-center">
                <div style="font-size: 1.5rem; font-weight: 700; color: #38a169;">Rs. <?php echo number_format(array_sum(array_column($monthly_revenue, 'revenue')), 2); ?></div>
                <small class="text-muted">Total Revenue (12 months)</small>
            </div>
            <div class="text-center">
                <div style="font-size: 1.5rem; font-weight: 700; color: #3182ce;"><?php echo array_sum(array_column($monthly_revenue, 'payment_count')); ?></div>
                <small class="text-muted">Total Payments</small>
            </div>
            <div class="text-center">
                <div style="font-size: 1.5rem; font-weight: 700; color: #805ad5;">Rs. <?php echo !empty($monthly_revenue) ? number_format(array_sum(array_column($monthly_revenue, 'revenue')) / count($monthly_revenue), 2) : '0.00'; ?></div>
                <small class="text-muted">Avg Monthly Revenue</small>
            </div>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #718096;">
            <i class="fas fa-chart-line" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No revenue data available yet</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- Property Performance -->
    <div class="chart-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-bottom: 25px;">
        <h3 style="margin-bottom: 20px; color: #2d3748;">Property Performance</h3>
        
        <?php if (!empty($property_performance)): ?>
        <div class="properties-performance" style="display: grid; gap: 15px;">
            <?php foreach ($property_performance as $property): ?>
            <div class="property-performance-item" style="padding: 20px; background: #f7fafc; border-radius: 8px; border-left: 4px solid #38a169;">
                <div style="display: flex; justify-content: space-between; align-items: start;">
                    <div style="flex: 1;">
                        <h4 style="color: #2d3748; margin-bottom: 10px;"><?php echo htmlspecialchars($property['title']); ?></h4>
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(120px, 1fr)); gap: 15px;">
                            <div>
                                <div style="font-weight: 700; color: #3182ce;"><?php echo $property['total_bookings']; ?></div>
                                <small class="text-muted">Total Bookings</small>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #38a169;"><?php echo $property['confirmed_bookings']; ?></div>
                                <small class="text-muted">Confirmed</small>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #805ad5;">Rs. <?php echo number_format($property['total_revenue'], 2); ?></div>
                                <small class="text-muted">Total Revenue</small>
                            </div>
                            <div>
                                <div style="font-weight: 700; color: #ed8936;">Rs. <?php echo number_format($property['avg_booking_value'] ?? 0, 2); ?></div>
                                <small class="text-muted">Avg Booking Value</small>
                            </div>
                        </div>
                    </div>
                    <div class="performance-score" style="text-align: center;">
                        <?php 
                        $occupancy_rate = $property['total_bookings'] > 0 ? ($property['confirmed_bookings'] / $property['total_bookings']) * 100 : 0;
                        ?>
                        <div style="font-size: 1.5rem; font-weight: 700; color: <?php echo $occupancy_rate > 70 ? '#38a169' : ($occupancy_rate > 40 ? '#ed8936' : '#e53e3e'); ?>">
                            <?php echo number_format($occupancy_rate, 1); ?>%
                        </div>
                        <small class="text-muted">Confirmation Rate</small>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div style="text-align: center; padding: 50px; color: #718096;">
            <i class="fas fa-home" style="font-size: 3rem; margin-bottom: 15px;"></i>
            <p>No properties found</p>
        </div>
        <?php endif; ?>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 25px;">
        <!-- Booking Trends -->
        <div class="chart-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; color: #2d3748;">Booking Trends (30 Days)</h3>
            
            <?php if (!empty($booking_trends)): ?>
            <div style="position: relative; height: 200px;">
                <canvas id="bookingTrendsChart" width="100%" height="200"></canvas>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #718096;">
                <i class="fas fa-chart-area" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>No booking data available</p>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top Customers -->
        <div class="chart-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h3 style="margin-bottom: 20px; color: #2d3748;">Top Customers</h3>
            
            <?php if (!empty($top_customers)): ?>
            <div class="customers-list" style="max-height: 300px; overflow-y: auto;">
                <?php foreach ($top_customers as $index => $customer): ?>
                <div class="customer-item" style="display: flex; align-items: center; padding: 12px; margin-bottom: 10px; background: #f7fafc; border-radius: 8px;">
                    <div style="width: 30px; height: 30px; background: #3182ce; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin-right: 15px; font-weight: 700;">
                        <?php echo $index + 1; ?>
                    </div>
                    <div style="flex: 1;">
                        <div style="font-weight: 600; color: #2d3748;"><?php echo htmlspecialchars($customer['full_name']); ?></div>
                        <small class="text-muted"><?php echo htmlspecialchars($customer['email']); ?></small>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-weight: 700; color: #38a169;"><?php echo $customer['booking_count']; ?> bookings</div>
                        <small class="text-muted">Rs. <?php echo number_format($customer['total_spent'], 2); ?></small>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div style="text-align: center; padding: 30px; color: #718096;">
                <i class="fas fa-users" style="font-size: 2rem; margin-bottom: 10px;"></i>
                <p>No customer data available</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Revenue Chart
<?php if (!empty($monthly_revenue)): ?>
const revenueCtx = document.getElementById('revenueChart').getContext('2d');
const revenueChart = new Chart(revenueCtx, {
    type: 'line',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return '"' . date('M Y', mktime(0, 0, 0, $item['month'], 1, $item['year'])) . '"'; }, array_reverse($monthly_revenue))); ?>],
        datasets: [{
            label: 'Revenue (Rs.)',
            data: [<?php echo implode(',', array_column(array_reverse($monthly_revenue), 'revenue')); ?>],
            borderColor: '#38a169',
            backgroundColor: 'rgba(56, 161, 105, 0.1)',
            borderWidth: 3,
            fill: true,
            tension: 0.4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return 'Rs. ' + value.toLocaleString();
                    }
                }
            }
        },
        plugins: {
            tooltip: {
                callbacks: {
                    label: function(context) {
                        return 'Revenue: Rs. ' + context.parsed.y.toLocaleString();
                    }
                }
            }
        }
    }
});
<?php endif; ?>

// Booking Trends Chart
<?php if (!empty($booking_trends)): ?>
const bookingCtx = document.getElementById('bookingTrendsChart').getContext('2d');
const bookingChart = new Chart(bookingCtx, {
    type: 'bar',
    data: {
        labels: [<?php echo implode(',', array_map(function($item) { return '"' . date('M j', strtotime($item['booking_date'])) . '"'; }, array_reverse($booking_trends))); ?>],
        datasets: [{
            label: 'Bookings',
            data: [<?php echo implode(',', array_column(array_reverse($booking_trends), 'bookings_count')); ?>],
            backgroundColor: 'rgba(49, 130, 206, 0.8)',
            borderColor: '#3182ce',
            borderWidth: 1
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    stepSize: 1
                }
            }
        }
    }
});
<?php endif; ?>
</script>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load analytics: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>