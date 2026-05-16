<?php
function log_error(string $msg): void {
    error_log(date('[Y-m-d H:i:s] ') . $msg . PHP_EOL, 3, __DIR__ . '/../../logs/app.log');
}

function escape(string $val): string {
    return htmlspecialchars($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
}

function image_url(?string $path): string {
    if ($path === null || $path === '') return '';
    if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) return $path;
    return '/' . $path;
}

function redirect(string $url): void {
    header("Location: $url");
    exit;
}

function require_login(): void {
    if (empty($_SESSION['user_id'])) {
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please log in to continue.'];
        redirect('/app/auth/login.php');
    }
}

function require_admin(): void {
    require_login();
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        $_SESSION['flash'] = ['type' => 'error', 'message' => 'Admin access required.'];
        redirect('/index.php');
    }
}

function old(string $key, string $default = ''): string {
    return $_SESSION['_old'][$key] ?? $default;
}

function csrf_token(): string {
    if (empty($_SESSION['_csrf'])) {
        $_SESSION['_csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['_csrf'];
}

function verify_csrf(string $token): bool {
    return hash_equals($_SESSION['_csrf'] ?? '', $token);
}
