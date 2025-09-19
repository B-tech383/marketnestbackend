<?php
require_once '../config/config.php';
require_once '../includes/product.php';

require_admin();

$productManager = new ProductManager();
$message = '';
$error = '';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_csrf_token();
}

// Handle product approval
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['approve_product'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $product_id = (int)$_POST['product_id'];
        $admin_id = $_SESSION['user_id'];
        
        if ($product_id > 0) {
            $result = $productManager->approve_product($product_id, $admin_id);
            if ($result['success']) {
                $message = $result['message'];
                // Redirect to prevent form resubmission
                header('Location: products.php?filter=' . $filter . '&approved=1');
                exit;
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Invalid product ID.';
        }
    } else {
        $error = 'Invalid security token. Please try again.';
    }
}

// Handle product rejection
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['reject_product'])) {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $product_id = (int)$_POST['product_id'];
        $admin_id = $_SESSION['user_id'];
        $reason = sanitize_input($_POST['reason'] ?? '');
        
        if ($product_id > 0) {
            $result = $productManager->reject_product($product_id, $admin_id, $reason);
            if ($result['success']) {
                $message = $result['message'];
                // Redirect to prevent form resubmission
                header('Location: products.php?filter=' . $filter . '&rejected=1');
                exit;
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Invalid product ID.';
        }
    } else {
        $error = 'Invalid security token. Please try again.';
    }
}

// Get filter parameter
$filter = $_GET['filter'] ?? 'pending';
$valid_filters = ['all', 'pending', 'approved', 'rejected'];
if (!in_array($filter, $valid_filters)) {
    $filter = 'pending';
}
$approvedProducts = $productManager->getProducts(20, 0, null, null, null, false, 'approved');
$pendingProducts  = $productManager->getProducts(20, 0, null, null, null, false, 'pending');
$rejectedProducts = $productManager->getProducts(20, 0, null, null, null, false, 'rejected');

