<?php
require_once __DIR__ . '/includes/header.php';

$code = trim($_GET['code'] ?? '');
$db = get_db();
$stmt = $db->prepare("SELECT * FROM orders WHERE order_code = ?");
$stmt->execute([$code]);
$order = $stmt->fetch();

if (!$order) {
    echo "<div style='text-align:center; padding:80px 20px;'><h2>Order Not Found</h2><a href='index.php'>Return to Catalog</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$is_ws = $order['order_type'] === 'wholesale';
$whatsappMsg = "Hello MMK Hub! I have confirmed payment for Order {$order['order_code']} ({$order['customer_name']}). Total: " . format_cfa($order['total_amount']) . ". Please dispatch from Douala depot!";
$waUrl = "https://wa.me/237670000000?text=" . urlencode($whatsappMsg);
?>

<div style="max-width: 760px; margin: 50px auto; padding: 0 20px; text-align: center;">
  
  <div style="width: 80px; height: 80px; background: #ecfdf5; border: 2px solid #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: #047857; margin: 0 auto 20px;">
    ✓
  </div>

  <h1 style="font-size: 2.2rem; margin-bottom: 8px;"><?= t('Order Successfully Authorized!', 'Commande Autorisée avec Succès !') ?></h1>
  <p style="font-size: 1rem; color: #64748b; margin-bottom: 24px;">
    <?= t('Thank you for trusting MMK Hub. Your direct owner fulfillment process has begun.', 'Merci de votre confiance. Le traitement direct par le propriétaire est en cours.') ?>
  </p>

  <!-- Order Reference Pill -->
  <div style="background: #fff; border: 2px dashed #cbd5e1; border-radius: 14px; padding: 24px; margin-bottom: 28px; text-align: left;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
      <div>
        <div style="font-size: 0.8rem; color: #64748b;"><?= t('Tracking Reference Code:', 'Code de Suivi Unique :') ?></div>
        <div style="font-size: 1.5rem; font-weight: 800; color: #047857; letter-spacing: 1px;"><?= htmlspecialchars($order['order_code']) ?></div>
      </div>
      <span class="price-mode-label <?= $is_ws ? 'wholesale' : 'retail' ?>">
        <?= $is_ws ? 'Wholesale B2B Order' : 'Retail Direct Order' ?>
      </span>
    </div>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; font-size: 0.9rem; margin-bottom: 16px;">
      <div>
        <span style="color: #64748b;"><?= t('Customer Name:', 'Client :') ?></span>
        <strong><?= htmlspecialchars($order['customer_name']) ?></strong>
      </div>
      <div>
        <span style="color: #64748b;"><?= t('Phone Number:', 'Téléphone :') ?></span>
        <strong><?= htmlspecialchars($order['customer_phone']) ?></strong>
      </div>
      <div>
        <span style="color: #64748b;"><?= t('Destination City:', 'Destination :') ?></span>
        <strong><?= htmlspecialchars($order['city']) ?></strong>
      </div>
      <div>
        <span style="color: #64748b;"><?= t('Payment Status:', 'Statut Règlement :') ?></span>
        <strong style="color: #047857;">✓ <?= strtoupper(str_replace('_', ' ', $order['payment_status'])) ?></strong>
      </div>
    </div>

    <div style="background: #f8fafc; border-radius: 8px; padding: 14px; display: flex; justify-content: space-between; font-size: 1rem;">
      <span><?= t('Total Amount Paid / Authorized:', 'Montant Total Règlement :') ?></span>
      <strong style="color: #0f172a; font-size: 1.15rem;"><?= format_cfa($order['total_amount']) ?></strong>
    </div>

    <?php if ($is_ws): ?>
      <div style="margin-top: 12px; font-size: 0.825rem; color: #047857; background: #ecfdf5; padding: 10px; border-radius: 6px;">
        ℹ️ <strong><?= t('Wholesale Deposit Record:', 'Acompte Grossiste :') ?></strong> <?= format_cfa($order['deposit_amount']) ?> <?= t('paid. Remaining balance of', 'réglé. Solde restant de') ?> <?= format_cfa($order['balance_amount']) ?> <?= t('due upon courier delivery inspection.', 'à régler à la réception du matériel.') ?>
      </div>
    <?php endif; ?>
  </div>

  <!-- Action CTA Buttons -->
  <div style="display: flex; gap: 14px; justify-content: center; flex-wrap: wrap;">
    <a href="<?= $waUrl ?>" target="_blank" class="floating-whatsapp" style="position: static; box-shadow: var(--shadow-md);">
      <span>💬</span> <span><?= t('Sync & Notify Owner on WhatsApp', 'Notifier le Propriétaire sur WhatsApp') ?></span>
    </a>

    <a href="track.php?code=<?= urlencode($order['order_code']) ?>" class="btn-card-primary" style="padding: 12px 24px; font-size: 0.95rem;">
      🚚 <?= t('View Live Order Tracking & Invoice →', 'Suivre le Colis & Facture Proforma →') ?>
    </a>
  </div>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
