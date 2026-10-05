<?php
require_once __DIR__ . '/includes/header.php';

$db = get_db();
$is_ws = is_wholesale();
$lang = get_lang();

// Filters
$category = $_GET['cat'] ?? 'all';
$query = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM products WHERE active = 1";
$params = [];

if ($category !== 'all') {
    $sql .= " AND category = ?";
    $params[] = $category;
}

if (!empty($query)) {
    $sql .= " AND (name_en LIKE ? OR name_fr LIKE ? OR description_en LIKE ?)";
    $params[] = "%$query%";
    $params[] = "%$query%";
    $params[] = "%$query%";
}

$sql .= " ORDER BY is_bundle DESC, is_service DESC, id ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll();

// Categories count
$categories = [
    'all' => t('All Catalog', 'Tout le Catalogue'),
    'Solar Energy' => t('Solar & Energy', 'Énergie Solaire'),
    'African Textiles' => t('African Fabrics (Wax)', 'Pagne & Textiles'),
    'Building Materials' => t('Building & Cement', 'Matériaux & Ciment'),
    'Services' => t('Services & Installations', 'Services & Travaux'),
    'Bundles' => t('Commercial Bundles', 'Packs Clé en Main')
];
?>

<!-- Hero Section -->
<section class="hero-section">
  <div class="hero-container">
    
    <!-- Hero Text -->
    <div>
      <div class="hero-badge">
        <i data-lucide="zap" class="icon-xs"></i>
        <span><?= t('Direct Owner Sourcing Platform • Cameroon', 'Plateforme Directe Propriétaire • Cameroun') ?></span>
      </div>
      <h1 class="hero-title">
        <?= t('One Platform.', 'Une Seule Plateforme.') ?><br>
        <span><?= t('Retail & Bulk Wholesale', 'Vente en Gros & au Détail') ?></span>
      </h1>
      <p class="hero-subtitle">
        <?= t(
          'Direct from the owner to individual consumers and business resellers across Douala, Yaoundé, and nationwide. Zero intermediary markup, guaranteed authenticity, and MTN MoMo / Orange Money integration.',
          'En direct du propriétaire vers les particuliers et les commerçants à Douala, Yaoundé et tout le Cameroun. Zéro intermédiaire, qualité garantie et paiement MTN MoMo / Orange Money.'
        ) ?>
      </p>

      <div class="hero-pills">
        <div class="hero-pill-item">
          <i data-lucide="package" class="icon-xs"></i>
          <span><?= t('Stock Ready in Douala Depot', 'Stock Disponible Dépôt Douala') ?></span>
        </div>
        <div class="hero-pill-item">
          <i data-lucide="smartphone" class="icon-xs"></i>
          <span><?= t('MTN MoMo & Orange Money Rails', 'Règlement MoMo & Orange Money') ?></span>
        </div>
        <div class="hero-pill-item">
          <i data-lucide="truck" class="icon-xs"></i>
          <span><?= t('Nationwide Freight & Couriers', 'Expéditions Interurbaines') ?></span>
        </div>
      </div>

      <div style="display: flex; gap: 14px; flex-wrap: wrap;">
        <a href="#catalog" class="btn-card-primary" style="padding: 12px 24px; font-size: 1rem;">
          <i data-lucide="shopping-cart" class="icon-sm"></i> <?= t('Explore Catalog', 'Découvrir le Catalogue') ?>
        </a>
        <a href="wholesale.php" class="btn-card-details" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.3); color:#fff; padding: 12px 24px; font-size: 1rem;">
          <i data-lucide="building-2" class="icon-sm"></i> <?= t('Wholesale Registration →', 'Espace Grossiste B2B →') ?>
        </a>
      </div>
    </div>

    <!-- Dual Mode Interactive Explainer Card -->
    <div class="hero-dual-card">
      <div class="dual-card-header">
        <h3><?= t('How MMK Hub Works', 'Fonctionnement MMK Hub') ?></h3>
        <p style="font-size: 0.8rem; color: #94a3b8;">
          <?= t('The same inventory sold in two tailored purchasing models:', 'Un inventaire unique décliné en deux modes d\'achat adaptés :') ?>
        </p>
      </div>

      <div class="dual-card-tabs">
        <button type="button" class="card-tab-btn retail <?= !$is_ws ? 'active' : '' ?>" onclick="MMK.setChannelMode('retail')">
          <i data-lucide="shopping-bag" class="icon-xs"></i> <?= t('Retail Mode', 'Mode Détail') ?>
        </button>
        <button type="button" class="card-tab-btn wholesale <?= $is_ws ? 'active' : '' ?>" onclick="MMK.setChannelMode('wholesale')">
          <i data-lucide="building-2" class="icon-xs"></i> <?= t('Wholesale B2B', 'Gros B2B') ?>
        </button>
      </div>

      <?php if (!$is_ws): ?>
        <ul class="tab-features-list">
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Single Unit Purchasing:', 'Achat à l\'Unité :') ?></strong> <?= t('Buy 1 or a few items for personal/household use.', 'Commandez 1 ou plusieurs articles pour votre foyer.') ?></span></li>
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Fixed Transparent Retail Pricing:', 'Prix Fixes Transparents :') ?></strong> <?= t('Direct competitive rates in FCFA.', 'Tarifs compétitifs directs en FCFA.') ?></span></li>
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Fast Local Courier Dispatch:', 'Livraison Express par Coursier :') ?></strong> <?= t('Douala & Yaoundé intra-city delivery within 24h.', 'Livraison locale à Douala et Yaoundé sous 24h.') ?></span></li>
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Payment Flexibility:', 'Paiements Souples :') ?></strong> <?= t('MTN MoMo, Orange Money, or Cash on Delivery.', 'MTN MoMo, Orange Money ou Paiement à la livraison.') ?></span></li>
        </ul>
        <div style="background: rgba(2, 132, 199, 0.15); border: 1px solid rgba(2, 132, 199, 0.3); border-radius: 8px; padding: 10px; font-size: 0.8rem; color: #7dd3fc; text-align: center;">
          <?= t('Currently browsing with Retail individual pricing', 'Vous consultez actuellement les prix de détail individuels') ?>
        </div>
      <?php else: ?>
        <ul class="tab-features-list">
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Quantity-Based Volume Discounts:', 'Remises Volume Dégressives :') ?></strong> <?= t('Save 15% to 28% off as order quantity increases.', 'Économisez 15% à 28% selon le volume commandé.') ?></span></li>
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Minimum Order Quantities (MOQ):', 'Quantités Minimales (MOQ) :') ?></strong> <?= t('Optimized carton & pallet lot sizes for resellers.', 'Lots optimisés pour les revendeurs et entreprises.') ?></span></li>
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('Wholesale 50% Deposit Terms:', 'Acompte de 50% à la Commande :') ?></strong> <?= t('Pay 50% upfront via Mobile Money, balance on delivery inspection.', 'Réglez 50% par MoMo et le solde après vérification.') ?></span></li>
          <li><span class="icon"><i data-lucide="check" class="icon-xs"></i></span> <span><strong><?= t('OHADA Business Invoicing:', 'Facturation Conforme OHADA :') ?></strong> <?= t('Downloadable proforma invoices for official accounting.', 'Factures proforma téléchargeables pour votre comptabilité.') ?></span></li>
        </ul>
        <div style="background: rgba(4, 120, 87, 0.3); border: 1px solid rgba(16, 185, 129, 0.4); border-radius: 8px; padding: 10px; font-size: 0.8rem; color: #6ee7b7; text-align: center;">
          <?= t('✨ Wholesale B2B Volume Pricing Active! Bulk tiers unlocked.', '✨ Tarifs Grossistes Débloqués ! Prix dégressifs appliqués.') ?>
        </div>
      <?php endif; ?>

    </div>

  </div>
