<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wp,$wpdb;
$wpaicg_ai_thinking = get_option('_wpaicg_ai_thinking','');
$wpaicg_you = get_option('_wpaicg_chatbox_you','');
$wpaicg_typing_placeholder = get_option('_wpaicg_typing_placeholder','');
$wpaicg_welcome_message = get_option('_wpaicg_chatbox_welcome_message','');
if (!isset($wpaicg_chat_widget)) {
    $wpaicg_chat_widget = get_option('wpaicg_chat_widget',[]);
}

/* Check Custom Widget For Page/Post */
if (!isset($wpaicg_chat_widget['custom_loaded']) && !isset($wpaicg_chat_widget['bot_id'])) {
    $current_context_ID = get_the_ID();
    $wpaicg_bot_content = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key=%s",'wpaicg_widget_page_'.$current_context_ID));
    if($wpaicg_bot_content && isset($wpaicg_bot_content->post_id)){
        $wpaicg_bot = get_post($wpaicg_bot_content->post_id);
        if($wpaicg_bot) {
            if(strpos($wpaicg_bot->post_content,'\"') !== false) {
                $wpaicg_bot->post_content = str_replace('\"', '&quot;', $wpaicg_bot->post_content);
            }
            if(strpos($wpaicg_bot->post_content,"\'") !== false) {
                $wpaicg_bot->post_content = str_replace('\\', '', $wpaicg_bot->post_content);
            }
            $wpaicg_chat_widget = json_decode($wpaicg_bot->post_content, true);
        }
    }
}
$wpaicg_stream_nav_setting = get_option('wpaicg_widget_stream', '0'); // Default to '1' if not set
$wpaicg_conversation_starters_widget_json = get_option('wpaicg_conversation_starters_widget', '');
$wpaicg_conversation_starters_widget = !empty($wpaicg_conversation_starters_widget_json) ? json_decode($wpaicg_conversation_starters_widget_json, true) : [];
$wpaicg_autoload_chat_conversations = get_option('wpaicg_autoload_chat_conversations', 0);
$wpaicg_conversation_cut = get_option('wpaicg_conversation_cut', 100);
/*Check Custom Widget For Page Post*/
$current_context_ID = get_the_ID();
$wpaicg_bot_id = 0;
$wpaicg_bot_content = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key=%s",'wpaicg_widget_page_'.$current_context_ID));
if($wpaicg_bot_content && isset($wpaicg_bot_content->post_id)){
    $wpaicg_bot_id = $wpaicg_bot_content->post_id;
    $wpaicg_bot = get_post($wpaicg_bot_content->post_id);
    if($wpaicg_bot) {
        if(strpos($wpaicg_bot->post_content,'\"') !== false) {
            $wpaicg_bot->post_content = str_replace('\"', '&quot;', $wpaicg_bot->post_content);
        }
        if(strpos($wpaicg_bot->post_content,"\'") !== false) {
            $wpaicg_bot->post_content = str_replace('\\', '', $wpaicg_bot->post_content);
        }
        $wpaicg_chat_widget = json_decode($wpaicg_bot->post_content, true);
        $wpaicg_chat_status = 'active';
        $wpaicg_you = isset($wpaicg_chat_widget['you']) && !empty($wpaicg_chat_widget['you']) ? $wpaicg_chat_widget['you'] : $wpaicg_you;
        $wpaicg_typing_placeholder = isset($wpaicg_chat_widget['placeholder']) && !empty($wpaicg_chat_widget['placeholder']) ? $wpaicg_chat_widget['placeholder'] : $wpaicg_typing_placeholder;
        $wpaicg_welcome_message = isset($wpaicg_chat_widget['welcome']) && !empty($wpaicg_chat_widget['welcome']) ? $wpaicg_chat_widget['welcome'] : $wpaicg_welcome_message;
        $wpaicg_ai_thinking = isset($wpaicg_chat_widget['ai_thinking']) && !empty($wpaicg_chat_widget['ai_thinking']) ? $wpaicg_chat_widget['ai_thinking'] : $wpaicg_ai_thinking;
        $wpaicg_stream_nav_setting = isset($wpaicg_chat_widget['openai_stream_nav']) && !empty($wpaicg_chat_widget['openai_stream_nav']) ? $wpaicg_chat_widget['openai_stream_nav'] : '0';
        $wpaicg_conversation_cut = isset($wpaicg_chat_widget['conversation_cut']) && !empty($wpaicg_chat_widget['conversation_cut']) ? $wpaicg_chat_widget['conversation_cut'] : '100';
        
        // Adapt conversation starters for widget if they exist and are an array
        $wpaicg_conversation_starters_widget = isset($wpaicg_chat_widget['conversation_starters']) && is_array($wpaicg_chat_widget['conversation_starters']) ? $wpaicg_chat_widget['conversation_starters'] : [];
        $adapted_conversation_starters_widget = [];

        if (!empty($wpaicg_conversation_starters_widget)) {
            $adapted_conversation_starters_widget = array_map(function($text, $index) {
                $text = is_string($text) ? $text : '';
                return ['index' => $index, 'text' => $text];
            }, $wpaicg_conversation_starters_widget, array_keys($wpaicg_conversation_starters_widget));
        }

        // Update $wpaicg_conversation_starters_widget with the adapted version
        $wpaicg_conversation_starters_widget = $adapted_conversation_starters_widget;
    }
}
$wpaicg_chat_widget_width = isset($wpaicg_chat_widget['width']) && !empty($wpaicg_chat_widget['width']) ? $wpaicg_chat_widget['width'] : '30%';
$wpaicg_chat_widget_height = isset($wpaicg_chat_widget['height']) && !empty($wpaicg_chat_widget['height']) ? $wpaicg_chat_widget['height'] : '40%';
/*End check*/
$wpaicg_ai_thinking = !empty($wpaicg_ai_thinking) ? $wpaicg_ai_thinking : esc_html__('AI thinking','gpt3-ai-content-generator');
$wpaicg_typing_placeholder = !empty($wpaicg_typing_placeholder) ? $wpaicg_typing_placeholder : esc_html__('Type a message','gpt3-ai-content-generator');
$wpaicg_chat_content_aware = isset($wpaicg_chat_widget['content_aware']) && !empty($wpaicg_chat_widget['content_aware']) ? $wpaicg_chat_widget['content_aware'] : 'yes';
$wpaicg_welcome_message = !empty($wpaicg_welcome_message) ? $wpaicg_welcome_message : 'Hello 👋, how can I help you today?';
$wpaicg_user_bg_color = isset($wpaicg_chat_widget['user_bg_color']) && !empty($wpaicg_chat_widget['user_bg_color']) ? $wpaicg_chat_widget['user_bg_color'] : '#444654';
$wpaicg_ai_bg_color = isset($wpaicg_chat_widget['ai_bg_color']) && !empty($wpaicg_chat_widget['ai_bg_color']) ? $wpaicg_chat_widget['ai_bg_color'] : '#343541';
$wpaicg_bg_text_field = isset($wpaicg_chat_widget['bg_text_field']) && !empty($wpaicg_chat_widget['bg_text_field']) ? $wpaicg_chat_widget['bg_text_field'] : '#ffffff';
$wpaicg_border_text_field = isset($wpaicg_chat_widget['border_text_field']) && !empty($wpaicg_chat_widget['border_text_field']) ? $wpaicg_chat_widget['border_text_field'] : '#cccccc';
$wpaicg_send_color = isset($wpaicg_chat_widget['send_color']) && !empty($wpaicg_chat_widget['send_color']) ? $wpaicg_chat_widget['send_color'] : '#d1e8ff';
$wpaicg_footer_color = isset($wpaicg_chat_widget['footer_color']) && !empty($wpaicg_chat_widget['footer_color']) ? $wpaicg_chat_widget['footer_color'] : '#ffffff';
$wpaicg_footer_font_color = isset($wpaicg_chat_widget['footer_font_color']) && !empty($wpaicg_chat_widget['footer_font_color']) ? $wpaicg_chat_widget['footer_font_color'] : '#495057';

