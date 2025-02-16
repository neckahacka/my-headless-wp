<?php

namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;
if(!class_exists('\\WPAICG\\WPAICG_Util')) {
    class WPAICG_Util
    {
        private static  $instance = null ;

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        // Function to get the default AI provider and model
        public function get_default_ai_provider() {
            $provider = get_option('wpaicg_provider', 'OpenAI');
            $model = '';

            switch ($provider) {
                case 'OpenAI':
                    $model = get_option('wpaicg_ai_model', 'gpt-3.5-turbo-16k');
                    break;
                case 'Azure':
                    $model = get_option('wpaicg_azure_deployment', '');
                    break;
                case 'Google':
                    $model = get_option('wpaicg_google_default_model', 'gemini-pro');
                    break;
                case 'OpenRouter':
                    $model = get_option('wpaicg_openrouter_default_model', 'openai/gpt-4o');
                    break;
                default:
                    $model = 'gpt-3.5-turbo-16k';
            }

            return array('provider' => $provider, 'model' => $model);
        }

        public function initialize_ai_engine($provider = null) {
            $wpaicg_provider = $provider ? $provider : get_option('wpaicg_provider', 'OpenAI');
            $ai_engine = WPAICG_OpenAI::get_instance()->openai();
    
            switch ($wpaicg_provider) {
                case 'OpenAI':
                    $ai_engine = WPAICG_OpenAI::get_instance()->openai();
                    break;
                case 'Azure':
                    $ai_engine = WPAICG_AzureAI::get_instance()->azureai();
                    break;
                case 'Google':
                    $ai_engine = WPAICG_Google::get_instance();
                    break;
                case 'OpenRouter':
                    $ai_engine = WPAICG_OpenRouter::get_instance()->openai();
                    break;
                default:
                    $ai_engine = WPAICG_OpenAI::get_instance()->openai();
            }
    
            if (!$ai_engine) {
                throw new \Exception(esc_html__('Enter your API key in the Settings.', 'gpt3-ai-content-generator'));
            }
    
            return $ai_engine;
        }

        public function initialize_embedding_engine($selected_embedding_provider = null, $wpaicg_provider = null) {
            if (!$selected_embedding_provider) {
                $selected_embedding_provider = $wpaicg_provider ? $wpaicg_provider : get_option('wpaicg_provider', 'OpenAI'); // default to OpenAI
            }
        
            switch ($selected_embedding_provider) {
                case 'OpenAI':
                    $ai_engine = WPAICG_OpenAI::get_instance()->openai();
                    break;
                case 'Azure':
                    $ai_engine = WPAICG_AzureAI::get_instance()->azureai();
                    break;
                case 'Google':
                    $ai_engine = WPAICG_Google::get_instance();
                    break;
                default:
                    $ai_engine = WPAICG_OpenAI::get_instance()->openai();
            }
        
            if (!$ai_engine) {
                throw new \Exception(esc_html__('Unable to initialize the AI engine. Please check your API key settings.', 'gpt3-ai-content-generator'));
            }
        
            return $ai_engine;
        }
        

        public function get_embedding_models() {
            $models = array(
                'OpenAI' => array(
                    'text-embedding-3-small' => 1536,
                    'text-embedding-3-large' => 3072,
                    'text-embedding-ada-002' => 1536
                ),
                'Google' => array(
                    'embedding-001' => 768,
                    'text-embedding-004' => 768,
                )
            );
        
            // Check if 'wpaicg_azure_embeddings' exists in the options table
            $azure_embeddings = get_option('wpaicg_azure_embeddings');
            if (!empty($azure_embeddings)) {
                $models['Azure'] = array($azure_embeddings => 1536);
            }
        
            return $models;
        }
        
        public function seo_plugin_activated()
        {
            $activated = false;
            if(is_plugin_active('wordpress-seo/wp-seo.php')){
                $activated = '_yoast_wpseo_metadesc';
            }
            elseif(is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')){
                $activated = '_aioseo_description';
            }
            elseif(is_plugin_active('seo-by-rank-math/rank-math.php')){
                $activated = 'rank_math_description';
            }
            elseif (is_plugin_active('autodescription/autodescription.php')) { // The SEO Framework plugin check
                $activated = '_genesis_description';
            }
            return $activated;
        }

        public function wpaicg_random($length = 10) {
            $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $charactersLength = strlen($characters);
            $randomString = '';
            for ($i = 0; $i < $length; $i++) {
                $randomString .= $characters[wp_rand(0, $charactersLength - 1)];
            }
            return $randomString;
        }

        public function wpaicg_is_pro()
        {
            return wpaicg_gacg_fs()->is_plan__premium_only( 'pro' );
        }

        public function sanitize_text_or_array_field($array_or_string)
        {
            if (is_string($array_or_string)) {
                $array_or_string = sanitize_text_field($array_or_string);
            } elseif (is_array($array_or_string)) {
                foreach ($array_or_string as $key => &$value) {
                    if (is_array($value)) {
                        $value = $this->sanitize_text_or_array_field($value);
                    } else {
                        $value = sanitize_text_field(str_replace('%20','+',$value));
                    }
                }
            }

            return $array_or_string;
        }

        public function wpaicg_get_meta_keys($post_type = false)
        {
            if (empty($post_type)) return array();

            $post_type = ($post_type == 'product' and class_exists('WooCommerce')) ? array('product') : array($post_type);

            global $wpdb;
            $table_prefix = $wpdb->prefix;

            $post_type = array_map(function($item) use ($wpdb) {
                return $wpdb->prepare('%s', $item);
            }, $post_type);

            $post_type_in = implode(',', $post_type);

            $meta_keys = $wpdb->get_results($wpdb->prepare("SELECT DISTINCT {$table_prefix}postmeta.meta_key FROM {$table_prefix}postmeta, {$table_prefix}posts WHERE {$table_prefix}postmeta.post_id = {$table_prefix}posts.ID AND {$table_prefix}posts.post_type IN ({$post_type_in}) AND {$table_prefix}postmeta.meta_key NOT LIKE '_edit%' AND {$table_prefix}postmeta.meta_key NOT LIKE '_oembed_%' LIMIT 1000"));

            $_existing_meta_keys = array();
            if ( ! empty($meta_keys)){
                $exclude_keys = array('_first_variation_attributes', '_is_first_variation_created');
                foreach ($meta_keys as $meta_key) {
                    if ( strpos($meta_key->meta_key, "_tmp") === false && strpos($meta_key->meta_key, "_v_") === false && ! in_array($meta_key->meta_key, $exclude_keys))
                        $_existing_meta_keys[] = 'wpaicgcf_'.$meta_key->meta_key;
                }
            }
            return $_existing_meta_keys;
        }

        public function wpaicg_existing_taxonomies($post_type = false)
        {
            if (empty($post_type)) return array();

            $post_taxonomies = array_diff_key($this->wpaicg_get_taxonomies_by_object_type(array($post_type), 'object'), array_flip(array('post_format')));
            $_existing_taxonomies = array();
            if ( ! empty($post_taxonomies)){
                foreach ($post_taxonomies as $tx) {
                    if (strpos($tx->name, "pa_") !== 0)
                        $_existing_taxonomies[] = array(
                            'name' => empty($tx->label) ? $tx->name : $tx->label,
                            'label' => 'wpaicgtx_'.$tx->name,
                            'type' => 'cats'
                        );
                }
            }
            return $_existing_taxonomies;
        }

        function wpaicg_get_taxonomies_by_object_type($object_type, $output = 'names') {
            global $wp_taxonomies;

            is_array($object_type) or $object_type = array($object_type);
            $field = ('names' == $output) ? 'name' : false;
            $filtered = array();
            foreach ($wp_taxonomies as $key => $obj) {
                if (array_intersect($object_type, $obj->object_type)) {
                    $filtered[$key] = $obj;
                }
            }
            if ($field) {
                $filtered = wp_list_pluck($filtered, $field);
            }
            return $filtered;
        }

        public function wpaicg_tabs($prefix, $menus, $selected = false)
        {
            foreach($menus as $key=>$menu){
                $capability = $prefix;
                if(is_string($key)){
                    $capability .= '_'.$key;
                }
                if($capability == 'wpaicg_finetune_fine-tunes'){
                    $capability = 'wpaicg_finetune_file-tunes';
                }
                if(current_user_can($capability) || in_array('administrator', (array)wp_get_current_user()->roles)){
                    $url = admin_url('admin.php?page='.$prefix);
                    if(is_string($key)){
                        $url .= '&action='.$key;
                    }
                    ?>
                    <a class="nav-tab<?php echo $key === $selected ? ' nav-tab-active':''?>" href="<?php echo esc_html($url)?>">
                        <?php
                        echo esc_html($menu);
                        if($key == 'pdf' && $prefix == 'wpaicg_embeddings' && !$this->wpaicg_is_pro()){
                            ?>
                            <span style="color: #000;padding: 2px 5px;font-size: 12px;background:#ffba00;border-radius: 2px;"><?php echo esc_html__('Pro','gpt3-ai-content-generator')?></span>
                            <?php
                        }
                        ?>
                    </a>
                    <?php
                }
            }
        }

        // Public function to retrieve default values based on bot type
        public static function get_default_values($bot_type = 'custom') {
            // Define common default values
            $default_icon_path = WPAICG_PLUGIN_URL . 'admin/images/chatbot.png';
            $default_values = array(
                "bgcolor" => "#343A40",
                "fontcolor" => "#E8E8E8",
                "ai_bg_color" => "#495057",
                "user_bg_color" => "#6C757D",
                "width" => "75%",
                "height" => "50%",
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
                "provider" => "OpenAI",
                "model" => "gpt-4o-mini",
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
                "welcome" => "Hello 👋, how can I help you today?",
                "newchat" => "New Chat",
                "ai_thinking" => "Gathering thoughts",
                "placeholder" => "Type your message here...",
                "no_answer" => "",
                "feedback_title" => "Feedback",
                "feedback_message" => "Please provide details: (optional)",
                "feedback_success" => "Thank you for your feedback!",
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
                "profession" => "none",
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
                "lead_collection" => "0",
                "lead_title" => "Let us know how to contact you",
                "lead_name" => "Name",
                "enable_lead_name" => "1",
                "lead_email" => "Email",
                "enable_lead_email" => "1",
                "lead_phone" => "Phone",
                "enable_lead_phone" => "1",
            );

            // Add or modify defaults based on bot type
            switch ($bot_type) {
                case 'shortcode':
                    // Fetch conversation starters from another option table
                    $conversation_starters = get_option('wpaicg_conversation_starters', false);

                    // If conversation starters don't exist, create them with default values in the specified format
                    if ($conversation_starters === false) {

                        // [{"index":0,"text":"hi"},{"index":1,"text":"how"}]
                        $conversation_starters = json_encode(array(
                            array("index" => 0, "text" => "What’s today’s date?"),
                            array("index" => 1, "text" => "Can you tell me a joke?"),
                            array("index" => 2, "text" => "What’s something fun I can do today?"),
                        ));

                        // Save the conversation starters
                        update_option('wpaicg_conversation_starters', $conversation_starters);
                    }

                    // if wpaicg_shortcode_stream is not set, set it to 1
                    if (get_option('wpaicg_shortcode_stream', false) === false) {
                        update_option('wpaicg_shortcode_stream', 1);
                    }

                    // if wpaicg_shortcode_google_model not exist set it to gemini-pro
                    if (get_option('wpaicg_shortcode_google_model', false) === false) {
                        update_option('wpaicg_shortcode_google_model', 'gemini-pro');
                    }

                    $default_values = array_merge($default_values, array(
                        // Add or modify shortcode defaults
                    ));
                    break;

                case 'widget':
                    // Define an array of options with default values
                    $options = [
                        'wpaicg_widget_stream' => 1,
                        'wpaicg_chat_addition' => 1,
                        'wpaicg_chat_addition' => 1,
                        'wpaicg_chat_addition_text' => 'You are a helpful AI Assistant. Please be friendly. Today\'s date is [date].',
                        '_wpaicg_chatbox_welcome_message' => 'Hello 👋, how can I help you today?',
                        'wpaicg_chat_temperature' => 0,
                        'wpaicg_chat_max_tokens' => 1500,
                        'wpaicg_chat_presence_penalty' => 0,
                        'wpaicg_chat_language' => 'en',
                        'wpaicg_chat_vectordb' => 'pinecone',
                        'wpaicg_chat_embedding' => 0,
                        'wpaicg_chat_embedding_type' => 'openai',
                        'wpaicg_chat_embedding_top' => 1,
                        'wpaicg_widget_qdrant_collection' => '',
                        'wpaicg_conversation_cut' => 100,
                        '_wpaicg_ai_thinking' => 'Gathering thoughts..',
                        '_wpaicg_typing_placeholder' => 'Type your message here...',
                        'wpaicg_chat_top_p' => 0,
                        // Add more options as needed
                    ];

                    // Loop through each option and set it if it's not already set
                    foreach ($options as $option_name => $default_value) {
                        if (get_option($option_name, false) === false) {
                            update_option($option_name, $default_value);
                        }
                    }

                    $default_values = array_merge($default_values, array(
                        "position" => "left",
                        "width" => "300px",
                        "height" => "50%",
                        "status" => '',
                        "pages" => '',
                        "delay_time" => '',
                        "icon" => 'default',
                        "icon_url" => $default_icon_path,
                        'close_btn' => '1',
                    ));
                    break;

                case 'custom':
                    $default_values = array_merge($default_values, array(
                        "icon_url" => "",
                        "id" => "custom_bot_id",
                        "type" => "custom",
                        "pages" => "",
                        "chat_rounded" => "8",
                        "fontsize" => "13",
                        "bg_text_field" => "#ffffff",
                        "input_font_color" => "#495057",
                        "border_text_field" => "#cccccc",
                    ));
                    break;

                default:
                    // You can return a basic default if no matching type is found
                    break;
            }

            return $default_values;
        }

        public $search_languages = [
            '' => 'Global',
            'lang_ar' => 'Arabic',
            'lang_bg' => 'Bulgarian',
            'lang_ca' => 'Catalan',
            'lang_cs' => 'Czech',
            'lang_da' => 'Danish',
            'lang_de' => 'German',
            'lang_el' => 'Greek',
            'lang_en' => 'English',
            'lang_es' => 'Spanish',
            'lang_et' => 'Estonian',
            'lang_fi' => 'Finnish',
            'lang_fr' => 'French',
            'lang_hr' => 'Croatian',
            'lang_hu' => 'Hungarian',
            'lang_id' => 'Indonesian',
            'lang_is' => 'Icelandic',
            'lang_it' => 'Italian',
            'lang_iw' => 'Hebrew',
            'lang_ja' => 'Japanese',
            'lang_ko' => 'Korean',
            'lang_lt' => 'Lithuanian',
            'lang_lv' => 'Latvian',
            'lang_nl' => 'Dutch',
            'lang_no' => 'Norwegian',
            'lang_pl' => 'Polish',
            'lang_pt' => 'Portuguese',
            'lang_ro' => 'Romanian',
            'lang_ru' => 'Russian',
            'lang_sk' => 'Slovak',
            'lang_sl' => 'Slovenian',
            'lang_sr' => 'Serbian',
            'lang_sv' => 'Swedish',
            'lang_tr' => 'Turkish',
            'lang_zh-CN' => 'Chinese (Standard)',
            'lang_zh-TW' => 'Chinese (Traditional)'
        ];
        

        public $wpaicg_countries = [
            '' => 'Global',
            'countryAF' => 'Afghanistan',
            'countryAL' => 'Albania',
            'countryDZ' => 'Algeria',
            'countryAS' => 'American Samoa',
            'countryAD' => 'Andorra',
            'countryAO' => 'Angola',
            'countryAI' => 'Anguilla',
            'countryAQ' => 'Antarctica',
            'countryAG' => 'Antigua and Barbuda',
            'countryAR' => 'Argentina',
            'countryAM' => 'Armenia',
            'countryAW' => 'Aruba',
            'countryAU' => 'Australia',
            'countryAT' => 'Austria',
            'countryAZ' => 'Azerbaijan',
            'countryBS' => 'Bahamas',
            'countryBH' => 'Bahrain',
            'countryBD' => 'Bangladesh',
            'countryBB' => 'Barbados',
            'countryBY' => 'Belarus',
            'countryBE' => 'Belgium',
            'countryBZ' => 'Belize',
            'countryBJ' => 'Benin',
            'countryBM' => 'Bermuda',
            'countryBT' => 'Bhutan',
            'countryBO' => 'Bolivia',
            'countryBA' => 'Bosnia and Herzegovina',
            'countryBW' => 'Botswana',
            'countryBV' => 'Bouvet Island',
            'countryBR' => 'Brazil',
            'countryIO' => 'British Indian Ocean Territory',
            'countryBN' => 'Brunei',
            'countryBG' => 'Bulgaria',
            'countryBF' => 'Burkina Faso',
            'countryBI' => 'Burundi',
            'countryKH' => 'Cambodia',
            'countryCM' => 'Cameroon',
            'countryCA' => 'Canada',
            'countryCV' => 'Cape Verde',
            'countryKY' => 'Cayman Islands',
            'countryCF' => 'Central African Republic',
            'countryTD' => 'Chad',
            'countryCL' => 'Chile',
            'countryCN' => 'China',
            'countryCX' => 'Christmas Island',
            'countryCC' => 'Cocos (Keeling) Islands',
            'countryCO' => 'Colombia',
            'countryKM' => 'Comoros',
            'countryCG' => 'Congo',
            'countryCD' => 'Congo, Democratic Republic',
            'countryCK' => 'Cook Islands',
            'countryCR' => 'Costa Rica',
            'countryCI' => 'Côte d\'Ivoire',
            'countryHR' => 'Croatia',
            'countryCU' => 'Cuba',
            'countryCY' => 'Cyprus',
            'countryCZ' => 'Czech Republic',
            'countryDK' => 'Denmark',
            'countryDJ' => 'Djibouti',
            'countryDM' => 'Dominica',
            'countryDO' => 'Dominican Republic',
            'countryEC' => 'Ecuador',
            'countryEG' => 'Egypt',
            'countrySV' => 'El Salvador',
            'countryGQ' => 'Equatorial Guinea',
            'countryER' => 'Eritrea',
            'countryEE' => 'Estonia',
            'countryET' => 'Ethiopia',
            'countryFK' => 'Falkland Islands',
            'countryFO' => 'Faroe Islands',
            'countryFJ' => 'Fiji',
            'countryFI' => 'Finland',
            'countryFR' => 'France',
            'countryGF' => 'French Guiana',
            'countryPF' => 'French Polynesia',
            'countryTF' => 'French Southern Territories',
            'countryGA' => 'Gabon',
            'countryGM' => 'Gambia',
            'countryGE' => 'Georgia',
            'countryDE' => 'Germany',
            'countryGH' => 'Ghana',
            'countryGI' => 'Gibraltar',
            'countryGR' => 'Greece',
            'countryGL' => 'Greenland',
            'countryGD' => 'Grenada',
            'countryGP' => 'Guadeloupe',
            'countryGU' => 'Guam',
            'countryGT' => 'Guatemala',
            'countryGG' => 'Guernsey',
            'countryGN' => 'Guinea',
            'countryGW' => 'Guinea-Bissau',
            'countryGY' => 'Guyana',
            'countryHT' => 'Haiti',
            'countryHM' => 'Heard Island and McDonald Islands',
            'countryVA' => 'Holy See (Vatican City)',
            'countryHN' => 'Honduras',
            'countryHK' => 'Hong Kong',
            'countryHU' => 'Hungary',
            'countryIS' => 'Iceland',
            'countryIN' => 'India',
            'countryID' => 'Indonesia',
            'countryIR' => 'Iran',
            'countryIQ' => 'Iraq',
            'countryIE' => 'Ireland',
            'countryIM' => 'Isle of Man',
            'countryIL' => 'Israel',
            'countryIT' => 'Italy',
            'countryJM' => 'Jamaica',
            'countryJP' => 'Japan',
            'countryJE' => 'Jersey',
            'countryJO' => 'Jordan',
            'countryKZ' => 'Kazakhstan',
            'countryKE' => 'Kenya',
            'countryKI' => 'Kiribati',
            'countryKP' => 'Korea, Democratic People\'s Republic',
            'countryKR' => 'Korea, Republic',
            'countryKW' => 'Kuwait',
            'countryKG' => 'Kyrgyzstan',
            'countryLA' => 'Lao',
            'countryLV' => 'Latvia',
            'countryLB' => 'Lebanon',
            'countryLS' => 'Lesotho',
            'countryLR' => 'Liberia',
            'countryLY' => 'Libya',
            'countryLI' => 'Liechtenstein',
            'countryLT' => 'Lithuania',
            'countryLU' => 'Luxembourg',
            'countryMO' => 'Macao',
            'countryMK' => 'Macedonia',
            'countryMG' => 'Madagascar',
            'countryMW' => 'Malawi',
            'countryMY' => 'Malaysia',
            'countryMV' => 'Maldives',
            'countryML' => 'Mali',
            'countryMT' => 'Malta',
            'countryMH' => 'Marshall Islands',
            'countryMQ' => 'Martinique',
            'countryMR' => 'Mauritania',
            'countryMU' => 'Mauritius',
            'countryYT' => 'Mayotte',
            'countryMX' => 'Mexico',
            'countryFM' => 'Micronesia',
            'countryMD' => 'Moldova',
            'countryMC' => 'Monaco',
            'countryMN' => 'Mongolia',
            'countryME' => 'Montenegro',
            'countryMS' => 'Montserrat',
            'countryMA' => 'Morocco',
            'countryMZ' => 'Mozambique',
            'countryMM' => 'Myanmar',
            'countryNA' => 'Namibia',
            'countryNR' => 'Nauru',
            'countryNP' => 'Nepal',
            'countryNL' => 'Netherlands',
            'countryNC' => 'New Caledonia',
            'countryNZ' => 'New Zealand',
            'countryNI' => 'Nicaragua',
            'countryNE' => 'Niger',
            'countryNG' => 'Nigeria',
            'countryNU' => 'Niue',
            'countryNF' => 'Norfolk Island',
            'countryMP' => 'Northern Mariana Islands',
            'countryNO' => 'Norway',
            'countryOM' => 'Oman',
            'countryPK' => 'Pakistan',
            'countryPW' => 'Palau',
            'countryPS' => 'Palestine, State',
            'countryPA' => 'Panama',
            'countryPG' => 'Papua New Guinea',
            'countryPY' => 'Paraguay',
            'countryPE' => 'Peru',
            'countryPH' => 'Philippines',
            'countryPN' => 'Pitcairn Islands',
            'countryPL' => 'Poland',
            'countryPT' => 'Portugal',
            'countryPR' => 'Puerto Rico',
            'countryQA' => 'Qatar',
            'countryRE' => 'Réunion',
            'countryRO' => 'Romania',
            'countryRU' => 'Russian Federation',
            'countryRW' => 'Rwanda',
            'countrySH' => 'Saint Helena',
            'countryKN' => 'Saint Kitts and Nevis',
            'countryLC' => 'Saint Lucia',
            'countryPM' => 'Saint Pierre and Miquelon',
            'countryVC' => 'Saint Vincent and the Grenadines',
            'countryWS' => 'Samoa',
            'countrySM' => 'San Marino',
            'countryST' => 'Sao Tome and Principe',
            'countrySA' => 'Saudi Arabia',
            'countrySN' => 'Senegal',
            'countryRS' => 'Serbia',
            'countrySC' => 'Seychelles',
            'countrySL' => 'Sierra Leone',
            'countrySG' => 'Singapore',
            'countrySX' => 'Sint Maarten',
            'countrySK' => 'Slovakia',
            'countrySI' => 'Slovenia',
            'countrySB' => 'Solomon Islands',
            'countrySO' => 'Somalia',
            'countryZA' => 'South Africa',
            'countryGS' => 'South Georgia and South Sandwich Islands',
            'countrySS' => 'South Sudan',
            'countryES' => 'Spain',
            'countryLK' => 'Sri Lanka',
            'countrySD' => 'Sudan',
            'countrySR' => 'Suriname',
            'countrySJ' => 'Svalbard and Jan Mayen',
            'countrySZ' => 'Swaziland',
            'countrySE' => 'Sweden',
            'countryCH' => 'Switzerland',
            'countrySY' => 'Syrian Arab Republic',
            'countryTW' => 'Taiwan',
            'countryTJ' => 'Tajikistan',
            'countryTZ' => 'Tanzania',
            'countryTH' => 'Thailand',
            'countryTL' => 'Timor-Leste',
            'countryTG' => 'Togo',
            'countryTK' => 'Tokelau',
            'countryTO' => 'Tonga',
            'countryTT' => 'Trinidad and Tobago',
            'countryTN' => 'Tunisia',
            'countryTR' => 'Turkey',
            'countryTM' => 'Turkmenistan',
            'countryTC' => 'Turks and Caicos Islands',
            'countryTV' => 'Tuvalu',
            'countryUG' => 'Uganda',
            'countryUA' => 'Ukraine',
            'countryAE' => 'United Arab Emirates',
            'countryGB' => 'United Kingdom',
            'countryUS' => 'United States',
            'countryUY' => 'Uruguay',
            'countryUZ' => 'Uzbekistan',
            'countryVU' => 'Vanuatu',
            'countryVE' => 'Venezuela',
            'countryVN' => 'Viet Nam',
            'countryVG' => 'Virgin Islands, British',
            'countryVI' => 'Virgin Islands, U.S.',
            'countryWF' => 'Wallis and Futuna',
            'countryEH' => 'Western Sahara',
            'countryYE' => 'Yemen',
            'countryZM' => 'Zambia',
            'countryZW' => 'Zimbabwe'
        ];

        public $wpaicg_languages = [
            'en' => 'English',
            'af' => 'Afrikaans',
            'ar' => 'Arabic',
            'an' => 'Armenian',
            'bs' => 'Bosnian',
            'bg' => 'Bulgarian',
            'zh' => 'Chinese (Standard)',
            'zt' => 'Chinese (Traditional)',
            'hr' => 'Croatian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'nl' => 'Dutch',
            'et' => 'Estonian',
            'fil' => 'Filipino',
            'fi' => 'Finnish',
            'fr' => 'French',
            'de' => 'German',
            'el' => 'Greek',
            'he' => 'Hebrew',
            'hi' => 'Hindi',
            'hu' => 'Hungarian',
            'id' => 'Indonesian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'ms' => 'Malay',
            'no' => 'Norwegian',
            'fa' => 'Persian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'sr' => 'Serbian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'es' => 'Spanish',
            'sv' => 'Swedish',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese'
        ];

        public $chat_language_options = array(
            'en' => 'English',
            'af' => 'Afrikaans',
            'ar' => 'Arabic',
            'bg' => 'Bulgarian',
            'zh' => 'Chinese',
            'hr' => 'Croatian',
            'cs' => 'Czech',
            'da' => 'Danish',
            'nl' => 'Dutch',
            'et' => 'Estonian',
            'fil' => 'Filipino',
            'fi' => 'Finnish',
            'fr' => 'French',
            'de' => 'German',
            'el' => 'Greek',
            'he' => 'Hebrew',
            'hi' => 'Hindi',
            'hu' => 'Hungarian',
            'id' => 'Indonesian',
            'it' => 'Italian',
            'ja' => 'Japanese',
            'ko' => 'Korean',
            'lv' => 'Latvian',
            'lt' => 'Lithuanian',
            'ms' => 'Malay',
            'no' => 'Norwegian',
            'fa' => 'Persian',
            'pl' => 'Polish',
            'pt' => 'Portuguese',
            'ro' => 'Romanian',
            'ru' => 'Russian',
            'sr' => 'Serbian',
            'sk' => 'Slovak',
            'sl' => 'Slovenian',
            'sv' => 'Swedish',
            'es' => 'Spanish',
            'th' => 'Thai',
            'tr' => 'Turkish',
            'uk' => 'Ukrainian',
            'vi' => 'Vietnamese',
        );
    
        public $chat_profession_options = array(
            'none' => 'None',
            'accountant' => 'Accountant',
            'advertisingspecialist' => 'Advertising Specialist',
            'architect' => 'Architect',
            'artist' => 'Artist',
            'blogger' => 'Blogger',
            'businessanalyst' => 'Business Analyst',
            'businessowner' => 'Business Owner',
            'carexpert' => 'Car Expert',
            'consultant' => 'Consultant',
            'counselor' => 'Counselor',
            'cryptocurrencytrader' => 'Cryptocurrency Trader',
            'cryptocurrencyexpert' => 'Cryptocurrency Expert',
            'customersupport' => 'Customer Support', 
            'designer' => 'Designer',
            'digitalmarketinagency' => 'Digital Marketing Agency',
            'editor' => 'Editor',
            'engineer' => 'Engineer',
            'eventplanner' => 'Event Planner',
            'freelancer' => 'Freelancer',
            'insuranceagent' => 'Insurance Agent',
            'insurancebroker' => 'Insurance Broker',
            'interiordesigner' => 'Interior Designer',
            'journalist' => 'Journalist',
            'marketingagency' => 'Marketing Agency',
            'marketingexpert' => 'Marketing Expert',
            'marketingspecialist' => 'Marketing Specialist',
            'photographer' => 'Photographer',
            'programmer' => 'Programmer',
            'publicrelationsagency' => 'Public Relations Agency',
            'publisher' => 'Publisher',
            'realestateagent' => 'Real Estate Agent',
            'recruiter' => 'Recruiter',
            'reporter' => 'Reporter',
            'salesperson' => 'Sales Person',
            'salerep' => 'Sales Representative',
            'seoagency' => 'SEO Agency',
            'seoexpert' => 'SEO Expert',
            'socialmediaagency' => 'Social Media Agency',
            'student' => 'Student',
            'teacher' => 'Teacher',
            'technicalsupport' => 'Technical Support',
            'trainer' => 'Trainer',
            'travelagency' => 'Travel Agency',
            'videographer' => 'Videographer', 
            'webdesignagency' => 'Web Design Agency',
            'webdesignexpert' => 'Web Design Expert',
            'webdevelopmentagency' => 'Web Development Agency', 
            'webdevelopmentexpert' => 'Web Development Expert',
            'webdesigner' => 'Web Designer', 
            'webdeveloper' => 'Web Developer',
            'writer' => 'Writer'
        );        

        public $chat_tone_options = array(
            'friendly' => 'Friendly',
            'professional' => 'Professional',
            'sarcastic' => 'Sarcastic',
            'humorous' => 'Humorous',
            'cheerful' => 'Cheerful',
            'anecdotal' => 'Anecdotal'
        );

        public $wpaicg_writing_styles = array(
            'infor' => 'Informative',
            'acade' => 'Academic',
            'analy' => 'Analytical',
            'anect' => 'Anecdotal',
            'argum' => 'Argumentative',
            'artic' => 'Articulate',
            'biogr' => 'Biographical',
            'blog' => 'Blog',
            'casua' => 'Casual',
            'collo' => 'Colloquial',
            'compa' => 'Comparative',
            'conci' => 'Concise',
            'creat' => 'Creative',
            'criti' => 'Critical',
            'descr' => 'Descriptive',
            'detai' => 'Detailed',
            'dialo' => 'Dialogue',
            'direct' => 'Direct',
            'drama' => 'Dramatic',
            'evalu' => 'Evaluative',
            'emoti' => 'Emotional',
            'expos' => 'Expository',
            'ficti' => 'Fiction',
            'histo' => 'Historical',
            'journ' => 'Journalistic',
            'lette' => 'Letter',
            'lyric' => 'Lyrical',
            'metaph' => 'Metaphorical',
            'monol' => 'Monologue',
            'narra' => 'Narrative',
            'news' => 'News',
            'objec' => 'Objective',
            'pasto' => 'Pastoral',
            'perso' => 'Personal',
            'persu' => 'Persuasive',
            'poeti' => 'Poetic',
            'refle' => 'Reflective',
            'rheto' => 'Rhetorical',
            'satir' => 'Satirical',
            'senso' => 'Sensory',
            'simpl' => 'Simple',
            'techn' => 'Technical',
            'theore' => 'Theoretical',
            'vivid' => 'Vivid',
            'busin' => 'Business',
            'repor' => 'Report',
            'resea' => 'Research'
        );
    
        public $wpaicg_writing_tones = array(
            'formal' => 'Formal',
            'asser' => 'Assertive',
            'authoritative' => 'Authoritative',
            'cheer' => 'Cheerful',
            'confident' => 'Confident',
            'conve' => 'Conversational',
            'factual' => 'Factual',
            'friendly' => 'Friendly',
            'humor' => 'Humorous',
            'informal' => 'Informal',
            'inspi' => 'Inspirational',
            'neutr' => 'Neutral',
            'nostalgic' => 'Nostalgic',
            'polite' => 'Polite',
            'profe' => 'Professional',
            'romantic' => 'Romantic',
            'sarca' => 'Sarcastic',
            'scien' => 'Scientific',
            'sensit' => 'Sensitive',
            'serious' => 'Serious',
            'sincere' => 'Sincere',
            'skept' => 'Skeptical',
            'suspenseful' => 'Suspenseful',
            'sympathetic' => 'Sympathetic',
            'curio' => 'Curious',
            'disap' => 'Disappointed',
            'encou' => 'Encouraging',
            'optim' => 'Optimistic',
            'surpr' => 'Surprised',
            'worry' => 'Worried'
        );
      
        public $wpaicg_heading_tags = array('h1', 'h2', 'h3', 'h4', 'h5', 'h6');

        public $wpaicg_image_sizes = [
            '256x256' => 'Small (256x256)',
            '512x512' => 'Medium (512x512)',
            '1024x1024' => 'Big (1024x1024)',
            '1792x1024' => 'Wide (1792x1024)',
            '1024x1792' => 'Tall (1024x1792)',
        ];

        // Define available modules here
        public $wpaicg_modules = [
            'chat_bot' => [
                'title' => 'Chatbot',
                'menu_slug' => '', // No menu, so this is empty
                'capability' => '',
                'callback' => '', // No callback needed for the menu
                'position' => null, // No position as it doesn't appear in the menu
                'href' => '', // No href needed if it doesn't appear in the top nav
                'icon' => '', // No icon needed if it doesn't appear in the top nav
            ],
            'content_writer' => [
                'title' => 'Content Writer',
                'menu_slug' => 'wpaicg_single_content',
                'capability' => 'wpaicg_single_content',
                'callback' => 'wpaicg_single_content',
                'position' => 5,
                'href' => 'wpaicg_single_content',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 512 512">
                                <path d="M362.7 19.3L314.3 67.7 444.3 197.7l48.4-48.4c25-25 25-65.5 0-90.5L453.3 19.3c-25-25-65.5-25-90.5 0zm-71 71L58.6 323.5c-10.4 10.4-18 23.3-22.2 37.4L1 481.2C-1.5 489.7 .8 498.8 7 505s15.3 8.5 23.7 6.1l120.3-35.4c14.1-4.2 27-11.8 37.4-22.2L421.7 220.3 291.7 90.3z"/>
                            </svg>',
            ],
            'autogpt' => [
                'title' => 'AutoGPT',
                'menu_slug' => 'wpaicg_bulk_content',
                'capability' => 'wpaicg_bulk_content',
                'callback' => 'wpaicg_bulk_content',
                'position' => 6,
                'href' => 'wpaicg_bulk_content',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 448 512">
                                <path d="M349.4 44.6c5.9-13.7 1.5-29.7-10.6-38.5s-28.6-8-39.9 1.8l-256 224c-10 8.8-13.6 22.9-8.9 35.3S50.7 288 64 288l111.5 0L98.6 467.4c-5.9 13.7-1.5 29.7 10.6 38.5s28.6 8 39.9-1.8l256-224c10-8.8 13.6-22.9 8.9-35.3s-16.6-20.7-30-20.7l-111.5 0L349.4 44.6z"/>
                            </svg>',
            ],
            'ai_forms' => [
                'title' => 'AI Forms',
                'menu_slug' => 'wpaicg_forms',
                'capability' => 'wpaicg_forms',
                'callback' => 'wpaicg_forms',
                'position' => 7,
                'href' => 'wpaicg_forms',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 576 512">
                                <path d="M234.7 42.7L197 56.8c-3 1.1-5 4-5 7.2s2 6.1 5 7.2l37.7 14.1L248.8 123c1.1 3 4 5 7.2 5s6.1-2 7.2-5l14.1-37.7L315 71.2c3-1.1 5-4 5-7.2s-2-6.1-5-7.2L277.3 42.7 263.2 5c-1.1-3-4-5-7.2-5s-6.1 2-7.2 5L234.7 42.7zM46.1 395.4c-18.7 18.7-18.7 49.1 0 67.9l34.6 34.6c18.7 18.7 49.1 18.7 67.9 0L529.9 116.5c18.7-18.7 18.7-49.1 0-67.9L495.3 14.1c-18.7-18.7-49.1-18.7-67.9 0L46.1 395.4zM484.6 82.6l-105 105-23.3-23.3 105-105 23.3 23.3zM7.5 117.2C3 118.9 0 123.2 0 128s3 9.1 7.5 10.8L64 160l21.2 56.5c1.7 4.5 6 7.5 10.8 7.5s9.1-3 10.8-7.5L128 160l56.5-21.2c4.5-1.7 7.5-6 7.5-10.8s-3-9.1-7.5-10.8L128 96 106.8 39.5C105.1 35 100.8 32 96 32s-9.1 3-10.8 7.5L64 96 7.5 117.2zm352 256c-4.5 1.7-7.5 6-7.5 10.8s3 9.1 7.5 10.8L416 416l21.2 56.5c1.7 4.5 6 7.5 10.8 7.5s9.1-3 10.8-7.5L480 416l56.5-21.2c4.5-1.7 7.5-6 7.5-10.8s-3-9.1-7.5-10.8L480 352l-21.2-56.5c-1.7-4.5-6-7.5-10.8-7.5s-9.1 3-10.8 7.5L416 352l-56.5 21.2z"/>
                            </svg>',
            ],
            'image_generator' => [
                'title' => 'Image Generator',
                'menu_slug' => 'wpaicg_image_generator',
                'capability' => 'wpaicg_image_generator',
                'callback' => 'wpaicg_image_generator',
                'position' => 9,
                'href' => 'wpaicg_image_generator',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 512 512">
                                <path d="M0 96C0 60.7 28.7 32 64 32l384 0c35.3 0 64 28.7 64 64l0 320c0 35.3-28.7 64-64 64L64 480c-35.3 0-64-28.7-64-64L0 96zM323.8 202.5c-4.5-6.6-11.9-10.5-19.8-10.5s-15.4 3.9-19.8 10.5l-87 127.6L170.7 297c-4.6-5.7-11.5-9-18.7-9s-14.2 3.3-18.7 9l-64 80c-5.8 7.2-6.9 17.1-2.9 25.4s12.4 13.6 21.6 13.6l96 0 32 0 208 0c8.9 0 17.1-4.9 21.2-12.8s3.6-17.4-1.4-24.7l-120-176zM112 192a48 48 0 1 0 0-96 48 48 0 1 0 0 96z"/>
                            </svg>',
            ],
            'training' => [
                'title' => 'Training',
                'menu_slug' => 'wpaicg_embeddings',
                'capability' => 'wpaicg_embeddings',
                'callback' => 'wpaicg_main',
                'position' => 10,
                'href' => 'wpaicg_embeddings',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 640 512">
                                <path d="M320 0c17.7 0 32 14.3 32 32l0 64 120 0c39.8 0 72 32.2 72 72l0 272c0 39.8-32.2 72-72 72l-304 0c-39.8 0-72-32.2-72-72l0-272c0-39.8 32.2-72 72-72l120 0 0-64c0-17.7 14.3-32 32-32zM208 384c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zm96 0c-8.8 0-16 7.2-16 16s7.2 16 16 16l32 0c8.8 0 16-7.2 16-16s-7.2-16-16-16l-32 0zM264 256a40 40 0 1 0 -80 0 40 40 0 1 0 80 0zm152 40a40 40 0 1 0 0-80 40 40 0 1 0 0 80zM48 224l16 0 0 192-16 0c-26.5 0-48-21.5-48-48l0-96c0-26.5 21.5-48 48-48zm544 0c26.5 0 48 21.5 48 48l0 96c0 26.5-21.5 48-48 48l-16 0 0-192 16 0z"/>
                            </svg>',
            ],
            'ai_account' => [
                'title' => 'User Credits',
                'menu_slug' => 'wpaicg_myai_account',
                'capability' => 'wpaicg_myai_account',
                'callback' => 'wpaicg_myai_account',
                'position' => 11,
                'href' => 'wpaicg_myai_account',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 512 512">
                                <path d="M512 80c0 18-14.3 34.6-38.4 48c-29.1 16.1-72.5 27.5-122.3 30.9c-3.7-1.8-7.4-3.5-11.3-5C300.6 137.4 248.2 128 192 128c-8.3 0-16.4 .2-24.5 .6l-1.1-.6C142.3 114.6 128 98 128 80c0-44.2 86-80 192-80S512 35.8 512 80zM160.7 161.1c10.2-.7 20.7-1.1 31.3-1.1c62.2 0 117.4 12.3 152.5 31.4C369.3 204.9 384 221.7 384 240c0 4-.7 7.9-2.1 11.7c-4.6 13.2-17 25.3-35 35.5c0 0 0 0 0 0c-.1 .1-.3 .1-.4 .2c0 0 0 0 0 0s0 0 0 0c-.3 .2-.6 .3-.9 .5c-35 19.4-90.8 32-153.6 32c-59.6 0-112.9-11.3-148.2-29.1c-1.9-.9-3.7-1.9-5.5-2.9C14.3 274.6 0 258 0 240c0-34.8 53.4-64.5 128-75.4c10.5-1.5 21.4-2.7 32.7-3.5zM416 240c0-21.9-10.6-39.9-24.1-53.4c28.3-4.4 54.2-11.4 76.2-20.5c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 19.3-16.5 37.1-43.8 50.9c-14.6 7.4-32.4 13.7-52.4 18.5c.1-1.8 .2-3.5 .2-5.3zm-32 96c0 18-14.3 34.6-38.4 48c-1.8 1-3.6 1.9-5.5 2.9C304.9 404.7 251.6 416 192 416c-62.8 0-118.6-12.6-153.6-32C14.3 370.6 0 354 0 336l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 342.6 135.8 352 192 352s108.6-9.4 148.1-25.9c7.8-3.2 15.3-6.9 22.4-10.9c6.1-3.4 11.8-7.2 17.2-11.2c1.5-1.1 2.9-2.3 4.3-3.4l0 3.4 0 5.7 0 26.3zm32 0l0-32 0-25.9c19-4.2 36.5-9.5 52.1-16c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 10.5-5 21-14.9 30.9c-16.3 16.3-45 29.7-81.3 38.4c.1-1.7 .2-3.5 .2-5.3zM192 448c56.2 0 108.6-9.4 148.1-25.9c16.3-6.8 31.5-15.2 43.9-25.5l0 35.4c0 44.2-86 80-192 80S0 476.2 0 432l0-35.4c12.5 10.3 27.6 18.7 43.9 25.5C83.4 438.6 135.8 448 192 448z"/>
                            </svg>',
            ],
            'audio_converter' => [
                'title' => 'Audio Converter',
                'menu_slug' => 'wpaicg_audio',
                'capability' => 'wpaicg_audio',
                'callback' => 'wpaicg_audio',
                'position' => 12,
                'href' => 'wpaicg_audio',
                'icon'  => '<svg class="aipower-icon" viewBox="0 0 512 512">
                                <path d="M499.1 6.3c8.1 6 12.9 15.6 12.9 25.7l0 72 0 264c0 44.2-43 80-96 80s-96-35.8-96-80s43-80 96-80c11.2 0 22 1.6 32 4.6L448 147 192 223.8 192 432c0 44.2-43 80-96 80s-96-35.8-96-80s43-80 96-80c11.2 0 22 1.6 32 4.6L128 200l0-72c0-14.1 9.3-26.6 22.8-30.7l320-96c9.7-2.9 20.2-1.1 28.3 5z"/>
                            </svg>',
            ],
        ];

        public $wpaicg_image_styles = [
            '' => 'None',
            'abstract' => 'Abstract',
            'modern' => 'Modern',
            'impressionist' => 'Impressionist',
            'popart' => 'Pop Art',
            'cubism' => 'Cubism',
            'surrealism' => 'Surrealism',
            'contemporary' => 'Contemporary',
            'cantasy' => 'Fantasy',
            'graffiti' => 'Graffiti',
            'abstract_expressionism' => 'Abstract Expressionism',
            'action_painting' => 'Action painting',
            'art_brut' => 'Art Brut',
            'art_deco' => 'Art Deco',
            'art_nouveau' => 'Art Nouveau',
            'baroque' => 'Baroque',
            'byzantine' => 'Byzantine',
            'classical' => 'Classical',
            'color_field' => 'Color Field',
            'conceptual' => 'Conceptual',
            'dada' => 'Dada',
            'expressionism' => 'Expressionism',
            'fauvism' => 'Fauvism',
            'figurative' => 'Figurative',
            'futurism' => 'Futurism',
            'gothic' => 'Gothic',
            'hard_edge_painting' => 'Hard-edge painting',
            'hyperrealism' => 'Hyperrealism',
            'japonisme' => 'Japonisme',
            'luminism' => 'Luminism',
            'lyrical_abstraction' => 'Lyrical Abstraction',
            'mannerism' => 'Mannerism',
            'minimalism' => 'Minimalism',
            'naive_art' => 'Naive Art',
            'new_realism' => 'New Realism',
            'neo_expressionism' => 'Neo-expressionism',
            'neo_pop' => 'Neo-pop',
            'op_art' => 'Op Art',
            'opus_anglicanum' => 'Opus Anglicanum',
            'outsider_art' => 'Outsider Art',
            'photorealism' => 'Photorealism',
            'pointillism' => 'Pointillism',
            'post_impressionism' => 'Post-Impressionism',
            'realism' => 'Realism',
            'renaissance' => 'Renaissance',
            'rococo' => 'Rococo',
            'romanticism' => 'Romanticism',
            'street_art' => 'Street Art',
            'superflat' => 'Superflat',
            'symbolism' => 'Symbolism',
            'tenebrism' => 'Tenebrism',
            'ukiyo_e' => 'Ukiyo-e',
            'western_art' => 'Western Art',
            'yba' => 'YBA'
        ];

        public $playground_categories = [
            '' => 'Select a category',
            'wordpress' => 'WordPress',
            'blogging' => 'Blogging',
            'writing' => 'Writing',
            'ecommerce' => 'E-commerce',
            'online_business' => 'Online Business',
            'entrepreneurship' => 'Entrepreneurship',
            'seo' => 'SEO',
            'social_media' => 'Social Media',
            'digital_marketing' => 'Digital Marketing',
            'woocommerce' => 'WooCommerce',
            'content_strategy' => 'Content Strategy',
            'keyword_research' => 'Keyword Research',
            'product_listing' => 'Product Listing',
            'customer_relationship_management' => 'Customer Relationship Management',
        ];

        public $openai_gpt4_models = [
            'chatgpt-4o-latest' => 'ChatGPT-4o',
            'gpt-4' => 'GPT-4',
            'gpt-4o' => 'GPT-4o',
            'gpt-4o-mini' => 'GPT-4o Mini',
            'gpt-4-turbo' => 'GPT-4 Turbo',
            'gpt-4-vision-preview' => 'GPT-4 Vision',
            'o1-preview' => 'o1 Preview',
            'o1-mini' => 'o1 Mini',
            'o1' => 'o1',
            'o3-mini' => 'o3 Mini',
        ];

        public $openai_gpt35_models = [
            'gpt-3.5-turbo' => 'GPT-3.5 Turbo',
            'gpt-3.5-turbo-16k' => 'GPT-3.5 Turbo 16K',
            'gpt-3.5-turbo-instruct' => 'GPT-3.5 Turbo Instruct'
        ];

        // New list for o1-mini and o1-preview models
        public $o1_models = [
            'o1-preview' => 'o1 Preview',
            'o1-mini' => 'o1 Mini',
            'o1' => 'o1',
            'o3-mini' => 'o3 Mini',
        ];

        public $model_pricing = [
            'chatgpt-4o-latest' => 0.06,
            'gpt-4' => 0.06,
            'gpt-4o' => 0.015,
            'gpt-4o-mini' => 0.0003,
            'gpt-4-32k' => 0.12,
            'gpt-4-1106-preview' => 0.06,
            'gpt-4-turbo' => 0.03,
            'gpt-4-vision-preview' => 0.06,
            'o1-preview' => 0.06,
            'o1-mini' => 0.0003,
            'o1' => 0.015,
            'gpt-3.5-turbo' => 0.0015,
            'gpt-4-turbo-preview' => 0.03,
            'gpt-3.5-turbo-instruct' => 0.002,
            'gpt-3.5-turbo-16k' => 0.004,
            'text-davinci-003' => 0.02,
            'text-curie-001' => 0.002,
            'text-babbage-001' => 0.0005,
            'text-ada-001' => 0.0004,
            'gemini-pro' => 0.000375,
            'openrouter/auto' => 0,
            'nousresearch/nous-capybara-7b:free' => 0,
            'mistralai/mistral-7b-instruct:free' => 0,
            'openchat/openchat-7b:free' => 0,
            'gryphe/mythomist-7b:free' => 0,
            'undi95/toppy-m-7b:free' => 0,
            'openrouter/cinematika-7b:free' => 0,
            'google/gemma-7b-it:free' => 0,
            'meta-llama/llama-3-8b-instruct:free' => 0,
            'koboldai/psyfighter-13b-2' => 0.000002,
            'intel/neural-chat-7b' => 0.00001,
            'pygmalionai/mythalion-13b' => 0.00000225,
            'xwin-lm/xwin-lm-70b' => 0.0000075,
            'alpindale/goliath-120b' => 0.00001875,
            'neversleep/noromaid-20b' => 0.00000375,
            'gryphe/mythomist-7b' => 0.00000075,
            'sophosympatheia/midnight-rose-70b' => 0.000018,
            'sao10k/fimbulvetr-11b-v2' => 0.000001875,
            'neversleep/llama-3-lumimaid-8b' => 0.000001325625,
            'undi95/remm-slerp-l2-13b:extended' => 0.00000225,
            'gryphe/mythomax-l2-13b:extended' => 0.00000225,
            'meta-llama/llama-3-8b-instruct:extended' => 0.000001325625,
            'neversleep/llama-3-lumimaid-8b:extended' => 0.000001325625,
            'mancer/weaver' => 0.000004125,
            'nousresearch/nous-capybara-7b' => 0.00000036,
            'meta-llama/codellama-34b-instruct' => 0.00000144,
            'codellama/codellama-70b-instruct' => 0.00000162,
            'phind/phind-codellama-34b' => 0.00000144,
            'open-orca/mistral-7b-openorca' => 0.00000036,
            'teknium/openhermes-2-mistral-7b' => 0.00000036,
            'undi95/remm-slerp-l2-13b' => 0.00000054,
            'openrouter/cinematika-7b' => 0.00000036,
            '01-ai/yi-34b-chat' => 0.00000144,
            '01-ai/yi-34b' => 0.00000144,
            '01-ai/yi-6b' => 0.000000252,
            '01-ai/yi-large' => 0.00000072,
            'togethercomputer/stripedhyena-nous-7b' => 0.00000036,
            'togethercomputer/stripedhyena-hessian-7b' => 0.00000036,
            'mistralai/mixtral-8x7b' => 0.00000108,
            'nousresearch/nous-hermes-yi-34b' => 0.00000144,
            'nousresearch/nous-hermes-2-mixtral-8x7b-sft' => 0.00000108,
            'nousresearch/nous-hermes-2-mistral-7b-dpo' => 0.00000036,
            'meta-llama/llama-3-8b' => 0.00000036,
            'meta-llama/llama-3-70b' => 0.00000162,
            'meta-llama/llama-guard-2-8b' => 0.00000036,
            'databricks/dbrx-instruct' => 0.00000216,
            'allenai/olmo-7b-instruct' => 0.00000036,
            'snowflake/snowflake-arctic-instruct' => 0.00000432,
            'qwen/qwen-110b-chat' => 0.00000324,
            'qwen/qwen-32b-chat' => 0.00000144,
            'qwen/qwen-14b-chat' => 0.00000054,
            'qwen/qwen-7b-chat' => 0.00000036,
            'qwen/qwen-4b-chat' => 0.00000018,
            'qwen/qwen-2-72b-instruct' => 0.00000036,
            'mistralai/mixtral-8x7b-instruct:nitro' => 0.00000108,
            'openai/gpt-3.5-turbo' => 0.000002,
            'openai/gpt-3.5-turbo-0125' => 0.000002,
            'openai/gpt-3.5-turbo-1106' => 0.000003,
            'openai/gpt-3.5-turbo-0613' => 0.000003,
            'openai/gpt-3.5-turbo-0301' => 0.000003,
            'openai/gpt-3.5-turbo-16k' => 0.000007,
            'openai/gpt-4o' => 0.00002,
            'openai/gpt-4o-mini' => 0.00004,
            'openai/gpt-4o-mini-2024-07-18' => 0.00004,
            'openai/gpt-4o-2024-05-13' => 0.00002,
            'openai/gpt-4-turbo' => 0.00004,
            'openai/gpt-4-turbo-preview' => 0.00004,
            'openai/gpt-4-1106-preview' => 0.00004,
            'openai/gpt-4' => 0.00009,
            'openai/gpt-4-0314' => 0.00009,
            'openai/gpt-4-32k' => 0.00018,
            'openai/gpt-4-32k-0314' => 0.00018,
            'openai/gpt-4-vision-preview' => 0.00004,
            'openai/gpt-3.5-turbo-instruct' => 0.0000035,
            'google/palm-2-chat-bison' => 0.00000075,
            'google/palm-2-codechat-bison' => 0.00000075,
            'google/palm-2-chat-bison-32k' => 0.00000075,
            'google/palm-2-codechat-bison-32k' => 0.00000075,
            'google/gemini-pro' => 0.0000005,
            'google/gemini-pro-vision' => 0.0000005,
            'google/gemini-pro-1.5' => 0.00001,
            'google/gemini-flash-1.5' => 0.000001,
            'perplexity/llama-3-sonar-small-32k-chat' => 0.0000004,
            'perplexity/llama-3-sonar-small-32k-online' => 0.0000004,
            'perplexity/llama-3-sonar-large-32k-chat' => 0.000002,
            'perplexity/llama-3-sonar-large-32k-online' => 0.000002,
            'fireworks/firellava-13b' => 0.0000004,
            'anthropic/claude-3-opus' => 0.00009,
            'anthropic/claude-3-sonnet' => 0.000018,
            'anthropic/claude-3-haiku' => 0.0000015,
            'anthropic/claude-2' => 0.000032,
            'anthropic/claude-2.0' => 0.000032,
            'anthropic/claude-2.1' => 0.000032,
            'anthropic/claude-instant-1' => 0.0000032,
            'anthropic/claude-3-opus:beta' => 0.00009,
            'anthropic/claude-3-sonnet:beta' => 0.000018,
            'anthropic/claude-3-haiku:beta' => 0.0000015,
            'anthropic/claude-2:beta' => 0.000032,
            'anthropic/claude-2.0:beta' => 0.000032,
            'anthropic/claude-2.1:beta' => 0.000032,
            'anthropic/claude-instant-1:beta' => 0.0000032,
            'anthropic/claude-3.5-sonnet' => 0.000018,
            'anthropic/claude-3.5-sonnet:beta' => 0.000018,
            'openai/gpt-3.5' => 0.000002,
            'openai/gpt-3.5-1106' => 0.000003,
            'openai/gpt-3.5-16k' => 0.000007,
            'openai/gpt-4-1106' => 0.00004,
            'openai/gpt-4-32k-1106' => 0.00018,
            'openai/gpt-4-vision' => 0.00004,
            'openai/gpt-4-turbo-1106' => 0.00004,
            'openai/gpt-4-32k-1106-preview' => 0.00018,
        ];

        public $max_token_values = [
            'gpt-4' => 8192,
            'gpt-4o' => 8192,
            'gpt-4o-mini' => 8192,
            'gpt-4-32k'=> 32768,
            'gpt-3.5-turbo'=> 4096,
            'gpt-4-turbo'=> 4096,
            'gpt-4-vision-preview'=> 4096,
            'gpt-3.5-turbo-16k' => 16384,
            'gpt-3.5-turbo-instruct'=> 4096,
            'text-davinci-003'=> 4000,
            'text-curie-001'=> 2048,
            'text-babbage-001'=> 2048,
            'text-ada-001'=> 2048,
            'gemini-pro' => 2048,
            'gemini-1.0-pro' => 2048,
            'gemini-1.0-pro-001' => 2048,
            'gemini-1.0-pro-latest' => 2048,
            'gemini-1.0-pro-vision-latest' => 2048,
            'gemini-1.5-flash-latest' => 8191,
            'gemini-1.5-pro-latest' => 8191,
            'gemini-pro-vision' => 2048,
        ];
    }
}
if(!function_exists(__NAMESPACE__.'\wpaicg_util_core')){
    function wpaicg_util_core(){
        return WPAICG_Util::get_instance();
    }
}
