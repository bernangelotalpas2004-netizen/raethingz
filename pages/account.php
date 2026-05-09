<?php

require_once '../includes/auth.php';
require_once '../includes/db.php';
require_once '../includes/helpers.php';

startSession();
requireLogin('../pages/login.php');

$currentPage = 'account';
$csrfToken   = generateCSRF();
$user        = currentUser();
$pageTitle   = 'My Account — Raethingz Handicrafts';

// ── Fetch order history ────────────────────────────────────────────────────────
try {
    $pdo = getDB();

    $orderStmt = $pdo->prepare(
        'SELECT o.order_id, o.status, o.total_amount, o.created_at,
                COUNT(oi.item_id) AS item_count
         FROM   orders o
         LEFT JOIN order_items oi ON oi.order_id = o.order_id
         WHERE  o.user_id = :uid
         GROUP  BY o.order_id
         ORDER  BY o.created_at DESC
         LIMIT  10'
    );
    $orderStmt->execute([':uid' => $user['id']]);
    $orders = $orderStmt->fetchAll();

    $customStmt = $pdo->prepare(
        'SELECT request_id, name, description, colors, status, created_at
         FROM   custom_requests
         WHERE  user_id = :uid
         ORDER  BY created_at DESC
         LIMIT  5'
    );
    $customStmt->execute([':uid' => $user['id']]);
    $customRequests = $customStmt->fetchAll();

} catch (PDOException $e) {
    error_log('[Account DB Error] ' . $e->getMessage());
    $orders         = [];
    $customRequests = [];
}

