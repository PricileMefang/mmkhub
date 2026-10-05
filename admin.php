<?php
require_once __DIR__ . '/includes/db.php';

// ── Auth Guard ──────────────────────────────────────────────
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$db       = get_db();
$lang     = get_lang();
$adminId  = (int) ($_SESSION['admin_id'] ?? 0);
$adminRow = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin' LIMIT 1");
$adminRow->execute([$adminId]);
$adminRow = $adminRow->fetch();
if (!$adminRow) {
    session_destroy();
    header('Location: admin_login.php');
    exit;
}

$successMsg = '';
$errorMsg   = '';

// ── POST Action Handler ─────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['admin_action'] ?? '';

    // Logout
    if ($action === 'logout') {
        session_destroy();
        header('Location: admin_login.php');
        exit;
    }

    // Update order status
    if ($action === 'update_order_status') {
        $orderId       = intval($_POST['order_id'] ?? 0);
        $status        = $_POST['fulfillment_status'] ?? 'received';
        $paymentStatus = $_POST['payment_status'] ?? 'pending';
        $stmt = $db->prepare("UPDATE orders SET fulfillment_status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$status, $paymentStatus, $orderId]);
        header("Location: admin.php?tab=orders&msg=Order+updated");
        exit;
    }

    // Settle wholesale balance
    if ($action === 'settle_balance') {
        $orderId = intval($_POST['order_id'] ?? 0);
        $stmt = $db->prepare("UPDATE orders SET payment_status='fully_paid', deposit_amount=total_amount, balance_amount=0 WHERE id=?");
        $stmt->execute([$orderId]);
        header("Location: admin.php?tab=orders&msg=Balance+settled");
        exit;
    }

    // Approve wholesale application
    if ($action === 'approve_wholesale') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET role='wholesale_approved' WHERE id=?");
        $stmt->execute([$userId]);
        header("Location: admin.php?tab=wholesale&msg=Merchant+approved");
        exit;
    }

    // Reject / demote wholesale back to retail
    if ($action === 'reject_wholesale') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET role='retail' WHERE id=?");
        $stmt->execute([$userId]);
        header("Location: admin.php?tab=wholesale&msg=Application+rejected");
        exit;
    }

    // ★ Suspend a wholesale buyer (they cannot see wholesale prices or place WS orders)
    if ($action === 'suspend_wholesale') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET role='wholesale_suspended' WHERE id=?");
        $stmt->execute([$userId]);
        header("Location: admin.php?tab=wholesale&msg=Buyer+suspended");
        exit;
    }

    // ★ Reinstate a previously suspended wholesale buyer
    if ($action === 'reinstate_wholesale') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET role='wholesale_approved' WHERE id=?");
        $stmt->execute([$userId]);
        header("Location: admin.php?tab=wholesale&msg=Buyer+reinstated");
        exit;
    }

    // Add product
    if ($action === 'add_product') {
        $sku            = trim($_POST['sku'] ?? 'PROD-' . rand(100, 999));
        $nameEn         = trim($_POST['name_en'] ?? '');
        $nameFr         = trim($_POST['name_fr'] ?? $nameEn);
        $category       = trim($_POST['category'] ?? 'General');
        $retailPrice    = floatval($_POST['retail_price'] ?? 0);
        $wholesalePrice = floatval($_POST['wholesale_price'] ?? ($retailPrice * 0.8));
        $moq            = intval($_POST['moq'] ?? 1);
        $stock          = intval($_POST['stock'] ?? 100);
        $descEn         = trim($_POST['description_en'] ?? '');
        $descFr         = trim($_POST['description_fr'] ?? $descEn);
        $isService      = isset($_POST['is_service']) ? 1 : 0;
        $image          = 'assets/images/solar_lantern.jpg';
        $stmt = $db->prepare("INSERT INTO products (sku,name_en,name_fr,category,retail_price,wholesale_price,moq,stock,description_en,description_fr,is_service,image) VALUES (?,?,?,?,?,?,?,?,?,?,?,?)");
        $stmt->execute([$sku,$nameEn,$nameFr,$category,$retailPrice,$wholesalePrice,$moq,$stock,$descEn,$descFr,$isService,$image]);
        header("Location: admin.php?tab=inventory&msg=Product+added");
        exit;
    }

    // ★ Change admin name and/or password (requires current password)
    if ($action === 'update_profile') {
        $currentPass = trim($_POST['current_password'] ?? '');
        $newName     = trim($_POST['new_name'] ?? '');
        $newEmail    = trim($_POST['new_email'] ?? '');
        $newPass     = trim($_POST['new_password'] ?? '');
        $newPass2    = trim($_POST['new_password2'] ?? '');

        if (!password_verify($currentPass, $adminRow['password'])) {
            $errorMsg = 'Current password is incorrect.';
        } elseif (empty($newName)) {
            $errorMsg = 'Name cannot be empty.';
        } elseif ($newPass && $newPass !== $newPass2) {
            $errorMsg = 'New passwords do not match.';
        } else {
            $updates = ['name' => $newName, 'email' => ($newEmail ?: $adminRow['email'])];
            if ($newPass) {
                $updates['password'] = password_hash($newPass, PASSWORD_DEFAULT);
            }
            $sets   = implode(', ', array_map(fn($k) => "$k = ?", array_keys($updates)));
            $vals   = array_values($updates);
            $vals[] = $adminId;
            $db->prepare("UPDATE users SET $sets WHERE id = ?")->execute($vals);
            $_SESSION['admin_name']  = $newName;
            $_SESSION['admin_email'] = $newEmail ?: $adminRow['email'];
            $successMsg = 'Profile updated successfully.';
            // Refresh admin row
            $stmt = $db->prepare("SELECT * FROM users WHERE id = ? LIMIT 1");
            $stmt->execute([$adminId]);
            $adminRow = $stmt->fetch();
        }
    }
}

