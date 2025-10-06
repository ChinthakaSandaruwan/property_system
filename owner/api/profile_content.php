<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';

$owner_id = $_GET['owner_id'] ?? 1;

try {
    // First, check if the user exists at all
    $stmt = $pdo->prepare("SELECT id, full_name, email, user_type FROM users WHERE id = ?");
    $stmt->execute([$owner_id]);
    $user_check = $stmt->fetch();
    
    if (!$user_check) {
        throw new Exception("User with ID $owner_id not found in database. Please check the owner_id parameter.");
    }
    
    if ($user_check['user_type'] !== 'owner') {
        throw new Exception("User with ID $owner_id exists but is not an owner (type: {$user_check['user_type']}). Please use a valid owner ID.");
    }
    
    // Get owner profile information
    $stmt = $pdo->prepare("
        SELECT 
            id,
            full_name,
            email,
            phone,
            address,
            created_at,
            profile_image
        FROM users 
        WHERE id = ? AND user_type = 'owner'
    ");
    $stmt->execute([$owner_id]);
    $owner = $stmt->fetch();

    if (!$owner) {
        throw new Exception('Owner profile could not be loaded');
    }

    // Get owner statistics for profile overview
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT p.id) as total_properties,
            COUNT(DISTINCT b.id) as total_bookings,
            COALESCE(SUM(CASE WHEN pay.status = 'successful' THEN pay.owner_payout END), 0) as total_earnings
        FROM properties p
        LEFT JOIN bookings b ON p.id = b.property_id
        LEFT JOIN payments pay ON b.id = pay.booking_id
        WHERE p.owner_id = ?
    ");
    $stmt->execute([$owner_id]);
    $stats = $stmt->fetch();
?>

<div class="profile-container">
    <div class="section-header" style="margin-bottom: 30px;">
        <h2>Property Owner Profile</h2>
        <p class="text-muted">Manage your property owner account information and business preferences</p>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 2fr; gap: 30px;">
        <!-- Profile Overview -->
        <div class="profile-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); height: fit-content;">
            <div style="text-align: center; margin-bottom: 25px;">
                <div class="profile-avatar" style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #38a169, #4fd1c7); display: flex; align-items: center; justify-content: center; margin: 0 auto 15px; font-size: 2.5rem; color: white; font-weight: 700;">
                    <?php echo strtoupper(substr($owner['full_name'], 0, 1)); ?>
                </div>
                <h3 style="color: #2d3748; margin-bottom: 5px;"><?php echo htmlspecialchars($owner['full_name']); ?></h3>
                <p class="text-muted" style="margin-bottom: 5px;"><?php echo htmlspecialchars($owner['email']); ?></p>
                <small class="text-muted">Member since <?php echo date('M Y', strtotime($owner['created_at'])); ?></small>
            </div>

            <div class="profile-stats" style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                <div class="stat-item" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span>Properties:</span>
                    <strong style="color: #3182ce;"><?php echo $stats['total_properties']; ?></strong>
                </div>
                <div class="stat-item" style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span>Total Bookings:</span>
                    <strong style="color: #38a169;"><?php echo $stats['total_bookings']; ?></strong>
                </div>
                <div class="stat-item" style="display: flex; justify-content: space-between;">
                    <span>Total Earnings:</span>
                    <strong style="color: #805ad5;">Rs. <?php echo number_format($stats['total_earnings'], 2); ?></strong>
                </div>
            </div>

            <div style="margin-top: 25px;">
                <button class="btn btn-primary" style="width: 100%; margin-bottom: 10px;" onclick="toggleEditMode()">
                    <i class="fas fa-edit"></i> Edit Profile
                </button>
                <button class="btn btn-secondary" style="width: 100%;" onclick="showChangePassword()">
                    <i class="fas fa-key"></i> Change Password
                </button>
            </div>
        </div>

        <!-- Profile Details -->
        <div class="profile-details-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <!-- View Mode -->
            <div id="view-mode">
                <h3 style="margin-bottom: 25px; color: #2d3748;">🏠 Property Owner Information</h3>
                
                <div style="display: grid; gap: 20px;">
                    <div class="info-group">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Full Name</label>
                        <div style="padding: 12px; background: #f7fafc; border-radius: 8px; color: #2d3748;">
                            <?php echo htmlspecialchars($owner['full_name']); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Email Address</label>
                        <div style="padding: 12px; background: #f7fafc; border-radius: 8px; color: #2d3748;">
                            <?php echo htmlspecialchars($owner['email']); ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Phone Number</label>
                        <div style="padding: 12px; background: #f7fafc; border-radius: 8px; color: #2d3748;">
                            <?php echo $owner['phone'] ? htmlspecialchars($owner['phone']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>

                    <div class="info-group">
                        <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 5px;">Address</label>
                        <div style="padding: 12px; background: #f7fafc; border-radius: 8px; color: #2d3748;">
                            <?php echo $owner['address'] ? htmlspecialchars($owner['address']) : '<span class="text-muted">Not provided</span>'; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Edit Mode -->
            <div id="edit-mode" style="display: none;">
                <h3 style="margin-bottom: 25px; color: #2d3748;">✏️ Edit Property Owner Profile</h3>
                
                <form id="profile-form" style="display: grid; gap: 20px;">
                    <div class="form-group">
                        <label for="full_name" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Full Name *</label>
                        <input type="text" id="full_name" name="full_name" class="form-input" value="<?php echo htmlspecialchars($owner['full_name']); ?>" required style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px;">
                    </div>

                    <div class="form-group">
                        <label for="email" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Email Address *</label>
                        <input type="email" id="email" name="email" class="form-input" value="<?php echo htmlspecialchars($owner['email']); ?>" required style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px;">
                    </div>

                    <div class="form-group">
                        <label for="phone" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Phone Number</label>
                        <input type="tel" id="phone" name="phone" class="form-input" value="<?php echo htmlspecialchars($owner['phone'] ?? ''); ?>" pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px;" placeholder="0771234567">
                        <small class="text-muted" style="margin-top: 5px; display: block;">Enter Sri Lankan phone number (e.g., 0771234567)</small>
                    </div>

                    <div class="form-group">
                        <label for="address" style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Address</label>
                        <textarea id="address" name="address" class="form-input" rows="3" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px; font-size: 16px; resize: vertical;" placeholder="Your address"><?php echo htmlspecialchars($owner['address'] ?? ''); ?></textarea>
                    </div>

                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                        <button type="button" class="btn btn-secondary" onclick="cancelEdit()">
                            <i class="fas fa-times"></i> Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Business Information Section -->
    <div class="business-info-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 30px;">
        <h3 style="margin-bottom: 25px; color: #2d3748;">📊 Property Business Overview</h3>
        
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
            <div class="business-stat" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;"><?php echo $stats['total_properties']; ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Active Properties</div>
            </div>
            
            <div class="business-stat" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;"><?php echo $stats['total_bookings']; ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Total Bookings</div>
            </div>
            
            <div class="business-stat" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 1.5rem; font-weight: bold; margin-bottom: 5px;">Rs. <?php echo number_format($stats['total_earnings'], 0); ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Total Earnings</div>
            </div>
            
            <div class="business-stat" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%); color: white; padding: 20px; border-radius: 10px; text-align: center;">
                <div style="font-size: 2rem; font-weight: bold; margin-bottom: 5px;"><?php echo date('M Y', strtotime($owner['created_at'])); ?></div>
                <div style="font-size: 0.9rem; opacity: 0.9;">Member Since</div>
            </div>
        </div>
    </div>

    <!-- Preferences Section -->
    <div class="preferences-card" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); margin-top: 30px;">
        <h3 style="margin-bottom: 25px; color: #2d3748;">🔔 Property Owner Notifications</h3>
        
        <div style="display: grid; gap: 15px;">
            <label class="checkbox-label" style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" checked style="margin-right: 12px; transform: scale(1.2);">
                <span>Email notifications for new bookings</span>
            </label>
            <label class="checkbox-label" style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" checked style="margin-right: 12px; transform: scale(1.2);">
                <span>SMS notifications for confirmed bookings</span>
            </label>
            <label class="checkbox-label" style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" style="margin-right: 12px; transform: scale(1.2);">
                <span>Marketing and promotional emails</span>
            </label>
            <label class="checkbox-label" style="display: flex; align-items: center; cursor: pointer;">
                <input type="checkbox" checked style="margin-right: 12px; transform: scale(1.2);">
                <span>Monthly earnings reports</span>
            </label>
        </div>
        
        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
            <button class="btn btn-primary">
                <i class="fas fa-save"></i> Save Preferences
            </button>
        </div>
    </div>
