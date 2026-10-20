<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();
$s = library_stats();
$recent = loans_search('', 'active', '', 1, 5)['rows'];

render_header('Admin Dashboard', 'admin', 'dashboard');
?>
<h2 class="page-title mb-4"><i class="fas fa-gauge me-2"></i>Welcome, <?= e(explode(' ', $admin['full_name'])[0]) ?></h2>

<div class="row g-4 mb-4">
    <div class="col-md-6 col-lg-3">
        <div class="card h-100"><div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-number"><?= number_format($s['users']) ?></div>
            <div class="stat-label">Active students</div>
            <a href="manage_users.php" class="btn btn-primary btn-sm mt-3">Manage students</a>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100"><div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book"></i></div>
            <div class="stat-number"><?= number_format($s['titles']) ?></div>
            <div class="stat-label">Titles (<?= number_format($s['copies']) ?> copies)</div>
            <a href="manage_books.php" class="btn btn-primary btn-sm mt-3">Manage books</a>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100"><div class="stat-card">
            <div class="stat-icon"><i class="fas fa-book-reader"></i></div>
            <div class="stat-number"><?= number_format($s['active_loans']) ?></div>
            <div class="stat-label">Currently checked out</div>
            <a href="admin_view_issued_books.php" class="btn btn-primary btn-sm mt-3">View loans</a>
        </div></div>
    </div>
    <div class="col-md-6 col-lg-3">
        <div class="card h-100"><div class="stat-card">
            <div class="stat-icon"><i class="fas fa-triangle-exclamation"></i></div>
            <div class="stat-number <?= $s['overdue_loans'] ? 'text-danger' : '' ?>"><?= number_format($s['overdue_loans']) ?></div>
            <div class="stat-label">Overdue</div>
            <a href="admin_view_issued_books.php?status=overdue" class="btn btn-primary btn-sm mt-3">See overdue</a>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-header d-flex justify-content-between"><span><i class="fas fa-clock me-2"></i>Next books due back</span>
        <a href="issue_book.php" class="btn btn-light btn-sm"><i class="fas fa-plus me-1"></i>Issue a book</a></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Book</th><th>Student</th><th>Due</th><th>Status</th></tr></thead>
            <tbody>
            <?php foreach ($recent as $l): ?>
                <tr><td><?= e($l['title']) ?></td><td><?= e($l['user_name']) ?> <small class="text-muted"><?= e($l['student_number']) ?></small></td>
                    <td><?= e(format_date($l['due_at'])) ?></td><td><?= status_badge($l) ?></td></tr>
            <?php endforeach; ?>
            <?php if (!$recent): ?><tr><td colspan="4" class="text-center text-muted py-4">No books are currently on loan.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="text-center">
    <a href="add_book.php" class="btn btn-light btn-lg"><i class="fas fa-plus-circle me-2"></i>Add New Book</a>
</div>
<?php render_footer();
