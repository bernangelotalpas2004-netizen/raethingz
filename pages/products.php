<?php

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

startSession();

$currentPage = 'products';
$csrfToken   = generateCSRF();
$pageTitle   = 'Products — Raethingz Handicrafts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Browse all handcrafted products from Raethingz — keychains, flowers, bouquets, hairclips, and more." />
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body>

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <?php require_once '../includes/navbar.php'; ?>

  <main id="main-content">

    <!-- ===== PAGE HERO ===== -->
    <div class="page-hero">
      <div class="container">
        <h1 class="section-title">Our Products</h1>
        <p class="section-subtitle">All items are handmade — no two pieces are exactly alike.</p>
      </div>
    </div>

    <!-- ===== PRODUCTS SECTION ===== -->
    <section class="products-section" aria-labelledby="products-heading">
      <div class="container">

        <!-- Search + Filter Controls -->
        <div class="products-controls" role="search">
          <div class="search-bar">
            <span class="search-icon" aria-hidden="true">🔍</span>
            <input
              type="search"
              id="product-search"
              placeholder="Search products..."
              aria-label="Search products"
              autocomplete="off"
            />
          </div>
          <div class="filter-btns" role="group" aria-label="Filter by category">
            <button class="filter-btn active-filter" data-category="all">All</button>
            <button class="filter-btn" data-category="flower">Flowers</button>
            <button class="filter-btn" data-category="bouquet">Bouquet</button>
            <button class="filter-btn" data-category="keychain">Keychain</button>
            <button class="filter-btn" data-category="hairclip">Hairclip</button>
          </div>
        </div>

        <!-- Products Grid — populated by JS / fetch_products.php -->
        <div class="products-grid" id="products-grid" role="list" aria-label="Product listing">
          <!-- JavaScript renders cards here -->
        </div>

      </div>
    </section>

  </main>

  <?php require_once '../includes/footer.php'; ?>

  <script src="../assets/js/script.js"></script>
</body>
</html>