$wpaicg_chat_fontsize = isset($wpaicg_chat_widget['fontsize']) && !empty($wpaicg_chat_widget['fontsize']) ? $wpaicg_chat_widget['fontsize'] : '13';
$wpaicg_chat_fontcolor = isset($wpaicg_chat_widget['fontcolor']) && !empty($wpaicg_chat_widget['fontcolor']) ? $wpaicg_chat_widget['fontcolor'] : '#ffffff';
$wpaicg_input_font_color = isset($wpaicg_chat_widget['input_font_color']) && !empty($wpaicg_chat_widget['input_font_color']) ? $wpaicg_chat_widget['input_font_color'] : '#495057';
$wpaicg_save_logs = isset($wpaicg_chat_widget['save_logs']) && !empty($wpaicg_chat_widget['save_logs']) ? $wpaicg_chat_widget['save_logs'] : false;
$wpaicg_log_notice = isset($wpaicg_chat_widget['log_notice']) && !empty($wpaicg_chat_widget['log_notice']) ? $wpaicg_chat_widget['log_notice'] : false;
$wpaicg_log_notice_message = isset($wpaicg_chat_widget['log_notice_message']) && !empty($wpaicg_chat_widget['log_notice_message']) ? $wpaicg_chat_widget['log_notice_message'] : esc_html__('Please note that your conversations will be recorded.','gpt3-ai-content-generator');
$wpaicg_audio_enable = isset($wpaicg_chat_widget['audio_enable']) ? $wpaicg_chat_widget['audio_enable'] : false;
$wpaicg_image_enable = isset($wpaicg_chat_widget['image_enable']) ? $wpaicg_chat_widget['image_enable'] : false;
$wpaicg_pdf_enable = isset($wpaicg_chat_widget['embedding_pdf']) ? $wpaicg_chat_widget['embedding_pdf'] : false;
$wpaicg_pdf_pages = isset($wpaicg_chat_widget['pdf_pages']) ? $wpaicg_chat_widget['pdf_pages'] : 120;
$wpaicg_mic_color = isset($wpaicg_chat_widget['mic_color']) ? $wpaicg_chat_widget['mic_color'] : '#222222';
$wpaicg_pdf_color = isset($wpaicg_chat_widget['pdf_color']) ? $wpaicg_chat_widget['pdf_color'] : '#222222';
$wpaicg_stop_color = isset($wpaicg_chat_widget['stop_color']) ? $wpaicg_chat_widget['stop_color'] : '#d1e8ff';
$wpaicg_chat_fullscreen = isset($wpaicg_chat_widget['fullscreen']) && !empty($wpaicg_chat_widget['fullscreen']) ? $wpaicg_chat_widget['fullscreen'] : false;
$wpaicg_chat_close_btn = isset($wpaicg_chat_widget['close_btn']) && !empty($wpaicg_chat_widget['close_btn']) ? $wpaicg_chat_widget['close_btn'] : false;
$wpaicg_chat_copy_btn = isset($wpaicg_chat_widget['copy_btn']) && !empty($wpaicg_chat_widget['copy_btn']) ? $wpaicg_chat_widget['copy_btn'] : false;
$wpaicg_chat_feedback_btn = isset($wpaicg_chat_widget['feedback_btn']) && !empty($wpaicg_chat_widget['feedback_btn']) ? $wpaicg_chat_widget['feedback_btn'] : false;
$wpaicg_chat_feedback_title = isset($wpaicg_chat_widget['feedback_title']) && !empty($wpaicg_chat_widget['feedback_title']) ? $wpaicg_chat_widget['feedback_title'] : esc_html__('Feedback','gpt3-ai-content-generator');
$wpaicg_chat_feedback_message = isset($wpaicg_chat_widget['feedback_message']) && !empty($wpaicg_chat_widget['feedback_message']) ? $wpaicg_chat_widget['feedback_message'] : esc_html__('Please provide details: (optional)','gpt3-ai-content-generator');
$wpaicg_chat_feedback_success = isset($wpaicg_chat_widget['feedback_success']) && !empty($wpaicg_chat_widget['feedback_success']) ? $wpaicg_chat_widget['feedback_success'] : esc_html__('Thank you for your feedback!','gpt3-ai-content-generator');
$wpaicg_chat_download_btn = isset($wpaicg_chat_widget['download_btn']) && !empty($wpaicg_chat_widget['download_btn']) ? $wpaicg_chat_widget['download_btn'] : false;
$wpaicg_chat_audio_btn = isset($wpaicg_chat_widget['audio_btn']) && !empty($wpaicg_chat_widget['audio_btn']) ? $wpaicg_chat_widget['audio_btn'] : false;
$wpaicg_voice_muted_by_default = isset($wpaicg_chat_widget['muted_by_default']) && !empty($wpaicg_chat_widget['muted_by_default']) ? $wpaicg_chat_widget['muted_by_default'] : false;
$wpaicg_chat_clear_btn = isset($wpaicg_chat_widget['clear_btn']) && !empty($wpaicg_chat_widget['clear_btn']) ? $wpaicg_chat_widget['clear_btn'] : false;
$wpaicg_has_action_bar = false;
$wpaicg_chat_bgcolor = isset($wpaicg_chat_widget['bgcolor']) && !empty($wpaicg_chat_widget['bgcolor']) ? $wpaicg_chat_widget['bgcolor'] : '#f8f9fa';
$wpaicg_bar_color = isset($wpaicg_chat_widget['bar_color']) && !empty($wpaicg_chat_widget['bar_color']) ? $wpaicg_chat_widget['bar_color'] : '#ffffff';
$wpaicg_thinking_color = isset($wpaicg_chat_widget['thinking_color']) && !empty($wpaicg_chat_widget['thinking_color']) ? $wpaicg_chat_widget['thinking_color'] : '#ffffff';
$wpaicg_delay_time = isset($wpaicg_chat_widget['delay_time']) && !empty($wpaicg_chat_widget['delay_time']) ? $wpaicg_chat_widget['delay_time'] : '';
if($wpaicg_chat_fullscreen || $wpaicg_chat_download_btn || $wpaicg_chat_close_btn || $wpaicg_chat_clear_btn || $wpaicg_chat_audio_btn){
    $wpaicg_has_action_bar = true;
}
$wpaicg_text_height = isset($wpaicg_chat_widget['text_height']) && !empty($wpaicg_chat_widget['text_height']) ? $wpaicg_chat_widget['text_height'] : 40;
$wpaicg_text_rounded = isset($wpaicg_chat_widget['text_rounded']) && !empty($wpaicg_chat_widget['text_height']) ? $wpaicg_chat_widget['text_rounded'] : 8;
$wpaicg_chat_rounded = isset($wpaicg_chat_widget['chat_rounded']) && !empty($wpaicg_chat_widget['text_height']) ? $wpaicg_chat_widget['chat_rounded'] : 8;
$wpaicg_chat_to_speech = isset($wpaicg_chat_widget['chat_to_speech']) ? $wpaicg_chat_widget['chat_to_speech'] : false;
$wpaicg_elevenlabs_voice = isset($wpaicg_chat_widget['elevenlabs_voice']) ? $wpaicg_chat_widget['elevenlabs_voice'] : '';
$wpaicg_elevenlabs_model = isset($wpaicg_chat_widget['elevenlabs_model']) ? $wpaicg_chat_widget['elevenlabs_model'] : '';
$wpaicg_elevenlabs_api = get_option('wpaicg_elevenlabs_api', '');
$wpaicg_google_api_key = get_option('wpaicg_google_api_key', '');
$wpaicg_elevenlabs_hide_error = get_option('wpaicg_elevenlabs_hide_error', false);

