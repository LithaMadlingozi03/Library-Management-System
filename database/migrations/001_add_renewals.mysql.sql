-- Run once on an existing MySQL/MariaDB database created before renewals were added.
-- (SQLite databases are upgraded automatically.)
ALTER TABLE loans ADD COLUMN renewals TINYINT UNSIGNED NOT NULL DEFAULT 0;
