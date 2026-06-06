<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle order cancellation
if(isset($_GET['cancel']) && isset($_GET['order_id'])) {
    $order_id = intval($_GET['order_id']);
    $cancel_token = $_GET['cancel'];
    
    $check_order = mysqli_query($conn, "SELECT status, order_number FROM orders WHERE id = '$order_id' AND user_id = '$user_id'");
    $order_data = mysqli_fetch_assoc($check_order);
    
    if($order_data && $order_data['status'] == 'pending') {
        mysqli_query($conn, "UPDATE orders SET status = 'cancelled' WHERE id = '$order_id'");
        
        $items = mysqli_query($conn, "SELECT product_id, quantity FROM order_items WHERE order_id = '$order_id'");
        while($item = mysqli_fetch_assoc($items)) {
            mysqli_query($conn, "UPDATE products SET stock = stock + {$item['quantity']} WHERE id = '{$item['product_id']}'");
        }
        
        mysqli_query($conn, "INSERT INTO order_tracking (order_id, status, notes, updated_by) VALUES ('$order_id', 'cancelled', 'Order cancelled by customer', '{$_SESSION['user_id']}')");
        
        header('Location: orders.php?cancelled=1');
        exit();
    } else {
        header('Location: orders.php?error=1');
        exit();
    }
}

