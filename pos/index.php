<?php
/**
 * Sales / POS screen: search products, build a cart, checkout.
 * Available to both Admin and Staff.
 */
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

$pageTitle    = 'Sales / POS';
$extraScripts = [BASE_URL . '/assets/js/pos.js'];
require __DIR__ . '/../includes/header.php';
?>

<div id="posApp"
     data-base="<?= e(BASE_URL) ?>"
     data-csrf="<?= e(csrfToken()) ?>"
     data-currency="<?= e(CURRENCY) ?>">

    <h3 class="mb-3"><i class="bi bi-cart3"></i> New Sale</h3>

    <div class="row g-3">
        <!-- LEFT: product search -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="input-group mb-3">
                        <span class="input-group-text"><i class="bi bi-search"></i></span>
                        <input type="search" id="searchBox" class="form-control form-control-lg"
                               placeholder="Search product name or SKU..." autofocus autocomplete="off">
                    </div>
                    <div id="results" class="list-group pos-results"></div>
                </div>
            </div>
        </div>

        <!-- RIGHT: cart -->
        <div class="col-lg-6">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <strong>Current Cart</strong>
                    <button class="btn btn-sm btn-outline-secondary" id="clearCart">Clear</button>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle mb-0">
                        <thead class="table-light">
                            <tr><th>Item</th><th class="text-end">Price</th><th>Qty</th><th class="text-end">Total</th><th></th></tr>
                        </thead>
                        <tbody id="cartBody"></tbody>
                    </table>
                </div>
                <div class="card-body border-top">
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small mb-1" for="discountValue">Discount</label>
                            <input type="number" min="0" step="0.01" value="0" class="form-control" id="discountValue">
                        </div>
                        <div class="col-5">
                            <label class="form-label small mb-1" for="discountType">Type</label>
                            <select class="form-select" id="discountType">
                                <option value="amount"><?= e(CURRENCY) ?> amount</option>
                                <option value="percent">Percent %</option>
                            </select>
                        </div>
                    </div>
                    <div class="d-flex justify-content-between"><span>Subtotal</span><strong id="subtotal">0.00</strong></div>
                    <div class="d-flex justify-content-between text-danger"><span>Discount</span><strong id="discountAmt">0.00</strong></div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between fs-4"><span>Net Price</span><strong id="netTotal">0.00</strong></div>
                    <button class="btn btn-success btn-lg w-100 mt-3" id="checkoutBtn" disabled>
                        <i class="bi bi-check-circle"></i> Complete Sale
                    </button>
                    <div id="posMessage" class="mt-3"></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
