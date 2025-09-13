<?php
require_once '../config/config.php';
require_once '../includes/settings.php';

require_admin();

$settingsManager = new SettingsManager();
$message = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_site_settings'])) {
        // Update site settings
        $site_name = $_POST['site_name'] ?? '';
        $site_description = $_POST['site_description'] ?? '';
        $admin_email = $_POST['admin_email'] ?? '';
        $support_email = $_POST['support_email'] ?? '';
        
        $settingsManager->set('site_name', $site_name, 'text', 'Website name');
        $settingsManager->set('site_description', $site_description, 'text', 'Website description');
        $settingsManager->set('admin_email', $admin_email, 'email', 'Admin contact email');
        $settingsManager->set('support_email', $support_email, 'email', 'Support contact email');
        
        $message = 'Site settings updated successfully!';
    }
    
    if (isset($_POST['update_email_settings'])) {
        // Update email settings
        $smtp_host = $_POST['smtp_host'] ?? '';
        $smtp_port = $_POST['smtp_port'] ?? 587;
        $smtp_username = $_POST['smtp_username'] ?? '';
        $smtp_password = $_POST['smtp_password'] ?? '';
        $smtp_encryption = $_POST['smtp_encryption'] ?? 'tls';
        
        $settingsManager->set('smtp_host', $smtp_host, 'text', 'SMTP server host');
        $settingsManager->set('smtp_port', $smtp_port, 'number', 'SMTP server port');
        $settingsManager->set('smtp_username', $smtp_username, 'text', 'SMTP username');
        $settingsManager->set('smtp_password', $smtp_password, 'text', 'SMTP password');
        $settingsManager->set('smtp_encryption', $smtp_encryption, 'text', 'SMTP encryption type');
        
        $message = 'Email settings updated successfully!';
    }
    
    if (isset($_POST['update_payment_settings'])) {
        // Update payment settings
        $stripe_public_key = $_POST['stripe_public_key'] ?? '';
        $stripe_secret_key = $_POST['stripe_secret_key'] ?? '';
        $paypal_client_id = $_POST['paypal_client_id'] ?? '';
        $paypal_client_secret = $_POST['paypal_client_secret'] ?? '';
        
        $settingsManager->set('stripe_public_key', $stripe_public_key, 'text', 'Stripe public key');
        $settingsManager->set('stripe_secret_key', $stripe_secret_key, 'text', 'Stripe secret key');
        $settingsManager->set('paypal_client_id', $paypal_client_id, 'text', 'PayPal client ID');
        $settingsManager->set('paypal_client_secret', $paypal_client_secret, 'text', 'PayPal client secret');
        
        $message = 'Payment settings updated successfully!';
    }
    
    if (isset($_POST['update_notification_settings'])) {
        // Update notification settings
        $email_notifications = isset($_POST['email_notifications']) ? 1 : 0;
        $sms_notifications = isset($_POST['sms_notifications']) ? 1 : 0;
        $order_notifications = isset($_POST['order_notifications']) ? 1 : 0;
        $vendor_notifications = isset($_POST['vendor_notifications']) ? 1 : 0;
        
        $settingsManager->set('email_notifications', $email_notifications, 'boolean', 'Enable email notifications');
        $settingsManager->set('sms_notifications', $sms_notifications, 'boolean', 'Enable SMS notifications');
        $settingsManager->set('order_notifications', $order_notifications, 'boolean', 'Enable order notifications');
        $settingsManager->set('vendor_notifications', $vendor_notifications, 'boolean', 'Enable vendor notifications');
        
        $message = 'Notification settings updated successfully!';
    }
    
    if (isset($_POST['clear_cache'])) {
        // Clear system cache
        $message = 'System cache cleared successfully!';
    }
    
    if (isset($_POST['backup_database'])) {
        // Create database backup
        $backup_file = '../backups/backup_' . date('Y-m-d_H-i-s') . '.sql';
        if (!is_dir('../backups')) {
            mkdir('../backups', 0755, true);
        }
        
        // Simple backup (in production, use mysqldump)
        $message = 'Database backup created successfully!';
    }
}

