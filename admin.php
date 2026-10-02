<?php
require_once __DIR__ . '/includes/db.php';

$db = get_db();
$lang = get_lang();

// Simple admin action handlers
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $action = $_POST['admin_action'] ?? '';

    if ($action === 'update_order_status') {
        $orderId = intval($_POST['order_id'] ?? 0);
        $status = $_POST['fulfillment_status'] ?? 'received';
        $paymentStatus = $_POST['payment_status'] ?? 'pending';

        $stmt = $db->prepare("UPDATE orders SET fulfillment_status = ?, payment_status = ? WHERE id = ?");
        $stmt->execute([$status, $paymentStatus, $orderId]);
        header("Location: admin.php?tab=orders&msg=Order+updated");
        exit;
    }

    if ($action === 'settle_balance') {
        $orderId = intval($_POST['order_id'] ?? 0);
        $stmt = $db->prepare("UPDATE orders SET payment_status = 'fully_paid', deposit_amount = total_amount, balance_amount = 0 WHERE id = ?");
        $stmt->execute([$orderId]);
        header("Location: admin.php?tab=orders&msg=Wholesale+balance+settled");
        exit;
    }

    if ($action === 'approve_wholesale') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET role = 'wholesale_approved' WHERE id = ?");
        $stmt->execute([$userId]);
        header("Location: admin.php?tab=wholesale&msg=Merchant+approved");
        exit;
    }

    if ($action === 'reject_wholesale') {
        $userId = intval($_POST['user_id'] ?? 0);
        $stmt = $db->prepare("UPDATE users SET role = 'retail' WHERE id = ?");
        $stmt->execute([$userId]);
        header("Location: admin.php?tab=wholesale&msg=Application+rejected");
        exit;
    }

    if ($action === 'add_product') {
        $sku = trim($_POST['sku'] ?? 'PROD-' . rand(100, 999));
        $nameEn = trim($_POST['name_en'] ?? '');
        $nameFr = trim($_POST['name_fr'] ?? $nameEn);
        $category = trim($_POST['category'] ?? 'General');
        $retailPrice = floatval($_POST['retail_price'] ?? 0);
        $wholesalePrice = floatval($_POST['wholesale_price'] ?? ($retailPrice * 0.8));
        $moq = intval($_POST['moq'] ?? 1);
        $stock = intval($_POST['stock'] ?? 100);
        $descEn = trim($_POST['description_en'] ?? '');
        $descFr = trim($_POST['description_fr'] ?? $descEn);
        $isService = isset($_POST['is_service']) ? 1 : 0;
        $image = 'assets/images/solar_lantern.jpg';

        $stmt = $db->prepare("INSERT INTO products (sku, name_en, name_fr, category, retail_price, wholesale_price, moq, stock, description_en, description_fr, is_service, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$sku, $nameEn, $nameFr, $category, $retailPrice, $wholesalePrice, $moq, $stock, $descEn, $descFr, $isService, $image]);
        header("Location: admin.php?tab=inventory&msg=Product+added");
        exit;
    }
}

// Queries for KPI metrics
$totalSales = $db->query("SELECT SUM(total_amount) FROM orders WHERE fulfillment_status != 'cancelled'")->fetchColumn() ?: 0;
$retailSales = $db->query("SELECT SUM(total_amount) FROM orders WHERE order_type = 'retail' AND fulfillment_status != 'cancelled'")->fetchColumn() ?: 0;
$wholesaleSales = $db->query("SELECT SUM(total_amount) FROM orders WHERE order_type = 'wholesale' AND fulfillment_status != 'cancelled'")->fetchColumn() ?: 0;
$totalOrders = $db->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$pendingWholesale = $db->query("SELECT COUNT(*) FROM users WHERE role = 'wholesale_pending'")->fetchColumn();
$openQuotes = $db->query("SELECT COUNT(*) FROM quotes WHERE status = 'new'")->fetchColumn();

// Fetch active tab
$tab = $_GET['tab'] ?? 'orders';

