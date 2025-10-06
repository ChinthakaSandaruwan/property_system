// Simplified Admin Dashboard JavaScript for debugging
console.log('Loading simplified admin dashboard...');

class SimpleAdminDashboard {
    constructor() {
        console.log('Initializing SimpleAdminDashboard...');
        
        this.currentSection = 'dashboard';
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('main-content');
        this.toggleBtn = document.getElementById('toggle-btn');
        this.pageTitle = document.getElementById('page-title');
        this.loadingSpinner = document.getElementById('loading-spinner');
        
        // Check if elements exist
        console.log('Elements found:', {
            sidebar: !!this.sidebar,
            mainContent: !!this.mainContent,
            toggleBtn: !!this.toggleBtn,
            pageTitle: !!this.pageTitle,
            loadingSpinner: !!this.loadingSpinner
        });
        
        this.init();
        this.bindEvents();
    }

    init() {
        console.log('Admin Dashboard initialized');
        
        // Check if sidebar should be collapsed on small screens
        if (window.innerWidth <= 768) {
            this.sidebar.classList.add('mobile-closed');
        }
        
        // Handle initial navigation
        this.handleInitialNavigation();
    }
    
    handleInitialNavigation() {
        console.log('Handling initial navigation...');
        // Get section from URL hash
        const hash = window.location.hash.substring(1);
        console.log('Current hash:', hash);
        
        if (hash && hash !== '') {
            const validSections = ['dashboard', 'properties', 'bookings', 'users', 'payments', 'reports', 'settings'];
            if (validSections.includes(hash)) {
                console.log('Navigating to section from hash:', hash);
                this.navigateToSection(hash);
                return;
            }
        }
        
        // Default to dashboard
        console.log('Defaulting to dashboard section');
        this.navigateToSection('dashboard');
    }

