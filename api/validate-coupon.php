<?php
require_once '../config/config.php';
require_once '../includes/coupon.php';
require_once '../includes/cart.php';

header('Content-Type: application/json');

if (!is_logged_in()) {
    echo json_encode(['success' => false, 'message' => 'Please log in to use coupons']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$coupon_code = sanitize_input($_POST['coupon_code'] ?? '');
$user_id = $_SESSION['user_id'];

if (empty($coupon_code)) {
    echo json_encode(['success' => false, 'message' => 'Please enter a coupon code']);
    exit;
}

try {
    $couponManager = new CouponManager();
    $cartManager = new CartManager();
    
    // Get cart items
    $cart_items = $cartManager->get_cart_items($user_id);
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Your cart is empty']);
        exit;
    }
    
    // Convert cart items to the format expected by coupon validation
    $items_for_validation = [];
    foreach ($cart_items as $item) {
        $items_for_validation[] = [
            'product_id' => $item['product_id'],
            'price' => $item['sale_price'] ?: $item['price'],
            'quantity' => $item['quantity']
        ];
    }
    
    // Validate coupon
    $result = $couponManager->validateCoupon($coupon_code, $user_id, $items_for_validation);
    
    if ($result['success']) {
        // Store coupon in session for checkout
        $_SESSION['applied_coupon'] = [
            'coupon' => $result['coupon'],
            'discount' => $result['discount']
        ];
        
        echo json_encode([
            'success' => true,
            'message' => 'Coupon applied successfully!',
            'coupon' => $result['coupon'],
            'discount' => $result['discount']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => $result['message']
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error validating coupon: ' . $e->getMessage()]);
}
?>
