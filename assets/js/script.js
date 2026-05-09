
'use strict';

/* ================================================================
   UTILITY: Escape HTML — prevents XSS when injecting user data
   ================================================================ */
function escapeHTML(str) {
  const div = document.createElement('div');
  div.appendChild(document.createTextNode(String(str)));
  return div.innerHTML;
}

/* ================================================================
   UTILITY: Debounce — limits how often a function fires
   ================================================================ */
function debounce(fn, delay = 300) {
  let timer;
  return (...args) => {
    clearTimeout(timer);
    timer = setTimeout(() => fn(...args), delay);
  };
}

/* ================================================================
   UTILITY: Resolve base path for API calls
   (pages/ subdirectory needs different prefix than root)
   ================================================================ */
function apiPath(endpoint) {
  const isInPages = window.location.pathname.includes('/pages/');
  return isInPages ? endpoint : 'pages/' + endpoint;
}

function imgPath(path) {
  const isInPages = window.location.pathname.includes('/pages/');
  return isInPages ? '../' + path : path;
}

/* ================================================================
   CART MODULE
   Uses localStorage to persist across pages.
   ================================================================ */
const Cart = {
  KEY: 'raethingz_cart',

  get() {
    try {
      return JSON.parse(localStorage.getItem(this.KEY)) || [];
    } catch {
      return [];
    }
  },

  save(data) {
    localStorage.setItem(this.KEY, JSON.stringify(data));
  },

  add(product) {
    const cart = this.get();
    const existing = cart.find(i => i.id === product.id);
    if (existing) {
      existing.qty += 1;
    } else {
      cart.push({ ...product, qty: 1 });
    }
    this.save(cart);
    this.updateBadge();
  },

  remove(id) {
    this.save(this.get().filter(i => i.id !== id));
    this.updateBadge();
  },

  setQty(id, qty) {
    if (qty <= 0) { this.remove(id); return; }
    const cart = this.get();
    const item = cart.find(i => i.id === id);
    if (item) { item.qty = qty; this.save(cart); }
    this.updateBadge();
  },

  clear() {
    this.save([]);
    this.updateBadge();
  },

  totalItems() {
    return this.get().reduce((s, i) => s + i.qty, 0);
  },

  totalPrice() {
    return this.get().reduce((s, i) => s + i.price * i.qty, 0);
  },

  updateBadge() {
    const count = this.totalItems();
    document.querySelectorAll('.cart-count').forEach(el => {
      el.textContent = count;
      el.style.display = count > 0 ? 'inline-flex' : 'none';
    });
  }
};

/* ================================================================
   TOAST MODULE
   Brief non-blocking notifications.
   ================================================================ */
const Toast = {
  _timer: null,

  show(message, type = 'success', duration = 3200) {
    let el = document.getElementById('toast-notification');
    if (!el) {
      el = document.createElement('div');
      el.id = 'toast-notification';
      el.className = 'toast';
      el.setAttribute('role', 'status');
      el.setAttribute('aria-live', 'polite');
      document.body.appendChild(el);
    }

    const icons = { success: '✅', error: '❌', info: 'ℹ️' };
    el.className = `toast ${type}`;
    el.innerHTML = `<span aria-hidden="true">${icons[type] ?? '✅'}</span><span>${escapeHTML(message)}</span>`;

    // Trigger reflow to restart transition
    el.classList.remove('show');
    // eslint-disable-next-line no-unused-expressions
    el.offsetHeight;
    requestAnimationFrame(() => el.classList.add('show'));

    clearTimeout(this._timer);
    this._timer = setTimeout(() => el.classList.remove('show'), duration);
  }
};

/* ================================================================
   CSRF TOKEN
   PHP session token is exposed via a meta tag in each page.
   ================================================================ */
function getCSRFToken() {
  return document.querySelector('meta[name="csrf-token"]')?.content ?? '';
}

/* ================================================================
   NAVBAR MODULE
   Sticky scroll shadow, hamburger toggle, active link.
   ================================================================ */
function initNavbar() {
  const hamburger = document.getElementById('hamburger');
  const mobileNav = document.getElementById('mobile-nav');
  const navbar    = document.getElementById('navbar');

  // Sticky shadow on scroll
  if (navbar) {
    const onScroll = () => navbar.classList.toggle('scrolled', window.scrollY > 20);
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();
  }

  // Hamburger toggle
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => {
      const isOpen = mobileNav.classList.toggle('open');
      hamburger.setAttribute('aria-expanded', isOpen);
    });

    // Close on link click
    mobileNav.querySelectorAll('a').forEach(link =>
      link.addEventListener('click', () => {
        mobileNav.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      })
    );

    // Close on outside click
    document.addEventListener('click', (e) => {
      if (!navbar?.contains(e.target)) {
        mobileNav.classList.remove('open');
        hamburger.setAttribute('aria-expanded', 'false');
      }
    });
  }

  Cart.updateBadge();
}

