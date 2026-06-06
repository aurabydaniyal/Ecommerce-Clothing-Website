<?php
session_start();
require_once 'db_connection.php';

// Get type from the including file
$type = isset($type) ? $type : 'men';
$page_title = isset($page_title) ? $page_title : ucfirst($type) . "'s Collection";

// Get filter by category if selected
$category_filter = isset($_GET['category']) ? mysqli_real_escape_string($conn, $_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : '';

// Build query join with categories
$sql = "SELECT p.*, c.name as category_name 
        FROM products p 
        LEFT JOIN categories c ON p.category_id = c.id 
        WHERE p.type = '$type'";

// Apply category filter
if($category_filter) {
    $sql .= " AND c.name = '$category_filter'";
}

// Apply sorting
if($sort == 'price_low') {
    $sql .= " ORDER BY COALESCE(p.sale_price, p.price) ASC";
} elseif($sort == 'price_high') {
    $sql .= " ORDER BY COALESCE(p.sale_price, p.price) DESC";
} elseif($sort == 'newest') {
    $sql .= " ORDER BY p.created_at DESC";
} elseif($sort == 'popular') {
    $sql .= " ORDER BY p.views DESC";
} else {
    $sql .= " ORDER BY p.created_at DESC";
}

$products = mysqli_query($conn, $sql);

// Get categories for this type
$categories = mysqli_query($conn, "SELECT * FROM categories WHERE type = '$type' AND is_active = 1 ORDER BY display_order");

// For sale page - calculate countdown timer (24 hours from now)
$show_timer = ($type == 'sale');
if($show_timer) {
    // Set end time to 24 hours from current time
    $end_time = time() + (24 * 60 * 60);
}

$descriptions = [
    'men' => 'Discover the latest trends in men\'s fashion',
    'women' => 'Discover the latest trends in women\'s fashion',
    'kids' => 'Discover the latest trends in kids\' fashion',
    'sale' => '⚡ FLASH SALE! Up to 50% OFF - Limited Time Offer ⚡'
];
$page_description = $descriptions[$type];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title><?php echo $page_title; ?> - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        :root {
            --primary: #FFD700;
            --primary-dark: #E6BE00;
            --primary-glow: #FFD700;
            --dark: #0a0a0a;
            --dark-gray: #1a1a1a;
            --gray: #2a2a2a;
            --light-gray: #3a3a3a;
            --white: #ffffff;
            --neon-shadow: 0 0 10px rgba(255, 215, 0, 0.3), 0 0 20px rgba(255, 215, 0, 0.15);
        }
        
        /* ============================================
           CUSTOM TOAST NOTIFICATION
           ============================================ */
        .custom-toast {
            position: fixed;
            top: 90px;
            right: 20px;
            z-index: 10001;
            max-width: 350px;
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-left: 4px solid #FFD700;
            border-radius: 12px;
            padding: 15px 20px;
            margin-bottom: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
            transform: translateX(120%);
            transition: transform 0.3s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            display: flex;
            align-items: center;
            gap: 12px;
            cursor: pointer;
        }
        
        .custom-toast.show {
            transform: translateX(0);
        }
        
        .custom-toast.success {
            border-left-color: #28a745;
        }
        .custom-toast.success i:first-child { color: #28a745; }
        .custom-toast.error {
            border-left-color: #dc3545;
        }
        .custom-toast.error i:first-child { color: #dc3545; }
        .custom-toast.warning {
            border-left-color: #ffc107;
        }
        .custom-toast.warning i:first-child { color: #ffc107; }
        
        .custom-toast i:first-child { font-size: 22px; }
        .toast-content { flex: 1; }
        .toast-title {
            font-weight: bold;
            margin-bottom: 3px;
            color: #FFD700;
        }
        .toast-message {
            font-size: 13px;
            color: #ccc;
        }
        .toast-close {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            font-size: 14px;
            transition: color 0.3s;
        }
        .toast-close:hover { color: #FFD700; }
        
        /* ============================================
           BUTTON GLOW EFFECT
           ============================================ */
        .btn-cart, .btn-wishlist, .quick-view-btn-card, .cat-btn, .sort-dropdown {
            position: relative;
            overflow: hidden;
        }
        
        .btn-cart, .btn-wishlist, .quick-view-btn-card {
            animation: subtleBeat 2s ease-in-out infinite;
        }
        
        @keyframes subtleBeat {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 0 3px rgba(255, 215, 0, 0.15); }
        }
        
        .cat-btn {
            animation: catGlow 2.5s ease-in-out infinite;
        }
        
        @keyframes catGlow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(255, 215, 0, 0.2); border-color: rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 0 2px rgba(255, 215, 0, 0.1); border-color: rgba(255, 215, 0, 0.5); }
        }
        
        .cat-btn.active {
            animation: activeGlow 1.5s ease-in-out infinite;
        }
        
        @keyframes activeGlow {
            0%, 100% { box-shadow: 0 0 5px #FFD700, 0 0 10px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 0 8px #FFD700, 0 0 15px rgba(255, 215, 0, 0.4); }
        }
        
        .btn-cart:hover, .btn-wishlist:hover, .quick-view-btn-card:hover {
            animation: none;
            transform: translateY(-2px);
        }
        
        /* ============================================
           PRODUCT CARD - Simple Hover Overlay
           ============================================ */
        .product-card {
            background: var(--gray);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            margin-bottom: 30px;
            position: relative;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
        }
        
        .product-img-wrapper {
            position: relative;
            overflow: hidden;
            height: 280px;
            cursor: pointer;
        }
        
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-img-wrapper:hover .product-img {
            transform: scale(1.05);
        }
        
        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .product-img-wrapper:hover .product-overlay {
            opacity: 1;
        }
        
        .quick-view-btn-card {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 12px 25px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
            transition: all 0.3s ease;
            transform: translateY(20px);
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .product-img-wrapper:hover .quick-view-btn-card {
            transform: translateY(0);
        }
        
        .quick-view-btn-card:hover {
            transform: scale(1.05);
            background: #FFD700;
        }
        
        .sale-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #FFD700;
            color: #000;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
            z-index: 10;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: var(--white);
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary);
        }
        
        .product-old-price {
            text-decoration: line-through;
            color: var(--text-muted);
            font-size: 14px;
            margin-left: 8px;
        }
        
        .discount-badge {
            color: #FFD700;
            font-size: 12px;
            margin-left: 5px;
        }
        
        .product-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-cart {
            background: var(--primary);
            color: #000;
            border: none;
            padding: 10px 15px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
            cursor: pointer;
            font-size: 13px;
        }
        
        .btn-cart:hover {
            background: var(--primary-dark);
            transform: translateY(-2px);
            animation: none;
        }
        
        .btn-wishlist {
            background: transparent;
            border: 2px solid var(--primary);
            color: var(--primary);
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-wishlist:hover {
            background: var(--primary);
            color: #000;
            transform: translateY(-2px);
            animation: none;
        }
        
        .btn-wishlist:hover i {
            animation: heartbeat 1s ease infinite;
            display: inline-block;
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        /* ============================================
           ENHANCED QUICK VIEW MODAL WITH NEON GLOW
           ============================================ */
        .modal-content {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border: 2px solid #FFD700;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.4), 0 0 40px rgba(255, 215, 0, 0.2), 0 0 60px rgba(255, 215, 0, 0.1);
            animation: modalNeonPulse 2s ease-in-out infinite;
        }
        
        @keyframes modalNeonPulse {
            0%, 100% { box-shadow: 0 0 15px rgba(255, 215, 0, 0.3), 0 0 30px rgba(255, 215, 0, 0.15); }
            50% { box-shadow: 0 0 25px rgba(255, 215, 0, 0.5), 0 0 50px rgba(255, 215, 0, 0.25); }
        }
        
        .modal-header {
            border-bottom: 2px solid #FFD700;
            background: rgba(0,0,0,0.3);
        }
        
        .modal-title {
            color: #FFD700;
            font-weight: bold;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
        }
        
        /* Image Container with Zoom on Hover */
        .quick-view-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            cursor: zoom-in;
        }
        
        .quick-view-main-img {
            width: 100%;
            transition: transform 0.5s ease;
            border-radius: 15px;
        }
        
        .quick-view-image-container:hover .quick-view-main-img {
            transform: scale(1.2);
        }
        
        /* Image Transition Animation */
        .quick-view-main-img {
            transition: opacity 0.3s ease-in-out, transform 0.5s ease;
        }
        
        .img-changing {
            opacity: 0.5;
            transform: scale(0.95);
        }
        
        /* Thumbnail Styles */
        .quick-view-thumbnails {
            display: flex;
            gap: 10px;
            margin-top: 15px;
            flex-wrap: wrap;
        }
        
        .quick-view-thumb {
            width: 70px;
            height: 70px;
            object-fit: cover;
            border-radius: 10px;
            cursor: pointer;
            border: 2px solid #444;
            transition: all 0.3s ease;
        }
        
        .quick-view-thumb:hover {
            border-color: #FFD700;
            transform: translateY(-3px);
            box-shadow: 0 0 10px rgba(255,215,0,0.5);
        }
        
        .quick-view-thumb.active {
            border-color: #FFD700;
            box-shadow: 0 0 15px rgba(255,215,0,0.5);
        }
        
        /* Color Options */
        .color-option {
            transition: all 0.3s ease;
        }
        
        .color-option:hover {
            transform: scale(1.1);
        }
        
        .color-option.selected {
            border: 3px solid #FFD700 !important;
            box-shadow: 0 0 10px rgba(255,215,0,0.5);
            transform: scale(1.1);
        }
        
        /* Size Options */
        .size-option {
            transition: all 0.3s ease;
        }
        
        .size-option.selected {
            background: #FFD700 !important;
            color: #000 !important;
            border-color: #FFD700 !important;
            box-shadow: 0 0 10px rgba(255,215,0,0.5);
        }
        
        /* ============================================
           CATEGORY BUTTON NAVIGATION
           ============================================ */
        .category-nav {
            background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
            border-radius: 60px;
            padding: 8px;
            margin: 20px 0;
            border: 1px solid rgba(255, 215, 0, 0.3);
            box-shadow: var(--neon-shadow);
            position: relative;
            overflow: hidden;
        }
        
        .category-nav::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            background: linear-gradient(45deg, #FFD700, #FFC107, #FFD700, #FFC107);
            border-radius: 62px;
            z-index: -1;
            opacity: 0;
            transition: opacity 0.5s;
        }
        
        .category-nav:hover::before {
            opacity: 1;
            animation: borderGlow 2s linear infinite;
        }
        
        @keyframes borderGlow {
            0% { filter: blur(0px); }
            50% { filter: blur(5px); }
            100% { filter: blur(0px); }
        }
        
        .category-buttons {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            justify-content: center;
            align-items: center;
        }
        
        .cat-btn {
            position: relative;
            padding: 12px 28px;
            background: transparent;
            border: 2px solid rgba(255, 215, 0, 0.4);
            color: #fff;
            font-weight: 600;
            font-size: 15px;
            border-radius: 50px;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            z-index: 1;
            letter-spacing: 0.5px;
            backdrop-filter: blur(5px);
        }
        
        .cat-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border-radius: 50px;
            transform: scale(0);
            transition: transform 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            z-index: -1;
        }
        
        .cat-btn:hover::before {
            transform: scale(1);
        }
        
        .cat-btn:hover {
            color: #000;
            transform: translateY(-3px) scale(1.02);
            border-color: #FFD700;
            animation: none;
        }
        
        .cat-btn.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border-color: #FFD700;
        }
        
        .cat-btn-all {
            background: rgba(255, 215, 0, 0.1);
            border: 2px solid rgba(255, 215, 0, 0.5);
        }
        
        .cat-btn-all.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border: none;
        }
        
        .cat-icon {
            margin-right: 8px;
            font-size: 14px;
        }
        
        /* ============================================
           HAMBURGER MENU FOR MOBILE
           ============================================ */
        .category-header-mobile {
            display: none;
            justify-content: space-between;
            align-items: center;
            padding: 15px 20px;
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 15px;
            margin: 15px 0;
            border: 1px solid rgba(255, 215, 0, 0.3);
            cursor: pointer;
        }
        
        .hamburger-icon {
            width: 35px;
            height: 35px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 6px;
            cursor: pointer;
        }
        
        .hamburger-icon span {
            display: block;
            width: 100%;
            height: 3px;
            background: #FFD700;
            border-radius: 3px;
            transition: all 0.3s;
        }
        
        .hamburger-icon.active span:nth-child(1) {
            transform: rotate(45deg) translate(8px, 8px);
        }
        
        .hamburger-icon.active span:nth-child(2) {
            opacity: 0;
        }
        
        .hamburger-icon.active span:nth-child(3) {
            transform: rotate(-45deg) translate(8px, -8px);
        }
        
        .category-title {
            color: #FFD700;
            font-weight: bold;
            font-size: 18px;
        }
        
        .category-title i {
            margin-right: 8px;
        }
        
        .category-mobile-menu {
            display: none;
            flex-direction: column;
            gap: 10px;
            padding: 15px;
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 15px;
            margin-bottom: 15px;
            border: 1px solid rgba(255, 215, 0, 0.3);
        }
        
        .category-mobile-menu.show {
            display: flex;
        }
        
        .cat-btn-mobile {
            padding: 12px 20px;
            background: rgba(255, 215, 0, 0.1);
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 50px;
            color: #fff;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: center;
        }
        
        .cat-btn-mobile.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border-color: #FFD700;
        }
        
        .cat-btn-mobile:hover {
            transform: translateX(5px);
            border-color: #FFD700;
        }
        
        /* ============================================
           SALE COUNTDOWN TIMER
           ============================================ */
        .sale-timer-container {
            background: linear-gradient(135deg, #FFD70020, #FFC10710);
            border-radius: 20px;
            padding: 30px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #FFD700;
            box-shadow: 0 0 30px rgba(255, 215, 0, 0.2), inset 0 0 20px rgba(255, 215, 0, 0.05);
            position: relative;
            overflow: hidden;
        }
        
        .sale-timer-container::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.08) 0%, transparent 70%);
            animation: rotateGlow 10s linear infinite;
        }
        
        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .sale-title {
            font-size: 32px;
            font-weight: 800;
            color: #FFD700;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-bottom: 10px;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
        }
        
        .sale-subtitle {
            color: #fff;
            margin-bottom: 25px;
            font-size: 16px;
        }
        
        .countdown-wrapper {
            display: flex;
            justify-content: center;
            gap: 20px;
            flex-wrap: wrap;
            margin: 20px 0;
        }
        
        .countdown-box {
            background: rgba(0,0,0,0.7);
            border-radius: 15px;
            padding: 20px;
            min-width: 100px;
            text-align: center;
            border: 1px solid #FFD700;
            box-shadow: 0 0 15px rgba(255,215,0,0.2);
            backdrop-filter: blur(10px);
        }
        
        .countdown-number {
            font-size: 48px;
            font-weight: 800;
            color: #FFD700;
            font-family: monospace;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
        }
        
        .countdown-label {
            font-size: 12px;
            color: #fff;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 5px;
        }
        
        .sale-offer-text {
            margin-top: 20px;
            font-size: 18px;
            color: #FFD700;
            font-weight: bold;
        }
        
        /* ============================================
           SORT DROPDOWN
           ============================================ */
        .sort-dropdown {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border: 2px solid rgba(255, 215, 0, 0.4);
            border-radius: 50px;
            padding: 10px 20px;
            color: #fff;
            transition: all 0.3s;
        }
        
        .sort-dropdown:hover {
            border-color: #FFD700;
        }
        
        .sort-dropdown i {
            color: #FFD700;
            margin-right: 8px;
        }
        
        .dropdown-menu-custom {
            background: #1a1a1a;
            border: 1px solid rgba(255, 215, 0, 0.3);
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        .dropdown-menu-custom .dropdown-item {
            color: #fff;
            transition: all 0.3s;
        }
        
        .dropdown-menu-custom .dropdown-item:hover {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
        }
        
        /* Particle Effect */
        .particle-effect {
            position: fixed;
            width: 8px;
            height: 8px;
            background: #FFD700;
            border-radius: 50%;
            pointer-events: none;
            z-index: 10000;
            animation: particleFade 1s ease-out forwards;
        }
        
        @keyframes particleFade {
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0); opacity: 0; }
        }
        
        /* Loading Overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.8);
            z-index: 9999;
            display: none;
            justify-content: center;
            align-items: center;
        }
        
        .loading-spinner {
            width: 50px;
            height: 50px;
            border: 3px solid #FFD700;
            border-top: 3px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        /* Mobile Responsive */
        @media (max-width: 768px) {
            .category-nav { display: none; }
            .category-header-mobile { display: flex; }
            .product-img-wrapper { height: 240px; }
            .countdown-box {
                min-width: 70px;
                padding: 12px;
            }
            .countdown-number { font-size: 28px; }
            .sale-title { font-size: 24px; }
            .custom-toast {
                top: 70px;
                right: 10px;
                left: 10px;
                max-width: none;
            }
            .quick-view-thumb {
                width: 50px;
                height: 50px;
            }
        }
        
        @media (min-width: 769px) {
            .category-mobile-menu { display: none !important; }
        }
    </style>
