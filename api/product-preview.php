<?php
require_once '../config/config.php';
require_once '../includes/product.php';

header('Content-Type: application/json');

if (!isset($_GET['id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID required']);
    exit;
}

$product_id = (int)$_GET['id'];
$productManager = new ProductManager();

// Get product details
$product = $productManager->get_product_by_id_preview($product_id);

if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found']);
    exit;
}

// Generate preview HTML
$html = '
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="relative">
        ' . (!empty($product['images']) ? 
            '<img src="' . htmlspecialchars((preg_match('/^https?:\/\//', $product['images'][0]) ? $product['images'][0] : ('../' . $product['images'][0]))) . '" alt="' . htmlspecialchars($product['name']) . '" class="w-full h-48 object-cover">' :
            '<div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                <span class="text-gray-400">No Image</span>
            </div>') . '
        
        ' . ($product['is_featured'] ? '<div class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">Featured</div>' : '') . '
        
        ' . ($product['sale_price'] ? '<div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">Sale</div>' : '') . '
    </div>
    
    <div class="p-4">
        <div class="flex items-center mb-2">
            <span class="text-sm text-gray-600">' . htmlspecialchars($product['business_name']) . '</span>
            ' . ($product['is_verified'] ? '<span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Verified</span>' : '') . '
        </div>
        
        <h3 class="font-medium text-gray-900 mb-2">' . htmlspecialchars($product['name']) . '</h3>
        
        <p class="text-sm text-gray-600 mb-3">' . htmlspecialchars($product['description']) . '</p>
        
        <div class="flex items-center mb-2">
            ' . ($product['avg_rating'] > 0 ? 
                '<div class="flex items-center">
                    <span class="text-yellow-400">★</span>
                    <span class="text-sm text-gray-600 ml-1">' . $product['avg_rating'] . ' (' . $product['review_count'] . ')</span>
                </div>' :
                '<span class="text-sm text-gray-500">No reviews yet</span>') . '
        </div>
        
        <div class="flex items-center justify-between">
            <div>
                ' . ($product['sale_price'] ? 
                    '<span class="text-lg font-bold text-red-600">$' . number_format($product['sale_price'], 2) . '</span>
                     <span class="text-sm text-gray-500 line-through ml-2">$' . number_format($product['price'], 2) . '</span>' :
                    '<span class="text-lg font-bold text-gray-900">$' . number_format($product['price'], 2) . '</span>') . '
            </div>
            
            ' . ($product['stock_quantity'] > 0 ? 
                '<span class="text-sm text-green-600">In Stock (' . $product['stock_quantity'] . ')</span>' :
                '<span class="text-sm text-red-600">Out of Stock</span>') . '
        </div>
        
        <div class="mt-3 pt-3 border-t">
            <div class="text-xs text-gray-500">
                <p><strong>SKU:</strong> ' . htmlspecialchars($product['sku'] ?: 'N/A') . '</p>
                <p><strong>Category:</strong> ' . htmlspecialchars($product['category_name']) . '</p>
                <p><strong>Status:</strong> ' . ucfirst($product['status']) . '</p>
                <p><strong>Admin Approved:</strong> ' . ($product['admin_approved'] ? 'Yes' : 'No') . '</p>
            </div>
        </div>
    </div>
</div>';

echo json_encode(['success' => true, 'html' => $html]);
?>
