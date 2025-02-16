<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wp,$wpdb;
$table = $wpdb->prefix . 'wpaicg';
$wpaicg_bot_id = 0;
$existingValue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE name = %s", 'wpaicg_settings' ), ARRAY_A );
$wpaicg_chat_shortcode_options = get_option('wpaicg_chat_shortcode_options',[]);
$wpaicg_stream_nav_setting = get_option('wpaicg_shortcode_stream', '1'); // Default to '1' if not set
$wpaicg_conversation_starters_json = get_option('wpaicg_conversation_starters', '');
$wpaicg_conversation_starters = !empty($wpaicg_conversation_starters_json) ? json_decode($wpaicg_conversation_starters_json, true) : [];

/*Check Custom Shortcode ID*/
if(isset($atts) && isset($atts['id']) && !empty($atts['id'])) {
    $wpaicg_bot = get_post($atts['id']);
    if ($wpaicg_bot) {
        $wpaicg_bot_id = $wpaicg_bot->ID;
        if(strpos($wpaicg_bot->post_content,'\"') !== false) {
            $wpaicg_bot->post_content = str_replace('\"', '&quot;', $wpaicg_bot->post_content);
        }
        if(strpos($wpaicg_bot->post_content,"\'") !== false) {
            $wpaicg_bot->post_content = str_replace('\\', '', $wpaicg_bot->post_content);
        }
        $wpaicg_chat_shortcode_options = json_decode($wpaicg_bot->post_content, true);
        $wpaicg_chat_shortcode_options['width'] = isset($wpaicg_chat_shortcode_options['width']) && !empty($wpaicg_chat_shortcode_options['width']) ? $wpaicg_chat_shortcode_options['width'].'px' : '350px';
        $wpaicg_chat_shortcode_options['height'] = isset($wpaicg_chat_shortcode_options['height']) && !empty($wpaicg_chat_shortcode_options['height']) ? $wpaicg_chat_shortcode_options['height'].'px' : '400px';

        $wpaicg_conversation_starters = isset($wpaicg_chat_shortcode_options['conversation_starters']) && is_array($wpaicg_chat_shortcode_options['conversation_starters']) ? $wpaicg_chat_shortcode_options['conversation_starters'] : [];
        $adapted_conversation_starters = [];
        // Proceed with the conversion only if $wpaicg_conversation_starters is not empty
        if (!empty($wpaicg_conversation_starters)) {
            $adapted_conversation_starters = array_map(function($text, $index) {
                // Ensure that $text is always treated as a string, even if it's null or other types
                $text = is_string($text) ? $text : '';
                return ['index' => $index, 'text' => $text];
            }, $wpaicg_conversation_starters, array_keys($wpaicg_conversation_starters));
        }

        // Update $wpaicg_conversation_starters with the adapted version or keep it as an empty array if it was initially empty
        $wpaicg_conversation_starters = $adapted_conversation_starters;

        $wpaicg_stream_nav_setting = isset($wpaicg_chat_shortcode_options['openai_stream_nav']) ? $wpaicg_chat_shortcode_options['openai_stream_nav'] : '0';
    }
}
/*End check*/
$default_setting = array(
    'language' => 'en',
    'tone' => 'friendly',
    'profession' => 'none',
    'model' => 'gpt-4o-mini',
    'temperature' => $existingValue['temperature'],
    'max_tokens' => $existingValue['max_tokens'],
    'top_p' => $existingValue['top_p'],
    'best_of' => $existingValue['best_of'],
    'frequency_penalty' => $existingValue['frequency_penalty'],
    'presence_penalty' => $existingValue['presence_penalty'],
    'ai_thinking' => esc_html__('Gathering thoughts','gpt3-ai-content-generator'),
    'placeholder' => esc_html__('Type a message','gpt3-ai-content-generator'),
    'welcome' => esc_html__('Hello 👋, how can I help you today?','gpt3-ai-content-generator'),
    'newchat' => esc_html__('New Chat','gpt3-ai-content-generator'),
    'remember_conversation' => 'yes',
    'conversation_cut' => 100,
    'content_aware' => 'yes',
    'embedding' =>  false,
    'embedding_type' =>  false,
    'embedding_pdf' => false,
    'pdf_color' => '#d1e8ff',
    'embedding_top' =>  false,
    'no_answer' => '',
    'fontsize' => 14,
    'fontcolor' => '#495057',
    'user_bg_color' => '#ccf5e1',
    'ai_bg_color' => '#d1e8ff',
    'ai_icon_url' => '',
    'ai_icon' => 'default',
    'width' => '100%',
    'height' => '50%',
    'save_logs' => false,
    'log_notice' => false,
    'log_notice_message' => esc_html__('Please note that your conversations will be recorded.','gpt3-ai-content-generator'),
    'bgcolor' => '#f8f9fa',
    'bg_text_field' => '#ffffff',
    'input_font_color' => '#495057',
    'send_color' => '#d1e8ff',
    // Default to true if not set, ensuring checkbox reflects enabled state by default
    'send_button_enabled' => array_key_exists('send_button_enabled', $wpaicg_chat_shortcode_options) ? $wpaicg_chat_shortcode_options['send_button_enabled'] : true,
    'border_text_field' => '#ced4da',
    'footer_text' => '',
    'footer_color' => '#ffffff',
    'footer_font_color' => '#495057',
    'audio_enable' => false,
    'image_enable' => false,
    'mic_color' => '#d1e8ff',
    'stop_color' => '#d1e8ff',
    'fullscreen' => false,
    'download_btn' => false,
    'audio_btn' => false,
    'muted_by_default' => false,
    'clear_btn' => false,
    'sidebar' => false,
    'copy_btn' => false,
    'feedback_btn' => false,
    'feedback_title' => __('Feedback','gpt3-ai-content-generator'),
    'feedback_message' => __('Please provide details: (optional)','gpt3-ai-content-generator'),
    'feedback_success' => __('Thank you for your feedback!','gpt3-ai-content-generator'),
    'bar_color' => '#495057',
    'thinking_color' => '#495057',
    'chat_to_speech' => false,
    'elevenlabs_voice' => '',
    'elevenlabs_model' => '',
    'text_height' => 60,
    'text_rounded' => 8,
    'pdf_pages' => 120,
    'chat_rounded' => 8,
    'voice_language' => 'en-US',
    'voice_name' => 'en-US-Studio-M',
    'voice_device' => '',
    'voice_speed' => 1,
    'voice_pitch' => 0,
    'voice_service' => '',
    'openai_model' => 'tts-1',
    'openai_voice' => 'alloy',
    'openai_output_format' => 'mp3',
    'openai_voice_speed' => '1.0',
    "lead_collection" => "0",
    "lead_title" => "Let us know how to contact you",
    "lead_name" => "Name",
    "enable_lead_name" => "1",
    "lead_email" => "Email",
    "enable_lead_email" => "1",
    "lead_phone" => "Phone",
    "enable_lead_phone" => "1",
);
$wpaicg_settings = shortcode_atts($default_setting, $wpaicg_chat_shortcode_options);
$wpaicg_ai_thinking = $wpaicg_settings['ai_thinking'];
$wpaicg_typing_placeholder = $wpaicg_settings['placeholder'];
$wpaicg_welcome_message = $wpaicg_settings['welcome'];
$wpaicg_new_chat = $wpaicg_settings['newchat'];
$wpaicg_chat_content_aware = $wpaicg_settings['content_aware'];
$wpaicg_font_color = $wpaicg_settings['fontcolor'];
$wpaicg_font_size = $wpaicg_settings['fontsize'];
$wpaicg_user_bg_color = $wpaicg_settings['user_bg_color'];
$wpaicg_ai_bg_color = $wpaicg_settings['ai_bg_color'];
$wpaicg_save_logs = isset($wpaicg_settings['save_logs']) && !empty($wpaicg_settings['save_logs']) ? $wpaicg_settings['save_logs'] : false;
$wpaicg_log_notice = isset($wpaicg_settings['log_notice']) && !empty($wpaicg_settings['log_notice']) ? $wpaicg_settings['log_notice'] : false;
$wpaicg_log_notice_message = isset($wpaicg_settings['log_notice_message']) && !empty($wpaicg_settings['log_notice_message']) ? $wpaicg_settings['log_notice_message'] : esc_html__('Please note that your conversations will be recorded.','gpt3-ai-content-generator');
$wpaicg_include_footer = (isset($wpaicg_settings['footer_text']) && !empty($wpaicg_settings['footer_text'])) ? 5 : 0;
$wpaicg_audio_enable = $wpaicg_settings['audio_enable'];
$wpaicg_image_enable = $wpaicg_settings['image_enable'];
$wpaicg_pdf_enable = $wpaicg_settings['embedding_pdf'];
$wpaicg_mic_color = (isset($wpaicg_settings['mic_color']) && !empty($wpaicg_settings['mic_color'])) ? $wpaicg_settings['mic_color'] : '#d1e8ff';
$wpaicg_stop_color = (isset($wpaicg_settings['stop_color']) && !empty($wpaicg_settings['stop_color'])) ? $wpaicg_settings['stop_color'] : '#d1e8ff';
$wpaicg_chat_fullscreen = isset($wpaicg_settings['fullscreen']) && !empty($wpaicg_settings['fullscreen']) ? $wpaicg_settings['fullscreen'] : false;
$wpaicg_chat_download_btn = isset($wpaicg_settings['download_btn']) && !empty($wpaicg_settings['download_btn']) ? $wpaicg_settings['download_btn'] : false;
$wpaicg_chat_audio_btn = isset($wpaicg_settings['audio_btn']) && !empty($wpaicg_settings['audio_btn']) ? $wpaicg_settings['audio_btn'] : false;
$wpaicg_voice_muted_by_default = isset($wpaicg_settings['muted_by_default']) && !empty($wpaicg_settings['muted_by_default']) ? $wpaicg_settings['muted_by_default'] : false;
$wpaicg_chat_clear_btn = isset($wpaicg_settings['clear_btn']) && !empty($wpaicg_settings['clear_btn']) ? $wpaicg_settings['clear_btn'] : false;
$wpaicg_chat_sidebar = isset($wpaicg_settings['sidebar']) && !empty($wpaicg_settings['sidebar']) ? $wpaicg_settings['sidebar'] : false;
$wpaicg_chat_copy_btn = isset($wpaicg_settings['copy_btn']) && !empty($wpaicg_settings['copy_btn']) ? $wpaicg_settings['copy_btn'] : false;
$wpaicg_chat_feedback_btn = isset($wpaicg_settings['feedback_btn']) && !empty($wpaicg_settings['feedback_btn']) ? $wpaicg_settings['feedback_btn'] : false;
$wpaicg_chat_feedback_title = isset($wpaicg_settings['feedback_title']) && !empty($wpaicg_settings['feedback_title']) ? $wpaicg_settings['feedback_title'] : __('Feedback','gpt3-ai-content-generator');
$wpaicg_chat_feedback_message = isset($wpaicg_settings['feedback_message']) && !empty($wpaicg_settings['feedback_message']) ? $wpaicg_settings['feedback_message'] : __('Please provide details: (optional)','gpt3-ai-content-generator');
$wpaicg_chat_feedback_success = isset($wpaicg_settings['feedback_success']) && !empty($wpaicg_settings['feedback_success']) ? $wpaicg_settings['feedback_success'] : __('Thank you for your feedback!','gpt3-ai-content-generator');
$wpaicg_chat_widget_width = isset($wpaicg_settings['width']) && !empty($wpaicg_settings['width']) ? $wpaicg_settings['width'] : '100%';
$wpaicg_chat_widget_height = isset($wpaicg_settings['height']) && !empty($wpaicg_settings['height']) ? $wpaicg_settings['height'] : '400';
$wpaicg_bar_color = isset($wpaicg_settings['bar_color']) && !empty($wpaicg_settings['bar_color']) ? $wpaicg_settings['bar_color'] : '#ffffff';
$wpaicg_thinking_color = isset($wpaicg_settings['thinking_color']) && !empty($wpaicg_settings['thinking_color']) ? $wpaicg_settings['thinking_color'] : '#495057';
$wpaicg_chat_to_speech = isset($wpaicg_settings['chat_to_speech']) ? $wpaicg_settings['chat_to_speech'] : false;
$wpaicg_elevenlabs_voice = isset($wpaicg_settings['elevenlabs_voice']) ? $wpaicg_settings['elevenlabs_voice'] : '';
$wpaicg_elevenlabs_model = isset($wpaicg_settings['elevenlabs_model']) ? $wpaicg_settings['elevenlabs_model'] : '';
$wpaicg_elevenlabs_hide_error = get_option('wpaicg_elevenlabs_hide_error', false);
$wpaicg_typewriter_effect = get_option('wpaicg_typewriter_effect', false);
$wpaicg_typewriter_speed = get_option('wpaicg_typewriter_speed', 1);
$wpaicg_elevenlabs_api = get_option('wpaicg_elevenlabs_api', '');
$wpaicg_text_height = isset($wpaicg_settings['text_height']) && !empty($wpaicg_settings['text_height']) ? $wpaicg_settings['text_height'] : 60;
$wpaicg_text_rounded = isset($wpaicg_settings['text_rounded']) && !empty($wpaicg_settings['text_height']) ? $wpaicg_settings['text_rounded'] : 8;
$wpaicg_chat_rounded = isset($wpaicg_settings['chat_rounded']) && !empty($wpaicg_settings['text_height']) ? $wpaicg_settings['chat_rounded'] : 8;
$wpaicg_chat_voice_service = isset($wpaicg_settings['voice_service']) && !empty($wpaicg_settings['voice_service']) ? $wpaicg_settings['voice_service'] : '';
$wpaicg_voice_language = isset($wpaicg_settings['voice_language']) && !empty($wpaicg_settings['voice_language']) ? $wpaicg_settings['voice_language'] : 'en-US';
$wpaicg_voice_name = isset($wpaicg_settings['voice_name']) && !empty($wpaicg_settings['voice_name']) ? $wpaicg_settings['voice_name'] : 'en-US-Studio-M';
$wpaicg_voice_device = isset($wpaicg_settings['voice_device']) && !empty($wpaicg_settings['voice_device']) ? $wpaicg_settings['voice_device'] : '';
$wpaicg_voice_speed = isset($wpaicg_settings['voice_speed']) && !empty($wpaicg_settings['voice_speed']) ? $wpaicg_settings['voice_speed'] : 1;
$wpaicg_voice_pitch = isset($wpaicg_settings['voice_pitch']) && !empty($wpaicg_settings['voice_pitch']) ? $wpaicg_settings['voice_pitch'] : 0;
$wpaicg_openai_model = isset($wpaicg_settings['openai_model']) && !empty($wpaicg_settings['openai_model']) ? $wpaicg_settings['openai_model'] : 'tts-1';
$wpaicg_openai_voice = isset($wpaicg_settings['openai_voice']) && !empty($wpaicg_settings['openai_voice']) ? $wpaicg_settings['openai_voice'] : 'alloy';
$wpaicg_openai_output_format = isset($wpaicg_settings['openai_output_format']) && !empty($wpaicg_settings['openai_output_format']) ? $wpaicg_settings['openai_output_format'] : 'mp3';
$wpaicg_openai_voice_speed = isset($wpaicg_settings['openai_voice_speed']) && !empty($wpaicg_settings['openai_voice_speed']) ? $wpaicg_settings['openai_voice_speed'] : '1.0';
$wpaicg_google_api_key = get_option('wpaicg_google_api_key', '');
$wpaicg_input_font_color = isset($wpaicg_settings['input_font_color']) && !empty($wpaicg_settings['input_font_color']) ? $wpaicg_settings['input_font_color'] : '#495057';
$wpaicg_footer_color = isset($wpaicg_settings['footer_color']) && !empty($wpaicg_settings['footer_color']) ? $wpaicg_settings['footer_color'] : '#ffffff';
$wpaicg_autoload_chat_conversations = get_option('wpaicg_autoload_chat_conversations', 0);
$wpaicg_conversation_cut = isset($wpaicg_settings['conversation_cut']) && !empty($wpaicg_settings['conversation_cut']) ? $wpaicg_settings['conversation_cut'] : 100;

