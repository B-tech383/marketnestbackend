<?php
require_once './config/config.php';
require_once './includes/product.php';

$productManager = new ProductManager();

// Get featured products and categories
$featuredProducts = $productManager->getFeaturedProducts(12);
$categories = $productManager->getCategories();
$flashDeals = $productManager->getFlashDeals(6);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Your Premier Marketplace Destination</title>
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
    <style>
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(-20px) rotate(120deg); }
            66% { transform: translateY(10px) rotate(240deg); }
        }
        @keyframes float-reverse {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            33% { transform: translateY(15px) rotate(-90deg); }
            66% { transform: translateY(-25px) rotate(-180deg); }
        }
        @keyframes spin-slow {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-float { animation: float 6s ease-in-out infinite; }
        .animate-float-reverse { animation: float-reverse 8s ease-in-out infinite; }
        .animate-spin-slow { animation: spin-slow 20s linear infinite; }
        .floating-element {
            backdrop-filter: blur(10px);
            box-shadow: 0 8px 32px 0 rgba(255, 255, 255, 0.1);
        }
        .card-3d {
            transform-style: preserve-3d;
            transition: transform 0.3s ease;
        }
        .card-3d:hover {
            transform: rotateY(5deg) rotateX(5deg) translateZ(20px);
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Professional Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50 border-b">
        <!-- Top Bar -->
        <div class="bg-primary text-white text-sm">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-2">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-6">
                        <span>📍 Free shipping on orders over $50</span>
                        <span>📞 Customer Service: 1-800-MARKET-NEST</span>
                    </div>
                    <div class="flex items-center space-x-4">
                        <?php if (!is_logged_in()): ?>
                            <a href="login.php" class="hover:text-gray-300">Sign In</a>
                            <span class="text-gray-400">|</span>
                            <a href="register.php" class="hover:text-gray-300">Create Account</a>
                        <?php else: ?>
                            <span>Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                            <a href="logout.php" class="hover:text-gray-300">Sign Out</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Header -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <!-- Logo -->
                <div class="flex items-center">
                    <a href="index.php" class="flex items-center">
                        <div class="w-10 h-10 bg-accent rounded-lg flex items-center justify-center mr-3">
                            <span class="text-white font-bold text-lg">MN</span>
                        </div>
                        <span class="text-2xl font-bold text-primary"><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                
                <!-- Search Bar -->
                <div class="flex-1 max-w-2xl mx-8">
                    <form action="products.php" method="GET" class="relative">
                        <div class="flex">
                            <input type="text" name="search" placeholder="Search products, brands, and more..." 
                                   class="flex-1 px-4 py-3 border-2 border-gray-200 rounded-l-lg focus:outline-none focus:border-accent text-gray-700">
                            <button type="submit" class="bg-accent hover:bg-blue-600 text-white px-6 py-3 rounded-r-lg transition-colors">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </button>
                        </div>
                    </form>
                </div>
                
                <!-- User Actions -->
                <div class="flex items-center space-x-6">
                    <?php if (is_logged_in()): ?>
                        <?php if (get_user_role() === 'customer'): ?>
                            <a href="cart.php" class="flex items-center text-gray-700 hover:text-accent relative">
                                <svg class="w-6 h-6 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-2.5 5M7 13l2.5 5m6-5v5a2 2 0 01-2 2H9a2 2 0 01-2-2v-5m6-5V6a2 2 0 00-2-2H9a2 2 0 00-2 2v3"></path>
                                </svg>
                                <span class="font-medium">Cart</span>
                                <span id="cart-count" class="absolute -top-2 -right-2 bg-warning text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">0</span>
                            </a>
                        <?php endif; ?>
                        
                        <div class="relative group">
                            <button class="flex items-center text-gray-700 hover:text-accent">
                                <svg class="w-6 h-6 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                </svg>
                                <span class="font-medium">Account</span>
                            </button>
                            <div class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-lg border opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200">
                                <div class="py-2">
                                    <?php if (get_user_role() === 'customer'): ?>
                                        <a href="user/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Dashboard</a>
                                    <?php elseif (get_user_role() === 'vendor'): ?>
                                        <a href="vendor/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Vendor Panel</a>
                                    <?php elseif (get_user_role() === 'admin'): ?>
                                        <a href="admin/dashboard.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Admin Panel</a>
                                    <?php endif; ?>
                                    <a href="order-history.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Orders</a>
                                    <a href="track.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Track Package</a>
                                    <hr class="my-1">
                                    <a href="logout.php" class="block px-4 py-2 text-gray-700 hover:bg-gray-50">Sign Out</a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="track.php" class="flex items-center text-gray-700 hover:text-accent">
                            <svg class="w-6 h-6 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path>
                            </svg>
                            <span class="font-medium">Track</span>
                        </a>
                        <a href="login.php" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors font-medium">Sign In</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Category Navigation -->
        <div class="border-t bg-gray-50">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <nav class="flex items-center space-x-8 py-3 overflow-x-auto">
                    <a href="products.php" class="whitespace-nowrap text-gray-700 hover:text-accent font-medium">All Products</a>
                    <?php foreach (array_slice($categories, 0, 8) as $category): ?>
                        <a href="products.php?category=<?php echo urlencode($category['name']); ?>" 
                           class="whitespace-nowrap text-gray-700 hover:text-accent flex items-center">
                            <span class="mr-2"><?php echo $category['icon'] ?? '📦'; ?></span>
                            <?php echo htmlspecialchars($category['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </nav>
            </div>
        </div>
    </header>

    <!-- Hero Section with 3D Animation Background -->
    <section class="relative bg-gradient-to-r from-accent to-blue-600 text-white overflow-hidden">
        <!-- 3D Floating Elements Animation -->
        <div class="absolute inset-0 overflow-hidden pointer-events-none">
            <div class="floating-element absolute top-10 left-10 w-16 h-16 bg-white bg-opacity-10 rounded-lg animate-float"></div>
            <div class="floating-element absolute top-20 right-20 w-12 h-12 bg-white bg-opacity-10 rounded-full animate-pulse"></div>
            <div class="floating-element absolute bottom-20 left-20 w-20 h-20 bg-white bg-opacity-10 rounded-lg animate-spin-slow"></div>
            <div class="floating-element absolute bottom-10 right-10 w-14 h-14 bg-white bg-opacity-10 rounded-full animate-float-reverse"></div>
            <div class="floating-element absolute top-1/2 left-1/4 w-8 h-8 bg-white bg-opacity-10 rounded-lg animate-bounce" style="animation-delay: 1s;"></div>
            <div class="floating-element absolute top-1/3 right-1/3 w-10 h-10 bg-white bg-opacity-10 rounded-full animate-float" style="animation-delay: 2s;"></div>
        </div>
        
        <!-- Gradient Overlay for depth -->
        <div class="absolute inset-0 bg-gradient-to-br from-transparent via-blue-500 to-accent opacity-30"></div>
        
        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12 items-center">
                <div>
                    <h1 class="text-5xl lg:text-6xl font-bold leading-tight mb-6">
                        Discover Everything You Need at 
                        <span class="text-yellow-300"><?php echo SITE_NAME; ?></span>
                    </h1>
                    <p class="text-xl text-blue-100 mb-8 leading-relaxed">
                        From daily essentials to unique finds - explore millions of products with fast delivery, secure payment, and advanced tracking.
                    </p>
                    <div class="flex flex-col sm:flex-row gap-4">
                        <a href="products.php" class="bg-white text-accent px-8 py-4 rounded-lg text-lg font-semibold hover:bg-gray-100 transition-colors shadow-lg">
                            Start Shopping
                        </a>
                        <a href="#categories" class="border-2 border-white text-white px-8 py-4 rounded-lg text-lg font-semibold hover:bg-white hover:text-accent transition-colors">
                            Browse Categories
                        </a>
                    </div>
                </div>
                <div class="relative">
                    <div class="bg-white/10 rounded-2xl p-8 backdrop-blur-sm">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="bg-white/20 rounded-lg p-4 text-center">
                                <div class="text-3xl mb-2">🚚</div>
                                <div class="font-semibold">Fast Delivery</div>
                            </div>
                            <div class="bg-white/20 rounded-lg p-4 text-center">
                                <div class="text-3xl mb-2">🔒</div>
                                <div class="font-semibold">Secure Payment</div>
                            </div>
                            <div class="bg-white/20 rounded-lg p-4 text-center">
                                <div class="text-3xl mb-2">📱</div>
                                <div class="font-semibold">Easy Tracking</div>
                            </div>
                            <div class="bg-white/20 rounded-lg p-4 text-center">
                                <div class="text-3xl mb-2">💯</div>
                                <div class="font-semibold">Quality Guarantee</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Flash Deals Section -->
    <?php if (!empty($flashDeals)): ?>
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">⚡ Flash Deals</h2>
                    <p class="text-gray-600">Limited time offers - grab them while you can!</p>
                </div>
                <a href="products.php?deals=1" class="text-accent hover:text-blue-600 font-semibold">View All Deals →</a>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4">
                <?php foreach ($flashDeals as $product): ?>
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border group card-3d">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/200x200?text=Product'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-40 object-cover group-hover:scale-105 transition-transform duration-300">
                            <div class="absolute top-2 left-2 bg-red-500 text-white px-2 py-1 rounded-md text-xs font-bold">
                                <?php echo $product['discount_percentage']; ?>% OFF
                            </div>
                        </div>
                        <div class="p-3">
                            <h3 class="font-medium text-gray-900 text-sm mb-2 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="text-lg font-bold text-gray-900">$<?php echo number_format($product['sale_price'], 2); ?></span>
                                    <span class="text-sm text-gray-500 line-through ml-1">$<?php echo number_format($product['price'], 2); ?></span>
                                </div>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="bg-accent text-white px-2 py-1 rounded text-xs hover:bg-blue-600 transition-colors">
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

    <!-- Categories Section -->
    <section id="categories" class="py-12 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold text-gray-900 mb-4">Shop by Category</h2>
                <p class="text-gray-600 max-w-2xl mx-auto">Find exactly what you're looking for in our carefully curated categories</p>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-6">
                <?php foreach ($categories as $category): ?>
                    <a href="products.php?category=<?php echo urlencode($category['name']); ?>" 
                       class="group bg-white rounded-xl p-6 text-center hover:shadow-lg transition-all duration-300 border hover:border-accent card-3d">
                        <div class="w-16 h-16 bg-gradient-to-br from-accent/10 to-blue-100 rounded-xl flex items-center justify-center mx-auto mb-4 group-hover:from-accent/20 group-hover:to-blue-200 transition-colors">
                            <span class="text-2xl"><?php echo $category['icon'] ?? '📦'; ?></span>
                        </div>
                        <h3 class="font-semibold text-gray-900 mb-1"><?php echo htmlspecialchars($category['name']); ?></h3>
                        <p class="text-sm text-gray-500"><?php echo $category['product_count']; ?> items</p>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Featured Products -->
    <section class="py-12 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-2">Featured Products</h2>
                    <p class="text-gray-600">Handpicked items just for you</p>
                </div>
                <a href="products.php" class="text-accent hover:text-blue-600 font-semibold">View All Products →</a>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 xl:grid-cols-6 gap-6">
                <?php foreach ($featuredProducts as $product): ?>
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border group card-3d">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars($product['image_url'] ?: 'https://via.placeholder.com/200x200?text=Product'); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                        </div>
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 mb-2 line-clamp-2"><?php echo htmlspecialchars($product['name']); ?></h3>
                            <p class="text-gray-500 text-sm mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($product['description'], 0, 80)); ?>...</p>
                            <div class="flex items-center justify-between">
                                <span class="text-xl font-bold text-gray-900">$<?php echo number_format($product['price'], 2); ?></span>
                                <a href="product-detail.php?id=<?php echo $product['id']; ?>" 
                                   class="bg-accent text-white px-4 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                                    View
                                </a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-16 bg-primary text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl font-bold mb-4">Why Choose Market Nest?</h2>
                <p class="text-lg text-gray-300">Experience the difference with our premium marketplace</p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div class="text-center group card-3d">
                    <div class="w-20 h-20 bg-accent rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Verified Quality</h3>
                    <p class="text-gray-300 leading-relaxed">Every product is carefully vetted by our quality assurance team to ensure you receive only the best.</p>
                </div>
                
                <div class="text-center group card-3d">
                    <div class="w-20 h-20 bg-success rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Lightning Fast Delivery</h3>
                    <p class="text-gray-300 leading-relaxed">Get your orders delivered in record time with our optimized logistics network and real-time tracking.</p>
                </div>
                
                <div class="text-center group card-3d">
                    <div class="w-20 h-20 bg-warning rounded-2xl flex items-center justify-center mx-auto mb-6 group-hover:scale-110 transition-transform duration-300">
                        <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold mb-3">Secure & Protected</h3>
                    <p class="text-gray-300 leading-relaxed">Shop with confidence knowing your data and transactions are protected by bank-grade security.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8 mb-8">
                <div>
                    <div class="flex items-center mb-4">
                        <div class="w-8 h-8 bg-accent rounded-lg flex items-center justify-center mr-2">
                            <span class="text-white font-bold">MN</span>
                        </div>
                        <span class="text-xl font-bold"><?php echo SITE_NAME; ?></span>
                    </div>
                    <p class="text-gray-400 mb-4 leading-relaxed">Your trusted marketplace for quality products, fast delivery, and exceptional service.</p>
                    <div class="flex space-x-4">
                        <a href="#" class="text-gray-400 hover:text-accent transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M24 4.557c-.883.392-1.832.656-2.828.775 1.017-.609 1.798-1.574 2.165-2.724-.951.564-2.005.974-3.127 1.195-.897-.957-2.178-1.555-3.594-1.555-3.179 0-5.515 2.966-4.797 6.045-4.091-.205-7.719-2.165-10.148-5.144-1.29 2.213-.669 5.108 1.523 6.574-.806-.026-1.566-.247-2.229-.616-.054 2.281 1.581 4.415 3.949 4.89-.693.188-1.452.232-2.224.084.626 1.956 2.444 3.379 4.6 3.419-2.07 1.623-4.678 2.348-7.29 2.04 2.179 1.397 4.768 2.212 7.548 2.212 9.142 0 14.307-7.721 13.995-14.646.962-.695 1.797-1.562 2.457-2.549z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-accent transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M22.46 6c-.77.35-1.6.58-2.46.69.88-.53 1.56-1.37 1.88-2.38-.83.5-1.75.85-2.72 1.05C18.37 4.5 17.26 4 16 4c-2.35 0-4.27 1.92-4.27 4.29 0 .34.04.67.11.98C8.28 9.09 5.11 7.38 3 4.79c-.37.63-.58 1.37-.58 2.15 0 1.49.75 2.81 1.91 3.56-.71 0-1.37-.2-1.95-.5v.03c0 2.08 1.48 3.82 3.44 4.21a4.22 4.22 0 0 1-1.93.07 4.28 4.28 0 0 0 4 2.98 8.521 8.521 0 0 1-5.33 1.84c-.34 0-.68-.02-1.02-.06C3.44 20.29 5.7 21 8.12 21 16 21 20.33 14.46 20.33 8.79c0-.19 0-.37-.01-.56.84-.6 1.56-1.36 2.14-2.23z"/>
                            </svg>
                        </a>
                        <a href="#" class="text-gray-400 hover:text-accent transition-colors">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M12.017 0C5.396 0 .029 5.367.029 11.987c0 5.079 3.158 9.417 7.618 11.174-.105-.949-.199-2.403.041-3.439.219-.937 1.406-5.957 1.406-5.957s-.359-.72-.359-1.781c0-1.663.967-2.911 2.168-2.911 1.024 0 1.518.769 1.518 1.688 0 1.029-.653 2.567-.992 3.992-.285 1.193.6 2.165 1.775 2.165 2.128 0 3.768-2.245 3.768-5.487 0-2.861-2.063-4.869-5.008-4.869-3.41 0-5.409 2.562-5.409 5.199 0 1.033.394 2.143.889 2.741.099.12.112.225.085.345-.09.375-.293 1.199-.334 1.363-.053.225-.172.271-.402.165-1.495-.69-2.433-2.878-2.433-4.646 0-3.776 2.748-7.252 7.92-7.252 4.158 0 7.392 2.967 7.392 6.923 0 4.135-2.607 7.462-6.233 7.462-1.214 0-2.357-.629-2.754-1.378l-.748 2.853c-.271 1.043-1.002 2.35-1.492 3.146C9.57 23.812 10.763 24.009 12.017 24.009c6.624 0 11.99-5.367 11.99-11.988C24.007 5.367 18.641.001 12.017.001z"/>
                            </svg>
                        </a>
                    </div>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4 text-lg">Shop</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="products.php" class="hover:text-accent transition-colors">All Products</a></li>
                        <li><a href="products.php?deals=1" class="hover:text-accent transition-colors">Flash Deals</a></li>
                        <li><a href="products.php?featured=1" class="hover:text-accent transition-colors">Featured Items</a></li>
                        <li><a href="vendor-application.php" class="hover:text-accent transition-colors">Become a Seller</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4 text-lg">Customer Service</h4>
                    <ul class="space-y-3 text-gray-400">
                        <li><a href="track.php" class="hover:text-accent transition-colors">Track Your Order</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">Help Center</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">Returns & Refunds</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">Contact Support</a></li>
                    </ul>
                </div>
                
                <div>
                    <h4 class="font-semibold mb-4 text-lg">Account</h4>
                    <ul class="space-y-3 text-gray-400">
                        <?php if (is_logged_in()): ?>
                            <li><a href="user/dashboard.php" class="hover:text-accent transition-colors">My Account</a></li>
                            <li><a href="order-history.php" class="hover:text-accent transition-colors">Order History</a></li>
                        <?php else: ?>
                            <li><a href="login.php" class="hover:text-accent transition-colors">Sign In</a></li>
                            <li><a href="register.php" class="hover:text-accent transition-colors">Create Account</a></li>
                        <?php endif; ?>
                        <li><a href="#" class="hover:text-accent transition-colors">Privacy Policy</a></li>
                        <li><a href="#" class="hover:text-accent transition-colors">Terms of Service</a></li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 pt-8">
                <div class="flex flex-col md:flex-row justify-between items-center">
                    <p class="text-gray-400 mb-4 md:mb-0">&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
                    <div class="flex items-center space-x-6 text-sm text-gray-400">
                        <span>🔒 Secure Shopping</span>
                        <span>🚚 Fast Delivery</span>
                        <span>💳 Multiple Payment Options</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Scripts -->
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