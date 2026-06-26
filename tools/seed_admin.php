<?php
/**
 * One-time admin password seeder.
 * Run from CLI: php tools/seed_admin.php
 * Sets admin password to superadmin123 (use after import if login fails).
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

$email = 'admin@farmconnect.co.ke';
$hash = password_hash('superadmin123', PASSWORD_DEFAULT);

$pdo->exec(
    'DELETE FROM admins WHERE email = ' . $pdo->quote($email) . ' AND id NOT IN (
        SELECT id FROM (SELECT MAX(id) AS id FROM admins WHERE email = ' . $pdo->quote($email) . ') AS keep_row
    )'
);

$adminCount = (int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn();
if ($adminCount === 0) {
    $stmt = $pdo->prepare(
        "INSERT INTO admins (full_name, email, password_hash, role, status)
         VALUES ('System Administrator', ?, ?, 'super_admin', 'active')"
    );
    $stmt->execute([$email, $hash]);
} else {
    $stmt = $pdo->prepare(
        "UPDATE admins SET full_name = 'System Administrator', email = ?, password_hash = ?, role = 'super_admin', status = 'active'
         WHERE id = (SELECT id FROM (SELECT MIN(id) AS id FROM admins) AS x)"
    );
    $stmt->execute([$email, $hash]);
}

try {
    $pdo->exec('ALTER TABLE admins ADD UNIQUE INDEX uq_admins_email (email)');
} catch (PDOException) {
    // index may already exist
}

echo "Admin seeded: {$email} / superadmin123\n";
