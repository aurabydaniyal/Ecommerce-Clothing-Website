<?php
session_start();
require_once 'db_connection.php';

if(!isset($_SESSION['user_id'])) {
    echo 'not_logged_in';
    exit();
}

$user_id = $_SESSION['user_id'];
$result = mysqli_query($conn, "DELETE FROM wishlist WHERE user_id = '$user_id'");

if(mysqli_affected_rows($conn) >= 0) {
    echo 'success';
} else {
    echo 'error';
}
?>