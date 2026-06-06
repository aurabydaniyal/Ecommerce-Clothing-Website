<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Add category
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_category'])) {
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    
    // Escape special characters
    $name = addslashes($name);
    
    $sql = "INSERT INTO categories (name, type) VALUES ('$name', '$type')";
    
    if(mysqli_query($conn, $sql)) {
        header('Location: categories.php?added=1');
        exit();
    } else {
        $error = "Error: " . mysqli_error($conn);
    }
}

// Delete category
if(isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($conn, "DELETE FROM categories WHERE id = '$id'");
    header('Location: categories.php?deleted=1');
    exit();
}

$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY type, display_order");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Categories - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet");
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
           GLASSY TABLE (Black Background, White Text)
           ============================================ */
        .category-table { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            border: 1px solid rgba(255,215,0,0.15);
        }
        
        .category-table th { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            padding: 15px; 
            font-weight: 600;
            border-bottom: none;
        }
        
        .category-table td { 
            padding: 12px 15px; 
            border-bottom: 1px solid rgba(255,255,255,0.1); 
            color: #070707;
            vertical-align: middle;
        }
        
        .category-table tr:hover td {
            background: rgba(255, 232, 104, 0.89);
        }
        
        /* Badge Styles */
        .badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 500;
            font-size: 11px;
        }
        
        .bg-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
        .bg-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .bg-success { background: linear-gradient(135deg, #28a745, #20c997); }
        .bg-warning { background: linear-gradient(135deg, #ffc107, #ff9800); color: #000; }
        
        h2 { 
            color: #FFD700;
            font-weight: 700;
        }
        
        .subtitle {
            color: #aaa;
        }
        
        /* Alert Styles */
        .alert-custom { 
            background: rgba(40,167,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #28a745; 
            color: #6bff8a; 
            padding: 12px 18px; 
            border-radius: 12px;
        }
        
        .alert-danger-custom {
            background: rgba(220,53,69,0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #dc3545;
            color: #ff6b6b;
            padding: 12px 18px;
            border-radius: 12px;
        }
        
        /* Buttons */
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
        
        .btn-delete-cat {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220,53,69,0.3);
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 12px;
            transition: all 0.3s;
        }
        .btn-delete-cat:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
        }
        
        /* Modal Styles */
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
        .text-muted {
            color: #aaa !important;
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
            h2 { font-size: 22px; }
            .category-table { overflow-x: auto; display: block; }
            .category-table table { min-width: 500px; }
            .d-flex.justify-content-between { flex-direction: column; gap: 15px; align-items: flex-start; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            h2 { font-size: 20px; }
            .p-4 { padding: 15px !important; }
            .category-table th, .category-table td { padding: 8px 10px; font-size: 12px; }
            .badge { font-size: 9px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            h2 { font-size: 18px; }
            .category-table th, .category-table td { padding: 6px 8px; font-size: 11px; }
            .btn-delete-cat { padding: 3px 10px; font-size: 10px; }
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
                <a class="nav-link" href="products.php"><i class="fas fa-box"></i> Products</a>
                <a class="nav-link" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="sliders.php"><i class="fas fa-image"></i> Sliders</a>
                <a class="nav-link active" href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 15px;">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <div class="d-flex justify-content-between mb-4 flex-wrap align-items-center">
                <div>
                    <h2><i class="fas fa-tags me-2"></i> Categories</h2>
                    <p class="subtitle">Manage product categories for your store</p>
                </div>
                <button class="btn-warning-custom" data-bs-toggle="modal" data-bs-target="#addModal">
                    <i class="fas fa-plus me-2"></i> Add Category
                </button>
            </div>
            
            <?php if(isset($_GET['added'])): ?>
            <div class="alert-custom mt-2"><i class="fas fa-check-circle me-2"></i> Category added successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['deleted'])): ?>
            <div class="alert-danger-custom mt-2"><i class="fas fa-trash me-2"></i> Category deleted successfully!</div>
            <?php endif; ?>
            <?php if(isset($error)): ?>
            <div class="alert-danger-custom mt-2"><i class="fas fa-exclamation-triangle me-2"></i> <?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="category-table mt-4">
                <div style="overflow-x: auto;">
                    <table class="table mb-0">
                        <thead>
                            <tr>
                                <th><i class="fas fa-hashtag"></i> ID</th>
                                <th><i class="fas fa-tag"></i> Category Name</th>
                                <th><i class="fas fa-layer-group"></i> Type</th>
                                <th><i class="fas fa-cog"></i> Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($c = mysqli_fetch_assoc($categories)): ?>
                            <tr>
                                <td><strong><?php echo $c['id']; ?></strong></td>
                                <td><i class="fas fa-folder-open me-1"></i> <?php echo htmlspecialchars($c['name']); ?></td>
                                <td>
                                    <?php
                                    $type_badge = '';
                                    $type_icon = '';
                                    if($c['type'] == 'men') { $type_badge = 'primary'; $type_icon = 'fa-male'; }
                                    elseif($c['type'] == 'women') { $type_badge = 'danger'; $type_icon = 'fa-female'; }
                                    elseif($c['type'] == 'kids') { $type_badge = 'success'; $type_icon = 'fa-child'; }
                                    elseif($c['type'] == 'sale') { $type_badge = 'warning'; $type_icon = 'fa-tag'; }
                                    ?>
                                    <span class="badge bg-<?php echo $type_badge; ?>">
                                        <i class="fas <?php echo $type_icon; ?> me-1"></i> <?php echo ucfirst($c['type']); ?>
                                    </span>
                                </td>
                                <td>
                                    <button class="btn-delete-cat delete-cat" data-id="<?php echo $c['id']; ?>" data-name="<?php echo htmlspecialchars($c['name']); ?>">
                                        <i class="fas fa-trash-alt me-1"></i> Delete
                                    </button>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Stats Summary -->
            <div class="row mt-4 g-3">
                <div class="col-md-3 col-6">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15); text-align: center;">
                        <i class="fas fa-male" style="color: #007bff; font-size: 24px;"></i>
                        <div style="color: #fff; margin-top: 5px;">Men: <strong style="color: #FFD700;"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM categories WHERE type='men'")); ?></strong></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15); text-align: center;">
                        <i class="fas fa-female" style="color: #dc3545; font-size: 24px;"></i>
                        <div style="color: #fff; margin-top: 5px;">Women: <strong style="color: #FFD700;"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM categories WHERE type='women'")); ?></strong></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15); text-align: center;">
                        <i class="fas fa-child" style="color: #28a745; font-size: 24px;"></i>
                        <div style="color: #fff; margin-top: 5px;">Kids: <strong style="color: #FFD700;"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM categories WHERE type='kids'")); ?></strong></div>
                    </div>
                </div>
                <div class="col-md-3 col-6">
                    <div class="stat-mini" style="background: rgba(26,26,26,0.7); backdrop-filter: blur(10px); border-radius: 16px; padding: 15px; border: 1px solid rgba(255,215,0,0.15); text-align: center;">
                        <i class="fas fa-tag" style="color: #ffc107; font-size: 24px;"></i>
                        <div style="color: #fff; margin-top: 5px;">Sale: <strong style="color: #FFD700;"><?php echo mysqli_num_rows(mysqli_query($conn, "SELECT * FROM categories WHERE type='sale'")); ?></strong></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Category Modal -->
<div class="modal fade" id="addModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-plus-circle me-2"></i> Add New Category</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label><i class="fas fa-tag me-1"></i> Category Name</label>
                        <input type="text" name="name" class="form-control" placeholder="e.g., Winter Sale, Summer Sale, Clearance" required>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> You can use apostrophes (e.g., Men's)</small>
                    </div>
                    <div class="mb-3">
                        <label><i class="fas fa-layer-group me-1"></i> Type / Page</label>
                        <select name="type" class="form-select" required>
                            <option value="men">👔 Men</option>
                            <option value="women">👗 Women</option>
                            <option value="kids">🧸 Kids</option>
                            <option value="sale">🔥 Sale</option>
                        </select>
                        <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Select which page this category belongs to</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn" style="background: rgba(108,117,125,0.5); color: #fff; border-radius: 50px;" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="add_category" class="btn" style="background: linear-gradient(135deg, #FFD700, #FFC107); color: #000; border-radius: 50px; padding: 8px 25px; font-weight: 600;">
                        <i class="fas fa-save me-2"></i> Add Category
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // SweetAlert for Delete Category
    $('.delete-cat').click(function() {
        var catId = $(this).data('id');
        var catName = $(this).data('name');
        Swal.fire({
            title: 'Delete Category?',
            html: `Are you sure you want to delete <strong style="color: #FFD700;">${catName}</strong>?<br><br>Products in this category will not be deleted.<br>This action cannot be undone.`,
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
                window.location.href = 'categories.php?delete=' + catId;
            }
        });
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