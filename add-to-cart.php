<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) { echo 'not_logged_in'; exit(); }

$product_id = intval($_POST['product_id']);
$quantity = intval($_POST['quantity'] ?? 1);
$size = $_POST['size'] ?? 'M';
$color = $_POST['color'] ?? 'Default';
$user_id = $_SESSION['user_id'];

$stock_check = mysqli_query($conn, "SELECT stock FROM products WHERE id = '$product_id'");
$product = mysqli_fetch_assoc($stock_check);
if(!$product || $product['stock'] < $quantity) { echo 'stock_limit'; exit(); }

$check = mysqli_query($conn, "SELECT id, quantity FROM cart WHERE user_id = '$user_id' AND product_id = '$product_id' AND size = '$size' AND color = '$color'");

if(mysqli_num_rows($check) > 0) {
    $existing = mysqli_fetch_assoc($check);
    $new_qty = $existing['quantity'] + $quantity;
    if($new_qty > $product['stock']) { echo 'stock_limit'; exit(); }
    mysqli_query($conn, "UPDATE cart SET quantity = '$new_qty' WHERE id = '{$existing['id']}'");
} else {
    mysqli_query($conn, "INSERT INTO cart (user_id, product_id, quantity, size, color) VALUES ('$user_id', '$product_id', '$quantity', '$size', '$color')");
}
echo 'success';
?>