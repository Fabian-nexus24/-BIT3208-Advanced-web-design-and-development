<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../includes/bootstrap.php';

require_admin();
deny_cross_role_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('danger', 'Invalid request method.');
    redirect('admin/products.php');
}

if (!csrf_verify()) {
    flash_set('danger', 'CSRF verification failed.');
    redirect('admin/products.php');
}

$id = (int) ($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');
$redirect = trim($_POST['redirect'] ?? '');

$auth = auth_user();
$currentAdminId = (int) $auth['id'];

if ($id <= 0) {
    flash_set('danger', 'Invalid product ID.');
    redirect('admin/products.php');
}

global $pdo;

// Fetch target product details
$stmt = $pdo->prepare('SELECT id, name, farmer_id FROM products WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$target = $stmt->fetch();

if ($target === false) {
    flash_set('danger', 'Product not found.');
    redirect('admin/products.php');
}

switch ($action) {
    case 'delete':
        $delete = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $delete->execute([$id]);

        audit_log($currentAdminId, 'product_deleted', 'product', $id, [
            'name' => $target['name'],
            'farmer_id' => $target['farmer_id']
        ]);

        flash_set('success', 'Product permanently deleted.');
        break;

    default:
        flash_set('danger', 'Unknown action.');
}

if ($redirect !== '' && (str_contains($redirect, 'farmer_details.php') || str_contains($redirect, 'products.php'))) {
    redirect('admin/' . $redirect);
} else {
    redirect('admin/products.php');
}