</head>
<body>

<div class="loading-overlay" id="loadingOverlay">
    <div class="loading-spinner"></div>
</div>

<?php include 'includes/nav-menu.php'; ?>

<!-- Page Header -->
<div class="container-fluid py-4" style="background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%); border-bottom: 2px solid #FFD700;">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h1 style="color: #FFD700; text-shadow: 0 0 10px rgba(255,215,0,0.3);"><?php echo $page_title; ?></h1>
                <p style="color: #ffffff; opacity: 0.9;"><?php echo $page_description; ?></p>
            </div>
            <div class="col-md-6">
                <div class="d-flex justify-content-md-end mt-3 mt-md-0 gap-2">
                    <div class="dropdown">
                        <button class="btn sort-dropdown dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-filter"></i> Sort By
                        </button>
                        <ul class="dropdown-menu dropdown-menu-custom">
                            <li><a class="dropdown-item sort-option" href="#" data-sort="newest"><i class="fas fa-clock me-2"></i> Newest First</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="price_low"><i class="fas fa-arrow-up me-2"></i> Price: Low to High</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="price_high"><i class="fas fa-arrow-down me-2"></i> Price: High to Low</a></li>
                            <li><a class="dropdown-item sort-option" href="#" data-sort="popular"><i class="fas fa-fire me-2"></i> Most Popular</a></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- SALE COUNTDOWN TIMER -->
