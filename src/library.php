<?php
declare(strict_types=1);

/* =========================================================
 * Library business rules. Pages call these functions; none of them print HTML.
 * ========================================================= */

/** Escapes % and _ so user search text is matched literally by LIKE ... ESCAPE '!'. */
function like_pattern(string $text): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $text) . '%';
}

/* ---------------- Dashboard numbers ---------------- */

function library_stats(): array
{
    return [
        'users'         => (int) db_value('SELECT COUNT(*) FROM users WHERE is_active = 1'),
        'titles'        => (int) db_value('SELECT COUNT(*) FROM books'),
        'copies'        => (int) db_value('SELECT COALESCE(SUM(copies), 0) FROM books'),
        'active_loans'  => (int) db_value('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL'),
        'overdue_loans' => (int) db_value('SELECT COUNT(*) FROM loans WHERE returned_at IS NULL AND due_at < ?', [today()]),
    ];
}

/* ---------------- Books ---------------- */

const BOOK_SELECT = 'SELECT b.*, (b.copies - (SELECT COUNT(*) FROM loans l WHERE l.book_id = b.id AND l.returned_at IS NULL)) AS available FROM books b';

function book_categories(): array
{
    return array_column(db_all('SELECT DISTINCT category FROM books ORDER BY category'), 'category');
}

function books_search(string $q = '', string $category = '', int $page = 1, ?int $perPage = null): array
{
    $perPage ??= config('per_page');
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = "(b.title LIKE ? ESCAPE '!' OR b.author LIKE ? ESCAPE '!' OR b.isbn LIKE ? ESCAPE '!')";
        $like = like_pattern($q);
        array_push($params, $like, $like, $like);
    }
    if ($category !== '') {
        $where[] = 'b.category = ?';
        $params[] = $category;
    }
    $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $total = (int) db_value('SELECT COUNT(*) FROM books b' . $clause, $params);
    $offset = ($page - 1) * $perPage;
    $rows = db_all(BOOK_SELECT . $clause . " ORDER BY b.title LIMIT $perPage OFFSET $offset", $params);
    return ['rows' => $rows, 'total' => $total];
}

function book_get(int $id): ?array
{
    return db_one(BOOK_SELECT . ' WHERE b.id = ?', [$id]);
}

/** Validates book form input. Returns [cleanData, errors]. */
function book_validate(array $in): array
{
    $data = [
        'title'    => clean_text($in['title'] ?? ''),
        'author'   => clean_text($in['author'] ?? ''),
        'isbn'     => preg_replace('/[^0-9Xx-]/', '', (string) ($in['isbn'] ?? '')),
        'year'     => trim((string) ($in['year'] ?? '')),
        'category' => clean_text($in['category'] ?? '') ?: 'General',
        'copies'   => trim((string) ($in['copies'] ?? '1')),
    ];
    $errors = [];
    if ($data['title'] === '' || strlen($data['title']) > 255) $errors[] = 'Enter the book title (up to 255 characters).';
    if ($data['author'] === '' || strlen($data['author']) > 190) $errors[] = 'Enter the author.';
    if ($data['isbn'] !== '') {
        $digits = strlen(str_replace('-', '', $data['isbn']));
        if ($digits !== 10 && $digits !== 13) $errors[] = 'An ISBN has 10 or 13 digits.';
    }
    if ($data['year'] === '') {
        $data['year'] = null;
    } elseif (!ctype_digit($data['year']) || (int) $data['year'] < 1000 || (int) $data['year'] > (int) date('Y') + 1) {
        $errors[] = 'Enter a valid publication year.';
    } else {
        $data['year'] = (int) $data['year'];
    }
    if (!ctype_digit($data['copies']) || (int) $data['copies'] < 1 || (int) $data['copies'] > 999) {
        $errors[] = 'Copies must be a number from 1 to 999.';
    } else {
        $data['copies'] = (int) $data['copies'];
    }
    if (strlen($data['category']) > 80) $errors[] = 'Category is too long.';
    return [$data, $errors];
}

