<?php
/**
 * submit_custom.php — handles customization form submissions
 *
 * Accepts multipart/form-data (because of optional file upload).
 * Saves request to DB; optionally saves uploaded image to /uploads.
 *
 * SECURITY:
 *  - CSRF verified via POST field
 *  - finfo used for real MIME detection (not client-supplied type)
 *  - Random filename prevents path traversal / overwrite attacks
 *  - Prepared statements
 */

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

// CSRF from regular form POST (not JSON body)
$csrfToken  = sanitizeString($_POST['csrf_token'] ?? '');
$name        = sanitizeString($_POST['name']        ?? '');
$description = sanitizeString($_POST['description'] ?? '');
$colors      = sanitizeString($_POST['colors']      ?? '');
$materials   = sanitizeString($_POST['materials']   ?? '');
$contact     = sanitizeString($_POST['contact']     ?? '');

// ── CSRF ───────────────────────────────────────────────────────────────────────
if (!verifyCSRF($csrfToken)) {
    jsonResponse(false, 'Invalid request. Please refresh and try again.', [], 403);
}

// ── Validate ───────────────────────────────────────────────────────────────────
$errors = [];

if (!isLength($name, 2, 100))        $errors['name']        = 'Please enter your name.';
if (!isLength($description, 10, 1000)) $errors['description'] = 'Please describe your design idea (min 10 characters).';
if (!isLength($colors, 2, 255))      $errors['colors']      = 'Please specify your colour preference.';

if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors], 422);
}

// ── Handle Optional File Upload ────────────────────────────────────────────────
$imagePath = null;

if (!empty($_FILES['image']['name'])) {
    $file       = $_FILES['image'];
    $validation = validateUpload($file, UPLOAD_MAX_SIZE, ALLOWED_TYPES);

    if (!$validation['valid']) {
        jsonResponse(false, $validation['error'], [], 400);
    }

    // Generate safe random filename
    $ext       = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $filename  = bin2hex(random_bytes(16)) . '.' . $ext;
    $destPath  = UPLOAD_DIR . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        jsonResponse(false, 'File upload failed. Please try again.', [], 500);
    }

    $imagePath = 'uploads/' . $filename;
}

// ── Insert into DB ─────────────────────────────────────────────────────────────
try {
    startSession();
    $userId = isLoggedIn() ? (int) $_SESSION['user_id'] : null;

    $pdo  = getDB();
    $stmt = $pdo->prepare(
        'INSERT INTO custom_requests
             (user_id, name, description, colors, materials, contact, image_path, status)
         VALUES
             (:uid, :name, :desc, :colors, :materials, :contact, :image, :status)'
    );
    $stmt->execute([
        ':uid'       => $userId,
        ':name'      => $name,
        ':desc'      => $description,
        ':colors'    => $colors,
        ':materials' => $materials ?: null,
        ':contact'   => $contact   ?: null,
        ':image'     => $imagePath,
        ':status'    => 'pending',
    ]);

    jsonResponse(true, 'Your customization request has been received! We\'ll be in touch within 24–48 hours. ✨');

} catch (PDOException $e) {
    error_log('[submit_custom Error] ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.', [], 500);
}
