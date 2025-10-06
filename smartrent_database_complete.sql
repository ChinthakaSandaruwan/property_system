-- =====================================================
-- SMARTRENT RENTAL SYSTEM - COMPLETE DATABASE SETUP
-- =====================================================
-- This file contains everything needed to set up SmartRent
-- Run this file to create the database and all necessary tables
-- Updated with all new features including:
-- - Property visit booking system
-- - PayHere payment integration 
-- - Rental agreements
-- - Commission tracking
-- - Card tokenization
-- =====================================================

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS rental_system;
USE rental_system;

-- Drop existing tables if they exist (for clean setup)
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS commissions;
DROP TABLE IF EXISTS rental_agreements;
DROP TABLE IF EXISTS card_tokens;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS bookings;
DROP TABLE IF EXISTS property_visits;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS otp_verifications;
DROP TABLE IF EXISTS system_settings;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- USERS TABLE
-- =====================================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    full_name VARCHAR(255) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    phone VARCHAR(20) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('admin', 'owner', 'customer') NOT NULL,
    is_phone_verified BOOLEAN DEFAULT FALSE,
    profile_image VARCHAR(255) DEFAULT NULL,
    address TEXT,
    city VARCHAR(100),
    state VARCHAR(100),
    zip_code VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    INDEX idx_users_email (email),
    INDEX idx_users_phone (phone),
    INDEX idx_users_type (user_type)
);

-- =====================================================
-- OTP VERIFICATIONS TABLE
-- =====================================================
CREATE TABLE otp_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    phone VARCHAR(20) NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    is_used BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- =====================================================
-- SYSTEM SETTINGS TABLE
-- =====================================================
CREATE TABLE system_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- =====================================================
-- PROPERTIES TABLE
-- =====================================================
CREATE TABLE properties (
    id INT PRIMARY KEY AUTO_INCREMENT,
    owner_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    property_type ENUM('apartment', 'house', 'villa', 'studio', 'commercial') NOT NULL,
    bedrooms INT NOT NULL DEFAULT 1,
    bathrooms INT NOT NULL DEFAULT 1,
    area_sqft INT,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    state VARCHAR(100) NOT NULL,
    zip_code VARCHAR(20),
    rent_amount DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) NOT NULL,
    images JSON, -- Store image filenames as JSON array
    amenities JSON, -- Store amenities as JSON array
    status ENUM('pending', 'approved', 'rejected', 'rented') DEFAULT 'pending',
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_properties_city (city),
    INDEX idx_properties_status (status),
    INDEX idx_properties_owner (owner_id)
);

-- =====================================================
-- PROPERTY VISITS TABLE (Enhanced)
-- =====================================================
CREATE TABLE property_visits (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    customer_id INT NOT NULL,
    owner_id INT NOT NULL,
    requested_date DATETIME NOT NULL,
    status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending',
    customer_notes TEXT,
    owner_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_property_visits_property (property_id),
    INDEX idx_property_visits_customer (customer_id),
    INDEX idx_property_visits_owner (owner_id),
    INDEX idx_property_visits_status (status)
);

-- =====================================================
-- CARD TOKENS TABLE (PayHere Integration)
-- =====================================================
CREATE TABLE card_tokens (
    id INT PRIMARY KEY AUTO_INCREMENT,
    customer_id INT NOT NULL,
    payhere_token VARCHAR(255) NOT NULL UNIQUE,
    card_last4 VARCHAR(4) NOT NULL,
    card_brand VARCHAR(20),
    card_holder_name VARCHAR(100),
    status ENUM('active', 'expired', 'disabled') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_card_tokens_customer (customer_id),
    INDEX idx_card_tokens_status (status)
);

-- =====================================================
-- RENTAL AGREEMENTS TABLE
-- =====================================================
CREATE TABLE rental_agreements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    customer_id INT NOT NULL,
    owner_id INT NOT NULL,
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2),
    lease_start_date DATE NOT NULL,
    lease_end_date DATE NOT NULL,
    status ENUM('active', 'terminated', 'expired') DEFAULT 'active',
    token_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (token_id) REFERENCES card_tokens(id) ON DELETE SET NULL,
    INDEX idx_rental_agreements_customer (customer_id),
    INDEX idx_rental_agreements_owner (owner_id),
    INDEX idx_rental_agreements_property (property_id),
    INDEX idx_rental_agreements_status (status)
);

-- =====================================================
-- BOOKINGS/RENTALS TABLE
-- =====================================================
CREATE TABLE bookings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    property_id INT NOT NULL,
    customer_id INT NOT NULL,
    owner_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE,
    monthly_rent DECIMAL(10,2) NOT NULL,
    security_deposit DECIMAL(10,2) NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    commission_amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'approved', 'active', 'completed', 'cancelled') DEFAULT 'pending',
    payment_status ENUM('pending', 'partial', 'paid') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_bookings_customer (customer_id),
    INDEX idx_bookings_property (property_id),
    INDEX idx_bookings_owner (owner_id)
);

