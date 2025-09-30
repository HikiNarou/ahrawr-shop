-- Database setup for AhRawr Shop E-commerce Application
-- Run this SQL to create the required database and tables

CREATE DATABASE IF NOT EXISTS ecommerce;
USE ecommerce;

-- Users table for authentication
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    phone VARCHAR(20) DEFAULT '',
    address TEXT DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table for e-commerce
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT DEFAULT '',
    price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    image VARCHAR(255) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_products_name (name),
    INDEX idx_products_price (price)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User sessions table (created automatically by AccountService, but included for completeness)
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    session_id VARCHAR(191) NOT NULL UNIQUE,
    session_token VARCHAR(191) NOT NULL,
    user_agent VARCHAR(255) NOT NULL,
    ip_address VARCHAR(64) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User activity log table (created automatically by AccountService, but included for completeness)  
CREATE TABLE IF NOT EXISTS user_activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(32) NOT NULL,
    message VARCHAR(255) NOT NULL,
    ip_address VARCHAR(64) DEFAULT '',
    user_agent VARCHAR(255) DEFAULT '',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_activity_user (user_id),
    INDEX idx_user_activity_category (category),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert some sample products
INSERT INTO products (name, description, price, image) VALUES
('Laptop Gaming', 'Laptop gaming dengan spesifikasi tinggi untuk gaming dan produktivitas', 15000000.00, ''),
('Smartphone Android', 'Smartphone Android terbaru dengan kamera canggih dan performa optimal', 8000000.00, ''),
('Headphone Wireless', 'Headphone wireless dengan noise cancelling dan kualitas suara premium', 2500000.00, ''),
('Keyboard Mechanical', 'Keyboard mechanical dengan switch tactile untuk typing dan gaming', 1200000.00, ''),
('Mouse Gaming', 'Mouse gaming dengan sensor presisi tinggi dan desain ergonomis', 800000.00, '');