// ── KPI Queries ─────────────────────────────────────────────
$totalSales      = $db->query("SELECT SUM(total_amount) FROM orders WHERE fulfillment_status != 'cancelled'")->fetchColumn() ?: 0;
$retailSales     = $db->query("SELECT SUM(total_amount) FROM orders WHERE order_type='retail' AND fulfillment_status!='cancelled'")->fetchColumn() ?: 0;
$wholesaleSales  = $db->query("SELECT SUM(total_amount) FROM orders WHERE order_type='wholesale' AND fulfillment_status!='cancelled'")->fetchColumn() ?: 0;
$totalOrders     = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingWS       = $db->query("SELECT COUNT(*) FROM users WHERE role='wholesale_pending'")->fetchColumn();
$openQuotes      = $db->query("SELECT COUNT(*) FROM quotes WHERE status='new'")->fetchColumn();

$tab = $_GET['tab'] ?? 'orders';

$orders         = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$products       = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$wholesaleUsers = $db->query("SELECT * FROM users WHERE role IN ('wholesale_pending','wholesale_approved','wholesale_suspended') ORDER BY id DESC")->fetchAll();
$quotes         = $db->query("SELECT * FROM quotes ORDER BY id DESC")->fetchAll();
$bookings       = $db->query("SELECT * FROM service_bookings ORDER BY id DESC")->fetchAll();

