<?php
require_once '../config/config.php';
require_once '../includes/vendor.php';

require_admin();

$vendor_manager = new VendorManager();
$message = '';

// Handle actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'];
    $application_id = $_POST['application_id'];
    
    if ($action == 'approve') {
        $result = $vendor_manager->approve_application($application_id, $_SESSION['user_id']);
        $message = $result['message'];
        if ($result['success']) {
            $message .= " Login credentials: Username: " . $result['credentials']['username'] . ", Password: " . $result['credentials']['password'];
        }
    } elseif ($action == 'reject') {
        $result = $vendor_manager->reject_application($application_id, $_SESSION['user_id']);
        $message = $result['message'];
    }
}

$pending_applications = $vendor_manager->get_pending_applications();
$all_applications = $vendor_manager->get_all_applications();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendor Applications - Admin Dashboard</title>
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
                    <a href="../logout.php" class="text-accent hover:text-blue-600">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Vendor Applications</h1>
            <p class="mt-2 text-gray-600">Review and manage vendor applications</p>
        </div>
        
        <?php if ($message): ?>
            <div class="bg-blue-100 border border-blue-400 text-blue-700 px-4 py-3 rounded mb-6">
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <!-- Pending Applications -->
        <div class="bg-white shadow rounded-lg mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">
                    Pending Applications 
                    <span class="bg-blue-100 text-blue-800 text-sm font-medium px-2.5 py-0.5 rounded-full ml-2">
                        <?php echo count($pending_applications); ?>
                    </span>
                </h2>
            </div>
            
            <?php if (empty($pending_applications)): ?>
                <div class="px-6 py-8 text-center text-gray-500">
                    No pending applications
                </div>
            <?php else: ?>
                <div class="divide-y divide-gray-200">
                    <?php foreach ($pending_applications as $app): ?>
                        <div class="px-6 py-6">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <div class="flex items-center space-x-4 mb-4">
                                        <?php if ($app['logo_path']): ?>
                                            <img src="../<?php echo $app['logo_path']; ?>" alt="Logo" class="w-12 h-12 rounded-full object-cover">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-blue-100 rounded-full flex items-center justify-center">
                                                <span class="text-accent font-medium"><?php echo substr($app['business_name'], 0, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <div>
                                            <h3 class="text-lg font-medium text-gray-900"><?php echo htmlspecialchars($app['business_name']); ?></h3>
                                            <p class="text-sm text-gray-500">by <?php echo htmlspecialchars($app['name']); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                        <div>
                                            <span class="text-sm font-medium text-gray-500">Email:</span>
                                            <p class="text-sm text-gray-900"><?php echo htmlspecialchars($app['email']); ?></p>
                                        </div>
                                        <div>
                                            <span class="text-sm font-medium text-gray-500">Applied:</span>
                                            <p class="text-sm text-gray-900"><?php echo date('M j, Y g:i A', strtotime($app['applied_at'])); ?></p>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-4">
                                        <span class="text-sm font-medium text-gray-500">Description:</span>
                                        <p class="text-sm text-gray-900 mt-1"><?php echo nl2br(htmlspecialchars($app['description'])); ?></p>
                                    </div>
                                </div>
                                
                                <div class="flex space-x-2 ml-4">
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="approve">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" 
                                                onclick="return confirm('Are you sure you want to approve this application?')"
                                                class="bg-green-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-green-600 transition duration-200">
                                            Approve
                                        </button>
                                    </form>
                                    
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="reject">
                                        <input type="hidden" name="application_id" value="<?php echo $app['id']; ?>">
                                        <button type="submit" 
                                                onclick="return confirm('Are you sure you want to reject this application?')"
                                                class="bg-red-500 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition duration-200">
                                            Reject
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- All Applications History -->
        <div class="bg-white shadow rounded-lg">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Application History</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Business</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applicant</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Applied</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Reviewed By</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($all_applications as $app): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($app['logo_path']): ?>
                                            <img src="../<?php echo $app['logo_path']; ?>" alt="Logo" class="w-8 h-8 rounded-full object-cover mr-3">
                                        <?php else: ?>
                                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                                <span class="text-accent text-xs font-medium"><?php echo substr($app['business_name'], 0, 1); ?></span>
                                            </div>
                                        <?php endif; ?>
                                        <span class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($app['business_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-gray-900"><?php echo htmlspecialchars($app['name']); ?></div>
                                    <div class="text-sm text-gray-500"><?php echo htmlspecialchars($app['email']); ?></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($app['applied_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <?php
                                    $status_colors = [
                                        'pending' => 'bg-yellow-100 text-yellow-800',
                                        'approved' => 'bg-green-100 text-green-800',
                                        'rejected' => 'bg-red-100 text-red-800'
                                    ];
                                    ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_colors[$app['status']]; ?>">
                                        <?php echo ucfirst($app['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $app['reviewed_by_username'] ?? '-'; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>
