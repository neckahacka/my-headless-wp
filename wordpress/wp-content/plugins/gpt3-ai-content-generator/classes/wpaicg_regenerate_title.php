<?php

namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_Regenerate_Title')) {
    class WPAICG_Regenerate_Title
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
            add_filter('post_row_actions',[$this,'wpaicg_regenerate_action'],10,2);
            add_filter('page_row_actions',[$this,'wpaicg_regenerate_action'],10,2);
            add_action('admin_footer',[$this,'wpaicg_regenerate_footer']);
            add_action('wp_ajax_wpaicg_regenerate_title',[$this,'wpaicg_regenerate_title']);
            add_action('wp_ajax_wpaicg_regenerate_save',[$this,'wpaicg_regenerate_save']);
        }

        public function wpaicg_regenerate_save()
        {
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));
            if(!current_user_can('wpaicg_suggester')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(isset($_POST['title']) && !empty($_POST['title']) && isset($_POST['id']) && !empty($_POST['id'])){
                $id = sanitize_text_field($_POST['id']);
                $title = sanitize_text_field($_POST['title']);
                $check = wp_update_post(array(
                    'ID' => $id,
                    'post_title' => $title
                ));
                if(is_wp_error($check)){
                    $wpaicg_result['msg'] = $check->get_error_message();
                }
                else{
                    $wpaicg_result['status'] = 'success';
                }
            }
            wp_send_json($wpaicg_result);
        }

        private function process_ai_response($complete) {
            $result = ['status' => 'error', 'msg' => ''];
        
            if (isset($complete['status']) && $complete['status'] == 'error') {
                $result['msg'] = isset($complete['msg']) ? $complete['msg'] : 'Something went wrong';
            } else {
                $responseData = trim($complete['data']);
                $responseData = preg_replace('/\n$/', '', preg_replace('/^\n/', '', preg_replace('/[\r\n]+/', "\n", $responseData)));
                // remove <br> tags
                $responseData = preg_replace('/<br[^>]*>/', "\n", $responseData);
                $titleList = preg_split("/\r\n|\n|\r/", $responseData);
                $titleList = preg_replace('/^\\d+\\.\\s/', '', $titleList);
                $titleList = preg_replace('/\\.$/', '', $titleList);
                //remove empty lines
                $titleList = array_filter($titleList, function($item) {
                    return !empty($item);
                });
                // trim
                $titleList = array_map('trim', $titleList);
        
                if ($titleList && is_array($titleList) && count($titleList)) {
                    $newlist = array_map(function($item) {
                        return str_replace('"', '', $item);
                    }, $titleList);
        
                    $result = ['status' => 'success', 'data' => $newlist];
                } else {
                    $result['msg'] = esc_html__('No title generated', 'gpt3-ai-content-generator');
                }
            }
        
            return $result;
        }

        public function wpaicg_regenerate_title()
        {
            // Initialize the default error response.
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong', 'gpt3-ai-content-generator'));

            // Check if the current user has the required capability.
            if (!current_user_can('wpaicg_suggester')) {
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            // Verify the nonce for security.
            if (!wp_verify_nonce($_POST['nonce'], 'wpaicg-ajax-nonce')) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed', 'gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            // Check if the title is set and not empty.
            if (isset($_POST['title']) && !empty($_POST['title'])) {

                $title = sanitize_text_field($_POST['title']);

                // Get the AI engine.
                try {
                    $ai_engine = WPAICG_Util::get_instance()->initialize_ai_engine();
                } catch (\Exception $e) {
                    $wpaicg_result['msg'] = $e->getMessage();
                    wp_send_json($wpaicg_result);
                }

                $temperature = floatval( $ai_engine->temperature );
                $max_tokens = intval( $ai_engine->max_tokens );
                $top_p = floatval( $ai_engine->top_p );
                $best_of = intval( $ai_engine->best_of );
                $frequency_penalty = floatval( $ai_engine->frequency_penalty );
                $presence_penalty = floatval( $ai_engine->presence_penalty );
                $wpai_language = sanitize_text_field( $ai_engine->wpai_language );
                if ( empty($wpai_language) ) {
                    $wpai_language = "en";
                }
                $wpaicg_language_file = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/languages/' . $wpai_language . '.json';
                if ( !file_exists( $wpaicg_language_file ) ) {
                    $wpaicg_language_file = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/languages/en.json';
                }
                $wpaicg_language_json = file_get_contents( $wpaicg_language_file );
                $wpaicg_languages = json_decode( $wpaicg_language_json, true );
                $prompt = isset($wpaicg_languages['regenerate_prompt']) && !empty($wpaicg_languages['regenerate_prompt']) ? $wpaicg_languages['regenerate_prompt'] : 'Suggest me 5 different title for: %s.';
                $prompt = sprintf($prompt, $title);

                $ai_provider_info = \WPAICG\WPAICG_Util::get_instance()->get_default_ai_provider();
                $wpaicg_provider = $ai_provider_info['provider'];
                $wpaicg_ai_model = $ai_provider_info['model'];

                $legacy_models = array(
                    "text-davinci-001", "davinci", "babbage", "text-babbage-001", "curie-instruct-beta",
                    "text-davinci-003", "text-curie-001", "davinci-instruct-beta", "text-davinci-002",
                    "ada", "text-ada-001", "curie","gpt-3.5-turbo-instruct"
                );
                
                if (!in_array($wpaicg_ai_model, $legacy_models)) {
                    $prompt = $wpaicg_languages['fixed_prompt_turbo'].' '.$prompt;
                }
                
                if ($wpaicg_provider == 'Google') {
                    $title = $prompt;
                    $model = $wpaicg_ai_model;
                    $temperature = $temperature;
                    $top_p = $top_p;
                    $max_tokens = $max_tokens;

                    $complete = $ai_engine->send_google_request($title, $model, $temperature, $top_p, $max_tokens);

                    if (!empty($complete['status']) && $complete['status'] === 'error') {
                        wp_send_json(['msg' => $complete['msg'], 'status' => 'error']);
                    } else {
                        $wpaicg_result = $this->process_ai_response($complete);
                    }                    

                } else {
                    $wpaicg_generator = WPAICG_Generator::get_instance();
                    $wpaicg_generator->openai($ai_engine);

                    $complete = $wpaicg_generator->wpaicg_request( [
                        'model'             => $wpaicg_ai_model,
                        'prompt'            => $prompt,
                        'temperature'       => $temperature,
                        'max_tokens'        => $max_tokens,
                        'frequency_penalty' => $frequency_penalty,
                        'presence_penalty'  => $presence_penalty,
                        'top_p'             => $top_p,
                        'best_of'           => $best_of,
                        'stop' => '6.'
                    ] );
                    $wpaicg_result['prompt'] = $prompt;
                }
                
                if($complete['status'] == 'error'){
                    $wpaicg_result['msg'] = $complete['msg'];
                }
                else{
                    $wpaicg_result = $this->process_ai_response($complete);
                }

            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_regenerate_action($actions, $post)
        {
            if(current_user_can('wpaicg_suggester')) {
                $actions['wpaicg_regenerate'] = '<a class="wpaicg_regenerate_title" data-title="' . esc_html($post->post_title) . '" data-id="' . esc_attr($post->ID) . '" href="javascript:void(0)">' .esc_html__('Suggest Title','gpt3-ai-content-generator'). '</a>';
            }
            return $actions;
        }

        public function wpaicg_regenerate_footer()
        {
            ?>
            <script>
                jQuery(document).ready(function ($){
                    var wpaicgRegenerateRunning = false;
                    $('.wpaicg_modal_close').click(function (){
                        $('.wpaicg_modal_content').empty();
                        $('.wpaicg_modal_close').closest('.wpaicg_modal').hide();
                        $('.wpaicg_modal_close').closest('.wpaicg_modal').removeClass('wpaicg-small-modal');
                        $('.wpaicg-overlay').hide();
                        if(wpaicgRegenerateRunning){
                            wpaicgRegenerateRunning.abort();
                        }
                    })
                    function wpaicgLoading(btn){
                        btn.attr('disabled','disabled');
                        if(!btn.find('spinner').length){
                            btn.append('<span class="spinner"></span>');
                        }
                        btn.find('.spinner').css('visibility','unset');
                    }
                    function wpaicgRmLoading(btn){
                        btn.removeAttr('disabled');
                        btn.find('.spinner').remove();
                    }
                    $(document).on('click','.wpaicg_regenerate_save', function (e){
                        var btn = $(e.currentTarget);
                        var title = btn.parent().find('input').val();
                        var id = btn.attr('data-id');
                        if(title === ''){
                            alert('<?php echo esc_html__('Please insert title','gpt3-ai-content-generator')?>');
                        }
                        else{
                            wpaicgRegenerateRunning = $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php')?>',
                                data: {action: 'wpaicg_regenerate_save',title: title, id: id,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                                dataType: 'JSON',
                                type: 'POST',
                                beforeSend: function (){
                                    $('.wpaicg_regenerate_save').attr('disabled','disabled');
                                    wpaicgLoading(btn);
                                },
                                success: function(res){
                                    if(res.status === 'success'){
                                        $('#post-'+id+' .row-title').text(title);
                                        $('.wpaicg_modal_close').click();
                                    }
                                    else{
                                        wpaicgRmLoading(btn);
                                        alert(res.msg);
                                    }
                                },
                                error: function (){
                                    wpaicgRmLoading(btn);
                                    alert('Something went wrong');
                                    $('.wpaicg_regenerate_save').removeAttr('disabled');
                                }
                            })
                        }
                    })
                    $(document).on('click','.wpaicg_regenerate_title', function (e){
                        var btn = $(e.currentTarget);
                        var id = btn.attr('data-id');
                        var title = btn.attr('data-title');
                        if(title === ''){
                            alert('Please update title first');
                        }
                        else{
                            if(wpaicgRegenerateRunning){
                                wpaicgRegenerateRunning.abort();
                            }
                            $('.wpaicg_modal_content').empty();
                            $('.wpaicg-overlay').show();
                            $('.wpaicg_modal').show();
                            $('.wpaicg_modal_title').html('AI Power - <?php echo esc_html__('Title Suggestion Tool','gpt3-ai-content-generator')?>');
                            $('.wpaicg_modal_content').html('<p style="font-style: italic;margin-top: 5px;text-align: center;"><?php echo esc_html__('Preparing suggestions...','gpt3-ai-content-generator')?></p>');
                            wpaicgRegenerateRunning = $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php')?>',
                                data: {action: 'wpaicg_regenerate_title',title: title,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                                dataType: 'JSON',
                                type: 'POST',
                                success: function (res){
                                    if(res.status === 'success'){
                                        var html = '';
                                        if(res.data.length){
                                            $.each(res.data, function (idx, item){
                                                html += '<div class="wpaicg-regenerate-title"><input type="text" value="'+item+'"><button data-id="'+id+'" class="button button-primary wpaicg_regenerate_save"><?php echo esc_html__('Use','gpt3-ai-content-generator')?></button></div>';
                                            })
                                            $('.wpaicg_modal_content').html(html);
                                        }
                                        else{
                                            $('.wpaicg_modal_content').html('<p style="color: #f00;margin-top: 5px;text-align: center;"><?php echo esc_html__('No result','gpt3-ai-content-generator')?></p>');
                                        }
                                    }
                                    else{
                                        $('.wpaicg_modal_content').html('<p style="color: #f00;margin-top: 5px;text-align: center;">'+res.msg+'</p>');
                                    }
                                },
                                error: function (){
                                    $('.wpaicg_modal_content').html('<p style="color: #f00;margin-top: 5px;text-align: center;"><?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?></p>');
                                }
                            })
                        }
                    })
                })
            </script>
            <?php
        }
    }
    WPAICG_Regenerate_Title::get_instance();
}
