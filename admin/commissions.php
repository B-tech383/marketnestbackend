<?php
require_once '../config/config.php';
require_once '../includes/vendor.php';

require_admin();

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = generate_csrf_token();
}

$database = new Database();
$db = $database->getConnection();

// Handle commission rate updates
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'update_rate' && verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $vendor_id = (int)$_POST['vendor_id'];
        $new_rate = (float)$_POST['commission_rate'];
        
        if ($new_rate >= 0 && $new_rate <= 50) {
            // Check if vendor has existing commission rate
            $stmt = $db->prepare("SELECT id FROM vendor_commissions WHERE vendor_id = ? AND is_active = 1");
            $stmt->execute([$vendor_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                // Deactivate old rate
                $stmt = $db->prepare("UPDATE vendor_commissions SET is_active = 0 WHERE vendor_id = ? AND is_active = 1");
                $stmt->execute([$vendor_id]);
            }
            
            // Insert new rate with history tracking
            $stmt = $db->prepare("INSERT INTO vendor_commissions (vendor_id, commission_rate, effective_date, is_active, created_by) VALUES (?, ?, CURRENT_TIMESTAMP, 1, ?)");
            $stmt->execute([$vendor_id, $new_rate, $_SESSION['user_id']]);
            
            $success = "Commission rate updated successfully! New rate will apply to future orders.";
        } else {
            $error = "Commission rate must be between 0% and 50%";
        }
    } elseif ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
        $error = "Invalid request. Please try again.";
    }
}

// Get all vendors with their commission data using actual commission_transactions
$stmt = $db->prepare("
    SELECT 
        v.id,
        v.business_name,
        v.user_id,
        u.first_name,
        u.last_name,
        u.email,
        vc.commission_rate,
        vc.effective_date,
        COALESCE(ct.total_sales, 0) as total_sales,
        COALESCE(ct.total_commission, 0) as total_commission,
        COALESCE(ct.pending_commission, 0) as pending_commission
    FROM vendors v
    JOIN users u ON v.user_id = u.id
    LEFT JOIN vendor_commissions vc ON v.id = vc.vendor_id AND vc.is_active = 1
    LEFT JOIN (
        SELECT 
            ct.vendor_id,
            SUM(ct.sale_amount) as total_sales,
            SUM(ct.commission_amount) as total_commission,
            SUM(CASE WHEN ct.status = 'pending' THEN ct.commission_amount ELSE 0 END) as pending_commission
        FROM commission_transactions ct
        JOIN orders o ON ct.order_id = o.id
        WHERE o.status != 'cancelled'
        GROUP BY ct.vendor_id
    ) ct ON v.id = ct.vendor_id
    ORDER BY v.business_name
");
$stmt->execute();
$vendors = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get commission summary using actual commission_transactions
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT v.id) as total_vendors,
        AVG(vc.commission_rate) as avg_commission_rate,
        SUM(COALESCE(ct.total_commission, 0)) as total_commission_earned
    FROM vendors v
    LEFT JOIN vendor_commissions vc ON v.id = vc.vendor_id AND vc.is_active = 1
    LEFT JOIN (
        SELECT 
            ct.vendor_id,
            SUM(ct.commission_amount) as total_commission
        FROM commission_transactions ct
        JOIN orders o ON ct.order_id = o.id
        WHERE o.status != 'cancelled'
        GROUP BY ct.vendor_id
    ) ct ON v.id = ct.vendor_id
");
$stmt->execute();
$summary = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Commission Management - Admin</title>
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
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="../index.php" class="flex items-center">
                        <div class="w-10 h-10 bg-primary rounded-lg flex items-center justify-center mr-3">
                            <span class="text-white font-bold text-lg">MN</span>
                        </div>
                        <span class="text-xl font-bold text-primary"><?php echo SITE_NAME; ?></span>
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-600 hover:text-primary">Dashboard</a>
                    <a href="analytics.php" class="text-gray-600 hover:text-primary">Analytics</a>
                    <a href="orders.php" class="text-gray-600 hover:text-primary">Orders</a>
                    <a href="commissions.php" class="text-primary font-medium">Commissions</a>
                    <a href="../logout.php" class="bg-primary text-white px-4 py-2 rounded-lg hover:bg-secondary">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-primary">Commission Management</h1>
            <p class="text-gray-600 mt-2">Manage vendor commission rates and track earnings</p>
        </div>

        <?php if (isset($success)): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- Summary Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-600">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo $summary['total_vendors']; ?></h3>
                        <p class="text-gray-600">Total Vendors</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-600">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo number_format($summary['avg_commission_rate'], 1); ?>%</h3>
                        <p class="text-gray-600">Average Commission</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-600">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <h3 class="text-lg font-semibold text-gray-900"><?php echo format_currency($summary['total_commission_earned'] ?? 0); ?></h3>
                        <p class="text-gray-600">Total Earned</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendors Commission Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-xl font-semibold text-gray-900">Vendor Commission Rates</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Vendor</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission Rate</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Total Sales</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Commission Earned</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($vendors as $vendor): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($vendor['business_name']); ?></div>
                                <div class="text-sm text-gray-500">ID: <?php echo $vendor['id']; ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($vendor['first_name'] . ' ' . $vendor['last_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($vendor['email']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <form method="POST" class="flex items-center space-x-2">
                                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                                    <input type="hidden" name="action" value="update_rate">
                                    <input type="hidden" name="vendor_id" value="<?php echo $vendor['id']; ?>">
                                    <input type="number" 
                                           name="commission_rate" 
                                           value="<?php echo $vendor['commission_rate'] ?? 10; ?>" 
                                           step="0.1" 
                                           min="0" 
                                           max="50"
                                           class="w-20 px-2 py-1 border border-gray-300 rounded text-sm">
                                    <span class="text-sm text-gray-500">%</span>
                                    <button type="submit" class="bg-accent text-white px-3 py-1 rounded text-xs hover:bg-blue-600">Update</button>
                                </form>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo format_currency($vendor['total_sales'] ?? 0); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo format_currency($vendor['total_commission'] ?? 0); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="#" class="text-accent hover:text-blue-600">View Details</a>
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