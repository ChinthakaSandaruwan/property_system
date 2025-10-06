<?php
require_once '../../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer visits
$query = "SELECT pv.*, p.title as property_title, 
                 CONCAT(p.address, ', ', p.city) as location, 
                 p.images, p.rent_amount as price,
                 p.property_type, p.address, p.bedrooms, p.bathrooms,
                 u.full_name as owner_name, u.phone as owner_phone, u.email as owner_email
          FROM property_visits pv
          JOIN properties p ON pv.property_id = p.id
          JOIN users u ON p.owner_id = u.id
          WHERE pv.customer_id = ?
          ORDER BY pv.requested_date DESC";

$stmt = $pdo->prepare($query);
$stmt->execute([$customer_id]);
$visits = $stmt->fetchAll();

// Group visits by status and date
$upcoming_visits = array_filter($visits, function($v) {
    return in_array($v['status'], ['pending', 'approved']) && strtotime($v['requested_date']) > time();
});
$past_visits = array_filter($visits, function($v) {
    return $v['status'] === 'completed' || strtotime($v['requested_date']) <= time();
});
$cancelled_visits = array_filter($visits, function($v) { return $v['status'] === 'rejected'; });
?>

<div class="my-visits">
    <div class="page-header">
        <h2><i class="fas fa-calendar-alt"></i> My Visits</h2>
        <p>Manage your property visit appointments</p>
        <div class="header-stats">
            <span class="stat-item">
                <strong><?php echo count($upcoming_visits); ?></strong> Upcoming
            </span>
            <span class="stat-item">
                <strong><?php echo count($past_visits); ?></strong> Past
            </span>
            <span class="stat-item">
                <strong><?php echo count($visits); ?></strong> Total
            </span>
        </div>
    </div>

    <!-- Visit Filters -->
    <div class="visit-filters">
        <button class="filter-btn active" onclick="filterVisits('all')">All Visits</button>
        <button class="filter-btn" onclick="filterVisits('upcoming')">Upcoming (<?php echo count($upcoming_visits); ?>)</button>
        <button class="filter-btn" onclick="filterVisits('past')">Past (<?php echo count($past_visits); ?>)</button>
        <button class="filter-btn" onclick="filterVisits('cancelled')">Cancelled (<?php echo count($cancelled_visits); ?>)</button>
    </div>

    <?php if (!empty($visits)): ?>
        <div class="visits-list">
            <?php foreach ($visits as $visit): ?>
                <?php 
                $is_upcoming = in_array($visit['status'], ['pending', 'approved']) && strtotime($visit['requested_date']) > time();
                $is_past = $visit['status'] === 'completed' || strtotime($visit['requested_date']) <= time();
                $is_cancelled = $visit['status'] === 'rejected';
                
                $visit_category = 'past';
                if ($is_upcoming) $visit_category = 'upcoming';
                elseif ($is_cancelled) $visit_category = 'cancelled';
                ?>
                <div class="visit-card" data-category="<?php echo $visit_category; ?>">
                    <div class="visit-image">
                        <?php if (!empty($visit['images'])): ?>
                            <?php $images = json_decode($visit['images'], true); ?>
                            <img src="../uploads/<?php echo htmlspecialchars($images[0]); ?>" alt="Property Image">
                        <?php else: ?>
                            <img src="../assets/images/no-image.jpg" alt="No Image">
                        <?php endif; ?>
                        
                        <div class="visit-status">
                            <span class="status-badge status-<?php echo $visit['status']; ?>">
                                <?php echo ucfirst($visit['status']); ?>
                            </span>
                        </div>
                        
                        <?php if ($is_upcoming): ?>
                            <div class="visit-countdown">
                                <i class="fas fa-clock"></i>
                                <span class="countdown-text" data-datetime="<?php echo $visit['requested_date']; ?>"></span>
                            </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="visit-details">
                        <div class="visit-header">
                            <h3><?php echo htmlspecialchars($visit['property_title']); ?></h3>
                            <div class="visit-id">#<?php echo $visit['id']; ?></div>
                        </div>
                        
                        <div class="visit-info">
                            <div class="info-row">
                                <i class="fas fa-map-marker-alt"></i>
                                <span><?php echo htmlspecialchars($visit['location']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-home"></i>
                                <span><?php echo ucfirst($visit['property_type']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-calendar"></i>
                                <span><?php echo date('l, M j, Y g:i A', strtotime($visit['requested_date'])); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-money-bill-wave"></i>
                                <span>LKR <?php echo number_format($visit['price']); ?>/month</span>
                            </div>
                            <?php if ($visit['customer_notes']): ?>
                                <div class="info-row">
                                    <i class="fas fa-sticky-note"></i>
                                    <span><?php echo htmlspecialchars($visit['customer_notes']); ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="owner-info">
                            <h4>Owner Contact</h4>
                            <div class="info-row">
                                <i class="fas fa-user"></i>
                                <span><?php echo htmlspecialchars($visit['owner_name']); ?></span>
                            </div>
                            <div class="info-row">
                                <i class="fas fa-phone"></i>
                                <span><?php echo htmlspecialchars($visit['owner_phone']); ?></span>
                            </div>
                        </div>
                        
                        <div class="visit-actions">
                            <?php if ($is_upcoming): ?>
                                <button class="btn-primary" onclick="rescheduleVisit(<?php echo $visit['id']; ?>)">
                                    <i class="fas fa-edit"></i> Reschedule
                                </button>
                                <button class="btn-danger" onclick="cancelVisit(<?php echo $visit['id']; ?>)">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                                <button class="btn-secondary" onclick="getDirections('<?php echo htmlspecialchars($visit['address']); ?>')">
                                    <i class="fas fa-map"></i> Directions
                                </button>
                            <?php elseif ($is_past && $visit['status'] === 'completed'): ?>
                                <button class="btn-primary" onclick="viewProperty(<?php echo $visit['property_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Property
                                </button>
                                <button class="btn-success" onclick="rentProperty(<?php echo $visit['property_id']; ?>)">
                                    <i class="fas fa-key"></i> Rent This Property
                                </button>
                                <?php if ($visit['feedback'] === null): ?>
                                    <button class="btn-secondary" onclick="leaveFeedback(<?php echo $visit['id']; ?>)">
                                        <i class="fas fa-star"></i> Leave Review
                                    </button>
                                <?php endif; ?>
                            <?php else: ?>
                                <button class="btn-primary" onclick="viewProperty(<?php echo $visit['property_id']; ?>)">
                                    <i class="fas fa-eye"></i> View Property
                                </button>
                                <button class="btn-secondary" onclick="bookNewVisit(<?php echo $visit['property_id']; ?>)">
                                    <i class="fas fa-calendar-plus"></i> Book New Visit
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <div class="no-visits">
            <i class="fas fa-calendar-times"></i>
            <h3>No Visits Scheduled</h3>
            <p>You haven't booked any property visits yet. Browse properties and schedule a visit.</p>
            <button class="btn-primary" onclick="loadContent('browse-properties', 'content/browse_properties.php')">
                <i class="fas fa-search"></i> Browse Properties
            </button>
        </div>
    <?php endif; ?>
</div>

<script>
function filterVisits(category) {
    const filterButtons = document.querySelectorAll('.filter-btn');
    const visitCards = document.querySelectorAll('.visit-card');
    
    // Update active filter button
    filterButtons.forEach(btn => btn.classList.remove('active'));
    event.target.classList.add('active');
    
    // Show/hide visit cards
    visitCards.forEach(card => {
        if (category === 'all' || card.dataset.category === category) {
            card.style.display = 'flex';
        } else {
            card.style.display = 'none';
        }
    });
}

function rescheduleVisit(visitId) {
    window.open(`reschedule_visit.php?id=${visitId}`, '_blank');
}

function cancelVisit(visitId) {
    if (!confirm('Are you sure you want to cancel this visit?')) {
        return;
    }
    
    fetch('api/visit_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'cancel',
            visit_id: visitId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadContent('my-visits', 'content/my_visits.php');
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

function getDirections(address) {
    const encodedAddress = encodeURIComponent(address);
    window.open(`https://www.google.com/maps/dir/?api=1&destination=${encodedAddress}`, '_blank');
}

function viewProperty(propertyId) {
    window.open(`property_details.php?id=${propertyId}`, '_blank');
}

function rentProperty(propertyId) {
    window.open(`rent_property.php?property_id=${propertyId}`, '_blank');
}

function leaveFeedback(visitId) {
    window.open(`leave_feedback.php?visit_id=${visitId}`, '_blank');
}

function bookNewVisit(propertyId) {
    window.open(`book_visit.php?property_id=${propertyId}`, '_blank');
}

// Update countdown timers for upcoming visits
function updateCountdowns() {
    const countdownElements = document.querySelectorAll('.countdown-text');
    countdownElements.forEach(element => {
        const targetDateTime = element.dataset.datetime;
        const target = new Date(targetDateTime).getTime();
        const now = new Date().getTime();
        const difference = target - now;
        
        if (difference > 0) {
            const days = Math.floor(difference / (1000 * 60 * 60 * 24));
            const hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
            
            if (days > 0) {
                element.textContent = `${days}d ${hours}h`;
            } else if (hours > 0) {
                element.textContent = `${hours}h ${minutes}m`;
            } else {
                element.textContent = `${minutes}m`;
            }
        } else {
            element.textContent = 'Now';
        }
    });
}

// Update countdowns every minute
setInterval(updateCountdowns, 60000);
updateCountdowns(); // Initial call
</script>