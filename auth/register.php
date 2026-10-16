<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mail.php';

if (current_user()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();

    $name = trim($_POST['name'] ?? '');
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (
        $name === '' ||
        !filter_var($email, FILTER_VALIDATE_EMAIL) ||
        strlen($password) < 8
    ) {
        $error = 'Enter a name, valid email, and password with at least 8 characters.';
    } else {
        $stmt = $conn->prepare(
            'SELECT id FROM users WHERE email = ? LIMIT 1'
        );
        $stmt->bind_param('s', $email);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $role = 'cashier';

            // New accounts must be unverified and inactive.
            $isVerified = 0;
            $isActive = 0;

            $token = bin2hex(random_bytes(32));
            $tokenHash = hash('sha256', $token);
            $expires = date('Y-m-d H:i:s', time() + 1800);

            try {
                $conn->begin_transaction();

                $stmt = $conn->prepare(
                    'INSERT INTO users
                    (name, email, password_hash, role, is_verified, is_active)
                    VALUES (?, ?, ?, ?, ?, ?)'
                );
                $stmt->bind_param(
                    'ssssii',
                    $name,
                    $email,
                    $hash,
                    $role,
                    $isVerified,
                    $isActive
                );
                $stmt->execute();

                $uid = $conn->insert_id;

                $stmt = $conn->prepare(
                    'INSERT INTO email_verifications
                    (user_id, token_hash, expires_at)
                    VALUES (?, ?, ?)'
                );
                $stmt->bind_param('iss', $uid, $tokenHash, $expires);
                $stmt->execute();

                $conn->commit();

                // Send the verification email only after saving the account.
                $sent = send_verification_email($email, $name, $token);

                if ($sent) {
                    flash(
                        'success',
                        'Registration successful. Check your email to verify your address. Admin approval is also required before login.'
                    );
                } else {
                    flash(
                        'warning',
                        'Your account was created but the verification email could not be sent. Please use Resend verification after email delivery is configured.'
                    );
                }

                redirect('auth/login.php');

            } catch (Throwable $e) {
                $conn->rollback();
                error_log('Registration failed: ' . $e->getMessage());

                $error = 'Registration could not be completed. Please try again.';
            }
        }
    }
}

layout_header('Create account');
?>

<div class="auth-card card">
    <h2>Join your store team</h2>

    <p class="muted">
        New accounts require email verification and admin approval.
    </p>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post">
        <?= csrf_field() ?>

        <label>
            Full name
            <input
                name="name"
                required
                maxlength="120"
                value="<?= e($_POST['name'] ?? '') ?>"
            >
        </label>

        <label>
            Email address
            <input
                type="email"
                name="email"
                required
                maxlength="190"
                value="<?= e($_POST['email'] ?? '') ?>"
            >
        </label>

        <label>
            Password
            <input
                type="password"
                name="password"
                minlength="8"
                required
            >
        </label>

        <button class="btn full" type="submit">Create account</button>
    </form>

    <p class="muted">
        Already registered?
        <a href="<?= BASE_URL ?>/auth/login.php">Sign in</a>
    </p>
</div>

<?php layout_footer(); ?>