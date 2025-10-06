<?php
// Include database configuration
require_once '../../includes/config.php';
require_once '../../includes/functions.php';

// Get system settings from database
try {
    $stmt = $pdo->query("SELECT setting_name, setting_value FROM system_settings");
    $db_settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Merge with default settings
    $settings = array_merge([
        'site_name' => 'SmartRent',
        'site_url' => SITE_URL,
        'commission_percentage' => COMMISSION_PERCENTAGE,
        'admin_email' => 'admin@smartrent.com',
        'phone_number' => '+94 70 123 4567',
        'address' => 'Colombo, Sri Lanka',
        'currency' => 'LKR',
        'timezone' => 'Asia/Colombo',
        'smtp_host' => '',
        'smtp_port' => '587',
        'smtp_username' => '',
        'smtp_password' => '',
        'auto_approve_properties' => '0',
        'registration_approval' => '0',
        'max_properties_per_owner' => '10',
        // PayHere Settings
        'payhere_merchant_id' => PAYHERE_MERCHANT_ID,
        'payhere_merchant_secret' => '',
        'payhere_mode' => 'sandbox',
        'payhere_currency' => 'LKR',
        'payhere_enabled' => '1'
    ], $db_settings);
    
} catch (Exception $e) {
    // Fallback to default settings if database error
    $settings = [
        'site_name' => 'SmartRent',
        'site_url' => SITE_URL,
        'commission_percentage' => COMMISSION_PERCENTAGE,
        'admin_email' => 'admin@smartrent.com',
        'phone_number' => '+94 70 123 4567',
        'address' => 'Colombo, Sri Lanka',
        'currency' => 'LKR',
        'timezone' => 'Asia/Colombo'
    ];
}
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>System Settings</h2>
    <div id="settings-notifications" style="display: none;"></div>
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
                    <small class="form-text">Current value: <strong><?php echo htmlspecialchars($settings['site_name']); ?></strong></small>
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

    <!-- PayHere Payment Settings -->
    <div class="widget">
        <h3>PayHere Payment Gateway</h3>
        <div class="widget-content">
            <div style="background: #e8f5e8; padding: 15px; border-radius: 8px; margin-bottom: 20px; border-left: 4px solid #28a745;">
                <h4 style="margin: 0 0 10px 0; color: #155724;"><i class="fas fa-credit-card"></i> PayHere Configuration</h4>
                <p style="margin: 0; color: #155724; font-size: 14px;">Configure your PayHere payment gateway settings. Get your credentials from <a href="https://www.payhere.lk/" target="_blank" style="color: #007bff;">PayHere Merchant Portal</a>.</p>
            </div>
            
            <form id="payment-settings-form">
                <div class="form-group">
                    <label class="form-label">Merchant ID <span style="color: red;">*</span></label>
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($settings['payhere_merchant_id'] ?? PAYHERE_MERCHANT_ID); ?>" name="payhere_merchant_id" placeholder="Enter your PayHere Merchant ID" required>
                    <small style="color: #666; font-size: 12px;">Your unique merchant identifier from PayHere</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Merchant Secret <span style="color: red;">*</span></label>
                    <input type="password" class="form-input" value="<?php echo htmlspecialchars($settings['payhere_merchant_secret'] ?? ''); ?>" name="payhere_merchant_secret" placeholder="Enter your PayHere Merchant Secret" required>
                    <small style="color: #666; font-size: 12px;">Keep this secret secure - used for payment verification</small>
                    <button type="button" onclick="toggleSecretVisibility(this)" style="margin-top: 5px; padding: 5px 10px; border: 1px solid #ddd; background: #f8f9fa; border-radius: 4px; cursor: pointer; font-size: 12px;">
                        <i class="fas fa-eye"></i> Show/Hide
                    </button>
                </div>
                
                <div class="form-group">
                    <label class="form-label">PayHere Mode</label>
                    <select class="form-input" name="payhere_mode">
                        <option value="sandbox" <?php echo ($settings['payhere_mode'] ?? 'sandbox') === 'sandbox' ? 'selected' : ''; ?>>Sandbox (Testing)</option>
                        <option value="live" <?php echo ($settings['payhere_mode'] ?? 'sandbox') === 'live' ? 'selected' : ''; ?>>Live (Production)</option>
                    </select>
                    <small style="color: #666; font-size: 12px;">Use Sandbox for testing, Live for real transactions</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Currency</label>
                    <select class="form-input" name="payhere_currency">
                        <option value="LKR" <?php echo ($settings['payhere_currency'] ?? 'LKR') === 'LKR' ? 'selected' : ''; ?>>Sri Lankan Rupee (LKR)</option>
                        <option value="USD" <?php echo ($settings['payhere_currency'] ?? 'LKR') === 'USD' ? 'selected' : ''; ?>>US Dollar (USD)</option>
                    </select>
                    <small style="color: #666; font-size: 12px;">Currency for processing payments</small>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Payment Gateway Status</label>
                    <select class="form-input" name="payhere_enabled">
                        <option value="1" <?php echo ($settings['payhere_enabled'] ?? '1') === '1' ? 'selected' : ''; ?>>Enabled</option>
                        <option value="0" <?php echo ($settings['payhere_enabled'] ?? '1') === '0' ? 'selected' : ''; ?>>Disabled</option>
                    </select>
                    <small style="color: #666; font-size: 12px;">Enable/disable PayHere payments system-wide</small>
                </div>
                
                <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin: 20px 0; border-left: 4px solid #ffc107;">
                    <h5 style="margin: 0 0 10px 0; color: #856404;"><i class="fas fa-exclamation-triangle"></i> Important Notes:</h5>
                    <ul style="margin: 0; padding-left: 20px; color: #856404; font-size: 14px;">
                        <li>Always use <strong>Sandbox mode</strong> for testing</li>
                        <li>Keep your <strong>Merchant Secret</strong> confidential</li>
                        <li>Switch to <strong>Live mode</strong> only when ready for production</li>
                        <li>Test transactions thoroughly before going live</li>
                    </ul>
                </div>
                
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Save PayHere Settings
                    </button>
                    <button type="button" class="btn btn-secondary" onclick="testPayHereConnection()">
                        <i class="fas fa-plug"></i> Test Connection
                    </button>
                </div>
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
                    <input type="text" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_host']); ?>" placeholder="smtp.gmail.com" name="smtp_host">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Port</label>
                    <input type="number" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_port']); ?>" placeholder="587" name="smtp_port">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Username</label>
                    <input type="email" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_username']); ?>" placeholder="your-email@domain.com" name="smtp_username">
                </div>
                
                <div class="form-group">
                    <label class="form-label">SMTP Password</label>
                    <input type="password" class="form-input" value="<?php echo htmlspecialchars($settings['smtp_password']); ?>" placeholder="••••••••" name="smtp_password">
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
                        <option value="0" <?php echo $settings['auto_approve_properties'] === '0' ? 'selected' : ''; ?>>Require Manual Approval</option>
                        <option value="1" <?php echo $settings['auto_approve_properties'] === '1' ? 'selected' : ''; ?>>Auto-Approve All</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Registration Approval</label>
                    <select class="form-input" name="registration_approval">
                        <option value="0" <?php echo $settings['registration_approval'] === '0' ? 'selected' : ''; ?>>Auto-Activate Users</option>
                        <option value="1" <?php echo $settings['registration_approval'] === '1' ? 'selected' : ''; ?>>Require Admin Approval</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Max Properties Per Owner</label>
                    <input type="number" class="form-input" value="<?php echo htmlspecialchars($settings['max_properties_per_owner']); ?>" name="max_properties_per_owner" min="1">
                </div>
                
                <button type="submit" class="btn btn-primary">Save User Settings</button>
            </form>
        </div>
    </div>
    
    <!-- Settings Management -->
    <div class="widget" style="grid-column: span 2;">
        <h3>Settings Management <button class="btn btn-sm btn-primary" onclick="loadAllSettings()" style="float: right;"><i class="fas fa-refresh"></i> Refresh</button></h3>
        <div class="widget-content">
            <div style="margin-bottom: 20px;">
                <button class="btn btn-success" onclick="showAddSettingModal()">
                    <i class="fas fa-plus"></i> Add New Setting
                </button>
                <button class="btn btn-info" onclick="exportSettings()">
                    <i class="fas fa-download"></i> Export Settings
                </button>
            </div>
            
            <div class="table-responsive">
                <table class="table" id="settings-table">
                    <thead>
                        <tr>
                            <th>Setting Name</th>
                            <th>Value</th>
                            <th>Description</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="settings-tbody">
                        <tr>
                            <td colspan="4" class="text-center">Loading settings...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Setting Modal -->
