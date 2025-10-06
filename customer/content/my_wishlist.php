<?php
require_once '../../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get wishlist properties
$query = "SELECT p.*, w.created_at as added_date,
                 CONCAT(p.address, ', ', p.city) as location,
                 p.rent_amount as price
          FROM properties p 
JOIN wishlists w ON p.id = w.property_id
          WHERE w.customer_id = ? 
          ORDER BY w.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$customer_id]);
$wishlist_properties = $stmt->fetchAll();
?>

<div class="my-wishlist">
    <div class="page-header">
        <h2><i class="fas fa-heart"></i> My Wishlist</h2>
        <p>Properties you've saved for later</p>
        <div class="header-stats">
            <span class="stat-item">
                <strong><?php echo count($wishlist_properties); ?></strong> Properties
            </span>
        </div>
    </div>

    <?php if (!empty($wishlist_properties)): ?>
        <div class="wishlist-actions">
            <button class="btn-secondary" onclick="clearWishlist()">
                <i class="fas fa-trash"></i> Clear All
            </button>
            <button class="btn-primary" onclick="exportWishlist()">
                <i class="fas fa-download"></i> Export List
            </button>
        </div>

        <div class="properties-grid">
            <?php foreach ($wishlist_properties as $property): ?>
                <div class="property-card wishlist-item" data-property-id="<?php echo $property['id']; ?>">
                    <div class="property-image">
                        <?php if (!empty($property['images'])): ?>
                            <?php $images = json_decode($property['images'], true); ?>
                            <?php if (!empty($images) && is_array($images)): ?>
                                <img src="../uploads/properties/<?php echo htmlspecialchars($images[0]); ?>" 
                                     alt="Property Image" 
                                     loading="lazy"
                                     onerror="this.src='../images/placeholder.svg'; this.onerror=null;">
                            <?php else: ?>
                                <div class="no-image-placeholder">
                                    <i class="fas fa-home"></i>
                                    <span>No Image</span>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="fas fa-home"></i>
                                <span>No Image</span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="property-actions">
                            <button class="wishlist-btn active" onclick="removeFromWishlist(<?php echo $property['id']; ?>)">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                        
                        <div class="property-status">
                            <span class="status-badge status-<?php echo $property['status']; ?>">
                                <?php echo ucfirst($property['status']); ?>
                            </span>
                        </div>
                        
                        <div class="added-date">
                            <i class="fas fa-calendar-plus"></i>
                            Added <?php echo date('M j, Y', strtotime($property['added_date'])); ?>
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
                            <button class="btn-primary" onclick="viewProperty(<?php echo $property['id']; ?>)">
                                <i class="fas fa-eye"></i> View Details
                            </button>
                            <button class="btn-secondary" onclick="bookVisit(<?php echo $property['id']; ?>)">
                                <i class="fas fa-calendar"></i> Book Visit
                            </button>
                            <?php if ($property['status'] === 'approved' && $property['is_available']): ?>
                                <button class="btn-success" onclick="rentProperty(<?php echo $property['id']; ?>)">
                                    <i class="fas fa-key"></i> Rent Now
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="empty-wishlist">
            <i class="fas fa-heart"></i>
            <h3>Your Wishlist is Empty</h3>
            <p>Start browsing properties and save your favorites here.</p>
            <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">
                <i class="fas fa-search"></i> Browse Properties
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
function removeFromWishlist(propertyId) {
    if (!confirm('Remove this property from your wishlist?')) {
        return;
    }
    
    fetch('api/wishlist_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'remove',
            property_id: propertyId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Remove the property card from the display
            const propertyCard = document.querySelector(`.property-card[data-property-id="${propertyId}"]`);
            propertyCard.style.opacity = '0.5';
            setTimeout(() => {
                propertyCard.remove();
                
                // Check if wishlist is now empty
                const remainingItems = document.querySelectorAll('.property-card');
                if (remainingItems.length === 0) {
                    loadContent('wishlist', 'content/my_wishlist.php');
                }
            }, 300);
            
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

function clearWishlist() {
    if (!confirm('Are you sure you want to clear your entire wishlist? This action cannot be undone.')) {
        return;
    }
    
    fetch('api/wishlist_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'clear'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadContent('wishlist', 'content/my_wishlist.php');
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

function exportWishlist() {
    window.open('api/export_wishlist.php', '_blank');
}

function viewProperty(propertyId) {
    window.open(`../property_details.php?id=${propertyId}`, '_blank');
}

function bookVisit(propertyId) {
    window.open(`book_visit.php?property_id=${propertyId}`, '_blank');
}

function rentProperty(propertyId) {
    window.open(`rent_property.php?property_id=${propertyId}`, '_blank');
}
</script>