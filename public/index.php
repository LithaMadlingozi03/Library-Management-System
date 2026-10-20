<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) redirect('user_dashboard.php');

$error = '';
$email = '';
if (is_post()) {
    csrf_check();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $result = attempt_login('users', 'student', $email, (string) ($_POST['password'] ?? ''));
    if (is_array($result)) {
        login_user((int) $result['id']);
        redirect('user_dashboard.php');
    }
    $error = $result;
}

render_header('Student Login', 'guest', 'login');
?>
<div class="row g-4">
    <div class="col-lg-5">
        <div class="card h-100">
            <div class="card-body p-4 text-center d-flex flex-column justify-content-center">
                <div class="library-icon"><i class="fas fa-university"></i></div>
                <h2 class="welcome-message">Welcome to Richfield's Digital Library</h2>
                <p class="welcome-text">
                    Search the catalogue, see what's available and keep track of the books you have borrowed.
                    The library is your gateway to knowledge and academic success.
                </p>
                <div class="mt-4">
                    <h5><i class="fas fa-clock text-primary me-2"></i>24/7 Online Catalogue</h5>
                    <p class="mb-0">Physical Library: Mon-Fri 8AM-6PM</p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center mb-4"><i class="fas fa-user-graduate text-primary me-2"></i>Student Login</h3>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= e($error) ?></div>
                <?php endif; ?>
                <form method="post" autocomplete="on">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="email" class="form-label"><i class="fas fa-envelope text-primary me-2"></i>Student Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($email) ?>" placeholder="student@example.com" required autofocus>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label"><i class="fas fa-lock text-primary me-2"></i>Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3"><i class="fas fa-sign-in-alt me-2"></i>Login</button>
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="signup.php" class="btn btn-outline-primary"><i class="fas fa-user-plus me-2"></i>Create Account</a>
                        <a href="forgot_password.php">Forgot password?</a>
                    </div>
                </form>
                <hr class="my-4">
                <p class="text-center mb-0">Staff access? <a href="admin_login.php" class="fw-bold">Administrator Login</a></p>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
