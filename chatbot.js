// chatbot.js

document.addEventListener("DOMContentLoaded", () => {
  // --- Get DOM Elements ---
  const chatbox = document.getElementById("chatbox");
  const userInput = document.getElementById("userInput");
  const sendButton = document.getElementById("sendButton");
  // Optional: Get the widget container if you want to add open/close logic later
  // const chatWidget = document.getElementById('chat-widget-container');

  // --- Basic Checks ---
  if (!chatbox || !userInput || !sendButton) {
    console.error("Chatbot UI elements missing! Cannot initialize chatbot.");
    return; // Stop if elements aren't found
  }

  // --- Functions ---

  function addMessage(text, sender) {
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message");
    messageDiv.classList.add(sender === "user" ? "user-message" : "ai-message");

    // Simple text sanitation (replace potential HTML tags - basic protection)
    // For more robust sanitation, use a library like DOMPurify if needed.
    messageDiv.textContent = text; // Use textContent to prevent HTML injection

    chatbox.appendChild(messageDiv);
    // Scroll to the bottom of the chatbox smoothly
    chatbox.scrollTo({
      top: chatbox.scrollHeight,
      behavior: "smooth",
    });
  }

  function showLoading() {
    // Check if loading indicator already exists
    if (document.getElementById("loading-indicator")) return;

    const loadingDiv = document.createElement("div");
    loadingDiv.classList.add("message", "ai-message", "loading");
    loadingDiv.id = "loading-indicator";
    loadingDiv.textContent = "Thinking..."; // Use textContent
    chatbox.appendChild(loadingDiv);
    chatbox.scrollTo({ top: chatbox.scrollHeight, behavior: "smooth" });
  }

  function hideLoading() {
    const loadingIndicator = document.getElementById("loading-indicator");
    if (loadingIndicator) {
      loadingIndicator.remove();
    }
  }

  async function sendMessage() {
    const messageText = userInput.value.trim();
    if (!messageText) return; // Don't send empty messages

    addMessage(messageText, "user");
    userInput.value = ""; // Clear input field
    showLoading();
    userInput.disabled = true;
    sendButton.disabled = true;

    try {
      const response = await fetch("api/chat_handler.php", {
        // Path to your PHP script
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          // API Key is handled by PHP, NOT sent from JS
        },
        body: JSON.stringify({ message: messageText }),
      });

      hideLoading(); // Hide loading indicator regardless of outcome

      // Always try to parse the response, even on error, to get error message from PHP
      const data = await response.json();

      if (!response.ok) {
        // Handle HTTP errors (4xx, 5xx) using the parsed error message from PHP
        console.error("Error from backend:", response.status, data.error);
        addMessage(`Error: ${data.error || "Failed to get response."}`, "ai");
      } else if (data.error) {
        // Handle application-level errors sent back with a 200 OK status
        console.error("Application error from backend:", data.error);
        addMessage(`Error: ${data.error}`, "ai");
      } else if (data.reply) {
        // Success case
        addMessage(data.reply, "ai");
      } else {
        // Unexpected: Response OK but no reply or error field
        console.error("Unexpected response format:", data);
        addMessage("Sorry, I received an unexpected response.", "ai");
      }
    } catch (error) {
      // Handle network errors (fetch failed completely)
      hideLoading();
      console.error("Network or fetch error:", error);
      addMessage(
        "Error: Could not connect to the assistant. Check your network.",
        "ai"
      );
    } finally {
      // Re-enable input regardless of success or failure
      userInput.disabled = false;
      sendButton.disabled = false;
      userInput.focus(); // Set focus back to input field
    }
  }

  // --- Event Listeners ---
  sendButton.addEventListener("click", sendMessage);

  userInput.addEventListener("keypress", (event) => {
    // Send message if Enter key is pressed (without Shift key)
    if (event.key === "Enter" && !event.shiftKey) {
      event.preventDefault(); // Prevent default form submission or newline
      sendMessage();
    }
  });
}); // End DOMContentLoaded

// --- Chat Widget Logic ---
const chatToggleButton = document.getElementById("chat-toggle-button");
const chatContainer = document.getElementById("chat-container");
const chatCloseButton = document.getElementById("chat-close-button");
const chatInput = document.getElementById("chat-input");
const chatSendButton = document.getElementById("chat-send-button");
const chatMessages = document.getElementById("chat-messages");
const chatLoading = document.getElementById("chat-loading"); // Get loading indicator

