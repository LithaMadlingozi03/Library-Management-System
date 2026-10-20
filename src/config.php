<?php
/*
 * Application settings. Override any value with environment variables,
 * or create src/config.local.php returning an array of overrides (it is git-ignored).
 */
$config = [
    'app_name'    => getenv('LIB_APP_NAME') ?: 'Richfield Online Library',
    'institution' => getenv('LIB_INSTITUTION') ?: 'Richfield Graduate Institute of Technology',
    'timezone'    => getenv('LIB_TIMEZONE') ?: 'Africa/Johannesburg',

    'db' => [
        // 'sqlite' works out of the box. Use 'mysql' for a production-style setup.
        'driver'      => getenv('LIB_DB_DRIVER') ?: 'sqlite',
        'sqlite_path' => getenv('LIB_DB_PATH') ?: __DIR__ . '/../database/library.sqlite',
        'host'        => getenv('LIB_DB_HOST') ?: '127.0.0.1',
        'port'        => getenv('LIB_DB_PORT') ?: '3306',
        'name'        => getenv('LIB_DB_NAME') ?: 'lms',
        'user'        => getenv('LIB_DB_USER') ?: 'root',
        'pass'        => getenv('LIB_DB_PASS') ?: '',
    ],

    'loan_days'   => 14,   // how long a book can be borrowed
    'max_loans'   => 3,    // active loans allowed per student
    'max_renewals' => 1,   // how many times one loan can be extended (by another loan_days)
    'fine_per_day' => 2.00, // late fine per day, per book (set to 0 to hide fines)
    'currency'    => 'R',
    'per_page'    => 10,   // rows per page in lists

    // Secret needed to create a new administrator account. Empty = admin self-registration disabled.
    'admin_key'   => getenv('LIB_ADMIN_KEY') ?: '',

    'login_max_attempts' => 5,    // failed logins allowed ...
    'login_window_min'   => 15,   // ... within this many minutes before a temporary lock
];

$local = __DIR__ . '/config.local.php';
if (is_file($local)) {
    $config = array_replace_recursive($config, require $local);
}
return $config;
