<?php
// includes/product.php

class ProductManager {
    /** @var PDO */
    private $db;

    public function __construct() {
        $database = new Database();
        $this->db = $database->getConnection();
    }
    public function normalizeImagePaths($images) {
        // If it's already an array, use it directly
        $imgs = [];
        if (is_array($images)) {
            $imgs = $images;
        } elseif (is_string($images) && $images !== '') {
            $imgs = json_decode($images, true) ?: [];
        }

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

            // Handle paths that start with ../uploads/
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


   
    public function get_all_products_admin($limit = 50, $offset = 0, $filter = 'pending', $category_id = null) {
        try {
            $category_id = $category_id ?? null;

            // Build WHERE clause
            $where = '';
            switch ($filter) {
                case 'pending':
                    $where .= "((admin_approved = 0 OR admin_approved IS NULL) AND status NOT IN ('inactive'))";
                    break;
                case 'approved':
                    $where .= "(admin_approved = 1 AND status = 'active')";
                    break;
                case 'rejected':
                    $where .= "status = 'inactive'";
                    break;
                case 'all':
                default:
                    $where .= '1=1';
                    break;
            }

            // Category filter
            if ($category_id) {
                $where .= " AND p.category_id = :category_id";
            }

            $sql = "SELECT p.*,
                        v.business_name,
                        COALESCE(c.name, 'Uncategorized') AS category_name
                    FROM products p
                    LEFT JOIN vendors v ON p.vendor_id = v.id
                    LEFT JOIN categories c ON p.category_id = c.id
                    WHERE $where
                    ORDER BY p.created_at DESC
                    LIMIT :offset, :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

            if ($category_id) {
                $stmt->bindValue(':category_id', (int)$category_id, PDO::PARAM_INT);
            }

            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images'] ?? null);
            }

            return $products;

        } catch (PDOException $e) {
            error_log("ProductManager::get_all_products_admin error: " . $e->getMessage());
            return [];
        }
    }


