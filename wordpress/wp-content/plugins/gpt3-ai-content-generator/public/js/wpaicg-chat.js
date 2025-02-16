/**
 * Loads or initializes conversations on page load, but now
 * simply displays the *latest* conversation instead of forcing
 * a brand-new conversation each time.
 */
function loadConversations() {
    // Attempt to retrieve existing clientId from localStorage; create if missing
    var clientId = localStorage.getItem('wpaicg_chat_client_id');
    if (!clientId) {
        clientId = generateRandomString(10);
        localStorage.setItem('wpaicg_chat_client_id', clientId);
    }

    // Load chat interfaces (shortcode + widget) on the page
    loadChatInterface('.wpaicg-chat-shortcode', 'shortcode', clientId);
    loadChatInterface('.wpaicg-chatbox', 'widget', clientId);

    // For each container, set its active conversation to the highest
    // existing index. If none exist, create a new conversation once.
    var containers = document.querySelectorAll('.wpaicg-chat-shortcode, .wpaicg-chatbox');
    containers.forEach(container => {
        let botId = container.getAttribute('data-bot-id') || '0';
        let chatType = container.getAttribute('data-type') || 'shortcode';

        // Build base localStorage prefix
        let conversationKeyBase = (botId !== '0')
            ? `wpaicg_chat_history_custom_bot_${botId}_${clientId}`
            : `wpaicg_chat_history_${chatType}_${clientId}`;

        // Find existing conversation indexes
        let highestIndex = 0;
        for (let i = 0; i < localStorage.length; i++) {
            let key = localStorage.key(i);
            if (key.startsWith(conversationKeyBase)) {
                let suffixMatch = key.match(/_(\d+)$/);
                if (suffixMatch) {
                    let indexNum = parseInt(suffixMatch[1], 10);
                    if (indexNum > highestIndex) {
                        highestIndex = indexNum;
                    }
                }
            }
        }

        // If no existing conversation found, create one
        if (highestIndex === 0) {
            highestIndex = 1;
            let firstConversationKey = `${conversationKeyBase}_${highestIndex}`;
            localStorage.setItem(firstConversationKey, JSON.stringify([]));
        }

        // Mark the highest (latest) conversation as active
        let latestConversationKey = `${conversationKeyBase}_${highestIndex}`;
        let activeConversationKey = `wpaicg_current_conversation_${botId}_${clientId}`;
        localStorage.setItem(activeConversationKey, latestConversationKey);

        // If assistant is enabled, do the same for the "thread" logic
        let assistantEnabled = container.getAttribute('data-assistant-enabled') === 'true';
        if (assistantEnabled) {
            let threadBase = (botId !== '0')
                ? `custom_bot_${botId}_${clientId}`
                : `${chatType}_${clientId}`;

            let threadListObj = JSON.parse(localStorage.getItem('wpaicg_thread_list')) || {};
            let highestThreadIndex = 0;
            for (let existingKey in threadListObj) {
                if (existingKey.startsWith(threadBase)) {
                    let suffixMatch = existingKey.match(/_(\d+)$/);
                    if (suffixMatch) {
                        let threadIndexNum = parseInt(suffixMatch[1], 10);
                        if (threadIndexNum > highestThreadIndex) {
                            highestThreadIndex = threadIndexNum;
                        }
                    }
                }
            }

            // If no existing thread, create a new one
            if (highestThreadIndex === 0) {
                highestThreadIndex = 1;
                let newThreadKey = `${threadBase}_${highestThreadIndex}`;
                localStorage.setItem(`wpaicg_current_thread_${botId}_${clientId}`, newThreadKey);
            } else {
                // Otherwise, set the highest as active
                let existingThreadKey = `${threadBase}_${highestThreadIndex}`;
                localStorage.setItem(`wpaicg_current_thread_${botId}_${clientId}`, existingThreadKey);
            }
        }
    });

    // Update the conversation list in the sidebar
    loadConversationList();
}
/**
 * Initialize "New Chat" buttons. When a user clicks "New Chat," we create
 * a fresh conversation in localStorage and load it in the chat interface.
 * This updated version also immediately refreshes the conversation list
 * in the sidebar so users see the new chat appear instantly.
 */
function initNewChatButtons() {
    const newChatButtons = document.querySelectorAll('.wpaicg-new-chat-button');
    newChatButtons.forEach(button => {
        button.addEventListener('click', function () {
            // Find the parent chat container (shortcode or widget)
            const chatContainer = button.closest('.wpaicg-chat-shortcode, .wpaicg-chatbox');
            if (!chatContainer) return;

            // Identify chat type, e.g. 'custom_bot_4367' or 'shortcode' or 'widget'
            const botId = chatContainer.getAttribute('data-bot-id') || '0';
            const chatType = chatContainer.getAttribute('data-type') || 'shortcode';
            const assistantEnabled = chatContainer.getAttribute('data-assistant-enabled') === 'true';
            let conversationKeyBase = '';

            // Distinguish custom bots vs default shortcodes or widgets
            if (botId !== '0') {
                // custom bot format
                conversationKeyBase = `wpaicg_chat_history_custom_bot_${botId}`;
            } else {
                // fallback: default shortcodes or widgets
                conversationKeyBase = `wpaicg_chat_history_${chatType}`;
            }

            // Grab or create the clientID
            let clientID = localStorage.getItem('wpaicg_chat_client_id');
            if (!clientID) {
                clientID = generateRandomString(10);
                localStorage.setItem('wpaicg_chat_client_id', clientID);
            }

            conversationKeyBase += `_${clientID}`;

            // Count how many existing conversations use this prefix
            let highestIndex = 0;
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                if (key.startsWith(conversationKeyBase)) {
                    const suffixMatch = key.match(/_(\d+)$/);
                    if (suffixMatch) {
                        const indexNum = parseInt(suffixMatch[1], 10);
                        if (indexNum > highestIndex) highestIndex = indexNum;
                    }
                }
            }
            // The new conversation will be next integer
            const newConversationIndex = highestIndex + 1;
            const newConversationKey = `${conversationKeyBase}_${newConversationIndex}`;

            // Initialize an empty array for that conversation
            localStorage.setItem(newConversationKey, JSON.stringify([]));

            // Also set a timestamp for sorting
            localStorage.setItem(`${newConversationKey}_timestamp`, Date.now().toString());

            // Also set this new conversation key as the "active conversation"
            const activeConversationKey = `wpaicg_current_conversation_${botId}_${clientID}`;
            localStorage.setItem(activeConversationKey, newConversationKey);

            // ----- Assistant-enabled logic for multi-thread handling -----
            if (assistantEnabled) {
                // We store the "active thread" localStorage
                const threadBase = botId !== '0'
                    ? `custom_bot_${botId}_${clientID}`
                    : `${chatType}_${clientID}`;

                // Count how many existing "thread" keys exist for this base
                let highestThreadIndex = 0;
                const threadListObj = JSON.parse(localStorage.getItem('wpaicg_thread_list')) || {};
                for (let existingKey in threadListObj) {
                    if (existingKey.startsWith(threadBase)) {
                        const suffixMatch = existingKey.match(/_(\d+)$/);
                        if (suffixMatch) {
                            const threadIndexNum = parseInt(suffixMatch[1], 10);
                            if (threadIndexNum > highestThreadIndex) highestThreadIndex = threadIndexNum;
                        }
                    }
                }
                const newThreadIndex = highestThreadIndex + 1;
                const newThreadKey = `${threadBase}_${newThreadIndex}`;

                // Now set the "active thread" localStorage
                const activeThreadKey = `wpaicg_current_thread_${botId}_${clientID}`;
                localStorage.setItem(activeThreadKey, newThreadKey);
            }

            // Immediately load the newly created conversation so user sees empty chat
            loadSelectedConversation(newConversationKey, chatContainer);

            // Refresh the conversation list in the sidebar so user sees it
            loadConversationList();
        });
    });
}

/**
 * Utility function that the code base already uses or references for client ID generation.
 * If you already have a similar function, you may omit or adapt this.
 */
function generateRandomString(length) {
    let result = '';
    const characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    for (let i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() * characters.length));
    }
    return result;
}
function showAllConversationStarters() {
    // Target both interfaces
    var containers = ['.wpaicg-chat-shortcode', '.wpaicg-chatbox'];
    containers.forEach(containerSelector => {
        var chatContainers = document.querySelectorAll(containerSelector);
        chatContainers.forEach(chatContainer => {
            showConversationStarters(chatContainer);
        });
    });
}
/**
 * Appends the user/AI message to the localStorage conversation, respecting memory limits
 * and triggers a refresh of the conversation list. Also stores a timestamp for sorting.
 *
 * @param {string} message           The raw message text from the user or AI.
 * @param {string} sender            Either 'user' or 'ai'.
 * @param {string} uniqueId          A unique identifier for the message (e.g., a random integer).
 * @param {HTMLElement} chat         The parent chat container (.wpaicg-chat-shortcode or .wpaicg-chatbox).
 * @param {string} chatbot_identity  Identity of the chatbot ('custom_bot_####' or 'shortcode'/'widget').
 * @param {string} clientID          The user's local client/session ID from localStorage.
 */
function updateChatHistory(message, sender, uniqueId, chat, chatbot_identity, clientID) {
    // Identify which bot is being used
    const botId = chat.getAttribute('data-bot-id') || '0';
    // Active conversation key in localStorage
    const activeConversationKey = `wpaicg_current_conversation_${botId}_${clientID}`;
    let conversationKey = localStorage.getItem(activeConversationKey);

    // If no active conversation yet, create one
    if (!conversationKey) {
        let defaultIndex = 1;
        if (botId !== '0') {
            // custom bot
            conversationKey = `wpaicg_chat_history_custom_bot_${botId}_${clientID}_${defaultIndex}`;
        } else {
            // fallback for shortcodes or widgets
            const type = chat.getAttribute('data-type') || 'shortcode';
            conversationKey = `wpaicg_chat_history_${type}_${clientID}_${defaultIndex}`;
        }
        localStorage.setItem(conversationKey, JSON.stringify([]));
        localStorage.setItem(activeConversationKey, conversationKey);
    }

    // Load existing history
    let history = JSON.parse(localStorage.getItem(conversationKey) || '[]');

    // Memory limit from data attribute or default
    const memoryLimit = parseInt(chat.getAttribute('data-memory-limit'), 10) || 100;
    // Format the message with "Human: " or "AI: "
    const formattedMessage = (sender === 'user' ? "Human: " : "AI: ") + (message.trim() || '');
    const numericId = typeof uniqueId === 'string' ? parseInt(uniqueId, 10) : uniqueId;

    // Push new message to array
    history.push({
        id: numericId || '',
        text: formattedMessage
    });

    // Trim the array to memoryLimit
    if (history.length > memoryLimit) {
        history = history.slice(-memoryLimit);
    }

    // Save updated history
    localStorage.setItem(conversationKey, JSON.stringify(history));

    // Update the 'last updated' timestamp in a separate localStorage key
    // This will be used for sorting conversations in descending order
    localStorage.setItem(`${conversationKey}_timestamp`, Date.now().toString());

    // Refresh the conversation list so the user sees updated titles/timestamps
    loadConversationList();
}

function loadChatInterface(containerSelector, type, clientId) {
    var chatContainers = document.querySelectorAll(containerSelector);

    chatContainers.forEach(chatContainer => {

        // Read autoload chat conversations setting, default to '0' if not set
        var autoloadConversations = chatContainer.getAttribute('data-autoload_chat_conversations');
        if (autoloadConversations === null) {
            autoloadConversations = '1';  // Default value if attribute does not exist
        }

        // Fetch the bot ID based on the type
        var botId = chatContainer.getAttribute('data-bot-id') || '0';

        // Determine the history key based on whether it's a custom bot or not
        var historyKey = botId !== '0'
            ? `wpaicg_chat_history_custom_bot_${botId}_${clientId}`
            : `wpaicg_chat_history_${type}_${clientId}`;

        if (autoloadConversations === '0') {
            // Retrieve and display the chat history
            var chatHistory = localStorage.getItem(historyKey);
            if (chatHistory) {
                chatHistory = JSON.parse(chatHistory);

                // Convert old format messages to new format
                chatHistory = chatHistory.map(message => {
                    if (typeof message === 'string') {
                        return {
                            id: '', // Old messages won't have an ID, so leave it empty
                            text: message
                        };
                    }
                    return message; // Leave new format messages as is
                });

                // Save the updated history back to localStorage in the new format
                localStorage.setItem(historyKey, JSON.stringify(chatHistory));

                var chatBox = chatContainer.querySelector('.wpaicg-chatbox-messages, .wpaicg-chat-shortcode-messages'); // Generalized selector
                if (!chatBox) {
                    console.error(`No chat box found within the ${type} container.`);
                    return;
                }
                chatBox.innerHTML = '';  // Clears the chat box
                chatHistory.forEach(message => {
                    reconstructMessage(chatBox, message, chatContainer);
                });
                chatBox.appendChild(document.createElement('br'));
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        chatBox.scrollTop = chatBox.scrollHeight; // Scrolls to the bottom
                    });
                });
                hideConversationStarter(chatContainer);

            } else {
                showConversationStarters(chatContainer);
            }
        } else {
            showConversationStarters(chatContainer);
        }
    });
}

function reconstructMessage(chatBox, message, chatContainer) {
    var messageElement = document.createElement('li');

    // Check if message is an object and has a 'text' property
    var messageText = typeof message === 'object' && message.text ? message.text : message;

    // Determine if the message is from the user or AI
    var isUserMessage = messageText.startsWith('Human:');
    var isWidget = chatContainer.classList.contains('wpaicg-chatbox');

    // Apply the correct class based on message source and container type
    if (isUserMessage) {
        messageElement.className = isWidget ? 'wpaicg-chat-user-message' : 'wpaicg-user-message';
    } else {
        messageElement.className = isWidget ? 'wpaicg-chat-ai-message' : 'wpaicg-ai-message';
    }

    var formattedMessage = messageText.replace('Human:', '').replace('AI:', '');
    formattedMessage = marked.parse(formattedMessage);
    var userBgColor = chatContainer.getAttribute('data-user-bg-color');
    var aiBgColor = chatContainer.getAttribute('data-ai-bg-color');
    var fontSize = chatContainer.getAttribute('data-fontsize');
    var fontColor = chatContainer.getAttribute('data-color');

    messageElement.style.backgroundColor = isUserMessage ? userBgColor : aiBgColor;
    messageElement.style.color = fontColor;
    messageElement.style.fontSize = fontSize;
    messageElement.innerHTML = `<span class="wpaicg-chat-message">${formattedMessage}</span>`;

    chatBox.appendChild(messageElement);
}
function hideConversationStarter(chatContainer) {
    var starters = chatContainer.querySelectorAll('.wpaicg-conversation-starters');
    starters.forEach(starter => starter.style.display = 'none');
}

function showConversationStarters(chatContainer) {
    const startersContainer = chatContainer.querySelector('.wpaicg-conversation-starters');
    if (startersContainer) {  // Check if the container exists
        startersContainer.style.visibility = 'visible'; // Make the container visible if it exists
        const starters = startersContainer.querySelectorAll('.wpaicg-conversation-starter');
        starters.forEach((starter, index) => {
            setTimeout(() => {
                starter.style.opacity = "1";
                starter.style.transform = "translateY(0)";
            }, index * 150); // Staggered appearance
        });
    }
}
/**
 * Adjusts the size of all shortcode-based chat windows (.wpaicg-chat-shortcode),
 * including handling fullscreen mode and mobile layout. Now adds an extra offset
 * on mobile when there is *no* footer to prevent the text box from being cut off.
 */
