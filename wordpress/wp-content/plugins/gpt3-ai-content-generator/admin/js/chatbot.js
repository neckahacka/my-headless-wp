// -------------------- LOGIC: Handle Chat Bot Creation, Pagination, Loading, and AJAX Refresh --------------------
jQuery(document).ready(function ($) {
    // -------------------- Cached jQuery Selectors --------------------
    const $spinner = $('#aipower-spinner');
    const $message = $('#aipower-message');
    const $botIdField = $('#current-bot-id');
    const $createBotSection = $('#aipower-create-bot-section');
    const $chatboxContainer = $('#aipower-chatbox-container');
    const $chatbotTableContainer = $('#aipower-chatbot-table-container');
    const $deleteModal = $('#aipower-delete-modal');
    const $duplicateModal = $('#aipower-duplicate-modal');
    const $nonce = $('#ai-engine-nonce').val();

    // -------------------- UI FEEDBACK: Spinner and Message Display Functions --------------------
    const UI = {
        showSpinner: () => $spinner.show(),
        hideSpinner: () => $spinner.hide(),
        showMessage: (type, text, autoHide = false) => {
            $message
                .removeClass('error success aipower-autosaving')
                .addClass(type)
                .text(text)
                .fadeIn();

            if (autoHide) {
                setTimeout(() => $message.fadeOut(), 3000);
            }
        },
        showReloadMessage: () => {
            // Display the reload message
            const $reloadMessage = $('#aipower-reload-message');
            if ($reloadMessage.length === 0) {
                $message.after('<p id="aipower-reload-message" class="aipower-reload-warning" style="color: red;">You need to reload the page for the changes to take effect.</p>');
            }
        },
        showAutosaving: () => {
            $spinner.show();
            $message
                .removeClass('error success')
                .addClass('aipower-autosaving')
                .text('Saving...')
                .fadeIn();
        }
    };

    // -------------------- Field Configuration and State Management --------------------
    const fieldConfigurations = [
        {
            name: 'name',
            selector: '#aipower-bot-name',
            type: 'text',
            label: 'Bot Name',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field'
        },
        {
            name: 'provider',
            selector: '#aipower-bot-provider',
            type: 'select',
            label: 'Provider',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'OpenAI'
        },
        {
            name: 'model',
            selector: '#aipower-bot-model',
            type: 'select',
            label: 'Model',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'gpt-4o-mini',
            dependsOn: 'provider'
        },
        {
            name: 'chat_addition',
            selector: '#aipower-chat-addition',
            type: 'checkbox',
            label: 'Instructions',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
            dependencies: {
                show: ['chat_addition_text']
            }
        },
        {
            name: 'chat_addition_text',
            selector: '#aipower-chat-addition-text',
            type: 'textarea',
            label: 'Instructions',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'You are a helpful AI Assistant. Please be friendly. Today\'s date is [date].',
            visibility: {
                dependsOn: 'chat_addition',
                showWhen: '1'
            },
            requiresReload: false
        },
        {
            name: 'openai_stream_nav',
            selector: '#aipower-streaming',
            type: 'checkbox',
            label: 'Streaming',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
            requiresReload: true,
            dependencies: {
                mutuallyExclusive: ['image_enable']
            }
        },
        // New: Image Upload Field
        {
            name: 'image_enable',
            selector: '#aipower-image-upload',
            type: 'checkbox',
            label: 'Image Upload',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            dependencies: {
                mutuallyExclusive: ['openai_stream_nav']
            }
        },
        // New: Bot Type Field
        {
            name: 'type',
            selector: 'input[name="type"]',
            type: 'radio',
            label: 'Bot Type',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'shortcode'
        },
        {
            name: 'pages',
            selector: '#aipower-page-post-id',
            type: 'text',
            label: 'Page/Post IDs',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            visibility: {
                dependsOn: 'type',
                showWhen: 'widget'
            },
            validate_callback: function (value) {
                // Validate that the value is comma-separated integers
                const regex = /^(\d+,)*\d+$/;
                return regex.test(value);
            }
        },
        {
            name: 'position',
            selector: 'input[name="position"]',
            type: 'radio',
            label: 'Widget Position',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'left',
            visibility: {
                dependsOn: 'type',
                showWhen: 'widget'
            },
            validate_callback: function (value) {
                return ['left', 'right'].includes(value);
            }
        },
        {
            name: 'delay_time',
            selector: '#aipower-widget-delay-time',
            type: 'text',
            label: 'Delay',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            visibility: {
                dependsOn: 'type',
                showWhen: 'widget'
            },
        },
        {
            name: 'internet_browsing',
            selector: '#aipower-internet-browsing',
            type: 'checkbox',
            label: 'Internet Browsing',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0'
        },
        {
            name: 'save_logs',
            selector: '#aipower-logs',
            type: 'checkbox',
            label: 'Save Logs',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'log_request',
            selector: '#aipower-save-prompt-details',
            type: 'checkbox',
            label: 'Save Prompt Details',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'log_notice',
            selector: '#aipower-log-notification',
            type: 'checkbox',
            label: 'Log Notification',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'log_notice_message',
            selector: '#aipower-bot-log-notification-message',
            type: 'textarea',
            label: 'Log Notification Message',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Please note that your conversations will be recorded.',
        },
        {
            name: 'moderation',
            selector: '#aipower-enable-moderation',
            type: 'checkbox',
            label: 'Enable Moderation',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0'
        },
        {
            name: 'moderation_model',
            selector: '#aipower-moderation_model',
            type: 'select',
            label: 'Moderation Model',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'text-moderation-latest',
        },
        {
            name: 'moderation_notice',
            selector: '#aipower-bot-moderation-notice',
            type: 'textarea',
            label: 'Moderation Notification',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Your message has been flagged as potentially harmful or inappropriate. Please ensure that your messages are respectful and do not contain language or content that could be offensive or harmful to others.',
        },
        {
            name: 'max_tokens',
            selector: '#aipower-bot-max-tokens',
            type: 'text',
            label: 'Max Tokens',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1500',
            validate_callback: function (value) {
                // Ensure it is a number.
                return !isNaN(value) && value > 0;
            }
        },
        {
            name: 'temperature',
            selector: '#aipower-bot-temperature',
            type: 'text',
            label: 'Temperature',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'frequency_penalty',
            selector: '#aipower-bot-fp',
            type: 'text',
            label: 'Frequency Penalty',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'presence_penalty',
            selector: '#aipower-bot-pp',
            type: 'text',
            label: 'Presence Penalty',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'top_p',
            selector: '#aipower-bot-tp',
            type: 'text',
            label: 'Top P',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'remember_conversation',
            selector: '#aipower-memory',
            type: 'checkbox',
            label: 'Memory',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'yes', // Ensure default is 'yes'
            validate_callback: function (value) {
                return ['yes', 'no'].includes(value);
            }
        },
        {
            name: 'conversation_cut',
            selector: '#aipower-memory-limit',
            type: 'text',
            label: 'Memory Limit',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '100',
        },
        {
            name: 'content_aware',
            selector: '#aipower-content-aware',
            type: 'checkbox',
            label: 'Content Aware',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'yes', // Ensure default is 'yes'
            validate_callback: function (value) {
                return ['yes', 'no'].includes(value);
            }
        },
        {
            name: 'user_aware',
            selector: '#aipower-user-aware',
            type: 'checkbox',
            label: 'User Aware',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'yes',
            validate_callback: function (value) {
                return ['yes', 'no'].includes(value);
            }
        },
        {
            name: 'embedding',
            selector: 'input[name="embedding"]',
            type: 'radio',
            label: 'Data Source',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'vectordb',
            selector: 'input[name="vectordb"]',
            type: 'radio',
            label: 'Vector DB',
            required: false,
            saveOn: 'change',
            defaultValue: '',
            ajaxAction: 'aipower_save_field',
            visibility: {
                dependsOn: 'embedding',
                showWhen: '1'
            },
            validate_callback: function (value) {
                return ['pinecone', 'qdrant'].includes(value);
            }
        },
        {
            name: 'embedding_index',
            selector: '#aipower-bot-pinecone-index',
            type: 'select',
            label: 'Index',
            required: false,
            saveOn: 'change',
            defaultValue: '',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: 'pinecone'
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                }
            ]
        },
        {
            name: 'qdrant_collection',
            selector: '#aipower-bot-qdrant-collection',
            type: 'select',
            label: 'Collection',
            required: false,
            saveOn: 'change',
            defaultValue: '',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: 'qdrant'
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                }
            ]
        },
        {
            name: 'embedding_top',
            selector: '#aipower-bot-embedding-top',
            type: 'select',
            label: 'Query Limit',
            required: false,
            saveOn: 'change',
            defaultValue: '1',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: ['pinecone', 'qdrant']
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                }
            ]
        },
        {
            name: 'embedding_type',
            selector: '#aipower-bot-embedding-type',
            type: 'select',
            label: 'Bot Behavior',
            required: false,
            saveOn: 'change',
            defaultValue: 'openai',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: ['pinecone', 'qdrant']
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                }
            ]
        },
        {
            name: 'confidence_score',
            selector: '#aipower-confidence-score',
            type: 'text',
            label: 'Confidence Score',
            required: false,
            saveOn: 'blur',
            defaultValue: '20',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: ['pinecone', 'qdrant']
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                }
            ]
        },
        {
            name: 'use_default_embedding',
            selector: '#aipower-use-default-embedding',
            type: 'checkbox',
            label: 'Use Default Embedding',
            required: false,
            saveOn: 'change',
            defaultValue: '1',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: ['pinecone', 'qdrant']
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                }
            ]
        },
        {
            name: 'embedding_model',
            selector: '#aipower-bot-embedding-model',
            type: 'select',
            label: 'Embedding Model',
            required: false,
            saveOn: 'change',
            defaultValue: 'text-embedding-ada-002',
            ajaxAction: 'aipower_save_field',
            visibility: [
                {
                    dependsOn: 'vectordb',
                    showWhen: ['pinecone', 'qdrant']
                },
                {
                    dependsOn: 'embedding',
                    showWhen: '1'
                },
                {
                    dependsOn: 'use_default_embedding',
                    showWhen: '0'
                }
            ]
        },
        {
            name: 'feedback_btn',
            selector: '#aipower-feedback',
            type: 'checkbox',
            label: 'Feedback Collection',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'fullscreen',
            selector: '#aipower-fullscreen',
            type: 'checkbox',
            label: 'Fullscreen',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'sidebar',
            selector: '#aipower-sidebar',
            type: 'checkbox',
            label: 'Sidebar',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'download_btn',
            selector: '#aipower-download',
            type: 'checkbox',
            label: 'Download',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'clear_btn',
            selector: '#aipower-clear',
            type: 'checkbox',
            label: 'Clear',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'copy_btn',
            selector: '#aipower-copy',
            type: 'checkbox',
            label: 'Copy',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1'
        },
        {
            name: 'close_btn',
            selector: '#aipower-close-button',
            type: 'checkbox',
            label: 'Close',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
            visibility: {
                dependsOn: 'type',
                showWhen: 'widget'
            }
        },
        {
            name: 'welcome',
            selector: '#aipower-welcome-message',
            type: 'text',
            label: 'Welcome Message',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Hello 👋, how can I help you today?'
        },
        {
            name: 'newchat',
            selector: '#aipower-new-chat',
            type: 'text',
            label: 'New Chat',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'New Chat'
        },
        {
            name: 'ai_thinking',
            selector: '#aipower-response-wait-message',
            type: 'text',
            label: 'Thinking Message',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Gathering thoughts'
        },
        {
            name: 'placeholder',
            selector: '#aipower-placeholder-message',
            type: 'text',
            label: 'Placeholder',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Type your message here...'
        },
        {
            name: 'footer_text',
            selector: '#aipower-footer-note',
            type: 'text',
            label: 'Footer Note',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Powered by AI'
        },
        {
            name: 'feedback_title',
            selector: '#aipower-bot-feedback-title',
            type: 'text',
            label: 'Feedback Title',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Feedback'
        },
        {
            name: 'feedback_message',
            selector: '#aipower-bot-feedback-message',
            type: 'text',
            label: 'Feedback Message',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Please provide details: (optional)'
        },
        {
            name: 'feedback_success',
            selector: '#aipower-bot-feedback-confirmation',
            type: 'text',
            label: 'Feedback Confirmation',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Thank you for your feedback!'
        },
        {
            name: 'embedding_pdf',
            selector: '#aipower-pdf-upload',
            type: 'checkbox',
            label: 'PDF Upload',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0'
        },
        {
            name: 'embedding_pdf_message',
            selector: '#aipower-bot-pdf-upload-confirmation',
            type: 'textarea',
            label: 'PDF Upload Confirmation',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Congrats! Your PDF is uploaded now! You can ask questions about your document. Example Questions:[questions]',
        },
        {
            name: 'pdf_pages',
            selector: '#aipower-bot-pdf-page-limit',
            type: 'text',
            label: 'PDF Page Limit',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '120',
        },
        {
            name: 'fontsize',
            selector: '#aipower-fontsize',
            type: 'text',
            label: 'Font Size',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '13',
        },
        {
            name: 'chat_rounded',
            selector: '#aipower-chat-window-corners',
            type: 'text',
            label: 'Corners',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '8',
        },
        {
            name: 'text_rounded',
            selector: '#aipower-input-field-corners',
            type: 'text',
            label: 'Text Corners',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '8',
        },
        {
            name: 'width',
            selector: '#aipower-chat-window-width',
            type: 'text',
            label: 'Width',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '100%'
        },
        {
            name: 'height',
            selector: '#aipower-chat-window-height',
            type: 'text',
            label: 'Height',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '50%'
        },
        {
            name: 'text_height',
            selector: '#aipower-input-field-height',
            type: 'text',
            label: 'Input Field Height',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '60'
        },
        {
            name: 'pdf_color',
            selector: '#aipower-pdf-icon-color',
            type: 'text',
            label: 'PDF Icon Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#FF0000',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-pdf-icon',
                        property: 'color'
                    }
                ]
            }
        },
        {
            name: 'conversation_starters',
            selector: '#aipower-bot-conversation-starters',
            type: 'textarea',
            label: 'Conversation Starters',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: ['What\’s today\’s date?', 'Can you tell me a joke?', 'What\’s something fun I can do today?'].join('\n'),
        },
        {
            name: 'bgcolor',
            selector: '#aipower-bgcolor',
            type: 'text',
            label: 'Background',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#343A40',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-chat-shortcode',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-chatbox',
                        property: 'background-color'
                    }
                ]
            }
        },
        {
            name: 'fontcolor',
            selector: '#aipower-fontcolor',
            type: 'text',
            label: 'Font Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#E8E8E8',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-user-message',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-chat-user-message',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-conversation-starter',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-ai-message',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-chat-ai-message',
                        property: 'color'
                    },
                ]
            }
        },
        {
            name: 'footer_color',
            selector: '#aipower-footer-color',
            type: 'text',
            label: 'Footer Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#495057',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-chat-shortcode-footer',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-chatbox-footer',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-chat-shortcode-footer',
                        property: 'border-top-color'
                    },
                    {
                        target: '.wpaicg-chatbox-footer',
                        property: 'border-top-color'
                    },
                    {
                        target: '.wpaicg-chatbox-action-bar',
                        property: 'background-color'
                    },
                ]
            }
        },
        {
            name: 'ai_bg_color',
            selector: '#aipower-aibgcolor',
            type: 'text',
            label: 'AI Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#495057',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-ai-message',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-chat-ai-message',
                        property: 'background-color'
                    }
                ]
            }
        },
        {
            name: 'user_bg_color',
            selector: '#aipower-userbgcolor',
            type: 'text',
            label: 'User Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#6C757D',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-user-message',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-chat-user-message',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-conversation-starter',
                        property: 'background-color'
                    },
                ]
            }
        },
        {
            name: 'bg_text_field',
            selector: '#aipower-input-field-bgcolor',
            type: 'text',
            label: 'Input Field Background',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#495057',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-chat-shortcode-typing',
                        property: 'background-color'
                    },
                    {
                        target: '.wpaicg-chatbox-typing',
                        property: 'background-color'
                    }
                ]
            }
        },
        {
            name: 'input_font_color',
            selector: '#aipower-input-field-fontcolor',
            type: 'text',
            label: 'Input Field Font Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#F8F9FA',
            realtimePreview: {
                css: [
                    {
                        target: 'textarea.wpaicg-chat-shortcode-typing, textarea.auto-expand',
                        property: 'color'
                    },
                    {
                        target: 'textarea.wpaicg-chatbox-typing, textarea.auto-expand',
                        property: 'color'
                    }
                ],
                custom: function (value) {
                    // Update the placeholder color
                    updatePlaceholderColor(value);
                }
            }
        },
        {
            name: 'border_text_field',
            selector: '#aipower-input-border-color',
            type: 'text',
            label: 'Border Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#6C757D',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-chat-shortcode-typing',
                        property: 'border-color'
                    },
                    {
                        target: '.wpaicg-chatbox-typing',
                        property: 'border-color'
                    }
                ]
            }
        },
        {
            name: 'send_color',
            selector: '#aipower-send-button-color',
            type: 'text',
            label: 'Button Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#F8F9FA',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-chat-shortcode-send',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-chatbox-send',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-img-icon',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-chat-shortcode-type',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-chatbox-type',
                        property: 'color'
                    },
                ]
            }
        },
        {
            name: 'mic_color',
            selector: '#aipower-mic-icon-color',
            type: 'text',
            label: 'Mic Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#F8F9FA',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-mic-icon',
                        property: 'color'
                    }
                ]
            }
        },
        {
            name: 'stop_color',
            selector: '#aipower-mic-icon-stop-color',
            type: 'text',
            label: 'Stop Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#F8F9FA',
        },
        {
            name: 'footer_font_color',
            selector: '#aipower-footer-fontcolor',
            type: 'text',
            label: 'Footer Font Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#FFFFFF',
            realtimePreview: {
                css: [
                    {
                        target: '.wpaicg-chat-shortcode-footer',
                        property: 'color'
                    },
                    {
                        target: '.wpaicg-chatbox-footer',
                        property: 'color'
                    }
                ]
            }
        },
        {
            name: 'bar_color',
            selector: '#aipower-header-iconcolor',
            type: 'text',
            label: 'Header Icon Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#FFFFFF',
            realtimePreview: {
                css: {
                    target: '.wpaicg-chatbox-action-bar',
                    property: 'color'
                }
            }
        },
        {
            name: 'thinking_color',
            selector: '#aipower-loading-response-color',
            type: 'text',
            label: 'Loading Response Color',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '#CED4DA',
            realtimePreview: {
                css: {
                    target: '.wpaicg-bot-thinking',
                    property: 'color'
                }
            }
        },
        {
            name: 'icon',
            selector: 'input[name="icon"]',
            type: 'radio',
            label: 'AI Icon',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'default',
            visibility:
            {
                dependsOn: 'type',
                showWhen: 'widget'
            },
            validate_callback: function (value) {
                return ['default', 'custom'].includes(value);
            },
        },
        {
            name: 'icon_url',
            selector: '#aipower-icon-url',
            type: 'text', // Changed from 'hidden' to 'text' as per your requirements
            label: 'Icon URL',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            visibility: [{
                dependsOn: 'icon',
                showWhen: 'custom' // Show only when 'icon' is 'custom'
            },
            {
                dependsOn: 'type',
                showWhen: 'widget'
            }]
        },
        {
            name: 'audio_enable',
            selector: '#aipower-speech-to-text',
            type: 'checkbox',
            label: 'Speech to Text',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            requiresReload: true,
        },
        {
            name: 'chat_to_speech',
            selector: '#aipower-text-to-speech',
            type: 'checkbox',
            label: 'Text to Speech',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            requiresReload: true,
        },
        {
            name: 'audio_btn',
            selector: '#aipower-text-to-speech-allow-user',
            type: 'checkbox',
            label: 'Allow Users to Mute',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            requiresReload: true,
        },
        {
            name: 'muted_by_default',
            selector: '#aipower-text-to-speech-muted',
            type: 'checkbox',
            label: 'Muted by Default',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            requiresReload: true,
        },
        {
            name: 'voice_service',
            selector: '#aipower-voice-provider',
            type: 'select',
            label: 'Voice Provider',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'openai',
            requiresReload: true,
            validate_callback: function (value) {
                return ['openai', 'elevenlabs', 'google'].includes(value);
            }
        },
        {
            name: 'openai_model',
            selector: '#aipower-openai-voice-model',
            type: 'select',
            label: 'Voice Model',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'tts-1',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'openai'
            },
            validate_callback: function (value) {
                return ['tts-1', 'tts-1-hd'].includes(value);
            }
        },
        {
            name: 'openai_voice',
            selector: '#aipower-openai-voice',
            type: 'select',
            label: 'Voice',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'alloy',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'openai'
            },
            validate_callback: function (value) {
                return ['alloy', 'echo', 'fable', 'onyx', 'nova', 'shimmer'].includes(value);
            }
        },
        {
            name: 'openai_output_format',
            selector: '#aipower-openai-format',
            type: 'select',
            label: 'Output Format',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'mp3',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'openai'
            },
            validate_callback: function (value) {
                return ['mp3', 'opus', 'aac', 'flac', 'wav', 'pcm'].includes(value);
            }
        },
        {
            name: 'openai_voice_speed',
            selector: '#aipower-openai-voice-speed',
            type: 'text',
            label: 'Voice Speed',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'openai'
            },
        },
        {
            name: 'elevenlabs_model',
            selector: '#aipower-elevenlabs-voice-model',
            type: 'select',
            label: 'Voice Model',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'elevenlabs'
            },
        },
        {
            name: 'elevenlabs_voice',
            selector: '#aipower-elevenlabs-voice',
            type: 'select',
            label: 'Voice',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'elevenlabs'
            },
        },
        {
            name: 'voice_language',
            selector: '#aipower-google-language',
            type: 'select',
            label: 'Language',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'en-US|en-US-Wavenet-A',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'google'
            },
        },
        {
            name: 'voice_name',
            selector: '#aipower-google-voice',
            type: 'select',
            label: 'Voice',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'google'
            },
        },
        {
            name: 'voice_device',
            selector: '#aipower-google-device',
            type: 'select',
            label: 'Device',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'google'
            },
        },
        {
            name: 'voice_speed',
            selector: '#aipower-google-voice-speed',
            type: 'text',
            label: 'Voice Speed',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'google'
            },
        },
        {
            name: 'voice_pitch',
            selector: '#aipower-google-voice-pitch',
            type: 'text',
            label: 'Voice Pitch',
            required: false,
            saveOn: 'blur',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            requiresReload: true,
            visibility: {
                dependsOn: 'voice_service',
                showWhen: 'google'
            },
        },
        {
            name: 'user_limited',
            selector: '#aipower-limit-registered-users',
            type: 'checkbox',
            label: 'Limit Registered Users',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            dependencies: {
                mutuallyExclusive: ['role_limited']
            }
        },
        {
            name: 'user_tokens',
            selector: '#aipower-registered-users-token-limit',
            type: 'text',
            label: 'Token Limit',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            visibility: {
                dependsOn: 'user_limited',
                showWhen: '1'
            },
        },
        {
            name: 'role_limited',
            selector: '#aipower-role-based-limit',
            type: 'checkbox',
            label: 'Role Based Limit',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            dependencies: {
                mutuallyExclusive: ['user_limited']
            }
        },
        {
            name: 'guest_limited',
            selector: '#aipower-limit-non-registered-users',
            type: 'checkbox',
            label: 'Limit Guests',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
        },
        {
            name: 'guest_tokens',
            selector: '#aipower-non-registered-users-token-limit',
            type: 'text',
            label: 'Token Limit',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
            visibility: {
                dependsOn: 'guest_limited',
                showWhen: '1'
            },
        },
        {
            name: 'reset_limit',
            selector: '#aipower-reset-interval',
            type: 'text',
            label: 'Reset Interval',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'limited_message',
            selector: '#aipower-token-notification',
            type: 'text',
            label: 'Token Limit Message',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'You have reached your token limit.',
        },
        {
            name: 'language',
            selector: '#aipower-bot-language',
            type: 'text',
            label: 'Language',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'en',
        },
        {
            name: 'tone',
            selector: '#aipower-bot-tone',
            type: 'text',
            label: 'Tone',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'friendly',
        },
        {
            name: 'proffesion',
            selector: '#aipower-bot-proffesion',
            type: 'text',
            label: 'Proffesion',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'none',
        },
        {
            name: 'lead_collection',
            selector: '#aipower-lead-collection',
            type: 'checkbox',
            label: 'Lead Collection',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '0',
        },
        {
            name: 'lead_title',
            selector: '#aipower-bot-lead-title',
            type: 'text',
            label: 'Title',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Let us know how to contact you'
        },
        {
            name: 'enable_lead_name',
            selector: '#aipower-enable-lead-name',
            type: 'checkbox',
            label: 'Collect Name',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
        },
        {
            name: 'lead_name',
            selector: '#aipower-bot-lead-name',
            type: 'text',
            label: 'Name',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Name',
            visibility: {
                dependsOn: 'enable_lead_name',
                showWhen: '1'
            },
        },
        {
            name: 'enable_lead_email',
            selector: '#aipower-enable-lead-email',
            type: 'checkbox',
            label: 'Collect Email',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
        },
        {
            name: 'lead_email',
            selector: '#aipower-bot-lead-email',
            type: 'text',
            label: 'Email',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Email',
            visibility: {
                dependsOn: 'enable_lead_email',
                showWhen: '1'
            },
        },
        {
            name: 'enable_lead_phone',
            selector: '#aipower-enable-lead-phone',
            type: 'checkbox',
            label: 'Collect Phone',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: '1',
        },
        {
            name: 'lead_phone',
            selector: '#aipower-bot-lead-phone',
            type: 'text',
            label: 'Phone',
            required: false,
            saveOn: 'change',
            ajaxAction: 'aipower_save_field',
            defaultValue: 'Phone',
            visibility: {
                dependsOn: 'enable_lead_phone',
                showWhen: '1'
            },
        },

    ];

    const yesNoFields = ['remember_conversation', 'content_aware', 'user_aware']; // Add more fields as needed

    // Function to enable or disable Streaming and Image Upload based on provider
    function updateFeatureAvailability(provider) {
        if (provider === 'Google') {
            // Disable Streaming
            const streamingField = fieldConfigurations.find(f => f.name === 'openai_stream_nav');
            if (streamingField) {
                setFieldValue(streamingField, '0', true); // Uncheck without triggering event
                $(streamingField.selector).prop('disabled', true); // Disable in UI
                handleFieldSave(streamingField); // Save the change
            }

            // Disable Image Upload
            const imageUploadField = fieldConfigurations.find(f => f.name === 'image_enable');
            if (imageUploadField) {
                setFieldValue(imageUploadField, '0', true); // Uncheck without triggering event
                $(imageUploadField.selector).prop('disabled', true); // Disable in UI
                handleFieldSave(imageUploadField); // Save the change
            }
        } else {
            // Enable Streaming
            const streamingField = fieldConfigurations.find(f => f.name === 'openai_stream_nav');
            if (streamingField) {
                $(streamingField.selector).prop('disabled', false); // **Enable in UI**
                // Optionally, set to default value or leave as is
            }

            // Enable Image Upload
            const imageUploadField = fieldConfigurations.find(f => f.name === 'image_enable');
            if (imageUploadField) {
                $(imageUploadField.selector).prop('disabled', false); // **Enable in UI**
                // Optionally, set to default value or leave as is
            }
        }
    }

    const lastSavedValues = {};

    // Initialize lastSavedValues with default values or current field values
    fieldConfigurations.forEach(field => {
        if (field.type === 'radio') {
            lastSavedValues[field.name] = $('input[name="' + field.name + '"]:checked').val() || field.defaultValue;
        } else if (field.type === 'checkbox') {
            if (yesNoFields.includes(field.name)) {
                lastSavedValues[field.name] = $(`${field.selector}`).is(':checked') ? 'yes' : 'no';
            } else {
                lastSavedValues[field.name] = field.defaultValue !== undefined ? field.defaultValue : '';
            }
        } else {
            lastSavedValues[field.name] = field.defaultValue !== undefined ? field.defaultValue : '';
        }
    });

    // -------------------- Utility Functions --------------------
    function getCurrentBotId() {
        return $botIdField.val() || null;
    }

    // Function to toggle mutually exclusive fields
    function toggleMutuallyExclusive(fieldName, currentValue) {
        const fieldConfig = fieldConfigurations.find(f => f.name === fieldName);
        if (!fieldConfig || !fieldConfig.dependencies || !fieldConfig.dependencies.mutuallyExclusive) return;

        fieldConfig.dependencies.mutuallyExclusive.forEach(exclusiveFieldName => {
            const exclusiveFieldConfig = fieldConfigurations.find(f => f.name === exclusiveFieldName);
            if (exclusiveFieldConfig) {
                const exclusiveValue = getFieldValue(exclusiveFieldConfig);
                if (currentValue === '1' && exclusiveValue === '1') {
                    // Disable the exclusive field
                    setFieldValue(exclusiveFieldConfig, '0');
                    // Optionally, save the change immediately
                    handleFieldSave(exclusiveFieldConfig);
                }
            }
        });
    }

    function getFieldValue(field) {
        const $element = $(field.selector);
        if (!$element.length) {
            console.warn(`Field ${field.name} (${field.selector}) does not exist.`);
            return '';
        }

        switch (field.type) {
            case 'text':
            case 'textarea':
            case 'select':
                return $element.val() ? $element.val().trim() : '';
            case 'checkbox':
                if (yesNoFields.includes(field.name)) {
                    return $element.is(':checked') ? 'yes' : 'no';
                }
                return $element.is(':checked') ? '1' : '0';
            case 'radio':
                return $('input[name="' + field.name + '"]:checked').val() || '';
            // Add cases for other types as needed
            default:
                return $element.val() ? $element.val().trim() : '';
        }
    }

    function setFieldValue(field, value, triggerEvent = true) {
        const $element = $(field.selector);
        if (!$element.length) return;

        switch (field.type) {
            case 'text':
            case 'textarea':
                $element.val(value);
                // Trigger the input event to update the output element
                $element.trigger('input');
                break;
            case 'select':
                $element.val(value);
                if (triggerEvent) {
                    $element.trigger('change');
                }
                break;
            case 'checkbox':
                if (yesNoFields.includes(field.name)) {
                    $element.prop('checked', value === 'yes');
                } else {
                    $element.prop('checked', value === '1');
                }
                if (triggerEvent) {
                    $element.trigger('change');
                }
                break;
            case 'radio':
                $('input[name="' + field.name + '"]').each(function () {
                    if ($(this).val() === value) {
                        $(this).prop('checked', true);
                    } else {
                        $(this).prop('checked', false);
                    }
                });
                if (triggerEvent) {
                    $('input[name="' + field.name + '"]').trigger('change');
                }
                break;
            // Add cases for other types as needed
            default:
                $element.val(value);
        }
    }

    function validateField(field, value) {
        if (field.required && !value) {
            return `Please enter a valid ${field.label}.`;
        }
        // If field has validate_callback, execute it
        if (typeof field.validate_callback === 'function') {
            const isValid = field.validate_callback(value);
            if (!isValid) {
                return `Invalid value for ${field.label}.`;
            }
        }
        // Add more validation rules as needed
        return null;
    }


    function debounce(func, wait) {
        let timeout;
        return function (...args) {
            clearTimeout(timeout);
            timeout = setTimeout(() => func.apply(this, args), wait);
        };
    }

    function getCurrentPage() {
        return parseInt($chatbotTableContainer.data('current-page')) || 1;
    }

    function resetBotCreationForm() {
        // Clear the bot ID
        $botIdField.val('');
        // Show the bot name field
        $('#aipower-bot-name').closest('.aipower-form-group').show();

        // hide the shortcode message
        $('#aipower-shortcode-display').remove();

        // Reset form fields to default values without triggering events
        fieldConfigurations.forEach(field => {
            // Special handling for the Azure provider case
            updateModelDropdown($('#aipower-bot-provider').val());
            setFieldValue(field, field.defaultValue !== undefined ? field.defaultValue : '', false);
        });

        // Reset lastSavedValues
        fieldConfigurations.forEach(field => {
            if (field.type === 'radio') {
                lastSavedValues[field.name] = $('input[name="' + field.name + '"]:checked').val() || field.defaultValue;
            } else {
                lastSavedValues[field.name] = field.defaultValue !== undefined ? field.defaultValue : '';
            }
        });

        // After resetting fields, update dependencies and visibility
        fieldConfigurations.forEach(field => {
            updateFieldDependencies(field);
            updateFieldVisibility(field);
        });
    }

    // -------------------- AJAX Helper Function --------------------
    function ajaxPost(data, onSuccess, onError = () => { }) {
        $.post(ajaxurl, data, onSuccess)
            .fail(() => {
                onError('Failed to connect to the server. Please try again.');
            });
    }

    // **NEW CODE: Implement a savingFields lock**
    const savingFields = {};

    // -------------------- Autosave Handling --------------------
    const autosaveField = (field, value, callback, onError = () => { }) => {
        if (savingFields[field.name]) {
            // Field is already being saved; skip this save to prevent overlap
            return;
        }

        savingFields[field.name] = true; // Lock the field to prevent concurrent saves

        UI.showAutosaving();

        // **NEW CODE: Disable related fields to prevent overlapping changes**
        if (field.name === 'provider') {
            $('#aipower-bot-model').prop('disabled', true);
        }
        if (field.name === 'model') {
            $('#aipower-bot-provider').prop('disabled', true);
        }


        // Set default name if empty
        if (field.name === 'name' && !value) {
            const randomnumber = Math.floor(Math.random() * 100);
            value = 'My Bot ' + randomnumber;
            $(field.selector).val(value); // Update the field in the UI
        }

        const data = {
            action: field.ajaxAction,
            field: field.name,
            value,
            _wpnonce: $nonce
        };

        const botId = getCurrentBotId();
        if (botId) {
            data.bot_id = botId;
        }

        ajaxPost(data, (response) => {
            savingFields[field.name] = false; // Unlock the field after save
            // **NEW CODE: Re-enable related fields after saving**
            if (field.name === 'provider') {
                $('#aipower-bot-model').prop('disabled', false);
            }
            if (field.name === 'model') {
                $('#aipower-bot-provider').prop('disabled', false);
            }
            if (response.success) {
                UI.showMessage('success', response.data.message || 'Settings saved successfully.', true);
                lastSavedValues[field.name] = value;
                // **NEW CODE: If saving conversation starters, update the switch**
                if (field.name === 'conversation_starters') {
                    updateConversationStartersSwitch();
                }

                // If saving the bot name and a new bot was created
                if (field.name === 'name' && response.data.bot_id) {
                    $botIdField.val(response.data.bot_id); // Store the bot ID
                    // Pass the new bot ID to refreshPaginationState
                    refreshPaginationState(response.data.bot_id);

                    const shortcode = `[wpaicg_chatgpt id=${response.data.bot_id}]`;
                    const $shortcodeMessage = `<p id="aipower-shortcode-display">Use this: <code style="font-size: 12px;background: #f1f1f1;color: #333333;border-radius: 3px;cursor: pointer;">${shortcode}</code></p>`;
                    // Display the shortcode message after the button
                    $('#aipower-done-editing-btn').after($shortcodeMessage);
                }
                // If bot is being edited (botId exists), refresh pagination
                if (botId) {
                    debouncedRefreshPagination();
                }

                // **NEW CODE: Handle 'type' field changes to show/hide shortcode message**
                if (field.name === 'type') {
                    if (value === 'widget') {
                        // Hide the shortcode message
                        $('#aipower-shortcode-display').remove();
                    } else if (value === 'shortcode') {
                        // Optionally, show the shortcode message again
                        const shortcode = botId ? `[wpaicg_chatgpt id=${botId}]` : '[wpaicg_chatgpt]';
                        const $shortcodeMessage = `<p id="aipower-shortcode-display">Use this shortcode: <code>${shortcode}</code></p>`;
                        // Display the shortcode message after the button, only if it's not already present
                        if (!$('#aipower-shortcode-display').length) {
                            $('#aipower-done-editing-btn').after($shortcodeMessage);
                        }
                    }
                }

                // **NEW CODE: Handle mutual exclusivity**
                if (field.dependencies && field.dependencies.mutuallyExclusive) {
                    field.dependencies.mutuallyExclusive.forEach(exclusiveFieldName => {
                        const exclusiveFieldConfig = fieldConfigurations.find(f => f.name === exclusiveFieldName);
                        if (exclusiveFieldConfig && value === '1') {
                            // Turn off the mutually exclusive field
                            setFieldValue(exclusiveFieldConfig, '0');
                            handleFieldSave(exclusiveFieldConfig);
                        }
                    });
                }

                // Execute the callback if provided
                if (typeof callback === 'function') {
                    callback();
                }
            } else {
                UI.showMessage('error', response.data.message || 'An error occurred while saving the settings.');
            }
        }, (errorMsg) => {
            savingFields[field.name] = false; // Unlock the field on error
            // **NEW CODE: Re-enable related fields if save fails**
            if (field.name === 'provider') {
                $('#aipower-bot-model').prop('disabled', false);
            }
            if (field.name === 'model') {
                $('#aipower-bot-provider').prop('disabled', false);
            }
            UI.showMessage('error', errorMsg);
            if (typeof onError === 'function') {
                onError(errorMsg);
            }
        });
    };


    const debouncedRefreshPagination = debounce(() => {
        const currentPage = getCurrentPage();
        refreshChatbotTable(currentPage);
    }, 500); // 500ms delay

    // -------------------- Define Provider to Models Mapping --------------------
    // Get the default models element
    const defaultModelsElement = document.getElementById('default-models');

    // Default models for each provider
    const defaultModels = {
        'OpenAI': [
            { value: defaultModelsElement.dataset.openaiDefault || 'gpt-4o-mini', label: 'GPT-4o Mini' }
        ],
        'OpenRouter': [
            { value: defaultModelsElement.dataset.openrouterDefault || 'anthropic/claude-3.5-sonnet', label: 'Claude 3.5 Sonnet' }
        ],
        'Google': [
            { value: defaultModelsElement.dataset.googleDefault || 'gemini-pro', label: 'Gemini Pro' }
        ],
        'Azure': [
            { value: defaultModelsElement.dataset.azureDefault || '', label: 'Azure Default' }
        ]
    };

    function updateModelDropdown(selectedProvider) {
        let $modelField = $('#aipower-bot-model');

        // Check if the current field is an input, and if so, replace it with a select
        if ($modelField.is('input')) {
            $modelField.replaceWith('<select id="aipower-bot-model" name="aipower-bot-model"></select>');
            $modelField = $('#aipower-bot-model');  // Re-select the new element
        }

        // Clear existing options
        $modelField.empty();

        if (selectedProvider === 'OpenAI') {
            // 1. Add the "Assistants" optgroup
            const $assistantsOptgroup = $('<optgroup label="Assistants"></optgroup>');
            const $assistantsDiv = $('#openai-assistants');
            let assistantsData = $assistantsDiv.attr('data-assistants');

            // Parse the JSON string into a JavaScript object
            try {
                assistantsData = JSON.parse(assistantsData);
            } catch (e) {
                console.error('Failed to parse assistants data:', e);
                assistantsData = [];
            }

            if (Array.isArray(assistantsData) && assistantsData.length > 0) {
                assistantsData.forEach(function (assistant) {
                    // Check if 'name' exists; if not, fallback to 'assistant_id' or a default label
                    let displayName = 'Unnamed Assistant';
                    if (assistant.name && assistant.name.trim() !== '') {
                        displayName = assistant.name;
                    } else if (assistant.assistant_id) {
                        displayName = assistant.assistant_id;
                    }

                    // Append the assistant as an option with name as display text
                    $assistantsOptgroup.append('<option value="' + assistant.assistant_id + '">' + displayName + '</option>');
                });
            } else {
                // If no assistants, add a disabled option with a helpful message
                $assistantsOptgroup.append('<option disabled>You don\'t have any assistants. Click the sync button to fetch your assistants.</option>');
            }

            // Append the "Assistants" optgroup to the model dropdown
            $modelField.append($assistantsOptgroup);

            // 2. Append existing OpenAI models

            const openaiModelsDiv = $('#openai-models');
            let gpt4Models = openaiModelsDiv.attr('data-gpt4-models');
            let gpt35Models = openaiModelsDiv.attr('data-gpt35-models');
            let customModels = openaiModelsDiv.attr('data-custom-models');

            // Parse the JSON strings
            try {
                gpt4Models = JSON.parse(gpt4Models);
            } catch (e) {
                console.error('Failed to parse GPT-4 models:', e);
                gpt4Models = [];
            }

            try {
                gpt35Models = JSON.parse(gpt35Models);
            } catch (e) {
                console.error('Failed to parse GPT-3.5 models:', e);
                gpt35Models = [];
            }

            try {
                customModels = JSON.parse(customModels);
            } catch (e) {
                console.error('Failed to parse custom models:', e);
                customModels = [];
            }

            // Append GPT-3.5 Models
            if (gpt35Models && Object.keys(gpt35Models).length > 0) {
                const $gpt35Optgroup = $('<optgroup label="GPT-3.5 Models"></optgroup>');
                $.each(gpt35Models, function (key, label) {
                    $gpt35Optgroup.append('<option value="' + key + '">' + label + '</option>');
                });
                $modelField.append($gpt35Optgroup);
            }

            // Append GPT-4 Models
            if (gpt4Models && Object.keys(gpt4Models).length > 0) {
                const $gpt4Optgroup = $('<optgroup label="GPT-4 Models"></optgroup>');
                $.each(gpt4Models, function (key, label) {
                    $gpt4Optgroup.append('<option value="' + key + '">' + label + '</option>');
                });
                $modelField.append($gpt4Optgroup);
            }

            // Append Custom Models
            if (customModels && customModels.length > 0) {
                const $customOptgroup = $('<optgroup label="Custom Models"></optgroup>');
                $.each(customModels, function (index, value) {
                    $customOptgroup.append('<option value="' + value + '">' + value + '</option>');
                });
                $modelField.append($customOptgroup);
            }

        } else if (selectedProvider === 'OpenRouter') {
            const openrouterModelsDiv = $('#openrouter-models');
            const openrouterModels = JSON.parse(openrouterModelsDiv.attr('data-models'));

            if (openrouterModels && openrouterModels.length > 0) {
                $.each(openrouterModels, function (index, model) {
                    const provider = model.id.split('/')[0];
                    let $optgroup = $modelField.find('optgroup[label="' + provider + '"]');
                    if ($optgroup.length === 0) {
                        $optgroup = $('<optgroup label="' + provider + '"></optgroup>');
                        $modelField.append($optgroup);
                    }
                    $optgroup.append('<option value="' + model.id + '">' + model.name + '</option>');
                });
            } else {
                // Append default model when openrouterModels is empty or undefined
                const defaultModel = defaultModels['OpenRouter'][0].value;
                $modelField.append('<option value="' + defaultModel + '">' + defaultModel.replace(/-/g, ' ') + '</option>');
            }
        } else if (selectedProvider === 'Google') {
            const googleModelsDiv = $('#google-models');
            const googleModels = JSON.parse(googleModelsDiv.attr('data-models'));

            if (googleModels && googleModels.length > 0) {
                $.each(googleModels, function (index, model) {
                    $modelField.append('<option value="' + model + '">' + model.replace(/-/g, ' ') + '</option>');
                });
            } else {
                // Append default model when googleModels is empty or undefined
                const defaultModel = defaultModels['Google'][0].value;
                $modelField.append('<option value="' + defaultModel + '">' + defaultModel.replace(/-/g, ' ') + '</option>');
            }
        } else if (selectedProvider === 'Azure') {
            // Azure logic
            if ($modelField.is('select')) {
                const currentValue = $modelField.val() || '';
                const $input = $('<input>', {
                    type: 'text',
                    id: 'aipower-bot-model',
                    name: 'aipower-bot-model',
                    placeholder: 'Enter Azure model...'
                }).val(currentValue);
                $modelField.replaceWith($input);
                $modelField = $input;  // Update the reference
            }
        }

        // If no models are available, add a default option
        if ($modelField.is('select') && $modelField.find('option').length === 0) {
            $modelField.append('<option value="">No models available</option>');
        }

        // Select the default model for the provider
        if (defaultModels[selectedProvider] && defaultModels[selectedProvider].length > 0) {
            const defaultModel = defaultModels[selectedProvider][0].value;
            if ($modelField.is('select')) {
                $modelField.val(defaultModel);
            } else if ($modelField.is('input')) {
                $modelField.val(defaultModel);
            }
        }

        // Update field configuration and attach events
        const modelFieldConfig = fieldConfigurations.find(f => f.name === 'model');
        if (modelFieldConfig) {
            modelFieldConfig.type = selectedProvider === 'Azure' ? 'text' : 'select';
            attachFieldEvents(modelFieldConfig);
        }

        // Show/hide sync icons based on the selected provider
        $('.aipower-bot-settings-icon').hide(); // Hide all sync icons first

        if (selectedProvider === 'OpenAI') {
            $('#aipower_sync_openai_models_bot').show();
        } else if (selectedProvider === 'OpenRouter') {
            $('#aipower_sync_openrouter_models_bot').show();
        } else if (selectedProvider === 'Google') {
            $('#aipower_sync_google_models_bot').show();
        }
    }


    // -------------------- Field Initialization --------------------
    fieldConfigurations.forEach(field => {
        const $field = $(field.selector);
        if (!$field.length) return;

        // Set default value
        setFieldValue(field, field.defaultValue !== undefined ? field.defaultValue : '');

        // Attach event handlers
        attachFieldEvents(field);

        // Update dependencies and visibility
        updateFieldDependencies(field);
        updateFieldVisibility(field);
    });
    // **NEW CODE: Refresh visibility after all fields are initialized**
    refreshAllVisibility();

    // **NEW CODE: Debounce handleFieldSave to prevent rapid multiple saves**
    const debouncedHandleFieldSave = debounce(handleFieldSave, 300); // 300ms debounce

    // Function to attach event handlers to fields
    function attachFieldEvents(field) {
        const $field = $(field.selector);

        if (field.saveOn) {
            $field.on(field.saveOn, function () {
                debouncedHandleFieldSave(field);
            });
        }

        // Handle dependencies
        if (field.dependencies || field.visibility) {
            $field.on('change', function () {
                updateFieldDependencies(field);
                updateFieldVisibility(field);
                // Handle mutual exclusivity
                if (field.dependencies && field.dependencies.mutuallyExclusive) {
                    const currentValue = getFieldValue(field);
                    toggleMutuallyExclusive(field.name, currentValue);
                }
            });
        }
        // **NEW CODE: If this field is a dependency for others, refresh visibility on change**
        // For example, if this field is 'type', changing it should refresh visibility of dependent fields
        const dependentFields = fieldConfigurations.filter(f => f.visibility && f.visibility.dependsOn === field.name);
        if (dependentFields.length > 0) {
            $field.on('change', function () {
                refreshAllVisibility();
            });
        }

        // **REVISED CODE: Handle Real-Time Preview on 'input' Event**
        if (field.realtimePreview) {
            $field.on('input', function () {
                const value = getFieldValue(field);
                handleRealtimePreview(field, value);
            });
        }
    }

    // **REVISED CODE: Modify handleFieldSave to handle 'provider' sequentially**
    function handleFieldSave(field) {
        const value = getFieldValue(field);
        const lastValue = lastSavedValues[field.name] || '';

        if (value === lastValue) return; // No change detected

        const validationError = validateField(field, value);
        if (validationError) {
            UI.showMessage('error', validationError);
            return;
        }

        if (field.name === 'provider') {
            // **NEW CODE: Update model dropdown immediately**
            updateModelDropdown(value);

            // **OPTIONAL: Set a default model value**
            const modelField = fieldConfigurations.find(f => f.name === 'model');
            const defaultModel = defaultModels[value]?.[0]?.value || '';
            setFieldValue(modelField, defaultModel, false); // Set model value without triggering 'change'

            // **NEW CODE: Save provider first**
            autosaveField(field, value, () => {
                // **NEW CODE: After provider is saved, save model**
                handleFieldSave(modelField);
                updateFeatureAvailability(value);
                // **Refresh visibility or other dependent UI elements**
                refreshAllVisibility();
            }, (errorMsg) => {
                // **NEW CODE: Handle error if provider save fails**
                UI.showMessage('error', errorMsg);

                // **NEW CODE: Revert model dropdown to previous provider's models**
                updateModelDropdown(lastSavedValues['provider']);

                // **OPTIONAL: Revert model field value**
                const modelField = fieldConfigurations.find(f => f.name === 'model');
                setFieldValue(modelField, lastSavedValues['model'] || modelField.defaultValue, false);
            });
        } else {
            // For all other fields, proceed with autosave
            autosaveField(field, value, () => {
                refreshAllVisibility();
            }, (errorMsg) => {
                UI.showMessage('error', errorMsg);
            });
        }
    }


    // Function to update field dependencies
    function updateFieldDependencies(field) {
        if (field.dependencies) {
            const value = getFieldValue(field);
            field.dependencies.show?.forEach(depFieldName => {
                const depFieldConfig = fieldConfigurations.find(f => f.name === depFieldName);
                if (depFieldConfig) {
                    const $depField = $(depFieldConfig.selector);
                    if (value === '1') {
                        $depField.closest('.aipower-form-group').show();
                    } else {
                        $depField.closest('.aipower-form-group').hide();
                    }
                }
            });
            // You can add 'hide' dependencies or other logic as needed
        }
    }

    // Function to update field visibility based on other fields
    function updateFieldVisibility(field) {
        if (field.visibility) {
            let isVisible = true;

            // Check if visibility is an array of conditions
            if (Array.isArray(field.visibility)) {
                field.visibility.forEach(condition => {
                    const dependsOnField = fieldConfigurations.find(f => f.name === condition.dependsOn);
                    if (!dependsOnField) {
                        console.warn(`Dependency field "${condition.dependsOn}" not found for "${field.name}".`);
                        isVisible = false;
                        return;
                    }

                    const dependsOnValue = getFieldValue(dependsOnField);

                    // If showWhen is an array, check if the value is included
                    if (Array.isArray(condition.showWhen)) {
                        if (!condition.showWhen.includes(dependsOnValue)) {
                            isVisible = false;
                        }
                    } else {
                        // For single value in showWhen
                        if (dependsOnValue !== condition.showWhen) {
                            isVisible = false;
                        }
                    }
                });
            } else {
                // Existing single visibility condition
                const { dependsOn, showWhen } = field.visibility;
                const dependsOnField = fieldConfigurations.find(f => f.name === dependsOn);
                if (!dependsOnField) {
                    console.warn(`Dependency field "${dependsOn}" not found for "${field.name}".`);
                    isVisible = false;
                } else {
                    const dependsOnValue = getFieldValue(dependsOnField);

                    if (Array.isArray(showWhen)) {
                        isVisible = showWhen.includes(dependsOnValue);
                    } else {
                        isVisible = dependsOnValue === showWhen;
                    }
                }
            }

            const $field = $(field.selector);
            if (isVisible) {
                $field.closest('.aipower-form-group').show();
            } else {
                $field.closest('.aipower-form-group').hide();
            }
        }
    }

    function refreshAllVisibility() {
        fieldConfigurations.forEach(field => {
            if (field.visibility) {
                updateFieldVisibility(field);
            }
        });
    }

    // -------------------- Real-Time Preview Handling --------------------
    function handleRealtimePreview(field, value) {
        if (field.realtimePreview && field.realtimePreview.css) {
            if (Array.isArray(field.realtimePreview.css)) {
                field.realtimePreview.css.forEach(({ target, property }) => {
                    $(target).css(property, value);
                });
            } else {
                const { target, property } = field.realtimePreview.css;
                $(target).css(property, value);
            }
        }

        if (field.realtimePreview && field.realtimePreview.custom && typeof field.realtimePreview.custom === 'function') {
            field.realtimePreview.custom(value);
        }
    }

    // -------------------- Pagination Handling --------------------
    const refreshChatbotTable = (page = 1, newBotId = null) => {
        UI.showSpinner();
        const data = {
            action: 'aipower_refresh_chatbot_table',
            page,
            _wpnonce: $nonce
        };

        ajaxPost(data, (response) => {
            if (response.success) {
                $chatbotTableContainer.html(response.data.table);
                // Update current page data attribute
                $chatbotTableContainer.data('current-page', page);
                UI.hideSpinner();

                // Now set the active state if newBotId is provided
                if (newBotId) {
                    // Remove 'active' class from other icons
                    $('.aipower-preview-btn, .aipower-preview-icon, .aipower-edit-icon').removeClass('active');
                    // Add 'active' class to the new bot's icons
                    $(`.aipower-preview-btn[data-id="${newBotId}"], .aipower-preview-icon[data-id="${newBotId}"], .aipower-edit-icon[data-id="${newBotId}"]`).addClass('active');

                    // Get the bot name from the form
                    const botName = $('#aipower-bot-name').val() || 'Chatbot';

                    // Display the chatbox for the new bot
                    displayChatbox({ id: newBotId, type: 'bot', name: botName });
                }
            } else {
                UI.showMessage('error', response.data.message || 'Failed to refresh the chatbot table.');
                UI.hideSpinner();
            }
        }, (errorMsg) => {
            UI.showMessage('error', errorMsg);
            UI.hideSpinner();
        });
    };

    const refreshPaginationState = (newBotId = null) => {
        const currentPage = getCurrentPage();
        refreshChatbotTable(currentPage, newBotId);
    };

    // Event listener for pagination buttons
    $(document).on('click', '.aipower-page-btn', function (e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !isNaN(page)) {
            refreshChatbotTable(page);
        }
    });

    // -------------------- Chatbox Display Handling --------------------
    function displayChatbox({ id = '0', type = 'shortcode', name = 'Chatbot' } = {}) {
        $chatboxContainer.html('<div id="aipower-spinner">Loading ' + name + '...</div>'); // Display loading message with bot name

        const dataPayload = {
            action: 'aipower_load_chatbot',
            type,
            bot_id: id,
            _wpnonce: $nonce
        };

        ajaxPost(dataPayload, (response) => {
            if (response.success) {
                $chatboxContainer.html(response.data.chatbox); // Replace with chatbox content
                const functionNames = [
                    'wpaicgChatInit',       // Reinitialize chat JS if necessary
                    'loadConversations',    // Initialize conversation starter buttons
                    'initSidebarToggle',    // Initialize sidebar toggle
                    'loadConversationList', // Initialize conversation list
                    'initNewChatButtons'    // Initialize new chat buttons
                  ];
                  
                  functionNames.forEach(name => {
                    const fn = window[name]; // Adjust if your functions live on another object
                    if (typeof fn === 'function') {
                      fn();
                    }
                  });
                  
                // **New Code: Automatically toggle all widgets found in the chatbox**
                $chatboxContainer.find('.wpaicg_chat_widget').each(function () {
                    const $widget = $(this);
                    // Check if the widget is not already open
                    if (!$widget.hasClass('wpaicg_widget_open')) {
                        const $toggleBtn = $widget.find('.wpaicg_toggle');
                        if ($toggleBtn.length) {
                            $toggleBtn.trigger('click'); // Trigger click to open the widget
                        }
                    }
                });
            } else {
                $chatboxContainer.html('<p>Error loading chatbot</p>');
            }
        }, () => {
            $chatboxContainer.html('<p>Failed to load chatbot</p>');
        });
    }

    // -------------------- Initial Setup --------------------
    // Initial Load: Display Default Shortcode Chatbot
    displayChatbox({ id: '0', type: 'shortcode', name: 'Default Shortcode' });

    // Set 'active' class to default shortcode's preview and edit icons on initial load
    $('.aipower-preview-btn, .aipower-preview-icon, .aipower-edit-icon').removeClass('active');
    $('.aipower-preview-btn[data-id="0"], .aipower-preview-icon[data-id="0"], .aipower-edit-icon[data-id="0"]').addClass('active');

    // **NEW CODE: Initialize Model dropdown based on the default Provider selection on page load**
    const initialProvider = $('#aipower-bot-provider').val();
    updateModelDropdown(initialProvider);
    updateFeatureAvailability(initialProvider);
    // **NEW CODE: Initialize Conversation Starters Switch on Page Load**
    updateConversationStartersSwitch();

    // Handle "Preview" Button Clicks
    $(document).on('click', '.aipower-preview-btn, .aipower-preview-icon', function (e) {
        e.preventDefault();
        const botId = $(this).data('id');
        const type = $(this).data('type') || 'bot'; // Default to 'bot' if type not set
        const botName = $(this).data('name') || 'Chatbot'; // Get bot name

        // Remove 'active' class from all preview icons
        $('.aipower-preview-btn, .aipower-preview-icon').removeClass('active');
        // Add 'active' class to the clicked preview icon
        $(`.aipower-preview-btn[data-id="${botId}"], .aipower-preview-icon[data-id="${botId}"]`).addClass('active');

        displayChatbox({ id: botId, type, name: botName });
    });

    // -------------------- Bot Creation Handling --------------------
    $('#aipower-add-new-bot-btn').on('click', function () {
        // Always reset the form for a new bot
        resetBotCreationForm();

        // Show the bot creation section if it's not visible
        if (!$createBotSection.is(':visible')) {
            $createBotSection.show();
        }

        // Always reset the bot ID when creating a new bot
        $botIdField.val('');

        // Remove 'active' class from preview and edit icons
        $('.aipower-preview-btn, .aipower-preview-icon, .aipower-edit-icon').removeClass('active');

        // Trigger autosave for 'name' field
        const nameFieldConfig = fieldConfigurations.find(field => field.name === 'name');
        if (nameFieldConfig) {
            const value = getFieldValue(nameFieldConfig);
            autosaveField(nameFieldConfig, value);
        }

        // Hide "Create New Bot" button and show "Done Editing" button
        $('#aipower-add-new-bot-btn').hide();
        $('#aipower-done-editing-btn').show();
    });

    // -------------------- Handle "Done Editing" Button Click --------------------
    $('#aipower-done-editing-btn').on('click', function () {
        // Close the accordion
        $createBotSection.hide();

        // Clear the bot ID
        $botIdField.val('');

        // Hide "Done Editing" button and show "Create New Bot" button
        $('#aipower-done-editing-btn').hide();
        $('#aipower-add-new-bot-btn').show();

        // Reset the form fields
        resetBotCreationForm();
    });

    // -------------------- Delete Confirmation Modal Handling --------------------
    let chatbotToDelete = null;

    // Show delete confirmation modal only when delete button is clicked
    $(document).on('click', '.aipower-delete-icon', function (e) {
        e.preventDefault();
        chatbotToDelete = $(this).data('id'); // Store chatbot ID to delete
        $deleteModal.fadeIn(); // Show the confirmation modal
    });

    // Toggle the custom tools menu display when the tools icon is clicked
    $(document).on('click', '#aipower-custom-tools-icon', function (e) {
        e.preventDefault();
        var $menu = $(this).next('.aipower-custom-tools-menu'); // Use .next() to select the adjacent menu

        // Hide any other open menus
        $('.aipower-custom-tools-menu').not($menu).hide();

        // Toggle the visibility of the current menu
        $menu.toggle();

        // Position the menu relative to the icon
        $menu.css({
            top: $(this).position().top + $(this).outerHeight(), // Align below the icon
            left: $(this).position().left // Align to the left of the icon
        });
    });

    // Close the custom tools menu when clicking outside of it
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#aipower-custom-tools-icon, .aipower-custom-tools-menu').length) {
            $('.aipower-custom-tools-menu').hide(); // Hide the menu if clicked outside
        }
    });

    // Close modal when 'Cancel' is clicked
    $('#aipower-cancel-delete-btn, .aipower-close').on('click', function () {
        chatbotToDelete = null; // Reset chatbot ID
        $deleteModal.fadeOut(); // Hide modal
    });

    // Handle chatbot deletion
    $('#aipower-confirm-delete-btn').on('click', function (e) {
        e.preventDefault();
        if (!chatbotToDelete) return;

        UI.showSpinner();
        $deleteModal.fadeOut();

        const data = {
            action: 'aipower_delete_chatbot',
            chatbot_id: chatbotToDelete,
            _wpnonce: $nonce
        };

        ajaxPost(data, (response) => {
            if (response.success) {
                refreshPaginationState(); // Update pagination after deleting a bot
                $createBotSection.slideUp();
                UI.showMessage('success', 'Chatbot deleted successfully.', true);
            } else {
                UI.showMessage('error', response.data.message || 'Failed to delete chatbot.');
            }
            UI.hideSpinner();
        }, (errorMsg) => {
            UI.showMessage('error', errorMsg);
            UI.hideSpinner();
        });
    });

    // -------------------- Duplicate Confirmation Modal Handling --------------------
    let chatbotToDuplicate = null;

    // Show duplicate confirmation modal when duplicate button is clicked
    $(document).on('click', '.aipower-duplicate-icon', function (e) {
        e.preventDefault();
        chatbotToDuplicate = $(this).data('id'); // Store chatbot ID to duplicate
        $duplicateModal.fadeIn(); // Show the duplication confirmation modal
    });

    // Close the duplicate modal when 'Cancel' is clicked
    $('#aipower-cancel-duplicate-btn, #aipower-duplicate-modal .aipower-close').on('click', function () {
        chatbotToDuplicate = null; // Reset chatbot ID
        $duplicateModal.fadeOut(); // Hide modal
    });

    // Handle chatbot duplication
    $(document).on('click', '#aipower-confirm-duplicate-btn', function (e) {
        e.preventDefault();
        if (!chatbotToDuplicate) return;

        UI.showSpinner();
        $duplicateModal.fadeOut();

        const data = {
            action: 'aipower_duplicate_chatbot',
            chatbot_id: chatbotToDuplicate,
            _wpnonce: $nonce
        };

        ajaxPost(data, (response) => {
            if (response.success) {
                refreshPaginationState(response.data.new_bot_id); // Update pagination after duplicating a bot
                UI.showMessage('success', 'Chatbot duplicated successfully.', true);
            } else {
                UI.showMessage('error', response.data.message || 'Failed to duplicate chatbot.');
            }
            UI.hideSpinner();
        }
            , (errorMsg) => {
                UI.showMessage('error', errorMsg);
                UI.hideSpinner();
            }
        );
    });

    // --------------------  Export Single Chatbot Handling --------------------
    // Variable to store the chatbot ID to export
    var chatbotToExport = null;

    $(document).on('click', '.aipower-export-icon', function (e) {
        e.preventDefault();
        var botId = $(this).data('id');
        chatbotToExport = botId;

        // Show modal confirmation
        $('#aipower-export-modal').fadeIn();
    });

    // Close Export Modal
    $('#aipower-export-modal .aipower-close, #aipower-cancel-export-btn').on('click', function () {
        chatbotToExport = null; // Reset chatbot ID
        $('#aipower-export-modal').fadeOut(); // Hide modal
    });

    // Confirm Export Single Bot
    $(document).on('click', '#aipower-confirm-export-btn', function (e) {
        e.preventDefault();
        if (!chatbotToExport) return;

        UI.showSpinner();
        $('#aipower-export-modal').fadeOut();

        const data = {
            action: 'aipower_export_bots',
            export_type: 'single',
            bot_id: chatbotToExport,
            _wpnonce: $('#ai-engine-nonce').val()
        };

        ajaxPost(data, function (response) {
            if (response.success) {
                // Trigger download
                var downloadLink = document.createElement('a');
                downloadLink.href = response.data.file_url;
                downloadLink.download = response.data.filename;
                document.body.appendChild(downloadLink);
                downloadLink.click();
                document.body.removeChild(downloadLink);

                // Show success message
                UI.showMessage('success', 'Chatbot exported successfully.', true);
            } else {
                // Show error message
                UI.showMessage('error', response.data.message || 'Failed to export chatbot.');
            }
            UI.hideSpinner();
        }, function (errorMsg) {
            // Show error message
            UI.showMessage('error', errorMsg || 'An error occurred while exporting the chatbot.');
            UI.hideSpinner();
        });
    });

    /**
     * Loads and displays a custom attachment (icon) preview.
     *
     * @param {boolean} isCustom - Determines if a custom attachment should be loaded.
     * @param {string|number} attachmentId - The ID of the attachment to load.
     * @param {string} previewSelector - The jQuery selector for the preview element.
     * @param {string} errorMessage - The error message to display if loading fails.
     */
    function loadCustomPreview(isCustom, attachmentId, previewSelector, errorMessage) {
        if (isCustom && attachmentId) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aipower_get_attachment_url',
                    attachment_id: attachmentId,
                    _wpnonce: $nonce
                },
                success: function (response) {
                    if (response.success && response.data.url) {
                        $(previewSelector).html('<img src="' + response.data.url + '" style="max-width:100px; height:auto;" />');
                    } else {
                        $(previewSelector).html('<p style="color: red;">' + (response.data.message || 'Error loading attachment.') + '</p>');
                    }
                },
                error: function () {
                    $(previewSelector).html('<p style="color: red;">' + errorMessage + '</p>');
                }
            });
        } else {
            $(previewSelector).empty(); // Clear the preview if conditions are not met
        }
    }

    // -------------------- Handle "Edit" Button Clicks --------------------
    $(document).on('click', '.aipower-edit-icon', function (e, silent = false) {
        e.preventDefault();
        const botId = $(this).data('id');
        const botType = $(this).data('bot-type');

        // Hide the previous shortcode message
        $('#aipower-shortcode-display').remove();

        // If bot type is 'shortcode' or botId is 0, display the shortcode message
        if (botType === 'Shortcode' || botId == 0) {
            const shortcode = botId == 0 ? '[wpaicg_chatgpt]' : `[wpaicg_chatgpt id=${botId}]`;
            const $shortcodeMessage = `<p id="aipower-shortcode-display">Use this shortcode: <code>${shortcode}</code></p>`;
            $('#aipower-done-editing-btn').after($shortcodeMessage);
        }

        if (botId == 0 || botId == -1) {
            $('#aipower-bot-name').closest('.aipower-form-group').hide();
        } else {
            $('#aipower-bot-name').closest('.aipower-form-group').show();
        }

        if (![0, -1].includes(botId) && botId < 0) {
            UI.showMessage('error', 'Invalid Bot ID.');
            return;
        }

        // Skip spinner and message if silent mode is active
        if (!silent) {
            UI.showSpinner();
            UI.showMessage('aipower-autosaving', 'Loading bot data...');
        }

        const data = {
            action: 'aipower_get_bot_data',
            bot_id: botId,
            _wpnonce: $nonce
        };

        ajaxPost(data, (response) => {
            if (response.success) {
                const { bot_data: botData, type: botType } = response.data;

                fieldConfigurations.forEach(field => {
                    const fieldName = field.name;

                    if (botData.hasOwnProperty(fieldName)) {
                        setFieldValue(field, botData[fieldName], false); // Set triggerEvent to false
                        lastSavedValues[fieldName] = botData[fieldName];
                    } else {
                        setFieldValue(field, field.defaultValue !== undefined ? field.defaultValue : '', false); // Set triggerEvent to false
                        lastSavedValues[fieldName] = field.defaultValue !== undefined ? field.defaultValue : '';
                    }

                    // Check if it's the provider field and update the model dropdown accordingly
                    if (field.name === 'provider') {
                        updateModelDropdown(botData[fieldName]); // Call updateModelDropdown with the provider's value
                    }

                    // **NEW CODE: After setting conversation starters, update the switch**
                    if (field.name === 'conversation_starters') {
                        const conversationStarters = botData[fieldName] || [];
                        const $conversationStartersField = $('#aipower-bot-conversation-starters');
                        if ($conversationStartersField.length) {
                            $conversationStartersField.val(Array.isArray(conversationStarters) ? conversationStarters.join('\n') : '');
                        }
                        updateConversationStartersSwitch(); // Update the switch based on starters
                    }

                    // **NEW CODE START: Handle "type" field based on botType**
                    const typeFieldConfig = fieldConfigurations.find(f => f.name === 'type');
                    if (typeFieldConfig) {
                        const $typeFieldContainer = $(typeFieldConfig.selector).closest('.aipower-form-group');

                        if (botType === 'shortcode') {
                            // 1. Hide the "type" field completely
                            $typeFieldContainer.hide();
                        } else if (botType === 'widget') {
                            // 2. Show the "type" field and disable its inputs
                            $typeFieldContainer.show();
                            $(typeFieldConfig.selector).prop('disabled', true);
                            // disable pages field #aipower-page-post-id
                            $('#aipower-page-post-id').prop('disabled', true);
                        } else {
                            // For any other botType, ensure the "type" field is visible and enabled
                            $typeFieldContainer.show();
                            $(typeFieldConfig.selector).prop('disabled', false);
                        }
                    }

                    // Update dependencies and visibility
                    updateFieldDependencies(field);
                    updateFieldVisibility(field);
                });

                // Populate the role limits in the modal
                if (botData.limited_roles) {
                    $('#bot-role-limits-modal .role-limit-input').each(function () {
                        const role = $(this).data('role');
                        if (botData.limited_roles.hasOwnProperty(role)) {
                            $(this).val(botData.limited_roles[role]);
                        } else {
                            $(this).val(''); // Clear if not set
                        }
                    });
                } else {
                    // Clear the inputs if no limited_roles data
                    $('#bot-role-limits-modal .role-limit-input').val('');
                }

                updateMainSpeechSwitch(); // Update the main speech switch based on the 'main_speech' field
                $botIdField.val(botId);

                if (!$createBotSection.is(':visible')) {
                    $createBotSection.show();
                }

                $('.aipower-accordion-btn').removeClass('active');
                $('.aipower-accordion-panel').hide();

                $('.aipower-accordion-btn').first().addClass('active');
                $('.aipower-accordion-panel').first().show();

                // **NEW CODE: Display custom icon thumbnails if set to 'custom'**
                loadCustomPreview(
                    botData.icon === 'custom',
                    botData.icon_url,
                    '#aipower-icon-preview',
                    'Failed to load the custom icon.'
                );

                // Skip message if silent mode is active
                if (!silent) {
                    UI.showMessage('success', 'Bot data loaded for editing.', true);
                }

                let previewType = 'bot';
                let botName = botData.name || 'Chatbot';

                if (botId == 0) {
                    previewType = 'shortcode';
                    botName = 'Default Shortcode';
                } else if (botId == -1) {
                    previewType = 'widget';
                    botName = 'Default Widget';
                }

                displayChatbox({ id: botId, type: previewType, name: botName });

                $('.aipower-preview-btn, .aipower-preview-icon, .aipower-edit-icon').removeClass('active');
                $(`.aipower-preview-btn[data-id="${botId}"], .aipower-preview-icon[data-id="${botId}"], .aipower-edit-icon[data-id="${botId}"]`).addClass('active');
                // Refresh the switch state after loading bot data
                updateConversationStartersSwitch();
            } else {
                UI.showMessage('error', response.data.message || 'Failed to load bot data.');
            }

            // Hide spinner if not in silent mode
            if (!silent) {
                UI.hideSpinner();
            }
        }, (errorMsg) => {
            UI.showMessage('error', errorMsg);
            if (!silent) {
                UI.hideSpinner();
            }
        });
        // Show "Done Editing" button and hide "Create New Bot" button
        $('#aipower-add-new-bot-btn').hide();
        $('#aipower-done-editing-btn').show();
    });

    // -------------------- LOGIC: Handle Chat Bot Accordions --------------------
    // By default, ensure General Settings is visible when accordion is triggered
    $('.aipower-accordion-btn').first().addClass('active'); // Activate the first button (General Settings)
    $('.aipower-accordion-panel').first().show(); // Show the first panel (General Settings)

    // Handle accordion toggle
    $('.aipower-accordion-btn').on('click', function () {
        // Toggle active class for clicked button
        $(this).toggleClass('active');
        // Toggle visibility of the corresponding panel
        $(this).next('.aipower-accordion-panel').slideToggle();

        // Hide others (optional, for single accordion open behavior)
        $('.aipower-accordion-btn').not(this).removeClass('active');
        $('.aipower-accordion-panel').not($(this).next()).slideUp();
    });

    // Attach events for fields that require a reload message
    fieldConfigurations.forEach(field => {
        if (field.requiresReload) {
            const $field = $(field.selector);
            if ($field.length) {
                $field.on('change', function () {
                    // Trigger autosave and show the reload message after a delay
                    setTimeout(UI.showReloadMessage, 5000); // Show reload message after a 5-second delay
                    // call th displayChatbox function
                    const botnametocall = $('#aipower-bot-name').val();
                    displayChatbox({ id: getCurrentBotId(), type: 'bot', name: botnametocall });
                });
            }
        }
    });
    // -------------------- LOGIC: Handle Chat Bot Tools Menu --------------------
    const $toolsIcon = $('#aipower-tools-icon');
    const $toolsMenu = $('#aipower-tools-menu');
    const $deleteAllBtn = $('#aipower-delete-all-btn');
    const $exportAllBtn = $('#aipower-export-all-btn');
    const $resetBtn = $('#aipower-reset-btn');
    const $importBtn = $('#aipower-import-btn');
    const $confirmation = $('#aipower-confirmation');
    const $confirmYes = $('#aipower-confirm-yes');

    // Variable to track the current action ('delete_all' or 'export_all')
    let currentAction = null;

    // Show/hide tools menu and hide confirmation when icon is clicked
    $toolsIcon.on('click', function () {
        $toolsMenu.toggle();
        $confirmation.hide(); // Hide confirmation when clicking the tools icon again
    });

    // Close the menu when clicking outside
    $(document).on('click', function (e) {
        if (!$(e.target).closest('#aipower-tools-icon, #aipower-tools-menu').length) {
            $toolsMenu.hide();
            $confirmation.hide(); // Hide confirmation as well when clicking outside
        }
    });

    // Handle delete all action (show inline confirmation)
    $deleteAllBtn.on('click', function () {
        $toolsMenu.hide(); // Hide the menu
        currentAction = 'delete_all';
        $confirmation.show(); // Show the confirmation
    });

    // Handle export all action (show inline confirmation)
    $exportAllBtn.on('click', function () {
        $toolsMenu.hide(); // Hide the menu
        currentAction = 'export_all';
        $confirmation.show(); // Show the confirmation
    });

    // Handle reset action (reload the page)
    $resetBtn.on('click', function () {
        $toolsMenu.hide(); // Hide the menu
        currentAction = 'reset';
        $confirmation.show();
    });

    // Handle import action (trigger file input)
    $importBtn.on('click', function () {
        $toolsMenu.hide(); // Hide the menu
        $('#aipower-import-file-input').click(); // Trigger the hidden file input
    });

    // Confirm action when "Yes" is clicked both for Delete All and Export All
    $confirmYes.on('click', function () {
        if (currentAction === 'delete_all') {
            // -------------------- Handle Delete All --------------------
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aipower_delete_all_bots',
                    _wpnonce: $('#ai-engine-nonce').val()
                },
                beforeSend: function () {
                    UI.showSpinner(); // Show spinner during deletion
                },
                success: function (response) {
                    if (response.success) {
                        UI.showMessage('success', 'All bots deleted successfully except default ones.', true);
                        refreshChatbotTable(); // Reload the chatbot table without page refresh
                        // Reset the bot creation form
                        resetBotCreationForm();
                    } else {
                        UI.showMessage('error', response.data.message || 'Failed to delete all bots.');
                    }
                },
                error: function () {
                    UI.showMessage('error', 'Failed to connect to the server. Please try again.');
                },
                complete: function () {
                    UI.hideSpinner(); // Hide spinner after completion
                    $confirmation.hide(); // Hide the confirmation text
                    currentAction = null; // Reset the action
                }
            });
        } else if (currentAction === 'export_all') {
            // -------------------- Handle Export All --------------------
            var data = {
                action: 'aipower_export_bots',
                export_type: 'all',
                _wpnonce: $('#ai-engine-nonce').val()
            };

            UI.showSpinner(); // Show spinner during export

            ajaxPost(data, function (response) {
                if (response.success) {
                    // Trigger download
                    var downloadLink = document.createElement('a');
                    downloadLink.href = response.data.file_url;
                    downloadLink.download = response.data.filename;
                    document.body.appendChild(downloadLink);
                    downloadLink.click();
                    document.body.removeChild(downloadLink);

                    // Show success message
                    UI.showMessage('success', 'All chatbots exported successfully.', true);
                } else {
                    // Show error message
                    UI.showMessage('error', response.data.message || 'Failed to export chatbots.');
                }
                // Hide spinner and confirmation
                UI.hideSpinner();
                $confirmation.hide();
                currentAction = null; // Reset the action
            }, function (errorMsg) {
                // Show error message
                UI.showMessage('error', errorMsg || 'An error occurred while exporting chatbots.');
                // Hide spinner and confirmation
                UI.hideSpinner();
                $confirmation.hide();
                currentAction = null; // Reset the action
            });
        } else if (currentAction === 'reset') {
            // -------------------- Handle Reset --------------------
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aipower_reset_settings',
                    _wpnonce: $('#ai-engine-nonce').val()
                },
                beforeSend: function () {
                    UI.showSpinner(); // Show spinner during reset
                },
                success: function (response) {
                    if (response.success) {
                        UI.showMessage('success', 'Default shortcode and site-wide widget reset successfully.', true);
                        UI.showReloadMessage(); // Inform the user to reload the page
                        // reloading the page now
                        setTimeout(() => {
                            location.reload();
                        }
                            , 3000);
                    } else {
                        UI.showMessage('error', response.data.message || 'Failed to reset chatbots.');
                    }
                },
                error: function () {
                    UI.showMessage('error', 'Failed to connect to the server. Please try again.');
                },
                complete: function () {
                    UI.hideSpinner(); // Hide spinner after completion
                    $confirmation.hide(); // Hide the confirmation text
                    currentAction = null; // Reset the action
                }
            });
        }
    });

    // -------------------- Import All Chatbots Handling --------------------
    $('#aipower-import-file-input').on('change', function (e) {
        var file = e.target.files[0];
        if (!file) return;

        // Validate file type
        if (file.type !== 'application/json' && !file.name.endsWith('.json')) {
            UI.showMessage('error', 'Please upload a valid JSON file.');
            return;
        }

        // Prepare FormData
        var formData = new FormData();
        formData.append('action', 'aipower_import_bots');
        formData.append('import_file', file);
        formData.append('_wpnonce', $('#ai-engine-nonce').val());

        // Show spinner
        UI.showSpinner();

        // Perform AJAX upload
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: formData,
            contentType: false, // Important for file upload
            processData: false, // Important for file upload
            success: function (response) {
                if (response.success) {
                    UI.showMessage('success', 'Chatbots imported successfully.', true);
                    refreshChatbotTable(); // Reload the chatbot table without page refresh
                } else {
                    UI.showMessage('error', response.data.message || 'Failed to import chatbots.');
                }
            },
            error: function () {
                UI.showMessage('error', 'An error occurred while importing chatbots.');
            },
            complete: function () {
                UI.hideSpinner();
                // Reset the file input
                $('#aipower-import-file-input').val('');
            }
        });
    });

    // -------------------- LOGIC: Handle Chat Bot Instruction Template Dropdown --------------------
    const $instructionSwitch = $('#aipower-chat-addition');
    const $instructionDropdown = $('#aipower-instruction-template');
    const $instructionTextarea = $('#aipower-chat-addition-text');
    const defaultInstruction = 'You are a helpful AI Assistant. Please be friendly. Today\'s date is [date].'; // Default instruction

    // Initial check to show/hide dropdown based on switch status
    if ($instructionSwitch.is(':checked')) {
        $instructionDropdown.show(); // Show dropdown if switch is checked
        $instructionTextarea.val(defaultInstruction); // Set default instruction if switch is checked on load
    } else {
        $instructionDropdown.hide(); // Hide dropdown if switch is unchecked
        $instructionTextarea.val(''); // Clear textarea if switch is unchecked on load
    }

    // Toggle dropdown visibility when the switch is changed
    $instructionSwitch.on('change', function () {
        if ($(this).is(':checked')) {
            $instructionDropdown.show(); // Show dropdown when enabled
            $instructionTextarea.val(defaultInstruction); // Set default instruction when switch is turned on
        } else {
            $instructionDropdown.hide(); // Hide dropdown when disabled
            $instructionTextarea.val(''); // Clear textarea when disabled
        }
    });

    // Replace textarea content with the selected template text
    $instructionDropdown.on('change', function () {
        const selectedTemplate = $(this).val();
        let instructionText = '';

        switch (selectedTemplate) {
            case 'customersupport':
                instructionText = 'Today\'s date is [date]. As a highly proficient and empathetic customer support assistant, your primary goal is to provide exceptional assistance, addressing customer concerns and inquiries in a timely and effective manner. Harness your expertise to deliver outstanding service and create a positive customer experience.';
                break;
            case 'salesupport':
                instructionText = 'Today\'s date is [date]. As an adept and proactive sales support assistant, you are an essential part of the [sitename] team. Your expertise in sales processes, combined with exceptional interpersonal skills, enables you to facilitate seamless customer interactions, foster strong relationships, and drive business growth. Dedicate yourself to providing unparalleled support and contributing to the overall success of [sitename].';
                break;
            case 'productsupport':
                instructionText = 'Today\'s date is [date]. As a knowledgeable and customer-focused product support assistant for [sitename], your expertise lies in guiding customers through product-related issues, offering tailored solutions, and ensuring their satisfaction. Your deep understanding of [sitename] product line and dedication to customer success positions you as a valuable resource. Strive to provide exceptional support, empowering customers to fully benefit from their product experience and fostering long-lasting relationships.';
                break;
            case 'languagetutor':
                instructionText = 'Today\'s date is [date]. As a skilled and patient language tutor, your mission is to assist learners in mastering new languages with confidence and ease. Your approach should be supportive, focusing on building the learner\'s language skills through interactive exercises, real-world examples, and personalized feedback. Encourage a positive learning environment that fosters growth and curiosity.';
                break;
            case 'lifecoach':
                instructionText = 'Today\'s date is [date]. As a compassionate and motivational life coach, your goal is to guide individuals towards personal and professional growth. Through insightful advice and constructive feedback, help them overcome challenges, set clear goals, and develop strategies for success. Provide empathetic support while encouraging self-reflection and accountability, empowering clients to take control of their journey towards a fulfilling life.';
                break;
            default:
                instructionText = defaultInstruction; // Use default instruction if no specific template is selected
        }

        // Clear the current text and set the new template text
        $instructionTextarea.val(instructionText);
    });

    // **NEW FUNCTION: Update Conversation Starters Switch Based on Textarea Value**
    function updateConversationStartersSwitch() {
        const starters = $('#aipower-bot-conversation-starters').val().trim();
        if (starters) {
            $('#aipower-starters').prop('disabled', false);
            $('#aipower-starters').prop('checked', true);
        } else {
            $('#aipower-starters').prop('disabled', true);
            $('#aipower-starters').prop('checked', false);
        }
    }

    // -------------------- LOGIC: Handle Chat Bot Advanced Settings Modal --------------------
    function handleModal(trigger, modal) {
        $(trigger).on('click', function () {
            $(modal).show();
        });

        $('.aipower-close').on('click', function () {
            updateConversationStartersSwitch(); // Update the conversation starters switch after closing the modal
            $(modal).hide();
        });

        $(window).on('click', function (event) {
            if ($(event.target).is(modal)) {
                updateConversationStartersSwitch();
                $(modal).hide();
            }
        });
    }

    // Handle modals
    handleModal('#aipower-bot-advanced-settings-icon', '#bot-advanced-settings-modal');
    handleModal('#aipower-bot-memory-settings-icon', '#bot-memory-settings-modal');
    handleModal('#aipower-bot-content-aware-settings-icon', '#bot-content-aware-settings-modal');
    handleModal('#aipower-bot-feedback-settings-icon', '#bot-feedback-settings-modal');
    handleModal('#aipower-bot-pdf-upload-settings-icon', '#bot-pdf-upload-settings-modal');
    handleModal('#aipower-bot-starters-settings-icon', '#bot-conversation-starters-modal');
    handleModal('#aipower-bot-logs-settings-icon', '#bot-logs-settings-modal');
    handleModal('#aipower-bot-speech-settings-icon', '#bot-speech-settings-modal');
    handleModal('#aipower-bot-role-limits-icon', '#bot-role-limits-modal');
    handleModal('#aipower-bot-leads-settings-icon', '#bot-leads-settings-modal');

    // Reusable function to set up media uploader
    function setupMediaUploader(options) {
        let uploader;

        $(options.buttonSelector).on('click', function (e) {
            e.preventDefault();

            // If the uploader object has already been created, reopen the dialog
            if (uploader) {
                uploader.open();
                return;
            }

            // Initialize the wp.media frame
            uploader = wp.media({
                title: options.title,
                button: {
                    text: options.buttonText
                },
                multiple: false
            });

            // When a file is selected, handle the selection
            uploader.on('select', function () {
                const attachment = uploader.state().get('selection').first().toJSON();
                const attachmentId = attachment.id;
                const attachmentUrl = attachment.url;

                // Set the hidden input value to the attachment ID
                $(options.hiddenFieldSelector).val(attachmentId);

                // Update the preview area with the selected image
                $(options.previewSelector).html('<img src="' + attachmentUrl + '" style="max-width:100px; height:auto;" />');

                // Trigger autosave for the specified field
                const fieldConfig = fieldConfigurations.find(f => f.name === options.fieldName);
                if (fieldConfig) {
                    handleFieldSave(fieldConfig);
                }
            });

            // Open the uploader dialog
            uploader.open();
        });
    }

    // Set up the Icon Uploader
    setupMediaUploader({
        buttonSelector: '#aipower-icon-upload-button',
        title: 'Select Icon',
        buttonText: 'Use this icon',
        hiddenFieldSelector: '#aipower-icon-url',
        previewSelector: '#aipower-icon-preview',
        fieldName: 'icon_url'
    });

    // Function to update the main Speech switch based on the modal switches
    function updateMainSpeechSwitch() {
        const isAudioEnabled = $('#aipower-speech-to-text').is(':checked');
        const isChatToSpeechEnabled = $('#aipower-text-to-speech').is(':checked');
        $('#aipower-speech').prop('checked', isAudioEnabled || isChatToSpeechEnabled);
    }

    // Attach change event listeners to the audio_enable and chat_to_speech switches
    $('#aipower-speech-to-text, #aipower-text-to-speech').on('change', updateMainSpeechSwitch);
    // Initialize the main Speech switch
    updateMainSpeechSwitch();

    // -------------------- Handle 'role_limited' Checkbox Change --------------------
    const roleLimitedField = fieldConfigurations.find(f => f.name === 'role_limited');
    if (roleLimitedField) {
        const $field = $(roleLimitedField.selector);
        $field.on('change', function () {
            const isChecked = $(this).is(':checked');
            if (isChecked) {
                // Trigger the modal using the existing handleModal function
                $('#aipower-bot-role-limits-icon').trigger('click');
            } else {
                // Hide the modal
                $('#bot-role-limits-modal').hide();
                // Clear the limited_roles field
                const field = {
                    name: 'limited_roles',
                    ajaxAction: 'aipower_save_field',
                };
                const value = {}; // Empty object
                autosaveField(field, value);
            }
        });
    }

    // -------------------- Attach Event Listener to Role-Limit Inputs --------------------
    function saveLimitedRoles() {
        const roleLimits = {};
        $('#bot-role-limits-modal .role-limit-input').each(function () {
            const role = $(this).data('role');
            const value = $(this).val().trim();
            roleLimits[role] = value; // May be empty or '0'
        });

        const field = {
            name: 'limited_roles',
            ajaxAction: 'aipower_save_field',
        };

        autosaveField(field, roleLimits);
    }

    $('#bot-role-limits-modal').on('input', '.role-limit-input', debounce(saveLimitedRoles, 300));

    // Theme selection handling
    $('#aipower-themes').on('change', function () {
        const selectedTheme = $(this).val();

        // Disable the theme dropdown
        $(this).prop('disabled', true);

        // Show the scanning effect
        showScanningEffect();

        // Define the theme color values
        const themes = {
            'default': {
                'bgcolor': '#f8f9fa',
                'fontcolor': '#495057',
                'footer_color': '#FFFFFF',
                'ai_bg_color': '#d1e8ff',
                'user_bg_color': '#ccf5e1',
                'bg_text_field': '#FFFFFF',
                'input_font_color': '#495057',
                'border_text_field': '#CED4DA',
                'send_color': '#d1e8ff',
                'mic_color': '#d1e8ff',
                'pdf_color': '#d1e8ff',
                'footer_font_color': '#495057',
                'bar_color': '#495057',
                'thinking_color': '#495057'
            },
            'dark': {
                // Dark theme colors
                'bgcolor': '#1E1E1E',
                'fontcolor': '#FFFFFF',
                'footer_color': '#2E2E2E',
                'ai_bg_color': '#2E2E2E',
                'user_bg_color': '#3E3E3E',
                'bg_text_field': '#2E2E2E',
                'input_font_color': '#FFFFFF',
                'border_text_field': '#3E3E3E',
                'send_color': '#FFFFFF',
                'mic_color': '#FFFFFF',
                'pdf_color': '#FFFFFF',
                'footer_font_color': '#FFFFFF',
                'bar_color': '#FFFFFF',
                'thinking_color': '#BBBBBB'
            },
            'light': {
                // Light theme colors
                'bgcolor': '#f8f9fa',
                'fontcolor': '#495057',
                'footer_color': '#FFFFFF',
                'ai_bg_color': '#d1e8ff',
                'user_bg_color': '#ccf5e1',
                'bg_text_field': '#FFFFFF',
                'input_font_color': '#495057',
                'border_text_field': '#CED4DA',
                'send_color': '#d1e8ff',
                'mic_color': '#d1e8ff',
                'pdf_color': '#d1e8ff',
                'footer_font_color': '#495057',
                'bar_color': '#495057',
                'thinking_color': '#495057'
            },
            'whatsapp': {
                // WhatsApp theme colors
                'bgcolor': '#ECE5DD',
                'fontcolor': '#000000',
                'footer_color': '#D9DBD5',
                'ai_bg_color': '#DCF8C6',
                'user_bg_color': '#FFFFFF',
                'bg_text_field': '#FFFFFF',
                'input_font_color': '#000000',
                'border_text_field': '#D9DBD5',
                'send_color': '#075E54',
                'mic_color': '#075E54',
                'pdf_color': '#075E54',
                'footer_font_color': '#000000',
                'bar_color': '#075E54',
                'thinking_color': '#777777'
            },
            'terminal': {
                // Terminal theme colors
                'bgcolor': '#000000',
                'fontcolor': '#00FF00',
                'footer_color': '#000000',
                'ai_bg_color': '#000000',
                'user_bg_color': '#000000',
                'bg_text_field': '#000000',
                'input_font_color': '#00FF00',
                'border_text_field': '#00FF00',
                'send_color': '#00FF00',
                'mic_color': '#00FF00',
                'pdf_color': '#00FF00',
                'footer_font_color': '#00FF00',
                'bar_color': '#00FF00',
                'thinking_color': '#00FF00'
            },
            'sunset': {
                // Sunset-inspired colors
                'bgcolor': '#FF6F61',
                'fontcolor': '#2E1F27',
                'footer_color': '#FF8360',
                'ai_bg_color': '#FFE0B2',
                'user_bg_color': '#FFAB91',
                'bg_text_field': '#FFE0B2',
                'input_font_color': '#2E1F27',
                'border_text_field': '#FFAB91',
                'send_color': '#FF7043',
                'mic_color': '#FF7043',
                'pdf_color': '#FF7043',
                'footer_font_color': '#FFE0B2',
                'bar_color': '#FFE0B2',
                'thinking_color': '#FFAB91'
            },
            'ocean': {
                // Ocean-inspired colors
                'bgcolor': '#0077B6',
                'fontcolor': '#FFFFFF',
                'footer_color': '#0096C7',
                'ai_bg_color': '#00B4D8',
                'user_bg_color': '#48CAE4',
                'bg_text_field': '#00B4D8',
                'input_font_color': '#FFFFFF',
                'border_text_field': '#90E0EF',
                'send_color': '#FFFFFF',
                'mic_color': '#FFFFFF',
                'pdf_color': '#FFFFFF',
                'footer_font_color': '#FFFFFF',
                'bar_color': '#FFFFFF',
                'thinking_color': '#90E0EF'
            },
            'forest': {
                // Forest-inspired colors
                'bgcolor': '#2D6A4F',
                'fontcolor': '#FFFFFF',
                'footer_color': '#40916C',
                'ai_bg_color': '#52B788',
                'user_bg_color': '#74C69D',
                'bg_text_field': '#52B788',
                'input_font_color': '#FFFFFF',
                'border_text_field': '#74C69D',
                'send_color': '#1B4332',
                'mic_color': '#1B4332',
                'pdf_color': '#1B4332',
                'footer_font_color': '#FFFFFF',
                'bar_color': '#FFFFFF',
                'thinking_color': '#95D5B2'
            },
            'neon': {
                // Neon-inspired colors
                'bgcolor': '#121212',
                'fontcolor': '#0ABAB5',
                'footer_color': '#1A1A1A',
                'ai_bg_color': '#1F1F1F',
                'user_bg_color': '#2D2D2D',
                'bg_text_field': '#1A1A1A',
                'input_font_color': '#0ABAB5',
                'border_text_field': '#0ABAB5',
                'send_color': '#0ABAB5',
                'mic_color': '#0ABAB5',
                'pdf_color': '#0ABAB5',
                'footer_font_color': '#FFFFFF',
                'bar_color': '#FFFFFF',
                'thinking_color': '#FF26A2'
            }
        };

        const themeColors = themes[selectedTheme];

        // Prepare the fields to update
        const colorFields = [
            'bgcolor',
            'fontcolor',
            'footer_color',
            'ai_bg_color',
            'user_bg_color',
            'bg_text_field',
            'input_font_color',
            'border_text_field',
            'send_color',
            'footer_font_color',
            'bar_color',
            'thinking_color',
            'mic_color',
            'pdf_color'
        ];

        // Update the fields one by one
        let index = 0;

        function updateNextField() {
            if (index >= colorFields.length) {
                // All fields updated
                // Re-enable the theme dropdown
                $('#aipower-themes').prop('disabled', false);
                // Hide the scanning effect
                hideScanningEffect();
                return;
            }

            const fieldName = colorFields[index];
            const fieldValue = themeColors[fieldName];
            const fieldConfig = fieldConfigurations.find(f => f.name === fieldName);

            if (fieldConfig) {
                // Set the field value without triggering events
                setFieldValue(fieldConfig, fieldValue, false);

                // Apply realtime preview if any
                handleRealtimePreview(fieldConfig, fieldValue);

                // Autosave the field
                autosaveField(fieldConfig, fieldValue, function () {
                    // Proceed to the next field
                    index++;
                    updateNextField();
                });
            } else {
                // Field not found, proceed to next
                index++;
                updateNextField();
            }
        }

        // Start updating fields
        updateNextField();
    });

    // Function to show the scanning effect
    function showScanningEffect() {
        // Append the overlay to the chat window its either .wpaicg-chat-shortcode or .wpaicg-chatbox
        const $chatWindow = $('.wpaicg-chat-shortcode, .wpaicg-chatbox');
        if ($chatWindow.length) {
            // Ensure the chat window has position relative or absolute
            if ($chatWindow.css('position') === 'static') {
                $chatWindow.css('position', 'relative');
            }

            // Add the scanning overlay if it doesn't exist
            if (!$chatWindow.find('.aipower-scanning-overlay').length) {
                $chatWindow.append('<div class="aipower-scanning-overlay"></div>');
            }
        }
    }

    // Function to hide the scanning effect
    function hideScanningEffect() {
        const $chatWindow = $('.wpaicg-chat-shortcode, .wpaicg-chatbox');
        $chatWindow.find('.aipower-scanning-overlay').remove();
    }

    function updatePlaceholderColor(colorValue) {
        // Create or get the style element
        let styleElement = $('#placeholder-style');
        if (styleElement.length === 0) {
            styleElement = $('<style id="placeholder-style"></style>');
            $('head').append(styleElement);
        }
        // Generate the CSS rule
        const cssRule = `
            textarea.wpaicg-chat-shortcode-typing::placeholder {
                color: ${colorValue} !important;
            }
            textarea.wpaicg-chat-shortcode-typing::-webkit-input-placeholder {
                color: ${colorValue} !important;
            }
            textarea.wpaicg-chat-shortcode-typing:-ms-input-placeholder {
                color: ${colorValue} !important;
            }
            textarea.wpaicg-chatbox-typing::placeholder {
                color: ${colorValue} !important;
            }
            textarea.wpaicg-chatbox-typing::-webkit-input-placeholder {
                color: ${colorValue} !important;
            }
            textarea.wpaicg-chatbox-typing:-ms-input-placeholder {
                color: ${colorValue} !important;
            }
        `;
        // Update the style element
        styleElement.html(cssRule);
    }


    // -------------------- Handle Embeddings from Bot Settings --------------------
    let allPostsForEmbedding = {};
    let $addDataModal = $('#aipower-add-data-modal');
    let $addDataTabs = $('#aipower-add-data-tabs');

    // The two buttons in the modal:
    let $indexSelectedBtn = $('#aipower-index-selected-btn');  // The button we want to disable while indexing
    let $stopIndexingBtn = $('#aipower-stop-indexing-btn');

    $('#aipower-add-data-button-wrapper').show();

    // 1. Listen for "Add Data" button to open the second modal
    $('#aipower-add-data-button').on('click', function (e) {
        e.preventDefault();

        // We only proceed if "Embeddings" is selected:
        let embeddingVal = $('input[name="embedding"]:checked').val();
        if (embeddingVal !== '1') {
            alert('Please select "Embeddings" as Data Source first.');
            return;
        }

        // Show the second modal
        $addDataModal.show();

        // If we haven't fetched posts yet, do so
        if (Object.keys(allPostsForEmbedding).length === 0) {
            fetchAllPostsForEmbedding();
        }
    });

    // 2. AJAX to fetch all posts for embedding
    function fetchAllPostsForEmbedding() {
        $addDataTabs.html('<p>Loading content...</p>');

        // The nonce from hidden field
        let $nonce = $('#ai-engine-nonce').val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_get_all_posts_for_embeddings',
                _wpnonce: $nonce
            },
            success: function (res) {
                if (res.success) {
                    allPostsForEmbedding = res.data.data;
                    renderAllPostsByType(allPostsForEmbedding);
                } else {
                    $addDataTabs.html('<p style="color:red;">Failed to load posts. ' + (res.data.message ? res.data.message : '') + '</p>');
                }
            },
            error: function () {
                $addDataTabs.html('<p style="color:red;">Error retrieving posts.</p>');
            }
        });
    }

    // 3. Render the retrieved posts
    function renderAllPostsByType(data) {
        $addDataTabs.empty();
        let tabHTML = '';
        for (let pt in data) {
            let typeTitle = pt.charAt(0).toUpperCase() + pt.slice(1);
            let postsArray = data[pt];

            tabHTML += `
            <div class="aipower-add-data-posttype-block" style="margin-bottom: 20px;">
                <h3>${typeTitle} 
                    <span style="font-size:12px;color:#888;cursor:pointer;"
                          class="aipower-select-all" data-pt="${pt}">[Select All]</span>
                </h3>
                <div style="margin-left:10px;">`;

            postsArray.forEach(function (post) {
                let safeTitle = $('<div/>').text(post.post_title).html();
                tabHTML += `
                <div style="margin:2px 0;">
                    <label>
                        <input type="checkbox" class="aipower-embed-checkbox" 
                               data-post-id="${post.ID}" data-pt="${pt}" />
                        ${safeTitle}
                    </label>
                </div>`;
            });

            tabHTML += `</div></div>`;
        }
        $addDataTabs.html(tabHTML);
    }

    // 4. "Select All" handler
    $(document).on('click', '.aipower-select-all', function () {
        let block = $(this).closest('.aipower-add-data-posttype-block');
        let checkboxes = block.find('.aipower-embed-checkbox');
        let allChecked = true;

        checkboxes.each(function () {
            if (!$(this).is(':checked')) {
                allChecked = false;
            }
        });
        checkboxes.prop('checked', !allChecked);
    });

    // === Indexing logic ===
    let indexingQueue = [];
    let currentIndexingIndex = 0;
    let isIndexing = false;
    let currentRequest = null;

    // 5. "Index Selected" click
    $indexSelectedBtn.on('click', function (e) {
        e.preventDefault();

        // Gather selected IDs
        let selectedIDs = [];
        $('.aipower-embed-checkbox:checked').each(function () {
            selectedIDs.push($(this).data('post-id'));
        });

        if (selectedIDs.length === 0) {
            alert('Please select at least one item to index.');
            return;
        }

        startIndexing(selectedIDs);
    });

    // 6. Stop button click
    $stopIndexingBtn.on('click', function (e) {
        e.preventDefault();
        stopIndexing();
    });

    function startIndexing(ids) {
        isIndexing = true;
        indexingQueue = ids;
        currentIndexingIndex = 0;

        $('#aipower-index-progress').html(`<p>Indexing 0/${indexingQueue.length} ...</p>`);

        // Disable the "Index Selected" button
        $indexSelectedBtn.prop('disabled', true);

        // Show the "Stop Indexing" button
        $stopIndexingBtn.show();

        indexNext();
    }

    function stopIndexing() {
        if (currentRequest) {
            currentRequest.abort();
        }

        isIndexing = false;
        $('#aipower-index-progress').append(`<p style="color:red;">Indexing stopped by user.</p>`);

        // Hide status texts after a small delay
        setTimeout(function () {
            $('#aipower-index-progress').empty();
        }, 3000);

        // Hide the stop button
        $stopIndexingBtn.hide();

        // Re-enable "Index Selected" button
        $indexSelectedBtn.prop('disabled', false);
    }

    function indexNext() {
        if (!isIndexing) {
            return; // indexing was stopped
        }

        if (currentIndexingIndex >= indexingQueue.length) {
            // Done indexing all
            $('#aipower-index-progress').append('<p style="color:green;">All done!</p>');
            isIndexing = false;

            // Hide the stop button
            $stopIndexingBtn.hide();

            // Re-enable "Index Selected"
            $indexSelectedBtn.prop('disabled', false);

            // Hide status texts after a small delay
            setTimeout(function () {
                $('#aipower-index-progress').empty();
            }, 3000);
            return;
        }

        let postId = indexingQueue[currentIndexingIndex];
        let $embedding_nonce = $('#embedding-nonce').val();

        currentRequest = $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_instant_embedding',
                id: postId,
                nonce: $embedding_nonce
            },
            success: function (res) {
                if (!isIndexing) return; // if user stopped indexing, do nothing further

                if (res.status === 'success') {
                    $('#aipower-index-progress').html(
                        `<p>Indexing ${currentIndexingIndex + 1}/${indexingQueue.length} ... success</p>`
                    );
                } else {
                    $('#aipower-index-progress').append(
                        `<p style="color:red;">Post ID ${postId} error: ${res.msg}</p>`
                    );
                }
                currentIndexingIndex++;
                indexNext();
            },
            error: function (err) {
                if (err.statusText === 'abort') {
                    // We intentionally aborted the request
                    return;
                }
                $('#aipower-index-progress').append(
                    `<p style="color:red;">Post ID ${postId} failed. Possibly server error.</p>`
                );
                currentIndexingIndex++;
                indexNext();
            }
        });
    }

    // 7. Closing the Add Data modal
    $(document).on('click', '#aipower-add-data-modal .aipower-close', function (e) {
        e.preventDefault();
        e.stopPropagation();
        $addDataModal.hide();
    });

    // Also close if user clicks outside the second modal content
    $(window).on('click', function (e) {
        if ($(e.target).is($addDataModal)) {
            e.stopPropagation();
            $addDataModal.hide();
        }
    });

});
