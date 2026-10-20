<?php
declare(strict_types=1);

function config(?string $key = null)
{
    static $config = null;
    $config ??= require __DIR__ . '/config.php';
    if ($key === null) return $config;
    $value = $config;
    foreach (explode('.', $key) as $part) {
        $value = $value[$part] ?? null;
    }
    return $value;
}

/** Escape text for safe output in HTML. */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $url): never
{
    header('Location: ' . $url);
    exit;
}

/** One-time messages shown after a redirect. */
function flash(?string $type = null, ?string $message = null): array
{
    if ($type !== null) {
        $_SESSION['flash'][] = ['type' => $type, 'message' => $message];
        return [];
    }
    $messages = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $messages;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? 'cli';
}

function now(): string   { return date('Y-m-d H:i:s'); }
function today(): string { return date('Y-m-d'); }

function format_date(?string $date): string
{
    return $date ? date('d M Y', strtotime($date)) : '—';
}

function clean_text($value): string
{
    if (!is_string($value)) return '';
    return trim(preg_replace('/\s+/', ' ', $value));
}

function valid_email(string $email): bool
{
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false && strlen($email) <= 190;
}

/**
 * Returns a list of problems with a password (empty list = acceptable).
 * Students: 8+ characters with a letter and a digit.
 * Administrators: 12+ characters with upper, lower, digit and symbol.
 */
function password_errors(string $password, bool $strict = false): array
{
    $errors = [];
    $min = $strict ? 12 : 8;
    if (strlen($password) < $min) $errors[] = "Password must be at least $min characters long.";
    if (strlen($password) > 200)  $errors[] = 'Password is too long.';
    if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors[] = 'Password must contain letters and numbers.';
    }
    if ($strict && (!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password) || !preg_match('/[^A-Za-z0-9]/', $password))) {
        $errors[] = 'Administrator passwords need upper and lower case letters, a number and a symbol.';
    }
    return $errors;
}

/** Page number from ?page=, and the matching SQL offset. */
function page_number(): int
{
    return min(100000, max(1, (int) ($_GET['page'] ?? 1)));
}

/** Pagination links that keep the current search filters. */
function pagination(int $total, int $page, int $perPage): string
{
    $pages = (int) ceil($total / $perPage);
    if ($pages <= 1) return '';
    $query = $_GET;
    $link = function (int $p, string $label, bool $disabled = false, bool $active = false) use ($query): string {
        $query['page'] = $p;
        $class = 'page-item' . ($disabled ? ' disabled' : '') . ($active ? ' active' : '');
        $href = $disabled ? '#' : '?' . http_build_query($query);
        return '<li class="' . $class . '"><a class="page-link" href="' . e($href) . '">' . $label . '</a></li>';
    };
    $html = '<nav aria-label="Pages"><ul class="pagination mb-0">';
    $html .= $link($page - 1, 'Previous', $page <= 1);
    $from = max(1, $page - 2);
    $to = min($pages, $page + 2);
    for ($p = $from; $p <= $to; $p++) $html .= $link($p, (string) $p, false, $p === $page);
    $html .= $link($page + 1, 'Next', $page >= $pages);
    return $html . '</ul></nav>';
}