function wpaicgChatShortcodeSize() {
    var wpaicgWindowWidth = window.innerWidth;
    var wpaicgWindowHeight = window.innerHeight;
    var chatShortcodes = document.getElementsByClassName('wpaicg-chat-shortcode');

    if (chatShortcodes !== null && chatShortcodes.length) {
        for (var i = 0; i < chatShortcodes.length; i++) {
            var chatShortcode = chatShortcodes[i];
            var parentChat = chatShortcode.parentElement;
            var parentWidth = parentChat.offsetWidth;
            var chatWidth = chatShortcode.getAttribute('data-width');
            var chatHeight = chatShortcode.getAttribute('data-height');
            var chatFooter = chatShortcode.getAttribute('data-footer') === 'true';
            var chatBar = chatShortcode.getAttribute('data-has-bar') === 'true';
            var chatRounded = parseFloat(chatShortcode.getAttribute('data-chat_rounded'));
            var textRounded = parseFloat(chatShortcode.getAttribute('data-text_rounded'));
            var textHeight = parseFloat(chatShortcode.getAttribute('data-text_height'));
            var textInput = chatShortcode.getElementsByClassName('wpaicg-chat-shortcode-typing')[0];

            // Set text area dimensions
            textInput.style.height = textHeight + 'px';
            textInput.style.borderRadius = textRounded + 'px';

            // Round the chat container
            chatShortcode.style.borderRadius = chatRounded + 'px';
            chatShortcode.style.overflow = 'hidden';

            // Fallback dimension if not supplied
            chatWidth = chatWidth !== null ? chatWidth : '350';
            chatHeight = chatHeight !== null ? chatHeight : '400';

            // FULLSCREEN MODE
            if (chatShortcode.classList.contains('wpaicg-fullscreened')) {
                // Occupy the full window
                parentWidth = wpaicgWindowWidth;

                // On very narrow mobile, subtract a bit more from height
                if (wpaicgWindowWidth < 480) {
                    chatWidth = wpaicgWindowWidth;
                    chatHeight = wpaicgWindowHeight - 40; 
                } else {
                    chatWidth = resolveDimension(chatWidth, parentWidth);
                    chatHeight = resolveDimension(chatHeight, wpaicgWindowHeight);
                }

                chatShortcode.style.width = chatWidth + 'px';
                chatShortcode.style.maxWidth = chatWidth + 'px';
                chatShortcode.style.height = chatHeight + 'px';
                chatShortcode.style.maxHeight = chatHeight + 'px';
                chatShortcode.style.marginTop = 0;

                // Deduce space for header(s), action bar, etc.
                var deduceHeight = 69; // Base
                if (chatFooter) {
                    deduceHeight += 60; // Footer
                }
                if (chatBar) {
                    deduceHeight += 30; // Action bar
                }
                deduceHeight += 20;   // Additional spacing

                // On mobile, add some extra
                if (wpaicgWindowWidth < 480) {
                    deduceHeight += 20;
                }

                // NEW: If no footer and on mobile, add offset so text box is visible
                if (!chatFooter && wpaicgWindowWidth < 480) {
                    deduceHeight += 60;
                }

                var chatMessages = chatShortcode.getElementsByClassName('wpaicg-chat-shortcode-messages')[0];
                chatMessages.style.height = (chatHeight - deduceHeight - textHeight) + 'px';

                // Force scroll to bottom so new messages or input are visible
                requestAnimationFrame(() => {
                    requestAnimationFrame(() => {
                        chatMessages.scrollTop = chatMessages.scrollHeight;
                    });
                });
            }
            // NON-FULLSCREEN MODE
            else {
                // Resolve percentage or px for chatWidth
                if (chatWidth.indexOf('%') < 0) {
                    if (chatWidth.indexOf('px') < 0) {
                        chatWidth = parseFloat(chatWidth);
                    } else {
                        chatWidth = parseFloat(chatWidth.replace(/px/g, ''));
                    }
                } else {
                    chatWidth = parseFloat(chatWidth.replace(/%/g, ''));
                    if (chatWidth < 100) {
                        chatWidth = chatWidth * parentWidth / 100;
                    } else {
                        chatWidth = '';
                    }
                }

                // Resolve percentage or px for chatHeight
                if (chatHeight.indexOf('%') < 0) {
                    if (chatHeight.indexOf('px') < 0) {
                        chatHeight = parseFloat(chatHeight);
                    } else {
                        chatHeight = parseFloat(chatHeight.replace(/px/g, ''));
                    }
                } else {
                    chatHeight = parseFloat(chatHeight.replace(/%/g, ''));
                    chatHeight = chatHeight * wpaicgWindowHeight / 100;
                }

                // Apply final computed width & height
                if (chatWidth !== '') {
                    chatShortcode.style.width = chatWidth + 'px';
                    chatShortcode.style.maxWidth = chatWidth + 'px';
                } else {
                    chatShortcode.style.width = '';
                    chatShortcode.style.maxWidth = '';
                }

                if (chatShortcode.classList.contains('wpaicg-fullscreened')) {
                    chatShortcode.style.marginTop = 0;
                } else {
                    chatShortcode.style.marginTop = '';
                }

                var chatMessages = chatShortcode.getElementsByClassName('wpaicg-chat-shortcode-messages')[0];
                var deduceHeight = 69; // Base
                if (chatFooter) {
                    deduceHeight += 60; // Footer
                }
                if (chatBar) {
                    deduceHeight += 30; // Action bar
                }
                deduceHeight += 20;   // Additional spacing

                // On mobile, add some extra
                if (wpaicgWindowWidth < 480) {
                    deduceHeight += 20;
                }

                // NEW: If no footer and on mobile, add offset so text box is visible
                if (!chatFooter && wpaicgWindowWidth < 480) {
                    deduceHeight += 60;
                }

                // Subtract the typed text box height
                chatMessages.style.height = (chatHeight - deduceHeight - textHeight) + 'px';
            }
        }
    }
}

/**
 * Adjusts the size of widget-based chat windows (.wpaicg_chat_widget_content
 * containing .wpaicg-chatbox). Also adds an extra offset on mobile when there's
 * no footer so the text box remains visible in fullscreen.
 */
function wpaicgChatBoxSize() {
    var wpaicgWindowWidth = window.innerWidth;
    var wpaicgWindowHeight = window.innerHeight;
    var chatWidgets = document.getElementsByClassName('wpaicg_chat_widget_content');

    if (chatWidgets.length) {
        for (var i = 0; i < chatWidgets.length; i++) {
            var chatWidget = chatWidgets[i];
            var chatbox = chatWidget.getElementsByClassName('wpaicg-chatbox')[0];
            var chatWidth = chatbox.getAttribute('data-width') || '350';
            var chatHeight = chatbox.getAttribute('data-height') || '400';
            var chatFooter = chatbox.getAttribute('data-footer') === 'true';
            var chatboxBar = chatbox.getElementsByClassName('wpaicg-chatbox-action-bar');
            var textHeight = parseFloat(chatbox.getAttribute('data-text_height'));

            // If the widget container is in fullscreen mode, override dimensions
            if (chatWidget.classList.contains('wpaicg-fullscreened')) {
                chatWidth = wpaicgWindowWidth;
                // On small mobile, subtract more so text box is not hidden
                if (wpaicgWindowWidth < 480) {
                    chatHeight = wpaicgWindowHeight - 40;
                } else {
                    chatHeight = wpaicgWindowHeight - 20;
                }
            } else {
                chatWidth = resolveDimension(chatWidth, wpaicgWindowWidth);
                chatHeight = resolveDimension(chatHeight, wpaicgWindowHeight);
            }

            chatbox.style.width = chatWidth + 'px';
            chatbox.style.height = chatHeight + 'px';
            chatWidget.style.width = chatWidth + 'px';
            chatWidget.style.height = chatHeight + 'px';

            var actionBarHeight = chatboxBar.length ? 40 : 0;
            var footerHeight = chatFooter ? 60 : 0;
            var extraGap = (wpaicgWindowWidth < 480) ? 40 : 20;

            // NEW: If no footer on mobile, still add 60px so text box isn't hidden
            if (!chatFooter && wpaicgWindowWidth < 480) {
                footerHeight = 60;
            }

            var contentHeight = chatHeight - textHeight - actionBarHeight - footerHeight - extraGap;
            var messagesHeight = contentHeight - 20;

            var chatboxContent = chatWidget.getElementsByClassName('wpaicg-chatbox-content')[0];
            var chatboxMessages = chatWidget.getElementsByClassName('wpaicg-chatbox-messages')[0];

            chatboxContent.style.height = contentHeight + 'px';
            chatboxMessages.style.height = messagesHeight + 'px';

            // Ensure we are scrolled to bottom for existing messages
            chatboxMessages.scrollTop = chatboxMessages.scrollHeight;
        }
    }
}
function resolveDimension(value, totalSize) {
    if (value.includes('%')) {
        return parseFloat(value) / 100 * totalSize;
    } else if (value.includes('px')) {
        return parseFloat(value.replace('px', ''));
    }
    return parseFloat(value); // Default to parsing the value as pixels if no units are specified
}

