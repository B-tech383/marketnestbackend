<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== BASIC CHECKS ===\n";
    
    // Check products count
    $stmt = $db->query("SELECT COUNT(*) as count FROM products");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total products: $count\n";
    
    // Check vendors count
    $stmt = $db->query("SELECT COUNT(*) as count FROM vendors");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total vendors: $count\n";
    
    // Check categories count
    $stmt = $db->query("SELECT COUNT(*) as count FROM categories");
    $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    echo "Total categories: $count\n";
    
    // Show first few products
    echo "\n=== FIRST 3 PRODUCTS ===\n";
    $stmt = $db->query("SELECT id, name, vendor_id, admin_approved FROM products LIMIT 3");
    foreach($stmt as $row) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Vendor: {$row['vendor_id']}, Approved: {$row['admin_approved']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
