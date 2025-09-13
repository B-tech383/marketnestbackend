<?php
class Database {
    private $db_path;
    private $conn;

    public function __construct() {
        $this->db_path = __DIR__ . '/../database/ecommerce.db';
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
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                )
            );
            
            // Enable foreign key constraints for SQLite
            $this->conn->exec("PRAGMA foreign_keys = ON");
            
        } catch (PDOException $e) {
            die('SQLite connection failed: ' . $e->getMessage());
        }
        return $this->conn;
    }
}
?>