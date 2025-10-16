<?php

declare(strict_types=1);

const DB_PATH = __DIR__ . '/app.sqlite';

if (file_exists(DB_PATH)) {
    unlink(DB_PATH);
}

$pdo = new PDO('sqlite:' . DB_PATH);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$pdo->exec('PRAGMA foreign_keys = ON');

$pdo->exec(<<<SQL
CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL CHECK(role IN ("admin", "instructeur")),
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)
SQL);

$pdo->exec(<<<SQL
CREATE TABLE students (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    phone TEXT NOT NULL,
    vehicle TEXT,
    package TEXT,
    location TEXT,
    description TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)
SQL);

$pdo->exec(<<<SQL
CREATE TABLE events (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    instructor_id INTEGER NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    student_id INTEGER NOT NULL REFERENCES students(id) ON DELETE CASCADE,
    start_time TEXT NOT NULL,
    end_time TEXT NOT NULL,
    vehicle TEXT,
    package TEXT,
    location TEXT,
    notes TEXT,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
)
SQL);

echo "Database migrated to " . DB_PATH . PHP_EOL;
