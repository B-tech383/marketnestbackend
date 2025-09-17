<?php
require_once '../config/config.php';
require_once '../includes/vendor.php';

require_admin();


$vendor_manager = new VendorManager();
$message = '';

// Generate CSRF token
$csrf_token = generate_csrf_token();

// Handle badge assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_badge'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $vendor_id = (int)$_POST['vendor_id'];
        $badge_id  = (int)$_POST['badge_id']; // badge ID from the form
        
        $result = $vendor_manager->assign_badge($vendor_id, $badge_id);
        $message = $result['message'];
    }
}


// Handle add vendor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_vendor'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $name = sanitize_input($_POST['name']);
        $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
        $business_name = sanitize_input($_POST['business_name']);
        $description = sanitize_input($_POST['description']);
        
        if (!$email) {
            $message = 'Please provide a valid email address.';
        } elseif (empty($name) || empty($business_name) || empty($description)) {
            $message = 'All fields are required.';
        } else {
            $result = $vendor_manager->create_vendor_directly($name, $email, $business_name, $description);
            $message = $result['message'];
        }
    }
}

// Handle delete vendor
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_vendor'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $message = 'Invalid security token. Please try again.';
    } else {
        $vendor_id = (int)$_POST['vendor_id'];
        
        $result = $vendor_manager->delete_vendor($vendor_id);
        $message = $result['message'];
    }
}


$vendors = $vendor_manager->get_all_vendors();

// Get available badges
$database = new Database();
$db = $database->getConnection();
$stmt = $db->prepare("SELECT * FROM badges ORDER BY name");
$stmt->execute();
$badges = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Management - Admin Dashboard</title>
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
                    <a href="vendor-applications.php" class="text-gray-700 hover:text-accent">Applications</a>
                    <a href="../logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900">Vendor Management</h1>
                <p class="mt-2 text-gray-600">Manage existing vendors and assign verification badges</p>
            </div>
            <button onclick="document.getElementById('addVendorModal').classList.remove('hidden')" 
                    class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">
                Add New Vendor
            </button>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    All Vendors 
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                        <?php echo count($vendors); ?>
                    </span>
                </h2>
            </div>
            
            <?php if (empty($vendors)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    No vendors found
                </div>
            <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Badge</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($vendors as $vendor): ?>
                                <tr>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <?php if (isset($vendor['logo_path']) && $vendor['logo_path']): ?>
                                                <img src="../<?php echo $vendor['logo_path']; ?>" alt="Logo" class="w-10 h-10 rounded-full object-cover mr-4">
                                            <?php else: ?>
                                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center mr-4">
                                                    <span class="text-accent font-medium"><?php echo substr($vendor['business_name'], 0, 1); ?></span>
                                                </div>
                                            <?php endif; ?>
                                            <div>
                                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['business_name']); ?></div>
                                                <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($vendor['username']); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($vendor['is_verified']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                                Verified
                                            </span>
                                        <?php else: ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                                Unverified
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if (!empty($vendor['badge_id']) && isset($all_badges[$vendor['badge_id']])): ?>
                                            <?php $badge = $all_badges[$vendor['badge_id']]; ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full"
                                                style="background-color:<?php echo htmlspecialchars($badge['color']); ?>; color:#fff;">
                                                <?php if (!empty($badge['icon'])): ?>
                                                    <img src="<?php echo htmlspecialchars($badge['icon']); ?>" 
                                                        alt="" class="inline w-4 h-4 mr-1">
                                                <?php endif; ?>
                                                <?php echo htmlspecialchars($badge['name']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">No badge</span>
                                        <?php endif; ?>
                                    </td>



                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center space-x-2">
                                            <form method="POST" class="flex items-center space-x-2">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <select name="badge_id">
                                                    <option value="">Select Badge</option>
                                                    <?php foreach ($badges as $badge): ?>
                                                        <option value="<?php echo $badge['id']; ?>" <?php echo ($vendor['badge_id'] == $badge['id']) ? 'selected' : ''; ?>>
                                                            <?php echo htmlspecialchars($badge['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>

                                                <button type="submit" name="assign_badge" 
                                                        class="bg-accent text-white px-3 py-1 rounded-md text-sm font-medium hover:bg-blue-600 transition duration-200">
                                                    Assign
                                                </button>
                                            </form>
                                            <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this vendor? This will also deactivate all their products.')">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                                <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                                <button type="submit" name="delete_vendor" 
                                                        class="bg-red-500 text-white px-3 py-1 rounded-md text-sm font-medium hover:bg-red-600 transition duration-200">
                                                    Delete
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Vendor Modal -->
    <div id="addVendorModal" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto p-5 border w-96 shadow-lg rounded-md bg-white">
            <div class="mt-3 text-center">
                <h3 class="text-lg font-medium text-gray-900 mb-4">Add New Vendor</h3>
                <form method="POST" class="space-y-4">
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Contact Name</label>
                        <input type="text" name="name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                    </div>
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                        <input type="email" name="email" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                    </div>
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Business Name</label>
                        <input type="text" name="business_name" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent">
                    </div>
                    <div class="text-left">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
                        <textarea name="description" rows="3" required 
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-accent focus:border-accent"></textarea>
                    </div>
                    <div class="flex space-x-3 pt-3">
                        <button type="submit" name="add_vendor" 
                                class="flex-1 bg-accent text-white px-4 py-2 rounded-md hover:bg-blue-600 transition">
                            Create Vendor
                        </button>
                        <button type="button" onclick="document.getElementById('addVendorModal').classList.add('hidden')" 
                                class="flex-1 bg-gray-300 text-gray-700 px-4 py-2 rounded-md hover:bg-gray-400 transition">
                            Cancel
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
