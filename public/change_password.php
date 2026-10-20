<?php
require __DIR__ . '/../src/bootstrap.php';

// Shared by students and administrators.
$admin = current_admin();
$user = $admin ? null : require_user();
$table = $admin ? 'admins' : 'users';
$id = (int) ($admin['id'] ?? $user['id']);
$area = $admin ? 'admin' : 'user';

$errors = [];
if (is_post()) {
    csrf_check();
    $errors = password_change($table, $id, (string) ($_POST['current_password'] ?? ''), (string) ($_POST['new_password'] ?? ''), (string) ($_POST['confirm_password'] ?? ''));
    if (!$errors) {
        session_regenerate_id(true);
        flash('success', 'Your password has been changed.');
        redirect($admin ? 'admin_dashboard.php' : 'user_dashboard.php');
    }
}

render_header('Change Password', $area, 'password');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center mb-4"><i class="fas fa-key text-primary me-2"></i>Change Password</h3>
                <?php if ($user && $user['must_change_password']): ?>
                    <div class="alert alert-warning">You are using a temporary password. Please choose a new one to continue.</div>
                <?php endif; ?>
                <?php render_errors($errors); ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input type="password" class="form-control" id="current_password" name="current_password" autocomplete="current-password" required>
                    </div>
                    <div class="mb-3">
                        <label for="new_password" class="form-label">New password</label>
                        <input type="password" class="form-control" id="new_password" name="new_password" data-strength="#strength" <?= $admin ? 'data-strict="1"' : '' ?> autocomplete="new-password" required>
                        <div class="password-strength mt-2"><div id="strength"></div></div>
                        <small class="text-muted"><?= $admin ? 'At least 12 characters with upper and lower case letters, a number and a symbol.' : 'At least 8 characters, with letters and numbers.' ?></small>
                    </div>
                    <div class="mb-4">
                        <label for="confirm_password" class="form-label">Confirm new password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" data-match="#new_password" data-match-hint="#confirmHelp" autocomplete="new-password" required>
                        <div id="confirmHelp" class="form-text"></div>
                    </div>
                    <button class="btn btn-primary w-100"><i class="fas fa-save me-2"></i>Update password</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
