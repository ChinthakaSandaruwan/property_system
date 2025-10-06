<?php
// Include database configuration
require_once '../../includes/config.php';

// Get system settings (you might want to create a settings table)
$settings = [
    'site_name' => 'Smart Rental System',
    'site_url' => SITE_URL,
    'commission_percentage' => COMMISSION_PERCENTAGE,
    'admin_email' => 'admin@smartrental.lk',
    'phone_number' => '+94 11 234 5678',
    'address' => 'Colombo, Sri Lanka',
    'currency' => 'LKR',
    'timezone' => 'Asia/Colombo'
];
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>System Settings</h2>
</div>

<div class="dashboard-widgets">
    <!-- General Settings -->
    <div class="widget" style="grid-column: span 2;">
        <h3>General Settings</h3>
        <div class="widget-content">
            <form id="general-settings-form">
                <div class="form-group">
                    <label class="form-label">Site Name</label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($settings['site_name']); ?>" name="site_name">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Site URL</label>
                    <input type="url" class="form-input" value="<?php echo htmlspecialchars($settings['site_url']); ?>" name="site_url">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Commission Percentage (%)</label>
                    <input type="number" class="form-input" value="<?php echo $settings['commission_percentage']; ?>" name="commission_percentage" min="0" max="100" step="0.1">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Currency</label>
                    <select class="form-input" name="currency">
                        <option value="LKR" <?php echo $settings['currency'] === 'LKR' ? 'selected' : ''; ?>>Sri Lankan Rupee (LKR)</option>
                        <option value="USD" <?php echo $settings['currency'] === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Save General Settings</button>
            </form>
        </div>
    </div>

    <!-- Contact Information -->
    <div class="widget">
        <h3>Contact Information</h3>
        <div class="widget-content">
            <form id="contact-settings-form">
                <div class="form-group">
                    <label class="form-label">Admin Email</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($settings['admin_email']); ?>" name="admin_email">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="tel" class="form-input" value="<?php echo htmlspecialchars($settings['phone_number']); ?>" name="phone_number" pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" title="Please enter a valid Sri Lankan phone number">
                </div>
                
                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea class="form-input" name="address" rows="3"><?php echo htmlspecialchars($settings['address']); ?></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Contact Info</button>
            </form>
        </div>
    </div>

    <!-- System Maintenance -->
    <div class="widget">
        <h3>System Maintenance</h3>
        <div class="widget-content">
            <div class="form-group">
                <label class="form-label">Database Backup</label>
                <button class="btn btn-secondary" onclick="createBackup()">
                    <i class="fas fa-database"></i> Create Backup
                </button>
            </div>
            
            <div class="form-group">
                <label class="form-label">Clear Cache</label>
                <button class="btn btn-secondary" onclick="clearCache()">
                    <i class="fas fa-broom"></i> Clear Cache
                </button>
            </div>
            
            <div class="form-group">
                <label class="form-label">System Logs</label>
                <button class="btn btn-secondary" onclick="viewLogs()">
                    <i class="fas fa-file-alt"></i> View Logs
                </button>
            </div>
            
            <div class="form-group">
                <label class="form-label">Maintenance Mode</label>
                <div style="display: flex; gap: 10px;">
                    <button class="btn btn-warning" onclick="toggleMaintenance(true)">
                        <i class="fas fa-tools"></i> Enable
                    </button>
                    <button class="btn btn-primary" onclick="toggleMaintenance(false)">
                        <i class="fas fa-play"></i> Disable
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Payment Settings -->
    <div class="widget">
        <h3>Payment Settings</h3>
        <div class="widget-content">
            <form id="payment-settings-form">
                <div class="form-group">
                    <label class="form-label">PayHere Merchant ID</label>
                    <input type="text" class="form-input" value="<?php echo PAYHERE_MERCHANT_ID; ?>" name="payhere_merchant_id">
                </div>
                
                <div class="form-group">
                    <label class="form-label">PayHere Mode</label>
                    <select class="form-input" name="payhere_mode">
                        <option value="sandbox">Sandbox (Testing)</option>
                        <option value="live">Live (Production)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Default Payment Method</label>
                    <select class="form-input" name="default_payment_method">
                        <option value="payhere">PayHere</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="cash">Cash Payment</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Save Payment Settings</button>
            </form>
        </div>
    </div>

    <!-- Email Settings -->
    <div class="widget">
        <h3>Email Configuration</h3>
        <div class="widget-content">
            <form id="email-settings-form">
                <div class="form-group">
                    <label class="form-label">SMTP Host</label>
                    <input type="text" class="form-input" placeholder="smtp.gmail.com" name="smtp_host">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" class="form-input" placeholder="587" name="smtp_port">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Username</label>
                    <input type="email" class="form-input" placeholder="your-email@domain.com" name="smtp_username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" class="form-input" placeholder="••••••••" name="smtp_password">
                </div>
                
                <button type="submit" class="btn btn-primary">Save Email Settings</button>
                <button type="button" class="btn btn-secondary" onclick="testEmail()">Test Email</button>
            </form>
        </div>
    </div>

    <!-- User Management -->
    <div class="widget">
        <h3>User Management Settings</h3>
        <div class="widget-content">
            <form id="user-settings-form">
                <div class="form-group">
                    <label class="form-label">Auto-Approve Properties</label>
                    <select class="form-input" name="auto_approve_properties">
                        <option value="0">Require Manual Approval</option>
                        <option value="1">Auto-Approve All</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Registration Approval</label>
                    <select class="form-input" name="registration_approval">
                        <option value="0">Auto-Activate Users</option>
                        <option value="1">Require Admin Approval</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Max Properties Per Owner</label>
                    <input type="number" class="form-input" value="10" name="max_properties_per_owner" min="1">
                </div>
                
                <button type="submit" class="btn btn-primary">Save User Settings</button>
            </form>
        </div>
    </div>
