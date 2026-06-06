<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$product_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if($product_id == 0) {
    header('Location: products.php');
    exit();
}

$result = mysqli_query($conn, "SELECT * FROM products WHERE id = $product_id");
$product = mysqli_fetch_assoc($result);

if(!$product) {
    header('Location: products.php');
    exit();
}

// Update product with multiple images
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_product'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $price = $_POST['price'];
    $sale_price = !empty($_POST['sale_price']) ? $_POST['sale_price'] : 'NULL';
    $type = $_POST['type'];
    $colors = mysqli_real_escape_string($conn, $_POST['colors']);
    $sizes = mysqli_real_escape_string($conn, $_POST['sizes']);
    $stock = $_POST['stock'];
    
    $target_dir = "../uploads/products/";
    if(!file_exists($target_dir)) mkdir($target_dir, 0777, true);
    
    // Handle main image upload
    $image1 = $product['image_url'];
    if(isset($_FILES['image1']) && $_FILES['image1']['error'] == 0) {
        $filename1 = time() . '_1_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image1']['name']);
        move_uploaded_file($_FILES['image1']['tmp_name'], $target_dir . $filename1);
        $image1 = "uploads/products/" . $filename1;
    }
    
    // Handle image2 upload
    $image2 = $product['image2'];
    if(isset($_FILES['image2']) && $_FILES['image2']['error'] == 0) {
        $filename2 = time() . '_2_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image2']['name']);
        move_uploaded_file($_FILES['image2']['tmp_name'], $target_dir . $filename2);
        $image2 = "uploads/products/" . $filename2;
    }
    
    // Handle image3 upload
    $image3 = $product['image3'];
    if(isset($_FILES['image3']) && $_FILES['image3']['error'] == 0) {
        $filename3 = time() . '_3_' . preg_replace('/[^a-zA-Z0-9._-]/', '', $_FILES['image3']['name']);
        move_uploaded_file($_FILES['image3']['tmp_name'], $target_dir . $filename3);
        $image3 = "uploads/products/" . $filename3;
    }
    
    $sql = "UPDATE products SET 
            name = '$name',
            description = '$description',
            price = '$price',
            sale_price = " . ($sale_price != 'NULL' ? "'$sale_price'" : "NULL") . ",
            type = '$type',
            colors = '$colors',
            sizes = '$sizes',
            stock = '$stock',
            image_url = '$image1',
            image2 = '$image2',
            image3 = '$image3'
            WHERE id = $product_id";
    
    if(mysqli_query($conn, $sql)) {
        header('Location: products.php?updated=1');
        exit();
    }
}

