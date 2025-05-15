<?php
session_start();
// You can uncomment the line below if you have site-wide configuration to include
// require_once __DIR__ . '/../config/config.php';

// Determine if user is logged in
$isLoggedIn = isset($_SESSION['user_id']); // For header logic
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark" data-accent-color="crous-pink-primary">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title data-i18n-key="help_page_title_document">Help – CROUS-X</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"
    integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg=="
    crossorigin="anonymous" referrerpolicy="no-referrer"/>
  <link rel="stylesheet" href="style.css">
  <link rel="icon" type="image/png" href="assets/images/icon.png">
</head>
<body>
  <?php require 'header.php'; ?>

  <main class="app-container help-page-wrapper">
    <div class="help-content-area">
      <h1 class="page-main-heading" data-i18n-key="help_main_heading">Help Center</h1>

      <div class="help-sections-container">
        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s1_title">Getting Started</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s1_text">Welcome to CROUS-X! Use the <strong data-i18n-key="help_s1_text_strong1">News stand</strong> to browse the latest student housing listings. You can click on any listing to see more details.</p>
          </div>
        </section>

        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s2_title">Searching & Filtering</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s2_text_p1">At the top of the listings, use the search bar to find a housing by name. Open the filter sidebar to:</p>
            <ul class="styled-list-help">
              <li data-i18n-key="help_s2_text_li1">Select housing types (Studio, Apartment, Shared Room, House)</li>
              <li data-i18n-key="help_s2_text_li2">Adjust the maximum price and size sliders</li>
              <li data-i18n-key="help_s2_text_li3">Clear all filters with the <em data-i18n-key="help_s2_text_em1">Clear Filters</em> button</li>
            </ul>
          </div>
        </section>

        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s3_title">Sorting</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s3_text_p1">Use the sort controls to order results by:</p>
            <ul class="styled-list-help">
              <li data-i18n-key="help_s3_text_li1"><strong data-i18n-key="help_s3_text_strong1">New</strong> — most recently added first</li>
              <li data-i18n-key="help_s3_text_li2"><strong>Price ascending</strong> or <strong>descending</strong></li>
              <li data-i18n-key="help_s3_text_li3"><strong data-i18n-key="help_s3_text_strong3">Rating</strong></li>
            </ul>
          </div>
        </section>

        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s4_title">Map View</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s4_text">Toggle the map pane to see listings on the map. You can drag the resize handle at the right edge of the map to adjust its width. Click markers for quick info or open the popup for details.</p>
          </div>
        </section>

        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s5_title">Account & Profile</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s5_text">
              To save favorites or manage your listings, <a href="register.php" data-i18n-key="help_s5_link_register">register</a> for an account. If you already have one, <a href="login.php" data-i18n-key="help_s5_link_signin">sign in</a>. Once logged in, visit your <a href="#" data-i18n-key="help_s5_link_profile">profile</a> page.
            </p>
          </div>
        </section>

        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s6_title">Chatbot Assistance</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s6_text">
              Click the pink chat bubble in the bottom-right corner to open our assistant. You can ask questions like “How do I apply?” or “Show me studios under €400”.
            </p>
          </div>
        </section>

        <section class="help-section-item">
          <h2 class="help-section-title" data-i18n-key="help_s7_title">More FAQs</h2>
          <div class="help-section-content">
            <p data-i18n-key="help_s7_text">If you still have questions, check our <a href="faq.php" data-i18n-key="help_s7_link_faq">Frequently Asked Questions</a> page for detailed answers.</p>
          </div>
        </section>
      </div> <!-- .help-sections-container -->
    </div> <!-- .help-content-area -->
  </main>

  <?php require 'chat-widget.php';?>

  <script src="script.js" defer></script>
  <script src="chatbot.js" defer></script>
</body>
</html>