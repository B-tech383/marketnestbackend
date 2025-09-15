<?php
require_once 'config/database.php';
require_once 'includes/product.php';

echo "Testing product display...\n";

try {
    $productManager = new ProductManager();
    
    // Test getting all products
    $products = $productManager->get_products(10);
    echo "Found " . count($products) . " products:\n";
    
    foreach ($products as $product) {
        echo "- ID: {$product['id']}, Name: {$product['name']}, Price: {$product['price']}, Status: {$product['status']}\n";
    }
    
    // Test getting featured products
    $featured = method_exists($productManager, 'get_featured_products') ? $productManager->get_featured_products(5) : $productManager->getFeaturedProducts(5);
    echo "\nFound " . count($featured) . " featured products:\n";
    
    foreach ($featured as $product) {
        echo "- ID: {$product['id']}, Name: {$product['name']}, Featured: " . ($product['is_featured'] ? 'Yes' : 'No') . "\n";
    }
    
    // Test getting categories
    $categories = $productManager->get_categories();
    echo "\nFound " . count($categories) . " categories:\n";
    
    foreach ($categories as $category) {
        echo "- ID: {$category['id']}, Name: {$category['name']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
