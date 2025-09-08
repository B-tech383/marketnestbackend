<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/order.php';
require_once '../includes/product.php';

// Check if user is logged in
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = getCurrentUser();
if ($user['role'] !== 'customer') {
    header('Location: ../index.php');
    exit();
}

$orderManager = new OrderManager();
$productManager = new ProductManager();

// Get user statistics
$recentOrders = $orderManager->getUserOrders($user['id'], 5);
$totalOrders = $orderManager->getUserOrderCount($user['id']);
$totalSpent = $orderManager->getUserTotalSpent($user['id']);
$wishlistCount = $productManager->getWishlistCount($user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - E-Commerce</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#f97316',
                        secondary: '#fb923c'
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
                    <a href="../index.php" class="text-2xl font-bold text-primary">E-Commerce</a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo htmlspecialchars($user['first_name']); ?>!</span>
                    <a href="../logout.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Dashboard Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">My Dashboard</h1>
            <p class="text-gray-600 mt-2">Manage your orders, profile, and preferences</p>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                        <p class="text-sm font-medium text-gray-600">Total Spent</p>
                        <p class="text-2xl font-semibold text-gray-900">$<?php echo number_format($totalSpent, 2); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Wishlist Items</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $wishlistCount; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Addresses</p>
                        <p class="text-2xl font-semibold text-gray-900">2</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Recent Orders -->
            <div class="lg:col-span-2">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    </div>
                    <div class="p-6">
                        <?php if (empty($recentOrders)): ?>
                            <p class="text-gray-500 text-center py-8">No orders yet. <a href="../products.php" class="text-primary hover:underline">Start shopping!</a></p>
                        <?php else: ?>
                            <div class="space-y-4">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="border border-gray-200 rounded-lg p-4">
                                        <div class="flex justify-between items-start">
                                            <div>
                                                <p class="font-semibold text-gray-900">Order #<?php echo $order['order_number']; ?></p>
                                                <p class="text-sm text-gray-600"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                                <p class="text-sm text-gray-600"><?php echo $order['item_count']; ?> items</p>
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
                                        <div class="mt-3 flex space-x-3">
                                            <a href="../order-detail.php?id=<?php echo $order['id']; ?>" class="text-primary hover:underline text-sm">View Details</a>
                                            <?php if ($order['tracking_number']): ?>
                                                <a href="../track.php?tracking=<?php echo $order['tracking_number']; ?>" class="text-primary hover:underline text-sm">Track Package</a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="mt-6 text-center">
                                <a href="../order-history.php" class="text-primary hover:underline">View All Orders</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Quick Actions -->
            <div class="space-y-6">
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <a href="../products.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                            <span class="text-gray-700">Browse Products</span>
                        </a>
                        
                        <a href="../cart.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v5a2 2 0 01-2 2H9a2 2 0 01-2-2v-5m6-5V6a2 2 0 00-2-2H9a2 2 0 00-2 2v3"></path>
                            </svg>
                            <span class="text-gray-700">View Cart</span>
                        </a>
                        
                        <a href="wishlist.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                            </svg>
                            <span class="text-gray-700">My Wishlist</span>
                        </a>
                        
                        <a href="profile.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            <span class="text-gray-700">Edit Profile</span>
                        </a>
                        
                        <a href="../track.php" class="flex items-center p-3 border border-gray-200 rounded-lg hover:bg-gray-50 transition">
                            <svg class="w-5 h-5 text-primary mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            <span class="text-gray-700">Track Shipment</span>
                        </a>
                    </div>
                </div>

                <!-- Account Info -->
                <div class="bg-white rounded-lg shadow">
                    <div class="px-6 py-4 border-b border-gray-200">
                        <h2 class="text-lg font-semibold text-gray-900">Account Info</h2>
                    </div>
                    <div class="p-6">
                        <div class="space-y-3">
                            <div>
                                <p class="text-sm text-gray-600">Name</p>
                                <p class="font-medium"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Email</p>
                                <p class="font-medium"><?php echo htmlspecialchars($user['email']); ?></p>
                            </div>
                            <div>
                                <p class="text-sm text-gray-600">Member Since</p>
                                <p class="font-medium"><?php echo date('M Y', strtotime($user['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