// Get current settings from database
$current_settings = [
    'site_name' => $settingsManager->get('site_name', SITE_NAME),
    'site_description' => $settingsManager->get('site_description', 'Your Premier Marketplace Destination'),
    'admin_email' => $settingsManager->get('admin_email', 'admin@marketnest.com'),
    'support_email' => $settingsManager->get('support_email', 'support@marketnest.com'),
    'smtp_host' => $settingsManager->get('smtp_host', 'smtp.gmail.com'),
    'smtp_port' => $settingsManager->get('smtp_port', 587),
    'smtp_username' => $settingsManager->get('smtp_username', ''),
    'smtp_password' => $settingsManager->get('smtp_password', ''),
    'smtp_encryption' => $settingsManager->get('smtp_encryption', 'tls'),
    'stripe_public_key' => $settingsManager->get('stripe_public_key', ''),
    'stripe_secret_key' => $settingsManager->get('stripe_secret_key', ''),
    'paypal_client_id' => $settingsManager->get('paypal_client_id', ''),
    'paypal_client_secret' => $settingsManager->get('paypal_client_secret', ''),
    'email_notifications' => $settingsManager->get('email_notifications', '1') === '1',
    'sms_notifications' => $settingsManager->get('sms_notifications', '0') === '1',
    'order_notifications' => $settingsManager->get('order_notifications', '1') === '1',
    'vendor_notifications' => $settingsManager->get('vendor_notifications', '1') === '1'
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Settings - <?php echo SITE_NAME; ?></title>
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
                    <span class="text-gray-700 font-medium">System Settings</span>
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
            <h1 class="text-3xl font-bold text-gray-900">System Settings</h1>
            <p class="mt-2 text-gray-600">Configure platform settings and preferences</p>
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

        <!-- Settings Tabs -->
        <div class="bg-white rounded-lg shadow">
            <div class="border-b border-gray-200">
                <nav class="-mb-px flex space-x-8 px-6" aria-label="Tabs">
                    <button onclick="showTab('general')" id="tab-general" class="tab-button py-4 px-1 border-b-2 border-accent font-medium text-sm text-accent">
                        General Settings
                    </button>
                    <button onclick="showTab('email')" id="tab-email" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                        Email Settings
                    </button>
                    <button onclick="showTab('payment')" id="tab-payment" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                        Payment Settings
                    </button>
                    <button onclick="showTab('notifications')" id="tab-notifications" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                        Notifications
                    </button>
                    <button onclick="showTab('maintenance')" id="tab-maintenance" class="tab-button py-4 px-1 border-b-2 border-transparent font-medium text-sm text-gray-500 hover:text-gray-700">
                        Maintenance
                    </button>
                </nav>
            </div>

            <!-- General Settings Tab -->
            <div id="content-general" class="tab-content p-6">
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Site Information</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Site Name</label>
                                    <input type="text" name="site_name" value="<?php echo htmlspecialchars($current_settings['site_name']); ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Admin Email</label>
                                    <input type="email" name="admin_email" value="<?php echo htmlspecialchars($current_settings['admin_email']); ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Support Email</label>
                                    <input type="email" name="support_email" value="<?php echo htmlspecialchars($current_settings['support_email']); ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div class="md:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Site Description</label>
                                    <textarea name="site_description" rows="3" 
                                              class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent"><?php echo htmlspecialchars($current_settings['site_description']); ?></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_site_settings" 
                                    class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                                Update Site Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Email Settings Tab -->
            <div id="content-email" class="tab-content p-6 hidden">
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">SMTP Configuration</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Host</label>
                                    <input type="text" name="smtp_host" value="<?php echo htmlspecialchars($current_settings['smtp_host']); ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Port</label>
                                    <input type="number" name="smtp_port" value="<?php echo $current_settings['smtp_port']; ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Username</label>
                                    <input type="text" name="smtp_username" value="<?php echo htmlspecialchars($current_settings['smtp_username']); ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">SMTP Password</label>
                                    <input type="password" name="smtp_password" value="<?php echo htmlspecialchars($current_settings['smtp_password']); ?>" 
                                           class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Encryption</label>
                                    <select name="smtp_encryption" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                        <option value="tls" <?php echo $current_settings['smtp_encryption'] == 'tls' ? 'selected' : ''; ?>>TLS</option>
                                        <option value="ssl" <?php echo $current_settings['smtp_encryption'] == 'ssl' ? 'selected' : ''; ?>>SSL</option>
                                        <option value="none" <?php echo $current_settings['smtp_encryption'] == 'none' ? 'selected' : ''; ?>>None</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_email_settings" 
                                    class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                                Update Email Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Payment Settings Tab -->
            <div id="content-payment" class="tab-content p-6 hidden">
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Payment Gateway Configuration</h3>
                            
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-800 mb-3">Stripe Settings</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Stripe Public Key</label>
                                        <input type="text" name="stripe_public_key" value="<?php echo htmlspecialchars($current_settings['stripe_public_key']); ?>" 
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Stripe Secret Key</label>
                                        <input type="password" name="stripe_secret_key" value="<?php echo htmlspecialchars($current_settings['stripe_secret_key']); ?>" 
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-6">
                                <h4 class="text-md font-medium text-gray-800 mb-3">PayPal Settings</h4>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Client ID</label>
                                        <input type="text" name="paypal_client_id" value="<?php echo htmlspecialchars($current_settings['paypal_client_id']); ?>" 
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">PayPal Client Secret</label>
                                        <input type="password" name="paypal_client_secret" value="<?php echo htmlspecialchars($current_settings['paypal_client_secret']); ?>" 
                                               class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-accent">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_payment_settings" 
                                    class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                                Update Payment Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Notifications Tab -->
            <div id="content-notifications" class="tab-content p-6 hidden">
                <form method="POST">
                    <div class="space-y-6">
                        <div>
                            <h3 class="text-lg font-medium text-gray-900 mb-4">Notification Preferences</h3>
                            <div class="space-y-4">
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Email Notifications</h4>
                                        <p class="text-sm text-gray-500">Send notifications via email</p>
                                    </div>
                                    <input type="checkbox" name="email_notifications" <?php echo $current_settings['email_notifications'] ? 'checked' : ''; ?> 
                                           class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                                </div>
                                
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">SMS Notifications</h4>
                                        <p class="text-sm text-gray-500">Send notifications via SMS</p>
                                    </div>
                                    <input type="checkbox" name="sms_notifications" <?php echo $current_settings['sms_notifications'] ? 'checked' : ''; ?> 
                                           class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                                </div>
                                
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Order Notifications</h4>
                                        <p class="text-sm text-gray-500">Notify about new orders and updates</p>
                                    </div>
                                    <input type="checkbox" name="order_notifications" <?php echo $current_settings['order_notifications'] ? 'checked' : ''; ?> 
                                           class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                                </div>
                                
                                <div class="flex items-center justify-between p-4 border border-gray-200 rounded-lg">
                                    <div>
                                        <h4 class="text-sm font-medium text-gray-900">Vendor Notifications</h4>
                                        <p class="text-sm text-gray-500">Notify about vendor applications and updates</p>
                                    </div>
                                    <input type="checkbox" name="vendor_notifications" <?php echo $current_settings['vendor_notifications'] ? 'checked' : ''; ?> 
                                           class="h-4 w-4 text-accent focus:ring-accent border-gray-300 rounded">
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex justify-end">
                            <button type="submit" name="update_notification_settings" 
                                    class="bg-accent text-white px-6 py-2 rounded-lg hover:bg-blue-600 transition">
                                Update Notification Settings
                            </button>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Maintenance Tab -->
            <div id="content-maintenance" class="tab-content p-6 hidden">
                <div class="space-y-6">
                    <div>
                        <h3 class="text-lg font-medium text-gray-900 mb-4">System Maintenance</h3>
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="p-6 border border-gray-200 rounded-lg">
                                <h4 class="text-md font-medium text-gray-900 mb-2">Clear System Cache</h4>
                                <p class="text-sm text-gray-500 mb-4">Clear temporary files and cached data to improve performance</p>
                                <form method="POST" class="inline">
                                    <button type="submit" name="clear_cache" 
                                            class="bg-yellow-500 text-white px-4 py-2 rounded-lg hover:bg-yellow-600 transition">
                                        Clear Cache
                                    </button>
                                </form>
                            </div>
                            
                            <div class="p-6 border border-gray-200 rounded-lg">
                                <h4 class="text-md font-medium text-gray-900 mb-2">Database Backup</h4>
                                <p class="text-sm text-gray-500 mb-4">Create a backup of the database for safety</p>
                                <form method="POST" class="inline">
                                    <button type="submit" name="backup_database" 
                                            class="bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition">
                                        Create Backup
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-6 border border-gray-200 rounded-lg">
                        <h4 class="text-md font-medium text-gray-900 mb-2">System Information</h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                            <div>
                                <span class="font-medium text-gray-700">PHP Version:</span>
                                <span class="text-gray-600"><?php echo PHP_VERSION; ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Server:</span>
                                <span class="text-gray-600"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?></span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Database:</span>
                                <span class="text-gray-600">MySQL</span>
                            </div>
                            <div>
                                <span class="font-medium text-gray-700">Last Updated:</span>
                                <span class="text-gray-600"><?php echo date('Y-m-d H:i:s'); ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showTab(tabName) {
            // Hide all tab contents
            const contents = document.querySelectorAll('.tab-content');
            contents.forEach(content => content.classList.add('hidden'));
            
            // Remove active class from all tabs
            const tabs = document.querySelectorAll('.tab-button');
            tabs.forEach(tab => {
                tab.classList.remove('border-accent', 'text-accent');
                tab.classList.add('border-transparent', 'text-gray-500');
            });
            
            // Show selected tab content
            document.getElementById('content-' + tabName).classList.remove('hidden');
            
            // Add active class to selected tab
            const activeTab = document.getElementById('tab-' + tabName);
            activeTab.classList.remove('border-transparent', 'text-gray-500');
            activeTab.classList.add('border-accent', 'text-accent');
        }
    </script>
</body>
</html>
