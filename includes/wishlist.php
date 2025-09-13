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
            // Check if already in wishlist
            if ($this->is_in_wishlist($user_id, $product_id)) {
                return ['success' => false, 'message' => 'Item already in wishlist'];
            }
            
            $stmt = $this->db->prepare("INSERT INTO wishlist (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $product_id]);
            
            return ['success' => true, 'message' => 'Added to wishlist'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add to wishlist: ' . $e->getMessage()];
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
                WHERE w.user_id = ? AND p.status = 'active' AND p.admin_approved = 1
                ORDER BY w.created_at DESC
            ");
            
            $stmt->execute([$user_id]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $imgs = $product['images'] ? json_decode($product['images'], true) ?: [] : [];
                $normalized = [];
                foreach ($imgs as $img) {
                    if (!$img) continue;
                    if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                        $normalized[] = $img;
                    } elseif (strpos($img, 'uploads/') === 0) {
                        $normalized[] = $img;
                    } else {
                        $normalized[] = 'uploads/products/' . basename($img);
                    }
                }
                $product['images'] = $normalized;
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
    
    public function getWishlistCount($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM wishlist WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
}
?>