// Status badge colours
$statusColors = [
    'pending'   => '#f59e0b',
    'confirmed' => '#3b82f6',
    'crafting'  => '#8b5cf6',
    'shipped'   => '#06b6d4',
    'delivered' => '#10b981',
    'cancelled' => '#ef4444',
    'reviewing' => '#f59e0b',
    'quoted'    => '#3b82f6',
    'accepted'  => '#10b981',
    'rejected'  => '#ef4444',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
  <style>
    .account-section { padding: clamp(40px,6vw,80px) 0; }
    .account-grid {
      display: grid;
      grid-template-columns: 280px 1fr;
      gap: 32px;
      align-items: start;
    }
    @media (max-width: 760px) { .account-grid { grid-template-columns: 1fr; } }
    .account-card {
      background: var(--clr-surface);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      overflow: hidden;
    }
    .account-card-header {
      background: linear-gradient(135deg, var(--clr-primary), #b5c9a0);
      padding: 28px 24px;
      text-align: center;
      color: #fff;
    }
    .account-avatar {
      width: 72px; height: 72px;
      border-radius: 50%;
      background: rgba(255,255,255,0.25);
      border: 3px solid rgba(255,255,255,0.6);
      display: flex; align-items: center; justify-content: center;
      font-size: 2rem; margin: 0 auto 12px;
    }
    .account-name { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; }
    .account-email { font-size: 0.8rem; opacity: 0.85; margin-top: 4px; }
    .account-nav { padding: 16px; }
    .account-nav a {
      display: flex; align-items: center; gap: 10px;
      padding: 10px 14px;
      border-radius: var(--radius-md);
      font-size: 0.88rem; font-weight: 500; color: var(--clr-dark);
      transition: background-color var(--duration) var(--ease), color var(--duration) var(--ease);
      margin-bottom: 2px;
    }
    .account-nav a:hover { background: var(--clr-primary-light); color: var(--clr-primary-dark); }
    .account-nav a.logout-link:hover { background: rgba(255,148,155,0.1); color: var(--clr-accent); }
    .content-card {
      background: var(--clr-surface);
      border-radius: var(--radius-lg);
      box-shadow: var(--shadow-md);
      overflow: hidden;
      margin-bottom: 24px;
    }
    .content-card-header {
      padding: 20px 24px;
      border-bottom: 1px solid var(--clr-border);
      display: flex; align-items: center; justify-content: space-between;
    }
    .content-card-title { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; }
    .content-card-body { padding: 0; }
    table { width: 100%; border-collapse: collapse; }
    th, td { padding: 14px 20px; text-align: left; border-bottom: 1px solid var(--clr-border); font-size: 0.875rem; }
    th { font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.07em; color: var(--clr-muted); font-weight: 600; background: var(--clr-bg); }
    tr:last-child td { border-bottom: none; }
    tr:hover td { background: #fafafa; }
    .status-badge {
      padding: 4px 12px; border-radius: var(--radius-full);
      font-size: 0.72rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em;
      display: inline-block;
    }
    .empty-state { padding: 40px 24px; text-align: center; color: var(--clr-muted); }
    .empty-state .empty-icon { font-size: 2.5rem; margin-bottom: 12px; }
  </style>
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>
  <?php require_once '../includes/navbar.php'; ?>

  <main id="main-content">
    <div class="page-hero">
      <div class="container">
        <h1 class="section-title">My Account</h1>
        <p class="section-subtitle">View your orders, custom requests, and profile.</p>
      </div>
    </div>

    <section class="account-section">
      <div class="container">
        <div class="account-grid">

          <!-- ── Sidebar ── -->
          <div>
            <div class="account-card">
              <div class="account-card-header">
                <div class="account-avatar" aria-hidden="true">👤</div>
                <div class="account-name"><?= htmlspecialchars($user['name']) ?></div>
                <div class="account-email"><?= htmlspecialchars($user['email']) ?></div>
              </div>
              <nav class="account-nav" aria-label="Account navigation">
                <a href="#orders">📦 My Orders</a>
                <a href="#custom-requests">✏️ Custom Requests</a>
                <a href="../pages/products.php">🛒 Shop</a>
                <a href="../pages/customization.php">🎨 New Custom Order</a>
                <a href="logout.php" class="logout-link">🚪 Logout</a>
              </nav>
            </div>
          </div>

          <!-- ── Main Content ── -->
          <div>

            <!-- Orders -->
            <div class="content-card" id="orders">
              <div class="content-card-header">
                <span class="content-card-title">📦 Order History</span>
                <span style="font-size:0.83rem; color:var(--clr-muted);"><?= count($orders) ?> order(s)</span>
              </div>
              <div class="content-card-body">
                <?php if (empty($orders)): ?>
                  <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true">🧺</div>
                    <p>No orders yet.</p>
                    <a href="../pages/products.php" class="btn btn-primary" style="margin-top:14px; font-size:0.875rem;">Start Shopping</a>
                  </div>
                <?php else: ?>
                  <table aria-label="Order history">
                    <thead>
                      <tr>
                        <th scope="col">Order #</th>
                        <th scope="col">Items</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($orders as $order): ?>
                        <?php
                          $color = $statusColors[$order['status']] ?? '#6b7280';
                          $date  = date('M j, Y', strtotime($order['created_at']));
                        ?>
                        <tr>
                          <td><strong>#<?= (int) $order['order_id'] ?></strong></td>
                          <td><?= (int) $order['item_count'] ?> item(s)</td>
                          <td>₱<?= number_format((float) $order['total_amount'], 2) ?></td>
                          <td>
                            <span class="status-badge"
                                  style="background:<?= $color ?>22; color:<?= htmlspecialchars($color) ?>; border:1px solid <?= htmlspecialchars($color) ?>44;">
                              <?= htmlspecialchars($order['status']) ?>
                            </span>
                          </td>
                          <td style="color:var(--clr-muted);"><?= htmlspecialchars($date) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </div>

            <!-- Custom Requests -->
            <div class="content-card" id="custom-requests">
              <div class="content-card-header">
                <span class="content-card-title">✏️ Custom Requests</span>
                <a href="../pages/customization.php" class="btn btn-outline" style="font-size:0.78rem; padding:6px 14px;">+ New Request</a>
              </div>
              <div class="content-card-body">
                <?php if (empty($customRequests)): ?>
                  <div class="empty-state">
                    <div class="empty-icon" aria-hidden="true">🎨</div>
                    <p>No custom requests yet.</p>
                    <a href="../pages/customization.php" class="btn btn-primary" style="margin-top:14px; font-size:0.875rem;">Request Custom Item</a>
                  </div>
                <?php else: ?>
                  <table aria-label="Custom request history">
                    <thead>
                      <tr>
                        <th scope="col">Request #</th>
                        <th scope="col">Description</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($customRequests as $req): ?>
                        <?php
                          $color = $statusColors[$req['status']] ?? '#6b7280';
                          $date  = date('M j, Y', strtotime($req['created_at']));
                          $desc  = mb_strimwidth($req['description'], 0, 60, '...');
                        ?>
                        <tr>
                          <td><strong>#<?= (int) $req['request_id'] ?></strong></td>
                          <td title="<?= htmlspecialchars($req['description']) ?>"><?= htmlspecialchars($desc) ?></td>
                          <td>
                            <span class="status-badge"
                                  style="background:<?= $color ?>22; color:<?= htmlspecialchars($color) ?>; border:1px solid <?= htmlspecialchars($color) ?>44;">
                              <?= htmlspecialchars($req['status']) ?>
                            </span>
                          </td>
                          <td style="color:var(--clr-muted);"><?= htmlspecialchars($date) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                <?php endif; ?>
              </div>
            </div>

          </div><!-- /main content -->
        </div>
      </div>
    </section>
  </main>

  <?php require_once '../includes/footer.php'; ?>
  <script src="../assets/js/script.js"></script>
</body>
</html>
