<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) { echo 'not_logged_in'; exit(); }

$product_id = intval($_POST['product_id']);
$user_id = $_SESSION['user_id'];

$check = mysqli_query($conn, "SELECT id FROM wishlist WHERE user_id = '$user_id' AND product_id = '$product_id'");
if(mysqli_num_rows($check) > 0) { echo 'already_exists'; exit(); }

mysqli_query($conn, "INSERT INTO wishlist (user_id, product_id) VALUES ('$user_id', '$product_id')");
echo 'success';
?>