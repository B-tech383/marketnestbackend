<?php
require_once __DIR__ . '/../config/config.php';

class CartManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function add_to_cart($user_id, $product_id, $quantity = 1) {
        try {
            // Check if product exists and is active
            $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ? AND status = 'active'");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$product) {
                return ['success' => false, 'message' => 'Product not found'];
            }
            
            // Check if item already in cart
            $stmt = $this->db->prepare("SELECT quantity FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existing) {
                $new_quantity = $existing['quantity'] + $quantity;
                
                if ($new_quantity > $product['stock_quantity']) {
                    return ['success' => false, 'message' => 'Not enough stock available'];
                }
                
                $stmt = $this->db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
                $stmt->execute([$new_quantity, $user_id, $product_id]);
            } else {
                if ($quantity > $product['stock_quantity']) {
                    return ['success' => false, 'message' => 'Not enough stock available'];
                }
                
                $stmt = $this->db->prepare("INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $product_id, $quantity]);
            }
            
            return ['success' => true, 'message' => 'Item added to cart'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add to cart: ' . $e->getMessage()];
        }
    }
    
    public function get_cart_items($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, p.name, p.price, p.sale_price, p.images, p.stock_quantity, v.business_name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                JOIN vendors v ON p.vendor_id = v.id
                WHERE c.user_id = ? AND p.status = 'active'
                ORDER BY c.updated_at DESC
            ");
            
            $stmt->execute([$user_id]);
            $items = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($items as &$item) {
                $item['images'] = json_decode($item['images'], true) ?: [];
                $item['current_price'] = $item['sale_price'] ?: $item['price'];
                $item['total_price'] = $item['current_price'] * $item['quantity'];
            }
            
            return $items;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function update_cart_quantity($user_id, $product_id, $quantity) {
        try {
            if ($quantity <= 0) {
                return $this->remove_from_cart($user_id, $product_id);
            }
            
            // Check stock
            $stmt = $this->db->prepare("SELECT stock_quantity FROM products WHERE id = ?");
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($quantity > $product['stock_quantity']) {
                return ['success' => false, 'message' => 'Not enough stock available'];
            }
            
            $stmt = $this->db->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$quantity, $user_id, $product_id]);
            
            return ['success' => true, 'message' => 'Cart updated'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update cart: ' . $e->getMessage()];
        }
    }
    
    public function remove_from_cart($user_id, $product_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            return ['success' => true, 'message' => 'Item removed from cart'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to remove from cart: ' . $e->getMessage()];
        }
    }
    
    public function get_cart_count($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT SUM(quantity) as total FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?: 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function get_cart_total($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(c.quantity * COALESCE(p.sale_price, p.price)) as total
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.status = 'active'
            ");
            
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            return $result['total'] ?: 0;
            
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function clear_cart($user_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            return ['success' => true, 'message' => 'Cart cleared'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to clear cart'];
        }
    }
}
?>
