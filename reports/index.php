
<?php
require_once __DIR__ . '/../includes/functions.php';

require_role(['admin', 'staff', 'cashier']);

// Validate date filters
$from = $_GET['from'] ?? date('Y-m-01');
$to   = $_GET['to'] ?? date('Y-m-d');

$isValidFrom = is_string($from)
    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)
    && checkdate((int) substr($from, 5, 2), (int) substr($from, 8, 2), (int) substr($from, 0, 4));

$isValidTo = is_string($to)
    && preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)
    && checkdate((int) substr($to, 5, 2), (int) substr($to, 8, 2), (int) substr($to, 0, 4));

if (!$isValidFrom || !$isValidTo || $from > $to) {
    $from = date('Y-m-01');
    $to = date('Y-m-d');
}

$user = current_user();
$isCashier = $user['role'] === 'cashier';
$uid = (int) $user['id'];

// Sales transactions
$sql = "
    SELECT
        s.id,
        s.receipt_no,
        s.total,
        s.payment_received,
        s.change_amount,
        s.created_at,
        u.name AS cashier
    FROM sales s
    JOIN users u ON u.id = s.cashier_id
    WHERE s.status = 'completed'
      AND DATE(s.created_at) >= ?
      AND DATE(s.created_at) <= ?
";

if ($isCashier) {
    $sql .= " AND s.cashier_id = ?";
}

$sql .= " ORDER BY s.created_at DESC";

$stmt = $conn->prepare($sql);

if ($isCashier) {
    $stmt->bind_param('ssi', $from, $to, $uid);
} else {
    $stmt->bind_param('ss', $from, $to);
}

$stmt->execute();
$sales = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate total sales
$total = 0;

foreach ($sales as $sale) {
    $total += (float) $sale['total'];
}

// Best-selling products
$topSql = "
    SELECT
        p.name,
        SUM(si.quantity) AS units,
        SUM(si.line_total) AS sales
    FROM sale_items si
    JOIN sales s ON s.id = si.sale_id
    JOIN products p ON p.id = si.product_id
    WHERE s.status = 'completed'
      AND DATE(s.created_at) >= ?
      AND DATE(s.created_at) <= ?
";

if ($isCashier) {
    $topSql .= " AND s.cashier_id = ?";
}

$topSql .= "
    GROUP BY p.id, p.name
    ORDER BY units DESC
    LIMIT 10
";

$top = $conn->prepare($topSql);

if ($isCashier) {
    $top->bind_param('ssi', $from, $to, $uid);
} else {
    $top->bind_param('ss', $from, $to);
}

$top->execute();
$topProducts = $top->get_result()->fetch_all(MYSQLI_ASSOC);
$top->close();

layout_header('Sales reports');
?>
<style>
    .reports-page {
    --primary: #13795b;
    --primary-dark: #0c6047;
    --primary-light: #e6f5ef;
    --text: #172b4d;
    --text-muted: #718096;
    --border: #e1e8f0;
    --surface: #ffffff;
    --surface-soft: #f5f8fc;
    --blue: #5488e8;
    --orange: #e7a33c;

    width: 100%;
    max-width: 100%;
    color: var(--text);
    font-family: inherit;
    line-height: 1.5;
    box-sizing: border-box;
}

.reports-page *,
.reports-page *::before,
.reports-page *::after {
    box-sizing: border-box;
}

.reports-page h2,
.reports-page h3,
.reports-page p {
    margin-top: 0;
}

.reports-page button,
.reports-page input {
    font: inherit;
}

/* =========================================
   PAGE INTRODUCTION
========================================= */

.reports-page .report-intro {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 16px;
    width: 100%;
    margin: 8px 0 24px;
}

.reports-page .report-intro h2 {
    margin: 0 0 6px;
    color: var(--text);
    font-size: clamp(21px, 2vw, 26px);
    font-weight: 800;
    line-height: 1.3;
}

.reports-page .report-intro p {
    margin: 0;
    color: var(--text-muted);
    font-size: 14px;
}

.reports-page .report-tag {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    min-height: 34px;
    padding: 7px 13px;
    border: 1px solid #cdeade;
    border-radius: 30px;
    background: var(--primary-light);
    color: var(--primary-dark);
    font-size: 12px;
    font-weight: 700;
    white-space: nowrap;
}

