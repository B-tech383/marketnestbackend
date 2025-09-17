<?php
require_once __DIR__ . '/../config/config.php';

class VendorManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function submit_application($name, $email, $business_name, $description, $logo_path = null) {
        try {
            // Check if email already has an application
            $stmt = $this->db->prepare("SELECT id FROM vendor_applications WHERE email = ? AND status = 'pending'");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'You already have a pending application'];
            }
            
            // Check if email is already a vendor
            $stmt = $this->db->prepare("
                SELECT v.id FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                WHERE u.email = ?
            ");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                return ['success' => false, 'message' => 'This email is already associated with a vendor account'];
            }
            
            // Insert application
            $stmt = $this->db->prepare("
                INSERT INTO vendor_applications (name, email, business_name, description, logo_path) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([$name, $email, $business_name, $description, $logo_path]);
            
            return ['success' => true, 'message' => 'Application submitted successfully! We will review it within 24-48 hours.'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Application failed: ' . $e->getMessage()];
        }
    }
    
    public function get_pending_applications() {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM vendor_applications 
                WHERE status = 'pending' 
                ORDER BY applied_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_all_applications() {
        try {
            $stmt = $this->db->prepare("
                SELECT va.*, u.username as reviewed_by_username 
                FROM vendor_applications va 
                LEFT JOIN users u ON va.reviewed_by = u.id 
                ORDER BY va.applied_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function approve_application($application_id, $admin_id) {
        try {
            $this->db->beginTransaction();
            
            // Get application details
            $stmt = $this->db->prepare("SELECT * FROM vendor_applications WHERE id = ?");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$application) {
                throw new Exception('Application not found');
            }
            
            // Create user account for vendor
            $username = $this->generate_unique_username($application['business_name']);
            $temp_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, first_name, last_name) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username, 
                $application['email'], 
                $hashed_password, 
                $application['name'], 
                'Vendor'
            ]);
            
            $user_id = $this->db->lastInsertId();
            
            // Add vendor role
            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'vendor')");
            $stmt->execute([$user_id]);
            
            // Create vendor record
            $stmt = $this->db->prepare("
                INSERT INTO vendors (user_id, business_name, description, logo_path) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $user_id, 
                $application['business_name'], 
                $application['description'], 
                $application['logo_path']
            ]);
            
            // Update application status
            $stmt = $this->db->prepare("
                UPDATE vendor_applications 
                SET status = 'approved', reviewed_at = NOW(), reviewed_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $application_id]);
            
            // Send notification to vendor (internal)
            $this->send_notification($user_id, 'vendor_approved', 'Vendor Application Approved', 
                "Congratulations! Your vendor application has been approved. Your login credentials: Username: {$username}, Temporary Password: {$temp_password}. Please change your password after first login.");
            
            // Send approval email to vendor
            try {
                require_once __DIR__ . '/email.php';
                $emailService = new EmailService();
                $email_result = $emailService->sendVendorApprovalEmail(
                    $application['email'],
                    $application['name'],
                    $application['business_name'],
                    $username,
                    $temp_password
                );
                error_log("Vendor approval email sent successfully to " . $application['email'] . " - MessageID: " . $email_result['messageId']);
            } catch (Exception $e) {
                // Log the error but don't fail the approval process
                error_log("Failed to send vendor approval email to " . $application['email'] . ": " . $e->getMessage());
            }
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Vendor application approved successfully!', 'credentials' => ['username' => $username, 'password' => $temp_password]];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Approval failed: ' . $e->getMessage()];
        }
    }
    
    public function reject_application($application_id, $admin_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE vendor_applications 
                SET status = 'rejected', reviewed_at = NOW(), reviewed_by = ? 
                WHERE id = ?
            ");
            $stmt->execute([$admin_id, $application_id]);
            
            return ['success' => true, 'message' => 'Application rejected'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Rejection failed: ' . $e->getMessage()];
        }
    }
    
    public function assign_badge($vendor_id, $badge_id) {
        try {
            $stmt = $this->db->prepare("
                UPDATE vendors 
                SET badge_id = ?, is_verified = TRUE 
                WHERE id = ?
            ");
            $stmt->execute([$badge_id, $vendor_id]);

            return ['success' => true, 'message' => 'Badge assigned successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Badge assignment failed: ' . $e->getMessage()];
        }
    }


    
    public function get_all_vendors() {
        try {
            $stmt = $this->db->prepare("
                SELECT v.*, u.username, u.email, u.first_name, u.last_name 
                FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                ORDER BY v.is_verified DESC, v.created_at DESC
            ");
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function create_vendor_directly($name, $email, $business_name, $description) {
        try {
            $this->db->beginTransaction();
            
            // Check if email already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            
            if ($stmt->rowCount() > 0) {
                throw new Exception('User with this email already exists');
            }
            
            // Create user account for vendor
            $username = $this->generate_unique_username($business_name);
            $temp_password = bin2hex(random_bytes(8));
            $hashed_password = password_hash($temp_password, PASSWORD_DEFAULT);
            
            $stmt = $this->db->prepare("
                INSERT INTO users (username, email, password, first_name, last_name) 
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $username, 
                $email, 
                $hashed_password, 
                $name, 
                'Vendor'
            ]);
            
            $user_id = $this->db->lastInsertId();
            
            // Add vendor role
            $stmt = $this->db->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, 'vendor')");
            $stmt->execute([$user_id]);
            
            // Create vendor record
            $stmt = $this->db->prepare("
                INSERT INTO vendors (user_id, business_name, description) 
                VALUES (?, ?, ?)
            ");
            $stmt->execute([
                $user_id, 
                $business_name, 
                $description
            ]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => "Vendor '{$business_name}' created successfully. Login: {$username} / {$temp_password}"];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Failed to create vendor: ' . $e->getMessage()];
        }
    }
    
    public function delete_vendor($vendor_id) {
        try {
            $this->db->beginTransaction();
            
            // Get vendor details first
            $stmt = $this->db->prepare("
                SELECT v.*, u.username, u.email 
                FROM vendors v 
                JOIN users u ON v.user_id = u.id 
                WHERE v.id = ?
            ");
            $stmt->execute([$vendor_id]);
            $vendor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$vendor) {
                throw new Exception('Vendor not found');
            }
            
            // Check if vendor has any orders - prevent deletion if they do
            $stmt = $this->db->prepare("SELECT COUNT(*) as order_count FROM orders WHERE user_id = ?");
            $stmt->execute([$vendor['user_id']]);
            $order_count = $stmt->fetch(PDO::FETCH_ASSOC)['order_count'];
            
            if ($order_count > 0) {
                throw new Exception("Cannot delete vendor '{$vendor['business_name']}' because they have {$order_count} existing order(s). Please handle these orders first.");
            }
            
            // Check if vendor has any products with orders
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as product_orders 
                FROM order_items oi 
                JOIN products p ON oi.product_id = p.id 
                WHERE p.vendor_id = ?
            ");
            $stmt->execute([$vendor_id]);
            $product_orders = $stmt->fetch(PDO::FETCH_ASSOC)['product_orders'];
            
            if ($product_orders > 0) {
                throw new Exception("Cannot delete vendor '{$vendor['business_name']}' because their products have been ordered {$product_orders} time(s). Please handle these orders first.");
            }
            
            // Safe to delete - no orders exist
            
            // Delete products from this vendor first
            $stmt = $this->db->prepare("DELETE FROM products WHERE vendor_id = ?");
            $stmt->execute([$vendor_id]);
            
            // Delete vendor record
            $stmt = $this->db->prepare("DELETE FROM vendors WHERE id = ?");
            $stmt->execute([$vendor_id]);
            
            // Delete user roles
            $stmt = $this->db->prepare("DELETE FROM user_roles WHERE user_id = ?");
            $stmt->execute([$vendor['user_id']]);
            
            // Delete user account
            $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$vendor['user_id']]);
            
            $this->db->commit();
            
            return ['success' => true, 'message' => "Vendor '{$vendor['business_name']}' deleted successfully"];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function get_vendor_by_user_id($user_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM vendors WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return false;
        }
    }

    private function generate_unique_username($base_name, $max_attempts = 10) {
        $base_username = strtolower(str_replace(' ', '_', $base_name));
        
        for ($attempt = 1; $attempt <= $max_attempts; $attempt++) {
            $username = $base_username . '_' . random_int(1000, 9999);
            
            // Check if username already exists
            $stmt = $this->db->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            
            if ($stmt->rowCount() === 0) {
                return $username; // Username is unique
            }
        }
        
        // If we couldn't generate a unique username after max attempts, 
        // use timestamp to ensure uniqueness
        return $base_username . '_' . time() . '_' . random_int(100, 999);
    }

    private function send_notification($user_id, $type, $title, $message) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO notifications (user_id, type, title, message) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$user_id, $type, $title, $message]);
            
        } catch (PDOException $e) {
            // Log error but don't fail the main operation
            error_log("Notification failed: " . $e->getMessage());
        }
    }
}
?>
