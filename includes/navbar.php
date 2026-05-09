<?php
/**
 * navbar.php — shared navigation component
 *
 * HOW IT REMOVES DUPLICATION:
 *  Previously each HTML file had its own copy of the nav, meaning a small
 *  change (e.g. adding a new link) required editing 5 files.
 *  Now you change this one file and every page updates automatically.
 *
 * Usage:  <?php require_once 'includes/navbar.php'; ?>
 *
 * Expects:  $currentPage variable set by the including file, e.g.
 *           $currentPage = 'products';
 */

require_once __DIR__ . '/auth.php';
startSession();

$user = currentUser();

// Build navigation links
$navLinks = [
    'index'         => ['label' => 'Home',          'href' => 'index.php'],
    'products'      => ['label' => 'Products',       'href' => 'pages/products.php'],
    'customization' => ['label' => 'Customization',  'href' => 'pages/customization.php'],
];

if ($user) {
    $navLinks['account'] = ['label' => '👤 ' . htmlspecialchars($user['name']), 'href' => 'pages/account.php'];
} else {
    $navLinks['login'] = ['label' => 'Login', 'href' => 'pages/login.php'];
}

$currentPage = $currentPage ?? 'index';

// Helper: resolve href relative to current file depth
function navHref(string $href, string $currentPage): string {
    // pages/* need to go up one level; index is at root
    $pagesContext = in_array($currentPage, ['products','customization','login','register','account']);
    return $pagesContext ? '../' . $href : $href;
}
?>
<nav class="navbar" id="navbar" role="navigation" aria-label="Main navigation">
  <div class="container">
    <!-- Brand -->
    <a href="<?= navHref('index.php', $currentPage) ?>" class="navbar-brand" aria-label="Raethingz Handicrafts — Home">
      <div class="navbar-logo">
        <img src="<?= navHref('assets/images/raethingz_logo.jpg', $currentPage) ?>" alt="Raethingz Logo" width="44" height="44">
      </div>
      <div>
        <div class="navbar-brand-name">Raethingz</div>
        <div class="navbar-brand-tagline">Handicrafts</div>
      </div>
    </a>

    <!-- Desktop Links -->
    <ul class="nav-links" role="list">
      <?php foreach ($navLinks as $key => $link): ?>
        <li>
          <a href="<?= navHref($link['href'], $currentPage) ?>"
             class="<?= $currentPage === $key ? 'active' : '' ?>"
             <?= $currentPage === $key ? 'aria-current="page"' : '' ?>>
            <?= htmlspecialchars($link['label']) ?>
          </a>
        </li>
      <?php endforeach; ?>
      <?php if ($user): ?>
        <li>
          <a href="<?= navHref('pages/logout.php', $currentPage) ?>" class="nav-link-logout">Logout</a>
        </li>
      <?php endif; ?>
      <li>
        <button class="nav-cart-btn btn" id="open-cart-btn" aria-label="Open cart" aria-haspopup="true">
          <span aria-hidden="true">🛒</span> Cart
          <span class="cart-count" style="display:none;" aria-live="polite">0</span>
        </button>
      </li>
    </ul>

    <!-- Hamburger (mobile) -->
    <button class="hamburger" id="hamburger" aria-label="Toggle navigation menu" aria-expanded="false" aria-controls="mobile-nav">
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
      <span aria-hidden="true"></span>
    </button>
  </div>

  <!-- Mobile Menu -->
  <div class="mobile-nav" id="mobile-nav" role="menu" aria-label="Mobile navigation">
    <?php foreach ($navLinks as $key => $link): ?>
      <a href="<?= navHref($link['href'], $currentPage) ?>"
         class="<?= $currentPage === $key ? 'active' : '' ?>"
         role="menuitem">
        <?= htmlspecialchars($link['label']) ?>
      </a>
    <?php endforeach; ?>
    <?php if ($user): ?>
      <a href="<?= navHref('pages/logout.php', $currentPage) ?>" role="menuitem">Logout</a>
    <?php endif; ?>
    <button class="nav-cart-btn" id="open-cart-btn-mobile" aria-label="Open cart">
      🛒 Cart <span class="cart-count" style="display:none;">0</span>
    </button>
  </div>
</nav>
