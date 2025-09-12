<?php
require_once '../config/config.php';
require_once '../includes/auth.php';
require_once '../includes/product.php';

// Check if user is logged in
if (!isLoggedIn()) {
	header('Location: ../login.php');
	exit();
}

$user = getCurrentUser();

// Authorize: allow if user has 'vendor' role in user_roles or is admin
$database = new Database();
$db = $database->getConnection();
$hasVendorRole = false;
$hasAdminRole = false;
try {
	$stmt = $db->prepare("SELECT role FROM user_roles WHERE user_id = ?");
	$stmt->execute([$user['id']]);
	$roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
	$hasVendorRole = in_array('vendor', $roles, true);
	$hasAdminRole = in_array('admin', $roles, true);
} catch (PDOException $e) {
	// Fallback to existing role field if query fails
	$hasVendorRole = ($user['role'] ?? '') === 'vendor';
	$hasAdminRole = ($user['role'] ?? '') === 'admin';
}

if (!$hasVendorRole && !$hasAdminRole) {
	header('Location: ../index.php');
	exit();
}

$productManager = new ProductManager();

// Resolve vendor business id from current user
$stmt = $db->prepare("SELECT id FROM vendors WHERE user_id = ?");
$stmt->execute([$user['id']]);
$vendorRow = $stmt->fetch(PDO::FETCH_ASSOC);
$vendorId = $vendorRow['id'] ?? null;

// Handle product actions
if (($_POST['action'] ?? '') === 'update_status' && $vendorId) {
	$product_id = $_POST['product_id'];
	$status = $_POST['status'];
	
	$productManager->updateProductStatus($product_id, $status, $vendorId);
	header('Location: products.php?updated=1');
	exit();
}

// Get vendor's products (direct)
$products = $vendorId ? $productManager->getVendorProductsSimple($vendorId) : [];
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

		<?php if (!$vendorId): ?>
			<div class="bg-yellow-50 border border-yellow-200 text-yellow-800 px-4 py-3 rounded mb-6">
				No vendor profile is linked to your account yet. Please complete your vendor application or contact support.
			</div>
		<?php endif; ?>

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
											<?php $thumb = (!empty($product['images']) && is_array($product['images'])) ? $product['images'][0] : null; ?>
											<?php if ($thumb): ?>
												<?php $src = preg_match('/^https?:\\/\\//', $thumb) ? $thumb : ('../' . $thumb); ?>
												<img src="<?php echo htmlspecialchars($src); ?>" 
													alt="<?php echo htmlspecialchars($product['name']); ?>"
													class="w-12 h-12 object-cover rounded-lg">
											<?php else: ?>
												<?php 
													$name = trim($product['name'] ?? '');
													$parts = preg_split('/\s+/', $name);
													$first = strtoupper(substr($parts[0] ?? 'P', 0, 1));
													$second = strtoupper(substr($parts[1] ?? '', 0, 1));
													$initials = $first . $second;
												?>
												<div class="w-12 h-12 bg-primary/10 rounded-lg flex items-center justify-center">
													<span class="text-primary text-sm font-semibold"><?php echo htmlspecialchars($initials); ?></span>
												</div>
											<?php endif; ?>
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
                                        <div class="space-y-1">
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $product['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                                <?php echo ucfirst($product['status']); ?>
                                            </span>
                                            <br>
                                            <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                                <?php echo $product['admin_approved'] ? 'bg-blue-100 text-blue-800' : 'bg-yellow-100 text-yellow-800'; ?>">
                                                <?php echo $product['admin_approved'] ? 'Approved' : 'Pending Review'; ?>
                                            </span>
                                        </div>
                                    </td>
									<td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
										<?php echo date('M j, Y', strtotime($product['created_at'])); ?>
									</td>
									<td class="px-6 py-4 whitespace-nowrap text-sm font-medium space-x-2">
										<button onclick="previewProduct(<?php echo $product['id']; ?>)" 
												class="text-blue-600 hover:text-blue-800">Preview</button>
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
