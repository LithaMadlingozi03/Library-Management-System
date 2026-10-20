<?php
declare(strict_types=1);

function session_boot(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('library_session');
    session_start();
}

/* ---- Who is logged in ---- */

function current_user(): ?array
{
    if (empty($_SESSION['user_id'])) return null;
    static $cache = [];
    $id = (int) $_SESSION['user_id'];
    // Re-read from the database so a deactivated student is locked out immediately.
    $cache[$id] ??= db_one('SELECT id, student_number, name, email, phone, program, is_active, must_change_password, created_at FROM users WHERE id = ?', [$id]);
    if (!$cache[$id] || !$cache[$id]['is_active']) {
        unset($_SESSION['user_id']);
        return null;
    }
    return $cache[$id];
}

function current_admin(): ?array
{
    if (empty($_SESSION['admin_id'])) return null;
    return db_one('SELECT id, employee_id, full_name, email, mobile, department, created_at FROM admins WHERE id = ?', [(int) $_SESSION['admin_id']]);
}

function require_user(): array
{
    $user = current_user();
    if (!$user) redirect('index.php');
    $script = basename($_SERVER['SCRIPT_NAME'] ?? '');
    if ($user['must_change_password'] && $script !== 'change_password.php' && $script !== 'logout.php') {
        flash('warning', 'Please choose a new password to continue.');
        redirect('change_password.php');
    }
    return $user;
}

function require_admin(): array
{
    $admin = current_admin();
    if (!$admin) redirect('admin_login.php');
    return $admin;
}

/* ---- Login / logout ---- */

function login_user(int $id): void
{
    session_regenerate_id(true);
    unset($_SESSION['admin_id']);
    $_SESSION['user_id'] = $id;
}

function login_admin(int $id): void
{
    session_regenerate_id(true);
    unset($_SESSION['user_id']);
    $_SESSION['admin_id'] = $id;
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 3600, $p['path'], $p['domain'] ?? '', $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/* ---- Brute-force protection ---- */

function throttle_key(string $scope, string $identifier): string
{
    return $scope . ':' . strtolower($identifier) . '|' . client_ip();
}

function throttle_locked(string $key): bool
{
    $since = date('Y-m-d H:i:s', time() - config('login_window_min') * 60);
    $count = (int) db_value('SELECT COUNT(*) FROM login_attempts WHERE attempt_key = ? AND attempted_at >= ?', [$key, $since]);
    return $count >= config('login_max_attempts');
}

function throttle_fail(string $key): void
{
    db_run('INSERT INTO login_attempts (attempt_key, attempted_at) VALUES (?, ?)', [$key, now()]);
    // Tidy up old rows now and then.
    db_run('DELETE FROM login_attempts WHERE attempted_at < ?', [date('Y-m-d H:i:s', time() - 86400)]);
}

function throttle_clear(string $key): void
{
    db_run('DELETE FROM login_attempts WHERE attempt_key = ?', [$key]);
}

/**
 * Checks an email + password for a table ('users' or 'admins').
 * Returns the row on success, or an error string. "No such account" and "wrong password"
 * give the same message so the form can't be used to discover which emails are registered.
 */
function attempt_login(string $table, string $scope, string $email, string $password)
{
    // A real hash to compare against when the email is unknown, so timing doesn't leak whether it exists.
    static $dummyHash = '$2y$10$iW7jcjqPh7FZrtYvgsIlEeRpc7V1LfWUdOoPFxRYJyKo1DDmLgP/q';

    $key = throttle_key($scope, $email);
    if (throttle_locked($key)) {
        return 'Too many failed attempts. Please wait ' . config('login_window_min') . ' minutes and try again.';
    }
    $row = db_one("SELECT * FROM $table WHERE email = ?", [strtolower($email)]);
    $passwordOk = password_verify($password, $row['password_hash'] ?? $dummyHash) && $row !== null;

    if (!$passwordOk) {
        throttle_fail($key);
        return 'Incorrect email or password.';
    }
    if (isset($row['is_active']) && !$row['is_active']) {
        return 'This account has been deactivated. Please contact the library.';
    }
    throttle_clear($key);
    if (password_needs_rehash($row['password_hash'], PASSWORD_DEFAULT)) {
        db_run("UPDATE $table SET password_hash = ? WHERE id = ?", [password_hash($password, PASSWORD_DEFAULT), $row['id']]);
    }
    return $row;
}
