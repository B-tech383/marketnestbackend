<?php
require_once __DIR__ . '/../config/database.php';

function initializeDatabase() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            echo "Database connection established successfully.\n";
            
            // Read and execute the schema
            $schema = file_get_contents(__DIR__ . '/schema_sqlite.sql');
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
    
    $stmt = $conn->prepare("INSERT OR IGNORE INTO categories (name, description, icon) VALUES (?, ?, ?)");
    foreach ($categories as $category) {
        $stmt->execute([$category[0], $category[1], $category[2]]);
    }
    
    // Create a default admin user
    $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT OR IGNORE INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute(['admin', 'admin@orangecart.com', $admin_password, 'Admin', 'User', 'admin']);
    
    $admin_id = $conn->lastInsertId();
    if ($admin_id) {
        $stmt = $conn->prepare("INSERT OR IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmt->execute([$admin_id, 'admin']);
    }
    
    echo "Sample data inserted successfully.\n";
}

// Run initialization if called directly
if (basename(__FILE__) == basename($_SERVER["SCRIPT_NAME"])) {
    initializeDatabase();
}
?>