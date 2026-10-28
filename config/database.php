<?php
declare(strict_types=1);

date_default_timezone_set('Asia/Manila');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
if (session_status() === PHP_SESSION_NONE) {
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_set_cookie_params([
        'httponly' => true,
        'secure' => $secure,
        'samesite' => 'Lax'
    ]);
    session_start();
}

define('APP_NAME', 'AC Store Management System');
$documentRoot = realpath($_SERVER['DOCUMENT_ROOT'] ?? '');
$applicationRoot = realpath(dirname(__DIR__));
$basePath = '';
if ($documentRoot !== false && $applicationRoot !== false && str_starts_with(strtolower($applicationRoot), strtolower($documentRoot))) {
    $basePath = str_replace(DIRECTORY_SEPARATOR, '/', substr($applicationRoot, strlen($documentRoot)));
}
$scheme = (!empty($_SERVER['HTTPS']) && strtolower((string) $_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
$serverName = $_SERVER['SERVER_NAME'] ?? 'localhost';
if (!preg_match('/\A[a-zA-Z0-9.-]+\z/', $serverName)) {
    $serverName = 'localhost';
}
$serverPort = (int) ($_SERVER['SERVER_PORT'] ?? 0);
$defaultPort = $scheme === 'https' ? 443 : 80;
$portSuffix = $serverPort > 0 && $serverPort !== $defaultPort ? ':' . $serverPort : '';
define('BASE_URL', $scheme . '://' . $serverName . $portSuffix . rtrim($basePath, '/'));
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ac_shop');
if (($_SESSION['_database_name'] ?? null) !== DB_NAME) {
    $_SESSION = [];
    session_regenerate_id(true);
}
$_SESSION['_database_name'] = DB_NAME;

try {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    $conn->set_charset('utf8mb4');
    $conn->query("SET time_zone = '+08:00'");
} catch (Throwable $e) {
    http_response_code(500);
    exit('Database connection failed. Import database/sari_sari_store.sql in phpMyAdmin and check config/database.php.');
}

function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
function redirect(string $path): never {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}
function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf_token'];
}
function verify_csrf(): void {
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(403);
        exit('Invalid request token. Please go back and try again.');
    }
}
function flash(string $type, string $message): void {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}
function get_flash(): ?array {
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}
