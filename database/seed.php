<?php
/*
 * Fills the database with FICTITIOUS demonstration data.
 *
 *   php database/seed.php           add demo data (stops if the database already has books/students)
 *   php database/seed.php --fresh   SQLite only: delete the database file first, then add demo data
 *
 * Demo logins (change or delete these before using the app for real):
 *   Administrator: admin@example.com          / Library#Admin2026
 *   Students:      thandi.nkosi@example.com   / Student2026   (and every other seeded student)
 */
declare(strict_types=1);
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');

require __DIR__ . '/../src/bootstrap.php';

if (in_array('--fresh', $argv, true) && db_driver() === 'sqlite') {
    $path = config('db.sqlite_path');
    if ($path !== ':memory:' && is_file($path)) unlink($path);
}

if ((int) db_value('SELECT COUNT(*) FROM books') > 0 || (int) db_value('SELECT COUNT(*) FROM users') > 0) {
    fwrite(STDERR, "The database already has data. Use --fresh (SQLite) or empty the tables first.\n");
    exit(1);
}

mt_srand(2026);   // same demo data every time
$studentHash = password_hash('Student2026', PASSWORD_DEFAULT);
$adminHash = password_hash('Library#Admin2026', PASSWORD_DEFAULT);
$created = now();

// --- Administrator ---------------------------------------------------------
db_run('INSERT INTO admins (employee_id, full_name, email, mobile, department, password_hash, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
    ['ADM0001', 'Demo Librarian', 'admin@example.com', '+27 83 000 0001', 'Library Services', $adminHash, $created]);
$adminId = (int) db()->lastInsertId();

// --- Students (made-up people) --------------------------------------------
$names = ['Thandi Nkosi', 'Sipho Dlamini', 'Aisha Patel', 'Liam van der Merwe', 'Naledi Mokoena', 'Kabelo Sithole',
    'Zanele Khumalo', 'Ethan Naidoo', 'Lerato Molefe', 'Jabu Zulu', 'Fatima Khan', 'Pieter Botha',
    'Nomsa Ndlovu', 'Tumelo Mahlangu', 'Priya Singh', 'Johan Pretorius', 'Ayanda Cele', 'Refilwe Tau',
    'Mandla Mthembu', 'Chloe Jacobs', 'Bongani Zwane', 'Karabo Letsoalo', 'Sarah Adams', 'Themba Radebe'];
$programs = ['BSc Information Technology', 'BSc Computer Science', 'BCom Accounting', 'BA Psychology', 'BCom Business Management'];
$studentIds = [];
foreach ($names as $i => $name) {
    $email = strtolower(str_replace(' ', '.', $name)) . '@example.com';
    db_run('INSERT INTO users (student_number, name, email, phone, program, password_hash, is_active, must_change_password, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 0, ?)',
        [sprintf('DEMO%04d', 1001 + $i), $name, $email, sprintf('+27 82 555 %04d', 1000 + $i), $programs[$i % count($programs)],
         $studentHash, $i === 21 ? 0 : 1, date('Y-m-d H:i:s', strtotime('-' . (30 + $i * 9) . ' days'))]);
    $studentIds[] = (int) db()->lastInsertId();
}

