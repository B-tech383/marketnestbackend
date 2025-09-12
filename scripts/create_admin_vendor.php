<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if admin vendor already exists
    $stmt = $db->prepare("SELECT id FROM vendors WHERE business_name = 'Admin Store'");
    $stmt->execute();
    $adminVendor = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$adminVendor) {
        // Create admin vendor
        $stmt = $db->prepare("
            INSERT INTO vendors (user_id, business_name, description, is_verified, created_at) 
            VALUES (1, 'Admin Store', 'Official admin store for marketplace products', 1, NOW())
        ");
        $stmt->execute();
        $adminVendorId = $db->lastInsertId();
        echo "Created admin vendor with ID: $adminVendorId\n";
    } else {
        $adminVendorId = $adminVendor['id'];
        echo "Admin vendor already exists with ID: $adminVendorId\n";
    }
    
    // Update existing products with vendor_id = 0 to use admin vendor
    $stmt = $db->prepare("UPDATE products SET vendor_id = ? WHERE vendor_id = 0");
    $stmt->execute([$adminVendorId]);
    $updated = $stmt->rowCount();
    echo "Updated $updated products to use admin vendor\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
