<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) { 
    header('Location: login.php'); 
    exit(); 
}

$user_id = $_SESSION['user_id'];
$cart_items = mysqli_query($conn, "SELECT cart.*, products.name, products.price, products.sale_price, products.image_path, products.image_url, products.stock FROM cart JOIN products ON cart.product_id = products.id WHERE cart.user_id = '$user_id'");
$total = 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - UHD-Wears</title>
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
        
        /* Cart Header */
        .cart-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,215,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .cart-header::before {
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
        
        .cart-header h2 {
            color: #FFD700;
            font-weight: 700;
            font-size: 28px;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .cart-header p {
            color: #aaa;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        /* Cart Items */
        .cart-item {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            transition: all 0.3s;
            border: 1px solid rgba(255,215,0,0.1);
        }
        
        .cart-item:hover {
            transform: translateX(5px);
            border-color: rgba(255,215,0,0.3);
        }
        
        .product-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 12px;
            transition: transform 0.3s;
        }
        
        .product-image:hover {
            transform: scale(1.05);
        }
        
        .product-name {
            color: #fff;
            font-weight: 600;
            margin-bottom: 5px;
            font-size: 16px;
        }
        
        .product-meta {
            color: #aaa;
            font-size: 12px;
        }
        
        .product-meta i {
            color: #FFD700;
            margin-right: 4px;
        }
        
        .quantity-wrapper {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .quantity-input {
            width: 70px;
            background: #2a2a2a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 8px;
            padding: 8px;
            text-align: center;
        }
        
        .quantity-input:focus {
            outline: none;
            border-color: #FFD700;
        }
        
        .product-price {
            color: #FFD700;
            font-size: 20px;
            font-weight: bold;
        }
        
        .remove-btn {
            background: rgba(220,53,69,0.1);
            border: 1px solid rgba(220,53,69,0.3);
            color: #dc3545;
            padding: 8px 16px;
            border-radius: 8px;
            transition: all 0.3s;
        }
        
        .remove-btn:hover {
            background: #dc3545;
            color: #fff;
        }
        
        /* Order Summary */
        .order-summary {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 15px;
            padding: 25px;
            position: sticky;
            top: 100px;
            border: 1px solid rgba(255,215,0,0.2);
        }
        
        .order-summary h4 {
            color: #FFD700;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .summary-row.total {
            border-bottom: none;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 20px;
            font-weight: 700;
            color: #FFD700;
        }
        
        .checkout-btn {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            padding: 12px;
            border-radius: 50px;
            font-weight: 700;
            width: 100%;
            margin-top: 20px;
            border: none;
            transition: all 0.3s;
        }
        
        .checkout-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        
        .clear-cart-btn {
            background: transparent;
            border: 2px solid rgba(220,53,69,0.5);
            color: #dc3545;
            padding: 10px;
            border-radius: 50px;
            font-weight: 600;
            width: 100%;
            margin-top: 12px;
            transition: all 0.3s;
        }
        
        .clear-cart-btn:hover {
            background: #dc3545;
            color: #fff;
            border-color: #dc3545;
        }
        
        /* Empty Cart */
        .empty-cart-full {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 20px;
            border: 1px solid rgba(255,215,0,0.2);
            padding: 50px 20px;
            text-align: center;
            width: 100%;
        }
        
        .empty-cart-full i {
            font-size: 60px;
            color: #FFD700;
            margin-bottom: 20px;
            animation: float 3s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-8px); }
        }
        
        .empty-cart-full h4 {
            color: #fff;
            font-size: 24px;
            margin-bottom: 10px;
        }
        
        .empty-cart-full p {
            color: #aaa;
            font-size: 14px;
            margin-bottom: 25px;
        }
        
        .shop-now-btn {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            padding: 10px 30px;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        
        .shop-now-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
            color: #000;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .marquee-content span {
                font-size: 11px;
                padding: 0 15px;
            }
            .cart-header {
                padding: 18px 20px;
            }
            .cart-header h2 {
                font-size: 22px;
            }
            .cart-item {
                text-align: center;
            }
            .product-image {
                margin-bottom: 15px;
            }
            .quantity-wrapper {
                justify-content: center;
                margin: 10px 0;
            }
            .order-summary {
                margin-top: 20px;
                position: relative;
                top: 0;
            }
            .empty-cart-full {
                padding: 35px 20px;
            }
            .empty-cart-full i {
                font-size: 45px;
            }
            .empty-cart-full h4 {
                font-size: 20px;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/nav-menu.php'; ?>

<!-- Marquee Banner -->
<div class="marquee-banner">
    <div class="marquee-content">
        <span><i class="fas fa-shopping-cart"></i> <span class="highlight">🛒 YOUR CART</span> - Complete your purchase before items sell out! <i class="fas fa-arrow-right"></i></span>
        <span><i class="fas fa-tag"></i> <span class="highlight">🔥 LIMITED TIME OFFER</span> - Checkout now and save big! <i class="fas fa-clock"></i></span>
        <span><i class="fas fa-gift"></i> <span class="highlight">🎁 SPECIAL OFFER</span> - Add more items to unlock exclusive deals! <i class="fas fa-gem"></i></span>
        <span><i class="fas fa-shopping-cart"></i> <span class="highlight">🛒 YOUR CART</span> - Complete your purchase before items sell out! <i class="fas fa-arrow-right"></i></span>
        <span><i class="fas fa-tag"></i> <span class="highlight">🔥 LIMITED TIME OFFER</span> - Checkout now and save big! <i class="fas fa-clock"></i></span>
        <span><i class="fas fa-gift"></i> <span class="highlight">🎁 SPECIAL OFFER</span> - Add more items to unlock exclusive deals! <i class="fas fa-gem"></i></span>
    </div>
</div>

<div class="container mt-4">
    <!-- Cart Header -->
    <div class="cart-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2><i class="fas fa-shopping-cart"></i> Shopping Cart</h2>
                <p>Review your items before checkout</p>
                <?php if(mysqli_num_rows($cart_items) > 0): ?>
                <div class="cart-stats" style="margin-top: 10px;">
                    <span style="background: rgba(255,215,0,0.1); padding: 5px 15px; border-radius: 50px; font-size: 13px;">
                        <i class="fas fa-box"></i> <?php echo mysqli_num_rows($cart_items); ?> item(s) in cart
                    </span>
                </div>
                <?php endif; ?>
            </div>
            <div class="mt-3 mt-sm-0">
                <i class="fas fa-lock" style="color: #FFD700; font-size: 22px;"></i>
                <small class="text-muted d-block">Secure Checkout</small>
            </div>
        </div>
    </div>
    
    <?php if(mysqli_num_rows($cart_items) == 0): ?>
        <!-- Empty Cart - Full Width -->
        <div class="empty-cart-full">
            <i class="fas fa-shopping-cart"></i>
            <h4>Your cart is empty</h4>
            <p>Looks like you haven't added anything to your cart yet</p>
            <a href="index.php" class="shop-now-btn">Shop Now</a>
        </div>
    <?php else: ?>
        <div class="row">
            <!-- Cart Items Column -->
            <div class="col-lg-8">
                <?php while($item = mysqli_fetch_assoc($cart_items)): 
                    $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
                    $item_total = $price * $item['quantity'];
                    $total += $item_total;
                ?>
                <div class="cart-item">
                    <div class="row align-items-center">
                        <div class="col-md-2 col-12 text-center text-md-start mb-3 mb-md-0">
                            <img src="<?php echo $item['image_path'] ? $item['image_path'] : $item['image_url']; ?>" class="product-image" alt="<?php echo $item['name']; ?>">
                        </div>
                        <div class="col-md-4 col-12 mb-3 mb-md-0">
                            <h5 class="product-name"><?php echo htmlspecialchars($item['name']); ?></h5>
                            <div class="product-meta">
                                <span><i class="fas fa-tag"></i> Size: <?php echo $item['size'] ?: 'One Size'; ?></span>
                                <span class="ms-2"><i class="fas fa-palette"></i> Color: <?php echo $item['color'] ?: 'Default'; ?></span>
                            </div>
                        </div>
                        <div class="col-md-2 col-12 mb-3 mb-md-0">
                            <div class="quantity-wrapper">
                                <input type="number" class="quantity-input quantity" data-id="<?php echo $item['id']; ?>" data-stock="<?php echo $item['stock']; ?>" value="<?php echo $item['quantity']; ?>" min="1" max="<?php echo $item['stock']; ?>">
                            </div>
                        </div>
                        <div class="col-md-2 col-12 mb-3 mb-md-0 text-center text-md-start">
                            <span class="product-price">Rs <?php echo number_format($item_total, 2); ?></span>
                        </div>
                        <div class="col-md-2 col-12 text-center text-md-end">
                            <button class="remove-btn remove-item" data-id="<?php echo $item['id']; ?>">
                                <i class="fas fa-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
            
            <!-- Order Summary Column -->
            <div class="col-lg-4">
                <div class="order-summary">
                    <h4><i class="fas fa-receipt"></i> Order Summary</h4>
                    
                    <div class="summary-row">
                        <span>Subtotal</span>
                        <span>Rs <?php echo number_format($total, 2); ?></span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Shipping</span>
                        <span>Rs 0.00</span>
                    </div>
                    
                    <div class="summary-row">
                        <span>Tax</span>
                        <span>Rs 0.00</span>
                    </div>
                    
                    <hr style="border-color: rgba(255,255,255,0.1); margin: 15px 0;">
                    
                    <div class="summary-row total">
                        <span>Total</span>
                        <span>Rs <?php echo number_format($total , 2); ?></span>
                    </div>
                    
                    <button class="checkout-btn" onclick="window.location.href='checkout.php'">
                        <i class="fas fa-credit-card"></i> Proceed to Checkout
                    </button>
                    
                    <button class="clear-cart-btn" id="clearCart">
                        <i class="fas fa-trash-alt"></i> Clear Cart
                    </button>
                </div>
                
                <!-- Trust Badges -->
                <div class="text-center mt-3">
                    <div class="d-flex justify-content-center gap-3">
                        <i class="fab fa-cc-visa fa-2x text-muted"></i>
                        <i class="fab fa-cc-mastercard fa-2x text-muted"></i>
                        <i class="fab fa-cc-amex fa-2x text-muted"></i>
                        <i class="fab fa-cc-paypal fa-2x text-muted"></i>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Continue Shopping Link -->
        <div class="text-center mt-4">
            <a href="index.php" style="color: #FFD700; text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Continue Shopping
            </a>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/toast.js"></script>
<script>
// Function to update cart count in navbar
function updateCartCount() {
    $.ajax({
        url: 'get-cart-count.php',
        success: function(data) {
            $('#cartCount').text(data);
        }
    });
}

// Function to update wishlist count in navbar
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
    
    // Update quantity
    $('.quantity').change(function() {
        var id = $(this).data('id');
        var qty = parseInt($(this).val());
        var maxStock = parseInt($(this).data('stock'));
        
        if(isNaN(qty) || qty < 1) {
            $(this).val(1);
            qty = 1;
        }
        
        if(qty > maxStock) { 
            showToast('Only ' + maxStock + ' items available!', 'error'); 
            $(this).val(maxStock); 
            return; 
        }
        
        var $input = $(this);
        $input.css('opacity', '0.6');
        
        $.ajax({
            url: 'update-cart.php', 
            method: 'POST', 
            data: {id: id, quantity: qty}, 
            success: function(r) { 
                $input.css('opacity', '1');
                if(r === 'success') {
                    location.reload();
                } else if(r === 'stock_limit') {
                    showToast('Stock limit exceeded!', 'error');
                } else {
                    showToast('Cart updated!', 'success');
                }
            },
            error: function() {
                $input.css('opacity', '1');
                showToast('Error updating cart', 'error');
            }
        });
    });
    
    // Remove item with SweetAlert
    $('.remove-item').click(function() {
        var itemId = $(this).data('id');
        var $cartItem = $(this).closest('.cart-item');
        
        Swal.fire({
            title: 'Remove Item?',
            text: 'Are you sure you want to remove this item from your cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, remove it!',
            background: '#1a1a1a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $cartItem.css({
                    'transform': 'translateX(100%)',
                    'opacity': '0',
                    'transition': 'all 0.3s'
                });
                
                setTimeout(() => {
                    $.ajax({
                        url: 'remove-from-cart.php', 
                        method: 'POST', 
                        data: {id: itemId}, 
                        success: function() { 
                            showToast('Item removed from cart!', 'success');
                            updateCartCount();
                            updateWishlistCount();
                            setTimeout(function() { location.reload(); }, 500);
                        }
                    });
                }, 200);
            }
        });
    });
    
    // Clear cart with SweetAlert
    $('#clearCart').click(function() {
        Swal.fire({
            title: 'Clear Cart?',
            text: 'Are you sure you want to remove all items from your cart?',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, clear it!',
            background: '#1a1a1a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                $('.cart-item').css({
                    'opacity': '0',
                    'transform': 'translateX(-100%)',
                    'transition': 'all 0.3s'
                });
                
                setTimeout(() => {
                    $.ajax({
                        url: 'clear-cart.php',
                        method: 'POST',
                        success: function(response) {
                            if(response === 'success') {
                                showToast('Cart cleared successfully!', 'success');
                                updateCartCount();
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
</script>
</body>
</html>