<?php
declare(strict_types=1);

namespace WPAICG;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if ( ! class_exists('\\WPAICG\\WPAICG_Playground')) {

    class WPAICG_Playground {

        /**
         * The singleton instance.
         *
         * @var null|WPAICG_Playground
         */
        private static $instance = null;

        /**
         * The configured AI provider, e.g. "OpenAI", "Google", "Azure", etc.
         *
         * @var string
         */
        private string $provider;

        /**
         * Retrieve the singleton instance of this class.
         *
         * @return WPAICG_Playground
         */
        public static function get_instance() {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        /**
         * Constructor hooks needed actions.
         */
        public function __construct() {
            /**
             * We store the default provider once here.
             * It may be overridden later in wpaicg_stream() if
             * the request originates from an AI form (source_stream === 'form').
             */
            $this->provider = get_option('wpaicg_provider', 'OpenAI');

            add_action('init', [$this, 'wpaicg_stream'], 1);
            add_action('wp_ajax_wpaicg_generate_content_google', [$this, 'wpaicg_generate_content_google']);
            add_action('wp_ajax_save_wpaicg_google_api_key', [$this, 'save_wpaicg_google_api_key']);
            add_action('wp_ajax_save_wpaicg_togetherai_api_key', [$this, 'save_wpaicg_togetherai_api_key']);
            add_action('wp_ajax_wpaicg_generate_content_togetherai', [$this, 'wpaicg_generate_content_togetherai']);
        }

        /**
         * Handles saving the Together AI API key.
         */
        public function save_wpaicg_togetherai_api_key() {
            check_ajax_referer('wpaicg-save-togetherai-api', 'nonce');

            // Check if the current user has the capability to manage options
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            $apiKey = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
            update_option('wpaicg_togetherai_model_api_key', $apiKey);

            wp_send_json_success(['message' => 'API key saved successfully']);
        }

        /**
         * Handles saving the Google API key.
         */
        public function save_wpaicg_google_api_key() {
            check_ajax_referer('wpaicg-save-google-api', 'nonce');

            // Check if the current user has the capability to manage options
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
                return;
            }

            $apiKey = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
            update_option('wpaicg_google_model_api_key', $apiKey);

            wp_send_json_success(['message' => 'API key saved successfully']);
        }

        /**
         * AJAX callback for generating content via Google.
         */
        public function wpaicg_generate_content_google() {
            check_ajax_referer('wpaicg_generate_content_google', 'nonce');

            $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';

            $response = $this->send_google_request($title, $model);
            wp_send_json_success(['content' => $response]);
        }
        
        /**
         * Actually sends the request to the Google API endpoint.
         *
         * @param string $title
         * @param string $model
         * @return string
         */
        private function send_google_request($title, $model) {
            $userPrompt = $title;
            $apiKey     = get_option('wpaicg_google_model_api_key', '');
            if (empty($apiKey)) {
                return 'Error: Google API key is not set';
            }

            // Dynamically construct the URL using the model name
            $url = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}";

            $args = array(
                'headers' => array('Content-Type' => 'application/json'),
                'method'  => 'POST',
                'timeout' => 300,
                'body'    => json_encode([
                    "contents"         => [
                        ["role" => "user", "parts" => [["text" => $userPrompt]]]
                    ],
                    "generationConfig" => [
                        "temperature"     => 0.9,
                        "topK"            => 1,
                        "topP"            => 1,
                        "maxOutputTokens" => 2048,
                        "stopSequences"   => []
                    ],
                    "safetySettings"   => [
                        ["category" => "HARM_CATEGORY_HARASSMENT", "threshold" => "BLOCK_MEDIUM_AND_ABOVE"],
                    ]
                ])
            );

            $response = wp_safe_remote_post($url, $args);

            if (is_wp_error($response)) {
                return 'HTTP request error: ' . $response->get_error_message();
            }

            $body            = wp_remote_retrieve_body($response);
            $decodedResponse = json_decode($body, true);

            if (isset($decodedResponse['error'])) {
                $errorMsg = $decodedResponse['error']['message'] ?? 'Unknown error from Google API';
                return 'Error: ' . $errorMsg;
            }

            // Check the expected response structure based on the API documentation
            if (isset($decodedResponse['candidates'][0]['content']['parts'][0]['text'])) {
                return $decodedResponse['candidates'][0]['content']['parts'][0]['text'];
            } else {
                return 'Error: Invalid response from Google API';
            }
        }
        
        /**
         * AJAX callback for generating content via Together AI.
         */
        public function wpaicg_generate_content_togetherai() {
            check_ajax_referer('wpaicg_generate_content_togetherai', 'nonce');

            $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';

            $response = $this->send_togetherai_request($title, $model);
            wp_send_json_success(['content' => $response]);
        }
        
        /**
         * Sends the request to the Together AI endpoint.
         *
         * @param string $title
         * @param string $model
         * @return string
         */
        private function send_togetherai_request($title, $model) {
            $apiKey = get_option('wpaicg_togetherai_model_api_key', '');
            if (empty($apiKey)) {
                return 'Error: Together AI API key is not set';
            }

            $url  = "https://api.together.xyz/inference";
            $args = [
                'method'  => 'POST',
                'timeout' => 300,
                'headers' => [
                    'Content-Type'  => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey
                ],
                'body'    => json_encode([
                    "model"              => $model,
                    "max_tokens"         => 2000,
                    "prompt"             => $title,
                    "request_type"       => "language-model-inference",
                    "temperature"        => 0.7,
                    "top_p"              => 0.7,
                    "top_k"              => 50,
                    "repetition_penalty" => 1,
                    "stream_tokens"      => true,
                    "stop"               => [ "</s>", "[INST]" ],
                    "negative_prompt"    => "",
                    "sessionKey"         => "your_session_key",
                    "repetitive_penalty" => 1,
                    "update_at"          => current_time('c')
                ])
            ];

            $response = wp_safe_remote_post($url, $args);
            if (is_wp_error($response)) {
                return 'HTTP request error: ' . $response->get_error_message();
            }

            $body = wp_remote_retrieve_body($response);
            return $this->process_stream_response($body);
        }
        
