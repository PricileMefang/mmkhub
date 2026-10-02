<?php
require_once __DIR__ . '/includes/header.php';

$code = trim($_GET['code'] ?? '');
$db = get_db();
$order = null;
$items = [];

if (!empty($code)) {
    $stmt = $db->prepare("SELECT * FROM orders WHERE order_code = ?");
    $stmt->execute([$code]);
    $order = $stmt->fetch();

    if ($order) {
        $stmtItems = $db->prepare("SELECT * FROM order_items WHERE order_id = ?");
        $stmtItems->execute([$order['id']]);
        $items = $stmtItems->fetchAll();
    }
}

$statusMap = [
    'received' => 1,
    'processing' => 2,
    'shipped' => 3,
    'delivered' => 4
];
$currentStep = $order ? ($statusMap[$order['fulfillment_status']] ?? 1) : 1;
?>

<div style="max-width: 960px; margin: 40px auto; padding: 0 20px;">
  
  <div style="text-align: center; margin-bottom: 32px;" class="no-print">
    <h1 style="font-size: 2.2rem; margin-bottom: 6px;">🚚 <?= t('Real-Time Order & Logistics Tracker', 'Suivi de Commande en Temps Réel') ?></h1>
    <p style="color: #64748b;"><?= t('Enter your 8-character MMK Hub tracking code to inspect delivery status or print your formal proforma invoice.', 'Saisissez votre code MMK Hub pour suivre l\'acheminement ou imprimer votre facture.') ?></p>
    
    <!-- Code Lookup Form -->
    <form method="GET" action="track.php" style="margin-top: 20px; display: inline-flex; gap: 8px;">
      <input type="text" name="code" value="<?= htmlspecialchars($code) ?>" placeholder="e.g. MMK-78419" required
             style="padding: 12px 18px; border-radius: 8px; border: 2px solid #cbd5e1; font-size: 1rem; font-weight: 700; text-transform: uppercase;">
      <button type="submit" class="btn-card-primary" style="padding: 12px 24px; font-size: 1rem;">
        🔍 <?= t('Track', 'Vérifier') ?>
      </button>
    </form>
  </div>

  <?php if ($order): ?>
    
    <!-- 4-Stage Visual Stepper Timeline (No-print) -->
    <div class="no-print" style="margin-bottom: 36px;">
      <div class="tracking-stepper">
        
        <div class="step-node <?= $currentStep >= 1 ? 'completed' : '' ?> <?= $currentStep === 1 ? 'current' : '' ?>">
          <div class="step-number"><?= $currentStep > 1 ? '✓' : '1' ?></div>
          <div style="font-weight: 700; font-size: 0.9rem;"><?= t('1. Order Received', '1. Reçue & Validée') ?></div>
          <div style="font-size: 0.75rem; color: #64748b;"><?= t('Payment / Deposit verified', 'Paiement ou acompte vérifié') ?></div>
        </div>

        <div class="step-node <?= $currentStep >= 2 ? 'completed' : '' ?> <?= $currentStep === 2 ? 'current' : '' ?>">
          <div class="step-number"><?= $currentStep > 2 ? '✓' : '2' ?></div>
          <div style="font-weight: 700; font-size: 0.9rem;"><?= t('2. Picking & Quality Check', '2. Préparation & Contrôle') ?></div>
          <div style="font-size: 0.75rem; color: #64748b;"><?= t('Inspected at Douala Depot', 'Conditionnement dépôt Akwa') ?></div>
        </div>

        <div class="step-node <?= $currentStep >= 3 ? 'completed' : '' ?> <?= $currentStep === 3 ? 'current' : '' ?>">
          <div class="step-number"><?= $currentStep > 3 ? '✓' : '3' ?></div>
          <div style="font-weight: 700; font-size: 0.9rem;"><?= t('3. Dispatched / In Transit', '3. Expédié / En Route') ?></div>
          <div style="font-size: 0.75rem; color: #64748b;"><?= t('Courier or regional shuttle en route', 'Coursier ou navette en transit') ?></div>
        </div>

        <div class="step-node <?= $currentStep >= 4 ? 'completed' : '' ?> <?= $currentStep === 4 ? 'current' : '' ?>">
          <div class="step-number"><?= $currentStep >= 4 ? '✓' : '4' ?></div>
          <div style="font-weight: 700; font-size: 0.9rem;"><?= t('4. Delivered & Settled', '4. Livré & Clôturé') ?></div>
          <div style="font-size: 0.75rem; color: #64748b;"><?= t('Receipt confirmed by customer', 'Réception confirmée par le client') ?></div>
        </div>

      </div>

      <div style="text-align: right; margin-bottom: 20px;">
        <button type="button" class="btn-card-details" onclick="window.print()" style="font-weight: 700;">
          🖨️ <?= t('Print Formal Proforma Invoice (PDF)', 'Imprimer Facture Proforma (PDF)') ?>
        </button>
      </div>
    </div>

    <!-- Formal Printable Proforma Invoice & Order Sheet -->
    <div class="invoice-container">
      
      <div class="invoice-stamp">
        <?= $order['payment_status'] === 'fully_paid' ? 'ACQUITTÉ / PAID' : 'ACOMPTE PAYÉ / 50% DEPOSIT' ?>
      </div>

      <!-- Invoice Header -->
      <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #0f172a; padding-bottom: 20px; margin-bottom: 24px;">
        <div>
          <h2 style="font-size: 1.8rem; color: #047857; margin-bottom: 4px;">MMK HUB SARL</h2>
          <div style="font-size: 0.85rem; color: #475569; line-height: 1.4;">
            RC/DLA/2026/B/8921 • NIU: M0226149204A<br>
            Akwa Commercial Center, Douala, Cameroon<br>
            Phone / WhatsApp: +237 670 000 000 • Email: contact@mmkhub.cm
          </div>
        </div>

        <div style="text-align: right;">
          <h3 style="font-size: 1.4rem; color: #0f172a;"><?= t('PROFORMA INVOICE', 'FACTURE PROFORMA') ?></h3>
          <div style="font-size: 0.9rem; font-weight: 700; color: #047857;">N° <?= htmlspecialchars($order['order_code']) ?></div>
          <div style="font-size: 0.8rem; color: #64748b;"><?= t('Date:', 'Date :') ?> <?= date('d/m/Y H:i', strtotime($order['created_at'])) ?></div>
        </div>
      </div>

      <!-- Client & Logistics Info -->
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 24px; margin-bottom: 28px; font-size: 0.875rem;">
        <div style="background: #f8fafc; padding: 16px; border-radius: 8px;">
          <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px; text-transform: uppercase;"><?= t('Billed To (Customer):', 'Facturé à (Client) :') ?></div>
          <div><strong><?= htmlspecialchars($order['customer_name']) ?></strong></div>
          <div><?= t('Tel:', 'Tél :') ?> <?= htmlspecialchars($order['customer_phone']) ?></div>
          <div><?= t('Email:', 'Email :') ?> <?= htmlspecialchars($order['customer_email'] ?: 'N/A') ?></div>
          <div><?= t('Channel Mode:', 'Canal :') ?> <strong><?= strtoupper($order['order_type']) ?></strong></div>
        </div>

        <div style="background: #f8fafc; padding: 16px; border-radius: 8px;">
          <div style="font-weight: 800; color: #0f172a; margin-bottom: 6px; text-transform: uppercase;"><?= t('Delivery Destination:', 'Lieu de Livraison :') ?></div>
          <div><?= t('City:', 'Ville :') ?> <strong><?= htmlspecialchars($order['city']) ?></strong></div>
          <div><?= t('Address:', 'Adresse :') ?> <?= htmlspecialchars($order['delivery_address']) ?></div>
          <div><?= t('Method:', 'Mode :') ?> <?= htmlspecialchars($order['delivery_method']) ?></div>
          <div><?= t('Payment Rail:', 'Moyen de Paiement :') ?> <strong><?= strtoupper($order['payment_method']) ?></strong> (Ref: <?= htmlspecialchars($order['payment_ref']) ?>)</div>
        </div>
      </div>

      <!-- Itemized Table -->
      <table style="width: 100%; border-collapse: collapse; margin-bottom: 24px; font-size: 0.875rem;">
        <thead>
          <tr style="background: #0f172a; color: #fff; text-align: left;">
            <th style="padding: 10px 14px;"><?= t('Item Description', 'Désignation des Articles') ?></th>
            <th style="padding: 10px 14px; text-align: center;"><?= t('Qty', 'Qté') ?></th>
            <th style="padding: 10px 14px; text-align: right;"><?= t('Unit Price', 'Prix Unitaire') ?></th>
            <th style="padding: 10px 14px; text-align: right;"><?= t('Total FCFA', 'Total FCFA') ?></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $item): ?>
            <tr style="border-bottom: 1px solid #e2e8f0;">
              <td style="padding: 10px 14px; font-weight: 600;"><?= htmlspecialchars($item['product_name']) ?></td>
              <td style="padding: 10px 14px; text-align: center; font-weight: 700;"><?= $item['quantity'] ?></td>
              <td style="padding: 10px 14px; text-align: right;"><?= format_cfa($item['unit_price']) ?></td>
              <td style="padding: 10px 14px; text-align: right; font-weight: 700; color: #047857;"><?= format_cfa($item['subtotal']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>

      <!-- Financial Summary & Balance Breakdown -->
      <div style="display: flex; justify-content: flex-end; margin-bottom: 30px;">
        <div style="width: 320px; font-size: 0.9rem; display: flex; flex-direction: column; gap: 8px;">
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;"><?= t('Subtotal:', 'Sous-total :') ?></span>
            <span style="font-weight: 700;"><?= format_cfa($order['subtotal']) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;"><?= t('Delivery Logistics:', 'Frais d\'Acheminement :') ?></span>
            <span style="font-weight: 700;"><?= format_cfa($order['delivery_fee']) ?></span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 1.15rem; font-weight: 800; border-top: 2px solid #0f172a; padding-top: 8px;">
            <span><?= t('Grand Total:', 'Total TTC :') ?></span>
            <span style="color: #047857;"><?= format_cfa($order['total_amount']) ?></span>
          </div>

          <?php if ($order['order_type'] === 'wholesale'): ?>
            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 10px; margin-top: 8px; font-size: 0.8rem;">
              <div style="display: flex; justify-content: space-between; color: #047857; font-weight: 700;">
                <span><?= t('Deposit Settled (50%):', 'Acompte Réglé (50%) :') ?></span>
                <span><?= format_cfa($order['deposit_amount']) ?></span>
              </div>
              <div style="display: flex; justify-content: space-between; color: #b45309; font-weight: 700; margin-top: 4px;">
                <span><?= t('Balance Due on Delivery:', 'Solde Dû à la Livraison :') ?></span>
                <span><?= format_cfa($order['balance_amount']) ?></span>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Legal / Signature Area -->
      <div style="border-top: 1px solid #e2e8f0; padding-top: 20px; display: flex; justify-content: space-between; align-items: flex-end; font-size: 0.775rem; color: #64748b;">
        <div>
          <?= t('Goods sold are warranted by MMK Hub for quality & compliance under Cameroon commercial law.', 'Matériel garanti d\'origine et certifié conforme par MMK Hub selon la législation camerounaise.') ?>
        </div>
        <div style="text-align: right;">
          <strong>MMK HUB DIRECTION COMMERCIALE</strong><br>
          <em>Visa & Signature Électronique</em>
        </div>
      </div>

    </div>

  <?php elseif (!empty($code)): ?>
    <div style="text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px;">
      <p style="color: #ef4444; font-size: 1.1rem; font-weight: 700;"><?= t('No order found with code: ', 'Aucune commande trouvée avec le code : ') . htmlspecialchars($code) ?></p>
      <p style="color: #64748b; font-size: 0.85rem; margin-top: 6px;"><?= t('Please check your order confirmation SMS or WhatsApp message.', 'Veuillez vérifier votre SMS ou message WhatsApp de confirmation.') ?></p>
    </div>
  <?php endif; ?>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
