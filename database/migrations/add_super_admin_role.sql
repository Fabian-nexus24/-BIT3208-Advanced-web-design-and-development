-- =============================================================================
-- FarmConnect Kenya — Migration: Super Admin Role
-- File: database/migrations/add_super_admin_role.sql
-- Safe to run on existing database — no data is lost.
-- Run via phpMyAdmin: select farmconnect_kenya, then import this file.
-- =============================================================================

-- Step 1: Add role column to admins (IF NOT EXISTS prevents duplicate column error)
ALTER TABLE admins
  ADD COLUMN IF NOT EXISTS role ENUM('super_admin', 'manager') NOT NULL DEFAULT 'manager';

-- Step 2: Promote the earliest-registered admin to super_admin
-- (uses subquery alias to avoid MySQL "can't update same table" restriction)
UPDATE admins
SET role = 'super_admin'
WHERE id = (
    SELECT id FROM (
        SELECT id FROM admins ORDER BY id ASC LIMIT 1
    ) AS first_admin
);

-- Step 3: Create audit_logs table
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
-- Verification queries (run manually to confirm):
-- SELECT id, full_name, email, role, status FROM admins;
-- SHOW TABLES LIKE 'audit_logs';
-- =============================================================================