$wpaicg_typewriter_effect = get_option('wpaicg_typewriter_effect', false);
$wpaicg_typewriter_speed = get_option('wpaicg_typewriter_speed', 1);

$wpaicg_chat_voice_service = isset($wpaicg_chat_widget['voice_service']) && !empty($wpaicg_chat_widget['voice_service']) ? $wpaicg_chat_widget['voice_service'] : 'en-US';
$wpaicg_voice_language = isset($wpaicg_chat_widget['voice_language']) && !empty($wpaicg_chat_widget['voice_language']) ? $wpaicg_chat_widget['voice_language'] : 'en-US';
$wpaicg_voice_name = isset($wpaicg_chat_widget['voice_name']) && !empty($wpaicg_chat_widget['voice_name']) ? $wpaicg_chat_widget['voice_name'] : 'en-US-Studio-M';
$wpaicg_voice_device = isset($wpaicg_chat_widget['voice_device']) && !empty($wpaicg_chat_widget['voice_device']) ? $wpaicg_chat_widget['voice_device'] : '';
$wpaicg_voice_speed = isset($wpaicg_chat_widget['voice_speed']) && !empty($wpaicg_chat_widget['voice_speed']) ? $wpaicg_chat_widget['voice_speed'] : 1;
$wpaicg_voice_pitch = isset($wpaicg_chat_widget['voice_pitch']) && !empty($wpaicg_chat_widget['voice_pitch']) ? $wpaicg_chat_widget['voice_pitch'] : 0;

$wpaicg_openai_model = isset($wpaicg_chat_widget['openai_model']) && !empty($wpaicg_chat_widget['openai_model']) ? $wpaicg_chat_widget['openai_model'] : 'tts-1';
$wpaicg_openai_voice = isset($wpaicg_chat_widget['openai_voice']) && !empty($wpaicg_chat_widget['openai_voice']) ? $wpaicg_chat_widget['openai_voice'] : 'alloy';
$wpaicg_openai_output_format = isset($wpaicg_chat_widget['openai_output_format']) && !empty($wpaicg_chat_widget['openai_output_format']) ? $wpaicg_chat_widget['openai_output_format'] : 'mp3';
$wpaicg_openai_voice_speed = isset($wpaicg_chat_widget['openai_voice_speed']) && !empty($wpaicg_chat_widget['openai_voice_speed']) ? $wpaicg_chat_widget['openai_voice_speed'] : '1.0';

// Lead Collection Settings
$wpaicg_lead_collection = isset($wpaicg_chat_widget['lead_collection']) && !empty($wpaicg_chat_widget['lead_collection']) ? $wpaicg_chat_widget['lead_collection'] : 0;
$wpaicg_lead_title = isset($wpaicg_chat_widget['lead_title']) && !empty($wpaicg_chat_widget['lead_title']) ? $wpaicg_chat_widget['lead_title'] : 'Let us know how to contact you';
$wpaicg_lead_name = isset($wpaicg_chat_widget['lead_name']) && !empty($wpaicg_chat_widget['lead_name']) ? $wpaicg_chat_widget['lead_name'] : 'Name';
$wpaicg_enable_lead_name = isset($wpaicg_chat_widget['enable_lead_name']) && !empty($wpaicg_chat_widget['enable_lead_name']) ? $wpaicg_chat_widget['enable_lead_name'] : 0;
$wpaicg_lead_email = isset($wpaicg_chat_widget['lead_email']) && !empty($wpaicg_chat_widget['lead_email']) ? $wpaicg_chat_widget['lead_email'] : 'Email';
$wpaicg_enable_lead_email = isset($wpaicg_chat_widget['enable_lead_email']) && !empty($wpaicg_chat_widget['enable_lead_email']) ? $wpaicg_chat_widget['enable_lead_email'] : 0;
$wpaicg_lead_phone = isset($wpaicg_chat_widget['lead_phone']) && !empty($wpaicg_chat_widget['lead_phone']) ? $wpaicg_chat_widget['lead_phone'] : 'Phone';
$wpaicg_enable_lead_phone = isset($wpaicg_chat_widget['enable_lead_phone']) && !empty($wpaicg_chat_widget['enable_lead_phone']) ? $wpaicg_chat_widget['enable_lead_phone'] : 0;

