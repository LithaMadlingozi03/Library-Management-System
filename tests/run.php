<?php
/*
 * Tests for the library rules. Run with:   php tests/run.php
 * Uses a throw-away in-memory SQLite database, so it never touches real data.
 */
declare(strict_types=1);

putenv('LIB_DB_DRIVER=sqlite');
putenv('LIB_DB_PATH=:memory:');
putenv('LIB_ADMIN_KEY=test-key-123');
require __DIR__ . '/../src/bootstrap.php';

$passed = 0;
$failed = 0;
function check(string $name, bool $condition): void
{
    global $passed, $failed;
    if ($condition) { $passed++; echo "  ok   $name\n"; }
    else { $failed++; echo "  FAIL $name\n"; }
}
function section(string $title): void { echo "\n$title\n"; }

function student(string $n, array $extra = []): int
{
    [$id, $errors] = user_create($extra + [
        'name' => "Student $n", 'student_number' => "TEST$n", 'email' => "s$n@example.com",
        'password' => 'Passw0rd1', 'confirm_password' => 'Passw0rd1',
    ]);
    if (!$id) throw new RuntimeException(implode(' ', $errors));
    return $id;
}
function book(string $title, int $copies = 1): int
{
    [$data] = book_validate(['title' => $title, 'author' => 'Test Author', 'copies' => (string) $copies]);
    return book_save($data)[2];
}

section('Password rules');
check('student: short password rejected', password_errors('abc1') !== []);
check('student: letters only rejected', password_errors('abcdefgh') !== []);
check('student: 8 chars with letter+digit accepted', password_errors('Passw0rd') === []);
check('admin: needs 12+ and symbol', password_errors('Passw0rdPassw0rd', true) !== []);
check('admin: strong password accepted', password_errors('Library#Admin2026', true) === []);

section('Student registration');
$s1 = student('1');
check('student created', $s1 > 0);
check('password is stored hashed', str_starts_with((string) db_value('SELECT password_hash FROM users WHERE id = ?', [$s1]), '$2y$'));
[$id, $errors] = user_create(['name' => 'Dup', 'student_number' => 'TEST999', 'email' => 'S1@Example.com', 'password' => 'Passw0rd1', 'confirm_password' => 'Passw0rd1']);
check('duplicate email (any case) rejected', $id === null && $errors !== []);
[$id, $errors] = user_create(['name' => 'Dup', 'student_number' => 'test1', 'email' => 'new@example.com', 'password' => 'Passw0rd1', 'confirm_password' => 'Passw0rd1']);
check('duplicate student number rejected', $id === null && $errors !== []);
[$id, $errors] = user_create(['name' => 'X', 'student_number' => '!!', 'email' => 'bad', 'password' => 'a', 'confirm_password' => 'b']);
check('invalid fields rejected', $id === null && count($errors) >= 4);

section('Login and brute-force protection');
check('correct login returns the account', is_array(attempt_login('users', 'student', 's1@example.com', 'Passw0rd1')));
check('email is case-insensitive', is_array(attempt_login('users', 'student', 'S1@EXAMPLE.COM', 'Passw0rd1')));
$bad = attempt_login('users', 'student', 's1@example.com', 'wrong');
$unknown = attempt_login('users', 'student', 'nobody@example.com', 'wrong');
check('wrong password gives an error string', is_string($bad));
check('unknown email gives the SAME message (no account enumeration)', $bad === $unknown);
for ($i = 0; $i < 5; $i++) attempt_login('users', 'student', 'lock@example.com', 'wrong');
$locked = attempt_login('users', 'student', 'lock@example.com', 'wrong');
check('account locks after repeated failures', is_string($locked) && str_contains($locked, 'Too many'));
user_set_active($s1, false);
check('deactivated student cannot log in', is_string(attempt_login('users', 'student', 's1@example.com', 'Passw0rd1')));
user_set_active($s1, true);
check('reactivated student can log in', is_array(attempt_login('users', 'student', 's1@example.com', 'Passw0rd1')));