function wpaicgChatInit() {
    let wpaicgMicIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M176 0C123 0 80 43 80 96V256c0 53 43 96 96 96s96-43 96-96V96c0-53-43-96-96-96zM48 216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 89.1 66.2 162.7 152 174.4V464H104c-13.3 0-24 10.7-24 24s10.7 24 24 24h72 72c13.3 0 24-10.7 24-24s-10.7-24-24-24H200V430.4c85.8-11.7 152-85.3 152-174.4V216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 70.7-57.3 128-128 128s-128-57.3-128-128V216z"/></svg>';
    let wpaicgStopIcon = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 512 512"><path d="M0 256a256 256 0 1 1 512 0A256 256 0 1 1 0 256zm256-96a96 96 0 1 1 0 192 96 96 0 1 1 0-192zm0 224a128 128 0 1 0 0-256 128 128 0 1 0 0 256zm0-96a32 32 0 1 0 0-64 32 32 0 1 0 0 64z"/></svg>';
    var wpaicgChatStream;
    var wpaicgChatRec;
    var wpaicgInput;
    var wpaicgChatAudioContext = window.AudioContext || window['webkitAudioContext'];
    var wpaicgaudioContext;
    var wpaicgMicBtns = document.querySelectorAll('.wpaicg-mic-icon');
    var wpaicgChatTyping = document.querySelectorAll('.wpaicg-chatbox-typing');
    var wpaicgShortcodeTyping = document.querySelectorAll('.wpaicg-chat-shortcode-typing');
    var wpaicgChatSend = document.querySelectorAll('.wpaicg-chatbox-send');
    var wpaicgShortcodeSend = document.querySelectorAll('.wpaicg-chat-shortcode-send');
    var wpaicgChatFullScreens = document.getElementsByClassName('wpaicg-chatbox-fullscreen');
    var wpaicgChatCloseButtons = document.getElementsByClassName('wpaicg-chatbox-close-btn');
    var wpaicgChatDownloadButtons = document.getElementsByClassName('wpaicg-chatbox-download-btn');
    var wpaicg_chat_widget_toggles = document.getElementsByClassName('wpaicg_toggle');
    var wpaicg_chat_widgets = document.getElementsByClassName('wpaicg_chat_widget');
    var imageInputThumbnail = null; // Variable to store the image thumbnail
    function setupConversationStarters() {
        const starters = document.querySelectorAll('.wpaicg-conversation-starter');
        starters.forEach(starter => {
            starter.addEventListener('click', function () {
                const messageText = starter.innerText || starter.textContent;
                const chatContainer = starter.closest('.wpaicg-chat-shortcode') || starter.closest('.wpaicg-chatbox');
                const type = chatContainer.classList.contains('wpaicg-chat-shortcode') ? 'shortcode' : 'widget';
                const typingInput = type === 'shortcode' ? chatContainer.querySelector('.wpaicg-chat-shortcode-typing') : chatContainer.querySelector('.wpaicg-chatbox-typing');

                typingInput.value = messageText;
                wpaicgSendChatMessage(chatContainer, typingInput, type);

                // Hide all starters
                starters.forEach(starter => {
                    starter.style.display = 'none';
                });
            });
        });
    }

    function maybeShowLeadForm(chat, chatId) {
        // Helper function to interpret truthy values
        function isTruthy(value) {
            if (value === null || value === undefined) return false;
            return value === '1' || value.toLowerCase() === 'true';
        }

        // Get the necessary data attributes
        let leadCollectionEnabled = isTruthy(chat.getAttribute('data-lead-collection'));
        if (!leadCollectionEnabled) {
            return;
        }

        // Check if form has already been shown
        let leadFormShown = localStorage.getItem('wpaicg_lead_form_shown');
        if (leadFormShown === '1') {
            return;
        }

        // Get the enable fields
        let enableLeadName = isTruthy(chat.getAttribute('data-enable-lead-name'));
        let enableLeadEmail = isTruthy(chat.getAttribute('data-enable-lead-email'));
        let enableLeadPhone = isTruthy(chat.getAttribute('data-enable-lead-phone'));

        // Check if at least one field is enabled
        if (!(enableLeadName || enableLeadEmail || enableLeadPhone)) {
            return;
        }

        // Get other data attributes
        let leadTitle = chat.getAttribute('data-lead-title') || 'Contact Information';
        let leadNameLabel = chat.getAttribute('data-lead-name') || 'Name';
        let leadEmailLabel = chat.getAttribute('data-lead-email') || 'Email';
        let leadPhoneLabel = chat.getAttribute('data-lead-phone') || 'Phone';
        let wpaicg_nonce = chat.getAttribute('data-nonce');
        let aiBg = chat.getAttribute('data-ai-bg-color');
        let fontSize = chat.getAttribute('data-fontsize');
        let fontColor = chat.getAttribute('data-color');
        let text_field_bgcolor = chat.getAttribute('data-bg_text_field');
        let text_field_font_color = chat.getAttribute('data-bg_text_field_font_color');
        let text_field_border_color = chat.getAttribute('data-bg_text_field_border_color');

        // Now, construct the form in JavaScript
        // Define the form HTML within a chat message
        let formHtml = `
        <li class="wpaicg-lead-form-message" style="background-color:${aiBg};font-size:${fontSize}px;color:${fontColor}">
            <div class="wpaicg-lead-form-container">
                <button class="wpaicg-lead-form-close" style="float:right;">&times;</button>
                <h2>${leadTitle}</h2>
                <form>
        `;

        if (enableLeadName) {
            formHtml += `
                <div class="wpaicg-lead-form-field">
                    <label>${leadNameLabel}</label>
                    <input type="text" name="lead_name"/>
                </div>
            `;
        }

        if (enableLeadEmail) {
            formHtml += `
                <div class="wpaicg-lead-form-field">
                    <label>${leadEmailLabel}</label>
                    <input type="email" name="lead_email" />
                </div>
            `;
        }

        if (enableLeadPhone) {
            formHtml += `
                <div class="wpaicg-lead-form-field">
                    <label>${leadPhoneLabel}</label>
                    <input type="tel" name="lead_phone"/>
                </div>
            `;
        }

        // Add error message container
        formHtml += `
                <div class="wpaicg-lead-form-error" style="color: red; display: none;"></div>
        `;

        formHtml += `
                    <div class="svg-submit-button-container">
                        <button type="submit" class="svg-submit-button">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill=${fontColor} aria-hidden="true" width="24" height="24"><path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z"></path></svg>
                        </button>
                    </div>
                </form>
            </div>
        </li>
        `;

        // Append the form after the last AI message
        let messagesList = chat.querySelector('.wpaicg-chatbox-messages') || chat.querySelector('.wpaicg-chat-shortcode-messages');
        if (messagesList) {
            messagesList.insertAdjacentHTML('beforeend', formHtml);
        }

        // Add CSS styles
        let styles = `
        .wpaicg-lead-form-message {
            list-style: none;
            padding: 10px;
            margin-bottom: 10px;
            position: relative;
        }
    
        .wpaicg-lead-form-container {
            display: block;
        }
    
        .wpaicg-lead-form-container h2 {
            margin-top: 0;
            font-size: 1.2em;
            margin-bottom: 10px;
            color: ${fontColor};
            padding-right: 40px;
        }
    
        .wpaicg-lead-form-close {
            position: absolute;
            top: 10px;
            right: 10px;
            background: none;
            border: none;
            font-size: 18px;
            cursor: pointer;
            color: ${fontColor};
        }
    
        .wpaicg-lead-form-field {
            margin-bottom: 15px;
        }
    
        .wpaicg-lead-form-field label {
            display: block;
            margin-bottom: 5px;
        }
    
        .wpaicg-lead-form-field input {
            color: ${text_field_font_color};
            background-color: ${text_field_bgcolor};
            width: 100%;
            padding: 8px;
            box-sizing: border-box;
            border: 1px solid ${text_field_border_color};
        }
    
        .svg-submit-button {
            background-color: ${aiBg};
            border: none;
            cursor: pointer;
            padding: 10px;
            outline: none;
        }

        .svg-submit-button svg {
            fill: ${fontColor}; /* Dynamic color */
            transition: fill 0.3s ease;
        }

        /* Spinner inside button */
        .wpaicg-lead-spinner {
            display: inline-flex;
            justify-content: center;
            align-items: center;
        }

        .wpaicg-lead-spinner .dot {
            font-size: 16px;
            color: ${fontColor};
            animation: jump 1s infinite;
            margin: 0 2px;
        }

        /* Align submit button to the right */
        .svg-submit-button-container {
            text-align: right; /* This aligns the button to the right */
        }

        @keyframes jump {
            0%, 100% {
                transform: translateY(0);
            }
            50% {
                transform: translateY(-6px);
            }
        }

        `;

        // Create a style element and append to the head
        let styleSheet = document.createElement("style");
        styleSheet.innerText = styles;
        document.head.appendChild(styleSheet);

        // Add event listeners for form submission and close button
        let formMessage = messagesList.querySelector('.wpaicg-lead-form-message');
        let closeButton = formMessage.querySelector('.wpaicg-lead-form-close');
        let form = formMessage.querySelector('form');
        let errorDiv = form.querySelector('.wpaicg-lead-form-error');

        closeButton.addEventListener('click', function () {
            // Hide the form
            formMessage.remove();
            // Set that form has been shown
            localStorage.setItem('wpaicg_lead_form_shown', '1');
        });

        form.addEventListener('submit', function (event) {
            event.preventDefault();

            // Validate that at least one field is filled
            let nameInput = form.querySelector('input[name="lead_name"]');
            let emailInput = form.querySelector('input[name="lead_email"]');
            let phoneInput = form.querySelector('input[name="lead_phone"]');

            let nameValue = nameInput ? nameInput.value.trim() : '';
            let emailValue = emailInput ? emailInput.value.trim() : '';
            let phoneValue = phoneInput ? phoneInput.value.trim() : '';

            // If all fields are empty, display an error message and stop submission
            if (!nameValue && !emailValue && !phoneValue) {
                errorDiv.textContent = 'Please fill in at least one field.';
                errorDiv.style.display = 'block';
                return; // Stop form submission
            } else {
                errorDiv.textContent = '';
                errorDiv.style.display = 'none';
            }

            // Get the submit button
            let submitButton = form.querySelector('.svg-submit-button');

            // Replace the button content with the spinner (jumping dots)
            submitButton.innerHTML = `
                <div class="wpaicg-lead-spinner">
                    <span class="dot">•</span>
                    <span class="dot">•</span>
                    <span class="dot">•</span>
                </div>
            `;
            submitButton.disabled = true; // Disable the button while submitting

            // Collect the data
            let formData = new FormData(form);
            formData.append('action', 'wpaicg_submit_lead');
            formData.append('_wpnonce', wpaicg_nonce);
            // Include chatId
            formData.append('chatId', chatId);

            // Send the data via AJAX
            fetch(wpaicgParams.ajax_url, {
                method: 'POST',
                body: formData
            }).then(response => response.json())
                .then(data => {
                    formMessage.remove();

                    // Mark form as shown
                    localStorage.setItem('wpaicg_lead_form_shown', '1');
                }).catch(error => {
                    console.error('Error submitting lead form:', error);
                    formMessage.remove();
                    localStorage.setItem('wpaicg_lead_form_shown', '1');
                }).finally(() => {
                    // Restore original SVG icon in the button and re-enable it
                    submitButton.innerHTML = `
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" fill="${fontColor}" class="bi bi-send" viewBox="0 0 16 16">
                        <path d="M15.5 0.5a.5.5 0 0 0-.854-.353L.646 14.646a.5.5 0 0 0 .708.708L15.5 0.854A.5.5 0 0 0 15.5 0.5z"/>
                        <path d="M6.646 15.646l8-8a.5.5 0 0 0-.708-.708l-8 8a.5.5 0 1 0 .708.708z"/>
                        <path d="M4.5 3.5a.5.5 0 0 0 0 1h6a.5.5 0 0 0 0-1h-6z"/>
                    </svg>
                `;
                    submitButton.disabled = false;
                });
        });
    }

    setupConversationStarters();

    var wpaicgUserAudioEnabled = {}; // Object to track audio settings per chat instance

    // Function to set up event listeners on audio buttons
    function setupAudioButtons() {
        var wpaicgAudioButtons = document.querySelectorAll('.wpaicg-chatbox-audio-btn');
        wpaicgAudioButtons.forEach(button => {
            var chatContainer = button.closest('.wpaicg-chat-shortcode, .wpaicg-chatbox');
            var botId = chatContainer.getAttribute('data-bot-id') || '0';
            var chatType = chatContainer.getAttribute('data-type') || 'shortcode';

            // Create a unique key for each bot based on bot-id and chat-type
            var audioKey = `audio_${chatType}_${botId}`;

            // Get muted state from data attribute (1 = muted, 0 or absent = unmuted)
            var voiceMutedByDefault = chatContainer.getAttribute('data-voice-muted-by-default') === '1';

            // Initialize the audio button state based on the default mode
            if (voiceMutedByDefault) {
                // Audio is muted by default
                wpaicgUserAudioEnabled[audioKey] = false;
                button.classList.remove('wpaicg-audio-enabled');
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M301.1 34.8C312.6 40 320 51.4 320 64l0 384c0 12.6-7.4 24-18.9 29.2s-25 3.1-34.4-5.3L131.8 352 64 352c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l67.8 0L266.7 40.1c9.4-8.4 22.9-10.4 34.4-5.3zM425 167l55 55 55-55c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-55 55 55 55c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-55-55-55 55c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l55-55-55-55c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0z"/></svg>'; // Muted icon SVG
            } else {
                // Audio is not muted by default
                wpaicgUserAudioEnabled[audioKey] = true;
                button.classList.add('wpaicg-audio-enabled');
                button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M533.6 32.5C598.5 85.2 640 165.8 640 256s-41.5 170.7-106.4 223.5c-10.3 8.4-25.4 6.8-33.8-3.5s-6.8-25.4 3.5-33.8C557.5 398.2 592 331.2 592 256s-34.5-142.2-88.7-186.3c-10.3-8.4-11.8-23.5-3.5-33.8s23.5-11.8 33.8-3.5zM473.1 107c43.2 35.2 70.9 88.9 70.9 149s-27.7 113.8-70.9 149c-10.3 8.4-25.4 6.8-33.8-3.5s-6.8-25.4 3.5-33.8C475.3 341.3 496 301.1 496 256s-20.7-85.3-53.2-111.8c-10.3-8.4-11.8-23.5-3.5-33.8s23.5-11.8 33.8-3.5zm-60.5 74.5C434.1 199.1 448 225.9 448 256s-13.9 56.9-35.4 74.5c-10.3 8.4-25.4 6.8-33.8-3.5s-6.8-25.4 3.5-33.8C393.1 284.4 400 271 400 256s-6.9-28.4-17.7-37.3c-10.3-8.4-11.8-23.5-3.5-33.8s23.5-11.8 33.8-3.5zM301.1 34.8C312.6 40 320 51.4 320 64l0 384c0 12.6-7.4 24-18.9 29.2s-25 3.1-34.4-5.3L131.8 352 64 352c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l67.8 0L266.7 40.1c9.4-8.4 22.9-10.4 34.4-5.3z"/></svg>'; // Unmuted icon SVG
            }

            // Set up click listener to toggle audio state
            button.addEventListener('click', function () {
                wpaicgUserAudioEnabled[audioKey] = !wpaicgUserAudioEnabled[audioKey];
                if (wpaicgUserAudioEnabled[audioKey]) {
                    // Audio is now enabled
                    button.classList.add('wpaicg-audio-enabled');
                    button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 512"><path d="M533.6 32.5C598.5 85.2 640 165.8 640 256s-41.5 170.7-106.4 223.5c-10.3 8.4-25.4 6.8-33.8-3.5s-6.8-25.4 3.5-33.8C557.5 398.2 592 331.2 592 256s-34.5-142.2-88.7-186.3c-10.3-8.4-11.8-23.5-3.5-33.8s23.5-11.8 33.8-3.5zM473.1 107c43.2 35.2 70.9 88.9 70.9 149s-27.7 113.8-70.9 149c-10.3 8.4-25.4 6.8-33.8-3.5s-6.8-25.4 3.5-33.8C475.3 341.3 496 301.1 496 256s-20.7-85.3-53.2-111.8c-10.3-8.4-11.8-23.5-3.5-33.8s23.5-11.8 33.8-3.5zm-60.5 74.5C434.1 199.1 448 225.9 448 256s-13.9 56.9-35.4 74.5c-10.3 8.4-25.4 6.8-33.8-3.5s-6.8-25.4 3.5-33.8C393.1 284.4 400 271 400 256s-6.9-28.4-17.7-37.3c-10.3-8.4-11.8-23.5-3.5-33.8s23.5-11.8 33.8-3.5zM301.1 34.8C312.6 40 320 51.4 320 64l0 384c0 12.6-7.4 24-18.9 29.2s-25 3.1-34.4-5.3L131.8 352 64 352c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l67.8 0L266.7 40.1c9.4-8.4 22.9-10.4 34.4-5.3z"/></svg>'; // Unmuted icon SVG
                } else {
                    // Audio is now disabled
                    button.classList.remove('wpaicg-audio-enabled');
                    button.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M301.1 34.8C312.6 40 320 51.4 320 64l0 384c0 12.6-7.4 24-18.9 29.2s-25 3.1-34.4-5.3L131.8 352 64 352c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l67.8 0L266.7 40.1c9.4-8.4 22.9-10.4 34.4-5.3zM425 167l55 55 55-55c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-55 55 55 55c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-55-55-55 55c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l55-55-55-55c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0z"/></svg>'; // Muted icon SVG
                }
            });
        });
    }

    // Call the setup function
    setupAudioButtons();

    var imageIcon = document.querySelector('.wpaicg-img-icon');
    var spinner = document.querySelector('.wpaicg-img-spinner');
    var thumbnailPlaceholder = document.querySelector('.wpaicg-thumbnail-placeholder');

    if (imageIcon) {
        imageIcon.addEventListener('click', function () {
            var imageInput = document.getElementById('imageUpload');
            imageInput.click();
        });
    }

    var imageInput = document.getElementById('imageUpload');
    if (imageInput) {
        imageInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                var file = this.files[0];  // Store the file reference here

                // Show the spinner and hide the image icon
                imageIcon.style.display = 'none';
                spinner.style.display = 'inline-block';

                imageIcon.title = file.name; // Optional: show image name on hover

                // Hide the spinner and show the image icon and thumbnail after a delay
                setTimeout(function () {
                    spinner.style.display = 'none';
                    imageIcon.style.display = 'inline-block'; // Re-display image icon

                    // Now set the thumbnail image using the stored file reference
                    thumbnailPlaceholder.style.backgroundImage = `url(${URL.createObjectURL(file)})`;
                    thumbnailPlaceholder.style.backgroundSize = 'cover';
                    thumbnailPlaceholder.style.backgroundPosition = 'center';
                    thumbnailPlaceholder.style.backgroundRepeat = 'no-repeat';
                    thumbnailPlaceholder.style.display = 'inline-block'; // Display thumbnail
                }, 2000);
            }
        });
    }

    // Function to set up event listeners on all clear chat buttons
    function setupClearChatButtons() {
        var wpaicgChatClearButtons = document.querySelectorAll('.wpaicg-chatbox-clear-btn');
        wpaicgChatClearButtons.forEach(button => {
            button.addEventListener('click', function () {
                var chatContainer = button.closest('[data-bot-id]'); // Finds the nearest parent with 'data-bot-id'
                if (chatContainer) {
                    var botId = chatContainer.getAttribute('data-bot-id') || '0';
                    var clientId = localStorage.getItem('wpaicg_chat_client_id');
                    clearChatHistory(botId, clientId, chatContainer);
                }
            });
        });
    }

    // Function to clear the chat history from local storage and the display
    function clearChatHistory(botId, clientId, chatContainer) {
        var isCustomBot = botId !== '0';
        var type = chatContainer.classList.contains('wpaicg-chat-shortcode') ? 'shortcode' : 'widget'; // Determine the type based on class
        var historyKey = isCustomBot
            ? `wpaicg_chat_history_custom_bot_${botId}_${clientId}`
            : `wpaicg_chat_history_${type}_${clientId}`; // Adjust history key based on type

        // Remove the item from local storage
        localStorage.removeItem(historyKey);

        // Clear the chat display
        var chatBoxSelector = '.wpaicg-chatbox-messages, .wpaicg-chat-shortcode-messages'; // Generalized selector for both types
        var chatBox = chatContainer.querySelector(chatBoxSelector);
        if (chatBox) {
            chatBox.innerHTML = ''; // Clear the chat box visually
        }

        // delete wpaicg_lead_form_shown if exists
        localStorage.removeItem('wpaicg_lead_form_shown');

        // Check if wpaicg_thread_list exists in local storage
        var threadList = localStorage.getItem('wpaicg_thread_list');
        if (threadList) {
            try {
                var threadListObj = JSON.parse(threadList);

                // Construct the key for the thread to be deleted
                var threadKey = isCustomBot
                    ? `custom_bot_${botId}_${clientId}`
                    : `${type}_${clientId}`;

                // Delete the relevant record from threadListObj
                if (threadKey in threadListObj) {
                    delete threadListObj[threadKey];

                    // Update the local storage with the modified thread list
                    localStorage.setItem('wpaicg_thread_list', JSON.stringify(threadListObj));
                }
            } catch (error) {
                console.error('Error parsing wpaicg_thread_list:', error);
            }
        }
    }

    // Call this function once your DOM is fully loaded or at the end of your script
    setupClearChatButtons();


    if (wpaicg_chat_widget_toggles !== null && wpaicg_chat_widget_toggles.length) {
        for (var i = 0; i < wpaicg_chat_widget_toggles.length; i++) {
            var wpaicg_chat_widget_toggle = wpaicg_chat_widget_toggles[i];
            var wpaicg_chat_widget = wpaicg_chat_widget_toggle.closest('.wpaicg_chat_widget');
            wpaicg_chat_widget_toggle.addEventListener('click', function (e) {
                e.preventDefault();
                wpaicg_chat_widget_toggle = e.currentTarget;
                if (wpaicg_chat_widget_toggle.classList.contains('wpaicg_widget_open')) {
                    wpaicg_chat_widget_toggle.classList.remove('wpaicg_widget_open');
                    wpaicg_chat_widget.classList.remove('wpaicg_widget_open');
                }
                else {
                    wpaicg_chat_widget.classList.add('wpaicg_widget_open');
                    wpaicg_chat_widget_toggle.classList.add('wpaicg_widget_open');
                }
            });
        }
    }
    if (wpaicgChatDownloadButtons.length) {
        for (var i = 0; i < wpaicgChatDownloadButtons.length; i++) {
            var wpaicgChatDownloadButton = wpaicgChatDownloadButtons[i];
            wpaicgChatDownloadButton.addEventListener('click', function (e) {
                wpaicgChatDownloadButton = e.currentTarget;
                var type = wpaicgChatDownloadButton.getAttribute('data-type');
                var wpaicgWidgetContent, listMessages;
                if (type === 'shortcode') {
                    wpaicgWidgetContent = wpaicgChatDownloadButton.closest('.wpaicg-chat-shortcode');
                    listMessages = wpaicgWidgetContent.getElementsByClassName('wpaicg-chat-shortcode-messages');
                }
                else {
                    wpaicgWidgetContent = wpaicgChatDownloadButton.closest('.wpaicg_chat_widget_content');
                    listMessages = wpaicgWidgetContent.getElementsByClassName('wpaicg-chatbox-messages');
                }
                if (listMessages.length) {
                    var listMessage = listMessages[0];
                    var messages = [];
                    var chatMessages = listMessage.getElementsByTagName('li');
                    if (chatMessages.length) {
                        for (var i = 0; i < chatMessages.length; i++) {
                            messages.push(chatMessages[i].innerText.replace("\n", ' '));
                        }
                    }
                    var messagesDownload = messages.join("\n");
                    var element = document.createElement('a');
                    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(messagesDownload));
                    element.setAttribute('download', 'chat.txt');

                    element.style.display = 'none';
                    document.body.appendChild(element);

                    element.click();

                    document.body.removeChild(element);
                }
            })
        }
    }
    if (wpaicgChatCloseButtons.length) {
        for (var i = 0; i < wpaicgChatCloseButtons.length; i++) {
            var wpaicgChatCloseButton = wpaicgChatCloseButtons[i];
            wpaicgChatCloseButton.addEventListener('click', function (e) {
                wpaicgChatCloseButton = e.currentTarget;
                var wpaicgWidgetContent = wpaicgChatCloseButton.closest('.wpaicg_chat_widget_content');
                var chatbox = wpaicgWidgetContent.closest('.wpaicg_chat_widget');
                if (wpaicgWidgetContent.classList.contains('wpaicg-fullscreened')) {
                    var fullScreenBtn = wpaicgWidgetContent.getElementsByClassName('wpaicg-chatbox-fullscreen')[0];
                    wpaicgFullScreen(fullScreenBtn);
                }
                chatbox.getElementsByClassName('wpaicg_toggle')[0].click();

            })
        }
    }

    /**
     * Toggle fullscreen mode for either a shortcode chat or a widget chat.
     * Ensures that upon restore, the prior sizing/layout is fully restored,
     * including footer, text box, etc.
     *
     * @param {HTMLElement} btn - The clicked fullscreen button.
     */
    function wpaicgFullScreen(btn) {
        const type = btn.getAttribute('data-type');
        const isExitingFullscreen = btn.classList.contains('wpaicg-fullscreen-box');
    
        if (type === 'shortcode') {
            const wpaicgChatShortcode = btn.closest('.wpaicg-chat-shortcode');
            if (!wpaicgChatShortcode) return;
    
            // Exiting fullscreen
            if (isExitingFullscreen) {
                btn.classList.remove('wpaicg-fullscreen-box');
                wpaicgChatShortcode.classList.remove('wpaicg-fullscreened');
    
                // Restore old inline style
                const oldInlineStyle = wpaicgChatShortcode.getAttribute('data-old-inline-style') || '';
                wpaicgChatShortcode.setAttribute('style', oldInlineStyle);
                wpaicgChatShortcode.removeAttribute('data-old-inline-style');
    
                // Restore old data-width / data-height
                const oldDataWidth = wpaicgChatShortcode.getAttribute('data-old-width');
                const oldDataHeight = wpaicgChatShortcode.getAttribute('data-old-height');
                if (oldDataWidth !== null) {
                    wpaicgChatShortcode.setAttribute('data-width', oldDataWidth);
                    wpaicgChatShortcode.removeAttribute('data-old-width');
                }
                if (oldDataHeight !== null) {
                    wpaicgChatShortcode.setAttribute('data-height', oldDataHeight);
                    wpaicgChatShortcode.removeAttribute('data-old-height');
                }
    
                // Rerun sizing
                wpaicgChatShortcodeSize();
            }
            // Entering fullscreen
            else {
                btn.classList.add('wpaicg-fullscreen-box');
                wpaicgChatShortcode.classList.add('wpaicg-fullscreened');
    
                // Save existing style
                const currentInline = wpaicgChatShortcode.getAttribute('style') || '';
                wpaicgChatShortcode.setAttribute('data-old-inline-style', currentInline);
    
                // Save data-width / data-height
                const currentDataWidth = wpaicgChatShortcode.getAttribute('data-width') || '';
                const currentDataHeight = wpaicgChatShortcode.getAttribute('data-height') || '';
                wpaicgChatShortcode.setAttribute('data-old-width', currentDataWidth);
                wpaicgChatShortcode.setAttribute('data-old-height', currentDataHeight);
    
                // Apply fixed positioning for fullscreen
                wpaicgChatShortcode.style.position = 'fixed';
                wpaicgChatShortcode.style.top = '0';
                wpaicgChatShortcode.style.left = '0';
                wpaicgChatShortcode.style.zIndex = '999999999';
                wpaicgChatShortcode.style.width = '100%';
                // Use slight offset so the text area & footer are fully visible
                wpaicgChatShortcode.style.height = 'calc(100vh - 20px)';
                wpaicgChatShortcode.style.overflowY = 'auto';
    
                // Update data-width / data-height to reflect 100% fill
                wpaicgChatShortcode.setAttribute('data-width', '100%');
                // Use 100% but we do an inline "calc(100vh - 20px)" so that the JS dimensioning can handle subtracting
                wpaicgChatShortcode.setAttribute('data-height', '100%');
    
                // Re-run sizing
                wpaicgChatShortcodeSize();
            }
        }
        else {
            // For a widget
            const wpaicgWidgetContent = btn.closest('.wpaicg_chat_widget_content');
            if (!wpaicgWidgetContent) return;
            const chatbox = wpaicgWidgetContent.querySelector('.wpaicg-chatbox');
            if (!chatbox) return;
    
            // Exiting fullscreen
            if (isExitingFullscreen) {
                btn.classList.remove('wpaicg-fullscreen-box');
                wpaicgWidgetContent.classList.remove('wpaicg-fullscreened');
    
                // Restore old inline style for wpaicg_chat_widget_content
                const oldWidgetStyle = wpaicgWidgetContent.getAttribute('data-old-inline-style') || '';
                wpaicgWidgetContent.setAttribute('style', oldWidgetStyle);
                wpaicgWidgetContent.removeAttribute('data-old-inline-style');
    
                // Restore old inline style for chatbox
                const oldChatboxStyle = chatbox.getAttribute('data-old-inline-style') || '';
                chatbox.setAttribute('style', oldChatboxStyle);
                chatbox.removeAttribute('data-old-inline-style');
    
                // Restore data-width / data-height
                const oldWidth = chatbox.getAttribute('data-old-width');
                const oldHeight = chatbox.getAttribute('data-old-height');
                if (oldWidth !== null) {
                    chatbox.setAttribute('data-width', oldWidth);
                    chatbox.removeAttribute('data-old-width');
                }
                if (oldHeight !== null) {
                    chatbox.setAttribute('data-height', oldHeight);
                    chatbox.removeAttribute('data-old-height');
                }
    
                // Re-run the widget sizing
                wpaicgChatBoxSize();
            }
            // Entering fullscreen
            else {
                btn.classList.add('wpaicg-fullscreen-box');
                wpaicgWidgetContent.classList.add('wpaicg-fullscreened');
    
                // Save wpaicg_chat_widget_content style
                const currentWidgetStyle = wpaicgWidgetContent.getAttribute('style') || '';
                wpaicgWidgetContent.setAttribute('data-old-inline-style', currentWidgetStyle);
    
                // Save chatbox style
                const currentChatboxStyle = chatbox.getAttribute('style') || '';
                chatbox.setAttribute('data-old-inline-style', currentChatboxStyle);
    
                // Also store data-width / data-height
                const currentDataWidth = chatbox.getAttribute('data-width') || '';
                const currentDataHeight = chatbox.getAttribute('data-height') || '';
                chatbox.setAttribute('data-old-width', currentDataWidth);
                chatbox.setAttribute('data-old-height', currentDataHeight);
    
                // Fullscreen styles
                wpaicgWidgetContent.style.position = 'fixed';
                wpaicgWidgetContent.style.top = '0';
                wpaicgWidgetContent.style.left = '0';
                wpaicgWidgetContent.style.width = '100%';
                wpaicgWidgetContent.style.height = 'calc(100vh - 20px)';
                wpaicgWidgetContent.style.zIndex = '999999999';
                wpaicgWidgetContent.style.overflowY = 'auto';
    
                // Expand chatbox to fill container
                chatbox.style.width = '100%';
                chatbox.style.height = 'calc(100vh - 20px)';
                chatbox.style.overflowY = 'auto';
    
                // Update data attributes
                chatbox.setAttribute('data-width', '100%');
                chatbox.setAttribute('data-height', '100%');
    
                // Re-run widget sizing
                wpaicgChatBoxSize();
            }
        }
    }
    if (wpaicgChatFullScreens.length) {
        for (var i = 0; i < wpaicgChatFullScreens.length; i++) {
            var wpaicgChatFullScreen = wpaicgChatFullScreens[i];
            wpaicgChatFullScreen.addEventListener('click', function (e) {
                wpaicgFullScreen(e.currentTarget);
            })
        }
    }
    function resizeChatWidgets() {
        if (wpaicg_chat_widgets !== null && wpaicg_chat_widgets.length) {
            for (var i = 0; i < wpaicg_chat_widgets.length; i++) {
                var wpaicg_chat_widget = wpaicg_chat_widgets[i];
                if (window.innerWidth < 350) {
                    wpaicg_chat_widget.getElementsByClassName('wpaicg-chatbox')[0].style.width = window.innerWidth + 'px';
                    wpaicg_chat_widget.getElementsByClassName('wpaicg_chat_widget_content')[0].style.width = window.innerWidth + 'px';
                }
            }
        }
    }
    window.addEventListener('resize', function () {
        wpaicgChatBoxSize();
        wpaicgChatShortcodeSize();
        resizeChatWidgets();
    });
    wpaicgChatShortcodeSize();
    wpaicgChatBoxSize();
    resizeChatWidgets();

    function wpaicgescapeHtml(unsafe) {
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function wpaicgstartChatRecording() {
        let constraints = { audio: true, video: false }
        navigator.mediaDevices.getUserMedia(constraints).then(function (stream) {
            wpaicgaudioContext = new wpaicgChatAudioContext();
            wpaicgChatStream = stream;
            wpaicgInput = wpaicgaudioContext.createMediaStreamSource(stream);
            wpaicgChatRec = new Recorder(wpaicgInput, { numChannels: 1 });
            wpaicgChatRec.record();
        })
    }

    function wpaicgstopChatRecording(mic) {
        wpaicgChatRec.stop();
        wpaicgChatStream.getAudioTracks()[0].stop();
        wpaicgChatRec.exportWAV(function (blob) {
            let type = mic.getAttribute('data-type');
            let parentChat;
            let chatContent;
            let chatTyping;
            if (type === 'widget') {
                parentChat = mic.closest('.wpaicg-chatbox');
                chatContent = parentChat.querySelectorAll('.wpaicg-chatbox-content')[0];
                chatTyping = parentChat.querySelectorAll('.wpaicg-chatbox-typing')[0];
            } else {
                parentChat = mic.closest('.wpaicg-chat-shortcode');
                chatContent = parentChat.querySelectorAll('.wpaicg-chat-shortcode-content')[0];
                chatTyping = parentChat.querySelectorAll('.wpaicg-chat-shortcode-typing')[0];
            }
            wpaicgSendChatMessage(parentChat, chatTyping, type, blob);
        });
    }

    // Function to generate a random string
    function generateRandomString(length) {
        let result = '';
        let characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        let charactersLength = characters.length;
        for (let i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() * charactersLength));
        }
        return result;
    }

    function setupButtonListeners(isCopyEnabled, isFeedbackEnabled, class_ai_item, emptyClipboardSVG, checkedClipboardSVG, thumbsUpSVG, thumbsDownSVG, showFeedbackModal, aiBg, fontColor, usrBg, chat, wpaicg_nonce, chatbot_identity) {
        let hideTimeout;

        // Show buttons on hover or touchstart of the icon container
        jQuery(document).on('mouseenter touchstart', `li.${class_ai_item} .wpaicg-icon-container`, function (event) {
            clearTimeout(hideTimeout);

            // Prevent triggering on scroll or unintended touches
            if (event.type === 'touchstart') {
                event.stopPropagation();
            }

            const buttons = jQuery(this).find('.wpaicg-copy-button, .wpaicg-thumbs-up-button, .wpaicg-thumbs-down-button');

            // Display copy button if enabled
            if (isCopyEnabled) {
                buttons.filter('.wpaicg-copy-button').css({
                    display: 'inline-block',
                    opacity: 1,
                    visibility: 'visible'
                });
            }

            // Display feedback buttons if enabled
            if (isFeedbackEnabled) {
                buttons.filter('.wpaicg-thumbs-up-button, .wpaicg-thumbs-down-button').css({
                    display: 'inline-block',
                    opacity: 1,
                    visibility: 'visible'
                });
            }
        });

        // Hide buttons after leaving the icon container
        jQuery(document).on('mouseleave touchend', `li.${class_ai_item} .wpaicg-icon-container`, function () {
            const buttons = jQuery(this).find('.wpaicg-copy-button, .wpaicg-thumbs-up-button, .wpaicg-thumbs-down-button');
            hideTimeout = setTimeout(() => {
                buttons.css({
                    opacity: 0,
                    visibility: 'hidden',
                    display: 'none'
                });
            }, 2000);
        });

        // Copy text functionality remains unchanged
        jQuery(document).on('click', '.wpaicg-copy-button', function () {
            const chatId = jQuery(this).data('chat-id');
            const messageText = document.getElementById(chatId).innerText;

            navigator.clipboard.writeText(messageText).then(() => {
                // Change icon to check mark
                jQuery(this).html(checkedClipboardSVG);
                setTimeout(() => {
                    // Reset icon to original after 2 seconds
                    jQuery(this).html(emptyClipboardSVG);
                }, 2000);
            }).catch(err => console.error('Failed to copy text: ', err));
        });

        // Feedback functionality remains unchanged
        jQuery(document).on('click', '.wpaicg-thumbs-up-button, .wpaicg-thumbs-down-button', function () {
            const feedbackType = jQuery(this).hasClass('wpaicg-thumbs-up-button') ? 'up' : 'down';
            const chatId = jQuery(this).data('chat-id').replace('wpaicg-chat-message-', '');
            showFeedbackModal(feedbackType, chatId, aiBg, fontColor, usrBg, chat, wpaicg_nonce, chatbot_identity);
        });
    }

    function showFeedbackModal(feedbackType, chatId, bgColor, textColor, usrBg, chat, wpaicg_nonce, chatbot_identity) {
        const chatWidget = jQuery('.wpaicg_chat_widget');
        const feedbackTitle = chat.getAttribute('data-feedback_title') || 'Feedback';
        const feedbackMessage = chat.getAttribute('data-feedback_message') || 'Please provide details: (optional)';
        const feedbackSuccessMessage = chat.getAttribute('data-feedback_success') || 'Thank you for your feedback!';

        const chatShortcode = jQuery(chat).closest('.wpaicg-chat-shortcode');
        const wasFullscreen = chatShortcode.hasClass('wpaicg-fullscreened');

        if (wasFullscreen) {
            // Exit fullscreen mode before showing feedback modal
            const fullScreenBtn = chatShortcode.find('.wpaicg-chatbox-fullscreen');
            wpaicgFullScreen(fullScreenBtn[0]); // Exit fullscreen
        }

        if (chatWidget.hasClass('wpaicg_widget_open')) {
            chatWidget.data('was-open', true);
            chatWidget.removeClass('wpaicg_widget_open');
        } else {
            chatWidget.data('was-open', false);
        }

        const modalHtml = ` 
            <style>
                @keyframes wpaicg-feedback-spin {
                    0% { transform: rotate(0deg); }
                    100% { transform: rotate(360deg); }
                }
        
                .wpaicg-feedback-spinner {
                    display: inline-block;
                    width: 16px;
                    height: 16px;
                    border: 2px solid ${textColor};
                    border-top: 2px solid ${bgColor};
                    border-radius: 50%;
                    animation: wpaicg-feedback-spin 1s linear infinite;
                }
                .wpaicg-feedback-message {
                    color: ${textColor};
                }
            </style>
            <div class="wpaicg-feedback-modal-overlay">
                <div class="wpaicg-feedback-modal" style="background-color:${bgColor};color:${textColor};position:relative;">
                    <button class="wpaicg-feedback-modal-close" style="position:absolute; top:10px; right:10px; background:none; border:none; color:${textColor}; font-size:18px; cursor:pointer;">&times;</button>
                    <h2 style="background-color:${bgColor};color:${textColor};">${feedbackTitle}</h2>
                    <p>${feedbackMessage}</p>
                    <textarea class="wpaicg-feedback-textarea"></textarea>
                    <div class="wpaicg-feedback-modal-buttons">
                        <div class="wpaicg-feedback-message" style="display:none;"></div>
                        <button class="wpaicg-feedback-modal-submit" style="background-color:${usrBg};color:${textColor};border:none;" data-feedback-type="${feedbackType}" data-chat-id="${chatId}">
                            Submit
                            <span class="wpaicg-feedback-spinner" style="display:none; margin-left:5px; border: 2px solid ${textColor}; border-top: 2px solid ${bgColor}; border-radius: 50%; width: 16px; height: 16px; animation: wpaicg-feedback-spin 1s linear infinite;"></span>
                        </button>
                    </div>
                </div>
            </div>
        `;

        jQuery('body').append(modalHtml);

        jQuery('.wpaicg-feedback-modal-close').on('click', function () {
            jQuery('.wpaicg-feedback-modal-overlay').fadeOut(300, function () {
                jQuery(this).remove();
                if (wasFullscreen) {
                    // Restore fullscreen after feedback modal is closed
                    const fullScreenBtn = chatShortcode.find('.wpaicg-chatbox-fullscreen');
                    wpaicgFullScreen(fullScreenBtn[0]); // Re-enter fullscreen
                }
                if (chatWidget.data('was-open')) {
                    chatWidget.addClass('wpaicg_widget_open');
                }
            });
        });

        jQuery('.wpaicg-feedback-modal-submit').on('click', function () {
            const modal = jQuery(this).closest('.wpaicg-feedback-modal');
            const feedbackText = modal.find('.wpaicg-feedback-textarea').val();
            const feedbackType = jQuery(this).data('feedback-type');
            const chatId = jQuery(this).data('chat-id');
            const nonce = wpaicg_nonce;
            const submitButton = jQuery(this);
            const spinner = submitButton.find('.wpaicg-feedback-spinner');
            const feedbackMessageElement = modal.find('.wpaicg-feedback-message');

            spinner.show();
            submitButton.prop('disabled', true);

            jQuery.ajax({
                url: wpaicgParams.ajax_url,
                method: 'POST',
                data: {
                    action: 'wpaicg_submit_feedback',
                    chatId: chatId,
                    feedbackType: feedbackType,
                    feedbackDetails: feedbackText,
                    _wpnonce: nonce,
                    chatbot_id: chatbot_identity,
                },
                success: function (response) {
                    feedbackMessageElement.html(`<span style="color:${textColor};">${feedbackSuccessMessage}</span>`).fadeIn(300);
                    setTimeout(() => {
                        jQuery('.wpaicg-feedback-modal-overlay').fadeOut(300, function () {
                            jQuery(this).remove();
                            if (chatWidget.data('was-open')) {
                                chatWidget.addClass('wpaicg_widget_open');
                            }
                            if (wasFullscreen) {
                                const fullScreenBtn = chatShortcode.find('.wpaicg-chatbox-fullscreen');
                                wpaicgFullScreen(fullScreenBtn[0]); // Re-enter fullscreen
                            }
                        });
                    }, 2000);
                },
                error: function (error) {
                    feedbackMessageElement.html(`<span style="color:${textColor};">Error. Please try again later.</span>`).fadeIn(300);
                    setTimeout(() => {
                        jQuery('.wpaicg-feedback-modal-overlay').fadeOut(300, function () {
                            jQuery(this).remove();
                            if (chatWidget.data('was-open')) {
                                chatWidget.addClass('wpaicg_widget_open');
                            }
                            if (wasFullscreen) {
                                const fullScreenBtn = chatShortcode.find('.wpaicg-chatbox-fullscreen');
                                wpaicgFullScreen(fullScreenBtn[0]); // Re-enter fullscreen
                            }
                        });
                    }, 2000);
                },
                complete: function () {
                    spinner.hide();
                    submitButton.prop('disabled', false);
                }
            });
        });
    }

    /**
     * Sends a user message (text or audio) to the server, handles response streaming or non-streaming,
     * and updates the conversation history in localStorage.
     *
     * @param {HTMLElement} chat - The parent chat container (wpaicg-chat-shortcode or wpaicg-chatbox).
     * @param {HTMLTextAreaElement} typing - The textarea input element.
     * @param {string} type - Either "shortcode" or "widget", indicating chat type.
     * @param {Blob} [blob] - Optional audio blob if the user recorded microphone input.
     */
    function wpaicgSendChatMessage(chat, typing, type, blob) {
        hideConversationStarters();
        // Remove the lead form if it exists
        var leadFormMessage = chat.querySelector('.wpaicg-lead-form-message');
        if (leadFormMessage) {
            leadFormMessage.remove();
            // Optionally, set 'wpaicg_lead_form_shown' to '1' so it doesn't show again
            localStorage.setItem('wpaicg_lead_form_shown', '1');
        }
        let botIdAudio = chat.getAttribute('data-bot-id') || '0';
        let chatTypeAudio = chat.getAttribute('data-type') || 'shortcode';
        let audioKey = `audio_${chatTypeAudio}_${botIdAudio}`; // Use the new key for audio state

        let isAudioEnabledByUser = wpaicgUserAudioEnabled[audioKey]; // Get the audio state for this bot instance
        let userVoiceControl = chat.getAttribute('data-user-voice-control');
        let wpaicg_box_typing = typing;
        let wpaicg_ai_thinking, wpaicg_messages_box, class_user_item, class_ai_item;
        let wpaicgMessage = '';
        let wpaicgData = new FormData();
        let wpaicg_nonce = chat.getAttribute('data-nonce');
        let wpaicg_bot_id = parseInt(chat.getAttribute('data-bot-id'));
        let wpaicg_user_bg = chat.getAttribute('data-user-bg-color');
        let wpaicg_font_size = chat.getAttribute('data-fontsize');
        let wpaicg_speech = chat.getAttribute('data-speech');
        let wpaicg_voice = chat.getAttribute('data-voice');
        let elevenlabs_model = chat.getAttribute('data-elevenlabs-model');
        if (elevenlabs_model === null || elevenlabs_model === undefined) {
            elevenlabs_model = chat.getAttribute('data-elevenlabs_model');
        }
        let elevenlabs_voice = chat.getAttribute('data-elevenlabs-voice');
        if (elevenlabs_voice === null || elevenlabs_voice === undefined) {
            elevenlabs_voice = chat.getAttribute('data-elevenlabs_voice');
        }
        let wpaicg_voice_error = chat.getAttribute('data-voice-error');
        let wpaicg_typewriter_effect = chat.getAttribute('data-typewriter-effect');
        let wpaicg_typewriter_speed = chat.getAttribute('data-typewriter-speed');

        let url = chat.getAttribute('data-url');
        let post_id = chat.getAttribute('data-post-id');
        let wpaicg_ai_bg = chat.getAttribute('data-ai-bg-color');
        let wpaicg_font_color = chat.getAttribute('data-color');
        let voice_service = chat.getAttribute('data-voice_service');

        let voice_language = chat.getAttribute('data-voice_language');
        let voice_name = chat.getAttribute('data-voice_name');
        let voice_device = chat.getAttribute('data-voice_device');
        let openai_model = chat.getAttribute('data-openai_model');

        let openai_voice = chat.getAttribute('data-openai_voice');

        let openai_output_format = chat.getAttribute('data-openai_output_format');

        let openai_voice_speed = chat.getAttribute('data-openai_voice_speed');

        let openai_stream_nav = chat.getAttribute('data-openai_stream_nav');

        let voice_speed = chat.getAttribute('data-voice_speed');
        let voice_pitch = chat.getAttribute('data-voice_pitch');
        var chat_pdf = chat.getAttribute('data-pdf');

        // Handle image upload
        var imageInput = document.getElementById('imageUpload');
        var imageUrl = ''; // Variable to store the URL of the uploaded image for preview
        if (imageInput) {
            if (imageInput.files && imageInput.files[0]) {
                var validImageTypes = ['image/png', 'image/jpeg', 'image/jpg', 'image/webp', 'image/gif'];
                if (!validImageTypes.includes(imageInput.files[0].type)) {
                    alert('Invalid file type. Only PNG, JPEG, WEBP, and non-animated GIF images are allowed.');
                    return;
                }
                // Append image file to FormData object
                wpaicgData.append('image', imageInput.files[0], imageInput.files[0].name);
                // Create a URL for the uploaded image file for preview
                imageUrl = URL.createObjectURL(imageInput.files[0]);
            }
        }

        if (type === 'widget') {
            wpaicg_ai_thinking = chat.getElementsByClassName('wpaicg-bot-thinking')[0];
            wpaicg_messages_box = chat.getElementsByClassName('wpaicg-chatbox-messages')[0];
            class_user_item = 'wpaicg-chat-user-message';
            class_ai_item = 'wpaicg-chat-ai-message';
            wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;
            // Retrieve all message elements
            const messages = wpaicg_messages_box.querySelectorAll('li');
            // Ensure messages exist and scroll to the last message
            if (messages.length > 0) {
                messages[messages.length - 1].scrollIntoView();
            }

        } else {
            wpaicg_ai_thinking = chat.getElementsByClassName('wpaicg-bot-thinking')[0];
            wpaicg_messages_box = chat.getElementsByClassName('wpaicg-chat-shortcode-messages')[0];
            class_user_item = 'wpaicg-user-message';
            class_ai_item = 'wpaicg-ai-message';
        }
        wpaicg_ai_thinking.style.display = 'block';
        let wpaicg_question = wpaicgescapeHtml(wpaicg_box_typing.value);
        if (!wpaicg_question.trim() && blob === undefined) {
            wpaicg_ai_thinking.style.display = 'none';
            return; // Exit the function if no message or blob is provided
        }
        wpaicgMessage += '<li class="' + class_user_item + '" style="background-color:' + wpaicg_user_bg + ';font-size: ' + wpaicg_font_size + 'px;color: ' + wpaicg_font_color + '">';
        wpaicgData.append('_wpnonce', wpaicg_nonce);
        wpaicgData.append('post_id', post_id);
        if (chat_pdf && chat_pdf !== null) {
            wpaicgData.append('namespace', chat_pdf);
        }
        wpaicgData.append('url', url);
        if (type === 'widget') {
            wpaicgData.append('action', 'wpaicg_chatbox_message');
        } else {
            wpaicgData.append('action', 'wpaicg_chat_shortcode_message');
        }
        if (blob !== undefined) {
            let url = URL.createObjectURL(blob);
            wpaicgMessage += '<audio src="' + url + '" controls="true"></audio>';
            wpaicgData.append('audio', blob, 'wpaicg-chat-recording.wav');
        } else if (wpaicg_question !== '') {
            wpaicgData.append('message', wpaicg_question);
            wpaicgMessage += wpaicg_question.replace(/\n/g, '<br>');

        }

        wpaicgData.append('bot_id', wpaicg_bot_id);
        wpaicgMessage += '</li>';
        // If an image URL is available, add an <img> tag to display the image
        if (imageUrl !== '') {
            wpaicgMessage += '<li class="' + class_user_item + '" style="background-color:' + wpaicg_user_bg + ';font-size: ' + wpaicg_font_size + 'px;color: ' + wpaicg_font_color + '">';
            wpaicgMessage += '<div style="max-width: 300px; height: auto; display: flex;">';
            wpaicgMessage += '<img src="' + imageUrl + '" style="max-width: 100%; height: auto;" onload="this.parentElement.parentElement.parentElement.scrollTop = this.parentElement.parentElement.parentElement.scrollHeight;">';
            wpaicgMessage += '</div>'; // Closing the div tag
            wpaicgMessage += '</li>';
        }
        wpaicg_messages_box.innerHTML += wpaicgMessage;
        wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;

        // Hide the thumbnail placeholder
        var thumbnailPlaceholder = document.querySelector('.wpaicg-thumbnail-placeholder');
        if (thumbnailPlaceholder) {
            thumbnailPlaceholder.style.display = 'none'; // Hide the thumbnail after message is sent
        }

        // Reset the image input after sending the message if imageInput exists first
        if (imageInput) {
            imageInput.value = '';
        }

        let chat_type = chat.getAttribute('data-type');

        let stream_nav;
        let chatbot_identity;

        // Check if it's a bot with dynamic ID
        if (wpaicg_bot_id && wpaicg_bot_id !== "0") {
            stream_nav = openai_stream_nav;
            chatbot_identity = 'custom_bot_' + wpaicg_bot_id;
        } else {
            // Check if it's a shortcode or widget based on chat_type
            if (chat_type === "shortcode") {
                stream_nav = chat.getAttribute('data-openai_stream_nav');
                chatbot_identity = 'shortcode';
            } else if (chat_type === "widget") {
                stream_nav = chat.getAttribute('data-openai_stream_nav');
                chatbot_identity = 'widget';
            }
        }
        wpaicgData.append('chatbot_identity', chatbot_identity);

        // Check for existing client_id in localStorage
        let clientID = localStorage.getItem('wpaicg_chat_client_id');
        if (!clientID) {
            // Generate and store a new client ID if not found
            clientID = generateRandomString(10); // Generate a 10 character string
            localStorage.setItem('wpaicg_chat_client_id', clientID);
        }

        let botId = chat.getAttribute('data-bot-id') || '0';
        let assistantEnabled = chat.getAttribute('data-assistant-enabled') === 'true';

        // Include local conversation if any
        let botIdAttr = botId || '0';
        let activeConversationKey = `wpaicg_current_conversation_${botIdAttr}_${clientID}`;
        let conversationKey = localStorage.getItem(activeConversationKey);
        // If there's no active conversation yet, fallback to a default
        if (!conversationKey) {
            // e.g. wpaicg_chat_history_custom_bot_4367_{clientID}_1
            let basePrefix = botIdAttr !== '0'
                ? `wpaicg_chat_history_custom_bot_${botIdAttr}_${clientID}`
                : `wpaicg_chat_history_${type}_${clientID}`;
            // Create the default index = 1 if not set
            let newKey = `${basePrefix}_1`;
            if (!localStorage.getItem(newKey)) {
                localStorage.setItem(newKey, JSON.stringify([]));
            }
            localStorage.setItem(activeConversationKey, newKey);
            conversationKey = newKey;
        }
        // Add the conversation history to form data
        let storedHistory = localStorage.getItem(conversationKey) || '[]';
        wpaicgData.append('wpaicg_chat_history', storedHistory);

        //append client_id to wpaicgData
        wpaicgData.append('wpaicg_chat_client_id', clientID);

        // --------------- Assistant-Enabled logic for thread ---------------
        let activeThreadKey = `wpaicg_current_thread_${botIdAttr}_${clientID}`;
        let currentThreadStorageKey = null;
        if (assistantEnabled) {
            // We store or retrieve the "active thread" from localStorage
            currentThreadStorageKey = localStorage.getItem(activeThreadKey);
            if (!currentThreadStorageKey) {
                // If user never clicked "New Chat," fallback to default index=1
                let baseThreadPrefix = botIdAttr !== '0'
                    ? `custom_bot_${botIdAttr}_${clientID}`
                    : `${type}_${clientID}`;
                let foundMaxThreadIndex = 0;
                const threadListObj = JSON.parse(localStorage.getItem('wpaicg_thread_list')) || {};
                for (let existingKey in threadListObj) {
                    if (existingKey.startsWith(baseThreadPrefix)) {
                        let suffixMatch = existingKey.match(/_(\d+)$/);
                        if (suffixMatch) {
                            let indexNum = parseInt(suffixMatch[1], 10);
                            if (indexNum > foundMaxThreadIndex) foundMaxThreadIndex = indexNum;
                        }
                    }
                }
                let defaultThreadIndex = foundMaxThreadIndex + 1;
                currentThreadStorageKey = `${baseThreadPrefix}_${defaultThreadIndex}`;
                localStorage.setItem(activeThreadKey, currentThreadStorageKey);
            }
            // If there's a real thread ID in localStorage for that key, we pass it
            let threadListData = JSON.parse(localStorage.getItem('wpaicg_thread_list')) || {};
            let existingThreadId = threadListData[currentThreadStorageKey];
            if (existingThreadId) {
                wpaicgData.append('thread_id', existingThreadId);
            }
        }

        // Hide the image thumbnail after sending the message
        if (imageInputThumbnail) {
            imageInputThumbnail.style.display = 'none';
        }

        if (stream_nav === "1") {
            // Update local storage
            updateChatHistory(wpaicg_question, 'user', wpaicg_randomnum, chat, chatbot_identity, clientID);

            if (assistantEnabled) {
                // Call the new assistant streaming function
                handleAssistantStreaming(
                    wpaicgData,
                    wpaicg_messages_box,
                    wpaicg_box_typing,
                    wpaicg_ai_thinking,
                    class_ai_item,
                    chat,
                    chatbot_identity,
                    clientID,
                    wpaicg_nonce
                );
            } else {
                handleStreaming(wpaicgData, wpaicg_messages_box, wpaicg_box_typing, wpaicg_ai_thinking, class_ai_item, chat, chatbot_identity, clientID, wpaicg_nonce);
            }
        }
        else {
            updateChatHistory(wpaicg_question, 'user', wpaicg_randomnum, chat, chatbot_identity, clientID);

            // We also pass a fresh random ID for chat message
            var wpaicg_randomnum = Math.floor((Math.random() * 100000) + 1);
            const chatId = `wpaicg-chat-message-${wpaicg_randomnum}`;
            // Extract copy and feedback settings from the chat element
            const copyEnabled = chat.getAttribute('data-copy_btn') === "1";
            const feedbackEnabled = chat.getAttribute('data-feedback_btn') === "1";
            const fontColor = chat.getAttribute('data-color');
            const usrBg = chat.getAttribute('data-user-bg-color');
            const emptyClipboardSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-copy" viewBox="0 0 16 16">
            <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
            </svg>`;
            const checkedClipboardSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-check2" viewBox="0 0 16 16">
            <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
            </svg>`;

            const thumbsUpSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-hand-thumbs-up" viewBox="0 0 16 16">
            <path d="M8.864.046C7.908-.193 7.02.53 6.956 1.466c-.072 1.051-.23 2.016-.428 2.59-.125.36-.479 1.013-1.04 1.639-.557.623-1.282 1.178-2.131 1.41C2.685 7.288 2 7.87 2 8.72v4.001c0 .845.682 1.464 1.448 1.545 1.07.114 1.564.415 2.068.723l.048.03c.272.165.578.348.97.484.397.136.861.217 1.466.217h3.5c.937 0 1.599-.477 1.934-1.064a1.86 1.86 0 0 0 .254-.912c0-.152-.023-.312-.077-.464.201-.263.38-.578.488-.901.11-.33.172-.762.004-1.149.069-.13.12-.269.159-.403.077-.27.113-.568.113-.857 0-.288-.036-.585-.113-.856a2 2 0 0 0-.138-.362 1.9 1.9 0 0 0 .234-1.734c-.206-.592-.682-1.1-1.2-1.272-.847-.282-1.803-.276-2.516-.211a10 10 0 0 0-.443.05 9.4 9.4 0 0 0-.062-4.509A1.38 1.38 0 0 0 9.125.111zM11.5 14.721H8c-.51 0-.863-.069-1.14-.164-.281-.097-.506-.228-.776-.393l-.04-.024c-.555-.339-1.198-.731-2.49-.868-.333-.036-.554-.29-.554-.55V8.72c0-.254.226-.543.62-.65 1.095-.3 1.977-.996 2.614-1.708.635-.71 1.064-1.475 1.238-1.978.243-.7.407-1.768.482-2.85.025-.362.36-.594.667-.518l.262.066c.16.04.258.143.288.255a8.34 8.34 0 0 1-.145 4.725.5.5 0 0 0 .595.644l.003-.001.014-.003.058-.014a9 9 0 0 1 1.036-.157c.663-.06 1.457-.054 2.11.164.175.058.45.3.57.65.107.308.087.67-.266 1.022l-.353.353.353.354c.043.043.105.141.154.315.048.167.075.37.075.581 0 .212-.027.414-.075.582-.05.174-.111.272-.154.315l-.353.353.353.354c.047.047.109.177.005.488a2.2 2.2 0 0 1-.505.805l-.353.353.353.354c.006.005.041.05.041.17a.9.9 0 0 1-.121.416c-.165.288-.503.56-1.066.56z"/>
            </svg>`;
            const thumbsDownSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-hand-thumbs-down" viewBox="0 0 16 16">
            <path d="M8.864 15.674c-.956.24-1.843-.484-1.908-1.42-.072-1.05-.23-2.015-.428-2.59-.125-.36-.479-1.012-1.04-1.638-.557-.624-1.282-1.179-2.131-1.41C2.685 8.432 2 7.85 2 7V3c0-.845.682-1.464 1.448-1.546 1.07-.113 1.564-.415 2.068-.723l.048-.029c.272-.166.578-.349.97-.484C6.931.08 7.395 0 8 0h3.5c.937 0 1.599.478 1.934 1.064.164.287.254.607.254.913 0 .152-.023.312-.077.464.201.262.38.577.488.9.11.33.172.762.004 1.15.069.13.12.268.159.403.077.27.113.567.113.856s-.036.586-.113.856c-.035.12-.08.244-.138.363.394.571.418 1.2.234 1.733-.206.592-.682 1.1-1.2 1.272-.847.283-1.803.276-2.516.211a10 10 0 0 1-.443-.05 9.36 9.36 0 0 1-.062 4.51c-.138.508-.55.848-1.012.964zM11.5 1H8c-.51 0-.863.068-1.14.163-.281.097-.506.229-.776.393l-.04.025c-.555.338-1.198.73-2.49.868-.333.035-.554.29-.554.55V7c0 .255.226.543.62.65 1.095.3 1.977.997 2.614 1.709.635.71 1.064 1.475 1.238 1.977.243.7.407 1.768.482 2.85.025.362.36.595.667.518l.262-.065c.16-.04.258-.144.288-.255a8.34 8.34 0 0 0-.145-4.726.5.5 0 0 1 .595-.643h.003l.014.004.058.013a9 9 0 0 0 1.036.157c.663.06 1.457.054 2.11-.163.175-.059.45-.301.57-.651.107-.308.087-.67-.266-1.021L12.793 7l.353-.354c.043-.042.105-.14.154-.315.048-.167.075-.37.075-.581s-.027-.414-.075-.581c-.05-.174-.111-.273-.154-.315l-.353-.354.353-.354c.047-.047.109-.176.005-.488a2.2 2.2 0 0 0-.505-.804l-.353-.354.353-.354c.006-.005.041-.05.041-.17a.9.9 0 0 0-.121-.415C12.4 1.272 12.063 1 11.5 1"/>
            </svg>`;
            const xhttp = new XMLHttpRequest();
            wpaicg_box_typing.value = '';
            xhttp.open('POST', wpaicgParams.ajax_url, true);
            xhttp.send(wpaicgData);
            xhttp.onreadystatechange = function (oEvent) {
                if (xhttp.readyState === 4) {
                    var wpaicg_message = '';
                    var wpaicg_response_text = '';
                    if (xhttp.status === 200) {
                        var wpaicg_response = this.responseText;
                        if (wpaicg_response !== '') {
                            wpaicg_response = JSON.parse(wpaicg_response);
                            wpaicg_ai_thinking.style.display = 'none'
                            if (wpaicg_response.status === 'success') {
                                // If assistantEnabled and the response contains thread_id, store it:
                                if (assistantEnabled && wpaicg_response.thread_id) {
                                    let threadList = JSON.parse(localStorage.getItem('wpaicg_thread_list')) || {};
                                    threadList[currentThreadStorageKey] = wpaicg_response.thread_id;
                                    localStorage.setItem('wpaicg_thread_list', JSON.stringify(threadList));
                                }
                                wpaicg_response_text = wpaicg_response.data;
                                wpaicg_message = `
                                    <li class="${class_ai_item} wpaicg-icon-container" style="background-color:${wpaicg_ai_bg};font-size:${wpaicg_font_size}px;color:${wpaicg_font_color}">
                                        <p style="width:100%">
                                            <span class="wpaicg-chat-message" id="${chatId}">${wpaicg_response_text}</span>
                                            ${copyEnabled ? `<button class="wpaicg-copy-button" data-chat-id="${chatId}">${emptyClipboardSVG}</button>` : ''}
                                            ${feedbackEnabled ? `
                                                <button class="wpaicg-thumbs-up-button" data-chat-id="${chatId}">${thumbsUpSVG}</button>
                                                <button class="wpaicg-thumbs-down-button" data-chat-id="${chatId}">${thumbsDownSVG}</button>` : ''}
                                        </p>
                                    </li>
                                `;
                            } else {
                                wpaicg_response_text = wpaicg_response.msg;
                                wpaicg_message = '<li class="' + class_ai_item + '" style="background-color:' + wpaicg_ai_bg + ';font-size: ' + wpaicg_font_size + 'px;color: ' + wpaicg_font_color + '"><p style="width:100%"><span class="wpaicg-chat-message wpaicg-chat-message-error" id="wpaicg-chat-message-' + wpaicg_randomnum + '"></span>';
                            }
                        }
                    } else {
                        wpaicg_message = '<li class="' + class_ai_item + '" style="background-color:' + wpaicg_ai_bg + ';font-size: ' + wpaicg_font_size + 'px;color: ' + wpaicg_font_color + '"><p style="width:100%"><span class="wpaicg-chat-message wpaicg-chat-message-error" id="wpaicg-chat-message-' + wpaicg_randomnum + '"></span>';
                        wpaicg_response_text = 'Something went wrong. Please clear your cache and try again.';
                        wpaicg_ai_thinking.style.display = 'none';
                    }
                    if (wpaicg_response_text === 'null' || wpaicg_response_text === null) {
                        wpaicg_response_text = 'Empty response from api. Check your server logs for more details.';
                    }
                    setupButtonListeners(copyEnabled, feedbackEnabled, class_ai_item, emptyClipboardSVG, checkedClipboardSVG, thumbsUpSVG, thumbsDownSVG, showFeedbackModal, wpaicg_ai_bg, wpaicg_font_color, usrBg, chat, wpaicg_nonce, chatbot_identity);
                    const simpleChatId = chatId.replace('wpaicg-chat-message-', '');
                    updateChatHistory(wpaicg_response_text, 'ai', simpleChatId, chat, chatbot_identity, clientID);
                    if (wpaicg_response_text !== '' && wpaicg_message !== '') {
                        if (parseInt(wpaicg_speech) == 1 && (userVoiceControl == "1" ? isAudioEnabledByUser : true)) {
                            if (voice_service === 'google') {
                                wpaicg_ai_thinking.style.display = 'block';
                                let speechData = new FormData();
                                speechData.append('nonce', wpaicg_nonce);
                                speechData.append('action', 'wpaicg_google_speech');
                                speechData.append('language', voice_language);
                                speechData.append('name', voice_name);
                                speechData.append('device', voice_device);
                                speechData.append('speed', voice_speed);
                                speechData.append('pitch', voice_pitch);
                                speechData.append('text', wpaicg_response_text);
                                var speechRequest = new XMLHttpRequest();
                                speechRequest.open("POST", wpaicgParams.ajax_url);
                                speechRequest.onload = function () {
                                    var result = speechRequest.responseText;
                                    try {
                                        result = JSON.parse(result);
                                        if (result.status === 'success') {
                                            var byteCharacters = atob(result.audio);
                                            const byteNumbers = new Array(byteCharacters.length);
                                            for (let i = 0; i < byteCharacters.length; i++) {
                                                byteNumbers[i] = byteCharacters.charCodeAt(i);
                                            }
                                            const byteArray = new Uint8Array(byteNumbers);
                                            const blob = new Blob([byteArray], { type: 'audio/mp3' });
                                            const blobUrl = URL.createObjectURL(blob);
                                            wpaicg_message += '<audio style="margin-top:6px;" controls="controls"><source type="audio/mpeg" src="' + blobUrl + '"></audio>';
                                            wpaicg_message += '</p></li>';
                                            wpaicg_ai_thinking.style.display = 'none';
                                            // scroll to the bottom of the chatbox
                                            wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;
                                            wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                        }
                                        else {
                                            var errorMessageDetail = 'Google: ' + result.msg;
                                            wpaicg_ai_thinking.style.display = 'none';
                                            if (parseInt(wpaicg_voice_error) !== 1) {
                                                wpaicg_message += '<span style="width: 100%;display: block;font-size: 11px;">' + errorMessageDetail + '</span>';
                                            }
                                            else if (typeof wpaicg_response !== 'undefined' && typeof wpaicg_response.log !== 'undefined' && wpaicg_response.log !== '') {
                                                var speechLogMessage = new FormData();
                                                speechLogMessage.append('nonce', wpaicg_nonce);
                                                speechLogMessage.append('log_id', wpaicg_response.log);
                                                speechLogMessage.append('message', errorMessageDetail);
                                                speechLogMessage.append('action', 'wpaicg_speech_error_log');
                                                var speechErrorRequest = new XMLHttpRequest();
                                                speechErrorRequest.open("POST", wpaicgParams.ajax_url);
                                                speechErrorRequest.send(speechLogMessage);
                                            }
                                            wpaicg_message += '</p></li>';
                                            wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                        }
                                    }
                                    catch (errorSpeech) {

                                    }
                                }
                                speechRequest.send(speechData);
                            }
                            else if (voice_service === 'openai') {
                                // OpenAI TTS code
                                let speechData = new FormData();
                                speechData.append('action', 'wpaicg_openai_speech');
                                speechData.append('nonce', wpaicg_nonce);
                                speechData.append('text', wpaicg_response_text);


                                speechData.append('model', openai_model);
                                speechData.append('voice', openai_voice);
                                speechData.append('output_format', openai_output_format);
                                speechData.append('speed', openai_voice_speed);

                                // Display some sort of loading indicator
                                wpaicg_ai_thinking.style.display = 'block';

                                var speechRequest = new XMLHttpRequest();
                                speechRequest.open("POST", wpaicgParams.ajax_url);
                                speechRequest.responseType = "arraybuffer"; // Expecting raw audio data

                                speechRequest.onload = function () {
                                    if (speechRequest.status === 200) {
                                        wpaicg_ai_thinking.style.display = 'none';

                                        const audioData = speechRequest.response;
                                        const blobMimeType = getBlobMimeType(openai_output_format); // Get the MIME type based on the format
                                        const blob = new Blob([audioData], { type: blobMimeType });
                                        const blobUrl = URL.createObjectURL(blob);
                                        // Update your message UI here
                                        wpaicg_message += '<audio style="margin-top:6px;" controls="controls"><source type="audio/mpeg" src="' + blobUrl + '"></audio>';
                                        // scroll to the bottom of the chatbox
                                        wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;
                                        wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                    } else {
                                        // Handle HTTP errors
                                        wpaicg_ai_thinking.style.display = 'none';
                                        console.error('Error generating speech with OpenAI:', speechRequest.statusText);
                                        // Update your message UI to show the error
                                        wpaicg_message += '<span style="width: 100%;display: block;font-size: 11px;">Error generating speech with OpenAI</span>';
                                        wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                    }
                                };

                                speechRequest.onerror = function () {
                                    // Handle network errors
                                    wpaicg_ai_thinking.style.display = 'none';
                                    console.error('Network error during speech generation with OpenAI');
                                    // Update your message UI to show the network error
                                    wpaicg_message += '<span style="width: 100%;display: block;font-size: 11px;">Network error during speech generation</span>';
                                    wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                };

                                speechRequest.send(speechData);
                                // Utility function to get the correct MIME type
                                function getBlobMimeType(format) {
                                    switch (format) {
                                        case 'opus':
                                            return 'audio/opus';
                                        case 'aac':
                                            return 'audio/aac';
                                        case 'flac':
                                            return 'audio/flac';
                                        default:
                                            return 'audio/mpeg'; // Default to MP3
                                    }
                                }
                            }

                            else {
                                let speechData = new FormData();
                                speechData.append('nonce', wpaicg_nonce);
                                speechData.append('message', wpaicg_response_text);
                                speechData.append('voice', wpaicg_voice);
                                speechData.append('elevenlabs_model', elevenlabs_model);
                                speechData.append('action', 'wpaicg_text_to_speech');
                                wpaicg_ai_thinking.style.display = 'block';
                                var speechRequest = new XMLHttpRequest();
                                speechRequest.open("POST", wpaicgParams.ajax_url);
                                speechRequest.responseType = "arraybuffer";
                                speechRequest.onload = function () {
                                    wpaicg_ai_thinking.style.display = 'none';
                                    var blob = new Blob([speechRequest.response], { type: "audio/mpeg" });
                                    var fr = new FileReader();
                                    fr.onload = function () {
                                        var fileText = this.result;
                                        try {
                                            var errorMessage = JSON.parse(fileText);
                                            var errorMessageDetail = 'ElevenLabs: ' + errorMessage.detail.message;
                                            if (parseInt(wpaicg_voice_error) !== 1) {
                                                wpaicg_message += '<span style="width: 100%;display: block;font-size: 11px;">' + errorMessageDetail + '</span>';
                                            } else if (typeof wpaicg_response !== 'undefined' && typeof wpaicg_response.log !== 'undefined' && wpaicg_response.log !== '') {
                                                var speechLogMessage = new FormData();
                                                speechLogMessage.append('nonce', wpaicg_nonce);
                                                speechLogMessage.append('log_id', wpaicg_response.log);
                                                speechLogMessage.append('message', errorMessageDetail);
                                                speechLogMessage.append('action', 'wpaicg_speech_error_log');
                                                var speechErrorRequest = new XMLHttpRequest();
                                                speechErrorRequest.open("POST", wpaicgParams.ajax_url);
                                                speechErrorRequest.send(speechLogMessage);
                                            }
                                            wpaicg_message += '</p></li>';
                                            wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                        } catch (errorBlob) {
                                            var blobUrl = URL.createObjectURL(blob);
                                            wpaicg_message += '<audio style="margin-top:6px;" controls="controls"><source type="audio/mpeg" src="' + blobUrl + '"></audio>';
                                            wpaicg_message += '</p></li>';
                                            wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                                        }
                                    }
                                    fr.readAsText(blob);
                                }
                                speechRequest.send(speechData);
                            }
                        }
                        else {
                            wpaicg_message += '</p></li>';
                            wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed);
                        }
                    }
                }
            }
        }
    }

    // Function to hide all conversation starters
    function hideConversationStarters() {
        const starters = document.querySelectorAll('.wpaicg-conversation-starters');
        starters.forEach(starter => {
            starter.style.display = 'none';
        });
    }

    function handleStreaming(wpaicgData, wpaicg_messages_box, wpaicg_box_typing, wpaicg_ai_thinking, class_ai_item, chat, chatbot_identity, clientID, wpaicg_nonce) {
        // Remove the lead form if it exists
        var leadFormMessage = chat.querySelector('.wpaicg-lead-form-message');
        if (leadFormMessage) {
            leadFormMessage.remove();
            // Optionally, set 'wpaicg_lead_form_shown' to '1' so it doesn't show again
            localStorage.setItem('wpaicg_lead_form_shown', '1');
        }
        const fontSize = chat.getAttribute('data-fontsize');
        const aiBg = chat.getAttribute('data-ai-bg-color');
        const fontColor = chat.getAttribute('data-color');
        const usrBg = chat.getAttribute('data-user-bg-color');
        const copyEnabled = chat.getAttribute('data-copy_btn') === "1";
        const feedbackEnabled = chat.getAttribute('data-feedback_btn') === "1";

        wpaicg_box_typing.value = '';
        // add chatID to the query string
        const chatId = `wpaicg-chat-message-${Math.floor(Math.random() * 100000) + 1}`;
        const cleanedChatId = chatId.replace('wpaicg-chat-message-', ''); // Clean the chatId by removing the prefix
        wpaicgData.append('chat_id', cleanedChatId);

        const emptyClipboardSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-copy" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
        </svg>`;
        const checkedClipboardSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-check2" viewBox="0 0 16 16">
        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
        </svg>`;

        const thumbsUpSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-hand-thumbs-up" viewBox="0 0 16 16">
        <path d="M8.864.046C7.908-.193 7.02.53 6.956 1.466c-.072 1.051-.23 2.016-.428 2.59-.125.36-.479 1.013-1.04 1.639-.557.623-1.282 1.178-2.131 1.41C2.685 7.288 2 7.87 2 8.72v4.001c0 .845.682 1.464 1.448 1.545 1.07.114 1.564.415 2.068.723l.048.03c.272.165.578.348.97.484.397.136.861.217 1.466.217h3.5c.937 0 1.599-.477 1.934-1.064a1.86 1.86 0 0 0 .254-.912c0-.152-.023-.312-.077-.464.201-.263.38-.578.488-.901.11-.33.172-.762.004-1.149.069-.13.12-.269.159-.403.077-.27.113-.568.113-.857 0-.288-.036-.585-.113-.856a2 2 0 0 0-.138-.362 1.9 1.9 0 0 0 .234-1.734c-.206-.592-.682-1.1-1.2-1.272-.847-.282-1.803-.276-2.516-.211a10 10 0 0 0-.443.05 9.4 9.4 0 0 0-.062-4.509A1.38 1.38 0 0 0 9.125.111zM11.5 14.721H8c-.51 0-.863-.069-1.14-.164-.281-.097-.506-.228-.776-.393l-.04-.024c-.555-.339-1.198-.731-2.49-.868-.333-.036-.554-.29-.554-.55V8.72c0-.254.226-.543.62-.65 1.095-.3 1.977-.996 2.614-1.708.635-.71 1.064-1.475 1.238-1.978.243-.7.407-1.768.482-2.85.025-.362.36-.594.667-.518l.262.066c.16.04.258.143.288.255a8.34 8.34 0 0 1-.145 4.725.5.5 0 0 0 .595.644l.003-.001.014-.003.058-.014a9 9 0 0 1 1.036-.157c.663-.06 1.457-.054 2.11.164.175.058.45.3.57.65.107.308.087.67-.266 1.022l-.353.353.353.354c.043.043.105.141.154.315.048.167.075.37.075.581 0 .212-.027.414-.075.582-.05.174-.111.272-.154.315l-.353.353.353.354c.047.047.109.177.005.488a2.2 2.2 0 0 1-.505.805l-.353.353.353.354c.006.005.041.05.041.17a.9.9 0 0 1-.121.416c-.165.288-.503.56-1.066.56z"/>
        </svg>`;
        const thumbsDownSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-hand-thumbs-down" viewBox="0 0 16 16">
        <path d="M8.864 15.674c-.956.24-1.843-.484-1.908-1.42-.072-1.05-.23-2.015-.428-2.59-.125-.36-.479-1.012-1.04-1.638-.557-.624-1.282-1.179-2.131-1.41C2.685 8.432 2 7.85 2 7V3c0-.845.682-1.464 1.448-1.546 1.07-.113 1.564-.415 2.068-.723l.048-.029c.272-.166.578-.349.97-.484C6.931.08 7.395 0 8 0h3.5c.937 0 1.599.478 1.934 1.064.164.287.254.607.254.913 0 .152-.023.312-.077.464.201.262.38.577.488.9.11.33.172.762.004 1.15.069.13.12.268.159.403.077.27.113.567.113.856s-.036.586-.113.856c-.035.12-.08.244-.138.363.394.571.418 1.2.234 1.733-.206.592-.682 1.1-1.2 1.272-.847.283-1.803.276-2.516.211a10 10 0 0 1-.443-.05 9.36 9.36 0 0 1-.062 4.51c-.138.508-.55.848-1.012.964zM11.5 1H8c-.51 0-.863.068-1.14.163-.281.097-.506.229-.776.393l-.04.025c-.555.338-1.198.73-2.49.868-.333.035-.554.29-.554.55V7c0 .255.226.543.62.65 1.095.3 1.977.997 2.614 1.709.635.71 1.064 1.475 1.238 1.977.243.7.407 1.768.482 2.85.025.362.36.595.667.518l.262-.065c.16-.04.258-.144.288-.255a8.34 8.34 0 0 0-.145-4.726.5.5 0 0 1 .595-.643h.003l.014.004.058.013a9 9 0 0 0 1.036.157c.663.06 1.457.054 2.11-.163.175-.059.45-.301.57-.651.107-.308.087-.67-.266-1.021L12.793 7l.353-.354c.043-.042.105-.14.154-.315.048-.167.075-.37.075-.581s-.027-.414-.075-.581c-.05-.174-.111-.273-.154-.315l-.353-.354.353-.354c.047-.047.109-.176.005-.488a2.2 2.2 0 0 0-.505-.804l-.353-.354.353-.354c.006-.005.041-.05.041-.17a.9.9 0 0 0-.121-.415C12.4 1.272 12.063 1 11.5 1"/>
        </svg>`;

        const messageHtml = `
            <li class="${class_ai_item} wpaicg-icon-container" style="background-color:${aiBg};font-size:${fontSize}px;color:${fontColor}">
                <p style="width:100%">
                    <span class="wpaicg-chat-message" id="${chatId}"></span>
                    ${copyEnabled ? `<button class="wpaicg-copy-button" data-chat-id="${chatId}">${emptyClipboardSVG}</button>` : ''}
                    ${feedbackEnabled ? `<button class="wpaicg-thumbs-up-button" data-chat-id="${chatId}">${thumbsUpSVG}</button>
                    <button class="wpaicg-thumbs-down-button" data-chat-id="${chatId}">${thumbsDownSVG}</button>` : ''}
                </p>
            </li>
        `;

        // Buffer to accumulate chunks
        let buffer = '';
        let completeAIResponse = '';
        let dataQueue = [];
        let isProcessing = false;

        function processBuffer() {
            processMarkdown(buffer, true, chatId);
        }

        function typeWriter(text, i, elementId, callback) {
            toggleBlinkingCursor(false);
            if (i < text.length) {
                const charToAdd = text.charAt(i);
                if (charToAdd === '<') {
                    const tag = text.slice(i, i + 4);
                    if (tag === '<br>') {
                        jQuery(`#${elementId}`).append(tag);
                        i += 4;
                    } else {
                        jQuery(`#${elementId}`).append(charToAdd);
                        i++;
                    }
                } else {
                    jQuery(`#${elementId}`).append(charToAdd);
                    i++;
                }
                setTimeout(() => typeWriter(text, i, elementId, callback), 1);
            } else if (callback) {
                callback();
                scrollToBottom();
            }
        }

        function scrollToBottom() {
            wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;
        }

        function processQueue() {
            if (dataQueue.length && !isProcessing) {
                isProcessing = true;
                const nextChunk = dataQueue.shift();
                typeWriter(nextChunk, 0, chatId, () => {
                    isProcessing = false;
                    processQueue();
                });
            } else {
                toggleBlinkingCursor(false);
            }
        }

        function toggleBlinkingCursor(isVisible) {
            const cursorElement = jQuery(`#${chatId} .blinking-cursor`);
            if (isVisible) {
                if (!cursorElement.length) {
                    jQuery(`#${chatId}`).append('<span class="blinking-cursor">|</span>');
                }
            } else {
                cursorElement.remove();
            }
        }

        // Fetch POST request for streaming
        fetch(wpaicgParams.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(wpaicgData).toString(),
        })
            .then(response => response.body)
            .then(async (reader) => {
                const decoder = new TextDecoder();
                const stream = reader.getReader();

                toggleBlinkingCursor(true);
                wpaicg_ai_thinking.style.display = 'none';
                wpaicg_messages_box.innerHTML += messageHtml;

                let partial = '';

                while (true) {
                    const { done, value } = await stream.read();
                    if (done) {
                        toggleBlinkingCursor(false);
                        wpaicg_ai_thinking.style.display = 'none';
                        const simpleChatId = chatId.replace('wpaicg-chat-message-', '');
                        updateChatHistory(completeAIResponse, 'ai', simpleChatId, chat, chatbot_identity, clientID);

                        break;
                    }

                    partial += decoder.decode(value); // Append chunk

                    // Split the partial data by lines
                    const lines = partial.split('\n');

                    for (let i = 0; i < lines.length - 1; i++) {
                        let line = lines[i];
                        if (line.trim() === '' || !line.startsWith('data: ')) {
                            continue;
                        }

                        // Remove 'data: ' prefix
                        line = line.slice(6);

                        // Handle [DONE] signal
                        if (line === "[DONE]") {
                            toggleBlinkingCursor(false);
                            wpaicg_ai_thinking.style.display = 'none';
                            scrollToBottom();
                            const simpleChatId = chatId.replace('wpaicg-chat-message-', '');
                            updateChatHistory(completeAIResponse, 'ai', simpleChatId, chat, chatbot_identity, clientID);

                            if (!localStorage.getItem('wpaicg_lead_form_shown')) {
                                maybeShowLeadForm(chat, chatId);
                                // scroll to the bottom of the chatbox
                                scrollToBottom();
                            }
                            return;
                        }

                        // Try to parse the remaining JSON data
                        try {
                            const resultData = JSON.parse(line);

                            if (resultData.tokenLimitReached || resultData.messageFlagged || resultData.pineconeError || resultData.ipBanned || resultData.modflag) {
                                document.getElementById(chatId).innerHTML = `<span class="wpaicg-chat-message">${resultData.msg}</span>`;
                                wpaicg_ai_thinking.style.display = 'none';
                                toggleBlinkingCursor(false);
                                scrollToBottom();
                                return;
                            }

                            if (resultData.error) {
                                dataQueue.push(resultData.error.message);
                            } else {
                                const content = resultData.choices?.[0]?.delta?.content || resultData.choices?.[0]?.text || '';
                                buffer += content;
                                processBuffer();
                                completeAIResponse += content;
                            }

                            processQueue();
                            scrollToBottom();
                        } catch (err) {
                            console.error('Error parsing JSON:', err, line);
                        }
                    }

                    partial = lines[lines.length - 1]; // Keep last partial line
                }

                // Process any remaining partial data after the loop ends
                if (partial.trim() !== '') {
                    const lines = partial.split('\n');
                    for (let line of lines) {
                        if (line.trim() === '' || !line.startsWith('data: ')) {
                            continue;
                        }

                        // Remove 'data: ' prefix
                        line = line.slice(6);

                        // Handle [DONE] signal
                        if (line === "[DONE]") {
                            toggleBlinkingCursor(false);
                            return;
                        }

                        // Try to parse the remaining JSON data
                        try {
                            const resultData = JSON.parse(line);

                            if (resultData.tokenLimitReached || resultData.messageFlagged || resultData.pineconeError || resultData.ipBanned || resultData.modflag) {
                                document.getElementById(chatId).innerHTML = `<span class="wpaicg-chat-message">${resultData.msg}</span>`;
                                wpaicg_ai_thinking.style.display = 'none';
                                toggleBlinkingCursor(false);
                                scrollToBottom();
                                return;
                            }

                            if (resultData.error) {
                                dataQueue.push(resultData.error.message);
                            } else {
                                const content = resultData.choices?.[0]?.delta?.content || resultData.choices?.[0]?.text || '';
                                buffer += content;
                                processBuffer();
                                completeAIResponse += content;
                            }

                            processQueue();
                            scrollToBottom();
                        } catch (err) {
                            console.error('Error parsing JSON after stream end:', err, line);
                        }
                    }
                }
            })
            .catch(error => {
                console.log("Fetch failed:", error);
                toggleBlinkingCursor(false);
                wpaicg_ai_thinking.style.display = 'none';
            });

        // Setup button listeners for the copy and feedback buttons
        setupButtonListeners(copyEnabled, feedbackEnabled, class_ai_item, emptyClipboardSVG, checkedClipboardSVG, thumbsUpSVG, thumbsDownSVG, showFeedbackModal, aiBg, fontColor, usrBg, chat, wpaicg_nonce, chatbot_identity);

    }

    function handleAssistantStreaming(
        wpaicgData,
        wpaicg_messages_box,
        wpaicg_box_typing,
        wpaicg_ai_thinking,
        class_ai_item,
        chat,
        chatbot_identity,
        clientID,
        wpaicg_nonce
    ) {
        // Remove the lead form if it exists
        const leadFormMessage = chat.querySelector('.wpaicg-lead-form-message');
        if (leadFormMessage) {
            leadFormMessage.remove();
            // Optionally, set 'wpaicg_lead_form_shown' to '1' so it doesn't show again
            localStorage.setItem('wpaicg_lead_form_shown', '1');
        }

        // Retrieve UI configurations
        const fontSize = chat.getAttribute('data-fontsize');
        const aiBg = chat.getAttribute('data-ai-bg-color');
        const fontColor = chat.getAttribute('data-color');
        const usrBg = chat.getAttribute('data-user-bg-color');
        const copyEnabled = chat.getAttribute('data-copy_btn') === "1";
        const feedbackEnabled = chat.getAttribute('data-feedback_btn') === "1";

        // Clear the typing box
        wpaicg_box_typing.value = '';

        // Generate a unique chat ID
        const chatId = `wpaicg-chat-message-${Math.floor(Math.random() * 100000) + 1}`;
        const cleanedChatId = chatId.replace('wpaicg-chat-message-', ''); // Clean the chatId by removing the prefix
        wpaicgData.append('chat_id', cleanedChatId);

        const emptyClipboardSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-copy" viewBox="0 0 16 16">
        <path fill-rule="evenodd" d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z"/>
        </svg>`;
        const checkedClipboardSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-check2" viewBox="0 0 16 16">
        <path d="M13.854 3.646a.5.5 0 0 1 0 .708l-7 7a.5.5 0 0 1-.708 0l-3.5-3.5a.5.5 0 1 1 .708-.708L6.5 10.293l6.646-6.647a.5.5 0 0 1 .708 0"/>
        </svg>`;

        const thumbsUpSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-hand-thumbs-up" viewBox="0 0 16 16">
        <path d="M8.864.046C7.908-.193 7.02.53 6.956 1.466c-.072 1.051-.23 2.016-.428 2.59-.125.36-.479 1.013-1.04 1.639-.557.623-1.282 1.178-2.131 1.41C2.685 7.288 2 7.87 2 8.72v4.001c0 .845.682 1.464 1.448 1.545 1.07.114 1.564.415 2.068.723l.048.03c.272.165.578.348.97.484.397.136.861.217 1.466.217h3.5c.937 0 1.599-.477 1.934-1.064a1.86 1.86 0 0 0 .254-.912c0-.152-.023-.312-.077-.464.201-.263.38-.578.488-.901.11-.33.172-.762.004-1.149.069-.13.12-.269.159-.403.077-.27.113-.568.113-.857 0-.288-.036-.585-.113-.856a2 2 0 0 0-.138-.362 1.9 1.9 0 0 0 .234-1.734c-.206-.592-.682-1.1-1.2-1.272-.847-.282-1.803-.276-2.516-.211a10 10 0 0 0-.443.05 9.4 9.4 0 0 0-.062-4.509A1.38 1.38 0 0 0 9.125.111zM11.5 14.721H8c-.51 0-.863-.069-1.14-.164-.281-.097-.506-.228-.776-.393l-.04-.024c-.555-.339-1.198-.731-2.49-.868-.333-.036-.554-.29-.554-.55V8.72c0-.254.226-.543.62-.65 1.095-.3 1.977-.996 2.614-1.708.635-.71 1.064-1.475 1.238-1.978.243-.7.407-1.768.482-2.85.025-.362.36-.594.667-.518l.262.066c.16.04.258.143.288.255a8.34 8.34 0 0 1-.145 4.725.5.5 0 0 0 .595.644l.003-.001.014-.003.058-.014a9 9 0 0 1 1.036-.157c.663-.06 1.457-.054 2.11.164.175.058.45.3.57.65.107.308.087.67-.266 1.022l-.353.353.353.354c.043.043.105.141.154.315.048.167.075.37.075.581 0 .212-.027.414-.075.582-.05.174-.111.272-.154.315l-.353.353.353.354c.047.047.109.177.005.488a2.2 2.2 0 0 1-.505.805l-.353.353.353.354c.006.005.041.05.041.17a.9.9 0 0 1-.121.416c-.165.288-.503.56-1.066.56z"/>
        </svg>`;
        const thumbsDownSVG = `<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="${fontColor}" class="bi bi-hand-thumbs-down" viewBox="0 0 16 16">
        <path d="M8.864 15.674c-.956.24-1.843-.484-1.908-1.42-.072-1.05-.23-2.015-.428-2.59-.125-.36-.479-1.012-1.04-1.638-.557-.624-1.282-1.179-2.131-1.41C2.685 8.432 2 7.85 2 7V3c0-.845.682-1.464 1.448-1.546 1.07-.113 1.564-.415 2.068-.723l.048-.029c.272-.166.578-.349.97-.484C6.931.08 7.395 0 8 0h3.5c.937 0 1.599.478 1.934 1.064.164.287.254.607.254.913 0 .152-.023.312-.077.464.201.262.38.577.488.9.11.33.172.762.004 1.15.069.13.12.268.159.403.077.27.113.567.113.856s-.036.586-.113.856c-.035.12-.08.244-.138.363.394.571.418 1.2.234 1.733-.206.592-.682 1.1-1.2 1.272-.847.283-1.803.276-2.516.211a10 10 0 0 1-.443-.05 9.36 9.36 0 0 1-.062 4.51c-.138.508-.55.848-1.012.964zM11.5 1H8c-.51 0-.863.068-1.14.163-.281.097-.506.229-.776.393l-.04.025c-.555.338-1.198.73-2.49.868-.333.035-.554.29-.554.55V7c0 .255.226.543.62.65 1.095.3 1.977.997 2.614 1.709.635.71 1.064 1.475 1.238 1.977.243.7.407 1.768.482 2.85.025.362.36.595.667.518l.262-.065c.16-.04.258-.144.288-.255a8.34 8.34 0 0 0-.145-4.726.5.5 0 0 1 .595-.643h.003l.014.004.058.013a9 9 0 0 0 1.036.157c.663.06 1.457.054 2.11-.163.175-.059.45-.301.57-.651.107-.308.087-.67-.266-1.021L12.793 7l.353-.354c.043-.042.105-.14.154-.315.048-.167.075-.37.075-.581s-.027-.414-.075-.581c-.05-.174-.111-.273-.154-.315l-.353-.354.353-.354c.047-.047.109-.176.005-.488a2.2 2.2 0 0 0-.505-.804l-.353-.354.353-.354c.006-.005.041-.05.041-.17a.9.9 0 0 0-.121-.415C12.4 1.272 12.063 1 11.5 1"/>
        </svg>`;

        const messageHtml = `
            <li class="${class_ai_item} wpaicg-icon-container" style="background-color:${aiBg};font-size:${fontSize}px;color:${fontColor}">
                <span class="wpaicg-chat-message" id="${chatId}"></span>
                ${copyEnabled ? `<button class="wpaicg-copy-button" data-chat-id="${chatId}">${emptyClipboardSVG}</button>` : ''}
                ${feedbackEnabled ? `<button class="wpaicg-thumbs-up-button" data-chat-id="${chatId}">${thumbsUpSVG}</button>
                <button class="wpaicg-thumbs-down-button" data-chat-id="${chatId}">${thumbsDownSVG}</button>` : ''}
            </li>
        `;

        let completeAIResponse = '';
        let aiMessageAdded = false;
        let accumulatedBuffer = ''; // Buffer to accumulate chunks
        let doneCalled = false;

        // To store the thread ID logic (multiple threads)
        const botId = chat.getAttribute('data-bot-id') || '0';
        const activeThreadKey = `wpaicg_current_thread_${botId}_${clientID}`;
        const existingThreadKey = localStorage.getItem(activeThreadKey);
        // If we appended a real thread_id in wpaicgData, handle that in the server streaming

        function scrollToBottom() {
            wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;
        }

        function toggleBlinkingCursor(isVisible) {
            const cursorElement = jQuery(`#${chatId} .blinking-cursor`);
            if (isVisible) {
                if (!cursorElement.length) {
                    jQuery(`#${chatId}`).append('<span class="blinking-cursor">|</span>');
                }
            } else {
                cursorElement.remove();
            }
        }


        // Fetch POST request for Assistant API streaming
        fetch(wpaicgParams.ajax_url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams(wpaicgData).toString(),
        })
            .then(response => {
                const reader = response.body.getReader();
                const decoder = new TextDecoder();
                let partial = '';
                let currentEvent = null;

                function read() {
                    return reader.read().then(({ done, value }) => {
                        if (done) {
                            handleDoneEvent();
                            return;
                        }

                        partial += decoder.decode(value, { stream: true });
                        // attempt to parse the partial response as JSON
                        try {
                            const json = JSON.parse(partial);
                            if (json.status === 'error' && json.msg) {
                                handleErrorEvent(json.msg); // use the existing error handler
                                return; // stop further processing
                            }
                        } catch (e) {
                            // ignore JSON parse errors here, continue streaming
                        }
                        const lines = partial.split('\n');

                        for (let i = 0; i < lines.length - 1; i++) {
                            const line = lines[i].trim();
                            if (line === '') continue;

                            if (line.startsWith('event: ')) {
                                currentEvent = line.slice(7).trim();
                            } else if (line.startsWith('data: ')) {
                                const data = line.slice(6).trim();
                                handleAssistantStreamEvent(currentEvent, data);
                            }
                        }

                        partial = lines[lines.length - 1];
                        return read();
                    });
                }

                return read();
            })
            .catch(error => {
                console.log("Fetch failed:", error);
                toggleBlinkingCursor(false);
                wpaicg_ai_thinking.style.display = 'none';
            });

        // Function to handle Assistant API streaming events
        function handleAssistantStreamEvent(event, data) {
            switch (event) {
                case 'thread_id':
                    handleThreadIdEvent(data);
                    break;
                case 'thread.message.delta':
                    handleMessageDeltaEvent(data);
                    break;
                case 'thread.message.completed':
                    handleMessageCompletedEvent(data);
                    break;
                case 'assistant_error':
                    handleErrorEvent(data);
                    break;
                case 'done':
                    handleDoneEvent();
                    break;
                case null:
                    handleErrorEvent(data);
                    break;
            }
        }

        function handleMessageDeltaEvent(data) {
            const deltaData = JSON.parse(data);
            if (deltaData && deltaData.delta && deltaData.delta.content) {
                const contentArray = deltaData.delta.content;
                for (const contentItem of contentArray) {
                    if (contentItem.type === 'text' && contentItem.text && contentItem.text.value) {
                        const messageChunk = contentItem.text.value;
                        if (!aiMessageAdded) {
                            wpaicg_messages_box.innerHTML += messageHtml;
                            aiMessageAdded = true;
                            wpaicg_ai_thinking.style.display = 'none';
                        }

                        // Accumulate chunks
                        accumulatedBuffer += messageChunk;
                        completeAIResponse += messageChunk;

                        // Convert Markdown to HTML
                        const htmlContent = marked.parse(accumulatedBuffer);

                        // Update the chat message content
                        document.getElementById(chatId).innerHTML = htmlContent;

                        scrollToBottom();
                    }
                }
            }
        }

        function handleMessageCompletedEvent(data) {
            toggleBlinkingCursor(false);
            wpaicg_ai_thinking.style.display = 'none';
            scrollToBottom();
        }

        function handleThreadIdEvent(threadId) {
            // store thread id for the active thread localStorage
            if (existingThreadKey) {
                let threadListObj = JSON.parse(localStorage.getItem('wpaicg_thread_list')) || {};
                threadListObj[existingThreadKey] = threadId;
                localStorage.setItem('wpaicg_thread_list', JSON.stringify(threadListObj));
            }
        }

        // Handler for 'assistant_error' events
        function handleErrorEvent(errorData) {
            console.log('Assistant Error:', errorData);

            let errorMessage = 'An unknown error occurred.';

            try {
                // check if errorData is a string and try to parse it as JSON
                if (typeof errorData === 'string') {
                    try {
                        const parsedError = JSON.parse(errorData);
                        errorMessage = parsedError.msg || errorMessage; // use parsed message if available
                    } catch (jsonParseError) {
                        // if parsing fails, fall back to using the raw string
                        errorMessage = errorData;
                    }
                } else if (errorData && errorData.msg) {
                    errorMessage = errorData.msg; // directly use the msg field if it's an object
                }
            } catch (e) {
                console.error('Error handling error data:', e); // log any unexpected issues
            }

            // parse the error message with marked for markdown or URL formatting
            const parsedHtml = marked.parse(errorMessage);

            const chatElement = document.getElementById(chatId);

            if (chatElement) {
                chatElement.innerHTML = `<span class="wpaicg-chat-message">${parsedHtml}</span>`;
            } else {
                // fallback if chatId doesn't exist yet
                wpaicg_messages_box.innerHTML += `
                    <li class="${class_ai_item}" style="background-color:${aiBg};font-size:${fontSize}px;color:${fontColor}">
                        <span class="wpaicg-chat-message">${parsedHtml}</span>
                    </li>`;
            }

            wpaicg_ai_thinking.style.display = 'none';
            toggleBlinkingCursor(false);
            scrollToBottom();
        }


        // Handler for 'done' events
        function handleDoneEvent() {
            if (doneCalled) return;
            doneCalled = true;
            toggleBlinkingCursor(false);
            wpaicg_ai_thinking.style.display = 'none';
            if (!localStorage.getItem('wpaicg_lead_form_shown')) {
                maybeShowLeadForm(chat, chatId);
                scrollToBottom();
            }
            // update chat history
            const simpleChatId = chatId.replace('wpaicg-chat-message-', '');
            updateChatHistory(completeAIResponse, 'ai', simpleChatId, chat, chatbot_identity, clientID);
        }

        // Setup button listeners for the copy and feedback buttons
        setupButtonListeners(
            copyEnabled,
            feedbackEnabled,
            class_ai_item,
            emptyClipboardSVG,
            checkedClipboardSVG,
            thumbsUpSVG,
            thumbsDownSVG,
            showFeedbackModal,
            aiBg,
            fontColor,
            usrBg,
            chat,
            wpaicg_nonce,
            chatbot_identity
        );
    }

    function processMarkdown(inputText, isStream = false, chatId = null) {
        inputText = inputText !== '' ? inputText.trim() : '';

        // parse the markdown using the marked library
        const formattedText = marked.parse(inputText);

        // if in stream mode and chatId exists, update the DOM
        if (isStream && chatId) {
            const element = document.getElementById(chatId);
            if (element) {
                element.innerHTML = formattedText;
            }
        }

        return formattedText;
    }

    // Scroll function to adjust.
    function scrollToAdjust(wpaicg_messages_box) {
        requestAnimationFrame(() => {
            wpaicg_messages_box.scrollTop = wpaicg_messages_box.scrollHeight;
        });
    }

    function wpaicgWriteMessage(wpaicg_messages_box, wpaicg_message, wpaicg_randomnum, wpaicg_response_text, wpaicg_typewriter_effect, wpaicg_typewriter_speed) {
        var chatContainerforLead = wpaicg_messages_box.closest('.wpaicg-chat-shortcode') || wpaicg_messages_box.closest('.wpaicg-chatbox');
        wpaicg_messages_box.insertAdjacentHTML('beforeend', wpaicg_message);
        var wpaicg_current_message = document.getElementById('wpaicg-chat-message-' + wpaicg_randomnum);

        // Ensure the current message is found
        if (wpaicg_current_message) {
            // Get the next sibling element, which should be the audio element
            var nextElement = wpaicg_current_message.closest('li').nextElementSibling;

            // Check if the next element is an audio tag and play it if found
            if (nextElement && nextElement.tagName.toLowerCase() === 'audio') {
                nextElement.play();
            } else {
                console.log('No audio found next to the current message.');
            }
        } else {
            console.log('Current message not found.');
        }

        // Apply formatting to the entire response text first
        var formattedText = marked.parse(wpaicg_response_text);

        if (wpaicg_typewriter_effect) {
            let index = 0; // Starting index of the substring
            function typeWriter() {
                if (index < formattedText.length) {
                    wpaicg_current_message.innerHTML = formattedText.slice(0, index + 1);
                    index++;
                    setTimeout(typeWriter, wpaicg_typewriter_speed);
                    //scroll to the latest message if needed
                    scrollToAdjust(wpaicg_messages_box);
                } else {
                    // Once complete, ensure scrolling if needed
                    scrollToAdjust(wpaicg_messages_box);
                }
            }
            typeWriter(); // Start the typewriter effect

        } else {
            wpaicg_current_message.innerHTML = formattedText;
            // Scroll to the latest message if needed
            scrollToAdjust(wpaicg_messages_box);
        }
        if (!localStorage.getItem('wpaicg_lead_form_shown')) {
            maybeShowLeadForm(chatContainerforLead, 'wpaicg-chat-message-' + wpaicg_randomnum);
            // scroll to the bottom of the chatbox
            scrollToAdjust(wpaicg_messages_box);
        }
    }

    function wpaicgMicEvent(mic) {
        if (mic.classList.contains('wpaicg-recording')) {
            mic.innerHTML = '';
            mic.innerHTML = wpaicgMicIcon;
            mic.classList.remove('wpaicg-recording');
            wpaicgstopChatRecording(mic)
        } else {
            let checkRecording = document.querySelectorAll('.wpaicg-recording');
            if (checkRecording && checkRecording.length) {
                alert('Please finish previous recording');
            } else {
                mic.innerHTML = '';
                mic.innerHTML = wpaicgStopIcon;
                mic.classList.add('wpaicg-recording');
                wpaicgstartChatRecording();
            }
        }
    }
    if (wpaicgChatTyping && wpaicgChatTyping.length) {
        for (let i = 0; i < wpaicgChatTyping.length; i++) {
            wpaicgChatTyping[i].addEventListener('keyup', function (event) {
                if ((event.which === 13 || event.keyCode === 13) && !event.shiftKey) {
                    let parentChat = wpaicgChatTyping[i].closest('.wpaicg-chatbox');
                    let chatTyping = parentChat.querySelectorAll('.wpaicg-chatbox-typing')[0];
                    wpaicgSendChatMessage(parentChat, chatTyping, 'widget');
                }
            })
        }
    }
    if (wpaicgShortcodeTyping && wpaicgShortcodeTyping.length) {
        for (let i = 0; i < wpaicgShortcodeTyping.length; i++) {
            wpaicgShortcodeTyping[i].addEventListener('keyup', function (event) {
                if ((event.which === 13 || event.keyCode === 13) && !event.shiftKey) {
                    let parentChat = wpaicgShortcodeTyping[i].closest('.wpaicg-chat-shortcode');
                    let chatTyping = parentChat.querySelectorAll('.wpaicg-chat-shortcode-typing')[0];
                    wpaicgSendChatMessage(parentChat, chatTyping, 'shortcode');
                }
            })
        }
    }
    if (wpaicgChatSend && wpaicgChatSend.length) {
        for (let i = 0; i < wpaicgChatSend.length; i++) {
            wpaicgChatSend[i].addEventListener('click', function (event) {
                let parentChat = wpaicgChatSend[i].closest('.wpaicg-chatbox');
                let chatTyping = parentChat.querySelectorAll('.wpaicg-chatbox-typing')[0];
                wpaicgSendChatMessage(parentChat, chatTyping, 'widget');
            })
        }
    }
    if (wpaicgShortcodeSend && wpaicgShortcodeSend.length) {
        for (let i = 0; i < wpaicgShortcodeSend.length; i++) {
            wpaicgShortcodeSend[i].addEventListener('click', function (event) {
                let parentChat = wpaicgShortcodeSend[i].closest('.wpaicg-chat-shortcode');
                let chatTyping = parentChat.querySelectorAll('.wpaicg-chat-shortcode-typing')[0];
                wpaicgSendChatMessage(parentChat, chatTyping, 'shortcode');
            })
        }
    }

    if (wpaicgMicBtns && wpaicgMicBtns.length) {
        for (let i = 0; i < wpaicgMicBtns.length; i++) {
            wpaicgMicBtns[i].addEventListener('click', function () {
                wpaicgMicEvent(wpaicgMicBtns[i]);
            });
        }
    }
}

