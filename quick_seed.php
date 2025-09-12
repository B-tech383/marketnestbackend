<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Quick seeding vendors and categories...\n";

try {
    // Clear existing data (respect FKs order)
    $db->exec("DELETE FROM order_items");
    $db->exec("DELETE FROM orders");
    $db->exec("DELETE FROM reviews");
    $db->exec("DELETE FROM wishlist");
    $db->exec("DELETE FROM cart");
    $db->exec("DELETE FROM recently_viewed");
    $db->exec("DELETE FROM products");
    $db->exec("DELETE FROM vendors");
    $db->exec("DELETE FROM user_roles");
    $db->exec("DELETE FROM users");
    $db->exec("DELETE FROM categories");
    echo "Cleared existing data\n";

    // Add categories
    $categories = [
        ['Electronics', 'Electronic gadgets and devices'],
        ['Clothing', 'Fashion and apparel'],
        ['Sports', 'Sports and outdoor equipment']
    ];

    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    $categoryIds = [];
    foreach ($categories as $category) {
        $stmt->execute([$category[0], $category[1]]);
        $categoryIds[$category[0]] = (int)$db->lastInsertId();
        echo "Added category: {$category[0]}\n";
    }

    // Add admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@example.com', $admin_password, 'Site', 'Admin']);
    $admin_user_id = $db->lastInsertId();
    $stmt = $db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'admin')");
    $stmt->execute([$admin_user_id]);
    echo "Added admin user\n";

    // Add vendor user
    $vendor_password = password_hash('vendor123', PASSWORD_DEFAULT);
    $stmt = $db->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['tech_store', 'tech@example.com', $vendor_password, 'Tech', 'Store']);
    $vendor_user_id = $db->lastInsertId();
    echo "Added vendor user\n";

    // Add vendor role
    $stmt = $db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
    $stmt->execute([$vendor_user_id, 'vendor']);
    echo "Added vendor role\n";

    // Add vendor business
    $stmt = $db->prepare("INSERT INTO vendors (user_id, business_name, description, is_verified) VALUES (?, ?, ?, ?)");
    $stmt->execute([$vendor_user_id, 'Tech Store Pro', 'Leading electronics store', 1]);
    $vendor_business_id = $db->lastInsertId();
    echo "Added vendor business (ID: $vendor_business_id)\n";

    // Add sample products (active, with images)
    $stmt = $db->prepare("INSERT INTO products (vendor_id, category_id, name, description, price, stock_quantity, sku, images, status, is_featured) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', 1)");
    $stmt->execute([
        $vendor_business_id,
        $categoryIds['Electronics'],
        'Smartphone X',
        'Flagship smartphone with AMOLED display',
        699.00,
        25,
        'SPX-001',
        json_encode(['https://via.placeholder.com/600x600?text=Smartphone+X'])
    ]);
    $stmt->execute([
        $vendor_business_id,
        $categoryIds['Sports'],
        'Football Pro',
        'Durable leather football for outdoor play',
        29.99,
        100,
        'FB-100',
        json_encode(['https://via.placeholder.com/600x600?text=Football+Pro'])
    ]);
    echo "Added sample products\n";

    echo "\nSeeding completed successfully!\n";
    echo "Vendor login: tech_store / vendor123\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
