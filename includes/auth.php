<?php
require_once __DIR__ . '/../config/config.php';

class Auth {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function register($username, $email, $password, $first_name, $last_name, $phone = '', $address = '', $city = '', $state = '', $zip_code = '') {
        try {
            // Check if user already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $stmt->execute([$username, $email]);
            
            if ($stmt->fetch()) {
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
            // First get user info
            $stmt = $this->db->prepare("
                SELECT * FROM users 
                WHERE username = ? OR email = ?
                LIMIT 1
            ");
            $stmt->execute([$username, $username]);
            
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user && password_verify($password, $user['password'])) {
                // Get all roles for the user
                $stmt = $this->db->prepare("
                    SELECT role FROM user_roles 
                    WHERE user_id = ?
                    ORDER BY 
                        CASE role 
                            WHEN 'admin' THEN 1
                            WHEN 'vendor' THEN 2
                            WHEN 'customer' THEN 3
                            ELSE 4
                        END
                ");
                $stmt->execute([$user['id']]);
                $roles = $stmt->fetchAll(PDO::FETCH_COLUMN);
                
                // Use the highest priority role (admin > vendor > customer)
                $primary_role = !empty($roles) ? $roles[0] : 'customer';
                
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['user_role'] = $primary_role;
                $_SESSION['user_roles'] = $roles; // Store all roles for advanced checking
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                
                return ['success' => true, 'message' => 'Login successful'];
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
