<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

// ── Ensure clean JSON output — catch fatal errors ────────────────────
register_shutdown_function(function () {
    $err = error_get_last();
    if ($err && in_array($err['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        http_response_code(500);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode([
            'success' => false,
            'message' => DEBUG_MODE
                ? "Fatal: {$err['message']} in {$err['file']}:{$err['line']}"
                : 'A server error occurred. Your order was not placed.',
        ]);
    }
});

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$csrfToken = $body['csrf_token'] ?? '';
$name      = sanitizeString($body['name']    ?? '');
$address   = sanitizeString($body['address'] ?? '');
$contact   = sanitizeString($body['contact'] ?? '');
$items     = $body['items'] ?? [];   // [{ id, qty, price, name }, ...]

// ── CSRF ───────────────────────────────────────────────────────────────────────
if (!verifyCSRF($csrfToken)) {
    jsonResponse(false, 'Invalid request. Please refresh the page.', [], 403);
}

// ── Validate Fields ────────────────────────────────────────────────────────────
$errors = [];

if (!isLength($name, 2, 100))      $errors['name']    = 'Please enter your full name.';
if (!isLength($address, 5, 500))   $errors['address'] = 'Please enter a valid delivery address.';
if (!isValidPHPhone($contact))     $errors['contact'] = 'Please enter a valid PH mobile number (e.g. 09XX-XXX-XXXX).';

if (empty($items) || !is_array($items)) {
    jsonResponse(false, 'Your cart is empty.', [], 400);
}

if (!empty($errors)) {
    jsonResponse(false, 'Please fix the errors below.', ['errors' => $errors], 422);
}

// ── Validate & Price Cart Server-Side ─────────────────────────────────────────
// WHY: We NEVER trust prices sent from the client — recalculate from DB
try {
    $pdo = getDB();

    $placeholders = implode(',', array_fill(0, count($items), '?'));
    $productIds   = array_map(fn($i) => (int)($i['id'] ?? 0), $items);

    // Rebuild priced items using DB prices, reject unknown products
    $priceStmt = $pdo->prepare(
        "SELECT p.product_id AS id, p.product_name AS name, p.price, s.quantity
         FROM   products p
         LEFT JOIN stock s ON s.product_id = p.product_id
         WHERE  p.product_id IN ($placeholders) AND p.is_active = 1"
    );
    $priceStmt->execute($productIds);
    $dbRows = [];
    while ($row = $priceStmt->fetch()) {
        $dbRows[$row['id']] = $row;
    }

    $validatedItems = [];
    $total = 0.0;

    foreach ($items as $item) {
        $id  = (int) ($item['id']  ?? 0);
        $qty = (int) ($item['qty'] ?? 1);

        if ($qty <= 0 || !isset($dbRows[$id])) {
            jsonResponse(false, 'One or more cart items are no longer available.', [], 400);
        }

        $dbRow = $dbRows[$id];

        if ($dbRow['quantity'] !== null && $dbRow['quantity'] < $qty) {
            jsonResponse(false, "Sorry, \"{$dbRow['name']}\" only has {$dbRow['quantity']} left in stock.", [], 409);
        }

        $linePrice = (float) $dbRow['price'] * $qty;
        $total    += $linePrice;

        $validatedItems[] = [
            'id'    => $id,
            'name'  => $dbRow['name'],
            'price' => (float) $dbRow['price'],
            'qty'   => $qty,
        ];
    }

    // ── Transaction: Insert Order + Items + Update Stock ──────────────────────
    $pdo->beginTransaction();

    startSession();
    $userId = isLoggedIn() ? (int) $_SESSION['user_id'] : null;

    // Insert order
    $orderStmt = $pdo->prepare(
        'INSERT INTO orders (user_id, customer_name, address, contact, total_amount, status)
         VALUES (:uid, :name, :address, :contact, :total, :status)'
    );
    $orderStmt->execute([
        ':uid'     => $userId,
        ':name'    => $name,
        ':address' => $address,
        ':contact' => $contact,
        ':total'   => $total,
        ':status'  => 'pending',
    ]);

    $orderId = (int) $pdo->lastInsertId();

    // Insert order items + decrement stock
    $itemStmt  = $pdo->prepare(
        'INSERT INTO order_items (order_id, product_id, quantity, price_at_time)
         VALUES (:oid, :pid, :qty, :price)'
    );
    $stockStmt = $pdo->prepare(
        'UPDATE stock SET quantity = quantity - :qty WHERE product_id = :pid AND quantity >= :qty_check'
    );

    foreach ($validatedItems as $vi) {
        $itemStmt->execute([
            ':oid'   => $orderId,
            ':pid'   => $vi['id'],
            ':qty'   => $vi['qty'],
            ':price' => $vi['price'],
        ]);

        // Only update stock for products that track it
        $stockStmt->execute([':qty' => $vi['qty'], ':qty_check' => $vi['qty'], ':pid' => $vi['id']]);
    }

    $pdo->commit();

    jsonResponse(true, "Order #$orderId placed successfully! Thank you, $name 🎉", [
        'order_id' => $orderId,
        'total'    => $total,
    ]);

} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[place_order Error] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    // Return the actual error in debug mode, generic message otherwise
    $msg = defined('DEBUG_MODE') && DEBUG_MODE
        ? $e->getMessage()
        : 'A server error occurred. Your order was not placed.';
    jsonResponse(false, $msg, ['file' => $e->getFile(), 'line' => $e->getLine()], 500);
}
