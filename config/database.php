<?php
class Database {
    private $conn;
    private $error_message;

    public function getConnection() {
        if ($this->conn) {
            return $this->conn;
        }
        
        // Check for individual PostgreSQL environment variables first (more reliable)
        $host = $_ENV['PGHOST'] ?? getenv('PGHOST');
        $db_name = $_ENV['PGDATABASE'] ?? getenv('PGDATABASE');
        $username = $_ENV['PGUSER'] ?? getenv('PGUSER');
        $password = $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD');
        $port = $_ENV['PGPORT'] ?? getenv('PGPORT') ?? 5432;
        
        if ($host && $db_name && $username && $password) {
            try {
                $dsn = "pgsql:host={$host};port={$port};dbname={$db_name};sslmode=require";
                $this->conn = new PDO(
                    $dsn,
                    $username,
                    $password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_TIMEOUT => 10
                    )
                );
                return $this->conn;
            } catch (PDOException $e) {
                $this->error_message = 'PostgreSQL connection failed (individual env vars)';
                error_log($this->error_message . ': ' . $e->getMessage());
            }
        }
        
        // Fallback: Check for DATABASE_URL
        $database_url = $_ENV['DATABASE_URL'] ?? getenv('DATABASE_URL');
        if ($database_url) {
            // Parse Replit DATABASE_URL format: postgresql://user:password@host:port/database?params
            $url = str_replace('postgresql://', 'postgres://', $database_url);
            $parsed = parse_url($url);
            
            if ($parsed && isset($parsed['host'], $parsed['user'], $parsed['pass'], $parsed['path'])) {
                $host = $parsed['host'];
                $port = $parsed['port'] ?? 5432;
                $dbname = ltrim($parsed['path'], '/');
                $username = $parsed['user'];
                $password = $parsed['pass'];
                
                // Parse query parameters for SSL and other options
                $options = [];
                $query_params = [];
                if (isset($parsed['query'])) {
                    parse_str($parsed['query'], $query_params);
                    if (isset($query_params['sslmode'])) {
                        $options['sslmode'] = $query_params['sslmode'];
                    }
                }
                
                // Build DSN with query parameters
                $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
                if (isset($options['sslmode'])) {
                    $dsn .= ";sslmode={$options['sslmode']}";
                } else {
                    $dsn .= ";sslmode=require";
                }
                
                try {
                    $this->conn = new PDO(
                        $dsn,
                        $username,
                        $password,
                        array(
                            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                            PDO::ATTR_TIMEOUT => 10
                        )
                    );
                    return $this->conn;
                } catch (PDOException $e) {
                    $this->error_message = 'PostgreSQL connection failed (DATABASE_URL)';
                    error_log($this->error_message . ': ' . $e->getMessage());
                }
            }
        }
        
        // Fallback to local MySQL for XAMPP environment (for local development)
        $host = 'localhost';
        $db_name = 'ecommerce_db';
        $username = 'root';
        $password = '';
        $port = 3306;
        
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
                $this->error_message = 'MySQL connection failed';
                error_log($this->error_message . ': ' . $e2->getMessage());
                throw new Exception('Database connection failed');
            }
        }
        return $this->conn;
    }
    
    public function getConnectionWithoutDb() {
        // PostgreSQL connection without specific database (for Replit)
        $host = $_ENV['PGHOST'] ?? getenv('PGHOST');
        $username = $_ENV['PGUSER'] ?? getenv('PGUSER');
        $password = $_ENV['PGPASSWORD'] ?? getenv('PGPASSWORD');
        $port = $_ENV['PGPORT'] ?? getenv('PGPORT') ?? 5432;
        
        if ($host && $username && $password) {
            try {
                $dsn = "pgsql:host={$host};port={$port};sslmode=require";
                $conn = new PDO(
                    $dsn,
                    $username,
                    $password,
                    array(
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                    )
                );
                return $conn;
            } catch (PDOException $e) {
                error_log('PostgreSQL connection failed (no database): ' . $e->getMessage());
                throw new Exception('Database connection failed');
            }
        }
        
        // Fallback to MySQL for local development
        $host = 'localhost';
        $username = 'root';
        $password = '';
        $port = 3306;
        
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
            error_log('MySQL connection failed: ' . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }

    public function getLastError() {
        return $this->error_message;
    }
}
?>