// Fetch Tab Data
$orders = $db->query("SELECT * FROM orders ORDER BY id DESC")->fetchAll();
$products = $db->query("SELECT * FROM products ORDER BY id DESC")->fetchAll();
$wholesaleUsers = $db->query("SELECT * FROM users WHERE role IN ('wholesale_pending', 'wholesale_approved') ORDER BY id DESC")->fetchAll();
$quotes = $db->query("SELECT * FROM quotes ORDER BY id DESC")->fetchAll();
$bookings = $db->query("SELECT * FROM service_bookings ORDER BY id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MMK Hub - Owner Control Center 🇨🇲</title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="assets/images/solar_lantern.jpg">
  <style>
    .admin-layout {
      display: grid;
      grid-template-columns: 240px 1fr;
      min-height: 100vh;
    }
    .admin-sidebar {
      background: #0f172a;
      color: #94a3b8;
      padding: 24px 16px;
      border-right: 1px solid #1e293b;
    }
    .admin-nav-item {
      display: flex;
      align-items: center;
      gap: 10px;
      padding: 10px 14px;
      border-radius: 8px;
      color: #cbd5e1;
      font-weight: 600;
      font-size: 0.9rem;
      margin-bottom: 4px;
    }
    .admin-nav-item:hover, .admin-nav-item.active {
      background: #1e293b;
      color: #10b981;
    }
    .admin-badge {
      margin-left: auto;
      background: #ef4444;
      color: #fff;
      font-size: 0.7rem;
      font-weight: 800;
      padding: 2px 6px;
      border-radius: 9999px;
    }
    .admin-main {
      background: #f8fafc;
      padding: 32px 40px;
      overflow-y: auto;
    }
    .kpi-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 32px;
    }
    .kpi-card {
      background: #fff;
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      padding: 18px 20px;
      box-shadow: var(--shadow-sm);
    }
    .kpi-title {
      font-size: 0.8rem;
      color: #64748b;
      font-weight: 700;
      text-transform: uppercase;
      margin-bottom: 6px;
    }
    .kpi-val {
      font-size: 1.5rem;
      font-weight: 800;
      color: #0f172a;
      font-family: 'Outfit', sans-serif;
    }
    .admin-table {
      width: 100%;
      border-collapse: collapse;
      background: #fff;
      border-radius: 12px;
      overflow: hidden;
      border: 1px solid #e2e8f0;
      font-size: 0.85rem;
    }
    .admin-table th {
      background: #0f172a;
      color: #fff;
      text-align: left;
      padding: 12px 16px;
    }
    .admin-table td {
      padding: 12px 16px;
      border-bottom: 1px solid #f1f5f9;
    }
  </style>
</head>
<body>

