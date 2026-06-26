<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

$search = trim($_GET['search'] ?? '');

global $pdo;

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT id, full_name, email, phone, county, farming_location, status, created_at
         FROM farmers
         WHERE full_name LIKE ? OR email LIKE ? OR farm_name LIKE ? OR county LIKE ?
         ORDER BY created_at DESC'
    );
    $stmt->execute(["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
} else {
    $stmt = $pdo->query('SELECT id, full_name, email, phone, county, farming_location, status, created_at FROM farmers ORDER BY created_at DESC');
}

$farmers = $stmt->fetchAll();

$pageTitle = 'Farmers';
dashboard_shell_start(ROLE_ADMIN, 'farmers', $pageTitle);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <p class="text-muted small mb-0">View registered farmers, manage their access, or view details.</p>
    </div>
    <form action="<?= e(url('admin/farmers.php')) ?>" method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, county..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-sm btn-success">Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?= e(url('admin/farmers.php')) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                    <th>County / Location</th>
                    <th>Status</th>
                    <th>Registered At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($farmers)): ?>
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">No farmers found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($farmers as $farmer): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($farmer['full_name']) ?></div>
                            </td>
                            <td><?= e($farmer['email']) ?></td>
                            <td><?= e($farmer['phone'] ?? '-') ?></td>
                            <td>
                                <div class="small text-dark"><?= e($farmer['county'] ?? '-') ?></div>
                                <div class="small text-muted"><?= e($farmer['farming_location'] ?? '-') ?></div>
                            </td>
                            <td>
                                <?php if ($farmer['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-danger-subtle text-danger">Suspended</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= e(date('d M Y, H:i', strtotime($farmer['created_at']))) ?></td>
                            <td class="text-end">
                                <div class="d-flex justify-content-end gap-2">
                                    <!-- View Details -->
                                    <a href="<?= e(url('admin/farmer_details.php?id=' . $farmer['id'])) ?>" class="btn btn-sm btn-outline-info" title="View details">
                                        <i class="bi bi-eye"></i> View
                                    </a>

                                    <!-- Suspend/Activate -->
                                    <?php if ($farmer['status'] === 'active'): ?>
                                        <form action="<?= e(url('admin/farmer_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$farmer['id']) ?>">
                                            <input type="hidden" name="action" value="suspend">
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Suspend Farmer" onclick="return confirm('Are you sure you want to suspend this farmer?');">
                                                <i class="bi bi-slash-circle"></i> Suspend
                                            </button>
                                        </form>
                                    <?php else: ?>
                                        <form action="<?= e(url('admin/farmer_action.php')) ?>" method="POST" class="d-inline">
                                            <?= csrf_field() ?>
                                            <input type="hidden" name="id" value="<?= e((string)$farmer['id']) ?>">
                                            <input type="hidden" name="action" value="activate">
                                            <button type="submit" class="btn btn-sm btn-outline-success" title="Activate Farmer">
                                                <i class="bi bi-check-circle"></i> Activate
                                            </button>
                                        </form>
                                    <?php endif; ?>

                                    <!-- Delete Farmer -->
                                    <form action="<?= e(url('admin/farmer_action.php')) ?>" method="POST" class="d-inline">
                                        <?= csrf_field() ?>
                                        <input type="hidden" name="id" value="<?= e((string)$farmer['id']) ?>">
                                        <input type="hidden" name="action" value="delete">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Farmer" onclick="return confirm('Are you sure you want to permanently delete this farmer? This will also delete all their products. This action cannot be undone.');">
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