// Check if the model name starts with 'asst_'
$assistantEnabled = false;
if (isset($wpaicg_chat_widget['model']) && strpos($wpaicg_chat_widget['model'], 'asst_') === 0) {
    $assistantEnabled = true;
}
?>
<style>
    .wpaicg-icon-container {
        position: relative;
        margin-bottom: 30px;
        margin-top: 10px;
    }

    .wpaicg-copy-button, .wpaicg-thumbs-up-button, .wpaicg-thumbs-down-button {
        position: absolute;
        bottom: -25px;
        display: inline-block;
        background: none;
        border: none;
        cursor: pointer;
        padding: 0;
        opacity: 0;
        visibility: hidden;
        transition: opacity 0.3s ease, visibility 0.3s ease;
        outline: none;
    }

    .wpaicg-copy-button {
        left: 10px;
    }

    .wpaicg-thumbs-up-button {
        left: 40px;
    }

    .wpaicg-thumbs-down-button {
        left: 70px;
    }

    /* Keep buttons visible when hovering over either the container or the buttons */
    .wpaicg-icon-container:hover .wpaicg-copy-button,
    .wpaicg-icon-container:hover .wpaicg-thumbs-up-button,
    .wpaicg-icon-container:hover .wpaicg-thumbs-down-button,
    .wpaicg-copy-button:hover,
    .wpaicg-thumbs-up-button:hover,
    .wpaicg-thumbs-down-button:hover {
        opacity: 1;
        visibility: visible;
    }
    .log_notification {
        background: <?php echo esc_html($wpaicg_chat_bgcolor)?>;
        color: <?php echo esc_html($wpaicg_chat_fontcolor)?>;
        font-size: 11px;
        font-style: italic;
        padding: 10px;
        border-radius: 5px;
    }

    .wpaicg-copy-button img,
    .wpaicg-thumbs-up-button img,
    .wpaicg-thumbs-down-button img {
        width: 16px;
        height: 16px;
        filter: none; /* Ensure no filter (like grayscale or color change) is applied */
        color: inherit; /* Inherit the current color from the parent */
        transition: none; /* Remove any transitions that might change appearance on hover */
    }

    .wpaicg-copy-button img:hover,
    .wpaicg-thumbs-up-button img:hover,
    .wpaicg-thumbs-down-button img:hover {
        filter: none; /* Ensure no hover filter is applied */
        color: inherit; /* Keep the color consistent on hover */
    }

    .wpaicg-copy-button,
    .wpaicg-thumbs-up-button,
    .wpaicg-thumbs-down-button {
        background: none;
        border: none;
        padding: 0;
        cursor: pointer;
        color: inherit; /* Keep the color of the button consistent */
        outline: none; /* Remove default browser outline on focus */
    }

    .wpaicg-copy-button:hover,
    .wpaicg-thumbs-up-button:hover,
    .wpaicg-thumbs-down-button:hover {
        background: none; /* Remove any background color change on hover */
        color: inherit; /* Keep the color the same on hover */
    }
    /* Remove outline (focus border) on buttons */
    .wpaicg-copy-button:focus,
    .wpaicg-thumbs-up-button:focus,
    .wpaicg-thumbs-down-button:focus {
        outline: none;
    }

    .wpaicg-feedback-modal-overlay {
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 10px; /* Added padding to prevent edge cutoff */
    }

    .wpaicg-feedback-modal {
        background-color: #fff;
        color: #333;
        padding: 20px;
        border-radius: 10px; /* Slightly increased border-radius for better aesthetics */
        width: 100%;
        max-width: 400px; /* Max width for larger screens */
        box-shadow: 0 0 15px rgba(0, 0, 0, 0.2); /* Increased shadow for better visibility */
        text-align: center;
        position: relative; /* To position the close button */
        margin: 0 auto; /* Centering the modal horizontally */
        box-sizing: border-box; /* Ensures padding is included in width */
    }

    .wpaicg-feedback-modal h2 {
        margin-top: 0;
    }

    .wpaicg-feedback-textarea {
        width: 100%;
        height: 80px;
        margin: 10px 0;
        border-radius: 5px;
    }

    .wpaicg-feedback-modal-buttons {
        display: flex;
        justify-content: flex-end; /* Aligns the submit button to the right */
        align-items: center;
        margin-top: 10px;
    }

    .wpaicg-feedback-message {
        flex-grow: 1; /* Takes up available space on the left */
        margin-right: 10px;
        text-align: left; /* Ensures the text aligns left */
    }

    .wpaicg-feedback-modal-submit {
        padding: 10px 20px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        background-color: #007bff;
        color: #fff;
        margin-left: 10px; /* Adds space between the button and the message */
    }

    .wpaicg-feedback-modal-close {
        position: absolute;
        top: 10px;
        right: 10px;
        background: none;
        border: none;
        color: #333;
        cursor: pointer;
    }

    /* Media Query for Mobile Devices */
    @media (max-width: 480px) {
        .wpaicg-feedback-modal {
            max-width: 95%; /* Reduce max width on smaller screens to ensure it doesn't touch the edges */
            padding: 15px;
            margin: 0 auto; /* Ensure modal is centered */
        }
        .wpaicg-feedback-modal-submit {
            padding: 8px 16px; /* Slightly smaller padding on mobile */
        }

        .wpaicg-feedback-textarea {
            height: 60px; /* Reduce height on mobile */
        }
    }
/* Styling the scrollbar track (part the thumb slides within) */
.wpaicg-chatbox ::-webkit-scrollbar-track {
background-color: <?php echo esc_html($wpaicg_chat_bgcolor)?>;
border-radius: 10px;
}

/* Styling the scrollbar thumb (the part that you drag) */
.wpaicg-chatbox ::-webkit-scrollbar-thumb {
    background-color: #888; /* Dark grey thumb */
    border-radius: 10px;
    border: 3px solid <?php echo esc_html($wpaicg_chat_bgcolor)?>;
}

/* Styling the scrollbar thumb on hover */
.wpaicg-chatbox ::-webkit-scrollbar-thumb:hover {
    background-color: #555; /* Black thumb on hover */
}

/* Setting the width of the scrollbar */
.wpaicg-chatbox ::-webkit-scrollbar {
    width: 8px; /* Narrow width */
}

/* For vertical scroll */
.wpaicg-chatbox ::-webkit-scrollbar {
    height: 8px; /* For horizontal scrolling */
}
.wpaicg-chatbox .wpaicg-conversation-starters {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    padding: 15px;
    justify-content: center;
    font-size: <?php echo esc_html($wpaicg_chat_fontsize)?>px;
    visibility: hidden;
}

.wpaicg-chatbox .wpaicg-conversation-starter {
    background-color: <?php echo esc_html($wpaicg_user_bg_color)?>;
    color: <?php echo esc_html($wpaicg_chat_fontcolor)?>;
    border: none;
    border-radius: 20px;
    padding: 5px 10px;
    cursor: pointer;
    transition: background-color 0.3s ease;
    font-size: <?php echo esc_html($wpaicg_chat_fontsize)?>px;
    transition: background-color 0.3s ease, transform 0.5s ease-out, opacity 0.5s ease-out;
    display: flex;
    opacity: 0;
    transform: translateY(20px);
}

.wpaicg-chatbox .wpaicg-conversation-starter:hover {
    filter: brightness(90%);
}
    .wpaicg-chatbox  h1, 
    .wpaicg-chatbox h2, 
    .wpaicg-chatbox h3, 
    .wpaicg-chatbox h4, 
    .wpaicg-chatbox h5, 
    .wpaicg-chatbox h6 {
        color: <?php echo esc_html($wpaicg_chat_fontcolor)?>;
    }
</style>
<style>
    .wpaicg-img-spinner {
        display: none;
        width: 16px;
        height: 16px;
        border: 2px solid rgba(0, 0, 0, 0.1);
        border-left-color: #000;
        border-radius: 50%;
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        0% { transform: rotate(0deg); }
        100% { transform: rotate(360deg); }
    }
    .wpaicg-thumbnail-placeholder {
        display: none;
        width: 50px;
        height: 50px;
        overflow: hidden;
    }
</style>

