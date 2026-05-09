<?php
/**
 * customization.php — Custom Order Request Page
 *
 * IMPROVEMENT over original customization.html:
 *  - Form now POSTs to submit_custom.php (saved to DB)
 *  - File upload UI with drag-and-drop preview
 *  - CSRF protection
 *  - Session-aware (pre-fills name if logged in)
 */

require_once '../includes/auth.php';
require_once '../includes/helpers.php';

startSession();

$currentPage = 'customization';
$csrfToken   = generateCSRF();
$pageTitle   = 'Custom Order — Raethingz Handicrafts';
$user        = currentUser();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Request a custom handcrafted item from Raethingz. Tell us your idea and we'll make it for you." />
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
        <h1 class="section-title">Custom Order Request</h1>
        <p class="section-subtitle">Have something specific in mind? We'd love to make it for you.</p>
      </div>
    </div>

    <!-- ===== CUSTOMIZATION SECTION ===== -->
    <section class="customization-section" aria-labelledby="custom-heading">
      <div class="container">
        <div class="customization-grid">

          <!-- ── LEFT: Info ── -->
          <div class="custom-info">
            <h2 id="custom-heading">Tell Us Your Vision</h2>
            <p>
              We love bringing ideas to life. Share your concept, preferred colours, and materials,
              and our artisans will create something uniquely yours.
            </p>

            <div class="custom-features">
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">✏️</div>
                <div class="custom-feature-text">
                  <h4>Describe Your Design</h4>
                  <p>Tell us what you want — shape, size, theme, occasion, or anything specific.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">🎨</div>
                <div class="custom-feature-text">
                  <h4>Choose Your Colours</h4>
                  <p>Pick your palette or describe the mood. We'll match it as closely as possible.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">📸</div>
                <div class="custom-feature-text">
                  <h4>Upload an Inspiration Photo</h4>
                  <p>A reference image helps us understand your vision — optional but very helpful.</p>
                </div>
              </div>
              <div class="custom-feature">
                <div class="custom-feature-icon" aria-hidden="true">💬</div>
                <div class="custom-feature-text">
                  <h4>We'll Reach Out in 24–48 Hours</h4>
                  <p>Our team will review your request and send a quote and timeline via your contact.</p>
                </div>
              </div>
            </div>
          </div>

          <!-- ── RIGHT: Form ── -->
          <div class="custom-form-card" role="region" aria-label="Custom order form">
            <h3>📝 Your Request</h3>

            <!-- Alert (shown by JS) -->
            <div class="form-alert" role="alert" aria-live="assertive"></div>

            <form id="customization-form" enctype="multipart/form-data" novalidate aria-label="Custom order request form">

              <div class="form-group">
                <label for="custom-name">Your Name <span aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="custom-name"
                  name="name"
                  placeholder="e.g. Maria Santos"
                  autocomplete="name"
                  value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                  required
                />
                <span class="form-error" role="alert">Please enter your name.</span>
              </div>

              <div class="form-group">
                <label for="custom-description">Describe Your Design <span aria-hidden="true">*</span></label>
                <textarea
                  id="custom-description"
                  name="description"
                  rows="4"
                  placeholder="e.g. I want a bouquet with sunflowers and pink roses, suitable for a birthday gift..."
                  required
                ></textarea>
                <span class="form-error" role="alert">Please describe your design (min 10 characters).</span>
              </div>

              <div class="form-group">
                <label for="custom-colors">Preferred Colours <span aria-hidden="true">*</span></label>
                <input
                  type="text"
                  id="custom-colors"
                  name="colors"
                  placeholder="e.g. Pastel pink, white, lavender"
                  required
                />
                <span class="form-error" role="alert">Please specify your colour preference.</span>
              </div>

              <div class="form-group">
                <label for="custom-materials">Preferred Materials <span>(optional)</span></label>
                <select id="custom-materials" name="materials">
                  <option value="">— Select a material —</option>
                  <option value="ribbon">Ribbon / Satin</option>
                  <option value="paper">Crepe / Tissue Paper</option>
                  <option value="fabric">Fabric / Felt</option>
                  <option value="clay">Air-Dry Clay</option>
                  <option value="mixed">Mixed / Artisan's Choice</option>
                </select>
              </div>

              <div class="form-group">
                <label for="custom-contact">Contact Number <span>(optional)</span></label>
                <input
                  type="tel"
                  id="custom-contact"
                  name="contact"
                  placeholder="09XX-XXX-XXXX"
                  autocomplete="tel"
                />
              </div>

              <!-- File upload -->
              <div class="form-group">
                <label>Inspiration Photo <span>(optional, max 5 MB)</span></label>
                <div class="file-upload-area" role="button" tabindex="0" aria-label="Upload inspiration photo">
                  <input
                    type="file"
                    id="custom-file"
                    name="image"
                    accept="image/jpeg,image/png,image/webp"
                    aria-label="Upload inspiration photo"
                  />
                  <div class="file-upload-icon" aria-hidden="true">📎</div>
                  <p class="file-upload-text" id="file-label-text">
                    <strong>Click to browse</strong> or drag file here<br/>
                    <small>JPG, PNG, or WEBP up to 5 MB</small>
                  </p>
                </div>
              </div>

              <button type="submit" class="form-submit-btn" id="custom-submit-btn">
                Submit Request ✨
              </button>

            </form>
          </div>

        </div>
      </div>
    </section>

  </main>

  <?php require_once '../includes/footer.php'; ?>

  <script src="../assets/js/script.js"></script>
</body>
</html>
