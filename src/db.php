<?php
declare(strict_types=1);

/** Shared database connection (PDO). SQLite is created and set up automatically on first use. */
function db(): PDO
{
    static $pdo = null;
    if ($pdo) return $pdo;

    $cfg = config('db');
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];

    if ($cfg['driver'] === 'mysql') {
        $dsn = "mysql:host={$cfg['host']};port={$cfg['port']};dbname={$cfg['name']};charset=utf8mb4";
        $pdo = new PDO($dsn, $cfg['user'], $cfg['pass'], $options);
        return $pdo;
    }

    $path = $cfg['sqlite_path'];
    $isNew = $path === ':memory:' || !is_file($path) || filesize($path) === 0;
    $pdo = new PDO('sqlite:' . $path, null, null, $options);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA busy_timeout = 5000');
    if ($isNew) {
        $pdo->exec(file_get_contents(APP_ROOT . '/database/schema.sqlite.sql'));
    } else {
        // Upgrade databases created before renewals existed.
        $cols = array_column($pdo->query('PRAGMA table_info(loans)')->fetchAll(), 'name');
        if ($cols && !in_array('renewals', $cols, true)) {
            $pdo->exec('ALTER TABLE loans ADD COLUMN renewals INTEGER NOT NULL DEFAULT 0');
        }
    }
    return $pdo;
}

function db_driver(): string
{
    return config('db.driver') === 'mysql' ? 'mysql' : 'sqlite';
}

/** Runs a query and returns every row. */
function db_all(string $sql, array $params = []): array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function db_one(string $sql, array $params = []): ?array
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $row = $stmt->fetch();
    return $row === false ? null : $row;
}

function db_value(string $sql, array $params = [])
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    $value = $stmt->fetchColumn();
    return $value === false ? null : $value;
}

function db_run(string $sql, array $params = []): int
{
    $stmt = db()->prepare($sql);
    $stmt->execute($params);
    return $stmt->rowCount();
}

/** Starts a transaction that takes the write lock immediately (avoids two people grabbing the last copy). */
function db_begin_write(): void
{
    if (db_driver() === 'sqlite') db()->exec('BEGIN IMMEDIATE');
    else db()->beginTransaction();
}

function db_commit(): void
{
    if (db_driver() === 'sqlite') db()->exec('COMMIT');
    else db()->commit();
}

function db_rollback(): void
{
    if (db_driver() === 'sqlite') db()->exec('ROLLBACK');
    elseif (db()->inTransaction()) db()->rollBack();
}
