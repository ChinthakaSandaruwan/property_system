<?php
require_once '../../includes/functions.php';

// Authentication check for admin
require_auth('admin');
?>

<div class="user-management-page">
    <div class="page-header">
        <h2><i class="fas fa-users-cog"></i> User Management</h2>
        <p>Review and approve property owner registrations</p>
    </div>

    <!-- Stats Cards -->
    <div class="stats-row" id="user-stats">
        <div class="stat-card pending-stat">
            <div class="stat-icon">
                <i class="fas fa-clock"></i>
            </div>
            <div class="stat-content">
                <h3 id="pending-count">-</h3>
                <p>Pending Approval</p>
            </div>
        </div>
        <div class="stat-card approved-stat">
            <div class="stat-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <div class="stat-content">
                <h3 id="approved-count">-</h3>
                <p>Approved Owners</p>
            </div>
        </div>
        <div class="stat-card rejected-stat">
            <div class="stat-icon">
                <i class="fas fa-times-circle"></i>
            </div>
            <div class="stat-content">
                <h3 id="rejected-count">-</h3>
                <p>Rejected Applications</p>
            </div>
        </div>
        <div class="stat-card total-stat">
            <div class="stat-icon">
                <i class="fas fa-users"></i>
            </div>
            <div class="stat-content">
                <h3 id="total-count">-</h3>
                <p>Total Applications</p>
            </div>
        </div>
    </div>

    <!-- Filter Tabs -->
    <div class="filter-tabs">
        <button class="tab-btn active" data-tab="pending">
            <i class="fas fa-clock"></i> Pending (<span id="pending-tab-count">0</span>)
        </button>
        <button class="tab-btn" data-tab="approved">
            <i class="fas fa-check-circle"></i> Approved (<span id="approved-tab-count">0</span>)
        </button>
        <button class="tab-btn" data-tab="rejected">
            <i class="fas fa-times-circle"></i> Rejected (<span id="rejected-tab-count">0</span>)
        </button>
        <button class="tab-btn" data-tab="all">
            <i class="fas fa-list"></i> All Users
        </button>
    </div>

    <!-- Content Area -->
    <div class="content-area">
        <!-- Pending Owners Tab -->
        <div id="pending-tab" class="tab-content active">
            <div class="section-header">
                <h3><i class="fas fa-clock"></i> Pending Approvals</h3>
                <button class="refresh-btn" onclick="loadPendingOwners()">
                    <i class="fas fa-sync-alt"></i> Refresh
                </button>
            </div>
            <div id="pending-owners-list" class="users-list">
                <div class="loading">Loading pending owners...</div>
            </div>
        </div>

        <!-- Approved Owners Tab -->
        <div id="approved-tab" class="tab-content">
            <div class="section-header">
                <h3><i class="fas fa-check-circle"></i> Approved Owners</h3>
            </div>
            <div id="approved-owners-list" class="users-list">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- Rejected Owners Tab -->
        <div id="rejected-tab" class="tab-content">
            <div class="section-header">
                <h3><i class="fas fa-times-circle"></i> Rejected Applications</h3>
            </div>
            <div id="rejected-owners-list" class="users-list">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>

        <!-- All Users Tab -->
        <div id="all-tab" class="tab-content">
            <div class="section-header">
                <h3><i class="fas fa-list"></i> All Property Owners</h3>
            </div>
            <div id="all-owners-list" class="users-list">
                <!-- Will be populated by JavaScript -->
            </div>
        </div>
    </div>
</div>

<!-- Rejection Modal -->
<div id="rejection-modal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-times-circle"></i> Reject Application</h3>
            <button class="close-modal" onclick="closeRejectionModal()">&times;</button>
        </div>
        <div class="modal-body">
            <p>You are about to reject the application for <strong id="reject-owner-name"></strong>.</p>
            <div class="form-group">
                <label for="rejection-reason">Reason for Rejection *</label>
                <textarea id="rejection-reason" placeholder="Please provide a clear reason for rejection..." rows="4" required></textarea>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeRejectionModal()">Cancel</button>
            <button class="btn btn-danger" onclick="confirmRejection()">
                <i class="fas fa-times"></i> Reject Application
            </button>
        </div>
    </div>
</div>

<style>
.user-management-page {
    padding: 20px;
}

