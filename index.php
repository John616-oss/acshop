<?php
require_once __DIR__ . '/includes/functions.php';
if (current_user()) redirect('dashboard.php');
layout_header('Welcome');
?>
<section class="hero landing-hero">
  <div class="hero-copy">
    <h2>Know your stocks.<br><span>Track every sale.</span></h2>
    <p>Manage products, sales, inventory, restocking, and reports in one simple web application built for a neighborhood sari-sari store.</p>
    <div class="actions"><a class="btn" href="<?= BASE_URL ?>/auth/login.php">Sign in</a><a class="btn btn-outline" href="<?= BASE_URL ?>/auth/register.php">Create account</a></div>
  </div>
</section>
<section class="landing-features" aria-label="Store features">
 <article class="card landing-feature"><div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="m4 7 8-4 8 4-8 4-8-4Z"/><path d="M4 7v10l8 4 8-4V7M12 11v10"/></svg></div><div><h3>Inventory control</h3><p>Track stock quantities, movements, and low-stock items.</p></div></article>
 <article class="card landing-feature"><div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6 3h12v18l-3-2-3 2-3-2-3 2V3Z"/><path d="M9 8h6M9 12h6M9 16h3"/></svg></div><div><h3>Fast checkout</h3><p>Record sales, validate payment, and print receipts.</p></div></article>
 <article class="card landing-feature"><div class="feature-icon"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 19V5M4 19h17"/><path d="M8 16v-4M13 16V8M18 16V5"/></svg></div><div><h3>Useful reports</h3><p>Filter transactions and review store performance.</p></div></article>
</section>
<?php layout_footer(); ?>



