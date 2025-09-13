<?php
require_once './config/config.php';
require_once './includes/cart.php';
require_once './includes/order.php';
require_once './includes/coupon.php';

require_login();

$cart_manager = new CartManager();
$order_manager = new OrderManager();
$coupon_manager = new CouponManager();

$cart_items = $cart_manager->get_cart_items($_SESSION['user_id']);
$cart_total = $cart_manager->get_cart_total($_SESSION['user_id']);

if (empty($cart_items)) {
    redirect('cart.php');
}

$error = '';
$success = '';
$coupon_discount = 0;
$coupon_message = '';
$applied_coupon = $_SESSION['checkout_coupon'] ?? null;

// Handle coupon validation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['apply_coupon'])) {
    $coupon_code = sanitize_input($_POST['coupon_code'] ?? '');
    
    // Convert cart items to the format expected by coupon validation
    $items_for_validation = [];
    foreach ($cart_items as $item) {
        $items_for_validation[] = [
            'product_id' => $item['product_id'],
            'price' => $item['sale_price'] ?: $item['price'],
            'quantity' => $item['quantity']
        ];
    }
    
    $result = $coupon_manager->validateCoupon($coupon_code, $_SESSION['user_id'], $items_for_validation);
    
    if ($result['success']) {
        $coupon_discount = $result['discount']['amount'];
        $coupon_message = "Coupon applied! You saved " . format_currency($coupon_discount);
        $_SESSION['checkout_coupon'] = $result['coupon'];
        $_SESSION['checkout_discount'] = $coupon_discount;
    } else {
        $coupon_message = $result['message'];
    }
}

// Handle order placement
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['place_order'])) {
    $shipping_address = sanitize_input($_POST['shipping_address'] ?? '');
    $billing_address = sanitize_input($_POST['billing_address'] ?? '');
    $payment_method = sanitize_input($_POST['payment_method'] ?? '');
    $coupon_code = $_SESSION['checkout_coupon'] ?? null;
    
    // For free product coupons, skip payment method requirement
    $applied_coupon = $_SESSION['checkout_coupon'] ?? null;
    if ($applied_coupon && $applied_coupon['type'] === 'free_product') {
        $payment_method = 'free_coupon';
    }
    
    if (empty($shipping_address) || empty($billing_address) || empty($payment_method)) {
        $error = 'Please fill in all required fields';
    } else {
        $result = $order_manager->create_order(
            $_SESSION['user_id'], 
            $cart_items, 
            $shipping_address, 
            $billing_address, 
            $payment_method,
            $coupon_code
        );
        
        if ($result['success']) {
            // Record coupon usage if applicable
            if ($applied_coupon) {
                $coupon_manager->recordCouponUsage($applied_coupon['id'], $_SESSION['user_id'], $result['order_id']);
            }
            
            // Clear checkout session data
            unset($_SESSION['checkout_coupon']);
            unset($_SESSION['checkout_discount']);
            
            redirect('order-confirmation.php?order=' . $result['order_number']);
        } else {
            $error = $result['message'];
        }
    }
}

// Use session discount if available
if (isset($_SESSION['checkout_discount'])) {
    $coupon_discount = $_SESSION['checkout_discount'];
}

// For free product coupons, everything is free
if ($applied_coupon && $applied_coupon['type'] === 'free_product') {
    $tax_amount = 0;
    $final_total = 0;
} else {
    $tax_amount = ($cart_total - $coupon_discount) * 0.08;
    $final_total = $cart_total + $tax_amount - $coupon_discount;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#0f172a',
                        secondary: '#1e293b',
                        accent: '#3b82f6',
                        warning: '#f59e0b',
                        success: '#10b981'
                    }
                }
            }
        }
    </script>
