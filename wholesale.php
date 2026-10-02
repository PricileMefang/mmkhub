<?php
require_once __DIR__ . '/includes/header.php';

$is_ws = is_wholesale();
$user_role = get_user_role();
$lang = get_lang();
?>

<div style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
  
  <!-- Hero Banner -->
  <div style="background: linear-gradient(135deg, #064e3b 0%, #047857 50%, #0f172a 100%); color: #fff; border-radius: 20px; padding: 48px 36px; margin-bottom: 40px; box-shadow: var(--shadow-lg);">
    <div style="max-width: 720px;">
      <span class="hero-badge" style="background: rgba(255,255,255,0.2); border-color: rgba(255,255,255,0.3); color: #fff;">
        🏢 <?= t('B2B Commercial & Wholesale Channel', 'Espace Professionnel & Vente en Gros') ?>
      </span>
      <h1 style="font-size: 2.5rem; color: #fff; margin-bottom: 16px; line-height: 1.2;">
        <?= t('Source Direct from the Owner with Tiered Wholesale Discounts', 'Approvisionnez-vous en Direct avec Tarifs Dégressifs Grossistes') ?>
      </h1>
      <p style="font-size: 1.05rem; color: #d1fae5; line-height: 1.6; margin-bottom: 24px;">
        <?= t(
          'Built specifically for Cameroonian retailers, hardware stores, solar contractors, and fashion boutiques. Access bulk carton prices, 50% Mobile Money deposit terms, and formal OHADA business invoicing.',
          'Conçu spécialement pour les revendeurs, quincailleries, installateurs solaires et boutiques au Cameroun. Accédez aux prix par carton, acomptes 50% MoMo et facturation OHADA.'
        ) ?>
      </p>

      <!-- Sandbox Quick Test Bar -->
      <div style="background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(8px); border: 1px solid rgba(255,255,255,0.2); border-radius: 12px; padding: 16px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div>
          <div style="font-size: 0.8rem; font-weight: 700; color: #f59e0b;">🧪 <?= t('Sandbox Demonstration Role Switcher:', 'Simulateur de Profil Grossiste :') ?></div>
          <div style="font-size: 0.775rem; color: #cbd5e1;">
            <?= t('Test how the storefront behaves under different verification states:', 'Testez l\'affichage selon l\'état de validation du compte :') ?>
          </div>
        </div>

        <div style="display: flex; gap: 8px;">
          <a href="api/actions.php?action=set_demo_role&role=retail" class="cat-pill" style="font-size: 0.75rem; padding: 6px 12px; <?= $user_role === 'retail' ? 'background:#0284c7; color:#fff;' : '' ?>">
            <?= t('Retail Guest', 'Visiteur Détail') ?>
          </a>
          <a href="api/actions.php?action=set_demo_role&role=wholesale_pending" class="cat-pill" style="font-size: 0.75rem; padding: 6px 12px; <?= $user_role === 'wholesale_pending' ? 'background:#d97706; color:#fff;' : '' ?>">
            <?= t('Pending Applicant', 'Candidat en Attente') ?>
          </a>
          <a href="api/actions.php?action=set_demo_role&role=wholesale_approved" class="cat-pill" style="font-size: 0.75rem; padding: 6px 12px; <?= $user_role === 'wholesale_approved' ? 'background:#10b981; color:#fff;' : '' ?>">
            ✨ <?= t('Approved Merchant', 'Grossiste Agréé') ?>
          </a>
        </div>
      </div>
    </div>
  </div>

  <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 40px; align-items: flex-start;">
    
    <!-- Left Column: Registration Form -->
    <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 16px; padding: 32px; box-shadow: var(--shadow-sm);">
      <h3 style="font-size: 1.4rem; margin-bottom: 8px;">📋 <?= t('Register Business Account', 'Demande d\'Agrément Entreprise') ?></h3>
      <p style="font-size: 0.875rem; color: #64748b; margin-bottom: 24px;">
        <?= t('Complete the business profile below. MMK Hub owner reviews each application to unlock wholesale pricing and proforma invoicing.', 'Remplissez le formulaire ci-dessous. Le propriétaire valide chaque demande pour débloquer les tarifs pro.') ?>
      </p>

      <?php if ($user_role === 'wholesale_pending'): ?>
        <div style="background: #fef3c7; border: 1px solid #fde68a; border-radius: 10px; padding: 16px; margin-bottom: 20px; color: #92400e;">
          ⏳ <strong><?= t('Application Under Review:', 'Dossier en Cours d\'Examen :') ?></strong><br>
          <?= t('Your business application is currently pending owner validation. You can log into the Admin portal or use the sandbox switcher above to approve it immediately!', 'Votre demande est en cours de validation par le propriétaire. Vous pouvez l\'approuver via l\'espace Admin ou le sélecteur ci-dessus !') ?>
        </div>
      <?php elseif ($user_role === 'wholesale_approved'): ?>
        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 10px; padding: 16px; margin-bottom: 20px; color: #065f46;">
          ✓ <strong><?= t('Approved B2B Merchant Status Active!', 'Compte Grossiste Agréé Actif !') ?></strong><br>
          <?= t('All volume discounts and 50% deposit terms are currently unlocked for your account in the catalog.', 'Toutes les remises par volume et les facilités d\'acompte 50% sont actives dans votre catalogue.') ?>
        </div>
      <?php endif; ?>

      <form id="wholesaleRegForm" onsubmit="handleWholesaleRegistration(event)">
        <div style="margin-bottom: 16px;">
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Registered Business Name / Entity', 'Raison Sociale / Nom Entreprise') ?> *</label>
          <input type="text" name="company_name" required placeholder="e.g. Kenfack Electronics & Solar SARL" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Contact Person Name', 'Nom du Responsable') ?> *</label>
            <input type="text" name="name" required placeholder="e.g. Kenfack Joseph" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Phone (MoMo / WhatsApp)', 'Téléphone (MoMo / WhatsApp)') ?> *</label>
            <input type="tel" name="phone" required placeholder="+237 6xx xxx xxx" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
          </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px; margin-bottom: 16px;">
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Tax ID / NIU / RC Number', 'Numéro de Registre de Commerce (RC) / NIU') ?> *</label>
            <input type="text" name="tax_id" required placeholder="e.g. M0220149204B" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
          </div>
          <div>
            <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Primary City of Activity', 'Ville Principale') ?> *</label>
            <select name="city" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
              <option value="Douala">Douala</option>
              <option value="Yaoundé">Yaoundé</option>
              <option value="Bafoussam">Bafoussam</option>
              <option value="Bamenda">Bamenda</option>
              <option value="Buea">Buea</option>
              <option value="Limbe">Limbe</option>
              <option value="Garoua">Garoua / Maroua</option>
            </select>
          </div>
        </div>

        <div style="margin-bottom: 16px;">
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Business Address & Shop / Warehouse Location', 'Adresse Boutique / Dépôt') ?> *</label>
          <input type="text" name="address" required placeholder="e.g. Marché Central, Allée 3 Boutique 42" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <div style="margin-bottom: 20px;">
          <label style="display:block; font-size: 0.8rem; font-weight:700; margin-bottom:4px;"><?= t('Official Email (Optional)', 'Email Professionnel') ?></label>
          <input type="email" name="email" placeholder="contact@company.cm" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
        </div>

        <button type="submit" class="btn-card-primary" style="width: 100%; padding: 12px; font-size: 1rem;">
          🚀 <?= t('Submit Wholesale Application', 'Envoyer ma Demande d\'Agrément') ?>
        </button>
      </form>
    </div>

    <!-- Right Column: Wholesale Benefits -->
    <div>
      <h3 style="font-size: 1.4rem; margin-bottom: 20px;"><?= t('Wholesale Merchant Advantages', 'Les Avantages Grossistes') ?></h3>

      <div style="display: flex; flex-direction: column; gap: 20px;">
        
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
          <div style="display: flex; gap: 14px; align-items: flex-start;">
            <div style="background: #ecfdf5; color: #047857; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800;">
              %
            </div>
            <div>
              <h4 style="font-size: 1.05rem; margin-bottom: 4px;"><?= t('Volume Discount Tiers up to 28%', 'Remises Volume jusqu\'à -28%') ?></h4>
              <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                <?= t('Automatic price drops as your order quantity scales. Buy 20, 50, or 100+ units to maximize your commercial resale margins.', 'Baisse automatique des tarifs selon les volumes. Optimisez vos marges de revente au Cameroun.') ?>
              </p>
            </div>
          </div>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
          <div style="display: flex; gap: 14px; align-items: flex-start;">
            <div style="background: #fef3c7; color: #d97706; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800;">
              🤝
            </div>
            <div>
              <h4 style="font-size: 1.05rem; margin-bottom: 4px;"><?= t('50% Deposit + Balance on Delivery', 'Acompte 50% + Solde à la Réception') ?></h4>
              <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                <?= t('Cashflow security for B2B trade. Pay 50% upfront via Mobile Money, and settle the remainder once your cargo is inspected.', 'Sécurisez votre trésorerie. Réglez 50% d\'acompte par MoMo et le solde après vérification.') ?>
              </p>
            </div>
          </div>
        </div>

        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 20px;">
          <div style="display: flex; gap: 14px; align-items: flex-start;">
            <div style="background: #e0f2fe; color: #0284c7; width: 44px; height: 44px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.4rem; font-weight: 800;">
              📑
            </div>
            <div>
              <h4 style="font-size: 1.05rem; margin-bottom: 4px;"><?= t('Compliant OHADA Invoices & Receipts', 'Facturation Conforme OHADA') ?></h4>
              <p style="font-size: 0.85rem; color: #64748b; line-height: 1.5;">
                <?= t('Download stamped and verifiable proforma and commercial receipts for formal bookkeeping and tax accounting.', 'Factures professionnelles certifiées avec mention de la TVA pour votre comptabilité.') ?>
              </p>
            </div>
          </div>
        </div>

      </div>
    </div>

  </div>
</div>

<script>
function handleWholesaleRegistration(e) {
  e.preventDefault();
  const form = e.target;
  const formData = new FormData(form);

  fetch('api/actions.php?action=register_wholesale', {
    method: 'POST',
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert(data.message);
      window.location.reload();
    } else {
      alert(data.error || 'Registration failed');
    }
  })
  .catch(err => {
    alert('Wholesale registration submitted successfully for review!');
    window.location.reload();
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
