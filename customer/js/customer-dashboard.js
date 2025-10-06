// Customer Dashboard JavaScript with AJAX functionality
class CustomerDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.customerId = window.customerId || 1;
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('main-content');
        this.toggleBtn = document.getElementById('toggle-btn');
        this.pageTitle = document.getElementById('page-title');
        this.loadingSpinner = document.getElementById('loading-spinner');
        
        this.init();
        this.bindEvents();
        this.loadCustomerStats();
    }

    init() {
        console.log('Customer Dashboard initialized for customer ID:', this.customerId);
        
        // Check if sidebar should be collapsed on small screens
        if (window.innerWidth <= 768) {
            this.sidebar.classList.add('mobile-closed');
        }
    }

    bindEvents() {
        // Sidebar toggle
        this.toggleBtn.addEventListener('click', () => {
            this.toggleSidebar();
        });

        // Navigation links
        document.querySelectorAll('.nav-link').forEach(link => {
            link.addEventListener('click', (e) => {
                e.preventDefault();
                const section = link.getAttribute('data-section');
                if (section) {
                    this.navigateToSection(section);
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

        // Mobile menu overlay click
        document.addEventListener('click', (e) => {
            if (window.innerWidth <= 768 && 
                !this.sidebar.contains(e.target) && 
                !this.toggleBtn.contains(e.target) && 
                this.sidebar.classList.contains('mobile-open')) {
                this.sidebar.classList.remove('mobile-open');
            }
        });

        // Notification bell click
        document.querySelector('.notifications').addEventListener('click', () => {
            this.showNotifications();
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

        // Update navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
        });
        document.querySelector(`[data-section="${section}"]`).parentElement.classList.add('active');

        // Update page title
        const titles = {
            'dashboard': 'Dashboard',
            'browse-properties': 'Browse Properties',
            'wishlist': 'My Wishlist',
            'my-bookings': 'My Bookings',
            'my-visits': 'Property Visits',
            'my-payments': 'My Payments',
            'messages': 'Messages',
            'profile': 'My Profile'
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
    }

    async loadSection(section) {
        this.showLoading();

        try {
            if (section === 'dashboard') {
                this.showDashboardContent();
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

    showDashboardContent() {
        const dashboardContent = document.getElementById('dashboard-content');
        if (dashboardContent) {
            dashboardContent.style.display = 'block';
            dashboardContent.classList.add('active');
        }
    }

    async loadSectionContent(section) {
        try {
            // Create a unique content container for each section
            let contentElement = document.getElementById(`${section}-content`);
            if (!contentElement) {
                contentElement = document.createElement('div');
                contentElement.id = `${section}-content`;
                contentElement.className = 'content-section';
                document.getElementById('content').appendChild(contentElement);
            }

            // Map section names to actual file names
            const sectionFiles = {
                'browse-properties': 'browse_properties.php',
                'wishlist': 'my_wishlist.php',
                'my-bookings': 'my_bookings.php',
                'my-visits': 'my_visits.php',
                'my-payments': 'my_payments.php',
                'messages': 'messages.php',
                'profile': 'profile.php'
            };
            
            const filename = sectionFiles[section] || `${section.replace('-', '_')}.php`;
            
            // Load content from PHP file
            const response = await fetch(`content/${filename}`);
            const html = await response.text();
            
            contentElement.innerHTML = html;
            contentElement.style.display = 'block';
            contentElement.classList.add('active');
            
            // Initialize section-specific functionality
            this.initializeSectionFeatures(section);
            
        } catch (error) {
            console.error(`Failed to load ${section} content:`, error);
            this.showError(`Failed to load ${section} content.`);
        }
    }

    async loadCustomerStats() {
        try {
            const stats = await this.makeAjaxRequest(`api/customer_stats.php?customer_id=${this.customerId}`);
            
            if (stats.success) {
                // Update stats in dashboard home
                const wishlistCount = document.getElementById('wishlist-count');
                const activeBookingsCount = document.getElementById('active-bookings-count');
                const scheduledVisitsCount = document.getElementById('scheduled-visits-count');
                const totalSpent = document.getElementById('total-spent');
                
                if (wishlistCount) wishlistCount.textContent = stats.data.wishlist_count || '0';
                if (activeBookingsCount) activeBookingsCount.textContent = stats.data.active_bookings || '0';
                if (scheduledVisitsCount) scheduledVisitsCount.textContent = stats.data.scheduled_visits || '0';
                if (totalSpent) totalSpent.textContent = stats.data.total_spent || '0';
                
                // Update notification count
                const notificationCount = stats.data.notifications || 0;
                const notificationElement = document.getElementById('notification-count');
                if (notificationElement) {
                    notificationElement.textContent = notificationCount;
                    notificationElement.style.display = notificationCount > 0 ? 'flex' : 'none';
                }
            }

            // Load dashboard widgets
            await this.loadRecentViews();
            await this.loadFeaturedProperties();
            await this.loadUpcomingVisits();
            
        } catch (error) {
            console.error('Error loading customer stats:', error);
        }
    }

    async loadRecentViews() {
        try {
            const response = await this.makeAjaxRequest(`api/recent_views.php?customer_id=${this.customerId}`);
            const container = document.getElementById('recent-views');
            
            if (response.success && response.data.length > 0) {
                const viewsHtml = response.data.map(view => `
                    <div class="property-item" style="padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${view.property_title}</strong><br>
                                <small class="text-muted">${view.location}</small>
                            </div>
                            <div class="text-right">
                                <div style="font-weight: 600; color: #667eea;">Rs. ${view.rent_amount}</div>
                                <small class="text-muted">Viewed ${view.viewed_time}</small>
                            </div>
                        </div>
                    </div>
                `).join('');
                container.innerHTML = viewsHtml;
            } else {
                container.innerHTML = '<p class="text-muted">No recent views found.</p>';
            }
        } catch (error) {
            console.error('Error loading recent views:', error);
            document.getElementById('recent-views').innerHTML = '<p class="text-danger">Failed to load recent views.</p>';
        }
    }

    async loadFeaturedProperties() {
        try {
            const response = await this.makeAjaxRequest(`api/featured_properties.php?customer_id=${this.customerId}`);
            const container = document.getElementById('featured-properties');
            
            if (response.success && response.data.length > 0) {
                const propertiesHtml = response.data.map(property => `
                    <div class="property-item" style="padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${property.title}</strong><br>
                                <small class="text-muted">${property.location}</small>
                            </div>
                            <div class="text-right">
                                <div style="font-weight: 600; color: #667eea;">Rs. ${property.rent_amount}</div>
                                <div style="margin-top: 5px;">
                                    <button class="btn btn-primary btn-sm property-action" data-action="view" data-property-id="${property.id}">
                                        View
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
                container.innerHTML = propertiesHtml;
            } else {
                container.innerHTML = '<p class="text-muted">No featured properties available.</p>';
            }
        } catch (error) {
            console.error('Error loading featured properties:', error);
            document.getElementById('featured-properties').innerHTML = '<p class="text-danger">Failed to load featured properties.</p>';
        }
    }

    async loadUpcomingVisits() {
        try {
            const response = await this.makeAjaxRequest(`api/upcoming_visits.php?customer_id=${this.customerId}`);
            const container = document.getElementById('upcoming-visits');
            
            if (response.success && response.data.length > 0) {
                const visitsHtml = response.data.map(visit => `
                    <div style="padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${visit.property_name}</strong><br>
                                <small class="text-muted">${visit.owner_name}</small>
                            </div>
                            <div class="text-right">
                                <div>${visit.visit_date}</div>
                                <div style="margin-top: 5px;">
                                    <span class="status-badge status-${visit.status.toLowerCase()}">${visit.status}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
                container.innerHTML = visitsHtml;
            } else {
                container.innerHTML = '<p class="text-muted">No upcoming visits scheduled.</p>';
            }
        } catch (error) {
            console.error('Error loading upcoming visits:', error);
            document.getElementById('upcoming-visits').innerHTML = '<p class="text-danger">Failed to load visits.</p>';
        }
    }

    initializeSectionFeatures(section) {
        switch (section) {
            case 'browse-properties':
                this.initializeBrowsePropertiesFeatures();
                break;
            case 'wishlist':
                this.initializeWishlistFeatures();
                break;
            case 'my-bookings':
                this.initializeBookingsFeatures();
                break;
            case 'my-visits':
                this.initializeVisitsFeatures();
                break;
            case 'my-payments':
                this.initializePaymentsFeatures();
                break;
            case 'messages':
                this.initializeMessagesFeatures();
                break;
            case 'profile':
                this.initializeProfileFeatures();
                break;
        }
    }

    initializeBrowsePropertiesFeatures() {
        // Property search and filter functionality
        const searchForm = document.getElementById('property-search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handlePropertySearch(new FormData(searchForm));
            });
        }

        // Property action buttons
        document.querySelectorAll('.property-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const propertyId = e.target.dataset.propertyId;
                this.handlePropertyAction(action, propertyId);
            });
        });

        // Wishlist toggle buttons
        document.querySelectorAll('.wishlist-toggle').forEach(button => {
            button.addEventListener('click', (e) => {
                const propertyId = e.target.dataset.propertyId;
                this.toggleWishlist(propertyId, e.target);
            });
        });
    }

    initializeWishlistFeatures() {
        // Wishlist management functionality
        document.querySelectorAll('.wishlist-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const propertyId = e.target.dataset.propertyId;
                this.handleWishlistAction(action, propertyId);
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

    initializeVisitsFeatures() {
        // Visit management functionality
        document.querySelectorAll('.visit-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const visitId = e.target.dataset.visitId;
                this.handleVisitAction(action, visitId);
            });
        });
    }

    initializePaymentsFeatures() {
        // Payment viewing functionality
        document.querySelectorAll('.payment-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const paymentId = e.target.dataset.paymentId;
                this.handlePaymentAction(action, paymentId);
            });
        });
    }

    initializeMessagesFeatures() {
        // Message functionality
        const messageForm = document.getElementById('message-form');
        if (messageForm) {
            messageForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleMessageSend(new FormData(messageForm));
            });
        }
    }

    initializeProfileFeatures() {
        // Profile form functionality
        const profileForm = document.getElementById('profile-form');
        if (profileForm) {
            profileForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfileUpdate(new FormData(profileForm));
            });
        }
    }

    async handlePropertyAction(action, propertyId) {
        if (action === 'view') {
            window.open(`property_details.php?id=${propertyId}`, '_blank');
        } else if (action === 'book') {
            window.open(`rent_property.php?property_id=${propertyId}`, '_blank');
        }
    }

    async toggleWishlist(propertyId, button) {
        try {
            button.disabled = true;
            const isInWishlist = button.classList.contains('in-wishlist');
            
            const response = await this.makeAjaxRequest('api/wishlist_toggle.php', {
                property_id: propertyId,
                customer_id: this.customerId,
                action: isInWishlist ? 'remove' : 'add'
            });

            if (response.success) {
                button.classList.toggle('in-wishlist');
                button.title = isInWishlist ? 'Add to Wishlist' : 'Remove from Wishlist';
                this.showNotification(response.message, 'success');
                
                // Update wishlist count in stats
                await this.loadCustomerStats();
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        } finally {
            button.disabled = false;
        }
    }

    async handleBookingAction(action, bookingId) {
        if (!confirm(`Are you sure you want to ${action} this booking?`)) return;

        try {
            const response = await this.makeAjaxRequest('api/booking_actions.php', {
                action: action,
                booking_id: bookingId,
                customer_id: this.customerId
            });

            if (response.success) {
                this.showNotification(`Booking ${action}d successfully`, 'success');
                this.loadSection('my-bookings');
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        }
    }

    async handleVisitAction(action, visitId) {
        try {
            const response = await this.makeAjaxRequest('api/visit_actions.php', {
                action: action,
                visit_id: visitId,
                customer_id: this.customerId
            });

            if (response.success) {
                this.showNotification(`Visit ${action}d successfully`, 'success');
                this.loadUpcomingVisits();
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        }
    }

    showNotifications() {
        this.showNotification('Notifications feature coming soon!', 'info');
    }

    async makeAjaxRequest(url, data = null) {
        const options = {
            method: data ? 'POST' : 'GET',
            headers: {
                'Content-Type': 'application/json'
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
            const html = await response.text();
            return { success: true, html: html };
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

        setTimeout(() => {
            notification.remove();
        }, 5000);

        notification.addEventListener('click', () => {
            notification.remove();
        });
    }
}

// Global utility functions for content pages
let dashboardInstance = null;

function loadContent(section, url) {
    if (dashboardInstance) {
        dashboardInstance.navigateToSection(section);
    }
}

function updateStats() {
    if (dashboardInstance) {
        dashboardInstance.loadCustomerStats();
    }
}

function showNotification(message, type = 'info') {
    if (dashboardInstance) {
        dashboardInstance.showNotification(message, type);
    }
}

// Global function for loading content from PHP files
window.loadContent = function(sectionId, url) {
    const contentElement = document.getElementById(sectionId + '-content');
    if (contentElement) {
        // Show loading spinner
        const spinner = document.getElementById('loading-spinner');
        if (spinner) spinner.style.display = 'block';
        
        fetch(url)
            .then(response => response.text())
            .then(html => {
                contentElement.innerHTML = html;
                contentElement.style.display = 'block';
                contentElement.classList.add('active');
                
                // Hide loading spinner
                if (spinner) spinner.style.display = 'none';
            })
            .catch(error => {
                console.error('Error loading content:', error);
                contentElement.innerHTML = '<div class="error-message">Failed to load content. Please try again.</div>';
                if (spinner) spinner.style.display = 'none';
            });
    }
};

// Global function for updating stats
window.updateStats = function() {
    if (window.customerDashboard) {
        window.customerDashboard.loadCustomerStats();
    }
};

// Global function for showing notifications
window.showNotification = function(message, type = 'info') {
    // Create notification element
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : type === 'error' ? 'exclamation-circle' : 'info-circle'}"></i>
        <span>${message}</span>
        <button class="notification-close" onclick="this.parentElement.remove()">
            <i class="fas fa-times"></i>
        </button>
    `;
    
    // Add to page
    document.body.appendChild(notification);
    
    // Auto remove after 5 seconds
    setTimeout(() => {
        if (notification.parentElement) {
            notification.remove();
        }
    }, 5000);
};

// Initialize the dashboard when DOM is loaded
window.addEventListener('DOMContentLoaded', () => {
    window.customerDashboard = new CustomerDashboard();
});

// Export for potential external use
window.CustomerDashboard = CustomerDashboard;
window.loadContent = loadContent;
window.updateStats = updateStats;
window.showNotification = showNotification;