<div class="admin-layout">
  
  <!-- Sidebar -->
  <aside class="admin-sidebar">
    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 30px; padding: 0 8px;">
      <div class="brand-logo-badge" style="width: 36px; height: 36px; font-size: 1rem;">M</div>
      <div>
        <div style="font-weight: 800; color: #fff; font-size: 1.1rem; line-height: 1;">MMK HUB</div>
        <div style="font-size: 0.7rem; color: #10b981; font-weight: 700;">OWNER PORTAL 🇨🇲</div>
      </div>
    </div>

    <nav>
      <a href="admin.php?tab=orders" class="admin-nav-item <?= $tab === 'orders' ? 'active' : '' ?>">
        <span>📦</span> <span>Orders & Shipments</span>
        <?php if ($totalOrders > 0): ?><span class="admin-badge" style="background:#047857;"><?= $totalOrders ?></span><?php endif; ?>
      </a>

      <a href="admin.php?tab=inventory" class="admin-nav-item <?= $tab === 'inventory' ? 'active' : '' ?>">
        <span>🏷️</span> <span>Catalog & Inventory</span>
      </a>

      <a href="admin.php?tab=wholesale" class="admin-nav-item <?= $tab === 'wholesale' ? 'active' : '' ?>">
        <span>🏢</span> <span>Wholesale Approvals</span>
        <?php if ($pendingWholesale > 0): ?><span class="admin-badge"><?= $pendingWholesale ?></span><?php endif; ?>
      </a>

      <a href="admin.php?tab=quotes" class="admin-nav-item <?= $tab === 'quotes' ? 'active' : '' ?>">
        <span>💬</span> <span>Quote Inquiries</span>
        <?php if ($openQuotes > 0): ?><span class="admin-badge" style="background:#f59e0b;"><?= $openQuotes ?></span><?php endif; ?>
      </a>

      <a href="admin.php?tab=bookings" class="admin-nav-item <?= $tab === 'bookings' ? 'active' : '' ?>">
        <span>🛠️</span> <span>Service Appointments</span>
      </a>

      <div style="margin: 24px 0 12px 14px; font-size: 0.7rem; font-weight: 700; color: #475569; text-transform: uppercase;">
        External Links
      </div>

      <a href="index.php" class="admin-nav-item" target="_blank">
        <span>🌐</span> <span>View Storefront</span>
      </a>
      <a href="track.php" class="admin-nav-item" target="_blank">
        <span>🧾</span> <span>Proforma Invoices</span>
      </a>
    </nav>
  </aside>

  <!-- Main Content Area -->
  <main class="admin-main">
    
    <!-- Top Bar -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
      <div>
        <h2 style="font-size: 1.8rem; margin-bottom: 4px;">Owner Control Center</h2>
        <p style="font-size: 0.85rem; color: #64748b;">Direct management of Cameroon inventory, wholesale accounts, Mobile Money deposits, and order fulfillment.</p>
      </div>

      <?php if (isset($_GET['msg'])): ?>
        <div style="background: #ecfdf5; border: 1px solid #10b981; color: #047857; font-weight: 700; padding: 8px 16px; border-radius: 8px; font-size: 0.85rem;">
          ✓ <?= htmlspecialchars($_GET['msg']) ?>
        </div>
      <?php endif; ?>
    </div>

    <!-- KPI Summary Cards -->
    <div class="kpi-grid">
      <div class="kpi-card">
        <div class="kpi-title">Total Gross Sales</div>
        <div class="kpi-val" style="color: #047857;"><?= format_cfa($totalSales) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Retail + Wholesale</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-title">Wholesale B2B Revenue</div>
        <div class="kpi-val" style="color: #059669;"><?= format_cfa($wholesaleSales) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Tiered volume orders</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-title">Retail Consumer Revenue</div>
        <div class="kpi-val" style="color: #0284c7;"><?= format_cfa($retailSales) ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Direct unit purchases</div>
      </div>

      <div class="kpi-card">
        <div class="kpi-title">Pending B2B Approvals</div>
        <div class="kpi-val" style="color: <?= $pendingWholesale > 0 ? '#ef4444' : '#10b981' ?>;"><?= $pendingWholesale ?></div>
        <div style="font-size: 0.75rem; color: #64748b; margin-top: 4px;">Business accounts waiting</div>
      </div>
    </div>

    <!-- TAB 1: ORDERS & DISPATCH -->
    <?php if ($tab === 'orders'): ?>
      <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px; box-shadow: var(--shadow-sm);">
        <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Orders & Fulfillment Log (Retail & Wholesale)</h3>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Order Code</th>
              <th>Customer / Business</th>
              <th>City</th>
              <th>Channel</th>
              <th>Total / Balance</th>
              <th>Payment Rail</th>
              <th>Status</th>
              <th>Fulfillment Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($orders)): ?>
              <tr><td colspan="8" style="text-align:center; padding: 30px; color:#64748b;">No orders placed yet.</td></tr>
            <?php else: ?>
              <?php foreach ($orders as $ord): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($ord['order_code']) ?></strong><br>
                    <span style="font-size:0.7rem; color:#64748b;"><?= date('d/m/Y H:i', strtotime($ord['created_at'])) ?></span>
                  </td>
                  <td>
                    <strong><?= htmlspecialchars($ord['customer_name']) ?></strong><br>
                    <span style="font-size:0.75rem; color:#64748b;">📞 <?= htmlspecialchars($ord['customer_phone']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($ord['city']) ?></td>
                  <td>
                    <span class="price-mode-label <?= $ord['order_type'] === 'wholesale' ? 'wholesale' : 'retail' ?>">
                      <?= strtoupper($ord['order_type']) ?>
                    </span>
                  </td>
                  <td>
                    <strong><?= format_cfa($ord['total_amount']) ?></strong><br>
                    <?php if ($ord['order_type'] === 'wholesale' && $ord['balance_amount'] > 0): ?>
                      <span style="font-size:0.75rem; color:#b45309; font-weight:700;">Bal Due: <?= format_cfa($ord['balance_amount']) ?></span>
                    <?php else: ?>
                      <span style="font-size:0.75rem; color:#047857; font-weight:600;">Paid in Full</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <span style="font-size:0.8rem; font-weight:600;"><?= strtoupper($ord['payment_method']) ?></span><br>
                    <span style="font-size:0.7rem; color:#64748b;">Ref: <?= htmlspecialchars($ord['payment_ref']) ?></span>
                  </td>
                  <td>
                    <form method="POST" action="admin.php?tab=orders" style="display:flex; flex-direction:column; gap:4px;">
                      <input type="hidden" name="admin_action" value="update_order_status">
                      <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                      <select name="fulfillment_status" style="padding: 4px; font-size: 0.75rem; border-radius: 4px; font-weight: 600;">
                        <option value="received" <?= $ord['fulfillment_status'] === 'received' ? 'selected' : '' ?>>Received</option>
                        <option value="processing" <?= $ord['fulfillment_status'] === 'processing' ? 'selected' : '' ?>>Processing</option>
                        <option value="shipped" <?= $ord['fulfillment_status'] === 'shipped' ? 'selected' : '' ?>>Shipped</option>
                        <option value="delivered" <?= $ord['fulfillment_status'] === 'delivered' ? 'selected' : '' ?>>Delivered</option>
                      </select>
                      <button type="submit" style="background:#0f172a; color:#fff; font-size:0.7rem; padding:2px 6px; border-radius:4px;">Save</button>
                    </form>
                  </td>
                  <td>
                    <div style="display:flex; flex-direction:column; gap:4px;">
                      <a href="track.php?code=<?= urlencode($ord['order_code']) ?>" target="_blank" style="font-size:0.75rem; color:#047857; font-weight:700; text-decoration:underline;">
                        Print Invoice 🖨️
                      </a>
                      <?php if ($ord['order_type'] === 'wholesale' && $ord['balance_amount'] > 0): ?>
                        <form method="POST" action="admin.php?tab=orders">
                          <input type="hidden" name="admin_action" value="settle_balance">
                          <input type="hidden" name="order_id" value="<?= $ord['id'] ?>">
                          <button type="submit" style="background:#10b981; color:#fff; font-size:0.7rem; padding:4px 8px; border-radius:4px; font-weight:700;">
                            ✓ Settle Balance
                          </button>
                        </form>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    <!-- TAB 2: INVENTORY & CATALOG -->
    <?php elseif ($tab === 'inventory'): ?>
      <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 24px; align-items: flex-start;">
        
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
          <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Active Catalog Items</h3>
          
          <table class="admin-table">
            <thead>
              <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Retail</th>
                <th>Wholesale</th>
                <th>MOQ</th>
                <th>Stock</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($products as $p): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($p['name_en']) ?></strong><br>
                    <span style="font-size:0.7rem; color:#64748b;">SKU: <?= htmlspecialchars($p['sku']) ?></span>
                  </td>
                  <td><?= htmlspecialchars($p['category']) ?></td>
                  <td style="font-weight:700; color:#0284c7;"><?= format_cfa($p['retail_price']) ?></td>
                  <td style="font-weight:700; color:#047857;"><?= format_cfa($p['wholesale_price']) ?></td>
                  <td><strong><?= $p['moq'] ?></strong></td>
                  <td><strong><?= $p['stock'] ?></strong> units</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>

        <!-- Add New Item Form -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
          <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Add New Product / Service</h3>
          
          <form method="POST" action="admin.php?tab=inventory">
            <input type="hidden" name="admin_action" value="add_product">

            <div style="margin-bottom: 12px;">
              <label style="display:block; font-size:0.75rem; font-weight:700;">Product Title (English)</label>
              <input type="text" name="name_en" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
            </div>

            <div style="margin-bottom: 12px;">
              <label style="display:block; font-size:0.75rem; font-weight:700;">Titre (Français)</label>
              <input type="text" name="name_fr" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
            </div>

            <div style="margin-bottom: 12px;">
              <label style="display:block; font-size:0.75rem; font-weight:700;">Category</label>
              <select name="category" style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
                <option value="Solar Energy">Solar Energy</option>
                <option value="Building Materials">Building Materials</option>
                <option value="African Textiles">African Textiles</option>
                <option value="Electronics">Electronics</option>
                <option value="Services">Services</option>
                <option value="Bundles">Bundles</option>
              </select>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom: 12px;">
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700;">Retail Price (FCFA)</label>
                <input type="number" name="retail_price" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
              </div>
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700;">Wholesale (FCFA)</label>
                <input type="number" name="wholesale_price" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
              </div>
            </div>

            <div style="display:grid; grid-template-columns: 1fr 1fr; gap:10px; margin-bottom: 12px;">
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700;">Wholesale MOQ</label>
                <input type="number" name="moq" value="10" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
              </div>
              <div>
                <label style="display:block; font-size:0.75rem; font-weight:700;">Stock Quantity</label>
                <input type="number" name="stock" value="100" required style="width:100%; padding:8px; border:1px solid #cbd5e1; border-radius:6px;">
              </div>
            </div>

            <button type="submit" class="btn-card-primary" style="width:100%; padding:10px;">+ Publish Item</button>
          </form>
        </div>

      </div>

    <!-- TAB 3: WHOLESALE APPROVALS -->
    <?php elseif ($tab === 'wholesale'): ?>
      <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Wholesale Business Registrations & Approvals</h3>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Company Entity</th>
              <th>Contact Person</th>
              <th>City</th>
              <th>Tax ID / NIU</th>
              <th>Status</th>
              <th>Owner Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($wholesaleUsers as $u): ?>
              <tr>
                <td><strong><?= htmlspecialchars($u['company_name'] ?: $u['name']) ?></strong></td>
                <td>
                  <?= htmlspecialchars($u['name']) ?><br>
                  <span style="font-size:0.75rem; color:#64748b;">📞 <?= htmlspecialchars($u['phone']) ?></span>
                </td>
                <td><?= htmlspecialchars($u['city']) ?></td>
                <td><code><?= htmlspecialchars($u['tax_id'] ?: 'N/A') ?></code></td>
                <td>
                  <span class="price-mode-label <?= $u['role'] === 'wholesale_approved' ? 'wholesale' : 'retail' ?>">
                    <?= $u['role'] === 'wholesale_approved' ? 'APPROVED' : 'PENDING' ?>
                  </span>
                </td>
                <td>
                  <?php if ($u['role'] === 'wholesale_pending'): ?>
                    <form method="POST" action="admin.php?tab=wholesale" style="display:inline-block;">
                      <input type="hidden" name="admin_action" value="approve_wholesale">
                      <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                      <button type="submit" style="background:#10b981; color:#fff; padding:6px 12px; border-radius:6px; font-size:0.75rem; font-weight:700;">
                        ✓ Approve
                      </button>
                    </form>
                  <?php else: ?>
                    <span style="color:#047857; font-weight:700; font-size:0.8rem;">Active Merchant</span>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

    <!-- TAB 4: QUOTES INBOX -->
    <?php elseif ($tab === 'quotes'): ?>
      <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Custom High-Volume Quote Inquiries (>500 Units)</h3>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Quote Code</th>
              <th>Client / Company</th>
              <th>Product Requested</th>
              <th>Requested Qty</th>
              <th>Target Budget</th>
              <th>Message</th>
              <th>Contact via WhatsApp</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($quotes)): ?>
              <tr><td colspan="7" style="text-align:center; padding:30px; color:#64748b;">No open quote requests.</td></tr>
            <?php else: ?>
              <?php foreach ($quotes as $q): ?>
                <tr>
                  <td><strong><?= htmlspecialchars($q['quote_code']) ?></strong></td>
                  <td>
                    <strong><?= htmlspecialchars($q['customer_name']) ?></strong><br>
                    <span style="font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($q['company_name'] ?: 'Private Trader') ?></span>
                  </td>
                  <td><strong><?= htmlspecialchars($q['product_name']) ?></strong></td>
                  <td><strong><?= $q['requested_qty'] ?></strong> units</td>
                  <td><?= $q['target_price'] ? format_cfa($q['target_price']) : 'Open' ?></td>
                  <td style="font-size:0.75rem; max-width:200px;"><?= htmlspecialchars($q['message']) ?></td>
                  <td>
                    <a href="https://wa.me/<?= preg_replace('/[^0-9]/', '', $q['customer_phone']) ?>?text=Hello+<?= urlencode($q['customer_name']) ?>+regarding+your+MMK+Hub+quote+<?= $q['quote_code'] ?>" 
                       target="_blank" style="background:#25d366; color:#fff; padding:4px 10px; border-radius:4px; font-size:0.75rem; font-weight:700; display:inline-block;">
                      💬 WhatsApp
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

    <!-- TAB 5: SERVICE APPOINTMENTS -->
    <?php elseif ($tab === 'bookings'): ?>
      <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 24px;">
        <h3 style="font-size: 1.25rem; margin-bottom: 16px;">Scheduled On-Site Solar Technician Installations</h3>

        <table class="admin-table">
          <thead>
            <tr>
              <th>Date & Slot</th>
              <th>Customer</th>
              <th>Phone</th>
              <th>Location Address</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($bookings)): ?>
              <tr><td colspan="5" style="text-align:center; padding:30px; color:#64748b;">No scheduled service appointments.</td></tr>
            <?php else: ?>
              <?php foreach ($bookings as $b): ?>
                <tr>
                  <td>
                    <strong><?= htmlspecialchars($b['service_date']) ?></strong><br>
                    <span style="font-size:0.75rem; color:#64748b;"><?= htmlspecialchars($b['service_slot']) ?></span>
                  </td>
                  <td><strong><?= htmlspecialchars($b['customer_name']) ?></strong></td>
                  <td><strong><?= htmlspecialchars($b['customer_phone']) ?></strong></td>
                  <td><?= htmlspecialchars($b['address']) ?></td>
                  <td><span class="price-mode-label wholesale"><?= strtoupper($b['status']) ?></span></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

  </main>
</div>

</body>
</html>
