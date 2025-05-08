<?php
// public/admin_dashboard.php
session_start();
if (empty($_SESSION['is_admin'])) {
    header('Location: admin.php');
    exit;
}
// … your admin interface here …
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="dashboard-page-wrapper">
  <div class="dashboard-container">
    <h1>Admin Dashboard</h1>
    <p>Welcome, <strong><?=htmlspecialchars(ADMIN_USER)?></strong>!</p>
    <p><a href="admin_logout.php" class="btn-signin">Log Out</a></p>
    <!-- Add your admin controls: manage housing listings, view users, etc. -->
  </div>
</body>
</html>
