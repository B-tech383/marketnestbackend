<?php
// Site configuration
define('SITE_NAME', 'OrangeCart');
define('SITE_URL', 'https://' . $_SERVER['HTTP_HOST']);
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB

// Tracking pricing
define('FREE_TRACKING_LIMIT', 2);
define('STANDARD_TRACKING_PRICE', 5.99);
define('PREMIUM_TRACKING_PRICE', 9.99);

// Session configuration
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS

session_start();

// Include database connection
require_once 'database.php';

// Helper functions
function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function is_logged_in() {
    return isset($_SESSION['user_id']);
}

function get_user_role() {
    return $_SESSION['user_role'] ?? 'customer';
}

function require_login() {
    if (!is_logged_in()) {
        redirect('login.php');
    }
}

function require_admin() {
    require_login();
    if (get_user_role() !== 'admin') {
        redirect('index.php');
    }
}

function require_vendor() {
    require_login();
    if (!in_array(get_user_role(), ['vendor', 'admin'])) {
        redirect('index.php');
    }
}
?>
