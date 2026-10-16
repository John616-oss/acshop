
<?php
require_once __DIR__ . '/../includes/functions.php';

require_role(['admin']);

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $id = (int) ($_POST['id'] ?? 0);
    $action = $_POST['action'] ?? 'update';

    if ($id <= 0) {
        $error = 'Invalid user account.';
    } elseif ($action === 'delete') {

        if ($id === (int) current_user()['id']) {
            $error = 'You cannot delete your own account.';
        } else {
            try {
                $stmt = $conn->prepare(
                    'DELETE FROM users WHERE id = ?'
                );
                $stmt->bind_param('i', $id);
                $stmt->execute();

                if ($stmt->affected_rows === 0) {
                    $error = 'User not found.';
                } else {
                    log_activity(
                        $conn,
                        'DELETE',
                        'users',
                        $id,
                        'User deleted'
                    );

                    flash('success', 'User deleted successfully.');
                    redirect('admin/users.php');
                }
            } catch (mysqli_sql_exception $e) {
                if ((int) $e->getCode() === 1451) {
                    $error = 'This account has transaction history and cannot be permanently deleted. Deactivate the account instead.';
                } else {
                    error_log('User deletion failed: ' . $e->getMessage());
                    $error = 'Unable to delete this user account.';
                }
            }
        }

    } elseif ($action === 'update') {

        $role = $_POST['role'] ?? 'cashier';
        $verified = isset($_POST['is_verified']) ? 1 : 0;
        $active = isset($_POST['is_active']) ? 1 : 0;

        if (!in_array($role, ['admin', 'staff', 'cashier'], true)) {
            $error = 'Invalid role.';
        } elseif (
            $id === (int) current_user()['id']
            && $role !== 'admin'
        ) {
            $error = 'You cannot remove your own administrator role.';
        } else {
            $stmt = $conn->prepare(
                'UPDATE users
                 SET role = ?, is_verified = ?, is_active = ?
                 WHERE id = ?'
            );
            $stmt->bind_param(
                'siii',
                $role,
                $verified,
                $active,
                $id
            );
            $stmt->execute();

            log_activity(
                $conn,
                'UPDATE',
                'users',
                $id,
                'Role/account status updated'
            );

            flash('success', 'User updated.');
            redirect('admin/users.php');
        }

    } else {
        $error = 'Invalid action.';
    }
}

layout_header('User accounts');
?>

<?php if ($error): ?>
    <div class="alert alert-danger"><?= e($error) ?></div>
<?php endif; ?>

<div class="alert alert-info">
    Public registration can only create cashier accounts.
    Use this page to assign roles. Only verified and active
    accounts can log in. Deactivate former employees to
    preserve their transaction history.
</div>

<section class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Verified</th>
                    <th>Active</th>
                    <th>Save changes</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $q = $conn->query(
                    'SELECT id, name, email, role, is_verified, is_active
                     FROM users
                     ORDER BY created_at DESC'
                );

                while ($u = $q->fetch_assoc()):
                    $formId = 'user-update-' . (int) $u['id'];
                ?>
                    <tr>
                        <td>
                            <form id="<?= e($formId) ?>" method="post">
                                <?= csrf_field() ?>
                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?= (int) $u['id'] ?>"
                                >
                            </form>
                            <?= e($u['name']) ?>
                        </td>

                        <td><?= e($u['email']) ?></td>

                        <td>
                            <select form="<?= e($formId) ?>" name="role">
                                <?php foreach (['admin', 'staff', 'cashier'] as $r): ?>
                                    <option
                                        value="<?= e($r) ?>"
                                        <?= $u['role'] === $r ? 'selected' : '' ?>
                                    >
                                        <?= ucfirst($r) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>

                        <td>
                            <input
                                form="<?= e($formId) ?>"
                                type="checkbox"
                                name="is_verified"
                                <?= $u['is_verified'] ? 'checked' : '' ?>
                            >
                        </td>

                        <td>
                            <input
                                form="<?= e($formId) ?>"
                                type="checkbox"
                                name="is_active"
                                <?= $u['is_active'] ? 'checked' : '' ?>
                            >
                        </td>

                        <td>
                            <button
                                form="<?= e($formId) ?>"
                                type="submit"
                                class="btn btn-small"
                            >
                                Update
                            </button>
                        </td>

                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</section>

<?php layout_footer(); ?>
