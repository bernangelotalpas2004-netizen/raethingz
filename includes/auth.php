<?php
/**
 * auth.php
 * Session management and authentication helpers.
 *
 * SECURITY PRACTICES:
 *  - session_regenerate_id() on login prevents session fixation attacks
 *  - password_hash() with BCRYPT (cost 12) for strong hashing
 *  - CSRF tokens protect forms from cross-site request forgery
 */

require_once __DIR__ . '/config.php';

// ── Start Session Securely ────────────────────────────────────────────────────
function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_name(SESSION_NAME);
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => isset($_SERVER['HTTPS']),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
}

// ── CSRF Token ─────────────────────────────────────────────────────────────────
function generateCSRF(): string {
    startSession();
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRF(string $token): bool {
    startSession();
    return isset($_SESSION['csrf_token']) &&
           hash_equals($_SESSION['csrf_token'], $token);
}

// ── Authentication Helpers ────────────────────────────────────────────────────

/** Returns true if a user is logged in */
function isLoggedIn(): bool {
    startSession();
    return !empty($_SESSION['user_id']);
}

/** Returns current user data array or null */
function currentUser(): ?array {
    startSession();
    if (empty($_SESSION['user_id'])) return null;
    return [
        'id'    => $_SESSION['user_id'],
        'name'  => $_SESSION['user_name']  ?? '',
        'email' => $_SESSION['user_email'] ?? '',
        'role'  => $_SESSION['user_role']  ?? 'customer',
    ];
}

/** Redirects to login if not authenticated */
function requireLogin(string $redirect = '../index.php'): void {
    if (!isLoggedIn()) {
        header('Location: ' . $redirect);
        exit;
    }
}

/** Destroy session on logout */
function logout(): void {
    startSession();
    $_SESSION = [];
    session_destroy();
}
