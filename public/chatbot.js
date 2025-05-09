// chatbot.js (Self-Contained with Toggle Logic)

document.addEventListener("DOMContentLoaded", () => {
  // --- Chat Widget DOM Elements ---
  const chatToggleButton = document.getElementById("chat-toggle-button");
  const chatContainer = document.getElementById("chat-container");
  const chatCloseButton = document.getElementById("chat-close-button");
  // --- Internal Chat DOM Elements ---
  const chatMessagesContainer = document.getElementById("chat-messages");
  const chatInputField = document.getElementById("chat-input");
  const chatSendButtonElem = document.getElementById("chat-send-button");
  const chatLoadingIndicator = document.getElementById("chat-loading");

  // --- Basic Checks for Core Chat Functionality ---
  if (!chatMessagesContainer || !chatInputField || !chatSendButtonElem) {
    console.error(
      "Chatbot internal UI elements (chat-messages, chat-input, or chat-send-button) missing! Cannot initialize chatbot message functionality."
    );
    // We can still try to initialize the toggle if those elements exist
  }

  // --- Check for Toggle Elements ---
  if (!chatToggleButton || !chatContainer || !chatCloseButton) {
    console.warn(
      "Chatbot toggle/container elements missing. Chat visibility control might not work."
    );
  }

  // --- Function to toggle chat visibility ---
  function toggleChatVisibility() {
    if (chatContainer && chatToggleButton) {
      const isHidden = chatContainer.classList.toggle("chat-hidden");
      chatToggleButton.setAttribute("aria-expanded", String(!isHidden));
      const icon = chatToggleButton.querySelector("i");
      if (icon) {
        icon.className = isHidden ? "fas fa-comments" : "fas fa-times";
      }
      if (!isHidden && chatInputField) {
        chatInputField.focus();
      }
    }
  }

  // --- Event Listeners for Toggling Chat ---
  if (chatToggleButton) {
    chatToggleButton.addEventListener("click", toggleChatVisibility);
  }

  if (chatCloseButton) {
    chatCloseButton.addEventListener("click", toggleChatVisibility); // Close button also toggles
  }

  // --- Functions for internal chat logic (message handling, API calls) ---
  function addMessageToChatUI(text, sender) {
    if (!chatMessagesContainer) return; // Guard clause
    const messageDiv = document.createElement("div");
    messageDiv.classList.add("message");
    messageDiv.classList.add(sender === "user" ? "user" : "bot");

    if (sender === "bot" && typeof marked !== "undefined" && marked?.parse) {
      try {
        messageDiv.innerHTML = marked.parse(text);
      } catch (e) {
        console.error("Error parsing Markdown:", e);
        messageDiv.textContent = text; // Fallback to text if Markdown fails
      }
    } else {
      messageDiv.textContent = text;
    }

    chatMessagesContainer.appendChild(messageDiv);
    chatMessagesContainer.scrollTo({
      top: chatMessagesContainer.scrollHeight,
      behavior: "smooth",
    });
  }

  function showChatLoadingUI(isLoading) {
    if (!chatLoadingIndicator) return;
    if (isLoading) {
      chatLoadingIndicator.classList.remove("chat-hidden");
    } else {
      chatLoadingIndicator.classList.add("chat-hidden");
    }
  }

  async function processAndSendMessage() {
    if (!chatInputField || !chatSendButtonElem) return; // Guard clause

    const messageText = chatInputField.value.trim();
    if (!messageText) return;

    addMessageToChatUI(messageText, "user");
    chatInputField.value = "";
    chatInputField.disabled = true;
    chatSendButtonElem.disabled = true;
    showChatLoadingUI(true);

    try {
      const response = await fetch("api/chat_handler.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ message: messageText }),
      });

      showChatLoadingUI(false);
      const data = await response.json();

      if (!response.ok) {
        addMessageToChatUI(
          `Error: ${data.error || "Failed to get response."}`,
          "bot"
        );
      } else if (data.error) {
        addMessageToChatUI(`Error: ${data.error}`, "bot");
      } else if (data.reply) {
        addMessageToChatUI(data.reply, "bot");
      } else {
        addMessageToChatUI("Sorry, I received an unexpected response.", "bot");
      }
    } catch (error) {
      showChatLoadingUI(false);
      console.error("Chat API Error:", error);
      addMessageToChatUI(
        "Error: Could not connect. Check your network.",
        "bot"
      );
    } finally {
      chatInputField.disabled = false;
      chatSendButtonElem.disabled = false;
      chatInputField.focus();
    }
  }

  // --- Event Listeners for internal chat actions (Sending messages) ---
  if (chatSendButtonElem) {
    chatSendButtonElem.addEventListener("click", processAndSendMessage);
  }

  if (chatInputField) {
    chatInputField.addEventListener("keypress", (event) => {
      if (event.key === "Enter" && !event.shiftKey) {
        event.preventDefault();
        processAndSendMessage();
      }
    });
  }

  // Add initial greeting if it's defined in HTML and chat is not hidden initially
  // (This assumes the greeting is part of the static HTML with a data-lang-key)
  // The main script's `applyTranslations` will handle translating it.
  // If chat starts open and needs a JS-added greeting, that would be more complex
  // as `chatbot.js` doesn't have direct access to `currentLanguageData` from the main script.
  const initialGreetingElement = chatMessagesContainer?.querySelector(
    '.message.bot[data-lang-key="chat_greeting_app"]'
  );
  if (initialGreetingElement && chatMessagesContainer.children.length === 1) {
    // If the greeting is already in the HTML, ensure it's scrolled into view if chat is open on load
    if (chatContainer && !chatContainer.classList.contains("chat-hidden")) {
      chatMessagesContainer.scrollTo({
        top: chatMessagesContainer.scrollHeight,
        behavior: "auto",
      });
    }
  }
}); // End DOMContentLoaded
