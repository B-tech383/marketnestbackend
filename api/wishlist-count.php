<?php
require_once '../config/config.php';
require_once '../includes/wishlist.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['count' => 0]);
    exit;
}

$wishlist_manager = new WishlistManager();
$count = $wishlist_manager->getWishlistCount($_SESSION['user_id']);

echo json_encode(['count' => $count]);
?>

