<?php
session_start();
require_once '../includes/db_connection.php';
require_once '../includes/functions.php';

requireLogin();

$user_id = $_SESSION['user_id'];
$user = getUserById($user_id);

// Get cart and wishlist counts
$cart_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as c FROM cart WHERE user_id = '$user_id'"))['c'] ?? 0;
$wishlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM wishlist WHERE user_id = '$user_id'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>My Profile - UHD-Wears</title>
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
        
        /* Glassy Profile Card */
        .profile-card {
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(15px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.3);
            border: 1px solid rgba(255,215,0,0.15);
            transition: all 0.3s;
        }
        
        .profile-card:hover {
            border-color: rgba(255,215,0,0.35);
            transform: translateY(-3px);
        }
        
        .profile-card .card-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            font-weight: 700;
            padding: 18px 25px;
            border-bottom: none;
        }
        
        .profile-card .card-header h4 {
            margin: 0;
            font-size: 18px;
        }
        
        .profile-card .card-body {
            padding: 30px;
        }
        
        .profile-card p {
            margin-bottom: 15px;
            color: #ffffff;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255,255,255,0.05);
            transition: all 0.3s;
        }
        
        .profile-card p:hover {
            transform: translateX(5px);
            border-bottom-color: rgba(255,215,0,0.3);
        }
        
        .profile-card strong {
            color: #FFD700;
            font-weight: 600;
            min-width: 130px;
            display: inline-block;
        }
        
        .profile-card strong i {
            width: 25px;
            margin-right: 8px;
            color: #FFD700;
        }
        
        .profile-card hr {
            border-color: rgba(255,255,255,0.1);
            margin: 25px 0;
        }
        
        /* Edit Profile Button */
        .edit-profile-btn {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .edit-profile-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,215,0,0.3);
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
            
            .profile-card .card-body .row [class*="col-"] {
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 768px) {
            .dashboard-wrapper { padding: 70px 0 20px; }
            .sidebar-dash { width: 260px; }
            .profile-card .card-body { padding: 20px; }
            .profile-card strong { min-width: 110px; font-size: 13px; }
            .profile-card p { font-size: 13px; }
            .edit-profile-btn { width: 100%; text-align: center; }
        }
        
        @media (max-width: 576px) {
            .dashboard-wrapper { padding: 60px 0 15px; }
            .profile-card .card-body { padding: 15px; }
            .profile-card p { font-size: 12px; margin-bottom: 10px; }
            .profile-card strong { min-width: 100px; font-size: 12px; }
            .profile-card .card-header h4 { font-size: 16px; text-align: center; }
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
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                        <a class="nav-link" href="track-order.php">
                            <i class="fas fa-truck"></i> Track Order
                        </a>
                        <a class="nav-link active" href="profile.php">
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
                <div class="profile-card">
                    <div class="card-header">
                        <h4><i class="fas fa-user-circle me-2"></i> My Profile</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-user"></i> Full Name:</strong> <?php echo htmlspecialchars($user['full_name'] ?: 'Not provided'); ?></p>
                                <p><strong><i class="fas fa-at"></i> Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
                                <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
                                <p><strong><i class="fas fa-phone"></i> Phone:</strong> <?php echo htmlspecialchars($user['phone'] ?: 'Not provided'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong><i class="fas fa-location-dot"></i> Address:</strong> <?php echo htmlspecialchars($user['address'] ?: 'Not provided'); ?></p>
                                <p><strong><i class="fas fa-city"></i> City:</strong> <?php echo htmlspecialchars($user['city'] ?: 'Not provided'); ?></p>
                                <p><strong><i class="fas fa-map"></i> State:</strong> <?php echo htmlspecialchars($user['state'] ?: 'Not provided'); ?></p>
                                <p><strong><i class="fas fa-mail-bulk"></i> ZIP Code:</strong> <?php echo htmlspecialchars($user['zip_code'] ?: 'Not provided'); ?></p>
                            </div>
                        </div>
                        <hr>
                        <div class="row">
                            <div class="col-md-12">
                                <p><strong><i class="fas fa-calendar-alt"></i> Member Since:</strong> <?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                        
                        <hr>
                        <div class="text-center mt-3">
                            <a href="edit-profile.php" class="edit-profile-btn">
                                <i class="fas fa-user-edit me-2"></i> Edit Profile
                            </a>
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
// Update cart and wishlist counts
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