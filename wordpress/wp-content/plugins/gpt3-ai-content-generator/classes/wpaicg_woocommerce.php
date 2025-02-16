<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( '\\WPAICG\\WPAICG_WooCommerce' ) ) {
    class WPAICG_WooCommerce
    {
        private static  $instance = null ;

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('add_meta_boxes_product', array($this,'wpaicg_register_meta_box'));
            add_action('wp_ajax_wpaicg_product_generator',array($this,'wpaicg_product_generator'));
            add_action('wp_ajax_wpaicg_product_save',array($this,'wpaicg_product_save'));
            add_action('manage_posts_extra_tablenav',[$this,'wpaicg_woocommerce_content_button']);
            add_action('admin_footer',[$this,'wpaicg_woocommerce_content_footer']);
            add_action('wp_ajax_wpaicg_woo_content_generator',[$this,'wpaicg_woo_content_generator']);
        }

        // Function to get SEO keywords based on the active plugin for a WooCommerce product
        public function get_seo_keywords_for_product($product_id) {
            $keywords_array = [];

            // Check for Yoast SEO
            if (is_plugin_active('wordpress-seo/wp-seo.php')) {
                $yoast_keyword = get_post_meta($product_id, '_yoast_wpseo_focuskw', true);
                if (!empty($yoast_keyword)) {
                    $keywords_array[] = $yoast_keyword;
                }
            }

            // Check for Rank Math
            if (is_plugin_active('seo-by-rank-math/rank-math.php')) {
                $rank_math_keyword = get_post_meta($product_id, 'rank_math_focus_keyword', true);
                if (!empty($rank_math_keyword)) {
                    $keywords_array[] = $rank_math_keyword;
                }
            }

            // Check for All In One SEO Pack
            if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || 
                is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                $aioseo_keyword = get_post_meta($product_id, '_aioseo_keywords', true); // Ensure correct meta key for AIOSEO
                if (!empty($aioseo_keyword)) {
                    $keywords_array[] = $aioseo_keyword;
                }
            }

            // Combine keywords into a comma-separated string
            $seo_keywords = implode(', ', array_filter($keywords_array));
            return !empty($seo_keywords) ? $seo_keywords : 'N/A';
        }

        public function wpaicg_woo_content_generator()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'error','msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));
            if(!current_user_can('wpaicg_woocommerce_content')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-action' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            if(
                isset($_REQUEST['title'])
                && !empty($_REQUEST['title'])
                && isset($_REQUEST['id'])
                && !empty($_REQUEST['id'])
                && isset($_REQUEST['step'])
                && !empty($_REQUEST['step'])
            ) {
                if ($_REQUEST['step'] != 'shorten_url' && $_REQUEST['step'] != 'enforce_focus_keyword_in_url') {
                    // Get the AI engine.
                    try {
                        $open_ai = WPAICG_Util::get_instance()->initialize_ai_engine();
                    } catch (\Exception $e) {
                        $wpaicg_result['msg'] = $e->getMessage();
                        wp_send_json($wpaicg_result);
                        exit;
                    }
                    $temperature = floatval($open_ai->temperature);
                    $max_tokens = intval($open_ai->max_tokens);
                    $top_p = floatval($open_ai->top_p);
                    $best_of = intval($open_ai->best_of);
                    $frequency_penalty = floatval($open_ai->frequency_penalty);
                    $presence_penalty = floatval($open_ai->presence_penalty);
                    $wpai_language = sanitize_text_field($open_ai->wpai_language);
                    $wpaicg_language_file = plugin_dir_path(dirname(__FILE__)) . 'admin/languages/' . $wpai_language . '.json';
                    if (!file_exists($wpaicg_language_file)) {
                        $wpaicg_language_file = plugin_dir_path(dirname(__FILE__)) . 'admin/languages/en.json';
                    }
                    $wpaicg_language_json = file_get_contents($wpaicg_language_file);
                    $wpaicg_languages = json_decode($wpaicg_language_json, true);
                    $wpaicg_woo_generate_title = isset($_REQUEST['wpaicg_woo_generate_title']) && !empty($_REQUEST['wpaicg_woo_generate_title']) ? true : false;
                    $wpaicg_woo_meta_description = isset($_REQUEST['wpaicg_woo_meta_description']) && !empty($_REQUEST['wpaicg_woo_meta_description']) ? true : false;
                    $wpaicg_woo_generate_description = isset($_REQUEST['wpaicg_woo_generate_description']) && !empty($_REQUEST['wpaicg_woo_generate_description']) ? true : false;
                    $wpaicg_woo_generate_short = isset($_REQUEST['wpaicg_woo_generate_short']) && !empty($_REQUEST['wpaicg_woo_generate_short']) ? true : false;
                    $wpaicg_woo_generate_tags = isset($_REQUEST['wpaicg_woo_generate_tags']) && !empty($_REQUEST['wpaicg_woo_generate_tags']) ? true : false;
                    $wpaicg_woo_custom_prompt = isset($_REQUEST['wpaicg_woo_custom_prompt']) && !empty($_REQUEST['wpaicg_woo_custom_prompt']) ? true : false;
                    $wpaicg_woo_custom_prompt_title = isset($_REQUEST['wpaicg_woo_custom_prompt_title']) && !empty($_REQUEST['wpaicg_woo_custom_prompt_title']) ? sanitize_text_field($_REQUEST['wpaicg_woo_custom_prompt_title']) : get_option('wpaicg_woo_custom_prompt_title',esc_html__('Compose an SEO-optimized title in English for the following product: %s. Ensure it is engaging, concise, and includes relevant keywords to maximize its visibility on search engines.','gpt3-ai-content-generator'));
                    $wpaicg_woo_custom_prompt_short = isset($_REQUEST['wpaicg_woo_custom_prompt_short']) && !empty($_REQUEST['wpaicg_woo_custom_prompt_short']) ? sanitize_text_field($_REQUEST['wpaicg_woo_custom_prompt_short']) : get_option('wpaicg_woo_custom_prompt_short',esc_html__('Provide a compelling and concise summary in English for the following product: %s, highlighting its key features, benefits, and unique selling points.','gpt3-ai-content-generator'));
                    $wpaicg_woo_custom_prompt_description = isset($_REQUEST['wpaicg_woo_custom_prompt_description']) && !empty($_REQUEST['wpaicg_woo_custom_prompt_description']) ? sanitize_text_field($_REQUEST['wpaicg_woo_custom_prompt_description']) : get_option('wpaicg_woo_custom_prompt_description',esc_html__('Craft a comprehensive and engaging product description in English for: %s. Include specific details, features, and benefits, as well as the value it offers to the customer, thereby creating a compelling narrative around the product.','gpt3-ai-content-generator'));
                    $wpaicg_woo_custom_prompt_meta = isset($_REQUEST['wpaicg_woo_custom_prompt_meta']) && !empty($_REQUEST['wpaicg_woo_custom_prompt_meta']) ? sanitize_text_field($_REQUEST['wpaicg_woo_custom_prompt_meta']) : get_option('wpaicg_woo_custom_prompt_meta',esc_html__('Craft a compelling and concise meta description in English for: %s. Aim to highlight its key features and benefits within a limit of 155 characters, while incorporating relevant keywords for SEO effectiveness.','gpt3-ai-content-generator'));
                    $wpaicg_woo_custom_prompt_keywords = isset($_REQUEST['wpaicg_woo_custom_prompt_keywords']) && !empty($_REQUEST['wpaicg_woo_custom_prompt_keywords']) ? sanitize_text_field($_REQUEST['wpaicg_woo_custom_prompt_keywords']) : get_option('wpaicg_woo_custom_prompt_keywords',esc_html__('Propose a set of relevant keywords in English for the following product: %s. The keywords should be directly related to the product, enhancing its discoverability. Please present these keywords in a comma-separated format, avoiding the use of symbols such as -, #, etc.','gpt3-ai-content-generator'));
                    
                    $wpaicg_generate_woo_focus_keyword = isset($_REQUEST['wpaicg_generate_woo_focus_keyword']) && !empty($_REQUEST['wpaicg_generate_woo_focus_keyword']) ? true : false;
                    $wpaicg_woo_custom_prompt_focus_keyword = isset($_REQUEST['wpaicg_woo_custom_prompt_focus_keyword']) && !empty($_REQUEST['wpaicg_woo_custom_prompt_focus_keyword']) ? sanitize_text_field($_REQUEST['wpaicg_woo_custom_prompt_focus_keyword']) : get_option('wpaicg_woo_custom_prompt_focus_keyword', esc_html__('Identify the primary keyword for the following product: %s. Please respond in English. No additional comments, just the keyword.', 'gpt3-ai-content-generator'));

                    if(!$wpaicg_woo_custom_prompt){
                        $wpaicg_woo_custom_prompt_title = $wpaicg_languages['woo_product_title'];
                        $wpaicg_woo_custom_prompt_short = $wpaicg_languages['woo_product_short'];
                        $wpaicg_woo_custom_prompt_description = $wpaicg_languages['woo_product_description'];
                        $wpaicg_woo_custom_prompt_meta = $wpaicg_languages['meta_desc_prompt'];
                        $wpaicg_woo_custom_prompt_keywords = $wpaicg_languages['woo_product_tags'];
                        // Check if the key exists in the JSON file before assigning it
                        if (array_key_exists('woo_focus_keyword', $wpaicg_languages)) {
                            $wpaicg_woo_custom_prompt_focus_keyword = $wpaicg_languages['woo_focus_keyword'];
                        }
                    }

                    // Get sleep time, default to 1 seconds if not set
                    $sleepTime = get_option('wpaicg_sleep_time', 1);

                    // Apply the sleep
                    sleep($sleepTime);

                    $title = sanitize_text_field($_REQUEST['title']);
                    $id = sanitize_text_field($_REQUEST['id']);
                    $step = sanitize_text_field($_REQUEST['step']);

                    // Fetch the WooCommerce product by ID
                    $product = wc_get_product($id);

                    $product_seo_keywords = $this->get_seo_keywords_for_product($id);

                    // Get the product categories
                    $terms = get_the_terms($id, 'product_cat');

                    $categories = [];

                    if ($terms && !is_wp_error($terms)) {
                        $categories = wp_list_pluck($terms, 'name');
                    }

                    // Convert the categories array to a comma-separated string
                    $current_categories = implode(', ', $categories);

                    $weight_unit = get_option('woocommerce_weight_unit'); // Returns 'kg', 'g', 'lbs', etc.
                    $dimension_unit = get_option('woocommerce_dimension_unit'); // Returns 'm', 'cm', 'mm', 'in', etc.

                    $currency_symbol = get_woocommerce_currency_symbol(); // Returns '$', '€', '£', etc.

                    // Get the additional product properties
                    $current_price = $product ? html_entity_decode($currency_symbol) . $product->get_price() : 'N/A';
                    $current_weight = $product ? $product->get_weight() . ' ' . $weight_unit : 'N/A';
                    $current_length = $product ? $product->get_length() . ' ' . $dimension_unit : 'N/A';
                    $current_width = $product ? $product->get_width() . ' ' . $dimension_unit : 'N/A';
                    $current_height = $product ? $product->get_height() . ' ' . $dimension_unit : 'N/A';
                    
                    $current_sku = $product ? $product->get_sku() : 'N/A';
                    $current_purchase_note = $product ? $product->get_purchase_note() : 'N/A';

                    // Get the short description and full description
                    $short_description = $product ? $product->get_short_description() : '';
                    $full_description = $product ? $product->get_description() : '';

                    // Get the product attributes
                    $attributes_array = $product ? $product->get_attributes() : [];
                    $attributes_list = [];

                    if ($attributes_array) {
                        foreach ($attributes_array as $attribute) {
                            // Check if the attribute is taxonomy-based
                            if ($attribute->is_taxonomy()) {
                                $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                                $attributes_list[] = implode(', ', $terms);
                            } else {
                                // For custom product attributes, you can get values by this method
                                $attributes_list[] = implode(', ', $attribute->get_options());
                            }
                        }
                    }
                    
                    // Convert the attributes array to a comma-separated string
                    $current_attributes = implode(', ', $attributes_list);

                    function escape_percentage($string) {
                        return str_replace('%', '%%', $string);
                    }
                    
                    // Variables that might contain '%' and need to be escaped
                    $title = escape_percentage($title);
                    $short_description = escape_percentage($short_description);
                    $full_description = escape_percentage($full_description);
                    $current_attributes = escape_percentage($current_attributes);
                    $current_categories = escape_percentage($current_categories);
                    $current_price = escape_percentage($current_price);
                    $current_weight = escape_percentage($current_weight);
                    $current_length = escape_percentage($current_length);
                    $current_width = escape_percentage($current_width);
                    $current_height = escape_percentage($current_height);
                    $current_sku = escape_percentage($current_sku);
                    $current_purchase_note = escape_percentage($current_purchase_note);
                    $current_seo_keywords = escape_percentage($product_seo_keywords);
                    
                    // Common replacements for all steps
                    $common_replacements = [
                        '[current_short_description]' => $short_description,
                        '[current_full_description]' => $full_description,
                        '[current_attributes]' => $current_attributes,
                        '[current_categories]' => $current_categories,
                        '[current_price]' => $current_price,
                        '[current_weight]' => $current_weight,
                        '[current_length]' => $current_length,
                        '[current_width]' => $current_width,
                        '[current_height]' => $current_height,
                        '[current_sku]' => $current_sku,
                        '[current_purchase_note]' => $current_purchase_note,
                        '[current_focus_keywords]' => $current_seo_keywords,
                    ];

                    function escape_percentage_except_placeholders($prompt) {
                        // Temporarily replace %s with a placeholder unlikely to be used
                        $temp_placeholder = '{{PRODUCT_TITLE_PLACEHOLDER}}';
                        $prompt = str_replace('%s', $temp_placeholder, $prompt);
                        
                        // Escape all remaining % symbols
                        $prompt = str_replace('%', '%%', $prompt);
                        
                        // Revert our temporary placeholder back to %s
                        $prompt = str_replace($temp_placeholder, '%s', $prompt);
                        
                        return $prompt;
                    }
                    
                    // Apply this function to your custom prompts
                    $wpaicg_woo_custom_prompt_title = escape_percentage_except_placeholders($wpaicg_woo_custom_prompt_title);
                    $wpaicg_woo_custom_prompt_short = escape_percentage_except_placeholders($wpaicg_woo_custom_prompt_short);
                    $wpaicg_woo_custom_prompt_description = escape_percentage_except_placeholders($wpaicg_woo_custom_prompt_description);
                    $wpaicg_woo_custom_prompt_meta = escape_percentage_except_placeholders($wpaicg_woo_custom_prompt_meta);
                    $wpaicg_woo_custom_prompt_keywords = escape_percentage_except_placeholders($wpaicg_woo_custom_prompt_keywords);
                    $wpaicg_woo_custom_prompt_focus_keyword = escape_percentage_except_placeholders($wpaicg_woo_custom_prompt_focus_keyword);
                    
                    // Mapping step to corresponding prompt
                    $step_mapping = [
                        'title' => $wpaicg_woo_custom_prompt_title,
                        'meta' => $wpaicg_woo_custom_prompt_meta,
                        'description' => $wpaicg_woo_custom_prompt_description,
                        'short' => $wpaicg_woo_custom_prompt_short,
                        'tags' => $wpaicg_woo_custom_prompt_keywords,
                        'focus_keyword' => $wpaicg_woo_custom_prompt_focus_keyword
                    ];

                    function replace_placeholders($prompt, $replacements) {
                        foreach ($replacements as $placeholder => $value) {
                            $prompt = str_replace($placeholder, $value, $prompt);
                        }
                        return $prompt;
                    }
                    
                    if (array_key_exists($step, $step_mapping)) {
                        $prompt = sprintf($step_mapping[$step], $title);
                        $prompt = replace_placeholders($prompt, $common_replacements);
                        $prompt = escape_percentage($prompt);
                        $prompt = sprintf($prompt, $title);
                    }

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
    
                        $complete = $open_ai->send_google_request($title, $model, $temperature, $top_p, $max_tokens);
                        // remove /n at the end of the response
                        $complete['data'] = rtrim($complete['data']);
                        // remove <br> at the end of the response
                        $complete['data'] = preg_replace('/(<br\s*\/?>)+$/i', '', $complete['data']);
    
                        if (!empty($complete['status']) && $complete['status'] === 'error') {
                            wp_send_json(['msg' => $complete['msg'], 'status' => 'error']);
                        } else {
                            $wpaicg_result = $complete;
                        }    
    
                    } else {

                        $opts = array(
                            'model' => $wpaicg_ai_model,
                            'prompt' => $prompt,
                            'temperature' => $temperature,
                            'max_tokens' => $max_tokens,
                            'frequency_penalty' => $frequency_penalty,
                            'presence_penalty' => $presence_penalty,
                            'top_p' => $top_p,
                            'best_of' => $best_of,
                        );

                        $wpaicg_result['prompt'] = $prompt;

                        $wpaicg_generator = WPAICG_Generator::get_instance();
                        $wpaicg_generator->openai($open_ai);

                        $complete = $wpaicg_generator->wpaicg_request($opts);
                    }

                    if($complete['status'] == 'error'){
                        $wpaicg_result['msg'] = $complete['msg'];
                    }
                    else{
                        $result = $complete['data'];

                        $wpaicg_result['data'] = trim($result);
                        $wpaicg_result['status'] = 'success';

                        if($step === 'tags'){
                            $tags = preg_split( "/\r\n|\n|\r/", $result );
                            $tags = preg_replace( '/^\\d+\\.\\s/', '', $tags );
                            if(is_array($tags)){
                                $tags = $tags[0];
                                $tags = array_map('trim', explode(',', $tags));
                                $wpaicg_result['data'] = array();
                                if($tags && is_array($tags) && count($tags)){
                                    $post_tags = wp_get_post_terms($id,'product_tag');
                                    if($post_tags && is_array($post_tags) && count($post_tags)){
                                        $terms_id = wp_list_pluck($post_tags,'term_id');
                                        wp_remove_object_terms($id, $terms_id,'product_tag');
                                    }
                                    $terms_id = array();
                                    foreach($tags as $tag){
                                        $product_tag = term_exists($tag,'product_tag');
                                        if(!$product_tag){
                                            $product_tag = wp_insert_term($tag,'product_tag');
                                            // Check if wp_insert_term returned an error.
                                            if (is_wp_error($product_tag)) {
                                                error_log('Error inserting term: ' . $product_tag->get_error_message());
                                                continue; // Skip this iteration if there was an error.
                                            }
                                        }
                                        $term = get_term($product_tag['term_id'],'product_tag');
                                        if (is_wp_error($term)) {
                                            error_log('Error getting term: ' . $term->get_error_message());
                                            continue; // Skip this iteration if there was an error.
                                        }
                                        $wpaicg_result['data'][$term->slug] = $tag;
                                        $terms_id[] = (int)$term->term_id;
                                    }
                                    wp_add_object_terms($id, $terms_id,'product_tag');
                                }
                            }
                        }
                        elseif($step == 'title'){
                            // Trim the result
                            $result = trim($result);

                            // Remove specified characters from the beginning and end of the string
                            $result = preg_replace("/^[ .,;#'\"-]+|[ .,;#'\"-]+$/", '', $result);

                            wp_update_post(array(
                                'ID' => $id,
                                'post_title' => $result
                            ));
                        }
                        elseif($step == 'meta'){
                            $seo_option = get_option('_yoast_wpseo_metadesc',false);
                            $seo_plugin_activated = wpaicg_util_core()->seo_plugin_activated();
                            if($seo_plugin_activated == '_yoast_wpseo_metadesc' && $seo_option){
                                update_post_meta($id,$seo_plugin_activated,$result);
                            }
                            $seo_option = get_option('_aioseo_description',false);
                            if($seo_plugin_activated == '_aioseo_description' && $seo_option){
                                update_post_meta($id,$seo_plugin_activated,$result);
                                $check = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."aioseo_posts WHERE post_id=%d",$id));
                                if($check){
                                    $wpdb->update(
                                        $wpdb->prefix . 'aioseo_posts',
                                        ['description' => $result],
                                        ['post_id' => $id]
                                    );
                                }
                                else{
                                    $wpdb->insert(
                                        $wpdb->prefix . 'aioseo_posts',
                                        [
                                            'post_id' => $id,
                                            'description' => $result,
                                            'created' => gmdate('Y-m-d H:i:s'),
                                            'updated' => gmdate('Y-m-d H:i:s')
                                        ]
                                    );
                                }
                                
                            }
                            $seo_option = get_option('rank_math_description',false);
                            if($seo_plugin_activated == 'rank_math_description' && $seo_option){
                                update_post_meta($id,$seo_plugin_activated,$result);
                            }
                            // The SEO Framework
                            $seo_option = get_option('_wpaicg_genesis_description', false);
                            if ($seo_plugin_activated == '_genesis_description' && $seo_option) {
                                update_post_meta($id, '_genesis_description', $result);
                            }
                            update_post_meta($id,'_wpaicg_meta_description', $result);
                        }
                        elseif($step == 'description'){
                            wp_update_post(array(
                                'ID' => $id,
                                'post_content' => trim($result)
                            ));
                        }
                        elseif($step == 'short'){
                            wp_update_post(array(
                                'ID' => $id,
                                'post_excerpt' => trim($result)
                            ));
                        } 
  
                        elseif ($step == 'focus_keyword') {
                            if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                                // Trim the keyword
                                $trimmed_keyword = trim($result);
                        
                                // Remove special characters from the beginning and end of the keyword
                                $cleaned_keyword = preg_replace('/^[\#\-\.\'"]+|[\#\-\.\'"]+$/', '', $trimmed_keyword);
                                
                                $this->update_focus_keywords_based_on_result($id, $cleaned_keyword);
                            }
                        }         
                    }
                
                } elseif ($_REQUEST['step'] === 'shorten_url') {
                    // Call the function to shorten the URL
                    $slug_result = $this->shorten_product_url(sanitize_text_field($_REQUEST['id']));
                
                    // Check the status returned by the function
                    if ($slug_result['status'] == 3) { // 3 means "URL shortened successfully."
                        wp_update_post(array(
                            'ID' => sanitize_text_field($_REQUEST['id']),
                            'post_name' => $slug_result['new_slug']  // Set the new slug
                        ));
                        $wpaicg_result['status'] = 'success';
                        $wpaicg_result['msg'] = $slug_result['msg'];
                        $wpaicg_result['new_slug'] = $slug_result['new_slug'];
                    } elseif ($slug_result['status'] == 1) { // 1 means "URL is already short."
                        $wpaicg_result['status'] = 'info'; // Or maybe some other status to indicate it was already short
                        $wpaicg_result['msg'] = $slug_result['msg'];
                    } elseif ($slug_result['status'] == 2) { // 2 means "Slug is empty."
                        $wpaicg_result['status'] = 'info';
                        $wpaicg_result['msg'] = $slug_result['msg'];
                    } else {
                        // Handle other cases like when "Shorten Product URL setting is disabled."
                        $wpaicg_result['status'] = 'error';
                        $wpaicg_result['msg'] = esc_html__('Failed to shorten URL', 'gpt3-ai-content-generator');
                    }
                }
                elseif ($_REQUEST['step'] === 'enforce_focus_keyword_in_url') {
                    $slug_result = $this->enforce_keyword_in_slug(sanitize_text_field($_REQUEST['id']));
                    
                    // Check the status returned by the function
                    switch ($slug_result['status']) {
                        case 3:  // Slug updated with focus keyword successfully.
                            wp_update_post(array(
                                'ID' => sanitize_text_field($_REQUEST['id']),
                                'post_name' => $slug_result['new_slug']  // Set the new slug
                            ));
                            $wpaicg_result['status'] = 'success';
                            $wpaicg_result['msg'] = $slug_result['msg'];
                            $wpaicg_result['new_slug'] = $slug_result['new_slug'];
                            break;
                        
                        case 1:  // Focus keyword is already present in the URL.
                        case 10:  // Focus keyword not found for the product.
                            $wpaicg_result['status'] = 'info';
                            $wpaicg_result['msg'] = $slug_result['msg'];
                            break;
                        
                        default:  // Handle other cases, like when the conditions for enforcing the keyword are not met.
                            $wpaicg_result['status'] = 'error';
                            $wpaicg_result['msg'] = esc_html__('Failed to enforce focus keyword in URL', 'gpt3-ai-content-generator');
                            break;
                    }
                }
                                    
            }
            else{
                $wpaicg_result['msg'] = esc_html__('Missing request parameters','gpt3-ai-content-generator');
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_woocommerce_content_footer()
        {
            ?>
            <div class="wpaicg-woo-content-default" style="display: none">
                <?php
                include WPAICG_PLUGIN_DIR.'admin/views/settings/woocommerce.php';
                ?>
            </div>
            <script>
                jQuery(document).ready(function ($){
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
                    var ids = [];
                    var titles = {};
                    var wpaicgWooContentAjax = false;
                    var wpaicgWooContentWorking = true;
                    var wpaicgWooContentSuccess = 0;
                    var wooGenerateContent = $('.wpaicg-woocommerce-content-btn');
                    var wpaicgSteps = [];
                    var hasGenerateTitle = false;
                    var hasGenerateTags = false;
                    var hasShortenURL = false;
                    var hasEnforceFocusinURL = false;
                    var wpaicgFirstStep = '';
                    var wpaicgLastStep = '';
                    wooGenerateContent.click(function (){
                        if(!wpaicgWooContentAjax){
                            ids = [];
                            titles = {};
                            let form = $(this).closest('#posts-filter');
                            form.find('.wp-list-table th.check-column input[type=checkbox]:checked').each(function (idx, item){
                                let post_id = $(item).val();
                                ids.push(post_id);
                                let row = form.find('#post-'+post_id);
                                let post_name = row.find('.column-title .row-title').text();
                                if(post_name === ''){
                                    post_name = row.find('.column-name .row-title').text();
                                }
                                titles[post_id] = post_name.trim();
                                titles[post_id] = post_name.trim().replace(/(^['"])|(['"]$)/g, '');
                            });
                            if(ids.length === 0){
                                alert('<?php echo esc_html__('Please select a product to generate.','gpt3-ai-content-generator')?>');
                            }
                            else {
                                wpaicgWooContentWorking = true;
                                // Query to get the language from the wp_wpaicg table using PHP
                                <?php 
                                global $wpdb;
                                $wpaicg_woo_custom_prompt = get_option('wpaicg_woo_custom_prompt', false);
                                $language_upper = '';
                                if (!$wpaicg_woo_custom_prompt) {
                                    $language = $wpdb->get_var("SELECT wpai_language FROM " . $wpdb->prefix . "wpaicg LIMIT 1");
                                    $language_upper = strtoupper($language);
                                }
                                $ai_provider_info = \WPAICG\WPAICG_Util::get_instance()->get_default_ai_provider();
                                $wpaicg_provider = $ai_provider_info['provider'];
                                $ai_model = $ai_provider_info['model'];
                                ?>
                                // Include the language in the modal title if custom prompt is not enabled
                                var wpaicg_modal_title_base = '<?php echo esc_html__("WooCommerce Content Generator", "gpt3-ai-content-generator")?>';
                                if ('<?php echo $language_upper; ?>' !== '') {
                                    wpaicg_modal_title_base += ' - <?php echo esc_html($language_upper); ?>';
                                }
                                $('.wpaicg_modal_title').html(wpaicg_modal_title_base + '<span style="font-size: 16px;background: #2271B1;padding: 1px 5px;border-radius: 3px;display: inline-block;margin-left: 6px;color: #fff;" class="wpaicg-woocontent-remain">0/' + ids.length + '</span>');
                                $('.wpaicg_modal').css({
                                    top: '5%',
                                    height: '90%'
                                });
                                $('.wpaicg_modal_content').css({
                                    'max-height': 'calc(100% - 103px)',
                                    'overflow-y': 'auto'
                                });

                                var woo_content_message = '<?php echo esc_html__('This will generate content for [numbers] products. Do you want to continue?','gpt3-ai-content-generator')?>';
                                var wpaicg_provider = '<?php echo $wpaicg_provider; ?>'; // Fetch AI engine
                                var ai_model = '<?php echo $ai_model; ?>'; // Fetch AI model
                                var sleep_time = '<?php echo get_option('wpaicg_sleep_time', 1); ?>'; // Fetch sleep time
                                var settings_message = '<?php echo esc_html__('You are using %1$s with %2$s and your rate limit buffer is %3$s seconds. You can change them from Settings > AI Engine tab.', 'gpt3-ai-content-generator'); ?>';
                                settings_message = settings_message.replace('%1$s', '<strong>' + wpaicg_provider + '</strong>');
                                settings_message = settings_message.replace('%2$s', '<strong>' + ai_model + '</strong>');
                                settings_message = settings_message.replace('%3$s', '<strong>' + sleep_time + '</strong>');

                                var html = '<form action="" method="post" id="wpaicg-woo-content-form">';
                                html += '<input type="hidden" name="action" value="wpaicg_woo_content_generator">';
                                html += '<input type="hidden" name="nonce" value="<?php echo wp_create_nonce('wpaicg-ajax-action')?>">';
                                html += '<p>' + settings_message + '</p>';
                                html += $('.wpaicg-woo-content-default').html();
                                html += '<p><?php echo esc_html__('To modify your default settings, please visit Settings > WooCommerce.','gpt3-ai-content-generator')?></p>'
                                html += '<p>'+woo_content_message.replace('[numbers]',ids.length)+'</p>';
                                html += '<button class="button button-primary wpaicg_woo_content_btn"><?php echo esc_html__('Start','gpt3-ai-content-generator')?></button>';
                                html += '&nbsp;<button type="button" class="button wpaicg_woo_content_cancel" style="display: none"><?php echo esc_html__('Cancel','gpt3-ai-content-generator')?></button>';
                                html += '<div class="wpaicg-woo-content-modal-content" style="padding:10px 0px"></div>';
                                html += '</form>';
                                $('.wpaicg_modal_content').html(html);
                                $('#wpaicg-woo-content-form h3').hide();
                                $('#wpaicg-woo-content-form .wpaicg_woo_token_sale').hide();
                                $('.wpaicg-overlay').show();
                                $('.wpaicg_modal').show();
                            }
                        }
                        else{
                            alert('<?php echo esc_html__('Please wait until the previous task is finished.','gpt3-ai-content-generator')?>');
                        }
                    });
                    $(document).on('submit','#wpaicg-woo-content-form',function(e){
                        e.preventDefault();
                        wpaicgSteps = [];
                        var form = $(e.currentTarget);
                        if(form.find('input[name=wpaicg_woo_generate_title]:checked').length){
                            wpaicgSteps.push('title');
                            hasGenerateTitle = true;
                        }
                        else{
                            hasGenerateTitle = false;
                        }
                        if(form.find('input[name=wpaicg_woo_meta_description]:checked').length){
                            wpaicgSteps.push('meta');
                        }
                        if(form.find('input[name=wpaicg_woo_generate_description]:checked').length){
                            wpaicgSteps.push('description');
                        }
                        if(form.find('input[name=wpaicg_woo_generate_short]:checked').length){
                            wpaicgSteps.push('short');
                        }
                        if(form.find('input[name=wpaicg_woo_generate_tags]:checked').length){
                            wpaicgSteps.push('tags');
                            hasGenerateTags = true;
                        }
                        if(form.find('input[name=_wpaicg_shorten_woo_url]:checked').length){
                            wpaicgSteps.push('shorten_url');
                            hasShortenURL = true;
                        }
                        if(form.find('input[name=wpaicg_generate_woo_focus_keyword]:checked').length){
                            wpaicgSteps.push('focus_keyword');
                        }
                        if(form.find('input[name=wpaicg_enforce_woo_keyword_in_url]:checked').length){
                            wpaicgSteps.push('enforce_focus_keyword_in_url');
                            hasEnforceFocusinURL = true;
                        }
                        else{
                            hasGenerateTags = false;
                            hasShortenURL = false;
                            hasEnforceFocusinURL = false;
                        }
                        if(ids.length === 0){
                            alert('<?php echo esc_html__('Please select a product to generate.','gpt3-ai-content-generator')?>');
                        }
                        else if(wpaicgSteps.length === 0){
                            alert('<?php echo esc_html__('Please make at least one selection.','gpt3-ai-content-generator')?>');
                        }
                        else{
                            $('.wpaicg-woo-content-modal-content').empty();
                            wpaicgFirstStep = wpaicgSteps[0];
                            wpaicgLastStep = wpaicgSteps[wpaicgSteps.length-1];
                            $('.wpaicg_modal_close').hide();
                            var btn = $('.wpaicg_woo_content_btn');
                            wpaicgLoading(btn);
                            $('.wpaicg_woo_content_cancel').show();
                            wpaicgWooContentSuccess = 0;
                            wpaicgWooContentGenerator(0,0,ids);
                        }
                    });
                    $(document).on('click','.wpaicg_woo_content_cancel',function (){
                        var btn = $('.wpaicg_woo_content_btn');
                        wpaicgRmLoading(btn);
                        $('.wpaicg_woo_content_cancel').hide();
                        if(wpaicgWooContentAjax){
                            wpaicgWooContentAjax.abort();
                            wpaicgWooContentAjax = false;
                        }
                    });
                    function wpaicgWooContentGenerator(start,step,ids){
                        var btn = $('.wpaicg_woo_content_btn');
                        var contentEl = $('.wpaicg-woo-content-modal-content');
                        var data = $('#wpaicg-woo-content-form').serialize();
                        var currentStep = wpaicgSteps[step];
                        var currentStepText = wpaicgSteps[step];
                        var nextID = start;
                        var id = ids[start];
                        if(start + 1 > ids.length){
                            $('.wpaicg_modal_close').show();
                            wpaicgWooContentAjax = false;
                            wpaicgRmLoading(btn);
                            $('.wpaicg_woo_content_cancel').hide();
                        }
                        else {
                            data += '&id='+id;
                            data += '&title='+titles[id];
                            data += '&step='+currentStep;
                            if(currentStepText === 'short'){
                                currentStepText = 'short description';
                            }
                            if(currentStepText === 'description'){
                                currentStepText = 'full description';
                            }
                            if(currentStepText === 'shorten_url'){
                                currentStepText = 'shorten URL';
                            }
                            if(currentStepText === 'focus_keyword'){
                                currentStepText = 'focus keyword';
                            }
                            wpaicgWooContentAjax = $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php')?>',
                                data: data,
                                type: 'POST',
                                beforeSend: function () {
                                    if(!$('#wpaicg-product-generate-'+id).length){
                                        contentEl.append('<div class="wpaicg-product-generate-pending" id="wpaicg-product-generate-'+id+'" style="background: #ebebeb;border-radius: 3px;padding: 5px;margin-bottom: 5px;border: 1px solid #dfdfdf;"><div style="display: flex; justify-content: space-between;"><span>'+titles[id]+'</span><span style="font-style: italic" class="wpaicg-product-generate-status"><?php echo esc_html__('Generating...','gpt3-ai-content-generator')?></span></div></div>');
                                    }
                                },
                                dataType: 'JSON',
                                success: function (res) {
                                    var product = $('#wpaicg-product-generate-'+id);
                                    if (res.status === 'success' || res.status === 'info') {
                                        var row = $('#post-'+id);
                                        
                                        // Display the returned message if status is 'info', otherwise display 'OK'
                                        var displayMessage = (res.status === 'info') ? res.msg : '<?php echo esc_html__("OK","gpt3-ai-content-generator")?>';
                                        
                                        product.append('<div style="color: #0f8f00;font-size: 12px;">[' + currentStepText + ']&nbsp;' + displayMessage + '</div>');
        
                                        if(currentStep === 'title'){
                                            row.find('.column-name a.row-title').html(
                                                res.data.replace(/(^[\#\-\.\'"]+)|([\#\-\.\'"]+$)/g, '')
                                            );
                                        }

                                        if(currentStep === 'tags'){
                                            row.find('.column-product_tag').empty();
                                            if(typeof res.data !== "undefined"){
                                                var key = 0;
                                                $.each(res.data,function(slug, tag){
                                                    var html = '';
                                                    if(key > 0){
                                                        html += ', ';
                                                    }
                                                    html += '<a href="<?php echo admin_url('edit.php?product_tag=')?>'+slug+'&post_type=product">'+tag+'</a>';
                                                    row.find('.column-product_tag').append(html);
                                                    key += 1;
                                                })
                                            }
                                        }

                                        if(currentStep === wpaicgLastStep) {
                                            wpaicgWooContentSuccess += 1;
                                            $('.wpaicg-woocontent-remain').html(wpaicgWooContentSuccess + '/' + ids.length);
                                            product.css({
                                                'background-color': '#cde5dd'
                                            });
                                            product.removeClass('wpaicg-product-generate-pending');
                                            product.find('.wpaicg-product-generate-status').html('<?php echo esc_html__('Done', 'gpt3-ai-content-generator')?>');
                                            product.find('.wpaicg-product-generate-status').css({
                                                'font-style': 'normal',
                                                'font-weight': 'bold',
                                                'color': '#008917'
                                            });
                                        }
                                    }
                                    else{
                                        product.css({
                                            'background-color': '#e5cdcd'
                                        });
                                        product.find('.wpaicg-product-generate-status').html('<?php echo esc_html__('Error','gpt3-ai-content-generator')?>');
                                        product.find('.wpaicg-product-generate-status').css({
                                            'font-style': 'normal',
                                            'font-weight': 'bold',
                                            'color': '#e30000'
                                        })
                                        product.append('<div style="color: #e30000;font-size: 12px;">['+currentStepText+']&nbsp;' + res.msg + '</div>');
                                    }
                                    if(currentStep === wpaicgLastStep){
                                        nextID = start+1;
                                        step = 0;
                                    }
                                    else{
                                        step = step+1;
                                    }
                                    wpaicgWooContentGenerator(nextID,step,ids);
                                },
                                error: function (request, status, error) {
                                    $('.wpaicg_modal_close').show();
                                }
                            });
                        }
                    }
                })
            </script>
            <?php
        }

        public function wpaicg_woocommerce_content_button()
        {
            global $post_type;
            if($post_type == 'product' && current_user_can('wpaicg_woocommerce_content')){
                ?>
                <div class="alignleft actions">
                    <a style="height: 32px" href="javascript:void(0)" class="button button-primary wpaicg-woocommerce-content-btn"><?php echo esc_html__('Generate Content','gpt3-ai-content-generator')?></a>
                </div>
                <?php
            }
        }

        public function wpaicg_register_meta_box()
        {
            if(current_user_can('wpaicg_woocommerce_product_writer')) {
                add_meta_box('wpaicg-woocommerce-generator', esc_html__('AI Power Product Writer','gpt3-ai-content-generator'), [$this, 'wpaicg_meta_box']);
            }
        }

        public function wpaicg_meta_box($post)
        {
                include WPAICG_PLUGIN_DIR . 'admin/views/woocommerce/wpaicg-meta-box.php';
        }

        public function wpaicg_product_save()
        {
            global $wpdb;
            $wpaicg_result = array('status' => 'error','msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));
            if(!current_user_can('wpaicg_woocommerce_product_writer')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(
                isset($_REQUEST['id'])
                && !empty($_REQUEST['id'])
                && isset($_REQUEST['mode'])
                && !empty($_REQUEST['mode'])
            ){
                $wpaicgMode = sanitize_text_field($_REQUEST['mode']);
                $wpaicgProductID = sanitize_text_field($_REQUEST['id']);
                if($wpaicgMode == 'new'){
                    $wpaicgProductData = array(
                        'post_title' => '',
                        'post_type' => 'product'
                    );
                    if(isset($_REQUEST['wpaicg_product_title']) && !empty($_REQUEST['wpaicg_product_title'])){
                        $wpaicgProductData['post_title'] = sanitize_text_field($_REQUEST['wpaicg_product_title']);
                    }
                    elseif(isset($_REQUEST['wpaicg_original_title']) && !empty($_REQUEST['wpaicg_original_title'])){
                        $wpaicgProductData['post_title'] = sanitize_text_field($_REQUEST['wpaicg_original_title']);
                    }
                    $wpaicgProductID = wp_insert_post($wpaicgProductData);
                }
                $wpaicgData = array('ID' => $wpaicgProductID);
                if(isset($_REQUEST['wpaicg_product_title']) && !empty($_REQUEST['wpaicg_product_title'])){
                    $wpaicgData['post_title'] = sanitize_text_field($_REQUEST['wpaicg_product_title']);
                    update_post_meta($wpaicgProductID,'wpaicg_product_title', sanitize_text_field($_REQUEST['wpaicg_product_title']));
                }
                if(isset($_REQUEST['wpaicg_product_short']) && !empty($_REQUEST['wpaicg_product_short'])){
                    $wpaicgData['post_excerpt'] = sanitize_text_field($_REQUEST['wpaicg_product_short']);
                    update_post_meta($wpaicgProductID,'wpaicg_product_short', sanitize_text_field($_REQUEST['wpaicg_product_short']));
                }

                // meta 
                if (isset($_REQUEST['wpaicg_product_meta']) && !empty($_REQUEST['wpaicg_product_meta'])) {
                    $seo_description = sanitize_text_field($_REQUEST['wpaicg_product_meta']);
                    $seo_plugin_activated = wpaicg_util_core()->seo_plugin_activated();
                
                    $seo_plugins = array(
                        '_yoast_wpseo_metadesc' => function($wpaicgProductID, $seo_description) {
                            update_post_meta($wpaicgProductID, '_yoast_wpseo_metadesc', $seo_description);
                        },
                        '_aioseo_description' => function($wpaicgProductID, $seo_description) use ($wpdb) {
                            update_post_meta($wpaicgProductID, '_aioseo_description', $seo_description);
                            $check = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "aioseo_posts WHERE post_id=%d", $wpaicgProductID));
                            if ($check) {
                                $wpdb->update(
                                    $wpdb->prefix . 'aioseo_posts',
                                    ['description' => $seo_description],
                                    ['post_id' => $wpaicgProductID]
                                );
                            } else {
                                $wpdb->insert(
                                    $wpdb->prefix . 'aioseo_posts',
                                    [
                                        'post_id' => $wpaicgProductID,
                                        'description' => $seo_description,
                                        'created' => gmdate('Y-m-d H:i:s'),
                                        'updated' => gmdate('Y-m-d H:i:s')
                                    ]
                                );
                            }
                        },
                        'rank_math_description' => function($wpaicgProductID, $seo_description) {
                            update_post_meta($wpaicgProductID, 'rank_math_description', $seo_description);
                        },
                        '_genesis_description' => function($wpaicgProductID, $seo_description) {
                            update_post_meta($wpaicgProductID, '_genesis_description', $seo_description);
                        }
                    );
                
                    if ($seo_plugin_activated && array_key_exists($seo_plugin_activated, $seo_plugins)) {
                        $seo_option = get_option($seo_plugin_activated, false);
                        if ($seo_option) {
                            $seo_plugins[$seo_plugin_activated]($wpaicgProductID, $seo_description);
                        }
                        // Additional check for The SEO Framework option
                        if ($seo_plugin_activated == '_genesis_description') {
                            $seo_option = get_option('_wpaicg_genesis_description', false);
                            if ($seo_option) {
                                $seo_plugins[$seo_plugin_activated]($wpaicgProductID, $seo_description);
                            }
                        }
                    }
                
                    update_post_meta($wpaicgProductID, '_wpaicg_meta_description', $seo_description);
                }
                
                if(isset($_REQUEST['wpaicg_product_description']) && !empty($_REQUEST['wpaicg_product_description'])){
                    $wpaicgData['post_content'] = wp_kses_post($_REQUEST['wpaicg_product_description']);
                    update_post_meta($wpaicgProductID,'wpaicg_product_description', wp_kses_post($_REQUEST['wpaicg_product_description']));
                }
                if(isset($_REQUEST['wpaicg_product_tags']) && !empty($_REQUEST['wpaicg_product_tags'])){
                    $wpaicgTags = sanitize_text_field($_REQUEST['wpaicg_product_tags']);
                    $wpaicgTags = array_map('trim', explode(',', $wpaicgTags));
                    wp_set_object_terms($wpaicgProductID, $wpaicgTags,'product_tag');
                    update_post_meta($wpaicgProductID,'wpaicg_product_tags', sanitize_text_field($_REQUEST['wpaicg_product_tags']));
                }
                // if wpaicg_product_focus_keyword then update focus keyword
                if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                    if (isset($_REQUEST['wpaicg_product_focus_keyword']) && !empty($_REQUEST['wpaicg_product_focus_keyword'])) {

                        $cleaned_keyword = sanitize_text_field($_REQUEST['wpaicg_product_focus_keyword']);
        
                        // Remove special characters from the beginning and end of the keyword
                        $cleaned_keyword = preg_replace('/^[\#\-\.\'"]+|[\#\-\.\'"]+$/', '', $cleaned_keyword);
                        
                        $this->update_focus_keywords_based_on_result($wpaicgProductID, $cleaned_keyword);
                    }
                }                
                $meta_keys = [
                    'wpaicg_generate_title',
                    'wpaicg_generate_description',
                    'wpaicg_generate_short',
                    'wpaicg_generate_tags',
                    'wpaicg_generate_meta',
                    'wpaicg_generate_shorten_url',
                    'wpaicg_generate_focus_keyword'
                ];
                
                foreach ($meta_keys as $key) {
                    if (isset($_REQUEST[$key]) && $_REQUEST[$key]) {
                        update_post_meta($wpaicgProductID, $key, 1);
                    } else {
                        delete_post_meta($wpaicgProductID, $key);
                    }
                }
                
                $slug_updated = false;

                // Check if _wpaicg_shorten_woo_url option is set and true
                if(get_option('_wpaicg_shorten_woo_url')){
                    $slug_result = $this->shorten_product_url($wpaicgProductID);
                    if ($slug_result['status'] == 3) {
                        $wpaicgData['post_name'] = $slug_result['new_slug'];
                        $slug_updated = true;
                    }
                }
                
                // Check if the option to enforce the focus keyword in the URL is set and true
                $enforce_keyword_option = get_option('wpaicg_enforce_woo_keyword_in_url', false);
                if($enforce_keyword_option){
                    $keyword_slug_result = $this->enforce_keyword_in_slug($wpaicgProductID);
                    if ($keyword_slug_result['status'] == 3) {
                        $wpaicgData['post_name'] = $keyword_slug_result['new_slug'];
                        $slug_updated = true;
                    }
                }
                
                // Update the post only if the slug was updated
                if ($slug_updated) {
                    wp_update_post($wpaicgData);
                }
                
                wp_update_post($wpaicgData);
                $wpaicg_result['status'] = 'success';
                $wpaicg_result['url'] = admin_url('post.php?post='.$wpaicgProductID.'&action=edit');
            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_product_generator()
        {
            global $wpdb;

            // Get the AI engine.
            try {
                $open_ai = WPAICG_Util::get_instance()->initialize_ai_engine();
            } catch (\Exception $e) {
                $wpaicg_result['msg'] = $e->getMessage();
                wp_send_json($wpaicg_result);
            }

            $wpaicg_result = array('status' => 'error','msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'),'data' => '');
            if(!current_user_can('wpaicg_woocommerce_product_writer')){
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }

            ini_set( 'max_execution_time', 1000 );
            $temperature = floatval( $open_ai->temperature );
            $max_tokens = intval( $open_ai->max_tokens );
            $top_p = floatval( $open_ai->top_p );
            $best_of = intval( $open_ai->best_of );
            $frequency_penalty = floatval( $open_ai->frequency_penalty );
            $presence_penalty = floatval( $open_ai->presence_penalty );
            $wpai_language = sanitize_text_field( $open_ai->wpai_language );
            $wpaicg_language_file = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/languages/' . $wpai_language . '.json';
            if ( !file_exists( $wpaicg_language_file ) ) {
                $wpaicg_language_file = plugin_dir_path( dirname( __FILE__ ) ) . 'admin/languages/en.json';
            }
            $wpaicg_language_json = file_get_contents( $wpaicg_language_file );
            $wpaicg_languages = json_decode( $wpaicg_language_json, true );
            if(isset($_REQUEST['step']) && !empty($_REQUEST['step']) && isset($_REQUEST['title']) && !empty($_REQUEST['title'])) {
                $wpaicg_step = sanitize_text_field($_REQUEST['step']);
                $wpaicg_title = sanitize_text_field($_REQUEST['title']);

                // Fetch the WooCommerce product by ID if available from product_id
                $product_id = isset($_REQUEST['product_id']) && !empty($_REQUEST['product_id']) ? intval($_REQUEST['product_id']) : 0;
                $product = wc_get_product($product_id);  // Get the product object

                $currency_symbol = get_woocommerce_currency_symbol(); // Returns '$', '€', '£', etc.
                $weight_unit = get_option('woocommerce_weight_unit'); // Returns 'kg', 'g', 'lbs', etc.
                $dimension_unit = get_option('woocommerce_dimension_unit'); // Returns 'm', 'cm', 'mm', 'in', etc.

                // Get the additional product properties
                $current_price = $product ? html_entity_decode($currency_symbol) . $product->get_price() : 'N/A';
                $current_weight = $product ? $product->get_weight() . ' ' . $weight_unit : 'N/A';
                $current_length = $product ? $product->get_length() . ' ' . $dimension_unit : 'N/A';
                $current_width = $product ? $product->get_width() . ' ' . $dimension_unit : 'N/A';
                $current_height = $product ? $product->get_height() . ' ' . $dimension_unit : 'N/A';
                
                $current_sku = $product ? $product->get_sku() : 'N/A';
                $current_purchase_note = $product ? $product->get_purchase_note() : 'N/A';

                // Get the short description and full description
                $short_description = $product ? $product->get_short_description() : '';
                $full_description = $product ? $product->get_description() : '';

                $product_seo_keywords = $this->get_seo_keywords_for_product($product_id);

                // Get the product categories
                $terms = get_the_terms($product_id, 'product_cat');
                $categories = [];

                if ($terms && !is_wp_error($terms)) {
                    $categories = wp_list_pluck($terms, 'name');
                }

                // Convert the categories array to a comma-separated string
                $current_categories = implode(', ', $categories);

                // Get the product attributes
                $attributes_array = $product ? $product->get_attributes() : [];
                $attributes_list = [];
                
                if ($attributes_array) {
                    foreach ($attributes_array as $attribute_name => $attribute) {
                        if ($attribute->is_taxonomy()) {
                            $terms = wp_get_post_terms($product->get_id(), $attribute->get_name(), ['fields' => 'names']);
                            $attributes_list[$attribute_name] = implode(', ', $terms);
                        } else {
                            $attributes_list[$attribute_name] = implode(', ', $attribute->get_options());
                        }
                    }
                }

                $current_attributes = '';
                foreach ($attributes_list as $key => $value) {
                    $current_attributes .= "$key: $value, ";
                }
                $current_attributes = rtrim($current_attributes, ', ');

                if($wpaicg_step == 'meta'){
                    $wpaicg_language_key = 'meta_desc_prompt';
                }
                elseif ($wpaicg_step == 'focus_keyword') {
                    $wpaicg_language_key = 'woo_focus_keyword';
                } 
                else{
                    $wpaicg_language_key = isset($wpaicg_languages['woo_product_'.$wpaicg_step]) ? 'woo_product_'.$wpaicg_step : 'woo_product_title';
                }
                /*Custom Prompt*/
                $wpaicg_woo_custom_prompt = get_option('wpaicg_woo_custom_prompt',false);
                if($wpaicg_woo_custom_prompt) {
                    if($wpaicg_step == 'title'){
                        $wpaicg_languages[$wpaicg_language_key] = get_option('wpaicg_woo_custom_prompt_title', esc_html__('Compose an SEO-optimized title in English for the following product: %s. Ensure it is engaging, concise, and includes relevant keywords to maximize its visibility on search engines.','gpt3-ai-content-generator'));
                    }
                    if($wpaicg_step == 'meta'){
                        $wpaicg_languages[$wpaicg_language_key] = get_option('wpaicg_woo_custom_prompt_meta', esc_html__('Craft a compelling and concise meta description in English for: %s. Aim to highlight its key features and benefits within a limit of 155 characters, while incorporating relevant keywords for SEO effectiveness.','gpt3-ai-content-generator'));
                    }
                    if($wpaicg_step == 'short'){
                        $wpaicg_languages[$wpaicg_language_key] = get_option('wpaicg_woo_custom_prompt_short', esc_html__('Provide a compelling and concise summary in English for the following product: %s, highlighting its key features, benefits, and unique selling points.','gpt3-ai-content-generator'));
                    }
                    if($wpaicg_step == 'description'){
                        $wpaicg_languages[$wpaicg_language_key] = get_option('wpaicg_woo_custom_prompt_description', esc_html__('Craft a comprehensive and engaging product description in English for: %s. Include specific details, features, and benefits, as well as the value it offers to the customer, thereby creating a compelling narrative around the product.','gpt3-ai-content-generator'));
                    }
                    if($wpaicg_step == 'tags'){
                        $wpaicg_languages[$wpaicg_language_key] = get_option('wpaicg_woo_custom_prompt_keywords', esc_html__('Propose a set of relevant keywords in English for the following product: %s. The keywords should be directly related to the product, enhancing its discoverability. Please present these keywords in a comma-separated format, avoiding the use of symbols such as -, #, etc.','gpt3-ai-content-generator'));
                    }
                    if ($wpaicg_step == 'focus_keyword') {
                        $wpaicg_languages[$wpaicg_language_key] = get_option('wpaicg_woo_custom_prompt_focus_keyword', esc_html__('Generate a focus keyword in English for the following product: %s.', 'gpt3-ai-content-generator'));
                    }
                }
                /*End Custom Prompt*/
                $myprompt = isset($wpaicg_languages[$wpaicg_language_key]) && !empty($wpaicg_languages[$wpaicg_language_key]) ? sprintf($wpaicg_languages[$wpaicg_language_key], $wpaicg_title) : $wpaicg_title;
                
                $myprompt = str_replace('[current_short_description]', $short_description, $myprompt);
                $myprompt = str_replace('[current_full_description]', $full_description, $myprompt);
                $myprompt = str_replace('[current_attributes]', $current_attributes, $myprompt);
                $myprompt = str_replace('[current_categories]', $current_categories, $myprompt);
                $myprompt = str_replace('[current_price]', $current_price, $myprompt);

                $myprompt = str_replace('[current_weight]', $current_weight, $myprompt);
                $myprompt = str_replace('[current_length]', $current_length, $myprompt);
                $myprompt = str_replace('[current_width]', $current_width, $myprompt);
                $myprompt = str_replace('[current_height]', $current_height, $myprompt);
                $myprompt = str_replace('[current_sku]', $current_sku, $myprompt);
                $myprompt = str_replace('[current_purchase_note]', $current_purchase_note, $myprompt);
                $myprompt = str_replace('[current_focus_keywords]', $product_seo_keywords, $myprompt);
                
                $wpaicg_result['prompt'] = $myprompt;

                $ai_provider_info = \WPAICG\WPAICG_Util::get_instance()->get_default_ai_provider();
                $wpaicg_provider = $ai_provider_info['provider'];
                $wpaicg_ai_model = $ai_provider_info['model'];

                $legacy_models = array(
                    'text-davinci-001', 'davinci', 'babbage', 'text-babbage-001', 'curie-instruct-beta',
                    'text-davinci-003', 'text-curie-001', 'davinci-instruct-beta', 'text-davinci-002',
                    'ada', 'text-ada-001', 'curie'
                );
                
                if(!in_array($wpaicg_ai_model, $legacy_models)){
                    $myprompt = $wpaicg_languages['fixed_prompt_turbo'].' '.$myprompt;
                }
                
                // Get sleep time, default to 1 seconds if not set
                $sleepTime = get_option('wpaicg_sleep_time', 1);

                // Apply the sleep
                sleep($sleepTime);

                if ($wpaicg_provider == 'Google') {

                    $complete = $open_ai->send_google_request($myprompt, $wpaicg_ai_model, $temperature, $top_p, $max_tokens);

                    // remove /n at the end of the response
                    $complete['data'] = rtrim($complete['data']);
                    // remove <br> at the end of the response
                    $complete['data'] = preg_replace('/(<br\s*\/?>)+$/i', '', $complete['data']);

                    if (!empty($complete['status']) && $complete['status'] === 'error') {
                        wp_send_json(['msg' => $complete['msg'], 'status' => 'error']);
                    } else {
                        $wpaicg_result = $complete;
                    }    

                } else {

                    $wpaicg_generator = WPAICG_Generator::get_instance();
                    $wpaicg_generator->openai($open_ai);

                    $complete = $wpaicg_generator->wpaicg_request([
                        'model' => $wpaicg_ai_model,
                        'prompt' => $myprompt,
                        'temperature' => $temperature,
                        'max_tokens' => $max_tokens,
                        'frequency_penalty' => $frequency_penalty,
                        'presence_penalty' => $presence_penalty,
                        'top_p' => $top_p,
                        'best_of' => $best_of,
                    ]);
                }
                if($complete['status'] == 'error'){
                    $wpaicg_result['msg'] = $complete['msg'];
                }
                else{
                    $wpaicg_result['status'] = 'success';
                    $complete = $complete['data'];
                    if($wpaicg_step == 'tags'){
                        $wpaicgTags = preg_split( "/\r\n|\n|\r/", $complete );
                        $wpaicgTags = preg_replace( '/^\\d+\\.\\s/', '', $wpaicgTags );
                        foreach($wpaicgTags as $wpaicgTag){
                            if(!empty($wpaicgTag)){
                                // Remove special characters from the beginning and end of the tag
                                $wpaicgTag = preg_replace('/^[#\'".,;\\s]+|[#\'".,;\\s]+$/', '', $wpaicgTag);
                                $wpaicg_result['data'] .= (empty($wpaicg_result['data']) ? '' : ', ') . trim($wpaicgTag);
                            }
                        }
                    }                    
                    else{
                        $wpaicg_result['data'] = trim($complete);
                        // Remove specified characters from the beginning and end of the string
                        if ($wpaicg_step == 'title' || $wpaicg_step == 'focus_keyword') {
                            $wpaicg_result['data'] = preg_replace("/^[ .,;#'\"-]+|[ .,;#'\"-]+$/", '', $wpaicg_result['data']);
                        }
                        if(empty($wpaicg_result['data'])){
                            $wpaicg_result['data'] = esc_html__('There was no response for this product from OpenAI. Please try again','gpt3-ai-content-generator');
                        }
                    }
                }
            }
            wp_send_json($wpaicg_result);
        }
        

        public function shorten_product_url($wpaicgProductID) {
    
            // Initialize result array
            $result = array(
                'status' => 0,
                'msg' => '',
                'new_slug' => null
            );
        
            update_option('_wpaicg_shorten_woo_url', true);

            // Fetch the "Shorten Product URL" setting from the WordPress options
            $_wpaicg_shorten_woo_url = get_option('_wpaicg_shorten_woo_url', false);
        
            // Check if "Shorten Product URL" is enabled and user is on the pro plan
            if ($_wpaicg_shorten_woo_url && \WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                // Get the existing post slug
                $current_post = get_post($wpaicgProductID);
                $current_slug = $current_post->post_name;
        
                if (empty($current_slug)) {
                    $result['status'] = 2;
                    $result['msg'] = esc_html__('URL is empty. Skipped.','gpt3-ai-content-generator');
                    return $result;
                }
        
                // Define the maximum URL length
                $max_url_length = 70;
        
                // Fetch WooCommerce permalink settings
                $woo_permalinks = get_option('woocommerce_permalinks');
                $product_base = isset($woo_permalinks['product_base']) ? $woo_permalinks['product_base'] : 'product';
        
                // Construct the root WooCommerce product URL
                $site_url = get_site_url();
                $product_base_url = trailingslashit($site_url) . $product_base . '/';
        
                // Calculate the maximum length for the slug by considering the product base URL
                $max_slug_length = $max_url_length - strlen($product_base_url);
        
                // Common stop words
                $stop_words = array('of', 'the', 'a', 'an', 'in', 'on', 'at', 'by', 'for', 'with', 'to', 'from', 'as', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'be', 'been');
        
                // Remove stop words from the slug
                $slug_parts = explode('-', $current_slug);
                $filtered_slug_parts = array_diff($slug_parts, $stop_words);
                $filtered_slug = implode('-', $filtered_slug_parts);
        
                // Check if the slug length exceeds the calculated maximum length
                if (strlen($filtered_slug) > $max_slug_length) {
                    // Shorten the slug to the maximum length while ensuring words are not cut off
                    $new_slug_parts = explode('-', $filtered_slug);
                    $new_slug = '';
                    $current_length = 0;
        
                    foreach ($new_slug_parts as $part) {
                        if ($current_length + strlen($part) + 1 <= $max_slug_length) {
                            $new_slug .= $part . '-';
                            $current_length += strlen($part) + 1;
                        } else {
                            break;
                        }
                    }
        
                    // Remove the trailing hyphen
                    $new_slug = rtrim($new_slug, '-');
        
                    $result['status'] = 3;
                    $result['msg'] = esc_html__('URL shortened successfully.','gpt3-ai-content-generator');
                    $result['new_slug'] = $new_slug;
        
                    // Update the slug
                    $wpaicgData['post_name'] = $new_slug;
                } 
                else {
                    $result['status'] = 1;
                    $result['msg'] = esc_html__('URL is already short enough. Skipped.','gpt3-ai-content-generator');
                }
            }
            return $result;
        }
        
        public function update_focus_keywords_based_on_result($product_id, $generated_keywords) {
            // Trim and sanitize the generated keywords
            $generated_keywords = sanitize_text_field(trim($generated_keywords));
            
            // Remove special characters from the beginning and end of the keyword
            $generated_keywords = preg_replace('/^[\#\-\.\'"]+|[\#\-\.\'"]+$/', '', $generated_keywords);
    
            // Update Rank Math focus keyword
            update_post_meta($product_id, 'rank_math_focus_keyword', $generated_keywords);
            
            // Extract the first keyword for Yoast
            $keywords_array = explode(', ', $generated_keywords);
            $first_keyword = !empty($keywords_array) ? $keywords_array[0] : '';
            
            if (!empty($first_keyword)) {
                // Update Yoast focus keyword
                update_post_meta($product_id, '_yoast_wpseo_focuskw', $first_keyword);
                
                // Check if 'All In One SEO Pack' or 'All In One SEO Pack Pro' is active
                if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                    $this->wpaicg_save_aioseo_focus_keyword_woo($product_id, $first_keyword);
                }
            }
        }

        public function wpaicg_save_aioseo_focus_keyword_woo($post_id, $first_keyword) {
            global $wpdb;
        
            // Define default AIOSEO format
            $default_aioseo_format = json_encode([
                "focus" => [
                    "keyphrase" => "",
                    "score" => 0,
                    "analysis" => []
                ],
                "additional" => []
            ]);
        
            // Get existing AIOSEO data if it exists
            $existing_aioseo_data = $wpdb->get_var($wpdb->prepare(
                "SELECT keyphrases FROM " . $wpdb->prefix . "aioseo_posts WHERE post_id = %d",
                $post_id
            ));
        
            // If existing data is not found, use the default structure
            if (null === $existing_aioseo_data) {
                $existing_aioseo_data = $default_aioseo_format;
            }
        
            // Decode to PHP array
            $existing_aioseo_array = json_decode($existing_aioseo_data, true);
        
            // Update only the keyphrase
            $existing_aioseo_array['focus']['keyphrase'] = $first_keyword;
        
            // Encode back to JSON
            $updated_aioseo_data = json_encode($existing_aioseo_array);
        
            // Update or Insert into wp_aioseo_posts table
            $check = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "aioseo_posts WHERE post_id = %d", $post_id));
            if ($check) {
                $wpdb->update(
                    $wpdb->prefix . 'aioseo_posts',
                    ['keyphrases' => $updated_aioseo_data],
                    ['post_id' => $post_id]
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'aioseo_posts',
                    [
                        'post_id' => $post_id,
                        'keyphrases' => $updated_aioseo_data,
                        'created' => gmdate('Y-m-d H:i:s'),
                        'updated' => gmdate('Y-m-d H:i:s')
                    ]
                );
            }
        }

        public function enforce_keyword_in_slug($wpaicgProductID) {
            // Initialize result array
            $result = array(
                'status' => 0,
                'msg' => '',
                'new_slug' => null
            );
            
            // Check if the user is on the pro plan
            $is_user_pro = \WPAICG\wpaicg_util_core()->wpaicg_is_pro();
        
            update_option('wpaicg_enforce_woo_keyword_in_url', true);
            $enforce_keyword_option = get_option('wpaicg_enforce_woo_keyword_in_url', false);
            
            // Fetch the focus keyword for the product
            $focus_keyword = get_post_meta($wpaicgProductID, 'rank_math_focus_keyword', true);

            // If focus keyword does not exist
            if (empty($focus_keyword)) {
                $result['status'] = 10;
                $result['msg'] = esc_html__('There is no focus keyword for the product. Skipped.', 'gpt3-ai-content-generator');
                return $result;
            }
            
            // Check the three conditions
            if ($is_user_pro && $enforce_keyword_option && !empty($focus_keyword)) {
                $current_post = get_post($wpaicgProductID);
                $current_slug = $current_post->post_name;

                // Convert focus keyword to slug format (replace spaces with hyphens and convert to lowercase)
                $focus_keyword_slugified = strtolower(str_replace(' ', '-', $focus_keyword));
                
                // Check if focus keyword is already present in the slug
                if (stripos($current_slug, $focus_keyword_slugified) === false) {
                    // Add the focus keyword to the beginning of the slug
                    $new_slug = $focus_keyword . '-' . $current_slug;
                    
                    // Construct the root WooCommerce product URL
                    $site_url = get_site_url();
                    $woo_permalinks = get_option('woocommerce_permalinks');
                    $product_base = isset($woo_permalinks['product_base']) ? $woo_permalinks['product_base'] : 'product';
                    $product_base_url = trailingslashit($site_url) . $product_base . '/';
                    
                    // Calculate the total new URL length
                    $total_url_length = strlen($product_base_url) + strlen($new_slug);
                    
                    // If the new URL length exceeds 70, shorten the slug
                    if ($total_url_length > 70) {
                        // Define the maximum URL length
                        $max_url_length = 70;
                        // Calculate the maximum length for the slug by considering the product base URL
                        $max_slug_length = $max_url_length - strlen($product_base_url);
                        // Common stop words
                        $stop_words = array('of', 'the', 'a', 'an', 'in', 'on', 'at', 'by', 'for', 'with', 'to', 'from', 'as', 'and', 'or', 'but', 'is', 'are', 'was', 'were', 'be', 'been');
                        // Remove stop words from the slug
                        $slug_parts = explode('-', $new_slug);
                        $filtered_slug_parts = array_diff($slug_parts, $stop_words);
                        $filtered_slug = implode('-', $filtered_slug_parts);
                        // Check if the slug length exceeds the calculated maximum length
                        if (strlen($filtered_slug) > $max_slug_length) {
                            // Shorten the slug to the maximum length while ensuring words are not cut off
                            $new_slug_parts = explode('-', $filtered_slug);
                            $new_slug = '';
                            $current_length = 0;
                            foreach ($new_slug_parts as $part) {
                                if ($current_length + strlen($part) + 1 <= $max_slug_length) {
                                    $new_slug .= $part . '-';
                                    $current_length += strlen($part) + 1;
                                } else {
                                    break;
                                }
                            }
                            // Remove the trailing hyphen
                            $new_slug = rtrim($new_slug, '-');
                        }
                    }
                    
                    $result['status'] = 3;
                    $result['msg'] = esc_html__('Slug updated with focus keyword successfully.', 'gpt3-ai-content-generator');
                    $result['new_slug'] = $new_slug;
                } else {
                    $result['status'] = 1;
                    $result['msg'] = esc_html__('Focus keyword is already present in the URL. Skipped.', 'gpt3-ai-content-generator');
                }
            }
            return $result;
        }
        


    }

    WPAICG_WooCommerce::get_instance();
}