// Initialize Sidebar Toggle
function initSidebarToggle() {
    const toggleButtons = document.querySelectorAll('.wpaicg-sidebar-toggle');
    const savedSidebarState = localStorage.getItem('wpaicg_sidebar_state') || 'closed';

    // Set initial state for all sidebars based on the saved preference.
    document.querySelectorAll('.wpaicg-sidebar').forEach(sidebar => {
        if (savedSidebarState === 'open') {
            sidebar.classList.add('open');
        } else {
            sidebar.classList.remove('open');
        }
    });

    toggleButtons.forEach(toggle => {
        toggle.addEventListener('click', function () {
            const chatContainer = this.closest('.wpaicg-chat-shortcode, .wpaicg-chatbox');
            const sidebar = chatContainer.querySelector('.wpaicg-sidebar');
            sidebar.classList.toggle('open');
            const newState = sidebar.classList.contains('open') ? 'open' : 'closed';
            localStorage.setItem('wpaicg_sidebar_state', newState);
        });

        // Allow toggle via keyboard (Enter key)
        toggle.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.click();
            }
        });
    });
}


/**
 * Builds the list of all conversations in the sidebar for each chat container,
 * sorted by their "last updated" timestamp in descending order.
 * We look for the first user message (prefixed "Human:") to name the conversation.
 */