// --- Function to toggle chat visibility ---
function toggleChat() {
  if (chatContainer) {
    const isHidden = chatContainer.classList.toggle("chat-hidden");
    // Optional: Change toggle button icon based on state
    const icon = chatToggleButton ? chatToggleButton.querySelector("i") : null;
    if (icon) {
      if (isHidden) {
        icon.classList.remove("fa-times"); // Change back to chat icon
        icon.classList.add("fa-comments");
        chatToggleButton.setAttribute("aria-label", "Open chat");
      } else {
        icon.classList.remove("fa-comments"); // Change to close icon
        icon.classList.add("fa-times");
        chatToggleButton.setAttribute("aria-label", "Close chat");
        // Focus input when opening
        if (chatInput) chatInput.focus();
      }
    }
  }
}

// --- Event Listeners ---
if (chatToggleButton) {
  chatToggleButton.addEventListener("click", toggleChat);
}

if (chatCloseButton) {
  chatCloseButton.addEventListener("click", toggleChat); // Close button does the same toggle
}

// --- Function to add a message to the chat ---
function addChatMessage(message, sender = "bot") {
  if (!chatMessages) return;

  const messageElement = document.createElement("div");
  messageElement.classList.add("message", sender); // 'user' or 'bot'
  messageElement.textContent = message;
  chatMessages.appendChild(messageElement);

  // Auto-scroll to the bottom
  chatMessages.scrollTop = chatMessages.scrollHeight;
}

// --- Function to show/hide loading indicator ---
function showLoading(isLoading) {
  if (!chatLoading) return;
  if (isLoading) {
    chatLoading.classList.remove("chat-hidden");
  } else {
    chatLoading.classList.add("chat-hidden");
  }
}

// --- Function to handle sending a message ---
async function handleSendMessage() {
  if (!chatInput || !chatInput.value.trim()) return; // Ignore empty messages

  const userMessage = chatInput.value.trim();
  addChatMessage(userMessage, "user"); // Display user's message
  chatInput.value = ""; // Clear input field
  chatInput.disabled = true; // Disable input while waiting
  chatSendButton.disabled = true;
  showLoading(true); // Show thinking indicator

  // --- API Call ---
  try {
    const response = await fetch("api/chat_handler.php", {
      // Ensure this path is correct
      method: "POST",
      headers: {
        "Content-Type": "application/json",
      },
      body: JSON.stringify({ message: userMessage }),
    });

    const data = await response.json();

    if (!response.ok) {
      // Display specific error from API if available, otherwise generic
      addChatMessage(
        `Error: ${data.error || `HTTP ${response.status}`}`,
        "bot"
      );
    } else if (data.reply) {
      addChatMessage(data.reply, "bot"); // Display bot's reply
    } else if (data.error) {
      addChatMessage(`Error: ${data.error}`, "bot"); // Display error from JSON payload
    } else {
      addChatMessage("Sorry, I couldn't get a response.", "bot"); // Fallback
    }
  } catch (error) {
    console.error("Chat API Error:", error);
    addChatMessage(
      "Sorry, something went wrong trying to connect. Please try again.",
      "bot"
    );
  } finally {
    chatInput.disabled = false; // Re-enable input
    chatSendButton.disabled = false;
    showLoading(false); // Hide thinking indicator
    chatInput.focus(); // Refocus input
  }
}

// --- Event Listeners for Sending ---
if (chatSendButton) {
  chatSendButton.addEventListener("click", handleSendMessage);
}

if (chatInput) {
  // Allow sending message by pressing Enter key
  chatInput.addEventListener("keypress", function (event) {
    if (event.key === "Enter") {
      event.preventDefault(); // Prevent default form submission (if any)
      handleSendMessage();
    }
  });
}

// --- Initialize Chat State (Optional: Start hidden) ---
// The chat starts hidden because of the chat-hidden class in HTML.
// No extra JS needed for initial state unless you want it to open automatically under certain conditions.

// --- Make sure the rest of your existing script.js code is still present ---
// (e.g., dark mode toggle, map initialization, filtering logic, etc.)
// ...
