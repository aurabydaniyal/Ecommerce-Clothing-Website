<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get categories for dropdown
$men_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'men' AND is_active = 1 ORDER BY display_order");
$women_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'women' AND is_active = 1 ORDER BY display_order");
$kids_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'kids' AND is_active = 1 ORDER BY display_order");
$sale_cats = mysqli_query($conn, "SELECT * FROM categories WHERE type = 'sale' AND is_active = 1 ORDER BY display_order");

// Add product with multiple images
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : 'NULL';
    $type = $_POST['type'];
    $category_name = mysqli_real_escape_string($conn, $_POST['category']);
    $colors = mysqli_real_escape_string($conn, $_POST['colors']);
    $sizes = mysqli_real_escape_string($conn, $_POST['sizes']);
    $stock = $_POST['stock'];
    
    // Get category_id
    $cat_result = mysqli_query($conn, "SELECT id FROM categories WHERE name = '$category_name' AND type = '$type'");
    $category = mysqli_fetch_assoc($cat_result);
    $category_id = $category ? $category['id'] : NULL;
    
    $target_dir = "../uploads/products/";
    if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    // Handle main image upload
    $image1 = '';
    if(isset($_FILES['image1']) && $_FILES['image1']['error'] == 0) {
        $filename1 = time() . '_1_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image1']['name']);
        move_uploaded_file($_FILES['image1']['tmp_name'], $target_dir . $filename1);
        $image1 = "uploads/products/" . $filename1;
    }
    
    // Handle image2 upload
    $image2 = '';
    if(isset($_FILES['image2']) && $_FILES['image2']['error'] == 0) {
        $filename2 = time() . '_2_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image2']['name']);
        move_uploaded_file($_FILES['image2']['tmp_name'], $target_dir . $filename2);
        $image2 = "uploads/products/" . $filename2;
    }
    
    // Handle image3 upload
    $image3 = '';
    if(isset($_FILES['image3']) && $_FILES['image3']['error'] == 0) {
        $filename3 = time() . '_3_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image3']['name']);
        move_uploaded_file($_FILES['image3']['tmp_name'], $target_dir . $filename3);
        $image3 = "uploads/products/" . $filename3;
    }
    
    if($image1) {
        $sql = "INSERT INTO products (name, description, price, sale_price, type, category_id, colors, sizes, stock, image_url, image2, image3) 
                VALUES ('$name', '$description', '$price', " . ($sale_price != 'NULL' ? "'$sale_price'" : "NULL") . ", '$type', " . ($category_id ? "'$category_id'" : "NULL") . ", '$colors', '$sizes', '$stock', '$image1', '$image2', '$image3')";
        
        if(mysqli_query($conn, $sql)) {
            header('Location: products.php?added=1');
            exit();
        }
    }
}

// Delete product
if(isset($_GET['delete'])) {
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM products WHERE id = '$id'");
    header('Location: products.php?deleted=1');
    exit();
}

