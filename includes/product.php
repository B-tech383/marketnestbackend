<?php
require_once __DIR__ . '/../config/config.php';

class ProductManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function add_product($vendor_id, $category_id, $name, $description, $price, $sale_price, $stock_quantity, $sku, $images, $is_featured = false, $is_flash_deal = false, $flash_deal_end = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO products (vendor_id, category_id, name, description, price, sale_price, stock_quantity, sku, images, is_featured, is_flash_deal, flash_deal_end) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $images_json = json_encode($images);
            
            $stmt->execute([
                $vendor_id, $category_id, $name, $description, $price, 
                $sale_price, $stock_quantity, $sku, $images_json, 
                $is_featured, $is_flash_deal, $flash_deal_end
            ]);
            
            return ['success' => true, 'message' => 'Product added successfully', 'product_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()];
        }
    }
    
    public function get_products($limit = 20, $offset = 0, $category_id = null, $search = null, $vendor_id = null, $featured_only = false) {
        try {
            $where_conditions = ["p.status = 'active'"];
            $params = [];
            
            if ($category_id) {
                $where_conditions[] = "p.category_id = ?";
                $params[] = $category_id;
            }
            
            if ($search) {
                $where_conditions[] = "(p.name LIKE ? OR p.description LIKE ?)";
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            
            if ($vendor_id) {
                $where_conditions[] = "p.vendor_id = ?";
                $params[] = $vendor_id;
            }
            
            if ($featured_only) {
                $where_conditions[] = "p.is_featured = 1";
            }
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $stmt = $this->db->prepare("
                SELECT p.*, c.name as category_name, v.business_name, v.is_verified, v.verification_badge,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN vendors v ON p.vendor_id = v.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE $where_clause
                GROUP BY p.id
                ORDER BY v.is_verified DESC, p.is_featured DESC, p.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode images JSON for each product
            foreach ($products as &$product) {
                $product['images'] = json_decode($product['images'], true) ?: [];
                $product['avg_rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_product_by_id($product_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name as category_name, v.business_name, v.is_verified, v.verification_badge, v.logo_path as vendor_logo,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN vendors v ON p.vendor_id = v.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.id = ? AND p.status = 'active'
                GROUP BY p.id
            ");
            
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $product['images'] = json_decode($product['images'], true) ?: [];
                $product['avg_rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
            }
            
            return $product;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function get_categories() {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id AND p.status = 'active'
                GROUP BY c.id 
                ORDER BY c.name
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_flash_deals($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name as category_name, v.business_name, v.is_verified,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN vendors v ON p.vendor_id = v.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.is_flash_deal = 1 AND p.flash_deal_end > NOW() AND p.status = 'active'
                GROUP BY p.id
                ORDER BY p.flash_deal_end ASC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = json_decode($product['images'], true) ?: [];
                $product['avg_rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function add_to_recently_viewed($user_id, $product_id) {
        if (!$user_id) return;
        
        try {
            // Remove if already exists
            $stmt = $this->db->prepare("DELETE FROM recently_viewed WHERE user_id = ? AND product_id = ?");
            $stmt->execute([$user_id, $product_id]);
            
            // Add new entry
            $stmt = $this->db->prepare("INSERT INTO recently_viewed (user_id, product_id) VALUES (?, ?)");
            $stmt->execute([$user_id, $product_id]);
            
            // Keep only last 10 items
            $stmt = $this->db->prepare("
                DELETE FROM recently_viewed 
                WHERE user_id = ? AND id NOT IN (
                    SELECT id FROM (
                        SELECT id FROM recently_viewed 
                        WHERE user_id = ? 
                        ORDER BY viewed_at DESC 
                        LIMIT 10
                    ) as temp
                )
            ");
            $stmt->execute([$user_id, $user_id]);
            
        } catch (PDOException $e) {
            // Ignore errors for recently viewed
        }
    }
    
    public function get_recently_viewed($user_id, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, rv.viewed_at
                FROM recently_viewed rv
                JOIN products p ON rv.product_id = p.id
                WHERE rv.user_id = ? AND p.status = 'active'
                ORDER BY rv.viewed_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = json_decode($product['images'], true) ?: [];
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function add_review($user_id, $product_id, $order_id, $rating, $title, $comment) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO reviews (user_id, product_id, order_id, rating, title, comment) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$user_id, $product_id, $order_id, $rating, $title, $comment]);
            
            return ['success' => true, 'message' => 'Review added successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add review: ' . $e->getMessage()];
        }
    }
    
    public function getFeaturedProducts($limit = 10) {
        return $this->get_products($limit, 0, null, null, null, true);
    }
    
    public function getCategories() {
        return $this->get_categories();
    }
    
    public function getFlashDeals($limit = 10) {
        return $this->get_flash_deals($limit);
    }
    
    public function get_product_reviews($product_id, $limit = 10, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT r.*, u.first_name, u.last_name, u.username
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$product_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