.stats-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.stat-card {
    background: white;
    border-radius: 12px;
    padding: 20px;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
    display: flex;
    align-items: center;
    gap: 15px;
}

.stat-icon {
    width: 50px;
    height: 50px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 20px;
    color: white;
}

.pending-stat .stat-icon { background: linear-gradient(135deg, #ff9500, #ff6b00); }
.approved-stat .stat-icon { background: linear-gradient(135deg, #28a745, #20c997); }
.rejected-stat .stat-icon { background: linear-gradient(135deg, #dc3545, #e74c3c); }
.total-stat .stat-icon { background: linear-gradient(135deg, #667eea, #764ba2); }

.stat-content h3 {
    font-size: 2rem;
    font-weight: 700;
    color: #333;
    margin: 0;
}

.stat-content p {
    color: #666;
    margin: 0;
    font-size: 0.9rem;
}

.filter-tabs {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    border-bottom: 2px solid #e2e8f0;
}

.tab-btn {
    padding: 12px 20px;
    border: none;
    background: none;
    color: #666;
    font-weight: 500;
    cursor: pointer;
    border-radius: 8px 8px 0 0;
    transition: all 0.3s ease;
    position: relative;
}

.tab-btn:hover {
    background: #f8fafc;
    color: #333;
}

.tab-btn.active {
    background: white;
    color: #667eea;
    border-bottom: 3px solid #667eea;
}

.section-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
    padding: 0 5px;
}

.refresh-btn {
    padding: 8px 16px;
    border: 1px solid #ddd;
    background: white;
    color: #666;
    border-radius: 6px;
    cursor: pointer;
    transition: all 0.3s ease;
}

.refresh-btn:hover {
    background: #f8fafc;
    border-color: #667eea;
    color: #667eea;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

.users-list {
    background: white;
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
}

.user-card {
    border-bottom: 1px solid #f1f5f9;
    padding: 20px;
    transition: background 0.3s ease;
}

.user-card:hover {
    background: #f8fafc;
}

.user-card:last-child {
    border-bottom: none;
}

.user-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    margin-bottom: 15px;
}

.user-info h4 {
    margin: 0 0 5px 0;
    color: #333;
    font-size: 1.1rem;
}

.user-info p {
    margin: 2px 0;
    color: #666;
    font-size: 0.9rem;
}

.user-status {
    padding: 4px 12px;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
    text-transform: uppercase;
}

.status-pending { background: #fff3cd; color: #856404; }
.status-approved { background: #d1edff; color: #0c5460; }
.status-rejected { background: #f8d7da; color: #721c24; }

.user-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.btn {
    padding: 8px 16px;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: 500;
    transition: all 0.3s ease;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.btn-success {
    background: #28a745;
    color: white;
}

.btn-success:hover {
    background: #218838;
}

.btn-danger {
    background: #dc3545;
    color: white;
}

.btn-danger:hover {
    background: #c82333;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-secondary:hover {
    background: #545b62;
}

.btn-info {
    background: #17a2b8;
    color: white;
}

.btn-info:hover {
    background: #138496;
}

.loading {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #666;
}

.empty-state i {
    font-size: 3rem;
    color: #ddd;
    margin-bottom: 15px;
}

.modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
    background-color: white;
    margin: 10% auto;
    border-radius: 12px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-header h3 {
    margin: 0;
    color: #333;
}

.close-modal {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #999;
}

.modal-body {
    padding: 20px;
}

.modal-footer {
    padding: 20px;
    border-top: 1px solid #e2e8f0;
    display: flex;
    justify-content: flex-end;
    gap: 10px;
}

.form-group {
    margin-bottom: 15px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #333;
}

.form-group textarea {
    width: 100%;
    padding: 10px;
    border: 1px solid #ddd;
    border-radius: 6px;
    resize: vertical;
}

@media (max-width: 768px) {
    .user-header {
        flex-direction: column;
        gap: 10px;
    }
    
    .user-actions {
        flex-wrap: wrap;
    }
    
    .filter-tabs {
        flex-wrap: wrap;
    }
}
</style>

<script>
let currentRejectingOwner = null;

// Load page data on initialization
document.addEventListener('DOMContentLoaded', function() {
    loadStats();
    loadPendingOwners();
    setupTabs();
});

function setupTabs() {
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const tabName = this.getAttribute('data-tab');
            switchTab(tabName);
        });
    });
}

function switchTab(tabName) {
    // Update active tab button
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    document.querySelector(`[data-tab="${tabName}"]`).classList.add('active');
    
    // Update active content
    document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
    });
    document.getElementById(`${tabName}-tab`).classList.add('active');
    
    // Load content for tab
    switch(tabName) {
        case 'pending':
            loadPendingOwners();
            break;
        case 'approved':
        case 'rejected':
        case 'all':
            loadAllOwners();
            break;
    }
}

function loadStats() {
    fetch('api/user_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_stats'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            document.getElementById('pending-count').textContent = data.data.pending;
            document.getElementById('approved-count').textContent = data.data.approved;
            document.getElementById('rejected-count').textContent = data.data.rejected;
            document.getElementById('total-count').textContent = data.data.total;
            
            document.getElementById('pending-tab-count').textContent = data.data.pending;
            document.getElementById('approved-tab-count').textContent = data.data.approved;
            document.getElementById('rejected-tab-count').textContent = data.data.rejected;
        }
    })
    .catch(error => {
        console.error('Error loading stats:', error);
    });
}

function loadPendingOwners() {
    const container = document.getElementById('pending-owners-list');
    container.innerHTML = '<div class="loading">Loading pending owners...</div>';
    
    fetch('api/user_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_pending_owners'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if (data.data.length === 0) {
                container.innerHTML = `
                    <div class="empty-state">
                        <i class="fas fa-check-circle"></i>
                        <h4>No Pending Approvals</h4>
                        <p>All property owner applications have been reviewed.</p>
                    </div>
                `;
            } else {
                container.innerHTML = data.data.map(owner => createOwnerCard(owner, 'pending')).join('');
            }
        } else {
            container.innerHTML = `<div class="empty-state">Error: ${data.message}</div>`;
        }
    })
    .catch(error => {
        console.error('Error loading pending owners:', error);
        container.innerHTML = '<div class="empty-state">Error loading data</div>';
    });
}

function loadAllOwners() {
    const containers = {
        'approved': document.getElementById('approved-owners-list'),
        'rejected': document.getElementById('rejected-owners-list'),
        'all': document.getElementById('all-owners-list')
    };
    
    // Show loading for all containers
    Object.values(containers).forEach(container => {
        container.innerHTML = '<div class="loading">Loading...</div>';
    });
    
    fetch('api/user_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'get_all_owners'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const owners = data.data;
            
            // Populate approved owners
            containers.approved.innerHTML = owners.approved.length === 0 ? 
                '<div class="empty-state"><i class="fas fa-users"></i><h4>No Approved Owners</h4></div>' :
                owners.approved.map(owner => createOwnerCard(owner, 'approved')).join('');
            
            // Populate rejected owners  
            containers.rejected.innerHTML = owners.rejected.length === 0 ? 
                '<div class="empty-state"><i class="fas fa-ban"></i><h4>No Rejected Applications</h4></div>' :
                owners.rejected.map(owner => createOwnerCard(owner, 'rejected')).join('');
                
            // Populate all owners
            const allOwners = [...owners.pending, ...owners.approved, ...owners.rejected, ...owners.other];
            containers.all.innerHTML = allOwners.length === 0 ? 
                '<div class="empty-state"><i class="fas fa-users"></i><h4>No Property Owners</h4></div>' :
                allOwners.map(owner => createOwnerCard(owner, 'all')).join('');
        } else {
            Object.values(containers).forEach(container => {
                container.innerHTML = `<div class="empty-state">Error: ${data.message}</div>`;
            });
        }
    })
    .catch(error => {
        console.error('Error loading all owners:', error);
        Object.values(containers).forEach(container => {
            container.innerHTML = '<div class="empty-state">Error loading data</div>';
        });
    });
}

