<?php
/**
 * Category management (Admin only): add, edit, delete.
 */
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf();
    $action = $_POST['action'] ?? '';
    $id     = (int) ($_POST['id'] ?? 0);
    $name   = trim($_POST['name'] ?? '');

    try {
        if ($action === 'add' || $action === 'edit') {
            if ($name === '' || mb_strlen($name) > 100) {
                throw new InvalidArgumentException('Category name is required (max 100 characters).');
            }
            if ($action === 'add') {
                $pdo->prepare('INSERT INTO categories (name) VALUES (?)')->execute([$name]);
                flash('success', 'Category added.');
            } else {
                $pdo->prepare('UPDATE categories SET name = ? WHERE id = ?')->execute([$name, $id]);
                flash('success', 'Category updated.');
            }
        } elseif ($action === 'delete') {
            $pdo->prepare('DELETE FROM categories WHERE id = ?')->execute([$id]);
            flash('success', 'Category deleted.');
        }
    } catch (InvalidArgumentException $e) {
        flash('danger', $e->getMessage());
    } catch (PDOException $e) {
        // 23000 = integrity constraint (duplicate name or category still used by products)
        if ($e->getCode() === '23000') {
            flash('danger', $action === 'delete'
                ? 'Cannot delete: this category still has products.'
                : 'A category with that name already exists.');
        } else {
            flash('danger', 'Database error. Please try again.');
        }
    }
    redirect('admin/categories.php');
}

$categories = $pdo->query(
    'SELECT c.id, c.name, COUNT(p.id) AS product_count
       FROM categories c LEFT JOIN products p ON p.category_id = c.id
   GROUP BY c.id, c.name ORDER BY c.name'
)->fetchAll();

$pageTitle = 'Categories';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><i class="bi bi-tags"></i> Categories</h3>
    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#catModal" onclick="openCat()">
        <i class="bi bi-plus-lg"></i> Add Category
    </button>
</div>

<div class="card shadow-sm">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr><th>#</th><th>Name</th><th>Products</th><th class="text-end">Actions</th></tr>
            </thead>
            <tbody>
            <?php foreach ($categories as $c): ?>
                <tr>
                    <td><?= (int) $c['id'] ?></td>
                    <td><?= e($c['name']) ?></td>
                    <td><span class="badge bg-secondary"><?= (int) $c['product_count'] ?></span></td>
                    <td class="text-end">
                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#catModal"
                                onclick='openCat(<?= (int) $c['id'] ?>, <?= json_encode($c['name'], JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) ?>)'>
                            <i class="bi bi-pencil"></i> Edit
                        </button>
                        <form method="post" class="d-inline" onsubmit="return confirm('Delete this category?');">
                            <?= csrfField() ?>
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="id" value="<?= (int) $c['id'] ?>">
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i> Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$categories): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">No categories yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Add / Edit modal -->
<div class="modal fade" id="catModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" class="modal-content">
            <?= csrfField() ?>
            <input type="hidden" name="action" id="catAction" value="add">
            <input type="hidden" name="id" id="catId" value="0">
            <div class="modal-header">
                <h5 class="modal-title" id="catTitle">Add Category</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label" for="catName">Category name</label>
                <input type="text" class="form-control" name="name" id="catName" maxlength="100" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Save</button>
            </div>
        </form>
    </div>
</div>

<script>
// Fill the modal for add (no args) or edit (id + name).
function openCat(id, name) {
    var editing = !!id;
    document.getElementById('catAction').value = editing ? 'edit' : 'add';
    document.getElementById('catId').value     = id || 0;
    document.getElementById('catName').value   = name || '';
    document.getElementById('catTitle').textContent = editing ? 'Edit Category' : 'Add Category';
}
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
