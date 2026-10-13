<?php
/**
 * Date range sales report (printable).
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

/** Validate a Y-m-d date string; returns null when invalid. */
function validDate(?string $d): ?string
{
    $dt = DateTime::createFromFormat('Y-m-d', (string) $d);
    return ($dt && $dt->format('Y-m-d') === $d) ? $d : null;
}

// Default range: first day of this month -> today.
$from  = validDate($_GET['from'] ?? '') ?? date('Y-m-01');
$to    = validDate($_GET['to'] ?? '')   ?? date('Y-m-d');
$error = '';

if ($from > $to) {
    $error = 'The "From" date cannot be after the "To" date.';
    $sales = [];
} else {
    // created_at < (to + 1 day) includes the whole last day.
    $stmt = db()->prepare(
        'SELECT s.id, s.created_at, s.subtotal, s.discount, s.net_total, u.name AS cashier,
                (SELECT COALESCE(SUM(qty), 0) FROM sale_items WHERE sale_id = s.id) AS items
           FROM sales s JOIN users u ON u.id = s.user_id
          WHERE s.created_at >= ? AND s.created_at < DATE_ADD(?, INTERVAL 1 DAY)
          ORDER BY s.created_at DESC'
    );
    $stmt->execute([$from, $to]);
    $sales = $stmt->fetchAll();
}

$totalTransactions = count($sales);
$totalRevenue = 0.0;
$totalDiscount = 0.0;
foreach ($sales as $s) {
    $totalRevenue  += (float) $s['net_total'];
    $totalDiscount += (float) $s['discount'];
}

$pageTitle = 'Sales Report';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3 no-print">
    <h3 class="mb-0"><i class="bi bi-graph-up"></i> Sales Report</h3>
    <button class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Print</button>
</div>

<form method="get" class="card card-body shadow-sm mb-4 no-print">
    <div class="row g-3 align-items-end">
        <div class="col-md-4">
            <label class="form-label" for="from">From</label>
            <input type="date" class="form-control" id="from" name="from" value="<?= e($from) ?>" required>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="to">To</label>
            <input type="date" class="form-control" id="to" name="to" value="<?= e($to) ?>" required>
        </div>
        <div class="col-md-4">
            <button class="btn btn-primary w-100"><i class="bi bi-funnel"></i> Generate</button>
        </div>
    </div>
</form>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php else: ?>

<h5 class="d-none d-print-block">Sales Report: <?= e($from) ?> to <?= e($to) ?></h5>

<div class="row g-3 mb-4">
    <div class="col-md-4"><div class="card stat-card c-primary"><div class="card-body">
        <div class="text-muted">Total Transactions</div><div class="stat-value"><?= $totalTransactions ?></div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card c-success"><div class="card-body">
        <div class="text-muted">Total Revenue</div><div class="stat-value"><?= money($totalRevenue) ?></div>
    </div></div></div>
    <div class="col-md-4"><div class="card stat-card c-danger"><div class="card-body">
        <div class="text-muted">Total Discounts Given</div><div class="stat-value"><?= money($totalDiscount) ?></div>
    </div></div></div>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-striped align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Sale #</th><th>Date &amp; Time</th><th>Cashier</th><th class="text-end">Items</th>
                    <th class="text-end">Subtotal</th><th class="text-end">Discount</th><th class="text-end">Net Total</th>
                    <th class="no-print"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($sales as $s): ?>
                <tr>
                    <td><?= (int) $s['id'] ?></td>
                    <td><?= e($s['created_at']) ?></td>
                    <td><?= e($s['cashier']) ?></td>
                    <td class="text-end"><?= (int) $s['items'] ?></td>
                    <td class="text-end"><?= number_format((float) $s['subtotal'], 2) ?></td>
                    <td class="text-end"><?= number_format((float) $s['discount'], 2) ?></td>
                    <td class="text-end fw-bold"><?= number_format((float) $s['net_total'], 2) ?></td>
                    <td class="no-print text-end">
                        <?php if (isAdmin()): ?>
                            <a class="btn btn-sm btn-outline-secondary" href="<?= BASE_URL ?>/pos/receipt.php?id=<?= (int) $s['id'] ?>">Receipt</a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$sales): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No sales in this date range.</td></tr>
            <?php endif; ?>
            </tbody>
            <?php if ($sales): ?>
            <tfoot class="table-light fw-bold">
                <tr>
                    <td colspan="6" class="text-end">Total revenue (<?= $totalTransactions ?> transactions)</td>
                    <td class="text-end"><?= number_format($totalRevenue, 2) ?></td>
                    <td class="no-print"></td>
                </tr>
            </tfoot>
            <?php endif; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
