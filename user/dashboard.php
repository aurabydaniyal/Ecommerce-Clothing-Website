<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'"));
$orders = mysqli_query($conn, "SELECT * FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC LIMIT 5");
$cart_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as c FROM cart WHERE user_id = '$user_id'"))['c'] ?? 0;
$wishlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM wishlist WHERE user_id = '$user_id'"))['c'] ?? 0;
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE user_id = '$user_id'"))['c'] ?? 0;
$total_spent = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE user_id = '$user_id' AND status != 'cancelled'"))['t'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Dashboard - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
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
        
        /* Glassy Stats Cards */
        .stat-card { 
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 25px 20px; 
            text-align: center; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255,215,0,0.15);
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
        }
        .stat-card:hover { 
            transform: translateY(-8px); 
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 15px 35px rgba(255,215,0,0.15);
        }
        .stat-card i { 
            font-size: 45px; 
            color: #FFD700;
            text-shadow: 0 0 10px rgba(255,215,0,0.3);
            margin-bottom: 12px;
            display: block;
        }
        .stat-card h3 { 
            color: #ffffff; 
            margin-top: 8px; 
            font-size: 28px;
            font-weight: 700;
        }
        .stat-card p { 
            color: #aaaaaa; 
            margin-bottom: 0;
            font-size: 14px;
            letter-spacing: 0.5px;
        }
        
        /* Glassy Order Card */
        .order-card { 
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 15px; 
            padding: 18px; 
            margin-bottom: 15px; 
            border-left: 4px solid #FFD700;
            transition: all 0.3s;
        }
        .order-card:hover {
            transform: translateX(5px);
            background: rgba(26, 26, 26, 0.9);
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
        .order-card small { 
            color: #aaaaaa; 
            font-size: 11px;
            letter-spacing: 0.5px;
        }
        
        /* Glassy Card Header */
        .card-header-custom { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            font-weight: 700; 
            padding: 15px 25px; 
            border-radius: 15px 15px 0 0;
            font-size: 18px;
        }
        
        .card-body {
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(10px);
            border-radius: 0 0 15px 15px;
            padding: 20px;
        }
        
        /* Glassy Card */
        .card {
            background: transparent;
            border: none;
            border-radius: 15px;
            overflow: hidden;
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
        
        /* Welcome Text Animation */
        .welcome-text {
            animation: fadeInUp 0.6s ease;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
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
            
            /* Sidebar becomes off-canvas on mobile */
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
            
            /* Overlay when sidebar is open */
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
            .sidebar-dash {
                width: 260px;
            }
            .stat-card {
                padding: 18px 15px;
            }
            .stat-card i {
                font-size: 35px;
            }
            .stat-card h3 {
                font-size: 22px;
            }
        }
        
        @media (max-width: 576px) {
            .dashboard-wrapper { padding: 60px 0 15px; }
            
            .stat-card {
                display: flex;
                align-items: center;
                justify-content: space-between;
                text-align: left;
                padding: 15px 20px;
            }
            
            .stat-card i {
                font-size: 35px;
                margin-bottom: 0;
            }
            
            .stat-card h3 {
                margin-top: 0;
                font-size: 24px;
            }
            
            .stat-card p {
                margin-bottom: 0;
            }
            
            .stat-card > div {
                text-align: right;
            }
            
            .order-card .row [class*="col-"] {
                margin-bottom: 10px;
                text-align: center;
            }
            
            .order-card {
                text-align: center;
            }
            
            .card-header-custom {
                font-size: 16px;
                padding: 12px 20px;
            }
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
                        <div class="avatar-circle" style="width: 80px; height: 80px; background: linear-gradient(135deg, #FFD700, #FFC107); border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                            <i class="fas fa-user" style="font-size: 40px; color: #000;"></i>
                        </div>
                        <h5 class="mt-2" style="color:#ffffff;"><?php echo htmlspecialchars($user['full_name']); ?></h5>
                        <p style="color:#aaaaaa; font-size: 13px;"><?php echo htmlspecialchars($user['email']); ?></p>
                    </div>
                    <hr style="border-color: rgba(255,255,255,0.1);">
                    <nav class="nav flex-column">
                        <a class="nav-link active" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="orders.php">
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
                <h3 class="mb-4 welcome-text" style="color: #FFD700; font-weight: 700;">
                    <i class="fas fa-hand-peace me-2"></i> Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!
                </h3>
                
                <!-- Glassy Stats Cards -->
                <div class="row g-4">
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="stat-card">
                            <i class="fas fa-shopping-cart"></i>
                            <div>
                                <h3><?php echo $cart_count; ?></h3>
                                <p>Cart Items</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="stat-card">
                            <i class="fas fa-heart"></i>
                            <div>
                                <h3><?php echo $wishlist_count; ?></h3>
                                <p>Wishlist</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="stat-card">
                            <i class="fas fa-box"></i>
                            <div>
                                <h3><?php echo $total_orders; ?></h3>
                                <p>Total Orders</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3 col-sm-6 col-12">
                        <div class="stat-card">
                            <i class="fa-solid fa-rupee-sign"></i>
                            <div>
                                <h3><?php echo number_format($total_spent, 2); ?></h3>
                                <p>Total Spent</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Glassy Recent Orders -->
                <div class="mt-5">
                    <div class="card">
                        <div class="card-header-custom">
                            <i class="fas fa-clock me-2"></i> Recent Orders
                        </div>
                        <div class="card-body">
                            <?php if(mysqli_num_rows($orders) == 0): ?>
                                <p style="color:#cccccc; text-align: center; padding: 30px;">
                                    <i class="fas fa-box-open fa-3x mb-3" style="color: #FFD700;"></i><br>
                                    No orders yet.
                                </p>
                                <div class="text-center">
                                    <a href="../index.php" class="btn" style="background: #FFD700; color: #000; border-radius: 50px; padding: 10px 30px; font-weight: 600;">Start Shopping <i class="fas fa-arrow-right ms-2"></i></a>
                                </div>
                            <?php else: ?>
                                <?php while($o = mysqli_fetch_assoc($orders)): ?>
                                <div class="order-card">
                                    <div class="row align-items-center">
                                        <div class="col-md-4 col-sm-12 mb-2 mb-md-0">
                                            <small>Order Number</small><br>
                                            <span class="order-number"><?php echo htmlspecialchars($o['order_number']); ?></span>
                                        </div>
                                        <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
                                            <small>Date</small><br>
                                            <span class="order-date"><?php echo date('M d, Y', strtotime($o['order_date'])); ?></span>
                                        </div>
                                        <div class="col-md-2 col-sm-12 mb-2 mb-md-0">
                                            <small>Total</small><br>
                                            <span class="order-total">$<?php echo number_format($o['total_amount'], 2); ?></span>
                                        </div>
                                        <div class="col-md-3 col-sm-12">
                                            <small>Status</small><br>
                                            <span class="badge bg-<?php echo $o['status'] == 'delivered' ? 'success' : 'warning'; ?>">
                                                <i class="fas <?php echo $o['status'] == 'delivered' ? 'fa-check-circle' : 'fa-clock'; ?> me-1"></i>
                                                <?php echo ucfirst($o['status']); ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endwhile; ?>
                                <div class="text-center mt-3">
                                    <a href="orders.php" class="btn" style="background: rgba(255,215,0,0.1); color: #FFD700; border: 1px solid #FFD700; border-radius: 50px; padding: 8px 25px; font-weight: 500;">
                                        View All Orders <i class="fas fa-arrow-right ms-2"></i>
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
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
function updateCartCount() {
    $.ajax({url: '../get-cart-count.php', success: function(data) {$('#cartCount').text(data);}});
}
function updateWishlistCount() {
    $.ajax({url: '../get-wishlist-count.php', success: function(data) {$('#wishlistCount').text(data);}});
}
updateCartCount();
updateWishlistCount();

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
</script>
</body>
</html>