function loadConversationList() {
    const containers = ['.wpaicg-chat-shortcode', '.wpaicg-chatbox'];
    containers.forEach(containerSelector => {
        const chatContainers = document.querySelectorAll(containerSelector);
        chatContainers.forEach(chatContainer => {
            // Find the sidebar within this container
            const sidebar = chatContainer.querySelector('.wpaicg-sidebar');
            if (!sidebar) return;

            // Conversation list UL inside the sidebar
            const conversationList = sidebar.querySelector('.wpaicg-conversation-list');
            if (!conversationList) return;

            // Clear out any old items
            conversationList.innerHTML = '';

            // Identify which bot
            const botId = chatContainer.getAttribute('data-bot-id') || '0';
            const chatType = chatContainer.getAttribute('data-type') || 'shortcode';

            // Need the clientID from localStorage
            let clientID = localStorage.getItem('wpaicg_chat_client_id');
            if (!clientID) return;

            // Base prefix for conversation keys
            let conversationKeyBase = '';
            if (botId !== '0') {
                // custom bot
                conversationKeyBase = `wpaicg_chat_history_custom_bot_${botId}_${clientID}`;
            } else {
                // default shortcodes or widgets
                conversationKeyBase = `wpaicg_chat_history_${chatType}_${clientID}`;
            }

            // We'll collect all conversations, then sort
            let conversationData = [];

            // Loop through localStorage to find all conversation keys
            for (let i = 0; i < localStorage.length; i++) {
                const key = localStorage.key(i);
                // Must match our base prefix plus the underscore for the index
                if (key.startsWith(conversationKeyBase + '_')) {
                    // Ensure we're looking specifically at the conversation array, not the _timestamp
                    if (key.endsWith('_timestamp')) continue;

                    const rawData = localStorage.getItem(key);
                    if (!rawData) continue;

                    let chatHistory;
                    try {
                        chatHistory = JSON.parse(rawData);
                    } catch (e) {
                        chatHistory = [];
                    }

                    // Default name
                    let title = "Untitled Conversation";

                    // We look specifically for the FIRST user message ("Human:")
                    if (Array.isArray(chatHistory) && chatHistory.length > 0) {
                        for (let j = 0; j < chatHistory.length; j++) {
                            let msg = chatHistory[j].text;
                            if (typeof msg === 'string' && msg.startsWith('Human:')) {
                                // Remove "Human:"
                                const userMsg = msg.replace(/^Human:\s*/, '').trim();
                                title = userMsg.substring(0, 20) + (userMsg.length > 20 ? '…' : '');
                                break; // we found our user message
                            }
                        }
                    }

                    // Check if there's a timestamp
                    const timestampKey = key + '_timestamp';
                    const rawTimestamp = localStorage.getItem(timestampKey);
                    const lastUpdated = rawTimestamp ? parseInt(rawTimestamp, 10) : 0;

                    // Collect
                    conversationData.push({ key, lastUpdated, title });
                }
            }

            // Sort conversationData by lastUpdated descending
            conversationData.sort((a, b) => b.lastUpdated - a.lastUpdated);

            // Now build <li> for each
            conversationData.forEach(item => {
                const li = document.createElement('li');
                li.textContent = item.title;
                li.dataset.conversationKey = item.key;

                // --- NEW CODE: Add a "trash" icon on hover for deletion ---
                const trashIcon = document.createElement('span');
                // Dashicons classes for a trash icon + our custom class
                trashIcon.classList.add('dashicons', 'dashicons-trash', 'wpaicg-delete-icon');
                // On click, remove from local storage & UI
                trashIcon.addEventListener('click', e => {
                    e.stopPropagation(); // Prevent loading the conversation
                    localStorage.removeItem(item.key);
                    localStorage.removeItem(item.key + '_timestamp');
                    li.remove();
                });
                li.appendChild(trashIcon);

                // Clicking the li itself loads the conversation
                li.addEventListener('click', function () {
                    loadSelectedConversation(item.key, chatContainer);
                });

                conversationList.appendChild(li);
            });
        });
    });
}

