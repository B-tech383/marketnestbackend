<?php
require_once '../config/config.php';
require_once '../includes/vendor.php';

require_admin();

$vendor_manager = new VendorManager();
$message = '';

// Handle badge assignment
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['assign_badge'])) {
    $vendor_id = $_POST['vendor_id'];
    $badge_name = $_POST['badge_name'];
    
    $result = $vendor_manager->assign_badge($vendor_id, $badge_name);
    $message = $result['message'];
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
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Vendor Management</h1>
            <p class="mt-2 text-gray-600">Manage existing vendors and assign verification badges</p>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
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
                                            <?php if ($vendor['logo_path']): ?>
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
                                        <?php if ($vendor['verification_badge']): ?>
                                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($vendor['verification_badge']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-sm text-gray-500">No badge</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <form method="POST" class="flex items-center space-x-2">
                                            <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                            <select name="badge_name" class="text-sm border border-gray-300 rounded-md px-2 py-1 focus:outline-none focus:ring-accent focus:border-accent">
                                                <option value="">Select Badge</option>
                                                <?php foreach ($badges as $badge): ?>
                                                    <option value="<?php echo $badge['name']; ?>" <?php echo ($vendor['verification_badge'] == $badge['name']) ? 'selected' : ''; ?>>
                                                        <?php echo htmlspecialchars($badge['name']); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" name="assign_badge" 
                                                    class="bg-accent text-white px-3 py-1 rounded-md text-sm font-medium hover:bg-blue-600 transition duration-200">
                                                Assign
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
</body>
</html>
