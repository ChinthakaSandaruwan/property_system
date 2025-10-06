<?php
require_once '../includes/functions.php';

header('Content-Type: application/json');

try {
    // Check if user is logged in
    if (!is_logged_in()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'User not authenticated'
        ]);
        exit;
    }

    $user_type = $_GET['type'] ?? $_SESSION['user_type'];
    
    if (!in_array($user_type, ['admin', 'owner', 'customer'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user type'
        ]);
        exit;
    }

    // Get user information
    $user_name = $_SESSION['user_name'] ?? 'User';
    
    // Generate sidebar HTML based on user type
    $sidebarHtml = generateSidebarHtml($user_type, $user_name);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'html' => $sidebarHtml,
            'user_type' => $user_type,
            'user_name' => $user_name
        ]
    ]);

} catch (Exception $e) {
    error_log('Sidebar API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

function generateSidebarHtml($user_type, $user_name) {
    $baseStyles = '<style>
        .sidebar {
            width: 280px;
            height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: fixed;
            left: 0;
            top: 0;
            color: white;
            overflow-y: auto;
            z-index: 1000;
            transition: transform 0.3s ease;
            box-shadow: 2px 0 10px rgba(0,0,0,0.1);
        }
        
        .sidebar-header {
            padding: 25px 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            background: rgba(0,0,0,0.1);
        }
        
        .sidebar-logo {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
            color: white;
        }
        
        .sidebar-subtitle {
            font-size: 14px;
            opacity: 0.8;
        }
        
        .user-profile {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 10px;
            font-size: 24px;
        }
        
        .user-name {
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .user-role {
            font-size: 12px;
            opacity: 0.8;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .nav-section {
            margin-bottom: 30px;
        }
        
        .nav-section-title {
            padding: 0 20px 10px;
            font-size: 12px;
            text-transform: uppercase;
            letter-spacing: 1px;
            opacity: 0.6;
            font-weight: 600;
        }
        
        .nav-item {
            margin-bottom: 5px;
        }
        
        .nav-link {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            transition: all 0.3s ease;
            position: relative;
            cursor: pointer;
        }
        
        .nav-link:hover,
        .nav-link.active {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }
        
        .nav-link.active::before {
            content: "";
            position: absolute;
            left: 0;
            top: 0;
            height: 100%;
            width: 4px;
            background: #fff;
        }
        
        .nav-icon {
            width: 20px;
            margin-right: 15px;
            text-align: center;
        }
        
        .nav-text {
            flex: 1;
        }
        
        .nav-badge {
            background: #ff4757;
            color: white;
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.sidebar-open {
                transform: translateX(0);
            }
        }
    </style>';

    switch ($user_type) {
        case 'admin':
            return $baseStyles . generateAdminSidebar($user_name);
        case 'owner':
            return $baseStyles . generateOwnerSidebar($user_name);
        case 'customer':
            return $baseStyles . generateCustomerSidebar($user_name);
        default:
            return '<div class="error">Invalid user type</div>';
    }
}

function generateAdminSidebar($user_name) {
    return '
    <div class="sidebar-header">
        <div class="sidebar-logo">🏠 SmartRent</div>
        <div class="sidebar-subtitle">Admin Panel</div>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-user-shield"></i>
        </div>
        <div class="user-name">' . htmlspecialchars($user_name) . '</div>
        <div class="user-role">System Administrator</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-item">
                <a class="nav-link active" data-page="dashboard">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="properties">
                    <i class="nav-icon fas fa-building"></i>
                    <span class="nav-text">Properties</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="users">
                    <i class="nav-icon fas fa-users"></i>
                    <span class="nav-text">Users</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Financial</div>
            <div class="nav-item">
                <a class="nav-link" data-page="payments">
                    <i class="nav-icon fas fa-credit-card"></i>
                    <span class="nav-text">Payments</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">System</div>
            <div class="nav-item">
                <a class="nav-link" data-page="settings">
                    <i class="nav-icon fas fa-cog"></i>
                    <span class="nav-text">Settings</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link" onclick="return confirm(\'Are you sure you want to logout?\')">
                    <i class="nav-icon fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </div>
    </nav>';
}

function generateOwnerSidebar($user_name) {
    return '
    <div class="sidebar-header">
        <div class="sidebar-logo">🏠 SmartRent</div>
        <div class="sidebar-subtitle">Owner Panel</div>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-home"></i>
        </div>
        <div class="user-name">' . htmlspecialchars($user_name) . '</div>
        <div class="user-role">Property Owner</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Main</div>
            <div class="nav-item">
                <a class="nav-link active" data-page="dashboard">
                    <i class="nav-icon fas fa-tachometer-alt"></i>
                    <span class="nav-text">Dashboard</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="properties">
                    <i class="nav-icon fas fa-building"></i>
                    <span class="nav-text">My Properties</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="add-property">
                    <i class="nav-icon fas fa-plus"></i>
                    <span class="nav-text">Add Property</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Management</div>
            <div class="nav-item">
                <a class="nav-link" data-page="visits">
                    <i class="nav-icon fas fa-calendar-check"></i>
                    <span class="nav-text">Visits</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="payments">
                    <i class="nav-icon fas fa-money-bill"></i>
                    <span class="nav-text">Payments</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a class="nav-link" data-page="profile">
                    <i class="nav-icon fas fa-user"></i>
                    <span class="nav-text">Profile</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="../logout.php" class="nav-link" onclick="return confirm(\'Are you sure you want to logout?\')">
                    <i class="nav-icon fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </div>
    </nav>';
}

function generateCustomerSidebar($user_name) {
    return '
    <div class="sidebar-header">
        <div class="sidebar-logo">🏠 SmartRent</div>
        <div class="sidebar-subtitle">Customer Portal</div>
    </div>
    
    <div class="user-profile">
        <div class="user-avatar">
            <i class="fas fa-user"></i>
        </div>
        <div class="user-name">' . htmlspecialchars($user_name) . '</div>
        <div class="user-role">Customer</div>
    </div>
    
    <nav class="sidebar-nav">
        <div class="nav-section">
            <div class="nav-section-title">Browse</div>
            <div class="nav-item">
                <a class="nav-link active" data-page="dashboard">
                    <i class="nav-icon fas fa-search"></i>
                    <span class="nav-text">Find Properties</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="wishlist">
                    <i class="nav-icon fas fa-heart"></i>
                    <span class="nav-text">Wishlist</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">My Activity</div>
            <div class="nav-item">
                <a class="nav-link" data-page="visits">
                    <i class="nav-icon fas fa-calendar-alt"></i>
                    <span class="nav-text">My Visits</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="rentals">
                    <i class="nav-icon fas fa-key"></i>
                    <span class="nav-text">My Rentals</span>
                </a>
            </div>
            <div class="nav-item">
                <a class="nav-link" data-page="payments">
                    <i class="nav-icon fas fa-receipt"></i>
                    <span class="nav-text">Payment History</span>
                </a>
            </div>
        </div>
        
        <div class="nav-section">
            <div class="nav-section-title">Account</div>
            <div class="nav-item">
                <a class="nav-link" data-page="profile">
                    <i class="nav-icon fas fa-user-cog"></i>
                    <span class="nav-text">Profile</span>
                </a>
            </div>
            <div class="nav-item">
                <a href="logout.php" class="nav-link" onclick="return confirm(\'Are you sure you want to logout?\')">
                    <i class="nav-icon fas fa-sign-out-alt"></i>
                    <span class="nav-text">Logout</span>
                </a>
            </div>
        </div>
    </nav>';
}
?>