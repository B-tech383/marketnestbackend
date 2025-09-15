<?php
require_once 'config/database.php';
require_once 'includes/product.php';

echo "Testing vendor product addition...\n";

try {
    $productManager = new ProductManager();
    
    // Test data
    $test_data = [
        'vendor_id' => 1, // Assuming vendor ID 1 exists
        'name' => 'Test Product',
        'description' => 'This is a test product',
        'price' => 99.99,
        'category_id' => 1, // Assuming category ID 1 exists
        'stock_quantity' => 10,
        'sku' => 'TEST001',
        'images' => ['https://via.placeholder.com/400x400?text=Test+Product']
    ];
    
    $result = $productManager->addProduct($test_data);
    
    if ($result['success']) {
        echo "✅ Product added successfully! Product ID: " . $result['product_id'] . "\n";
        // Ensure preview API can fetch it regardless of approval
        $preview = $productManager->get_product_by_id_preview($result['product_id']);
        if ($preview) {
            echo "Preview fetch OK: " . $preview['name'] . "\n";
        } else {
            echo "Preview fetch FAILED\n";
        }
    } else {
        echo "❌ Failed to add product: " . $result['message'] . "\n";
    }
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
