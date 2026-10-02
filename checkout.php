<?php
require_once __DIR__ . '/includes/header.php';
$is_ws = is_wholesale();
$lang = get_lang();
?>

<div style="max-width: 1100px; margin: 40px auto; padding: 0 20px;">
  
  <div style="margin-bottom: 28px;">
    <h1 style="font-size: 2.2rem; margin-bottom: 6px;">🔒 <?= t('Cameroon Secure Checkout', 'Caisse Sécurisée Cameroun') ?></h1>
    <p style="color: #64748b;">
      <?= $is_ws 
        ? t('Wholesale B2B Order: 50% upfront deposit via Mobile Money, balance settled on delivery inspection.', 'Commande Grossiste B2B : Acompte de 50% par Mobile Money, solde à la livraison.') 
        : t('Retail Order: Pay via MTN MoMo (*126#), Orange Money (#150#), or Cash on Delivery.', 'Commande Détail : Règlement par MTN MoMo, Orange Money ou Paiement à la livraison.') ?>
    </p>
  </div>

  <form id="checkoutForm" onsubmit="handleCheckoutSubmit(event)">
    <div style="display: grid; grid-template-columns: 1.2fr 0.8fr; gap: 32px; align-items: flex-start;">
      
      <!-- Left Column: Shipping & Payment Details -->
      <div>
        
        <!-- Contact & Delivery Address -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; margin-bottom: 24px;">
          <h3 style="font-size: 1.2rem; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            <span>📍</span> <span><?= t('1. Delivery Address & Contact', '1. Adresse de Livraison & Contact') ?></span>
          </h3>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
              <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;"><?= t('Customer / Company Name', 'Nom ou Raison Sociale') ?> *</label>
              <input type="text" name="customer_name" required placeholder="e.g. Kenfack Joseph" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
            <div>
              <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;"><?= t('Cameroon Phone (MoMo / WhatsApp)', 'Téléphone (MoMo / WhatsApp)') ?> *</label>
              <input type="tel" name="customer_phone" id="customerPhoneInput" required placeholder="671 23 45 67" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
          </div>

          <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-bottom: 16px;">
            <div>
              <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;"><?= t('Destination City', 'Ville de Destination') ?> *</label>
              <select name="city" id="citySelect" onchange="updateDeliveryFee()" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
                <option value="Douala" data-fee="1500">Douala (Courier Dispatch - 1 500 FCFA)</option>
                <option value="Yaoundé" data-fee="2000">Yaoundé (Express Shuttle - 2 000 FCFA)</option>
                <option value="Bafoussam" data-fee="3500">Bafoussam (Regional Agency - 3 500 FCFA)</option>
                <option value="Bamenda" data-fee="3500">Bamenda (Regional Agency - 3 500 FCFA)</option>
                <option value="Buea" data-fee="3000">Buea / Limbe (Regional Shuttle - 3 000 FCFA)</option>
                <option value="Pickup" data-fee="0">Akwa Douala Depot Pickup (FREE / Gratuit)</option>
              </select>
            </div>
            <div>
              <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;"><?= t('Email Address (Optional)', 'Email (Optionnel)') ?></label>
              <input type="email" name="customer_email" placeholder="name@domain.cm" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
            </div>
          </div>

          <div>
            <label style="display:block; font-size: 0.8rem; font-weight: 700; margin-bottom: 4px;"><?= t('Precise Delivery Street / Quarter / Landmark', 'Quartier / Repère de Livraison') ?> *</label>
            <input type="text" name="delivery_address" required placeholder="e.g. Akwa, Boulevard de la Liberté, face Direction MTN" style="width: 100%; padding: 10px; border: 1px solid #cbd5e1; border-radius: 6px;">
          </div>
        </div>

        <!-- Payment Method Selection -->
        <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px;">
          <h3 style="font-size: 1.2rem; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
            <span>💳</span> <span><?= t('2. Payment Method (Cameroon Rails)', '2. Moyen de Règlement') ?></span>
          </h3>

          <div style="display: flex; flex-direction: column; gap: 12px;">
            
            <!-- MTN MoMo -->
            <label style="display: flex; align-items: center; gap: 14px; padding: 14px; border: 2px solid #cbd5e1; border-radius: 10px; cursor: pointer; transition: 0.2s;" id="labelMomo">
              <input type="radio" name="payment_method" value="momo" checked onchange="handlePaymentChange(this)">
              <div style="background: #fef08a; padding: 6px 10px; border-radius: 6px; font-weight: 800; color: #854d0e;">MTN</div>
              <div style="flex: 1;">
                <div style="font-weight: 700;"><?= t('MTN Mobile Money Cameroon (*126#)', 'MTN Mobile Money Cameroun (*126#)') ?></div>
                <div style="font-size: 0.775rem; color: #64748b;"><?= t('Instant push prompt simulation directly to your MTN number', 'Simulation de validation USSD directe sur votre ligne MTN') ?></div>
              </div>
            </label>

            <!-- Orange Money -->
            <label style="display: flex; align-items: center; gap: 14px; padding: 14px; border: 2px solid #cbd5e1; border-radius: 10px; cursor: pointer; transition: 0.2s;" id="labelOrange">
              <input type="radio" name="payment_method" value="orange" onchange="handlePaymentChange(this)">
              <div style="background: #fed7aa; padding: 6px 10px; border-radius: 6px; font-weight: 800; color: #9a3412;">OM</div>
              <div style="flex: 1;">
                <div style="font-weight: 700;"><?= t('Orange Money Cameroon (#150#)', 'Orange Money Cameroun (#150#)') ?></div>
                <div style="font-size: 0.775rem; color: #64748b;"><?= t('Secure Cameroon payment prompt with official transaction code', 'Code de confirmation sécurisé Orange Money Cameroun') ?></div>
              </div>
            </label>

            <!-- Cash on Delivery -->
            <label style="display: flex; align-items: center; gap: 14px; padding: 14px; border: 2px solid #cbd5e1; border-radius: 10px; cursor: pointer; transition: 0.2s; <?= $is_ws ? 'opacity:0.4; pointer-events:none;' : '' ?>" id="labelCod">
              <input type="radio" name="payment_method" value="cod" <?= $is_ws ? 'disabled' : '' ?> onchange="handlePaymentChange(this)">
              <div style="background: #e0f2fe; padding: 6px 10px; border-radius: 6px; font-weight: 800; color: #0369a1;">COD</div>
              <div style="flex: 1;">
                <div style="font-weight: 700;"><?= t('Cash on Delivery (Retail Only)', 'Paiement à la Livraison (Détail Uniquement)') ?></div>
                <div style="font-size: 0.775rem; color: #64748b;">
                  <?= $is_ws 
                    ? t('Not available for wholesale bulk orders (50% deposit required)', 'Non disponible en gros (acompte obligatoire)') 
                    : t('Pay cash to the dispatched courier upon receiving package in Douala/Yaoundé', 'Réglez en espèces au coursier lors de la livraison') ?>
                </div>
              </div>
            </label>

          </div>
        </div>

      </div>

      <!-- Right Column: Order Summary & Review -->
      <div style="background: #fff; border: 1px solid #e2e8f0; border-radius: 14px; padding: 24px; position: sticky; top: 100px;">
        <h3 style="font-size: 1.2rem; margin-bottom: 16px;">
          <?= t('Order Summary', 'Récapitulatif de Commande') ?>
          <span class="price-mode-label <?= $is_ws ? 'wholesale' : 'retail' ?>" style="font-size: 0.75rem; margin-left: 8px;">
            <?= $is_ws ? 'Wholesale B2B' : 'Retail' ?>
          </span>
        </h3>

        <div id="checkoutItemsList" style="max-height: 260px; overflow-y: auto; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
          <!-- Dynamically populated from cart -->
        </div>

        <div style="display: flex; flex-direction: column; gap: 8px; font-size: 0.9rem; margin-bottom: 16px;">
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;"><?= t('Items Subtotal:', 'Sous-total Articles :') ?></span>
            <span style="font-weight: 700;" id="chkSubtotal">0 FCFA</span>
          </div>
          <div style="display: flex; justify-content: space-between;">
            <span style="color: #64748b;"><?= t('Logistics / Delivery Fee:', 'Frais de Livraison :') ?></span>
            <span style="font-weight: 700;" id="chkDeliveryFee">1 500 FCFA</span>
          </div>
          <div style="display: flex; justify-content: space-between; font-size: 1.2rem; font-weight: 800; border-top: 1px solid #e2e8f0; padding-top: 10px; margin-top: 4px;">
            <span><?= t('Total Order Value:', 'Montant Total :') ?></span>
            <span style="color: #047857;" id="chkGrandTotal">0 FCFA</span>
          </div>
        </div>

        <?php if ($is_ws): ?>
          <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 14px; margin-bottom: 20px;">
            <div style="font-weight: 700; color: #065f46; margin-bottom: 4px; font-size: 0.9rem;">
              🏢 <?= t('Wholesale Payment Breakdown:', 'Détail Règlement Grossiste :') ?>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; margin-bottom: 4px;">
              <span><?= t('50% Upfront Deposit (Now):', 'Acompte 50% (À payer maintenant) :') ?></span>
              <strong style="color: #047857;" id="chkDepositAmount">0 FCFA</strong>
            </div>
            <div style="display: flex; justify-content: space-between; font-size: 0.85rem; color: #64748b;">
              <span><?= t('50% Balance on Delivery:', 'Solde 50% (À la réception) :') ?></span>
              <strong id="chkBalanceAmount">0 FCFA</strong>
            </div>
          </div>
        <?php endif; ?>

        <button type="submit" class="btn-checkout-cameroon" id="btnPlaceOrder">
          🔒 <?= $is_ws ? t('Authorize 50% Deposit & Place Order', 'Autoriser l\'Acompte 50% & Commander') : t('Confirm & Authorize Order', 'Confirmer & Régler la Commande') ?>
        </button>

        <div style="margin-top: 14px; font-size: 0.75rem; color: #64748b; text-align: center;">
          <?= t('Protected by MMK Hub Direct Owner Guarantee. Instant WhatsApp & SMS confirmation.', 'Garantie directe propriétaire MMK Hub. Confirmation immédiate par WhatsApp et SMS.') ?>
        </div>
      </div>

    </div>
  </form>
