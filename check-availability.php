<?php
session_start();
require_once 'db_connection.php';

if(isset($_POST['type']) && isset($_POST['value'])) {
    $type = mysqli_real_escape_string($conn, $_POST['type']);
    $value = mysqli_real_escape_string($conn, $_POST['value']);
    
    $exists = false;
    
    if($type == 'username') {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username = '$value'");
        $exists = mysqli_num_rows($check) > 0;
    } elseif($type == 'email') {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$value'");
        $exists = mysqli_num_rows($check) > 0;
    }
    
    echo json_encode(['exists' => $exists]);
} else {
    echo json_encode(['exists' => false]);
}
?>