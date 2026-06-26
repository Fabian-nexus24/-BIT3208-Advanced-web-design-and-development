<?php
/**
 * Normalize all table collations to utf8mb4_unicode_ci.
 * Run: php tools/fix_collations.php
 */
declare(strict_types=1);

$_root = dirname(__DIR__);
$_configDir = is_file($_root . '/config/app.php') ? $_root . '/config' : $_root . '/WEEK 1/config';
require_once $_configDir . '/app.php';
require_once dirname(__DIR__) . '/includes/env.php';
require_once $_configDir . '/db.php';

$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    try {
        $pdo->exec("ALTER TABLE `{$table}` CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "Converted: {$table}\n";
    } catch (PDOException $e) {
        echo "Skipped {$table}: {$e->getMessage()}\n";
    }
}
echo "Done.\n";
