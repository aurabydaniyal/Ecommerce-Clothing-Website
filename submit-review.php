<?php
session_start();
require_once 'includes/db_connection.php';

if(!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $product_id = $_POST['product_id'];
    $user_id = $_SESSION['user_id'];
    $rating = $_POST['rating'];
    $comment = mysqli_real_escape_string($conn, $_POST['comment']);
    
    $sql = "INSERT INTO reviews (product_id, user_id, rating, comment) 
            VALUES ('$product_id', '$user_id', '$rating', '$comment')";
    
    if(mysqli_query($conn, $sql)) {
        // Update product rating
        $avg_sql = "SELECT AVG(rating) as avg_rating FROM reviews WHERE product_id = '$product_id'";
        $avg_result = mysqli_query($conn, $avg_sql);
        $avg = mysqli_fetch_assoc($avg_result);
        $new_rating = round($avg['avg_rating']);
        
        mysqli_query($conn, "UPDATE products SET rating = '$new_rating' WHERE id = '$product_id'");
        
        header("Location: product-detail.php?id=$product_id&review=success");
    } else {
        header("Location: product-detail.php?id=$product_id&review=error");
    }
}
?>