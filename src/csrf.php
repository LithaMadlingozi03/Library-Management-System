<?php
declare(strict_types=1);

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

/** Call at the top of any code that handles a POST. Stops the request if the token is wrong. */
function csrf_check(): void
{
    $sent = $_POST['csrf'] ?? '';
    $expected = $_SESSION['csrf'] ?? '';
    // An empty expected token (no session yet) must never match an empty submitted one.
    if (!is_string($sent) || $expected === '' || !hash_equals($expected, $sent)) {
        http_response_code(419);
        exit('Your session expired or the request was not valid. Go back, refresh the page and try again.');
    }
}

function is_post(): bool
{
    return ($_SERVER['REQUEST_METHOD'] ?? '') === 'POST';
}
