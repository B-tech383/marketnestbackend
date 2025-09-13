<?php
require_once '../config/config.php';
require_once '../includes/coupon.php';

require_admin();

$couponManager = new CouponManager();
$message = '';
$error = '';

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_csrf_token();
}

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'create':
                $result = $couponManager->createCoupon([
                    'code' => sanitize_input($_POST['code']),
                    'name' => sanitize_input($_POST['name']),
                    'description' => sanitize_input($_POST['description']),
                    'type' => $_POST['type'],
                    'value' => (float)$_POST['value'],
                    'minimum_amount' => (float)$_POST['minimum_amount'],
                    'maximum_discount' => !empty($_POST['maximum_discount']) ? (float)$_POST['maximum_discount'] : null,
                    'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
                    'is_active' => isset($_POST['is_active']),
                    'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null,
                    'product_ids' => $_POST['product_ids'] ?? []
                ]);
                
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'update':
                $coupon_id = (int)$_POST['coupon_id'];
                $result = $couponManager->updateCoupon($coupon_id, [
                    'code' => sanitize_input($_POST['code']),
                    'name' => sanitize_input($_POST['name']),
                    'description' => sanitize_input($_POST['description']),
                    'type' => $_POST['type'],
                    'value' => (float)$_POST['value'],
                    'minimum_amount' => (float)$_POST['minimum_amount'],
                    'maximum_discount' => !empty($_POST['maximum_discount']) ? (float)$_POST['maximum_discount'] : null,
                    'usage_limit' => !empty($_POST['usage_limit']) ? (int)$_POST['usage_limit'] : null,
                    'is_active' => isset($_POST['is_active']),
                    'start_date' => !empty($_POST['start_date']) ? $_POST['start_date'] : null,
                    'end_date' => !empty($_POST['end_date']) ? $_POST['end_date'] : null
                ]);
                
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
                
            case 'delete':
                $coupon_id = (int)$_POST['coupon_id'];
                $result = $couponManager->deleteCoupon($coupon_id);
                
                if ($result['success']) {
                    $message = $result['message'];
                } else {
                    $error = $result['message'];
                }
                break;
        }
    } else {
        $error = 'Invalid security token. Please try again.';
    }
}

// Get all coupons
$coupons = $couponManager->getAllCoupons(100, 0);

