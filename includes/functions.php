<?php
require_once 'db_connection.php';

// Sanitize input
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)));
}

// Check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Check if user is admin
function isAdmin() {
    return isset($_SESSION['user_role']) && $_SESSION['user_role'] == 'admin';
}

// Redirect if not logged in
function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . SITE_URL . 'login.php');
        exit();
    }
}

// Redirect if not admin
function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . SITE_URL . 'index.php');
        exit();
    }
}

// Get cart count
function getCartCount() {
    global $conn;
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $result = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE user_id = '$user_id'");
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    } elseif (isset($_COOKIE['cart_session'])) {
        $session_id = $_COOKIE['cart_session'];
        $result = mysqli_query($conn, "SELECT SUM(quantity) as total FROM cart WHERE session_id = '$session_id'");
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    return 0;
}

// Get wishlist count
function getWishlistCount() {
    global $conn;
    if (isLoggedIn()) {
        $user_id = $_SESSION['user_id'];
        $result = mysqli_query($conn, "SELECT COUNT(*) as total FROM wishlist WHERE user_id = '$user_id'");
        $row = mysqli_fetch_assoc($result);
        return $row['total'] ?? 0;
    }
    return 0;
}

// Get product by ID
function getProductById($id) {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM products WHERE id = '$id'");
    return mysqli_fetch_assoc($result);
}

// Update product views
function updateProductViews($product_id) {
    global $conn;
    mysqli_query($conn, "UPDATE products SET views = views + 1 WHERE id = '$product_id'");
}

// Generate order number
function generateOrderNumber() {
    return 'ORD-' . date('Ymd') . '-' . uniqid();
}

// Clean old cart sessions
function cleanOldCartSessions() {
    global $conn;
    $expiry = date('Y-m-d H:i:s', time() - CART_EXPIRY);
    mysqli_query($conn, "DELETE FROM cart WHERE session_id IS NOT NULL AND added_at < '$expiry'");
}

// Get user by ID
function getUserById($id) {
    global $conn;
    $result = mysqli_query($conn, "SELECT * FROM users WHERE id = '$id'");
    return mysqli_fetch_assoc($result);
}
?>