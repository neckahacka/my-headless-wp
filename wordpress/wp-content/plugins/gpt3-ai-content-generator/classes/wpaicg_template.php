<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_Template')) {
    class WPAICG_Template
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
            add_action('wp_ajax_wpaicg_template_generator', [$this,'wpaicg_template_generator']);
            add_action('wp_ajax_wpaicg_template_post', [$this,'wpaicg_template_post']);
            add_action('wp_ajax_wpaicg_save_template', [$this,'wpaicg_save_template']);
            add_action('wp_ajax_wpaicg_template_delete', [$this,'wpaicg_template_delete']);
        }

        public function wpaicg_template_delete()
        {
            $wpaicg_result = array('status' => 'error', 'msg'=>'Missing request');
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(
                isset($_REQUEST['id'])
                && !empty($_REQUEST['id'])
            ){
                wp_delete_post(sanitize_text_field($_REQUEST['id']));
                $wpaicg_result['status'] = 'success';
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_save_template()
        {
            $wpaicg_result = array('status' => 'error', 'msg'=>esc_html__('Missing request','gpt3-ai-content-generator'));
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg_custom_mode_generator' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(
                isset($_REQUEST['title'])
                && !empty($_REQUEST['title'])
                && isset($_REQUEST['template'])
                && is_array($_REQUEST['template'])
                && count($_REQUEST['template'])
            ){
                $template = wpaicg_util_core()->sanitize_text_or_array_field($_REQUEST['template']);
                $template['title'] = sanitize_text_field($_REQUEST['title']);
                $template_id = false;
                if(isset($_REQUEST['id']) && !empty($_REQUEST['id'])){
                    $template_id = $_REQUEST['id'];
                }
                if(isset($_REQUEST['title_count']) && !empty($_REQUEST['title_count'])){
                    $template['title_count'] = sanitize_text_field($_REQUEST['title_count']);
                }
                if(isset($_REQUEST['section_count']) && !empty($_REQUEST['section_count'])){
                    $template['section_count'] = sanitize_text_field($_REQUEST['section_count']);
                }
                if(isset($_REQUEST['paragraph_count']) && !empty($_REQUEST['paragraph_count'])){
                    $template['paragraph_count'] = sanitize_text_field($_REQUEST['paragraph_count']);
                }
                $post_content = json_encode($template);
                if($template_id){
                    wp_update_post(array(
                        'ID' => $template_id,
                        'post_title' => $template['title'],
                        'post_content' => $post_content
                    ));
                }
                else{
                    $template_id = wp_insert_post(array(
                        'post_status' => 'publish',
                        'post_type' => 'wpaicg_mtemplate',
                        'post_title' => $template['title'],
                        'post_content' => $post_content
                    ));
                }
                $selected_template = $template_id;
                ob_start();
                include WPAICG_PLUGIN_DIR.'admin/extra/wpaicg_single.php';
                $wpaicg_result['setting'] = ob_get_clean();
                $wpaicg_result['status'] = 'success';
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_template_post()
        {
            $wpaicg_result = array('status' => 'error', 'msg'=>esc_html__('Missing request','gpt3-ai-content-generator'));
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(isset($_REQUEST['title']) && !empty($_REQUEST['title']) && isset($_REQUEST['content']) && !empty($_REQUEST['content'])){
                $title = sanitize_text_field($_REQUEST['title']);
                $content = wp_kses_post($_REQUEST['content']);
                $new_content = array();
                $exs = array_map('trim', explode("\n", $content));
                foreach($exs as $ex){
                    if(strpos($ex, '##') !== false){
                        $new_content[] = '<h2>'.trim(str_replace('##','',$ex)).'</h2>';
                    }
                    else $new_content[] = $ex;
                }
                $new_content = implode("\n",$new_content);
                $post_type = 'post';
                if(isset($_REQUEST['post_type']) && !empty($_REQUEST['post_type'])){
                    $post_type = sanitize_text_field($_REQUEST['post_type']);
                }
                $wpaicg_data = array(
                    'post_title' => $title,
                    'post_content' => $new_content,
                    'post_status' => 'draft',
                    'post_type' => $post_type
                );
                if(isset($_REQUEST['excerpt']) && !empty($_REQUEST['excerpt'])){
                    $wpaicg_data['post_excerpt'] = sanitize_text_field($_REQUEST['excerpt']);
                }
                $wpaicg_post_id = wp_insert_post($wpaicg_data);
                if(is_wp_error($wpaicg_post_id)){
                    $wpaicg_result['msg'] = $wpaicg_post_id->get_error_message();
                    wp_send_json($wpaicg_result);
                }
            else{
                $content_class = WPAICG_Content::get_instance();
                if(isset($_REQUEST['description']) && !empty($_REQUEST['description'])){
                    $content_class->wpaicg_save_description($wpaicg_post_id, sanitize_text_field($_REQUEST['description']));
                }
                $wpaicg_duration = isset($_REQUEST['duration']) && !empty($_REQUEST['duration']) ? sanitize_text_field($_REQUEST['duration']) : 0;
                $wpaicg_usage_token = isset($_REQUEST['tokens']) && !empty($_REQUEST['tokens']) ? sanitize_text_field($_REQUEST['tokens']) : 0;
                $wpaicg_word_count = isset($_REQUEST['words']) && !empty($_REQUEST['words']) ? sanitize_text_field($_REQUEST['words']) : 0;
                $wpaicg_log_id = wp_insert_post(array(
                    'post_title' => 'WPAICGLOG:' . $title,
                    'post_type' => 'wpaicg_slog',
                    'post_status' => 'publish'
                ));

                $wpaicg_provider = isset($_REQUEST['provider']) && !empty($_REQUEST['provider']) ? sanitize_text_field($_REQUEST['provider']) : 'openai';

                // Next, check the provider and set the model accordingly
                if ($wpaicg_provider === 'openai') {
                    // If the provider is OpenAI, use the 'model' variable
                    $wpaicg_ai_model = isset($_REQUEST['model']) && !empty($_REQUEST['model']) ? sanitize_text_field($_REQUEST['model']) : 'gpt-3.5-turbo-16k';
                } elseif ($wpaicg_provider === 'google') {
                    // If the provider is Google, use the 'google_model' variable
                    $wpaicg_ai_model = isset($_REQUEST['google_model']) && !empty($_REQUEST['google_model']) ? sanitize_text_field($_REQUEST['google_model']) : 'gemini-pro';
                } elseif ($wpaicg_provider === 'openrouter') {
                    // If the provider is openrouter, use the 'openrouter_model' variable
                    $wpaicg_ai_model = isset($_REQUEST['openrouter_model']) && !empty($_REQUEST['openrouter_model']) ? sanitize_text_field($_REQUEST['openrouter_model']) : 'openrouter/auto';
                } elseif ($wpaicg_provider === 'azure') {
                    // If the provider is Azure, use the 'azure_deployment' variable
                    $wpaicg_ai_model = isset($_REQUEST['azure_deployment']) && !empty($_REQUEST['azure_deployment']) ? sanitize_text_field($_REQUEST['azure_deployment']) : get_option('wpaicg_azure_deployment', '');
                } else {
                    // Fallback in case the provider is not recognized
                    $wpaicg_ai_model = 'gpt-3.5-turbo-16k';
                }

                $source_log = 'custom';
                add_post_meta($wpaicg_log_id, 'wpaicg_source_log', $source_log);
                add_post_meta($wpaicg_log_id, 'wpaicg_ai_model', $wpaicg_ai_model);
                add_post_meta($wpaicg_log_id, 'wpaicg_duration', $wpaicg_duration);
                add_post_meta($wpaicg_log_id, 'wpaicg_usage_token', $wpaicg_usage_token);
                add_post_meta($wpaicg_log_id, 'wpaicg_word_count', $wpaicg_word_count);
                add_post_meta($wpaicg_log_id, 'wpaicg_post_id', $wpaicg_post_id);
                add_post_meta($wpaicg_log_id, 'wpaicg_provider', $wpaicg_provider);
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['id'] = $wpaicg_post_id;
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_template_generator()
        {
            $wpaicg_result = array('status' => 'error', 'msg'=>esc_html__('Missing request','gpt3-ai-content-generator'));
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg_custom_mode_generator' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if($_REQUEST['template'] && is_array($_REQUEST['template']) && count($_REQUEST['template']) && isset($_REQUEST['step']) && !empty($_REQUEST['step'])){
                $step = sanitize_text_field($_REQUEST['step']);
                $template = wpaicg_util_core()->sanitize_text_or_array_field($_REQUEST['template']);
                $prompt = '';
                $title_count = (int)sanitize_text_field($_REQUEST['title_count']);
                $section_count = (int)sanitize_text_field($_REQUEST['section_count']);
                $paragraph_count = sanitize_text_field($_REQUEST['paragraph_count']);
                $post_title = isset($_REQUEST['post_title']) && !empty($_REQUEST['post_title']) ? sanitize_text_field($_REQUEST['post_title']) : '';
                $sections = isset($_REQUEST['sections']) && !empty($_REQUEST['sections']) ? sanitize_text_field($_REQUEST['sections']) : '';
                $list_sections = array();
                if($step == 'titles'){
                    $topic = sanitize_text_field($_REQUEST['topic']);
                    $prompt = $template['prompt_title'];
                    $prompt = str_replace('[count]',$title_count,$prompt);
                    $prompt = str_replace('[topic]',$topic,$prompt);
                }
                if($step == 'sections'){
                    if(empty($post_title)){
                        $wpaicg_result['msg'] = esc_html__('Please generate title first','gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $prompt = $template['prompt_section'];
                    $prompt = str_replace('[count]',$section_count,$prompt);
                    $prompt = str_replace('[title]',$post_title,$prompt);
                }
                if($step == 'excerpt'){
                    if(empty($post_title)){
                        $wpaicg_result['msg'] = esc_html__('Please generate title first','gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $prompt = $template['prompt_excerpt'];
                    $prompt = str_replace('[title]',$post_title,$prompt);
                }
                if($step == 'meta'){
                    if(empty($post_title)){
                        $wpaicg_result['msg'] = esc_html__('Please generate title first','gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $prompt = $template['prompt_meta'];
                    $prompt = str_replace('[title]',$post_title,$prompt);
                }
                if($step == 'content'){
                    if(empty($post_title)){
                        $wpaicg_result['msg'] = esc_html__('Please generate title first','gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    if(empty($sections)){
                        $wpaicg_result['msg'] = esc_html__('Please generate sections first','gpt3-ai-content-generator');
                        wp_send_json($wpaicg_result);
                    }
                    $exs = array_map('trim', explode("##", $sections));
                    foreach($exs as $key=> $ex){
                        $section = trim(str_replace("\n",'',$ex));
                        if(!empty($section)) {
                            $list_sections[] = $section;
                        }
                    }
                    $new_sections = implode("\n",$list_sections);
                    $prompt = $template['prompt_content'];
                    $prompt = str_replace('[count]',$paragraph_count,$prompt);
                    $prompt = str_replace('[title]',$post_title,$prompt);
                    $prompt = str_replace('[sections]',$new_sections,$prompt);
                }
                $wpaicg_provider = $template['provider'];

                if (in_array($wpaicg_provider, ['openai', 'azure', 'openrouter'])) {
                    // Determine the correct instance based on the provider
                    switch ($wpaicg_provider) {
                        case 'openai':
                            $openai = WPAICG_OpenAI::get_instance()->openai();
                            $model = $template['model'];
                            break;
                        case 'azure':
                            $openai = WPAICG_AzureAI::get_instance()->azureai();
                            $model = !empty($template['azure_deployment']) ? $template['azure_deployment'] : get_option('wpaicg_azure_deployment', '');
                            break;
                        case 'openrouter':
                            $openai = WPAICG_OpenRouter::get_instance()->openai();
                            $model = $template['model'];
                            break;
                    }
                
                    $generator = WPAICG_Generator::get_instance();
                    if ($wpaicg_provider == 'openrouter') {
                        $generator->openai(WPAICG_OpenRouter::get_instance());
                    } else {
                        $generator->openai($openai);
                    }

                    $data_request = array(
                        'prompt' => $prompt,
                        'model' => $model,
                        'temperature' => (float)$template['temperature'],
                        'max_tokens' => (float)$template['max_tokens'],
                        'top_p' => (float)$template['top_p'],
                        'best_of' => 1,
                        'frequency_penalty' => (float)$template['frequency_penalty'],
                        'presence_penalty' => (float)$template['presence_penalty'],
                    );
                    // Add a unique value to the data request if the provider is Azure
                    if ($wpaicg_provider == 'azure') {
                        $data_request['isAzure'] = true;  // Unique flag for Azure
                    }
                    if($step == 'sections'){
                        $data_request['stop'] = ($section_count+1).'.';
                    }
                    if($step == 'titles'){
                        $data_request['stop'] = ($title_count+1).'.';
                    }
                    $result = $generator->wpaicg_request($data_request);
                } elseif ($wpaicg_provider == 'google') {
                    $googleAI = WPAICG_Google::get_instance();
                    $title = $prompt;
                    $model = $template['google_model'];
                    $temperature = (float)$template['temperature'];
                    $top_p = (float)$template['top_p'];
                    $max_tokens = (float)$template['max_tokens'];
                    $result = $googleAI->send_google_request($title, $model, $temperature, $top_p, $max_tokens,'template');

                    if (!empty($result['status']) && $result['status'] === 'error') {
                        wp_send_json(['msg' => $result['msg'], 'status' => 'error']);
                    } else {
                        $result['data'] = $result['data'];
                        $result['msg'] = 'success';
                        $result['status'] = 'success';
                    }
                    
                }
                if ($result['status'] == 'error') {
                    $wpaicg_result['msg'] = $result['msg'];
                }
                else{
                    // $wpaicg_result['data_open'] = $result['data'];
                    if($step == 'titles' || $step == 'sections'){
                        $complete = $result['data'];
                        $words_count = $wpaicg_provider == 'google' ? str_word_count($complete) : $generator->wpaicg_count_words($complete);
                        $complete = trim( $complete );
                        $complete=preg_replace('/\n$/','',preg_replace('/^\n/','',preg_replace('/[\r\n]+/',"\n",$complete)));
                        $mylist = preg_split( "/\r\n|\n|\r/", $complete );
                        $mylist = preg_replace( '/^\\d+\\.\\s/', '', $mylist );
                        $mylist = preg_replace( '/\\.$/', '', $mylist );
                        if($mylist && is_array($mylist) && count($mylist)){
                            $newlist = array();
                            foreach($mylist as $item){
                                $newlist[] = str_replace('"','',$item);
                            }
                            $wpaicg_result['data'] = $newlist;
                            $wpaicg_result['status'] = 'success';
                            $wpaicg_result['tokens'] = $wpaicg_provider == 'google' ? ($words_count / 750) * 1000 : $result['tokens'];
                            $wpaicg_result['words'] = $words_count;
                        }
                        else{
                            $wpaicg_result['msg'] = esc_html__('No data generated','gpt3-ai-content-generator');
                        }
                    }
                    if($step == 'content'){
                        $content = $result['data'];
                        $wpaicg_result['content'] = $content;
                        $words_count = $wpaicg_provider == 'google' ? str_word_count($content) : $generator->wpaicg_count_words($content);
                        foreach($list_sections as $list_section){
                            $list_section = str_replace('\\','',$list_section);
                            if(strpos($content,$list_section.':') !== false){
                                $content = str_replace($list_section.':',$list_section,$content);
                            }
                            if(strpos($content,$list_section."\n") === false){
                                $content = str_replace($list_section,$list_section."\n",$content);
                            }
                            $content = str_replace($list_section,'## '.$list_section, $content);
                        }
                        $wpaicg_result['data'] = $content;
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['tokens'] = $wpaicg_provider == 'google' ? ($words_count / 750) * 1000 : $result['tokens'];
                        $wpaicg_result['words'] = $words_count;
                    }
                    if($step == 'meta' || $step == 'excerpt'){
                        $content = $result['data'];
                        $words_count = $wpaicg_provider == 'google' ? str_word_count($content) : $generator->wpaicg_count_words($content);
                        $wpaicg_result['data'] = $content;
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['tokens'] = $wpaicg_provider == 'google' ? ($words_count / 750) * 1000 : $result['tokens'];
                        $wpaicg_result['words'] = $words_count;
                    }
                    $wpaicg_result['prompt'] = $prompt;

                }
            }
            wp_send_json($wpaicg_result);
        }
    }
    WPAICG_Template::get_instance();
}