/* ================================================================
   CART SIDEBAR MODULE
   ================================================================ */
function initCartSidebar() {
  const overlay      = document.getElementById('cart-overlay');
  const sidebar      = document.getElementById('cart-sidebar');
  const closeCartBtn = document.getElementById('close-cart');
  const checkoutModal = document.getElementById('checkout-modal');
  const closeModalBtn = document.getElementById('close-modal');
  const checkoutBtn   = document.getElementById('cart-checkout-btn');
  const checkoutForm  = document.getElementById('checkout-form');

  if (!sidebar) return;

  function openCart() {
    renderCartItems();
    sidebar.classList.add('open');
    overlay?.classList.add('open');
    sidebar.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeCart() {
    sidebar.classList.remove('open');
    overlay?.classList.remove('open');
    sidebar.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  // Open via navbar cart buttons
  document.querySelectorAll('#open-cart-btn, #open-cart-btn-mobile').forEach(btn =>
    btn?.addEventListener('click', (e) => { e.preventDefault(); openCart(); })
  );

  closeCartBtn?.addEventListener('click', closeCart);
  overlay?.addEventListener('click', closeCart);

  // Keyboard: close on Escape
  document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
      if (sidebar.classList.contains('open')) closeCart();
      if (checkoutModal?.classList.contains('open')) closeModal();
    }
  });

  // ── Render cart items ──────────────────────────────────────────
  function renderCartItems() {
    const list    = document.getElementById('cart-items-list');
    const totalEl = document.getElementById('cart-total-price');
    if (!list) return;

    const cart = Cart.get();
    list.innerHTML = '';

    if (cart.length === 0) {
      list.innerHTML = `
        <div class="cart-empty">
          <div class="empty-icon" aria-hidden="true">🧺</div>
          <p>Your cart is empty.</p>
          <p style="font-size:0.83rem; margin-top:4px;">Add some handcrafted items!</p>
        </div>`;
    } else {
      cart.forEach(item => {
        const el = document.createElement('div');
        el.className = 'cart-item';
        el.setAttribute('role', 'listitem');

        const imgContent = item.image
        ? `<img src="${escapeHTML(imgPath(item.image))}" alt="${escapeHTML(item.name)}" loading="lazy">`
          : item.icon ?? '🎁';

        el.innerHTML = `
          <div class="cart-item-image">${imgContent}</div>
          <div class="cart-item-details">
            <div class="cart-item-name">${escapeHTML(item.name)}</div>
            <div class="cart-item-price">₱${(item.price * item.qty).toLocaleString('en-PH')}</div>
            <div class="cart-item-qty">
              <button class="qty-btn" data-action="dec" data-id="${item.id}" aria-label="Decrease quantity">−</button>
              <span class="qty-display" aria-label="Quantity: ${item.qty}">${item.qty}</span>
              <button class="qty-btn" data-action="inc" data-id="${item.id}" aria-label="Increase quantity">+</button>
            </div>
          </div>
          <button class="remove-item" data-id="${item.id}" aria-label="Remove ${escapeHTML(item.name)}">🗑</button>
        `;
        list.appendChild(el);
      });

      list.querySelectorAll('.qty-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const id   = parseInt(btn.dataset.id);
          const item = Cart.get().find(i => i.id === id);
          if (!item) return;
          Cart.setQty(id, item.qty + (btn.dataset.action === 'inc' ? 1 : -1));
          renderCartItems();
        });
      });

      list.querySelectorAll('.remove-item').forEach(btn => {
        btn.addEventListener('click', () => {
          Cart.remove(parseInt(btn.dataset.id));
          renderCartItems();
          Toast.show('Item removed from cart', 'info');
        });
      });
    }

    if (totalEl) totalEl.textContent = `₱${Cart.totalPrice().toLocaleString('en-PH')}`;
  }

  // ── Checkout modal ─────────────────────────────────────────────
  function openModal() {
    if (!checkoutModal) return;
    checkoutModal.classList.add('open');
    checkoutModal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
  }

  function closeModal() {
    if (!checkoutModal) return;
    checkoutModal.classList.remove('open');
    checkoutModal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
  }

  checkoutBtn?.addEventListener('click', () => {
    if (Cart.get().length === 0) {
      Toast.show('Your cart is empty!', 'error');
      return;
    }
    closeCart();
    openModal();
  });

  closeModalBtn?.addEventListener('click', closeModal);
  checkoutModal?.addEventListener('click', e => { if (e.target === checkoutModal) closeModal(); });

  // ── Checkout form submission → place_order.php ─────────────────
  if (checkoutForm) {
    const nameField    = document.getElementById('checkout-name');
    const addressField = document.getElementById('checkout-address');
    const contactField = document.getElementById('checkout-contact');
    const submitBtn    = document.getElementById('checkout-submit-btn');

    checkoutForm.addEventListener('submit', async (e) => {
      e.preventDefault();

      let valid = true;
      const phoneRegex = /^(09|\+639)\d{9}$/;

      // Validate
      [[nameField,    v => v.trim().length >= 2],
       [addressField, v => v.trim().length >= 5],
       [contactField, v => phoneRegex.test(v.replace(/[\s\-]/g, ''))],
      ].forEach(([field, rule]) => {
        if (!field) return;
        const ok = rule(field.value);
        field.closest('.form-group').classList.toggle('error', !ok);
        if (!ok) valid = false;
      });

      if (!valid) return;

      // Show loading state
      if (submitBtn) {
        submitBtn.querySelector('.btn-text').style.display = 'none';
        submitBtn.querySelector('.btn-loading').style.display = 'flex';
        submitBtn.disabled = true;
      }

      try {
        const res = await fetch(apiPath('place_order.php'), {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({
            csrf_token: getCSRFToken(),
            name:    nameField.value.trim(),
            address: addressField.value.trim(),
            contact: contactField.value.trim(),
            items:   Cart.get().map(i => ({ id: i.id, qty: i.qty, price: i.price })),
          }),
        });

        const data = await res.json();

        if (data.success) {
          Cart.clear();
          closeModal();
          checkoutForm.reset();
          Toast.show(data.message, 'success', 5000);
        } else {
          // Show field-level errors if returned
          if (data.data?.errors) {
            Object.entries(data.data.errors).forEach(([key, msg]) => {
              const field = document.getElementById(`checkout-${key}`);
              if (field) {
                const group = field.closest('.form-group');
                group?.classList.add('error');
                const errEl = group?.querySelector('.form-error');
                if (errEl) errEl.textContent = msg;
              }
            });
          }
          Toast.show(data.message || 'Could not place order. Please try again.', 'error');
        }
      } catch (err) {
        console.error('Checkout error:', err);
        Toast.show('Network error. Please check your connection.', 'error');
      } finally {
        if (submitBtn) {
          submitBtn.querySelector('.btn-text').style.display = '';
          submitBtn.querySelector('.btn-loading').style.display = 'none';
          submitBtn.disabled = false;
        }
      }
    });

    // Clear error state on input
    checkoutForm.querySelectorAll('input, textarea').forEach(field => {
      field.addEventListener('input', () => field.closest('.form-group')?.classList.remove('error'));
    });
  }
}