if (isset($_GET['msg'])) {
    $successMsg = htmlspecialchars($_GET['msg']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MMK Hub – Owner Control Center</title>
  <link rel="icon" href="assets/images/logo.png">
  <link rel="stylesheet" href="css/style.css">
  <script src="js/lucide.min.js"></script>
  <style>
    .admin-layout{display:grid;grid-template-columns:240px 1fr;min-height:100vh}
    .admin-sidebar{background:#0f172a;color:#94a3b8;padding:0;display:flex;flex-direction:column}
    .sidebar-brand{padding:20px 16px 16px;border-bottom:1px solid #1e293b;display:flex;align-items:center;gap:10px}
    .sidebar-brand img{height:36px;border-radius:6px;background:#fff;padding:2px}
    .admin-nav{padding:12px 10px;flex:1}
    .admin-nav-item{display:flex;align-items:center;gap:10px;padding:10px 14px;border-radius:8px;color:#cbd5e1;font-weight:600;font-size:.875rem;margin-bottom:3px;cursor:pointer;text-decoration:none;transition:.2s;border:none;width:100%;background:none;text-align:left}
    .admin-nav-item:hover{background:rgba(255,255,255,.06);color:#fff}
    .admin-nav-item.active{background:#10b981;color:#fff}
    .admin-nav-item.danger{color:#f87171}
    .admin-nav-item.danger:hover{background:rgba(239,68,68,.12);color:#ef4444}
    .sidebar-admin-info{padding:14px 16px;border-top:1px solid #1e293b;font-size:.78rem}
    .sidebar-admin-info strong{display:block;color:#fff;font-size:.85rem}
    .admin-main{background:#f8fafc;padding:28px;overflow-y:auto}
    .admin-topbar{display:flex;align-items:center;justify-content:space-between;margin-bottom:28px;flex-wrap:wrap;gap:12px}
    .admin-topbar h1{font-size:1.5rem;color:#0f172a}
    .kpi-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:16px;margin-bottom:28px}
    .kpi-card{background:#fff;border-radius:12px;padding:20px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e2e8f0;position:relative;overflow:hidden}
    .kpi-card::before{content:'';position:absolute;top:0;left:0;width:4px;height:100%;background:var(--kpi-c,#10b981)}
    .kpi-label{font-size:.75rem;color:#64748b;font-weight:600;text-transform:uppercase;letter-spacing:.4px;margin-bottom:8px}
    .kpi-value{font-size:1.85rem;font-weight:800;color:#0f172a}
    .kpi-sub{font-size:.75rem;color:#64748b;margin-top:4px}
    .panel{background:#fff;border-radius:12px;box-shadow:0 1px 3px rgba(0,0,0,.07);border:1px solid #e2e8f0;overflow:hidden;margin-bottom:24px}
    .panel-head{padding:14px 20px;border-bottom:1px solid #e2e8f0;display:flex;align-items:center;justify-content:space-between}
    .panel-head h3{font-size:1rem;color:#0f172a;font-weight:700}
    .tbl{width:100%;border-collapse:collapse;font-size:.845rem}
    .tbl th{padding:10px 16px;text-align:left;font-size:.71rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;border-bottom:1px solid #e2e8f0;background:#f8fafc}
    .tbl td{padding:11px 16px;border-bottom:1px solid #e2e8f0;color:#0f172a}
    .tbl tr:last-child td{border-bottom:none}
    .tbl tr:hover td{background:rgba(16,185,129,.03)}
    .badge{padding:3px 10px;border-radius:9999px;font-size:.71rem;font-weight:700;text-transform:uppercase;display:inline-block}
    .b-green{background:#dcfce7;color:#166534}
    .b-blue{background:#dbeafe;color:#1e40af}
    .b-yellow{background:#fef9c3;color:#854d0e}
    .b-orange{background:#ffedd5;color:#9a3412}
    .b-red{background:#fee2e2;color:#991b1b}
    .b-gray{background:#f1f5f9;color:#475569}
    .b-purple{background:#f3e8ff;color:#6b21a8}
    .form-row{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px}
    .form-field label{display:block;font-size:.75rem;font-weight:700;color:#0f172a;margin-bottom:5px;text-transform:uppercase;letter-spacing:.4px}
    .form-field input,.form-field select,.form-field textarea{width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:6px;font-size:.875rem;font-family:inherit}
    .form-field input:focus,.form-field select:focus,.form-field textarea:focus{outline:none;border-color:#10b981;box-shadow:0 0 0 3px rgba(16,185,129,.1)}
    .alert-success{background:#dcfce7;color:#166534;border:1px solid #bbf7d0;border-radius:8px;padding:11px 16px;margin-bottom:18px;font-size:.875rem;font-weight:600}
    .alert-error{background:#fee2e2;color:#991b1b;border:1px solid #fecaca;border-radius:8px;padding:11px 16px;margin-bottom:18px;font-size:.875rem;font-weight:600}
    .btn-action{display:inline-flex;align-items:center;gap:5px;padding:5px 12px;border-radius:6px;font-size:.78rem;font-weight:700;cursor:pointer;border:none;transition:.2s}
    .btn-approve{background:#dcfce7;color:#166534}
    .btn-approve:hover{background:#10b981;color:#fff}
    .btn-reject{background:#ffedd5;color:#9a3412}
    .btn-reject:hover{background:#ef4444;color:#fff}
    .btn-suspend{background:#fef9c3;color:#854d0e}
    .btn-suspend:hover{background:#f59e0b;color:#fff}
    .btn-reinstate{background:#dbeafe;color:#1e40af}
    .btn-reinstate:hover{background:#3b82f6;color:#fff}
    .btn-settle{background:#ede9fe;color:#5b21b6}
    .btn-settle:hover{background:#7c3aed;color:#fff}
    .profile-section{background:#fff;border-radius:14px;padding:28px;box-shadow:0 1px 3px rgba(0,0,0,.08);border:1px solid #e2e8f0;max-width:600px}
    .profile-section h3{font-size:1.1rem;color:#0f172a;margin-bottom:20px;padding-bottom:12px;border-bottom:1px solid #e2e8f0}
    .tab-nav{display:flex;gap:4px;background:#f1f5f9;padding:4px;border-radius:10px;margin-bottom:24px;width:fit-content}
    .tab-btn{padding:8px 16px;border-radius:7px;font-size:.83rem;font-weight:700;color:#64748b;cursor:pointer;border:none;background:none;transition:.2s}
    .tab-btn.active{background:#fff;color:#0f172a;box-shadow:0 1px 3px rgba(0,0,0,.1)}
  </style>
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div class="sidebar-brand">
      <img src="assets/images/logo.png" alt="MMK Hub">
      <div>
        <div style="color:#fff;font-weight:800;font-size:1rem">MMK HUB</div>
        <div style="color:#10b981;font-size:.7rem;font-weight:600">OWNER PANEL</div>
      </div>
    </div>

    <nav class="admin-nav">
      <a href="admin.php?tab=orders"    class="admin-nav-item <?= $tab==='orders'?'active':'' ?>">
        <i data-lucide="shopping-bag" style="width:16px;height:16px"></i> Orders
        <?php if ($totalOrders > 0): ?><span style="margin-left:auto;background:rgba(255,255,255,.15);border-radius:9999px;padding:1px 7px;font-size:.72rem"><?= $totalOrders ?></span><?php endif; ?>
      </a>
      <a href="admin.php?tab=wholesale" class="admin-nav-item <?= $tab==='wholesale'?'active':'' ?>">
        <i data-lucide="building-2" style="width:16px;height:16px"></i> Wholesale
        <?php if ($pendingWS > 0): ?><span style="margin-left:auto;background:#f59e0b;color:#fff;border-radius:9999px;padding:1px 7px;font-size:.72rem"><?= $pendingWS ?></span><?php endif; ?>
      </a>
      <a href="admin.php?tab=inventory" class="admin-nav-item <?= $tab==='inventory'?'active':'' ?>">
        <i data-lucide="package" style="width:16px;height:16px"></i> Inventory
      </a>
      <a href="admin.php?tab=quotes"    class="admin-nav-item <?= $tab==='quotes'?'active':'' ?>">
        <i data-lucide="file-text" style="width:16px;height:16px"></i> Quotes
        <?php if ($openQuotes > 0): ?><span style="margin-left:auto;background:rgba(255,255,255,.15);border-radius:9999px;padding:1px 7px;font-size:.72rem"><?= $openQuotes ?></span><?php endif; ?>
      </a>
      <a href="admin.php?tab=bookings"  class="admin-nav-item <?= $tab==='bookings'?'active':'' ?>">
        <i data-lucide="calendar" style="width:16px;height:16px"></i> Service Bookings
      </a>
      <a href="admin.php?tab=profile"   class="admin-nav-item <?= $tab==='profile'?'active':'' ?>">
        <i data-lucide="settings" style="width:16px;height:16px"></i> My Profile
      </a>
      <a href="index.php" class="admin-nav-item" style="margin-top:8px">
        <i data-lucide="store" style="width:16px;height:16px"></i> View Store
      </a>
      <form method="POST" style="margin-top:auto">
        <input type="hidden" name="admin_action" value="logout">
        <button type="submit" class="admin-nav-item danger" style="margin-top:24px">
          <i data-lucide="log-out" style="width:16px;height:16px"></i> Sign Out
        </button>
      </form>
    </nav>

    <div class="sidebar-admin-info">
      <strong><?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?></strong>
      <span><?= htmlspecialchars($_SESSION['admin_email'] ?? '') ?></span>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="admin-main">

    <?php if ($successMsg): ?>
      <div class="alert-success">✓ <?= htmlspecialchars($successMsg) ?></div>
    <?php endif; ?>
    <?php if ($errorMsg): ?>
      <div class="alert-error">⚠ <?= htmlspecialchars($errorMsg) ?></div>
    <?php endif; ?>

    <!-- ═══════════════════ ORDERS TAB ═══════════════════ -->
    <?php if ($tab === 'orders'): ?>
    <div class="admin-topbar">
      <h1><i data-lucide="shopping-bag"></i> Orders</h1>
    </div>

    <!-- KPI Cards -->
    <div class="kpi-grid">
      <div class="kpi-card" style="--kpi-c:#10b981">
        <div class="kpi-label">Total Revenue</div>
        <div class="kpi-value"><?= format_cfa($totalSales) ?></div>
        <div class="kpi-sub">All fulfilled orders</div>
      </div>
      <div class="kpi-card" style="--kpi-c:#0284c7">
        <div class="kpi-label">Retail Sales</div>
        <div class="kpi-value"><?= format_cfa($retailSales) ?></div>
      </div>
      <div class="kpi-card" style="--kpi-c:#7c3aed">
        <div class="kpi-label">Wholesale Sales</div>
        <div class="kpi-value"><?= format_cfa($wholesaleSales) ?></div>
      </div>
      <div class="kpi-card" style="--kpi-c:#f59e0b">
        <div class="kpi-label">Total Orders</div>
        <div class="kpi-value"><?= $totalOrders ?></div>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>All Orders</h3></div>
      <table class="tbl">
        <thead>
          <tr>
            <th>Code</th><th>Customer</th><th>Type</th><th>Total</th>
            <th>Payment</th><th>Status</th><th>Date</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
          <tr>
            <td><strong><?= htmlspecialchars($o['order_code']) ?></strong></td>
            <td>
              <?= htmlspecialchars($o['customer_name']) ?><br>
              <small style="color:#64748b"><?= htmlspecialchars($o['customer_phone']) ?></small>
            </td>
            <td>
              <?php if ($o['order_type']==='wholesale'): ?>
                <span class="badge b-purple">Wholesale</span>
              <?php else: ?>
                <span class="badge b-blue">Retail</span>
              <?php endif; ?>
            </td>
            <td>
              <strong><?= format_cfa($o['total_amount']) ?></strong><br>
              <?php if ($o['balance_amount'] > 0): ?>
                <small style="color:#dc2626">Balance: <?= format_cfa($o['balance_amount']) ?></small>
              <?php endif; ?>
            </td>
            <td>
              <?php
              $pBadge = match($o['payment_status']) {
                'fully_paid'   => ['b-green','Paid'],
                'deposit_paid' => ['b-yellow','Deposit'],
                'pending'      => ['b-orange','Pending'],
                default        => ['b-gray', ucfirst($o['payment_status'])]
              };
              ?>
              <span class="badge <?= $pBadge[0] ?>"><?= $pBadge[1] ?></span>
            </td>
            <td>
              <?php
              $sBadge = match($o['fulfillment_status']) {
                'received'   => ['b-blue','Received'],
                'processing' => ['b-yellow','Processing'],
                'shipped'    => ['b-orange','Shipped'],
                'delivered'  => ['b-green','Delivered'],
                'cancelled'  => ['b-red','Cancelled'],
                default      => ['b-gray', ucfirst($o['fulfillment_status'])]
              };
              ?>
              <span class="badge <?= $sBadge[0] ?>"><?= $sBadge[1] ?></span>
            </td>
            <td style="font-size:.78rem;color:#64748b"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            <td>
              <details style="position:relative">
                <summary style="cursor:pointer;font-size:.78rem;font-weight:700;color:#047857">Actions ▾</summary>
                <div style="position:absolute;right:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;padding:10px;min-width:240px;z-index:10;box-shadow:0 8px 24px rgba(0,0,0,.12)">
                  <form method="POST" style="display:flex;flex-direction:column;gap:8px">
                    <input type="hidden" name="admin_action" value="update_order_status">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <select name="fulfillment_status" style="padding:6px;border-radius:4px;border:1px solid #e2e8f0;font-size:.8rem">
                      <?php foreach (['received','processing','shipped','delivered','cancelled'] as $st): ?>
                        <option value="<?= $st ?>" <?= $o['fulfillment_status']===$st?'selected':'' ?>><?= ucfirst($st) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <select name="payment_status" style="padding:6px;border-radius:4px;border:1px solid #e2e8f0;font-size:.8rem">
                      <?php foreach (['pending','deposit_paid','fully_paid','refunded'] as $ps): ?>
                        <option value="<?= $ps ?>" <?= $o['payment_status']===$ps?'selected':'' ?>><?= ucwords(str_replace('_',' ',$ps)) ?></option>
                      <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-action btn-approve">Update Status</button>
                  </form>
                  <?php if ($o['balance_amount'] > 0): ?>
                  <form method="POST" style="margin-top:6px">
                    <input type="hidden" name="admin_action" value="settle_balance">
                    <input type="hidden" name="order_id" value="<?= $o['id'] ?>">
                    <button type="submit" class="btn-action btn-settle" style="width:100%">Mark Balance Settled</button>
                  </form>
                  <?php endif; ?>
                  <div style="margin-top:8px;font-size:.75rem;color:#64748b">
                    <a href="track.php?code=<?= urlencode($o['order_code']) ?>" target="_blank" style="color:#047857;font-weight:600">View Track Page →</a>
                  </div>
                </div>
              </details>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════ WHOLESALE TAB ═══════════════════ -->
    <?php elseif ($tab === 'wholesale'): ?>
    <div class="admin-topbar">
      <h1><i data-lucide="building-2"></i> Wholesale Buyers</h1>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>B2B Accounts</h3></div>
      <table class="tbl">
        <thead>
          <tr>
            <th>Company / Name</th><th>Phone</th><th>City</th>
            <th>Tax ID</th><th>Status</th><th>Joined</th><th>Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($wholesaleUsers as $u): ?>
          <tr>
            <td>
              <strong><?= htmlspecialchars($u['company_name'] ?: $u['name']) ?></strong><br>
              <small style="color:#64748b"><?= htmlspecialchars($u['name']) ?></small>
            </td>
            <td><?= htmlspecialchars($u['phone']) ?></td>
            <td><?= htmlspecialchars($u['city']) ?></td>
            <td style="font-size:.8rem;color:#64748b"><?= htmlspecialchars($u['tax_id'] ?? '—') ?></td>
            <td>
              <?php
              $wsBadge = match($u['role']) {
                'wholesale_approved'  => ['b-green','Approved'],
                'wholesale_pending'   => ['b-yellow','Pending'],
                'wholesale_suspended' => ['b-red','Suspended'],
                default               => ['b-gray', ucfirst($u['role'])]
              };
              ?>
              <span class="badge <?= $wsBadge[0] ?>"><?= $wsBadge[1] ?></span>
            </td>
            <td style="font-size:.78rem;color:#64748b"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
            <td style="display:flex;gap:6px;flex-wrap:wrap;padding:8px 16px">
              <?php if ($u['role'] === 'wholesale_pending'): ?>
                <form method="POST">
                  <input type="hidden" name="admin_action" value="approve_wholesale">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-action btn-approve">✓ Approve</button>
                </form>
                <form method="POST">
                  <input type="hidden" name="admin_action" value="reject_wholesale">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-action btn-reject">✗ Reject</button>
                </form>
              <?php elseif ($u['role'] === 'wholesale_approved'): ?>
                <form method="POST" onsubmit="return confirm('Suspend this wholesale buyer? They will lose B2B access.')">
                  <input type="hidden" name="admin_action" value="suspend_wholesale">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-action btn-suspend">⏸ Suspend</button>
                </form>
              <?php elseif ($u['role'] === 'wholesale_suspended'): ?>
                <form method="POST">
                  <input type="hidden" name="admin_action" value="reinstate_wholesale">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-action btn-reinstate">▶ Reinstate</button>
                </form>
                <form method="POST">
                  <input type="hidden" name="admin_action" value="reject_wholesale">
                  <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                  <button type="submit" class="btn-action btn-reject">✗ Remove</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($wholesaleUsers)): ?>
            <tr><td colspan="7" style="text-align:center;color:#64748b;padding:28px">No wholesale applications yet.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════ INVENTORY TAB ═══════════════════ -->
    <?php elseif ($tab === 'inventory'): ?>
    <div class="admin-topbar">
      <h1><i data-lucide="package"></i> Product Inventory</h1>
    </div>

    <!-- Add Product Form -->
    <div class="panel" style="margin-bottom:24px">
      <div class="panel-head"><h3>+ Add New Product / Service</h3></div>
      <div style="padding:20px">
        <form method="POST">
          <input type="hidden" name="admin_action" value="add_product">
          <div class="form-row">
            <div class="form-field">
              <label>SKU</label>
              <input type="text" name="sku" placeholder="e.g. SOLAR-KIT-001">
            </div>
            <div class="form-field">
              <label>Category</label>
              <select name="category">
                <option>Solar Energy</option>
                <option>African Textiles</option>
                <option>Building Materials</option>
                <option>Electronics</option>
                <option>Services</option>
                <option>Bundles</option>
              </select>
            </div>
          </div>
          <div class="form-row">
            <div class="form-field"><label>Name (English)</label><input type="text" name="name_en" required placeholder="Product name in English"></div>
            <div class="form-field"><label>Name (French)</label><input type="text" name="name_fr" placeholder="Nom en français"></div>
          </div>
          <div class="form-row">
            <div class="form-field"><label>Retail Price (FCFA)</label><input type="number" name="retail_price" min="0" step="100" required></div>
            <div class="form-field"><label>Wholesale Price (FCFA)</label><input type="number" name="wholesale_price" min="0" step="100"></div>
          </div>
          <div class="form-row">
            <div class="form-field"><label>MOQ (Min Order Qty)</label><input type="number" name="moq" min="1" value="1"></div>
            <div class="form-field"><label>Stock Units</label><input type="number" name="stock" min="0" value="100"></div>
          </div>
          <div class="form-field" style="margin-bottom:14px">
            <label>Description (EN)</label>
            <textarea name="description_en" rows="2" style="width:100%;padding:9px 12px;border:1.5px solid #e2e8f0;border-radius:6px;font-family:inherit"></textarea>
          </div>
          <label style="display:flex;align-items:center;gap:8px;font-size:.875rem;margin-bottom:14px;cursor:pointer">
            <input type="checkbox" name="is_service"> This is a Service (not a physical product)
          </label>
          <button type="submit" class="btn-card-primary" style="padding:10px 24px">Add to Catalog</button>
        </form>
      </div>
    </div>

    <div class="panel">
      <div class="panel-head"><h3>Current Products (<?= count($products) ?>)</h3></div>
      <table class="tbl">
        <thead>
          <tr><th>SKU</th><th>Name</th><th>Category</th><th>Retail</th><th>Wholesale</th><th>MOQ</th><th>Stock</th><th>Type</th></tr>
        </thead>
        <tbody>
          <?php foreach ($products as $p): ?>
          <tr>
            <td style="font-size:.78rem;color:#64748b"><?= htmlspecialchars($p['sku'] ?? '—') ?></td>
            <td><?= htmlspecialchars(get_lang()==='fr' ? $p['name_fr'] : $p['name_en']) ?></td>
            <td><span class="badge b-blue"><?= htmlspecialchars($p['category']) ?></span></td>
            <td><?= format_cfa($p['retail_price']) ?></td>
            <td><?= format_cfa($p['wholesale_price']) ?></td>
            <td><?= $p['moq'] ?></td>
            <td><?= $p['stock'] ?></td>
            <td><?php if ($p['is_service']): ?><span class="badge b-purple">Service</span><?php elseif ($p['is_bundle']): ?><span class="badge b-green">Bundle</span><?php else: ?><span class="badge b-gray">Product</span><?php endif; ?></td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════ QUOTES TAB ═══════════════════ -->
    <?php elseif ($tab === 'quotes'): ?>
    <div class="admin-topbar">
      <h1><i data-lucide="file-text"></i> Quote Requests</h1>
    </div>
    <div class="panel">
      <div class="panel-head"><h3>Incoming Quotes (<?= count($quotes) ?>)</h3></div>
      <table class="tbl">
        <thead><tr><th>Code</th><th>Customer</th><th>Product</th><th>Qty</th><th>Target Price</th><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach ($quotes as $q): ?>
          <tr>
            <td><strong><?= htmlspecialchars($q['quote_code']) ?></strong></td>
            <td><?= htmlspecialchars($q['customer_name']) ?><br><small style="color:#64748b"><?= htmlspecialchars($q['customer_phone']) ?></small></td>
            <td><?= htmlspecialchars($q['product_name']) ?></td>
            <td><?= $q['requested_qty'] ?></td>
            <td><?= $q['target_price'] > 0 ? format_cfa($q['target_price']) : '—' ?></td>
            <td><span class="badge <?= $q['status']==='new'?'b-yellow':'b-green' ?>"><?= htmlspecialchars($q['status']) ?></span></td>
            <td style="font-size:.78rem;color:#64748b"><?= date('d M Y', strtotime($q['created_at'])) ?></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($quotes)): ?><tr><td colspan="7" style="text-align:center;color:#64748b;padding:28px">No quote requests yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════ BOOKINGS TAB ═══════════════════ -->
    <?php elseif ($tab === 'bookings'): ?>
    <div class="admin-topbar">
      <h1><i data-lucide="calendar"></i> Service Bookings</h1>
    </div>
    <div class="panel">
      <div class="panel-head"><h3>Technical Appointments (<?= count($bookings) ?>)</h3></div>
      <table class="tbl">
        <thead><tr><th>Customer</th><th>Service</th><th>Date</th><th>Slot</th><th>Address</th><th>Status</th></tr></thead>
        <tbody>
          <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= htmlspecialchars($b['customer_name']) ?><br><small style="color:#64748b"><?= htmlspecialchars($b['customer_phone']) ?></small></td>
            <td>Service #<?= $b['service_id'] ?><br><small style="color:#64748b"><?= htmlspecialchars($b['tier'] ?? '') ?></small></td>
            <td><?= htmlspecialchars($b['service_date']) ?></td>
            <td><?= htmlspecialchars($b['service_slot']) ?></td>
            <td style="font-size:.8rem"><?= htmlspecialchars($b['address']) ?></td>
            <td><span class="badge b-yellow">Scheduled</span></td>
          </tr>
          <?php endforeach; ?>
          <?php if (empty($bookings)): ?><tr><td colspan="6" style="text-align:center;color:#64748b;padding:28px">No service bookings yet.</td></tr><?php endif; ?>
        </tbody>
      </table>
    </div>

    <!-- ═══════════════════ PROFILE TAB ═══════════════════ -->
    <?php elseif ($tab === 'profile'): ?>
    <div class="admin-topbar">
      <h1><i data-lucide="settings"></i> My Profile</h1>
    </div>

    <div class="profile-section">
      <h3>Update Account Details</h3>
      <p style="font-size:.85rem;color:#64748b;margin-bottom:20px">
        You must enter your <strong>current password</strong> to save any changes.
      </p>
      <form method="POST" novalidate>
        <input type="hidden" name="admin_action" value="update_profile">

        <div class="form-row">
          <div class="form-field">
            <label>Display Name</label>
            <input type="text" name="new_name" required
                   value="<?= htmlspecialchars($adminRow['name']) ?>"
                   placeholder="Your full name">
          </div>
          <div class="form-field">
            <label>Email Address</label>
            <input type="email" name="new_email"
                   value="<?= htmlspecialchars($adminRow['email'] ?? '') ?>"
                   placeholder="owner@mmkhub.cm">
          </div>
        </div>

        <div style="background:#f8fafc;border:1px solid #e2e8f0;border-radius:10px;padding:18px;margin-bottom:16px">
          <div style="font-size:.78rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.4px;margin-bottom:12px">Change Password (optional)</div>
          <div class="form-row">
            <div class="form-field">
              <label>New Password</label>
              <input type="password" name="new_password" placeholder="Leave blank to keep current" autocomplete="new-password">
            </div>
            <div class="form-field">
              <label>Confirm New Password</label>
              <input type="password" name="new_password2" placeholder="Repeat new password">
            </div>
          </div>
        </div>

        <div class="form-field" style="margin-bottom:20px">
          <label>Current Password <span style="color:#ef4444">*</span></label>
          <input type="password" name="current_password" required
                 placeholder="Enter your current password to confirm changes"
                 autocomplete="current-password">
        </div>

        <button type="submit" class="btn-card-primary" style="padding:11px 28px">
          <i data-lucide="save" style="width:14px;height:14px"></i> Save Changes
        </button>
      </form>
    </div>

    <!-- Current account info card -->
    <div style="margin-top:24px;background:#fff;border-radius:12px;padding:20px;border:1px solid #e2e8f0;max-width:600px">
      <div style="font-size:.75rem;font-weight:700;color:#64748b;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px">Current Account</div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;font-size:.875rem">
        <div><span style="color:#64748b">Name:</span> <strong><?= htmlspecialchars($adminRow['name']) ?></strong></div>
        <div><span style="color:#64748b">Email:</span> <strong><?= htmlspecialchars($adminRow['email'] ?? '—') ?></strong></div>
        <div><span style="color:#64748b">Role:</span> <span class="badge b-green">Admin</span></div>
        <div><span style="color:#64748b">Last updated:</span> <span style="color:#64748b;font-size:.8rem"><?= date('d M Y') ?></span></div>
      </div>
    </div>

    <?php endif; ?>

  </main>
</div>

<script>
if (window.lucide) lucide.createIcons();
// Close details dropdowns when clicking outside
document.addEventListener('click', e => {
  if (!e.target.closest('details')) {
    document.querySelectorAll('details[open]').forEach(d => d.removeAttribute('open'));
  }
});
</script>
</body>
</html>
