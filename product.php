<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$is_ws = is_wholesale();
$lang = get_lang();

$id = intval($_GET['id'] ?? 1);
$stmt = $db->prepare("SELECT * FROM products WHERE id = ?");
$stmt->execute([$id]);
$product = $stmt->fetch();

if (!$product) {
    echo "<div style='text-align:center; padding:100px 20px;'><h2>Product Not Found</h2><a href='index.php'>Return to Catalog</a></div>";
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$tiers = !empty($product['tier_discounts']) ? json_decode($product['tier_discounts'], true) : [];
$title = $lang === 'fr' ? $product['name_fr'] : $product['name_en'];
$desc = $lang === 'fr' ? $product['description_fr'] : $product['description_en'];
$activePrice = $is_ws ? $product['wholesale_price'] : $product['retail_price'];
$minQty = $is_ws ? max(1, $product['moq']) : 1;
?>

<div style="max-width: 1200px; margin: 40px auto; padding: 0 20px;">
  
  <!-- Breadcrumbs -->
  <div style="font-size: 0.85rem; color: #64748b; margin-bottom: 24px;">
    <a href="index.php"><?= t('Home', 'Accueil') ?></a> / 
    <a href="index.php?cat=<?= urlencode($product['category']) ?>"><?= htmlspecialchars($product['category']) ?></a> / 
    <span style="color: #0f172a; font-weight: 600;"><?= htmlspecialchars($title) ?></span>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: flex-start;">
    
    <!-- Image Box -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; overflow: hidden; padding: 20px; box-shadow: var(--shadow-sm);">
      <img src="<?= htmlspecialchars($product['image'] ?: 'assets/images/solar_lantern.jpg') ?>" 
           alt="<?= htmlspecialchars($title) ?>" 
           style="width: 100%; border-radius: 12px; height: 420px; object-fit: cover;">
      
      <div style="margin-top: 16px; display: flex; gap: 12px;">
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; flex: 1; text-align: center;">
          <div style="font-size: 0.75rem; color: #64748b;"><?= t('Stock Status', 'Disponibilité') ?></div>
          <div style="font-weight: 700; color: #047857;">✓ <?= $product['stock'] ?> <?= t('Units in Douala', 'Unités à Douala') ?></div>
        </div>
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; flex: 1; text-align: center;">
          <div style="font-size: 0.75rem; color: #64748b;"><?= t('SKU Code', 'Référence') ?></div>
          <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($product['sku']) ?></div>
        </div>
      </div>
    </div>

    <!-- Product Info & Interactive Volume Calculator -->
    <div>
      <div style="font-size: 0.8rem; text-transform: uppercase; color: #047857; font-weight: 800; letter-spacing: 0.5px; margin-bottom: 6px;">
        <?= htmlspecialchars($product['category']) ?>
      </div>
      <h1 style="font-size: 2rem; line-height: 1.25; margin-bottom: 16px;"><?= htmlspecialchars($title) ?></h1>
      
      <p style="font-size: 0.95rem; color: #475569; line-height: 1.6; margin-bottom: 24px;">
        <?= htmlspecialchars($desc) ?>
      </p>

      <!-- Pricing Highlight Card -->
      <div style="background: #f8fafc; border: 2px solid #e2e8f0; border-radius: 14px; padding: 20px; margin-bottom: 24px;">
        
        <div style="display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 12px;">
          <div>
            <span style="font-size: 2.2rem; font-weight: 800; color: <?= $is_ws ? '#047857' : '#0284c7' ?>;" id="liveUnitPrice">
              <?= format_cfa($activePrice) ?>
            </span>
            <span style="font-size: 0.85rem; color: #64748b;"> / <?= t('unit', 'unité') ?></span>
          </div>
          <span class="price-mode-label <?= $is_ws ? 'wholesale' : 'retail' ?>">
            <?= $is_ws ? t('Wholesale Channel Price', 'Tarif Grossiste B2B') : t('Standard Retail Price', 'Prix Public Détail') ?>
          </span>
        </div>

        <?php if ($is_ws && !empty($tiers) && !$product['is_service']): ?>
          <div style="margin-bottom: 18px; border-top: 1px dashed #cbd5e1; padding-top: 12px;">
            <div style="font-size: 0.825rem; font-weight: 700; margin-bottom: 8px; color: #0f172a;">
              📊 <?= t('Volume Discount Matrix (Automatic Tier Pricing):', 'Grille de Remises Volume Dégressives :') ?>
            </div>
            <table style="width: 100%; font-size: 0.8rem; border-collapse: collapse; text-align: left;">
              <tr style="background: #e2e8f0; font-weight: 700;">
                <th style="padding: 6px 10px;"><?= t('Quantity Range', 'Quantité') ?></th>
                <th style="padding: 6px 10px;"><?= t('Unit Price', 'Prix Unitaire') ?></th>
                <th style="padding: 6px 10px;"><?= t('Discount', 'Économie') ?></th>
              </tr>
              <?php foreach ($tiers as $tier): ?>
                <tr style="border-bottom: 1px solid #e2e8f0;">
                  <td style="padding: 6px 10px;"><?= $tier['min'] ?>+ <?= t('units', 'unités') ?></td>
                  <td style="padding: 6px 10px; font-weight: 700; color: #047857;"><?= format_cfa($tier['price'] ?? 0) ?></td>
                  <td style="padding: 6px 10px; color: #d97706; font-weight: 600;">-<?= $tier['discount_pct'] ?? 0 ?>%</td>
                </tr>
              <?php endforeach; ?>
            </table>
          </div>
        <?php endif; ?>

        <!-- Live Quantity Selector & Calculator -->
        <div style="display: flex; align-items: center; gap: 16px; margin-bottom: 18px;">
          <div>
            <label style="display: block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;">
              <?= t('Order Quantity:', 'Quantité à Commander :') ?>
            </label>
            <input type="number" id="detailQty" value="<?= $minQty ?>" min="<?= $minQty ?>" max="<?= $product['stock'] ?>"
                   style="width: 100px; padding: 10px; font-size: 1.1rem; font-weight: 700; border: 2px solid #cbd5e1; border-radius: 8px; text-align: center;"
                   oninput="updateVolumeCalculation()">
          </div>

          <div style="flex: 1; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px;">
            <div style="font-size: 0.75rem; color: #64748b;"><?= t('Calculated Subtotal', 'Sous-total Calculé') ?></div>
            <div style="font-size: 1.25rem; font-weight: 800; color: #0f172a;" id="liveSubtotal">
              <?= format_cfa($activePrice * $minQty) ?>
            </div>
            <?php if ($is_ws): ?>
              <div style="font-size: 0.725rem; color: #047857; font-weight: 600;" id="liveDepositNotice">
                <?= t('50% Deposit upfront: ', 'Acompte 50% requis : ') . format_cfa(($activePrice * $minQty) * 0.5) ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <?php if ($is_ws): ?>
          <div style="font-size: 0.775rem; color: #b45309; background: #fef3c7; border: 1px solid #fde68a; border-radius: 6px; padding: 8px 12px; margin-bottom: 16px;">
            ⚠️ <?= t("Wholesale MOQ: You must order at least {$product['moq']} units.", "MOQ Grossiste : Commande minimum de {$product['moq']} unités requise.") ?>
          </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div style="display: flex; gap: 12px; flex-wrap: wrap;">
          <button type="button" class="btn-card-primary" style="flex: 1; padding: 14px; font-size: 1rem;" onclick="addDetailToCart()">
            🛒 <?= t('Add to Cart & Checkout', 'Ajouter au Panier & Commander') ?>
          </button>
          
          <button type="button" class="btn-card-details" style="display: flex; align-items: center; gap: 8px; padding: 14px 18px;" onclick="orderViaWhatsApp()">
            💬 <?= t('Order via WhatsApp', 'Commander sur WhatsApp') ?>
          </button>
        </div>

      </div>

      <!-- Cameroon Delivery Estimator Box -->
      <div style="border: 1px solid #e2e8f0; border-radius: 12px; padding: 18px; font-size: 0.85rem;">
        <h4 style="font-size: 0.95rem; margin-bottom: 8px;">🚚 <?= t('Cameroon Logistics Dispatch Times:', 'Délais d\'Expédition au Cameroun :') ?></h4>
        <ul style="list-style: none; display: flex; flex-direction: column; gap: 6px; color: #64748b;">
          <li>📍 <strong>Douala (Akwa, Bonapriso, Bonabéri):</strong> <?= t('Same-day / 24h Courier (1 500 FCFA)', 'Livraison coursier sous 24h (1 500 FCFA)') ?></li>
          <li>📍 <strong>Yaoundé (Mokolo, Bastos, Centre):</strong> <?= t('Express Shuttle 24h (2 000 FCFA)', 'Navette express 24h (2 000 FCFA)') ?></li>
          <li>📍 <strong>West & South-West (Bafoussam, Bamenda, Buea):</strong> <?= t('Regional Freight Agency 48h (3 500 FCFA)', 'Agences partenaires 48h (3 500 FCFA)') ?></li>
          <li>📍 <strong>Douala Akwa Depot Pickup:</strong> <?= t('Free (Immediate Collection)', 'Gratuit (Retrait immédiat au dépôt)') ?></li>
        </ul>
      </div>

    </div>

  </div>
</div>

<script>
const basePrice = <?= $activePrice ?>;
const tiersJson = <?= json_encode($tiers) ?>;
const isWholesale = <?= $is_ws ? 'true' : 'false' ?>;
const moq = <?= $product['moq'] ?>;
const prodTitle = <?= json_encode($title) ?>;
const prodId = <?= $product['id'] ?>;

function updateVolumeCalculation() {
  const qtyInput = document.getElementById('detailQty');
  let qty = parseInt(qtyInput.value) || 1;

  if (isWholesale && qty < moq) {
    qty = moq;
  }

  let unitPrice = basePrice;
  if (isWholesale && tiersJson && tiersJson.length > 0) {
    tiersJson.forEach(tier => {
      if (tier.min && qty >= tier.min) {
        if (tier.price) unitPrice = tier.price;
        else if (tier.discount_pct) unitPrice = Math.round(basePrice * (1 - tier.discount_pct / 100));
      }
    });
  }

  const subtotal = unitPrice * qty;
  document.getElementById('liveUnitPrice').textContent = MMK.formatCFA(unitPrice);
  document.getElementById('liveSubtotal').textContent = MMK.formatCFA(subtotal);

  const depositEl = document.getElementById('liveDepositNotice');
  if (depositEl) {
    depositEl.textContent = '50% Deposit upfront: ' + MMK.formatCFA(subtotal * 0.5);
  }
}

function addDetailToCart() {
  const qty = parseInt(document.getElementById('detailQty').value) || 1;
  MMK.addToCart({
    id: prodId,
    name: prodTitle,
    retail_price: <?= $product['retail_price'] ?>,
    wholesale_price: <?= $product['wholesale_price'] ?>,
    image: '<?= htmlspecialchars($product['image']) ?>',
    moq: moq,
    is_service: <?= $product['is_service'] ?>,
    is_bundle: <?= $product['is_bundle'] ?>
  }, qty);
}

function orderViaWhatsApp() {
  const qty = document.getElementById('detailQty').value;
  const unitPrice = document.getElementById('liveUnitPrice').textContent;
  const subtotal = document.getElementById('liveSubtotal').textContent;
  const text = `Hello MMK Hub! I am interested in purchasing ${qty} unit(s) of "${prodTitle}" at ${unitPrice}/unit (Total: ${subtotal}). Mode: ${isWholesale ? 'Wholesale B2B' : 'Retail'}. Please confirm dispatch!`;
  window.open('https://wa.me/237670000000?text=' + encodeURIComponent(text), '_blank');
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