/** Adds (id = null) or updates a book. Returns [ok, message, id]. */
function book_save(array $data, ?int $id = null): array
{
    if ($id === null) {
        db_run('INSERT INTO books (title, author, isbn, year, category, copies, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$data['title'], $data['author'], $data['isbn'], $data['year'], $data['category'], $data['copies'], now()]);
        return [true, 'Book added.', (int) db()->lastInsertId()];
    }
    $out = (int) db_value('SELECT COUNT(*) FROM loans WHERE book_id = ? AND returned_at IS NULL', [$id]);
    if ($data['copies'] < $out) {
        return [false, "Copies can't be lower than the $out currently on loan.", $id];
    }
    db_run('UPDATE books SET title = ?, author = ?, isbn = ?, year = ?, category = ?, copies = ? WHERE id = ?',
        [$data['title'], $data['author'], $data['isbn'], $data['year'], $data['category'], $data['copies'], $id]);
    return [true, 'Book updated.', $id];
}

function book_delete(int $id): array
{
    if (db_value('SELECT COUNT(*) FROM loans WHERE book_id = ?', [$id])) {
        return [false, 'This book has loan history, so it can\'t be deleted. Set its copies to the number you still own instead.'];
    }
    return db_run('DELETE FROM books WHERE id = ?', [$id]) ? [true, 'Book deleted.'] : [false, 'Book not found.'];
}

/* ---------------- Students ---------------- */

function users_search(string $q = '', int $page = 1, ?int $perPage = null): array
{
    $perPage ??= config('per_page');
    $clause = '';
    $params = [];
    if ($q !== '') {
        $clause = " WHERE (u.name LIKE ? ESCAPE '!' OR u.email LIKE ? ESCAPE '!' OR u.student_number LIKE ? ESCAPE '!')";
        $like = like_pattern($q);
        $params = [$like, $like, $like];
    }
    $total = (int) db_value('SELECT COUNT(*) FROM users u' . $clause, $params);
    $offset = ($page - 1) * $perPage;
    $rows = db_all('SELECT u.*, (SELECT COUNT(*) FROM loans l WHERE l.user_id = u.id AND l.returned_at IS NULL) AS active_loans FROM users u'
        . $clause . " ORDER BY u.name LIMIT $perPage OFFSET $offset", $params);
    return ['rows' => $rows, 'total' => $total];
}

function user_get(int $id): ?array
{
    return db_one('SELECT * FROM users WHERE id = ?', [$id]);
}

/** Checks the sign-up form. Returns [cleanData, errors]. */
function user_validate(array $in, bool $needPassword = true): array
{
    $data = [
        'name'           => clean_text($in['name'] ?? ''),
        'student_number' => strtoupper(trim((string) ($in['student_number'] ?? ''))),
        'email'          => strtolower(trim((string) ($in['email'] ?? ''))),
        'phone'          => clean_text($in['phone'] ?? ''),
        'program'        => clean_text($in['program'] ?? ''),
    ];
    $errors = [];
    if (strlen($data['name']) < 2 || strlen($data['name']) > 120) $errors[] = 'Enter your full name.';
    if (!preg_match('/^[A-Z0-9-]{4,20}$/', $data['student_number'])) $errors[] = 'Student number should be 4–20 letters, numbers or dashes.';
    if (!valid_email($data['email'])) $errors[] = 'Enter a valid email address.';
    if ($data['phone'] !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $data['phone'])) $errors[] = 'Enter a valid phone number.';
    if (strlen($data['program']) > 120) $errors[] = 'Program name is too long.';
    if ($needPassword) {
        $password = (string) ($in['password'] ?? '');
        $errors = array_merge($errors, password_errors($password));
        if ($password !== (string) ($in['confirm_password'] ?? '')) $errors[] = 'The two passwords do not match.';
    }
    return [$data, $errors];
}

/** Creates a student. Returns [id|null, errors]. */
function user_create(array $in, bool $needPassword = true): array
{
    [$data, $errors] = user_validate($in, $needPassword);
    if (!$errors) {
        if (db_value('SELECT 1 FROM users WHERE email = ?', [$data['email']])) $errors[] = 'That email is already registered.';
        if (db_value('SELECT 1 FROM users WHERE student_number = ?', [$data['student_number']])) $errors[] = 'That student number is already registered.';
    }
    if ($errors) return [null, $errors];
    db_run('INSERT INTO users (student_number, name, email, phone, program, password_hash, is_active, must_change_password, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, ?, ?)',
        [$data['student_number'], $data['name'], $data['email'], $data['phone'], $data['program'], password_hash((string) ($in['password'] ?? ''), PASSWORD_DEFAULT), $needPassword ? 0 : 1, now()]);
    return [(int) db()->lastInsertId(), []];
}

/** Admin creates a student with a temporary password. Returns [id|null, errors, tempPassword]. */
function user_create_by_admin(array $in): array
{
    $temp = generate_temp_password();
    $in['password'] = $temp;
    [$data, $errors] = user_validate($in, false);
    if ($errors) return [null, $errors, ''];
    [$id, $errors] = user_create($in, false);
    return [$id, $errors, $temp];
}

