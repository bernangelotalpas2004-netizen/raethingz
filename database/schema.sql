-- ============================================================
-- Raethingz Handicrafts — Database Schema v2
-- Run once to create the full database with sample data.
-- ============================================================

CREATE DATABASE IF NOT EXISTS raethingz
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE raethingz;

-- ── 1. USERS TABLE ──────────────────────────────────────────
-- WHY: Renamed from "customers" to support multiple roles
--      (customer, admin). Password is hashed with bcrypt.
-- SECURITY: Never store plain-text passwords. Use password_hash()
--           in PHP (see register.php).
CREATE TABLE IF NOT EXISTS users (
    user_id     INT          PRIMARY KEY AUTO_INCREMENT,
    full_name   VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password    VARCHAR(255) NOT NULL,          -- bcrypt hash
    phone       VARCHAR(20)  DEFAULT NULL,
    address     TEXT         DEFAULT NULL,
    role        ENUM('customer','admin') NOT NULL DEFAULT 'customer',
    is_active   TINYINT(1)   NOT NULL DEFAULT 1,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    INDEX idx_email (email),
    INDEX idx_role  (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 2. CATEGORIES TABLE ─────────────────────────────────────
CREATE TABLE IF NOT EXISTS categories (
    category_id   INT          PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(80)  NOT NULL UNIQUE,
    slug          VARCHAR(80)  NOT NULL UNIQUE,    -- URL-friendly name
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 3. PRODUCTS TABLE ───────────────────────────────────────
-- WHY: Added category_id FK, image path, badge, and is_active
--      so products can be hidden without deleting.
CREATE TABLE IF NOT EXISTS products (
    product_id    INT            PRIMARY KEY AUTO_INCREMENT,
    category_id   INT            NOT NULL,
    product_name  VARCHAR(150)   NOT NULL,
    description   TEXT           DEFAULT NULL,
    price         DECIMAL(10,2)  NOT NULL,
    image_path    VARCHAR(255)   DEFAULT NULL,     -- relative path, e.g. assets/images/item1.jpg
    badge         VARCHAR(30)    DEFAULT NULL,     -- e.g. "Bestseller", "New", "Limited"
    is_active     TINYINT(1)     NOT NULL DEFAULT 1,
    created_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at    TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_product_category
        FOREIGN KEY (category_id) REFERENCES categories(category_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_category (category_id),
    INDEX idx_active   (is_active)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 4. STOCK TABLE ──────────────────────────────────────────
CREATE TABLE IF NOT EXISTS stock (
    stock_id      INT       PRIMARY KEY AUTO_INCREMENT,
    product_id    INT       NOT NULL UNIQUE,       -- One stock row per product
    quantity      INT       NOT NULL DEFAULT 0,
    last_updated  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_stock_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 5. ORDERS TABLE ─────────────────────────────────────────
CREATE TABLE IF NOT EXISTS orders (
    order_id       INT            PRIMARY KEY AUTO_INCREMENT,
    user_id        INT            DEFAULT NULL,    -- NULL = guest checkout
    customer_name  VARCHAR(100)   NOT NULL,
    address        TEXT           NOT NULL,
    contact        VARCHAR(20)    NOT NULL,
    total_amount   DECIMAL(10,2)  NOT NULL,
    status         ENUM('pending','confirmed','crafting','shipped','delivered','cancelled')
                   NOT NULL DEFAULT 'pending',
    notes          TEXT           DEFAULT NULL,
    created_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_order_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_status     (status),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 6. ORDER ITEMS TABLE ────────────────────────────────────
-- WHY: Bridge table between orders and products.
--      Stores price_at_time so historical order totals are accurate
--      even if product prices change later.
CREATE TABLE IF NOT EXISTS order_items (
    item_id        INT           PRIMARY KEY AUTO_INCREMENT,
    order_id       INT           NOT NULL,
    product_id     INT           NOT NULL,
    quantity       INT           NOT NULL DEFAULT 1,
    price_at_time  DECIMAL(10,2) NOT NULL,   -- snapshot of price when ordered

    CONSTRAINT fk_item_order
        FOREIGN KEY (order_id) REFERENCES orders(order_id)
        ON UPDATE CASCADE ON DELETE CASCADE,

    CONSTRAINT fk_item_product
        FOREIGN KEY (product_id) REFERENCES products(product_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,

    INDEX idx_order   (order_id),
    INDEX idx_product (product_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ── 7. CUSTOM REQUESTS TABLE ────────────────────────────────
CREATE TABLE IF NOT EXISTS custom_requests (
    request_id   INT          PRIMARY KEY AUTO_INCREMENT,
    user_id      INT          DEFAULT NULL,
    name         VARCHAR(100) NOT NULL,
    description  TEXT         NOT NULL,
    colors       VARCHAR(255) NOT NULL,
    materials    VARCHAR(100) DEFAULT NULL,
    contact      VARCHAR(20)  DEFAULT NULL,
    image_path   VARCHAR(255) DEFAULT NULL,
    status       ENUM('pending','reviewing','quoted','accepted','rejected')
                 NOT NULL DEFAULT 'pending',
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_request_user
        FOREIGN KEY (user_id) REFERENCES users(user_id)
        ON UPDATE CASCADE ON DELETE SET NULL,

    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ═══════════════════════════════════════════════════════════
--  SAMPLE DATA
-- ═══════════════════════════════════════════════════════════

-- Categories
INSERT INTO categories (category_name, slug) VALUES
  ('Flower',    'flower'),
  ('Bouquet',   'bouquet'),
  ('Keychain',  'keychain'),
  ('Hairclip',  'hairclip');

-- Products (image paths match the assets/images/ folder)
INSERT INTO products (category_id, product_name, description, price, image_path, badge) VALUES
  (2, 'Snickers Bouquet',   'A handmade bouquet with Snickers chocolates as flowers.',                              450.00, 'assets/images/raethingz_item20.jpg', 'Bestseller'),
  (1, 'Roses',              'Handmade roses — available as single stem or bouquet.',                               350.00, 'assets/images/raethingz_item17.jpg', NULL),
  (1, 'Tulips',             'Handmade tulips — available as single stem or bouquet.',                              350.00, 'assets/images/raethingz_item3.jpg',  'New'),
  (2, 'Sneakers Bouquet',   'A bouquet with shoes on top. (Shoes to be provided by buyer.)',                      1900.00, 'assets/images/raethingz_item25.png', NULL),
  (3, 'Carrot Keychain',    'A cute carrot-shaped keychain.',                                                       50.00, 'assets/images/raethingz_item22.jpg', NULL),
  (3, 'Sushi Keychain',     'A tiny sushi-shaped keychain.',                                                        50.00, 'assets/images/raethingz_item23.jpg', NULL),
  (4, 'Flower Hairclip',    'A hairclip with a handcrafted fabric flower.',                                         30.00, 'assets/images/raethingz_item26.jpg', 'Limited'),
  (2, 'Plushie Bouquet',    'A bouquet built around your plushie. (Plushie to be provided by buyer.)',             600.00, 'assets/images/raethingz_item24.png', NULL),
  (3, 'Doughnut Keychain',  'A cute minimalist doughnut-shaped keychain.',                                          50.00, 'assets/images/raethingz_item11.jpg', 'Bestseller'),
  (3, 'Lily Keychain',      'A delicate lily-flower keychain.',                                                     50.00, 'assets/images/raethingz_item7.jpg',  NULL),
  (1, 'Peach-Purple Roses', 'A mix of peach and purple roses for elegance and beauty.',                            350.00, 'assets/images/raethingz_item10.jpg', NULL);

-- Stock (all products start with 20 units)
INSERT INTO stock (product_id, quantity)
  SELECT product_id, 20 FROM products;

-- Admin user  (password: Admin@1234  — change immediately on production!)
INSERT INTO users (full_name, email, password, role)
  VALUES ('Admin', 'admin@raethingz.com',
          '$2y$12$5f5UHl8V2VX5JtqEe0CZluFJA5QXrjFzQQq3NUCQvQQxrBaGFpfVi',
          'admin');
-- NOTE: The hash above is for "Admin@1234". Generate your own with:
--       echo password_hash('YourNewPassword', PASSWORD_BCRYPT, ['cost'=>12]);
