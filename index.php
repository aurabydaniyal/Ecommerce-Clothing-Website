<?php
session_start();

// Clear temporary data on homepage load (but keep login)
if(!isset($_SESSION['page_viewed'])) {
    unset($_SESSION['temp_message']);
    unset($_SESSION['temp_error']);
    $_SESSION['page_viewed'] = true;
}

$page_title = "Home";
require_once 'db_connection.php';

// Get products for homepage
$men_products = [];
$women_products = [];
$kids_products = [];

$sql = "SELECT * FROM products WHERE type = 'men' ORDER BY views DESC LIMIT 4";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $men_products[] = $row;
}

$sql = "SELECT * FROM products WHERE type = 'women' ORDER BY views DESC LIMIT 4";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $women_products[] = $row;
}

$sql = "SELECT * FROM products WHERE type = 'kids' ORDER BY views DESC LIMIT 4";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $kids_products[] = $row;
}

// Get slider mode
$mode_result = mysqli_query($conn, "SELECT slider_type FROM slider_settings LIMIT 1");
$slider_mode = mysqli_fetch_assoc($mode_result)['slider_type'] ?? 'image';

// Get sliders based on mode
$sliders = [];
if($slider_mode == 'image') {
    $sql = "SELECT * FROM sliders WHERE is_active = 1 AND media_type = 'image' ORDER BY order_position";
} else {
    $sql = "SELECT * FROM sliders WHERE is_active = 1 AND media_type = 'video' ORDER BY order_position";
}
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $sliders[] = $row;
}

