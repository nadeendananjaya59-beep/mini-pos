<?php
/**
 * Dashboard: key metric cards + low stock list.
 */
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pdo = db();

// Total number of products
$totalProducts = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();

// Products at or below their threshold
$lowStockCount = (int) $pdo->query('SELECT COUNT(*) FROM products WHERE quantity <= low_stock_threshold')->fetchColumn();

// Today's sales (count + revenue). Range comparison keeps the created_at index usable.
$stmt = $pdo->prepare(
    'SELECT COUNT(*) AS cnt, COALESCE(SUM(net_total), 0) AS revenue
       FROM sales WHERE created_at >= CURDATE() AND created_at < CURDATE() + INTERVAL 1 DAY'
);
$stmt->execute();
$today = $stmt->fetch();

// Low stock list (top 8, most critical first)
$lowStock = $pdo->query(
    'SELECT name, sku, quantity, low_stock_threshold FROM products
      WHERE quantity <= low_stock_threshold ORDER BY quantity ASC, name LIMIT 8'
)->fetchAll();

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<h3 class="mb-4">Welcome, <?= e($_SESSION['name']) ?> 👋</h3>

<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card stat-card c-primary h-100"><div class="card-body">
            <div class="text-muted"><i class="bi bi-box-seam"></i> Total Products</div>
            <div class="stat-value"><?= $totalProducts ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card c-danger h-100"><div class="card-body">
            <div class="text-muted"><i class="bi bi-exclamation-triangle"></i> Low Stock Alerts</div>
            <div class="stat-value text-danger"><?= $lowStockCount ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card stat-card c-success h-100"><div class="card-body">
            <div class="text-muted"><i class="bi bi-cash-coin"></i> Today's Sales</div>
            <div class="stat-value"><?= money($today['revenue']) ?></div>
            <small class="text-muted"><?= (int) $today['cnt'] ?> transaction(s) today</small>
        </div></div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Low stock products</strong></div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>SKU</th><th>Name</th><th class="text-end">Qty</th><th class="text-end">Threshold</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $p): ?>
                        <tr class="table-low-stock">
                            <td><code><?= e($p['sku']) ?></code></td>
                            <td><?= e($p['name']) ?></td>
                            <td class="text-end"><span class="badge bg-danger"><?= (int) $p['quantity'] ?></span></td>
                            <td class="text-end"><?= (int) $p['low_stock_threshold'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?>
                        <tr><td colspan="4" class="text-center text-success py-3">All products are well stocked. ✔</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-header bg-white"><strong>Quick actions</strong></div>
            <div class="list-group list-group-flush">
                <a class="list-group-item list-group-item-action" href="<?= BASE_URL ?>/pos/index.php"><i class="bi bi-cart-plus"></i> Start a new sale</a>
                <a class="list-group-item list-group-item-action" href="<?= BASE_URL ?>/reports/sales_report.php"><i class="bi bi-graph-up"></i> View sales report</a>
                <?php if (isAdmin()): ?>
                    <a class="list-group-item list-group-item-action" href="<?= BASE_URL ?>/admin/products.php"><i class="bi bi-box-seam"></i> Manage products</a>
                    <a class="list-group-item list-group-item-action" href="<?= BASE_URL ?>/admin/categories.php"><i class="bi bi-tags"></i> Manage categories</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