if(isset($_POST['delete_product'])) {
    mysqli_query($conn, "DELETE FROM products WHERE id = $product_id");
    header('Location: products.php?deleted=1');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Edit Product - Admin</title>
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
           GLASSY FORM CONTAINER
           ============================================ */
        .form-container { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(15px);
            border-radius: 24px; 
            padding: 30px; 
            border: 1px solid rgba(255,215,0,0.2);
            transition: all 0.3s;
        }
        .form-container:hover {
            border-color: rgba(255,215,0,0.4);
        }
        .form-container h2 { 
            color: #FFD700;
            margin-bottom: 20px;
            font-weight: 700;
        }
        
        /* Form Controls */
        .form-control { 
            background: rgba(42, 42, 42, 0.9);
            border: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-control, 
        .form-select,
        input.form-control,
        select.form-select,
        textarea.form-control {
            color: #ffffff !important;
        }

        .form-control:focus { 
            border-color: #FFD700; 
            box-shadow: 0 0 0 3px rgba(255,215,0,0.15); 
            outline: none;
            background: rgba(42, 42, 42, 1);
        }
        label {
            color: #fff;
            font-weight: 500;
            margin-bottom: 8px;
        }
        label i {
            color: #FFD700;
            margin-right: 6px;
        }
        .text-muted {
            color: #aaa !important;
            font-size: 11px;
        }
        
        /* Image Upload Box */
        .image-upload-box { 
            border: 2px dashed rgba(255,255,255,0.2);
            background: rgba(42, 42, 42, 0.5);
            border-radius: 16px; 
            padding: 20px 15px; 
            text-align: center; 
            transition: all 0.3s;
        }
        .image-upload-box:hover {
            border-color: rgba(255,215,0,0.4);
        }
        .image-upload-box label {
            color: #FFD700;
            font-weight: 600;
        }
        
        /* Buttons */
        .btn-update { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            border: none; 
            padding: 12px 30px; 
            border-radius: 50px; 
            font-weight: 600; 
            transition: all 0.3s;
        }
        .btn-update:hover { 
            transform: translateY(-2px); 
            box-shadow: 0 8px 20px rgba(255,215,0,0.3);
        }
        .btn-delete { 
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff; 
            border: none; 
            padding: 12px 30px; 
            border-radius: 50px; 
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-delete:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(220,53,69,0.3);
        }
        .btn-cancel { 
            background: rgba(108, 117, 125, 0.8);
            color: #fff; 
            border: none; 
            padding: 12px 30px; 
            border-radius: 50px;
            transition: all 0.3s;
        }
        .btn-cancel:hover {
            background: #5a6268;
            transform: translateY(-2px);
        }
        
        /* Preview Images */
        .preview-img { 
            max-width: 100px; 
            border-radius: 12px; 
            margin-top: 10px; 
            border: 2px solid #FFD700;
            padding: 3px;
        }
        
        /* Back Button */
        .btn-back {
            background: rgba(255,215,0,0.15);
            color: #FFD700;
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 50px;
            padding: 8px 20px;
            transition: all 0.3s;
        }
        .btn-back:hover {
            background: rgba(255,215,0,0.25);
            transform: translateX(-3px);
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
            .d-flex.justify-content-between { flex-direction: column; gap: 15px; align-items: flex-start; }
            .form-container { padding: 20px; }
            h2 { font-size: 22px; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            .form-container { padding: 15px; }
            h2 { font-size: 20px; }
            .col-md-6 { width: 100%; }
            .btn-update, .btn-delete, .btn-cancel { 
                padding: 10px 20px; 
                font-size: 14px; 
                width: 100%;
                text-align: center;
            }
            .mt-4.d-flex.gap-3 { 
                flex-direction: column; 
                gap: 10px !important; 
            }
            .image-upload-box { margin-bottom: 15px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            h2 { font-size: 18px; }
            .form-control { padding: 8px 12px; }
            label { font-size: 13px; }
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
            <div class="form-container">
                <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                    <h2><i class="fas fa-edit me-2"></i> Edit Product</h2>
                    <a href="products.php" class="btn-back"><i class="fas fa-arrow-left me-2"></i> Back to Products</a>
                </div>
                
                <form method="POST" enctype="multipart/form-data">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-tag"></i> Product Name</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-layer-group"></i> Category Type</label>
                            <select name="type" class="form-control" required>
                                <option value="men" <?php echo $product['type'] == 'men' ? 'selected' : ''; ?>>Men</option>
                                <option value="women" <?php echo $product['type'] == 'women' ? 'selected' : ''; ?>>Women</option>
                                <option value="kids" <?php echo $product['type'] == 'kids' ? 'selected' : ''; ?>>Kids</option>
                                <option value="sale" <?php echo $product['type'] == 'sale' ? 'selected' : ''; ?>>Sale</option>
                            </select>
                        </div>
                        <div class="col-md-12 mb-3">
                            <label><i class="fas fa-align-left"></i> Description</label>
                            <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($product['description']); ?></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-dollar-sign"></i> Price (Rs )</label>
                            <input type="number" step="0.01" name="price" class="form-control" value="<?php echo $product['price']; ?>" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-percent"></i> Sale Price (Rs )</label>
                            <input type="number" step="0.01" name="sale_price" class="form-control" value="<?php echo $product['sale_price']; ?>" placeholder="Leave empty if no sale">
                            <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Leave empty if not on sale</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-palette"></i> Colors (comma separated)</label>
                            <input type="text" name="colors" class="form-control" value="<?php echo htmlspecialchars($product['colors']); ?>" required>
                            <small class="text-muted">Example: Red,Blue,Black</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-ruler-combined"></i> Sizes (comma separated)</label>
                            <input type="text" name="sizes" class="form-control" value="<?php echo htmlspecialchars($product['sizes']); ?>" required>
                            <small class="text-muted">Example: S,M,L,XL</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label><i class="fas fa-boxes"></i> Stock Quantity</label>
                            <input type="number" name="stock" class="form-control" value="<?php echo $product['stock']; ?>" required>
                        </div>
                        
                        <!-- Multiple Image Upload Section -->
                        <div class="col-md-12 mb-3">
                            <label style="font-weight: bold; margin-bottom: 15px; display: block;"><i class="fas fa-images"></i> Product Images</label>
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <div class="image-upload-box">
                                        <label><i class="fas fa-image"></i> Main Image</label>
                                        <input type="file" name="image1" class="form-control mt-2" accept="image/*" onchange="previewImage(this, 'preview1')">
                                        <?php if($product['image_url']): ?>
                                            <img src="<?php echo $product['image_url']; ?>" class="preview-img" id="current1">
                                        <?php endif; ?>
                                        <div id="preview1" class="mt-2"></div>
                                        <small class="text-muted"><i class="fas fa-star-of-life me-1"></i> Shown on product cards</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="image-upload-box">
                                        <label><i class="fas fa-image"></i> Image 2</label>
                                        <input type="file" name="image2" class="form-control mt-2" accept="image/*" onchange="previewImage(this, 'preview2')">
                                        <?php if($product['image2']): ?>
                                            <img src="<?php echo $product['image2']; ?>" class="preview-img" id="current2">
                                        <?php endif; ?>
                                        <div id="preview2" class="mt-2"></div>
                                        <small class="text-muted"><i class="fas fa-eye me-1"></i> Shown in quick view</small>
                                    </div>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <div class="image-upload-box">
                                        <label><i class="fas fa-image"></i> Image 3</label>
                                        <input type="file" name="image3" class="form-control mt-2" accept="image/*" onchange="previewImage(this, 'preview3')">
                                        <?php if($product['image3']): ?>
                                            <img src="<?php echo $product['image3']; ?>" class="preview-img" id="current3">
                                        <?php endif; ?>
                                        <div id="preview3" class="mt-2"></div>
                                        <small class="text-muted"><i class="fas fa-eye me-1"></i> Shown in quick view</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 d-flex gap-3 flex-wrap">
                        <button type="submit" name="update_product" class="btn-update">
                            <i class="fas fa-save me-2"></i> Update Product
                        </button>
                        <button type="submit" name="delete_product" class="btn-delete" id="deleteBtn">
                            <i class="fas fa-trash me-2"></i> Delete Product
                        </button>
                        <a href="products.php" class="btn-cancel">
                            <i class="fas fa-times me-2"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
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
            $('#' + previewId).html('<img src="' + e.target.result + '" style="max-width:100px; border-radius:12px; margin-top:10px; border:2px solid #FFD700; padding:3px;">');
            // Hide current image
            $(input).closest('.image-upload-box').find('.preview-img').hide();
        }
        reader.readAsDataURL(input.files[0]);
    }
}

// Delete confirmation with SweetAlert
document.getElementById('deleteBtn').addEventListener('click', function(event) {
    event.preventDefault();
    Swal.fire({
        title: 'Delete Product?',
        text: 'This action cannot be undone! All product data will be permanently removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Yes, delete it!',
        cancelButtonText: 'Cancel',
        background: '#1a1a1a',
        color: '#fff'
    }).then((result) => {
        if (result.isConfirmed) {
            // Create a hidden form and submit
            var form = document.createElement('form');
            form.method = 'POST';
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'delete_product';
            input.value = '1';
            form.appendChild(input);
            document.body.appendChild(form);
            form.submit();
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