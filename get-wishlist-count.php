<?php
session_start();
require_once 'db_connection.php';

if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM wishlist WHERE user_id = '$user_id'");
    $row = mysqli_fetch_assoc($result);
    echo $row['total'];
} else {
    echo 0;
}
?>