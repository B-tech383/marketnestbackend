<?php
require_once __DIR__ . '/../config/config.php';

class WishlistManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function add_to_wishlist($user_id, $product_id) {
        try {
            // Use INSERT OR IGNORE for SQLite compatibility, but will work with MySQL too
            $stmt = $this->db->prepare("INSERT OR IGNORE INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $product_id]);
            
            return ['success' => true, 'message' => 'Added to wishlist'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add to wishlist'];
        }
    }
    
    public function remove_from_wishlist($user_id, $product_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            return ['success' => true, 'message' => 'Removed from wishlist'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to remove from wishlist'];
        }
    }
    
    public function get_wishlist($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, w.created_at as added_at
                FROM wishlist w
                JOIN products p ON w.product_id = p.id
                WHERE w.user_id = ? AND p.status = 'active'
                ORDER BY w.created_at DESC
            ");
            
            $stmt->execute([$user_id]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = $product['images'] ? json_decode($product['images'], true) ?: [] : [];
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function is_in_wishlist($user_id, $product_id) {
        try {
            $stmt = $this->db->prepare("SELECT id FROM wishlist WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            return false;
        }
    }
}
?>
