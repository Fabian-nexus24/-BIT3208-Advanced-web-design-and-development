<?php
/**
 * Upgrade legacy farmconnect_db schema to match current application code.
 * Run: php tools/migrate_legacy_db.php
 */
declare(strict_types=1);

$_root = dirname(__DIR__);
if (is_file($_root . '/config/app.php')) {
    $_configDir = $_root . '/config';
} else {
    $_configDir = $_root . '/WEEK 1/config';
}

require_once $_configDir . '/app.php';
require_once dirname(__DIR__) . '/includes/env.php';
require_once $_configDir . '/db.php';

function table_exists(PDO $pdo, string $table): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?'
    );
    $stmt->execute([$table]);
    return (int) $stmt->fetchColumn() > 0;
}

function column_exists(PDO $pdo, string $table, string $column): bool
{
    $stmt = $pdo->prepare(
        'SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?'
    );
    $stmt->execute([$table, $column]);
    return (int) $stmt->fetchColumn() > 0;
}

function rename_column(PDO $pdo, string $table, string $from, string $to, string $definition): void
{
    if (column_exists($pdo, $table, $from) && !column_exists($pdo, $table, $to)) {
        $pdo->exec("ALTER TABLE `{$table}` CHANGE `{$from}` `{$to}` {$definition}");
        echo "  Renamed {$table}.{$from} -> {$to}\n";
    }
}

function add_column(PDO $pdo, string $table, string $column, string $definition): void
{
    if (!column_exists($pdo, $table, $column)) {
        $pdo->exec("ALTER TABLE `{$table}` ADD COLUMN `{$column}` {$definition}");
        echo "  Added {$table}.{$column}\n";
    }
}

echo 'FarmConnect legacy database migration' . PHP_EOL;
echo 'Database: ' . DB_NAME . PHP_EOL . PHP_EOL;

if (!table_exists($pdo, 'admins')) {
    fwrite(STDERR, "Table 'admins' not found. Import database/schema.sql first.\n");
    exit(1);
}

echo "Upgrading admins...\n";
rename_column($pdo, 'admins', 'fullname', 'full_name', "VARCHAR(150) NOT NULL DEFAULT ''");
add_column($pdo, 'admins', 'full_name', "VARCHAR(150) NOT NULL DEFAULT '' AFTER id");
rename_column($pdo, 'admins', 'password', 'password_hash', 'VARCHAR(255) NOT NULL');
add_column($pdo, 'admins', 'password_hash', "VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
add_column($pdo, 'admins', 'email', "VARCHAR(255) NULL AFTER full_name");
add_column($pdo, 'admins', 'phone', 'VARCHAR(20) NULL AFTER password_hash');
add_column($pdo, 'admins', 'role', "ENUM('super_admin', 'manager') NOT NULL DEFAULT 'manager' AFTER phone");
add_column($pdo, 'admins', 'status', "ENUM('active', 'suspended') NOT NULL DEFAULT 'active' AFTER role");
add_column($pdo, 'admins', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');

if (column_exists($pdo, 'admins', 'username')) {
    if (column_exists($pdo, 'admins', 'email')) {
        $pdo->exec(
            "UPDATE admins SET email = CONCAT(username, '@farmconnect.co.ke')
             WHERE (email IS NULL OR email = '') AND username IS NOT NULL AND username != ''"
        );
        $pdo->exec(
            "UPDATE admins SET full_name = username
             WHERE (full_name IS NULL OR full_name = '') AND username IS NOT NULL AND username != ''"
        );
        echo "  Migrated admins.username into email/full_name\n";
    }
    if (column_exists($pdo, 'admins', 'email')) {
        try {
            $pdo->exec('ALTER TABLE admins DROP COLUMN username');
            echo "  Dropped admins.username\n";
        } catch (PDOException $e) {
            echo "  Note: could not drop admins.username (may still be required by an index)\n";
        }
    }
}

$pdo->exec("UPDATE admins SET email = 'admin@farmconnect.co.ke' WHERE email IS NULL OR email = ''");
$pdo->exec("UPDATE admins SET full_name = 'System Administrator' WHERE full_name IS NULL OR full_name = ''");
$pdo->exec("UPDATE admins SET status = 'active' WHERE status IS NULL OR status = ''");

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount > 0) {
    $pdo->exec(
        "UPDATE admins SET role = 'super_admin' WHERE id = (
            SELECT id FROM (SELECT id FROM admins ORDER BY id ASC LIMIT 1) AS first_admin
        )"
    );
}

try {
    $pdo->exec('ALTER TABLE admins MODIFY email VARCHAR(255) NOT NULL');
} catch (PDOException) {
    // duplicate emails may block NOT NULL — continue
}

