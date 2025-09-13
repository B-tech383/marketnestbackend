<?php
require_once __DIR__ . '/../config/database.php';

class AdvertisementManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    public function createAd($title, $content, $image_url = null, $video_url = null, $link_url = null, $ad_type = 'banner', $position = 'top', $start_date = null, $end_date = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO advertisements (title, content, image_url, video_url, link_url, ad_type, position, start_date, end_date) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$title, $content, $image_url, $video_url, $link_url, $ad_type, $position, $start_date, $end_date]);
            return ['success' => true, 'message' => 'Advertisement created successfully', 'id' => $this->db->lastInsertId()];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to create advertisement: ' . $e->getMessage()];
        }
    }
    
    public function updateAd($id, $title, $content, $image_url = null, $video_url = null, $link_url = null, $ad_type = 'banner', $position = 'top', $is_active = true, $start_date = null, $end_date = null) {
        try {
            $stmt = $this->db->prepare("
                UPDATE advertisements 
                SET title = ?, content = ?, image_url = ?, video_url = ?, link_url = ?, 
                    ad_type = ?, position = ?, is_active = ?, start_date = ?, end_date = ?
                WHERE id = ?
            ");
            $stmt->execute([$title, $content, $image_url, $video_url, $link_url, $ad_type, $position, $is_active, $start_date, $end_date, $id]);
            return ['success' => true, 'message' => 'Advertisement updated successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to update advertisement: ' . $e->getMessage()];
        }
    }
    
    public function deleteAd($id) {
        try {
            $stmt = $this->db->prepare("DELETE FROM advertisements WHERE id = ?");
            $stmt->execute([$id]);
            return ['success' => true, 'message' => 'Advertisement deleted successfully'];
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to delete advertisement: ' . $e->getMessage()];
        }
    }
    
    public function getAd($id) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM advertisements WHERE id = ?");
            $stmt->execute([$id]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function getAllAds($limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM advertisements 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function getActiveAds($ad_type = null, $position = null) {
        try {
            $where_conditions = ["is_active = 1"];
            $params = [];
            
            if ($ad_type) {
                $where_conditions[] = "ad_type = ?";
                $params[] = $ad_type;
            }
            
            if ($position) {
                $where_conditions[] = "position = ?";
                $params[] = $position;
            }
            
            // Check date range
            $where_conditions[] = "(start_date IS NULL OR start_date <= NOW())";
            $where_conditions[] = "(end_date IS NULL OR end_date >= NOW())";
            
            $where_clause = implode(" AND ", $where_conditions);
            
            $stmt = $this->db->prepare("
                SELECT * FROM advertisements 
                WHERE $where_clause 
                ORDER BY created_at DESC
            ");
            $stmt->execute($params);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function incrementViewCount($id) {
        try {
            $stmt = $this->db->prepare("UPDATE advertisements SET view_count = view_count + 1 WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Silent fail for view counting
        }
    }
    
    public function incrementClickCount($id) {
        try {
            $stmt = $this->db->prepare("UPDATE advertisements SET click_count = click_count + 1 WHERE id = ?");
            $stmt->execute([$id]);
        } catch (PDOException $e) {
            // Silent fail for click counting
        }
    }
    
    public function getAdStats() {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_ads,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active_ads,
                    SUM(view_count) as total_views,
                    SUM(click_count) as total_clicks
                FROM advertisements
            ");
            $stmt->execute();
            return $stmt->fetch(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return ['total_ads' => 0, 'active_ads' => 0, 'total_views' => 0, 'total_clicks' => 0];
        }
    }
}
?>

