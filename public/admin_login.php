<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_admin()) redirect('admin_dashboard.php');

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $result = attempt_login('admins', 'admin', $email, (string) ($_POST['password'] ?? ''));
    if (is_array($result)) {
        login_admin((int) $result['id']);
        redirect('admin_dashboard.php');
    }
    $error = $result;
}

render_header('Administrator Login', 'guest', 'admin_login');
?>
<div class="row justify-content-center">
    <div class="col-lg-5">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center mb-4"><i class="fas fa-user-shield text-primary me-2"></i>Administrator Login</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label"><i class="fas fa-envelope text-primary me-2"></i>Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="fas fa-lock text-primary me-2"></i>Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button class="btn btn-primary w-100 py-2 mb-3"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
                    <div class="d-flex justify-content-between">
                        <a href="admin_registration.php">Register as admin</a>
                        <a href="index.php">Student login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