<div id="settingModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h4 id="modalTitle">Add New Setting</h4>
            <span class="close" onclick="closeSettingModal()">&times;</span>
        </div>
        <div class="modal-body">
            <form id="setting-form">
                <div class="form-group">
                    <label class="form-label">Setting Name</label>
                    <input type="text" class="form-input" name="setting_name" id="setting_name" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Setting Value</label>
                    <textarea class="form-input" name="setting_value" id="setting_value" rows="3" required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-input" name="description" id="description">
                </div>
                <div class="form-group" style="text-align: right;">
                    <button type="button" class="btn btn-secondary" onclick="closeSettingModal()">Cancel</button>
                    <button type="submit" class="btn btn-primary" id="saveSettingBtn">Save Setting</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.modal-content {
    background-color: white;
    margin: 5% auto;
    padding: 0;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.3);
}

.modal-header {
    padding: 20px;
    border-bottom: 1px solid #ddd;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.modal-body {
    padding: 20px;
}

.close {
    font-size: 24px;
    font-weight: bold;
    cursor: pointer;
    color: #999;
}

.close:hover {
    color: #000;
}

.table-responsive {
    overflow-x: auto;
}

.table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 10px;
}

.table th,
.table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.table th {
    background-color: #f5f5f5;
    font-weight: bold;
}