/**
 * Loads the selected conversation key from localStorage 
 * into the chat UI, and sets it as active for subsequent messages.
 * Also handles assistant thread alignment if assistant is enabled.
 *
 * @param {string} conversationKey - The exact localStorage key for this conversation.
 * @param {HTMLElement} chatContainer - The .wpaicg-chat-shortcode or .wpaicg-chatbox container.
 */
function loadSelectedConversation(conversationKey, chatContainer) {
    // Clear the display
    var chatBox = chatContainer.querySelector('.wpaicg-chatbox-messages, .wpaicg-chat-shortcode-messages');
    if (!chatBox) return;
    chatBox.innerHTML = '';

    // Retrieve the conversation
    var rawHistory = localStorage.getItem(conversationKey);
    if (!rawHistory) return;

    var chatHistory = JSON.parse(rawHistory);
    if (!Array.isArray(chatHistory)) chatHistory = [];

    // Reconstruct the conversation in the UI
    chatHistory.forEach(message => {
        reconstructMessage(chatBox, message, chatContainer);
    });
    // Scroll to bottom
    chatBox.scrollTop = chatBox.scrollHeight;

    // Mark this conversationKey as the current "active" conversation
    const botId = chatContainer.getAttribute('data-bot-id') || '0';
    const clientID = localStorage.getItem('wpaicg_chat_client_id') || '';
    const activeConversationKey = `wpaicg_current_conversation_${botId}_${clientID}`;
    localStorage.setItem(activeConversationKey, conversationKey);

    // If assistant is enabled, we want to align the active thread similarly
    let assistantEnabled = chatContainer.getAttribute('data-assistant-enabled') === 'true';
    if (assistantEnabled) {
        // Example conversationKey:
        // wpaicg_chat_history_custom_bot_4377_dc8XxpH90T_2
        // we want to map the portion after "custom_bot_4377_dc8XxpH90T_" => "2"
        // so the thread key is "custom_bot_4377_dc8XxpH90T_2"
        // or if it's the default short/widget fallback
        let suffixParts = conversationKey.split('_');
        if (suffixParts.length >= 2) {
            // For custom bot conversation keys => wpaicg_chat_history_custom_bot_${botId}_${clientID}_${index}
            // suffixParts might be: [ 'wpaicg','chat','history','custom','bot','4377','dc8XxpH90T','2' ]
            // We'll find the last item => index
            let lastIndex = suffixParts[suffixParts.length - 1];
            // Rebuild the base to "custom_bot_{botId}_{clientID}_{lastIndex}" or for short/widget => "shortcode_{clientID}_{lastIndex}"
            // Actually we can slice from the third piece onward
            // e.g. conversationKeyBase = "custom_bot_4377_dc8XxpH90T_2"
            let conversationKeyBase = suffixParts.slice(3).join('_');
            // e.g. "custom_bot_4377_dc8XxpH90T_2"

            // Create the "activeThreadKey"
            let activeThreadKeyName = `wpaicg_current_thread_${botId}_${clientID}`;
            localStorage.setItem(activeThreadKeyName, conversationKeyBase);
        }
    }
}
// Call the init function when the document is ready
document.addEventListener('DOMContentLoaded', function () {
    wpaicgChatInit();
    loadConversations();
    initSidebarToggle();
    loadConversationList();
    initNewChatButtons();
});