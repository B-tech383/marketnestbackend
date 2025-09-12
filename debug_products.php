<?php
require_once './config/config.php';
require_once './includes/product.php';

echo "=== Product Debug Script ===\n";

$product_manager = new ProductManager();

// Test basic product retrieval
echo "1. Testing get_products():\n";
$products = $product_manager->get_products(5, 0);
echo "Found " . count($products) . " products\n";

foreach ($products as $product) {
    echo "- ID: {$product['id']}, Name: {$product['name']}, Status: {$product['status']}\n";
    echo "  Category: {$product['category_name']}, Vendor: {$product['business_name']}\n";
    echo "  Images: " . print_r($product['images'], true) . "\n";
    echo "\n";
}

// Test categories
echo "2. Testing get_categories():\n";
$categories = $product_manager->get_categories();
echo "Found " . count($categories) . " categories\n";

foreach (array_slice($categories, 0, 10) as $category) {
    echo "- ID: {$category['id']}, Name: {$category['name']}, Product Count: {$category['product_count']}\n";
}

// Test specific category (Sports)
echo "\n3. Testing Sports category products:\n";
$sports_category = null;
foreach ($categories as $cat) {
    if ($cat['name'] === 'Sports') {
        $sports_category = $cat;
        break;
    }
}

if ($sports_category) {
    echo "Sports category ID: {$sports_category['id']}\n";
    $sports_products = $product_manager->get_products(10, 0, $sports_category['id']);
    echo "Found " . count($sports_products) . " sports products\n";
    
    foreach ($sports_products as $product) {
        echo "- {$product['name']}\n";
    }
} else {
    echo "Sports category not found!\n";
}

echo "\n=== Debug Complete ===\n";
?>