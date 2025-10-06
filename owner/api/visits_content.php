<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 1;

try {
    $stmt = $pdo->prepare("
        SELECT 
            pv.id,
            pv.requested_date,
            pv.status,
            pv.customer_notes,
            pv.owner_notes,
            pv.created_at,
            p.title as property_name,
            p.address as property_address,
            u.full_name as customer_name,
            u.email as customer_email,
            u.phone as customer_phone
        FROM property_visits pv
        JOIN properties p ON pv.property_id = p.id
        JOIN users u ON pv.customer_id = u.id
        WHERE p.owner_id = ?
        ORDER BY pv.requested_date DESC
    ");
    $stmt->execute([$owner_id]);
    $visits = $stmt->fetchAll();
?>

<div class="section-header" style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
    <h2>Property Visits</h2>
    <div class="quick-actions">
        <select class="form-input" style="width: 150px;" id="visit-status-filter">
            <option value="">All Visits</option>
            <option value="pending">Pending</option>
            <option value="approved">Approved</option>
            <option value="rejected">Rejected</option>
            <option value="completed">Completed</option>
        </select>
    </div>
</div>

<div class="visits-grid" style="display: grid; gap: 20px;">
    <?php foreach ($visits as $visit): ?>
    <div class="visit-card" data-visit-status="<?php echo $visit['status']; ?>" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); border-left: 4px solid <?php echo $visit['status'] === 'pending' ? '#ed8936' : ($visit['status'] === 'approved' ? '#38a169' : ($visit['status'] === 'completed' ? '#3182ce' : '#e53e3e')); ?>;">
        
        <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
            <div>
                <h3 style="font-size: 1.2rem; color: #2d3748; margin-bottom: 5px;"><?php echo htmlspecialchars($visit['property_name']); ?></h3>
                <span class="status-badge status-<?php echo strtolower($visit['status']); ?>">
                    <?php echo ucfirst($visit['status']); ?>
                </span>
            </div>
            <div class="text-right">
                <div style="font-weight: 600; color: #2d3748;"><?php echo date('M j, Y', strtotime($visit['requested_date'])); ?></div>
                <small class="text-muted"><?php echo date('g:i A', strtotime($visit['requested_date'])); ?></small>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
            <div>
                <strong>Customer Details:</strong><br>
                <span><?php echo htmlspecialchars($visit['customer_name']); ?></span><br>
                <small class="text-muted"><?php echo htmlspecialchars($visit['customer_email']); ?></small><br>
                <small class="text-muted"><?php echo htmlspecialchars($visit['customer_phone']); ?></small>
            </div>
            <div>
                <strong>Property:</strong><br>
                <span><?php echo htmlspecialchars($visit['property_address']); ?></span><br>
                <small class="text-muted">Requested on <?php echo date('M j, Y', strtotime($visit['created_at'])); ?></small>
            </div>
        </div>

        <?php if ($visit['customer_notes']): ?>
        <div style="margin-bottom: 15px; padding: 15px; background: #f7fafc; border-radius: 8px;">
            <strong>Customer Notes:</strong><br>
            <span><?php echo htmlspecialchars($visit['customer_notes']); ?></span>
        </div>
        <?php endif; ?>

        <?php if ($visit['owner_notes']): ?>
        <div style="margin-bottom: 15px; padding: 15px; background: #e6fffa; border-radius: 8px;">
            <strong>Your Notes:</strong><br>
            <span><?php echo htmlspecialchars($visit['owner_notes']); ?></span>
        </div>
        <?php endif; ?>

        <div class="visit-actions" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <?php if ($visit['status'] === 'pending'): ?>
                <button class="btn btn-success visit-action" data-action="approve" data-visit-id="<?php echo $visit['id']; ?>">
                    <i class="fas fa-check"></i> Approve Visit
                </button>
                <button class="btn btn-danger visit-action" data-action="reject" data-visit-id="<?php echo $visit['id']; ?>">
                    <i class="fas fa-times"></i> Reject Visit
                </button>
            <?php elseif ($visit['status'] === 'approved'): ?>
                <button class="btn btn-primary visit-action" data-action="complete" data-visit-id="<?php echo $visit['id']; ?>">
                    <i class="fas fa-flag-checkered"></i> Mark Completed
                </button>
                <button class="btn btn-warning visit-action" data-action="reschedule" data-visit-id="<?php echo $visit['id']; ?>">
                    <i class="fas fa-calendar-alt"></i> Reschedule
                </button>
            <?php endif; ?>
            
            <button class="btn btn-secondary visit-action" data-action="contact" data-visit-id="<?php echo $visit['id']; ?>">
                <i class="fas fa-phone"></i> Contact Customer
            </button>
            
            <button class="btn btn-secondary visit-action" data-action="add-notes" data-visit-id="<?php echo $visit['id']; ?>">
                <i class="fas fa-sticky-note"></i> Add Notes
            </button>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php if (empty($visits)): ?>
<div style="text-align: center; padding: 50px;">
    <i class="fas fa-eye" style="font-size: 4rem; color: #cbd5e0; margin-bottom: 20px;"></i>
    <h3 style="color: #718096; margin-bottom: 20px;">No Visit Requests Yet</h3>
    <p class="text-muted" style="margin-bottom: 30px;">When customers request to visit your properties, they'll appear here for approval.</p>
    <a href="#properties" class="btn btn-primary" onclick="window.ownerDashboard?.navigateToSection('properties')">
        <i class="fas fa-home"></i> View My Properties
    </a>
</div>
<?php endif; ?>

<!-- Add Notes Modal (Simple) -->
<div id="notes-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: white; padding: 30px; border-radius: 12px; width: 90%; max-width: 500px;">
        <h3 style="margin-bottom: 20px;">Add Notes</h3>
        <textarea id="visit-notes" style="width: 100%; height: 100px; padding: 10px; border: 2px solid #e2e8f0; border-radius: 8px; margin-bottom: 20px;" placeholder="Add your notes about this visit..."></textarea>
        <div style="display: flex; gap: 10px; justify-content: flex-end;">
            <button class="btn btn-secondary" onclick="closeNotesModal()">Cancel</button>
            <button class="btn btn-primary" onclick="saveNotes()">Save Notes</button>
        </div>
    </div>
</div>

<script>
let currentVisitId = null;

// Add filtering functionality
document.getElementById('visit-status-filter').addEventListener('change', filterVisits);

function filterVisits() {
    const statusFilter = document.getElementById('visit-status-filter').value;
    const visitCards = document.querySelectorAll('.visit-card');

    visitCards.forEach(card => {
        const visitStatus = card.getAttribute('data-visit-status');
        
        if (!statusFilter || visitStatus === statusFilter) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Handle add notes action
function showNotesModal(visitId) {
    currentVisitId = visitId;
    document.getElementById('notes-modal').style.display = 'block';
}

function closeNotesModal() {
    document.getElementById('notes-modal').style.display = 'none';
    document.getElementById('visit-notes').value = '';
    currentVisitId = null;
}

function saveNotes() {
    const notes = document.getElementById('visit-notes').value;
    if (notes && currentVisitId) {
        // This would normally send an AJAX request to save notes
        console.log('Saving notes for visit:', currentVisitId, notes);
        closeNotesModal();
    }
}

// Enhanced visit action handling
document.addEventListener('click', function(e) {
    if (e.target.matches('.visit-action[data-action="add-notes"]')) {
        const visitId = e.target.getAttribute('data-visit-id');
        showNotesModal(visitId);
    }
});
</script>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load visits: ' . htmlspecialchars($e->getMessage()) . '</p>
    </div>';
}
?>