// --- Books -----------------------------------------------------------------
$books = [
    ['Pride and Prejudice', 'Jane Austen', 1813, 'Fiction'], ['Great Expectations', 'Charles Dickens', 1861, 'Fiction'],
    ['Things Fall Apart', 'Chinua Achebe', 1958, 'Fiction'], ['Cry, the Beloved Country', 'Alan Paton', 1948, 'Fiction'],
    ['Frankenstein', 'Mary Shelley', 1818, 'Fiction'], ['The Great Gatsby', 'F. Scott Fitzgerald', 1925, 'Fiction'],
    ['Nineteen Eighty-Four', 'George Orwell', 1949, 'Fiction'], ['To Kill a Mockingbird', 'Harper Lee', 1960, 'Fiction'],
    ['Introduction to Algorithms', 'Cormen, Leiserson, Rivest, Stein', 2009, 'Computer Science'],
    ['Clean Code', 'Robert C. Martin', 2008, 'Computer Science'], ['The Pragmatic Programmer', 'Andrew Hunt, David Thomas', 1999, 'Computer Science'],
    ['Database System Concepts', 'Silberschatz, Korth, Sudarshan', 2019, 'Computer Science'],
    ['Computer Networking: A Top-Down Approach', 'Kurose, Ross', 2021, 'Computer Science'],
    ['Operating System Concepts', 'Silberschatz, Galvin, Gagne', 2018, 'Computer Science'],
    ['Artificial Intelligence: A Modern Approach', 'Russell, Norvig', 2020, 'Computer Science'],
    ['Design Patterns', 'Gamma, Helm, Johnson, Vlissides', 1994, 'Computer Science'],
    ['Learning PHP, MySQL & JavaScript', 'Robin Nixon', 2021, 'Computer Science'],
    ['Eloquent JavaScript', 'Marijn Haverbeke', 2018, 'Computer Science'],
    ['Principles of Accounting', 'Needles, Powers, Crosson', 2013, 'Business'], ['Financial Accounting', 'Weygandt, Kimmel, Kieso', 2018, 'Business'],
    ['Principles of Marketing', 'Philip Kotler, Gary Armstrong', 2020, 'Business'], ['Good to Great', 'Jim Collins', 2001, 'Business'],
    ['The Lean Startup', 'Eric Ries', 2011, 'Business'], ['Management', 'Stephen Robbins, Mary Coulter', 2017, 'Business'],
    ['Organizational Behavior', 'Stephen Robbins, Timothy Judge', 2018, 'Business'],
    ['Psychology', 'Saundra Ciccarelli, J. Noland White', 2017, 'Psychology'], ['Thinking, Fast and Slow', 'Daniel Kahneman', 2011, 'Psychology'],
    ['Man\'s Search for Meaning', 'Viktor Frankl', 1946, 'Psychology'], ['Introduction to Social Psychology', 'Hewstone, Stroebe', 2012, 'Psychology'],
    ['A Brief History of Time', 'Stephen Hawking', 1988, 'Science'], ['The Selfish Gene', 'Richard Dawkins', 1976, 'Science'],
    ['Cosmos', 'Carl Sagan', 1980, 'Science'], ['Silent Spring', 'Rachel Carson', 1962, 'Science'],
    ['Calculus: Early Transcendentals', 'James Stewart', 2015, 'Mathematics'], ['Linear Algebra Done Right', 'Sheldon Axler', 2015, 'Mathematics'],
    ['Discrete Mathematics and Its Applications', 'Kenneth Rosen', 2018, 'Mathematics'], ['Statistics for Business and Economics', 'Anderson, Sweeney, Williams', 2016, 'Mathematics'],
    ['Long Walk to Freedom', 'Nelson Mandela', 1994, 'History'], ['Sapiens', 'Yuval Noah Harari', 2011, 'History'],
    ['Guns, Germs, and Steel', 'Jared Diamond', 1997, 'History'], ['A History of South Africa', 'Leonard Thompson', 2001, 'History'],
    ['The Elements of Style', 'Strunk and White', 1959, 'Study Skills'], ['How to Read a Book', 'Mortimer Adler, Charles Van Doren', 1940, 'Study Skills'],
];
$bookIds = [];
foreach ($books as $i => [$title, $author, $year, $category]) {
    $copies = mt_rand(2, 4);
    if ($i === 14) $copies = 1;   // one title that will be fully out on loan
    $stub = '978000' . sprintf('%06d', $i + 1);
    $sum = 0;
    foreach (str_split($stub) as $k => $d) $sum += (int) $d * ($k % 2 ? 3 : 1);
    $isbn = $stub . ((10 - $sum % 10) % 10);
    db_run('INSERT INTO books (title, author, isbn, year, category, copies, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$title, $author, $isbn, $year, $category, $copies, $created]);
    $bookIds[] = (int) db()->lastInsertId();
}

// --- Loans (dates are relative to today so the demo always looks current) -----
$addLoan = function (int $book, int $student, int $issuedDaysAgo, ?int $returnedDaysAgo) use ($adminId) {
    $issued = date('Y-m-d', strtotime("-$issuedDaysAgo days"));
    $due = date('Y-m-d', strtotime($issued . ' +' . config('loan_days') . ' days'));
    $returned = $returnedDaysAgo === null ? null : date('Y-m-d H:i:s', strtotime("-$returnedDaysAgo days 10:30"));
    db_run('INSERT INTO loans (book_id, user_id, issued_by, issued_at, due_at, returned_at) VALUES (?, ?, ?, ?, ?, ?)',
        [$book, $student, $adminId, $issued, $due, $returned]);
};

$activeOnTime = [[0, 0, 2], [8, 1, 5], [9, 2, 8], [20, 3, 1], [29, 4, 3], [33, 5, 6], [37, 6, 9], [40, 7, 4], [14, 8, 7], [24, 9, 10]];
foreach ($activeOnTime as [$b, $s, $days]) $addLoan($bookIds[$b], $studentIds[$s], $days, null);
foreach ([[11, 10, 12], [15, 11, 13], [17, 12, 12]] as [$b, $s, $days]) $addLoan($bookIds[$b], $studentIds[$s], $days, null);       // due very soon
foreach ([[2, 13, 21], [10, 14, 26], [22, 15, 30], [30, 16, 35], [36, 17, 24]] as [$b, $s, $days]) $addLoan($bookIds[$b], $studentIds[$s], $days, null); // overdue
$history = [[1, 18], [3, 19], [5, 20], [7, 0], [12, 1], [13, 2], [16, 3], [18, 4], [19, 5], [21, 6], [23, 7], [25, 8], [26, 9], [27, 10], [28, 11],
    [31, 12], [32, 13], [34, 14], [35, 15], [38, 16], [39, 17], [41, 18], [42, 19], [4, 20], [6, 0]];
foreach ($history as $i => [$b, $s]) {
    $issuedAgo = 20 + $i * 3;
    $addLoan($bookIds[$b], $studentIds[$s], $issuedAgo, $issuedAgo - mt_rand(5, 14));
}

echo "Demo data added:\n";
echo '  ' . count($names) . " students, " . count($books) . " books, " . (int) db_value('SELECT COUNT(*) FROM loans') . " loans\n";
echo "  Admin login:   admin@example.com / Library#Admin2026\n";
echo "  Student login: thandi.nkosi@example.com / Student2026\n";