</head>
<body class="bg-gray-50">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-2xl font-bold text-accent"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700">Checkout</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="cart.php" class="text-gray-700 hover:text-accent">Back to Cart</a>
                    <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Checkout</h1>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($coupon_message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo $coupon_message; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Checkout Form -->
            <div class="lg:col-span-2 space-y-8">
                <!-- Shipping Address -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Shipping Address</h2>
                    <textarea name="shipping_address" rows="4" required 
                              placeholder="Enter your full shipping address..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"></textarea>
                </div>
                
                <!-- Billing Address -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Billing Address</h2>
                    <div class="mb-4">
                        <label class="flex items-center">
                            <input type="checkbox" id="same_as_shipping" class="mr-2" onchange="toggleBillingAddress()">
                            <span class="text-sm text-gray-700">Same as shipping address</span>
                        </label>
                    </div>
                    <textarea name="billing_address" id="billing_address" rows="4" required 
                              placeholder="Enter your billing address..."
                              class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"></textarea>
                </div>
                
                <!-- Payment Method -->
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Payment Method</h2>
                    <div class="space-y-3">
                        <label class="flex items-center p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="payment_method" value="credit_card" required class="mr-3">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">💳</span>
                                <span class="font-medium">Credit Card</span>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="payment_method" value="paypal" required class="mr-3">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">🅿️</span>
                                <span class="font-medium">PayPal</span>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-gray-200 rounded-md hover:bg-gray-50 cursor-pointer">
                            <input type="radio" name="payment_method" value="apple_pay" required class="mr-3">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">🍎</span>
                                <span class="font-medium">Apple Pay</span>
                            </div>
                        </label>
                        
                        <label class="flex items-center p-3 border border-green-200 rounded-md hover:bg-green-50 cursor-pointer bg-green-25">
                            <input type="radio" name="payment_method" value="mobile_money_cameroon" required class="mr-3">
                            <div class="flex items-center">
                                <span class="text-2xl mr-2">📱</span>
                                <div>
                                    <span class="font-medium text-green-700">Mobile Money (Cameroon)</span>
                                    <p class="text-sm text-green-600">Send payment to: 679871130</p>
                                </div>
                            </div>
                        </label>
                    </div>
                    
                    <div class="mt-4 p-3 bg-blue-50 border border-blue-200 rounded-md">
                        <p class="text-sm text-blue-800">
                            <strong>Mobile Money Instructions:</strong> For Mobile Money payments, send the total amount to <strong>679871130</strong> and include your order number in the transaction message.
                        </p>
                    </div>
                </div>
            </div>
            
            <!-- Order Summary -->
            <div class="lg:col-span-1">
                <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                    <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                    
                    <!-- Order Items -->
                    <div class="space-y-3 mb-6 max-h-60 overflow-y-auto">
                        <?php foreach ($cart_items as $item): ?>
                            <div class="flex items-center space-x-3">
                                <?php if (!empty($item['images'])): ?>
                                    <img src="<?php echo $item['images'][0]; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                         class="w-12 h-12 object-cover rounded">
                                <?php else: ?>
                                    <div class="w-12 h-12 bg-gray-200 rounded flex items-center justify-center">
                                        <span class="text-gray-400 text-xs">No Image</span>
                                    </div>
                                <?php endif; ?>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900 line-clamp-1"><?php echo htmlspecialchars($item['name']); ?></p>
                                    <p class="text-xs text-gray-500">Qty: <?php echo $item['quantity']; ?></p>
                                </div>
                                <p class="text-sm font-medium"><?php echo format_currency($item['total_price']); ?></p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Coupon Code -->
                    <div class="mb-6 pb-6 border-b border-gray-200">
                        <div class="flex space-x-2">
                            <input type="text" name="coupon_code" placeholder="Coupon code" 
                                   value="<?php echo $_SESSION['checkout_coupon'] ?? ''; ?>"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded-md text-sm focus:outline-none focus:ring-accent focus:border-accent">
                            <button type="submit" name="apply_coupon" 
                                    class="px-4 py-2 bg-gray-500 text-white rounded-md text-sm hover:bg-gray-600 transition duration-200">
                                Apply
                            </button>
                        </div>
                    </div>
                    
                    <!-- Totals -->
                    <div class="space-y-3 mb-6">
                        <div class="flex justify-between">
                            <span class="text-gray-600">Subtotal</span>
                            <span class="font-medium"><?php echo format_currency($cart_total); ?></span>
                        </div>
                        
                        <?php if ($coupon_discount > 0): ?>
                            <div class="flex justify-between text-green-600">
                                <span>Discount</span>
                                <span>-<?php echo format_currency($coupon_discount); ?></span>
                            </div>
                        <?php endif; ?>
                        
                        <div class="flex justify-between">
                            <span class="text-gray-600">Shipping</span>
                            <span class="font-medium text-green-600">Free</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-600">Tax</span>
                            <span class="font-medium"><?php echo format_currency($tax_amount); ?></span>
                        </div>
                        <div class="border-t pt-3">
                            <div class="flex justify-between">
                                <span class="text-lg font-semibold">Total</span>
                                <span class="text-lg font-bold text-accent"><?php echo format_currency($final_total); ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <button type="submit" name="place_order" 
                            class="w-full bg-accent text-white py-3 px-4 rounded-md font-medium hover:bg-blue-600 transition duration-200">
                        Place Order
                    </button>
                    
                    <div class="mt-4 text-center">
                        <p class="text-xs text-gray-500">
                            By placing your order, you agree to our Terms of Service and Privacy Policy.
                        </p>
                    </div>
                </div>
            </div>
        </form>
    </div>
    
    <script>
        function toggleBillingAddress() {
            const checkbox = document.getElementById('same_as_shipping');
            const billingAddress = document.getElementById('billing_address');
            const shippingAddress = document.querySelector('textarea[name="shipping_address"]');
            
            if (checkbox.checked) {
                billingAddress.value = shippingAddress.value;
                billingAddress.disabled = true;
                billingAddress.classList.add('bg-gray-100');
            } else {
                billingAddress.disabled = false;
                billingAddress.classList.remove('bg-gray-100');
            }
        }
        
        // Auto-copy shipping to billing when shipping changes
        document.querySelector('textarea[name="shipping_address"]').addEventListener('input', function() {
            const checkbox = document.getElementById('same_as_shipping');
            if (checkbox.checked) {
                document.getElementById('billing_address').value = this.value;
            }
        });
    </script>
</body>
</html>
