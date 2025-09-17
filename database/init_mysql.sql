-- MySQL Database Schema for Market Nest E-commerce Platform
-- Drop database if exists and create fresh
DROP DATABASE IF EXISTS ecommerce_db;
CREATE DATABASE ecommerce_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ecommerce_db;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
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
    country VARCHAR(50),
    free_trackings_used INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- User roles table for role-based access control
CREATE TABLE user_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    role VARCHAR(20) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_role (user_id, role)
);

-- Categories table
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    image_url VARCHAR(255),
    parent_id INT NULL,
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Vendors table
CREATE TABLE vendors (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    business_name VARCHAR(100) NOT NULL,
    business_email VARCHAR(100),
    business_phone VARCHAR(20),
    business_address TEXT,
    description TEXT,
    logo_path text,
    tax_id VARCHAR(50),
    business_license VARCHAR(100),
    is_verified BOOLEAN DEFAULT FALSE,
    status ENUM('pending', 'approved', 'suspended', 'rejected') DEFAULT 'pending',
    application_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    approval_date TIMESTAMP NULL,
    commission_rate DECIMAL(5,2) DEFAULT 15.00,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Products table
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    short_description TEXT,
    price DECIMAL(10,2) NOT NULL,
    compare_price DECIMAL(10,2),
    cost_price DECIMAL(10,2),
    track_quantity BOOLEAN DEFAULT TRUE,
    stock_quantity INT DEFAULT 0,
    low_stock_threshold INT DEFAULT 5,
    sku VARCHAR(100),
    barcode VARCHAR(100),
    weight DECIMAL(8,2),
    weight_unit ENUM('kg', 'g', 'lb', 'oz') DEFAULT 'kg',
    requires_shipping BOOLEAN DEFAULT TRUE,
    taxable BOOLEAN DEFAULT TRUE,
    status ENUM('active', 'draft', 'archived') DEFAULT 'draft',
    admin_approved BOOLEAN DEFAULT FALSE,
    featured BOOLEAN DEFAULT FALSE,
    meta_title VARCHAR(200),
    meta_description TEXT,
    seo_url VARCHAR(200),
    image_url VARCHAR(255),
    gallery JSON,
    tags JSON,
    options JSON,
    variants JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    INDEX idx_vendor_status (vendor_id, status),
    INDEX idx_category_status (category_id, status),
    INDEX idx_sku (sku),
    INDEX idx_status_approved (status, admin_approved)
);

-- Orders table
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    order_number VARCHAR(20) UNIQUE NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    tax_amount DECIMAL(10,2) DEFAULT 0,
    shipping_amount DECIMAL(10,2) DEFAULT 0,
    discount_amount DECIMAL(10,2) DEFAULT 0,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded') DEFAULT 'pending',
    payment_status ENUM('pending', 'paid', 'failed', 'refunded', 'partial') DEFAULT 'pending',
    payment_method VARCHAR(50),
    payment_transaction_id VARCHAR(100),
    shipping_address TEXT NOT NULL,
    billing_address TEXT NOT NULL,
    notes TEXT,
    verified_by INT NULL,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (verified_by) REFERENCES users(id),
    INDEX idx_user_status (user_id, status),
    INDEX idx_order_number (order_number),
    INDEX idx_payment_status (payment_status)
);

-- Order items table
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    vendor_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    product_name VARCHAR(200) NOT NULL,
    product_sku VARCHAR(100),
    variant_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    INDEX idx_order_vendor (order_id, vendor_id),
    INDEX idx_vendor_product (vendor_id, product_id)
);

-- Shopping cart table
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    variant_data JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product_variant (user_id, product_id, variant_data(100))
);

-- Shipments table
CREATE TABLE shipments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    vendor_id INT NOT NULL,
    tracking_number VARCHAR(100) UNIQUE,
    carrier VARCHAR(50),
    service_level ENUM('standard', 'express', 'overnight') DEFAULT 'standard',
    status ENUM('pending', 'picked_up', 'in_transit', 'out_for_delivery', 'delivered', 'exception', 'returned') DEFAULT 'pending',
    shipped_at TIMESTAMP NULL,
    estimated_delivery TIMESTAMP NULL,
    delivered_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id),
    INDEX idx_tracking (tracking_number),
    INDEX idx_order_vendor (order_id, vendor_id)
);

-- Shipment tracking history
CREATE TABLE shipment_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    shipment_id INT NOT NULL,
    status VARCHAR(50) NOT NULL,
    location VARCHAR(200),
    description TEXT,
    timestamp TIMESTAMP NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (shipment_id) REFERENCES shipments(id) ON DELETE CASCADE,
    INDEX idx_shipment_timestamp (shipment_id, timestamp)
);

-- Vendor notifications table
CREATE TABLE vendor_notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vendor_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vendor_id) REFERENCES vendors(id) ON DELETE CASCADE,
    INDEX idx_vendor_read (vendor_id, is_read),
    INDEX idx_vendor_created (vendor_id, created_at)
);

