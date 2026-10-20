<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'return') {
        [$ok, $message] = loan_return((int) ($_POST['id'] ?? 0));
        flash($ok ? 'success' : 'danger', $message);
    } elseif (($_POST['action'] ?? '') === 'renew') {
        [$ok, $message] = loan_renew((int) ($_POST['id'] ?? 0));
        flash($ok ? 'success' : 'danger', $message);
    }
    redirect('admin_view_issued_books.php' . (!empty($_POST['return']) ? '?' . ltrim((string) $_POST['return'], '?') : ''));
}

$q = clean_text($_GET['q'] ?? '');
$status = in_array($_GET['status'] ?? 'active', ['active', 'overdue', 'returned', 'all'], true) ? ($_GET['status'] ?? 'active') : 'active';
$due = in_array($_GET['due'] ?? '', ['today', 'week', 'month'], true) ? $_GET['due'] : '';
$page = page_number();
$perPage = config('per_page');
$result = loans_search($q, $status, $due, $page);
$stats = library_stats();
$returnQuery = http_build_query(array_filter(['q' => $q, 'status' => $status !== 'active' ? $status : null, 'due' => $due, 'page' => $page > 1 ? $page : null]));

render_header('Issued Books', 'admin', 'loans');
?>
<h2 class="page-title mb-4"><i class="fas fa-book-reader me-2"></i>Issued Books</h2>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card"><div class="stat-card"><div class="stat-number"><?= $stats['active_loans'] ?></div><div class="stat-label">Currently issued</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="stat-card"><div class="stat-number text-success"><?= $stats['active_loans'] - $stats['overdue_loans'] ?></div><div class="stat-label">On time</div></div></div></div>
    <div class="col-md-4"><div class="card"><div class="stat-card"><div class="stat-number <?= $stats['overdue_loans'] ? 'text-danger' : '' ?>"><?= $stats['overdue_loans'] ?></div><div class="stat-label">Overdue</div></div></div></div>
</div>

<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2">
        <div class="col-md-5"><input type="search" name="q" class="form-control" placeholder="Search by book, author, student name or number" value="<?= e($q) ?>"></div>
        <div class="col-md-3">
            <select name="status" class="form-select" aria-label="Status">
                <?php foreach (['active' => 'Currently issued', 'overdue' => 'Overdue', 'returned' => 'Returned', 'all' => 'All loans'] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $status === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <select name="due" class="form-select" aria-label="Due">
                <?php foreach (['' => 'Any due date', 'today' => 'Due today', 'week' => 'Due this week', 'month' => 'Due this month'] as $k => $label): ?>
                    <option value="<?= $k ?>" <?= $due === $k ? 'selected' : '' ?>><?= $label ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fas fa-filter me-1"></i>Filter</button></div>
    </form>
</div></div>

<div class="card">
    <div class="card-header d-flex justify-content-between"><span><i class="fas fa-list me-2"></i>Loans</span><span><?= $result['total'] ?> records</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Book</th><th>Student</th><th>Issued</th><th>Due</th><th>Status</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $l): ?>
                <tr>
                    <td class="fw-semibold"><?= e($l['title']) ?><br><small class="text-muted"><?= e($l['author']) ?></small></td>
                    <td><?= e($l['user_name']) ?><br><small class="text-muted"><?= e($l['student_number']) ?></small></td>
                    <td><?= e(format_date($l['issued_at'])) ?></td>
                    <td><?= e(format_date($l['due_at'])) ?></td>
                    <td><?= status_badge($l) ?><?= loan_fine($l) > 0 ? '<br><small class="text-danger">Fine ' . e(format_money(loan_fine($l))) . '</small>' : '' ?></td>
                    <td class="text-end">
                        <?php if (!$l['returned_at']): ?>
                            <?php if (loan_can_renew($l)): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="renew">
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <input type="hidden" name="return" value="<?= e($returnQuery) ?>">
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-rotate-right me-1"></i>Renew</button>
                            </form>
                            <?php endif; ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="return">
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <input type="hidden" name="return" value="<?= e($returnQuery) ?>">
                                <button class="btn btn-sm btn-outline-success"><i class="fas fa-rotate-left me-1"></i>Mark returned</button>
                            </form>
                        <?php else: ?>
                            <small class="text-muted"><?= e(format_date($l['returned_at'])) ?></small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?><tr><td colspan="6" class="text-center text-muted py-5">No loans match these filters.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span>Showing <?= $result['rows'] ? ($page - 1) * $perPage + 1 : 0 ?>–<?= ($page - 1) * $perPage + count($result['rows']) ?> of <?= $result['total'] ?></span>
        <?= pagination($result['total'], $page, $perPage) ?>
    </div>
</div>
<?php render_footer();
