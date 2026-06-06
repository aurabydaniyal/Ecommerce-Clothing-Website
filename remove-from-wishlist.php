<?php
session_start();
require_once 'db_connection.php';

$id = intval($_POST['id']);
mysqli_query($conn, "DELETE FROM wishlist WHERE id = '$id'");
echo 'success';
?>