$orders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC");
$cart_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as c FROM cart WHERE user_id = '$user_id'"))['c'] ?? 0;
$wishlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM wishlist WHERE user_id = '$user_id'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Orders - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; background: #0a0a0a; }
        
        /* ============================================
           GLASSY MODERN NAVBAR
           ============================================ */
        .navbar { 
            background: rgba(10, 10, 10, 0.95) !important; 
            backdrop-filter: blur(10px);
            padding: 12px 0; 
            border-bottom: 2px solid #FFD700;
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .navbar-brand { 
            font-size: 24px; 
            font-weight: 700; 
            color: #FFD700 !important;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
        }
        .navbar-nav .nav-link { 
            color: #ffffff !important; 
            font-weight: 500; 
            padding: 8px 16px; 
            transition: all 0.3s;
            border-radius: 8px;
        }
        .navbar-nav .nav-link:hover { 
            color: #FFD700 !important; 
            background: rgba(255,215,0,0.1);
            transform: translateY(-2px);
        }
        .dropdown-menu { 
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,215,0,0.3);
            border-radius: 12px;
        }
        .dropdown-item { 
            color: #ffffff !important;
            transition: all 0.3s;
            border-radius: 8px;
        }
        .dropdown-item:hover { 
            background: #FFD700; 
            color: #000 !important;
            transform: translateX(5px);
        }
        .nav-icon { 
            color: #ffffff; 
            position: relative; 
            margin: 0 8px; 
            font-size: 20px; 
            text-decoration: none; 
            transition: all 0.3s;
        }
        .nav-icon:hover { 
            color: #FFD700;
            transform: translateY(-2px);
        }
        .nav-icon .badge { 
            position: absolute; 
            top: -10px; 
            right: -12px; 
            background: #FFD700; 
            color: #000; 
            font-size: 10px; 
            padding: 2px 6px; 
            border-radius: 50%;
            box-shadow: 0 0 5px rgba(255,215,0,0.5);
        }
        .btn-user { 
            background: rgba(26, 26, 26, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid #FFD700; 
            color: #FFD700; 
            padding: 8px 18px; 
            border-radius: 50px; 
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-user:hover {
            background: #FFD700;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        
        /* Sidebar Toggle Button */
        .sidebar-toggle {
            display: none;
            position: fixed;
            top: 80px;
            left: 15px;
            z-index: 999;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            width: 45px;
            height: 45px;
            border-radius: 50%;
            cursor: pointer;
            font-size: 18px;
            box-shadow: 0 5px 20px rgba(255,215,0,0.3);
            transition: all 0.3s;
        }
        
        .sidebar-toggle:hover {
            transform: scale(1.1);
            background: #FFD700;
            box-shadow: 0 8px 25px rgba(255,215,0,0.5);
        }
        
        /* ============================================
           GLASSY DASHBOARD WRAPPER
           ============================================ */
        .dashboard-wrapper { 
            padding: 100px 0 50px; 
            background: radial-gradient(circle at 20% 50%, rgba(255,215,0,0.05), transparent);
        }
        
        /* Glassy Sidebar */
        .sidebar-dash {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            padding: 25px;
            transition: all 0.3s;
            position: sticky;
            top: 100px;
            border: 1px solid rgba(255,215,0,0.2);
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        }
        
        .sidebar-dash:hover {
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 10px 35px rgba(255,215,0,0.1);
        }
        
        .sidebar-dash .nav-link {
            color: #ffffff;
            padding: 12px 18px;
            border-radius: 12px;
            text-decoration: none;
            display: block;
            margin: 8px 0;
            transition: all 0.3s;
            font-weight: 500;
        }
        
        .sidebar-dash .nav-link:hover, 
        .sidebar-dash .nav-link.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            transform: translateX(8px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        
        .sidebar-dash .nav-link i {
            width: 28px;
            margin-right: 12px;
            font-size: 18px;
        }
        
        .sidebar-dash .nav-link.text-danger:hover {
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff;
            box-shadow: 0 5px 15px rgba(220,53,69,0.3);
        }
        
        /* Glassy Card */
        .card {
            background: transparent;
            border: none;
            border-radius: 20px;
            overflow: hidden;
        }
        
        .card-header-custom { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            font-weight: 700; 
            padding: 15px 25px; 
            border-radius: 20px 20px 0 0;
            font-size: 18px;
        }
        
        .card-body {
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 0 0 20px 20px;
            padding: 25px;
        }
        
        /* Glassy Order Card */
        .order-card { 
            background: rgba(26, 26, 26, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 15px; 
            padding: 20px; 
            margin-bottom: 15px; 
            border-left: 4px solid #FFD700;
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateX(5px);
            background: rgba(26, 26, 26, 0.85);
            box-shadow: 0 5px 20px rgba(0,0,0,0.3);
        }
        .order-card .order-number { 
            color: #FFD700; 
            font-weight: 700;
            font-size: 15px;
        }
        .order-card .order-date, .order-card .order-total { 
            color: #ffffff; 
        }
        .order-card strong { 
            color: #ffffff;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .order-card small { 
            color: #aaaaaa; 
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        
        /* Badge Styles */
        .badge {
            padding: 5px 12px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 12px;
        }
        
        .bg-success {
            background: linear-gradient(135deg, #28a745, #20c997);
        }
        
        .bg-warning {
            background: linear-gradient(135deg, #ffc107, #ff9800);
            color: #000;
        }
        
        .bg-danger {
            background: linear-gradient(135deg, #dc3545, #c82333);
        }
        
        /* Cancel Button */
        .btn-cancel-order { 
            background: linear-gradient(135deg, #dc3545, #c82333);
            color: #fff; 
            border: none; 
            padding: 8px 20px; 
            border-radius: 50px; 
            font-size: 12px; 
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-cancel-order:hover { 
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(220,53,69,0.4);
        }
        
        /* Alert Styles */
        .alert-custom { 
            background: rgba(40, 167, 69, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #28a745; 
            color: #6bff8a; 
            padding: 15px; 
            border-radius: 12px; 
            margin-bottom: 20px;
        }
        .alert-danger-custom { 
            background: rgba(220, 53, 69, 0.15);
            backdrop-filter: blur(10px);
            border: 1px solid #dc3545; 
            color: #ff6b6b; 
            padding: 15px; 
            border-radius: 12px; 
            margin-bottom: 20px;
        }
        
        /* Footer */
        .footer { 
            background: rgba(10, 10, 10, 0.95);
            backdrop-filter: blur(10px);
            padding: 40px 0 20px; 
            margin-top: 60px; 
            border-top: 1px solid rgba(255,215,0,0.2);
            text-align: center; 
            color: #888;
        }
        
        /* Empty State */
        .empty-orders {
            text-align: center;
            padding: 40px;
        }
        
        .empty-orders i {
            font-size: 60px;
            color: #FFD700;
            margin-bottom: 20px;
            opacity: 0.7;
        }
        
        /* ============================================
           MOBILE RESPONSIVE STYLES
           ============================================ */
        @media (max-width: 992px) {
            .dashboard-wrapper { padding: 80px 0 30px; }
            .navbar-brand { font-size: 20px; }
            .navbar-nav .nav-link { padding: 6px 12px; font-size: 14px; }
            .sidebar-toggle {
                display: block;
            }
            
            .sidebar-dash {
                position: fixed;
                top: 0;
                left: -300px;
                width: 280px;
                height: 100%;
                z-index: 1000;
                border-radius: 0;
                overflow-y: auto;
                transition: left 0.3s ease;
                padding-top: 80px;
            }
            
            .sidebar-dash.show {
                left: 0;
            }
            
            .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.8);
                backdrop-filter: blur(5px);
                z-index: 999;
                display: none;
            }
            
            .sidebar-overlay.show {
                display: block;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-wrapper { padding: 70px 0 20px; }
            .sidebar-dash { width: 260px; }
            
            .order-card .row [class*="col-"] {
                margin-bottom: 10px;
                text-align: center;
            }
            
            .order-card .text-end {
                text-align: center !important;
            }
            
            .order-card {
                text-align: center;
            }
            
            .btn-cancel-order {
                width: 100%;
                margin-top: 8px;
            }
            
            .card-header-custom {
                font-size: 16px;
                text-align: center;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-wrapper { padding: 60px 0 15px; }
            .sidebar-dash { width: 240px; }
            .order-card { padding: 15px; }
            .order-card .order-number { font-size: 14px; }
            .badge { font-size: 10px; }
            .btn-cancel-order { padding: 6px 15px; font-size: 11px; }
            .card-header-custom { font-size: 14px; }
        }
    </style>
</head>
<body>

<!-- Sidebar Toggle Button (Mobile Only) -->
<button class="sidebar-toggle" id="sidebarToggle">
    <i class="fas fa-bars"></i>
</button>

<!-- Sidebar Overlay (Mobile Only) -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Glassy Navbar -->
<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container">
        <a class="navbar-brand" href="../index.php"><i class="fas fa-tshirt"></i> UHD-WEARS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon" style="background-image: url('data:image/svg+xml,%3csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 30 30\'%3e%3cpath stroke=\'%23FFD700\' stroke-linecap=\'round\' stroke-miterlimit=\'10\' stroke-width=\'2\' d=\'M4 7h22M4 15h22M4 23h22\'/%3e%3c/svg%3e');"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item"><a class="nav-link" href="../index.php"><i class="fas fa-home me-1"></i> Home</a></li>
                <li class="nav-item"><a class="nav-link" href="../men.php"><i class="fas fa-male me-1"></i> Men</a></li>
                <li class="nav-item"><a class="nav-link" href="../women.php"><i class="fas fa-female me-1"></i> Women</a></li>
                <li class="nav-item"><a class="nav-link" href="../kids.php"><i class="fas fa-child me-1"></i> Kids</a></li>
                <li class="nav-item"><a class="nav-link" href="../sale.php"><i class="fas fa-tag me-1"></i> Sale</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2">
                <a href="../wishlist.php" class="nav-icon"><i class="fas fa-heart"></i><span class="badge" id="wishlistCount"><?php echo $wishlist_count; ?></span></a>
                <a href="../cart.php" class="nav-icon"><i class="fas fa-shopping-cart"></i><span class="badge" id="cartCount"><?php echo $cart_count; ?></span></a>
                <div class="dropdown">
                    <button class="btn-user dropdown-toggle" data-bs-toggle="dropdown"><i class="fas fa-user-circle"></i> <?php echo htmlspecialchars($_SESSION['username']); ?></button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i> Dashboard</a></li>
                        <li><a class="dropdown-item" href="orders.php"><i class="fas fa-shopping-bag me-2"></i> My Orders</a></li>
                        <li><a class="dropdown-item" href="track-order.php"><i class="fas fa-truck me-2"></i> Track Order</a></li>
                        <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-circle me-2"></i> My Profile</a></li>
                        <li><a class="dropdown-item" href="edit-profile.php"><i class="fas fa-user-edit me-2"></i> Edit Profile</a></li>
                        <li><a class="dropdown-item" href="edit-address.php"><i class="fas fa-map-marker-alt me-2"></i> Edit Address</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="../logout.php"><i class="fas fa-sign-out-alt me-2"></i> Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</nav>

<!-- Dashboard Content -->
<div class="dashboard-wrapper">
    <div class="container">
        <div class="row">
            <!-- Glassy Sidebar -->
            <div class="col-md-3 mb-4">
                <div class="sidebar-dash" id="sidebar">
                    <div class="text-center mb-4">
                        <div class="avatar-circle" style="width: 70px; height: 70px; background: linear-gradient(135deg, #FFD700, #FFC107); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="fas fa-shopping-bag" style="font-size: 32px; color: #000;"></i>
                        </div>
                        <h5 class="mt-2" style="color:#ffffff;">My Orders</h5>
                        <p style="color:#aaaaaa; font-size: 13px;"><?php echo htmlspecialchars($_SESSION['username']); ?></p>
                    </div>
                    <hr style="border-color: rgba(255,255,255,0.1);">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                        <a class="nav-link" href="track-order.php">
                            <i class="fas fa-truck"></i> Track Order
                        </a>
                        <a class="nav-link" href="profile.php">
                            <i class="fas fa-user-circle"></i> My Profile
                        </a>
                        <a class="nav-link" href="edit-profile.php">
                            <i class="fas fa-user-edit"></i> Edit Profile
                        </a>
                        <a class="nav-link" href="edit-address.php">
                            <i class="fas fa-map-marker-alt"></i> Edit Address
                        </a>
                        <hr style="border-color: rgba(255,255,255,0.1);">
                        <a class="nav-link text-danger" href="../logout.php">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header-custom">
                        <i class="fas fa-box me-2"></i> My Orders
                    </div>
                    <div class="card-body">
                        <?php if(isset($_GET['cancelled'])): ?>
                        <div class="alert-custom"><i class="fas fa-check-circle me-2"></i> Order cancelled successfully!</div>
                        <?php endif; ?>
                        <?php if(isset($_GET['error'])): ?>
                        <div class="alert-danger-custom"><i class="fas fa-exclamation-circle me-2"></i> Cannot cancel this order. Only pending orders can be cancelled.</div>
                        <?php endif; ?>
                        
                        <?php if(mysqli_num_rows($orders) == 0): ?>
                            <div class="empty-orders">
                                <i class="fas fa-box-open"></i>
                                <p style="color:#aaa;">No orders yet.</p>
                                <a href="../index.php" class="btn" style="background: #FFD700; color: #000; border-radius: 50px; padding: 10px 30px; font-weight: 600; margin-top: 10px;">Start Shopping <i class="fas fa-arrow-right ms-2"></i></a>
                            </div>
                        <?php else: ?>
                            <?php while($o = mysqli_fetch_assoc($orders)): ?>
                            <div class="order-card">
                                <div class="row align-items-center">
                                    <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
                                        <strong><i class="fas fa-hashtag me-1"></i> Order #:</strong><br>
                                        <span class="order-number"><?php echo htmlspecialchars($o['order_number']); ?></span>
                                    </div>
                                    <div class="col-md-2 col-sm-12 mb-2 mb-md-0">
                                        <strong><i class="fas fa-calendar-alt me-1"></i> Date:</strong><br>
                                        <span class="order-date"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></span>
                                    </div>
                                    <div class="col-md-2 col-sm-12 mb-2 mb-md-0">
                                        <strong><i class="fas fa-dollar-sign me-1"></i> Total:</strong><br>
                                        <span class="order-total">Rs <?php echo number_format($o['total_amount'], 2); ?></span>
                                    </div>
                                    <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
                                        <strong><i class="fas fa-info-circle me-1"></i> Status:</strong><br>
                                        <span class="badge bg-<?php echo $o['status'] == 'delivered' ? 'success' : ($o['status'] == 'cancelled' ? 'danger' : 'warning'); ?>">
                                            <i class="fas <?php echo $o['status'] == 'delivered' ? 'fa-check-circle' : ($o['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-clock'); ?> me-1"></i>
                                            <?php echo ucfirst($o['status']); ?>
                                        </span>
                                    </div>
                                    <div class="col-md-2 col-sm-12 text-center text-md-end">
                                        <?php if($o['status'] == 'pending'): ?>
                                        <button class="btn-cancel-order cancel-order-btn" data-order-id="<?php echo $o['id']; ?>" data-order-number="<?php echo htmlspecialchars($o['order_number']); ?>">
                                            <i class="fas fa-times-circle"></i> Cancel
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Footer -->
<footer class="footer">
    <div class="container">
        <p>&copy; 2024 UHD-Wears. All rights reserved. | Designed with <i class="fas fa-heart" style="color: #FFD700;"></i> for fashion lovers</p>
    </div>
</footer>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // Cancel order with SweetAlert
    $('.cancel-order-btn').click(function() {
        var orderId = $(this).data('order-id');
        var orderNumber = $(this).data('order-number');
        
        Swal.fire({
            title: 'Cancel Order?',
            html: `Are you sure you want to cancel order <strong style="color: #FFD700;">${orderNumber}</strong>?<br><br>This action cannot be undone and stock will be restored.`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Yes, cancel it!',
            cancelButtonText: 'No, keep it',
            background: '#1a1a1a',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = 'orders.php?cancel=1&order_id=' + orderId;
            }
        });
    });
});

// Mobile Sidebar Toggle
const sidebarToggle = document.getElementById('sidebarToggle');
const sidebar = document.getElementById('sidebar');
const sidebarOverlay = document.getElementById('sidebarOverlay');

function openSidebar() {
    sidebar.classList.add('show');
    sidebarOverlay.classList.add('show');
    document.body.style.overflow = 'hidden';
}

function closeSidebar() {
    sidebar.classList.remove('show');
    sidebarOverlay.classList.remove('show');
    document.body.style.overflow = '';
}

if (sidebarToggle) {
    sidebarToggle.addEventListener('click', openSidebar);
}

if (sidebarOverlay) {
    sidebarOverlay.addEventListener('click', closeSidebar);
}

// Close sidebar when clicking a link on mobile
const sidebarLinks = document.querySelectorAll('#sidebar .nav-link');
sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            closeSidebar();
        }
    });
});

// Close sidebar on window resize if open
window.addEventListener('resize', function() {
    if (window.innerWidth > 992 && sidebar.classList.contains('show')) {
        closeSidebar();
    }
});

// Update cart and wishlist counts
function updateCartCount() {
    $.ajax({url: '../get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
}
function updateWishlistCount() {
    $.ajax({url: '../get-wishlist-count.php', success: function(data) {$('#wishlistCount').text(data);}});
}
updateCartCount();
updateWishlistCount();
</script>
</body>
</html>