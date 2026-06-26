<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();
deny_cross_role_access();

$stats = admin_dashboard_stats();
$orderOverview = admin_orders_overview();
$activity = admin_marketplace_activity(10);
$auth = auth_user();

$pageTitle = is_super_admin() ? 'Super Admin Dashboard' : 'Admin Dashboard';
dashboard_shell_start(ROLE_ADMIN, 'dashboard', $pageTitle);

if (is_super_admin()) {
    ?>
    <div class="card bg-warning bg-opacity-10 border-warning border-opacity-25 p-4 mb-4 animate-fade-in">
        <div class="row align-items-center">
            <div class="col">
                <p class="text-warning-emphasis small mb-1 fw-bold"><i class="bi bi-shield-fill-check"></i> Super Administrator privileges active</p>
                <h2 class="h4 fw-bold mb-1 text-dark">Welcome back, <?= e($auth['name'] ?? 'Super Admin') ?></h2>
                <p class="text-muted mb-0 small">Access full user access controls, admin hierarchy operations, and audit logs.</p>
            </div>
            <div class="col-auto d-none d-sm-block">
                <i class="bi bi-shield-lock display-4 text-warning opacity-75"></i>
            </div>
        </div>
    </div>

    <?php
    $saStats = super_admin_dashboard_stats();
    ?>
    <div class="row g-3 mb-4">
        <?= stat_card('Active Farmers', (string) $saStats['active_farmers'], 'bi-people-fill', 'success') ?>
        <?= stat_card('Suspended Farmers', (string) $saStats['suspended_farmers'], 'bi-person-x', 'danger') ?>
        <?= stat_card('Active Customers', (string) $saStats['active_customers'], 'bi-person-lines-fill', 'primary') ?>
        <?= stat_card('Suspended Customers', (string) $saStats['suspended_customers'], 'bi-person-dash', 'danger') ?>
    </div>
    <div class="row g-3 mb-4">
        <?= stat_card('Active Admins', (string) $saStats['active_managers'], 'bi-shield-check', 'info') ?>
        <?= stat_card('Suspended Admins', (string) $saStats['suspended_managers'], 'bi-shield-slash', 'danger') ?>
        <?= stat_card('Total Products', (string) $saStats['total_products'], 'bi-basket', 'warning') ?>
        <?= stat_card('Total Orders', (string) $saStats['total_orders'], 'bi-cart-check', 'secondary') ?>
    </div>
    <?php
} else {
    dashboard_welcome_banner(
        (string) ($auth['name'] ?? 'Administrator'),
        'Overview of platform users and marketplace activity.'
    );
    ?>

    <div class="row g-3 mb-4">
        <?= stat_card('Total farmers', (string) $stats['farmers'], 'bi-people', 'success') ?>
        <?= stat_card('Total customers', (string) $stats['customers'], 'bi-person-lines-fill', 'primary') ?>
        <?= stat_card('Total products', (string) $stats['products'], 'bi-basket', 'warning') ?>
        <?= stat_card('Total orders', (string) $orderOverview['total'], 'bi-cart-check', 'info') ?>
    </div>
    <?php
}
?>