if (table_exists($pdo, 'farmers')) {
    echo "Upgrading farmers...\n";
    rename_column($pdo, 'farmers', 'fullname', 'full_name', "VARCHAR(150) NOT NULL DEFAULT ''");
    add_column($pdo, 'farmers', 'full_name', "VARCHAR(150) NOT NULL DEFAULT '' AFTER id");
    rename_column($pdo, 'farmers', 'password', 'password_hash', 'VARCHAR(255) NOT NULL');
    add_column($pdo, 'farmers', 'password_hash', "VARCHAR(255) NOT NULL DEFAULT '' AFTER email");
    rename_column($pdo, 'farmers', 'location', 'farming_location', 'VARCHAR(200) NULL');
    add_column($pdo, 'farmers', 'farming_location', 'VARCHAR(200) NULL');
    add_column($pdo, 'farmers', 'farm_name', 'VARCHAR(150) NULL');
    add_column($pdo, 'farmers', 'county', 'VARCHAR(100) NULL');
    add_column($pdo, 'farmers', 'profile_image', 'VARCHAR(255) NULL');
    add_column($pdo, 'farmers', 'status', "ENUM('active', 'suspended') NOT NULL DEFAULT 'active'");
    add_column($pdo, 'farmers', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
    $pdo->exec("UPDATE farmers SET status = 'active' WHERE status IS NULL OR status = ''");
}

if (table_exists($pdo, 'products')) {
    echo "Upgrading products...\n";
    rename_column($pdo, 'products', 'product_name', 'name', "VARCHAR(200) NOT NULL DEFAULT ''");
    add_column($pdo, 'products', 'name', "VARCHAR(200) NOT NULL DEFAULT ''");
    rename_column($pdo, 'products', 'price_per_kg', 'price', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00');
    add_column($pdo, 'products', 'price', 'DECIMAL(10,2) NOT NULL DEFAULT 0.00');
    rename_column($pdo, 'products', 'quantity', 'stock_qty', 'INT UNSIGNED NOT NULL DEFAULT 0');
    add_column($pdo, 'products', 'stock_qty', 'INT UNSIGNED NOT NULL DEFAULT 0');
    rename_column($pdo, 'products', 'image', 'image_path', 'VARCHAR(255) NULL');
    add_column($pdo, 'products', 'image_path', 'VARCHAR(255) NULL');
    add_column($pdo, 'products', 'category', "VARCHAR(100) NOT NULL DEFAULT 'Other'");
    add_column($pdo, 'products', 'description', 'TEXT NULL');
    add_column($pdo, 'products', 'unit', "VARCHAR(50) NOT NULL DEFAULT 'kg'");
    add_column($pdo, 'products', 'status', "ENUM('active','inactive') NOT NULL DEFAULT 'active'");
    add_column($pdo, 'products', 'updated_at', 'TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
}

if (!table_exists($pdo, 'customers')) {
    echo "Creating customers table...\n";
    $pdo->exec(
        "CREATE TABLE customers (
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
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

if (!table_exists($pdo, 'audit_logs')) {
    echo "Creating audit_logs table...\n";
    $pdo->exec(
        "CREATE TABLE audit_logs (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            actor_admin_id INT UNSIGNED NOT NULL,
            action VARCHAR(100) NOT NULL,
            target_type VARCHAR(50) NOT NULL,
            target_id INT UNSIGNED NOT NULL,
            metadata JSON NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_audit_actor (actor_admin_id),
            INDEX idx_audit_target (target_type, target_id),
            INDEX idx_audit_created (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    );
}

// Import remaining tables from upgrade file if missing
$upgradeSql = file_get_contents(dirname(__DIR__) . '/database/migrations/upgrade_farmconnect_db.sql');
if ($upgradeSql !== false) {
    $chunks = preg_split('/;\s*\n/', $upgradeSql) ?: [];
    foreach ($chunks as $chunk) {
        $chunk = trim($chunk);
        if ($chunk === '' || str_starts_with($chunk, '--') || str_starts_with($chunk, 'USE ')) {
            continue;
        }
        if (str_starts_with($chunk, 'SET @') || str_starts_with($chunk, 'SELECT ') || str_starts_with($chunk, 'SHOW ') || str_starts_with($chunk, 'DESCRIBE ')) {
            continue;
        }
        try {
            $pdo->exec($chunk);
        } catch (PDOException $e) {
            // ignore duplicate column/table errors from IF NOT EXISTS batches
        }
    }
}

echo "Normalizing table collations...\n";
foreach (['admins', 'farmers', 'customers', 'products', 'orders', 'inquiries', 'notifications', 'audit_logs'] as $table) {
    if (!table_exists($pdo, $table)) {
        continue;
    }
    try {
        $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "  Converted {$table}\n";
    } catch (PDOException) {
        // non-fatal
    }
}

echo PHP_EOL . 'Seeding super admin account...' . PHP_EOL;

$email = 'admin@farmconnect.co.ke';
$hash = password_hash('superadmin123', PASSWORD_DEFAULT);

$pdo->exec(
    'DELETE FROM admins WHERE email = ' . $pdo->quote($email) . ' AND id NOT IN (
        SELECT id FROM (SELECT MAX(id) AS id FROM admins WHERE email = ' . $pdo->quote($email) . ') AS keep_row
    )'
);

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    $insert = $pdo->prepare(
        "INSERT INTO admins (full_name, email, password_hash, role, status)
         VALUES ('System Administrator', ?, ?, 'super_admin', 'active')"
    );
    $insert->execute([$email, $hash]);
} else {
    $update = $pdo->prepare(
        "UPDATE admins SET full_name = 'System Administrator', email = ?, password_hash = ?, role = 'super_admin', status = 'active'
         WHERE id = (SELECT id FROM (SELECT MIN(id) AS id FROM admins) AS x)"
    );
    $update->execute([$email, $hash]);
}

try {
    $pdo->exec('ALTER TABLE admins ADD UNIQUE INDEX uq_admins_email (email)');
} catch (PDOException) {
    // index may already exist
}

echo "Admin seeded: {$email} / superadmin123\n";

echo PHP_EOL . 'Migration complete.' . PHP_EOL;
echo 'Login: admin@farmconnect.co.ke / superadmin123' . PHP_EOL;

$cols = $pdo->query('SHOW COLUMNS FROM admins')->fetchAll(PDO::FETCH_COLUMN);
echo 'admins columns: ' . implode(', ', $cols) . PHP_EOL;
