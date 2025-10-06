<?php
// Include database configuration
require_once '../../includes/config.php';

try {
    // Get properties with owner details
    $stmt = $pdo->prepare("
        SELECT 
            p.id,
            p.title as name,
            p.property_type as type,
            p.rent_amount as price,
            p.status,
            p.created_at,
            p.is_available,
            u.full_name as owner_name,
            u.email as owner_email,
            p.address,
            p.bedrooms,
            p.bathrooms,
            p.area_sqft
        FROM properties p
        JOIN users u ON p.owner_id = u.id
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $properties = $stmt->fetchAll();
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Properties Management</h2>
    <button class="btn btn-primary" id="add-property-btn">
        <i class="fas fa-plus"></i> Add New Property
    </button>
</div>

<div class="table-container">
    <table class="data-table">
        <thead>
            <tr>
                <th>Property</th>
                <th>Type</th>
                <th>Owner</th>
                <th>Price</th>
                <th>Status</th>
                <th>Availability</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($properties as $property): ?>
            <tr>
                <td>
                    <div>
                        <strong><?php echo htmlspecialchars($property['name']); ?></strong><br>
                        <small class="text-muted"><?php echo htmlspecialchars($property['address']); ?></small>
                        <div style="margin-top: 5px;">
                            <small class="text-info"><?php echo $property['bedrooms']; ?>BR • <?php echo $property['bathrooms']; ?>BA<?php echo $property['area_sqft'] ? ' • ' . $property['area_sqft'] . ' sqft' : ''; ?></small>
                        </div>
                    </div>
                </td>
                <td><?php echo ucfirst(htmlspecialchars($property['type'])); ?></td>
                <td>
                    <div>
                        <?php echo htmlspecialchars($property['owner_name']); ?><br>
                        <small class="text-muted"><?php echo htmlspecialchars($property['owner_email']); ?></small>
                    </div>
                </td>
                <td>Rs. <?php echo number_format($property['price'], 2); ?></td>
                <td>
                    <span class="status-badge status-<?php echo strtolower($property['status']); ?>">
                        <?php echo ucfirst($property['status']); ?>
                    </span>
                </td>
                <td>
                    <span class="status-badge status-<?php echo $property['is_available'] ? 'available' : 'unavailable'; ?>">
                        <?php echo $property['is_available'] ? 'Available' : 'Unavailable'; ?>
                    </span>
                </td>
                <td>
                    <div style="display: flex; gap: 5px; flex-wrap: wrap;">
                        <button class="btn btn-info btn-sm property-action" data-action="view" data-property-id="<?php echo $property['id']; ?>" title="View Details">
                            <i class="fas fa-eye"></i>
                        </button>
                        <button class="btn btn-primary btn-sm property-action" data-action="edit" data-property-id="<?php echo $property['id']; ?>" title="Edit Property">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-danger btn-sm property-action" data-action="delete" data-property-id="<?php echo $property['id']; ?>" title="Delete Property">
                            <i class="fas fa-trash"></i>
                        </button>
                        
                        <!-- Status Actions -->
                        <?php if ($property['status'] === 'pending'): ?>
                        <button class="btn btn-success btn-sm property-action" data-action="approve" data-property-id="<?php echo $property['id']; ?>" title="Approve Property">
                            <i class="fas fa-check"></i>
                        </button>
                        <button class="btn btn-warning btn-sm property-action" data-action="reject" data-property-id="<?php echo $property['id']; ?>" title="Reject Property">
                            <i class="fas fa-times"></i>
                        </button>
                        <?php elseif ($property['status'] === 'approved'): ?>
                        <button class="btn btn-warning btn-sm property-action" data-action="suspend" data-property-id="<?php echo $property['id']; ?>" title="Suspend Property">
                            <i class="fas fa-pause"></i>
                        </button>
                        <?php elseif ($property['status'] === 'rejected'): ?>
                        <button class="btn btn-success btn-sm property-action" data-action="activate" data-property-id="<?php echo $property['id']; ?>" title="Activate Property">
                            <i class="fas fa-play"></i>
                        </button>
                        <?php endif; ?>
                        
                        <!-- Availability Toggle -->
                        <?php if ($property['is_available']): ?>
                        <button class="btn btn-secondary btn-sm property-action" data-action="mark-unavailable" data-property-id="<?php echo $property['id']; ?>" title="Mark Unavailable">
                            <i class="fas fa-eye-slash"></i>
                        </button>
                        <?php else: ?>
                        <button class="btn btn-success btn-sm property-action" data-action="mark-available" data-property-id="<?php echo $property['id']; ?>" title="Mark Available">
                            <i class="fas fa-eye"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($properties)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-home" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096;">No Properties Found</h3>
    <p class="text-muted">There are no properties in the system yet.</p>
</div>
<?php endif; ?>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load properties. Please try again later.</p>
    </div>';
}
?>