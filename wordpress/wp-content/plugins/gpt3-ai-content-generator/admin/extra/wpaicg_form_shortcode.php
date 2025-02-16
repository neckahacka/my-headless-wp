<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$wpaicg_items = array();
$wpaicg_icons = array();

$gpt4_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt4_models;
$gpt35_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt35_models;
$custom_models = get_option('wpaicg_custom_models', []);

if(file_exists(WPAICG_PLUGIN_DIR.'admin/data/gptcategories.json')){
    $wpaicg_file_content = file_get_contents(WPAICG_PLUGIN_DIR.'admin/data/gptcategories.json');
    $wpaicg_file_content = json_decode($wpaicg_file_content, true);
    if($wpaicg_file_content && is_array($wpaicg_file_content) && count($wpaicg_file_content)){
        foreach($wpaicg_file_content as $key=>$item){
            $wpaicg_categories[$key] = trim($item);
        }
    }
}
if(file_exists(WPAICG_PLUGIN_DIR.'admin/data/icons.json')){
    $wpaicg_file_content = file_get_contents(WPAICG_PLUGIN_DIR.'admin/data/icons.json');
    $wpaicg_file_content = json_decode($wpaicg_file_content, true);
    if($wpaicg_file_content && is_array($wpaicg_file_content) && count($wpaicg_file_content)){
        foreach($wpaicg_file_content as $key=>$item){
            $wpaicg_icons[$key] = trim($item);
        }
    }
}
if(file_exists(WPAICG_PLUGIN_DIR.'admin/data/gptforms.json')){
    $wpaicg_file_content = file_get_contents(WPAICG_PLUGIN_DIR.'admin/data/gptforms.json');
    $wpaicg_file_content = json_decode($wpaicg_file_content, true);
    if($wpaicg_file_content && is_array($wpaicg_file_content) && count($wpaicg_file_content)){
        foreach($wpaicg_file_content as $item){
            $wpaicg_items[] = $item;
        }
    }
}

