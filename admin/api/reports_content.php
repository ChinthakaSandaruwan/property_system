<?php
// Include database configuration
require_once '../../includes/config.php';

try {
    // Get various statistics for reports
    
    // Monthly revenue data
    $stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(created_at, '%Y-%m') as month,
            SUM(amount) as revenue
        FROM payments 
        WHERE status = 'successful'
        AND created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 12 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY month DESC
        LIMIT 12
    ");
    $stmt->execute();
    $monthlyRevenue = $stmt->fetchAll();

    // Property type distribution
    $stmt = $pdo->prepare("
        SELECT 
            property_type as type,
            COUNT(*) as count
        FROM properties 
        WHERE status = 'approved'
        GROUP BY property_type
    ");
    $stmt->execute();
    $propertyTypes = $stmt->fetchAll();

    // Booking status distribution
    $stmt = $pdo->prepare("
        SELECT 
            status,
            COUNT(*) as count
        FROM bookings
        GROUP BY status
    ");
    $stmt->execute();
    $bookingStats = $stmt->fetchAll();

    // Top performing properties
    $stmt = $pdo->prepare("
        SELECT 
            p.title as name,
            p.property_type as type,
            COUNT(b.id) as booking_count,
            COALESCE(SUM(b.total_amount), 0) as total_revenue
        FROM properties p
        LEFT JOIN bookings b ON p.id = b.property_id AND b.status IN ('active', 'completed')
        WHERE p.status = 'approved'
        GROUP BY p.id, p.title, p.property_type
        ORDER BY total_revenue DESC
        LIMIT 10
    ");
    $stmt->execute();
    $topProperties = $stmt->fetchAll();

?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>Reports & Analytics</h2>
</div>

<div class="stats-grid" style="margin-bottom: 30px;">
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo count($monthlyRevenue); ?></h3>
            <p>Months of Data</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-home"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo !empty($propertyTypes) ? array_sum(array_column($propertyTypes, 'count')) : 0; ?></h3>
            <p>Active Properties</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-calendar"></i>
        </div>
        <div class="stat-content">
            <h3><?php echo !empty($bookingStats) ? array_sum(array_column($bookingStats, 'count')) : 0; ?></h3>
            <p>Total Bookings</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-content">
            <h3>Rs. <?php echo number_format(!empty($monthlyRevenue) ? array_sum(array_column($monthlyRevenue, 'revenue')) : 0, 2); ?></h3>
            <p>Total Revenue</p>
        </div>
    </div>
</div>

<div class="dashboard-widgets">
    <!-- Monthly Revenue Chart -->
    <div class="widget">
        <h3>Monthly Revenue Trend</h3>
        <div class="widget-content">
            <div style="height: 300px; display: flex; align-items: end; justify-content: space-around; border-bottom: 1px solid #e2e8f0; padding: 20px 0;">
                <?php if (!empty($monthlyRevenue)): ?>
                    <?php 
                    $revenues = array_column($monthlyRevenue, 'revenue');
                    $maxRevenue = !empty($revenues) ? max($revenues) : 1;
                    foreach (array_reverse($monthlyRevenue) as $data): 
                        $height = ($data['revenue'] / $maxRevenue) * 250;
                    ?>
                    <div style="display: flex; flex-direction: column; align-items: center;">
                        <div style="width: 30px; background: linear-gradient(135deg, #667eea, #764ba2); height: <?php echo $height; ?>px; margin-bottom: 10px; border-radius: 4px 4px 0 0;"></div>
                        <small style="font-size: 0.75rem; color: #718096;"><?php echo date('M', strtotime($data['month'].'-01')); ?></small>
                        <small style="font-size: 0.7rem; color: #a0aec0;">Rs. <?php echo number_format($data['revenue'], 0); ?></small>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div style="text-align: center; width: 100%; padding: 50px;">
                        <i class="fas fa-chart-line" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 20px;"></i>
                        <h4 style="color: #718096; margin-bottom: 10px;">No Revenue Data</h4>
                        <p style="color: #a0aec0;">No revenue data available for the chart</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Property Type Distribution -->
    <div class="widget">
        <h3>Property Types</h3>
        <div class="widget-content">
            <?php if (!empty($propertyTypes)): ?>
                <?php foreach ($propertyTypes as $type): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f7fafc;">
                    <div>
                        <strong><?php echo ucfirst($type['type']); ?></strong>
                    </div>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span class="status-badge"><?php echo $type['count']; ?></span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-home" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 10px;"></i>
                    <p style="color: #a0aec0;">No property types found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Booking Status -->
    <div class="widget">
        <h3>Booking Status Distribution</h3>
        <div class="widget-content">
            <?php if (!empty($bookingStats)): ?>
                <?php foreach ($bookingStats as $status): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f7fafc;">
                    <div>
                        <span class="status-badge status-<?php echo strtolower($status['status']); ?>"><?php echo ucfirst($status['status']); ?></span>
                    </div>
                    <div>
                        <strong><?php echo $status['count']; ?></strong>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-calendar-check" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 10px;"></i>
                    <p style="color: #a0aec0;">No booking data found</p>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Top Performing Properties -->
    <div class="widget">
        <h3>Top Performing Properties</h3>
        <div class="widget-content">
            <?php if (!empty($topProperties)): ?>
                <?php foreach ($topProperties as $index => $property): ?>
                <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f7fafc;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="background: <?php echo $index < 3 ? '#ffd700' : '#e2e8f0'; ?>; color: <?php echo $index < 3 ? '#744210' : '#4a5568'; ?>; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;"><?php echo $index + 1; ?></span>
                        <div>
                            <strong><?php echo htmlspecialchars($property['name']); ?></strong><br>
                            <small class="text-muted"><?php echo ucfirst($property['type']); ?></small>
                        </div>
                    </div>
                    <div class="text-right">
                        <strong>Rs. <?php echo number_format($property['total_revenue'], 0); ?></strong><br>
                        <small class="text-muted"><?php echo $property['booking_count']; ?> bookings</small>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div style="text-align: center; padding: 30px;">
                    <i class="fas fa-trophy" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 10px;"></i>
                    <p style="color: #a0aec0;">No property performance data available</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Export Options -->
<div style="margin-top: 30px; text-align: center; padding: 20px; background: white; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
    <h3 style="margin-bottom: 20px;">Export Reports</h3>
    <div style="display: flex; gap: 10px; justify-content: center; flex-wrap: wrap;">
        <button class="btn btn-primary" onclick="exportReport('revenue')">
            <i class="fas fa-download"></i> Revenue Report
        </button>
        <button class="btn btn-secondary" onclick="exportReport('properties')">
            <i class="fas fa-download"></i> Properties Report
        </button>
        <button class="btn btn-secondary" onclick="exportReport('bookings')">
            <i class="fas fa-download"></i> Bookings Report
        </button>
    </div>
</div>

<script>
function exportReport(type) {
    // Simple export functionality - you can enhance this
    alert('Export functionality for ' + type + ' report will be implemented here.');
}
</script>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load reports: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>