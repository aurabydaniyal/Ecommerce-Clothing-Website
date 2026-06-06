<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$wishlist = mysqli_query($conn, "SELECT wishlist.*, products.name, products.price, products.sale_price, products.image_url, products.image2, products.image3, products.description, products.stock, products.colors, products.sizes FROM wishlist JOIN products ON wishlist.product_id = products.id WHERE wishlist.user_id = '$user_id'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Wishlist - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        /* Marquee Banner Styles */
        .marquee-banner {
            background: linear-gradient(135deg, #1a1a1a 0%, #0a0a0a 100%);
            border-top: 1px solid rgba(255,215,0,0.3);
            border-bottom: 1px solid rgba(255,215,0,0.3);
            padding: 10px 0;
            margin-bottom: 30px;
            overflow: hidden;
            position: relative;
        }
        
        .marquee-banner::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,215,0,0.1), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { left: -100%; }
            100% { left: 100%; }
        }
        
        .marquee-content {
            display: inline-block;
            white-space: nowrap;
            animation: marqueeScroll 25s linear infinite;
        }
        
        .marquee-content span {
            display: inline-block;
            padding: 0 25px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .marquee-content i {
            color: #FFD700;
            margin-right: 6px;
        }
        
        .marquee-content .highlight {
            color: #FFD700;
            font-weight: bold;
        }
        
        @keyframes marqueeScroll {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .marquee-banner:hover .marquee-content {
            animation-play-state: paused;
        }
        
        /* Wishlist Header */
        .wishlist-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,215,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .wishlist-header::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,215,0,0.05) 0%, transparent 70%);
            animation: rotateGlow 20s linear infinite;
        }
        
        @keyframes rotateGlow {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        
        .wishlist-header h2 {
            color: #FFD700;
            font-weight: 700;
            font-size: 28px;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .wishlist-header p {
            color: #aaa;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        /* Empty Wishlist - Full Width like Header */
        .empty-wishlist-full {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 20px;
            border: 1px solid rgba(255,215,0,0.2);
            padding: 50px 20px;
            text-align: center;
            width: 100%;
            transition: all 0.3s;
        }
        
        .empty-wishlist-full:hover {
            border-color: rgba(255,215,0,0.4);
        }
        
        .empty-wishlist-full i {
            font-size: 60px;
            color: #FFD700;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .empty-wishlist-full h4 {
            color: #fff;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .empty-wishlist-full p {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 25px;
        }
        
        .shop-now-link {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .shop-now-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
            color: #000;
        }
        
        /* Product Card */
        .product-card {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 15px;
            overflow: hidden;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            height: 100%;
            border: 1px solid rgba(255,215,0,0.1);
        }
        
        .product-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.4);
            border-color: rgba(255,215,0,0.4);
        }
        
        .product-img-wrapper {
            position: relative;
            overflow: hidden;
            height: 260px;
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
            padding: 10px 22px;
            border-radius: 50px;
            font-weight: bold;
            font-size: 13px;
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
        
        .sale-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background: #FFD700;
            color: #000;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: bold;
            z-index: 10;
        }
        
        .product-info {
            padding: 15px;
        }
        
        .product-title {
            font-size: 15px;
            font-weight: 600;
            margin-bottom: 8px;
            color: #fff;
        }
        
        .product-price {
            font-size: 18px;
            font-weight: bold;
            color: #FFD700;
        }
        
        .product-old-price {
            text-decoration: line-through;
            color: #aaa;
            font-size: 13px;
            margin-left: 6px;
        }
        
        .product-buttons {
            display: flex;
            gap: 8px;
            margin-top: 12px;
        }
        
        .btn-cart {
            background: #FFD700;
            color: #000;
            border: none;
            padding: 8px 12px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s;
            flex: 1;
            cursor: pointer;
            font-size: 12px;
        }
        
        .btn-cart:hover {
            background: #E6BE00;
            transform: translateY(-2px);
        }
        
        .btn-remove-wishlist {
            background: transparent;
            border: 2px solid #dc3545;
            color: #dc3545;
            padding: 8px 12px;
            border-radius: 8px;
            transition: all 0.3s;
            cursor: pointer;
        }
        
        .btn-remove-wishlist:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
        }
        
        .clear-wishlist-btn {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .clear-wishlist-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220,53,69,0.3);
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
            0% { transform: scale(1); opacity: 1; }
            100% { transform: scale(0); opacity: 0; }
        }
        
        /* Quick View Modal */
        .quick-view-modal .modal-content {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border: 2px solid #FFD700;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(255,215,0,0.4);
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
        }
        
        .quick-view-image-container:hover .quick-view-main-img {
            transform: scale(1.1);
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
        
        .quick-view-thumb:hover,
        .quick-view-thumb.active {
            border-color: #FFD700;
            transform: translateY(-3px);
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
        }
        
        @media (max-width: 768px) {
            .product-img-wrapper {
                height: 220px;
            }
            .marquee-content span {
                font-size: 11px;
                padding: 0 15px;
            }
            .wishlist-header {
                padding: 18px 20px;
            }
            .wishlist-header h2 {
                font-size: 22px;
            }
            .quick-view-thumb {
                width: 55px;
                height: 55px;
            }
            .empty-wishlist-full {
                padding: 35px 20px;
            }
            .empty-wishlist-full i {
                font-size: 45px;
            }
            .empty-wishlist-full h4 {
                font-size: 20px;
            }
            .shop-now-link {
                padding: 8px 25px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/nav-menu.php'; ?>

<!-- Marquee Banner -->
<div class="marquee-banner">
    <div class="marquee-content">
        <span><i class="fas fa-heart"></i> <span class="highlight">❤️ YOUR WISHLIST</span> - Items you love are waiting for you! <i class="fas fa-arrow-right"></i></span>
        <span><i class="fas fa-tag"></i> <span class="highlight">🔥 LIMITED TIME OFFER</span> - Add to cart before they're gone! <i class="fas fa-clock"></i></span>
        <span><i class="fas fa-star"></i> <span class="highlight">✨ PREMIUM QUALITY</span> - 100% satisfaction guaranteed! <i class="fas fa-check-circle"></i></span>
        <span><i class="fas fa-heart"></i> <span class="highlight">❤️ YOUR WISHLIST</span> - Items you love are waiting for you! <i class="fas fa-arrow-right"></i></span>
        <span><i class="fas fa-tag"></i> <span class="highlight">🔥 LIMITED TIME OFFER</span> - Add to cart before they're gone! <i class="fas fa-clock"></i></span>
        <span><i class="fas fa-star"></i> <span class="highlight">✨ PREMIUM QUALITY</span> - 100% satisfaction guaranteed! <i class="fas fa-check-circle"></i></span>
    </div>
</div>

<div class="container mt-4">
    <!-- Wishlist Header -->
    <div class="wishlist-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2><i class="fas fa-heart"></i> My Wishlist</h2>
                <p>Your curated collection of favorite items</p>
                <?php if(mysqli_num_rows($wishlist) > 0): ?>
                <div class="wishlist-stats" style="display: flex; gap: 15px; margin-top: 10px;">
                    <span style="background: rgba(255,215,0,0.1); padding: 5px 15px; border-radius: 50px; font-size: 13px;">
                        <i class="fas fa-box"></i> <?php echo mysqli_num_rows($wishlist); ?> item(s)
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-3 mt-sm-0">
                <i class="fas fa-heart" style="color: #FFD700; font-size: 22px;"></i>
                <small class="text-muted d-block">Your favorites</small>
            </div>
        </div>
    </div>
    
    <?php if(mysqli_num_rows($wishlist) == 0): ?>
        <!-- Empty Wishlist - Full Width like Header -->
        <div class="empty-wishlist-full">
            <i class="fas fa-heart-broken"></i>
            <h4>Your wishlist is empty</h4>
            <p>Start adding items you love to your wishlist</p>
            <a href="index.php" class="shop-now-link">Shop Now</a>
        </div>
    <?php else: ?>
        <div class="row">
            <?php while($item = mysqli_fetch_assoc($wishlist)): 
                $is_on_sale = ($item['sale_price'] && $item['sale_price'] < $item['price']);
                $discount = $is_on_sale ? round((($item['price'] - $item['sale_price']) / $item['price']) * 100) : 0;
            ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="<?php echo $item['image_url']; ?>" class="product-img" alt="<?php echo $item['name']; ?>">
                        <div class="product-overlay">
                            <button class="quick-view-btn" 
                                data-id="<?php echo $item['product_id']; ?>"
                                data-name="<?php echo htmlspecialchars($item['name']); ?>"
                                data-price="<?php echo $item['price']; ?>"
                                data-sale-price="<?php echo $item['sale_price']; ?>"
                                data-image="<?php echo $item['image_url']; ?>"
                                data-image2="<?php echo $item['image2']; ?>"
                                data-image3="<?php echo $item['image3']; ?>"
                                data-description="<?php echo htmlspecialchars($item['description']); ?>"
                                data-stock="<?php echo $item['stock']; ?>"
                                data-colors="<?php echo $item['colors']; ?>"
                                data-sizes="<?php echo $item['sizes']; ?>">
                                <i class="fas fa-eye"></i> Quick View
                            </button>
                        </div>
                        <?php if($is_on_sale): ?>
                            <span class="sale-badge">-<?php echo $discount; ?>% OFF</span>
                        <?php endif; ?>
                    </div>
                    <div class="product-info">
                        <h5 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                        <div class="mb-2">
                            <?php if($is_on_sale): ?>
                                <span class="product-price">Rs <?php echo $item['sale_price']; ?></span>
                                <span class="product-old-price">Rs <?php echo $item['price']; ?></span>
                            <?php else: ?>
                                <span class="product-price">Rs <?php echo $item['price']; ?></span>
                            <?php endif; ?>
                        </div>
                        <div class="product-buttons">
                            <button class="btn-cart add-to-cart" data-id="<?php echo $item['product_id']; ?>">
                                <i class="fas fa-shopping-cart"></i> Add to Cart
                            </button>
                            <button class="btn-remove-wishlist remove-wishlist" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
        
        <div class="text-center mt-3 mb-5">
            <button class="clear-wishlist-btn" id="clearWishlist">
                <i class="fas fa-trash-alt"></i> Clear All
            </button>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/toast.js"></script>
<script>
// Global variables for quick view
let currentImages = [];
let currentImageIndex = 0;

// Function to create gold particles effect
function createGoldParticles(x, y) {
    for(let i = 0; i < 25; i++) {
        const particle = document.createElement('div');
        particle.className = 'gold-particle';
        const angle = Math.random() * Math.PI * 2;
        const velocity = 2 + Math.random() * 6;
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

// Function to update cart count
function updateCartCount() {
    $.ajax({
        url: 'get-cart-count.php',
        success: function(data) {
            $('#cartCount').text(data);
        }
    });
}

// Function to update wishlist count
function updateWishlistCount() {
    $.ajax({
        url: 'get-wishlist-count.php',
        success: function(data) {
            $('#wishlistCount').text(data);
        }
    });
}

// Show toast notification
function showToast(message, type = 'success') {
    if(typeof showToastMessage === 'function') {
        showToastMessage(message, type);
    } else {
        const toast = $(`
            <div class="toast-notification" style="position: fixed; bottom: 20px; right: 20px; background: #1a1a1a; color: #fff; padding: 12px 20px; border-radius: 10px; z-index: 9999; border-left: 4px solid ${type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107')};">
                <i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle')}" style="margin-right: 10px;"></i>
                ${message}
            </div>
        `);
        $('body').append(toast);
        setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 3000);
    }
}

$(document).ready(function() {
    // Update counts on page load
    updateCartCount();
    updateWishlistCount();
    
    // Add to cart
    $('.add-to-cart').click(function() {
        var pid = $(this).data('id');
        var btn = $(this);
        var originalHtml = btn.html();
        
        btn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
        
        $.ajax({
            url: 'add-to-cart.php', 
            method: 'POST', 
            data: {product_id: pid, quantity: 1},
            success: function(r) { 
                if(r == 'success') {
                    showToast('Added to cart!', 'success');
                    updateCartCount();
                    updateWishlistCount();
                    btn.html('<i class="fas fa-check"></i> Added!');
                    setTimeout(() => {
                        btn.html(originalHtml).prop('disabled', false);
                    }, 1500);
                } else if(r == 'not_logged_in') {
                    showToast('Please login first', 'error');
                    btn.html(originalHtml).prop('disabled', false);
                } else {
                    showToast('Error adding to cart', 'error');
                    btn.html(originalHtml).prop('disabled', false);
                }
            },
            error: function() {
                showToast('Something went wrong!', 'error');
                btn.html(originalHtml).prop('disabled', false);
            }
        });
    });
    
    // Quick View with Gold Particle Effect - USING DATA ATTRIBUTES
    $(document).on('click', '.quick-view-btn', function(e) {
        const rect = e.target.getBoundingClientRect();
        createGoldParticles(rect.left + rect.width/2, rect.top + rect.height/2);
        
        // Get product data from data attributes
        const productData = {
            id: $(this).data('id'),
            name: $(this).data('name'),
            price: $(this).data('price'),
            sale_price: $(this).data('sale-price'),
            image_url: $(this).data('image'),
            image2: $(this).data('image2'),
            image3: $(this).data('image3'),
            description: $(this).data('description'),
            stock: $(this).data('stock'),
            colors: $(this).data('colors'),
            sizes: $(this).data('sizes')
        };
        
        showCustomQuickView(productData);
    });
    
    // Remove single item from wishlist
    $('.remove-wishlist').click(function() {
        var id = $(this).data('id');
        var $card = $(this).closest('.col-lg-3');
        
        Swal.fire({
            title: 'Remove Item?',
            text: 'Remove this item from wishlist?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Yes, remove it!',
            background: '#1a1a1a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $card.css({
                    'transform': 'scale(0.8)',
                    'opacity': '0',
                    'transition': 'all 0.3s'
                });
                
                setTimeout(() => {
                    $.ajax({
                        url: 'remove-from-wishlist.php', 
                        method: 'POST', 
                        data: {id: id}, 
                        success: function() { 
                            showToast('Item removed!', 'success');
                            updateWishlistCount();
                            setTimeout(function() { location.reload(); }, 500);
                        }
                    });
                }, 200);
            }
        });
    });
    
    // Clear entire wishlist
    $('#clearWishlist').click(function() {
        Swal.fire({
            title: 'Clear Wishlist?',
            text: 'Are you sure you want to remove all items from your wishlist?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, clear it!',
            background: '#1a1a1a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.product-card').css({
                    'transform': 'scale(0.8)',
                    'opacity': '0',
                    'transition': 'all 0.3s'
                });
                
                setTimeout(() => {
                    $.ajax({
                        url: 'clear-wishlist.php',
                        method: 'POST',
                        success: function(response) {
                            if(response === 'success') {
                                showToast('Wishlist cleared!', 'success');
                                updateWishlistCount();
                                setTimeout(function() { location.reload(); }, 600);
                            }
                        }
                    });
                }, 200);
            }
        });
    });
});

