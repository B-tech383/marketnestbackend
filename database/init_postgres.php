<?php
require_once __DIR__ . '/../config/database.php';

function initializePostgresDatabase() {
    try {
        $database = new Database();
        $conn = $database->getConnection();
        
        if ($conn) {
            echo "Database connection established successfully.\n";
            
            // Create tables with PostgreSQL syntax
            $tables = [
                "CREATE TABLE IF NOT EXISTS users (
                    id SERIAL PRIMARY KEY,
                    username VARCHAR(50) UNIQUE NOT NULL,
                    email VARCHAR(100) UNIQUE NOT NULL,
                    password VARCHAR(255) NOT NULL,
                    first_name VARCHAR(50) NOT NULL,
                    last_name VARCHAR(50) NOT NULL,
                    phone VARCHAR(20),
                    address TEXT,
                    city VARCHAR(50),
                    state VARCHAR(50),
                    zip_code VARCHAR(10),
                    country VARCHAR(50) DEFAULT 'Cameroon',
                    free_trackings_used INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS user_roles (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    role VARCHAR(20) NOT NULL DEFAULT 'customer' CHECK (role IN ('customer', 'vendor', 'admin')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )",
                
                "CREATE TABLE IF NOT EXISTS categories (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    description TEXT,
                    image_path VARCHAR(255),
                    icon VARCHAR(10),
                    parent_id INTEGER NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (parent_id) REFERENCES categories(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS vendors (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER UNIQUE NOT NULL,
                    business_name VARCHAR(100) NOT NULL,
                    description TEXT,
                    logo_path VARCHAR(255),
                    is_verified BOOLEAN DEFAULT FALSE,
                    verification_badge VARCHAR(50),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )",
                
                "CREATE TABLE IF NOT EXISTS products (
                    id SERIAL PRIMARY KEY,
                    vendor_id INTEGER NOT NULL,
                    category_id INTEGER NOT NULL,
                    name VARCHAR(200) NOT NULL,
                    description TEXT,
                    price DECIMAL(10,2) NOT NULL,
                    sale_price DECIMAL(10,2) NULL,
                    stock_quantity INTEGER DEFAULT 0,
                    sku VARCHAR(100) UNIQUE,
                    images TEXT,
                    is_featured BOOLEAN DEFAULT FALSE,
                    is_flash_deal BOOLEAN DEFAULT FALSE,
                    flash_deal_end TIMESTAMP NULL,
                    status VARCHAR(20) DEFAULT 'active' CHECK (status IN ('active', 'inactive', 'out_of_stock')),
                    admin_approved BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
                    FOREIGN KEY (category_id) REFERENCES categories(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS orders (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    order_number VARCHAR(50) UNIQUE NOT NULL,
                    total_amount DECIMAL(10,2) NOT NULL,
                    tax_amount DECIMAL(10,2) DEFAULT 0,
                    shipping_amount DECIMAL(10,2) DEFAULT 0,
                    discount_amount DECIMAL(10,2) DEFAULT 0,
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'processing', 'shipped', 'delivered', 'cancelled')),
                    payment_status VARCHAR(20) DEFAULT 'pending' CHECK (payment_status IN ('pending', 'paid', 'failed', 'refunded')),
                    shipping_address TEXT NOT NULL,
                    billing_address TEXT NOT NULL,
                    verified_by INTEGER,
                    verified_at TIMESTAMP,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (verified_by) REFERENCES users(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS order_items (
                    id SERIAL PRIMARY KEY,
                    order_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    vendor_id INTEGER NOT NULL,
                    quantity INTEGER NOT NULL,
                    price DECIMAL(10,2) NOT NULL,
                    total DECIMAL(10,2) NOT NULL,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id),
                    FOREIGN KEY (vendor_id) REFERENCES vendors(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS cart (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    quantity INTEGER NOT NULL DEFAULT 1,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    UNIQUE(user_id, product_id)
                )",
                
                "CREATE TABLE IF NOT EXISTS wishlist (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    UNIQUE(user_id, product_id)
                )",
                
                "CREATE TABLE IF NOT EXISTS notifications (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    title VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
                )",
                
                "CREATE TABLE IF NOT EXISTS vendor_notifications (
                    id SERIAL PRIMARY KEY,
                    vendor_id INTEGER NOT NULL,
                    type VARCHAR(50) NOT NULL,
                    title VARCHAR(200) NOT NULL,
                    message TEXT NOT NULL,
                    is_read BOOLEAN DEFAULT FALSE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE
                )",
                
                "CREATE TABLE IF NOT EXISTS vendor_applications (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    email VARCHAR(100) NOT NULL,
                    business_name VARCHAR(100) NOT NULL,
                    description TEXT NOT NULL,
                    logo_path VARCHAR(255),
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'approved', 'rejected')),
                    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    reviewed_at TIMESTAMP NULL,
                    reviewed_by INTEGER,
                    FOREIGN KEY (reviewed_by) REFERENCES users(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS reviews (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    order_id INTEGER NULL,
                    rating INTEGER NOT NULL CHECK (rating >= 1 AND rating <= 5),
                    title VARCHAR(200),
                    comment TEXT,
                    is_verified_purchase BOOLEAN DEFAULT TRUE,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id),
                    FOREIGN KEY (product_id) REFERENCES products(id),
                    FOREIGN KEY (order_id) REFERENCES orders(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS shipments (
                    id SERIAL PRIMARY KEY,
                    order_id INTEGER NOT NULL,
                    tracking_number VARCHAR(100) UNIQUE NOT NULL,
                    carrier VARCHAR(50) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'pending_approval', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered')),
                    current_location VARCHAR(100),
                    estimated_delivery TIMESTAMP NULL,
                    shipped_at TIMESTAMP NULL,
                    delivered_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
                )",
                
                "CREATE TABLE IF NOT EXISTS tracking_history (
                    id SERIAL PRIMARY KEY,
                    shipment_id INTEGER NOT NULL,
                    status VARCHAR(100) NOT NULL,
                    location VARCHAR(100),
                    description TEXT,
                    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE
                )",
                
                "CREATE TABLE IF NOT EXISTS payments (
                    id SERIAL PRIMARY KEY,
                    order_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    amount DECIMAL(10,2) NOT NULL,
                    payment_method VARCHAR(50) NOT NULL,
                    transaction_id VARCHAR(100),
                    transaction_reference VARCHAR(100),
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'completed', 'failed', 'refunded')),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id),
                    FOREIGN KEY (user_id) REFERENCES users(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS badges (
                    id SERIAL PRIMARY KEY,
                    name VARCHAR(50) NOT NULL,
                    description TEXT,
                    icon VARCHAR(100),
                    color VARCHAR(20) DEFAULT '#f97316',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS coupons (
                    id SERIAL PRIMARY KEY,
                    code VARCHAR(50) UNIQUE NOT NULL,
                    name VARCHAR(200),
                    description TEXT,
                    type VARCHAR(20) NOT NULL CHECK (type IN ('percentage', 'fixed', 'free_product')),
                    value DECIMAL(10,2) NOT NULL,
                    minimum_amount DECIMAL(10,2) DEFAULT 0,
                    maximum_discount DECIMAL(10,2) DEFAULT NULL,
                    usage_limit INTEGER DEFAULT NULL,
                    used_count INTEGER DEFAULT 0,
                    is_active BOOLEAN DEFAULT TRUE,
                    start_date TIMESTAMP NULL,
                    end_date TIMESTAMP NULL,
                    expires_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS coupon_usage (
                    id SERIAL PRIMARY KEY,
                    coupon_id INTEGER NOT NULL,
                    user_id INTEGER NOT NULL,
                    order_id INTEGER NOT NULL,
                    discount_amount DECIMAL(10,2) NOT NULL,
                    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    UNIQUE (coupon_id, order_id)
                )",
                
                "CREATE TABLE IF NOT EXISTS coupon_products (
                    id SERIAL PRIMARY KEY,
                    coupon_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    UNIQUE (coupon_id, product_id)
                )",
                
                "CREATE TABLE IF NOT EXISTS recently_viewed (
                    id SERIAL PRIMARY KEY,
                    user_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
                    UNIQUE (user_id, product_id)
                )",
                
                "CREATE TABLE IF NOT EXISTS settings (
                    id SERIAL PRIMARY KEY,
                    setting_key VARCHAR(100) UNIQUE NOT NULL,
                    setting_value TEXT,
                    setting_type VARCHAR(20) DEFAULT 'text' CHECK (setting_type IN ('text', 'email', 'number', 'boolean', 'json')),
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS advertisements (
                    id SERIAL PRIMARY KEY,
                    title VARCHAR(200) NOT NULL,
                    content TEXT,
                    image_url VARCHAR(500),
                    video_url VARCHAR(500),
                    link_url VARCHAR(500),
                    ad_type VARCHAR(20) DEFAULT 'banner' CHECK (ad_type IN ('banner', 'sidebar', 'popup', 'inline')),
                    position VARCHAR(50) DEFAULT 'top',
                    is_active BOOLEAN DEFAULT TRUE,
                    start_date TIMESTAMP,
                    end_date TIMESTAMP,
                    click_count INTEGER DEFAULT 0,
                    view_count INTEGER DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )",
                
                "CREATE TABLE IF NOT EXISTS vendor_commissions (
                    id SERIAL PRIMARY KEY,
                    vendor_id INTEGER NOT NULL,
                    commission_rate DECIMAL(5,2) NOT NULL DEFAULT 10.00 CHECK (commission_rate >= 0 AND commission_rate <= 50),
                    effective_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    is_active BOOLEAN DEFAULT TRUE,
                    created_by INTEGER NULL,
                    notes TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
                    FOREIGN KEY (created_by) REFERENCES users(id)
                )",
                
                "CREATE TABLE IF NOT EXISTS commission_transactions (
                    id SERIAL PRIMARY KEY,
                    order_id INTEGER NOT NULL,
                    vendor_id INTEGER NOT NULL,
                    order_item_id INTEGER NOT NULL,
                    product_id INTEGER NOT NULL,
                    sale_amount DECIMAL(10,2) NOT NULL,
                    commission_rate DECIMAL(5,2) NOT NULL,
                    commission_amount DECIMAL(10,2) NOT NULL,
                    status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'paid', 'cancelled')),
                    payment_reference VARCHAR(100) NULL,
                    paid_at TIMESTAMP NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
                    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
                    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE CASCADE,
                    FOREIGN KEY (product_id) REFERENCES products(id)
                )"
            ];
            
            foreach ($tables as $table_sql) {
                try {
                    $conn->exec($table_sql);
                    echo "Table created successfully.\n";
                } catch (Exception $e) {
                    echo "Error creating table: " . $e->getMessage() . "\n";
                }
            }
            
            // Create trigger function for updated_at columns
            $trigger_function = "
                CREATE OR REPLACE FUNCTION update_updated_at_column()
                RETURNS TRIGGER AS $$
                BEGIN
                    NEW.updated_at = CURRENT_TIMESTAMP;
                    RETURN NEW;
                END;
                $$ language 'plpgsql';
            ";
            
            try {
                $conn->exec($trigger_function);
                echo "Trigger function created successfully.\n";
            } catch (Exception $e) {
                echo "Error creating trigger function: " . $e->getMessage() . "\n";
            }
            
            // Create triggers for tables with updated_at columns
            $tables_with_updated_at = [
                'users',
                'cart',
                'orders',
                'products',
                'shipments',
                'coupons',
                'settings',
                'advertisements',
                'commission_transactions'
            ];
            
            foreach ($tables_with_updated_at as $table) {
                $trigger_sql = "
                    CREATE TRIGGER update_{$table}_updated_at
                    BEFORE UPDATE ON {$table}
                    FOR EACH ROW
                    EXECUTE FUNCTION update_updated_at_column();
                ";
                
                try {
                    $conn->exec($trigger_sql);
                    echo "Trigger for {$table} created successfully.\n";
                } catch (Exception $e) {
                    echo "Error creating trigger for {$table}: " . $e->getMessage() . "\n";
                }
            }
            
            echo "Database schema created successfully.\n";
            
            // Insert sample data
            insertSampleData($conn);
            
            return true;
        } else {
            echo "Failed to connect to database.\n";
            return false;
        }
    } catch (Exception $e) {
        echo "Error initializing database: " . $e->getMessage() . "\n";
        return false;
    }
}

