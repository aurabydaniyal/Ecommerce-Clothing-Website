<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

$total_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM users WHERE role = 'user'"))['c'];
$total_products = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM products"))['c'];
$total_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
$total_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE status != 'cancelled'"))['t'] ?? 0;
$pending_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'pending'"))['c'];
$shipped_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'shipped'"))['c'];
$delivered_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders WHERE status = 'delivered'"))['c'];

$monthly_data = [];
for($i = 6; $i >= 0; $i--) {
    $month = date('Y-m', strtotime("-$i months"));
    $rev = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as t FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$month' AND status != 'cancelled'"))['t'] ?? 0;
    $monthly_data[] = ['month' => date('M', strtotime("-$i months")), 'revenue' => $rev];
}

// Get total count of orders for load more functionality
$total_orders_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];

// Get recent orders - initially only 5
$recent_orders = mysqli_query($conn, "SELECT orders.*, users.full_name FROM orders JOIN users ON orders.user_id = users.id ORDER BY order_date DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .col-md-10 { position: relative; z-index: 1; }
        
        /* Glassy Stat Cards */
        .stat-card { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 20px; 
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            border: 1px solid rgba(255,215,0,0.15);
            height: 100%;
        }
        .stat-card:hover { 
            transform: translateY(-8px); 
            background: rgba(26, 26, 26, 0.95);
            border-color: rgba(255,215,0,0.4);
            box-shadow: 0 15px 35px rgba(255,215,0,0.15);
        }
        .stat-card i { font-size: 42px; color: #FFD700; text-shadow: 0 0 10px rgba(255,215,0,0.3); }
        .stat-card .value { font-size: 32px; font-weight: 700; margin: 10px 0 5px; color: #fff; }
        .stat-card p { color: #aaa; font-size: 14px; margin: 0; }
        .stat-card small { color: #666; font-size: 11px; }
        
        /* Glassy Chart Card */
        .chart-card { 
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px; 
            padding: 20px;
            border: 1px solid rgba(255,215,0,0.15);
            transition: all 0.3s;
            height: 100%;
        }
        .chart-card:hover { border-color: rgba(255,215,0,0.35); }
        .chart-card h5 { color: #FFD700; margin-bottom: 20px; font-weight: 600; }
        .chart-card h5 i { margin-right: 8px; }
        
        /* Beautiful Table UI */
        .recent-table { 
            background: #ffffff;
            border-radius: 0 0 20px 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .recent-table th { 
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000; 
            padding: 16px 15px; 
            font-weight: 700;
            border-bottom: none;
            font-size: 14px;
        }
        .recent-table td { 
            padding: 14px 15px; 
            border-bottom: 1px solid #e0e0e0; 
            color: #000000 !important;
            vertical-align: middle;
            font-size: 13px;
            background: #ffffff;
        }
        .recent-table td strong { color: #000000; font-weight: 600; }
        .recent-table tr:hover td { background: #fff8e1; }
        
        /* Status Badge Styles */
        .badge {
            padding: 6px 14px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 11px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .badge i { font-size: 10px; }
        .badge-pending { background: #ffc107; color: #000; }
        .badge-processing { background: #17a2b8; color: #fff; }
        .badge-shipped { background: #0dcaf0; color: #000; }
        .badge-delivered { background: #28a745; color: #fff; }
        .badge-cancelled { background: #dc3545; color: #fff; }
        
        /* View More Button */
        .view-more-btn {
            background: #000;
            color: #FFD700;
            border: 2px solid #FFD700;
            border-radius: 50px;
            padding: 10px 30px;
            font-weight: 600;
            transition: all 0.3s;
            margin-top: 15px;
        }
        .view-more-btn:hover {
            background: #FFD700;
            color: #000;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(255,215,0,0.3);
        }
        .view-more-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }
        
        /* Yellow Footer Background */
        .table-footer-yellow {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            padding: 15px;
            text-align: center;
            border-radius: 0 0 20px 20px;
        }
        
        /* Stock Alerts Marquee */
        .stock-marquee-container {
            background: rgba(42, 42, 42, 0.6);
            border-radius: 15px;
            overflow: hidden;
            position: relative;
            height: 510px;
            border: 1px solid rgba(255,215,0,0.15);
        }
        
        .stock-marquee-wrapper {
            position: relative;
            height: 100%;
            overflow: hidden;
        }
        
        .stock-marquee {
            position: absolute;
            width: 100%;
            animation: scrollVertical 25s linear infinite;
        }
        
        .stock-marquee:hover {
            animation-play-state: paused;
        }
        
        @keyframes scrollVertical {
            0% { transform: translateY(0); }
            100% { transform: translateY(-50%); }
        }
        
        .stock-item {
            background: rgba(255,215,0,0.08);
            border-left: 3px solid #FFD700;
            border-radius: 10px;
            padding: 15px;
            margin: 12px;
            transition: all 0.3s;
        }
        
        .stock-item:hover {
            background: rgba(255,215,0,0.15);
            transform: translateX(5px);
        }
        
        .stock-name {
            color: #fff;
            font-weight: 600;
            font-size: 14px;
            margin-bottom: 8px;
        }
        
        .stock-name i { color: #FFD700; margin-right: 8px; }
        .stock-count { color: #FFD700; font-weight: bold; font-size: 20px; margin-bottom: 5px; }
        .stock-warning { font-size: 11px; color: #ff6b6b; }
        .stock-warning i { margin-right: 4px; }
        
        /* View Store Button */
        .view-store-btn {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 10px 25px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .view-store-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,215,0,0.3);
        }
        
        .admin-avatar {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, #FFD700, #FFC107);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        /* Badge styles for modal preview */
        .badge-pending { background: #ffc107; color: #000; padding: 4px 10px; border-radius: 50px; font-size: 11px; }
        .badge-processing { background: #17a2b8; color: #fff; padding: 4px 10px; border-radius: 50px; font-size: 11px; }
        .badge-shipped { background: #0dcaf0; color: #000; padding: 4px 10px; border-radius: 50px; font-size: 11px; }
        .badge-delivered { background: #28a745; color: #fff; padding: 4px 10px; border-radius: 50px; font-size: 11px; }
        .badge-cancelled { background: #dc3545; color: #fff; padding: 4px 10px; border-radius: 50px; font-size: 11px; }
        
        /* ============================================
           FIXED STORE PREVIEW MODAL - Independent Scroll
           ============================================ */
        .modal-preview {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.95);
            backdrop-filter: blur(5px);
            z-index: 9999;
            animation: fadeInModal 0.3s ease;
        }
        @keyframes fadeInModal {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-preview-content {
            position: relative;
            width: 95%;
            height: 90%;
            margin: 30px auto;
            background: rgba(26, 26, 26, 0.95);
            backdrop-filter: blur(15px);
            border-radius: 24px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,215,0,0.3);
            box-shadow: 0 25px 50px rgba(0,0,0,0.5);
        }
        
        .modal-preview-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-shrink: 0;
        }
        
        .modal-preview-header h3 {
            color: #000;
            margin: 0;
            font-weight: 700;
        }
        
        .modal-preview-close {
            background: none;
            border: none;
            font-size: 28px;
            cursor: pointer;
            color: #000;
            transition: 0.3s;
        }
        
        .modal-preview-close:hover {
            transform: scale(1.1) rotate(90deg);
        }
        
        .modal-preview-body {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
        }
        
        .modal-preview-section {
            flex: 1;
            min-width: 300px;
            background: rgba(10, 10, 10, 0.8);
            backdrop-filter: blur(5px);
            border-radius: 16px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            border: 1px solid rgba(255,215,0,0.2);
            max-height: calc(90vh - 120px);
            overflow: hidden;
        }
        
        .modal-preview-section h4 {
            color: #FFD700;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid rgba(255,215,0,0.3);
            font-weight: 600;
            flex-shrink: 0;
        }
        
        .modal-table-container {
            overflow-y: auto;
            flex: 1;
            margin-top: 10px;
        }
        
        .modal-table-container::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        
        .modal-table-container::-webkit-scrollbar-track {
            background: rgba(255,255,255,0.1);
            border-radius: 10px;
        }
        
        .modal-table-container::-webkit-scrollbar-thumb {
            background: #FFD700;
            border-radius: 10px;
        }
        
        .modal-preview-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .modal-preview-table th {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            padding: 10px;
            text-align: left;
            font-size: 12px;
            font-weight: 600;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        
        .modal-preview-table td {
            padding: 8px 10px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 12px;
        }
        
        .modal-preview-table tr:hover td {
            background: rgba(255,215,0,0.1);
        }
        
        /* Action Button in Table */
        .view-order-btn {
            background: #FFD700;
            color: #000;
            border: none;
            border-radius: 50px;
            padding: 5px 15px;
            font-size: 12px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        .view-order-btn:hover {
            background: #e6be00;
            transform: translateY(-2px);
            color: #000;
        }
        
        .amount-text {
            color: #000000;
            font-weight: 600;
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
            .col-md-3, .col-md-4 { width: 50%; }
            .stat-card .value { font-size: 24px; }
            .stat-card i { font-size: 30px; }
            h2 { font-size: 22px; }
            .col-md-8, .col-md-4 { width: 100%; }
            .d-flex.justify-content-between { flex-direction: column; gap: 15px; align-items: flex-start; }
            .recent-table { overflow-x: auto; display: block; }
            .recent-table table { min-width: 700px; }
            .recent-table th, .recent-table td { padding: 10px 12px; font-size: 12px; }
            .badge { padding: 4px 10px; font-size: 10px; }
            .stock-marquee-container { height: 310px; }
            .modal-preview-body { flex-direction: column; }
            .modal-preview-section { min-width: auto; max-height: 400px; }
        }
        
        @media (max-width: 768px) {
            .sidebar { width: 240px; }
            .col-md-3, .col-md-4 { width: 100%; }
            .stat-card { margin-bottom: 12px; }
            .stat-card .value { font-size: 28px; }
            h2 { font-size: 20px; }
            .p-4 { padding: 15px !important; }
            .chart-card { padding: 15px; }
            .stock-marquee-container { height: 280px; }
            .stock-item { padding: 12px; margin: 10px; }
            .stock-count { font-size: 18px; }
            .modal-preview-section { max-height: 350px; }
        }
        
        @media (max-width: 576px) {
            .sidebar { width: 220px; }
            .stat-card { padding: 15px; }
            .stat-card i { font-size: 24px; }
            .stat-card .value { font-size: 22px; }
            .recent-table th, .recent-table td { padding: 8px 10px; font-size: 11px; }
            .view-order-btn { padding: 3px 10px; font-size: 10px; }
            .badge { padding: 3px 8px; font-size: 9px; }
            .stock-marquee-container { height: 250px; }
            .modal-preview-section { max-height: 300px; }
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
                <div class="logo-circle" style="width: 60px; height: 60px; background: linear-gradient(135deg, #FFD700, #FFC107); border-radius: 16px; display: flex; align-items: center; justify-content: center; margin: 0 auto 15px;">
                    <i class="fas fa-tshirt" style="font-size: 28px; color: #000;"></i>
                </div>
                <h3 style="color: #FFD700; font-size: 20px;">UHD-Wears</h3>
                <p style="color:#888; font-size: 12px;">Admin Panel</p>
            </div>
            <nav class="nav flex-column">
                <a class="nav-link active" href="dashboard.php"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a class="nav-link" href="products.php"><i class="fas fa-box"></i> Products</a>
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
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
                <h2 style="color: #FFD700; font-weight: 700;">
                    <i class="fas fa-chart-line me-2"></i> Dashboard
                </h2>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <button onclick="openStorePreview()" class="view-store-btn">
                        <i class="fas fa-store me-2"></i> View Store
                    </button>
                    <a href="store-inventory.php" class="btn" style="background: linear-gradient(135deg, #17a2b8, #138496); color: #fff; border-radius: 50px; padding: 10px 25px; font-weight: 600;">
                        <i class="fas fa-boxes"></i> Store Inventory
                    </a>
                    <div class="admin-avatar">
                        <i class="fas fa-user" style="color: #000;"></i>
                    </div>
                    <span style="color:#aaa;">Welcome, <strong class="welcome-text" style="color:#FFD700;"><?php echo htmlspecialchars($_SESSION['username']); ?></strong></span>
                </div>
            </div>
            
            <!-- Glassy Stats Cards -->
            <div class="row g-4">
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-users"></i>
                        <div class="value"><?php echo $total_users; ?></div>
                        <p>Total Users</p>
                        <small><i class="fas fa-user-plus me-1"></i> Registered customers</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-box"></i>
                        <div class="value"><?php echo $total_products; ?></div>
                        <p>Total Products</p>
                        <small><i class="fas fa-tag me-1"></i> In catalog</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fas fa-shopping-cart"></i>
                        <div class="value"><?php echo $total_orders; ?></div>
                        <p>Total Orders</p>
                        <small><i class="fas fa-calendar me-1"></i> All time</small>
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="stat-card">
                        <i class="fa-solid fa-rupee-sign"></i>
                        <div class="value"><?php echo number_format($total_revenue, 2); ?></div>
                        <p>Total Revenue</p>
                        <small><i class="fas fa-chart-line me-1"></i> + this month</small>
                    </div>
                </div>
            </div>
            
            <!-- Order Status Cards -->
            <div class="row g-4 mt-2">
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <i class="fas fa-clock"></i>
                        <div class="value"><?php echo $pending_orders; ?></div>
                        <p>Pending Orders</p>
                        <span class="badge" style="background: #ffc107; color:#000;">Need attention</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <i class="fas fa-truck"></i>
                        <div class="value"><?php echo $shipped_orders; ?></div>
                        <p>Shipped Orders</p>
                        <span class="badge" style="background: #0dcaf0; color:#000;">On the way</span>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card text-center">
                        <i class="fas fa-check-circle"></i>
                        <div class="value"><?php echo $delivered_orders; ?></div>
                        <p>Delivered Orders</p>
                        <span class="badge" style="background: #28a745; color:#fff;">Completed</span>
                    </div>
                </div>
            </div>
            
            <!-- Chart and Stock Alerts -->
            <div class="row g-4 mt-2">
                <div class="col-md-8">
                    <div class="chart-card">
                        <h5><i class="fas fa-chart-line"></i> Revenue Overview (Last 7 Months)</h5>
                        <canvas id="revenueChart" height="250"></canvas>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="chart-card">
                        <h5><i class="fas fa-exclamation-triangle"></i> Stock Alerts</h5>
                        <?php
                        $low_stock_products = mysqli_query($conn, "SELECT name, stock FROM products WHERE stock < 5 ORDER BY stock ASC");
                        if(mysqli_num_rows($low_stock_products) > 0):
                        ?>
                        <div class="stock-marquee-container">
                            <div class="stock-marquee-wrapper">
                                <div class="stock-marquee" id="stockMarquee">
                                    <?php 
                                    $stock_items = [];
                                    while($p = mysqli_fetch_assoc($low_stock_products)) {
                                        $stock_items[] = $p;
                                    }
                                    for($i = 0; $i < 6; $i++) {
                                        foreach($stock_items as $p): 
                                    ?>
                                    <div class="stock-item">
                                        <div class="stock-name">
                                            <i class="fas fa-box"></i> <?php echo htmlspecialchars($p['name']); ?>
                                        </div>
                                        <div class="stock-count">
                                            Only <?php echo $p['stock']; ?> left!
                                        </div>
                                        <div class="stock-warning">
                                            <i class="fas fa-exclamation-triangle"></i> Restock soon!
                                        </div>
                                    </div>
                                    <?php 
                                        endforeach;
                                    } 
                                    ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert mt-3 text-center" style="background: rgba(40,167,69,0.2); border: 1px solid #28a745; border-radius: 10px; color: #6bff8a;">
                            <i class="fas fa-check-circle me-2"></i> All products well stocked!
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Recent Orders Table with View More -->
            <div class="row mt-4">
                <div class="col-md-12">
                    <div class="chart-card" style="padding: 0; overflow: hidden;">
                        <div style="padding: 15px 20px; background: linear-gradient(135deg, #FFD700, #FFC107);">
                            <h5 style="color: #000; margin: 0;"><i class="fas fa-clock me-2"></i> Recent Orders</h5>
                        </div>
                        <div class="recent-table">
                            <div style="overflow-x: auto;">
                                <table class="table mb-0" id="ordersTable">
                                    <thead>
                                        <tr>
                                            <th><i class="fas fa-hashtag"></i> Order #</th>
                                            <th><i class="fas fa-user"></i> Customer</th>
                                            <th><i class="fas fa-rupee-sign"></i> Amount</th>
                                            <th><i class="fas fa-chart-simple"></i> Status</th>
                                            <th><i class="fas fa-calendar"></i> Date</th>
                                            <th><i class="fas fa-cog"></i> Action</th>
                                        </tr>
                                    </thead>
                                    <tbody id="ordersTableBody">
                                        <?php 
                                        while($order = mysqli_fetch_assoc($recent_orders)): 
                                            $status_class = '';
                                            $status_icon = '';
                                            if($order['status'] == 'pending') {
                                                $status_class = 'badge-pending';
                                                $status_icon = 'fa-clock';
                                            } elseif($order['status'] == 'processing') {
                                                $status_class = 'badge-processing';
                                                $status_icon = 'fa-cogs';
                                            } elseif($order['status'] == 'shipped') {
                                                $status_class = 'badge-shipped';
                                                $status_icon = 'fa-truck';
                                            } elseif($order['status'] == 'delivered') {
                                                $status_class = 'badge-delivered';
                                                $status_icon = 'fa-check-circle';
                                            } elseif($order['status'] == 'cancelled') {
                                                $status_class = 'badge-cancelled';
                                                $status_icon = 'fa-times-circle';
                                            }
                                        ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($order['order_number']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                                            <td><span class="amount-text">Rs <?php echo number_format($order['total_amount'], 2); ?></span></td>
                                            <td>
                                                <span class="badge <?php echo $status_class; ?>">
                                                    <i class="fas <?php echo $status_icon; ?>"></i> <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </td>
                                            <td><i class="fas fa-calendar-alt me-1" style="color:#FFD700;"></i> <?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                                            <td>
                                                <a href="orders.php" class="view-order-btn">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        <?php if($total_orders_count > 5): ?>
                        <div class="table-footer-yellow">
                            <button class="view-more-btn" id="viewMoreBtn">
                                <i class="fas fa-eye me-2"></i> View More Orders
                            </button>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Store Preview Modal -->
<div id="storePreviewModal" class="modal-preview">
    <div class="modal-preview-content">
        <div class="modal-preview-header">
            <h3><i class="fas fa-store"></i> Store Overview</h3>
            <button class="modal-preview-close" onclick="closeStorePreview()">&times;</button>
        </div>
        <div class="modal-preview-body">
            <!-- Recent Orders Section -->
            <div class="modal-preview-section">
                <h4><i class="fas fa-shopping-cart"></i> Recent Orders</h4>
                <div class="modal-table-container">
                    <table class="modal-preview-table">
                        <thead>
                            <tr>
                                <th>Order #</th>
                                <th>Customer</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody id="previewOrdersTable">
                            <tr><td colspan="5" style="text-align: center; color: #888;">Loading...<\/td><\/tr>
                        </tbody>
                    </table>
                </div>
            </div>
            
            <!-- Products Inventory Section -->
            <div class="modal-preview-section">
                <h4><i class="fas fa-box"></i> Products Inventory</h4>
                <div class="modal-table-container">
                    <table class="modal-preview-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product Name</th>
                                <th>Price</th>
                                <th>Stock</th>
                                <th>Type</th>
                            </tr>
                        </thead>
                        <tbody id="previewProductsTable">
                            <td><td colspan="5" style="text-align: center; color: #888;">Loading...<\/td><\/tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentOffset = 5;
const totalOrders = <?php echo $total_orders_count; ?>;

// View More Orders
$('#viewMoreBtn').click(function() {
    const btn = $(this);
    const originalText = btn.html();
    btn.html('<i class="fas fa-spinner fa-spin me-2"></i> Loading...').prop('disabled', true);
    
    $.ajax({
        url: 'get-more-orders.php',
        method: 'POST',
        data: { offset: currentOffset },
        dataType: 'json',
        success: function(response) {
            if(response.success && response.orders.length > 0) {
                response.orders.forEach(function(order) {
                    let statusClass = '';
                    let statusIcon = '';
                    let statusText = '';
                    
                    if(order.status === 'pending') {
                        statusClass = 'badge-pending';
                        statusIcon = 'fa-clock';
                        statusText = 'Pending';
                    } else if(order.status === 'processing') {
                        statusClass = 'badge-processing';
                        statusIcon = 'fa-cogs';
                        statusText = 'Processing';
                    } else if(order.status === 'shipped') {
                        statusClass = 'badge-shipped';
                        statusIcon = 'fa-truck';
                        statusText = 'Shipped';
                    } else if(order.status === 'delivered') {
                        statusClass = 'badge-delivered';
                        statusIcon = 'fa-check-circle';
                        statusText = 'Delivered';
                    } else {
                        statusClass = 'badge-cancelled';
                        statusIcon = 'fa-times-circle';
                        statusText = 'Cancelled';
                    }
                    
                    $('#ordersTableBody').append(`
                        <tr>
                            <td><strong>${order.order_number}</strong></td>
                            <td>${order.full_name}</td>
                            <td><span class="amount-text">Rs ${parseFloat(order.total_amount).toFixed(2)}</span></td>
                            <td>
                                <span class="badge ${statusClass}">
                                    <i class="fas ${statusIcon}"></i> ${statusText}
                                </span>
                            </td>
                            <td><i class="fas fa-calendar-alt me-1" style="color:#FFD700;"></i> ${order.order_date}</td>
                            <td>
                                <a href="orders.php" class="view-order-btn">
                                    <i class="fas fa-eye"></i> View
                                </a>
                            </td>
                        </tr>
                    `);
                });
                
                currentOffset += 5;
                
                if(currentOffset >= totalOrders) {
                    btn.html('<i class="fas fa-check me-2"></i> All Orders Loaded').prop('disabled', true);
                } else {
                    btn.html(originalText).prop('disabled', false);
                }
            } else {
                btn.html('<i class="fas fa-check me-2"></i> All Orders Loaded').prop('disabled', true);
            }
        },
        error: function() {
            btn.html(originalText).prop('disabled', false);
            Swal.fire({
                title: 'Error!',
                text: 'Failed to load more orders',
                icon: 'error',
                confirmButtonColor: '#FFD700',
                background: '#1a1a1a',
                color: '#fff'
            });
        }
    });
});

// Revenue Chart
const ctx = document.getElementById('revenueChart').getContext('2d');
new Chart(ctx, {
    type: 'line',
    data: { 
        labels: <?php echo json_encode(array_column($monthly_data, 'month')); ?>, 
        datasets: [{ 
            label: 'Revenue (Rs)', 
            data: <?php echo json_encode(array_column($monthly_data, 'revenue')); ?>, 
            borderColor: '#FFD700', 
            backgroundColor: 'rgba(255,215,0,0.1)', 
            fill: true, 
            tension: 0.4,
            borderWidth: 3,
            pointBackgroundColor: '#FFD700',
            pointBorderColor: '#000',
            pointRadius: 4,
            pointHoverRadius: 6
        }] 
    },
    options: { 
        responsive: true, 
        maintainAspectRatio: true,
        plugins: { 
            legend: { labels: { color: '#fff', font: { size: 12 } } },
            tooltip: { backgroundColor: '#1a1a1a', titleColor: '#FFD700', bodyColor: '#fff' }
        }, 
        scales: { 
            y: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } }, 
            x: { ticks: { color: '#fff' }, grid: { color: 'rgba(255,255,255,0.1)' } } 
        } 
    }
});

// Store Preview Functions
function openStorePreview() {
    document.getElementById('storePreviewModal').style.display = 'block';
    document.body.style.overflow = 'hidden';
    loadStoreData();
}

function closeStorePreview() {
    document.getElementById('storePreviewModal').style.display = 'none';
    document.body.style.overflow = 'auto';
}

function loadStoreData() {
    // Load orders
    $.ajax({
        url: 'get-store-data.php',
        method: 'GET',
        data: { type: 'orders' },
        success: function(data) { 
            $('#previewOrdersTable').html(data); 
        },
        error: function() { 
            $('#previewOrdersTable').html('<tr><td colspan="5" style="text-align: center; color: #ff6b6b;">Failed to load orders</td></tr>'); 
        }
    });
    
    // Load products
    $.ajax({
        url: 'get-store-data.php',
        method: 'GET',
        data: { type: 'products' },
        success: function(data) { 
            $('#previewProductsTable').html(data); 
        },
        error: function() { 
            $('#previewProductsTable').html('<tr><td colspan="5" style="text-align: center; color: #ff6b6b;">Failed to load products</td></tr>'); 
        }
    });
}

document.getElementById('storePreviewModal').addEventListener('click', function(e) {
    if (e.target === this) closeStorePreview();
});

// Mobile Sidebar Toggle
document.getElementById('sidebarToggle').addEventListener('click', function() {
    document.getElementById('sidebar').classList.toggle('show');
});

document.addEventListener('click', function(e) {
    if (window.innerWidth <= 992) {
        const sidebar = document.getElementById('sidebar');
        const toggle = document.getElementById('sidebarToggle');
        if (!sidebar.contains(e.target) && !toggle.contains(e.target) && sidebar.classList.contains('show')) {
            sidebar.classList.remove('show');
        }
    }
});
</script>
</body>
</html>