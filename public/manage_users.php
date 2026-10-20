<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

$errors = [];
$old = ['name' => '', 'student_number' => '', 'email' => '', 'phone' => '', 'program' => ''];
$openModal = false;

if (is_post()) {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $id = (int) ($_POST['id'] ?? 0);
    $back = 'manage_users.php' . (!empty($_POST['return']) ? '?' . ltrim((string) $_POST['return'], '?') : '');

    if ($action === 'add') {
        $old = array_intersect_key($_POST, $old) + $old;
        [$newId, $errors, $temp] = user_create_by_admin($_POST);
        if ($newId) {
            flash('success', "Student added. Temporary password: $temp (give it to the student; they must change it at first login).");
            redirect($back);
        }
        $openModal = true;
    } elseif ($action === 'toggle' && ($u = user_get($id))) {
        user_set_active($id, !$u['is_active']);
        flash('success', $u['name'] . ($u['is_active'] ? ' was deactivated.' : ' was reactivated.'));
        redirect($back);
    } elseif ($action === 'reset' && ($u = user_get($id))) {
        $temp = user_reset_password($id);
        flash('success', 'Temporary password for ' . $u['name'] . ': ' . $temp . ' (they must change it at next login).');
        redirect($back);
    } elseif ($action === 'delete') {
        [$ok, $message] = user_delete($id);
        flash($ok ? 'success' : 'danger', $message);
        redirect($back);
    } else {
        redirect($back);
    }
}

$q = clean_text($_GET['q'] ?? '');
$page = page_number();
$perPage = config('per_page');
$result = users_search($q, $page);
$returnQuery = http_build_query(array_filter(['q' => $q, 'page' => $page > 1 ? $page : null]));

/** Small POST button used in the table. */
function user_action_form(array $u, string $action, string $icon, string $class, string $title, string $returnQuery, ?string $confirm = null): string
{
    return '<form method="post" class="d-inline"' . ($confirm ? ' data-confirm="' . e($confirm) . '"' : '') . '>' . csrf_field()
        . '<input type="hidden" name="action" value="' . e($action) . '"><input type="hidden" name="id" value="' . (int) $u['id'] . '">'
        . '<input type="hidden" name="return" value="' . e($returnQuery) . '">'
        . '<button class="btn btn-sm ' . $class . '" title="' . e($title) . '" aria-label="' . e($title) . '"><i class="fas fa-' . $icon . '"></i></button></form>';
}

render_header('Manage Students', 'admin', 'users');
?>
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="page-title mb-0"><i class="fas fa-users me-2"></i>Students</h2>
    <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#addUserModal"><i class="fas fa-user-plus me-2"></i>Add Student</button>
</div>

<div class="card mb-4"><div class="card-body">
    <form method="get" class="row g-2">
        <div class="col-md-10"><input type="search" name="q" class="form-control" placeholder="Search by name, email or student number" value="<?= e($q) ?>"></div>
        <div class="col-md-2"><button class="btn btn-primary w-100"><i class="fas fa-search me-1"></i>Search</button></div>
    </form>
</div></div>

<div class="card">
    <div class="card-header d-flex justify-content-between"><span><i class="fas fa-list me-2"></i>Library users</span><span><?= $result['total'] ?> records</span></div>
    <div class="table-responsive">
        <table class="table table-hover mb-0 align-middle">
            <thead><tr><th>Student no.</th><th>Name</th><th>Email</th><th>Phone</th><th>Program</th><th>Books</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            <?php foreach ($result['rows'] as $u): ?>
                <tr>
                    <td><?= e($u['student_number']) ?></td>
                    <td class="fw-semibold"><?= e($u['name']) ?></td>
                    <td><?= e($u['email']) ?></td>
                    <td class="text-nowrap"><?= e($u['phone'] ?: '—') ?></td>
                    <td><?= e($u['program'] ?: '—') ?></td>
                    <td><?= (int) $u['active_loans'] ?></td>
                    <td><?= $u['is_active'] ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Deactivated</span>' ?></td>
                    <td class="text-end text-nowrap">
                        <?= user_action_form($u, 'reset', 'key', 'btn-outline-primary', 'Reset password', $returnQuery, 'Give ' . $u['name'] . ' a new temporary password?') ?>
                        <?= user_action_form($u, 'toggle', $u['is_active'] ? 'user-slash' : 'user-check', 'btn-outline-warning', $u['is_active'] ? 'Deactivate' : 'Reactivate', $returnQuery) ?>
                        <?= user_action_form($u, 'delete', 'trash', 'btn-outline-danger', 'Delete', $returnQuery, 'Delete ' . $u['name'] . '? This can\'t be undone.') ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$result['rows']): ?><tr><td colspan="8" class="text-center text-muted py-5">No students found.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <span>Showing <?= $result['rows'] ? ($page - 1) * $perPage + 1 : 0 ?>–<?= ($page - 1) * $perPage + count($result['rows']) ?> of <?= $result['total'] ?></span>
        <?= pagination($result['total'], $page, $perPage) ?>
    </div>
</div>

<div class="modal fade" id="addUserModal" tabindex="-1" aria-labelledby="addUserModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg"><div class="modal-content">
        <form method="post" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <input type="hidden" name="return" value="<?= e($returnQuery) ?>">
            <div class="modal-header">
                <h5 class="modal-title" id="addUserModalLabel"><i class="fas fa-user-plus me-2"></i>Add Student</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <?php render_errors($errors); ?>
                <div class="row">
                    <div class="col-md-6 mb-3"><label class="form-label" for="n_name">Full name</label>
                        <input class="form-control" id="n_name" name="name" value="<?= e($old['name']) ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="n_sn">Student number</label>
                        <input class="form-control" id="n_sn" name="student_number" value="<?= e($old['student_number']) ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="n_email">Email</label>
                        <input type="email" class="form-control" id="n_email" name="email" value="<?= e($old['email']) ?>" required></div>
                    <div class="col-md-6 mb-3"><label class="form-label" for="n_phone">Phone</label>
                        <input type="tel" class="form-control" id="n_phone" name="phone" value="<?= e($old['phone']) ?>"></div>
                    <div class="col-12 mb-1"><label class="form-label" for="n_prog">Academic program</label>
                        <input class="form-control" id="n_prog" name="program" value="<?= e($old['program']) ?>"></div>
                </div>
                <p class="text-muted small mb-0">A temporary password is generated and shown once after saving.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button class="btn btn-primary">Add Student</button>
            </div>
        </form>
    </div></div>
</div>
<?php if ($openModal): ?>
<script>document.addEventListener('DOMContentLoaded', function () { new bootstrap.Modal(document.getElementById('addUserModal')).show(); });</script>
<?php endif; ?>
<?php render_footer();
