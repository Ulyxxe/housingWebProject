<?php
session_start(); // Must be at the very top
require_once '../config/config.php';  // Corrected path to PDO connection ($pdo)

$errors = [];
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and trim form inputs
    $first_name       = trim($_POST['first_name'] ?? '');
    $last_name        = trim($_POST['last_name'] ?? '');
    $username         = trim($_POST['username'] ?? ''); // Dedicated username field
    $email            = trim($_POST['email'] ?? '');
    $password         = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $terms_agreed     = isset($_POST['terms']);

    // --- Validations ---
    if (empty($first_name)) {
        $errors[] = "First Name is required.";
    } elseif (strlen($first_name) > 50) {
        $errors[] = "First Name cannot exceed 50 characters.";
    }

    if (empty($last_name)) {
        $errors[] = "Last Name is required.";
    } elseif (strlen($last_name) > 50) {
        $errors[] = "Last Name cannot exceed 50 characters.";
    }

    if (empty($username)) {
        $errors[] = "Username is required.";
    } elseif (strlen($username) > 50) {
        $errors[] = "Username cannot exceed 50 characters.";
    } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) { // Allow alphanumeric and underscores
        $errors[] = "Username can only contain letters, numbers, and underscores.";
    }


    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "A valid email address is required.";
    } elseif (strlen($email) > 100) {
        $errors[] = "Email cannot exceed 100 characters.";
    }

    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 6) { // Example: Minimum password length
        $errors[] = "Password must be at least 6 characters long.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    if (!$terms_agreed) {
        $errors[] = "You must agree to the Terms of Service & Privacy Policy.";
    }

    // --- Check if username or email already exists (if no prior validation errors) ---
    if (empty($errors)) {
        try {
            // Check for existing username
            $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = :username");
            $stmt->bindParam(':username', $username);
            $stmt->execute();
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $errors[] = "This username is already taken. Please choose another.";
            }

            // Check for existing email (only if username wasn't already an issue)
            if (empty($errors)) { // Re-check errors in case username was found
                $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email = :email");
                $stmt->bindParam(':email', $email);
                $stmt->execute();
                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $errors[] = "This email address is already registered.";
                }
            }
        } catch (PDOException $e) {
            error_log("Registration Check Error: " . $e->getMessage()); // Log detailed error
            $errors[] = "A database error occurred while checking your details. Please try again.";
        }
    }

    // --- If no errors, proceed to insert new user ---
    if (empty($errors)) {
        try {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $pdo->prepare(
                "INSERT INTO users (username, email, password_hash, first_name, last_name) 
                 VALUES (:username, :email, :password_hash, :first_name, :last_name)"
            );
            $stmt->bindParam(':username', $username);
            $stmt->bindParam(':email', $email);
            $stmt->bindParam(':password_hash', $password_hash);
            $stmt->bindParam(':first_name', $first_name);
            $stmt->bindParam(':last_name', $last_name);
            
            if ($stmt->execute()) {
                $success = "Registration successful! You can now <a href='login.php' data-i18n-key='register_success_login_link'>log in</a>.";
                // To automatically log in and redirect:
                // $_SESSION['user_id'] = $pdo->lastInsertId();
                // $_SESSION['username'] = $username;
                // // ... set other session variables ...
                // header("Location: dashboard.php"); // Or home.php
                // exit;
            } else {
                $errors[] = "Registration failed. Please try again.";
            }
        } catch (PDOException $e) {
            error_log("Registration Insert Error: " . $e->getMessage()); // Log detailed error
            $errors[] = "An error occurred during registration. Please try again later.";
        }
    }
}

// Determine if user is logged in (for header logic)
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title data-i18n-key="register_page_title_document">Register - CROUS-X</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/png" href="assets/images/icon.png" />
  </head>
  <body>
    <?php require 'header.php'; ?>

    <main class="app-container auth-page-wrapper"> <!-- Consistent main wrapper -->
      <div class="auth-form-container"> <!-- Specific container for the form box -->
        <h2 class="auth-form-title" data-i18n-key="register_form_title">Create Account</h2>

        <?php if (!empty($success)): ?>
          <div class="form-message success-message">
            <i class="fas fa-check-circle"></i>
            <span data-i18n-key="register_success_message_dynamic"><?php echo $success; // Link is part of the success message here ?></span>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="form-message error-message">
            <i class="fas fa-exclamation-triangle"></i>
            <div>
              <strong data-i18n-key="register_error_heading">Please correct the following errors:</strong>
              <ul class="error-list">
                <?php foreach ($errors as $error): ?>
                  <li><?php echo htmlspecialchars($error); ?></li>
                <?php endforeach; ?>
              </ul>
            </div>
          </div>
        <?php endif; ?>

        <?php if (empty($success)): // Only show form if registration isn't successful ?>
        <form action="register.php" method="post" id="registrationForm" class="auth-form">
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
                  value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
                  required
                />
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
                  value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
                  required
                />
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
                value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                pattern="^[a-zA-Z0-9_]+$"
                title="Username can only contain letters, numbers, and underscores."
                required
              />
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
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                required
              />
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
              </div>
            </div>
          </div>
          <div class="form-group terms-agreement-group">
            <label class="checkbox-item-inline">
              <input type="checkbox" id="terms" name="terms" required 
                     <?php echo (isset($_POST['terms'])) ? 'checked' : ''; ?> />
              <span data-i18n-key="register_text_agree_terms_part1">I agree to the</span> <a href="#" data-i18n-key="register_link_terms">Terms of Service</a> & <a href="#" data-i18n-key="register_link_privacy">Privacy Policy</a>
            </label>
          </div>
          <button type="submit" class="btn-auth primary-auth-button" data-i18n-key="register_button_register">
            Register
          </button>
        </form>
        <?php endif; // End of if(empty($success)) ?>

        <div class="auth-form-footer-links">
          <p data-i18n-key="register_text_already_account">Already have an account? <a href="login.php" data-i18n-key="register_link_signin_here">Sign in here</a></p>
        </div>
      </div>
    </main>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="script.js" defer></script>
  </body>
</html>