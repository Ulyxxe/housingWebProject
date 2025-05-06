<?php
session_start();
// If you have configuration (e.g. database, constants), include it here:
// require __DIR__ . '/../config/config.php';

// Determine if user is logged in
$isLoggedIn = isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title>FAQ - CROUS-X</title>
  <link rel="stylesheet" href="style.css">
</head>
<body>
  <header class="site-header">
    <a href="index.php" class="logo">CROUS-X</a>
    <nav class="main-nav">
      <ul>
        <li><a href="index.php" data-i18n-key="nav_news">News stand</a></li>
        <li><a href="help.php" data-i18n-key="nav_help">Need help ?</a></li>
        <li><a href="profile.php" data-i18n-key="nav_profile">My profile</a></li>
        <?php if ($isLoggedIn): ?>
          <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
          <li><a href="login.php" data-i18n-key="nav_signin">Sign in</a></li>
          <li><a href="register.php" data-i18n-key="nav_register">Register</a></li>
        <?php endif; ?>
      </ul>
    </nav>
  </header>

  <main class="content-box">
    <h1>Frequently Asked Questions</h1>

    <section class="faq-item">
      <h2>How do I search for available housing?</h2>
      <p>
        Use the search bar at the top of the listings page to search by name.  
        You can also apply filters for price, size, and type on the sidebar.
      </p>
    </section>

    <section class="faq-item">
      <h2>How do I book a room or apartment?</h2>
      <p>
        Once you’ve found a listing you like, click on it to view details.  
        If it’s available, there will be a “Register” or “Request Booking” button—follow the on-screen instructions.
      </p>
    </section>

    <section class="faq-item">
      <h2>Can I cancel or change my booking?</h2>
      <p>
        Yes—log in to your dashboard, go to “My Bookings,” and select the booking you want to modify or cancel.  
        Note that cancellation policies may vary by property.
      </p>
    </section>

    <section class="faq-item">
      <h2>Who can I contact for further help?</h2>
      <p>
        If you need assistance beyond this FAQ, click on the “Need help?” link in the navigation,  
        or send us a message via the chat widget at the bottom right of the screen.
      </p>
    </section>
  </main>

  <script src="script.js" defer></script>
  <script src="chatbot.js" defer></script>
</body>
</html>
