-- MySQL / MariaDB version of the schema. Create the database first:
--   CREATE DATABASE lms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
--   USE lms;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    student_number VARCHAR(40) NOT NULL UNIQUE,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    phone VARCHAR(40) NOT NULL DEFAULT '',
    program VARCHAR(120) NOT NULL DEFAULT '',
    password_hash VARCHAR(255) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    must_change_password TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS admins (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_id VARCHAR(40) NOT NULL UNIQUE,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    mobile VARCHAR(40) NOT NULL DEFAULT '',
    department VARCHAR(120) NOT NULL DEFAULT 'Library Services',
    password_hash VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS books (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    author VARCHAR(190) NOT NULL,
    isbn VARCHAR(30) NOT NULL DEFAULT '',
    year SMALLINT,
    category VARCHAR(80) NOT NULL DEFAULT 'General',
    copies INT UNSIGNED NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS loans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    book_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    issued_by INT UNSIGNED NULL,
    issued_at DATE NOT NULL,
    due_at DATE NOT NULL,
    returned_at DATETIME NULL,
    renewals TINYINT UNSIGNED NOT NULL DEFAULT 0,
    INDEX idx_loans_book (book_id, returned_at),
    INDEX idx_loans_user (user_id, returned_at),
    FOREIGN KEY (book_id) REFERENCES books(id),
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (issued_by) REFERENCES admins(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS login_attempts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    attempt_key VARCHAR(255) NOT NULL,
    attempted_at DATETIME NOT NULL,
    INDEX idx_attempts (attempt_key, attempted_at)
) ENGINE=InnoDB;
