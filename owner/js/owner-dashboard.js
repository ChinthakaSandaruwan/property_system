// Owner Dashboard JavaScript with AJAX functionality
class OwnerDashboard {
    constructor() {
        this.currentSection = 'dashboard';
        this.ownerId = window.ownerId || 1;
        this.sidebar = document.getElementById('sidebar');
        this.mainContent = document.getElementById('main-content');
        this.toggleBtn = document.getElementById('toggle-btn');
        this.pageTitle = document.getElementById('page-title');
        this.loadingSpinner = document.getElementById('loading-spinner');
        
        this.init();
        this.bindEvents();
        this.loadOwnerStats();
    }

    init() {
        console.log('Owner Dashboard initialized for owner ID:', this.ownerId);
        
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
            'properties': 'My Properties',
            'add-property': 'Add New Property',
            'bookings': 'Property Bookings',
            'visits': 'Property Visits',
            'payments': 'My Payments',
            'analytics': 'Property Analytics',
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
                await this.loadOwnerStats();
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
            const response = await this.makeAjaxRequest(`api/${section.replace('-', '_')}_content.php?owner_id=${this.ownerId}`);
            
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

    async loadOwnerStats() {
        try {
            const stats = await this.makeAjaxRequest(`api/owner_stats.php?owner_id=${this.ownerId}`);
            
            if (stats.success) {
                document.getElementById('total-properties').textContent = stats.data.total_properties || '0';
                document.getElementById('active-bookings').textContent = stats.data.active_bookings || '0';
                document.getElementById('pending-visits').textContent = stats.data.pending_visits || '0';
                document.getElementById('monthly-earnings').textContent = `Rs. ${stats.data.monthly_earnings || '0'}`;
                
                // Update notification count
                const notificationCount = stats.data.notifications || 0;
                document.getElementById('notification-count').textContent = notificationCount;
                document.getElementById('notification-count').style.display = notificationCount > 0 ? 'flex' : 'none';
            }

            // Load dashboard widgets
            await this.loadRecentBookings();
            await this.loadPropertyPerformance();
            await this.loadUpcomingVisits();
            
        } catch (error) {
            console.error('Error loading owner stats:', error);
        }
    }

    async loadRecentBookings() {
        try {
            const response = await this.makeAjaxRequest(`api/recent_bookings.php?owner_id=${this.ownerId}`);
            const container = document.getElementById('recent-bookings');
            
            if (response.success && response.data.length > 0) {
                const bookingsHtml = response.data.map(booking => `
                    <div class="booking-item" style="padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${booking.property_name}</strong><br>
                                <small class="text-muted">by ${booking.customer_name}</small>
                            </div>
                            <div class="text-right">
                                <div class="status-badge status-${booking.status.toLowerCase()}">${booking.status}</div>
                                <small class="text-muted">Rs. ${booking.amount}</small>
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

    async loadPropertyPerformance() {
        try {
            const response = await this.makeAjaxRequest(`api/property_performance.php?owner_id=${this.ownerId}`);
            const container = document.getElementById('property-performance');
            
            if (response.success && response.data.length > 0) {
                const performanceHtml = response.data.map((property, index) => `
                    <div style="display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f7fafc;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <span style="background: ${index < 3 ? '#38a169' : '#e2e8f0'}; color: ${index < 3 ? 'white' : '#4a5568'}; width: 24px; height: 24px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 0.75rem; font-weight: bold;">${index + 1}</span>
                            <div>
                                <strong>${property.property_name}</strong><br>
                                <small class="text-muted">${property.bookings_count} bookings</small>
                            </div>
                        </div>
                        <div class="text-right">
                            <strong class="text-success">Rs. ${property.revenue}</strong>
                        </div>
                    </div>
                `).join('');
                container.innerHTML = performanceHtml;
            } else {
                container.innerHTML = '<p class="text-muted">No performance data available yet.</p>';
            }
        } catch (error) {
            console.error('Error loading property performance:', error);
            document.getElementById('property-performance').innerHTML = '<p class="text-danger">Failed to load performance data.</p>';
        }
    }

    async loadUpcomingVisits() {
        try {
            const response = await this.makeAjaxRequest(`api/upcoming_visits.php?owner_id=${this.ownerId}`);
            const container = document.getElementById('upcoming-visits');
            
            if (response.success && response.data.length > 0) {
                const visitsHtml = response.data.map(visit => `
                    <div style="padding: 10px 0; border-bottom: 1px solid #e2e8f0;">
                        <div style="display: flex; justify-content: space-between; align-items: center;">
                            <div>
                                <strong>${visit.property_name}</strong><br>
                                <small class="text-muted">${visit.customer_name}</small>
                            </div>
                            <div class="text-right">
                                <div>${visit.visit_date}</div>
                                <div style="margin-top: 5px;">
                                    <button class="btn btn-success btn-sm visit-action" data-action="approve" data-visit-id="${visit.id}">Approve</button>
                                    <button class="btn btn-secondary btn-sm visit-action" data-action="reject" data-visit-id="${visit.id}">Reject</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('');
                container.innerHTML = visitsHtml;
            } else {
                container.innerHTML = '<p class="text-muted">No upcoming visits.</p>';
            }
        } catch (error) {
            console.error('Error loading upcoming visits:', error);
            document.getElementById('upcoming-visits').innerHTML = '<p class="text-danger">Failed to load visits.</p>';
        }
    }

    initializeSectionFeatures(section) {
        switch (section) {
            case 'properties':
                this.initializePropertiesFeatures();
                break;
            case 'add-property':
                this.initializeAddPropertyFeatures();
                break;
            case 'bookings':
                this.initializeBookingsFeatures();
                break;
            case 'visits':
                this.initializeVisitsFeatures();
                break;
            case 'payments':
                this.initializePaymentsFeatures();
                break;
            case 'analytics':
                this.initializeAnalyticsFeatures();
                break;
            case 'profile':
                this.initializeProfileFeatures();
                break;
        }
    }

    initializePropertiesFeatures() {
        // Property management functionality
        document.querySelectorAll('.property-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const propertyId = e.target.dataset.propertyId || e.target.getAttribute('data-property-id');
                this.handlePropertyAction(action, propertyId);
            });
        });
    }

    initializeAddPropertyFeatures() {
        // Property form functionality
        const form = document.getElementById('add-property-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handlePropertySubmission(new FormData(form));
            });
        }

        // Image upload functionality
        this.initializeImageUpload();
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

    initializePaymentsFeatures() {
        // Payment action functionality
        document.querySelectorAll('.payment-action').forEach(button => {
            button.addEventListener('click', (e) => {
                const action = e.target.dataset.action;
                const paymentId = e.target.dataset.paymentId;
                const bookingId = e.target.dataset.bookingId;
                
                if (action === 'view') {
                    this.viewPaymentDetails(paymentId);
                } else if (action === 'receipt') {
                    this.downloadPaymentReceipt(paymentId);
                } else if (action === 'booking') {
                    this.viewBookingDetails(bookingId);
                }
            });
        });
    }

    initializeAnalyticsFeatures() {
        // Analytics functionality - charts are already loaded by the content
        // Just initialize any interactive elements if needed
        console.log('Analytics features initialized');
        
        // Initialize any analytics-specific event handlers
        this.initializeAnalyticsEventHandlers();
    }
    
    initializeAnalyticsEventHandlers() {
        // Handle any analytics-specific interactions
        // Charts are already loaded by Chart.js in the analytics_content.php
        
        // Example: Add refresh functionality
        const refreshBtn = document.querySelector('.refresh-analytics');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', () => {
                this.loadSection('analytics');
            });
        }
    }

    initializeProfileFeatures() {
        // Profile form functionality
        const form = document.getElementById('profile-form');
        if (form) {
            form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleProfileUpdate(new FormData(form));
            });
        }
    }

    initializeImageUpload() {
        const uploadArea = document.querySelector('.upload-area');
        if (!uploadArea) return;

        uploadArea.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadArea.classList.add('dragover');
        });

        uploadArea.addEventListener('dragleave', () => {
            uploadArea.classList.remove('dragover');
        });

        uploadArea.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadArea.classList.remove('dragover');
            const files = e.dataTransfer.files;
            // Update the form input with dropped files
            const existingInput = document.getElementById('property-images-input');
            if (existingInput && files.length > 0) {
                existingInput.files = files;
            }
            this.handleImageFiles(files);
        });

        uploadArea.addEventListener('click', () => {
            // Use the existing form input if available, otherwise create new one
            const existingInput = document.getElementById('property-images-input');
            if (existingInput) {
                existingInput.onchange = (e) => this.handleImageFiles(e.target.files);
                existingInput.click();
            } else {
                const input = document.createElement('input');
                input.type = 'file';
                input.multiple = true;
                input.accept = 'image/*';
                input.onchange = (e) => this.handleImageFiles(e.target.files);
                input.click();
            }
        });
    }

    async handlePropertyAction(action, propertyId) {
        try {
            // Special handling for view action
            if (action === 'view') {
                const response = await this.makeAjaxRequest('api/property_actions.php', {
                    action: action,
                    property_id: propertyId,
                    owner_id: this.ownerId
                });
                
                if (response.success) {
                    this.showPropertyModal(response.data);
                } else {
                    this.showNotification(response.message || 'Failed to load property details', 'error');
                }
                return;
            }
            
            // Special handling for edit action
            if (action === 'edit') {
                const response = await this.makeAjaxRequest('api/property_actions.php', {
                    action: action,
                    property_id: propertyId,
                    owner_id: this.ownerId
                });
                
                if (response.success) {
                    this.showEditPropertyModal(response.data);
                } else {
                    this.showNotification(response.message || 'Failed to load property for editing', 'error');
                }
                return;
            }
            
            // Special handling for delete action
            if (action === 'delete') {
                if (!confirm('Are you sure you want to delete this property? This action cannot be undone.')) {
                    return;
                }
            }
            
            const response = await this.makeAjaxRequest('api/property_actions.php', {
                action: action,
                property_id: propertyId,
                owner_id: this.ownerId
            });

            if (response.success) {
                this.showNotification(response.message || 'Action completed successfully', 'success');
                this.loadSection('properties'); // Reload the section
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
                owner_id: this.ownerId
            });

            if (response.success) {
                this.showNotification(`Visit ${action}d successfully`, 'success');
                this.loadUpcomingVisits(); // Reload visits widget
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
                booking_id: bookingId,
                owner_id: this.ownerId
            });

            if (response.success) {
                this.showNotification(`Booking ${action}d successfully`, 'success');
                this.loadSection('bookings');
            } else {
                this.showNotification(response.message || 'Action failed', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred', 'error');
        }
    }

    async handlePropertySubmission(formData) {
        formData.append('owner_id', this.ownerId);
        
        try {
            const response = await this.makeAjaxRequest('api/add_property.php', formData, true);

            if (response.success) {
                this.showNotification('Property added successfully! It will be reviewed by admin.', 'success');
                document.getElementById('add-property-form').reset();
            } else {
                this.showNotification(response.message || 'Failed to add property', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred while adding property', 'error');
        }
    }

    async handleProfileUpdate(formData) {
        formData.append('owner_id', this.ownerId);
        
        try {
            const response = await this.makeAjaxRequest('api/update_profile.php', formData, true);

            if (response.success) {
                this.showNotification('Profile updated successfully', 'success');
            } else {
                this.showNotification(response.message || 'Failed to update profile', 'error');
            }
        } catch (error) {
            this.showNotification('An error occurred while updating profile', 'error');
        }
    }

    handleImageFiles(files) {
        // Handle image file uploads
        Array.from(files).forEach(file => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.displayImagePreview(e.target.result, file.name);
                };
                reader.readAsDataURL(file);
            }
        });
    }

    displayImagePreview(src, filename) {
        const previewContainer = document.querySelector('.image-previews') || this.createPreviewContainer();
        
        const preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = `
            <img src="${src}" alt="${filename}" style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
            <button type="button" class="remove-image" onclick="this.parentElement.remove()">×</button>
        `;
        
        previewContainer.appendChild(preview);
    }

    createPreviewContainer() {
        const container = document.createElement('div');
        container.className = 'image-previews';
        container.style.display = 'flex';
        container.style.gap = '10px';
        container.style.marginTop = '20px';
        container.style.flexWrap = 'wrap';
        
        document.querySelector('.upload-area').parentNode.appendChild(container);
        return container;
    }

    showNotifications() {
        // Show notifications dropdown
        this.showNotification('Notifications feature coming soon!', 'info');
    }
    
    showPropertyModal(propertyData) {
        // Create modal HTML
        const modalHtml = `
            <div class="property-modal-overlay" style="
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
                <div class="property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 30px;
                    max-width: 800px;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
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
                    
                    <h2 style="margin-bottom: 20px; color: #2d3748;">${propertyData.title}</h2>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                        <div>
                            <h3 style="margin-bottom: 15px; color: #38a169;">Property Details</h3>
                            <div style="margin-bottom: 10px;"><strong>Type:</strong> ${propertyData.property_type}</div>
                            <div style="margin-bottom: 10px;"><strong>Rent:</strong> Rs. ${Number(propertyData.rent_amount).toLocaleString()}/month</div>
                            <div style="margin-bottom: 10px;"><strong>Security Deposit:</strong> Rs. ${Number(propertyData.security_deposit).toLocaleString()}</div>
                            <div style="margin-bottom: 10px;"><strong>Bedrooms:</strong> ${propertyData.bedrooms}</div>
                            <div style="margin-bottom: 10px;"><strong>Bathrooms:</strong> ${propertyData.bathrooms}</div>
                            ${propertyData.area_sqft ? `<div style="margin-bottom: 10px;"><strong>Area:</strong> ${propertyData.area_sqft} sqft</div>` : ''}
                            <div style="margin-bottom: 10px;"><strong>Status:</strong> 
                                <span class="status-badge status-${propertyData.status.toLowerCase()}">${propertyData.status}</span>
                            </div>
                            <div style="margin-bottom: 10px;"><strong>Availability:</strong> ${propertyData.is_available ? 'Available' : 'Occupied'}</div>
                            <div style="margin-bottom: 10px;"><strong>Total Bookings:</strong> ${propertyData.booking_count || 0}</div>
                        </div>
                        
                        <div>
                            <h3 style="margin-bottom: 15px; color: #38a169;">Location & Contact</h3>
                            <div style="margin-bottom: 10px;"><strong>Address:</strong> ${propertyData.address}</div>
                            <div style="margin-bottom: 10px;"><strong>City:</strong> ${propertyData.city}</div>
                            <div style="margin-bottom: 10px;"><strong>State:</strong> ${propertyData.state}</div>
                            ${propertyData.zip_code ? `<div style="margin-bottom: 10px;"><strong>ZIP:</strong> ${propertyData.zip_code}</div>` : ''}
                            ${propertyData.contact_phone ? `<div style="margin-bottom: 10px;"><strong>Contact:</strong> ${propertyData.contact_phone}</div>` : ''}
                            
                            ${propertyData.description ? `
                                <div style="margin-top: 20px;">
                                    <h4 style="margin-bottom: 10px;">Description</h4>
                                    <p style="color: #666; line-height: 1.5;">${propertyData.description}</p>
                                </div>
                            ` : ''}
                        </div>
                    </div>
                    
                    ${propertyData.image_urls && propertyData.image_urls.length > 0 ? `
                        <div style="margin-bottom: 20px;">
                            <h3 style="margin-bottom: 15px; color: #38a169;">Images</h3>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 10px;">
                                ${propertyData.image_urls.map(url => `
                                    <img src="${url}" alt="Property image" style="
                                        width: 100%;
                                        height: 120px;
                                        object-fit: cover;
                                        border-radius: 8px;
                                        cursor: pointer;
                                    " onclick="window.open('${url}', '_blank')">
                                `).join('')}
                            </div>
                        </div>
                    ` : ''}
                    
                    ${this.getAmenitiesHtml(propertyData.amenities)}
                    
                    <div style="text-align: right; margin-top: 30px;">
                        <button class="btn btn-secondary close-modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        // Add modal to page
        const modalElement = document.createElement('div');
        modalElement.innerHTML = modalHtml;
        document.body.appendChild(modalElement);
        
        // Add event listeners for closing modal
        const closeButtons = modalElement.querySelectorAll('.close-modal');
        const overlay = modalElement.querySelector('.property-modal-overlay');
        
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
    }
    
    getAmenitiesHtml(amenitiesJson) {
        try {
            if (!amenitiesJson) return '';
            const amenities = JSON.parse(amenitiesJson);
            if (!Array.isArray(amenities) || amenities.length === 0) return '';
            
            return `
                <div style="margin-bottom: 20px;">
                    <h3 style="margin-bottom: 15px; color: #38a169;">Amenities</h3>
                    <div style="display: flex; flex-wrap: wrap; gap: 8px;">
                        ${amenities.map(amenity => `
                            <span style="
                                background: #e6fffa;
                                color: #2d7d32;
                                padding: 4px 8px;
                                border-radius: 12px;
                                font-size: 0.875rem;
                            ">${amenity}</span>
                        `).join('')}
                    </div>
                </div>
            `;
        } catch (e) {
            console.error('Error parsing amenities:', e);
            return '';
        }
    }
    
    showEditPropertyModal(propertyData) {
        const amenitiesList = ['Air Conditioning', 'Parking', 'WiFi', 'Security', 'Swimming Pool', 'Gym', 'Garden', 'Furnished', 'Pet Friendly', 'Elevator'];
        const propertyTypes = ['apartment', 'house', 'villa', 'studio', 'commercial'];
        const existingAmenities = propertyData.amenities_array || [];
        
        const modalHtml = `
            <div class="edit-property-modal-overlay" style="
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
                <div class="edit-property-modal" style="
                    background: white;
                    border-radius: 12px;
                    padding: 30px;
                    max-width: 900px;
                    max-height: 90vh;
                    overflow-y: auto;
                    position: relative;
                    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.2);
                ">
                    <button class="close-edit-modal" style="
                        position: absolute;
                        top: 15px;
                        right: 20px;
                        background: none;
                        border: none;
                        font-size: 24px;
                        cursor: pointer;
                        color: #666;
                    ">×</button>
                    
                    <h2 style="margin-bottom: 20px; color: #2d3748;">Edit Property</h2>
                    
                    <form id="edit-property-form">
                        <input type="hidden" name="property_id" value="${propertyData.id}">
                        
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 20px;">
                            <!-- Left Column -->
                            <div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Title *</label>
                                    <input type="text" name="title" class="form-input" required value="${propertyData.title || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Property Type *</label>
                                    <select name="property_type" class="form-input" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                        ${propertyTypes.map(type => `
                                            <option value="${type}" ${propertyData.property_type === type ? 'selected' : ''}>
                                                ${type.charAt(0).toUpperCase() + type.slice(1)}
                                            </option>
                                        `).join('')}
                                    </select>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bedrooms *</label>
                                        <input type="number" name="bedrooms" class="form-input" required min="0" max="10" value="${propertyData.bedrooms || 1}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Bathrooms *</label>
                                        <input type="number" name="bathrooms" class="form-input" required min="1" max="10" value="${propertyData.bathrooms || 1}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">Area (sqft)</label>
                                        <input type="number" name="area_sqft" class="form-input" min="100" max="10000" value="${propertyData.area_sqft || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Monthly Rent (LKR) *</label>
                                    <input type="number" name="rent_amount" class="form-input" required min="5000" step="100" value="${propertyData.rent_amount || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Security Deposit (LKR) *</label>
                                    <input type="number" name="security_deposit" class="form-input" required min="5000" step="100" value="${propertyData.security_deposit || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>
                            </div>

                            <!-- Right Column -->
                            <div>
                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Address *</label>
                                    <textarea name="address" class="form-input" rows="3" required style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;">${propertyData.address || ''}</textarea>
                                </div>

                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">City *</label>
                                        <input type="text" name="city" class="form-input" required value="${propertyData.city || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                    <div class="form-group">
                                        <label style="display: block; margin-bottom: 5px; font-weight: 500;">State *</label>
                                        <input type="text" name="state" class="form-input" required value="${propertyData.state || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    </div>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">ZIP Code</label>
                                    <input type="text" name="zip_code" class="form-input" value="${propertyData.zip_code || ''}" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Contact Phone Number *</label>
                                    <input type="tel" name="contact_phone" class="form-input" required 
                                           pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" 
                                           value="${propertyData.contact_phone || ''}" 
                                           style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px;">
                                    <small style="color: #666; font-size: 0.875rem;">Enter Sri Lankan mobile number (e.g., 0771234567)</small>
                                </div>

                                <div class="form-group" style="margin-bottom: 15px;">
                                    <label style="display: block; margin-bottom: 5px; font-weight: 500;">Description</label>
                                    <textarea name="description" class="form-input" rows="4" style="width: 100%; padding: 10px; border: 2px solid #e2e8f0; border-radius: 6px; resize: vertical;">${propertyData.description || ''}</textarea>
                                </div>
                            </div>
                        </div>

                        <!-- Amenities Section -->
                        <div class="form-group" style="margin-bottom: 20px;">
                            <label style="display: block; margin-bottom: 10px; font-weight: 500;">Amenities</label>
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px;">
                                ${amenitiesList.map(amenity => `
                                    <label style="display: flex; align-items: center; gap: 8px;">
                                        <input type="checkbox" name="amenities[]" value="${amenity}" ${existingAmenities.includes(amenity) ? 'checked' : ''}> ${amenity}
                                    </label>
                                `).join('')}
                            </div>
                        </div>

                        <!-- Submit Buttons -->
                        <div style="text-align: center; margin-top: 30px;">
                            <button type="submit" class="btn btn-primary" style="padding: 12px 30px; background: #38a169; color: white; border: none; border-radius: 6px; margin-right: 10px;">
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
        const overlay = modalElement.querySelector('.edit-property-modal-overlay');
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
        
        // Add phone number validation
        const phoneInput = form.querySelector('input[name="contact_phone"]');
        if (phoneInput) {
            phoneInput.addEventListener('input', function(e) {
                const phoneRegex = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/;
                const value = e.target.value;
                
                if (value && !phoneRegex.test(value)) {
                    e.target.setCustomValidity('Please enter a valid Sri Lankan phone number (e.g., 0771234567)');
                } else {
                    e.target.setCustomValidity('');
                }
            });
        }
        
        // Add auto-calculation for security deposit
        const rentInput = form.querySelector('input[name="rent_amount"]');
        const depositInput = form.querySelector('input[name="security_deposit"]');
        if (rentInput && depositInput) {
            rentInput.addEventListener('input', function(e) {
                const rentAmount = parseFloat(e.target.value);
                if (rentAmount > 0) {
                    // Only auto-calculate if deposit field is empty or has the default 2x rent value
                    const currentDeposit = parseFloat(depositInput.value) || 0;
                    const expectedDeposit = rentAmount * 2;
                    
                    // If the current deposit is exactly 2x the previous rent, update it
                    if (currentDeposit === 0 || Math.abs(currentDeposit - expectedDeposit) > rentAmount) {
                        depositInput.value = expectedDeposit;
                    }
                }
            });
        }
        
        // Handle form submission
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            await this.handlePropertyUpdate(form, modalElement);
        });
    }
    
    async handlePropertyUpdate(form, modalElement) {
        try {
            // Get form data
            const formData = new FormData(form);
            
            // Convert form data to object for JSON submission
            const updateData = {
                action: 'update',
                property_id: formData.get('property_id'),
                owner_id: this.ownerId,
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
                description: formData.get('description'),
                amenities: formData.getAll('amenities[]')
            };
            
            const response = await this.makeAjaxRequest('api/property_actions.php', updateData);
            
            if (response.success) {
                this.showNotification(response.message || 'Property updated successfully!', 'success');
                document.body.removeChild(modalElement);
                this.loadSection('properties'); // Reload properties list
            } else {
                this.showNotification(response.message || 'Failed to update property', 'error');
            }
        } catch (error) {
            console.error('Property update error:', error);
            this.showNotification('An error occurred while updating property', 'error');
        }
    }

    async makeAjaxRequest(url, data = null, isFormData = false) {
        const options = {
            method: data ? 'POST' : 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        };

        if (data && !isFormData) {
            options.headers['Content-Type'] = 'application/json';
            options.body = JSON.stringify(data);
        } else if (data && isFormData) {
            options.body = data;
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

    viewPaymentDetails(paymentId) {
        // Show payment details in a modal or redirect
        this.showNotification('Payment details feature coming soon', 'info');
    }
    
    downloadPaymentReceipt(paymentId) {
        // Download receipt functionality
        window.open(`api/download_receipt.php?payment_id=${paymentId}&owner_id=${this.ownerId}`, '_blank');
    }
    
    viewBookingDetails(bookingId) {
        // Navigate to booking details or show in modal
        this.navigateToSection('bookings');
        this.showNotification('Booking details loaded', 'info');
    }

    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <div style="
                position: fixed;
                top: 20px;
                right: 20px;
                background: ${type === 'success' ? '#38a169' : type === 'error' ? '#e53e3e' : '#38b2ac'};
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

// Initialize dashboard when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    new OwnerDashboard();
});

// Export for potential external use
window.OwnerDashboard = OwnerDashboard;