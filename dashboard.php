<?php
session_start();

// Check if the user is logged in by verifying session variables
if (!isset($_SESSION['userID'])) {
    // If not logged in, redirect to the login page
    header("Location: login.html");
    exit;
}

// The user is authenticated, so you can display restricted content
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Dashboard</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body>
  <h1>Welcome to Your Dashboard!</h1>
  <p>You are logged in as <?= htmlspecialchars($_SESSION['email']) ?> (<?= htmlspecialchars($_SESSION['userType']) ?>).</p>
  <a href="logout.php">Logout</a>
</body>
</html>
