<?php


require_once '../includes/auth.php';
require_once '../includes/helpers.php';

startSession();

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit;
}

$currentPage = 'register';
$csrfToken   = generateCSRF();
$pageTitle   = 'Create Account — Raethingz Handicrafts';
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

    <section class="auth-section" aria-labelledby="register-heading">
      <div class="auth-card">

        <!-- Logo -->
        <div class="auth-logo">
          <div class="auth-logo-icon">
            <img src="../assets/images/raethingz_logo.jpg" alt="Raethingz Logo" width="68" height="68" />
          </div>
          <h1 id="register-heading">Create Account</h1>
          <p>Join the Raethingz community</p>
        </div>

        <!-- Alert -->
        <div id="register-alert" class="form-alert" role="alert" aria-live="assertive"></div>

        <!-- Register Form -->
        <form id="register-form" novalidate aria-label="Registration form">

          <div class="form-group">
            <label for="reg-name">Full Name <span aria-hidden="true">*</span></label>
            <input
              type="text"
              id="reg-name"
              name="full_name"
              placeholder="e.g. Maria Santos"
              autocomplete="name"
              required
            />
            <span class="form-error" role="alert">Name must be at least 2 characters.</span>
          </div>

          <div class="form-group">
            <label for="reg-email">Email Address <span aria-hidden="true">*</span></label>
            <input
              type="email"
              id="reg-email"
              name="email"
              placeholder="you@example.com"
              autocomplete="email"
              required
            />
            <span class="form-error" role="alert">Please enter a valid email.</span>
          </div>

          <div class="form-group">
            <label for="reg-password">Password <span aria-hidden="true">*</span></label>
            <div class="password-wrapper">
              <input
                type="password"
                id="reg-password"
                name="password"
                placeholder="Min 8 characters, 1 letter, 1 number"
                autocomplete="new-password"
                required
              />
              <button type="button" class="toggle-password" aria-label="Show password">👁</button>
            </div>
            <span class="form-error" role="alert">At least 8 characters, one letter and one number.</span>
          </div>

          <div class="form-group">
            <label for="reg-confirm">Confirm Password <span aria-hidden="true">*</span></label>
            <div class="password-wrapper">
              <input
                type="password"
                id="reg-confirm"
                name="confirm_password"
                placeholder="Re-enter your password"
                autocomplete="new-password"
                required
              />
              <button type="button" class="toggle-password" aria-label="Show confirm password">👁</button>
            </div>
            <span class="form-error" role="alert">Passwords do not match.</span>
          </div>

          <button type="submit" class="form-submit-btn">Create Account →</button>

        </form>

        <div class="auth-divider">or</div>

        <p class="auth-footer-text">
          Already have an account?
          <a href="login.php">Sign in</a>
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
