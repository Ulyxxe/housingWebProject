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
