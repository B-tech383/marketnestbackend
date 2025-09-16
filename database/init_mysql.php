<?php
require_once __DIR__ . '/../config/database.php';

function initializeMySQLDatabase() {
    try {
        // Use the Database class to get connection with environment variables
        $database = new Database();
        $conn = $database->getConnectionWithoutDb();
        
        echo "Connected to MySQL server successfully.\n";
        
        // Get database name from environment or use default
        $db_name = 'ecommerce_db';

        
        // Check if we should create database (external providers usually don't allow this)
        $allow_db_create = $_ENV['MYSQL_ALLOW_DB_CREATE'] ?? getenv('MYSQL_ALLOW_DB_CREATE') ?? 'true';
        $is_external = ($_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST')) && 
                      (($_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST')) !== 'localhost');
        
        if ($allow_db_create === 'true' && !$is_external) {
            // Create database if it doesn't exist (local MySQL only)
            $conn->exec("CREATE DATABASE IF NOT EXISTS {$db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
            echo "Database '{$db_name}' created/verified successfully.\n";
        } else {
            echo "Using existing database '{$db_name}' (external provider).\n";
        }
        
        // Use the database
        $conn->exec("USE {$db_name}");
        
        // Read and execute the MySQL schema
        $schema_file = __DIR__ . '/init_mysql.sql';
        if (!file_exists($schema_file)) {
            throw new Exception("MySQL schema file not found: {$schema_file}");
        }
        
        $schema = file_get_contents($schema_file);
        
        // Remove the database creation lines since we handle that above
        $schema = preg_replace('/^DROP DATABASE.*?;\s*/m', '', $schema);
        $schema = preg_replace('/^CREATE DATABASE.*?;\s*/m', '', $schema);
        $schema = preg_replace('/^USE.*?;\s*/m', '', $schema);
        
        // Split into individual statements and execute
        $statements = array_filter(array_map('trim', explode(';', $schema)));
        
        foreach ($statements as $statement) {
            if (!empty($statement) && !preg_match('/^\s*--/', $statement)) {
                try {
                    $conn->exec($statement);
                    echo "✓ Executed statement successfully\n";
                } catch (PDOException $e) {
                    echo "✗ Error executing statement: " . $e->getMessage() . "\n";
                    echo "Statement: " . substr($statement, 0, 100) . "...\n";
                }
            }
        }
        
        echo "Database schema created successfully.\n";
        
        // Insert sample data
        insertMySQLSampleData($conn);
        
        return true;
    } catch (Exception $e) {
        echo "Error initializing MySQL database: " . $e->getMessage() . "\n";
        return false;
    }
}

function insertMySQLSampleData($conn) {
    try {
        // Insert sample categories
        $categories = [
            ['Electronics', 'Electronic gadgets and devices', '📱'],
            ['Clothing', 'Fashion and apparel', '👕'],
            ['Home & Garden', 'Home improvement and garden items', '🏠'],
            ['Books', 'Books and educational materials', '📚'],
            ['Sports', 'Sports and outdoor equipment', '⚽'],
            ['Beauty', 'Beauty and personal care products', '💄']
        ];
        
        $stmt = $conn->prepare("INSERT IGNORE INTO categories (name, description, icon) VALUES (?, ?, ?)");
        foreach ($categories as $category) {
            $stmt->execute([$category[0], $category[1], $category[2]]);
        }
        echo "Sample categories inserted.\n";
        
        // Create a default admin user with secure password
        $secure_password = bin2hex(random_bytes(16)); // Generate random 32-character password
        $admin_password = password_hash($secure_password, PASSWORD_DEFAULT);
        
        // Display admin credentials securely (console only)
        echo "\n" . str_repeat("=", 50) . "\n";
        echo "🔐 ADMIN CREDENTIALS (SAVE THESE SECURELY)\n";
        echo str_repeat("=", 50) . "\n";
        echo "Username: admin\n";
        echo "Password: " . $secure_password . "\n";
        echo str_repeat("=", 50) . "\n";
        echo "⚠️  These credentials are shown ONCE ONLY!\n";
        echo "📝 Please save them in a secure password manager.\n";
        echo str_repeat("=", 50) . "\n\n";
        
        $stmt = $conn->prepare("INSERT IGNORE INTO users (username, email, password, first_name, last_name, country) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute(['admin', 'admin@marketnest.com', $admin_password, 'Admin', 'User', 'Cameroon']);
        
        // Get admin user ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $admin_id = $result['id'];
            
            // Assign admin role using the proper user_roles table
            $stmt = $conn->prepare("INSERT IGNORE INTO user_roles (user_id, role) VALUES (?, ?)");
            $stmt->execute([$admin_id, 'admin']);
            
            echo "Admin user created/updated successfully.\n";
        }
        
        echo "Sample data inserted successfully.\n";
        
    } catch (Exception $e) {
        echo "Error inserting sample data: " . $e->getMessage() . "\n";
    }
}

// Test connection first
function testMySQLConnection() {
    try {
        $database = new Database();
        $conn = $database->getConnectionWithoutDb();
        echo "✓ MySQL connection test successful\n";
        return true;
    } catch (Exception $e) {
        echo "✗ MySQL connection test failed: " . $e->getMessage() . "\n";
        echo "\nPlease check your MySQL configuration:\n";
        echo "- For external MySQL: Set MYSQL_HOST, MYSQL_DATABASE, MYSQL_USER, MYSQL_PASSWORD\n";
        echo "- For local MySQL: Ensure MySQL server is running on localhost:3306\n";
        return false;
    }
}

// Run the initialization
echo "Testing MySQL connection...\n";
if (testMySQLConnection()) {
    echo "\nInitializing MySQL database for Market Nest...\n";
    initializeMySQLDatabase();
} else {
    echo "\nPlease fix the connection issues before running initialization.\n";
}
?>