section('SQL injection attempts');
$evil = "' OR '1'='1";
check('login with SQL in email fails', is_string(attempt_login('users', 'student', $evil, $evil)));
check('book search with SQL text returns nothing', books_search("x' OR 1=1 --")['total'] === 0);
check('users table still intact', (int) db_value('SELECT COUNT(*) FROM users') >= 1);

section('Books');
[$data, $errors] = book_validate(['title' => '', 'author' => '', 'copies' => '0', 'year' => '1', 'isbn' => '123']);
check('invalid book rejected with several errors', count($errors) >= 4);
$b1 = book('Alpha Book', 2);
$b2 = book('Beta Book', 1);
check('books added', book_get($b1)['copies'] === 2);
check('available = copies when nothing is on loan', (int) book_get($b1)['available'] === 2);
check('search by title', books_search('alpha')['total'] === 1);
check('search treats % literally', books_search('%')['total'] === 0);
check('search by author', books_search('Test Author')['total'] === 2);
[$ok] = book_save(['title' => 'Alpha Book 2nd', 'author' => 'T', 'isbn' => '', 'year' => 2020, 'category' => 'General', 'copies' => 3], $b1);
check('book can be edited', $ok && book_get($b1)['title'] === 'Alpha Book 2nd');

section('Lending rules');
$admin = (int) (admin_create(['full_name' => 'Ann Admin', 'email' => 'ann@example.com', 'employee_id' => 'ADM9', 'password' => 'Library#Admin2026', 'confirm_password' => 'Library#Admin2026', 'admin_key' => 'test-key-123'])[0]);
check('admin created with correct key', $admin > 0);
[$ok, $msg] = loan_issue($b1, $s1, $admin);
check('book issued', $ok);
check('availability drops by one', (int) book_get($b1)['available'] === 2);
[$ok] = loan_issue($b1, $s1, $admin);
check('same student cannot borrow the same book twice', !$ok);
[$ok] = loan_issue($b2, $s1, $admin);
check('second different book is fine', $ok);
[$ok] = loan_issue($b2, student('2'), $admin);
check('cannot issue when no copies are left', !$ok);
$b3 = book('Gamma', 3); $b4 = book('Delta', 3);
[$ok] = loan_issue($b3, $s1, $admin);
check('third loan allowed (limit is ' . config('max_loans') . ')', $ok);
[$ok, $msg] = loan_issue($b4, $s1, $admin);
check('fourth loan blocked by the limit', !$ok && str_contains($msg, 'maximum'));
$s3 = student('3');
$loan = (int) db_value('SELECT id FROM loans WHERE book_id = ? AND user_id = ?', [$b3, $s1]);
db_run('UPDATE loans SET due_at = ? WHERE id = ?', [date('Y-m-d', strtotime('-3 days')), $loan]);
[$ok, $msg] = loan_issue($b4, $s1, $admin);
check('overdue book blocks new loans', !$ok);
check('overdue shows in status text', str_contains(loan_status(db_one('SELECT * FROM loans WHERE id = ?', [$loan]))[0], 'Overdue by 3 days'));
check('stats count the overdue loan', library_stats()['overdue_loans'] === 1);
[$ok] = loan_return($loan);
check('return works', $ok);
[$ok] = loan_return($loan);
check('returning twice is refused', !$ok);
[$ok] = loan_issue($b4, $s1, $admin);
check('after returning the overdue book, borrowing works again', $ok);
user_set_active($s3, false);
[$ok] = loan_issue($b4, $s3, $admin);
check('deactivated student cannot borrow', !$ok);
[$ok] = book_save(['title' => 'Delta', 'author' => 'y', 'isbn' => '', 'year' => null, 'category' => 'General', 'copies' => 0], $b4);
check('cannot reduce copies below the number on loan', !$ok);
[$ok] = book_delete($b2);
check('cannot delete a book with loan history', !$ok);
[$ok] = user_delete($s1);
check('cannot delete a student with loan history', !$ok);
$unused = book('Never borrowed');
check('unused book can be deleted', book_delete($unused)[0]);

