<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/../includes/bootstrap.php';

require_super_admin();
deny_cross_role_access();

$auth = auth_user();
$currentAdminId = (int) $auth['id'];

$errors = [];
$fullName = '';
$email = '';
$phone = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $errors['csrf'] = 'CSRF validation failed.';
    } else {
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        }
        if ($email === '') {
            $errors['email'] = 'Email is required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Invalid email address format.';
        } else {
            // Check if email already exists in admins, farmers, or customers
            global $pdo;
            $stmt = $pdo->prepare(
                'SELECT email FROM admins WHERE email = ?
                 UNION
                 SELECT email FROM farmers WHERE email = ?
                 UNION
                 SELECT email FROM customers WHERE email = ?
                 LIMIT 1'
            );
            $stmt->execute([$email, $email, $email]);
            if ($stmt->fetch() !== false) {
                $errors['email'] = 'Email is already in use across the platform.';
            }
        }

        if (strlen($password) < MIN_PASSWORD_LENGTH) {
            $errors['password'] = 'Password must be at least ' . MIN_PASSWORD_LENGTH . ' characters.';
        } elseif ($password !== $confirmPassword) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if (empty($errors)) {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            
            global $pdo;
            $stmt = $pdo->prepare(
                "INSERT INTO admins (full_name, email, password_hash, phone, role, status)
                 VALUES (?, ?, ?, ?, 'manager', 'active')"
            );
            $stmt->execute([$fullName, $email, $hash, $phone !== '' ? $phone : null]);
            $newAdminId = (int) $pdo->lastInsertId();

            // Write audit log
            audit_log($currentAdminId, 'admin_created', 'admin', $newAdminId, [
                'full_name' => $fullName,
                'email' => $email,
                'role' => 'manager'
            ]);

            flash_set('success', 'Manager account created successfully.');
            redirect('admin/admins.php');
        }
    }
}

$pageTitle = 'Add New Admin';
dashboard_shell_start(ROLE_ADMIN, 'admins', $pageTitle);
?>

<div class="mb-4">
    <a href="<?= e(url('admin/admins.php')) ?>" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i> Back to Admins
    </a>
</div>

<div class="row">
    <div class="col-lg-8 col-xl-6">
        <div class="card shadow-sm border-0">
            <div class="card-body p-4">
                <h2 class="h5 fw-bold text-success mb-3">Create Manager Account</h2>
                <p class="text-muted small">Managers can access the admin dashboard, manage farmers, customers, products, and view orders, but cannot manage other administrators.</p>

                <?php if (isset($errors['csrf'])): ?>
                    <div class="alert alert-danger"><?= e($errors['csrf']) ?></div>
                <?php endif; ?>

                <form action="<?= e(url('admin/admin_create.php')) ?>" method="POST" class="needs-validation">
                    <?= csrf_field() ?>

                    <div class="mb-3">
                        <label for="full_name" class="form-label small fw-semibold">Full Name</label>
                        <input type="text" class="form-control <?= isset($errors['full_name']) ? 'is-invalid' : '' ?>" id="full_name" name="full_name" value="<?= e($fullName) ?>" required>
                        <?php if (isset($errors['full_name'])): ?>
                            <div class="invalid-feedback"><?= e($errors['full_name']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label small fw-semibold">Email Address</label>
                        <input type="email" class="form-control <?= isset($errors['email']) ? 'is-invalid' : '' ?>" id="email" name="email" value="<?= e($email) ?>" required>
                        <?php if (isset($errors['email'])): ?>
                            <div class="invalid-feedback"><?= e($errors['email']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="phone" class="form-label small fw-semibold">Phone Number (Optional)</label>
                        <input type="text" class="form-control <?= isset($errors['phone']) ? 'is-invalid' : '' ?>" id="phone" name="phone" value="<?= e($phone) ?>">
                        <?php if (isset($errors['phone'])): ?>
                            <div class="invalid-feedback"><?= e($errors['phone']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label for="password" class="form-label small fw-semibold">Password</label>
                            <input type="password" class="form-control <?= isset($errors['password']) ? 'is-invalid' : '' ?>" id="password" name="password" required>
                            <?php if (isset($errors['password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['password']) ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-6">
                            <label for="confirm_password" class="form-label small fw-semibold">Confirm Password</label>
                            <input type="password" class="form-control <?= isset($errors['confirm_password']) ? 'is-invalid' : '' ?>" id="confirm_password" name="confirm_password" required>
                            <?php if (isset($errors['confirm_password'])): ?>
                                <div class="invalid-feedback"><?= e($errors['confirm_password']) ?></div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-success w-100">
                        <i class="bi bi-save"></i> Create Manager Account
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php dashboard_shell_end(); ?>
