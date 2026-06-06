<?php
require_once 'includes/db_connection.php';

echo "<h2>Database Check</h2>";

$sql = "SELECT * FROM users WHERE username = 'itxadmin'";
$result = mysqli_query($conn, $sql);

if(mysqli_num_rows($result) > 0) {
    $user = mysqli_fetch_assoc($result);
    echo "<p>✅ Admin user found!</p>";
    echo "<pre>";
    print_r($user);
    echo "</pre>";
    
    // Test the password
    $test_password = 'admin123';
    echo "<p>Testing password: <strong>$test_password</strong></p>";
    
    if(password_verify($test_password, $user['password'])) {
        echo "<p style='color:green; font-size:20px;'>✅ PASSWORD IS CORRECT! Login should work.</p>";
    } else {
        echo "<p style='color:red; font-size:20px;'>❌ PASSWORD HASH MISMATCH! Need to fix.</p>";
        
        // Fix the password
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        $update = "UPDATE users SET password = '$new_hash' WHERE username = 'itxadmin'";
        
        if(mysqli_query($conn, $update)) {
            echo "<p style='color:green;'>✅ Password has been reset! Hash: $new_hash</p>";
            echo "<p>Try logging in now with: <strong>itxadmin / admin123</strong></p>";
        } else {
            echo "<p style='color:red;'>Error updating: " . mysqli_error($conn) . "</p>";
        }
    }
} else {
    echo "<p style='color:red;'>❌ Admin user NOT found! Creating now...</p>";
    
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $insert = "INSERT INTO users (username, email, password, full_name, role) 
               VALUES ('itxadmin', 'admin@uhdwears.com', '$new_hash', 'Admin User', 'admin')";
    
    if(mysqli_query($conn, $insert)) {
        echo "<p style='color:green;'>✅ Admin user created successfully!</p>";
        echo "<p>Username: <strong>itxadmin</strong></p>";
        echo "<p>Password: <strong>admin123</strong></p>";
        echo "<p>Hash: $new_hash</p>";
    } else {
        echo "<p style='color:red;'>Error: " . mysqli_error($conn) . "</p>";
    }
}

// Show all users
echo "<h3>All Users in Database:</h3>";
$all = mysqli_query($conn, "SELECT id, username, email, role FROM users");
echo "<table border='1' cellpadding='8' style='border-collapse: collapse;'>";
echo "<tr style='background:#667eea; color:white;'><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
while($row = mysqli_fetch_assoc($all)) {
    echo "<tr>";
    echo "<td>" . $row['id'] . "</td>";
    echo "<td>" . $row['username'] . "</td>";
    echo "<td>" . $row['email'] . "</td>";
    echo "<td>" . $row['role'] . "</td>";
    echo "</tr>";
}
echo "</table>";
?>