<?php
session_start();
require_once 'db_connection.php';

if(isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $result = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'");
    $row = mysqli_fetch_assoc($result);
    echo $row['total'] ?? 0;
} else {
    echo 0;
}
?>