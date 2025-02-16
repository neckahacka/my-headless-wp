<?php

if ( !defined( 'ABSPATH' ) ) {
    exit;
}
$gpt4_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt4_models;
$gpt35_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt35_models;
// Prepare the models in a grouped format
$openai_models = array(
    'groups' => array(
        'GPT-4 Models'   => $gpt4_models,
        'GPT-3.5 Models' => $gpt35_models,
    ),
);
$custom_models = get_option( 'wpaicg_custom_models', [] );
$wpaicg_parameters = array(
    'type'              => 'topic',
    'post_type'         => 'post',
    'provider'          => 'openai',
    'model'             => 'gpt-3.5-turbo-16k',
    'azure_deployment'  => '',
    'google_model'      => 'gemini-pro',
    'temperature'       => '0.7',
    'max_tokens'        => 3000,
    'top_p'             => '0.01',
    'best_of'           => 1,
    'frequency_penalty' => '0.01',
    'presence_penalty'  => '0.01',
    'prompt_title'      => esc_html__( 'Suggest [count] title for an article about [topic]. Return only the titles without any additional text or explanation.', 'gpt3-ai-content-generator' ),
    'prompt_section'    => esc_html__( 'Write [count] consecutive headings for an article about [title]. Return only the headings without any additional text or explanation.', 'gpt3-ai-content-generator' ),
    'prompt_content'    => esc_html__( 'Write a comprehensive article about [title], covering the following subtopics [sections]. Each subtopic should have at least [count] paragraphs. Use a cohesive structure to ensure smooth transitions between ideas. Include relevant statistics, examples, and quotes to support your arguments and engage the reader.', 'gpt3-ai-content-generator' ),
    'prompt_meta'       => esc_html__( 'Write a meta description about [title]. Max: 155 characters.', 'gpt3-ai-content-generator' ),
    'prompt_excerpt'    => esc_html__( 'Generate an excerpt for [title]. Max: 55 words.', 'gpt3-ai-content-generator' ),
);
$wpaicg_all_templates = get_posts( array(
    'post_type'      => 'wpaicg_mtemplate',
    'posts_per_page' => -1,
) );
$wpaicg_templates = array(array(
    'title'   => 'Default',
    'content' => $wpaicg_parameters,
));
foreach ( $wpaicg_all_templates as $wpaicg_all_template ) {
    $wpaicg_template_content = json_decode( $wpaicg_all_template->post_content, true );
    if ( !is_array( $wpaicg_template_content ) ) {
        $wpaicg_template_content = array();
    }
    $wpaicg_template_content = wp_parse_args( $wpaicg_template_content, $wpaicg_parameters );
    $wpaicg_templates[$wpaicg_all_template->ID] = array(
        'title'   => $wpaicg_all_template->post_title,
        'content' => $wpaicg_template_content,
    );
}
$default_name = '';
if ( isset( $selected_template ) && !empty( $selected_template ) ) {
    $wpaicg_parameters = $wpaicg_templates[$selected_template]['content'];
    $default_name = $wpaicg_templates[$selected_template]['title'];
}
?>

<div class="content-writer-master">
  <div class="content-writer-master-navigation">
    <nav>
      <ul>
        <li>
          <a href="#express">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-tool">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
            </svg>
            <?php 
echo esc_html__( 'Express Mode', 'gpt3-ai-content-generator' );
?>
            </a>
        </li>
        <li>
          <a href="#custom">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers">
              <polygon points="12 2 2 7 12 12 22 7 12 2" />
              <polyline points="2 17 12 22 22 17" />
              <polyline points="2 12 12 17 22 12" />
            </svg>
            <?php 
echo esc_html__( 'Custom Mode', 'gpt3-ai-content-generator' );
?>
        </a>
        </li>
        <li>
          <a href="#playground">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-square">
              <polyline points="9 11 12 14 22 4" />
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
            <?php 
echo esc_html__( 'Playground', 'gpt3-ai-content-generator' );
?>
            </a>
        </li>
        <li>
          <a href="#speechtopost">
            <svg xmlns="http://www.w3.org/2000/svg" style="transform: rotate(90deg)" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-columns">
              <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7m0-18v18" />
            </svg>
            <?php 
echo esc_html__( 'Speech to Post', 'gpt3-ai-content-generator' );
?>
        </a>
        </li>
        <li>
          <a href="#logs">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-feather">
              <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z" />
              <line x1="16" y1="8" x2="2" y2="22" />
              <line x1="17.5" y1="15" x2="9" y2="15" />
            </svg>
            <?php 
echo esc_html__( 'Logs', 'gpt3-ai-content-generator' );
?>
        </a>
        </li>
      </ul>
    </nav>
  </div>
  <main class="content-writer-master-content">
    <!-- Express Mode -->
    <form id="wpaicg-single-content-form">
        <section id="expressdata">
            <div class="href-target" id="express"></div>
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-tool">
                <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
                </svg>
                <?php 
echo esc_html__( 'Express Mode', 'gpt3-ai-content-generator' );
?>
            </h1>
            <div class="nice-form-group">
                <?php 
$mode = 'NEW';
global $wpdb;
$table = $wpdb->prefix . 'wpaicg';
$result = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE name = %s", 'wpaicg_settings' ) );
$value = '';
$_wporg_preview_title = '';
$_wporg_language = $result->wpai_language;
$_wporg_number_of_heading = $result->wpai_number_of_heading;
$_wporg_writing_style = $result->wpai_writing_style;
$_wporg_writing_tone = $result->wpai_writing_tone;
$_wporg_heading_tag = $result->wpai_heading_tag;
$_wporg_target_url = '';
$_wporg_cta_pos = $result->wpai_cta_pos;
$_wporg_target_url_cta = '';
$_wporg_keywords = "";
$_wporg_add_keywords_bold = $result->wpai_add_keywords_bold;
$_wporg_words_to_avoid = '';
$_wporg_modify_headings = $result->wpai_modify_headings;
$_wporg_add_img = $result->wpai_add_img;
$_wporg_add_tagline = $result->wpai_add_tagline;
$_wporg_add_intro = $result->wpai_add_intro;
$_wporg_add_faq = $result->wpai_add_faq;
$_wporg_add_conclusion = $result->wpai_add_conclusion;
$_wporg_anchor_text = '';
$_wporg_generated_text = '';
$languages = \WPAICG\WPAICG_Util::get_instance()->wpaicg_languages;
$styles = \WPAICG\WPAICG_Util::get_instance()->wpaicg_writing_styles;
$tones = \WPAICG\WPAICG_Util::get_instance()->wpaicg_writing_tones;
$heading_tags = \WPAICG\WPAICG_Util::get_instance()->wpaicg_heading_tags;
?>
                <!-- Title, Content and SEO tabs here -->
                <div class="nice-form-group" style="display: flex;">
                    <input type="text" id="wpai_preview_title" placeholder="<?php 
echo esc_html__( 'Title: e.g. Artificial Intelligence', 'gpt3-ai-content-generator' );
?>" name="_wporg_preview_title" value="<?php 
echo esc_html( $_wporg_preview_title );
?>">
                    <button type="button" name="get_preview" id="wpcgai_load_plugin_settings" class="button button-primary" style="margin-left: 1em;display: flex;align-items: center;"><?php 
echo esc_html__( 'Generate', 'gpt3-ai-content-generator' );
?></button>
                </div>
                <div class="nice-form-group">
                    <div class="wpaicg-tabs">
                        <ul>
                            <li id="wpaicg-seo-tab-content" data-target="wpaicg-tab-generated-text" class="wpaicg-active"><?php 
echo esc_html__( 'Content', 'gpt3-ai-content-generator' );
?></li>
                            <li id="wpaicg-seo-tab-item" data-target="wpaicg-seo-tab" class="<?php 
echo ( !empty( $post->post_excerpt ) ? 'wpaicg-has-seo' : '' );
?>"><?php 
echo esc_html__( 'SEO', 'gpt3-ai-content-generator' );
?></li>
                        </ul>
                        <div class="wpaicg-tab-content">
                            <div id="wpaicg-tab-generated-text">
                                <textarea id="wpcgai_preview_box" name="_wporg_generated_text" rows="20" cols="20" class="wpai-content-generator-textarea"></textarea>
                            </div>
                            <div id="wpaicg-seo-tab" style="display: none">
                                <p><?php 
echo esc_html__( 'Meta Description', 'gpt3-ai-content-generator' );
?></p>
                                <textarea id="wpaicg-meta-description" name="_wpaicg_meta_description" rows="20" cols="20"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="nice-form-group">
                    <button type="button" style="display:none;" name="action_save_draft" id="wpcgai_save_draft_post_action" class="button button-primary"><?php 
echo esc_html__( 'Save Draft', 'gpt3-ai-content-generator' );
?></button>
                </div>
            </div>
            <!-- Express Mode Heading Modal Window -->
            <div class="modal-wpaicg fade" id="myModal" role="dialog" data-backdrop="static" data-keyboard="false">
                <div class="modal-wpaicg-dialog">
                    <!-- Modal content-->
                    <div class="wpcgai_modal-content">
                        <div class="wpcgai_modal-header">
                            <!--<button type="button" class="close" data-dismiss="modal">&times;</button>-->
                            <h4 class="wpcgai_modal-title"><?php 
echo esc_html__( 'Outline Editor', 'gpt3-ai-content-generator' );
?></h4>
                            <span><?php 
echo esc_html__( 'You can modify, sort, add or delete headings.', 'gpt3-ai-content-generator' );
?></span>
                        </div>
                        <div class="wpcgai_modal-body">
                            <ol class="wpcgai_menu_editor"></ol>
                            <a href="javascript:;" id="wpcgai_add_new_heading">+ <?php 
echo esc_html__( 'Add new heading', 'gpt3-ai-content-generator' );
?></a>
                        </div>
                        <div class="wpcgai_modal-footer">
                            <button type="button" class="button button-secondary button-large m_close"><?php 
echo esc_html__( 'CANCEL', 'gpt3-ai-content-generator' );
?></button>
                            <button type="button" class="button button-primary button-large m_generate"><?php 
echo esc_html__( 'GENERATE', 'gpt3-ai-content-generator' );
?></button>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </form>
    <!-- Custom Mode -->
    <form action="" method="post" class="wpaicg_custom_template_form">
        <?php 
wp_nonce_field( 'wpaicg_custom_mode_generator' );
?>
        <section>
            <div class="href-target" id="custom"></div>
            <h1>
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers">
                <polygon points="12 2 2 7 12 12 22 7 12 2" />
                <polyline points="2 17 12 22 22 17" />
                <polyline points="2 12 12 17 22 12" />
                </svg>
                <?php 
echo esc_html__( 'Custom Mode', 'gpt3-ai-content-generator' );
?>
            </h1>
            <!-- Custom Mode Title -->
            <div class="nice-form-group">
                <div class="mb-5" style="height:30px;display: flex;justify-content: space-between;align-items: center">
                    <div>
                        <label><input name="template[type]" checked type="radio" class="wpaicg_custom_template_type_topic" value="topic">&nbsp;<strong><?php 
echo esc_html__( 'Topic', 'gpt3-ai-content-generator' );
?></strong></label>
                        &nbsp;&nbsp;&nbsp;<label><input name="template[type]" class="wpaicg_custom_template_type_title" type="radio" value="title">&nbsp;<strong><?php 
echo esc_html__( 'Use My Own Title', 'gpt3-ai-content-generator' );
?></strong></label>
                    </div>
                    <div class="wpaicg-custom-template-row wpaicg_custom_template_row_type">
                        #<?php 
echo esc_html__( 'of titles', 'gpt3-ai-content-generator' );
?>&nbsp;
                        <select class="wpaicg_custom_template_title_count" name="title_count">
                            <option value="3">3</option>
                            <option selected value="5">5</option>
                            <option value="7">7</option>
                        </select>
                        &nbsp;
                        <button class="button button-primary wpaicg_template_generate_titles" type="button"><?php 
echo esc_html__( 'Suggest Titles', 'gpt3-ai-content-generator' );
?></button>
                    </div>
                </div>
                <div class="wpaicg_custom_template_add_topic">
                    <div class="nice-form-group">
                        <input class="wpaicg_template_topic" type="text" placeholder="<?php 
echo esc_html__( 'Topic: e.g. Artificial Intelligence', 'gpt3-ai-content-generator' );
?>">
                    </div>
                </div>
                <div class="wpaicg_custom_template_add_title" style="display: none">
                    <div class="nice-form-group">
                        <input type="text" class="wpaicg_template_title_field" placeholder="<?php 
echo esc_html__( 'Please enter a title', 'gpt3-ai-content-generator' );
?>">
                    </div>
                </div>
                <div class="wpaicg_template_title_result" style="display: none"></div>
            </div>
            <!-- Custom Mode Sections -->
            <div class="nice-form-group">
                <div class="wpaicg-mb-10">
                    <div class="mb-5" style="display: flex;justify-content: space-between;align-items: center">
                        <strong><?php 
echo esc_html__( 'Sections', 'gpt3-ai-content-generator' );
?></strong>
                        <div class="wpaicg-custom-template-row">
                            #<?php 
echo esc_html__( 'of sections', 'gpt3-ai-content-generator' );
?>&nbsp;
                            <select class="wpaicg_custom_template_section_count" name="section_count">
                                <?php 
for ($i = 1; $i < 13; $i++) {
    if ( $i % 2 == 0 ) {
        echo '<option' . (( $i == 4 ? ' selected' : '' )) . ' value="' . esc_html( $i ) . '">' . esc_html( $i ) . '</option>';
    }
}
?>
                            </select>
                            &nbsp;
                            <button class="button button-primary wpaicg_template_generate_sections" type="button" disabled><?php 
echo esc_html__( 'Generate Sections', 'gpt3-ai-content-generator' );
?></button>
                            <button class="button button-link-delete wpaicg_template_generate_stop" data-type="section" type="button" style="display: none"><?php 
echo esc_html__( 'Stop', 'gpt3-ai-content-generator' );
?></button>
                        </div>
                    </div>
                    <div class="mb-5">
                        <textarea class="wpaicg_template_section_result" rows="5"></textarea>
                    </div>
                </div>
            </div>
            <!-- Custom Mode Content -->
            <div class="nice-form-group">
                <div class="wpaicg-mb-10">
                    <div class="mb-5" style="display: flex;justify-content: space-between;align-items: center">
                        <strong><?php 
echo esc_html__( 'Content', 'gpt3-ai-content-generator' );
?></strong>
                        <div class="wpaicg-custom-template-row">
                            #<?php 
echo esc_html__( 'of Paragraph per Section', 'gpt3-ai-content-generator' );
?>&nbsp;
                            <select class="wpaicg_custom_template_paragraph_count" name="paragraph_count">
                                <?php 
for ($i = 1; $i < 11; $i++) {
    echo '<option' . (( $i == 4 ? ' selected' : '' )) . ' value="' . esc_html( $i ) . '">' . esc_html( $i ) . '</option>';
}
?>
                            </select>
                            &nbsp;
                            <button class="button button-primary wpaicg_template_generate_content" type="button" disabled><?php 
echo esc_html__( 'Generate Content', 'gpt3-ai-content-generator' );
?></button>
                            <button class="button button-link-delete wpaicg_template_generate_stop" data-type="content" type="button" style="display: none"><?php 
echo esc_html__( 'Stop', 'gpt3-ai-content-generator' );
?></button>
                        </div>
                    </div>
                    <div class="mb-5">
                        <textarea class="wpaicg_template_content_result" rows="15"></textarea>
                    </div>
                </div>
            </div>
            <!-- Custom Mode Excerpt -->
            <div class="nice-form-group">
                <div class="wpaicg-mb-10">
                    <div class="mb-5" style="display: flex;justify-content: space-between;align-items: center">
                        <strong><?php 
echo esc_html__( 'Excerpt', 'gpt3-ai-content-generator' );
?></strong>
                        <div class="wpaicg-custom-template-row">
                            <button class="button button-primary wpaicg_template_generate_excerpt" type="button" disabled><?php 
echo esc_html__( 'Generate Excerpt', 'gpt3-ai-content-generator' );
?></button>
                            <button class="button button-link-delete wpaicg_template_generate_stop" data-type="excerpt" type="button" style="display: none"><?php 
echo esc_html__( 'Stop', 'gpt3-ai-content-generator' );
?></button>
                        </div>
                    </div>
                    <div class="mb-5">
                        <textarea class="wpaicg_template_excerpt_result" rows="5"></textarea>
                    </div>
                </div>
            </div>
            <!-- Custom Mode Meta -->
            <div class="nice-form-group">
                <div class="wpaicg-mb-10">
                    <div class="mb-5" style="display: flex;justify-content: space-between;align-items: center">
                        <strong><?php 
echo esc_html__( 'Meta Description', 'gpt3-ai-content-generator' );
?></strong>
                        <div class="wpaicg-custom-template-row">
                            <button class="button button-primary wpaicg_template_generate_meta" type="button" disabled><?php 
echo esc_html__( 'Generate Meta', 'gpt3-ai-content-generator' );
?></button>
                            <button class="button button-link-delete wpaicg_template_generate_stop" data-type="meta" type="button" style="display: none"><?php 
echo esc_html__( 'Stop', 'gpt3-ai-content-generator' );
?></button>
                        </div>
                    </div>
                    <div class="mb-5">
                        <textarea class="wpaicg_template_meta_result" rows="5"></textarea>
                    </div>
                </div>
            </div>
            <div class="">
                <button type="button" class="button button-primary wpaicg_template_save_post" style="display: none;width: 100%"><?php 
echo esc_html__( 'Create Post', 'gpt3-ai-content-generator' );
?></button>
            </div>
        </section>
    </form>

    <!-- Speech to Post -->
    <section>
        <div class="href-target" id="speechtopost"></div>
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-square">
            <polyline points="9 11 12 14 22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
            <?php 
echo esc_html__( 'Speech to Post', 'gpt3-ai-content-generator' );
?>
        </h1>
        <?php 
wp_enqueue_editor();
$wpaicg_provider = get_option( 'wpaicg_provider', 'OpenAI' );
?>
        <div class="nice-form-group">
            <p><?php 
echo esc_html__( 'Simply press the record button and speak your prompt, just like you would in a conversation.', 'gpt3-ai-content-generator' );
?></p>
            <strong><?php 
echo esc_html__( 'Example', 'gpt3-ai-content-generator' );
?></strong>
            <p style="font-style: italic">"<?php 
echo esc_html__( 'Write a blog post about the latest mobile phones and their features. Include an introduction that highlights the importance of mobile phones in today\'s world. In the body of the post, discuss the latest mobile phone trends, such as foldable screens, 5G connectivity, and high refresh rate displays. Also, mention the most popular mobile phone brands and their latest releases. Don\'t forget to discuss the benefits and drawbacks of each phone and how they compare to one another. In the conclusion, summarize the key points of the post.', 'gpt3-ai-content-generator' );
?>"</p>
            <button class="button button-primary button-hero btn-start-record" style="display: inline-flex; align-items: center;" <?php 
echo ( $wpaicg_provider != 'OpenAI' ? 'disabled' : '' );
?>><span class="dashicons dashicons-microphone"></span><?php 
echo esc_html__( 'Speak', 'gpt3-ai-content-generator' );
?></button>
            <button class="button button-primary button-hero btn-pause-record" style="display: none; align-items: center;" <?php 
echo ( $wpaicg_provider != 'OpenAI' ? 'disabled' : '' );
?>><span class="dashicons dashicons-controls-pause"></span><?php 
echo esc_html__( 'Pause', 'gpt3-ai-content-generator' );
?></button>
            <button class="button button-link-delete button-hero btn-stop-record" style="display: none; align-items: center;" <?php 
echo ( $wpaicg_provider != 'OpenAI' ? 'disabled' : '' );
?>><span class="dashicons dashicons-saved"></span><?php 
echo esc_html__( 'Stop', 'gpt3-ai-content-generator' );
?></button>
            <button class="button button-link-delete button-hero btn-abort-record" style="display: none; align-items: center;" <?php 
echo ( $wpaicg_provider != 'OpenAI' ? 'disabled' : '' );
?>><span class="dashicons dashicons-no"></span><?php 
echo esc_html__( 'Cancel', 'gpt3-ai-content-generator' );
?></button>
            <!-- Not OpenAI -->
            <?php 
if ( $wpaicg_provider != 'OpenAI' ) {
    ?>
                <p style="color:red;">
                    <?php 
    echo esc_html__( '"Speech to Post" module is only available with OpenAI. If you want to use this feature, go to Settings - AI Engine and change your provider to OpenAI.', 'gpt3-ai-content-generator' );
    ?>
                </p>
            <?php 
}
?>

            <p class="wpaicg-sending-record" style="display:none;width: 150px; text-align: left">
                <button class="button button-link-delete button-hero btn-cancel-record" style="display: inline-flex;color: #0b9529;"><span class="spinner" style="visibility: unset;margin: auto;"></span><?php 
echo esc_html__( 'Generating Content..Please Wait..', 'gpt3-ai-content-generator' );
?></button>
            </p>
            <p></p>
            <div class="wpaicg-speech-audio"></div>
            <div class="wpaicg-speech-message"></div>
            <div class="wpaicg-speech-result" style="margin-top: 20px;display: none">
                <div class="nice-form-group">
                    <input type="text" class="wpaicg-speech-title" placeholder="<?php 
echo esc_html__( 'Enter a Post Title', 'gpt3-ai-content-generator' );
?>">
                </div>
                <div class="nice-form-group">
                    <strong><?php 
echo esc_html__( 'Post Content', 'gpt3-ai-content-generator' );
?></strong>
                </div>
                <div class="nice-form-group">
                    <?php 
wp_editor( '', 'wpaicg-speech-content', array(
    'editor_height' => 425,
    'textarea_rows' => 20,
) );
?>
                </div>
                <input type="hidden" class="wpaicg-audio-duration">
                <input type="hidden" class="wpaicg-audio-tokens">
                <input type="hidden" class="wpaicg-audio-length">
                <button class="button button-primary wpaicg-audio-save"><?php 