<?php if ($orderOverview['total'] > 0): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0">Order statistics</h2>
    </div>
    <div class="card-body">
        <div class="row g-3 mb-3">
            <div class="col-6 col-md-3">
                <span class="badge <?= e(order_status_badge(ORDER_STATUS_PENDING)) ?> w-100 py-2">
                    Pending: <?= e((string) $orderOverview['by_status'][ORDER_STATUS_PENDING]) ?>
                </span>
            </div>
            <div class="col-6 col-md-3">
                <span class="badge <?= e(order_status_badge(ORDER_STATUS_ACCEPTED)) ?> w-100 py-2">
                    Accepted: <?= e((string) $orderOverview['by_status'][ORDER_STATUS_ACCEPTED]) ?>
                </span>
            </div>
            <div class="col-6 col-md-3">
                <span class="badge <?= e(order_status_badge(ORDER_STATUS_REJECTED)) ?> w-100 py-2">
                    Rejected: <?= e((string) $orderOverview['by_status'][ORDER_STATUS_REJECTED]) ?>
                </span>
            </div>
            <div class="col-6 col-md-3">
                <span class="badge <?= e(order_status_badge(ORDER_STATUS_DELIVERED)) ?> w-100 py-2">
                    Delivered: <?= e((string) $orderOverview['by_status'][ORDER_STATUS_DELIVERED]) ?>
                </span>
            </div>
        </div>
        <div class="row g-3 small">
            <div class="col-md-6">
                <strong>Confirmed value</strong> (accepted + delivered):
                <span class="text-success fw-semibold">KES <?= e(number_format($orderOverview['revenue_total'], 2)) ?></span>
            </div>
            <div class="col-md-6">
                <strong>Pending value</strong> (awaiting farmer):
                <span class="text-warning fw-semibold">KES <?= e(number_format($orderOverview['revenue_pending'], 2)) ?></span>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Recent users</h2>
                <span class="badge bg-light text-dark">Last 8 registrations</span>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Joined</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($stats['recent'] === []): ?>
                            <tr><td colspan="4" class="text-muted text-center py-4">No users yet.</td></tr>
                        <?php else: ?>
                            <?php foreach ($stats['recent'] as $row): ?>
                                <tr>
                                    <td><span class="badge bg-<?= $row['user_type'] === 'farmer' ? 'success' : 'primary' ?>"><?= e(ucfirst((string) $row['user_type'])) ?></span></td>
                                    <td><?= e($row['name']) ?></td>
                                    <td><?= e($row['email']) ?></td>
                                    <td><?= e(date('d M Y, H:i', strtotime((string) $row['created_at']))) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h2 class="h6 mb-0">Recent orders</h2>
                <span class="badge bg-light text-dark"><?= e((string) $orderOverview['total']) ?> total</span>
            </div>
            <div class="list-group list-group-flush">
                <?php if ($orderOverview['recent'] === []): ?>
                    <div class="list-group-item text-muted small">No orders yet.</div>
                <?php else: ?>
                    <?php foreach ($orderOverview['recent'] as $ord): ?>
                        <div class="list-group-item">
                            <div class="d-flex justify-content-between gap-2">
                                <span class="small fw-semibold"><?= e($ord['product_name']) ?></span>
                                <span class="badge <?= e(order_status_badge((string) $ord['status'])) ?>"><?= e(order_status_label((string) $ord['status'])) ?></span>
                            </div>
                            <p class="small text-muted mb-0">
                                <?= e($ord['customer_name']) ?> → <?= e($ord['farmer_name']) ?>
                            </p>
                            <p class="small mb-0">
                                KES <?= e(number_format((float) $ord['total_price'], 2)) ?>
                                · <?= e(date('d M Y', strtotime((string) $ord['created_at']))) ?>
                            </p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h2 class="h6 mb-0">Recent marketplace activity</h2>
    </div>
    <div class="list-group list-group-flush">
        <?php if ($activity === []): ?>
            <div class="list-group-item text-muted small">No activity yet.</div>
        <?php else: ?>
            <?php foreach ($activity as $item): ?>
                <div class="list-group-item">
                    <?php if (($item['kind'] ?? '') === 'order'): ?>
                        <span class="badge bg-info me-1">Order</span>
                        <strong><?= e($item['actor']) ?></strong> ordered <?= e($item['subject']) ?>
                        · KES <?= e(number_format((float) $item['total_price'], 2)) ?>
                        · <span class="badge <?= e(order_status_badge((string) $item['status'])) ?>"><?= e(order_status_label((string) $item['status'])) ?></span>
                    <?php else: ?>
                        <span class="badge bg-secondary me-1">Signup</span>
                        New <?= e($item['user_type'] ?? 'user') ?>: <strong><?= e($item['actor']) ?></strong>
                    <?php endif; ?>
                    <span class="text-muted small"> · <?= e(date('d M Y, H:i', strtotime((string) $item['created_at']))) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<?php dashboard_shell_end(); ?>