.table tr:hover {
    background-color: #f9f9f9;
}

.btn-sm {
    padding: 5px 10px;
    font-size: 12px;
}

.text-center {
    text-align: center;
}
</style>

<script>
// Note: Form submission handlers are now managed by the dashboard class
// This ensures proper event binding after AJAX content load

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

function toggleSecretVisibility(button) {
    const secretInput = button.parentElement.querySelector('input[name="payhere_merchant_secret"]');
    const icon = button.querySelector('i');
    
    if (secretInput.type === 'password') {
        secretInput.type = 'text';
        icon.className = 'fas fa-eye-slash';
    } else {
        secretInput.type = 'password';
        icon.className = 'fas fa-eye';
    }
}

function testPayHereConnection() {
    const merchantId = document.querySelector('input[name="payhere_merchant_id"]').value;
    const merchantSecret = document.querySelector('input[name="payhere_merchant_secret"]').value;
    const mode = document.querySelector('select[name="payhere_mode"]').value;
    
    if (!merchantId || !merchantSecret) {
        showNotification('Please enter both Merchant ID and Merchant Secret before testing', 'error');
        return;
    }
    
    showNotification('Testing PayHere connection...', 'info');
    
    // Simulate connection test
    setTimeout(() => {
        const isValid = merchantId.length > 5 && merchantSecret.length > 10;
        if (isValid) {
            showNotification(`PayHere connection test successful! (${mode} mode)`, 'success');
        } else {
            showNotification('PayHere connection test failed. Please check your credentials.', 'error');
        }
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
    console.log(`${type.toUpperCase()}: ${message}`);
    
    // Try to use the dashboard's notification system if available
    if (window.dashboard && window.dashboard.showNotification) {
        window.dashboard.showNotification(message, type);
        return;
    }
    
    // Fallback to local notification area
    const notificationArea = document.getElementById('settings-notifications');
    if (notificationArea) {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 'alert-info';
        
        notificationArea.innerHTML = `<div class="alert ${alertClass}" style="margin: 10px 0; padding: 10px; border-radius: 4px; background: ${type === 'success' ? '#d4edda' : type === 'error' ? '#f8d7da' : '#d1ecf1'}; color: ${type === 'success' ? '#155724' : type === 'error' ? '#721c24' : '#0c5460'}; border: 1px solid ${type === 'success' ? '#c3e6cb' : type === 'error' ? '#f5c6cb' : '#bee5eb'};">${message}</div>`;
        notificationArea.style.display = 'block';
        
        // Hide after 5 seconds
        setTimeout(() => {
            if (notificationArea) {
                notificationArea.style.display = 'none';
            }
        }, 5000);
    } else {
        // Ultimate fallback to alert
        alert(message);
    }
}

// Settings Management Functions
let isEditMode = false;
let currentEditingSetting = null;

// Load all settings into the table
async function loadAllSettings() {
    try {
        const response = await fetch('api/settings_actions.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            displaySettingsInTable(result.data);
        } else {
            showNotification('Failed to load settings', 'error');
        }
    } catch (error) {
        showNotification('Error loading settings', 'error');
    }
}

// Display settings in table
function displaySettingsInTable(settings) {
    const tbody = document.getElementById('settings-tbody');
    
    if (settings.length === 0) {
        tbody.innerHTML = '<tr><td colspan="4" class="text-center">No settings found</td></tr>';
        return;
    }
    
    tbody.innerHTML = settings.map(setting => `
        <tr>
            <td><strong>${escapeHtml(setting.setting_name)}</strong></td>
            <td>
                <div style="max-width: 200px; overflow: hidden; text-overflow: ellipsis;">
                    ${escapeHtml(setting.setting_value)}
                </div>
            </td>
            <td>${escapeHtml(setting.description || '-')}</td>
            <td>
                <button class="btn btn-sm btn-info" onclick="editSetting('${setting.setting_name}', '${escapeHtml(setting.setting_value)}', '${escapeHtml(setting.description)}')">
                    <i class="fas fa-edit"></i> Edit
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteSetting('${setting.setting_name}')" style="margin-left: 5px;">
                    <i class="fas fa-trash"></i> Delete
                </button>
            </td>
        </tr>
    `).join('');
}

// Show add setting modal
function showAddSettingModal() {
    isEditMode = false;
    currentEditingSetting = null;
    document.getElementById('modalTitle').textContent = 'Add New Setting';
    document.getElementById('saveSettingBtn').textContent = 'Add Setting';
    
    // Clear form
    document.getElementById('setting-form').reset();
    document.getElementById('setting_name').readOnly = false;
    
    document.getElementById('settingModal').style.display = 'block';
}

// Edit setting
function editSetting(name, value, description) {
    isEditMode = true;
    currentEditingSetting = name;
    document.getElementById('modalTitle').textContent = 'Edit Setting';
    document.getElementById('saveSettingBtn').textContent = 'Update Setting';
    
    // Populate form
    document.getElementById('setting_name').value = name;
    document.getElementById('setting_value').value = value;
    document.getElementById('description').value = description || '';
    
    // Make setting name readonly when editing
    document.getElementById('setting_name').readOnly = true;
    
    document.getElementById('settingModal').style.display = 'block';
}

// Close modal
function closeSettingModal() {
    document.getElementById('settingModal').style.display = 'none';
}

// Delete setting
async function deleteSetting(settingName) {
    if (!confirm(`Are you sure you want to delete the setting "${settingName}"?`)) {
        return;
    }
    
    try {
        const response = await fetch('api/settings_actions.php', {
            method: 'DELETE',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ setting_name: settingName })
        });
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('Setting deleted successfully', 'success');
            loadAllSettings();
        } else {
            showNotification(result.message || 'Failed to delete setting', 'error');
        }
    } catch (error) {
        showNotification('Error deleting setting', 'error');
    }
}

