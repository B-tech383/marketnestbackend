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
$categories = $productManager->getCategories();

$success = false;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = floatval($_POST['price']);
    $category_id = intval($_POST['category_id']);
    $stock_quantity = intval($_POST['stock_quantity']);
    $sku = trim($_POST['sku']);
    $weight = floatval($_POST['weight']);
    $dimensions = trim($_POST['dimensions']);
    
    // Handle image upload
    $image_url = '';
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = '../uploads/products/';
        if (!file_exists($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $file_extension = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid() . '.' . $file_extension;
        $upload_path = $upload_dir . $filename;
        
        if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
            $image_url = 'uploads/products/' . $filename;
        }
    }
    
    if ($name && $description && $price > 0 && $category_id && $stock_quantity >= 0) {
        $product_id = $productManager->addProduct([
            'vendor_id' => $user['id'],
            'name' => $name,
            'description' => $description,
            'price' => $price,
            'category_id' => $category_id,
            'stock_quantity' => $stock_quantity,
            'sku' => $sku,
            'weight' => $weight,
            'dimensions' => $dimensions,
            'image_url' => $image_url,
            'status' => 'active'
        ]);
        
        if ($product_id) {
            $success = true;
        } else {
            $error = 'Failed to add product. Please try again.';
        }
    } else {
        $error = 'Please fill in all required fields.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Vendor Dashboard</title>
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
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-2xl font-bold text-primary">E-Commerce</a>
                    <span class="text-gray-400">|</span>
                    <a href="dashboard.php" class="text-gray-700 hover:text-primary">Vendor</a>
                    <span class="text-gray-400">></span>
                    <span class="text-gray-700 font-medium">Add Product</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                    <a href="../logout.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
            <p class="text-gray-600 mt-2">Create a new product listing for your store</p>
        </div>

        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                Product added successfully! <a href="products.php" class="underline">View your products</a>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="bg-white rounded-lg shadow">
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                        <input type="text" id="name" name="name" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                        <input type="text" id="sku" name="sku" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                    <textarea id="description" name="description" rows="4" required 
                              class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div>
                        <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Price ($) *</label>
                        <input type="number" id="price" name="price" step="0.01" min="0" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                        <select id="category_id" name="category_id" required 
                                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $category): ?>
                                <option value="<?php echo $category['id']; ?>" 
                                        <?php echo ($_POST['category_id'] ?? '') == $category['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($category['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                        <input type="number" id="stock_quantity" name="stock_quantity" min="0" required 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="weight" class="block text-sm font-medium text-gray-700 mb-2">Weight (lbs)</label>
                        <input type="number" id="weight" name="weight" step="0.01" min="0" 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['weight'] ?? ''); ?>">
                    </div>

                    <div>
                        <label for="dimensions" class="block text-sm font-medium text-gray-700 mb-2">Dimensions (L x W x H)</label>
                        <input type="text" id="dimensions" name="dimensions" placeholder="e.g., 10 x 8 x 6 inches"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent"
                               value="<?php echo htmlspecialchars($_POST['dimensions'] ?? ''); ?>">
                    </div>
                </div>

                <div>
                    <label for="image" class="block text-sm font-medium text-gray-700 mb-2">Product Image</label>
                    <input type="file" id="image" name="image" accept="image/*" 
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    <p class="text-sm text-gray-500 mt-1">Upload a high-quality image of your product (JPG, PNG, GIF)</p>
                </div>

                <div class="flex justify-end space-x-4">
                    <a href="dashboard.php" class="px-6 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 transition">
                        Cancel
                    </a>
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-orange-600 transition">
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
