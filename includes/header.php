<?php
require_once __DIR__ . '/db.php';
$lang = get_lang();
$is_ws = is_wholesale();
$user_role = get_user_role();
?>
<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= t('MMK Hub - Cameroon Commercial & Wholesale Sourcing', 'MMK Hub - Plateforme Commerciale & Vente en Gros au Cameroun') ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="icon" href="assets/images/solar_lantern.jpg">
</head>
<body data-mode="<?= $is_ws ? 'wholesale' : 'retail' ?>">

  <!-- Top Info Bar -->
  <div class="top-notice-bar">
    <div>
      <span class="notice-pill">🇨🇲 <?= t('CAMEROON EXCLUSIVE', 'EXCLUSIVITÉ CAMEROUN') ?></span>
      <span><?= t('Direct Owner-Operated Wholesale & Retail Hub • Douala & Yaoundé Hubs', 'Vente Directe en Gros & Détail par le Propriétaire • Dépôts Douala & Yaoundé') ?></span>
    </div>
    <div class="notice-links">
      <a href="https://wa.me/237670000000" target="_blank" style="color: #6ee7b7; font-weight:600;">
        💬 WhatsApp: +237 670 000 000
      </a>
      <div style="display: flex; align-items: center; gap: 4px;">
        <span style="font-size:0.75rem; color:#94a3b8;"><?= t('Language:', 'Langue:') ?></span>
        <a href="?lang=en" class="lang-toggle" style="<?= $lang === 'en' ? 'background:#10b981; color:#fff;' : '' ?>">EN</a>
        <a href="?lang=fr" class="lang-toggle" style="<?= $lang === 'fr' ? 'background:#10b981; color:#fff;' : '' ?>">FR</a>
      </div>
    </div>
  </div>

  <!-- Main Navigation Header -->
  <header class="main-header">
    <div class="header-container">
      
      <!-- Brand Logo -->
      <a href="index.php" class="brand-wrapper">
        <div class="brand-logo-badge">M</div>
        <div class="brand-info">
          <h1>MMK HUB <span style="font-size: 0.85rem; color: #10b981;">🇨🇲</span></h1>
          <div class="brand-tagline"><?= t('Direct Commercial & Wholesale Hub', 'Commerce Direct & Vente en Gros') ?></div>
        </div>
      </a>

      <!-- Channel Mode Switcher (Retail vs Wholesale B2B) -->
      <div class="channel-mode-switcher" title="<?= t('Switch between individual retail prices and business bulk wholesale pricing', 'Basculer entre prix de détail individuels et tarifs de gros pour entreprises') ?>">
        <button type="button" class="mode-btn retail <?= !$is_ws ? 'active' : '' ?>" onclick="MMK.setChannelMode('retail')">
          🛒 <?= t('Retail Mode', 'Mode Détail') ?>
        </button>
        <button type="button" class="mode-btn wholesale <?= $is_ws ? 'active' : '' ?>" onclick="MMK.setChannelMode('wholesale')">
          🏢 <?= t('Wholesale B2B', 'Gros B2B') ?>
        </button>
      </div>

      <!-- Navigation Links -->
      <nav class="header-nav">
        <a href="index.php" class="nav-link"><?= t('Catalog', 'Catalogue') ?></a>
        <a href="index.php#services" class="nav-link"><?= t('Services', 'Services') ?></a>
        <a href="wholesale.php" class="nav-link" style="color: #047857; font-weight:700;">
          ✨ <?= t('B2B Wholesale', 'Espace Pro') ?>
        </a>
        <a href="quote.php" class="nav-link"><?= t('Request Quote', 'Devis sur Mesure') ?></a>
        <a href="track.php" class="nav-link"><?= t('Track Order', 'Suivi Colis') ?></a>
      </nav>

      <!-- Actions: User role switcher, Cart, Admin -->
      <div class="header-actions">
        <!-- Interactive Demo Role Selector -->
        <div style="position: relative;">
          <select onchange="window.location.href='api/actions.php?action=set_demo_role&role=' + this.value" 
                  style="padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 0.75rem; font-weight: 600; background: #f8fafc;">
            <option value="retail" <?= $user_role === 'retail' ? 'selected' : '' ?>>👤 <?= t('View: Retail Buyer', 'Vue: Acheteur Détail') ?></option>
            <option value="wholesale_approved" <?= $user_role === 'wholesale_approved' ? 'selected' : '' ?>>🏢 <?= t('View: Approved B2B Merchant', 'Vue: Grossiste Agréé') ?></option>
            <option value="wholesale_pending" <?= $user_role === 'wholesale_pending' ? 'selected' : '' ?>>⏳ <?= t('View: Pending B2B Applicant', 'Vue: Candidat Grossiste') ?></option>
            <option value="admin" <?= $user_role === 'admin' ? 'selected' : '' ?>>👑 <?= t('View: Owner Admin', 'Vue: Propriétaire Admin') ?></option>
          </select>
        </div>

        <!-- Cart Drawer Toggle -->
        <button type="button" class="cart-btn" onclick="MMK.openCartDrawer()">
          <span>🛒</span>
          <span class="cart-badge cart-count-badge">0</span>
        </button>

        <!-- Admin Portal Link -->
        <a href="admin.php" class="btn-admin" title="<?= t('Owner Control Dashboard', 'Panneau d\'Administration Propriétaire') ?>">
          ⚙️ <span>Admin</span>
        </a>
      </div>

    </div>

    <!-- Active Channel Status Banner -->
    <div class="channel-status-bar <?= $is_ws ? '' : 'retail-mode' ?>">
      <div class="mode-indicator">
        <span class="mode-tag <?= $is_ws ? 'wholesale' : 'retail' ?>">
          <?= $is_ws ? t('Wholesale Channel Active', 'Canal Vente en Gros Actif') : t('Retail Mode Active', 'Mode Vente au Détail Actif') ?>
        </span>
        <span style="font-size: 0.85rem; color: #334155;">
          <?= $is_ws 
            ? t('Volume tiered pricing unlocked (up to 25% off) • MOQs enforced • 50% Mobile Money deposit terms', 'Tarifs dégressifs débloqués (jusqu\'à -25%) • Quantités minimales requises • Acompte 50% MoMo') 
            : t('Single unit purchases • Instant Mobile Money / Orange Money / COD • Local delivery dispatch', 'Achats à l\'unité • Paiement Mobile Money / Orange Money / Cash • Livraison locale express') ?>
        </span>
      </div>
      <div>
        <a href="wholesale.php" style="font-size:0.775rem; font-weight:700; color: #047857; text-decoration:underline;">
          <?= t('Learn about Wholesale Terms & Approvals →', 'En savoir plus sur les conditions grossistes →') ?>
        </a>
      </div>
    </div>
  </header>
