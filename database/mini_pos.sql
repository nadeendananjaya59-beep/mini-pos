-- ============================================================
-- Mini-POS & Inventory System - Database Dump
-- Import via phpMyAdmin (Import tab) or: mysql -u root < mini_pos.sql
-- Default logins:  admin / admin123    staff / staff123
-- ============================================================

CREATE DATABASE IF NOT EXISTS mini_pos
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;
USE mini_pos;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS sale_items;
DROP TABLE IF EXISTS sales;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------
-- users: system accounts with role based access
-- ---------------------------------------------------------------
CREATE TABLE users (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name       VARCHAR(100) NOT NULL,
    username   VARCHAR(50)  NOT NULL,
    password   VARCHAR(255) NOT NULL,              -- bcrypt hash (password_hash)
    role       ENUM('admin','staff') NOT NULL DEFAULT 'staff',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_users_username (username)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- categories: product groups
-- ---------------------------------------------------------------
CREATE TABLE categories (
    id   INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_categories_name (name)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- products: inventory items
-- ---------------------------------------------------------------
CREATE TABLE products (
    id                  INT UNSIGNED NOT NULL AUTO_INCREMENT,
    category_id         INT UNSIGNED NOT NULL,
    name                VARCHAR(150) NOT NULL,
    sku                 VARCHAR(50)  NOT NULL,
    price               DECIMAL(10,2) NOT NULL,
    quantity            INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 5,
    created_at          TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    UNIQUE KEY uq_products_sku (sku),
    KEY idx_products_name (name),
    KEY idx_products_category (category_id),
    CONSTRAINT fk_products_category FOREIGN KEY (category_id)
        REFERENCES categories (id) ON DELETE RESTRICT ON UPDATE CASCADE,
    CONSTRAINT chk_products_price CHECK (price >= 0),
    CONSTRAINT chk_products_qty CHECK (quantity >= 0)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- sales: one row per completed bill
-- ---------------------------------------------------------------
CREATE TABLE sales (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    user_id    INT UNSIGNED NOT NULL,
    subtotal   DECIMAL(12,2) NOT NULL,
    discount   DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    net_total  DECIMAL(12,2) NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (id),
    KEY idx_sales_created (created_at),
    CONSTRAINT fk_sales_user FOREIGN KEY (user_id)
        REFERENCES users (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------------
-- sale_items: line items of each sale (price snapshot kept)
-- ---------------------------------------------------------------
CREATE TABLE sale_items (
    id         INT UNSIGNED NOT NULL AUTO_INCREMENT,
    sale_id    INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    qty        INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    line_total DECIMAL(12,2) NOT NULL,
    PRIMARY KEY (id),
    KEY idx_items_sale (sale_id),
    KEY idx_items_product (product_id),
    CONSTRAINT fk_items_sale FOREIGN KEY (sale_id)
        REFERENCES sales (id) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_items_product FOREIGN KEY (product_id)
        REFERENCES products (id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ===============================================================
-- SEED DATA
-- ===============================================================

-- Passwords: admin123 / staff123 (bcrypt hashes)
INSERT INTO users (id, name, username, password, role) VALUES
(1, 'System Administrator', 'admin', '$2y$10$.jKfbKqp9pgGtXDiFEgTMOG.XpOROYAIL7O1AmUaBXGJK9TX5VSn6', 'admin'),
(2, 'Cashier Staff',        'staff', '$2y$10$4En0dzA84rIR3koS4dLCCOC08KStSSE0yms3mWxPEhGxf9YfcYbaO', 'staff');

INSERT INTO categories (id, name) VALUES
(1, 'Beverages'),
(2, 'Snacks'),
(3, 'Groceries'),
(4, 'Stationery');

INSERT INTO products (id, category_id, name, sku, price, quantity, low_stock_threshold) VALUES
(1,  1, 'Mineral Water 500ml',   'BEV-001',  80.00, 120, 20),
(2,  1, 'Orange Juice 1L',       'BEV-002', 450.00,  30, 10),
(3,  1, 'Ginger Beer 400ml',     'BEV-003', 180.00,   8, 10),
(4,  2, 'Potato Chips 100g',     'SNK-001', 250.00,  45, 15),
(5,  2, 'Chocolate Bar 50g',     'SNK-002', 220.00,  60, 15),
(6,  2, 'Biscuit Pack',          'SNK-003', 150.00,   4, 12),
(7,  3, 'Basmati Rice 1kg',      'GRO-001', 520.00,  50, 10),
(8,  3, 'Sugar 1kg',             'GRO-002', 260.00,  35, 10),
(9,  3, 'Coconut Oil 750ml',     'GRO-003', 890.00,   6, 8),
(10, 4, 'Exercise Book 80pg',    'STA-001', 120.00,  90, 20),
(11, 4, 'Ball Point Pen (Blue)', 'STA-002',  40.00, 200, 50),
(12, 4, 'Pencil HB',             'STA-003',  30.00,  15, 25);

-- Sample sales (dated relative to today so the dashboard shows data)
INSERT INTO sales (id, user_id, subtotal, discount, net_total, created_at) VALUES
(1, 2, 1030.00,  30.00, 1000.00, NOW() - INTERVAL 3 DAY),
(2, 2,  790.00,   0.00,  790.00, NOW() - INTERVAL 2 DAY),
(3, 1, 1520.00, 120.00, 1400.00, NOW() - INTERVAL 1 DAY),
(4, 2,  470.00,   0.00,  470.00, NOW()),
(5, 2,  970.00,  70.00,  900.00, NOW());

INSERT INTO sale_items (sale_id, product_id, qty, unit_price, line_total) VALUES
(1, 1,  5,  80.00,  400.00),
(1, 7,  1, 520.00,  520.00),
(1, 11, 2,  40.00,   80.00),
(1, 12, 1,  30.00,   30.00),
(2, 4,  2, 250.00,  500.00),
(2, 5,  1, 220.00,  220.00),
(2, 12, 1,  30.00,   30.00),
(2, 11, 1,  40.00,   40.00),
(3, 2,  2, 450.00,  900.00),
(3, 8,  1, 260.00,  260.00),
(3, 10, 3, 120.00,  360.00),
(4, 5,  1, 220.00,  220.00),
(4, 4,  1, 250.00,  250.00),
(5, 9,  1, 890.00,  890.00),
(5, 1,  1,  80.00,   80.00);
