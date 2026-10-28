<?php
require_once __DIR__ . '/../includes/functions.php';

// Check database connection
if (!isset($conn) || !($conn instanceof mysqli)) {
    die("Database connection failed. Please check your database configuration.");
}

// Redirect if already logged in
if (current_user()) {
    redirect('dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    verify_csrf();

    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {

        // MySQLi prepared statement
        $sql = "SELECT id, name, email, password_hash, role, is_verified, is_active
                FROM users
                WHERE email = ?
                LIMIT 1";

        $stmt = $conn->prepare($sql);

        if (!$stmt) {
            die("SQL prepare failed: " . $conn->error);
        }

        $stmt->bind_param("s", $email);
        $stmt->execute();

        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        $stmt->close();

        // Validate email and password
        
        // Validate email and password
        if (
            !$user ||
            !password_verify($password, $user['password_hash'])
        ) {
            $error = 'Invalid email or password.';

        } elseif ((int) $user['is_active'] !== 1) {

            $error = 'This account is inactive. Please contact an administrator.';

        } elseif ((int) $user['is_verified'] !== 1) {

            $error = 'Please verify your email first. '
                . '<a href="' . BASE_URL
                . '/auth/resend_verification.php">Resend link</a>.';

        } else {


            // Regenerate session ID
            session_regenerate_id(true);

            $_SESSION['user'] = [
                'id'    => (int) $user['id'],
                'name'  => $user['name'],
                'email' => $user['email'],
                'role'  => $user['role']
            ];

            // Record login activity
            log_activity(
                $conn,
                'LOGIN',
                'users',
                (int) $user['id'],
                'User logged in'
            );

            // Redirect to dashboard
            redirect('dashboard.php');
        }
    }
}

layout_header('Sign in');
?>

<div class="auth-page">
    <section class="auth-welcome">
        <a class="auth-brand" href="<?= BASE_URL ?>/index.php"><span class="brand-mark" aria-hidden="true"><img src="<?= BASE_URL ?>/assets/images/Filipino_sari-sari_store_interior_20261025085641.jpg" alt=""></span>AC Store</a>
        <div class="auth-welcome-copy">
            <p class="auth-kicker">STORE MANAGEMENT</p>
            <h2>WELCOME<br><span>TO YOUR STORE</span></h2>
            <p>Keep products, inventory, and daily sales moving in one place.</p>
        </div>
    </section>

    <section class="auth-panel">
        <div class="auth-card">
            <p class="auth-form-kicker">AC STORE</p>
            <h2>Sign in</h2>
            <p class="muted">Access your store management account.</p>

            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="post">
                <?= csrf_field() ?>

                <label>
                    Email address
                    <input
                        type="email"
                        name="email"
                        required
                        autocomplete="email"
                        value="<?= htmlspecialchars($_POST['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                    >
                </label>

                <label>
                    Password
                    <span class="password-field">
                        <input
                            type="password"
                            id="login-password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >
                        <button class="password-toggle" type="button" data-password-toggle="login-password" aria-controls="login-password" aria-pressed="false">Show</button>
                    </span>
                </label>

                <button type="submit" class="btn full">Sign in</button>
            </form>

            <p class="auth-form-link">
                <a href="<?= BASE_URL ?>/auth/resend_verification.php">Resend verification email</a>
            </p>
            <p class="auth-form-switch">
                New to AC Store? <a href="<?= BASE_URL ?>/auth/register.php">Create account</a>
            </p>
        </div>
    </section>
</div>

<?php layout_footer(); ?>