    /**
     * Simple fallback fetch (no joins) in case complex query fails.
     */
    public function get_all_products_admin_simple($limit = 50, $offset = 0, $filter = 'pending') {
        try {
            $where = '1=1';
            switch ($filter) {
                case 'pending':
                    $where = "((admin_approved = 0 OR admin_approved IS NULL) AND status NOT IN ('inactive'))";
                    break;
                case 'approved':
                    $where = "(admin_approved = 1 AND status IN ('active'))";
                    break;
                case 'rejected':
                    $where = "status = 'inactive'";
                    break;
                case 'all':
                default:
                    $where = '1=1';
                    break;
            }

            $sql = "SELECT * FROM products WHERE $where ORDER BY created_at DESC LIMIT :offset, :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images'] ?? null);
            }

            return $products;
        } catch (PDOException $e) {
            error_log("ProductManager::get_all_products_admin_simple error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get pending products count / list
     */
    public function get_pending_products($limit = 100) {
        try {
            $sql = "SELECT * FROM products
                    WHERE (admin_approved = 0 OR admin_approved IS NULL) AND status NOT IN ('inactive')
                    ORDER BY created_at DESC
                    LIMIT :limit";
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();
            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as &$product) {
                $product['images'] = $this->normalizeImagePaths($product['images'] ?? null);
            }

            return $products;
        } catch (PDOException $e) {
            error_log("ProductManager::get_pending_products error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Featured products helper
     */

    
    public function getFeaturedProducts() {
        $sql = "
            SELECT 
                p.*,
                v.business_name,
                IFNULL(AVG(r.rating), 0) AS avg_rating,
                IFNULL(COUNT(r.id), 0) AS review_count
            FROM products p
            LEFT JOIN vendors v ON p.vendor_id = v.id
            LEFT JOIN product_reviews r 
                ON r.product_id = p.id 
                AND r.is_approved = 1
            WHERE p.is_featured = 1 
            AND p.status = 'active'
            GROUP BY p.id, v.business_name
            ORDER BY p.created_at DESC
            LIMIT 10
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($products as &$product) {
            $product['images'] = $this->normalizeImagePaths($product['images'] ?? null);
        }

        return $products;
    }



    /**
     * Add a product (simple)
     * $data['images'] can be array or JSON string
     */
    public function addProduct(array $data) {
        try {
            $images_json = json_encode($this->normalizeImagePaths($data['images'] ?? null));

            $sql = "INSERT INTO products
                (vendor_id, category_id, name, description, short_description, price, compare_price, sale_price, track_quantity, stock_quantity, sku, barcode, weight, weight_unit, requires_shipping, taxable, status, admin_approved, is_featured, is_flash_deal, images, created_at, updated_at)
                VALUES
                (:vendor_id, :category_id, :name, :description, :short_description, :price, :compare_price, :sale_price, :track_quantity, :stock_quantity, :sku, :barcode, :weight, :weight_unit, :requires_shipping, :taxable, :status, :admin_approved, :is_featured, :is_flash_deal, :images, NOW(), NOW())";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':vendor_id' => $data['vendor_id'] ?? null,
                ':category_id' => $data['category_id'] ?? null,
                ':name' => $data['name'] ?? '',
                ':description' => $data['description'] ?? null,
                ':short_description' => $data['short_description'] ?? null,
                ':price' => $data['price'] ?? 0,
                ':compare_price' => $data['compare_price'] ?? null,
                ':sale_price' => $data['sale_price'] ?? null,
                ':track_quantity' => $data['track_quantity'] ?? 0,
                ':stock_quantity' => $data['stock_quantity'] ?? 0,
                ':sku' => $data['sku'] ?? null,
                ':barcode' => $data['barcode'] ?? null,
                ':weight' => $data['weight'] ?? null,
                ':weight_unit' => $data['weight_unit'] ?? null,
                ':requires_shipping' => $data['requires_shipping'] ?? 1,
                ':taxable' => $data['taxable'] ?? 1,
                ':status' => $data['status'] ?? 'draft',
                ':admin_approved' => $data['admin_approved'] ?? 0,
                ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
                ':is_flash_deal' => !empty($data['is_flash_deal']) ? 1 : 0,
                ':images' => $images_json,
            ]);

            return $this->db->lastInsertId();

        } catch (PDOException $e) {
            // instead of silently logging, echo or throw to see error
            throw new Exception("ProductManager::addProduct error: " . $e->getMessage());
        }
    }


    /**
     * Update product (simple)
     */
    public function updateProduct($id, array $data) {
        try {
            $images_json = isset($data['images']) ? json_encode($this->normalizeImagePaths($data['images'])) : null;

            $sql = "UPDATE products SET
                        category_id = :category_id,
                        name = :name,
                        description = :description,
                        short_description = :short_description,
                        price = :price,
                        compare_price = :compare_price,
                        sale_price = :sale_price,
                        track_quantity = :track_quantity,
                        stock_quantity = :stock_quantity,
                        sku = :sku,
                        barcode = :barcode,
                        weight = :weight,
                        weight_unit = :weight_unit,
                        requires_shipping = :requires_shipping,
                        taxable = :taxable,
                        status = :status,
                        admin_approved = :admin_approved,
                        is_featured = :is_featured,
                        is_flash_deal = :is_flash_deal,
                        updated_at = NOW()" .
                    ($images_json !== null ? ", images = :images" : "") .
                    " WHERE id = :id";

            $stmt = $this->db->prepare($sql);

            $params = [
                ':category_id' => $data['category_id'] ?? null,
                ':name' => $data['name'] ?? '',
                ':description' => $data['description'] ?? null,
                ':short_description' => $data['short_description'] ?? null,
                ':price' => $data['price'] ?? 0,
                ':compare_price' => $data['compare_price'] ?? null,
                ':sale_price' => $data['sale_price'] ?? null,
                ':track_quantity' => $data['track_quantity'] ?? 0,
                ':stock_quantity' => $data['stock_quantity'] ?? 0,
                ':sku' => $data['sku'] ?? null,
                ':barcode' => $data['barcode'] ?? null,
                ':weight' => $data['weight'] ?? null,
                ':weight_unit' => $data['weight_unit'] ?? null,
                ':requires_shipping' => $data['requires_shipping'] ?? 1,
                ':taxable' => $data['taxable'] ?? 1,
                ':status' => $data['status'] ?? 'draft',
                ':admin_approved' => $data['admin_approved'] ?? 0,
                ':is_featured' => !empty($data['is_featured']) ? 1 : 0,
                ':is_flash_deal' => !empty($data['is_flash_deal']) ? 1 : 0,
                ':id' => $id,
            ];

            if ($images_json !== null) {
                $params[':images'] = $images_json;
            }

            return $stmt->execute($params);

        } catch (PDOException $e) {
            error_log("ProductManager::updateProduct error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Approve product (sets admin_approved = 1 and status = 'active')
     */
    public function approve_product($product_id, $admin_id = null) {
        try {
            $sql = "UPDATE products SET admin_approved = 1, status = 'active', updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $product_id]);

            if ($stmt->rowCount() > 0) {
                return ['success' => true, 'message' => 'Product approved successfully.'];
            }
            return ['success' => false, 'message' => 'No product updated.'];

        } catch (PDOException $e) {
            error_log("ProductManager::approve_product error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error approving product.'];
        }
    }

    /**
     * Reject product (sets admin_approved = 0 and status = 'inactive')
     * $reason is optional - storeable only if you have a column for it.
     */
    public function reject_product($product_id, $admin_id = null, $reason = '') {
        try {
            $sql = "UPDATE products SET admin_approved = 0, status = 'inactive', updated_at = NOW() WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':id' => $product_id]);

            if ($stmt->rowCount() > 0) {
                // If you want to store $reason you must have a column like rejection_reason in products
                // If that column exists uncomment and use:
                // $this->db->prepare("UPDATE products SET rejection_reason = :reason WHERE id = :id")->execute([':reason'=>$reason,':id'=>$product_id]);

                return ['success' => true, 'message' => 'Product rejected.'];
            }
            return ['success' => false, 'message' => 'No product updated.'];

        } catch (PDOException $e) {
            error_log("ProductManager::reject_product error: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error rejecting product.'];
        }
    }

    /**
     * Get a single product by id
     */
    public function get_product_by_id($id) {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    p.*, 
                    v.business_name, 
                    v.logo_path AS vendor_logo,  -- ✅ use actual column name here
                    v.is_verified,
                    c.name AS category_name,
                    COALESCE(AVG(r.rating), 0) AS avg_rating,
                    COUNT(r.id) AS review_count  -- ✅ add review_count too
                FROM products p
                LEFT JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_reviews r ON p.id = r.product_id AND r.is_approved = 1
                WHERE p.id = :id
                GROUP BY 
                    p.id, v.business_name, v.logo_path, v.is_verified, c.name
                LIMIT 1
            ");
            $stmt->execute([':id' => $id]);
            $product = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($product) {
                // normalize images if you store them as JSON
                $product['images'] = $this->normalizeImagePaths($product['images'] ?? null);
            }
            return $product ?: null;
        } catch (PDOException $e) {
            error_log("ProductManager::get_product_by_id error: " . $e->getMessage());
            return null;
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
                $where_conditions[] = "p.is_featured = true";
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
    public function get_products(
        $limit = 20,
        $offset = 0,
        $category_id = null,
        $search = null,
        $vendor_id = null,
        $featured_only = false,
        $status = null // 'approved', 'pending', 'rejected'
    ) {
        // Base query including reviews
        $query = "SELECT 
                    p.*,
                    v.business_name,
                    v.is_verified,
                    COALESCE(c.name, 'Uncategorized') AS category_name,
                    IFNULL(AVG(r.rating), 0) AS avg_rating,
                    IFNULL(COUNT(r.id), 0) AS review_count
                FROM products p
                LEFT JOIN vendors v ON p.vendor_id = v.id
                LEFT JOIN categories c ON p.category_id = c.id
                LEFT JOIN product_reviews r 
                    ON r.product_id = p.id 
                    AND r.is_approved = 1
                WHERE 1=1";

        // Apply filters
        if ($category_id !== null) {
            $query .= " AND p.category_id = :category_id";
        }

        if ($vendor_id !== null) {
            $query .= " AND p.vendor_id = :vendor_id";
        }

        if ($featured_only) {
            $query .= " AND p.is_featured = 1";
        }

        if ($search !== null) {
            $query .= " AND p.name LIKE :search";
        }

        // Filter by status
        if ($status !== null) {
            switch ($status) {
                case 'approved':
                    $query .= " AND p.admin_approved = 1";
                    break;
                case 'pending':
                    $query .= " AND (p.admin_approved = 0 OR p.admin_approved IS NULL)";
                    break;
                case 'rejected':
                    $query .= " AND p.admin_approved = -1";
                    break;
            }
        }

        // Group by product to calculate aggregate fields
        $query .= " GROUP BY p.id 
                    ORDER BY p.created_at DESC 
                    LIMIT :offset, :limit";

        $stmt = $this->db->prepare($query);

        // Bind parameters safely
        if ($category_id !== null) {
            $stmt->bindValue(':category_id', (int)$category_id, PDO::PARAM_INT);
        }
        if ($vendor_id !== null) {
            $stmt->bindValue(':vendor_id', (int)$vendor_id, PDO::PARAM_INT);
        }
        if ($search !== null) {
            $stmt->bindValue(':search', "%$search%", PDO::PARAM_STR);
        }
        $stmt->bindValue(':offset', (int)$offset, PDO::PARAM_INT);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);

        $stmt->execute();
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Normalise images if you have such a function
        foreach ($products as &$product) {
            $product['images'] = $this->normalizeImagePaths($product['images'] ?? null);
        }

        return $products;
    }


    // Wrapper remains the same
    public function getProducts($limit = 20, $offset = 0, $category_id = null, $search = null, $vendor_id = null, $featured_only = false, $status = null) {
        return $this->get_products($limit, $offset, $category_id, $search, $vendor_id, $featured_only, $status);
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

    public function getVendorTopProducts($vendor_id, $limit = 5) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.id, p.name, p.price,
                    COALESCE(SUM(oi.quantity),0) AS total_sold,
                    AVG(r.rating) AS avg_rating,
                    COUNT(r.id) AS review_count,
                    p.images
                FROM products p
                LEFT JOIN order_items oi ON p.id = oi.product_id
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.vendor_id = ? AND p.status = 'active'
                GROUP BY p.id
                ORDER BY total_sold DESC, p.created_at DESC
                LIMIT ?
            ");
            // bind vendor_id normally
            $stmt->bindValue(1, $vendor_id, PDO::PARAM_INT);
            // bind limit as integer!
            $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($products as &$product) {
                $product['images'] = json_decode($product['images'], true) ?: [];
                $product['total_sold'] = (int)$product['total_sold'];
                $product['avg_rating'] = $product['avg_rating'] ? round($product['avg_rating'], 1) : 0;
            }

            return $products;
        } catch (PDOException $e) {
            return [];
        }
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

     public function get_recently_viewed($user_id, $limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, rv.viewed_at
                FROM recently_viewed rv
                JOIN products p ON rv.product_id = p.id
                WHERE rv.user_id = ? AND p.status IN ('active','out_of_stock') AND p.admin_approved = true
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

    public function get_flash_deals($limit = 10) {
        try {
            $stmt = $this->db->prepare("
                SELECT p.*, c.name as category_name, v.business_name, v.is_verified,
                       AVG(r.rating) as avg_rating, COUNT(r.id) as review_count
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN vendors v ON p.vendor_id = v.id 
                LEFT JOIN reviews r ON p.id = r.product_id
                WHERE p.is_flash_deal = true AND p.flash_deal_end > CURRENT_TIMESTAMP AND p.status IN ('active','out_of_stock') AND p.admin_approved = true
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

     public function get_categories() {
        try {
            $stmt = $this->db->prepare("
                SELECT c.*, COUNT(p.id) as product_count 
                FROM categories c 
                LEFT JOIN products p ON c.id = p.category_id AND p.status IN ('active','out_of_stock') AND p.admin_approved = true
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

    public function get_products_by_category_minimal($category_id, $limit = 20, $offset = 0) {
        try {
            $stmt = $this->db->prepare("SELECT * FROM products WHERE category_id = ? AND status IN ('active','out_of_stock') AND admin_approved = true ORDER BY created_at DESC LIMIT ? OFFSET ?");
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

    public function get_products_simple($limit = 20, $offset = 0, $category_id = null, $search = null) {
        try {
            $where = ["status IN ('active','out_of_stock')", "admin_approved = true"];
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
    
    
}
// end of includes/product.php (no closing PHP tag intentionally)



