<?php
// login.php
session_start();

// Optionally include configuration or helper files
// require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Login - CROUS-X</title>
    <!-- Leaflet CSS (Optional for login, but keeps consistency if header uses it) -->
    <link
      rel="stylesheet"
      href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
      integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
      crossorigin=""
    />

    <!-- Marker Cluster CSS (Optional for login) -->
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
    <link rel="icon" type="image/png" href="images/icon.png" />
  </head>
  <body>
    <header class="site-header">
      <a href="home.php">
        <div class="logo">CROUS-X</div>
      </a>
      <nav class="main-nav">
        <ul>
          <li><a href="home.php">Search Housing</a></li>
          <!-- Link back to main page -->
          <li><a href="#">Need help ?</a></li>
          <!-- Removed My Profile, Sign in (current page), Register is kept -->
          <li>
            <button
              id="theme-toggle"
              class="btn btn-dark-mode"
              aria-label="Toggle dark mode"
            >
              <i class="fas fa-moon"></i>
              <!-- Icon will be toggled by JS -->
            </button>
          </li>
          <!-- Updated registration link to register.php -->
          <li><a href="register.php" class="btn btn-register">Register</a></li>
        </ul>
      </nav>
    </header>

    <div class="main-content-wrapper login-page-wrapper">
      <!-- Added login-page-wrapper class -->

      <div class="login-container">
        <h2>Sign In</h2>
        <form action="authenticate.php" method="post" id="loginForm">
          <!-- Post to your authentication handler -->
          <div class="form-group">
            <label for="email">Email Address</label>
            <input
              type="email"
              id="email"
              name="email"
              placeholder="Enter your email"
              required
            />
          </div>
          <div class="form-group">
            <label for="password">Password</label>
            <input
              type="password"
              id="password"
              name="password"
              placeholder="Enter your password"
              required
            />
          </div>
          <div class="form-group form-options">
            <a href="#" class="forgot-password">Forgot Password?</a>
          </div>
          <button type="submit" class="btn btn-register btn-login-submit">
            Sign In
          </button>
          <!-- Reusing register style for the button -->
        </form>
        <div class="login-links">
          <p>Don't have an account? <a href="register.php">Register here</a></p>
        </div>
      </div>
    </div>
    <!-- End main-content-wrapper -->

    <!-- Leaflet JS (Keep if needed by other scripts or header) -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>

    <!-- Your script.js - needed for dark mode toggle -->
    <script src="script.js"></script>
  </body>
</html>
