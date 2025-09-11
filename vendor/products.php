<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/product.php';

// Check if user is logged in and is a vendor
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

$user = getCurrentUser();
if ($user['role'] !== 'vendor') {
    header('Location: ../index.php');
    exit();
}

$productManager = new ProductManager();

// Handle product actions
if ($_POST['action'] ?? '' === 'update_status') {
    $product_id = $_POST['product_id'];
    $status = $_POST['status'];
    
    $productManager->updateProductStatus($product_id, $status, $user['id']);
    header('Location: products.php?updated=1');
    exit();
}

// Get vendor's products
$products = $productManager->getVendorProducts($user['id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Products - Vendor Dashboard</title>
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
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-2xl font-bold text-accent">E-Commerce</a>
                    <span class="text-gray-400">|</span>
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Vendor</a>
                    <span class="text-gray-400">></span>
                    <span class="text-gray-700 font-medium">Products</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Dashboard</a>
                    <a href="../logout.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">My Products</h1>
                <p class="text-gray-600 mt-2">Manage your product listings</p>
            </div>
            <a href="add-product.php" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                Add New Product
            </a>
        </div>

        <?php if (isset($_GET['updated'])): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Product updated successfully!
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <?php if (empty($products)): ?>
                <div class="text-center py-12">
                    <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                    </svg>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No products yet</h3>
                    <p class="text-gray-600 mb-4">Start by adding your first product to your store.</p>
                    <a href="add-product.php" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                        Add Your First Product
                    </a>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Product</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Price</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Stock</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Created</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($products as $product): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img src="../<?php echo htmlspecialchars($product['image_url'] ?: 'placeholder.svg?height=50&width=50'); ?>" 
                                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="w-12 h-12 object-cover rounded-lg">
                                            <div class="ml-4">
                                                <div class="text-sm font-medium text-gray-900">
                                                    <?php echo htmlspecialchars($product['name']); ?>
                                                </div>
                                                <div class="text-sm text-gray-500">
                                                    SKU: <?php echo htmlspecialchars($product['sku'] ?: 'N/A'); ?>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        $<?php echo number_format($product['price'], 2); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $product['stock_quantity']; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                            <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                            <?php echo ucfirst($product['status']); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        <?php echo date('M j, Y', strtotime($product['created_at'])); ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <a href="../product-detail.php?id=<?php echo $product['id']; ?>" 
                                           class="text-primary hover:text-blue-600">View</a>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $product['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="text-primary hover:text-blue-600">
                                                <?php echo $product['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
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
</body>
</html>
