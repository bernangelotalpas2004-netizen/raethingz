<?php
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
header('Content-Type: application/json; charset=UTF-8');

try {
    require_once 'includes/config.php';
    require_once 'includes/db.php';
    require_once 'includes/auth.php';
    require_once 'includes/helpers.php';
    
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    
    // Test DB connection
    $pdo = getDB();
    
    // Test items parsing (just use sample)
    $items = $body['items'] ?? [];
    if (empty($items)) {
        echo json_encode(['success' => false, 'message' => 'No items']);
        exit;
    }
    
    $placeholders = implode(',', array_fill(0, count($items), '?'));
    $productIds = array_map(fn($i) => (int)($i['id'] ?? 0), $items);
    
    $stmt = $pdo->prepare(
        "SELECT p.product_id AS id, p.product_name AS name, p.price, s.quantity
         FROM products p
         LEFT JOIN stock s ON s.product_id = p.product_id
         WHERE p.product_id IN ($placeholders) AND p.is_active = 1"
    );
    $stmt->execute($productIds);
    
    $rows = [];
    while ($row = $stmt->fetch()) {
        $rows[] = $row;
    }
    
    echo json_encode(['success' => true, 'products' => $rows, 'product_ids' => $productIds]);
    
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}