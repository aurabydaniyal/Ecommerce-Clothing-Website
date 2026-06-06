<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$order_id = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;

// Get all orders for dropdown
$orders = mysqli_query($conn, "SELECT id, order_number, status FROM orders WHERE user_id = '$user_id' ORDER BY order_date DESC");

// Get selected order details
$selected_order = null;
if($order_id > 0) {
    $selected_order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = '$order_id' AND user_id = '$user_id'"));
}

// Get cart/wishlist counts
$cart_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(quantity) as c FROM cart WHERE user_id = '$user_id'"))['c'] ?? 0;
$wishlist_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM wishlist WHERE user_id = '$user_id'"))['c'] ?? 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Track Order - UHD-Wears</title>
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
        
        /* Glassy Tracking Container */
        .tracking-container { 
            background: rgba(26, 26, 26, 0.7);
            backdrop-filter: blur(15px);
            border-radius: 20px; 
            padding: 30px;
            border: 1px solid rgba(255,215,0,0.15);
            transition: all 0.3s;
        }
        
        .tracking-container:hover {
            border-color: rgba(255,215,0,0.3);
        }
        
        /* Form Select */
        .form-select {
            background: rgba(42, 42, 42, 0.9);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,215,0,0.2);
            color: #ffffff;
            border-radius: 12px;
            padding: 12px 15px;
            transition: all 0.3s;
        }
        
        .form-select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 10px rgba(255,215,0,0.3);
            outline: none;
        }
        
        /* Glassy Order Info Card */
        .order-info-card { 
            background: rgba(26, 26, 26, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 16px; 
            padding: 20px; 
            margin-bottom: 20px; 
            border-left: 4px solid #FFD700;
            transition: all 0.3s;
        }
        
        .order-info-card:hover {
            background: rgba(26, 26, 26, 0.8);
            transform: translateX(5px);
        }
        
        .order-info-card p, .order-info-card .fw-bold { 
            color: #ffffff !important; 
        }
        .order-info-card .text-muted { 
            color: #aaaaaa !important; 
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        
        /* Timeline Styles */
        .timeline { 
            position: relative; 
            padding: 20px 0; 
        }
        
        .timeline-step { 
            display: flex; 
            align-items: flex-start; 
            margin-bottom: 40px; 
            position: relative; 
        }
        
        .timeline-step:not(:last-child)::before { 
            content: ''; 
            position: absolute; 
            left: 20px; 
            top: 40px; 
            width: 2px; 
            height: calc(100% - 20px); 
            background: rgba(255,255,255,0.1); 
        }
        
        .timeline-icon { 
            width: 45px; 
            height: 45px; 
            background: rgba(51, 51, 51, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 50%; 
            display: flex; 
            align-items: center; 
            justify-content: center; 
            margin-right: 20px; 
            z-index: 1; 
            color: #fff; 
            transition: all 0.3s;
        }
        
        .timeline-step.completed .timeline-icon { 
            background: linear-gradient(135deg, #28a745, #20c997);
            box-shadow: 0 0 15px rgba(40,167,69,0.3);
        }
        
        .timeline-step.active .timeline-icon { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            animation: pulse 1.5s infinite;
            box-shadow: 0 0 15px rgba(255,215,0,0.5);
        }
        
        .timeline-content h5 { 
            margin: 0; 
            color: #ffffff;
            font-weight: 600;
        }
        
        .timeline-content p { 
            margin: 5px 0 0; 
            color: #aaaaaa; 
            font-size: 13px;
        }
        
        @keyframes pulse {
            0% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,215,0,0.7); }
            70% { transform: scale(1.05); box-shadow: 0 0 0 10px rgba(255,215,0,0); }
            100% { transform: scale(1); box-shadow: 0 0 0 0 rgba(255,215,0,0); }
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
        
        /* Alert Box */
        .alert-info {
            background: rgba(42, 42, 42, 0.8);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255,215,0,0.2);
            border-radius: 12px;
            color: #ffffff;
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
        .empty-state {
            text-align: center;
            padding: 50px 20px;
        }
        
        .empty-state i {
            font-size: 70px;
            color: #FFD700;
            margin-bottom: 20px;
            opacity: 0.6;
        }
        
        /* Track Button */
        .btn-track {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            font-weight: 600;
            border: none;
            border-radius: 12px;
            padding: 12px 25px;
            transition: all 0.3s;
        }
        
        .btn-track:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 20px rgba(255,215,0,0.4);
        }
        
        /* ============================================
           MOBILE RESPONSIVE STYLES
           ============================================ */
        @media (max-width: 992px) {
            .dashboard-wrapper { padding: 80px 0 30px; }
            .sidebar-toggle { display: block; }
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
            .sidebar-dash.show { left: 0; }
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
            .sidebar-overlay.show { display: block; }
        }
        
        @media (max-width: 768px) {
            .dashboard-wrapper { padding: 70px 0 20px; }
            .tracking-container { padding: 20px; }
            .timeline-step { margin-bottom: 25px; }
            .timeline-icon { width: 38px; height: 38px; font-size: 14px; }
            .timeline-content h5 { font-size: 14px; }
            .timeline-content p { font-size: 12px; }
            .order-info-card .row [class*="col-"] { margin-bottom: 12px; text-align: center; }
            h4 { font-size: 20px; text-align: center; }
        }
        
        @media (max-width: 576px) {
            .dashboard-wrapper { padding: 60px 0 15px; }
            .tracking-container { padding: 15px; }
            .order-info-card { padding: 15px; }
            .timeline-icon { width: 32px; height: 32px; margin-right: 12px; }
            .timeline-content h5 { font-size: 13px; }
            .input-group { flex-direction: column; gap: 10px; }
            .input-group select, .input-group button { width: 100%; border-radius: 12px !important; }
            h4 { font-size: 18px; }
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
                            <i class="fas fa-truck" style="font-size: 32px; color: #000;"></i>
                        </div>
                        <h5 class="mt-2" style="color:#ffffff;">Track Order</h5>
                        <p style="color:#aaaaaa; font-size: 13px;">Monitor your delivery status</p>
                    </div>
                    <hr style="border-color: rgba(255,255,255,0.1);">
                    <nav class="nav flex-column">
                        <a class="nav-link" href="dashboard.php">
                            <i class="fas fa-tachometer-alt"></i> Dashboard
                        </a>
                        <a class="nav-link" href="orders.php">
                            <i class="fas fa-shopping-bag"></i> My Orders
                        </a>
                        <a class="nav-link active" href="track-order.php">
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
                <div class="tracking-container">
                    <h4 style="color: #FFD700; margin-bottom: 20px;">
                        <i class="fas fa-search-location me-2"></i> Track Your Order
                    </h4>
                    
                    <form method="GET" action="" class="mb-4">
                        <div class="input-group">
                            <select name="order_id" class="form-select">
                                <option value="">-- Select an Order to Track --</option>
                                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                                <option value="<?php echo $order['id']; ?>" <?php echo ($order_id == $order['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($order['order_number']); ?> - <?php echo ucfirst($order['status']); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <button type="submit" class="btn-track">
                                <i class="fas fa-search"></i> Track
                            </button>
                        </div>
                    </form>
                    
                    <?php if($selected_order): ?>
                        <!-- Order Details -->
                        <div class="order-info-card">
                            <div class="row align-items-center">
                                <div class="col-md-4 col-sm-12 mb-2 mb-md-0">
                                    <small class="text-muted"><i class="fas fa-hashtag me-1"></i> Order Number</small>
                                    <p class="fw-bold mb-0"><?php echo htmlspecialchars($selected_order['order_number']); ?></p>
                                </div>
                                <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
                                    <small class="text-muted"><i class="fas fa-calendar-alt me-1"></i> Order Date</small>
                                    <p class="fw-bold mb-0"><?php echo date('M d, Y', strtotime($selected_order['order_date'])); ?></p>
                                </div>
                                <div class="col-md-3 col-sm-12 mb-2 mb-md-0">
                                    <small class="text-muted"><i class="fas fa-dollar-sign me-1"></i> Total Amount</small>
                                    <p class="fw-bold mb-0" style="color: #FFD700;">Rs <?php echo number_format($selected_order['total_amount'], 2); ?></p>
                                </div>
                                <div class="col-md-2 col-sm-12">
                                    <small class="text-muted"><i class="fas fa-info-circle me-1"></i> Status</small>
                                    <p><span class="badge bg-<?php echo $selected_order['status'] == 'delivered' ? 'success' : ($selected_order['status'] == 'cancelled' ? 'danger' : 'warning'); ?>">
                                        <i class="fas <?php echo $selected_order['status'] == 'delivered' ? 'fa-check-circle' : ($selected_order['status'] == 'cancelled' ? 'fa-times-circle' : 'fa-clock'); ?> me-1"></i>
                                        <?php echo ucfirst($selected_order['status']); ?>
                                    </span></p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Shipping Address -->
                        <div class="order-info-card" style="border-left-color: #28a745;">
                            <h6 style="color: #FFD700; margin-bottom: 12px;">
                                <i class="fas fa-map-marker-alt me-2"></i> Shipping Address
                            </h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($selected_order['shipping_address'])); ?></p>
                        </div>
                        
                        <!-- Delivery Timeline -->
                        <h5 class="mt-4 mb-3" style="color: #FFD700;">
                            <i class="fas fa-chart-line me-2"></i> Delivery Status
                        </h5>
                        <div class="timeline">
                            <?php
                            $statuses = [
                                'pending' => ['label' => 'Order Placed', 'icon' => 'fa-clock', 'desc' => 'Your order has been received and is pending confirmation'],
                                'processing' => ['label' => 'Processing', 'icon' => 'fa-cogs', 'desc' => 'Your order is being processed and verified'],
                                'shipped' => ['label' => 'Shipped', 'icon' => 'fa-truck', 'desc' => 'Your order has been shipped and is on the way'],
                                'delivered' => ['label' => 'Delivered', 'icon' => 'fa-check-circle', 'desc' => 'Your order has been delivered successfully']
                            ];
                            
                            $current_status = $selected_order['status'];
                            $status_keys = array_keys($statuses);
                            $current_index = array_search($current_status, $status_keys);
                            ?>
                            
                            <?php foreach($statuses as $key => $status): 
                                $is_completed = array_search($key, $status_keys) <= $current_index;
                                $is_active = $key == $current_status;
                            ?>
                            <div class="timeline-step <?php echo $is_completed ? 'completed' : ''; ?> <?php echo $is_active ? 'active' : ''; ?>">
                                <div class="timeline-icon">
                                    <i class="fas <?php echo $status['icon']; ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h5><?php echo $status['label']; ?></h5>
                                    <p><?php echo $status['desc']; ?></p>
                                    <?php if($is_active && $current_status != 'delivered' && $current_status != 'cancelled'): ?>
                                        <small class="text-warning"><i class="fas fa-spinner fa-pulse me-1"></i> In Progress...</small>
                                    <?php elseif($is_completed && $current_status != 'cancelled'): ?>
                                        <small class="text-success"><i class="fas fa-check-circle me-1"></i> Completed ✓</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <!-- Estimated Delivery -->
                        <?php 
                        $delivery_date = date('F j, Y', strtotime('+5 days'));
                        if($current_status == 'shipped') $delivery_date = date('F j, Y', strtotime('+2 days'));
                        if($current_status == 'delivered') $delivery_date = date('F j, Y', strtotime($selected_order['order_date'] . ' +5 days'));
                        if($current_status == 'cancelled') $delivery_date = 'Order Cancelled';
                        ?>
                        <div class="alert-info mt-4 p-3 rounded-3">
                            <i class="fas fa-calendar-alt" style="color: #FFD700;"></i> 
                            <strong>Estimated Delivery:</strong> <?php echo $delivery_date; ?>
                            <?php if($current_status == 'shipped'): ?>
                                <br><small><i class="fas fa-truck me-1"></i> Your package is on the way!</small>
                            <?php elseif($current_status == 'delivered'): ?>
                                <br><small><i class="fas fa-check-circle text-success me-1"></i> Order delivered successfully!</small>
                            <?php elseif($current_status == 'cancelled'): ?>
                                <br><small><i class="fas fa-times-circle text-danger me-1"></i> This order has been cancelled.</small>
                            <?php endif; ?>
                        </div>
                        
                    <?php else: ?>
                        <!-- Empty State -->
                        <div class="empty-state">
                            <i class="fas fa-box-open"></i>
                            <h5 style="color: #ffffff;">No Order Selected</h5>
                            <p class="text-muted">Please select an order from the dropdown above to track its status.</p>
                        </div>
                    <?php endif; ?>
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

const sidebarLinks = document.querySelectorAll('#sidebar .nav-link');
sidebarLinks.forEach(link => {
    link.addEventListener('click', function() {
        if (window.innerWidth <= 992) {
            closeSidebar();
        }
    });
});

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