/* ================================================================
   HOME PAGE MODULE
   ================================================================ */
async function initHome() {
  // Load featured products from API
  const grid = document.getElementById('products-grid');
  if (!grid) return;

  grid.innerHTML = `<div class="loading-grid"><div class="spinner"></div> Loading products...</div>`;

  try {
    const res  = await fetch(apiPath('fetch_products.php?featured=1'));
    const data = await res.json();

    if (!data.success || !data.data.length) {
      grid.innerHTML = '<p class="no-results">No featured products found.</p>';
      return;
    }

    renderProductCards(grid, data.data, true);
  } catch (err) {
    console.error('Home products error:', err);
    grid.innerHTML = '<p class="no-results">Could not load products. Please refresh.</p>';
  }
}

/* ================================================================
   PRODUCTS PAGE MODULE
   ================================================================ */
async function initProducts() {
  const grid       = document.getElementById('products-grid');
  const searchInput = document.getElementById('product-search');
  const filterBtns  = document.querySelectorAll('.filter-btn');

  if (!grid) return;

  let allProducts = [];
  let activeCategory = 'all';

  // ── Load products from DB ──────────────────────────────────────
  async function loadProducts(category = 'all', search = '') {
    grid.innerHTML = `<div class="loading-grid"><div class="spinner"></div> Loading products...</div>`;

    try {
      const params = new URLSearchParams();
      if (category !== 'all') params.set('category', category);
      if (search)             params.set('search', search);

      const res  = await fetch(`${apiPath('fetch_products.php')}?${params}`);
      const data = await res.json();

      if (!data.success) throw new Error(data.message);

      allProducts = data.data;
      renderProductCards(grid, allProducts, false);
    } catch (err) {
      console.error('Products load error:', err);
      grid.innerHTML = '<p class="no-results">Could not load products. Please try again.</p>';
    }
  }

  await loadProducts();

  // Search (debounced)
  searchInput?.addEventListener('input', debounce(() => {
    loadProducts(activeCategory, searchInput.value.trim());
  }, 350));

  // Category filter
  filterBtns.forEach(btn => {
    btn.addEventListener('click', () => {
      filterBtns.forEach(b => b.classList.remove('active-filter'));
      btn.classList.add('active-filter');
      activeCategory = btn.dataset.category;
      loadProducts(activeCategory, searchInput?.value.trim() ?? '');
    });
  });
}

