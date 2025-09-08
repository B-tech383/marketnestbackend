-- Sample data for testing
USE ecommerce_db;

-- Insert sample admin user
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code) VALUES
('admin', 'admin@ecommerce.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin', 'User', '555-0001', '123 Admin St', 'Admin City', 'CA', '90210');

-- Insert admin role
INSERT INTO user_roles (user_id, role) VALUES (1, 'admin');

-- Insert sample customers
INSERT INTO users (username, email, password, first_name, last_name, phone, address, city, state, zip_code) VALUES
('john_doe', 'john@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'John', 'Doe', '555-0002', '456 Customer Ave', 'Los Angeles', 'CA', '90001'),
('jane_smith', 'jane@example.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jane', 'Smith', '555-0003', '789 Buyer Blvd', 'San Francisco', 'CA', '94102');

-- Insert customer roles
INSERT INTO user_roles (user_id, role) VALUES (2, 'customer'), (3, 'customer');

-- Insert sample categories
INSERT INTO categories (name, description) VALUES
('Electronics', 'Electronic devices and gadgets'),
('Clothing', 'Fashion and apparel'),
('Home & Garden', 'Home improvement and garden supplies'),
('Books', 'Books and educational materials'),
('Sports', 'Sports equipment and accessories');

-- Insert sample badges
INSERT INTO badges (name, description, icon, color) VALUES
('Verified Seller', 'Verified and trusted seller', 'verified', '#f97316'),
('Top Rated', 'Highly rated by customers', 'star', '#f97316'),
('Fast Shipping', 'Ships orders quickly', 'shipping', '#f97316');

-- Insert sample vendor application
INSERT INTO vendor_applications (name, email, business_name, description, status) VALUES
('Tech Store Owner', 'techstore@example.com', 'Tech Paradise', 'We sell the latest electronic gadgets and accessories', 'pending');

-- Insert sample coupons
INSERT INTO coupons (code, type, value, minimum_amount, usage_limit, expires_at) VALUES
('WELCOME10', 'percentage', 10.00, 50.00, 100, DATE_ADD(NOW(), INTERVAL 30 DAY)),
('SAVE20', 'fixed', 20.00, 100.00, 50, DATE_ADD(NOW(), INTERVAL 60 DAY));