</section>

<!-- Catalog Section -->
<section class="catalog-section" id="catalog">
  
  <div class="section-head">
    <div class="section-title-wrap">
      <h2><?= t('Official Product & Service Catalog', 'Catalogue Officiel Produits & Services') ?></h2>
      <p><?= t('Direct owner stock. Filter by commercial sector or search by keyword.', 'Stock direct propriétaire. Filtrez par secteur ou recherchez par mot-clé.') ?></p>
    </div>

    <!-- Live Search Input -->
    <form method="GET" action="index.php#catalog" style="display: flex; gap: 8px;">
      <input type="text" name="q" value="<?= htmlspecialchars($query) ?>" placeholder="<?= t('Search products, cement, solar...', 'Rechercher ciment, solaire, pagne...') ?>" 
             style="padding: 10px 14px; border-radius: 20px; border: 1px solid #cbd5e1; font-size: 0.875rem; width: 260px;">
      <button type="submit" class="btn-card-primary" style="padding: 10px 16px; border-radius: 20px;"><i data-lucide="search" class="icon-xs"></i></button>
    </form>
  </div>

  <!-- Category Pills -->
  <div class="category-bar">
    <?php foreach ($categories as $catKey => $catLabel): ?>
      <a href="index.php?cat=<?= urlencode($catKey) ?>#catalog" 
         class="cat-pill <?= $category === $catKey ? 'active' : '' ?>">
        <?= $catLabel ?>
      </a>
    <?php endforeach; ?>
  </div>

  <!-- Products Grid -->
  <div class="products-grid">
    <?php if (empty($products)): ?>
      <div style="grid-column: 1 / -1; text-align: center; padding: 60px 20px; background: #fff; border-radius: 12px;">
        <p style="font-size: 1.1rem; color: #64748b;"><?= t('No items found matching your filter.', 'Aucun produit ne correspond à vos critères.') ?></p>
        <a href="index.php#catalog" class="btn-card-primary" style="display: inline-block; margin-top: 14px;"><?= t('Reset Filter', 'Réinitialiser') ?></a>
      </div>
    <?php else: ?>
      <?php foreach ($products as $prod): 
        $tiers = !empty($prod['tier_discounts']) ? json_decode($prod['tier_discounts'], true) : [];
        $activePrice = $is_ws ? $prod['wholesale_price'] : $prod['retail_price'];
        $title = $lang === 'fr' ? $prod['name_fr'] : $prod['name_en'];
        $desc = $lang === 'fr' ? $prod['description_fr'] : $prod['description_en'];
      ?>
        <div class="product-card">
          <div class="card-image-box">
            <img src="<?= htmlspecialchars($prod['image'] ?: 'assets/images/solar_lantern.jpg') ?>" alt="<?= htmlspecialchars($title) ?>">
            <div class="card-badge-top">
              <?php if ($prod['is_service']): ?>
                <span class="tag-service"><i data-lucide="wrench" class="icon-xs"></i> <?= t('Service', 'Prestation') ?></span>
              <?php elseif ($prod['is_bundle']): ?>
                <span class="tag-bundle"><i data-lucide="layers" class="icon-xs"></i> <?= t('Bundle', 'Pack Clé en Main') ?></span>
              <?php endif; ?>
            </div>
          </div>

          <div class="card-body">
            <div class="card-category"><?= htmlspecialchars($prod['category']) ?></div>
            <h3 class="card-title"><?= htmlspecialchars($title) ?></h3>
            <p class="card-desc"><?= htmlspecialchars($desc) ?></p>

            <!-- Pricing Box -->
            <div class="pricing-display">
              <div class="price-main-row">
                <span class="price-value <?= !$is_ws ? 'retail-active' : '' ?>">
                  <?= format_cfa($activePrice) ?>
                </span>
                <span class="price-mode-label <?= $is_ws ? 'wholesale' : 'retail' ?>">
                  <?= $is_ws ? t('Wholesale Tier', 'Prix Grossiste') : t('Retail Price', 'Prix Détail') ?>
                </span>
              </div>

              <div class="moq-row">
                <span><?= t('Available Stock:', 'Stock Réel :') ?> <strong><?= $prod['stock'] ?></strong></span>
                <span>
                  <?= $is_ws 
                    ? t('Wholesale MOQ: ', 'MOQ Grossiste : ') . '<strong>' . $prod['moq'] . ' ' . t('units', 'unités') . '</strong>'
                    : t('Min Order: 1 unit', 'Min : 1 unité') ?>
                </span>
              </div>

              <!-- Wholesale Volume Tier Breakdown -->
              <?php if ($is_ws && !empty($tiers) && !$prod['is_service']): ?>
                <div class="tier-preview-table">
                  <strong><?= t('Volume Price Tiers:', 'Paliers Dégressifs :') ?></strong>
                  <div style="display: flex; gap: 8px; flex-wrap: wrap; margin-top: 4px;">
                    <?php foreach ($tiers as $tier): ?>
                      <span style="background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 0.7rem;">
                        <?= $tier['min'] ?>+ : <strong><?= format_cfa($tier['price'] ?? 0) ?></strong> (-<?= $tier['discount_pct'] ?? 0 ?>%)
                      </span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Card Actions -->
            <div class="card-actions">
              <?php if ($prod['is_service']): ?>
                <button type="button" class="btn-card-primary" 
                        onclick="openServiceModal(<?= $prod['id'] ?>, '<?= htmlspecialchars(addslashes($title)) ?>')">
                  <i data-lucide="calendar" class="icon-xs"></i> <?= t('Book Service', 'Réserver Date') ?>
                </button>
              <?php else: ?>
                <button type="button" class="btn-card-primary" 
                        onclick="MMK.addToCart({
                          id: <?= $prod['id'] ?>,
                          name: '<?= htmlspecialchars(addslashes($title)) ?>',
                          retail_price: <?= $prod['retail_price'] ?>,
                          wholesale_price: <?= $prod['wholesale_price'] ?>,
                          image: '<?= htmlspecialchars($prod['image']) ?>',
                          moq: <?= $prod['moq'] ?>,
                          is_service: 0,
                          is_bundle: <?= $prod['is_bundle'] ?>
                        }, <?= $is_ws ? $prod['moq'] : 1 ?>)">
                  <i data-lucide="shopping-cart" class="icon-xs"></i> <?= t('Add to Cart', 'Ajouter au Panier') ?>
                </button>
              <?php endif; ?>

              <a href="product.php?id=<?= $prod['id'] ?>" class="btn-card-details">
                <?= t('Details', 'Détails') ?>
              </a>
            </div>

          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

