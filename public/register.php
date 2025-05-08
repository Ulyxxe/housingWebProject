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

            // Insert into 'users' table.
            // 'user_type' will use its DB default ('student').
            // 'is_active' will use its DB default (1).
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
                $success = "Registration successful! You can now <a href='login.php'>log in</a>.";
                // To automatically log in:
                // $_SESSION['user_id'] = $pdo->lastInsertId();
                // $_SESSION['username'] = $username;
                // $_SESSION['email'] = $email;
                // $_SESSION['first_name'] = $first_name;
                // $_SESSION['last_name'] = $last_name;
                // $_SESSION['user_type'] = 'student'; // Or fetch it if needed
                // header("Location: dashboard.php");
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
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Register - CROUS-X</title>
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin=""/>
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css" />
    <link rel="stylesheet" href="https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css" />
    <link rel="stylesheet" href="style.css" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="icon" type="image/png" href="assets/images/icon.png" />
  </head>
  <body>
    <header class="site-header">
      <a href="index.php" class="logo-link">
        <div class="logo">CROUS-X</div>
      </a>
      <nav class="main-nav">
        <ul>
          <li><a href="index.php">Search Housing</a></li>
          <li><a href="help.php">Need help ?</a></li>
          <li>
            <button id="theme-toggle" class="btn btn-dark-mode" aria-label="Toggle dark mode">
              <i class="fas fa-moon"></i>
            </button>
          </li>
          <li><a href="login.php" class="btn btn-signin">Sign in</a></li>
        </ul>
      </nav>
    </header>

    <div class="main-content-wrapper register-page-wrapper">
      <div class="register-container">
        <h2>Create Account</h2>

        <?php if (!empty($success)): ?>
          <div class="alert alert-success" role="alert" style="color: green; background-color: #d4edda; border-color: #c3e6cb; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem;">
            <?php echo $success; ?>
          </div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger" role="alert" style="color: #721c24; background-color: #f8d7da; border-color: #f5c6cb; padding: .75rem 1.25rem; margin-bottom: 1rem; border: 1px solid transparent; border-radius: .25rem;">
            <ul style="margin: 0; padding-left: 20px;">
              <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <?php if (empty($success)): ?>
        <form action="register.php" method="post" id="registrationForm">
          <div class="form-group">
            <label for="first_name">First Name</label>
            <input
              type="text"
              id="first_name"
              name="first_name" 
              placeholder="Enter your first name"
              value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>"
              required
            />
          </div>
          <div class="form-group">
            <label for="last_name">Last Name</label>
            <input
              type="text"
              id="last_name"
              name="last_name" 
              placeholder="Enter your last name"
              value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>"
              required
            />
          </div>
          <div class="form-group">
            <label for="username">Username</label>
            <input
              type="text"
              id="username"
              name="username" 
              placeholder="Choose a username (letters, numbers, _)"
              value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
              pattern="^[a-zA-Z0-9_]+$"
              title="Username can only contain letters, numbers, and underscores."
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
              value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
              required
            />
          </div>
          <div class="form-group">
            <label for="password">Password (min. 6 characters)</label>
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
            <input type="checkbox" id="terms" name="terms" required 
                   <?php echo (isset($_POST['terms'])) ? 'checked' : ''; ?> />
            <label for="terms"
              >I agree to the <a href="#">Terms of Service</a> &
              <a href="#">Privacy Policy</a></label
            >
          </div>
          <button type="submit" class="btn btn-register btn-register-submit">
            Register
          </button>
        </form>
        <?php endif; ?>

        <div class="login-links">
          <p>Already have an account? <a href="login.php">Sign in here</a></p>
        </div>
      </div>
    </div>

    <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>
    <script src="https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js"></script>
    <script src="script.js"></script>
  </body>
</html>