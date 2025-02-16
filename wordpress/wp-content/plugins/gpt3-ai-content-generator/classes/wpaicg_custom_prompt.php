<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( '\\WPAICG\\WPAICG_Custom_Prompt' ) ) {
    class WPAICG_Custom_Prompt
    {
        private static  $instance = null ;

        public $wpaicg_default_custom_prompt = 'Create a compelling and well-researched article of at least 500 words on the topic of "[title]" in English. Structure the article with clear headings enclosed within the appropriate heading tags (e.g., <h1>, <h2>, etc.) and engaging subheadings. Ensure that the content is informative and provides valuable insights to the reader. Incorporate relevant examples, case studies, and statistics to support your points. Organize your ideas using unordered lists with <ul> and <li> tags where appropriate. Conclude with a strong summary that ties together the key takeaways of the article. Remember to enclose headings in the specified heading tags to make parsing the content easier. Additionally, wrap even paragraphs in <p> tags for improved readability.';

        public $wpaicg_default_custom_image_prompt = 'Create a high-quality image that visually represents the topic of "[title]". The image should be visually appealing and relevant to the content of the article.';

        public $wpaicg_default_custom_featured_image_prompt = 'Create a high-quality featured image that visually represents the topic of "[title]". The featured image should be visually appealing and relevant to the content of the article.';

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_wpaicg_generate_custom_prompt',array($this,'wpaicg_generate_custom_prompt'));
        }

        public function wpaicg_generate_custom_prompt()
        {
            $wpaicg_result = array('status' => 'error','tokens' => 0, 'length' => 0);
            if(!current_user_can('wpaicg_single_content_express')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(
                isset($_REQUEST['wpai_preview_title'])
                && !empty($_REQUEST['wpai_preview_title'])
                && isset($_REQUEST['wpaicg_custom_prompt'])
                && !empty($_REQUEST['wpaicg_custom_prompt'])
            ) {
                $wpaicg_generator = WPAICG_Generator::get_instance();
                $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                $openai = WPAICG_OpenAI::get_instance()->openai();

                // Get the AI engine.
                try {
                    $openai = WPAICG_Util::get_instance()->initialize_ai_engine();
                } catch (\Exception $e) {
                    $wpaicg_result['msg'] = $e->getMessage();
                    wp_send_json($wpaicg_result);
                }
                if(!$openai){
                    $wpaicg_result['msg'] = esc_html__('Missing API Setting','gpt3-ai-content-generator');
                    wp_send_json($wpaicg_result);
                    exit;
                }
                $wpaicg_single = new \stdClass();
                $wpaicg_single->wpaicg_keywords = '';
                $wpaicg_single->wpaicg_words_to_avoid = '';
                if(isset($_REQUEST['wpai_keywords']) && !empty($_REQUEST['wpai_keywords'])){
                    $wpaicg_single->wpaicg_keywords = sanitize_text_field($_REQUEST['wpai_keywords']);
                }
                if(isset($_REQUEST['wpai_words_to_avoid']) && !empty($_REQUEST['wpai_words_to_avoid'])){
                    $wpaicg_single->wpaicg_words_to_avoid = sanitize_text_field($_REQUEST['wpai_words_to_avoid']);
                }
                $wpaicg_single->post_title = sanitize_text_field($_REQUEST['wpai_preview_title']);
                $wpaicg_generator->init($openai,$wpaicg_single->post_title);
                $wpaicg_custom_prompt_auto = sanitize_text_field($_REQUEST['wpaicg_custom_prompt']);
                $wpaicg_custom_prompt_auto = str_replace('[title]', $wpaicg_single->post_title, $wpaicg_custom_prompt_auto);
                // Replace [description] if user has included it
                if (! empty($wpaicg_single->post_content)) {
                    $wpaicg_custom_prompt_auto = str_replace('[description]', $wpaicg_single->post_content, $wpaicg_custom_prompt_auto);
                } else {
                    // Optionally, remove it or leave it blank if there is no content
                    $wpaicg_custom_prompt_auto = str_replace('[description]', '', $wpaicg_custom_prompt_auto);
                }
                $wpaicg_generator->wpaicg_opts['prompt'] = $wpaicg_custom_prompt_auto;
                if(wpaicg_util_core()->wpaicg_is_pro()){
                    $result = WPAICG_Custom_Prompt_Pro::get_instance()->request($wpaicg_generator);
                }
                else{
                    $result = $wpaicg_generator->wpaicg_request($wpaicg_generator->wpaicg_opts);
                }
                if($result['status'] == 'success'){
                    $wpaicg_result['status'] = 'success';
                    $generated_content = $result['data'];
                    $wpaicg_result['tokens'] = $result['tokens'];
                    $wpaicg_result['length'] = $result['length'];
                    preg_match_all('/<h\d>([^<]*)<\/h\d>/iU', $generated_content, $matches);
                    $wpaicg_toc_lists = [];
                    $first_heading_tag = $wpaicg_generator->wpaicg_heading_tag;
                    if($matches && is_array($matches) && count($matches) == 2){
                        foreach($matches[1] as $key=>$match){
                            if($key == 0){
                                $first_heading_tag = str_replace(array('<','>'),'',substr($matches[0][0],0,3));
                            }
                            $heading_id = sanitize_title($match);
                            $wpaicg_toc_lists[] = $match;
                            $generated_content = str_replace('>'.$match.'<',' id="'.$heading_id.'-wpaicgheading">'.$match.'<', $generated_content);
                        }
                    }
                    $wpaicg_result['wpaicg_heading_tag_modify'] = $first_heading_tag;
                    $wpaicg_result['tocs'] = implode('||',$wpaicg_toc_lists);
                    $wpaicg_result['headings'] = implode('||',$wpaicg_toc_lists);
                    $wpaicg_result['content'] = $generated_content;
                }
                else{
                    $wpaicg_result['msg'] = $result['msg'];
                }
            }
            wp_send_json($wpaicg_result);
        }

        public function generator()
        {
            global  $wpdb ;
            update_option( '_wpaicg_cron_added', time() );
            $sql = "SELECT * FROM " . $wpdb->posts . " WHERE post_type='wpaicg_bulk' AND post_status='pending' ORDER BY post_date ASC";
            $wpaicg_single = $wpdb->get_row( $sql );
            update_option( '_wpaicg_crojob_bulk_last_time', time() );
            /* Fix in progress task stuck*/
            $wpaicg_restart_queue = get_option('wpaicg_restart_queue','');
            $wpaicg_try_queue = get_option('wpaicg_try_queue','');
            if(!empty($wpaicg_restart_queue) && !empty($wpaicg_try_queue)) {
                $wpaicg_fix_sql = $wpdb->prepare("SELECT p.ID,(SELECT m.meta_value FROM ".$wpdb->postmeta." m WHERE m.post_id=p.ID AND m.meta_key='wpaicg_try_queue_time') as try_time FROM ".$wpdb->posts." p WHERE (p.post_status='draft' OR p.post_status='trash') AND p.post_type='wpaicg_bulk' AND p.post_modified <  NOW() - INTERVAL %d MINUTE",$wpaicg_restart_queue);
                $in_progress_posts = $wpdb->get_results($wpaicg_fix_sql);
                if($in_progress_posts && is_array($in_progress_posts) && count($in_progress_posts)){
                    foreach($in_progress_posts as $in_progress_post){
                        if(!$in_progress_post->try_time || (int)$in_progress_post->try_time < $wpaicg_try_queue){
                            wp_update_post(array(
                                'ID'          => $in_progress_post->ID,
                                'post_status' => 'pending',
                            ));
                            wp_update_post(array(
                                'ID'          => $in_progress_post->post_parent,
                                'post_status' => 'pending',
                            ));
                            $next_time = (int)$in_progress_post->try_time + 1;
                            update_post_meta($in_progress_post->ID,'wpaicg_try_queue_time',$next_time);
                        }
                    }
                }
            }
            /* END fix stuck */
            if ( $wpaicg_single ) {
                $wpaicg_generator = WPAICG_Generator::get_instance();
                $wpaicg_content_class = WPAICG_Content::get_instance();
                $wpaicg_generator_start = microtime( true );
                $wpaicg_generator_tokens = 0;
                $wpaicg_generator_text_length = 0;
                try {
                    wp_update_post( array(
                        'ID'          => $wpaicg_single->ID,
                        'post_status' => 'draft',
                        'post_modified' => gmdate('Y-m-d H:i:s')
                    ) );
                    $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                    $openai = WPAICG_OpenAI::get_instance()->openai();
                    // Get the AI engine.
                    try {
                        $openai = WPAICG_Util::get_instance()->initialize_ai_engine();
                    } catch (\Exception $e) {
                        $wpaicg_result['msg'] = $e->getMessage();
                        wp_send_json($wpaicg_result);
                    }

                    if(!$openai){
                        $wpaicg_content_class->wpaicg_bulk_error_log($wpaicg_single->ID, 'Missing API Setting');
                    }
                    else{
                        $wpaicg_custom_prompt_auto = get_option('wpaicg_custom_prompt_auto',$this->wpaicg_default_custom_prompt);
                        $wpaicg_custom_prompt_auto = str_replace('[title]', $wpaicg_single->post_title,$wpaicg_custom_prompt_auto);
                        // get description from post_content and Replace [description] similarly to [title]
                        $wpaicg_custom_prompt_auto = str_replace('[description]', $wpaicg_single->post_content, $wpaicg_custom_prompt_auto);
                        $wpaicg_generator->init($openai,$wpaicg_single->post_title,true,$wpaicg_single->ID);
                        $wpaicg_has_error = false;
                        $break_step = '';
                        $wpaicg_generator->wpaicg_opts['prompt'] = $wpaicg_custom_prompt_auto;
                        if(wpaicg_util_core()->wpaicg_is_pro()){
                            $result = WPAICG_Custom_Prompt_Pro::get_instance()->request($wpaicg_generator);
                        }
                        else{
                            $result = $wpaicg_generator->wpaicg_request($wpaicg_generator->wpaicg_opts);
                        }
                        if($result['status'] == 'success'){
                            $wpaicg_random_id = wpaicg_util_core()->wpaicg_random();
                            $generated_content = $result['data'];
                            $wpaicg_generator_tokens += $result['tokens'];
                            $wpaicg_generator_text_length += $result['length'];
                            preg_match_all('/<h\d>([^<]*)<\/h\d>/iU', $generated_content, $matches);
                            $wpaicg_toc_lists = [];
                            $first_heading_tag = $wpaicg_generator->wpaicg_heading_tag;
                            if($matches && is_array($matches) && count($matches) == 2){
                                foreach($matches[1] as $key=>$match){
                                    if($key == 0){
                                        $first_heading_tag = str_replace(array('<','>'),'',substr($matches[0][0],0,3));
                                    }
                                    $heading_id = sanitize_title($match).'-'.$wpaicg_random_id;
                                    $wpaicg_toc_lists[] = $match;
                                    $generated_content = str_replace('>'.$match.'<',' id="'.$heading_id.'">'.$match.'<', $generated_content);
                                }
                            }
                            $wpaicg_generator->wpaicg_result['content'] = $generated_content;
                            $is_pro = wpaicg_util_core()->wpaicg_is_pro(); 
                            $gen_title_from_keywords = get_option('_wpaicg_gen_title_from_keywords', false);
                            $steps = array('seo','addition','featuredimage');
                            if($is_pro && $gen_title_from_keywords){
                                $steps = array('seo','generate_title','addition','featuredimage','keywords');
                            }

                            foreach ($steps as $step){
                                $wpaicg_generator->wpaicg_generator($step);
                                if($wpaicg_generator->error_msg){
                                    $break_step = $step;
                                    $wpaicg_has_error = $wpaicg_generator->error_msg;
                                    break;
                                }
                            }
                            if($wpaicg_has_error){
                                $wpaicg_content_class->wpaicg_bulk_error_log($wpaicg_single->ID, $wpaicg_has_error.'. Break at step '.$break_step);
                                $wpaicg_running = WPAICG_PLUGIN_DIR.'/wpaicg_running.txt';
                                if(file_exists($wpaicg_running)){
                                    unlink($wpaicg_running);
                                }
                            }
                            else{
                                /*Generate Image*/
                                if($wpaicg_generator->wpaicg_image_source == 'dalle' || $wpaicg_generator->wpaicg_image_source == 'dalle2' || $wpaicg_generator->wpaicg_image_source == 'dalle3' || $wpaicg_generator->wpaicg_image_source == 'dalle3hd'){
                                    $wpaicg_generator->sleep_request();
                                    $_wpaicg_image_style = '';
                                    $_wpaicg_art_style = '';
                                    if(!empty($wpaicg_generator->wpaicg_img_style)){
                                        $_wpaicg_art_style = (isset($wpaicg_generator->wpaicg_languages['art_style']) && !empty($wpaicg_generator->wpaicg_languages['art_style']) ? ' ' . $wpaicg_generator->wpaicg_languages['art_style'] : '');
                                        $_wpaicg_image_style = (isset($wpaicg_generator->wpaicg_languages['img_styles'][$wpaicg_generator->wpaicg_img_style]) && !empty($wpaicg_generator->wpaicg_languages['img_styles'][$wpaicg_generator->wpaicg_img_style]) ? ' ' . $wpaicg_generator->wpaicg_languages['img_styles'][$wpaicg_generator->wpaicg_img_style] : '');
                                    }
                                    $prompt_image = $wpaicg_generator->wpaicg_preview_title . $_wpaicg_art_style . $_wpaicg_image_style;
                                    if($wpaicg_generator->wpaicg_custom_image_settings && is_array($wpaicg_generator->wpaicg_custom_image_settings) && count($wpaicg_generator->wpaicg_custom_image_settings)) {
                                        $prompt_elements = array(
                                            'artist' => esc_html__('Painter','gpt3-ai-content-generator'),
                                            'photography_style' => esc_html__('Photography Style','gpt3-ai-content-generator'),
                                            'composition' => esc_html__('Composition','gpt3-ai-content-generator'),
                                            'resolution' => esc_html__('Resolution','gpt3-ai-content-generator'),
                                            'color' => esc_html__('Color','gpt3-ai-content-generator'),
                                            'special_effects' => esc_html__('Special Effects','gpt3-ai-content-generator'),
                                            'lighting' => esc_html__('Lighting','gpt3-ai-content-generator'),
                                            'subject' => esc_html__('Subject','gpt3-ai-content-generator'),
                                            'camera_settings' => esc_html__('Camera Settings','gpt3-ai-content-generator'),
                                        );
                                        foreach ($wpaicg_generator->wpaicg_custom_image_settings as $key => $value) {
                                            if ($value != "None") {
                                                $prompt_image = $prompt_image . ". " . $prompt_elements[$key] . ": " . $value;
                                            }
                                        }
                                    }
                                    // Check if image source is dalle3hd and set quality parameter
                                    $extra_params_custom = [];
                                    // Check if image source is 'dalle3' or 'dalle3hd' and set model parameter
                                    if($wpaicg_generator->wpaicg_image_source === 'dalle3' || $wpaicg_generator->wpaicg_image_source === 'dalle3hd'){
                                        $extra_params_custom['model'] = 'dall-e-3';

                                        // Retrieve the style option from the WordPress options table
                                        $wpaicg_dalle_type = get_option('wpaicg_dalle_type', 'vivid');

                                        // Add the style parameter to the request
                                        $extra_params_custom['style'] = $wpaicg_dalle_type;
                                        
                                        // Check if custom image size is empty, does not exist, or is 256x256 or 512x512 and set it to 1024x1024
                                        if (empty($wpaicg_generator->wpaicg_img_size) || !isset($wpaicg_generator->wpaicg_img_size) || $wpaicg_generator->wpaicg_img_size === '256x256' || $wpaicg_generator->wpaicg_img_size === '512x512') {
                                            $wpaicg_generator->wpaicg_img_size = '1024x1024';
                                        }
                                    }
                                    if($wpaicg_generator->wpaicg_image_source === 'dalle3hd'){
                                        $extra_params_custom['quality'] = 'hd';
                                    }

                                    // check if custom image prompt is enabled
                                    $custom_image_prompt_enable = get_option('wpaicg_custom_image_prompt_enable');
                                    if ($custom_image_prompt_enable && $custom_image_prompt_enable == 1) {
                                        // check if custom image prompt exists and has a value
                                        $custom_image_prompt = get_option('wpaicg_custom_image_prompt');
                                        if (!empty($custom_image_prompt)) {
                                            // replace [title] placeholder with post title in custom prompt
                                            if (isset($wpaicg_single->post_title)) {
                                                $custom_image_prompt = str_replace('[title]', $wpaicg_single->post_title, $custom_image_prompt);
                                            }
                                            $prompt_image = $custom_image_prompt; // use the final custom prompt
                                        }
                                    }

                                    $wpaicg_request = $wpaicg_generator->wpaicg_image(array_merge([
                                        "prompt" => $prompt_image,
                                        "n" => 1,
                                        "size" => $wpaicg_generator->wpaicg_img_size,
                                        "response_format" => "url",
                                    ], $extra_params_custom));

                                    if($wpaicg_request['status'] == 'error'){
                                        $wpaicg_generator->wpaicg_result['status'] = 'no_image';
                                        $wpaicg_generator->wpaicg_result['msg'] = $wpaicg_request['msg'];
                                    }
                                    else{
                                        $wpaicg_generator->wpaicg_result['img'] = trim($wpaicg_request['url']);
                                    }
                                }
                                if($wpaicg_generator->wpaicg_image_source == 'pexels'){
                                    $wpaicg_pexels_response = $wpaicg_generator->wpaicg_pexels_generator();
                                    if(isset($wpaicg_pexels_response['pexels_response']) && !empty($wpaicg_pexels_response['pexels_response'])){
                                        $wpaicg_generator->wpaicg_result['img'] = trim($wpaicg_pexels_response['pexels_response']);
                                    }
                                }
                                if($wpaicg_generator->wpaicg_image_source == 'pixabay'){
                                    $wpaicg_pixabay_response = $wpaicg_generator->wpaicg_pixabay_generator();
                                    if(isset($wpaicg_pixabay_response['img']) && !empty($wpaicg_pixabay_response['img'])){
                                        $wpaicg_generator->wpaicg_result['img'] = trim($wpaicg_pixabay_response['img']);
                                    }
                                }
                                if($wpaicg_generator->wpaicg_image_source == 'replicate'){
                                    $wpaicg_replicate_response = $wpaicg_generator->wpaicg_replicate_image_generator('image');
                                    if($wpaicg_replicate_response['status'] == 'error'){
                                        $wpaicg_generator->wpaicg_result['status'] = 'no_image';
                                        $wpaicg_generator->wpaicg_result['msg'] = $wpaicg_replicate_response['msg'];
                                    }
                                    else{
                                        if(isset($wpaicg_replicate_response['img']) && !empty($wpaicg_replicate_response['img'])){
                                            $wpaicg_generator->wpaicg_result['img'] = trim($wpaicg_replicate_response['img']);
                                        }
                                    }
                                }
                                if(!empty($wpaicg_generator->wpaicg_result['img'])){
                                    $imgresult = "__WPAICG_IMAGE__";
                                    $wpaicg_content = explode("</" . $first_heading_tag . ">", $wpaicg_generator->wpaicg_result['content']);
                                    $wpaicg_content[1] = $imgresult.$wpaicg_content[1];
                                    $wpaicg_generator->wpaicg_result['content'] = implode("</" . $first_heading_tag . ">", $wpaicg_content);
                                }
                                /*End Generate Image*/

                                $wpaicg_generator_result = $wpaicg_generator->wpaicgResult();
                                $wpaicg_generator_text_length += $wpaicg_generator_result['length'];
                                $wpaicg_generator_tokens += $wpaicg_generator_result['tokens'];

                                $generated_title = isset($wpaicg_generator_result['title']) ? $wpaicg_generator_result['title'] : null;
                                // Remove ' and " from the beginning and end of the string
                                $cleaned_generated_title = trim($generated_title, "'\"");

                                // Get the user's choice for URL shortening
                                $should_shorten_url = get_option('_wpaicg_shorten_url', true);
                                
                                // If the user has chosen to shorten the URL
                                if ($should_shorten_url) {
                                    // Define the maximum length for the URL (70 characters)
                                    $max_url_length = 70;
                            
                                    // Get the domain name dynamically from WordPress settings
                                    $domain_name = get_site_url();  // or use home_url() depending on your needs
                            
                                    // Calculate the maximum length for the slug by considering the domain name
                                    $max_slug_length = $max_url_length - strlen($domain_name);
                            
                                    // Generate initial slug from title using WordPress function to get a URL-friendly string
                                    $slug = sanitize_title($cleaned_generated_title);
                                    
                                    // If the slug is too long, truncate it intelligently
                                    if (strlen($slug) > $max_slug_length) {
                                        $slug_words = explode("-", $slug);
                                        $new_slug_words = array();
                                        $new_slug_length = 0;
                            
                                        foreach($slug_words as $word) {
                                            if ($new_slug_length + strlen($word) + 1 <= $max_slug_length) { // +1 for the hyphen
                                                $new_slug_words[] = $word;  // Add the word to the new slug
                                                $new_slug_length += strlen($word) + 1;  // Update the new slug length
                                            } else {
                                                break;  // Stop adding more words as it would exceed the maximum length
                                            }
                                        }
                                        $slug = implode("-", $new_slug_words);  // Create the new truncated slug
                                    }
                                    // Final check to ensure the slug doesn't exceed 70 characters
                                    if (strlen($slug) > 70) {
                                        $slug = substr($slug, 0, 70);  // Trim the slug to be exactly 70 characters
                                    }
                                } else {
                                    // Generate slug without shortening
                                    $slug = sanitize_title($cleaned_generated_title);
                                }

                                // Get focus keyword option status
                                $should_include_focus_keyword = get_option('_wpaicg_focus_keyword_in_url', false);

                                if ($should_include_focus_keyword) {
                                    // Step 1: Check if _wporg_keywords is set and not empty
                                    $focus_keywords = get_post_meta($wpaicg_single->ID, '_wpaicg_keywords', true);
                                    if (!empty($focus_keywords)) {
                                        // Step 2: If the focus keywords contain a comma, get the first one
                                        if (strpos($focus_keywords, ',') !== false) {
                                            $focus_keywords_array = explode(',', $focus_keywords);
                                            $focus_keyword = trim($focus_keywords_array[0]);  // Get the first keyword
                                        } else {
                                            $focus_keyword = $focus_keywords;
                                        }

                                        // Step 3: Check if the focus keyword is already in the slug
                                        if (strpos($slug, $focus_keyword) === false) {
                                            // Step 4: Trim the slug and prepend the focus keyword
                                            $keyword_length = strlen($focus_keyword);
                                            $slug = substr($slug, 0, -1 * $keyword_length);  // Trim last n characters
                                            $slug = $focus_keyword . '-' . $slug;  // Prepend keyword and hyphen
                                        }
                                    }
                                }

                                $wpaicg_allowed_html_content_post = wp_kses_allowed_html( 'post' );
                                $wpaicg_content = wp_kses( $wpaicg_generator_result['content'], $wpaicg_allowed_html_content_post );
                                $wpaicg_post_status = ( $wpaicg_single->post_password == 'draft' ? 'draft' : 'publish' );
                                $wpaicg_image_attachment_id = false;

                                $alt_text = !empty($cleaned_generated_title) ? $cleaned_generated_title : $wpaicg_single->post_title;

                                if(isset($wpaicg_generator_result['img']) && !empty($wpaicg_generator_result['img'])){
                                    $wpaicg_image_url = sanitize_url($wpaicg_generator_result['img']);
                                    $wpaicg_image_attachment_id = $wpaicg_content_class->wpaicg_save_image($wpaicg_image_url,$wpaicg_single->post_title,false);
                                    if($wpaicg_image_attachment_id['status'] == 'success'){
                                        $wpaicg_image_attachment_url = wp_get_attachment_url($wpaicg_image_attachment_id['id']);
                                        $wpaicg_content = str_replace("__WPAICG_IMAGE__", '<img src="'.$wpaicg_image_attachment_url.'" alt="'.$alt_text.'" />', $wpaicg_content);
                                    }
                                }
                                // Fix empty image
                                $wpaicg_content = str_replace("__WPAICG_IMAGE__", '', $wpaicg_content);
                                $wpaicg_content = str_replace("wpaicgheading", $wpaicg_random_id, $wpaicg_content);
                                /*Add TOC*/
                                if($wpaicg_generator->wpaicg_toc && count($wpaicg_toc_lists)){
                                    $wpaicg_table_content = '<ul class="toc_post_list"><li>';
                                    if($wpaicg_generator->wpaicg_toc_title !== ''){
                                        $wpaicg_table_content .= '<'.$wpaicg_generator->wpaicg_toc_title_tag.'>'.$wpaicg_generator->wpaicg_toc_title.'</'.$wpaicg_generator->wpaicg_toc_title_tag.'>';
                                    }
                                    $wpaicg_table_content .= '<ul>';
                                    foreach($wpaicg_toc_lists as $wpaicg_toc_item){
                                        $wpaicg_toc_item_id = sanitize_title($wpaicg_toc_item).'-'.$wpaicg_random_id;
                                        $wpaicg_table_content .= '<li><a href="#'.$wpaicg_toc_item_id.'">'.$wpaicg_toc_item.'</a></li>';
                                    }
                                    $wpaicg_table_content .= '</ul>';
                                    $wpaicg_table_content .= '</li></ul>';
                                    $wpaicg_content = $wpaicg_table_content.$wpaicg_content;
                                }

                                $wpaicg_post_data = array(
                                    'post_title'   => !empty($cleaned_generated_title) ? $cleaned_generated_title : $wpaicg_single->post_title,
                                    'post_author'  => $wpaicg_single->post_author,
                                    'post_content' => $wpaicg_content,
                                    'post_status'  => $wpaicg_post_status,
                                    'post_name'    => $slug,
                                );
                                if($wpaicg_single->menu_order && $wpaicg_single->menu_order > 0){
                                    $wpaicg_post_data['post_category'] = array($wpaicg_single->menu_order);
                                }

                                if ( !empty($wpaicg_single->post_excerpt) ) {
                                    $wpaicg_post_data['post_status'] = 'future';
                                    $wpaicg_post_data['post_date'] = $wpaicg_single->post_excerpt;
                                    $wpaicg_post_data['post_date_gmt'] = $wpaicg_single->post_excerpt;
                                }

                                $wpaicg_post_id = wp_insert_post( $wpaicg_post_data );

                                if ( is_wp_error( $wpaicg_post_id ) ) {
                                    update_post_meta( $wpaicg_single->ID, '_wpaicg_error', $wpaicg_post_id->get_error_message() );
                                    wp_update_post( array(
                                        'ID'          => $wpaicg_single->ID,
                                        'post_status' => 'trash',
                                    ) );
                                } else {

                                    $ai_provider_info = \WPAICG\WPAICG_Util::get_instance()->get_default_ai_provider();
                                    $wpaicg_provider = $ai_provider_info['provider'];
                                    $wpaicg_ai_model = $ai_provider_info['model'];
                                    
                                    add_post_meta($wpaicg_post_id,'wpaicg_ai_model',$wpaicg_ai_model);
                                    add_post_meta($wpaicg_single->ID,'wpaicg_ai_model',$wpaicg_ai_model);

                                    // Retrieve the focus keywords for this post
                                    $keywords = get_post_meta($wpaicg_single->ID, '_wpaicg_keywords', true);

                                    if (!empty($keywords)) {

                                        // Sanitize and update the _wporg_keywords meta field
                                        $keywords_sanitized = sanitize_text_field($keywords);
                                        update_post_meta($wpaicg_post_id, '_wporg_keywords', $keywords_sanitized);

                                        // Update Rank Math focus keyword
                                        update_post_meta($wpaicg_post_id, 'rank_math_focus_keyword', $keywords_sanitized);

                                        // Extract the first keyword for Yoast
                                        $keyword_array = explode(',', $keywords_sanitized);
                                        $first_keyword = trim($keyword_array[0]);

                                        if (!empty($first_keyword)) {
                                            // Update Yoast focus keyword
                                            update_post_meta($wpaicg_post_id, '_yoast_wpseo_focuskw', $first_keyword);

                                        // Check if 'All In One SEO Pack' or 'All In One SEO Pack Pro' is active
                                        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                                            $wpaicg_content_class->wpaicg_save_aioseo_focus_keyword($wpaicg_post_id, $first_keyword);
                                        }

                                        }
                                    }

                                    if(isset($wpaicg_generator_result['description']) && !empty($wpaicg_generator_result['description'])){
                                        $wpaicg_content_class->wpaicg_save_description($wpaicg_post_id,sanitize_text_field($wpaicg_generator_result['description']));
                                    }

                                    if(isset($wpaicg_generator_result['featured_img']) && !empty($wpaicg_generator_result['featured_img'])){
                                        $wpaicg_featured_image_url = sanitize_url($wpaicg_generator_result['featured_img']);
                                        $wpaicg_image_attachment_id = $wpaicg_content_class->wpaicg_save_image($wpaicg_featured_image_url,$wpaicg_single->post_title,true);
                                        if($wpaicg_image_attachment_id['status'] == 'success'){
                                            update_post_meta( $wpaicg_post_id, '_thumbnail_id', $wpaicg_image_attachment_id['id']);
                                        }
                                    }

                                    $wpaicg_tags = get_post_meta($wpaicg_single->ID, '_wpaicg_tags',true);
                                    if(!empty($wpaicg_tags)){
                                        $wpaicg_tags = array_map('trim', explode(',', $wpaicg_tags));
                                        if($wpaicg_tags && is_array($wpaicg_tags) && count($wpaicg_tags)){
                                            wp_set_post_tags($wpaicg_post_id,$wpaicg_tags);
                                        }
                                    }
                                    update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_post', $wpaicg_post_id );
                                    wp_update_post( array(
                                        'ID'          => $wpaicg_single->ID,
                                        'post_status' => 'publish',
                                    ));
                                    /*Save Last Content*/
                                    if($wpaicg_single->post_mime_type == 'sheets'){
                                        update_option('wpaicg_cronjob_sheets_content',time());
                                    }
                                    elseif($wpaicg_single->post_mime_type == 'rss'){
                                        update_option('wpaicg_cronjob_rss_content',time());
                                    }
                                    else{
                                        update_option('wpaicg_cronjob_bulk_content',time());
                                    }
                                }

                            }
                        }
                        else{
                            $wpaicg_content_class->wpaicg_bulk_error_log($wpaicg_single->ID, $result['msg']);
                            $wpaicg_running = WPAICG_PLUGIN_DIR.'/wpaicg_running.txt';
                            if(file_exists($wpaicg_running)){
                                unlink($wpaicg_running);
                            }
                        }
                    }
                } catch ( \Exception $exception ) {
                }
                $wpaicg_bulks = get_posts( array(
                    'post_type'      => 'wpaicg_bulk',
                    'post_status'    => array(
                        'publish',
                        'pending',
                        'draft',
                        'trash',
                        'inherit'
                    ),
                    'post_parent'    => $wpaicg_single->post_parent,
                    'posts_per_page' => -1,
                ) );
                $wpaicg_bulk_completed = true;
                $wpaicg_bulk_error = false;
                foreach ( $wpaicg_bulks as $wpaicg_bulk ) {
                    if ( $wpaicg_bulk->post_status == 'pending' || $wpaicg_bulk->post_status == 'draft' ) {
                        $wpaicg_bulk_completed = false;
                    }

                    if ( $wpaicg_bulk->post_status == 'trash' ) {
                        $wpaicg_bulk_error = true;
                        $wpaicg_bulk_completed = false;
                    }

                }
                if ( $wpaicg_bulk_completed ) {
                    wp_update_post( array(
                        'ID'          => $wpaicg_single->post_parent,
                        'post_status' => 'publish',
                    ) );
                }
                if ( $wpaicg_bulk_error ) {
                    wp_update_post( array(
                        'ID'          => $wpaicg_single->post_parent,
                        'post_status' => 'draft',
                    ) );
                }
                $wpaicg_generator_end = microtime( true ) - $wpaicg_generator_start;
                update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_run', $wpaicg_generator_end );
                update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_length', $wpaicg_generator_text_length );
                update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_token', $wpaicg_generator_tokens );
            }

        }
    }
    WPAICG_Custom_Prompt::get_instance();
}
