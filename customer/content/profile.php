<?php
require_once '../../includes/config.php';

$customer_id = $_SESSION['customer_id'];

// Get customer information
$query = "SELECT * FROM users WHERE id = ? AND user_type = 'customer'";
$stmt = $pdo->prepare($query);
$stmt->execute([$customer_id]);
$customer = $stmt->fetch();

if (!$customer) {
    echo "<div class='error'>Customer not found.</div>";
    exit;
}
?>

<div class="profile-page">
    <div class="page-header">
        <h2><i class="fas fa-user-cog"></i> My Profile</h2>
        <p>Manage your account information and settings</p>
    </div>

    <div class="profile-content">
        <div class="profile-form-container">
            <form id="profile-form" class="profile-form">
                <div class="form-section">
                    <h3>Personal Information</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="name">Full Name</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($customer['full_name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($customer['email'] ?? ''); ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($customer['phone'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="zip_code">ZIP Code</label>
                            <input type="text" id="zip_code" name="zip_code" value="<?php echo htmlspecialchars($customer['zip_code'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Address Information</h3>
                    
                    <div class="form-group">
                        <label for="address">Address</label>
                        <textarea id="address" name="address" rows="3"><?php echo htmlspecialchars($customer['address'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" id="city" name="city" value="<?php echo htmlspecialchars($customer['city'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label for="state">State/Province</label>
                            <input type="text" id="state" name="state" value="<?php echo htmlspecialchars($customer['state'] ?? ''); ?>">
                        </div>
                    </div>
                </div>

                <div class="form-section">
                    <h3>Account Settings</h3>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" placeholder="Enter current password to change">
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" placeholder="Enter new password (optional)">
                        </div>
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm new password">
                        </div>
                    </div>
                </div>

                <div class="form-actions">
                    <button type="submit" class="btn-primary update-profile-btn">
                        <i class="fas fa-save"></i> Update Profile
                    </button>
                </div>
            </form>
        </div>

        <div class="profile-sidebar">
            <div class="profile-card">
                <div class="profile-avatar">
                    <i class="fas fa-user-circle"></i>
                </div>
                <h3><?php echo htmlspecialchars($customer['full_name'] ?? 'Customer'); ?></h3>
                <p><?php echo htmlspecialchars($customer['email'] ?? ''); ?></p>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <strong id="profile-wishlist-count">-</strong>
                        <span>Saved Properties</span>
                    </div>
                    <div class="stat-item">
                        <strong id="profile-bookings-count">-</strong>
                        <span>Total Bookings</span>
                    </div>
                    <div class="stat-item">
                        <strong><?php echo date('M Y', strtotime($customer['created_at'] ?? 'now')); ?></strong>
                        <span>Member Since</span>
                    </div>
                </div>

                <div class="profile-actions">
                    <button class="btn-danger delete-account-btn" onclick="deleteAccount()" style="background: linear-gradient(135deg, #ffd700 0%, #ffb347 100%) !important; color: #2d3748 !important; border-color: #ffd700 !important;">
                        <i class="fas fa-trash" style="color: #2d3748 !important;"></i> Delete Account
                    </button>
                </div>
            </div>

            <div class="notification-preferences">
                <h4>Notification Preferences</h4>
                <div class="preference-item">
                    <label class="checkbox-label">
                        <input type="checkbox" id="email_notifications" checked>
                        <span class="checkmark"></span>
                        Email Notifications
                    </label>
                </div>
                <div class="preference-item">
                    <label class="checkbox-label">
                        <input type="checkbox" id="sms_notifications" checked>
                        <span class="checkmark"></span>
                        SMS Notifications
                    </label>
                </div>
                <div class="preference-item">
                    <label class="checkbox-label">
                        <input type="checkbox" id="marketing_emails">
                        <span class="checkmark"></span>
                        Marketing Emails
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const profileData = Object.fromEntries(formData.entries());
    
    // Validate passwords match if changing password
    if (profileData.new_password && profileData.new_password !== profileData.confirm_password) {
        showNotification('New passwords do not match', 'error');
        return;
    }
    
    // Update profile
    fetch('api/profile_actions.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: 'update_profile',
            data: profileData
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Profile updated successfully', 'success');
            
            // Clear password fields
            document.getElementById('current_password').value = '';
            document.getElementById('new_password').value = '';
            document.getElementById('confirm_password').value = '';
        } else {
            showNotification(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('An error occurred while updating profile', 'error');
    });
});


function deleteAccount() {
    if (confirm('Are you sure you want to delete your account? This action cannot be undone.')) {
        const reason = prompt('Please tell us why you want to delete your account (optional):');
        
        fetch('api/profile_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'delete_account',
                reason: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showNotification('Account deletion request submitted', 'success');
                setTimeout(() => {
                    window.location.href = '../login.php';
                }, 2000);
            } else {
                showNotification(data.message, 'error');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            showNotification('An error occurred', 'error');
        });
    }
}

// Load profile stats
fetch('api/customer_stats.php')
.then(response => response.json())
.then(data => {
    if (data.success) {
        document.getElementById('profile-wishlist-count').textContent = data.data.wishlist_count || '0';
        document.getElementById('profile-bookings-count').textContent = data.data.total_bookings || '0';
    }
})
.catch(error => {
    console.error('Error loading stats:', error);
});
</script>