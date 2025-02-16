<?php

namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_Promptbase')) {
    class WPAICG_Promptbase
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
            add_shortcode('wpaicg_prompt',[$this,'wpaicg_prompt_shortcode']);
            add_action('wp_ajax_wpaicg_update_prompt',[$this,'wpaicg_update_prompt']);
            add_action('wp_ajax_wpaicg_prompt_delete',[$this,'wpaicg_prompt_delete']);
            add_action('wp_ajax_wpaicg_prompt_log', [$this,'wpaicg_prompt_log']);
            add_action('wp_ajax_wpaicg_prompt_duplicate', [$this,'wpaicg_prompt_duplicate']);
            // wpaicg_export_prompts
            add_action('wp_ajax_wpaicg_export_prompts', [$this,'wpaicg_export_prompts']);
            add_action('wp_ajax_nopriv_wpaicg_export_prompts', [$this,'wpaicg_export_prompts']);
            // wpaicg_import_prompts
            add_action('wp_ajax_wpaicg_import_prompts', [$this,'wpaicg_import_prompts']);
            add_action('wp_ajax_nopriv_wpaicg_import_prompts', [$this,'wpaicg_import_prompts']);
            // wpaicg_delete_all_prompts
            add_action('wp_ajax_wpaicg_delete_all_prompts', [$this,'wpaicg_delete_all_prompts']);
            add_action('wp_ajax_nopriv_wpaicg_delete_all_prompts', [$this,'wpaicg_delete_all_prompts']);
            add_action('wp_ajax_nopriv_wpaicg_prompt_log', [$this,'wpaicg_prompt_log']);
            if ( ! wp_next_scheduled( 'wpaicg_remove_promptbase_tokens_limited' ) ) {
                wp_schedule_event( time(), 'hourly', 'wpaicg_remove_promptbase_tokens_limited' );
            }
            add_action( 'wpaicg_remove_promptbase_tokens_limited', array( $this, 'wpaicg_remove_tokens_limit' ) );
            add_action('wp_ajax_wpaicg_delete_all_prompt_logs', [$this,'wpaicg_delete_all_prompt_logs']);
        }

        public function wpaicg_delete_all_prompt_logs() {
            check_ajax_referer('wpaicg_delete_all_prompt_logs_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'You do not have sufficient permissions']);
                return;
            }
        
            global $wpdb;
            $wpaicgFormLogTable = $wpdb->prefix . 'wpaicg_promptbase_logs';
            $wpaicgFeedbackTable = $wpdb->prefix . 'wpaicg_prompt_feedback';
        
            // Truncate the form logs table
            $resultLogs = $wpdb->query("TRUNCATE TABLE `$wpaicgFormLogTable`");
            // Truncate the feedback table
            $resultFeedback = $wpdb->query("TRUNCATE TABLE `$wpaicgFeedbackTable`");
        
            if ($resultLogs === false || $resultFeedback === false) {
                wp_send_json_error(['message' => 'Failed to delete logs and feedback']);
            } else {
                wp_send_json_success(['message' => 'All logs and feedback have been deleted successfully']);
            }
        }

        function wpaicg_export_prompts() {
            global $wpdb, $wp_filesystem;
        
            // Security and permissions checks
            $nonce = isset($_REQUEST['nonce']) ? $_REQUEST['nonce'] : '';
            if (!wp_verify_nonce($nonce, 'wpaicg_export_prompts')) {
                wp_send_json_error(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
                return;
            }
            if (!current_user_can('manage_options')) {
                wp_send_json_error(esc_html__('You do not have sufficient permissions to access this page.', 'gpt3-ai-content-generator'));
                return;
            }
        
            // WP_Filesystem initialization
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            WP_Filesystem();
        
            // Fetch Prompts and their meta
            $forms = $wpdb->get_results("
                SELECT ID, post_title, post_content
                FROM {$wpdb->posts}
                WHERE post_type = 'wpaicg_prompt'
                  AND post_status = 'publish'
            ", ARRAY_A);
        
            $settings = [];
            foreach ($forms as $form) {
                if (!empty($form['post_content'])) {
                    $meta = get_post_meta($form['ID']);
                    // Instead of maybe_unserialize, safely unserialize:
                    $content = $this->wpaicg_safe_unserialize($form['post_content']);
        
                    $settings[] = [
                        'title'   => $form['post_title'],
                        'content' => $content,
                        'meta'    => $meta, // Include meta data
                    ];
                }
            }
        
            // JSON encoding and file saving
            $json_content = json_encode($settings);
            $upload_dir   = wp_upload_dir();
            $file_name    = 'prompts_export_' . wp_rand() . '.json';
            $file_path    = $upload_dir['basedir'] . '/' . $file_name;
        
            if ($wp_filesystem->put_contents($file_path, $json_content)) {
                wp_send_json_success(['url' => $upload_dir['baseurl'] . '/' . $file_name]);
            } else {
                wp_send_json_error(esc_html__('Failed to export AI forms.', 'gpt3-ai-content-generator'));
            }
        }

        function wpaicg_import_prompts() {
            // Security checks
            if (!check_ajax_referer('wpaicg_import_prompts_nonce', 'nonce', false)) {
                wp_send_json_error('Nonce verification failed');
                return;
            }
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error('You do not have sufficient permissions');
                return;
            }
            
            // Check if file is uploaded and read its contents
            if (isset($_FILES['file']['tmp_name'])) {
                $file_contents = file_get_contents($_FILES['file']['tmp_name']);
                $data = json_decode($file_contents, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    wp_send_json_error('Invalid JSON file');
                    return;
                }
                
                foreach ($data as $form_data) {
                    if (empty($form_data['title']) || empty($form_data['content'])) {
                        continue;
                    }
                    
                    $post_data = [
                        'post_title'   => sanitize_text_field($form_data['title']),
                        'post_content' => wp_kses_post($form_data['content']),
                        'post_status'  => 'publish',
                        'post_type'    => 'wpaicg_prompt',
                    ];
                    
                    $post_id = wp_insert_post($post_data, true);
                    
                    if (is_wp_error($post_id)) {
                        continue;
                    }
                    
                    if (!empty($form_data['meta']) && is_array($form_data['meta'])) {
                        foreach ($form_data['meta'] as $meta_key => $meta_value) {
                            // Ensure meta values are saved as simple strings where appropriate
                            if (is_array($meta_value) && count($meta_value) === 1) {
                                // If it's a single-element array, extract the value directly
                                $meta_value = reset($meta_value);
                            }
                            update_post_meta($post_id, sanitize_text_field($meta_key), $meta_value);
                        }
                    }
                }
                
                wp_send_json_success('AI forms imported successfully');
            } else {
                wp_send_json_error('No file uploaded');
            }
        }

        function wpaicg_delete_all_prompts() {
            // Security checks
            if (!check_ajax_referer('wpaicg_delete_all_prompts_nonce', 'nonce', false)) {
                wp_send_json_error('Nonce verification failed');
                return;
            }
        
            if (!current_user_can('manage_options')) {
                wp_send_json_error('You do not have sufficient permissions');
                return;
            }
        
            $args = [
                'post_type'      => 'wpaicg_prompt',
                'posts_per_page' => -1,
                'fields'         => 'ids', // Only get post IDs to improve performance
            ];
        
            $forms = get_posts($args);
        
            // Check if there are forms to delete
            if (empty($forms)) {
                wp_send_json_success('There are no custom forms to delete.');
                return; // Exit the function if no forms found
            }
        
            foreach ($forms as $form_id) {
                wp_delete_post($form_id, true); // Set to true to bypass trash and permanently delete
            }
        
            wp_send_json_success('All custom forms have been deleted.');
        }

        public function wpaicg_remove_tokens_limit()
        {
            global $wpdb;
            $wpaicg_settings = get_option('wpaicg_limit_tokens_promptbase',[]);
            $widget_reset_limit = isset($wpaicg_settings['reset_limit']) && !empty($wpaicg_settings['reset_limit']) ? $wpaicg_settings['reset_limit'] : 0;
            if($widget_reset_limit > 0) {
                $widget_time = time() - ($widget_reset_limit * 86400);
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wpaicg_prompttokens WHERE created_at < %s",$widget_time));
            }
        }

        public function wpaicg_prompt_log()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'success');
            $wpaicg_nonce = sanitize_text_field($_REQUEST['_wpnonce']);
            if ( !wp_verify_nonce( $wpaicg_nonce, 'wpaicg-promptbase' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
                exit;
            }
            if(
                isset($_REQUEST['prompt_id'])
                && !empty($_REQUEST['prompt_id'])
                && isset($_REQUEST['prompt_name'])
                && !empty($_REQUEST['prompt_name'])
                && isset($_REQUEST['prompt_response'])
                && !empty($_REQUEST['prompt_response'])
                && isset($_REQUEST['engine'])
                && !empty($_REQUEST['engine'])
                && isset($_REQUEST['title'])
                && !empty($_REQUEST['title'])
            ){
                // Check if a user is logged in
                if(is_user_logged_in()) {
                    // Get the logged-in user's ID
                    $userID = get_current_user_id();
                } else {
                    $userID = ""; // Set to empty string if user is not logged in
                }
                $log = array(
                    'prompt' => wp_kses_post($_REQUEST['title']),
                    'data' => wp_kses_post($_REQUEST['prompt_response']),
                    'prompt_id' => sanitize_text_field($_REQUEST['prompt_id']),
                    'name' => sanitize_text_field($_REQUEST['prompt_name']),
                    'model' => sanitize_text_field($_REQUEST['engine']),
                    'duration' => sanitize_text_field($_REQUEST['duration']),
                    'eventID' => sanitize_text_field($_REQUEST['eventID']),
                    'userID' => $userID,
                    'created_at' => time()
                );
                if(isset($_REQUEST['source_id']) && !empty($_REQUEST['source_id'])){
                    $log['source'] = sanitize_text_field($_REQUEST['source_id']);
                }
                $wpaicg_generator = WPAICG_Generator::get_instance();
                $log['tokens'] = ceil($wpaicg_generator->wpaicg_count_words($log['data'])*1000/750);
                WPAICG_Account::get_instance()->save_log('promptbase',$log['tokens']);
                $wpdb->insert($wpdb->prefix.'wpaicg_promptbase_logs', $log);
                $wpaicg_playground = WPAICG_Playground::get_instance();
                $wpaicg_tokens_handling = $wpaicg_playground->wpaicg_token_handling('promptbase');
                if($wpaicg_tokens_handling['limit']){
                    if($wpaicg_tokens_handling['token_id']){
                        $wpdb->update($wpdb->prefix.$wpaicg_tokens_handling['table'], array(
                            'tokens' => ($log['tokens'] + $wpaicg_tokens_handling['old_tokens'])
                        ), array('id' => $wpaicg_tokens_handling['token_id']));
                    }
                    else{
                        $wpaicg_prompt_token_data = array(
                            'tokens' => $log['tokens'],
                            'created_at' => time()
                        );
                        if(is_user_logged_in()){
                            $wpaicg_prompt_token_data['user_id'] = get_current_user_id();
                        }
                        else{
                            $wpaicg_prompt_token_data['session_id'] = $wpaicg_tokens_handling['client_id'];
                        }
                        $wpdb->insert($wpdb->prefix.$wpaicg_tokens_handling['table'],$wpaicg_prompt_token_data);
                    }
                }

            }
            wp_send_json($wpaicg_result);
        }
        
        public function wpaicg_update_prompt()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));
            if(!current_user_can('wpaicg_promptbase_promptbase')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg_promptbase_save' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(
                isset($_POST['title'])
                && !empty($_POST['title'])
                && isset($_POST['description'])
                && !empty($_POST['description'])
                && isset($_POST['prompt'])
                && !empty($_POST['prompt'])
            ){
                $title = sanitize_text_field($_POST['title']);
                $description = sanitize_text_field($_POST['description']);
                if(isset($_POST['id']) && !empty($_POST['id'])){
                    $wpaicg_prompt_id = sanitize_text_field($_POST['id']);
                    wp_update_post(array(
                        'ID' => $wpaicg_prompt_id,
                        'post_title' => $title,
                        'post_content' => $description
                    ));
                }
                else{
                    $wpaicg_prompt_id = wp_insert_post(array(
                        'post_title' => $title,
                        'post_type' => 'wpaicg_prompt',
                        'post_content' => $description,
                        'post_status' => 'publish'
                    ));
                }
                $prompt_fields = array('prompt','response','category','engine','max_tokens','temperature','top_p','best_of','frequency_penalty','presence_penalty','stop','color','icon','editor','bgcolor','header','embeddings','use_default_embedding_model','selected_embedding_model','selected_embedding_provider','vectordb','collections','pineconeindexes','suffix_text','suffix_position','embeddings_limit','dans','ddraft','dclear','dnotice','generate_text','noanswer_text','draft_text','clear_text','stop_text','cnotice_text','download_text','ddownload','copy_button','copy_text','feedback_buttons');
                
                foreach($prompt_fields as $prompt_field){
                    if(isset($_POST[$prompt_field]) && !empty($_POST[$prompt_field])){

                        if ($prompt_field == 'prompt') {
                            $value = wp_kses($_POST['prompt'], wp_kses_allowed_html('post'));
                        } else {
                            $value = wpaicg_util_core()->sanitize_text_or_array_field($_POST[$prompt_field]);
                        }
                
                        $key = sanitize_text_field($prompt_field);
                        update_post_meta($wpaicg_prompt_id, 'wpaicg_prompt_'.$key, $value);
                    }
                    elseif(in_array($prompt_field,array('bgcolor','header','dans','ddraft','dclear','dnotice','ddownload','copy_button','feedback_buttons')) && (!isset($_POST[$prompt_field]) || empty($_POST[$prompt_field]))){
                        delete_post_meta($wpaicg_prompt_id, 'wpaicg_prompt_'.$prompt_field);
                    }
                }
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['id'] = $wpaicg_prompt_id;
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_prompt_duplicate()
        {
            $wpaicg_result = array('status' => 'success');
            if(!current_user_can('wpaicg_promptbase_promptbase')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(isset($_POST['id']) && !empty($_POST['id'])){
                $promptbase = get_post(sanitize_post($_REQUEST['id']));
                $wpaicg_prompt_id = wp_insert_post(array(
                    'post_title' => $promptbase->post_title,
                    'post_type' => 'wpaicg_prompt',
                    'post_content' => $promptbase->post_content,
                    'post_status' => 'publish'
                ));
                $post_meta = get_post_meta( $promptbase->ID );
                if( $post_meta ) {

                    foreach ( $post_meta as $meta_key => $meta_values ) {

                        if( '_wp_old_slug' == $meta_key ) { // do nothing for this meta key
                            continue;
                        }

                        foreach ( $meta_values as $meta_value ) {
                            add_post_meta( $wpaicg_prompt_id, $meta_key, $meta_value );
                        }
                    }
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_prompt_delete()
        {
            $wpaicg_result = array('status' => 'success');
            if(!current_user_can('wpaicg_promptbase_promptbase')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(isset($_POST['id']) && !empty($_POST['id'])){
                wp_delete_post(sanitize_text_field($_POST['id']));
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_promptbase()
        {
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_promptbase.php';
        }

        public function wpaicg_prompt_shortcode($atts)
        {
            ob_start();
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_prompt_shortcode.php';
            return ob_get_clean();
        }

        private function wpaicg_safe_unserialize($data) {
            // Early check: If it’s obviously not a serialized string, just return as-is.
            if ( ! is_serialized($data) ) {
                return $data;
            }
        
            // On PHP 7+ we can prevent object rehydration by disallowing all classes.
            if ( version_compare(PHP_VERSION, '7.0.0', '>=') ) {
                $unserialized = @unserialize($data, ['allowed_classes' => false]);
            } else {
                // Fallback for older PHP. (In practice, strongly encourage upgrading PHP!)
                $unserialized = @unserialize($data);
            }
        
            // If unserialize() fails, return the original string (some prompts may just have plain text).
            if ($unserialized === false && $data !== serialize(false)) {
                return $data;
            }
        
            // If it’s an object, discard it so no malicious object can be rehydrated. 
            // Return null (or an empty array/string) to prevent the injection.
            if ( is_object($unserialized) ) {
                return null; 
            }
        
            // Otherwise, it’s an array/scalar—safe to return.
            return $unserialized;
        }
    }
    WPAICG_Promptbase::get_instance();
}
