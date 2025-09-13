<?php
class Database {
    private $conn;

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        // MySQL configuration for all environments
        // Check for external MySQL environment variables first
        if (isset($_ENV['MYSQL_HOST']) && isset($_ENV['MYSQL_DATABASE']) && isset($_ENV['MYSQL_USER']) && isset($_ENV['MYSQL_PASSWORD'])) {
            $host = $_ENV['MYSQL_HOST'];
            $db_name = $_ENV['MYSQL_DATABASE'];
            $username = $_ENV['MYSQL_USER'];
            $password = $_ENV['MYSQL_PASSWORD'];
            $port = $_ENV['MYSQL_PORT'] ?? 3306;
        } else {
            // Fallback to local MySQL for XAMPP environment
            $host = 'localhost';
            $db_name = 'ecommerce_db';
            $username = 'root';
            $password = '';
            $port = 3306;
        }
        
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
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
            
        } catch (PDOException $e) {
            // If database doesn't exist, try to connect without database name
            try {
                $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8mb4";
                $this->conn = new PDO(
                    $dsn,
                    $username,
                    $password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    )
                );
                
                // Create database if it doesn't exist
                $this->conn->exec("CREATE DATABASE IF NOT EXISTS {$db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->conn->exec("USE {$db_name}");
                
            } catch (PDOException $e2) {
                die('MySQL connection failed: ' . $e2->getMessage());
            }
        }
        return $this->conn;
    }
    
    public function getConnectionWithoutDb() {
        // MySQL connection without specific database
        if (isset($_ENV['MYSQL_HOST']) && isset($_ENV['MYSQL_USER']) && isset($_ENV['MYSQL_PASSWORD'])) {
            $host = $_ENV['MYSQL_HOST'];
            $username = $_ENV['MYSQL_USER'];
            $password = $_ENV['MYSQL_PASSWORD'];
            $port = $_ENV['MYSQL_PORT'] ?? 3306;
        } else {
            $host = 'localhost';
            $username = 'root';
            $password = '';
            $port = 3306;
        }
        
        try {
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8mb4";
            $conn = new PDO(
                $dsn,
                $username,
                $password,
                array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                )
            );
            return $conn;
        } catch (PDOException $e) {
            die('MySQL connection failed: ' . $e->getMessage());
        }
    }
}
?>