$products = mysqli_query($conn, "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.created_at DESC");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Products - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            background: #0a0a0a; 
            font-family: 'Poppins', sans-serif;
            position: relative;
        }
        
        /* ============================================
           NEON YELLOW GRID BACKGROUND
           ============================================ */
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                linear-gradient(rgba(255,215,0,0.04) 1px, transparent 1px),
                linear-gradient(90deg, rgba(255,215,0,0.04) 1px, transparent 1px);
            background-size: 50px 50px;
            pointer-events: none;
            z-index: 0;
        }
        
        body::after {
            content: '';
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(255,215,0,0.08) 0%, transparent 70%);
            pointer-events: none;
            z-index: 0;
        }
        
        /* ============================================
           GLASSY SIDEBAR
           ============================================ */
        .sidebar { 
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            min-height: 100vh; 
            border-right: 1px solid rgba(255,215,0,0.2);
            position: sticky;
            top: 0;
            z-index: 1;
        }
        
        .sidebar .nav-link { 
            color: #fff; 
            padding: 12px 20px; 
            transition: all 0.3s; 
            border-radius: 12px; 
            margin: 5px 15px;
            font-weight: 500;
        }
        .sidebar .nav-link:hover, 
        .sidebar .nav-link.active { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            transform: translateX(5px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.2);
        }
        .sidebar .nav-link i {
            width: 25px;
            margin-right: 10px;
        }
        
        /* Main Content */
        .col-md-10 {
            position: relative;
            z-index: 1;
        }
        
        /* ============================================
           GLASSY PRODUCT CARDS
           ============================================ */
        .product-card { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            overflow: hidden; 
            margin-bottom: 20px; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255,215,0,0.15);
            height: 100%;
        }
        .product-card:hover { 
            transform: translateY(-8px); 
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 20px 40px rgba(255,215,0,0.15);
        }
        .product-card img { 
            width: 100%; 
            height: 220px; 
            object-fit: cover; 
            transition: transform 0.5s;
        }
        .product-card:hover img {
            transform: scale(1.05);
        }
        .product-card .info { 
            padding: 18px; 
        }
        .product-card h6 { 
            color: #fff; 
            margin-bottom: 8px;
            font-weight: 600;
            font-size: 16px;
        }
        .product-card .price { 
            color: #FFD700; 
            font-weight: bold;
            font-size: 18px;
        }
        
        /* Badges */
        .badge {
            padding: 5px 10px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 11px;
        }
        
        /* ============================================
           GLASSY HEADER
           ============================================ */
        h2 { 
            color: #FFD700;
            font-weight: 700;
        }
        
        .btn-warning-custom {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-warning-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,215,0,0.3);
        }
        
        /* ============================================
           GLASSY MODAL
           ============================================ */
        .modal-content {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 20px;
        }
        .modal-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border-radius: 20px 20px 0 0;
            border-bottom: none;
        }
        .modal-header h5 {
            color: #000;
            font-weight: 700;
        }
        .modal-body label {
            color: #fff;
            font-weight: 500;
            margin-bottom: 8px;
        }
        .form-control, .form-select {
            background: rgba(42, 42, 42, 0.9);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 12px;
            padding: 10px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
            outline: none;
        }
        .modal-footer {
            border-top: 1px solid rgba(255,255,255,0.1);
        }
        
        /* Image Preview */
        .image-preview { 
            max-width: 80px; 
            max-height: 80px; 
            margin-top: 10px; 
            border-radius: 10px; 
            border: 2px solid #FFD700;
        }
        .preview-container { 
            display: flex; 
            gap: 10px; 
            flex-wrap: wrap; 
            margin-top: 10px; 
        }
        
        /* Alert Styles */
        .alert-custom {
            background: rgba(40,167,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #28a745;
            color: #6bff8a;
            border-radius: 12px;
            padding: 12px 18px;
        }
        .alert-danger-custom {
            background: rgba(220,53,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #dc3545;
            color: #ff6b6b;
            border-radius: 12px;
            padding: 12px 18px;
        }
        
        /* ============================================
           MOBILE RESPONSIVE STYLES
           ============================================ */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1060;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 10px 16px;
            border-radius: 50px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        @media (max-width: 992px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -280px;
                width: 260px;
                height: 100%;
                z-index: 1050;
                transition: left 0.3s ease;
                overflow-y: auto;
            }
            .sidebar.show { left: 0; }
            .sidebar-toggle { display: block; }
            .col-md-10 { width: 100%; margin-left: 0; padding: 15px; }
            body { padding-top: 70px; }
            .col-md-3 { width: 50%; }
            h2 { font-size: 22px; }
            .d-flex.justify-content-between { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            .col-md-3 { width: 100%; }
            h2 { font-size: 20px; }
            .p-4 { padding: 15px !important; }
            .product-card img { height: 200px; }
            .product-card .info { padding: 12px; }
            .product-card h6 { font-size: 14px; }
            .btn-sm { padding: 4px 8px; font-size: 11px; }
            .modal-body { padding: 20px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            .product-card img { height: 180px; }
        }
    </style>
</head>
<body>

<!-- Mobile Sidebar Toggle Button -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i> Menu
</button>

<div class="container-fluid">
    <div class="row">
        <!-- Glassy Sidebar -->
        <div class="col-md-2 p-0 sidebar" id="sidebar">
            <div class="p-4 text-center">
                <div class="logo-circle" style="width: 50px; height: 50px; background: linear-gradient(135deg, #FFD700, #FFC107); border-radius: 12px; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;">
                    <i class="fas fa-tshirt" style="font-size: 24px; color: #000;"></i>
                </div>
                <h3 style="color: #FFD700; font-size: 18px;">UHD-Wears</h3>
                <p style="color:#888; font-size: 11px;">Admin Panel</p>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a class="nav-link active" href="products.php"><i class="fas fa-box"></i> Products</a>
                <a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="sliders.php"><i class="fas fa-image"></i> Sliders</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 15px;">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between mb-4 flex-wrap align-items-center">
                <h2><i class="fas fa-box me-2"></i> Products</h2>
                <button class="btn-warning-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i> Add Product
                </button>
            </div>
            
            <?php if(isset($_GET['added'])): ?>
            <div class="alert-custom mb-3"><i class="fas fa-check-circle me-2"></i> Product added successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['deleted'])): ?>
            <div class="alert-danger-custom mb-3"><i class="fas fa-trash-alt me-2"></i> Product deleted!</div>
            <?php endif; ?>
            
            <div class="row g-4 mt-2">
                <?php while($p = mysqli_fetch_assoc($products)): 
                    $img_src = $p['image_url'];
                    if(!empty($img_src) && strpos($img_src, '../') !== 0 && strpos($img_src, 'http') !== 0) {
                        $img_src = '../' . $img_src;
                    }
                ?>
                <div class="col-lg-3 col-md-4 col-sm-6">
                    <div class="product-card">
                        <img src="<?php echo $img_src; ?>" onerror="this.src='https://via.placeholder.com/300x300?text=No+Image'">
                        <div class="info">
                            <h6><?php echo htmlspecialchars($p['name']); ?></h6>
                            <p class="price">Rs <?php echo $p['price']; ?></p>
                            <div class="mb-2">
                                <span class="badge" style="background: rgba(255,215,0,0.2); color: #FFD700;"><?php echo ucfirst($p['type']); ?></span>
                                <?php if($p['category_name']): ?>
                                <span class="badge" style="background: rgba(255,215,0,0.15); color: #aaa;"><?php echo $p['category_name']; ?></span>
                                <?php endif; ?>
                                <span class="badge <?php echo $p['stock'] > 0 ? 'bg-success' : 'bg-danger'; ?>">Stock: <?php echo $p['stock']; ?></span>
                            </div>
                            <div class="d-flex gap-2">
                                <a href="edit-product.php?id=<?php echo $p['id']; ?>" class="btn btn-sm" style="background: #FFD700; color: #000; border-radius: 50px; padding: 5px 12px;">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button class="btn btn-sm delete-product" style="background: rgba(220,53,69,0.2); color: #dc3545; border-radius: 50px; padding: 5px 12px;" data-id="<?php echo $p['id']; ?>" data-name="<?php echo htmlspecialchars($p['name']); ?>">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Product Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Add New Product</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" enctype="multipart/form-data">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-tag me-1"></i> Product Name</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-layer-group me-1"></i> Type</label>
                            <select name="type" class="form-select" id="productType" required>
                                <option value="men">Men</option>
                                <option value="women">Women</option>
                                <option value="kids">Kids</option>
                                <option value="sale">Sale</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label><i class="fas fa-align-left me-1"></i> Description</label>
                            <textarea name="description" class="form-control" rows="3" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-dollar-sign me-1"></i> Price (Rs )</label>
                            <input type="number" step="0.01" name="price" class="form-control" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-percent me-1"></i> Sale Price (Rs )</label>
                            <input type="number" step="0.01" name="sale_price" class="form-control" placeholder="Optional">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-folder me-1"></i> Category</label>
                            <select name="category" class="form-select" id="categorySelect" required>
                                <option value="">-- Select Category --</option>
                                <?php 
                                mysqli_data_seek($men_cats, 0);
                                while($cat = mysqli_fetch_assoc($men_cats)): ?>
                                <option value="<?php echo $cat['name']; ?>" class="men-cat">Men - <?php echo $cat['name']; ?></option>
                                <?php endwhile; ?>
                                <?php 
                                mysqli_data_seek($women_cats, 0);
                                while($cat = mysqli_fetch_assoc($women_cats)): ?>
                                <option value="<?php echo $cat['name']; ?>" class="women-cat" style="display:none;">Women - <?php echo $cat['name']; ?></option>
                                <?php endwhile; ?>
                                <?php 
                                mysqli_data_seek($kids_cats, 0);
                                while($cat = mysqli_fetch_assoc($kids_cats)): ?>
                                <option value="<?php echo $cat['name']; ?>" class="kids-cat" style="display:none;">Kids - <?php echo $cat['name']; ?></option>
                                <?php endwhile; ?>
                                <?php 
                                mysqli_data_seek($sale_cats, 0);
                                while($cat = mysqli_fetch_assoc($sale_cats)): ?>
                                <option value="<?php echo $cat['name']; ?>" class="sale-cat" style="display:none;">Sale - <?php echo $cat['name']; ?></option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-palette me-1"></i> Colors (comma)</label>
                            <input type="text" name="colors" class="form-control" placeholder="Red,Blue,Black" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-ruler-combined me-1"></i> Sizes (comma)</label>
                            <input type="text" name="sizes" class="form-control" placeholder="S,M,L,XL" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-boxes me-1"></i> Stock</label>
                            <input type="number" name="stock" class="form-control" value="10" required>
                        </div>
                        
                        <!-- Multiple Image Upload -->
                        <div class="col-md-12 mb-3">
                            <label><i class="fas fa-images me-1"></i> Product Images</label>
                            <div class="row">
                                <div class="col-md-4 mb-2">
                                    <label>Main Image <span class="text-danger">*</span></label>
                                    <input type="file" name="image1" class="form-control" accept="image/*" required onchange="previewImage(this, 'preview1')">
                                    <div id="preview1" class="preview-container"></div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Image 2 (Optional)</label>
                                    <input type="file" name="image2" class="form-control" accept="image/*" onchange="previewImage(this, 'preview2')">
                                    <div id="preview2" class="preview-container"></div>
                                </div>
                                <div class="col-md-4 mb-2">
                                    <label>Image 3 (Optional)</label>
                                    <input type="file" name="image3" class="form-control" accept="image/*" onchange="previewImage(this, 'preview3')">
                                    <div id="preview3" class="preview-container"></div>
                                </div>
                            </div>
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> First image shows on product cards. Additional images appear in quick view.</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background: rgba(108,117,125,0.5); color: #fff; border-radius: 50px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_product" class="btn" style="background: linear-gradient(135deg, #FFD700, #FFC107); color: #000; border-radius: 50px; padding: 8px 25px; font-weight: 600;">
                        <i class="fas fa-save me-2"></i> Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function previewImage(input, previewId) {
    if (input.files && input.files[0]) {
        var reader = new FileReader();
        reader.onload = function(e) {
            $('#' + previewId).html('<img src="' + e.target.result + '" class="image-preview">');
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Show categories based on selected type
$('#productType').change(function() {
    var type = $(this).val();
    $('.men-cat, .women-cat, .kids-cat, .sale-cat').hide();
    $('.' + type + '-cat').show();
    $('#categorySelect').val('');
});

// Delete confirmation
$('.delete-product').click(function() {
    var id = $(this).data('id');
    var name = $(this).data('name');
    Swal.fire({
        title: 'Delete Product?',
        html: `Are you sure you want to delete <strong style="color: #FFD700;">${name}</strong>?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        background: '#1a1a1a',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = 'products.php?delete=' + id;
        }
    });
});

// Mobile Sidebar Toggle
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});

// Close sidebar when clicking outside on mobile
document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        if (sidebar && toggle && !sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});
</script>
</body>
</html>