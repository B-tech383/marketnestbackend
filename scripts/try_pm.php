<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/product.php';

$pm = new ProductManager();
$rows = $pm->get_products(20, 0);
echo 'count=' . count($rows) . "\n";
foreach ($rows as $r) {
    $img = isset($r['images'][0]) ? $r['images'][0] : 'noimg';
    echo "- {$r['id']} {$r['name']} [{$r['status']}] img={$img}\n";
}
?>


