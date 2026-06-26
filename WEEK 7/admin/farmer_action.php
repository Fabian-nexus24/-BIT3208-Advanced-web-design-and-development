<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../includes/bootstrap.php';

require_admin();
deny_cross_role_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('danger', 'Invalid request method.');
    redirect('admin/farmers.php');
}

if (!csrf_verify()) {
    flash_set('danger', 'CSRF verification failed.');
    redirect('admin/farmers.php');
}

$id = (int) ($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');

$auth = auth_user();
$currentAdminId = (int) $auth['id'];

if ($id <= 0) {
    flash_set('danger', 'Invalid farmer ID.');
    redirect('admin/farmers.php');
}

global $pdo;

// Fetch target farmer details
$stmt = $pdo->prepare('SELECT id, full_name, email FROM farmers WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$target = $stmt->fetch();

if ($target === false) {
    flash_set('danger', 'Farmer not found.');
    redirect('admin/farmers.php');
}

switch ($action) {
    case 'suspend':
        $update = $pdo->prepare("UPDATE farmers SET status = 'suspended' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'farmer_suspended', 'farmer', $id, [
            'full_name' => $target['full_name'],
            'email' => $target['email']
        ]);

        flash_set('success', 'Farmer account suspended.');
        break;

    case 'activate':
        $update = $pdo->prepare("UPDATE farmers SET status = 'active' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'farmer_activated', 'farmer', $id, [
            'full_name' => $target['full_name'],
            'email' => $target['email']
        ]);

        flash_set('success', 'Farmer account activated.');
        break;

    case 'delete':
        $delete = $pdo->prepare('DELETE FROM farmers WHERE id = ?');
        $delete->execute([$id]);

        audit_log($currentAdminId, 'farmer_deleted', 'farmer', $id, [
            'full_name' => $target['full_name'],
            'email' => $target['email']
        ]);

        flash_set('success', 'Farmer account permanently deleted.');
        break;

    default:
        flash_set('danger', 'Unknown action.');
}

redirect('admin/farmers.php');
