<?php
require_once dirname(__DIR__) . '/config/app.php';
require_once dirname(__DIR__) . '/includes/env.php';
require_once dirname(__DIR__) . '/config/db.php';

$email = 'admin@farmconnect.co.ke';
$hash = password_hash('superadmin123', PASSWORD_DEFAULT);

// Remove legacy duplicate admin rows, keep the newest
$pdo->exec("DELETE FROM admins WHERE email = " . $pdo->quote($email) . " AND id NOT IN (
    SELECT id FROM (SELECT MAX(id) AS id FROM admins WHERE email = " . $pdo->quote($email) . ") AS keep_row
)");

$stmt = $pdo->prepare(
    "UPDATE admins SET full_name = 'System Administrator', email = ?, password_hash = ?, role = 'super_admin', status = 'active' WHERE id = (
        SELECT id FROM (SELECT MIN(id) AS id FROM admins) AS x
    )"
);
$stmt->execute([$email, $hash]);

// If no rows, insert
if ((int) $pdo->query('SELECT COUNT(*) FROM admins')->fetchColumn() === 0) {
    $insert = $pdo->prepare(
        "INSERT INTO admins (full_name, email, password_hash, role, status) VALUES ('System Administrator', ?, ?, 'super_admin', 'active')"
    );
    $insert->execute([$email, $hash]);
}

try {
    $pdo->exec('ALTER TABLE admins ADD UNIQUE INDEX uq_admins_email (email)');
} catch (PDOException) {
    // already unique
}

echo "Fixed admin account: {$email} / superadmin123\n";
