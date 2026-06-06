<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$offset = isset($_POST['offset']) ? intval($_POST['offset']) : 0;
$limit = 5;

$sql = "SELECT orders.*, users.full_name 
        FROM orders 
        JOIN users ON orders.user_id = users.id 
        ORDER BY orders.order_date DESC 
        LIMIT $offset, $limit";

$result = mysqli_query($conn, $sql);
$orders = [];

while($row = mysqli_fetch_assoc($result)) {
    $orders[] = [
        'order_number' => $row['order_number'],
        'full_name' => $row['full_name'],
        'total_amount' => floatval($row['total_amount']),
        'status' => $row['status'],
        'order_date' => date('M d, Y', strtotime($row['order_date']))
    ];
}

echo json_encode(['success' => true, 'orders' => $orders]);
?>