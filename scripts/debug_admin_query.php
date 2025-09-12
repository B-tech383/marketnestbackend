<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    echo "=== DIRECT SQL TEST ===\n";
    
    // Test the exact query from get_all_products_admin
    $sql = "
        SELECT 
            p.*,
            c.name AS category_name,
            v.business_name,
            v.email AS vendor_email,
            (
                SELECT ROUND(AVG(r2.rating),1) FROM reviews r2 WHERE r2.product_id = p.id
            ) AS avg_rating,
            (
                SELECT COUNT(r3.id) FROM reviews r3 WHERE r3.product_id = p.id
            ) AS review_count
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN vendors v ON p.vendor_id = v.id
        ORDER BY p.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Direct query returned: " . count($products) . " products\n";
    
    foreach (array_slice($products, 0, 3) as $product) {
        echo "ID: {$product['id']}, Name: {$product['name']}, Vendor: {$product['business_name']}, Approved: {$product['admin_approved']}\n";
    }
    
    echo "\n=== SIMPLE PRODUCTS QUERY ===\n";
    $stmt = $db->query("SELECT id, name, vendor_id, admin_approved FROM products LIMIT 5");
    foreach($stmt as $row) {
        echo "ID: {$row['id']}, Name: {$row['name']}, Vendor ID: {$row['vendor_id']}, Approved: {$row['admin_approved']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