/* =========================================
   COMMON PANELS
========================================= */

.reports-page .report-panel {
    width: 100%;
    min-width: 0;
    margin: 0 0 22px;
    padding: 24px;
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: var(--surface);
    box-shadow: 0 6px 24px rgba(25, 48, 78, 0.045);
}

.reports-page .panel-heading {
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 12px;
    width: 100%;
    margin: 0 0 20px;
}

.reports-page .panel-heading h3 {
    margin: 0;
    color: var(--text);
    font-size: 18px;
    font-weight: 750;
    line-height: 1.4;
}

.reports-page .panel-heading p {
    margin: 5px 0 0;
    color: var(--text-muted);
    font-size: 13px;
    line-height: 1.5;
}

/* =========================================
   FILTER FORM - ALIGNED
========================================= */

.reports-page .filter-grid {
    display: grid;
    grid-template-columns:
        minmax(0, 1fr)
        minmax(0, 1fr)
        145px
        145px;
    align-items: end;
    gap: 14px;
    width: 100%;
    margin: 0;
    padding: 0;
}

.reports-page .filter-grid label {
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    gap: 8px;
    width: 100%;
    min-width: 0;
    margin: 0;
    padding: 0;
    color: #40516b;
    font-size: 13px;
    font-weight: 650;
    line-height: 18px;
}

.reports-page .filter-grid input[type="date"] {
    display: block;
    width: 100%;
    height: 44px;
    min-height: 44px;
    margin: 0;
    padding: 0 12px;
    border: 1px solid #d5dfeb;
    border-radius: 9px;
    outline: none;
    background: #fff;
    color: var(--text);
    font-family: inherit;
    font-size: 13px;
    font-weight: 500;
    line-height: normal;
    box-shadow: none;
    transition: border-color 0.2s, box-shadow 0.2s;
}

.reports-page .filter-grid input[type="date"]:hover {
    border-color: #aabbd0;
}

.reports-page .filter-grid input[type="date"]:focus {
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(19, 121, 91, 0.10);
}

/* =========================================
   BUTTONS
========================================= */

.reports-page .report-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 7px;
    width: 100%;
    height: 44px;
    min-height: 44px;
    margin: 0;
    padding: 0 12px;
    border: 1px solid transparent;
    border-radius: 9px;
    background: var(--primary);
    color: #fff;
    font-family: inherit;
    font-size: 12px;
    font-weight: 750;
    line-height: 1;
    text-align: center;
    text-decoration: none;
    white-space: nowrap;
    cursor: pointer;
    transition:
        background 0.2s ease,
        border-color 0.2s ease,
        color 0.2s ease,
        transform 0.2s ease;
}

.reports-page .report-btn:hover {
    background: var(--primary-dark);
    color: #fff;
    transform: translateY(-1px);
}

.reports-page .report-btn:focus-visible {
    outline: 3px solid rgba(19, 121, 91, 0.2);
    outline-offset: 2px;
}

.reports-page .report-btn-outline {
    border-color: #d5dfeb;
    background: #fff;
    color: #344660;
}

.reports-page .report-btn-outline:hover {
    border-color: #a7d8c5;
    background: #f0faf6;
    color: var(--primary-dark);
}

/* =========================================
   SUMMARY CARDS
========================================= */

.reports-page .summary-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    align-items: stretch;
    gap: 18px;
    width: 100%;
    margin: 0 0 22px;
}

.reports-page .summary-card {
    position: relative;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-width: 0;
    min-height: 205px;
    padding: 23px;
    overflow: hidden;
    border: 1px solid var(--border);
    border-radius: 16px;
    background: var(--surface);
    box-shadow: 0 6px 24px rgba(25, 48, 78, 0.045);
    transition: transform 0.2s, box-shadow 0.2s;
}

.reports-page .summary-card::before {
    position: absolute;
    top: 0;
    right: 0;
    left: 0;
    height: 4px;
    background: var(--primary);
    content: "";
}

.reports-page .summary-card.sales-card::before {
    background: #299b79;
}

