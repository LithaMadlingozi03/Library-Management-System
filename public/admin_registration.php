<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_admin()) redirect('admin_dashboard.php');

$errors = [];
$old = ['full_name' => '', 'email' => '', 'employee_id' => ''];
if (is_post()) {
    csrf_check();
    $old = array_intersect_key($_POST, $old) + $old;
    if (empty($_POST['terms'])) {
        $errors[] = 'You must accept the Terms and Conditions.';
    } else {
        [$id, $errors] = admin_create($_POST);
        if ($id) {
            flash('success', 'Administrator account created. You can log in now.');
            redirect('admin_login.php');
        }
    }
}

render_header('Administrator Registration', 'guest', 'admin_login');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center mb-4"><i class="fas fa-user-shield text-primary me-2"></i>Administrator Registration</h3>
                <?php if (config('admin_key') === ''): ?>
                    <div class="alert alert-warning">Administrator self-registration is switched off. The site owner can enable it by setting the <code>LIB_ADMIN_KEY</code> environment variable.</div>
                <?php endif; ?>
                <?php render_errors($errors); ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="full_name" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="full_name" name="full_name" value="<?= e($old['full_name']) ?>" maxlength="120" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" maxlength="190" required>
                    </div>
                    <div class="mb-3">
                        <label for="employee_id" class="form-label">Employee ID</label>
                        <input type="text" class="form-control" id="employee_id" name="employee_id" value="<?= e($old['employee_id']) ?>" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" data-strength="#strength" data-strict="1" autocomplete="new-password" required>
                        <div class="password-strength mt-2"><div id="strength"></div></div>
                        <small class="text-muted">At least 12 characters with upper and lower case letters, a number and a symbol.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" data-match="#password" data-match-hint="#confirmHelp" autocomplete="new-password" required>
                        <div id="confirmHelp" class="form-text"></div>
                    </div>
                    <div class="mb-3">
                        <label for="admin_key" class="form-label">Administrator registration key</label>
                        <input type="password" class="form-control" id="admin_key" name="admin_key" autocomplete="off" required>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" value="1" required>
                        <label class="form-check-label" for="terms">I will use administrator access only for library work.</label>
                    </div>
                    <button class="btn btn-primary w-100 py-2 mb-3"><i class="fas fa-user-plus me-2"></i>Register</button>
                    <p class="text-center mb-0"><a href="admin_login.php">Back to administrator login</a></p>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