function createOwnerCard(owner, context) {
    const statusClass = `status-${owner.status}`;
    const statusText = owner.status.charAt(0).toUpperCase() + owner.status.slice(1);
    
    let actions = '';
    if (context === 'pending') {
        actions = `
            <div class="user-actions">
                <button class="btn btn-success" onclick="approveOwner(${owner.id}, '${owner.full_name}')">
                    <i class="fas fa-check"></i> Approve
                </button>
                <button class="btn btn-danger" onclick="openRejectionModal(${owner.id}, '${owner.full_name}')">
                    <i class="fas fa-times"></i> Reject
                </button>
            </div>
        `;
    } else if (context === 'rejected') {
        actions = `
            <div class="user-actions">
                <button class="btn btn-info" onclick="reactivateOwner(${owner.id}, '${owner.full_name}')">
                    <i class="fas fa-redo"></i> Reactivate
                </button>
            </div>
        `;
    }
    
    let approvalInfo = '';
    if (owner.approved_at && owner.approved_by_name) {
        approvalInfo = `
            <p><i class="fas fa-clock"></i> ${owner.status === 'approved' ? 'Approved' : 'Rejected'} on ${owner.approval_date} by ${owner.approved_by_name}</p>
        `;
    }
    
    let rejectionReason = '';
    if (owner.status === 'rejected' && owner.rejection_reason) {
        rejectionReason = `
            <div style="background: #f8d7da; padding: 10px; border-radius: 6px; margin-top: 10px;">
                <strong>Rejection Reason:</strong> ${owner.rejection_reason}
            </div>
        `;
    }
    
    return `
        <div class="user-card">
            <div class="user-header">
                <div class="user-info">
                    <h4>${owner.full_name}</h4>
                    <p><i class="fas fa-envelope"></i> ${owner.email}</p>
                    <p><i class="fas fa-phone"></i> ${owner.phone}</p>
                    <p><i class="fas fa-calendar"></i> Registered: ${owner.registration_date}</p>
                    ${approvalInfo}
                </div>
                <div class="user-status ${statusClass}">${statusText}</div>
            </div>
            ${rejectionReason}
            ${actions}
        </div>
    `;
}

