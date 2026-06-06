<?php
session_start();
require_once 'db_connection.php';

$search = isset($_GET['q']) ? mysqli_real_escape_string($conn, $_GET['q']) : '';
$results = [];

if($search) {
    $results = mysqli_query($conn, "SELECT p.*, c.name as category_name 
                                    FROM products p 
                                    LEFT JOIN categories c ON p.category_id = c.id 
                                    WHERE p.name LIKE '%$search%' 
                                    OR p.description LIKE '%$search%' 
                                    OR c.name LIKE '%$search%'
                                    ORDER BY p.created_at DESC");
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>
<body>

<?php include 'includes/nav-menu.php'; ?>

<div class="container mt-5 pt-4">
    <h2 style="color: #FFD700;">Search Results for: "<?php echo htmlspecialchars($search); ?>"</h2>
    
    <?php if($search && isset($results) && mysqli_num_rows($results) > 0): ?>
        <div class="row mt-4">
            <?php while($product = mysqli_fetch_assoc($results)): ?>
            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                <div class="product-card">
                    <div class="product-img-wrapper">
                        <img src="<?php echo !empty($product['image_url']) ? $product['image_url'] : 'https://via.placeholder.com/300x400?text=No+Image'; ?>" class="product-img">
                        <div class="product-overlay">
                            <button class="quick-view-btn" data-id="<?php echo $product['id']; ?>">Quick View</button>
                        </div>
                    </div>
                    <div class="product-info">
                        <h5 class="product-title"><?php echo $product['name']; ?></h5>
                        <div><span class="product-price">$<?php echo $product['sale_price'] ? $product['sale_price'] : $product['price']; ?></span></div>
                        <div class="product-buttons">
                            <button class="btn-cart add-to-cart" data-id="<?php echo $product['id']; ?>">Add to Cart</button>
                            <button class="btn-wishlist add-to-wishlist" data-id="<?php echo $product['id']; ?>"><i class="fas fa-heart"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>
        </div>
    <?php elseif($search): ?>
        <div class="alert alert-info mt-4 text-center">No products found matching your search.</div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="js/toast.js"></script>
<script>
$('.add-to-cart').click(function() {
    var pid = $(this).data('id');
    $.ajax({url: 'add-to-cart.php', method: 'POST', data: {product_id: pid, quantity: 1},
        success: function(r) { if(r=='success') showToast('Added to cart!','success'); else if(r=='not_logged_in') showToast('Please login first','error'); }
    });
});
$('.add-to-wishlist').click(function() {
    var pid = $(this).data('id');
    $.ajax({url: 'add-to-wishlist.php', method: 'POST', data: {product_id: pid},
        success: function(r) { if(r=='success') showToast('Added to wishlist!','success'); else if(r=='already_exists') showToast('Already in wishlist!','warning'); }
    });
});
</script>
</body>
</html>