<?php
require_once __DIR__ . '/../config/database.php';

class CouponManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Create a new coupon
     */
    public function createCoupon($data) {
        try {
            // Check if coupon code already exists
            $stmt = $this->db->prepare("SELECT id FROM coupons WHERE code = ?");
            $stmt->execute([$data['code']]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Coupon code already exists. Please use a different code.'];
            }

            // Insert coupon (MySQL uses lastInsertId instead of RETURNING)
            $stmt = $this->db->prepare("
                INSERT INTO coupons 
                (code, name, description, type, value, minimum_amount, maximum_discount, usage_limit, is_active, starts_at, expires_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['description'] ?? '',
                $data['type'],
                $data['value'],
                $data['minimum_amount'] ?? 0,
                $data['maximum_discount'] ?? null,
                $data['usage_limit'] ?? null,
                $data['is_active'] ?? true,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null
            ]);

            $coupon_id = $this->db->lastInsertId(); // get the inserted coupon ID

            // If it's a free product coupon, add product associations
            if ($data['type'] === 'free_product' && !empty($data['product_ids'])) {
                $this->addProductsToCoupon($coupon_id, $data['product_ids']);
            }

            return ['success' => true, 'message' => 'Coupon created successfully', 'coupon_id' => $coupon_id];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create coupon: ' . $e->getMessage()];
        }
    }

    /**
     * Validate and apply a coupon
     */
    public function validateCoupon($code, $user_id, $cart_items = []) {
        try {
            // Get coupon details
            $stmt = $this->db->prepare("
                SELECT * FROM coupons 
                WHERE code = ? AND is_active = TRUE
            ");
            $stmt->execute([$code]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                return ['success' => false, 'message' => 'Invalid coupon code'];
            }
            
            // Check if coupon is within date range
            $now = date('Y-m-d H:i:s');
            if ($coupon['starts_at'] && $now < $coupon['starts_at']) {
                return ['success' => false, 'message' => 'Coupon is not yet active'];
            }
            if ($coupon['expires_at'] && $now > $coupon['expires_at']) {
                return ['success' => false, 'message' => 'Coupon has expired'];
            }
            
            // Check usage limit
            if ($coupon['usage_limit'] && $coupon['used_count'] >= $coupon['usage_limit']) {
                return ['success' => false, 'message' => 'Coupon usage limit reached'];
            }
            
            // Check if user has already used this coupon
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count FROM coupon_usage 
                WHERE coupon_id = ? AND user_id = ?
            ");
            $stmt->execute([$coupon['id'], $user_id]);
            $usage_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            
            if ($usage_count > 0) {
                return ['success' => false, 'message' => 'You have already used this coupon'];
            }
            
            // Calculate discount
            $discount = $this->calculateDiscount($coupon, $cart_items);
            
            if ($discount['amount'] <= 0) {
                return ['success' => false, 'message' => 'Coupon does not apply to your cart'];
            }
            
            return [
                'success' => true,
                'coupon' => $coupon,
                'discount' => $discount
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Error validating coupon: ' . $e->getMessage()];
        }
    }
    
    /**
     * Calculate discount amount
     */
    private function calculateDiscount($coupon, $cart_items) {
        $total_amount = 0;
        $applicable_items = [];
        
        // Calculate total amount and find applicable items
        foreach ($cart_items as $item) {
            $item_total = $item['price'] * $item['quantity'];
            $total_amount += $item_total;
            
            // For free product coupons, check if this product is eligible
            if ($coupon['type'] === 'free_product') {
                if ($this->isProductEligibleForCoupon($coupon['id'], $item['product_id'])) {
                    $applicable_items[] = $item;
                }
            } else {
                $applicable_items[] = $item;
            }
        }
        
        // Check minimum amount requirement
        if ($coupon['minimum_amount'] > 0 && $total_amount < $coupon['minimum_amount']) {
            return ['amount' => 0, 'message' => 'Minimum order amount not met'];
        }
        
        $discount_amount = 0;
        
        switch ($coupon['type']) {
            case 'percentage':
                $discount_amount = ($total_amount * $coupon['value']) / 100;
                if ($coupon['maximum_discount'] && $discount_amount > $coupon['maximum_discount']) {
                    $discount_amount = $coupon['maximum_discount'];
                }
                break;
                
            case 'fixed':
                $discount_amount = $coupon['value'];
                if ($discount_amount > $total_amount) {
                    $discount_amount = $total_amount;
                }
                break;
                
            case 'free_product':
                // For free product coupons, make the entire order free
                $discount_amount = $total_amount;
                break;
        }
        
        return [
            'amount' => $discount_amount,
            'applicable_items' => $applicable_items,
            'total_amount' => $total_amount
        ];
    }
    
    /**
     * Check if a product is eligible for a free product coupon
     */
    private function isProductEligibleForCoupon($coupon_id, $product_id) {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM coupon_products 
            WHERE coupon_id = ? AND product_id = ?
        ");
        $stmt->execute([$coupon_id, $product_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }
    
    /**
     * Record coupon usage
     */
    public function recordCouponUsage($coupon_id, $user_id, $order_id = null) {
        try {
            // Record usage
            $stmt = $this->db->prepare("
                INSERT INTO coupon_usage (coupon_id, user_id, order_id) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([$coupon_id, $user_id, $order_id]);
            
            // Update usage count
            $stmt = $this->db->prepare("
                UPDATE coupons SET used_count = used_count + 1 
                WHERE id = ?
            ");
            $stmt->execute([$coupon_id]);
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to record coupon usage: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get all coupons for admin
     */
    public function getAllCoupons($limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM coupons 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
            $stmt->bindValue(2, (int)$offset, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    /**
     * Get coupon by ID
     */
    public function getCouponById($coupon_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM coupons WHERE id = ?
            ");
            $stmt->execute([$coupon_id]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return null;
        }
    }
    
    /**
     * Update coupon
     */
    public function updateCoupon($coupon_id, $data) {
        try {
            // Check if the new code exists in another coupon
            $stmt = $this->db->prepare("SELECT id FROM coupons WHERE code = ? AND id != ?");
            $stmt->execute([$data['code'], $coupon_id]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                return ['success' => false, 'message' => 'Coupon code already exists. Please use a different code.'];
            }

            // Update coupon
            $stmt = $this->db->prepare("
                UPDATE coupons SET 
                    code = ?, name = ?, description = ?, type = ?, value = ?, 
                    minimum_amount = ?, maximum_discount = ?, usage_limit = ?, 
                    is_active = ?, start_date = ?, end_date = ?
                WHERE id = ?
            ");

            $stmt->execute([
                $data['code'],
                $data['name'],
                $data['description'] ?? '',
                $data['type'],
                $data['value'],
                $data['minimum_amount'] ?? 0,
                $data['maximum_discount'] ?? null,
                $data['usage_limit'] ?? null,
                $data['is_active'] ?? true,
                $data['start_date'] ?? null,
                $data['end_date'] ?? null,
                $coupon_id
            ]);

            return ['success' => true, 'message' => 'Coupon updated successfully'];

        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update coupon: ' . $e->getMessage()];
        }
    }


    
    /**
     * Delete coupon
     */
    public function deleteCoupon($coupon_id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM coupons WHERE id = ?");
            $stmt->execute([$coupon_id]);
            
            return ['success' => true, 'message' => 'Coupon deleted successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete coupon: ' . $e->getMessage()];
        }
    }
    
    /**
     * Add products to a coupon
     */
    public function addProductsToCoupon($coupon_id, $product_ids) {
        try {
            // Remove existing associations
            $stmt = $this->db->prepare("DELETE FROM coupon_products WHERE coupon_id = ?");
            $stmt->execute([$coupon_id]);
            
            // Add new associations
            $stmt = $this->db->prepare("INSERT INTO coupon_products (coupon_id, product_id) VALUES (?, ?)");
            foreach ($product_ids as $product_id) {
                $stmt->execute([$coupon_id, $product_id]);
            }
            
            return ['success' => true];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add products to coupon: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get products associated with a coupon
     */
    public function getCouponProducts($coupon_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.* FROM products p
                JOIN coupon_products cp ON p.id = cp.product_id
                WHERE cp.coupon_id = ?
            ");
            $stmt->execute([$coupon_id]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
