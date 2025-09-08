<?php
require_once './config/config.php';
require_once './includes/tracking.php';

$tracking_manager = new TrackingManager();
$tracking_number = $_GET['tracking'] ?? $_POST['tracking_number'] ?? '';
$shipment = null;
$error = '';
$message = '';

// Handle tracking lookup
if ($tracking_number) {
    $user_id = is_logged_in() ? $_SESSION['user_id'] : null;
    $shipment = $tracking_manager->get_shipment_by_tracking($tracking_number, $user_id);
    
    if (!$shipment) {
        $error = 'Tracking number not found. Please check and try again.';
    } else {
        // Increment free tracking usage for logged-in users
        if ($user_id && $user_id == $shipment['user_id'] && $shipment['free_trackings_used'] < FREE_TRACKING_LIMIT) {
            $tracking_manager->increment_free_tracking_usage($user_id);
        }
    }
}

// Handle advanced tracking purchase
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['purchase_tracking'])) {
    if (!is_logged_in()) {
        redirect('login.php');
    }
    
    $tracking_level = $_POST['tracking_level'];
    $amount = $tracking_level === 'standard' ? STANDARD_TRACKING_PRICE : PREMIUM_TRACKING_PRICE;
    
    $result = $tracking_manager->purchase_advanced_tracking($_SESSION['user_id'], $shipment['id'], $tracking_level, $amount);
    $message = $result['message'];
    
    if ($result['success']) {
        // Refresh shipment data
        $shipment = $tracking_manager->get_shipment_by_tracking($tracking_number, $_SESSION['user_id']);
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track Your Shipment - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        'orange': {
                            500: '#f97316'
                        }
                    }
                }
            }
        }
    </script>
    <style>
        .video-background {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            object-fit: cover;
        }
        
        .overlay {
            background: rgba(249, 115, 22, 0.1);
            backdrop-filter: blur(1px);
        }
        
        .content-overlay {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
        }
        
        .progress-bar {
            transition: width 1s ease-in-out;
        }
        
        .tracking-step {
            transition: all 0.3s ease;
        }
        
        .tracking-step.completed {
            transform: scale(1.1);
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
        
        .pulse-animation {
            animation: pulse 2s infinite;
        }
    </style>
</head>
<body class="min-h-screen">
    <!-- 3D Video Background -->
    <video autoplay muted loop class="video-background">
        <source src="https://cdn.durable.co/getty-videos/11YFphDEbcgMokFBstfj1wUNbEiH67ztyxxEKzGe05GR5HPPHbhh2IegZrfKys3a.mov" type="video/mp4">
        Your browser does not support the video tag.
    </video>
    
    <!-- Overlay -->
    <div class="overlay min-h-screen">
        <!-- Header -->
        <header class="content-overlay border-b border-white/20">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center space-x-4">
                        <a href="index.php" class="text-2xl font-bold text-orange-500"><?php echo SITE_NAME; ?></a>
                        <span class="text-gray-400">|</span>
                        <span class="text-gray-700">Track Shipment</span>
                    </div>
                    
                    <nav class="flex items-center space-x-4">
                        <a href="products.php" class="text-gray-700 hover:text-orange-500">Shop</a>
                        <?php if (is_logged_in()): ?>
                            <a href="order-history.php" class="text-gray-700 hover:text-orange-500">Orders</a>
                            <span class="text-gray-700">Hi, <?php echo $_SESSION['first_name']; ?>!</span>
                            <a href="logout.php" class="text-orange-500 hover:text-orange-600">Logout</a>
                        <?php else: ?>
                            <a href="login.php" class="text-orange-500 hover:text-orange-600">Login</a>
                        <?php endif; ?>
                    </nav>
                </div>
            </div>
        </header>

        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <!-- Tracking Form -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-white mb-4 drop-shadow-lg">Track Your Shipment</h1>
                <p class="text-xl text-white/90 mb-8 drop-shadow">Enter your tracking number to get real-time updates</p>
                
                <form method="POST" class="max-w-md mx-auto">
                    <div class="flex">
                        <input type="text" name="tracking_number" value="<?php echo htmlspecialchars($tracking_number); ?>" 
                               placeholder="Enter tracking number..." required
                               class="flex-1 px-4 py-3 rounded-l-lg border-0 focus:outline-none focus:ring-2 focus:ring-orange-500 text-lg">
                        <button type="submit" 
                                class="px-6 py-3 bg-orange-500 text-white rounded-r-lg hover:bg-orange-600 transition duration-200 font-medium text-lg">
                            Track
                        </button>
                    </div>
                </form>
            </div>
            
            <?php if ($error): ?>
                <div class="content-overlay rounded-lg p-6 mb-8 border border-red-200">
                    <div class="text-center">
                        <div class="text-red-500 text-6xl mb-4">❌</div>
                        <h3 class="text-xl font-semibold text-red-700 mb-2">Tracking Number Not Found</h3>
                        <p class="text-red-600"><?php echo $error; ?></p>
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="content-overlay rounded-lg p-4 mb-8 border border-blue-200">
                    <p class="text-blue-700 text-center"><?php echo $message; ?></p>
                </div>
            <?php endif; ?>
            
            <?php if ($shipment): ?>
                <!-- Shipment Info -->
                <div class="content-overlay rounded-lg p-6 mb-8 border border-white/20">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Tracking Number</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $shipment['tracking_number']; ?></p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Order Number</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $shipment['order_number']; ?></p>
                        </div>
                        <div class="text-center">
                            <h3 class="text-sm font-medium text-gray-500 mb-1">Carrier</h3>
                            <p class="text-lg font-semibold text-gray-900"><?php echo $shipment['carrier']; ?></p>
                        </div>
                    </div>
                    
                    <!-- Progress Bar -->
                    <div class="mb-8">
                        <div class="flex justify-between text-sm text-gray-600 mb-2">
                            <span>Order Placed</span>
                            <span>Delivered</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-3">
                            <div class="bg-gradient-to-r from-orange-400 to-orange-600 h-3 rounded-full progress-bar" 
                                 style="width: <?php echo $tracking_manager->get_tracking_progress_percentage($shipment['status']); ?>%"></div>
                        </div>
                        <div class="text-center mt-2">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-orange-100 text-orange-800">
                                <?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Tracking Level Info -->
                <div class="content-overlay rounded-lg p-6 mb-8 border border-white/20">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-xl font-semibold text-gray-900">Tracking Details</h2>
                        <span class="px-3 py-1 text-sm font-semibold rounded-full 
                                   <?php echo $shipment['tracking_level'] === 'basic' ? 'bg-gray-100 text-gray-800' : 
                                             ($shipment['tracking_level'] === 'standard' ? 'bg-blue-100 text-blue-800' : 'bg-purple-100 text-purple-800'); ?>">
                            <?php echo ucfirst($shipment['tracking_level']); ?> Tracking
                        </span>
                    </div>
                    
                    <?php if ($shipment['tracking_level'] === 'basic'): ?>
                        <!-- Basic Tracking -->
                        <div class="space-y-4">
                            <div class="text-center py-8">
                                <div class="text-4xl mb-4">📦</div>
                                <h3 class="text-lg font-medium text-gray-900 mb-2">Basic Tracking</h3>
                                <p class="text-gray-600 mb-4">Current Status: <strong><?php echo ucfirst(str_replace('_', ' ', $shipment['status'])); ?></strong></p>
                                
                                <?php if (is_logged_in() && $_SESSION['user_id'] == $shipment['user_id'] && $shipment['free_trackings_used'] >= FREE_TRACKING_LIMIT): ?>
                                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4 mb-4">
                                        <p class="text-yellow-800 text-sm">
                                            You've used your <?php echo FREE_TRACKING_LIMIT; ?> free trackings. Upgrade for detailed location and ETA information.
                                        </p>
                                    </div>
                                    
                                    <!-- Upgrade Options -->
                                    <form method="POST" class="space-y-4">
                                        <input type="hidden" name="tracking_number" value="<?php echo $tracking_number; ?>">
                                        
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="border border-blue-200 rounded-lg p-4 hover:bg-blue-50 transition duration-200">
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="tracking_level" value="standard" required class="sr-only">
                                                    <div class="text-center">
                                                        <h4 class="font-semibold text-blue-800 mb-2">Standard Tracking</h4>
                                                        <p class="text-2xl font-bold text-blue-600 mb-2">$<?php echo number_format(STANDARD_TRACKING_PRICE, 2); ?></p>
                                                        <ul class="text-sm text-blue-700 space-y-1">
                                                            <li>✓ Current status</li>
                                                            <li>✓ Current city</li>
                                                            <li>✓ Estimated delivery</li>
                                                        </ul>
                                                    </div>
                                                </label>
                                            </div>
                                            
                                            <div class="border border-purple-200 rounded-lg p-4 hover:bg-purple-50 transition duration-200">
                                                <label class="cursor-pointer">
                                                    <input type="radio" name="tracking_level" value="premium" required class="sr-only">
                                                    <div class="text-center">
                                                        <h4 class="font-semibold text-purple-800 mb-2">Premium Tracking</h4>
                                                        <p class="text-2xl font-bold text-purple-600 mb-2">$<?php echo number_format(PREMIUM_TRACKING_PRICE, 2); ?></p>
                                                        <ul class="text-sm text-purple-700 space-y-1">
                                                            <li>✓ Everything in Standard</li>
                                                            <li>✓ Exact location</li>
                                                            <li>✓ Live updates</li>
                                                            <li>✓ SMS notifications</li>
                                                        </ul>
                                                    </div>
                                                </label>
                                            </div>
                                        </div>
                                        
                                        <button type="submit" name="purchase_tracking" 
                                                class="w-full bg-orange-500 text-white py-3 px-4 rounded-lg font-medium hover:bg-orange-600 transition duration-200">
                                            Upgrade Tracking
                                        </button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Advanced Tracking -->
                        <div class="space-y-6">
                            <!-- Current Location -->
                            <?php if ($shipment['current_location']): ?>
                                <div class="text-center py-4">
                                    <div class="text-4xl mb-2 pulse-animation">📍</div>
                                    <h3 class="text-lg font-medium text-gray-900">Current Location</h3>
                                    <p class="text-xl font-semibold text-orange-600"><?php echo htmlspecialchars($shipment['current_location']); ?></p>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Estimated Delivery -->
                            <?php if ($shipment['estimated_delivery']): ?>
                                <div class="bg-green-50 border border-green-200 rounded-lg p-4 text-center">
                                    <h4 class="font-medium text-green-800 mb-1">Estimated Delivery</h4>
                                    <p class="text-lg font-semibold text-green-700">
                                        <?php echo date('M j, Y g:i A', strtotime($shipment['estimated_delivery'])); ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- Tracking History -->
                <div class="content-overlay rounded-lg p-6 border border-white/20">
                    <h2 class="text-xl font-semibold text-gray-900 mb-6">Tracking History</h2>
                    
                    <div class="space-y-4">
                        <?php foreach (array_reverse($shipment['history']) as $index => $event): ?>
                            <div class="flex items-start space-x-4 tracking-step <?php echo $index === 0 ? 'completed' : ''; ?>">
                                <div class="flex-shrink-0 w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                                    <span class="text-white text-sm font-bold"><?php echo $index + 1; ?></span>
                                </div>
                                
                                <div class="flex-1">
                                    <div class="flex items-center justify-between">
                                        <h3 class="font-medium text-gray-900"><?php echo htmlspecialchars($event['status']); ?></h3>
                                        <span class="text-sm text-gray-500"><?php echo date('M j, Y g:i A', strtotime($event['timestamp'])); ?></span>
                                    </div>
                                    
                                    <?php if ($event['location'] && $shipment['tracking_level'] !== 'basic'): ?>
                                        <p class="text-sm text-gray-600 mt-1">📍 <?php echo htmlspecialchars($event['location']); ?></p>
                                    <?php endif; ?>
                                    
                                    <?php if ($event['description']): ?>
                                        <p class="text-sm text-gray-600 mt-1"><?php echo htmlspecialchars($event['description']); ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Add interactive effects
        document.addEventListener('DOMContentLoaded', function() {
            // Animate progress bar on load
            const progressBar = document.querySelector('.progress-bar');
            if (progressBar) {
                setTimeout(() => {
                    progressBar.style.width = progressBar.style.width;
                }, 500);
            }
            
            // Handle tracking level selection
            const trackingOptions = document.querySelectorAll('input[name="tracking_level"]');
            trackingOptions.forEach(option => {
                option.addEventListener('change', function() {
                    // Remove selection from all options
                    document.querySelectorAll('input[name="tracking_level"]').forEach(opt => {
                        opt.closest('.border').classList.remove('ring-2', 'ring-blue-500', 'ring-purple-500');
                    });
                    
                    // Add selection to chosen option
                    const color = this.value === 'standard' ? 'ring-blue-500' : 'ring-purple-500';
                    this.closest('.border').classList.add('ring-2', color);
                });
            });
        });
    </script>
</body>
</html>
