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

<div id="posApp" class="pos-page"
     data-base="<?= e(BASE_URL) ?>"
     data-csrf="<?= e(csrfToken()) ?>"
     data-currency="<?= e(CURRENCY) ?>">

    <header class="pos-heading">
        <div>
            <div class="pos-kicker"><i class="bi bi-lightning-charge-fill" aria-hidden="true"></i> Point of sale</div>
            <h1>New sale</h1>
            <p>Find products, add them to the cart, and check out.</p>
        </div>
        <div class="pos-live"><span class="pos-live-dot"></span> Ready for checkout</div>
    </header>

    <div class="row g-4 pos-workspace">
        <!-- LEFT: product search -->
        <div class="col-lg-7">
            <section class="pos-panel products-panel" aria-labelledby="products-title">
                <div class="pos-panel-heading">
                    <div class="pos-title-icon"><i class="bi bi-box-seam" aria-hidden="true"></i></div>
                    <div><h2 id="products-title">Products</h2><p>Search by product name or SKU</p></div>
                </div>
                <div class="pos-search-wrap">
                    <i class="bi bi-search" aria-hidden="true"></i>
                    <input type="search" id="searchBox" class="form-control"
                           placeholder="Search products..." aria-label="Search products by name or SKU" autofocus autocomplete="off">
                    <kbd>⌕</kbd>
                </div>
                <div class="pos-results-heading"><span>Product catalogue</span><span>Click an item to add it</span></div>
                <div id="results" class="list-group pos-results" aria-live="polite"></div>
            </section>
        </div>

        <!-- RIGHT: cart -->
        <div class="col-lg-5">
            <section class="pos-panel cart-panel" aria-labelledby="cart-title">
                <div class="pos-panel-heading cart-heading">
                    <div class="pos-title-icon cart-title-icon"><i class="bi bi-bag" aria-hidden="true"></i></div>
                    <div><h2 id="cart-title">Current sale</h2><p><span id="cartCount">0</span> items in your cart</p></div>
                    <button class="btn btn-sm pos-clear-btn" id="clearCart" type="button"><i class="bi bi-trash3" aria-hidden="true"></i> Clear</button>
                </div>
                <div class="pos-cart-table">
                    <table class="table align-middle mb-0">
                        <thead><tr><th>Item</th><th class="text-end">Price</th><th>Qty</th><th class="text-end">Total</th><th><span class="visually-hidden">Remove</span></th></tr></thead>
                        <tbody id="cartBody"></tbody>
                    </table>
                </div>
                <div class="pos-checkout">
                    <div class="pos-discount-title"><i class="bi bi-tag" aria-hidden="true"></i> Apply discount</div>
                    <div class="row g-2 mb-4">
                        <div class="col-7">
                            <label class="form-label" for="discountValue">Discount amount</label>
                            <input type="number" min="0" step="0.01" value="0" class="form-control" id="discountValue" placeholder="0.00">
                        </div>
                        <div class="col-5">
                            <label class="form-label" for="discountType">Discount type</label>
                            <select class="form-select" id="discountType">
                                <option value="amount"><?= e(CURRENCY) ?> amount</option>
                                <option value="percent">Percent %</option>
                            </select>
                        </div>
                    </div>
                    <div class="pos-total-row"><span>Subtotal</span><strong><?= e(CURRENCY) ?> <span id="subtotal">0.00</span></strong></div>
                    <div class="pos-total-row discount-row"><span>Discount</span><strong>− <?= e(CURRENCY) ?> <span id="discountAmt">0.00</span></strong></div>
                    <div class="pos-grand-total"><span>Total due</span><strong><?= e(CURRENCY) ?> <span id="netTotal">0.00</span></strong></div>
                    <button class="btn pos-checkout-btn w-100" id="checkoutBtn" disabled>
                        <i class="bi bi-lock-fill" aria-hidden="true"></i> Complete sale <i class="bi bi-arrow-right ms-auto" aria-hidden="true"></i>
                    </button>
                    <div class="pos-secure-note"><i class="bi bi-shield-check" aria-hidden="true"></i> Secure checkout · Stock updates automatically</div>
                    <div id="posMessage" class="mt-3" aria-live="assertive"></div>
                </div>
            </section>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