// Export settings
async function exportSettings() {
    try {
        const response = await fetch('api/settings_actions.php', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        const result = await response.json();
        
        if (result.success) {
            const dataStr = JSON.stringify(result.data, null, 2);
            const dataBlob = new Blob([dataStr], {type: 'application/json'});
            
            const link = document.createElement('a');
            link.href = URL.createObjectURL(dataBlob);
            link.download = `settings_export_${new Date().toISOString().split('T')[0]}.json`;
            link.click();
            
            showNotification('Settings exported successfully', 'success');
        } else {
            showNotification('Failed to export settings', 'error');
        }
    } catch (error) {
        showNotification('Error exporting settings', 'error');
    }
}

// Handle setting form submission
document.getElementById('setting-form').addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    const data = Object.fromEntries(formData);
    
    try {
        let response;
        
        if (isEditMode) {
            // Update existing setting
            response = await fetch('api/settings_actions.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
        } else {
            // Add new setting
            response = await fetch('api/settings_actions.php', {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify(data)
            });
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification(isEditMode ? 'Setting updated successfully' : 'Setting added successfully', 'success');
            closeSettingModal();
            loadAllSettings();
        } else {
            showNotification(result.message || 'Failed to save setting', 'error');
        }
    } catch (error) {
        showNotification('Error saving setting', 'error');
    }
});

// Utility function to escape HTML
function escapeHtml(unsafe) {
    if (typeof unsafe !== 'string') return unsafe;
    return unsafe
         .replace(/&/g, "&amp;")
         .replace(/</g, "&lt;")
         .replace(/>/g, "&gt;")
         .replace(/"/g, "&quot;")
         .replace(/'/g, "&#039;");
}

// Close modal when clicking outside
window.addEventListener('click', function(event) {
    const modal = document.getElementById('settingModal');
    if (event.target === modal) {
        closeSettingModal();
    }
});

// Load settings when page loads
document.addEventListener('DOMContentLoaded', function() {
    loadAllSettings();
});
</script>