function approveOwner(ownerId, ownerName) {
    if (!confirm(`Are you sure you want to approve ${ownerName}?`)) {
        return;
    }
    
    fetch('api/user_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'approve_owner',
            owner_id: ownerId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`${data.owner_name} has been approved successfully!`, 'success');
            loadStats();
            loadPendingOwners();
            loadAllOwners();
        } else {
            showNotification(`Error: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Error approving owner:', error);
        showNotification('An error occurred while approving the owner.', 'error');
    });
}

function openRejectionModal(ownerId, ownerName) {
    currentRejectingOwner = { id: ownerId, name: ownerName };
    document.getElementById('reject-owner-name').textContent = ownerName;
    document.getElementById('rejection-reason').value = '';
    document.getElementById('rejection-modal').style.display = 'block';
}

function closeRejectionModal() {
    document.getElementById('rejection-modal').style.display = 'none';
    currentRejectingOwner = null;
}

function confirmRejection() {
    const reason = document.getElementById('rejection-reason').value.trim();
    
    if (!reason) {
        alert('Please provide a reason for rejection.');
        return;
    }
    
    if (!currentRejectingOwner) {
        return;
    }
    
    fetch('api/user_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reject_owner',
            owner_id: currentRejectingOwner.id,
            rejection_reason: reason
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`${data.owner_name} has been rejected.`, 'success');
            closeRejectionModal();
            loadStats();
            loadPendingOwners();
            loadAllOwners();
        } else {
            showNotification(`Error: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Error rejecting owner:', error);
        showNotification('An error occurred while rejecting the owner.', 'error');
    });
}

function reactivateOwner(ownerId, ownerName) {
    if (!confirm(`Are you sure you want to reactivate ${ownerName}? This will move them back to pending status.`)) {
        return;
    }
    
    fetch('api/user_management.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reactivate_owner',
            owner_id: ownerId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification(`${ownerName} has been reactivated and moved to pending status.`, 'success');
            loadStats();
            loadPendingOwners();
            loadAllOwners();
        } else {
            showNotification(`Error: ${data.message}`, 'error');
        }
    })
    .catch(error => {
        console.error('Error reactivating owner:', error);
        showNotification('An error occurred while reactivating the owner.', 'error');
    });
}

function showNotification(message, type) {
    // Use existing notification system if available, otherwise use alert
    if (typeof showNotification !== 'undefined') {
        showNotification(message, type);
    } else {
        alert(message);
    }
}

// Close modal when clicking outside
window.onclick = function(event) {
    const modal = document.getElementById('rejection-modal');
    if (event.target === modal) {
        closeRejectionModal();
    }
}
</script>