<style>
    .wpaicg_chat_widget,.wpaicg_chat_widget_content{
        z-index: 99999;
    }
    .wpaicg_chat_widget{
        overflow: hidden;
    }
    .wpaicg_widget_open.wpaicg_chat_widget{
        overflow: visible;
    }
    .wpaicg-chatbox-preview-box .wpaicg-chatbox-action-bar{
        width: calc(100% - 10px);
    }
    .wpaicg_widget_open .wpaicg-chatbox-action-bar{
        display: flex;
    }
    .wpaicg_chat_widget_content {
        /* Initial state of the chat window - hidden */
        opacity: 0;
        transform: scale(0.9);
        visibility: hidden;
        transition: opacity 0.3s ease, transform 0.3s ease, visibility 0s linear 0.3s;
    }

    .wpaicg_widget_open .wpaicg_chat_widget_content {
        /* Visible state of the chat window */
        opacity: 1;
        transform: scale(1);
        visibility: visible;
        transition-delay: 0s;
    }
    /* Updated shining light effect for hover without background */
    @keyframes shine {
        0% {
            background-position: -150px;
        }
        50% {
            background-position: 150px;
        }
        100% {
            background-position: -150px;
        }
    }

    .wpaicg_chat_widget .wpaicg_toggle {
        position: relative;
        overflow: hidden;
        transition: box-shadow 0.3s ease;
    }

    .wpaicg_chat_widget .wpaicg_toggle::before {
        content: '';
        position: absolute;
        top: -50%;
        left: -50%;
        width: 200%;
        height: 200%;
        /* Ensure gradient is completely transparent except for the shine */
        background: linear-gradient(to right, transparent, rgba(255,255,255,0.8) 50%, transparent) no-repeat;
        transform: rotate(30deg);
        /* Start with the shine outside of the visible area */
        background-position: -150px;
    }

    .wpaicg_chat_widget .wpaicg_toggle:hover::before {
        /* Apply the animation only on hover */
        animation: shine 2s infinite;
    }

    .wpaicg_chat_widget .wpaicg_toggle img {
        display: block;
        transition: opacity 0.3s ease;
    }
