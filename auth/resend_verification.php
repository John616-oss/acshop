<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $stmt = $conn->prepare('SELECT id,name,is_verified FROM users WHERE email=? LIMIT 1');
        $stmt->bind_param('s', $email); $stmt->execute(); $u = $stmt->get_result()->fetch_assoc();
        if ($u && !$u['is_verified']) {
            $token = bin2hex(random_bytes(32)); $hash = hash('sha256', $token); $expires = date('Y-m-d H:i:s',time()+1800);
            $stmt = $conn->prepare('INSERT INTO email_verifications (user_id,token_hash,expires_at) VALUES (?,?,?)');
            $stmt->bind_param('iss',$u['id'],$hash,$expires); $stmt->execute();
            if (send_verification_email($email,$u['name'],$token)) flash('success','If the account exists and is unverified, a new link was sent.');
            else flash('warning','Mail delivery failed. Configure PHP mail/SMTP in your local environment.');
        } else flash('success','If the account exists and is unverified, a new link was sent.');
        redirect('auth/login.php');
    } else $error = 'Enter a valid email address.';
}
layout_header('Resend verification');
?>
<div class="auth-card card"><h2>Resend verification link</h2><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><label>Email address<input type="email" name="email" required></label><button class="btn full">Send verification email</button></form></div>
<?php layout_footer(); ?>
