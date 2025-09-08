<?php
require_once './config/config.php';
require_once './includes/product.php';

$productManager = new ProductManager();

// Get featured products and categories
$featuredProducts = $productManager->getFeaturedProducts(8);
$categories = $productManager->getCategories();
$flashDeals = $productManager->getFlashDeals(4);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Your Premier E-commerce Destination</title>
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
<body class="bg-white">
    <!-- Enhanced Header with Search and Cart -->
    <header class="bg-white shadow-sm border-b sticky top-0 z-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-8">
                    <a href="index.php" class="text-2xl font-bold text-primary"><?php echo SITE_NAME; ?></a>
                    
                    <!-- Search Bar -->
                    <div class="hidden md:block flex-1 max-w-lg">
                        <form action="products.php" method="GET" class="relative">
                            <input type="text" name="search" placeholder="Search products..." 
                                   class="w-full pl-4 pr-12 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <button type="submit" class="absolute right-2 top-1/2 transform -translate-y-1/2 text-gray-400 hover:text-primary">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </form>
                    </div>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="products.php" class="text-gray-700 hover:text-primary">Products</a>
                    <a href="track.php" class="text-gray-700 hover:text-primary">Track</a>
                    
                    <?php if (is_logged_in()): ?>
                        <?php if (get_user_role() === 'customer'): ?>
                            <a href="cart.php" class="relative text-gray-700 hover:text-primary">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v5a2 2 0 01-2 2H9a2 2 0 01-2-2v-5m6-5V6a2 2 0 00-2-2H9a2 2 0 00-2 2v3"></path>
                                </svg>
                                <span id="cart-count" class="absolute -top-2 -right-2 bg-primary text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                            </a>
                            <a href="user/dashboard.php" class="text-gray-700 hover:text-primary">Dashboard</a>
                        <?php elseif (get_user_role() === 'vendor'): ?>
                            <a href="vendor/dashboard.php" class="text-gray-700 hover:text-primary">Vendor Panel</a>
                        <?php elseif (get_user_role() === 'admin'): ?>
                            <a href="admin/dashboard.php" class="text-gray-700 hover:text-primary">Admin Panel</a>
                        <?php endif; ?>
                        <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                        <a href="logout.php" class="text-primary hover:text-orange-600">Logout</a>
                    <?php else: ?>
                        <a href="login.php" class="text-primary hover:text-orange-600">Login</a>
                        <a href="register.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-orange-600 transition duration-200">Sign Up</a>
                    <?php endif; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Enhanced Hero Section -->
    <section class="bg-gradient-to-br from-primary/5 via-orange-50 to-white py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h1 class="text-5xl font-bold text-gray-900 mb-6">Welcome to <?php echo SITE_NAME; ?></h1>
                <p class="text-xl text-gray-600 mb-8 max-w-3xl mx-auto">Your premier destination for quality products with advanced shipment tracking. Shop from verified vendors and track your orders with precision.</p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="products.php" class="bg-primary text-white px-8 py-4 rounded-lg text-lg font-medium hover:bg-orange-600 transition duration-200 shadow-lg">
                        Shop Now
                    </a>
                    <a href="track.php" class="border-2 border-primary text-primary px-8 py-4 rounded-lg text-lg font-medium hover:bg-primary hover:text-white transition duration-200">
                        Track Shipment
                    </a>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Shop by Category</h2>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach (array_slice($categories, 0, 6) as $category): ?>
                    <a href="products.php?category=<?php echo urlencode($category['name']); ?>" 
                       class="bg-white rounded-lg p-6 text-center hover:shadow-lg transition duration-200 group">
                        <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4 group-hover:bg-primary/20 transition">
                            <span class="text-2xl"><?php echo $category['icon'] ?? '📦'; ?></span>
                        </div>
                        <h3 class="font-semibold text-gray-900"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="text-sm text-gray-500 mt-1"><?php echo $category['product_count']; ?> items</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Flash Deals Section -->
    <?php if (!empty($flashDeals)): ?>
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Flash Deals</h2>
                <a href="products.php?deals=1" class="text-primary hover:text-orange-600 font-medium">View All Deals →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($flashDeals as $product): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-200">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?: '/placeholder.svg?height=200&width=300'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-48 object-cover">
                            <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded text-sm font-medium">
                                <?php echo $product['discount_percentage']; ?>% OFF
                            </div>
                        </div>
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-lg font-bold text-primary">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                    <span class="text-sm text-gray-500 line-through ml-2">$<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="bg-primary text-white px-3 py-1 rounded text-sm hover:bg-orange-600 transition">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Featured Products Section -->
    <section class="py-16 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Featured Products</h2>
                <a href="products.php" class="text-primary hover:text-orange-600 font-medium">View All Products →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition duration-200">
                        <img src="<?php echo htmlspecialchars($product['image_url'] ?: '/placeholder.svg?height=200&width=300'); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="w-full h-48 object-cover">
                        <div class="p-4">
                            <h3 class="font-semibold text-gray-900 mb-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-gray-600 text-sm mb-3"><?php echo htmlspecialchars(substr($product['description'], 0, 100)); ?>...</p>
                            <div class="flex items-center justify-between">
                                <span class="text-lg font-bold text-primary">$<?php echo number_format($product['price'], 2); ?></span>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="bg-primary text-white px-4 py-2 rounded hover:bg-orange-600 transition">
                                    View Details
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <h2 class="text-3xl font-bold text-gray-900 text-center mb-12">Why Choose Us?</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Quality Products</h3>
                    <p class="text-gray-600">Curated selection from verified vendors with quality guarantees</p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Advanced Tracking</h3>
                    <p class="text-gray-600">Real-time shipment tracking with detailed progress updates</p>
                </div>
                
                <div class="text-center p-6">
                    <div class="w-16 h-16 bg-primary/10 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-2">Secure Shopping</h3>
                    <p class="text-gray-600">Safe and secure transactions with buyer protection</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Enhanced Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold text-primary mb-4"><?php echo SITE_NAME; ?></h3>
                    <p class="text-gray-300 mb-4">Your premier e-commerce destination with advanced tracking and quality products.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-primary">Facebook</a>
                        <a href="#" class="text-gray-400 hover:text-primary">Twitter</a>
                        <a href="#" class="text-gray-400 hover:text-primary">Instagram</a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Shop</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="products.php" class="hover:text-primary">All Products</a></li>
                        <li><a href="products.php?deals=1" class="hover:text-primary">Flash Deals</a></li>
                        <li><a href="products.php?featured=1" class="hover:text-primary">Featured</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Account</h4>
                    <ul class="space-y-2 text-gray-300">
                        <?php if (is_logged_in()): ?>
                            <li><a href="user/dashboard.php" class="hover:text-primary">My Account</a></li>
                            <li><a href="order-history.php" class="hover:text-primary">Order History</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="hover:text-primary">Login</a></li>
                            <li><a href="register.php" class="hover:text-primary">Sign Up</a></li>
                        <?php endif; ?>
                        <li><a href="vendor-application.php" class="hover:text-primary">Become a Vendor</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4">Support</h4>
                    <ul class="space-y-2 text-gray-300">
                        <li><a href="track.php" class="hover:text-primary">Track Shipment</a></li>
                        <li><a href="#" class="hover:text-primary">Help Center</a></li>
                        <li><a href="#" class="hover:text-primary">Contact Us</a></li>
                        <li><a href="#" class="hover:text-primary">Returns</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center">
                <p class="text-gray-400">&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved. Built with advanced tracking technology.</p>
            </div>
        </div>
    </footer>

    <!-- Cart Count Script -->
    <script>
        // Update cart count on page load
        if (document.getElementById('cart-count')) {
            fetch('api/cart-count.php')
                .then(response => response.json())
                .then(data => {
                    document.getElementById('cart-count').textContent = data.count || 0;
                })
                .catch(error => console.error('Error fetching cart count:', error));
        }
    </script>
</body>
</html>
