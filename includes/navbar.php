<?php

require_once __DIR__ . '/auth.php';
startSession();

$user = currentUser();

// Category dropdown items
$categoryDropdown = [
    ['label' => 'All Products',  'href' => 'pages/products.php'],
    ['label' => 'Flowers',       'href' => 'pages/products.php?category=flower'],
    ['label' => 'Bouquets',      'href' => 'pages/products.php?category=bouquet'],
    ['label' => 'Keychains',     'href' => 'pages/products.php?category=keychain'],
    ['label' => 'Hairclips',     'href' => 'pages/products.php?category=hairclip'],
];

// Build navigation links
$navLinks = [
    'index'         => ['label' => 'Home',          'href' => 'index.php'],
    'products'      => ['label' => 'Products',       'href' => 'pages/products.php', 'dropdown' => $categoryDropdown],
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
        <?php if (!empty($link['dropdown'])): ?>
          <!-- Dropdown item -->
          <li class="nav-dropdown">
            <a href="<?= navHref($link['href'], $currentPage) ?>"
               class="nav-dropdown-toggle <?= $currentPage === $key ? 'active' : '' ?>"
               <?= $currentPage === $key ? 'aria-current="page"' : '' ?>
               aria-haspopup="true"
               aria-expanded="false">
              <?= htmlspecialchars($link['label']) ?>
              <span class="dropdown-arrow" aria-hidden="true">▾</span>
            </a>
            <ul class="nav-dropdown-menu" role="menu" aria-label="<?= htmlspecialchars($link['label']) ?> submenu">
              <?php foreach ($link['dropdown'] as $item): ?>
                <li role="none">
                  <a href="<?= navHref($item['href'], $currentPage) ?>"
                     role="menuitem"
                     tabindex="-1">
                    <?= htmlspecialchars($item['label']) ?>
                  </a>
                </li>
              <?php endforeach; ?>
            </ul>
          </li>
        <?php else: ?>
          <!-- Normal item -->
          <li>
            <a href="<?= navHref($link['href'], $currentPage) ?>"
               class="<?= $currentPage === $key ? 'active' : '' ?>"
               <?= $currentPage === $key ? 'aria-current="page"' : '' ?>>
              <?= htmlspecialchars($link['label']) ?>
            </a>
          </li>
        <?php endif; ?>
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
      <?php if (!empty($link['dropdown'])): ?>
        <!-- Mobile dropdown header (clickable) -->
        <a href="<?= navHref($link['href'], $currentPage) ?>"
           class="<?= $currentPage === $key ? 'active' : '' ?>"
           role="menuitem">
          <?= htmlspecialchars($link['label']) ?>
        </a>
        <!-- Mobile dropdown sub-items -->
        <?php foreach ($link['dropdown'] as $item): ?>
          <a href="<?= navHref($item['href'], $currentPage) ?>"
             class="mobile-dropdown-item"
             role="menuitem">
            <?= htmlspecialchars($item['label']) ?>
          </a>
        <?php endforeach; ?>
      <?php else: ?>
        <a href="<?= navHref($link['href'], $currentPage) ?>"
           class="<?= $currentPage === $key ? 'active' : '' ?>"
           role="menuitem">
          <?= htmlspecialchars($link['label']) ?>
        </a>
      <?php endif; ?>
    <?php endforeach; ?>
    <?php if ($user): ?>
      <a href="<?= navHref('pages/logout.php', $currentPage) ?>" role="menuitem">Logout</a>
    <?php endif; ?>
    <button class="nav-cart-btn" id="open-cart-btn-mobile" aria-label="Open cart">
      🛒 Cart <span class="cart-count" style="display:none;">0</span>
    </button>
  </div>
</nav>