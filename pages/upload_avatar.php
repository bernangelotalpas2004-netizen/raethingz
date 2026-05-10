<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

startSession();
requireLogin('../pages/login.php');

header('Content-Type: application/json; charset=UTF-8');

// ── Validate CSRF ─────────────────────────────────────────────────────────────
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// Only accept JSON requests (no multipart/form-data via JSON)
// We'll handle the file upload via a separate FormData request using POST.
// If content-type is multipart/form-data, we read $_FILES directly.
$isFormData = strpos($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data') !== false;

if ($isFormData) {
    // FormData upload (AJAX from JS)
    $csrf = $_POST['csrf_token'] ?? '';
} else {
    $csrf = $input['csrf_token'] ?? '';
}

if (!verifyCSRF($csrf)) {
    jsonResponse(false, 'Invalid security token. Please refresh the page.', [], 403);
}

$user = currentUser();
if (!$user) {
    jsonResponse(false, 'You must be logged in.', [], 401);
}

// ── Handle different actions ──────────────────────────────────────────────────
$action = $isFormData ? ($_POST['action'] ?? 'upload') : ($input['action'] ?? 'upload');

if ($action === 'remove') {
    // Remove profile picture
    try {
        $pdo = getDB();

        // Get current pic to delete the file
        $stmt = $pdo->prepare('SELECT profile_pic FROM users WHERE user_id = :uid');
        $stmt->execute([':uid' => $user['id']]);
        $row = $stmt->fetch();

        if ($row && $row['profile_pic']) {
            $filePath = __DIR__ . '/../' . $row['profile_pic'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $pdo->prepare('UPDATE users SET profile_pic = NULL WHERE user_id = :uid');
        $stmt->execute([':uid' => $user['id']]);

        // Update session
        $_SESSION['user_profile_pic'] = '';

        jsonResponse(true, 'Profile picture removed.');
    } catch (PDOException $e) {
        error_log('[Avatar Remove] ' . $e->getMessage());
        jsonResponse(false, 'Database error. Please try again.', [], 500);
    }
}

// ── Upload ────────────────────────────────────────────────────────────────────
if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
    $errCode = $_FILES['avatar']['error'] ?? -1;
    jsonResponse(false, 'No file uploaded or upload error (code: ' . $errCode . ').', [], 400);
}

$file = $_FILES['avatar'];

// Validate
$validation = validateUpload($file, UPLOAD_MAX_SIZE, ['image/jpeg', 'image/png', 'image/webp']);
if (!$validation['valid']) {
    jsonResponse(false, $validation['error'], [], 400);
}

// Generate unique filename
$extMap = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$ext   = $extMap[finfo_file(new finfo(FILEINFO_MIME_TYPE), $file['tmp_name'])] ?? 'jpg';
$filename = 'avatar_' . $user['id'] . '_' . time() . '.' . $ext;

$uploadDir = __DIR__ . '/../uploads/avatars/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destPath = $uploadDir . $filename;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    jsonResponse(false, 'Failed to save file. Please try again.', [], 500);
}

// Delete old avatar if exists
try {
    $pdo = getDB();
    $stmt = $pdo->prepare('SELECT profile_pic FROM users WHERE user_id = :uid');
    $stmt->execute([':uid' => $user['id']]);
    $row = $stmt->fetch();

    if ($row && $row['profile_pic']) {
        $oldPath = __DIR__ . '/../' . $row['profile_pic'];
        if ($oldPath !== $destPath && file_exists($oldPath)) {
            unlink($oldPath);
        }
    }

    // Update DB
    $relativePath = 'uploads/avatars/' . $filename;
    $stmt = $pdo->prepare('UPDATE users SET profile_pic = :pic WHERE user_id = :uid');
    $stmt->execute([':pic' => $relativePath, ':uid' => $user['id']]);

    // Update session
    $_SESSION['user_profile_pic'] = $relativePath;

    jsonResponse(true, 'Profile picture updated!', ['path' => $relativePath]);
} catch (PDOException $e) {
    error_log('[Avatar Upload DB] ' . $e->getMessage());
    jsonResponse(false, 'Database error. Please try again.', [], 500);
}