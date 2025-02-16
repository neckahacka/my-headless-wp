<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( '\\WPAICG\\WPAICG_Dashboard' ) ) {
    class WPAICG_Dashboard
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_aipower_save_ai_engine', array($this, 'aipower_save_ai_engine'));
            add_action('wp_ajax_aipower_save_api_key', array($this, 'aipower_save_api_key'));
            add_action('wp_ajax_aipower_save_openai_model', array($this, 'aipower_save_openai_model'));
            add_action('wp_ajax_aipower_save_openrouter_model', array($this, 'aipower_save_openrouter_model'));
            add_action('wp_ajax_aipower_save_google_model', array($this, 'aipower_save_google_model'));
            add_action('wp_ajax_aipower_save_azure_field', array($this, 'aipower_save_azure_field'));
            add_action('wp_ajax_aipower_save_advanced_setting', array($this, 'aipower_save_advanced_setting'));
            add_action('wp_ajax_aipower_save_google_safety_settings', array($this, 'aipower_save_google_safety_settings'));
            add_action('wp_ajax_aipower_save_content_settings', array($this, 'aipower_save_content_settings'));
            add_action('wp_ajax_aipower_refresh_chatbot_table', array($this, 'aipower_refresh_chatbot_table'));
            add_action('wp_ajax_aipower_load_chatbot', array($this, 'aipower_load_chatbot'));
            add_action('wp_ajax_aipower_delete_chatbot', array($this, 'aipower_delete_chatbot'));
            add_action('wp_ajax_aipower_save_field', array($this, 'aipower_save_field'));
            add_action('wp_ajax_aipower_get_bot_data', array($this, 'aipower_get_bot_data'));
            add_action('wp_ajax_aipower_delete_all_bots', array($this, 'aipower_delete_all_bots'));
            add_action('wp_ajax_aipower_update_module_settings', array($this, 'aipower_update_module_settings'));
            add_action('wp_ajax_aipower_get_attachment_url', array($this, 'aipower_get_attachment_url'));
            add_action('wp_ajax_aipower_toggle_default_widget_status', array($this, 'aipower_toggle_default_widget_status'));
            add_action('wp_ajax_aipower_duplicate_chatbot', array($this, 'aipower_duplicate_chatbot'));
            add_action('wp_ajax_aipower_export_bots', array($this, 'aipower_export_bots'));
            add_action('wp_ajax_aipower_import_bots', array($this, 'aipower_import_bots'));
            add_action('wp_ajax_aipower_reset_settings', array($this, 'aipower_reset_settings'));
            // aipower_save_replicate_field
            add_action('wp_ajax_aipower_save_replicate_field', array($this, 'aipower_save_replicate_field'));
        }

        /**
         * Handle AJAX Request to Import Bots from JSON
         */
        public function aipower_import_bots() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => __('Nonce verification failed.', 'gpt3-ai-content-generator')));
                return;
            }

            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            // Check if a file is uploaded
            if (!isset($_FILES['import_file']) || $_FILES['import_file']['error'] !== UPLOAD_ERR_OK) {
                wp_send_json_error(array('message' => __('Failed to upload file. Please try again.', 'gpt3-ai-content-generator')));
                return;
            }

            $file = $_FILES['import_file'];

            // Validate file extension json
            $file_info = pathinfo($file['name']);
            if (!isset($file_info['extension']) || strtolower($file_info['extension']) !== 'json') {
                wp_send_json_error(array('message' => __('Invalid file format. Please upload a JSON file.', 'gpt3-ai-content-generator')));
                return;
            }

            // Optional: Enforce a maximum file size (e.g., 2MB)
            $max_file_size = 2 * 1024 * 1024; // 2MB in bytes
            if ($file['size'] > $max_file_size) {
                wp_send_json_error(array('message' => __('File size exceeds the maximum limit of 2MB.', 'gpt3-ai-content-generator')));
                return;
            }

            // Read file contents
            $json_content = file_get_contents($file['tmp_name']);
            if ($json_content === false) {
                wp_send_json_error(array('message' => __('Failed to read the uploaded file.', 'gpt3-ai-content-generator')));
                return;
            }

            // Decode JSON
            $data = json_decode($json_content, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => __('Invalid JSON format: ' . json_last_error_msg(), 'gpt3-ai-content-generator')));
                return;
            }

            // Handle if data is a single object, wrap it in an array
            if (isset($data['Name']) && isset($data['Content'])) {
                $data = array($data);
            }

            // Ensure data is an array
            if (!is_array($data)) {
                wp_send_json_error(array('message' => __('Invalid data structure in JSON file. Expected an array of chatbots.', 'gpt3-ai-content-generator')));
                return;
            }

            // Initialize counters
            $imported = 0;
            $skipped = 0;
            $errors = array();

            // Loop through each chatbot data
            foreach ($data as $index => $chatbot) {
                // Validate required fields
                if (!isset($chatbot['Name']) || !isset($chatbot['Content'])) {
                    $skipped++;
                    $errors[] = sprintf(__('Chatbot at index %d is missing required fields.', 'gpt3-ai-content-generator'), $index);
                    continue; // Skip invalid entries
                }

                // Validate that 'Content' is an array
                if (!is_array($chatbot['Content'])) {
                    $skipped++;
                    $errors[] = sprintf(__('Chatbot "%s" has invalid "Content" format.', 'gpt3-ai-content-generator'), $chatbot['Name']);
                    continue; // Skip invalid entries
                }

                // Check if a chatbot with the same name already exists (optional)
                $existing_bot = get_page_by_title($chatbot['Name'], OBJECT, 'wpaicg_chatbot');
                if ($existing_bot) {
                    $skipped++;
                    $errors[] = sprintf(__('Chatbot "%s" already exists and was skipped.', 'gpt3-ai-content-generator'), $chatbot['Name']);
                    continue; // Skip duplicates
                }

                // Prepare post data
                $post_data = array(
                    'post_title'   => sanitize_text_field($chatbot['Name']),
                    'post_content' => wp_slash(json_encode($chatbot['Content'], JSON_UNESCAPED_UNICODE)), // Store content as JSON
                    'post_status'  => 'publish',
                    'post_type'    => 'wpaicg_chatbot',
                );

                // Insert the post
                $post_id = wp_insert_post($post_data, true);

                if (is_wp_error($post_id)) {
                    $skipped++;
                    $errors[] = sprintf(__('Failed to import chatbot: %s', 'gpt3-ai-content-generator'), $chatbot['Name']);
                    continue;
                }

                // Optionally, add post meta if needed
                // Example: update_post_meta($post_id, '_aipower_chatbot_data', $chatbot['Content']);

                $imported++;
            }

            // Prepare response message
            $message = sprintf(__('Imported %d chatbot(s).', 'gpt3-ai-content-generator'), $imported);
            if ($skipped > 0) {
                $message .= ' ' . sprintf(__('Skipped %d chatbot(s) due to duplicates or invalid data.', 'gpt3-ai-content-generator'), $skipped);
            }
            if (!empty($errors)) {
                $message .= ' ' . implode(' ', $errors);
            }

            wp_send_json_success(array('message' => $message));
        }
        
        /**
         * Handle AJAX Request to Export Bots to JSON
         */
        public function aipower_export_bots() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => __('Nonce verification failed.', 'gpt3-ai-content-generator')));
                return;
            }

            // Check user capabilities
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            // Determine export type
            $export_type = isset($_POST['export_type']) ? sanitize_text_field($_POST['export_type']) : '';

            // Initialize data array
            $data = array();

            if ($export_type === 'all') {
                // Export All Bots
                $bots_args = array(
                    'post_type'      => 'wpaicg_chatbot',
                    'posts_per_page' => -1,
                    'post_status'    => 'publish',
                );
                $bots_query = new \WP_Query($bots_args);

                if ($bots_query->have_posts()) {
                    while ($bots_query->have_posts()) {
                        $bots_query->the_post();
                        $bot_id = get_the_ID();
                        $bot_title = get_the_title();
                        $bot_content = json_decode(get_the_content(), true);

                        // Ensure 'Name' and 'Content' fields are present
                        if (empty($bot_title) || !is_array($bot_content)) {
                            continue; // Skip bots missing required fields
                        }

                        $data[] = array(
                            'ID'       => $bot_id,
                            'Name'     => $bot_title,
                            'Content'  => $bot_content,
                            'Date'     => get_the_date('c'), // ISO 8601 format
                            'Modified' => get_the_modified_date('c'), // ISO 8601 format
                        );
                    }
                    wp_reset_postdata();
                } else {
                    wp_send_json_error(array('message' => __('No chatbots found to export.', 'gpt3-ai-content-generator')));
                    return;
                }
            } elseif ($export_type === 'single') {
                // Export Single Bot
                $bot_id = isset($_POST['bot_id']) ? intval($_POST['bot_id']) : 0;
                if ($bot_id <= 0) {
                    wp_send_json_error(array('message' => __('Invalid chatbot ID.', 'gpt3-ai-content-generator')));
                    return;
                }

                $bot = get_post($bot_id);
                if (!$bot || $bot->post_type !== 'wpaicg_chatbot') {
                    wp_send_json_error(array('message' => __('Chatbot not found.', 'gpt3-ai-content-generator')));
                    return;
                }

                $bot_title = $bot->post_title;
                $bot_content = json_decode($bot->post_content, true);

                // Ensure 'Name' and 'Content' fields are present
                if (empty($bot_title) || !is_array($bot_content)) {
                    wp_send_json_error(array('message' => __('Chatbot data is incomplete.', 'gpt3-ai-content-generator')));
                    return;
                }

                $data[] = array(
                    'ID'       => $bot_id,
                    'Name'     => $bot_title,
                    'Content'  => $bot_content,
                    'Date'     => get_the_date('c', $bot_id), // ISO 8601 format
                    'Modified' => get_the_modified_date('c', $bot_id), // ISO 8601 format
                );
            } else {
                wp_send_json_error(array('message' => __('Invalid export type.', 'gpt3-ai-content-generator')));
                return;
            }

            // Encode data to JSON
            $json_data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            if ($json_data === false) {
                wp_send_json_error(array('message' => __('Failed to encode data to JSON.', 'gpt3-ai-content-generator')));
                return;
            }

            // Check uploads directory
            $upload_dir = wp_upload_dir();
            if (!is_writable($upload_dir['basedir'])) {
                wp_send_json_error(array('message' => __('The uploads folder is not writable. Please check folder permissions.', 'gpt3-ai-content-generator')));
                return;
            }

            // Generate filename
            if ($export_type === 'all') {
                $filename = 'aipower_all_chatbots_' . current_time('Ymd_His') . '.json';
            } else { // single
                $bot_slug = sanitize_title($bot->post_title);
                $filename = 'aipower_chatbot_' . $bot_slug . '_' . current_time('Ymd_His') . '.json';
            }

            $file_path = trailingslashit($upload_dir['basedir']) . $filename;

            // Save JSON data to file
            $file_saved = file_put_contents($file_path, $json_data);
            if ($file_saved === false) {
                wp_send_json_error(array('message' => __('Failed to create JSON file.', 'gpt3-ai-content-generator')));
                return;
            }

            // Generate file URL
            $file_url = trailingslashit($upload_dir['baseurl']) . $filename;

            wp_send_json_success(array(
                'file_url' => esc_url($file_url),
                'filename' => esc_html($filename),
            ));
        }


        public function aipower_toggle_default_widget_status() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                return;
            }
            
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Get the new status from POST data
            $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : '';
        
            // Retrieve the current widget data
            $widget_data = get_option('wpaicg_chat_widget', []);
        
            // Update the status
            if ($new_status === 'active') {
                $widget_data['status'] = 'active';
            } else {
                unset($widget_data['status']);
            }
        
            // Update the option in the database
            $updated = update_option('wpaicg_chat_widget', $widget_data);
        
            if ($updated) {
                // Set the message based on the new status
                if ($new_status === 'active') {
                    $message = esc_html__('Your widget is activated site-wide.', 'gpt3-ai-content-generator');
                } else {
                    $message = esc_html__('Your widget is deactivated.', 'gpt3-ai-content-generator');
                }
                wp_send_json_success(['message' => $message]);
            } else {
                wp_send_json_error(['message' => esc_html__('Failed to update widget status.', 'gpt3-ai-content-generator')]);
            }
        }        

        public function aipower_get_bot_data() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Sanitize and retrieve bot_id
            $bot_id = isset($_POST['bot_id']) ? intval($_POST['bot_id']) : 0;
        
            // Handle Default Shortcode Bot
            if ($bot_id === 0) {
                $option_key = 'wpaicg_chat_shortcode_options';
                $bot_data = get_option($option_key, array());
        
                if (empty($bot_data)) {
                    wp_send_json_error(array('message' => esc_html__('Shortcode bot data not found.', 'gpt3-ai-content-generator')));
                    return;
                }

                // Retrieve 'openai_stream_nav' from its own option table and merge into bot_data
                $stream_option_key = 'wpaicg_shortcode_stream';
                $openai_stream_nav = get_option($stream_option_key, '0'); // Default to '0' if not set
                $bot_data['openai_stream_nav'] = $openai_stream_nav;
                
                // Retrieve conversation starters
                $bot_data['conversation_starters'] = $this->get_conversation_starters($bot_id);


                // **NEW CODE: Retrieve model from 'wpaicg_shortcode_google_model' if provider is 'Google'**
                if (isset($bot_data['provider']) && $bot_data['provider'] === 'Google') {
                    $google_model_option = 'wpaicg_shortcode_google_model';
                    $google_model = get_option($google_model_option, '');
                    $bot_data['model'] = $google_model;
                }

                // change the field name profession to proffesion
                if (isset($bot_data['profession'])) {
                    $bot_data['proffesion'] = $bot_data['profession'];
                    unset($bot_data['profession']);
                }

                // set the default value for type field to 'shortcode'
                $bot_data['type'] = 'shortcode'; 

                // **NEW CODE: Combine 'voice_language' and 'voice_name' for default bot_id=0**
                if (isset($bot_data['voice_language']) && isset($bot_data['voice_name'])) {
                    $combined_voice = sanitize_text_field($bot_data['voice_language']) . '|' . sanitize_text_field($bot_data['voice_name']);
                    $bot_data['voice_language'] = $combined_voice;
                }

                wp_send_json_success(array('bot_data' => $bot_data, 'type' => 'shortcode'));
                return;
            }
        
            // Handle Default Widget Bot
            if ($bot_id === -1) {
                $option_key = 'wpaicg_chat_widget';
                $bot_data = get_option($option_key, array());

                // Retrieve conversation starters
                $bot_data['conversation_starters'] = $this->get_conversation_starters($bot_id);

                // set the default value for type field to 'widget'
                $bot_data['type'] = 'widget';

                
                // get the model from wpaicg_chat_model and add it to bot_data
                if ($bot_data['provider'] === 'OpenAI') {
                    $bot_data['model'] = get_option('wpaicg_chat_model', 'gpt-4o-mini');
                } elseif ($bot_data['provider'] === 'Google') {
                    $bot_data['model'] = get_option('wpaicg_widget_google_model');
                } elseif ($bot_data['provider'] === 'OpenRouter') {
                    $bot_data['model'] = get_option('wpaicg_widget_openrouter_model');
                } elseif ($bot_data['provider'] === 'Azure') {
                    $bot_data['model'] = get_option('wpaicg_azure_deployment');
                }
                
                // Define an array of options with their corresponding bot_data keys and default values
                $options = [
                    'chat_addition' => ['option_name' => 'wpaicg_chat_addition', 'default' => '0'],
                    'chat_addition_text' => ['option_name' => 'wpaicg_chat_addition_text', 'default' => ''],
                    'openai_stream_nav' => ['option_name' => 'wpaicg_widget_stream', 'default' => '0'],
                    'welcome' => ['option_name' => '_wpaicg_chatbox_welcome_message', 'default' => 'Hello 👋, how can I help you today?'],
                    'newchat' => ['option_name' => '_wpaicg_chatbox_newchat', 'default' => 'New Chat'],
                    'temperature' => ['option_name' => 'wpaicg_chat_temperature', 'default' => '1'],
                    'max_tokens' => ['option_name' => 'wpaicg_chat_max_tokens', 'default' => '1500'],
                    'presence_penalty' => ['option_name' => 'wpaicg_chat_presence_penalty', 'default' => '0'],
                    'language' => ['option_name' => 'wpaicg_chat_language', 'default' => 'en'],
                    'vectordb' => ['option_name' => 'wpaicg_chat_vectordb', 'default' => 'pinecone'],
                    'embedding' => ['option_name' => 'wpaicg_chat_embedding', 'default' => '0'],
                    'embedding_type' => ['option_name' => 'wpaicg_chat_embedding_type', 'default' => 'openai'],
                    'embedding_top' => ['option_name' => 'wpaicg_chat_embedding_top', 'default' => '1'],
                    'qdrant_collection' => ['option_name' => 'wpaicg_widget_qdrant_collection', 'default' => ''],
                    'conversation_cut' => ['option_name' => 'wpaicg_conversation_cut', 'default' => '100'],
                    'ai_thinking' => ['option_name' => '_wpaicg_ai_thinking', 'default' => 'Gathering thoughts...'],
                    'placeholder' => ['option_name' => '_wpaicg_typing_placeholder', 'default' => 'Type your message here...'],
                    'top_p' => ['option_name' => 'wpaicg_chat_top_p', 'default' => '0'],
                    // Add more fields here as needed
                ];

                // Loop through the options array to populate $bot_data
                foreach ($options as $key => $option) {
                    $bot_data[$key] = get_option($option['option_name'], $option['default']);
                }

                // **NEW CODE: Combine 'voice_language' and 'voice_name' for default bot_id=-1**
                if (isset($bot_data['voice_language']) && isset($bot_data['voice_name'])) {
                    $combined_voice = sanitize_text_field($bot_data['voice_language']) . '|' . sanitize_text_field($bot_data['voice_name']);
                    $bot_data['voice_language'] = $combined_voice;
                }

        
                if (empty($bot_data)) {
                    wp_send_json_error(array('message' => esc_html__('Widget bot data not found.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                wp_send_json_success(array('bot_data' => $bot_data, 'type' => 'widget'));
                return;
            }
        
            // Handle Custom Bots Stored as Posts
            if ($bot_id > 0) {
                // Retrieve the bot post
                $post = get_post($bot_id);
                if (!$post || $post->post_type !== 'wpaicg_chatbot') {
                    wp_send_json_error(array('message' => esc_html__('Bot not found.', 'gpt3-ai-content-generator')));
                    return;
                }

                // Decode the JSON stored in post_content
                $post_content = $post->post_content;
                // Try decoding the post_content as is
                $bot_data = json_decode($post_content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    // Try applying wp_unslash and decode again
                    $post_content_unslashed = wp_unslash($post_content);
                    $bot_data = json_decode($post_content_unslashed, true);
        
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        // Try applying stripslashes and decode again
                        $post_content_stripped = stripslashes($post_content);
                        $bot_data = json_decode($post_content_stripped, true);
        
                        if (json_last_error() !== JSON_ERROR_NONE) {
                            wp_send_json_error(array('message' => esc_html__('Invalid bot data format.', 'gpt3-ai-content-generator')));
                            return;
                        } else {
                            // Successfully decoded after applying stripslashes
                            // Optionally, re-save the corrected data
                            wp_update_post(array(
                                'ID' => $bot_id,
                                'post_content' => wp_slash(wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                            ));
                        }
                    } else {
                        // Successfully decoded after applying wp_unslash
                        // Optionally, re-save the corrected data
                        wp_update_post(array(
                            'ID' => $bot_id,
                            'post_content' => wp_slash(wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                        ));
                    }
                } else {
                    // Successfully decoded without modifications
                    // No action needed
                }

                // Check if provider is Google and set fields to 0
                if (isset($bot_data['provider']) && $bot_data['provider'] === 'Google') {
                    $bot_data['openai_stream_nav'] = '0';
                    $bot_data['image_enable'] = '0';
                }

                // if these fields not exist, set them to 0
                $fields = ['openai_stream_nav', 'feedback_btn', 'save_logs','chat_addition','fullscreen','download_btn','clear_btn','sidebar','copy_btn','close_btn','moderation'];

                foreach ($fields as $field) {
                    if (!isset($bot_data[$field])) {
                        $bot_data[$field] = '0';
                    }
                }
                
                // Ensure 'icon_url' is present and valid if 'icon' is 'custom'
                if (isset($bot_data['icon']) && $bot_data['icon'] === 'custom') {
                    if (empty($bot_data['icon_url'])) {
                        // Set to empty or handle accordingly
                        $bot_data['icon_url'] = '';
                    } else {
                        // Ensure it's a valid attachment ID and is an image
                        if (!wp_attachment_is_image($bot_data['icon_url'])) {
                            $bot_data['icon_url'] = '';
                        }
                    }
                } else {
                    // If 'icon' is not 'custom', ensure 'icon_url' is empty
                    $bot_data['icon_url'] = '';
                }

                // Combine 'voice_language' and 'voice_name' into 'voice_language'**
                if (isset($bot_data['voice_language']) && isset($bot_data['voice_name'])) {
                    // Combine the two fields with a '|'
                    $combined_voice = sanitize_text_field($bot_data['voice_language']) . '|' . sanitize_text_field($bot_data['voice_name']);
                    
                    // Assign the combined value back to 'voice_language'
                    $bot_data['voice_language'] = $combined_voice;
                }

                // Ensure 'limited_roles' is properly set
                if (!isset($bot_data['limited_roles'])) {
                    $bot_data['limited_roles'] = array();
                }

                // Return the bot data
                wp_send_json_success(array('bot_data' => $bot_data, 'type' => 'custom'));
                return;
            }
        
            // If bot_id does not match any known types
            wp_send_json_error(array('message' => esc_html__('Invalid Bot ID.', 'gpt3-ai-content-generator')));
        }

        public function aipower_save_field() {

            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Sanitize and validate inputs
            $field = isset($_POST['field']) ? sanitize_text_field($_POST['field']) : '';
            $value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';
            $bot_id = isset($_POST['bot_id']) ? intval($_POST['bot_id']) : 0;
        
            if (empty($field)) {
                wp_send_json_error(array('message' => esc_html__('Field name is missing.', 'gpt3-ai-content-generator')));
                return;
            }

            $color_field_template = array(
                'required' => false,
                'sanitize_callback' => 'sanitize_text_field',
                'type' => 'post_content' // Stored in post_content JSON
            );
        
            // Define field definitions with validation and storage type
            $field_definitions = array(
                'name' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_bot_name'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'is_creation' => true, // Indicates bot creation
                    'type' => 'post_content' // Stored in post_content JSON or handled specially for default bots
                ),
                'provider' => array(
                    'required' => true,
                    'validate_callback' => array($this, 'validate_provider'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON or handled specially for default bots
                ),
                'model' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON or handled specially for default bots
                ),
                // Example of a field stored in options
                'some_option_field' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_some_option_field'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'option' // Stored in options table
                ),
                'chat_addition' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'chat_addition_text' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'openai_stream_nav' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'type' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_bot_type'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'pages' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_pages'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'position' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_position'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'image_enable' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'internet_browsing' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'max_tokens' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_max_tokens'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'temperature' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'frequency_penalty' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'presence_penalty' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'top_p' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'remember_conversation' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_yes_no_field'),
                    'sanitize_callback' => array($this, 'sanitize_yes_no_field'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'conversation_cut' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'content_aware' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_yes_no_field'),
                    'sanitize_callback' => array($this, 'sanitize_yes_no_field'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'user_aware' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_yes_no_field'),
                    'sanitize_callback' => array($this, 'sanitize_yes_no_field'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'vectordb' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_vectordb'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding_index' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding_top' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding_type' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_embedding_type'),
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'qdrant_collection' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'confidence_score' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'use_default_embedding' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding_model' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'feedback_btn' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'feedback_title' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'feedback_message' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'feedback_success' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding_pdf' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'embedding_pdf_message' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'pdf_pages' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'conversation_starters' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'fontsize' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'chat_rounded' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'text_rounded' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'width' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'height' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'text_height' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'fullscreen' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'download_btn' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'clear_btn' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'sidebar' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'copy_btn' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'close_btn' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'welcome' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'newchat' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'ai_thinking' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'placeholder' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'delay_time' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'footer_text' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_footer_text'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'icon' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field', // Sanitization function
                    'type' => 'post_content' // Stored in post_content JSON
                ),
        
                'icon_url' => array(
                    'required' => false,
                    'validate_callback' => array($this, 'validate_icon_url'),
                    'sanitize_callback' => 'sanitize_text_field', // Sanitization function
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'save_logs' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'log_request' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'log_notice' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'log_notice_message' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'moderation' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'moderation_model' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'moderation_notice' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_textarea_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'audio_enable' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'chat_to_speech' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'audio_btn' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'muted_by_default' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_checkbox'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'voice_service' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'openai_model' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'openai_voice' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'openai_output_format' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'openai_voice_speed' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'elevenlabs_model' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'elevenlabs_voice' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'voice_language' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'voice_name' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                "voice_device" => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'voice_speed' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'voice_pitch' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'user_limited' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'user_tokens' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'role_limited' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'limited_roles' => array(
                    'required' => false,
                    'sanitize_callback' => array($this, 'sanitize_limited_roles'),
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'guest_limited' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'guest_tokens' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'reset_limit' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'limited_message' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'language' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'tone' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'proffesion' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'lead_collection' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'lead_title' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'enable_lead_name' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'lead_name' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'enable_lead_email' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'lead_email' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'enable_lead_phone' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_checkbox',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'lead_phone' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'type' => 'post_content' // Stored in post_content JSON
                ),
                'bgcolor' => $color_field_template,
                'fontcolor' => $color_field_template,
                'pdf_color' => $color_field_template,
                'ai_bg_color' => $color_field_template,
                'user_bg_color' => $color_field_template,
                'bg_text_field' => $color_field_template,
                'input_font_color' => $color_field_template,
                'border_text_field' => $color_field_template,
                'send_color' => $color_field_template,
                'footer_color' => $color_field_template,
                'footer_font_color' => $color_field_template,
                'bar_color' => $color_field_template,
                'thinking_color' => $color_field_template,
                'mic_color' => $color_field_template,
                'stop_color' => $color_field_template,

            );
        
            // Check if the field is defined
            if (!array_key_exists($field, $field_definitions)) {
                wp_send_json_error(array('message' => esc_html__('Field definition not found.', 'gpt3-ai-content-generator')));
                return;
            }
        
            $definition = $field_definitions[$field];

            // **Call the appropriate handler functions**

            // Handle 'conversation_starters' field
            if ($field === 'conversation_starters') {
                $this->handle_conversation_starters_save($value, $bot_id);
                return; // Exit after handling
            }

            $fields_to_handle = array(
                0 => [ // Default shortcode bot
                    'model',
                    'openai_stream_nav',
                    'proffesion',
                    // Add other fields specific to bot_id 0 here
                ],
                -1 => [ // Widget bot
                    'vectordb',
                    'embedding',
                    'embedding_type',
                    'embedding_top',
                    'qdrant_collection',
                    'conversation_cut',
                    'ai_thinking',
                    'placeholder',
                    'top_p',
                    'openai_stream_nav',
                    'chat_addition',
                    'chat_addition_text',
                    'welcome',
                    'newchat',
                    'temperature',
                    'max_tokens',
                    'presence_penalty',
                    'language',
                    'model',
                    // Add other fields specific to bot_id -1 here
                ],
                // Add other bot_ids and their fields as needed
                'custom' => [ // Custom bots (bot_id > 0)
                    // Define fields that apply to all custom bots if any
                    // Or handle them separately in the handle_field_save function
                ],
            );
            
            // Function to check if a field is valid for a given bot_id
            function is_field_valid_for_bot($field, $bot_id, $fields_to_handle) {
                if (array_key_exists($bot_id, $fields_to_handle)) {
                    return in_array($field, $fields_to_handle[$bot_id]);
                } elseif ($bot_id > 0 && !empty($fields_to_handle['custom'])) {
                    return in_array($field, $fields_to_handle['custom']);
                }
                return false;
            }
            
            // Example usage within your existing context
            if (is_field_valid_for_bot($field, $bot_id, $fields_to_handle)) {
                $this->handle_field_save($field, $value, $bot_id);
                return; // Exit after handling
            }

            // Handle bot creation
            if (isset($definition['is_creation']) && $definition['is_creation'] && !$bot_id) { // Only create if no bot_id
                if (empty($value)) {
                    $value = 'My Bot'; // Set default bot name
                }
        
                // Create a new bot as a custom post type
                $post_id = wp_insert_post(array(
                    'post_title' => $value,
                    'post_type' => 'wpaicg_chatbot',
                    'post_status' => 'publish',
                ));
        
                if (is_wp_error($post_id)) {
                    wp_send_json_error(array('message' => esc_html__('Failed to create bot.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Initialize the JSON structure with 'id' set to the new post ID
                $bot_data = array(
                    "icon_url" => "",
                    "id" => strval($post_id), // Cast to string
                    "name" => $value,
                    "type" => "shortcode",
                    "pages" => "",
                    "position" => "left",
                    "bgcolor" => "#343A40",
                    "fontcolor" => "#E8E8E8",
                    "ai_bg_color" => "#495057",
                    "user_bg_color" => "#6C757D",
                    "width" => "100%",
                    "height" => "60%",
                    "chat_rounded" => "8",
                    "fontsize" => "13",
                    "bg_text_field" => "#495057",
                    "input_font_color" => "#F8F9FA",
                    "border_text_field" => "#6C757D",
                    "send_color" => "#F8F9FA",
                    "mic_color" => "#F8F9FA",
                    "stop_color" => "#F8F9FA",
                    "text_height" => "60",
                    "text_rounded" => "8",
                    'send_button_enabled' => '1',
                    "footer_color" => "#495057",
                    "footer_font_color" => "#FFFFFF",
                    "bar_color" => "#FFFFFF",
                    "thinking_color" => "#CED4DA",
                    "icon" => "default",
                    "delay_time" => "",
                    "provider" => "OpenAI",
                    "model" => "gpt-4o-mini",
                    'openai_stream_nav' => '1',
                    "chat_addition" => "1",
                    "chat_addition_text" => "You are a helpful AI Assistant. Please be friendly. Today's date is [date].",
                    'internet_browsing' => '0',
                    "max_tokens" => "1500",
                    "temperature" => "0",
                    "audio_enable" => "0",
                    "top_p" => "0",
                    "best_of" => "1",
                    "frequency_penalty" => "0",
                    "presence_penalty" => "0",
                    "moderation_model" =>  "text-moderation-latest",
                    "moderation_notice" =>  "Your message has been flagged as potentially harmful or inappropriate. Please ensure that your messages are respectful and do not contain language or content that could be offensive or harmful to others. Thank you for your cooperation.",
                    "image_enable" => "0",
                    'fullscreen' => '1',
                    'clear_btn' => '1',
                    'sidebar' => '1',
                    'download_btn' => '1',
                    'copy_btn' => '1',
                    'feedback_btn' => '1',
                    'close_btn' => '1',
                    "welcome" => "Hello 👋, how can I help you today?",
                    "newchat" => "New Chat",
                    "ai_thinking" => "Gathering thoughts",
                    "placeholder" => "Type your message here...",
                    "no_answer" => "",
                    "feedback_title" => "Feedback",
                    "feedback_message" => "Please provide details: (optional)",
                    "feedback_success" => "Thank you for your feedback!",
                    "lead_collection" => "0",
                    "lead_title" => "Let us know how to contact you",
                    "lead_name" => "Name",
                    "enable_lead_name" => "1",
                    "lead_email" => "Email",
                    "enable_lead_email" => "1",
                    "lead_phone" => "Phone",
                    "enable_lead_phone" => "1",
                    "content_aware" => "yes",
                    "user_aware" => "yes",
                    "remember_conversation" => "yes",
                    "vectordb" => "pinecone",
                    "embedding_index" => "",
                    "conversation_cut" => "100",
                    "confidence_score" => "20",
                    "embedding" => "0",
                    "use_default_embedding" => "1",
                    "embedding_model" => "text-embedding-ada-002",
                    "embedding_provider" => "",
                    "embedding_top" => "1",
                    "embedding_type" => "openai",
                    "language" => "en",
                    "tone" => "friendly",
                    "proffesion" => "none",
                    "chat_to_speech" => "0",
                    "voice_service" => "openai",
                    "audio_btn" => "0",
                    "muted_by_default" => "0",
                    "openai_model" => "tts-1",
                    "openai_voice" => "alloy",
                    "openai_output_format" => "mp3",
                    "openai_voice_speed" => "1",
                    "elevenlabs_model" => "",
                    "elevenlabs_voice" => "",
                    "voice_language" => "en-US",
                    "voice_name" => "en-US-Wavenet-A",
                    "voice_device" => "",
                    "voice_speed" => "1",
                    "save_logs" =>  "1",
                    "log_request" =>  "1",
                    "log_notice_message" => "Please note that your conversations will be recorded.",
                    "limited_message" => "You have reached your token limit.",
                    "reset_limit" => "0",
                    "footer_text" => "Powered by AI",
                    "conversation_starters" => array(
                        "What’s today’s date?",
                        "Can you tell me a joke?",
                        "What’s something fun I can do today?"
                    )
                );
        
                // Encode JSON and save to post_content
                $encoded_data = wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                wp_update_post(array(
                    'ID' => $post_id,
                    'post_content' => $encoded_data
                ));
        
                wp_send_json_success(array(
                    'message' => esc_html__('Bot created successfully.', 'gpt3-ai-content-generator'),
                    'bot_id' => $post_id
                ));
                return;
            }
        
            // Handle fields that are part of a chatbot (post_content JSON) or default bots (options)
            if ($definition['type'] === 'post_content') {
                if ($bot_id > 0) {
                    // Handle Custom Bots Stored as Posts
        
                    if ($bot_id <= 0) {
                        wp_send_json_error(array('message' => esc_html__('Invalid Bot ID.', 'gpt3-ai-content-generator')));
                        return;
                    }
        
                    // Retrieve existing JSON from post_content
                    $post = get_post($bot_id);
                    if (!$post) {
                        wp_send_json_error(array('message' => esc_html__('Bot not found.', 'gpt3-ai-content-generator')));
                        return;
                    }
        
                    $bot_data = json_decode($post->post_content, true);
                    if (json_last_error() !== JSON_ERROR_NONE) {
                        wp_send_json_error(array('message' => esc_html__('Invalid bot data format.', 'gpt3-ai-content-generator')));
                        return;
                    }
        
                    // Validate the value if a callback is provided
                    if (isset($definition['validate_callback']) && is_callable($definition['validate_callback'])) {
                        $is_valid = call_user_func($definition['validate_callback'], $value);
                        if (!$is_valid) {
                            wp_send_json_error(array('message' => esc_html__('Invalid value provided.', 'gpt3-ai-content-generator')));
                            return;
                        }
                    }
        
                    // Sanitize the value
                    if (isset($definition['sanitize_callback']) && is_callable($definition['sanitize_callback'])) {
                        $sanitized_value = call_user_func($definition['sanitize_callback'], $value);
                    } else {
                        $sanitized_value = sanitize_text_field($value);
                    }
        
                    // Update the specific field in the JSON
                    $bot_data[$field] = $sanitized_value;

                    // Check if the field is 'embedding_model'
                    if ($field === 'embedding_model') {
                        $embedding_models = \WPAICG\WPAICG_Util::get_instance()->get_embedding_models();
                        $embedding_provider = '';

                        // Loop through the models to find the provider
                        foreach ($embedding_models as $provider => $models) {
                            if (array_key_exists($sanitized_value, $models)) {
                                $embedding_provider = $provider;
                                break;
                            }
                        }

                        // Store the embedding provider in the bot data
                        $bot_data['embedding_provider'] = $embedding_provider;
                    }

                    // **NEW CODE: Update post_title if the field is 'name'**
                    if ($field === 'name') {
                        // Update post_title
                        $update_title = wp_update_post(array(
                            'ID' => $bot_id,
                            'post_title' => $sanitized_value
                        ));
        
                        if (is_wp_error($update_title)) {
                            wp_send_json_error(array('message' => esc_html__('Failed to update post title.', 'gpt3-ai-content-generator')));
                            return;
                        }
                    }

                    // Handle mutual exclusivity with streaming**
                    $mutually_exclusive_fields = array('image_enable', 'audio_enable','chat_to_speech'); // Add more fields as needed

                    if (in_array($field, $mutually_exclusive_fields) && $sanitized_value === '1') {
                        // Disable streaming if any of the mutually exclusive fields are enabled
                        $bot_data['openai_stream_nav'] = '0';
                    } elseif ($field === 'openai_stream_nav' && $sanitized_value === '1') {
                        // Disable all mutually exclusive fields when streaming is enabled
                        foreach ($mutually_exclusive_fields as $exclusive_field) {
                            $bot_data[$exclusive_field] = '0';
                        }
                    }

                    // Handle deletion for 'user_limited' and 'role_limited' fields. If unchecked, delete the field.
                    $limited_fields = ['user_limited', 'role_limited','guest_limited'];

                    if (in_array($field, $limited_fields)) {
                        if ($sanitized_value === '1') {
                            // Only set the field if it's checked
                            $bot_data[$field] = '1';
                        } else {
                            // Delete the field if it's unchecked
                            unset($bot_data[$field]);
                        }
                    } else {
                        // Update the specific field in the JSON
                        $bot_data[$field] = $sanitized_value;
                    }

                    // if user_limited not exists then delete user_tokens field too
                    if (!isset($bot_data['user_limited'])) {
                        unset($bot_data['user_tokens']);
                    }

                    // if guest_limited not exists then delete guest_tokens field too
                    if (!isset($bot_data['guest_limited'])) {
                        unset($bot_data['guest_tokens']);
                    }

                    // Update the post_content with the modified data if needed
                    $encoded_data = wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    wp_update_post(array(
                        'ID' => $bot_id,
                        'post_content' => wp_slash($encoded_data)
                    ));


                    // **NEW CODE: Handle 'pages' field updates for Widget Bots**
                    if ($field === 'pages') {
                        if ($sanitized_value !== '') {
                            // Ensure the bot type is 'widget'
                            if (isset($bot_data['type']) && $bot_data['type'] === 'widget') {
                                // Sanitize the pages input: expect comma-separated integers
                                $pages = array_map('trim', explode(',', $sanitized_value));
                                $valid_pages = array_filter($pages, 'ctype_digit'); // Keep only numeric IDs
                                // get global wpdb object
                                global $wpdb;
                                // Delete existing 'wpaicg_widget_page_*' post meta
                                $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s", $bot_id, 'wpaicg_widget_page_%'));

                                // Add new post meta for each valid page ID
                                foreach ($valid_pages as $page_id) {
                                    add_post_meta($bot_id, 'wpaicg_widget_page_' . intval($page_id), 'yes', true);
                                }
                            }
                        } else {
                            // If 'pages' is empty, remove all related post meta
                            $wpdb->query($wpdb->prepare("DELETE FROM {$wpdb->postmeta} WHERE post_id = %d AND meta_key LIKE %s", $bot_id, 'wpaicg_widget_page_%'));
                        }
                    }

                    // Special handling for 'icon_url' field
                    if ($field === 'icon_url') {
                        if ($bot_data['icon'] === 'custom') {
                            if (empty($value)) {
                                wp_send_json_error(array('message' => esc_html__('Icon ID is required when using a custom icon.', 'gpt3-ai-content-generator')));
                                return;
                            }

                            // Validate that the attachment ID exists and is an image
                            if (!wp_attachment_is_image($value)) {
                                wp_send_json_error(array('message' => esc_html__('Invalid attachment ID or not an image.', 'gpt3-ai-content-generator')));
                                return;
                            }
                        } else {
                            // If 'icon' is not 'custom', ensure 'icon_url' is empty
                            $bot_data['icon_url'] = '';
                        }
                    }

                    // Set Google language and voice name based on the 'voice_language' field
                    if ($field === 'voice_language') {
                        // Split the value by the '|' delimiter
                        $parts = explode('|', $sanitized_value);
                        
                        // Check if both parts are present
                        if (count($parts) === 2) {
                            // Assign the first part to 'voice_language'
                            $bot_data['voice_language'] = sanitize_text_field($parts[0]);
                            
                            // Assign the second part to 'voice_name'
                            $bot_data['voice_name'] = sanitize_text_field($parts[1]);
                        } else {
                            // Handle cases where the expected delimiter is not found
                            wp_send_json_error(array('message' => esc_html__('Invalid voice_language format.', 'gpt3-ai-content-generator')));
                            return;
                        }
                    }

                    // Encode JSON and save back to post_content
                    $encoded_data = wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    wp_update_post(array(
                        'ID' => $bot_id,
                        'post_content' => wp_slash($encoded_data)
                    ));
        
                    wp_send_json_success(array('message' => esc_html__('Bot updated successfully.', 'gpt3-ai-content-generator')));
                    return;
                } elseif ($bot_id === 0 || $bot_id === -1) {
                    // Handle Default Bots Stored in Options
        
                    // Determine the option key based on bot_id
                    if ($bot_id === 0) {
                        $option_key = 'wpaicg_chat_shortcode_options';
                    } elseif ($bot_id === -1) {
                        $option_key = 'wpaicg_chat_widget';
                    } else {
                        wp_send_json_error(array('message' => esc_html__('Invalid Bot ID for option type.', 'gpt3-ai-content-generator')));
                        return;
                    }
        
                    // Retrieve existing data from the option
                    $bot_data = get_option($option_key, array());

                    if ($field === 'name') {
                        // Do not save 'name' field for default bots
                        wp_send_json_success(array('message' => esc_html__('Bot name is not editable for default bots.', 'gpt3-ai-content-generator')));
                        return;
                    }
        
                    if (empty($bot_data)) {
                        wp_send_json_error(array('message' => esc_html__('Bot data not found in options.', 'gpt3-ai-content-generator')));
                        return;
                    }

                    // Validate the value if a callback is provided
                    if (isset($definition['validate_callback']) && is_callable($definition['validate_callback'])) {
                        $is_valid = call_user_func($definition['validate_callback'], $value);
                        if (!$is_valid) {
                            wp_send_json_error(array('message' => esc_html__('Invalid value provided.', 'gpt3-ai-content-generator')));
                            return;
                        }
                    }
        
                    // Sanitize the value
                    if (isset($definition['sanitize_callback']) && is_callable($definition['sanitize_callback'])) {
                        $sanitized_value = call_user_func($definition['sanitize_callback'], $value);
                    } else {
                        $sanitized_value = sanitize_text_field($value);
                    }
        
                    // Update the specific field in the option data
                    $bot_data[$field] = $sanitized_value;

        
                    // For 'name' field, ensure consistency
                    if ($field === 'name') {
                        // You might want to perform additional actions here if necessary
                        // For example, updating titles in other areas or caching mechanisms
                    }
                    if ($field === 'voice_language') {
                        if (strpos($sanitized_value, '|') !== false) {
                            // Split into language and voice name
                            $parts = explode('|', $sanitized_value, 2);
                            $language_part = sanitize_text_field($parts[0]);
                            $voice_part = sanitize_text_field($parts[1]);
            
                            // Store both language and voice_name in the option data
                            $bot_data['voice_language'] = $language_part;
                            $bot_data['voice_name'] = $voice_part;
                        } else {
                            // If no delimiter, just store the language and clear voice_name
                            $bot_data['voice_language'] = $sanitized_value;
                            // Optionally clear voice_name if needed
                            if (isset($bot_data['voice_name'])) {
                                unset($bot_data['voice_name']);
                            }
                        }
                    } 
                    // Special handling for 'icon_url' field
                    if ($field === 'icon_url') {
                        if (isset($bot_data['icon']) && $bot_data['icon'] === 'custom') {
                            if (empty($sanitized_value)) {
                                wp_send_json_error(array('message' => esc_html__('Icon ID is required when using a custom icon.', 'gpt3-ai-content-generator')));
                                return;
                            }

                            // Validate that the attachment ID exists and is an image
                            if (!wp_attachment_is_image($sanitized_value)) {
                                wp_send_json_error(array('message' => esc_html__('Invalid attachment ID or not an image.', 'gpt3-ai-content-generator')));
                                return;
                            }
                        } else {
                            // If 'icon' is not 'custom', ensure 'icon_url' is empty
                            $bot_data['icon_url'] = '';
                        }
                    }

                    // Update the option in the database
                    update_option($option_key, $bot_data);
        
                    wp_send_json_success(array('message' => esc_html__('Bot updated successfully.', 'gpt3-ai-content-generator')));
                    return;
                }
            }
        
            // Handle fields that are part of options (non-post_content)
            if ($definition['type'] === 'option') {
                // Handle Default Bots Stored in Options
                if ($bot_id === 0) {
                    $option_key = 'wpaicg_chat_shortcode_options';
                } elseif ($bot_id === -1) {
                    $option_key = 'wpaicg_chat_widget';
                } else {
                    // For other bots stored as options, you can extend this logic
                    wp_send_json_error(array('message' => esc_html__('Invalid Bot ID for option type.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Retrieve existing data from the option
                $bot_data = get_option($option_key, array());
        
                if (empty($bot_data)) {
                    wp_send_json_error(array('message' => esc_html__('Bot data not found in options.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Validate the value if a callback is provided
                if (isset($definition['validate_callback']) && is_callable($definition['validate_callback'])) {
                    $is_valid = call_user_func($definition['validate_callback'], $value);
                    if (!$is_valid) {
                        wp_send_json_error(array('message' => esc_html__('Invalid value provided.', 'gpt3-ai-content-generator')));
                        return;
                    }
                }
        
                // Sanitize the value
                if (isset($definition['sanitize_callback']) && is_callable($definition['sanitize_callback'])) {
                    $sanitized_value = call_user_func($definition['sanitize_callback'], $value);
                } else {
                    $sanitized_value = sanitize_text_field($value);
                }
        
                // Update the specific field in the option data
                $bot_data[$field] = $sanitized_value;
        
                // Update the option in the database
                update_option($option_key, $bot_data);
        
                wp_send_json_success(array('message' => esc_html__('Setting updated successfully.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // If the field does not belong to any known group
            wp_send_json_error(array('message' => esc_html__('Field mapping not defined.', 'gpt3-ai-content-generator')));
        }

        private function get_conversation_starters($bot_id) {
            if ($bot_id === 0) {
                $starter_option_key = 'wpaicg_conversation_starters'; // Option name for shortcode bot
            } elseif ($bot_id === -1) {
                $starter_option_key = 'wpaicg_conversation_starters_widget'; // Option name for widget bot
            } else {
                return array(); // For custom bots, conversation_starters are stored in the bot_data
            }
            $conversation_starters_json = get_option($starter_option_key, '[]'); // Default to '[]' if not set
            $conversation_starters_array = json_decode($conversation_starters_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $conversation_starters_array = array(); // Default to empty array if JSON decode fails
            }
            $conversation_starters = array();
            foreach ($conversation_starters_array as $starter) {
                if (isset($starter['text'])) {
                    $conversation_starters[] = $starter['text'];
                }
            }
            return $conversation_starters;
        }
        
        
        private function handle_conversation_starters_save($value, $bot_id) {
            // Sanitize and process the conversation starters
            $conversation_starters = explode("\n", sanitize_textarea_field($value));
            $conversation_starters = array_map('trim', $conversation_starters); // Trim each line
            $conversation_starters = array_filter($conversation_starters); // Remove empty lines
        
            if ($bot_id === 0 || $bot_id === -1) {
                // For bot_id === 0 or bot_id === -1, save to the corresponding option
                $starter_option_key = ($bot_id === 0) ? 'wpaicg_conversation_starters' : 'wpaicg_conversation_starters_widget';
                $starters_to_save = array();
                foreach ($conversation_starters as $index => $text) {
                    $starters_to_save[] = array('index' => $index, 'text' => $text);
                }
                update_option($starter_option_key, wp_json_encode($starters_to_save));
            } elseif ($bot_id > 0) {
                // Retrieve existing JSON from post_content
                $post = get_post($bot_id);
                if (!$post) {
                    wp_send_json_error(array('message' => esc_html__('Bot not found.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                $bot_data = json_decode($post->post_content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    wp_send_json_error(array('message' => esc_html__('Invalid bot data format.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Update the conversation_starters field in the JSON
                $bot_data['conversation_starters'] = $conversation_starters;
        
                // Save the updated bot data
                $encoded_data = wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                wp_update_post(array(
                    'ID' => $bot_id,
                    'post_content' => wp_slash($encoded_data)
                ));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid Bot ID for conversation starters.', 'gpt3-ai-content-generator')));
                return;
            }
        
            wp_send_json_success(array('message' => esc_html__('Bot updated successfully.', 'gpt3-ai-content-generator')));
        }
        
        private function handle_field_save($field, $value, $bot_id) {
            // Sanitize the value
            $sanitized_value = sanitize_text_field($value);
        
            // Define the mapping for fields and their corresponding options
            $default_bot_options = array(
                'model' => array(
                    'option_key' => 'wpaicg_chat_shortcode_options',
                    'google_key' => 'wpaicg_shortcode_google_model'
                ),
                'openai_stream_nav' => array(
                    'option_key' => 'wpaicg_shortcode_stream'
                ),
                'proffesion' => array(
                    'option_key' => 'wpaicg_chat_shortcode_options'
                ),
                // Add more fields as needed
            );
        
            if ($bot_id === 0) {
                // For default shortcode bot
                if (isset($default_bot_options[$field])) {
                    $option_key = $default_bot_options[$field]['option_key'];
        
                    // Special handling for the 'model' field with Google provider
                    if ($field === 'model') {
                        $bot_data = get_option($option_key, array());
                        if (isset($bot_data['provider']) && $bot_data['provider'] === 'Google') {
                            update_option($default_bot_options[$field]['google_key'], $sanitized_value);
                        } else {
                            $bot_data[$field] = $sanitized_value;
                            update_option($option_key, $bot_data);
                        }
                    } elseif ($field === 'proffesion') {
                        // Correct the field name to 'profession'
                        $option_key = 'wpaicg_chat_shortcode_options';
                        $bot_data = get_option($option_key, array());
                        $bot_data['profession'] = $sanitized_value;
                        update_option($option_key, $bot_data);
                    } else {
                        update_option($option_key, $sanitized_value);
                    }
                }
            } elseif ($bot_id > 0) {
                // For custom bots, update the field in the bot's JSON data
                $post = get_post($bot_id);
                if (!$post) {
                    wp_send_json_error(array('message' => esc_html__('Bot not found.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                $bot_data = json_decode($post->post_content, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    wp_send_json_error(array('message' => esc_html__('Invalid bot data format.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Update the field in the JSON
                $bot_data[$field] = $sanitized_value;
        
                // Encode JSON and save back to post_content
                $encoded_data = wp_json_encode($bot_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                wp_update_post(array(
                    'ID' => $bot_id,
                    'post_content' => wp_slash($encoded_data)
                ));
            } elseif ($bot_id === -1) {
                // For bot_id -1, handle multiple conditions
                // Mapping of fields to option keys, excluding 'model'
                $field_option_map = array(
                    'chat_addition' => 'wpaicg_chat_addition',
                    'chat_addition_text' => 'wpaicg_chat_addition_text',
                    'openai_stream_nav' => 'wpaicg_widget_stream',
                    'welcome' => '_wpaicg_chatbox_welcome_message',
                    'newchat' => '_wpaicg_chatbox_newchat',
                    'temperature' => 'wpaicg_chat_temperature',
                    'max_tokens' => 'wpaicg_chat_max_tokens',
                    'presence_penalty' => 'wpaicg_chat_presence_penalty',
                    'language' => 'wpaicg_chat_language',
                    'vectordb' => 'wpaicg_chat_vectordb',
                    'embedding' => 'wpaicg_chat_embedding',
                    'embedding_type' => 'wpaicg_chat_embedding_type',
                    'embedding_top' => 'wpaicg_chat_embedding_top',
                    'qdrant_collection' => 'wpaicg_widget_qdrant_collection',
                    'conversation_cut' => 'wpaicg_conversation_cut',
                    'ai_thinking' => '_wpaicg_ai_thinking',
                    'placeholder' => '_wpaicg_typing_placeholder',
                    'top_p' => 'wpaicg_chat_top_p',
                    // Add more fields and their option keys here
                );
        
                if ($field === 'model') {
                    // Check provider value in wpaicg_chat_widget option
                    $widget_options = get_option('wpaicg_chat_widget');
                    if (!empty($widget_options) && isset($widget_options['provider'])) {
                        $provider = $widget_options['provider'];
        
                        // Update model based on provider
                        switch ($provider) {
                            case 'OpenAI':
                                update_option('wpaicg_chat_model', $sanitized_value);
                                // Update the model value in $widget_options and save it back
                                $widget_options['model'] = $sanitized_value;
                                update_option('wpaicg_chat_widget', $widget_options);
                                break;
                            case 'OpenRouter':
                                update_option('wpaicg_widget_openrouter_model', $sanitized_value);
                                break;
                            case 'Google':
                                update_option('wpaicg_widget_google_model', $sanitized_value);
                                break;
                            case 'Azure':
                                update_option('wpaicg_azure_deployment', $sanitized_value);
                                break;
                            default:
                                wp_send_json_error(array('message' => esc_html__('Invalid provider.', 'gpt3-ai-content-generator')));
                                return;
                        }
                    } else {
                        wp_send_json_error(array('message' => esc_html__('Provider not found.', 'gpt3-ai-content-generator')));
                        return;
                    }
                } elseif (array_key_exists($field, $field_option_map)) {
                    // Get the corresponding option key
                    $option_key = $field_option_map[$field];
                    // Update the option
                    update_option($option_key, $sanitized_value);
                } else {
                    // Optionally handle other fields or return an error
                    wp_send_json_error(array('message' => esc_html__('Invalid field for widget bot.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Add other conditions for bot_id -1 as needed
            } else {
                // Handle other bot IDs if necessary
                wp_send_json_error(array('message' => esc_html__('Invalid Bot ID for field.', 'gpt3-ai-content-generator')));
                return;
            }
        
            wp_send_json_success(array('message' => esc_html__('Bot updated successfully.', 'gpt3-ai-content-generator')));
        }
        
        // Implement the sanitization function
        public function sanitize_limited_roles($value) {
            // Decode JSON if it's a string
            if (is_string($value)) {
                $value = json_decode($value, true);
            }
            if (!is_array($value)) {
                return array();
            }
            $sanitized_roles = array();
            foreach ($value as $role => $limit) {
                $role = sanitize_text_field($role);
                $limit = trim($limit);
                if ($limit === '') {
                    $sanitized_limit = '';
                } else {
                    $sanitized_limit = intval($limit);
                    if ($sanitized_limit < 0) {
                        $sanitized_limit = 0;
                    }
                }
                $sanitized_roles[$role] = $sanitized_limit;
            }
            return $sanitized_roles;
        }

        /**
         * Validation function for icon_url
         */
        public function validate_icon_url($value) {
            if (empty($value)) {
                // If icon is set to 'default', icon_url can be empty
                return true;
            }

            // Validate that the attachment ID exists and is an image
            return wp_attachment_is_image($value);
        }

        /**
         * Validation function for bot name
         */
        public function validate_bot_name($value) {
            // Allow empty names; default will be set
            return true;
        }
        
        /**
         * Validation function for provider
         */
        public function validate_provider($value) {
            $valid_providers = array('OpenAI', 'Google', 'OpenRouter', 'Azure');
            return in_array($value, $valid_providers);
        }

        /**
         * Validation function for some_option_field (example)
         */
        public function validate_some_option_field($value) {
            // Implement validation logic for the option field
            return true; // Placeholder
        }

        public function sanitize_checkbox($value) {
            return $value === '1' ? '1' : '0';
        }   
        /**
         * Validation function for bot type
         */
        public function validate_bot_type($value) {
            $valid_types = array('shortcode', 'widget');
            return in_array($value, $valid_types, true);
        }  
        
        /**
         * Validation function for pages
         */
        public function validate_pages($value) {
            // Split the input by commas
            $ids = explode(',', $value);
            foreach ($ids as $id) {
                // Trim whitespace and check if each ID is a digit
                if (!ctype_digit(trim($id))) {
                    return false;
                }
            }
            return true;
        }

        /**
         * Validation function for widget position
         *
         * @param string $value The value to validate.
         * @return bool True if valid, false otherwise.
         */
        public function validate_position($value) {
            $valid_positions = array('left', 'right');
            return in_array($value, $valid_positions, true);
        }
        
        /**
         * Truncate text to a specified maximum length and append ellipsis if necessary.
         * Also, decode HTML entities to prevent issues with special characters.
         *
         * @param string $text The text to truncate.
         * @param int    $max_length The maximum allowed length.
         * @return string Truncated text with ellipsis if it exceeds max_length.
         */
        public static function truncate_text($text, $max_length = 20) {
            // Decode HTML entities before truncating
            $text = html_entity_decode($text, ENT_QUOTES, 'UTF-8');
            
            // Truncate if the text exceeds the max length
            if (mb_strlen($text) > $max_length) {
                return esc_html(mb_substr($text, 0, $max_length) . '...');
            }
            
            // Return the text safely escaped
            return esc_html($text);
        }


        /**
         * Validation function for max_tokens
         */
        public function validate_max_tokens($value) {
            // Ensure it is a positive integer
            return is_numeric($value) && intval($value) > 0 && intval($value) == $value;
        }

        /**
         * Generic Validate function for yes/no fields.
         *
         * @param string $value The value to validate.
         * @return bool True if valid, false otherwise.
         */
        public function validate_yes_no_field($value) {
            return in_array($value, array('yes', 'no'), true);
        }

        /**
         * Generic Sanitize function for yes/no fields.
         *
         * @param string $value The value to sanitize.
         * @return string The sanitized value.
         */
        public function sanitize_yes_no_field($value) {
            return in_array($value, array('yes', 'no'), true) ? $value : 'yes'; // Default to 'yes' if invalid
        }

        /**
         * Validation function for vectordb
         */
        public function validate_vectordb($value) {
            $valid_vectordbs = array('pinecone', 'qdrant');
            return in_array(strtolower($value), $valid_vectordbs, true);
        }

        /**
         * Validation function for embedding_type
         */
        public function validate_embedding_type($value) {
            $valid_types = array('openai', ''); // '' represents Non-Conversational
            return in_array($value, $valid_types, true);
        }

        /**
         * Sanitize footer text allowing specific HTML tags.
         *
         * @param string $value The footer text input by the user.
         * @return string The sanitized footer text.
         */
        public function sanitize_footer_text($value) {
            // Define allowed HTML tags and their attributes
            $allowed_tags = array(
                'a' => array(
                    'href'  => array(),
                    'title' => array(),
                    'target' => array(),
                    'rel'    => array(),
                ),
                'br' => array(),
                'em' => array(),
                'strong' => array(),
                // Add other tags if needed
            );
            
            // Use wp_kses to sanitize the input
            return wp_kses($value, $allowed_tags);
        }

        /**
         * AJAX handler to retrieve attachment URL based on attachment ID.
         */
        public function aipower_get_attachment_url() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                return;
            }

            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('Insufficient permissions.', 'gpt3-ai-content-generator')));
                return;
            }

            // Sanitize and retrieve attachment_id
            $attachment_id = isset($_POST['attachment_id']) ? intval($_POST['attachment_id']) : 0;

            if (!$attachment_id || !wp_attachment_is_image($attachment_id)) {
                wp_send_json_error(array('message' => esc_html__('Invalid attachment ID or not an image.', 'gpt3-ai-content-generator')));
                return;
            }

            $attachment_url = wp_get_attachment_url($attachment_id);

            if ($attachment_url) {
                wp_send_json_success(array('url' => $attachment_url));
            } else {
                wp_send_json_error(array('message' => esc_html__('Failed to retrieve attachment URL.', 'gpt3-ai-content-generator')));
            }
        }

        public function aipower_delete_chatbot() {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }
        
            $chatbot_id = isset($_POST['chatbot_id']) ? intval($_POST['chatbot_id']) : 0;
        
            if ($chatbot_id) {
                $deleted = wp_delete_post($chatbot_id, true); // Force delete
        
                if ($deleted) {
                    wp_send_json_success();
                } else {
                    wp_send_json_error(array('message' => esc_html__('Failed to delete the chatbot.', 'gpt3-ai-content-generator')));
                }
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid chatbot ID.', 'gpt3-ai-content-generator')));
            }
        }
        
        public function aipower_load_chatbot() {
            // Verify nonce for security
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Get and sanitize the type and bot_id from POST data
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'bot';
            $bot_id = isset($_POST['bot_id']) ? sanitize_text_field($_POST['bot_id']) : '';
        
            // Initialize the chatbox variable
            $chatbox = '';
        
            // Define the default icon path
            $default_icon_path = WPAICG_PLUGIN_URL . 'admin/images/chatbot.png';
        
            // Handle Default Shortcode Bot
            if ($type === 'shortcode' && $bot_id === '0') {
                // Always echo the default shortcode
                $shortcode = '[wpaicg_chatgpt]';
                $chatbox = do_shortcode($shortcode);
            }
            // Handle Default Site-wide Widget
            elseif ($type === 'widget' && $bot_id === '-1') {
                // Generate a random Widget ID
                $randomWidgetID = wp_rand(100000, 999999);
        
                // Retrieve the options from the options table
                $widget_options = get_option('wpaicg_chat_widget', array());
        
                // Get 'icon' and 'icon_url' from options, defaulting to 'default' and empty
                $icon = isset($widget_options['icon']) ? sanitize_text_field($widget_options['icon']) : 'default';
                $icon_url = isset($widget_options['icon_url']) ? sanitize_text_field($widget_options['icon_url']) : '';
        
                // Get the chat icon URL using the helper function
                $wpaicg_chat_icon_url = $this->get_chat_icon_url($icon, $icon_url, $default_icon_path);
        
                // Construct the HTML structure for the widget bot
                ob_start();
                ?>
                <div data-id="<?php echo esc_attr($randomWidgetID); ?>" id="wpaicgChat<?php echo esc_attr($randomWidgetID); ?>" class="wpaicg_chat_widget">
                    <div class="wpaicg_chat_widget_content">
                        <?php echo do_shortcode('[wpaicg_chatgpt_widget]'); ?>
                    </div>
                    <div class="wpaicg_toggle" id="wpaicg_toggle_<?php echo esc_attr($randomWidgetID); ?>">
                        <img src="<?php echo esc_url($wpaicg_chat_icon_url); ?>" alt="<?php esc_attr_e('Chatbot Toggle', 'gpt3-ai-content-generator'); ?>" />
                    </div>
                </div>
                <?php
                $chatbox = ob_get_clean();
            }
            // Handle Custom Bots
            elseif ($type === 'bot' && is_numeric($bot_id) && intval($bot_id) > 0) {
                $bot_id = intval($bot_id);
        
                // Retrieve the bot post
                $bot_post = get_post($bot_id);
                if (!$bot_post || $bot_post->post_type !== 'wpaicg_chatbot') {
                    wp_send_json_error(array('message' => esc_html__('Bot not found.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Decode the JSON stored in post_content
                $bot_data = json_decode($bot_post->post_content, true);
                if (json_last_error() !== JSON_ERROR_NONE || !is_array($bot_data)) {
                    wp_send_json_error(array('message' => esc_html__('Invalid bot data format.', 'gpt3-ai-content-generator')));
                    return;
                }
        
                // Determine the bot type: 'shortcode' or 'widget'
                $bot_type = isset($bot_data['type']) ? sanitize_text_field($bot_data['type']) : 'shortcode';
        
                if ($bot_type === 'shortcode') {
                    // Custom Shortcode Bot
                    $shortcode = '[wpaicg_chatgpt id="' . esc_attr($bot_id) . '"]';
                    $chatbox = do_shortcode($shortcode);
                }
                elseif ($bot_type === 'widget') {
                    // Custom Widget Bot
        
                    // Generate a random Widget ID
                    $randomWidgetID = wp_rand(100000, 999999);
        
                    // Get 'icon' and 'icon_url' from bot_data, defaulting to 'default' and empty
                    $icon = isset($bot_data['icon']) ? sanitize_text_field($bot_data['icon']) : 'default';
                    $icon_url = isset($bot_data['icon_url']) ? sanitize_text_field($bot_data['icon_url']) : '';
        
                    // Get the chat icon URL using the helper function
                    $wpaicg_chat_icon_url = $this->get_chat_icon_url($icon, $icon_url, $default_icon_path);
        
                    // Construct the HTML structure for the widget bot
                    ob_start();
                    ?>
                    <div data-id="<?php echo esc_attr($randomWidgetID); ?>" id="wpaicgChat<?php echo esc_attr($randomWidgetID); ?>" class="wpaicg_chat_widget">
                        <div class="wpaicg_chat_widget_content">
                            <?php echo do_shortcode('[wpaicg_chatgpt_widget id="' . esc_attr($bot_id) . '"]'); ?>
                        </div>
                        <div class="wpaicg_toggle" id="wpaicg_toggle_<?php echo esc_attr($randomWidgetID); ?>">
                            <img src="<?php echo esc_url($wpaicg_chat_icon_url); ?>" alt="<?php esc_attr_e('Chatbot Toggle', 'gpt3-ai-content-generator'); ?>" />
                        </div>
                    </div>
                    <?php
                    $chatbox = ob_get_clean();
                }
                else {
                    // Invalid bot type
                    wp_send_json_error(array('message' => esc_html__('Invalid bot type.', 'gpt3-ai-content-generator')));
                    return;
                }
            }
            else {
                // Invalid request parameters
                wp_send_json_error(array('message' => esc_html__('Invalid request parameters.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Return the chatbox as a JSON response
            wp_send_json_success(array('chatbox' => $chatbox));
        }
        
        /**
         * Helper function to retrieve the chat icon URL based on icon type and URL.
         *
         * @param string $icon       The icon type ('default' or 'custom').
         * @param string $icon_url   The icon URL or attachment ID if custom.
         * @param string $default_icon_path The default icon path to use if custom icon retrieval fails.
         *
         * @return string The sanitized URL of the icon to be used.
         */
        private function get_chat_icon_url($icon, $icon_url, $default_icon_path) {
            if ($icon === 'custom' && !empty($icon_url)) {
                // Assume icon_url is an attachment ID; retrieve the URL
                $attachment_url = wp_get_attachment_url(intval($icon_url));
                if ($attachment_url) {
                    return esc_url($attachment_url);
                }
            }
            // Fallback to default icon
            return esc_url($default_icon_path);
        }
                
        public function aipower_refresh_chatbot_table() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            $paged = isset($_POST['page']) ? intval($_POST['page']) : 1; // Get current page
            $search = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : ''; // Get search query
        
            // Use the pagination and search parameters when rendering the table
            $table_html = self::aipower_render_chatbot_table($paged, $search);
        
            wp_send_json_success(array('table' => $table_html));
        }
        
        public static function aipower_render_chatbot_table($paged = 1) {
            
            // Number of bots to display per page (excluding defaults)
            $bots_per_page = 8;
        
            // Retrieve the default shortcode data from the options table
            $default_shortcode_serialized = get_option('wpaicg_chat_shortcode_options');
            if ($default_shortcode_serialized === false) {
                // Retrieve the default values for shortcode bots and update the option
                $default_shortcode_data = \WPAICG\WPAICG_Util::get_instance()->get_default_values('shortcode');
                update_option('wpaicg_chat_shortcode_options', $default_shortcode_data);
            } else {
                // If option exists, unserialize it
                $default_shortcode_data = maybe_unserialize($default_shortcode_serialized);
            }
            // Extract provider and model, with fallback to 'N/A' if not set
            $default_shortcode_provider = isset($default_shortcode_data['provider']) ? esc_html($default_shortcode_data['provider']) : esc_html__('N/A', 'gpt3-ai-content-generator');

            if ($default_shortcode_provider === 'Google') {
                $default_shortcode_model = get_option('wpaicg_shortcode_google_model', esc_html__('N/A', 'gpt3-ai-content-generator'));
            } else {
                $default_shortcode_model = isset($default_shortcode_data['model']) ? esc_html($default_shortcode_data['model']) : esc_html__('N/A', 'gpt3-ai-content-generator');
            }
            
            // Retrieve the default widget data from the options table
            $default_widget_serialized = get_option('wpaicg_chat_widget');
            if ($default_widget_serialized === false) {
                // Retrieve the default values for widget bots and update the option
                $default_widget_data = \WPAICG\WPAICG_Util::get_instance()->get_default_values('widget');
                update_option('wpaicg_chat_widget', $default_widget_data);
            } else {
                // If option exists, unserialize it
                $default_widget_data = maybe_unserialize($default_widget_serialized);
            }
        
            // Extract provider and model, with fallback to 'N/A' if not set
            $default_widget_provider = isset($default_widget_data['provider']) ? esc_html($default_widget_data['provider']) : esc_html__('N/A', 'gpt3-ai-content-generator');

            // Retrieve the default widget model based on the provider
            if ($default_widget_provider === 'OpenAI') {
                $default_widget_model_option = get_option('wpaicg_chat_model');
            } elseif ($default_widget_provider === 'Google') {
                $default_widget_model_option = get_option('wpaicg_widget_google_model');
            } elseif ($default_widget_provider === 'OpenRouter') {
                $default_widget_model_option = get_option('wpaicg_widget_openrouter_model');
            } elseif ($default_widget_provider === 'Azure') {
                $default_widget_model_option = get_option('wpaicg_azure_deployment');
            } else {
                $default_widget_model_option = null; // If no provider is matched
            }

            // Set the model with fallback to 'N/A' if not available
            $default_widget_model = isset($default_widget_data['model']) 
                ? esc_html($default_widget_data['model']) 
                : ($default_widget_model_option ? esc_html($default_widget_model_option) : esc_html__('N/A', 'gpt3-ai-content-generator'));


        
            // Query to retrieve dynamic bots with custom post type 'wpaicg_chatbot'
            $bots_args = array(
                'post_type'      => 'wpaicg_chatbot',
                'posts_per_page' => $bots_per_page,
                'paged'          => $paged,
                'orderby'        => 'modified',
                'order'          => 'DESC',
            );
            $bots_query = new \WP_Query($bots_args);
            $total_bots = $bots_query->found_posts;
        
            // Calculate total pages based on dynamic bots only
            $total_pages = ceil($total_bots / $bots_per_page);
        
            ob_start();
        
            if ($bots_query->have_posts() || $paged == 1) :
                ?>
                <div id="aipower-chatbot-table-container">
                    <table class="aipower-chatbot-table">
                        <!-- Define column widths -->
                        <colgroup>
                            <col style="width: 30%;"> <!-- Name -->
                            <col style="width: 30%;"> <!-- Tools -->
                            <col style="width: 25%;"> <!-- Model -->
                            <col style="width: 25%;"> <!-- Actions (Fixed Width) -->
                        </colgroup>

                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Name', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php echo esc_html__('Tools', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php echo esc_html__('Model', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php echo esc_html__('Actions', 'gpt3-ai-content-generator'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            // Display default shortcode and default widget on the first page
                            if ($paged == 1) {
                                ?>
                                <!-- Default Shortcode Row -->
                                <tr>
                                    <td><?php echo esc_html__('Shortcode', 'gpt3-ai-content-generator'); ?></td>
                                    <td>
                                        <span class="aipower-icons-container">
                                            <?php if (get_option('wpaicg_shortcode_stream') === '1') : ?>
                                                <span class="dashicons dashicons-admin-plugins aipower-streaming-icon" title="<?php echo esc_attr__('Streaming Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Streaming Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>
                                            
                                            <?php if (isset($default_shortcode_data['image_enable']) && $default_shortcode_data['image_enable'] === '1') : ?>
                                                <span class="dashicons dashicons-format-image aipower-image-icon" title="<?php echo esc_attr__('Image Upload Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Image Upload Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($default_shortcode_data['embedding']) && $default_shortcode_data['embedding'] === '1') : ?>
                                                <span class="dashicons dashicons-database aipower-vector-db-icon" title="<?php echo esc_attr__('Vector DB Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Vector DB Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($default_shortcode_data['internet_browsing']) && $default_shortcode_data['internet_browsing'] === '1') : ?>
                                                <span class="dashicons dashicons-admin-site-alt3 aipower-internet-icon" title="<?php echo esc_attr__('Internet Browsing Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Internet Browsing Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if ((isset($default_shortcode_data['audio_enable']) && $default_shortcode_data['audio_enable'] === '1') ||
                                                    (isset($default_shortcode_data['chat_to_speech']) && $default_shortcode_data['chat_to_speech'] === '1')) : ?>
                                                <span class="dashicons dashicons-controls-volumeon aipower-audio-icon" title="<?php echo esc_attr__('Audio Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Audio Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td title="<?php echo esc_attr($default_shortcode_model); ?>"><?php echo esc_html(self::truncate_text($default_shortcode_model, 15)); ?></td>
                                    <td>
                                        <span class="dashicons dashicons-visibility aipower-preview-icon" data-id="0" data-type="shortcode" data-name="<?php echo esc_attr__('Default Shortcode', 'gpt3-ai-content-generator'); ?>" title="<?php echo esc_attr__('Preview Shortcode', 'gpt3-ai-content-generator'); ?>"></span>
                                        <span class="dashicons dashicons-edit aipower-edit-icon" data-id="0" data-name="<?php echo esc_attr__('Default Shortcode', 'gpt3-ai-content-generator'); ?>" title="<?php echo esc_attr__('Edit Shortcode', 'gpt3-ai-content-generator'); ?>"></span>
                                        <!-- Hidden Delete Icon Placeholder -->
                                        <span class="dashicons dashicons-trash aipower-delete-icon-placeholder" title=""></span>
                                    </td>
                                </tr>

                                <!-- Default Widget Row -->
                                <tr>
                                    <td><?php echo esc_html__('Site-wide Widget', 'gpt3-ai-content-generator'); ?></td>
                                    <td>
                                        <span class="aipower-icons-container">
                                            <?php if (get_option('wpaicg_widget_stream') === '1') : ?>
                                                <span class="dashicons dashicons-admin-plugins aipower-streaming-icon" title="<?php echo esc_attr__('Streaming Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Streaming Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($default_widget_data['image_enable']) && $default_widget_data['image_enable'] === '1') : ?>
                                                <span class="dashicons dashicons-format-image aipower-image-icon" title="<?php echo esc_attr__('Image Upload Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Image Upload Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (get_option('wpaicg_chat_embedding') === '1') : ?>
                                                <span class="dashicons dashicons-database aipower-vector-db-icon" title="<?php echo esc_attr__('Vector DB Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Vector DB Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($default_widget_data['internet_browsing']) && $default_widget_data['internet_browsing'] === '1') : ?>
                                                <span class="dashicons dashicons-admin-site-alt3 aipower-internet-icon" title="<?php echo esc_attr__('Internet Browsing Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Internet Browsing Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if ((isset($default_widget_data['audio_enable']) && $default_widget_data['audio_enable'] === '1') || 
                                                    (isset($default_widget_data['chat_to_speech']) && $default_widget_data['chat_to_speech'] === '1')) : ?>
                                                <span class="dashicons dashicons-controls-volumeon aipower-audio-icon" title="<?php echo esc_attr__('Audio Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Audio Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>
                                            <?php if (isset($default_widget_data['embedding_pdf']) && $default_widget_data['embedding_pdf'] === '1') : ?>
                                                <span class="dashicons dashicons-pdf aipower-pdf-icon" title="<?php echo esc_attr__('PDF Upload Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('PDF Upload Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>
                                        </span>
                                    </td>
                                    <td title="<?php echo esc_attr($default_widget_model); ?>"><?php echo esc_html(self::truncate_text($default_widget_model, 15)); ?></td>
                                    <td>
                                        <span class="dashicons dashicons-visibility aipower-preview-icon" data-id="-1" data-type="widget" data-name="<?php echo esc_attr__('Default Widget', 'gpt3-ai-content-generator'); ?>" title="<?php echo esc_attr__('Preview Widget', 'gpt3-ai-content-generator'); ?>"></span>
                                        <span class="dashicons dashicons-edit aipower-edit-icon" data-id="-1" data-name="<?php echo esc_attr__('Default Widget', 'gpt3-ai-content-generator'); ?>" title="<?php echo esc_attr__('Edit Widget', 'gpt3-ai-content-generator'); ?>"></span>
                                        <!-- New Toggle Switch -->
                                        <?php
                                            // Retrieve the status of the default widget
                                            $widget_status = isset($default_widget_data['status']) && $default_widget_data['status'] === 'active' ? 'active' : '';
                                        ?>
                                        <span class="dashicons dashicons-yes-alt aipower-toggle-switch <?php echo $widget_status === 'active' ? 'active' : 'inactive'; ?>" 
                                            data-status="<?php echo esc_attr($widget_status); ?>" 
                                            title="<?php echo esc_attr__('Toggle Widget Status', 'gpt3-ai-content-generator'); ?>">
                                        </span>
                                    </td>
                                </tr>

                                <?php
                            }
        
                            // Loop through the retrieved bots
                            while ($bots_query->have_posts()) : $bots_query->the_post(); ?>
                                <?php
                                // Decode the JSON stored in post_content
                                $bot_name = get_the_title();
                                $aipower_chatbot_data = json_decode(get_the_content(), true);
                                $aipower_provider = isset($aipower_chatbot_data['provider']) ? esc_html($aipower_chatbot_data['provider']) : esc_html__('N/A', 'gpt3-ai-content-generator');
                                $aipower_model = isset($aipower_chatbot_data['model']) ? esc_html($aipower_chatbot_data['model']) : esc_html__('N/A', 'gpt3-ai-content-generator');
                                $aipower_bot_type = isset($aipower_chatbot_data['type']) ? esc_html($aipower_chatbot_data['type']) : 'shortcode';
                                $aipower_bot_type = isset($aipower_chatbot_data['type']) ? esc_html(ucfirst($aipower_chatbot_data['type'])) : esc_html__('Shortcode', 'gpt3-ai-content-generator'); // Capitalize first letter
                                ?>
                                <tr>
                                    <td title="<?php echo esc_attr($bot_name); ?>">
                                        <?php echo esc_html(self::truncate_text($bot_name, 15)); ?>
                                    </td>
                                    <td>
                                        <span class="aipower-icons-container">
                                            <?php if (isset($aipower_chatbot_data['openai_stream_nav']) && $aipower_chatbot_data['openai_stream_nav'] === '1') : ?>
                                                <span class="dashicons dashicons-admin-plugins aipower-streaming-icon" title="<?php echo esc_attr__('Streaming Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Streaming Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($aipower_chatbot_data['image_enable']) && $aipower_chatbot_data['image_enable'] === '1') : ?>
                                                <span class="dashicons dashicons-format-image aipower-image-icon" title="<?php echo esc_attr__('Image Upload Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Image Upload Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($aipower_chatbot_data['embedding']) && $aipower_chatbot_data['embedding'] === '1') : ?>
                                                <span class="dashicons dashicons-database aipower-vector-db-icon" title="<?php echo esc_attr__('Vector DB Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Vector DB Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if (isset($aipower_chatbot_data['internet_browsing']) && $aipower_chatbot_data['internet_browsing'] === '1') : ?>
                                                <span class="dashicons dashicons-admin-site-alt3 aipower-internet-icon" title="<?php echo esc_attr__('Internet Browsing Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Internet Browsing Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                            <?php if ((isset($aipower_chatbot_data['audio_enable']) && $aipower_chatbot_data['audio_enable'] === '1') || 
                                                    (isset($aipower_chatbot_data['chat_to_speech']) && $aipower_chatbot_data['chat_to_speech'] === '1')) : ?>
                                                <span class="dashicons dashicons-controls-volumeon aipower-audio-icon" title="<?php echo esc_attr__('Audio Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('Audio Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                             <?php if (isset($aipower_chatbot_data['embedding_pdf']) && $aipower_chatbot_data['embedding_pdf'] === '1') : ?>
                                                <span class="dashicons dashicons-pdf aipower-pdf-icon" title="<?php echo esc_attr__('PDF Upload Enabled', 'gpt3-ai-content-generator'); ?>" aria-label="<?php echo esc_attr__('PDF Upload Enabled', 'gpt3-ai-content-generator'); ?>"></span>
                                            <?php endif; ?>

                                        </span>
                                    </td>
                                    <td title="<?php echo esc_attr($aipower_model); ?>"><?php echo esc_html(self::truncate_text($aipower_model, 15)); ?></td>
                                    <td>
                                        <!-- Preview Button with Title -->
                                        <span class="dashicons dashicons-visibility aipower-preview-icon" data-id="<?php echo esc_attr(get_the_ID()); ?>" data-type="bot" data-name="<?php echo esc_attr($bot_name); ?>" data-bot-type="<?php echo esc_attr($aipower_bot_type); ?>" title="<?php echo esc_attr__('Preview Bot', 'gpt3-ai-content-generator'); ?>"></span>

                                        <!-- Edit Button with Title -->
                                        <span class="dashicons dashicons-edit aipower-edit-icon" data-id="<?php echo esc_attr(get_the_ID()); ?>" data-name="<?php echo esc_attr($bot_name); ?>" data-bot-type="<?php echo esc_attr($aipower_bot_type); ?>" title="<?php echo esc_attr__('Edit Bot', 'gpt3-ai-content-generator'); ?>"></span>

                                        <!-- Tools icon and menu section -->
                                        <span class="dashicons dashicons-menu" id="aipower-custom-tools-icon"></span>
                                        <div class="aipower-custom-tools-menu">
                                            <!-- Delete Button with Title -->
                                            <span class="dashicons dashicons-trash aipower-delete-icon" data-id="<?php echo esc_attr(get_the_ID()); ?>" data-bot-type="<?php echo esc_attr($aipower_bot_type); ?>" title="<?php echo esc_attr__('Delete', 'gpt3-ai-content-generator'); ?>"></span>
                                            <!-- Future icons can be added here -->
                                            <span class="dashicons dashicons-admin-page aipower-duplicate-icon" 
                                                data-id="<?php echo esc_attr(get_the_ID()); ?>" 
                                                data-bot-type="<?php echo esc_attr($aipower_bot_type); ?>" 
                                                title="<?php echo esc_attr__('Duplicate', 'gpt3-ai-content-generator'); ?>">
                                            </span>
                                            <span class="dashicons dashicons-download aipower-export-icon" 
                                                id="aipower-export-icon-<?php echo esc_attr(get_the_ID()); ?>" 
                                                data-id="<?php echo esc_attr(get_the_ID()); ?>" 
                                                data-bot-type="<?php echo esc_attr($aipower_bot_type); ?>" 
                                                title="<?php echo esc_attr__('Export', 'gpt3-ai-content-generator'); ?>">
                                            </span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
        
                    <?php
                    // Show pagination only if there are multiple pages of bots
                    if ($total_pages > 1) {
                        ?>
                        <div class="aipower-pagination">
                            <?php
                            $range = 2; // Number of pages to show around the current page
                            $first_last = 2; // Always show the first and last two pages
        
                            if ($total_pages <= ($first_last * 2 + $range)) {
                                // If the total pages are small, display all pages
                                for ($i = 1; $i <= $total_pages; $i++) {
                                    echo '<button class="aipower-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                                }
                            } else {
                                // Display the first set of pages
                                for ($i = 1; $i <= $first_last; $i++) {
                                    echo '<button class="aipower-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                                }
        
                                // Display ellipsis if needed
                                if ($paged > $first_last + $range + 1) {
                                    echo '<span>...</span>';
                                }
        
                                // Calculate start and end for middle pages
                                $start = max($first_last + 1, $paged - $range);
                                $end = min($paged + $range, $total_pages - $first_last);
        
                                // Display the middle pages
                                for ($i = $start; $i <= $end; $i++) {
                                    if ($i > $first_last && $i <= $total_pages - $first_last) {
                                        echo '<button class="aipower-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                                    }
                                }
        
                                // Display ellipsis if needed
                                if ($paged < $total_pages - $first_last - $range) {
                                    echo '<span>...</span>';
                                }
        
                                // Display the last set of pages
                                for ($i = $total_pages - $first_last + 1; $i <= $total_pages; $i++) {
                                    if ($i > $end) {
                                        echo '<button class="aipower-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                                    }
                                }
                            }
                            ?>
                        </div>
                        <?php
                    }
                    ?>
                </div>
                <?php
            else :
                echo '<p>' . esc_html__('No chatbots found.', 'gpt3-ai-content-generator') . '</p>';
            endif;
        
            wp_reset_postdata(); // Reset post data
        
            return ob_get_clean();
        }

        public function aipower_delete_all_bots() {
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Delete all chatbots with custom post type 'wpaicg_chatbot'
            $bots_args = array(
                'post_type'      => 'wpaicg_chatbot',
                'posts_per_page' => -1,
                'fields'         => 'ids',
            );
            $bots_query = new \WP_Query($bots_args);
        
            if ($bots_query->have_posts()) {
                foreach ($bots_query->posts as $bot_id) {
                    wp_delete_post($bot_id, true); // Force delete
                }
            }
        
            wp_send_json_success(array('message' => esc_html__('All chatbots deleted successfully.', 'gpt3-ai-content-generator')));
        }
        
        public function aipower_save_content_settings() {

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (isset($_POST['field']) && isset($_POST['value'])) {
                $field = sanitize_text_field($_POST['field']);
                $value = ($field === 'wpaicg_content_custom_prompt') ? wp_kses_post($_POST['value']) : sanitize_text_field($_POST['value']);
        
                global $wpdb;

                // Handling custom image settings as serialized data
                if (strpos($field, 'wpaicg_custom_image_settings[') === 0) {
                    // Extract the setting key (e.g., 'artist' from 'wpaicg_custom_image_settings[artist]')
                    preg_match('/wpaicg_custom_image_settings\[(.*?)\]/', $field, $matches);
                    if (isset($matches[1])) {
                        $setting_key = $matches[1];
                        // Get the current stored option value (an array)
                        $custom_settings = get_option('wpaicg_custom_image_settings', array());
                
                        // Define the list of required fields
                        $required_fields = [
                            'artist', 'photography_style', 'lighting', 'subject',
                            'camera_settings', 'composition', 'resolution', 
                            'color', 'special_effects'
                        ];
                
                        // Ensure all required fields exist in the array with a default value of 'None'
                        foreach ($required_fields as $required_field) {
                            if (!array_key_exists($required_field, $custom_settings)) {
                                $custom_settings[$required_field] = 'None';
                            }
                        }
                
                        // Update the specific setting
                        $custom_settings[$setting_key] = $value;
                
                        // Save the updated array as serialized data
                        update_option('wpaicg_custom_image_settings', $custom_settings);
                
                        wp_send_json_success(array('message' => esc_html__('Custom image setting updated successfully.', 'gpt3-ai-content-generator')));
                        return;
                    }
                }

                if (isset($_POST['field']) && $_POST['field'] === 'wpaicg_editor_button_menus') {
                    $editor_button_menus = maybe_unserialize(get_option('wpaicg_editor_button_menus', array()));
                    $new_menus = json_decode(stripslashes($_POST['value']), true);
                
                    if (is_array($new_menus)) {
                        update_option('wpaicg_editor_button_menus', $new_menus);
                        wp_send_json_success(array('message' => esc_html__('Editor button menus updated successfully.', 'gpt3-ai-content-generator')));
                    } else {
                        wp_send_json_error(array('message' => esc_html__('Invalid data for editor button menus.', 'gpt3-ai-content-generator')));
                    }
                }

                // List of fields not to process dynamically (non-option list)
                $non_option_fields = array(
                    'wpai_language', 'wpai_writing_style', 'wpai_writing_tone', 'wpai_number_of_heading',
                    'wpai_heading_tag', 'wpai_modify_headings', 'wpai_add_tagline', 'wpai_add_keywords_bold',
                    'wpai_add_faq', 'wpai_add_intro', 'wpai_add_conclusion','wpai_cta_pos','img_size'
                );
        
                if (in_array($field, $non_option_fields)) {
                    // Save to the settings table
                    $wpdb->update(
                        "{$wpdb->prefix}wpaicg",
                        array($field => $value),
                        array('id' => 1)
                    );
                    wp_send_json_success(array('message' => esc_html__('Settings updated successfully.', 'gpt3-ai-content-generator')));
                } else {
                    // Save to the options table for everything else
                    update_option($field, $value);
                    wp_send_json_success(array('message' => esc_html__('Settings updated successfully.', 'gpt3-ai-content-generator')));
                }
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }
            
        public function aipower_save_google_safety_settings()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            if (isset($_POST['settings']) && is_array($_POST['settings'])) {
                $settings = array();
                foreach ($_POST['settings'] as $category => $threshold) {
                    $sanitized_category = sanitize_text_field($category);
                    $sanitized_threshold = sanitize_text_field($threshold);
                    $settings[] = array(
                        'category' => $sanitized_category,
                        'threshold' => $sanitized_threshold
                    );
                }
                update_option('wpaicg_google_safety_settings', $settings);

                wp_send_json_success(array('message' => esc_html__('Google safety settings updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }

        public function aipower_save_advanced_setting()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            if (isset($_POST['option_name']) && isset($_POST['option_value'])) {
                $option_name = sanitize_text_field($_POST['option_name']);
                $option_value = sanitize_text_field($_POST['option_value']);

                global $wpdb;
                if ($option_name === 'wpaicg_sleep_time') {
                    // Stored in options table
                    update_option($option_name, $option_value);
                } else {
                    // Stored in wpaicg table
                    $allowed_fields = array(
                        'max_tokens'        => 'max_tokens',
                        'temperature'       => 'temperature',
                        'top_p'             => 'top_p',
                        'frequency'         => 'frequency_penalty',
                        'presence'          => 'presence_penalty'
                    );

                    // Map the field names
                    $field_name = str_replace('wpaicg_', '', $option_name);
                    if (array_key_exists($field_name, $allowed_fields)) {
                        $db_field_name = $allowed_fields[$field_name];
                        $updated = $wpdb->update(
                            "{$wpdb->prefix}wpaicg",
                            array($db_field_name => $option_value),
                            array('id' => 1)
                        );

                        if ($updated === false) {
                            wp_send_json_error(array('message' => esc_html__('Failed to update the setting.', 'gpt3-ai-content-generator')));
                        }
                    } else {
                        wp_send_json_error(array('message' => esc_html__('Invalid setting.', 'gpt3-ai-content-generator')));
                    }
                }

                wp_send_json_success(array('message' => esc_html__('Setting updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }

        public function aipower_save_replicate_field() {
            // check permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error([
                    'message' => esc_html__('you do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')
                ]);
                return;
            }
        
            // verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error([
                    'message' => esc_html__('nonce verification failed.', 'gpt3-ai-content-generator')
                ]);
                return;
            }
        
            // sanitize inputs
            $model_name = sanitize_text_field($_POST['model_name']);
            $field_key = sanitize_text_field($_POST['field_key']);
            $field_value = $_POST['field_value']; // keep raw to handle all value types
        
            // get replicate models
            $replicate_models = get_option('wpaicg_replicate_models', []);
            $found = false;
        
            // validate field against schema
            foreach ($replicate_models as $owner => &$model_group) {
                foreach ($model_group as &$model) {
                    if ($model['name'] === $model_name) {
        
                        if (isset($model['schema']['Input']['properties'][$field_key])) {
                            $field_schema = $model['schema']['Input']['properties'][$field_key];
        
                            // validate type
                            if (isset($field_schema['type'])) {
                                switch ($field_schema['type']) {
                                    case 'integer':
                                        if (!is_numeric($field_value) || intval($field_value) != $field_value) {
                                            wp_send_json_error(['message' => __('invalid value type: must be an integer.', 'gpt3-ai-content-generator')]);
                                        }
                                        $field_value = intval($field_value);
                                        break;
        
                                    case 'number':
                                        if (!is_numeric($field_value)) {
                                            wp_send_json_error(['message' => __('invalid value type: must be a number.', 'gpt3-ai-content-generator')]);
                                        }
                                        $field_value = floatval($field_value);
                                        break;
        
                                    case 'boolean':
                                        if (!in_array($field_value, [0, 1, '0', '1', true, false], true)) {
                                            wp_send_json_error(['message' => __('invalid value type: must be boolean.', 'gpt3-ai-content-generator')]);
                                        }
                                        $field_value = (bool) $field_value;
                                        break;
        
                                    case 'string':
                                        $field_value = sanitize_text_field($field_value);
                                        if (isset($field_schema['enum']) && !in_array($field_value, $field_schema['enum'], true)) {
                                            wp_send_json_error(['message' => __('invalid value: must be one of the allowed options.', 'gpt3-ai-content-generator')]);
                                        }
                                        break;
        
                                    default:
                                        wp_send_json_error(['message' => __('unsupported field type.', 'gpt3-ai-content-generator')]);
                                }
                            }
        
                            // validate range
                            if (isset($field_schema['minimum']) && $field_value < $field_schema['minimum']) {
                                wp_send_json_error(['message' => __('value is below the minimum allowed.', 'gpt3-ai-content-generator')]);
                            }
                            if (isset($field_schema['maximum']) && $field_value > $field_schema['maximum']) {
                                wp_send_json_error(['message' => __('value exceeds the maximum allowed.', 'gpt3-ai-content-generator')]);
                            }
        
                            // all validations passed: save the value
                            $model['schema']['Input']['properties'][$field_key]['default'] = $field_value;
                            $found = true;
                            break 2; // exit both loops
                        } else {
                            error_log("field key '{$field_key}' not found in schema.");
                        }
                    }
                }
            }
        
            if ($found) {
                // update the option with the new value
                update_option('wpaicg_replicate_models', $replicate_models);
                wp_send_json_success(['message' => wp_kses(__("{$field_key} updated successfully.", 'gpt3-ai-content-generator'), [])]);
            } else {
                wp_send_json_error(['message' => esc_html__('Field or model not found in the schema.', 'gpt3-ai-content-generator')]);
            }
        }

        public function aipower_save_azure_field() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            if (isset($_POST['option_name']) && isset($_POST['option_value'])) {
                $option_name = sanitize_text_field($_POST['option_name']);
                $option_value = sanitize_text_field($_POST['option_value']);
        
                update_option($option_name, $option_value);
        
                wp_send_json_success(array('message' => esc_html__('Azure settings updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }
        
        // Function to save the selected Google model
        public function aipower_save_google_model()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            // Check if model parameter is sent
            if (isset($_POST['model'])) {
                $model = sanitize_text_field($_POST['model']);
                update_option('wpaicg_google_default_model', $model);  // Save the key of the selected model

                // Send a success response
                wp_send_json_success(array('message' => esc_html__('Google model updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }

        // Function to update the wpaicg_provider option with the selected AI engine
        public function aipower_save_ai_engine()
        {
            // Check user capability (admin rights) and nonce for security
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            // Check if engine parameter is sent
            if (isset($_POST['engine'])) {
                $engine = sanitize_text_field($_POST['engine']);
                update_option('wpaicg_provider', $engine);  // Update the wpaicg_provider option

                // Send a success response
                wp_send_json_success(array('message' => esc_html__('AI engine updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }

        // Function to save API key based on the selected provider
        public function aipower_save_api_key()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            if (isset($_POST['engine']) && isset($_POST['api_key'])) {
                $engine = sanitize_text_field($_POST['engine']);
                $api_key = sanitize_text_field($_POST['api_key']); // Ensure the full API key is used
                global $wpdb;

                // Save the API key based on the provider
                switch ($engine) {
                    case 'OpenAI':
                        $updated = $wpdb->update("{$wpdb->prefix}wpaicg", array('api_key' => $api_key), array('id' => 1));
                        break;
                    case 'OpenRouter':
                        update_option('wpaicg_openrouter_api_key', $api_key);
                        $updated = true;
                        break;
                    case 'Google':
                        update_option('wpaicg_google_model_api_key', $api_key);
                        $updated = true;
                        break;
                    case 'Azure':
                        update_option('wpaicg_azure_api_key', $api_key);
                        $updated = true;
                        break;
                    default:
                        $updated = false;
                        break;
                }

                // Send a success response
                if ($updated) {
                    wp_send_json_success(array('message' => esc_html__('API key updated successfully.', 'gpt3-ai-content-generator')));
                } else {
                    wp_send_json_error(array('message' => esc_html__('Failed to update the API key.', 'gpt3-ai-content-generator')));
                }
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }

        // Function to save the selected OpenAI model
        public function aipower_save_openai_model()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            // Check if model parameter is sent
            if (isset($_POST['model'])) {
                $model = sanitize_textarea_field($_POST['model']); // Allows more characters
                update_option('wpaicg_ai_model', $model);
                wp_send_json_success(array('message' => esc_html__('OpenAI model updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }

        // Function to save the selected OpenRouter model
        public function aipower_save_openrouter_model()
        {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            // Check if model parameter is sent
            if (isset($_POST['model'])) {
                $model = sanitize_text_field($_POST['model']);
                update_option('wpaicg_openrouter_default_model', $model);  // Save the key of the selected model

                // Send a success response
                wp_send_json_success(array('message' => esc_html__('OpenRouter model updated successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Invalid request.', 'gpt3-ai-content-generator')));
            }
        }
        
        public function aipower_update_module_settings() {
            check_ajax_referer('wpaicg_save_ai_engine_nonce');
        
            $module_key = isset($_POST['module_key']) ? sanitize_text_field($_POST['module_key']) : '';
            $enabled = isset($_POST['enabled']) ? intval($_POST['enabled']) : 0;
        
            if (!$module_key) {
                wp_send_json_error(array('message' => 'Invalid module key.'));
            }
        
            $module_settings = get_option('wpaicg_module_settings');
            if ($module_settings === false) {
                // Initialize with default values
                $module_settings = array(
                    'content_writer' => true,
                    'autogpt' => true,
                    'ai_forms' => true,
                    'promptbase' => true,
                    'image_generator' => true,
                    'training' => true,
                    'chat_bot' => true, // Include 'chat_bot' here if it's not in $available_modules
                );
            }
        
            $module_settings[$module_key] = ($enabled == 1);
        
            update_option('wpaicg_module_settings', $module_settings);
        
            wp_send_json_success(array(
                'message' => 'Module settings updated successfully.',
                'module_settings' => $module_settings, // Return the updated module settings
            ));
        }

        /**
         * Initializes the wpaicg table and inserts default settings if necessary.
         */
        public function aipower_initialize_settings_table() {
            global $wpdb;
            $table_name = $wpdb->prefix . 'wpaicg';
            $charset_collate = $wpdb->get_charset_collate();

            // Check if the table exists
            if ( $wpdb->get_var( "SHOW TABLES LIKE '{$table_name}'" ) !== $table_name ) {
                // Table does not exist, create it
                $this->create_wpaicg_table($table_name, $charset_collate);
            } else {
                // Table exists, check if it's empty
                $existing = $wpdb->get_var( "SELECT COUNT(*) FROM {$table_name}" );

                if ( $existing == 0 ) {
                    // Table exists but is empty, drop and recreate it
                    $wpdb->query("DROP TABLE IF EXISTS {$table_name}");
                    $this->create_wpaicg_table($table_name, $charset_collate);
                }
            }
        }

        /**
         * Creates the wpaicg table with default structure and inserts default values.
         */
        private function create_wpaicg_table($table_name, $charset_collate) {
            global $wpdb;

            // Create the table
            $sql = "CREATE TABLE {$table_name} (
                ID mediumint(11) NOT NULL AUTO_INCREMENT,
                name text NOT NULL,
                temperature float NOT NULL,
                max_tokens float NOT NULL,
                top_p float NOT NULL,
                best_of float NOT NULL,
                frequency_penalty float NOT NULL,
                presence_penalty float NOT NULL,
                img_size text NOT NULL,
                api_key text NOT NULL,
                wpai_language VARCHAR(255) NOT NULL,
                wpai_add_img BOOLEAN NOT NULL,
                wpai_add_intro BOOLEAN NOT NULL,
                wpai_add_conclusion BOOLEAN NOT NULL,
                wpai_add_tagline BOOLEAN NOT NULL,
                wpai_add_faq BOOLEAN NOT NULL,
                wpai_add_keywords_bold BOOLEAN NOT NULL,
                wpai_number_of_heading INT NOT NULL,
                wpai_modify_headings BOOLEAN NOT NULL,
                wpai_heading_tag VARCHAR(10) NOT NULL,
                wpai_writing_style VARCHAR(255) NOT NULL,
                wpai_writing_tone VARCHAR(255) NOT NULL,
                wpai_target_url VARCHAR(255) NOT NULL,
                wpai_target_url_cta VARCHAR(255) NOT NULL,
                wpai_cta_pos VARCHAR(255) NOT NULL,
                added_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                modified_date datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                PRIMARY KEY  (ID)
            ) {$charset_collate};";

            require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
            dbDelta( $sql );

            // Insert default settings
            $defaultData = [
                'name'                   => 'wpaicg_settings',
                'temperature'            => '1',
                'max_tokens'             => '1500',
                'top_p'                  => '0.01',
                'best_of'                => '1',
                'frequency_penalty'      => '0.01',
                'presence_penalty'       => '0.01',
                'img_size'               => '1024x1024',
                'api_key'                => 'sk..',
                'wpai_language'          => 'en',
                'wpai_add_img'           => 1,
                'wpai_add_intro'         => 'false',
                'wpai_add_conclusion'    => 'false',
                'wpai_add_tagline'       => 'false',
                'wpai_add_faq'           => 'false',
                'wpai_add_keywords_bold' => 'false',
                'wpai_number_of_heading' => 3,
                'wpai_modify_headings'   => 'false',
                'wpai_heading_tag'       => 'h1',
                'wpai_writing_style'     => 'infor',
                'wpai_writing_tone'      => 'formal',
                'wpai_cta_pos'           => 'beg',
                'added_date'             => current_time( 'mysql' ),
                'modified_date'          => current_time( 'mysql' ),
            ];

            if ( $wpdb->insert( $table_name, $defaultData ) === false ) {
                error_log( 'wpaicg: Failed to insert default settings.' );
            }
        }
        public function aipower_duplicate_chatbot() {
            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Retrieve the chatbot ID
            if (!isset($_POST['chatbot_id']) || !is_numeric($_POST['chatbot_id'])) {
                wp_send_json_error(array('message' => esc_html__('Invalid chatbot ID.', 'gpt3-ai-content-generator')));
                return;
            }
        
            $chatbot_id = intval($_POST['chatbot_id']);
        
            // Get the original chatbot post
            $original_chatbot = get_post($chatbot_id);
            if (!$original_chatbot || $original_chatbot->post_type !== 'wpaicg_chatbot') {
                wp_send_json_error(array('message' => esc_html__('Chatbot not found.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Get the original title and content
            $original_title = $original_chatbot->post_title;
            $original_content = $original_chatbot->post_content;
        
            // Decode the post_content JSON
            $chatbot_data = json_decode($original_content, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => esc_html__('Invalid chatbot data.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Append ' - Duplicated' to the title and name
            $new_title = $original_title . ' - ' . esc_html__('Duplicated', 'gpt3-ai-content-generator');
            $chatbot_data['name'] = $new_title;
        
            // Re-encode the post_content JSON
            $new_content = wp_json_encode($chatbot_data, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => esc_html__('Failed to encode duplicated chatbot data.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Create the duplicated chatbot
            $new_chatbot = array(
                'post_title'   => $new_title,
                'post_content' => $new_content,
                'post_status'  => 'publish', // You can set this to 'draft' if preferred
                'post_type'    => 'wpaicg_chatbot',
            );
        
            $new_chatbot_id = wp_insert_post($new_chatbot);
        
            if (is_wp_error($new_chatbot_id)) {
                wp_send_json_error(array('message' => esc_html__('Failed to duplicate chatbot.', 'gpt3-ai-content-generator')));
                return;
            }
        
            // Optionally, copy meta fields if your chatbots have additional metadata
            // Example:
            // $meta_keys = get_post_meta($chatbot_id);
            // foreach ($meta_keys as $key => $values) {
            //     foreach ($values as $value) {
            //         update_post_meta($new_chatbot_id, $key, $value);
            //     }
            // }
        
            // Respond with success
            wp_send_json_success(array(
                'message' => esc_html__('Chatbot duplicated successfully.', 'gpt3-ai-content-generator'),
                'new_chatbot_id' => $new_chatbot_id
            ));
        }

        public function aipower_reset_settings() {
            // Verify nonce
            if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce' ) ) {
                wp_send_json_error( array( 'message' => __( 'Nonce verification failed.', 'gpt3-ai-content-generator' ) ) );
                return;
            }

            // Check user capabilities
            if ( ! current_user_can( 'manage_options' ) ) {
                wp_send_json_error( array( 'message' => __( 'You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator' ) ) );
                return;
            }

            // Delete the specified options
            $options_deleted = array();
            $options_to_delete = array( 'wpaicg_chat_widget', 'wpaicg_chat_shortcode_options' );

            foreach ( $options_to_delete as $option_name ) {
                if ( delete_option( $option_name ) ) {
                    $options_deleted[] = $option_name;
                } else {
                    // Option might not exist; log or handle as needed
                    $options_deleted[] = $option_name . ' (' . __( 'not found or already deleted', 'gpt3-ai-content-generator' ) . ')';
                }
            }

            // Optionally, reset default settings or perform additional cleanup
            // Example: Reset default widgets or shortcode settings to initial values
            // This depends on how your plugin initializes these settings

            // Prepare response message
            $deleted_count = count( $options_deleted );
            $message = sprintf( __( 'Reset completed. %d option(s) deleted.', 'gpt3-ai-content-generator' ), $deleted_count );

            wp_send_json_success( array( 'message' => $message ) );
        }
    }
    WPAICG_Dashboard::get_instance();
}