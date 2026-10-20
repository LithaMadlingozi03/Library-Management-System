<?php
declare(strict_types=1);

// Loaded at the top of every page.
define('APP_ROOT', dirname(__DIR__));

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/library.php';
require_once __DIR__ . '/layout.php';

date_default_timezone_set((string) config('timezone'));

if (PHP_SAPI !== 'cli') {
    // No page uses array inputs (?x[]=1), so drop them instead of letting them cause type errors.
    $_GET  = array_filter($_GET, 'is_string');
    $_POST = array_filter($_POST, 'is_string');

    // Never show PHP errors or stack traces to visitors; log them and show a plain message instead.
    ini_set('display_errors', '0');
    set_exception_handler(function (Throwable $e): void {
        error_log('Library app error: ' . $e);
        if (!headers_sent()) http_response_code(500);
        echo '<!DOCTYPE html><meta charset="utf-8"><title>Something went wrong</title>'
            . '<body style="font-family:sans-serif;max-width:32rem;margin:4rem auto"><h1>Something went wrong</h1>'
            . '<p>The page could not be loaded. Please go back and try again.</p></body>';
    });

    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('Referrer-Policy: same-origin');
    session_boot();
}