<?php if($show_timer): ?>
<div class="container">
    <div class="sale-timer-container">
        <div class="sale-title">⚡ FLASH SALE ENDS IN ⚡</div>
        <div class="sale-subtitle">Grab your favorites before they're gone!</div>
        
        <div class="countdown-wrapper">
            <div class="countdown-box">
                <div class="countdown-number" id="days">00</div>
                <div class="countdown-label">Days</div>
            </div>
            <div class="countdown-box">
                <div class="countdown-number" id="hours">00</div>
                <div class="countdown-label">Hours</div>
            </div>
            <div class="countdown-box">
                <div class="countdown-number" id="minutes">00</div>
                <div class="countdown-label">Minutes</div>
            </div>
            <div class="countdown-box">
                <div class="countdown-number" id="seconds">00</div>
                <div class="countdown-label">Seconds</div>
            </div>
        </div>
        
        <div class="sale-offer-text">
            <i class="fas fa-gift"></i> UPTO 50% OFF - SHOP NOW  <i class="fas fa-truck"></i>
        </div>
    </div>
</div>
<br>
<?php endif; ?>

<!-- CATEGORY NAVIGATION -->
<div class="container">
    <div class="category-nav">
        <div class="category-buttons">
            <button class="cat-btn cat-btn-all <?php echo !$category_filter ? 'active' : ''; ?>" data-category="">
                <i class="fas fa-th-large cat-icon"></i> All Products
            </button>
            
            <?php 
            mysqli_data_seek($categories, 0);
            while($cat = mysqli_fetch_assoc($categories)): 
                $is_active = ($category_filter == $cat['name']);
            ?>
            <button class="cat-btn <?php echo $is_active ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($cat['name']); ?>">
                <i class="fas fa-tag cat-icon"></i> <?php echo htmlspecialchars($cat['name']); ?>
            </button>
            <?php endwhile; ?>
        </div>
    </div>
    
    <!-- MOBILE HAMBURGER MENU -->
    <div class="category-header-mobile" id="categoryHeaderMobile">
        <div class="category-title">
            <i class="fas fa-tags"></i> Categories
        </div>
        <div class="hamburger-icon" id="hamburgerIcon">
            <span></span>
            <span></span>
            <span></span>
        </div>
    </div>
    
    <div class="category-mobile-menu" id="categoryMobileMenu">
        <button class="cat-btn-mobile <?php echo !$category_filter ? 'active' : ''; ?>" data-category="">
            <i class="fas fa-th-large"></i> All Products
        </button>
        <?php 
        mysqli_data_seek($categories, 0);
        while($cat = mysqli_fetch_assoc($categories)): 
            $is_active = ($category_filter == $cat['name']);
        ?>
        <button class="cat-btn-mobile <?php echo $is_active ? 'active' : ''; ?>" data-category="<?php echo htmlspecialchars($cat['name']); ?>">
            <i class="fas fa-tag"></i> <?php echo htmlspecialchars($cat['name']); ?>
        </button>
        <?php endwhile; ?>
    </div>