/* ================================================================
   SHARED: Render Product Cards
   Used by both home and products pages.
   ================================================================ */
function renderProductCards(grid, products, limit = false) {
  grid.innerHTML = '';

  if (!products.length) {
    grid.innerHTML = '<p class="no-results">No products found. Try a different search.</p>';
    return;
  }

  const items = limit ? products.slice(0, 4) : products;

  items.forEach((product, index) => {
    const card = document.createElement('div');
    card.className = 'product-card';
    card.style.animationDelay = `${index * 60}ms`;

    const badge = product.badge
      ? `<span class="product-badge">${escapeHTML(product.badge)}</span>` : '';

    const stockLabel = product.quantity <= 0
      ? `<p class="product-stock out-of-stock">Out of stock</p>`
      : (product.quantity <= 5
        ? `<p class="product-stock">Only ${product.quantity} left!</p>`
        : '');

    const cartDisabled = product.quantity !== null && product.quantity <= 0;

    card.innerHTML = `
      <div class="product-img-wrap">
        ${badge}
        <img class="product-img" src="${escapeHTML(imgPath(product.image ?? ''))}"
             alt="${escapeHTML(product.name)}" loading="lazy"
             onerror="this.style.display='none'">
      </div>
      <div class="product-info">
        <span class="product-category">${escapeHTML(product.category)}</span>
        <h3 class="product-name">${escapeHTML(product.name)}</h3>
        <p class="product-desc">${escapeHTML(product.description ?? '')}</p>
        ${stockLabel}
        <div class="product-footer">
          <div class="product-price">
            ₱${Number(product.price).toLocaleString('en-PH')}
            <span class="unit">each</span>
          </div>
          <button class="add-to-cart-btn"
                  data-id="${product.id}"
                  ${cartDisabled ? 'disabled aria-disabled="true"' : ''}>
            ${cartDisabled ? 'Out of Stock' : 'Add to Cart'}
          </button>
        </div>
      </div>
    `;

    grid.appendChild(card);
  });

  // Attach add-to-cart listeners
  grid.querySelectorAll('.add-to-cart-btn:not([disabled])').forEach(btn => {
    btn.addEventListener('click', () => {
      const id      = parseInt(btn.dataset.id);
      const product = products.find(p => p.id === id);
      if (!product) return;

      Cart.add({ id: product.id, name: product.name, price: product.price, image: product.image });
      Toast.show(`"${product.name}" added to cart 🛒`, 'success');

      btn.textContent = 'Added! ✓';
      btn.classList.add('added');
      setTimeout(() => {
        btn.textContent = 'Add to Cart';
        btn.classList.remove('added');
      }, 1800);
    });
  });
}

/* ================================================================
   CUSTOMIZATION PAGE MODULE
   ================================================================ */