// Get products with error handling
$products = [];
try {
    $products = $productManager->get_all_products_admin(50, 0, $filter);
    if (empty($products)) {
        // Fallback to simple method
        $products = $productManager->get_all_products_admin_simple(50, 0, $filter);
    }
} catch (Exception $e) {
    error_log("Error fetching products: " . $e->getMessage());
    $error = "Error loading products. Please try again.";
}
// Get pending count with error handling
$pending_count = 0;
try {
    $pending_products = $productManager->get_pending_products(100);
    $pending_count = is_array($pending_products) ? count($pending_products) : 0;
} catch (Exception $e) {
    error_log("Error fetching pending products count: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Admin Dashboard</title>
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
                    <span class="text-gray-700">Admin Dashboard</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Dashboard</a>
                    <a href="vendor-management.php" class="text-gray-700 hover:text-accent">Vendors</a>
                    <a href="orders.php" class="text-gray-700 hover:text-accent">Orders</a>
                    <a href="../logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Product Management</h1>
                <p class="mt-2 text-gray-600">Review vendor products and manage the product catalog</p>
            </div>
            <a href="add-product.php" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                Add New Product
            </a>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['approved'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Product approved successfully!
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['rejected'])): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                Product rejected successfully.
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Filter Tabs -->
        <div class="mb-6">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8">
                    <a href="?filter=pending" 
                       class="<?php echo $filter === 'pending' ? 'border-accent text-accent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> py-2 px-1 border-b-2 font-medium text-sm">
                        Pending Approval 
                        <?php if ($pending_count > 0): ?>
                            <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full ml-2">
                                <?php echo $pending_count; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="?filter=approved" 
                       class="<?php echo $filter === 'approved' ? 'border-accent text-accent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> py-2 px-1 border-b-2 font-medium text-sm">
                        Approved Products
                    </a>
                    <a href="?filter=rejected" 
                       class="<?php echo $filter === 'rejected' ? 'border-accent text-accent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> py-2 px-1 border-b-2 font-medium text-sm">
                        Rejected Products
                    </a>
                    <a href="?filter=all" 
                       class="<?php echo $filter === 'all' ? 'border-accent text-accent' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'; ?> py-2 px-1 border-b-2 font-medium text-sm">
                        All Products
                    </a>
                </nav>
            </div>
        </div>
        
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900 capitalize">
                    <?php echo $filter === 'all' ? 'All' : $filter; ?> Products
                    <span class="bg-gray-100 text-gray-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                        <?php echo count($products); ?>
                    </span>
                </h2>
            </div>
            
            <?php if (empty($products)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    <div class="text-6xl mb-4">📦</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Products Found</h3>
                    <p>No products match the current filter criteria.</p>
                    <?php if (isset($_GET['debug'])): ?>
                        <div class="mt-4 text-xs bg-gray-100 p-2 rounded">
                            <p>Filter: <?php echo htmlspecialchars($filter); ?></p>
                            <p>Total pending: <?php echo $pending_count; ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Category</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                                <?php if ($filter === 'pending'): ?>
                                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="flex-shrink-0 h-16 w-16">
                                                <?php if (!empty($product['images'])): ?>
                                                    <?php
                                                    $img_src = $product['images'][0];
                                                    // Handle image path - if it starts with uploads/, prepend ../
                                                    if (strpos($img_src, 'uploads/') === 0) {
                                                        $img_src = '../' . $img_src;
                                                    } elseif (strpos($img_src, 'http://') !== 0 && strpos($img_src, 'https://') !== 0) {
                                                        $img_src = '../uploads/products/' . basename($img_src);
                                                    }
                                                    ?>
                                                    <img class="h-16 w-16 object-cover rounded-lg" src="<?php echo htmlspecialchars($img_src); ?>" alt="">
                                                <?php else: ?>
                                                    <?php 
                                                        $name = trim($product['name'] ?? '');
                                                        $parts = preg_split('/\s+/', $name);
                                                        $first = strtoupper(substr($parts[0] ?? 'P', 0, 1));
                                                        $second = strtoupper(substr($parts[1] ?? '', 0, 1));
                                                        $initials = $first . $second;
                                                    ?>
                                                    <div class="h-16 w-16 bg-primary/10 rounded-lg flex items-center justify-center">
                                                        <span class="text-primary text-sm font-semibold"><?php echo htmlspecialchars($initials); ?></span>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($product['name']); ?></div>
                                                <div class="text-sm text-gray-500">SKU: <?php echo htmlspecialchars($product['sku'] ?? 'N/A'); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($product['business_name'] ?? 'Admin'); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($product['vendor_email'] ?? ''); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900">
                                            <?php echo htmlspecialchars($product['category_name'] ?? 'Uncategorized'); ?>
                                        </div>
                                    </td>

                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php if ($product['sale_price']): ?>
                                                <span class="text-red-600">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                                <span class="text-gray-500 line-through ml-2">$<?php echo number_format($product['price'], 2); ?></span>
                                            <?php else: ?>
                                                $<?php echo number_format($product['price'], 2); ?>
                                            <?php endif; ?>
                                        </div>
                                        <div class="text-sm text-gray-500">Stock: <?php echo $product['stock_quantity']; ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($product['admin_approved']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Approved
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                Pending
                                            </span>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['status'] === 'inactive'): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800 ml-2">
                                                Rejected
                                            </span>
                                        <?php endif; ?>
                                        
                                        <!-- Preview Button -->
                                        <button onclick="previewProduct(<?php echo $product['id']; ?>)" 
                                                class="ml-2 px-2 py-1 text-xs bg-blue-100 text-blue-800 rounded hover:bg-blue-200 transition">
                                            Preview
                                        </button>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo date('M j, Y', strtotime($product['created_at'])); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo date('g:i A', strtotime($product['created_at'])); ?></div>
                                    </td>
                                    
                                    <?php if ($filter === 'pending'): ?>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex space-x-2">
                                                <!-- Approve Button -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <button type="submit" name="approve_product" 
                                                            class="bg-green-600 text-white px-3 py-1 rounded-md text-xs font-medium hover:bg-green-700 transition duration-200"
                                                            onclick="return confirm('Approve this product?')">
                                                        Approve
                                                    </button>
                                                </form>
                                                
                                                <!-- Reject Button -->
                                                <form method="POST" class="inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                                    <input type="hidden" name="reason" value="Quality standards not met">
                                                    <button type="submit" name="reject_product" 
                                                            class="bg-red-600 text-white px-3 py-1 rounded-md text-xs font-medium hover:bg-red-700 transition duration-200"
                                                            onclick="return confirm('Reject this product?')">
                                                        Reject
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    <?php endif; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Statistics -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Pending Approval</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $pending_count; ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Preview Modal -->
    <div id="previewModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-medium text-gray-900">Product Preview</h3>
                    <button onclick="closePreview()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div id="previewContent" class="max-h-96 overflow-y-auto">
                    <!-- Preview content will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        function previewProduct(productId) {
            // Fetch product details and show preview
            fetch(`../api/product-preview.php?id=${productId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('previewContent').innerHTML = data.html;
                        document.getElementById('previewModal').classList.remove('hidden');
                    } else {
                        alert('Error loading preview: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error loading preview');
                });
        }

        function closePreview() {
            document.getElementById('previewModal').classList.add('hidden');
        }

        // Close modal when clicking outside
        document.getElementById('previewModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closePreview();
            }
        });
    </script>
</body>
</html>