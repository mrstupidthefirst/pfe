-- JemAll Marketplace Consolidated Database for PostgreSQL
-- This file contains the complete schema including all tables and sample data

-- Note: In PostgreSQL, it's common to use a specific schema or just the 'public' schema.
-- This script assumes you are connected to the desired database.

-- Create custom types for ENUMs
DO $$ 
BEGIN
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_role') THEN
        CREATE TYPE user_role AS ENUM ('admin', 'seller', 'buyer');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'user_status') THEN
        CREATE TYPE user_status AS ENUM ('active', 'inactive', 'pending');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'item_status') THEN
        CREATE TYPE item_status AS ENUM ('active', 'inactive');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'product_status') THEN
        CREATE TYPE product_status AS ENUM ('pending', 'approved', 'rejected');
    END IF;
    IF NOT EXISTS (SELECT 1 FROM pg_type WHERE typname = 'order_status') THEN
        CREATE TYPE order_status AS ENUM ('pending', 'processing', 'shipped', 'delivered', 'cancelled');
    END IF;
END $$;

-- Create function for updating timestamps
CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

-- Drop tables if they exist (in reverse order of dependencies)
DROP TABLE IF EXISTS notifications CASCADE;
DROP TABLE IF EXISTS favorites CASCADE;
DROP TABLE IF EXISTS order_items CASCADE;
DROP TABLE IF EXISTS orders CASCADE;
DROP TABLE IF EXISTS cart CASCADE;
DROP TABLE IF EXISTS product_images CASCADE;
DROP TABLE IF EXISTS products CASCADE;
DROP TABLE IF EXISTS categories CASCADE;
DROP TABLE IF EXISTS users CASCADE;

-- Users table
CREATE TABLE users (
    id SERIAL PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role user_role NOT NULL DEFAULT 'buyer',
    full_name VARCHAR(100),
    phone VARCHAR(20),
    national_id VARCHAR(50) DEFAULT NULL,
    is_id_verified BOOLEAN DEFAULT FALSE,
    address TEXT,
    profile_picture VARCHAR(255) DEFAULT NULL,
    status user_status DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TRIGGER update_users_modtime
    BEFORE UPDATE ON users
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Categories table
CREATE TABLE categories (
    id SERIAL PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    status item_status DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Products table
CREATE TABLE products (
    id SERIAL PRIMARY KEY,
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
    status product_status DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_seller FOREIGN KEY (seller_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_category FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE RESTRICT
);

CREATE TRIGGER update_products_modtime
    BEFORE UPDATE ON products
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Product Additional Images table
CREATE TABLE product_images (
    id SERIAL PRIMARY KEY,
    product_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    display_order INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_product_images FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
);
CREATE INDEX idx_product_images_product_id ON product_images(product_id);

-- Cart table
CREATE TABLE cart (
    id SERIAL PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_cart_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT unique_cart_item UNIQUE (buyer_id, product_id)
);

-- Orders table
CREATE TABLE orders (
    id SERIAL PRIMARY KEY,
    buyer_id INT NOT NULL,
    total_amount DECIMAL(10, 2) NOT NULL,
    status order_status DEFAULT 'pending',
    shipping_address TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TRIGGER update_orders_modtime
    BEFORE UPDATE ON orders
    FOR EACH ROW
    EXECUTE FUNCTION update_updated_at_column();

-- Order items table
CREATE TABLE order_items (
    id SERIAL PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    price DECIMAL(10, 2) NOT NULL,
    CONSTRAINT fk_items_order FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
);

-- Favorites (Wishlist) table
CREATE TABLE favorites (
    id SERIAL PRIMARY KEY,
    buyer_id INT NOT NULL,
    product_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_fav_buyer FOREIGN KEY (buyer_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_fav_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT unique_favorite UNIQUE (buyer_id, product_id)
);
CREATE INDEX idx_fav_buyer_id ON favorites(buyer_id);
CREATE INDEX idx_fav_product_id ON favorites(product_id);

-- Notifications table
CREATE TABLE notifications (
    id SERIAL PRIMARY KEY,
    user_id INT NOT NULL,
    type VARCHAR(50) NOT NULL,
    message TEXT NOT NULL,
    link VARCHAR(255),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
CREATE INDEX idx_notif_user_read ON notifications(user_id, is_read);

-- INSERT SAMPLE DATA --

-- Admin user (password: admin123)
-- Note: is_id_verified uses TRUE/FALSE instead of 1/0
INSERT INTO users (username, email, password, role, full_name, status) VALUES
('admin', 'admin@jemall.com', '$2y$12$LH7OI3HP/vbLA8rlDxAS3Oynwgo9EEI4krLvUEtPGAuCsCpnufhZi', 'admin', 'Admin User', 'active');

-- Sample sellers (password: seller123)
INSERT INTO users (username, email, password, role, full_name, phone, address, status, national_id, is_id_verified) VALUES
('seller1', 'seller1@jemall.com', '$2y$12$BzLgm9UJodXioAMVCrGyfeZH1/jcgWn2HkT4Q06UfgdnvnfP81ndi', 'seller', 'John Seller', '1234567890', '123 Seller Street', 'active', 'ID123456', TRUE),
('seller2', 'seller2@jemall.com', '$2y$12$BzLgm9UJodXioAMVCrGyfeZH1/jcgWn2HkT4Q06UfgdnvnfP81ndi', 'seller', 'Jane Merchant', '0987654321', '456 Merchant Ave', 'active', 'ID654321', TRUE);

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
