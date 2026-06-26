<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

$search = trim($_GET['search'] ?? '');
$statusFilter = trim($_GET['status'] ?? '');

$validStatuses = [
    ORDER_STATUS_PENDING,
    ORDER_STATUS_ACCEPTED,
    ORDER_STATUS_REJECTED,
    ORDER_STATUS_DELIVERED,
];

$orders = [];
$total = 0;
$meta = pagination_meta(0, 1, PER_PAGE_ORDERS);
$overview = admin_orders_overview();

if (orders_table_exists()) {
    global $pdo;

    $where = ['1=1'];
    $params = [];

    if ($search !== '') {
        $where[] = '(c.full_name LIKE ? OR f.full_name LIKE ? OR p.name LIKE ? OR c.email LIKE ?)';
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like, $like]);
    }

    if ($statusFilter !== '' && in_array($statusFilter, $validStatuses, true)) {
        $where[] = 'o.status = ?';
        $params[] = $statusFilter;
    }

    $whereSql = implode(' AND ', $where);

    $countStmt = $pdo->prepare(
        "SELECT COUNT(*)
         FROM orders o
         INNER JOIN customers c ON o.customer_id = c.id
         INNER JOIN farmers f ON o.farmer_id = f.id
         INNER JOIN products p ON o.product_id = p.id
         WHERE {$whereSql}"
    );
    $countStmt->execute($params);
    $total = (int) $countStmt->fetchColumn();

    $page = pagination_page_from_request();
    $meta = pagination_meta($total, $page, PER_PAGE_ORDERS);

    $stmt = $pdo->prepare(
        "SELECT o.id, o.quantity, o.total_price, o.status, o.payment_method, o.delivery_notes, o.created_at,
                c.full_name AS customer_name, c.email AS customer_email,
                f.full_name AS farmer_name,
                p.name AS product_name, p.unit
         FROM orders o
         INNER JOIN customers c ON o.customer_id = c.id
         INNER JOIN farmers f ON o.farmer_id = f.id
         INNER JOIN products p ON o.product_id = p.id
         WHERE {$whereSql}
         ORDER BY o.created_at DESC
         LIMIT {$meta['per_page']} OFFSET {$meta['offset']}"
    );
    $stmt->execute($params);
    $orders = $stmt->fetchAll();
}

$pageTitle = 'Orders';
dashboard_shell_start(ROLE_ADMIN, 'orders', $pageTitle);
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <p class="text-muted small mb-0">View all platform orders. Farmers handle accept/reject/deliver actions from their dashboard.</p>
    </div>
    <form action="<?= e(url('admin/orders.php')) ?>" method="GET" class="d-flex flex-wrap gap-2">
        <input type="text" name="search" class="form-control form-control-sm" placeholder="Search customer, farmer, product..." value="<?= e($search) ?>">
        <select name="status" class="form-select form-select-sm" style="width: auto;">
            <option value="">All statuses</option>
            <?php foreach ($validStatuses as $status): ?>
                <option value="<?= e($status) ?>" <?= $statusFilter === $status ? 'selected' : '' ?>>
                    <?= e(order_status_label($status)) ?>
                </option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn btn-sm btn-success">Filter</button>
        <?php if ($search !== '' || $statusFilter !== ''): ?>
            <a href="<?= e(url('admin/orders.php')) ?>" class="btn btn-sm btn-outline-secondary">Clear</a>
        <?php endif; ?>
    </form>
</div>

<?php if ($overview['total'] > 0): ?>
<div class="d-flex flex-wrap gap-2 mb-4">
    <span class="badge <?= e(order_status_badge(ORDER_STATUS_PENDING)) ?>">
        Pending <?= e((string) $overview['by_status'][ORDER_STATUS_PENDING]) ?>
    </span>
    <span class="badge <?= e(order_status_badge(ORDER_STATUS_ACCEPTED)) ?>">
        Accepted <?= e((string) $overview['by_status'][ORDER_STATUS_ACCEPTED]) ?>
    </span>
    <span class="badge <?= e(order_status_badge(ORDER_STATUS_REJECTED)) ?>">
        Rejected <?= e((string) $overview['by_status'][ORDER_STATUS_REJECTED]) ?>
    </span>
    <span class="badge <?= e(order_status_badge(ORDER_STATUS_DELIVERED)) ?>">
        Delivered <?= e((string) $overview['by_status'][ORDER_STATUS_DELIVERED]) ?>
    </span>
</div>
<?php endif; ?>

<div class="card shadow-sm border-0">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h2 class="h6 mb-0">All orders</h2>
        <span class="text-muted small"><?= e((string) $total) ?> matching · page <?= e((string) $meta['page']) ?> of <?= e((string) max(1, $meta['total_pages'])) ?></span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Order</th>
                    <th>Product</th>
                    <th>Customer</th>
                    <th>Farmer</th>
                    <th>Qty</th>
                    <th>Total</th>
                    <th>Payment</th>
                    <th>Status</th>
                    <th>Placed</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($orders === []): ?>
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">No orders found.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $order): ?>
                        <tr>
                            <td class="fw-semibold">#<?= e((string) $order['id']) ?></td>
                            <td>
                                <div class="fw-semibold text-dark"><?= e($order['product_name']) ?></div>
                            </td>
                            <td>
                                <div class="small fw-semibold"><?= e($order['customer_name']) ?></div>
                                <div class="small text-muted"><?= e($order['customer_email']) ?></div>
                            </td>
                            <td class="small"><?= e($order['farmer_name']) ?></td>
                            <td><?= e((string) $order['quantity']) ?> <?= e($order['unit']) ?></td>
                            <td class="fw-semibold">KES <?= e(number_format((float) $order['total_price'], 2)) ?></td>
                            <td class="small"><?= e(order_payment_label((string) $order['payment_method'])) ?></td>
                            <td>
                                <span class="badge <?= e(order_status_badge((string) $order['status'])) ?>">
                                    <?= e(order_status_label((string) $order['status'])) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= e(date('d M Y, H:i', strtotime((string) $order['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <?php if ($meta['total_pages'] > 1): ?>
        <div class="card-footer bg-white">
            <?php render_pagination($total, $meta['page'], PER_PAGE_ORDERS, 'admin/orders.php', array_filter([
                'search' => $search,
                'status' => $statusFilter,
            ])); ?>
        </div>
    <?php endif; ?>
</div>

<?php dashboard_shell_end(); ?>
