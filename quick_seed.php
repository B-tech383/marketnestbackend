<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Quick seeding vendors and categories...\n";

try {
    // Clear existing data
    $db->exec("DELETE FROM products");
    $db->exec("DELETE FROM vendors");
    $db->exec("DELETE FROM categories");
    $db->exec("DELETE FROM user_roles");
    $db->exec("DELETE FROM users");
    echo "Cleared existing data\n";

    // Add categories
    $categories = [
        ['Electronics', 'Electronic gadgets and devices'],
        ['Clothing', 'Fashion and apparel'],
        ['Sports', 'Sports and outdoor equipment']
    ];

    $stmt = $db->prepare("INSERT INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category[0], $category[1]]);
        echo "Added category: {$category[0]}\n";
    }

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

    // Add sample product
    $stmt = $db->prepare("INSERT INTO products (vendor_id, category_id, name, description, price, stock_quantity, sku, images) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $vendor_business_id,
        1, // Electronics category
        'Test Product',
        'A test product for vendor',
        99.99,
        10,
        'TEST001',
        json_encode(['https://via.placeholder.com/400x400?text=Test+Product'])
    ]);
    echo "Added test product\n";

    echo "\nSeeding completed successfully!\n";
    echo "Vendor login: tech_store / vendor123\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
