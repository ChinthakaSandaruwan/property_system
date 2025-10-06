<?php
// Start session and include config
require_once '../includes/config.php';

// Check if user is logged in and is customer
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'customer') {
    header("Location: ../login.php");
    exit();
}

// Get customer info from session (URL parameter for testing only)
$customer_id = $_SESSION['user_id'] ?? $_GET['customer_id'] ?? 1; // Prioritize session, fallback to URL for testing
$_SESSION['customer_id'] = $customer_id; // Set session variable for content files

// Get customer name (prioritize session)
$customer_name = $_SESSION['user_name'] ?? null;

// If not in session, get from database
if (!$customer_name) {
    try {
        $stmt = $pdo->prepare("SELECT full_name FROM users WHERE id = ? AND user_type = 'customer'");
        $stmt->execute([$customer_id]);
        $customer_data = $stmt->fetch();
        
        if ($customer_data) {
            $customer_name = $customer_data['full_name'];
        } else {
            $customer_name = "Customer (ID: $customer_id)"; // Fallback name
        }
    } catch (Exception $e) {
        $customer_name = "Customer (ID: $customer_id)"; // Fallback if database error
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Customer Dashboard - Rental System</title>
    <link rel="stylesheet" href="css/customer-dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-user"></i> Customer Panel</h2>
            <div class="toggle-btn" id="toggle-btn">
                <i class="fas fa-bars"></i>
            </div>
        </div>
        <nav class="sidebar-nav">
            <ul>
                <li class="nav-item">
                    <a href="../index.php" target="_blank" class="external-link">
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Home</span>
                    </a>
                </li>
                <li class="nav-item active">
                    <a href="#dashboard" data-section="dashboard" class="nav-link">
                        <i class="fas fa-tachometer-alt"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#browse-properties" data-section="browse-properties" class="nav-link">
                        <i class="fas fa-search"></i>
                        <span class="nav-text">Browse Properties</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#wishlist" data-section="wishlist" class="nav-link">
                        <i class="fas fa-heart"></i>
                        <span class="nav-text">My Wishlist</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#my-bookings" data-section="my-bookings" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">My Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#my-visits" data-section="my-visits" class="nav-link">
                        <i class="fas fa-eye"></i>
                        <span class="nav-text">Property Visits</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#my-payments" data-section="my-payments" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span class="nav-text">My Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#messages" data-section="messages" class="nav-link">
                        <i class="fas fa-envelope"></i>
                        <span class="nav-text">Messages</span>
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
                        <span><?php echo htmlspecialchars($customer_name); ?></span>
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
                <?php include 'content/dashboard_home.php'; ?>
            </div>

            <!-- Other content sections will be dynamically loaded -->
            <div class="content-section" id="browse-properties-content" style="display: none;">
                <!-- Browse properties content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="wishlist-content" style="display: none;">
                <!-- Wishlist content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="my-bookings-content" style="display: none;">
                <!-- My bookings content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="my-visits-content" style="display: none;">
                <!-- My visits content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="my-payments-content" style="display: none;">
                <!-- My payments content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="messages-content" style="display: none;">
                <!-- Messages content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="profile-content" style="display: none;">
                <!-- Profile content will be loaded via AJAX -->
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <script>
        // Pass customer ID to JavaScript
        window.customerId = <?php echo $customer_id; ?>;
    </script>
    <script src="js/customer-dashboard.js"></script>
</body>
</html>