.reports-page .summary-card.count-card::before {
    background: var(--blue);
}

.reports-page .summary-card.period-card::before {
    background: var(--orange);
}

.reports-page .summary-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 28px rgba(25, 48, 78, 0.08);
}

.reports-page .summary-icon {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    width: 42px;
    height: 42px;
    margin: 0 0 16px;
    border-radius: 12px;
    background: var(--primary-light);
    font-size: 19px;
}

.reports-page .count-card .summary-icon {
    background: #eaf1ff;
}

.reports-page .period-card .summary-icon {
    background: #fff3df;
}

.reports-page .summary-label {
    display: block;
    margin: 0 0 9px;
    color: var(--text-muted);
    font-size: 13px;
    font-weight: 550;
}

.reports-page .summary-value {
    display: block;
    max-width: 100%;
    margin: 0;
    color: var(--text);
    font-size: clamp(23px, 2.4vw, 31px);
    font-weight: 800;
    line-height: 1.3;
    overflow-wrap: anywhere;
}

.reports-page .summary-note {
    display: block;
    margin-top: auto;
    padding-top: 12px;
    color: var(--text-muted);
    font-size: 12px;
    line-height: 1.5;
}

/* =========================================
   TABLE CONTAINER
========================================= */

.reports-page .table-scroll {
    display: block;
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
    border-radius: 9px;
    scrollbar-width: thin;
    scrollbar-color: #cbd5e1 transparent;
}

.reports-page table {
    width: 100%;
    min-width: 720px;
    margin: 0;
    border-collapse: separate;
    border-spacing: 0;
    background: #fff;
    font-size: 13px;
    text-align: left;
}

.reports-page thead th {
    height: 46px;
    padding: 13px 14px;
    border-top: 1px solid #e8edf4;
    border-bottom: 1px solid #e2e9f1;
    background: var(--surface-soft);
    color: #63738b;
    font-size: 11px;
    font-weight: 750;
    letter-spacing: 0.045em;
    line-height: 1.4;
    text-align: left;
    text-transform: uppercase;
    white-space: nowrap;
}

.reports-page thead th:first-child {
    border-top-left-radius: 8px;
}

.reports-page thead th:last-child {
    border-top-right-radius: 8px;
}

.reports-page tbody td {
    height: 55px;
    padding: 13px 14px;
    border-bottom: 1px solid #edf1f6;
    background: #fff;
    color: #344660;
    font-size: 13px;
    line-height: 1.5;
    vertical-align: middle;
}

.reports-page tbody tr:last-child td {
    border-bottom: none;
}

.reports-page tbody tr {
    transition: background 0.15s ease;
}

.reports-page tbody tr:hover td {
    background: #f8fbfa;
}

/* =========================================
   TABLE CONTENT
========================================= */

.reports-page .receipt-number {
    color: var(--primary-dark);
    font-weight: 750;
    white-space: nowrap;
}

.reports-page .money-cell {
    color: #243853;
    font-weight: 700;
    white-space: nowrap;
    font-variant-numeric: tabular-nums;
}

.reports-page .table-link {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 32px;
    padding: 6px 10px;
    border: 1px solid #cdeade;
    border-radius: 7px;
    background: #eff9f5;
    color: var(--primary-dark);
    font-size: 12px;
    font-weight: 750;
    text-decoration: none;
    white-space: nowrap;
    transition: background 0.2s, border-color 0.2s;
}

.reports-page .table-link:hover {
    border-color: #a7d8c5;
    background: #dff3ea;
}

.reports-page .rank-number {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 30px;
    height: 30px;
    border-radius: 9px;
    background: #edf7f2;
    color: var(--primary-dark);
    font-size: 12px;
    font-weight: 800;
}

.reports-page .product-name {
    color: #243853;
    font-weight: 700;
}

.reports-page .units-pill {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-height: 27px;
    padding: 5px 10px;
    border-radius: 20px;
    background: #eef3ff;
    color: #385ea8;
    font-size: 12px;
    font-weight: 750;
    white-space: nowrap;
}

/* =========================================
   EMPTY STATE
========================================= */

.reports-page .empty-state {
    height: auto;
    padding: 38px 15px;
    color: var(--text-muted);
    text-align: center;
    line-height: 1.7;
}