function initCustomization() {
  const form = document.getElementById('customization-form');
  if (!form) return;

  const alertEl  = form.querySelector('.form-alert');
  const submitBtn = form.querySelector('.form-submit-btn');

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const requiredFields = [
      ['custom-name',        v => v.trim().length >= 2],
      ['custom-description', v => v.trim().length >= 10],
      ['custom-colors',      v => v.trim().length >= 2],
    ];

    let valid = true;
    requiredFields.forEach(([id, rule]) => {
      const field = document.getElementById(id);
      if (!field) return;
      const ok = rule(field.value);
      field.closest('.form-group').classList.toggle('error', !ok);
      if (!ok) valid = false;
    });

    if (!valid) return;

    // Build FormData (supports file upload)
    const fd = new FormData(form);
    fd.append('csrf_token', getCSRFToken());

    if (submitBtn) {
      submitBtn.disabled = true;
      submitBtn.innerHTML = '<div class="spinner"></div> Submitting...';
    }
    alertEl?.classList.remove('show', 'success', 'error');

    try {
      const res  = await fetch(apiPath('submit_custom.php'), {
        method: 'POST',
        body: fd,
      });
      const data = await res.json();

      if (data.success) {
        alertEl?.classList.add('show', 'success');
        if (alertEl) alertEl.textContent = '✅ ' + data.message;
        form.reset();
        document.getElementById('file-label-text').innerHTML =
          '<strong>Click to browse</strong> or drag file here';
      } else {
        if (data.data?.errors) {
          Object.entries(data.data.errors).forEach(([key, msg]) => {
            const field = document.getElementById('custom-' + key) || document.getElementById(key);
            if (field) {
              const group = field.closest('.form-group');
              group?.classList.add('error');
              const errEl = group?.querySelector('.form-error');
              if (errEl) errEl.textContent = msg;
            }
          });
        }
        alertEl?.classList.add('show', 'error');
        if (alertEl) alertEl.textContent = '❌ ' + (data.message || 'Please fix the errors above.');
      }
    } catch {
      alertEl?.classList.add('show', 'error');
      if (alertEl) alertEl.textContent = '❌ Network error. Please try again.';
    } finally {
      if (submitBtn) {
        submitBtn.disabled = false;
        submitBtn.textContent = 'Submit Request ✨';
      }
    }
  });

  // Clear errors on input
  form.querySelectorAll('input, textarea, select').forEach(field => {
    field.addEventListener('input', () => field.closest('.form-group')?.classList.remove('error'));
  });

  // File upload label
  const fileInput = document.getElementById('custom-file');
  const fileLabel = document.getElementById('file-label-text');
  fileInput?.addEventListener('change', () => {
    const file = fileInput.files[0];
    if (fileLabel) {
      fileLabel.innerHTML = file
        ? `📎 ${escapeHTML(file.name)}`
        : '<strong>Click to browse</strong> or drag file here';
    }
  });
}

/* ================================================================
   LOGIN PAGE MODULE
   Submits to login_handler.php via fetch (JSON)
   ================================================================ */
function initLogin() {
  const form        = document.getElementById('login-form');
  const alertEl     = document.getElementById('login-alert');
  const togglePwBtn = document.getElementById('toggle-password');
  const passwordFld = document.getElementById('login-password');
  const submitBtn   = form?.querySelector('.form-submit-btn');

  if (!form) return;

  // Toggle password visibility
  togglePwBtn?.addEventListener('click', () => {
    const show = passwordFld.type === 'password';
    passwordFld.type = show ? 'text' : 'password';
    togglePwBtn.textContent = show ? '🙈' : '👁';
    togglePwBtn.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const emailField = document.getElementById('login-email');
    const emailGroup = emailField?.closest('.form-group');
    const passGroup  = passwordFld?.closest('.form-group');

    let valid = true;
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

    if (!emailField?.value.trim() || !emailRegex.test(emailField.value.trim())) {
      emailGroup?.classList.add('error');
      valid = false;
    } else {
      emailGroup?.classList.remove('error');
    }

    if (!passwordFld?.value) {
      passGroup?.classList.add('error');
      valid = false;
    } else {
      passGroup?.classList.remove('error');
    }

    if (!valid) return;

    // Loading state
    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Logging in...'; }
    alertEl?.classList.remove('show', 'success', 'error');

    try {
      const res  = await fetch(apiPath('login_handler.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token: getCSRFToken(),
          email:    emailField.value.trim(),
          password: passwordFld.value,
        }),
      });

      const data = await res.json();

      if (data.success) {
        alertEl?.classList.add('show', 'success');
        if (alertEl) alertEl.textContent = '✅ ' + data.message;
        setTimeout(() => {
          window.location.href = data.data?.redirect ?? '../index.php';
        }, 1200);
      } else {
        if (data.data?.errors?.email) {
          emailGroup?.classList.add('error');
          const errEl = emailGroup?.querySelector('.form-error');
          if (errEl) errEl.textContent = data.data.errors.email;
        }
        alertEl?.classList.add('show', 'error');
        if (alertEl) alertEl.textContent = '❌ ' + (data.message || 'Login failed. Please try again.');
      }
    } catch {
      if (alertEl) { alertEl.classList.add('show', 'error'); alertEl.textContent = '❌ Network error. Please try again.'; }
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Login →'; }
    }
  });

  form.querySelectorAll('input').forEach(field => {
    field.addEventListener('input', () => field.closest('.form-group')?.classList.remove('error'));
  });
}