</div>

<!-- PRODUCTS GRID -->
<div class="container mt-4" id="productsContainer">
    <?php if(mysqli_num_rows($products) == 0): ?>
        <div class="alert alert-info text-center py-5" style="background: #1a1a1a; border: none; color: #fff;">
            <i class="fas fa-box-open fa-3x mb-3"></i>
            <h4>No products found</h4>
            <p>Check back soon for new arrivals!</p>
            <a href="index.php" class="btn btn-warning mt-3">Continue Shopping</a>
        </div>
    <?php else: ?>
        <div class="row" id="productsGrid">
            <?php while($product = mysqli_fetch_assoc($products)): 
                $img_url = !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300x400?text=No+Image';
                $is_on_sale = ($product['sale_price'] && $product['sale_price'] < $product['price']);
                $discount_percent = $is_on_sale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4 product-item" data-category="<?php echo htmlspecialchars($product['category_name']); ?>">
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="<?php echo $img_url; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                        <div class="product-overlay">
                            <button class="quick-view-btn-card" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-eye"></i> Quick View
                            </button>
                        </div>
                        <?php if($is_on_sale): ?>
                            <span class="sale-badge">-<?php echo $discount_percent; ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    
                    <div class="product-info">
                        <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                        <div class="mb-2">
                            <?php if($is_on_sale): ?>
                                <span class="product-price">Rs <?php echo $product['sale_price']; ?></span>
                                <span class="product-old-price">Rs <?php echo $product['price']; ?></span>
                                <span class="discount-badge">-<?php echo $discount_percent; ?>%</span>
                            <?php else: ?>
                                <span class="product-price">Rs <?php echo $product['price']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-buttons">
                            <button class="btn-cart add-to-cart" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <button class="btn-wishlist add-to-wishlist" data-id="<?php echo $product['id']; ?>">
                                <i class="fas fa-heart"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Footer -->
