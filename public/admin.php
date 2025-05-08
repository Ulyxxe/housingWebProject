<?php
// public/admin.php
session_start();
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/generate_hash.php';

if (!empty($_SESSION['is_admin'])) {
    header('Location: admin_dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user = $_POST['username'] ?? '';
    $pass = $_POST['password'] ?? '';

    if ($user === ADMIN_USER && verify_hash($pass, ADMIN_PASS_HASH)) {
        $_SESSION['is_admin'] = true;
        header('Location: admin_dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Login</title>
  <link rel="stylesheet" href="style.css">
</head>
<body class="login-page-wrapper">
  <div class="login-container">
    <h2>Administrator Login</h2>
    <?php if ($error): ?>
      <p class="error" style="color:red;"><?=htmlspecialchars($error)?></p>
    <?php endif; ?>
    <form method="post" action="">
      <div class="form-group">
        <label for="username">Username</label>
        <input type="text"
               id="username"
               name="username"
               required>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password"
               id="password"
               name="password"
               required>
      </div>
      <button type="submit" class="btn-register-submit">Log In</button>
    </form>
  </div>
</body>
</html>
