<?php
/**
 * Printable receipt for a completed sale.
 * Admin can open any receipt; staff can only open their own.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$saleId = (int) ($_GET['id'] ?? 0);

$stmt = db()->prepare(
    'SELECT s.*, u.name AS cashier FROM sales s JOIN users u ON u.id = s.user_id WHERE s.id = ?'
);
$stmt->execute([$saleId]);
$sale = $stmt->fetch();

if (!$sale || (!isAdmin() && (int) $sale['user_id'] !== (int) $_SESSION['user_id'])) {
    flash('danger', 'Receipt not found.');
    redirect('pos/index.php');
}

$stmt = db()->prepare(
    'SELECT si.qty, si.unit_price, si.line_total, p.name
       FROM sale_items si JOIN products p ON p.id = si.product_id
      WHERE si.sale_id = ? ORDER BY si.id'
);
$stmt->execute([$saleId]);
$items = $stmt->fetchAll();

$pageTitle = 'Receipt #' . $saleId;
require __DIR__ . '/../includes/header.php';
?>

<div class="receipt shadow-sm">
    <h4 class="text-center mb-0">MINI-POS</h4>
    <p class="text-center small mb-2">Sales Receipt</p>
    <hr>
    <div class="small">
        Receipt #: <?= (int) $sale['id'] ?><br>
        Date: <?= e($sale['created_at']) ?><br>
        Cashier: <?= e($sale['cashier']) ?>
    </div>
    <hr>
    <table class="table table-sm table-borderless small mb-0">
        <?php foreach ($items as $it): ?>
            <tr>
                <td colspan="2"><?= e($it['name']) ?></td>
            </tr>
            <tr>
                <td class="ps-3"><?= (int) $it['qty'] ?> x <?= number_format((float) $it['unit_price'], 2) ?></td>
                <td class="text-end"><?= number_format((float) $it['line_total'], 2) ?></td>
            </tr>
        <?php endforeach; ?>
    </table>
    <hr>
    <table class="table table-sm table-borderless mb-0">
        <tr><td>Subtotal</td><td class="text-end"><?= money($sale['subtotal']) ?></td></tr>
        <tr><td>Discount</td><td class="text-end">- <?= money($sale['discount']) ?></td></tr>
        <tr class="fw-bold"><td>NET TOTAL</td><td class="text-end"><?= money($sale['net_total']) ?></td></tr>
    </table>
    <hr>
    <p class="text-center small mb-0">Thank you! Come again.</p>
</div>

<div class="text-center mt-3 no-print">
    <button class="btn btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
    <a class="btn btn-success" href="<?= BASE_URL ?>/pos/index.php"><i class="bi bi-cart-plus"></i> New Sale</a>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
