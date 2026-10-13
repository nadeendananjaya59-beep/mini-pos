<?php
/**
 * AJAX endpoint: search products by name or SKU. Returns JSON.
 */
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Session expired. Please log in again.']);
    exit;
}

$q = trim($_GET['q'] ?? '');

$sql  = 'SELECT id, name, sku, price, quantity FROM products';
$args = [];
if ($q !== '') {
    $sql .= ' WHERE name LIKE ? OR sku LIKE ?';
    $args = ['%' . $q . '%', '%' . $q . '%'];
}
$sql .= ' ORDER BY name LIMIT 30';

$stmt = db()->prepare($sql);
$stmt->execute($args);

$rows = array_map(static function (array $r): array {
    return [
        'id'       => (int) $r['id'],
        'name'     => $r['name'],
        'sku'      => $r['sku'],
        'price'    => (float) $r['price'],
        'quantity' => (int) $r['quantity'],
    ];
}, $stmt->fetchAll());

echo json_encode($rows);
