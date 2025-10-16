<?php

declare(strict_types=1);

session_start();

const DB_PATH = __DIR__ . '/../database/app.sqlite';

function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        if (!file_exists(DB_PATH)) {
            throw new RuntimeException('Database not found. Run `php database/migrate.php` followed by `php database/seed.php`.');
        }

        $pdo = new PDO('sqlite:' . DB_PATH);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    }

    return $pdo;
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_auth(): void
{
    if (!current_user()) {
        header('Location: /login');
        exit;
    }
}

function is_admin(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && $user['role'] === 'admin';
}

function is_instructor(?array $user = null): bool
{
    $user = $user ?? current_user();
    return $user && $user['role'] === 'instructeur';
}

function sanitize_string(string $value): string
{
    return trim(filter_var($value, FILTER_UNSAFE_RAW, FILTER_FLAG_STRIP_LOW));
}

function json_response(array $payload, int $code = 200): void
{
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}
