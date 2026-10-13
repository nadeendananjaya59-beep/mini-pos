<?php
/**
 * Authentication helpers: session start, timeout, role checks, CSRF, flash messages.
 * Include this file at the top of every page.
 */

require_once __DIR__ . '/../config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    // Harden the session cookie.
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/** Escape output (XSS protection). */
function e($value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

/** Redirect helper. */
function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

/** Is a user logged in (and not timed out)? */
function isLoggedIn(): bool
{
    if (empty($_SESSION['user_id'])) {
        return false;
    }

    // Inactivity timeout.
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_TIMEOUT) {
        $_SESSION = [];
        session_destroy();
        return false;
    }

    $_SESSION['last_activity'] = time();
    return true;
}

/** Block guests. */
function requireLogin(): void
{
    if (!isLoggedIn()) {
        // Restart a clean session so the flash message can be stored.
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Please log in to continue (session may have expired).'];
        redirect('index.php');
    }
}

/** Block everyone except admins. */
function requireAdmin(): void
{
    requireLogin();
    if (($_SESSION['role'] ?? '') !== 'admin') {
        http_response_code(403);
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Access denied: administrators only.'];
        redirect('dashboard.php');
    }
}

function isAdmin(): bool
{
    return ($_SESSION['role'] ?? '') === 'admin';
}

/** Store a one-time message shown on the next page. */
function flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/** Read & clear the flash message. */
function takeFlash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/** CSRF token for forms. */
function csrfToken(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrfField(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrfToken()) . '">';
}

/** Validate CSRF token on POST requests; aborts on failure. */
function verifyCsrf(): void
{
    $sent = $_POST['csrf'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if (!hash_equals($_SESSION['csrf'] ?? '', (string) $sent)) {
        http_response_code(400);
        exit('Invalid request token. Please go back, refresh and try again.');
    }
}

/** Format money. */
function money($amount): string
{
    return CURRENCY . ' ' . number_format((float) $amount, 2);
}
