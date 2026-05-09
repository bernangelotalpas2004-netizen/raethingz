<?php

require_once 'includes/auth.php';
require_once 'includes/helpers.php';

startSession();

$currentPage = 'index';
$csrfToken   = generateCSRF();
$pageTitle   = 'Raethingz Handicrafts — Handmade with Love';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Raethingz Handicrafts — unique handmade keychains, flowers, bouquets, and more from Valenzuela City, Philippines." />
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="assets/css/styles.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
</head>
<body>

  <!-- Skip to main content (accessibility) -->
  <a href="#main-content" class="skip-link">Skip to main content</a>

  <?php require_once 'includes/navbar.php'; ?>

  <main id="main-content">

    <!-- ===== HERO SECTION ===== -->
    <section class="hero" aria-labelledby="hero-heading">
      <div class="container">
        <div class="hero-content">
          <div class="hero-badge">✨ Handmade in the Philippines</div>
          <h1 class="hero-title" id="hero-heading">
            Crafted with <span>Heart</span>,<br/>Made for You
          </h1>
          <p class="hero-tagline">
            Discover unique, handcrafted pieces made with love and local materials.
            From ribbon bouquets to handmade keychains — every item tells a story.
          </p>
          <div class="hero-actions">
            <a href="pages/products.php" class="btn btn-primary">Shop Now →</a>
            <a href="pages/customization.php" class="btn btn-outline">Request Custom Order</a>
          </div>
        </div>
        <div class="hero-image">
          <div class="hero-img-wrapper">
            <img src="assets/images/raethingz_logo.jpg"
                 alt="Raethingz handcrafted products"
                 width="460" height="345"
                 loading="eager" />
          </div>
        </div>
      </div>
    </section>

    <!-- ===== FEATURES STRIP ===== -->
    <div class="features-strip" aria-label="Our commitments">
      <div class="container">
        <div class="feature-item">
          <span class="feature-icon" aria-hidden="true">🚚</span>
          <span>Free Delivery on ₱1,000+</span>
        </div>
        <div class="feature-item">
          <span class="feature-icon" aria-hidden="true">🤝</span>
          <span>100% Handmade</span>
        </div>
        <div class="feature-item">
          <span class="feature-icon" aria-hidden="true">🌿</span>
          <span>Eco-Friendly Materials</span>
        </div>
        <div class="feature-item">
          <span class="feature-icon" aria-hidden="true">⭐</span>
          <span>Customizable Orders</span>
        </div>
      </div>
    </div>

    <!-- ===== FEATURED PRODUCTS ===== -->
    <section class="products-section" aria-labelledby="featured-heading">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title" id="featured-heading">Featured Products</h2>
          <p class="section-subtitle">A glimpse of what we make — each piece uniquely handcrafted.</p>
        </div>

        <!-- Populated dynamically by initHome() in script.js -->
        <div class="products-grid" id="products-grid" role="list" aria-label="Featured products">
          <!-- JS renders product cards here -->
        </div>

        <div style="text-align:center; margin-top:52px;">
          <a href="pages/products.php" class="btn btn-dark">View All Products →</a>
        </div>
      </div>
    </section>

    <!-- ===== WHY RAETHINGZ ===== -->
    <section class="customization-section" style="background:linear-gradient(135deg,#f5f7f3,#fef9f9); padding:72px 0;" aria-labelledby="why-heading">
      <div class="container">
        <div class="section-header">
          <h2 class="section-title" id="why-heading">Why Raethingz?</h2>
          <p class="section-subtitle">Every piece carries the maker's care and intention.</p>
        </div>
        <div class="customization-grid" style="gap:32px;">
          <div class="custom-info">
            <div class="custom-features">
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">🎨</div>
                <div class="custom-feature-text">
                  <h4>Truly Handmade</h4>
                  <p>Every item is crafted by hand — no mass production, no shortcuts.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">🌿</div>
                <div class="custom-feature-text">
                  <h4>Local Materials</h4>
                  <p>We source locally to support Philippine artisans and reduce our footprint.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">💌</div>
                <div class="custom-feature-text">
                  <h4>Made with Love</h4>
                  <p>Each piece is wrapped with care — perfect for gifting or keeping.</p>
                </div>
              </div>
            </div>
          </div>
          <div class="custom-info">
            <div class="custom-features">
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">✏️</div>
                <div class="custom-feature-text">
                  <h4>Fully Customizable</h4>
                  <p>Don't see what you want? We'll make it to your specifications.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">⚡</div>
                <div class="custom-feature-text">
                  <h4>Fast Turnaround</h4>
                  <p>Ready-made items ship within 3–5 days. Custom orders 7–14 days.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">💬</div>
                <div class="custom-feature-text">
                  <h4>Personal Updates</h4>
                  <p>We send progress photos so you know exactly how your order is going.</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

  </main>

  <?php require_once 'includes/footer.php'; ?>

  <script src="assets/js/script.js"></script>
</body>
</html>
