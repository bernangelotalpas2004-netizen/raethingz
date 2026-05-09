<?php
/**
 * helpers.php
 * Reusable input sanitization, validation, and response utilities.
 *
 * WHY THIS FILE EXISTS:
 *  Centralising these functions means every form handler uses the same
 *  battle-tested rules instead of reimplementing them inconsistently.
 */

// ── Sanitization ──────────────────────────────────────────────────────────────

/** Trim + strip tags from a string input */
function sanitizeString(string $value): string {
    return htmlspecialchars(strip_tags(trim($value)), ENT_QUOTES, 'UTF-8');
}

/** Sanitize an email address */
function sanitizeEmail(string $value): string {
    return filter_var(trim($value), FILTER_SANITIZE_EMAIL);
}

/** Cast and validate an integer */
function sanitizeInt(mixed $value): int {
    return (int) filter_var($value, FILTER_SANITIZE_NUMBER_INT);
}

// ── Validation ─────────────────────────────────────────────────────────────────

/** Returns true if string is a valid email */
function isValidEmail(string $email): bool {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/** Returns true if PH mobile number: starts with 09, 11 digits */
function isValidPHPhone(string $phone): bool {
    $cleaned = preg_replace('/[\s\-]/', '', $phone);
    return (bool) preg_match('/^(09|\+639)\d{9}$/', $cleaned);
}

/** Returns true if string length is within bounds */
function isLength(string $value, int $min = 1, int $max = 255): bool {
    $len = mb_strlen(trim($value));
    return $len >= $min && $len <= $max;
}

// ── JSON Responses ─────────────────────────────────────────────────────────────

/**
 * Send a JSON response and exit.
 *
 * @param bool   $success
 * @param string $message Human-readable message
 * @param array  $data    Optional payload
 * @param int    $code    HTTP status code
 */
function jsonResponse(bool $success, string $message, array $data = [], int $code = 200): never {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data'    => $data,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ── File Upload Validation ─────────────────────────────────────────────────────

/**
 * Validate an uploaded file.
 *
 * @param array  $file    Entry from $_FILES
 * @param int    $maxSize Max bytes
 * @param array  $allowed Allowed MIME types
 * @return array ['valid' => bool, 'error' => string]
 */
function validateUpload(array $file, int $maxSize, array $allowed): array {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'error' => 'File upload failed (error code ' . $file['error'] . ').'];
    }
    if ($file['size'] > $maxSize) {
        return ['valid' => false, 'error' => 'File too large. Maximum size is ' . ($maxSize / 1048576) . ' MB.'];
    }

    // Use finfo for reliable MIME detection (not the client-supplied type)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);

    if (!in_array($mime, $allowed, true)) {
        return ['valid' => false, 'error' => 'Invalid file type. Allowed: ' . implode(', ', $allowed)];
    }

    return ['valid' => true, 'error' => ''];
}
