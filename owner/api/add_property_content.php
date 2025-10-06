<?php
require_once dirname(dirname(__DIR__)) . '/includes/config.php';
?>

<div class="section-header" style="margin-bottom: 30px;">
    <h2>Add New Property</h2>
    <p class="text-muted">Fill in the details below to list your property for rent.</p>
</div>

<form id="add-property-form" enctype="multipart/form-data">
    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-bottom: 30px;">
        <!-- Left Column -->
        <div>
            <div class="form-group">
                <label class="form-label">Property Title *</label>
                <input type="text" name="title" class="form-input" required placeholder="e.g., Modern 3BR Apartment in Colombo">
            </div>

            <div class="form-group">
                <label class="form-label">Property Type *</label>
                <select name="property_type" class="form-input" required>
                    <option value="">Select Property Type</option>
                    <option value="apartment">Apartment</option>
                    <option value="house">House</option>
                    <option value="villa">Villa</option>
                    <option value="studio">Studio</option>
                    <option value="commercial">Commercial</option>
                </select>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">Bedrooms *</label>
                    <input type="number" name="bedrooms" class="form-input" required min="0" max="10" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Bathrooms *</label>
                    <input type="number" name="bathrooms" class="form-input" required min="1" max="10" value="1">
                </div>
                <div class="form-group">
                    <label class="form-label">Area (sqft)</label>
                    <input type="number" name="area_sqft" class="form-input" min="100" max="10000">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Monthly Rent (LKR) *</label>
                <input type="number" name="rent_amount" class="form-input" required min="5000" step="100" placeholder="50000">
            </div>

            <div class="form-group">
                <label class="form-label">Security Deposit (LKR) *</label>
                <input type="number" name="security_deposit" class="form-input" required min="5000" step="100" placeholder="100000">
            </div>
        </div>

        <!-- Right Column -->
        <div>
            <div class="form-group">
                <label class="form-label">Address *</label>
                <textarea name="address" class="form-input" rows="3" required placeholder="Full address with street, area, city"></textarea>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label class="form-label">City *</label>
                    <input type="text" name="city" class="form-input" required placeholder="e.g., Colombo">
                </div>
                <div class="form-group">
                    <label class="form-label">State *</label>
                    <input type="text" name="state" class="form-input" required placeholder="e.g., Western Province">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">ZIP Code</label>
                <input type="text" name="zip_code" class="form-input" placeholder="e.g., 00100">
            </div>

            <div class="form-group">
                <label class="form-label">Description</label>
                <textarea name="description" class="form-input" rows="4" placeholder="Describe your property, its features, and nearby amenities..."></textarea>
            </div>
        </div>
    </div>

    <!-- Amenities Section -->
    <div class="form-group">
        <label class="form-label">Amenities</label>
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-top: 10px;">
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Air Conditioning"> Air Conditioning
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Parking"> Parking
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="WiFi"> WiFi
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Security"> Security
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Swimming Pool"> Swimming Pool
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Gym"> Gym
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Garden"> Garden
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Furnished"> Furnished
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Pet Friendly"> Pet Friendly
            </label>
            <label style="display: flex; align-items: center; gap: 8px;">
                <input type="checkbox" name="amenities[]" value="Elevator"> Elevator
            </label>
        </div>
    </div>

    <!-- Image Upload Section -->
    <div class="form-group">
        <label class="form-label">Property Images</label>
        <div class="upload-area">
            <i class="fas fa-cloud-upload-alt" style="font-size: 3rem; color: #38a169; margin-bottom: 15px;"></i>
            <h3 style="color: #38a169; margin-bottom: 10px;">Drop images here or click to upload</h3>
            <p class="text-muted">You can upload multiple images. Supported formats: JPG, PNG, GIF</p>
            <p class="text-muted">Maximum file size: 5MB per image</p>
        </div>
        <input type="file" name="property_images[]" multiple accept="image/*" style="display: none;" id="property-images-input">
    </div>

    <!-- Phone Number Validation -->
    <div class="form-group">
        <label class="form-label">Contact Phone Number *</label>
        <input type="tel" name="contact_phone" class="form-input" required 
               pattern="^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$" 
               placeholder="0771234567"
               title="Please enter a valid Sri Lankan mobile number (e.g., 0771234567)">
        <small class="text-muted">Enter your Sri Lankan mobile number (10 digits starting with 07)</small>
    </div>

    <!-- Submit Button -->
    <div style="text-align: center; margin-top: 30px;">
        <button type="submit" class="btn btn-primary" style="padding: 15px 40px; font-size: 1.1rem;">
            <i class="fas fa-plus-circle"></i> Add Property
        </button>
        <button type="button" class="btn btn-secondary" style="padding: 15px 40px; font-size: 1.1rem; margin-left: 15px;" onclick="document.getElementById('add-property-form').reset()">
            <i class="fas fa-undo"></i> Reset Form
        </button>
    </div>
</form>

<style>
.form-input {
    width: 100%;
    padding: 12px;
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    font-size: 14px;
    transition: border-color 0.3s ease;
}

.form-input:focus {
    outline: none;
    border-color: #38a169;
}

.upload-area {
    border: 2px dashed #38a169;
    border-radius: 8px;
    padding: 40px;
    text-align: center;
    background: #f0fff4;
    transition: all 0.3s ease;
    cursor: pointer;
}

.upload-area:hover {
    border-color: #2d7d32;
    background: #e6fffa;
}
</style>

<script>
// Phone number validation for Sri Lankan numbers
document.querySelector('input[name="contact_phone"]').addEventListener('input', function(e) {
    const phoneRegex = /^[0]{1}[7]{1}[01245678]{1}[0-9]{7}$/;
    const value = e.target.value;
    
    if (value && !phoneRegex.test(value)) {
        e.target.setCustomValidity('Please enter a valid Sri Lankan phone number (e.g., 0771234567)');
    } else {
        e.target.setCustomValidity('');
    }
});

// Calculate security deposit automatically (2x rent by default)
document.querySelector('input[name="rent_amount"]').addEventListener('input', function(e) {
    const rentAmount = parseFloat(e.target.value);
    if (rentAmount > 0) {
        const securityDepositField = document.querySelector('input[name="security_deposit"]');
        if (!securityDepositField.value) {
            securityDepositField.value = rentAmount * 2;
        }
    }
});

// Upload area click handler is handled by the dashboard JavaScript
// The dashboard's initializeImageUpload() handles both click and drag/drop
// for the upload area element
</script>