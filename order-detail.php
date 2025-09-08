<?php
require_once './config/config.php';
require_once './includes/order.php';

require_login();

$order_id = $_GET['id'] ?? null;
if (!$order_id) {
    redirect('order-history.php');
}

$order_manager = new OrderManager();
$order = $order_manager->get_order_by_id($order_id, $_SESSION['user_id']);

if (!$order) {
    redirect('order-history.php');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - <?php echo SITE_NAME; ?></title>
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
                    <a href="order-history.php" class="text-gray-700 hover:text-orange-500">Order History</a>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="logout.php" class="text-orange-500 hover:text-orange-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-6">
            <a href="order-history.php" class="text-orange-500 hover:text-orange-600 text-sm font-medium">
                ← Back to Order History
            </a>
        </div>
        
        <h1 class="text-3xl font-bold text-gray-900 mb-8">Order Details</h1>
        
        <!-- Order Info -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Order Number</h3>
                    <p class="text-lg font-semibold text-gray-900"><?php echo $order['order_number']; ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Order Date</h3>
                    <p class="text-lg font-semibold text-gray-900"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Status</h3>
                    <?php
                    $status_colors = [
                        'pending' => 'bg-yellow-100 text-yellow-800',
                        'processing' => 'bg-blue-100 text-blue-800',
                        'shipped' => 'bg-purple-100 text-purple-800',
                        'delivered' => 'bg-green-100 text-green-800',
                        'cancelled' => 'bg-red-100 text-red-800'
                    ];
                    ?>
                    <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full <?php echo $status_colors[$order['status']]; ?>">
                        <?php echo ucfirst($order['status']); ?>
                    </span>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total</h3>
                    <p class="text-lg font-semibold text-orange-600">$<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
            </div>
            
            <?php if ($order['tracking_number']): ?>
                <div class="mt-6 bg-orange-50 border border-orange-200 rounded-md p-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center">
                            <span class="text-orange-600 text-xl mr-3">📦</span>
                            <div>
                                <h4 class="font-medium text-orange-800">Tracking Number</h4>
                                <p class="text-sm text-orange-700"><?php echo $order['tracking_number']; ?></p>
                            </div>
                        </div>
                        <a href="track.php?tracking=<?php echo $order['tracking_number']; ?>" 
                           class="bg-orange-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-orange-600 transition duration-200">
                            Track Package
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Order Items -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Items</h2>
            <div class="divide-y divide-gray-200">
                <?php foreach ($order['items'] as $item): ?>
                    <div class="py-4 flex items-center space-x-4">
                        <div class="flex-shrink-0">
                            <?php if (!empty($item['images'])): ?>
                                <img src="<?php echo $item['images'][0]; ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                     class="w-16 h-16 object-cover rounded-md">
                            <?php else: ?>
                                <div class="w-16 h-16 bg-gray-200 rounded-md flex items-center justify-center">
                                    <span class="text-gray-400 text-xs">No Image</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="flex-1">
                            <h3 class="font-medium text-gray-900">
                                <a href="product-detail.php?id=<?php echo $item['product_id']; ?>" class="hover:text-orange-500">
                                    <?php echo htmlspecialchars($item['name']); ?>
                                </a>
                            </h3>
                            <p class="text-sm text-gray-600">by <?php echo htmlspecialchars($item['business_name']); ?></p>
                            <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></p>
                        </div>
                        
                        <div class="text-right">
                            <p class="font-medium text-gray-900">$<?php echo number_format($item['total'], 2); ?></p>
                            <?php if ($order['status'] === 'delivered'): ?>
                                <a href="add-review.php?product=<?php echo $item['product_id']; ?>&order=<?php echo $order['id']; ?>" 
                                   class="text-sm text-orange-500 hover:text-orange-600 font-medium">
                                    Write Review
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Order Summary & Addresses -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Order Summary -->
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Order Summary</h2>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Subtotal</span>
                        <span>$<?php echo number_format($order['total_amount'] - $order['tax_amount'] - $order['shipping_amount'] + $order['discount_amount'], 2); ?></span>
                    </div>
                    
                    <?php if ($order['discount_amount'] > 0): ?>
                        <div class="flex justify-between text-green-600">
                            <span>Discount</span>
                            <span>-$<?php echo number_format($order['discount_amount'], 2); ?></span>
                        </div>
                    <?php endif; ?>
                    
                    <div class="flex justify-between">
                        <span class="text-gray-600">Shipping</span>
                        <span><?php echo $order['shipping_amount'] > 0 ? '$' . number_format($order['shipping_amount'], 2) : 'Free'; ?></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Tax</span>
                        <span>$<?php echo number_format($order['tax_amount'], 2); ?></span>
                    </div>
                    <div class="border-t pt-2">
                        <div class="flex justify-between font-semibold text-lg">
                            <span>Total</span>
                            <span class="text-orange-600">$<?php echo number_format($order['total_amount'], 2); ?></span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Addresses -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Shipping Address</h3>
                    <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
                </div>
                
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-3">Billing Address</h3>
                    <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($order['billing_address']); ?></p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
