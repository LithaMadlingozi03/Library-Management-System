<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

$bookId = (int) ($_GET['book_id'] ?? $_POST['book_id'] ?? 0);
$book = $bookId ? book_get($bookId) : null;
if (!$book) {
    flash('warning', 'Choose a book to issue from the list.');
    redirect('manage_books.php');
}

$errors = [];
$lookup = '';
if (is_post()) {
    csrf_check();
    $lookup = clean_text($_POST['student'] ?? '');
    $student = $lookup === '' ? null : db_one('SELECT id FROM users WHERE student_number = ? OR email = ?', [strtoupper($lookup), strtolower($lookup)]);
    if (!$student) {
        $errors[] = 'No student found with that student number or email.';
    } else {
        [$ok, $message] = loan_issue($bookId, (int) $student['id'], (int) $admin['id']);
        if ($ok) {
            flash('success', $message);
            redirect('manage_books.php');
        }
        $errors[] = $message;
    }
    $book = book_get($bookId);
}

render_header('Issue Book', 'admin', 'books');
?>
<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-header"><i class="fas fa-hand-holding me-2"></i>Issue a book</div>
            <div class="card-body p-4">
                <div class="mb-3">
                    <div class="profile-detail-label">Book</div>
                    <div class="profile-detail-value"><?= e($book['title']) ?> <small class="text-muted">by <?= e($book['author']) ?></small></div>
                    <div class="mt-1"><?= availability_badge($book) ?></div>
                </div>
                <?php render_errors($errors); ?>
                <form method="post">
                    <?= csrf_field() ?>
                    <input type="hidden" name="book_id" value="<?= (int) $book['id'] ?>">
                    <div class="mb-3">
                        <label for="student" class="form-label">Student number or email</label>
                        <input type="text" class="form-control" id="student" name="student" value="<?= e($lookup) ?>" required autofocus>
                        <div class="form-text">Due date will be <?= e(format_date(date('Y-m-d', strtotime('+' . config('loan_days') . ' days')))) ?> (<?= (int) config('loan_days') ?> days). Maximum <?= (int) config('max_loans') ?> books per student.</div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="manage_books.php" class="btn btn-outline-secondary">Cancel</a>
                        <button class="btn btn-primary"><i class="fas fa-check me-2"></i>Issue book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
