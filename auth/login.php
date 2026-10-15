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
        $sql = "SELECT id, name, email, password_hash, role, is_verified
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
        if (!$user || !password_verify($password, $user['password_hash'])) {

            $error = 'Invalid email or password.';

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

<div class="auth-card card">

    <h2>Welcome back</h2>

    <p class="muted">
        Sign in to manage store operations.
    </p>

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
            <input
                type="password"
                name="password"
                required
                autocomplete="current-password"
            >
        </label>

        <button type="submit" class="btn full">
            Sign in
        </button>
    </form>

    <p class="muted">
        <a href="<?= BASE_URL ?>/auth/resend_verification.php">
            Resend verification email
        </a>
    </p>

    <p class="muted">
        No account?
        <a href="<?= BASE_URL ?>/auth/register.php">Register</a>
    </p>

</div>

<?php layout_footer(); ?>
