<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Update order status
if(isset($_POST['update_status'])) {
    $order_id = intval($_POST['order_id']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $notes = mysqli_real_escape_string($conn, $_POST['notes'] ?? '');
    
    $update_sql = "UPDATE orders SET status = '$status' WHERE id = $order_id";
    
    if(mysqli_query($conn, $update_sql)) {
        mysqli_query($conn, "INSERT INTO order_tracking (order_id, status, notes, updated_by) VALUES ($order_id, '$status', '$notes', '{$_SESSION['user_id']}')");
        header('Location: orders.php?updated=1');
        exit();
    }
}

// Delete order
if(isset($_GET['delete'])) {
    $order_id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM order_items WHERE order_id = $order_id");
    mysqli_query($conn, "DELETE FROM orders WHERE id = $order_id");
    header('Location: orders.php?deleted=1');
    exit();
}

// Get status filter from URL
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($conn, $_GET['status']) : '';

// Build query with filter
$sql = "SELECT orders.*, users.full_name, users.email 
        FROM orders 
        JOIN users ON orders.user_id = users.id";

if($status_filter && $status_filter != 'all') {
    $sql .= " WHERE orders.status = '$status_filter'";
}

$sql .= " ORDER BY orders.order_date DESC";

$orders = mysqli_query($conn, $sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Manage Orders - Admin</title>
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
        
        /* Neon Yellow Grid Background */
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
        
        /* Glassy Sidebar */
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
        
        /* Moving Marquee for Filter Buttons */
        .filter-marquee {
            background: rgba(26, 26, 26, 0.6);
            backdrop-filter: blur(10px);
            border-radius: 60px;
            padding: 8px;
            margin: 20px 0;
            border: 1px solid rgba(255,215,0,0.2);
            overflow: hidden;
            position: relative;
        }
        
        .filter-track {
            display: flex;
            gap: 15px;
            animation: slideFilters 25s linear infinite;
            width: fit-content;
        }
        
        .filter-track:hover {
            animation-play-state: paused;
        }
        
        @keyframes slideFilters {
            0% { transform: translateX(0); }
            100% { transform: translateX(-50%); }
        }
        
        .filter-btn-marquee {
            background: rgba(42, 42, 42, 0.9);
            color: #fff;
            border: 1px solid rgba(255,215,0,0.3);
            padding: 10px 25px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
            white-space: nowrap;
            display: inline-block;
        }
        
        .filter-btn-marquee:hover,
        .filter-btn-marquee.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border-color: #FFD700;
            transform: translateY(-2px);
        }
        
        /* Static Filter Section (for desktop) */
        .filter-section {
            margin: 20px 0;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        
        .filter-btn {
            background: rgba(26, 26, 26, 0.8);
            backdrop-filter: blur(5px);
            color: #fff;
            border: 1px solid rgba(255,215,0,0.2);
            padding: 10px 24px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .filter-btn:hover, .filter-btn.active {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border-color: #FFD700;
            transform: translateY(-2px);
        }
        
        /* Glassy Order Cards */
        .order-card { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 25px; 
            margin-bottom: 25px; 
            border-left: 4px solid #FFD700;
            transition: all 0.3s;
            border: 1px solid rgba(255,215,0,0.15);
        }
        .order-card:hover {
            transform: translateY(-3px);
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 10px 30px rgba(255,215,0,0.1);
        }
        .order-card h5 { 
            color: #FFD700;
            font-weight: 700;
        }
        .order-card strong { 
            color: #ffffff !important;
            font-weight: 600;
        }
        .order-card span, .order-card p, .order-card div { 
            color: #ffffff !important; 
        }
        .order-card small { 
            color: #cccccc !important; 
        }
        
        /* Form Controls */
        .form-control, .form-select { 
            background: rgba(42, 42, 42, 0.9);
            color: #ffffff; 
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 12px;
            padding: 8px 15px;
        }
        .form-control:focus, .form-select:focus {
            border-color: #FFD700;
            box-shadow: 0 0 0 3px rgba(255,215,0,0.1);
            outline: none;
        }
        .form-control::placeholder { 
            color: #aaa; 
        }
        
        .btn-warning { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-warning:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        
        .btn-summary {
            background: linear-gradient(135deg, #17a2b8, #138496);
            color: #fff;
            border: none;
            border-radius: 50px;
            padding: 8px 20px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-summary:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(23,162,184,0.3);
        }
        
        .btn-danger-custom {
            background: rgba(220,53,69,0.2);
            color: #ff6b6b;
            border: 1px solid rgba(220,53,69,0.3);
            border-radius: 50px;
            padding: 5px 15px;
            transition: all 0.3s;
        }
        .btn-danger-custom:hover {
            background: #dc3545;
            color: #fff;
            transform: translateY(-2px);
        }
        
        /* Alerts */
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
        
        h2 { 
            color: #FFD700;
            font-weight: 700;
        }
        
        /* Order Summary Modal Styles */
        .summary-modal .modal-content {
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(15px);
            border: 2px solid #FFD700;
            border-radius: 20px;
        }
        .summary-modal .modal-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border-radius: 18px 18px 0 0;
            border-bottom: none;
        }
        .summary-modal .modal-header h5 {
            color: #000;
            font-weight: 700;
        }
        .summary-modal .modal-body {
            color: #fff;
            padding: 20px;
        }
        .summary-item {
            background: rgba(42, 42, 42, 0.5);
            border-radius: 12px;
            padding: 12px;
            margin-bottom: 10px;
            transition: all 0.3s;
        }
        .summary-item:hover {
            background: rgba(42, 42, 42, 0.8);
            transform: translateX(5px);
        }
        .summary-item-image {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 8px;
        }
        .summary-total {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border-radius: 12px;
            padding: 15px;
            margin-top: 15px;
            font-weight: bold;
        }
        
        /* Mobile Responsive */
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
        
        /* Action Buttons Container */
        .action-buttons {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .filter-section { display: none; }
            .filter-marquee { display: block; }
            .action-buttons { justify-content: center; margin-top: 15px; }
            .btn-summary, .btn-danger-custom { width: auto; padding: 5px 15px; font-size: 12px; }
        }
        
        @media (min-width: 769px) {
            .filter-marquee { display: none; }
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
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            .order-card { padding: 18px; }
            .order-card .row [class*="col-"] { margin-bottom: 12px; }
            .btn-warning { width: 100%; }
            h2 { font-size: 22px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            h2 { font-size: 18px; }
            .order-card { padding: 15px; }
            .filter-btn-marquee { padding: 6px 16px; font-size: 12px; }
            .action-buttons { flex-direction: column; align-items: stretch; }
            .btn-summary, .btn-danger-custom { width: 100%; text-align: center; }
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
                <a class="nav-link active" href="orders.php"><i class="fas fa-shopping-cart"></i> Orders</a>
                <a class="nav-link" href="users.php"><i class="fas fa-users"></i> Users</a>
                <a class="nav-link" href="sliders.php"><i class="fas fa-image"></i> Sliders</a>
                <a class="nav-link" href="categories.php"><i class="fas fa-tags"></i> Categories</a>
                <hr style="border-color: rgba(255,255,255,0.1); margin: 15px;">
                <a class="nav-link" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-10 p-4">
            <h2><i class="fas fa-shopping-cart me-2"></i> Manage Orders</h2>
            <p style="color:#aaa;">View and update customer orders</p>
            
            <?php if(isset($_GET['updated'])): ?>
            <div class="alert-custom mt-3"><i class="fas fa-check-circle me-2"></i> Order status updated successfully!</div>
            <?php endif; ?>
            <?php if(isset($_GET['deleted'])): ?>
            <div class="alert-danger-custom mt-3"><i class="fas fa-trash me-2"></i> Order deleted successfully!</div>
            <?php endif; ?>
            
            <!-- MOVING MARQUEE FILTER BUTTONS (Mobile Only) -->
            <div class="filter-marquee">
                <div class="filter-track">
                    <a href="orders.php" class="filter-btn-marquee <?php echo (!$status_filter || $status_filter == 'all') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All Orders
                    </a>
                    <a href="orders.php?status=pending" class="filter-btn-marquee <?php echo ($status_filter == 'pending') ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="orders.php?status=processing" class="filter-btn-marquee <?php echo ($status_filter == 'processing') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i> Processing
                    </a>
                    <a href="orders.php?status=shipped" class="filter-btn-marquee <?php echo ($status_filter == 'shipped') ? 'active' : ''; ?>">
                        <i class="fas fa-truck"></i> Shipped
                    </a>
                    <a href="orders.php?status=delivered" class="filter-btn-marquee <?php echo ($status_filter == 'delivered') ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Delivered
                    </a>
                    <a href="orders.php?status=cancelled" class="filter-btn-marquee <?php echo ($status_filter == 'cancelled') ? 'active' : ''; ?>">
                        <i class="fas fa-times-circle"></i> Cancelled
                    </a>
                    <!-- Duplicate set for seamless loop -->
                    <a href="orders.php" class="filter-btn-marquee <?php echo (!$status_filter || $status_filter == 'all') ? 'active' : ''; ?>">
                        <i class="fas fa-list"></i> All Orders
                    </a>
                    <a href="orders.php?status=pending" class="filter-btn-marquee <?php echo ($status_filter == 'pending') ? 'active' : ''; ?>">
                        <i class="fas fa-clock"></i> Pending
                    </a>
                    <a href="orders.php?status=processing" class="filter-btn-marquee <?php echo ($status_filter == 'processing') ? 'active' : ''; ?>">
                        <i class="fas fa-cogs"></i> Processing
                    </a>
                    <a href="orders.php?status=shipped" class="filter-btn-marquee <?php echo ($status_filter == 'shipped') ? 'active' : ''; ?>">
                        <i class="fas fa-truck"></i> Shipped
                    </a>
                    <a href="orders.php?status=delivered" class="filter-btn-marquee <?php echo ($status_filter == 'delivered') ? 'active' : ''; ?>">
                        <i class="fas fa-check-circle"></i> Delivered
                    </a>
                    <a href="orders.php?status=cancelled" class="filter-btn-marquee <?php echo ($status_filter == 'cancelled') ? 'active' : ''; ?>">
                        <i class="fas fa-times-circle"></i> Cancelled
                    </a>
                </div>
            </div>
            
            <!-- STATIC FILTER SECTION (Desktop) -->
            <div class="filter-section">
                <a href="orders.php" class="filter-btn <?php echo (!$status_filter || $status_filter == 'all') ? 'active' : ''; ?>">
                    <i class="fas fa-list"></i> All Orders
                </a>
                <a href="orders.php?status=pending" class="filter-btn <?php echo ($status_filter == 'pending') ? 'active' : ''; ?>">
                    <i class="fas fa-clock"></i> Pending
                </a>
                <a href="orders.php?status=processing" class="filter-btn <?php echo ($status_filter == 'processing') ? 'active' : ''; ?>">
                    <i class="fas fa-cogs"></i> Processing
                </a>
                <a href="orders.php?status=shipped" class="filter-btn <?php echo ($status_filter == 'shipped') ? 'active' : ''; ?>">
                    <i class="fas fa-truck"></i> Shipped
                </a>
                <a href="orders.php?status=delivered" class="filter-btn <?php echo ($status_filter == 'delivered') ? 'active' : ''; ?>">
                    <i class="fas fa-check-circle"></i> Delivered
                </a>
                <a href="orders.php?status=cancelled" class="filter-btn <?php echo ($status_filter == 'cancelled') ? 'active' : ''; ?>">
                    <i class="fas fa-times-circle"></i> Cancelled
                </a>
            </div>
            
            <?php if(mysqli_num_rows($orders) == 0): ?>
                <div class="alert text-center mt-4" style="background: rgba(26,26,26,0.8); backdrop-filter: blur(10px); border: 1px solid rgba(255,215,0,0.2); border-radius: 20px; color: #fff;">
                    <i class="fas fa-inbox fa-3x mb-3" style="color: #FFD700;"></i>
                    <h5>No orders found</h5>
                    <p>No orders match the selected filter.</p>
                </div>
            <?php else: ?>
                <?php while($order = mysqli_fetch_assoc($orders)): ?>
                <div class="order-card mt-4">
                    <div class="d-flex justify-content-between flex-wrap align-items-center">
                        <h5><i class="fas fa-receipt me-2"></i> Order #<?php echo htmlspecialchars($order['order_number']); ?></h5>
                        <small style="color:#ccc;"><i class="fas fa-calendar-alt me-1"></i> <?php echo date('F j, Y g:i A', strtotime($order['order_date'])); ?></small>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-3 col-sm-12 mb-2">
                            <strong><i class="fas fa-user me-1"></i> Customer:</strong><br>
                            <span><?php echo htmlspecialchars($order['full_name']); ?></span><br>
                            <small><i class="fas fa-envelope me-1"></i> <?php echo htmlspecialchars($order['email']); ?></small>
                        </div>
                        <div class="col-md-2 col-sm-12 mb-2">
                            <strong><i class="fas fa-dollar-sign me-1"></i> Total:</strong><br>
                            <span style="color:#FFD700; font-size:22px; font-weight:bold;">Rs <?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                        <div class="col-md-2 col-sm-12 mb-2">
                            <strong><i class="fas fa-credit-card me-1"></i> Payment:</strong><br>
                            <span><i class="fas <?php echo $order['payment_method'] == 'card' ? 'fa-credit-card' : 'fa-money-bill-wave'; ?> me-1"></i> <?php echo ucfirst($order['payment_method']); ?></span>
                        </div>
                        <div class="col-md-5 col-sm-12">
                            <form method="POST">
                                <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                <div class="row g-2">
                                    <div class="col-md-5 col-sm-12">
                                        <select name="status" class="form-select form-select-sm">
                                            <option value="pending" <?php echo $order['status']=='pending'?'selected':''; ?>>🕐 Pending</option>
                                            <option value="processing" <?php echo $order['status']=='processing'?'selected':''; ?>>⚙️ Processing</option>
                                            <option value="shipped" <?php echo $order['status']=='shipped'?'selected':''; ?>>🚚 Shipped</option>
                                            <option value="delivered" <?php echo $order['status']=='delivered'?'selected':''; ?>>✅ Delivered</option>
                                            <option value="cancelled" <?php echo $order['status']=='cancelled'?'selected':''; ?>>❌ Cancelled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-4 col-sm-12">
                                        <input type="text" name="notes" class="form-control form-control-sm" placeholder="Add notes">
                                    </div>
                                    <div class="col-md-3 col-sm-12">
                                        <button type="submit" name="update_status" class="btn btn-warning btn-sm w-100">
                                            <i class="fas fa-sync-alt"></i> Update
                                        </button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <strong><i class="fas fa-map-marker-alt me-1"></i> Shipping Address:</strong><br>
                            <span><?php echo nl2br(htmlspecialchars($order['shipping_address'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <div class="action-buttons">
                                <button class="btn-summary summary-order-btn" data-order-id="<?php echo $order['id']; ?>">
                                    <i class="fas fa-info-circle me-1"></i> Summary
                                </button>
                                <button class="btn-danger-custom delete-order-btn" data-id="<?php echo $order['id']; ?>">
                                    <i class="fas fa-trash-alt me-1"></i> Delete Order
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
$(document).ready(function() {
    // SweetAlert for Delete Order
    $('.delete-order-btn').click(function() {
        var orderId = $(this).data('id');
        Swal.fire({
            title: 'Delete Order?',
            text: 'This action cannot be undone! All order data will be permanently removed.',
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
                window.location.href = 'orders.php?delete=' + orderId;
            }
        });
    });
    
    // Summary Order Button - Fetch and display order details
    $('.summary-order-btn').click(function() {
        var orderId = $(this).data('order-id');
        
        Swal.fire({
            title: '<span style="color:#FFD700;"><i class="fas fa-receipt"></i> Order Summary</span>',
            html: '<div style="text-align: center;"><i class="fas fa-spinner fa-pulse fa-2x" style="color:#FFD700;"></i><br>Loading order details...</div>',
            showConfirmButton: false,
            allowOutsideClick: false,
            background: '#1a1a1a',
            customClass: {
                popup: 'summary-modal'
            }
        });
        
        $.ajax({
            url: 'get-order-details.php',
            method: 'POST',
            data: { order_id: orderId },
            dataType: 'json',
            success: function(response) {
                if(response.success) {
                    var itemsHtml = '';
                    var subtotal = 0;
                    
                    $.each(response.items, function(index, item) {
                        var itemTotal = item.price * item.quantity;
                        subtotal += itemTotal;
                        itemsHtml += `
                            <div class="summary-item">
                                <div class="row align-items-center">
                                    <div class="col-1">
                                        <i class="fas fa-box" style="color:#FFD700;"></i>
                                    </div>
                                    <div class="col-7">
                                        <strong style="color:#FFD700;">${item.name}</strong>
                                        <div style="font-size: 12px; color:#aaa;">
                                            ${item.size ? '<i class="fas fa-ruler-combined"></i> Size: ' + item.size + ' | ' : ''}
                                            ${item.color ? '<i class="fas fa-palette"></i> Color: ' + item.color : ''}
                                        </div>
                                    </div>
                                    <div class="col-2 text-center">
                                        <span style="color:#fff;">x${item.quantity}</span>
                                    </div>
                                    <div class="col-2 text-end">
                                        <span style="color:#FFD700;">Rs ${parseFloat(itemTotal).toFixed(2)}</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    });
                    
                    var shipping = 0.00;
                    var grandTotal = subtotal + shipping;
                    
                    var summaryHtml = `
                        <div style="max-height: 400px; overflow-y: auto; padding: 5px;">
                            ${itemsHtml}
                            
                            <div style="margin-top: 15px; padding-top: 10px; border-top: 1px solid rgba(255,255,255,0.1);">
                                <div class="d-flex justify-content-between" style="margin-bottom: 8px;">
                                    <span style="color:#aaa;">Subtotal:</span>
                                    <span style="color:#fff;">Rs ${subtotal.toFixed(2)}</span>
                                </div>
                                <div class="d-flex justify-content-between" style="margin-bottom: 8px;">
                                    <span style="color:#aaa;">Shipping:</span>
                                    <span style="color:#fff;">Rs ${shipping.toFixed(2)}</span>
                                </div>
                                <div class="d-flex justify-content-between summary-total" style="margin-top: 10px; padding: 12px; background: linear-gradient(135deg, #FFD700, #FFC107); border-radius: 10px;">
                                    <span style="color:#000; font-weight:bold;">Grand Total:</span>
                                    <span style="color:#000; font-weight:bold;">Rs ${grandTotal.toFixed(2)}</span>
                                </div>
                            </div>
                            
                            <div style="margin-top: 15px; padding: 10px; background: rgba(42,42,42,0.5); border-radius: 10px;">
                                <small style="color:#FFD700;"><i class="fas fa-calendar-alt"></i> Order Date: ${response.order_date}</small><br>
                                <small style="color:#FFD700;"><i class="fas fa-credit-card"></i> Payment: ${response.payment_method}</small><br>
                                <small style="color:#FFD700;"><i class="fas fa-tag"></i> Status: ${response.status}</small>
                            </div>
                        </div>
                    `;
                    
                    Swal.fire({
                        title: '<span style="color:#FFD700;"><i class="fas fa-receipt"></i> Order Summary</span>',
                        html: summaryHtml,
                        confirmButtonText: '<i class="fas fa-check"></i> Close',
                        confirmButtonColor: '#FFD700',
                        background: 'rgba(26, 26, 26, 0.95)',
                        customClass: {
                            popup: 'summary-modal',
                            confirmButton: 'btn-warning'
                        }
                    });
                } else {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Could not load order details.',
                        icon: 'error',
                        confirmButtonColor: '#FFD700',
                        background: '#1a1a1a',
                        color: '#fff'
                    });
                }
            },
            error: function() {
                Swal.fire({
                    title: 'Error!',
                    text: 'Something went wrong. Please try again.',
                    icon: 'error',
                    confirmButtonColor: '#FFD700',
                    background: '#1a1a1a',
                    color: '#fff'
                });
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