// Get products for free product coupons
require_once '../includes/product.php';
$productManager = new ProductManager();
$products = $productManager->get_products(100, 0);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Coupon Management - Admin Dashboard</title>
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
                    <a href="products.php" class="text-gray-700 hover:text-accent">Products</a>
                    <a href="orders.php" class="text-gray-700 hover:text-accent">Orders</a>
                    <a href="../logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8 flex justify-between items-center">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Coupon Management</h1>
                <p class="mt-2 text-gray-600">Create and manage discount coupons</p>
            </div>
            <button onclick="openCreateModal()" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                Create New Coupon
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Coupons Table -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    All Coupons
                    <span class="bg-gray-100 text-gray-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                        <?php echo count($coupons); ?>
                    </span>
                </h2>
            </div>
            
            <?php if (empty($coupons)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    <div class="text-6xl mb-4">🎫</div>
                    <h3 class="text-lg font-medium text-gray-900 mb-2">No Coupons Found</h3>
                    <p>Create your first coupon to start offering discounts to customers.</p>
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Code</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Value</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Usage</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($coupons as $coupon): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <code class="bg-gray-100 px-2 py-1 rounded text-sm font-mono">
                                            <?php echo htmlspecialchars($coupon['code']); ?>
                                        </code>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($coupon['name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($coupon['description']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php 
                                            switch($coupon['type']) {
                                                case 'percentage': echo 'bg-blue-100 text-blue-800'; break;
                                                case 'fixed': echo 'bg-green-100 text-green-800'; break;
                                                case 'free_product': echo 'bg-purple-100 text-purple-800'; break;
                                            }
                                            ?>">
                                            <?php echo ucfirst(str_replace('_', ' ', $coupon['type'])); ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php 
                                        if ($coupon['type'] === 'percentage') {
                                            echo $coupon['value'] . '%';
                                        } elseif ($coupon['type'] === 'free_product') {
                                            echo 'FREE';
                                        } else {
                                            echo format_currency($coupon['value']);
                                        }
                                        ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                        <?php echo $coupon['used_count']; ?>
                                        <?php if ($coupon['usage_limit']): ?>
                                            / <?php echo $coupon['usage_limit']; ?>
                                        <?php else: ?>
                                            / ∞
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                            <?php echo $coupon['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                            <?php echo $coupon['is_active'] ? 'Active' : 'Inactive'; ?>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                        <button onclick="editCoupon(<?php echo htmlspecialchars(json_encode($coupon)); ?>)" 
                                                class="text-blue-600 hover:text-blue-800">Edit</button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="coupon_id" value="<?php echo $coupon['id']; ?>">
                                            <button type="submit" class="text-red-600 hover:text-red-800"
                                                    onclick="return confirm('Are you sure you want to delete this coupon?')">
                                                Delete
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

    <!-- Create/Edit Coupon Modal -->
    <div id="couponModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50">
        <div class="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
            <div class="mt-3">
                <div class="flex justify-between items-center mb-4">
                    <h3 id="modalTitle" class="text-lg font-medium text-gray-900">Create New Coupon</h3>
                    <button onclick="closeModal()" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                
                <form id="couponForm" method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input type="hidden" name="action" value="create">
                    <input type="hidden" name="coupon_id" id="couponId" value="">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Coupon Code *</label>
                            <input type="text" name="code" id="code" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Coupon Name *</label>
                            <input type="text" name="name" id="name" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea name="description" id="description" rows="2"
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent"></textarea>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Type *</label>
                            <select name="type" id="type" required onchange="toggleValueFields()"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                <option value="percentage">Percentage Discount</option>
                                <option value="fixed">Fixed Amount Discount</option>
                                <option value="free_product">Free Product</option>
                            </select>
                        </div>
                        
                        <div id="valueField">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Value *</label>
                            <input type="number" name="value" id="value" step="0.01" required
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Minimum Order Amount</label>
                            <input type="number" name="minimum_amount" id="minimum_amount" step="0.01" value="0"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div id="maxDiscountField">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Maximum Discount</label>
                            <input type="number" name="maximum_discount" id="maximum_discount" step="0.01"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Usage Limit</label>
                            <input type="number" name="usage_limit" id="usage_limit" min="1"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                            <input type="datetime-local" name="start_date" id="start_date"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                            <input type="datetime-local" name="end_date" id="end_date"
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                        </div>
                        
                        <div class="md:col-span-2">
                            <label class="flex items-center">
                                <input type="checkbox" name="is_active" id="is_active" checked
                                       class="rounded border-gray-300 text-accent focus:ring-accent">
                                <span class="ml-2 text-sm text-gray-700">Active</span>
                            </label>
                        </div>
                        
                        <div id="productSelection" class="md:col-span-2 hidden">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Select Products (for free product coupons)</label>
                            <div class="max-h-40 overflow-y-auto border border-gray-300 rounded-lg p-3">
                                <?php foreach ($products as $product): ?>
                                    <label class="flex items-center mb-2">
                                        <input type="checkbox" name="product_ids[]" value="<?php echo $product['id']; ?>"
                                               class="rounded border-gray-300 text-accent focus:ring-accent">
                                        <span class="ml-2 text-sm text-gray-700"><?php echo htmlspecialchars($product['name']); ?></span>
                                    </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex justify-end space-x-3">
                        <button type="button" onclick="closeModal()"
                                class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50">
                            Cancel
                        </button>
                        <button type="submit"
                                class="px-4 py-2 bg-accent text-white rounded-lg hover:bg-blue-600">
                            Save Coupon
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCreateModal() {
            document.getElementById('modalTitle').textContent = 'Create New Coupon';
            document.getElementById('couponForm').reset();
            document.querySelector('input[name="action"]').value = 'create';
            document.getElementById('couponId').value = '';
            document.getElementById('couponModal').classList.remove('hidden');
            toggleValueFields();
        }
        
        function editCoupon(coupon) {
            document.getElementById('modalTitle').textContent = 'Edit Coupon';
            document.querySelector('input[name="action"]').value = 'update';
            document.getElementById('couponId').value = coupon.id;
            document.getElementById('code').value = coupon.code;
            document.getElementById('name').value = coupon.name;
            document.getElementById('description').value = coupon.description || '';
            document.getElementById('type').value = coupon.type;
            document.getElementById('value').value = coupon.value;
            document.getElementById('minimum_amount').value = coupon.minimum_amount || 0;
            document.getElementById('maximum_discount').value = coupon.maximum_discount || '';
            document.getElementById('usage_limit').value = coupon.usage_limit || '';
            document.getElementById('start_date').value = coupon.start_date ? coupon.start_date.slice(0, 16) : '';
            document.getElementById('end_date').value = coupon.end_date ? coupon.end_date.slice(0, 16) : '';
            document.getElementById('is_active').checked = coupon.is_active == 1;
            
            document.getElementById('couponModal').classList.remove('hidden');
            toggleValueFields();
        }
        
        function closeModal() {
            document.getElementById('couponModal').classList.add('hidden');
        }
        
        function toggleValueFields() {
            const type = document.getElementById('type').value;
            const valueField = document.getElementById('valueField');
            const maxDiscountField = document.getElementById('maxDiscountField');
            const productSelection = document.getElementById('productSelection');
            
            if (type === 'free_product') {
                valueField.style.display = 'none';
                maxDiscountField.style.display = 'none';
                productSelection.classList.remove('hidden');
            } else {
                valueField.style.display = 'block';
                maxDiscountField.style.display = 'block';
                productSelection.classList.add('hidden');
            }
        }
        
        // Close modal when clicking outside
        document.getElementById('couponModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
