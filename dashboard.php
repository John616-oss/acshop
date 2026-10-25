<?php
require_once __DIR__ . '/includes/functions.php'; require_login();
$today = date('Y-m-d');
$r = $conn->query("SELECT COALESCE(SUM(total),0) v FROM sales WHERE DATE(created_at)='$today' AND status='completed'")->fetch_assoc();
$salesToday = (float)$r['v'];
$r = $conn->query('SELECT COUNT(*) v FROM products WHERE is_active=1')->fetch_assoc(); $products=(int)$r['v'];
$r = $conn->query('SELECT COUNT(*) v FROM products WHERE is_active=1 AND stock_quantity <= reorder_level')->fetch_assoc(); $low=(int)$r['v'];
$r = $conn->query("SELECT COUNT(*) v FROM restock_requests WHERE status='pending'")->fetch_assoc(); $pending=(int)$r['v'];
$chartStart = date('Y-m-01', strtotime('-11 months'));
$monthlySalesQuery = $conn->prepare(
	"SELECT DATE_FORMAT(created_at, '%Y-%m') AS month_key, SUM(total) AS amount
	 FROM sales
	 WHERE status = 'completed' AND created_at >= ?
	 GROUP BY month_key
	 ORDER BY month_key"
);
$monthlySalesQuery->bind_param('s', $chartStart);
$monthlySalesQuery->execute();
$monthlySalesResult = $monthlySalesQuery->get_result();
$topProductsQuery = $conn->prepare(
	"SELECT p.id, p.name, SUM(si.line_total) AS sales
	 FROM sale_items si
	 JOIN sales s ON s.id = si.sale_id
	 JOIN products p ON p.id = si.product_id
	 WHERE s.status = 'completed' AND s.created_at >= ?
	 GROUP BY p.id, p.name
	 ORDER BY sales DESC
	 LIMIT 5"
);
$topProductsQuery->bind_param('s', $chartStart);
$topProductsQuery->execute();
$topProducts = $topProductsQuery->get_result()->fetch_all(MYSQLI_ASSOC);
$topProductPeak = $topProducts ? (float) $topProducts[0]['sales'] : 0;
$salesByMonth = [];
for ($monthsAgo = 11; $monthsAgo >= 0; $monthsAgo--) {
	$monthTimestamp = strtotime(date('Y-m-01') . " -$monthsAgo months");
	$monthKey = date('Y-m', $monthTimestamp);
	$salesByMonth[$monthKey] = [
		'label' => date('M', $monthTimestamp),
		'amount' => 0.0
	];
}
while ($month = $monthlySalesResult->fetch_assoc()) {
	if (isset($salesByMonth[$month['month_key']])) {
		$salesByMonth[$month['month_key']]['amount'] = (float) $month['amount'];
	}
}
$monthlyPeak = max(array_column($salesByMonth, 'amount'));
$hasMonthlySales = $monthlyPeak > 0;
$maxMonthlySales = $monthlyPeak ?: 1;
$revenuePoints = [];
foreach (array_values($salesByMonth) as $index => $month) {
	$x = 20 + ($index * 560 / 11);
	$y = 190 - (($month['amount'] / $maxMonthlySales) * 150);
	$revenuePoints[] = number_format($x, 1, '.', '') . ',' . number_format($y, 1, '.', '');
}
$revenuePoints = implode(' ', $revenuePoints);
layout_header('Dashboard');
$canManageInventory = in_array(current_user()['role'], ['admin', 'staff'], true);
?>
<div class="grid four">
 <div class="stat-card"><span>Sales today</span><strong><?= money($salesToday) ?></strong><small>Completed transactions</small></div>
 <div class="stat-card"><span>Active products</span><strong><?= $products ?></strong><small>Listed inventory items</small></div>
 <div class="stat-card warning-stat"><span>Low stock</span><strong><?= $low ?></strong><small>At or below reorder level</small></div>
 <div class="stat-card"><span>Pending restock</span><strong><?= $pending ?></strong><small>Awaiting review</small></div>
