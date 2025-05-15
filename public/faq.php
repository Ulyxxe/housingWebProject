<?php
session_start();
// If you have configuration (e.g. database, constants), include it here:
// require __DIR__ . '/../config/config.php';

// Determine if user is logged in
$isLoggedIn = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1.0">
  <title data-i18n-key="faq_page_title_document">FAQ - CROUS-X</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="assets/images/icon.png"> 
</head>
<body>
  <?php require 'header.php'; ?>

  <main class="app-container faq-page-wrapper"> 
    <div class="faq-content-area"> 
      <h1 class="page-main-heading" data-i18n-key="faq_main_heading">Frequently Asked Questions</h1>

      <div class="faq-list-container">
        <section class="faq-item">
          <h2 class="faq-question" data-i18n-key="faq_q1_title">How do I search for available housing?</h2>
          <div class="faq-answer-content">
            <p data-i18n-key="faq_q1_text">
              Use the search bar at the top of the listings page to search by name.
              You can also apply filters for price, size, and type on the sidebar.
            </p>
          </div>
        </section>

        <section class="faq-item">
          <h2 class="faq-question" data-i18n-key="faq_q2_title">How do I book a room or apartment?</h2>
          <div class="faq-answer-content">
            <p data-i18n-key="faq_q2_text">
              Once you’ve found a listing you like, click on it to view details.
              If it’s available, there will be a “Register” or “Request Booking” button—follow the on-screen instructions.
            </p>
          </div>
        </section>

        <section class="faq-item">
          <h2 class="faq-question" data-i18n-key="faq_q3_title">Can I cancel or change my booking?</h2>
          <div class="faq-answer-content">
            <p data-i18n-key="faq_q3_text">
              Yes, log in to your dashboard, go to “My Bookings,” and select the booking you want to modify or cancel.
              Note that cancellation policies may vary by property.
            </p>
          </div>
        </section>

        <section class="faq-item">
          <h2 class="faq-question" data-i18n-key="faq_q4_title">Who can I contact for further help?</h2>
          <div class="faq-answer-content">
            <p data-i18n-key="faq_q4_text">
              If you need assistance beyond this FAQ, click on the “Need help?” button in the header,
              or use the chat widget on the bottom right of your screen.
            </p>
          </div>
        </section>
        
        
      </div> 
    </div> 
  </main>

  <?php require 'chat-widget.php';?>

  <script src="script.js" defer></script>
  <script src="chatbot.js" defer></script>
</body>
</html>