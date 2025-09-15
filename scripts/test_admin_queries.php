<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/product.php';

$pm = new ProductManager();

echo "=== TESTING ADMIN QUERIES ===\n";

$admin = $pm->get_all_products_admin(10, 0, 'all');
echo "admin_all=" . count($admin) . "\n";

$pending = $pm->get_pending_products(10, 0);
echo "pending=" . count($pending) . "\n";

$approved = $pm->get_all_products_admin(10, 0, 'approved');
echo "approved=" . count($approved) . "\n";

echo "\n=== SAMPLE PRODUCTS ===\n";
foreach (array_slice($admin, 0, 3) as $product) {
    $img = (!empty($product['images']) && is_array($product['images'])) ? ($product['images'][0] ?? '(none)') : '(none)';
    echo "ID: {$product['id']}, Name: {$product['name']}, Approved: {$product['admin_approved']}, Img: {$img}\n";
}
?>