</div>

<!-- Change Password Modal -->
<div id="password-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; align-items: center; justify-content: center;">
    <div style="background: white; border-radius: 12px; padding: 30px; width: 90%; max-width: 400px;">
        <h3 style="margin-bottom: 20px; color: #2d3748;">Change Password</h3>
        
        <form id="password-form" style="display: grid; gap: 15px;">
            <div class="form-group">
                <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Current Password</label>
                <input type="password" name="current_password" class="form-input" required style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            
            <div class="form-group">
                <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">New Password</label>
                <input type="password" name="new_password" class="form-input" required minlength="6" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            
            <div class="form-group">
                <label style="display: block; font-weight: 600; color: #4a5568; margin-bottom: 8px;">Confirm New Password</label>
                <input type="password" name="confirm_password" class="form-input" required style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 8px;">
            </div>
            
            <div style="display: flex; gap: 10px; margin-top: 15px;">
                <button type="submit" class="btn btn-primary" style="flex: 1;">
                    <i class="fas fa-key"></i> Update Password
                </button>
                <button type="button" class="btn btn-secondary" onclick="closePasswordModal()" style="flex: 1;">
                    Cancel
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Profile editing functionality
function toggleEditMode() {
    document.getElementById('view-mode').style.display = 'none';
    document.getElementById('edit-mode').style.display = 'block';
}