function generate_temp_password(): string
{
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $pw = '';
    for ($i = 0; $i < 10; $i++) $pw .= $alphabet[random_int(0, strlen($alphabet) - 1)];
    return $pw . '7';   // guarantees at least one digit
}

function user_update_profile(int $id, array $in): array
{
    $name = clean_text($in['name'] ?? '');
    $phone = clean_text($in['phone'] ?? '');
    $program = clean_text($in['program'] ?? '');
    $errors = [];
    if (strlen($name) < 2 || strlen($name) > 120) $errors[] = 'Enter your full name.';
    if ($phone !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $phone)) $errors[] = 'Enter a valid phone number.';
    if (strlen($program) > 120) $errors[] = 'Program name is too long.';
    if (!$errors) db_run('UPDATE users SET name = ?, phone = ?, program = ? WHERE id = ?', [$name, $phone, $program, $id]);
    return $errors;
}

function user_set_active(int $id, bool $active): void
{
    db_run('UPDATE users SET is_active = ? WHERE id = ?', [$active ? 1 : 0, $id]);
}

/** Gives a student a one-time password they must change at next login. Returns the temporary password. */
function user_reset_password(int $id): string
{
    $temp = generate_temp_password();
    db_run('UPDATE users SET password_hash = ?, must_change_password = 1 WHERE id = ?', [password_hash($temp, PASSWORD_DEFAULT), $id]);
    return $temp;
}

function user_delete(int $id): array
{
    if (db_value('SELECT COUNT(*) FROM loans WHERE user_id = ?', [$id])) {
        return [false, 'This student has loan history, so the account can\'t be deleted. Deactivate it instead.'];
    }
    return db_run('DELETE FROM users WHERE id = ?', [$id]) ? [true, 'Student deleted.'] : [false, 'Student not found.'];
}

/* ---------------- Administrators ---------------- */

function admin_create(array $in): array
{
    $data = [
        'full_name'   => clean_text($in['full_name'] ?? ''),
        'email'       => strtolower(trim((string) ($in['email'] ?? ''))),
        'employee_id' => strtoupper(trim((string) ($in['employee_id'] ?? ''))),
    ];
    $password = (string) ($in['password'] ?? '');
    $errors = [];
    $key = (string) config('admin_key');
    if ($key === '') {
        $errors[] = 'Administrator registration is switched off. Set LIB_ADMIN_KEY to enable it.';
    } elseif (!hash_equals($key, (string) ($in['admin_key'] ?? ''))) {
        $errors[] = 'The administrator registration key is not correct.';
    }
    if (strlen($data['full_name']) < 2 || strlen($data['full_name']) > 120) $errors[] = 'Enter your full name.';
    if (!valid_email($data['email'])) $errors[] = 'Enter a valid email address.';
    if (!preg_match('/^[A-Z0-9-]{3,20}$/', $data['employee_id'])) $errors[] = 'Employee ID should be 3–20 letters, numbers or dashes.';
    $errors = array_merge($errors, password_errors($password, true));
    if ($password !== (string) ($in['confirm_password'] ?? '')) $errors[] = 'The two passwords do not match.';
    if (!$errors) {
        if (db_value('SELECT 1 FROM admins WHERE email = ?', [$data['email']])) $errors[] = 'That email is already registered.';
        if (db_value('SELECT 1 FROM admins WHERE employee_id = ?', [$data['employee_id']])) $errors[] = 'That employee ID is already registered.';
    }
    if ($errors) return [null, $errors];
    db_run('INSERT INTO admins (employee_id, full_name, email, password_hash, created_at) VALUES (?, ?, ?, ?, ?)',
        [$data['employee_id'], $data['full_name'], $data['email'], password_hash($password, PASSWORD_DEFAULT), now()]);
    return [(int) db()->lastInsertId(), []];
}

function admin_update_profile(int $id, array $in): array
{
    $name = clean_text($in['full_name'] ?? '');
    $mobile = clean_text($in['mobile'] ?? '');
    $department = clean_text($in['department'] ?? '');
    $errors = [];
    if (strlen($name) < 2 || strlen($name) > 120) $errors[] = 'Enter your full name.';
    if ($mobile !== '' && !preg_match('/^[0-9+() -]{7,20}$/', $mobile)) $errors[] = 'Enter a valid mobile number.';
    if ($department === '' || strlen($department) > 120) $errors[] = 'Enter your department.';
    if (!$errors) db_run('UPDATE admins SET full_name = ?, mobile = ?, department = ? WHERE id = ?', [$name, $mobile, $department, $id]);
    return $errors;
}

