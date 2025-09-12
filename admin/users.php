<?php
require_once '../config/config.php';
require_once '../includes/auth.php';

require_admin();

$database = new Database();
$db = $database->getConnection();

// Detect available role/status sources to avoid SQL errors
// Role/Status capability detection
$hasUserRoleTable = false; // true if a role history table exists
$roleTableName = null;     // 'user_role' or 'user_roles'
$hasUsersRoleColumn = false;
$hasUsersStatusColumn = false;
// Status table detection
$hasUserStatusTable = false;
$statusTableName = null;   // 'user_status' or 'user_statuses'
$statusTableHasCreatedAt = false;
try {
    // Detect role tables via information_schema
    $stmtTbl = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('user_role','user_roles')");
    $stmtTbl->execute();
    $rowTbl = $stmtTbl->fetch(PDO::FETCH_ASSOC);
    if ($rowTbl && !empty($rowTbl['TABLE_NAME'])) {
        $roleTableName = $rowTbl['TABLE_NAME'];
        $hasUserRoleTable = true;
    }
} catch (Exception $e) {
    $hasUserRoleTable = false;
}
try {
    // Detect status tables via information_schema
    $stmtTblS = $db->prepare("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME IN ('user_status','user_statuses')");
    $stmtTblS->execute();
    $rowTblS = $stmtTblS->fetch(PDO::FETCH_ASSOC);
    if ($rowTblS && !empty($rowTblS['TABLE_NAME'])) {
        $statusTableName = $rowTblS['TABLE_NAME'];
        $hasUserStatusTable = true;
    }
} catch (Exception $e) {
    $hasUserStatusTable = false;
}
try {
    // Check users.role column via information_schema (MySQL)
    $stmtCheck = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'role'");
    $stmtCheck->execute();
    $hasUsersRoleColumn = (bool)($stmtCheck->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
} catch (Exception $e) {
    $hasUsersRoleColumn = false;
}
try {
    // Check users.status column
    $stmtCheck2 = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users' AND COLUMN_NAME = 'status'");
    $stmtCheck2->execute();
    $hasUsersStatusColumn = (bool)($stmtCheck2->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
} catch (Exception $e) {
    $hasUsersStatusColumn = false;
}

// Handle user actions
if (($_POST['action'] ?? '') === 'update_status') {
    $user_id = $_POST['user_id'] ?? null;
    $status = $_POST['status'] ?? null;
    if ($user_id && $status) {
        if ($hasUserStatusTable && $statusTableName) {
            // Determine if created_at exists
            try {
                $stmtColS = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = 'created_at'");
                $stmtColS->execute([':tbl' => $statusTableName]);
                $statusTableHasCreatedAt = (bool)($stmtColS->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
            } catch (Exception $e) {
                $statusTableHasCreatedAt = false;
            }
            if ($statusTableHasCreatedAt) {
                $stmt = $db->prepare("INSERT INTO " . $statusTableName . " (user_id, status, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$user_id, $status]);
            } else {
                $stmt = $db->prepare("INSERT INTO " . $statusTableName . " (user_id, status) VALUES (?, ?)");
                $stmt->execute([$user_id, $status]);
            }
            header('Location: users.php?updated=1');
        } elseif ($hasUsersStatusColumn) {
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$status, $user_id]);
            header('Location: users.php?updated=1');
        } else {
            header('Location: users.php');
        }
    } else {
        header('Location: users.php');
    }
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
    if ($hasUserRoleTable) {
        $where_conditions[] = "roles.role = ?";
        $params[] = $role_filter;
    } elseif ($hasUsersRoleColumn) {
        $where_conditions[] = "users.role = ?";
        $params[] = $role_filter;
    } else {
        // No role source; ignore filter to avoid SQL errors
    }
}

$where_clause = $where_conditions ? 'WHERE ' . implode(' AND ', $where_conditions) : '';

// Build JOINs
$joinClauses = [];
// Join latest role per user from role table (if available)
if ($hasUserRoleTable && $roleTableName) {
    // Determine if created_at exists on the role table; if not, fall back to max(id)
    $orderColumn = 'created_at';
    try {
        $stmtCol = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = 'created_at'");
        $stmtCol->execute([':tbl' => $roleTableName]);
        $hasCreatedAt = (bool)($stmtCol->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        if (!$hasCreatedAt) {
            $orderColumn = 'id';
        }
    } catch (Exception $e) {
        $orderColumn = 'id';
    }

    $joinClauses[] = "LEFT JOIN (\n        SELECT ur.user_id, ur.role\n        FROM " . $roleTableName . " ur\n        INNER JOIN (\n            SELECT user_id, MAX(" . $orderColumn . ") AS max_val\n            FROM " . $roleTableName . "\n            GROUP BY user_id\n        ) latest ON latest.user_id = ur.user_id AND latest.max_val = ur." . $orderColumn . "\n    ) roles ON roles.user_id = users.id";
}
// Join latest status per user from status table (if available)
if ($hasUserStatusTable && $statusTableName) {
    $statusOrderColumn = 'created_at';
    try {
        $stmtColSS = $db->prepare("SELECT COUNT(*) AS c FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = :tbl AND COLUMN_NAME = 'created_at'");
        $stmtColSS->execute([':tbl' => $statusTableName]);
        $hasStatusCreatedAt = (bool)($stmtColSS->fetch(PDO::FETCH_ASSOC)['c'] ?? 0);
        if (!$hasStatusCreatedAt) {
            $statusOrderColumn = 'id';
        }
    } catch (Exception $e) {
        $statusOrderColumn = 'id';
    }
    $joinClauses[] = "LEFT JOIN (\n        SELECT us.user_id, us.status\n        FROM " . $statusTableName . " us\n        INNER JOIN (\n            SELECT user_id, MAX(" . $statusOrderColumn . ") AS max_val\n            FROM " . $statusTableName . "\n            GROUP BY user_id\n        ) latestS ON latestS.user_id = us.user_id AND latestS.max_val = us." . $statusOrderColumn . "\n    ) ustatus ON ustatus.user_id = users.id";
}
$joins = implode(' ', $joinClauses);

$selectRole = $hasUserRoleTable ? 'roles.role AS role' : ($hasUsersRoleColumn ? 'users.role AS role' : 'NULL AS role');
$selectStatus = $hasUserStatusTable ? 'ustatus.status AS status' : ($hasUsersStatusColumn ? 'users.status AS status' : 'NULL AS status');
$stmt = $db->prepare("SELECT users.*, $selectRole, $selectStatus FROM users $joins $where_clause ORDER BY users.created_at DESC LIMIT $limit OFFSET $offset");
$stmt->execute($params);
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(DISTINCT users.id) as total FROM users $joins $where_clause");
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
                            <?php 
                                $firstName = $user['first_name'] ?? '';
                                $lastName = $user['last_name'] ?? '';
                                $email = $user['email'] ?? '';
                                $role = $user['role'] ?? '';
                                $statusVal = $user['status'] ?? '';
                                $createdAt = $user['created_at'] ?? null;
                                $userId = $user['id'] ?? '';
                            ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="w-10 h-10 bg-primary/10 rounded-full flex items-center justify-center">
                                            <span class="text-primary font-medium"><?php echo substr($firstName ?: 'U', 0, 1); ?></span>
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars(trim(($firstName . ' ' . $lastName)) ?: 'Unknown User'); ?>
                                            </div>
                                            <div class="text-sm text-gray-500"><?php echo htmlspecialchars($email ?: ''); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $role === 'admin' ? 'bg-red-100 text-red-800' : 
                                            ($role === 'vendor' ? 'bg-primary/10 text-primary' : 'bg-blue-100 text-blue-800'); ?>">
                                        <?php echo $role ? ucfirst($role) : 'Unknown'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full 
                                        <?php echo $statusVal === 'active' ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800'; ?>">
                                        <?php echo $statusVal ? ucfirst($statusVal) : 'Unknown'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo $createdAt ? date('M j, Y', strtotime($createdAt)) : ''; ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <?php if ($role !== 'admin'): ?>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="action" value="update_status">
                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($userId); ?>">
                                            <input type="hidden" name="status" value="<?php echo $statusVal === 'active' ? 'inactive' : 'active'; ?>">
                                            <button type="submit" class="text-primary hover:text-blue-600">
                                                <?php echo $statusVal === 'active' ? 'Deactivate' : 'Activate'; ?>
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
