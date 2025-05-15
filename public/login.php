<?php
// login.php
session_start();

// Optionally include configuration or helper files
// require_once __DIR__ . '/../config/config.php';

// Determine if user is logged in (for header logic)
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title data-i18n-key="login_page_title_document">Login - CROUS-X</title>
    
    <!-- Leaflet CSS (Optional, keep if header depends on it or for consistency) -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />
    <!-- Marker Cluster CSS (Optional) -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css"
    />
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css"
    />

    <!-- Custom CSS -->
    <link rel="stylesheet" href="style.css" />
    <!-- Font Awesome for Icons -->
    <link
      rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
      integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
      crossorigin="anonymous"
      referrerpolicy="no-referrer"
    />
    <link rel="icon" type="image/png" href="assets/images/icon.png" /> <!-- Corrected path -->
  </head>
  <body>
    <?php require 'header.php'; ?>

    <main class="app-container auth-page-wrapper"> <!-- Consistent main wrapper -->
      <div class="auth-form-container"> <!-- Specific container for the form box -->
        <h2 class="auth-form-title" data-i18n-key="login_form_title">Sign In</h2>
        
        <!-- Placeholder for login error messages -->
        <?php if (isset($_SESSION['login_error'])): ?>
            <div class="form-message error-message">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo htmlspecialchars($_SESSION['login_error']); unset($_SESSION['login_error']); ?>
            </div>
        <?php endif; ?>

        <form action="authenticate.php" method="post" id="loginForm" class="auth-form">
          <div class="form-group">
            <label for="email" data-i18n-key="login_label_email">Email Address</label>
            <div class="input-group">
              <span class="input-group-icon"><i class="fas fa-envelope"></i></span>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="your.email@example.com"
                data-i18n-key-placeholder="login_placeholder_email"
                required
              />
            </div>
          </div>
          <div class="form-group">
            <label for="password" data-i18n-key="login_label_password">Password</label>
            <div class="input-group">
              <span class="input-group-icon"><i class="fas fa-lock"></i></span>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                data-i18n-key-placeholder="login_placeholder_password"
                required
              />
            </div>
          </div>
          <div class="form-group form-options">
            {/* Optional: Remember me checkbox
            <label class="checkbox-item-inline">
              <input type="checkbox" name="remember_me">
              <span data-i18n-key="login_label_remember_me">Remember me</span>
            </label>
            */}
            <a href="#" class="forgot-password-link" data-i18n-key="login_link_forgot_password">Forgot Password?</a>
          </div>
          <button type="submit" class="btn-auth primary-auth-button" data-i18n-key="login_button_signin">
            Sign In
          </button>
        </form>
        <div class="auth-form-footer-links">
          <p data-i18n-key="login_text_no_account">Don't have an account? <a href="register.php" data-i18n-key="login_link_register_here">Register here</a></p>
        </div>
      </div>
    </main>

    <!-- Leaflet JS (Keep if needed by other scripts or header) -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <script src="script.js" defer></script>
    <!-- No need for chatbot.js unless you specifically want the chatbot on the login page -->
    <!-- <script src="chatbot.js" defer></script> -->
  </body>
</html>