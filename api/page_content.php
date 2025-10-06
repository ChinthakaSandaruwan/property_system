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

    $page = $_GET['page'] ?? 'dashboard';
    $user_type = $_GET['type'] ?? $_SESSION['user_type'];
    
    if (!in_array($user_type, ['admin', 'owner', 'customer'])) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'error' => 'Invalid user type'
        ]);
        exit;
    }

    // Get page content based on user type and page
    $content = generatePageContent($page, $user_type);
    
    echo json_encode([
        'success' => true,
        'data' => [
            'html' => $content['html'],
            'title' => $content['title'],
            'page' => $page,
            'user_type' => $user_type
        ]
    ]);

} catch (Exception $e) {
    error_log('Page Content API Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

function generatePageContent($page, $user_type) {
    global $pdo;
    
    switch ($page) {
        case 'dashboard':
            return generateDashboardContent($user_type);
        case 'properties':
            return generatePropertiesContent($user_type);
        case 'users':
            return generateUsersContent($user_type);
        case 'payments':
            return generatePaymentsContent($user_type);
        case 'visits':
            return generateVisitsContent($user_type);
        case 'wishlist':
            return generateWishlistContent($user_type);
        case 'rentals':
            return generateRentalsContent($user_type);
        case 'profile':
            return generateProfileContent($user_type);
        case 'add-property':
            return generateAddPropertyContent($user_type);
        case 'settings':
            return generateSettingsContent($user_type);
        default:
            return [
                'title' => 'Page Not Found',
                'html' => '<div class="error-page">
                    <h2>404 - Page Not Found</h2>
                    <p>The requested page could not be found.</p>
                    <a href="#dashboard" class="btn btn-primary">Go to Dashboard</a>
                </div>'
            ];
    }
}

function generateDashboardContent($user_type) {
    global $pdo;
    
    switch ($user_type) {
        case 'admin':
            return generateAdminDashboard();
        case 'owner':
            return generateOwnerDashboard();
        case 'customer':
            return generateCustomerDashboard();
    }
}

function generateAdminDashboard() {
    global $pdo;
    
    // Get statistics
    $stats = [
        'total_properties' => $pdo->query("SELECT COUNT(*) FROM properties")->fetchColumn(),
        'pending_properties' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'pending'")->fetchColumn(),
        'approved_properties' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'approved'")->fetchColumn(),
        'total_owners' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'owner'")->fetchColumn(),
        'total_customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'")->fetchColumn(),
        'total_visits' => $pdo->query("SELECT COUNT(*) FROM property_visits")->fetchColumn(),
    ];

    $html = '
    <div class="dashboard-header">
        <h1>Admin Dashboard</h1>
        <p>Welcome back! Here\'s an overview of your property rental system.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['total_properties']) . '</div>
            <div class="stat-label">Total Properties</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['pending_properties']) . '</div>
            <div class="stat-label">Pending Approval</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['approved_properties']) . '</div>
            <div class="stat-label">Approved Properties</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['total_owners']) . '</div>
            <div class="stat-label">Property Owners</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['total_customers']) . '</div>
            <div class="stat-label">Customers</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['total_visits']) . '</div>
            <div class="stat-label">Property Visits</div>
        </div>
    </div>
    
    <style>
    .dashboard-header {
        margin-bottom: 30px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 30px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    </style>';

    return [
        'title' => 'Admin Dashboard',
        'html' => $html
    ];
}

