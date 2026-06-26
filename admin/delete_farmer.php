<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    flash_set('danger', 'Invalid request method or CSRF validation failed.');
    redirect('admin/farmers.php');
}

// Map parameters to farmer_action.php
$_POST['action'] = 'delete';
require_once __DIR__ . '/farmer_action.php';
