<?php
require_once __DIR__ . '/../includes/functions.php';
require_role(['admin']);
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $id = (int)($_POST['id'] ?? 0);

    if (($_POST['action'] ?? 'update') === 'delete') {
        if ($id === (int)current_user()['id']) {
            $error = 'You cannot delete your own account.';
        } else {
            try {
                $stmt = $conn->prepare('DELETE FROM users WHERE id=?');
                $stmt->bind_param('i', $id);
                $stmt->execute();
                if ($stmt->affected_rows === 0) {
                    $error = 'User not found.';
                } else {
                    log_activity($conn, 'DELETE', 'users', $id, 'User deleted');
                    flash('success', 'User deleted.');
                    redirect('admin/users.php');
                }
            } catch (mysqli_sql_exception $e) {
                if ((int)$e->getCode() === 1451) {
                    $error = 'This user cannot be deleted because their account is referenced by sales, restocking, or inventory history.';
                } else {
                    throw $e;
                }
            }
        }
    } else {
        $role = $_POST['role'] ?? 'cashier';
        $verified = isset($_POST['is_verified']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (!in_array($role, ['admin', 'staff', 'cashier'], true)) {
            $error = 'Invalid role.';
        } elseif ($id === (int)current_user()['id'] && $role !== 'admin') {
            $error = 'You cannot remove your own administrator role.';
        } else {
            $stmt = $conn->prepare('UPDATE users SET role=?,is_verified=?,is_active=? WHERE id=?');
            $stmt->bind_param('siii', $role, $verified, $active, $id);
            $stmt->execute();
            log_activity($conn, 'UPDATE', 'users', $id, 'Role/account status updated');
            flash('success', 'User updated.');
            redirect('admin/users.php');
        }
    }
}

layout_header('User accounts');
?>
<?php if ($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<div class="alert alert-info">Public registration can only create cashier accounts. Use this page to assign roles. Only verified and active accounts can log in.</div>
<section class="card"><div class="table-wrap"><table>
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Verified</th><th>Active</th><th>Save changes</th><th>Actions</th></tr></thead>
    <tbody>
    <?php $q = $conn->query('SELECT id,name,email,role,is_verified,is_active FROM users ORDER BY created_at DESC'); while ($u = $q->fetch_assoc()): ?>
        <?php $formId = 'user-update-' . (int)$u['id']; ?>
        <tr>
            <td><form id="<?= e($formId) ?>" method="post"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"></form><?= e($u['name']) ?></td>
            <td><?= e($u['email']) ?></td>
            <td><select form="<?= e($formId) ?>" name="role"><?php foreach (['admin', 'staff', 'cashier'] as $r): ?><option value="<?= $r ?>" <?= $u['role'] === $r ? 'selected' : '' ?>><?= ucfirst($r) ?></option><?php endforeach; ?></select></td>
            <td><input form="<?= e($formId) ?>" type="checkbox" name="is_verified" <?= $u['is_verified'] ? 'checked' : '' ?>></td>
            <td><input form="<?= e($formId) ?>" type="checkbox" name="is_active" <?= $u['is_active'] ? 'checked' : '' ?>></td>
            <td><button form="<?= e($formId) ?>" class="btn btn-small">Update</button></td>
            <td><form method="post" class="inline" onsubmit="return confirm('Delete this user account? This cannot be undone.');"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$u['id'] ?>"><input type="hidden" name="action" value="delete"><button class="btn btn-small btn-action btn-reject">Delete</button></form></td>
        </tr>
    <?php endwhile; ?>
    </tbody>
</table></div></section>
<?php layout_footer(); ?>
