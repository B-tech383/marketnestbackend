<?php
require_once __DIR__ . '/../config/database.php';

class SettingsManager {
    private $db;
    private $settings_cache = [];
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
        $this->loadSettings();
    }
    
    private function loadSettings() {
        try {
            $stmt = $this->db->prepare("SELECT setting_key, setting_value FROM settings");
            $stmt->execute();
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
            $this->settings_cache = $settings;
        } catch (PDOException $e) {
            $this->settings_cache = [];
        }
    }
    
    public function get($key, $default = null) {
        return $this->settings_cache[$key] ?? $default;
    }
    
    public function set($key, $value, $type = 'text', $description = '') {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO settings (setting_key, setting_value, setting_type, description) 
                VALUES (?, ?, ?, ?) 
                ON DUPLICATE KEY UPDATE 
                setting_value = VALUES(setting_value),
                setting_type = VALUES(setting_type),
                description = VALUES(description)
            ");
            $stmt->execute([$key, $value, $type, $description]);
            $this->settings_cache[$key] = $value;
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getAll() {
        try {
            $stmt = $this->db->prepare("SELECT * FROM settings ORDER BY setting_key");
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getSiteName() {
        return $this->get('site_name', 'Market Nest');
    }
    
    public function getSiteDescription() {
        return $this->get('site_description', 'Your Premier Marketplace Destination');
    }
    
    public function getAdminEmail() {
        return $this->get('admin_email', 'admin@marketnest.com');
    }
    
    public function getSupportEmail() {
        return $this->get('support_email', 'support@marketnest.com');
    }
    
    public function isMaintenanceMode() {
        return $this->get('maintenance_mode', '0') === '1';
    }
    
    public function getMaintenanceMessage() {
        return $this->get('maintenance_message', 'We are currently performing maintenance. Please check back later.');
    }
    
    public function refreshCache() {
        $this->loadSettings();
    }
}
?>