// Lead Collection Settings
$wpaicg_lead_collection = isset($wpaicg_settings['lead_collection']) && !empty($wpaicg_settings['lead_collection']) ? $wpaicg_settings['lead_collection'] : 0;
$wpaicg_lead_title = isset($wpaicg_settings['lead_title']) && !empty($wpaicg_settings['lead_title']) ? $wpaicg_settings['lead_title'] : 'Let us know how to contact you';
$wpaicg_lead_name = isset($wpaicg_settings['lead_name']) && !empty($wpaicg_settings['lead_name']) ? $wpaicg_settings['lead_name'] : 'Name';
$wpaicg_enable_lead_name = isset($wpaicg_settings['enable_lead_name']) && !empty($wpaicg_settings['enable_lead_name']) ? $wpaicg_settings['enable_lead_name'] : 0;
$wpaicg_lead_email = isset($wpaicg_settings['lead_email']) && !empty($wpaicg_settings['lead_email']) ? $wpaicg_settings['lead_email'] : 'Email';
$wpaicg_enable_lead_email = isset($wpaicg_settings['enable_lead_email']) && !empty($wpaicg_settings['enable_lead_email']) ? $wpaicg_settings['enable_lead_email'] : 0;
$wpaicg_lead_phone = isset($wpaicg_settings['lead_phone']) && !empty($wpaicg_settings['lead_phone']) ? $wpaicg_settings['lead_phone'] : 'Phone';
$wpaicg_enable_lead_phone = isset($wpaicg_settings['enable_lead_phone']) && !empty($wpaicg_settings['enable_lead_phone']) ? $wpaicg_settings['enable_lead_phone'] : 0;
// bg_text_field
$wpaicg_bg_text_field = isset($wpaicg_settings['bg_text_field']) && !empty($wpaicg_settings['bg_text_field']) ? $wpaicg_settings['bg_text_field'] : '#ffffff';
// border_text_field
$wpaicg_border_text_field = isset($wpaicg_settings['border_text_field']) && !empty($wpaicg_settings['border_text_field']) ? $wpaicg_settings['border_text_field'] : '#ced4da';

