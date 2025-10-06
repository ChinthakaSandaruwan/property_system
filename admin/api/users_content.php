<?php
// Include database configuration
require_once '../../includes/config.php';

try {
    // Get initial users (first page, no filters)
    $stmt = $pdo->prepare("
        SELECT 
            id,
            full_name,
            email,
            phone,
            user_type,
            status,
            created_at,
            updated_at as last_login
        FROM users
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    // Get total count
    $countStmt = $pdo->prepare("SELECT COUNT(*) as total FROM users");
    $countStmt->execute();
    $totalUsers = $countStmt->fetch()['total'];
?>

<!-- User Management Header -->
<div class="page-header" style="
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    padding: 30px;
    border-radius: 16px;
    margin-bottom: 30px;
    color: white;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
        <div>
            <h1 style="margin: 0; font-size: 28px; font-weight: 700; margin-bottom: 8px;">
                <i class="fas fa-users" style="margin-right: 12px;"></i>User Management
            </h1>
            <p style="margin: 0; opacity: 0.9; font-size: 16px;">Manage and oversee all system users</p>
        </div>
        <button class="btn btn-light" id="add-user-btn" style="
            background: rgba(255, 255, 255, 0.95);
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            color: #667eea;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 15px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        "
        onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 6px 20px rgba(0, 0, 0, 0.15)'"
        onmouseout="this.style.transform='translateY(0)'; this.style.boxShadow='0 4px 12px rgba(0, 0, 0, 0.1)'">
            <i class="fas fa-plus-circle"></i> Add New User
        </button>
    </div>
    
    <!-- Enhanced Search and Filter Controls -->
    <div class="search-filters-container" style="
        background: rgba(255, 255, 255, 0.95);
        padding: 24px;
        border-radius: 12px;
        backdrop-filter: blur(10px);
        display: grid;
        grid-template-columns: 1fr auto auto auto;
        gap: 16px;
        align-items: center;
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.1);
    ">
        <!-- Real-time Search Input -->
        <div style="position: relative;">
            <i class="fas fa-search" id="search-icon" style="
                position: absolute;
                left: 16px;
                top: 50%;
                transform: translateY(-50%);
                color: #667eea;
                z-index: 2;
                font-size: 16px;
                transition: all 0.3s ease;
            "></i>
            <input type="text" 
                   id="user-search-input" 
                   placeholder="Search users by name, email, or phone number..."
                   style="
                       width: 100%;
                       padding: 16px 20px 16px 50px;
                       border: 2px solid #e1e5e9;
                       border-radius: 12px;
                       font-size: 15px;
                       transition: all 0.3s ease;
                       background: white;
                       color: #2d3748;
                       box-sizing: border-box;
                   "
                   onfocus="this.style.borderColor='#667eea'; this.style.boxShadow='0 0 0 4px rgba(102, 126, 234, 0.1)'"
                   onblur="this.style.borderColor='#e1e5e9'; this.style.boxShadow='none'">
            
            <!-- Real-time search indicator -->
            <div id="search-indicator" style="
                position: absolute;
                right: 16px;
                top: 50%;
                transform: translateY(-50%);
                display: none;
                color: #667eea;
                font-size: 14px;
            ">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
        
        <!-- User Type Filter -->
        <div>
            <label style="display: block; color: #667eea; font-weight: 600; margin-bottom: 6px; font-size: 13px;">USER TYPE</label>
            <select id="user-type-filter" style="
                min-width: 150px;
                padding: 14px 16px;
                border: 2px solid #e1e5e9;
                border-radius: 10px;
                background: white;
                color: #2d3748;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
            " onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e1e5e9'">
                <option value="">All Types</option>
                <option value="customer">Customer</option>
                <option value="owner">Owner</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        
        <!-- Status Filter -->
        <div>
            <label style="display: block; color: #667eea; font-weight: 600; margin-bottom: 6px; font-size: 13px;">STATUS</label>
            <select id="user-status-filter" style="
                min-width: 150px;
                padding: 14px 16px;
                border: 2px solid #e1e5e9;
                border-radius: 10px;
                background: white;
                color: #2d3748;
                font-size: 14px;
                font-weight: 500;
                cursor: pointer;
                transition: all 0.3s ease;
            " onfocus="this.style.borderColor='#667eea'" onblur="this.style.borderColor='#e1e5e9'">
                <option value="">All Status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
                <option value="suspended">Suspended</option>
            </select>
        </div>
        
        <!-- Clear Button -->
        <div>
            <label style="display: block; color: transparent; font-weight: 600; margin-bottom: 6px; font-size: 13px;">ACTIONS</label>
            <div style="display: flex; gap: 8px;">
                <button id="clear-filters" style="
                    padding: 14px 16px;
                    background: #f8f9fa;
                    border: 2px solid #e1e5e9;
                    border-radius: 10px;
                    color: #6c757d;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    justify-content: center;
                " 
                onmouseover="this.style.background='#e9ecef'; this.style.borderColor='#adb5bd'" 
                onmouseout="this.style.background='#f8f9fa'; this.style.borderColor='#e1e5e9'" 
                onclick="clearAllFilters()" title="Clear all filters and search">
                    <i class="fas fa-times-circle"></i> Clear
                </button>
                
                <button id="search-tips-btn" onclick="toggleSearchTips()" style="
                    padding: 14px 16px;
                    background: #e8f4ff;
                    border: 2px solid #b3d9ff;
                    border-radius: 10px;
                    color: #0066cc;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    gap: 6px;
                    font-size: 13px;
                    font-weight: 600;
                    transition: all 0.3s ease;
                    justify-content: center;
                " 
                onmouseover="this.style.background='#cce6ff'" 
                onmouseout="this.style.background='#e8f4ff'" title="Search tips">
                    <i class="fas fa-question-circle"></i> Tips
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Search Results Summary -->
<div id="search-results-summary" style="
    background: white;
    padding: 16px 24px;
    border-radius: 12px;
    margin-bottom: 16px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
    border-left: 4px solid #667eea;
    display: none;
">
    <div style="display: flex; justify-content: space-between; align-items: center;">
        <div>
            <span id="search-results-text" style="color: #2d3748; font-weight: 600;"></span>
            <span id="search-query-display" style="color: #667eea; margin-left: 8px;"></span>
        </div>
        <button onclick="clearAllFilters()" style="
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 6px 12px;
            color: #4a5568;
            cursor: pointer;
            font-size: 13px;
        ">
            <i class="fas fa-times" style="margin-right: 4px;"></i> Clear Search
        </button>
    </div>
</div>

<!-- Loading Indicator -->
<div id="search-loading" style="
    display: none;
    text-align: center;
    padding: 40px;
    background: white;
    border-radius: 12px;
    margin-bottom: 16px;
">
    <div style="
        display: inline-block;
        width: 40px;
        height: 40px;
        border: 4px solid #f3f4f6;
        border-top: 4px solid #667eea;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    "></div>
    <p style="margin-top: 16px; color: #6b7280;">Searching users...</p>
</div>

<!-- Enhanced Data Table -->
<div class="table-container" style="
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    overflow: hidden;
    border: 1px solid #e1e5e9;
">
    <table class="data-table" style="
        width: 100%;
        border-collapse: collapse;
        font-size: 14px;
        background: white;
    ">
        <thead style="
            background: linear-gradient(135deg, #f8f9fd 0%, #f1f3f9 100%);
            border-bottom: 2px solid #e1e5e9;
        ">
            <tr>
                <th style="
                    padding: 20px 24px;
                    text-align: left;
                    font-weight: 700;
                    color: #2d3748;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #e1e5e9;
                ">User Information</th>
                <th style="
                    padding: 20px 24px;
                    text-align: left;
                    font-weight: 700;
                    color: #2d3748;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #e1e5e9;
                ">Type</th>
                <th style="
                    padding: 20px 24px;
                    text-align: left;
                    font-weight: 700;
                    color: #2d3748;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #e1e5e9;
                ">Contact</th>
                <th style="
                    padding: 20px 24px;
                    text-align: left;
                    font-weight: 700;
                    color: #2d3748;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #e1e5e9;
                ">Status</th>
                <th style="
                    padding: 20px 24px;
                    text-align: left;
                    font-weight: 700;
                    color: #2d3748;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #e1e5e9;
                ">Activity</th>
                <th style="
                    padding: 20px 24px;
                    text-align: center;
                    font-weight: 700;
                    color: #2d3748;
                    font-size: 13px;
                    text-transform: uppercase;
                    letter-spacing: 0.5px;
                    border-bottom: 2px solid #e1e5e9;
                    width: 200px;
                ">Actions</th>
            </tr>
        </thead>
        <tbody id="users-table-body">
            <?php foreach ($users as $user): ?>
            <tr data-user-type="<?php echo $user['user_type']; ?>" 
                data-user-status="<?php echo $user['status']; ?>"
                data-search-text="<?php echo strtolower(htmlspecialchars($user['full_name'] . ' ' . $user['email'] . ' ' . $user['phone'])); ?>"
                style="
                    border-bottom: 1px solid #f1f3f4;
                    transition: all 0.2s ease;
                    cursor: pointer;
                "
                onmouseover="this.style.backgroundColor='#f8f9fd'; this.style.transform='translateY(-1px)'"
                onmouseout="this.style.backgroundColor='white'; this.style.transform='translateY(0)'">
                
                <!-- User Information -->
                <td style="padding: 20px 24px; vertical-align: middle;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div style="
                            width: 48px;
                            height: 48px;
                            border-radius: 12px;
                            background: linear-gradient(135deg, #667eea, #764ba2);
                            display: flex;
                            align-items: center;
                            justify-content: center;
                            color: white;
                            font-weight: 700;
                            font-size: 18px;
                            flex-shrink: 0;
                        ">
                            <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                        </div>
                        <div>
                            <div style="font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 4px;">
                                <?php echo htmlspecialchars($user['full_name']); ?>
                            </div>
                            <div style="color: #718096; font-size: 13px;">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </div>
                        </div>
                    </div>
                </td>
                
                <!-- User Type -->
                <td style="padding: 20px 24px; vertical-align: middle;">
                    <?php 
                        $typeColors = [
                            'admin' => ['bg' => '#fed7d7', 'text' => '#c53030'],
                            'owner' => ['bg' => '#bee3f8', 'text' => '#2b6cb0'],
                            'customer' => ['bg' => '#c6f6d5', 'text' => '#2f855a']
                        ];
                        $color = $typeColors[$user['user_type']];
                    ?>
                    <span style="
                        background: <?php echo $color['bg']; ?>;
                        color: <?php echo $color['text']; ?>;
                        padding: 6px 12px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                    ">
                        <i class="fas fa-<?php echo $user['user_type'] === 'admin' ? 'crown' : ($user['user_type'] === 'owner' ? 'key' : 'user'); ?>" style="margin-right: 4px;"></i>
                        <?php echo ucfirst($user['user_type']); ?>
                    </span>
                </td>
                
                <!-- Contact -->
                <td style="padding: 20px 24px; vertical-align: middle;">
                    <div style="color: #4a5568; font-weight: 500;">
                        <i class="fas fa-phone" style="color: #667eea; margin-right: 8px;"></i>
                        <?php echo htmlspecialchars($user['phone']); ?>
                    </div>
                </td>
                
                <!-- Status -->
                <td style="padding: 20px 24px; vertical-align: middle;">
                    <?php 
                        $statusColors = [
                            'active' => ['bg' => '#c6f6d5', 'text' => '#2f855a', 'icon' => 'check-circle'],
                            'inactive' => ['bg' => '#fed7d7', 'text' => '#c53030', 'icon' => 'times-circle'],
                            'suspended' => ['bg' => '#feebc8', 'text' => '#c05621', 'icon' => 'pause-circle']
                        ];
                        $statusColor = $statusColors[strtolower($user['status'])];
                    ?>
                    <span style="
                        background: <?php echo $statusColor['bg']; ?>;
                        color: <?php echo $statusColor['text']; ?>;
                        padding: 8px 14px;
                        border-radius: 20px;
                        font-size: 12px;
                        font-weight: 600;
                        text-transform: uppercase;
                        letter-spacing: 0.5px;
                        display: inline-flex;
                        align-items: center;
                        gap: 6px;
                    ">
                        <i class="fas fa-<?php echo $statusColor['icon']; ?>"></i>
                        <?php echo ucfirst($user['status']); ?>
                    </span>
                </td>
                
                <!-- Activity -->
                <td style="padding: 20px 24px; vertical-align: middle;">
                    <div style="font-size: 13px;">
                        <div style="color: #4a5568; margin-bottom: 4px;">
                            <strong>Last:</strong> <?php echo $user['last_login'] ? date('M j, Y', strtotime($user['last_login'])) : 'Never'; ?>
                        </div>
                        <div style="color: #718096;">
                            <strong>Joined:</strong> <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                        </div>
                    </div>
                </td>
                
                <!-- Actions -->
                <td style="padding: 20px 24px; vertical-align: middle; text-align: center;">
                    <div style="display: flex; justify-content: center; gap: 6px; flex-wrap: wrap;">
                        <button class="user-action" data-action="view" data-user-id="<?php echo $user['id']; ?>" 
                                title="View Details" style="
                                    padding: 8px 10px;
                                    background: #e3f2fd;
                                    border: none;
                                    border-radius: 8px;
                                    color: #1976d2;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    font-size: 12px;
                                "
                                onmouseover="this.style.background='#bbdefb'"
                                onmouseout="this.style.background='#e3f2fd'">
                            <i class="fas fa-eye"></i>
                        </button>
                        
                        <button class="user-action" data-action="edit" data-user-id="<?php echo $user['id']; ?>" 
                                title="Edit User" style="
                                    padding: 8px 10px;
                                    background: #e8f5e8;
                                    border: none;
                                    border-radius: 8px;
                                    color: #2e7d32;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    font-size: 12px;
                                "
                                onmouseover="this.style.background='#c8e6c9'"
                                onmouseout="this.style.background='#e8f5e8'">
                            <i class="fas fa-edit"></i>
                        </button>
                        
                        <?php if ($user['status'] === 'active'): ?>
                        <button class="user-action" data-action="suspend" data-user-id="<?php echo $user['id']; ?>" 
                                title="Suspend User" style="
                                    padding: 8px 10px;
                                    background: #fff3e0;
                                    border: none;
                                    border-radius: 8px;
                                    color: #f57c00;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    font-size: 12px;
                                "
                                onmouseover="this.style.background='#ffcc02'"
                                onmouseout="this.style.background='#fff3e0'">
                            <i class="fas fa-pause"></i>
                        </button>
                        <?php else: ?>
                        <button class="user-action" data-action="activate" data-user-id="<?php echo $user['id']; ?>" 
                                title="Activate User" style="
                                    padding: 8px 10px;
                                    background: #e8f5e8;
                                    border: none;
                                    border-radius: 8px;
                                    color: #2e7d32;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    font-size: 12px;
                                "
                                onmouseover="this.style.background='#c8e6c9'"
                                onmouseout="this.style.background='#e8f5e8'">
                            <i class="fas fa-play"></i>
                        </button>
                        <?php endif; ?>
                        
                        <button class="user-action" data-action="reset_password" data-user-id="<?php echo $user['id']; ?>" 
                                title="Reset Password" style="
                                    padding: 8px 10px;
                                    background: #f3e5f5;
                                    border: none;
                                    border-radius: 8px;
                                    color: #7b1fa2;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    font-size: 12px;
                                "
                                onmouseover="this.style.background='#e1bee7'"
                                onmouseout="this.style.background='#f3e5f5'">
                            <i class="fas fa-key"></i>
                        </button>
                        
                        <?php if ($user['user_type'] !== 'admin'): ?>
                        <button class="user-action" data-action="delete" data-user-id="<?php echo $user['id']; ?>" 
                                title="Delete User" style="
                                    padding: 8px 10px;
                                    background: #ffebee;
                                    border: none;
                                    border-radius: 8px;
                                    color: #d32f2f;
                                    cursor: pointer;
                                    transition: all 0.2s;
                                    font-size: 12px;
                                "
                                onmouseover="this.style.background='#ffcdd2'"
                                onmouseout="this.style.background='#ffebee'">
                            <i class="fas fa-trash"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if (empty($users)): ?>
<div style="
    text-align: center; 
    padding: 80px 40px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    margin-top: 20px;
">
    <div style="
        width: 120px;
        height: 120px;
        background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 24px;
    ">
        <i class="fas fa-users" style="font-size: 3rem; color: #667eea;"></i>
    </div>
    <h3 style="color: #2d3748; margin-bottom: 12px; font-size: 24px;">No Users Found</h3>
    <p style="color: #718096; margin-bottom: 24px; font-size: 16px;">There are no users in the system yet. Add your first user to get started.</p>
    <button onclick="document.getElementById('add-user-btn').click()" style="
        background: linear-gradient(135deg, #667eea, #764ba2);
        color: white;
        border: none;
        padding: 12px 24px;
        border-radius: 10px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        font-size: 15px;
        transition: transform 0.2s;
    "
    onmouseover="this.style.transform='translateY(-2px)'"
    onmouseout="this.style.transform='translateY(0)'">
        <i class="fas fa-plus-circle"></i> Add First User
    </button>
</div>
<?php endif; ?>

<!-- Add responsive CSS -->
<style>
/* Responsive Design */
@media (max-width: 1200px) {
    .search-filters-container {
        grid-template-columns: 1fr auto auto !important;
    }
    .search-filters-container > div:first-child {
        grid-column: 1 / -1;
        margin-bottom: 12px;
    }
}

@media (max-width: 768px) {
    .page-header {
        padding: 20px !important;
    }
    
    .page-header > div:first-child {
        flex-direction: column;
        align-items: flex-start !important;
        gap: 16px;
    }
    
    .search-filters-container {
        grid-template-columns: 1fr !important;
        gap: 12px !important;
    }
    
    .data-table {
        font-size: 12px !important;
    }
    
    .data-table td {
        padding: 12px 16px !important;
    }
    
    /* Hide some columns on mobile */
    .data-table th:nth-child(5),
    .data-table td:nth-child(5) {
        display: none;
    }
    
    /* Stack action buttons */
    .data-table td:last-child > div {
        flex-direction: column !important;
        gap: 4px !important;
    }
}

@media (max-width: 480px) {
    .data-table th:nth-child(3),
    .data-table td:nth-child(3) {
        display: none;
    }
    
    .page-header h1 {
        font-size: 22px !important;
    }
}

/* Enhanced scrollbar */
.table-container {
    scrollbar-width: thin;
    scrollbar-color: #cbd5e0 #f7fafc;
}

.table-container::-webkit-scrollbar {
    height: 8px;
    width: 8px;
}

.table-container::-webkit-scrollbar-track {
    background: #f7fafc;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb {
    background: #cbd5e0;
    border-radius: 4px;
}

.table-container::-webkit-scrollbar-thumb:hover {
    background: #a0aec0;
}

/* Animation for table rows */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.data-table tbody tr {
    animation: fadeInUp 0.3s ease-out;
}

/* Focus states for accessibility */
.user-action:focus {
    outline: 2px solid #667eea;
    outline-offset: 2px;
}

input:focus, select:focus {
    outline: none;
}

/* Loading spinner animation */
@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>

<script>
let searchTimeout = null;
let currentSearchParams = {
    search: '',
    type: '',
    status: '',
    page: 1
};

// Initialize search functionality
document.addEventListener('DOMContentLoaded', function() {
    console.log('DOM Content Loaded - Initializing search functionality');
    
    // Check if elements exist
    const typeFilter = document.getElementById('user-type-filter');
    const statusFilter = document.getElementById('user-status-filter');
    const searchInput = document.getElementById('user-search-input');
    
    console.log('Elements found:', {
        typeFilter: !!typeFilter,
        statusFilter: !!statusFilter,
        searchInput: !!searchInput
    });
    
    if (typeFilter && statusFilter && searchInput) {
        // Add event listeners
        typeFilter.addEventListener('change', handleFilterChange);
        statusFilter.addEventListener('change', handleFilterChange);
        searchInput.addEventListener('input', handleSearchInput);
        
        console.log('Event listeners attached successfully');
        
        // Initial load
        updateSearchResults();
    } else {
        console.error('Some elements are missing - cannot initialize search');
    }
});

// Handle search input with real-time AJAX search
function handleSearchInput(event) {
    console.log('Search input handler triggered:', event.target.value);
    clearTimeout(searchTimeout);
    
    // Show search indicator
    showSearchIndicator();
    
    searchTimeout = setTimeout(() => {
        const searchValue = document.getElementById('user-search-input').value.trim();
        console.log('Performing search with value:', searchValue);
        currentSearchParams.search = searchValue;
        currentSearchParams.page = 1;
        performSearch();
    }, 300); // 300ms delay for optimal UX
}

// Show search indicator
function showSearchIndicator() {
    const searchIcon = document.getElementById('search-icon');
    const searchIndicator = document.getElementById('search-indicator');
    
    searchIcon.style.opacity = '0.5';
    searchIndicator.style.display = 'block';
}

// Hide search indicator
function hideSearchIndicator() {
    const searchIcon = document.getElementById('search-icon');
    const searchIndicator = document.getElementById('search-indicator');
    
    searchIcon.style.opacity = '1';
    searchIndicator.style.display = 'none';
}

// Handle filter changes
function handleFilterChange() {
    currentSearchParams.type = document.getElementById('user-type-filter').value;
    currentSearchParams.status = document.getElementById('user-status-filter').value;
    currentSearchParams.page = 1;
    performSearch();
}

// Perform search with backend API
async function performSearch() {
    const { search, type, status, page } = currentSearchParams;
    console.log('performSearch called with params:', { search, type, status, page });
    
    // Show loading indicator
    showLoading();
    
    // Hide previous results
    hideSearchSummary();
    
    try {
        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (type) params.append('type', type);
        if (status) params.append('status', status);
        params.append('page', page);
        params.append('limit', 50);
        
        const url = `api/user_search.php?${params.toString()}`;
        console.log('Fetching URL:', url);
        
        const response = await fetch(url);
        const data = await response.json();
        console.log('API Response:', data);
        
        if (data.success) {
            displaySearchResults(data.data);
            updateSearchSummary(data.data);
        } else {
            showError(data.message || 'Search failed');
        }
    } catch (error) {
        console.error('Search error:', error);
        showError('An error occurred while searching');
    } finally {
        hideLoading();
        hideSearchIndicator(); // Hide search indicator when done
    }
}

// Display search results in table
function displaySearchResults(data) {
    console.log('displaySearchResults called with:', data);
    const tbody = document.getElementById('users-table-body');
    console.log('Table body found:', !!tbody);
    
    if (!tbody) {
        console.error('Table body not found!');
        return;
    }
    
    const { users } = data;
    console.log('Number of users to display:', users ? users.length : 0);
    
    if (!users || users.length === 0) {
        console.log('No users found, showing no results');
        showNoResults();
        return;
    }
    
    // Clear existing rows
    console.log('Clearing existing table rows');
    tbody.innerHTML = '';
    
    // Add new rows
    console.log('Adding new rows to table');
    users.forEach((user, index) => {
        console.log(`Adding user ${index + 1}:`, user.full_name);
        const row = createUserRow(user);
        tbody.appendChild(row);
    });
    
    // Show table
    const tableContainer = document.querySelector('.table-container');
    if (tableContainer) {
        tableContainer.style.display = 'block';
        console.log('Table container shown');
    }
    
    hideNoResults();
    
    // Highlight search terms
    if (currentSearchParams.search) {
        console.log('Highlighting search terms:', currentSearchParams.search);
        highlightSearchTerms(currentSearchParams.search);
    }
    
    console.log('Table update completed');
}

// Create user table row
function createUserRow(user) {
    const row = document.createElement('tr');
    row.setAttribute('data-user-type', user.user_type);
    row.setAttribute('data-user-status', user.status);
    row.style.cssText = `
        border-bottom: 1px solid #f1f3f4;
        transition: all 0.2s ease;
        cursor: pointer;
    `;
    row.onmouseover = () => {
        row.style.backgroundColor = '#f8f9fd';
        row.style.transform = 'translateY(-1px)';
    };
    row.onmouseout = () => {
        row.style.backgroundColor = 'white';
        row.style.transform = 'translateY(0)';
    };
    
    row.innerHTML = generateUserRowHTML(user);
    return row;
}

// Generate HTML for user row (reuse existing template)
function generateUserRowHTML(user) {
    const typeColors = {
        'admin': { bg: '#fed7d7', text: '#c53030' },
        'owner': { bg: '#bee3f8', text: '#2b6cb0' },
        'customer': { bg: '#c6f6d5', text: '#2f855a' }
    };
    const typeColor = typeColors[user.user_type];
    
    const statusColors = {
        'active': { bg: '#c6f6d5', text: '#2f855a', icon: 'check-circle' },
        'inactive': { bg: '#fed7d7', text: '#c53030', icon: 'times-circle' },
        'suspended': { bg: '#feebc8', text: '#c05621', icon: 'pause-circle' }
    };
    const statusColor = statusColors[user.status.toLowerCase()];
    
    const userInitial = user.full_name.charAt(0).toUpperCase();
    const lastLogin = user.last_login ? new Date(user.last_login).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }) : 'Never';
    const joinedDate = new Date(user.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    
    return `
        <td style="padding: 20px 24px; vertical-align: middle;">
            <div style="display: flex; align-items: center; gap: 12px;">
                <div style="
                    width: 48px; height: 48px; border-radius: 12px;
                    background: linear-gradient(135deg, #667eea, #764ba2);
                    display: flex; align-items: center; justify-content: center;
                    color: white; font-weight: 700; font-size: 18px; flex-shrink: 0;
                ">${userInitial}</div>
                <div>
                    <div style="font-weight: 600; color: #2d3748; font-size: 15px; margin-bottom: 4px;">
                        ${escapeHtml(user.full_name)}
                    </div>
                    <div style="color: #718096; font-size: 13px;">
                        ${escapeHtml(user.email)}
                    </div>
                </div>
            </div>
        </td>
        <td style="padding: 20px 24px; vertical-align: middle;">
            <span style="
                background: ${typeColor.bg}; color: ${typeColor.text};
                padding: 6px 12px; border-radius: 20px; font-size: 12px;
                font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
            ">
                <i class="fas fa-${user.user_type === 'admin' ? 'crown' : (user.user_type === 'owner' ? 'key' : 'user')}" style="margin-right: 4px;"></i>
                ${user.user_type.charAt(0).toUpperCase() + user.user_type.slice(1)}
            </span>
        </td>
        <td style="padding: 20px 24px; vertical-align: middle;">
            <div style="color: #4a5568; font-weight: 500;">
                <i class="fas fa-phone" style="color: #667eea; margin-right: 8px;"></i>
                ${escapeHtml(user.phone)}
            </div>
        </td>
        <td style="padding: 20px 24px; vertical-align: middle;">
            <span style="
                background: ${statusColor.bg}; color: ${statusColor.text};
                padding: 8px 14px; border-radius: 20px; font-size: 12px;
                font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;
                display: inline-flex; align-items: center; gap: 6px;
            ">
                <i class="fas fa-${statusColor.icon}"></i>
                ${user.status.charAt(0).toUpperCase() + user.status.slice(1)}
            </span>
        </td>
        <td style="padding: 20px 24px; vertical-align: middle;">
            <div style="font-size: 13px;">
                <div style="color: #4a5568; margin-bottom: 4px;">
                    <strong>Last:</strong> ${lastLogin}
                </div>
                <div style="color: #718096;">
                    <strong>Joined:</strong> ${joinedDate}
                </div>
            </div>
        </td>
        <td style="padding: 20px 24px; vertical-align: middle; text-align: center;">
            ${generateActionButtons(user)}
        </td>
    `;
}

// Generate action buttons HTML
function generateActionButtons(user) {
    const buttons = [
        { action: 'view', icon: 'eye', bg: '#e3f2fd', color: '#1976d2', hover: '#bbdefb', title: 'View Details' },
        { action: 'edit', icon: 'edit', bg: '#e8f5e8', color: '#2e7d32', hover: '#c8e6c9', title: 'Edit User' }
    ];
    
    if (user.status === 'active') {
        buttons.push({ action: 'suspend', icon: 'pause', bg: '#fff3e0', color: '#f57c00', hover: '#ffcc02', title: 'Suspend User' });
    } else {
        buttons.push({ action: 'activate', icon: 'play', bg: '#e8f5e8', color: '#2e7d32', hover: '#c8e6c9', title: 'Activate User' });
    }
    
    buttons.push({ action: 'reset_password', icon: 'key', bg: '#f3e5f5', color: '#7b1fa2', hover: '#e1bee7', title: 'Reset Password' });
    
    if (user.user_type !== 'admin') {
        buttons.push({ action: 'delete', icon: 'trash', bg: '#ffebee', color: '#d32f2f', hover: '#ffcdd2', title: 'Delete User' });
    }
    
    return `
        <div style="display: flex; justify-content: center; gap: 6px; flex-wrap: wrap;">
            ${buttons.map(btn => `
                <button class="user-action" data-action="${btn.action}" data-user-id="${user.id}" 
                        title="${btn.title}" style="
                            padding: 8px 10px; background: ${btn.bg}; border: none;
                            border-radius: 8px; color: ${btn.color}; cursor: pointer;
                            transition: all 0.2s; font-size: 12px;
                        "
                        onmouseover="this.style.background='${btn.hover}'"
                        onmouseout="this.style.background='${btn.bg}'">
                    <i class="fas fa-${btn.icon}"></i>
                </button>
            `).join('')}
        </div>
    `;
}

// Update search summary
function updateSearchSummary(data) {
    const summary = document.getElementById('search-results-summary');
    const resultText = document.getElementById('search-results-text');
    const queryDisplay = document.getElementById('search-query-display');
    
    const { pagination, filters } = data;
    const hasFilters = filters.search || filters.type || filters.status;
    
    if (hasFilters) {
        resultText.textContent = `Found ${pagination.total_users} user${pagination.total_users !== 1 ? 's' : ''}`;
        
        let queryParts = [];
        if (filters.search) queryParts.push(`"${filters.search}"`);
        if (filters.type) queryParts.push(`Type: ${filters.type}`);
        if (filters.status) queryParts.push(`Status: ${filters.status}`);
        
        queryDisplay.textContent = queryParts.length ? `(${queryParts.join(', ')})` : '';
        summary.style.display = 'block';
    } else {
        hideSearchSummary();
    }
}

// Utility functions
function showLoading() {
    console.log('Showing loading indicator');
    const loadingDiv = document.getElementById('search-loading');
    const tableContainer = document.querySelector('.table-container');
    
    if (loadingDiv) {
        loadingDiv.style.display = 'block';
    } else {
        console.error('Loading div not found!');
    }
    
    if (tableContainer) {
        tableContainer.style.display = 'none';
    } else {
        console.error('Table container not found!');
    }
}

function hideLoading() {
    console.log('Hiding loading indicator');
    const loadingDiv = document.getElementById('search-loading');
    const tableContainer = document.querySelector('.table-container');
    
    if (loadingDiv) {
        loadingDiv.style.display = 'none';
    }
    
    if (tableContainer) {
        tableContainer.style.display = 'block';
    }
}

function hideSearchSummary() {
    document.getElementById('search-results-summary').style.display = 'none';
}

function showNoResults() {
    document.querySelector('.table-container').style.display = 'none';
    let noResultsDiv = document.getElementById('no-search-results');
    
    if (!noResultsDiv) {
        noResultsDiv = document.createElement('div');
        noResultsDiv.id = 'no-search-results';
        noResultsDiv.innerHTML = `
            <div style="
                text-align: center; padding: 80px 40px; background: white;
                border-radius: 16px; box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
            ">
                <div style="
                    width: 120px; height: 120px;
                    background: linear-gradient(135deg, #e2e8f0, #cbd5e0);
                    border-radius: 50%; display: flex; align-items: center;
                    justify-content: center; margin: 0 auto 24px;
                ">
                    <i class="fas fa-search" style="font-size: 3rem; color: #667eea;"></i>
                </div>
                <h3 style="color: #2d3748; margin-bottom: 12px; font-size: 24px;">No Users Found</h3>
                <p style="color: #718096; margin-bottom: 24px; font-size: 16px;">Try adjusting your search criteria or filters.</p>
                <button onclick="clearAllFilters()" style="
                    background: linear-gradient(135deg, #667eea, #764ba2); color: white;
                    border: none; padding: 12px 24px; border-radius: 10px;
                    font-weight: 600; cursor: pointer; display: inline-flex;
                    align-items: center; gap: 8px; font-size: 15px;
                    transition: transform 0.2s;
                "
                onmouseover="this.style.transform='translateY(-2px)'"
                onmouseout="this.style.transform='translateY(0)'">
                    <i class="fas fa-times-circle"></i> Clear Filters
                </button>
            </div>
        `;
        document.querySelector('.table-container').parentNode.insertBefore(noResultsDiv, document.querySelector('.table-container'));
    }
    
    noResultsDiv.style.display = 'block';
}

function hideNoResults() {
    const noResultsDiv = document.getElementById('no-search-results');
    if (noResultsDiv) {
        noResultsDiv.style.display = 'none';
    }
}

function showError(message) {
    console.error('Search error:', message);
    // You could show a toast notification here
}

function highlightSearchTerms(searchTerm) {
    if (!searchTerm || searchTerm.length < 2) return;
    
    const tbody = document.getElementById('users-table-body');
    const rows = tbody.querySelectorAll('tr');
    
    rows.forEach(row => {
        const cells = row.querySelectorAll('td');
        cells.forEach(cell => {
            if (cell.querySelector('.user-action')) return; // Skip action buttons
            
            let content = cell.innerHTML;
            // Remove previous highlights
            content = content.replace(/<mark class="search-highlight">(.*?)<\/mark>/gi, '$1');
            
            // Add new highlights
            const regex = new RegExp(`(${searchTerm.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')})`, 'gi');
            content = content.replace(regex, '<mark class="search-highlight" style="background-color: #fef08a; padding: 2px 4px; border-radius: 3px; font-weight: bold;">$1</mark>');
            
            cell.innerHTML = content;
        });
    });
}

function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

function clearAllFilters() {
    document.getElementById('user-type-filter').value = '';
    document.getElementById('user-status-filter').value = '';
    document.getElementById('user-search-input').value = '';
    
    currentSearchParams = {
        search: '',
        type: '',
        status: '',
        page: 1
    };
    
    updateSearchResults();
}

function updateSearchResults() {
    performSearch();
}

// Add missing toggleSearchTips function
function toggleSearchTips() {
    alert('Search Tips:\n\n• Type to search names, emails, or phone numbers\n• Use filters to narrow results\n• Search is case-insensitive\n• Results update as you type');
}

// Debug function to test search
function testSearch() {
    console.log('Testing search functionality...');
    console.log('Current search params:', currentSearchParams);
    performSearch();
}

// Simple test search function
function testSimpleSearch() {
    console.log('Running simple search test...');
    currentSearchParams.search = 'shehan';
    currentSearchParams.page = 1;
    performSearch();
}

// Make functions available globally for debugging and AJAX integration
window.testSearch = testSearch;
window.testSimpleSearch = testSimpleSearch;
window.performSearch = performSearch;
window.currentSearchParams = currentSearchParams;
window.updateSearchResults = updateSearchResults;
window.handleSearchInput = handleSearchInput;
window.handleFilterChange = handleFilterChange;
window.clearAllFilters = clearAllFilters; // Override the global one with the local one

// Add some debugging
console.log('User search script loaded successfully');
console.log('Available test functions: testSearch(), testSimpleSearch()');
console.log('You can run testSimpleSearch() in console to test search functionality');

// Notify that user management is ready
if (typeof window.onUserManagementReady === 'function') {
    window.onUserManagementReady();
}

// Add a ready flag
window.userManagementReady = true;

// Final test to make sure everything works
setTimeout(() => {
    console.log('🔥 Final check - Search system status:');
    console.log('- performSearch:', typeof performSearch);
    console.log('- currentSearchParams:', !!currentSearchParams);
    console.log('- DOM elements:', {
        searchInput: !!document.getElementById('user-search-input'),
        typeFilter: !!document.getElementById('user-type-filter'),
        statusFilter: !!document.getElementById('user-status-filter'),
        tableBody: !!document.getElementById('users-table-body')
    });
    console.log('✅ Search system fully loaded and ready!');
}, 500);
</script>

<?php
} catch (PDOException $e) {
    echo '<div style="text-align: center; padding: 50px;">
        <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
        <h3 style="color: #e53e3e;">Database Error</h3>
        <p>Unable to load users. Please try again later.</p>
    </div>';
}
?>