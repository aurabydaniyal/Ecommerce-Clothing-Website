<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if(isset($_POST['order_id'])) {
    $order_id = intval($_POST['order_id']);
    
    // Get order details
    $order_query = mysqli_query($conn, "SELECT order_number, order_date, total_amount, status, payment_method FROM orders WHERE id = '$order_id'");
    $order = mysqli_fetch_assoc($order_query);
    
    if(!$order) {
        echo json_encode(['success' => false, 'message' => 'Order not found']);
        exit();
    }
    
    // Get order items with product details
    $items_query = "SELECT oi.*, p.name, p.image_url 
                    FROM order_items oi 
                    JOIN products p ON oi.product_id = p.id 
                    WHERE oi.order_id = '$order_id'";
    $items_result = mysqli_query($conn, $items_query);
    
    $items = [];
    while($item = mysqli_fetch_assoc($items_result)) {
        $items[] = [
            'name' => $item['name'],
            'quantity' => $item['quantity'],
            'price' => floatval($item['price']),
            'size' => $item['size'] ?? '-',
            'color' => $item['color'] ?? '-',
            'image' => $item['image_url'] ?? ''
        ];
    }
    
    echo json_encode([
        'success' => true,
        'order_number' => $order['order_number'],
        'order_date' => date('F j, Y', strtotime($order['order_date'])),
        'total_amount' => floatval($order['total_amount']),
        'status' => ucfirst($order['status']),
        'payment_method' => ucfirst($order['payment_method']),
        'items' => $items
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'No order ID provided']);
}
?>