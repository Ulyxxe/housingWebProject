<?php
// if (session_status() == PHP_SESSION_NONE) {
//     session_start(); // Ensure session is started if not already
// }
?>
<header class="site-header">
  <div class="header-inner">
    <div class="header-group-left">
      <div class="logo">
        <!-- Change href to index.php (landing) or home.php (app main) -->
        <a href="index.php" aria-label="Go to Landing Page">CROUS-X</a>
      </div>
      <nav class="main-nav">
        <ul>
          <!-- Add News stand link -->
          <li><a href="home.php" data-lang-key="nav_newsstand">News stand</a></li>
          <li><a href="help.php" data-lang-key="nav_help">Need help?</a></li>
          <li><a href="faq.php" data-lang-key="nav_faq">FAQ</a></li>
          <li><a href="dashboard.php" data-lang-key="nav_profile">My profile</a></li>
          <?php if (isset($_SESSION['user_is_admin']) && $_SESSION['user_is_admin']): ?>
          <li><a href="admin.php" data-lang-key="nav_admin_app">Admin</a></li>
          <?php endif; ?>
        </ul>
      </nav>
    </div>

    <div class="header-group-right">
      <div class="header-actions">
        <button
          id="language-switcher-toggle"
          class="header-button icon-button"
          aria-label="Choose language"
          aria-haspopup="true"
          aria-expanded="false"
        >
          <i class="fas fa-globe" aria-hidden="true"></i>
        </button>
        <button
          id="theme-toggle"
          class="header-button icon-button theme-toggle-button"
          aria-label="Toggle theme"
        >
          <svg class="sun-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" > <circle cx="12" cy="12" r="5" /> <line x1="12" y1="1" x2="12" y2="3" /> <line x1="12" y1="21" x2="12" y2="23" /> <line x1="4.22" y1="4.22" x2="5.64" y2="5.64" /> <line x1="18.36" y1="18.36" x2="19.78" y2="19.78" /> <line x1="1" y1="12" x2="3" y2="12" /> <line x1="21" y1="12" x2="23" y2="12" /> <line x1="4.22" y1="19.78" x2="5.64" y2="18.36" /> <line x1="18.36" y1="5.64" x2="19.78" y2="4.22" /> </svg>
          <svg class="moon-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" > <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z" /> </svg>
        </button>
        
        <?php if (isset($_SESSION['user_id']) && isset($_SESSION['username'])): ?>
          <!-- <span class="user-greeting" data-lang-key="nav_welcome">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?>!</span> --> <!-- Welcome message can be optional -->
          <a href="logout.php" class="header-button auth-button" data-lang-key="nav_logout">Logout</a>
        <?php else: ?>
          <a href="login.php" class="header-button auth-button" data-lang-key="nav_signin_app">Sign In</a>
          <a href="register.php" class="header-button auth-button primary" data-lang-key="nav_register_app">Register</a>
        <?php endif; ?>
      </div>
    </div>
    <div id="language-switcher-dropdown" class="language-switcher-dropdown" aria-hidden="true">
        <button class="language-choice-button" data-lang="en" aria-label="Switch to English">English</button>
        <button class="language-choice-button" data-lang="fr" aria-label="Switch to French">Français</button>
        <button class="language-choice-button" data-lang="es" aria-label="Switch to Spanish">Español</button>
    </div>
  </div>
</header>