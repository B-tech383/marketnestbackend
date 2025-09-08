<?php
class Database {
    private $db_path;
    private $conn;

    public function __construct() {
        // Use SQLite for development
        $this->db_path = __DIR__ . '/../database/ecommerce.db';
        
        // Create database directory if it doesn't exist
        $db_dir = dirname($this->db_path);
        if (!is_dir($db_dir)) {
            mkdir($db_dir, 0755, true);
        }
    }

    public function getConnection() {
        $this->conn = null;
        
        try {
            $this->conn = new PDO(
                "sqlite:" . $this->db_path,
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
            
        } catch(PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }
        
        return $this->conn;
    }
}
?>
