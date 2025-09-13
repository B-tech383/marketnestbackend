<?php
/**
 * Setup Default Commission Rates Script
 * This script ensures all vendors have active commission rates
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/order.php';

// Only allow admin execution
if (php_sapi_name() !== 'cli') {
    require_admin();
}

$database = new Database();
$db = $database->getConnection();
$order_manager = new OrderManager();

try {
    echo "Setting up default commission rates for vendors...\n\n";
    
    // Find vendors without active commission rates
    $stmt = $db->prepare("
        SELECT v.id, v.business_name, v.user_id 
        FROM vendors v
        LEFT JOIN vendor_commissions vc ON v.id = vc.vendor_id AND vc.is_active = 1
        WHERE vc.id IS NULL
    ");
    $stmt->execute();
    $vendors_without_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($vendors_without_rates)) {
        echo "✅ All vendors already have commission rates set up.\n";
    } else {
        $default_rate = 10.00; // 10% default commission rate
        $created_count = 0;
        
        foreach ($vendors_without_rates as $vendor) {
            $result = $order_manager->ensure_vendor_commission_rate($vendor['id'], $default_rate);
            
            if ($result['success']) {
                echo "✅ Created {$default_rate}% commission rate for: {$vendor['business_name']}\n";
                $created_count++;
            } else {
                echo "❌ Failed to create rate for {$vendor['business_name']}: {$result['message']}\n";
            }
        }
        
        echo "\n📊 Summary:\n";
        echo "- Total vendors processed: " . count($vendors_without_rates) . "\n";
        echo "- Commission rates created: {$created_count}\n";
        echo "- Default rate: {$default_rate}%\n";
    }
    
    // Display current commission rates
    echo "\n📋 Current Vendor Commission Rates:\n";
    echo str_repeat("-", 70) . "\n";
    
    $stmt = $db->prepare("
        SELECT 
            v.business_name,
            vc.commission_rate,
            vc.effective_date,
            CASE WHEN vc.is_active THEN 'Active' ELSE 'Inactive' END as status
        FROM vendors v
        LEFT JOIN vendor_commissions vc ON v.id = vc.vendor_id AND vc.is_active = 1
        ORDER BY v.business_name
    ");
    $stmt->execute();
    $all_rates = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($all_rates as $rate) {
        $commission_info = $rate['commission_rate'] ? 
            "{$rate['commission_rate']}% ({$rate['status']}) - Since " . date('Y-m-d', strtotime($rate['effective_date'])) : 
            "No rate set";
        
        printf("%-30s: %s\n", 
            substr($rate['business_name'], 0, 29),
            $commission_info
        );
    }
    
    echo "\n✅ Commission rate setup completed!\n";
    
} catch (Exception $e) {
    echo "❌ Error setting up commission rates: " . $e->getMessage() . "\n";
    exit(1);
}
?>