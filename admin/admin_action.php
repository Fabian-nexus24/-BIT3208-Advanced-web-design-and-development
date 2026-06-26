<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_super_admin();
deny_cross_role_access();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    flash_set('danger', 'Invalid request method.');
    redirect('admin/admins.php');
}

if (!csrf_verify()) {
    flash_set('danger', 'CSRF verification failed.');
    redirect('admin/admins.php');
}

$id = (int) ($_POST['id'] ?? 0);
$action = trim($_POST['action'] ?? '');

$auth = auth_user();
$currentAdminId = (int) $auth['id'];

if ($id <= 0) {
    flash_set('danger', 'Invalid administrator ID.');
    redirect('admin/admins.php');
}

if ($id === $currentAdminId) {
    flash_set('danger', 'You cannot perform actions on your own account.');
    redirect('admin/admins.php');
}

global $pdo;

// Fetch target admin details
$stmt = $pdo->prepare('SELECT id, full_name, role, status FROM admins WHERE id = ? LIMIT 1');
$stmt->execute([$id]);
$target = $stmt->fetch();

if ($target === false) {
    flash_set('danger', 'Administrator not found.');
    redirect('admin/admins.php');
}

// Count active super admins to prevent lockout
$superAdminsCount = (int) $pdo->query("SELECT COUNT(*) FROM admins WHERE role = 'super_admin' AND status = 'active'")->fetchColumn();

switch ($action) {
    case 'suspend':
        if ($target['role'] === 'super_admin' && $superAdminsCount <= 1) {
            flash_set('danger', 'Cannot suspend the last active Super Admin.');
            break;
        }

        $update = $pdo->prepare("UPDATE admins SET status = 'suspended' WHERE id = ?");
        $update->execute([$id]);
        
        audit_log($currentAdminId, 'admin_suspended', 'admin', $id, [
            'full_name' => $target['full_name'],
            'role' => $target['role']
        ]);
        
        flash_set('success', 'Administrator account suspended.');
        break;

    case 'activate':
        $update = $pdo->prepare("UPDATE admins SET status = 'active' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'admin_activated', 'admin', $id, [
            'full_name' => $target['full_name'],
            'role' => $target['role']
        ]);

        flash_set('success', 'Administrator account activated.');
        break;

    case 'promote':
        if ($target['role'] === 'super_admin') {
            flash_set('warning', 'Administrator is already a Super Admin.');
            break;
        }

        $update = $pdo->prepare("UPDATE admins SET role = 'super_admin' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'admin_promoted', 'admin', $id, [
            'full_name' => $target['full_name']
        ]);

        flash_set('success', 'Administrator promoted to Super Admin.');
        break;

    case 'demote':
        if ($target['role'] !== 'super_admin') {
            flash_set('warning', 'Administrator is not a Super Admin.');
            break;
        }

        if ($superAdminsCount <= 1 && $target['status'] === 'active') {
            flash_set('danger', 'Cannot demote the last active Super Admin.');
            break;
        }

        $update = $pdo->prepare("UPDATE admins SET role = 'manager' WHERE id = ?");
        $update->execute([$id]);

        audit_log($currentAdminId, 'admin_demoted', 'admin', $id, [
            'full_name' => $target['full_name']
        ]);

        flash_set('success', 'Administrator demoted to Admin.');
        break;

    default:
        flash_set('danger', 'Unknown action.');
}

redirect('admin/admins.php');
