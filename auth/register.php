<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';
if (current_user()) redirect('dashboard.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    if ($name === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8) {
        $error = 'Enter a name, valid email, and password with at least 8 characters.';
    } else {
        $stmt = $conn->prepare('SELECT id FROM users WHERE email=?');
        $stmt->bind_param('s', $email); $stmt->execute();
        if ($stmt->get_result()->num_rows) $error = 'That email is already registered.';
        else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'cashier'; // Public registration cannot assign privileged roles.
            $stmt = $conn->prepare('INSERT INTO users (name,email,password_hash,role,is_verified) VALUES (?,?,?,?,0)');
            $stmt->bind_param('ssss', $name, $email, $hash, $role); $stmt->execute();
            $uid = $conn->insert_id; $token = bin2hex(random_bytes(32)); $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time()+1800);
            $stmt = $conn->prepare('INSERT INTO email_verifications (user_id,token_hash,expires_at) VALUES (?,?,?)');
            $stmt->bind_param('iss', $uid, $tokenHash, $expires); $stmt->execute();
            $sent = send_verification_email($email, $name, $token);
            if ($sent) flash('success', 'Account created. Check your email for the verification link.');
            else flash('warning', 'Account created, but email could not be sent by this PHP setup. Configure SMTP/mail delivery, then use Resend verification.');
            redirect('auth/login.php');
        }
    }
}
layout_header('Create account');
?>
<div class="auth-card card"><h2>Join your store team</h2><p class="muted">Public registration creates a cashier account. Admins assign privileged roles.</p>
<?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?>
<form method="post"><?= csrf_field() ?><label>Full name<input name="name" required maxlength="120" value="<?= e($_POST['name'] ?? '') ?>"></label><label>Email address<input type="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>"></label><label>Password<input type="password" name="password" minlength="8" required></label><button class="btn full">Create account</button></form>
<p class="muted">Already registered? <a href="<?= BASE_URL ?>/auth/login.php">Sign in</a></p></div>
<?php layout_footer(); ?>