        /**
         * Parses the streaming JSON data from Together AI.
         *
         * @param string $body
         * @return string
         */
        private function process_stream_response($body) {
            $lines    = explode("\n", $body);
            $fullText = '';

            foreach ($lines as $line) {
                if (strpos($line, 'data: ') === 0) {
                    $jsonString = substr($line, 6); // Remove 'data: ' prefix
                    $data       = json_decode($jsonString, true);
                    if (isset($data['choices'][0]['text'])) {
                        $fullText .= $data['choices'][0]['text'];
                    }
                }
            }

            return $fullText;
        }

        /**
         * Handles token limits for different sources.
         *
         * @param string $source
         * @return array
         */
        public function wpaicg_token_handling($source) {
            global $wpdb;
            $result                = [];
            $result['message']     = esc_html__('You have reached your token limit.','gpt3-ai-content-generator');
            $result['table']       = 'wpaicg_formtokens';
            $result['limit']       = false;
            $result['tokens']      = 0;
            $result['source']      = $source;
            $result['token_id']    = false;
            $result['limited']     = false;
            $result['old_tokens']  = 0;

            if (!is_user_logged_in()) {
                $wpaicg_client_id     = $this->wpaicg_get_cookie_id($source);
            } else {
                $wpaicg_client_id     = false;
            }
            $result['client_id'] = $wpaicg_client_id;

            if ($result['source'] === 'promptbase') {
                $result['table'] = 'wpaicg_prompttokens';
            }
            if ($result['source'] === 'image') {
                $result['table'] = 'wpaicg_imagetokens';
            }

            $wpaicg_settings = get_option('wpaicg_limit_tokens_' . $result['source'], []);
            $result['message'] = isset($wpaicg_settings['limited_message']) && !empty($wpaicg_settings['limited_message'])
                ? wp_unslash($wpaicg_settings['limited_message'])
                : $result['message'];

            // Check user-based limits
            if (
                is_user_logged_in()
                && isset($wpaicg_settings['user_limited'])
                && $wpaicg_settings['user_limited']
                && $wpaicg_settings['user_tokens'] > 0
            ) {
                $result['limit']  = true;
                $result['tokens'] = $wpaicg_settings['user_tokens'];
            }

            // Check role-based limits
            if (
                is_user_logged_in()
                && isset($wpaicg_settings['role_limited'])
                && $wpaicg_settings['role_limited']
            ) {
                $wpaicg_roles = (array) wp_get_current_user()->roles;
                $limited_current_role = 0;
                foreach ($wpaicg_roles as $wpaicg_role) {
                    if (
                        isset($wpaicg_settings['limited_roles'])
                        && is_array($wpaicg_settings['limited_roles'])
                        && isset($wpaicg_settings['limited_roles'][$wpaicg_role])
                        && $wpaicg_settings['limited_roles'][$wpaicg_role] > $limited_current_role
                    ) {
                        $limited_current_role = $wpaicg_settings['limited_roles'][$wpaicg_role];
                    }
                }
                if ($limited_current_role > 0) {
                    $result['limit']  = true;
                    $result['tokens'] = $limited_current_role;
                } else {
                    $result['limit'] = false;
                }
            }

            // Guest-based limits
            if (
                !is_user_logged_in()
                && isset($wpaicg_settings['guest_limited'])
                && $wpaicg_settings['guest_limited']
                && $wpaicg_settings['guest_tokens'] > 0
            ) {
                $result['limit']  = true;
                $result['tokens'] = $wpaicg_settings['guest_tokens'];
            }

            // If there's an overall limit set, check current token usage
            if ($result['limit']) {
                if (is_user_logged_in()) {
                    $wpaicg_chat_token_log = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}{$result['table']} WHERE user_id=%d",
                            get_current_user_id()
                        )
                    );
                } else {
                    $wpaicg_chat_token_log = $wpdb->get_row(
                        $wpdb->prepare(
                            "SELECT * FROM {$wpdb->prefix}{$result['table']} WHERE session_id=%s",
                            $wpaicg_client_id
                        )
                    );
                }

                $result['old_tokens'] = $wpaicg_chat_token_log ? $wpaicg_chat_token_log->tokens : 0;
                $wpaicg_chat_token_id = $wpaicg_chat_token_log ? $wpaicg_chat_token_log->id : false;

                if (
                    $result['old_tokens'] > 0
                    && $result['tokens'] > 0
                    && $result['old_tokens'] > $result['tokens']
                ) {
                    $result['limited']  = true;
                    $result['token_id'] = $wpaicg_chat_token_id;
                    $result['left_tokens'] = 0;
                } else {
                    $result['left_tokens'] = $result['tokens'] - $result['old_tokens'];
                    $result['token_id']    = $wpaicg_chat_token_id;
                    $result['limited']     = false;
                }

                // Check if logged user has limit tokens in user_meta
                if (is_user_logged_in()) {
                    $user_meta_key = 'wpaicg_' . ($result['source'] === 'form' ? 'forms' : $result['source']) . '_tokens';
                    $user_tokens   = get_user_meta(get_current_user_id(), $user_meta_key, true);
                    $result['left_tokens'] += (float) $user_tokens;
                }

                // If system says "limited," but user has user-meta tokens left
                if ($result['limited'] && is_user_logged_in()) {
                    if (!empty($user_tokens) && $user_tokens > 0) {
                        $result['limited'] = false;
                    }
                }
            }

            return $result;
        }

        /**
         * Retrieve the final prompt text by merging user inputs into placeholders.
         *
         * @param int $post_id
         * @return string
         */
        public function get_defined_prompt($post_id) {
            $form_fields     = get_post_meta($post_id, 'wpaicg_form_fields', true);
            $defined_prompt  = get_post_meta($post_id, 'wpaicg_form_prompt', true);

            if (empty($form_fields) || empty($defined_prompt)) {
                if (file_exists(WPAICG_PLUGIN_DIR . 'admin/data/gptforms.json')) {
                    $forms_data = json_decode(file_get_contents(WPAICG_PLUGIN_DIR . 'admin/data/gptforms.json'), true);
                    foreach ($forms_data as $form) {
                        if (isset($form['id']) && $form['id'] == $post_id) {
                            $form_fields    = json_encode($form['fields']);
                            $defined_prompt = $form['prompt'];
                            break;
                        }
                    }
                }
            }

            // Function to fix common JSON issues
            function fix_json($json) {
                // Remove BOM
                $json = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', $json);
                // Fix escaped quotes
                $json = str_replace("\\'", "'", $json);
                // Ensure double quotes for property names
                $json = preg_replace('/(\w+)(?=\s*:)/','"$1"',$json);
                return $json;
            }

            $decoded_fields = json_decode($form_fields, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $fixed_json     = fix_json($form_fields);
                $decoded_fields = json_decode($fixed_json, true);

                if (json_last_error() !== JSON_ERROR_NONE) {
                    return "Error: Unable to process form fields.";
                }
            }

            if (!is_array($decoded_fields)) {
                return "Error: Invalid form structure.";
            }

            $field_values = [];
            // Handle user-submitted data for each field
            foreach ($decoded_fields as $field) {
                if (isset($field['id'], $_REQUEST[$field['id']])) {
                    if (is_array($_REQUEST[$field['id']])) {
                        // e.g. multiple checkboxes
                        $values = array_map('sanitize_text_field', $_REQUEST[$field['id']]);
                        $field_values[$field['id']] = implode(', ', $values);
                    } else {
                        // Single value (text, fileupload, etc.)
                        $rawValue = sanitize_text_field($_REQUEST[$field['id']]);
                        // If it references our transient key, retrieve the actual content
                        if (strpos($rawValue, 'wpaicg_upload_') === 0) {
                            $fileData = get_transient($rawValue);
                            if ($fileData !== false) {
                                $field_values[$field['id']] = $fileData;
                            } else {
                                // If the transient is missing/expired, store as empty
                                $field_values[$field['id']] = '';
                            }
                        } else {
                            $field_values[$field['id']] = $rawValue;
                        }
                    }
                }
            }

            // Replace placeholders {field_id} with user-submitted values
            foreach ($field_values as $key => $value) {
                $defined_prompt = str_replace('{' . $key . '}', $value, $defined_prompt);
            }

            return $defined_prompt;
        }

        /**
         * Action to handle streaming responses from AI providers.
         * Primarily used for SSE (Server-Sent Events) output.
         */
        public function wpaicg_stream()
        {
            if (isset($_GET['wpaicg_stream']) && sanitize_text_field($_GET['wpaicg_stream']) === 'yes') {
                global $wpdb;
        
                header('Content-type: text/event-stream');
                header('Cache-Control: no-cache');
        
                if (! wp_verify_nonce($_REQUEST['nonce'], 'wpaicg-ajax-nonce')) {
                    $wpaicg_error_message = esc_html__('Nonce verification failed', 'gpt3-ai-content-generator');
                    $this->wpaicg_event_message($wpaicg_error_message);
                    exit;
                }
        
                $source = isset($_REQUEST['source_stream']) ? sanitize_text_field($_REQUEST['source_stream']) : '';
        
                $wpaicg_prompt = '';
                $post_id       = 0;
        
                // Playground & Promptbase
                if ($source === 'playground' || $source === 'promptbase') {
                    if (isset($_REQUEST['title']) && !empty($_REQUEST['title'])) {
                        $wpaicg_prompt = sanitize_text_field($_REQUEST['title']);
                    }
                } else {
                    // AI Forms
                    if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                        $post_id       = intval($_REQUEST['id']);
                        $wpaicg_prompt = $this->get_defined_prompt($post_id);
                    }
                }
        
                if (!$wpaicg_prompt) {
                    exit;
                }
        
                // ------------------------------------
                // 1) Check if user enabled Internet Browsing for this form/prompt
                //    If yes, fetch Google search results and append to prompt
                // ------------------------------------
                if ($source === 'form' && !empty($post_id)) {
                    $internet_browsing = get_post_meta($post_id, 'wpaicg_form_internet_browsing', true);
                    if ($internet_browsing === 'yes') {
                        $googleSearch = $this->handle_internet_search($wpaicg_prompt);
                        if (!empty($googleSearch)) {
                            // Append the search results
                            $wpaicg_prompt .= "\n" . $googleSearch;
                        }
                    }
                } elseif ($source === 'promptbase' && !empty($post_id)) {
                    // If you also support “internet” for Promptbase, 
                    // you could do something similar here, e.g.:
                    // $internet_browsing = get_post_meta($post_id, 'wpaicg_prompt_internet_browsing', true);
                    // if ($internet_browsing === 'yes') { ... }
                }
        
                // ------------------------------------
                // 2) Check if embeddings are enabled
                // ------------------------------------
                $embeddingsDetails = $this->get_embeddings_details();
                if ($embeddingsDetails['embeddingsEnabled']) {
                    $wpaicg_prompt = $this->handle_embeddings($wpaicg_prompt, $embeddingsDetails);
                }
        
                // ------------------------------------
                // 3) Initialize the correct AI provider
                //    (possibly overridden if source=‘form’ or ‘promptbase’)
                // ------------------------------------
                try {
                    // If the request is from an AI form, we override $this->provider
                    if ($source === 'form' && !empty($post_id)) {
                        $providerFromMeta = get_post_meta($post_id, 'wpaicg_form_model_provider', true);
                        if (!empty($providerFromMeta)) {
                            $this->provider = $providerFromMeta;
                        }
                    } elseif ($source === 'promptbase' && !empty($post_id)) {
                        $providerFromMeta = get_post_meta($post_id, 'wpaicg_prompt_model_provider', true);
                        if (!empty($providerFromMeta)) {
                            $this->provider = $providerFromMeta;
                        }
                    }
        
                    $ai_engine = \WPAICG\WPAICG_Util::get_instance()->initialize_ai_engine($this->provider);
                } catch (\Exception $e) {
                    $wpaicg_result['msg'] = $e->getMessage();
                    wp_send_json($wpaicg_result);
                }
        
                if (!$ai_engine) {
                    exit;
                }
        
                // ------------------------------------
                // 4) Check if user is token-limited
                // ------------------------------------
                $has_limited = false;
                if (in_array($source, ['promptbase','form'], true)) {
                    $wpaicg_token_handling = $this->wpaicg_token_handling($source);
                    if ($wpaicg_token_handling['limited']) {
                        $has_limited = true;
                        $this->wpaicg_event_message($wpaicg_token_handling['message']);
                    }
                }
        
                // ------------------------------------
                // 5) If not limited, proceed with SSE
                // ------------------------------------
                if (!$has_limited) {
                    // If the provider is Google, handle it differently
                    if ($this->provider === 'Google') {
                        $google_model = (isset($_REQUEST['engine']) && !empty($_REQUEST['engine']))
                            ? sanitize_text_field($_REQUEST['engine'])
                            : get_option('wpaicg_google_default_model', 'gemini-pro');
        
                        $wpaicg_args = [
                            'messages'    => [
                                ['content' => $wpaicg_prompt]
                            ],
                            'model'       => $google_model,
                            'temperature' => 0.9,
                            'top_p'       => 1,
                            'max_tokens'  => 2048,
                            'sourceModule'=> 'form'
                        ];
                        $response = $ai_engine->chat($wpaicg_args);
                        $words    = [];
                        if (isset($response['error']) && !empty($response['error'])) {
                            $words = explode(' ', $response['error']);
                        } else {
                            $words = explode(' ', $response['data']);
                        }
                        foreach ($words as $key => $word) {
                            echo "event: message\n";
                            if ($key === 0) {
                                echo 'data: {"choices":[{"delta":{"content":"' . $word . '"}}]}';
                            } else {
                                echo 'data: {"choices":[{"delta":{"content":" ' . $word . '"}}]}';
                            }
                            echo "\n\n";
                            if (ob_get_level() > 0) {
                                ob_end_flush();
                            }
                            flush();
                        }
                        echo 'data: [DONE]' . "\n\n";
                        if (ob_get_length()) {
                            ob_flush();
                            flush();
                        }
                    } else {
                        // For OpenAI / Azure / OpenRouter
                        $wpaicg_args = $this->build_ai_args($ai_engine, $wpaicg_prompt);
                        $legacy_models = [
                            'text-davinci-001','davinci','babbage','text-babbage-001','curie-instruct-beta','text-davinci-003',
                            'text-curie-001','davinci-instruct-beta','text-davinci-002','ada','text-ada-001','curie','gpt-3.5-turbo-instruct'
                        ];
                        if (!in_array($wpaicg_args['model'], $legacy_models, true)) {
                            // Chat endpoint
                            unset($wpaicg_args['best_of']);
                            $wpaicg_args['messages'] = [
                                ['role' => 'user', 'content' => $wpaicg_args['prompt']]
                            ];
                            unset($wpaicg_args['prompt']);
                            try {
                                $ai_engine->chat($wpaicg_args, function ($curl_info, $data) {
                                    $this->handle_sse_callback($data);
                                    return strlen($data);
                                });
                            } catch (\Exception $exception) {
                                $message = $exception->getMessage();
                                $this->wpaicg_event_message($message);
                            }
                        } else {
                            // Legacy completion endpoint
                            try {
                                $ai_engine->completion($wpaicg_args, function ($curl_info, $data) {
                                    $this->handle_sse_callback($data);
                                    return strlen($data);
                                });
                            } catch (\Exception $exception) {
                                $message = $exception->getMessage();
                                $this->wpaicg_event_message($message);
                            }
                        }
                    }
                }
                exit;
            }
        }

        /**
         * If "internet browsing" is enabled for this AI Form,
         * perform a Google search with the user’s prompt and return results.
         * This is appended to the main $wpaicg_prompt before sending to the AI.
         */
        private function handle_internet_search(string $prompt): string
        {
            // Retrieve necessary settings
            $google_api_key          = get_option('wpaicg_google_api_key', '');
            $google_search_engine_id = get_option('wpaicg_google_search_engine_id', '');
            if (empty($google_api_key) || empty($google_search_engine_id)) {
                // If no API key or engine ID, simply return nothing
                return '';
            }

            // Sanitize the search query
            $search_query = sanitize_text_field($prompt);

            // Execute the search
            $results = $this->wpaicg_search_internet($google_api_key, $google_search_engine_id, $search_query);

            if ($results['status'] === 'success' && !empty($results['data'])) {
                // Return raw string of results, optionally label them
                return "\n" . esc_html__('Internet Search Results:', 'gpt3-ai-content-generator') 
                    . "\n" . $results['data'];
            }

            return '';
        }

        /**
         * Perform a Google CSE (Custom Search Engine) call using the provided API key, engine ID, and query.
         * Returns an array with ['status'=>'success','data'=>'...'] or ['status'=>'error','data'=>''].
         */
        public function wpaicg_search_internet(string $api_key, string $search_engine_id, string $query): array
        {
            $country    = get_option('wpaicg_google_search_country', '');
            $num_results= get_option('wpaicg_google_search_num', 10);
            $language   = get_option('wpaicg_google_search_language', '');

            $search_url = 'https://www.googleapis.com/customsearch/v1?q=' . urlencode($query)
                        . '&key=' . $api_key
                        . '&cx=' . $search_engine_id;

            if (!empty($country)) {
                $search_url .= '&cr=' . urlencode($country);
            }
            if (!empty($language)) {
                $search_url .= '&lr=' . urlencode($language);
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
                // Build a short textual snippet from each result
                foreach ($data['items'] as $item) {
                    $title   = isset($item['title'])   ? $item['title']   : '';
                    $snippet = isset($item['snippet']) ? $item['snippet'] : '';
                    $link    = isset($item['link'])    ? $item['link']    : '';
                    $search_content .= $title . "\n" . $snippet . "\n" . $link . "\n\n";
                }
                return [
                    'status' => 'success',
                    'data'   => $search_content
                ];
            }

            return ['status' => 'empty', 'data' => ''];
        }

        /**
         * Helper to fetch embeddings details if provided in form/prompt meta.
         *
         * @return array
         */
        public function get_embeddings_details() {
            // Must check presence of required params
            if (
                isset($_REQUEST['id'], $_REQUEST['source_stream'])
                && !empty($_REQUEST['id'])
                && in_array($_REQUEST['source_stream'], ['promptbase','form'], true)
            ) {
                $wpaicg_post_id = intval($_REQUEST['id']);
                $source         = $_REQUEST['source_stream'];
                if ($source === 'form') {
                    $embeddingsEnabled           = (get_post_meta($wpaicg_post_id, 'wpaicg_form_embeddings', true) === 'yes');
                    $use_default_embedding_model = get_post_meta($wpaicg_post_id, 'wpaicg_form_use_default_embedding_model', true);
                    $selected_embedding_model    = get_post_meta($wpaicg_post_id, 'wpaicg_form_selected_embedding_model', true);
                    $selected_embedding_provider = get_post_meta($wpaicg_post_id, 'wpaicg_form_selected_embedding_provider', true);
                    $context_suffix              = get_post_meta($wpaicg_post_id, 'wpaicg_form_suffix_text', true);
                    $context_suffix_position     = get_post_meta($wpaicg_post_id, 'wpaicg_form_suffix_position', true);
                    $embeddings_limit            = get_post_meta($wpaicg_post_id, 'wpaicg_form_embeddings_limit', true);
                    $vectordb                    = get_post_meta($wpaicg_post_id, 'wpaicg_form_vectordb', true);

                    $collectionsOrIndexes = '';
                    if ($vectordb === 'qdrant') {
                        $collectionsOrIndexes = get_post_meta($wpaicg_post_id, 'wpaicg_form_collections', true);
                    } elseif ($vectordb === 'pinecone') {
                        $collectionsOrIndexes = get_post_meta($wpaicg_post_id, 'wpaicg_form_pineconeindexes', true);
                    }
                } else {
                    $embeddingsEnabled           = (get_post_meta($wpaicg_post_id, 'wpaicg_prompt_embeddings', true) === 'yes');
                    $use_default_embedding_model = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_use_default_embedding_model', true);
                    $selected_embedding_model    = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_selected_embedding_model', true);
                    $selected_embedding_provider = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_selected_embedding_provider', true);
                    $context_suffix              = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_suffix_text', true);
                    $context_suffix_position     = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_suffix_position', true);
                    $embeddings_limit            = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_embeddings_limit', true);
                    $vectordb                    = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_vectordb', true);

                    $collectionsOrIndexes = '';
                    if ($vectordb === 'qdrant') {
                        $collectionsOrIndexes = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_collections', true);
                    } elseif ($vectordb === 'pinecone') {
                        $collectionsOrIndexes = get_post_meta($wpaicg_post_id, 'wpaicg_prompt_pineconeindexes', true);
                    }
                }

                if(empty($embeddings_limit)){
                    $embeddings_limit = 1;
                }
        
                // Disable embeddings if provider is OpenRouter
                if ($this->provider === 'OpenRouter') {
                    $embeddingsEnabled = false;
                }

                // If embeddings are enabled, return vectordb and collections or indexes meta values
                if ($embeddingsEnabled) {

                    return [
                        'embeddingsEnabled'           => true,
                        'vectordb'                    => $vectordb,
                        'collections'                 => $collectionsOrIndexes,
                        'context_suffix'              => $context_suffix,
                        'context_suffix_position'     => $context_suffix_position,
                        'embeddings_limit'            => (int) $embeddings_limit,
                        'use_default_embedding_model' => $use_default_embedding_model,
                        'selected_embedding_model'    => $selected_embedding_model,
                        'selected_embedding_provider' => $selected_embedding_provider
                    ];
                }
            }

            // By default, embeddings disabled
            return ['embeddingsEnabled' => false];
        }

        /**
         * Helper to handle Qdrant/Pinecone embeddings retrieval and merge with prompt.
         *
         * @param string $wpaicg_prompt
         * @param array  $embeddingsDetails
         * @return string
         */
        private function handle_embeddings($wpaicg_prompt, array $embeddingsDetails) {
            $contextLabel = !empty($embeddingsDetails['context_suffix']) ? $embeddingsDetails['context_suffix'] : "";
            $contextData  = "";

            if ($embeddingsDetails['vectordb'] === 'qdrant') {
                $embedding_result = $this->wpaicg_embeddings_result_qdrant(
                    $embeddingsDetails['collections'],
                    $wpaicg_prompt,
                    $embeddingsDetails['embeddings_limit'],
                    $embeddingsDetails['use_default_embedding_model'],
                    $embeddingsDetails['selected_embedding_model'],
                    $embeddingsDetails['selected_embedding_provider']
                );
                if (!empty($embedding_result['data'])) {
                    $contextData = $contextLabel . " " . $embedding_result['data'];
                }
            } elseif ($embeddingsDetails['vectordb'] === 'pinecone') {
                $embedding_result = $this->wpaicg_embeddings_result_pinecone(
                    $embeddingsDetails['collections'],
                    $wpaicg_prompt,
                    $embeddingsDetails['embeddings_limit'],
                    $embeddingsDetails['use_default_embedding_model'],
                    $embeddingsDetails['selected_embedding_model'],
                    $embeddingsDetails['selected_embedding_provider']
                );
                if (!empty($embedding_result['data'])) {
                    $contextData = $contextLabel . " " . $embedding_result['data'];
                }
            } else {
                // If vectordb is neither 'qdrant' nor 'pinecone'
                error_log("Embeddings enabled but no valid vector DB found.");
            }

            // Prepend or append context
            if ($embeddingsDetails['context_suffix_position'] === 'before') {
                return $contextData . " " . $wpaicg_prompt;
            }
            return $wpaicg_prompt . " " . $contextData;
        }

        /**
         * Handle building AI arguments from request parameters.
         *
         * @param object $ai_engine
         * @param string $prompt
         * @return array
         */
        private function build_ai_args($ai_engine, $prompt) {
            $args = [
                'prompt'            => $prompt,
                'temperature'       => (float)$ai_engine->temperature,
                'max_tokens'        => (float)$ai_engine->max_tokens,
                'frequency_penalty' => (float)$ai_engine->frequency_penalty,
                'presence_penalty'  => (float)$ai_engine->presence_penalty,
                'stream'            => true
            ];

            if (isset($_REQUEST['temperature']) && !empty($_REQUEST['temperature'])) {
                $args['temperature'] = (float)sanitize_text_field($_REQUEST['temperature']);
            }
            if (isset($_REQUEST['max_tokens']) && !empty($_REQUEST['max_tokens'])) {
                $args['max_tokens'] = (float)sanitize_text_field($_REQUEST['max_tokens']);
            }
            if (isset($_REQUEST['frequency_penalty']) && !empty($_REQUEST['frequency_penalty'])) {
                $args['frequency_penalty'] = (float)sanitize_text_field($_REQUEST['frequency_penalty']);
            }
            if (isset($_REQUEST['presence_penalty']) && !empty($_REQUEST['presence_penalty'])) {
                $args['presence_penalty'] = (float)sanitize_text_field($_REQUEST['presence_penalty']);
            }
            if (isset($_REQUEST['top_p']) && !empty($_REQUEST['top_p'])) {
                $args['top_p'] = (float)sanitize_text_field($_REQUEST['top_p']);
            }
            if (isset($_REQUEST['best_of']) && !empty($_REQUEST['best_of'])) {
                $args['best_of'] = (float)sanitize_text_field($_REQUEST['best_of']);
            }
            if (isset($_REQUEST['stop']) && !empty($_REQUEST['stop'])) {
                $args['stop'] = explode(',', sanitize_text_field($_REQUEST['stop']));
            }

            // Configure the model based on the provider
            if ($this->provider === 'OpenAI') {
                if (isset($_REQUEST['engine']) && !empty($_REQUEST['engine'])) {
                    $args['model'] = sanitize_text_field($_REQUEST['engine']);
                } else {
                    // default model for OpenAI
                    $args['model'] = 'gpt-3.5-turbo-16k';
                }
            } elseif ($this->provider === 'Google') {
                if (isset($_REQUEST['engine']) && !empty($_REQUEST['engine'])) {
                    $args['model'] = sanitize_text_field($_REQUEST['engine']);
                } else {
                    $args['model'] = get_option('wpaicg_google_default_model', 'gemini-pro');
                }
            } elseif ($this->provider === 'OpenRouter') {
                if (isset($_REQUEST['engine']) && !empty($_REQUEST['engine'])) {
                    $args['model'] = sanitize_text_field($_REQUEST['engine']);
                } else {
                    $args['model'] = get_option('wpaicg_openrouter_default_model', 'openrouter/auto');
                }
            } else {
                // Assume Azure for any other cases
                $args['model'] = get_option('wpaicg_azure_deployment', '');
            }

            return $args;
        }

        /**
         * Helper to handle SSE-like output for Google.
         * Google responses are returned as a single chunk, so we artificially
         * split them into words for SSE streaming.
         *
         * @param object $ai_engine
         * @param array  $wpaicg_args
         * @return void
         */
        private function handle_google_stream_sse($ai_engine, array $wpaicg_args) {
            // This function is no longer used for Google since we now call the old code path directly in wpaicg_stream()
        }

        /**
         * Helper to handle streaming for providers like OpenAI / Azure that
         * support chunked responses.
         *
         * @param object $ai_engine
         * @param array  $wpaicg_args
         * @return void
         */
        private function handle_openai_like_stream($ai_engine, array $wpaicg_args) {
            $legacy_models = [
                'text-davinci-001','davinci','babbage','text-babbage-001','curie-instruct-beta','text-davinci-003',
                'text-curie-001','davinci-instruct-beta','text-davinci-002','ada','text-ada-001','curie','gpt-3.5-turbo-instruct'
            ];

            // If it's not a legacy model, treat as Chat-based
            if (! in_array($wpaicg_args['model'], $legacy_models, true)) {
                unset($wpaicg_args['best_of']);
                $wpaicg_args['messages'] = [
                    ['role' => 'user', 'content' => $wpaicg_args['prompt']]
                ];
                unset($wpaicg_args['prompt']);

                // Attempt streaming chat
                try {
                    $ai_engine->chat($wpaicg_args, function ($curl_info, $data) {
                        $this->handle_sse_callback($data);
                        return strlen($data);
                    });
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    $this->wpaicg_event_message($message);
                }

            } else {
                // Legacy-based completion call
                try {
                    $ai_engine->completion($wpaicg_args, function ($curl_info, $data) {
                        $this->handle_sse_callback($data);
                        return strlen($data);
                    });
                } catch (\Exception $exception) {
                    $message = $exception->getMessage();
                    $this->wpaicg_event_message($message);
                }
            }
        }

        /**
         * SSE callback for partial data; handles both normal and error outputs.
         *
         * @param string $data
         * @return void
         */
        private function handle_sse_callback($data) {
            $response = json_decode($data, true);

            // If there's an error, we stream it out as words, then finalize
            if (isset($response['error']) && !empty($response['error'])) {
                $message = $response['error']['message'] ?? '';
                if (empty($message) && isset($response['error']['code']) && $response['error']['code'] === 'invalid_api_key') {
                    $message = "Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.";
                }
                $this->stream_words_as_sse($message);
                // Send final SSE chunk with finish_reason
                echo 'data: {"choices":[{"finish_reason":"stop"}]}' . "\n\n";
                ob_flush();
                flush();
                return;
            }

            // Otherwise just echo raw chunk
            echo $data;
            ob_flush();
            flush();
        }

        /**
         * Helper method to split a text message into space-delimited words
         * and stream them as SSE.
         *
         * @param string $message
         * @return void
         */
        private function stream_words_as_sse($message) {
            $words = explode(' ', $message);
            foreach ($words as $key => $word) {
                echo "event: message\n";
                if ($key === 0) {
                    echo 'data: {"choices":[{"delta":{"content":"' . $word . '"}}]}';
                } else {
                    echo 'data: {"choices":[{"delta":{"content":" ' . $word . '"}}]}';
                }
                echo "\n\n";
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }
        }

        /**
         * Send a message in SSE format, splitting content by space
         * and appending the "[LIMITED]" marker.
         *
         * @param string $words
         * @return void
         */
        public function wpaicg_event_message($words) {
            $splitWords = explode(' ', $words);
            // Append the special marker to indicate limitation
            $splitWords[] = '[LIMITED]';

            foreach ($splitWords as $key => $word) {
                echo "event: message\n";
                if ($key === 0) {
                    echo 'data: {"choices":[{"delta":{"content":"' . $word . '"}}]}';
                } else {
                    if ($word === '[LIMITED]') {
                        echo 'data: [LIMITED]';
                    } else {
                        echo 'data: {"choices":[{"delta":{"content":" ' . $word . '"}}]}';
                    }
                }
                echo "\n\n";
                if (ob_get_level() > 0) {
                    ob_end_flush();
                }
                flush();
            }
        }

        /**
         * Retrieve or set a unique cookie ID for non-logged-in users.
         *
         * @param string $source_stream
         * @return string
         */
        public function wpaicg_get_cookie_id($source_stream) {
            if(!function_exists('PasswordHash')){
                require_once ABSPATH . 'wp-includes/class-phpass.php';
            }

            $cookieName = 'wpaicg_' . $source_stream . '_client_id';
            if (isset($_COOKIE[$cookieName]) && !empty($_COOKIE[$cookieName])) {
                return $_COOKIE[$cookieName];
            }

            $hasher    = new \PasswordHash(8, false);
            $cookie_id = 't_' . substr(md5($hasher->get_random_bytes(32)), 2);

            setcookie($cookieName, $cookie_id, time() + 604800, COOKIEPATH, COOKIE_DOMAIN);
            return $cookie_id;
        }

        /**
         * Handle embeddings with Pinecone.
         *
         * @param string $wpaicg_pinecone_environment
         * @param string $wpaicg_message
         * @param int    $limit
         * @param string $use_default_embedding_model
         * @param string $selected_embedding_model
         * @param string $selected_embedding_provider
         * @param bool   $namespace
         * @return array
         */
        public function wpaicg_embeddings_result_pinecone(
            $wpaicg_pinecone_environment,
            $wpaicg_message,
            $limit,
            $use_default_embedding_model,
            $selected_embedding_model,
            $selected_embedding_provider,
            $namespace = false
        ) {
            $result                 = ['status' => 'error', 'data' => ''];
            $wpaicg_pinecone_api_key= get_option('wpaicg_pinecone_api', '');

            if (empty($wpaicg_pinecone_api_key) || empty($wpaicg_pinecone_environment)) {
                return ['data' => esc_html__('Required Pinecone or API configuration missing.', 'gpt3-ai-content-generator')];
            }

            $model     = $this->get_embedding_model($use_default_embedding_model, $selected_embedding_model);
            $apiParams = $this->prepare_api_params($wpaicg_message, $model);

            $ai_instance = $this->initialize_ai_instance($use_default_embedding_model, $selected_embedding_provider);
            if (!$ai_instance) {
                return ['data' => esc_html__('Unable to initialize the AI instance.', 'gpt3-ai-content-generator')];
            }

            $response = $ai_instance->embeddings($apiParams);
            $response = json_decode($response, true);

            if (isset($response['error']) && !empty($response['error'])) {
                $errorMessage = $response['error']['message'] ?? 'Incorrect API key provided.';
                return ['data' => $errorMessage];
            }

            $embedding = $response['data'][0]['embedding'] ?? null;
            if (empty($embedding)) {
                return ['data' => esc_html__('No embedding data received from the AI provider.', 'gpt3-ai-content-generator')];
            }

            // Calculate prompt_tokens based on $wpaicg_message character count
            $contentLength = strlen($wpaicg_message);
            $promptTokens = ceil($contentLength / 4);

            // Convert to OpenAI format
            $openAiResponse = [
                "object" => "list",
                "data" => [
                    [
                        "object" => "embedding",
                        "index" => 0,
                        "embedding" => $embedding
                    ]
                ],
                "model" => $model,
                "usage" => [
                    "prompt_tokens" => $promptTokens,
                    "total_tokens" => $promptTokens
                ]
            ];

            $decodedResponse = json_encode($openAiResponse);

            return $decodedResponse;
        }
        
        /**
         * Perform the Pinecone query to search for nearest matches.
         *
         * @param string $wpaicg_pinecone_environment
         * @param array  $embedding
         * @param string $wpaicg_pinecone_api_key
         * @param int    $limit
         * @param bool   $namespace
         * @return array
         */
        private function search_pinecone($wpaicg_pinecone_environment, $embedding, $wpaicg_pinecone_api_key, $limit, $namespace) {
            $headers = [
                'Content-Type' => 'application/json',
                'Api-Key'      => $wpaicg_pinecone_api_key
            ];

            $pinecone_body = [
                'vector' => $embedding,
                'topK'   => $limit
            ];

            if ($namespace) {
                $pinecone_body['namespace'] = $namespace;
            }

            $response = wp_safe_remote_post("https://{$wpaicg_pinecone_environment}/query", [
                'headers' => $headers,
                'body'    => json_encode($pinecone_body)
            ]);

            if (is_wp_error($response)) {
                return ['data' => esc_html($response->get_error_message())];
            }

            $bodyContent = wp_remote_retrieve_body($response);
            $body        = json_decode($bodyContent, true);

            if (
                isset($body['matches'])
                && is_array($body['matches'])
                && count($body['matches'])
            ) {
                $data           = '';
                $processedCount = 0;
                foreach ($body['matches'] as $match) {
                    if ($processedCount >= $limit) {
                        break;
                    }
                    $wpaicg_embedding = get_post($match['id']);
                    if ($wpaicg_embedding) {
                        $data .= empty($data) ? $wpaicg_embedding->post_content : "\n" . $wpaicg_embedding->post_content;
                    }
                    $processedCount++;
                }
                return ['status' => 'success', 'data' => $data];
            }

            return [
                'status' => 'error',
                'data'   => esc_html__('No matches found or error in Pinecone response.', 'gpt3-ai-content-generator')
            ];
        }

        /**
         * Handle embeddings with Qdrant.
         *
         * @param string $wpaicg_qdrant_collection
         * @param string $wpaicg_message
         * @param int    $limit
         * @param string $use_default_embedding_model
         * @param string $selected_embedding_model
         * @param string $selected_embedding_provider
         * @return array
         */
        public function wpaicg_embeddings_result_qdrant(
            $wpaicg_qdrant_collection,
            $wpaicg_message,
            $limit,
            $use_default_embedding_model,
            $selected_embedding_model,
            $selected_embedding_provider
        ) {
            $result                 = ['status' => 'error', 'data' => ''];
            $wpaicg_qdrant_api_key  = get_option('wpaicg_qdrant_api_key', '');
            $wpaicg_qdrant_endpoint = get_option('wpaicg_qdrant_endpoint', '');

            if (
                empty($wpaicg_qdrant_api_key)
                || empty($wpaicg_qdrant_endpoint)
                || empty($wpaicg_qdrant_collection)
            ) {
                return ['data' => esc_html__('Required Qdrant or API configuration missing.', 'gpt3-ai-content-generator')];
            }

            $model     = $this->get_embedding_model($use_default_embedding_model, $selected_embedding_model);
            $apiParams = $this->prepare_api_params($wpaicg_message, $model);

            $ai_instance = $this->initialize_ai_instance($use_default_embedding_model, $selected_embedding_provider);
            if (!$ai_instance) {
                return ['data' => esc_html__('Unable to initialize the AI instance.', 'gpt3-ai-content-generator')];
            }

            $response = $ai_instance->embeddings($apiParams);
            $response = json_decode($response, true);

            if (isset($response['error']) && !empty($response['error'])) {
                $errorMessage = $response['error']['message'] ?? 'Incorrect API key provided.';
                return ['data' => $errorMessage];
            }

            $embedding = $response['data'][0]['embedding'] ?? null;
            if (empty($embedding)) {
                return ['data' => esc_html__('No embedding data received from the AI provider.', 'gpt3-ai-content-generator')];
            }

            $result = $this->search_qdrant($wpaicg_qdrant_endpoint, $wpaicg_qdrant_collection, $embedding, $limit, $wpaicg_qdrant_api_key);

            return $result;
        }

        /**
         * Get the embedding model name from either default or custom.
         *
         * @param string $use_default_embedding_model
         * @param string $selected_embedding_model
         * @return string
         */
        private function get_embedding_model($use_default_embedding_model, $selected_embedding_model) {
            if ($use_default_embedding_model === 'no') {
                return $selected_embedding_model;
            }

            $main_embedding_model = get_option('wpaicg_main_embedding_model', '');
            if (!empty($main_embedding_model)) {
                list($provider, $model) = explode(':', $main_embedding_model, 2);
                return $model;
            } else {
                $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                // Retrieve the embedding model based on the provider
                switch ($wpaicg_provider) {
                    case 'OpenAI':
                        return get_option('wpaicg_openai_embeddings', 'text-embedding-ada-002');
                    case 'Azure':
                        return get_option('wpaicg_azure_embeddings', 'text-embedding-ada-002');
                    case 'Google':
                        return get_option('wpaicg_google_embeddings', 'embedding-001');
                    default:
                        return 'default-embedding-model';
                }
            }
        }

        /**
         * Prepare the API params for embeddings calls.
         *
         * @param string $wpaicg_message
         * @param string $model
         * @return array
         */
        private function prepare_api_params($wpaicg_message, $model) {
            return [
                'input' => $wpaicg_message,
                'model' => $model
            ];
        }

        /**
         * Initialize the correct AI instance for embeddings calls.
         *
         * @param string $use_default_embedding_model
         * @param string $selected_embedding_provider
         * @return object|null
         */
        private function initialize_ai_instance($use_default_embedding_model, $selected_embedding_provider) {
            /**
             * Start with the main plugin provider,
             * override if $use_default_embedding_model === 'no'
             */
            $provider = $this->provider;

            if ($use_default_embedding_model === 'no') {
                $provider = $selected_embedding_provider;
            } else {
                // Check if a specific model was configured in "wpaicg_main_embedding_model"
                $main_embedding_model = get_option('wpaicg_main_embedding_model', '');
                if (!empty($main_embedding_model)) {
                    list($provider, $model) = explode(':', $main_embedding_model, 2);
                }
            }

            switch ($provider) {
                case 'OpenAI':
                    return WPAICG_OpenAI::get_instance()->openai();
                case 'Azure':
                    return WPAICG_AzureAI::get_instance()->azureai();
                case 'Google':
                    return WPAICG_Google::get_instance();
                default:
                    return null;
            }
        }

        /**
         * Execute a Qdrant vector search and retrieve the best matches.
         *
         * @param string $endpoint
         * @param string $collection
         * @param array  $embedding
         * @param int    $limit
         * @param string $apiKey
         * @return array
         */
        private function search_qdrant($endpoint, $collection, $embedding, $limit, $apiKey) {
            $group_id_value = "default";
            $queryData      = [
                'vector' => $embedding,
                'limit'  => $limit,
                'filter' => [
                    'must' => [[
                        'key'   => 'group_id',
                        'match' => ['value' => $group_id_value]
                    ]]
                ]
            ];

            $response = wp_safe_remote_post("{$endpoint}/collections/{$collection}/points/search", [
                'method'  => 'POST',
                'headers' => [
                    'api-key'      => $apiKey,
                    'Content-Type' => 'application/json'
                ],
                'body'    => json_encode($queryData)
            ]);

            if (is_wp_error($response)) {
                return ['data' => esc_html($response->get_error_message())];
            }

            $bodyContent = wp_remote_retrieve_body($response);
            $body        = json_decode($bodyContent, true);

            if (isset($body['result']) && is_array($body['result'])) {
                $data = array_reduce($body['result'], function ($carry, $match) {
                    $postContent = get_post($match['id'])->post_content ?? '';
                    return $carry . ($carry ? "\n" : '') . $postContent;
                }, '');

                return ['status' => 'success', 'data' => $data];
            }

            return ['data' => esc_html__('No matches found or error in Qdrant response.', 'gpt3-ai-content-generator')];
        }
    }

    WPAICG_Playground::get_instance();
}