echo esc_html__( 'Save Draft', 'gpt3-ai-content-generator' );
?></button>
            </div>

        </div>
    </section>
    
    <!-- Playground -->
    <section>
        <div class="href-target" id="playground"></div>
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" style="transform: rotate(90deg)" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-columns">
            <path d="M12 3h7a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-7m0-18H5a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h7m0-18v18" />
            </svg>
            <?php 
echo esc_html__( 'Playground', 'gpt3-ai-content-generator' );
?>
        </h1>
        <div class="nice-form-group">
        <?php 
wp_editor( '', 'wpaicg_generator_result', array(
    'editor_height' => 415,
    'media_buttons' => true,
    'textarea_name' => 'wpaicg_generator_result',
    'textarea_rows' => 20,
) );
?>
        <p class="wpaicg-playground-buttons">
            <button class="button button-primary wpaicg-playground-save"><?php 
echo esc_html__( 'Save as Draft', 'gpt3-ai-content-generator' );
?></button>
            <button class="button wpaicg-playground-clear"><?php 
echo esc_html__( 'Clear', 'gpt3-ai-content-generator' );
?></button>
        </p>
        </div>
    </section>
    
    <!-- Logs -->
    <section>
        <div class="href-target" id="logs"></div>
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-feather">
            <path d="M20.24 12.24a6 6 0 0 0-8.49-8.49L5 10.5V19h8.5z" />
            <line x1="16" y1="8" x2="2" y2="22" />
            <line x1="17.5" y1="15" x2="9" y2="15" />
            </svg>
            <?php 
echo esc_html__( 'Logs', 'gpt3-ai-content-generator' );
?>
        </h1>
        <?php 
$wpaicg_log_page = ( isset( $_GET['wpage'] ) && !empty( $_GET['wpage'] ) ? sanitize_text_field( $_GET['wpage'] ) : 1 );
$args = array(
    'post_type'      => 'wpaicg_slog',
    'posts_per_page' => 10,
    'paged'          => $wpaicg_log_page,
    'order'          => 'DESC',
    'orderby'        => 'date',
);
$wpaicg_single_logs = new WP_Query($args);
?>
        <p><?php 
echo esc_html__( 'Only the last 10 logs are displayed here.', 'gpt3-ai-content-generator' );
?></p>
        <div class="nice-form-group">
            <table class="wp-list-table widefat striped posts" style="white-space: normal;">
                <thead>
                <tr>
                    <th><?php 
echo esc_html__( 'Title', 'gpt3-ai-content-generator' );
?></th>
                    <th><?php 
echo esc_html__( 'Date', 'gpt3-ai-content-generator' );
?></th>
                    <th><?php 
echo esc_html__( 'Token', 'gpt3-ai-content-generator' );
?></th>
                    <th><?php 
echo esc_html__( 'Estimated', 'gpt3-ai-content-generator' );
?></th>
                    <th><?php 
echo esc_html__( 'Model', 'gpt3-ai-content-generator' );
?></th>
                    <th><?php 
echo esc_html__( 'Word', 'gpt3-ai-content-generator' );
?></th>
                </tr>
                </thead>
                <tbody>
                <?php 
if ( $wpaicg_single_logs->have_posts() ) {
    foreach ( $wpaicg_single_logs->posts as $wpaicg_single_log ) {
        $wpaicg_duration = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_duration', true );
        $wpaicg_ai_model = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_ai_model', true );
        $wpaicg_usage_token = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_usage_token', true );
        $wpaicg_word_count = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_word_count', true );
        $wpaicg_post_id = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_post_id', true );
        $wpaicg_source_log = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_source_log', true );
        $post_categories = wp_get_post_categories( $wpaicg_post_id, array(
            'fields' => 'names',
        ) );
        $wpaicg_provider = get_post_meta( $wpaicg_single_log->ID, 'wpaicg_provider', true );
        // Define pricing per 1K tokens
        $pricing = \WPAICG\WPAICG_Util::get_instance()->model_pricing;
        $wpaicg_estimated = 0;
        // Calculate estimated cost
        if ( !empty( $wpaicg_usage_token ) && is_numeric( $wpaicg_usage_token ) ) {
            if ( array_key_exists( $wpaicg_ai_model, $pricing ) ) {
                $wpaicg_estimated = $pricing[$wpaicg_ai_model] * $wpaicg_usage_token / 1000;
            } else {
                // Default pricing if the model is not listed
                $wpaicg_estimated = 0.02 * $wpaicg_usage_token / 1000;
            }
        } else {
            $wpaicg_estimated = 0;
            // Ensure estimated cost is 0 if there are no usage tokens
        }
        if ( $wpaicg_source_log == 'speech' && $wpaicg_duration > 0 ) {
            $wpaicg_estimated += $wpaicg_duration * 0.0001;
        }
        ?>
                <tr>
                    <td>
                        <a href="<?php 
        echo admin_url( 'post.php?post=' . esc_html( $wpaicg_post_id ) . '&action=edit' );
        ?>">
                            <?php 
        $title = str_replace( 'WPAICGLOG:', '', esc_html( $wpaicg_single_log->post_title ) );
        echo ( strlen( $title ) > 10 ? substr( $title, 0, 10 ) . '...' : $title );
        ?>
                        </a>
                    </td>
                    <td><?php 
        echo esc_html( gmdate( 'd.m.Y H:i', strtotime( $wpaicg_single_log->post_date ) ) );
        ?></td>
                    <td>
                        <?php 
        // Check if $wpaicg_usage_token is set, not empty, and is numeric
        if ( isset( $wpaicg_usage_token ) && !empty( $wpaicg_usage_token ) && is_numeric( $wpaicg_usage_token ) ) {
            echo esc_html( round( (float) $wpaicg_usage_token ) );
        } else {
            // If $wpaicg_usage_token does not meet the criteria, display a default value or handle as needed
            echo esc_html( '0' );
        }
        ?>
                    </td>
                    <td><?php 
        echo esc_html( number_format( $wpaicg_estimated, 5 ) );
        ?>$</td>
                    <td><?php 
        echo esc_html( $wpaicg_ai_model );
        ?></td>
                    <td><?php 
        echo esc_html( $wpaicg_word_count );
        ?></td>
                </tr>
                <?php 
    }
}
?>
                </tbody>
            </table>
        </div>
    </section>
  </main>
    <!-- Right Navigation for Express Mode -->
    <div class="content-writer-right-navigation" id="right-nav-express" style="display: none;">
        <nav>
            <ul>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings" ><?php 
