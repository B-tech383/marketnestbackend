<?php
class Database {
    private $host = 'localhost';
    private $db_name = 'ecommerce_db';
    private $username = 'root';
    private $password = '';
    private $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // First, try to connect to the specific database
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
            
        } catch (PDOException $e) {
            // If database doesn't exist, try to connect without database name
            try {
                $this->conn = new PDO(
                    "mysql:host=" . $this->host . ";charset=utf8mb4",
                    $this->username,
                    $this->password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                    )
                );
                
                // Create database if it doesn't exist
                $this->conn->exec("CREATE DATABASE IF NOT EXISTS {$this->db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                $this->conn->exec("USE {$this->db_name}");
                
            } catch (PDOException $e2) {
                die('MySQL connection failed: ' . $e2->getMessage());
            }
        }
        return $this->conn;
    }
    
    public function getConnectionWithoutDb() {
        try {
            $conn = new PDO(
                "mysql:host=" . $this->host . ";charset=utf8mb4",
                $this->username,
                $this->password,
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