<?php

namespace WPAICG;

if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_Chat')) {
    class WPAICG_Chat
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
            add_shortcode( 'wpaicg_chatgpt', [ $this, 'wpaicg_chatbox' ] );
            add_shortcode( 'wpaicg_chatgpt_widget', [ $this, 'wpaicg_chatbox_widget' ] );
            add_action( 'wp_ajax_wpaicg_chatbox_message', array( $this, 'wpaicg_chatbox_message' ) );
            add_action( 'wp_ajax_nopriv_wpaicg_chatbox_message', array( $this, 'wpaicg_chatbox_message' ) );
            add_action( 'wp_ajax_wpaicg_chat_shortcode_message', array( $this, 'wpaicg_chatbox_message' ) );
            add_action( 'wp_ajax_nopriv_wpaicg_chat_shortcode_message', array( $this, 'wpaicg_chatbox_message' ) );
            if ( ! wp_next_scheduled( 'wpaicg_remove_chat_tokens_limited' ) ) {
                wp_schedule_event( time(), 'hourly', 'wpaicg_remove_chat_tokens_limited' );
            }
            add_action( 'wpaicg_remove_chat_tokens_limited', array( $this, 'wpaicg_remove_chat_tokens' ) );
            add_action( 'wp_ajax_wpaicg_submit_feedback', array( $this, 'wpaicg_submit_feedback' ) );
            add_action( 'wp_ajax_nopriv_wpaicg_submit_feedback', array( $this, 'wpaicg_submit_feedback' ) );
            add_action('wp_ajax_wpaicg_submit_lead', array($this, 'wpaicg_submit_lead'));
            add_action('wp_ajax_nopriv_wpaicg_submit_lead', array($this, 'wpaicg_submit_lead'));
        }

        public function wpaicg_submit_lead() {
            global $wpdb;
        
            // Verify the nonce
            check_admin_referer('wpaicg-chatbox', '_wpnonce');
        
            // Sanitize and retrieve data from the request
            $lead_name = isset($_POST['lead_name']) ? sanitize_text_field( wp_unslash($_POST['lead_name']) ) : '';
            $lead_email = isset($_POST['lead_email']) ? sanitize_email( wp_unslash($_POST['lead_email']) ) : '';
            $lead_phone = isset($_POST['lead_phone']) ? sanitize_text_field( wp_unslash($_POST['lead_phone']) ) : '';
            $chatId = isset($_POST['chatId']) ? sanitize_text_field( wp_unslash($_POST['chatId']) ) : '';            
            // remove wpaicg-chat-message-73714 the text from the chatId
            $chatId = str_replace('wpaicg-chat-message-', '', $chatId);
        
            // Ensure at least one field is provided
            if (empty($lead_name) && empty($lead_email) && empty($lead_phone)) {
                wp_send_json_error('No lead data provided.');
                exit;
            }
        
            if (empty($chatId)) {
                wp_send_json_error('Chat ID is missing.');
                exit;
            }
        
            // Retrieve the specific chat log entry that matches the chatId
            $log_entry = $wpdb->get_row(
                $wpdb->prepare(
                    "SELECT id, data FROM {$wpdb->prefix}wpaicg_chatlogs WHERE data LIKE %s",
                    '%' . $wpdb->esc_like($chatId) . '%'
                ),
                ARRAY_A
            );
        
            if ($log_entry) {
                $log_data = json_decode($log_entry['data'], true);
        
                // Iterate over the log data to find the entry with the matching chatId
                foreach ($log_data as &$entry) {
                    if (isset($entry['chatId']) && (string)$entry['chatId'] === (string)$chatId) {
                        // Check if lead_data already exists
                        if (!isset($entry['lead_data']) || !is_array($entry['lead_data'])) {
                            $entry['lead_data'] = [];
                        }
        
                        // Add or update the lead data
                        $entry['lead_data'] = array(
                            'name'  => $lead_name,
                            'email' => $lead_email,
                            'phone' => $lead_phone,
                        );
        
                        // Update the database with the new log data
                        $wpdb->update(
                            $wpdb->prefix . 'wpaicg_chatlogs',
                            array('data' => wp_json_encode($log_data)),
                            array('id' => $log_entry['id']),
                            array('%s'),
                            array('%d')
                        );
        
                        wp_send_json_success('Lead data submitted successfully.');
                        return;
                    }
                }
        
                // If not found, append a new entry to log_data
                $log_data[] = array(
                    'lead_data' => array(
                        'name'  => $lead_name,
                        'email' => $lead_email,
                        'phone' => $lead_phone,
                    ),
                    'chatId' => $chatId,
                );
        
                // Update the database with the new log data
                $wpdb->update(
                    $wpdb->prefix . 'wpaicg_chatlogs',
                    array('data' => wp_json_encode($log_data)),
                    array('id' => $log_entry['id']),
                    array('%s'),
                    array('%d')
                );
        
                wp_send_json_success('Lead data submitted successfully.');
                return;
            } else {
                wp_send_json_error('Chat log entry not found.');
                return;
            }
        }

        function wpaicg_submit_feedback() {
            global $wpdb;

            // Verify the nonce
            check_admin_referer('wpaicg-chatbox', '_wpnonce');

            // Unslash and sanitize data from the request
            $chatId = isset($_POST['chatId']) ? sanitize_text_field( wp_unslash($_POST['chatId']) ) : '';
            $feedbackType = isset($_POST['feedbackType']) ? sanitize_text_field( wp_unslash($_POST['feedbackType']) ) : '';
            $feedbackDetails = isset($_POST['feedbackDetails']) ? sanitize_textarea_field( wp_unslash($_POST['feedbackDetails']) ) : '';
        
            if (!empty($chatId) && !empty($feedbackType)) {
                // Retrieve the specific chat log entry that matches the chatId
                $log_entry = $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT id, data FROM {$wpdb->prefix}wpaicg_chatlogs WHERE data LIKE %s",
                        '%' . $wpdb->esc_like($chatId) . '%'
                    ),
                    ARRAY_A
                );
        
                if ($log_entry) {
                    $log_data = json_decode($log_entry['data'], true);
        
                    foreach ($log_data as &$entry) {
                        if (isset($entry['chatId']) && (string)$entry['chatId'] === (string)$chatId) {
                            // Check if userfeedback already exists
                            if (!isset($entry['userfeedback']) || !is_array($entry['userfeedback'])) {
                                $entry['userfeedback'] = [];
                            }
        
                            // Add the new feedback to the array, including both type and details
                            $entry['userfeedback'][] = array(
                                'type' => $feedbackType,
                                'details' => $feedbackDetails
                            );
        
                            // Update the database with the new log data
                            $wpdb->update(
                                $wpdb->prefix . 'wpaicg_chatlogs',
                                array('data' => wp_json_encode($log_data)),
                                array('id' => $log_entry['id']),
                                array('%s'),
                                array('%d')
                            );
        
                            wp_send_json_success('Feedback submitted successfully.');
                            return;
                        }
                    }
                }
        
                wp_send_json_error('Chat log entry not found.');
            } else {
                wp_send_json_error('Invalid input.');
            }
        }

        public function wpaicg_remove_chat_tokens()
        {
            global $wpdb;
            $wpaicg_chat_shortcode_options = get_option('wpaicg_chat_shortcode_options',[]);
            $wpaicg_chat_widget = get_option('wpaicg_chat_widget', []);
            $widget_reset_limit = isset($wpaicg_chat_widget['reset_limit']) && !empty($wpaicg_chat_widget['reset_limit']) ? $wpaicg_chat_widget['reset_limit'] : 0;
            $shortcode_reset_limit = isset($wpaicg_chat_shortcode_options['reset_limit']) && !empty($wpaicg_chat_shortcode_options['reset_limit']) ? $wpaicg_chat_shortcode_options['reset_limit'] : 0;
            if($widget_reset_limit > 0) {
                $widget_time = time() - ($widget_reset_limit * 86400);
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wpaicg_chattokens WHERE source='widget' AND created_at < %s",$widget_time));
            }
            if($shortcode_reset_limit > 0) {
                $shortcode_time = time() - ($shortcode_reset_limit * 86400);
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wpaicg_chattokens WHERE source='shortcode' AND created_at < %s",$shortcode_time));
            }
            // New code to handle custom bots
            $custom_bots = $wpdb->get_results("SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type = 'wpaicg_chatbot'");
            foreach ($custom_bots as $bot) {
                $content = json_decode($bot->post_content, true);
                $reset_limit = isset($content['reset_limit']) ? (int) $content['reset_limit'] : 0;
                if ($reset_limit > 0) {
                    $bot_type = isset($content['type']) && in_array(strtolower($content['type']), ['widget', 'shortcode']) ? ucfirst($content['type']) : '';
                    $source_key = $bot_type . ' ID: ' . $bot->ID;
                    $time_threshold = time() - ($reset_limit * 86400);
                    $wpdb->query($wpdb->prepare(
                        "DELETE FROM {$wpdb->prefix}wpaicg_chattokens WHERE source = %s AND created_at < %s",
                        $source_key, $time_threshold
                    ));
                }
            }
        }

        public function wpaicg_chatbox_message()
        {
            $wpaicg_result = [
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong', 'gpt3-ai-content-generator'),
            ];
        
            // Verify the nonce
            check_admin_referer('wpaicg-chatbox', '_wpnonce');
        
            global $wpdb;
    
            // Use $_POST instead of $_REQUEST for better clarity and security
            if (isset($_POST['wpaicg_chat_client_id']) && !empty($_POST['wpaicg_chat_client_id'])) {
                // Remove slashes and sanitize the client ID
                $wpaicg_client_id = sanitize_text_field(wp_unslash($_POST['wpaicg_chat_client_id']));
            } else {
                // Optionally, handle the absence gracefully without logging or exiting
                // For example, set a default client ID or leave it as null
                // $wpaicg_client_id = 'default_client_id';
                // Or simply continue without setting it
            }

            // Get the default provider option
            $default_provider = get_option('wpaicg_provider', 'OpenAI');
            $wpaicg_provider = $default_provider;
            $wpaicg_use_internet = 0;
            $confidence_score_threshold = 20; // Default value

            // Check for bot_id first
            if (isset($_POST['bot_id']) && intval($_POST['bot_id']) > 0) {
                $bot_id = intval($_POST['bot_id']);
                $post = get_post($bot_id);
                if ($post) {
                    $post_content = $post->post_content;
                    $post_content_json = json_decode($post_content, true);
                    $wpaicg_provider = isset($post_content_json['provider']) && !empty($post_content_json['provider']) ? sanitize_text_field($post_content_json['provider']) : $default_provider;
                    $wpaicg_use_internet = isset($post_content_json['internet_browsing']) && $post_content_json['internet_browsing'] ? 1 : 0;
                    // Set confidence score for custom bot
                    $confidence_score_threshold = isset($post_content_json['confidence_score']) ? intval($post_content_json['confidence_score']) : 20;
                }
            } elseif (isset($_POST['chatbot_identity'])) {
                $chatbot_identity = sanitize_text_field(wp_unslash($_POST['chatbot_identity']));
                if ($chatbot_identity === 'shortcode') {
                    $shortcode_options = get_option('wpaicg_chat_shortcode_options');
                    $wpaicg_provider = isset($shortcode_options['provider']) ? sanitize_text_field($shortcode_options['provider']) : $default_provider;
                    $wpaicg_use_internet = isset($shortcode_options['internet_browsing']) && $shortcode_options['internet_browsing'] ? 1 : 0;
                    // Set confidence score for shortcode
                    $confidence_score_threshold = isset($shortcode_options['confidence_score']) ? intval($shortcode_options['confidence_score']) : 20;
                } elseif ($chatbot_identity === 'widget') {
                    $widget_options = get_option('wpaicg_chat_widget');
                    $wpaicg_use_internet = isset($widget_options['internet_browsing']) && $widget_options['internet_browsing'] ? 1 : 0;
                    if (isset($_POST['wpaicg_chat_widget']['provider']) && !empty($_POST['wpaicg_chat_widget']['provider'])) {
                        $wpaicg_provider = sanitize_text_field(wp_unslash($_POST['wpaicg_chat_widget']['provider']));
                    } else {
                        $widget_options = get_option('wpaicg_chat_widget');
                        $wpaicg_provider = isset($widget_options['provider']) ? sanitize_text_field($widget_options['provider']) : $default_provider;
                    }
                    // Set confidence score for widget
                    $confidence_score_threshold = isset($widget_options['confidence_score']) ? intval($widget_options['confidence_score']) : 20;
                } else {
                    // Handle other custom chatbot identities if needed
                    $wpaicg_provider = $default_provider;
                }
            } else {
                // Fallback to default provider if no specific identity or bot_id is found
                $wpaicg_provider = $default_provider;
            }

            $open_ai = WPAICG_OpenAI::get_instance()->openai();

            // Determine AI engine based on provider
            switch ($wpaicg_provider) {
                case 'Google':
                    $open_ai = WPAICG_Google::get_instance();
                    break;
                case 'Azure':
                    $open_ai = WPAICG_AzureAI::get_instance()->azureai();
                    break;
                case 'OpenAI':
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    break;
                case 'OpenRouter':
                    $open_ai = WPAICG_OpenRouter::get_instance()->openai();
                    break;
                default:
                    $open_ai = WPAICG_OpenAI::get_instance()->openai();
                    break;
            }
        
            if (!$open_ai) {
                $wpaicg_result['msg'] = esc_html__('Unable to initialize the AI instance. Please make sure the API key is valid.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
                exit;
            }

            $wpaicg_save_request = false;


            $url = sanitize_text_field(wp_unslash($_POST['url'] ?? ''));

            $wpaicg_pinecone_api = get_option('wpaicg_pinecone_api', '');
            $wpaicg_pinecone_environment = get_option('wpaicg_pinecone_environment', '');
            $wpaicg_total_tokens = 0;
            $wpaicg_limited_tokens = false;
            $wpaicg_token_usage_client = 0;
            $wpaicg_token_limit_message = esc_html__('You have reached your token limit.','gpt3-ai-content-generator');
            $wpaicg_limited_tokens_number = 0;
            $wpaicg_chat_source = 'widget';
            $wpaicg_chat_temperature = get_option('wpaicg_chat_temperature',$open_ai->temperature);
            $wpaicg_chat_max_tokens = get_option('wpaicg_chat_max_tokens',$open_ai->max_tokens);
            $wpaicg_chat_top_p = get_option('wpaicg_chat_top_p',$open_ai->top_p);
            $wpaicg_chat_best_of = get_option('wpaicg_chat_best_of',$open_ai->best_of);
            $wpaicg_chat_frequency_penalty = get_option('wpaicg_chat_frequency_penalty',$open_ai->frequency_penalty);
            $wpaicg_chat_presence_penalty = get_option('wpaicg_chat_presence_penalty',$open_ai->presence_penalty);
            if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'wpaicg_chat_shortcode_message') {
                $wpaicg_chat_source = 'shortcode';
            }

            $use_default_embedding = true;
            $selected_embedding_model = '';
            $selected_embedding_provider = '';

            $wpaicg_moderation = false;
            $wpaicg_moderation_model = 'text-moderation-latest';
            $wpaicg_moderation_notice = esc_html__('Your message has been flagged as potentially harmful or inappropriate. Please ensure that your messages are respectful and do not contain language or content that could be offensive or harmful to others. Thank you for your cooperation.','gpt3-ai-content-generator');
            if(isset($_REQUEST['action']) && $_REQUEST['action'] == 'wpaicg_chat_shortcode_message'){
                $existingValue = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}wpaicg WHERE name = %s", 'wpaicg_settings' ), ARRAY_A );
                $wpaicg_chat_shortcode_options = get_option('wpaicg_chat_shortcode_options',[]);
                $default_setting = array(
                    'provider' => 'OpenAI',
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
                    'conversation_cut' => 10,
                    'content_aware' => 'yes',
                    'embedding' =>  false,
                    'embedding_type' =>  false,
                    'embedding_top' =>  false,
                    'embedding_index' => '',
                    'no_answer' => '',
                    'fontsize' => 13,
                    'fontcolor' => '#495057',
                    'user_bg_color' => '#ccf5e1',
                    'ai_bg_color' => '#d1e8ff',
                    'ai_icon_url' => '',
                    'ai_icon' => 'default',
                    'save_logs' => false,
                    'chat_addition' => false,
                    'chat_addition_text' => '',
                    'user_aware' => 'no',
                    'user_limited' => false,
                    'guest_limited' => false,
                    'user_tokens' => 0,
                    'limited_message'=> esc_html__('You have reached your token limit.','gpt3-ai-content-generator'),
                    'guest_tokens' => 0,
                    'moderation' => false,
                    'moderation_model' => 'text-moderation-latest',
                    'moderation_notice' => esc_html__('Your message has been flagged as potentially harmful or inappropriate. Please ensure that your messages are respectful and do not contain language or content that could be offensive or harmful to others. Thank you for your cooperation.','gpt3-ai-content-generator'),
                    'role_limited' => false,
                    'limited_roles' => [],
                    'log_request' => false,
                    'vectordb' => 'pinecone',
                    'qdrant_collection' => '',
                    'use_default_embedding' => true,
                    'embedding_model' => '',
                    'embedding_provider' => '',
                );
                $wpaicg_settings = shortcode_atts($default_setting, $wpaicg_chat_shortcode_options);
                $wpaicg_save_request = isset($wpaicg_settings['log_request']) && $wpaicg_settings['log_request'] ? true : false;
                $wpaicg_chat_embedding = isset($wpaicg_settings['embedding']) && $wpaicg_settings['embedding'] ? true : false;
                $wpaicg_chat_embedding_type = isset($wpaicg_settings['embedding_type']) ? $wpaicg_settings['embedding_type'] : '' ;
                $wpaicg_chat_no_answer = isset($wpaicg_settings['no_answer']) ? $wpaicg_settings['no_answer'] : '' ;
                $wpaicg_chat_embedding_top = isset($wpaicg_settings['embedding_top']) ? $wpaicg_settings['embedding_top'] : 1 ;
                $wpaicg_chat_no_answer = empty($wpaicg_chat_no_answer) ? 'I dont know' : $wpaicg_chat_no_answer;
                $wpaicg_chat_with_embedding = false;
                $wpaicg_chat_language = isset($wpaicg_settings['language']) ? $wpaicg_settings['language'] : 'en' ;
                $wpaicg_chat_tone = isset($wpaicg_settings['tone']) ? $wpaicg_settings['tone'] : 'friendly' ;
                $wpaicg_chat_proffesion = isset($wpaicg_settings['profession']) ? $wpaicg_settings['profession'] : 'none' ;
                $wpaicg_chat_remember_conversation = isset($wpaicg_settings['remember_conversation']) ? $wpaicg_settings['remember_conversation'] : 'yes' ;
                $wpaicg_chat_content_aware = isset($wpaicg_settings['content_aware']) ? $wpaicg_settings['content_aware'] : 'yes' ;
                $wpaicg_chat_vectordb = isset($wpaicg_settings['vectordb']) ? $wpaicg_settings['vectordb'] : 'pinecone' ;
                $wpaicg_chat_qdrant_collection = isset($wpaicg_settings['qdrant_collection']) ? $wpaicg_settings['qdrant_collection'] : '' ;

                $use_default_embedding = isset($wpaicg_settings['use_default_embedding']) ? $wpaicg_settings['use_default_embedding'] : true;
                $selected_embedding_model = isset($wpaicg_settings['embedding_model']) ? $wpaicg_settings['embedding_model'] : "";
                $selected_embedding_provider = isset($wpaicg_settings['embedding_provider']) ? $wpaicg_settings['embedding_provider'] : "";

                $wpaicg_ai_model = isset($wpaicg_settings['model']) ? $wpaicg_settings['model'] : 'gpt-4o-mini' ;

                // if OpenAI or OpenRouter
                if ($wpaicg_provider === 'OpenAI' || $wpaicg_provider === 'OpenRouter') {
                    $wpaicg_ai_model = isset($wpaicg_settings['model']) ? $wpaicg_settings['model'] : 'gpt-4o-mini';
                } elseif ($wpaicg_provider === 'Azure') {
                    $wpaicg_ai_model = get_option('wpaicg_azure_deployment', ''); 
                }  elseif ($wpaicg_provider === 'Google') {
                    if (isset($wpaicg_settings['provider']) && $wpaicg_settings['provider'] === 'Google') {
                        $wpaicg_ai_model = isset($wpaicg_settings['model']) ? $wpaicg_settings['model'] : get_option('wpaicg_shortcode_google_model', 'gemini-pro');
                    } else {
                        $wpaicg_ai_model = get_option('wpaicg_shortcode_google_model', 'gemini-pro');
                    }
                } else {
                    // Handle other providers or set a default value
                    $wpaicg_ai_model = 'gpt-4o-mini';
                }

                $wpaicg_save_logs = isset($wpaicg_settings['save_logs']) && $wpaicg_settings['save_logs'] ? true : false;
                $wpaicg_chat_addition = isset($wpaicg_settings['chat_addition']) && $wpaicg_settings['chat_addition'] ? true : false;
                $wpaicg_chat_addition_text = isset($wpaicg_settings['chat_addition_text']) && !empty($wpaicg_settings['chat_addition_text']) ? $wpaicg_settings['chat_addition_text'] : '';
                $wpaicg_user_aware = isset($wpaicg_settings['user_aware']) ? $wpaicg_settings['user_aware'] : 'no';
                $wpaicg_token_limit_message = isset($wpaicg_settings['limited_message']) ? $wpaicg_settings['limited_message'] : $wpaicg_token_limit_message;
                $wpaicg_chat_temperature = isset($wpaicg_settings['temperature']) && !empty($wpaicg_settings['temperature']) ? $wpaicg_settings['temperature'] :$wpaicg_chat_temperature;
                $wpaicg_chat_max_tokens = isset($wpaicg_settings['max_tokens']) && !empty($wpaicg_settings['max_tokens']) ? $wpaicg_settings['max_tokens'] :$wpaicg_chat_max_tokens;
                $wpaicg_chat_top_p = isset($wpaicg_settings['top_p']) && !empty($wpaicg_settings['top_p']) ? $wpaicg_settings['top_p'] :$wpaicg_chat_top_p;
                $wpaicg_chat_best_of = isset($wpaicg_settings['best_of']) && !empty($wpaicg_settings['best_of']) ? $wpaicg_settings['best_of'] :$wpaicg_chat_best_of;
                $wpaicg_chat_frequency_penalty = isset($wpaicg_settings['frequency_penalty']) && !empty($wpaicg_settings['frequency_penalty']) ? $wpaicg_settings['frequency_penalty'] :$wpaicg_chat_frequency_penalty;
                $wpaicg_chat_presence_penalty = isset($wpaicg_settings['presence_penalty']) && !empty($wpaicg_settings['presence_penalty']) ? $wpaicg_settings['presence_penalty'] :$wpaicg_chat_presence_penalty;
                if(isset($wpaicg_settings['embedding_index']) && !empty($wpaicg_settings['embedding_index'])){
                    $wpaicg_pinecone_environment = $wpaicg_settings['embedding_index'];
                }
                if(is_user_logged_in() && $wpaicg_settings['user_limited'] && $wpaicg_settings['user_tokens'] > 0){
                    $wpaicg_limited_tokens = true;
                    $wpaicg_limited_tokens_number = $wpaicg_settings['user_tokens'];
                }
                /*Check limit base role*/
                if(is_user_logged_in() && isset($wpaicg_settings['role_limited']) && $wpaicg_settings['role_limited']){
                    $wpaicg_roles = ( array )wp_get_current_user()->roles;
                    $limited_current_role = 0;
                    foreach ($wpaicg_roles as $wpaicg_role) {
                        if(
                            isset($wpaicg_settings['limited_roles'])
                            && is_array($wpaicg_settings['limited_roles'])
                            && isset($wpaicg_settings['limited_roles'][$wpaicg_role])
                            && $wpaicg_settings['limited_roles'][$wpaicg_role] > $limited_current_role
                        ){
                            $limited_current_role = $wpaicg_settings['limited_roles'][$wpaicg_role];
                        }
                    }
                    if($limited_current_role > 0){
                        $wpaicg_limited_tokens = true;
                        $wpaicg_limited_tokens_number = $limited_current_role;
                    }
                    else{
                        $wpaicg_limited_tokens = false;
                    }
                }
                /*End check limit base role*/
                if(!is_user_logged_in() && $wpaicg_settings['guest_limited'] && $wpaicg_settings['guest_tokens'] > 0){
                    $wpaicg_limited_tokens = true;
                    $wpaicg_limited_tokens_number = $wpaicg_settings['guest_tokens'];
                }
                if(wpaicg_util_core()->wpaicg_is_pro()) {
                    $wpaicg_chat_pro = WPAICG_Chat_Pro::get_instance();
                    $wpaicg_moderation = $wpaicg_chat_pro->activated($wpaicg_settings);
                    $wpaicg_moderation_model = $wpaicg_chat_pro->model($wpaicg_settings);
                    $wpaicg_moderation_notice = $wpaicg_chat_pro->notice($wpaicg_settings);
                }
            }
            else {
                $wpaicg_limited_tokens = false;
                $wpaicg_chat_widget = get_option('wpaicg_chat_widget', []);
                $wpaicg_chat_embedding = get_option('wpaicg_chat_embedding', false);
                $wpaicg_chat_embedding_type = get_option('wpaicg_chat_embedding_type', false);
                $wpaicg_chat_no_answer = get_option('wpaicg_chat_no_answer', '');
                $wpaicg_chat_embedding_top = get_option('wpaicg_chat_embedding_top', 1);
                $wpaicg_chat_qdrant_collection = get_option('wpaicg_widget_qdrant_collection', '');
                $wpaicg_chat_vectordb = get_option('wpaicg_chat_vectordb', 'pinecone');
                $wpaicg_chat_no_answer = empty($wpaicg_chat_no_answer) ? 'I dont know' : $wpaicg_chat_no_answer;
                $wpaicg_chat_with_embedding = false;
                $wpaicg_chat_language = get_option('wpaicg_chat_language', 'en');
                $wpaicg_chat_tone = isset($wpaicg_chat_widget['tone']) && !empty($wpaicg_chat_widget['tone']) ? $wpaicg_chat_widget['tone'] : 'friendly';
                $wpaicg_chat_proffesion = isset($wpaicg_chat_widget['proffesion']) && !empty($wpaicg_chat_widget['proffesion']) ? $wpaicg_chat_widget['proffesion'] : 'none';
                $wpaicg_chat_remember_conversation = isset($wpaicg_chat_widget['remember_conversation']) && !empty($wpaicg_chat_widget['remember_conversation']) ? $wpaicg_chat_widget['remember_conversation'] : 'yes';
                $wpaicg_chat_content_aware = isset($wpaicg_chat_widget['content_aware']) && !empty($wpaicg_chat_widget['content_aware']) ? $wpaicg_chat_widget['content_aware'] : 'yes';
                if ($wpaicg_provider === 'Azure') {
                    $wpaicg_ai_model = get_option('wpaicg_azure_deployment');
                }  elseif ($wpaicg_provider === 'Google') {
                    $wpaicg_ai_model = get_option('wpaicg_widget_google_model', 'gemini-pro'); 
                } elseif ($wpaicg_provider === 'OpenRouter') {
                    $wpaicg_ai_model = get_option('wpaicg_widget_openrouter_model', 'openrouter/auto'); 
                } else {
                    $wpaicg_ai_model = get_option('wpaicg_chat_model', 'gpt-4o-mini');
                }                    
                $wpaicg_save_logs = isset($wpaicg_chat_widget['save_logs']) && $wpaicg_chat_widget['save_logs'] ? true : false;
                $wpaicg_chat_addition = get_option('wpaicg_chat_addition',false);
                $wpaicg_chat_addition_text = get_option('wpaicg_chat_addition_text','');
                $wpaicg_user_aware = isset($wpaicg_chat_widget['user_aware']) ? $wpaicg_chat_widget['user_aware'] : 'no';
                $wpaicg_token_limit_message = isset($wpaicg_chat_widget['limited_message']) ? $wpaicg_chat_widget['limited_message'] : $wpaicg_token_limit_message;
                $wpaicg_save_request = isset($wpaicg_chat_widget['log_request']) && $wpaicg_chat_widget['log_request'] ? true : false;
                if(is_user_logged_in() && isset($wpaicg_chat_widget['user_limited']) && $wpaicg_chat_widget['user_limited'] && $wpaicg_chat_widget['user_tokens'] > 0){
                    $wpaicg_limited_tokens = true;
                    $wpaicg_limited_tokens_number = $wpaicg_chat_widget['user_tokens'];
                }
                /*Check limit base role*/
                if(is_user_logged_in() && isset($wpaicg_chat_widget['role_limited']) && $wpaicg_chat_widget['role_limited']){
                    $wpaicg_roles = ( array )wp_get_current_user()->roles;
                    $limited_current_role = 0;
                    foreach ($wpaicg_roles as $wpaicg_role) {
                        if(
                            isset($wpaicg_chat_widget['limited_roles'])
                            && is_array($wpaicg_chat_widget['limited_roles'])
                            && isset($wpaicg_chat_widget['limited_roles'][$wpaicg_role])
                            && $wpaicg_chat_widget['limited_roles'][$wpaicg_role] > $limited_current_role
                        ){
                            $limited_current_role = $wpaicg_chat_widget['limited_roles'][$wpaicg_role];
                        }
                    }
                    if($limited_current_role > 0){
                        $wpaicg_limited_tokens = true;
                        $wpaicg_limited_tokens_number = $limited_current_role;
                    }
                    else{
                        $wpaicg_limited_tokens = false;
                    }
                }
                /*End check limit base role*/
                if(
                    !is_user_logged_in() && 
                    isset($wpaicg_chat_widget['guest_limited']) && $wpaicg_chat_widget['guest_limited'] && 
                    isset($wpaicg_chat_widget['guest_tokens']) && $wpaicg_chat_widget['guest_tokens'] > 0
                ){
                    $wpaicg_limited_tokens = true;
                    $wpaicg_limited_tokens_number = $wpaicg_chat_widget['guest_tokens'];
                }
                
                if(wpaicg_util_core()->wpaicg_is_pro()){
                    $wpaicg_chat_pro = WPAICG_Chat_Pro::get_instance();
                    $wpaicg_moderation = $wpaicg_chat_pro->activated($wpaicg_chat_widget);
                    $wpaicg_moderation_model = $wpaicg_chat_pro->model($wpaicg_chat_widget);
                    $wpaicg_moderation_notice = $wpaicg_chat_pro->notice($wpaicg_chat_widget);
                }
                if(isset($wpaicg_chat_widget['embedding_index']) && !empty($wpaicg_chat_widget['embedding_index'])){
                    $wpaicg_pinecone_environment = $wpaicg_chat_widget['embedding_index'];
                }
                $use_default_embedding = isset($wpaicg_chat_widget['use_default_embedding']) ? $wpaicg_chat_widget['use_default_embedding'] : true;
                $selected_embedding_model = isset($wpaicg_chat_widget['embedding_model']) ? $wpaicg_chat_widget['embedding_model'] : "";
                $selected_embedding_provider = isset($wpaicg_chat_widget['embedding_provider']) ? $wpaicg_chat_widget['embedding_provider'] : "";
            }
            if (isset($_POST['bot_id']) && !empty($_POST['bot_id'])) {
                $wpaicg_bot = get_post(sanitize_text_field(wp_unslash($_POST['bot_id'])));
                if($wpaicg_bot) {
                    $wpaicg_limited_tokens = false;
                    if(strpos($wpaicg_bot->post_content,'\"') !== false) {
                        $wpaicg_bot->post_content = str_replace('\"', '&quot;', $wpaicg_bot->post_content);
                    }
                    if(strpos($wpaicg_bot->post_content,"\'") !== false) {
                        $wpaicg_bot->post_content = str_replace('\\', '', $wpaicg_bot->post_content);
                    }
                    $wpaicg_chat_widget = json_decode($wpaicg_bot->post_content, true);
                    $wpaicg_bot_type = isset($wpaicg_chat_widget['type']) && $wpaicg_chat_widget['type'] == 'shortcode' ? 'Shortcode ' : 'Widget ';
                    $wpaicg_chat_embedding = isset($wpaicg_chat_widget['embedding']) && $wpaicg_chat_widget['embedding'] ? true : false;
                    $wpaicg_chat_embedding_type = isset($wpaicg_chat_widget['embedding_type']) ? $wpaicg_chat_widget['embedding_type'] : '' ;
                    $wpaicg_chat_no_answer = isset($wpaicg_chat_widget['no_answer']) ? $wpaicg_chat_widget['no_answer'] : '' ;
                    $wpaicg_chat_embedding_top = isset($wpaicg_chat_widget['embedding_top']) ? $wpaicg_chat_widget['embedding_top'] : 1 ;
                    $wpaicg_chat_no_answer = empty($wpaicg_chat_no_answer) ? 'I dont know' : $wpaicg_chat_no_answer;
                    $wpaicg_chat_with_embedding = false;
                    $wpaicg_chat_language = isset($wpaicg_chat_widget['language']) ? $wpaicg_chat_widget['language'] : 'en' ;
                    $wpaicg_chat_tone = isset($wpaicg_chat_widget['tone']) ? $wpaicg_chat_widget['tone'] : 'friendly' ;
                    $wpaicg_chat_proffesion = isset($wpaicg_chat_widget['proffesion']) ? $wpaicg_chat_widget['proffesion'] : 'none' ;
                    $wpaicg_chat_remember_conversation = isset($wpaicg_chat_widget['remember_conversation']) ? $wpaicg_chat_widget['remember_conversation'] : 'yes' ;
                    $wpaicg_chat_content_aware = isset($wpaicg_chat_widget['content_aware']) ? $wpaicg_chat_widget['content_aware'] : 'yes' ;
                    $wpaicg_chat_vectordb = isset($wpaicg_chat_widget['vectordb']) ? $wpaicg_chat_widget['vectordb'] : 'pinecone' ;
                    $wpaicg_chat_qdrant_collection = isset($wpaicg_chat_widget['qdrant_collection']) ? $wpaicg_chat_widget['qdrant_collection'] : '' ;
                    $wpaicg_ai_model = isset($wpaicg_chat_widget['model']) ? $wpaicg_chat_widget['model'] : 'gpt-4o-mini' ;

                    if ($wpaicg_provider === 'Azure') {
                        $wpaicg_ai_model = isset($wpaicg_chat_widget['model']) ? $wpaicg_chat_widget['model'] : '';
                    } elseif ($wpaicg_provider === 'Google') {
                        $wpaicg_ai_model = isset($wpaicg_chat_widget['model']) ? $wpaicg_chat_widget['model'] : 'gemini-pro';
                    }  elseif ($wpaicg_provider === 'OpenRouter') {
                        $wpaicg_ai_model = isset($wpaicg_chat_widget['model']) ? $wpaicg_chat_widget['model'] : 'openrouter/auto';
                    } else {
                        $wpaicg_ai_model = isset($wpaicg_chat_widget['model']) ? $wpaicg_chat_widget['model'] : 'gpt-4o-mini';
                    }

                    $wpaicg_save_logs = isset($wpaicg_chat_widget['save_logs']) && $wpaicg_chat_widget['save_logs'] ? true : false;
                    $wpaicg_chat_addition = isset($wpaicg_chat_widget['chat_addition']) && $wpaicg_chat_widget['chat_addition'] ? true : false;
                    $wpaicg_chat_addition_text = isset($wpaicg_chat_widget['chat_addition_text']) && !empty($wpaicg_chat_widget['chat_addition_text']) ? $wpaicg_chat_widget['chat_addition_text'] : '';
                    $wpaicg_user_aware = isset($wpaicg_chat_widget['user_aware']) ? $wpaicg_chat_widget['user_aware'] : 'no';
                    $wpaicg_token_limit_message = isset($wpaicg_chat_widget['limited_message']) ? $wpaicg_chat_widget['limited_message'] : $wpaicg_token_limit_message;
                    $wpaicg_save_request = isset($wpaicg_chat_widget['log_request']) && $wpaicg_chat_widget['log_request'] ? true : false;
                    $wpaicg_chat_temperature = isset($wpaicg_chat_widget['temperature']) && !empty($wpaicg_chat_widget['temperature']) ? $wpaicg_chat_widget['temperature'] :$wpaicg_chat_temperature;
                    $wpaicg_chat_max_tokens = isset($wpaicg_chat_widget['max_tokens']) && !empty($wpaicg_chat_widget['max_tokens']) ? $wpaicg_chat_widget['max_tokens'] :$wpaicg_chat_max_tokens;
                    $wpaicg_chat_top_p = isset($wpaicg_chat_widget['top_p']) && !empty($wpaicg_chat_widget['top_p']) ? $wpaicg_chat_widget['top_p'] :$wpaicg_chat_top_p;
                    $wpaicg_chat_best_of = isset($wpaicg_chat_widget['best_of']) && !empty($wpaicg_chat_widget['best_of']) ? $wpaicg_chat_widget['best_of'] :$wpaicg_chat_best_of;
                    $wpaicg_chat_frequency_penalty = isset($wpaicg_chat_widget['frequency_penalty']) && !empty($wpaicg_chat_widget['frequency_penalty']) ? $wpaicg_chat_widget['frequency_penalty'] :$wpaicg_chat_frequency_penalty;
                    $wpaicg_chat_presence_penalty = isset($wpaicg_chat_widget['presence_penalty']) && !empty($wpaicg_chat_widget['presence_penalty']) ? $wpaicg_chat_widget['presence_penalty'] :$wpaicg_chat_presence_penalty;
                    $use_default_embedding = isset($wpaicg_chat_widget['use_default_embedding']) ? $wpaicg_chat_widget['use_default_embedding'] : true;
                    $selected_embedding_model = isset($wpaicg_chat_widget['embedding_model']) ? $wpaicg_chat_widget['embedding_model'] : "";
                    $selected_embedding_provider = isset($wpaicg_chat_widget['embedding_provider']) ? $wpaicg_chat_widget['embedding_provider'] : "";
                    if (is_user_logged_in() && 
                        isset($wpaicg_chat_widget['user_limited']) && $wpaicg_chat_widget['user_limited'] && 
                        isset($wpaicg_chat_widget['user_tokens']) && $wpaicg_chat_widget['user_tokens'] > 0) {
                        $wpaicg_limited_tokens = true;
                        $wpaicg_limited_tokens_number = $wpaicg_chat_widget['user_tokens'];
                    }
                
                    /*Check limit base role*/
                    if(is_user_logged_in() && isset($wpaicg_chat_widget['role_limited']) && $wpaicg_chat_widget['role_limited']){
                        $wpaicg_roles = ( array )wp_get_current_user()->roles;
                        $limited_current_role = 0;
                        foreach ($wpaicg_roles as $wpaicg_role) {
                            if(
                                isset($wpaicg_chat_widget['limited_roles'])
                                && is_array($wpaicg_chat_widget['limited_roles'])
                                && isset($wpaicg_chat_widget['limited_roles'][$wpaicg_role])
                                && $wpaicg_chat_widget['limited_roles'][$wpaicg_role] > $limited_current_role
                            ){
                                $limited_current_role = $wpaicg_chat_widget['limited_roles'][$wpaicg_role];
                            }
                        }
                        if($limited_current_role > 0){
                            $wpaicg_limited_tokens = true;
                            $wpaicg_limited_tokens_number = $limited_current_role;
                        }
                        else{
                            $wpaicg_limited_tokens = false;
                        }
                    }

                    if(
                        !is_user_logged_in() && 
                        isset($wpaicg_chat_widget['guest_limited']) && $wpaicg_chat_widget['guest_limited'] && 
                        isset($wpaicg_chat_widget['guest_tokens']) && $wpaicg_chat_widget['guest_tokens'] > 0
                    ){
                        $wpaicg_limited_tokens = true;
                        $wpaicg_limited_tokens_number = $wpaicg_chat_widget['guest_tokens'];
                    }
                    
                    if(wpaicg_util_core()->wpaicg_is_pro()){
                        $wpaicg_chat_pro = WPAICG_Chat_Pro::get_instance();
                        $wpaicg_moderation = $wpaicg_chat_pro->activated($wpaicg_chat_widget);
                        $wpaicg_moderation_model = $wpaicg_chat_pro->model($wpaicg_chat_widget);
                        $wpaicg_moderation_notice = $wpaicg_chat_pro->notice($wpaicg_chat_widget);
                    }
                    $wpaicg_chat_source = $wpaicg_bot_type.'ID: '.$wpaicg_bot->ID;
                    if(isset($wpaicg_chat_widget['embedding_index']) && !empty($wpaicg_chat_widget['embedding_index'])){
                        $wpaicg_pinecone_environment = $wpaicg_chat_widget['embedding_index'];
                    }
                }
            }
            if(!is_user_logged_in()){
                $wpaicg_user_aware = 'no';
            }
            $wpaicg_human_name = 'Human';
            $wpaicg_user_name = '';
            if($wpaicg_user_aware == 'yes'){
                $wpaicg_human_name = wp_get_current_user()->user_login;
                if(!empty(wp_get_current_user()->display_name)) {
                    $wpaicg_user_name = 'Username: ' . wp_get_current_user()->display_name;
                    $wpaicg_human_name = wp_get_current_user()->display_name;
                }
            }
            /*Token handing*/
            $wpaicg_chat_token_id = false;

            // Check for banned IPs
            $this->check_banned_ips($wpaicg_chat_source, $wpaicg_provider);

            // Get message and URL
            $wpaicg_message = wp_unslash($_POST['message'] ?? '');

            // Check for banned words
            $this->check_banned_words($wpaicg_message, $wpaicg_chat_source, $wpaicg_provider);

            if ($wpaicg_limited_tokens) {
                $wpaicg_chat_token_log = $this->getUserTokenUsage($wpdb, $wpaicg_chat_source, $wpaicg_client_id);
                $wpaicg_token_usage_client = $wpaicg_chat_token_log ? $wpaicg_chat_token_log->tokens : 0;
                $wpaicg_chat_token_id = $wpaicg_chat_token_log ? $wpaicg_chat_token_log->id : false;

                $user_tokens = is_user_logged_in() ? get_user_meta(get_current_user_id(), 'wpaicg_chat_tokens', true) : 0;
                $still_limited = $this->isUserTokenLimited($user_tokens, $wpaicg_limited_tokens_number, $wpaicg_token_usage_client);

                if ($still_limited) {
                    $wpaicg_result = ['msg' => $wpaicg_token_limit_message, 'tokenLimitReached' => true];
                    $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);

                    if ($stream_nav_setting == 1) {
                        header('Content-Type: text/event-stream');
                        header('Cache-Control: no-cache');
                        header( 'X-Accel-Buffering: no' );
                        echo "data: " . wp_json_encode($wpaicg_result) . "\n\n";
                        ob_implicit_flush( true );
                        // Flush and end buffer if it exists
                        if (ob_get_level() > 0) {
                            ob_end_flush();
                        }
                    } else {
                        wp_send_json($wpaicg_result);
                    }
                    exit;
                }
            }
            /*End check token handing*/

            // Initialize the audio_message variable
            $audio_message = '';

            /* Check Audio Recording */
            if (isset($_FILES['audio']) && empty($_FILES['audio']['error'])) {
                $audio_file = (isset($_FILES['audio']) && is_array($_FILES['audio'])) ? array_map('sanitize_text_field', $_FILES['audio']) : array();
                $file_name = sanitize_file_name($audio_file['name']);
                $file_tmp_name = $audio_file['tmp_name'];
                $file_type = wp_check_filetype_and_ext($file_tmp_name, $file_name);
                $allowed_types = ['mp3', 'wav', 'ogg', 'flac', 'webm', 'mp4', 'mpeg', 'mpga', 'm4a'];

                // Check if the file type is allowed and file size is acceptable (e.g., 10MB limit)
                if (in_array($file_type['ext'], $allowed_types) && $audio_file['size'] <= 10000000) { // 10MB limit
                    // Process the audio file using the speech-to-text function
                    $result = $this->processSpeechToText($audio_file, $open_ai);
                    
                    if ($result['error']) {
                        $wpaicg_result['msg'] = $result['msg'];
                        wp_send_json($wpaicg_result);
                    }
                    
                    $wpaicg_message = sanitize_text_field($result['text']);
                    $audio_message = $wpaicg_message;
                } else {
                    $wpaicg_result['msg'] = 'Invalid file type or file too large.';
                    wp_send_json($wpaicg_result);
                }
            }

            /*Start check Log*/
            $wpaicg_chat_log_id = false;
            $wpaicg_chat_log_data = array();

            if (!empty($wpaicg_message) && $wpaicg_save_logs) {
                $wpaicg_current_context_id = isset($_POST['post_id']) && !empty($_POST['post_id']) ? sanitize_text_field(wp_unslash($_POST['post_id'])) : '';
                $wpaicg_current_context_title = !empty($wpaicg_current_context_id) ? get_the_title($wpaicg_current_context_id) : '';
                $wpaicg_unique_chat = md5($wpaicg_client_id . '-' . $wpaicg_current_context_id);
                $wpaicg_chat_log_check = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "wpaicg_chatlogs WHERE source=%s AND log_session=%s", $wpaicg_chat_source, $wpaicg_unique_chat));
                
                if (!$wpaicg_chat_log_check) {
                    $wpdb->insert($wpdb->prefix . 'wpaicg_chatlogs', array(
                        'log_session' => $wpaicg_unique_chat,
                        'data' => wp_json_encode(array(), JSON_UNESCAPED_UNICODE),
                        'page_title' => $wpaicg_current_context_title,
                        'source' => $wpaicg_chat_source,
                        'created_at' => current_time('timestamp')
                    ));
                    $wpaicg_chat_log_id = $wpdb->insert_id;
                } else {
                    $wpaicg_chat_log_id = $wpaicg_chat_log_check->id;
                    $wpaicg_current_log_data = json_decode($wpaicg_chat_log_check->data, true);
                    if ($wpaicg_current_log_data && is_array($wpaicg_current_log_data)) {
                        $wpaicg_chat_log_data = $wpaicg_current_log_data;
                    }
                }
                
                // Extract chatId from the request and remove non-numeric characters
                $chatId = isset($_POST['chat_id']) ? preg_replace('/\D/', '', sanitize_text_field(wp_unslash($_POST['chat_id']))) : null;

                $anonymize_ip = get_option('wpaicg_ip_anonymization', false);
                $original_ip = $this->getIpAddress();
                $ip_to_store = $anonymize_ip ? $this->anonymizeIp($original_ip) : $original_ip;

                // Insert the message into the logs with the numeric chatId
                $wpaicg_chat_log_data[] = array(
                    'message' => $wpaicg_message,
                    'type' => 'user',
                    'date' => time(),
                    'ip' => $ip_to_store,
                    'username' => $this->getCurrentUsername(),
                    'chatId' => $chatId // Store numeric chatId
                );
                
                // Save the log data back to the database
                $wpdb->update(
                    $wpdb->prefix . 'wpaicg_chatlogs',
                    array('data' => wp_json_encode($wpaicg_chat_log_data, JSON_UNESCAPED_UNICODE)),
                    array('id' => $wpaicg_chat_log_id)
                );
                
                // Clean up chat history if needed
                $wpaicg_chat_history = isset($_POST['wpaicg_chat_history']) && !empty($_POST['wpaicg_chat_history']) 
                                        ? json_decode(sanitize_text_field(wp_unslash($_POST['wpaicg_chat_history'])), true) 
                                        : [];
                $cleaned_chat_history = [];

                if (is_array($wpaicg_chat_history)) {
                    foreach ($wpaicg_chat_history as $history_item) {
                        if (is_array($history_item)) {
                            $cleaned_chat_history[] = isset($history_item['text']) ? $history_item['text'] : $history_item;
                        } else {
                            $cleaned_chat_history[] = $history_item;
                        }
                    }
                }
                
                // Save the cleaned history back to the database if needed
                $_REQUEST['wpaicg_chat_history'] = wp_json_encode($cleaned_chat_history, JSON_UNESCAPED_UNICODE);
            }
            /*End Check Log*/

            /* Disable Audio if provider is Azure  or Google */

            if ($wpaicg_provider === 'Azure' || $wpaicg_provider === 'Google') {
                // Fetch the existing options
                $wpaicg_chat_shortcode_options = get_option('wpaicg_chat_shortcode_options', []);

                // Update the audio_enable key
                $wpaicg_chat_shortcode_options['audio_enable'] = 0;

                // Update the option in the database
                update_option('wpaicg_chat_shortcode_options', $wpaicg_chat_shortcode_options);

            }

            /*Check Moderation*/
            // Check if it's the pro version
            $is_pro = \WPAICG\wpaicg_util_core()->wpaicg_is_pro();
            // If it's not the pro version then disable moderation, if it is the pro version and the provider is not OpenAI then disable moderation. if its free version disable moderation regardless of the provider
            if (!$is_pro || $wpaicg_provider !== 'OpenAI') {
                $wpaicg_moderation = false;
            }

            if(!empty($wpaicg_message) && $wpaicg_moderation){
                $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);
                $wpaicg_chat_pro->moderation($open_ai,$wpaicg_message, $wpaicg_moderation_model, $wpaicg_moderation_notice, $wpaicg_save_logs, $wpaicg_chat_log_id,$wpaicg_chat_log_data, $stream_nav_setting);
            }
            /*End Check Moderation*/
            $wpaicg_embedding_content = '';
            
            // New code to handle internet search independently
            $internet_search_content = '';

            if ($wpaicg_use_internet == 1) {
                // Retrieve Google Custom Search API key and search engine ID
                $google_api_key = get_option('wpaicg_google_api_key', '');
                $google_search_engine_id = get_option('wpaicg_google_search_engine_id', '');

                if (!empty($google_api_key) && !empty($google_search_engine_id)) {
                    $search_query = sanitize_text_field($wpaicg_message);
                    $search_results = $this->wpaicg_search_internet($google_api_key, $google_search_engine_id, $search_query);

                    if ($search_results['status'] == 'success' && !empty($search_results['data'])) {
                        // Store the internet search results
                        $internet_search_content = "\n" . $search_results['data'];
                    }
                }
            }
            $wpaicg_embedding_context_for_assistant = '';
            $excerpt_content_for_assistant = '';
            if($wpaicg_chat_embedding){
                /*Using embeddings only*/
                $namespace = false;
                if(isset($_POST['namespace']) && !empty($_POST['namespace'])){
                    $namespace = sanitize_text_field(wp_unslash($_POST['namespace']));
                }

                $wpaicg_qdrant_api_key = get_option('wpaicg_qdrant_api_key', '');
                $wpaicg_qdrant_endpoint = get_option('wpaicg_qdrant_endpoint', '');

                // Check if vectordb is set to 'qdrant'
                if ($wpaicg_chat_vectordb === 'qdrant') {
                    // Call the Qdrant specific function
                    $wpaicg_embeddings_result = $this->wpaicg_embeddings_result_qdrant($wpaicg_provider, $use_default_embedding,$selected_embedding_model,$selected_embedding_provider,$open_ai, $wpaicg_qdrant_api_key, $wpaicg_qdrant_endpoint, $wpaicg_chat_qdrant_collection, $wpaicg_message, $wpaicg_chat_source,$wpaicg_chat_embedding_top, $namespace,$confidence_score_threshold);
                } else {
                    // Continue with the current flow for Pinecone or other DB providers
                    $wpaicg_embeddings_result = $this->wpaicg_embeddings_result($wpaicg_provider, $use_default_embedding,$selected_embedding_model,$selected_embedding_provider,$open_ai,$wpaicg_pinecone_api, $wpaicg_pinecone_environment, $wpaicg_message, $wpaicg_chat_embedding_top, $wpaicg_chat_source, $namespace,$confidence_score_threshold);
                }

                if($wpaicg_embeddings_result['status'] == 'empty'){
                    $wpaicg_chat_with_embedding = false;
                }
                else {
                    if (!$wpaicg_chat_embedding_type || empty($wpaicg_chat_embedding_type)) {
                        $wpaicg_result['status'] = $wpaicg_embeddings_result['status'];
                        $wpaicg_result['data'] = empty($wpaicg_embeddings_result['data']) ? $wpaicg_chat_no_answer : $wpaicg_embeddings_result['data'];
                        $wpaicg_result['msg'] = empty($wpaicg_embeddings_result['data']) ? $wpaicg_chat_no_answer : $wpaicg_embeddings_result['data'];
                        $this->wpaicg_save_chat_log($wpaicg_chat_log_id, $wpaicg_chat_log_data, 'ai', $wpaicg_result['data']);
                        wp_send_json($wpaicg_result);
                        exit;
                    } else {
                        $wpaicg_result['status'] = $wpaicg_embeddings_result['status'];
                        if ($wpaicg_result['status'] == 'error') {
                            $wpaicg_result['msg'] = empty($wpaicg_embeddings_result['data']) ? $wpaicg_chat_no_answer : $wpaicg_embeddings_result['data'];
                            if (empty($wpaicg_result['data'])) {
                                $this->wpaicg_save_chat_log($wpaicg_chat_log_id, $wpaicg_chat_log_data, 'ai', $wpaicg_result['msg']);
                            } else {
                                $this->wpaicg_save_chat_log($wpaicg_chat_log_id, $wpaicg_chat_log_data, 'ai', $wpaicg_result['data']);
                            }
                            wp_send_json($wpaicg_result);
                            exit;
                        } else {
                            $wpaicg_total_tokens += $wpaicg_embeddings_result['tokens']; // Add embedding tokens
                            $wpaicg_embedding_content = $wpaicg_embeddings_result['data'];
                        }
                        $wpaicg_chat_with_embedding = true;
                    }
                }

                // if internet_search_content not empty, append the search results to the embedding content
                if (!empty($internet_search_content)) {
                    $wpaicg_embedding_content .= $internet_search_content;
                }
            }
            if ($wpaicg_chat_remember_conversation == 'yes') {
                
                // Check if wpaicg_chat_history exists in the POST request
                if (isset($_POST['wpaicg_chat_history']) && !empty($_POST['wpaicg_chat_history'])) {
                    // Use wp_unslash and sanitize_textarea_field for better sanitization of potential multi-line inputs
                    $wpaicg_chat_history = sanitize_textarea_field(wp_unslash($_POST['wpaicg_chat_history']));
                    
                    // Remove any backslashes
                    $wpaicg_chat_history = str_replace("\\", '', $wpaicg_chat_history);
                    
                    // Decode the JSON chat history
                    $wpaicg_chat_history = json_decode($wpaicg_chat_history, true);
                }

                // Ensure it's an array, even if json_decode fails or history is empty
                $wpaicg_chat_history = is_array($wpaicg_chat_history) ? $wpaicg_chat_history : array();

                // Set conversation end messages
                $wpaicg_conversation_end_messages = $wpaicg_chat_history;
            }

            if (!empty($wpaicg_message)) {
                global $wp_filesystem;

                // Initialize the filesystem API
                if (empty($wp_filesystem)) {
                    require_once(ABSPATH . 'wp-admin/includes/file.php');
                    WP_Filesystem();
                }
                
                $wpaicg_language_file = WPAICG_PLUGIN_DIR . 'admin/chat/languages/' . $wpaicg_chat_language . '.json';

                // Fallback to English if the specified language file doesn't exist
                if (!$wp_filesystem->exists($wpaicg_language_file)) {
                    $wpaicg_language_file = WPAICG_PLUGIN_DIR . 'admin/chat/languages/en.json';
                }
                // Get the file contents using WP_Filesystem
                $wpaicg_language_json = $wp_filesystem->get_contents($wpaicg_language_file);
                $wpaicg_languages = json_decode($wpaicg_language_json, true);
                $wpaicg_chat_tone = isset($wpaicg_languages['tone'][$wpaicg_chat_tone]) ? $wpaicg_languages['tone'][$wpaicg_chat_tone] : 'Professional';
                $wpaicg_chat_proffesion = isset($wpaicg_languages['proffesion'][$wpaicg_chat_proffesion]) ? $wpaicg_languages['proffesion'][$wpaicg_chat_proffesion] : 'none';


                $wpaicg_greeting_key = 'greeting';

                if ($wpaicg_chat_proffesion != 'none') {
                    $wpaicg_greeting_key .= '_proffesion';
                }
                $wpaicg_chat_greeting_message = sprintf($wpaicg_languages[$wpaicg_greeting_key], $wpaicg_chat_tone, $wpaicg_chat_proffesion . ".\n");

                if(!empty($wpaicg_chat_addition_text)){
                    $site_url = site_url();
                    $parse_url = wp_parse_url($site_url);
                    $domain_name = isset($parse_url['host']) && !empty($parse_url['host']) ? $parse_url['host'] : '';
                    $date = gmdate(get_option( 'date_format'));
                    $sitename = get_bloginfo('name');
                    $wpaicg_chat_addition_text = str_replace('[siteurl]',$site_url, $wpaicg_chat_addition_text);
                    $wpaicg_chat_addition_text = str_replace('[domain]',$domain_name, $wpaicg_chat_addition_text);
                    $wpaicg_chat_addition_text = str_replace('[sitename]',$sitename, $wpaicg_chat_addition_text);
                    $wpaicg_chat_addition_text = str_replace('[date]',$date, $wpaicg_chat_addition_text);
                }
                if ($wpaicg_chat_content_aware == 'yes') {
                    if($wpaicg_chat_with_embedding && !empty($wpaicg_embedding_content)){
                        $wpaicg_embedding_context_for_assistant = $wpaicg_embedding_content;
                        $wpaicg_greeting_key .= '_content';
                        $current_context = '"'.$wpaicg_embedding_content.'"';
                        if ($wpaicg_chat_proffesion != 'none') {
                            $wpaicg_chat_greeting_message = sprintf($wpaicg_languages[$wpaicg_greeting_key], $wpaicg_chat_tone, $wpaicg_chat_proffesion . ".\n", $current_context);
                        } else {
                            $wpaicg_chat_greeting_message = sprintf($wpaicg_languages[$wpaicg_greeting_key], $wpaicg_chat_tone . ".\n", $current_context);
                        }
                        if($wpaicg_chat_addition && !empty($wpaicg_chat_addition_text)){
                            $wpaicg_chat_greeting_message .= ' '.sprintf($wpaicg_languages[$wpaicg_greeting_key.'_extra'], $wpaicg_chat_addition_text);
                        }
                    }
                    elseif(isset($_REQUEST['post_id']) && !empty($_REQUEST['post_id'])){
                        $current_post = get_post(sanitize_text_field(wp_unslash($_POST['post_id'])));
                        if ($current_post) {
                            $wpaicg_greeting_key .= '_content';
                            $current_context = '"' . wp_strip_all_tags($current_post->post_title);
                            $current_post_excerpt = str_replace('[...]', '', wp_strip_all_tags(get_the_excerpt($current_post)));
                            if ($current_post_excerpt !== '') {
                                $current_post_excerpt = preg_replace_callback("/(&#[0-9]+;)/", function ($m) {
                                    return mb_convert_encoding($m[1], "UTF-8", "HTML-ENTITIES");
                                }, $current_post_excerpt);
                                $current_context .= "\n" . $current_post_excerpt;
                            }
                            $current_context .= '"';
                            $excerpt_content_for_assistant = $current_context;
                            if ($wpaicg_chat_proffesion != 'none') {
                                $wpaicg_chat_greeting_message = sprintf($wpaicg_languages[$wpaicg_greeting_key], $wpaicg_chat_tone, $wpaicg_chat_proffesion . ".\n", $current_context);
                            } else {
                                $wpaicg_chat_greeting_message = sprintf($wpaicg_languages[$wpaicg_greeting_key], $wpaicg_chat_tone . ".\n", $current_context);
                            }
                            if($wpaicg_chat_addition && !empty($wpaicg_chat_addition_text)){
                                $wpaicg_chat_greeting_message .= ' '.sprintf($wpaicg_languages[$wpaicg_greeting_key.'_extra'], $wpaicg_chat_addition_text);
                            }
                        }
                    }
                    elseif($wpaicg_chat_addition && !empty($wpaicg_chat_addition_text)){
                        $wpaicg_greeting_key .= '_content';
                        $wpaicg_chat_greeting_message .= ' '.sprintf($wpaicg_languages[$wpaicg_greeting_key.'_extra'], $wpaicg_chat_addition_text);
                    }
                }
                elseif($wpaicg_chat_addition && !empty($wpaicg_chat_addition_text)){
                    $wpaicg_greeting_key .= '_content';
                    $wpaicg_chat_greeting_message .= ' '.sprintf($wpaicg_languages[$wpaicg_greeting_key.'_extra'], $wpaicg_chat_addition_text);
                }
                if(!empty($wpaicg_user_name)){
                    $wpaicg_chat_greeting_message .= '. '.$wpaicg_user_name;
                }

                // Append the internet search content directly to the greeting message
                if (!empty($internet_search_content)) {
                    $wpaicg_chat_greeting_message .= "\n\n" . $internet_search_content;
                }

                $wpaicg_result['greeting_message'] = $wpaicg_chat_greeting_message;

                // Fetch assistant_id
                $assistant_id = '';
                if (!empty($wpaicg_ai_model) && strpos($wpaicg_ai_model, 'asst_') === 0) {
                    $assistant_id = $wpaicg_ai_model; // assign if model name starts with "asst_"
                }
                // check to see image is present in the request
                $image_final_data = '';
                $image_model_list = get_option( 'wpaicg_openrouter_image_model_list', array() );
                // Extract all 'id' fields from the stored image models.
                $image_model_ids  = array_column( $image_model_list, 'id' );
                
            if ($wpaicg_ai_model === 'gpt-4-vision-preview' || $wpaicg_ai_model === 'gpt-4o' || $wpaicg_ai_model === 'gpt-4o-mini' || in_array( $wpaicg_ai_model, $image_model_ids, true ) || ! empty( $assistant_id )) {
                    $image_file = (isset($_FILES['image']) && is_array($_FILES['image'])) ? array_map('sanitize_text_field', $_FILES['image']) : array();

                    if (!empty($image_file) && empty($image_file['error'])) {
                        // Fetch the user's preference for image processing method
                        $wpaicg_img_processing_method = get_option('wpaicg_img_processing_method', 'url');
                        
                        // Check if $assistant_id is not empty AND the processing method is base64
                        if (!empty($assistant_id) && $wpaicg_img_processing_method === 'base64') {
                            // If $assistant_id is present, perform the new method for OpenAI Assistant
                            $tmp_file = $image_file['tmp_name'];

                            $result = json_decode($open_ai->uploadAssitantFile(array(
                                'file' => array(
                                    'data' => file_get_contents($tmp_file),
                                    'filename' => basename($image_file['name']),
                                )
                            )), true);
                            
                            if (!empty($result) && isset($result['id'])) {
                                $image_final_data = $result['id'];
                            } else {
                                error_log("Failed to upload the file to OpenAI servers: " . print_r($result, true));
                            }
                                                                                 
                        } else {
                            // Otherwise, proceed with the existing logic
                            // Handle the image upload and get the URL or base64 string
                            $image_data = $this->handle_image_upload($image_file);
                            
                            // Assign the appropriate data based on the processing method
                            if ($wpaicg_img_processing_method == 'base64' && isset($image_data['base64'])) {
                                $image_final_data = $image_data['base64'];
                            } elseif (isset($image_data['url'])) {
                                $image_final_data = $image_data['url'];
                            }
                        }
                    }
                } 

                $wpaicg_chatgpt_messages = array();

                // Check if there's an image data
                if (!empty($image_final_data)) {
                    // Prepare the message with both text and image
                    $image_quality = get_option('wpaicg_img_vision_quality', 'auto');
                    $textMessage = [
                        "role" => "user",
                        "content" => [
                            [
                                "type" => "text",
                                "text" => html_entity_decode($wpaicg_chat_greeting_message, ENT_QUOTES, 'UTF-8')
                            ],
                            [
                                "type" => "image_url",
                                "image_url" => [
                                    "url" => $image_final_data,
                                    "detail" => $image_quality
                                ]
                            ]
                        ]
                    ];
                } else {
                    // Prepare the message with text only, keeping the original format
                    $textMessage = [
                        "role" => "user",
                        "content" => html_entity_decode($wpaicg_chat_greeting_message, ENT_QUOTES, 'UTF-8')
                    ];
                }

                // Add the message to the messages array
                $wpaicg_chatgpt_messages[] = $textMessage;      

                if ($wpaicg_chat_remember_conversation == 'yes') {
                    $wpaicg_conversation_end_messages[] = $wpaicg_human_name.': ' . $wpaicg_message;
                    foreach ($wpaicg_conversation_end_messages as $wpaicg_conversation_end_message) {
                        // Check if the message is an array (new format) or a string (old format)
                        if (is_array($wpaicg_conversation_end_message) && isset($wpaicg_conversation_end_message['text'])) {
                            // Handle the new format
                            $wpaicg_conversation_end_message = $wpaicg_conversation_end_message['text'];
                        }

                        // Trim the message to remove leading and trailing whitespace
                        $wpaicg_conversation_end_message = trim($wpaicg_conversation_end_message);
                        // Check if the message is from the user
                        if (strpos($wpaicg_conversation_end_message, "Human: ") === 0) {
                            // Extract user message content after "Human: "
                            $wpaicg_chatgpt_message = substr($wpaicg_conversation_end_message, strlen("Human: "));
                            $wpaicg_chatgpt_message = trim($wpaicg_chatgpt_message); // Trim the user message
                            $wpaicg_chatgpt_messages[] = array('role' => 'user', 'content' => $wpaicg_chatgpt_message);
                        } else {
                            // For assistant messages
                            $wpaicg_chatgpt_message = $wpaicg_conversation_end_message;
                            // Remove any instance of "AI: " from the message
                            $wpaicg_chatgpt_message = str_replace("AI: ", '', $wpaicg_chatgpt_message);
                            $wpaicg_chatgpt_message = trim($wpaicg_chatgpt_message); // Trim the assistant message
                            if(!empty($wpaicg_chatgpt_message)) {
                                $wpaicg_chatgpt_messages[] = array('role' => 'assistant', 'content' => $wpaicg_chatgpt_message);
                            }
                        }
                    }
                    $prompt = $wpaicg_chat_greeting_message;
                } else {
                    $prompt = $wpaicg_chat_greeting_message. "\n".$wpaicg_human_name.": " . $wpaicg_message;
                    $wpaicg_chatgpt_messages[] = array('role' => 'user','content' => $wpaicg_message);
                }

                // Determine if user enabled streaming
                $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);
                $is_streaming = ($stream_nav_setting == 1);

                if (!empty($assistant_id)) {
                    // Call the separate function
                    // Get thread_id from the thread_id in post data
                    $thread_id = sanitize_text_field(wp_unslash($_POST['thread_id'] ?? ''));

                    // Prepare additional instructions
                    $additional_instructions = '';
                    // Additional text
                    if ($wpaicg_chat_addition && !empty($wpaicg_chat_addition_text)) {
                        $additional_instructions .= $wpaicg_chat_addition_text . "\n";
                    }

                    // Internet search results
                    if ($wpaicg_use_internet == 1 && !empty($internet_search_content)) {
                        $additional_instructions .= $internet_search_content . "\n";
                    }

                    // Check if content-aware mode is enabled
                    if ($wpaicg_chat_content_aware == 'yes') {
                        // Check if embeddings are enabled and content exists
                        if ($wpaicg_chat_with_embedding && !empty($wpaicg_embedding_content)) {
                            $additional_instructions .= $wpaicg_embedding_content . "\n";
                        } 
                        // Fall back to excerpt content if embeddings are not enabled or empty
                        elseif (!empty($excerpt_content_for_assistant)) {
                            $additional_instructions .= $excerpt_content_for_assistant . "\n";
                        }
                    }

                    // if $wpaicg_user_aware is enabled, add the user's name to the additional instructions
                    if (!empty($wpaicg_user_name)) {
                        $additional_instructions .= $wpaicg_user_name . "\n";
                    }

                    // Trim any extra whitespace
                    $additional_instructions = trim($additional_instructions);

                    // If additional_instructions is empty, set to null
                    if (empty($additional_instructions)) {
                        $additional_instructions = null;
                    }

                    $assistant_response = WPAICG_Assistants::get_instance()->handle_assistant_api(
                        $assistant_id,
                        $wpaicg_message,
                        $thread_id,
                        $additional_instructions,
                        $is_streaming,
                        $image_final_data 
                    );
                    
                    if ($is_streaming) {
                        // we need to save logs and token handling here.

                        $wpaicg_result['data'] = $assistant_response['data'];
                        $wpaicg_total_tokens = isset($assistant_response['total_tokens']) ? $assistant_response['total_tokens'] : 0;
                        $wpaicg_assistant_model = isset($assistant_response['model']) ? $assistant_response['model'] : $wpaicg_ai_model;

                        // Include embedding tokens if any
                        if (isset($wpaicg_embeddings_result['tokens'])) {
                            $wpaicg_total_tokens += $wpaicg_embeddings_result['tokens'];
                        }

                        // Prepare the request array with model
                        $request = [
                            'model' => $wpaicg_assistant_model,
                            'temperature' => floatval($wpaicg_chat_temperature),
                            'max_tokens' => intval($wpaicg_chat_max_tokens),
                            'frequency_penalty' => floatval($wpaicg_chat_frequency_penalty),
                            'presence_penalty' => floatval($wpaicg_chat_presence_penalty),
                            'top_p' => floatval($wpaicg_chat_top_p),
                            'stream' => $is_streaming,
                            'provider' => $wpaicg_provider
                        ];

                        // call the function for log saving and token handling
                        $this->wpaicg_handle_logs_and_tokens(
                            $wpaicg_chat_log_id,
                            $wpaicg_chat_log_data,
                            $wpaicg_result['data'],
                            $wpaicg_total_tokens,
                            $request,
                            isset($wpaicg_embeddings_result['matches']) ? $wpaicg_embeddings_result['matches'] : [],
                            $wpaicg_limited_tokens,
                            $wpaicg_token_usage_client,
                            $wpaicg_chat_token_id,
                            $wpaicg_chat_source,
                            $wpaicg_client_id,
                            $wpdb
                        );

                        // Prepare result
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['log'] = $wpaicg_chat_log_id;

                        exit;

                    }

                    if ($assistant_response['status'] == 'error') {
                        $wpaicg_result['status'] = 'error';
                        $wpaicg_result['msg'] = $assistant_response['msg'];
                    } else {
                        // Process the assistant result
                        $wpaicg_result['data'] = $assistant_response['data'];
                        $wpaicg_result['thread_id'] = $assistant_response['thread_id'];

                        // Use the total_tokens from the assistant response
                        $wpaicg_total_tokens = isset($assistant_response['total_tokens']) ? $assistant_response['total_tokens'] : 0;
                        // get model name from the assistant response
                        $wpaicg_assistant_model = isset($assistant_response['model']) ? $assistant_response['model'] : $wpaicg_ai_model;

                        // Include embedding tokens if any
                        if (isset($wpaicg_embeddings_result['tokens'])) {
                            $wpaicg_total_tokens += $wpaicg_embeddings_result['tokens'];
                        }

                        // Prepare the request array with model
                        $request = [
                            'model' => $wpaicg_assistant_model,
                            'temperature' => floatval($wpaicg_chat_temperature),
                            'max_tokens' => intval($wpaicg_chat_max_tokens),
                            'frequency_penalty' => floatval($wpaicg_chat_frequency_penalty),
                            'presence_penalty' => floatval($wpaicg_chat_presence_penalty),
                            'top_p' => floatval($wpaicg_chat_top_p),
                            'stream' => $is_streaming,
                            'provider' => $wpaicg_provider
                        ];

                        // call the function for log saving and token handling
                        $this->wpaicg_handle_logs_and_tokens(
                            $wpaicg_chat_log_id,
                            $wpaicg_chat_log_data,
                            $wpaicg_result['data'],
                            $wpaicg_total_tokens,
                            $request,
                            isset($wpaicg_embeddings_result['matches']) ? $wpaicg_embeddings_result['matches'] : [],
                            $wpaicg_limited_tokens,
                            $wpaicg_token_usage_client,
                            $wpaicg_chat_token_id,
                            $wpaicg_chat_source,
                            $wpaicg_client_id,
                            $wpdb
                        );

                        // Prepare result
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['log'] = $wpaicg_chat_log_id;
                    }

                    // Send the JSON response
                    wp_send_json($wpaicg_result);
                    exit;
                }

                // Get the list of models for chat and completion endpoints
                $chatEndpointModels = $this->getChatEndpointModels();
                $completionEndpointModels = $this->getCompletionEndpointModels();

                // Initialize the data request array with common elements
                $wpaicg_data_request = [
                    'model' => $wpaicg_ai_model,
                    'temperature' => floatval($wpaicg_chat_temperature),
                    'max_tokens' => intval($wpaicg_chat_max_tokens),
                    'frequency_penalty' => floatval($wpaicg_chat_frequency_penalty),
                    'presence_penalty' => floatval($wpaicg_chat_presence_penalty),
                    'top_p' => floatval($wpaicg_chat_top_p)
                ];

                // Determine the appropriate API endpoint and modify the data request accordingly
                if (in_array($wpaicg_ai_model, $chatEndpointModels)) {
                    // Model uses the chat endpoint
                    $wpaicg_data_request['messages'] = $wpaicg_chatgpt_messages;
                } elseif (in_array($wpaicg_ai_model, $completionEndpointModels)) {
                    // Model uses the completion endpoint
                    foreach ($wpaicg_chatgpt_messages as $wpaicg_chatgpt_message) {
                        $prompt .= $wpaicg_chatgpt_message['content'] . "\n";
                    }
                    $wpaicg_data_request += ['prompt' => $prompt, 'best_of' => intval($wpaicg_chat_best_of)];
                }

                // Determine stream navigation setting and modify the data request
                $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);

                if ($stream_nav_setting == 1) {
                    $wpaicg_data_request['stream'] = true;
                    header("Content-Type: text/event-stream");
                    header("Cache-Control: no-cache");
                    header("X-Accel-Buffering: no");
                    ob_implicit_flush( true );
                    // Flush and end buffer if it exists
                    if (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                }

                $apiFunction = in_array($wpaicg_ai_model, $chatEndpointModels) ? 'chat' : 'completion';

                // Call the new function
                $complete = $this->performOpenAiRequest($wpaicg_provider, $open_ai, $apiFunction, $wpaicg_data_request, $accumulatedData);

                // Process the response based on the stream navigation setting
                if ($stream_nav_setting == 1) {
                    $isChatEndpoint = ($apiFunction === 'chat');
                    $complete = $this->processChunkedData($accumulatedData, $wpaicg_chatgpt_messages, $wpaicg_ai_model, $isChatEndpoint);
                } else {
                    if (is_string($complete)) {
                        $complete = json_decode($complete);
                    }
                }

                if (isset($complete->error)) {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html(trim($complete->error->message));
                    if(empty($wpaicg_result['msg']) && isset($complete->error->code) && $complete->error->code == 'invalid_api_key'){
                        $wpaicg_result['msg'] = 'Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.';
                    }
                    $wpaicg_result['log'] = $wpaicg_chat_log_id;

                } else {

                    // Determine if the model is a legacy model using predefined functions
                    $isLegacyModel = in_array($wpaicg_ai_model, $this->getCompletionEndpointModels());

                    // Use the helper function to extract data
                    $wpaicg_result['data'] = $this->extractResponseData($complete, $stream_nav_setting, $isLegacyModel);

                    $wpaicg_total_tokens += $this->extractTotalTokens($complete, $stream_nav_setting);

                    if(!$wpaicg_save_request){
                        $wpaicg_data_request = false;
                    }

                    // Ensure $wpaicg_data_request is an array
                    if (!is_array($wpaicg_data_request)) {
                        $wpaicg_data_request = array();
                    }

                    // Now, you can safely assign the provider
                    $wpaicg_data_request['provider'] = $wpaicg_provider !== false ? $wpaicg_provider : 'OpenAI';

                    $wpaicg_data_request['model'] = $wpaicg_ai_model;
                    /**
                     * 1. Retrieve any stored image model list from the database.
                     * 2. Extract the "id" fields to compare with $wpaicg_ai_model.
                     */
                    $image_model_list = get_option( 'wpaicg_openrouter_image_model_list', array() );
                    $image_model_ids  = array_column( $image_model_list, 'id' );

                    if (
                        (
                            $wpaicg_ai_model === 'gpt-4-vision-preview' ||
                            $wpaicg_ai_model === 'gpt-4o' ||
                            $wpaicg_ai_model === 'gpt-4o-mini' ||
                            in_array( $wpaicg_ai_model, $image_model_ids, true )
                        )
                        && isset( $_FILES['image'] )
                        && empty( $_FILES['image']['error'] )
                    ) {
                        $wpaicg_img_processing_method = get_option('wpaicg_img_processing_method', 'url');
                        
                        // Proceed only if the image processing method is base64
                        if ($wpaicg_img_processing_method == 'base64') {
                            if (isset($wpaicg_data_request['messages']) && is_array($wpaicg_data_request['messages'])) {
                                // Iterate through the messages to find and replace base64 image data with URL
                                foreach ($wpaicg_data_request['messages'] as &$message) {
                                    if (isset($message['content']) && is_array($message['content'])) {
                                        foreach ($message['content'] as &$content) {
                                            if ($content['type'] == 'image_url' && isset($content['image_url']['url'])) {
                                                // Check if the URL is actually a base64 string
                                                if (strpos($content['image_url']['url'], 'data:image/') === 0) {
                                                    // Replace base64 data with the URL
                                                    $content['image_url']['url'] = $image_data['url'];
                                                }
                                            }
                                        }
                                        unset($content); // Break the reference with the last element
                                    }
                                }
                                unset($message); // Break the reference with the last element
                            }
                        }
                    }
                
                    $this->wpaicg_save_chat_log($wpaicg_chat_log_id, $wpaicg_chat_log_data, 'ai',$wpaicg_result['data'],$wpaicg_total_tokens,false,$wpaicg_data_request,isset($wpaicg_embeddings_result['matches']) ? $wpaicg_embeddings_result['matches'] : array());
                    
                    if(is_user_logged_in() && $wpaicg_limited_tokens){
                        WPAICG_Account::get_instance()->save_log('chat', $wpaicg_total_tokens);
                    }

                    $wpaicg_result['status'] = 'success';
                    $wpaicg_result['log'] = $wpaicg_chat_log_id;
                    if($wpaicg_limited_tokens){
                        if($wpaicg_chat_token_id){
                            $wpdb->update($wpdb->prefix.'wpaicg_chattokens', array(
                                'tokens' => ($wpaicg_total_tokens + $wpaicg_token_usage_client)
                            ), array('id' => $wpaicg_chat_token_id));
                        }
                        else{
                            $wpaicg_chattoken_data = array(
                                'tokens' => $wpaicg_total_tokens,
                                'source' => $wpaicg_chat_source,
                                'created_at' => current_time('timestamp')
                            );
                            if(is_user_logged_in()){
                                $wpaicg_chattoken_data['user_id'] = get_current_user_id();
                            }
                            else{
                                $wpaicg_chattoken_data['session_id'] = $wpaicg_client_id;
                            }
                            $wpdb->insert($wpdb->prefix.'wpaicg_chattokens',$wpaicg_chattoken_data);
                        }
                    }
                    /*
                        * End save token handing
                        * */
                    if ($wpaicg_chat_remember_conversation == 'yes') {
                        $wpaicg_conversation_end_messages[] = $wpaicg_result['data'];
                    }
                }
            }
            else{
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('It appears that nothing was inputted.','gpt3-ai-content-generator');
            }

            // if stream_nav_setting is enabled, exit now else continue
            if ($stream_nav_setting == 1) {
                exit;
            }
            wp_send_json( $wpaicg_result );
        }

        private function estimateTokens($text)
        {
            // Simple estimation: 1 token per 4 characters
            $num_chars = mb_strlen($text, 'UTF-8');
            $num_tokens = ceil($num_chars / 4);
            return $num_tokens;
        }

        // Add the search_internet function to handle the API call
        public function wpaicg_search_internet($api_key, $search_engine_id, $query) {
            $country = get_option('wpaicg_google_search_country', '');
            $num_results = get_option('wpaicg_google_search_num', 10);
            $language = get_option('wpaicg_google_search_language', '');
        
            $search_url = 'https://www.googleapis.com/customsearch/v1?q=' . urlencode($query) . '&key=' . $api_key . '&cx=' . $search_engine_id;
        
            if (!empty($country)) {
                $search_url .= '&cr=' . $country;
            }
        
            if (!empty($language)) {
                $search_url .= '&lr=' . $language;
            }
        
            $search_url .= '&num=' . intval($num_results);
        
            $response = wp_remote_get($search_url);
        
            if (is_wp_error($response)) {
                return ['status' => 'error', 'data' => ''];
            }
        
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
        
            if (isset($data['items']) && !empty($data['items'])) {
                $search_content = '';
        
                foreach ($data['items'] as $item) {
                    $search_content .= $item['title'] . "\n" . $item['snippet'] . "\n" . $item['link'] . "\n\n";
                }
        
                return ['status' => 'success', 'data' => $search_content];
            }
        
            return ['status' => 'empty', 'data' => ''];
        }
        
        public function handle_image_upload($image) {
            $wpaicg_user_uploads = get_option('wpaicg_user_uploads', 'filesystem');
            $wpaicg_img_processing_method = get_option('wpaicg_img_processing_method', 'url'); // Fetch user preference
            $wpaicg_delete_image = get_option('wpaicg_delete_image', 0); // Fetch delete image preference
            $result = ['url' => '', 'base64' => '']; // Initialize result variable with both keys
        
            // Validate file type before proceeding
            $file_info = wp_check_filetype_and_ext($image['tmp_name'], $image['name']);
            
            // Allowed file extensions and MIME types
            $allowed_file_types = [
                'png' => 'image/png',
                'jpeg' => 'image/jpeg',
                'jpg' => 'image/jpeg',
                'webp' => 'image/webp',
                'gif' => 'image/gif'
            ];

            if (!$file_info['ext'] || !$file_info['type'] || !array_key_exists($file_info['ext'], $allowed_file_types) || $file_info['type'] !== $allowed_file_types[$file_info['ext']]) {
                die(__("File type is not allowed. Only PNG, JPEG, WEBP, and non-animated GIF are supported.", "gpt3-ai-content-generator"));
            }
            // Initialize the WordPress filesystem once
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                WP_Filesystem();
            }
            if ($wpaicg_user_uploads === 'filesystem') {
                // Use WordPress function wp_handle_upload to manage the upload
                $upload = wp_handle_upload($image, ['test_form' => false]);
            
                if ($upload && !isset($upload['error'])) {
                    // Get the base upload directory path (e.g., uploads/)
                    $upload_dir = wp_upload_dir();
                    $custom_subfolder = $upload_dir['basedir'] . '/aipower_user_uploads/'; // Define custom subfolder inside basedir
            
                    // Create the custom subfolder if it doesn't exist
                    if (!file_exists($custom_subfolder)) {
                        wp_mkdir_p($custom_subfolder);
                    }
            
                    // Move the uploaded file to the custom subfolder using WP_Filesystem::move()
                    $new_file_path = $custom_subfolder . basename($upload['file']);
                    $wp_filesystem->move($upload['file'], $new_file_path, true);
            
                    // Set the new URL for the uploaded file in the custom subfolder
                    $result['url'] = $upload_dir['baseurl'] . '/aipower_user_uploads/' . basename($new_file_path);
            
                    // Convert to base64 if required
                    $imageData = $wp_filesystem->get_contents($new_file_path);
                    $result['base64'] = 'data:image/' . pathinfo($new_file_path, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
            
                    // Delete the image file after processing if the option is enabled and processing method is not URL
                    if ($wpaicg_delete_image && $wpaicg_img_processing_method !== 'url') {
                        wp_delete_file($new_file_path);
                    }
                }
            } else if ($wpaicg_user_uploads === 'media_library') {
                // Insert the image into the WordPress Media Library
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                require_once(ABSPATH . 'wp-admin/includes/file.php');
                require_once(ABSPATH . 'wp-admin/includes/media.php');
        
                $attachment_id = media_handle_upload('image', 0);
        
                if (!is_wp_error($attachment_id)) {
                    // Get the file path of the uploaded image
                    $file_path = get_attached_file($attachment_id);
                
                    // Always set the URL of the uploaded image
                    $result['url'] = wp_get_attachment_url($attachment_id);
                
                    // Convert to base64 if required
                    $imageData = $wp_filesystem->get_contents($file_path);
                    $result['base64'] = 'data:image/' . pathinfo($file_path, PATHINFO_EXTENSION) . ';base64,' . base64_encode($imageData);
                
                    // Delete the image file after processing if the option is enabled and processing method is not URL
                    if ($wpaicg_delete_image && $wpaicg_img_processing_method !== 'url') {
                        wp_delete_attachment($attachment_id, true);
                    }
                }                
            }
        
            return $result;
        }
        
        /* Token handling */
        public function getUserTokenUsage($wpdb, $wpaicg_chat_source, $wpaicg_client_id) {
            if (is_user_logged_in()) {
                $user_id = get_current_user_id();
                return $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wpaicg_chattokens WHERE source = %s AND user_id = %d", 
                        $wpaicg_chat_source, 
                        $user_id
                    )
                );
            } else {
                return $wpdb->get_row(
                    $wpdb->prepare(
                        "SELECT * FROM {$wpdb->prefix}wpaicg_chattokens WHERE source = %s AND session_id = %s", 
                        $wpaicg_chat_source, 
                        $wpaicg_client_id
                    )
                );
            }
        }


        public function isUserTokenLimited($user_tokens, $wpaicg_limited_tokens_number, $wpaicg_token_usage_client) {
            return $user_tokens <= 0 && $wpaicg_token_usage_client > $wpaicg_limited_tokens_number;
        }

        public function processSpeechToText($file, $open_ai) {
            global $wp_filesystem;
            if (empty($wp_filesystem)) {
                require_once ABSPATH . 'wp-admin/includes/file.php';
                WP_Filesystem();
            }

            $file_name = sanitize_file_name(basename($file['name']));
            $filetype = wp_check_filetype($file_name);
            $mime_types = ['mp3' => 'audio/mpeg', 'mp4' => 'video/mp4', 'mpeg' => 'video/mpeg', 'm4a' => 'audio/m4a', 'wav' => 'audio/wav', 'webm' => 'video/webm'];
        
            if (!in_array($filetype['type'], $mime_types)) {
                return ['error' => true, 'msg' => esc_html__('Accepted audio and video formats: MP3, MP4, MPEG, M4A, WAV, and WEBM.', 'gpt3-ai-content-generator')];
            }
        
            if ($file['size'] > 26214400) {
                return ['error' => true, 'msg' => esc_html__('Maximum audio file size: 25MB.', 'gpt3-ai-content-generator')];
            }
        
            $tmp_file = $file['tmp_name'];
            $audio_data = $wp_filesystem->get_contents($tmp_file);
            if ($audio_data === false) {
                return ['error' => true, 'msg' => esc_html__('Failed to read the audio file.', 'gpt3-ai-content-generator')];
            }

            $data_audio_request = [
                'audio' => [
                    'filename' => $file_name,
                    'data' => $audio_data
                ],
                'model' => 'whisper-1',
                'response_format' => 'json'
            ];
        
            $completion = $open_ai->transcriptions($data_audio_request);
            $completion = json_decode($completion);
        
            if ($completion && isset($completion->error)) {
                $msg = $completion->error->message;
                if (empty($msg) && isset($completion->error->code) && $completion->error->code == 'invalid_api_key') {
                    $msg = 'Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.';
                }
                return ['error' => true, 'msg' => $msg];
            }
        
            return ['error' => false, 'text' => $completion->text];
        }

        public function getChatEndpointModels() {
            // List of models for the chat completions endpoint
            $chatModels = ['gpt-4', 'gpt-4-32k', 'gpt-4-1106-preview','gpt-4o', 'gpt-4o-mini','o1-preview','o1','o1-mini','o3-mini','gpt-4-turbo','gpt-4-vision-preview', 'gpt-3.5-turbo', 'gpt-3.5-turbo-16k','chatgpt-4o-latest'];
            
            // Get custom models and Azure deployment model, if any
            $custom_models = get_option('wpaicg_custom_models', []);
            $wpaicg_azure_deployment = get_option('wpaicg_azure_deployment', '');
            $wpaicg_shortcode_google_model = get_option('wpaicg_shortcode_google_model', 'gemini-pro'); 
            $wpaicg_widget_google_model = get_option('wpaicg_widget_google_model', 'gemini-pro');
            $google_models = get_option('wpaicg_google_model_list', []);
            // Retrieve and extract model IDs from wpaicg_openrouter_model_list
            $openrouter_model_list = get_option('wpaicg_openrouter_model_list', []);
            $openrouter_models = [];
            if (is_array($openrouter_model_list)) {
                foreach ($openrouter_model_list as $model) {
                    if (isset($model['id'])) {
                        $openrouter_models[] = $model['id'];
                    }
                }
            }
    
        
            // Merge and filter the list
            return array_filter(array_merge($google_models,$chatModels, $custom_models,$openrouter_models, [$wpaicg_azure_deployment], [$wpaicg_shortcode_google_model], [$wpaicg_widget_google_model]));
        }

        public function getCompletionEndpointModels() {
            // List of legacy models for the completion endpoint
            return ['text-davinci-003', 'text-ada-001', 'text-curie-001', 'text-babbage-001', 'gpt-3.5-turbo-instruct', 'babbage-002', 'davinci-002'];
        }
        
        
        public function extractTotalTokens($complete, $stream_nav_setting) {
            if ($stream_nav_setting == 1) {
                // For chunked data, access 'usage' as an array
                return isset($complete['usage']['total_tokens']) ? $complete['usage']['total_tokens'] : 0;
            } else {
                // For non-chunked data, access 'usage' as it is (assuming it's an object)
                return isset($complete->usage->total_tokens) ? $complete->usage->total_tokens : 0;
            }
        }
        
        public function extractResponseData($complete, $stream_nav_setting, $isLegacyModel) {
            if ($stream_nav_setting == 1) {
                // For chunked data, the content is already concatenated in process ChunkedData
                if (isset($complete['choices'][0]['message']['content'])) {
                    return $complete['choices'][0]['message']['content'];
                } elseif (isset($complete['choices'][0]['text'])) {
                    return $complete['choices'][0]['text'];
                } else {
                    return ''; // Return an empty string if no content is found
                }
            } else {
                // For non-chunked data, extract based on legacy or non-legacy model
                $dataKey = $isLegacyModel ? 'text' : 'message';
                return isset($complete->choices[0]->$dataKey->content) ? $complete->choices[0]->$dataKey->content : (isset($complete->choices[0]->$dataKey) ? $complete->choices[0]->$dataKey : '');
            }
        }
        
        public function performOpenAiRequest($wpaicg_provider, $open_ai, $apiFunction, $wpaicg_data_request, &$accumulatedData) {
            if ($wpaicg_provider == 'Google') {
                // add source = chat to the request
                $wpaicg_data_request['sourceModule'] = 'chat';
                return $open_ai->chat($wpaicg_data_request);
            } elseif ($wpaicg_provider == 'OpenRouter') {
                // Custom handling for OpenRouter
                try {
                    return $open_ai->$apiFunction($wpaicg_data_request, function ($curl_info, $data) use (&$accumulatedData) {
                        // Process data line by line for OpenRouter
                        $lines = explode("\n", $data);
                        foreach ($lines as $line) {
                            $line = trim($line);
                            if ($line === '') {
                                continue;
                            }
                            // Only echo lines that start with 'data:'
                            if (strpos($line, 'data:') === 0) {
                                // Echo the line to the client
                                echo $line . "\n\n";
                                ob_implicit_flush( true );
                                // Flush and end buffer if it exists
                                if (ob_get_level() > 0) {
                                    ob_end_flush();
                                }
                                // Accumulate the data
                                $accumulatedData .= $line . "\n";
                            }
                        }
                        return strlen($data);
                    });
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    $this->wpaicg_event_message($message);
                }
            } else {
                try {
                    return $open_ai->$apiFunction($wpaicg_data_request, function ($curl_info, $data) use (&$accumulatedData) {
                        $response = json_decode($data, true);
                        if (isset($response['error']) && !empty($response['error'])) {
                            $message = isset($response['error']['message']) && !empty($response['error']['message']) ? $response['error']['message'] : '';
                            if (empty($message) && isset($response['error']['code']) && $response['error']['code'] == 'invalid_api_key') {
                                $message = "Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.";
                            }
                            $this->handleStreamErrorMessage($message);
                        } else {
                            echo $data;
                            ob_implicit_flush( true );
                            // Flush and end buffer if it exists
                            if (ob_get_level() > 0) {
                                ob_end_flush();
                            }
                            $accumulatedData .= $data; // Append data to the accumulator
                            return strlen($data);
                        }
                    });
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    $this->wpaicg_event_message($message);
                }
            }

        }

        public function wpaicg_event_message($words)
        {
            $words = explode(' ', $words);
            $words[count($words) + 1] = '[LIMITED]';
            foreach ($words as $key => $word) {
                echo "event: message\n";
                if ($key == 0) {
                    echo 'data: {"choices":[{"delta":{"content":"' . $word . '"}}]}';
                } else {
                    if ($word == '[LIMITED]') {
                        echo 'data: [LIMITED]';
                    } else {
                        echo 'data: {"choices":[{"delta":{"content":" ' . $word . '"}}]}';
                    }
                }
                echo "\n\n";
				ob_implicit_flush( true );
                // Flush and end buffer if it exists
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }
        }
        
        public function handleStreamErrorMessage($message) {
            $words = explode(' ', $message);

            foreach ($words as $key => $word) {
                echo "event: message\n";
                $data = $key == 0 ? '{"choices":[{"delta":{"content":"' . $word . '"}}]}' : '{"choices":[{"delta":{"content":" ' . $word . '"}}]}';
                echo "data: $data\n\n";
				ob_implicit_flush( true );
                // Flush and end buffer if it exists
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
            }

            // Send finish_reason stop after the message
            echo 'data: {"choices":[{"finish_reason":"stop"}]}';
            echo "\n\n";
            ob_implicit_flush( true );
            // Flush and end buffer if it exists
            if (ob_get_level() > 0) {
                ob_end_flush();
            }
        }

        public function processChunkedData($accumulatedData, $wpaicg_chatgpt_messages, $wpaicg_ai_model, $isChatEndpoint) {
            $decodedData = json_decode($accumulatedData, true);
            if (isset($decodedData['error']['message'])) {
                echo "event: message\n";
                echo 'data: {"choices":[{"delta":{"content":"' . $decodedData['error']['message'] . '"}}]}';
                echo "\n\n";
                echo 'data: {"choices":[{"finish_reason":"stop"}]}';
                echo "\n\n";
                ob_implicit_flush( true );
                // Flush and end buffer if it exists
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                return;
            }
            // Check if this is OpenRouter response (contains multiple data: entries in one chunk)
            if (strpos($accumulatedData, "data: {") !== false && substr_count($accumulatedData, "data: {") > 1) {
                // Split the OpenRouter response into individual chunks
                preg_match_all('/data: ({.*})(?:\n|$)/', $accumulatedData, $matches);
                $chunks = $matches[0];
            } else {
                // Handle OpenAI format (already properly split)
                $chunks = explode("\n\n", $accumulatedData);
            }
            $completeData = [];
            $id = $created = null;
        
            foreach ($chunks as $chunk) {
                if (trim($chunk) != "") {
                    $jsonStart = strpos($chunk, "{");
                    if ($jsonStart !== false) {
                        $decodedChunk = json_decode(substr($chunk, $jsonStart), true);
                        if ($decodedChunk !== null) {
                            if ($isChatEndpoint) {
                                if (isset($decodedChunk['choices'][0]['delta']['content'])) {
                                    $completeData[] = $decodedChunk['choices'][0]['delta']['content'];
                                }
                            } else {
                                if (isset($decodedChunk['choices'][0]['text'])) {
                                    $completeData[] = $decodedChunk['choices'][0]['text'];
                                }
                            }
                            if (is_null($id) && isset($decodedChunk['id']) && isset($decodedChunk['created'])) {
                                $id = $decodedChunk['id'];
                                $created = $decodedChunk['created'];
                            }
                        }
                    }
                }
            }
        
            $finalMessage = implode("", $completeData);
        
            // Calculate tokens
            $promptCharacters = array_sum(array_map('strlen', array_column($wpaicg_chatgpt_messages, 'content')));
            $prompt_tokens = intval($promptCharacters / 100 * 21);
            $completionCharacters = strlen($finalMessage);
            $completion_tokens = intval($completionCharacters / 100 * 21);
            $total_tokens = $prompt_tokens + $completion_tokens;
        
            // Construct the complete array with usage information
            return [
                "id" => $id,
                "object" => "chat.completion",
                "created" => $created,
                "model" => $wpaicg_ai_model,
                "choices" => [
                    [
                        "index" => 0,
                        "message" => [
                            "role" => "assistant",
                            "content" => $finalMessage
                        ],
                        "finish_reason" => "stop"
                    ]
                ],
                "usage" => [
                    "prompt_tokens" => $prompt_tokens,
                    "completion_tokens" => $completion_tokens,
                    "total_tokens" => $total_tokens
                ]
            ];
        }

        public function determine_stream_nav_setting($chat_source, $wpaicg_provider) {

            global $wpdb;

            // If the provider is Google, return '0' for streaming
            if ($wpaicg_provider === 'Google') {
                return '0';
            }

            if ($chat_source === 'shortcode') {
                return get_option('wpaicg_shortcode_stream', '1');
            } elseif ($chat_source === 'widget') {
                return get_option('wpaicg_widget_stream', '1');
            } elseif (strpos($chat_source, 'Shortcode ID:') !== false || strpos($chat_source, 'Widget ID:') !== false) {
                // Extracting the numeric ID from the chat source
                $post_id = intval(str_replace(['Shortcode ID:', 'Widget ID:'], '', $chat_source));
                
                // Fetch the post content from the database
                $post_content = $wpdb->get_var($wpdb->prepare("SELECT post_content FROM {$wpdb->posts} WHERE ID = %d", $post_id));

                if ($post_content) {
                    $bot_settings = json_decode($post_content, true);
                    if (isset($bot_settings['openai_stream_nav'])) {
                        return $bot_settings['openai_stream_nav'];
                    }
                }

            }
            return '0';
        }

        public function getIpAddress()
        {
            $ip_sources = [
                'HTTP_CLIENT_IP',
                'HTTP_X_FORWARDED_FOR',
                'HTTP_X_FORWARDED',
                'HTTP_X_CLUSTER_CLIENT_IP',
                'HTTP_FORWARDED_FOR',
                'HTTP_FORWARDED',
                'REMOTE_ADDR'
            ];
        
            $ipAddress = '';
        
            foreach ($ip_sources as $source) {
                if (isset($_SERVER[$source]) && !empty($_SERVER[$source])) {
                    // Unsanitize and sanitize the IP source
                    $ip = sanitize_text_field(wp_unslash($_SERVER[$source]));
        
                    // Handle multiple IPs for 'HTTP_X_FORWARDED_FOR'
                    if ($source === 'HTTP_X_FORWARDED_FOR') {
                        $ipAddressList = explode(',', $ip);
                        foreach ($ipAddressList as $ipItem) {
                            $ipItem = sanitize_text_field($ipItem); // Ensure each IP is sanitized
                            if (!empty($ipItem)) {
                                $ipAddress = $ipItem;
                                break;
                            }
                        }
                    } else {
                        $ipAddress = $ip;
                    }
        
                    if (!empty($ipAddress)) {
                        break; // Exit loop once a valid IP is found
                    }
                }
            }
        
            // Replace ::1 with 127.0.0.1
            if ($ipAddress === '::1') {
                $ipAddress = '127.0.0.1';
            }
        
            return $ipAddress;
        }
        
        public function check_banned_ips($wpaicg_chat_source, $wpaicg_provider) {
            // Get the user's IP
            $user_ip = $this->getIpAddress();
        
            // Retrieve the list of banned IPs from the database
            $banned_ips = explode(',', get_option('wpaicg_banned_ips', ''));
            $banned_ips = array_map('trim', $banned_ips); // Trim spaces
        
            // Check if the user's IP is in the banned list
            if (in_array($user_ip, $banned_ips)) {
                $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);
                $stream_ban_result = ['msg' => esc_html__('You are not allowed to access this feature.', 'gpt3-ai-content-generator'), 'ipBanned' => true];
        
                if ($stream_nav_setting == 1) {
                    header('Content-Type: text/event-stream');
                    header('Cache-Control: no-cache');
                    header('X-Accel-Buffering: no');
                    echo "data: " . wp_json_encode($stream_ban_result) . "\n\n";
                    ob_implicit_flush( true );
                    // Flush and end buffer if it exists
                    if (ob_get_level() > 0) {
                        ob_end_flush();
                    }
                } else {
                    wp_send_json(array(
                        'status' => 'error',
                        'msg'    => esc_html__('You are not allowed to access this feature.', 'gpt3-ai-content-generator')
                    ));
                    exit;
                }
            }
        }
        

        public function check_banned_words($message, $wpaicg_chat_source, $wpaicg_provider) {
            // Retrieve the list of banned words from the database
            $banned_words = explode(',', get_option('wpaicg_banned_words', ''));
            $banned_words = array_map('trim', $banned_words); // Trim spaces
            $banned_words = array_filter($banned_words); // Remove empty elements
        
            // Convert message and banned words to lowercase for case-insensitive search
            $message_lower = strtolower($message);
            $banned_words_lower = array_map('strtolower', $banned_words);
        
            // Break the message into individual words
            $message_words = explode(' ', $message_lower);
        
            // Check if any word in the message is a banned word
            foreach ($message_words as $word) {
                if (in_array($word, $banned_words_lower)) {
                    $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);
                    $stream_ban_result = ['msg'    => esc_html__('Your message contains prohibited words. Please modify your message and try again.', 'gpt3-ai-content-generator'), 'messageFlagged' => true];
                    if ($stream_nav_setting == 1) {
                        header('Content-Type: text/event-stream');
                        header('Cache-Control: no-cache');
                        header( 'X-Accel-Buffering: no' );
                        echo "data: " . wp_json_encode($stream_ban_result) . "\n\n";
                        ob_implicit_flush( true );
                        // Flush and end buffer if it exists
                        if (ob_get_level() > 0) {
                            ob_end_flush();
                        }
                    } else {
                        wp_send_json(array(
                            'status' => 'error',
                            'msg'    => esc_html__('Your message contains prohibited words. Please modify your message and try again.', 'gpt3-ai-content-generator')
                        ));
                        exit;
                    }
                }
            }
        }

        public function anonymizeIp($ip) {
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
                // IPv4 example: Convert "192.168.1.123" to "192.168.1.0"
                $parts = explode('.', $ip);
                $parts[3] = '0'; // or 'xxx' if you prefer a non-valid IP format
                $ip = implode('.', $parts);
            } elseif (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                // IPv6 example: Only keep the first 4 groups and pad the rest
                $parts = explode(':', $ip);
                // Keep first 4 parts and replace remaining with "0000"
                $parts = array_pad(array_slice($parts, 0, 4), 8, '0000');
                $ip = implode(':', $parts);
            }
        
            return $ip;
        }
        
        public function getCurrentUsername() {
            if (is_user_logged_in()) {
                $current_user = wp_get_current_user();
                return $current_user->user_login; // Return the username of the logged-in user.
            } else {
                return null; // Return null if no user is logged in.
            }
        }
        

        function wpaicg_handle_logs_and_tokens($chat_log_id,$chat_log_data,$result_data,$total_tokens,$request,$embeddings_matches,$limited_tokens,$token_usage_client,$chat_token_id,$chat_source,$client_id,$wpdb) {
            // Save logs
            $this->wpaicg_save_chat_log(
                $chat_log_id,
                $chat_log_data,
                'ai',
                $result_data,
                $total_tokens,
                false,
                $request,
                $embeddings_matches
            );

            // Handle token usage
            if ($limited_tokens) {
                if ($chat_token_id) {
                    $wpdb->update(
                        $wpdb->prefix . 'wpaicg_chattokens',
                        ['tokens' => ($total_tokens + $token_usage_client)],
                        ['id' => $chat_token_id]
                    );
                } else {
                    $chat_token_data = [
                        'tokens' => $total_tokens,
                        'source' => $chat_source,
                        'created_at' => current_time('timestamp'),
                    ];
                    if (is_user_logged_in()) {
                        $chat_token_data['user_id'] = get_current_user_id();
                    } else {
                        $chat_token_data['session_id'] = $client_id;
                    }
                    $wpdb->insert($wpdb->prefix . 'wpaicg_chattokens', $chat_token_data);
                }
            }

            if (is_user_logged_in()) {
                WPAICG_Account::get_instance()->save_log('chat', $total_tokens);
            }
        }


        public function wpaicg_save_chat_log($wpaicg_log_id, $wpaicg_log_data, $type = 'user', $message = '', $tokens = 0, $flag = false, $request = '', $matches = array())
        {
            global $wpdb;
            if($wpaicg_log_id){
                $wpaicg_log_data[] = array('message' => $message, 'type' => $type, 'date' => time(), 'token' => $tokens, 'flag' => $flag, 'request' => $request,'matches' => $matches);
                $wpdb->update($wpdb->prefix.'wpaicg_chatlogs', array(
                    'data' => wp_json_encode($wpaicg_log_data,JSON_UNESCAPED_UNICODE),
                    'created_at' => current_time('timestamp')
                ), array(
                    'id' => $wpaicg_log_id
                ));
            }
        }

        public function wpaicg_embeddings_result($wpaicg_provider,$use_default_embedding,$selected_embedding_model,$selected_embedding_provider,$open_ai,$wpaicg_pinecone_api,$wpaicg_pinecone_environment,$wpaicg_message, $wpaicg_chat_embedding_top,$wpaicg_chat_source, $namespace = false, $confidence_score_threshold = 20)
        {
            $result = array('status' => 'error','data' => '');
            if(!empty($wpaicg_pinecone_api) && !empty($wpaicg_pinecone_environment) ) {

                // Determine the embedding engine and model
                $embedding_engine = $open_ai; // default engine
                $model = 'text-embedding-ada-002'; // default model

                if (isset($use_default_embedding) && $use_default_embedding != 1) {
                    // Custom embedding logic
                    if (!empty($selected_embedding_model) && !empty($selected_embedding_provider)) {
                        $model = $selected_embedding_model;
                        try {
                            $embedding_engine = WPAICG_Util::get_instance()->initialize_embedding_engine($selected_embedding_provider, $wpaicg_provider);
                        } catch (\Exception $e) {
                            $result['msg'] = $e->getMessage();
                            return $result;
                        }
                    }
                } else {
                    switch ($wpaicg_provider) {
                        case 'Azure':
                            $model = get_option('wpaicg_azure_embeddings', 'text-embedding-ada-002');
                            break;
                        case 'Google':
                            $model = get_option('wpaicg_google_embeddings', 'embedding-001');
                            break;
                        default:
                            $model = get_option('wpaicg_openai_embeddings', 'text-embedding-3-small');
                            break;
                    }
            
                    $main_embedding_model = get_option('wpaicg_main_embedding_model', '');
                    if (!empty($main_embedding_model)) {
                        $model_parts = explode(':', $main_embedding_model);
                        if (count($model_parts) === 2) {
                            $model = $model_parts[1];
                            try {
                                $embedding_engine = WPAICG_Util::get_instance()->initialize_embedding_engine($model_parts[0], $wpaicg_provider);
                            } catch (\Exception $e) {
                                $result['msg'] = $e->getMessage();
                                return $result;
                            }
                        }
                    }
                }

                // Prepare the API call parameters
                $apiParams = [
                    'input' => $wpaicg_message,
                    'model' => $model
                ];

                // Make the API call
                $response = $embedding_engine->embeddings($apiParams);
                $response = json_decode($response, true);
                if (isset($response['error']) && !empty($response['error'])) {
                    $result['data'] = $response['error']['message'];
                    if(empty($result['data']) && isset($response['error']['code']) && $response['error']['code'] == 'invalid_api_key'){
                        $result['data'] = 'Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.';
                    }
                } else {
                    $embedding = $response['data'][0]['embedding'];
                    if (!empty($embedding)) {
                        $result['tokens'] = $response['usage']['total_tokens'];
                        $headers = array(
                            'Content-Type' => 'application/json',
                            'Api-Key' => $wpaicg_pinecone_api
                        );
                        $pinecone_body = array(
                            'vector' => $embedding,
                            'topK' => $wpaicg_chat_embedding_top
                        );
                        if($namespace){
                            $pinecone_body['namespace'] = $namespace;
                        }
                        $response = wp_remote_post('https://' . $wpaicg_pinecone_environment . '/query', array(
                            'headers' => $headers,
                            'body' => wp_json_encode($pinecone_body)
                        ));

                        if (is_wp_error($response)) {
                            $result['data'] = esc_html($response->get_error_message());
                        } else {
                            $body_content = wp_remote_retrieve_body($response);
                            $body = json_decode($response['body'], true);
                            if ($body) {
                                if (isset($body['matches']) && is_array($body['matches']) && count($body['matches'])) {
                                    $data = '';
                                    $matches = [];
                                    foreach ($body['matches'] as $match) {
                                        if ($match['score'] >= $confidence_score_threshold / 100) {
                                            $wpaicg_embedding = get_post($match['id']);
                                            if ($wpaicg_embedding) {
                                                $data .= empty($data) ? $wpaicg_embedding->post_content : "\n" . $wpaicg_embedding->post_content;
                                                $matches[] = [
                                                    'id' => $match['id'],
                                                    'score' => number_format($match['score'], 4) // Keep only 4 digits after the decimal point
                                                ];
                                            }
                                        }
                                    }
                                    $result['data'] = $data;
                                    $result['matches'] = $matches;
                                    $result['status'] = 'success';
                                }                                
                                else{
                                    $result['status'] = 'empty';
                                }
                            }
                            else{
                                $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);
                                $stream_pinecone_error = ['msg'    => esc_html__($body_content, 'gpt3-ai-content-generator'), 'pineconeError' => true];
                                if ($stream_nav_setting == 1) {
                                    header('Content-Type: text/event-stream');
                                    header('Cache-Control: no-cache');
                                    header( 'X-Accel-Buffering: no' );
                                    echo "data: " . wp_json_encode($stream_pinecone_error) . "\n\n";
                                    ob_implicit_flush( true );
                                    // Flush and end buffer if it exists
                                    if (ob_get_level() > 0) {
                                        ob_end_flush();
                                    }
                                    exit;
                                } else {
                                    $result['data'] = $body_content ? $body_content : esc_html__('No results from Pinecone.','gpt3-ai-content-generator');
                                }
                            }
                        }
                    }
                }
            }
            else{
                $result['data'] = esc_html__('Something wrong with Pinecone setup. Check your Pinecone settings.','gpt3-ai-content-generator');
            }
            return $result;
        }
        public function wpaicg_embeddings_result_qdrant($wpaicg_provider, $use_default_embedding,$selected_embedding_model,$selected_embedding_provider,$open_ai, $wpaicg_qdrant_api_key, $wpaicg_qdrant_endpoint, $wpaicg_chat_qdrant_collection, $wpaicg_message, $wpaicg_chat_source,$wpaicg_chat_embedding_top, $namespace = false, $confidence_score_threshold = 20)
        {
            $result = array('status' => 'error','data' => '');
            if(!empty($wpaicg_qdrant_api_key) && !empty($wpaicg_qdrant_endpoint && !empty($wpaicg_chat_qdrant_collection))) {

                // Determine the embedding engine and model
                $embedding_engine = $open_ai; // default engine
                $model = 'text-embedding-ada-002'; // default model

                if (isset($use_default_embedding) && $use_default_embedding != 1) {
                    // Custom embedding logic
                    if (!empty($selected_embedding_model) && !empty($selected_embedding_provider)) {
                        $model = $selected_embedding_model;
                        try {
                            $embedding_engine = WPAICG_Util::get_instance()->initialize_embedding_engine($selected_embedding_provider, $wpaicg_provider);
                        } catch (\Exception $e) {
                            $result['msg'] = $e->getMessage();
                            return $result;
                        }
                    }
                } else {
                    // Use default embedding logic
                    switch ($wpaicg_provider) {
                        case 'Azure':
                            $model = get_option('wpaicg_azure_embeddings', 'text-embedding-ada-002');
                            break;
                        case 'Google':
                            $model = get_option('wpaicg_google_embeddings', 'embedding-001');
                            break;
                        default:
                            $model = get_option('wpaicg_openai_embeddings', 'text-embedding-3-small');
                            break;
                    }
            
                    $main_embedding_model = get_option('wpaicg_main_embedding_model', '');
                    if (!empty($main_embedding_model)) {
                        $model_parts = explode(':', $main_embedding_model);
                        if (count($model_parts) === 2) {
                            $model = $model_parts[1];
                            try {
                                $embedding_engine = WPAICG_Util::get_instance()->initialize_embedding_engine($model_parts[0], $wpaicg_provider);
                            } catch (\Exception $e) {
                                $result['msg'] = $e->getMessage();
                                return $result;
                            }
                        }
                    }
                }

                // Prepare the API call parameters
                $apiParams = [
                    'input' => $wpaicg_message,
                    'model' => $model
                ];

                // Make the API call
                $response = $embedding_engine->embeddings($apiParams);
                $response = json_decode($response, true);
                if (isset($response['error']) && !empty($response['error'])) {
                    $result['data'] = $response['error']['message'];
                    if(empty($result['data']) && isset($response['error']['code']) && $response['error']['code'] == 'invalid_api_key'){
                        $result['data'] = 'Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.';
                    }
                } else {
                    $embedding = $response['data'][0]['embedding'];
                    if (!empty($embedding)) {
                        $result['tokens'] = $response['usage']['total_tokens'];
                        // Prepare Qdrant search query
                        $queryData = [
                            'vector' => $embedding,
                            'limit' => intval($wpaicg_chat_embedding_top)
                        ];
                        
                        // Use namespace if it exists and is not empty; otherwise, use a fixed "default" string
                        $group_id_value = $namespace ?: "default";

                        $queryData['filter'] = [
                            'must' => [
                                [
                                    'key' => 'group_id',
                                    'match' => [
                                        'value' => $group_id_value
                                    ]
                                ]
                            ]
                        ];

                        $query = wp_json_encode($queryData);

                        // Send request to Qdrant
                        $response = wp_remote_post($wpaicg_qdrant_endpoint . '/collections/' . $wpaicg_chat_qdrant_collection . '/points/search', array(
                            'method' => 'POST',
                            'headers' => [
                                'api-key' => $wpaicg_qdrant_api_key,
                                'Content-Type' => 'application/json'
                            ],
                            'body' => $query
                        ));
                        if (is_wp_error($response)) {
                            $result['data'] = esc_html($response->get_error_message());
                        } else {
                            $bodyContent = wp_remote_retrieve_body($response);
                            $body = json_decode($bodyContent, true);
                            if (isset($body['result']) && is_array($body['result'])) {
                                $data = '';
                                $matches = [];
                                foreach ($body['result'] as $match) {
                                    if ($match['score'] >= $confidence_score_threshold / 100) {
                                        $wpaicg_embedding = get_post($match['id']);
                                        if ($wpaicg_embedding) {
                                            $data .= empty($data) ? $wpaicg_embedding->post_content : "\n" . $wpaicg_embedding->post_content;
                                            $matches[] = [
                                                'id' => $match['id'],
                                                'score' => number_format($match['score'], 4) // Keep only 4 digits after the decimal point
                                            ];
                                        }
                                    }
                                }
                                $result['data'] = $data;
                                $result['matches'] = $matches;
                                $result['status'] = 'success';
                            } else {
                                $errror_message_from_api = isset($body['status']['error']) ? $body['status']['error'] : esc_html__('No results from Qdrant.', 'gpt3-ai-content-generator');
                                $errror_message_from_api = esc_html__('Response from Qdrant: ', 'gpt3-ai-content-generator') . $errror_message_from_api;
                                $result['status'] = 'error';
                                $stream_nav_setting = $this->determine_stream_nav_setting($wpaicg_chat_source, $wpaicg_provider);
                                $stream_pinecone_error = ['msg'    => $errror_message_from_api, 'pineconeError' => true];
                                if ($stream_nav_setting == 1) {
                                    header('Content-Type: text/event-stream');
                                    header('Cache-Control: no-cache');
                                    header( 'X-Accel-Buffering: no' );
                                    echo "data: " . wp_json_encode($stream_pinecone_error) . "\n\n";
                                    ob_implicit_flush( true );
                                    // Flush and end buffer if it exists
                                    if (ob_get_level() > 0) {
                                        ob_end_flush();
                                    }
                                    exit;
                                } else {
                                    $result['data'] = $errror_message_from_api;
                                }
                            }
                        }
                    }
                }
            }
            else{
                $result['data'] = esc_html__('Something wrong with Qdrant setup3. Check your Qdrant settings.','gpt3-ai-content-generator');
            }
            return $result;
        }


        public function wpaicg_chatbox($atts)
        {
            ob_start();
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_chatbox.php';
            $wpaicg_chatbox = ob_get_clean();
            return $wpaicg_chatbox;
        }

        public function wpaicg_chatbox_widget($atts) {
            // Extract shortcode attributes, defaulting 'id' to empty
            $atts = shortcode_atts( array(
                'id' => '',
            ), $atts, 'wpaicg_chatgpt_widget' );
        
            $wpaicg_bot_id = $atts['id'];
        
            if (!empty($wpaicg_bot_id)) {
                // Load bot data from post with ID $wpaicg_bot_id
                $bot_post = get_post($wpaicg_bot_id);
                if ($bot_post && $bot_post->post_type === 'wpaicg_chatbot') {
                    $bot_data = json_decode($bot_post->post_content, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($bot_data)) {
                        // Set $wpaicg_chat_widget and $wpaicg_chat_status variables
                        $wpaicg_chat_widget = $bot_data;
                        $wpaicg_chat_status = 'active';
                        // Optionally, set other variables used in wpaicg_chatbox_widget.php
                    }
                }
            } else {
                // Fallback to default site-wide widget settings if 'id' is not provided
                $wpaicg_chat_widget = get_option('wpaicg_chat_widget', []);
                $wpaicg_chat_status = isset($wpaicg_chat_widget['status']) && !empty($wpaicg_chat_widget['status']) ? $wpaicg_chat_widget['status'] : '';
            }
        
            ob_start();
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_chatbox_widget.php';
            $wpaicg_chatbox = ob_get_clean();
            return $wpaicg_chatbox;
        }
        
    }
    WPAICG_Chat::get_instance();
}
