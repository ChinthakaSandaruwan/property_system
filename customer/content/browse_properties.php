<?php
require_once '../../includes/config.php';

// Get search parameters
$search = $_GET['search'] ?? '';
$location = $_GET['location'] ?? '';
$min_price = $_GET['min_price'] ?? '';
$max_price = $_GET['max_price'] ?? '';
$property_type = $_GET['property_type'] ?? '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 12;
$offset = ($page - 1) * $limit;

// Build query
$query = "SELECT p.*, 
          CONCAT(p.address, ', ', p.city) as location,
          p.rent_amount as price,
          CASE WHEN w.id IS NOT NULL THEN 1 ELSE 0 END as in_wishlist
          FROM properties p 
LEFT JOIN wishlists w ON p.id = w.property_id AND w.customer_id = ?
          WHERE p.status = 'approved' AND p.is_available = TRUE";
$params = [$_SESSION['customer_id']];

if (!empty($search)) {
    $query .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if (!empty($location)) {
    $query .= " AND (p.address LIKE ? OR p.city LIKE ?)";
    $params[] = "%$location%";
    $params[] = "%$location%";
}

if (!empty($min_price)) {
    $query .= " AND p.rent_amount >= ?";
    $params[] = $min_price;
}

if (!empty($max_price)) {
    $query .= " AND p.rent_amount <= ?";
    $params[] = $max_price;
}

if (!empty($property_type)) {
    $query .= " AND p.property_type = ?";
    $params[] = $property_type;
}

$query .= " ORDER BY p.created_at DESC LIMIT $limit OFFSET $offset";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$properties = $stmt->fetchAll();

// Get total count for pagination
$countQuery = "SELECT COUNT(*) FROM properties p WHERE p.status = 'approved' AND p.is_available = TRUE";
$countParams = [];

if (!empty($search)) {
    $countQuery .= " AND (p.title LIKE ? OR p.description LIKE ?)";
    $countParams[] = "%$search%";
    $countParams[] = "%$search%";
}

if (!empty($location)) {
    $countQuery .= " AND (p.address LIKE ? OR p.city LIKE ?)";
    $countParams[] = "%$location%";
    $countParams[] = "%$location%";
}

if (!empty($min_price)) {
    $countQuery .= " AND p.rent_amount >= ?";
    $countParams[] = $min_price;
}

if (!empty($max_price)) {
    $countQuery .= " AND p.rent_amount <= ?";
    $countParams[] = $max_price;
}

if (!empty($property_type)) {
    $countQuery .= " AND p.property_type = ?";
    $countParams[] = $property_type;
}

$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalProperties = $countStmt->fetchColumn();
$totalPages = ceil($totalProperties / $limit);
?>

<div class="browse-properties">
    <div class="page-header">
        <h2><i class="fas fa-search"></i> Browse Properties</h2>
        <p>Find your perfect rental property</p>
    </div>

    <!-- Search Filters -->
    <div class="search-filters">
        <div class="filter-row">
            <div class="filter-group">
                <input type="text" id="search-input" placeholder="Search properties..." value="<?php echo htmlspecialchars($search); ?>">
            </div>
            <div class="filter-group">
                <input type="text" id="location-input" placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
            </div>
            <div class="filter-group">
                <select id="property-type">
                    <option value="">All Types</option>
                    <option value="apartment" <?php echo $property_type === 'apartment' ? 'selected' : ''; ?>>Apartment</option>
                    <option value="house" <?php echo $property_type === 'house' ? 'selected' : ''; ?>>House</option>
                    <option value="villa" <?php echo $property_type === 'villa' ? 'selected' : ''; ?>>Villa</option>
                    <option value="studio" <?php echo $property_type === 'studio' ? 'selected' : ''; ?>>Studio</option>
                    <option value="commercial" <?php echo $property_type === 'commercial' ? 'selected' : ''; ?>>Commercial</option>
                </select>
            </div>
        </div>
        <div class="filter-row">
            <div class="filter-group">
                <input type="number" id="min-price" placeholder="Min Price" value="<?php echo htmlspecialchars($min_price); ?>">
            </div>
            <div class="filter-group">
                <input type="number" id="max-price" placeholder="Max Price" value="<?php echo htmlspecialchars($max_price); ?>">
            </div>
            <div class="filter-group">
                <button class="btn btn-primary" onclick="searchProperties()">
                    <i class="fas fa-search"></i> Search
                </button>
                <button class="btn btn-secondary" onclick="clearFilters()">
                    <i class="fas fa-times"></i> Clear
                </button>
            </div>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="results-summary">
        <p>Showing <?php echo count($properties); ?> of <?php echo $totalProperties; ?> properties</p>
    </div>

    <!-- Properties Grid -->
    <div class="properties-grid">
        <?php foreach ($properties as $property): ?>
            <div class="property-card">
                <div class="property-image">
                    <?php if (!empty($property['images'])): ?>
                        <?php 
                        $images = json_decode($property['images'], true);
                        if ($images && is_array($images) && count($images) > 0) {
                            // Check if the image filename already contains the path
                            $imagePath = $images[0];
                            if (strpos($imagePath, 'uploads/properties/') === false) {
                                $imagePath = '../uploads/properties/' . $images[0];
                            } else {
                                $imagePath = '../' . $images[0];
                            }
                        ?>
                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Property Image" onerror="this.src='../assets/images/no-image.svg'">
                        <?php } else { ?>
                            <img src="../assets/images/no-image.svg" alt="No Image">
                        <?php } ?>
                    <?php else: ?>
                        <img src="../assets/images/no-image.svg" alt="No Image">
                    <?php endif; ?>
                    
                    <div class="property-actions">
                        <button class="wishlist-btn <?php echo $property['in_wishlist'] ? 'active' : ''; ?>" 
                                onclick="toggleWishlist(<?php echo $property['id']; ?>)">
                            <i class="<?php echo $property['in_wishlist'] ? 'fas' : 'far'; ?> fa-heart"></i>
                        </button>
                    </div>
                    
                    <div class="property-status">
                        <span class="status-badge status-<?php echo $property['status']; ?>">
                            <?php echo ucfirst($property['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="property-details">
                    <h3><?php echo htmlspecialchars($property['title']); ?></h3>
                    <p class="property-location">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($property['location']); ?>
                    </p>
                    <p class="property-type">
                        <i class="fas fa-home"></i> 
                        <?php echo ucfirst($property['property_type']); ?>
                    </p>
                    <p class="property-price">
                        LKR <?php echo number_format($property['price']); ?>/month
                    </p>
                    
                    <div class="property-features">
                        <?php if ($property['bedrooms']): ?>
                            <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?></span>
                        <?php endif; ?>
                        <?php if ($property['bathrooms']): ?>
                            <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?></span>
                        <?php endif; ?>
                        <?php if ($property['area_sqft']): ?>
                            <span><i class="fas fa-ruler-combined"></i> <?php echo $property['area_sqft']; ?> sq ft</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="property-buttons">
                        <button class="btn btn-primary" onclick="viewProperty(<?php echo $property['id']; ?>)">
                            <i class="fas fa-eye"></i> View Details
                        </button>
                        <button class="btn btn-secondary" onclick="bookVisit(<?php echo $property['id']; ?>)">
                            <i class="fas fa-calendar"></i> Book Visit
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($properties)): ?>
        <div class="no-results">
            <i class="fas fa-search"></i>
            <h3>No Properties Found</h3>
            <p>Try adjusting your search criteria to find more properties.</p>
        </div>
    <?php endif; ?>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
        <div class="pagination">
            <?php if ($page > 1): ?>
                <button onclick="loadPage(<?php echo $page - 1; ?>)">
                    <i class="fas fa-chevron-left"></i> Previous
                </button>
            <?php endif; ?>
            
            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                <button class="<?php echo $i === $page ? 'active' : ''; ?>" onclick="loadPage(<?php echo $i; ?>)">
                    <?php echo $i; ?>
                </button>
            <?php endfor; ?>
            
            <?php if ($page < $totalPages): ?>
                <button onclick="loadPage(<?php echo $page + 1; ?>)">
                    Next <i class="fas fa-chevron-right"></i>
                </button>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<script>
function searchProperties() {
    const search = document.getElementById('search-input').value;
    const location = document.getElementById('location-input').value;
    const minPrice = document.getElementById('min-price').value;
    const maxPrice = document.getElementById('max-price').value;
    const propertyType = document.getElementById('property-type').value;
    
    const params = new URLSearchParams();
    if (search) params.append('search', search);
    if (location) params.append('location', location);
    if (minPrice) params.append('min_price', minPrice);
    if (maxPrice) params.append('max_price', maxPrice);
    if (propertyType) params.append('property_type', propertyType);
    
    loadContent('browse-properties', 'content/browse_properties.php?' + params.toString());
}

function clearFilters() {
    document.getElementById('search-input').value = '';
    document.getElementById('location-input').value = '';
    document.getElementById('min-price').value = '';
    document.getElementById('max-price').value = '';
    document.getElementById('property-type').value = '';
    loadContent('browse-properties', 'content/browse_properties.php');
}

function loadPage(page) {
    const params = new URLSearchParams(window.location.search);
    params.set('page', page);
    loadContent('browse-properties', 'content/browse_properties.php?' + params.toString());
}

function toggleWishlist(propertyId) {
    fetch('api/wishlist_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'toggle',
            property_id: propertyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const wishlistBtn = document.querySelector(`button[onclick="toggleWishlist(${propertyId})"]`);
            const icon = wishlistBtn.querySelector('i');
            
            if (data.added) {
                wishlistBtn.classList.add('active');
                icon.className = 'fas fa-heart';
            } else {
                wishlistBtn.classList.remove('active');
                icon.className = 'far fa-heart';
            }
            
            showNotification(data.message, 'success');
            updateStats();
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred', 'error');
    });
}

function viewProperty(propertyId) {
    window.open(`property_details.php?id=${propertyId}`, '_blank');
}

function bookVisit(propertyId) {
    window.open(`book_visit.php?property_id=${propertyId}`, '_blank');
}
</script>