<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../includes/bootstrap.php';

require_admin();
deny_cross_role_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('danger', 'Invalid request method.');
    redirect('admin/customers.php');
}

if (!csrf_verify()) {
    flash_set('danger', 'CSRF verification failed.');
    redirect('admin/customers.php');
}

$id = (int) ($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');

$auth = auth_user();
$currentAdminId = (int) $auth['id'];

if ($id <= 0) {
    flash_set('danger', 'Invalid customer ID.');
    redirect('admin/customers.php');
}

global $pdo;

// Fetch target customer details
$stmt = $pdo->prepare('SELECT id, full_name, email FROM customers WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$target = $stmt->fetch();

if ($target === false) {
    flash_set('danger', 'Customer not found.');
    redirect('admin/customers.php');
}

switch ($action) {
    case 'suspend':
        $update = $pdo->prepare("UPDATE customers SET status = 'suspended' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'customer_suspended', 'customer', $id, [
            'full_name' => $target['full_name'],
            'email' => $target['email']
        ]);

        flash_set('success', 'Customer account suspended.');
        break;

    case 'activate':
        $update = $pdo->prepare("UPDATE customers SET status = 'active' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'customer_activated', 'customer', $id, [
            'full_name' => $target['full_name'],
            'email' => $target['email']
        ]);

        flash_set('success', 'Customer account activated.');
        break;

    case 'delete':
        $delete = $pdo->prepare('DELETE FROM customers WHERE id = ?');
        $delete->execute([$id]);

        audit_log($currentAdminId, 'customer_deleted', 'customer', $id, [
            'full_name' => $target['full_name'],
            'email' => $target['email']
        ]);

        flash_set('success', 'Customer account permanently deleted.');
        break;

    default:
        flash_set('danger', 'Unknown action.');
}

redirect('admin/customers.php');
