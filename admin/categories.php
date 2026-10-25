<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['action'] ?? 'save') === 'delete') {
        $stmt = $conn->prepare('DELETE FROM categories WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $error = 'Category not found.';
        } else {
            log_activity($conn, 'DELETE', 'categories', $id, 'Category deleted; linked products were uncategorized');
            flash('success', 'Category deleted. Products in this category are now uncategorized.');
            redirect('admin/categories.php');
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        if ($name === '') {
            $error = 'Category name is required.';
        } else {
            try {
                if ($id) {
                    $stmt = $conn->prepare('UPDATE categories SET name=?,description=? WHERE id=?');
                    $stmt->bind_param('ssi', $name, $desc, $id);
                } else {
                    $stmt = $conn->prepare('INSERT INTO categories(name,description) VALUES(?,?)');
                    $stmt->bind_param('ss', $name, $desc);
                }
                $stmt->execute();
                log_activity($conn, $id ? 'UPDATE' : 'CREATE', 'categories', $id ?: $conn->insert_id, 'Category saved');
                flash('success', 'Category saved.');
                redirect('admin/categories.php');
            } catch (mysqli_sql_exception $e) {
                if ((int)$e->getCode() === 1062) {
                    $error = 'Could not save category. Name may already exist.';
                } else {
                    throw $e;
                }
            }
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM categories WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
layout_header('Categories');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="grid two section-gap">
    <section class="card">
        <h2><?= $edit ? 'Edit' : 'Add' ?> category</h2>
        <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label>Category name<input name="name" required value="<?= e($edit['name'] ?? '') ?>"></label>
            <label>Description<textarea name="description"><?= e($edit['description'] ?? '') ?></textarea></label>
            <button class="btn">Save category</button>
        </form>
    </section>
    <section class="card">
        <h2>Categories</h2>
        <div class="table-wrap"><table class="category-table">
            <thead><tr><th>Name</th><th>Description</th><th>Actions</th></tr></thead>
            <tbody>
            <?php $q = $conn->query('SELECT * FROM categories ORDER BY name'); while ($c = $q->fetch_assoc()): ?>
                <tr>
                    <td><?= e($c['name']) ?></td><td><?= e($c['description']) ?></td>
                    <td class="actions-cell category-actions">
                        <a class="btn btn-small btn-action btn-edit" href="?edit=<?= (int)$c['id'] ?>">Edit</a>
                        <form method="post" class="inline" onsubmit="return confirm('Delete this category? Products in it will become uncategorized.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$c['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-small btn-action btn-delete">Delete</button></form>
                    </td>
                </tr>
            <?php endwhile; ?>
            </tbody>
        </table></div>
    </section>
</div>
<?php layout_footer(); ?>
