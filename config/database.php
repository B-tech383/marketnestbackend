<?php
class Database {
    private $conn;

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        // Check if we're in Replit environment with PostgreSQL
        if (isset($_ENV['PGHOST']) && isset($_ENV['PGPORT']) && isset($_ENV['PGUSER']) && isset($_ENV['PGPASSWORD']) && isset($_ENV['PGDATABASE'])) {
            $dsn = "pgsql:host=" . $_ENV['PGHOST'] . ";port=" . $_ENV['PGPORT'] . ";dbname=" . $_ENV['PGDATABASE'];
            
            try {
                $this->conn = new PDO($dsn, $_ENV['PGUSER'], $_ENV['PGPASSWORD'], array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ));
                return $this->conn;
            } catch (PDOException $e) {
                die('PostgreSQL connection failed: ' . $e->getMessage());
            }
        }
        
        // Fallback to MySQL for local XAMPP environment
        $host = 'localhost';
        $db_name = 'ecommerce_db';
        $username = 'root';
        $password = '';
        
        try {
            // First, try to connect to the specific database
            $this->conn = new PDO(
                "mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8mb4",
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
                $this->conn = new PDO(
                    "mysql:host=" . $host . ";charset=utf8mb4",
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
        // In PostgreSQL environment, always use the main connection
        if (isset($_ENV['PGHOST'])) {
            return $this->getConnection();
        }
        
        // MySQL fallback for XAMPP
        $host = 'localhost';
        $username = 'root';
        $password = '';
        
        try {
            $conn = new PDO(
                "mysql:host=" . $host . ";charset=utf8mb4",
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