<?php
require_once '../config/config.php';
require_once '../includes/product.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        echo json_encode(['success' => false, 'message' => 'Product ID required']);
        exit;
    }

    $product_id = (int)$_GET['id'];
    if ($product_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'Invalid product ID']);
        exit;
    }

    $productManager = new ProductManager();

    // Get product details
    $product = $productManager->get_product_by_id($product_id);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit;
    }
} catch (Exception $e) {
    error_log("Product preview API error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred while loading the product']);
    exit;
}

// Sanitize and prepare product data
$name = htmlspecialchars($product['name'] ?? 'Unnamed Product');
$description = htmlspecialchars($product['description'] ?? 'No description available');
$business_name = htmlspecialchars($product['business_name'] ?? 'Unknown Vendor');
$category_name = htmlspecialchars($product['category_name'] ?? 'Uncategorized');
$sku = htmlspecialchars($product['sku'] ?? 'N/A');
$price = floatval($product['price'] ?? 0);
$sale_price = !empty($product['sale_price']) ? floatval($product['sale_price']) : 0;
$stock_quantity = intval($product['stock_quantity'] ?? 0);
$avg_rating = floatval($product['avg_rating'] ?? 0);
$review_count = intval($product['review_count'] ?? 0);
$is_featured = !empty($product['is_featured']);
$is_verified = !empty($product['is_verified']);
$admin_approved = !empty($product['admin_approved']);
$status = htmlspecialchars($product['status'] ?? 'inactive');

// Handle image URL
$image_html = '<div class="w-full h-48 bg-gray-200 flex items-center justify-center">
    <span class="text-gray-400">No Image</span>
</div>';

if (!empty($product['images']) && is_array($product['images'])) {
    $img_src = $product['images'][0];
    if (strpos($img_src, 'uploads/') === 0) {
        $img_src = '../' . $img_src;
    } elseif (strpos($img_src, 'http://') !== 0 && strpos($img_src, 'https://') !== 0) {
        $img_src = '../uploads/products/' . basename($img_src);
    }
    $image_html = '<img src="' . htmlspecialchars($img_src) . '" alt="' . $name . '" class="w-full h-48 object-cover">';
}

// Generate preview HTML
$html = '
<div class="bg-white rounded-lg shadow-md overflow-hidden">
    <div class="relative">
        ' . $image_html . '
        ' . ($is_featured ? '<div class="absolute top-2 left-2 bg-blue-500 text-white text-xs px-2 py-1 rounded">Featured</div>' : '') . '
        ' . ($sale_price > 0 ? '<div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">Sale</div>' : '') . '
    </div>
    
    <div class="p-4">
        <div class="flex items-center mb-2">
            <span class="text-sm text-gray-600">' . $business_name . '</span>
            ' . ($is_verified ? '<span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Verified</span>' : '') . '
        </div>
        
        <h3 class="font-medium text-gray-900 mb-2">' . $name . '</h3>
        
        <p class="text-sm text-gray-600 mb-3">' . (strlen($description) > 150 ? substr($description, 0, 150) . '...' : $description) . '</p>
        
        <div class="flex items-center mb-2">
            ' . ($avg_rating > 0 ? 
                '<div class="flex items-center">
                    <span class="text-yellow-400">★</span>
                    <span class="text-sm text-gray-600 ml-1">' . number_format($avg_rating, 1) . ' (' . $review_count . ')</span>
                </div>' :
                '<span class="text-sm text-gray-500">No reviews yet</span>') . '
        </div>
        
        <div class="flex items-center justify-between">
            <div>
                ' . ($sale_price > 0 && $sale_price < $price ? 
                    '<span class="text-lg font-bold text-red-600">$' . number_format($sale_price, 2) . '</span>
                     <span class="text-sm text-gray-500 line-through ml-2">$' . number_format($price, 2) . '</span>' :
                    '<span class="text-lg font-bold text-gray-900">$' . number_format($price, 2) . '</span>') . '
            </div>
            
            ' . ($stock_quantity > 0 ? 
                '<span class="text-sm text-green-600">In Stock (' . $stock_quantity . ')</span>' :
                '<span class="text-sm text-red-600">Out of Stock</span>') . '
        </div>
        
        <div class="mt-3 pt-3 border-t">
            <div class="text-xs text-gray-500">
                <p><strong>SKU:</strong> ' . $sku . '</p>
                <p><strong>Category:</strong> ' . $category_name . '</p>
                <p><strong>Status:</strong> ' . ucfirst($status) . '</p>
                <p><strong>Admin Approved:</strong> ' . ($admin_approved ? 'Yes' : 'No') . '</p>
            </div>
        </div>
    </div>
</div>';

echo json_encode(['success' => true, 'html' => $html]);
?>
