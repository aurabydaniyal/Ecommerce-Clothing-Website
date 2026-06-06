<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) { header('Location: login.php'); exit(); }

$order_number = $_GET['order'] ?? '';
$order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE order_number = '$order_number' AND user_id = '{$_SESSION['user_id']}'"));

if(!$order) { header('Location: index.php'); exit(); }

// Get order items for invoice
$order_items = mysqli_query($conn, "SELECT order_items.*, products.name FROM order_items JOIN products ON order_items.product_id = products.id WHERE order_items.order_id = '{$order['id']}'");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Success - UHD-Wears</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <style>
        body { background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 50px 0; }
        .success-card { background: rgba(26,26,26,0.95); border-radius: 20px; padding: 50px; text-align: center; max-width: 650px; animation: bounceIn 0.6s; }
        @keyframes bounceIn { 0% { transform: scale(0.3); opacity: 0; } 50% { transform: scale(1.05); } 100% { transform: scale(1); opacity: 1; } }
        .checkmark { width: 80px; height: 80px; background: #28a745; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 20px; }
        .checkmark i { font-size: 40px; color: white; }
        .order-number { background: #2a2a2a; padding: 10px; border-radius: 10px; font-family: monospace; margin: 20px 0; }
        .btn-download { background: #28a745; color: white; border: none; }
        .btn-download:hover { background: #218838; transform: translateY(-2px); }
        
        /* ============================================
           PROFESSIONAL INVOICE - A4 PERFECT
           ============================================ */
        #invoiceContainer {
            position: fixed;
            left: -9999px;
            top: 0;
        }
        
        #invoiceContent {
            width: 100%;
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
            font-family: 'Poppins', 'Segoe UI', Arial, sans-serif;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        
        .invoice-page {
            page-break-after: avoid;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        
        .invoice-header {
            background: linear-gradient(135deg, #0a0a0a 0%, #1a1a1a 100%);
            padding: 35px 30px;
            text-align: center;
            border-bottom: 4px solid #FFD700;
        }
        .invoice-header h1 {
            color: #FFD700;
            margin: 0;
            font-size: 32px;
            letter-spacing: 3px;
            font-weight: 700;
        }
        .invoice-header p {
            color: #aaa;
            margin: 8px 0 0;
            font-size: 13px;
        }
        
        .invoice-title {
            background: #FFD700;
            padding: 12px;
            text-align: center;
        }
        .invoice-title h2 {
            color: #000;
            margin: 0;
            font-size: 20px;
            font-weight: bold;
            letter-spacing: 1px;
        }
        
        .invoice-body {
            padding: 30px;
        }
        
        .info-section {
            margin-bottom: 30px;
            page-break-inside: avoid;
            break-inside: avoid;
        }
        .info-title {
            font-weight: bold;
            color: #FFD700;
            border-left: 4px solid #FFD700;
            padding-left: 12px;
            margin-bottom: 15px;
            font-size: 16px;
        }
        .info-grid {
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            background: #f8f9fa;
            padding: 18px;
            border-radius: 10px;
            gap: 15px;
        }
        .info-item {
            flex: 1;
            min-width: 150px;
        }
        .info-label {
            font-size: 11px;
            color: #888;
            margin-bottom: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .info-value {
            font-size: 14px;
            font-weight: 600;
            color: #333;
            word-wrap: break-word;
        }
        
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .items-table th {
            background: #FFD700;
            color: #000;
            padding: 12px 10px;
            font-size: 12px;
            font-weight: 600;
            text-align: left;
            border-bottom: 2px solid #e6be00;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #eee;
            font-size: 12px;
            color: #333;
        }
        .items-table th:last-child,
        .items-table td:last-child {
            text-align: right;
        }
        .items-table th:nth-child(2),
        .items-table td:nth-child(2),
        .items-table th:nth-child(3),
        .items-table td:nth-child(3),
        .items-table th:nth-child(4),
        .items-table td:nth-child(4) {
            text-align: center;
        }
        
        .total-section {
            margin-top: 25px;
            text-align: right;
            padding-top: 20px;
            border-top: 2px solid #FFD700;
        }
        .total-row {
            margin: 8px 0;
            font-size: 14px;
        }
        .grand-total {
            font-size: 20px;
            font-weight: bold;
            color: #FFD700;
            margin-top: 12px;
            padding-top: 8px;
            border-top: 1px dashed #ddd;
        }
        
        .invoice-footer {
            background: #f5f5f5;
            padding: 25px;
            text-align: center;
            border-top: 1px solid #ddd;
        }
        .invoice-footer p {
            margin: 5px 0;
            color: #666;
            font-size: 11px;
        }
        
        .status-paid {
            display: inline-block;
            background: #28a745;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-pending {
            display: inline-block;
            background: #ffc107;
            color: #000;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        .status-shipped {
            display: inline-block;
            background: #17a2b8;
            color: #fff;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
        }
        
        @media print {
            body { background: white; }
            .success-card { display: none; }
            #invoiceContainer { position: static; }
            #invoiceContent { margin: 0; box-shadow: none; }
            .btn-download, .btn-primary-custom, .btn-outline-custom { display: none; }
        }
        
        @media (max-width: 600px) {
            .info-grid { flex-direction: column; gap: 10px; }
            .info-item { min-width: auto; }
            .items-table th, .items-table td { padding: 8px 5px; font-size: 10px; }
        }
    </style>
</head>
<body>

<!-- Hidden Invoice Container for PDF -->
<div id="invoiceContainer">
    <div id="invoiceContent">
        <!-- Header -->
        <div class="invoice-header">
            <h1>UHD-WEARS</h1>
            <p>Premium Fashion Destination</p>
        </div>
        
        <!-- Title -->
        <div class="invoice-title">
            <h2>TAX INVOICE / ORDER RECEIPT</h2>
        </div>
        
        <!-- Body -->
        <div class="invoice-body">
            <!-- Order Info -->
            <div class="info-section">
                <div class="info-title">ORDER INFORMATION</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">ORDER NUMBER</div>
                        <div class="info-value"><?php echo $order['order_number']; ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ORDER DATE</div>
                        <div class="info-value"><?php echo date('F j, Y', strtotime($order['order_date'])); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">ORDER STATUS</div>
                        <div class="info-value">
                            <span class="status-<?php echo $order['status']; ?>"><?php echo ucfirst($order['status']); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Payment & Shipping -->
            <div class="info-section">
                <div class="info-title">PAYMENT & SHIPPING</div>
                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">PAYMENT METHOD</div>
                        <div class="info-value"><?php echo ucfirst($order['payment_method']); ?></div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">SHIPPING ADDRESS</div>
                        <div class="info-value"><?php echo nl2br($order['shipping_address']); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Order Items -->
            <div class="info-section">
                <div class="info-title">ORDER ITEMS</div>
                <table class="items-table">
                    <thead>
                        <tr>
                            <th>Product Description</th>
                            <th>Qty</th>
                            <th>Size</th>
                            <th>Color</th>
                            <th>Unit Price</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $subtotal = 0;
                        mysqli_data_seek($order_items, 0);
                        while($item = mysqli_fetch_assoc($order_items)): 
                            $item_total = $item['price'] * $item['quantity'];
                            $subtotal += $item_total;
                        ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($item['name']); ?></strong></td>
                            <td><?php echo $item['quantity']; ?></td>
                            <td><?php echo $item['size'] ?: '-'; ?></td>
                            <td><?php echo $item['color'] ?: '-'; ?></td>
                            <td>Rs <?php echo number_format($item['price'], 2); ?></td>
                            <td>Rs <?php echo number_format($item_total, 2); ?></td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                
                <div class="total-section">
                    <div class="total-row">
                        <strong>Subtotal:</strong> Rs <?php echo number_format($subtotal, 2); ?>
                    </div>
                    <div class="total-row">
                        <strong>Shipping Charge:</strong> Rs 10.00
                    </div>
                    <div class="grand-total">
                        <strong>Grand Total:</strong> Rs <?php echo number_format($order['total_amount'], 2); ?>
                    </div>
                </div>
            </div>
            
            <!-- Thank You Note -->
            <div style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 8px; text-align: center;">
                <p style="margin: 0; color: #333; font-size: 12px;">
                    <i class="fas fa-heart" style="color: #FFD700;"></i> Thank you for shopping with us! 
                    Your order will be processed within 24 hours.
                </p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="invoice-footer">
            <p><strong>UHD-Wears - Premium Clothing Brand</strong></p>
            <p>Gulberg, Lahore, Pakistan | +92 3XX XXXXXXX | info@uhdwears.com</p>
            <p>For support, contact our customer service team.</p>
            <p style="margin-top: 10px; font-size: 9px;">
                This is a computer generated invoice and requires no signature.
            </p>
        </div>
    </div>
</div>

<!-- Success Card -->
<div class="success-card">
    <div class="checkmark"><i class="fas fa-check"></i></div>
    <h2 style="color: #FFD700;">Order Placed!</h2>
    <p>Thank you for shopping with UHD-Wears</p>
    <div class="order-number"><strong>Order #:</strong> <?php echo $order['order_number']; ?></div>
    <p><strong>Total:</strong> Rs <?php echo number_format($order['total_amount'], 2); ?></p>
    <p><strong>Payment:</strong> <?php echo ucfirst($order['payment_method']); ?></p>
    
    <div class="d-flex justify-content-center gap-3 mt-4 flex-wrap">
        <button onclick="downloadBill()" class="btn btn-primary-custom btn-download">
            <i class="fas fa-download"></i> Download Invoice (PDF)
        </button>
        <a href="index.php" class="btn btn-primary-custom">
            <i class="fas fa-home"></i> Continue Shopping
        </a>
        <a href="user/orders.php" class="btn btn-outline-custom">
            <i class="fas fa-list"></i> View Orders
        </a>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
function downloadBill() {
    var element = document.getElementById('invoiceContent');
    
    var opt = {
        margin: [0.5, 0.5, 0.5, 0.5],
        filename: 'UHD-Wears_Invoice_<?php echo $order['order_number']; ?>.pdf',
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 2, 
            letterRendering: true,
            useCORS: true,
            logging: false
        },
        jsPDF: { 
            unit: 'in', 
            format: 'a4', 
            orientation: 'portrait'
        },
        pagebreak: { mode: ['css', 'legacy'] }
    };
    
    Swal.fire({
        title: 'Generating Invoice...',
        text: 'Please wait while we prepare your invoice',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    html2pdf().set(opt).from(element).save().then(function() {
        Swal.fire({
            title: 'Downloaded Successfully!',
            text: 'Your invoice has been downloaded.',
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
            text: 'Please try again or contact support.',
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