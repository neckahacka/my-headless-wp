<?php
/**
 * Prompt Shortcode for GPT3 AI Content Generator
 *
 * @package GPT3 AI Content Generator
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$wpaicg_items = array();
$wpaicg_icons = array();

// Define the model categories and their members.
$gpt4_models   = \WPAICG\WPAICG_Util::get_instance()->openai_gpt4_models;
$gpt35_models  = \WPAICG\WPAICG_Util::get_instance()->openai_gpt35_models;
$custom_models = get_option('wpaicg_custom_models', []);

// Load category data.
if ( file_exists( WPAICG_PLUGIN_DIR . 'admin/data/categories.json' ) ) {
    $wpaicg_file_content = file_get_contents( WPAICG_PLUGIN_DIR . 'admin/data/categories.json' );
    $wpaicg_file_content = json_decode( $wpaicg_file_content, true );
    if ( $wpaicg_file_content && is_array( $wpaicg_file_content ) && count( $wpaicg_file_content ) ) {
        foreach ( $wpaicg_file_content as $key => $item ) {
            $wpaicg_categories[ $key ] = trim( $item );
        }
    }
}

// Load icon data (dashicons).
if ( file_exists( WPAICG_PLUGIN_DIR . 'admin/data/icons.json' ) ) {
    $wpaicg_file_content = file_get_contents( WPAICG_PLUGIN_DIR . 'admin/data/icons.json' );
    $wpaicg_file_content = json_decode( $wpaicg_file_content, true );
    if ( $wpaicg_file_content && is_array( $wpaicg_file_content ) && count( $wpaicg_file_content ) ) {
        foreach ( $wpaicg_file_content as $key => $item ) {
            $wpaicg_icons[ $key ] = trim( $item );
        }
    }
}

// Load prompts data.
if ( file_exists( WPAICG_PLUGIN_DIR . 'admin/data/prompts.json' ) ) {
    $wpaicg_file_content = file_get_contents( WPAICG_PLUGIN_DIR . 'admin/data/prompts.json' );
    $wpaicg_file_content = json_decode( $wpaicg_file_content, true );
    if ( $wpaicg_file_content && is_array( $wpaicg_file_content ) && count( $wpaicg_file_content ) ) {
        foreach ( $wpaicg_file_content as $item ) {
            $wpaicg_items[] = $item;
        }
    }
}

global $wpdb;

if ( isset( $atts ) && is_array( $atts ) && isset( $atts['id'] ) && ! empty( $atts['id'] ) ) {
    $wpaicg_item_id = sanitize_text_field( $atts['id'] );
    $wpaicg_item    = false;
    $wpaicg_custom  = ( isset( $atts['custom'] ) && $atts['custom'] === 'yes' );

    // Retrieve from default prompts JSON if not custom.
    if ( count( $wpaicg_items ) && ! $wpaicg_custom ) {
        foreach ( $wpaicg_items as $wpaicg_prompt ) {
            if ( isset( $wpaicg_prompt['id'] ) && $wpaicg_prompt['id'] == $wpaicg_item_id ) {
                $wpaicg_item          = $wpaicg_prompt;
                $wpaicg_item['type']  = 'json';
            }
        }
    }

    // If custom, fetch from the database.
    if ( $wpaicg_custom ) {
        $sql = "SELECT p.ID as id,p.post_title as title, p.post_content as description";
        $wpaicg_meta_keys = array(
            'prompt','editor','response','category','engine','max_tokens','temperature','top_p','best_of',
            'frequency_penalty','presence_penalty','stop','color','icon','bgcolor','header','embeddings',
            'use_default_embedding_model','selected_embedding_model','selected_embedding_provider','vectordb',
            'collections','pineconeindexes','suffix_text','suffix_position','embeddings_limit','dans','ddraft',
            'dclear','dnotice','generate_text','noanswer_text','draft_text','clear_text','stop_text','cnotice_text',
            'download_text','ddownload','copy_button','copy_text','feedback_buttons'
        );
        foreach ( $wpaicg_meta_keys as $wpaicg_meta_key ) {
            $sql .= ', (' . $wpdb->prepare(
                "SELECT {$wpaicg_meta_key}.meta_value FROM {$wpdb->postmeta} {$wpaicg_meta_key}
                    WHERE {$wpaicg_meta_key}.meta_key = %s
                    AND p.ID={$wpaicg_meta_key}.post_id
                    LIMIT 1",
                'wpaicg_prompt_' . $wpaicg_meta_key
            ) . ") as {$wpaicg_meta_key}";
        }
        $sql .= $wpdb->prepare(
            " FROM {$wpdb->posts} p
              WHERE p.post_type = 'wpaicg_prompt'
              AND p.post_status='publish'
              AND p.ID=%d
              ORDER BY p.post_date DESC",
            $wpaicg_item_id
        );
        $wpaicg_item = $wpdb->get_row( $sql, ARRAY_A );
        if ( $wpaicg_item ) {
            $wpaicg_item['type'] = 'custom';
        }
    }

    if ( $wpaicg_item ) {
        $wpaicg_item_categories      = array();
        $wpaicg_item_categories_name = array();

        if ( isset( $wpaicg_item['category'] ) && ! empty( $wpaicg_item['category'] ) ) {
            $wpaicg_item_categories = array_map( 'trim', explode( ',', $wpaicg_item['category'] ) );
        }

        // Default dashicon if none is set or found.
        $wpaicg_icon = 'dashicons dashicons-admin-generic';
        if (
            isset( $wpaicg_item['icon'] )
            && ! empty( $wpaicg_item['icon'] )
            && isset( $wpaicg_icons[ $wpaicg_item['icon'] ] )
            && ! empty( $wpaicg_icons[ $wpaicg_item['icon'] ] )
        ) {
            $wpaicg_icon = $wpaicg_icons[ $wpaicg_item['icon'] ];
        }

        $wpaicg_icon_color    = ( isset( $wpaicg_item['color'] ) && ! empty( $wpaicg_item['color'] ) ) ? $wpaicg_item['color'] : '#19c37d';
        $wpaicg_engine        = ( isset( $wpaicg_item['engine'] ) && ! empty( $wpaicg_item['engine'] ) ) ? $wpaicg_item['engine'] : $this->wpaicg_engine;
        $wpaicg_max_tokens    = ( isset( $wpaicg_item['max_tokens'] ) && ! empty( $wpaicg_item['max_tokens'] ) ) ? $wpaicg_item['max_tokens'] : $this->wpaicg_max_tokens;
        $wpaicg_temperature   = ( isset( $wpaicg_item['temperature'] ) && ! empty( $wpaicg_item['temperature'] ) ) ? $wpaicg_item['temperature'] : $this->wpaicg_temperature;
        $wpaicg_top_p         = ( isset( $wpaicg_item['top_p'] ) && ! empty( $wpaicg_item['top_p'] ) ) ? $wpaicg_item['top_p'] : $this->wpaicg_top_p;
        $wpaicg_best_of       = ( isset( $wpaicg_item['best_of'] ) && ! empty( $wpaicg_item['best_of'] ) ) ? $wpaicg_item['best_of'] : $this->wpaicg_best_of;
        $wpaicg_frequency_penalty = ( isset( $wpaicg_item['frequency_penalty'] ) && ! empty( $wpaicg_item['frequency_penalty'] ) ) ? $wpaicg_item['frequency_penalty'] : $this->wpaicg_frequency_penalty;
        $wpaicg_presence_penalty  = ( isset( $wpaicg_item['presence_penalty'] ) && ! empty( $wpaicg_item['presence_penalty'] ) ) ? $wpaicg_item['presence_penalty'] : $this->wpaicg_presence_penalty;
        $wpaicg_stop          = ( isset( $wpaicg_item['stop'] ) && ! empty( $wpaicg_item['stop'] ) ) ? $wpaicg_item['stop'] : $this->wpaicg_stop;
        $wpaicg_generate_text = ( isset( $wpaicg_item['generate_text'] ) && ! empty( $wpaicg_item['generate_text'] ) ) ? $wpaicg_item['generate_text'] : esc_html__( 'Generate', 'gpt3-ai-content-generator' );
        $wpaicg_suffix_text   = ( isset( $wpaicg_item['suffix_text'] ) && ! empty( $wpaicg_item['suffix_text'] ) ) ? $wpaicg_item['suffix_text'] : 'Context:';
        $wpaicg_use_default_embedding_model = ( isset( $wpaicg_item['use_default_embedding_model'] ) && ! empty( $wpaicg_item['use_default_embedding_model'] ) ) ? $wpaicg_item['use_default_embedding_model'] : 'yes';
        $selected_embedding_model   = ( isset( $wpaicg_item['selected_embedding_model'] ) && ! empty( $wpaicg_item['selected_embedding_model'] ) ) ? $wpaicg_item['selected_embedding_model'] : '';
        $selected_embedding_provider = ( isset( $wpaicg_item['selected_embedding_provider'] ) && ! empty( $wpaicg_item['selected_embedding_provider'] ) ) ? $wpaicg_item['selected_embedding_provider'] : '';
        $wpaicg_draft_text   = ( isset( $wpaicg_item['draft_text'] ) && ! empty( $wpaicg_item['draft_text'] ) ) ? $wpaicg_item['draft_text'] : esc_html__( 'Save Draft', 'gpt3-ai-content-generator' );
        $wpaicg_noanswer_text= ( isset( $wpaicg_item['noanswer_text'] ) && ! empty( $wpaicg_item['noanswer_text'] ) ) ? $wpaicg_item['noanswer_text'] : esc_html__( 'Number of Answers', 'gpt3-ai-content-generator' );
        $wpaicg_clear_text   = ( isset( $wpaicg_item['clear_text'] ) && ! empty( $wpaicg_item['clear_text'] ) ) ? $wpaicg_item['clear_text'] : esc_html__( 'Clear', 'gpt3-ai-content-generator' );
        $wpaicg_stop_text    = ( isset( $wpaicg_item['stop_text'] ) && ! empty( $wpaicg_item['stop_text'] ) ) ? $wpaicg_item['stop_text'] : esc_html__( 'Stop', 'gpt3-ai-content-generator' );
        $wpaicg_cnotice_text = ( isset( $wpaicg_item['cnotice_text'] ) && ! empty( $wpaicg_item['cnotice_text'] ) ) ? $wpaicg_item['cnotice_text'] : esc_html__( 'Please register to save your result', 'gpt3-ai-content-generator' );
        $wpaicg_download_text= ( isset( $wpaicg_item['download_text'] ) && ! empty( $wpaicg_item['download_text'] ) ) ? $wpaicg_item['download_text'] : __( 'Download', 'gpt3-ai-content-generator' );
        $wpaicg_copy_text    = ( isset( $wpaicg_item['copy_text'] ) && ! empty( $wpaicg_item['copy_text'] ) ) ? $wpaicg_item['copy_text'] : __( 'Copy', 'gpt3-ai-content-generator' );

        // Prepare stop tokens for display.
        $wpaicg_stop_lists = '';
        if ( is_array( $wpaicg_stop ) && count( $wpaicg_stop ) ) {
            foreach ( $wpaicg_stop as $item_stop ) {
                if ( $item_stop === "\n" ) {
                    $item_stop = '\n';
                }
                $wpaicg_stop_lists = empty( $wpaicg_stop_lists ) ? $item_stop : $wpaicg_stop_lists . ',' . $item_stop;
            }
        }

        // Retrieve category names if needed.
        if ( count( $wpaicg_item_categories ) ) {
            foreach ( $wpaicg_item_categories as $wpaicg_item_category ) {
                if ( isset( $wpaicg_categories[ $wpaicg_item_category ] ) && ! empty( $wpaicg_categories[ $wpaicg_item_category ] ) ) {
                    $wpaicg_item_categories_name[] = $wpaicg_categories[ $wpaicg_item_category ];
                }
            }
        }

        // Enqueue WP editor if user is logged in (for the results area).
        if ( is_user_logged_in() ) {
            wp_enqueue_editor();
        }

        // Determine if settings should be shown.
        $wpaicg_show_setting = false;
        if ( isset( $atts['settings'] ) && $atts['settings'] === 'yes' ) {
            $wpaicg_show_setting = true;
        }
        ?>

        <style>
            .wpaicg-prompt-item {}

            .wpaicg-prompt-head {
                display: flex;
                align-items: center;
                padding-bottom: 10px;
                border-bottom: 1px solid #b1b1b1;
            }
            .wpaicg-prompt-icon {
                color: #fff;
                width: 60px;
                height: 60px;
                margin-right: 10px;
                display: flex;
                align-items: center;
                justify-content: center;
                border-radius: 5px;
            }
            /* Updated to style Dashicons instead of SVG */
            .wpaicg-prompt-icon .dashicons {
                line-height: 1;
                color: #fff;
            }

            .wpaicg-prompt-head p {
                margin: 5px 0;
            }
            .wpaicg-prompt-head strong {
                font-size: 20px;
                display: block;
            }
            .wpaicg-prompt-content {
                padding: 10px 0;
            }
            .wpaicg-grid-three {
                display: grid;
                grid-template-columns: repeat(3,1fr);
                grid-column-gap: 20px;
                grid-row-gap: 20px;
                grid-template-rows: auto auto;
            }
            .wpaicg-grid-2 {
                grid-column: span 2/span 1;
            }
            .wpaicg-grid-1 {
                grid-column: span 1/span 1;
            }
            .wpaicg-prompt-item .wpaicg-prompt-sample {
                display: block;
                position: relative;
                font-size: 13px;
            }
            .wpaicg-prompt-item .wpaicg-prompt-sample:hover .wpaicg-prompt-response {
                display: block;
            }
            .wpaicg-prompt-title {
                display: block;
                width: 100%;
                margin-bottom: 20px;
            }
            .wpaicg-prompt-result {
                width: 100%;
            }
            .wpaicg-prompt-max-lines {
                display: inline-block;
                width: auto;
                border: 1px solid #8f8f8f;
                margin-left: 10px;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 15px;
            }
            .wpaicg-generate-button {
                margin-left: 10px;
            }
            .wpaicg-button {
                padding: 5px 10px;
                background: #424242;
                border: 1px solid #343434;
                border-radius: 4px;
                color: #fff;
                font-size: 15px;
                position: relative;
                display: inline-flex;
                align-items: center;
            }
            .wpaicg-button:disabled {
                background: #505050;
                border-color: #999;
            }
            .wpaicg-button:hover:not(:disabled),
            .wpaicg-button:focus:not(:disabled) {
                color: #fff;
                background-color: #171717;
                text-decoration: none;
            }
            .wpaicg-prompt-item .wpaicg-prompt-response {
                background: #333;
                border: 1px solid #444;
                position: absolute;
                border-radius: 3px;
                color: #fff;
                padding: 5px;
                width: 320px;
                bottom: calc(100% + 5px);
                left: -100px;
                z-index: 99;
                display: none;
                font-size: 13px;
            }
            .wpaicg-prompt-item h3 {
                font-size: 25px;
                margin: 0 0 20px 0px;
            }
            .wpaicg-prompt-item .wpaicg-prompt-response:after,
            .wpaicg-prompt-item .wpaicg-prompt-response:before {
                top: 100%;
                left: 50%;
                border: solid transparent;
                content: "";
                height: 0;
                width: 0;
                position: absolute;
                pointer-events: none;
            }
            .wpaicg-prompt-item .wpaicg-prompt-response:before {
                border-color: rgba(68, 68, 68, 0);
                border-top-color: #444;
                border-width: 7px;
                margin-left: -7px;
            }
            .wpaicg-prompt-item .wpaicg-prompt-response:after {
                border-color: rgba(51, 51, 51, 0);
                border-top-color: #333;
                border-width: 6px;
                margin-left: -6px;
            }
            .wpaicg-prompt-item .wpaicg-prompt-field > strong {
                display: inline-flex;
                width: 50%;
                font-size: 13px;
                align-items: center;
                flex-wrap: wrap;
            }
            .wpaicg-prompt-item .wpaicg-prompt-field > strong > small {
                font-size: 12px;
                font-weight: normal;
                display: block;
            }
            .wpaicg-prompt-item .wpaicg-prompt-field > input,
            .wpaicg-prompt-item .wpaicg-prompt-field > select {
                border: 1px solid #8f8f8f;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 15px;
                display: inline-block;
                width: 50%;
            }
            .wpaicg-prompt-flex-center {
                display: flex;
                align-items: center;
            }
            .wpaicg-prompt-field {
                margin-bottom: 10px;
                display: flex;
            }
            .wpaicg-mb-10 {
                margin-bottom: 10px;
            }
            .wpaicg-loader {
                width: 20px;
                height: 20px;
                border: 2px solid #FFF;
                border-bottom-color: transparent;
                border-radius: 50%;
                display: inline-block;
                box-sizing: border-box;
                animation: wpaicg_rotation 1s linear infinite;
            }
            .wpaicg-button .wpaicg-loader {
                float: right;
                margin-left: 5px;
                margin-top: 2px;
            }
            @keyframes wpaicg_rotation {
                0% {
                    transform: rotate(0deg);
                }
                100% {
                    transform: rotate(360deg);
                }
            }
            /* Feedback Modal Styles */
            #wpaicg_feedbackModal {
                display: none;
                position: fixed;
                top: 50%;
                left: 50%;
                z-index: 9999;
                width: 600px;
                justify-content: center;
                align-items: center;
                transform: translate(-50%, -50%);
            }
            .wpaicg_feedbackModal-content {
                background: #fff;
                padding: 40px;
                border-radius: 16px;
                box-shadow: 0px 5px 20px rgba(0, 0, 0, 0.2);
            }
            #wpaicg_feedbackModal h3 {
                margin-bottom: 20px;
                color: #333;
            }
            #wpaicg_feedbackModal textarea {
                width: 100%;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                font-size: 16px;
                resize: vertical;
                box-shadow: 0 0 5px rgba(0, 0, 0, 0.05) inset;
            }
            .wpaicg_button-group {
                display: flex;
                justify-content: flex-end;
                gap: 12px;
                margin-top: 20px;
            }
            #wpaicg_feedbackModal button {
                padding: 10px 16px;
                border: none;
                border-radius: 8px;
                font-size: 16px;
                cursor: pointer;
                transition: background-color 0.3s, transform 0.1s;
            }
            #wpaicg_feedbackModal button:hover {
                transform: translateY(-2px);
            }
            #wpaicg_feedbackModal #wpaicg_submitFeedback {
                background-color: #007BFF;
                color: #fff;
            }
            #wpaicg_feedbackModal #wpaicg_submitFeedback:hover {
                background-color: #0056b3;
            }
            #wpaicg_feedbackModal #closeFeedbackModal {
                background-color: #e0e0e0;
                color: #333;
            }
            #wpaicg_feedbackModal #closeFeedbackModal:hover {
                background-color: #c4c4c4;
            }
            .wpaicg_feedbackModal-overlay {
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0, 0, 0, 0.5);
                z-index: 9998;
                display: none;
                max-width: none !important;
                margin: 0 !important;
            }
        </style>
        <?php
        $wpaicg_response_type = ( isset( $wpaicg_item['editor'] ) && $wpaicg_item['editor'] === 'div' ) ? 'div' : 'textarea';
        $randomFormID         = wp_rand( 100000, 999999 );
        ?>
        <div class="wpaicg-prompt-item wpaicg-playground-shortcode" style="<?php echo isset( $wpaicg_item['bgcolor'] ) && ! empty( $wpaicg_item['bgcolor'] ) ? 'background-color:' . esc_html( $wpaicg_item['bgcolor'] ) : ''; ?>">
            <div class="wpaicg-prompt-head" style="<?php echo ( isset( $wpaicg_item['header'] ) && $wpaicg_item['header'] === 'no' ) ? 'display: none;' : ''; ?>">
                <div class="wpaicg-prompt-icon" style="background: <?php echo esc_html( $wpaicg_icon_color ); ?>;">
                    <span class="<?php echo esc_attr( $wpaicg_icon ); ?>"></span>
                </div>
                <div>
                    <strong>
                        <?php
                        echo ( isset( $wpaicg_item['title'] ) && ! empty( $wpaicg_item['title'] ) )
                            ? esc_html( $wpaicg_item['title'] )
                            : '';
                        ?>
                    </strong>
                    <?php
                    if ( isset( $wpaicg_item['description'] ) && ! empty( $wpaicg_item['description'] ) ) {
                        echo '<p>' . esc_html( $wpaicg_item['description'] ) . '</p>';
                    }
                    ?>
                </div>
            </div>
            <div class="wpaicg-prompt-content">
                <form
                    data-source="promptbase"
                    data-id="<?php echo esc_html( $randomFormID ); ?>"
                    method="post"
                    action=""
                    class="wpaicg-prompt-form"
                    id="wpaicg-prompt-form"
                >
                    <?php if ( $wpaicg_show_setting ) : ?>
                    <div class="wpaicg-grid-three">
                        <div class="wpaicg-grid-2">
                    <?php endif; ?>
                            <strong><?php echo esc_html__( 'Prompt', 'gpt3-ai-content-generator' ); ?></strong>
                            <div class="wpaicg-mb-10">
                                <textarea
                                    name="title"
                                    class="wpaicg-prompt-title"
                                    id="wpaicg-prompt-title"
                                    rows="8"
                                ><?php
                                    echo ( $wpaicg_item['type'] === 'custom' )
                                        ? esc_html( $wpaicg_item['prompt'] ) . ".\n\n"
                                        : esc_html( $wpaicg_item['prompt'] );
                                ?></textarea>
                                <div class="wpaicg-prompt-flex-center">
                                    <div style="<?php echo ( isset( $wpaicg_item['dans'] ) && $wpaicg_item['dans'] === 'no' ) ? 'display:none' : ''; ?>">
                                        <strong><?php echo esc_html( $wpaicg_noanswer_text ); ?></strong>
                                        <select class="wpaicg-prompt-max-lines" id="wpaicg-prompt-max-lines">
                                            <?php
                                            for ( $i = 1; $i <= 10; $i++ ) {
                                                echo '<option value="' . esc_html( $i ) . '">' . esc_html( $i ) . '</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    <button
                                        style="<?php echo ( isset( $wpaicg_item['dans'] ) && $wpaicg_item['dans'] === 'no' ) ? 'margin-left:0' : ''; ?>"
                                        class="wpaicg-button wpaicg-generate-button"
                                        id="wpaicg-generate-button"
                                    >
                                        <?php echo esc_html( $wpaicg_generate_text ); ?>
                                    </button>
                                    &nbsp;<button
                                        data-id="<?php echo esc_html( $randomFormID ); ?>"
                                        type="button"
                                        class="wpaicg-button wpaicg-prompt-stop-generate"
                                        id="wpaicg-prompt-stop-generate"
                                        style="display: none"
                                    >
                                        <?php echo esc_html( $wpaicg_stop_text ); ?>
                                    </button>
                                </div>
                            </div>
                            <div class="mb-5">
                                <?php
                                if ( $wpaicg_response_type === 'textarea' ) :
                                    if ( is_user_logged_in() ) {
                                        wp_editor( '', 'wpaicg-prompt-result-' . $randomFormID, array(
                                            'media_buttons' => true,
                                            'textarea_name' => 'wpaicg-prompt-result-' . $randomFormID,
                                        ) );
                                    } else {
                                        ?>
                                        <textarea
                                            class="wpaicg-prompt-result-<?php echo esc_html( $randomFormID ); ?>"
                                            id="wpaicg-prompt-result-<?php echo esc_html( $randomFormID ); ?>"
                                            rows="12"
                                        ></textarea>
                                        <?php
                                        if ( ! isset( $wpaicg_item['dnotice'] ) || $wpaicg_item['dnotice'] !== 'no' ) {
                                            ?>
                                            <a style="font-size: 13px;" href="<?php echo esc_url( site_url( 'wp-login.php?action=register' ) ); ?>">
                                                <?php echo esc_html( $wpaicg_cnotice_text ); ?>
                                            </a>
                                            <?php
                                        }
                                    }
                                else :
                                    echo '<div id="wpaicg-prompt-result-' . esc_html( $randomFormID ) . '"></div>';
                                    if ( ! is_user_logged_in() ) {
                                        if ( ! isset( $wpaicg_item['dnotice'] ) || $wpaicg_item['dnotice'] !== 'no' ) {
                                            ?>
                                            <a style="font-size: 13px;" href="<?php echo esc_url( site_url( 'wp-login.php?action=register' ) ); ?>">
                                                <?php echo esc_html( $wpaicg_cnotice_text ); ?>
                                            </a>
                                            <?php
                                        }
                                    }
                                endif;
                                ?>
                            </div>
                            <div
                                class="wpaicg-prompt-save-result"
                                id="wpaicg-prompt-save-result"
                                style="display: none; margin-top: 10px;"
                            >
                                <?php
                                if ( is_user_logged_in() ) :
                                    if ( ! isset( $wpaicg_item['ddraft'] ) || $wpaicg_item['ddraft'] !== 'no' ) :
                                ?>
                                <button
                                    data-id="<?php echo esc_html( $randomFormID ); ?>"
                                    type="button"
                                    class="wpaicg-button wpaicg-prompt-save-draft"
                                    id="wpaicg-prompt-save-draft"
                                >
                                    <?php echo esc_html( $wpaicg_draft_text ); ?>
                                </button>
                                <?php
                                    endif;
                                endif;

                                if ( ! isset( $wpaicg_item['dclear'] ) || $wpaicg_item['dclear'] !== 'no' ) :
                                ?>
                                <button
                                    data-id="<?php echo esc_html( $randomFormID ); ?>"
                                    type="button"
                                    class="wpaicg-button wpaicg-prompt-clear"
                                    id="wpaicg-prompt-clear"
                                >
                                    <?php echo esc_html( $wpaicg_clear_text ); ?>
                                </button>
                                <?php endif; ?>

                                <?php if ( ! isset( $wpaicg_item['ddownload'] ) || $wpaicg_item['ddownload'] !== 'no' ) : ?>
                                    <button
                                        data-id="<?php echo esc_html( $randomFormID ); ?>"
                                        type="button"
                                        class="wpaicg-button wpaicg-prompt-download"
                                    >
                                        <?php echo esc_html( $wpaicg_download_text ); ?>
                                    </button>
                                <?php endif; ?>

                                <?php
                                // If copy button is not explicitly turned off, show it.
                                if ( ! isset( $wpaicg_item['copy_button'] ) || $wpaicg_item['copy_button'] !== 'no' ) :
                                ?>
                                    <button
                                        data-id="<?php echo esc_html( $randomFormID ); ?>"
                                        type="button"
                                        class="wpaicg-button wpaicg-prompt-copy_button"
                                        id="wpaicg-prompt-copy_button"
                                    >
                                        <?php echo esc_html( $wpaicg_copy_text ); ?>
                                    </button>
                                <?php endif; ?>

                                <?php
                                if ( isset( $wpaicg_item['feedback_buttons'] ) && $wpaicg_item['feedback_buttons'] === 'yes' ) :
                                ?>
                                    <button
                                        data-id="<?php echo esc_html( $randomFormID ); ?>"
                                        type="button"
                                        class="wpaicg-button wpaicg-prompt-thumbs_up"
                                        id="wpaicg-prompt-thumbs_up"
                                    >
                                        👍
                                    </button>
                                    <button
                                        data-id="<?php echo esc_html( $randomFormID ); ?>"
                                        type="button"
                                        class="wpaicg-button wpaicg-prompt-thumbs_down"
                                        id="wpaicg-prompt-thumbs_down"
                                    >
                                        👎
                                    </button>
                                <?php endif; ?>
                            </div>
                            <?php if ( $wpaicg_show_setting ) : ?>
                        </div>
                        <div class="wpaicg-grid-1">
                            <?php endif; ?>
                            <div
                                class="wpaicg-mb-10 wpaicg-prompt-item"
                                style="<?php echo ! $wpaicg_show_setting ? 'display:none' : ''; ?>"
                            >
                                <h3><?php echo esc_html__( 'Settings', 'gpt3-ai-content-generator' ); ?></h3>
                                <?php
                                // Prepare data for potential multi-provider scenarios.
                                $wpaicg_provider           = get_option( 'wpaicg_provider', 'OpenAI' );
                                $azure_deployment_name     = get_option( 'wpaicg_azure_deployment', '' );
                                $wpaicg_google_model_list  = get_option( 'wpaicg_google_model_list', array( 'gemini-pro' ) );
                                $wpaicg_google_default_model = get_option( 'wpaicg_google_default_model', 'gemini-pro' );
                                $openrouter_models         = get_option( 'wpaicg_openrouter_model_list', array() );
                                $wpaicg_openrouter_default_model = get_option( 'wpaicg_openrouter_default_model', 'openrouter/auto' );
                                ?>
                                <div class="wpaicg-prompt-field wpaicg-prompt-engine">
                                    <strong><?php echo esc_html__( 'Engine', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <?php if ( $wpaicg_provider === 'OpenAI' ) : ?>
                                        <select name="engine">
                                            <optgroup label="GPT-4">
                                                <?php foreach ( $gpt4_models as $value => $display_name ) : ?>
                                                    <option
                                                        value="<?php echo esc_attr( $value ); ?>"
                                                        <?php selected( $value, $wpaicg_engine ); ?>
                                                    >
                                                        <?php echo esc_html( $display_name ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="GPT-3.5">
                                                <?php foreach ( $gpt35_models as $value => $display_name ) : ?>
                                                    <option
                                                        value="<?php echo esc_attr( $value ); ?>"
                                                        <?php selected( $value, $wpaicg_engine ); ?>
                                                    >
                                                        <?php echo esc_html( $display_name ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                            <optgroup label="Custom Models">
                                                <?php foreach ( $custom_models as $model ) : ?>
                                                    <option
                                                        value="<?php echo esc_attr( $model ); ?>"
                                                        <?php selected( $model, $wpaicg_engine ); ?>
                                                    >
                                                        <?php echo esc_html( $model ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    <?php elseif ( $wpaicg_provider === 'OpenRouter' ) : ?>
                                        <?php
                                        // Group models by provider prefix.
                                        $openrouter_grouped_models = array();
                                        foreach ( $openrouter_models as $orm ) {
                                            $orm_parts = explode( '/', $orm['id'] );
                                            $orm_provider = $orm_parts[0];
                                            if ( ! isset( $openrouter_grouped_models[ $orm_provider ] ) ) {
                                                $openrouter_grouped_models[ $orm_provider ] = array();
                                            }
                                            $openrouter_grouped_models[ $orm_provider ][] = $orm;
                                        }
                                        ksort( $openrouter_grouped_models );
                                        ?>
                                        <select name="engine">
                                            <?php
                                            foreach ( $openrouter_grouped_models as $orm_provider => $orm_items ) {
                                                echo '<optgroup label="' . esc_attr( $orm_provider ) . '">';
                                                usort( $orm_items, static function( $a, $b ) {
                                                    return strcmp( $a['name'], $b['name'] );
                                                });
                                                foreach ( $orm_items as $openrouter_model ) {
                                                    ?>
                                                    <option
                                                        value="<?php echo esc_attr( $openrouter_model['id'] ); ?>"
                                                        <?php selected( $openrouter_model['id'], $wpaicg_openrouter_default_model ); ?>
                                                    >
                                                        <?php echo esc_html( $openrouter_model['name'] ); ?>
                                                    </option>
                                                    <?php
                                                }
                                                echo '</optgroup>';
                                            }
                                            ?>
                                        </select>
                                    <?php elseif ( $wpaicg_provider === 'Google' ) : ?>
                                        <select name="engine">
                                            <optgroup label="Google Models">
                                                <?php foreach ( $wpaicg_google_model_list as $model ) : ?>
                                                    <option
                                                        value="<?php echo esc_attr( $model ); ?>"
                                                        <?php selected( $model, $wpaicg_google_default_model ); ?>
                                                    >
                                                        <?php echo esc_html( $model ); ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            </optgroup>
                                        </select>
                                    <?php else : ?>
                                        <!-- Azure AI fallback -->
                                        <input
                                            type="text"
                                            name="engine"
                                            readonly
                                            value="<?php echo esc_html( $azure_deployment_name ); ?>"
                                        />
                                    <?php endif; ?>
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__( 'Token', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <input
                                        class="wpaicg-prompt-max_tokens"
                                        id="wpaicg-prompt-max_tokens"
                                        name="max_tokens"
                                        type="text"
                                        value="<?php echo esc_html( $wpaicg_max_tokens ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__( 'Temp', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <input
                                        class="wpaicg-prompt-temperature"
                                        id="wpaicg-prompt-temperature"
                                        name="temperature"
                                        type="text"
                                        value="<?php echo esc_html( $wpaicg_temperature ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__( 'TP', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <input
                                        class="wpaicg-prompt-top_p"
                                        id="wpaicg-prompt-top_p"
                                        type="text"
                                        name="top_p"
                                        value="<?php echo esc_html( $wpaicg_top_p ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__( 'BO', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <input
                                        class="wpaicg-prompt-best_of"
                                        id="wpaicg-prompt-best_of"
                                        name="best_of"
                                        type="text"
                                        value="<?php echo esc_html( $wpaicg_best_of ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__( 'FP', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <input
                                        class="wpaicg-prompt-frequency_penalty"
                                        id="wpaicg-prompt-frequency_penalty"
                                        name="frequency_penalty"
                                        type="text"
                                        value="<?php echo esc_html( $wpaicg_frequency_penalty ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong><?php echo esc_html__( 'PP', 'gpt3-ai-content-generator' ); ?>: </strong>
                                    <input
                                        class="wpaicg-prompt-presence_penalty"
                                        id="wpaicg-prompt-presence_penalty"
                                        name="presence_penalty"
                                        type="text"
                                        value="<?php echo esc_html( $wpaicg_presence_penalty ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <strong>
                                        <?php echo esc_html__( 'Stop', 'gpt3-ai-content-generator' ); ?>:
                                        <small><?php echo esc_html__( 'separate by commas', 'gpt3-ai-content-generator' ); ?></small>
                                    </strong>
                                    <input
                                        class="wpaicg-prompt-stop"
                                        id="wpaicg-prompt-stop"
                                        type="text"
                                        name="stop"
                                        value="<?php echo esc_html( $wpaicg_stop_lists ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field">
                                    <input
                                        class="wpaicg-prompt-post_title"
                                        id="wpaicg-prompt-post_title"
                                        type="hidden"
                                        name="post_title"
                                        value="<?php echo esc_html( $wpaicg_item['title'] ); ?>"
                                    >
                                </div>
                                <!-- store item id -->
                                <div class="wpaicg-prompt-field">
                                    <input
                                        id="wpaicg-prompt-id"
                                        class="wpaicg-prompt-id"
                                        type="hidden"
                                        name="id"
                                        value="<?php echo esc_html( $wpaicg_item_id ); ?>"
                                    >
                                </div>
                                <div class="wpaicg-prompt-field wpaicg-prompt-sample">
                                    <?php echo esc_html__( 'Sample Response', 'gpt3-ai-content-generator' ); ?>
                                    <div class="wpaicg-prompt-response">
                                        <?php echo esc_html( @$wpaicg_item['response'] ); ?>
                                    </div>
                                </div>
                            </div>
                            <?php if ( $wpaicg_show_setting ) : ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        <?php
        // Feedback modal setup.
        $textareaID = 'feedbackText_' . $randomFormID;
        ?>
        <div class="wpaicg_feedbackModal-overlay"></div>
        <div id="wpaicg_feedbackModal">
            <div class="wpaicg_feedbackModal-content">
                <h3><?php echo esc_html__( 'Provide additional feedback', 'gpt3-ai-content-generator' ); ?></h3>
                <textarea
                    id="<?php echo esc_html( $textareaID ); ?>"
                    rows="6"
                    placeholder="<?php echo esc_attr__( 'Enter your feedback here...', 'gpt3-ai-content-generator' ); ?>"
                ></textarea>
                <div class="wpaicg_button-group">
                    <button id="wpaicg_submitFeedback">
                        <?php echo esc_html__( 'Submit', 'gpt3-ai-content-generator' ); ?>
                    </button>
                    <button id="closeFeedbackModal">
                        <?php echo esc_html__( 'Close', 'gpt3-ai-content-generator' ); ?>
                    </button>
                </div>
            </div>
        </div>
        <script>
            var wpaicg_prompt_logged = <?php echo is_user_logged_in() ? 'true' : 'false'; ?>;
            window['wpaicgForm<?php echo esc_html( $randomFormID ); ?>'] = {
                type: '<?php echo esc_html( $wpaicg_item['type'] ); ?>',
                response: '<?php echo esc_html( $wpaicg_response_type ); ?>',
                logged_in: <?php echo is_user_logged_in() ? 'true' : 'false'; ?>,
                event: '<?php echo esc_html( add_query_arg( 'wpaicg_stream','yes', site_url() . '/index.php' ) ); ?>',
                ajax: '<?php echo esc_html( admin_url( 'admin-ajax.php' ) ); ?>',
                post: '<?php echo esc_html( admin_url( 'post.php' ) ); ?>',
                sourceID: '<?php echo esc_html( get_the_ID() ); ?>',
                nonce: '<?php echo esc_html( wp_create_nonce( 'wpaicg-promptbase' ) ); ?>',
                ajax_nonce: '<?php echo esc_html( wp_create_nonce( 'wpaicg-ajax-nonce' ) ); ?>',
                id: <?php echo esc_html( $wpaicg_item_id ); ?>,
                feedback_buttons: '<?php echo ( isset( $wpaicg_item['feedback_buttons'] ) && $wpaicg_item['feedback_buttons'] !== 'no' ) ? 'yes' : 'no'; ?>',
                datasource: 'promptbase',
                name: '<?php echo ( isset( $wpaicg_item['title'] ) && ! empty( $wpaicg_item['title'] ) ) ? esc_html( $wpaicg_item['title'] ) : ''; ?>',
                feedbackID: '<?php echo esc_html( $textareaID ); ?>'
            };
        </script>
        <?php
    }
}