<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    
    // ============================================
    // CUSTOM TOAST NOTIFICATION
    // ============================================
    window.showToast = function(message, type = 'success') {
        const toastId = 'toast_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
        const titles = { success: 'Success!', error: 'Oops!', warning: 'Heads Up!' };
        const icons = { success: 'fa-check-circle', error: 'fa-exclamation-circle', warning: 'fa-exclamation-triangle' };
        
        const toastHtml = `
            <div class="custom-toast ${type}" id="${toastId}">
                <i class="fas ${icons[type] || 'fa-info-circle'}"></i>
                <div class="toast-content">
                    <div class="toast-title">${titles[type] || 'Notice'}</div>
                    <div class="toast-message">${message}</div>
                </div>
                <button class="toast-close" onclick="this.closest('.custom-toast').remove()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        $('body').append(toastHtml);
        const $toast = $('#' + toastId);
        setTimeout(() => $toast.addClass('show'), 10);
        setTimeout(() => { $toast.removeClass('show'); setTimeout(() => $toast.remove(), 500); }, 4000);
        $toast.click(function(e) { if(!$(e.target).closest('.toast-close').length) { $(this).removeClass('show'); setTimeout(() => $(this).remove(), 300); } });
    };
    
    // ============================================
    // CATEGORY FILTER
    // ============================================
    function filterProductsByCategory(categoryName) {
        $('#loadingOverlay').fadeIn();
        let url = window.location.pathname;
        let params = new URLSearchParams();
        if(categoryName && categoryName !== '') params.set('category', categoryName);
        let currentSort = getCurrentSort();
        if(currentSort) params.set('sort', currentSort);
        let queryString = params.toString();
        window.location.href = queryString ? url + '?' + queryString : url;
    }
    
    function getCurrentSort() {
        let urlParams = new URLSearchParams(window.location.search);
        return urlParams.get('sort');
    }
    
    $('.cat-btn, .cat-btn-mobile').click(function() { filterProductsByCategory($(this).data('category')); });
    
    $('.sort-option').click(function(e) {
        e.preventDefault();
        let sort = $(this).data('sort');
        let currentCategory = '<?php echo $category_filter; ?>';
        let params = new URLSearchParams();
        if(currentCategory) params.set('category', currentCategory);
        params.set('sort', sort);
        window.location.href = window.location.pathname + '?' + params.toString();
    });
    
    // ============================================
    // HAMBURGER MENU
    // ============================================
    $('#hamburgerIcon, #categoryHeaderMobile').click(function(e) {
        e.stopPropagation();
        $('#hamburgerIcon').toggleClass('active');
        $('#categoryMobileMenu').toggleClass('show');
    });
    
    $(document).click(function(e) {
        if(!$(e.target).closest('.category-header-mobile').length && !$(e.target).closest('.category-mobile-menu').length) {
            $('#hamburgerIcon').removeClass('active');
            $('#categoryMobileMenu').removeClass('show');
        }
    });
    
    // ============================================
    // COUNTDOWN TIMER
    // ============================================
    <?php if($show_timer): ?>
    function startCountdown() {
        let endTime = new Date();
        endTime.setHours(endTime.getHours() + 24);
        endTime.setMinutes(0);
        endTime.setSeconds(0);
        
        function updateCountdown() {
            let now = new Date();
            let distance = endTime - now;
            if(distance < 0) {
                endTime = new Date();
                endTime.setHours(endTime.getHours() + 24);
                endTime.setMinutes(0);
                endTime.setSeconds(0);
                distance = endTime - now;
            }
            let days = Math.floor(distance / (1000 * 60 * 60 * 24));
            let hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            let minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            let seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            $('#days').text(String(days).padStart(2, '0'));
            $('#hours').text(String(hours).padStart(2, '0'));
            $('#minutes').text(String(minutes).padStart(2, '0'));
            $('#seconds').text(String(seconds).padStart(2, '0'));
        }
        updateCountdown();
        setInterval(updateCountdown, 1000);
    }
    startCountdown();
    <?php endif; ?>
    
    // ============================================
    // ADD TO CART
    // ============================================
    $('.add-to-cart').click(function() {
        var productId = $(this).data('id');
        var btn = $(this);
        var originalHtml = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>');
        btn.prop('disabled', true);
        
        $.ajax({
            url: 'add-to-cart.php',
            method: 'POST',
            data: {product_id: productId, quantity: 1},
            success: function(response) {
                if(response === 'success') {
                    showToast('Product added to cart successfully!', 'success');
                    btn.html('<i class="fas fa-check"></i> Added!');
                    setTimeout(function() { btn.html(originalHtml); btn.prop('disabled', false); }, 1500);
                    updateCartCount();
                } else if(response === 'not_logged_in') {
                    showToast('Please login to add items to cart', 'error');
                    setTimeout(function() { window.location.href = 'login.php'; }, 1500);
                } else {
                    showToast('Error adding product to cart', 'error');
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
                }
            },
            error: function() {
                showToast('Something went wrong!', 'error');
                btn.html(originalHtml);
                btn.prop('disabled', false);
            }
        });
    });
    
    // ============================================
    // ADD TO WISHLIST
    // ============================================
    $('.add-to-wishlist').click(function() {
        var productId = $(this).data('id');
        var btn = $(this);
        
        $.ajax({
            url: 'add-to-wishlist.php',
            method: 'POST',
            data: {product_id: productId},
            success: function(response) {
                if(response === 'success') {
                    showToast('Product added to wishlist!', 'success');
                    btn.html('<i class="fas fa-check"></i>');
                    setTimeout(function() { btn.html('<i class="fas fa-heart"></i>'); }, 1000);
                    updateWishlistCount();
                } else if(response === 'not_logged_in') {
                    showToast('Please login to add items to wishlist', 'error');
                    setTimeout(function() { window.location.href = 'login.php'; }, 1500);
                } else if(response === 'already_exists') {
                    showToast('Product is already in your wishlist!', 'warning');
                } else {
                    showToast('Error adding to wishlist', 'error');
                }
            }
        });
    });
    
    // ============================================
    // QUICK VIEW WITH GOLD PARTICLES
    // ============================================
    $('.quick-view-btn-card').click(function(e) {
        var productId = $(this).data('id');
        createGoldParticles(e.clientX, e.clientY);
        showQuickViewModal(productId);
    });
    
    function createGoldParticles(x, y) {
        for(let i = 0; i < 25; i++) {
            let particle = document.createElement('div');
            particle.className = 'particle-effect';
            let angle = Math.random() * Math.PI * 2;
            let velocity = 2 + Math.random() * 6;
            let vx = Math.cos(angle) * velocity;
            let vy = Math.sin(angle) * velocity;
            particle.style.left = (x - 4) + 'px';
            particle.style.top = (y - 4) + 'px';
            document.body.appendChild(particle);
            let posX = x - 4, posY = y - 4;
            let startTime = Date.now();
            function animate() {
                let elapsed = (Date.now() - startTime) / 1000;
                if (elapsed > 1) { particle.remove(); return; }
                posX += vx; posY += vy;
                particle.style.left = posX + 'px';
                particle.style.top = posY + 'px';
                particle.style.opacity = 1 - elapsed;
                requestAnimationFrame(animate);
            }
            animate();
        }
    }
    
    function updateCartCount() {
        $.ajax({url: 'get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
    }
    function updateWishlistCount() {
        $.ajax({url: 'get-wishlist-count.php', success: function(data) {$('#wishlistCount').text(data);}});
    }
    
    updateCartCount();
    updateWishlistCount();
});

// ============================================
// ENHANCED QUICK VIEW MODAL
// ============================================
let currentImages = [];
let currentImageIndex = 0;

function showQuickViewModal(productId) {
    var modalHtml = `
        <div class="modal fade" id="quickViewModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-eye"></i> Product Details</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="quickViewBody">
                        <div class="text-center py-5">
                            <div class="spinner-border text-warning"></div>
                            <p class="mt-3">Loading...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    $('#quickViewModal').remove();
    $('body').append(modalHtml);
    
    $.ajax({
        url: 'get-product-details.php',
        method: 'POST',
        data: {product_id: productId},
        dataType: 'json',
        success: function(product) {
            if(product.success) {
                displayQuickViewWithImages(product);
                $('#quickViewModal').modal('show');
            } else {
                showToast('Product not found!', 'error');
            }
        },
        error: function() {
            showToast('Error loading product details', 'error');
        }
    });
}

