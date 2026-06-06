<?php
require_once 'db_connection.php';

echo "<h2>🔍 Password Debug Tool</h2>";

// Check if admin exists
$result = mysqli_query($conn, "SELECT id, username, email, password, role FROM users WHERE username = 'itxadmin' OR role = 'admin'");

if(mysqli_num_rows($result) > 0) {
    $admin = mysqli_fetch_assoc($result);
    echo "<h3>✅ Admin User Found:</h3>";
    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Field</th><th>Value</th></tr>";
    echo "<tr><td>ID</td><td>{$admin['id']}</td></tr>";
    echo "<tr><td>Username</td><td>{$admin['username']}</td></tr>";
    echo "<tr><td>Email</td><td>{$admin['email']}</td></tr>";
    echo "<tr><td>Role</td><td>{$admin['role']}</td></tr>";
    echo "<tr><td>Password Hash</td><td><code>{$admin['password']}</code></td></tr>";
    echo "</table>";
    
    // Test the password
    $test_password = 'admin123';
    if(password_verify($test_password, $admin['password'])) {
        echo "<p style='color:green; font-size:18px;'>✅ PASSWORD VERIFIED! The hash is correct.</p>";
        echo "<p>Your login should work. If not, the issue is in login.php</p>";
    } else {
        echo "<p style='color:red; font-size:18px;'>❌ PASSWORD HASH MISMATCH! Need to reset password.</p>";
        
        // Fix it immediately
        $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
        mysqli_query($conn, "UPDATE users SET password = '$new_hash' WHERE id = {$admin['id']}");
        echo "<p style='color:green;'>✅ Password has been reset! New hash: <code>$new_hash</code></p>";
        echo "<p>Try logging in now with: <strong>itxadmin / admin123</strong></p>";
    }
} else {
    echo "<p style='color:red'>❌ No admin user found! Creating one...</p>";
    
    $new_hash = password_hash('admin123', PASSWORD_DEFAULT);
    $insert = mysqli_query($conn, "INSERT INTO users (username, email, password, full_name, role) 
                                   VALUES ('itxadmin', 'admin@uhdwears.com', '$new_hash', 'Administrator', 'admin')");
    
    if($insert) {
        echo "<p style='color:green'>✅ Admin user created! Hash: <code>$new_hash</code></p>";
        echo "<p>Try logging in with: <strong>itxadmin / admin123</strong></p>";
    } else {
        echo "<p style='color:red'>❌ Error: " . mysqli_error($conn) . "</p>";
    }
}

// Show all users
echo "<h3>📋 All Users in Database:</h3>";
$all_users = mysqli_query($conn, "SELECT id, username, email, role FROM users");
echo "<table border='1' cellpadding='8'>";
echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th></tr>";
while($user = mysqli_fetch_assoc($all_users)) {
    echo "<tr>";
    echo "<td>{$user['id']}</td>";
    echo "<td>{$user['username']}</td>";
    echo "<td>{$user['email']}</td>";
    echo "<td>{$user['role']}</td>";
    echo "</tr>";
}
echo "</table>";
?>