// Get sale products for popup
$sale_products = [];
$sql = "SELECT * FROM products WHERE type = 'sale' AND sale_price IS NOT NULL AND sale_price < price ORDER BY RAND() LIMIT 6";
$result = mysqli_query($conn, $sql);
while($row = mysqli_fetch_assoc($result)) {
    $sale_products[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>UHD-Wears - Premium Clothing Brand</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
    <style>
        /* ============================================
           TOP MARQUEE - Transparent, ON TOP OF SLIDES
           ============================================ */
        .top-marquee {
    position: absolute;
    top: 80px;
    left: 0;
    right: 0;
    background: #000;
    color: #FFD700;
    padding: 10px 0;
    overflow: hidden;
    white-space: nowrap;
    font-size: 14px;
    font-weight: 600;
    letter-spacing: 0.5px;
    z-index: 100;
    pointer-events: none;
    border-top: 1px solid rgba(255,215,0,0.3);
    border-bottom: 1px solid rgba(255,215,0,0.3);
}

.top-marquee-content {
    display: inline-block;
    animation: simpleMarquee 25s linear infinite;
}

.top-marquee-content span {
    display: inline-block;
    padding: 0 35px;
    color: #FFD700;
    font-weight: 700;
    text-shadow: 0 0 5px rgba(255,215,0,0.3);
}

.top-marquee-content i {
    margin-right: 8px;
    color: #FFD700;
}
        
        @keyframes simpleMarquee {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        /* ============================================
           HERO SLIDER (Supports both Images & Videos)
           ============================================ */
        .hero-slider {
            position: relative;
            height: 100vh;
            overflow: hidden;
        }
        
        .carousel-item {
            height: 100vh;
            position: relative;
        }
        
        .carousel-item img,
        .carousel-item video {
            height: 100%;
            width: 100%;
            object-fit: cover;
            filter: brightness(0.6);
        }
        
        .carousel-caption {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            bottom: auto;
            left: 10%;
            right: auto;
            text-align: left;
            background: linear-gradient(90deg, rgba(0,0,0,0.7), transparent);
            padding: 40px;
            border-radius: 20px;
            max-width: 600px;
            z-index: 10;
        }
        
        .carousel-caption h2 {
            font-size: 48px;
            font-weight: 800;
            margin-bottom: 20px;
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255,215,0,0.5);
        }
        
        .carousel-caption p {
            font-size: 18px;
            margin-bottom: 25px;
            color: #fff;
            opacity: 0.9;
        }
        
        .carousel-caption .btn-hero {
            background: #FFD700;
            color: #000;
            padding: 12px 35px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .carousel-caption .btn-hero:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 25px rgba(255,215,0,0.3);
        }
        
        /* Typing effect for slider text - cursor hidden when done */
        .typed-slider-text {
            display: inline-block;
        }
        
        .typed-cursor-slider {
            display: inline-block;
            width: 3px;
            height: 48px;
            background-color: #FFD700;
            margin-left: 5px;
            animation: blink 0.7s infinite;
            vertical-align: middle;
        }
        
        .typed-cursor-slider.hidden {
            display: none;
        }
        
        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0; }
        }
        
        /* ============================================
           CATEGORY CARDS - BLUR/FADE ON EDGES (SELF ANIMATION)
           ============================================ */
        .categories-slider {
            overflow: hidden;
            position: relative;
            padding: 20px 0;
        }
        
        .categories-track {
            display: flex;
            gap: 30px;
            animation: slowSlide 35s linear infinite;
            width: fit-content;
        }
        
        .categories-track:hover {
            animation-play-state: paused;
        }
        
        @keyframes slowSlide {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .category-slide-card {
            min-width: 300px;
            background: var(--gray);
            border-radius: 20px;
            overflow: hidden;
            cursor: pointer;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            position: relative;
            /* Blur/Fade effect on edges - applied to the card itself */
            filter: blur(0px);
            transition: filter 0.3s ease;
        }
        
        /* Cards at the edges get blurred - using CSS only */
        .category-slide-card:first-child,
        .category-slide-card:last-child {
            filter: blur(3px);
            opacity: 0.7;
        }
        
        .category-slide-card:hover {
            transform: translateY(-10px) scale(1.03);
            box-shadow: 0 20px 40px rgba(255,215,0,0.2);
            border: 1px solid rgba(255,215,0,0.5);
            filter: blur(0px) !important;
            opacity: 1 !important;
        }
        
        .category-slide-card img {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .category-slide-card:hover img {
            transform: scale(1.05);
        }
        
        .category-img-overlay {
            position: absolute;
            bottom: 0;
            left: 0;
            right: 0;
            background: linear-gradient(transparent, rgba(0,0,0,0.85));
            padding: 25px;
            text-align: center;
        }
        
        .category-img-overlay h3 {
            color: #FFD700;
            margin-bottom: 5px;
            font-size: 22px;
            font-weight: bold;
        }
        
        .category-img-overlay p {
            color: rgba(255,255,255,0.85);
            font-size: 13px;
            margin: 0;
        }
        
        /* ============================================
           SALES POPUP STYLES
           ============================================ */
        .sales-popup {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 380px;
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.5);
            z-index: 9998;
            transform: translateX(120%);
            transition: transform 0.5s cubic-bezier(0.68, -0.55, 0.265, 1.55);
            border: 1px solid rgba(255,215,0,0.3);
            overflow: hidden;
            cursor: pointer;
        }
        
        .sales-popup.show {
            transform: translateX(0);
        }
        
        .popup-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            padding: 12px 15px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            cursor: move;
        }
        
        .popup-header h4 {
            margin: 0;
            color: #000;
            font-size: 16px;
            font-weight: bold;
        }
        
        .popup-close {
            background: none;
            border: none;
            color: #000;
            font-size: 20px;
            cursor: pointer;
            transition: transform 0.3s;
        }
        
        .popup-close:hover {
            transform: scale(1.2);
        }
        
        .marquee-container {
            background: rgba(0,0,0,0.6);
            padding: 10px;
            overflow: hidden;
            white-space: nowrap;
        }
        
        .marquee-text {
            display: inline-block;
            animation: marqueeScroll 5s linear infinite;
            color: #FFD700;
            font-weight: bold;
            font-size: 14px;
        }
        
        @keyframes marqueeScroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        
        .popup-products {
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
        }
        
        .popup-products::-webkit-scrollbar {
            width: 5px;
        }
        
        .popup-products::-webkit-scrollbar-track {
            background: #2a2a2a;
            border-radius: 10px;
        }
        
        .popup-products::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 10px;
        }
        
        .popup-product-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px;
            background: #1a1a1a;
            border-radius: 12px;
            margin-bottom: 10px;
            transition: all 0.3s;
            border: 1px solid transparent;
        }
        
        .popup-product-item:hover {
            border-color: #FFD700;
            transform: translateX(5px);
            background: #2a2a2a;
        }
        
        .popup-product-img {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 10px;
        }
        
        .popup-product-info {
            flex: 1;
        }
        
        .popup-product-name {
            font-size: 13px;
            font-weight: 600;
            color: #fff;
            margin-bottom: 3px;
        }
        
        .popup-product-price {
            font-size: 14px;
            font-weight: bold;
            color: #FFD700;
        }
        
        .popup-product-old-price {
            font-size: 11px;
            text-decoration: line-through;
            color: #888;
            margin-left: 5px;
        }
        
        .popup-badge {
            background: #FFD700;
            color: #000;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: bold;
        }
        
        .popup-footer {
            padding: 12px 15px;
            background: rgba(0,0,0,0.5);
            text-align: center;
            border-top: 1px solid #2a2a2a;
        }
        
        .popup-footer p {
            margin: 0;
            color: #aaa;
            font-size: 12px;
        }
        
        .popup-footer p i {
            color: #FFD700;
        }
        
        @media (max-width: 576px) {
            .sales-popup {
                width: 320px;
                bottom: 80px;
                right: 20px;
            }
            .popup-products {
                max-height: 250px;
            }
        }
        
        /* Pulse animation for popup */
        @keyframes pulseGlow {
            0% { box-shadow: 0 0 0 0 rgba(255,215,0,0.4); }
            70% { box-shadow: 0 0 0 10px rgba(255,215,0,0); }
            100% { box-shadow: 0 0 0 0 rgba(255,215,0,0); }
        }
        
        .sales-popup {
            animation: pulseGlow 2s infinite;
        }
        
        /* ============================================
           FLOATING CART BUTTON
           ============================================ */
        .floating-cart-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 9999;
            box-shadow: 0 5px 20px rgba(255, 215, 0, 0.4);
            transition: all 0.3s ease;
            border: none;
            animation: floatGlow 2s ease-in-out infinite;
        }
        
        .floating-cart-btn:hover {
            transform: scale(1.1);
            box-shadow: 0 8px 30px rgba(255, 215, 0, 0.7);
        }
        
        @keyframes floatGlow {
            0%, 100% { box-shadow: 0 5px 20px rgba(255, 215, 0, 0.3); }
            50% { box-shadow: 0 5px 30px rgba(255, 215, 0, 0.6); }
        }
        
        .floating-cart-btn i {
            font-size: 28px;
            color: #000;
        }
        
        .floating-cart-badge {
            position: absolute;
            top: -5px;
            right: -5px;
            background: #ff4757;
            color: white;
            width: 22px;
            height: 22px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 11px;
            font-weight: bold;
            display: none;
            animation: bounce 0.5s ease;
        }
        
        @keyframes bounce {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.2); }
        }
        
        /* ============================================
           ENHANCED QUICK VIEW MODAL
           ============================================ */
        .quick-view-modal .modal-content {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border: 2px solid #FFD700;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(255, 215, 0, 0.4), 0 0 40px rgba(255, 215, 0, 0.2);
            animation: modalNeonPulse 2s ease-in-out infinite;
        }
        
        @keyframes modalNeonPulse {
            0%, 100% { box-shadow: 0 0 15px rgba(255, 215, 0, 0.3), 0 0 30px rgba(255, 215, 0, 0.15); }
            50% { box-shadow: 0 0 25px rgba(255, 215, 0, 0.5), 0 0 50px rgba(255, 215, 0, 0.25); }
        }
        
        .quick-view-image-container {
            position: relative;
            overflow: hidden;
            border-radius: 15px;
            cursor: zoom-in;
        }
        
        .quick-view-main-img {
            width: 100%;
            transition: transform 0.5s ease, opacity 0.3s ease;
            border-radius: 15px;
        }
        
        .quick-view-image-container:hover .quick-view-main-img {
            transform: scale(1.1);
        }
        
        .img-changing {
            opacity: 0.5;
            transform: scale(0.95);
        }
        
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
            transition: all 0.3s;
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
        
        .color-option {
            transition: all 0.3s;
        }
        
        .color-option:hover {
            transform: scale(1.1);
        }
        
        .color-option.selected {
            border: 3px solid #FFD700 !important;
            box-shadow: 0 0 10px rgba(255,215,0,0.5);
        }
        
        .size-option.selected {
            background: #FFD700 !important;
            color: #000 !important;
            border-color: #FFD700 !important;
            box-shadow: 0 0 8px rgba(255,215,0,0.5);
        }
        
        /* Gold Particle Effect */
        .gold-particle {
            position: fixed;
            width: 6px;
            height: 6px;
            background: #FFD700;
            border-radius: 50%;
            pointer-events: none;
            z-index: 10000;
            animation: particleExplode 0.8s ease-out forwards;
            box-shadow: 0 0 5px #FFD700;
        }
        
        @keyframes particleExplode {
            0% {
                transform: scale(1);
                opacity: 1;
            }
            100% {
                transform: scale(0);
                opacity: 0;
            }
        }
        
        /* Product Cards */
        .product-card {
            background: var(--gray);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            height: 100%;
        }
        
        .product-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0,0,0,0.4);
        }
        
        .product-img-wrapper {
            position: relative;
            overflow: hidden;
            height: 280px;
        }
        
        .product-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.5s;
        }
        
        .product-card:hover .product-img {
            transform: scale(1.05);
        }
        
        .product-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            opacity: 0;
            transition: all 0.4s ease;
        }
        
        .product-img-wrapper:hover .product-overlay {
            opacity: 1;
        }
        
        .quick-view-btn {
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
        
        .product-img-wrapper:hover .quick-view-btn {
            transform: translateY(0);
        }
        
        .quick-view-btn:hover {
            transform: scale(1.05);
            background: #FFD700;
        }
        
        .product-info {
            padding: 20px;
        }
        
        .product-title {
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 10px;
            color: #fff;
        }
        
        .product-price {
            font-size: 20px;
            font-weight: bold;
            color: #FFD700;
        }
        
        .product-old-price {
            text-decoration: line-through;
            color: #aaa;
            font-size: 14px;
            margin-left: 8px;
        }
        
        .product-buttons {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        
        .btn-cart {
            background: #FFD700;
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
            background: #E6BE00;
            transform: translateY(-2px);
        }
        
        .btn-wishlist {
            background: transparent;
            border: 2px solid #FFD700;
            color: #FFD700;
            padding: 10px 15px;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-wishlist:hover {
            background: #FFD700;
            color: #000;
            transform: translateY(-2px);
            animation: heartbeat 0.5s ease-in-out;
        }
        
        @keyframes heartbeat {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        
        .feature-card {
            transition: all 0.3s;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        
        .review-card {
    background: var(--dark-gray);
    padding: 25px;
    border-radius: 15px;
    transition: all 0.3s;
    height: 100%;
    box-shadow: 0 5px 20px rgba(255,215,0,0.15);
    border: 1px solid rgba(255,215,0,0.1);
}

.review-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 30px rgba(255,215,0,0.25);
    border-color: rgba(255,215,0,0.3);
}

.review-card i {
    font-size: 30px;
    color: #FFD700;
    margin-bottom: 15px;
}

.review-card p {
    color: #fff;
    line-height: 1.6;
}

.review-card h5 {
    color: #FFD700;
    margin-top: 15px;
}
        
        @media (max-width: 768px) {
            .floating-cart-btn {
                width: 50px;
                height: 50px;
                bottom: 20px;
                right: 20px;
            }
            .floating-cart-btn i {
                font-size: 24px;
            }
            .category-slide-card {
                min-width: 250px;
            }
            .category-slide-card img {
                height: 220px;
            }
            .carousel-caption h2 {
                font-size: 28px;
            }
            .carousel-caption {
                left: 5%;
                right: 5%;
                padding: 20px;
                max-width: 90%;
            }
            .top-marquee {
                top: 70px;
            }
            .top-marquee-content span {
                padding: 3px 18px;
                font-size: 11px;
            }
        }
        
        @media (max-width: 480px) {
            .category-slide-card {
                min-width: 220px;
            }
            .category-slide-card img {
                height: 200px;
            }
        }
    </style>
</head>
<body>

<!-- TOP MARQUEE - Transparent, ON TOP OF SLIDES -->
<div class="top-marquee">
    <div class="top-marquee-content">
        <span><i class="fas fa-star"></i> PREMIUM QUALITY</span>
        <span><i class="fas fa-truck"></i> FAST DELIVERY</span>
        <span><i class="fas fa-shield-alt"></i> SECURE PAYMENT</span>
        <span><i class="fas fa-undo"></i> EASY RETURNS</span>
        <span><i class="fas fa-tag"></i> BEST PRICES</span>
        <span><i class="fas fa-gem"></i> LUXURY FABRICS</span>
        <span><i class="fas fa-star"></i> PREMIUM QUALITY</span>
        <span><i class="fas fa-truck"></i> FAST DELIVERY</span>
        <span><i class="fas fa-shield-alt"></i> SECURE PAYMENT</span>
        <span><i class="fas fa-undo"></i> EASY RETURNS</span>
        <span><i class="fas fa-tag"></i> BEST PRICES</span>
        <span><i class="fas fa-gem"></i> LUXURY FABRICS</span>
    </div>
</div>

<div class="animated-bg"></div>
<div class="particles" id="particles"></div>

<!-- Loading Page Animation -->
<div class="loader-wrapper" id="loaderWrapper">
    <div class="loader">
        <div class="loader-circle"></div>
        <div class="loader-text">UHD-WEARS</div>
        <div class="loader-sub">Premium Fashion Destination</div>
    </div>
</div>

<!-- Spinner Overlay for AJAX -->
<div class="spinner-overlay" id="spinnerOverlay">
    <div class="spinner"></div>
</div>

<!-- Navbar -->
<?php include 'includes/nav-menu.php'; ?>

<!-- Hero Slider - Supports both Images & Videos -->
<div id="heroSlider" class="carousel slide hero-slider" data-bs-ride="carousel">
    <div class="carousel-indicators">
        <?php foreach($sliders as $index => $slider): ?>
        <button type="button" data-bs-target="#heroSlider" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index == 0 ? 'active' : ''; ?>"></button>
        <?php endforeach; ?>
    </div>
    <div class="carousel-inner">
        <?php foreach($sliders as $index => $slider): ?>
        <div class="carousel-item <?php echo $index == 0 ? 'active' : ''; ?>">
            <?php
            $media_src = '';
            $is_video = ($slider['media_type'] == 'video');
            
            if($is_video && !empty($slider['video_url'])) {
                $media_src = $slider['video_url'];
            } elseif(!$is_video && !empty($slider['image_path'])) {
                $media_src = $slider['image_path'];
            } elseif($slider['image_url']) {
                $media_src = $slider['image_url'];
            } else {
                $media_src = 'https://via.placeholder.com/1920x1080?text=UHD-Wears';
            }
            ?>
            <?php if($is_video): ?>
                <video class="d-block w-100" autoplay muted loop playsinline>
                    <source src="<?php echo $media_src; ?>" type="video/mp4">
                    Your browser does not support the video tag.
                </video>
            <?php else: ?>
                <img src="<?php echo $media_src; ?>" class="d-block w-100" alt="<?php echo htmlspecialchars($slider['title']); ?>">
            <?php endif; ?>
            <div class="carousel-caption">
                <h2 class="slider-heading" data-text="<?php echo htmlspecialchars($slider['title']); ?>">
                    <span class="typed-slider-text"></span><span class="typed-cursor-slider"></span>
                </h2>
                <p><?php echo htmlspecialchars($slider['subtitle']); ?></p>
                <a href="<?php echo $slider['button_link']; ?>" class="btn-hero"><?php echo $slider['button_text']; ?> <i class="fas fa-arrow-right"></i></a>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#heroSlider" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#heroSlider" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
    </button>
</div>

<!-- Features Bar -->
<div class="container mt-5">
    <div class="row text-center g-3">
        <div class="col-md-3 col-6">
            <div class="p-3 feature-card" style="background: var(--gray); border-radius: 15px;">
                <i class="fas fa-check-circle" style="font-size: 40px; color: var(--primary);"></i>
                <h5 class="mt-2">Trusted Shipping</h5>
                <small class="text-muted">Reliable delivery</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 feature-card" style="background: var(--gray); border-radius: 15px;">
                <i class="fas fa-undo" style="font-size: 40px; color: var(--primary);"></i>
                <h5 class="mt-2">30-Day Returns</h5>
                <small class="text-muted">Easy returns</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 feature-card" style="background: var(--gray); border-radius: 15px;">
                <i class="fas fa-shield-alt" style="font-size: 40px; color: var(--primary);"></i>
                <h5 class="mt-2">Secure Payment</h5>
                <small class="text-muted">100% secure</small>
            </div>
        </div>
        <div class="col-md-3 col-6">
            <div class="p-3 feature-card" style="background: var(--gray); border-radius: 15px;">
                <i class="fas fa-headset" style="font-size: 40px; color: var(--primary);"></i>
                <h5 class="mt-2">24/7 Support</h5>
                <small class="text-muted">Dedicated team</small>
            </div>
        </div>
    </div>
</div>

<!-- Categories Section - Blur/Fade on Edge Cards -->
<div class="container mt-5">
    <h2 class="text-center mb-4" style="color: var(--primary); position: relative;">
        Shop by Category
        <span style="display: block; width: 60px; height: 3px; background: var(--primary); margin: 10px auto 0;"></span>
    </h2>
    
    <div class="categories-slider">
        <div class="categories-track">
            <!-- First set -->
            <div class="category-slide-card" onclick="location.href='men.php'">
                <img src="uploads/category/dddww.jpg" alt="Men">
                <div class="category-img-overlay">
                    <h3>Men's Fashion</h3>
                    <p>Premium Collection</p>
                </div>
            </div>
            <div class="category-slide-card" onclick="location.href='women.php'">
                <img src="uploads/category/designerlehanga.jpg" alt="Women">
                <div class="category-img-overlay">
                    <h3>Women's Fashion</h3>
                    <p>Elegant Styles</p>
                </div>
            </div>
            <div class="category-slide-card" onclick="location.href='kids.php'">
                <img src="uploads/category/kids.jpeg" alt="Kids">
                <div class="category-img-overlay">
                    <h3>Kid's Fashion</h3>
                    <p>Adorable Outfits</p>
                </div>
            </div>
            <div class="category-slide-card" onclick="location.href='sale.php'">
                <img src="uploads/category/sale.jpeg" alt="Sale">
                <div class="category-img-overlay">
                    <h3>Sale</h3>
                    <p>Up to 50% OFF</p>
                </div>
            </div>
            <!-- Duplicate set for seamless loop -->
            <div class="category-slide-card" onclick="location.href='men.php'">
                <img src="uploads/category/dddww.jpg" alt="Men">
                <div class="category-img-overlay">
                    <h3>Men's Fashion</h3>
                    <p>Premium Collection</p>
                </div>
            </div>
            <div class="category-slide-card" onclick="location.href='women.php'">
                <img src="uploads/category/designerlehanga.jpg" alt="Women">
                <div class="category-img-overlay">
                    <h3>Women's Fashion</h3>
                    <p>Elegant Styles</p>
                </div>
            </div>
            <div class="category-slide-card" onclick="location.href='kids.php'">
                <img src="uploads/category/kids.jpeg" alt="Kids">
                <div class="category-img-overlay">
                    <h3>Kid's Fashion</h3>
                    <p>Adorable Outfits</p>
                </div>
            </div>
            <div class="category-slide-card" onclick="location.href='sale.php'">
                <img src="uploads/category/sale.jpeg" alt="Sale">
                <div class="category-img-overlay">
                    <h3>Sale</h3>
                    <p>Up to 50% OFF</p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Men's Collection -->
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 style="color: var(--primary);">Men's Collection</h2>
        <a href="men.php" class="btn btn-outline-custom" style="color: var(--primary); border: 1px solid var(--primary); padding: 8px 20px; border-radius: 30px; text-decoration: none;">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php foreach($men_products as $product): 
            $is_on_sale = ($product['sale_price'] && $product['sale_price'] < $product['price']);
            $discount = $is_on_sale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="product-card">
                <div class="product-img-wrapper">
                    <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                    <div class="product-overlay">
                        <button class="quick-view-btn" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-eye"></i> Quick View
                        </button>
                    </div>
                    <?php if($is_on_sale): ?>
                        <span class="sale-badge" style="position: absolute; top: 10px; left: 10px; background: #FFD700; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold;">-<?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <div>
                        <?php if($is_on_sale): ?>
                            <span class="product-price">Rs <?php echo $product['sale_price']; ?></span>
                            <span class="product-old-price">$<?php echo $product['price']; ?></span>
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
        <?php endforeach; ?>
    </div>
</div>

<!-- Women's Collection -->
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 style="color: var(--primary);">Women's Collection</h2>
        <a href="women.php" class="btn btn-outline-custom" style="color: var(--primary); border: 1px solid var(--primary); padding: 8px 20px; border-radius: 30px; text-decoration: none;">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php foreach($women_products as $product): 
            $is_on_sale = ($product['sale_price'] && $product['sale_price'] < $product['price']);
            $discount = $is_on_sale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="product-card">
                <div class="product-img-wrapper">
                    <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                    <div class="product-overlay">
                        <button class="quick-view-btn" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-eye"></i> Quick View
                        </button>
                    </div>
                    <?php if($is_on_sale): ?>
                        <span class="sale-badge" style="position: absolute; top: 10px; left: 10px; background: #FFD700; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold;">-<?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <div>
                        <?php if($is_on_sale): ?>
                            <span class="product-price">Rs <?php echo $product['sale_price']; ?></span>
                            <span class="product-old-price">$<?php echo $product['price']; ?></span>
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
        <?php endforeach; ?>
    </div>
</div>

<!-- Kids Collection -->
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
        <h2 style="color: var(--primary);">Kid's Collection</h2>
        <a href="kids.php" class="btn btn-outline-custom" style="color: var(--primary); border: 1px solid var(--primary); padding: 8px 20px; border-radius: 30px; text-decoration: none;">View All <i class="fas fa-arrow-right"></i></a>
    </div>
    <div class="row g-4">
        <?php foreach($kids_products as $product): 
            $is_on_sale = ($product['sale_price'] && $product['sale_price'] < $product['price']);
            $discount = $is_on_sale ? round((($product['price'] - $product['sale_price']) / $product['price']) * 100) : 0;
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="product-card">
                <div class="product-img-wrapper">
                    <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>" class="product-img" alt="<?php echo $product['name']; ?>">
                    <div class="product-overlay">
                        <button class="quick-view-btn" data-id="<?php echo $product['id']; ?>">
                            <i class="fas fa-eye"></i> Quick View
                        </button>
                    </div>
                    <?php if($is_on_sale): ?>
                        <span class="sale-badge" style="position: absolute; top: 10px; left: 10px; background: #FFD700; color: #000; padding: 5px 10px; border-radius: 5px; font-size: 12px; font-weight: bold;">-<?php echo $discount; ?>% OFF</span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                    <div>
                        <?php if($is_on_sale): ?>
                            <span class="product-price">Rs <?php echo $product['sale_price']; ?></span>
                            <span class="product-old-price">$<?php echo $product['price']; ?></span>
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
        <?php endforeach; ?>
    </div>
</div>

<!-- Newsletter Section -->
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center p-5" style="background: var(--gray); border-radius: 20px;">
            <h3 style="color: var(--primary);">Subscribe to Our Newsletter</h3>
            <p>Get the latest updates on new arrivals and exclusive offers!</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <input type="email" id="newsletterEmail" class="form-control" style="max-width: 300px; background: var(--light-gray); border: none; color: #fff;" placeholder="Enter your email">
                <button class="btn btn-primary-custom" id="subscribeBtn" style="background: #FFD700; color: #000; border: none; padding: 10px 25px; border-radius: 30px; font-weight: 600;">Subscribe</button>
            </div>
        </div>
    </div>
</div>

<!-- Customer Reviews -->
<div class="container mt-5">
    <h2 class="text-center mb-4" style="color: var(--primary);">What Our Customers Say</h2>
    <div class="row g-4">
        <div class="col-md-4">
            <div class="review-card">
                <i class="fas fa-quote-left"></i>
                <p>Amazing quality products! The fabric is premium and fits perfectly. Highly recommend UHD-Wears!</p>
                <h5>- Hassan Abdullah</h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="review-card">
                <i class="fas fa-quote-left"></i>
                <p>Fast delivery and great customer service. Will definitely shop again! Best online store ever.</p>
                <h5>- Sarah Amir</h5>
            </div>
        </div>
        <div class="col-md-4">
            <div class="review-card">
                <i class="fas fa-quote-left"></i>
                <p>Best online shopping experience! The kids collection is adorable. Quality is outstanding!</p>
                <h5>- Ali Haider</h5>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-4 mb-4">
                <h4>About UHD-Wears</h4>
                <p>Premium clothing brand offering the latest fashion for men, women, and kids. Quality and style guaranteed since 2024.</p>
                <div class="social-links mt-3">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                    <a href="#"><i class="fab fa-youtube"></i></a>
                </div>
            </div>
            <div class="col-md-4 mb-4">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="men.php">Men</a></li>
                    <li><a href="women.php">Women</a></li>
                    <li><a href="kids.php">Kids</a></li>
                    <li><a href="sale.php">Sale</a></li>
                </ul>
            </div>
            <div class="col-md-4 mb-4">
                <h4>Contact Info</h4>
                <p><i class="fas fa-map-marker-alt"></i> Gulberg, Lahore, Pakistan</p>
                <p><i class="fas fa-phone"></i> +92 3XX XXXXXXX</p>
                <p><i class="fas fa-envelope"></i> info@uhdwears.com</p>
                <p><i class="fas fa-clock"></i> Mon-Sat: 10AM - 8PM</p>
            </div>
        </div>
        <div class="text-center mt-4 pt-3 border-top border-secondary">
            <p>&copy; 2024 UHD-Wears. All rights reserved. | Designed with <i class="fas fa-heart" style="color: var(--primary);"></i> for fashion lovers</p>
        </div>
    </div>
</footer>

<!-- FLOATING CART BUTTON -->
<button class="floating-cart-btn" id="floatingCartBtn" onclick="window.location.href='cart.php'">
    <i class="fas fa-shopping-cart"></i>
    <span class="floating-cart-badge" id="floatingCartBadge">0</span>
</button>

<!-- SALES POPUP -->
<div class="sales-popup" id="salesPopup">
    <div class="popup-header">
        <h4><i class="fas fa-fire"></i> 🔥 LIMITED TIME SALE! 🔥</h4>
        <button class="popup-close" id="closePopup">&times;</button>
    </div>
    <div class="marquee-container">
        <div class="marquee-text">
            🔥 DON'T MISS OUT! UP TO 50% OFF ON SELECTED ITEMS! 🔥 SHOP NOW BEFORE THEY'RE GONE! 🔥
        </div>
    </div>
    <div class="popup-products">
        <?php if(count($sale_products) > 0): ?>
            <?php foreach($sale_products as $sale_product): 
                $discount = round((($sale_product['price'] - $sale_product['sale_price']) / $sale_product['price']) * 100);
            ?>
            <div class="popup-product-item" data-product-id="<?php echo $sale_product['id']; ?>" onclick="goToSalePage()">
                <img src="<?php echo !empty($sale_product['image_url']) ? $sale_product['image_url'] : 'https://via.placeholder.com/50x50?text=No+Image'; ?>" class="popup-product-img" alt="<?php echo $sale_product['name']; ?>">
                <div class="popup-product-info">
                    <div class="popup-product-name"><?php echo htmlspecialchars($sale_product['name']); ?></div>
                    <div>
                        <span class="popup-product-price">$<?php echo $sale_product['sale_price']; ?></span>
                        <span class="popup-product-old-price">$<?php echo $sale_product['price']; ?></span>
                    </div>
                </div>
                <div class="popup-badge">-<?php echo $discount; ?>%</div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="text-center" style="color: #aaa; padding: 20px;">
                <i class="fas fa-box-open fa-2x mb-2"></i>
                <p>No sale items available right now!</p>
                <p>Check back soon for amazing deals!</p>
            </div>
        <?php endif; ?>
    </div>
    <div class="popup-footer" onclick="goToSalePage()">
        <p><i class="fas fa-tag"></i> Click anywhere to shop the sale! <i class="fas fa-arrow-right"></i></p>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/toast.js"></script>
<script>
// Global variables for quick view
let currentImages = [];
let currentImageIndex = 0;

// ============================================
// TYPING EFFECT FOR SLIDER HEADINGS (Cursor hides when done)
// ============================================
let activeTypingInterval = null;
let currentTypingIndex = 0;

function startTypingForSlide(slideElement) {
    // Stop any existing typing
    if (activeTypingInterval) {
        clearInterval(activeTypingInterval);
        activeTypingInterval = null;
    }
    
    const headingContainer = slideElement.querySelector('.slider-heading');
    if (!headingContainer) return;
    
    const fullText = headingContainer.getAttribute('data-text') || '';
    const typedSpan = headingContainer.querySelector('.typed-slider-text');
    const cursorSpan = headingContainer.querySelector('.typed-cursor-slider');
    
    if (!typedSpan) return;
    
    typedSpan.textContent = '';
    // Show cursor initially
    cursorSpan.classList.remove('hidden');
    let charIndex = 0;
    
    function typeCharacter() {
        if (charIndex < fullText.length) {
            typedSpan.textContent += fullText.charAt(charIndex);
            charIndex++;
        } else {
            // Typing complete - hide cursor
            cursorSpan.classList.add('hidden');
            clearInterval(activeTypingInterval);
            activeTypingInterval = null;
        }
    }
    
    activeTypingInterval = setInterval(typeCharacter, 80);
}

// Initialize typing for active slide
function initSliderTyping() {
    const activeSlide = document.querySelector('.carousel-item.active');
    if (activeSlide) {
        startTypingForSlide(activeSlide);
    }
    
    // Listen for slide change events
    $('#heroSlider').on('slid.bs.carousel', function() {
        const newActiveSlide = document.querySelector('.carousel-item.active');
        if (newActiveSlide) {
            startTypingForSlide(newActiveSlide);
        }
    });
}

// Function to go to sale page
function goToSalePage() {
    window.location.href = 'sale.php';
}

// Function to create gold particles explosion effect
function createGoldParticles(x, y) {
    for(let i = 0; i < 30; i++) {
        const particle = document.createElement('div');
        particle.className = 'gold-particle';
        const angle = Math.random() * Math.PI * 2;
        const velocity = 2 + Math.random() * 8;
        const vx = Math.cos(angle) * velocity;
        const vy = Math.sin(angle) * velocity;
        particle.style.left = (x - 3) + 'px';
        particle.style.top = (y - 3) + 'px';
        document.body.appendChild(particle);
        
        let posX = x - 3, posY = y - 3;
        let startTime = Date.now();
        
        function animate() {
            const elapsed = (Date.now() - startTime) / 1000;
            if(elapsed > 0.8) {
                particle.remove();
                return;
            }
            posX += vx;
            posY += vy;
            particle.style.left = posX + 'px';
            particle.style.top = posY + 'px';
            particle.style.opacity = 1 - (elapsed / 0.8);
            requestAnimationFrame(animate);
        }
        animate();
    }
}

// Update floating cart badge
function updateFloatingCartBadge() {
    $.ajax({
        url: 'get-cart-count.php',
        success: function(count) {
            const badge = $('#floatingCartBadge');
            const numCount = parseInt(count);
            if(numCount > 0) {
                badge.text(numCount).show();
                badge.css('animation', 'none');
                setTimeout(() => badge.css('animation', 'bounce 0.5s ease'), 10);
            } else {
                badge.hide();
            }
        }
    });
}

// Create floating particles
function createParticles() {
    const particlesContainer = document.getElementById('particles');
    if(particlesContainer) {
        for(let i = 0; i < 50; i++) {
            const particle = document.createElement('div');
            particle.className = 'particle';
            particle.style.left = Math.random() * 100 + '%';
            particle.style.animationDuration = (Math.random() * 20 + 10) + 's';
            particle.style.animationDelay = (Math.random() * 10) + 's';
            particle.style.width = (Math.random() * 4 + 1) + 'px';
            particle.style.height = particle.style.width;
            particlesContainer.appendChild(particle);
        }
    }
}
createParticles();

$(document).ready(function() {
    // Hide loader after 2 seconds
    setTimeout(function() {
        $('#loaderWrapper').fadeOut();
    }, 2000);
    
    // Initialize slider typing effect
    setTimeout(initSliderTyping, 100);
    
    // Update floating cart badge
    updateFloatingCartBadge();
    
    // SALES POPUP - Show after 20 seconds
    setTimeout(function() {
        $('#salesPopup').addClass('show');
        setTimeout(function() {
            $('#salesPopup').removeClass('show');
        }, 5000);
    }, 30000);
    
    // Close popup when X is clicked
    $('#closePopup').click(function(e) {
        e.stopPropagation();
        $('#salesPopup').removeClass('show');
    });
    
    // Click anywhere on popup (except close button) goes to sale page
    $('.sales-popup').click(function(e) {
        if($(e.target).hasClass('popup-close') || $(e.target).closest('.popup-close').length) {
            return;
        }
        goToSalePage();
    });
    
    // Navbar scroll effect
    $(window).scroll(function() {
        if ($(window).scrollTop() > 50) {
            $('.navbar').addClass('scrolled');
        } else {
            $('.navbar').removeClass('scrolled');
        }
    });
    
    // Newsletter Subscription
    $('#subscribeBtn').click(function() {
        var email = $('#newsletterEmail').val();
        if(email === '') {
            showToast('Please enter your email address!', 'warning');
            return;
        }
        if(!email.includes('@') || !email.includes('.')) {
            showToast('Please enter a valid email address!', 'error');
            return;
        }
        showToast('Thank you for subscribing to our newsletter! 🎉', 'success');
        $('#newsletterEmail').val('');
    });
    
    // Update cart count
    function updateCartCount() {
        $.ajax({
            url: 'get-cart-count.php',
            success: function(data) {
                $('#cartCount').text(data);
                updateFloatingCartBadge();
            }
        });
    }
    
    // Update wishlist count
    function updateWishlistCount() {
        $.ajax({
            url: 'get-wishlist-count.php',
            success: function(data) {
                $('#wishlistCount').text(data);
            }
        });
    }
    
    // Add to cart
    $('.add-to-cart').click(function() {
        var productId = $(this).data('id');
        var btn = $(this);
        var originalHtml = btn.html();
        btn.html('<i class="fas fa-spinner fa-spin"></i> Adding...');
        btn.prop('disabled', true);
        
        $.ajax({
            url: 'add-to-cart.php',
            method: 'POST',
            data: {product_id: productId, quantity: 1},
            success: function(response) {
                if(response === 'success') {
                    showToast('Product added to cart successfully!', 'success');
                    btn.html('<i class="fas fa-check"></i> Added!');
                    setTimeout(function() {
                        btn.html(originalHtml);
                        btn.prop('disabled', false);
                    }, 1500);
                    updateCartCount();
                } else if(response === 'not_logged_in') {
                    showToast('Please login to add items to cart', 'error');
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 1500);
                } else if(response === 'stock_limit') {
                    showToast('Sorry, this product is out of stock!', 'error');
                    btn.html(originalHtml);
                    btn.prop('disabled', false);
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
    
    // Add to wishlist
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
                    setTimeout(function() {
                        btn.html('<i class="fas fa-heart"></i>');
                    }, 1000);
                    updateWishlistCount();
                } else if(response === 'not_logged_in') {
                    showToast('Please login to add items to wishlist', 'error');
                    setTimeout(function() {
                        window.location.href = 'login.php';
                    }, 1500);
                } else if(response === 'already_exists') {
                    showToast('Product already in wishlist!', 'warning');
                } else {
                    showToast('Error adding to wishlist', 'error');
                }
            }
        });
    });
    
    // Quick view modal with GOLD PARTICLE EFFECT
    $('.quick-view-btn').click(function(e) {
        var productId = $(this).data('id');
        const rect = e.target.getBoundingClientRect();
        createGoldParticles(rect.left + rect.width/2, rect.top + rect.height/2);
        showQuickViewModal(productId);
    });
    
    updateCartCount();
    updateWishlistCount();
});

// ============================================
// ENHANCED QUICK VIEW MODAL
// ============================================
function showQuickViewModal(productId) {
    var modalHtml = `
        <div class="modal fade quick-view-modal" id="quickViewModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="border-bottom-color: #FFD700;">
                        <h5 class="modal-title" style="color: #FFD700;"><i class="fas fa-eye"></i> Quick View</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="quickViewBody">
                        <div class="text-center py-5">
                            <div class="spinner-border text-warning"></div>
                            <p class="mt-3">Loading product details...</p>
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
                displayEnhancedQuickView(product);
                $('#quickViewModal').modal('show');
            } else {
                showToast('Product not found!', 'error');
            }
        },
        error: function() {
            showToast('Error loading product!', 'error');
        }
    });
}

function displayEnhancedQuickView(product) {
    // Build images array
    currentImages = [];
    if(product.image_url && product.image_url !== '') currentImages.push(product.image_url);
    if(product.image2 && product.image2 !== '' && product.image2 !== product.image_url) currentImages.push(product.image2);
    if(product.image3 && product.image3 !== '' && product.image3 !== product.image_url) currentImages.push(product.image3);
    if(product.images && product.images.length > 0) {
        product.images.forEach(img => {
            if(img && !currentImages.includes(img)) currentImages.push(img);
        });
    }
    if(currentImages.length === 0) currentImages.push('https://via.placeholder.com/500x500?text=No+Image');
    currentImageIndex = 0;
    
    // Parse colors and sizes
    let colors = [];
    let sizes = [];
    
    if(product.colors && product.colors.length > 0 && product.colors[0] !== '') {
        colors = typeof product.colors === 'string' ? product.colors.split(',') : product.colors;
        colors = colors.filter(c => c && c.trim() !== '');
    }
    if(product.sizes && product.sizes.length > 0 && product.sizes[0] !== '') {
        sizes = typeof product.sizes === 'string' ? product.sizes.split(',') : product.sizes;
        sizes = sizes.filter(s => s && s.trim() !== '');
    }
    
    // Auto-select if only one option
    let defaultColor = colors.length === 1 ? colors[0] : '';
    let defaultSize = sizes.length === 1 ? sizes[0] : '';
    
    // Build HTML
    let colorHtml = '';
    if(colors.length > 0) {
        colorHtml = '<div class="d-flex flex-wrap gap-2">';
        colors.forEach((color, idx) => {
            const isSelected = (defaultColor === color);
            colorHtml += `<div class="color-option ${isSelected ? 'selected' : ''}" 
                style="background-color: ${color.trim().toLowerCase()}; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 2px solid ${isSelected ? '#FFD700' : '#fff'};" 
                data-color="${color.trim()}"></div>`;
        });
        colorHtml += '</div>';
        if(colors.length > 1) colorHtml += '<small class="text-muted mt-1 d-block">Click to select a color</small>';
    } else {
        colorHtml = '<p class="text-muted">No color options</p>';
    }
    
    let sizeHtml = '';
    if(sizes.length > 0) {
        sizeHtml = '<div class="d-flex flex-wrap gap-2">';
        sizes.forEach(size => {
            const isSelected = (defaultSize === size);
            sizeHtml += `<button class="size-option btn btn-sm m-1 ${isSelected ? 'btn-warning selected' : 'btn-outline-secondary'}" 
                data-size="${size.trim()}"
                style="${isSelected ? 'background: #FFD700 !important; color: #000 !important; border-color: #FFD700 !important;' : ''}">
                ${size.trim()}
            </button>`;
        });
        sizeHtml += '</div>';
        if(sizes.length > 1) sizeHtml += '<small class="text-muted mt-1 d-block">Click to select a size</small>';
    } else {
        sizeHtml = '<p class="text-muted">No size options</p>';
    }
    
    // Price HTML
    let priceHtml = '';
    let isOnSale = (product.sale_price && parseFloat(product.sale_price) < parseFloat(product.price));
    if(isOnSale) {
        let discount = Math.round(((product.price - product.sale_price) / product.price) * 100);
        priceHtml = `
            <span class="h2 text-warning">Rs ${parseFloat(product.sale_price).toFixed(2)}</span>
            <span class="text-muted"><del>Rs ${parseFloat(product.price).toFixed(2)}</del></span>
            <span class="badge bg-danger ms-2">-${discount}%</span>
        `;
    } else {
        priceHtml = `<span class="h2 text-warning">Rs ${parseFloat(product.price).toFixed(2)}</span>`;
    }
    
    // Thumbnails
    let thumbHtml = '';
    if(currentImages.length > 1) {
        thumbHtml = '<div class="quick-view-thumbnails mt-3">';
        currentImages.forEach((img, idx) => {
            thumbHtml += `<img src="${img}" class="quick-view-thumb ${idx === 0 ? 'active' : ''}" data-index="${idx}" onclick="changeQuickViewImage(${idx})">`;
        });
        thumbHtml += '</div>';
    }
    
    let modalContent = `
        <div class="row">
            <div class="col-md-5">
                <div class="quick-view-image-container">
                    <img src="${currentImages[0]}" id="quickMainImage" class="quick-view-main-img img-fluid rounded" style="width: 100%;">
                </div>
                ${thumbHtml}
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
                
                <button class="btn btn-warning w-100 fw-bold mt-3" id="modalAddToCart" style="background:#FFD700; color:#000; font-weight:bold; padding: 12px; border-radius: 10px;">
                    <i class="fas fa-shopping-cart"></i> Add to Cart
                </button>
            </div>
        </div>
    `;
    
    $('#quickViewBody').html(modalContent);
    
    // Add zoom effect
    $('.quick-view-image-container').hover(
        function() { $(this).find('#quickMainImage').css('transform', 'scale(1.1)'); },
        function() { $(this).find('#quickMainImage').css('transform', 'scale(1)'); }
    );
    
    let selectedColor = defaultColor;
    let selectedSize = defaultSize;
    
    // Color selection
    $('.color-option').click(function() {
        $('.color-option').removeClass('selected').css('border', '2px solid #fff');
        $(this).addClass('selected').css('border', '3px solid #FFD700');
        selectedColor = $(this).data('color');
        showToast('Color selected', 'success');
    });
    
    // Size selection
    $('.size-option').click(function() {
        $('.size-option').removeClass('selected').removeClass('btn-warning').addClass('btn-outline-secondary')
            .css('background', '').css('color', '').css('border-color', '');
        $(this).addClass('selected').addClass('btn-warning').removeClass('btn-outline-secondary');
        $(this).css('background', '#FFD700').css('color', '#000').css('border-color', '#FFD700');
        selectedSize = $(this).data('size');
        showToast(`Size ${selectedSize} selected`, 'success');
    });
    
    // Quantity handlers
    $('#qtyMinus').click(function() {
        let qty = parseInt($('#qtyInput').val());
        if(qty > 1) $('#qtyInput').val(qty - 1);
    });
    $('#qtyPlus').click(function() {
        let qty = parseInt($('#qtyInput').val());
        if(qty < product.stock) $('#qtyInput').val(qty + 1);
        else showToast(`Only ${product.stock} items available`, 'warning');
    });
    
    // Add to cart
    $('#modalAddToCart').click(function() {
        if(colors.length > 1 && !selectedColor) {
            showToast('Please select a color', 'warning');
            return;
        }
        if(sizes.length > 1 && !selectedSize) {
            showToast('Please select a size', 'warning');
            return;
        }
        
        let quantity = $('#qtyInput').val();
        let btn = $('#modalAddToCart');
        let originalText = btn.html();
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
                    showToast('Product added to cart!', 'success');
                    $('#quickViewModal').modal('hide');
                    updateCartCount();
                } else if(response === 'not_logged_in') {
                    showToast('Please login first', 'error');
                    setTimeout(() => window.location.href = 'login.php', 1500);
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
}

// Change quick view image with animation
function changeQuickViewImage(index) {
    if(index === currentImageIndex || !currentImages[index]) return;
    currentImageIndex = index;
    const $mainImage = $('#quickMainImage');
    $mainImage.css('opacity', '0.5');
    setTimeout(() => {
        $mainImage.attr('src', currentImages[index]);
        setTimeout(() => $mainImage.css('opacity', '1'), 50);
    }, 150);
    $('.quick-view-thumb').removeClass('active').css('border-color', '#444');
    $(`.quick-view-thumb[data-index="${index}"]`).addClass('active').css('border-color', '#FFD700');
}

function updateCartCount() {
    $.ajax({url: 'get-cart-count.php', success: function(data) {
        $('#cartCount').text(data);
        updateFloatingCartBadge();
    }});
}
</script>
</body>
</html>