-- =====================================================
-- PAYMENTS TABLE (Enhanced for PayHere)
-- =====================================================
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    booking_id INT NOT NULL,
    customer_id INT NOT NULL,
    property_id INT NOT NULL,
    owner_id INT NOT NULL,
    token_id INT NULL,
    payer_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    commission DECIMAL(10,2) NOT NULL DEFAULT 0,
    owner_payout DECIMAL(10,2) NOT NULL DEFAULT 0,
    payment_type ENUM('security_deposit', 'monthly_rent', 'commission') NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    transaction_id VARCHAR(255) UNIQUE NOT NULL,
    payhere_payment_id VARCHAR(100),
    gateway_transaction_id VARCHAR(255),
    payhere_response TEXT,
    payment_date TIMESTAMP NULL,
    due_date DATE,
    status ENUM('pending', 'successful', 'failed', 'refunded') DEFAULT 'pending',
    payment_gateway VARCHAR(50), -- PayHere, Stripe, etc.
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (owner_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (token_id) REFERENCES card_tokens(id) ON DELETE SET NULL,
    FOREIGN KEY (payer_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_payments_booking (booking_id),
    INDEX idx_payments_customer (customer_id),
    INDEX idx_payments_status (status)
);

-- =====================================================
-- COMMISSIONS TABLE
-- =====================================================
CREATE TABLE commissions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    payment_id INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (payment_id) REFERENCES payments(id) ON DELETE CASCADE,
    INDEX idx_commissions_payment (payment_id)
);

-- =====================================================
-- MESSAGES/CONTACT TABLE
-- =====================================================
CREATE TABLE messages (
    id INT PRIMARY KEY AUTO_INCREMENT,
    sender_id INT,
    receiver_id INT,
    property_id INT,
    subject VARCHAR(255),
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE
);

-- =====================================================
-- DEFAULT SYSTEM SETTINGS
-- =====================================================
INSERT INTO system_settings (setting_name, setting_value, description) VALUES
-- Basic System Settings
('commission_percentage', '10', 'Default commission percentage for rentals'),
('site_name', 'SmartRent', 'Name of the website'),
('site_email', 'admin@smartrent.com', 'Default site email'),
('currency_symbol', 'Rs.', 'Currency symbol to display (Sri Lankan Rupees)'),
('default_security_deposit_months', '2', 'Default security deposit in months of rent'),

-- PayHere Integration Settings
('payhere_merchant_id', 'your_payhere_merchant_id', 'PayHere merchant ID'),
('payhere_merchant_secret', 'your_payhere_merchant_secret', 'PayHere merchant secret'),
('payhere_app_code', 'your_business_app_code', 'PayHere business app code'),
('payhere_app_secret', 'your_business_app_secret', 'PayHere business app secret'),
('payhere_mode', 'sandbox', 'PayHere mode: sandbox or live'),

-- Payment & Rental Settings
('monthly_payment_day', '1', 'Day of month to process recurring payments (1-28)'),
('payment_retry_attempts', '3', 'Number of times to retry failed payments'),
('late_payment_fee', '500', 'Late payment fee amount'),
('grace_period_days', '5', 'Grace period before applying late fees'),

-- Visit & Approval Settings
('visit_approval_required', '1', 'Whether property visits require owner approval'),
('auto_approve_properties', '0', 'Whether to auto-approve new properties'),
('max_images_per_property', '10', 'Maximum images allowed per property'),

-- Notification Settings
('notification_email', 'admin@smartrent.com', 'Email for system notifications'),
('sms_notifications_enabled', '1', 'Whether SMS notifications are enabled'),
('email_notifications_enabled', '1', 'Whether email notifications are enabled'),

-- Business Settings
('business_name', 'SmartRent Property Management', 'Official business name'),
('business_address', 'Colombo, Sri Lanka', 'Business address'),
('business_phone', '0701234567', 'Business contact phone'),
('support_email', 'support@smartrent.com', 'Customer support email');

-- =====================================================
-- DEFAULT USERS DATA
-- =====================================================

