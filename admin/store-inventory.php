<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

// Get all data
$products = mysqli_query($conn, "SELECT * FROM products ORDER BY id DESC");
$orders = mysqli_query($conn, "SELECT orders.*, users.full_name FROM orders JOIN users ON orders.user_id = users.id ORDER BY orders.order_date DESC");
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY id DESC");
$categories = mysqli_query($conn, "SELECT * FROM categories ORDER BY type, display_order");

// Calculate monthly budget
$current_month = date('Y-m');
$previous_month = date('Y-m', strtotime('-1 month'));

$current_month_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$current_month' AND status != 'cancelled'"))['total'] ?? 0;
$previous_month_revenue = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_amount) as total FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$previous_month' AND status != 'cancelled'"))['total'] ?? 0;

$current_month_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$current_month'"))['count'] ?? 0;
$previous_month_orders = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM orders WHERE DATE_FORMAT(order_date, '%Y-%m') = '$previous_month'"))['count'] ?? 0;

// Calculate percentage change
$revenue_change = 0;
if($previous_month_revenue > 0) {
    $revenue_change = (($current_month_revenue - $previous_month_revenue) / $previous_month_revenue) * 100;
}

$orders_change = 0;
if($previous_month_orders > 0) {
    $orders_change = (($current_month_orders - $previous_month_orders) / $previous_month_orders) * 100;
}

// Get product stats
$total_stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(stock) as total FROM products"))['total'] ?? 0;
$low_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock < 5"))['count'] ?? 0;
$out_of_stock_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM products WHERE stock = 0"))['count'] ?? 0;

