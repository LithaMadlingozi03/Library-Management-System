<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_user();

$errors = [];
if (is_post()) {
    csrf_check();
    $errors = user_update_profile((int) $user['id'], $_POST);
    if (!$errors) {
        flash('success', 'Profile updated.');
        redirect('profile.php');
    }
    $user = array_merge($user, array_intersect_key($_POST, array_flip(['name', 'phone', 'program'])));
}

render_header('My Profile', 'user', 'profile');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="profile-avatar"><?= e(strtoupper(mb_substr($user['name'], 0, 1))) ?></div>
                <h3 class="text-center mb-4">My Profile</h3>
                <?php render_errors($errors); ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <div class="row">
                        <div class="col-md-6 profile-detail">
                            <div class="profile-detail-label">Student number</div>
                            <div class="profile-detail-value"><?= e($user['student_number']) ?></div>
                        </div>
                        <div class="col-md-6 profile-detail">
                            <div class="profile-detail-label">Email</div>
                            <div class="profile-detail-value"><?= e($user['email']) ?></div>
                        </div>
                        <div class="col-md-6 profile-detail">
                            <div class="profile-detail-label">Member since</div>
                            <div class="profile-detail-value"><?= e(format_date($user['created_at'])) ?></div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($user['name']) ?>" maxlength="120" required>
                    </div>
                    <div class="mb-3">
                        <label for="phone" class="form-label">Phone</label>
                        <input type="tel" class="form-control" id="phone" name="phone" value="<?= e($user['phone']) ?>" maxlength="20">
                    </div>
                    <div class="mb-3">
                        <label for="program" class="form-label">Academic program</label>
                        <input type="text" class="form-control" id="program" name="program" value="<?= e($user['program']) ?>" maxlength="120" placeholder="e.g. BSc Information Technology">
                    </div>
                    <div class="d-flex justify-content-between">
                        <a href="change_password.php" class="btn btn-outline-primary"><i class="fas fa-key me-2"></i>Change password</a>
                        <button class="btn btn-primary"><i class="fas fa-save me-2"></i>Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