</div>

<script>
let currentFee = 1500;

document.addEventListener('DOMContentLoaded', () => {
  renderCheckoutSummary();
  updateDeliveryFee();
});

function renderCheckoutSummary() {
  const container = document.getElementById('checkoutItemsList');
  if (MMK.cart.length === 0) {
    container.innerHTML = '<p style="color:#ef4444; font-size:0.9rem;">Your cart is empty. Please add items before checking out.</p>';
    document.getElementById('btnPlaceOrder').disabled = true;
    return;
  }

  let html = '';
  MMK.cart.forEach(item => {
    const unitPrice = <?= $is_ws ? 'true' : 'false' ?> ? (item.wholesale_price || item.price) : item.retail_price;
    html += `
      <div style="display: flex; justify-content: space-between; font-size: 0.825rem; margin-bottom: 8px;">
        <span style="font-weight: 600;">${item.name} x ${item.qty}</span>
        <span style="font-weight: 700;">${MMK.formatCFA(unitPrice * item.qty)}</span>
      </div>
    `;
  });
  container.innerHTML = html;
  updateTotals();
}

function updateDeliveryFee() {
  const select = document.getElementById('citySelect');
  const opt = select.options[select.selectedIndex];
  currentFee = parseInt(opt.getAttribute('data-fee') || '1500');
  
  // Free delivery for orders over 100,000 FCFA
  const totals = MMK.getCartTotals();
  if (totals.subtotal >= 100000 && currentFee > 0) {
    currentFee = 0;
    document.getElementById('chkDeliveryFee').textContent = 'FREE (Promo > 100 000 FCFA)';
  } else {
    document.getElementById('chkDeliveryFee').textContent = MMK.formatCFA(currentFee);
  }

  updateTotals();
}