// Get user stats
$new_users_this_month = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users WHERE DATE_FORMAT(created_at, '%Y-%m') = '$current_month'"))['count'] ?? 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Store Inventory - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
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
        
        /* Main Content */
        .main-content {
            position: relative;
            z-index: 1;
            padding: 30px;
        }
        
        /* Header */
        .inventory-header {
            background: linear-gradient(135deg, #1a1a1a, #0d0d0d);
            border-radius: 20px;
            padding: 25px 30px;
            margin-bottom: 30px;
            border: 1px solid rgba(255,215,0,0.2);
        }
        
        .inventory-header h1 {
            color: #FFD700;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .inventory-header p {
            color: #aaa;
        }
        
        /* Stats Cards */
        .stat-card {
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            padding: 20px;
            border: 1px solid rgba(255,215,0,0.15);
            transition: all 0.3s;
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(255,215,0,0.4);
        }
        
        .stat-card i {
            font-size: 40px;
            color: #FFD700;
            margin-bottom: 10px;
        }
        
        .stat-card .stat-value {
            font-size: 32px;
            font-weight: 700;
            color: #fff;
        }
        
        .stat-card .stat-label {
            color: #aaa;
            font-size: 14px;
        }
        
        .stat-change {
            font-size: 12px;
            margin-top: 8px;
        }
        
        .stat-change.positive {
            color: #6bff8a;
        }
        
        .stat-change.negative {
            color: #ff6b6b;
        }
        
        /* Section Cards */
        .section-card {
            background: rgba(26, 26, 26, 0.85);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            margin-bottom: 30px;
            overflow: hidden;
            border: 1px solid rgba(255,215,0,0.15);
        }
        
        .section-header {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            padding: 15px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .section-header h3 {
            color: #000;
            margin: 0;
            font-weight: 700;
        }
        
        .section-header h3 i {
            margin-right: 10px;
        }
        
        .section-badge {
            background: rgba(0,0,0,0.3);
            padding: 5px 12px;
            border-radius: 50px;
            font-size: 13px;
            color: #000;
            font-weight: 600;
        }
        
        .table-container {
            overflow-x: auto;
            padding: 20px;
        }
        
        .inventory-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 600px;
        }
        
        .inventory-table th {
            background: rgba(255,215,0,0.15);
            color: #FFD700;
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
        }
        
        .inventory-table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            color: #fff;
            font-size: 13px;
        }
        
        .inventory-table tr:hover td {
            background: rgba(255,215,0,0.05);
        }
        
        .badge {
            padding: 4px 10px;
            border-radius: 50px;
            font-size: 11px;
            font-weight: 500;
        }
        
        .bg-primary { background: linear-gradient(135deg, #007bff, #0056b3); }
        .bg-danger { background: linear-gradient(135deg, #dc3545, #c82333); }
        .bg-success { background: linear-gradient(135deg, #28a745, #20c997); }
        .bg-warning { background: linear-gradient(135deg, #ffc107, #ff9800); color: #000; }
        
        .stock-low { color: #ff6b6b; font-weight: bold; }
        .stock-out { color: #ff6b6b; font-weight: bold; background: rgba(220,53,69,0.2); }
        
        /* Download Button */
        .download-btn {
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
        }
        
        .download-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(40,167,69,0.3);
            color: #fff;
        }
        
        /* Back Button */
        .back-btn {
            background: linear-gradient(135deg, #FFD700, #FFC107);
            color: #000;
            border: none;
            padding: 12px 30px;
            border-radius: 50px;
            font-weight: 600;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(255,215,0,0.3);
            color: #000;
        }
        
        /* PDF Container (Hidden) */
        #pdfContainer {
            position: fixed;
            left: -9999px;
            top: 0;
        }
        
        #pdfContent {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            font-family: Arial, sans-serif;
            padding: 30px;
        }
        
        @media (max-width: 768px) {
            .main-content { padding: 15px; }
            .inventory-header { padding: 18px 20px; }
            .inventory-header h1 { font-size: 22px; }
            .stat-card .stat-value { font-size: 24px; }
            .stat-card i { font-size: 30px; }
            .section-header { flex-direction: column; text-align: center; }
            .section-header h3 { font-size: 18px; }
        }
        
        @media (max-width: 576px) {
            .col-md-3 { width: 100%; }
            .stat-card { margin-bottom: 12px; }
        }
    </style>
</head>
<body>

<div class="main-content">
    <div class="container-fluid">
        <!-- Header -->
        <div class="inventory-header">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h1><i class="fas fa-boxes"></i> Store Inventory</h1>
                    <p>Complete overview of your store - Products, Orders, Users & Categories</p>
                </div>
                <div class="mt-3 mt-sm-0 d-flex gap-2">
                    <button onclick="downloadPDF()" class="download-btn">
                        <i class="fas fa-download"></i> Download Report (PDF)
                    </button>
                    <a href="dashboard.php" class="back-btn">
                        <i class="fas fa-arrow-left"></i> Back to Dashboard
                    </a>
                </div>
            </div>
        </div>
        
        <!-- Stats Overview -->
        <div class="row g-4 mb-4">
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-box"></i>
                    <div class="stat-value"><?php echo mysqli_num_rows($products); ?></div>
                    <div class="stat-label">Total Products</div>
                    <div class="stat-change"><?php echo $total_stock; ?> units in stock</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-shopping-cart"></i>
                    <div class="stat-value"><?php echo mysqli_num_rows($orders); ?></div>
                    <div class="stat-label">Total Orders</div>
                    <div class="stat-change <?php echo $orders_change >= 0 ? 'positive' : 'negative'; ?>">
                        <i class="fas fa-chart-line"></i> <?php echo $orders_change >= 0 ? '+' : ''; ?><?php echo number_format($orders_change, 1); ?>% from last month
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-users"></i>
                    <div class="stat-value"><?php echo mysqli_num_rows($users); ?></div>
                    <div class="stat-label">Total Users</div>
                    <div class="stat-change positive"><i class="fas fa-user-plus"></i> +<?php echo $new_users_this_month; ?> new this month</div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6">
                <div class="stat-card">
                    <i class="fas fa-tags"></i>
                    <div class="stat-value"><?php echo mysqli_num_rows($categories); ?></div>
                    <div class="stat-label">Categories</div>
                    <div class="stat-change">Men · Women · Kids · Sale</div>
                </div>
            </div>
        </div>
        
        <!-- Monthly Budget Section -->
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-chart-line"></i> Monthly Budget Overview</h3>
                <span class="section-badge"><?php echo date('F Y'); ?></span>
            </div>
            <div class="table-container">
                <div class="row">
                    <div class="col-md-6">
                        <div class="stat-card text-center" style="margin: 10px;">
                            <i class="fas fa-dollar-sign"></i>
                            <div class="stat-value">Rs <?php echo number_format($current_month_revenue, 2); ?></div>
                            <div class="stat-label">Current Month Revenue</div>
                            <div class="stat-change <?php echo $revenue_change >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-chart-line"></i> <?php echo $revenue_change >= 0 ? '+' : ''; ?><?php echo number_format($revenue_change, 1); ?>% vs last month
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card text-center" style="margin: 10px;">
                            <i class="fas fa-shopping-cart"></i>
                            <div class="stat-value"><?php echo $current_month_orders; ?></div>
                            <div class="stat-label">Orders This Month</div>
                            <div class="stat-change <?php echo $orders_change >= 0 ? 'positive' : 'negative'; ?>">
                                <i class="fas fa-chart-line"></i> <?php echo $orders_change >= 0 ? '+' : ''; ?><?php echo number_format($orders_change, 1); ?>% vs last month
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mt-3">
                    <div class="col-md-6">
                        <div class="stat-card text-center" style="margin: 10px;">
                            <i class="fas fa-calendar-alt"></i>
                            <div class="stat-value">Rs <?php echo number_format($previous_month_revenue, 2); ?></div>
                            <div class="stat-label">Previous Month Revenue</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="stat-card text-center" style="margin: 10px;">
                            <i class="fas fa-calendar-week"></i>
                            <div class="stat-value"><?php echo $previous_month_orders; ?></div>
                            <div class="stat-label">Previous Month Orders</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Products Section -->
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-box"></i> Products Inventory</h3>
                <span class="section-badge"><?php echo $low_stock_count; ?> low stock | <?php echo $out_of_stock_count; ?> out of stock</span>
            </div>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Product Name</th>
                            <th>Price</th>
                            <th>Sale Price</th>
                            <th>Stock</th>
                            <th>Type</th>
                            <th>Category</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($product = mysqli_fetch_assoc($products)): 
                            $stock_class = '';
                            if($product['stock'] == 0) $stock_class = 'stock-out';
                            elseif($product['stock'] < 5) $stock_class = 'stock-low';
                        ?>
                        <tr>
                            <td><?php echo $product['id']; ?></td>
                            <td><?php echo htmlspecialchars($product['name']); ?></td>
                            <td>Rs <?php echo number_format($product['price'], 2); ?></td>
                            <td><?php echo $product['sale_price'] ? 'Rs '.number_format($product['sale_price'], 2) : '-'; ?></td>
                            <td class="<?php echo $stock_class; ?>"><?php echo $product['stock']; ?></td>
                            <td><span class="badge bg-<?php 
                                if($product['type'] == 'men') echo 'primary';
                                elseif($product['type'] == 'women') echo 'danger';
                                elseif($product['type'] == 'kids') echo 'success';
                                else echo 'warning';
                            ?>"><?php echo ucfirst($product['type']); ?></span></td>
                            <td><?php echo !empty($product['category_id']) ? 'Yes' : '-'; ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Orders Section -->
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-shopping-cart"></i> Orders</h3>
                <span class="section-badge">Total: Rs <?php echo number_format($total_revenue ?? 0, 2); ?></span>
            </div>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Customer</th>
                            <th>Amount</th>
                            <th>Payment</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $total_revenue_sum = 0;
                        mysqli_data_seek($orders, 0);
                        while($order = mysqli_fetch_assoc($orders)): 
                            $total_revenue_sum += $order['total_amount'];
                        ?>
                        <tr>
                            <td><strong><?php echo $order['order_number']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['full_name']); ?></td>
                            <td>Rs <?php echo number_format($order['total_amount'], 2); ?></td>
                            <td><?php echo ucfirst($order['payment_method']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    if($order['status'] == 'pending') echo 'warning';
                                    elseif($order['status'] == 'processing') echo 'primary';
                                    elseif($order['status'] == 'shipped') echo 'info';
                                    elseif($order['status'] == 'delivered') echo 'success';
                                    else echo 'danger';
                                ?>" style="color: <?php echo $order['status'] == 'pending' ? '#000' : '#fff'; ?>">
                                    <?php echo ucfirst($order['status']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($order['order_date'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Users Section -->
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-users"></i> Registered Users</h3>
                <span class="section-badge"><?php echo mysqli_num_rows($users); ?> total users</span>
            </div>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Role</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($user = mysqli_fetch_assoc($users)): ?>
                        <tr>
                            <td><?php echo $user['id']; ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                            <td><?php echo htmlspecialchars($user['phone'] ?: '-'); ?></td>
                            <td>
                                <span class="badge <?php echo $user['role'] == 'admin' ? 'bg-danger' : 'bg-success'; ?>">
                                    <?php echo ucfirst($user['role']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Categories Section -->
        <div class="section-card">
            <div class="section-header">
                <h3><i class="fas fa-tags"></i> Categories</h3>
                <span class="section-badge">Organized by type</span>
            </div>
            <div class="table-container">
                <table class="inventory-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Category Name</th>
                            <th>Type</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while($cat = mysqli_fetch_assoc($categories)): ?>
                        <tr>
                            <td><?php echo $cat['id']; ?></td>
                            <td><?php echo htmlspecialchars($cat['name']); ?></td>
                            <td>
                                <span class="badge bg-<?php 
                                    if($cat['type'] == 'men') echo 'primary';
                                    elseif($cat['type'] == 'women') echo 'danger';
                                    elseif($cat['type'] == 'kids') echo 'success';
                                    else echo 'warning';
                                ?>">
                                    <?php echo ucfirst($cat['type']); ?>
                                </span>
                            </td>
                            <td><span class="badge bg-success">Active</span></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- PDF Container -->
<div id="pdfContainer">
    <div id="pdfContent">
        <style>
            .pdf-header { text-align: center; padding: 20px; border-bottom: 3px solid #FFD700; margin-bottom: 20px; }
            .pdf-header h1 { color: #FFD700; margin: 0; font-size: 28px; }
            .pdf-header p { color: #666; margin: 5px 0 0; }
            .pdf-section { margin-bottom: 30px; }
            .pdf-section h3 { background: #FFD700; color: #000; padding: 10px; margin: 0 0 15px; font-size: 18px; }
            .pdf-stats { display: flex; gap: 15px; flex-wrap: wrap; margin-bottom: 20px; }
            .pdf-stat-card { background: #f5f5f5; padding: 15px; border-radius: 10px; flex: 1; text-align: center; }
            .pdf-stat-card .value { font-size: 24px; font-weight: bold; color: #FFD700; }
            .pdf-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .pdf-table th { background: #333; color: #fff; padding: 10px; text-align: left; font-size: 12px; }
            .pdf-table td { padding: 8px 10px; border-bottom: 1px solid #ddd; font-size: 11px; }
            .pdf-footer { text-align: center; padding: 20px; border-top: 1px solid #ddd; margin-top: 20px; font-size: 11px; color: #666; }
        </style>
        
        <div class="pdf-header">
            <h1>UHD-WEARS - STORE INVENTORY REPORT</h1>
            <p>Generated on <?php echo date('F j, Y g:i A'); ?></p>
        </div>
        
        <!-- Products Section PDF -->
        <div class="pdf-section">
            <h3>Products Inventory</h3>
            <table class="pdf-table">
                <thead>
                    <tr><th>ID</th><th>Product Name</th><th>Price</th><th>Sale Price</th><th>Stock</th><th>Type</th></tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($products, 0);
                    while($p = mysqli_fetch_assoc($products)): ?>
                    <tr>
                        <td><?php echo $p['id']; ?></td>
                        <td><?php echo htmlspecialchars($p['name']); ?></td>
                        <td>Rs <?php echo number_format($p['price'], 2); ?></td>
                        <td><?php echo $p['sale_price'] ? 'Rs '.number_format($p['sale_price'], 2) : '-'; ?></td>
                        <td><?php echo $p['stock']; ?></td>
                        <td><?php echo ucfirst($p['type']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Orders Section PDF -->
        <div class="pdf-section">
            <h3>Orders</h3>
            <table class="pdf-table">
                <thead>
                    <tr><th>Order #</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($orders, 0);
                    while($o = mysqli_fetch_assoc($orders)): ?>
                    <tr>
                        <td><?php echo $o['order_number']; ?></td>
                        <td><?php echo htmlspecialchars($o['full_name']); ?></td>
                        <td>Rs <?php echo number_format($o['total_amount'], 2); ?></td>
                        <td><?php echo ucfirst($o['status']); ?></td>
                        <td><?php echo date('M d, Y', strtotime($o['order_date'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Users Section PDF -->
        <div class="pdf-section">
            <h3>Users</h3>
            <table class="pdf-table">
                <thead>
                    <tr><th>ID</th><th>Username</th><th>Full Name</th><th>Email</th><th>Role</th></tr>
                </thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($users, 0);
                    while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td><?php echo htmlspecialchars($u['full_name']); ?></td>
                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                        <td><?php echo ucfirst($u['role']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Categories Section PDF -->
        <div class="pdf-section">
            <h3>Categories</h3>
            <table class="pdf-table">
                <thead><tr><th>ID</th><th>Category Name</th><th>Type</th></tr></thead>
                <tbody>
                    <?php 
                    mysqli_data_seek($categories, 0);
                    while($c = mysqli_fetch_assoc($categories)): ?>
                    <tr>
                        <td><?php echo $c['id']; ?></td>
                        <td><?php echo htmlspecialchars($c['name']); ?></td>
                        <td><?php echo ucfirst($c['type']); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        
        <div class="pdf-footer">
            <p>UHD-Wears - Premium Clothing Brand | Generated by Admin Panel</p>
            <p>This is a computer generated report and requires no signature</p>
        </div>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function downloadPDF() {
    var element = document.getElementById('pdfContent');
    
    var opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: 'UHD-Wears_Store_Inventory_<?php echo date('Y-m-d'); ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { scale: 2, letterRendering: true, useCORS: true, logging: false },
        jsPDF: { unit: 'in', format: 'a4', orientation: 'landscape' }
    };
    
    Swal.fire({
        title: 'Generating Report...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => { Swal.showLoading(); },
        background: '#1a1a1a',
        color: '#fff'
    });
    
    html2pdf().set(opt).from(element).save().then(function() {
        Swal.fire({
            title: 'Downloaded Successfully!',
            text: 'Your inventory report has been downloaded.',
            icon: 'success',
            confirmButtonColor: '#FFD700',
            background: '#1a1a1a',
            color: '#fff',
            timer: 2000,
            showConfirmButton: true
        });
    }).catch(function(error) {
        console.error('PDF Error:', error);
        Swal.fire({
            title: 'Download Failed!',
            text: 'Please try again.',
            icon: 'error',
            confirmButtonColor: '#FFD700',
            background: '#1a1a1a',
            color: '#fff'
        });
    });
}
</script>
</body>
</html>