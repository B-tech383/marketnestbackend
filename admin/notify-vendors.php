<?php
require_once '../config/config.php';
require_once '../includes/vendor.php';
require_once '../includes/order.php';

require_admin();

$vendor_manager = new VendorManager();
$orderManager = new OrderManager();

// Handle form submission
if ($_POST && isset($_POST['action'])) {
    $action = $_POST['action'];
    
    if ($action === 'notify_all_vendors') {
        // Get all vendors with pending orders
        $database = new Database();
        $db = $database->getConnection();
        
        $stmt = $db->prepare("
            SELECT DISTINCT v.id as vendor_id, v.*, u.email, u.first_name, u.last_name
            FROM vendors v
            JOIN users u ON v.user_id = u.id
            JOIN order_items oi ON v.id = oi.vendor_id
            JOIN orders o ON oi.order_id = o.id
            WHERE o.status = 'pending' OR o.payment_status = 'pending'
        ");
        $stmt->execute();
        $vendors_with_pending = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $notified_count = 0;
        foreach ($vendors_with_pending as $vendor) {
            // Here you would send actual email/SMS notifications
            // For now, we'll just add a notification to the database
            $stmt = $db->prepare("
                INSERT INTO vendor_notifications (vendor_id, type, title, message) 
                VALUES (?, 'delivery_reminder', 'Pending Orders Alert', 'You have pending orders that need to be processed for delivery.')
            ");
            $stmt->execute([$vendor['vendor_id']]);
            $notified_count++;
        }
        
        $success_message = "Successfully notified {$notified_count} vendors about their pending orders.";
    }
    
    if ($action === 'notify_vendor' && isset($_POST['vendor_id'])) {
        // Send notification to individual vendor
        $vendor_id = (int)$_POST['vendor_id'];
        
        $stmt = $db->prepare("
            INSERT INTO vendor_notifications (vendor_id, type, title, message) 
            VALUES (?, 'delivery_reminder', 'Pending Orders Alert', 'You have pending orders that need to be processed for delivery.')
        ");
        $stmt->execute([$vendor_id]);
        
        $success_message = "Successfully sent notification to vendor.";
    }
}

// Get pending orders by vendor
$database = new Database();
$db = $database->getConnection();

$stmt = $db->prepare("
    SELECT v.id as vendor_id, v.business_name, v.user_id, u.email, COUNT(oi.id) as pending_count, SUM(oi.total) as total_value
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    JOIN order_items oi ON v.id = oi.vendor_id
    JOIN orders o ON oi.order_id = o.id
    WHERE o.status = 'pending' OR o.payment_status = 'pending'
    GROUP BY v.id, v.business_name, v.user_id, u.email
    ORDER BY pending_count DESC
");
$stmt->execute();
$vendor_pending_orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notify Vendors - <?php echo SITE_NAME; ?></title>
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
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-2xl font-bold text-primary"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700 font-medium">Admin Panel</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Dashboard</a>
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="../logout.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Notify Vendors</h1>
            <p class="mt-2 text-gray-600">Send delivery reminders to vendors with pending orders</p>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="mb-6 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Quick Actions</h2>
            </div>
            <div class="p-6">
                <div class="flex flex-wrap gap-4">
                    <form method="POST" class="inline">
                        <input type="hidden" name="action" value="notify_all_vendors">
                        <button type="submit" class="bg-orange-500 text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition">
                            Notify All Vendors with Pending Orders
                        </button>
                    </form>
                    
                    <a href="dashboard.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition">
                        Back to Dashboard
                    </a>
                </div>
            </div>
        </div>

        <!-- Vendors with Pending Orders -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Vendors with Pending Orders</h2>
            </div>
            <div class="p-6">
                <?php if (empty($vendor_pending_orders)): ?>
                    <p class="text-gray-500 text-center py-8">No vendors have pending orders at the moment.</p>
                <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pending Orders</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Value</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($vendor_pending_orders as $vendor): ?>
                                    <tr>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['business_name']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                <?php echo $vendor['pending_count']; ?> orders
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm text-gray-900">$<?php echo number_format($vendor['total_value'], 2); ?></div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <form method="POST" class="inline">
                                                <input type="hidden" name="action" value="notify_vendor">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['vendor_id']; ?>">
                                                <button type="submit" class="text-orange-600 hover:text-orange-900">
                                                    Send Reminder
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>
