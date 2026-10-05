<?php
$lang = get_lang();
?>
  <!-- Slide-out Cart Drawer -->
  <div class="cart-drawer-overlay" id="cartDrawerOverlay" onclick="if(event.target === this) MMK.closeCartDrawer()">
    <div class="cart-drawer">
      <div class="drawer-head">
        <h3><i data-lucide="shopping-cart"></i> <?= t('Your Shopping Cart', 'Votre Panier') ?></h3>
        <button type="button" class="btn-close-drawer" onclick="MMK.closeCartDrawer()"><i data-lucide="x"></i></button>
      </div>

      <div class="drawer-items-list" id="drawerCartItems">
        <!-- Rendered dynamically by app.js -->
      </div>

      <div class="drawer-footer">
        <div class="drawer-subtotal-row">
          <span style="color: #64748b;"><?= t('Subtotal:', 'Sous-total :') ?></span>
          <span style="font-weight: 700;" id="drawerSubtotal">0 FCFA</span>
        </div>
        <div class="drawer-total-row">
          <span><?= t('Estimated Total:', 'Total Estimé :') ?></span>
          <span style="color: #047857;" id="drawerTotal">0 FCFA</span>
        </div>

        <div class="deposit-notice-box" id="drawerDepositNotice" style="display: none;">
          <!-- Wholesale 50% Deposit terms shown here -->
        </div>

        <a href="checkout.php" id="drawerCheckoutBtn" class="btn-checkout-cameroon">
          <i data-lucide="lock" class="icon-sm"></i> <?= t('Proceed to Cameroon Checkout', 'Passer à la Caisse (Cameroun)') ?>
        </a>
      </div>
    </div>
  </div>

  <!-- Interactive USSD Mobile Money Simulator Modal -->
  <div class="ussd-modal-overlay" id="ussdSimulatorOverlay">
    <div class="ussd-phone-device">
      <div class="phone-speaker"></div>
      <div class="phone-screen">
        <div class="screen-carrier">
          <span id="ussdCarrierName">MTN Mobile Money CM</span>
          <span>●●● LTE</span>
        </div>

        <div class="ussd-dialog-box">
          <div class="ussd-prompt-text" id="ussdPromptText">
            Transfer of 30,000 FCFA to MMK HUB (671234567). Enter PIN:
          </div>
          <input type="password" maxlength="5" class="ussd-pin-input" id="ussdPinInput" placeholder="••••" autocomplete="off">
          <button type="button" class="btn-card-primary" id="ussdApproveBtn" style="width: 100%;">
            <i data-lucide="check" class="icon-sm"></i> <?= t('Confirm & Authorize', 'Confirmer & Autoriser') ?>
          </button>
        </div>

        <div class="ussd-keypad">
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '1'">1</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '2'">2</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '3'">3</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '4'">4</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '5'">5</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '6'">6</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '7'">7</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '8'">8</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '9'">9</button>
          <button type="button" class="key-btn" style="color: #ef4444;" onclick="MMK.closeUssdSimulator()">✕</button>
          <button type="button" class="key-btn" onclick="document.getElementById('ussdPinInput').value += '0'">0</button>
          <button type="button" class="key-btn" style="color: #64748b;" onclick="let el = document.getElementById('ussdPinInput'); el.value = el.value.slice(0, -1)">⌫</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Floating WhatsApp Quick Support -->
  <a href="https://wa.me/237670000000?text=Hello%20MMK%20Hub,%20I%20have%20an%20inquiry%20regarding%20an%20order" 
     target="_blank" class="floating-whatsapp" title="<?= t('Chat with MMK Hub Owner on WhatsApp', 'Discuter avec le Propriétaire sur WhatsApp') ?>">
    <i data-lucide="message-circle" class="icon-md"></i>
    <span><?= t('Direct WhatsApp Help', 'Assistance WhatsApp Directe') ?></span>
  </a>

  <!-- Site Footer -->
  <footer class="site-footer">
    <div class="footer-container">
      
      <div class="footer-col">
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 14px;">
          <img src="assets/images/logo.png" alt="MMK Hub" style="height: 42px; border-radius: 6px; background: #fff; padding: 2px;">
          <div>
            <div style="color: #fff; font-weight: 800; font-size: 1.2rem; line-height: 1;">MMK HUB</div>
            <div style="font-size: 0.7rem; color: #10b981; font-weight: 700;">CAMEROON <span class="cm-badge">CM</span></div>
          </div>
        </div>
        <p style="font-size: 0.875rem; line-height: 1.6; margin-bottom: 16px;">
          <?= t(
            'Cameroon’s premier direct commercial and wholesale hub. Operating as a single-seller owner platform for guaranteed product quality, zero third-party counterfeit risks, and dedicated customer fulfillment.',
            'Première plateforme commerciale et vente en gros directe au Cameroun. Opérée par un propriétaire unique pour une garantie totale de qualité, zéro risque de contrefaçon et un suivi dédié.'
          ) ?>
        </p>
        <div style="font-size: 0.8rem; color: #10b981; font-weight: 600;">
          <i data-lucide="map-pin" class="icon-xs"></i> <?= t('Central Depot: Akwa, Douala & Mokolo, Yaoundé', 'Dépôt Central : Akwa, Douala & Mokolo, Yaoundé') ?>
        </div>
      </div>

      <div class="footer-col">
        <h4><?= t('Product Sectors', 'Secteurs Produits') ?></h4>
        <ul class="footer-links">
          <li><a href="index.php?cat=Solar+Energy"><?= t('Solar & Energy Systems', 'Systèmes Solaires & Énergie') ?></a></li>
          <li><a href="index.php?cat=Building+Materials"><?= t('Building & Cement Supplies', 'Matériaux & Ciment') ?></a></li>
          <li><a href="index.php?cat=African+Textiles"><?= t('African Wax Fabrics (Hollandais)', 'Tissus Wax Africains') ?></a></li>
          <li><a href="index.php?cat=Services"><?= t('Technical On-Site Installation', 'Installation Technique sur Site') ?></a></li>
          <li><a href="index.php?cat=Bundles"><?= t('Turnkey Commercial Bundles', 'Packs Énergie Clé en Main') ?></a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4><?= t('B2B Wholesale Services', 'Services Grossistes B2B') ?></h4>
        <ul class="footer-links">
          <li><a href="wholesale.php"><?= t('Register Business Account', 'Ouvrir un Compte Entreprise') ?></a></li>
          <li><a href="wholesale.php#pricing"><?= t('Volume Discount Tiers (-25%)', 'Grille Tarifaire Dégressive (-25%)') ?></a></li>
          <li><a href="quote.php"><?= t('Request Custom Bulk Quote', 'Demander un Devis sur Mesure') ?></a></li>
          <li><a href="track.php"><?= t('Download Proforma Invoices', 'Télécharger Facture Proforma') ?></a></li>
          <li><a href="admin.php"><?= t('Owner Control Center', 'Espace Administrateur') ?></a></li>
        </ul>
      </div>

      <div class="footer-col">
        <h4><?= t('Cameroon Payment Rails', 'Moyens de Paiement') ?></h4>
        <p style="font-size: 0.825rem; margin-bottom: 12px;">
          <?= t('Instant local settlement with official Cameroon mobile operators and COD options:', 'Règlement instantané via les opérateurs locaux agréés et paiement à la livraison :') ?>
        </p>
        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.825rem; font-weight: 600;">
          <div style="background: #1e293b; padding: 8px 12px; border-radius: 6px; border: 1px solid #334155; color: #fbbf24;">
            <i data-lucide="smartphone" class="icon-xs"></i> MTN Mobile Money (*126#)
          </div>
          <div style="background: #1e293b; padding: 8px 12px; border-radius: 6px; border: 1px solid #334155; color: #fb923c;">
            <i data-lucide="smartphone" class="icon-xs"></i> Orange Money Cameroun (#150#)
          </div>
          <div style="background: #1e293b; padding: 8px 12px; border-radius: 6px; border: 1px solid #334155; color: #38bdf8;">
            <i data-lucide="banknote" class="icon-xs"></i> <?= t('Cash on Delivery (Retail Orders)', 'Paiement à la Livraison (Détail)') ?>
          </div>
        </div>
      </div>

    </div>

    <div style="max-width: 1320px; margin: 0 auto; padding-top: 24px; border-top: 1px solid #1e293b; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; font-size: 0.8rem;">
      <div>
        © <?= date('Y') ?> MMK Hub SARL. <?= t('All Rights Reserved. Douala, Cameroon.', 'Tous droits réservés. Douala, Cameroun.') ?>
      </div>
      <div>
        <?= t('Single-Seller Model • High Reliability • Certified Quality', 'Modèle Vendeur Unique • Haute Fiabilité • Qualité Certifiée') ?>
      </div>
    </div>
  </footer>

  <script src="js/app.js"></script>
  <script>if(window.lucide) { lucide.createIcons(); }</script>
</body>
</html>