.reports-page .empty-state strong {
    display: block;
    margin-bottom: 5px;
    color: #344660;
    font-size: 14px;
}

.reports-page .empty-icon {
    display: block;
    margin-bottom: 9px;
    font-size: 29px;
}

/* =========================================
   TABLET RESPONSIVENESS
========================================= */

@media (max-width: 1000px) {
    .reports-page .filter-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 17px 14px;
    }

    .reports-page .summary-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
    }

    .reports-page .summary-card:last-child {
        grid-column: 1 / -1;
        min-height: 175px;
    }
}

/* =========================================
   MOBILE RESPONSIVENESS
========================================= */

@media (max-width: 600px) {
    .reports-page .report-intro {
        align-items: flex-start;
        flex-direction: column;
        margin-bottom: 18px;
    }

    .reports-page .report-intro h2 {
        font-size: 22px;
    }

    .reports-page .report-panel {
        padding: 17px;
        margin-bottom: 16px;
        border-radius: 13px;
    }

    .reports-page .panel-heading {
        align-items: flex-start;
        margin-bottom: 17px;
    }

    .reports-page .panel-heading h3 {
        font-size: 16px;
    }

    .reports-page .filter-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 15px;
    }

    .reports-page .filter-grid input[type="date"] {
        height: 45px;
    }

    .reports-page .filter-grid .report-btn {
        width: 100%;
        height: 45px;
    }

    .reports-page .summary-grid {
        grid-template-columns: minmax(0, 1fr);
        gap: 13px;
        margin-bottom: 16px;
    }

    .reports-page .summary-card,
    .reports-page .summary-card:last-child {
        grid-column: auto;
        min-height: 175px;
        padding: 20px;
    }

    .reports-page .summary-value {
        font-size: 26px;
    }

    .reports-page .table-scroll {
        margin: 0;
    }

    .reports-page table {
        min-width: 700px;
    }
}

/* =========================================
   PRINT REPORT
========================================= */

