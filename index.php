<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('dashboard.php');
layout_header('Welcome');
?>
<section class="hero">
  <div><span class="tag">SMARTER STORE OPERATIONS</span><h2>Know your stocks.<br><span>Track every sale.</span></h2>
  <p>Manage products, sales, inventory, restocking, and reports in one simple web application built for a neighborhood sari-sari store.</p>
  <div class="actions"><a class="btn" href="<?= BASE_URL ?>/auth/login.php">Sign in</a><a class="btn btn-outline" href="<?= BASE_URL ?>/auth/register.php">Create account</a></div></div>
  <div class="hero-art"><div class="store-icon">🏪</div><div class="mini-stat"><b>Inventory</b><span>Organized</span></div><div class="mini-stat"><b>Sales</b><span>Recorded</span></div></div>
</section>
<div class="grid three">
 <article class="card"><div class="feature-icon">📦</div><h3>Inventory control</h3><p>Track stock quantities, movements, and low-stock items.</p></article>
 <article class="card"><div class="feature-icon">🧾</div><h3>Fast checkout</h3><p>Record sales, validate payment, and print receipts.</p></article>
 <article class="card"><div class="feature-icon">📊</div><h3>Useful reports</h3><p>Filter transactions and review store performance.</p></article>
</div>
<?php layout_footer(); ?>