function insertSampleData($conn) {
    try {
        // Insert sample categories
        $categories = [
            ['Electronics', 'Electronic gadgets and devices', '📱'],
            ['Clothing', 'Fashion and apparel', '👕'],
            ['Home & Garden', 'Home improvement and garden items', '🏠'],
            ['Books', 'Books and educational materials', '📚'],
            ['Sports', 'Sports and outdoor equipment', '⚽'],
            ['Beauty', 'Beauty and personal care products', '💄']
        ];
        
        $stmt = $conn->prepare("INSERT INTO categories (name, description, icon) VALUES (?, ?, ?) ON CONFLICT DO NOTHING");
        foreach ($categories as $category) {
            $stmt->execute([$category[0], $category[1], $category[2]]);
        }
        
        // Create a default admin user with secure password
        $secure_password = bin2hex(random_bytes(16)); // Generate random 32-character password
        $admin_password = password_hash($secure_password, PASSWORD_DEFAULT);
        echo "Generated admin password: " . $secure_password . "\n";
        echo "Please save this password securely for admin access!\n";
        
        $stmt = $conn->prepare("INSERT INTO users (username, email, password, first_name, last_name) VALUES (?, ?, ?, ?, ?) ON CONFLICT (username) DO NOTHING");
        $stmt->execute(['admin', 'admin@marketnest.com', $admin_password, 'Admin', 'User']);
        
        // Get admin user ID
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute(['admin']);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $admin_id = $result['id'];
            
            // Assign admin role
            $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?) ON CONFLICT DO NOTHING");
            $stmt->execute([$admin_id, 'admin']);
            
            echo "Admin user created/updated successfully.\n";
        }
        
        echo "Sample data inserted successfully.\n";
        
    } catch (Exception $e) {
        echo "Error inserting sample data: " . $e->getMessage() . "\n";
    }
}

// Run the initialization
echo "Initializing PostgreSQL database for Market Nest...\n";
initializePostgresDatabase();
?>