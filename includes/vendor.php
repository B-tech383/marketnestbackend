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
            $username = strtolower(str_replace(' ', '_', $application['business_name'])) . '_' . rand(1000, 9999);
            $temp_password = 'temp_' . rand(100000, 999999);
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
            
            // Send notification to vendor
            $this->send_notification($user_id, 'vendor_approved', 'Vendor Application Approved', 
                "Congratulations! Your vendor application has been approved. Your login credentials: Username: {$username}, Temporary Password: {$temp_password}. Please change your password after first login.");
            
            $this->db->commit();
            
            return ['success' => true, 'message' => 'Vendor application approved successfully', 'credentials' => ['username' => $username, 'password' => $temp_password]];
            
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
    
    public function assign_badge($vendor_id, $badge_name) {
        try {
            $stmt = $this->db->prepare("
                UPDATE vendors 
                SET verification_badge = ?, is_verified = TRUE 
                WHERE id = ?
            ");
            $stmt->execute([$badge_name, $vendor_id]);
            
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
