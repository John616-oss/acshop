<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
       
$stmt = $conn->prepare(
    'SELECT id, name, is_verified, is_active
     FROM users
     WHERE email = ?
     LIMIT 1'
);

$stmt->bind_param('s', $email);
$stmt->execute();

$u = $stmt->get_result()->fetch_assoc();

if ($u && (int)$u['is_active'] !== 1) {

    // Notify the admin instead of sending a verification link
    // directly to an inactive user.
    send_admin_inactive_account_notice(
        $email,
        $u['name']
    );

    flash(
        'success',
        'If the account exists, the request has been received.'
    );

} elseif ($u && (int)$u['is_verified'] !== 1) {

    $token = bin2hex(random_bytes(32));
    $hash = hash('sha256', $token);
    $expires = date(
        'Y-m-d H:i:s',
        time() + 1800
    );

    $stmt = $conn->prepare(
        'INSERT INTO email_verifications
         (user_id, token_hash, expires_at)
         VALUES (?, ?, ?)'
    );

    $stmt->bind_param(
        'iss',
        $u['id'],
        $hash,
        $expires
    );

    $stmt->execute();

    if (send_verification_email($email, $u['name'], $token)) {
        flash(
            'success',
            'If the account exists, a new link was sent.'
        );
    } else {
        flash(
            'warning',
            'The email could not be sent. Please try again later.'
        );
    }

} else {

    // Use a generic response to avoid revealing account status.
    flash(
        'success',
        'If the account exists, the request has been received.'
    );
}

redirect('auth/login.php');

    } else $error = 'Enter a valid email address.';
}
layout_header('Resend verification');
?>
<div class="auth-card card"><h2>Resend verification link</h2><?php if($error): ?><div class="alert alert-danger"><?= e($error) ?></div><?php endif; ?><form method="post"><?= csrf_field() ?><label>Email address<input type="email" name="email" required></label><button class="btn full">Send verification email</button></form></div>
<?php layout_footer(); ?>
