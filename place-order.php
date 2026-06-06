<?php
session_start();
require_once 'includes/db_connection.php';
require_once 'includes/functions.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get cart items
$cart_items = mysqli_query($conn, "SELECT cart.*, products.name, products.price, products.sale_price 
                                    FROM cart 
                                    JOIN products ON cart.product_id = products.id 
                                    WHERE cart.user_id = '$user_id'");

$total = 0;
while($item = mysqli_fetch_assoc($cart_items)) {
    $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
    $total += $price * $item['quantity'];
}

if($total == 0) {
    header('Location: cart.php');
    exit();
}

$address = mysqli_real_escape_string($conn, $_POST['address']);
$city = mysqli_real_escape_string($conn, $_POST['city']);
$state = mysqli_real_escape_string($conn, $_POST['state']);
$zip = mysqli_real_escape_string($conn, $_POST['zip']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

$order_number = generateOrderNumber();
$shipping_address = "$address, $city, $state - $zip";

// Create order
$sql = "INSERT INTO orders (order_number, user_id, total_amount, payment_method, shipping_address, shipping_city, shipping_state, shipping_zip) 
        VALUES ('$order_number', '$user_id', '$total', '$payment_method', '$shipping_address', '$city', '$state', '$zip')";

if(mysqli_query($conn, $sql)) {
    $order_id = mysqli_insert_id($conn);
    
    // Reset cart items query
    $cart_items = mysqli_query($conn, "SELECT cart.*, products.name, products.price, products.sale_price 
                                        FROM cart 
                                        JOIN products ON cart.product_id = products.id 
                                        WHERE cart.user_id = '$user_id'");
    
    // Add order items
    while($item = mysqli_fetch_assoc($cart_items)) {
        $price = $item['sale_price'] ? $item['sale_price'] : $item['price'];
        $sql = "INSERT INTO order_items (order_id, product_id, quantity, price, size, color) 
                VALUES ('$order_id', '{$item['product_id']}', '{$item['quantity']}', '$price', '{$item['size']}', '{$item['color']}')";
        mysqli_query($conn, $sql);
    }
    
    // Clear cart
    mysqli_query($conn, "DELETE FROM cart WHERE user_id = '$user_id'");
    
    header("Location: order-success.php?order=$order_number");
} else {
    header('Location: checkout.php?error=1');
}
?>