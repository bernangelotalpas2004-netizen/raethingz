<?php

require_once '../includes/config.php';
require_once '../includes/db.php';
require_once '../includes/auth.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    jsonResponse(false, 'Method not allowed.', [], 405);
}

$body = json_decode(file_get_contents('php://input'), true) ?? [];

$csrfToken = $body['csrf_token'] ?? '';
$orderId   = (int) ($body['order_id'] ?? 0);

// ── CSRF ───────────────────────────────────────────────────────────────────────
if (!verifyCSRF($csrfToken)) {
    jsonResponse(false, 'Invalid request. Please refresh the page.', [], 403);
}

if ($orderId <= 0) {
    jsonResponse(false, 'Invalid order.', [], 400);
}

try {
    $pdo = getDB();
    startSession();
    
    $userId = isLoggedIn() ? (int) $_SESSION['user_id'] : null;
    if (!$userId) {
        jsonResponse(false, 'You must be logged in.', [], 401);
    }

    // Check the order belongs to the user and is still pending
    $checkStmt = $pdo->prepare(
        'SELECT order_id, status FROM orders WHERE order_id = :oid AND user_id = :uid'
    );
    $checkStmt->execute([':oid' => $orderId, ':uid' => $userId]);
    $order = $checkStmt->fetch();

    if (!$order) {
        jsonResponse(false, 'Order not found.', [], 404);
    }

    if ($order['status'] !== 'pending') {
        jsonResponse(false, 'Only pending orders can be cancelled.', [], 400);
    }

    // Begin transaction: cancel order + restore stock
    $pdo->beginTransaction();

    // Get order items to restore stock
    $itemsStmt = $pdo->prepare(
        'SELECT product_id, quantity FROM order_items WHERE order_id = :oid'
    );
    $itemsStmt->execute([':oid' => $orderId]);
    $orderItems = $itemsStmt->fetchAll();

    // Restore stock for each item
    $restoreStmt = $pdo->prepare(
        'UPDATE stock SET quantity = quantity + :qty WHERE product_id = :pid'
    );

    foreach ($orderItems as $item) {
        $restoreStmt->execute([
            ':qty' => (int) $item['quantity'],
            ':pid' => (int) $item['product_id'],
        ]);
    }

    // Update order status to cancelled
    $cancelStmt = $pdo->prepare(
        'UPDATE orders SET status = :status WHERE order_id = :oid'
    );
    $cancelStmt->execute([
        ':status' => 'cancelled',
        ':oid'    => $orderId,
    ]);

    $pdo->commit();

    jsonResponse(true, "Order #$orderId has been cancelled successfully.", [
        'order_id' => $orderId,
        'status'   => 'cancelled',
    ]);

} catch (\Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('[cancel_order Error] ' . $e->getMessage());
    jsonResponse(false, 'A server error occurred. Please try again.', [], 500);
}