    bindEvents() {
        console.log('Binding events...');
        
        // Sidebar toggle
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => {
                console.log('Toggle button clicked');
                this.toggleSidebar();
            });
        }

        // Navigation links
        const navLinks = document.querySelectorAll('.nav-link');
        console.log(`Found ${navLinks.length} navigation links`);
        
        navLinks.forEach((link, index) => {
            const section = link.getAttribute('data-section');
            console.log(`Binding nav link ${index}: ${section}`);
            
            link.addEventListener('click', (e) => {
                e.preventDefault();
                console.log(`Navigation clicked: ${section}`);
                
                if (section) {
                    this.navigateToSection(section);
                } else {
                    console.error('No section found for link');
                }
            });
        });

        // Window resize handler
        window.addEventListener('resize', () => {
            if (window.innerWidth <= 768) {
                this.sidebar.classList.remove('collapsed');
                this.sidebar.classList.add('mobile-closed');
            } else {
                this.sidebar.classList.remove('mobile-closed');
            }
        });

        // Hash change handler
        window.addEventListener('hashchange', () => {
            console.log('Hash changed, handling navigation...');
            this.handleInitialNavigation();
        });

        // Mobile menu overlay click
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !this.sidebar.contains(e.target) && 
                !this.toggleBtn.contains(e.target) && 
                this.sidebar.classList.contains('mobile-open')) {
                this.sidebar.classList.remove('mobile-open');
            }
        });
        
        console.log('Events bound successfully');
    }

    toggleSidebar() {
        console.log('Toggling sidebar');
        if (window.innerWidth <= 768) {
            this.sidebar.classList.toggle('mobile-open');
        } else {
            this.sidebar.classList.toggle('collapsed');
        }
    }

    navigateToSection(section) {
        console.log(`Navigating to section: ${section}`);
        
        if (this.currentSection === section) {
            console.log('Already on this section');
            return;
        }

        // Update URL hash (only if it's different to avoid triggering hashchange)
        if (window.location.hash !== `#${section}`) {
            window.location.hash = section;
        }

        // Update navigation active state
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const targetLink = document.querySelector(`[data-section="${section}"]`);
        if (targetLink) {
            targetLink.parentElement.classList.add('active');
            console.log('Updated active nav item');
        } else {
            console.error(`No link found for section: ${section}`);
        }

        // Update page title
        const titles = {
            'dashboard': 'Dashboard',
            'properties': 'Properties Management',
            'bookings': 'Bookings Management',
            'users': 'User Management',
            'payments': 'Payments',
            'reports': 'Reports & Analytics',
            'settings': 'Settings'
        };
        
        if (this.pageTitle) {
            this.pageTitle.textContent = titles[section] || 'Dashboard';
            console.log('Updated page title');
        }

        // Hide all content sections
        document.querySelectorAll('.content-section').forEach(content => {
            content.classList.remove('active');
            content.style.display = 'none';
        });

        // Load section content
        this.loadSection(section);
        this.currentSection = section;

        // Close mobile menu after navigation
        if (window.innerWidth <= 768) {
            this.sidebar.classList.remove('mobile-open');
        }
        
        console.log(`Navigation completed: ${section}`);
    }

    async loadSection(section) {
        console.log(`Loading section: ${section}`);
        this.showLoading();

        try {
            if (section === 'dashboard') {
                this.showDashboardContent();
                await this.loadDashboardStats();
            } else {
                await this.loadSectionContent(section);
            }
        } catch (error) {
            console.error(`Error loading section ${section}:`, error);
            this.showError(`Failed to load ${section} content.`);
        } finally {
            this.hideLoading();
        }
    }

    async showDashboardContent() {
        console.log('Showing dashboard content');
        const dashboardContent = document.getElementById('dashboard-content');
        if (dashboardContent) {
            dashboardContent.style.display = 'block';
            dashboardContent.classList.add('active');
            console.log('Dashboard content shown');
        } else {
            console.error('Dashboard content element not found');
        }
    }

    async loadSectionContent(section) {
        console.log(`Loading content for section: ${section}`);
        try {
            const response = await fetch(`api/${section}_content.php`);
            
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            
            const html = await response.text();
            
            const contentElement = document.getElementById(`${section}-content`);
            if (contentElement) {
                contentElement.innerHTML = html;
                contentElement.style.display = 'block';
                contentElement.classList.add('active');
                console.log(`Content loaded for ${section}`);
                
                // Initialize section-specific functionality
                this.initializeSectionFeatures(section);
            } else {
                console.error(`Content element not found: ${section}-content`);
            }
            
        } catch (error) {
            console.error(`Failed to load ${section} content:`, error);
            this.showError(`Failed to load ${section} content.`);
        }
    }
    
    initializeSectionFeatures(section) {
        console.log(`Initializing features for section: ${section}`);
        
        switch (section) {
            case 'properties':
                this.initializePropertiesFeatures();
                break;
            case 'users':
                this.initializeUsersFeatures();
                break;
            case 'payments':
                this.initializePaymentsFeatures();
                break;
            case 'bookings':
                this.initializeBookingsFeatures();
                break;
        }
    }
    
    initializePropertiesFeatures() {
        console.log('Initializing properties features');
        // Basic property features without complex modals
        document.querySelectorAll('.property-action').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const action = e.target.closest('.property-action')?.getAttribute('data-action');
                const propertyId = e.target.closest('.property-action')?.getAttribute('data-property-id');
                console.log(`Property action: ${action} for property ${propertyId}`);
                
                if (action && propertyId) {
                    this.handleBasicPropertyAction(action, propertyId);
                }
            });
        });
        
        // Add New Property button
        const addPropertyBtn = document.getElementById('add-property-btn');
        if (addPropertyBtn) {
            addPropertyBtn.addEventListener('click', () => {
                console.log('Add property button clicked');
                this.showAddPropertyModal();
            });
        }
    }
    
    async handleBasicPropertyAction(action, propertyId) {
        console.log(`Handling property action: ${action} for property ${propertyId}`);
        
        if (action === 'view') {
            await this.showPropertyDetailsModal(propertyId);
            return;
        }
        
        if (action === 'edit') {
            await this.showEditPropertyModal(propertyId);
            return;
        }
        
        // For other actions, ask for confirmation
        const actionMessages = {
            'delete': 'delete this property? This action cannot be undone.',
            'approve': 'approve this property?',
            'reject': 'reject this property?',
            'suspend': 'suspend this property?',
            'activate': 'activate this property?',
            'mark-available': 'mark this property as available?',
            'mark-unavailable': 'mark this property as unavailable?'
        };
        
        const message = actionMessages[action] || `${action} this property?`;
        if (!confirm(`Are you sure you want to ${message}`)) {
            return;
        }
        
        try {
            const response = await fetch('api/property_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    property_id: propertyId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(result.message || 'Action completed successfully');
                this.loadSection('properties'); // Reload
            } else {
                this.showErrorMessage('Action failed: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Property action error:', error);
            this.showErrorMessage('An error occurred while processing the action');
        }
    }
    
    initializeUsersFeatures() {
        console.log('Initializing users features');
        
        // Add a small delay to ensure DOM is fully loaded
        setTimeout(() => {
            console.log('Delayed initialization of user features');
            
            // Check what elements are available
            const userActions = document.querySelectorAll('.user-action');
            const addBtn = document.getElementById('add-user-btn');
            console.log(`Found ${userActions.length} user action buttons`);
            console.log('Add user button exists:', !!addBtn);
            
            // Add event listeners for user actions
            this.bindUserActionEvents();
            
            // Load user statistics
            this.loadUserStatistics();
        }, 100);
    }
    
    bindUserActionEvents() {
        console.log('Binding user action events with event delegation');
        
        // Remove any existing listeners for user actions to prevent duplicates
        document.removeEventListener('click', this.userActionHandler);
        
        // Use event delegation for dynamically loaded content
        this.userActionHandler = async (e) => {
            // Handle user action buttons
            const userActionBtn = e.target.closest('.user-action');
            if (userActionBtn) {
                e.preventDefault();
                e.stopPropagation();
                
                const action = userActionBtn.getAttribute('data-action');
                const userId = userActionBtn.getAttribute('data-user-id');
                
                if (!action || !userId) {
                    console.error('Missing action or user ID');
                    return;
                }
                
                console.log(`User action: ${action} for user ${userId}`);
                
                switch (action) {
                    case 'view':
                        await this.showUserDetailsModal(userId);
                        break;
                    case 'edit':
                        await this.showEditUserModal(userId);
                        break;
                    case 'suspend':
                    case 'activate':
                    case 'delete':
                    case 'reset_password':
                        await this.handleUserAction(action, userId);
                        break;
                    default:
                        console.warn(`Unknown user action: ${action}`);
                }
                return;
            }
            
            // Handle add user button
            const addUserBtn = e.target.closest('#add-user-btn');
            if (addUserBtn) {
                e.preventDefault();
                console.log('Add user button clicked via delegation');
                this.showAddUserModal();
                return;
            }
        };
        
        // Add the event listener to the document for delegation
        document.addEventListener('click', this.userActionHandler);
        
        // Also handle add user button with event delegation
        // This will be handled by the same event handler if needed, but let's also check specifically
        setTimeout(() => {
            const addUserBtn = document.getElementById('add-user-btn');
            console.log('Add user button found:', !!addUserBtn);
            if (addUserBtn && !addUserBtn.hasAttribute('data-event-bound')) {
                addUserBtn.setAttribute('data-event-bound', 'true');
                addUserBtn.addEventListener('click', (e) => {
                    e.preventDefault();
                    console.log('Add user button clicked');
                    this.showAddUserModal();
                });
            }
        }, 100);
    }
    
    async handleUserAction(action, userId) {
        const confirmMessage = {
            'suspend': 'Are you sure you want to suspend this user?',
            'activate': 'Are you sure you want to activate this user?',
            'delete': 'Are you sure you want to delete this user? This action cannot be undone.',
            'reset_password': 'Are you sure you want to reset this user\'s password? They will receive a new temporary password via email.'
        };
        
        if (confirmMessage[action] && !confirm(confirmMessage[action])) {
            return;
        }
        
        try {
            const response = await fetch('api/user_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: action,
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(result.message);
                this.loadSection('users'); // Reload users list
            } else {
                this.showErrorMessage(result.message || 'Action failed');
            }
        } catch (error) {
            console.error('User action error:', error);
            this.showErrorMessage('An error occurred while performing the action');
        }
    }
    
    async showUserDetailsModal(userId) {
        try {
            const response = await fetch('api/user_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'view',
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.displayUserDetailsModal(result.data);
            } else {
                this.showErrorMessage('Failed to load user details');
            }
        } catch (error) {
            console.error('Error loading user details:', error);
            this.showErrorMessage('An error occurred while loading user details');
        }
    }
    
    displayUserDetailsModal(user) {
        const modalHtml = `
            <div class="property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                padding: 20px;
                box-sizing: border-box;
            ">
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 0;
                    max-width: 700px;
                    width: 100%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <!-- Header -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px 12px 0 0;">
                        <button class="close-modal" style="
                            position: absolute;
                            top: 15px;
                            right: 20px;
                            background: rgba(255,255,255,0.2);
                            border: none;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            font-size: 18px;
                            cursor: pointer;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">×</button>
                        <h2 style="margin: 0; font-size: 24px;">
                            <i class="fas fa-user"></i> User Details
                        </h2>
                        <p style="margin: 10px 0 0; opacity: 0.9; font-size: 16px;">
                            ${user.full_name}
                        </p>
                    </div>
                    
                    <!-- Content -->
                    <div style="padding: 30px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                            <!-- Left Column -->
                            <div>
                                <h3 style="color: #2d3748; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-info-circle"></i> Basic Information
                                </h3>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Full Name:</label>
                                    <p style="margin: 5px 0;">${user.full_name}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Email:</label>
                                    <p style="margin: 5px 0;">${user.email}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Phone:</label>
                                    <p style="margin: 5px 0;">${user.phone} ${user.is_phone_verified ? '<span style="color: #48bb78;"><i class="fas fa-check-circle"></i> Verified</span>' : '<span style="color: #e53e3e;"><i class="fas fa-times-circle"></i> Unverified</span>'}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">User Type:</label>
                                    <p style="margin: 5px 0;"><span class="status-badge status-${user.user_type}">${user.user_type.charAt(0).toUpperCase() + user.user_type.slice(1)}</span></p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Status:</label>
                                    <p style="margin: 5px 0;"><span class="status-badge status-${user.status.toLowerCase()}">${user.status.charAt(0).toUpperCase() + user.status.slice(1)}</span></p>
                                </div>
                            </div>
                            
                            <!-- Right Column -->
                            <div>
                                <h3 style="color: #2d3748; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-chart-bar"></i> Activity Stats
                                </h3>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Properties:</label>
                                    <p style="margin: 5px 0;">${user.properties_count || 0}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Bookings:</label>
                                    <p style="margin: 5px 0;">${user.bookings_count || 0}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Property Visits:</label>
                                    <p style="margin: 5px 0;">${user.visits_count || 0}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Member Since:</label>
                                    <p style="margin: 5px 0;">${new Date(user.created_at).toLocaleDateString()}</p>
                                </div>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="font-weight: bold; color: #4a5568;">Last Updated:</label>
                                    <p style="margin: 5px 0;">${new Date(user.updated_at).toLocaleDateString()}</p>
                                </div>
                            </div>
                        </div>
                        
                        ${user.address || user.city || user.state ? `
                            <div style="margin-top: 20px;">
                                <h3 style="color: #2d3748; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-map-marker-alt"></i> Address Information
                                </h3>
                                
                                ${user.address ? `<p><strong>Address:</strong> ${user.address}</p>` : ''}
                                ${user.city ? `<p><strong>City:</strong> ${user.city}</p>` : ''}
                                ${user.state ? `<p><strong>State:</strong> ${user.state}</p>` : ''}
                                ${user.zip_code ? `<p><strong>ZIP Code:</strong> ${user.zip_code}</p>` : ''}
                            </div>
                        ` : ''}
                        
                        <!-- Action Buttons -->
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                            <button class="edit-user-btn" data-user-id="${user.id}" style="
                                margin-right: 10px;
                                padding: 12px 25px;
                                background: #667eea;
                                color: white;
                                border: none;
                                border-radius: 6px;
                                font-weight: 600;
                                cursor: pointer;
                                font-size: 14px;
                            ">
                                <i class="fas fa-edit"></i> Edit User
                            </button>
                            <button class="close-modal" style="
                                padding: 12px 25px;
                                background: #e2e8f0;
                                color: #4a5568;
                                border: none;
                                border-radius: 6px;
                                font-weight: 600;
                                cursor: pointer;
                                font-size: 14px;
                            ">
                                <i class="fas fa-times"></i> Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Add event listeners
        modalElement.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        // Edit user button
        const editBtn = modalElement.querySelector('.edit-user-btn');
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
                this.showEditUserModal(user.id);
            });
        }
        
        // Close on overlay click
        modalElement.querySelector('.property-modal-overlay').addEventListener('click', (e) => {
            if (e.target.classList.contains('property-modal-overlay')) {
                document.body.removeChild(modalElement);
            }
        });
    }
    
    async loadUserStatistics() {
        try {
            const response = await fetch('api/user_statistics.php');
            const result = await response.json();
            
            if (result.success) {
                this.displayUserStatistics(result.data);
            }
        } catch (error) {
            console.error('Error loading user statistics:', error);
        }
    }
    
    displayUserStatistics(stats) {
        // Update statistics cards if they exist
        const updateStat = (id, value) => {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        };
        
        updateStat('total-users', stats.total_users);
        updateStat('active-users', stats.user_statuses?.active || 0);
        updateStat('pending-users', stats.user_statuses?.pending || 0);
        updateStat('verified-users', stats.phone_verification?.verified || 0);
    }
    
    showAddUserModal() {
        const modalHtml = `
            <div class="property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                padding: 20px;
                box-sizing: border-box;
            ">
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 0;
                    max-width: 600px;
                    width: 100%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <!-- Header -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px 12px 0 0;">
                        <button class="close-modal" style="
                            position: absolute;
                            top: 15px;
                            right: 20px;
                            background: rgba(255,255,255,0.2);
                            border: none;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            font-size: 18px;
                            cursor: pointer;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">×</button>
                        <h2 style="margin: 0; font-size: 24px;">
                            <i class="fas fa-user-plus"></i> Add New User
                        </h2>
                    </div>
                    
                    <!-- Form -->
                    <div style="padding: 30px;">
                        <form id="add-user-form">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <!-- Left Column -->
                                <div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Full Name *</label>
                                        <input type="text" name="full_name" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Email *</label>
                                        <input type="email" name="email" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Phone *</label>
                                        <input type="tel" name="phone" required pattern="[0]{1}[7]{1}[01245678]{1}[0-9]{7}" placeholder="0771234567" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                        <small style="color: #666; font-size: 12px;">Sri Lankan mobile number format</small>
                                    </div>
                                </div>
                                
                                <!-- Right Column -->
                                <div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">User Type *</label>
                                        <select name="user_type" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="">Select User Type...</option>
                                            <option value="customer">Customer</option>
                                            <option value="owner">Owner</option>
                                            <option value="admin">Admin</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Password *</label>
                                        <input type="password" name="password" required minlength="8" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                        <small style="color: #666; font-size: 12px;">Minimum 8 characters</small>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Status</label>
                                        <select name="status" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="active">Active</option>
                                            <option value="inactive">Inactive</option>
                                            <option value="suspended">Suspended</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Optional Address Fields -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                                <h3 style="color: #2d3748; margin-bottom: 15px;">
                                    <i class="fas fa-map-marker-alt"></i> Address Information (Optional)
                                </h3>
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Address</label>
                                        <input type="text" name="address" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">City</label>
                                        <input type="text" name="city" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">ZIP Code</label>
                                        <input type="text" name="zip_code" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                                <button type="submit" style="
                                    margin-right: 10px;
                                    padding: 12px 25px;
                                    background: linear-gradient(135deg, #667eea, #764ba2);
                                    color: white;
                                    border: none;
                                    border-radius: 6px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 14px;
                                ">
                                    <i class="fas fa-save"></i> Create User
                                </button>
                                <button type="button" class="close-modal" style="
                                    padding: 12px 25px;
                                    background: #e2e8f0;
                                    color: #4a5568;
                                    border: none;
                                    border-radius: 6px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 14px;
                                ">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Initialize modal functionality
        this.initializeAddUserModal(modalElement);
    }
    
    initializeAddUserModal(modalElement) {
        // Close modal handlers
        modalElement.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        // Close on overlay click
        const overlay = modalElement.querySelector('.property-modal-overlay');
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(modalElement);
            }
        });
        
        // Form submission
        const form = modalElement.querySelector('#add-user-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const userData = {
                full_name: formData.get('full_name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                user_type: formData.get('user_type'),
                password: formData.get('password'),
                status: formData.get('status'),
                address: formData.get('address'),
                city: formData.get('city'),
                zip_code: formData.get('zip_code')
            };
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            try {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
                
                const response = await fetch('api/user_crud.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify(userData)
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showSuccessMessage('User created successfully!');
                    document.body.removeChild(modalElement);
                    this.loadSection('users'); // Reload users list
                } else {
                    this.showErrorMessage(result.message || 'Failed to create user');
                    if (result.errors) {
                        // Show specific validation errors
                        let errorMessage = 'Validation errors:\n';
                        Object.values(result.errors).forEach(error => {
                            errorMessage += '• ' + error + '\n';
                        });
                        alert(errorMessage);
                    }
                }
            } catch (error) {
                console.error('Create user error:', error);
                this.showErrorMessage('An error occurred while creating the user');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    async showEditUserModal(userId) {
        try {
            // First, get the user data
            const response = await fetch('api/user_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'edit',
                    user_id: userId
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.displayEditUserModal(result.data);
            } else {
                this.showErrorMessage('Failed to load user data for editing');
            }
        } catch (error) {
            console.error('Error loading user for editing:', error);
            this.showErrorMessage('An error occurred while loading user data');
        }
    }
    
    displayEditUserModal(user) {
        const modalHtml = `
            <div class="property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                padding: 20px;
                box-sizing: border-box;
            ">
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 0;
                    max-width: 600px;
                    width: 100%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <!-- Header -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px 12px 0 0;">
                        <button class="close-modal" style="
                            position: absolute;
                            top: 15px;
                            right: 20px;
                            background: rgba(255,255,255,0.2);
                            border: none;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            font-size: 18px;
                            cursor: pointer;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">×</button>
                        <h2 style="margin: 0; font-size: 24px;">
                            <i class="fas fa-user-edit"></i> Edit User
                        </h2>
                        <p style="margin: 10px 0 0; opacity: 0.9; font-size: 16px;">
                            ${user.full_name}
                        </p>
                    </div>
                    
                    <!-- Form -->
                    <div style="padding: 30px;">
                        <form id="edit-user-form">
                            <input type="hidden" name="user_id" value="${user.id}">
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <!-- Left Column -->
                                <div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Full Name *</label>
                                        <input type="text" name="full_name" value="${user.full_name || ''}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Email *</label>
                                        <input type="email" name="email" value="${user.email || ''}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Phone *</label>
                                        <input type="tel" name="phone" value="${user.phone || ''}" required pattern="[0]{1}[7]{1}[01245678]{1}[0-9]{7}" placeholder="0771234567" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                        <small style="color: #666; font-size: 12px;">Sri Lankan mobile number format</small>
                                    </div>
                                </div>
                                
                                <!-- Right Column -->
                                <div>
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">User Type *</label>
                                        <select name="user_type" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="customer" ${user.user_type === 'customer' ? 'selected' : ''}>Customer</option>
                                            <option value="owner" ${user.user_type === 'owner' ? 'selected' : ''}>Owner</option>
                                            <option value="admin" ${user.user_type === 'admin' ? 'selected' : ''}>Admin</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Status *</label>
                                        <select name="status" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="active" ${user.status === 'active' ? 'selected' : ''}>Active</option>
                                            <option value="inactive" ${user.status === 'inactive' ? 'selected' : ''}>Inactive</option>
                                            <option value="suspended" ${user.status === 'suspended' ? 'selected' : ''}>Suspended</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 15px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">New Password</label>
                                        <input type="password" name="password" minlength="8" placeholder="Leave blank to keep current password" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                        <small style="color: #666; font-size: 12px;">Leave blank to keep current password</small>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Address Fields -->
                            <div style="margin-top: 20px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                                <h3 style="color: #2d3748; margin-bottom: 15px;">
                                    <i class="fas fa-map-marker-alt"></i> Address Information
                                </h3>
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr 1fr; gap: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Address</label>
                                        <input type="text" name="address" value="${user.address || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">City</label>
                                        <input type="text" name="city" value="${user.city || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">State</label>
                                        <input type="text" name="state" value="${user.state || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">ZIP Code</label>
                                        <input type="text" name="zip_code" value="${user.zip_code || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                                <button type="submit" style="
                                    margin-right: 10px;
                                    padding: 12px 25px;
                                    background: linear-gradient(135deg, #667eea, #764ba2);
                                    color: white;
                                    border: none;
                                    border-radius: 6px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 14px;
                                ">
                                    <i class="fas fa-save"></i> Update User
                                </button>
                                <button type="button" class="close-modal" style="
                                    padding: 12px 25px;
                                    background: #e2e8f0;
                                    color: #4a5568;
                                    border: none;
                                    border-radius: 6px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 14px;
                                ">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Initialize modal functionality
        this.initializeEditUserModal(modalElement);
    }
    
    initializeEditUserModal(modalElement) {
        // Close modal handlers
        modalElement.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        // Close on overlay click
        const overlay = modalElement.querySelector('.property-modal-overlay');
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(modalElement);
            }
        });
        
        // Form submission
        const form = modalElement.querySelector('#edit-user-form');
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = new FormData(form);
            const userData = {
                user_id: formData.get('user_id'),
                full_name: formData.get('full_name'),
                email: formData.get('email'),
                phone: formData.get('phone'),
                user_type: formData.get('user_type'),
                status: formData.get('status'),
                address: formData.get('address'),
                city: formData.get('city'),
                state: formData.get('state'),
                zip_code: formData.get('zip_code')
            };
            
            // Only include password if it's provided
            const password = formData.get('password');
            if (password && password.trim()) {
                userData.password = password;
            }
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            try {
                // Show loading state
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                
                const response = await fetch('api/user_actions.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'update',
                        ...userData
                    })
                });
                
                const result = await response.json();
                
                if (result.success) {
                    this.showSuccessMessage('User updated successfully!');
                    document.body.removeChild(modalElement);
                    this.loadSection('users'); // Reload users list
                } else {
                    this.showErrorMessage(result.message || 'Failed to update user');
                    if (result.errors) {
                        // Show specific validation errors
                        let errorMessage = 'Validation errors:\n';
                        Object.values(result.errors).forEach(error => {
                            errorMessage += '• ' + error + '\n';
                        });
                        alert(errorMessage);
                    }
                }
            } catch (error) {
                console.error('Update user error:', error);
                this.showErrorMessage('An error occurred while updating the user');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    initializePaymentsFeatures() {
        console.log('Initializing payments features');
        // Basic implementation
    }
    
    initializeBookingsFeatures() {
        console.log('Initializing bookings features');
        // Basic implementation
    }

    async loadDashboardStats() {
        console.log('Loading dashboard stats');
        try {
            const response = await fetch('api/dashboard_stats.php');
            const stats = await response.json();
            
            if (stats.success) {
                const elements = {
                    'total-properties': stats.data.total_properties || '0',
                    'total-bookings': stats.data.total_bookings || '0',
                    'total-users': stats.data.total_users || '0',
                    'monthly-revenue': `$${stats.data.monthly_revenue || '0'}`
                };
                
                Object.entries(elements).forEach(([id, value]) => {
                    const element = document.getElementById(id);
                    if (element) {
                        element.textContent = value;
                    }
                });
                
                console.log('Dashboard stats loaded');
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    showLoading() {
        if (this.loadingSpinner) {
            this.loadingSpinner.style.display = 'flex';
        }
    }

    hideLoading() {
        if (this.loadingSpinner) {
            this.loadingSpinner.style.display = 'none';
        }
    }

    showAddPropertyModal() {
        console.log('Showing add property modal');
        
        const modalHtml = `
            <div class="property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.7);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            ">
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 30px;
                    max-width: 800px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <button class="close-modal" style="
                        position: absolute;
                        top: 15px;
                        right: 20px;
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: #666;
                    ">×</button>
                    
                    <h2 style="margin-bottom: 20px; color: #2d3748;">Add New Property</h2>
                    
                    <form id="add-property-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                            <!-- Left Column -->
                            <div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Owner *</label>
                                    <select name="owner_id" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        <option value="">Select Property Owner...</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Title *</label>
                                    <input type="text" name="title" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Type *</label>
                                    <select name="property_type" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        <option value="">Select Type...</option>
                                        <option value="apartment">Apartment</option>
                                        <option value="house">House</option>
                                        <option value="villa">Villa</option>
                                        <option value="studio">Studio</option>
                                        <option value="commercial">Commercial</option>
                                    </select>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bedrooms *</label>
                                        <input type="number" name="bedrooms" required min="0" max="10" value="1" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms *</label>
                                        <input type="number" name="bathrooms" required min="1" max="10" value="1" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Area (sqft)</label>
                                        <input type="number" name="area_sqft" min="100" max="10000" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Monthly Rent (LKR) *</label>
                                    <input type="number" name="rent_amount" required min="5000" step="100" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Security Deposit (LKR) *</label>
                                    <input type="number" name="security_deposit" required min="5000" step="100" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Address *</label>
                                    <textarea name="address" rows="3" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;"></textarea>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">City *</label>
                                        <input type="text" name="city" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">State *</label>
                                        <input type="text" name="state" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">ZIP Code</label>
                                    <input type="text" name="zip_code" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Contact Phone Number *</label>
                                    <input type="tel" name="contact_phone" required 
                                           pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" 
                                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;"
                                           placeholder="0771234567">
                                    <small style="color: #666; font-size: 0.875rem;">Enter Sri Lankan mobile number</small>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                                    <textarea name="description" rows="4" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;"></textarea>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Status</label>
                                    <select name="status" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        <option value="pending">Pending Review</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="rented">Rented</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Property Images Section -->
                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                <i class="fas fa-images"></i> Property Images
                            </h3>
                            
                            <div class="form-group" style="margin-bottom: 20px;">
                                <label style="display: block; margin-bottom: 10px; font-weight: 500;">Upload Images *</label>
                                <div style="border: 2px dashed #e2e8f0; border-radius: 8px; padding: 40px; text-align: center; background: #f9fafb; transition: all 0.3s ease;" id="image-upload-area">
                                    <input type="file" id="property-images" name="property_images[]" multiple accept="image/*" style="display: none;">
                                    <div id="upload-prompt">
                                        <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 15px;"></i>
                                        <p style="margin: 0 0 10px; color: #4a5568; font-size: 16px;">Drop images here or click to browse</p>
                                        <p style="margin: 0; color: #718096; font-size: 14px;">Support JPG, PNG, WebP (Max: 5MB per image, Up to 10 images)</p>
                                        <button type="button" id="browse-images-btn" style="margin-top: 15px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                            <i class="fas fa-folder-open"></i> Browse Images
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Image Preview Area -->
                                <div id="image-preview-container" style="margin-top: 20px; display: none;">
                                    <h4 style="color: #4a5568; margin-bottom: 15px;">Selected Images:</h4>
                                    <div id="image-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;"></div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Amenities Section -->
                        <div style="margin-bottom: 30px;">
                            <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                <i class="fas fa-star"></i> Amenities
                            </h3>
                            
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="WiFi" style="margin-right: 10px;">
                                    <i class="fas fa-wifi" style="margin-right: 8px; color: #667eea;"></i> WiFi
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Air Conditioning" style="margin-right: 10px;">
                                    <i class="fas fa-snowflake" style="margin-right: 8px; color: #667eea;"></i> Air Conditioning
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Parking" style="margin-right: 10px;">
                                    <i class="fas fa-car" style="margin-right: 8px; color: #667eea;"></i> Parking
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Swimming Pool" style="margin-right: 10px;">
                                    <i class="fas fa-swimming-pool" style="margin-right: 8px; color: #667eea;"></i> Swimming Pool
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Gym" style="margin-right: 10px;">
                                    <i class="fas fa-dumbbell" style="margin-right: 8px; color: #667eea;"></i> Gym
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Security" style="margin-right: 10px;">
                                    <i class="fas fa-shield-alt" style="margin-right: 8px; color: #667eea;"></i> Security
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Garden" style="margin-right: 10px;">
                                    <i class="fas fa-seedling" style="margin-right: 8px; color: #667eea;"></i> Garden
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Elevator" style="margin-right: 10px;">
                                    <i class="fas fa-arrows-alt-v" style="margin-right: 8px; color: #667eea;"></i> Elevator
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Balcony" style="margin-right: 10px;">
                                    <i class="fas fa-mountain" style="margin-right: 8px; color: #667eea;"></i> Balcony
                                </label>
                                <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer;">
                                    <input type="checkbox" name="amenities[]" value="Furnished" style="margin-right: 10px;">
                                    <i class="fas fa-couch" style="margin-right: 8px; color: #667eea;"></i> Furnished
                                </label>
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 6px; margin-right: 10px;">
                                <i class="fas fa-save"></i> Create Property
                            </button>
                            <button type="button" class="close-modal" style="padding: 12px 30px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 6px;">
                                Cancel
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Load property owners
        this.loadPropertyOwners(modalElement);
        
        // Initialize image upload functionality
        this.initializeImageUpload(modalElement);
        
        // Add event listeners
        const closeButtons = modalElement.querySelectorAll('.close-modal');
        const overlay = modalElement.querySelector('.property-modal-overlay');
        const form = modalElement.querySelector('#add-property-form');
        
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(modalElement);
            }
        });
        
        // Prevent modal from closing when clicking inside
        const modalContent = modalElement.querySelector('.property-modal');
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        
        // Handle form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            try {
                await this.handlePropertyCreation(form, modalElement);
            } catch (error) {
                console.error('Form submission error:', error);
                alert('An error occurred while submitting the form');
            } finally {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
        
        // Auto-calculate security deposit
        const rentInput = form.querySelector('input[name="rent_amount"]');
        const depositInput = form.querySelector('input[name="security_deposit"]');
        rentInput.addEventListener('input', function(e) {
            const rentAmount = parseFloat(e.target.value);
            if (rentAmount > 0 && !depositInput.value) {
                depositInput.value = rentAmount * 2;
            }
        });
    }
    
    async loadPropertyOwners(modalElement) {
        console.log('Loading property owners');
        try {
            const response = await fetch('api/get_property_owners.php');
            const result = await response.json();
            
            const ownerSelect = modalElement.querySelector('select[name="owner_id"]');
            
            if (result.success && result.data) {
                result.data.forEach(owner => {
                    const option = document.createElement('option');
                    option.value = owner.id;
                    option.textContent = `${owner.full_name} (${owner.email})`;
                    ownerSelect.appendChild(option);
                });
                console.log(`Loaded ${result.data.length} property owners`);
            }
        } catch (error) {
            console.error('Error loading property owners:', error);
        }
    }
    
    async handlePropertyCreation(form, modalElement) {
        console.log('Handling property creation');
        
        const formData = new FormData(form);
        
        // Add amenities as a JSON string
        const amenities = [];
        const amenityInputs = form.querySelectorAll('input[name="amenities[]"]');
        amenityInputs.forEach(input => {
            if (input.checked) {
                amenities.push(input.value);
            }
        });
        formData.set('amenities', JSON.stringify(amenities));
        
        // Validate images
        const imageInput = form.querySelector('#property-images');
        if (imageInput.files.length === 0) {
            alert('Please select at least one image for the property.');
            return;
        }
        
        console.log('Form data prepared with', imageInput.files.length, 'images');
        
        try {
            const response = await fetch('api/admin_add_property_with_images.php', {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            
            if (result.success) {
                alert('Property created successfully with images!');
                document.body.removeChild(modalElement);
                this.loadSection('properties'); // Reload properties list
            } else {
                alert('Failed to create property: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Property creation error:', error);
            alert('An error occurred while creating the property');
        }
    }

    initializeImageUpload(modalElement) {
        const imageUploadArea = modalElement.querySelector('#image-upload-area');
        const imageInput = modalElement.querySelector('#property-images');
        const browseBtn = modalElement.querySelector('#browse-images-btn');
        const previewContainer = modalElement.querySelector('#image-preview-container');
        const previewGrid = modalElement.querySelector('#image-preview-grid');
        
        let selectedFiles = [];
        
        // Browse button click handler
        browseBtn.addEventListener('click', () => imageInput.click());
        
        // Click on upload area to browse
        imageUploadArea.addEventListener('click', (e) => {
            if (e.target === imageUploadArea || e.target.closest('#upload-prompt')) {
                imageInput.click();
            }
        });
        
        // Drag and drop handlers
        imageUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#667eea';
            imageUploadArea.style.backgroundColor = '#edf2f7';
        });
        
        imageUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#e2e8f0';
            imageUploadArea.style.backgroundColor = '#f9fafb';
        });
        
        imageUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#e2e8f0';
            imageUploadArea.style.backgroundColor = '#f9fafb';
            
            const files = Array.from(e.dataTransfer.files);
            this.handleImageFiles(files, selectedFiles, previewContainer, previewGrid, imageInput);
        });
        
        // File input change handler
        imageInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            this.handleImageFiles(files, selectedFiles, previewContainer, previewGrid, imageInput);
        });
    }
    
    handleImageFiles(files, selectedFiles, previewContainer, previewGrid, imageInput) {
        const validFiles = files.filter(file => {
            // Check file type
            const validTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!validTypes.includes(file.type)) {
                alert(`Invalid file type: ${file.name}. Please select JPG, PNG, or WebP images.`);
                return false;
            }
            
            // Check file size (5MB limit)
            const maxSize = 5 * 1024 * 1024; // 5MB in bytes
            if (file.size > maxSize) {
                alert(`File too large: ${file.name}. Please select images under 5MB.`);
                return false;
            }
            
            return true;
        });
        
        // Add new valid files to selected files (max 10 total)
        validFiles.forEach(file => {
            if (selectedFiles.length < 10) {
                selectedFiles.push(file);
            } else {
                alert('Maximum 10 images allowed.');
                return;
            }
        });
        
        // Update file input with selected files
        this.updateFileInput(selectedFiles, imageInput);
        
        // Update preview
        this.updateImagePreview(selectedFiles, previewContainer, previewGrid);
    }
    
    updateFileInput(selectedFiles, imageInput) {
        // Create new FileList
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        imageInput.files = dt.files;
    }
    
    updateImagePreview(selectedFiles, previewContainer, previewGrid) {
        if (selectedFiles.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }
        
        previewContainer.style.display = 'block';
        previewGrid.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.style.cssText = 'position: relative; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);';
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" style="width: 100%; height: 120px; object-fit: cover;">
                    <button type="button" class="remove-image" data-index="${index}" style="
                        position: absolute;
                        top: 5px;
                        right: 5px;
                        background: rgba(239, 68, 68, 0.9);
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        font-size: 12px;
                        cursor: pointer;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                    ">
                        <i class="fas fa-times"></i>
                    </button>
                    <div style="padding: 8px; background: white; font-size: 12px; color: #4a5568; text-align: center;">
                        ${file.name.length > 15 ? file.name.substring(0, 15) + '...' : file.name}
                    </div>
                `;
                
                // Add remove functionality
                const removeBtn = previewItem.querySelector('.remove-image');
                removeBtn.addEventListener('click', () => {
                    const fileIndex = parseInt(removeBtn.dataset.index);
                    selectedFiles.splice(fileIndex, 1);
                    this.updateFileInput(selectedFiles, imageInput.closest('form').querySelector('#property-images'));
                    this.updateImagePreview(selectedFiles, previewContainer, previewGrid);
                });
                
                previewGrid.appendChild(previewItem);
            };
            reader.readAsDataURL(file);
        });
    }

    async showPropertyDetailsModal(propertyId) {
        console.log('Showing property details modal for:', propertyId);
        
        try {
            const response = await fetch('api/property_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'view',
                    property_id: propertyId
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                const property = result.data;
                this.displayPropertyDetailsModal(property);
            } else {
                this.showErrorMessage('Failed to load property details');
            }
        } catch (error) {
            console.error('Error loading property details:', error);
            this.showErrorMessage('An error occurred while loading property details');
        }
    }
    
    displayPropertyDetailsModal(property) {
        const modalHtml = `
            <div class="property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            ">
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 0;
                    max-width: 900px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px 12px 0 0; position: relative;">
                        <button class="close-modal" style="
                            position: absolute;
                            top: 15px;
                            right: 20px;
                            background: rgba(255,255,255,0.2);
                            border: none;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            font-size: 18px;
                            cursor: pointer;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">×</button>
                        <h2 style="margin: 0; font-size: 24px;">
                            <i class="fas fa-home"></i> ${property.title}
                        </h2>
                        <p style="margin: 10px 0 0; opacity: 0.9; font-size: 16px;">
                            <i class="fas fa-map-marker-alt"></i> ${property.address}, ${property.city}, ${property.state}
                        </p>
                    </div>
                    
                    <div style="padding: 30px;">
                        <!-- Property Images -->
                        ${property.images && property.images.length > 0 ? `
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 15px;">
                                    <i class="fas fa-images"></i> Property Images (${property.images.length})
                                </h3>
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                    ${property.images.map((image, index) => {
                                        const imagePath = `/rental_system/uploads/properties/${image}`;
                                        return `
                                        <div style="position: relative; background: #f7fafc; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                                            <img src="${imagePath}" 
                                                 alt="Property Image ${index + 1}" 
                                                 style="width: 100%; height: 150px; object-fit: cover; cursor: pointer; transition: all 0.3s ease;" 
                                                 onclick="window.open('${imagePath}', '_blank')" 
                                                 onload="this.style.opacity='1'; this.nextElementSibling.style.display='none';" 
                                                 onerror="this.style.display='none'; this.nextElementSibling.innerHTML='Image failed to load: ${image}'; this.nextElementSibling.style.color='#e53e3e';" 
                                                 style="opacity: 0;">
                                            <div style="position: absolute; top: 0; left: 0; width: 100%; height: 150px; background: #f0f0f0; display: flex; align-items: center; justify-content: center; color: #666; font-size: 14px;">
                                                <i class="fas fa-spinner fa-spin"></i> Loading image...
                                            </div>
                                        </div>
                                        `;
                                    }).join('')}
                                </div>
                                <div style="margin-top: 10px; padding: 10px; background: #f7fafc; border-radius: 6px; font-size: 12px; color: #4a5568;">
                                    <strong>Debug Info:</strong> Images path: /rental_system/uploads/properties/<br>
                                    <strong>Images:</strong> ${property.images.join(', ')}
                                </div>
                            </div>
                        ` : `
                            <div style="margin-bottom: 30px; text-align: center; padding: 40px; background: #f7fafc; border-radius: 8px;">
                                <i class="fas fa-image" style="font-size: 3rem; color: #cbd5e0; margin-bottom: 15px;"></i>
                                <h3 style="color: #718096; margin: 0;">No Images Available</h3>
                                <p style="color: #a0aec0; margin: 5px 0 0;">This property doesn't have any images uploaded.</p>
                            </div>
                        `}
                        
                        <!-- Property Details Grid -->
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 30px; margin-bottom: 30px;">
                            <!-- Basic Details -->
                            <div class="detail-section">
                                <h3 style="color: #667eea; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                                    <i class="fas fa-info-circle"></i> Basic Details
                                </h3>
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Type:</strong> ${property.property_type.charAt(0).toUpperCase() + property.property_type.slice(1)}
                                </div>
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Bedrooms:</strong> ${property.bedrooms}
                                </div>
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Bathrooms:</strong> ${property.bathrooms}
                                </div>
                                ${property.area_sqft ? `
                                    <div class="detail-item" style="margin-bottom: 12px;">
                                        <strong style="color: #4a5568;">Area:</strong> ${property.area_sqft} sqft
                                    </div>
                                ` : ''}
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Status:</strong> 
                                    <span class="status-badge status-${property.status.toLowerCase()}" style="
                                        padding: 4px 12px;
                                        border-radius: 20px;
                                        font-size: 12px;
                                        font-weight: 500;
                                        text-transform: uppercase;
                                    ">
                                        ${property.status}
                                    </span>
                                </div>
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Availability:</strong> 
                                    <span class="status-badge status-${property.is_available ? 'available' : 'unavailable'}" style="
                                        padding: 4px 12px;
                                        border-radius: 20px;
                                        font-size: 12px;
                                        font-weight: 500;
                                    ">
                                        ${property.is_available ? 'Available' : 'Unavailable'}
                                    </span>
                                </div>
                            </div>
                            
                            <!-- Financial Details -->
                            <div class="detail-section">
                                <h3 style="color: #667eea; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                                    <i class="fas fa-money-bill-wave"></i> Financial Details
                                </h3>
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Monthly Rent:</strong> 
                                    <span style="color: #48bb78; font-weight: 600; font-size: 16px;">Rs. ${parseFloat(property.rent_amount).toLocaleString()}</span>
                                </div>
                                <div class="detail-item" style="margin-bottom: 12px;">
                                    <strong style="color: #4a5568;">Security Deposit:</strong> 
                                    <span style="color: #ed8936; font-weight: 600;">Rs. ${parseFloat(property.security_deposit).toLocaleString()}</span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Owner Details -->
                        <div class="detail-section" style="margin-bottom: 30px;">
                            <h3 style="color: #667eea; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                                <i class="fas fa-user"></i> Owner Details
                            </h3>
                            <div style="background: #f7fafc; padding: 20px; border-radius: 8px;">
                                <div class="detail-item" style="margin-bottom: 10px;">
                                    <strong style="color: #4a5568;">Name:</strong> ${property.owner_name}
                                </div>
                                <div class="detail-item" style="margin-bottom: 10px;">
                                    <strong style="color: #4a5568;">Email:</strong> ${property.owner_email}
                                </div>
                                <div class="detail-item" style="margin-bottom: 10px;">
                                    <strong style="color: #4a5568;">Phone:</strong> ${property.owner_phone || property.contact_phone}
                                </div>
                            </div>
                        </div>
                        
                        ${property.description ? `
                            <div class="detail-section" style="margin-bottom: 30px;">
                                <h3 style="color: #667eea; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                                    <i class="fas fa-align-left"></i> Description
                                </h3>
                                <p style="line-height: 1.6; color: #4a5568; background: #f7fafc; padding: 15px; border-radius: 8px;">
                                    ${property.description}
                                </p>
                            </div>
                        ` : ''}
                        
                        ${property.amenities && property.amenities.length > 0 ? `
                            <div class="detail-section" style="margin-bottom: 30px;">
                                <h3 style="color: #667eea; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 2px solid #e2e8f0;">
                                    <i class="fas fa-star"></i> Amenities
                                </h3>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    ${property.amenities.map(amenity => `
                                        <span style="
                                            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                                            color: white;
                                            padding: 6px 12px;
                                            border-radius: 20px;
                                            font-size: 13px;
                                            font-weight: 500;
                                        ">
                                            ${amenity}
                                        </span>
                                    `).join('')}
                                </div>
                            </div>
                        ` : ''}
                        
                        <!-- Timestamps -->
                        <div class="detail-section" style="border-top: 1px solid #e2e8f0; padding-top: 20px;">
                            <h3 style="color: #667eea; margin-bottom: 15px;">
                                <i class="fas fa-clock"></i> Timeline
                            </h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                <div style="background: #f7fafc; padding: 15px; border-radius: 8px;">
                                    <strong style="color: #4a5568;">Created:</strong><br>
                                    <small style="color: #718096;">${new Date(property.created_at).toLocaleString()}</small>
                                </div>
                                ${property.updated_at ? `
                                    <div style="background: #f7fafc; padding: 15px; border-radius: 8px;">
                                        <strong style="color: #4a5568;">Last Updated:</strong><br>
                                        <small style="color: #718096;">${new Date(property.updated_at).toLocaleString()}</small>
                                    </div>
                                ` : ''}
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                            <button type="button" class="edit-property-btn" style="
                                margin-right: 10px;
                                padding: 12px 24px;
                                background: #667eea;
                                color: white;
                                border: none;
                                border-radius: 6px;
                                font-weight: 500;
                                cursor: pointer;
                            ">
                                <i class="fas fa-edit"></i> Edit Property
                            </button>
                            <button type="button" class="close-modal" style="
                                padding: 12px 24px;
                                background: #e2e8f0;
                                color: #4a5568;
                                border: none;
                                border-radius: 6px;
                                font-weight: 500;
                                cursor: pointer;
                            ">
                                Close
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Add event listeners
        const closeButtons = modalElement.querySelectorAll('.close-modal');
        const overlay = modalElement.querySelector('.property-modal-overlay');
        const editBtn = modalElement.querySelector('.edit-property-btn');
        
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(modalElement);
            }
        });
        
        if (editBtn) {
            editBtn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
                this.showEditPropertyModal(property.id);
            });
        }
        
        // Prevent modal from closing when clicking inside
        const modalContent = modalElement.querySelector('.property-modal');
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
    }
    
    async showEditPropertyModal(propertyId) {
        console.log('Showing edit property modal for:', propertyId);
        
        try {
            // First, load the property data
            const response = await fetch('api/property_actions.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'view',
                    property_id: propertyId
                })
            });
            
            const result = await response.json();
            
            if (result.success && result.data) {
                this.displayEditPropertyModal(result.data);
            } else {
                this.showErrorMessage('Failed to load property data for editing');
            }
        } catch (error) {
            console.error('Error loading property for editing:', error);
            this.showErrorMessage('An error occurred while loading property data');
        }
    }
    
    displayEditPropertyModal(property) {
        console.log('Displaying edit modal for property:', property);
        
        const modalHtml = `
            <div class="property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
                padding: 20px;
                box-sizing: border-box;
            ">
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 0;
                    max-width: 900px;
                    width: 100%;
                    max-height: 95vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                ">
                    <!-- Header -->
                    <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 25px; border-radius: 12px 12px 0 0; position: sticky; top: 0; z-index: 10;">
                        <button class="close-modal" style="
                            position: absolute;
                            top: 15px;
                            right: 20px;
                            background: rgba(255,255,255,0.2);
                            border: none;
                            border-radius: 50%;
                            width: 35px;
                            height: 35px;
                            font-size: 18px;
                            cursor: pointer;
                            color: white;
                            display: flex;
                            align-items: center;
                            justify-content: center;
                        ">×</button>
                        <h2 style="margin: 0; font-size: 24px;">
                            <i class="fas fa-edit"></i> Edit Property
                        </h2>
                        <p style="margin: 10px 0 0; opacity: 0.9; font-size: 16px;">
                            ${property.title}
                        </p>
                    </div>
                    
                    <!-- Form Content -->
                    <div style="padding: 30px;">
                        <form id="edit-property-form">
                            <input type="hidden" name="property_id" value="${property.id}">
                            
                            <!-- Basic Information -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-info-circle"></i> Basic Information
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Property Title *</label>
                                        <input type="text" name="title" value="${property.title || ''}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Property Type *</label>
                                        <select name="property_type" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="apartment" ${property.property_type === 'apartment' ? 'selected' : ''}>Apartment</option>
                                            <option value="house" ${property.property_type === 'house' ? 'selected' : ''}>House</option>
                                            <option value="villa" ${property.property_type === 'villa' ? 'selected' : ''}>Villa</option>
                                            <option value="studio" ${property.property_type === 'studio' ? 'selected' : ''}>Studio</option>
                                            <option value="commercial" ${property.property_type === 'commercial' ? 'selected' : ''}>Commercial</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div style="margin-top: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Description</label>
                                    <textarea name="description" rows="4" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px; resize: vertical;">${property.description || ''}</textarea>
                                </div>
                            </div>
                            
                            <!-- Property Images Section -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-images"></i> Property Images
                                </h3>
                                
                                <!-- Current Images -->
                                ${property.images && property.images.length > 0 ? `
                                    <div style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 10px; font-weight: 500; color: #4a5568;">Current Images (${property.images.length})</label>
                                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin-bottom: 20px;" id="current-images-grid">
                                            ${property.images.map((image, index) => `
                                                <div style="position: relative; background: #f7fafc; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 8px rgba(0,0,0,0.1);" data-image="${image}">
                                                    <img src="/rental_system/uploads/properties/${image}" alt="Property Image ${index + 1}" style="
                                                        width: 100%;
                                                        height: 120px;
                                                        object-fit: cover;
                                                        cursor: pointer;
                                                    " onclick="window.open('/rental_system/uploads/properties/${image}', '_blank')">
                                                    <button type="button" class="remove-current-image" data-image="${image}" style="
                                                        position: absolute;
                                                        top: 5px;
                                                        right: 5px;
                                                        background: rgba(239, 68, 68, 0.9);
                                                        color: white;
                                                        border: none;
                                                        border-radius: 50%;
                                                        width: 24px;
                                                        height: 24px;
                                                        font-size: 12px;
                                                        cursor: pointer;
                                                        display: flex;
                                                        align-items: center;
                                                        justify-content: center;
                                                        transition: background-color 0.2s ease;
                                                    " title="Remove this image">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                    <div style="padding: 8px; background: white; font-size: 11px; color: #4a5568; text-align: center;">
                                                        ${image.length > 18 ? image.substring(0, 18) + '...' : image}
                                                    </div>
                                                </div>
                                            `).join('')}
                                        </div>
                                        <input type="hidden" name="removed_images" id="removed-images" value="">
                                    </div>
                                ` : `
                                    <div style="margin-bottom: 20px; text-align: center; padding: 20px; background: #f7fafc; border-radius: 8px;">
                                        <i class="fas fa-image" style="font-size: 2rem; color: #cbd5e0; margin-bottom: 10px;"></i>
                                        <p style="color: #718096; margin: 0;">No images currently uploaded</p>
                                    </div>
                                `}
                                
                                <!-- Add New Images -->
                                <div class="form-group" style="margin-bottom: 20px;">
                                    <label style="display: block; margin-bottom: 10px; font-weight: 500; color: #4a5568;">Add New Images</label>
                                    <div style="border: 2px dashed #e2e8f0; border-radius: 8px; padding: 30px; text-align: center; background: #f9fafb; transition: all 0.3s ease;" id="edit-image-upload-area">
                                        <input type="file" id="edit-property-images" name="new_property_images[]" multiple accept="image/*" style="display: none;">
                                        <div id="edit-upload-prompt">
                                            <i class="fas fa-cloud-upload-alt" style="font-size: 2.5rem; color: #cbd5e0; margin-bottom: 15px;"></i>
                                            <p style="margin: 0 0 10px; color: #4a5568; font-size: 16px;">Drop new images here or click to browse</p>
                                            <p style="margin: 0; color: #718096; font-size: 14px;">Support JPG, PNG, WebP (Max: 5MB per image, Up to 5 new images)</p>
                                            <button type="button" id="edit-browse-images-btn" style="margin-top: 15px; padding: 10px 20px; background: #667eea; color: white; border: none; border-radius: 6px; cursor: pointer;">
                                                <i class="fas fa-folder-open"></i> Browse Images
                                            </button>
                                        </div>
                                    </div>
                                    
                                    <!-- New Image Preview Area -->
                                    <div id="edit-image-preview-container" style="margin-top: 20px; display: none;">
                                        <h4 style="color: #4a5568; margin-bottom: 15px;">New Images to Upload:</h4>
                                        <div id="edit-image-preview-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px;"></div>
                                    </div>
                                </div>
                                
                                <div style="background: #e3f2fd; padding: 15px; border-radius: 8px; border-left: 4px solid #2196f3;">
                                    <p style="margin: 0; color: #1565c0; font-size: 14px;">
                                        <i class="fas fa-info-circle"></i> <strong>Image Management:</strong>
                                        You can remove existing images and add new ones. Changes will be applied when you save the property.
                                        Maximum total images: 10
                                    </p>
                                </div>
                            </div>
                            
                            <!-- Property Details -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-home"></i> Property Details
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Bedrooms *</label>
                                        <input type="number" name="bedrooms" value="${property.bedrooms || ''}" min="0" max="10" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Bathrooms *</label>
                                        <input type="number" name="bathrooms" value="${property.bathrooms || ''}" min="1" max="10" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Area (sq ft)</label>
                                        <input type="number" name="area_sqft" value="${property.area_sqft || ''}" min="100" max="10000" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Location -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-map-marker-alt"></i> Location
                                </h3>
                                
                                <div style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Address *</label>
                                    <input type="text" name="address" value="${property.address || ''}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                </div>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">City *</label>
                                        <input type="text" name="city" value="${property.city || ''}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">State/Province *</label>
                                        <input type="text" name="state" value="${property.state || ''}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">ZIP Code</label>
                                        <input type="text" name="zip_code" value="${property.zip_code || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Financial Details -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-money-bill-wave"></i> Financial Details
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Monthly Rent (LKR) *</label>
                                        <input type="number" name="rent_amount" value="${property.rent_amount || ''}" min="5000" step="0.01" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Security Deposit (LKR) *</label>
                                        <input type="number" name="security_deposit" value="${property.security_deposit || ''}" min="5000" step="0.01" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Contact Phone *</label>
                                        <input type="tel" name="contact_phone" value="${property.contact_phone || property.owner_phone || ''}" pattern="[0]{1}[7]{1}[01245678]{1}[0-9]{7}" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;" placeholder="0771234567">
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Amenities -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-star"></i> Amenities
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
                                    ${[
                                        { value: 'WiFi', icon: 'fas fa-wifi' },
                                        { value: 'Air Conditioning', icon: 'fas fa-snowflake' },
                                        { value: 'Parking', icon: 'fas fa-car' },
                                        { value: 'Swimming Pool', icon: 'fas fa-swimming-pool' },
                                        { value: 'Gym', icon: 'fas fa-dumbbell' },
                                        { value: 'Security', icon: 'fas fa-shield-alt' },
                                        { value: 'Garden', icon: 'fas fa-seedling' },
                                        { value: 'Elevator', icon: 'fas fa-arrows-alt-v' },
                                        { value: 'Furnished', icon: 'fas fa-couch' }
                                    ].map(amenity => {
                                        const isChecked = property.amenities && property.amenities.includes(amenity.value);
                                        return `
                                            <label style="display: flex; align-items: center; padding: 10px; background: #f7fafc; border-radius: 6px; cursor: pointer; border: 2px solid ${isChecked ? '#667eea' : 'transparent'};">
                                                <input type="checkbox" name="amenities[]" value="${amenity.value}" ${isChecked ? 'checked' : ''} style="margin-right: 10px;">
                                                <i class="${amenity.icon}" style="margin-right: 8px; color: #667eea;"></i> ${amenity.value}
                                            </label>
                                        `;
                                    }).join('')}
                                </div>
                            </div>
                            
                            <!-- Status and Availability -->
                            <div style="margin-bottom: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; padding-bottom: 10px; border-bottom: 2px solid #667eea;">
                                    <i class="fas fa-toggle-on"></i> Status & Availability
                                </h3>
                                
                                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Property Status *</label>
                                        <select name="status" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="pending" ${property.status === 'pending' ? 'selected' : ''}>Pending Review</option>
                                            <option value="approved" ${property.status === 'approved' ? 'selected' : ''}>Approved</option>
                                            <option value="rejected" ${property.status === 'rejected' ? 'selected' : ''}>Rejected</option>
                                            <option value="rented" ${property.status === 'rented' ? 'selected' : ''}>Rented</option>
                                        </select>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500; color: #4a5568;">Availability *</label>
                                        <select name="is_available" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; font-size: 14px;">
                                            <option value="1" ${property.is_available ? 'selected' : ''}>Available</option>
                                            <option value="0" ${!property.is_available ? 'selected' : ''}>Unavailable</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div style="text-align: center; margin-top: 40px; padding-top: 20px; border-top: 2px solid #e2e8f0;">
                                <button type="submit" style="
                                    margin-right: 15px;
                                    padding: 15px 30px;
                                    background: linear-gradient(135deg, #667eea, #764ba2);
                                    color: white;
                                    border: none;
                                    border-radius: 8px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 16px;
                                    transition: all 0.3s ease;
                                ">
                                    <i class="fas fa-save"></i> Update Property
                                </button>
                                <button type="button" class="close-modal" style="
                                    padding: 15px 30px;
                                    background: #e2e8f0;
                                    color: #4a5568;
                                    border: none;
                                    border-radius: 8px;
                                    font-weight: 600;
                                    cursor: pointer;
                                    font-size: 16px;
                                ">
                                    <i class="fas fa-times"></i> Cancel
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Initialize modal functionality
        this.initializeEditModal(modalElement, property);
    }
    
    initializeEditModal(modalElement, property) {
        console.log('Initializing edit modal for property:', property.id);
        
        // Get form and modal elements
        const form = modalElement.querySelector('#edit-property-form');
        const closeButtons = modalElement.querySelectorAll('.close-modal');
        const overlay = modalElement.querySelector('.property-modal-overlay');
        const modalContent = modalElement.querySelector('.property-modal');
        
        // Close modal handlers
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                document.body.removeChild(modalElement);
            }
        });
        
        // Prevent modal from closing when clicking inside
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        
        // Initialize image editing functionality
        this.initializeImageEditing(modalElement, property);
        
        // Handle amenity checkbox styling
        const amenityCheckboxes = modalElement.querySelectorAll('input[name="amenities[]"]');
        amenityCheckboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                const label = checkbox.closest('label');
                if (checkbox.checked) {
                    label.style.borderColor = '#667eea';
                    label.style.backgroundColor = '#f0f4ff';
                } else {
                    label.style.borderColor = 'transparent';
                    label.style.backgroundColor = '#f7fafc';
                }
            });
        });
        
        // Auto-calculate security deposit based on rent
        const rentInput = modalElement.querySelector('input[name="rent_amount"]');
        const depositInput = modalElement.querySelector('input[name="security_deposit"]');
        
        rentInput.addEventListener('input', () => {
            const rentAmount = parseFloat(rentInput.value);
            if (rentAmount > 0 && (!depositInput.value || depositInput.value == 0)) {
                depositInput.value = (rentAmount * 2).toFixed(2);
            }
        });
        
        // Handle form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            try {
                await this.handlePropertyUpdate(form, modalElement);
            } catch (error) {
                console.error('Form submission error:', error);
                this.showErrorMessage('An error occurred while updating the property');
            } finally {
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            }
        });
    }
    
    async handlePropertyUpdate(form, modalElement) {
        console.log('Handling property update');
        
        const formData = new FormData(form);
        
        // Process amenities
        const amenities = [];
        const amenityInputs = form.querySelectorAll('input[name="amenities[]"]:checked');
        amenityInputs.forEach(input => {
            amenities.push(input.value);
        });
        
        // Prepare update data
        const updateData = {
            property_id: formData.get('property_id'),
            title: formData.get('title'),
            description: formData.get('description'),
            property_type: formData.get('property_type'),
            bedrooms: parseInt(formData.get('bedrooms')),
            bathrooms: parseInt(formData.get('bathrooms')),
            area_sqft: formData.get('area_sqft') ? parseInt(formData.get('area_sqft')) : null,
            address: formData.get('address'),
            city: formData.get('city'),
            state: formData.get('state'),
            zip_code: formData.get('zip_code'),
            rent_amount: parseFloat(formData.get('rent_amount')),
            security_deposit: parseFloat(formData.get('security_deposit')),
            contact_phone: formData.get('contact_phone'),
            amenities: amenities,
            status: formData.get('status'),
            is_available: parseInt(formData.get('is_available'))
        };
        
        // Add image changes to update data
        const removedImagesInput = form.querySelector('#removed-images');
        if (removedImagesInput && removedImagesInput.value) {
            try {
                const removedImages = JSON.parse(removedImagesInput.value);
                if (removedImages && removedImages.length > 0) {
                    updateData.images_to_remove = removedImages;
                }
            } catch (e) {
                console.warn('Failed to parse removed images:', e);
            }
        }
        
        // Also check the instance variable as fallback
        if (this.imagesToRemove && this.imagesToRemove.length > 0) {
            updateData.images_to_remove = this.imagesToRemove;
        }
        
        console.log('Update data:', updateData);
        
        try {
            // Create FormData if we have new images to upload
            let requestBody;
            let requestHeaders = {};
            
            const newImagesInput = form.querySelector('#edit-property-images');
            if (newImagesInput && newImagesInput.files && newImagesInput.files.length > 0) {
                // Use FormData for file uploads
                const formDataForUpload = new FormData();
                formDataForUpload.append('update_data', JSON.stringify(updateData));
                
                // Add new images from file input
                Array.from(newImagesInput.files).forEach((file) => {
                    formDataForUpload.append(`new_images[]`, file);
                });
                
                requestBody = formDataForUpload;
                // Don't set Content-Type header for FormData - browser will set it automatically
            } else {
                // Use JSON for regular updates without new images
                requestBody = JSON.stringify(updateData);
                requestHeaders['Content-Type'] = 'application/json';
            }
            
            const response = await fetch('api/update_property.php', {
                method: 'POST',
                headers: requestHeaders,
                body: requestBody
            });
            
            const result = await response.json();
            
            if (result.success) {
                this.showSuccessMessage(result.message || 'Property updated successfully!');
                document.body.removeChild(modalElement);
                this.loadSection('properties'); // Reload properties list
            } else {
                this.showErrorMessage('Update failed: ' + (result.message || 'Unknown error'));
            }
        } catch (error) {
            console.error('Property update error:', error);
            this.showErrorMessage('An error occurred while updating the property');
        }
    }
    
    // Method to initialize image editing functionality
    initializeImageEditing(modalElement, property) {
        // Track images to remove and new images to add
        this.imagesToRemove = [];
        this.newImagesToAdd = [];
        
        // Handle current images display and removal
        this.displayCurrentImages(modalElement, property);
        
        // Handle new image upload
        this.initializeEditImageUpload(modalElement);
    }
    
    displayCurrentImages(modalElement, property) {
        // Set up remove button event listeners for existing images
        const removeButtons = modalElement.querySelectorAll('.remove-current-image');
        removeButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                const imageName = button.getAttribute('data-image');
                
                if (confirm('Are you sure you want to remove this image?')) {
                    // Add to removal list
                    if (!this.imagesToRemove.includes(imageName)) {
                        this.imagesToRemove.push(imageName);
                    }
                    
                    // Update hidden input
                    const removedImagesInput = modalElement.querySelector('#removed-images');
                    if (removedImagesInput) {
                        removedImagesInput.value = JSON.stringify(this.imagesToRemove);
                    }
                    
                    // Remove the image container from UI
                    const imageContainer = button.closest('[data-image]');
                    if (imageContainer) {
                        imageContainer.style.opacity = '0.5';
                        imageContainer.style.filter = 'grayscale(100%)';
                        
                        // Add removed indicator
                        const indicator = document.createElement('div');
                        indicator.style.cssText = `
                            position: absolute;
                            top: 50%;
                            left: 50%;
                            transform: translate(-50%, -50%);
                            background: rgba(239, 68, 68, 0.9);
                            color: white;
                            padding: 5px 10px;
                            border-radius: 4px;
                            font-size: 12px;
                            font-weight: bold;
                            z-index: 10;
                        `;
                        indicator.textContent = 'REMOVED';
                        imageContainer.style.position = 'relative';
                        imageContainer.appendChild(indicator);
                        
                        // Disable the remove button
                        button.disabled = true;
                        button.style.display = 'none';
                    }
                }
            });
        });
    }
    
    // Image upload for ADD PROPERTY modal
    initializeImageUpload(modalElement) {
        const imageUploadArea = modalElement.querySelector('#image-upload-area');
        const imageInput = modalElement.querySelector('#property-images');
        const browseBtn = modalElement.querySelector('#browse-images-btn');
        const previewContainer = modalElement.querySelector('#image-preview-container');
        const previewGrid = modalElement.querySelector('#image-preview-grid');
        
        let selectedFiles = [];
        
        // Browse button click handler
        browseBtn.addEventListener('click', () => imageInput.click());
        
        // Click on upload area to browse
        imageUploadArea.addEventListener('click', (e) => {
            if (e.target === imageUploadArea || e.target.closest('#upload-prompt')) {
                imageInput.click();
            }
        });
        
        // Drag and drop handlers
        imageUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#667eea';
            imageUploadArea.style.backgroundColor = '#edf2f7';
        });
        
        imageUploadArea.addEventListener('dragleave', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#e2e8f0';
            imageUploadArea.style.backgroundColor = '#f9fafb';
        });
        
        imageUploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#e2e8f0';
            imageUploadArea.style.backgroundColor = '#f9fafb';
            
            const files = Array.from(e.dataTransfer.files);
            this.handleImageFiles(files, selectedFiles, previewContainer, previewGrid, imageInput);
        });
        
        // File input change handler
        imageInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            this.handleImageFiles(files, selectedFiles, previewContainer, previewGrid, imageInput);
        });
    }
    
    // Image upload for EDIT PROPERTY modal
    initializeEditImageUpload(modalElement) {
        const imageInput = modalElement.querySelector('#edit-property-images');
        const imagePreview = modalElement.querySelector('#edit-image-preview-grid');
        const previewContainer = modalElement.querySelector('#edit-image-preview-container');
        const browseBtn = modalElement.querySelector('#edit-browse-images-btn');
        const uploadArea = modalElement.querySelector('#edit-image-upload-area');
        
        // Handle browse button click
        if (browseBtn) {
            browseBtn.addEventListener('click', () => {
                imageInput.click();
            });
        }
        
        // Handle upload area click
        if (uploadArea) {
            uploadArea.addEventListener('click', (e) => {
                if (e.target === uploadArea || e.target.closest('#edit-upload-prompt')) {
                    imageInput.click();
                }
            });
        }
        
        // Handle drag and drop
        if (uploadArea) {
            uploadArea.addEventListener('dragover', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#667eea';
                uploadArea.style.backgroundColor = '#f0f4ff';
            });
            
            uploadArea.addEventListener('dragleave', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#e2e8f0';
                uploadArea.style.backgroundColor = '#f9fafb';
            });
            
            uploadArea.addEventListener('drop', (e) => {
                e.preventDefault();
                uploadArea.style.borderColor = '#e2e8f0';
                uploadArea.style.backgroundColor = '#f9fafb';
                
                const files = Array.from(e.dataTransfer.files);
                const imageFiles = files.filter(file => file.type.startsWith('image/'));
                
                if (imageFiles.length > 0) {
                    // Create a new FileList
                    const dt = new DataTransfer();
                    imageFiles.forEach(file => dt.items.add(file));
                    imageInput.files = dt.files;
                    
                    // Trigger change event
                    imageInput.dispatchEvent(new Event('change'));
                }
            });
        }
        
        // Handle file input change
        if (imageInput) {
            imageInput.addEventListener('change', (e) => {
                const files = Array.from(e.target.files);
                
                if (files.length === 0) {
                    this.newImagesToAdd = [];
                    if (previewContainer) {
                        previewContainer.style.display = 'none';
                    }
                    return;
                }
                
                // Validate files
                const validFiles = files.filter(file => {
                    const isValidType = file.type.startsWith('image/');
                    const isValidSize = file.size <= 5 * 1024 * 1024; // 5MB limit
                    
                    if (!isValidType) {
                        alert(`${file.name} is not a valid image file`);
                        return false;
                    }
                    if (!isValidSize) {
                        alert(`${file.name} is too large. Maximum size is 5MB`);
                        return false;
                    }
                    return true;
                });
                
                // Check if we have too many new images (limit 5 new images)
                if (validFiles.length > 5) {
                    alert('You can only upload up to 5 new images at once.');
                    return;
                }
                
                // Store valid files
                this.newImagesToAdd = validFiles;
                
                // Show preview
                this.showImagePreview(imagePreview, previewContainer, validFiles, modalElement);
            });
        }
    }
    
    showImagePreview(previewGrid, previewContainer, files, modalElement) {
        if (!previewGrid || !previewContainer) return;
        
        previewGrid.innerHTML = '';
        
        if (files.length === 0) {
            previewContainer.style.display = 'none';
            return;
        }
        
        previewContainer.style.display = 'block';
        
        files.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = (e) => {
                const imageDiv = document.createElement('div');
                imageDiv.style.cssText = `
                    position: relative;
                    background: #f7fafc;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    border: 2px solid #48bb78;
                `;
                imageDiv.setAttribute('data-new-image-index', index);
                
                const img = document.createElement('img');
                img.src = e.target.result;
                img.alt = `New image ${index + 1}`;
                img.style.cssText = `
                    width: 100%;
                    height: 120px;
                    object-fit: cover;
                    cursor: pointer;
                `;
                
                const removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.innerHTML = '<i class="fas fa-times"></i>';
                removeBtn.style.cssText = `
                    position: absolute;
                    top: 5px;
                    right: 5px;
                    background: rgba(239, 68, 68, 0.9);
                    color: white;
                    border: none;
                    border-radius: 50%;
                    width: 24px;
                    height: 24px;
                    font-size: 12px;
                    cursor: pointer;
                    display: flex;
                    align-items: center;
                    justify-content: center;
                    transition: background-color 0.2s ease;
                `;
                removeBtn.title = 'Remove this image';
                
                const fileName = document.createElement('div');
                fileName.style.cssText = `
                    padding: 8px;
                    background: white;
                    font-size: 11px;
                    color: #4a5568;
                    text-align: center;
                    border-top: 1px solid #e2e8f0;
                `;
                fileName.textContent = file.name.length > 18 ? file.name.substring(0, 18) + '...' : file.name;
                
                removeBtn.addEventListener('click', () => {
                    // Remove from newImagesToAdd array
                    this.newImagesToAdd = this.newImagesToAdd.filter((_, i) => i !== index);
                    imageDiv.remove();
                    
                    // Update file input
                    const input = modalElement.querySelector('#edit-property-images');
                    const dt = new DataTransfer();
                    this.newImagesToAdd.forEach(file => dt.items.add(file));
                    input.files = dt.files;
                    
                    // Hide preview container if no images left
                    if (this.newImagesToAdd.length === 0) {
                        previewContainer.style.display = 'none';
                    }
                });
                
                imageDiv.appendChild(img);
                imageDiv.appendChild(removeBtn);
                imageDiv.appendChild(fileName);
                previewGrid.appendChild(imageDiv);
            };
            reader.readAsDataURL(file);
        });
    }
    
    showSuccessMessage(message) {
        this.showNotification(message, 'success');
    }
    
    showErrorMessage(message) {
        this.showNotification(message, 'error');
    }
    
    showInfoMessage(message) {
        this.showNotification(message, 'info');
    }
    
    showNotification(message, type = 'info') {
        const colors = {
            success: { bg: '#48bb78', icon: 'check-circle' },
            error: { bg: '#e53e3e', icon: 'exclamation-triangle' },
            info: { bg: '#4299e1', icon: 'info-circle' }
        };
        
        const color = colors[type] || colors.info;
        
        const notification = document.createElement('div');
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${color.bg};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
            z-index: 10001;
            max-width: 400px;
            font-weight: 500;
            animation: slideInRight 0.3s ease;
        `;
        
        notification.innerHTML = `
            <i class="fas fa-${color.icon}" style="margin-right: 10px;"></i>
            ${message}
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.style.animation = 'slideOutRight 0.3s ease';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
        
        // Click to dismiss
        notification.addEventListener('click', () => {
            if (document.body.contains(notification)) {
                document.body.removeChild(notification);
            }
        });
    }

    showError(message) {
        console.error('Dashboard error:', message);
        const content = document.getElementById('content');
        if (content) {
            content.innerHTML = `
                <div style="text-align: center; padding: 50px;">
                    <i class="fas fa-exclamation-triangle" style="font-size: 3rem; color: #e53e3e; margin-bottom: 20px;"></i>
                    <h3 style="color: #e53e3e; margin-bottom: 10px;">Error</h3>
                    <p>${message}</p>
                    <button class="btn btn-primary" onclick="location.reload()" style="margin-top: 20px;">
                        Refresh Page
                    </button>
                </div>
            `;
        }
    }
}

// Initialize when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing SimpleAdminDashboard');
    try {
        new SimpleAdminDashboard();
    } catch (error) {
        console.error('Failed to initialize SimpleAdminDashboard:', error);
    }
});

// Global user management functions (accessible from AJAX-loaded content)
window.clearAllFilters = function() {
    console.log('clearAllFilters called');
    const userSearchInput = document.getElementById('user-search-input');
    const userTypeFilter = document.getElementById('user-type-filter');
    const userStatusFilter = document.getElementById('user-status-filter');
    
    if (userSearchInput) userSearchInput.value = '';
    if (userTypeFilter) userTypeFilter.value = '';
    if (userStatusFilter) userStatusFilter.value = '';
    
    // Trigger search if the functions are available
    if (typeof window.updateSearchResults === 'function') {
        window.updateSearchResults();
    } else if (typeof window.performSearch === 'function') {
        window.performSearch();
    } else {
        // Reload the section to reset
        window.location.reload();
    }
};

window.toggleSearchTips = function() {
    console.log('toggleSearchTips called');
    alert('Search Tips:\n\n• Type to search names, emails, or phone numbers\n• Use filters to narrow results\n• Search is case-insensitive\n• Results update as you type');
};

// Debug function accessible globally
window.testUserSearch = function() {
    console.log('Testing user search functionality...');
    if (typeof window.performSearch === 'function') {
        window.performSearch();
    } else {
        console.log('performSearch function not available yet');
    }
};

console.log('Global user management functions loaded');
console.log('Simplified admin dashboard script loaded');
