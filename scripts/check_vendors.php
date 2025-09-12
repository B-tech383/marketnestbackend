<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== VENDORS TABLE ===\n";
    $stmt = $db->query('SELECT id, user_id, business_name FROM vendors LIMIT 5');
    foreach($stmt as $row) {
        echo "Vendor ID: {$row['id']}, User ID: {$row['user_id']}, Business: {$row['business_name']}\n";
    }
    
    echo "\n=== PRODUCTS TABLE ===\n";
    $stmt = $db->query('SELECT id, vendor_id, name, admin_approved FROM products LIMIT 5');
    foreach($stmt as $row) {
        echo "Product ID: {$row['id']}, Vendor ID: {$row['vendor_id']}, Name: {$row['name']}, Approved: {$row['admin_approved']}\n";
    }
    
    echo "\n=== USERS TABLE ===\n";
    $stmt = $db->query('SELECT id, first_name, last_name FROM users LIMIT 5');
    foreach($stmt as $row) {
        echo "User ID: {$row['id']}, Name: {$row['first_name']} {$row['last_name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
