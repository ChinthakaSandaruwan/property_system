<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'includes/functions.php';

// Redirect admin and owner users to their dashboards, but let customers stay on homepage
if (is_logged_in()) {
    switch ($_SESSION['user_type']) {
        case 'admin':
            header('Location: admin/dashboard.php');
            exit();
        case 'owner':
            header('Location: owner/dashboard.php');
            exit();
        // customers stay on homepage and can access dashboard manually
    }
}

// Get some statistics for the homepage
$stats = [
    'total_properties' => $pdo->query("SELECT COUNT(*) FROM properties WHERE status = 'approved'")->fetchColumn(),
    'total_owners' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'owner'")->fetchColumn(),
    'total_customers' => $pdo->query("SELECT COUNT(*) FROM users WHERE user_type = 'customer'")->fetchColumn(),
];

// Get featured properties (latest approved properties)
$featured_properties = $pdo->query("
    SELECT p.*, u.full_name as owner_name 
    FROM properties p 
    JOIN users u ON p.owner_id = u.id 
    WHERE p.status = 'approved' 
    ORDER BY p.created_at DESC 
    LIMIT 6
")->fetchAll();

// Add sample testimonials
$testimonials = [
    [
        'name' => 'Sarah Johnson',
        'location' => 'Colombo',
        'rating' => 5,
        'text' => 'Amazing platform! Found my dream apartment in just 2 days. The verification process gave me confidence in the properties.'
    ],
    [
        'name' => 'Raj Patel', 
        'location' => 'Kandy',
        'rating' => 5,
        'text' => 'As a property owner, this platform has made rental management so easy. Reliable tenants and secure payments.'
    ],
    [
        'name' => 'Amara Silva',
        'location' => 'Galle', 
        'rating' => 4,
        'text' => 'Great experience overall. The property visits were well organized and the staff was very helpful throughout.'
    ]
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Property Rental System - Find Your Perfect Home</title>
    
    <!-- External CSS Libraries -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #38a169;
            --primary-dark: #2d7d32;
            --secondary-color: #2f855a;
            --accent-color: #68d391;
            --success-color: #38a169;
            --warning-color: #f59e0b;
            --error-color: #ef4444;
            --dark-color: #1f2937;
            --light-color: #f8fafc;
            --white: #ffffff;
            --gray-100: #f3f4f6;
            --gray-200: #e5e7eb;
            --gray-300: #d1d5db;
            --gray-400: #9ca3af;
            --gray-500: #6b7280;
            --gray-600: #4b5563;
            --gray-700: #374151;
            --gray-800: #1f2937;
            --gray-900: #111827;
            
            --gradient-primary: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            --gradient-accent: linear-gradient(135deg, var(--accent-color) 0%, var(--primary-color) 100%);
            
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --shadow-xl: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-2xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            
            --border-radius: 8px;
            --border-radius-lg: 16px;
            --border-radius-xl: 24px;
            
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            --transition-fast: all 0.15s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            scroll-behavior: smooth;
            font-size: 16px;
        }
        
        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
            line-height: 1.6;
            color: var(--dark-color);
            overflow-x: hidden;
            background-color: var(--white);
        }
        
        /* Container */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        /* Preloader */
        .preloader {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: var(--white);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            transition: opacity 0.5s ease;
        }
        
        .preloader.hidden {
            opacity: 0;
            pointer-events: none;
        }
        
        .loader {
            text-align: center;
        }
        
        .loader-ring {
            width: 60px;
            height: 60px;
            border: 3px solid var(--gray-200);
            border-top: 3px solid var(--primary-color);
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        
        .loader-text {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--primary-color);
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Navigation */
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            z-index: 1000;
            transition: var(--transition);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.98);
            box-shadow: var(--shadow-lg);
        }
        
        .nav-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            min-height: 80px;
        }
        
        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
            text-decoration: none;
        }
        
        .logo i {
            font-size: 1.75rem;
            background: var(--gradient-primary);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .nav-links {
            display: flex;
            list-style: none;
            align-items: center;
            gap: 2rem;
        }
        
        .nav-link {
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            font-size: 0.95rem;
            transition: var(--transition);
            position: relative;
            padding: 0.5rem 0;
        }
        
        .nav-link:hover,
        .nav-link.active {
            color: var(--primary-color);
        }
        
        .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 2px;
            background: var(--gradient-primary);
            border-radius: 1px;
        }
        
        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.5rem;
            border-radius: var(--border-radius);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.95rem;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
            line-height: 1.2;
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1rem;
        }
        
        .btn-primary {
            background: var(--gradient-primary);
            color: var(--white);
            box-shadow: var(--shadow-md);
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-xl);
        }
        
        .btn-outline {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }
        
        .btn-outline:hover {
            background: var(--white);
            color: var(--primary-color);
            transform: translateY(-2px);
        }
        
        .btn-outline-secondary {
            background: transparent;
            color: rgba(255, 255, 255, 0.8);
            border: 2px solid rgba(255, 255, 255, 0.5);
        }
        
        .btn-outline-secondary:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: var(--white);
            color: var(--white);
        }
        
        .btn-white {
            background: var(--white);
            color: var(--primary-color);
        }
        
        .btn-white:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
        }
        
        .btn-outline-white {
            background: transparent;
            color: var(--white);
            border: 2px solid var(--white);
        }
        
        .btn-outline-white:hover {
            background: var(--white);
            color: var(--primary-color);
        }
        
        .nav-btn {
            padding: 0.625rem 1.25rem;
            font-size: 0.875rem;
        }
        
        /* Mobile Menu */
        .mobile-menu {
            display: none;
            flex-direction: column;
            cursor: pointer;
            gap: 4px;
        }
        
        .mobile-menu span {
            width: 25px;
            height: 3px;
            background: var(--gray-700);
            border-radius: 2px;
            transition: var(--transition);
        }
        
        .mobile-menu.active span:nth-child(1) {
            transform: rotate(-45deg) translate(-5px, 6px);
        }
        
        .mobile-menu.active span:nth-child(2) {
            opacity: 0;
        }
        
        .mobile-menu.active span:nth-child(3) {
            transform: rotate(45deg) translate(-5px, -6px);
        }
        
        .mobile-nav {
            position: fixed;
            top: 80px;
            left: 0;
            width: 100%;
            height: calc(100vh - 80px);
            background: var(--white);
            transform: translateX(-100%);
            transition: var(--transition);
            z-index: 999;
        }
        
        .mobile-nav.active {
            transform: translateX(0);
        }
        
        .mobile-nav-content {
            padding: 2rem 1.5rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .mobile-nav-link {
            text-decoration: none;
            color: var(--gray-700);
            font-weight: 500;
            font-size: 1.1rem;
            padding: 1rem;
            border-radius: var(--border-radius);
            transition: var(--transition);
        }
        
        .mobile-nav-link:hover {
            background: var(--gray-100);
            color: var(--primary-color);
        }
        
        /* Hero Section */
        .hero {
            min-height: 100vh;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            padding: 120px 0 80px;
        }
        
        .hero-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            overflow: hidden;
        }
        
        .hero-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle fill="%23ffffff08" cx="10" cy="10" r="1"/><circle fill="%23ffffff05" cx="30" cy="25" r="1.5"/><circle fill="%23ffffff08" cx="60" cy="15" r="1"/><circle fill="%23ffffff05" cx="80" cy="35" r="1.5"/><circle fill="%23ffffff08" cx="90" cy="5" r="1"/></svg>');
            animation: float 20s linear infinite;
        }
        
        .hero-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }
        
        .shape {
            position: absolute;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.1);
            animation: float 6s ease-in-out infinite;
        }
        
        .shape-1 {
            width: 200px;
            height: 200px;
            top: 20%;
            left: 10%;
            animation-delay: -2s;
        }
        
        .shape-2 {
            width: 150px;
            height: 150px;
            top: 60%;
            right: 10%;
            animation-delay: -4s;
        }
        
        .shape-3 {
            width: 100px;
            height: 100px;
            bottom: 20%;
            left: 50%;
            animation-delay: -1s;
        }
        
        @keyframes float {
            0%, 100% { 
                transform: translateY(0px) rotate(0deg);
            }
            50% { 
                transform: translateY(-20px) rotate(180deg);
            }
        }
        
        .hero-content {
            text-align: center;
            color: var(--white);
            z-index: 2;
            position: relative;
            max-width: 900px;
            margin: 0 auto;
        }
        
        .hero-welcome .welcome-icon {
            font-size: 4rem;
            margin-bottom: 1rem;
            opacity: 0.9;
        }
        
        .hero-title {
            font-size: clamp(2.5rem, 5vw, 4.5rem);
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
        }
        
        .highlight {
            background: linear-gradient(135deg, #f093fb 0%, #f5f7fa 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .hero-subtitle {
            font-size: 1.25rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }
        
        .hero-features {
            display: flex;
            justify-content: center;
            gap: 2rem;
            margin-bottom: 3rem;
            flex-wrap: wrap;
        }
        
        .feature-badge {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.75rem 1.25rem;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            border-radius: 50px;
            font-size: 0.9rem;
            font-weight: 500;
        }
        
        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        
        .hero-search {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(20px);
            border-radius: var(--border-radius-lg);
            padding: 1.5rem;
            margin-top: 2rem;
        }
        
        .search-form {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr auto;
            gap: 1rem;
            align-items: center;
        }
        
        .search-input {
            position: relative;
            display: flex;
            align-items: center;
        }
        
        .search-input i {
            position: absolute;
            left: 1rem;
            color: var(--gray-500);
        }
        
        .search-input input {
            width: 100%;
            padding: 0.875rem 1rem 0.875rem 2.5rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .search-input input:focus {
            outline: none;
            border-color: var(--white);
            background: var(--white);
        }
        
        .search-filter select {
            width: 100%;
            padding: 0.875rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: var(--border-radius);
            background: rgba(255, 255, 255, 0.9);
            font-size: 1rem;
            transition: var(--transition);
        }
        
        .search-filter select:focus {
            outline: none;
            border-color: var(--white);
            background: var(--white);
        }
        
        .search-btn {
            padding: 0.875rem 1.5rem;
            background: var(--white);
            color: var(--primary-color);
            border: none;
            border-radius: var(--border-radius);
            font-weight: 600;
            cursor: pointer;
            transition: var(--transition);
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .search-btn:hover {
            background: var(--gray-100);
            transform: translateY(-2px);
        }
        
        .scroll-indicator {
            position: absolute;
            bottom: 2rem;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.875rem;
        }
        
        .scroll-mouse {
            width: 24px;
            height: 40px;
            border: 2px solid rgba(255, 255, 255, 0.5);
            border-radius: 12px;
            position: relative;
        }
        
        .scroll-wheel {
            width: 3px;
            height: 6px;
            background: rgba(255, 255, 255, 0.7);
            border-radius: 2px;
            position: absolute;
            top: 6px;
            left: 50%;
            transform: translateX(-50%);
            animation: scroll 2s infinite;
        }
        
        @keyframes scroll {
            0% { transform: translateX(-50%) translateY(0); opacity: 1; }
            100% { transform: translateX(-50%) translateY(16px); opacity: 0; }
        }
        
        /* Stats Section */
        .stats {
            padding: 5rem 0;
            background: var(--light-color);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }
        
        .stat-card {
            background: var(--white);
            padding: 2.5rem 1.5rem;
            border-radius: var(--border-radius-lg);
            text-align: center;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
        }
        
        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: var(--gradient-primary);
        }
        
        .stat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-2xl);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            color: var(--white);
            font-size: 1.5rem;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: 800;
            color: var(--primary-color);
            margin-bottom: 0.5rem;
            line-height: 1;
        }
        
        .stat-label {
            font-size: 1.1rem;
            color: var(--gray-700);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .stat-description {
            font-size: 0.875rem;
            color: var(--gray-500);
        }
        
        /* Section Headers */
        .section-header {
            text-align: center;
            margin-bottom: 4rem;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
        }
        
        .section-badge {
            display: inline-block;
            padding: 0.5rem 1rem;
            background: var(--gradient-primary);
            color: var(--white);
            border-radius: 50px;
            font-size: 0.875rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        
        .section-title {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 700;
            margin-bottom: 1rem;
            color: var(--dark-color);
            line-height: 1.2;
        }
        
        .section-subtitle {
            font-size: 1.125rem;
            color: var(--gray-600);
            line-height: 1.6;
        }
        
        .section-footer {
            text-align: center;
            margin-top: 3rem;
        }
        
        /* Features Section */
        .features {
            padding: 5rem 0;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
        }
        
        .feature-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            border: 1px solid var(--gray-200);
            position: relative;
            overflow: hidden;
        }
        
        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--gradient-primary);
            transform: translateX(-100%);
            transition: var(--transition);
        }
        
        .feature-card:hover::before {
            transform: translateX(0);
        }
        
        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-2xl);
        }
        
        .feature-header {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1rem;
        }
        
        .feature-icon {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-size: 1.25rem;
            flex-shrink: 0;
        }
        
        .feature-card h3 {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
        }
        
        .feature-card p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .feature-benefits {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .benefit {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .benefit i {
            color: var(--success-color);
            font-size: 0.75rem;
        }
        
        /* Properties Section */
        .properties {
            padding: 5rem 0;
            background: var(--light-color);
        }
        
        .properties-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .property-card {
            background: var(--white);
            border-radius: var(--border-radius-lg);
            overflow: hidden;
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            display: flex;
            flex-direction: column;
            height: 100%;
        }
        
        .property-card:hover {
            transform: translateY(-8px);
            box-shadow: var(--shadow-2xl);
        }
        
        .property-image-container {
            position: relative;
            overflow: hidden;
        }
        
        .property-image {
            width: 100%;
            height: 240px;
            object-fit: cover;
            transition: var(--transition);
        }
        
        .property-card:hover .property-image {
            transform: scale(1.05);
        }
        
        .property-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: var(--transition);
        }
        
        .property-card:hover .property-overlay {
            opacity: 1;
        }
        
        .property-actions {
            display: flex;
            gap: 1rem;
        }
        
        .action-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--white);
            border: none;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: var(--transition);
            color: var(--gray-600);
        }
        
        .action-btn:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: scale(1.1);
        }
        
        .property-badge {
            position: absolute;
            top: 1rem;
            left: 1rem;
            background: var(--gradient-primary);
            color: var(--white);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }
        
        .property-content {
            padding: 1.5rem;
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }
        
        .property-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            gap: 1rem;
        }
        
        .property-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--dark-color);
            margin: 0;
            line-height: 1.3;
            overflow: hidden;
            display: -webkit-box;
            -webkit-box-orient: vertical;
            -webkit-line-clamp: 2; 
        }
        
        .property-price {
            display: flex;
            align-items: baseline;
            gap: 0.25rem;
            flex-shrink: 0;
        }
        
        .property-price .currency {
            font-size: 0.875rem;
            font-weight: 500;
            color: var(--gray-600);
        }
        
        .property-price .amount {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .property-price .period {
            font-size: 0.75rem;
            font-weight: 400;
            color: var(--gray-500);
        }
        
        .property-location {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .property-location i {
            color: var(--error-color);
        }
        
        .property-details {
            display: flex;
            gap: 1.5rem;
            padding: 0.75rem 0;
            border-top: 1px solid var(--gray-200);
            border-bottom: 1px solid var(--gray-200);
        }
        
        .detail-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .detail-item i {
            color: var(--primary-color);
            width: 16px;
            text-align: center;
        }
        
        .property-footer {
            margin-top: auto;
        }
        
        .property-btn {
            width: 100%;
            justify-content: center;
            padding: 0.875rem 1.5rem;
            font-size: 0.95rem;
        }
        
        /* Empty State */
        .empty-state {
            grid-column: 1/-1;
            text-align: center;
            padding: 4rem 2rem;
            background: var(--white);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            max-width: 500px;
            margin: 0 auto;
        }
        
        .empty-state-icon {
            width: 80px;
            height: 80px;
            background: var(--gradient-primary);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            color: var(--white);
            font-size: 2rem;
        }
        
        .empty-state h3 {
            color: var(--dark-color);
            margin-bottom: 1rem;
            font-size: 1.5rem;
            font-weight: 600;
        }
        
        .empty-state p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 2rem;
        }
        
        /* How It Works */
        .how-it-works {
            padding: 5rem 0;
        }
        
        .steps-container {
            position: relative;
        }
        
        .steps-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 2rem;
        }
        
        .step-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            text-align: center;
            position: relative;
            border: 1px solid var(--gray-200);
        }
        
        .step-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-2xl);
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1.5rem;
            color: var(--white);
            font-size: 1.5rem;
            font-weight: 800;
        }
        
        .step-content h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: var(--dark-color);
        }
        
        .step-content p {
            color: var(--gray-600);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .step-features {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .step-feature {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            color: var(--gray-600);
        }
        
        .step-feature i {
            color: var(--success-color);
            font-size: 0.75rem;
        }
        
        .process-flow {
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 2px;
            background: var(--gray-200);
            z-index: -1;
            transform: translateY(-50%);
        }
        
        .flow-line {
            height: 100%;
            background: var(--gradient-primary);
            width: 100%;
            border-radius: 1px;
        }
        
        /* Testimonials */
        .testimonials {
            padding: 5rem 0;
            background: var(--light-color);
        }
        
        .testimonials-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 2rem;
        }
        
        .testimonial-card {
            background: var(--white);
            padding: 2rem;
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-lg);
            transition: var(--transition);
            position: relative;
            border: 1px solid var(--gray-200);
        }
        
        .testimonial-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-2xl);
        }
        
        .testimonial-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        
        .stars {
            color: var(--warning-color);
            font-size: 1rem;
        }
        
        .testimonial-quote {
            color: var(--gray-300);
            font-size: 2rem;
        }
        
        .testimonial-text {
            color: var(--gray-600);
            line-height: 1.7;
            margin-bottom: 1.5rem;
            font-style: italic;
            font-size: 1rem;
        }
        
        .testimonial-author {
            display: flex;
            align-items: center;
            gap: 1rem;
        }
        
        .author-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--white);
            font-weight: 600;
            font-size: 1.125rem;
            flex-shrink: 0;
        }
        
        .author-info h4 {
            color: var(--dark-color);
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        
        .author-info span {
            color: var(--gray-500);
            font-size: 0.875rem;
        }
        
        /* CTA Section */
        .cta {
            padding: 5rem 0;
            background: var(--gradient-primary);
            text-align: center;
            color: var(--white);
            position: relative;
            overflow: hidden;
        }
        
        .cta-background {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
        }
        
        .cta-particles {
            position: absolute;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
            background-size: 50px 50px;
            animation: float 20s linear infinite;
        }
        
        .cta-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
        }
        
        .cta-icon {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
            font-size: 2rem;
        }
        
        .cta h2 {
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .cta p {
            font-size: 1.125rem;
            margin-bottom: 2rem;
            opacity: 0.9;
            line-height: 1.6;
        }
        
        .cta-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-bottom: 3rem;
        }
        
        .cta-trust-signals {
            display: flex;
            justify-content: center;
            gap: 3rem;
            flex-wrap: wrap;
        }
        
        .trust-signal {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.875rem;
            opacity: 0.8;
        }
        
        /* Footer */
        .footer {
            background: var(--gray-900);
            color: var(--white);
            padding: 4rem 0 2rem;
        }
        
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr;
            gap: 3rem;
            margin-bottom: 2rem;
        }
        
        .footer-brand {
            max-width: 350px;
        }
        
        .footer-logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
        }
        
        .footer-logo i {
            font-size: 1.75rem;
            color: var(--primary-color);
        }
        
        .brand-description {
            color: var(--gray-400);
            line-height: 1.6;
            margin-bottom: 1.5rem;
        }
        
        .social-links {
            display: flex;
            gap: 1rem;
        }
        
        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--gray-800);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--gray-400);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .social-link:hover {
            background: var(--primary-color);
            color: var(--white);
            transform: translateY(-2px);
        }
        
        .footer-title {
            color: var(--white);
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 1.125rem;
        }
        
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .footer-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-links a:hover {
            color: var(--primary-color);
        }
        
        .contact-info {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .contact-item i {
            color: var(--primary-color);
            width: 16px;
            text-align: center;
        }
        
        .footer-bottom {
            border-top: 1px solid var(--gray-800);
            padding-top: 2rem;
        }
        
        .footer-bottom-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            color: var(--gray-400);
            font-size: 0.875rem;
        }
        
        .footer-bottom-links {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .footer-bottom-links a {
            color: var(--gray-400);
            text-decoration: none;
            transition: var(--transition);
        }
        
        .footer-bottom-links a:hover {
            color: var(--primary-color);
        }
        
        /* Back to Top */
        .back-to-top {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: var(--gradient-primary);
            color: var(--white);
            border: none;
            cursor: pointer;
            transition: var(--transition);
            opacity: 0;
            transform: translateY(20px);
            z-index: 1000;
            box-shadow: var(--shadow-lg);
        }
        
        .back-to-top.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .back-to-top:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
        }
        
        /* Responsive Design */
        @media (max-width: 768px) {
            .nav-links {
                display: none;
            }
            
            .mobile-menu {
                display: flex;
            }
            
            .hero {
                padding: 100px 0 60px;
            }
            
            .hero-buttons,
            .cta-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn {
                width: 100%;
                max-width: 300px;
            }
            
            .hero-features {
                flex-direction: column;
                gap: 1rem;
            }
            
            .search-form {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .stats-grid,
            .features-grid,
            .properties-grid,
            .steps-grid,
            .testimonials-grid {
                grid-template-columns: 1fr;
                gap: 1.5rem;
            }
            
            .property-details {
                flex-wrap: wrap;
                gap: 1rem;
            }
            
            .footer-grid {
                grid-template-columns: 1fr;
                gap: 2rem;
                text-align: center;
            }
            
            .footer-bottom-content {
                flex-direction: column;
                text-align: center;
            }
            
            .section-header,
            .cta-content {
                padding: 0 1rem;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 0 1rem;
            }
            
            .hero-content {
                padding: 0 0.5rem;
            }
            
            .stat-card,
            .feature-card,
            .property-card,
            .step-card,
            .testimonial-card {
                margin: 0 0.5rem;
            }
            
            .property-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Preloader -->
    <div id="preloader" class="preloader">
        <div class="loader">
            <div class="loader-ring"></div>
            <div class="loader-text">PropertyRental</div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="navbar" id="navbar">
        <div class="container">
            <div class="nav-container">
                <a href="#home" class="logo">
                    <i class="fas fa-home"></i>
                    <span>PropertyRental</span>
                </a>
                <ul class="nav-links">
                    <li><a href="#home" class="nav-link active">Home</a></li>
                    <li><a href="#features" class="nav-link">Features</a></li>
                    <li><a href="#properties" class="nav-link">Properties</a></li>
                    <li><a href="#how-it-works" class="nav-link">How It Works</a></li>
                    <li><a href="#testimonials" class="nav-link">Reviews</a></li>
                    <?php if (is_logged_in() && $_SESSION['user_type'] === 'customer'): ?>
                        <li><a href="customer/dashboard.php" class="btn btn-primary nav-btn">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a></li>
                        <li><a href="logout.php" class="nav-link">Sign Out</a></li>
                    <?php else: ?>
                        <li><a href="login.php" class="nav-link">Login</a></li>
                        <li><a href="register.php" class="btn btn-primary nav-btn">
                            <i class="fas fa-user-plus"></i> Register
                        </a></li>
                    <?php endif; ?>
                </ul>
                <div class="mobile-menu" id="mobileMenu">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </div>
        </div>
    </nav>

    <!-- Mobile Navigation Menu -->
    <div class="mobile-nav" id="mobileNav">
        <div class="mobile-nav-content">
            <a href="#home" class="mobile-nav-link">Home</a>
            <a href="#features" class="mobile-nav-link">Features</a>
            <a href="#properties" class="mobile-nav-link">Properties</a>
            <a href="#how-it-works" class="mobile-nav-link">How It Works</a>
            <a href="#testimonials" class="mobile-nav-link">Reviews</a>
            <?php if (is_logged_in() && $_SESSION['user_type'] === 'customer'): ?>
                <a href="customer/dashboard.php" class="mobile-nav-link">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
                <a href="logout.php" class="mobile-nav-link">Sign Out</a>
            <?php else: ?>
                <a href="login.php" class="mobile-nav-link">Login</a>
                <a href="register.php" class="mobile-nav-link btn-primary">Register</a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hero Section -->
    <section class="hero" id="home">
        <div class="hero-background">
            <div class="hero-particles"></div>
            <div class="hero-shapes">
                <div class="shape shape-1"></div>
                <div class="shape shape-2"></div>
                <div class="shape shape-3"></div>
            </div>
        </div>
        
        <div class="container">
            <div class="hero-content" data-aos="fade-up" data-aos-duration="1000">
                <?php if (is_logged_in() && $_SESSION['user_type'] === 'customer'): ?>
                    <div class="hero-welcome">
                        <div class="welcome-icon">
                            <i class="fas fa-user-circle"></i>
                        </div>
                        <h1>Welcome Back, <span class="highlight"><?= htmlspecialchars($_SESSION['user_name']) ?></span>! 🎉</h1>
                        <p class="hero-subtitle">Ready to continue your rental journey? Access your dashboard to manage bookings, view your wishlist, and discover new properties.</p>
                        <div class="hero-buttons">
                            <a href="customer/dashboard.php" class="btn btn-outline btn-lg">
                                <i class="fas fa-tachometer-alt"></i> 
                                <span>Go to Dashboard</span>
                            </a>
                            <a href="logout.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-sign-out-alt"></i> 
                                <span>Sign Out</span>
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="hero-main">
                        <h1 class="hero-title">Find Your <span class="highlight">Perfect</span> Rental Home</h1>
                        <p class="hero-subtitle">Discover amazing rental properties with our trusted platform. Safe, secure, and seamless rental experience for everyone.</p>
                        
                        <div class="hero-features">
                            <div class="feature-badge" data-aos="fade-up" data-aos-delay="200">
                                <i class="fas fa-shield-check"></i>
                                <span>Verified Properties</span>
                            </div>
                            <div class="feature-badge" data-aos="fade-up" data-aos-delay="300">
                                <i class="fas fa-lock"></i>
                                <span>Secure Payments</span>
                            </div>
                            <div class="feature-badge" data-aos="fade-up" data-aos-delay="400">
                                <i class="fas fa-clock"></i>
                                <span>24/7 Support</span>
                            </div>
                        </div>
                        
                        <div class="hero-buttons" data-aos="fade-up" data-aos-delay="500">
                            <a href="register.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-rocket"></i> 
                                <span>Get Started Today</span>
                            </a>
                            <a href="login.php" class="btn btn-outline btn-lg">
                                <i class="fas fa-sign-in-alt"></i> 
                                <span>Sign In</span>
                            </a>
                        </div>
                        
                        <!-- Search Bar -->
                        <div class="hero-search" data-aos="fade-up" data-aos-delay="600">
                            <form class="search-form" action="properties.php" method="GET">
                                <div class="search-input">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <input type="text" name="location" placeholder="Enter city or location...">
                                </div>
                                <div class="search-filter">
                                    <select name="property_type">
                                        <option value="">Property Type</option>
                                        <option value="apartment">Apartment</option>
                                        <option value="house">House</option>
                                        <option value="condo">Condo</option>
                                        <option value="studio">Studio</option>
                                    </select>
                                </div>
                                <div class="search-filter">
                                    <select name="budget">
                                        <option value="">Budget Range</option>
                                        <option value="0-25000">Under Rs. 25,000</option>
                                        <option value="25000-50000">Rs. 25,000 - 50,000</option>
                                        <option value="50000-100000">Rs. 50,000 - 100,000</option>
                                        <option value="100000+">Above Rs. 100,000</option>
                                    </select>
                                </div>
                                <button type="submit" class="search-btn">
                                    <i class="fas fa-search"></i>
                                    <span>Search</span>
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Scroll Indicator -->
        <div class="scroll-indicator" data-aos="fade-up" data-aos-delay="1000">
            <div class="scroll-mouse">
                <div class="scroll-wheel"></div>
            </div>
            <span>Scroll to explore</span>
        </div>
    </section>

    <!-- Statistics Section -->
    <section class="stats" id="stats">
        <div class="container">
            <div class="stats-grid">
                <div class="stat-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="stat-icon">
                        <i class="fas fa-home"></i>
                    </div>
                    <div class="stat-number" data-target="<?= $stats['total_properties'] ?>">0</div>
                    <div class="stat-label">Properties Available</div>
                    <div class="stat-description">Verified and ready to rent</div>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="stat-icon">
                        <i class="fas fa-user-tie"></i>
                    </div>
                    <div class="stat-number" data-target="<?= $stats['total_owners'] ?>">0</div>
                    <div class="stat-label">Trusted Owners</div>
                    <div class="stat-description">Verified property owners</div>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number" data-target="<?= $stats['total_customers'] ?>">0</div>
                    <div class="stat-label">Happy Customers</div>
                    <div class="stat-description">Satisfied renters</div>
                </div>
                <div class="stat-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="stat-icon">
                        <i class="fas fa-star"></i>
                    </div>
                    <div class="stat-number" data-target="98">0</div>
                    <div class="stat-label">Success Rate</div>
                    <div class="stat-description">Customer satisfaction</div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features" id="features">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Why Choose Us</div>
                <h2 class="section-title">Why Choose Our <span class="highlight">Platform?</span></h2>
                <p class="section-subtitle">We provide a secure, efficient, and user-friendly platform for all your rental needs with cutting-edge technology.</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card" data-aos="fade-up" data-aos-delay="0">
                    <div class="feature-header">
                        <div class="feature-icon">
                            <i class="fas fa-shield-check"></i>
                        </div>
                        <h3>Verified Properties</h3>
                    </div>
                    <p>Every property is thoroughly verified by our expert team to ensure quality, authenticity, and safety for our users.</p>
                    <div class="feature-benefits">
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Background verification</span>
                        </div>
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Legal documentation check</span>
                        </div>
                    </div>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="100">
                    <div class="feature-header">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h3>Secure Payments</h3>
                    </div>
                    <p>Bank-grade security with encrypted payment processing. Guaranteed safe transactions for both tenants and landlords.</p>
                    <div class="feature-benefits">
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>SSL encrypted transactions</span>
                        </div>
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Multiple payment options</span>
                        </div>
                    </div>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="200">
                    <div class="feature-header">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h3>Easy Booking</h3>
                    </div>
                    <p>Intuitive booking system with instant confirmation. Schedule property visits and complete rentals in just a few clicks.</p>
                    <div class="feature-benefits">
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Instant booking confirmation</span>
                        </div>
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Flexible scheduling</span>
                        </div>
                    </div>
                </div>
                
                <div class="feature-card" data-aos="fade-up" data-aos-delay="300">
                    <div class="feature-header">
                        <div class="feature-icon">
                            <i class="fas fa-brain"></i>
                        </div>
                        <h3>Smart Matching</h3>
                    </div>
                    <p>AI-powered search with advanced filters to find properties that perfectly match your preferences and budget.</p>
                    <div class="feature-benefits">
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Intelligent recommendations</span>
                        </div>
                        <div class="benefit">
                            <i class="fas fa-check"></i>
                            <span>Advanced filtering options</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Featured Properties -->
    <section class="properties" id="properties">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Featured Properties</div>
                <h2 class="section-title">Discover Our <span class="highlight">Featured</span> Properties</h2>
                <p class="section-subtitle">Explore our most popular and carefully selected rental properties in prime locations.</p>
            </div>
            
            <div class="properties-grid">
                <?php if (!empty($featured_properties)): ?>
                    <?php foreach (array_slice($featured_properties, 0, 6) as $index => $property): ?>
                        <div class="property-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                            <div class="property-image-container">
                                <?php 
                                $images = json_decode($property['images'] ?? '[]', true);
                                $first_image = !empty($images) ? $images[0] : 'placeholder.jpg';
                                ?>
                                <img src="uploads/properties/<?= htmlspecialchars($first_image) ?>" 
                                     alt="<?= htmlspecialchars($property['title']) ?>" 
                                     class="property-image" 
                                     loading="lazy"
                                     onerror="this.src='images/placeholder.svg'">
                                
                                <div class="property-overlay">
                                    <div class="property-actions">
                                        <button class="action-btn favorite-btn" title="Add to favorites">
                                            <i class="far fa-heart"></i>
                                        </button>
                                        <button class="action-btn share-btn" title="Share property">
                                            <i class="fas fa-share-alt"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="property-badge">Featured</div>
                            </div>
                            
                            <div class="property-content">
                                <div class="property-header">
                                    <h3 class="property-title"><?= htmlspecialchars($property['title']) ?></h3>
                                    <div class="property-price">
                                        <span class="currency">Rs.</span>
                                        <span class="amount"><?= number_format($property['rent_amount']) ?></span>
                                        <span class="period">/month</span>
                                    </div>
                                </div>
                                
                                <div class="property-location">
                                    <i class="fas fa-map-marker-alt"></i> 
                                    <span><?= htmlspecialchars($property['city'] . ', ' . $property['state']) ?></span>
                                </div>
                                
                                <div class="property-details">
                                    <div class="detail-item">
                                        <i class="fas fa-bed"></i>
                                        <span><?= $property['bedrooms'] ?> BR</span>
                                    </div>
                                    <div class="detail-item">
                                        <i class="fas fa-bath"></i>
                                        <span><?= $property['bathrooms'] ?> BA</span>
                                    </div>
                                    <?php if ($property['area_sqft']): ?>
                                        <div class="detail-item">
                                            <i class="fas fa-expand-arrows-alt"></i>
                                            <span><?= number_format($property['area_sqft']) ?> sq ft</span>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="property-footer">
                                    <a href="property_details.php?id=<?= $property['id'] ?>" class="btn btn-primary property-btn">
                                        <i class="fas fa-eye"></i>
                                        <span>View Details</span>
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="empty-state" data-aos="fade-up">
                        <div class="empty-state-icon">
                            <i class="fas fa-home"></i>
                        </div>
                        <h3>No Properties Available Yet</h3>
                        <p>We're working hard to bring you amazing rental properties. Check back soon for new listings in your area!</p>
                        <a href="register.php" class="btn btn-primary">
                            <i class="fas fa-bell"></i> Get Notified About New Properties
                        </a>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="section-footer" data-aos="fade-up">
                <a href="properties.php" class="btn btn-outline btn-lg">
                    <i class="fas fa-search"></i>
                    <span>View All Properties</span>
                </a>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section class="how-it-works" id="how-it-works">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Process</div>
                <h2 class="section-title">How It <span class="highlight">Works</span></h2>
                <p class="section-subtitle">Get your perfect rental in 4 simple steps. Quick, easy, and completely secure.</p>
            </div>
            
            <div class="steps-container">
                <div class="steps-grid">
                    <div class="step-card" data-aos="fade-up" data-aos-delay="0">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Create Account</h3>
                            <p>Sign up with email verification and phone number validation for enhanced security and personalized experience.</p>
                            <div class="step-features">
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Email verification
                                </span>
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Phone validation
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-card" data-aos="fade-up" data-aos-delay="100">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h3>Search & Filter</h3>
                            <p>Browse verified properties with advanced filters to find exactly what you're looking for in your preferred location.</p>
                            <div class="step-features">
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Advanced filters
                                </span>
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Location-based search
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-card" data-aos="fade-up" data-aos-delay="200">
                        <div class="step-number">3</div>
                        <div class="step-content">
                            <h3>Schedule Visit</h3>
                            <p>Book property visits at your convenient time with our easy scheduling system and get instant confirmation.</p>
                            <div class="step-features">
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Flexible timing
                                </span>
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Instant confirmation
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="step-card" data-aos="fade-up" data-aos-delay="300">
                        <div class="step-number">4</div>
                        <div class="step-content">
                            <h3>Secure Rental</h3>
                            <p>Complete the rental process with secure payments, digital agreements, and full legal documentation.</p>
                            <div class="step-features">
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Secure payments
                                </span>
                                <span class="step-feature">
                                    <i class="fas fa-check"></i>
                                    Digital contracts
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Process Flow -->
                <div class="process-flow" data-aos="fade-up" data-aos-delay="400">
                    <div class="flow-line"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" id="testimonials">
        <div class="container">
            <div class="section-header" data-aos="fade-up">
                <div class="section-badge">Reviews</div>
                <h2 class="section-title">What Our <span class="highlight">Users</span> Say</h2>
                <p class="section-subtitle">Real experiences from real customers who found their perfect homes through our platform.</p>
            </div>
            
            <div class="testimonials-grid">
                <?php foreach ($testimonials as $index => $testimonial): ?>
                    <div class="testimonial-card" data-aos="fade-up" data-aos-delay="<?= $index * 100 ?>">
                        <div class="testimonial-header">
                            <div class="stars">
                                <?php for ($i = 0; $i < $testimonial['rating']; $i++): ?>
                                    <i class="fas fa-star"></i>
                                <?php endfor; ?>
                            </div>
                            <div class="testimonial-quote">
                                <i class="fas fa-quote-right"></i>
                            </div>
                        </div>
                        
                        <p class="testimonial-text">"<?= htmlspecialchars($testimonial['text']) ?>"</p>
                        
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <span><?= strtoupper(substr($testimonial['name'], 0, 1)) ?></span>
                            </div>
                            <div class="author-info">
                                <h4><?= htmlspecialchars($testimonial['name']) ?></h4>
                                <span><?= htmlspecialchars($testimonial['location']) ?></span>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Call to Action -->
    <section class="cta">
        <div class="cta-background">
            <div class="cta-particles"></div>
        </div>
        
        <div class="container">
            <div class="cta-content" data-aos="fade-up">
                <div class="cta-icon">
                    <i class="fas fa-rocket"></i>
                </div>
                <h2>Ready to Get Started?</h2>
                <p>Join thousands of users who have found their perfect rental home through our trusted platform. Start your journey today!</p>
                
                <div class="cta-buttons">
                    <a href="register.php" class="btn btn-white btn-lg">
                        <i class="fas fa-user-plus"></i>
                        <span>Register as Customer</span>
                    </a>
                    <a href="register.php" class="btn btn-outline-white btn-lg">
                        <i class="fas fa-building"></i>
                        <span>Register as Owner</span>
                    </a>
                </div>
                
                <div class="cta-trust-signals" data-aos="fade-up" data-aos-delay="200">
                    <div class="trust-signal">
                        <i class="fas fa-shield-check"></i>
                        <span>100% Secure</span>
                    </div>
                    <div class="trust-signal">
                        <i class="fas fa-clock"></i>
                        <span>24/7 Support</span>
                    </div>
                    <div class="trust-signal">
                        <i class="fas fa-thumbs-up"></i>
                        <span>Trusted by 1000+</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-grid">
                    <div class="footer-section footer-brand">
                        <div class="brand-info">
                            <div class="footer-logo">
                                <i class="fas fa-home"></i>
                                <span>PropertyRental</span>
                            </div>
                            <p class="brand-description">Your trusted partner in finding the perfect rental home. Safe, secure, and seamless rental experience.</p>
                            
                            <div class="social-links">
                                <a href="#" class="social-link" title="Facebook">
                                    <i class="fab fa-facebook-f"></i>
                                </a>
                                <a href="#" class="social-link" title="Twitter">
                                    <i class="fab fa-twitter"></i>
                                </a>
                                <a href="#" class="social-link" title="Instagram">
                                    <i class="fab fa-instagram"></i>
                                </a>
                                <a href="#" class="social-link" title="LinkedIn">
                                    <i class="fab fa-linkedin-in"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-section">
                        <h3 class="footer-title">Quick Links</h3>
                        <ul class="footer-links">
                            <li><a href="#features">Features</a></li>
                            <li><a href="#properties">Properties</a></li>
                            <li><a href="#how-it-works">How It Works</a></li>
                            <li><a href="#testimonials">Reviews</a></li>
                            <li><a href="about.php">About Us</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3 class="footer-title">Account</h3>
                        <ul class="footer-links">
                            <li><a href="login.php">Login</a></li>
                            <li><a href="register.php">Register</a></li>
                            <li><a href="forgot-password.php">Forgot Password</a></li>
                            <li><a href="help.php">Help Center</a></li>
                            <li><a href="faq.php">FAQ</a></li>
                        </ul>
                    </div>
                    
                    <div class="footer-section">
                        <h3 class="footer-title">Contact Info</h3>
                        <div class="contact-info">
                            <div class="contact-item">
                                <i class="fas fa-map-marker-alt"></i>
                                <span>123 Main Street, Colombo, Sri Lanka</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-phone"></i>
                                <span>+94 77 123 4567</span>
                            </div>
                            <div class="contact-item">
                                <i class="fas fa-envelope"></i>
                                <span>info@propertyrental.lk</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="footer-bottom">
                    <div class="footer-bottom-content">
                        <p>&copy; 2024 PropertyRental System. All rights reserved.</p>
                        <div class="footer-bottom-links">
                            <a href="privacy.php">Privacy Policy</a>
                            <span>|</span>
                            <a href="terms.php">Terms of Service</a>
                            <span>|</span>
                            <a href="cookies.php">Cookie Policy</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Back to Top Button -->
    <button id="backToTop" class="back-to-top" title="Go to top">
        <i class="fas fa-chevron-up"></i>
    </button>

    <!-- External Scripts -->
    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    
    <script>
        // Initialize AOS
        AOS.init({
            duration: 800,
            easing: 'ease-in-out',
            once: true,
            offset: 100
        });

        // Preloader
        window.addEventListener('load', function() {
            const preloader = document.getElementById('preloader');
            setTimeout(() => {
                preloader.classList.add('hidden');
            }, 1000);
        });

        // Navbar scroll effect
        window.addEventListener('scroll', function() {
            const navbar = document.getElementById('navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });

        // Mobile menu toggle
        const mobileMenu = document.getElementById('mobileMenu');
        const mobileNav = document.getElementById('mobileNav');
        
        if (mobileMenu) {
            mobileMenu.addEventListener('click', function() {
                mobileMenu.classList.toggle('active');
                mobileNav.classList.toggle('active');
            });
        }

        // Mobile nav links
        document.querySelectorAll('.mobile-nav-link').forEach(link => {
            link.addEventListener('click', function() {
                mobileMenu.classList.remove('active');
                mobileNav.classList.remove('active');
            });
        });

        // Smooth scroll for navigation links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Counter animation for stats
        function animateCounter(element, target) {
            let current = 0;
            const increment = target / 100;
            const timer = setInterval(() => {
                current += increment;
                if (current >= target) {
                    current = target;
                    clearInterval(timer);
                }
                element.textContent = Math.floor(current);
            }, 20);
        }

        // Intersection Observer for stats animation
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const target = parseInt(entry.target.getAttribute('data-target'));
                    animateCounter(entry.target, target);
                    statsObserver.unobserve(entry.target);
                }
            });
        });

        document.querySelectorAll('.stat-number[data-target]').forEach(stat => {
            statsObserver.observe(stat);
        });

        // Active navigation link highlighting
        const sections = document.querySelectorAll('section[id]');
        const navLinks = document.querySelectorAll('.nav-link[href^="#"]');

        window.addEventListener('scroll', () => {
            let current = '';
            
            sections.forEach(section => {
                const sectionTop = section.offsetTop;
                const sectionHeight = section.clientHeight;
                if (scrollY >= (sectionTop - 200)) {
                    current = section.getAttribute('id');
                }
            });
            
            navLinks.forEach(link => {
                link.classList.remove('active');
                if (link.getAttribute('href') === '#' + current) {
                    link.classList.add('active');
                }
            });
        });

        // Back to top button
        const backToTop = document.getElementById('backToTop');
        
        window.addEventListener('scroll', () => {
            if (window.scrollY > 300) {
                backToTop.classList.add('visible');
            } else {
                backToTop.classList.remove('visible');
            }
        });

        backToTop.addEventListener('click', () => {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });

        // Property card interactions
        document.querySelectorAll('.favorite-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                if (icon.classList.contains('far')) {
                    icon.classList.remove('far');
                    icon.classList.add('fas');
                    this.style.color = '#ef4444';
                } else {
                    icon.classList.remove('fas');
                    icon.classList.add('far');
                    this.style.color = '';
                }
            });
        });

        // Search form enhancements
        const searchForm = document.querySelector('.search-form');
        if (searchForm) {
            searchForm.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const formData = new FormData(this);
                const params = new URLSearchParams();
                
                for (let [key, value] of formData) {
                    if (value.trim() !== '') {
                        params.append(key, value);
                    }
                }
                
                window.location.href = 'properties.php?' + params.toString();
            });
        }
    </script>
</body>
</html>