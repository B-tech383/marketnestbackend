<?php
require_once __DIR__ . '/../config/database.php';

class ProductManager {
    private $db;
    
    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    
    /**
     * Normalize image paths to ensure consistent handling
     */
    private function normalizeImagePaths($images_json) {
        $imgs = $images_json ? json_decode($images_json, true) ?: [] : [];
        $normalized = [];
        
        foreach ($imgs as $img) {
            if (!$img) continue;
            
            // Keep absolute URLs as-is
            if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                $normalized[] = $img;
                continue;
            }
            
            // Keep existing upload paths that start with uploads/
            if (strpos($img, 'uploads/') === 0) {
                $normalized[] = $img;
                continue;
            }
            
            // Handle paths that start with ../uploads/ (from subdirectories)
            if (strpos($img, '../uploads/') === 0) {
                $normalized[] = substr($img, 3); // Remove ../
                continue;
            }
            
            // Handle bare filenames - assume they're in uploads/products/
            $base = basename($img);
            if ($base) {
                $normalized[] = 'uploads/products/' . $base;
            }
        }
        
        return $normalized;
    }
    
    public function add_product($vendor_id, $category_id, $name, $description, $price, $sale_price, $stock_quantity, $sku, $images, $is_featured = false, $is_flash_deal = false, $flash_deal_end = null) {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO products (vendor_id, category_id, name, description, price, sale_price, stock_quantity, sku, images, is_featured, is_flash_deal, flash_deal_end, admin_approved) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0)
            ");
            
            $images_json = json_encode($images);
            
            $stmt->execute([
                $vendor_id, $category_id, $name, $description, $price, 
                $sale_price, $stock_quantity, $sku, $images_json, 
                $is_featured, $is_flash_deal, $flash_deal_end
            ]);
            
            return ['success' => true, 'message' => 'Product added successfully and is pending admin approval', 'product_id' => $this->db->lastInsertId()];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to add product: ' . $e->getMessage()];
        }
    }
    
    // Admin-specific methods for product management
    public function get_pending_products($limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name as category_name, v.business_name, v.email as vendor_email
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN vendors v ON p.vendor_id = v.id 
                WHERE p.admin_approved = 0 AND p.status = 'active'
                ORDER BY p.created_at ASC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$limit, $offset]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Normalize images for each product
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
            }
            
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function approve_product($product_id, $admin_id) {
        try {
            $stmt = $this->db->prepare("UPDATE products SET admin_approved = 1, updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$product_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Product approved successfully'];
            } else {
                return ['success' => false, 'message' => 'Product not found or already approved'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to approve product: ' . $e->getMessage()];
        }
    }
    
    public function reject_product($product_id, $admin_id, $reason = '') {
        try {
            $stmt = $this->db->prepare("UPDATE products SET admin_approved = 0, status = 'inactive', updated_at = CURRENT_TIMESTAMP WHERE id = ?");
            $result = $stmt->execute([$product_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Product rejected successfully'];
            } else {
                return ['success' => false, 'message' => 'Product not found or already processed'];
            }
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Failed to reject product: ' . $e->getMessage()];
        }
    }
    
    public function get_all_products_admin($limit = 50, $offset = 0, $filter = 'all') {
        try {
            $where = [];
            switch ($filter) {
                case 'pending':
                    $where[] = "admin_approved = 0 AND status = 'active'";
                    break;
                case 'approved':
                    $where[] = "admin_approved = 1 AND status = 'active'";
                    break;
                case 'rejected':
                    $where[] = "admin_approved = 0 AND status = 'inactive'";
                    break;
                default:
                    break;
            }
            $whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $limitInt = max(1, (int)$limit);
            $offsetInt = max(0, (int)$offset);
            
            $sql = "SELECT p.*, c.name AS category_name, v.business_name, v.email AS vendor_email
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN vendors v ON p.vendor_id = v.id
                    $whereClause
                    ORDER BY p.created_at DESC
                    LIMIT $limitInt OFFSET $offsetInt";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
            }
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_all_products_admin_simple($limit = 50, $offset = 0, $filter = 'all') {
        try {
            $where = [];
            switch ($filter) {
                case 'pending':
                    $where[] = "p.admin_approved = 0 AND p.status = 'active'";
                    break;
                case 'approved':
                    $where[] = "p.admin_approved = 1 AND p.status = 'active'";
                    break;
                case 'rejected':
                    $where[] = "p.admin_approved = 0 AND p.status = 'inactive'";
                    break;
                default:
                    break;
            }
            $whereClause = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
            $limitInt = max(1, (int)$limit);
            $offsetInt = max(0, (int)$offset);
            $sql = "SELECT p.*, c.name AS category_name, v.business_name, v.email AS vendor_email
                    FROM products p
                    LEFT JOIN categories c ON p.category_id = c.id
                    LEFT JOIN vendors v ON p.vendor_id = v.id
                    $whereClause
                    ORDER BY p.created_at DESC
                    LIMIT $limitInt OFFSET $offsetInt";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
            }
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function get_products($limit = 20, $offset = 0, $category_id = null, $search = null, $vendor_id = null, $featured_only = false) {
        try {
            $where_conditions = ["p.status IN ('active','out_of_stock')", "p.admin_approved = 1"];
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
            
            $limitInt = max(1, (int)$limit);
            $offsetInt = max(0, (int)$offset);
            
            $sql = "
                SELECT 
                    p.*, 
                    c.name AS category_name, 
                    v.business_name, 
                    v.is_verified, 
                    v.verification_badge,
                    (
                        SELECT ROUND(AVG(r2.rating), 1) 
                        FROM reviews r2 
                        WHERE r2.product_id = p.id
                    ) AS avg_rating,
                    (
                        SELECT COUNT(r3.id) 
                        FROM reviews r3 
                        WHERE r3.product_id = p.id
                    ) AS review_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN vendors v ON p.vendor_id = v.id
                WHERE $where_clause
                ORDER BY v.is_verified DESC, p.is_featured DESC, p.created_at DESC
                LIMIT $limitInt OFFSET $offsetInt
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
                $product['avg_rating'] = isset($product['avg_rating']) && $product['avg_rating'] ? (float)$product['avg_rating'] : 0;
                $product['review_count'] = isset($product['review_count']) ? (int)$product['review_count'] : 0;
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_products_simple($limit = 20, $offset = 0, $category_id = null, $search = null) {
        try {
            $where = ["status IN ('active','out_of_stock')", "admin_approved = 1"];
            $params = [];
            if ($category_id) {
                $where[] = 'category_id = ?';
                $params[] = $category_id;
            }
            if ($search) {
                $where[] = '(name LIKE ? OR description LIKE ?)';
                $params[] = "%$search%";
                $params[] = "%$search%";
            }
            $whereClause = implode(' AND ', $where);
            $stmt = $this->db->prepare("SELECT * FROM products WHERE $whereClause ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $params[] = $limit;
            $params[] = $offset;
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
            }
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function get_products_by_category_minimal($category_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE category_id = ? AND status IN ('active','out_of_stock') AND admin_approved = 1 ORDER BY created_at DESC LIMIT ? OFFSET ?");
            $stmt->execute([$category_id, $limit, $offset]);
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
    
    public function get_product_by_id($product_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name as category_name, v.business_name, v.is_verified, v.verification_badge, v.logo_path as vendor_logo,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN vendors v ON p.vendor_id = v.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.id = ? AND p.status IN ('active','out_of_stock') AND p.admin_approved = 1
                GROUP BY p.id
            ");
            
            $stmt->execute([$product_id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
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
                LEFT JOIN products p ON c.id = p.category_id AND p.status IN ('active','out_of_stock') AND p.admin_approved = 1
                GROUP BY c.id 
                ORDER BY c.name
            ");
            $stmt->execute();
            
            $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($categories as &$cat) {
                $img = $cat['image_path'] ?? '';
                if (!$img) {
                    continue;
                }
                if (strpos($img, 'http://') === 0 || strpos($img, 'https://') === 0) {
                    // keep as-is
                } elseif (strpos($img, 'uploads/') === 0) {
                    // already uploads path, keep
                } else {
                    $cat['image_path'] = 'uploads/categories/' . basename($img);
                }
            }
            return $categories;
            
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
                WHERE p.is_flash_deal = 1 AND p.flash_deal_end > CURRENT_TIMESTAMP AND p.status IN ('active','out_of_stock') AND p.admin_approved = 1
                GROUP BY p.id
                ORDER BY p.flash_deal_end ASC
                LIMIT ?
            ");
            
            $stmt->execute([$limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
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
                WHERE rv.user_id = ? AND p.status IN ('active','out_of_stock') AND p.admin_approved = 1
                ORDER BY rv.viewed_at DESC
                LIMIT ?
            ");
            
            $stmt->execute([$user_id, $limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    public function add_review($user_id, $product_id, $order_id, $rating, $title, $comment) {
        try {
            // Check if user is admin
            $stmt = $this->db->prepare("SELECT role FROM user_roles WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $user_role = $stmt->fetch(PDO::FETCH_ASSOC);
            $is_admin = ($user_role && $user_role['role'] === 'admin');
            
            // Set verified purchase status (admin reviews are not verified purchases)
            $is_verified = $is_admin ? false : true;
            
            $stmt = $this->db->prepare("
                INSERT INTO reviews (user_id, product_id, order_id, rating, title, comment, is_verified_purchase) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            // Use NULL for admin reviews instead of 0
            $review_order_id = $is_admin ? null : $order_id;
            $stmt->execute([$user_id, $product_id, $review_order_id, $rating, $title, $comment, $is_verified]);
            
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
                SELECT r.*, u.first_name, u.last_name, u.username, COALESCE(ur.role, 'customer') as role
                FROM reviews r
                JOIN users u ON r.user_id = u.id
                LEFT JOIN user_roles ur ON u.id = ur.user_id
                WHERE r.product_id = ?
                ORDER BY r.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->execute([$product_id, (int)$limit, (int)$offset]);
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            return $results;
            
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // Additional vendor-specific methods needed by dashboards
    public function getVendorProducts($vendor_id, $limit = 20, $offset = 0) {
        return $this->get_products($limit, $offset, null, null, $vendor_id);
    }

    public function getVendorProductsSimple($vendor_id, $limit = 50, $offset = 0) {
        try {
            $stmt = $this->db->prepare("\n                SELECT p.*\n                FROM products p\n                WHERE p.vendor_id = ?\n                ORDER BY p.created_at DESC\n                LIMIT ? OFFSET ?\n            ");
            $stmt->execute([$vendor_id, (int)$limit, (int)$offset]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
            }
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function getVendorProductCount($vendor_id) {
        try {
            $stmt = $this->db->prepare("SELECT COUNT(*) as count FROM products WHERE vendor_id = ?");
            $stmt->execute([$vendor_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['count'] ?? 0);
        } catch (PDOException $e) {
            return 0;
        }
    }
    
    public function getVendorTopProducts($vendor_id, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, SUM(oi.quantity) as total_sold,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.vendor_id = ? AND p.status = 'active'
                GROUP BY p.id
                ORDER BY total_sold DESC, p.created_at DESC
                LIMIT ?
            ");
            $stmt->execute([$vendor_id, $limit]);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Decode images for each product
            foreach ($products as &$product) {
                $product['images'] = json_decode($product['images'], true) ?: [];
                $product['total_sold'] = $product['total_sold'] ?: 0;
                $product['avg_rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
            }
            
            return $products;
        } catch (PDOException $e) {
            return [];
        }
    }

    public function updateProductStatus($product_id, $status, $vendor_id) {
        try {
            // Ensure the product belongs to the vendor
            $stmt = $this->db->prepare("UPDATE products SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ? AND vendor_id = ?");
            $stmt->execute([$status, $product_id, $vendor_id]);
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
    
    public function hasUserPurchasedProduct($user_id, $product_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT COUNT(*) as count 
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
            ");
            $stmt->execute([$user_id, $product_id]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return $result['count'] > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    public function getUserOrdersForProduct($user_id, $product_id) {
        try {
            $stmt = $this->db->prepare("
                SELECT o.id, o.order_number, o.created_at, oi.quantity, oi.price
                FROM order_items oi 
                JOIN orders o ON oi.order_id = o.id 
                WHERE o.user_id = ? AND oi.product_id = ? AND o.status = 'completed'
                ORDER BY o.created_at DESC
            ");
            $stmt->execute([$user_id, $product_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }
    
    // CamelCase method aliases for compatibility
    public function addProduct($data) {
        return $this->add_product(
            $data['vendor_id'],
            $data['category_id'],
            $data['name'],
            $data['description'],
            $data['price'],
            $data['sale_price'] ?? null,
            $data['stock_quantity'],
            $data['sku'] ?? null,
            $data['images'] ?? [],
            $data['is_featured'] ?? false,
            $data['is_flash_deal'] ?? false,
            $data['flash_deal_end'] ?? null
        );
    }
    
    public function getProducts($limit = 20, $offset = 0, $category_id = null, $search = null, $vendor_id = null, $featured_only = false) {
        return $this->get_products($limit, $offset, $category_id, $search, $vendor_id, $featured_only);
    }
    
    public function getProductById($product_id) {
        return $this->get_product_by_id($product_id);
    }

    // Preview method for admin/vendor dashboards (shows all products regardless of approval)
    public function get_products_preview($limit = 20, $offset = 0, $category_id = null, $search = null, $vendor_id = null, $featured_only = false) {
        try {
            $where_conditions = ["p.status IN ('active','out_of_stock')"];
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
            
            $limitInt = max(1, (int)$limit);
            $offsetInt = max(0, (int)$offset);
            
            $sql = "
                SELECT 
                    p.*, 
                    c.name AS category_name, 
                    v.business_name, 
                    v.is_verified, 
                    v.verification_badge,
                    (
                        SELECT ROUND(AVG(r2.rating), 1) 
                        FROM reviews r2 
                        WHERE r2.product_id = p.id
                    ) AS avg_rating,
                    (
                        SELECT COUNT(r3.id) 
                        FROM reviews r3 
                        WHERE r3.product_id = p.id
                    ) AS review_count
                FROM products p
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN vendors v ON p.vendor_id = v.id
                WHERE $where_clause
                ORDER BY p.admin_approved DESC, v.is_verified DESC, p.is_featured DESC, p.created_at DESC
                LIMIT $limitInt OFFSET $offsetInt
            ";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images']);
                $product['avg_rating'] = isset($product['avg_rating']) && $product['avg_rating'] ? (float)$product['avg_rating'] : 0;
                $product['review_count'] = isset($product['review_count']) ? (int)$product['review_count'] : 0;
            }
            
            return $products;
            
        } catch (PDOException $e) {
            return [];
        }
    }
}
?>
