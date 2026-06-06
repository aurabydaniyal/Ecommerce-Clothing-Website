<?php
session_start();
require_once '../db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header('Location: ../login.php');
    exit();
}

if(isset($_POST['update_tracking'])) {
    $order_id = $_POST['order_id'];
    $status = $_POST['status'];
    $notes = mysqli_real_escape_string($conn, $_POST['notes']);
    
    // Update order status
    mysqli_query($conn, "UPDATE orders SET status = '$status' WHERE id = '$order_id'");
    
    // Add tracking record
    mysqli_query($conn, "INSERT INTO order_tracking (order_id, status, notes, updated_by) VALUES ('$order_id', '$status', '$notes', '{$_SESSION['user_id']}')");
    
    header('Location: orders.php?msg=updated');
    exit();
}
?>