<?php
/**
 * Session Check & Security Functions
 * Include this file in pages that require login
 */

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Check if session is valid and not expired
 */
function checkSession() {
    // Check if session exists
    if (!isset($_SESSION['user_id'])) {
        // Clear any remaining cookies
        clearSessionCookies();
        
        // Redirect to login with expired message
        header('Location: ../login.php?session_expired=1');
        exit();
    }
    
    // Check session timeout (30 minutes = 1800 seconds)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
        // Session expired - clear everything
        session_unset();
        session_destroy();
        clearSessionCookies();
        
        header('Location: ../login.php?timeout=1');
        exit();
    }
    
    // Regenerate session ID periodically to prevent fixation (every 30 minutes)
    if (!isset($_SESSION['regenerated_at']) || (time() - $_SESSION['regenerated_at'] > 1800)) {
        session_regenerate_id(true);
        $_SESSION['regenerated_at'] = time();
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    return true;
}

/**
 * Clear all session-related cookies
 */
function clearSessionCookies() {
    $cookie_names = ['is_logged_in', 'user_id', 'user_name', 'user_email', 'user_role', 'cart_session'];
    
    foreach ($cookie_names as $cookie_name) {
        if (isset($_COOKIE[$cookie_name])) {
            setcookie($cookie_name, '', time() - 3600, '/');
            setcookie($cookie_name, '', time() - 3600, '/', '', false, true);
        }
    }
}

/**
 * Require login - use this on protected pages
 */
function requireLogin() {
    checkSession();
}

/**
 * Require admin access
 */
function requireAdmin() {
    checkSession();
    
    if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] != 'admin') {
        header('Location: ../index.php?access_denied=1');
        exit();
    }
}

/**
 * Check if user is logged in (returns boolean, no redirect)
 */
function isLoggedIn() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    return isset($_SESSION['user_id']) && 
           isset($_SESSION['last_activity']) && 
           (time() - $_SESSION['last_activity'] <= 1800);
}

/**
 * Get current user ID (returns null if not logged in)
 */
function getCurrentUserId() {
    if (isLoggedIn()) {
        return $_SESSION['user_id'];
    }
    return null;
}

/**
 * Get current user role (returns null if not logged in)
 */
function getCurrentUserRole() {
    if (isLoggedIn()) {
        return $_SESSION['user_role'] ?? null;
    }
    return null;
}
?>