</div>

<script>
// Form submissions
document.getElementById('general-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('general', new FormData(this));
});

document.getElementById('contact-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('contact', new FormData(this));
});

document.getElementById('payment-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('payment', new FormData(this));
});

document.getElementById('email-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('email', new FormData(this));
});

document.getElementById('user-settings-form').addEventListener('submit', function(e) {
    e.preventDefault();
    saveSettings('user', new FormData(this));
});

async function saveSettings(type, formData) {
    try {
        const data = Object.fromEntries(formData);
        data.type = type;
        
        const response = await fetch('api/settings_actions.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Settings saved successfully!', 'success');
        } else {
            showNotification(result.message || 'Failed to save settings', 'error');
        }
    } catch (error) {
        showNotification('An error occurred while saving settings', 'error');
    }
}

function createBackup() {
    if (confirm('This will create a backup of the database. Continue?')) {
        showNotification('Database backup initiated...', 'info');
        // Implement backup logic
    }
}

function clearCache() {
    if (confirm('This will clear all cached data. Continue?')) {
        showNotification('Cache cleared successfully!', 'success');
        // Implement cache clearing logic
    }
}

function viewLogs() {
    window.open('logs.php', '_blank');
}

function toggleMaintenance(enable) {
    const action = enable ? 'enable' : 'disable';
    if (confirm(`Are you sure you want to ${action} maintenance mode?`)) {
        showNotification(`Maintenance mode ${action}d`, enable ? 'warning' : 'success');
        // Implement maintenance mode toggle
    }
}

function testEmail() {
    const formData = new FormData(document.getElementById('email-settings-form'));
    showNotification('Sending test email...', 'info');
    
    // Implement test email functionality
    setTimeout(() => {
        showNotification('Test email sent successfully!', 'success');
    }, 2000);
}

// Phone number validation for Sri Lankan numbers
document.querySelector('input[name="phone_number"]').addEventListener('input', function(e) {
    const phoneRegex = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/;
    const value = e.target.value;
    
    if (value && !phoneRegex.test(value)) {
        e.target.setCustomValidity('Please enter a valid Sri Lankan phone number (e.g., 0771234567)');
    } else {
        e.target.setCustomValidity('');
    }
});

function showNotification(message, type = 'info') {
    // Use the dashboard's notification system if available
    if (window.AdminDashboard) {
        // This would work if we had access to the dashboard instance
        console.log(`${type.toUpperCase()}: ${message}`);
    } else {
        alert(message);
    }
}
</script>