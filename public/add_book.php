<?php
require __DIR__ . '/../src/bootstrap.php';
$admin = require_admin();

// Used for both adding a book and editing one (?id=...).
$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$book = $id ? book_get($id) : null;
if ($id && !$book) {
    flash('danger', 'That book does not exist.');
    redirect('manage_books.php');
}

$errors = [];
$form = $book ?? ['title' => '', 'author' => '', 'isbn' => '', 'year' => '', 'category' => 'General', 'copies' => 1];
if (is_post()) {
    csrf_check();
    [$data, $errors] = book_validate($_POST);
    $form = $data;
    if (!$errors) {
        [$ok, $message] = book_save($data, $id);
        if ($ok) {
            flash('success', $message);
            redirect('manage_books.php');
        }
        $errors[] = $message;
    }
}

render_header($id ? 'Edit Book' : 'Add Book', 'admin', 'books');
?>
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header"><i class="fas fa-<?= $id ? 'edit' : 'plus-circle' ?> me-2"></i><?= $id ? 'Edit Book' : 'Add New Book' ?></div>
            <div class="card-body p-4">
                <?php render_errors($errors); ?>
                <form method="post" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                        <label for="title" class="form-label">Book title</label>
                        <input type="text" name="title" id="title" class="form-control" value="<?= e($form['title']) ?>" maxlength="255" required>
                    </div>
                    <div class="mb-3">
                        <label for="author" class="form-label">Author</label>
                        <input type="text" name="author" id="author" class="form-control" value="<?= e($form['author']) ?>" maxlength="190" required>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="isbn" class="form-label">ISBN</label>
                            <input type="text" name="isbn" id="isbn" class="form-control" value="<?= e($form['isbn']) ?>" placeholder="10 or 13 digits">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="year" class="form-label">Year published</label>
                            <input type="number" name="year" id="year" class="form-control" value="<?= e($form['year']) ?>" min="1000" max="<?= (int) date('Y') + 1 ?>">
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="category" class="form-label">Category</label>
                            <input type="text" name="category" id="category" class="form-control" value="<?= e($form['category']) ?>" list="categories" maxlength="80">
                            <datalist id="categories"><?php foreach (book_categories() as $c): ?><option value="<?= e($c) ?>"><?php endforeach; ?></datalist>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="copies" class="form-label">Copies owned</label>
                            <input type="number" name="copies" id="copies" class="form-control" value="<?= e($form['copies']) ?>" min="1" max="999" required>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end gap-2">
                        <a href="manage_books.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left me-2"></i>Cancel</a>
                        <button class="btn btn-primary"><i class="fas fa-save me-2"></i><?= $id ? 'Save changes' : 'Add Book' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php render_footer();
