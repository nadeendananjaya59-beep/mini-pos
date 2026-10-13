<?php
/**
 * Checkout handler (AJAX, JSON in / JSON out).
 *
 * Security notes:
 *  - Prices are ALWAYS read from the database, never trusted from the browser.
 *  - Everything runs inside one DB transaction: either the whole sale is saved
 *    and the stock is reduced, or nothing changes (rollback).
 *  - Rows are locked with SELECT ... FOR UPDATE so two cashiers cannot sell
 *    the same last item at the same time.
 */
require_once __DIR__ . '/../includes/auth.php';

header('Content-Type: application/json; charset=utf-8');

/** Send a JSON response and stop. */
function respond(array $data, int $status = 200): void
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'Method not allowed.'], 405);
}
if (!isLoggedIn()) {
    respond(['success' => false, 'message' => 'Session expired. Please log in again.'], 401);
}
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''))) {
    respond(['success' => false, 'message' => 'Invalid request token. Refresh the page.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input) || empty($input['items']) || !is_array($input['items'])) {
    respond(['success' => false, 'message' => 'The cart is empty.'], 422);
}

// ---- Merge duplicate lines & validate quantities ----
$wanted = [];                                   // product_id => qty
foreach ($input['items'] as $line) {
    $pid = filter_var($line['id'] ?? null, FILTER_VALIDATE_INT);
    $qty = filter_var($line['qty'] ?? null, FILTER_VALIDATE_INT);
    if ($pid === false || $qty === false || $pid <= 0 || $qty <= 0) {
        respond(['success' => false, 'message' => 'Invalid item in cart.'], 422);
    }
    $wanted[$pid] = ($wanted[$pid] ?? 0) + $qty;
}
ksort($wanted);                                 // consistent lock order avoids deadlocks

$discountType  = ($input['discount_type'] ?? 'amount') === 'percent' ? 'percent' : 'amount';
$discountValue = is_numeric($input['discount_value'] ?? 0) ? (float) $input['discount_value'] : -1;
if ($discountValue < 0) {
    respond(['success' => false, 'message' => 'Invalid discount value.'], 422);
}

$pdo = db();

try {
    $pdo->beginTransaction();

    // 1. Lock the product rows and read the real prices / stock.
    $placeholders = implode(',', array_fill(0, count($wanted), '?'));
    $stmt = $pdo->prepare("SELECT id, name, price, quantity FROM products WHERE id IN ($placeholders) FOR UPDATE");
    $stmt->execute(array_keys($wanted));
    $products = [];
    foreach ($stmt->fetchAll() as $row) {
        $products[(int) $row['id']] = $row;
    }

    // 2. Check every line and build the totals.
    $subtotal  = 0.0;
    $lineItems = [];
    foreach ($wanted as $pid => $qty) {
        if (!isset($products[$pid])) {
            throw new RuntimeException('A product in the cart no longer exists.');
        }
        $p = $products[$pid];
        if ($p['quantity'] < $qty) {
            throw new RuntimeException("Not enough stock for \"{$p['name']}\" (available: {$p['quantity']}).");
        }
        $unit      = (float) $p['price'];
        $lineTotal = round($unit * $qty, 2);
        $subtotal += $lineTotal;
        $lineItems[] = ['id' => $pid, 'qty' => $qty, 'unit' => $unit, 'total' => $lineTotal];
    }
    $subtotal = round($subtotal, 2);

    $discount = $discountType === 'percent'
        ? round($subtotal * min($discountValue, 100) / 100, 2)
        : round($discountValue, 2);
    if ($discount > $subtotal) {
        throw new RuntimeException('Discount cannot be greater than the subtotal.');
    }
    $net = round($subtotal - $discount, 2);

    // 3. Save the sale header.
    $pdo->prepare('INSERT INTO sales (user_id, subtotal, discount, net_total) VALUES (?, ?, ?, ?)')
        ->execute([$_SESSION['user_id'], $subtotal, $discount, $net]);
    $saleId = (int) $pdo->lastInsertId();

    // 4. Save the lines and deduct the stock.
    $insItem = $pdo->prepare('INSERT INTO sale_items (sale_id, product_id, qty, unit_price, line_total) VALUES (?, ?, ?, ?, ?)');
    $updStock = $pdo->prepare('UPDATE products SET quantity = quantity - ? WHERE id = ? AND quantity >= ?');
    foreach ($lineItems as $li) {
        $insItem->execute([$saleId, $li['id'], $li['qty'], $li['unit'], $li['total']]);
        $updStock->execute([$li['qty'], $li['id'], $li['qty']]);
        if ($updStock->rowCount() !== 1) {
            throw new RuntimeException('Stock changed while processing. Please try again.');
        }
    }

    $pdo->commit();
    respond(['success' => true, 'sale_id' => $saleId, 'net_total' => $net]);

} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(['success' => false, 'message' => $e->getMessage()], 422);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    respond(['success' => false, 'message' => 'Checkout failed. Nothing was saved.'], 500);
}
