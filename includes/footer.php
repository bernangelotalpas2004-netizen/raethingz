<?php

require_once __DIR__ . '/auth.php';
startSession();

// Detect if we're being rendered from inside the /pages/ subdirectory
// so footer links resolve correctly regardless of where footer.php is included from.
$inPagesDir = isset($_SERVER['SCRIPT_NAME']) && strpos($_SERVER['SCRIPT_NAME'], '/pages/') !== false;
$prefix     = $inPagesDir ? '../' : '';

$user = currentUser();
if ($user) {
    $footerAccountLabel = '👤 ' . htmlspecialchars($user['name']);
    $footerAccountHref  = $prefix . 'pages/account.php';
} else {
    $footerAccountLabel = 'Login';
    $footerAccountHref  = $prefix . 'pages/login.php';
}

?>
<!-- ===== CART OVERLAY & SIDEBAR (shared across all pages) ===== -->
<div class="cart-overlay" id="cart-overlay" aria-hidden="true"></div>
<aside class="cart-sidebar" id="cart-sidebar" aria-label="Shopping cart" role="complementary">
  <div class="cart-sidebar-header">
    <h2 class="cart-sidebar-title">🛒 Your Cart</h2>
    <button class="close-cart" id="close-cart" aria-label="Close cart">✕</button>
  </div>
  <div class="cart-items-list" id="cart-items-list" role="list">
    <div class="cart-empty">
      <div class="empty-icon" aria-hidden="true">🧺</div>
      <p>Your cart is empty.</p>
    </div>
  </div>
  <div class="cart-footer">
    <div class="cart-total">
      <span class="cart-total-label">Total</span>
      <span class="cart-total-price" id="cart-total-price" aria-live="polite">₱0</span>
    </div>
    <button class="cart-checkout-btn" id="cart-checkout-btn">Proceed to Checkout</button>
  </div>
</aside>

<!-- ===== CHECKOUT MODAL ===== -->
<div class="modal-overlay" id="checkout-modal" role="dialog" aria-modal="true" aria-labelledby="checkout-modal-title" aria-hidden="true">
  <div class="modal">
    <div class="modal-header">
      <h3 id="checkout-modal-title">📦 Checkout</h3>
      <button class="close-modal" id="close-modal" aria-label="Close checkout">✕</button>
    </div>
    <div class="modal-body">
      <p class="modal-subtitle">Fill in your delivery details to complete your order.</p>
      <form id="checkout-form" novalidate>
        <input type="hidden" name="csrf_token" value=""><!-- Filled by JS from session -->

        <div class="form-group">
          <label for="checkout-name">Full Name <span aria-hidden="true">*</span></label>
          <input type="text" id="checkout-name" name="name" placeholder="e.g. Juan dela Cruz" autocomplete="name" required />
          <span class="form-error" role="alert">This field is required.</span>
        </div>

        <div class="form-group">
          <label for="checkout-address">Delivery Address <span aria-hidden="true">*</span></label>
          <textarea id="checkout-address" name="address" placeholder="House/Unit No., Street, Barangay, City, Province" rows="3" required></textarea>
          <span class="form-error" role="alert">This field is required.</span>
        </div>

        <div class="form-group">
          <label for="checkout-contact">Contact Number <span aria-hidden="true">*</span></label>
          <input type="tel" id="checkout-contact" name="contact" placeholder="e.g. 09XX-XXX-XXXX" autocomplete="tel" required />
          <span class="form-error" role="alert">Please enter a valid PH mobile number.</span>
        </div>

        <!-- Payment Method -->
        <div class="form-group">
          <label>Payment Method <span aria-hidden="true">*</span></label>
          <div class="payment-options">
            <label class="payment-option">
              <input type="radio" name="payment_method" value="cod" checked />
              <span class="payment-option-content">
                <span class="payment-icon" aria-hidden="true">💵</span>
                <span>
                  <strong>Cash on Delivery</strong>
                  <small>Pay when you receive</small>
                </span>
              </span>
            </label>
            <label class="payment-option">
              <input type="radio" name="payment_method" value="gcash" />
              <span class="payment-option-content">
                <span class="payment-icon" aria-hidden="true">📱</span>
                <span>
                  <strong>GCash</strong>
                  <small>Pay via GCash</small>
                </span>
              </span>
            </label>
          </div>
        </div>

        <!-- GCash Details (hidden by default) -->
        <div class="gcash-details" id="gcash-details" style="display:none;">
          <div class="gcash-info">
            <p><strong>GCash Payment Details:</strong></p>
            <p>Send payment to the following GCash account:</p>
            <div class="gcash-account">
              <span class="gcash-label">GCash Number:</span>
              <span class="gcash-value">0975-013-7786</span>
            </div>
            <div class="gcash-account">
              <span class="gcash-label">Account Name:</span>
              <span class="gcash-value">Raethingz Handicrafts</span>
            </div>
            <p class="gcash-note">After sending payment, your order will be processed once confirmed.</p>
          </div>
        </div>

        <button type="submit" class="form-submit-btn" id="checkout-submit-btn">
          <span class="btn-text">Place Order 🎉</span>
          <span class="btn-loading" style="display:none;">Processing...</span>
        </button>
      </form>
    </div>
  </div>
</div>

<!-- ===== FOOTER ===== -->
<footer role="contentinfo">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <div class="footer-brand-name">Raethingz Handicrafts</div>
        <p>Handmade with love and local Filipino materials. Supporting artisans and sustainable crafts since 2023.</p>
      </div>
      <div class="footer-col">
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= $prefix ?>index.php">Home</a></li>
          <li><a href="<?= $prefix ?>pages/products.php">Products</a></li>
          <li><a href="<?= $prefix ?>pages/customization.php">Customization</a></li>
          <li><a href="<?= $footerAccountHref ?>"><?= $footerAccountLabel ?></a></li>
        </ul>
      </div>
      <div class="footer-col">
        <h4>Contact Us</h4>
        <address>
          <div class="footer-contact-item"><span aria-hidden="true">📧</span> raethingz@gmail.com</div>
          <div class="footer-contact-item"><span aria-hidden="true">📱</span> 0975-013-7786</div>
          <div class="footer-contact-item"><span aria-hidden="true">📍</span> Valenzuela City, Philippines</div>
        </address>
      </div>
    </div>
    <div class="footer-bottom">
      <p>© <?= date('Y') ?> Raethingz Handicrafts. All rights reserved.</p>
      <span class="footer-badge">Handmade with ❤️</span>
    </div>
  </div>
</footer>