global $wpdb;
if(isset($atts) && is_array($atts) && isset($atts['id']) && !empty($atts['id'])){
    $wpaicg_item_id = sanitize_text_field($atts['id']);
    $wpaicg_item_id = esc_attr($wpaicg_item_id);
    // Ensure the ID contains only numeric values
    if (!preg_match('/^\d+$/', $wpaicg_item_id)) {
        return 'Invalid form ID';
    }
    $wpaicg_item = false;
    $wpaicg_custom = isset($atts['custom']) && $atts['custom'] == 'yes' ? true : false;

    // Removed 'dans' and 'noanswer_text' from meta keys
    $wpaicg_meta_keys = array(
        'prompt','editor','fields','response','category','engine','max_tokens','temperature','top_p',
        'frequency_penalty','presence_penalty','stop','color','icon','bgcolor','header','embeddings','vectordb',
        'collections','pineconeindexes','suffix_text','suffix_position','embeddings_limit','use_default_embedding_model',
        'selected_embedding_model','selected_embedding_provider','ddraft','dclear','dnotice','generate_text','draft_text',
        'clear_text','stop_text','cnotice_text','download_text','ddownload','copy_button','copy_text','feedback_buttons',
        // new meta key to store the chosen AI provider
        'model_provider'
    );

    if(count($wpaicg_items) && !$wpaicg_custom){
        foreach ($wpaicg_items as $wpaicg_prompt){
            if(isset($wpaicg_prompt['id']) && $wpaicg_prompt['id'] == $wpaicg_item_id){
                $wpaicg_item = $wpaicg_prompt;
                $wpaicg_item['type'] = 'json';
            }
        }
    }
    if($wpaicg_custom){
        $sql = "SELECT p.ID as id,p.post_title as title, p.post_content as description";
        foreach($wpaicg_meta_keys as $wpaicg_meta_key){
            $sql .= ", (".$wpdb->prepare(
                    "SELECT ".$wpaicg_meta_key.".meta_value 
                     FROM ".$wpdb->postmeta." ".$wpaicg_meta_key." 
                     WHERE ".$wpaicg_meta_key.".meta_key=%s 
                     AND p.ID=".$wpaicg_meta_key.".post_id LIMIT 1",
                    'wpaicg_form_'.$wpaicg_meta_key
                ).") as ".$wpaicg_meta_key;
        }
        $sql .= $wpdb->prepare(
            " FROM ".$wpdb->posts." p 
              WHERE p.post_type = 'wpaicg_form' AND p.post_status='publish' 
              AND p.ID=%d 
              ORDER BY p.post_date DESC", 
            $wpaicg_item_id
        );
        $wpaicg_item = $wpdb->get_row($sql, ARRAY_A);
        if($wpaicg_item){
            $wpaicg_item['type'] = 'custom';
        }
    }
    if($wpaicg_item){
        $wpaicg_item_categories = array();
        $wpaicg_item_categories_name = array();
        if(isset($wpaicg_item['category']) && !empty($wpaicg_item['category'])){
            $wpaicg_item_categories = array_map('trim', explode(',', $wpaicg_item['category']));
        }

        // --- Updated dashicons approach ---
        // Default to a dashicons fallback in case none is found or configured
        $wpaicg_icon_class = 'dashicons dashicons-admin-generic';
        if(isset($wpaicg_item['icon']) && !empty($wpaicg_item['icon']) && isset($wpaicg_icons[$wpaicg_item['icon']]) && !empty($wpaicg_icons[$wpaicg_item['icon']])){
            $wpaicg_icon_class = $wpaicg_icons[$wpaicg_item['icon']];
        }
        $wpaicg_icon_color = isset($wpaicg_item['color']) && !empty($wpaicg_item['color']) ? $wpaicg_item['color'] : '#19c37d';

        $wpaicg_engine = isset($wpaicg_item['engine']) && !empty($wpaicg_item['engine']) ? $wpaicg_item['engine'] : $this->wpaicg_engine;
        $wpaicg_max_tokens = isset($wpaicg_item['max_tokens']) && !empty($wpaicg_item['max_tokens']) ? $wpaicg_item['max_tokens'] : $this->wpaicg_max_tokens;
        $wpaicg_temperature = isset($wpaicg_item['temperature']) && !empty($wpaicg_item['temperature']) ? $wpaicg_item['temperature'] : $this->wpaicg_temperature;
        $wpaicg_top_p = isset($wpaicg_item['top_p']) && !empty($wpaicg_item['top_p']) ? $wpaicg_item['top_p'] : $this->wpaicg_top_p;
        $wpaicg_frequency_penalty = isset($wpaicg_item['frequency_penalty']) && !empty($wpaicg_item['frequency_penalty']) ? $wpaicg_item['frequency_penalty'] : $this->wpaicg_frequency_penalty;
        $wpaicg_presence_penalty = isset($wpaicg_item['presence_penalty']) && !empty($wpaicg_item['presence_penalty']) ? $wpaicg_item['presence_penalty'] : $this->wpaicg_presence_penalty;
        $wpaicg_stop = isset($wpaicg_item['stop']) && !empty($wpaicg_item['stop']) ? $wpaicg_item['stop'] : $this->wpaicg_stop;
        $wpaicg_generate_text = isset($wpaicg_item['generate_text']) && !empty($wpaicg_item['generate_text']) ? $wpaicg_item['generate_text'] : esc_html__('Generate','gpt3-ai-content-generator');
        $wpaicg_suffix_text = isset($wpaicg_item['suffix_text']) && !empty($wpaicg_item['suffix_text']) ? $wpaicg_item['suffix_text'] : 'Context:';
        $wpaicg_use_default_embedding_model = isset($wpaicg_item['use_default_embedding_model']) && !empty($wpaicg_item['use_default_embedding_model']) ? $wpaicg_item['use_default_embedding_model'] : 'yes';
        $selected_embedding_model = isset($wpaicg_item['selected_embedding_model']) && !empty($wpaicg_item['selected_embedding_model']) ? $wpaicg_item['selected_embedding_model'] : '';
        $selected_embedding_provider = isset($wpaicg_item['selected_embedding_provider']) && !empty($wpaicg_item['selected_embedding_provider']) ? $wpaicg_item['selected_embedding_provider'] : '';
        $wpaicg_draft_text = isset($wpaicg_item['draft_text']) && !empty($wpaicg_item['draft_text']) ? $wpaicg_item['draft_text'] : esc_html__('Save Draft','gpt3-ai-content-generator');
        $wpaicg_clear_text = isset($wpaicg_item['clear_text']) && !empty($wpaicg_item['clear_text']) ? $wpaicg_item['clear_text'] : esc_html__('Clear','gpt3-ai-content-generator');
        $wpaicg_stop_text = isset($wpaicg_item['stop_text']) && !empty($wpaicg_item['stop_text']) ? $wpaicg_item['stop_text'] : esc_html__('Stop','gpt3-ai-content-generator');
        $wpaicg_cnotice_text = isset($wpaicg_item['cnotice_text']) && !empty($wpaicg_item['cnotice_text']) ? $wpaicg_item['cnotice_text'] : esc_html__('Please register to save your result','gpt3-ai-content-generator');
        $wpaicg_download_text = isset($wpaicg_item['download_text']) && !empty($wpaicg_item['download_text']) ? $wpaicg_item['download_text'] : __('Download','gpt3-ai-content-generator');
        $wpaicg_copy_text = isset($wpaicg_item['copy_text']) && !empty($wpaicg_item['copy_text']) ? $wpaicg_item['copy_text'] : __('Copy','gpt3-ai-content-generator');
        $wpaicg_stop_lists = '';
        if(is_array($wpaicg_stop) && count($wpaicg_stop)){
            foreach($wpaicg_stop as $item_stop){
                if($item_stop === "\n"){
                    $item_stop = '\n';
                }
                $wpaicg_stop_lists = empty($wpaicg_stop_lists) ? $item_stop : ','.$item_stop;
            }
        }
        if(count($wpaicg_item_categories)){
            foreach($wpaicg_item_categories as $wpaicg_item_category){
                if(isset($wpaicg_categories[$wpaicg_item_category]) && !empty($wpaicg_categories[$wpaicg_item_category])){
                    $wpaicg_item_categories_name[] = $wpaicg_categories[$wpaicg_item_category];
                }
            }
        }
        if(is_user_logged_in()){
            wp_enqueue_editor();
        }
        $wpaicg_show_setting = false;
        if(isset($atts['settings']) && $atts['settings'] == 'yes'){
            $wpaicg_show_setting = true;
        }
        ?>
        <style>
        /* Container for each prompt form */
        .wpaicg-prompt-item {
            background: #f9f9f9;
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            font-family: Arial, sans-serif;
        }

        /* Header section: icon + title + description */
        .wpaicg-prompt-head {
            display: flex;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
        }

        .wpaicg-prompt-icon {
            background: #19c37d; /* Adjust to your preferred accent color */
            color: #fff;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wpaicg-prompt-icon .dashicons {
            color: #fff; /* ensure icon color remains white (or suitable) */
        }

        .wpaicg-prompt-head strong {
            font-size: 20px;
            margin-bottom: 5px;
            display: block;
            color: #333;
            font-weight: 600;
        }

        .wpaicg-prompt-head p {
            margin: 0;
            color: #666;
        }

        /* Main content area within the form container */
        .wpaicg-prompt-content {
            margin-top: 15px;
        }

        /* Grid layouts: tweak to your preference */
        .wpaicg-grid-three {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .wpaicg-grid-2 {
            grid-column: span 2/span 2;
        }

        .wpaicg-grid-1 {
            grid-column: span 1/span 1;
        }

        /* Form fields and labels */
        .wpaicg-form-field,
        .wpaicg-prompt-field {
            margin-bottom: 15px;
        }

        .wpaicg-form-field label strong,
        .wpaicg-prompt-field strong {
            font-size: 14px;
            margin-bottom: 6px;
            color: #555;
        }

        .wpaicg-prompt-field > strong > small {
            font-size: 12px;
            font-weight: normal;
            display: block;
            color: #888;
            margin-top: 4px;
        }

        /* Inputs, textareas, selects */
        .wpaicg-prompt-field input,
        .wpaicg-prompt-field select,
        .wpaicg-form-field input,
        .wpaicg-form-field select,
        .wpaicg-form-field textarea,
        .wpaicg-prompt-field textarea {
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 6px;
            padding: 8px;
            font-size: 14px;
            color: #333;
            transition: border-color 0.2s;
            box-sizing: border-box;
        }

        .wpaicg-prompt-field input:focus,
        .wpaicg-prompt-field select:focus,
        .wpaicg-form-field input:focus,
        .wpaicg-form-field select:focus,
        .wpaicg-form-field textarea:focus,
        .wpaicg-prompt-field textarea:focus {
            border-color: #19c37d;
            outline: none;
        }

        textarea {
            resize: vertical;
        }

        /* Generate and Stop buttons */
        .wpaicg-prompt-flex-center {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        /* Primary button styles */
        .wpaicg-button {
            padding: 8px 12px;
            background: #19c37d;
            border: none;
            border-radius: 6px;
            color: #fff;
            font-size: 14px;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
            display: inline-flex;
            align-items: center;
            gap: 0.4em;
        }

        .wpaicg-button:hover:not(:disabled),
        .wpaicg-button:focus:not(:disabled) {
            background-color: #17ab6f;
            transform: translateY(-2px);
        }

        .wpaicg-button:disabled {
            background: #c8c8c8;
            cursor: not-allowed;
            transform: none;
        }

        /* Loader animation inside the button */
        .wpaicg-loader {
            width: 18px;
            height: 18px;
            border: 2px solid #fff;
            border-bottom-color: transparent;
            border-radius: 50%;
            display: inline-block;
            box-sizing: border-box;
            animation: wpaicg_rotation 1s linear infinite;
        }

        @keyframes wpaicg_rotation {
            0%   { transform: rotate(0deg);   }
            100% { transform: rotate(360deg); }
        }

        /* The result text area or DIV */
        .wpaicg-prompt-result {
            width: 100%;
            min-height: 200px;
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 10px;
            margin-top: 15px;
            box-sizing: border-box;
            color: #333;
            font-size: 14px;
        }

        /* Additional controls below the result (Save Draft, Clear, etc.) */
        .wpaicg-prompt-save-result {
            margin-top: 15px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
        }

        .wpaicg-prompt-item .wpaicg-prompt-response {
            position: absolute;
            background: #333;
            color: #fff;
            border: 1px solid #444;
            border-radius: 4px;
            padding: 8px;
            width: 300px;
            bottom: calc(100% + 5px);
            left: 0;
            z-index: 99;
            display: none;
            font-size: 13px;
        }


        .wpaicg-prompt-response:after,
        .wpaicg-prompt-response:before {
            content: "";
            position: absolute;
            left: 20px;
            border: solid transparent;
            height: 0;
            width: 0;
            pointer-events: none;
        }

        .wpaicg-prompt-response:before {
            border-color: rgba(68, 68, 68, 0);
            border-bottom-color: #444;
            border-width: 7px;
            top: -14px;
        }

        .wpaicg-prompt-response:after {
            border-color: rgba(51, 51, 51, 0);
            border-bottom-color: #333;
            border-width: 6px;
            top: -12px;
        }

        /* Feedback Modal Overlay */
        .wpaicg_feedbackModal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            z-index: 9998;
            display: none; 
        }

        /* Feedback Modal Container */
        #wpaicg_feedbackModal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            z-index: 9999;
            width: 90%;
            max-width: 600px;
            transform: translate(-50%, -50%);
        }

        /* Feedback Modal Content */
        .wpaicg_feedbackModal-content {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.2);
        }

        #wpaicg_feedbackModal h3 {
            margin-top: 0;
            margin-bottom: 15px;
            color: #333;
            font-size: 18px;
            font-weight: 600;
        }

        #wpaicg_feedbackModal textarea {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            font-size: 14px;
            resize: vertical;
            box-sizing: border-box;
        }

        /* Feedback Modal Buttons */
        .wpaicg_button-group {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        #wpaicg_feedbackModal button {
            padding: 8px 12px;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            transition: background-color 0.3s, transform 0.1s;
        }

        #wpaicg_feedbackModal #wpaicg_submitFeedback {
            background-color: #19c37d;
            color: #fff;
        }

        #wpaicg_feedbackModal #wpaicg_submitFeedback:hover {
            background-color: #17ab6f;
            transform: translateY(-2px);
        }

        #wpaicg_feedbackModal #closeFeedbackModal {
            background-color: #e0e0e0;
            color: #333;
        }

        #wpaicg_feedbackModal #closeFeedbackModal:hover {
            background-color: #cacaca;
            transform: translateY(-2px);
        }

        /* Checkbox/radio styling */
        .wpaicg-checkbox-group {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin: 5px 0;
        }
        .wpaicg-checkbox-label {
            display: inline-flex;
            align-items: center;
            cursor: pointer;
            position: relative;
            user-select: none;
            padding-left: 26px;
            font-size: 14px;
            color: #555;
        }
        .wpaicg-checkbox-label input {
            position: absolute;
            opacity: 0;
            cursor: pointer;
            height: 0;
            width: 0;
        }
        .wpaicg-checkbox-custom {
            position: absolute;
            left: 0;
            top: 2px;
            height: 18px;
            width: 18px;
            background-color: #fff;
            border: 2px solid #ccc;
            border-radius: 2px; 
            box-sizing: border-box;
        }
        .wpaicg-checkbox-label input:checked ~ .wpaicg-checkbox-custom {
            background-color: #19c37d;
            border-color: #19c37d;
        }
        .wpaicg-checkbox-custom:after {
            content: "";
            position: absolute;
            display: none;
        }
        .wpaicg-checkbox-label input:checked ~ .wpaicg-checkbox-custom:after {
            display: block;
        }
        .wpaicg-checkbox-label .wpaicg-checkbox-custom:after {
            left: 5px;
            top: 0;
            width: 5px;
            height: 11px;
            border: solid #fff;
            border-width: 0 2px 2px 0;
            transform: rotate(45deg);
        }
        .wpaicg_checkbox_text {
            margin-left: 5px;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .wpaicg-grid-three {
                grid-template-columns: 1fr;
            }
            
            .wpaicg-button {
                font-size: 15px;
                padding: 10px;
            }
            
            .wpaicg_button-group {
                flex-direction: column;
                gap: 10px;
            }

            #wpaicg_feedbackModal {
                width: 90%;
            }
        }
        </style>
        <?php
        $wpaicg_fields = [];
        if($wpaicg_item['fields'] !== '') {
            if(is_string($wpaicg_item['fields'])) {
                if (strpos($wpaicg_item['fields'], '\"') !== false) {
                    $wpaicg_item['fields'] = str_replace('\"', '&quot;', $wpaicg_item['fields']);
                }
                if (strpos($wpaicg_item['fields'], "\'") !== false) {
                    $wpaicg_item['fields'] = str_replace('\\', '', $wpaicg_item['fields']);
                }
            }
            $wpaicg_fields = $wpaicg_item['type'] == 'custom' 
                ? json_decode($wpaicg_item['fields'],true) 
                : $wpaicg_item['fields'];
        }
        $wpaicg_response_type = isset($wpaicg_item['editor']) && $wpaicg_item['editor'] == 'div' ? 'div' : 'textarea';

        // KSES defaults for safety
        $kses_defaults = wp_kses_allowed_html( 'post' );
        $allowed_tags = $kses_defaults;

        $randomFormID = wp_rand(100000,999999);
        ?>
        <div class="wpaicg-prompt-item wpaicg-playground-shortcode" style="<?php echo isset($wpaicg_item['bgcolor']) && !empty($wpaicg_item['bgcolor']) ? 'background-color:'.esc_html($wpaicg_item['bgcolor']):'';?>">
            <div class="wpaicg-prompt-head" style="<?php echo isset($wpaicg_item['header']) && $wpaicg_item['header'] == 'no' ? 'display: none;':'';?>">
                <div class="wpaicg-prompt-icon" style="background: <?php echo esc_html($wpaicg_icon_color)?>">
                    <span class="<?php echo esc_attr($wpaicg_icon_class); ?>"></span>
                </div>
                <div class="">
                    <strong><?php echo isset($wpaicg_item['title']) && !empty($wpaicg_item['title']) ? esc_html($wpaicg_item['title']) : ''?></strong>
                    <?php
                    if(isset($wpaicg_item['description']) && !empty($wpaicg_item['description'])){
                        echo '<p>'.esc_html($wpaicg_item['description']).'</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="wpaicg-prompt-content">
                <form data-source="form" data-id="<?php echo esc_attr($randomFormID)?>" method="post" action="" class="wpaicg-prompt-form" id="wpaicg-prompt-form">
                    <?php
                    if($wpaicg_show_setting):
                    ?>
                    <div class="wpaicg-grid-three">
                        <div class="wpaicg-grid-2">
                            <?php
                            endif;
                            ?>
                            <div class="wpaicg-mb-10">
                                <?php
                                if($wpaicg_fields && is_array($wpaicg_fields) && count($wpaicg_fields)){
                                    foreach($wpaicg_fields as $key=>$wpaicg_field){
                                        ?>
                                        <div class="wpaicg-form-field">
                                            <label><strong><?php echo esc_html(@$wpaicg_field['label'])?></strong></label><br>
                                            <?php
                                            if($wpaicg_field['type'] == 'select'){
                                                $wpaicg_field_options = [];
                                                if(isset($wpaicg_field['options'])){
                                                    if($wpaicg_item['type'] == 'custom'){
                                                        $wpaicg_field_options = explode("|", $wpaicg_field['options']);
                                                    } else {
                                                        $wpaicg_field_options = $wpaicg_field['options'];
                                                    }
                                                }
                                                ?>
                                                <select 
                                                    id="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    class="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    name="<?php echo esc_html($wpaicg_field['id'])?>" 
                                                    data-label="<?php echo esc_html(@$wpaicg_field['label'])?>" 
                                                    data-type="<?php echo esc_html(@$wpaicg_field['type'])?>" 
                                                    data-min="<?php echo isset($wpaicg_field['min']) ? esc_html($wpaicg_field['min']) : ''?>" 
                                                    data-max="<?php echo isset($wpaicg_field['max']) ? esc_html($wpaicg_field['max']) : ''?>"
                                                >
                                                    <?php
                                                    foreach($wpaicg_field_options as $wpaicg_field_option){
                                                        echo '<option value="'.esc_html($wpaicg_field_option).'">'.esc_html($wpaicg_field_option).'</option>';
                                                    }
                                                    ?>
                                                </select>
                                                <?php
                                            }
                                            elseif($wpaicg_field['type'] == 'checkbox' || $wpaicg_field['type'] == 'radio'){
                                                $wpaicg_field_options = [];
                                                if(isset($wpaicg_field['options'])){
                                                    if($wpaicg_item['type'] == 'custom'){
                                                        $wpaicg_field_options = explode("|", $wpaicg_field['options']);
                                                    } else {
                                                        $wpaicg_field_options = $wpaicg_field['options'];
                                                    }
                                                }
                                                ?>
                                                <div 
                                                    id="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    class="wpaicg-form-field-<?php echo esc_html($key)?> wpaicg-checkbox-group"
                                                >
                                                    <?php
                                                    foreach($wpaicg_field_options as $wpaicg_field_option):
                                                    ?>
                                                    <label class="wpaicg-checkbox-label">
                                                        <input 
                                                            name="<?php echo esc_html($wpaicg_field['id']).($wpaicg_field['type'] == 'checkbox' ? '[]':'')?>"
                                                            value="<?php echo esc_html($wpaicg_field_option)?>" 
                                                            type="<?php echo esc_html($wpaicg_field['type'])?>"
                                                        >
                                                        <span class="wpaicg-checkbox-custom"></span>
                                                        <span class="wpaicg-checkbox-text"><?php echo esc_html($wpaicg_field_option)?></span>
                                                    </label>
                                                    <?php
                                                    endforeach;
                                                    ?>
                                                </div>
                                                <?php
                                            }
                                            elseif($wpaicg_field['type'] == 'textarea'){
                                                ?>
                                                <textarea
                                                    <?php echo isset($wpaicg_field['rows']) && !empty($wpaicg_field['rows']) ? ' rows="'.esc_html($wpaicg_field['rows']).'"': '';?> 
                                                    <?php echo isset($wpaicg_field['cols']) && !empty($wpaicg_field['cols']) ? ' cols="'.esc_html($wpaicg_field['cols']).'"': '';?> 
                                                    id="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    class="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    name="<?php echo esc_html($wpaicg_field['id'])?>" 
                                                    data-label="<?php echo esc_html(@$wpaicg_field['label'])?>" 
                                                    data-type="<?php echo esc_html(@$wpaicg_field['type'])?>" 
                                                    data-min="<?php echo isset($wpaicg_field['min']) ? esc_html($wpaicg_field['min']) : ''?>" 
                                                    data-max="<?php echo isset($wpaicg_field['max']) ? esc_html($wpaicg_field['max']) : ''?>"
                                                ></textarea>
                                                <?php
                                            }
                                            elseif($wpaicg_field['type'] == 'fileupload') {
                                                // Front-end usage: file input + hidden input
                                                // We store the file content in the hidden input after reading it on the client side
                                                $fileTypes = isset($wpaicg_field['file_types']) ? esc_attr($wpaicg_field['file_types']) : 'txt,csv,doc,docx';
                                                ?>
                                                <input 
                                                    type="file" 
                                                    id="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    class="wpaicg-form-field-<?php echo esc_html($key)?> wpaicg-fileupload-input" 
                                                    name="<?php echo esc_html($wpaicg_field['id'])?>__fileupload" 
                                                    data-label="<?php echo esc_html(@$wpaicg_field['label'])?>" 
                                                    data-type="fileupload"
                                                    data-filetypes="<?php echo $fileTypes;?>"
                                                />
                                                <input 
                                                    type="hidden" 
                                                    name="<?php echo esc_html($wpaicg_field['id'])?>" 
                                                    value="" 
                                                    id="wpaicg-fileupload-hidden-<?php echo esc_html($key)?>" 
                                                />
                                                <?php
                                            }
                                            else{
                                                ?>
                                                <input 
                                                    id="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    class="wpaicg-form-field-<?php echo esc_html($key)?>" 
                                                    name="<?php echo esc_html($wpaicg_field['id'])?>" 
                                                    data-label="<?php echo esc_html(@$wpaicg_field['label'])?>" 
                                                    data-type="<?php echo esc_html(@$wpaicg_field['type'])?>" 
                                                    type="<?php echo esc_html(@$wpaicg_field['type'])?>" 
                                                    data-min="<?php echo isset($wpaicg_field['min']) ? esc_html($wpaicg_field['min']) : ''?>" 
                                                    data-max="<?php echo isset($wpaicg_field['max']) ? esc_html($wpaicg_field['max']) : ''?>"
                                                >
                                                <?php
                                            }
                                            ?>
                                        </div>
                                        <?php
                                    }
                                }
                                ?>
                                <div class="wpaicg-prompt-flex-center">
                                    <!-- Hidden field to fix # of answers to 1 -->
                                    <input 
                                        type="hidden" 
                                        class="wpaicg-prompt-max-lines" 
                                        id="wpaicg-prompt-max-lines" 
                                        value="1"
                                    />
                                    <button style="margin-left:0" class="wpaicg-button wpaicg-generate-button" id="wpaicg-generate-button">
                                        <?php echo esc_html($wpaicg_generate_text);?>
                                    </button>
                                    &nbsp;
                                    <button 
                                        data-id="<?php echo esc_html($randomFormID)?>" 
                                        type="button" 
                                        class="wpaicg-button wpaicg-prompt-stop-generate" 
                                        id="wpaicg-prompt-stop-generate" 
                                        style="display: none"
                                    >
                                        <?php echo esc_html($wpaicg_stop_text);?>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-5">
                                <?php
                                if($wpaicg_response_type == 'textarea'):
                                    if(is_user_logged_in()){
                                        wp_editor('','wpaicg-prompt-result-'.$randomFormID, array(
                                            'media_buttons' => true, 
                                            'textarea_name' => 'wpaicg-prompt-result-'.$randomFormID
                                        ));
                                    }
                                    else{
                                        ?>
                                        <textarea 
                                            class="wpaicg-prompt-result-<?php echo esc_html($randomFormID)?>" 
                                            id="wpaicg-prompt-result-<?php echo esc_html($randomFormID)?>" 
                                            rows="12"
                                        ></textarea>
                                        <?php
                                        if(isset($wpaicg_item['dnotice']) && $wpaicg_item['dnotice'] == 'no'):
                                        else:
                                            ?>
                                        <a style="font-size: 13px;" href="<?php echo site_url('wp-login.php?action=register')?>">
                                            <?php echo esc_html($wpaicg_cnotice_text)?>
                                        </a>
                                        <?php
                                        endif;
                                        ?>
                                    <?php
                                    }
                                else:
                                    echo '<div id="wpaicg-prompt-result-'.esc_html($randomFormID).'"></div>';
                                    if(!is_user_logged_in()){
                                        if(isset($wpaicg_item['dnotice']) && $wpaicg_item['dnotice'] == 'no'){

                                        } else {
                                            ?>
                                            <a style="font-size: 13px;" href="<?php echo site_url('wp-login.php?action=register')?>">
                                                <?php echo esc_html($wpaicg_cnotice_text)?>
                                            </a>
                                            <?php
                                        }
                                    }
                                endif;
                                ?>
                            </div>
                            <div class="wpaicg-prompt-save-result" id="wpaicg-prompt-save-result" style="display: none;margin-top: 10px;">
                                <?php
                                if(is_user_logged_in()):
                                    if(isset($wpaicg_item['ddraft']) && $wpaicg_item['ddraft'] == 'no'):
                                    else:
                                ?>
                                <button 
                                    data-id="<?php echo esc_html($randomFormID)?>" 
                                    type="button" 
                                    class="wpaicg-button wpaicg-prompt-save-draft" 
                                    id="wpaicg-prompt-save-draft"
                                >
                                    <?php echo esc_html($wpaicg_draft_text);?>
                                </button>
                                <?php
                                    endif;
                                endif;

                                if(isset($wpaicg_item['dclear']) && $wpaicg_item['dclear'] == 'no'):
                                else:
                                ?>
                                <button 
                                    data-id="<?php echo esc_html($randomFormID)?>" 
                                    type="button" 
                                    class="wpaicg-button wpaicg-prompt-clear" 
                                    id="wpaicg-prompt-clear"
                                >
                                    <?php echo esc_html($wpaicg_clear_text);?>
                                </button>
                                <?php
                                endif;

                                if(isset($wpaicg_item['ddownload']) && $wpaicg_item['ddownload'] == 'no'):
                                else:
                                ?>
                                <button 
                                    data-id="<?php echo esc_html($randomFormID)?>" 
                                    type="button" 
                                    class="wpaicg-button wpaicg-prompt-download"
                                >
                                    <?php echo esc_html($wpaicg_download_text);?>
                                </button>
                                <?php
                                endif;

                                if (isset($wpaicg_item['copy_button']) && $wpaicg_item['copy_button'] === 'yes'):
                                ?>
                                <button 
                                    data-id="<?php echo esc_html($randomFormID)?>" 
                                    type="button" 
                                    class="wpaicg-button wpaicg-prompt-copy_button" 
                                    id="wpaicg-prompt-copy_button"
                                >
                                    <?php echo esc_html($wpaicg_copy_text);?>
                                </button>
                                <?php
                                endif;
                                
                                if(isset($wpaicg_item['feedback_buttons']) && $wpaicg_item['feedback_buttons'] === 'yes'):
                                ?>
                                <button 
                                    data-id="<?php echo esc_html($randomFormID)?>" 
                                    type="button" 
                                    class="wpaicg-button wpaicg-prompt-thumbs_up" 
                                    id="wpaicg-prompt-thumbs_up"
                                >
                                    👍
                                </button>
                                <button 
                                    data-id="<?php echo esc_html($randomFormID)?>" 
                                    type="button" 
                                    class="wpaicg-button wpaicg-prompt-thumbs_down" 
                                    id="wpaicg-prompt-thumbs_down"
                                >
                                    👎
                                </button>
                                <?php
                                endif;
                                ?>
                            </div>

                            <?php
                            if($wpaicg_show_setting):
                            ?>
                        </div>
                        <div class="wpaicg-grid-1">
                            <?php
                            endif;
                            ?>
                            <div class="wpaicg-mb-10 wpaicg-prompt-item" style="<?php echo !$wpaicg_show_setting ? 'display:none': ''?>">
                                <h3><?php echo esc_html__('Settings','gpt3-ai-content-generator')?></h3>
                                <?php
                                // Provider meta
                                $wpaicg_model_provider = isset($wpaicg_item['model_provider']) && !empty($wpaicg_item['model_provider'])
                                    ? $wpaicg_item['model_provider'] : 'OpenAI';

                                // Additional data for optional provider-based engines
                                $azure_deployment_name = get_option('wpaicg_azure_deployment', '');
                                $wpaicg_google_model_list = get_option('wpaicg_google_model_list', ['gemini-pro']);
                                $wpaicg_google_default_model = get_option('wpaicg_google_default_model', 'gemini-pro');
                                $openrouter_models = get_option('wpaicg_openrouter_model_list', []);
                                $wpaicg_openrouter_default_model = get_option('wpaicg_openrouter_default_model', 'openrouter/auto');
                                ?>

                                <!-- AI Provider -->
                                <div class="wpaicg-prompt-field wpaicg-prompt-provider">
                                    <strong><?php echo esc_html__('AI Provider','gpt3-ai-content-generator')?>: </strong>
                                    <select name="model_provider">
                                        <option value="OpenAI" <?php selected($wpaicg_model_provider, 'OpenAI'); ?>>OpenAI</option>
                                        <option value="OpenRouter" <?php selected($wpaicg_model_provider, 'OpenRouter'); ?>>OpenRouter</option>
                                        <option value="Google" <?php selected($wpaicg_model_provider, 'Google'); ?>>Google</option>
                                        <option value="Azure" <?php selected($wpaicg_model_provider, 'Azure'); ?>>Azure</option>
                                    </select>
                                </div>

                                <div class="wpaicg-prompt-field wpaicg-prompt-engine">
                                    <strong><?php echo esc_html__('AI Engine','gpt3-ai-content-generator')?>: </strong>
                                    <?php if($wpaicg_model_provider === 'OpenAI'): ?>
                                        <select name="engine">
                                            <optgroup label="GPT-4">
                                                <?php foreach ($gpt4_models as $value => $display_name): ?>
                                                    <option 
                                                        <?php echo $value == $wpaicg_engine ? ' selected':'' ?> 
                                                        value="<?php echo esc_attr($value); ?>"
                                                    >
                                                        <?php echo esc_html($display_name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="GPT-3.5">
                                                <?php foreach ($gpt35_models as $value => $display_name): ?>
                                                    <option 
                                                        <?php echo $value == $wpaicg_engine ? ' selected':'' ?> 
                                                        value="<?php echo esc_attr($value); ?>"
                                                    >
                                                        <?php echo esc_html($display_name); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Custom Models">
                                                <?php foreach ($custom_models as $model): ?>
                                                    <option 
                                                        <?php echo $model == $wpaicg_engine ? ' selected':'' ?> 
                                                        value="<?php echo esc_attr($model); ?>"
                                                    >
                                                        <?php echo esc_html($model); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    <?php elseif ($wpaicg_model_provider === 'Google'): ?>
                                        <!-- Display dropdown for Google AI -->
                                        <select name="engine">
                                            <optgroup label="Google Models">
                                                <?php foreach ($wpaicg_google_model_list as $model): ?>
                                                    <option 
                                                        value="<?php echo esc_attr($model); ?>"
                                                        <?php selected($model, $wpaicg_engine); ?>
                                                    >
                                                        <?php echo esc_html($model); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    <?php elseif ($wpaicg_model_provider === 'OpenRouter'): ?>
                                        <!-- Display dropdown for OpenRouter -->
                                        <?php
                                        $openrouter_grouped_models = [];
                                        foreach ($openrouter_models as $openrouter_model) {
                                            $openrouter_provider = explode('/', $openrouter_model['id'])[0];
                                            if (!isset($openrouter_grouped_models[$openrouter_provider])) {
                                                $openrouter_grouped_models[$openrouter_provider] = [];
                                            }
                                            $openrouter_grouped_models[$openrouter_provider][] = $openrouter_model;
                                        }
                                        ksort($openrouter_grouped_models);
                                        ?>
                                        <select name="engine">
                                            <?php
                                            foreach ($openrouter_grouped_models as $openrouter_provider => $models): ?>
                                                <optgroup label="<?php echo esc_attr($openrouter_provider); ?>">
                                                    <?php
                                                    usort($models, function($a, $b) {
                                                        return strcmp($a["name"], $b["name"]);
                                                    });
                                                    foreach ($models as $omodel): ?>
                                                        <option 
                                                            value="<?php echo esc_attr($omodel['id']); ?>"
                                                            <?php selected($omodel['id'], $wpaicg_engine); ?>
                                                        >
                                                            <?php echo esc_html($omodel['name']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    <?php else:?>
                                        <!-- Azure case -->
                                        <input 
                                            type="text"
                                            name="engine"
                                            readonly
                                            value="<?php echo esc_html($wpaicg_engine ? $wpaicg_engine : $azure_deployment_name); ?>"
                                        />
                                    <?php endif;?>
                                </div>

                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__('Token','gpt3-ai-content-generator')?>: </strong>
                                    <input 
                                        id="wpaicg-prompt-max_tokens" 
                                        class="wpaicg-prompt-max_tokens" 
                                        name="max_tokens" 
                                        type="text" 
                                        value="<?php echo esc_html($wpaicg_max_tokens);?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__('Temp','gpt3-ai-content-generator')?>: </strong>
                                    <input 
                                        id="wpaicg-prompt-temperature" 
                                        class="wpaicg-prompt-temperature" 
                                        name="temperature" 
                                        type="text" 
                                        value="<?php echo esc_html($wpaicg_temperature)?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__('TP','gpt3-ai-content-generator')?>: </strong>
                                    <input 
                                        id="wpaicg-prompt-top_p" 
                                        class="wpaicg-prompt-top_p" 
                                        type="text" 
                                        name="top_p" 
                                        value="<?php echo esc_html($wpaicg_top_p)?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__('FP','gpt3-ai-content-generator')?>: </strong>
                                    <input 
                                        id="wpaicg-prompt-frequency_penalty" 
                                        class="wpaicg-prompt-frequency_penalty" 
                                        name="frequency_penalty" 
                                        type="text" 
                                        value="<?php echo esc_html($wpaicg_frequency_penalty)?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__('PP','gpt3-ai-content-generator')?>: </strong>
                                    <input 
                                        id="wpaicg-prompt-presence_penalty" 
                                        class="wpaicg-prompt-presence_penalty" 
                                        name="presence_penalty" 
                                        type="text" 
                                        value="<?php echo esc_html($wpaicg_presence_penalty)?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__('Stop','gpt3-ai-content-generator')?>:
                                        <small><?php echo esc_html__('separate by commas','gpt3-ai-content-generator')?></small>
                                    </strong>
                                    <input 
                                        class="wpaicg-prompt-stop" 
                                        id="wpaicg-prompt-stop" 
                                        type="text" 
                                        name="stop" 
                                        value="<?php echo esc_html($wpaicg_stop_lists)?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <input 
                                        id="wpaicg-prompt-post_title" 
                                        class="wpaicg-prompt-post_title" 
                                        type="hidden" 
                                        name="post_title" 
                                        value="<?php echo esc_html($wpaicg_item['title'])?>"
                                    >
                                </div>
                                <!-- get item id -->
                                <div class="wpaicg-prompt-field">
                                    <input 
                                        id="wpaicg-prompt-id" 
                                        class="wpaicg-prompt-id" 
                                        type="hidden" 
                                        name="id" 
                                        value="<?php echo esc_html($wpaicg_item_id)?>"
                                    >
                                </div>
                            </div>
                            <?php
                            if($wpaicg_show_setting):
                            ?>
                        </div>
                    </div>
                    <?php
                    endif;
                    ?>
                </form>
            </div>
        </div>
        <?php
        $textareaID = 'feedbackText_' . $randomFormID;
        ?>
        <div class="wpaicg_feedbackModal-overlay"></div>
        <div id="wpaicg_feedbackModal">
            <div class="wpaicg_feedbackModal-content">
                <h3><?php echo esc_html__('Provide additional feedback', 'gpt3-ai-content-generator'); ?></h3>
                <textarea 
                    id="<?php echo esc_html($textareaID); ?>" 
                    rows="6" 
                    placeholder="<?php echo esc_attr__('Enter your feedback here...', 'gpt3-ai-content-generator'); ?>"
                ></textarea>
                <div class="wpaicg_button-group">
                    <button id="wpaicg_submitFeedback">
                        <?php echo esc_html__('Submit', 'gpt3-ai-content-generator'); ?>
                    </button>
                    <button id="closeFeedbackModal">
                        <?php echo esc_html__('Close', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
            </div>
        </div>
        <script>
            var wpaicg_prompt_logged = <?php echo is_user_logged_in() ? 'true' : 'false'?>;
            window['wpaicgForm<?php echo esc_html($randomFormID)?>'] = {
                fields: <?php echo json_encode($wpaicg_fields,JSON_UNESCAPED_UNICODE)?>,
                type: '<?php echo esc_html($wpaicg_item['type'])?>',
                response: '<?php echo esc_html($wpaicg_response_type)?>',
                logged_in: <?php echo is_user_logged_in() ? 'true': 'false'?>,
                event: '<?php echo esc_html(add_query_arg('wpaicg_stream','yes',site_url().'/index.php'));?>',
                ajax: '<?php echo admin_url('admin-ajax.php')?>',
                post: '<?php echo admin_url('post.php')?>',
                sourceID: '<?php echo esc_html(get_the_ID())?>',
                nonce: '<?php echo esc_html(wp_create_nonce( 'wpaicg-formlog' ))?>',
                ajax_nonce: '<?php echo esc_html(wp_create_nonce( 'wpaicg-ajax-nonce' ))?>',
                id: <?php echo esc_html($wpaicg_item_id)?>,
                feedback_buttons: '<?php echo isset($wpaicg_item['feedback_buttons']) && $wpaicg_item['feedback_buttons'] !== 'no' ? 'yes' : 'no' ?>',
                name: '<?php echo isset($wpaicg_item['title']) && !empty($wpaicg_item['title']) ? esc_html($wpaicg_item['title']) : ''?>',
                feedbackID: '<?php echo esc_html($textareaID); ?>'
            };
        </script>
        <?php
    }
}