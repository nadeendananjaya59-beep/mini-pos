<?php
/**
 * Product management (Admin only): CRUD + low stock alerts.
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);

    try {
        if ($action === 'add' || $action === 'edit') {
            $categoryId = (int) ($_POST['category_id'] ?? 0);
            $name       = trim($_POST['name'] ?? '');
            $sku        = strtoupper(trim($_POST['sku'] ?? ''));
            $price      = $_POST['price'] ?? '';
            $quantity   = $_POST['quantity'] ?? '';
            $threshold  = $_POST['low_stock_threshold'] ?? '';

            // ---- Validation ----
            if ($name === '' || $sku === '' || $categoryId <= 0) {
                throw new InvalidArgumentException('Name, SKU and category are required.');
            }
            if (!is_numeric($price) || $price < 0) {
                throw new InvalidArgumentException('Price must be a number of 0 or more.');
            }
            if (filter_var($quantity, FILTER_VALIDATE_INT) === false || (int) $quantity < 0) {
                throw new InvalidArgumentException('Quantity must be a whole number of 0 or more.');
            }
            if (filter_var($threshold, FILTER_VALIDATE_INT) === false || (int) $threshold < 0) {
                throw new InvalidArgumentException('Low stock threshold must be a whole number of 0 or more.');
            }

            $params = [$categoryId, $name, $sku, round((float) $price, 2), (int) $quantity, (int) $threshold];

            if ($action === 'add') {
                $pdo->prepare('INSERT INTO products (category_id, name, sku, price, quantity, low_stock_threshold)
                               VALUES (?, ?, ?, ?, ?, ?)')->execute($params);
                flash('success', 'Product added.');
            } else {
                $params[] = $id;
                $pdo->prepare('UPDATE products SET category_id = ?, name = ?, sku = ?, price = ?, quantity = ?,
                               low_stock_threshold = ? WHERE id = ?')->execute($params);
                flash('success', 'Product updated.');
            }
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM products WHERE id = ?')->execute([$id]);
            flash('success', 'Product deleted.');
        }
    } catch (InvalidArgumentException $e) {
        flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            flash('danger', $action === 'delete'
                ? 'Cannot delete: this product appears in past sales.'
                : 'SKU already exists or the category is invalid.');
        } else {
            flash('danger', 'Database error. Please try again.');
        }
    }
    redirect('admin/products.php');
}

// ---- Listing with optional search ----
$search = trim($_GET['q'] ?? '');
$sql = 'SELECT p.*, c.name AS category_name
          FROM products p JOIN categories c ON c.id = p.category_id';
$args = [];
if ($search !== '') {
    $sql .= ' WHERE p.name LIKE ? OR p.sku LIKE ?';
    $args = ['%' . $search . '%', '%' . $search . '%'];
}
$sql .= ' ORDER BY (p.quantity <= p.low_stock_threshold) DESC, p.name';
$stmt = $pdo->prepare($sql);
$stmt->execute($args);
$products   = $stmt->fetchAll();
$categories = $pdo->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();

$lowCount = 0;
foreach ($products as $p) {
    if ($p['quantity'] <= $p['low_stock_threshold']) {
        $lowCount++;
    }
}

$pageTitle = 'Products';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h3 class="mb-0"><i class="bi bi-box-seam"></i> Products</h3>
    <div class="d-flex gap-2">
        <form class="d-flex" method="get">
            <input class="form-control me-2" type="search" name="q" placeholder="Search name or SKU"
                   value="<?= e($search) ?>">
            <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
        </form>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#prodModal" onclick="openProd()">
            <i class="bi bi-plus-lg"></i> Add Product
        </button>
    </div>
</div>

<?php if ($lowCount > 0): ?>
    <div class="alert alert-danger d-flex align-items-center">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div><strong><?= $lowCount ?></strong> product(s) are at or below their low-stock threshold. Please restock.</div>
    </div>
<?php endif; ?>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>SKU</th><th>Name</th><th>Category</th>
                    <th class="text-end">Price</th><th class="text-end">Qty</th>
                    <th class="text-end">Threshold</th><th>Status</th><th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($products as $p):
                $low = $p['quantity'] <= $p['low_stock_threshold']; ?>
                <tr class="<?= $low ? 'table-low-stock' : '' ?>">
                    <td><code><?= e($p['sku']) ?></code></td>
                    <td><?= e($p['name']) ?></td>
                    <td><?= e($p['category_name']) ?></td>
                    <td class="text-end"><?= money($p['price']) ?></td>
                    <td class="text-end fw-bold"><?= (int) $p['quantity'] ?></td>
                    <td class="text-end"><?= (int) $p['low_stock_threshold'] ?></td>
                    <td>
                        <?php if ($p['quantity'] == 0): ?>
                            <span class="badge bg-dark">Out of stock</span>
                        <?php elseif ($low): ?>
                            <span class="badge bg-danger"><i class="bi bi-exclamation-triangle"></i> Low stock</span>
                        <?php else: ?>
                            <span class="badge bg-success">In stock</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#prodModal"
                                onclick='openProd(<?= json_encode([
                                    "id" => (int) $p["id"], "category_id" => (int) $p["category_id"],
                                    "name" => $p["name"], "sku" => $p["sku"], "price" => $p["price"],
                                    "quantity" => (int) $p["quantity"], "threshold" => (int) $p["low_stock_threshold"],
                                ], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this product?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit modal -->
<div class="modal fade" id="prodModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="pAction" value="add">
            <input type="hidden" name="id" id="pId" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="pTitle">Add Product</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="pName">Name</label>
                        <input class="form-control" name="name" id="pName" maxlength="150" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pSku">SKU</label>
                        <input class="form-control" name="sku" id="pSku" maxlength="50" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="pCat">Category</label>
                        <select class="form-select" name="category_id" id="pCat" required>
                            <option value="">-- select --</option>
                            <?php foreach ($categories as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pPrice">Price</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="price" id="pPrice" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pQty">Quantity</label>
                        <input type="number" min="0" class="form-control" name="quantity" id="pQty" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label" for="pThr">Low stock at</label>
                        <input type="number" min="0" class="form-control" name="low_stock_threshold" id="pThr" value="5" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fill the modal for add (no args) or edit (product object).
function openProd(p) {
    var editing = !!p;
    p = p || {id: 0, category_id: '', name: '', sku: '', price: '', quantity: '', threshold: 5};
    document.getElementById('pAction').value = editing ? 'edit' : 'add';
    document.getElementById('pTitle').textContent = editing ? 'Edit Product' : 'Add Product';
    document.getElementById('pId').value    = p.id;
    document.getElementById('pName').value  = p.name;
    document.getElementById('pSku').value   = p.sku;
    document.getElementById('pCat').value   = p.category_id;
    document.getElementById('pPrice').value = p.price;
    document.getElementById('pQty').value   = p.quantity;
    document.getElementById('pThr').value   = p.threshold;
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
