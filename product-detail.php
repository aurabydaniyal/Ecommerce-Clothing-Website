<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

// Get product ID from URL
$product_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if($product_id == 0) {
    header('Location: index.php');
    exit();
}

// Get product details
$sql = "SELECT * FROM products WHERE id = '$product_id'";
$result = mysqli_query($conn, $sql);
$product = mysqli_fetch_assoc($result);

if(!$product) {
    header('Location: index.php');
    exit();
}

// Update product views
updateProductViews($product_id);

// Get related products (same type)
$related_sql = "SELECT * FROM products WHERE type = '{$product['type']}' AND id != '$product_id' LIMIT 4";
$related_products = mysqli_query($conn, $related_sql);

// Get product reviews
$reviews_sql = "SELECT reviews.*, users.full_name 
                FROM reviews 
                JOIN users ON reviews.user_id = users.id 
                WHERE reviews.product_id = '$product_id' 
                ORDER BY reviews.created_at DESC";
$reviews = mysqli_query($conn, $reviews_sql);

// Parse colors and sizes
$colors = explode(',', $product['colors']);
$sizes = explode(',', $product['sizes']);

// Check if product is in wishlist
$in_wishlist = false;
if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'");
    $in_wishlist = mysqli_num_rows($check) > 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $product['name']; ?> - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .product-detail-container {
            padding: 100px 0 50px;
        }
        .product-main-image {
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.1);
        }
        .thumbnail-images {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .thumbnail {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .thumbnail:hover, .thumbnail.active {
            border-color: #667eea;
            transform: scale(1.05);
        }
        .color-option {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            display: inline-block;
            margin: 5px;
            cursor: pointer;
            border: 2px solid #ddd;
            transition: all 0.3s;
        }
        .color-option:hover, .color-option.selected {
            transform: scale(1.1);
            border-color: #667eea;
            box-shadow: 0 0 0 2px rgba(102,126,234,0.3);
        }
        .size-option {
            display: inline-block;
            padding: 8px 18px;
            margin: 5px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s;
            font-weight: 500;
        }
        .size-option:hover, .size-option.selected {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .quantity-btn {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            border: 1px solid #ddd;
            background: white;
            cursor: pointer;
            transition: all 0.3s;
        }
        .quantity-btn:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .quantity-input {
            width: 60px;
            text-align: center;
            font-size: 18px;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 8px;
        }
        .btn-add-cart {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 15px 40px;
            border-radius: 30px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .btn-add-cart:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 20px rgba(102,126,234,0.4);
            color: white;
        }
        .btn-wishlist {
            background: white;
            border: 2px solid #ddd;
            padding: 15px 30px;
            border-radius: 30px;
            transition: all 0.3s;
        }
        .btn-wishlist.active {
            background: #ff4757;
            color: white;
            border-color: #ff4757;
        }
        .review-card {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .rating-stars i {
            font-size: 14px;
        }
        .breadcrumb {
            background: transparent;
            padding: 0;
        }
        .stock-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        .stock-in {
            background: #d4edda;
            color: #155724;
        }
        .stock-out {
            background: #f8d7da;
            color: #721c24;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .product-detail-container {
            animation: fadeIn 0.5s ease-out;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
    <div class="container">
        <a class="navbar-brand" href="index.php"><i class="fas fa-tshirt"></i> UHD-Wears</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link" href="index.php">Home</a></li>
                <li class="nav-item"><a class="nav-link" href="men.php">Men</a></li>
                <li class="nav-item"><a class="nav-link" href="women.php">Women</a></li>
                <li class="nav-item"><a class="nav-link" href="kids.php">Kids</a></li>
                <li class="nav-item"><a class="nav-link" href="sale.php">Sale</a></li>
                <li class="nav-item"><a class="nav-link" href="about.php">About</a></li>
                <li class="nav-item"><a class="nav-link" href="wishlist.php"><i class="fas fa-heart"></i> <span id="wishlistCount">0</span></a></li>
                <li class="nav-item"><a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart"></i> <span id="cartCount">0</span></a></li>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['username']; ?>
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="user/dashboard.php">Dashboard</a></li>
                            <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item"><a class="nav-link" href="login.php">Login</a></li>
                    <li class="nav-item"><a class="nav-link" href="signup.php">Sign Up</a></li>
                <?php endif; ?>
            </ul>
            <form class="d-flex ms-3" action="search.php" method="GET">
                <input class="form-control me-2" type="search" name="q" placeholder="Search products...">
                <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
            </form>
        </div>
    </div>
</nav>

<!-- Product Detail -->
<div class="product-detail-container">
    <div class="container">
        <!-- Breadcrumb -->
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb">
                <li class="breadcrumb-item"><a href="index.php">Home</a></li>
                <li class="breadcrumb-item"><a href="<?php echo $product['type']; ?>.php"><?php echo ucfirst($product['type']); ?></a></li>
                <li class="breadcrumb-item active" aria-current="page"><?php echo $product['name']; ?></li>
            </ol>
        </nav>

        <div class="row">
            <!-- Product Images -->
            <div class="col-md-6">
                <img src="<?php echo $product['image_url']; ?>" id="mainProductImage" class="product-main-image" alt="<?php echo $product['name']; ?>">
                <div class="thumbnail-images">
                    <img src="<?php echo $product['image_url']; ?>" class="thumbnail active" data-image="<?php echo $product['image_url']; ?>">
                    <?php 
                    // If there are multiple images in image_urls field (JSON format)
                    $additional_images = [];
                    if($product['image_urls']) {
                        $additional_images = json_decode($product['image_urls'], true);
                        foreach($additional_images as $img):
                    ?>
                        <img src="<?php echo $img; ?>" class="thumbnail" data-image="<?php echo $img; ?>">
                    <?php 
                        endforeach;
                    } 
                    ?>
                </div>
            </div>

            <!-- Product Info -->
            <div class="col-md-6">
                <div class="mb-3">
                    <span class="badge bg-primary"><?php echo ucfirst($product['type']); ?></span>
                    <?php if($product['sale_price']): ?>
                        <span class="badge bg-danger ms-2">SALE <?php echo round((($product['price'] - $product['sale_price']) / $product['price']) * 100); ?>% OFF</span>
                    <?php endif; ?>
                </div>
                
                <h1><?php echo $product['name']; ?></h1>
                
                <div class="rating-stars mb-3">
                    <?php for($i = 1; $i <= 5; $i++): ?>
                        <i class="fas fa-star <?php echo $i <= $product['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                    <?php endfor; ?>
                    <span class="text-muted ms-2">(<?php echo $product['views']; ?> views)</span>
                </div>
                
                <div class="mb-3">
                    <?php if($product['sale_price']): ?>
                        <span class="display-6 text-primary fw-bold">$<?php echo number_format($product['sale_price'], 2); ?></span>
                        <span class="text-muted ms-2"><del>$<?php echo number_format($product['price'], 2); ?></del></span>
                    <?php else: ?>
                        <span class="display-6 text-primary fw-bold">$<?php echo number_format($product['price'], 2); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-3">
                    <?php if($product['stock'] > 0): ?>
                        <span class="stock-badge stock-in"><i class="fas fa-check-circle"></i> In Stock (<?php echo $product['stock']; ?> items)</span>
                    <?php else: ?>
                        <span class="stock-badge stock-out"><i class="fas fa-times-circle"></i> Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <p class="text-muted mb-4"><?php echo nl2br($product['description']); ?></p>
                
                <!-- Colors -->
                <div class="mb-4">
                    <label class="fw-bold mb-2">Select Color:</label>
                    <div id="colorOptions">
                        <?php foreach($colors as $color): ?>
                            <div class="color-option" style="background-color: <?php echo strtolower(trim($color)); ?>;" data-color="<?php echo trim($color); ?>" title="<?php echo $color; ?>"></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Sizes -->
                <div class="mb-4">
                    <label class="fw-bold mb-2">Select Size:</label>
                    <div id="sizeOptions">
                        <?php foreach($sizes as $size): ?>
                            <div class="size-option" data-size="<?php echo trim($size); ?>"><?php echo trim($size); ?></div>
                        <?php endforeach; ?>
                    </div>
                </div>
                
                <!-- Quantity -->
                <div class="mb-4">
                    <label class="fw-bold mb-2">Quantity:</label>
                    <div class="quantity-selector">
                        <button class="quantity-btn" id="decreaseQty">-</button>
                        <input type="number" id="quantity" class="quantity-input" value="1" min="1" max="<?php echo $product['stock']; ?>" readonly>
                        <button class="quantity-btn" id="increaseQty">+</button>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="d-flex gap-3">
                    <button class="btn-add-cart" id="addToCartBtn">
                        <i class="fas fa-shopping-cart"></i> Add to Cart
                    </button>
                    <button class="btn-wishlist <?php echo $in_wishlist ? 'active' : ''; ?>" id="wishlistBtn">
                        <i class="fas fa-heart"></i> <?php echo $in_wishlist ? 'Remove from Wishlist' : 'Add to Wishlist'; ?>
                    </button>
                </div>
                
                <!-- Product Meta -->
                <div class="mt-4 pt-3 border-top">
                    <p><i class="fas fa-tag"></i> <strong>Category:</strong> <?php echo ucfirst($product['type']); ?></p>
                    <p><i class="fas fa-box"></i> <strong>SKU:</strong> #<?php echo str_pad($product['id'], 6, '0', STR_PAD_LEFT); ?></p>
                    <p><i class="fas fa-truck"></i> <strong>Free Shipping</strong> on orders over $50</p>
                    <p><i class="fas fa-undo"></i> <strong>30-Day Return Policy</strong></p>
                </div>
            </div>
        </div>
        
        <!-- Product Tabs -->
        <div class="row mt-5">
            <div class="col-md-12">
                <ul class="nav nav-tabs" id="productTab" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#description">Description</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#specifications">Specifications</button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#reviews">Reviews (<?php echo mysqli_num_rows($reviews); ?>)</button>
                    </li>
                </ul>
                <div class="tab-content p-4 border border-top-0 rounded-bottom">
                    <div class="tab-pane fade show active" id="description">
                        <h4>Product Description</h4>
                        <p><?php echo nl2br($product['description']); ?></p>
                        <h5>Features:</h5>
                        <ul>
                            <li>High quality material</li>
                            <li>Comfortable fit</li>
                            <li>Premium stitching</li>
                            <li>Available in multiple colors and sizes</li>
                        </ul>
                    </div>
                    <div class="tab-pane fade" id="specifications">
                        <h4>Product Specifications</h4>
                        <table class="table table-bordered">
                            <tr>
                                <th>Material</th>
                                <td>Premium Quality Fabric</td>
                            </tr>
                            <tr>
                                <th>Colors Available</th>
                                <td><?php echo $product['colors']; ?></td>
                            </tr>
                            <tr>
                                <th>Sizes Available</th>
                                <td><?php echo $product['sizes']; ?></td>
                            </tr>
                            <tr>
                                <th>Care Instructions</th>
                                <td>Machine wash cold, tumble dry low</td>
                            </tr>
                            <tr>
                                <th>Origin</th>
                                <td>Imported</td>
                            </tr>
                        </table>
                    </div>
                    <div class="tab-pane fade" id="reviews">
                        <h4>Customer Reviews</h4>
                        <?php if(mysqli_num_rows($reviews) > 0): ?>
                            <?php while($review = mysqli_fetch_assoc($reviews)): ?>
                            <div class="review-card">
                                <div class="d-flex justify-content-between">
                                    <h6><?php echo $review['full_name']; ?></h6>
                                    <small class="text-muted"><?php echo date('F j, Y', strtotime($review['created_at'])); ?></small>
                                </div>
                                <div class="rating-stars mb-2">
                                    <?php for($i = 1; $i <= 5; $i++): ?>
                                        <i class="fas fa-star <?php echo $i <= $review['rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                    <?php endfor; ?>
                                </div>
                                <p><?php echo $review['comment']; ?></p>
                            </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-muted">No reviews yet. Be the first to review this product!</p>
                        <?php endif; ?>
                        
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <button class="btn btn-primary mt-3" data-bs-toggle="modal" data-bs-target="#reviewModal">
                                <i class="fas fa-pen"></i> Write a Review
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Related Products -->
        <?php if(mysqli_num_rows($related_products) > 0): ?>
        <div class="row mt-5">
            <div class="col-md-12">
                <h3 class="text-center mb-4">You May Also Like</h3>
                <div class="row">
                    <?php while($related = mysqli_fetch_assoc($related_products)): ?>
                    <div class="col-md-3 mb-4">
                        <div class="product-card">
                            <img src="<?php echo $related['image_url']; ?>" class="product-img" alt="<?php echo $related['name']; ?>">
                            <div class="product-overlay">
                                <a href="product-detail.php?id=<?php echo $related['id']; ?>" class="btn btn-primary">View Details</a>
                                <button class="btn btn-success add-to-cart" data-id="<?php echo $related['id']; ?>">Add to Cart</button>
                            </div>
                            <div class="product-info">
                                <h5><?php echo $related['name']; ?></h5>
                                <p class="price">$<?php echo $related['sale_price'] ? $related['sale_price'] : $related['price']; ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Review Modal -->
<div class="modal fade" id="reviewModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Write a Review</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="submit-review.php">
                <div class="modal-body">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <div class="mb-3">
                        <label>Rating</label>
                        <div class="rating-input">
                            <i class="far fa-star" data-rating="1"></i>
                            <i class="far fa-star" data-rating="2"></i>
                            <i class="far fa-star" data-rating="3"></i>
                            <i class="far fa-star" data-rating="4"></i>
                            <i class="far fa-star" data-rating="5"></i>
                            <input type="hidden" name="rating" id="ratingValue" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label>Your Review</label>
                        <textarea name="comment" class="form-control" rows="4" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Submit Review</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h4>About UHD-Wears</h4>
                <p>Premium clothing brand offering the latest fashion for men, women, and kids. Quality and style guaranteed.</p>
            </div>
            <div class="col-md-4">
                <h4>Quick Links</h4>
                <ul>
                    <li><a href="index.php">Home</a></li>
                    <li><a href="men.php">Men</a></li>
                    <li><a href="women.php">Women</a></li>
                    <li><a href="kids.php">Kids</a></li>
                    <li><a href="sale.php">Sale</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h4>Contact Info</h4>
                <p><i class="fas fa-map-marker-alt"></i> 123 Fashion Street, New York, USA</p>
                <p><i class="fas fa-phone"></i> +1 234 567 8900</p>
                <p><i class="fas fa-envelope"></i> info@uhdwears.com</p>
                <div class="social-links">
                    <a href="#"><i class="fab fa-facebook"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
        </div>
        <div class="text-center mt-3">
            <p>&copy; 2024 UHD-Wears. All rights reserved.</p>
        </div>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    let selectedColor = '';
    let selectedSize = '';
    let currentQuantity = 1;
    const maxStock = <?php echo $product['stock']; ?>;
    
    // Update cart/wishlist counts
    function updateCounts() {
        $.ajax({url: 'get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
        $.ajax({url: 'get-wishlist-count.php', success: function(data) {$('#wishlistCount').text(data);}});
    }
    
    // Thumbnail click
    $('.thumbnail').click(function() {
        $('.thumbnail').removeClass('active');
        $(this).addClass('active');
        var newImage = $(this).data('image');
        $('#mainProductImage').fadeOut(200, function() {
            $(this).attr('src', newImage).fadeIn(200);
        });
    });
    
    // Color selection
    $('.color-option').click(function() {
        $('.color-option').removeClass('selected');
        $(this).addClass('selected');
        selectedColor = $(this).data('color');
    });
    
    // Size selection
    $('.size-option').click(function() {
        $('.size-option').removeClass('selected');
        $(this).addClass('selected');
        selectedSize = $(this).data('size');
    });
    
    // Quantity buttons
    $('#decreaseQty').click(function() {
        if(currentQuantity > 1) {
            currentQuantity--;
            $('#quantity').val(currentQuantity);
        }
    });
    
    $('#increaseQty').click(function() {
        if(currentQuantity < maxStock) {
            currentQuantity++;
            $('#quantity').val(currentQuantity);
        } else {
            alert('Maximum stock available is ' + maxStock);
        }
    });
    
    // Add to cart
    $('#addToCartBtn').click(function() {
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
                product_id: <?php echo $product['id']; ?>,
                quantity: currentQuantity,
                color: selectedColor,
                size: selectedSize
            },
            success: function(response) {
                if(response === 'success') {
                    alert('Product added to cart successfully!');
                    updateCounts();
                } else {
                    alert('Error adding product to cart');
                }
            }
        });
    });
    
    // Wishlist button
    $('#wishlistBtn').click(function() {
        var btn = $(this);
        var isActive = btn.hasClass('active');
        
        if(isActive) {
            // Remove from wishlist
            $.ajax({
                url: 'remove-from-wishlist.php',
                method: 'POST',
                data: {product_id: <?php echo $product['id']; ?>},
                success: function() {
                    btn.removeClass('active');
                    btn.html('<i class="fas fa-heart"></i> Add to Wishlist');
                    updateCounts();
                    alert('Removed from wishlist');
                }
            });
        } else {
            // Add to wishlist
            $.ajax({
                url: 'add-to-wishlist.php',
                method: 'POST',
                data: {product_id: <?php echo $product['id']; ?>},
                success: function() {
                    btn.addClass('active');
                    btn.html('<i class="fas fa-heart"></i> Remove from Wishlist');
                    updateCounts();
                    alert('Added to wishlist');
                }
            });
        }
    });
    
    // Rating stars for review
    $('.rating-input i').hover(function() {
        var rating = $(this).data('rating');
        $('.rating-input i').each(function(index) {
            if(index < rating) {
                $(this).removeClass('far').addClass('fas text-warning');
            } else {
                $(this).removeClass('fas text-warning').addClass('far');
            }
        });
    });
    
    $('.rating-input i').click(function() {
        var rating = $(this).data('rating');
        $('#ratingValue').val(rating);
        $('.rating-input i').each(function(index) {
            if(index < rating) {
                $(this).removeClass('far').addClass('fas text-warning');
            } else {
                $(this).removeClass('fas text-warning').addClass('far');
            }
        });
    });
    
    // Add to cart for related products
    $('.add-to-cart').click(function() {
        var productId = $(this).data('id');
        $.ajax({
            url: 'add-to-cart.php',
            method: 'POST',
            data: {product_id: productId, quantity: 1},
            success: function(response) {
                alert('Product added to cart!');
                updateCounts();
            }
        });
    });
    
    updateCounts();
});
</script>
</body>
</html>