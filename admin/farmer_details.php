<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

$farmer_id = (int) ($_GET['id'] ?? 0);

if ($farmer_id <= 0) {
    flash_set('danger', 'Invalid farmer ID.');
    redirect('admin/farmers.php');
}

// Get farmer info
global $pdo;
$stmt = $pdo->prepare("SELECT * FROM farmers WHERE id = ? LIMIT 1");
$stmt->execute([$farmer_id]);
$farmer = $stmt->fetch();

if ($farmer === false) {
    flash_set('danger', 'Farmer not found.');
    redirect('admin/farmers.php');
}

// Get farmer's products
$stmt = $pdo->prepare("SELECT * FROM products WHERE farmer_id = ? ORDER BY created_at DESC");
$stmt->execute([$farmer_id]);
$products = $stmt->fetchAll();

// Statistics
$stmt = $pdo->prepare("SELECT COUNT(*) as total FROM products WHERE farmer_id = ?");
$stmt->execute([$farmer_id]);
$total_products = (int) $stmt->fetch()['total'];

$stmt = $pdo->prepare("SELECT SUM(stock_qty) as total FROM products WHERE farmer_id = ?");
$stmt->execute([$farmer_id]);
$total_stock = (float) ($stmt->fetch()['total'] ?? 0);

$pageTitle = 'Farmer Details';
dashboard_shell_start(ROLE_ADMIN, 'farmers', $pageTitle);
?>

<div class="mb-4 d-flex justify-content-between align-items-center">
    <a href="<?= e(url('admin/farmers.php')) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Farmers
    </a>
    <div>
        <?php if ($farmer['status'] === 'active'): ?>
            <form action="<?= e(url('admin/farmer_action.php')) ?>" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e((string)$farmer['id']) ?>">
                <input type="hidden" name="action" value="suspend">
                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to suspend this farmer?');">
                    <i class="bi bi-slash-circle"></i> Suspend Farmer
                </button>
            </form>
        <?php else: ?>
            <form action="<?= e(url('admin/farmer_action.php')) ?>" method="POST" class="d-inline">
                <?= csrf_field() ?>
                <input type="hidden" name="id" value="<?= e((string)$farmer['id']) ?>">
                <input type="hidden" name="action" value="activate">
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="bi bi-check-circle"></i> Activate Farmer
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<div class="row g-4 mb-4">
    <!-- Farmer Information -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold text-success">Farmer Profile</h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-4 gap-3">
                    <?php if (!empty($farmer['profile_image'])): ?>
                        <img src="<?= e(url(UPLOAD_PROFILE_URL . $farmer['profile_image'])) ?>" alt="<?= e($farmer['full_name']) ?>" class="rounded-circle border" style="width: 80px; height: 80px; object-fit: cover;">
                    <?php else: ?>
                        <span class="d-inline-flex align-items-center justify-content-center bg-success-subtle text-success rounded-circle border" style="width: 80px; height: 80px; font-size: 2rem;">
                            <?= e(strtoupper(substr($farmer['full_name'], 0, 1))) ?>
                        </span>
                    <?php endif; ?>
                    <div>
                        <h4 class="mb-1 fw-bold"><?= e($farmer['full_name']) ?></h4>
                        <span class="badge bg-<?= $farmer['status'] === 'active' ? 'success' : 'danger' ?>"><?= e(ucfirst($farmer['status'])) ?></span>
                    </div>
                </div>

                <table class="table table-borderless table-sm small mb-0">
                    <tr>
                        <td class="text-muted py-2" style="width: 130px;">Email:</td>
                        <td class="fw-semibold text-dark py-2"><?= e($farmer['email']) ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Phone:</td>
                        <td class="fw-semibold text-dark py-2"><?= e($farmer['phone'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Farm Name:</td>
                        <td class="fw-semibold text-dark py-2"><?= e($farmer['farm_name'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">County:</td>
                        <td class="fw-semibold text-dark py-2"><?= e($farmer['county'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Farming Location:</td>
                        <td class="fw-semibold text-dark py-2"><?= e($farmer['farming_location'] ?? '-') ?></td>
                    </tr>
                    <tr>
                        <td class="text-muted py-2">Registered:</td>
                        <td class="fw-semibold text-dark py-2"><?= e(date('d M Y, H:i', strtotime($farmer['created_at']))) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white py-3">
                <h5 class="card-title mb-0 fw-bold text-success">Sales & Stock Summary</h5>
            </div>
            <div class="card-body d-flex flex-column justify-content-around">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light text-center">
                            <h3 class="fw-bold mb-1"><?= $total_products ?></h3>
                            <span class="text-muted small">Active Products</span>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="p-3 border rounded bg-light text-center">
                            <h3 class="fw-bold mb-1"><?= number_format($total_stock) ?></h3>
                            <span class="text-muted small">Total Stock Quantity</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Products Listed -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white py-3">
        <h5 class="card-title mb-0 fw-bold text-success">Products Listed</h5>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Product</th>
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
                        <td colspan="7" class="text-center text-muted py-4">This farmer has not listed any products yet.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <?php if (!empty($product['image_path'])): ?>
                                        <img src="<?= e(url(UPLOAD_PRODUCT_URL . $product['image_path'])) ?>" alt="<?= e($product['name']) ?>" class="rounded" style="width: 45px; height: 45px; object-fit: cover;">
                                    <?php else: ?>
                                        <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 45px; height: 45px;">
                                            <i class="bi bi-basket text-muted"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div class="fw-semibold text-dark"><?= e($product['name']) ?></div>
                                </div>
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
                                    <input type="hidden" name="redirect" value="farmer_details.php?id=<?= e((string)$farmer_id) ?>">
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
