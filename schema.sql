-- MMK Hub Database Schema for MySQL / MariaDB (XAMPP)
CREATE DATABASE IF NOT EXISTS mmkhub_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mmkhub_db;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(64) UNIQUE,
    name_en VARCHAR(255) NOT NULL,
    name_fr VARCHAR(255) NOT NULL,
    category VARCHAR(100) NOT NULL,
    description_en TEXT,
    description_fr TEXT,
    image VARCHAR(255),
    is_service TINYINT DEFAULT 0,
    is_bundle TINYINT DEFAULT 0,
    retail_price DECIMAL(12,2) NOT NULL,
    wholesale_price DECIMAL(12,2) NOT NULL,
    moq INT DEFAULT 1,
    stock INT DEFAULT 100,
    tier_discounts JSON,
    active TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(32) NOT NULL UNIQUE,
    email VARCHAR(150),
    password VARCHAR(255),
    role ENUM('retail', 'wholesale_pending', 'wholesale_approved', 'admin') DEFAULT 'retail',
    company_name VARCHAR(255),
    tax_id VARCHAR(100),
    city VARCHAR(100) DEFAULT 'Douala',
    address TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_code VARCHAR(32) UNIQUE,
    user_id INT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(32) NOT NULL,
    customer_email VARCHAR(150),
    city VARCHAR(100) NOT NULL,
    delivery_address TEXT NOT NULL,
    delivery_method VARCHAR(50) DEFAULT 'standard',
    delivery_fee DECIMAL(10,2) DEFAULT 0,
    order_type ENUM('retail', 'wholesale') DEFAULT 'retail',
    subtotal DECIMAL(12,2) NOT NULL,
    discount_amount DECIMAL(12,2) DEFAULT 0,
    total_amount DECIMAL(12,2) NOT NULL,
    deposit_amount DECIMAL(12,2) DEFAULT 0,
    balance_amount DECIMAL(12,2) DEFAULT 0,
    payment_method VARCHAR(50) NOT NULL,
    payment_status ENUM('pending', 'deposit_paid', 'fully_paid') DEFAULT 'pending',
    fulfillment_status ENUM('received', 'processing', 'shipped', 'delivered', 'cancelled') DEFAULT 'received',
    payment_ref VARCHAR(100),
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price DECIMAL(12,2) NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    is_service TINYINT DEFAULT 0,
    service_date VARCHAR(32),
    service_slot VARCHAR(64),
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS quotes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    quote_code VARCHAR(32) UNIQUE,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(32) NOT NULL,
    customer_email VARCHAR(150),
    company_name VARCHAR(255),
    product_id INT NULL,
    product_name VARCHAR(255) NOT NULL,
    requested_qty INT NOT NULL,
    target_price DECIMAL(12,2),
    quoted_price DECIMAL(12,2),
    status ENUM('new', 'replied', 'won', 'closed') DEFAULT 'new',
    message TEXT,
    owner_reply TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS service_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    service_id INT NOT NULL,
    customer_name VARCHAR(255) NOT NULL,
    customer_phone VARCHAR(32) NOT NULL,
    service_date VARCHAR(32) NOT NULL,
    service_slot VARCHAR(64) NOT NULL,
    tier VARCHAR(50) DEFAULT 'Standard',
    address TEXT NOT NULL,
    status ENUM('confirmed', 'in_progress', 'completed', 'rescheduled') DEFAULT 'confirmed',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    key_name VARCHAR(100) PRIMARY KEY,
    key_value TEXT
);
