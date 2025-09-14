<?php
require_once '../config/config.php';
require_once '../includes/vendor.php';
require_once '../includes/order.php';

require_admin();

$vendor_manager = new VendorManager();
$orderManager = new OrderManager();
$pending_applications = $vendor_manager->get_pending_applications();
$all_vendors = $vendor_manager->get_all_vendors();

// Get comprehensive stats
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users u JOIN user_roles ur ON u.id = ur.user_id WHERE ur.role = 'customer'");
$stmt->execute();
$total_customers = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $db->prepare("SELECT COUNT(*) as total_users FROM users");
$stmt->execute();
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total_users'];

$stmt = $db->prepare("SELECT COUNT(*) as total_products FROM products WHERE status = 'active'");
$stmt->execute();
$total_products = $stmt->fetch(PDO::FETCH_ASSOC)['total_products'];

$stmt = $db->prepare("SELECT COUNT(*) as total_orders FROM orders");
$stmt->execute();
$total_orders = $stmt->fetch(PDO::FETCH_ASSOC)['total_orders'];

$stmt = $db->prepare("SELECT SUM(total_amount) as total_revenue FROM orders WHERE status != 'cancelled'");
$stmt->execute();
$total_revenue = $stmt->fetch(PDO::FETCH_ASSOC)['total_revenue'] ?: 0;

$stmt = $db->prepare("SELECT COUNT(*) as pending_orders FROM orders WHERE status = 'pending' OR payment_status = 'pending'");
$stmt->execute();
$pending_orders = $stmt->fetch(PDO::FETCH_ASSOC)['pending_orders'];

