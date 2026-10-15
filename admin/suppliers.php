<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['action'] ?? 'save') === 'delete') {
        $stmt = $conn->prepare('DELETE FROM suppliers WHERE id=?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        if ($stmt->affected_rows === 0) {
            $error = 'Supplier not found.';
        } else {
            log_activity($conn, 'DELETE', 'suppliers', $id, 'Supplier deleted; linked restock records retained');
            flash('success', 'Supplier deleted. Existing restock records were retained without a supplier.');
            redirect('admin/suppliers.php');
        }
    } else {
        $name = trim($_POST['name'] ?? '');
        $contact = trim($_POST['contact_name'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $address = trim($_POST['address'] ?? '');
        if ($name === '') {
            $error = 'Supplier name is required.';
        } else {
            if ($id) {
                $stmt = $conn->prepare('UPDATE suppliers SET name=?,contact_name=?,phone=?,email=?,address=? WHERE id=?');
                $stmt->bind_param('sssssi', $name, $contact, $phone, $email, $address, $id);
            } else {
                $stmt = $conn->prepare('INSERT INTO suppliers(name,contact_name,phone,email,address) VALUES(?,?,?,?,?)');
                $stmt->bind_param('sssss', $name, $contact, $phone, $email, $address);
            }
            $stmt->execute();
            log_activity($conn, $id ? 'UPDATE' : 'CREATE', 'suppliers', $id ?: $conn->insert_id, 'Supplier saved');
            flash('success', 'Supplier saved.');
            redirect('admin/suppliers.php');
        }
    }
}

$edit = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $stmt = $conn->prepare('SELECT * FROM suppliers WHERE id=?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $edit = $stmt->get_result()->fetch_assoc();
}
$suppliers = $conn->query('SELECT * FROM suppliers ORDER BY name');
layout_header('Suppliers');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="supplier-layout section-gap">
    <section class="card supplier-form-card">
        <div class="supplier-section-heading">
            <div>
                <p class="supplier-kicker"><?= $edit ? 'UPDATE DETAILS' : 'SUPPLIER DIRECTORY' ?></p>
                <h2><?= $edit ? 'Edit supplier' : 'Add a supplier' ?></h2>
                <p class="muted"><?= $edit ? 'Update this supplier’s contact information.' : 'Save a supplier for your store and restocking requests.' ?></p>
            </div>
            <span class="supplier-heading-icon" aria-hidden="true"><?= $edit ? '✎' : '+' ?></span>
        </div>
        <form method="post" class="supplier-form"><?= csrf_field() ?><input type="hidden" name="action" value="save"><input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <label class="supplier-field-wide">Business name<input name="name" required placeholder="e.g. Juan's General Merchandise" value="<?= e($edit['name'] ?? '') ?>"></label>
            <label>Contact person<input name="contact_name" placeholder="Person to contact" value="<?= e($edit['contact_name'] ?? '') ?>"></label>
            <label>Phone number<input type="tel" name="phone" placeholder="09XX XXX XXXX" value="<?= e($edit['phone'] ?? '') ?>"></label>
            <label class="supplier-field-wide">Email address<input type="email" name="email" placeholder="supplier@example.com" value="<?= e($edit['email'] ?? '') ?>"></label>
            <label class="supplier-field-wide">Business address<textarea name="address" placeholder="Street, barangay, city"><?= e($edit['address'] ?? '') ?></textarea></label>
            <div class="supplier-form-actions supplier-field-wide">
                <button class="btn"><?= $edit ? 'Save changes' : 'Add supplier' ?></button>
                <?php if ($edit): ?><a class="btn btn-outline" href="<?= e(BASE_URL) ?>/admin/suppliers.php">Cancel</a><?php endif; ?>
            </div>
        </form>
    </section>
    <section class="card supplier-list-card">
        <div class="supplier-list-heading">
            <div>
                <p class="supplier-kicker">YOUR BUSINESS PARTNERS</p>
                <h2>Supplier list</h2>
                <p class="muted">Manage supplier details and contact information.</p>
            </div>
            <span class="supplier-count"><strong><?= $suppliers->num_rows ?></strong><span>suppliers</span></span>
        </div>
        <?php if ($suppliers->num_rows > 0): ?>
            <div class="table-wrap supplier-table-wrap"><table class="supplier-table">
                <thead><tr><th>Supplier</th><th>Contact</th><th>Phone</th><th>Email</th><th>Actions</th></tr></thead>
                <tbody>
                <?php while ($s = $suppliers->fetch_assoc()): ?>
                    <tr>
                        <td><span class="supplier-name"><?= e($s['name']) ?></span><span class="supplier-subtext"><?= e($s['address'] ?: 'No address added') ?></span></td>
                        <td><?= e($s['contact_name'] ?: '—') ?></td>
                        <td><?= e($s['phone'] ?: '—') ?></td>
                        <td><?= e($s['email'] ?: '—') ?></td>
                        <td>
                            <div class="actions-cell">
                                <a class="btn btn-small btn-action btn-edit" href="<?= e(BASE_URL) ?>/admin/suppliers.php?edit=<?= (int)$s['id'] ?>">Edit</a>
                                <form action="<?= e(BASE_URL) ?>/admin/suppliers.php" method="post" class="inline" onsubmit="return confirm('Delete this supplier? Existing restock records will be kept without a supplier.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$s['id'] ?>"><input type="hidden" name="action" value="delete"><button type="submit" class="btn btn-small btn-action btn-reject">Delete</button></form>
                            </div>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <div class="supplier-empty-state">
                <span class="supplier-empty-icon" aria-hidden="true">＋</span>
                <h3>No suppliers yet</h3>
                <p>Add your first supplier using the form to keep their details handy for restocking.</p>
            </div>
        <?php endif; ?>
    </section>
</div>
<?php layout_footer(); ?>
