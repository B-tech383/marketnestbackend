<?php
require_once __DIR__ . '/../config/database.php';

function initializeDatabase() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            echo "Database connection established successfully.\n";
            
            // Read and execute the schema
            $schema = file_get_contents(__DIR__ . '/schema.sql');
            $statements = explode(';', $schema);
            
            foreach ($statements as $statement) {
                $statement = trim($statement);
                if (!empty($statement)) {
                    $conn->exec($statement);
                }
            }
            
            echo "Database schema created successfully.\n";
            
            // Insert sample categories
            insertSampleData($conn);
            
            return true;
        } else {
            echo "Failed to connect to database.\n";
            return false;
        }
    } catch (Exception $e) {
        echo "Error initializing database: " . $e->getMessage() . "\n";
        return false;
    }
}

function insertSampleData($conn) {
    // Insert sample categories
    $categories = [
        ['Electronics', 'Electronic gadgets and devices', '📱'],
        ['Clothing', 'Fashion and apparel', '👕'],
        ['Home & Garden', 'Home improvement and garden items', '🏠'],
        ['Books', 'Books and educational materials', '📚'],
        ['Sports', 'Sports and outdoor equipment', '⚽'],
        ['Beauty', 'Beauty and personal care products', '💄']
    ];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description) VALUES (?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category[0], $category[1]]);
    }
    
    // Create a default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@marketnest.com', $admin_password, 'Admin', 'User']);
    
    $admin_id = $conn->lastInsertId();
    
    // If INSERT IGNORE didn't create a new user, get the existing admin user ID
    if (!$admin_id) {
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($result) {
            $admin_id = $result['id'];
        }
    }
    
    // Assign admin role
    if ($admin_id) {
        $stmt = $conn->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmt->execute([$admin_id, 'admin']);
    }
    
    echo "Sample data inserted successfully.\n";
}

// Run initialization if called directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    initializeDatabase();
}
?>