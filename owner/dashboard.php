<?php
// Start session and include config
require_once '../includes/config.php';

// Check if user is logged in and is owner
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// You can add authentication check here
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'owner') {
//     header("Location: ../login.php");
//     exit();
// }

// Get owner info (you can get this from session or URL parameter for testing)
$owner_id = $_GET['owner_id'] ?? $_SESSION['user_id'] ?? 1; // Support URL parameter for testing

// Get actual owner info from database
try {
    $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND user_type = 'owner'");
    $stmt->execute([$owner_id]);
    $owner_data = $stmt->fetch();
    
    if ($owner_data) {
        $owner_name = $owner_data['full_name'];
    } else {
        $owner_name = "Property Owner (ID: $owner_id)"; // Fallback name
    }
} catch (Exception $e) {
    $owner_name = "Property Owner (ID: $owner_id)"; // Fallback if database error
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Owner Dashboard - Rental System</title>
    <link rel="stylesheet" href="css/owner-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-home"></i> Owner Panel</h2>
            <div class="toggle-btn" id="toggle-btn">
                <i class="fas fa-bars"></i>
            </div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item active">
                    <a href="#dashboard" data-section="dashboard" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#properties" data-section="properties" class="nav-link">
                        <i class="fas fa-building"></i>
                        <span class="nav-text">My Properties</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#add-property" data-section="add-property" class="nav-link">
                        <i class="fas fa-plus-circle"></i>
                        <span class="nav-text">Add Property</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#bookings" data-section="bookings" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#visits" data-section="visits" class="nav-link">
                        <i class="fas fa-eye"></i>
                        <span class="nav-text">Property Visits</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#payments" data-section="payments" class="nav-link">
                        <i class="fas fa-money-bill-wave"></i>
                        <span class="nav-text">Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#analytics" data-section="analytics" class="nav-link">
                        <i class="fas fa-chart-line"></i>
                        <span class="nav-text">Analytics</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#profile" data-section="profile" class="nav-link">
                        <i class="fas fa-user-cog"></i>
                        <span class="nav-text">Profile</span>
                    </a>
                </li>
            </ul>
        </nav>
        <div class="sidebar-footer">
            <a href="logout.php" class="logout-btn">
                <i class="fas fa-sign-out-alt"></i>
                <span class="nav-text">Logout</span>
            </a>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content" id="main-content">
        <!-- Header -->
        <header class="header">
            <div class="header-content">
                <div class="header-left">
                    <h1 id="page-title">Dashboard</h1>
                </div>
                <div class="header-right">
                    <div class="notifications">
                        <i class="fas fa-bell"></i>
                        <span class="notification-count" id="notification-count">0</span>
                    </div>
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span><?php echo htmlspecialchars($owner_name); ?></span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="content" id="content">
            <!-- Loading Spinner -->
            <div class="loading-spinner" id="loading-spinner" style="display: none;">
                <i class="fas fa-spinner fa-spin"></i>
                <span>Loading...</span>
            </div>

            <!-- Dashboard Content (Default) -->
            <div class="content-section active" id="dashboard-content">
                <div class="welcome-banner">
                    <h2>Welcome back, <?php echo htmlspecialchars($owner_name); ?>!</h2>
                    <p>Manage your properties and track your rental business performance.</p>
                </div>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-properties">-</h3>
                            <p>My Properties</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="active-bookings">-</h3>
                            <p>Active Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-eye"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="pending-visits">-</h3>
                            <p>Pending Visits</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="monthly-earnings">-</h3>
                            <p>Monthly Earnings</p>
                        </div>
                    </div>
                </div>

                <div class="dashboard-widgets">
                    <div class="widget">
                        <h3>Recent Bookings</h3>
                        <div class="widget-content" id="recent-bookings">
                            <!-- Recent bookings will be loaded here via AJAX -->
                        </div>
                    </div>
                    <div class="widget">
                        <h3>Property Performance</h3>
                        <div class="widget-content" id="property-performance">
                            <!-- Property performance will be loaded here via AJAX -->
                        </div>
                    </div>
                    <div class="widget">
                        <h3>Upcoming Visits</h3>
                        <div class="widget-content" id="upcoming-visits">
                            <!-- Upcoming visits will be loaded here via AJAX -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other content sections will be dynamically loaded -->
            <div class="content-section" id="properties-content" style="display: none;">
                <!-- Properties content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="add-property-content" style="display: none;">
                <!-- Add property form will be loaded via AJAX -->
            </div>

            <div class="content-section" id="bookings-content" style="display: none;">
                <!-- Bookings content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="visits-content" style="display: none;">
                <!-- Visits content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="payments-content" style="display: none;">
                <!-- Payments content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="analytics-content" style="display: none;">
                <!-- Analytics content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="profile-content" style="display: none;">
                <!-- Profile content will be loaded via AJAX -->
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Pass owner ID to JavaScript
        window.ownerId = <?php echo $owner_id; ?>;
    </script>
    <script src="js/owner-dashboard.js"></script>
</body>
</html>