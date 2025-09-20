<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/order.php';
require_once '../includes/vendor.php';

require_vendor();
$user = getCurrentUser();

$database = new Database();
$db = $database->getConnection();
$orderManager = new OrderManager();
$vendorManager = new VendorManager();

// Get vendor ID from vendors table
$vendor_info = $vendorManager->get_vendor_by_user_id($user['id']);
$vendor_id = $vendor_info ? $vendor_info['id'] : null;

if (!$vendor_id) {
    die('Vendor not found.');
}

// Fetch all vendor orders
$orders = $orderManager->getVendorOrders($vendor_id, 100, 0); // adjust limit/pagination if needed
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Vendor Orders</title>
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
<body class="bg-gray-100 min-h-screen p-6">

<div class="max-w-7xl mx-auto">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-bold text-gray-900">All Orders</h1>
        <a href="dashboard.php" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 transition">Back to Dashboard</a>
    </div>

    <div class="bg-white rounded-lg shadow overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order #</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Customer</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Items</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Amount</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Delivery Location</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Order Date</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-4 text-center text-gray-500">No orders yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <?php
                            // Safe fallback for names
                            $customerName = trim(
                                ($order['first_name'] ?? $order['customer_name'] ?? '') . ' ' .
                                ($order['last_name'] ?? '')
                            );
                            if (empty($customerName)) {
                                $customerName = 'Unknown Customer';
                            }

                            $itemCount = (int)($order['item_count'] ?? 0);
                            $totalAmount = number_format((float)($order['total_amount'] ?? 0), 2);
                            $status = ucfirst($order['status'] ?? 'pending');
                            $orderDate = isset($order['created_at']) ? date('M j, Y', strtotime($order['created_at'])) : '-';

                            // Delivery location (from shipments table, or fallback)
                            $deliveryLocation = htmlspecialchars($order['shipping_address'] ?? $order['delivery_address'] ?? '-');
                        ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 text-gray-900 font-medium">#<?php echo htmlspecialchars($order['order_number'] ?? '-'); ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo htmlspecialchars($customerName); ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo $itemCount; ?></td>
                            <td class="px-6 py-4 text-gray-900 font-semibold">$<?php echo $totalAmount; ?></td>
                            <td class="px-6 py-4">
                                <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                    <?php echo $status === 'Delivered' ? 'bg-green-100 text-green-800' :
                                          ($status === 'Shipped' ? 'bg-blue-100 text-blue-800' :
                                          ($status === 'Processing' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800')); ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-700"><?php echo $deliveryLocation; ?></td>
                            <td class="px-6 py-4 text-gray-700"><?php echo $orderDate; ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
