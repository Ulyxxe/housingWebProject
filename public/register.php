<?php
// register.php
session_start();
require_once '../config/config.php';  // This file should set up your PDO connection in $pdo

$errors = [];
$success = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize form inputs
    $username         = trim($_POST['username'] ?? '');
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    // Basic validations
    if (empty($username)) {
        $errors[] = "Full Name is required.";
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email is required.";
    }
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }
    
    // Check if email already exists
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("SELECT userID FROM Users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = "Email is already registered.";
            }
        } catch (PDOException $e) {
            $errors[] = "Database error: " . $e->getMessage();
        }
    }
    
    // If no errors, proceed to insert new user
    if (empty($errors)) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO Users (username, email, password) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hashedPassword]);
            $success = "Registration successful! You can now log in.";
        } catch (PDOException $e) {
            $errors[] = "Registration error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - CROUS-X</title>
    <!-- Leaflet CSS (Optional for register, kept for design consistency) -->
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
    <link rel="icon" type="image/png" href="images/icon.png" />
  </head>
  <body>
    <header class="site-header">
      <a href="index.php">
        <div class="logo">CROUS-X</div>
      </a>
      <nav class="main-nav">
        <ul>
          <li><a href="index.php">Search Housing</a></li>
          <!-- Link back to main page -->
          <li><a href="#">Need help ?</a></li>
          <li>
            <button
              id="theme-toggle"
              class="btn btn-dark-mode"
              aria-label="Toggle dark mode"
            >
              <i class="fas fa-moon"></i>
            </button>
          </li>
          <!-- Changed Sign In link to login.php -->
          <li><a href="login.php" class="btn btn-signin">Sign in</a></li>
        </ul>
      </nav>
    </header>

    <!-- Main Content for Registration -->
    <div class="main-content-wrapper register-page-wrapper">
      <div class="register-container">
        <h2>Create Account</h2>

        <!-- Display success or error messages -->
        <?php if (!empty($success)): ?>
          <p style="color: green;"><?php echo htmlspecialchars($success); ?></p>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <ul style="color: red;">
            <?php foreach ($errors as $error): ?>
              <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
          </ul>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="register.php" method="post">
          <!-- The "Full Name" field is mapped to the username -->
          <div class="form-group">
            <label for="fullname">Full Name</label>
            <input
              type="text"
              id="fullname"
              name="username"
              placeholder="Enter your full name"
              required
            />
          </div>
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
              placeholder="Create a password"
              required
            />
          </div>
          <div class="form-group">
            <label for="confirm-password">Confirm Password</label>
            <input
              type="password"
              id="confirm-password"
              name="confirm_password"
              placeholder="Confirm your password"
              required
            />
          </div>
          <div class="form-group terms-group">
            <input type="checkbox" id="terms" name="terms" required />
            <label for="terms"
              >I agree to the <a href="#">Terms of Service</a> &
              <a href="#">Privacy Policy</a></label
            >
          </div>
          <button type="submit" class="btn btn-register btn-register-submit">
            Register
          </button>
        </form>
        <div class="login-links">
          <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
      </div>
    </div>
    <!-- End Main Content -->

    <!-- Leaflet JS (Keep if needed by other scripts or header) -->
    <script
      src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
      integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
      crossorigin=""
    ></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <!-- Your script.js – for dark mode toggle and other interactions -->
    <script src="script.js"></script>
  </body>
</html>
