<?php
class Database {
    private $conn;
    private $error_message;

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        // Check for MySQL configuration first
        $host = 'localhost';
        $db_name = 'ecommerce_db';
        $username = 'root';
        $password = '';
        $port = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? 3306;
        
        // SSL options for external providers
        $ssl_ca = $_ENV['MYSQL_SSL_CA'] ?? getenv('MYSQL_SSL_CA');
        $ssl_cert = $_ENV['MYSQL_SSL_CERT'] ?? getenv('MYSQL_SSL_CERT');
        $ssl_key = $_ENV['MYSQL_SSL_KEY'] ?? getenv('MYSQL_SSL_KEY');
        $ssl_verify = $_ENV['MYSQL_SSL_VERIFY'] ?? getenv('MYSQL_SSL_VERIFY') ?? true;
        $allow_db_create = $_ENV['MYSQL_ALLOW_DB_CREATE'] ?? getenv('MYSQL_ALLOW_DB_CREATE') ?? 'true';
        
        // If MySQL environment variables are set, try MySQL connection
        if ($host && $db_name && $username) {
            $is_external = $host !== 'localhost';
            
            try {
                // First, try to connect to the specific database
                $dsn = "mysql:host=" . $host . ";port=" . $port . ";dbname=" . $db_name . ";charset=utf8mb4";
                $this->conn = new PDO(
                    $dsn,
                    $username,
                    $password,
                    $this->getPDOOptions($ssl_ca, $ssl_cert, $ssl_key, $ssl_verify)
                );
                return $this->conn;
                
            } catch (PDOException $e) {
                // If database doesn't exist, try to connect without database name and create it
                // Only for local MySQL, external providers usually don't allow CREATE DATABASE
                if ($allow_db_create === 'true' && !$is_external) {
                    try {
                        $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8mb4";
                        $this->conn = new PDO(
                            $dsn,
                            $username,
                            $password,
                            $this->getPDOOptions($ssl_ca, $ssl_cert, $ssl_key, $ssl_verify)
                        );
                        
                        // Create database if it doesn't exist
                        $this->conn->exec("CREATE DATABASE IF NOT EXISTS {$db_name} CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
                        $this->conn->exec("USE {$db_name}");
                        return $this->conn;
                        
                    } catch (PDOException $e2) {
                        // Fall through to SQLite fallback
                        error_log('MySQL connection failed, falling back to SQLite: ' . $e2->getMessage());
                    }
                } else {
                    // Fall through to SQLite fallback
                    error_log('External MySQL connection failed, falling back to SQLite: ' . $e->getMessage());
                }
            }
        }
        
        // Fallback to SQLite if MySQL is not configured or fails
        try {
            $sqlite_path = __DIR__ . '/../data/ecommerce.db';
            $dsn = "sqlite:" . $sqlite_path;
            $this->conn = new PDO(
                $dsn,
                null,
                null,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                ]
            );
            
            // Enable foreign keys for SQLite
            $this->conn->exec("PRAGMA foreign_keys = ON");
            
            return $this->conn;
            
        } catch (PDOException $e) {
            $this->error_message = 'Database connection failed';
            error_log($this->error_message . ': ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public function getConnectionWithoutDb() {
        // MySQL configuration - check environment variables
        $host = $_ENV['MYSQL_HOST'] ?? getenv('MYSQL_HOST');
        $username = $_ENV['MYSQL_USER'] ?? getenv('MYSQL_USER');
        $password = $_ENV['MYSQL_PASSWORD'] ?? getenv('MYSQL_PASSWORD');
        $port = $_ENV['MYSQL_PORT'] ?? getenv('MYSQL_PORT') ?? 3306;
        
        // SSL options for external providers
        $ssl_ca = $_ENV['MYSQL_SSL_CA'] ?? getenv('MYSQL_SSL_CA');
        $ssl_cert = $_ENV['MYSQL_SSL_CERT'] ?? getenv('MYSQL_SSL_CERT');
        $ssl_key = $_ENV['MYSQL_SSL_KEY'] ?? getenv('MYSQL_SSL_KEY');
        $ssl_verify = $_ENV['MYSQL_SSL_VERIFY'] ?? getenv('MYSQL_SSL_VERIFY') ?? true;
        
        // If any MySQL env var is set, require all critical ones
        if ($host || $username) {
            if (!$host || !$username) {
                throw new Exception('MySQL configuration incomplete. When using external MySQL, both MYSQL_HOST and MYSQL_USER must be set.');
            }
        } else {
            // Default to local MySQL for development
            $host = 'localhost';
            $username = 'root';
            $password = '';
        }
        
        try {
            $dsn = "mysql:host=" . $host . ";port=" . $port . ";charset=utf8mb4";
            $conn = new PDO(
                $dsn,
                $username,
                $password,
                $this->getPDOOptions($ssl_ca, $ssl_cert, $ssl_key, $ssl_verify)
            );
            return $conn;
        } catch (PDOException $e) {
            error_log('MySQL connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    private function getPDOOptions($ssl_ca = null, $ssl_cert = null, $ssl_key = null, $ssl_verify = true) {
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4",
            PDO::ATTR_TIMEOUT => 10
        ];
        
        // Add SSL options for external providers
        if ($ssl_ca) {
            $options[PDO::MYSQL_ATTR_SSL_CA] = $ssl_ca;
        }
        
        if ($ssl_cert) {
            $options[PDO::MYSQL_ATTR_SSL_CERT] = $ssl_cert;
        }
        
        if ($ssl_key) {
            $options[PDO::MYSQL_ATTR_SSL_KEY] = $ssl_key;
        }
        
        // Handle SSL verification (check if constant exists for compatibility)
        if (!$ssl_verify && defined('PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT')) {
            $options[PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT] = false;
        }
        
        return $options;
    }

    public function getLastError() {
        return $this->error_message;
    }
}
?>