section('Filtering loans');
check('active filter', loans_search('', 'active')['total'] === 3);
check('returned filter', loans_search('', 'returned')['total'] === 1);
check('search loans by student number', loans_search('TEST1', 'all')['total'] === 4);

section('Admin registration and passwords');
[$id, $errors] = admin_create(['full_name' => 'Bad', 'email' => 'b@example.com', 'employee_id' => 'ADM8', 'password' => 'Library#Admin2026', 'confirm_password' => 'Library#Admin2026', 'admin_key' => 'wrong']);
check('wrong admin key rejected', $id === null);
putenv('LIB_ADMIN_KEY=');
$temp = user_reset_password($s1);
check('reset gives a temporary password that works', is_array(attempt_login('users', 'student', 's1@example.com', $temp)));
check('reset forces a password change', (int) user_get($s1)['must_change_password'] === 1);
check('wrong current password refused', password_change('users', $s1, 'nope', 'NewPassw0rd', 'NewPassw0rd') !== []);
check('mismatched confirmation refused', password_change('users', $s1, $temp, 'NewPassw0rd', 'Other1234') !== []);
check('password change works', password_change('users', $s1, $temp, 'NewPassw0rd', 'NewPassw0rd') === []);
check('change clears the forced-change flag', (int) user_get($s1)['must_change_password'] === 0);
check('new password logs in', is_array(attempt_login('users', 'student', 's1@example.com', 'NewPassw0rd')));

section('Renewals and fines');
$rb = book('Renewable', 2);
$rs = student('R1'); $rs2 = student('R2');
loan_issue($rb, $rs, $admin);
$rl = (int) db_value('SELECT id FROM loans WHERE book_id = ? AND user_id = ?', [$rb, $rs]);
$due0 = db_value('SELECT due_at FROM loans WHERE id = ?', [$rl]);
check('new loan can be renewed', loan_can_renew(db_one('SELECT * FROM loans WHERE id = ?', [$rl])));
[$ok] = loan_renew($rl, $rs2);
check("a student can't renew someone else's loan", !$ok);
[$ok] = loan_renew($rl, $rs);
check('student can renew own loan', $ok);
check('due date moves out by one loan period', db_value('SELECT due_at FROM loans WHERE id = ?', [$rl]) === date('Y-m-d', strtotime("$due0 +" . config('loan_days') . ' days')));
[$ok, $msg] = loan_renew($rl, $rs);
check('renewal limit enforced (' . config('max_renewals') . ')', !$ok && str_contains($msg, 'maximum'));
check('renew button hidden once limit reached', !loan_can_renew(db_one('SELECT * FROM loans WHERE id = ?', [$rl])));
db_run('UPDATE loans SET renewals = 0, due_at = ? WHERE id = ?', [date('Y-m-d', strtotime('-2 days')), $rl]);
[$ok, $msg] = loan_renew($rl);
check('overdue loans cannot be renewed', !$ok && str_contains($msg, 'Overdue'));
$late = db_one('SELECT * FROM loans WHERE id = ?', [$rl]);
check('days late counted', loan_days_late($late) === 2);
check('fine = days late x daily rate', abs(loan_fine($late) - 2 * config('fine_per_day')) < 0.001);
check('money formats with currency', format_money(4) === config('currency') . ' 4.00');
loan_return($rl);
db_run('UPDATE loans SET returned_at = ? WHERE id = ?', [date('Y-m-d', strtotime('-1 day')) . ' 10:00:00', $rl]);
check('fine stops growing after return (1 day late)', loan_days_late(db_one('SELECT * FROM loans WHERE id = ?', [$rl])) === 1);
[$ok] = loan_renew($rl);
check('returned loans cannot be renewed', !$ok);

section('Input hardening');
check('clean_text ignores array input', clean_text(['x']) === '');
$_GET['page'] = '99999999999999999999';
check('page number is capped', page_number() === 100000);
$_GET['page'] = '-5';
check('negative page becomes 1', page_number() === 1);
unset($_GET['page']);

section('Output escaping');
check('e() escapes HTML', e('<script>alert("x")</script>') === '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;');

echo "\n$passed passed, $failed failed\n";
exit($failed ? 1 : 0);
