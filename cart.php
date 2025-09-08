<?php
require_once './config/config.php';
require_once './includes/cart.php';

require_login();

$cart_manager = new CartManager();
$message = '';

// Handle cart updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_quantity'])) {
        $product_id = $_POST['product_id'];
        $quantity = max(0, $_POST['quantity']);
        
        $result = $cart_manager->update_cart_quantity($_SESSION['user_id'], $product_id, $quantity);
        $message = $result['message'];
    } elseif (isset($_POST['remove_item'])) {
        $product_id = $_POST['product_id'];
        
        $result = $cart_manager->remove_from_cart($_SESSION['user_id'], $product_id);
        $message = $result['message'];
    }
}

$cart_items = $cart_manager->get_cart_items($_SESSION['user_id']);
$cart_total = $cart_manager->get_cart_total($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shopping Cart - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'orange': {
                            500: '#f97316'
                        }
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
                    <a href="index.php" class="text-2xl font-bold text-orange-500"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700">Shopping Cart</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="products.php" class="text-gray-700 hover:text-orange-500">Continue Shopping</a>
                    <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="logout.php" class="text-orange-500 hover:text-orange-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Shopping Cart</h1>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cart_items)): ?>
            <div class="text-center py-12">
                <div class="text-gray-400 text-6xl mb-4">🛒</div>
                <h3 class="text-xl font-medium text-gray-900 mb-2">Your cart is empty</h3>
                <p class="text-gray-500 mb-6">Add some products to get started!</p>
                <a href="products.php" class="bg-orange-500 text-white px-6 py-3 rounded-md font-medium hover:bg-orange-600 transition duration-200">
                    Start Shopping
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                <!-- Cart Items -->
                <div class="lg:col-span-2">
                    <div class="bg-white rounded-lg shadow-sm">
                        <div class="px-6 py-4 border-b border-gray-200">
                            <h2 class="text-lg font-semibold text-gray-900">Cart Items (<?php echo count($cart_items); ?>)</h2>
                        </div>
                        
                        <div class="divide-y divide-gray-200">
                            <?php foreach ($cart_items as $item): ?>
                                <div class="p-6">
                                    <div class="flex items-start space-x-4">
                                        <div class="flex-shrink-0">
                                            <?php if (!empty($item['images'])): ?>
                                                <img src="<?php echo $item['images'][0]; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                     class="w-20 h-20 object-cover rounded-md">
                                            <?php else: ?>
                                                <div class="w-20 h-20 bg-gray-200 rounded-md flex items-center justify-center">
                                                    <span class="text-gray-400 text-xs">No Image</span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex-1">
                                            <div class="flex justify-between">
                                                <div>
                                                    <h3 class="text-lg font-medium text-gray-900">
                                                        <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="hover:text-orange-500">
                                                            <?php echo htmlspecialchars($item['name']); ?>
                                                        </a>
                                                    </h3>
                                                    <p class="text-sm text-gray-600">by <?php echo htmlspecialchars($item['business_name']); ?></p>
                                                    <p class="text-lg font-semibold text-gray-900 mt-2">$<?php echo number_format($item['current_price'], 2); ?></p>
                                                </div>
                                                
                                                <div class="text-right">
                                                    <p class="text-lg font-bold text-gray-900">$<?php echo number_format($item['total_price'], 2); ?></p>
                                                </div>
                                            </div>
                                            
                                            <div class="flex items-center justify-between mt-4">
                                                <form method="POST" class="flex items-center space-x-2">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <label for="quantity_<?php echo $item['product_id']; ?>" class="text-sm text-gray-700">Qty:</label>
                                                    <select name="quantity" id="quantity_<?php echo $item['product_id']; ?>" 
                                                            class="border border-gray-300 rounded-md px-2 py-1 text-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500"
                                                            onchange="this.form.submit()">
                                                        <?php for ($i = 1; $i <= min(10, $item['stock_quantity']); $i++): ?>
                                                            <option value="<?php echo $i; ?>" <?php echo $i == $item['quantity'] ? 'selected' : ''; ?>>
                                                                <?php echo $i; ?>
                                                            </option>
                                                        <?php endfor; ?>
                                                    </select>
                                                    <input type="hidden" name="update_quantity" value="1">
                                                </form>
                                                
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="product_id" value="<?php echo $item['product_id']; ?>">
                                                    <button type="submit" name="remove_item" 
                                                            class="text-red-600 hover:text-red-800 text-sm font-medium"
                                                            onclick="return confirm('Remove this item from cart?')">
                                                        Remove
                                                    </button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Order Summary -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-lg shadow-sm p-6 sticky top-8">
                        <h2 class="text-lg font-semibold text-gray-900 mb-4">Order Summary</h2>
                        
                        <div class="space-y-3 mb-6">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Subtotal</span>
                                <span class="font-medium">$<?php echo number_format($cart_total, 2); ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Shipping</span>
                                <span class="font-medium">Free</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Tax</span>
                                <span class="font-medium">$<?php echo number_format($cart_total * 0.08, 2); ?></span>
                            </div>
                            <div class="border-t pt-3">
                                <div class="flex justify-between">
                                    <span class="text-lg font-semibold">Total</span>
                                    <span class="text-lg font-bold text-orange-600">$<?php echo number_format($cart_total * 1.08, 2); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <a href="checkout.php" class="w-full bg-orange-500 text-white py-3 px-4 rounded-md font-medium hover:bg-orange-600 transition duration-200 block text-center">
                            Proceed to Checkout
                        </a>
                        
                        <a href="products.php" class="w-full mt-3 border border-gray-300 text-gray-700 py-3 px-4 rounded-md font-medium hover:bg-gray-50 transition duration-200 block text-center">
                            Continue Shopping
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
