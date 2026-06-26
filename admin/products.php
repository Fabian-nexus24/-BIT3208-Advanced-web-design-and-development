<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

$search = trim($_GET['search'] ?? '');

global $pdo;

if ($search !== '') {
    $stmt = $pdo->prepare(
        'SELECT p.id, p.name, p.category, p.price, p.unit, p.stock_qty, p.status, p.created_at, p.farmer_id, p.location,
                f.full_name AS farmer_name
         FROM products p
         JOIN farmers f ON p.farmer_id = f.id
         WHERE p.name LIKE ? OR p.category LIKE ? OR p.location LIKE ? OR f.full_name LIKE ?
         ORDER BY p.created_at DESC'
    );
    $stmt->execute(["%{$search}%", "%{$search}%", "%{$search}%", "%{$search}%"]);
} else {
    $stmt = $pdo->query(
        'SELECT p.id, p.name, p.category, p.price, p.unit, p.stock_qty, p.status, p.created_at, p.farmer_id, p.location,
                f.full_name AS farmer_name
         FROM products p
         JOIN farmers f ON p.farmer_id = f.id
         ORDER BY p.created_at DESC'
    );
}

$products = $stmt->fetchAll();

$pageTitle = 'Products';
dashboard_shell_start(ROLE_ADMIN, 'products', $pageTitle);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <p class="text-muted small mb-0">Monitor and manage all listed products on the marketplace.</p>
    </div>
    <form action="<?= e(url('admin/products.php')) ?>" method="GET" class="d-flex gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search name, category, farmer..." value="<?= e($search) ?>">
        <button type="submit" class="btn btn-sm btn-success">Search</button>
        <?php if ($search !== ''): ?>
            <a href="<?= e(url('admin/products.php')) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<div class="card shadow-sm border-0">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
                    <th>Farmer</th>
                    <th>Category</th>
                    <th>Price</th>
                    <th>Stock</th>
                    <th>Location</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($products)): ?>
                    <tr>
                        <td colspan="8" class="text-center text-muted py-4">No products found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($product['name']) ?></div>
                                <span class="text-muted small">ID: <?= e((string)$product['id']) ?></span>
                            </td>
                            <td>
                                <a href="<?= e(url('admin/farmer_details.php?id=' . $product['farmer_id'])) ?>" class="fw-semibold text-success text-decoration-none">
                                    <?= e($product['farmer_name']) ?>
                                </a>
                            </td>
                            <td><?= e($product['category']) ?></td>
                            <td>KES <?= e(number_format((float)$product['price'], 2)) ?> / <?= e($product['unit']) ?></td>
                            <td><?= e((string)$product['stock_qty']) ?> <?= e($product['unit']) ?></td>
                            <td class="small"><?= e($product['location'] ?? '-') ?></td>
                            <td>
                                <?php if ($product['status'] === 'active'): ?>
                                    <span class="badge bg-success-subtle text-success">Active</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <!-- Delete Product form POST with CSRF -->
                                <form action="<?= e(url('admin/product_action.php')) ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this product?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="id" value="<?= e((string)$product['id']) ?>">
                                    <input type="hidden" name="action" value="delete">
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Product">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php dashboard_shell_end(); ?>
