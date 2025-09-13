<?php
require_once '../config/config.php';
require_once '../includes/advertisement.php';

require_admin();

$adManager = new AdvertisementManager();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['create_ad'])) {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $video_url = $_POST['video_url'] ?? '';
        $link_url = $_POST['link_url'] ?? '';
        $ad_type = $_POST['ad_type'] ?? 'banner';
        $position = $_POST['position'] ?? 'top';
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;
        
        if (empty($title)) {
            $error = 'Title is required';
        } else {
            $result = $adManager->createAd($title, $content, $image_url, $video_url, $link_url, $ad_type, $position, $start_date, $end_date);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
    
    if (isset($_POST['update_ad'])) {
        $id = $_POST['ad_id'] ?? '';
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $image_url = $_POST['image_url'] ?? '';
        $video_url = $_POST['video_url'] ?? '';
        $link_url = $_POST['link_url'] ?? '';
        $ad_type = $_POST['ad_type'] ?? 'banner';
        $position = $_POST['position'] ?? 'top';
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $start_date = $_POST['start_date'] ?: null;
        $end_date = $_POST['end_date'] ?: null;
        
        if (empty($title)) {
            $error = 'Title is required';
        } else {
            $result = $adManager->updateAd($id, $title, $content, $image_url, $video_url, $link_url, $ad_type, $position, $is_active, $start_date, $end_date);
            if ($result['success']) {
                $message = $result['message'];
            } else {
                $error = $result['message'];
            }
        }
    }
    
    if (isset($_POST['delete_ad'])) {
        $id = $_POST['ad_id'] ?? '';
        $result = $adManager->deleteAd($id);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get all ads
$ads = $adManager->getAllAds();
$stats = $adManager->getAdStats();

// Get ad for editing
$edit_ad = null;
if (isset($_GET['edit'])) {
    $edit_ad = $adManager->getAd($_GET['edit']);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advertisement Management - <?php echo SITE_NAME; ?></title>
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
                    <a href="../index.php" class="text-2xl font-bold text-primary"><?php echo SITE_NAME; ?></a>
                    <span class="text-gray-400">|</span>
                    <a href="dashboard.php" class="text-gray-700 hover:text-accent">Admin Dashboard</a>
                    <span class="text-gray-400">|</span>
                    <span class="text-gray-700 font-medium">Advertisement Management</span>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <?php echo $_SESSION['first_name']; ?>!</span>
                    <a href="../logout.php" class="bg-accent text-white px-4 py-2 rounded-lg hover:bg-blue-600 transition">Logout</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <!-- Page Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Advertisement Management</h1>
            <p class="mt-2 text-gray-600">Create and manage advertisements across the platform</p>
        </div>

        <!-- Messages -->
        <?php if ($message): ?>
            <div class="mb-6 p-4 bg-green-50 border border-green-200 rounded-lg">
                <p class="text-green-800"><?php echo htmlspecialchars($message); ?></p>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border border-red-200 rounded-lg">
                <p class="text-red-800"><?php echo htmlspecialchars($error); ?></p>
            </div>
        <?php endif; ?>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 4V2a1 1 0 011-1h8a1 1 0 011 1v2m0 0V1a1 1 0 011-1h2a1 1 0 011 1v18a1 1 0 01-1 1H3a1 1 0 01-1-1V1a1 1 0 011-1h2a1 1 0 011 1v3m0 0h8m-8 0v16a1 1 0 001 1h6a1 1 0 001-1V4H7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Ads</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['total_ads']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Active Ads</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo $stats['active_ads']; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-purple-100 rounded-lg">
                        <svg class="w-6 h-6 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Views</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_views']); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-yellow-100 rounded-lg">
                        <svg class="w-6 h-6 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600">Total Clicks</p>
                        <p class="text-2xl font-semibold text-gray-900"><?php echo number_format($stats['total_clicks']); ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Create/Edit Ad Form -->
        <div class="bg-white rounded-lg shadow mb-8">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">
                    <?php echo $edit_ad ? 'Edit Advertisement' : 'Create New Advertisement'; ?>
                </h2>
            </div>
            
            <form method="POST" class="p-6">
                <?php if ($edit_ad): ?>
                    <input type="hidden" name="ad_id" value="<?php echo $edit_ad['id']; ?>">
                <?php endif; ?>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Title *</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($edit_ad['title'] ?? ''); ?>" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent" required>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Ad Type</label>
                        <select name="ad_type" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="banner" <?php echo ($edit_ad['ad_type'] ?? 'banner') === 'banner' ? 'selected' : ''; ?>>Banner</option>
                            <option value="sidebar" <?php echo ($edit_ad['ad_type'] ?? '') === 'sidebar' ? 'selected' : ''; ?>>Sidebar</option>
                            <option value="popup" <?php echo ($edit_ad['ad_type'] ?? '') === 'popup' ? 'selected' : ''; ?>>Popup</option>
                            <option value="inline" <?php echo ($edit_ad['ad_type'] ?? '') === 'inline' ? 'selected' : ''; ?>>Inline</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Position</label>
                        <select name="position" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                            <option value="top" <?php echo ($edit_ad['position'] ?? 'top') === 'top' ? 'selected' : ''; ?>>Top</option>
                            <option value="middle" <?php echo ($edit_ad['position'] ?? '') === 'middle' ? 'selected' : ''; ?>>Middle</option>
                            <option value="bottom" <?php echo ($edit_ad['position'] ?? '') === 'bottom' ? 'selected' : ''; ?>>Bottom</option>
                            <option value="left" <?php echo ($edit_ad['position'] ?? '') === 'left' ? 'selected' : ''; ?>>Left</option>
                            <option value="right" <?php echo ($edit_ad['position'] ?? '') === 'right' ? 'selected' : ''; ?>>Right</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Link URL</label>
                        <input type="url" name="link_url" value="<?php echo htmlspecialchars($edit_ad['link_url'] ?? ''); ?>" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Image URL</label>
                        <input type="url" name="image_url" value="<?php echo htmlspecialchars($edit_ad['image_url'] ?? ''); ?>" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Video URL</label>
                        <input type="url" name="video_url" value="<?php echo htmlspecialchars($edit_ad['video_url'] ?? ''); ?>" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Start Date</label>
                        <input type="datetime-local" name="start_date" value="<?php echo $edit_ad['start_date'] ? date('Y-m-d\TH:i', strtotime($edit_ad['start_date'])) : ''; ?>" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">End Date</label>
                        <input type="datetime-local" name="end_date" value="<?php echo $edit_ad['end_date'] ? date('Y-m-d\TH:i', strtotime($edit_ad['end_date'])) : ''; ?>" 
                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                    </div>
                </div>
                
                <div class="mt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Content</label>
                    <textarea name="content" rows="4" 
                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent"><?php echo htmlspecialchars($edit_ad['content'] ?? ''); ?></textarea>
                </div>
                
                <?php if ($edit_ad): ?>
                    <div class="mt-6">
                        <label class="flex items-center">
                            <input type="checkbox" name="is_active" <?php echo $edit_ad['is_active'] ? 'checked' : ''; ?> 
                                   class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                            <span class="ml-2 text-sm text-gray-700">Active</span>
                        </label>
                    </div>
                <?php endif; ?>
                
                <div class="mt-6 flex justify-end space-x-4">
                    <?php if ($edit_ad): ?>
                        <a href="advertisements.php" class="bg-gray-300 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-400 transition">
                            Cancel
                        </a>
                        <button type="submit" name="update_ad" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                            Update Advertisement
                        </button>
                    <?php else: ?>
                        <button type="submit" name="create_ad" class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                            Create Advertisement
                        </button>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Ads List -->
        <div class="bg-white rounded-lg shadow">
            <div class="px-6 py-4 border-b border-gray-200">
                <h2 class="text-lg font-semibold text-gray-900">All Advertisements</h2>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Ad</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Type</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Position</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Views</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Clicks</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($ads as $ad): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <?php if ($ad['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($ad['image_url']); ?>" alt="Ad" class="w-12 h-12 object-cover rounded-lg">
                                        <?php else: ?>
                                            <div class="w-12 h-12 bg-gray-200 rounded-lg flex items-center justify-center">
                                                <span class="text-gray-500 text-xs">No Image</span>
                                            </div>
                                        <?php endif; ?>
                                        <div class="ml-4">
                                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($ad['title']); ?></div>
                                            <div class="text-sm text-gray-500"><?php echo date('M j, Y', strtotime($ad['created_at'])); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo ucfirst($ad['ad_type']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo ucfirst($ad['position']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $ad['is_active'] ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo $ad['is_active'] ? 'Active' : 'Inactive'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format($ad['view_count']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                    <?php echo number_format($ad['click_count']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex space-x-2">
                                        <a href="?edit=<?php echo $ad['id']; ?>" class="text-accent hover:text-blue-600">Edit</a>
                                        <form method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this advertisement?')">
                                            <input type="hidden" name="ad_id" value="<?php echo $ad['id']; ?>">
                                            <button type="submit" name="delete_ad" class="text-red-600 hover:text-red-900">Delete</button>
                                        </form>
                                    </div>
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

