<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'delete') {
        [$ok, $message] = book_delete((int) ($_POST['id'] ?? 0));
        flash($ok ? 'success' : 'danger', $message);
    }
    redirect('manage_books.php' . (!empty($_POST['return']) ? '?' . ltrim((string) $_POST['return'], '?') : ''));
}

$q = clean_text($_GET['q'] ?? '');
$category = clean_text($_GET['category'] ?? '');
$page = page_number();
$perPage = config('per_page');
$result = books_search($q, $category, $page);
$returnQuery = http_build_query(array_filter(['q' => $q, 'category' => $category, 'page' => $page > 1 ? $page : null]));

render_header('Manage Books', 'admin', 'books');
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="page-title mb-0"><i class="fas fa-book me-2"></i>Books</h2>
    <a href="add_book.php" class="btn btn-light"><i class="fas fa-plus-circle me-2"></i>Add Book</a>
</div>

<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2">
        <div class="col-md-6"><input type="search" name="q" class="form-control" placeholder="Search by title, author or ISBN" value="<?= e($q) ?>"></div>
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
</div></div>

<div class="card">
    <div class="card-header d-flex justify-content-between"><span><i class="fas fa-list me-2"></i>Library books</span><span><?= $result['total'] ?> records</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Title</th><th>Author</th><th>ISBN</th><th>Year</th><th>Category</th><th>Availability</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $b): ?>
                <tr>
                    <td class="fw-semibold"><?= e($b['title']) ?></td>
                    <td><?= e($b['author']) ?></td>
                    <td><?= e($b['isbn'] ?: '—') ?></td>
                    <td><?= e($b['year'] ?? '—') ?></td>
                    <td><?= e($b['category']) ?></td>
                    <td><?= availability_badge($b) ?></td>
                    <td class="text-end text-nowrap">
                        <?php if ((int) $b['available'] > 0): ?>
                            <a class="btn btn-sm btn-outline-success" href="issue_book.php?book_id=<?= (int) $b['id'] ?>" title="Issue this book" aria-label="Issue this book"><i class="fas fa-hand-holding"></i></a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-primary" href="add_book.php?id=<?= (int) $b['id'] ?>" title="Edit" aria-label="Edit"><i class="fas fa-edit"></i></a>
                        <form method="post" class="d-inline" data-confirm="Delete &quot;<?= e($b['title']) ?>&quot;? This can't be undone.">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $b['id'] ?>">
                            <input type="hidden" name="return" value="<?= e($returnQuery) ?>">
                            <button class="btn btn-sm btn-outline-danger" title="Delete" aria-label="Delete"><i class="fas fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?><tr><td colspan="7" class="text-center text-muted py-5">No books found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span>Showing <?= $result['rows'] ? ($page - 1) * $perPage + 1 : 0 ?>–<?= ($page - 1) * $perPage + count($result['rows']) ?> of <?= $result['total'] ?></span>
        <?= pagination($result['total'], $page, $perPage) ?>
    </div>
</div>
<?php render_footer();
