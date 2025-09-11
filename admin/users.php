<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

require_admin();

$database = new Database();
$db = $database->getConnection();

// Handle user actions
if ($_POST['action'] ?? '' === 'update_status') {
    $user_id = $_POST['user_id'];
    $status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
    $stmt->execute([$status, $user_id]);
    
    header('Location: users.php?updated=1');
    exit();
}

// Get users with pagination
$page = max(1, $_GET['page'] ?? 1);
$limit = 20;
$offset = ($page - 1) * $limit;

$search = $_GET['search'] ?? '';
$role_filter = $_GET['role'] ?? '';

$where_conditions = [];
$params = [];

if ($search) {
    $where_conditions[] = "(first_name LIKE ? OR last_name LIKE ? OR email LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($role_filter) {
    $where_conditions[] = "role = ?";
    $params[] = $role_filter;
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

$stmt = $db->prepare("SELECT * FROM users $where_clause ORDER BY created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) as total FROM users $where_clause");
$stmt->execute($params);
$total_users = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
$total_pages = ceil($total_users / $limit);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - Admin</title>
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
                <div class="flex items-center space-x-4">
                    <a href="../index.php" class="text-2xl font-bold text-accent">E-Commerce</a>
                    <span class="text-gray-400">|</span>
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Admin</a>
                    <span class="text-gray-400">></span>
                    <span class="text-gray-700 font-medium">Users</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Dashboard</a>
                    <a href="../logout.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">User Management</h1>
            <p class="text-gray-600 mt-2">Manage user accounts and permissions</p>
        </div>

        <!-- Filters -->
        <div class="bg-white rounded-lg shadow mb-6">
            <div class="p-6">
                <form method="GET" class="flex flex-wrap gap-4">
                    <div class="flex-1 min-w-64">
                        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                               placeholder="Search users..." 
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                    </div>
                    <div>
                        <select name="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-primary focus:border-transparent">
                            <option value="">All Roles</option>
                            <option value="customer" <?php echo $role_filter === 'customer' ? 'selected' : ''; ?>>Customer</option>
                            <option value="vendor" <?php echo $role_filter === 'vendor' ? 'selected' : ''; ?>>Vendor</option>
                            <option value="admin" <?php echo $role_filter === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <button type="submit" class="bg-primary text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                        Search
                    </button>
                </form>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">Users (<?php echo number_format($total_users); ?>)</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Joined</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                            <span class="text-primary font-medium"><?php echo substr($user['first_name'], 0, 1); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $user['role'] === 'admin' ? 'bg-red-100 text-red-800' : 
                                            ($user['role'] === 'vendor' ? 'bg-primary/10 text-primary' : 'bg-blue-100 text-blue-800'); ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M j, Y', strtotime($user['created_at'])); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($user['role'] !== 'admin'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <input type="hidden" name="status" value="<?php echo $user['status'] === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="text-primary hover:text-blue-600">
                                                <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="px-6 py-4 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <div class="text-sm text-gray-700">
                            Showing <?php echo ($offset + 1); ?> to <?php echo min($offset + $limit, $total_users); ?> of <?php echo $total_users; ?> users
                        </div>
                        <div class="flex space-x-2">
                            <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Previous</a>
                            <?php endif; ?>
                            
                            <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                                <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                                   class="px-3 py-2 border rounded-lg text-sm <?php echo $i === $page ? 'bg-primary text-white border-primary' : 'border-gray-300 hover:bg-gray-50'; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <a href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&role=<?php echo urlencode($role_filter); ?>" 
                                   class="px-3 py-2 border border-gray-300 rounded-lg text-sm hover:bg-gray-50">Next</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
