<?php
require_once './config/config.php';
require_once './includes/order.php';

require_login();

$order_number = $_GET['order'] ?? null;
if (!$order_number) {
    redirect('index.php');
}

$order_manager = new OrderManager();

// Get order by order number
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT id FROM orders WHERE order_number = ? AND user_id = ?");
$stmt->execute([$order_number, $_SESSION['user_id']]);
$order_data = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order_data) {
    redirect('index.php');
}

$order = $order_manager->get_order_by_id($order_data['id'], $_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Confirmation - <?php echo SITE_NAME; ?></title>
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
                    <span class="text-gray-700">Order Confirmation</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="order-history.php" class="text-gray-700 hover:text-accent">Order History</a>
                    <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Success Message -->
        <div class="text-center mb-8">
            <div class="w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
                <span class="text-green-600 text-2xl">✓</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Order Confirmed!</h1>
            <p class="text-gray-600">Thank you for your purchase. Your order has been placed successfully.</p>
        </div>
        
        <!-- Order Details -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Order Number</h3>
                    <p class="text-lg font-semibold text-gray-900"><?php echo $order['order_number']; ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Order Date</h3>
                    <p class="text-lg font-semibold text-gray-900"><?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                </div>
                <div>
                    <h3 class="text-sm font-medium text-gray-500 mb-1">Total Amount</h3>
                    <p class="text-lg font-semibold text-accent">$<?php echo number_format($order['total_amount'], 2); ?></p>
                </div>
            </div>
            
            <?php if ($order['tracking_number']): ?>
                <div class="bg-blue-50 border border-blue-200 rounded-md p-4">
                    <div class="flex items-center">
                        <span class="text-accent text-xl mr-3">📦</span>
                        <div>
                            <h4 class="font-medium text-blue-800">Tracking Information</h4>
                            <p class="text-sm text-blue-700">
                                Tracking Number: <strong><?php echo $order['tracking_number']; ?></strong>
                            </p>
                            <a href="track.php?tracking=<?php echo $order['tracking_number']; ?>" 
                               class="text-sm text-accent hover:text-blue-800 font-medium">
                                Track your shipment →
                            </a>
                        </div>
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
                            <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-sm text-gray-600">by <?php echo htmlspecialchars($item['business_name']); ?></p>
                            <p class="text-sm text-gray-600">Quantity: <?php echo $item['quantity']; ?></p>
                        </div>
                        
                        <div class="text-right">
                            <p class="font-medium text-gray-900">$<?php echo number_format($item['total'], 2); ?></p>
                            <p class="text-sm text-gray-600">$<?php echo number_format($item['price'], 2); ?> each</p>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Order Summary -->
        <div class="bg-white rounded-lg shadow-sm p-6 mb-8">
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
                        <span class="text-accent">$<?php echo number_format($order['total_amount'], 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Addresses -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Shipping Address</h3>
                <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($order['shipping_address']); ?></p>
            </div>
            
            <div class="bg-white rounded-lg shadow-sm p-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Billing Address</h3>
                <p class="text-gray-700 whitespace-pre-line"><?php echo htmlspecialchars($order['billing_address']); ?></p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="text-center space-x-4">
            <a href="products.php" class="bg-accent text-white px-6 py-3 rounded-md font-medium hover:bg-blue-600 transition duration-200">
                Continue Shopping
            </a>
            <a href="order-history.php" class="border border-gray-300 text-gray-700 px-6 py-3 rounded-md font-medium hover:bg-gray-50 transition duration-200">
                View Order History
            </a>
        </div>
    </div>
</body>
</html>
