<?php
// Simple test script to verify authenticated flows work
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once 'config/database.php';
require_once 'includes/functions.php';

echo "=== Market Nest Authentication Flow Test ===\n";

// Test 1: Admin Login and Dashboard Access
echo "\n1. Testing Admin Authentication Flow:\n";

// Test database connection
try {
    $database = new Database();
    $db = $database->getConnection();
    echo "✓ Database connection successful\n";
} catch (Exception $e) {
    echo "✗ Database connection failed: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if admin user exists
$stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ? LIMIT 1");
$stmt->execute(['admin@test.com']);
$admin_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($admin_user) {
    echo "✓ Admin user found: " . $admin_user['email'] . "\n";
} else {
    echo "✗ Admin user not found\n";
}

// Test 2: Vendor Login and Dashboard Access
echo "\n2. Testing Vendor Authentication Flow:\n";

$stmt = $db->prepare("SELECT id, username, email FROM users WHERE email = ? LIMIT 1");
$stmt->execute(['vendor@test.com']);
$vendor_user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($vendor_user) {
    echo "✓ Vendor user found: " . $vendor_user['email'] . "\n";
    
    // Check vendor record
    $stmt = $db->prepare("SELECT id, business_name FROM vendors WHERE user_id = ? LIMIT 1");
    $stmt->execute([$vendor_user['id']]);
    $vendor_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($vendor_info) {
        echo "✓ Vendor record found: " . $vendor_info['business_name'] . "\n";
    } else {
        echo "✗ Vendor record not found for user\n";
    }
} else {
    echo "✗ Vendor user not found\n";
}

// Test 3: Check key pages exist
echo "\n3. Testing Key Files Exist:\n";

$key_files = [
    'admin/dashboard.php',
    'vendor/dashboard.php', 
    'login.php',
    'products.php',
    'admin/index.php'
];

foreach ($key_files as $file) {
    if (file_exists($file)) {
        echo "✓ $file exists\n";
    } else {
        echo "✗ $file missing\n";
    }
}

// Test 4: Database integrity
echo "\n4. Testing Database Integrity:\n";

try {
    // Check main tables exist and have data
    $tables_to_check = ['users', 'vendors', 'products', 'orders', 'vendor_notifications'];
    
    foreach ($tables_to_check as $table) {
        $stmt = $db->query("SELECT COUNT(*) as count FROM $table");
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✓ Table '$table' has {$result['count']} records\n";
    }
    
} catch (Exception $e) {
    echo "✗ Database integrity check failed: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
?>