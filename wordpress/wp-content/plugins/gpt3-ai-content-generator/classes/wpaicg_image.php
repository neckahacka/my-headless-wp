<?php

namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_Image')) {
    class WPAICG_Image
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
            add_action('wp_ajax_wpaicg_image_generator',[$this, 'wpaicg_image_generator_action']);
            add_action('wp_ajax_nopriv_wpaicg_image_generator',[$this, 'wpaicg_image_generator_action']);
            add_action('wp_ajax_wpaicg_image_stable_diffusion',[$this, 'wpaicg_image_stable_diffusion']);
            add_action('wp_ajax_nopriv_wpaicg_image_stable_diffusion',[$this, 'wpaicg_image_stable_diffusion']);
            add_action('wp_ajax_nopriv_wpaicg_save_image_media',[$this, 'wpaicg_save_image_media']);
            add_action('wp_ajax_wpaicg_save_image_media',[$this, 'wpaicg_save_image_media']);
            add_shortcode('wpcgai_img',[$this,'wpaicg_image_generator_shortcode']);
            add_action( 'admin_menu', array( $this, 'wpaicg_menu' ) );
            add_action('wp_ajax_wpaicg_image_log', [$this,'wpaicg_image_log']);
            add_action('wp_ajax_nopriv_wpaicg_image_log', [$this,'wpaicg_image_log']);
            add_action('wp_ajax_wpaicg_image_default', [$this,'wpaicg_image_default']);
            if ( ! wp_next_scheduled( 'wpaicg_remove_image_tokens_limited' ) ) {
                wp_schedule_event( time(), 'hourly', 'wpaicg_remove_image_tokens_limited' );
            }
            add_action( 'wpaicg_remove_image_tokens_limited', array( $this, 'wpaicg_remove_tokens_limit' ) );
            add_action('wp_ajax_wpaicg_delete_all_image_logs', [$this,'wpaicg_delete_all_image_logs']);
            add_action('wp_ajax_wpaicg_fetch_replicate_models', [$this,'wpaicg_fetch_replicate_models']);
        }

        public function wpaicg_fetch_replicate_models() {
            global $wpdb;
            $result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));
        
            if (!current_user_can('manage_options')) {
                $result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($result);
            }
        
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }
        
            $api_key = get_option('wpaicg_sd_api_key');
            $url = 'https://api.replicate.com/v1/collections/text-to-image';
        
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key
                )
            ));
        
            if (is_wp_error($response)) {
                $result['msg'] = esc_html__('Failed to fetch models', 'gpt3-ai-content-generator');
                wp_send_json($result);
            }
        
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true); // Decode the response body as an associative array
        
            // Check if there's an error response from the API
            if (isset($data['status']) && $data['status'] !== 200) {
                // If an error is present, display the actual error message from the API
                $error_msg = isset($data['detail']) ? $data['detail'] : esc_html__('An error occurred', 'gpt3-ai-content-generator');
                $result['msg'] = esc_html__('API Error: ', 'gpt3-ai-content-generator') . $error_msg;
                wp_send_json($result);
            }
        
            if (isset($data['models'])) {
                // Sort models by run_count descending
                usort($data['models'], function($a, $b) {
                    return $b['run_count'] - $a['run_count'];
                });
        
                // Function to format numbers into human-readable format
                function format_run_count($num) {
                    if ($num >= 1000000) {
                        return round($num / 1000000, 1) . 'M';
                    } elseif ($num >= 1000) {
                        return round($num / 1000, 1) . 'K';
                    }
                    return $num;
                }
        
                // Group by owner and store latest version
                $grouped_models = array();
                foreach ($data['models'] as $model) {
                    $owner = $model['owner'] ?? 'Unknown';
                    $model_name = $model['name'] ?? '';
                    $model_version = $model['latest_version']['id'] ?? '';
                    $schema_details = $this->get_replicate_model_schema($owner, $model_name, $model_version, $api_key);
        
                    $grouped_models[$owner][] = array(
                        'name' => $model_name,
                        'owner' => $owner,
                        'url' => $model['url'] ?? '',
                        'run_count' => format_run_count($model['run_count'] ?? 0),
                        'latest_version' => $model_version,
                        'schema' => $schema_details // Add schema details to the model data
                    );
                }
        
                update_option('wpaicg_replicate_models', $grouped_models);
                $result['status'] = 'success';
                $result['models'] = $grouped_models;
            } else {
                $result['msg'] = esc_html__('No models found', 'gpt3-ai-content-generator');
            }
        
            wp_send_json($result);
        }
        
        // Function to fetch schema details for a model
        private function get_replicate_model_schema($owner, $model_name, $version_id, $api_key) {
            $url = "https://api.replicate.com/v1/models/{$owner}/{$model_name}/versions/{$version_id}";
            
            $response = wp_remote_get($url, array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $api_key
                )
            ));
            
            if (is_wp_error($response)) {
                return null;
            }
        
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
        
            if (!isset($data['openapi_schema'])) {
                return null;
            }
        
            $schema = $data['openapi_schema'];
        
            // Extract only the Input and Output components
            $filtered_schema = array(
                'Input' => $schema['components']['schemas']['Input'] ?? null,
                'Output' => $schema['components']['schemas']['Output'] ?? null,
            );
        
            return $filtered_schema; // Return the filtered schema details
        }

        public function wpaicg_delete_all_image_logs() {
            check_ajax_referer('wpaicg_delete_all_image_logs_nonce', 'nonce');
            
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'You do not have sufficient permissions']);
                return;
            }
        
            global $wpdb;
            $wpaicgFormLogTable = $wpdb->prefix . 'wpaicg_image_logs';
        
            $result = $wpdb->query("TRUNCATE TABLE `$wpaicgFormLogTable`");
        
            if ($result === false) {
                wp_send_json_error(['message' => 'Failed to delete logs']);
            } else {
                wp_send_json_success(['message' => 'All logs have been deleted successfully']);
            }
        }

        public function wpaicg_remove_tokens_limit()
        {
            global $wpdb;
            $wpaicg_settings = get_option('wpaicg_limit_tokens_image',[]);
            $widget_reset_limit = isset($wpaicg_settings['reset_limit']) && !empty($wpaicg_settings['reset_limit']) ? $wpaicg_settings['reset_limit'] : 0;
            if($widget_reset_limit > 0) {
                $widget_time = time() - ($widget_reset_limit * 86400);
                $wpdb->query($wpdb->prepare("DELETE FROM " . $wpdb->prefix . "wpaicg_imagetokens WHERE created_at < %s",$widget_time));
            }
        }

        public function wpaicg_image_default()
        {
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg-image-generator' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(isset($_REQUEST['type_default']) && !empty($_REQUEST['type_default'])){
                $type = sanitize_text_field($_REQUEST['type_default']);
                $keys = array(
                    'artist',
                    'art_style',
                    'photography_style',
                    'lighting',
                    'subject',
                    'camera_settings',
                    'composition',
                    'resolution',
                    'color',
                    'special_effects',
                    'img_size',
                    'img_model',
                    'img_type',
                    'num_images',
                    'negative_prompt',
                    'width',
                    'height',
                    'prompt_strength',
                    'num_outputs',
                    'num_inference_steps',
                    'guidance_scale',
                    'scheduler'
                );
                $result = array();
                foreach($keys as $key){
                    if(isset($_REQUEST[$key]) && !empty($_REQUEST[$key])){
                        $result[$key] = sanitize_text_field($_REQUEST[$key]);
                    }
                }

                if(isset($_REQUEST['scheduler']) && $_REQUEST['scheduler'] == "Custom" && isset($_REQUEST['customScheduler'])){
                    $result['scheduler'] = sanitize_text_field($_REQUEST['customScheduler']);
                } else if(isset($_REQUEST['scheduler'])){
                    $result['scheduler'] = sanitize_text_field($_REQUEST['scheduler']);
                }

                update_option('wpaicg_image_setting_'.$type,$result);
            }
            wp_send_json(array('status' => 'success'));
        }

        public function wpaicg_images_price()
        {
            $image_price = 0;
            if(
                isset($_REQUEST['img_size']) && !empty($_REQUEST['img_size']) &&
                isset($_REQUEST['num_images']) && !empty($_REQUEST['num_images']) &&
                isset($_REQUEST['img_model']) && !empty($_REQUEST['img_model'])
            ){
                $num_images = (int) sanitize_text_field($_REQUEST['num_images']);
                $img_size = sanitize_text_field($_REQUEST['img_size']);
                $img_model = sanitize_text_field($_REQUEST['img_model']);

                // Prices based on model and size
                $prices = [
                    'dall-e-2' => [
                        '256x256' => 0.016,
                        '512x512' => 0.018,
                        '1024x1024' => 0.020,
                    ],
                    'dall-e-3' => [
                        '1024x1024' => 0.040,
                        '1024x1792' => 0.080,
                        '1792x1024' => 0.080,
                    ],
                    'dall-e-3-hd' => [
                        '1024x1024' => 0.080,
                        '1024x1792' => 0.120,
                        '1792x1024' => 0.120,
                    ],
                ];

                // Check if the model and size combination exists in the pricing array
                if (isset($prices[$img_model]) && isset($prices[$img_model][$img_size])) {
                    $image_price = $prices[$img_model][$img_size] * $num_images;
                }
            }

            return $image_price;
        }


        public function wpaicg_image_log()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'success');
            $wpaicg_nonce = sanitize_text_field($_REQUEST['_wpnonce_image_log']);
            if ( !wp_verify_nonce( $wpaicg_nonce, 'wpaicg-imagelog' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
                exit;
            }
            if(
                isset($_REQUEST['prompt'])
                && !empty($_REQUEST['prompt'])
                && isset($_REQUEST['img_size'])
                && !empty($_REQUEST['img_size'])
                && isset($_REQUEST['shortcode'])
                && !empty($_REQUEST['shortcode'])
                && isset($_REQUEST['num_images'])
                && !empty($_REQUEST['num_images'])
                && isset($_REQUEST['duration'])
                && !empty($_REQUEST['duration'])
            ){
                $log = array(
                    'prompt' => sanitize_text_field($_REQUEST['prompt']),
                    'size' => sanitize_text_field($_REQUEST['img_size']),
                    'shortcode' => sanitize_text_field($_REQUEST['shortcode']),
                    'total' => sanitize_text_field($_REQUEST['num_images']),
                    'duration' => sanitize_text_field($_REQUEST['duration']),
                    'created_at' => time()
                );
                if(isset($_REQUEST['source_id']) && !empty($_REQUEST['source_id'])){
                    $log['source'] = sanitize_text_field($_REQUEST['source_id']);
                }
                $image_price = $this->wpaicg_images_price();
                $log['price'] = $image_price;
                $wpdb->insert($wpdb->prefix.'wpaicg_image_logs', $log);
                $wpaicg_pricing_handling = WPAICG_Playground::get_instance()->wpaicg_token_handling('image');
                if($wpaicg_pricing_handling['limit']){
                    if($wpaicg_pricing_handling['token_id']){
                        $wpdb->update($wpdb->prefix.$wpaicg_pricing_handling['table'], array(
                            'tokens' => ($log['price'] + $wpaicg_pricing_handling['old_tokens'])
                        ), array('id' => $wpaicg_pricing_handling['token_id']));
                    }
                    else{
                        $wpaicg_prompt_token_data = array(
                            'tokens' => $log['price'],
                            'created_at' => time()
                        );
                        if(is_user_logged_in()){
                            $wpaicg_prompt_token_data['user_id'] = get_current_user_id();
                        }
                        else{
                            $wpaicg_prompt_token_data['session_id'] = $wpaicg_pricing_handling['client_id'];
                        }
                        $wpdb->insert($wpdb->prefix.$wpaicg_pricing_handling['table'],$wpaicg_prompt_token_data);
                    }
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_menu()
        {
            $module_settings = get_option('wpaicg_module_settings');
            if ($module_settings === false) {
                $module_settings = array_map(function() { return true; }, \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules);
            }
        
            $modules = \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules;
            if (isset($module_settings['image_generator']) && $module_settings['image_generator']) {
                add_submenu_page(
                    'wpaicg',
                    esc_html__($modules['image_generator']['title'], 'gpt3-ai-content-generator'),
                    esc_html__($modules['image_generator']['title'], 'gpt3-ai-content-generator'),
                    $modules['image_generator']['capability'],
                    $modules['image_generator']['menu_slug'],
                    array($this, $modules['image_generator']['callback']),
                    $modules['image_generator']['position']
                );
            }
        }

        public function wpaicg_admin_footer()
        {
            ?>
            <div class="wpaicg-overlay" style="display: none">
                <div class="wpaicg_modal">
                    <div class="wpaicg_modal_head">
                        <span class="wpaicg_modal_title"><?php echo esc_html__('GPT3 Modal','gpt3-ai-content-generator')?></span>
                        <span class="wpaicg_modal_close">&times;</span>
                    </div>
                    <div class="wpaicg_modal_content"></div>
                </div>
            </div>
            <div class="wpcgai_lds-ellipsis" style="display: none">
                <div class="wpaicg-generating-title"><?php echo esc_html__('Generating content..','gpt3-ai-content-generator')?></div>
                <div class="wpaicg-generating-process"></div>
                <div class="wpaicg-timer"></div>
            </div>
            <?php
        }

        public function wpaicg_image_generator_shortcode($wpaicg_shortcode_settings)
        {
            add_action('wp_footer',[$this,'wpaicg_admin_footer']);
            ob_start();
            include WPAICG_PLUGIN_DIR.'admin/extra/wpaicg_image_shortcode.php';
            return ob_get_clean();
        }

        public function wpaicg_image_generator_action()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));

            // verify nonce wpaicg-image-generator
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg-image-generator' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            $wpaicg_pricing_handling = WPAICG_Playground::get_instance()->wpaicg_token_handling('image');

            if($wpaicg_pricing_handling['limited']){
                $wpaicg_result['msg'] = $wpaicg_pricing_handling['message'];
            }
            else {
                $prompt = sanitize_text_field($_POST['prompt']);
                $prompt_title = sanitize_text_field($_POST['prompt']);
                $img_size = sanitize_text_field($_POST['img_size']);
                $img_model = sanitize_text_field($_POST['img_model']);
                $img_type = sanitize_text_field($_POST['img_type']);

                $extra_params = [];

                // Initialize the quality variable.
                $quality = '';
                $style = '';
                

                // Check if the model is 'Dall-E 3 HD', modify the model and set quality to 'hd'.
                if ($img_model === 'dall-e-3-hd') {
                    $img_model = 'dall-e-3'; // Remove '-hd' part
                    $extra_params['model'] = $img_model;
                    $extra_params['quality'] = 'hd';
                } elseif ($img_model === 'dall-e-3') {
                    $extra_params['model'] = $img_model;
                }
                
                $num_images = (int)sanitize_text_field($_POST['num_images']);
                // Set the number of images to 1 if the model is 'dall-e-3' or 'dall-e-3-hd'.
                // Set the number of images to 1 if the model is 'dall-e-3' or 'dall-e-3-hd'.
                if ($img_model === 'dall-e-3' || $img_model === 'dall-e-3-hd') {
                    $num_images = 1;

                    // If the image size is either '256x256' or '512x512', set it to '1024x1024'.
                    if (in_array($img_size, ['256x256', '512x512'])) {
                        $img_size = '1024x1024';
                    }
                    // Add style to extra_params
                    $extra_params['style'] = $img_type;
                }

                $prompt_elements = array(
                    'artist' => esc_html__('Painter','gpt3-ai-content-generator'),
                    'art_style' => esc_html__('Style','gpt3-ai-content-generator'),
                    'photography_style' => esc_html__('Photography Style','gpt3-ai-content-generator'),
                    'composition' => esc_html__('Composition','gpt3-ai-content-generator'),
                    'resolution' => esc_html__('Resolution','gpt3-ai-content-generator'),
                    'color' => esc_html__('Color','gpt3-ai-content-generator'),
                    'special_effects' => esc_html__('Special Effects','gpt3-ai-content-generator'),
                    'lighting' => esc_html__('Lighting','gpt3-ai-content-generator'),
                    'subject' => esc_html__('Subject','gpt3-ai-content-generator'),
                    'camera_settings' => esc_html__('Camera Settings','gpt3-ai-content-generator'),
                );
                foreach ($prompt_elements as $key => $value) {
                    if ($_POST[$key] != "None") {
                        $prompt = $prompt . ". " . $value . ": " . sanitize_text_field($_POST[$key]);
                    }
                }

                // Merge the base parameters with the extra parameters.
                $img_params = array_merge([
                    "prompt" => $prompt,
                    "n" => $num_images,
                    "size" => $img_size,
                    "response_format" => "url"
                ], $extra_params);

                // Get the AI engine.
                try {
                    $ai_engine = WPAICG_Util::get_instance()->initialize_ai_engine();
                } catch (\Exception $e) {
                    $wpaicg_result['msg'] = $e->getMessage();
                    wp_send_json($wpaicg_result);
                }

                // Make the API call with the combined parameters.
                $imgresult = $ai_engine->image($img_params);

                if (isset($imgresult['status']) && $imgresult['status'] === 'error') {
                    $wpaicg_result['msg'] = $imgresult['msg'];
                    wp_send_json($wpaicg_result);
                } 


                $img_result = json_decode($imgresult);

                if (isset($img_result->error)) {
                    $wpaicg_result['msg'] = trim($img_result->error->message);
                    if(strpos($wpaicg_result['msg'],'limit has been reached') !== false){
                        $wpaicg_result['msg'] .= ' '.esc_html__('Please note that this message is coming from OpenAI and it is not related to our plugin. It means that you do not have enough credit from OpenAI. You can check your usage here: https://platform.openai.com/account/usage','gpt3-ai-content-generator');
                    }
                } else {
                    $wpaicg_result['imgs'] = array();
                    for ($i = 0; $i < $num_images; $i++) {
                        $wpaicg_result['imgs'][] = $img_result->data[$i]->url;
                    }
                    $wpaicg_result['title'] = $prompt_title;
                    $wpaicg_result['status'] = 'success';
                    /*Save log for user and deduce tokens*/
                    WPAICG_Account::get_instance()->save_log('image', $this->wpaicg_images_price());
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_stable_diffusion_images($url, $headers)
        {
            $images = array();
            try {
                $response = wp_remote_get($url, array('headers' => $headers));
                if(is_wp_error($response)){
                    $images = $response->get_error_message();
                }
                else{
                    $body = json_decode($response['body'],true);
                    if($body['status'] == 'succeeded'){
                        $images = $body['output'];
                    }
                    elseif($body['status'] == 'processing' || $body['status'] == 'starting'){
                        $images = $this->wpaicg_stable_diffusion_images($url, $headers);
                    }
                    elseif($body['status'] == 'failed'){
                        $images = $body['error'];
                    }
                    else{
                        $images = esc_html__('Something went wrong','gpt3-ai-content-generator');
                    }
                }
            }
            catch (\Exception $exception){
                $images = $exception->getMessage();
            }
            return $images;
        }

        public function wpaicg_image_stable_diffusion()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));
            $wpaicg_nonce = sanitize_text_field($_REQUEST['_wpnonce']);
            if ( !wp_verify_nonce( $wpaicg_nonce, 'wpaicg-image-generator' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
            }
            else {
                $wpaicg_sd_api_key = get_option('wpaicg_sd_api_key', '');
                $wpaicg_sd_api_version = get_option('wpaicg_sd_api_version', 'ac732df83cea7fff18b8472768c88ad041fa750ff7682a21affe81863cbe77e4');
                if (empty($wpaicg_sd_api_key)) {
                    $wpaicg_result['msg'] = esc_html__('Missing Stable Diffusion API','gpt3-ai-content-generator');
                } else {
                    if (isset($_REQUEST['prompt']) && !empty($_REQUEST['prompt'])) {
                        $headers = array(
                            'Content-Type' => 'application/json',
                            'Authorization' => 'Token ' . $wpaicg_sd_api_key
                        );
                        $prompt_title = sanitize_text_field($_REQUEST['prompt']);
                        $prompt = sanitize_text_field($_REQUEST['prompt']);
                        $prompt_elements = array(
                            'artist' => esc_html__('Painter','gpt3-ai-content-generator'),
                            'art_style' => esc_html__('Style','gpt3-ai-content-generator'),
                            'photography_style' => esc_html__('Photography Style','gpt3-ai-content-generator'),
                            'composition' => esc_html__('Composition','gpt3-ai-content-generator'),
                            'resolution' => esc_html__('Resolution','gpt3-ai-content-generator'),
                            'color' => esc_html__('Color','gpt3-ai-content-generator'),
                            'special_effects' => esc_html__('Special Effects','gpt3-ai-content-generator'),
                            'lighting' => esc_html__('Lighting','gpt3-ai-content-generator'),
                            'subject' => esc_html__('Subject','gpt3-ai-content-generator'),
                            'camera_settings' => esc_html__('Camera Settings','gpt3-ai-content-generator'),
                        );

                        if(isset($_REQUEST['scheduler']) && $_REQUEST['scheduler'] == "Custom" && isset($_REQUEST['customScheduler'])){
                            $schedulerValue = sanitize_text_field($_REQUEST['customScheduler']);
                        } else if(isset($_REQUEST['scheduler'])) {
                            $schedulerValue = sanitize_text_field($_REQUEST['scheduler']);
                        } else {
                            $schedulerValue = 'DPMSolverMultistep';
                        }

                        foreach ($prompt_elements as $key => $value) {
                            if ($_POST[$key] != "None") {
                                $prompt = $prompt . ". " . $value . ": " . sanitize_text_field($_POST[$key]);
                            }
                        }
                        $body = array(
                            'version' => $wpaicg_sd_api_version,
                            'input' => array(
                                'prompt' => $prompt,
                                'num_outputs' => 1,
                                'negative_prompt' => isset($_REQUEST['negative_prompt']) && !empty($_REQUEST['negative_prompt']) ? sanitize_text_field($_REQUEST['negative_prompt']) : '',
                                'width' => isset($_REQUEST['width']) && !empty($_REQUEST['width']) ? (float)sanitize_text_field($_REQUEST['width']) : 768,
                                'height' => isset($_REQUEST['height']) && !empty($_REQUEST['height']) ? (float)sanitize_text_field($_REQUEST['height']) : 768,
                                'prompt_strength' => isset($_REQUEST['prompt_strength']) && !empty($_REQUEST['prompt_strength']) ? (float)sanitize_text_field($_REQUEST['prompt_strength']) : 0.8,
                                'num_inference_steps' => isset($_REQUEST['num_inference_steps']) && !empty($_REQUEST['num_inference_steps']) ? (float)sanitize_text_field($_REQUEST['num_inference_steps']) : 50,
                                'scheduler' => $schedulerValue,
                            )
                        );

                        try {
                            $wpaicg_response = wp_remote_post('https://api.replicate.com/v1/predictions', array(
                                'headers' => $headers,
                                'body' => json_encode($body)
                            ));
                            if (is_wp_error($wpaicg_response)) {
                                $wpaicg_result['msg'] = $wpaicg_response->get_error_message();
                            } else {
                                $response_body = isset($wpaicg_response['body']) && !empty($wpaicg_response['body']) ? json_decode($wpaicg_response['body'], true) : false;
                                if (isset($response_body['detail']) && !empty($response_body['detail'])) {
                                    $wpaicg_result['msg'] = $response_body['detail'];
                                } elseif (!isset($response_body['urls']['get'])) {
                                    $wpaicg_result['msg'] = 'Empty results';
                                } else {
                                    $images = $this->wpaicg_stable_diffusion_images($response_body['urls']['get'], $headers);
                                    if (!is_array($images)) {
                                        $images = array($images);
                                        $wpaicg_result['title'] = $prompt_title;
                                        $wpaicg_result['imgs'] = $images;
                                        $wpaicg_result['status'] = 'success';
                                        $wpaicg_result['prompt'] = $prompt;
                                    } else {
                                        $wpaicg_result['title'] = $prompt_title;
                                        $wpaicg_result['imgs'] = $images;
                                        $wpaicg_result['status'] = 'success';
                                        $wpaicg_result['prompt'] = $prompt;
                                    }
                                }
                            }
                        } catch (\Exception $exception) {
                            $wpaicg_result['msg'] = $exception->getMessage();
                        }
                    } else {
                        $wpaicg_result['msg'] = esc_html__('Please insert prompt','gpt3-ai-content-generator');
                    }
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_save_image_media()
        {
            // Default error response
            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong', 'gpt3-ai-content-generator')
            );
        
            // 1) Verify nonce
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
        
            /**
             * 2) Permission Checks:
             *    - Must be able to upload files
             *    - Must have at least one of the Image Generator sub-module caps
             */
            if (
                ! current_user_can('wpaicg_image_generator_dalle') &&
                ! current_user_can('wpaicg_image_generator_stable-diffusion')
            ) {
                $wpaicg_result['msg'] = esc_html__('You do not have permission to upload images','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
        
            // 3) Check required field
            if ( empty($_POST['image_url']) ) {
                wp_send_json($wpaicg_result);
            }
        
            // 4) Sanitize data
            $url               = sanitize_url($_POST['image_url']);
            $image_title       = isset($_POST['image_title'])       ? sanitize_text_field($_POST['image_title'])       : '';
            $image_alt         = isset($_POST['image_alt'])         ? sanitize_text_field($_POST['image_alt'])         : '';
            $image_caption     = isset($_POST['image_caption'])     ? sanitize_text_field($_POST['image_caption'])     : '';
            $image_description = isset($_POST['image_description']) ? sanitize_text_field($_POST['image_description']) : '';
        
            // 5) Strip shortcodes from alt text to prevent shortcode injection
            $image_alt = strip_shortcodes($image_alt);
        
            // 6) Save the image
            $wpaicg_image_attachment_id = WPAICG_Content::get_instance()->wpaicg_save_image($url, $image_title, false);
        
            if ($wpaicg_image_attachment_id['status'] === 'success') {
                // Update the newly created attachment
                wp_update_post(array(
                    'ID'           => $wpaicg_image_attachment_id['id'],
                    'post_content' => $image_description,
                    'post_excerpt' => $image_caption
                ));
                // Update alt text
                update_post_meta($wpaicg_image_attachment_id['id'], '_wp_attachment_image_alt', $image_alt);
        
                $wpaicg_result['status'] = 'success';
            } else {
                $wpaicg_result['msg'] = $wpaicg_image_attachment_id['msg'];
            }
        
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_image_generator()
        {
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_image_generator.php';
        }

    }
    WPAICG_Image::get_instance();
}
