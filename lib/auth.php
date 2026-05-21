<?php
declare(strict_types=1);
if (session_status() === PHP_SESSION_NONE) session_start();

function current_user(): ?array {
    if (empty($_SESSION['loggedin'])) return null;
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) {
        header('Location: ' . base_url() . 'login.php');
        exit;
    }
}

function base_url(): string {
    $script = $_SERVER['SCRIPT_NAME'] ?? '';
    $dir = rtrim(dirname($script), '/');
    return $dir . '/';
}
