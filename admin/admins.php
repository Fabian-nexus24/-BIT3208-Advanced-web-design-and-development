<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_super_admin();
deny_cross_role_access();

$auth = auth_user();
$currentAdminId = (int) $auth['id'];

// Get all admins
global $pdo;
$stmt = $pdo->query('SELECT id, full_name, email, phone, role, status, created_at FROM admins ORDER BY created_at ASC');
$admins = $stmt->fetchAll();

// Count super admins to prevent lockout
$superAdminsCount = 0;
foreach ($admins as $ad) {
    if ($ad['role'] === 'super_admin' && $ad['status'] === 'active') {
        $superAdminsCount++;
    }
}

$pageTitle = 'Manage Admins';
dashboard_shell_start(ROLE_ADMIN, 'admins', $pageTitle);
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <p class="text-muted small mb-0">Manage administrator accounts, roles, and status.</p>
    </div>
    <a href="<?= e(url('admin/admin_create.php')) ?>" class="btn btn-success d-flex align-items-center gap-2">
        <i class="bi bi-plus-circle"></i> Add New Admin
    </a>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($admins as $ad): ?>
                    <?php 
                        $isAdminSelf = ((int)$ad['id'] === $currentAdminId);
                    ?>
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark"><?= e($ad['full_name']) ?></div>
                            <?php if ($isAdminSelf): ?>
                                <span class="badge bg-secondary-subtle text-secondary small">You</span>
                            <?php endif; ?>
                        </td>
                        <td><?= e($ad['email']) ?></td>
                        <td><?= e($ad['phone'] ?? '-') ?></td>
                        <td>
                            <?php if ($ad['role'] === 'super_admin'): ?>
                                <span class="badge bg-warning text-dark"><i class="bi bi-shield-fill-check me-1"></i>Super Admin</span>
                            <?php else: ?>
                                <span class="badge bg-info text-dark"><i class="bi bi-shield-check me-1"></i>Admin</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($ad['status'] === 'active'): ?>
                                <span class="badge bg-success-subtle text-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger-subtle text-danger">Suspended</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-muted small"><?= e(date('d M Y, H:i', strtotime($ad['created_at']))) ?></td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if (!$isAdminSelf): ?>
                                    <!-- Role Promotion / Demotion -->
                                    <?php if ($ad['role'] === 'manager'): ?>
                                        <form action="<?= e(url('admin/admin_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$ad['id']) ?>">
                                            <input type="hidden" name="action" value="promote">
                                            <button type="submit" class="btn btn-sm btn-outline-warning" title="Promote to Super Admin">
                                                <i class="bi bi-arrow-up-circle"></i> Promote
                                            </button>
                                        </form>
                                    <?php elseif ($ad['role'] === 'super_admin' && ($superAdminsCount > 1 || $ad['status'] !== 'active')): ?>
                                        <form action="<?= e(url('admin/admin_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$ad['id']) ?>">
                                            <input type="hidden" name="action" value="demote">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary" title="Demote to Admin" onclick="return confirm('Are you sure you want to demote this Super Admin to Admin?');">
                                                <i class="bi bi-arrow-down-circle"></i> Demote
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Suspension/Activation -->
                                    <?php if ($ad['status'] === 'active'): ?>
                                        <?php 
                                            // Allow suspending if not self and (not super_admin or we have other active super_admins)
                                            $canSuspend = ($ad['role'] !== 'super_admin' || $superAdminsCount > 1);
                                        ?>
                                        <?php if ($canSuspend): ?>
                                            <form action="<?= e(url('admin/admin_action.php')) ?>" method="POST" class="d-inline">
                                                <?= csrf_field() ?>
                                                <input type="hidden" name="id" value="<?= e((string)$ad['id']) ?>">
                                                <input type="hidden" name="action" value="suspend">
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Suspend Admin" onclick="return confirm('Are you sure you want to suspend this administrator?');">
                                                    <i class="bi bi-slash-circle"></i> Suspend
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <form action="<?= e(url('admin/admin_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$ad['id']) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate Admin">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="text-muted small italic">No actions</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php dashboard_shell_end(); ?>
