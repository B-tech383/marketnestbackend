<?php
require_once './config/config.php';
require_once './includes/order.php';

require_login();

$order_manager = new OrderManager();
$page = max(1, $_GET['page'] ?? 1);
$limit = 10;
$offset = ($page - 1) * $limit;

$orders = $order_manager->get_user_orders($_SESSION['user_id'], $limit, $offset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order History - <?php echo SITE_NAME; ?></title>
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
                <a href="dashboard.php" class="text-2xl font-bold text-accent"><?php echo SITE_NAME; ?></a>
                <span class="text-gray-400">|</span>
                <span class="text-gray-700">Order History</span>
            </div>
            
            <nav class="flex items-center space-x-4">
                <a href="products.php" class="text-gray-700 hover:text-accent">Shop</a>
                <a href="cart.php" class="text-gray-700 hover:text-accent">Cart</a>
                <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                <a href="logout.php" class="text-accent hover:text-blue-600">Logout</a>
            </nav>
        </div>
    </div>
</header>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
<h1 class="text-3xl font-bold text-gray-900 mb-8">Order History</h1>

<?php if (empty($orders)): ?>
    <div class="text-center py-12">
        <div class="text-gray-400 text-6xl mb-4">📋</div>
        <h3 class="text-xl font-medium text-gray-900 mb-2">No orders yet</h3>
        <p class="text-gray-500 mb-6">Start shopping to see your orders here!</p>
        <a href="products.php" class="bg-accent text-white px-6 py-3 rounded-md font-medium hover:bg-blue-600 transition duration-200">
            Start Shopping
        </a>
    </div>
<?php else: ?>
    <div class="space-y-6">
        <?php foreach ($orders as $order): ?>
            <div class="bg-white rounded-lg shadow-sm border">
                <!-- Order Header -->
                <div class="px-6 py-4 border-b border-gray-200 flex flex-col sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Order #<?php echo $order['order_number']; ?></h3>
                        <p class="text-sm text-gray-600">Placed on <?php echo date('M j, Y g:i A', strtotime($order['created_at'])); ?></p>
                        <p class="text-sm text-gray-600">Delivery to: <?php echo htmlspecialchars($order['delivery_address']); ?></p>
                        <p class="text-sm text-gray-600">Items: <?php echo count($order['items']); ?></p>
                        <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                    </div>

                    <div class="mt-4 sm:mt-0 text-right">
                        <p class="text-lg font-semibold text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></p>
                        <?php
                        $status_colors = [
                            'pending' => 'bg-yellow-100 text-yellow-800',
                            'processing' => 'bg-blue-100 text-blue-800',
                            'shipped' => 'bg-purple-100 text-purple-800',
                            'delivered' => 'bg-green-100 text-green-800',
                            'cancelled' => 'bg-red-100 text-red-800'
                        ];
                        ?>
                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_colors[$order['status']] ?? 'bg-gray-100 text-gray-800'; ?>">
                            <?php echo ucfirst($order['status']); ?>
                        </span>
                        <?php if (!empty($order['tracking_number'])): ?>
                            <p class="text-sm text-gray-600 mt-1">Tracking: <?php echo $order['tracking_number']; ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Product Thumbnails -->
                <div class="px-6 py-4">
                    <div class="flex space-x-2 overflow-x-auto">
                        <?php foreach ($order['items'] as $item): ?>
                        <?php
                            $images = is_array($item['product_images']) ? $item['product_images'] : json_decode($item['product_images'], true);
                            $img = $images[0] ?? '/placeholder.svg?height=50&width=50';
                        ?>
                        <div class="w-16 h-16 flex-shrink-0">
                            <img src="<?php echo htmlspecialchars($img); ?>" 
                                alt="<?php echo htmlspecialchars($item['product_name']); ?>" 
                                class="w-16 h-16 object-cover rounded-md border border-gray-200">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Actions -->
                <div class="px-6 py-4 flex space-x-3">
                    <a href="order-detail.php?id=<?php echo $order['id']; ?>" class="text-accent hover:text-blue-600 text-sm font-medium">View Details</a>
                    <?php if (!empty($order['tracking_number'])): ?>
                        <a href="track.php?tracking=<?php echo $order['tracking_number']; ?>" class="text-accent hover:text-blue-600 text-sm font-medium">Track Package</a>
                    <?php endif; ?>
                    <?php if ($order['status'] === 'delivered'): ?>
                        <a href="review-order.php?id=<?php echo $order['id']; ?>" class="text-accent hover:text-blue-600 text-sm font-medium">Write Review</a>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Pagination -->
    <div class="mt-8 flex justify-center">
        <div class="flex space-x-2">
            <?php if ($page > 1): ?>
                <a href="?page=<?php echo $page - 1; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Previous</a>
            <?php endif; ?>
            <?php if (count($orders) == $limit): ?>
                <a href="?page=<?php echo $page + 1; ?>" class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Next</a>
            <?php endif; ?>
        </div>
    </div>
<?php endif; ?>
</div>
</body>
</html>