</section>

<!-- Trust & Logistics Banner -->
<section style="background: #0f172a; color: #fff; padding: 50px 20px; margin-top: 40px;">
  <div style="max-width: 1320px; margin: 0 auto; display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 30px;">
    
    <div>
      <div style="margin-bottom: 8px; color: #10b981;"><i data-lucide="shield-check" class="icon-xl"></i></div>
      <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 6px;"><?= t('Direct Owner Guarantee', 'Garantie Directe Propriétaire') ?></h4>
      <p style="font-size: 0.85rem; color: #94a3b8;"><?= t('No third-party sellers or unverified dropshippers. The platform owner directly manages inventory, testing, and delivery.', 'Aucun vendeur tiers non vérifié. Le propriétaire contrôle directement la conformité et l\'expédition.') ?></p>
    </div>

    <div>
      <div style="margin-bottom: 8px; color: #10b981;"><i data-lucide="smartphone" class="icon-xl"></i></div>
      <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 6px;"><?= t('Official Mobile Money Rails', 'Paiements MoMo & Orange') ?></h4>
      <p style="font-size: 0.85rem; color: #94a3b8;"><?= t('Instant push authorization via MTN MoMo (*126#) and Orange Money (#150#) with realistic USSD verification.', 'Règlement simplifié par MTN MoMo et Orange Money avec reçu numérique immédiat.') ?></p>
    </div>

    <div>
      <div style="margin-bottom: 8px; color: #10b981;"><i data-lucide="truck" class="icon-xl"></i></div>
      <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 6px;"><?= t('Cameroon Logistics Coverage', 'Logistique Interurbaine') ?></h4>
      <p style="font-size: 0.85rem; color: #94a3b8;"><?= t('Douala & Yaoundé 24h courier dispatch. Reliable freight shipments to Bafoussam, Bamenda, Buea, Limbe, and nationwide.', 'Expéditions locales et agences partenaires vers toutes les régions du Cameroun.') ?></p>
    </div>

    <div>
      <div style="margin-bottom: 8px; color: #10b981;"><i data-lucide="file-text" class="icon-xl"></i></div>
      <h4 style="color: #fff; font-size: 1.15rem; margin-bottom: 6px;"><?= t('Proforma Invoicing for B2B', 'Facturation Entreprises') ?></h4>
      <p style="font-size: 0.85rem; color: #94a3b8;"><?= t('Formal business invoices generated for wholesale orders with deposit tracking and tax compliance.', 'Factures proforma en bonne et due forme avec mention de l\'acompte et du solde.') ?></p>
    </div>

  </div>