function cancelEdit() {
    document.getElementById('view-mode').style.display = 'block';
    document.getElementById('edit-mode').style.display = 'none';
}

function showChangePassword() {
    const modal = document.getElementById('password-modal');
    modal.style.display = 'flex';
}

function closePasswordModal() {
    const modal = document.getElementById('password-modal');
    modal.style.display = 'none';
    document.getElementById('password-form').reset();
}

// Handle profile form submission
document.getElementById('profile-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('owner_id', <?php echo $owner_id; ?>);
    formData.append('action', 'update_profile');
    
    // Phone validation
    const phone = formData.get('phone');
    if (phone && !/^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/.test(phone)) {
        showNotification('Please enter a valid Sri Lankan phone number', 'error');
        return;
    }
    
    fetch('api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Profile updated successfully!', 'success');
            // Reload content to show updated information
            setTimeout(() => {
                window.ownerDashboard?.loadContent('profile');
            }, 1500);
        } else {
            showNotification(data.message || 'Failed to update profile', 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred while updating profile', 'error');
    });
});

// Handle password change form submission
document.getElementById('password-form').addEventListener('submit', function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const newPassword = formData.get('new_password');
    const confirmPassword = formData.get('confirm_password');
    
    if (newPassword !== confirmPassword) {
        showNotification('New passwords do not match', 'error');
        return;
    }
    
    formData.append('owner_id', <?php echo $owner_id; ?>);
    formData.append('action', 'change_password');
    
    fetch('api/update_profile.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('Password changed successfully!', 'success');
            closePasswordModal();
        } else {
            showNotification(data.message || 'Failed to change password', 'error');
        }
    })
    .catch(error => {
        showNotification('An error occurred while changing password', 'error');
    });
});

// Click outside modal to close
document.getElementById('password-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closePasswordModal();
    }
});
</script>

<?php
} catch (Exception $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Profile Error</h3>
        <p style="margin-bottom: 20px;">' . htmlspecialchars($e->getMessage()) . '</p>
        
        <div style="background: #f7fafc; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left; max-width: 600px; margin: 20px auto;">
            <h4 style="color: #2d3748; margin-bottom: 15px;">Debug Information:</h4>
            <p><strong>Owner ID:</strong> ' . htmlspecialchars($owner_id) . '</p>
            <p><strong>URL:</strong> ' . htmlspecialchars($_SERVER['REQUEST_URI']) . '</p>
            
            <h4 style="color: #2d3748; margin: 20px 0 10px 0;">Next Steps:</h4>
            <ol style="text-align: left;">
                <li>Check if you have any owners in the database by visiting: 
                    <a href="check_users.php" target="_blank" style="color: #3182ce;">check_users.php</a>
                </li>
                <li>If no owners exist, the check_users.php script will create a test owner for you</li>
                <li>Use the correct owner_id in the dashboard URL</li>
                <li>Make sure you\'re accessing the dashboard with: <code>dashboard.php#profile</code></li>
            </ol>
        </div>
        
        <div style="margin-top: 30px;">
            <a href="check_users.php" class="btn btn-primary" target="_blank" style="display: inline-block; padding: 12px 24px; background: #3182ce; color: white; text-decoration: none; border-radius: 6px; margin-right: 10px;">
                <i class="fas fa-users"></i> Check Users
            </a>
            <a href="../dashboard.php" class="btn btn-secondary" style="display: inline-block; padding: 12px 24px; background: #4a5568; color: white; text-decoration: none; border-radius: 6px;">
                <i class="fas fa-home"></i> Back to Dashboard
            </a>
        </div>
    </div>';
}
?>
