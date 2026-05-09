<?php
/**
 * fetch_products.php — Returns product data as JSON
 *
 * WHY A SEPARATE API ENDPOINT?
 *  Previously products were hardcoded in JavaScript.
 *  Now the JS fetches from this PHP file, which reads from the DB.
 *  This means adding/editing products only requires a DB change — no code edits.
 *
 * QUERY PARAMS (all optional):
 *  ?category=Flower   → filter by category slug
 *  ?search=rose       → search name/description
 *  ?featured=1        → return only bestseller/new items (for home page)
 *
 * SECURITY:
 *  - Only SELECT, no user input goes into raw SQL (prepared statements)
 *  - Output escaped by json_encode
 */

require_once '../includes/db.php';
require_once '../includes/helpers.php';

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: public, max-age=60'); // Light cache (1 min)

$category = sanitizeString($_GET['category'] ?? '');
$search   = sanitizeString($_GET['search']   ?? '');
$featured = !empty($_GET['featured']);

try {
    $pdo = getDB();

    // Build query dynamically but safely with bound params
    $sql = '
        SELECT
            p.product_id   AS id,
            p.product_name AS name,
            p.description,
            p.price,
            p.image_path   AS image,
            p.badge,
            c.category_name AS category,
            s.quantity
        FROM   products  p
        JOIN   categories c ON c.category_id = p.category_id
        LEFT JOIN stock   s ON s.product_id  = p.product_id
        WHERE  p.is_active = 1
    ';

    $params = [];

    if ($category !== '' && $category !== 'all') {
        $sql .= ' AND c.slug = :category';
        $params[':category'] = strtolower($category);
    }

    if ($search !== '') {
        $sql .= ' AND (p.product_name LIKE :search OR p.description LIKE :search OR c.category_name LIKE :search)';
        $params[':search'] = '%' . $search . '%';
    }

    if ($featured) {
        $sql .= " AND p.badge IN ('Bestseller','New')";
    }

    $sql .= ' ORDER BY p.created_at DESC';

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $products = $stmt->fetchAll();

    // Format prices as floats
    foreach ($products as &$p) {
        $p['price']    = (float) $p['price'];
        $p['quantity'] = (int)   ($p['quantity'] ?? 0);
    }
    unset($p);

    echo json_encode(['success' => true, 'data' => $products], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    error_log('[fetch_products Error] ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Could not load products.']);
}
