<div id="chat-widget">
      <div id="chat-container" class="chat-hidden">
        <div id="chat-header">
          <span data-lang-key="chat_title_app">CROUS-X Assistant</span>
          <button id="chat-close-button" aria-label="Close chat">×</button>
        </div>
        <div id="chat-messages">
          <div class="message bot" data-lang-key="chat_greeting_app">
            Hi there! How can I help you navigate CROUS-X today?
          </div>
        </div>
        <div id="chat-input-area">
          <input type="text" id="chat-input" placeholder="Ask a question..." data-lang-key-placeholder="chat_placeholder_app" />
          <button id="chat-send-button" aria-label="Send message"><i class="fas fa-paper-plane"></i></button>
        </div>
        <div id="chat-loading" class="chat-hidden" data-lang-key="chat_loading_app">
          <i class="fas fa-spinner fa-spin"></i> Thinking...
        </div>
      </div>
      <button id="chat-toggle-button" aria-label="Toggle chat">
        <i class="fas fa-comments"></i>
      </button>
    </div>