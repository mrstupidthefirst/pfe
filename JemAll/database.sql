-- JemAll Marketplace Consolidated Database
-- This file contains the complete schema including all migrations and sample data

-- Drop database if exists and create new one
DROP DATABASE IF EXISTS jemall_db;
CREATE DATABASE jemall_db;
USE jemall_db;

-- Users table (handles Admin, Seller, and Buyer roles)
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'seller', 'buyer') NOT NULL DEFAULT 'buyer',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    national_id VARCHAR(50) DEFAULT NULL,
    is_id_verified TINYINT(1) DEFAULT 0,
    address TEXT,
    profile_picture VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'pending') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    seller_id INT NOT NULL,
    category_id INT NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    genre VARCHAR(100) DEFAULT NULL,
    sizes TEXT DEFAULT NULL,
    subcategory VARCHAR(100) DEFAULT NULL,
    price DECIMAL(10, 2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    min_qty INT NOT NULL DEFAULT 1,
    max_qty INT NOT NULL DEFAULT 100,
    image VARCHAR(255),
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product Additional Images table
CREATE TABLE product_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    INDEX idx_product_id (product_id),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart table
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (buyer_id, product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Orders table
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status ENUM('pending', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE order_items (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Favorites (Wishlist) table
CREATE TABLE favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (buyer_id, product_id),
    INDEX idx_buyer_id (buyer_id),
    INDEX idx_product_id (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Notifications table
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- INSERT SAMPLE DATA --

-- Admin user (password: admin123)
INSERT INTO users (username, email, password, role, full_name, status) VALUES
('admin', 'admin@jemall.com', '$2y$12$LH7OI3HP/vbLA8rlDxAS3Oynwgo9EEI4krLvUEtPGAuCsCpnufhZi', 'admin', 'Admin User', 'active');

-- Sample sellers (password: seller123)
INSERT INTO users (username, email, password, role, full_name, phone, address, status, national_id, is_id_verified) VALUES
('seller1', 'seller1@jemall.com', '$2y$12$BzLgm9UJodXioAMVCrGyfeZH1/jcgWn2HkT4Q06UfgdnvnfP81ndi', 'seller', 'John Seller', '1234567890', '123 Seller Street', 'active', 'ID123456', 1),
('seller2', 'seller2@jemall.com', '$2y$12$BzLgm9UJodXioAMVCrGyfeZH1/jcgWn2HkT4Q06UfgdnvnfP81ndi', 'seller', 'Jane Merchant', '0987654321', '456 Merchant Ave', 'active', 'ID654321', 1);

-- Sample buyer (password: buyer123)
INSERT INTO users (username, email, password, role, full_name, phone, address, status) VALUES
('buyer1', 'buyer1@jemall.com', '$2y$12$6M3ILq1PYpZQVIIhtE3/xO3wKq/Pz.HVWaEJPiGWU4kQygd3Yxuju', 'buyer', 'Bob Buyer', '5551234567', '789 Buyer Road', 'active');

-- Sample categories
INSERT INTO categories (name, description, status) VALUES
('Electronics', 'Electronic devices and gadgets', 'active'),
('Clothing', 'Apparel and fashion items', 'active'),
('Books', 'Books and reading materials', 'active'),
('Home & Garden', 'Home improvement and garden supplies', 'active'),
('Sports', 'Sports equipment and accessories', 'active');

-- Sample products
INSERT INTO products (seller_id, category_id, name, description, genre, sizes, subcategory, price, stock, min_qty, max_qty, image, status) VALUES
(2, 1, 'Wireless Mouse', 'Ergonomic wireless mouse with 2.4GHz connectivity', 'Tech', NULL, 'Accessories', 29.99, 50, 1, 10, 'mouse.jpg', 'approved'),
(2, 1, 'USB-C Cable', 'Fast charging USB-C cable, 6ft length', 'Tech', NULL, 'Cables', 12.99, 100, 1, 50, 'cable.jpg', 'approved'),
(3, 2, 'Cotton T-Shirt', '100% cotton comfortable t-shirt', 'Fashion', 'S, M, L, XL', 'T-Shirts', 19.99, 75, 1, 20, 'tshirt.jpg', 'approved'),
(3, 2, 'Denim Jeans', 'Classic blue denim jeans', 'Fashion', '30, 32, 34, 36', 'Pants', 49.99, 40, 1, 10, 'jeans.jpg', 'approved'),
(2, 3, 'Programming Book', 'Learn PHP programming from scratch', 'Education', NULL, 'Computers', 34.99, 30, 1, 5, 'book.jpg', 'approved'),
(3, 4, 'Garden Tools Set', 'Complete set of garden tools', 'Home', NULL, 'Garden', 79.99, 20, 1, 5, 'tools.jpg', 'pending'),
(2, 5, 'Yoga Mat', 'Premium non-slip yoga mat', 'Sports', NULL, 'Yoga', 24.99, 60, 1, 20, 'yogamat.jpg', 'approved');

