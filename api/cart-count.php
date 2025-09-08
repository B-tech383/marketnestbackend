<?php
require_once '../config/config.php';
require_once '../includes/cart.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['count' => 0]);
    exit;
}

$cart_manager = new CartManager();
$count = $cart_manager->get_cart_count($_SESSION['user_id']);

echo json_encode(['count' => $count]);
?>