// ============================================
// CUSTOM QUICK VIEW WITH ALL 3 IMAGES
// ============================================
function showCustomQuickView(product) {
    // Build images array - ALL 3 IMAGES
    let images = [];
    if(product.image_url && product.image_url !== '') images.push(product.image_url);
    if(product.image2 && product.image2 !== '' && product.image2 !== product.image_url) images.push(product.image2);
    if(product.image3 && product.image3 !== '' && product.image3 !== product.image_url) images.push(product.image3);
    if(images.length === 0) images.push('https://via.placeholder.com/500x500?text=No+Image');
    
    // Parse colors and sizes
    let colors = [];
    let sizes = [];
    
    if(product.colors && product.colors !== '') {
        colors = product.colors.split(',');
        colors = colors.filter(c => c && c.trim() !== '');
    }
    if(product.sizes && product.sizes !== '') {
        sizes = product.sizes.split(',');
        sizes = sizes.filter(s => s && s.trim() !== '');
    }
    
    // Price calculation
    let isOnSale = (product.sale_price && parseFloat(product.sale_price) < parseFloat(product.price));
    let currentPrice = isOnSale ? product.sale_price : product.price;
    let originalPrice = product.price;
    
    // Color HTML
    let colorHtml = '';
    if(colors.length > 0) {
        colorHtml = '<div class="d-flex flex-wrap gap-2">';
        colors.forEach((color, idx) => {
            colorHtml += `<div class="color-option" style="background-color: ${color.trim().toLowerCase()}; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; border: 2px solid #fff;" data-color="${color.trim()}"></div>`;
        });
        colorHtml += '</div>';
        if(colors.length > 1) colorHtml += '<small class="text-muted mt-1 d-block">Click to select a color</small>';
    } else {
        colorHtml = '<p class="text-muted">No color options</p>';
    }
    
    // Size HTML
    let sizeHtml = '';
    if(sizes.length > 0) {
        sizeHtml = '<div class="d-flex flex-wrap gap-2">';
        sizes.forEach(size => {
            sizeHtml += `<button class="size-option btn btn-sm btn-outline-secondary m-1" data-size="${size.trim()}">${size.trim()}</button>`;
        });
        sizeHtml += '</div>';
        if(sizes.length > 1) sizeHtml += '<small class="text-muted mt-1 d-block">Click to select a size</small>';
    } else {
        sizeHtml = '<p class="text-muted">No size options</p>';
    }
    
    // Price HTML
    let priceHtml = '';
    if(isOnSale) {
        let discount = Math.round(((originalPrice - currentPrice) / originalPrice) * 100);
        priceHtml = `
            <span class="h2 text-warning">Rs ${parseFloat(currentPrice).toFixed(2)}</span>
            <span class="text-muted"><del>Rs ${parseFloat(originalPrice).toFixed(2)}</del></span>
            <span class="badge bg-danger ms-2">-${discount}%</span>
        `;
    } else {
        priceHtml = `<span class="h2 text-warning">Rs ${parseFloat(currentPrice).toFixed(2)}</span>`;
    }
    
    // Thumbnails HTML for ALL images
    let thumbHtml = '';
    if(images.length > 1) {
        thumbHtml = '<div class="quick-view-thumbnails mt-3">';
        images.forEach((img, idx) => {
            thumbHtml += `<img src="${img}" class="quick-view-thumb ${idx === 0 ? 'active' : ''}" data-index="${idx}" onclick="changeQuickViewImage(${idx})">`;
        });
        thumbHtml += '</div>';
    }
    
    // Create modal HTML
    const modalHtml = `
        <div class="modal fade quick-view-modal" id="quickViewModal" tabindex="-1">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header" style="border-bottom-color: #FFD700;">
                        <h5 class="modal-title" style="color: #FFD700;"><i class="fas fa-eye"></i> Quick View</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body" id="quickViewBody">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="quick-view-image-container">
                                    <img src="${images[0]}" id="quickMainImage" class="quick-view-main-img img-fluid rounded" style="width: 100%;">
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
                    </div>
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal and add new one
    $('#quickViewModal').remove();
    $('body').append(modalHtml);
    
    // Store images globally for thumbnail switching
    window.currentImages = images;
    window.currentImageIndex = 0;
    
    // Add zoom effect
    $('.quick-view-image-container').hover(
        function() { $(this).find('#quickMainImage').css('transform', 'scale(1.1)'); },
        function() { $(this).find('#quickMainImage').css('transform', 'scale(1)'); }
    );
    
    let selectedColor = '';
    let selectedSize = '';
    
    // Color selection
    $('.color-option').click(function() {
        $('.color-option').removeClass('selected').css('border', '2px solid #fff');
        $(this).addClass('selected').css('border', '3px solid #FFD700');
        selectedColor = $(this).data('color');
        showToast('Color selected', 'success');
    });
    
    // Size selection
    $('.size-option').click(function() {
        $('.size-option').removeClass('selected').removeClass('btn-warning').addClass('btn-outline-secondary');
        $(this).addClass('selected btn-warning').removeClass('btn-outline-secondary');
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
    
    // Add to cart from modal
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
    
    // Show the modal
    $('#quickViewModal').modal('show');
}

// Change quick view image with animation
function changeQuickViewImage(index) {
    if(index === window.currentImageIndex || !window.currentImages[index]) return;
    window.currentImageIndex = index;
    const $mainImage = $('#quickMainImage');
    $mainImage.css('opacity', '0.5');
    setTimeout(() => {
        $mainImage.attr('src', window.currentImages[index]);
        setTimeout(() => $mainImage.css('opacity', '1'), 50);
    }, 150);
    $('.quick-view-thumb').removeClass('active').css('border-color', '#444');
    $(`.quick-view-thumb[data-index="${index}"]`).addClass('active').css('border-color', '#FFD700');
}
</script>
</body>
</html>