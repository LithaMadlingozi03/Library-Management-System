<?php
require __DIR__ . '/../src/bootstrap.php';

render_header('Forgot Password', 'guest', 'login');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body p-4 p-md-5 text-center">
                <div class="library-icon"><i class="fas fa-key"></i></div>
                <h3 class="mb-3">Forgot your password?</h3>
                <p class="welcome-text">
                    Passwords can't be emailed from this system. Please visit the library desk (Mon-Fri 8AM-6PM)
                    with your student card and a librarian will give you a temporary password.
                    You will be asked to choose a new one when you log in.
                </p>
                <a href="index.php" class="btn btn-primary mt-2"><i class="fas fa-arrow-left me-2"></i>Back to login</a>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
