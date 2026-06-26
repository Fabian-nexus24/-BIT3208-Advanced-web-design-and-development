<?php
declare(strict_types=1);

// Auto-detect config location: root config/ (Laragon) or WEEK 1/config/ (GitHub repo)
$_root = dirname(__DIR__);
if (is_file($_root . '/config/app.php')) {
    $_configDir = $_root . '/config';
} else {
    $_configDir = $_root . '/WEEK 1/config';
}

require_once $_configDir . '/app.php';
require_once __DIR__ . '/env.php';
require_once $_configDir . '/db.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/pagination.php';
require_once __DIR__ . '/flash.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/audit.php';
require_once __DIR__ . '/middleware.php';
require_once __DIR__ . '/users.php';
require_once __DIR__ . '/upload.php';
require_once __DIR__ . '/dashboard_shell.php';
require_once __DIR__ . '/product_repository.php';
require_once __DIR__ . '/product_card.php';
require_once __DIR__ . '/inquiry_repository.php';
require_once __DIR__ . '/order_repository.php';
require_once __DIR__ . '/notification_repository.php';
require_once __DIR__ . '/analytics.php';
require_once __DIR__ . '/brand.php';
require_once __DIR__ . '/empty_state.php';