echo esc_html__( 'Language & Behavior', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Language, Style and Tone -->
                    <div class="submenu" style="display: none;">
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html( __( "Language", "gpt3-ai-content-generator" ) );
?></label>
                            <select name="_wporg_language" id="wpai_language">
                                <?php 
foreach ( $languages as $value => $label ) {
    ?>
                                    <option value="<?php 
    echo esc_attr( $value );
    ?>" <?php 
    echo ( esc_attr( $_wporg_language ) === $value ? 'selected' : '' );
    ?>>
                                        <?php 
    echo esc_html( $label );
    ?>
                                    </option>
                                <?php 
}
?>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html( __( "Style", "gpt3-ai-content-generator" ) );
?></label>
                            <select name="_wporg_writing_style" id="wpai_writing_style">
                                <?php 
foreach ( $styles as $value => $label ) {
    ?>
                                    <option value="<?php 
    echo esc_attr( $value );
    ?>" <?php 
    echo ( esc_attr( $_wporg_writing_style ) === $value ? 'selected' : '' );
    ?>>
                                        <?php 
    echo esc_html( $label );
    ?>
                                    </option>
                                <?php 
}
?>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html( __( "Tone", "gpt3-ai-content-generator" ) );
?></label>
                            <select name="_wporg_writing_tone" id="wpai_writing_tone">
                                <?php 
foreach ( $tones as $value => $label ) {
    ?>
                                    <option value="<?php 
    echo esc_attr( $value );
    ?>" <?php 
    echo ( esc_attr( $_wporg_writing_tone ) === $value ? 'selected' : '' );
    ?>>
                                        <?php 
    echo esc_html( $label );
    ?>
                                    </option>
                                <?php 
}
?>
                            </select>
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings"><?php 
echo esc_html__( 'Headings', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Headings -->
                    <div class="submenu" style="display: none;">
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html( __( "Number of Headings", "gpt3-ai-content-generator" ) );
?></label>
                            <input id="wpai_number_of_heading" name="_wporg_number_of_heading" type="range" min="1" max="15" value="<?php 
echo esc_attr( $_wporg_number_of_heading );
?>" oninput="this.nextElementSibling.value = this.value">
                            <output for="wpai_number_of_heading"><?php 
echo esc_attr( $_wporg_number_of_heading );
?></output>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html( __( "Heading Tag", "gpt3-ai-content-generator" ) );
?></label>
                            <select name="_wporg_heading_tag" id="wpai_heading_tag">
                                <?php 
foreach ( $heading_tags as $label ) {
    ?>
                                    <option value="<?php 
    echo esc_attr( $label );
    ?>" <?php 
    echo ( esc_html( $_wporg_heading_tag ) == $label ? 'selected' : '' );
    ?>>
                                        <?php 
    echo esc_html( $label );
    ?>
                                    </option>
                                <?php 
}
?>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <input type="checkbox" id="wpai_modify_headings2" name="_wporg_modify_headings2" value="<?php 
echo ( esc_html( $_wporg_modify_headings ) == 1 ? "1" : "0" );
?>" <?php 
echo ( esc_html( $_wporg_modify_headings ) == 1 ? "checked" : "" );
?> />
                            <label><?php 
echo esc_html( __( "Outline Editor", "gpt3-ai-content-generator" ) );
?></label>
                            <input type="hidden" id="wpai_modify_headings" name="_wporg_modify_headings" value="<?php 
echo ( esc_html( $_wporg_modify_headings ) == 1 ? "1" : "0" );
?>" />
                            <input type="hidden" id="hfHeadings" name="hfHeadings" />
                            <input type="hidden" id="is_generate_continue" name="is_generate_continue" value='0' />
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings" ><?php 
echo esc_html__( 'SEO', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for SEO -->
                    <div class="submenu" style="display: none;">
                        <div class="nice-form-group">
                            <?php 
$_wpaicg_seo_meta_desc = get_option( '_wpaicg_seo_meta_desc', false );
?>
                            <input <?php 
echo ( $_wpaicg_seo_meta_desc ? ' checked' : '' );
?> type="checkbox" name="wpaicg_seo_meta_desc" id="wpaicg_seo_meta_desc" value="1" />
                            <label><?php 
echo esc_html__( 'Meta Description', 'gpt3-ai-content-generator' );
?></label>
                        </div>
                        <div class="nice-form-group">
                        <?php 
?>
                            <input type="checkbox" disabled id="wpai_add_keywords_bold" class="wpai-content-title-input" name="_wporg_add_keywords_bold" value="0">
                            <label><?php 
echo esc_html__( 'Bold Keywords', 'gpt3-ai-content-generator' );
?></label>
                            <a href="<?php 
echo esc_url( admin_url( 'admin.php?page=wpaicg-pricing' ) );
?>" class="pro-feature-label"><?php 
echo esc_html__( 'Pro', 'gpt3-ai-content-generator' );
?></a>
                            <?php 
?>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Keywords to Include', 'gpt3-ai-content-generator' );
?></label>
                            <?php 
if ( \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ) {
    ?>
                            <input type="text" id="wpai_keywords" placeholder="<?php 
    echo esc_html__( 'e.g. gpt, ai', 'gpt3-ai-content-generator' );
    ?>" name="_wporg_keywords" value="">
                            <small><?php 
    echo esc_html__( 'Use comma to separate keywords', 'gpt3-ai-content-generator' );
    ?></small>
                            <?php 
} else {
    ?>
                            <input type="text" disabled id="wpai_keywords" placeholder="<?php 
    echo esc_html__( 'Pro Feature', 'gpt3-ai-content-generator' );
    ?>" name="_wporg_keywords" value="">
                            <small><?php 
    echo esc_html__( 'Use comma to separate keywords', 'gpt3-ai-content-generator' );
    ?></small>
                            <?php 
}
?>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Keywords to Avoid', 'gpt3-ai-content-generator' );
?></label>
                            <?php 
if ( \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ) {
    ?>
                            <input type="text" id="wpai_words_to_avoid" placeholder="<?php 
    echo esc_html__( 'e.g. top, best', 'gpt3-ai-content-generator' );
    ?>" name="_wporg_words_to_avoid" value="">
                            <small><?php 
    echo esc_html__( 'Use comma to separate keywords', 'gpt3-ai-content-generator' );
    ?></small>
                            <?php 
} else {
    ?>
                            <input type="text" disabled id="wpai_words_to_avoid" placeholder="<?php 
    echo esc_html__( 'Pro Feature', 'gpt3-ai-content-generator' );
    ?>" name="_wporg_words_to_avoid" value="">
                            <small><?php 
    echo esc_html__( 'Use comma to separate keywords', 'gpt3-ai-content-generator' );
    ?></small>
                            <?php 
}
?>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Tags', 'gpt3-ai-content-generator' );
?></label>
                            <input type="text" name="wpaicg_post_tags" id="wpaicg_post_tags" value="" />
                            <small><?php 
echo esc_html__( 'Use comma to seperate tags', 'gpt3-ai-content-generator' );
?></small>
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings"><?php 
echo esc_html__( 'Image', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Image -->
                    <div class="submenu" style="display: none;">
                        <?php 
$wpaicg_pexels_api = get_option( 'wpaicg_pexels_api', '' );
$wpaicg_image_source = get_option( 'wpaicg_image_source', 'dalle3' );
$wpaicg_featured_image_source = get_option( 'wpaicg_featured_image_source', 'dalle3' );
$wpaicg_pexels_orientation = get_option( 'wpaicg_pexels_orientation', '' );
$wpaicg_pexels_size = get_option( 'wpaicg_pexels_size', '' );
$wpaicg_pexels_enable_prompt = get_option( 'wpaicg_pexels_enable_prompt', false );
$wpaicg_pexels_custom_prompt = get_option( 'wpaicg_pexels_custom_prompt', \WPAICG\WPAICG_Generator::get_instance()->wpaicg_pexels_custom_prompt );
$wpaicg_pixabay_api = get_option( 'wpaicg_pixabay_api', '' );
$wpaicg_pixabay_language = get_option( 'wpaicg_pixabay_language', 'en' );
$wpaicg_pixabay_type = get_option( 'wpaicg_pixabay_type', 'all' );
$wpaicg_pixabay_orientation = get_option( 'wpaicg_pixabay_orientation', 'all' );
$wpaicg_pixabay_order = get_option( 'wpaicg_pixabay_order', 'popular' );
$wpaicg_pixabay_enable_prompt = get_option( 'wpaicg_pixabay_enable_prompt', false );
$wpaicg_pixabay_custom_prompt = get_option( 'wpaicg_pixabay_custom_prompt', \WPAICG\WPAICG_Generator::get_instance()->wpaicg_pixabay_custom_prompt );
?>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Image', 'gpt3-ai-content-generator' );
?></label>
                            <select id="wpaicg_image_source" name="wpaicg_image_source" >
                                <option value=""><?php 
echo esc_html__( 'None', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( $wpaicg_image_source == 'dalle3' ? ' selected' : '' );
?> value="dalle3"><?php 
echo esc_html__( 'DALL-E 3', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( $wpaicg_image_source == 'dalle3hd' ? ' selected' : '' );
?> value="dalle3hd"><?php 
echo esc_html__( 'DALL-E 3 HD', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( $wpaicg_image_source == 'dalle' || $wpaicg_image_source == 'pexels' && empty( $wpaicg_pexels_api ) ? ' selected' : '' );
?> value="dalle"><?php 
echo esc_html__( 'DALL-E 2', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( !empty( $wpaicg_pexels_api ) && $wpaicg_image_source == 'pexels' ? ' selected' : '' );
echo ( empty( $wpaicg_pexels_api ) ? ' disabled' : '' );
?> value="pexels"><?php 
echo esc_html__( 'Pexels', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( !empty( $wpaicg_pixabay_api ) && $wpaicg_image_source == 'pixabay' ? ' selected' : '' );
echo ( empty( $wpaicg_pixabay_api ) ? ' disabled' : '' );
?> value="pixabay"><?php 
echo esc_html__( 'Pixabay', 'gpt3-ai-content-generator' );
?></option>
                                <!-- replicate -->
                                <option <?php 
echo ( $wpaicg_image_source == 'replicate' ? ' selected' : '' );
?> value="replicate"><?php 
echo esc_html__( 'Replicate', 'gpt3-ai-content-generator' );
?></option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Featured Image', 'gpt3-ai-content-generator' );
?></label>
                            <select id="wpaicg_featured_image_source" name="wpaicg_featured_image_source" >
                                <option value=""><?php 
echo esc_html__( 'None', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( $wpaicg_featured_image_source == 'dalle3' ? ' selected' : '' );
?> value="dalle3"><?php 
echo esc_html__( 'DALL-E 3', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( $wpaicg_featured_image_source == 'dalle3hd' ? ' selected' : '' );
?> value="dalle3hd"><?php 
echo esc_html__( 'DALL-E 3 HD', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( $wpaicg_featured_image_source == 'dalle' || $wpaicg_featured_image_source == 'pexels' && empty( $wpaicg_pexels_api ) ? ' selected' : '' );
?> value="dalle"><?php 
echo esc_html__( 'DALL-E 2', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( !empty( $wpaicg_pexels_api ) && $wpaicg_featured_image_source == 'pexels' ? ' selected' : '' );
echo ( empty( $wpaicg_pexels_api ) ? ' disabled' : '' );
?> value="pexels"><?php 
echo esc_html__( 'Pexels', 'gpt3-ai-content-generator' );
?></option>
                                <option <?php 
echo ( !empty( $wpaicg_pixabay_api ) && $wpaicg_featured_image_source == 'pixabay' ? ' selected' : '' );
echo ( empty( $wpaicg_pixabay_api ) ? ' disabled' : '' );
?> value="pixabay"><?php 
echo esc_html__( 'Pixabay', 'gpt3-ai-content-generator' );
?></option>
                                <!-- replicate -->
                                <option <?php 
echo ( $wpaicg_featured_image_source == 'replicate' ? ' selected' : '' );
?> value="replicate"><?php 
echo esc_html__( 'Replicate', 'gpt3-ai-content-generator' );
?></option>
                            </select>
                        </div>
                        <div class="nice-form-group" style="margin-bottom: 1em;">
                            <span class="smallbuttons" data-target="dalle"><?php 
echo esc_html__( 'DALL-E', 'gpt3-ai-content-generator' );
?></span>
                            <span class="smallbuttons" data-target="pexels"><?php 
echo esc_html__( 'Pexels', 'gpt3-ai-content-generator' );
?></span>
                            <span class="smallbuttons" data-target="pixabay"><?php 
echo esc_html__( 'Pixabay', 'gpt3-ai-content-generator' );
?></span>
                        </div>
                        <!-- Dall-E Settings -->
                        <div class="nice-form-group" id="dalle" style="display:none;">
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Image Size', 'gpt3-ai-content-generator' );
?></label>
                                <select id="_wporg_img_size" name="_wporg_img_size">
                                    <?php 
$_wporg_img_size = $result->img_size;
$sizes = \WPAICG\WPAICG_Util::get_instance()->wpaicg_image_sizes;
foreach ( $sizes as $size => $label ) {
    $selected = ( esc_html( $_wporg_img_size ) == $size ? 'selected' : '' );
    echo "<option value=\"{$size}\" {$selected}>{$label}</option>";
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                            <?php 
$_wporg_img_style = get_option( '_wpaicg_image_style', '' );
$image_style_options = \WPAICG\WPAICG_Util::get_instance()->wpaicg_image_styles;
?>
                                <label><?php 
echo esc_html__( 'Image Style', 'gpt3-ai-content-generator' );
?></label>
                                <select class="regular-text" id="_wporg_img_style" name="_wporg_img_style" >
                                    <?php 
foreach ( $image_style_options as $value => $label ) {
    $selected = ( esc_html( $_wporg_img_style ) == $value ? 'selected' : '' );
    echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
}
?>
                                </select>
                            </div>
                            <?php 
$wpaicg_art_file = WPAICG_PLUGIN_DIR . 'admin/data/art.json';
$wpaicg_painter_data = file_get_contents( $wpaicg_art_file );
$wpaicg_painter_data = json_decode( $wpaicg_painter_data, true );
$wpaicg_style_data = file_get_contents( $wpaicg_art_file );
$wpaicg_style_data = json_decode( $wpaicg_style_data, true );
$wpaicg_photo_file = WPAICG_PLUGIN_DIR . 'admin/data/photo.json';
$wpaicg_photo_data = file_get_contents( $wpaicg_photo_file );
$wpaicg_photo_data = json_decode( $wpaicg_photo_data, true );
$wpaicg_custom_image_settings = get_option( 'wpaicg_custom_image_settings', [] );
?>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Artist', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[artist]" id="artist">
                                    <?php 
foreach ( $wpaicg_painter_data['painters'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['artist'] ) && $wpaicg_custom_image_settings['artist'] == $value || (!isset( $wpaicg_custom_image_settings['artist'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Photography', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[photography_style]" id="photo">
                                    <?php 
foreach ( $wpaicg_photo_data['photography_style'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['photography_style'] ) && $wpaicg_custom_image_settings['photography_style'] == $value || (!isset( $wpaicg_custom_image_settings['photography_style'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Lighting', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[lighting]" id="lighting">
                                    <?php 
foreach ( $wpaicg_photo_data['lighting'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['lighting'] ) && $wpaicg_custom_image_settings['lighting'] == $value || (!isset( $wpaicg_custom_image_settings['lighting'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Subject', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[subject]" id="subject">
                                    <?php 
foreach ( $wpaicg_photo_data['subject'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['subject'] ) && $wpaicg_custom_image_settings['subject'] == $value || (!isset( $wpaicg_custom_image_settings['subject'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Camera', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[camera_settings]" id="camera_settings">
                                    <?php 
foreach ( $wpaicg_photo_data['camera_settings'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['camera_settings'] ) && $wpaicg_custom_image_settings['camera_settings'] == $value || (!isset( $wpaicg_custom_image_settings['camera_settings'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Composition', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[composition]" id="composition">
                                    <?php 
foreach ( $wpaicg_photo_data['composition'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['composition'] ) && $wpaicg_custom_image_settings['composition'] == $value || (!isset( $wpaicg_custom_image_settings['composition'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Resolution', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[resolution]" id="resolution">
                                    <?php 
foreach ( $wpaicg_photo_data['resolution'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['resolution'] ) && $wpaicg_custom_image_settings['resolution'] == $value || (!isset( $wpaicg_custom_image_settings['resolution'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Color', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[color]" id="color">
                                    <?php 
foreach ( $wpaicg_photo_data['color'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['color'] ) && $wpaicg_custom_image_settings['color'] == $value || (!isset( $wpaicg_custom_image_settings['color'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Special Effects', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_custom_image_settings[special_effects]" id="special_effects">
                                    <?php 
foreach ( $wpaicg_photo_data['special_effects'] as $key => $value ) {
    echo '<option' . (( isset( $wpaicg_custom_image_settings['special_effects'] ) && $wpaicg_custom_image_settings['special_effects'] == $value || (!isset( $wpaicg_custom_image_settings['special_effects'] ) && $value) == 'None' ? ' selected' : '' )) . ' value="' . esc_html( $value ) . '">' . esc_html( $value ) . '</option>';
}
?>
                                </select>
                            </div>
                        </div>
                        <!-- Pexels Settings -->
                        <div class="nice-form-group" id="pexels" style="display:none;">
                            <?php 
if ( empty( $wpaicg_pexels_api ) ) {
    ?>
                               <small><?php 
    echo esc_html__( 'Your Pexels api key is not set.', 'gpt3-ai-content-generator' );
    ?></small>
                            <?php 
}
?>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Orientation', 'gpt3-ai-content-generator' );
?></label>
                                <select id="wpaicg_pexels_orientation" name="wpaicg_pexels_orientation" >
                                    <option value=""><?php 
echo esc_html__( 'None', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pexels_orientation == 'landscape' ? ' selected' : '' );
?> value="landscape"><?php 
echo esc_html__( 'Landscape', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pexels_orientation == 'portrait' ? ' selected' : '' );
?> value="portrait"><?php 
echo esc_html__( 'Portrait', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pexels_orientation == 'square' ? ' selected' : '' );
?> value="square"><?php 
echo esc_html__( 'Square', 'gpt3-ai-content-generator' );
?></option>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Size', 'gpt3-ai-content-generator' );
?></label>
                                <select id="wpaicg_pexels_size" name="wpaicg_pexels_size" >
                                    <option value=""><?php 
echo esc_html__( 'None', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pexels_size == 'large' ? ' selected' : '' );
?> value="large"><?php 
echo esc_html__( 'Large', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pexels_size == 'medium' ? ' selected' : '' );
?> value="medium"><?php 
echo esc_html__( 'Medium', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pexels_size == 'small' ? ' selected' : '' );
?> value="small"><?php 
echo esc_html__( 'Small', 'gpt3-ai-content-generator' );
?></option>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <input <?php 
echo ( $wpaicg_pexels_enable_prompt ? ' checked' : '' );
?> type="checkbox" name="wpaicg_pexels_enable_prompt" value="1" id="wpaicg_pexels_enable_prompt">
                                <label><?php 
echo esc_html__( 'Use Keyword', 'gpt3-ai-content-generator' );
?></label>
                            </div>
                            <div class="wpaicg-mb-10 wpaicg_pexels_custom_prompt" style="display:none">
                                <label><?php 
echo esc_html__( 'Custom Prompt', 'gpt3-ai-content-generator' );
?>:<small ><?php 
echo sprintf( esc_html__( 'Ensure %s is included in your prompt.', 'gpt3-ai-content-generator' ), '<code>[title]</code>' );
?></small></label>
                                <textarea id="wpaicg_pexels_custom_prompt" rows="5" name="wpaicg_pexels_custom_prompt"><?php 
echo esc_html( $wpaicg_pexels_custom_prompt );
?></textarea>
                            </div>
                        </div>
                        <!-- Pixabay Settings -->
                        <div class="nice-form-group" id="pixabay" style="display:none;">
                            <?php 
if ( empty( $wpaicg_pixabay_api ) ) {
    ?>
                               <small><?php 
    echo esc_html__( 'Your Pixabay api key is not set.', 'gpt3-ai-content-generator' );
    ?></small>
                            <?php 
}
?>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Language', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_pixabay_language" id="wpaicg_pixabay_language">
                                    <?php 
foreach ( \WPAICG\WPAICG_Generator::get_instance()->pixabay_languages as $key => $pixabay_language ) {
    echo '<option' . (( $wpaicg_pixabay_language == $key ? ' selected' : '' )) . ' value="' . esc_html( $key ) . '">' . esc_html( $pixabay_language ) . '</option>';
}
?>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Image Type', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_pixabay_type" id="wpaicg_pixabay_type">
                                    <option <?php 
echo ( $wpaicg_pixabay_type == 'all' ? ' selected' : '' );
?> value="all"><?php 
echo esc_html__( 'All', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pixabay_type == 'photo' ? ' selected' : '' );
?> value="photo"><?php 
echo esc_html__( 'Photo', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pixabay_type == 'illustration' ? ' selected' : '' );
?> value="illustration"><?php 
echo esc_html__( 'Illustration', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pixabay_type == 'vector' ? ' selected' : '' );
?> value="vector"><?php 
echo esc_html__( 'Vector', 'gpt3-ai-content-generator' );
?></option>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Orientation', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_pixabay_orientation" id="wpaicg_pixabay_orientation" >
                                    <option <?php 
echo ( $wpaicg_pixabay_orientation == 'all' ? ' selected' : '' );
?> value="all"><?php 
echo esc_html__( 'All', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pixabay_orientation == 'horizontal' ? ' selected' : '' );
?> value="horizontal"><?php 
echo esc_html__( 'Horizontal', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pixabay_orientation == 'vertical' ? ' selected' : '' );
?> value="vertical"><?php 
echo esc_html__( 'Vertical', 'gpt3-ai-content-generator' );
?></option>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <label><?php 
echo esc_html__( 'Order', 'gpt3-ai-content-generator' );
?></label>
                                <select name="wpaicg_pixabay_order" id="wpaicg_pixabay_order">
                                    <option <?php 
echo ( $wpaicg_pixabay_order == 'popular' ? ' selected' : '' );
?> value="popular"><?php 
echo esc_html__( 'Popular', 'gpt3-ai-content-generator' );
?></option>
                                    <option <?php 
echo ( $wpaicg_pixabay_order == 'latest' ? ' selected' : '' );
?> value="latest"><?php 
echo esc_html__( 'Latest', 'gpt3-ai-content-generator' );
?></option>
                                </select>
                            </div>
                            <div class="nice-form-group">
                                <input <?php 
echo ( $wpaicg_pixabay_enable_prompt ? ' checked' : '' );
?> type="checkbox" name="wpaicg_pixabay_enable_prompt" value="1" id="wpaicg_pixabay_enable_prompt">
                                <label><?php 
echo esc_html__( 'Use Keyword', 'gpt3-ai-content-generator' );
?></label>
                            </div>
                            <div class="wpaicg-mb-10 wpaicg_pixabay_custom_prompt" style="display:none">
                                <label><?php 
echo esc_html__( 'Custom Prompt', 'gpt3-ai-content-generator' );
?>:<small><?php 
echo sprintf( esc_html__( 'Ensure %s is included in your prompt.', 'gpt3-ai-content-generator' ), '<code>[title]</code>' );
?></small></label>
                                <textarea id="wpaicg_pixabay_custom_prompt" rows="5" name="wpaicg_pixabay_custom_prompt"><?php 
echo esc_html( $wpaicg_pixabay_custom_prompt );
?></textarea>
                            </div>
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings"><?php 
echo esc_html__( 'Additional Content', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Additional Content -->
                    <div class="submenu" style="display: none;">
                        <?php 
$wpaicg_hide_conclusion = get_option( 'wpaicg_hide_conclusion', false );
$wpaicg_hide_introduction = get_option( 'wpaicg_hide_introduction', false );
$wpaicg_intro_title_tag = get_option( 'wpaicg_intro_title_tag', 'h2' );
$wpaicg_conclusion_title_tag = get_option( 'wpaicg_conclusion_title_tag', 'h2' );
$wpaicg_toc = get_option( 'wpaicg_toc', false );
$wpaicg_toc_title = get_option( 'wpaicg_toc_title', esc_html__( 'Table of Contents', 'gpt3-ai-content-generator' ) );
$wpaicg_toc_title_tag = get_option( 'wpaicg_toc_title_tag', 'h2' );
?>
                        <div class="nice-form-group">
                            <input type="checkbox" id="wpai_add_tagline2"  name="_wporg_add_tagline2" value="<?php 
echo ( esc_html( $_wporg_add_tagline ) == 1 ? "1" : "0" );
?>" <?php 
echo ( esc_html( $_wporg_add_tagline ) == 1 ? "checked" : "" );
?> />
                            <label><?php 
echo esc_html__( 'Tagline', 'gpt3-ai-content-generator' );
?></label>
                            <input type="hidden" id="wpai_add_tagline" name="_wporg_add_tagline" value="<?php 
echo ( esc_html( $_wporg_add_tagline ) == 1 ? "1" : "0" );
?>" />
                        </div>
                        <div class="nice-form-group">
                            <input type="checkbox" id="wpai_add_intro2" name="_wporg_add_intro2" value="<?php 
echo ( esc_html( $_wporg_add_intro ) == 1 ? "1" : "0" );
?>" <?php 
echo ( esc_html( $_wporg_add_intro ) == 1 ? "checked" : "" );
?> />
                            <label><?php 
echo esc_html__( 'Introduction', 'gpt3-ai-content-generator' );
?></label>
                            <input type="hidden" id="wpai_add_intro" name="_wporg_add_intro" value="<?php 
echo ( esc_html( $_wporg_add_intro ) == 1 ? "1" : "0" );
?>" />
                        </div>
                        <div class="nice-form-group">
                            <input type="checkbox" id="wpaicg_hide_introduction" name="wpaicg_hide_introduction" value="1"<?php 
echo ( $wpaicg_hide_introduction ? " checked" : "" );
?>/>
                            <label><?php 
echo esc_html__( 'Hide Introduction Title', 'gpt3-ai-content-generator' );
?></label>
                        </div>
                        <div class="nice-form-group">
                            <input type="checkbox" id="wpai_add_conclusion2" name="_wporg_add_conclusion2" value="<?php 
echo ( esc_html( $_wporg_add_conclusion ) == 1 ? "1" : "0" );
?>" <?php 
echo ( esc_html( $_wporg_add_conclusion ) == 1 ? "checked" : "" );
?> />
                            <label><?php 
echo esc_html__( 'Conclusion', 'gpt3-ai-content-generator' );
?></label>
                            <input type="hidden" id="wpai_add_conclusion" name="_wporg_add_conclusion" value="<?php 
echo ( esc_html( $_wporg_add_conclusion ) == 1 ? "1" : "0" );
?>" />
                        </div>
                        <div class="nice-form-group">
                            <input type="checkbox" id="wpaicg_hide_conclusion" name="wpaicg_hide_conclusion" value="1"<?php 
echo ( $wpaicg_hide_conclusion ? " checked" : "" );
?>/>
                            <label><?php 
echo esc_html__( 'Hide Conclusion Title', 'gpt3-ai-content-generator' );
?></label>
                        </div>
                        <div class="nice-form-group">
                            <input <?php 
echo ( $wpaicg_toc ? ' checked' : '' );
?> type="checkbox" value="1" name="wpaicg_toc" id="wpaicg_toc">
                            <label><?php 
echo esc_html__( 'Table of Contents', 'gpt3-ai-content-generator' );
?></label>
                        </div>
                        <div class="nice-form-group">
                        <?php 
?>
                            <input type="checkbox" value="0" disabled>
                            <label><?php 
echo esc_html__( 'Q & A', 'gpt3-ai-content-generator' );
?></label>
                            <a href="<?php 
echo esc_url( admin_url( 'admin.php?page=wpaicg-pricing' ) );
?>" class="pro-feature-label"><?php 
echo esc_html__( 'Pro', 'gpt3-ai-content-generator' );
?></a>
                            <?php 
?>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Intro Title Tag', 'gpt3-ai-content-generator' );
?></label>
                            <select name="wpaicg_intro_title_tag" id="wpaicg_intro_title_tag">
                                <option value="h1" <?php 
echo ( esc_html( $wpaicg_intro_title_tag ) == 'h1' ? 'selected' : '' );
?>>h1</option>
                                <option value="h2" <?php 
echo ( esc_html( $wpaicg_intro_title_tag ) == 'h2' ? 'selected' : '' );
?>>h2</option>
                                <option value="h3" <?php 
echo ( esc_html( $wpaicg_intro_title_tag ) == 'h3' ? 'selected' : '' );
?>>h3</option>
                                <option value="h4" <?php 
echo ( esc_html( $wpaicg_intro_title_tag ) == 'h4' ? 'selected' : '' );
?>>h4</option>
                                <option value="h5" <?php 
echo ( esc_html( $wpaicg_intro_title_tag ) == 'h5' ? 'selected' : '' );
?>>h5</option>
                                <option value="h6" <?php 
echo ( esc_html( $wpaicg_intro_title_tag ) == 'h6' ? 'selected' : '' );
?>>h6</option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Conclusion Title Tag', 'gpt3-ai-content-generator' );
?></label>
                            <select name="wpaicg_conclusion_title_tag" id="wpaicg_conclusion_title_tag">
                                <option value="h1" <?php 
echo ( esc_html( $wpaicg_conclusion_title_tag ) == 'h1' ? 'selected' : '' );
?>>h1</option>
                                <option value="h2" <?php 
echo ( esc_html( $wpaicg_conclusion_title_tag ) == 'h2' ? 'selected' : '' );
?>>h2</option>
                                <option value="h3" <?php 
echo ( esc_html( $wpaicg_conclusion_title_tag ) == 'h3' ? 'selected' : '' );
?>>h3</option>
                                <option value="h4" <?php 
echo ( esc_html( $wpaicg_conclusion_title_tag ) == 'h4' ? 'selected' : '' );
?>>h4</option>
                                <option value="h5" <?php 
echo ( esc_html( $wpaicg_conclusion_title_tag ) == 'h5' ? 'selected' : '' );
?>>h5</option>
                                <option value="h6" <?php 
echo ( esc_html( $wpaicg_conclusion_title_tag ) == 'h6' ? 'selected' : '' );
?>>h6</option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'ToC Title Tag', 'gpt3-ai-content-generator' );
?></label>
                            <select name="wpaicg_toc_title_tag" id="wpaicg_toc_title_tag">
                                <option value="h1" <?php 
echo ( esc_html( $wpaicg_toc_title_tag ) == 'h1' ? 'selected' : '' );
?>>h1</option>
                                <option value="h2" <?php 
echo ( esc_html( $wpaicg_toc_title_tag ) == 'h2' ? 'selected' : '' );
?>>h2</option>
                                <option value="h3" <?php 
echo ( esc_html( $wpaicg_toc_title_tag ) == 'h3' ? 'selected' : '' );
?>>h3</option>
                                <option value="h4" <?php 
echo ( esc_html( $wpaicg_toc_title_tag ) == 'h4' ? 'selected' : '' );
?>>h4</option>
                                <option value="h5" <?php 
echo ( esc_html( $wpaicg_toc_title_tag ) == 'h5' ? 'selected' : '' );
?>>h5</option>
                                <option value="h6" <?php 
echo ( esc_html( $wpaicg_toc_title_tag ) == 'h6' ? 'selected' : '' );
?>>h6</option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'ToC Title', 'gpt3-ai-content-generator' );
?></label>
                            <input type="text" value="<?php 
echo esc_html( $wpaicg_toc_title );
?>" name="wpaicg_toc_title" id="wpaicg_toc_title">
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings"><?php 
echo esc_html__( 'Links', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Links -->
                    <div class="submenu" style="display: none;">
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Anchor Text', 'gpt3-ai-content-generator' );
?></label>
                            <input type="text" id="wpai_anchor_text" name="_wporg_anchor_text" value="<?php 
echo esc_html( $_wporg_anchor_text );
?>">
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Target URL', 'gpt3-ai-content-generator' );
?></label>
                            <input type="url" id="wpai_target_url" placeholder="https://..." name="_wporg_target_url" value="<?php 
echo esc_html( $_wporg_target_url );
?>">
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Call-to-Action', 'gpt3-ai-content-generator' );
?></label>
                            <input type="url" id="wpai_target_url_cta" placeholder="https://..." name="_wporg_target_url_cta" value="<?php 
echo esc_html( $_wporg_target_url_cta );
?>">
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'CTA Position', 'gpt3-ai-content-generator' );
?></label>
                            <select name="_wporg_cta_pos" id="wpai_cta_pos">
                                <option value="beg" <?php 
echo ( esc_html( $_wporg_cta_pos ) == 'beg' ? 'selected' : '' );
?>><?php 
echo esc_html__( 'Beginning', 'gpt3-ai-content-generator' );
?></option>
                                <option value="end" <?php 
echo ( esc_html( $_wporg_cta_pos ) == 'end' ? 'selected' : '' );
?>><?php 
echo esc_html__( 'End', 'gpt3-ai-content-generator' );
?></option>
                            </select>
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings"><?php 
echo esc_html__( 'Custom Prompt', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Custom Prompt -->
                    <?php 
$wpaicg_content_custom_prompt_enable = get_option( 'wpaicg_content_custom_prompt_enable', false );
$wpaicg_content_custom_prompt = get_option( 'wpaicg_content_custom_prompt', '' );
if ( empty( $wpaicg_content_custom_prompt ) ) {
    $wpaicg_content_custom_prompt = \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_prompt;
}
?>
                    <div class="submenu" style="display: none;">
                        <div class="nice-form-group">
                            <input <?php 
echo ( $wpaicg_content_custom_prompt_enable ? ' checked' : '' );
?> type="checkbox" class="wpaicg_meta_custom_prompt_enable" name="wpaicg_custom_prompt_enable">
                            <label><?php 
echo esc_html__( 'Custom Prompt', 'gpt3-ai-content-generator' );
?></label>
                        </div>
                        <div class="nice-form-group">
                            <div class="wpaicg_meta_custom_prompt_box" style="<?php 
echo ( isset( $wpaicg_content_custom_prompt_enable ) && $wpaicg_content_custom_prompt_enable ? '' : 'display:none' );
?>">
                                <textarea rows="20" class="wpaicg_meta_custom_prompt" name="wpaicg_custom_prompt"><?php 
echo esc_html( str_replace( "\\", '', $wpaicg_content_custom_prompt ) );
?></textarea>
                                <?php 
if ( \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ) {
    ?>
                                    <small class="smallnotes">Make sure to include <code>[title]</code> your prompt. You can also add <code>[keywords_to_include]</code> and <code>[keywords_to_avoid]</code> to further customize your prompt.</small>
                                <?php 
} else {
    ?>
                                    <small class="smallnotes">Make sure <code>[title]</code> is included in your prompt.</small>
                                <?php 
}
?>
                                <button style="color: #fff;background: #df0707;border-color: #df0707;" data-prompt="<?php 
echo esc_html( \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_prompt );
?>" class="button wpaicg_meta_custom_prompt_reset" type="button">
                                    <?php 
echo esc_html__( 'Reset', 'gpt3-ai-content-generator' );
?>
                                </button>
                                <div class="wpaicg_meta_custom_prompt_auto_error"></div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
    <!-- Right Navigation for Custom Mode -->
    <div class="content-writer-right-navigation" id="right-nav-custom" style="display: none;">
        <nav>
            <ul>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings" ><?php 
echo esc_html__( 'Template', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Template -->
                    <div class="submenu">
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Template', 'gpt3-ai-content-generator' );
?></label>
                            <select id="wpaicg_custom_template_select">
                                <?php 
foreach ( $wpaicg_templates as $key => $wpaicg_template ) {
    echo '<option' . (( isset( $selected_template ) && $selected_template == $key ? ' selected' : '' )) . ' class="wpaicg_custom_template_' . esc_html( $key ) . '" data-parameters="' . esc_html( json_encode( $wpaicg_template['content'], JSON_UNESCAPED_UNICODE ) ) . '" value="' . esc_html( $key ) . '">' . esc_html( $wpaicg_template['title'] ) . '</option>';
}
?>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Name', 'gpt3-ai-content-generator' );
?></label>
                            <input value="<?php 
echo esc_html( $default_name );
?>" type="text" class="wpaicg_custom_template_title" name="title" placeholder="<?php 
echo esc_attr__( 'Enter a Template Name', 'gpt3-ai-content-generator' );
?>">
                            <?php 
if ( isset( $selected_template ) && !empty( $selected_template ) ) {
    ?>
                                <input class="wpaicg_custom_template_id" type="hidden" name="id" value="<?php 
    echo esc_html( $selected_template );
    ?>">
                            <?php 
}
?>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Type', 'gpt3-ai-content-generator' );
?></label>
                            <select name="template[post_type]" class="wpaicg_custom_template_post_type">
                                <?php 
$args = array(
    'public'   => true,
    '_builtin' => false,
);
$post_types = get_post_types( $args );
$post_types = array_merge( $post_types, ['post', 'page'] );
// to include post and page
foreach ( $post_types as $post_type ) {
    $selected = ( isset( $wpaicg_parameters['post_type'] ) && $wpaicg_parameters['post_type'] == $post_type ? ' selected' : '' );
    echo '<option value="' . esc_html( $post_type ) . '"' . $selected . '>' . esc_html( ucfirst( $post_type ) ) . '</option>';
}
?>
                            </select>
                        </div>
                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings" ><?php 
echo esc_html__( 'Parameters', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Parameters -->
                    <div class="submenu" style="display: none;">
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Provider', 'gpt3-ai-content-generator' );
?></label>
                            <select name="template[provider]" class="wpaicg_custom_template_provider">
                                <option value="openai" selected>OpenAI</option>
                                <option value="google">Google</option>
                                <option value="openrouter">OpenRouter</option>
                                <option value="azure">Microsoft</option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <label><?php 
echo esc_html__( 'Model', 'gpt3-ai-content-generator' );
?></label>
                        </div>
                        <div class="nice-form-group">
                            <!-- Display dropdown for OpenAI -->
                            <select name="template[model]" class="wpaicg_custom_template_model">
                                <?php 
// Function to display options
function display_options(  $models, $selected_model  ) {
    foreach ( $models as $model_key => $model_name ) {
        ?>
                                        <option value="<?php 
        echo esc_attr( $model_key );
        ?>"<?php 
        selected( $model_key, $selected_model );
        ?>><?php 
        echo esc_html( $model_name );
        ?></option>
                                    <?php 
    }
}

?>
                                <optgroup label="GPT-4"><?php 
display_options( $gpt4_models, $wpaicg_parameters['model'] );
?></optgroup>
                                <optgroup label="GPT-3.5"><?php 
display_options( $gpt35_models, $wpaicg_parameters['model'] );
?></optgroup>
                                <optgroup label="Custom Models"><?php 
display_options( $custom_models, $wpaicg_parameters['model'] );
?></optgroup>
                            </select>
                        </div>
                        <div class="nice-form-group" style="display:none;" id="azure-deployment-field">
                            <input type="text" name="template[azure_deployment]" class="wpaicg_custom_template_azure_deployment" value="<?php 
echo esc_attr( $wpaicg_parameters['azure_deployment'] );
?>">
                        </div>
                        <!-- Google Models Dropdown -->
                        <div class="nice-form-group" style="display:none;" id="google-models-field">
                            <?php 
$wpaicg_google_model_list = get_option( 'wpaicg_google_model_list', ['gemini-pro'] );
$template_google_model = get_option( 'template[google_model]', 'gemini-pro' );
?>
                            <select name="template[google_model]" class="wpaicg_custom_template_google_model regular-text">
                                <?php 
if ( isset( $wpaicg_google_model_list ) && is_array( $wpaicg_google_model_list ) && count( $wpaicg_google_model_list ) > 0 ) {
    foreach ( $wpaicg_google_model_list as $model ) {
        // Define words that trigger disabling the option
        $disabledWords = ['vision'];
        // Add words that should disable the option
        $shouldBeDisabled = false;
        foreach ( $disabledWords as $word ) {
            if ( strpos( $model, $word ) !== false ) {
                $shouldBeDisabled = true;
                break;
                // Break the loop if any word is found
            }
        }
        ?>
                                    <option value="<?php 
        echo esc_attr( $model );
        ?>" <?php 
        selected( $model, $template_google_model );
        ?> <?php 
        echo ( $shouldBeDisabled ? 'disabled' : '' );
        ?>>
                                        <?php 
        echo esc_html( ucwords( str_replace( '-', ' ', $model ) ) );
        // Convert save format to display format
        ?>
                                    </option>
                                <?php 
    }
} else {
    ?>
                            <option value="gemini-pro" <?php 
    selected( 'gemini-pro', $template_google_model );
    ?>><?php 
    echo esc_html__( 'Gemini Pro', 'gpt3-ai-content-generator' );
    ?></option>
                            <?php 
}
?>
                            </select>
                        </div>

                        <!-- OpenRouter Models Dropdown -->
                        <div class="nice-form-group" style="display:none;" id="openrouter-models-field">
                            <?php 
$openrouter_models = get_option( 'wpaicg_openrouter_model_list', [] );
$template_openrouter_model = get_option( 'template[openrouter_model]', '' );
// Group the models by provider
$grouped_models = [];
foreach ( $openrouter_models as $model ) {
    $provider = explode( '/', $model['id'] )[0];
    // Extract the provider name
    if ( !isset( $grouped_models[$provider] ) ) {
        $grouped_models[$provider] = [];
    }
    $grouped_models[$provider][] = $model;
}
// Sort the providers alphabetically
ksort( $grouped_models );
?>
                            <select name="template[openrouter_model]" class="wpaicg_custom_template_openrouter_model regular-text">
                                <?php 
foreach ( $grouped_models as $provider => $models ) {
    ?>
                                    <optgroup label="<?php 
    echo esc_attr( $provider );
    ?>">
                                        <?php 
    // Sort the models alphabetically by name within each provider
    usort( $models, function ( $a, $b ) {
        return strcmp( $a["name"], $b["name"] );
    } );
    foreach ( $models as $model ) {
        ?>
                                            <option value="<?php 
        echo esc_attr( $model['id'] );
        ?>" <?php 
        selected( $model['id'], $template_openrouter_model );
        ?>>
                                                <?php 
        echo esc_html( $model['name'] );
        ?>
                                            </option>
                                        <?php 
    }
    ?>
                                    </optgroup>
                                <?php 
}
?>
                            </select>
                        </div>

                        <?php 
$labels = array(
    'temperature'       => esc_html__( 'Temperature', 'gpt3-ai-content-generator' ),
    'max_tokens'        => esc_html__( 'Maximum Length', 'gpt3-ai-content-generator' ),
    'top_p'             => esc_html__( 'Top P', 'gpt3-ai-content-generator' ),
    'frequency_penalty' => esc_html__( 'Frequency Penalty', 'gpt3-ai-content-generator' ),
    'presence_penalty'  => esc_html__( 'Presence Penalty', 'gpt3-ai-content-generator' ),
);
foreach ( $labels as $item => $label ) {
    ?>
                                <div class="nice-form-group">
                                    <label><?php 
    echo esc_html( $label );
    ?></label>
                                    <input type="text" value="<?php 
    echo esc_attr( $wpaicg_parameters[$item] );
    ?>" class="wpaicg_custom_template_<?php 
    echo esc_attr( $item );
    ?>" name="template[<?php 
    echo esc_attr( $item );
    ?>]">
                                </div>
                                <?php 
}
?>

                    </div>
                </li>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings" ><?php 
echo esc_html__( 'Prompts', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Prompts -->
                    <div class="submenu" style="display: none;">
                    <div class="wpaicg-custom-parameters">
                        <div class="nice-form-group">
                            <div class="wpaicg-custom-parameters-content">
                                <div class="nice-form-group">
                                    <label><?php 
echo esc_html__( 'Prompt for Title', 'gpt3-ai-content-generator' );
?></label>
                                    <textarea class="wpaicg_custom_template_prompt_title" name="template[prompt_title]" rows="3"><?php 
echo esc_html( $wpaicg_parameters['prompt_title'] );
?></textarea>
                                    <small class="smallnotes">Ensure <code>[count]</code> and <code>[topic]</code> is included in your prompt.</small>
                                </div>
                                <div class="nice-form-group">
                                    <label><?php 
echo esc_html__( 'Prompt for Sections', 'gpt3-ai-content-generator' );
?></label>
                                    <textarea class="wpaicg_custom_template_prompt_section" name="template[prompt_section]" rows="5"><?php 
echo esc_html( $wpaicg_parameters['prompt_section'] );
?></textarea>
                                    <small class="smallnotes">Ensure <code>[count]</code> and <code>[title]</code> is included in your prompt.</small>
                                </div>
                                <div class="nice-form-group">
                                    <label><?php 
echo esc_html__( 'Prompt for Content', 'gpt3-ai-content-generator' );
?></label>
                                    <textarea class="wpaicg_custom_template_prompt_content" name="template[prompt_content]" rows="15"><?php 
echo esc_html( $wpaicg_parameters['prompt_content'] );
?></textarea>
                                    <small class="smallnotes">Ensure <code>[title]</code>, <code>[sections]</code> and <code>[count]</code> is included in your prompt.</small>
                                </div>
                                <div class="nice-form-group">
                                    <label><?php 
echo esc_html__( 'Prompt for Excerpt', 'gpt3-ai-content-generator' );
?></label>
                                    <textarea class="wpaicg_custom_template_prompt_excerpt" name="template[prompt_excerpt]" rows="3"><?php 
echo esc_html( $wpaicg_parameters['prompt_excerpt'] );
?></textarea>
                                    <small class="smallnotes">Ensure <code>[title]</code> is included in your prompt.</small>
                                </div>
                                <div class="nice-form-group">
                                    <label><?php 
echo esc_html__( 'Prompt for Meta', 'gpt3-ai-content-generator' );
?></label>
                                    <textarea class="wpaicg_custom_template_prompt_meta" name="template[prompt_meta]" rows="3"><?php 
echo esc_html( $wpaicg_parameters['prompt_meta'] );
?></textarea>
                                    <small class="smallnotes">Ensure <code>[title]</code> is included in your prompt.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </li>
            </ul>
            <div style="display: flex;justify-content: space-between">
                <div>
                    <button style="<?php 
echo ( isset( $selected_template ) ? '' : 'display:none' );
?>" type="button" class="button button-primary wpaicg_template_update"><?php 
echo esc_html__( 'Update', 'gpt3-ai-content-generator' );
?></button>
                    <button type="button" class="button button-primary wpaicg_template_save"><?php 
echo esc_html__( 'Save', 'gpt3-ai-content-generator' );
?></button>
                    <button type="button" class="button button-link-delete wpaicg_template_delete" style="<?php 
echo ( isset( $selected_template ) ? '' : 'display:none' );
?>"><?php 
echo esc_html__( 'Delete', 'gpt3-ai-content-generator' );
?></button>
                </div>
            </div>
        </nav>
    </div>
    <!-- Right Navigation for Playground -->
    <div class="content-writer-right-navigation" id="right-nav-playground" style="display: none;">
        <nav>
            <ul>
                <li>
                    <a href="javascript:void(0)" class="advanced-settings" ><?php 
echo esc_html__( 'Settings', 'gpt3-ai-content-generator' );
?></a>
                    <!-- Submenu for Language, Style and Tone -->
                    <div class="submenu">
                        <div class="nice-form-group">
                            <select id="provider_select">
                                <option value="openai" selected><?php 
echo esc_html__( 'OpenAI', 'gpt3-ai-content-generator' );
?></option>
                                <option value="google"><?php 
echo esc_html__( 'Google', 'gpt3-ai-content-generator' );
?></option>
                                <option value="openrouter"><?php 
echo esc_html__( 'OpenRouter', 'gpt3-ai-content-generator' );
?></option>
                                <option value="togetherai"><?php 
echo esc_html__( 'Together AI', 'gpt3-ai-content-generator' );
?></option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <span id="add_togetherai_api_key_link" style="display: none; cursor: pointer; color: blue; text-decoration: underline;">Add your Together AI API key</span>
                        </div>
                        <div id="api_key_field_togetherai" style="display: none;">
                            <div class="nice-form-group">
                                <input type="text" id="togetherai_api_key" placeholder="Enter your Together AI API key" value="<?php 
echo esc_attr( get_option( 'wpaicg_togetherai_model_api_key' ) );
?>">
                            </div>
                            <div class="nice-form-group">
                                <button id="save_togetherai_api_key_button" class="button">Save</button>
                            </div>
                            <div class="nice-form-group">
                                <a href="https://api.together.xyz/settings/api-keys" target="_blank">Get your API key</a>
                            </div>
                        </div>
                        <div class="nice-form-group">
                            <select id="model_select">
                                <!-- Options will be populated dynamically by JavaScript -->
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <select id="category_select">
                                <?php 
$playground_categories = \WPAICG\WPAICG_Util::get_instance()->playground_categories;
?>
                                <?php 
foreach ( $playground_categories as $value => $label ) {
    ?>
                                    <option value="<?php 
    echo esc_attr( $value );
    ?>"><?php 
    echo esc_html__( $label, 'gpt3-ai-content-generator' );
    ?></option>
                                <?php 
}
?>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <select id="sample_prompts">
                                <option value=""><?php 
echo esc_html__( 'Select a prompt', 'gpt3-ai-content-generator' );
?></option>
                            </select>
                        </div>
                        <div class="nice-form-group">
                            <textarea type="text" rows="10" class="wpaicg_prompt"><?php 
echo esc_html__( 'Write a blog post on how to effectively monetize a blog, discussing various methods such as affiliate marketing, sponsored content, and display advertising, as well as tips for maximizing revenue.', 'gpt3-ai-content-generator' );
?></textarea>
                        </div>
                        <div class="nice-form-group">
                        <button class="button button-primary wpaicg_generator_button"><span class="spinner"></span><?php 
echo esc_html__( 'Generate', 'gpt3-ai-content-generator' );
?></button>
                &nbsp;<button class="button button-primary wpaicg_generator_stop"><?php 
echo esc_html__( 'Stop', 'gpt3-ai-content-generator' );
?></button>
                        </div>
                    </div>
                </li>
            </ul>
        </nav>
    </div>
</div>
<script>
    // Right navigation menu
    document.addEventListener("DOMContentLoaded", function () {
    // Get all left navigation links
    var leftNavLinks = document.querySelectorAll('.content-writer-master-navigation a');

    // Function to hide all right navigation menus
    function hideAllRightNavs() {
        document.querySelectorAll('.content-writer-right-navigation').forEach(function (nav) {
        nav.style.display = 'none';
        });
    }

    // Function to show a right navigation menu
    function showRightNav(navId) {
        var rightNav = document.getElementById(navId);
        if (rightNav) {
        rightNav.style.display = 'block';
        }
    }

    // Initialize by hiding all right navs and showing the first one
    hideAllRightNavs();
    showRightNav('right-nav-express'); // Replace with the ID of your first right nav

    // Add click event to all left navigation links
    leftNavLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = this.getAttribute('href').replace('#', '');
        // Hide all right navs
        hideAllRightNavs();
        // Show the right nav associated with the clicked left nav item
        showRightNav('right-nav-' + targetId);
        });
    });
    });

</script>
<script>
    // Submenu functionality
    document.addEventListener("DOMContentLoaded", function () {
        const menuItems = document.querySelectorAll('.content-writer-right-navigation > nav > ul > li > a');

        function closeAllSubmenus() {
            document.querySelectorAll('.submenu').forEach(function (submenu) {
                submenu.style.display = 'none';
            });
        }

        // Open the first submenu by default
        const firstSubmenu = document.querySelector('.content-writer-right-navigation .submenu');
        if (firstSubmenu) {
            firstSubmenu.style.display = 'block';
        }

        menuItems.forEach(function (menuItem) {
            menuItem.addEventListener('click', function () {
                // If the clicked submenu is already open, do nothing
                var submenu = this.nextElementSibling;
                if (submenu.style.display === 'block') {
                    return;
                }

                // Close all submenus
                closeAllSubmenus();

                // Open the clicked submenu
                submenu.style.display = 'block';
            });
        });
    });
</script>
<script>
    // Tab navigation
    document.addEventListener("DOMContentLoaded", function () {

        // 1. TAB NAVIGATION
        const tabs = document.querySelectorAll('.content-writer-master-navigation ul li a');
        const contentSections = document.querySelectorAll('.content-writer-master-content section');

        // Initially hide all sections (This part is handled by CSS now, you can choose to keep or remove these lines)
        contentSections.forEach((section, index) => {
            section.style.display = 'none';
        });

        // Explicitly show the first tab content and set the first tab as active
        if (contentSections.length > 0) {
            contentSections[0].style.display = 'block';
        }
        if (tabs.length > 0) {
            tabs[0].parentElement.classList.add('active');
        }

        // Tab click event
        tabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();

                const targetId = this.getAttribute('href').replace('#', '');
                const targetContent = document.getElementById(targetId);

                contentSections.forEach(section => {
                    section.style.display = 'none';
                });

                targetContent.parentElement.style.display = 'block';

                tabs.forEach(t => {
                    t.parentElement.classList.remove('active');
                });
                this.parentElement.classList.add('active');
            });
        });
    });
</script>
<script>
    jQuery("#wpai_modify_headings2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_modify_headings').attr('value', 1);
        else
            jQuery('#wpai_modify_headings').attr('value', 0);
    });

    jQuery("#wpai_add_img2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_add_img').attr('value', 1);
        else
            jQuery('#wpai_add_img').attr('value', 0);
    });

    jQuery("#wpai_add_tagline2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_add_tagline').attr('value', 1);
        else
            jQuery('#wpai_add_tagline').attr('value', 0);
    });

    jQuery("#wpai_add_intro2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_add_intro').attr('value', 1);
        else
            jQuery('#wpai_add_intro').attr('value', 0);
    });

    jQuery("#wpai_add_faq2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_add_faq').attr('value', 1);
        else
            jQuery('#wpai_add_faq').attr('value', 0);
    });

    jQuery("#wpai_add_conclusion2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_add_conclusion').attr('value', 1);
        else
            jQuery('#wpai_add_conclusion').attr('value', 0);
    });

    jQuery("#wpai_add_keywords_bold2").change(function()
    {
        if(this.checked)
            jQuery('#wpai_add_keywords_bold').attr('value', 1);
        else
            jQuery('#wpai_add_keywords_bold').attr('value', 0);
    });

    jQuery(".m_generate").on("click", function(e)
    {
        var menuholder = new Array();
        var menuholder2 = new Array();

        var menu_data = jQuery(".wpcgai_menu_editor").children();
        var firstli = menu_data;

        firstli.each(function ()
        {
            var menus_html = jQuery(this).children();

            var identifier = jQuery(this).find("#identifier").text();
            var text = jQuery(this).find("#text").val();

            if(text == '')
            {
                menuholder = new Array();
                menuholder2 = new Array();
                alert('<?php 
echo esc_html__( 'Heading input can not be blank!', 'gpt3-ai-content-generator' );
?>');
            }
            else
            {
                var menuObj = new Object();
                menuObj['Identifier'] = identifier;
                menuObj['Text'] = text;

                menuholder.push(menuObj);
                menuholder2.push(text);
            }
        });

        if(menuholder.length > 0)
        {
            jQuery('#wpai_number_of_heading').val(menuholder.length);

            jQuery("#hfHeadings2").val(JSON.stringify(menuholder));

            jQuery("#hfHeadings").val(menuholder2.join('||'));

            jQuery("#is_generate_continue").val(1);

            jQuery('#myModal').fadeOut('wpcgai_hide');
            jQuery('.modal-backdrop').hide();

            jQuery('#wpcgai_load_plugin_settings').click();
        }
        else if(firstli.length == 0)
        {
            alert('<?php 
echo esc_html__( 'No heading found.', 'gpt3-ai-content-generator' );
?>');
        }
    });

    jQuery(".m_close").on("click", function(e)
    {
        jQuery('#myModal').fadeOut('wpcgai_hide');
        jQuery('.modal-backdrop').hide();
        jQuery('.wpcgai_lds-ellipsis').hide();
        clearTimeout(window['wpaicgTimer']);
        jQuery('#wpcgai_load_plugin_settings').removeAttr('disabled');
        jQuery('#wpcgai_load_plugin_settings .spinner').remove();
        e.stopPropagation();
    });

    jQuery("#wpcgai_add_new_heading").on("click", function(e)
    {
        if(jQuery('#myModal .wpcgai_menu_editor li').length >= 10){
            alert('Limited 10 headings')
        }
        else{
            var randomnum = Math.floor((Math.random() * 100000) + 1);

            var itemTemplate = "<li><div>";

            itemTemplate += "<input type='text' id='text' value='' placeholder='<?php 
echo esc_html__( 'Type heading text...', 'gpt3-ai-content-generator' );
?>' style='width: 90%;'/>";

            itemTemplate += "<span class='wpcgai_sort_heading'><i class='fa fa-bars'></i></span>";

            itemTemplate += "<span id='wpcgai_remove_heading'><i class='fa fa-trash-o'></i></span>";

            itemTemplate += "<div style='display: none;'><span id='identifier'>" + randomnum + "</span>";
            itemTemplate += "</div>";
            itemTemplate += "</div></li>";
            jQuery(".wpcgai_menu_editor").append(itemTemplate);
        }
    });
    function URLSearchParams2JSON_2(STRING) {
        var searchParams = new URLSearchParams(STRING);
        var object = {};
        searchParams.forEach((value, key) => {
            var keys = key.split('.'),
                last = keys.pop();
            keys.reduce((r, a) => r[a] = r[a] || {}, object)[last] = value;
        });
        return object;
    }
    jQuery(document).ready(function ()
    {
        var menuHolder = jQuery('.wpcgai_menu_editor');
        menuHolder.sortable({
            handle: 'div',
            items: 'li',
            toleranceElement: '> div',
            maxLevels: 2,
            isTree: true,
            tolerance: 'pointer'
        });

        jQuery("body").on('click', '#wpcgai_remove_heading', function ()
        {
            var p = jQuery(this).parent().parent();
            jQuery(p).remove();
        });
    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        var buttons = document.querySelectorAll('.smallbuttons');

        buttons.forEach(function(button) {
            button.addEventListener('click', function() {
                var targetId = this.getAttribute('data-target');
                var containers = document.querySelectorAll('.nice-form-group[id]');

                // First, hide all containers
                containers.forEach(function(container) {
                    container.style.display = 'none';
                });

                // Then, show the target container
                document.getElementById(targetId).style.display = '';

                // If you wish to toggle the visibility back to hidden upon a second click, you can implement a check here.
                // For example, if the container is already visible, hide it. This requires storing the visibility state somewhere or checking the style directly.
            });
        });
    });
</script>
<script>
    jQuery(document).ready(function ($){
        let wpaicg_custom_template_form = $('.wpaicg_custom_template_form');
        let wpaicg_template_topic = $('.wpaicg_template_topic');
        let wpaicg_template_generate_titles = $('.wpaicg_template_generate_titles');
        let wpaicg_custom_template_title_count = $('.wpaicg_custom_template_title_count');
        let wpaicg_custom_template_model = $('.wpaicg_custom_template_model');
        let wpaicg_template_title_result = $('.wpaicg_template_title_result');
        let wpaicg_template_section_result = $('.wpaicg_template_section_result');
        let wpaicg_custom_template_section_count = $('.wpaicg_custom_template_section_count');
        let wpaicg_template_generate_sections = $('.wpaicg_template_generate_sections');
        let wpaicg_template_content_result = $('.wpaicg_template_content_result');
        let wpaicg_custom_template_paragraph_count = $('.wpaicg_custom_template_paragraph_count');
        let wpaicg_template_generate_content = $('.wpaicg_template_generate_content');
        let wpaicg_template_excerpt_result = $('.wpaicg_template_excerpt_result');
        let wpaicg_template_generate_excerpt = $('.wpaicg_template_generate_excerpt');
        let wpaicg_template_meta_result = $('.wpaicg_template_meta_result');
        let wpaicg_template_generate_meta = $('.wpaicg_template_generate_meta');
        let wpaicg_template_save_post = $('.wpaicg_template_save_post');
        let wpaicg_template_title_field = $('.wpaicg_template_title_field');
        let wpaicg_template_ajax_url = '<?php 
echo admin_url( 'admin-ajax.php' );
?>';
        let wpaicg_template_generate_stop = $('.wpaicg_template_generate_stop');
        let wpaicg_custom_template_add_topic = $('.wpaicg_custom_template_add_topic');
        let wpaicg_custom_template_add_title = $('.wpaicg_custom_template_add_title');
        let wpaicg_custom_template_type_topic = $('.wpaicg_custom_template_type_topic');
        let wpaicg_custom_template_type_title = $('.wpaicg_custom_template_type_title');
        let wpaicg_custom_template_row_type = $('.wpaicg_custom_template_row_type');
        let wpaicg_tokens = 0;
        let wpaicg_words_count = 0;
        let wpaicg_duration = 0;
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
        wpaicg_template_generate_stop.click(function (){
            let type = $(this).attr('data-type');
            window['wpaicg_template_generator_'+type].abort();
            $(this).hide();
            wpaicgRmLoading($(this).parent().find('.button-primary'));
        });
        wpaicg_custom_template_type_topic.click(function (){
            wpaicg_custom_template_add_title.hide();
            wpaicg_custom_template_add_topic.show();
            wpaicg_custom_template_row_type.show();
        });
        wpaicg_custom_template_type_title.click(function (){
            wpaicg_custom_template_add_title.show();
            wpaicg_custom_template_add_topic.hide();
            wpaicg_custom_template_row_type.hide();
            wpaicg_template_title_result.hide();
        });
        // Function to add or update the hidden 'id' field
        function setTemplateId(id) {
            let existingIdField = $('.wpaicg_custom_template_id');
            if (existingIdField.length) {
                existingIdField.val(id);
            } else {
                $('.wpaicg_custom_template_title').after('<input class="wpaicg_custom_template_id" type="hidden" name="id" value="' + id + '">');
            }
        }
        $(document).on('change','#wpaicg_custom_template_select', function (e){
            let selection = $(e.currentTarget);
            wpaicg_custom_template_title_count.val(3);
            wpaicg_custom_template_section_count.val(2);
            wpaicg_custom_template_paragraph_count.val(1);
            let val = parseFloat(selection.val());
            let selected = selection.find('option:selected');
            let parameters = selected.attr('data-parameters');
            parameters = JSON.parse(parameters);
            if(val > 0){
                $('.wpaicg_custom_template_title').val(selection.find('option:selected').text().trim());
                setTemplateId(val);
                $('.wpaicg_template_update').show();
                $('.wpaicg_template_delete').show();
                $('.wpaicg_template_delete').attr('data-id',val);
            }
            else{
                $('.wpaicg_template_delete').hide();
                $('.wpaicg_template_update').hide();
                $('.wpaicg_custom_template_id').remove();
                $('.wpaicg_custom_template_title').val('');
            }
            $.each(parameters, function (key, item){
                $('.wpaicg_custom_template_'+key).val(item);
            })
            // Show/Hide Azure and OpenAI fields based on provider
            toggleFieldsBasedOnProvider(parameters.provider);
        });
        // Function to show/hide fields based on provider
        function toggleFieldsBasedOnProvider(provider) {
            const modelSelectDiv = $('.wpaicg_custom_template_model').closest('.nice-form-group');
            const azureFieldDiv = $('#azure-deployment-field');
            const googleModelsDiv = $('#google-models-field'); // Google models div
            const openrouterModelsDiv = $('#openrouter-models-field'); // OpenRouter models div

            if (provider === 'azure') {
                modelSelectDiv.hide();
                azureFieldDiv.show();
                googleModelsDiv.hide(); // Hide Google models
                openrouterModelsDiv.hide(); // Hide OpenRouter models
            } else if (provider === 'google') {
                modelSelectDiv.hide();
                azureFieldDiv.hide();
                googleModelsDiv.show(); // Show Google models
                openrouterModelsDiv.hide(); // Hide OpenRouter models
            } else if (provider === 'openrouter') {
                modelSelectDiv.hide();
                azureFieldDiv.hide();
                googleModelsDiv.hide(); // Hide Google models
                openrouterModelsDiv.show(); // Show OpenRouter models
            } else { // Default to OpenAI
                modelSelectDiv.show();
                azureFieldDiv.hide();
                googleModelsDiv.hide(); // Hide Google models
                openrouterModelsDiv.hide(); // Hide OpenRouter models
            }
        }
        
        // Event listener for provider select change
        $(document).on('change', '.wpaicg_custom_template_provider', function () {
            toggleFieldsBasedOnProvider(this.value);
        });

        // Initial display update based on the selected provider
        $(document).ready(function() {
            toggleFieldsBasedOnProvider($('.wpaicg_custom_template_provider').val());
        });
        wpaicg_template_title_field.on('input', function (){
            let val = wpaicg_template_title_field.val();
            if(val !== ''){
                wpaicg_template_generate_sections.removeAttr('disabled');
                wpaicg_template_generate_meta.removeAttr('disabled');
                wpaicg_template_generate_excerpt.removeAttr('disabled');
            }
            else{
                wpaicg_template_generate_sections.attr('disabled','disabled');
                wpaicg_template_generate_meta.attr('disabled','disabled');
                wpaicg_template_generate_excerpt.attr('disabled','disabled');
            }
        })
        $(document).on('keypress','.wpaicg_custom_template_temperature,.wpaicg_custom_template_frequency_penalty,.wpaicg_custom_template_presence_penalty,.wpaicg_custom_template_max_tokens,.wpaicg_custom_template_top_p', function (e){
            var charCode = (e.which) ? e.which : e.keyCode;
            if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
                return false;
            }
            return true;
        });
        $('.wpaicg_modal_close').click(function (){
            wpaicgRmLoading(wpaicg_template_generate_titles);
        })
        $(document).on('click','.wpaicg_template_delete',function (){
            let con = confirm('<?php 
echo esc_html__( 'Are you sure?', 'gpt3-ai-content-generator' );
?>');
            let id = $('.wpaicg_template_delete').attr('data-id');
            if(con) {
                $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: {action: 'wpaicg_template_delete', id: id,'nonce': '<?php 
echo wp_create_nonce( 'wpaicg-ajax-nonce' );
?>'},
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function () {
                        wpaicgLoading($('.wpaicg_template_delete'))
                    },
                    success: function (res) {
                        if (res.status === 'success') {
                            alert('<?php 
echo esc_html__( 'Template successfully deleted.', 'gpt3-ai-content-generator' );
?>')
                            window.location.reload();
                        } else alert(res.msg);
                    }
                })
            }

        })
        $(document).on('click','.wpaicg_template_use_title', function (e){
            let btn = $(e.currentTarget);
            let title = btn.closest('.wpaicg-regenerate-title').find('input').val();
            if(title === ''){
                alert('<?php 
echo esc_html__( 'Please choose correct title', 'gpt3-ai-content-generator' );
?>');
            }
            else{
                $('.wpaicg_modal_content').empty();
                $('.wpaicg-overlay').hide();
                $('.wpaicg_modal').hide();
                wpaicg_template_title_field.val(title);
                wpaicg_template_title_result.html(title);
                wpaicg_template_title_result.show();
                wpaicg_template_generate_sections.removeAttr('disabled');
                wpaicg_template_generate_meta.removeAttr('disabled');
                wpaicg_template_generate_excerpt.removeAttr('disabled');
            }
        })
        // Generator Title
        wpaicg_template_generate_titles.click(function (){
            wpaicg_tokens = 0;
            wpaicg_words_count = 0;
            let topic = wpaicg_template_topic.val();
            if(topic === ''){
                alert('<?php 
echo esc_html__( 'Please enter a topic', 'gpt3-ai-content-generator' );
?>');
            }
            else{
                wpaicg_duration = new Date();
                wpaicg_template_generate_sections.attr('disabled','disabled');
                wpaicg_template_section_result.val('');
                wpaicg_template_title_result.empty();
                wpaicg_template_title_result.hide();
                wpaicg_template_generate_content.attr('disabled','disabled');
                wpaicg_template_content_result.val('');
                wpaicg_template_generate_excerpt.attr('disabled','disabled');
                wpaicg_template_excerpt_result.val('');
                wpaicg_template_generate_meta.attr('disabled','disabled');
                wpaicg_template_meta_result.val('');
                wpaicg_template_save_post.hide();
                let data = wpaicg_custom_template_form.serialize() + '&' + $('#right-nav-custom select').serialize()+'&'+$('#right-nav-custom input').serialize()+'&'+$('#right-nav-custom textarea').serialize();
                data += '&action=wpaicg_template_generator&step=titles&topic='+topic;
                $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        wpaicgLoading(wpaicg_template_generate_titles);
                        $('.wpaicg_modal_content').empty();
                        $('.wpaicg-overlay').show();
                        $('.wpaicg_modal').show();
                        $('.wpaicg_modal_title').html('<h1>AI Power - Title Suggestion Tool</h1>');
                        $('.wpaicg_modal_content').html('<p style="font-style: italic;margin-top: 5px;text-align: center;"><?php 
echo esc_html__( 'Preparing title suggestions...', 'gpt3-ai-content-generator' );
?></p>');
                    },
                    success: function (res){
                        wpaicgRmLoading(wpaicg_template_generate_titles);
                        if(res.status === 'success'){
                            var html = '';
                            wpaicg_tokens += parseFloat(res.tokens);
                            wpaicg_words_count += parseFloat(res.words);
                            if(res.data.length){
                                $.each(res.data, function (idx, item){
                                    html += '<div class="wpaicg-regenerate-title"><input type="text" value="'+item+'"><button class="button button-primary wpaicg_template_use_title"><?php 
echo esc_html__( 'Use', 'gpt3-ai-content-generator' );
?></button></div>';
                                })
                                $('.wpaicg_modal_content').html(html);
                            }
                            else{
                                $('.wpaicg_modal_content').html('<p style="color: #f00;margin-top: 5px;text-align: center;"><?php 
echo esc_html__( 'No result', 'gpt3-ai-content-generator' );
?></p>');
                            }
                        }
                        else{
                            alert(res.msg);
                        }
                    }
                })
            }
        });
        // Generator Sections
        wpaicg_template_generate_sections.click(function (){
            let title = wpaicg_template_title_field.val();
            if(title === ''){
                alert('Please generate title first');
            }
            else{
                let btnStop = $(this).parent().find('.wpaicg_template_generate_stop');
                wpaicg_template_section_result.val('');
                wpaicg_template_generate_content.attr('disabled','disabled');
                wpaicg_template_content_result.val('');
                wpaicg_template_generate_excerpt.attr('disabled','disabled');
                wpaicg_template_excerpt_result.val('');
                wpaicg_template_generate_meta.attr('disabled','disabled');
                wpaicg_template_meta_result.val('');
                let data = wpaicg_custom_template_form.serialize() + '&' + $('#right-nav-custom select').serialize()+'&'+$('#right-nav-custom input').serialize()+'&'+$('#right-nav-custom textarea').serialize();
                data += '&action=wpaicg_template_generator&step=sections&post_title='+title;
                window['wpaicg_template_generator_section'] = $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        wpaicgLoading(wpaicg_template_generate_sections);
                        btnStop.show();
                    },
                    success: function (res){
                        wpaicgRmLoading(wpaicg_template_generate_sections);
                        btnStop.hide();
                        wpaicg_tokens += parseFloat(res.tokens);
                        wpaicg_words_count += parseFloat(res.words);
                        if(res.status === 'success'){
                            if(res.data.length){
                                $.each(res.data, function (idx, item){
                                    let section_result = wpaicg_template_section_result.val();
                                    wpaicg_template_section_result.val(section_result+(idx === 0 ? '' : "\n")+'## '+item);
                                });
                                wpaicg_template_generate_content.removeAttr('disabled');
                            }
                            else{
                                alert('No result');
                            }
                        }
                        else {
                            alert(res.msg);
                        }
                    }
                });
            }
        });
        // Generator Post Content
        wpaicg_template_generate_content.click(function (){
            let sections = wpaicg_template_section_result.val();
            let title = wpaicg_template_title_field.val();
            if(title === ''){
                alert('<?php 
echo esc_html__( 'Please generate title first', 'gpt3-ai-content-generator' );
?>');
            }
            else if(sections === ''){
                alert('<?php 
echo esc_html__( 'Please generate sections first', 'gpt3-ai-content-generator' );
?>');
            }
            else{
                let btnStop = $(this).parent().find('.wpaicg_template_generate_stop');
                wpaicg_template_save_post.hide();
                wpaicg_template_content_result.val('');
                wpaicg_template_excerpt_result.val('');
                wpaicg_template_meta_result.val('');
                let data = wpaicg_custom_template_form.serialize() + '&' + $('#right-nav-custom select').serialize()+'&'+$('#right-nav-custom input').serialize()+'&'+$('#right-nav-custom textarea').serialize();
                data += '&action=wpaicg_template_generator&step=content&post_title='+title+'&sections='+sections;
                window['wpaicg_template_generator_content'] = $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        btnStop.show();
                        wpaicgLoading(wpaicg_template_generate_content);
                    },
                    success: function (res){
                        btnStop.hide();
                        wpaicgRmLoading(wpaicg_template_generate_content);
                        if(res.status === 'success'){
                            wpaicg_tokens += parseFloat(res.tokens);
                            wpaicg_words_count += parseFloat(res.words);
                            if(typeof res.data !== "undefined" && res.data !== ''){
                                wpaicg_template_content_result.val(res.data);
                                wpaicg_template_save_post.show();
                                wpaicg_template_generate_meta.removeAttr('disabled');
                                wpaicg_template_generate_excerpt.removeAttr('disabled');
                            }
                            else{
                                alert('<?php 
echo esc_html__( 'No result', 'gpt3-ai-content-generator' );
?>')
                            }
                        }
                        else{
                            alert(res.msg);
                        }
                    }
                });
            }
        });
        // Generator Excerpt
        wpaicg_template_generate_excerpt.click(function (){
            let title = wpaicg_template_title_field.val();
            if(title === ''){
                alert('Please generate title first');
            }
            else{
                let btnStop = $(this).parent().find('.wpaicg_template_generate_stop');
                wpaicg_template_excerpt_result.val('');
                let data = wpaicg_custom_template_form.serialize() + '&' + $('#right-nav-custom select').serialize()+'&'+$('#right-nav-custom input').serialize()+'&'+$('#right-nav-custom textarea').serialize();
                data += '&action=wpaicg_template_generator&step=excerpt&post_title='+title;
                window['wpaicg_template_generator_excerpt'] = $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        btnStop.show();
                        wpaicgLoading(wpaicg_template_generate_excerpt);
                    },
                    success: function (res){
                        btnStop.hide();
                        wpaicgRmLoading(wpaicg_template_generate_excerpt);
                        if(res.status === 'success'){
                            wpaicg_tokens += parseFloat(res.tokens);
                            wpaicg_words_count += parseFloat(res.words);
                            if(typeof res.data !== "undefined" && res.data !== ''){
                                wpaicg_template_excerpt_result.val(res.data);
                            }
                            else{
                                alert('<?php 
echo esc_html__( 'No result', 'gpt3-ai-content-generator' );
?>')
                            }
                        }
                        else{
                            alert(res.msg);
                        }
                    }
                });
            }
        });
        // Generator Meta
        wpaicg_template_generate_meta.click(function (){
            let title = wpaicg_template_title_field.val();
            if(title === ''){
                alert('<?php 
echo esc_html__( 'Please generate title first', 'gpt3-ai-content-generator' );
?>');
            }
            else{
                let btnStop = $(this).parent().find('.wpaicg_template_generate_stop');
                wpaicg_template_meta_result.val('');
                let data = wpaicg_custom_template_form.serialize() + '&' + $('#right-nav-custom select').serialize()+'&'+$('#right-nav-custom input').serialize()+'&'+$('#right-nav-custom textarea').serialize();
                data += '&action=wpaicg_template_generator&step=meta&post_title='+title;
                window['wpaicg_template_generator_meta'] = $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        btnStop.show();
                        wpaicgLoading(wpaicg_template_generate_meta);
                    },
                    success: function (res){
                        btnStop.hide();
                        wpaicgRmLoading(wpaicg_template_generate_meta);
                        if(res.status === 'success'){
                            wpaicg_tokens += parseFloat(res.tokens);
                            wpaicg_words_count += parseFloat(res.words);
                            if(typeof res.data !== "undefined" && res.data !== ''){
                                wpaicg_template_meta_result.val(res.data);
                            }
                            else{
                                alert('<?php 
echo esc_html__( 'No result', 'gpt3-ai-content-generator' );
?>')
                            }
                        }
                        else{
                            alert(res.msg);
                        }
                    }
                });
            }
        });
        wpaicg_template_save_post.click(function (){
            let title = wpaicg_template_title_field.val();
            let content = wpaicg_template_content_result.val();
            let excerpt = wpaicg_template_excerpt_result.val();
            let description = wpaicg_template_meta_result.val();
            let post_type = $('.wpaicg_custom_template_post_type').val();
            if(title === ''){
                alert('<?php 
echo esc_html__( 'Please generate title first', 'gpt3-ai-content-generator' );
?>');
            }
            else if(content === ''){
                alert('<?php 
echo esc_html__( 'Please generate content first', 'gpt3-ai-content-generator' );
?>');
            }
            else{
                let endTime = new Date();
                let duration = (endTime - wpaicg_duration)/1000;
                let model = wpaicg_custom_template_model.val();
                let provider = $('.wpaicg_custom_template_provider').val();
                let google_model = $('.wpaicg_custom_template_google_model').val();
                let azure_deployment = $('.wpaicg_custom_template_azure_deployment').val();
                let openrouter_model = $('.wpaicg_custom_template_openrouter_model').val();
                $.ajax({
                    url: wpaicg_template_ajax_url,
                    data: {action: 'wpaicg_template_post',post_type: post_type, model: model, provider:provider, google_model: google_model, openrouter_model: openrouter_model,azure_deployment: azure_deployment, duration: duration, title: title, excerpt: excerpt, content: content, description: description, tokens:wpaicg_tokens, words: wpaicg_words_count,'nonce': '<?php 
echo wp_create_nonce( 'wpaicg-ajax-nonce' );
?>'},
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function () {
                        wpaicgLoading(wpaicg_template_save_post);
                    },
                    success: function (res) {
                        wpaicgRmLoading(wpaicg_template_save_post);
                        if(res.status === 'success'){
                            window.location.href = '<?php 
echo admin_url( 'post.php?action=edit&post=' );
?>'+res.id;
                        }
                        else{
                            alert(res.msg);
                        }
                    }
                });
            }
        });
        function wpaicgSaveTemplate(e){
            let btn = $(e.currentTarget);
            let name = $('.wpaicg_custom_template_title').val();
            let has_error = false;
            let temperature = $('.wpaicg_custom_template_temperature').val();
            let model = $('.wpaicg_custom_template_model').val();
            let top_p = $('.wpaicg_custom_template_top_p').val();
            let max_tokens = $('.wpaicg_custom_template_max_tokens').val();
            let frequency_penalty = $('.wpaicg_custom_template_frequency_penalty').val();
            let presence_penalty = $('.wpaicg_custom_template_presence_penalty').val();
            if(name === ''){
                has_error = '<?php 
echo esc_html__( 'Please enter a template name', 'gpt3-ai-content-generator' );
?>';
            }
            if(!has_error && (temperature > 1 || temperature < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum temperature, 2: maximum temperature */
    esc_html__( 'Please enter a valid temperature value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    1
 );
?>';
            }
            if(!has_error && (top_p > 1 || top_p < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum top p value, 2: maximum top p value */
    esc_html__( 'Please enter a valid top p value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    1
 );
?>';
            }
            if(!has_error && (frequency_penalty > 2 || frequency_penalty < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum frequency penalty value, 2: maximum frequency penalty value */
    esc_html__( 'Please enter a valid frequency penalty value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    2
 );
?>';
            }
            if(!has_error && (presence_penalty > 2 || presence_penalty < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum presence penalty value, 2: maximum presence penalty value */
    esc_html__( 'Please enter a valid presence penalty value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    2
 );
?>';
            }
            if(!has_error && (model === 'gpt-3.5-turbo' || model === 'text-davinci-003' || model === 'gpt-3.5-turbo-instruct') && (max_tokens > 4096 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-3.5-turbo, gpt-3.5-turbo-instruct or text-davinci-003, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    4096
 );
?>';
            }
            if(!has_error && model === 'gpt-4' && (max_tokens > 8192 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-4, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    8192
 );
?>';
            }
            if(!has_error && model === 'gpt-4o' && (max_tokens > 8192 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-4o, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    8192
 );
?>';
            }
            if(!has_error && model === 'gpt-4o-mini' && (max_tokens > 8192 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-4o-mini, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    8192
 );
?>';
            }
            if(!has_error && model === 'gpt-3.5-turbo-16k' && (max_tokens > 16384 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-3.5-turbo-16k, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    16384
 );
?>';
            }
            if(!has_error && model === 'gpt-4-32k' && (max_tokens > 32768 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-4-32k, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    32768
 );
?>'
            }
            if (!has_error && model === 'gpt-4-turbo' && (max_tokens > 128000 || max_tokens < 0)) {
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-4-turbo, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    128000
 );
?>';
            }
            if (!has_error && model === 'gpt-4-vision-preview' && (max_tokens > 128000 || max_tokens < 0)) {
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For gpt-4-vision-preview, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    128000
 );
?>';
            }
            if(!has_error && (model === 'text-ada-001' || model === 'text-babbage-001' || model === 'text-curie-001') && (max_tokens > 2049 || max_tokens < 0)){
                has_error = '<?php 
echo sprintf( 
    /* translators: 1: minimum max token value, 2: maximum max token value */
    esc_html__( 'For ada, babbage and curie, please enter a valid max token value between %1$d and %2$d.', 'gpt3-ai-content-generator' ),
    0,
    2049
 );
?>'
            }
            if(has_error){
                alert(has_error);
            }
            else{
                let data = wpaicg_custom_template_form.serialize() + '&' + $('#right-nav-custom select').serialize()+'&'+$('#right-nav-custom input').serialize()+'&'+$('#right-nav-custom textarea').serialize();
                data += '&action=wpaicg_save_template';
                $.ajax({
                    url: wpaicg_template_ajax_url,
                    data:data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        wpaicgLoading(btn)
                    },
                    success: function (res){
                        if(res.status === 'success'){
                            $('.wpaicg-custom-parameters').html(res.setting);
                            if($('.wpaicg_custom_template_id').length && $('.wpaicg_custom_template_id') !== ''){
                                alert('<?php 
echo esc_html__( 'Template updated successfully.', 'gpt3-ai-content-generator' );
?>');
                                wpaicgRmLoading(btn);
                                window.location.reload();
                            }
                            else{
                                alert('<?php 
echo esc_html__( 'New template created successfully.', 'gpt3-ai-content-generator' );
?>');
                                window.location.reload();
                            }
                        }
                        else alert(res.msg);
                    }
                })
            }
        }
        $(document).on('click','.wpaicg_template_save',function (e){
            $('.wpaicg_custom_template_id').remove();
            wpaicgSaveTemplate(e);
        });
        $(document).on('click','.wpaicg_template_update',function (e){
            wpaicgSaveTemplate(e);
        });
    })
</script>
<script>
    jQuery(document).ready(function ($){
        let wpaicgBtnRecord = $('.btn-start-record');
        let wpaicgPauseRecord = $('.btn-pause-record');
        let wpaicgStopRecord = $('.btn-stop-record');
        let wpaicgCancelRecord = $('.btn-cancel-record');
        let wpaicgSendingRecord = $('.wpaicg-sending-record');
        let wpaicgSpeechAudio = $('.wpaicg-speech-audio');
        let wpaicgSpeechResult = $('.wpaicg-speech-result');
        let wpaicgSpeechTitle = $('.wpaicg-speech-title');
        let wpaicgAbortRecord = $('.btn-abort-record');
        let wpaicgDuration = $('.wpaicg-audio-duration');
        let wpaicgAudioTokens = $('.wpaicg-audio-tokens');
        let wpaicgAudioLength = $('.wpaicg-audio-length');
        let wpaicgSaveAudio = $('.wpaicg-audio-save');
        let wpaicgSpeechEditor = tinyMCE.get('wpaicg-speech-content');
        let wpaicgSpeechMessage = $('.wpaicg-speech-message');
        let wpaicgSpeechStream;
        let wpaicgSpeechRec;
        let speechinput;
        let wpaicgSpeechAudioContext = window.AudioContext || window.webkitAudioContext;
        let SpeechaudioContext;
        let wpaicgSpeechAjaxRequest;
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
        function wpaicgspeechstartRecording() {
            var constraints = { audio: true, video:false }
            navigator.mediaDevices.getUserMedia(constraints).then(function(stream) {
                SpeechaudioContext = new wpaicgSpeechAudioContext();
                wpaicgSpeechStream = stream;
                speechinput = SpeechaudioContext.createMediaStreamSource(stream);
                wpaicgSpeechRec = new Recorder(speechinput,{numChannels:1});
                wpaicgSpeechRec.record();
            })
        }

        function wpaicgspeechpauseRecording(){
            if (wpaicgSpeechRec.recording){
                wpaicgSpeechRec.stop();
            }
            else{
                wpaicgSpeechRec.record()
            }
        }
        function wpaicgSpeechAbortRecording(){
            wpaicgSpeechRec.stop();
            wpaicgSpeechStream.getAudioTracks()[0].stop();
        }

        function wpaicgspeechstopRecording() {
            wpaicgSpeechRec.stop();
            wpaicgSpeechStream.getAudioTracks()[0].stop();
            wpaicgSpeechRec.exportWAV(function (blob){
                let url = URL.createObjectURL(blob);
                let reader = new FileReader();
                reader.onload = function (e){
                    let audio = document.createElement('audio');
                    audio.src = e.target.result;
                    audio.addEventListener('loadedmetadata', function(){
                        let duration = audio.duration;
                        wpaicgDuration.val(duration);
                    })
                }
                reader.readAsDataURL(blob);
                wpaicgSpeechAudio.html('<audio controls="true" src="'+url+'"></audio>');
                let data = new FormData();
                data.append('action','wpaicg_speech_record');
                data.append('audio',blob,'speech_record.wav');
                data.append('nonce','<?php 
echo wp_create_nonce( 'wpaicg-ajax-nonce' );
?>');
                wpaicgSpeechAjaxRequest = $.ajax({
                    url: '<?php 
echo admin_url( 'admin-ajax.php' );
?>',
                    data: data,
                    type: 'POST',
                    dataType: 'JSON',
                    cache: false,
                    contentType: false,
                    processData: false,
                    success: function (res){
                        wpaicgSendingRecord.hide();
                        wpaicgBtnRecord.css('display','inline-flex');
                        if(res.status === 'success'){
                            let basicEditor = true;
                            if($('#wp-wpaicg-speech-content-wrap').hasClass('tmce-active')){
                                basicEditor = false;
                            }
                            wpaicgSpeechMessage.html('<p><strong><?php 
echo esc_html__( 'Your Prompt', 'gpt3-ai-content-generator' );
?>: </strong><span style="font-style: italic">'+res.text+'</span></p>');
                            wpaicgSpeechResult.show();
                            wpaicgAudioTokens.val(res.tokens);
                            wpaicgAudioLength.val(res.length);
                            if(basicEditor){
                                $('#wp-wpaicg-speech-content').val(res.data);
                            }
                            else{
                                wpaicgSpeechEditor.setContent(res.data.replace(/\n/g, "<br />"));
                            }
                        }
                        else{
                            alert(res.msg);
                        }
                    }
                })
            });
        }

        wpaicgSaveAudio.click(function (){
            let title = wpaicgSpeechTitle.val();
            let duration = wpaicgDuration.val();
            let tokens = wpaicgAudioTokens.val();
            let wordcount = wpaicgAudioLength.val();
            let content = '';
            let basicEditor = true;
            if($('#wp-wpaicg-speech-content-wrap').hasClass('tmce-active')){
                content = wpaicgSpeechEditor.getContent();
                basicEditor = false;
            }
            else{
                content = $('#wp-wpaicg-speech-content').val();
            }
            if(title === ''){
                alert('<?php 
echo esc_html__( 'Please insert a title before saving.', 'gpt3-ai-content-generator' );
?>');
                return;
            }
            if(content === ''){
                alert('<?php 
echo esc_html__( 'Please record a speech before saving.', 'gpt3-ai-content-generator' );
?>');
                return;
            }
            $.ajax({
                url: '<?php 
echo admin_url( 'admin-ajax.php' );
?>',
                data: {
                    action: 'wpaicg_save_draft_post_extra',
                    title: title,
                    model: 'gpt-4o-mini',
                    content: content,
                    duration: duration,
                    usage_token: tokens,
                    word_count: wordcount,
                    source_log: 'speech',
                    'nonce': '<?php 
echo wp_create_nonce( 'wpaicg-ajax-nonce' );
?>'
                },
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function (){
                    wpaicgLoading(wpaicgSaveAudio)
                },
                success: function (res){
                    wpaicgRmLoading(wpaicgSaveAudio);
                    if(res.status === 'success'){
                        wpaicgSpeechMessage.html('<span style="color: #26a300; font-weight: bold;display: block;margin-top: 10px;"><?php 
echo esc_html__( 'Your post has been saved successfully. You can view its details under the Logs tab.', 'gpt3-ai-content-generator' );
?></span>');
                        wpaicgSpeechResult.hide();
                        wpaicgSpeechTitle.val('');
                        wpaicgSpeechAudio.empty();
                    }
                    else{
                        alert(res.msg);
                    }
                }
            })
        });

        wpaicgAbortRecord.click(function (){
            wpaicgBtnRecord.css('display','inline-flex');
            wpaicgPauseRecord.hide();
            wpaicgStopRecord.hide();
            wpaicgAbortRecord.hide();
            wpaicgSpeechMessage.empty();
            wpaicgSpeechAbortRecording();
        })
        wpaicgBtnRecord.click(function (){
            wpaicgSpeechAudio.empty();
            wpaicgBtnRecord.hide();
            wpaicgSpeechMessage.empty();
            wpaicgPauseRecord.css('display','inline-flex');
            wpaicgStopRecord.css('display','inline-flex');
            wpaicgAbortRecord.css('display','inline-flex');
            wpaicgSpeechResult.hide();
            wpaicgspeechstartRecording();
        });
        wpaicgPauseRecord.click(function (){
            if(wpaicgPauseRecord.hasClass('wpaicg-paused')){
                wpaicgPauseRecord.html('<span class="dashicons dashicons-controls-pause"></span><?php 
echo esc_html__( 'Pause', 'gpt3-ai-content-generator' );
?>')
                wpaicgPauseRecord.removeClass('wpaicg-paused');
            }
            else{
                wpaicgPauseRecord.html('<span class="dashicons dashicons-controls-play"></span><?php 
echo esc_html__( 'Continue', 'gpt3-ai-content-generator' );
?>')
                wpaicgPauseRecord.addClass('wpaicg-paused');
            }
            wpaicgspeechpauseRecording();
        });
        wpaicgStopRecord.click(function (){
            wpaicgPauseRecord.hide();
            wpaicgStopRecord.hide();
            wpaicgAbortRecord.hide();
            wpaicgSendingRecord.show();
            wpaicgspeechstopRecording();
        });
        wpaicgCancelRecord.click(function (){
            if(wpaicgSpeechAjaxRequest !== undefined){
                wpaicgSpeechAjaxRequest.abort();
            }
            wpaicgSendingRecord.hide();
            wpaicgSpeechAudio.empty();
            wpaicgBtnRecord.css('display','inline-flex');
        });
        (function(f){if(typeof exports==="object"&&typeof module!=="undefined"){module.exports=f()}else if(typeof define==="function"&&define.amd){define([],f)}else{var g;if(typeof window!=="undefined"){g=window}else if(typeof global!=="undefined"){g=global}else if(typeof self!=="undefined"){g=self}else{g=this}g.Recorder = f()}})(function(){var define,module,exports;return (function e(t,n,r){function s(o,u){if(!n[o]){if(!t[o]){var a=typeof require=="function"&&require;if(!u&&a)return a(o,!0);if(i)return i(o,!0);var f=new Error("Cannot find module '"+o+"'");throw f.code="MODULE_NOT_FOUND",f}var l=n[o]={exports:{}};t[o][0].call(l.exports,function(e){var n=t[o][1][e];return s(n?n:e)},l,l.exports,e,t,n,r)}return n[o].exports}var i=typeof require=="function"&&require;for(var o=0;o<r.length;o++)s(r[o]);return s})({1:[function(require,module,exports){
                "use strict";

                module.exports = require("./recorder").Recorder;

            },{"./recorder":2}],2:[function(require,module,exports){
                'use strict';

                var _createClass = (function () {
                    function defineProperties(target, props) {
                        for (var i = 0; i < props.length; i++) {
                            var descriptor = props[i];descriptor.enumerable = descriptor.enumerable || false;descriptor.configurable = true;if ("value" in descriptor) descriptor.writable = true;Object.defineProperty(target, descriptor.key, descriptor);
                        }
                    }return function (Constructor, protoProps, staticProps) {
                        if (protoProps) defineProperties(Constructor.prototype, protoProps);if (staticProps) defineProperties(Constructor, staticProps);return Constructor;
                    };
                })();

                Object.defineProperty(exports, "__esModule", {
                    value: true
                });
                exports.Recorder = undefined;

                var _inlineWorker = require('inline-worker');

                var _inlineWorker2 = _interopRequireDefault(_inlineWorker);

                function _interopRequireDefault(obj) {
                    return obj && obj.__esModule ? obj : { default: obj };
                }

                function _classCallCheck(instance, Constructor) {
                    if (!(instance instanceof Constructor)) {
                        throw new TypeError("Cannot call a class as a function");
                    }
                }

                var Recorder = exports.Recorder = (function () {
                    function Recorder(source, cfg) {
                        var _this = this;

                        _classCallCheck(this, Recorder);

                        this.config = {
                            bufferLen: 4096,
                            numChannels: 2,
                            mimeType: 'audio/wav'
                        };
                        this.recording = false;
                        this.callbacks = {
                            getBuffer: [],
                            exportWAV: []
                        };

                        Object.assign(this.config, cfg);
                        this.context = source.context;
                        this.node = (this.context.createScriptProcessor || this.context.createJavaScriptNode).call(this.context, this.config.bufferLen, this.config.numChannels, this.config.numChannels);

                        this.node.onaudioprocess = function (e) {
                            if (!_this.recording) return;

                            var buffer = [];
                            for (var channel = 0; channel < _this.config.numChannels; channel++) {
                                buffer.push(e.inputBuffer.getChannelData(channel));
                            }
                            _this.worker.postMessage({
                                command: 'record',
                                buffer: buffer
                            });
                        };

                        source.connect(this.node);
                        this.node.connect(this.context.destination); //this should not be necessary

                        var self = {};
                        this.worker = new _inlineWorker2.default(function () {
                            var recLength = 0,
                                recBuffers = [],
                                sampleRate = undefined,
                                numChannels = undefined;

                            self.onmessage = function (e) {
                                switch (e.data.command) {
                                    case 'init':
                                        init(e.data.config);
                                        break;
                                    case 'record':
                                        record(e.data.buffer);
                                        break;
                                    case 'exportWAV':
                                        exportWAV(e.data.type);
                                        break;
                                    case 'getBuffer':
                                        getBuffer();
                                        break;
                                    case 'clear':
                                        clear();
                                        break;
                                }
                            };

                            function init(config) {
                                sampleRate = config.sampleRate;
                                numChannels = config.numChannels;
                                initBuffers();
                            }

                            function record(inputBuffer) {
                                for (var channel = 0; channel < numChannels; channel++) {
                                    recBuffers[channel].push(inputBuffer[channel]);
                                }
                                recLength += inputBuffer[0].length;
                            }

                            function exportWAV(type) {
                                var buffers = [];
                                for (var channel = 0; channel < numChannels; channel++) {
                                    buffers.push(mergeBuffers(recBuffers[channel], recLength));
                                }
                                var interleaved = undefined;
                                if (numChannels === 2) {
                                    interleaved = interleave(buffers[0], buffers[1]);
                                } else {
                                    interleaved = buffers[0];
                                }
                                var dataview = encodeWAV(interleaved);
                                var audioBlob = new Blob([dataview], { type: type });

                                self.postMessage({ command: 'exportWAV', data: audioBlob });
                            }

                            function getBuffer() {
                                var buffers = [];
                                for (var channel = 0; channel < numChannels; channel++) {
                                    buffers.push(mergeBuffers(recBuffers[channel], recLength));
                                }
                                self.postMessage({ command: 'getBuffer', data: buffers });
                            }

                            function clear() {
                                recLength = 0;
                                recBuffers = [];
                                initBuffers();
                            }

                            function initBuffers() {
                                for (var channel = 0; channel < numChannels; channel++) {
                                    recBuffers[channel] = [];
                                }
                            }

                            function mergeBuffers(recBuffers, recLength) {
                                var result = new Float32Array(recLength);
                                var offset = 0;
                                for (var i = 0; i < recBuffers.length; i++) {
                                    result.set(recBuffers[i], offset);
                                    offset += recBuffers[i].length;
                                }
                                return result;
                            }

                            function interleave(inputL, inputR) {
                                var length = inputL.length + inputR.length;
                                var result = new Float32Array(length);

                                var index = 0,
                                    inputIndex = 0;

                                while (index < length) {
                                    result[index++] = inputL[inputIndex];
                                    result[index++] = inputR[inputIndex];
                                    inputIndex++;
                                }
                                return result;
                            }

                            function floatTo16BitPCM(output, offset, input) {
                                for (var i = 0; i < input.length; i++, offset += 2) {
                                    var s = Math.max(-1, Math.min(1, input[i]));
                                    output.setInt16(offset, s < 0 ? s * 0x8000 : s * 0x7FFF, true);
                                }
                            }

                            function writeString(view, offset, string) {
                                for (var i = 0; i < string.length; i++) {
                                    view.setUint8(offset + i, string.charCodeAt(i));
                                }
                            }

                            function encodeWAV(samples) {
                                var buffer = new ArrayBuffer(44 + samples.length * 2);
                                var view = new DataView(buffer);

                                /* RIFF identifier */
                                writeString(view, 0, 'RIFF');
                                /* RIFF chunk length */
                                view.setUint32(4, 36 + samples.length * 2, true);
                                /* RIFF type */
                                writeString(view, 8, 'WAVE');
                                /* format chunk identifier */
                                writeString(view, 12, 'fmt ');
                                /* format chunk length */
                                view.setUint32(16, 16, true);
                                /* sample format (raw) */
                                view.setUint16(20, 1, true);
                                /* channel count */
                                view.setUint16(22, numChannels, true);
                                /* sample rate */
                                view.setUint32(24, sampleRate, true);
                                /* byte rate (sample rate * block align) */
                                view.setUint32(28, sampleRate * 4, true);
                                /* block align (channel count * bytes per sample) */
                                view.setUint16(32, numChannels * 2, true);
                                /* bits per sample */
                                view.setUint16(34, 16, true);
                                /* data chunk identifier */
                                writeString(view, 36, 'data');
                                /* data chunk length */
                                view.setUint32(40, samples.length * 2, true);

                                floatTo16BitPCM(view, 44, samples);

                                return view;
                            }
                        }, self);

                        this.worker.postMessage({
                            command: 'init',
                            config: {
                                sampleRate: this.context.sampleRate,
                                numChannels: this.config.numChannels
                            }
                        });

                        this.worker.onmessage = function (e) {
                            var cb = _this.callbacks[e.data.command].pop();
                            if (typeof cb == 'function') {
                                cb(e.data.data);
                            }
                        };
                    }

                    _createClass(Recorder, [{
                        key: 'record',
                        value: function record() {
                            this.recording = true;
                        }
                    }, {
                        key: 'stop',
                        value: function stop() {
                            this.recording = false;
                        }
                    }, {
                        key: 'clear',
                        value: function clear() {
                            this.worker.postMessage({ command: 'clear' });
                        }
                    }, {
                        key: 'getBuffer',
                        value: function getBuffer(cb) {
                            cb = cb || this.config.callback;
                            if (!cb) throw new Error('Callback not set');

                            this.callbacks.getBuffer.push(cb);

                            this.worker.postMessage({ command: 'getBuffer' });
                        }
                    }, {
                        key: 'exportWAV',
                        value: function exportWAV(cb, mimeType) {
                            mimeType = mimeType || this.config.mimeType;
                            cb = cb || this.config.callback;
                            if (!cb) throw new Error('Callback not set');

                            this.callbacks.exportWAV.push(cb);

                            this.worker.postMessage({
                                command: 'exportWAV',
                                type: mimeType
                            });
                        }
                    }], [{
                        key: 'forceDownload',
                        value: function forceDownload(blob, filename) {
                            var url = (window.URL || window.webkitURL).createObjectURL(blob);
                            var link = window.document.createElement('a');
                            link.href = url;
                            link.download = filename || 'output.wav';
                            var click = document.createEvent("Event");
                            click.initEvent("click", true, true);
                            link.dispatchEvent(click);
                        }
                    }]);

                    return Recorder;
                })();

                exports.default = Recorder;

            },{"inline-worker":3}],3:[function(require,module,exports){
                "use strict";

                module.exports = require("./inline-worker");
            },{"./inline-worker":4}],4:[function(require,module,exports){
                (function (global){
                    "use strict";

                    var _createClass = (function () { function defineProperties(target, props) { for (var key in props) { var prop = props[key]; prop.configurable = true; if (prop.value) prop.writable = true; } Object.defineProperties(target, props); } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; })();

                    var _classCallCheck = function (instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } };

                    var WORKER_ENABLED = !!(global === global.window && global.URL && global.Blob && global.Worker);

                    var InlineWorker = (function () {
                        function InlineWorker(func, self) {
                            var _this = this;

                            _classCallCheck(this, InlineWorker);

                            if (WORKER_ENABLED) {
                                var functionBody = func.toString().trim().match(/^function\s*\w*\s*\([\w\s,]*\)\s*{([\w\W]*?)}$/)[1];
                                var url = global.URL.createObjectURL(new global.Blob([functionBody], { type: "text/javascript" }));

                                return new global.Worker(url);
                            }

                            this.self = self;
                            this.self.postMessage = function (data) {
                                setTimeout(function () {
                                    _this.onmessage({ data: data });
                                }, 0);
                            };

                            setTimeout(function () {
                                func.call(self);
                            }, 0);
                        }

                        _createClass(InlineWorker, {
                            postMessage: {
                                value: function postMessage(data) {
                                    var _this = this;

                                    setTimeout(function () {
                                        _this.self.onmessage({ data: data });
                                    }, 0);
                                }
                            }
                        });

                        return InlineWorker;
                    })();

                    module.exports = InlineWorker;
                }).call(this,typeof global !== "undefined" ? global : typeof self !== "undefined" ? self : typeof window !== "undefined" ? window : {})
            },{}]},{},[1])(1)
        });
    })
</script>
<script>
    var openaiModels = <?php 
echo json_encode( $openai_models );
?>;
    jQuery(document).ready(function ($){
        // Define the prompts
        var prompts = [
            {category: 'wordpress', prompt: 'Write a beginner-friendly tutorial on how to set up a secure and optimized WordPress website, focusing on security measures, performance enhancements, and best practices.'},
            {category: 'wordpress', prompt: 'Create a list of essential WordPress plugins for various niches, explaining their features, use cases, and benefits for website owners.'},
            {category: 'wordpress', prompt: 'Develop an in-depth guide on how to improve the loading speed of a WordPress website, covering hosting, caching, image optimization, and more.'},
            {category: 'wordpress', prompt: 'Write an article on how to choose the perfect WordPress theme for a specific business niche, taking into account design, functionality, and customization options.'},
            {category: 'wordpress', prompt: 'Create a comprehensive guide on managing a WordPress website, including updating themes and plugins, performing backups, and monitoring site health.'},
            {category: 'wordpress', prompt: 'Write a tutorial on how to create a custom WordPress theme from scratch, covering design principles, template hierarchy, and best practices for coding.'},
            {category: 'wordpress', prompt: 'Develop a resource guide on how to leverage WordPress Multisite to manage multiple websites efficiently, including setup, management, and use cases.'},
            {category: 'wordpress', prompt: 'Write an article on the benefits of using WooCommerce for e-commerce websites, including features, extensions, and comparisons to other e-commerce platforms.'},
            {category: 'wordpress', prompt: 'Create a guide on how to optimize a WordPress website for search engines, focusing on SEO-friendly themes, plugins, and on-page optimization techniques.'},
            {category: 'wordpress', prompt: 'Write a case study on a successful WordPress website, detailing its design, growth strategies, and the impact of its content on its target audience.'},
            {category: 'blogging', prompt: 'Write a blog post on how to effectively monetize a blog, discussing various methods such as affiliate marketing, sponsored content, and display advertising, as well as tips for maximizing revenue.'},
            {category: 'blogging', prompt: 'Write a blog post about the importance of networking and collaboration in the blogging community, including practical tips for building relationships and partnering with other bloggers and influencers.'},
            {category: 'blogging', prompt: 'Create a blog post that explores various content formats for blogging, such as written articles, podcasts, and videos, and discusses their pros and cons, as well as strategies for selecting the best format for a specific audience.'},
            {category: 'blogging', prompt: 'Write a blog post detailing the essential elements of a successful blog design and layout, focusing on user experience and visual appeal.'},
            {category: 'blogging', prompt: 'Write a blog post discussing the importance of authentic storytelling in blogging and how it can enhance audience engagement and brand loyalty.'},
            {category: 'blogging', prompt: 'Write a blog post about leveraging social media for blog promotion, including tips on cross-platform marketing and strategies for increasing blog visibility.'},
            {category: 'blogging', prompt: 'Write a blog post exploring the role of search engine optimization in blogging success, with a step-by-step guide on optimizing blog content for improved search rankings.'},
            {category: 'blogging', prompt: 'Write a blog post about the value of developing a consistent posting schedule and editorial calendar, sharing strategies for maintaining productivity and audience interest.'},
            {category: 'blogging', prompt: 'Write a blog post about the benefits and challenges of embracing a lean startup methodology, with actionable tips for implementing this approach in a new business venture.'},
            {category: 'writing', prompt: 'Write an article discussing the benefits of incorporating mindfulness and meditation practices into daily routines for improved mental health.'},
            {category: 'writing', prompt: 'Write an article exploring the impact of sustainable agriculture practices on global food security and the environment.'},
            {category: 'writing', prompt: 'Write an article analyzing the role of renewable energy sources in combating climate change and reducing global carbon emissions.'},
            {category: 'writing', prompt: 'Write an article examining the history and cultural significance of traditional art forms from around the world.'},
            {category: 'writing', prompt: 'Write an article discussing the importance of financial literacy and practical tips for managing personal finances.'},
            {category: 'writing', prompt: 'Write an article highlighting advancements in telemedicine and its potential to transform healthcare access and delivery.'},
            {category: 'writing', prompt: 'Write an article discussing the ethical implications of artificial intelligence and its potential effects on society.'},
            {category: 'writing', prompt: 'Write an article exploring the benefits of lifelong learning and its impact on personal and professional growth.'},
            {category: 'writing', prompt: 'Write an article analyzing the role of urban planning and design in creating sustainable and livable cities.'},
            {category: 'writing', prompt: 'Write an article discussing the influence of technology on modern communication and its effect on human relationships.'},
            {category: 'ecommerce', prompt: 'Design a digital marketing campaign for an online fashion store, focusing on customer engagement and boosting sales.'},
            {category: 'ecommerce', prompt: 'Create a step-by-step guide for optimizing an e-commerce websites user experience, including navigation, product presentation, and checkout process.'},
            {category: 'ecommerce', prompt: 'Write a persuasive email sequence for a cart abandonment campaign, aimed at encouraging customers to complete their purchases.'},
            {category: 'ecommerce', prompt: 'Develop a content strategy for an e-commerce blog, focusing on topics that will educate, inform, and entertain potential customers.'},
            {category: 'ecommerce', prompt: 'Outline the benefits and features of a new e-commerce platform designed to simplify the process of setting up and managing an online store.'},
            {category: 'ecommerce', prompt: 'Create a video script for a product demonstration that highlights the unique selling points of an innovative kitchen gadget.'},
            {category: 'ecommerce', prompt: 'Design a customer loyalty program for an e-commerce business, focusing on rewards, incentives, and strategies to drive repeat purchases.'},
            {category: 'ecommerce', prompt: 'Write a case study showcasing the successful implementation of an e-commerce solution for a small brick-and-mortar retailer.'},
            {category: 'ecommerce', prompt: 'Develop an infographic that illustrates the growth of e-commerce, including key statistics, trends, and milestones in the industry.'},
            {category: 'ecommerce', prompt: 'Create a series of social media posts for an e-commerce brand that showcases their products and engages their target audience.'},
            {category: 'online_business', prompt: 'Create a comprehensive guide on selecting the best e-commerce platform for a new online business, considering features, pricing, and scalability.'},
            {category: 'online_business', prompt: 'Develop a social media marketing plan for a small online business, focusing on choosing the right platforms, content creation, and audience engagement.'},
            {category: 'online_business', prompt: 'Write an in-depth article on utilizing search engine optimization (SEO) strategies to drive organic traffic to an online business website.'},
            {category: 'online_business', prompt: 'Design a webinar series that teaches aspiring entrepreneurs the essentials of building and managing a successful online business.'},
            {category: 'online_business', prompt: 'Create a resource guide on the top tools and software solutions for managing an online business, covering inventory management, marketing, and customer service.'},
            {category: 'online_business', prompt: 'Write a case study about a successful online business that pivoted during challenging times and thrived through innovation and adaptability.'},
            {category: 'online_business', prompt: 'Develop a list of best practices for creating an engaging and visually appealing online business website that attracts customers and drives sales.'},
            {category: 'online_business', prompt: 'Outline a customer support strategy for an online business, focusing on communication channels, response times, and customer satisfaction.'},
            {category: 'online_business', prompt: 'Write an article on the importance of branding and visual identity for an online business, including tips for creating a consistent and memorable brand.'},
            {category: 'online_business', prompt: 'Create a guide on using email marketing to nurture leads and convert them into loyal customers for an online business.'},
            {category: 'entrepreneurship', prompt: 'Develop a step-by-step guide on how to identify and validate a profitable niche for a new business venture, including market research and competitor analysis.'},
            {category: 'entrepreneurship', prompt: 'Write an article on the most effective funding options for startups, exploring crowdfunding, angel investors, venture capital, and bootstrapping.'},
            {category: 'entrepreneurship', prompt: 'Create a comprehensive guide on building a strong team for a startup, focusing on hiring strategies, team culture, and effective communication.'},
            {category: 'entrepreneurship', prompt: 'Design a video tutorial series on creating a successful business plan, covering executive summary, market analysis, marketing strategy, and financial projections.'},
            {category: 'entrepreneurship', prompt: 'Write a case study on a successful entrepreneur who overcame significant challenges and setbacks on their journey to building a thriving business.'},
            {category: 'entrepreneurship', prompt: 'Develop a list of essential legal considerations for starting a new business, including business structure, licensing, permits, and intellectual property protection.'},
            {category: 'entrepreneurship', prompt: 'Outline a guide on how to develop and maintain a healthy work-life balance as an entrepreneur, with a focus on time management, delegation, and self-care.'},
            {category: 'entrepreneurship', prompt: 'Write an article on the importance of networking for entrepreneurs, including strategies for building connections, maintaining relationships, and leveraging partnerships.'},
            {category: 'entrepreneurship', prompt: 'Create a resource guide on top tools and technologies for startups, covering project management, communication, financial management, and customer relationship management.'},
            {category: 'entrepreneurship', prompt: 'Develop an in-depth guide on how to effectively pivot a business when faced with unexpected challenges, including recognizing the need for change and implementing a new strategy.'},
            {category: 'seo', prompt: 'Write an in-depth guide on conducting comprehensive keyword research for website content, focusing on understanding user intent, search volume, and competition.'},
            {category: 'seo', prompt: 'Develop a blog post on the essential on-page SEO factors that every website owner should know, including proper URL structures, title tags, header tags, and meta descriptions.'},
            {category: 'seo', prompt: 'Create a comprehensive guide on link-building strategies for improving website authority and search rankings, covering techniques such as guest blogging, broken link building, and outreach.'},
            {category: 'seo', prompt: 'Write an article about the impact of website speed on SEO and user experience, discussing tools and techniques for analyzing and improving site performance.'},
            {category: 'seo', prompt: 'Develop a tutorial on how to create SEO-friendly content that appeals to both search engines and human readers, focusing on readability, keyword usage, and information value.'},
            {category: 'seo', prompt: 'Write a blog post about the importance of mobile-first indexing and responsive web design in modern SEO, including tips for optimizing websites for mobile devices.'},
            {category: 'seo', prompt: 'Create a guide on how to use Google Search Console effectively for monitoring and improving website SEO performance, including features such as index coverage reports, sitemaps, and search analytics.'},
            {category: 'seo', prompt: 'Write an article discussing the role of voice search in SEO, highlighting strategies for optimizing website content for voice search queries and emerging trends in voice search technology.'},
            {category: 'seo', prompt: 'Develop a blog post about the significance of user experience (UX) in SEO, including tips for enhancing website navigation, layout, and overall user satisfaction to improve search rankings.'},
            {category: 'seo', prompt: 'Create an article on the importance of local SEO for small businesses, focusing on strategies such as Google My Business optimization, citation building, and local content creation.'},
            {category: 'social_media', prompt: 'Write an article on the most effective strategies for growing a brands presence on social media platforms, including content creation, engagement, and advertising.'},
            {category: 'social_media', prompt: 'Develop a blog post about the benefits of using social media analytics to improve marketing efforts, with tips on interpreting data and making data-driven decisions.'},
            {category: 'social_media', prompt: 'Create a guide on how to create compelling visual content for social media platforms, focusing on elements such as color, typography, and composition.'},
            {category: 'social_media', prompt: 'Craft an in-depth article on leveraging user-generated content to boost brand authenticity and increase engagement on social media platforms.'},
            {category: 'social_media', prompt: 'Write a comprehensive tutorial on optimizing social media profiles for search engines, highlighting the importance of keywords, descriptions, and profile images.'},
            {category: 'social_media', prompt: 'Develop an informative blog post about the role of social media influencers in brand promotion, and outline the process of selecting and collaborating with the right influencers for a specific target audience.'},
            {category: 'social_media', prompt: 'Create a guide on how to effectively use social media scheduling tools to streamline content creation and posting, ensuring consistency and maximizing reach.'},
            {category: 'social_media', prompt: 'Write an article discussing the best practices for managing online communities on social media platforms, focusing on fostering positive interactions and handling negative feedback.'},
            {category: 'social_media', prompt: 'Develop a blog post about the importance of storytelling in social media marketing, with tips on creating engaging narratives that resonate with audiences and generate brand loyalty.'},
            {category: 'social_media', prompt: 'Create a guide on how to measure and analyze the return on investment (ROI) for social media advertising campaigns, including the key performance indicators (KPIs) to track and optimize.'},
            {category: 'digital_marketing', prompt: 'Write a comprehensive guide on creating and executing a successful content marketing strategy, including planning, creation, distribution, and measurement.'},
            {category: 'digital_marketing', prompt: 'Develop a blog post about the benefits of using marketing automation tools, with examples of popular platforms and use cases for different business sizes and industries.'},
            {category: 'digital_marketing', prompt: 'Create an article discussing the role of influencer marketing in modern advertising, with tips on selecting the right influencers, developing campaigns, and measuring success.'},
            {category: 'digital_marketing', prompt: 'Write an in-depth guide on utilizing search engine optimization (SEO) techniques for improving website visibility, including keyword research, on-page optimization, and off-page strategies.'},
            {category: 'digital_marketing', prompt: 'Craft a detailed article on the importance of social media marketing, highlighting effective platform-specific strategies, content planning, engagement techniques, and performance analysis.'},
            {category: 'digital_marketing', prompt: 'Develop a comprehensive guide on email marketing best practices, covering list building, segmentation, email design, personalization, automation, and metrics tracking.'},
            {category: 'digital_marketing', prompt: 'Write an informative blog post about the advantages of data-driven marketing, including insights on collecting, analyzing, and applying data to enhance targeting, personalization, and campaign effectiveness.'},
            {category: 'digital_marketing', prompt: 'Create an article exploring the benefits of video marketing, with tips on producing engaging content, optimizing for search engines, and leveraging various distribution channels.'},
            {category: 'digital_marketing', prompt: 'Write a guide on implementing effective pay-per-click (PPC) advertising campaigns, discussing budget allocation, keyword targeting, ad copywriting, landing page optimization, and performance analysis.'},
            {category: 'digital_marketing', prompt: 'Develop a blog post on the role of content repurposing in digital marketing, providing strategies for transforming existing content into different formats and leveraging multiple distribution channels.'},
            {category: 'woocommerce', prompt: 'Write a comprehensive guide on optimizing WooCommerce stores for maximum performance, discussing topics such as caching, image optimization, database cleaning, and choosing the right hosting environment.'},
            {category: 'woocommerce', prompt: 'Create an in-depth tutorial on setting up a successful WooCommerce store from scratch, covering aspects like choosing the right theme, setting up payment gateways, configuring shipping options, and managing inventory.'},
            {category: 'woocommerce', prompt: 'Develop an article on the top WooCommerce plugins that can enhance an online store’s functionality, covering areas such as analytics, email marketing, product recommendations, and customer support.'},
            {category: 'woocommerce', prompt: 'Write a detailed guide on implementing effective WooCommerce SEO strategies to improve search engine visibility, discussing on-page optimization, product schema markup, permalink structure, and sitemaps.'},
            {category: 'woocommerce', prompt: 'Craft an article on enhancing the user experience of a WooCommerce store, focusing on design principles, seamless navigation, product presentation, mobile responsiveness, and checkout optimization.'},
            {category: 'woocommerce', prompt: 'Write an in-depth article on maximizing sales and conversions for WooCommerce stores, covering strategies such as abandoned cart recovery, personalized product recommendations, and utilizing customer reviews.'},
            {category: 'woocommerce', prompt: 'Create a comprehensive guide on managing and scaling a WooCommerce store, discussing topics like inventory management, order fulfillment, automating processes, and expanding into new markets.'},
            {category: 'woocommerce', prompt: 'Develop an article on the importance of security for WooCommerce stores and best practices to protect against threats, including SSL certificates, secure hosting, regular backups, and security plugins.'},
            {category: 'woocommerce', prompt: 'Write a detailed tutorial on how to create and implement a successful marketing strategy for a WooCommerce store, covering email marketing, social media advertising, content marketing, and influencer partnerships.'},
            {category: 'woocommerce', prompt: 'Craft an article on the benefits of integrating third-party services and APIs with a WooCommerce store, focusing on areas such as payment processing, shipping solutions, marketing automation, and customer relationship management.'},
            {category: 'content_creation', prompt: 'Write an in-depth guide on brainstorming and developing unique content ideas, covering various research methods, mind mapping, and using audience feedback to inform content creation.'},
            {category: 'content_creation', prompt: 'Develop a comprehensive article on the principles of effective copywriting, focusing on techniques such as writing compelling headlines, utilizing storytelling, and creating persuasive calls to action.'},
            {category: 'content_creation', prompt: 'Create a detailed tutorial on how to structure and format long-form content for maximum readability and engagement, discussing elements like headings, lists, images, and content flow.'},
            {category: 'content_creation', prompt: 'Craft a blog post about the role of visual storytelling in content creation, with tips on using images, videos, and infographics to enhance the impact of written content and engage diverse audiences.'},
            {category: 'content_creation', prompt: 'Write an informative guide on optimizing content for search engines, including keyword research, proper use of headings and meta tags, internal and external linking, and image optimization.'},
            {category: 'content_creation', prompt: 'Develop an article discussing the importance of editorial calendars in content creation, covering aspects like planning, organization, collaboration, and ensuring consistent content output.'},
            {category: 'content_creation', prompt: 'Write a comprehensive guide on using various multimedia formats in content creation, such as podcasts, webinars, and interactive content, to cater to different audience preferences and enhance engagement.'},
            {category: 'content_creation', prompt: 'Create a blog post about the role of user-generated content in content marketing, with tips on encouraging audience participation, curating submissions, and leveraging this content for promotional purposes.'},
            {category: 'content_creation', prompt: 'Craft a detailed article on repurposing existing content for different platforms and formats, such as transforming blog posts into infographics, videos, or social media snippets to maximize reach and engagement.'},
            {category: 'content_creation', prompt: 'Write an in-depth tutorial on incorporating storytelling techniques into content creation, including character development, conflict resolution, and narrative structure to create engaging and memorable content.'},
            {category: 'content_strategy', prompt: 'Write a comprehensive guide on creating a data-driven content strategy, including audience research, content gap analysis, setting goals, and measuring success through key performance indicators.'},
            {category: 'content_strategy', prompt: 'Develop a blog post discussing the importance of evergreen content in a content strategy, providing examples and tips on how to create timeless and valuable pieces that continue to drive traffic and engagement.'},
            {category: 'content_strategy', prompt: 'Create an in-depth article on the role of content distribution in a successful content strategy, covering various channels such as social media, email, guest posting, and leveraging partnerships to maximize reach.'},
            {category: 'content_strategy', prompt: 'Craft a detailed guide on using user personas to inform content strategy, discussing the process of creating accurate personas, identifying their needs and pain points, and tailoring content to address their specific interests.'},
            {category: 'content_strategy', prompt: 'Write an informative blog post about the benefits of conducting a content audit, outlining the steps involved, identifying underperforming content, and implementing improvements to enhance overall content strategy effectiveness.'},
            {category: 'content_strategy', prompt: 'Develop a comprehensive article on the importance of a well-defined content calendar for a successful content strategy, including tips on planning, organization, consistency, and collaboration among team members.'},
            {category: 'content_strategy', prompt: 'Write an in-depth guide on leveraging content analytics to improve content strategy, discussing key metrics to track, data-driven decision-making, and using insights to optimize content performance and audience engagement.'},
            {category: 'content_strategy', prompt: 'Create a detailed blog post about the role of content curation in a content strategy, with tips on sourcing high-quality content, adding value through commentary, and sharing curated pieces to supplement original content.'},
            {category: 'content_strategy', prompt: 'Craft an informative article on incorporating different content formats and types into a content strategy, such as blog posts, case studies, whitepapers, videos, and podcasts, to cater to diverse audience preferences.'},
            {category: 'content_strategy', prompt: 'Write a comprehensive guide on effective content promotion techniques to boost visibility and engagement, covering strategies such as influencer outreach, social media advertising, and search engine optimization.'},
            {category: 'keyword_research', prompt: 'Write a comprehensive guide on the basics of keyword research, explaining its importance in SEO, the tools used, and how it influences content creation and website ranking.'},
            {category: 'keyword_research', prompt: 'Craft an in-depth blog post about long-tail keywords, discussing their role in modern SEO strategies, how to identify them, and techniques for effectively incorporating them into content.'},
            {category: 'keyword_research', prompt: 'Develop a detailed tutorial on using Google Keyword Planner for keyword research, including step-by-step instructions and tips for interpreting and applying the data.'},
            {category: 'keyword_research', prompt: 'Create an informative article on the relationship between keyword research and user intent, explaining how understanding the latter can guide the former to produce more targeted, effective content.'},
            {category: 'keyword_research', prompt: 'Compose a comprehensive guide on the role of competitor analysis in keyword research, detailing how to identify and evaluate the keyword strategies of successful competitors.'},
            {category: 'keyword_research', prompt: 'Write an in-depth article on the integration of keyword research into content strategy, explaining how to seamlessly embed keywords into various types of content for maximum SEO impact.'},
            {category: 'keyword_research', prompt: 'Craft an informative piece on the importance of keyword relevancy and search volume in keyword research, and discuss how to balance these factors for optimal results.'},
            {category: 'keyword_research', prompt: 'Develop a detailed tutorial on tracking and refining keyword performance over time, including the tools and metrics that can be used to measure success.'},
            {category: 'keyword_research', prompt: 'Create an insightful blog post about the pitfalls to avoid in keyword research, discussing common mistakes and misconceptions that can hinder SEO efforts.'},
            {category: 'product_listing', prompt: 'Create an exhaustive guide on designing a product listing page that maximizes conversion rates, focusing on product descriptions, images, customer reviews, and pricing.'},
            {category: 'product_listing', prompt: 'Develop a detailed article on optimizing product listings for search engines, considering keyword research, SEO-friendly URLs, and meta descriptions.'},
            {category: 'product_listing', prompt: 'Craft a webinar series that provides insights into A/B testing for product listings, teaching businesses how to refine their listings based on customer behavior.'},
            {category: 'product_listing', prompt: 'Write a case study on a successful online retailer that significantly increased sales through effective product listing strategies, outlining the steps they took and the results they achieved.'},
            {category: 'product_listing', prompt: 'Develop a list of best practices for managing product listings on multiple e-commerce platforms, focusing on maintaining consistency, updating inventory, and handling customer queries.'},
            {category: 'product_listing', prompt: 'Create a comprehensive guide on how to write persuasive product descriptions that effectively showcase the benefits and features of products, leading to increased sales.'},
            {category: 'product_listing', prompt: 'Develop a blog post discussing the role of high-quality imagery and video content in product listings, including tips on product photography and videography.'},
            {category: 'product_listing', prompt: 'Design a tutorial on using data analytics to improve product listings, focusing on understanding customer behavior, product performance, and sales trends.'},
            {category: 'product_listing', prompt: 'Write a case study on a business that used cross-selling and up-selling techniques within their product listings to increase average order value, discussing the strategy and the results.'},
            {category: 'product_listing', prompt: 'Outline a strategy for managing product listings during peak sales periods, such as holidays or sales events, considering inventory management, pricing adjustments, and customer service.'},
            {category: 'customer_relationship_management', prompt: 'Compose an in-depth guide explaining how a CRM system can transform customer service operations, detailing the features and tools that can streamline customer interactions and enhance customer satisfaction.'},
            {category: 'customer_relationship_management', prompt: 'Write a comprehensive article on the role of analytics in CRM, explaining how businesses can leverage data to understand customer behavior, predict future trends, and personalize customer interactions.'},
            {category: 'customer_relationship_management', prompt: 'Develop a detailed tutorial on integrating a CRM system into a companys sales and marketing strategies, including practical steps and potential challenges to anticipate.'},
            {category: 'customer_relationship_management', prompt: 'Create an insightful blog post about the importance of CRM in retaining customers and building long-term relationships, discussing strategies for using CRM to increase customer loyalty and lifetime value.'},
            {category: 'customer_relationship_management', prompt: 'Craft a case study showcasing a successful implementation of a CRM system in a business, highlighting the benefits it brought in terms of sales growth, customer satisfaction, and improved internal processes.'},
            {category: 'customer_relationship_management', prompt: 'Write an exhaustive guide on choosing the right CRM system for a small business, taking into account factors such as scalability, usability, and integration with existing systems.'},
            {category: 'customer_relationship_management', prompt: 'Develop an in-depth article discussing the impact of AI and machine learning on CRM systems, and how these technologies can enhance customer interactions and provide deeper insights.'},
            {category: 'customer_relationship_management', prompt: 'Create a detailed tutorial on how to train employees to effectively use a CRM system, including tips for overcoming resistance to new technology.'},
            {category: 'customer_relationship_management', prompt: 'Craft an insightful blog post about the role of CRM in e-commerce, discussing how it can help online businesses better understand their customers and personalize their shopping experience.'},
            {category: 'customer_relationship_management', prompt: 'Compose a case study analyzing the transformation of a companys customer service operations before and after implementing a CRM system, detailing the challenges faced and the results achieved.'}
        ];
        // Function to handle category selection
        $('#category_select').on('change', function() {
            var selectedCategory = $(this).val();
            if (selectedCategory) {
                // Clear and populate the prompts dropdown
                $('#sample_prompts').html('<option value=""><?php 
echo esc_html__( 'Select a prompt', 'gpt3-ai-content-generator' );
?></option>');
                prompts.forEach(function(promptObj) {
                    if (promptObj.category === selectedCategory) {
                        $('#sample_prompts').append('<option value="' + promptObj.prompt + '">' + promptObj.prompt + '</option>');
                    }
                });
                $('.sample_prompts_row').show();
            } else {
                // Hide the prompts dropdown and clear its value
                $('.sample_prompts_row').hide();
                $('#sample_prompts').val('');
            }
        });


        function updateModelDropdown(models) {
            var modelSelect = $('#model_select');
            modelSelect.empty(); // Clear existing options

            // Check if the models object has groups
            if (models.hasOwnProperty('groups')) {
                $.each(models.groups, function(group, groupModels) {
                    var optgroup = $('<optgroup>').attr('label', group);
                    $.each(groupModels, function(value, label) {
                        optgroup.append($('<option>', {
                            value: value,
                            text: label
                        }));
                    });
                    modelSelect.append(optgroup);
                });
            } else {
                // If there are no groups, populate normally
                $.each(models, function(value, label) {
                    modelSelect.append($('<option>', {
                        value: value,
                        text: label
                    }));
                });
            }
        }

        var togetheraiModels = {
            groups: {
                'Mistral Models': {
                    'mistralai/Mixtral-8x7B-Instruct-v0.1': 'Mixtral (8x7B) Instruct',
                    'mistralai/Mistral-7B-Instruct-v0.1': 'Mistral (7B) Instruct'
                },
                'Meta Models': {
                    'togethercomputer/llama-2-70b-chat': 'LLaMA-2 Chat (70B)',
                    'togethercomputer/llama-2-13b-chat': 'LLaMA-2 Chat (13B)',
                    'togethercomputer/llama-2-7b-chat': 'LLaMA-2 Chat (7B)'
                },
                '01-ai Models': {
                    'zero-one-ai/Yi-34B-Chat': 'Yi Chat (34B)'
                },
                'Stanford': {
                    'togethercomputer/alpaca-7b': 'Alpaca (7B)'
                }
            }
        };

        var googleModels = {
            groups: {
                'Chat Models': {
                    'gemini-pro': 'Gemini Pro',
                    'chat-bison-001': 'Chat Bison-001'
                },
                'Text Models': {
                    'text-bison-001': 'Text Bison-001'
                }
            }
        };

        // Function to fetch and update OpenRouter models
        function fetchOpenRouterModels() {
            const openrouterModels = <?php 
echo json_encode( get_option( 'wpaicg_openrouter_model_list', [] ) );
?>;
            var modelOptions = {};

            if (openrouterModels.length === 0) {
                modelOptions['openrouter/auto'] = 'OpenRouter Auto';
            } else {
                // Group models by provider
                const groupedModels = openrouterModels.reduce((groups, model) => {
                    const provider = model.id.split('/')[0];
                    if (!groups[provider]) {
                        groups[provider] = {};
                    }
                    groups[provider][model.id] = model.name;
                    return groups;
                }, {});

                modelOptions.groups = groupedModels;
            }

            updateModelDropdown(modelOptions);
        }

        $('#provider_select').on('change', function() {
            var selectedProvider = $(this).val();
            var modelOptions;

            // Selecting model options based on provider
            if (selectedProvider === 'openai') {
                modelOptions = openaiModels;
            } else if (selectedProvider === 'google') {
                modelOptions = googleModels;
            } else if (selectedProvider === 'togetherai') {
                modelOptions = togetheraiModels;
            } else if (selectedProvider === 'openrouter') {
                fetchOpenRouterModels();
                return; // Exit the function as models are fetched asynchronously
            }

            updateModelDropdown(modelOptions);

            // Show or hide the API key link based on provider
            if (selectedProvider === 'google') {
                $('#add_api_key_link').show();
                $('#add_togetherai_api_key_link').hide();
                $('#api_key_field').hide();
                $('#api_key_field_togetherai').hide();
            } else if (selectedProvider === 'togetherai') {
                $('#add_togetherai_api_key_link').show();
                $('#add_api_key_link').hide();
                $('#api_key_field').hide();
                $('#api_key_field_togetherai').hide();
            } else {
                $('#add_api_key_link').hide();
                $('#add_togetherai_api_key_link').hide();
                $('#api_key_field').hide();
                $('#api_key_field_togetherai').hide();
            }
        });


        // Initial model dropdown update
        updateModelDropdown(openaiModels);

        // Show API key field for Google when its link is clicked
        $('#add_api_key_link').click(function(e) {
            e.preventDefault();
            $('#api_key_field').toggle();
        });

        // Show API key field for Together AI when its link is clicked
        $('#add_togetherai_api_key_link').click(function(e) {
            e.preventDefault();
            $('#api_key_field_togetherai').toggle();
        });

        // Save the API key
        $('#save_api_key_button').click(function() {
            var apiKey = $('#google_api_key').val();
            var messageDiv = $('#api_key_status_message');
            var apiKeyField = $('#google_api_key'); // Reference to the API key input field
            var saveButton = $('#save_api_key_button');
            var helpText = $('#api_key_help_text');
            // AJAX call to save the API key
            $.ajax({
                url: ajaxurl, // WordPress AJAX
                type: 'POST',
                data: {
                    action: 'save_wpaicg_google_api_key',
                    api_key: apiKey,
                    nonce: '<?php 
echo wp_create_nonce( 'wpaicg-save-google-api' );
?>'
                },
                success: function(response) {
                    messageDiv.text('API key saved successfully').css('color', 'green').show();
                    setTimeout(function() {
                        messageDiv.fadeOut(); // Hide the message after 5 seconds
                        apiKeyField.fadeOut(); // Hide the API key field
                        saveButton.fadeOut(); // Hide the Save button
                        helpText.fadeOut(); // Hide the help text
                    }, 1000);
                },
                error: function() {
                    messageDiv.text('Error saving API key').css('color', 'red').show();
                    setTimeout(function() {
                        messageDiv.fadeOut(); // Hide the message after 5 seconds
                    }, 1000);
                }
            });
        });

        $('#save_togetherai_api_key_button').click(function() {
            var apiKey = $('#togetherai_api_key').val();
            var messageDiv = $('#api_key_status_message');
            var apiKeyField = $('#togetherai_api_key'); // Reference to the API key input field
            var saveButton = $('#save_togetherai_api_key_button');
            // AJAX call to save the API key
            $.ajax({
                url: ajaxurl, // WordPress AJAX
                type: 'POST',
                data: {
                    action: 'save_wpaicg_togetherai_api_key',
                    api_key: apiKey,
                    nonce: '<?php 
echo wp_create_nonce( 'wpaicg-save-togetherai-api' );
?>'
                },
                success: function(response) {
                    messageDiv.text('API key saved successfully').css('color', 'green').show();
                    setTimeout(function() {
                        messageDiv.fadeOut(); // Hide the message after a delay
                        apiKeyField.fadeOut(); // Hide the API key field
                        saveButton.fadeOut(); // Hide the Save button
                    }, 1000);
                },
                error: function() {
                    messageDiv.text('Error saving API key').css('color', 'red').show();
                    setTimeout(function() {
                        messageDiv.fadeOut(); // Hide the message after a delay
                    }, 1000);
                }
            });
        });


        // Function to handle sample prompt selection
        $('#sample_prompts').on('change', function() {
            var selectedPrompt = $(this).val();
            if (selectedPrompt) {
                // Clear the textarea and set the selected prompt
                $('.wpaicg_prompt').val(selectedPrompt);
            }
        });
        var wpaicg_generator_working = false;
        var eventGenerator = false;
        var wpaicg_limitLines = 1;
        function stopOpenAIGenerator(){
            $('.wpaicg-playground-buttons').show();
            $('.wpaicg_generator_stop').hide();
            wpaicg_generator_working = false;
            $('.wpaicg_generator_button .spinner').hide();
            $('.wpaicg_generator_button').removeAttr('disabled');
            eventGenerator.close();
        }
        $('.wpaicg_generator_button').click(function(){
            var btn = $(this);
            var title = $('.wpaicg_prompt').val();
            var selectedModel = $('#model_select').val();
            console.log(selectedModel);
            var selectedProvider = $('#provider_select').val();
            console.log(selectedProvider);
            // openai or openrouter
            if(selectedProvider === 'openai' || selectedProvider === 'openrouter'){
                if(!wpaicg_generator_working && title !== ''){
                var count_line = 0;
                var wpaicg_generator_result = $('.wpaicg_generator_result');
                btn.attr('disabled','disabled');
                btn.find('.spinner').show();
                btn.find('.spinner').css('visibility','unset');
                wpaicg_generator_result.val('');
                wpaicg_generator_working = true;
                $('.wpaicg_generator_stop').show();
                eventGenerator = new EventSource('<?php 
echo esc_html( add_query_arg( 'wpaicg_stream', 'yes', site_url() . '/index.php' ) );
?>&title='+title+'&nonce=<?php 
echo wp_create_nonce( 'wpaicg-ajax-nonce' );
?>'+'&engine='+selectedModel+'&provider='+selectedProvider + '&source=playground');
                var editor = tinyMCE.get('wpaicg_generator_result');
                var basicEditor = true;
                if ( $('#wp-wpaicg_generator_result-wrap').hasClass('tmce-active') && editor ) {
                    basicEditor = false;
                }
                var currentContent = '';
                var wpaicg_newline_before = false;
                var wpaicg_response_events = 0;
                eventGenerator.onmessage = function (e) {
                    if(basicEditor){
                        currentContent = $('#wpaicg_generator_result').val();
                    }
                    else{
                        currentContent = editor.getContent();
                        currentContent = currentContent.replace(/<\/?p(>|$)/g, "");
                    }

                    var resultData = JSON.parse(e.data);

                    // Check if the response contains the finish_reason property and if it's set to "stop"
                    var hasFinishReason = resultData.choices &&
                      resultData.choices[0] &&
                      (resultData.choices[0].finish_reason === "stop" ||
                       resultData.choices[0].finish_reason === "length");

                    if(hasFinishReason){
                        count_line += 1;
                        if(basicEditor) {
                            $('#wpaicg_generator_result').val(currentContent+'\n\n');
                        }
                        else{
                            editor.setContent(currentContent+'\n\n');
                        }
                        wpaicg_response_events = 0;
                    }
                    else{
                        var result = JSON.parse(e.data);
                        if(result.error !== undefined){
                            var content_generated = result.error.message;
                        }
                        else{
                            var content_generated = result.choices[0].delta !== undefined ? (result.choices[0].delta.content !== undefined ? result.choices[0].delta.content : '') : result.choices[0].text;
                        }
                        if((content_generated === '\n' || content_generated === ' \n' || content_generated === '.\n' || content_generated === '\n\n' || content_generated === '.\n\n' || content_generated === '"\n') && wpaicg_response_events > 0 && currentContent !== ''){
                            if(!wpaicg_newline_before) {
                                wpaicg_newline_before = true;
                                if(basicEditor){
                                    $('#wpaicg_generator_result').val(currentContent+'<br /><br />');
                                }
                                else{
                                    editor.setContent(currentContent+'<br /><br />');
                                }
                            }
                        }
                        else if(content_generated === '\n' && wpaicg_response_events === 0  && currentContent === ''){

                        }
                        else{
                            wpaicg_newline_before = false;
                            wpaicg_response_events += 1;
                            if(basicEditor){
                                $('#wpaicg_generator_result').val(currentContent+content_generated);
                            }
                            else{
                                editor.setContent(currentContent+content_generated);
                            }
                        }
                    }
                    if(count_line === wpaicg_limitLines){
                        stopOpenAIGenerator();
                    }
                };
                eventGenerator.onerror = function (e) {
                };
            }
            }
            else if (selectedProvider === 'togetherai') {
                sendRequestToTogetherAI(title, selectedModel);
            }
            else{
                sendRequestToServer(title, selectedModel);
            }
        });
        function convertMarkdownHeadingsToHTML(content) {
            // Replace Markdown H1 headings (e.g., "# Heading") with HTML <h1> tags
            content = content.replace(/^# (.*)$/gm, '<h1>$1</h1>');

            // Replace Markdown H2 headings (e.g., "## Heading") with HTML <h2> tags
            content = content.replace(/^## (.*)$/gm, '<h2>$1</h2>');

            // Replace Markdown H3 headings (e.g., "### Heading") with HTML <h3> tags
            content = content.replace(/^### (.*)$/gm, '<h3>$1</h3>');

            // Replace Markdown bold formatting
            content = content.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');

            return content;
        }

        function sendRequestToServer(title, model) {
            var spinner = $('.wpaicg_generator_button .spinner'); // Reference to the spinner
            var button = $('.wpaicg_generator_button'); // Reference to the button
            // Show spinner and disable button
            spinner.show();
            spinner.css('visibility', 'visible');
            button.prop('disabled', true);
            $.ajax({
                url: '<?php 
echo admin_url( 'admin-ajax.php' );
?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpaicg_generate_content_google',
                    title: title,
                    model: model,
                    nonce: '<?php 
echo wp_create_nonce( 'wpaicg_generate_content_google' );
?>'
                },
                success: function(response) {
                    var content = response.data.content;

                    // Convert Markdown headings to HTML
                    var formattedContent = convertMarkdownHeadingsToHTML(content);

                    // Replace newline characters with HTML line breaks
                    formattedContent = formattedContent.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');

                    var editor = tinyMCE.get('wpaicg_generator_result');
                    if (editor) {
                        editor.setContent(formattedContent); // Set the formatted content
                    } else {
                        $('#wpaicg_generator_result').val(formattedContent); // Set the formatted content
                    }
                    // Hide spinner and enable button
                    spinner.hide();
                    button.prop('disabled', false);
                    $('.wpaicg-playground-buttons').show();
                },
                error: function() {
                    // Handle errors here
                    alert('An error occurred while generating content.');
                    // Hide spinner and enable button
                    spinner.hide();
                    button.prop('disabled', false);
                }
            });
        }

        function sendRequestToTogetherAI(title, model) {
            var spinner = $('.wpaicg_generator_button .spinner');
            var button = $('.wpaicg_generator_button');
            spinner.show();
            spinner.css('visibility', 'visible');
            button.prop('disabled', true);

            $.ajax({
                url: '<?php 
echo admin_url( 'admin-ajax.php' );
?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpaicg_generate_content_togetherai', // Update the action for Together AI
                    title: title,
                    model: model,
                    nonce: '<?php 
echo wp_create_nonce( 'wpaicg_generate_content_togetherai' );
?>' // Update the nonce for Together AI
                },
                success: function(response) {
                    var content = response.data.content;
                    var formattedContent = convertMarkdownHeadingsToHTML(content);
                    formattedContent = formattedContent.replace(/\n\n/g, '<br><br>').replace(/\n/g, '<br>');

                    var editor = tinyMCE.get('wpaicg_generator_result');
                    if (editor) {
                        editor.setContent(formattedContent);
                    } else {
                        $('#wpaicg_generator_result').val(formattedContent);
                    }
                    spinner.hide();
                    button.prop('disabled', false);
                    $('.wpaicg-playground-buttons').show();
                },
                error: function() {
                    alert('An error occurred while generating content.');
                    spinner.hide();
                    button.prop('disabled', false);
                }
            });
        }

        $('.wpaicg_generator_stop').click(function (){
            stopOpenAIGenerator();
        });
        $('.wpaicg-playground-clear').click(function (){
            // $('.wpaicg_prompt').val('');
            var editor = tinyMCE.get('wpaicg_generator_result');
            var basicEditor = true;
            if ( $('#wp-wpaicg_generator_result-wrap').hasClass('tmce-active') && editor ) {
                basicEditor = false;
            }
            if(basicEditor){
                $('#wpaicg_generator_result').val('');
            }
            else{
                editor.setContent('');
            }
        });
        $('.wpaicg-playground-save').click(function (){
            var wpaicg_draft_btn = $(this);
            var title = $('.wpaicg_prompt').val();
            var editor = tinyMCE.get('wpaicg_generator_result');
            var basicEditor = true;
            if ( $('#wp-wpaicg_generator_result-wrap').hasClass('tmce-active') && editor ) {
                basicEditor = false;
            }
            var content = '';
            if (basicEditor){
                content = $('#wpaicg_generator_result').val();
            }
            else{
                content = editor.getContent();
            }
            if(title === ''){
                alert('<?php 
echo esc_html__( 'Please enter a title.', 'gpt3-ai-content-generator' );
?>');
            }
            else if(content === ''){
                alert('<?php 
echo esc_html__( 'Please wait until the content is generated.', 'gpt3-ai-content-generator' );
?>');
            }
            else{
                $.ajax({
                    url: '<?php 
echo admin_url( 'admin-ajax.php' );
?>',
                    data: {title: title, content: content, action: 'wpaicg_save_draft_post_extra','nonce': '<?php 
echo wp_create_nonce( 'wpaicg-ajax-nonce' );
?>'},
                    dataType: 'json',
                    type: 'POST',
                    beforeSend: function (){
                        wpaicg_draft_btn.attr('disabled','disabled');
                        wpaicg_draft_btn.append('<span class="spinner"></span>');
                        wpaicg_draft_btn.find('.spinner').css('visibility','unset');
                    },
                    success: function (res){
                        wpaicg_draft_btn.removeAttr('disabled');
                        wpaicg_draft_btn.find('.spinner').remove();
                        if(res.status === 'success'){
                            window.location.href = '<?php 
echo admin_url( 'post.php' );
?>?post='+res.id+'&action=edit';
                        }
                        else{
                            alert(res.msg);
                        }
                    },
                    error: function (){
                        wpaicg_draft_btn.removeAttr('disabled');
                        wpaicg_draft_btn.find('.spinner').remove();
                        alert('<?php 
echo esc_html__( 'Something went wrong', 'gpt3-ai-content-generator' );
?>');
                    }
                });
            }
        })
    })
</script>