function displayQuickViewWithImages(product) {
    // ============================================
    // FIXED: Build images array properly from product data
    // ============================================
    let images = [];
    
    // Add main image
    if(product.image_url && product.image_url !== '') {
        images.push(product.image_url);
    }
    
    // Add image2 if exists
    if(product.image2 && product.image2 !== '' && product.image2 !== product.image_url) {
        images.push(product.image2);
    }
    
    // Add image3 if exists  
    if(product.image3 && product.image3 !== '' && product.image3 !== product.image_url) {
        images.push(product.image3);
    }
    
    // Also check for images array from API
    if(product.images && product.images.length > 0) {
        product.images.forEach(function(img) {
            if(img && img !== '' && !images.includes(img)) {
                images.push(img);
            }
        });
    }
    
    // If no images found, use placeholder
    if(images.length === 0) {
        images.push('https://via.placeholder.com/500x500?text=No+Image');
    }
    
    currentImages = images;
    currentImageIndex = 0;
    
    // Debug - check what images we have
    console.log('Product images found:', images);
    
    // Parse colors and sizes
    let colors = [];
    let colorNames = [];
    let sizes = [];
    
    // Handle colors from different possible formats
    if(product.colors && product.colors.length > 0 && product.colors[0] !== '') {
        if(typeof product.colors === 'string') {
            colors = product.colors.split(',');
        } else {
            colors = product.colors;
        }
    }
    
    // Handle color names
    if(product.color_names && product.color_names.length > 0) {
        if(typeof product.color_names === 'string') {
            colorNames = product.color_names.split(',');
        } else {
            colorNames = product.color_names;
        }
    }
    
    // Handle sizes
    if(product.sizes && product.sizes.length > 0 && product.sizes[0] !== '') {
        if(typeof product.sizes === 'string') {
            sizes = product.sizes.split(',');
        } else {
            sizes = product.sizes;
        }
    }
    
    // Clean up arrays (remove empty values)
    colors = colors.filter(c => c && c.trim() !== '');
    sizes = sizes.filter(s => s && s.trim() !== '');
    
    // Auto-select if only one option
    let defaultColor = '';
    let defaultSize = '';
    
    if(colors.length === 1) {
        defaultColor = colors[0];
    }
    if(sizes.length === 1) {
        defaultSize = sizes[0];
    }
    
    // Color HTML with auto-selection
    let colorHtml = '';
    if(colors.length > 0) {
        colorHtml = '<div class="d-flex flex-wrap gap-2">';
        colors.forEach(function(color, idx) {
            const colorName = colorNames[idx] || `Color ${idx+1}`;
            const isSelected = (defaultColor === color);
            colorHtml += `<div class="color-option ${isSelected ? 'selected' : ''}" 
                style="background-color: ${color.trim().toLowerCase()}; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 2px solid ${isSelected ? '#FFD700' : '#fff'}; box-shadow: ${isSelected ? '0 0 10px rgba(255,215,0,0.5)' : 'none'};" 
                data-color="${color.trim()}"
                data-colorname="${colorName}"></div>`;
        });
        colorHtml += '</div>';
        if(colors.length > 1) {
            colorHtml += '<small class="text-muted mt-1 d-block">Click to select a color</small>';
        }
    } else {
        colorHtml = '<p class="text-muted">No color options</p>';
    }
    
    // Size HTML with auto-selection
    let sizeHtml = '';
    if(sizes.length > 0) {
        sizeHtml = '<div class="d-flex flex-wrap gap-2">';
        sizes.forEach(function(size) {
            const isSelected = (defaultSize === size);
            sizeHtml += `<button class="size-option btn btn-sm m-1 ${isSelected ? 'btn-warning selected' : 'btn-outline-secondary'}" 
                data-size="${size.trim()}" 
                style="${isSelected ? 'background: #FFD700 !important; color: #000 !important; border-color: #FFD700 !important; box-shadow: 0 0 8px rgba(255,215,0,0.5);' : ''}">
                ${size.trim()}
            </button>`;
        });
        sizeHtml += '</div>';
        if(sizes.length > 1) {
            sizeHtml += '<small class="text-muted mt-1 d-block">Click to select a size</small>';
        }
    } else {
        sizeHtml = '<p class="text-muted">No size options</p>';
    }
    
    // Price HTML
    let priceHtml = '';
    let isOnSale = (product.sale_price && parseFloat(product.sale_price) < parseFloat(product.price));
    if(isOnSale) {
        var discount = Math.round(((product.price - product.sale_price) / product.price) * 100);
        priceHtml = `
            <span class="h2 text-warning">Rs ${parseFloat(product.sale_price).toFixed(2)}</span>
            <span class="text-muted"><del>Rs ${parseFloat(product.price).toFixed(2)}</del></span>
            <span class="badge bg-danger ms-2">-${discount}%</span>
        `;
    } else {
        priceHtml = `<span class="h2 text-warning">Rs ${parseFloat(product.price).toFixed(2)}</span>`;
    }
    
    // Thumbnails HTML - FIXED: Show all images properly
    let thumbnailsHtml = '';
    if(images.length > 1) {
        thumbnailsHtml = '<div class="quick-view-thumbnails mt-3" style="display: flex; gap: 10px; flex-wrap: wrap;">';
        images.forEach(function(img, idx) {
            thumbnailsHtml += `<img src="${img}" class="quick-view-thumb ${idx === 0 ? 'active' : ''}" 
                style="width: 70px; height: 70px; object-fit: cover; border-radius: 10px; cursor: pointer; border: 2px solid ${idx === 0 ? '#FFD700' : '#444'}; transition: all 0.3s;"
                data-index="${idx}" 
                onclick="changeQuickViewImage(${idx})"
                onmouseenter="this.style.transform='translateY(-3px)'; this.style.borderColor='#FFD700';"
                onmouseleave="this.style.transform='none'; this.style.borderColor='${idx === 0 ? '#FFD700' : '#444'}';">`;
        });
        thumbnailsHtml += '</div>';
    }
    
    var modalContent = `
        <div class="row">
            <div class="col-md-5">
                <div class="quick-view-image-container" style="position: relative; overflow: hidden; border-radius: 15px; cursor: zoom-in;">
                    <img src="${images[0]}" id="quickMainImage" class="quick-view-main-img img-fluid rounded" style="width: 100%; transition: transform 0.5s ease, opacity 0.3s ease;">
                </div>
                ${thumbnailsHtml}
                ${images.length > 1 ? '<small class="text-muted mt-2 d-block text-center"><i class="fas fa-arrows-alt"></i> Click thumbnails to change image</small>' : ''}
            </div>
            <div class="col-md-7">
                <h3 style="color:#FFD700;">${product.name}</h3>
                <div class="mb-2">${priceHtml}</div>
                <div class="mb-3">
                    <span class="badge bg-${product.stock > 0 ? 'success' : 'danger'}">
                        ${product.stock > 0 ? 'In Stock (' + product.stock + ')' : 'Out of Stock'}
                    </span>
                </div>
                <p class="text-white-50">${product.description || 'No description available'}</p>
                
                <div class="mb-3">
                    <label class="fw-bold" style="color:#fff;"><i class="fas fa-palette"></i> Colors:</label>
                    <div id="colorOptionsContainer" class="mt-2">${colorHtml}</div>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold" style="color:#fff;"><i class="fas fa-ruler-combined"></i> Sizes:</label>
                    <div id="sizeOptionsContainer" class="mt-2">${sizeHtml}</div>
                </div>
                
                <div class="mb-3">
                    <label class="fw-bold" style="color:#fff;"><i class="fas fa-cubes"></i> Quantity:</label>
                    <div class="d-flex align-items-center mt-2">
                        <button class="btn btn-sm btn-outline-secondary" id="qtyMinus" style="width: 35px;">-</button>
                        <input type="number" id="qtyInput" class="form-control text-center mx-2" style="width: 70px; background:#2a2a2a; color:#fff; border:1px solid #444;" value="1" min="1" max="${product.stock}">
                        <button class="btn btn-sm btn-outline-secondary" id="qtyPlus" style="width: 35px;">+</button>
                    </div>
                </div>
                
                <button class="btn btn-warning w-100 fw-bold mt-3" id="modalAddToCart" style="background:#FFD700; color:#000; font-weight:bold; padding: 12px; border-radius: 10px; transition: all 0.3s;">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
        </div>
    `;
    
    $('#quickViewBody').html(modalContent);
    
    // Add hover zoom effect
    $('.quick-view-image-container').hover(
        function() {
            $(this).find('.quick-view-main-img').css('transform', 'scale(1.1)');
        },
        function() {
            $(this).find('.quick-view-main-img').css('transform', 'scale(1)');
        }
    );
    
    // Store selected values
    let selectedColor = defaultColor;
    let selectedSize = defaultSize;
    
    // Color selection handler
    $('.color-option').click(function() {
        $('.color-option').removeClass('selected').css('border', '2px solid #fff').css('box-shadow', 'none');
        $(this).addClass('selected').css('border', '3px solid #FFD700').css('box-shadow', '0 0 15px rgba(255,215,0,0.5)');
        selectedColor = $(this).data('color');
        showToast(`✓ ${$(this).data('colorname')} selected`, 'success');
    });
    
    // Size selection handler
    $('.size-option').click(function() {
        $('.size-option').removeClass('selected').removeClass('btn-warning').addClass('btn-outline-secondary')
            .css('background', '').css('color', '').css('border-color', '').css('box-shadow', '');
        $(this).addClass('selected').addClass('btn-warning').removeClass('btn-outline-secondary');
        $(this).css('background', '#FFD700').css('color', '#000').css('border-color', '#FFD700').css('box-shadow', '0 0 10px rgba(255,215,0,0.5)');
        selectedSize = $(this).data('size');
        showToast(`✓ Size ${selectedSize} selected`, 'success');
    });
    
    // Quantity handlers
    $('#qtyMinus').click(function() {
        var qty = parseInt($('#qtyInput').val());
        if(qty > 1) $('#qtyInput').val(qty - 1);
    });
    
    $('#qtyPlus').click(function() {
        var qty = parseInt($('#qtyInput').val());
        if(qty < product.stock) $('#qtyInput').val(qty + 1);
        else showToast(`Only ${product.stock} items available`, 'warning');
    });
    
    // Add to cart from modal
    $('#modalAddToCart').click(function() {
        // Validate selections (only if more than one option exists)
        if(colors.length > 1 && !selectedColor) {
            showToast('Please select a color', 'warning');
            $('.color-option').first().css('animation', 'shake 0.5s');
            setTimeout(() => $('.color-option').css('animation', ''), 500);
            return;
        }
        if(sizes.length > 1 && !selectedSize) {
            showToast('Please select a size', 'warning');
            $('.size-option').first().css('animation', 'shake 0.5s');
            setTimeout(() => $('.size-option').css('animation', ''), 500);
            return;
        }
        
        var quantity = $('#qtyInput').val();
        var btn = $('#modalAddToCart');
        var originalText = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i> Adding...').prop('disabled', true);
        
        $.ajax({
            url: 'add-to-cart.php',
            method: 'POST',
            data: {
                product_id: product.id,
                quantity: quantity,
                color: selectedColor || (colors[0] || ''),
                size: selectedSize || (sizes[0] || '')
            },
            success: function(response) {
                if(response === 'success') {
                    showToast(`✓ ${quantity} × ${product.name} added to cart!`, 'success');
                    $('#quickViewModal').modal('hide');
                    updateCartCount();
                    
                    // Create flying animation
                    const modalBtn = document.getElementById('modalAddToCart');
                    if(modalBtn) {
                        const btnRect = modalBtn.getBoundingClientRect();
                        const cartRect = document.getElementById('floatingCartBtn')?.getBoundingClientRect();
                        if(cartRect) {
                            createGoldParticles(btnRect.left + btnRect.width/2, btnRect.top + btnRect.height/2);
                        }
                    }
                } else if(response === 'not_logged_in') {
                    showToast('Please login first', 'error');
                    setTimeout(function() { window.location.href = 'login.php'; }, 1500);
                } else {
                    showToast('Error adding to cart', 'error');
                }
                btn.html(originalText).prop('disabled', false);
            },
            error: function() {
                showToast('Something went wrong!', 'error');
                btn.html(originalText).prop('disabled', false);
            }
        });
    });
    
    // Add shake animation CSS if not exists
    if(!document.querySelector('#shakeAnimation')) {
        $('head').append(`
            <style id="shakeAnimation">
                @keyframes shake {
                    0%, 100% { transform: translateX(0); }
                    25% { transform: translateX(-5px); }
                    75% { transform: translateX(5px); }
                }
            </style>
        `);
    }
}

