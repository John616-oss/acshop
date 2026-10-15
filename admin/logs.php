<?php
require_once __DIR__ . '/../includes/functions.php'; require_role(['admin']); $q=$conn->query('SELECT a.*,u.name username FROM activity_logs a LEFT JOIN users u ON u.id=a.user_id ORDER BY a.created_at DESC LIMIT 300'); layout_header('Activity logs');
?>
<section class="card"><p class="muted">Latest 300 recorded actions. Keep logs for accountability and review.</p><div class="table-wrap"><table><thead><tr><th>Date/time</th><th>User</th><th>Action</th><th>Record</th><th>Details</th><th>IP</th></tr></thead><tbody><?php while($x=$q->fetch_assoc()): ?><tr><td><?= e($x['created_at']) ?></td><td><?= e($x['username']??'System/Deleted user') ?></td><td><?= e($x['action']) ?></td><td><?= e($x['entity_type']) ?> #<?= (int)$x['entity_id'] ?></td><td><?= e($x['details']) ?></td><td><?= e($x['ip_address']) ?></td></tr><?php endwhile; ?></tbody></table></div></section>
<?php layout_footer(); ?>
