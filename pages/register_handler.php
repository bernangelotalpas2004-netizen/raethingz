<?php


require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$csrfToken       = $body['csrf_token']       ?? '';
$fullName        = sanitizeString($body['full_name']       ?? '');
$email           = sanitizeEmail($body['email']            ?? '');
$password        = $body['password']         ?? '';
$confirmPassword = $body['confirm_password'] ?? '';

// ── CSRF ───────────────────────────────────────────────────────────────────────
if (!verifyCSRF($csrfToken)) {
    jsonResponse(false, 'Invalid request. Please refresh and try again.', [], 403);
}

// ── Validate ───────────────────────────────────────────────────────────────────
$errors = [];

if (!isLength($fullName, 2, 100)) {
    $errors['full_name'] = 'Full name must be between 2 and 100 characters.';
}

if (empty($email) || !isValidEmail($email)) {
    $errors['email'] = 'Please enter a valid email address.';
}

// Password rules: minimum 8 chars, at least one letter and one number
if (strlen($password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters.';
} elseif (!preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
    $errors['password'] = 'Password must contain at least one letter and one number.';
}

if ($password !== $confirmPassword) {
    $errors['confirm_password'] = 'Passwords do not match.';
}

if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors], 422);
}

// ── Check Email Uniqueness ─────────────────────────────────────────────────────
try {
    $pdo  = getDB();
    $stmt = $pdo->prepare('SELECT user_id FROM users WHERE email = :email LIMIT 1');
    $stmt->execute([':email' => $email]);

    if ($stmt->fetch()) {
        jsonResponse(false, 'That email is already registered. Try logging in.', [
            'errors' => ['email' => 'Email already in use.']
        ], 409);
    }

    // ── Insert User ────────────────────────────────────────────────────────────
    $hashed = password_hash($password, HASH_ALGO, ['cost' => HASH_COST]);

    $insert = $pdo->prepare(
        'INSERT INTO users (full_name, email, password, role)
         VALUES (:name, :email, :password, :role)'
    );
    $insert->execute([
        ':name'     => $fullName,
        ':email'    => $email,
        ':password' => $hashed,
        ':role'     => 'customer',
    ]);

    $newUserId = (int) $pdo->lastInsertId();

} catch (PDOException $e) {
    error_log('[Register DB Error] ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.', [], 500);
}

// ── Auto-Login After Registration ──────────────────────────────────────────────
startSession();
session_regenerate_id(true);

$_SESSION['user_id']    = $newUserId;
$_SESSION['user_name']  = $fullName;
$_SESSION['user_email'] = $email;
$_SESSION['user_role']  = 'customer';

jsonResponse(true, 'Account created! Welcome to Raethingz 🎉', [
    'name'     => $fullName,
    'redirect' => '../index.php',
]);
