<?php
require_once './config/config.php';
require_once './includes/product.php';

$product_manager = new ProductManager();

// Get parameters
$category_id = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

// Get products and categories
$products = $product_manager->get_products($limit, $offset, $category_id, $search);
$categories = $product_manager->get_categories();
$flash_deals = $product_manager->get_flash_deals(6);

$page_title = 'Products';
if ($search) {
    $page_title = "Search results for: " . htmlspecialchars($search);
} elseif ($category_id) {
    $category = array_filter($categories, fn($c) => $c['id'] == $category_id);
    if ($category) {
        $page_title = reset($category)['name'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> - <?php echo SITE_NAME; ?></title>
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
                    },
                    fontFamily: {
                        'sans': ['Inter', 'system-ui', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="flex items-center">
                        <div class="w-8 h-8 bg-accent rounded-lg flex items-center justify-center mr-2">
                            <span class="text-white font-bold">MN</span>
                        </div>
                        <span class="text-xl font-bold text-primary"><?php echo SITE_NAME; ?></span>
                    </a>
                    
                    <!-- Search Bar -->
                    <form method="GET" action="products.php" class="hidden md:flex items-center">
                        <div class="relative">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                   placeholder="Search products, brands, and more..." 
                                   class="w-96 px-4 py-2 border-2 border-gray-200 rounded-l-lg focus:outline-none focus:border-accent">
                            <button type="submit" class="px-6 py-2 bg-accent text-white rounded-r-lg hover:bg-blue-600 transition duration-200">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <?php if (is_logged_in()): ?>
                        <a href="cart.php" class="text-gray-700 hover:text-accent relative">
                            Cart
                            <span class="absolute -top-2 -right-2 bg-accent text-white text-xs rounded-full w-5 h-5 flex items-center justify-center" id="cart-count">0</span>
                        </a>
                        <a href="wishlist.php" class="text-gray-700 hover:text-accent">Wishlist</a>
                        <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                        <a href="logout.php" class="text-accent hover:text-blue-600">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-accent hover:text-blue-600">Login</a>
                        <a href="register.php" class="bg-accent text-white px-4 py-2 rounded-md hover:bg-blue-600 transition duration-200">Sign Up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Flash Deals Section -->
        <?php if (!empty($flash_deals) && !$search && !$category_id): ?>
            <div class="mb-8">
                <div class="bg-gradient-to-r from-red-500 to-orange-500 text-white rounded-lg p-6 mb-6">
                    <h2 class="text-2xl font-bold mb-2">Flash Deals - Limited Time!</h2>
                    <p class="text-red-100">Hurry up! These deals won't last long.</p>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                    <?php foreach ($flash_deals as $deal): ?>
                        <div class="bg-white rounded-lg shadow-md overflow-hidden border-2 border-red-200">
                            <div class="relative">
                                <?php if (!empty($deal['images'])): ?>
                                    <img src="<?php echo $deal['images'][0]; ?>" alt="<?php echo htmlspecialchars($deal['name']); ?>" 
                                         class="w-full h-32 object-cover">
                                <?php else: ?>
                                    <div class="w-full h-32 bg-gray-200 flex items-center justify-center">
                                        <span class="text-gray-400">No Image</span>
                                    </div>
                                <?php endif; ?>
                                
                                <div class="absolute top-2 left-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                    FLASH DEAL
                                </div>
                            </div>
                            
                            <div class="p-3">
                                <h3 class="font-medium text-sm text-gray-900 mb-1 line-clamp-2"><?php echo htmlspecialchars($deal['name']); ?></h3>
                                <div class="flex items-center space-x-2 mb-2">
                                    <span class="text-lg font-bold text-red-600">$<?php echo number_format($deal['sale_price'], 2); ?></span>
                                    <span class="text-sm text-gray-500 line-through">$<?php echo number_format($deal['price'], 2); ?></span>
                                </div>
                                
                                <div class="text-xs text-red-600 font-medium">
                                    Ends: <?php echo date('M j, g:i A', strtotime($deal['flash_deal_end'])); ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <div class="flex flex-col lg:flex-row gap-8">
            <!-- Sidebar -->
            <div class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-lg shadow-sm p-6">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">Categories</h3>
                    <ul class="space-y-2">
                        <li>
                            <a href="products.php" class="block text-gray-700 hover:text-orange-500 py-1 <?php echo !$category_id ? 'text-orange-500 font-medium' : ''; ?>">
                                All Products
                            </a>
                        </li>
                        <?php foreach ($categories as $category): ?>
                            <li>
                                <a href="products.php?category=<?php echo $category['id']; ?>" 
                                   class="block text-gray-700 hover:text-orange-500 py-1 <?php echo $category_id == $category['id'] ? 'text-orange-500 font-medium' : ''; ?>">
                                    <?php echo htmlspecialchars($category['name']); ?>
                                    <span class="text-sm text-gray-500">(<?php echo $category['product_count']; ?>)</span>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="flex-1">
                <div class="flex justify-between items-center mb-6">
                    <h1 class="text-2xl font-bold text-gray-900"><?php echo $page_title; ?></h1>
                    <div class="text-sm text-gray-500">
                        <?php echo count($products); ?> products found
                    </div>
                </div>
                
                <!-- Mobile Search -->
                <div class="md:hidden mb-6">
                    <form method="GET" action="products.php">
                        <div class="flex">
                            <input type="text" name="search" value="<?php echo htmlspecialchars($search ?? ''); ?>" 
                                   placeholder="Search products..." 
                                   class="flex-1 px-4 py-2 border border-gray-300 rounded-l-md focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                            <button type="submit" class="px-4 py-2 bg-orange-500 text-white rounded-r-md hover:bg-orange-600 transition duration-200">
                                Search
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- Products Grid -->
                <?php if (empty($products)): ?>
                    <div class="text-center py-12">
                        <div class="text-gray-400 text-6xl mb-4">📦</div>
                        <h3 class="text-xl font-medium text-gray-900 mb-2">No products found</h3>
                        <p class="text-gray-500">Try adjusting your search or browse different categories.</p>
                    </div>
                <?php else: ?>
                    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
                        <?php foreach ($products as $product): ?>
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-200">
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" class="block">
                                    <div class="relative">
                                        <?php if (!empty($product['images'])): ?>
                                            <img src="<?php echo $product['images'][0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                                 class="w-full h-48 object-cover">
                                        <?php else: ?>
                                            <div class="w-full h-48 bg-gray-200 flex items-center justify-center">
                                                <span class="text-gray-400">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['is_featured']): ?>
                                            <div class="absolute top-2 left-2 bg-orange-500 text-white text-xs px-2 py-1 rounded">
                                                Featured
                                            </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($product['sale_price']): ?>
                                            <div class="absolute top-2 right-2 bg-red-500 text-white text-xs px-2 py-1 rounded">
                                                Sale
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <div class="p-4">
                                        <div class="flex items-center mb-2">
                                            <span class="text-sm text-gray-600"><?php echo htmlspecialchars($product['business_name']); ?></span>
                                            <?php if ($product['is_verified']): ?>
                                                <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-0.5 rounded-full">Verified</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <h3 class="font-medium text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                                        
                                        <div class="flex items-center mb-2">
                                            <?php if ($product['avg_rating'] > 0): ?>
                                                <div class="flex items-center">
                                                    <span class="text-yellow-400">★</span>
                                                    <span class="text-sm text-gray-600 ml-1"><?php echo $product['avg_rating']; ?> (<?php echo $product['review_count']; ?>)</span>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-sm text-gray-500">No reviews yet</span>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="flex items-center justify-between">
                                            <div>
                                                <?php if ($product['sale_price']): ?>
                                                    <span class="text-lg font-bold text-red-600">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                                    <span class="text-sm text-gray-500 line-through ml-2">$<?php echo number_format($product['price'], 2); ?></span>
                                                <?php else: ?>
                                                    <span class="text-lg font-bold text-gray-900">$<?php echo number_format($product['price'], 2); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            
                                            <?php if ($product['stock_quantity'] > 0): ?>
                                                <span class="text-sm text-green-600">In Stock</span>
                                            <?php else: ?>
                                                <span class="text-sm text-red-600">Out of Stock</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <div class="mt-8 flex justify-center">
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            
                            <?php if (count($products) == $limit): ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" 
                                   class="px-4 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script>
        // Update cart count on page load
        if (<?php echo is_logged_in() ? 'true' : 'false'; ?>) {
            fetch('api/cart-count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                });
        }
    </script>
</body>
</html>
