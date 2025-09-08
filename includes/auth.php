<?php
require_once '../config/config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function register($username, $email, $password, $first_name, $last_name, $phone = '', $address = '', $city = '', $state = '', $zip_code = '') {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }
            
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            // Insert user
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $email, $hashed_password, $first_name, $last_name, $phone, $address, $city, $state, $zip_code]);
            $user_id = $this->db->lastInsertId();
            
            // Add customer role
            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'customer')");
            $stmt->execute([$user_id]);
            
            return ['success' => true, 'message' => 'Registration successful'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Registration failed: ' . $e->getMessage()];
        }
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, ur.role 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                WHERE u.username = ? OR u.email = ?
            ");
            $stmt->execute([$username, $username]);
            
            if ($stmt->rowCount() == 1) {
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
                
                if (password_verify($password, $user['password'])) {
                    // Set session variables
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['user_role'] = $user['role'] ?? 'customer';
                    $_SESSION['first_name'] = $user['first_name'];
                    $_SESSION['last_name'] = $user['last_name'];
                    
                    return ['success' => true, 'message' => 'Login successful'];
                }
            }
            
            return ['success' => false, 'message' => 'Invalid username or password'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Login failed: ' . $e->getMessage()];
        }
    }
    
    public function logout() {
        session_destroy();
        return ['success' => true, 'message' => 'Logged out successfully'];
    }
    
    public function get_user_info($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT u.*, ur.role 
                FROM users u 
                LEFT JOIN user_roles ur ON u.id = ur.user_id 
                WHERE u.id = ?
            ");
            $stmt->execute([$user_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