@media print {
    @page {
        size: landscape;
        margin: 12mm;
    }

    body {
        background: #fff !important;
        color: #000 !important;
    }

    .topbar,
    footer,
    .page-heading,
    .report-intro,
    .filter-panel,
    .no-print {
        display: none !important;
    }

    .container {
        width: 100% !important;
        max-width: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .reports-page {
        width: 100% !important;
        color: #000 !important;
    }

    .reports-page .report-panel,
    .reports-page .summary-card {
        margin-bottom: 12px !important;
        border: 1px solid #ccc !important;
        border-radius: 5px !important;
        box-shadow: none !important;
        break-inside: avoid;
    }

    .reports-page .summary-grid {
        grid-template-columns: repeat(3, minmax(0, 1fr)) !important;
        gap: 10px !important;
    }

    .reports-page .summary-card,
    .reports-page .summary-card:last-child {
        grid-column: auto !important;
        min-height: 130px !important;
        padding: 14px !important;
    }

    .reports-page .table-scroll {
        overflow: visible !important;
    }

    .reports-page table {
        width: 100% !important;
        min-width: 0 !important;
        font-size: 10px !important;
    }

    .reports-page thead th,
    .reports-page tbody td {
        height: auto !important;
        padding: 7px !important;
        border: 1px solid #ddd !important;
        color: #000 !important;
        font-size: 10px !important;
    }

    .reports-page .table-link,
    .reports-page .report-tag {
        border: none !important;
        background: transparent !important;
        color: #000 !important;
    }
</style>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <div class="reports-page">

    <div class="report-intro">
        <div>
            <h2>📊 Sales Overview</h2>
            <p>Track completed transactions and monitor your store's performance.</p>
        </div>

        <span class="report-tag">● Completed sales report</span>
    </div>

    <!-- Date filters -->
    <section class="report-panel filter-panel">
        <div class="panel-heading">
            <div>
                <h3>🔎 Filter report</h3>
                <p>Select a date range to view sales performance.</p>
            </div>
        </div>

        <form method="get" class="filter-grid">
            <label>
                From date
                <input
                    type="date"
                    name="from"
                    value="<?= e($from) ?>"
                    max="9999-12-31"
                    required
                >
            </label>

            <label>
                To date
                <input
                    type="date"
                    name="to"
                    value="<?= e($to) ?>"
                    max="9999-12-31"
                    required
                >
            </label>

            <button type="submit" class="report-btn">
                🔍 Apply filters
            </button>

            <button
                type="button"
                class="report-btn report-btn-outline"
                onclick="window.print()"
            >
                🖨️ Print report
            </button>
        </form>
    </section>

    <!-- Summary cards -->
    <div class="summary-grid">

        <div class="summary-card sales-card">
            <span class="summary-icon">💰</span>
            <span class="summary-label">Total sales</span>
            <strong class="summary-value"><?= money($total) ?></strong>
            <span class="summary-note">Completed transactions only</span>
        </div>

        <div class="summary-card count-card">
            <span class="summary-icon">🧾</span>
            <span class="summary-label">Completed transactions</span>
            <strong class="summary-value"><?= count($sales) ?></strong>
            <span class="summary-note">Sales within selected dates</span>
        </div>

        <div class="summary-card period-card">
            <span class="summary-icon">📅</span>
            <span class="summary-label">Report period</span>
            <strong class="summary-value" style="font-size:18px">
                <?= e(date('M d, Y', strtotime($from))) ?>
            </strong>
            <span class="summary-note">
                Until <?= e(date('M d, Y', strtotime($to))) ?>
            </span>
        </div>

    </div>

    <!-- Sales transactions -->
    <section class="report-panel">
        <div class="panel-heading">
            <div>
                <h3>🧾 Sales transactions</h3>
                <p>Review receipts, payments, and change for each sale.</p>
            </div>

            <span class="report-tag">
                <?= count($sales) ?> records
            </span>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Receipt no.</th>
                        <th>Date / Time</th>
                        <th>Cashier</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Change</th>
                        <th class="no-print">Action</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($sales): ?>
                    <?php foreach ($sales as $r): ?>
                        <tr>
                            <td>
                                <span class="receipt-number">
                                    <?= e($r['receipt_no']) ?>
                                </span>
                            </td>

                            <td><?= e($r['created_at']) ?></td>

                            <td><?= e($r['cashier']) ?></td>

                            <td class="money-cell">
                                <?= money((float) $r['total']) ?>
                            </td>

                            <td>
                                <?= money((float) $r['payment_received']) ?>
                            </td>

                            <td>
                                <?= money((float) $r['change_amount']) ?>
                            </td>

                            <td class="no-print">
                                <a
                                    class="table-link"
                                    href="<?= BASE_URL ?>/cashier/receipt.php?id=<?= (int) $r['id'] ?>"
                                >
                                    View receipt →
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="7" class="empty-state">
                            <span class="empty-icon">🧾</span>
                            <strong>No sales found</strong>
                            <div>No completed transactions for this date range.</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <!-- Best-selling products -->
    <section class="report-panel">
        <div class="panel-heading">
            <div>
                <h3>🏆 Best-selling products</h3>
                <p>Top 10 products ranked by total quantity sold.</p>
            </div>

            <span class="report-tag">Top 10 products</span>
        </div>

        <div class="table-scroll">
            <table>
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product name</th>
                        <th>Units sold</th>
                        <th>Sales value</th>
                    </tr>
                </thead>

                <tbody>
                <?php if ($topProducts): ?>
                    <?php foreach ($topProducts as $index => $p): ?>
                        <tr>
                            <td>
                                <span class="rank-number">
                                    <?= $index + 1 ?>
                                </span>
                            </td>

                            <td>
                                <span class="product-name">
                                    <?= e($p['name']) ?>
                                </span>
                            </td>

                            <td>
                                <span class="units-pill">
                                    <?= (int) $p['units'] ?> units
                                </span>
                            </td>

                            <td class="money-cell">
                                <?= money((float) $p['sales']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                <?php else: ?>
                    <tr>
                        <td colspan="4" class="empty-state">
                            <span class="empty-icon">📦</span>
                            <strong>No product sales yet</strong>
                            <div>Completed product sales will appear here.</div>
                        </td>
                    </tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

</div>
</body>
</html>

<?php layout_footer(); ?>
