<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

$search = trim($_GET['search'] ?? '');

global $pdo;

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, phone, status, created_at
         FROM customers
         WHERE full_name LIKE ? OR email LIKE ?
         ORDER BY created_at DESC'
    );
    $stmt->execute(["%{$search}%", "%{$search}%"]);
} else {
    $stmt = $pdo->query('SELECT id, full_name, email, phone, status, created_at FROM customers ORDER BY created_at DESC');
}

$customers = $stmt->fetchAll();

$pageTitle = 'Customers';
dashboard_shell_start(ROLE_ADMIN, 'customers', $pageTitle);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <p class="text-muted small mb-0">View registered customers, toggle their access, or remove them.</p>
    </div>
    <form action="<?= e(url('admin/customers.php')) ?>" method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name or email..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-sm btn-success">Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?= e(url('admin/customers.php')) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Phone</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($customers)): ?>
                    <tr>
                        <td colspan="6" class="text-center text-muted py-4">No customers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($customers as $cust): ?>
                        <tr>
                            <td><div class="fw-semibold text-dark"><?= e($cust['full_name']) ?></div></td>
                            <td><?= e($cust['email']) ?></td>
                            <td><?= e($cust['phone'] ?? '-') ?></td>
                            <td>
                                <?php if ($cust['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= e(date('d M Y, H:i', strtotime($cust['created_at']))) ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- Suspend/Activate -->
                                    <?php if ($cust['status'] === 'active'): ?>
                                        <form action="<?= e(url('admin/customer_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$cust['id']) ?>">
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Suspend Customer" onclick="return confirm('Are you sure you want to suspend this customer?');">
                                                <i class="bi bi-slash-circle"></i> Suspend
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= e(url('admin/customer_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$cust['id']) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate Customer">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Delete Customer -->
                                    <form action="<?= e(url('admin/customer_action.php')) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string)$cust['id']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Customer" onclick="return confirm('Are you sure you want to permanently delete this customer? This action cannot be undone.');">
                                            <i class="bi bi-trash"></i> Delete
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php dashboard_shell_end(); ?>
