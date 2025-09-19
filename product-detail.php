<?php
require_once './config/config.php';
require_once './includes/product.php';
require_once './includes/cart.php';
require_once './includes/wishlist.php';

$product_id = $_GET['id'] ?? null;
if (!$product_id) {
    redirect('products.php');
}

$product_manager = new ProductManager();
$cart_manager = new CartManager();
$wishlist_manager = new WishlistManager();

$product = $product_manager->get_product_by_id($product_id);
if (!$product) {
    redirect('products.php');
}

// Add to recently viewed
if (is_logged_in()) {
    $product_manager->add_to_recently_viewed($_SESSION['user_id'], $product_id);
}

// Generate CSRF token if not set
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_csrf_token();
}

// Handle add to cart
$message = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_to_cart'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $quantity = max(1, intval($_POST['quantity'] ?? 1));
        $result = $cart_manager->add_to_cart($_SESSION['user_id'], $product_id, $quantity);
        $message = $result['message'];
    }
}

// Handle wishlist toggle
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['toggle_wishlist'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $is_in_wishlist = $wishlist_manager->is_in_wishlist($_SESSION['user_id'], $product_id);
        
        if ($is_in_wishlist) {
            $result = $wishlist_manager->remove_from_wishlist($_SESSION['user_id'], $product_id);
        } else {
            $result = $wishlist_manager->add_to_wishlist($_SESSION['user_id'], $product_id);
        }
        
        $message = $result['message'];
    }
}

// Handle review submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_review'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    $rating = (int)($_POST['rating'] ?? 0);
    $title = trim($_POST['review_title'] ?? '');
    $comment = trim($_POST['review_comment'] ?? '');
    $order_id = (int)($_POST['order_id'] ?? 0);
    
    // Validate review data
    if ($rating < 1 || $rating > 5) {
        $message = 'Please select a valid rating (1-5 stars)';
    } elseif (empty($comment)) {
        $message = 'Please write a review comment';
    } elseif (empty($title)) {
        $message = 'Please provide a review title';
    } else {
        // Check if user has purchased this product or is admin
        $is_admin = (get_user_role() === 'admin');
        if (!$is_admin && !$product_manager->hasUserPurchasedProduct($_SESSION['user_id'], $product_id)) {
            $message = 'You can only review products you have purchased';
        } else {
            // For admins, use order_id = NULL (no specific order)
            $review_order_id = $is_admin ? null : (empty($order_id) ? null : $order_id);
            $result = $product_manager->add_review($_SESSION['user_id'], $product_id, $review_order_id, $rating, $title, $comment);
            $message = $result['message'];
            
            if ($result['success']) {
                // Refresh reviews after successful submission
                $reviews = $product_manager->get_product_reviews($product_id, 5);
            }
        }
    }
}

// Get reviews (only if not already fetched after form submission)
if (!isset($reviews)) {
    $reviews = $product_manager->get_product_reviews($product_id, 5);
}


// Check if in wishlist
$is_in_wishlist = is_logged_in() ? $wishlist_manager->is_in_wishlist($_SESSION['user_id'], $product_id) : false;

