<?php
// src/Views/auth/register.php
// Variables: $pageTitle, $errors, $oldInput, $successMessage, $isLoggedIn
?>
<main class="app-container auth-page-wrapper">
  <div class="auth-form-container">
    <h2 class="auth-form-title" data-i18n-key="register_form_title">Create Account</h2>

    <?php if (isset($successMessage) && $successMessage): ?>
      <div class="form-message success-message">
        <i class="fas fa-check-circle"></i>
        <?php echo $successMessage; // HTML is allowed in this message for the login link ?>
      </div>
    <?php endif; ?>

    <?php if (isset($errors) && !empty($errors)): ?>
      <div class="form-message error-message">
        <i class="fas fa-exclamation-triangle"></i>
        <div>
          <strong data-i18n-key="register_error_heading">Please correct the following errors:</strong>
          <ul class="error-list">
            <?php foreach ($errors as $field => $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        </div>
      </div>
    <?php endif; ?>

    <?php if (empty($successMessage)): // Only show form if registration isn't successful ?>
    <!-- IMPORTANT: Form action points to your new route -->
    <form action="/register" method="post" id="registrationForm" class="auth-form">
      <div class="form-row">
        <div class="form-group">
          <label for="first_name" data-i18n-key="register_label_firstname">First Name</label>
          <div class="input-group">
            <span class="input-group-icon"><i class="fas fa-user"></i></span>
            <input
              type="text"
              id="first_name"
              name="first_name"
              placeholder="Enter your first name"
              data-i18n-key-placeholder="register_placeholder_firstname"
              value="<?php echo htmlspecialchars($oldInput['first_name'] ?? ''); ?>"
              required
            />
            <?php if(isset($errors['first_name'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['first_name']); ?></small><?php endif; ?>
          </div>
        </div>
        <div class="form-group">
          <label for="last_name" data-i18n-key="register_label_lastname">Last Name</label>
          <div class="input-group">
            <span class="input-group-icon"><i class="fas fa-user"></i></span>
            <input
              type="text"
              id="last_name"
              name="last_name"
              placeholder="Enter your last name"
              data-i18n-key-placeholder="register_placeholder_lastname"
              value="<?php echo htmlspecialchars($oldInput['last_name'] ?? ''); ?>"
              required
            />
            <?php if(isset($errors['last_name'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['last_name']); ?></small><?php endif; ?>
          </div>
        </div>
      </div>

      <div class="form-group">
        <label for="username" data-i18n-key="register_label_username">Username</label>
         <div class="input-group">
          <span class="input-group-icon"><i class="fas fa-at"></i></span>
          <input
            type="text"
            id="username"
            name="username"
            placeholder="Choose a username"
            data-i18n-key-placeholder="register_placeholder_username"
            value="<?php echo htmlspecialchars($oldInput['username'] ?? ''); ?>"
            pattern="^[a-zA-Z0-9_]+$"
            title="Username can only contain letters, numbers, and underscores."
            required
          />
          <?php if(isset($errors['username'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['username']); ?></small><?php endif; ?>
        </div>
      </div>
      <div class="form-group">
        <label for="email" data-i18n-key="register_label_email">Email Address</label>
        <div class="input-group">
          <span class="input-group-icon"><i class="fas fa-envelope"></i></span>
          <input
            type="email"
            id="email"
            name="email"
            placeholder="your.email@example.com"
            data-i18n-key-placeholder="register_placeholder_email"
            value="<?php echo htmlspecialchars($oldInput['email'] ?? ''); ?>"
            required
          />
          <?php if(isset($errors['email'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['email']); ?></small><?php endif; ?>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="password" data-i18n-key="register_label_password">Password</label>
          <div class="input-group">
            <span class="input-group-icon"><i class="fas fa-lock"></i></span>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Create a password (min. 6)"
              data-i18n-key-placeholder="register_placeholder_password"
              required
            />
            <?php if(isset($errors['password'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['password']); ?></small><?php endif; ?>
          </div>
        </div>
        <div class="form-group">
          <label for="confirm-password" data-i18n-key="register_label_confirmpassword">Confirm Password</label>
          <div class="input-group">
            <span class="input-group-icon"><i class="fas fa-lock"></i></span>
            <input
              type="password"
              id="confirm-password"
              name="confirm_password"
              placeholder="Confirm your password"
              data-i18n-key-placeholder="register_placeholder_confirmpassword"
              required
            />
            <?php if(isset($errors['confirm_password'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['confirm_password']); ?></small><?php endif; ?>
          </div>
        </div>
      </div>
      <div class="form-group terms-agreement-group">
        <label class="checkbox-item-inline">
          <input type="checkbox" id="terms" name="terms" required
                 <?php echo (isset($oldInput['terms'])) ? 'checked' : ''; ?> />
          <span data-i18n-key="register_text_agree_terms_part1">I agree to the</span> <a href="#" data-i18n-key="register_link_terms">Terms of Service</a> & <a href="#" data-i18n-key="register_link_privacy">Privacy Policy</a>
        </label>
        <?php if(isset($errors['terms'])): ?><small class="form-error"><?php echo htmlspecialchars($errors['terms']); ?></small><?php endif; ?>
      </div>
      <button type="submit" class="btn-auth primary-auth-button" data-i18n-key="register_button_register">
        Register
      </button>
    </form>
    <?php endif; // End of if(empty($successMessage)) ?>

    <div class="auth-form-footer-links">
      <p data-i18n-key="register_text_already_account">Already have an account? <a href="/login" data-i18n-key="register_link_signin_here">Sign in here</a></p>
    </div>
  </div>
</main>

<script src="/script.js" defer></script>