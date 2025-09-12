<?php
require_once __DIR__ . '/../config/database.php';

try {
    $db = (new Database())->getConnection();
    
    // Check if column already exists
    $stmt = $db->query("SHOW COLUMNS FROM products LIKE 'admin_approved'");
    if ($stmt->rowCount() == 0) {
        $db->exec("ALTER TABLE products ADD COLUMN admin_approved TINYINT(1) DEFAULT 0");
        echo "Added admin_approved column\n";
    } else {
        echo "admin_approved column already exists\n";
    }
    
    // Update existing products to be approved (for testing)
    $db->exec("UPDATE products SET admin_approved = 1 WHERE status = 'active'");
    echo "Updated existing active products to approved\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
