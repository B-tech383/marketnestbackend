<?php
// Session configuration must be first, before any output
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', 0); // Set to 1 for HTTPS
    session_start();
}

// Site configuration
if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Market Nest'); // Default fallback
}
if (!defined('SITE_URL')) {
    define('SITE_URL', 'https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost:5000'));
}
if (!defined('UPLOAD_PATH')) {
    define('UPLOAD_PATH', 'uploads/');
}
if (!defined('MAX_FILE_SIZE')) {
    define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
}

// Currency configuration
if (!defined('CURRENCY_SYMBOL')) {
    define('CURRENCY_SYMBOL', 'XAF');
}
if (!defined('CURRENCY_NAME')) {
    define('CURRENCY_NAME', 'Central African CFA Franc');
}
if (!defined('CURRENCY_POSITION')) {
    define('CURRENCY_POSITION', 'after'); // 'before' or 'after'
}

// Tracking pricing
if (!defined('FREE_TRACKING_LIMIT')) {
    define('FREE_TRACKING_LIMIT', 2);
}
if (!defined('STANDARD_TRACKING_PRICE')) {
    define('STANDARD_TRACKING_PRICE', 5.99);
}
if (!defined('PREMIUM_TRACKING_PRICE')) {
    define('PREMIUM_TRACKING_PRICE', 9.99);
}

// Include database connection
require_once 'database.php';

// Helper functions
function sanitize_input($data) {
    if ($data === null) {
        return '';
    }
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

/**
 * Check if user has a specific role
 */
function has_role($role) {
    $user_roles = $_SESSION['user_roles'] ?? [];
    return in_array($role, $user_roles);
}

/**
 * Check if user has any of the specified roles
 */
function has_any_role($roles) {
    $user_roles = $_SESSION['user_roles'] ?? [];
    return !empty(array_intersect($roles, $user_roles));
}

/**
 * Get all user roles
 */
function get_user_roles() {
    return $_SESSION['user_roles'] ?? [];
}

function require_login() {
    if (!is_logged_in()) {
        // Check if we're in admin or vendor directory and adjust path accordingly
        $in_subdirectory = (strpos($_SERVER['REQUEST_URI'], '/admin/') !== false) || 
                          (strpos($_SERVER['REQUEST_URI'], '/vendor/') !== false);
        $login_path = $in_subdirectory ? '../login.php' : 'login.php';
        redirect($login_path);
    }
}

function require_admin() {
    require_login();
    if (get_user_role() !== 'admin') {
        redirect('../index.php');
    }
}

function require_vendor() {
    require_login();
    if (!in_array(get_user_role(), ['vendor', 'admin'])) {
        // Check if we're in vendor directory and adjust path accordingly
        $index_path = (strpos($_SERVER['REQUEST_URI'], '/vendor/') !== false) ? '../index.php' : 'index.php';
        redirect($index_path);
    }
}

// CSRF Protection functions
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Format currency amount
 */
function format_currency($amount, $show_symbol = true) {
    $formatted = number_format($amount, 0, '.', ' ');
    
    if ($show_symbol) {
        if (CURRENCY_POSITION === 'before') {
            return CURRENCY_SYMBOL . ' ' . $formatted;
        } else {
            return $formatted . ' ' . CURRENCY_SYMBOL;
        }
    }
    
    return $formatted;
}

function verify_csrf_token($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Additional auth helper functions
function isLoggedIn() {
    return is_logged_in();
}



function getCurrentUser() {
    if (!is_logged_in()) {
        return null;
    }
    
    $database = new Database();
    $db = $database->getConnection();
    
    try {
        $stmt = $db->prepare("
            SELECT u.*, ur.role 
            FROM users u 
            LEFT JOIN user_roles ur ON u.id = ur.user_id 
            WHERE u.id = ?
        ");
        $stmt->execute([$_SESSION['user_id']]);
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (PDOException $e) {
        return null;
    }
}
?>
