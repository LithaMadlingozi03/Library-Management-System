CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    student_number TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    phone TEXT NOT NULL DEFAULT '',
    program TEXT NOT NULL DEFAULT '',
    password_hash TEXT NOT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    must_change_password INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS admins (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    employee_id TEXT NOT NULL UNIQUE,
    full_name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    mobile TEXT NOT NULL DEFAULT '',
    department TEXT NOT NULL DEFAULT 'Library Services',
    password_hash TEXT NOT NULL,
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS books (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    title TEXT NOT NULL,
    author TEXT NOT NULL,
    isbn TEXT NOT NULL DEFAULT '',
    year INTEGER,
    category TEXT NOT NULL DEFAULT 'General',
    copies INTEGER NOT NULL DEFAULT 1 CHECK (copies >= 0),
    created_at TEXT NOT NULL
);

CREATE TABLE IF NOT EXISTS loans (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    book_id INTEGER NOT NULL REFERENCES books(id),
    user_id INTEGER NOT NULL REFERENCES users(id),
    issued_by INTEGER REFERENCES admins(id),
    issued_at TEXT NOT NULL,
    due_at TEXT NOT NULL,
    returned_at TEXT,
    renewals INTEGER NOT NULL DEFAULT 0
);

CREATE TABLE IF NOT EXISTS login_attempts (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    attempt_key TEXT NOT NULL,
    attempted_at TEXT NOT NULL
);

CREATE INDEX IF NOT EXISTS idx_loans_book ON loans(book_id, returned_at);
CREATE INDEX IF NOT EXISTS idx_loans_user ON loans(user_id, returned_at);
CREATE INDEX IF NOT EXISTS idx_attempts ON login_attempts(attempt_key, attempted_at);