-- Coupons table
CREATE TABLE coupons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    type ENUM('percentage', 'fixed_amount', 'free_shipping') NOT NULL,
    value DECIMAL(10,2) NOT NULL,
    minimum_amount DECIMAL(10,2) DEFAULT 0,
    maximum_discount DECIMAL(10,2) NULL,
    usage_limit INT NULL,
    used_count INT DEFAULT 0,
    user_limit INT DEFAULT 1,
    is_active BOOLEAN DEFAULT TRUE,
    starts_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_code_active (code, is_active),
    INDEX idx_active_dates (is_active, starts_at, expires_at)
);

-- Coupon usage tracking
CREATE TABLE coupon_usage (
    id INT AUTO_INCREMENT PRIMARY KEY,
    coupon_id INT NOT NULL,
    user_id INT NOT NULL,
    order_id INT NOT NULL,
    discount_amount DECIMAL(10,2) NOT NULL,
    used_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (coupon_id) REFERENCES coupons(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    INDEX idx_coupon_user (coupon_id, user_id)
);

-- Wishlist table
CREATE TABLE wishlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_product (user_id, product_id)
);

-- Product reviews table
CREATE TABLE product_reviews (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    user_id INT NOT NULL,
    order_item_id INT NULL,
    rating INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
    title VARCHAR(200),
    comment TEXT,
    is_verified_purchase BOOLEAN DEFAULT FALSE,
    is_approved BOOLEAN DEFAULT FALSE,
    helpful_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (order_item_id) REFERENCES order_items(id) ON DELETE SET NULL,
    INDEX idx_product_approved (product_id, is_approved),
    INDEX idx_user_product (user_id, product_id)
);

-- Insert initial categories
INSERT INTO categories (name, description, image_url) VALUES
('Electronics', 'Electronic devices and accessories', 'https://images.unsplash.com/photo-1498049794561-7780e7231661?w=300'),
('Clothing', 'Fashion and apparel', 'https://images.unsplash.com/photo-1441986300917-64674bd600d8?w=300'),
('Home & Garden', 'Home improvement and garden supplies', 'https://images.unsplash.com/photo-1449824913935-59a10b8d2000?w=300'),
('Sports & Outdoors', 'Sports equipment and outdoor gear', 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=300'),
('Books & Media', 'Books, movies, and digital media', 'https://images.unsplash.com/photo-1481627834876-b7833e8f5570?w=300');

-- Insert admin user
INSERT INTO users (username, email, password, first_name, last_name, created_at) VALUES
('admin', 'admin@marketnest.com', '$2y$10$v3qKL0A5BQ3Z1.MQM1K7K.YKmGkxLY8qX1Y2Z3A4B5C6D7E8F9G0H1', 'Admin', 'User', NOW());

-- Insert admin role
INSERT INTO user_roles (user_id, role) VALUES
((SELECT id FROM users WHERE username = 'admin'), 'admin');

-- Insert test vendor user
INSERT INTO users (username, email, password, first_name, last_name, created_at) VALUES
('testvendor', 'vendor@test.com', '$2y$10$v3qKL0A5BQ3Z1.MQM1K7K.YKmGkxLY8qX1Y2Z3A4B5C6D7E8F9G0H1', 'Test', 'Vendor', NOW());

-- Insert vendor role
INSERT INTO user_roles (user_id, role) VALUES
((SELECT id FROM users WHERE username = 'testvendor'), 'vendor');

-- Insert test vendor business
INSERT INTO vendors (user_id, business_name, business_email, status, is_verified, application_date, approval_date) VALUES
((SELECT id FROM users WHERE username = 'testvendor'), 'Test Business', 'vendor@test.com', 'approved', TRUE, NOW(), NOW());

-- Insert test customer
INSERT INTO users (username, email, password, first_name, last_name, created_at) VALUES
('customer', 'customer@test.com', '$2y$10$v3qKL0A5BQ3Z1.MQM1K7K.YKmGkxLY8qX1Y2Z3A4B5C6D7E8F9G0H1', 'Test', 'Customer', NOW());

-- Insert customer role
INSERT INTO user_roles (user_id, role) VALUES
((SELECT id FROM users WHERE username = 'customer'), 'customer');

-- Insert test product
INSERT INTO products (vendor_id, category_id, name, description, price, stock_quantity, status, admin_approved) VALUES
((SELECT id FROM vendors WHERE business_name = 'Test Business'), 1, 'Test Product', 'A test product for demonstration', 99.99, 10, 'active', TRUE);

-- Insert test coupons
INSERT INTO coupons (code, name, description, type, value, minimum_amount, is_active, starts_at, expires_at) VALUES
('WELCOME10', 'Welcome Discount', '10% off for new customers', 'percentage', 10.00, 50.00, TRUE, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY)),
('FREESHIP', 'Free Shipping', 'Free shipping on orders over $100', 'free_shipping', 0.00, 100.00, TRUE, NOW(), DATE_ADD(NOW(), INTERVAL 60 DAY));