?>
<style>
    /* Styling the scrollbar track within the chatbot window only */
    .wpaicg-chat-shortcode ::-webkit-scrollbar-track {
        background-color: <?php echo esc_html($wpaicg_settings['bgcolor'])?>; /* Light grey track */
        border-radius: 10px;
    }

    /* Styling the scrollbar thumb within the chatbot window only */
    .wpaicg-chat-shortcode ::-webkit-scrollbar-thumb {
        background-color: #888; /* Dark grey thumb */
        border-radius: 10px;
        border: 3px solid <?php echo esc_html($wpaicg_settings['bgcolor'])?>; /* White border around thumb */
    }

    /* Styling the scrollbar thumb on hover within the chatbot window only */
    .wpaicg-chat-shortcode ::-webkit-scrollbar-thumb:hover {
        background-color: #555; /* Black thumb on hover */
    }

    /* Setting the width of the scrollbar within the chatbot window only */
    .wpaicg-chat-shortcode ::-webkit-scrollbar {
        width: 8px; /* Narrow width */
        height: 8px; /* For horizontal scrolling */
    }
    .wpaicg-conversation-starters {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        padding: 15px;
        justify-content: center;
        font-size: <?php echo esc_html($wpaicg_font_size)?>px;
        visibility: hidden;
    }

    .wpaicg-conversation-starter {
        background-color: <?php echo esc_html($wpaicg_user_bg_color)?>;
        border: none;
        border-radius: 20px;
        padding: 5px 10px;
        cursor: pointer;
        font-size: <?php echo esc_html($wpaicg_font_size)?>px;
        color: <?php echo esc_html($wpaicg_font_color)?>;
        transition: background-color 0.3s ease, transform 0.5s ease-out, opacity 0.5s ease-out;
        display: flex;
        opacity: 0;
        transform: translateY(20px);
    }

    .wpaicg-conversation-starter:hover {
        filter: brightness(90%);
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
    .blinking-cursor {
    font-weight: 100;
    color: #ffffff; /* or the color you want */
    animation: blink 1s step-end infinite;
    }

    @keyframes blink {
        from, to { color: transparent }
        50% { color: inherit }
    }
</style>

<style>
    .wpaicg-chat-shortcode {
        width: <?php echo esc_html($wpaicg_settings['width'])?>;
        background-color: <?php echo esc_html($wpaicg_settings['bgcolor'])?>;
        border-radius: <?php echo esc_html($wpaicg_settings['chat_rounded'])?>px;
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.15);
        display: flex;
        flex-direction: column;
        max-width: 100%;
        overflow: hidden;
        border: none;
        transition: box-shadow 0.3s ease;
        margin-right: 20px; /* Adjust as needed */
    }
    .wpaicg-chat-shortcode:hover {
        box-shadow: 0 12px 24px rgba(0, 0, 0, 0.2); /* Enhanced shadow on hover for interaction feedback */
    }
    .wpaicg-chat-shortcode-content {
        overflow-y: auto;
        flex-grow: 1;
        padding: 15px; /* Increased padding for more space around messages */
    }
    .wpaicg-chat-shortcode-content ul {
        overflow-y: auto;
        margin: 0;
        padding: 0;
        list-style: none;
    }
    .log_notification {
        background: <?php echo esc_html($wpaicg_settings['bgcolor'])?>;
        color: <?php echo esc_html($wpaicg_settings['fontcolor'])?>;
        font-size: 11px;
        font-style: italic;
        padding: 10px;
        border-radius: 5px;
    }

    .wpaicg-chat-shortcode-footer {
        color: <?php echo esc_html($wpaicg_settings['footer_font_color'])?>;
        background: <?php echo esc_html($wpaicg_footer_color)?>;
        font-size: 0.75rem;
        padding: 12px 20px;
        border-top: 1px solid <?php echo esc_html($wpaicg_footer_color)?>;
    }
    .wpaicg-chat-shortcode-footer a {
        color: inherit;
        text-decoration: none;
    }

    .wpaicg-chat-shortcode-content ul .wpaicg-user-message {
        margin-left: auto; /* This pushes the user messages to the right */
        background: <?php echo esc_html($wpaicg_user_bg_color)?>;
        color: <?php echo esc_html($wpaicg_font_color)?>;
        font-size: <?php echo esc_html($wpaicg_font_size)?>px;
        margin-bottom: 20px;
        margin-right: 10px;
        padding: 10px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        width: fit-content;
    }
    .wpaicg-chat-shortcode-content ul .wpaicg-ai-message {
        color: <?php echo esc_html($wpaicg_font_color)?>;
        font-size: <?php echo esc_html($wpaicg_font_size)?>px;
        margin-bottom: 20px;
        margin-right: 10px;
        padding: 10px;
        border-radius: 20px;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        width: fit-content;
    }

    .wpaicg-chat-shortcode .wpaicg-bot-thinking {
        bottom: 0;
        font-size: 11px;
        color: <?php echo esc_html($wpaicg_thinking_color)?>;
        display: none;
    }

    .wpaicg-chat-message-error {
        color: #f00;
    }

    .wpaicg-jumping-dots span {
        position: relative;
        bottom: 0;
        -webkit-animation: wpaicg-jump 1500ms infinite;
        animation: wpaicg-jump 2s infinite;
    }
    .wpaicg-jumping-dots .wpaicg-dot-1 {
        -webkit-animation-delay: 200ms;
        animation-delay: 200ms;
    }
    .wpaicg-jumping-dots .wpaicg-dot-2 {
        -webkit-animation-delay: 400ms;
        animation-delay: 400ms;
    }
    .wpaicg-jumping-dots .wpaicg-dot-3 {
        -webkit-animation-delay: 600ms;
        animation-delay: 600ms;
    }

    .wpaicg-chat-shortcode-type {
        display: flex;
        align-items: center;
        padding: 0;
        color: <?php echo esc_html($wpaicg_settings['send_color'])?>;
    }

    .wpaicg-chat-shortcode-send {
        color: <?php echo esc_html($wpaicg_settings['send_color'])?>;
    }

    textarea.wpaicg-chat-shortcode-typing {
        flex: 1;
        border: 1px solid <?php echo esc_html($wpaicg_settings['border_text_field'])?>;
        background-color: <?php echo esc_html($wpaicg_settings['bg_text_field'])?>;
        resize: vertical;
        border-radius: <?php echo esc_html($wpaicg_settings['text_rounded'])?>px;
        line-height: <?php echo $wpaicg_text_height - ($wpaicg_text_height * 0.1)?>px;
        padding-left: 1em;
        color: <?php echo esc_html($wpaicg_input_font_color)?>;
        font-size: <?php echo esc_html($wpaicg_font_size)?>px;
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

    textarea.wpaicg-chat-shortcode-typing::placeholder {
        color: <?php echo esc_html($wpaicg_input_font_color)?>;
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
        .wpaicg-chat-shortcode {
            /* Adjust the width and right margin for smaller screens */
            width: auto; /* This makes the chat window adapt to the screen size */
            margin-right: 10px; /* Smaller margin for smaller devices */
            margin-left: 10px; /* Add some space on the left as well */
        }
    }

    /* Further adjustments for very small screens, like iPhones */
    @media (max-width: 480px) {
        .wpaicg-chat-shortcode {
            /* You might want even smaller margins here */
            margin-right: 5px;
            margin-left: 5px;
        }
    }
    .wpaicg-chat-shortcode .wpaicg-mic-icon {
        color: <?php echo esc_html($wpaicg_mic_color)?>;
    }
    .wpaicg-chat-shortcode .wpaicg-img-icon {
        color: <?php echo esc_html($wpaicg_settings['send_color'])?>;
    }
    .wpaicg-chat-shortcode .wpaicg-pdf-icon {
        color: <?php echo esc_html($wpaicg_settings['pdf_color'])?>;
    }
    .wpaicg-chat-shortcode .wpaicg-pdf-remove {
        color: <?php echo esc_html($wpaicg_settings['pdf_color'])?>;
        font-size: 33px;
        justify-content: center;
        align-items: center;
        width: 16px;
        height: 16px;
        line-height: unset;
        font-family: Arial, serif;
        border-radius: 50%;
        font-weight: normal;
        padding: 0;
    }
    .wpaicg-chat-shortcode .wpaicg-pdf-loading {
        border-color: <?php echo esc_html($wpaicg_settings['pdf_color'])?>;
        border-bottom-color: transparent;
    }
    .wpaicg-chat-shortcode .wpaicg-mic-icon.wpaicg-recording {
        color: <?php echo esc_html($wpaicg_stop_color)?>;
    }
    .wpaicg_chat_additions {
        display: flex;
        justify-content: center;
        align-items: center;
        position: absolute;
        right: 20px;
    }

    .wpaicg-chat-shortcode .wpaicg-chatbox-action-bar {
        position: absolute;
        top: 0; /* Position it at the top of the chat window */
        right: 0;
        left: 0; /* Ensure it spans the full width */
        height: 40px;
        padding: 0 10px;
        display: none;
        justify-content: center;
        align-items: center;
        background: <?php echo esc_html($wpaicg_footer_color)?>;
        color: <?php echo esc_html($wpaicg_bar_color)?>;
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        transition: background-color 0.3s ease;
        min-height: 40px;
    }

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
        background-color: transparent !important;
        color: inherit !important;
    }
    /* Ensure no hover color change for links inside icon containers */
    .wpaicg-icon-container a:hover {
        color: inherit !important;
        background-color: transparent !important;
        text-decoration: none !important;
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

    /* Button Styles */
    .wpaicg-chatbox-download-btn,
    .wpaicg-chatbox-clear-btn,
    .wpaicg-chatbox-fullscreen,
    .wpaicg-chatbox-audio-btn,
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
    .wpaicg-chatbox-clear-btn svg,
    .wpaicg-chatbox-fullscreen svg,
    .wpaicg-chatbox-audio-btn,
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
    .wpaicg-chatbox-fullscreen svg.wpaicg-exit-fullscreen {
        display: none;
        fill: none;
        height: 16px;
        width: 16px;
    }
    /* Fullscreen Button SVG Paths */
    .wpaicg-chatbox-fullscreen svg.wpaicg-exit-fullscreen path,
    .wpaicg-chatbox-fullscreen svg.wpaicg-active-fullscreen path {
        fill: currentColor; /* Ensure visibility */
    }
    .wpaicg-chatbox-fullscreen svg.wpaicg-active-fullscreen {
        fill: none;
        height: 16px;
        width: 16px;
    }

    /* Adjusting visibility for fullscreen icons */
    .wpaicg-chatbox-fullscreen.wpaicg-fullscreen-box svg.wpaicg-active-fullscreen {
        display: none; /* Hide when in fullscreen */
    }
    .wpaicg-chatbox-fullscreen.wpaicg-fullscreen-box svg.wpaicg-exit-fullscreen {
        display: block; /* Show exit icon in fullscreen mode */
    }
    .wpaicg-fullscreened {
        border-radius: 0;
        border: none; /* Remove border in fullscreen */
    }
    .wpaicg-fullscreened .wpaicg-chatbox-action-bar {
        top: 0;
        z-index: 99;
        border-top-left-radius: 0;
        border-top-right-radius: 0;
        border-bottom-left-radius: 3px;
    }

    .wpaicg-chat-shortcode .wpaicg-chatbox-action-bar {
        position: relative;
        top: 0;
        display: flex;
        justify-content: flex-end;
    }
    .wpaicg-chat-shortcode h1, 
    .wpaicg-chat-shortcode h2, 
    .wpaicg-chat-shortcode h3, 
    .wpaicg-chat-shortcode h4, 
    .wpaicg-chat-shortcode h5, 
    .wpaicg-chat-shortcode h6 {
        color: <?php echo esc_html($wpaicg_settings['fontcolor'])?>;
    }

</style>
<style>
    /* Sidebar */
    .wpaicg-sidebar {
    background-color: <?php echo esc_html($wpaicg_settings['bgcolor'])?>;
    border-right: 1px solid <?php echo esc_html($wpaicg_border_text_field)?>;
    }

    /* Sidebar Header */
    .wpaicg-sidebar-header {
    border-bottom: 1px solid <?php echo esc_html($wpaicg_border_text_field)?>;
    color: <?php echo esc_html($wpaicg_font_color)?>;
    }

    .wpaicg-sidebar-header h3 {
    color: <?php echo esc_html($wpaicg_font_color)?>;
    }

    .wpaicg-sidebar-header p {
    color: <?php echo esc_html($wpaicg_font_color)?>;
    font-size: <?php echo esc_html($wpaicg_font_size)?>px;
    }

    /* Conversation List Items */
    .wpaicg-conversation-list li {
    background-color: <?php echo esc_html($wpaicg_user_bg_color)?>;
    color: <?php echo esc_html($wpaicg_font_color)?>;
    font-size: <?php echo esc_html($wpaicg_font_size)?>px;
    }
    .wpaicg-conversation-list li:hover {
    background-color: <?php echo esc_html($wpaicg_font_color); ?>;
    color: <?php echo esc_html($wpaicg_user_bg_color); ?>;
    transform: none;
    }
    /* Trash icon displayed on hover for deleting a conversation */
    .wpaicg-conversation-list li .wpaicg-delete-icon {
    color: <?php echo esc_html($wpaicg_user_bg_color); ?>;
    font-size: <?php echo esc_html($wpaicg_font_size)?>px;
    }

    /* Sidebar Toggle Button */
    .wpaicg-sidebar-toggle {
    background-color: <?php echo esc_html($wpaicg_ai_bg_color)?>;
    }

    .wpaicg-sidebar-toggle:hover {
    background-color: <?php echo esc_html($wpaicg_user_bg_color)?>;
    }

    .wpaicg-sidebar-toggle span {
    color: <?php echo esc_html($wpaicg_font_color)?>;
    }
</style>
<?php
$wpaicg_has_action_bar = false;
if($wpaicg_chat_fullscreen || $wpaicg_chat_download_btn || $wpaicg_chat_clear_btn || $wpaicg_chat_audio_btn) {
    $wpaicg_has_action_bar = true;
}
?>
<?php
// Check if the model name starts with 'asst_'
$assistantEnabled = false;
if (isset($wpaicg_settings['model']) && strpos($wpaicg_settings['model'], 'asst_') === 0) {
    $assistantEnabled = true;
}
?>
<div class="wpaicg-chat-shortcode"
     data-user-bg-color="<?php echo esc_html($wpaicg_user_bg_color)?>"
     data-color="<?php echo esc_html($wpaicg_font_color)?>"
     data-fontsize="<?php echo esc_html($wpaicg_font_size)?>"
     data-ai-bg-color="<?php echo esc_html($wpaicg_ai_bg_color)?>"
     data-nonce="<?php echo esc_html(wp_create_nonce( 'wpaicg-chatbox' ))?>"
     data-post-id="<?php echo get_the_ID()?>"
     data-url="<?php echo home_url( $wp->request )?>"
     data-bot-id="<?php echo esc_html($wpaicg_bot_id)?>"
     data-width="<?php echo esc_html($wpaicg_chat_widget_width)?>"
     data-height="<?php echo esc_html($wpaicg_chat_widget_height)?>"
     data-footer="<?php echo isset($wpaicg_settings['footer_text']) && !empty($wpaicg_settings['footer_text']) ? 'true' : 'false'?>"
     data-has-bar="<?php echo $wpaicg_has_action_bar ? 'true' : 'false'?>"
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
     data-sidebar-enabled="<?php echo esc_html($wpaicg_chat_sidebar)?>"
     data-type="shortcode"
>
<style>
.wpaicg-new-chat-button {
    background-color: <?php echo esc_html($wpaicg_ai_bg_color)?>;
    color: <?php echo esc_html($wpaicg_font_color)?>;
    font-size: <?php echo esc_html($wpaicg_font_size)?>px;
}
.wpaicg-new-chat-button:hover {
    background-color: <?php echo esc_html($wpaicg_user_bg_color)?>;
}
</style>
<?php

// Sidebar HTML structure with added "New Chat" button:
$sidebar_html = '
    <div class="wpaicg-sidebar">
        <div class="wpaicg-sidebar-header">
            <button type="button" class="wpaicg-new-chat-button" style="margin-top:10px;">' . esc_html($wpaicg_new_chat) . '</button>
        </div>
        <ul class="wpaicg-conversation-list">
            <!-- Conversations will be loaded here dynamically -->
        </ul>
    </div>
';

?>
<?php if($wpaicg_has_action_bar): ?>
    <div class="wpaicg-chatbox-action-bar">
        <?php
        if($wpaicg_chat_download_btn):
            ?>
            <span class="wpaicg-chatbox-download-btn" data-type="shortcode" role="button" aria-label="Download" tabindex="0">
                <svg role="presentation" version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512"  xml:space="preserve"><path class="st0" d="M243.591,309.362c3.272,4.317,7.678,6.692,12.409,6.692c4.73,0,9.136-2.376,12.409-6.689l89.594-118.094 c3.348-4.414,4.274-8.692,2.611-12.042c-1.666-3.35-5.631-5.198-11.168-5.198H315.14c-9.288,0-16.844-7.554-16.844-16.84V59.777 c0-11.04-8.983-20.027-20.024-20.027h-44.546c-11.04,0-20.022,8.987-20.022,20.027v97.415c0,9.286-7.556,16.84-16.844,16.84 h-34.305c-5.538,0-9.503,1.848-11.168,5.198c-1.665,3.35-0.738,7.628,2.609,12.046L243.591,309.362z"/><path class="st0" d="M445.218,294.16v111.304H66.782V294.16H0v152.648c0,14.03,11.413,25.443,25.441,25.443h461.118 c14.028,0,25.441-11.413,25.441-25.443V294.16H445.218z"/></svg>
            </span>
        <?php
        endif;
        
        if($wpaicg_chat_audio_btn): ?>
            <span class="wpaicg-chatbox-audio-btn" data-type="shortcode" role="button" aria-label="Toggle Audio" tabindex="0">
            <!-- Muted SVG by default -->
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 576 512"><path d="M301.1 34.8C312.6 40 320 51.4 320 64l0 384c0 12.6-7.4 24-18.9 29.2s-25 3.1-34.4-5.3L131.8 352 64 352c-35.3 0-64-28.7-64-64l0-64c0-35.3 28.7-64 64-64l67.8 0L266.7 40.1c9.4-8.4 22.9-10.4 34.4-5.3zM425 167l55 55 55-55c9.4-9.4 24.6-9.4 33.9 0s9.4 24.6 0 33.9l-55 55 55 55c9.4 9.4 9.4 24.6 0 33.9s-24.6 9.4-33.9 0l-55-55-55 55c-9.4 9.4-24.6 9.4-33.9 0s-9.4-24.6 0-33.9l55-55-55-55c-9.4-9.4-9.4-24.6 0-33.9s24.6-9.4 33.9 0z"/></svg>
            </span>
        <?php endif; ?>
        <?php
        if($wpaicg_chat_fullscreen):
            ?>
            <span data-type="shortcode" class="wpaicg-chatbox-fullscreen" role="button" aria-label="Fullscreen" tabindex="0">
                <svg role="presentation" class="wpaicg-active-fullscreen" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M10 15H15V10H13.2V13.2H10V15ZM6 15V13.2H2.8V10H1V15H6ZM10 2.8H12.375H13.2V6H15V1H10V2.8ZM6 1V2.8H2.8V6H1V1H6Z"/></svg>
                <svg role="presentation" class="wpaicg-exit-fullscreen" viewBox="0 0 16 16" xmlns="http://www.w3.org/2000/svg"><path d="M1 6L6 6L6 1L4.2 1L4.2 4.2L1 4.2L1 6Z"/><path d="M15 10L10 10L10 15L11.8 15L11.8 11.8L15 11.8L15 10Z"/><path d="M6 15L6 10L1 10L1 11.8L4.2 11.8L4.2 15L6 15Z"/><path d="M10 1L10 6L15 6L15 4.2L11.8 4.2L11.8 1L10 1Z"/></svg>
            </span>
        <?php
        endif;
        ?>
        <?php
        if($wpaicg_chat_clear_btn):
            ?>
            <span class="wpaicg-chatbox-clear-btn" data-type="shortcode" role="button" aria-label="Clear Chat" tabindex="0">
            <svg role="presentation" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 448 512"><path d="M170.5 51.6L151.5 80h145l-19-28.4c-1.5-2.2-4-3.6-6.7-3.6H177.1c-2.7 0-5.2 1.3-6.7 3.6zm147-26.6L354.2 80H368h48 8c13.3 0 24 10.7 24 24s-10.7 24-24 24h-8V432c0 44.2-35.8 80-80 80H112c-44.2 0-80-35.8-80-80V128H24c-13.3 0-24-10.7-24-24S10.7 80 24 80h8H80 93.8l36.7-55.1C140.9 9.4 158.4 0 177.1 0h93.7c18.7 0 36.2 9.4 46.6 24.9zM80 128V432c0 17.7 14.3 32 32 32H336c17.7 0 32-14.3 32-32V128H80zm80 64V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16zm80 0V400c0 8.8-7.2 16-16 16s-16-7.2-16-16V192c0-8.8 7.2-16 16-16s16 7.2 16 16z"/></svg>
    </span>
        <?php
        endif;
        ?>

    </div>
<?php endif; ?>
<!-- Begin Chat Content Wrapper -->
<div class="wpaicg-chat-content-wrapper">
    <!-- Insert the toggle and sidebar if $wpaicg_chat_sidebar is enabled -->
    <?php if($wpaicg_chat_sidebar): ?>
        <span class="wpaicg-sidebar-toggle" role="button" aria-label="Toggle Sidebar" tabindex="0">
            <span class="dashicons dashicons-menu"></span>
        </span>
        <?php echo $sidebar_html; ?>
    <?php endif; ?>
    <div class="wpaicg-chat-shortcode-content">
        <ul class="wpaicg-chat-shortcode-messages">
            <?php
            if($wpaicg_save_logs && $wpaicg_log_notice && !empty($wpaicg_log_notice_message)):
                ?>
                <li class="log_notification">
                    <p>
                    <span class="wpaicg-chat-message">
                        <?php echo esc_html(str_replace("\\",'',$wpaicg_log_notice_message))?>
                    </span>
                    </p>
                </li>
            <?php
            endif;
            ?>
            <li class="wpaicg-ai-message" style="color: <?php echo esc_html($wpaicg_font_color)?>; font-size: <?php echo esc_html($wpaicg_font_size)?>px; background-color: <?php echo esc_html($wpaicg_ai_bg_color);?>">
                <span class="wpaicg-chat-message">
                    <?php echo esc_html(str_replace("\\",'',$wpaicg_welcome_message))?>
                </span>
            </li>
        </ul>
        <!-- Conversation Starters -->
        <?php if (!empty($wpaicg_conversation_starters)): ?>
            <div class="wpaicg-conversation-starters">
                <?php foreach ($wpaicg_conversation_starters as $starter): ?>
                    <button type="button" class="wpaicg-conversation-starter">
                        <?php echo esc_html($starter['text']); ?>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <span class="wpaicg-bot-thinking" style="padding-left: 0;color: <?php echo esc_html($wpaicg_thinking_color)?>;"><?php echo esc_html(str_replace("\\",'',$wpaicg_ai_thinking))?>&nbsp;<span class="wpaicg-jumping-dots"><span class="wpaicg-dot-1">.</span><span class="wpaicg-dot-2">.</span><span class="wpaicg-dot-3">.</span></span></span>
        <div class="wpaicg-chat-shortcode-type">
            <textarea type="text" name="wpaicg-chat-shortcode-typing" class="auto-expand wpaicg-chat-shortcode-typing" placeholder="<?php echo esc_html(str_replace("\\",'',$wpaicg_typing_placeholder))?>"></textarea>
            <div class="wpaicg_chat_additions">
                <span class="wpaicg-thumbnail-placeholder"></span>
                <span class="wpaicg-mic-icon" data-type="shortcode" role="button" aria-label="Mic" tabindex="0" style="<?php echo $wpaicg_audio_enable ? '' : 'display:none'?>">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 384 512"><path d="M176 0C123 0 80 43 80 96V256c0 53 43 96 96 96s96-43 96-96V96c0-53-43-96-96-96zM48 216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 89.1 66.2 162.7 152 174.4V464H104c-13.3 0-24 10.7-24 24s10.7 24 24 24h72 72c13.3 0 24-10.7 24-24s-10.7-24-24-24H200V430.4c85.8-11.7 152-85.3 152-174.4V216c0-13.3-10.7-24-24-24s-24 10.7-24 24v40c0 70.7-57.3 128-128 128s-128-57.3-128-128V216z"/></svg>
                </span>
                <span class="wpaicg-img-icon" data-type="shortcode" style="<?php echo $wpaicg_image_enable ? '' : 'display:none'?>">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-image"><rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><polyline points="21 15 16 10 5 21"></polyline></svg>
                    <input type="file" id="imageUpload" class="wpaicg-img-file" accept="image/png, image/jpeg, image/webp, image/gif" style="display: none;" />
                    <!-- add nonce -->
                    <input type="hidden" id="wpaicg-img-nonce" value="<?php echo esc_html(wp_create_nonce( 'wpaicg-img-nonce' ))?>" />
                </span>
                <span class="wpaicg-img-spinner"></span>
                <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()):?>
                <span class="wpaicg-pdf-icon" data-type="shortcode" style="<?php echo $wpaicg_pdf_enable ? '' : 'display:none'?>">
                    <svg version="1.1" id="_x32_" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" viewBox="0 0 512 512"  xml:space="preserve"><path class="st0" d="M378.413,0H208.297h-13.182L185.8,9.314L57.02,138.102l-9.314,9.314v13.176v265.514 c0,47.36,38.528,85.895,85.896,85.895h244.811c47.353,0,85.881-38.535,85.881-85.895V85.896C464.294,38.528,425.766,0,378.413,0z M432.497,426.105c0,29.877-24.214,54.091-54.084,54.091H133.602c-29.884,0-54.098-24.214-54.098-54.091V160.591h83.716 c24.885,0,45.077-20.178,45.077-45.07V31.804h170.116c29.87,0,54.084,24.214,54.084,54.092V426.105z"/><path class="st0" d="M171.947,252.785h-28.529c-5.432,0-8.686,3.533-8.686,8.825v73.754c0,6.388,4.204,10.599,10.041,10.599 c5.711,0,9.914-4.21,9.914-10.599v-22.406c0-0.545,0.279-0.817,0.824-0.817h16.436c20.095,0,32.188-12.226,32.188-29.612 C204.136,264.871,192.182,252.785,171.947,252.785z M170.719,294.888h-15.208c-0.545,0-0.824-0.272-0.824-0.81v-23.23 c0-0.545,0.279-0.816,0.824-0.816h15.208c8.42,0,13.447,5.027,13.447,12.498C184.167,290,179.139,294.888,170.719,294.888z"/><path class="st0" d="M250.191,252.785h-21.868c-5.432,0-8.686,3.533-8.686,8.825v74.843c0,5.3,3.253,8.693,8.686,8.693h21.868 c19.69,0,31.923-6.249,36.81-21.324c1.76-5.3,2.723-11.681,2.723-24.857c0-13.175-0.964-19.557-2.723-24.856 C282.113,259.034,269.881,252.785,250.191,252.785z M267.856,316.896c-2.318,7.331-8.965,10.459-18.21,10.459h-9.23 c-0.545,0-0.824-0.272-0.824-0.816v-55.146c0-0.545,0.279-0.817,0.824-0.817h9.23c9.245,0,15.892,3.128,18.21,10.46 c0.95,3.128,1.62,8.56,1.62,17.93C269.476,308.336,268.805,313.768,267.856,316.896z"/><path class="st0" d="M361.167,252.785h-44.812c-5.432,0-8.7,3.533-8.7,8.825v73.754c0,6.388,4.218,10.599,10.055,10.599 c5.697,0,9.914-4.21,9.914-10.599v-26.351c0-0.538,0.265-0.81,0.81-0.81h26.086c5.837,0,9.23-3.532,9.23-8.56 c0-5.028-3.393-8.553-9.23-8.553h-26.086c-0.545,0-0.81-0.272-0.81-0.817v-19.425c0-0.545,0.265-0.816,0.81-0.816h32.733 c5.572,0,9.245-3.666,9.245-8.553C370.411,256.45,366.738,252.785,361.167,252.785z"/></svg>
                </span>
                    <span class="wpaicg-pdf-loading" style="display: none"></span>
                    <span data-type="shortcode" alt="<?php echo esc_html__('Clear','gpt3-ai-content-generator')?>" title="<?php echo esc_html__('Clear','gpt3-ai-content-generator')?>" class="wpaicg-pdf-remove" style="display: none">&times;</span>
                    <input data-type="shortcode" data-limit="<?php echo esc_html($wpaicg_settings['pdf_pages'])?>" type="file" accept="application/pdf" class="wpaicg-pdf-file" style="display: none">
                <?php endif; ?>
                <?php if(isset($wpaicg_settings['send_button_enabled']) && $wpaicg_settings['send_button_enabled']): ?>
                <span class="wpaicg-chat-shortcode-send" role="button" aria-label="Send" tabindex="0">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-send"><line x1="22" y1="2" x2="11" y2="13"></line><polygon points="22 2 15 22 11 13 2 9 22 2"></polygon></svg>
                </span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php if($wpaicg_include_footer): ?>
    <div class="wpaicg-chat-shortcode-footer">
        <?php echo wp_kses_post(str_replace("\\",'',$wpaicg_settings['footer_text'])); ?>
    </div>