/** Changes a password after checking the current one. $table is 'users' or 'admins'. Returns a list of errors. */
function password_change(string $table, int $id, string $current, string $new, string $confirm): array
{
    if (!in_array($table, ['users', 'admins'], true)) throw new InvalidArgumentException('Bad table');
    $row = db_one("SELECT password_hash FROM $table WHERE id = ?", [$id]);
    $errors = [];
    if (!$row || !password_verify($current, $row['password_hash'])) $errors[] = 'Your current password is not correct.';
    $errors = array_merge($errors, password_errors($new, $table === 'admins'));
    if ($new !== $confirm) $errors[] = 'The new passwords do not match.';
    if (!$errors && $current === $new) $errors[] = 'Choose a password different from your current one.';
    if ($errors) return $errors;
    $extra = $table === 'users' ? ', must_change_password = 0' : '';
    db_run("UPDATE $table SET password_hash = ?$extra WHERE id = ?", [password_hash($new, PASSWORD_DEFAULT), $id]);
    return [];
}

/* ---------------- Loans ---------------- */

/** Lends a book to a student. Returns [ok, message]. */
function loan_issue(int $bookId, int $userId, ?int $adminId): array
{
    db_begin_write();
    try {
        $lock = db_driver() === 'mysql' ? ' FOR UPDATE' : '';
        $book = db_one('SELECT id, title, copies FROM books WHERE id = ?' . $lock, [$bookId]);
        $user = db_one('SELECT id, name, is_active FROM users WHERE id = ?', [$userId]);
        if (!$book || !$user) throw new RuntimeException('Book or student not found.');
        if (!$user['is_active']) throw new RuntimeException('This student account is deactivated.');

        $out = (int) db_value('SELECT COUNT(*) FROM loans WHERE book_id = ? AND returned_at IS NULL', [$bookId]);
        if ($out >= $book['copies']) throw new RuntimeException('No copies of "' . $book['title'] . '" are available.');

        if (db_value('SELECT 1 FROM loans WHERE book_id = ? AND user_id = ? AND returned_at IS NULL', [$bookId, $userId])) {
            throw new RuntimeException($user['name'] . ' already has this book.');
        }
        $has = (int) db_value('SELECT COUNT(*) FROM loans WHERE user_id = ? AND returned_at IS NULL', [$userId]);
        if ($has >= config('max_loans')) throw new RuntimeException($user['name'] . ' already has the maximum of ' . config('max_loans') . ' books.');
        if (db_value('SELECT 1 FROM loans WHERE user_id = ? AND returned_at IS NULL AND due_at < ?', [$userId, today()])) {
            throw new RuntimeException($user['name'] . ' has an overdue book. It must be returned first.');
        }

        $due = date('Y-m-d', strtotime('+' . config('loan_days') . ' days'));
        db_run('INSERT INTO loans (book_id, user_id, issued_by, issued_at, due_at) VALUES (?, ?, ?, ?, ?)', [$bookId, $userId, $adminId, today(), $due]);
        db_commit();
        return [true, '"' . $book['title'] . '" issued to ' . $user['name'] . '. Due ' . format_date($due) . '.'];
    } catch (RuntimeException $e) {
        db_rollback();
        return [false, $e->getMessage()];
    } catch (Throwable $e) {
        db_rollback();
        throw $e;
    }
}

function loan_return(int $loanId): array
{
    $loan = db_one('SELECT l.id, l.returned_at, b.title FROM loans l JOIN books b ON b.id = l.book_id WHERE l.id = ?', [$loanId]);
    if (!$loan) return [false, 'Loan not found.'];
    if ($loan['returned_at']) return [false, 'That book was already returned.'];
    db_run('UPDATE loans SET returned_at = ? WHERE id = ? AND returned_at IS NULL', [now(), $loanId]);
    return [true, '"' . $loan['title'] . '" marked as returned.'];
}

/** Human status for a loan row: returned / overdue / due soon / on loan. */
function loan_status(array $loan): array
{
    if ($loan['returned_at']) return ['Returned', 'secondary'];
    $days = (int) round((strtotime($loan['due_at']) - strtotime(today())) / 86400);
    if ($days < 0)  return ['Overdue by ' . abs($days) . ' day' . (abs($days) === 1 ? '' : 's'), 'danger'];
    if ($days <= 2) return ['Due ' . ($days === 0 ? 'today' : 'in ' . $days . ' day' . ($days === 1 ? '' : 's')), 'warning'];
    return ['On loan', 'success'];
}

/** Days a loan is (or was) late: 0 if on time. Returned loans are measured at the return date. */
function loan_days_late(array $loan): int
{
    $end = $loan['returned_at'] ? substr($loan['returned_at'], 0, 10) : today();
    return max(0, (int) round((strtotime($end) - strtotime($loan['due_at'])) / 86400));
}

