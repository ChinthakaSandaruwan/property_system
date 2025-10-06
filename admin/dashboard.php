<?php
// Start session and include config
require_once '../includes/config.php';

// Check if user is logged in and is admin (add your authentication logic here)
// For now, we'll just start the session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// You can add authentication check here
// if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
//     header("Location: ../login.php");
//     exit();
// }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Rental System</title>
    <link rel="stylesheet" href="css/dashboard.css">
    <!-- Font Awesome with fallbacks -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css" 
          onerror="this.onerror=null; this.href='https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'">
    <!-- Ultimate fallback for Font Awesome -->
    <script>
        // Check if Font Awesome loaded, if not, add local fallback styles for essential icons
        document.addEventListener('DOMContentLoaded', function() {
            const testElement = document.createElement('i');
            testElement.className = 'fas fa-home';
            testElement.style.visibility = 'hidden';
            testElement.style.position = 'absolute';
            document.body.appendChild(testElement);
            
            const computedStyle = window.getComputedStyle(testElement);
            if (computedStyle.fontFamily.indexOf('Font Awesome') === -1) {
                console.log('Font Awesome not loaded, using fallback');
                // Add basic fallback styles
                const style = document.createElement('style');
                style.textContent = `
                    .fas, .fa { font-family: Arial, sans-serif; }
                    .fa-home::before { content: '🏠'; }
                    .fa-cog::before { content: '⚙️'; }
                    .fa-users::before { content: '👥'; }
                    .fa-credit-card::before { content: '💳'; }
                    .fa-chart-bar::before { content: '📊'; }
                    .fa-calendar-check::before { content: '📅'; }
                    .fa-tachometer-alt::before { content: '📈'; }
                    .fa-sign-out-alt::before { content: '🚪'; }
                    .fa-bars::before { content: '☰'; }
                    .fa-user-circle::before { content: '👤'; }
                    .fa-spinner::before { content: '⏳'; }
                    .fa-building::before { content: '🏢'; }
                    .fa-dollar-sign::before { content: '$'; }
                    .fa-eye::before { content: '👁️'; }
                    .fa-eye-slash::before { content: '🙈'; }
                    .fa-save::before { content: '💾'; }
                    .fa-plug::before { content: '🔌'; }
                    .fa-exclamation-triangle::before { content: '⚠️'; }
                `;
                document.head.appendChild(style);
            }
            document.body.removeChild(testElement);
        });
    </script>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <h2><i class="fas fa-building"></i> Rental Admin</h2>
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
                        <i class="fas fa-home"></i>
                        <span class="nav-text">Properties</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#bookings" data-section="bookings" class="nav-link">
                        <i class="fas fa-calendar-check"></i>
                        <span class="nav-text">Bookings</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#users" data-section="users" class="nav-link">
                        <i class="fas fa-users"></i>
                        <span class="nav-text">Users</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#payments" data-section="payments" class="nav-link">
                        <i class="fas fa-credit-card"></i>
                        <span class="nav-text">Payments</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#reports" data-section="reports" class="nav-link">
                        <i class="fas fa-chart-bar"></i>
                        <span class="nav-text">Reports</span>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="#settings" data-section="settings" class="nav-link">
                        <i class="fas fa-cog"></i>
                        <span class="nav-text">Settings</span>
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
                    <div class="user-profile">
                        <i class="fas fa-user-circle"></i>
                        <span>Admin User</span>
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
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-properties">-</h3>
                            <p>Total Properties</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-bookings">-</h3>
                            <p>Active Bookings</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="total-users">-</h3>
                            <p>Total Users</p>
                        </div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon">
                            <i class="fas fa-dollar-sign"></i>
                        </div>
                        <div class="stat-content">
                            <h3 id="monthly-revenue">-</h3>
                            <p>Monthly Revenue</p>
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
                        <h3>Property Status</h3>
                        <div class="widget-content" id="property-status">
                            <!-- Property status will be loaded here via AJAX -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Other content sections will be dynamically loaded -->
            <div class="content-section" id="properties-content" style="display: none;">
                <!-- Properties content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="bookings-content" style="display: none;">
                <!-- Bookings content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="users-content" style="display: none;">
                <!-- Users content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="payments-content" style="display: none;">
                <!-- Payments content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="reports-content" style="display: none;">
                <!-- Reports content will be loaded via AJAX -->
            </div>

            <div class="content-section" id="settings-content" style="display: none;">
                <!-- Settings content will be loaded via AJAX -->
            </div>
        </main>
    </div>

    <!-- Scripts -->
    <!-- <script src="js/dashboard.js"></script> -->
    <script src="js/dashboard-simple.js"></script>
</body>
</html>