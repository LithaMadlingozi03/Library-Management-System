<?php
declare(strict_types=1);

/**
 * Prints the top of a page: <head>, navigation bar and any flash messages.
 * $area: 'guest' (not logged in), 'user' (student) or 'admin'.
 */
function render_header(string $title, string $area = 'guest', string $active = ''): void
{
    $app = config('app_name');
    $nav = function (string $href, string $icon, string $label, string $key) use ($active): string {
        $cls = 'nav-link' . ($active === $key ? ' active' : '');
        return '<li class="nav-item"><a class="' . $cls . '" href="' . e($href) . '"><i class="fas fa-' . $icon . ' me-1"></i>' . e($label) . '</a></li>';
    };
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - <?= e($app) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/app.css">
</head>
<body class="d-flex flex-column min-vh-100">
<nav class="navbar navbar-expand-lg navbar-dark">
    <div class="container">
        <a class="navbar-brand" href="<?= $area === 'admin' ? 'admin_dashboard.php' : ($area === 'user' ? 'user_dashboard.php' : 'index.php') ?>">
            <i class="fas fa-book-open me-2"></i><?= e($area === 'admin' ? 'Library Admin' : $app) ?>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarContent" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarContent">
            <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">
<?php if ($area === 'guest'): ?>
                <?= $nav('index.php', 'sign-in-alt', 'Student Login', 'login') ?>
                <?= $nav('admin_login.php', 'user-shield', 'Admin Login', 'admin_login') ?>
                <?= $nav('signup.php', 'user-plus', 'Register', 'signup') ?>
<?php elseif ($area === 'user'): ?>
                <?= $nav('user_dashboard.php', 'search', 'Catalogue', 'catalogue') ?>
                <?= $nav('my_books.php', 'book', 'My Books', 'my_books') ?>
                <?= $nav('profile.php', 'user-circle', 'Profile', 'profile') ?>
                <?= $nav('change_password.php', 'key', 'Password', 'password') ?>
                <li class="nav-item"><?= logout_form() ?></li>
<?php else: ?>
                <?= $nav('admin_dashboard.php', 'gauge', 'Dashboard', 'dashboard') ?>
                <?= $nav('manage_books.php', 'book', 'Books', 'books') ?>
                <?= $nav('manage_users.php', 'users', 'Students', 'users') ?>
                <?= $nav('admin_view_issued_books.php', 'book-reader', 'Loans', 'loans') ?>
                <?= $nav('admin_view_profile.php', 'user-circle', 'Profile', 'profile') ?>
                <li class="nav-item"><?= logout_form() ?></li>
<?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
<main class="container page-container flex-grow-1">
<?php foreach (flash() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($f['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endforeach;
}

function logout_form(): string
{
    return '<form method="post" action="logout.php" class="d-inline">' . csrf_field()
        . '<button type="submit" class="nav-link btn btn-link"><i class="fas fa-sign-out-alt me-1"></i>Logout</button></form>';
}

/** Prints a list of form errors as a Bootstrap alert. */
function render_errors(array $errors): void
{
    if (!$errors) return;
    echo '<div class="alert alert-danger" role="alert"><ul class="mb-0">';
    foreach ($errors as $error) echo '<li>' . e($error) . '</li>';
    echo '</ul></div>';
}

/** Prints the bottom of a page: footer and scripts. */
function render_footer(): void
{
    ?>
</main>
<footer class="mt-auto">
    <div class="container text-center">
        <p class="mb-0">&copy; <?= date('Y') ?> <?= e(config('institution')) ?>. Student project &mdash; not an official institutional system.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/app.js"></script>
</body>
</html>
<?php
}

/** Small coloured pill for a loan status. */
function status_badge(array $loan): string
{
    [$label, $color] = loan_status($loan);
    return '<span class="badge bg-' . $color . ($color === 'warning' ? ' text-dark' : '') . '">' . e($label) . '</span>';
}

/** Availability pill for a book row (needs the 'available' column). */
function availability_badge(array $book): string
{
    $n = (int) $book['available'];
    return $n > 0
        ? '<span class="badge bg-success">' . $n . ' of ' . (int) $book['copies'] . ' available</span>'
        : '<span class="badge bg-danger">All copies on loan</span>';
}