function generateOwnerDashboard() {
    global $pdo;
    $owner_id = $_SESSION['user_id'];
    
    // Get owner statistics
    $stats = [
        'total_properties' => $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ?"),
        'pending_properties' => $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ? AND status = 'pending'"),
        'approved_properties' => $pdo->prepare("SELECT COUNT(*) FROM properties WHERE owner_id = ? AND status = 'approved'"),
        'total_visits' => $pdo->prepare("SELECT COUNT(*) FROM property_visits pv JOIN properties p ON pv.property_id = p.id WHERE p.owner_id = ?"),
    ];
    
    foreach ($stats as $key => $stmt) {
        $stmt->execute([$owner_id]);
        $stats[$key] = $stmt->fetchColumn() ?: 0;
    }

    $html = '
    <div class="dashboard-header">
        <h1>Owner Dashboard</h1>
        <p>Manage your properties and track performance.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['total_properties']) . '</div>
            <div class="stat-label">Total Properties</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['pending_properties']) . '</div>
            <div class="stat-label">Pending Approval</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['approved_properties']) . '</div>
            <div class="stat-label">Live Properties</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['total_visits']) . '</div>
            <div class="stat-label">Total Visits</div>
        </div>
    </div>
    
    <div class="quick-actions">
        <h3>Quick Actions</h3>
        <div class="action-buttons">
            <a href="#add-property" data-page="add-property" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add New Property
            </a>
            <a href="#properties" data-page="properties" class="btn btn-secondary">
                <i class="fas fa-building"></i> Manage Properties
            </a>
            <a href="#visits" data-page="visits" class="btn btn-info">
                <i class="fas fa-calendar"></i> View Visits
            </a>
        </div>
    </div>
    
    <style>
    .dashboard-header {
        margin-bottom: 30px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 30px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .quick-actions {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    .quick-actions h3 {
        margin-bottom: 20px;
    }
    
    .action-buttons {
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary { background: #667eea; color: white; }
    .btn-secondary { background: #6c757d; color: white; }
    .btn-info { background: #17a2b8; color: white; }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    </style>';

    return [
        'title' => 'Owner Dashboard',
        'html' => $html
    ];
}

function generateCustomerDashboard() {
    global $pdo;
    $customer_id = $_SESSION['user_id'];
    
    // Get customer statistics
    $stats = [
        'saved_properties' => 0,
        'visit_requests' => 0,
        'active_rentals' => 0,
    ];

    try {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlists WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $stats['saved_properties'] = $stmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM property_visits WHERE customer_id = ?");
        $stmt->execute([$customer_id]);
        $stats['visit_requests'] = $stmt->fetchColumn() ?: 0;
        
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM rental_agreements WHERE customer_id = ? AND status = 'active'");
        $stmt->execute([$customer_id]);
        $stats['active_rentals'] = $stmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        // Handle gracefully
    }

    // Get featured properties
    $properties = [];
    try {
        $stmt = $pdo->query("
            SELECT p.*, u.full_name as owner_name 
            FROM properties p 
            JOIN users u ON p.owner_id = u.id 
            WHERE p.status = 'approved' 
            ORDER BY p.created_at DESC 
            LIMIT 6
        ");
        $properties = $stmt->fetchAll();
    } catch (Exception $e) {
        // Handle gracefully
    }

    $html = '
    <div class="dashboard-header">
        <h1>Find Your Perfect Property</h1>
        <p>Welcome back! Discover amazing rental properties.</p>
    </div>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['saved_properties']) . '</div>
            <div class="stat-label">💖 Saved Properties</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['visit_requests']) . '</div>
            <div class="stat-label">Visit Requests</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-number">' . number_format($stats['active_rentals']) . '</div>
            <div class="stat-label">Active Rentals</div>
        </div>
    </div>
    
    <div class="search-section">
        <h3>Search Properties</h3>
        <div class="search-filters">
            <input type="text" id="property-search" placeholder="Search by location, type, or features..." class="search-input">
            <button onclick="searchProperties()" class="btn btn-primary">
                <i class="fas fa-search"></i> Search
            </button>
        </div>
    </div>
    
    <div class="featured-properties">
        <h3>Featured Properties</h3>
        <div class="properties-grid" id="properties-container">';
        
    if (empty($properties)) {
        $html .= '<div class="alert alert-info">No properties available at the moment. Check back soon!</div>';
    } else {
        foreach ($properties as $property) {
            $images = json_decode($property['images'] ?? '[]', true);
            $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
            
            $html .= '
            <div class="property-card">
                <img src="../uploads/properties/' . htmlspecialchars($first_image) . '" alt="' . htmlspecialchars($property['title']) . '" class="property-image" onerror="this.src=\'../images/placeholder.jpg\'">
                <div class="property-content">
                    <h4 class="property-title">' . htmlspecialchars($property['title']) . '</h4>
                    <div class="property-price">' . format_currency($property['rent_amount']) . '/month</div>
                    <div class="property-details">
                        <span>' . $property['bedrooms'] . ' BR</span>
                        <span>' . $property['bathrooms'] . ' BA</span>
                        ' . ($property['area_sqft'] ? '<span>' . number_format($property['area_sqft']) . ' sq ft</span>' : '') . '
                    </div>
                    <div class="property-location">📍 ' . htmlspecialchars($property['city'] . ', ' . $property['state']) . '</div>
                    <button class="btn btn-primary btn-full">View Details</button>
                </div>
            </div>';
        }
    }

    $html .= '
        </div>
    </div>
    
    <style>
    .dashboard-header {
        margin-bottom: 30px;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 20px;
        margin-bottom: 30px;
    }
    
    .stat-card {
        background: white;
        padding: 30px 20px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        text-align: center;
        transition: transform 0.3s ease;
    }
    
    .stat-card:hover {
        transform: translateY(-5px);
    }
    
    .stat-number {
        font-size: 2.5rem;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 10px;
    }
    
    .stat-label {
        font-size: 14px;
        color: #666;
        text-transform: uppercase;
        letter-spacing: 1px;
    }
    
    .search-section, .featured-properties {
        background: white;
        padding: 30px;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        margin-bottom: 30px;
    }
    
    .search-filters {
        display: flex;
        gap: 15px;
        margin-top: 20px;
    }
    
    .search-input {
        flex: 1;
        padding: 12px 15px;
        border: 1px solid #ddd;
        border-radius: 8px;
        font-size: 16px;
    }
    
    .properties-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 20px;
    }
    
    .property-card {
        border: 1px solid #eee;
        border-radius: 10px;
        overflow: hidden;
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }
    
    .property-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.15);
    }
    
    .property-image {
        width: 100%;
        height: 200px;
        object-fit: cover;
    }
    
    .property-content {
        padding: 20px;
    }
    
    .property-title {
        font-size: 18px;
        font-weight: bold;
        margin-bottom: 10px;
    }
    
    .property-price {
        font-size: 20px;
        font-weight: bold;
        color: #667eea;
        margin-bottom: 10px;
    }
    
    .property-details {
        display: flex;
        gap: 10px;
        margin-bottom: 10px;
        font-size: 14px;
        color: #666;
    }
    
    .property-location {
        font-size: 14px;
        color: #666;
        margin-bottom: 15px;
    }
    
    .btn {
        padding: 12px 20px;
        border: none;
        border-radius: 8px;
        cursor: pointer;
        text-decoration: none;
        font-weight: bold;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 8px;
        transition: all 0.3s ease;
    }
    
    .btn-primary {
        background: #667eea;
        color: white;
    }
    
    .btn-full {
        width: 100%;
    }
    
    .btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 15px rgba(0,0,0,0.2);
    }
    
    .alert {
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
    }
    
    .alert-info {
        background: #d1ecf1;
        color: #0c5460;
        border: 1px solid #bee5eb;
    }
    </style>';

    return [
        'title' => 'Customer Dashboard',
        'html' => $html
    ];
}

