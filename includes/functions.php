<?php
require_once __DIR__ . '/../config/database.php';


function current_user(): ?array {
    return $_SESSION['user'] ?? null;
}

function require_login(): void {
    if (!current_user()) { flash('warning', 'Please log in first.'); redirect('auth/login.php'); }
}
function require_role(array $roles): void {
    require_login();
    if (!in_array($_SESSION['user']['role'], $roles, true)) {
        http_response_code(403);
        exit('403 Forbidden: You do not have permission to access this page.');
    }
}
function log_activity(mysqli $conn, string $action, string $entity = '', ?int $entityId = null, string $details = ''): void {
    $uid = $_SESSION['user']['id'] ?? null;
    $ip = substr($_SERVER['REMOTE_ADDR'] ?? 'unknown', 0, 45);
    $stmt = $conn->prepare('INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('ississ', $uid, $action, $entity, $entityId, $details, $ip);
    $stmt->execute();
}
function money(float $n): string { return '₱' . number_format($n, 2); }
function csrf_field(): string { return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">'; }
function layout_header(string $title): void {
    $user = current_user();
    $flash = get_flash();
    $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
    $navLink = static function (string $path, string $label) use ($currentPath): string {
        $href = BASE_URL . '/' . $path;
        $active = str_ends_with($currentPath, '/' . $path);
        return '<a href="' . e($href) . '"' . ($active ? ' aria-current="page"' : '') . '>' . e($label) . '</a>';
    };
    echo '<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
    echo '<title>' . e($title) . ' | ' . e(APP_NAME) . '</title><link rel="stylesheet" href="' . BASE_URL . '/assets/css/style.css?v=5">';
    echo '<script defer src="' . BASE_URL . '/assets/js/app.js"></script></head><body>';
    echo '<header class="topbar"><a class="brand" href="' . BASE_URL . '/index.php"><span class="brand-mark" aria-hidden="true">🛒</span><span>Sari-Sari Store</span></a>';
    if ($user) {
        echo '<nav id="primary-navigation" class="primary-navigation" aria-label="Main navigation">';
        echo $navLink('dashboard.php', 'Dashboard');
        if ($user['role'] === 'admin') {
            echo $navLink('admin/products.php', 'Products');
            echo $navLink('admin/categories.php', 'Categories');
            echo $navLink('admin/users.php', 'Users');
            echo $navLink('admin/suppliers.php', 'Suppliers');
            echo $navLink('admin/logs.php', 'Activity logs');
        }
        if (in_array($user['role'], ['admin','staff'], true)) {
            echo $navLink('staff/inventory.php', 'Inventory');
            echo $navLink('staff/restocking.php', 'Restocking');
        }
        echo $navLink('cashier/pos.php', 'POS');
        echo $navLink('reports/index.php', 'Reports');
        echo '<span class="user-chip"><span class="user-avatar" aria-hidden="true">' . e(strtoupper(substr($user['name'] ?? 'U', 0, 1))) . '</span><span>' . e($user['name'] ?? 'User') . '<small>' . e(ucfirst($user['role'] ?? 'staff')) . '</small></span></span>';
        echo '<a class="logout-link" href="' . BASE_URL . '/auth/logout.php">Log out</a></nav>';
    } else {
        echo '<nav class="primary-navigation" aria-label="Main navigation"><a href="' . BASE_URL . '/auth/login.php">Sign in</a><a class="btn btn-small" href="' . BASE_URL . '/auth/register.php">Create account</a></nav>';
    }
    echo '</header><main class="container" id="page-content">';
    if ($flash) echo '<div class="alert alert-' . e($flash['type']) . '">' . e($flash['message']) . '</div>';
    echo '<div class="page-heading"><div><p class="eyebrow">STORE OPERATIONS</p><h1>' . e($title) . '</h1></div><span class="date-pill">' . date('M d, Y') . '</span></div>';
}
function layout_footer(): void { echo '</main><footer> Sari-Sari Store Management System · PHP + MySQLi · Local demo project</footer></body></html>'; }
