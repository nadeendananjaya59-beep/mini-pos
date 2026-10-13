<?php
/**
 * Logout: destroy the session completely and return to login.
 */
require_once __DIR__ . '/includes/auth.php';

$_SESSION = [];

if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
}

session_destroy();

// Start a fresh session just to show the "logged out" message.
session_start();
flash('success', 'You have been logged out.');
redirect('index.php');