function updateTotals() {
  const totals = MMK.getCartTotals();
  const grandTotal = totals.subtotal + currentFee;
  
  document.getElementById('chkSubtotal').textContent = MMK.formatCFA(totals.subtotal);
  document.getElementById('chkGrandTotal').textContent = MMK.formatCFA(grandTotal);

  const isWs = <?= $is_ws ? 'true' : 'false' ?>;
  if (isWs) {
    const deposit = Math.round(grandTotal * 0.5);
    const balance = grandTotal - deposit;
    document.getElementById('chkDepositAmount').textContent = MMK.formatCFA(deposit);
    document.getElementById('chkBalanceAmount').textContent = MMK.formatCFA(balance);
  }
}

function handlePaymentChange(radio) {
  document.querySelectorAll('#labelMomo, #labelOrange, #labelCod').forEach(l => l.style.borderColor = '#cbd5e1');
  if (radio.value === 'momo') document.getElementById('labelMomo').style.borderColor = '#047857';
  if (radio.value === 'orange') document.getElementById('labelOrange').style.borderColor = '#047857';
  if (radio.value === 'cod') document.getElementById('labelCod').style.borderColor = '#047857';
}

function handleCheckoutSubmit(e) {
  e.preventDefault();
  if (MMK.cart.length === 0) {
    alert('Please add products to your cart before proceeding!');
    return;
  }

  const form = e.target;
  const formData = new FormData(form);
  const paymentMethod = formData.get('payment_method');
  const isWs = <?= $is_ws ? 'true' : 'false' ?>;
  const totals = MMK.getCartTotals();
  const grandTotal = totals.subtotal + currentFee;
  const deposit = isWs ? Math.round(grandTotal * 0.5) : grandTotal;
  const balance = isWs ? (grandTotal - deposit) : 0;
  const phone = document.getElementById('customerPhoneInput').value;

  // If MoMo or Orange, trigger the USSD interactive prompt simulator
  if (paymentMethod === 'momo' || paymentMethod === 'orange') {
    MMK.openUssdSimulator(paymentMethod, isWs ? deposit : grandTotal, phone, (refCode) => {
      sendOrderToBackend(formData, refCode, grandTotal, deposit, balance);
    });
  } else {
    // COD
    sendOrderToBackend(formData, 'COD-PENDING', grandTotal, deposit, balance);
  }
}

function sendOrderToBackend(formData, refCode, grandTotal, deposit, balance) {
  const isWs = <?= $is_ws ? 'true' : 'false' ?>;
  const totals = MMK.getCartTotals();

  const payload = {
    customer_name: formData.get('customer_name'),
    customer_phone: formData.get('customer_phone'),
    customer_email: formData.get('customer_email'),
    city: formData.get('city'),
    delivery_address: formData.get('delivery_address'),
    delivery_method: formData.get('city') === 'Pickup' ? 'depot_pickup' : 'courier_freight',
    delivery_fee: currentFee,
    order_type: isWs ? 'wholesale' : 'retail',
    subtotal: totals.subtotal,
    total_amount: grandTotal,
    deposit_amount: deposit,
    balance_amount: balance,
    payment_method: isWs ? ('deposit_' + formData.get('payment_method')) : formData.get('payment_method'),
    payment_ref: refCode,
    items: MMK.cart
  };

  fetch('api/actions.php?action=create_order', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(payload)
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      MMK.clearCart();
      window.location.href = 'order_success.php?code=' + data.order_code;
    } else {
      alert('Error creating order: ' + (data.error || 'Server error'));
    }
  })
  .catch(err => {
    alert('Network error connecting to MMK Hub backend');
  });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