</div>
<div class="dashboard-charts">
 <section class="card dashboard-chart">
  <div class="dashboard-chart-heading"><div><h2>Sales performance</h2><p>Completed sales by month</p></div><span class="chart-period">Last 12 months</span></div>
  <div class="bar-chart" role="img" aria-label="Monthly completed sales for the last 12 months">
   <div class="bar-chart-grid" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
	<?php if ($hasMonthlySales): ?>
   <div class="bar-chart-columns">
	<?php foreach ($salesByMonth as $month): ?>
	 <?php $barHeight = ($month['amount'] / $maxMonthlySales) * 100; ?>
	 <div class="bar-chart-column" title="<?= e($month['label'] . ': ' . money($month['amount'])) ?>">
	  <div class="bar-chart-track"><span class="bar-chart-bar" style="height: <?= number_format($barHeight, 2, '.', '') ?>%"></span></div>
	  <span class="bar-chart-label"><?= e($month['label']) ?></span>
	 </div>
	<?php endforeach; ?>
   </div>
	<?php else: ?>
	<p class="chart-empty">No completed sales recorded in the last 12 months.</p>
	<?php endif; ?>
  </div>
 </section>
 <section class="card dashboard-chart">
  <div class="dashboard-chart-heading"><div><h2>Revenue growth</h2><p>Monthly completed revenue</p></div><span class="chart-period">Last 12 months</span></div>
  <div class="line-chart-wrap" role="img" aria-label="Monthly revenue trend for the last 12 months">
   <svg class="line-chart" viewBox="0 0 600 220" preserveAspectRatio="none" aria-hidden="true">
	<line x1="20" y1="40" x2="580" y2="40"></line>
	<line x1="20" y1="90" x2="580" y2="90"></line>
	<line x1="20" y1="140" x2="580" y2="140"></line>
	<line x1="20" y1="190" x2="580" y2="190"></line>
	<?php if ($hasMonthlySales): ?>
	<polyline class="revenue-line" points="<?= e($revenuePoints) ?>"></polyline>
	<?php foreach (array_values($salesByMonth) as $index => $month): ?>
	 <?php $x = 20 + ($index * 560 / 11); $y = 190 - (($month['amount'] / $maxMonthlySales) * 150); ?>
	 <circle class="revenue-point" cx="<?= number_format($x, 1, '.', '') ?>" cy="<?= number_format($y, 1, '.', '') ?>" r="3.5"><title><?= e($month['label'] . ': ' . money($month['amount'])) ?></title></circle>
	<?php endforeach; ?>
	<?php endif; ?>
   </svg>
   <?php if (!$hasMonthlySales): ?><p class="chart-empty">Revenue data will appear after completed sales.</p><?php endif; ?>
   <div class="line-chart-labels" aria-hidden="true">
	<?php foreach ($salesByMonth as $month): ?><span><?= e($month['label']) ?></span><?php endforeach; ?>
   </div>
  </div>
 </section>
</div>
<section class="card top-products-card">
 <div class="dashboard-chart-heading">
  <div><h2>Top 5 Products</h2><p>Ranked by completed sales value</p></div>
  <span class="chart-period">Last 12 months</span>
 </div>
 <?php if ($topProducts): ?>
 <div class="top-products-list">
  <?php foreach ($topProducts as $product): ?>
   <?php $productBarWidth = ((float) $product['sales'] / $topProductPeak) * 100; ?>
   <div class="top-product-row" title="<?= e($product['name'] . ': ' . money((float) $product['sales'])) ?>">
	<span class="top-product-name"><?= e($product['name']) ?></span>
	<span class="top-product-track"><i style="width: <?= number_format($productBarWidth, 2, '.', '') ?>%"></i></span>
	<strong><?= money((float) $product['sales']) ?></strong>
   </div>
  <?php endforeach; ?>
 </div>
 <?php else: ?>
 <p class="top-products-empty">Product sales will appear after completed transactions.</p>
 <?php endif; ?>
</section>
<div class="grid two section-gap"><section class="card"><h2>Quick actions</h2><div class="quick-links">
<a href="<?= BASE_URL ?>/cashier/pos.php">🧾 Open POS</a><?php if($canManageInventory): ?><a href="<?= BASE_URL ?>/staff/inventory.php">📦 View inventory</a><a href="<?= BASE_URL ?>/staff/restocking.php">🚚 Restocking requests</a><?php endif; ?><a href="<?= BASE_URL ?>/reports/index.php">📊 View reports</a></div></section>
<section class="card"><h2>Low-stock watchlist</h2><div class="table-wrap"><table><thead><tr><th>Product</th><th>Stock</th><th>Reorder at</th></tr></thead><tbody>
<?php $q=$conn->query('SELECT name,stock_quantity,reorder_level FROM products WHERE is_active=1 AND stock_quantity <= reorder_level ORDER BY stock_quantity ASC LIMIT 8'); if(!$q->num_rows) echo '<tr><td colspan="3">All products are above reorder level.</td></tr>'; while($x=$q->fetch_assoc()): ?><tr><td><?= e($x['name']) ?></td><td><span class="badge badge-danger"><?= (int)$x['stock_quantity'] ?></span></td><td><?= (int)$x['reorder_level'] ?></td></tr><?php endwhile; ?>
</tbody></table></div></section></div>
<?php layout_footer(); ?>
