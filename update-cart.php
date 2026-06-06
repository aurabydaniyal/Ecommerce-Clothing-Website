<?php
session_start();
require_once 'db_connection.php';

$cart_id = intval($_POST['id']);
$quantity = intval($_POST['quantity']);

$cart = mysqli_fetch_assoc(mysqli_query($conn, "SELECT product_id FROM cart WHERE id = '$cart_id'"));
$stock = mysqli_fetch_assoc(mysqli_query($conn, "SELECT stock FROM products WHERE id = '{$cart['product_id']}'"));

if($quantity > $stock['stock']) { echo 'stock_limit'; exit(); }

mysqli_query($conn, "UPDATE cart SET quantity = '$quantity' WHERE id = '$cart_id'");
echo 'success';
?>