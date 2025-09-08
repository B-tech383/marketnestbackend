<?php
require_once '../config/config.php';

class TrackingManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function get_shipment_by_tracking($tracking_number, $user_id = null) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, o.order_number, o.user_id, o.total_amount,
                       u.first_name, u.last_name, u.free_trackings_used
                FROM shipments s
                JOIN orders o ON s.order_id = o.id
                JOIN users u ON o.user_id = u.id
                WHERE s.tracking_number = ?
            ");
            
            $stmt->execute([$tracking_number]);
            $shipment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($shipment) {
                // Get tracking history
                $stmt = $this->db->prepare("
                    SELECT * FROM tracking_history 
                    WHERE shipment_id = ? 
                    ORDER BY timestamp ASC
                ");
                $stmt->execute([$shipment['id']]);
                $shipment['history'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // Determine tracking level based on user and payment
                $shipment['tracking_level'] = $this->get_tracking_level($shipment, $user_id);
            }
            
            return $shipment;
            
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function get_tracking_level($shipment, $user_id = null) {
        // If user is not logged in, only basic tracking
        if (!$user_id) {
            return 'basic';
        }
        
        // If it's the shipment owner, check their free tracking usage
        if ($user_id == $shipment['user_id']) {
            if ($shipment['free_trackings_used'] < FREE_TRACKING_LIMIT) {
                return 'basic';
            }
        }
        
        // Check if user has paid for advanced tracking for this shipment
        try {
            $stmt = $this->db->prepare("
                SELECT tracking_level FROM tracking_payments 
                WHERE user_id = ? AND shipment_id = ? AND status = 'completed'
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$user_id, $shipment['id']]);
            $payment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($payment) {
                return $payment['tracking_level'];
            }
            
            return 'basic';
            
        } catch (PDOException $e) {
            return 'basic';
        }
    }
    
    public function increment_free_tracking_usage($user_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE users 
                SET free_trackings_used = free_trackings_used + 1 
                WHERE id = ?
            ");
            $stmt->execute([$user_id]);
            
        } catch (PDOException $e) {
            // Ignore errors
        }
    }
    
    public function purchase_advanced_tracking($user_id, $shipment_id, $tracking_level, $amount) {
        try {
            $this->db->beginTransaction();
            
            // Create tracking payment record
            $stmt = $this->db->prepare("
                INSERT INTO tracking_payments (user_id, shipment_id, tracking_level, amount, status) 
                VALUES (?, ?, ?, ?, 'completed')
            ");
            $stmt->execute([$user_id, $shipment_id, $tracking_level, $amount]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Advanced tracking purchased successfully'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Payment failed: ' . $e->getMessage()];
        }
    }
    
    public function update_shipment_status($shipment_id, $status, $location = null, $description = null) {
        try {
            $this->db->beginTransaction();
            
            // Update shipment
            $stmt = $this->db->prepare("
                UPDATE shipments 
                SET status = ?, current_location = ?, updated_at = NOW() 
                WHERE id = ?
            ");
            $stmt->execute([$status, $location, $shipment_id]);
            
            // Add tracking history
            $stmt = $this->db->prepare("
                INSERT INTO tracking_history (shipment_id, status, location, description) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$shipment_id, $status, $location, $description]);
            
            // Update order status if delivered
            if ($status === 'delivered') {
                $stmt = $this->db->prepare("
                    UPDATE orders o
                    JOIN shipments s ON o.id = s.order_id
                    SET o.status = 'delivered'
                    WHERE s.id = ?
                ");
                $stmt->execute([$shipment_id]);
                
                $stmt = $this->db->prepare("
                    UPDATE shipments 
                    SET delivered_at = NOW() 
                    WHERE id = ?
                ");
                $stmt->execute([$shipment_id]);
            }
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Shipment status updated'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Update failed: ' . $e->getMessage()];
        }
    }
    
    public function get_all_shipments($limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT s.*, o.order_number, u.first_name, u.last_name
                FROM shipments s
                JOIN orders o ON s.order_id = o.id
                JOIN users u ON o.user_id = u.id
                ORDER BY s.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$limit, $offset]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_tracking_progress_percentage($status) {
        $progress_map = [
            'pending' => 10,
            'picked_up' => 25,
            'in_transit' => 50,
            'out_for_delivery' => 75,
            'delivered' => 100
        ];
        
        return $progress_map[$status] ?? 0;
    }
}

// Create tracking_payments table if it doesn't exist
$database = new Database();
$db = $database->getConnection();
$db->exec("
    CREATE TABLE IF NOT EXISTS tracking_payments (
        id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT NOT NULL,
        shipment_id INT NOT NULL,
        tracking_level ENUM('standard', 'premium') NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id),
        FOREIGN KEY (shipment_id) REFERENCES shipments(id)
    )
");
?>