// ============================================
// CHANGE QUICK VIEW IMAGE WITH SMOOTH ANIMATION
// ============================================
function changeQuickViewImage(index) {
    if(index === currentImageIndex) return;
    if(!currentImages[index]) return;
    
    currentImageIndex = index;
    const $mainImage = $('#quickMainImage');
    const $thumbnails = $('.quick-view-thumb');
    
    // Fade out effect
    $mainImage.css('opacity', '0.5');
    
    // Change image after short delay
    setTimeout(() => {
        $mainImage.attr('src', currentImages[index]);
        
        // Fade back in
        setTimeout(() => {
            $mainImage.css('opacity', '1');
        }, 50);
    }, 150);
    
    // Update active thumbnail
    $thumbnails.removeClass('active').css('border-color', '#444');
    $thumbnails.eq(index).addClass('active').css('border-color', '#FFD700');
}

// ============================================
// CHANGE QUICK VIEW IMAGE WITH ANIMATION
// ============================================
function changeQuickViewImage(index) {
    if(index === currentImageIndex) return;
    
    currentImageIndex = index;
    const $mainImage = $('#quickMainImage');
    
    // Add animation class
    $mainImage.addClass('img-changing');
    
    // Change image after short delay
    setTimeout(() => {
        $mainImage.attr('src', currentImages[index]);
        
        // Remove animation class after image loads
        setTimeout(() => {
            $mainImage.removeClass('img-changing');
        }, 50);
    }, 100);
    
    // Update active thumbnail
    $('.quick-view-thumb').removeClass('active');
    $(`.quick-view-thumb[data-index="${index}"]`).addClass('active');
}

function updateCartCount() {
    $.ajax({url: 'get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
}
</script>
</body>
</html>