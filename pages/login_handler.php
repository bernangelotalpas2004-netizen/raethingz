<?php
/**
 * login.php — handles POST login requests (JSON API)
 *
 * SECURITY PRACTICES:
 *  1. CSRF token verification
 *  2. Input sanitization before any DB query
 *  3. Prepared statements (PDO) — no SQL injection possible
 *  4. password_verify() — constant-time comparison
 *  5. session_regenerate_id() after login — prevents session fixation
 *  6. Generic error message — does NOT reveal whether email exists
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

// Parse JSON body (sent by fetch() in script.js)
$body = json_decode(file_get_contents('php://input'), true) ?? [];

$csrfToken = $body['csrf_token'] ?? '';
$email     = sanitizeEmail($body['email']    ?? '');
$password  = $body['password'] ?? '';   // Raw — will be verified, not stored

// ── Validate ───────────────────────────────────────────────────────────────────
$errors = [];

if (!verifyCSRF($csrfToken)) {
    jsonResponse(false, 'Invalid request. Please refresh and try again.', [], 403);
}

if (empty($email) || !isValidEmail($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}

if (empty($password)) {
    $errors['password'] = 'Password is required.';
}

if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors], 422);
}

// ── Query DB ───────────────────────────────────────────────────────────────────
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'SELECT user_id, full_name, email, password, role, is_active
         FROM users
         WHERE email = :email
         LIMIT 1'
    );
    $stmt->execute([':email' => $email]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('[Login DB Error] ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.', [], 500);
}

// ── Verify Credentials ─────────────────────────────────────────────────────────
// Generic message: attacker cannot know if the email is registered
if (!$user || !password_verify($password, $user['password'])) {
    jsonResponse(false, 'Incorrect email or password.', [], 401);
}

if (!$user['is_active']) {
    jsonResponse(false, 'Your account has been disabled. Please contact support.', [], 403);
}

// ── Create Session ─────────────────────────────────────────────────────────────
startSession();
session_regenerate_id(true); // Prevent session fixation

$_SESSION['user_id']    = $user['user_id'];
$_SESSION['user_name']  = $user['full_name'];
$_SESSION['user_email'] = $user['email'];
$_SESSION['user_role']  = $user['role'];

jsonResponse(true, 'Login successful! Redirecting...', [
    'name'     => $user['full_name'],
    'role'     => $user['role'],
    'redirect' => '../index.php',
]);