/* ================================================================
   REGISTER PAGE MODULE
   Submits to register_handler.php via fetch (JSON)
   ================================================================ */
function initRegister() {
  const form    = document.getElementById('register-form');
  const alertEl = document.getElementById('register-alert');
  const submitBtn = form?.querySelector('.form-submit-btn');

  if (!form) return;

  // Toggle password visibility
  form.querySelectorAll('.toggle-password').forEach(btn => {
    btn.addEventListener('click', () => {
      const wrapper = btn.closest('.password-wrapper');
      const input   = wrapper?.querySelector('input');
      if (!input) return;
      const show = input.type === 'password';
      input.type = show ? 'text' : 'password';
      btn.textContent = show ? '🙈' : '👁';
    });
  });

  form.addEventListener('submit', async (e) => {
    e.preventDefault();

    const nameField    = document.getElementById('reg-name');
    const emailField   = document.getElementById('reg-email');
    const passField    = document.getElementById('reg-password');
    const confirmField = document.getElementById('reg-confirm');

    const rules = [
      [nameField,    v => v.trim().length >= 2,            'Name must be at least 2 characters.'],
      [emailField,   v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()), 'Please enter a valid email.'],
      [passField,    v => v.length >= 8 && /[A-Za-z]/.test(v) && /\d/.test(v), 'At least 8 characters, one letter and one number.'],
      [confirmField, v => v === passField?.value,          'Passwords do not match.'],
    ];

    let valid = true;
    rules.forEach(([field, rule, msg]) => {
      if (!field) return;
      const group = field.closest('.form-group');
      const ok    = rule(field.value);
      group?.classList.toggle('error', !ok);
      if (!ok) {
        const errEl = group?.querySelector('.form-error');
        if (errEl && msg) errEl.textContent = msg;
        valid = false;
      }
    });

    if (!valid) return;

    if (submitBtn) { submitBtn.disabled = true; submitBtn.textContent = 'Creating account...'; }
    alertEl?.classList.remove('show', 'success', 'error');

    try {
      const res  = await fetch(apiPath('register_handler.php'), {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          csrf_token:       getCSRFToken(),
          full_name:        nameField.value.trim(),
          email:            emailField.value.trim(),
          password:         passField.value,
          confirm_password: confirmField.value,
        }),
      });

      const data = await res.json();

      if (data.success) {
        alertEl?.classList.add('show', 'success');
        if (alertEl) alertEl.textContent = '✅ ' + data.message;
        setTimeout(() => {
          window.location.href = data.data?.redirect ?? '../index.php';
        }, 1400);
      } else {
        if (data.data?.errors) {
          Object.entries(data.data.errors).forEach(([key, msg]) => {
            const fieldMap = { full_name: 'reg-name', email: 'reg-email', password: 'reg-password', confirm_password: 'reg-confirm' };
            const el = document.getElementById(fieldMap[key]);
            if (el) {
              const group = el.closest('.form-group');
              group?.classList.add('error');
              const errEl = group?.querySelector('.form-error');
              if (errEl) errEl.textContent = msg;
            }
          });
        }
        alertEl?.classList.add('show', 'error');
        if (alertEl) alertEl.textContent = '❌ ' + (data.message || 'Registration failed.');
      }
    } catch {
      if (alertEl) { alertEl.classList.add('show', 'error'); alertEl.textContent = '❌ Network error. Please try again.'; }
    } finally {
      if (submitBtn) { submitBtn.disabled = false; submitBtn.textContent = 'Create Account →'; }
    }
  });

  form.querySelectorAll('input').forEach(field => {
    field.addEventListener('input', () => field.closest('.form-group')?.classList.remove('error'));
  });
}

/* ================================================================
   INIT — detect page and run the right module
   ================================================================ */
document.addEventListener('DOMContentLoaded', () => {
  initNavbar();
  initCartSidebar();

  const page = window.location.pathname.split('/').pop().replace('.php', '') || 'index';

  switch (page) {
    case 'index':
    case '':
      initHome();
      break;
    case 'products':
      initProducts();
      break;
    case 'customization':
      initCustomization();
      break;
    case 'login':
      initLogin();
      break;
    case 'register':
      initRegister();
      break;
  }
});
