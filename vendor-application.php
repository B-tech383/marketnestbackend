<?php
require_once './config/config.php';
require_once './includes/vendor.php';

$vendor_manager = new VendorManager();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = sanitize_input($_POST['name']);
    $email = sanitize_input($_POST['email']);
    $business_name = sanitize_input($_POST['business_name']);
    $description = sanitize_input($_POST['description']);
    
    $logo_path = null;
    
    // Handle logo upload
    if (isset($_FILES['logo']) && $_FILES['logo']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        $file_type = $_FILES['logo']['type'];
        
        if (in_array($file_type, $allowed_types) && $_FILES['logo']['size'] <= MAX_FILE_SIZE) {
            $upload_dir = UPLOAD_PATH . 'vendor_logos/';
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION);
            $filename = uniqid() . '.' . $file_extension;
            $logo_path = $upload_dir . $filename;
            
            if (!move_uploaded_file($_FILES['logo']['tmp_name'], $logo_path)) {
                $logo_path = null;
            }
        }
    }
    
    $result = $vendor_manager->submit_application($name, $email, $business_name, $description, $logo_path);
    
    if ($result['success']) {
        $success = $result['message'];
    } else {
        $error = $result['message'];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Become a Vendor - <?php echo SITE_NAME; ?></title>
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
</head>
<body class="bg-gray-50 min-h-screen">
    <!-- Header -->
    <header class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <a href="index.php" class="text-2xl font-bold text-orange-500"><?php echo SITE_NAME; ?></a>
                </div>
                
                <nav class="flex items-center space-x-4">
                    <a href="index.php" class="text-gray-700 hover:text-orange-500">Home</a>
                    <a href="login.php" class="text-orange-500 hover:text-orange-600">Login</a>
                </nav>
            </div>
        </div>
    </header>

    <div class="max-w-3xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900">Become a Vendor</h1>
            <p class="mt-4 text-lg text-gray-600">Join our marketplace and start selling your products to thousands of customers</p>
        </div>
        
        <?php if ($error): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <div class="bg-white shadow-lg rounded-lg p-8">
            <form method="POST" enctype="multipart/form-data" class="space-y-6">
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700">Full Name *</label>
                    <input id="name" name="name" type="text" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700">Email Address *</label>
                    <input id="email" name="email" type="email" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                </div>
                
                <div>
                    <label for="business_name" class="block text-sm font-medium text-gray-700">Business Name *</label>
                    <input id="business_name" name="business_name" type="text" required 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                </div>
                
                <div>
                    <label for="description" class="block text-sm font-medium text-gray-700">Business Description *</label>
                    <textarea id="description" name="description" rows="4" required 
                              placeholder="Tell us about your business, what products you sell, and why you'd be a great addition to our marketplace..."
                              class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500"></textarea>
                </div>
                
                <div>
                    <label for="logo" class="block text-sm font-medium text-gray-700">Business Logo (Optional)</label>
                    <input id="logo" name="logo" type="file" accept="image/*" 
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-orange-500 focus:border-orange-500">
                    <p class="mt-1 text-sm text-gray-500">Upload your business logo (JPG, PNG, GIF - Max 5MB)</p>
                </div>
                
                <div class="bg-orange-50 border border-orange-200 rounded-md p-4">
                    <h3 class="text-lg font-medium text-orange-800 mb-2">What happens next?</h3>
                    <ul class="text-sm text-orange-700 space-y-1">
                        <li>• We'll review your application within 24-48 hours</li>
                        <li>• If approved, you'll receive login credentials via notification</li>
                        <li>• You can then access your vendor dashboard to start adding products</li>
                        <li>• Verified vendors get priority placement in search results</li>
                    </ul>
                </div>
                
                <div class="pt-4">
                    <button type="submit" 
                            class="w-full flex justify-center py-3 px-4 border border-transparent rounded-md shadow-sm text-lg font-medium text-white bg-orange-500 hover:bg-orange-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-orange-500 transition duration-200">
                        Submit Application
                    </button>
                </div>
            </form>
        </div>
        
        <div class="mt-8 text-center">
            <a href="index.php" class="text-orange-500 hover:text-orange-600">← Back to Home</a>
        </div>
    </div>
</body>
</html>
