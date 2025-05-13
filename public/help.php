<?php
session_start();
// You can uncomment the line below if you have site-wide configuration to include
// require_once __DIR__ . '/../config/config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Help – CROUS-X</title>
  <link rel="stylesheet" href="style.css">
  <script defer src="script.js"></script>
  <script defer src="chatbot.js"></script>
</head>
<body>
  <?php require 'header.php'; // Or require_once if you prefer ?>

  <main class="content-box" style="max-width: 800px; margin: 40px auto;">
    <h1>Help Center</h1>

    <section>
      <h2>Getting Started</h2>
      <p>Welcome to CROUS-X! Use the <strong>News stand</strong> to browse the latest student housing listings. You can click on any listing to see more details.</p>
    </section>

    <section>
      <h2>Searching & Filtering</h2>
      <p>At the top of the listings, use the search bar to find a housing by name. Open the filter sidebar to:</p>
      <ul>
        <li>Select housing types (Studio, Apartment, Shared Room, House)</li>
        <li>Adjust the maximum price and size sliders</li>
        <li>Clear all filters with the <em>Clear Filters</em> button</li>
      </ul>
    </section>

    <section>
      <h2>Sorting</h2>
      <p>Use the sort controls to order results by:</p>
      <ul>
        <li><strong>New</strong> — most recently added first</li>
        <li><strong>Price ascending</strong> or <strong>descending</strong></li>
        <li><strong>Rating</strong></li>
      </ul>
    </section>

    <section>
      <h2>Map View</h2>
      <p>Toggle the map pane to see listings on the map. You can drag the resize handle at the right edge of the map to adjust its width. Click markers for quick info or open the popup for details.</p>
    </section>

    <section>
      <h2>Account & Profile</h2>
      <p>
        To save favorites or manage your listings, <a href="register.php">register</a> for an account. If you already have one, <a href="login.php">sign in</a>. Once logged in, visit your <a href="#">profile</a> page.
      </p>
    </section>

    <section>
      <h2>Chatbot Assistance</h2>
      <p>
        Click the pink chat bubble in the bottom-right corner to open our assistant. You can ask questions like “How do I apply?” or “Show me studios under €400”.  
      </p>
    </section>

    <section>
      <h2>More FAQs</h2>
      <p>If you still have questions, check our <a href="faq.php">Frequently Asked Questions</a> page for detailed answers.</p>
    </section>
  </main>

  <!-- Chat widget markup (for chatbot.js) -->
  <div id="chat-widget">
    <div id="chat-container" class="chat-hidden">
      <div id="chat-header">
        <span data-i18n-key="chat_title">CROUS-X Assistant</span>
        <button id="chat-close-button" aria-label="Close chat">&times;</button>
      </div>
      <div id="chat-messages"></div>
      <div id="chat-loading"><i class="fas fa-spinner"></i> <span data-i18n-key="chat_loading">Thinking...</span></div>
      <div id="chat-input-area">
        <input id="chat-input" type="text" placeholder="Ask a question…" data-i18n-key-placeholder="chat_placeholder" />
        <button id="chat-send-button" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
      </div>
    </div>
  </div>
</body>
</html>

