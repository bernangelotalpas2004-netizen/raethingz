<?php
/**
 * login.php — Login Page
 *
 * IMPROVEMENT over original login.html:
 *  - Already-logged-in users are redirected immediately
 *  - CSRF token in meta tag (read by script.js)
 *  - Alert div for JS-driven feedback (replaces inline alert())
 *  - Semantic auth-section / auth-card classes
 *  - Link to register page actually works
 */

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$currentPage = 'login';
$csrfToken   = generateCSRF();
$pageTitle   = 'Login — Raethingz Handicrafts';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="<?= htmlspecialchars($csrfToken) ?>">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="../assets/css/styles.css" />
</head>
<body style="display:flex; flex-direction:column; min-height:100dvh;">

  <a href="#main-content" class="skip-link">Skip to main content</a>

  <?php require_once '../includes/navbar.php'; ?>

  <main id="main-content" style="flex:1; display:flex; flex-direction:column;">

    <section class="auth-section" aria-labelledby="login-heading">
      <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
          <div class="auth-logo-icon">
            <img src="../assets/images/raethingz_logo.jpg" alt="Raethingz Logo" width="68" height="68" />
          </div>
          <h1 id="login-heading">Welcome Back</h1>
          <p>Sign in to your Raethingz account</p>
        </div>

        <!-- Alert (shown by JS) -->
        <div id="login-alert" class="form-alert" role="alert" aria-live="assertive"></div>

        <!-- Login Form -->
        <form id="login-form" novalidate aria-label="Login form">

          <div class="form-group">
            <label for="login-email">Email Address <span aria-hidden="true">*</span></label>
            <input
              type="email"
              id="login-email"
              name="email"
              placeholder="you@example.com"
              autocomplete="email"
              required
              aria-describedby="login-email-error"
            />
            <span id="login-email-error" class="form-error" role="alert">
              Please enter a valid email address.
            </span>
          </div>

          <div class="form-group">
            <label for="login-password">Password <span aria-hidden="true">*</span></label>
            <div class="password-wrapper">
              <input
                type="password"
                id="login-password"
                name="password"
                placeholder="Enter your password"
                autocomplete="current-password"
                required
                aria-describedby="login-pass-error"
              />
              <button type="button" class="toggle-password" id="toggle-password"
                      aria-label="Show password">👁</button>
            </div>
            <span id="login-pass-error" class="form-error" role="alert">
              Password is required.
            </span>
          </div>

          <div style="display:flex; justify-content:flex-end; margin-bottom:20px;">
            <a href="#" style="font-size:0.85rem; color:var(--clr-primary); font-weight:500;">
              Forgot password?
            </a>
          </div>

          <button type="submit" class="form-submit-btn">Login →</button>

        </form>

        <div class="auth-divider">or</div>

        <p class="auth-footer-text">
          Don't have an account?
          <a href="register.php">Create one free</a>
        </p>

        <p class="auth-footer-text" style="margin-top:8px;">
          <a href="../index.php" style="color:var(--clr-muted);">← Back to Home</a>
        </p>

      </div>
    </section>

  </main>

  <script src="../assets/js/script.js"></script>
</body>
</html>
