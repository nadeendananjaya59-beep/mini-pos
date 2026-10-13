/**
 * POS front-end logic (vanilla JS).
 *  - live product search (fetch)
 *  - cart handling
 *  - real-time Subtotal / Discount / Net calculation
 *  - checkout request
 *
 * NOTE: totals shown here are for the cashier's convenience only.
 * The server recalculates everything from database prices at checkout.
 */
(function () {
    'use strict';

    var app      = document.getElementById('posApp');
    var BASE     = app.dataset.base;
    var CSRF     = app.dataset.csrf;
    var CURRENCY = app.dataset.currency;

    var searchBox   = document.getElementById('searchBox');
    var resultsEl   = document.getElementById('results');
    var cartBody    = document.getElementById('cartBody');
    var discountVal = document.getElementById('discountValue');
    var discountTyp = document.getElementById('discountType');
    var checkoutBtn = document.getElementById('checkoutBtn');
    var messageEl   = document.getElementById('posMessage');

    var cart = [];          // [{id, name, sku, price, stock, qty}]
    var searchTimer = null;

    // ---------- helpers ----------
    function money(n) { return Number(n).toFixed(2); }

    function escapeHtml(s) {
        var d = document.createElement('div');
        d.textContent = s;
        return d.innerHTML;
    }

    function showMessage(type, text) {
        messageEl.innerHTML = '<div class="alert alert-' + type + ' mb-0">' + escapeHtml(text) + '</div>';
    }

    // ---------- product search ----------
    function search(q) {
        fetch(BASE + '/pos/search.php?q=' + encodeURIComponent(q), { credentials: 'same-origin' })
            .then(function (r) {
                if (r.status === 401) { window.location = BASE + '/index.php'; throw new Error('expired'); }
                return r.json();
            })
            .then(renderResults)
            .catch(function (err) {
                if (err.message !== 'expired') { showMessage('danger', 'Search failed. Please try again.'); }
            });
    }

    function renderResults(products) {
        resultsEl.innerHTML = '';
        if (!products.length) {
            resultsEl.innerHTML = '<div class="text-muted p-3">No products found.</div>';
            return;
        }
        products.forEach(function (p) {
            var out = p.quantity <= 0;
            var item = document.createElement('div');
            item.className = 'list-group-item pos-product d-flex justify-content-between align-items-center' +
                              (out ? ' out-of-stock' : '');
            item.innerHTML =
                '<div><div class="fw-semibold">' + escapeHtml(p.name) + '</div>' +
                '<small class="text-muted">' + escapeHtml(p.sku) + ' &middot; Stock: ' + p.quantity + '</small></div>' +
                '<div class="text-end"><div class="fw-bold">' + CURRENCY + ' ' + money(p.price) + '</div>' +
                (out ? '<span class="badge bg-dark">Out of stock</span>'
                     : '<span class="badge bg-primary">Add</span>') + '</div>';
            if (!out) { item.addEventListener('click', function () { addToCart(p); }); }
            resultsEl.appendChild(item);
        });
    }

    searchBox.addEventListener('input', function () {
        clearTimeout(searchTimer);                       // debounce typing
        searchTimer = setTimeout(function () { search(searchBox.value.trim()); }, 250);
    });

    // ---------- cart ----------
    function addToCart(p) {
        var line = cart.find(function (l) { return l.id === p.id; });
        if (line) {
            if (line.qty >= line.stock) { showMessage('warning', 'Only ' + line.stock + ' in stock for ' + p.name + '.'); return; }
            line.qty++;
        } else {
            cart.push({ id: p.id, name: p.name, sku: p.sku, price: p.price, stock: p.quantity, qty: 1 });
        }
        messageEl.innerHTML = '';
        renderCart();
    }

    function renderCart() {
        cartBody.innerHTML = '';
        if (!cart.length) {
            cartBody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">Cart is empty. Search and click a product to add it.</td></tr>';
        }
        cart.forEach(function (l, i) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td>' + escapeHtml(l.name) + '<br><small class="text-muted">' + escapeHtml(l.sku) + '</small></td>' +
                '<td class="text-end">' + money(l.price) + '</td>' +
                '<td><input type="number" class="form-control form-control-sm cart-qty" min="1" max="' + l.stock +
                    '" value="' + l.qty + '" data-index="' + i + '"></td>' +
                '<td class="text-end">' + money(l.price * l.qty) + '</td>' +
                '<td class="text-end"><button class="btn btn-sm btn-outline-danger" data-remove="' + i + '">' +
                    '<i class="bi bi-x-lg"></i></button></td>';
            cartBody.appendChild(tr);
        });
        recalc();
    }

    cartBody.addEventListener('input', function (ev) {
        if (!ev.target.classList.contains('cart-qty')) { return; }
        var line = cart[ev.target.dataset.index];
        var qty = parseInt(ev.target.value, 10);
        if (isNaN(qty) || qty < 1) { return; }
        if (qty > line.stock) { qty = line.stock; ev.target.value = qty; showMessage('warning', 'Only ' + line.stock + ' in stock.'); }
        line.qty = qty;
        // Update only the totals so the input keeps focus while typing.
        ev.target.closest('tr').children[3].textContent = money(line.price * line.qty);
        recalc();
    });

    cartBody.addEventListener('click', function (ev) {
        var btn = ev.target.closest('[data-remove]');
        if (!btn) { return; }
        cart.splice(parseInt(btn.dataset.remove, 10), 1);
        renderCart();
    });

    document.getElementById('clearCart').addEventListener('click', function () {
        cart = [];
        messageEl.innerHTML = '';
        renderCart();
    });

    // ---------- calculations ----------
    function calcTotals() {
        var subtotal = cart.reduce(function (sum, l) { return sum + l.price * l.qty; }, 0);
        var value = parseFloat(discountVal.value);
        if (isNaN(value) || value < 0) { value = 0; }

        var discount = discountTyp.value === 'percent' ? subtotal * Math.min(value, 100) / 100 : value;
        if (discount > subtotal) { discount = subtotal; }          // never go below zero

        return { subtotal: subtotal, discount: discount, net: subtotal - discount };
    }

    function recalc() {
        var t = calcTotals();
        document.getElementById('subtotal').textContent    = money(t.subtotal);
        document.getElementById('discountAmt').textContent = money(t.discount);
        document.getElementById('netTotal').textContent    = money(t.net);
        checkoutBtn.disabled = cart.length === 0;
    }

    discountVal.addEventListener('input', recalc);
    discountTyp.addEventListener('change', recalc);

    // ---------- checkout ----------
    checkoutBtn.addEventListener('click', function () {
        if (!cart.length) { return; }
        checkoutBtn.disabled = true;
        checkoutBtn.textContent = 'Processing...';

        fetch(BASE + '/pos/checkout.php', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-Token': CSRF },
            body: JSON.stringify({
                items: cart.map(function (l) { return { id: l.id, qty: l.qty }; }),
                discount_type: discountTyp.value,
                discount_value: parseFloat(discountVal.value) || 0
            })
        })
        .then(function (r) { return r.json().then(function (d) { return { status: r.status, data: d }; }); })
        .then(function (res) {
            if (res.data.success) {
                window.location = BASE + '/pos/receipt.php?id=' + res.data.sale_id;
            } else {
                if (res.status === 401) { window.location = BASE + '/index.php'; return; }
                showMessage('danger', res.data.message || 'Checkout failed.');
                resetButton();
            }
        })
        .catch(function () {
            showMessage('danger', 'Network error. Nothing was saved.');
            resetButton();
        });
    });

    function resetButton() {
        checkoutBtn.innerHTML = '<i class="bi bi-check-circle"></i> Complete Sale';
        checkoutBtn.disabled = cart.length === 0;
    }

    // ---------- init ----------
    renderCart();
    search('');          // show the first products straight away
})();