// Check if user can review (has purchased the product or is admin)
$can_review = false;
$user_orders = [];
$is_admin = false;
if (is_logged_in()) {
    $is_admin = (get_user_role() === 'admin');
    $can_review = $is_admin || $product_manager->hasUserPurchasedProduct($_SESSION['user_id'], $product_id);
    if ($can_review && !$is_admin) {
        $user_orders = $product_manager->getUserOrdersForProduct($_SESSION['user_id'], $product_id);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
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
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center space-x-4">
                    <a href="index.php" class="text-2xl font-bold text-accent"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <a href="products.php" class="text-gray-700 hover:text-accent">Products</a>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <?php if (is_logged_in()): ?>
                        <a href="cart.php" class="text-gray-700 hover:text-accent">Cart</a>
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
        <?php if ($message): ?>
            <div class="mb-6 p-4 <?php echo strpos($message, 'successfully') !== false ? 'bg-green-50 border border-green-200 text-green-800' : 'bg-red-50 border border-red-200 text-red-800'; ?> rounded-lg">
                <p><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
            <!-- Product Images -->
            <div>
                <?php if (!empty($product['images'])): ?>
                    <div class="mb-4">
                        <img src="<?php echo $product['images'][0]; ?>" alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="w-full h-96 object-cover rounded-lg shadow-md" id="main-image">
                    </div>
                    
                    <?php if (count($product['images']) > 1): ?>
                        <div class="grid grid-cols-4 gap-2">
                            <?php foreach ($product['images'] as $index => $image): ?>
                                <img src="<?php echo $image; ?>" alt="Product image <?php echo $index + 1; ?>" 
                                     class="w-full h-20 object-cover rounded cursor-pointer hover:opacity-75 transition duration-200 <?php echo $index === 0 ? 'ring-2 ring-accent' : ''; ?>"
                                     onclick="changeMainImage('<?php echo $image; ?>', this)">
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="w-full h-96 bg-gray-200 rounded-lg flex items-center justify-center">
                        <span class="text-gray-400 text-xl">No Image Available</span>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Product Info -->
            <div>
            <div class="flex items-center mb-4">
                <div class="flex items-center">
                    <?php if (!empty($product['vendor_logo'])): ?>
                        <img src="<?php echo htmlspecialchars($product['vendor_logo']); ?>" alt="Vendor logo" class="w-8 h-8 rounded-full mr-2">
                    <?php else: ?>
                        <img src="uploads/default-logo.png" alt="Default logo" class="w-8 h-8 rounded-full mr-2">
                    <?php endif; ?>

                    <span class="text-gray-600">
                        <?php echo htmlspecialchars($product['business_name'] ?? 'Unknown Vendor'); ?>
                    </span>

                    <?php if (!empty($product['is_verified']) && $product['is_verified'] == 1): ?>
                        <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded-full">
                            <?php echo $product['verification_badge'] ?? 'Verified'; ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

                
                <h1 class="text-3xl font-bold text-gray-900 mb-4"><?php echo htmlspecialchars($product['name']); ?></h1>
                
                <div class="flex items-center mb-4">
                    <?php if ($product['avg_rating'] > 0): ?>
                        <div class="flex items-center">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <span class="text-yellow-400 text-lg">
                                    <?php echo $i <= $product['avg_rating'] ? '★' : '☆'; ?>
                                </span>
                            <?php endfor; ?>
                            <span class="text-gray-600 ml-2"><?php echo $product['avg_rating']; ?> (<?php echo $product['review_count']; ?> reviews)</span>
                        </div>
                    <?php else: ?>
                        <span class="text-gray-500">No reviews yet</span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-6">
                    <?php if ($product['sale_price']): ?>
                        <div class="flex items-center space-x-4">
                            <span class="text-3xl font-bold text-red-600"><?php echo format_currency($product['sale_price']); ?></span>
                            <span class="text-xl text-gray-500 line-through"><?php echo format_currency($product['price']); ?></span>
                            <span class="bg-red-100 text-red-800 text-sm px-2 py-1 rounded">
                                Save <?php echo format_currency($product['price'] - $product['sale_price']); ?>
                            </span>
                        </div>
                    <?php else: ?>
                            <span class="text-3xl font-bold text-gray-900"><?php echo format_currency($product['price']); ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="mb-6">
                    <p class="text-gray-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                </div>
                
                <div class="mb-6">
                    <span class="text-sm font-medium text-gray-700">Category: </span>
                    <span class="text-sm text-gray-600"><?php echo htmlspecialchars($product['category_name']); ?></span>
                </div>
                
                <div class="mb-6">
                    <?php if ($product['stock_quantity'] > 0): ?>
                        <span class="text-green-600 font-medium">✓ In Stock (<?php echo $product['stock_quantity']; ?> available)</span>
                    <?php else: ?>
                        <span class="text-red-600 font-medium">✗ Out of Stock</span>
                    <?php endif; ?>
                </div>
                
                <?php if ($product['stock_quantity'] > 0): ?>
                    <form method="POST" class="mb-6">
                        <div class="flex items-center space-x-4 mb-4">
                            <label for="quantity" class="text-sm font-medium text-gray-700">Quantity:</label>
                            <select name="quantity" id="quantity" class="border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-accent focus:border-accent">
                                <?php for ($i = 1; $i <= min(10, $product['stock_quantity']); $i++): ?>
                                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        
                        <div class="flex space-x-4">
                            <form action="" method="post" class="flex gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <button type="submit" name="add_to_cart" 
                                    class="flex-1 bg-accent text-white px-6 py-3 rounded-md font-medium hover:bg-blue-600 transition duration-200">
                                    Add to Cart
                                </button>
                            </form>

                            <form action="" method="post" class="flex gap-4">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                                <button type="submit" name="toggle_wishlist" 
                                    class="px-6 py-3 border border-gray-300 rounded-md bg-red-300 hover:bg-red-600 transition duration-200">
                                    <?php echo $is_in_wishlist ? '❤️ Remove from Wishlist' : '❤️ Add to Wishlist'; ?>
                                </button>
                            </form>

                        </div>
                    </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Reviews Section -->
        <div class="bg-white rounded-lg shadow-sm p-6">
            <h2 class="text-2xl font-bold text-gray-900 mb-6">Customer Reviews</h2>
            
            <!-- Review Form -->
            <?php if ($can_review): ?>
                <div class="mb-8 p-6 bg-gray-50 rounded-lg">
                    <h3 class="text-lg font-semibold text-gray-900 mb-4">
                        Write a Review
                        <?php if ($is_admin): ?>
                            <span class="text-sm font-normal text-blue-600">(Admin Review)</span>
                        <?php endif; ?>
                    </h3>
                    <form method="POST">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <?php if (!$is_admin): ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Order Number</label>
                                    <select name="order_id" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent" required>
                                        <option value="">Select your order</option>
                                        <?php foreach ($user_orders as $order): ?>
                                            <option value="<?php echo $order['id']; ?>">
                                                <?php echo $order['order_number']; ?> - <?php echo date('M j, Y', strtotime($order['created_at'])); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            <?php else: ?>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Admin Review</label>
                                    <div class="p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <p class="text-sm text-blue-800">As an admin, you can review any product without a purchase requirement.</p>
                                    </div>
                                    <input type="hidden" name="order_id" value="">
                                </div>
                            <?php endif; ?>
                            
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">Rating *</label>
                                <div class="flex items-center space-x-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                        <input type="radio" name="rating" value="<?php echo $i; ?>" id="rating_<?php echo $i; ?>" class="sr-only" required>
                                        <label for="rating_<?php echo $i; ?>" class="text-2xl cursor-pointer hover:text-yellow-400 transition-colors">
                                            <span class="rating-star">☆</span>
                                        </label>
                                    <?php endfor; ?>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Review Title *</label>
                            <input type="text" name="review_title" placeholder="Summarize your review in a few words" 
                                   class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent" required>
                        </div>
                        
                        <div class="mt-4">
                            <label class="block text-sm font-medium text-gray-700 mb-2">Your Review *</label>
                            <textarea name="review_comment" rows="4" placeholder="Tell others about your experience with this product" 
                                      class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent" required></textarea>
                        </div>
                        
                        <div class="mt-6">
                            <button type="submit" name="submit_review" 
                                    class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition-colors">
                                Submit Review
                            </button>
                        </div>
                    </form>
                </div>
            <?php elseif (is_logged_in()): ?>
                <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                    <p class="text-yellow-800">You can only review products you have purchased. <a href="order-history.php" class="underline hover:text-yellow-900">View your orders</a></p>
                </div>
            <?php else: ?>
                <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                    <p class="text-blue-800">Please <a href="login.php" class="underline hover:text-blue-900">sign in</a> to write a review.</p>
                </div>
            <?php endif; ?>
            
            <!-- Existing Reviews -->
            <?php if (empty($reviews)): ?>
                <p class="text-gray-500">No reviews yet. Be the first to review this product!</p>
            <?php else: ?>
                <div class="space-y-6">
                    <?php foreach ($reviews as $review): ?>
                        <div class="border-b border-gray-200 pb-6 last:border-b-0">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center">
                                    <span class="font-medium text-gray-900"><?php echo htmlspecialchars($review['first_name'] . ' ' . $review['last_name']); ?></span>
                                    <?php if ($review['role'] === 'admin'): ?>
                                        <span class="ml-2 text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded">Admin Review</span>
                                    <?php elseif ($review['is_verified_purchase']): ?>
                                        <span class="ml-2 text-xs bg-green-100 text-green-800 px-2 py-1 rounded">Verified Purchase</span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($review['created_at'])); ?></span>
                            </div>
                            
                            <div class="flex items-center mb-2">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <span class="text-yellow-400">
                                        <?php echo $i <= $review['rating'] ? '★' : '☆'; ?>
                                    </span>
                                <?php endfor; ?>
                            </div>
                            
                            <?php if ($review['title']): ?>
                                <h4 class="font-medium text-gray-900 mb-2"><?php echo htmlspecialchars($review['title']); ?></h4>
                            <?php endif; ?>
                            
                            <p class="text-gray-700"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        function changeMainImage(src, element) {
            document.getElementById('main-image').src = src;
            
            // Remove ring from all thumbnails
            document.querySelectorAll('.grid img').forEach(img => {
                img.classList.remove('ring-2', 'ring-accent');
            });
            
            // Add ring to clicked thumbnail
            element.classList.add('ring-2', 'ring-accent');
        }
        
        // Star rating functionality
        document.addEventListener('DOMContentLoaded', function() {
            const ratingInputs = document.querySelectorAll('input[name="rating"]');
            const ratingStars = document.querySelectorAll('.rating-star');
            
            ratingInputs.forEach((input, index) => {
                input.addEventListener('change', function() {
                    updateStarDisplay(parseInt(this.value));
                });
            });
            
            ratingStars.forEach((star, index) => {
                star.addEventListener('click', function() {
                    const rating = index + 1;
                    document.querySelector(`input[name="rating"][value="${rating}"]`).checked = true;
                    updateStarDisplay(rating);
                });
                
                star.addEventListener('mouseover', function() {
                    const rating = index + 1;
                    updateStarDisplay(rating);
                });
            });
            
            // Reset stars on mouse leave
            const ratingContainer = document.querySelector('.flex.items-center.space-x-1');
            if (ratingContainer) {
                ratingContainer.addEventListener('mouseleave', function() {
                    const checkedInput = document.querySelector('input[name="rating"]:checked');
                    if (checkedInput) {
                        updateStarDisplay(parseInt(checkedInput.value));
                    } else {
                        updateStarDisplay(0);
                    }
                });
            }
        });
        
        function updateStarDisplay(rating) {
            const stars = document.querySelectorAll('.rating-star');
            stars.forEach((star, index) => {
                if (index < rating) {
                    star.textContent = '★';
                    star.classList.add('text-yellow-400');
                } else {
                    star.textContent = '☆';
                    star.classList.remove('text-yellow-400');
                }
            });
        }
    </script>
</body>
</html>
