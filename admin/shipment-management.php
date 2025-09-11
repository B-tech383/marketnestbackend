<?php
require_once '../config/config.php';
require_once '../includes/tracking.php';

require_admin();

$tracking_manager = new TrackingManager();
$message = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $shipment_id = $_POST['shipment_id'];
    $status = $_POST['status'];
    $location = sanitize_input($_POST['location']);
    $description = sanitize_input($_POST['description']);
    
    $result = $tracking_manager->update_shipment_status($shipment_id, $status, $location, $description);
    $message = $result['message'];
}

$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$shipments = $tracking_manager->get_all_shipments($limit, $offset);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Shipment Management - Admin Dashboard</title>
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
                    <a href="../index.php" class="text-2xl font-bold text-accent"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700">Shipment Management</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Dashboard</a>
                    <a href="../logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Shipment Management</h1>
            <p class="mt-2 text-gray-600">Track and update shipment statuses</p>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow rounded-lg overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">All Shipments</h2>
            </div>
            
            <?php if (empty($shipments)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    No shipments found
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Tracking Number</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Order</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($shipments as $shipment): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo $shipment['tracking_number']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo $shipment['carrier']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo $shipment['order_number']; ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($shipment['created_at'])); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($shipment['first_name'] . ' ' . $shipment['last_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php
                                        $status_colors = [
                                            'pending' => 'bg-yellow-100 text-yellow-800',
                                            'picked_up' => 'bg-blue-100 text-blue-800',
                                            'in_transit' => 'bg-purple-100 text-purple-800',
                                            'out_for_delivery' => 'bg-blue-100 text-blue-800',
                                            'delivered' => 'bg-green-100 text-green-800'
                                        ];
                                        ?>
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_colors[$shipment['status']]; ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo htmlspecialchars($shipment['current_location'] ?: 'Not specified'); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="openUpdateModal(<?php echo htmlspecialchars(json_encode($shipment)); ?>)" 
                                                class="text-accent hover:text-blue-600">
                                            Update
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Pagination -->
        <div class="mt-8 flex justify-center">
            <div class="flex space-x-2">
                <?php if ($page > 1): ?>
                    <a href="?page=<?php echo $page - 1; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Previous</a>
                <?php endif; ?>
                
                <?php if (count($shipments) == $limit): ?>
                    <a href="?page=<?php echo $page + 1; ?>" 
                       class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Next</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Update Modal - Professional Market Nest Styling -->
    <div id="updateModal" class="fixed inset-0 bg-black bg-opacity-60 hidden backdrop-blur-sm transition-all duration-300 z-50">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="bg-white rounded-xl shadow-2xl max-w-md w-full border border-gray-100 transform transition-all duration-300">
                <form method="POST">
                    <div class="px-6 py-4 border-b border-gray-200 bg-gradient-to-r from-accent to-blue-600 rounded-t-xl">
                        <h3 class="text-lg font-semibold text-white flex items-center">
                            <span class="w-8 h-8 bg-white bg-opacity-20 rounded-lg flex items-center justify-center mr-3">
                                <span class="text-white font-bold text-sm">MN</span>
                            </span>
                            Update Shipment Status
                        </h3>
                    </div>
                    
                    <div class="px-6 py-4 space-y-4">
                        <input type="hidden" name="shipment_id" id="modal_shipment_id">
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tracking Number</label>
                            <p id="modal_tracking_number" class="text-sm text-gray-900 font-medium"></p>
                        </div>
                        
                        <div>
                            <label for="modal_status" class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                            <select name="status" id="modal_status" required 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                                <option value="pending">Pending</option>
                                <option value="picked_up">Picked Up</option>
                                <option value="in_transit">In Transit</option>
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                            </select>
                        </div>
                        
                        <div>
                            <label for="modal_location" class="block text-sm font-medium text-gray-700 mb-1">Current Location</label>
                            <input type="text" name="location" id="modal_location" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                        </div>
                        
                        <div>
                            <label for="modal_description" class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                            <textarea name="description" id="modal_description" rows="3" 
                                      class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-orange-500 focus:border-
