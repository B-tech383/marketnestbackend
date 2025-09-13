<?php
require_once './config/config.php';
require_once './includes/wishlist.php';
require_once './includes/cart.php';

// Check if user is logged in
if (!is_logged_in()) {
    redirect('login.php');
}

$wishlist_manager = new WishlistManager();
$cart_manager = new CartManager();
$user_id = $_SESSION['user_id'];

// Handle remove from wishlist
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['remove_from_wishlist'])) {
    $product_id = $_POST['product_id'] ?? null;
    if ($product_id) {
        $result = $wishlist_manager->remove_from_wishlist($user_id, $product_id);
        $message = $result['message'];
    }
}

// Handle add to cart from wishlist
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    $product_id = $_POST['product_id'] ?? null;
    $quantity = max(1, $_POST['quantity'] ?? 1);
    
    if ($product_id) {
        $result = $cart_manager->add_to_cart($user_id, $product_id, $quantity);
        $message = $result['message'];
    }
}

// Get wishlist items
$wishlist_items = $wishlist_manager->get_wishlist($user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - My Wishlist</title>
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
        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
    </style>
</head>
<body class="bg-gray-50 font-sans">
    <!-- Header -->
    <header class="bg-white shadow-sm sticky top-0 z-50 border-b">
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
                
                <!-- Navigation -->
                <nav class="flex items-center space-x-6">
                    <a href="index.php" class="text-gray-700 hover:text-accent">Home</a>
                    <a href="products.php" class="text-gray-700 hover:text-accent">Products</a>
                    <a href="cart.php" class="text-gray-700 hover:text-accent">Cart</a>
                    <a href="wishlist.php" class="text-accent font-semibold">Wishlist</a>
                    <a href="user/dashboard.php" class="text-gray-700 hover:text-accent">Dashboard</a>
                    <a href="logout.php" class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">My Wishlist</h1>
            <p class="text-gray-600">Your saved items for later purchase</p>
        </div>

        <!-- Message Display -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <p class="text-blue-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <!-- Wishlist Items -->
        <?php if (empty($wishlist_items)): ?>
            <div class="text-center py-12">
                <div class="w-24 h-24 bg-gray-100 rounded-full flex items-center justify-center mx-auto mb-4">
                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"></path>
                    </svg>
                </div>
                <h3 class="text-xl font-semibold text-gray-900 mb-2">Your wishlist is empty</h3>
                <p class="text-gray-600 mb-6">Start adding items you love to your wishlist!</p>
                <a href="products.php" class="bg-accent text-white px-6 py-3 rounded-lg hover:bg-blue-600 transition-colors font-medium">
                    Browse Products
                </a>
            </div>
        <?php else: ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                <?php foreach ($wishlist_items as $item): ?>
                    <div class="bg-white rounded-xl shadow-sm hover:shadow-lg transition-all duration-300 overflow-hidden border group">
                        <div class="relative">
                            <img src="<?php echo htmlspecialchars((!empty($item['images']) && is_array($item['images'])) ? $item['images'][0] : 'https://via.placeholder.com/300x300?text=Product'); ?>" 
                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                 class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-300">
                            
                            <!-- Remove from wishlist button -->
                            <form method="POST" class="absolute top-2 right-2">
                                <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_from_wishlist" 
                                        class="bg-red-500 text-white p-2 rounded-full hover:bg-red-600 transition-colors"
                                        onclick="return confirm('Remove from wishlist?')">
                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        
                        <div class="p-4">
                            <h3 class="font-medium text-gray-900 mb-2 text-sm leading-tight"><?php echo htmlspecialchars($item['name']); ?></h3>
                            <p class="text-gray-500 text-xs mb-3 line-clamp-2"><?php echo htmlspecialchars(substr($item['description'], 0, 60)); ?>...</p>
                            
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex flex-col">
                                    <?php if ($item['sale_price'] && $item['sale_price'] < $item['price']): ?>
                                        <span class="text-lg font-bold text-gray-900">$<?php echo number_format($item['sale_price'], 2); ?></span>
                                        <span class="text-sm text-red-500 line-through">$<?php echo number_format($item['price'], 2); ?></span>
                                    <?php else: ?>
                                        <span class="text-lg font-bold text-gray-900">$<?php echo number_format($item['price'], 2); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="text-xs text-gray-500">
                                    Added <?php echo date('M j, Y', strtotime($item['added_at'])); ?>
                                </div>
                            </div>
                            
                            <div class="flex space-x-2">
                                <a href="product-detail.php?id=<?php echo $item['id']; ?>" 
                                   class="flex-1 bg-gray-100 text-gray-700 px-3 py-2 rounded-lg text-sm hover:bg-gray-200 transition-colors text-center">
                                    View Details
                                </a>
                                
                                <form method="POST" class="flex-1">
                                    <input type="hidden" name="product_id" value="<?php echo $item['id']; ?>">
                                    <input type="hidden" name="quantity" value="1">
                                    <button type="submit" name="add_to_cart" 
                                            class="w-full bg-accent text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-600 transition-colors">
                                        Add to Cart
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Wishlist Summary -->
            <div class="mt-8 bg-white rounded-lg p-6 border">
                <div class="flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Wishlist Summary</h3>
                        <p class="text-gray-600"><?php echo count($wishlist_items); ?> item(s) in your wishlist</p>
                    </div>
                    <div class="flex space-x-4">
                        <a href="products.php" class="bg-gray-100 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-200 transition-colors">
                            Continue Shopping
                        </a>
                        <a href="cart.php" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                            View Cart
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white mt-16">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="text-center">
                <p class="text-gray-400">&copy; 2024 <?php echo SITE_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>
</body>
</html>

