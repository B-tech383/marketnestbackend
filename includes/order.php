<?php
require_once __DIR__ . '/../config/config.php';

class OrderManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function create_order($user_id, $cart_items, $shipping_address, $billing_address, $payment_method, $coupon_code = null, $transaction_id = null) {
        try {
            $this->db->beginTransaction();
            
            // Calculate totals
            $subtotal = 0;
            foreach ($cart_items as $item) {
                $subtotal += $item['current_price'] * $item['quantity'];
            }
            
            $discount_amount = 0;
            $coupon_id = null;
            
            // Apply coupon if provided
            if ($coupon_code) {
                $coupon_result = $this->apply_coupon($coupon_code, $subtotal);
                if ($coupon_result['success']) {
                    $discount_amount = $coupon_result['discount'];
                    $coupon_id = $coupon_result['coupon_id'];
                }
            }
            
            $tax_amount = ($subtotal - $discount_amount) * 0.08; // 8% tax
            $shipping_amount = 0; // Free shipping
            $total_amount = $subtotal + $tax_amount + $shipping_amount - $discount_amount;
            
            // Generate order number
            $order_number = 'ORD-' . date('Y') . '-' . str_pad(rand(1, 999999), 6, '0', STR_PAD_LEFT);
            
            // Set order and payment status based on payment method
            if ($payment_method == 'mobile_money_cameroon') {
                $order_status = 'pending'; // Pending admin approval
                $payment_status = 'pending'; // Pending verification
            } else {
                $order_status = 'processing'; // Other payments process immediately
                $payment_status = 'paid';
            }
            
            // Create order
            $stmt = $this->db->prepare("
                INSERT INTO orders (user_id, order_number, total_amount, tax_amount, shipping_amount, discount_amount, shipping_address, billing_address, status, payment_status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $user_id, $order_number, $total_amount, $tax_amount, 
                $shipping_amount, $discount_amount, $shipping_address, $billing_address, $order_status, $payment_status
            ]);
            
            $order_id = $this->db->lastInsertId();
            
            // Create order items and commission transactions
            foreach ($cart_items as $item) {
                $stmt = $this->db->prepare("
                    INSERT INTO order_items (order_id, product_id, vendor_id, quantity, price, total) 
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $item_total = $item['current_price'] * $item['quantity'];
                
                // Get vendor_id from product
                $stmt2 = $this->db->prepare("SELECT vendor_id FROM products WHERE id = ?");
                $stmt2->execute([$item['product_id']]);
                $vendor_id = $stmt2->fetch(PDO::FETCH_ASSOC)['vendor_id'];
                
                $stmt->execute([
                    $order_id, $item['product_id'], $vendor_id, 
                    $item['quantity'], $item['current_price'], $item_total
                ]);
                
                $order_item_id = $this->db->lastInsertId();
                
                // Create commission transaction using rate at time of order
                $this->create_commission_transaction($order_id, $order_item_id, $vendor_id, $item['product_id'], $item_total);
                
                // Update product stock
                $stmt = $this->db->prepare("UPDATE products SET stock_quantity = stock_quantity - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['product_id']]);
            }
            
            // Create payment record
            if ($payment_method == 'mobile_money_cameroon') {
                // For mobile money, store transaction ID and set pending status
                $stmt = $this->db->prepare("
                    INSERT INTO payments (order_id, user_id, amount, payment_method, status, transaction_id) 
                    VALUES (?, ?, ?, ?, 'pending', ?)
                ");
                $stmt->execute([$order_id, $user_id, $total_amount, $payment_method, $transaction_id]);
                
                // Keep order status as pending for admin approval
            } else {
                // For other payment methods, mark as completed
                $stmt = $this->db->prepare("
                    INSERT INTO payments (order_id, user_id, amount, payment_method, status) 
                    VALUES (?, ?, ?, ?, 'paid')
                ");
                $stmt->execute([$order_id, $user_id, $total_amount, $payment_method]);
                
                // Update order payment status (already set correctly in order creation above)
            }
            
            // Update coupon usage if applied
            if ($coupon_id) {
                $stmt = $this->db->prepare("UPDATE coupons SET used_count = used_count + 1 WHERE id = ?");
                $stmt->execute([$coupon_id]);
            }
            
            // Create shipment record
            $tracking_number = 'TRK-' . date('Ymd') . '-' . str_pad(rand(1, 99999), 5, '0', STR_PAD_LEFT);
            
            if ($payment_method == 'mobile_money_cameroon') {
                // For mobile money, shipment is pending admin approval
                $stmt = $this->db->prepare("
                    INSERT INTO shipments (order_id, tracking_number, carrier, status) 
                    VALUES (?, ?, 'Standard Shipping', 'pending_approval')
                ");
                $stmt->execute([$order_id, $tracking_number]);
                
                $shipment_id = $this->db->lastInsertId();
                
                // Add initial tracking history for pending payment
                $stmt = $this->db->prepare("
                    INSERT INTO tracking_history (shipment_id, status, description) 
                    VALUES (?, 'Payment Verification', 'Order placed. Awaiting mobile money payment verification by admin.')
                ");
                $stmt->execute([$shipment_id]);
            } else {
                // For other payment methods, normal processing
                $stmt = $this->db->prepare("
                    INSERT INTO shipments (order_id, tracking_number, carrier, status) 
                    VALUES (?, ?, 'Standard Shipping', 'pending')
                ");
                $stmt->execute([$order_id, $tracking_number]);
                
                $shipment_id = $this->db->lastInsertId();
                
                // Add initial tracking history
                $stmt = $this->db->prepare("
                    INSERT INTO tracking_history (shipment_id, status, description) 
                    VALUES (?, 'Order Placed', 'Your order has been placed and is being processed')
                ");
                $stmt->execute([$shipment_id]);
            }
            
            // Clear cart
            $stmt = $this->db->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Order placed successfully',
                'order_id' => $order_id,
                'order_number' => $order_number,
                'tracking_number' => $tracking_number
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Order failed: ' . $e->getMessage()];
        }
    }
    
    public function get_order_by_id($order_id, $user_id = null) {
        try {
            $where_clause = "o.id = ?";
            $params = [$order_id];
            
            if ($user_id) {
                $where_clause .= " AND o.user_id = ?";
                $params[] = $user_id;
            }
            
            $stmt = $this->db->prepare("
                SELECT o.*, u.first_name, u.last_name, u.email, s.tracking_number, s.status as shipment_status
                FROM orders o
                JOIN users u ON o.user_id = u.id
                LEFT JOIN shipments s ON o.id = s.order_id
                WHERE $where_clause
            ");
            
            $stmt->execute($params);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($order) {
                // Get order items
                $stmt = $this->db->prepare("
                    SELECT oi.*, p.name, p.images, v.business_name
                    FROM order_items oi
                    JOIN products p ON oi.product_id = p.id
                    JOIN vendors v ON oi.vendor_id = v.id
                    WHERE oi.order_id = ?
                ");
                $stmt->execute([$order_id]);
                $order['items'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Decode images for each item
                foreach ($order['items'] as &$item) {
                    $item['images'] = json_decode($item['images'], true) ?: [];
                }
            }
            
            return $order;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function get_user_orders($user_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.*, s.tracking_number, s.status as shipment_status
                FROM orders o
                LEFT JOIN shipments s ON o.id = s.order_id
                WHERE o.user_id = ?
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$user_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_vendor_orders($vendor_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT o.*, u.first_name, u.last_name, s.tracking_number, s.status as shipment_status
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN shipments s ON o.id = s.order_id
                WHERE oi.vendor_id = ?
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$vendor_id, $limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function update_order_status($order_id, $status) {
        try {
            $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$status, date('Y-m-d H:i:s'), $order_id]);
            
            return ['success' => true, 'message' => 'Order status updated'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update order status'];
        }
    }
    
    private function apply_coupon($coupon_code, $subtotal) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM coupons 
                WHERE code = ? AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > ?)
                AND (usage_limit IS NULL OR used_count < usage_limit)
                AND minimum_amount <= ?
            ");
            
            $stmt->execute([$coupon_code, $subtotal, date('Y-m-d H:i:s')]);
            $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$coupon) {
                return ['success' => false, 'message' => 'Invalid or expired coupon'];
            }
            
            $discount = 0;
            if ($coupon['type'] === 'percentage') {
                $discount = ($subtotal * $coupon['value']) / 100;
            } else {
                $discount = $coupon['value'];
            }
            
            return [
                'success' => true,
                'discount' => $discount,
                'coupon_id' => $coupon['id']
            ];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Coupon validation failed'];
        }
    }
    
    public function validate_coupon($coupon_code, $subtotal) {
        return $this->apply_coupon($coupon_code, $subtotal);
    }
    
    // Additional vendor-specific methods needed by dashboards
    public function getVendorOrderCount($vendor_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(DISTINCT o.id) as count 
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                WHERE oi.vendor_id = ?
            ");
            $stmt->execute([$vendor_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getVendorEarnings($vendor_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT SUM(oi.total) as earnings 
                FROM order_items oi
                JOIN orders o ON oi.order_id = o.id
                WHERE oi.vendor_id = ? AND o.status != 'cancelled'
            ");
            $stmt->execute([$vendor_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['earnings'] ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getVendorPendingOrders($vendor_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT o.*, u.first_name, u.last_name, s.tracking_number, s.status as shipment_status
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN shipments s ON o.id = s.order_id
                WHERE oi.vendor_id = ? AND (o.status = 'pending' OR o.payment_status = 'pending')
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$vendor_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getVendorOrders($vendor_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT o.*, u.first_name, u.last_name, s.tracking_number, s.status as shipment_status,
                       COUNT(oi.id) as item_count
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN users u ON o.user_id = u.id
                LEFT JOIN shipments s ON o.id = s.order_id
                WHERE oi.vendor_id = ?
                GROUP BY o.id
                ORDER BY o.created_at DESC
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$vendor_id, $limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getUserOrders($user_id, $limit = 20, $offset = 0) {
        return $this->get_user_orders($user_id, $limit, $offset);
    }
    
    public function getUserOrderCount($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM orders WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'];
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getUserTotalSpent($user_id) {
        try {
            $stmt = $this->db->prepare("SELECT SUM(total_amount) as total FROM orders WHERE user_id = ? AND status != 'cancelled'");
            $stmt->execute([$user_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['total'] ?: 0;
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function get_all_pending_orders() {
        try {
            $stmt = $this->db->prepare("
                SELECT DISTINCT o.*, u.first_name, u.last_name, u.email as customer_email,
                       v.business_name as vendor_name, v.id as vendor_id,
                       GROUP_CONCAT(p.name, ', ') as products
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN users u ON o.user_id = u.id
                JOIN vendors v ON oi.vendor_id = v.id
                JOIN products p ON oi.product_id = p.id
                WHERE o.status = 'pending' OR o.payment_status = 'pending'
                GROUP BY o.id, v.id, o.payment_status
                ORDER BY o.created_at DESC
            ");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function notify_vendor_delivery($order_id, $vendor_id, $admin_id) {
        try {
            // Get order details
            $stmt = $this->db->prepare("
                SELECT o.*, v.business_name, vu.email as vendor_email, vu.id as vendor_user_id
                FROM orders o
                JOIN order_items oi ON o.id = oi.order_id
                JOIN vendors v ON oi.vendor_id = v.id
                JOIN users vu ON v.user_id = vu.id
                WHERE o.id = ? AND oi.vendor_id = ?
                LIMIT 1
            ");
            $stmt->execute([$order_id, $vendor_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                return ['success' => false, 'message' => 'Order or vendor not found'];
            }
            
            // Send notification to vendor
            $title = "Urgent: Complete Delivery for Order #{$order['order_number']}";
            $message = "Please complete the delivery for order #{$order['order_number']} as soon as possible. Customer is waiting for their order.";
            
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message) 
                VALUES (?, 'delivery_reminder', ?, ?)
            ");
            $stmt->execute([$order['vendor_user_id'], $title, $message]);
            
            return ['success' => true, 'message' => "Delivery reminder sent to {$order['business_name']}"];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to send notification: ' . $e->getMessage()];
        }
    }
    
    public function admin_update_order_status($order_id, $status) {
        try {
            // Validate status
            $allowed_statuses = ['pending', 'processing', 'shipped', 'delivered', 'cancelled'];
            if (!in_array($status, $allowed_statuses)) {
                return ['success' => false, 'message' => 'Invalid status value'];
            }
            
            $stmt = $this->db->prepare("UPDATE orders SET status = ?, updated_at = ? WHERE id = ?");
            $stmt->execute([$status, date('Y-m-d H:i:s'), $order_id]);
            
            return ['success' => true, 'message' => 'Order status updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update order status: ' . $e->getMessage()];
        }
    }
    
    /**
     * Verify order payment and confirm order
     */
    public function admin_verify_order($order_id, $admin_id) {
        try {
            $this->db->beginTransaction();
            
            // Check if order exists and is pending payment
            $stmt = $this->db->prepare("SELECT * FROM orders WHERE id = ? AND payment_status = 'pending'");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$order) {
                $this->db->rollBack();
                return ['success' => false, 'message' => 'Order not found or already verified'];
            }
            
            // Update order payment status to paid and status to processing
            $stmt = $this->db->prepare("
                UPDATE orders SET 
                    payment_status = 'paid', 
                    status = 'processing', 
                    verified_by = ?, 
                    verified_at = ?, 
                    updated_at = ? 
                WHERE id = ?
            ");
            
            $now = date('Y-m-d H:i:s');
            $stmt->execute([$admin_id, $now, $now, $order_id]);
            
            // Update the payment record to use 'paid' status for consistency
            $stmt = $this->db->prepare("UPDATE payments SET status = 'paid' WHERE order_id = ? AND status = 'pending'");
            $stmt->execute([$order_id]);
            
            // Update shipment status and add tracking history
            $stmt = $this->db->prepare("UPDATE shipments SET status = 'pending' WHERE order_id = ? AND status = 'pending_approval'");
            $stmt->execute([$order_id]);
            
            // Get shipment ID for tracking history
            $stmt = $this->db->prepare("SELECT id FROM shipments WHERE order_id = ?");
            $stmt->execute([$order_id]);
            $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shipment) {
                // Add tracking history for payment verification
                $stmt = $this->db->prepare("
                    INSERT INTO tracking_history (shipment_id, status, description) 
                    VALUES (?, 'Payment Verified', 'Payment verified by admin. Order is now being processed.')
                ");
                $stmt->execute([$shipment['id']]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Order payment verified and confirmed successfully'];
            
        } catch (PDOException $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to verify order: ' . $e->getMessage()];
        }
    }
    
    /**
     * Create commission transaction for vendor using rate at time of sale
     * This ensures historical accuracy of commission tracking
     */
    private function create_commission_transaction($order_id, $order_item_id, $vendor_id, $product_id, $sale_amount) {
        try {
            // Get current active commission rate for vendor
            $stmt = $this->db->prepare("
                SELECT commission_rate 
                FROM vendor_commissions 
                WHERE vendor_id = ? AND is_active = 1 
                ORDER BY effective_date DESC 
                LIMIT 1
            ");
            $stmt->execute([$vendor_id]);
            $commission_data = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If no commission rate exists, create default 10% rate
            if (!$commission_data) {
                $default_rate = 10.00;
                $stmt = $this->db->prepare("
                    INSERT INTO vendor_commissions (vendor_id, commission_rate, effective_date, is_active, notes) 
                    VALUES (?, ?, CURRENT_TIMESTAMP, 1, 'Default commission rate auto-created')
                ");
                $stmt->execute([$vendor_id, $default_rate]);
                $commission_rate = $default_rate;
            } else {
                $commission_rate = $commission_data['commission_rate'];
            }
            
            // Calculate commission amount
            $commission_amount = ($sale_amount * $commission_rate) / 100;
            
            // Determine commission status based on order payment status
            $stmt = $this->db->prepare("SELECT payment_status FROM orders WHERE id = ?");
            $stmt->execute([$order_id]);
            $order = $stmt->fetch(PDO::FETCH_ASSOC);
            $commission_status = ($order['payment_status'] === 'paid') ? 'pending' : 'pending';
            
            // Create commission transaction with historical rate
            $stmt = $this->db->prepare("
                INSERT INTO commission_transactions 
                (order_id, vendor_id, order_item_id, product_id, sale_amount, commission_rate, commission_amount, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $order_id, 
                $vendor_id, 
                $order_item_id, 
                $product_id, 
                $sale_amount, 
                $commission_rate, 
                $commission_amount, 
                $commission_status
            ]);
            
            return true;
            
        } catch (PDOException $e) {
            // Log error but don't break order processing
            error_log("Failed to create commission transaction: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Ensure vendor has active commission rate, create default if needed
     */
    public function ensure_vendor_commission_rate($vendor_id, $default_rate = 10.00) {
        try {
            // Check if vendor has active commission rate
            $stmt = $this->db->prepare("
                SELECT id FROM vendor_commissions 
                WHERE vendor_id = ? AND is_active = 1
            ");
            $stmt->execute([$vendor_id]);
            
            if (!$stmt->fetch()) {
                // Create default commission rate
                $stmt = $this->db->prepare("
                    INSERT INTO vendor_commissions (vendor_id, commission_rate, effective_date, is_active, notes) 
                    VALUES (?, ?, CURRENT_TIMESTAMP, 1, 'Default commission rate created')
                ");
                $stmt->execute([$vendor_id, $default_rate]);
                return ['success' => true, 'message' => "Default {$default_rate}% commission rate created"];
            }
            
            return ['success' => true, 'message' => 'Commission rate already exists'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to ensure commission rate: ' . $e->getMessage()];
        }
    }
}
?>
