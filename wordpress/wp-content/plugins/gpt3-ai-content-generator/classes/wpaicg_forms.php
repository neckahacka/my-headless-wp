<?php

declare(strict_types=1);

namespace WPAICG;

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('\\WPAICG\\WPAICG_Forms')) {
    class WPAICG_Forms
    {
        private static $instance = null;

        public $wpaicg_engine = 'gpt-4o-mini';
        public $wpaicg_max_tokens = 2000;
        public $wpaicg_temperature = 0;
        public $wpaicg_top_p = 1;
        public $wpaicg_best_of = 1;
        public $wpaicg_frequency_penalty = 0;
        public $wpaicg_presence_penalty = 0;
        public $wpaicg_stop = [];

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_wpaicg_update_template', [$this, 'wpaicg_update_template']);
            add_action('wp_ajax_wpaicg_template_delete', [$this, 'wpaicg_template_delete']);
            add_shortcode('wpaicg_form', [$this, 'wpaicg_form_shortcode']);
            add_action('admin_menu', [$this, 'wpaicg_menu']);

            // For saving logs
            add_action('wp_ajax_wpaicg_form_log', [$this, 'wpaicg_form_log']);
            add_action('wp_ajax_nopriv_wpaicg_form_log', [$this, 'wpaicg_form_log']);

            // Duplicating forms
            add_action('wp_ajax_wpaicg_form_duplicate', [$this, 'wpaicg_form_duplicate']);

            add_action('wp_ajax_wpaicg_get_custom_forms_ids', [$this, 'wpaicg_get_custom_forms_ids']);
            add_action('wp_ajax_wpaicg_export_single_form',  [$this, 'wpaicg_export_single_form']);
            add_action('wp_ajax_wpaicg_import_single_form',  [$this, 'wpaicg_import_single_form']);
            add_action('wp_ajax_wpaicg_delete_single_form',  [$this, 'wpaicg_delete_single_form']);

            // Schedules token removal
            if (!wp_next_scheduled('wpaicg_remove_forms_tokens_limited')) {
                wp_schedule_event(time(), 'hourly', 'wpaicg_remove_forms_tokens_limited');
            }
            add_action('wpaicg_remove_forms_tokens_limited', [$this, 'wpaicg_remove_tokens_limit']);

            // Feedback
            add_action('wp_ajax_wpaicg_save_feedback', [$this, 'wpaicg_save_feedback']);
            add_action('wp_ajax_nopriv_wpaicg_save_feedback', [$this, 'wpaicg_save_feedback']);
            add_action('wp_ajax_wpaicg_save_prompt_feedback', [$this, 'wpaicg_save_prompt_feedback']);
            add_action('wp_ajax_nopriv_wpaicg_save_prompt_feedback', [$this, 'wpaicg_save_prompt_feedback']);

            // Delete all logs
            add_action('wp_ajax_wpaicg_delete_all_logs', [$this, 'wpaicg_delete_all_logs']);

            // NEW: AJAX to load form preview
            add_action('wp_ajax_wpaicg_get_form_preview', [$this, 'wpaicg_get_form_preview']);

            // NEW: AJAX to create a new form
            add_action('wp_ajax_wpaicg_create_new_form', [$this, 'wpaicg_create_new_form']);

            // NEW: AJAX to get form data for editing
            add_action('wp_ajax_wpaicg_get_form_data_for_editing', [$this, 'wpaicg_get_form_data_for_editing']);

            // NEW: AJAX to save an edited form
            add_action('wp_ajax_wpaicg_save_edited_form', [$this, 'wpaicg_save_edited_form']);

            // NEW (Logs): get logs with pagination
            add_action('wp_ajax_wpaicg_get_logs', [$this, 'wpaicg_get_logs']);

            // IMPORTANT: handle Settings save via AJAX so the user stays on the same page
            add_action('wp_ajax_wpaicg_save_form_settings', [$this, 'wpaicg_save_form_settings']);

            // *********
            // NEW: store file content in transient for large uploads
            // *********
            add_action('wp_ajax_wpaicg_store_file_content', [$this, 'wpaicg_store_file_content']);
            add_action('wp_ajax_nopriv_wpaicg_store_file_content', [$this, 'wpaicg_store_file_content']);
        }

        /**
         * Save “Token Management” form settings via AJAX.
         * This prevents a redirect/refresh after clicking Save.
         */
        public function wpaicg_save_form_settings()
        {
            // Check the nonce from the hidden input "wpaicg_limit_tokens_nonce"
            if (!isset($_POST['security']) || !wp_verify_nonce($_POST['security'], 'wpaicg_limit_tokens_action')) {
                wp_send_json_error(['msg' => __('Nonce verification failed.', 'gpt3-ai-content-generator')]);
            }

            // Ensure permission
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['msg' => __('No permission to save settings.', 'gpt3-ai-content-generator')]);
            }

            // The “wpaicg_limit_tokens” array must exist
            if (!isset($_POST['wpaicg_limit_tokens'])) {
                wp_send_json_error(['msg' => __('Missing settings data.', 'gpt3-ai-content-generator')]);
            }

            // Sanitize and update
            $wpaicg_limit_tokens = \WPAICG\wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_limit_tokens']);
            update_option('wpaicg_limit_tokens_form', $wpaicg_limit_tokens);

            // Enable or disable token purchasing
            if (isset($_POST['wpaicg_forms_enable_sale']) && !empty($_POST['wpaicg_forms_enable_sale'])) {
                update_option('wpaicg_forms_enable_sale', sanitize_text_field($_POST['wpaicg_forms_enable_sale']));
            } else {
                delete_option('wpaicg_forms_enable_sale');
            }

            // --------------------------------------------------------------------
            // 2) Save "Internet Browsing" (Google API Key, CSE, region, language, etc.)
            //    We store them in the same standard WP options used by the Chatbot.
            // --------------------------------------------------------------------
            if (isset($_POST['wpaicg_google_api_key'])) {
                update_option('wpaicg_google_api_key', sanitize_text_field($_POST['wpaicg_google_api_key']));
            }
            if (isset($_POST['wpaicg_google_search_engine_id'])) {
                update_option('wpaicg_google_search_engine_id', sanitize_text_field($_POST['wpaicg_google_search_engine_id']));
            }
            if (isset($_POST['wpaicg_google_search_country'])) {
                update_option('wpaicg_google_search_country', sanitize_text_field($_POST['wpaicg_google_search_country']));
            }
            if (isset($_POST['wpaicg_google_search_language'])) {
                update_option('wpaicg_google_search_language', sanitize_text_field($_POST['wpaicg_google_search_language']));
            }
            if (isset($_POST['wpaicg_google_search_num'])) {
                update_option('wpaicg_google_search_num', intval($_POST['wpaicg_google_search_num']));
            }

            // Respond success without reloading the page
            wp_send_json_success(['message' => __('Settings updated successfully.', 'gpt3-ai-content-generator')]);
        }

        /**
         * Store large file content in a transient instead of passing in the SSE URL.
         *
         * Updated to parse .docx files from base64 -> extracted text.
         */
        public function wpaicg_store_file_content()
        {
            check_ajax_referer('wpaicg-ajax-nonce','nonce');
            if(!isset($_POST['fileContent'])) {
                wp_send_json_error(['message' => __('No file content found','gpt3-ai-content-generator')]);
            }
            $rawContent = wp_unslash($_POST['fileContent']);

            /**
             * If the user uploaded a doc/docx file, it's sent from JS as "base64:..."
             * We'll parse .docx into text so that the transient stores actual text.
             * For .doc, we currently still store the base64 string (unsupported).
             */
            if (strpos($rawContent, 'base64:') === 0) {
                // This implies docx or doc. We'll parse .docx. 
                // .doc is older binary format; for now we store it as base64.
                $justBase64 = substr($rawContent, 7);
                // Attempt docx extraction:
                $extractedText = $this->wpaicg_try_extract_docx($justBase64);

                // If extraction failed or not a valid docx,
                // we keep the original base64 for doc fallback.
                if (!empty($extractedText)) {
                    $rawContent = $extractedText;
                }
            } else {
                // For txt/csv (or any textual file), we keep standard sanitization
                $rawContent = sanitize_textarea_field($rawContent);
            }

            // Generate a unique transient key
            $transient_key = 'wpaicg_upload_' . wp_generate_uuid4();

            // Store for 1 hour
            set_transient($transient_key, $rawContent, HOUR_IN_SECONDS);

            wp_send_json_success(['transient_key'=>$transient_key]);
        }

        /**
         * Attempt to parse a .docx file (base64-encoded) into text.
         * If it fails or is invalid, return an empty string.
         */
        private function wpaicg_try_extract_docx(string $base64Docx): string
        {
            // Decode base64 to raw .docx data
            $binaryData = base64_decode($base64Docx, true);
            if (!$binaryData) {
                return '';
            }

            // Ensure we have ZipArchive available
            if (!class_exists('\\ZipArchive')) {
                return '';
            }

            // Create a temporary file
            $tmpFile = tempnam(sys_get_temp_dir(), 'wpaicg_docx_');
            if (!$tmpFile) {
                return '';
            }

            // Save the binary data to the temp file
            file_put_contents($tmpFile, $binaryData);

            $zip = new \ZipArchive();
            if ($zip->open($tmpFile) !== true) {
                @unlink($tmpFile);
                return '';
            }

            // Attempt to read "word/document.xml" inside docx
            $xmlContent = $zip->getFromName("word/document.xml");
            $zip->close();
            @unlink($tmpFile);

            if (!$xmlContent) {
                return '';
            }

            // Strip all tags to get raw text
            // Basic approach: remove XML tags. We can refine as needed.
            $text = strip_tags($xmlContent);

            // Clean up any leftover entities or newlines
            $text = html_entity_decode($text, ENT_QUOTES | ENT_XML1, 'UTF-8');
            $text = preg_replace("/[\r\n\t]+/", " ", $text);
            $text = trim($text);

            return $text;
        }

        /**
         * Normalize the form fields array so that "options" is stored as a
         * pipe-delimited string (rather than an array).
         */
        private function normalize_fields_array(array $fields): array
        {
            foreach ($fields as &$field) {
                if (isset($field['options']) && is_array($field['options'])) {
                    $field['options'] = implode('|', $field['options']);
                }
                if (!isset($field['min'])) {
                    $field['min'] = '';
                }
                if (!isset($field['max'])) {
                    $field['max'] = '';
                }
                if (!isset($field['rows'])) {
                    $field['rows'] = '';
                }
                if (!isset($field['cols'])) {
                    $field['cols'] = '';
                }
            }
            unset($field);
            return $fields;
        }

        /********************************
         * NEW: AJAX to get form data
         * for editing a custom form
         ********************************/
        public function wpaicg_get_form_data_for_editing()
        {
            check_ajax_referer('wpaicg_edit_form_nonce', 'nonce');

            if (!current_user_can('wpaicg_forms_forms')) {
                wp_send_json_error([
                    'message' => __('You do not have permission for this action.', 'gpt3-ai-content-generator')
                ]);
            }

            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            if (!$form_id) {
                wp_send_json_error(['message' => __('Invalid form ID', 'gpt3-ai-content-generator')]);
            }

            // Make sure it's actually a custom form
            $post = get_post($form_id);
            if (!$post || $post->post_type !== 'wpaicg_form') {
                wp_send_json_error([
                    'message' => __('This form does not exist or is not a custom form.', 'gpt3-ai-content-generator')
                ]);
            }

            // Gather meta
            $prompt      = get_post_meta($form_id, 'wpaicg_form_prompt', true);
            $fields_json = get_post_meta($form_id, 'wpaicg_form_fields', true);
            $description = $post->post_content;
            $title       = $post->post_title;
            $engine      = get_post_meta($form_id, 'wpaicg_form_engine', true);
            // IMPORTANT: must retrieve from wpaicg_form_model_provider
            $model_provider = get_post_meta($form_id, 'wpaicg_form_model_provider', true);

            // advanced settings
            $max_tokens         = get_post_meta($form_id, 'wpaicg_form_max_tokens', true);
            $top_p              = get_post_meta($form_id, 'wpaicg_form_top_p', true);
            $best_of            = get_post_meta($form_id, 'wpaicg_form_best_of', true);
            $frequency_penalty  = get_post_meta($form_id, 'wpaicg_form_frequency_penalty', true);
            $presence_penalty   = get_post_meta($form_id, 'wpaicg_form_presence_penalty', true);
            $stop               = get_post_meta($form_id, 'wpaicg_form_stop', true);

            // embeddings
            $use_embeddings     = get_post_meta($form_id, 'wpaicg_form_embeddings', true);
            $vector_db          = get_post_meta($form_id, 'wpaicg_form_vectordb', true);
            $collections        = get_post_meta($form_id, 'wpaicg_form_collections', true);
            $pineconeindexes    = get_post_meta($form_id, 'wpaicg_form_pineconeindexes', true);
            $suffix_text        = get_post_meta($form_id, 'wpaicg_form_suffix_text', true);
            $suffix_position    = get_post_meta($form_id, 'wpaicg_form_suffix_position', true);
            $use_default_embed  = get_post_meta($form_id, 'wpaicg_form_use_default_embedding_model', true);
            $selected_provider  = get_post_meta($form_id, 'wpaicg_form_selected_embedding_provider', true);
            $selected_model     = get_post_meta($form_id, 'wpaicg_form_selected_embedding_model', true);
            $embeddings_limit   = get_post_meta($form_id, 'wpaicg_form_embeddings_limit', true);

            // interface
            $interface_meta = [
                'wpaicg_form_response',
                'wpaicg_form_category',
                'wpaicg_form_color',
                'wpaicg_form_icon',
                'wpaicg_form_editor',
                'wpaicg_form_header',
                'wpaicg_form_copy_button',
                'wpaicg_form_ddraft',
                'wpaicg_form_dclear',
                'wpaicg_form_dnotice',
                'wpaicg_form_ddownload',
                'wpaicg_form_copy_text',
                'wpaicg_form_feedback_buttons',
                'wpaicg_form_generate_text',
                'wpaicg_form_noanswer_text',
                'wpaicg_form_draft_text',
                'wpaicg_form_clear_text',
                'wpaicg_form_stop_text',
                'wpaicg_form_cnotice_text',
                'wpaicg_form_download_text',
                'wpaicg_form_bgcolor',
            ];
            $interfaceData = [];
            foreach ($interface_meta as $im) {
                $interfaceData[$im] = get_post_meta($form_id, $im, true);
            }

            // decode fields
            $fields = [];
            if ($fields_json) {
                $decoded = json_decode($fields_json, true);
                if (is_array($decoded)) {
                    $fields = $decoded;
                }
            }

            // NEW: internet browsing meta
            $internet_browsing = get_post_meta($form_id, 'wpaicg_form_internet_browsing', true);
            $internet_status = ($internet_browsing === 'yes') ? 'yes' : 'no';

            wp_send_json_success([
                'title'       => $title,
                'description' => $description,
                'prompt'      => $prompt,
                'fields'      => $fields,
                'interface'   => $interfaceData,
                'engine'      => $engine,
                // must pass back the correct provider
                'model_provider' => $model_provider,
                'advanced_settings' => [
                    'max_tokens'         => $max_tokens ?: 1500,
                    'top_p'              => $top_p ?: 1,
                    'best_of'            => $best_of ?: 1,
                    'frequency_penalty'  => $frequency_penalty ?: 0,
                    'presence_penalty'   => $presence_penalty ?: 0,
                    'stop'               => $stop ?: '',
                ],
                'embedding_settings' => [
                    'use_embeddings'               => $use_embeddings ?: 'no',
                    'vectordb'                     => $vector_db ?: 'pinecone',
                    'collections'                  => $collections ?: '',
                    'pineconeindexes'              => $pineconeindexes ?: '',
                    'suffix_text'                  => $suffix_text ?: 'Context:',
                    'suffix_position'              => $suffix_position ?: 'after',
                    'use_default_embedding_model'  => $use_default_embed ?: 'yes',
                    'selected_embedding_provider'  => $selected_provider ?: '',
                    'selected_embedding_model'     => $selected_model ?: '',
                    'embeddings_limit'             => $embeddings_limit ?: 1,
                ],
                'internet_browsing' => $internet_status
            ]);
        }

        /************************************
         * NEW: AJAX to save an edited form
         ************************************/
        public function wpaicg_save_edited_form()
        {
            check_ajax_referer('wpaicg_save_edited_form_nonce', 'nonce');

            if (!current_user_can('wpaicg_forms_forms')) {
                wp_send_json_error(['message' => __('You do not have permission for this action.', 'gpt3-ai-content-generator')]);
            }

            $form_id    = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            $title      = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $description= isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
            $prompt     = isset($_POST['prompt']) ? wp_kses_post($_POST['prompt']) : '';
            $fields_json= isset($_POST['fields']) ? wp_unslash($_POST['fields']) : '';
            $engine     = isset($_POST['engine']) ? sanitize_text_field($_POST['engine']) : 'gpt-4o-mini';
            // Must read the provider from the posted data
            $provider   = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';

            $model_settings = isset($_POST['model_settings']) ? (array) $_POST['model_settings'] : [];
            $wpaicg_max_tokens        = isset($model_settings['max_tokens']) ? intval($model_settings['max_tokens']) : 1500;
            $wpaicg_top_p             = isset($model_settings['top_p']) ? floatval($model_settings['top_p']) : 1;
            $wpaicg_best_of           = isset($model_settings['best_of']) ? intval($model_settings['best_of']) : 1;
            $wpaicg_frequency_penalty = isset($model_settings['frequency_penalty']) ? floatval($model_settings['frequency_penalty']) : 0;
            $wpaicg_presence_penalty  = isset($model_settings['presence_penalty']) ? floatval($model_settings['presence_penalty']) : 0;
            $wpaicg_stop              = isset($model_settings['stop']) ? sanitize_text_field($model_settings['stop']) : '';

            if (!$form_id || !$title || !$description || !$prompt) {
                wp_send_json_error(['message' => __('Missing required data', 'gpt3-ai-content-generator')]);
            }

            $post = get_post($form_id);
            if (!$post || $post->post_type !== 'wpaicg_form') {
                wp_send_json_error(['message' => __('Not a valid custom form.', 'gpt3-ai-content-generator')]);
            }

            // Update the post
            wp_update_post([
                'ID'           => $form_id,
                'post_title'   => $title,
                'post_content' => $description,
            ]);

            // Update meta
            update_post_meta($form_id, 'wpaicg_form_engine', $engine);
            // Correct: store provider in wpaicg_form_model_provider
            update_post_meta($form_id, 'wpaicg_form_model_provider', $provider);

            update_post_meta($form_id, 'wpaicg_form_prompt', $prompt);
            update_post_meta($form_id, 'wpaicg_form_fields', $fields_json);

            // advanced model
            update_post_meta($form_id, 'wpaicg_form_max_tokens', $wpaicg_max_tokens);
            update_post_meta($form_id, 'wpaicg_form_top_p', $wpaicg_top_p);
            update_post_meta($form_id, 'wpaicg_form_best_of', $wpaicg_best_of);
            update_post_meta($form_id, 'wpaicg_form_frequency_penalty', $wpaicg_frequency_penalty);
            update_post_meta($form_id, 'wpaicg_form_presence_penalty', $wpaicg_presence_penalty);
            update_post_meta($form_id, 'wpaicg_form_stop', $wpaicg_stop);

            // Embedding settings
            $embedding_settings = isset($_POST['embedding_settings']) ? (array) $_POST['embedding_settings'] : [];
            $use_embeddings = !empty($embedding_settings['use_embeddings']) ? sanitize_text_field($embedding_settings['use_embeddings']) : 'no';
            update_post_meta($form_id, 'wpaicg_form_embeddings', $use_embeddings);

            $vector_db = !empty($embedding_settings['vectordb']) ? sanitize_text_field($embedding_settings['vectordb']) : 'pinecone';
            update_post_meta($form_id, 'wpaicg_form_vectordb', $vector_db);

            $collections = !empty($embedding_settings['collections']) ? sanitize_text_field($embedding_settings['collections']) : '';
            update_post_meta($form_id, 'wpaicg_form_collections', $collections);

            $pineconeindexes = !empty($embedding_settings['pineconeindexes']) ? sanitize_text_field($embedding_settings['pineconeindexes']) : '';
            update_post_meta($form_id, 'wpaicg_form_pineconeindexes', $pineconeindexes);

            $suffix_text = !empty($embedding_settings['suffix_text']) ? sanitize_text_field($embedding_settings['suffix_text']) : 'Context:';
            update_post_meta($form_id, 'wpaicg_form_suffix_text', $suffix_text);

            $suffix_position = !empty($embedding_settings['suffix_position']) ? sanitize_text_field($embedding_settings['suffix_position']) : 'after';
            update_post_meta($form_id, 'wpaicg_form_suffix_position', $suffix_position);

            $use_default = !empty($embedding_settings['use_default_embedding_model']) ? sanitize_text_field($embedding_settings['use_default_embedding_model']) : 'yes';
            update_post_meta($form_id, 'wpaicg_form_use_default_embedding_model', $use_default);

            $selected_provider = !empty($embedding_settings['selected_embedding_provider']) ? sanitize_text_field($embedding_settings['selected_embedding_provider']) : '';
            update_post_meta($form_id, 'wpaicg_form_selected_embedding_provider', $selected_provider);

            $selected_model = !empty($embedding_settings['selected_embedding_model']) ? sanitize_text_field($embedding_settings['selected_embedding_model']) : '';
            update_post_meta($form_id, 'wpaicg_form_selected_embedding_model', $selected_model);

            $embed_limit = !empty($embedding_settings['embeddings_limit']) ? intval($embedding_settings['embeddings_limit']) : 1;
            update_post_meta($form_id, 'wpaicg_form_embeddings_limit', $embed_limit);

            // Interface
            $interface_meta = [
                'wpaicg_form_response',
                'wpaicg_form_category',
                'wpaicg_form_color',
                'wpaicg_form_icon',
                'wpaicg_form_editor',
                'wpaicg_form_header',
                'wpaicg_form_copy_button',
                'wpaicg_form_ddraft',
                'wpaicg_form_dclear',
                'wpaicg_form_dnotice',
                'wpaicg_form_ddownload',
                'wpaicg_form_copy_text',
                'wpaicg_form_feedback_buttons',
                'wpaicg_form_generate_text',
                'wpaicg_form_noanswer_text',
                'wpaicg_form_draft_text',
                'wpaicg_form_clear_text',
                'wpaicg_form_stop_text',
                'wpaicg_form_cnotice_text',
                'wpaicg_form_download_text',
                'wpaicg_form_bgcolor',
            ];
            if (isset($_POST['interface']) && is_array($_POST['interface'])) {
                foreach ($interface_meta as $imKey) {
                    $val = isset($_POST['interface'][$imKey]) ? sanitize_text_field($_POST['interface'][$imKey]) : '';
                    update_post_meta($form_id, $imKey, $val);
                }
            }

            // NEW: internet browsing
            $internet_browsing = isset($_POST['internet_browsing']) ? sanitize_text_field($_POST['internet_browsing']) : 'no';
            update_post_meta($form_id, 'wpaicg_form_internet_browsing', $internet_browsing);

            wp_send_json_success([
                'message' => __('Form updated successfully.', 'gpt3-ai-content-generator')
            ]);
        }

        /**
         * Return the form's shortcode HTML so that the admin preview can show it inline.
         */
        public function wpaicg_get_form_preview()
        {
            check_ajax_referer('wpaicg-ajax-nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'Insufficient permissions']);
            }
            $form_id     = isset($_POST['form_id']) ? sanitize_text_field($_POST['form_id']) : '';
            $form_custom = isset($_POST['custom']) ? sanitize_text_field($_POST['custom']) : 'no';

            if (empty($form_id)) {
                wp_send_json_error(['message' => 'Invalid form ID']);
            }

            $shortcode_html = do_shortcode('[wpaicg_form id="' . $form_id . '" custom="' . $form_custom . '" settings="no"]');
            wp_send_json_success(['html' => $shortcode_html]);
        }

        public function wpaicg_delete_all_logs()
        {
            check_ajax_referer('wpaicg_delete_all_logs_nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'You do not have sufficient permissions']);
                return;
            }

            global $wpdb;
            $wpaicgFormLogTable = $wpdb->prefix . 'wpaicg_form_logs';
            $wpaicgFeedbackTable = $wpdb->prefix . 'wpaicg_form_feedback';

            $resultLogs = $wpdb->query("TRUNCATE TABLE `$wpaicgFormLogTable`");
            $resultFeedback = $wpdb->query("TRUNCATE TABLE `$wpaicgFeedbackTable`");

            if ($resultLogs === false || $resultFeedback === false) {
                wp_send_json_error(['message' => 'Failed to delete logs and feedback']);
            } else {
                wp_send_json_success(['message' => 'All logs and feedback have been deleted successfully']);
            }
        }

        public function wpaicg_remove_tokens_limit()
        {
            global $wpdb;
            $wpaicg_settings = get_option('wpaicg_limit_tokens_form', []);
            $widget_reset_limit = isset($wpaicg_settings['reset_limit']) && !empty($wpaicg_settings['reset_limit'])
                ? $wpaicg_settings['reset_limit'] : 0;

            if ($widget_reset_limit > 0) {
                $widget_time = time() - ($widget_reset_limit * 86400);
                $wpdb->query($wpdb->prepare(
                    "DELETE FROM " . $wpdb->prefix . "wpaicg_formtokens WHERE created_at < %s",
                    $widget_time
                ));
            }
        }

        public function wpaicg_form_log()
        {
            global $wpdb;

            $wpaicg_result = ['status' => 'success'];
            $wpaicg_nonce = sanitize_text_field($_REQUEST['_wpnonce']);

            if (!wp_verify_nonce($wpaicg_nonce, 'wpaicg-formlog')) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
                exit;
            }

            $required_fields = ['prompt_id', 'prompt_name', 'prompt_response', 'engine'];
            foreach ($required_fields as $field) {
                if (empty($_REQUEST[$field])) {
                    wp_send_json($wpaicg_result);
                    exit;
                }
            }

            $userID = is_user_logged_in() ? get_current_user_id() : '';

            if (isset($_REQUEST['id']) && !empty($_REQUEST['id'])) {
                $prompt_id = sanitize_text_field($_REQUEST['id']);
            }
            $wpaicg_prompt = WPAICG_Playground::get_instance()->get_defined_prompt($prompt_id);

            $log = [
                'prompt'   => wp_kses_post($wpaicg_prompt),
                'data'     => wp_kses_post($_REQUEST['prompt_response']),
                'prompt_id'=> sanitize_text_field($_REQUEST['prompt_id']),
                'name'     => sanitize_text_field($_REQUEST['prompt_name']),
                'model'    => sanitize_text_field($_REQUEST['engine']),
                'duration' => sanitize_text_field($_REQUEST['duration']),
                'eventID'  => sanitize_text_field($_REQUEST['eventID']),
                'userID'   => $userID,
                'created_at' => time(),
            ];

            if (!empty($_REQUEST['source_id'])) {
                $log['source'] = sanitize_text_field($_REQUEST['source_id']);
            }

            $wpaicg_generator = WPAICG_Generator::get_instance();
            $log['tokens'] = ceil($wpaicg_generator->wpaicg_count_words($log['data']) * 1000 / 750);

            WPAICG_Account::get_instance()->save_log('forms', $log['tokens']);
            $wpdb->insert($wpdb->prefix . 'wpaicg_form_logs', $log);

            $wpaicg_playground = WPAICG_Playground::get_instance();
            $wpaicg_tokens_handling = $wpaicg_playground->wpaicg_token_handling('form');

            if ($wpaicg_tokens_handling['limit']) {
                if ($wpaicg_tokens_handling['token_id']) {
                    $wpdb->update(
                        $wpdb->prefix . $wpaicg_tokens_handling['table'],
                        ['tokens' => ($log['tokens'] + $wpaicg_tokens_handling['old_tokens'])],
                        ['id' => $wpaicg_tokens_handling['token_id']]
                    );
                } else {
                    $wpaicg_prompt_token_data = [
                        'tokens' => $log['tokens'],
                        'created_at' => time(),
                    ];
                    if (is_user_logged_in()) {
                        $wpaicg_prompt_token_data['user_id'] = get_current_user_id();
                    } else {
                        $wpaicg_prompt_token_data['session_id'] = $wpaicg_tokens_handling['client_id'];
                    }
                    $wpdb->insert($wpdb->prefix . $wpaicg_tokens_handling['table'], $wpaicg_prompt_token_data);
                }
            }

            wp_send_json($wpaicg_result);
        }

        function wpaicg_save_feedback() {

            check_ajax_referer('wpaicg-ajax-nonce', 'nonce');

            $formID = sanitize_text_field($_POST['formID']);
            $eventID = sanitize_text_field($_POST['eventID']);
            $feedback = sanitize_text_field($_POST['feedback']);
            $comment = sanitize_textarea_field($_POST['comment']);
            $formname = sanitize_text_field($_POST['formname']);
            $sourceID = sanitize_text_field($_POST['sourceID']);
            $formResponse = sanitize_text_field($_POST['response']);

            global $wpdb;
            $feedbackTable = $wpdb->prefix . 'wpaicg_form_feedback';

            $inserted = $wpdb->insert($feedbackTable, [
                'formID' => $formID,
                'feedback' => $feedback,
                'comment' => $comment,
                'formname' => $formname,
                'source' => $sourceID,
                'response' => $formResponse,
                'eventID' => $eventID,
                'created_at' => current_time('mysql')
            ]);

            if ($inserted) {
                echo json_encode(['status' => 'success', 'msg' => esc_html__('Thank you for your feedback.', 'gpt3-ai-content-generator')]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => esc_html__('Failed to save feedback.', 'gpt3-ai-content-generator')]);
            }

            wp_die();
        }

        function wpaicg_save_prompt_feedback() {

            check_ajax_referer('wpaicg-ajax-nonce', 'nonce');

            $formID = sanitize_text_field($_POST['formID']);
            $eventID = sanitize_text_field($_POST['eventID']);
            $feedback = sanitize_text_field($_POST['feedback']);
            $comment = sanitize_textarea_field($_POST['comment']);
            $formname = sanitize_text_field($_POST['formname']);
            $sourceID = sanitize_text_field($_POST['sourceID']);
            $formResponse = sanitize_text_field($_POST['response']);

            global $wpdb;
            $feedbackTable = $wpdb->prefix . 'wpaicg_prompt_feedback';

            $inserted = $wpdb->insert($feedbackTable, [
                'formID' => $formID,
                'feedback' => $feedback,
                'comment' => $comment,
                'formname' => $formname,
                'source' => $sourceID,
                'response' => $formResponse,
                'eventID' => $eventID,
                'created_at' => current_time('mysql')
            ]);

            if ($inserted) {
                echo json_encode(['status' => 'success', 'msg' => esc_html__('Thank you for your feedback.', 'gpt3-ai-content-generator')]);
            } else {
                echo json_encode(['status' => 'error', 'msg' => esc_html__('Failed to save feedback.', 'gpt3-ai-content-generator')]);
            }

            wp_die();
        }

        public function enqueue_scripts()
        {
            wp_enqueue_script('wpaicg-gpt-form', WPAICG_PLUGIN_URL . 'public/js/wpaicg-form-shortcode.js', [], null, true);
        }

        public function wpaicg_template_delete()
        {
            $wpaicg_result = ['status' => 'success'];

            if (!current_user_can('wpaicg_forms_forms')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            if (isset($_POST['id']) && !empty($_POST['id'])) {
                wp_delete_post(sanitize_text_field($_POST['id']));
            }
            wp_send_json($wpaicg_result);
        }

        /**
         * DUPLICATE FORMS (both custom and builtin).
         */
        public function wpaicg_form_duplicate()
        {
            $wpaicg_result = ['status' => 'success'];

            if (!current_user_can('wpaicg_forms_forms')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            $form_type  = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
            $builtin_id = isset($_POST['builtin_id']) ? intval($_POST['builtin_id']) : 0;
            $db_id      = isset($_POST['db_id']) ? intval($_POST['db_id']) : 0;
            $current_user_id = get_current_user_id();

            if (!$form_type) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Missing form type', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            // Duplicate from builtin .json file
            if ($form_type === 'builtin' && $builtin_id > 0) {
                $builtin_file = WPAICG_PLUGIN_DIR . 'admin/data/gptforms.json';
                if (!file_exists($builtin_file)) {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html__('Built-in forms data file not found', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }
                $json_content = file_get_contents($builtin_file);
                $builtin_forms = json_decode($json_content, true);
                if (!is_array($builtin_forms)) {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html__('Invalid built-in forms data', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }

                $matched_form = null;
                foreach ($builtin_forms as $bf) {
                    if (isset($bf['id']) && (int)$bf['id'] === $builtin_id) {
                        $matched_form = $bf;
                        break;
                    }
                }

                if (!$matched_form) {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html__('Built-in form not found', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }

                $duplicated_title = isset($matched_form['title'])
                    ? $matched_form['title'].' - Duplicated'
                    : 'Duplicated Form';
                $duplicated_desc  = isset($matched_form['description'])
                    ? $matched_form['description']
                    : '';

                // Insert new custom form
                $new_form_id = wp_insert_post([
                    'post_author'  => $current_user_id,
                    'post_title'   => sanitize_text_field($duplicated_title),
                    'post_content' => wp_kses_post($duplicated_desc),
                    'post_status'  => 'publish',
                    'post_type'    => 'wpaicg_form',
                ]);

                if (is_wp_error($new_form_id)) {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html__('Could not create duplicated form', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }

                // fields
                $fieldsArr = isset($matched_form['fields']) ? $matched_form['fields'] : [];
                $fieldsArr = $this->normalize_fields_array($fieldsArr);
                $fieldsJson = json_encode($fieldsArr);

                // Save meta
                update_post_meta(
                    $new_form_id,
                    'wpaicg_form_prompt',
                    isset($matched_form['prompt']) ? wp_kses_post($matched_form['prompt']) : ''
                );
                update_post_meta($new_form_id, 'wpaicg_form_fields', $fieldsJson);

                update_post_meta(
                    $new_form_id,
                    'wpaicg_form_response',
                    isset($matched_form['response']) ? $matched_form['response'] : 'div'
                );
                update_post_meta(
                    $new_form_id,
                    'wpaicg_form_category',
                    isset($matched_form['category']) ? $matched_form['category'] : ''
                );
                update_post_meta($new_form_id, 'wpaicg_form_engine', 'gpt-4o-mini');
                update_post_meta($new_form_id, 'wpaicg_form_max_tokens', 1500);
                update_post_meta($new_form_id, 'wpaicg_form_top_p', 1);
                update_post_meta($new_form_id, 'wpaicg_form_best_of', 1);
                update_post_meta(
                    $new_form_id,
                    'wpaicg_form_color',
                    isset($matched_form['color']) ? $matched_form['color'] : '#f9f9f9'
                );
                update_post_meta(
                    $new_form_id,
                    'wpaicg_form_icon',
                    isset($matched_form['icon']) ? $matched_form['icon'] : ''
                );
                // Additional defaults
                update_post_meta($new_form_id, 'wpaicg_form_editor', 'div');
                update_post_meta($new_form_id, 'wpaicg_form_header', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_embeddings', 'no');
                update_post_meta($new_form_id, 'wpaicg_form_suffix_text', 'Context:');
                update_post_meta($new_form_id, 'wpaicg_form_suffix_position', 'after');
                update_post_meta($new_form_id, 'wpaicg_form_embeddings_limit', 1);
                update_post_meta($new_form_id, 'wpaicg_form_use_default_embedding_model', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_copy_button', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_ddraft', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_dclear', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_dnotice', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_ddownload', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_copy_text', 'Copy');
                update_post_meta($new_form_id, 'wpaicg_form_feedback_buttons', 'yes');
                update_post_meta($new_form_id, 'wpaicg_form_generate_text', 'Generate');
                update_post_meta($new_form_id, 'wpaicg_form_noanswer_text', 'Number of Answers');
                update_post_meta($new_form_id, 'wpaicg_form_draft_text', 'Save Draft');
                update_post_meta($new_form_id, 'wpaicg_form_clear_text', 'Clear');
                update_post_meta($new_form_id, 'wpaicg_form_stop_text', 'Stop');
                update_post_meta($new_form_id, 'wpaicg_form_cnotice_text', 'Please register to save your result');
                update_post_meta($new_form_id, 'wpaicg_form_download_text', 'Download');
                update_post_meta($new_form_id, 'wpaicg_form_category', 'generation');
                update_post_meta($new_form_id, 'wpaicg_form_bgcolor', '#f9f9f9');

                // NEW: also store default provider for duplicated built-in forms
                update_post_meta($new_form_id, 'wpaicg_form_model_provider', 'OpenAI');

                $wpaicg_result['msg'] = esc_html__('Built-in form duplicated successfully.', 'gpt3-ai-content-generator');
                $wpaicg_result['new_id'] = $new_form_id;
                wp_send_json($wpaicg_result);
            }

            // Duplicate from an existing custom form
            if ($form_type === 'custom' && $db_id > 0) {
                $promptbase = get_post($db_id);
                if (!$promptbase || $promptbase->post_type !== 'wpaicg_form') {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html__('Form not found or invalid', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }

                $new_title = $promptbase->post_title . ' - Duplicated';
                $wpaicg_prompt_id = wp_insert_post([
                    'post_author'  => $current_user_id,
                    'post_title'   => $new_title,
                    'post_type'    => 'wpaicg_form',
                    'post_content' => $promptbase->post_content,
                    'post_status'  => 'publish',
                ]);

                if (is_wp_error($wpaicg_prompt_id)) {
                    $wpaicg_result['status'] = 'error';
                    $wpaicg_result['msg'] = esc_html__('Error duplicating custom form', 'gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                }

                $post_meta = get_post_meta($promptbase->ID);
                if ($post_meta) {
                    foreach ($post_meta as $meta_key => $meta_values) {
                        if ('_wp_old_slug' === $meta_key) {
                            continue;
                        }
                        if ('wpaicg_form_fields' === $meta_key) {
                            continue;
                        }
                        foreach ($meta_values as $meta_value) {
                            add_post_meta($wpaicg_prompt_id, $meta_key, $meta_value);
                        }
                    }
                }

                $fieldsJson = get_post_meta($db_id, 'wpaicg_form_fields', true);
                if ($fieldsJson) {
                    $decodedFields = json_decode($fieldsJson, true);
                    if (is_array($decodedFields)) {
                        $decodedFields = $this->normalize_fields_array($decodedFields);
                        $fieldsJson = json_encode($decodedFields);
                    }
                    update_post_meta($wpaicg_prompt_id, 'wpaicg_form_fields', $fieldsJson);
                }

                $wpaicg_result['msg'] = esc_html__('Custom form duplicated successfully.', 'gpt3-ai-content-generator');
                $wpaicg_result['new_id'] = $wpaicg_prompt_id;
                wp_send_json($wpaicg_result);
            }

            $wpaicg_result['status'] = 'error';
            $wpaicg_result['msg'] = esc_html__('Invalid duplication request', 'gpt3-ai-content-generator');
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_update_template()
        {
            $wpaicg_result = ['status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator')];

            if (!current_user_can('wpaicg_forms_forms')) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            if (!wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_formai_save')) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            if (
                isset($_POST['title']) && !empty($_POST['title']) &&
                isset($_POST['description']) && !empty($_POST['description']) &&
                isset($_POST['prompt']) && !empty($_POST['prompt'])
            ) {
                $title = sanitize_text_field($_POST['title']);
                $description = sanitize_text_field($_POST['description']);
                if (isset($_POST['id']) && !empty($_POST['id'])) {
                    $wpaicg_prompt_id = sanitize_text_field($_POST['id']);
                    wp_update_post([
                        'ID'           => $wpaicg_prompt_id,
                        'post_title'   => $title,
                        'post_content' => $description
                    ]);
                } else {
                    $wpaicg_prompt_id = wp_insert_post([
                        'post_title'   => $title,
                        'post_type'    => 'wpaicg_form',
                        'post_content' => $description,
                        'post_status'  => 'publish'
                    ]);
                }
                $template_fields = [
                    'prompt','fields','response','category','engine','max_tokens','temperature',
                    'top_p','best_of','frequency_penalty','presence_penalty','stop','color','icon',
                    'editor','bgcolor','header','embeddings','vectordb','collections','pineconeindexes',
                    'suffix_text','suffix_position','embeddings_limit','use_default_embedding_model',
                    'selected_embedding_model','selected_embedding_provider','dans','ddraft','dclear',
                    'dnotice','ddownload','copy_button','copy_text','feedback_buttons','generate_text',
                    'noanswer_text','draft_text','clear_text','stop_text','cnotice_text','download_text',
                    // Must include model_provider here as well
                    'model_provider'
                ];

                foreach ($template_fields as $template_field) {
                    if (isset($_POST[$template_field]) && !empty($_POST[$template_field])) {
                        if ($template_field === 'prompt') {
                            $value = wp_kses($_POST['prompt'], wp_kses_allowed_html('post'));
                        } else {
                            $value = wpaicg_util_core()->sanitize_text_or_array_field($_POST[$template_field]);
                        }

                        $key = sanitize_text_field($template_field);
                        if ($key === 'fields') {
                            $value = json_encode($value, JSON_UNESCAPED_UNICODE);
                        }
                        update_post_meta($wpaicg_prompt_id, 'wpaicg_form_' . $key, $value);
                    } elseif (
                        in_array($template_field, ['bgcolor','header','dans','ddraft','dclear','dnotice','ddownload','copy_button','feedback_buttons'], true) &&
                        (!isset($_POST[$template_field]) || empty($_POST[$template_field]))
                    ) {
                        delete_post_meta($wpaicg_prompt_id, 'wpaicg_form_' . $template_field);
                    }
                }
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['id'] = $wpaicg_prompt_id;
            }
            wp_send_json($wpaicg_result);
        }

        /**
         * Adds submenu pages for AI Forms
         */
        public function wpaicg_menu()
        {
            $module_settings = get_option('wpaicg_module_settings');
            if ($module_settings === false) {
                $module_settings = array_map(
                    fn() => true,
                    \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules
                );
            }

            $modules = \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules;

            if (isset($module_settings['ai_forms']) && $module_settings['ai_forms']) {
                add_submenu_page(
                    'wpaicg',
                    esc_html__($modules['ai_forms']['title'], 'gpt3-ai-content-generator'),
                    esc_html__($modules['ai_forms']['title'], 'gpt3-ai-content-generator'),
                    $modules['ai_forms']['capability'],
                    $modules['ai_forms']['menu_slug'],
                    [$this, $modules['ai_forms']['callback']],
                    $modules['ai_forms']['position']
                );
            }
        }

        /**
         * Callback for shortcodes: [wpaicg_form id="..."]
         */
        public function wpaicg_form_shortcode($atts)
        {
            ob_start();
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_form_shortcode.php';
            return ob_get_clean();
        }

        /**
         * The callback used by existing AI Forms page
         */
        public function wpaicg_forms()
        {
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms.php';
        }

        private function wpaicg_safe_unserialize($data)
        {
            if (!is_serialized($data)) {
                return $data;
            }

            if (version_compare(PHP_VERSION, '7.0.0', '>=')) {
                $unserialized = @unserialize($data, ['allowed_classes' => false]);
            } else {
                $unserialized = @unserialize($data);
            }

            if ($unserialized === false && $data !== serialize(false)) {
                return $data;
            }

            if (is_object($unserialized)) {
                return null;
            }

            return $unserialized;
        }

        /**
         * Return an array of all custom wpaicg_form post IDs (published).
         */
        public function wpaicg_get_custom_forms_ids()
        {
            check_ajax_referer('wpaicg-get-forms-ids-nonce','nonce'); // or can be a more generic nonce
            if(!current_user_can('manage_options')){
                wp_send_json_error('No permission');
            }
            global $wpdb;
            $ids = $wpdb->get_col("
                SELECT ID
                FROM {$wpdb->posts}
                WHERE post_type = 'wpaicg_form'
                AND post_status = 'publish'
                ORDER BY post_date DESC
            ");
            wp_send_json_success(['forms'=>$ids]);
        }

        /**
         * Export a single form's data as JSON
         */
        public function wpaicg_export_single_form()
        {
            check_ajax_referer('wpaicg-export-single-nonce','nonce');
            if(!current_user_can('manage_options')){
                wp_send_json_error('No permission');
            }
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            if(!$form_id){
                wp_send_json_error('Invalid form_id');
            }
            $post = get_post($form_id);
            if(!$post || $post->post_type !== 'wpaicg_form'){
                wp_send_json_error('No such custom form');
            }

            // Gather meta
            $meta = get_post_meta($form_id);
            // Flatten meta so that each meta_key => single value (or array if needed)
            $safeMeta = [];
            foreach($meta as $k => $v){
                if(count($v) === 1){
                    $safeMeta[$k] = maybe_unserialize($v[0]);
                } else {
                    // multiple values
                    $safeMeta[$k] = array_map('maybe_unserialize',$v);
                }
            }

            // Return a simpler structure
            $exportData = [
                'title'   => $post->post_title,
                'content' => $post->post_content,
                'meta'    => $safeMeta,
            ];
            wp_send_json_success($exportData);
        }

        /**
         * Import a single form from provided JSON data
         */
        public function wpaicg_import_single_form()
        {
            check_ajax_referer('wpaicg-import-single-nonce','nonce');
            if(!current_user_can('manage_options')){
                wp_send_json_error('No permission');
            }
            if(!isset($_POST['form_data'])){
                wp_send_json_error('No form_data');
            }
            $rawData = json_decode(stripslashes($_POST['form_data']), true);
            if(!is_array($rawData)){
                wp_send_json_error('Invalid JSON data');
            }

            $title = isset($rawData['title']) ? sanitize_text_field($rawData['title']) : 'Untitled';
            $content = isset($rawData['content']) ? wp_kses_post($rawData['content']) : '';
            $metaArr = isset($rawData['meta']) ? $rawData['meta'] : [];

            // Insert new custom form
            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_type'    => 'wpaicg_form',
                'post_content' => $content,
                'post_status'  => 'publish',
            ], true);

            if(is_wp_error($post_id)){
                wp_send_json_error('Could not import form: ' . $title);
            }

            if(is_array($metaArr)){
                foreach($metaArr as $mKey => $mVal){
                    if(is_array($mVal) && isset($mVal[0])){
                        // Possibly an array with multiple values
                        foreach($mVal as $single){
                            add_post_meta($post_id, sanitize_text_field($mKey), maybe_serialize($single));
                        }
                    } else {
                        // single value
                        update_post_meta($post_id, sanitize_text_field($mKey), maybe_serialize($mVal));
                    }
                }
            }

            wp_send_json_success(['imported_id'=>$post_id]);
        }

        /**
         * Delete a single custom form by ID
         */
        public function wpaicg_delete_single_form()
        {
            check_ajax_referer('wpaicg-delete-single-nonce','nonce');
            if(!current_user_can('manage_options')){
                wp_send_json_error('No permission');
            }
            $form_id = isset($_POST['form_id']) ? intval($_POST['form_id']) : 0;
            if(!$form_id){
                wp_send_json_error('Invalid form ID');
            }
            $post = get_post($form_id);
            if(!$post || $post->post_type !== 'wpaicg_form'){
                wp_send_json_error('Not a valid custom form');
            }
            wp_delete_post($form_id, true);
            wp_send_json_success();
        }

        /**
         * NEW: AJAX handler to create a brand-new form from the drag-and-drop builder.
         */
        public function wpaicg_create_new_form()
        {
            check_ajax_referer('wpaicg_create_form_nonce', 'nonce');

            if (!current_user_can('wpaicg_forms_forms')) {
                wp_send_json_error([
                    'message' => esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator')
                ]);
            }

            $title       = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
            $description = isset($_POST['description']) ? sanitize_text_field($_POST['description']) : '';
            $prompt      = isset($_POST['prompt']) ? wp_kses_post($_POST['prompt']) : '';
            // Must read provider from posted data
            $provider    = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
            $fields_json = isset($_POST['fields']) ? wp_unslash($_POST['fields']) : '';
            $engine      = isset($_POST['engine']) ? sanitize_text_field($_POST['engine']) : 'gpt-4o-mini';

            $model_settings = isset($_POST['model_settings']) ? (array) $_POST['model_settings'] : [];
            $wpaicg_max_tokens        = isset($model_settings['max_tokens']) ? intval($model_settings['max_tokens']) : 1500;
            $wpaicg_top_p             = isset($model_settings['top_p']) ? floatval($model_settings['top_p']) : 1;
            $wpaicg_best_of           = isset($model_settings['best_of']) ? intval($model_settings['best_of']) : 1;
            $wpaicg_frequency_penalty = isset($model_settings['frequency_penalty']) ? floatval($model_settings['frequency_penalty']) : 0;
            $wpaicg_presence_penalty  = isset($model_settings['presence_penalty']) ? floatval($model_settings['presence_penalty']) : 0;
            $wpaicg_stop              = isset($model_settings['stop']) ? sanitize_text_field($model_settings['stop']) : '';

            if (empty($title) || empty($description) || empty($prompt)) {
                wp_send_json_error([
                    'message' => esc_html__('Required fields missing: title, description, or prompt', 'gpt3-ai-content-generator')
                ]);
            }

            $post_id = wp_insert_post([
                'post_title'   => $title,
                'post_type'    => 'wpaicg_form',
                'post_content' => $description,
                'post_status'  => 'publish'
            ]);

            if (is_wp_error($post_id)) {
                wp_send_json_error([
                    'message' => esc_html__('Failed to create new form', 'gpt3-ai-content-generator')
                ]);
            }

            // Correct: store provider in wpaicg_form_model_provider
            update_post_meta($post_id, 'wpaicg_form_model_provider', $provider);

            update_post_meta($post_id, 'wpaicg_form_prompt', $prompt);
            if (!empty($fields_json)) {
                update_post_meta($post_id, 'wpaicg_form_fields', $fields_json);
            }

            update_post_meta($post_id, 'wpaicg_form_engine', $engine);
            update_post_meta($post_id, 'wpaicg_form_max_tokens', $wpaicg_max_tokens);
            update_post_meta($post_id, 'wpaicg_form_top_p', $wpaicg_top_p);
            update_post_meta($post_id, 'wpaicg_form_best_of', $wpaicg_best_of);
            update_post_meta($post_id, 'wpaicg_form_frequency_penalty', $wpaicg_frequency_penalty);
            update_post_meta($post_id, 'wpaicg_form_presence_penalty', $wpaicg_presence_penalty);
            update_post_meta($post_id, 'wpaicg_form_stop', $wpaicg_stop);

            // Embedding
            $embedding_settings = isset($_POST['embedding_settings']) ? (array) $_POST['embedding_settings'] : [];
            $use_embeddings = !empty($embedding_settings['use_embeddings']) ? sanitize_text_field($embedding_settings['use_embeddings']) : 'no';
            update_post_meta($post_id, 'wpaicg_form_embeddings', $use_embeddings);

            $vector_db = !empty($embedding_settings['vectordb']) ? sanitize_text_field($embedding_settings['vectordb']) : 'pinecone';
            update_post_meta($post_id, 'wpaicg_form_vectordb', $vector_db);

            $collections = !empty($embedding_settings['collections']) ? sanitize_text_field($embedding_settings['collections']) : '';
            update_post_meta($post_id, 'wpaicg_form_collections', $collections);

            $pineconeindexes = !empty($embedding_settings['pineconeindexes']) ? sanitize_text_field($embedding_settings['pineconeindexes']) : '';
            update_post_meta($post_id, 'wpaicg_form_pineconeindexes', $pineconeindexes);

            $suffix_text = !empty($embedding_settings['suffix_text']) ? sanitize_text_field($embedding_settings['suffix_text']) : 'Context:';
            update_post_meta($post_id, 'wpaicg_form_suffix_text', $suffix_text);

            $suffix_position = !empty($embedding_settings['suffix_position']) ? sanitize_text_field($embedding_settings['suffix_position']) : 'after';
            update_post_meta($post_id, 'wpaicg_form_suffix_position', $suffix_position);

            $use_default = !empty($embedding_settings['use_default_embedding_model']) ? sanitize_text_field($embedding_settings['use_default_embedding_model']) : 'yes';
            update_post_meta($post_id, 'wpaicg_form_use_default_embedding_model', $use_default);

            $selected_provider = !empty($embedding_settings['selected_embedding_provider']) ? sanitize_text_field($embedding_settings['selected_embedding_provider']) : '';
            update_post_meta($post_id, 'wpaicg_form_selected_embedding_provider', $selected_provider);

            $selected_model = !empty($embedding_settings['selected_embedding_model']) ? sanitize_text_field($embedding_settings['selected_embedding_model']) : '';
            update_post_meta($post_id, 'wpaicg_form_selected_embedding_model', $selected_model);

            $embed_limit = !empty($embedding_settings['embeddings_limit']) ? intval($embedding_settings['embeddings_limit']) : 1;
            update_post_meta($post_id, 'wpaicg_form_embeddings_limit', $embed_limit);

            // Interface
            $interfaceFields = [
                'wpaicg_form_response'          => 'div',
                'wpaicg_form_category'          => '',
                'wpaicg_form_color'             => '#f9f9f9',
                'wpaicg_form_icon'              => '',
                'wpaicg_form_editor'            => 'div',
                'wpaicg_form_header'            => 'yes',
                'wpaicg_form_copy_button'       => 'yes',
                'wpaicg_form_ddraft'            => 'yes',
                'wpaicg_form_dclear'            => 'yes',
                'wpaicg_form_dnotice'           => 'yes',
                'wpaicg_form_ddownload'         => 'yes',
                'wpaicg_form_copy_text'         => 'Copy',
                'wpaicg_form_feedback_buttons'  => 'yes',
                'wpaicg_form_generate_text'     => 'Generate',
                'wpaicg_form_noanswer_text'     => 'Number of Answers',
                'wpaicg_form_draft_text'        => 'Save Draft',
                'wpaicg_form_clear_text'        => 'Clear',
                'wpaicg_form_stop_text'         => 'Stop',
                'wpaicg_form_cnotice_text'      => 'Please register to save your result',
                'wpaicg_form_download_text'     => 'Download',
                'wpaicg_form_bgcolor'           => '#f9f9f9',
            ];
            if (isset($_POST['interface']) && is_array($_POST['interface'])) {
                foreach ($interfaceFields as $ik => $defaultVal) {
                    $val = isset($_POST['interface'][$ik]) ? sanitize_text_field($_POST['interface'][$ik]) : $defaultVal;
                    update_post_meta($post_id, $ik, $val);
                }
            } else {
                foreach ($interfaceFields as $ik => $df) {
                    update_post_meta($post_id, $ik, $df);
                }
            }

            // NEW: Internet Browsing toggle (yes/no)
            $internet_browsing = isset($_POST['internet_browsing']) ? sanitize_text_field($_POST['internet_browsing']) : 'no';
            update_post_meta($post_id, 'wpaicg_form_internet_browsing', $internet_browsing);

            wp_send_json_success([
                'message' => esc_html__('Form created successfully', 'gpt3-ai-content-generator'),
                'id'      => $post_id
            ]);
        }

        /******************************************************
         * NEW: GET LOGS WITH PAGINATION & INSTANT SEARCH
         ******************************************************/
        public function wpaicg_get_logs()
        {
            check_ajax_referer('wpaicg-ajax-nonce', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'No permission']);
            }

            global $wpdb;
            $table_name    = $wpdb->prefix . 'wpaicg_form_logs';
            $feedbackTable = $wpdb->prefix . 'wpaicg_form_feedback';

            $search  = isset($_POST['search']) ? sanitize_text_field($_POST['search']) : '';
            $page    = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $per_page= 10;

            $where = '1=1';
            if (!empty($search)) {
                $like = '%'.$wpdb->esc_like($search).'%';
                $where .= $wpdb->prepare(
                    ' AND (name LIKE %s OR model LIKE %s OR prompt LIKE %s OR data LIKE %s OR duration LIKE %s OR tokens LIKE %s)',
                    $like, $like, $like, $like, $like, $like
                );
            }

            // JOIN feedback table to retrieve feedback & comment
            $total_sql = "SELECT COUNT(*) 
                          FROM $table_name l
                          LEFT JOIN $feedbackTable f ON l.eventID = f.eventID
                          WHERE $where";
            $total = $wpdb->get_var($total_sql);
            $total_pages = max(1, (int)ceil($total / $per_page));

            if ($page < 1) {
                $page = 1;
            }
            if ($page > $total_pages) {
                $page = $total_pages;
            }

            $offset = ($page - 1) * $per_page;

            // Main query, includes feedback/comment fields
            $logs_sql = $wpdb->prepare("
                SELECT l.*, f.feedback, f.comment
                FROM $table_name l
                LEFT JOIN $feedbackTable f ON l.eventID = f.eventID
                WHERE $where
                ORDER BY created_at DESC
                LIMIT %d, %d
            ", $offset, $per_page);

            $rows = $wpdb->get_results($logs_sql);

            $html_rows = '';
            if ($rows) {
                foreach ($rows as $row) {
                    $id       = $row->id;
                    $name     = esc_html($row->name);
                    $model    = esc_html($row->model);
                    $duration = esc_html($row->duration);
                    $tokens   = intval($row->tokens);
                    // If feedback = 'thumbs_up', show 👍; if 'thumbs_down', show 👎; else empty
                    $feedbackVal = isset($row->feedback) ? $row->feedback : '';
                    $feedbackDisplay = '';
                    if ($feedbackVal === 'thumbs_up') {
                        $feedbackDisplay = '👍';
                    } elseif ($feedbackVal === 'thumbs_down') {
                        $feedbackDisplay = '👎';
                    }
                    $comment  = isset($row->comment) ? esc_html($row->comment) : '';

                    $fullPrompt   = $row->prompt;
                    $fullResponse = $row->data;

                    $cleanResponse = strip_tags($fullResponse);
                    if (function_exists('mb_strlen')) {
                        $truncatedData = (mb_strlen($cleanResponse) > 25)
                            ? mb_substr($cleanResponse, 0, 25).'...'
                            : $cleanResponse;
                    } else {
                        $truncatedData = (strlen($cleanResponse) > 25)
                            ? substr($cleanResponse, 0, 25).'...'
                            : $cleanResponse;
                    }
                    $created = date_i18n('Y-m-d H:i:s', $row->created_at);

                    $html_rows .= "<tr>
                        <td>{$id}</td>
                        <td>{$name}</td>
                        <td>{$model}</td>
                        <td>{$duration}</td>
                        <td>{$tokens}</td>
                        <td>
                            <span class='wpaicg_log_view'
                                  data-fullprompt='".esc_attr($fullPrompt)."'
                                  data-fullresponse='".esc_attr($fullResponse)."'>
                                ".esc_html($truncatedData)."
                            </span>
                        </td>
                        <td>{$feedbackDisplay}</td>
                        <td>{$comment}</td>
                        <td>{$created}</td>
                    </tr>";
                }
            } else {
                $html_rows = '<tr><td colspan="9">'.esc_html__('No logs found','gpt3-ai-content-generator').'</td></tr>';
            }

            $pagination_html = $this->wpaicg_build_pagination_html($page, $total_pages);

            wp_send_json_success([
                'table_rows' => $html_rows,
                'pagination' => $pagination_html,
                'total'      => $total
            ]);
        }

        private function wpaicg_build_pagination_html(int $current_page, int $total_pages): string
        {
            if ($total_pages <= 1) {
                return '';
            }

            $html = '<div class="wpaicg_logs_pagination">';

            // Page 1
            if ($current_page > 1) {
                $html .= '<span class="wpaicg_logs_page_link" data-page="1">1</span>';
            } else {
                $html .= '<span class="wpaicg_logs_page_link active" data-page="1">1</span>';
            }

            if ($current_page > 3) {
                $html .= '<span class="wpaicg_logs_page_ellipsis">…</span>';
            }

            $start = max(2, $current_page - 1);
            $end   = min($total_pages - 1, $current_page + 1);

            for ($i = $start; $i <= $end; $i++) {
                if ($i === 1 || $i === $total_pages) {
                    continue;
                }
                if ($i === $current_page) {
                    $html .= '<span class="wpaicg_logs_page_link active" data-page="'.$i.'">'.$i.'</span>';
                } else {
                    $html .= '<span class="wpaicg_logs_page_link" data-page="'.$i.'">'.$i.'</span>';
                }
            }

            if ($current_page < $total_pages - 2) {
                $html .= '<span class="wpaicg_logs_page_ellipsis">…</span>';
            }

            if ($current_page < $total_pages) {
                $html .= '<span class="wpaicg_logs_page_link" data-page="'.$total_pages.'">'.$total_pages.'</span>';
            } else {
                if ($total_pages > 1) {
                    $html .= '<span class="wpaicg_logs_page_link active" data-page="'.$total_pages.'">'.$total_pages.'</span>';
                }
            }

            $html .= '</div>';
            return $html;
        }
    }

    WPAICG_Forms::get_instance();
}