</style>
<style>
    .wpaicg-chatbox {
        width: <?php echo esc_html($wpaicg_chat_widget_width)?>;
        background-color: <?php echo esc_html($wpaicg_chat_bgcolor)?>;
        border-radius: <?php echo esc_html($wpaicg_chat_rounded)?>px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        max-width: 100%;
        overflow: hidden;
        border: 1px solid #E0E0E0;
        transition: box-shadow 0.3s ease;
        margin-right: 20px; /* Adjust as needed */
    }
    .wpaicg-chatbox:hover {
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover for interaction feedback */
    }

    .wpaicg-chatbox-content {
        overflow: hidden;
        flex-grow: 1;
        padding: 15px; /* Increased padding for more space around messages */
    }
    .wpaicg-chatbox-content ul {
        overflow-y: auto;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .wpaicg-chatbox-content ul .wpaicg-chat-user-message {
        margin-left: auto; /* This pushes the user messages to the right */
        background-color: <?php echo esc_html($wpaicg_user_bg_color)?>;
        color: <?php echo esc_html($wpaicg_chat_fontcolor)?>;
        font-size: <?php echo esc_html($wpaicg_chat_fontsize)?>px;
        margin-bottom: 20px;
        margin-right: 10px;
        padding: 10px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        width: fit-content;
    }

    .wpaicg-chatbox-content ul .wpaicg-chat-ai-message {
        background-color: <?php echo esc_html($wpaicg_ai_bg_color)?>;
        color: <?php echo esc_html($wpaicg_chat_fontcolor)?>;
        font-size: <?php echo esc_html($wpaicg_chat_fontsize)?>px;
        margin-bottom: 20px;
        margin-right: 10px;
        padding: 10px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        width: fit-content;
    }



    .wpaicg_chat_widget_content .wpaicg-chat-ai-message .wpaicg-chat-message,
    .wpaicg_chat_widget_content .wpaicg-chat-user-message .wpaicg-chat-message {
        color: inherit;
    }


    .wpaicg-chat-user-message{
        padding: 10px;
        background: <?php echo esc_html($wpaicg_user_bg_color)?>;
    }
    .wpaicg-chat-ai-message{
        padding: 10px;
        background: <?php echo esc_html($wpaicg_ai_bg_color)?>;
    }

    .wpaicg-bot-thinking{
        bottom: 0;
        font-size: 11px;
        color: <?php echo esc_html($wpaicg_thinking_color)?>;
        display: none;
    }

    .wpaicg-jumping-dots span {
        position: relative;
        bottom: 0px;
        -webkit-animation: wpaicg-jump 1500ms infinite;
        animation: wpaicg-jump 2s infinite;
    }
    .wpaicg-jumping-dots .wpaicg-dot-1{
        -webkit-animation-delay: 200ms;
        animation-delay: 200ms;
    }
    .wpaicg-jumping-dots .wpaicg-dot-2{
        -webkit-animation-delay: 400ms;
        animation-delay: 400ms;
    }
    .wpaicg-jumping-dots .wpaicg-dot-3{
        -webkit-animation-delay: 600ms;
        animation-delay: 600ms;
    }

    .wpaicg-chatbox-type {
        display: flex;
        align-items: center;
        padding: 15px;
        color: <?php echo esc_html($wpaicg_send_color)?>;
    }

    .wpaicg-chatbox-send {
        color: <?php echo esc_html($wpaicg_send_color)?>;
    }

    textarea.wpaicg-chatbox-typing {
        flex: 1;
        border: 1px solid <?php echo esc_html($wpaicg_border_text_field)?>;
        background-color: <?php echo esc_html($wpaicg_bg_text_field)?>;
        resize: vertical;
        border-radius: <?php echo esc_html($wpaicg_text_rounded)?>px;
        line-height: <?php echo $wpaicg_text_height - ($wpaicg_text_height * 0.1)?>px;
        padding-left: 1em;
        color: <?php echo esc_html($wpaicg_input_font_color)?>;
        font-size: <?php echo esc_html($wpaicg_chat_fontsize)?>px;
    }

    textarea.auto-expand {
        overflow: hidden; /* Prevents scrollbar flash during size adjustment */
        transition: box-shadow 0.5s ease-in-out;
        line-height: 2;
    }

    textarea.auto-expand.resizing {
        transition: box-shadow 0.5s ease-in-out;
        box-shadow: 0 0 12px rgba(81, 203, 238, 0.8);
        line-height: 2;
    }

    textarea.auto-expand:focus {
        outline: none;
        box-shadow: 0 0 5px rgba(81, 203, 238, 1);
        line-height: 2;
    }

    textarea.wpaicg-chatbox-typing::placeholder {
        color: <?php echo esc_html($wpaicg_input_font_color)?>;
    }

    .wpaicg-chat-message-error{
        color: #f00;
    }

    @-webkit-keyframes wpaicg-jump {
        0%   {bottom: 0px;}
        20%  {bottom: 5px;}
        40%  {bottom: 0px;}
    }

    @keyframes wpaicg-jump {
        0%   {bottom: 0px;}
        20%  {bottom: 5px;}
        40%  {bottom: 0px;}
    }

    /* Adjustments for screens that are 768px wide or less (typical for tablets and smartphones) */
    @media (max-width: 768px) {
        .wpaicg-chatbox {
            /* Adjust the width and right margin for smaller screens */
            width: auto; /* This makes the chat window adapt to the screen size */
            margin-right: 10px; /* Smaller margin for smaller devices */
            margin-left: 10px; /* Add some space on the left as well */
        }
    }

    /* Further adjustments for very small screens, like iPhones */
    @media (max-width: 480px) {
        .wpaicg-chatbox {
            /* You might want even smaller margins here */
            margin-right: 5px;
            margin-left: 5px;
        }
    }
    .wpaicg_chat_additions{
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        right: 20px;
    }
    
    .wpaicg-chatbox .wpaicg-mic-icon{
        color: <?php echo esc_html($wpaicg_mic_color)?>;
    }
    .wpaicg-chatbox .wpaicg-img-icon{
        color: <?php echo esc_html($wpaicg_send_color)?>;
    }
    .wpaicg-chatbox .wpaicg-pdf-icon{
        color: <?php echo esc_html($wpaicg_pdf_color)?>;
    }
    .wpaicg-chatbox .wpaicg-pdf-remove{
        color: <?php echo esc_html($wpaicg_pdf_color)?>;
        font-size: 33px;
        justify-content: center;
        align-items: center;
        width: 22px;
        height: 22px;
        line-height: unset;
        font-family: Arial, serif;
        border-radius: 50%;
        font-weight: normal;
        padding: 0;
    }
    .wpaicg-chatbox .wpaicg-pdf-loading{
        border-color: <?php echo esc_html($wpaicg_pdf_color)?>;
        border-bottom-color: transparent;
    }
    .wpaicg-chatbox .wpaicg-mic-icon.wpaicg-recording{
        color: <?php echo esc_html($wpaicg_stop_color)?>;
    }
    .wpaicg-chatbox .wpaicg-bot-thinking {
        bottom: 0;
        font-size: 11px;
        color: <?php echo esc_html($wpaicg_thinking_color)?>;
        display: none;
    }
    .wpaicg-chatbox-action-bar{
        top: 0; /* Position it at the top of the chat window */
        right: 0;
        left: 0; /* Ensure it spans the full width */
        height: 40px;
        padding: 0 10px;
        justify-content: center;
        align-items: center;
        color: <?php echo esc_html($wpaicg_bar_color)?>;
        background-color: <?php echo esc_html($wpaicg_footer_color)?>;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: background-color 0.3s ease;
        position: relative;
        top: 0;
        display: flex;
        justify-content: flex-end;
        min-height: 40px;
    }

    /* Button Styles */
    .wpaicg-chatbox-download-btn,
    .wpaicg-chatbox-audio-btn,
    .wpaicg-chatbox-clear-btn,
    .wpaicg-chatbox-fullscreen,
    .wpaicg-chatbox-close-btn {
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: center; /* Center content */
        margin: 0 5px; /* Adjust spacing between buttons */
        transition: background-color 0.3s ease; /* Smooth transition for interactions */
    }

    /* SVG Icon Adjustments */
    .wpaicg-chatbox-download-btn svg,
    .wpaicg-chatbox-audio-btn svg,
    .wpaicg-chatbox-clear-btn svg,
    .wpaicg-chatbox-fullscreen svg,
    .wpaicg-chatbox-close-btn svg {
        fill: currentColor;
        height: 16px; /* Adjust size for visibility */
        width: 16px;
    }
    /* Hover States for Button Interactions */
    .wpaicg-chatbox-download-btn:hover,
    .wpaicg-chatbox-clear-btn:hover,
    .wpaicg-chatbox-fullscreen:hover,
    .wpaicg-chatbox-close-btn:hover {
        background-color: rgba(0, 0, 0, 0.1); /* Slight highlight on hover */
    }

    .wpaicg-chatbox-fullscreen svg.wpaicg-exit-fullscreen{
        display: none;
        fill: none;
        height: 16px;
        width: 16px;
    }
    .wpaicg-chatbox-fullscreen svg.wpaicg-exit-fullscreen path{
        fill: currentColor;
    }
    .wpaicg-chatbox-fullscreen svg.wpaicg-active-fullscreen{
        fill: none;
        height: 16px;
        width: 16px;
    }
    .wpaicg-chatbox-fullscreen svg.wpaicg-active-fullscreen path{
        fill: currentColor;
    }
    .wpaicg-chatbox-fullscreen.wpaicg-fullscreen-box svg.wpaicg-active-fullscreen{
        display:none;
    }
    .wpaicg-chatbox-fullscreen.wpaicg-fullscreen-box svg.wpaicg-exit-fullscreen{
        display: block;
    }
    .wpaicg-fullscreened .wpaicg-chatbox-action-bar{
        top: 0;
        z-index: 99;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
        border-bottom-left-radius: 3px;
    }
</style>
<div class="wpaicg-chatbox<?php echo $wpaicg_has_action_bar ? ' wpaicg-chatbox-has-action-bar':''?><?php echo isset($wpaicg_chat_widget['footer_text']) && !empty($wpaicg_chat_widget['footer_text']) ? ' wpaicg-chat-widget-has-footer':' wpaicg-chat-widget-no-footer'?>"
     data-user-bg-color="<?php echo esc_html($wpaicg_user_bg_color)?>"
     data-color="<?php echo esc_html($wpaicg_chat_fontcolor)?>"
     data-fontsize="<?php echo esc_html($wpaicg_chat_fontsize)?>"
     data-ai-bg-color="<?php echo esc_html($wpaicg_ai_bg_color)?>"
     data-nonce="<?php echo esc_html(wp_create_nonce( 'wpaicg-chatbox' ))?>"
     data-post-id="<?php echo get_the_ID()?>"
     data-url="<?php echo home_url( $wp->request )?>"
     data-bot-id="<?php echo esc_html($wpaicg_bot_id)?>"
     data-width="<?php echo esc_html($wpaicg_chat_widget_width)?>"
     data-height="<?php echo esc_html($wpaicg_chat_widget_height)?>"
     data-footer="<?php echo isset($wpaicg_chat_widget['footer_text']) && !empty($wpaicg_chat_widget['footer_text']) ? 'true' : 'false'?>"
     data-speech="<?php echo esc_html($wpaicg_chat_to_speech)?>"
     data-voice="<?php echo esc_html($wpaicg_elevenlabs_voice)?>"
     data-elevenlabs-model="<?php echo esc_html($wpaicg_elevenlabs_model)?>"
     data-voice-error="<?php echo esc_html($wpaicg_elevenlabs_hide_error)?>"
     data-typewriter-effect = "<?php echo esc_html($wpaicg_typewriter_effect)?>"
     data-typewriter-speed="<?php echo esc_html(get_option('wpaicg_typewriter_speed', 1)); ?>"
     data-text_height="<?php echo esc_html($wpaicg_text_height)?>"
     data-text_rounded="<?php echo esc_html($wpaicg_text_rounded)?>"
     data-chat_rounded="<?php echo esc_html($wpaicg_chat_rounded)?>"
     data-voice_service="<?php echo esc_html($wpaicg_chat_voice_service)?>"
     data-voice_language="<?php echo esc_html($wpaicg_voice_language)?>"
     data-voice_name="<?php echo esc_html($wpaicg_voice_name)?>"
     data-voice_device="<?php echo esc_html($wpaicg_voice_device)?>"
     data-voice_speed="<?php echo esc_html($wpaicg_voice_speed)?>"
     data-voice_pitch="<?php echo esc_html($wpaicg_voice_pitch)?>"
     data-openai_model="<?php echo esc_html($wpaicg_openai_model)?>"
     data-openai_voice="<?php echo esc_html($wpaicg_openai_voice)?>"
     data-openai_output_format="<?php echo esc_html($wpaicg_openai_output_format)?>"
     data-openai_voice_speed="<?php echo esc_html($wpaicg_openai_voice_speed)?>"
     data-openai_stream_nav="<?php echo esc_html($wpaicg_stream_nav_setting) ?>"
     data-autoload_chat_conversations="<?php echo esc_html($wpaicg_autoload_chat_conversations)?>"
     data-copy_btn="<?php echo esc_html($wpaicg_chat_copy_btn)?>"
     data-feedback_btn = "<?php echo esc_html($wpaicg_chat_feedback_btn)?>"
     data-feedback_title = "<?php echo esc_html($wpaicg_chat_feedback_title)?>"
     data-feedback_message = "<?php echo esc_html($wpaicg_chat_feedback_message)?>"
     data-feedback_success = "<?php echo esc_html($wpaicg_chat_feedback_success)?>"
     data-user-voice-control = "<?php echo esc_html($wpaicg_chat_audio_btn)?>"
     data-voice-muted-by-default="<?php echo esc_html($wpaicg_voice_muted_by_default); ?>"
     data-memory-limit = "<?php echo esc_html($wpaicg_conversation_cut)?>"
     data-lead-collection = "<?php echo esc_html($wpaicg_lead_collection)?>"
     data-lead-title = "<?php echo esc_html($wpaicg_lead_title)?>"
     data-lead-name = "<?php echo esc_html($wpaicg_lead_name)?>"
     data-enable-lead-name = "<?php echo esc_html($wpaicg_enable_lead_name)?>"
     data-lead-email = "<?php echo esc_html($wpaicg_lead_email)?>"
     data-enable-lead-email = "<?php echo esc_html($wpaicg_enable_lead_email)?>"
     data-lead-phone = "<?php echo esc_html($wpaicg_lead_phone)?>"
     data-enable-lead-phone = "<?php echo esc_html($wpaicg_enable_lead_phone)?>"
     data-bg_text_field = "<?php echo esc_html($wpaicg_bg_text_field)?>"
     data-bg_text_field_font_color = "<?php echo esc_html($wpaicg_input_font_color)?>"
     data-bg_text_field_border_color = "<?php echo esc_html($wpaicg_border_text_field)?>"
     data-assistant-enabled="<?php echo esc_html($assistantEnabled ? 'true' : 'false'); ?>"
     data-type="widget"
>
    <?php

    if($wpaicg_has_action_bar):
        ?>
        <div class="wpaicg-chatbox-action-bar">
            <?php
            if($wpaicg_chat_download_btn):
                ?>
            <span data-type="widget" class="wpaicg-chatbox-download-btn" title="Download Chat">
                <svg role="presentation" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512"  xml:space="preserve"><path class="st0" d="M243.591,309.362c3.272,4.317,7.678,6.692,12.409,6.692c4.73,0,9.136-2.376,12.409-6.689l89.594-118.094 c3.348-4.414,4.274-8.692,2.611-12.042c-1.666-3.35-5.631-5.198-11.168-5.198H315.14c-9.288,0-16.844-7.554-16.844-16.84V59.777 c0-11.04-8.983-20.027-20.024-20.027h-44.546c-11.04,0-20.022,8.987-20.022,20.027v97.415c0,9.286-7.556,16.84-16.844,16.84 h-34.305c-5.538,0-9.503,1.848-11.168,5.198c-1.665,3.35-0.738,7.628,2.609,12.046L243.591,309.362z"/><path class="st0" d="M445.218,294.16v111.304H66.782V294.16H0v152.648c0,14.03,11.413,25.443,25.441,25.443h461.118 c14.028,0,25.441-11.413,25.441-25.443V294.16H445.218z"/></svg>
            </span>
            <?php
            endif;
            ?>
            <?php
            if($wpaicg_chat_audio_btn):
                ?>
            <span data-type="widget" class="wpaicg-chatbox-audio-btn" title="Download Chat">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M301.1 34.8C312.6 40 320 51.4 320 64l0 384c0 12.6-7.4 24-18.9 29.2s-25 3.1-34.4-5.3L131.8 352 64 352c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l67.8 0L266.7 40.1c9.4-8.4 22.9-10.4 34.4-5.3zM425 167l55 55 55-55c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-55 55 55 55c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-55-55-55 55c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l55-55-55-55c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0z"/></svg>
            </span>
            <?php
            endif;
            ?>
            <?php
            if($wpaicg_chat_fullscreen):
                ?>
            <span data-type="widget" class="wpaicg-chatbox-fullscreen" title="Toggle Fullscreen">
                <svg class="wpaicg-active-fullscreen" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10 15H15V10H13.2V13.2H10V15ZM6 15V13.2H2.8V10H1V15H6ZM10 2.8H12.375H13.2V6H15V1H10V2.8ZM6 1V2.8H2.8V6H1V1H6Z"/></svg>
                <svg class="wpaicg-exit-fullscreen" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M1 6L6 6L6 1L4.2 1L4.2 4.2L1 4.2L1 6Z"/><path d="M15 10L10 10L10 15L11.8 15L11.8 11.8L15 11.8L15 10Z"/><path d="M6 15L6 10L1 10L1 11.8L4.2 11.8L4.2 15L6 15Z"/><path d="M10 1L10 6L15 6L15 4.2L11.8 4.2L11.8 1L10 1Z"/></svg>
            </span>
            <?php
            endif;
            ?>
            <?php
            if($wpaicg_chat_clear_btn):
                ?>
                <span class="wpaicg-chatbox-clear-btn" title="Clear Chat">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80h145l-19-28.4c-1.5-2.2-4-3.6-6.7-3.6H177.1c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80H368h48 8c13.3 0 24 10.7 24 24s-10.7 24-24 24h-8V432c0 44.2-35.8 80-80 80H112c-44.2 0-80-35.8-80-80V128H24c-13.3 0-24-10.7-24-24S10.7 80 24 80h8H80 93.8l36.7-55.1C140.9 9.4 158.4 0 177.1 0h93.7c18.7 0 36.2 9.4 46.6 24.9zM80 128V432c0 17.7 14.3 32 32 32H336c17.7 0 32-14.3 32-32V128H80zm80 64V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
        </span>
        <?php
            endif;
            ?>
            <?php
            if($wpaicg_chat_close_btn):
                ?>
                <span class="wpaicg-chatbox-close-btn" title="Close Chat">
                    <svg viewBox="0 0 1024 1024" xmlns="http://www.w3.org/2000/svg"><path d="M195.2 195.2a64 64 0 0 1 90.496 0L512 421.504 738.304 195.2a64 64 0 0 1 90.496 90.496L602.496 512 828.8 738.304a64 64 0 0 1-90.496 90.496L512 602.496 285.696 828.8a64 64 0 0 1-90.496-90.496L421.504 512 195.2 285.696a64 64 0 0 1 0-90.496z"/></svg>
                </span>
            <?php
            endif;
            ?>
        </div>
    <?php endif; ?>
    <div class="wpaicg-chatbox-content">
        <ul class="wpaicg-chatbox-messages">
            <?php
            if($wpaicg_save_logs && $wpaicg_log_notice && !empty($wpaicg_log_notice_message)):
                ?>
                <li style="log_notification">
                    <p>
                    <span class="wpaicg-chat-message">
                        <?php echo esc_html(str_replace("\\",'',$wpaicg_log_notice_message))?>
                    </span>
                    </p>
                </li>
            <?php
            endif;
            ?>
            <li class="wpaicg-chat-ai-message">
                <span class="wpaicg-chat-message">
                    <?php echo esc_html(str_replace("\\",'',$wpaicg_welcome_message))?>
                </span>
            </li>
        </ul>
    </div>
     <!-- Conversation Starters -->
    <?php if (!empty($wpaicg_conversation_starters_widget)): ?>
        <div class="wpaicg-conversation-starters">
            <?php foreach ($wpaicg_conversation_starters_widget as $starter): ?>
                <button type="button" class="wpaicg-conversation-starter">
                    <?php echo esc_html($starter['text']); ?>
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <span class="wpaicg-bot-thinking" style="padding-left: 15px;color: <?php echo esc_html($wpaicg_thinking_color)?>;"><?php echo esc_html(str_replace("\\",'',$wpaicg_ai_thinking))?>&nbsp;<span class="wpaicg-jumping-dots"><span class="wpaicg-dot-1">.</span><span class="wpaicg-dot-2">.</span><span class="wpaicg-dot-3">.</span></span></span>
    <div class="wpaicg-chatbox-type">
        <textarea type="text" class="auto-expand wpaicg-chatbox-typing" placeholder="<?php echo esc_html(str_replace("\\",'',$wpaicg_typing_placeholder))?>"></textarea>
        <div class="wpaicg_chat_additions">
            <span class="wpaicg-thumbnail-placeholder"></span>
            <?php if($wpaicg_audio_enable): ?>
                <span class="wpaicg-mic-icon" data-type="widget">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M176 0C123 0 80 43 80 96V256c0 53 43 96 96 96s96-43 96-96V96c0-53-43-96-96-96zM48 216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 89.1 66.2 162.7 152 174.4V464H104c-13.3 0-24 10.7-24 24s10.7 24 24 24h72 72c13.3 0 24-10.7 24-24s-10.7-24-24-24H200V430.4c85.8-11.7 152-85.3 152-174.4V216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 70.7-57.3 128-128 128s-128-57.3-128-128V216z"/></svg>
            </span>
            <?php endif; ?>
            <span class="wpaicg-img-icon" data-type="widget" style="<?php echo $wpaicg_image_enable ? '' : 'display:none'?>">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-image"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                <input type="file" id="imageUpload" class="wpaicg-img-file" accept="image/png, image/jpeg, image/webp, image/gif" style="display: none;" />
                <!-- add nonce -->
                <input type="hidden" id="wpaicg-img-nonce" value="<?php echo esc_html(wp_create_nonce( 'wpaicg-img-nonce' ))?>" />
            </span>
            <span class="wpaicg-img-spinner"></span>
            <?php
            if($wpaicg_pdf_enable && \WPAICG\wpaicg_util_core()->wpaicg_is_pro()):
                ?>
                <span class="wpaicg-pdf-icon" data-type="widget">
                <svg version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512"  xml:space="preserve"><path class="st0" d="M378.413,0H208.297h-13.182L185.8,9.314L57.02,138.102l-9.314,9.314v13.176v265.514 c0,47.36,38.528,85.895,85.896,85.895h244.811c47.353,0,85.881-38.535,85.881-85.895V85.896C464.294,38.528,425.766,0,378.413,0z M432.497,426.105c0,29.877-24.214,54.091-54.084,54.091H133.602c-29.884,0-54.098-24.214-54.098-54.091V160.591h83.716 c24.885,0,45.077-20.178,45.077-45.07V31.804h170.116c29.87,0,54.084,24.214,54.084,54.092V426.105z"/><path class="st0" d="M171.947,252.785h-28.529c-5.432,0-8.686,3.533-8.686,8.825v73.754c0,6.388,4.204,10.599,10.041,10.599 c5.711,0,9.914-4.21,9.914-10.599v-22.406c0-0.545,0.279-0.817,0.824-0.817h16.436c20.095,0,32.188-12.226,32.188-29.612 C204.136,264.871,192.182,252.785,171.947,252.785z M170.719,294.888h-15.208c-0.545,0-0.824-0.272-0.824-0.81v-23.23 c0-0.545,0.279-0.816,0.824-0.816h15.208c8.42,0,13.447,5.027,13.447,12.498C184.167,290,179.139,294.888,170.719,294.888z"/><path class="st0" d="M250.191,252.785h-21.868c-5.432,0-8.686,3.533-8.686,8.825v74.843c0,5.3,3.253,8.693,8.686,8.693h21.868 c19.69,0,31.923-6.249,36.81-21.324c1.76-5.3,2.723-11.681,2.723-24.857c0-13.175-0.964-19.557-2.723-24.856 C282.113,259.034,269.881,252.785,250.191,252.785z M267.856,316.896c-2.318,7.331-8.965,10.459-18.21,10.459h-9.23 c-0.545,0-0.824-0.272-0.824-0.816v-55.146c0-0.545,0.279-0.817,0.824-0.817h9.23c9.245,0,15.892,3.128,18.21,10.46 c0.95,3.128,1.62,8.56,1.62,17.93C269.476,308.336,268.805,313.768,267.856,316.896z"/><path class="st0" d="M361.167,252.785h-44.812c-5.432,0-8.7,3.533-8.7,8.825v73.754c0,6.388,4.218,10.599,10.055,10.599 c5.697,0,9.914-4.21,9.914-10.599v-26.351c0-0.538,0.265-0.81,0.81-0.81h26.086c5.837,0,9.23-3.532,9.23-8.56 c0-5.028-3.393-8.553-9.23-8.553h-26.086c-0.545,0-0.81-0.272-0.81-0.817v-19.425c0-0.545,0.265-0.816,0.81-0.816h32.733 c5.572,0,9.245-3.666,9.245-8.553C370.411,256.45,366.738,252.785,361.167,252.785z"/></svg>
            </span>
                <span class="wpaicg-pdf-loading" style="display: none"></span>
                <input data-type="widget" data-limit="<?php echo esc_html($wpaicg_pdf_pages)?>" type="file" accept="application/pdf" class="wpaicg-pdf-file" style="display: none">
                <span data-type="widget" alt="<?php echo esc_html__('Clear','gpt3-ai-content-generator')?>" title="<?php echo esc_html__('Clear','gpt3-ai-content-generator')?>" class="wpaicg-pdf-remove" style="display: none">&times;</span>
            <?php
            endif;
            ?>
            <span class="wpaicg-chatbox-send">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
            </span>
        </div>
    </div>
    <?php
    if(isset($wpaicg_chat_widget['footer_text']) && !empty($wpaicg_chat_widget['footer_text'])):
        ?>
        <div class="wpaicg-chatbox-footer" style="background-color: <?php echo esc_html($wpaicg_footer_color)?>; color: <?php echo esc_html($wpaicg_footer_font_color)?>">
            <?php
            echo wp_kses_post(str_replace("\\", '', htmlspecialchars_decode($wpaicg_chat_widget['footer_text'])));
            ?>
        </div>
    <?php endif; ?>
</div>