// Recent activity
$stmt = $db->prepare("
    SELECT u.first_name, u.last_name, u.email, u.created_at, 'user' as type
    FROM users u 
    JOIN user_roles ur ON u.id = ur.user_id
    WHERE ur.role = 'customer' 
    ORDER BY u.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("
    SELECT o.order_number, o.total_amount, o.created_at, u.first_name, u.last_name, 'order' as type
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    ORDER BY o.created_at DESC 
    LIMIT 5
");
$stmt->execute();
$recent_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
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
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-2xl font-bold text-primary"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700 font-medium">Admin Dashboard</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="../logout.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Admin Dashboard</h1>
            <p class="mt-2 text-gray-600">Comprehensive platform management and analytics</p>
        </div>
        
        <!-- Enhanced stats grid with revenue and pending orders -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-6 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.25"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Users</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_users); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-primary/10 rounded-lg">
                        <svg class="w-6 h-6 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Vendors</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format(count($all_vendors)); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Products</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_products); ?></p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Orders</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($total_orders); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-emerald-100 rounded-lg">
                        <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Revenue</p>
                        <p class="text-2xl font-semibold text-gray-900">$<?php echo number_format($total_revenue, 2); ?></p>
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
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($pending_orders); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Enhanced layout with analytics and recent activity -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
            <!-- Pending Applications -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">
                        Pending Applications
                        <?php if (count($pending_applications) > 0): ?>
                            <span class="bg-red-100 text-red-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                                <?php echo count($pending_applications); ?>
                            </span>
                        <?php endif; ?>
                    </h2>
                    <a href="vendor-applications.php" class="text-primary hover:text-blue-600 text-sm font-medium">View All</a>
                </div>
                
                <div class="p-6">
                    <?php if (empty($pending_applications)): ?>
                        <p class="text-gray-500 text-center py-4">No pending applications</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($pending_applications, 0, 3) as $app): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <?php if (isset($app['logo_path']) && $app['logo_path']): ?>
                                            <img src="../<?php echo $app['logo_path']; ?>" alt="Logo" class="w-8 h-8 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                                <span class="text-primary text-xs font-medium"><?php echo substr($app['business_name'], 0, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($app['business_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($app['name']); ?></p>
                                        </div>
                                    </div>
                                    <span class="text-xs text-gray-500"><?php echo date('M j', strtotime($app['applied_at'])); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Recent Users -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Users</h2>
                    <a href="users.php" class="text-primary hover:text-blue-600 text-sm font-medium">View All</a>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recent_users as $user): ?>
                            <div class="flex items-center space-x-3">
                                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 text-xs font-medium"><?php echo substr($user['first_name'], 0, 1); ?></span>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                                <span class="text-xs text-gray-500"><?php echo date('M j', strtotime($user['created_at'])); ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Recent Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Recent Orders</h2>
                    <a href="orders.php" class="text-primary hover:text-blue-600 text-sm font-medium">View All</a>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <?php foreach ($recent_orders as $order): ?>
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-medium text-gray-900">#<?php echo $order['order_number']; ?></p>
                                    <p class="text-xs text-gray-500"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                </div>
                                <div class="text-right">
                                    <p class="text-sm font-medium text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                    <p class="text-xs text-gray-500"><?php echo date('M j', strtotime($order['created_at'])); ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Vendor Management Section -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- All Vendors -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">All Vendors</h2>
                    <a href="vendor-management.php" class="text-primary hover:text-blue-600 text-sm font-medium">Manage All</a>
                </div>
                <div class="p-6">
                    <?php if (empty($all_vendors)): ?>
                        <p class="text-gray-500 text-center py-4">No vendors registered</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($all_vendors, 0, 5) as $vendor): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div class="flex items-center space-x-3">
                                        <?php if (isset($vendor['logo_path']) && $vendor['logo_path']): ?>
                                            <img src="../<?php echo $vendor['logo_path']; ?>" alt="Logo" class="w-8 h-8 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-primary/10 rounded-full flex items-center justify-center">
                                                <span class="text-primary text-xs font-medium"><?php echo substr($vendor['business_name'], 0, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <p class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['business_name']); ?></p>
                                            <p class="text-xs text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></p>
                                        </div>
                                    </div>
                                    <div class="flex items-center space-x-2">
                                        <?php if ($vendor['is_verified']): ?>
                                            <span class="text-blue-500" title="Verified Vendor">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                                </svg>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Pending</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Pending Orders -->
            <div class="bg-white rounded-lg shadow">
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <h2 class="text-lg font-semibold text-gray-900">Pending Orders</h2>
                    <a href="orders.php" class="text-primary hover:text-blue-600 text-sm font-medium">View All</a>
                </div>
                <div class="p-6">
                    <?php
                    // Get pending orders
                    $stmt = $db->prepare("
                        SELECT o.*, u.first_name, u.last_name, u.email
                        FROM orders o 
                        JOIN users u ON o.user_id = u.id 
                        WHERE o.status = 'pending' OR o.payment_status = 'pending'
                        ORDER BY o.created_at DESC 
                        LIMIT 5
                    ");
                    $stmt->execute();
                    $pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    ?>
                    
                    <?php if (empty($pending_orders)): ?>
                        <p class="text-gray-500 text-center py-4">No pending orders</p>
                    <?php else: ?>
                        <div class="space-y-4">
                            <?php foreach ($pending_orders as $order): ?>
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                                    <div>
                                        <p class="text-sm font-medium text-gray-900">#<?php echo $order['order_number']; ?></p>
                                        <p class="text-xs text-gray-500"><?php echo htmlspecialchars($order['first_name'] . ' ' . $order['last_name']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('M j, Y', strtotime($order['created_at'])); ?></p>
                                    </div>
                                    <div class="text-right">
                                        <p class="text-sm font-medium text-gray-900">$<?php echo number_format($order['total_amount'], 2); ?></p>
                                        <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">Pending</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
            
        <!-- Enhanced quick actions grid with more management options -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Management Tools</h2>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <a href="vendor-applications.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-primary/10 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Review Applications</p>
                            <p class="text-xs text-gray-500">Approve vendor applications</p>
                        </div>
                    </a>
                    
                    <a href="vendor-management.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-blue-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Vendors</p>
                            <p class="text-xs text-gray-500">Vendor accounts & badges</p>
                        </div>
                    </a>
                    
                    <a href="users.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-green-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-2.25"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Users</p>
                            <p class="text-xs text-gray-500">User accounts & roles</p>
                        </div>
                    </a>
                    
                    <a href="products.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Products</p>
                            <p class="text-xs text-gray-500">Product listings & reviews</p>
                        </div>
                    </a>

                    <a href="orders.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-yellow-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Orders</p>
                            <p class="text-xs text-gray-500">Order processing & status</p>
                        </div>
                    </a>
                    
                    <a href="shipment-management.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-indigo-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Manage Shipments</p>
                            <p class="text-xs text-gray-500">Tracking & delivery status</p>
                        </div>
                    </a>

                    <a href="analytics.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-pink-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-pink-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v4a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Analytics</p>
                            <p class="text-xs text-gray-500">Sales & performance data</p>
                        </div>
                    </a>

                    <a href="notify-vendors.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-orange-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4.828 7l2.586 2.586a2 2 0 002.828 0L12 7H4.828zM4 7h8l-2 2H4V7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Notify Vendors</p>
                            <p class="text-xs text-gray-500">Send delivery reminders</p>
                        </div>
                    </a>

                    <a href="advertisements.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-cyan-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-cyan-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H3a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3m0 0h8m-8 0v16a1 1 0 001 1h6a1 1 0 001-1V4H7z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Advertisements</p>
                            <p class="text-xs text-gray-500">Manage ads & promotions</p>
                        </div>
                    </a>

                    <a href="coupons.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-purple-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">Coupons</p>
                            <p class="text-xs text-gray-500">Manage discount coupons</p>
                        </div>
                    </a>

                    <a href="settings.php" class="flex items-center p-4 border border-gray-200 rounded-lg hover:bg-gray-50 transition duration-200">
                        <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center mr-4">
                            <svg class="w-5 h-5 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            </svg>
                        </div>
                        <div>
                            <p class="text-sm font-medium text-gray-900">System Settings</p>
                            <p class="text-xs text-gray-500">Platform configuration</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
