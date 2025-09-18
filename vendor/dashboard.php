<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/product.php';
require_once '../includes/order.php';
require_once '../includes/vendor.php';

// Use unified authorization system
require_vendor();
$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();

$productManager = new ProductManager();
$orderManager = new OrderManager();

// Get vendor ID from vendors table
$vendorManager = new VendorManager();
$vendor_info = $vendorManager->get_vendor_by_user_id($user['id']);
$vendor_id = $vendor_info ? $vendor_info['id'] : null;

// Get vendor statistics using vendor ID
if ($vendor_id) {
    $totalProducts     = $productManager->getVendorProductCount($vendor_id);
    $totalOrders       = $orderManager->getVendorOrderCount($vendor_id);
    $totalEarnings     = $orderManager->getVendorEarnings($vendor_id);
    $pendingOrdersList = $orderManager->getVendorPendingOrders($vendor_id);
    $pendingOrders     = count($pendingOrdersList);
    $recentOrders      = $orderManager->getVendorOrders($vendor_id, 5);
    $topProducts       = $productManager->getVendorTopProducts($vendor_id, 5);

    // === Get notifications for the current user from notifications table ===
    $stmt = $db->prepare("
        SELECT id, user_id, type, title, message, is_read, created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$user['id']]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Count unread notifications
    $stmt = $db->prepare("
        SELECT COUNT(*) AS unread_count
        FROM notifications
        WHERE user_id = ? AND (is_read = 0 OR is_read IS NULL)
    ");
    $stmt->execute([$user['id']]);
    $unreadCountRow = $stmt->fetch(PDO::FETCH_ASSOC);
    $unreadCount    = (int)($unreadCountRow['unread_count'] ?? 0);

} else {
    // No vendor record found - set all to zero
    $totalProducts = 0;
    $totalOrders   = 0;
    $totalEarnings = 0;
    $pendingOrders = 0;
    $recentOrders  = [];
    $topProducts   = [];
    $notifications = [];
    $unreadCount   = 0;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Dashboard - E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="text-2xl font-bold text-accent">E-Commerce</a>
                    <span class="ml-4 px-3 py-1 bg-accent/10 text-accent text-sm font-medium rounded-full">Vendor</span>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</span>
                    <a href="../logout.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Vendor Dashboard</h1>
            <p class="text-gray-600 mt-2">Manage your products, orders, and business analytics</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-accent/10 rounded-lg">
                        <svg class="w-6 h-6 text-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Products</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $totalProducts; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $totalOrders; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Earnings</p>
                        <p class="text-2xl font-semibold text-gray-900">$<?php echo number_format($totalEarnings, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $pendingOrders; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Orders -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                        <a href="#" class="text-primary hover:underline text-sm">View All</a>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentOrders)): ?>
                            <p class="text-gray-500 text-center py-8">No orders yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-gray-900">Order #<?php echo $order['order_number']; ?></p>
                                                <p class="text-sm text-gray-600">Customer: <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                                <p class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                            </div>
                                            <div class="text-right">
                                                <p class="font-semibold text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                    <?php echo $order['status'] === 'delivered' ? 'bg-green-100 text-green-800' : 
                                                        ($order['status'] === 'shipped' ? 'bg-blue-100 text-blue-800' : 
                                                        ($order['status'] === 'processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')); ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Products -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Top Selling Products</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($topProducts)): ?>
                            <p class="text-gray-500 text-center py-8">No sales data yet.</p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($topProducts as $product): ?>
                                    <div class="flex items-center space-x-4">
                                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: '/placeholder.svg?height=50&width=50'); ?>" 
                                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                             class="w-12 h-12 object-cover rounded-lg">
                                        <div class="flex-1">
                                            <p class="font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></p>
                                            <p class="text-sm text-gray-600"><?php echo $product['sales_count']; ?> sold</p>
                                        </div>
                                        <p class="font-semibold text-gray-900">$<?php echo number_format($product['price'], 2); ?></p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions & Analytics -->
            <div class="space-y-6">
                <!-- Notifications -->
                <div class="p-6">
                    <?php if (empty($notifications)): ?>
                        <p class="text-gray-500 text-center py-4">No notifications yet.</p>
                    <?php else: ?>
                        <div class="space-y-3">
                            <?php foreach ($notifications as $notification):
                                // Normalize fields and types
                                $isRead = isset($notification['is_read']) ? ((int)$notification['is_read'] === 1) : false;
                                $rowClass = !$isRead ? 'bg-blue-50 border-blue-200' : '';
                                $title = $notification['title'] ?? 'No title';
                                $message = $notification['message'] ?? '';
                                $createdAt = !empty($notification['created_at']) ? date('M j, Y g:i A', strtotime($notification['created_at'])) : '';
                            ?>
                                <div class="border border-gray-200 rounded-lg p-3 <?= $rowClass ?>">
                                    <div class="flex justify-between items-start">
                                        <div class="flex-1">
                                            <h4 class="text-sm font-medium text-gray-900"><?= htmlspecialchars($title) ?></h4>
                                            <p class="text-sm text-gray-600 mt-1"><?= nl2br(htmlspecialchars($message)) ?></p>
                                            <?php if ($createdAt): ?>
                                                <p class="text-xs text-gray-500 mt-2"><?= $createdAt ?></p>
                                            <?php endif; ?>
                                        </div>

                                        <?php if (!$isRead): ?>
                                            <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                New
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if (count($notifications) >= 5): ?>
                            <div class="text-center mt-4">
                                <a href="notifications.php" class="text-primary hover:text-blue-600 text-sm">View All Notifications</a>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>


                <!-- Quick Actions -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <a href="add-product.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <span class="text-gray-700">Add New Product</span>
                        </a>
                        
                        <a href="products.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            <span class="text-gray-700">Manage Products</span>
                        </a>
                        
                        <a href="#" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span class="text-gray-700">View Orders</span>
                        </a>
                        
                        <a href="analytics.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                            <span class="text-gray-700">View Analytics</span>
                        </a>
                    </div>
                </div>

                <!-- Earnings Chart -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Monthly Earnings</h2>
                    </div>
                    <div class="p-6">
                        <canvas id="earningsChart" width="400" height="200"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Sample earnings chart
        const ctx = document.getElementById('earningsChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
                datasets: [{
                    label: 'Earnings ($)',
                    data: [1200, 1900, 3000, 2500, 2200, 3000],
                    borderColor: '#f97316',
                    backgroundColor: 'rgba(249, 115, 22, 0.1)',
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    </script>
</body>
</html>
