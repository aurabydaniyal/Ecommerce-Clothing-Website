<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo '<tr><td colspan="5" style="text-align: center; color: #ff6b6b;">Unauthorized</td></tr>';
    exit();
}

$type = isset($_GET['type']) ? $_GET['type'] : '';

if($type == 'orders') {
    // Get ALL orders
    $sql = "SELECT orders.order_number, users.full_name, orders.total_amount, orders.status, orders.order_date 
            FROM orders 
            JOIN users ON orders.user_id = users.id 
            ORDER BY orders.order_date DESC";
    
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            // Determine status badge class
            $status_class = '';
            if($row['status'] == 'pending') {
                $status_class = 'badge-pending';
            } elseif($row['status'] == 'processing') {
                $status_class = 'badge-processing';
            } elseif($row['status'] == 'shipped') {
                $status_class = 'badge-shipped';
            } elseif($row['status'] == 'delivered') {
                $status_class = 'badge-delivered';
            } elseif($row['status'] == 'cancelled') {
                $status_class = 'badge-cancelled';
            } else {
                $status_class = 'badge-secondary';
            }
            
            echo '<tr>
                <td><strong>' . htmlspecialchars($row['order_number']) . '</strong></td>
                <td>' . htmlspecialchars($row['full_name']) . '</td>
                <td>Rs ' . number_format($row['total_amount'], 2) . '</td>
                <td><span class="badge ' . $status_class . '">' . ucfirst($row['status']) . '</span></td>
                <td>' . date('M d, Y', strtotime($row['order_date'])) . '</td>
            </tr>';
        }
    } else {
        echo '<tr><td colspan="5" style="text-align: center; color: #888;">No orders found</td></tr>';
    }
    
} elseif($type == 'products') {
    // Get ALL products
    $sql = "SELECT id, name, price, stock, type FROM products ORDER BY id DESC";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) > 0) {
        while($row = mysqli_fetch_assoc($result)) {
            // Determine type badge class
            $type_badge = '';
            if($row['type'] == 'men') {
                $type_badge = 'primary';
            } elseif($row['type'] == 'women') {
                $type_badge = 'danger';
            } elseif($row['type'] == 'kids') {
                $type_badge = 'success';
            } elseif($row['type'] == 'sale') {
                $type_badge = 'warning';
            } else {
                $type_badge = 'secondary';
            }
            
            // Stock color
            $stock_class = ($row['stock'] < 5) ? 'text-danger' : 'text-success';
            
            echo '<tr>
                <td>' . $row['id'] . '</td>
                <td>' . htmlspecialchars($row['name']) . '</td>
                <td>Rs ' . number_format($row['price'], 2) . '</td>
                <td class="' . $stock_class . '"><strong>' . $row['stock'] . '</strong></td>
                <td><span class="badge bg-' . $type_badge . '">' . ucfirst($row['type']) . '</span></td>
            </tr>';
        }
    } else {
        echo '<tr><td colspan="5" style="text-align: center; color: #888;">No products found</td></tr>';
    }
}
?>