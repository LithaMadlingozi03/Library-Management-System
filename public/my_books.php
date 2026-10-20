<?php
require __DIR__ . '/../src/bootstrap.php';
$user = require_user();

if (is_post()) {
    csrf_check();
    if (($_POST['action'] ?? '') === 'renew') {
        [$ok, $message] = loan_renew((int) ($_POST['id'] ?? 0), (int) $user['id']);
        flash($ok ? 'success' : 'danger', $message);
    }
    redirect('my_books.php');
}

$loans = user_loans((int) $user['id']);

render_header('My Books', 'user', 'my_books');
?>
<h2 class="page-title mb-4"><i class="fas fa-book me-2"></i>My Books</h2>
<div class="card">
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Title</th><th>Author</th><th>Issued</th><th>Due</th><th>Returned</th><th>Status</th><th>Fine</th><th class="text-end">Action</th></tr></thead>
            <tbody>
            <?php foreach ($loans as $l): ?>
                <tr>
                    <td class="fw-semibold"><?= e($l['title']) ?></td>
                    <td><?= e($l['author']) ?></td>
                    <td><?= e(format_date($l['issued_at'])) ?></td>
                    <td><?= e(format_date($l['due_at'])) ?></td>
                    <td><?= e(format_date($l['returned_at'])) ?></td>
                    <td><?= status_badge($l) ?></td>
                    <td><?= loan_fine($l) > 0 ? '<span class="text-danger fw-semibold">' . e(format_money(loan_fine($l))) . '</span>' : '—' ?></td>
                    <td class="text-end">
                        <?php if (loan_can_renew($l)): ?>
                            <form method="post" class="d-inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="renew">
                                <input type="hidden" name="id" value="<?= (int) $l['id'] ?>">
                                <button class="btn btn-sm btn-outline-primary"><i class="fas fa-rotate-right me-1"></i>Renew</button>
                            </form>
                        <?php elseif (!$l['returned_at'] && (int) $l['renewals'] >= (int) config('max_renewals')): ?>
                            <small class="text-muted">Renewed</small>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$loans): ?>
                <tr><td colspan="8" class="text-center text-muted py-5">You haven't borrowed any books yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php render_footer();
