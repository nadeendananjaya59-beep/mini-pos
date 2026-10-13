<?php
/**
 * Shared page header + navbar.
 * Set $pageTitle before including. Requires auth.php to be loaded.
 */
$pageTitle = $pageTitle ?? 'Mini-POS';
$current   = basename($_SERVER['PHP_SELF']);
$flash     = takeFlash();

/** Adds the "active" class for the current page. */
function navActive(string $file): string
{
    return basename($_SERVER['PHP_SELF']) === $file ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?> | Mini-POS</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>/assets/css/style.css" rel="stylesheet">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-dark no-print">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= BASE_URL ?>/dashboard.php"><i class="bi bi-shop"></i> Mini-POS</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto">
                <li class="nav-item"><a class="nav-link<?= navActive('dashboard.php') ?>" href="<?= BASE_URL ?>/dashboard.php">Dashboard</a></li>
                <li class="nav-item"><a class="nav-link<?= strpos($_SERVER['PHP_SELF'], '/pos/') !== false ? ' active' : '' ?>" href="<?= BASE_URL ?>/pos/index.php">Sales / POS</a></li>
                <?php if (isAdmin()): ?>
                    <li class="nav-item"><a class="nav-link<?= navActive('products.php') ?>" href="<?= BASE_URL ?>/admin/products.php">Products</a></li>
                    <li class="nav-item"><a class="nav-link<?= navActive('categories.php') ?>" href="<?= BASE_URL ?>/admin/categories.php">Categories</a></li>
                <?php endif; ?>
                <li class="nav-item"><a class="nav-link<?= navActive('sales_report.php') ?>" href="<?= BASE_URL ?>/reports/sales_report.php">Sales Report</a></li>
            </ul>
            <span class="navbar-text me-3">
                <i class="bi bi-person-circle"></i> <?= e($_SESSION['name'] ?? '') ?>
                <span class="badge bg-<?= isAdmin() ? 'warning text-dark' : 'info' ?>"><?= e(ucfirst($_SESSION['role'] ?? '')) ?></span>
            </span>
            <a class="btn btn-outline-light btn-sm" href="<?= BASE_URL ?>/logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a>
        </div>
    </div>
</nav>

<main class="container-fluid py-4">
<?php if ($flash): ?>
    <div class="alert alert-<?= e($flash['type']) ?> alert-dismissible fade show no-print" role="alert">
        <?= e($flash['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
