<?php
require_once __DIR__ . '/includes/functions.php'; require_login();
$today = date('Y-m-d');
$r = $conn->query("SELECT COALESCE(SUM(total),0) v FROM sales WHERE DATE(created_at)='$today' AND status='completed'")->fetch_assoc();
$salesToday = (float)$r['v'];
$r = $conn->query('SELECT COUNT(*) v FROM products WHERE is_active=1')->fetch_assoc(); $products=(int)$r['v'];
$r = $conn->query('SELECT COUNT(*) v FROM products WHERE is_active=1 AND stock_quantity <= reorder_level')->fetch_assoc(); $low=(int)$r['v'];
$r = $conn->query("SELECT COUNT(*) v FROM restock_requests WHERE status='pending'")->fetch_assoc(); $pending=(int)$r['v'];
layout_header('Dashboard');
$canManageInventory = in_array(current_user()['role'], ['admin', 'staff'], true);
?>
<div class="grid four">
 <div class="stat-card"><span>Sales today</span><strong><?= money($salesToday) ?></strong><small>Completed transactions</small></div>
 <div class="stat-card"><span>Active products</span><strong><?= $products ?></strong><small>Listed inventory items</small></div>
 <div class="stat-card warning-stat"><span>Low stock</span><strong><?= $low ?></strong><small>At or below reorder level</small></div>
 <div class="stat-card"><span>Pending restock</span><strong><?= $pending ?></strong><small>Awaiting review</small></div>
</div>
<div class="grid two section-gap"><section class="card"><h2>Quick actions</h2><div class="quick-links">
<a href="<?= BASE_URL ?>/cashier/pos.php">🧾 Open POS</a><?php if($canManageInventory): ?><a href="<?= BASE_URL ?>/staff/inventory.php">📦 View inventory</a><a href="<?= BASE_URL ?>/staff/restocking.php">🚚 Restocking requests</a><?php endif; ?><a href="<?= BASE_URL ?>/reports/index.php">📊 View reports</a></div></section>
<section class="card"><h2>Low-stock watchlist</h2><div class="table-wrap"><table><thead><tr><th>Product</th><th>Stock</th><th>Reorder at</th></tr></thead><tbody>
<?php $q=$conn->query('SELECT name,stock_quantity,reorder_level FROM products WHERE is_active=1 AND stock_quantity <= reorder_level ORDER BY stock_quantity ASC LIMIT 8'); if(!$q->num_rows) echo '<tr><td colspan="3">All products are above reorder level.</td></tr>'; while($x=$q->fetch_assoc()): ?><tr><td><?= e($x['name']) ?></td><td><span class="badge badge-danger"><?= (int)$x['stock_quantity'] ?></span></td><td><?= (int)$x['reorder_level'] ?></td></tr><?php endwhile; ?>
</tbody></table></div></section></div>
<?php layout_footer(); ?>
