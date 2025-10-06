// Dashboard JavaScript with AJAX functionality
class AdminDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('main-content');
        this.toggleBtn = document.getElementById('toggle-btn');
        this.pageTitle = document.getElementById('page-title');
        this.loadingSpinner = document.getElementById('loading-spinner');
        
        this.init();
        this.bindEvents();
        // Don't load dashboard stats here - let init() handle initial navigation
    }

    init() {
        // Initialize the dashboard
        console.log('Admin Dashboard initialized');
        
        // Check if sidebar should be collapsed on small screens
        if (window.innerWidth <= 768) {
            this.sidebar.classList.add('mobile-closed');
        }
        
        // Handle initial hash navigation
        this.handleInitialNavigation();
    }
    
    handleInitialNavigation() {
        // Get section from URL hash
        const hash = window.location.hash.substring(1); // Remove the # symbol
        if (hash && hash !== '') {
            const validSections = ['dashboard', 'properties', 'bookings', 'users', 'payments', 'reports', 'settings'];
            if (validSections.includes(hash)) {
                // Navigate to the section from URL hash
                this.navigateToSection(hash);
                return;
            }
        }
        
        // Default to dashboard if no valid hash
        this.navigateToSection('dashboard');
    }

    bindEvents() {
        // Sidebar toggle
        this.toggleBtn.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Navigation links
        console.log('Binding navigation links...');
        const navLinks = document.querySelectorAll('.nav-link');
        console.log(`Found ${navLinks.length} navigation links`);
        
        navLinks.forEach((link, index) => {
            const section = link.getAttribute('data-section');
            console.log(`Binding link ${index}: ${section}`);
            
            link.addEventListener('click', (e) => {
                e.preventDefault();
                console.log(`Navigation link clicked: ${section}`);
                
                if (section) {
                    this.navigateToSection(section);
                } else {
                    console.error('No section data attribute found on link');
                }
            });
        });

        // Logout button
        document.querySelector('.logout-btn').addEventListener('click', (e) => {
            e.preventDefault();
            this.handleLogout();
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

        // Mobile menu overlay click
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !this.sidebar.contains(e.target) && 
                !this.toggleBtn.contains(e.target) && 
                this.sidebar.classList.contains('mobile-open')) {
                this.sidebar.classList.remove('mobile-open');
            }
        });
        
        // Handle browser back/forward navigation
        window.addEventListener('hashchange', () => {
            this.handleInitialNavigation();
        });
    }

    toggleSidebar() {
        if (window.innerWidth <= 768) {
            this.sidebar.classList.toggle('mobile-open');
        } else {
            this.sidebar.classList.toggle('collapsed');
        }
    }

    navigateToSection(section) {
        if (this.currentSection === section) return;

        // Update URL hash (only if it's different to avoid triggering hashchange)
        if (window.location.hash !== `#${section}`) {
            window.location.hash = section;
        }

        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        
        const targetLink = document.querySelector(`[data-section="${section}"]`);
        if (targetLink) {
            targetLink.parentElement.classList.add('active');
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
        this.pageTitle.textContent = titles[section] || 'Dashboard';

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
        
        console.log(`Navigated to section: ${section}`);
    }

    async loadSection(section) {
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
        const dashboardContent = document.getElementById('dashboard-content');
        if (dashboardContent) {
            dashboardContent.style.display = 'block';
            dashboardContent.classList.add('active');
            
            // Load dashboard stats when showing dashboard
            await this.loadDashboardStats();
        }
    }

    async loadSectionContent(section) {
        try {
            const response = await this.makeAjaxRequest(`api/${section}_content.php`);
            
            const contentElement = document.getElementById(`${section}-content`);
            if (contentElement) {
                contentElement.innerHTML = response.html;
                contentElement.style.display = 'block';
                contentElement.classList.add('active');
            }
            
            // Initialize section-specific functionality
            this.initializeSectionFeatures(section);
            
        } catch (error) {
            console.error(`Failed to load ${section} content:`, error);
            this.showError(`Failed to load ${section} content.`);
        }
    }

    async loadDashboardStats() {
        try {
            const stats = await this.makeAjaxRequest('api/dashboard_stats.php');
            
            if (stats.success) {
                document.getElementById('total-properties').textContent = stats.data.total_properties || '0';
                document.getElementById('total-bookings').textContent = stats.data.total_bookings || '0';
                document.getElementById('total-users').textContent = stats.data.total_users || '0';
                document.getElementById('monthly-revenue').textContent = `$${stats.data.monthly_revenue || '0'}`;
            }

            // Load recent bookings
            await this.loadRecentBookings();
            
            // Load property status
            await this.loadPropertyStatus();
            
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }
    }

    async loadRecentBookings() {
        try {
            const response = await this.makeAjaxRequest('api/recent_bookings.php');
            const container = document.getElementById('recent-bookings');
            
            if (response.success && response.data.length > 0) {
                const bookingsHtml = response.data.map(booking => `
                    <div class="booking-item" style="padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${booking.property_name}</strong><br>
                                <small class="text-muted">${booking.customer_name}</small>
                            </div>
                            <div class="text-right">
                                <div class="status-badge status-${booking.status.toLowerCase()}">${booking.status}</div>
                                <small class="text-muted">${booking.created_date}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
                container.innerHTML = bookingsHtml;
            } else {
                container.innerHTML = '<p class="text-muted">No recent bookings found.</p>';
            }
        } catch (error) {
            console.error('Error loading recent bookings:', error);
            document.getElementById('recent-bookings').innerHTML = '<p class="text-danger">Failed to load recent bookings.</p>';
        }
    }

    async loadPropertyStatus() {
        try {
            const response = await this.makeAjaxRequest('api/property_status.php');
            const container = document.getElementById('property-status');
            
            if (response.success) {
                const statusHtml = `
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                        <div style="text-align: center; padding: 15px; background: #f0f9f0; border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #22543d;">${response.data.active || 0}</div>
                            <div style="color: #4a5568;">Active</div>
                        </div>
                        <div style="text-align: center; padding: 15px; background: #fef5e7; border-radius: 8px;">
                            <div style="font-size: 1.5rem; font-weight: bold; color: #7b341e;">${response.data.pending || 0}</div>
                            <div style="color: #4a5568;">Pending</div>
                        </div>
                    </div>
                `;
                container.innerHTML = statusHtml;
            }
        } catch (error) {
            console.error('Error loading property status:', error);
            document.getElementById('property-status').innerHTML = '<p class="text-danger">Failed to load property status.</p>';
        }
    }

    initializeSectionFeatures(section) {
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
            // Add more sections as needed
        }
    }

    initializePropertiesFeatures() {
        // Property-specific functionality
        document.querySelectorAll('.property-action').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Get the button element (in case user clicked on icon inside button)
                const buttonElement = e.target.closest('.property-action');
                if (!buttonElement) return;
                
                const action = buttonElement.getAttribute('data-action');
                const propertyId = buttonElement.getAttribute('data-property-id');
                
                if (!action || !propertyId) {
                    console.error('Missing action or propertyId:', { action, propertyId, element: buttonElement });
                    this.showNotification('Action or property ID is missing', 'error');
                    return;
                }
                
                this.handlePropertyAction(action, propertyId);
            });
        });
        
        // Add New Property button
        const addPropertyBtn = document.getElementById('add-property-btn');
        if (addPropertyBtn) {
            addPropertyBtn.addEventListener('click', () => {
                this.showAddPropertyModal();
            });
        }
    }

    initializeUsersFeatures() {
        // User management functionality
        document.querySelectorAll('.user-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const userId = e.target.dataset.userId;
                this.handleUserAction(action, userId);
            });
        });
    }

    initializePaymentsFeatures() {
        // Payment management functionality
        document.querySelectorAll('.payment-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const paymentId = e.target.dataset.paymentId;
                this.handlePaymentAction(action, paymentId);
            });
        });
    }

    initializeBookingsFeatures() {
        // Booking management functionality
        document.querySelectorAll('.booking-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const bookingId = e.target.dataset.bookingId;
                this.handleBookingAction(action, bookingId);
            });
        });
    }

    async handlePropertyAction(action, propertyId) {
        // Validate inputs
        if (!action) {
            console.error('No action provided');
            this.showNotification('No action specified', 'error');
            return;
        }
        
        if (!propertyId) {
            console.error('No property ID provided');
            this.showNotification('No property ID specified', 'error');
            return;
        }
        
        // Log action for debugging if needed
        // console.log(`Handling property action: ${action} for property ID: ${propertyId}`);
        
        // Handle special actions that don't require confirmation
        if (action === 'view') {
            this.showPropertyViewModal(propertyId);
            return;
        }
        
        if (action === 'edit') {
            this.showPropertyEditModal(propertyId);
            return;
        }
        
        // Actions that require confirmation
        const confirmMessages = {
            'delete': 'delete this property? This action cannot be undone.',
            'approve': 'approve this property?',
            'reject': 'reject this property?',
            'suspend': 'suspend this property?',
            'activate': 'activate this property?',
            'mark-available': 'mark this property as available?',
            'mark-unavailable': 'mark this property as unavailable?'
        };
        
        const message = confirmMessages[action] || `perform "${action}" action on this property?`;
        if (!confirm(`Are you sure you want to ${message}`)) return;

        try {
            const response = await this.makeAjaxRequest('api/property_actions.php', {
                action: action,
                property_id: propertyId
            });

            if (response.success) {
                this.showNotification(response.message || 'Property updated successfully', 'success');
                this.loadSection('properties'); // Reload the section
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            console.error('Property action error:', error);
            this.showNotification('An error occurred', 'error');
        }
    }

    async handleUserAction(action, userId) {
        if (!confirm(`Are you sure you want to ${action} this user?`)) return;

        try {
            const response = await this.makeAjaxRequest('api/user_actions.php', {
                action: action,
                user_id: userId
            });

            if (response.success) {
                this.showNotification('User updated successfully', 'success');
                this.loadSection('users');
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        }
    }

    async handlePaymentAction(action, paymentId) {
        try {
            const response = await this.makeAjaxRequest('api/payment_actions.php', {
                action: action,
                payment_id: paymentId
            });

            if (response.success) {
                this.showNotification('Payment updated successfully', 'success');
                this.loadSection('payments');
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        }
    }

    async handleBookingAction(action, bookingId) {
        if (!confirm(`Are you sure you want to ${action} this booking?`)) return;

        try {
            const response = await this.makeAjaxRequest('api/booking_actions.php', {
                action: action,
                booking_id: bookingId
            });

            if (response.success) {
                this.showNotification('Booking updated successfully', 'success');
                this.loadSection('bookings');
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        }
    }

    async makeAjaxRequest(url, data = null) {
        const options = {
            method: data ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data) {
            options.body = JSON.stringify(data);
        }

        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        } else {
            // For non-JSON responses (like HTML content)
            const html = await response.text();
            return { success: true, html: html };
        }
    }
    
    async makeAjaxRequestWithFiles(url, formData) {
        const options = {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
                // Don't set Content-Type, let browser set it with boundary for FormData
            },
            body: formData
        };

        const response = await fetch(url, options);
        
        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            return await response.json();
        } else {
            const text = await response.text();
            return { success: true, message: text };
        }
    }

    showLoading() {
        this.loadingSpinner.style.display = 'flex';
    }

    hideLoading() {
        this.loadingSpinner.style.display = 'none';
    }

    showError(message) {
        const content = document.getElementById('content');
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

    showNotification(message, type = 'info') {
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#38a169' : type === 'error' ? '#e53e3e' : '#667eea'};
                color: white;
                padding: 15px 20px;
                border-radius: 8px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.15);
                z-index: 9999;
                max-width: 300px;
                animation: slideIn 0.3s ease;
            ">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
                ${message}
            </div>
        `;

        document.body.appendChild(notification);

        // Auto remove after 5 seconds
        setTimeout(() => {
            notification.remove();
        }, 5000);

        // Add click to dismiss
        notification.addEventListener('click', () => {
            notification.remove();
        });
    }

    showAddPropertyModal() {
        const modalHtml = `
            <div class="admin-property-modal-overlay" style="
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 10000;
            ">
                <div class="admin-property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 30px;
                    max-width: 800px;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
                ">
                    <button class="close-admin-modal" style="
                        position: absolute;
                        top: 15px;
                        right: 20px;
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: #666;
                    ">×</button>
                    
                    <h2 style="margin-bottom: 20px; color: #2d3748;">Add New Property (Admin)</h2>
                    
                    <form id="admin-add-property-form">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                            <!-- Left Column -->
                            <div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Owner *</label>
                                    <select name="owner_id" class="form-input" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        <option value="">Select Property Owner...</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Title *</label>
                                    <input type="text" name="title" class="form-input" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Type *</label>
                                    <select name="property_type" class="form-input" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
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
                                        <input type="number" name="bedrooms" class="form-input" required min="0" max="10" value="1" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms *</label>
                                        <input type="number" name="bathrooms" class="form-input" required min="1" max="10" value="1" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Area (sqft)</label>
                                        <input type="number" name="area_sqft" class="form-input" min="100" max="10000" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Monthly Rent (LKR) *</label>
                                    <input type="number" name="rent_amount" class="form-input" required min="5000" step="100" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Security Deposit (LKR) *</label>
                                    <input type="number" name="security_deposit" class="form-input" required min="5000" step="100" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Address *</label>
                                    <textarea name="address" class="form-input" rows="3" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;"></textarea>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">City *</label>
                                        <input type="text" name="city" class="form-input" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">State *</label>
                                        <input type="text" name="state" class="form-input" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">ZIP Code</label>
                                    <input type="text" name="zip_code" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Contact Phone Number *</label>
                                    <input type="tel" name="contact_phone" class="form-input" required 
                                           pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" 
                                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;"
                                           placeholder="0771234567">
                                    <small style="color: #666; font-size: 0.875rem;">Enter Sri Lankan mobile number</small>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                                    <textarea name="description" class="form-input" rows="4" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;"></textarea>
                                </div>
                                
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Status</label>
                                    <select name="status" class="form-input" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        <option value="pending">Pending Review</option>
                                        <option value="approved">Approved</option>
                                        <option value="rejected">Rejected</option>
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
                        <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 6px; margin-right: 10px;">
                                <i class="fas fa-save"></i> Create Property
                            </button>
                            <button type="button" class="btn btn-secondary close-admin-modal" style="padding: 12px 30px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 6px;">
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
        
        // Initialize image upload functionality
        this.initializeImageUpload(modalElement);
        
        // Load property owners for dropdown
        this.loadPropertyOwners(modalElement);

                        <!-- Submit Buttons -->
                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 6px; margin-right: 10px;">
                                <i class="fas fa-save"></i> Create Property
                            </button>
                            <button type="button" class="btn btn-secondary close-admin-modal" style="padding: 12px 30px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 6px;">
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
        
        // Load property owners for dropdown
        this.loadPropertyOwners(modalElement);
        
        // Add event listeners
        const closeButtons = modalElement.querySelectorAll('.close-admin-modal');
        const overlay = modalElement.querySelector('.admin-property-modal-overlay');
        const form = modalElement.querySelector('#admin-add-property-form');
        
        closeButtons.forEach(btn => {
            btn.addEventListener('click', () => {
                document.body.removeChild(modalElement);
            });
        });
        
        overlay.addEventListener('click', (e) => {
            // Only close if clicking directly on the overlay, not on any child elements
            if (e.target === overlay) {
                document.body.removeChild(modalElement);
            }
        });
        
        // Prevent modal from closing when clicking inside the modal content
        const modalContent = modalElement.querySelector('.admin-property-modal');
        modalContent.addEventListener('click', (e) => {
            e.stopPropagation();
        });
        
        // Handle form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            e.stopPropagation();
            
            // Disable submit button to prevent double submission
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creating...';
            
            try {
                await this.handleAdminPropertyCreation(form, modalElement);
            } catch (error) {
                console.error('Form submission error:', error);
                this.showNotification('An error occurred while submitting the form', 'error');
            } finally {
                // Re-enable submit button
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
    
    initializeImageUpload(modalElement) {
        const imageUploadArea = modalElement.querySelector('#image-upload-area');
        const imageInput = modalElement.querySelector('#property-images');
        const browseBtn = modalElement.querySelector('#browse-images-btn');
        const previewContainer = modalElement.querySelector('#image-preview-container');
        const previewGrid = modalElement.querySelector('#image-preview-grid');
        
        let selectedFiles = [];
        
        // Browse button click
        browseBtn.addEventListener('click', () => {
            imageInput.click();
        });
        
        // Upload area click
        imageUploadArea.addEventListener('click', (e) => {
            if (e.target === imageUploadArea || e.target.closest('#upload-prompt')) {
                imageInput.click();
            }
        });
        
        // Drag and drop functionality
        imageUploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            imageUploadArea.style.borderColor = '#667eea';
            imageUploadArea.style.backgroundColor = '#f0f4ff';
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
            
            const files = Array.from(e.dataTransfer.files).filter(file => file.type.startsWith('image/'));
            handleFiles(files);
        });
        
        // File input change
        imageInput.addEventListener('change', (e) => {
            const files = Array.from(e.target.files);
            handleFiles(files);
        });
        
        function handleFiles(files) {
            // Validate file count
            if (selectedFiles.length + files.length > 10) {
                alert('You can upload maximum 10 images.');
                return;
            }
            
            files.forEach(file => {
                // Validate file size (5MB)
                if (file.size > 5 * 1024 * 1024) {
                    alert(`File ${file.name} is too large. Maximum size is 5MB.`);
                    return;
                }
                
                // Check for duplicates
                if (selectedFiles.some(f => f.name === file.name && f.size === file.size)) {
                    alert(`File ${file.name} is already selected.`);
                    return;
                }
                
                selectedFiles.push(file);
                addImagePreview(file);
            });
            
            updateImageInput();
        }
        
        function addImagePreview(file) {
            const reader = new FileReader();
            reader.onload = (e) => {
                const previewItem = document.createElement('div');
                previewItem.style.cssText = `
                    position: relative;
                    border-radius: 8px;
                    overflow: hidden;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
                    background: white;
                `;
                
                previewItem.innerHTML = `
                    <img src="${e.target.result}" alt="Preview" style="
                        width: 100%;
                        height: 120px;
                        object-fit: cover;
                    ">
                    <div style="
                        position: absolute;
                        top: 5px;
                        right: 5px;
                        background: rgba(0,0,0,0.7);
                        color: white;
                        border: none;
                        border-radius: 50%;
                        width: 24px;
                        height: 24px;
                        display: flex;
                        align-items: center;
                        justify-content: center;
                        cursor: pointer;
                        font-size: 12px;
                    " class="remove-image" data-filename="${file.name}">
                        ×
                    </div>
                    <div style="
                        padding: 8px;
                        font-size: 12px;
                        color: #4a5568;
                        text-align: center;
                        white-space: nowrap;
                        overflow: hidden;
                        text-overflow: ellipsis;
                    ">
                        ${file.name}
                    </div>
                `;
                
                // Add remove functionality
                const removeBtn = previewItem.querySelector('.remove-image');
                removeBtn.addEventListener('click', () => {
                    const filename = removeBtn.dataset.filename;
                    selectedFiles = selectedFiles.filter(f => f.name !== filename);
                    previewItem.remove();
                    updateImageInput();
                    
                    if (selectedFiles.length === 0) {
                        previewContainer.style.display = 'none';
                    }
                });
                
                previewGrid.appendChild(previewItem);
            };
            
            reader.readAsDataURL(file);
            previewContainer.style.display = 'block';
        }
        
        function updateImageInput() {
            // Create a new DataTransfer object to update the file input
            const dt = new DataTransfer();
            selectedFiles.forEach(file => dt.items.add(file));
            imageInput.files = dt.files;
        }
    }
    
    async loadPropertyOwners(modalElement) {
        try {
            const response = await this.makeAjaxRequest('api/get_property_owners.php');
            const ownerSelect = modalElement.querySelector('select[name="owner_id"]');
            
            if (response.success && response.data) {
                response.data.forEach(owner => {
                    const option = document.createElement('option');
                    option.value = owner.id;
                    option.textContent = `${owner.full_name} (${owner.email})`;
                    ownerSelect.appendChild(option);
                });
            }
        } catch (error) {
            console.error('Error loading property owners:', error);
        }
    }
    
    async handleAdminPropertyCreation(form, modalElement) {
        // Clear previous error messages
        const errorElements = form.querySelectorAll('.text-danger, .error-message');
        errorElements.forEach(el => el.remove());
        
        // Client-side validation
        const validationResult = this.validatePropertyForm(form);
        if (!validationResult.isValid) {
            this.displayFormErrors(form, validationResult.errors);
            throw new Error('Form validation failed');
        }
        
        // Validate that at least one image is selected
        const imageInput = form.querySelector('#property-images');
        if (!imageInput.files || imageInput.files.length === 0) {
            this.showNotification('Please upload at least one property image', 'error');
            throw new Error('No images selected');
        }
        
        try {
            const formData = new FormData(form);
            
            // Collect amenities
            const amenityCheckboxes = form.querySelectorAll('input[name="amenities[]"]');
            const selectedAmenities = Array.from(amenityCheckboxes)
                .filter(checkbox => checkbox.checked)
                .map(checkbox => checkbox.value);
            
            // Add amenities as JSON string
            formData.append('amenities', JSON.stringify(selectedAmenities));
            
            const response = await this.makeAjaxRequestWithFiles('api/admin_add_property_with_images.php', formData);
            
            if (response.success) {
                this.showNotification('Property created successfully!', 'success');
                document.body.removeChild(modalElement);
                this.loadSection('properties'); // Reload properties list
            } else {
                // Handle server-side validation errors
                if (response.errors && typeof response.errors === 'object') {
                    this.displayFormErrors(form, response.errors);
                } else {
                    this.showNotification(response.message || 'Failed to create property', 'error');
                }
                throw new Error(response.message || 'Server validation failed');
            }
        } catch (error) {
            console.error('Property creation error:', error);
            if (!error.message.includes('validation')) {
                this.showNotification('An error occurred while creating property', 'error');
            }
            throw error; // Re-throw to be handled by the form submission handler
        }
    }

    validatePropertyForm(form) {
        const errors = {};
        const phoneRegex = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/; // Sri Lankan phone number validation
        
        // Required field validation
        const requiredFields = {
            'owner_id': 'Property owner',
            'title': 'Property title',
            'property_type': 'Property type',
            'bedrooms': 'Number of bedrooms',
            'bathrooms': 'Number of bathrooms',
            'area_sqft': 'Area in sq ft',
            'rent_amount': 'Monthly rent',
            'security_deposit': 'Security deposit',
            'address': 'Address',
            'city': 'City',
            'state': 'State/Province',
            'zip_code': 'ZIP/Postal code',
            'contact_phone': 'Contact phone',
            'description': 'Description'
        };
        
        Object.keys(requiredFields).forEach(field => {
            const value = form.querySelector(`[name="${field}"]`)?.value?.trim();
            if (!value) {
                errors[field] = `${requiredFields[field]} is required`;
            }
        });
        
        // Phone number validation
        const phoneValue = form.querySelector('[name="contact_phone"]')?.value?.trim();
        if (phoneValue && !phoneRegex.test(phoneValue)) {
            errors['contact_phone'] = 'Please enter a valid Sri Lankan phone number (e.g., 0771234567)';
        }
        
        // Numeric validation
        const numericFields = ['bedrooms', 'bathrooms', 'area_sqft', 'rent_amount', 'security_deposit'];
        numericFields.forEach(field => {
            const value = form.querySelector(`[name="${field}"]`)?.value?.trim();
            if (value && (isNaN(value) || parseFloat(value) <= 0)) {
                errors[field] = `${requiredFields[field]} must be a positive number`;
            }
        });
        
        return {
            isValid: Object.keys(errors).length === 0,
            errors: errors
        };
    }
    
    displayFormErrors(form, errors) {
        let firstErrorField = null;
        
        Object.keys(errors).forEach((fieldName, index) => {
            const field = form.querySelector(`[name="${fieldName}"]`);
            if (field) {
                // Remember first error field for focus
                if (index === 0) {
                    firstErrorField = field;
                }
                
                // Create error message element
                const errorElement = document.createElement('div');
                errorElement.className = 'text-danger error-message';
                errorElement.style.cssText = 'color: #e53e3e; font-size: 12px; margin-top: 2px;';
                errorElement.textContent = errors[fieldName];
                
                // Insert error message after the field and style the field
                field.style.borderColor = '#e53e3e';
                field.style.boxShadow = '0 0 0 2px rgba(229, 62, 62, 0.2)';
                field.parentNode.appendChild(errorElement);
                
                // Add event listener to clear error styling when user starts typing
                const clearErrorStyling = () => {
                    field.style.borderColor = '';
                    field.style.boxShadow = '';
                    if (errorElement.parentNode) {
                        errorElement.parentNode.removeChild(errorElement);
                    }
                    field.removeEventListener('input', clearErrorStyling);
                    field.removeEventListener('change', clearErrorStyling);
                };
                
                field.addEventListener('input', clearErrorStyling);
                field.addEventListener('change', clearErrorStyling);
            }
        });
        
        // Focus on the first error field and scroll it into view
        if (firstErrorField) {
            firstErrorField.focus();
            firstErrorField.scrollIntoView({ 
                behavior: 'smooth', 
                block: 'center' 
            });
        }
    }
    
    async showPropertyViewModal(propertyId) {
        try {
            const response = await this.makeAjaxRequest('api/property_actions.php', {
                action: 'view',
                property_id: propertyId
            });
            
            if (!response.success) {
                this.showNotification('Failed to load property details', 'error');
                return;
            }
            
            const property = response.data;
            
            const modalHtml = `
                <div class="property-view-modal-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 1000;
                ">
                    <div class="property-view-modal" style="
                        background: white;
                        border-radius: 12px;
                        width: 90%;
                        max-width: 800px;
                        max-height: 90vh;
                        overflow-y: auto;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    ">
                        <div class="modal-header" style="padding: 30px 30px 0; border-bottom: 1px solid #e2e8f0;">
                            <h2 style="margin: 0; color: #2d3748; display: flex; justify-content: space-between; align-items: center;">
                                Property Details
                                <button class="close-view-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #718096;">&times;</button>
                            </h2>
                        </div>
                        
                        <div class="modal-body" style="padding: 30px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <!-- Property Information -->
                                <div>
                                    <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                        <i class="fas fa-home"></i> Property Information
                                    </h3>
                                    <div style="space-y: 15px;">
                                        <div class="detail-item" style="margin-bottom: 15px;">
                                            <strong style="color: #4a5568;">Title:</strong>
                                            <div style="margin-top: 5px;">${property.title}</div>
                                        </div>
                                        <div class="detail-item" style="margin-bottom: 15px;">
                                            <strong style="color: #4a5568;">Type:</strong>
                                            <div style="margin-top: 5px;">${property.property_type.charAt(0).toUpperCase() + property.property_type.slice(1)}</div>
                                        </div>
                                        <div class="detail-item" style="margin-bottom: 15px;">
                                            <strong style="color: #4a5568;">Status:</strong>
                                            <div style="margin-top: 5px;">
                                                <span class="status-badge status-${property.status.toLowerCase()}">${property.status.charAt(0).toUpperCase() + property.status.slice(1)}</span>
                                            </div>
                                        </div>
                                        <div class="detail-item" style="margin-bottom: 15px;">
                                            <strong style="color: #4a5568;">Availability:</strong>
                                            <div style="margin-top: 5px;">
                                                <span class="status-badge status-${property.is_available ? 'available' : 'unavailable'}">
                                                    ${property.is_available ? 'Available' : 'Unavailable'}
                                                </span>
                                            </div>
                                        </div>
                                        <div class="detail-item" style="margin-bottom: 15px;">
                                            <strong style="color: #4a5568;">Description:</strong>
                                            <div style="margin-top: 5px; padding: 10px; background: #f7fafc; border-radius: 6px;">
                                                ${property.description || 'No description provided'}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Property Details -->
                                <div>
                                    <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                        <i class="fas fa-info-circle"></i> Details
                                    </h3>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="detail-item">
                                            <strong style="color: #4a5568;">Bedrooms:</strong>
                                            <div style="margin-top: 5px;">${property.bedrooms}</div>
                                        </div>
                                        <div class="detail-item">
                                            <strong style="color: #4a5568;">Bathrooms:</strong>
                                            <div style="margin-top: 5px;">${property.bathrooms}</div>
                                        </div>
                                        ${property.area_sqft ? `
                                        <div class="detail-item">
                                            <strong style="color: #4a5568;">Area:</strong>
                                            <div style="margin-top: 5px;">${property.area_sqft} sqft</div>
                                        </div>
                                        ` : ''}
                                        <div class="detail-item">
                                            <strong style="color: #4a5568;">Monthly Rent:</strong>
                                            <div style="margin-top: 5px; color: #38a169; font-weight: 600;">Rs. ${parseFloat(property.rent_amount).toLocaleString()}</div>
                                        </div>
                                        <div class="detail-item">
                                            <strong style="color: #4a5568;">Security Deposit:</strong>
                                            <div style="margin-top: 5px; color: #e53e3e; font-weight: 600;">Rs. ${parseFloat(property.security_deposit).toLocaleString()}</div>
                                        </div>
                                        <div class="detail-item">
                                            <strong style="color: #4a5568;">Contact Phone:</strong>
                                            <div style="margin-top: 5px;">${property.contact_phone || 'Not provided'}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Location Information -->
                            <div style="margin-top: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                    <i class="fas fa-map-marker-alt"></i> Location
                                </h3>
                                <div style="display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 15px;">
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">Address:</strong>
                                        <div style="margin-top: 5px;">${property.address}</div>
                                    </div>
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">City:</strong>
                                        <div style="margin-top: 5px;">${property.city}</div>
                                    </div>
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">State:</strong>
                                        <div style="margin-top: 5px;">${property.state}</div>
                                    </div>
                                    ${property.zip_code ? `
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">ZIP Code:</strong>
                                        <div style="margin-top: 5px;">${property.zip_code}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <!-- Owner Information -->
                            <div style="margin-top: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                    <i class="fas fa-user"></i> Owner Information
                                </h3>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">Name:</strong>
                                        <div style="margin-top: 5px;">${property.owner_name}</div>
                                    </div>
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">Email:</strong>
                                        <div style="margin-top: 5px;">${property.owner_email}</div>
                                    </div>
                                    ${property.owner_phone ? `
                                    <div class="detail-item">
                                        <strong style="color: #4a5568;">Phone:</strong>
                                        <div style="margin-top: 5px;">${property.owner_phone}</div>
                                    </div>
                                    ` : ''}
                                </div>
                            </div>
                            
                            <!-- Amenities -->
                            ${property.amenities && property.amenities.length > 0 ? `
                            <div style="margin-top: 30px;">
                                <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                    <i class="fas fa-star"></i> Amenities
                                </h3>
                                <div style="display: flex; flex-wrap: wrap; gap: 10px;">
                                    ${property.amenities.map(amenity => `
                                        <span style="background: #e2e8f0; padding: 5px 12px; border-radius: 20px; font-size: 14px;">${amenity}</span>
                                    `).join('')}
                                </div>
                            </div>
                            ` : ''}
                            
                            <!-- Timestamps -->
                            <div style="margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; font-size: 14px; color: #718096;">
                                    <div>
                                        <strong>Created:</strong> ${new Date(property.created_at).toLocaleDateString()}
                                    </div>
                                    ${property.updated_at ? `
                                    <div>
                                        <strong>Last Updated:</strong> ${new Date(property.updated_at).toLocaleDateString()}
                                    </div>
                                    ` : ''}
                                </div>
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
            const closeButtons = modalElement.querySelectorAll('.close-view-modal');
            const overlay = modalElement.querySelector('.property-view-modal-overlay');
            
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
            
            // Prevent modal from closing when clicking inside the modal content
            const modalContent = modalElement.querySelector('.property-view-modal');
            modalContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            
        } catch (error) {
            console.error('Error showing property view modal:', error);
            this.showNotification('Failed to load property details', 'error');
        }
    }
    
    async showPropertyEditModal(propertyId) {
        try {
            const response = await this.makeAjaxRequest('api/property_actions.php', {
                action: 'view',
                property_id: propertyId
            });
            
            if (!response.success) {
                this.showNotification('Failed to load property details', 'error');
                return;
            }
            
            const property = response.data;
            
            const modalHtml = `
                <div class="property-edit-modal-overlay" style="
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background: rgba(0, 0, 0, 0.7);
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    z-index: 1000;
                ">
                    <div class="property-edit-modal" style="
                        background: white;
                        border-radius: 12px;
                        width: 90%;
                        max-width: 900px;
                        max-height: 90vh;
                        overflow-y: auto;
                        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
                    ">
                        <div class="modal-header" style="padding: 30px 30px 0; border-bottom: 1px solid #e2e8f0;">
                            <h2 style="margin: 0; color: #2d3748; display: flex; justify-content: space-between; align-items: center;">
                                Edit Property
                                <button class="close-edit-modal" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #718096;">&times;</button>
                            </h2>
                        </div>
                        
                        <form id="edit-property-form" class="modal-body" style="padding: 30px;">
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">
                                <!-- Basic Information -->
                                <div>
                                    <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                        Basic Information
                                    </h3>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Title *</label>
                                        <input type="text" name="title" value="${property.title}" required class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Type *</label>
                                        <select name="property_type" required class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                            <option value="apartment" ${property.property_type === 'apartment' ? 'selected' : ''}>Apartment</option>
                                            <option value="house" ${property.property_type === 'house' ? 'selected' : ''}>House</option>
                                            <option value="condo" ${property.property_type === 'condo' ? 'selected' : ''}>Condo</option>
                                            <option value="studio" ${property.property_type === 'studio' ? 'selected' : ''}>Studio</option>
                                            <option value="room" ${property.property_type === 'room' ? 'selected' : ''}>Room</option>
                                        </select>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bedrooms *</label>
                                            <input type="number" name="bedrooms" value="${property.bedrooms}" required min="0" max="10" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        </div>
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms *</label>
                                            <input type="number" name="bathrooms" value="${property.bathrooms}" required min="1" max="10" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Area (sq ft)</label>
                                        <input type="number" name="area_sqft" value="${property.area_sqft || ''}" min="100" max="10000" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                                        <textarea name="description" rows="4" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;">${property.description || ''}</textarea>
                                    </div>
                                </div>
                                
                                <!-- Location & Contact -->
                                <div>
                                    <h3 style="color: #2d3748; margin-bottom: 20px; border-bottom: 2px solid #667eea; padding-bottom: 10px;">
                                        Location & Contact
                                    </h3>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Address *</label>
                                        <input type="text" name="address" value="${property.address}" required class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">City *</label>
                                            <input type="text" name="city" value="${property.city}" required class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        </div>
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">State/Province *</label>
                                            <input type="text" name="state" value="${property.state}" required class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        </div>
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">ZIP/Postal Code</label>
                                        <input type="text" name="zip_code" value="${property.zip_code || ''}" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    
                                    <div class="form-group" style="margin-bottom: 20px;">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Contact Phone *</label>
                                        <input type="tel" name="contact_phone" value="${property.contact_phone}" required pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;" placeholder="0771234567">
                                        <small style="color: #718096;">Sri Lankan mobile number format</small>
                                    </div>
                                    
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Monthly Rent (LKR) *</label>
                                            <input type="number" name="rent_amount" value="${property.rent_amount}" required min="5000" step="0.01" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        </div>
                                        <div class="form-group">
                                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Security Deposit (LKR) *</label>
                                            <input type="number" name="security_deposit" value="${property.security_deposit}" required min="5000" step="0.01" class="form-input" style="width: 100%; padding: 12px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Submit Buttons -->
                            <div style="text-align: center; margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0;">
                                <button type="submit" class="btn btn-primary" style="padding: 12px 30px; background: #667eea; color: white; border: none; border-radius: 6px; margin-right: 10px;">
                                    <i class="fas fa-save"></i> Update Property
                                </button>
                                <button type="button" class="btn btn-secondary close-edit-modal" style="padding: 12px 30px; background: #e2e8f0; color: #4a5568; border: none; border-radius: 6px;">
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
            
            // Add event listeners
            const closeButtons = modalElement.querySelectorAll('.close-edit-modal');
            const overlay = modalElement.querySelector('.property-edit-modal-overlay');
            const form = modalElement.querySelector('#edit-property-form');
            
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
            
            // Prevent modal from closing when clicking inside the modal content
            const modalContent = modalElement.querySelector('.property-edit-modal');
            modalContent.addEventListener('click', (e) => {
                e.stopPropagation();
            });
            
            // Handle form submission
            form.addEventListener('submit', async (e) => {
                e.preventDefault();
                e.stopPropagation();
                
                // Disable submit button to prevent double submission
                const submitBtn = form.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
                
                try {
                    await this.handlePropertyUpdate(form, modalElement, propertyId);
                } catch (error) {
                    console.error('Form submission error:', error);
                    this.showNotification('An error occurred while submitting the form', 'error');
                } finally {
                    // Re-enable submit button
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalText;
                }
            });
            
            // Auto-calculate security deposit
            const rentInput = form.querySelector('input[name="rent_amount"]');
            const depositInput = form.querySelector('input[name="security_deposit"]');
            rentInput.addEventListener('input', function(e) {
                const rentAmount = parseFloat(e.target.value);
                if (rentAmount > 0) {
                    const currentDeposit = parseFloat(depositInput.value) || 0;
                    // Only auto-calculate if deposit is empty or exactly double the old rent
                    if (currentDeposit === 0 || currentDeposit === parseFloat(property.rent_amount) * 2) {
                        depositInput.value = rentAmount * 2;
                    }
                }
            });
            
        } catch (error) {
            console.error('Error showing property edit modal:', error);
            this.showNotification('Failed to load property details', 'error');
        }
    }
    
    async handlePropertyUpdate(form, modalElement, propertyId) {
        // Clear previous error messages
        const errorElements = form.querySelectorAll('.text-danger, .error-message');
        errorElements.forEach(el => el.remove());
        
        // Client-side validation
        const validationResult = this.validatePropertyForm(form);
        if (!validationResult.isValid) {
            this.displayFormErrors(form, validationResult.errors);
            throw new Error('Form validation failed');
        }
        
        try {
            const formData = new FormData(form);
            
            // Convert form data to object
            const propertyData = {
                property_id: propertyId,
                title: formData.get('title'),
                property_type: formData.get('property_type'),
                bedrooms: formData.get('bedrooms'),
                bathrooms: formData.get('bathrooms'),
                area_sqft: formData.get('area_sqft'),
                rent_amount: formData.get('rent_amount'),
                security_deposit: formData.get('security_deposit'),
                address: formData.get('address'),
                city: formData.get('city'),
                state: formData.get('state'),
                zip_code: formData.get('zip_code'),
                contact_phone: formData.get('contact_phone'),
                description: formData.get('description')
            };
            
            const response = await this.makeAjaxRequest('api/update_property.php', propertyData);
            
            if (response.success) {
                this.showNotification('Property updated successfully!', 'success');
                document.body.removeChild(modalElement);
                this.loadSection('properties'); // Reload properties list
            } else {
                // Handle server-side validation errors
                if (response.errors && typeof response.errors === 'object') {
                    this.displayFormErrors(form, response.errors);
                } else {
                    this.showNotification(response.message || 'Failed to update property', 'error');
                }
                throw new Error(response.message || 'Server validation failed');
            }
        } catch (error) {
            console.error('Property update error:', error);
            if (!error.message.includes('validation')) {
                this.showNotification('An error occurred while updating property', 'error');
            }
            throw error; // Re-throw to be handled by the form submission handler
        }
    }
    
    handleLogout() {
        if (confirm('Are you sure you want to logout?')) {
            window.location.href = 'logout.php';
        }
    }
}

// Add CSS animation for notifications
const style = document.createElement('style');
style.textContent = `
    @keyframes slideIn {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
`;
document.head.appendChild(style);

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new AdminDashboard();
});

// Export for potential external use
window.AdminDashboard = AdminDashboard;