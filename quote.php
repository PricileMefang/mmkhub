<?php
require_once __DIR__ . '/includes/header.php';

$lang = get_lang();
$db = get_db();
$products = $db->query("SELECT id, name_en, name_fr FROM products WHERE active = 1 ORDER BY name_en ASC")->fetchAll();
?>

<div style="max-width: 800px; margin: 40px auto; padding: 0 20px;">
  
  <div style="text-align: center; margin-bottom: 32px;">
    <h1 style="font-size: 2.2rem; margin-bottom: 8px;">📝 <?= t('Request a Custom Bulk Quote', 'Demander un Devis sur Mesure') ?></h1>
    <p style="color: #64748b; max-width: 600px; margin: 0 auto;">
      <?= t(
        'For high-volume orders (>500 units), institutional solar installations, or customized African wax print fabrics. MMK Hub owner will review and respond with tailored pricing.',
        'Pour les volumes supérieurs à 500 unités, projets solaires institutionnels ou commandes textiles spécifiques. Le propriétaire répondra directement avec un tarif personnalisé.'
      ) ?>
    </p>
  </div>

  <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
    
    <form id="quoteRequestForm" onsubmit="handleQuoteSubmit(event)">
      
      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Your Full Name / Contact', 'Nom Complet') ?> *</label>
          <input type="text" name="customer_name" required placeholder="e.g. Emmanuel Fotso" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Phone (MoMo / WhatsApp)', 'Téléphone (WhatsApp)') ?> *</label>
          <input type="tel" name="customer_phone" required placeholder="+237 6xx xxx xxx" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Company / Business Name (Optional)', 'Entreprise') ?></label>
          <input type="text" name="company_name" placeholder="e.g. Fotso Engineering SARL" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Email Address', 'Email') ?></label>
          <input type="email" name="customer_email" placeholder="contact@fotso.cm" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
      </div>

      <div style="margin-bottom: 16px;">
        <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Product / Project Requirements', 'Produit Concerné') ?> *</label>
        <select name="product_name" required style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
          <option value="Commercial Solar Home Lighting & Charging Kit"><?= t('Commercial Solar Home Lighting Kit', 'Kit Solaire Domestique Commercial') ?></option>
          <option value="CIMENCAM / Dangote 42.5R Cement (Pallet Lots)"><?= t('CIMENCAM / Dangote 42.5R Cement (50kg)', 'Ciment CIMENCAM / Dangote (Sacs 50kg)') ?></option>
          <option value="Premium African Wax Print Cotton Fabric"><?= t('Premium African Wax Print Fabric (Hollandais 6 Yards)', 'Pagne Wax Africain Véritable') ?></option>
          <option value="Solar System Turnkey Commercial Installation"><?= t('Solar System Installation Service', 'Installation Solaire Complète') ?></option>
          <option value="Custom Project / Mixed Container"><?= t('Custom Project / Other Supplies', 'Autre Besoin / Projet Spécifique') ?></option>
        </select>
      </div>

      <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Requested Quantity (Units / Cartons / Tons)', 'Quantité Estimée') ?> *</label>
          <input type="number" name="requested_qty" required min="10" value="100" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
        <div>
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Target Unit Budget (FCFA / Unit)', 'Budget Cible (FCFA / Unité)') ?></label>
          <input type="number" name="target_price" placeholder="Optional" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>
      </div>

      <div style="margin-bottom: 24px;">
        <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Specific Technical Specifications or Delivery Destination', 'Précisions & Ville de Livraison') ?></label>
        <textarea name="message" rows="4" placeholder="<?= t('Describe any custom dimensions, color patterns, or preferred delivery dates...', 'Précisez vos contraintes techniques, motifs souhaités, délais...') ?>" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: inherit;"></textarea>
      </div>

      <button type="submit" class="btn-card-primary" style="width: 100%; padding: 14px; font-size: 1rem;">
        📨 <?= t('Send Quote Inquiry to MMK Hub Owner', 'Envoyer la Demande de Devis') ?>
      </button>

    </form>

  </div>
</div>

<script>
function handleQuoteSubmit(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch('api/actions.php?action=request_quote', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('Your quote request ' + data.quote_code + ' was submitted to MMK Hub! We will contact you via WhatsApp / Phone shortly.');
      window.location.href = 'index.php';
    } else {
      alert(data.error || 'Error sending quote');
    }
  })
  .catch(err => {
    alert('Quote inquiry received! Our commercial team will contact you.');
    window.location.href = 'index.php';
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
