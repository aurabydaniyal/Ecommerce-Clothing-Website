<?php
session_start();
require_once 'db_connection.php';

$product_id = $_GET['id'] ?? 0;
$sql = "SELECT * FROM products WHERE id = '$product_id'";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if(!$product) {
    die('Product not found');
}

$colors = explode(',', $product['colors']);
$sizes = explode(',', $product['sizes']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quick View - <?php echo $product['name']; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .quick-view-container {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            animation: fadeInUp 0.5s;
        }
        @keyframes fadeInUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        .product-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
        }
        .color-option {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            display: inline-block;
            margin: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .color-option:hover, .color-option.selected {
            transform: scale(1.1);
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.3);
        }
        .size-option {
            display: inline-block;
            padding: 8px 15px;
            margin: 5px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
        }
        .size-option:hover, .size-option.selected {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .quantity-input {
            width: 80px;
            text-align: center;
        }
        .btn-add {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 30px;
            border-radius: 25px;
            transition: transform 0.3s;
        }
        .btn-add:hover {
            transform: translateY(-2px);
            color: white;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="quick-view-container">
        <div class="row g-0">
            <div class="col-md-6">
                <img src="<?php echo $product['image_url']; ?>" class="product-image" id="mainImage" alt="<?php echo $product['name']; ?>">
            </div>
            <div class="col-md-6 p-4">
                <h2><?php echo $product['name']; ?></h2>
                <div class="rating mb-3">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                    <?php endfor; ?>
                    <span class="text-muted">(<?php echo $product['views']; ?> views)</span>
                </div>
                
                <div class="mb-3">
                    <?php if($product['sale_price']): ?>
                        <span class="price display-6 text-primary">Rs <?php echo $product['sale_price']; ?></span>
                        <span class="old-price text-muted ms-2"><del>Rs <?php echo $product['price']; ?></del></span>
                        <span class="badge bg-danger ms-2">Sale!</span>
                    <?php else: ?>
                        <span class="price display-6 text-primary">Rs <?php echo $product['price']; ?></span>
                    <?php endif; ?>
                </div>
                
                <p class="text-muted"><?php echo $product['description']; ?></p>
                
                <div class="mb-3">
                    <strong>Colors:</strong>
                    <div id="colorOptions">
                        <?php foreach($colors as $color): ?>
                            <div class="color-option" style="background-color: <?php echo strtolower($color); ?>;" data-color="<?php echo $color; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Sizes:</strong>
                    <div id="sizeOptions">
                        <?php foreach($sizes as $size): ?>
                            <div class="size-option" data-size="<?php echo $size; ?>"><?php echo $size; ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <div class="mb-3">
                    <strong>Quantity:</strong>
                    <input type="number" id="quantity" class="form-control quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>">
                </div>
                
                <div class="mb-3">
                    <strong>Availability:</strong>
                    <?php if($product['stock'] > 0): ?>
                        <span class="text-success"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock']; ?> items)</span>
                    <?php else: ?>
                        <span class="text-danger"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <div class="mt-4">
                    <button class="btn-add btn" id="addToCartBtn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn-add btn" id="addToWishlistBtn" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                        <i class="fas fa-heart"></i> Add to Wishlist
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    var selectedColor = '';
    var selectedSize = '';
    
    $('.color-option').click(function() {
        $('.color-option').removeClass('selected');
        $(this).addClass('selected');
        selectedColor = $(this).data('color');
    });
    
    $('.size-option').click(function() {
        $('.size-option').removeClass('selected');
        $(this).addClass('selected');
        selectedSize = $(this).data('size');
    });
    
    $('#addToCartBtn').click(function() {
        var productId = <?php echo $product['id']; ?>;
        var quantity = $('#quantity').val();
        
        if(!selectedColor) {
            alert('Please select a color');
            return;
        }
        if(!selectedSize) {
            alert('Please select a size');
            return;
        }
        
        $.ajax({
            url: 'add-to-cart.php',
            method: 'POST',
            data: {
                product_id: productId,
                quantity: quantity,
                color: selectedColor,
                size: selectedSize
            },
            success: function(response) {
                alert('Product added to cart successfully!');
                window.close();
            }
        });
    });
    
    $('#addToWishlistBtn').click(function() {
        var productId = <?php echo $product['id']; ?>;
        
        $.ajax({
            url: 'add-to-wishlist.php',
            method: 'POST',
            data: {product_id: productId},
            success: function(response) {
                alert('Product added to wishlist!');
                window.close();
            }
        });
    });
});
</script>
</body>
</html>