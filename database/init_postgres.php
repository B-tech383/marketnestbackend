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
        
        // Create a default admin user
        $admin_password = password_hash('admin123', PASSWORD_DEFAULT);
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