<?php endif; ?>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const textareas = document.querySelectorAll('.auto-expand');
        textareas.forEach(textarea => {
            function adjustHeight() {
                    // Store the current scroll position to restore it after resizing
                    var scrollLeft = window.pageXOffset || (document.documentElement || document.body.parentNode || document.body).scrollLeft;
                    var scrollTop  = window.pageYOffset || (document.documentElement || document.body.parentNode || document.body).scrollTop;

                    // Add the resizing class to trigger the pulsing effect
                    textarea.classList.add('resizing');

                    // Temporarily shrink the textarea to potentially reduce its size
                    textarea.style.height = 'auto';
                    // Adjust the height based on the current content, allowing it to grow or shrink
                    textarea.style.height = textarea.scrollHeight + 'px';

                    // Restore the scroll position after resizing the textarea
                    window.scrollTo(scrollLeft, scrollTop);

                    // Remove the resizing class after a delay to show the pulsing effect
                    setTimeout(() => {
                        textarea.classList.remove('resizing');
                    }, 500); // Adjust this time to match your CSS animation duration
                }

            // Bind the adjustHeight function to the input event
            textarea.addEventListener('input', adjustHeight);

            // Perform an initial adjustment in case the textarea is pre-populated
            if (textarea.value.length) {
                adjustHeight();
            }
        });
    });
</script>