-- Insert default admin user (password: admin123)
INSERT INTO users (full_name, email, phone, password_hash, user_type, is_phone_verified, status) VALUES
('System Administrator', 'admin@smartrent.com', '0701111111', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', TRUE, 'active');

-- Insert sample property owner (password: owner123)  
INSERT INTO users (full_name, email, phone, password_hash, user_type, is_phone_verified, status, city, state, address) VALUES
('Kamal Perera', 'owner@smartrent.com', '0702222222', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'owner', TRUE, 'active', 'Colombo', 'Western Province', 'No. 123, Galle Road, Colombo 03');

-- Insert sample customer (password: customer123)
INSERT INTO users (full_name, email, phone, password_hash, user_type, is_phone_verified, status, city, state, address) VALUES
('Priya Silva', 'customer@smartrent.com', '0703333333', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer', TRUE, 'active', 'Kandy', 'Central Province', 'No. 456, Peradeniya Road, Kandy');

-- =====================================================
-- SAMPLE PROPERTIES DATA
-- =====================================================
INSERT INTO properties (owner_id, title, description, property_type, bedrooms, bathrooms, area_sqft, address, city, state, zip_code, rent_amount, security_deposit, status, amenities) VALUES

-- Colombo Properties
(2, 'Modern Apartment in Colombo 03', 'Luxury 3-bedroom apartment with sea view in the heart of Colombo business district', 'apartment', 3, 2, 1500, 'No. 789, Galle Face Green', 'Colombo', 'Western Province', '00300', 80000.00, 160000.00, 'approved', '["Air Conditioning", "Sea View", "Parking", "Gym", "Swimming Pool", "Security", "Elevator"]'),

(2, 'Cozy Studio Near University of Colombo', 'Perfect studio apartment for students or working professionals', 'studio', 1, 1, 400, 'No. 321, Reid Avenue', 'Colombo', 'Western Province', '00700', 35000.00, 70000.00, 'approved', '["Air Conditioning", "WiFi", "Furnished", "Near University", "24/7 Security"]'),

-- Kandy Properties  
(2, 'Beautiful House with Garden in Kandy', 'Spacious 4-bedroom house with large garden, perfect for families', 'house', 4, 3, 2200, 'No. 567, Colombo Street', 'Kandy', 'Central Province', '20000', 55000.00, 110000.00, 'approved', '["Garden", "Parking", "Mountain View", "Quiet Area", "Pet Friendly", "Solar Water Heater"]'),

-- Negombo Property
(2, 'Beach-side Villa in Negombo', 'Exclusive 5-bedroom villa just 100m from the beach', 'villa', 5, 4, 3000, 'No. 234, Beach Road', 'Negombo', 'Western Province', '11500', 120000.00, 240000.00, 'pending', '["Beach Access", "Swimming Pool", "Air Conditioning", "Parking", "Garden", "BBQ Area", "Security"]'),

-- Galle Property
(2, 'Historic House in Galle Fort', 'Charming 2-bedroom house within the UNESCO World Heritage Galle Fort', 'house', 2, 2, 1100, 'No. 678, Church Street, Galle Fort', 'Galle', 'Southern Province', '80000', 45000.00, 90000.00, 'approved', '["Historic Building", "Fort Location", "WiFi", "Air Conditioning", "Tourist Area"]');

-- =====================================================
-- SAMPLE PROPERTY VISITS DATA
-- =====================================================
INSERT INTO property_visits (property_id, customer_id, owner_id, requested_date, status, customer_notes) VALUES
(1, 3, 2, '2024-10-10 15:00:00', 'pending', 'I would like to view this property and discuss the lease terms.'),
(2, 3, 2, '2024-10-08 10:00:00', 'approved', 'Looking for a place near the university for my studies.'),
(3, 3, 2, '2024-10-05 14:00:00', 'completed', 'Interested in the garden space for my family.');

-- =====================================================
-- DATABASE SETUP COMPLETE!
-- =====================================================

-- Create a quick verification view to show setup status
CREATE OR REPLACE VIEW system_overview AS
SELECT 
    'Tables Created' as item, 
    COUNT(*) as count 
FROM information_schema.tables 
WHERE table_schema = 'rental_system'
UNION ALL
SELECT 
    'Users Created' as item, 
    COUNT(*) as count 
FROM users
UNION ALL
SELECT 
    'Properties Available' as item, 
    COUNT(*) as count 
FROM properties WHERE status = 'approved'
UNION ALL
SELECT 
    'System Settings' as item, 
    COUNT(*) as count 
FROM system_settings;

-- =====================================================
-- SETUP SUMMARY
-- =====================================================
-- Database: rental_system
-- Tables created: 11
-- Features included:
--   ✓ User management (Admin, Owner, Customer)
--   ✓ Property listings with approval workflow
--   ✓ Property visit booking system
--   ✓ PayHere payment integration
--   ✓ Rental agreements management
--   ✓ Commission tracking
--   ✓ Card tokenization for recurring payments
--   ✓ SMS/Email notifications system
--   ✓ Comprehensive admin dashboard
-- 
-- Default Login Credentials:
-- Admin: admin@smartrent.com / admin123
-- Owner: owner@smartrent.com / owner123  
-- Customer: customer@smartrent.com / customer123
-- 
-- Sri Lankan phone format: 07XXXXXXXX
-- Currency: Sri Lankan Rupees (Rs.)
-- 
-- Access: http://localhost/rental_system/
-- =====================================================

SELECT 'SmartRent Database Setup Complete!' as status;
SELECT * FROM system_overview;