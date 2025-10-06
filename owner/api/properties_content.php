<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 1;

try {
    $stmt = $pdo->prepare("
        SELECT 
            id,
            title,
            property_type,
            rent_amount,
            status,
            created_at,
            address,
            bedrooms,
            bathrooms,
            area_sqft,
            is_available,
            images
        FROM properties
        WHERE owner_id = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$owner_id]);
    $properties = $stmt->fetchAll();
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>My Properties</h2>
</div>

<div class="properties-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(350px, 1fr)); gap: 20px;">
    <?php foreach ($properties as $property): ?>
    <div class="property-card">
        <?php
        // Get first image from JSON if available
        $images = json_decode($property['images'] ?? '[]', true);
        if (!empty($images)) {
            $firstImage = '/rental_system/uploads/properties/' . $images[0];
        } else {
            $firstImage = 'https://via.placeholder.com/400x200?text=No+Image';
        }
        ?>
        <img src="<?php echo htmlspecialchars($firstImage); ?>" alt="<?php echo htmlspecialchars($property['title']); ?>" class="property-image">
        
        <div class="property-content">
            <div class="property-title"><?php echo htmlspecialchars($property['title']); ?></div>
            <div class="property-price">Rs. <?php echo number_format($property['rent_amount'], 2); ?>/month</div>
            
            <div class="property-details">
                <div style="margin-bottom: 10px;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($property['address']); ?>
                </div>
                <div style="display: flex; gap: 15px; margin-bottom: 10px;">
                    <span><i class="fas fa-bed"></i> <?php echo $property['bedrooms']; ?> bed</span>
                    <span><i class="fas fa-bath"></i> <?php echo $property['bathrooms']; ?> bath</span>
                    <?php if ($property['area_sqft']): ?>
                    <span><i class="fas fa-expand-arrows-alt"></i> <?php echo $property['area_sqft']; ?> sqft</span>
                    <?php endif; ?>
                </div>
                <div style="margin-bottom: 15px;">
                    <span class="status-badge status-<?php echo strtolower($property['status']); ?>">
                        <?php echo ucfirst($property['status']); ?>
                    </span>
                    <?php if ($property['is_available']): ?>
                        <span class="status-badge status-active">Available</span>
                    <?php else: ?>
                        <span class="status-badge status-pending">Occupied</span>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="property-actions">
                <button class="btn btn-primary property-action" data-action="view" data-property-id="<?php echo $property['id']; ?>">
                    <i class="fas fa-eye"></i> View
                </button>
                <?php if (in_array($property['status'], ['pending', 'rejected', 'approved'])): ?>
                    <button class="btn btn-secondary property-action" data-action="edit" data-property-id="<?php echo $property['id']; ?>">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                <?php endif; ?>
                <?php if ($property['status'] === 'approved'): ?>
                    <?php if ($property['is_available']): ?>
                        <button class="btn btn-success property-action" data-action="mark-unavailable" data-property-id="<?php echo $property['id']; ?>">
                            Mark Occupied
                        </button>
                    <?php else: ?>
                        <button class="btn btn-primary property-action" data-action="mark-available" data-property-id="<?php echo $property['id']; ?>">
                            Mark Available
                        </button>
                    <?php endif; ?>
                <?php endif; ?>
                <?php if (in_array($property['status'], ['rejected', 'pending'])): ?>
                    <button class="btn btn-danger property-action" data-action="delete" data-property-id="<?php echo $property['id']; ?>">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($properties)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-home" style="font-size: 4rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096; margin-bottom: 20px;">No Properties Yet</h3>
    <p class="text-muted">You haven't added any properties to your portfolio yet.</p>
</div>
<?php endif; ?>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load properties: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>