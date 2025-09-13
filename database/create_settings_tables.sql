-- Settings table for storing admin configuration
CREATE TABLE IF NOT EXISTS settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_key VARCHAR(100) UNIQUE NOT NULL,
    setting_value TEXT,
    setting_type ENUM('text', 'email', 'number', 'boolean', 'json') DEFAULT 'text',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Advertisements table for managing ads
CREATE TABLE IF NOT EXISTS advertisements (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    content TEXT,
    image_url VARCHAR(500),
    video_url VARCHAR(500),
    link_url VARCHAR(500),
    ad_type ENUM('banner', 'sidebar', 'popup', 'inline') DEFAULT 'banner',
    position VARCHAR(50) DEFAULT 'top',
    is_active BOOLEAN DEFAULT TRUE,
    start_date DATETIME,
    end_date DATETIME,
    click_count INT DEFAULT 0,
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default settings
INSERT INTO settings (setting_key, setting_value, setting_type, description) VALUES
('site_name', 'Market Nest', 'text', 'Website name'),
('site_description', 'Your Premier Marketplace Destination', 'text', 'Website description'),
('admin_email', 'admin@marketnest.com', 'email', 'Admin contact email'),
('support_email', 'support@marketnest.com', 'email', 'Support contact email'),
('smtp_host', 'smtp.gmail.com', 'text', 'SMTP server host'),
('smtp_port', '587', 'number', 'SMTP server port'),
('smtp_username', '', 'text', 'SMTP username'),
('smtp_password', '', 'text', 'SMTP password'),
('smtp_encryption', 'tls', 'text', 'SMTP encryption type'),
('stripe_public_key', '', 'text', 'Stripe public key'),
('stripe_secret_key', '', 'text', 'Stripe secret key'),
('paypal_client_id', '', 'text', 'PayPal client ID'),
('paypal_client_secret', '', 'text', 'PayPal client secret'),
('email_notifications', '1', 'boolean', 'Enable email notifications'),
('sms_notifications', '0', 'boolean', 'Enable SMS notifications'),
('order_notifications', '1', 'boolean', 'Enable order notifications'),
('vendor_notifications', '1', 'boolean', 'Enable vendor notifications'),
('maintenance_mode', '0', 'boolean', 'Enable maintenance mode'),
('maintenance_message', 'We are currently performing maintenance. Please check back later.', 'text', 'Maintenance mode message')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

