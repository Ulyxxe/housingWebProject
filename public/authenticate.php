<?php
session_start();
// It's good practice to have error reporting on for development
// ini_set('display_errors', 1);
// ini_set('display_startup_errors', 1);
// error_reporting(E_ALL);

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Password itself is not sanitized before hashing comparison

    // Validate that the fields are not empty
    if (empty($email) || empty($password)) {
        $_SESSION['login_error'] = "Please fill in all the fields.";
        header("Location: login.php"); // Login is in the same folder
        exit;
    }

    // Database connection parameters
    $host   = getenv('MYSQL_HOST');
    $dbname = getenv('MYSQL_DATABASE');
    $dbUser = getenv('MYSQL_USER');
    $dbPass = getenv('MYSQL_PASSWORD');

    try {
        // Create a PDO instance for a secure database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare a SQL statement to get user data by email
        // Fetching user_id, username, email, password_hash, first_name, last_name, user_type, is_active
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, first_name, last_name, user_type, is_active FROM users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Fetch the user record
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user exists, password is verified
        if ($user && password_verify($password, $user['password_hash'])) {
            // Check if user is active
            if ($user['is_active'] == 1) {
                // Set session variables upon successful login
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['first_name'] = $user['first_name'];
                $_SESSION['last_name'] = $user['last_name'];
                $_SESSION['user_type'] = $user['user_type']; // STORE USER TYPE

                // Regenerate session ID for security
                session_regenerate_id(true);

                // Check for a redirect URL (set by pages like booking.php if user was unauthenticated)
                if (isset($_SESSION['redirect_url'])) {
                    $redirect_to = $_SESSION['redirect_url'];
                    unset($_SESSION['redirect_url']); // Clear it after use
                    header("Location: " . $redirect_to);
                    exit;
                } else {
                    // Default redirect: admin to admin_dashboard, others to dashboard
                    if ($user['user_type'] === 'admin') {
                        header("Location: admin_dashboard.php");
                    } else {
                        header("Location: dashboard.php");
                    }
                    exit;
                }

            } else {
                $_SESSION['login_error'] = "Your account is inactive. Please contact support.";
                header("Location: login.php");
                exit;
            }
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: login.php");
            exit;
        }
    } catch (PDOException $e) {
        error_log("Database Error in authenticate.php: " . $e->getMessage());
        $_SESSION['login_error'] = "An internal error occurred. Please try again later.";
        header("Location: login.php");
        exit;
    }
} else {
    // If the form was not submitted, redirect back to the login page
    header("Location: login.php");
    exit;
}
?>