<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // Password itself is not sanitized before hashing comparison

    // Validate that the fields are not empty
    if (empty($email) || empty($password)) {
        // It's better to redirect back with an error message or display it on the login page
        $_SESSION['login_error'] = "Please fill in all the fields.";
        header("Location: login.php"); // Adjusted path to login.php
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
        // Updated column names: user_id, password_hash, user_type
        // Added check for is_active
        $stmt = $pdo->prepare("SELECT user_id, username, email, password_hash, user_type, is_active FROM Users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Fetch the user record
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user exists, password is verified, and user is active
        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_active'] == 1) {
                // Set session variables upon successful login
                $_SESSION['user_id'] = $user['user_id'];       // Updated from userID
                $_SESSION['username'] = $user['username'];     // Store username
                $_SESSION['email'] = $user['email'];           // Email is already correct
                $_SESSION['userType'] = $user['user_type'];   // Storing DB's user_type into session's userType
                
                // Regenerate session ID for security
                session_regenerate_id(true);

                // Redirect to a protected page (dashboard)
                // Path to dashboard.php is now relative to this controller's location
                header("Location: dashboard.php");
                exit;
            } else {
                $_SESSION['login_error'] = "Your account is inactive. Please contact support.";
                header("Location: login.php"); // Adjusted path
                exit;
            }
        } else {
            $_SESSION['login_error'] = "Invalid email or password.";
            header("Location: login.php"); // Adjusted path
            exit;
        }
    } catch (PDOException $e) {
        // Handle any errors while connecting or querying
        // Log the detailed error for developers
        error_log("Database Error in authenticate.php: " . $e->getMessage());
        $_SESSION['login_error'] = "An internal error occurred. Please try again later.";
        header("Location: login.php"); // Adjusted path
        exit;
    }
} else {
    // If the form was not submitted, redirect back to the login page
    // Assuming login.php is in the public directory
    header("Location: login.php"); // Adjusted path
    exit;
}
?>