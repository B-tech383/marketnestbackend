<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'ecommerce_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        
        // Try MySQL first, fallback to SQLite if MySQL is not available
        try {
            $this->conn = new PDO(
                "mysql:host=" . $this->host . ";dbname=" . $this->db_name . ";charset=utf8mb4",
                $this->username,
                $this->password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
            
        } catch(PDOException $mysql_exception) {
            // Fallback to SQLite if MySQL is not available
            try {
                $sqlite_path = __DIR__ . '/../database/ecommerce.db';
                
                // Create database directory if it doesn't exist
                $db_dir = dirname($sqlite_path);
                if (!is_dir($db_dir)) {
                    mkdir($db_dir, 0755, true);
                }
                
                $this->conn = new PDO(
                    "sqlite:" . $sqlite_path,
                    null,
                    null,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false
                    )
                );
                
                // Enable foreign key constraints for SQLite
                $this->conn->exec("PRAGMA foreign_keys = ON");
                
                // Initialize SQLite database if it's empty
                $this->initializeSQLiteIfEmpty();
                
            } catch(PDOException $sqlite_exception) {
                echo "Database connection error: " . $mysql_exception->getMessage() . " (MySQL), " . $sqlite_exception->getMessage() . " (SQLite)";
            }
        }
        
        return $this->conn;
    }
    
    private function initializeSQLiteIfEmpty() {
        try {
            // Check if tables exist
            $result = $this->conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            if ($result->rowCount() == 0) {
                // Database is empty, initialize it directly here to avoid recursion
                $schema = file_get_contents(__DIR__ . '/../database/schema_sqlite.sql');
                $statements = explode(';', $schema);
                
                foreach ($statements as $statement) {
                    $statement = trim($statement);
                    if (!empty($statement)) {
                        $this->conn->exec($statement);
                    }
                }
                
                // Insert basic sample data
                $this->insertSampleData();
            }
        } catch (Exception $e) {
            // Ignore initialization errors
        }
    }
    
    private function insertSampleData() {
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
            
            $stmt = $this->conn->prepare("INSERT OR IGNORE INTO categories (name, description, icon) VALUES (?, ?, ?)");
            foreach ($categories as $category) {
                $stmt->execute([$category[0], $category[1], $category[2]]);
            }
            
            // Create a default admin user
            $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
            $stmt = $this->conn->prepare("INSERT OR IGNORE INTO users (username, email, password, first_name, last_name, role) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute(['admin', 'admin@marketnest.com', $admin_password, 'Admin', 'User', 'admin']);
        } catch (Exception $e) {
            // Ignore sample data errors
        }
    }
}
?>