function generatePropertiesContent($user_type) {
    return [
        'title' => 'Properties',
        'html' => '<div class="loading-placeholder">
            <h2>Properties Management</h2>
            <p>Loading properties data...</p>
        </div>'
    ];
}

function generateUsersContent($user_type) {
    return [
        'title' => 'Users',
        'html' => '<div class="loading-placeholder">
            <h2>Users Management</h2>
            <p>Loading users data...</p>
        </div>'
    ];
}

function generatePaymentsContent($user_type) {
    return [
        'title' => 'Payments',
        'html' => '<div class="loading-placeholder">
            <h2>Payments</h2>
            <p>Loading payment data...</p>
        </div>'
    ];
}

function generateVisitsContent($user_type) {
    return [
        'title' => 'Visits',
        'html' => '<div class="loading-placeholder">
            <h2>Property Visits</h2>
            <p>Loading visits data...</p>
        </div>'
    ];
}

function generateWishlistContent($user_type) {
    return [
        'title' => 'Wishlist',
        'html' => '<div class="loading-placeholder">
            <h2>Your Wishlist</h2>
            <p>Loading saved properties...</p>
        </div>'
    ];
}

function generateRentalsContent($user_type) {
    return [
        'title' => 'My Rentals',
        'html' => '<div class="loading-placeholder">
            <h2>My Rentals</h2>
            <p>Loading rental information...</p>
        </div>'
    ];
}

function generateProfileContent($user_type) {
    return [
        'title' => 'Profile',
        'html' => '<div class="loading-placeholder">
            <h2>Profile Settings</h2>
            <p>Loading profile information...</p>
        </div>'
    ];
}

function generateAddPropertyContent($user_type) {
    if ($user_type !== 'owner') {
        return [
            'title' => 'Access Denied',
            'html' => '<div class="error-page">
                <h2>Access Denied</h2>
                <p>You do not have permission to access this page.</p>
            </div>'
        ];
    }
    
    return [
        'title' => 'Add Property',
        'html' => '<div class="loading-placeholder">
            <h2>Add New Property</h2>
            <p>Loading property form...</p>
        </div>'
    ];
}

function generateSettingsContent($user_type) {
    return [
        'title' => 'Settings',
        'html' => '<div class="loading-placeholder">
            <h2>Settings</h2>
            <p>Loading system settings...</p>
        </div>'
    ];
}
?>