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

// Get categories for dropdown
$categories = $productManager->get_categories();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (verify_csrf_token($_POST['csrf_token'])) {
        $name = sanitize_input($_POST['name']);
        $description = sanitize_input($_POST['description']);
        $price = (float)$_POST['price'];
        $sale_price = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
        $category_id = (int)$_POST['category_id'];
        $stock_quantity = (int)$_POST['stock_quantity'];
        $sku = sanitize_input($_POST['sku']);
        $is_featured = isset($_POST['is_featured']) ? 1 : 0;
        $is_flash_deal = isset($_POST['is_flash_deal']) ? 1 : 0;
        $flash_deal_end = $is_flash_deal && !empty($_POST['flash_deal_end']) ? $_POST['flash_deal_end'] : null;
        
        // Handle image upload with security validation
        $images = [];
        $upload_errors = [];
        
        if (isset($_FILES['images']) && $_FILES['images']['error'][0] !== UPLOAD_ERR_NO_FILE) {
            $upload_dir = '../uploads/products/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }
            
            // Security settings
            $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            $allowed_extensions = ['jpg', 'jpeg', 'png', 'webp'];
            $max_file_size = 5 * 1024 * 1024; // 5MB
            $max_files = 5;
            
            foreach ($_FILES['images']['tmp_name'] as $key => $tmp_name) {
                if ($_FILES['images']['error'][$key] === UPLOAD_ERR_OK) {
                    // Check file count limit
                    if (count($images) >= $max_files) {
                        $upload_errors[] = "Maximum $max_files files allowed";
                        break;
                    }
                    
                    // Check file size
                    if ($_FILES['images']['size'][$key] > $max_file_size) {
                        $upload_errors[] = "File '{$_FILES['images']['name'][$key]}' is too large (max 5MB)";
                        continue;
                    }
                    
                    // Verify MIME type with finfo
                    $finfo = finfo_open(FILEINFO_MIME_TYPE);
                    $mime_type = finfo_file($finfo, $tmp_name);
                    finfo_close($finfo);
                    
                    if (!in_array($mime_type, $allowed_types)) {
                        $upload_errors[] = "File '{$_FILES['images']['name'][$key]}' has invalid type. Only JPG, PNG, and WebP allowed";
                        continue;
                    }
                    
                    // Double-check with getimagesize
                    $image_info = getimagesize($tmp_name);
                    if ($image_info === false || !in_array($image_info['mime'], $allowed_types)) {
                        $upload_errors[] = "File '{$_FILES['images']['name'][$key]}' is not a valid image";
                        continue;
                    }
                    
                    // Generate secure filename
                    $extension = match($mime_type) {
                        'image/jpeg' => 'jpg',
                        'image/jpg' => 'jpg',
                        'image/png' => 'png',
                        'image/webp' => 'webp',
                        default => 'jpg'
                    };
                    
                    $filename = 'product_' . bin2hex(random_bytes(16)) . '.' . $extension;
                    $upload_path = $upload_dir . $filename;
                    
                    if (move_uploaded_file($tmp_name, $upload_path)) {
                        // Set secure file permissions
                        chmod($upload_path, 0644);
                        $images[] = 'uploads/products/' . $filename;
                    } else {
                        $upload_errors[] = "Failed to upload '{$_FILES['images']['name'][$key]}'";
                    }
                } elseif ($_FILES['images']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                    $upload_errors[] = "Upload error for '{$_FILES['images']['name'][$key]}': " . $_FILES['images']['error'][$key];
                }
            }
        }
        
        // Add placeholder image if no images uploaded
        if (empty($images)) {
            $images[] = 'https://via.placeholder.com/400x300?text=Product+Image';
        }
        
        // Show upload errors if any
        if (!empty($upload_errors)) {
            $error = 'Upload errors: ' . implode(', ', $upload_errors);
        }
        
        // Validate required fields
        if (empty($error) && $name && $description && $price > 0 && $category_id && $stock_quantity >= 0) {
            // Admin products use vendor_id = 0 (auto-approved)
            $result = $productManager->add_product(
                0, // vendor_id = 0 for admin products
                $category_id,
                $name,
                $description,
                $price,
                $sale_price,
                $stock_quantity,
                $sku,
                $images,
                $is_featured,
                $is_flash_deal,
                $flash_deal_end
            );
            
            if ($result['success']) {
                $message = 'Product added successfully! Product ID: ' . $result['product_id'];
                // Clear form
                $_POST = [];
            } else {
                $error = $result['message'];
            }
        } else {
            $error = 'Please fill in all required fields with valid values.';
        }
    } else {
        $error = 'Invalid security token. Please try again.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Product - Admin Dashboard</title>
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
                    <a href="vendor-management.php" class="text-gray-700 hover:text-accent">Vendors</a>
                    <a href="orders.php" class="text-gray-700 hover:text-accent">Orders</a>
                    <a href="../logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Add New Product</h1>
            <p class="mt-2 text-gray-600">Create a new product for the marketplace</p>
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
        
        <div class="bg-white shadow rounded-lg">
            <form method="POST" enctype="multipart/form-data" class="p-6 space-y-6">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                
                <!-- Basic Information -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Basic Information</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($_POST['name'] ?? ''); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent" required>
                        </div>
                        
                        <div>
                            <label for="category_id" class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select id="category_id" name="category_id" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['id']; ?>" 
                                            <?php echo isset($_POST['category_id']) && $_POST['category_id'] == $category['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($category['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div>
                            <label for="sku" class="block text-sm font-medium text-gray-700 mb-2">SKU</label>
                            <input type="text" id="sku" name="sku" value="<?php echo htmlspecialchars($_POST['sku'] ?? ''); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"
                                   placeholder="e.g., ADMIN-001">
                        </div>
                        
                        <div>
                            <label for="stock_quantity" class="block text-sm font-medium text-gray-700 mb-2">Stock Quantity *</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" value="<?php echo htmlspecialchars($_POST['stock_quantity'] ?? ''); ?>" 
                                   min="0" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent" required>
                        </div>
                    </div>
                    
                    <div class="mt-6">
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">Description *</label>
                        <textarea id="description" name="description" rows="4" 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                    </div>
                </div>
                
                <!-- Pricing -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Pricing</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="price" class="block text-sm font-medium text-gray-700 mb-2">Regular Price ($) *</label>
                            <input type="number" id="price" name="price" value="<?php echo htmlspecialchars($_POST['price'] ?? ''); ?>" 
                                   min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent" required>
                        </div>
                        
                        <div>
                            <label for="sale_price" class="block text-sm font-medium text-gray-700 mb-2">Sale Price ($)</label>
                            <input type="number" id="sale_price" name="sale_price" value="<?php echo htmlspecialchars($_POST['sale_price'] ?? ''); ?>" 
                                   min="0" step="0.01" class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                        </div>
                    </div>
                </div>
                
                <!-- Images -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Product Images</h3>
                    
                    <div>
                        <label for="images" class="block text-sm font-medium text-gray-700 mb-2">Upload Images</label>
                        <input type="file" id="images" name="images[]" multiple accept="image/*" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                        <p class="text-sm text-gray-500 mt-1">Select multiple images. If no images uploaded, a placeholder will be used.</p>
                    </div>
                </div>
                
                <!-- Product Options -->
                <div class="border-b border-gray-200 pb-6">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">Product Options</h3>
                    
                    <div class="space-y-4">
                        <div class="flex items-center">
                            <input type="checkbox" id="is_featured" name="is_featured" value="1" 
                                   <?php echo isset($_POST['is_featured']) ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-accent focus:ring-accent border-gray-300 rounded">
                            <label for="is_featured" class="ml-2 text-sm text-gray-700">Featured Product</label>
                        </div>
                        
                        <div class="flex items-center">
                            <input type="checkbox" id="is_flash_deal" name="is_flash_deal" value="1" 
                                   <?php echo isset($_POST['is_flash_deal']) ? 'checked' : ''; ?>
                                   class="w-4 h-4 text-accent focus:ring-accent border-gray-300 rounded"
                                   onchange="toggleFlashDealEnd()">
                            <label for="is_flash_deal" class="ml-2 text-sm text-gray-700">Flash Deal</label>
                        </div>
                        
                        <div id="flash_deal_end_container" style="display: none;">
                            <label for="flash_deal_end" class="block text-sm font-medium text-gray-700 mb-2">Flash Deal End Date</label>
                            <input type="datetime-local" id="flash_deal_end" name="flash_deal_end" value="<?php echo htmlspecialchars($_POST['flash_deal_end'] ?? ''); ?>" 
                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                        </div>
                    </div>
                </div>
                
                <!-- Submit Buttons -->
                <div class="flex justify-between items-center pt-6">
                    <a href="products.php" class="bg-gray-500 text-white px-6 py-2 rounded-lg hover:bg-gray-600 transition duration-200">
                        Cancel
                    </a>
                    
                    <button type="submit" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-200">
                        Add Product
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        function toggleFlashDealEnd() {
            const checkbox = document.getElementById('is_flash_deal');
            const container = document.getElementById('flash_deal_end_container');
            container.style.display = checkbox.checked ? 'block' : 'none';
        }
        
        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            toggleFlashDealEnd();
        });
    </script>
</body>
</html>