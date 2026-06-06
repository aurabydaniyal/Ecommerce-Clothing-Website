<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'"));
$cart_items = mysqli_query($conn, "SELECT cart.*, products.name, products.price, products.sale_price, products.stock 
                                   FROM cart 
                                   JOIN products ON cart.product_id = products.id 
                                   WHERE cart.user_id = '$user_id'");

$total = 0;
$cart_data = [];
while($item = mysqli_fetch_assoc($cart_items)) {
    $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
    $total += $price * $item['quantity'];
    $cart_data[] = $item;
}

if(empty($cart_data)) {
    header('Location: cart.php');
    exit();
}

// Auto-save address when order is placed
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $address = mysqli_real_escape_string($conn, $_POST['address']);
    $city = mysqli_real_escape_string($conn, $_POST['city']);
    $state = mysqli_real_escape_string($conn, $_POST['state']);
    $zip = mysqli_real_escape_string($conn, $_POST['zip']);
    $payment = mysqli_real_escape_string($conn, $_POST['payment_method']);
    
    // Save address to user profile
    mysqli_query($conn, "UPDATE users SET address = '$address', city = '$city', state = '$state', zip_code = '$zip' WHERE id = '$user_id'");
    
    $order_number = 'ORD-' . date('Ymd') . '-' . uniqid();
    $shipping = "$address, $city, $state - $zip";
    
    mysqli_query($conn, "INSERT INTO orders (order_number, user_id, total_amount, payment_method, shipping_address, shipping_city, shipping_state, shipping_zip) 
                         VALUES ('$order_number', '$user_id', '$total', '$payment', '$shipping', '$city', '$state', '$zip')");
    $order_id = mysqli_insert_id($conn);
    
    foreach($cart_data as $item) {
        $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
        mysqli_query($conn, "INSERT INTO order_items (order_id, product_id, quantity, price, size, color) 
                             VALUES ('$order_id', '{$item['product_id']}', '{$item['quantity']}', '$price', '{$item['size']}', '{$item['color']}')");
        // Update stock
        mysqli_query($conn, "UPDATE products SET stock = stock - {$item['quantity']} WHERE id = '{$item['product_id']}'");
    }
    
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
    header("Location: order-success.php?order=$order_number");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        /* Checkout Header */
        .checkout-header {
            background: linear-gradient(135deg, #1a1a1a 0%, #0d0d0d 100%);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,215,0,0.2);
            position: relative;
            overflow: hidden;
        }
        
        .checkout-header::before {
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
        
        .checkout-header h2 {
            color: #FFD700;
            font-weight: 700;
            font-size: 28px;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
            position: relative;
            z-index: 1;
        }
        
        .checkout-header p {
            color: #aaa;
            font-size: 14px;
            position: relative;
            z-index: 1;
        }
        
        /* Checkout Form Card */
        .checkout-form-card {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 20px;
            padding: 30px;
            border: 1px solid rgba(255,215,0,0.2);
            transition: all 0.3s;
        }
        
        .checkout-form-card:hover {
            border-color: rgba(255,215,0,0.4);
        }
        
        .section-title {
            color: #FFD700;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,215,0,0.3);
        }
        
        .form-label {
            color: #fff;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        .form-control-custom {
            background: #2a2a2a;
            border: 1px solid #444;
            color: #fff;
            border-radius: 10px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control-custom:focus {
            background: #2a2a2a;
            border-color: #FFD700;
            color: #fff;
            box-shadow: 0 0 10px rgba(255,215,0,0.2);
            outline: none;
        }
        
        textarea.form-control-custom {
            resize: vertical;
            min-height: 80px;
        }
        
        /* Radio Buttons */
        .payment-option {
            background: #2a2a2a;
            border: 1px solid #444;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s;
        }
        
        .payment-option:hover {
            border-color: #FFD700;
            background: #333;
        }
        
        .payment-option.selected {
            border-color: #FFD700;
            background: rgba(255,215,0,0.1);
        }
        
        .payment-option input {
            margin-right: 12px;
        }
        
        .payment-option label {
            color: #fff;
            cursor: pointer;
            font-weight: 500;
        }
        
        .payment-option i {
            color: #FFD700;
            margin-right: 8px;
            font-size: 18px;
        }
        
        /* Card Details */
        .card-details {
            background: #2a2a2a;
            border-radius: 15px;
            padding: 20px;
            margin-top: 15px;
            border: 1px solid rgba(255,215,0,0.2);
        }
        
        .card-details h6 {
            color: #FFD700;
            margin-bottom: 15px;
        }
        
        /* Order Summary */
        .order-summary-card {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 20px;
            padding: 25px;
            position: sticky;
            top: 100px;
            border: 1px solid rgba(255,215,0,0.2);
        }
        
        .order-summary-card h4 {
            color: #FFD700;
            font-weight: 700;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .order-item {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #fff;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        
        .order-item-name {
            font-size: 14px;
        }
        
        .order-item-price {
            color: #FFD700;
            font-weight: 500;
        }
        
        .summary-divider {
            border-color: rgba(255,255,255,0.1);
            margin: 15px 0;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            color: #fff;
        }
        
        .summary-row.total {
            padding-top: 15px;
            font-size: 20px;
            font-weight: 700;
            color: #FFD700;
        }
        
        .place-order-btn {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            padding: 14px;
            border-radius: 50px;
            font-weight: 700;
            font-size: 16px;
            width: 100%;
            margin-top: 20px;
            border: none;
            transition: all 0.3s;
        }
        
        .place-order-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(255,215,0,0.3);
        }
        
        /* Trust Badges */
        .trust-badges {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .trust-badges i {
            font-size: 32px;
            color: #666;
            transition: all 0.3s;
        }
        
        .trust-badges i:hover {
            color: #FFD700;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .marquee-content span {
                font-size: 11px;
                padding: 0 15px;
            }
            .checkout-header {
                padding: 18px 20px;
            }
            .checkout-header h2 {
                font-size: 22px;
            }
            .checkout-form-card {
                padding: 20px;
                margin-bottom: 20px;
            }
            .order-summary-card {
                position: relative;
                top: 0;
            }
            .section-title {
                font-size: 18px;
            }
        }
        
        @media (max-width: 576px) {
            .form-control-custom {
                padding: 10px 12px;
            }
            .payment-option {
                padding: 12px;
            }
            .trust-badges i {
                font-size: 24px;
            }
        }
    </style>
</head>
<body>

<?php include 'includes/nav-menu.php'; ?>

<!-- Marquee Banner -->
<div class="marquee-banner">
    <div class="marquee-content">
        <span><i class="fas fa-credit-card"></i> <span class="highlight">💳 SECURE CHECKOUT</span> - Your payment information is safe with us! <i class="fas fa-shield-alt"></i></span>
        <span><i class="fas fa-truck"></i> <span class="highlight">🚚 FAST DELIVERY</span> - Get your order within 3-5 business days! <i class="fas fa-clock"></i></span>
        <span><i class="fas fa-headset"></i> <span class="highlight">🎧 24/7 SUPPORT</span> - Need help? Contact our support team! <i class="fas fa-message"></i></span>
        <span><i class="fas fa-credit-card"></i> <span class="highlight">💳 SECURE CHECKOUT</span> - Your payment information is safe with us! <i class="fas fa-shield-alt"></i></span>
        <span><i class="fas fa-truck"></i> <span class="highlight">🚚 FAST DELIVERY</span> - Get your order within 3-5 business days! <i class="fas fa-clock"></i></span>
        <span><i class="fas fa-headset"></i> <span class="highlight">🎧 24/7 SUPPORT</span> - Need help? Contact our support team! <i class="fas fa-message"></i></span>
    </div>
</div>

<div class="container mt-4">
    <!-- Checkout Header -->
    <div class="checkout-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h2><i class="fas fa-credit-card"></i> Checkout</h2>
                <p>Complete your purchase securely</p>
                <div class="checkout-stats" style="margin-top: 10px;">
                    <span style="background: rgba(255,215,0,0.1); padding: 5px 15px; border-radius: 50px; font-size: 13px;">
                        <i class="fas fa-box"></i> <?php echo count($cart_data); ?> item(s) to checkout
                    </span>
                </div>
            </div>
            <div class="mt-3 mt-sm-0">
                <i class="fas fa-shield-alt" style="color: #FFD700; font-size: 28px;"></i>
                <small class="text-muted d-block">Secure Payment</small>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Checkout Form Column -->
        <div class="col-lg-7">
            <div class="checkout-form-card">
                <h5 class="section-title"><i class="fas fa-map-marker-alt"></i> Shipping Information</h5>
                
                <form method="POST" id="checkoutForm">
                    <div class="mb-3">
                        <label class="form-label">Full Address</label>
                        <textarea name="address" class="form-control form-control-custom" required><?php echo htmlspecialchars($user['address']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label">City</label>
                            <input type="text" name="city" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['city']); ?>" required>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">State</label>
                            <select name="state" class="form-control form-control-custom" required>
                                <option value="">-- Select State --</option>
                                <option value="Punjab" <?php echo ($user['state'] == 'Punjab') ? 'selected' : ''; ?>>Punjab</option>
                                <option value="Sindh" <?php echo ($user['state'] == 'Sindh') ? 'selected' : ''; ?>>Sindh</option>
                                <option value="Kpk" <?php echo ($user['state'] == 'Kpk') ? 'selected' : ''; ?>>Khyber Pakhtunkhwa (KPK)</option>
                                <option value="Balochistan" <?php echo ($user['state'] == 'Balochistan') ? 'selected' : ''; ?>>Balochistan</option>
                                <option value="Gilgit-Baltistan" <?php echo ($user['state'] == 'Gilgit-Baltistan') ? 'selected' : ''; ?>>Gilgit-Baltistan</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" name="zip" class="form-control form-control-custom" value="<?php echo htmlspecialchars($user['zip_code']); ?>" required>
                        </div>
                    </div>
                    
                    <h5 class="section-title mt-4"><i class="fas fa-credit-card"></i> Payment Method</h5>
                    
                    <!-- Cash on Delivery Option -->
                    <div class="payment-option" onclick="selectPayment('cash')">
                        <input type="radio" name="payment_method" value="cash" id="cash" checked>
                        <label for="cash">
                            <i class="fas fa-money-bill-wave"></i> Cash on Delivery
                        </label>
                        <span class="text-muted d-block mt-1" style="margin-left: 28px;">Pay when you receive your order</span>
                    </div>
                    
                    <!-- Card Payment Option -->
                    <div class="payment-option" onclick="selectPayment('card')">
                        <input type="radio" name="payment_method" value="card" id="card">
                        <label for="card">
                            <i class="fab fa-cc-visa"></i> <i class="fab fa-cc-mastercard"></i> Credit/Debit Card
                        </label>
                        <span class="text-muted d-block mt-1" style="margin-left: 28px;">Pay securely online</span>
                    </div>

                    <!-- Card Details Form (Hidden by default) -->
                    <div id="cardDetails" style="display: none;">
                        <div class="card-details">
                            <h6><i class="fas fa-lock"></i> Card Details</h6>
                            <div class="mb-3">
                                <label class="form-label">Card Number</label>
                                <input type="text" class="form-control form-control-custom" id="card_number" placeholder="1234 5678 9012 3456" maxlength="19">
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Expiry Date</label>
                                    <input type="text" class="form-control form-control-custom" id="card_expiry" placeholder="MM/YY" maxlength="5">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">CVV</label>
                                    <input type="password" class="form-control form-control-custom" id="card_cvv" placeholder="123" maxlength="4">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Card Holder Name</label>
                                <input type="text" class="form-control form-control-custom" id="card_name" placeholder="Name on card">
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle"></i> This is a demo. No real payment will be processed.</small>
                        </div>
                    </div>
                    
                    <button type="submit" class="place-order-btn" id="placeOrderBtn">
                        <i class="fas fa-check-circle"></i> Place Order
                    </button>
                </form>
            </div>
        </div>
        
        <!-- Order Summary Column -->
        <div class="col-lg-5">
            <div class="order-summary-card">
                <h4><i class="fas fa-receipt"></i> Order Summary</h4>
                
                <?php foreach($cart_data as $item): 
                    $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
                ?>
                <div class="order-item">
                    <span class="order-item-name"><?php echo htmlspecialchars($item['name']); ?> <strong>x<?php echo $item['quantity']; ?></strong></span>
                    <span class="order-item-price">Rs <?php echo number_format($price * $item['quantity'], 2); ?></span>
                </div>
                <?php endforeach; ?>
                
                <hr class="summary-divider">
                
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
                
                <hr class="summary-divider">
                
                <div class="summary-row total">
                    <span>Total</span>
                    <span>Rs <?php echo number_format($total , 2); ?></span>
                </div>
                
                <!-- Trust Badges -->
                <div class="trust-badges">
                    <i class="fab fa-cc-visa" title="Visa"></i>
                    <i class="fab fa-cc-mastercard" title="Mastercard"></i>
                    <i class="fab fa-cc-amex" title="American Express"></i>
                    <i class="fab fa-cc-paypal" title="PayPal"></i>
                    <i class="fas fa-shield-alt" title="Secure Payment"></i>
                </div>
            </div>
            
            <!-- Help Section -->
            <div class="text-center mt-3">
                <a href="cart.php" style="color: #FFD700; text-decoration: none;">
                    <i class="fas fa-arrow-left"></i> Return to Cart
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/toast.js"></script>
<script>
// Payment method selection styling
function selectPayment(method) {
    $('.payment-option').removeClass('selected');
    if(method === 'cash') {
        $('#cash').prop('checked', true);
        $('#cash').closest('.payment-option').addClass('selected');
        $('#cardDetails').slideUp();
    } else {
        $('#card').prop('checked', true);
        $('#card').closest('.payment-option').addClass('selected');
        $('#cardDetails').slideDown();
    }
}

// Format card number with spaces
$('#card_number').on('input', function() {
    let value = $(this).val().replace(/\s/g, '').replace(/(.{4})/g, '$1 ').trim();
    $(this).val(value);
});

// Format expiry date
$('#card_expiry').on('input', function() {
    let value = $(this).val().replace(/\//g, '');
    if(value.length >= 2) {
        value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    $(this).val(value);
});

// Only allow numbers for CVV
$('#card_cvv').on('input', function() {
    $(this).val($(this).val().replace(/[^0-9]/g, ''));
});

$(document).ready(function() {
    // Set initial selected state based on checked radio
    if($('#cash').is(':checked')) {
        $('#cash').closest('.payment-option').addClass('selected');
    } else if($('#card').is(':checked')) {
        $('#card').closest('.payment-option').addClass('selected');
        $('#cardDetails').show();
    }
    
    // Show/hide card details
    $('input[name="payment_method"]').change(function() {
        $('.payment-option').removeClass('selected');
        $(this).closest('.payment-option').addClass('selected');
        
        if($(this).val() === 'card') {
            $('#cardDetails').slideDown();
        } else {
            $('#cardDetails').slideUp();
        }
    });

    // Form submission with card validation
    $('#checkoutForm').submit(function(e) {
        var paymentMethod = $('input[name="payment_method"]:checked').val();
        
        if(paymentMethod === 'card') {
            var cardNum = $('#card_number').val();
            var cardExpiry = $('#card_expiry').val();
            var cardCvv = $('#card_cvv').val();
            var cardName = $('#card_name').val();
            
            if(cardNum === '' || cardExpiry === '' || cardCvv === '' || cardName === '') {
                showToast('Please fill all card details', 'error');
                e.preventDefault();
                return false;
            }
            
            var cleanCardNum = cardNum.replace(/\s/g, '');
            if(cleanCardNum.length < 16) {
                showToast('Please enter valid 16-digit card number', 'error');
                e.preventDefault();
                return false;
            }
            
            if(!/^\d{2}\/\d{2}$/.test(cardExpiry)) {
                showToast('Please enter valid expiry date (MM/YY)', 'error');
                e.preventDefault();
                return false;
            }
            
            var month = parseInt(cardExpiry.substring(0, 2));
            var year = parseInt(cardExpiry.substring(3, 5));
            var currentYear = new Date().getFullYear() % 100;
            var currentMonth = new Date().getMonth() + 1;
            
            if(month < 1 || month > 12) {
                showToast('Please enter valid month (01-12)', 'error');
                e.preventDefault();
                return false;
            }
            
            if(year < currentYear || (year === currentYear && month < currentMonth)) {
                showToast('Card has expired', 'error');
                e.preventDefault();
                return false;
            }
            
            if(cardCvv.length < 3) {
                showToast('Please enter valid CVV', 'error');
                e.preventDefault();
                return false;
            }
        }
        
        // Show loading state
        var $btn = $('#placeOrderBtn');
        $btn.html('<i class="fas fa-spinner fa-spin"></i> Processing...').prop('disabled', true);
        
        return true;
    });
});

// Show toast notification
function showToast(message, type = 'success') {
    const toast = $(`
        <div class="toast-notification" style="position: fixed; bottom: 20px; right: 20px; background: #1a1a1a; color: #fff; padding: 12px 20px; border-radius: 10px; z-index: 9999; border-left: 4px solid ${type === 'success' ? '#28a745' : (type === 'error' ? '#dc3545' : '#ffc107')};">
            <i class="fas ${type === 'success' ? 'fa-check-circle' : (type === 'error' ? 'fa-exclamation-circle' : 'fa-exclamation-triangle')}" style="margin-right: 10px;"></i>
            ${message}
        </div>
    `);
    $('body').append(toast);
    setTimeout(() => toast.fadeOut(300, function() { $(this).remove(); }), 3000);
}
</script>
</body>
</html>