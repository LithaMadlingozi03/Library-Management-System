<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

render_header('Admin Profile', 'admin', 'profile');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <div class="profile-avatar"><?= e(strtoupper(mb_substr($admin['full_name'], 0, 1))) ?></div>
                <h3 class="text-center mb-4">Admin Profile</h3>
                <div class="row">
                    <div class="col-md-6 profile-detail"><div class="profile-detail-label">Full name</div><div class="profile-detail-value"><?= e($admin['full_name']) ?></div></div>
                    <div class="col-md-6 profile-detail"><div class="profile-detail-label">Employee ID</div><div class="profile-detail-value"><?= e($admin['employee_id']) ?></div></div>
                    <div class="col-md-6 profile-detail"><div class="profile-detail-label">Email</div><div class="profile-detail-value"><?= e($admin['email']) ?></div></div>
                    <div class="col-md-6 profile-detail"><div class="profile-detail-label">Mobile</div><div class="profile-detail-value"><?= e($admin['mobile'] ?: '—') ?></div></div>
                    <div class="col-md-6 profile-detail"><div class="profile-detail-label">Department</div><div class="profile-detail-value"><?= e($admin['department']) ?></div></div>
                    <div class="col-md-6 profile-detail"><div class="profile-detail-label">Registered</div><div class="profile-detail-value"><?= e(format_date($admin['created_at'])) ?></div></div>
                </div>
                <div class="text-center mt-4">
                    <a href="admin_edit_profile.php" class="btn btn-primary me-2"><i class="fas fa-edit me-2"></i>Edit Profile</a>
                    <a href="change_password.php" class="btn btn-outline-primary"><i class="fas fa-key me-2"></i>Change Password</a>
                </div>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
