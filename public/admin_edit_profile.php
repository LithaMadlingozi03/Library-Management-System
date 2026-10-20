<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

$errors = [];
if (is_post()) {
    csrf_check();
    $errors = admin_update_profile((int) $admin['id'], $_POST);
    if (!$errors) {
        flash('success', 'Profile updated.');
        redirect('admin_view_profile.php');
    }
    $admin = array_merge($admin, array_intersect_key($_POST, array_flip(['full_name', 'mobile', 'department'])));
}

render_header('Edit Profile', 'admin', 'profile');
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header"><i class="fas fa-edit me-2"></i>Edit Profile</div>
            <div class="card-body p-4">
                <?php render_errors($errors); ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="mb-3"><label for="full_name" class="form-label">Full name</label>
                        <input class="form-control" id="full_name" name="full_name" value="<?= e($admin['full_name']) ?>" maxlength="120" required></div>
                    <div class="mb-3"><label class="form-label">Email</label>
                        <input class="form-control" value="<?= e($admin['email']) ?>" disabled></div>
                    <div class="mb-3"><label for="mobile" class="form-label">Mobile</label>
                        <input class="form-control" id="mobile" name="mobile" value="<?= e($admin['mobile']) ?>" maxlength="20"></div>
                    <div class="mb-3"><label for="department" class="form-label">Department</label>
                        <input class="form-control" id="department" name="department" value="<?= e($admin['department']) ?>" maxlength="120" required></div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="admin_view_profile.php" class="btn btn-outline-secondary">Cancel</a>
                        <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
