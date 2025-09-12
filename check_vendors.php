<?php
require_once 'config/database.php';

$database = new Database();
$db = $database->getConnection();

echo "Checking vendors in database...\n";

$stmt = $db->query("SELECT id, user_id, business_name FROM vendors");
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($vendors) . " vendors:\n";
foreach ($vendors as $vendor) {
    echo "- ID: {$vendor['id']}, User ID: {$vendor['user_id']}, Business: {$vendor['business_name']}\n";
}

echo "\nChecking categories...\n";
$stmt = $db->query("SELECT id, name FROM categories");
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Found " . count($categories) . " categories:\n";
foreach ($categories as $category) {
    echo "- ID: {$category['id']}, Name: {$category['name']}\n";
}
?>