/** Late fine for a loan (per day, per book). */
function loan_fine(array $loan): float
{
    return loan_days_late($loan) * (float) config('fine_per_day');
}

function format_money(float $amount): string
{
    return config('currency') . ' ' . number_format($amount, 2);
}

/** True if this loan could be renewed right now (active, not overdue, renewals left). */
function loan_can_renew(array $loan): bool
{
    return !$loan['returned_at'] && $loan['due_at'] >= today() && (int) ($loan['renewals'] ?? 0) < (int) config('max_renewals');
}

/** Extends a loan by another loan period. Pass $userId when a student renews their own loan. Returns [ok, message]. */
function loan_renew(int $loanId, ?int $userId = null): array
{
    $loan = db_one('SELECT l.*, b.title FROM loans l JOIN books b ON b.id = l.book_id WHERE l.id = ?', [$loanId]);
    if (!$loan || ($userId !== null && (int) $loan['user_id'] !== $userId)) return [false, 'Loan not found.'];
    if ($loan['returned_at']) return [false, 'That book was already returned.'];
    if ($loan['due_at'] < today()) return [false, 'Overdue books can\'t be renewed. Please return it to the library.'];
    if ((int) $loan['renewals'] >= (int) config('max_renewals')) return [false, 'This loan has already been renewed the maximum number of times.'];
    $due = date('Y-m-d', strtotime($loan['due_at'] . ' +' . config('loan_days') . ' days'));
    // The conditions are repeated in the UPDATE so a double-click can't renew twice.
    $changed = db_run('UPDATE loans SET due_at = ?, renewals = renewals + 1 WHERE id = ? AND returned_at IS NULL AND renewals < ?',
        [$due, $loanId, (int) config('max_renewals')]);
    return $changed ? [true, '"' . $loan['title'] . '" renewed. New due date ' . format_date($due) . '.'] : [false, 'This loan could not be renewed.'];
}

/** Searches loans. $status: active | overdue | returned | all.  $due: '' | today | week | month. */
function loans_search(string $q = '', string $status = 'active', string $due = '', int $page = 1, ?int $perPage = null): array
{
    $perPage ??= config('per_page');
    $where = [];
    $params = [];
    if ($q !== '') {
        $where[] = "(b.title LIKE ? ESCAPE '!' OR u.name LIKE ? ESCAPE '!' OR u.student_number LIKE ? ESCAPE '!' OR b.author LIKE ? ESCAPE '!')";
        $like = like_pattern($q);
        array_push($params, $like, $like, $like, $like);
    }
    switch ($status) {
        case 'active':   $where[] = 'l.returned_at IS NULL'; break;
        case 'overdue':  $where[] = 'l.returned_at IS NULL AND l.due_at < ?'; $params[] = today(); break;
        case 'returned': $where[] = 'l.returned_at IS NOT NULL'; break;
    }
    if ($due !== '' && $status !== 'returned') {
        $where[] = 'l.returned_at IS NULL';
        if ($due === 'today')      { $where[] = 'l.due_at = ?'; $params[] = today(); }
        elseif ($due === 'week')   { $where[] = 'l.due_at BETWEEN ? AND ?'; array_push($params, today(), date('Y-m-d', strtotime('+7 days'))); }
        elseif ($due === 'month')  { $where[] = 'l.due_at BETWEEN ? AND ?'; array_push($params, today(), date('Y-m-d', strtotime('+30 days'))); }
    }
    $clause = $where ? ' WHERE ' . implode(' AND ', $where) : '';
    $from = ' FROM loans l JOIN books b ON b.id = l.book_id JOIN users u ON u.id = l.user_id';
    $total = (int) db_value('SELECT COUNT(*)' . $from . $clause, $params);
    $offset = ($page - 1) * $perPage;
    $rows = db_all('SELECT l.*, b.title, b.author, u.name AS user_name, u.student_number' . $from . $clause
        . " ORDER BY l.returned_at IS NOT NULL, l.due_at ASC, l.id DESC LIMIT $perPage OFFSET $offset", $params);
    return ['rows' => $rows, 'total' => $total];
}

function user_loans(int $userId, bool $activeOnly = false): array
{
    return db_all('SELECT l.*, b.title, b.author FROM loans l JOIN books b ON b.id = l.book_id WHERE l.user_id = ?'
        . ($activeOnly ? ' AND l.returned_at IS NULL' : '') . ' ORDER BY l.returned_at IS NOT NULL, l.due_at ASC, l.id DESC', [$userId]);
}
