<?php
session_start();

// Check if the form has been submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Retrieve and sanitize input data
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password']; // More validation could be added here

    // Validate that the fields are not empty
    if (empty($email) || empty($password)) {
        echo "Please fill in all the fields.";
        exit;
    }

    // Database connection parameters (adjust these as needed)
    $host   = getenv('MYSQL_HOST');
    $dbname = getenv('MYSQL_DATABASE');
    $dbUser = getenv('MYSQL_USER');
    $dbPass = getenv('MYSQL_PASSWORD');

    try {
        // Create a PDO instance for a secure database connection
        $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Prepare a SQL statement to get user data by email
        $stmt = $pdo->prepare("SELECT userID, password, userType FROM Users WHERE email = :email LIMIT 1");
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        // Fetch the user record
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // If user exists and password is verified using password_verify()
        if ($user && password_verify($password, $user['password'])) {
            // Set session variables upon successful login
            $_SESSION['userID'] = $user['userID'];
            $_SESSION['userType'] = $user['userType'];
            $_SESSION['email'] = $email;
            
            // Redirect to a protected page (dashboard)
            header("Location: dashboard.php");
            exit;
        } else {
            echo "Invalid email or password.";
        }
    } catch (PDOException $e) {
        // Handle any errors while connecting or querying
        echo "Error: " . $e->getMessage();
        exit;
    }
} else {
    // If the form was not submitted, redirect back to the login page
    header("Location: login.html");
    exit;
}
?>
