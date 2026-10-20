<?php
require __DIR__ . '/../src/bootstrap.php';

if (current_user()) redirect('user_dashboard.php');

$errors = [];
$old = ['name' => '', 'student_number' => '', 'email' => ''];
if (is_post()) {
    csrf_check();
    $old = array_intersect_key($_POST, $old) + $old;
    [, $errors] = user_validate($_POST);
    if (empty($_POST['terms'])) $errors[] = 'You must accept the Terms and Conditions.';
    if (!$errors) {
        [$id, $errors] = user_create($_POST);
        if ($id) {
            flash('success', 'Account created. You can log in now.');
            redirect('index.php');
        }
    }
}

render_header('Create Account', 'guest', 'signup');
?>
<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4 p-md-5">
                <h3 class="text-center mb-4"><i class="fas fa-user-plus text-primary me-2"></i>Student Registration</h3>
                <?php render_errors($errors); ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="name" class="form-label">Full name</label>
                        <input type="text" class="form-control" id="name" name="name" value="<?= e($old['name']) ?>" maxlength="120" required>
                    </div>
                    <div class="mb-3">
                        <label for="student_number" class="form-label">Student number</label>
                        <input type="text" class="form-control" id="student_number" name="student_number" value="<?= e($old['student_number']) ?>" maxlength="20" required>
                    </div>
                    <div class="mb-3">
                        <label for="email" class="form-label">Email address</label>
                        <input type="email" class="form-control" id="email" name="email" value="<?= e($old['email']) ?>" maxlength="190" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" data-strength="#strength" autocomplete="new-password" required>
                        <div class="password-strength mt-2"><div id="strength"></div></div>
                        <small class="text-muted">At least 8 characters, with letters and numbers.</small>
                    </div>
                    <div class="mb-3">
                        <label for="confirm_password" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" data-match="#password" data-match-hint="#confirmHelp" autocomplete="new-password" required>
                        <div id="confirmHelp" class="form-text"></div>
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="terms" name="terms" value="1" required>
                        <label class="form-check-label" for="terms">I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a></label>
                    </div>
                    <button type="submit" class="btn btn-primary w-100 py-2 mb-3"><i class="fas fa-user-plus me-2"></i>Register</button>
                    <p class="text-center mb-0">Already have an account? <a href="index.php" class="fw-bold">Login here</a></p>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="termsModal" tabindex="-1" aria-labelledby="termsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="termsModalLabel">Terms and Conditions</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <h6><?= e(config('app_name')) ?> - Terms of Use</h6>
                <p>By registering for an account, you agree to the following terms:</p>
                <ol>
                    <li>You must be a current student to register for an account.</li>
                    <li>Your account is for personal use only and must not be shared.</li>
                    <li>You are responsible for keeping your login details confidential.</li>
                    <li>Books must be returned by the due date. Students with overdue books can't borrow more.</li>
                    <li>Library resources are protected by copyright and are for educational use only.</li>
                    <li>Misuse of library resources may result in account suspension.</li>
                </ol>
            </div>
            <div class="modal-footer"><button type="button" class="btn btn-primary" data-bs-dismiss="modal">I Understand</button></div>
        </div>
    </div>
</div>
<?php render_footer();
