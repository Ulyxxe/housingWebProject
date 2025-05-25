<?php
// src/Views/auth/login.php
// Variables like $pageTitle, $loginError, $isLoggedIn are passed from AuthController::showLoginForm()
?>
<main class="app-container auth-page-wrapper">
  <div class="auth-form-container">
    <h2 class="auth-form-title" data-i18n-key="login_form_title">Sign In</h2>

    <?php if (isset($loginError) && $loginError): ?>
        <div class="form-message error-message">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($loginError); ?>
        </div>
    <?php endif; ?>

    <!-- IMPORTANT: Form action points to your new route -->
    <form action="/login" method="post" id="loginForm" class="auth-form">
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
        <label class="checkbox-item-inline">
          <input type="checkbox" name="remember_me">
          <span data-i18n-key="login_label_remember_me">Remember me</span>
        </label>
        <a href="#" class="forgot-password-link" data-i18n-key="login_link_forgot_password">Forgot Password?</a>
      </div>
      <button type="submit" class="btn-auth primary-auth-button" data-i18n-key="login_button_signin">
        Sign In
      </button>
    </form>
    <div class="auth-form-footer-links">
      <p data-i18n-key="login_text_no_account">Don't have an account? <a href="/register" data-i18n-key="login_link_register_here">Register here</a></p>
    </div>
  </div>
</main>

<!-- Note: No Leaflet JS needed here usually, it was from your old login.php shell.
     Script.js might still be needed for global things like theme/language.
     Chatbot also usually not on login page. -->
<script src="/script.js" defer></script>