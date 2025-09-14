<?php
class Database {
    private $conn;
    private $error_message;

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        // MySQL configuration - check for custom environment variables first
        $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? 'localhost';
        $db_name = $_ENV['MYSQL_DATABASE'] ?? getenv('MYSQL_DATABASE') ?? 'ecommerce_db';
        $username = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? 'root';
        $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '';
        $port = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? 3306;
        
        try {
            // First, try to connect to the specific database
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db_name . ";charset=utf8mb4";
            $this->conn = new PDO(
                $dsn,
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_TIMEOUT => 10
                )
            );
            
        } catch (PDOException $e) {
            // If database doesn't exist, try to connect without database name and create it
            try {
                $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8mb4";
                $this->conn = new PDO(
                    $dsn,
                    $username,
                    $password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                        PDO::ATTR_TIMEOUT => 10
                    )
                );
                
                // Create database if it doesn't exist
                $this->conn->exec("CREATE DATABASE IF NOT EXISTS {$db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->conn->exec("USE {$db_name}");
                
            } catch (PDOException $e2) {
                $this->error_message = 'MySQL connection failed';
                error_log($this->error_message . ': ' . $e2->getMessage());
                throw new Exception('Database connection failed: ' . $e2->getMessage());
            }
        }
        return $this->conn;
    }
    
    public function getConnectionWithoutDb() {
        // MySQL connection without specific database
        $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST') ?? 'localhost';
        $username = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER') ?? 'root';
        $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD') ?? '';
        $port = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? 3306;
        
        try {
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8mb4";
            $conn = new PDO(
                $dsn,
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
                    PDO::ATTR_TIMEOUT => 10
                )
            );
            return $conn;
        } catch (PDOException $e) {
            error_log('MySQL connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }

    public function getLastError() {
        return $this->error_message;
    }
}
?>