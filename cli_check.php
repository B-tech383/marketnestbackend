<?php
require_once __DIR__ . '/config/database.php';

echo "DB diagnostics...\n";

try {
    $db = (new Database())->getConnection();
    $total = (int)$db->query("SELECT COUNT(*) FROM products")->fetchColumn();
    echo "products_total=".$total."\n";
    
    $stmt = $db->query("SELECT status, COUNT(*) c FROM products GROUP BY status");
    foreach ($stmt as $row) {
        echo $row['status'] . ':' . $row['c'] . "\n";
    }
    
    $stmt = $db->query("SELECT id,name,status,category_id,vendor_id,images FROM products ORDER BY created_at DESC LIMIT 5");
    foreach ($stmt as $row) {
        echo "- #{$row['id']} {$row['name']} [{$row['status']}] cat={$row['category_id']} vendor={$row['vendor_id']}\n";
    }
} catch (Throwable $e) {
    echo 'Error: ' . $e->getMessage() . "\n";
}
?>


