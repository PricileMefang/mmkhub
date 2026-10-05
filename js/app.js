/**
 * MMK Hub - Cameroon Dual-Channel Commercial & Wholesale Platform
 * Core Application Engine & Interactive Handlers
 */

const MMK = {
  cart: [],
  channelMode: 'retail', // 'retail' or 'wholesale'

  init() {
    this.loadCart();
    this.initChannelMode();
    this.bindEvents();
    this.renderCartUI();
    if (window.lucide) lucide.createIcons();
  },

  // --- Channel Mode (Retail vs Wholesale) ---
  initChannelMode() {
    const saved = localStorage.getItem('mmk_channel_mode');
    if (saved) {
      this.channelMode = saved;
    } else {
      // Check document data attribute
      const bodyMode = document.body.dataset.mode;
      if (bodyMode) this.channelMode = bodyMode;
    }
    this.applyChannelModeUI();
  },

  setChannelMode(mode) {
    this.channelMode = mode;
    localStorage.setItem('mmk_channel_mode', mode);
    
    // Sync to PHP session via AJAX
    fetch('api/actions.php?action=set_channel_mode&mode=' + mode)
      .then(() => {
        window.location.reload();
      })
      .catch(() => {
        window.location.reload();
      });
  },

  applyChannelModeUI() {
    document.querySelectorAll('.mode-btn').forEach(btn => {
      btn.classList.remove('active');
      if (btn.classList.contains(this.channelMode)) {
        btn.classList.add('active');
      }
    });
  },

  // --- Cart Management ---
  loadCart() {
    try {
      const stored = localStorage.getItem('mmk_cart');
      this.cart = stored ? JSON.parse(stored) : [];
    } catch (e) {
      this.cart = [];
    }
  },

  saveCart() {
    localStorage.setItem('mmk_cart', JSON.stringify(this.cart));
    this.renderCartUI();
    if (window.lucide) lucide.createIcons();
  },

  addToCart(product, qty = 1, tier = null) {
    const isWholesale = this.channelMode === 'wholesale';
    const moq = product.moq || 1;

    // Check MOQ constraint in Wholesale Mode
    if (isWholesale && qty < moq) {
      this.showToast(`Wholesale Minimum Order Quantity (MOQ) is ${moq} units for this item!`, 'warning');
      qty = moq;
    }

    const price = isWholesale ? (product.wholesale_price || product.retail_price) : product.retail_price;
    const existingIndex = this.cart.findIndex(item => item.id === product.id && item.tier === tier);

    if (existingIndex > -1) {
      this.cart[existingIndex].qty += qty;
    } else {
      this.cart.push({
        id: product.id,
        name: product.name,
        price: price,
        retail_price: product.retail_price,
        wholesale_price: product.wholesale_price,
        image: product.image,
        is_service: product.is_service || 0,
        is_bundle: product.is_bundle || 0,
        moq: moq,
        qty: qty,
        tier: tier
      });
    }

    this.saveCart();
    this.showToast(`${product.name} added to cart!`, 'success');
    this.openCartDrawer();
  },

  updateQty(index, newQty) {
    if (index >= 0 && index < this.cart.length) {
      const item = this.cart[index];
      const isWholesale = this.channelMode === 'wholesale';

      if (isWholesale && newQty < item.moq) {
        this.showToast(`Wholesale MOQ is ${item.moq} units for this item!`, 'warning');
        return;
      }

      if (newQty <= 0) {
        this.cart.splice(index, 1);
      } else {
        this.cart[index].qty = newQty;
      }
      this.saveCart();
    }
  },

  removeFromCart(index) {
    if (index >= 0 && index < this.cart.length) {
      this.cart.splice(index, 1);
      this.saveCart();
    }
  },

  clearCart() {
    this.cart = [];
    this.saveCart();
  },

  getCartTotals() {
    let subtotal = 0;
    this.cart.forEach(item => {
      // Dynamic tier calculation if applicable
      const unitPrice = this.channelMode === 'wholesale' ? (item.wholesale_price || item.price) : item.retail_price;
      subtotal += unitPrice * item.qty;
    });

    const isWholesale = this.channelMode === 'wholesale';
    const depositRequired = isWholesale ? Math.round(subtotal * 0.5) : subtotal;
    const balanceDue = isWholesale ? (subtotal - depositRequired) : 0;

    return {
      subtotal,
      depositRequired,
      balanceDue,
      itemCount: this.cart.reduce((sum, item) => sum + item.qty, 0)
    };
  },

  renderCartUI() {
    const totals = this.getCartTotals();
    
    // Update badge counters
    document.querySelectorAll('.cart-count-badge').forEach(el => {
      el.textContent = totals.itemCount;
    });

    // Update drawer elements if open
    const drawerList = document.getElementById('drawerCartItems');
    if (!drawerList) return;

    if (this.cart.length === 0) {
      drawerList.innerHTML = `
        <div style="text-align: center; padding: 40px 20px; color: #64748b;">
          <div style="margin-bottom: 12px;"><i data-lucide="shopping-cart" style="width: 48px; height: 48px; color: #94a3b8;"></i></div>
          <p style="font-weight: 600; margin-bottom: 6px;">Your cart is empty</p>
          <p style="font-size: 0.85rem;">Browse products or wholesale lots to add items.</p>
        </div>
      `;
      document.getElementById('drawerSubtotal').textContent = '0 FCFA';
      document.getElementById('drawerTotal').textContent = '0 FCFA';
      const checkoutBtn = document.getElementById('drawerCheckoutBtn');
      if (checkoutBtn) checkoutBtn.style.pointerEvents = 'none';
      return;
    }

    let html = '';
    this.cart.forEach((item, index) => {
      const unitPrice = this.channelMode === 'wholesale' ? (item.wholesale_price || item.price) : item.retail_price;
      const itemSubtotal = unitPrice * item.qty;

      html += `
        <div class="cart-item-row">
          <img src="${item.image || 'assets/images/solar_lantern.jpg'}" alt="${item.name}" class="cart-item-img">
          <div class="cart-item-info">
            <div class="cart-item-title">${item.name}</div>
            <div class="cart-item-price">${this.formatCFA(unitPrice)} <span style="font-size:0.75rem; color:#64748b;">x ${item.qty} = ${this.formatCFA(itemSubtotal)}</span></div>
            ${item.tier ? `<div style="font-size:0.75rem; color:#047857; font-weight:600;">Tier: ${item.tier}</div>` : ''}
            ${this.channelMode === 'wholesale' ? `<div style="font-size:0.72rem; color:#d97706;">MOQ: ${item.moq} units</div>` : ''}
            <div class="cart-item-controls">
              <div class="qty-stepper">
                <button type="button" class="qty-btn" onclick="MMK.updateQty(${index}, ${item.qty - 1})">-</button>
                <span class="qty-val">${item.qty}</span>
                <button type="button" class="qty-btn" onclick="MMK.updateQty(${index}, ${item.qty + 1})">+</button>
              </div>
              <button type="button" style="color:#ef4444; font-size:0.75rem; font-weight:600;" onclick="MMK.removeFromCart(${index})">Remove</button>
            </div>
          </div>
        </div>
      `;
    });

    drawerList.innerHTML = html;
    if (window.lucide) lucide.createIcons();
    document.getElementById('drawerSubtotal').textContent = this.formatCFA(totals.subtotal);
    document.getElementById('drawerTotal').textContent = this.formatCFA(totals.subtotal);

    const depositBox = document.getElementById('drawerDepositNotice');
    if (depositBox) {
      if (this.channelMode === 'wholesale') {
        depositBox.style.display = 'block';
        depositBox.innerHTML = `
          <strong>Wholesale Payment Terms:</strong> 50% Deposit upfront (${this.formatCFA(totals.depositRequired)}) via Mobile Money, balance of ${this.formatCFA(totals.balanceDue)} upon delivery inspection.
        `;
      } else {
        depositBox.style.display = 'none';
      }
    }

    const checkoutBtn = document.getElementById('drawerCheckoutBtn');
    if (checkoutBtn) checkoutBtn.style.pointerEvents = 'auto';
  },

  openCartDrawer() {
    const overlay = document.getElementById('cartDrawerOverlay');
    if (overlay) overlay.classList.add('open');
    this.renderCartUI();
    if (window.lucide) lucide.createIcons();
  },

  closeCartDrawer() {
    const overlay = document.getElementById('cartDrawerOverlay');
    if (overlay) overlay.classList.remove('open');
  },

  // --- Real-Time Tier Pricing Calculator ---
  calculateTierPrice(basePrice, tiersJson, qty) {
    if (!tiersJson) return basePrice;
    let tiers = [];
    try {
      tiers = typeof tiersJson === 'string' ? JSON.parse(tiersJson) : tiersJson;
    } catch (e) {
      return basePrice;
    }

    let applicablePrice = basePrice;
    tiers.forEach(tier => {
      if (tier.min && qty >= tier.min) {
        if (tier.price) applicablePrice = tier.price;
        else if (tier.discount_pct) applicablePrice = Math.round(basePrice * (1 - tier.discount_pct / 100));
      }
    });
    return applicablePrice;
  },

  // --- USSD Mobile Money Simulator Dialog ---
  openUssdSimulator(operator, amount, phone, onConfirm) {
    const overlay = document.getElementById('ussdSimulatorOverlay');
    if (!overlay) return;

    const opName = operator === 'orange' ? 'Orange Money CM' : 'MTN Mobile Money CM';
    const carrierCode = operator === 'orange' ? '#150#' : '*126#';

    document.getElementById('ussdCarrierName').textContent = opName;
    document.getElementById('ussdPromptText').innerHTML = `
      <strong>${opName} Authorization:</strong><br>
      Transfer of <strong>${this.formatCFA(amount)}</strong> to Merchant <strong>MMK HUB (671234567)</strong>.<br>
      Fee: 0 FCFA.<br><br>
      Please enter your 4 or 5-digit PIN to approve:
    `;

    const pinInput = document.getElementById('ussdPinInput');
    pinInput.value = '';
    pinInput.focus();

    overlay.classList.add('open');

    // Bind confirm button
    const approveBtn = document.getElementById('ussdApproveBtn');
    approveBtn.onclick = () => {
      const pin = pinInput.value;
      if (pin.length < 4) {
        alert('Please enter your secret Mobile Money PIN (4 digits)!');
        return;
      }

      document.getElementById('ussdPromptText').innerHTML = `
        <div style="text-align:center; padding: 20px 0;">
          <div style="margin-bottom: 8px;"><i data-lucide="loader-2" class="icon-lg animate-spin"></i></div>
          <strong>Authenticating transaction...</strong><br>
          <span style="font-size:0.75rem; color:#64748b;">Communicating with ${operator.toUpperCase()} gateway...</span>
        </div>
      `;

      setTimeout(() => {
        overlay.classList.remove('open');
        const refCode = (operator === 'orange' ? 'OM-' : 'MOMO-') + Math.floor(100000 + Math.random() * 900000);
        this.showToast(`Payment Approved! Ref: ${refCode}`, 'success');
        if (typeof onConfirm === 'function') {
          onConfirm(refCode);
        }
      }, 1500);
    };
  },

  closeUssdSimulator() {
    const overlay = document.getElementById('ussdSimulatorOverlay');
    if (overlay) overlay.classList.remove('open');
  },

  // --- Utilities ---
  formatCFA(amount) {
    return new Intl.NumberFormat('fr-FR').format(amount) + ' FCFA';
  },

  showToast(message, type = 'info') {
    let container = document.getElementById('toastContainer');
    if (!container) {
      container = document.createElement('div');
      container.id = 'toastContainer';
      container.className = 'toast-container';
      document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.innerHTML = `
      <span><i data-lucide="${type === 'success' ? 'check-circle' : type === 'warning' ? 'alert-triangle' : 'info'}" class="icon-sm"></i></span>
      <span>${message}</span>
    `;
    container.appendChild(toast);
    if (window.lucide) lucide.createIcons();

    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transform = 'translateX(100%)';
      setTimeout(() => toast.remove(), 300);
    }, 3500);
  },

  bindEvents() {
    // Escape key closes modals
    document.addEventListener('keydown', (e) => {
      if (e.key === 'Escape') {
        this.closeCartDrawer();
        this.closeUssdSimulator();
      }
    });
  }
};

document.addEventListener('DOMContentLoaded', () => {
  MMK.init();
});
