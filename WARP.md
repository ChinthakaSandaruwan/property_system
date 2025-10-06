# WARP.md

This file provides guidance to WARP (warp.dev) when working with code in this repository.

## Development Commands

### Database Setup
```bash
# Import the complete database schema
mysql -u root -p rental_system < smartrent_database_complete.sql

# Create database manually if needed
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS rental_system; USE rental_system; SOURCE smartrent_database_complete.sql;"
```

### Local Development (XAMPP)
```bash
# Start XAMPP services
sudo /opt/lampp/lampp start

# Access the application
# URL: http://localhost/rental_system/

# Default login credentials:
# Admin: admin@smartrent.com / admin123
# Owner: owner@smartrent.com / owner123
# Customer: customer@smartrent.com / customer123
```

### Testing
```bash
# Manual testing endpoints
curl -X POST http://localhost/rental_system/login.php -d "phone=0701111111&step=phone"

# Test database connection
php includes/debug_db.php

# Test property search
php admin/debug_search.php
```

### CRON Jobs (Production)
```bash
# Monthly payment processing (1st of each month at 9 AM)
0 9 1 * * /usr/bin/php /path/to/rental_system/cron/monthly_payments.php
```

## Architecture Overview

This is a multi-tenant property rental system built with PHP/MySQL, featuring three distinct user roles (Admin, Property Owner, Customer) with a complete rental workflow from property listing to recurring payments.

### Core Architecture Patterns

**Three-Tier Architecture:**
- **Presentation Layer:** Role-based dashboards (`admin/`, `owner/`, `customer/` directories)
- **Business Logic:** Centralized in `includes/functions.php` with specialized classes (`includes/payhere.php`)
- **Data Layer:** MySQL with comprehensive schema including payment processing, property management, and user lifecycle

**Authentication Flow:**
- OTP-based phone authentication (Sri Lankan format: 07XXXXXXXX)
- Session management with role-based access control
- Development fallback OTPs (123456, 000000) for testing

**Payment Architecture:**
- PayHere integration with card tokenization for recurring payments
- Commission-based revenue model (configurable via `COMMISSION_PERCENTAGE`)
- Automated monthly payment processing via CRON jobs
- Comprehensive payment status tracking and IPN handling

### Key Database Relationships

**Central Entity:** `properties` table links to:
- `users` (owners and customers)
- `property_visits` (booking system)
- `rental_agreements` (active leases)
- `payments` (recurring rent collection)
- `card_tokens` (stored payment methods)

**User Workflow States:**
- Owner: `pending` → `approved` → `active` (admin approval required)
- Properties: `pending` → `approved` → `rented`
- Payments: `pending` → `successful` → commission distribution

### File Structure Logic

**Role-Based Organization:**
```
├── admin/          # Admin dashboard and management
│   ├── api/        # Admin-specific API endpoints
│   └── dashboard.php
├── owner/          # Property owner interface
│   ├── api/        # Owner-specific API endpoints
│   └── dashboard.php
├── customer/       # Customer rental interface
│   ├── content/    # Dashboard content modules
│   └── dashboard.php
├── includes/       # Shared business logic
│   ├── config.php     # Database and system configuration
│   ├── functions.php  # Core utility functions
│   └── payhere.php    # Payment processing class
```

## Development Guidelines

### Phone Number Validation
Always use the Sri Lankan format validation:
```php
validate_phone($phone) // Expects: 07XXXXXXXX (10 digits)
format_phone($phone)   // Displays as: 077 123 4567
```

### Database Configuration
Configuration constants in `includes/config.php`:
- Database: `rental_system`
- PayHere sandbox mode for development
- Commission percentage: 10% (configurable)
- Upload path: `uploads/` for property images

### OTP Development Flow
- Development OTPs automatically logged to session
- Fallback codes: `123456`, `000000`
- Production: Integrate with SMS service provider

### Property Image Handling
- JSON array storage in database (`images` field)
- Fallback to `images/placeholder.svg` for missing images
- Upload validation via `upload_file()` function

### Payment Integration
- PayHere tokenization for recurring payments
- IPN handling in `customer/payment_notify.php`
- Commission calculation and owner payout automation
- Monthly payment processing via CRON

### Security Considerations
- All user inputs sanitized via `sanitize_input()`
- SQL injection prevention with prepared statements
- XSS prevention with `htmlspecialchars()` output encoding
- Role-based access control via `require_auth()` function

## Environment-Specific Notes

### XAMPP Development Setup
- Database server: localhost:3306
- PHP version compatibility: 7.4+
- Enable PDO MySQL extension
- Set proper file permissions for uploads directory

### Production Deployment
- Configure real PayHere credentials in `config.php`
- Set up SSL certificate for payment processing
- Configure CRON job for monthly payments
- Update SMS service integration
- Set proper file upload limits

### Testing Accounts
Use provided default accounts for development:
- Phone format must match Sri Lankan standard
- Email domains: `@smartrent.com`
- Password format: `{role}123`

This system implements a complete property rental workflow with integrated payments, making it suitable for production deployment with proper configuration of external services (SMS, PayHere, email).