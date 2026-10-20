<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_user();

$q = clean_text($_GET['q'] ?? '');
$category = clean_text($_GET['category'] ?? '');
$page = page_number();
$result = books_search($q, $category, $page);
$perPage = config('per_page');
$mine = user_loans((int) $user['id'], true);
$overdue = count(array_filter($mine, fn($l) => $l['due_at'] < today()));

render_header('Catalogue', 'user', 'catalogue');
?>
<h2 class="page-title mb-4"><i class="fas fa-search me-2"></i>Hello, <?= e(explode(' ', $user['name'])[0]) ?></h2>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card h-100"><div class="stat-card">
            <div class="stat-number"><?= count($mine) ?> <small class="fs-6 text-muted">of <?= (int) config('max_loans') ?></small></div>
            <div class="stat-label">Books you currently have</div>
            <a href="my_books.php" class="btn btn-primary btn-sm mt-2">View my books</a>
        </div></div>
    </div>
    <div class="col-md-6">
        <div class="card h-100"><div class="stat-card">
            <div class="stat-number <?= $overdue ? 'text-danger' : '' ?>"><?= $overdue ?></div>
            <div class="stat-label"><?= $overdue ? 'Overdue — please return them to borrow more' : 'Overdue books' ?></div>
        </div></div>
    </div>
</div>

<div class="card mb-4">
    <div class="card-body">
        <form method="get" class="row g-2">
            <div class="col-md-6">
                <input type="search" name="q" class="form-control" placeholder="Search by title, author or ISBN" value="<?= e($q) ?>">
            </div>
            <div class="col-md-4">
                <select name="category" class="form-select" aria-label="Category">
                    <option value="">All categories</option>
                    <?php foreach (book_categories() as $c): ?>
                        <option value="<?= e($c) ?>" <?= $c === $category ? 'selected' : '' ?>><?= e($c) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button></div>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header d-flex justify-content-between"><span><i class="fas fa-book me-2"></i>Books</span><span><?= $result['total'] ?> found</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Title</th><th>Author</th><th>Category</th><th>Year</th><th>Availability</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $b): ?>
                <tr>
                    <td class="fw-semibold"><?= e($b['title']) ?></td>
                    <td><?= e($b['author']) ?></td>
                    <td><?= e($b['category']) ?></td>
                    <td><?= e($b['year'] ?? '—') ?></td>
                    <td><?= availability_badge($b) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?>
                <tr><td colspan="5" class="text-center text-muted py-5">No books match your search.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span>Showing <?= $result['rows'] ? ($page - 1) * $perPage + 1 : 0 ?>–<?= ($page - 1) * $perPage + count($result['rows']) ?> of <?= $result['total'] ?></span>
        <?= pagination($result['total'], $page, $perPage) ?>
    </div>
</div>
<p class="text-white-50 small mt-3">To borrow a book, bring it to the library desk and a librarian will issue it to you.</p>
<?php render_footer();
