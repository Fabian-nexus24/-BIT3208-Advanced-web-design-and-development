-- =============================================================================
-- FarmConnect Kenya — Full Upgrade Migration for farmconnect_db
-- Run this on an existing database that only has admins, farmers, products.
-- Safe: uses IF NOT EXISTS / ADD COLUMN IF NOT EXISTS throughout.
-- =============================================================================

-- 1. Upgrade admins table columns
ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS full_name       VARCHAR(150) NOT NULL DEFAULT '' AFTER id,
  ADD COLUMN IF NOT EXISTS email           VARCHAR(255) NOT NULL DEFAULT '' AFTER full_name,
  ADD COLUMN IF NOT EXISTS password_hash   VARCHAR(255) NOT NULL DEFAULT '' AFTER email,
  ADD COLUMN IF NOT EXISTS phone           VARCHAR(20) NULL AFTER password_hash,
  ADD COLUMN IF NOT EXISTS role            ENUM('super_admin', 'manager') NOT NULL DEFAULT 'manager' AFTER phone,
  ADD COLUMN IF NOT EXISTS status          ENUM('active', 'suspended') NOT NULL DEFAULT 'active' AFTER role,
  ADD COLUMN IF NOT EXISTS updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- Rename password -> password_hash if old schema used 'password'
-- (Only runs if 'password' column exists and 'password_hash' does not)
SET @col_exists = (
  SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'admins'
    AND COLUMN_NAME = 'password'
);
-- We'll use a safe workaround: just ensure password_hash mirrors password if needed
-- (handled by seed script — no destructive rename here)

-- Add indexes if missing
ALTER TABLE admins
  ADD INDEX IF NOT EXISTS idx_admins_status (status),
  ADD INDEX IF NOT EXISTS idx_admins_role   (role);

-- 2. Upgrade farmers table
ALTER TABLE farmers
  ADD COLUMN IF NOT EXISTS full_name         VARCHAR(150) NOT NULL DEFAULT '' AFTER id,
  ADD COLUMN IF NOT EXISTS email             VARCHAR(255) NOT NULL DEFAULT '' AFTER full_name,
  ADD COLUMN IF NOT EXISTS password_hash     VARCHAR(255) NOT NULL DEFAULT '' AFTER email,
  ADD COLUMN IF NOT EXISTS phone             VARCHAR(20)  NULL AFTER password_hash,
  ADD COLUMN IF NOT EXISTS farm_name         VARCHAR(150) NULL AFTER phone,
  ADD COLUMN IF NOT EXISTS county            VARCHAR(100) NULL AFTER farm_name,
  ADD COLUMN IF NOT EXISTS farming_location  VARCHAR(200) NULL AFTER county,
  ADD COLUMN IF NOT EXISTS profile_image     VARCHAR(255) NULL AFTER farming_location,
  ADD COLUMN IF NOT EXISTS status            ENUM('active', 'suspended') NOT NULL DEFAULT 'active' AFTER profile_image,
  ADD COLUMN IF NOT EXISTS updated_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

-- 3. Upgrade products table
ALTER TABLE products
  ADD COLUMN IF NOT EXISTS description   TEXT          NULL,
  ADD COLUMN IF NOT EXISTS price         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  ADD COLUMN IF NOT EXISTS unit          VARCHAR(50)   NOT NULL DEFAULT 'kg',
  ADD COLUMN IF NOT EXISTS image_path    VARCHAR(255)  NULL,
  ADD COLUMN IF NOT EXISTS status        ENUM('active','inactive') NOT NULL DEFAULT 'active',
  ADD COLUMN IF NOT EXISTS stock_qty     INT UNSIGNED  NOT NULL DEFAULT 0,
  ADD COLUMN IF NOT EXISTS location      VARCHAR(200)  NULL,
  ADD COLUMN IF NOT EXISTS updated_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP;

-- 4. customers table
CREATE TABLE IF NOT EXISTS customers (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(150) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20) NULL,
    delivery_address TEXT NULL,
    status ENUM('active', 'suspended') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_customers_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. inquiries table
CREATE TABLE IF NOT EXISTS inquiries (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    farmer_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NULL,
    message TEXT NOT NULL,
    status ENUM('new', 'read', 'closed') NOT NULL DEFAULT 'new',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_inquiries_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_inquiries_farmer
        FOREIGN KEY (farmer_id) REFERENCES farmers(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_inquiries_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_inquiries_customer (customer_id),
    INDEX idx_inquiries_farmer (farmer_id),
    INDEX idx_inquiries_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. orders table
CREATE TABLE IF NOT EXISTS orders (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id INT UNSIGNED NOT NULL,
    farmer_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    total_price DECIMAL(10, 2) NOT NULL,
    delivery_notes TEXT NULL,
    payment_method ENUM('cash_on_delivery', 'mpesa') NOT NULL DEFAULT 'cash_on_delivery',
    status ENUM('pending', 'accepted', 'rejected', 'delivered') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_orders_customer
        FOREIGN KEY (customer_id) REFERENCES customers(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orders_farmer
        FOREIGN KEY (farmer_id) REFERENCES farmers(id)
        ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT fk_orders_product
        FOREIGN KEY (product_id) REFERENCES products(id)
        ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_orders_customer (customer_id),
    INDEX idx_orders_farmer (farmer_id),
    INDEX idx_orders_product (product_id),
    INDEX idx_orders_status (status),
    INDEX idx_orders_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. notifications table
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_role ENUM('admin', 'farmer', 'customer') NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    link_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_notifications_user (user_role, user_id, is_read),
    INDEX idx_notifications_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. audit_logs table
CREATE TABLE IF NOT EXISTS audit_logs (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    actor_admin_id INT UNSIGNED     NOT NULL,
    action         VARCHAR(100)     NOT NULL,
    target_type    VARCHAR(50)      NOT NULL,
    target_id      INT UNSIGNED     NOT NULL,
    metadata       JSON             NULL,
    created_at     TIMESTAMP        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_actor   (actor_admin_id),
    INDEX idx_audit_target  (target_type, target_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- Verification
SELECT 'Tables after migration:' AS '';
SHOW TABLES;
SELECT 'admins columns:' AS '';
DESCRIBE admins;
-- =============================================================================