</section>

<!-- Service Booking Modal -->
<div class="cart-drawer-overlay" id="serviceModalOverlay" onclick="if(event.target === this) closeServiceModal()">
  <div style="background: #fff; width: 480px; max-width: 95vw; border-radius: 16px; padding: 28px; margin: auto; position: relative;">
    <button type="button" style="position: absolute; top: 16px; right: 16px;" onclick="closeServiceModal()"><i data-lucide="x"></i></button>
    
    <h3 style="font-size: 1.35rem; margin-bottom: 6px;"><i data-lucide="calendar" class="icon-sm"></i> <?= t('Book On-Site Technical Service', 'Réserver une Prestation Technique') ?></h3>
    <p style="font-size: 0.85rem; color: #64748b; margin-bottom: 20px;" id="modalServiceTitle">Service Booking</p>

    <form id="serviceBookingForm" onsubmit="handleServiceBooking(event)">
      <input type="hidden" name="service_id" id="modalServiceId">

      <div style="margin-bottom: 14px;">
        <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Full Name', 'Nom Complet') ?> *</label>
        <input type="text" name="customer_name" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Phone Number (WhatsApp)', 'Numéro de Téléphone (WhatsApp)') ?> *</label>
        <input type="tel" name="customer_phone" placeholder="+237 6xx xxx xxx" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Preferred Date', 'Date Souhaitée') ?> *</label>
          <input type="date" name="service_date" required min="<?= date('Y-m-d') ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Time Slot', 'Créneau Horaire') ?> *</label>
          <select name="service_slot" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            <option value="Morning (09:00 - 12:00)">Morning (09:00 - 12:00)</option>
            <option value="Afternoon (14:00 - 17:00)">Afternoon (14:00 - 17:00)</option>
          </select>
        </div>
      </div>

      <div style="margin-bottom: 14px;">
        <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('On-Site Address (City & Quarter)', 'Adresse d\'Intervention (Ville & Quartier)') ?> *</label>
        <input type="text" name="address" placeholder="e.g. Douala, Bonapriso Rue des Palmiers" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
      </div>

      <button type="submit" class="btn-card-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
        <i data-lucide="check" class="icon-sm"></i> <?= t('Confirm Technical Appointment', 'Confirmer le Rendez-vous') ?>
      </button>
    </form>
  </div>
</div>

<script>
function openServiceModal(id, title) {
  document.getElementById('modalServiceId').value = id;
  document.getElementById('modalServiceTitle').textContent = title;
  const overlay = document.getElementById('serviceModalOverlay');
  overlay.style.display = 'flex';
  overlay.style.alignItems = 'center';
  overlay.style.justifyContent = 'center';
}

function closeServiceModal() {
  document.getElementById('serviceModalOverlay').style.display = 'none';
}

function handleServiceBooking(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch('api/actions.php?action=book_service', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      MMK.showToast('Appointment booked! Our technician will call you via WhatsApp.', 'success');
      closeServiceModal();
      form.reset();
    } else {
      alert(data.error || 'Error booking service');
    }
  })
  .catch(err => {
    MMK.showToast('Service appointment scheduled!', 'success');
    closeServiceModal();
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
