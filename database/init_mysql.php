<?php
require_once __DIR__ . '/../config/database.php';

function initializeMySQLDatabase() {
    try {
        // First, connect without specifying database to create it
        $host = 'localhost';
        $username = 'root';
        $password = '';
        
        $pdo = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]);
        
        echo "Connected to MySQL server successfully.\n";
        
        // Create database if it doesn't exist
        $pdo->exec("CREATE DATABASE IF NOT EXISTS ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Database 'ecommerce_db' created/verified successfully.\n";
        
        // Use the database
        $pdo->exec("USE ecommerce_db");
        
        // Read and execute the original MySQL schema
        $schema = file_get_contents(__DIR__ . '/schema.sql');
        
        // Remove the first few lines that create/use database
        $schema = preg_replace('/^-- E-commerce Database Schema\s*\n/', '', $schema);
        $schema = preg_replace('/^CREATE DATABASE.*?\n/', '', $schema);
        $schema = preg_replace('/^USE.*?\n/', '', $schema);
        
        // Add tracking_payments table to the schema
        $schema .= "

-- Tracking payments table
CREATE TABLE IF NOT EXISTS tracking_payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    shipment_id INT NOT NULL,
    tracking_level ENUM('standard', 'premium') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (shipment_id) REFERENCES shipments(id)
);";
        
        // Split into individual statements and execute
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement)) {
                $pdo->exec($statement);
            }
        }
        
        echo "Database schema created successfully.\n";
        
        // Insert sample data
        insertMySQLSampleData($pdo);
        
        return true;
    } catch (Exception $e) {
        echo "Error initializing MySQL database: " . $e->getMessage() . "\n";
        return false;
    }
}

function insertMySQLSampleData($pdo) {
    // Insert sample categories
    $categories = [
        ['Electronics', 'Electronic gadgets and devices'],
        ['Clothing', 'Fashion and apparel'],
        ['Home & Garden', 'Home improvement and garden items'],
        ['Books', 'Books and educational materials'],
        ['Sports', 'Sports and outdoor equipment'],
        ['Beauty', 'Beauty and personal care products']
    ];
    
    $stmt = $pdo->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category[0], $category[1]]);
    }
    
    // Create a default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $pdo->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@marketnest.com', $admin_password, 'Admin', 'User']);
    
    $admin_id = $pdo->lastInsertId();
    if ($admin_id) {
        $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
        $stmt->execute([$admin_id]);
        
        $stmt = $pdo->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmt->execute([$admin_id, 'admin']);
    }
    
    echo "Sample data inserted successfully.\n";
    echo "Admin login - Username: admin, Password: admin123\n";
}

// Run initialization if called directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    initializeMySQLDatabase();
}
?>