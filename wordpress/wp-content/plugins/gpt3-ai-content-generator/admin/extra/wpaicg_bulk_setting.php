<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$success_save = false;

// Check if the form was submitted
if(isset($_POST['save_bulk_setting'])) {
    // Verify nonce for security
    if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'save_bulk_setting_nonce')) {
        die(esc_html__('Nonce verification failed', 'gpt3-ai-content-generator'));
    }

    // Define a list of options to handle, with their sanitization callbacks
    $options = [
        'wpaicg_restart_queue'       => 'sanitize_text_field',
        'wpaicg_try_queue'           => 'sanitize_text_field',
        'wpaicg_custom_prompt_auto'   => 'wp_kses_post', // Assuming this one needs to allow some HTML
        'wpaicg_custom_prompt_enable' => 'boolval',      // Converts to boolean true/false
        'wpaicg_rss_new_title'       => 'boolval',       // Converts to boolean true/false
        'wpaicg_rss_keywords'        => 'sanitize_text_field'
    ];

    foreach ($options as $option_name => $sanitization_callback) {
        if (isset($_POST[$option_name]) && !empty($_POST[$option_name])) {
            // Sanitize and update the option
            $value = call_user_func($sanitization_callback, $_POST[$option_name]);
            update_option($option_name, $value);
        } else {
            // Delete the option if not set or empty
            delete_option($option_name);
        }
    }

    // Successfully saved settings
    $success_save = true;
}

// Retrieve current option values
$wpaicg_restart_queue       = get_option('wpaicg_restart_queue', 20);
$wpaicg_try_queue           = get_option('wpaicg_try_queue', '');
$wpaicg_ai_model            = get_option('wpaicg_ai_model','');
$wpaicg_custom_prompt_enable = get_option('wpaicg_custom_prompt_enable', false);

// New default prompt
$wpaicg_default_custom_prompt = 'Create a compelling and well-researched article of at least 500 words on the topic of "[title]" in English. Structure the article with clear headings enclosed within the appropriate heading tags (e.g., <h1>, <h2>, etc.) and engaging subheadings. Ensure that the content is informative and provides valuable insights to the reader. Incorporate relevant examples, case studies, and statistics to support your points. Organize your ideas using unordered lists with <ul> and <li> tags where appropriate. Conclude with a strong summary that ties together the key takeaways of the article. Wrap each paragraph in <p> tags for improved readability. Do not start your response with ```html and do not return Markdown. Use only valid HTML for your response.';

$wpaicg_custom_prompt_auto = get_option('wpaicg_custom_prompt_auto', $wpaicg_default_custom_prompt);
$wpaicg_rss_new_title      = get_option('wpaicg_rss_new_title', false);
$wpaicg_rss_keywords       = get_option('wpaicg_rss_keywords', '');
?>
<?php
if($success_save){
    echo '<div class="wpaicg_sheets_cron_msg">Record updated successfully</div>';
}
?>
<form action="" method="post" class="wpaicg_auto_settings">
    <?php wp_nonce_field('save_bulk_setting_nonce'); ?>
    
    <h1><?php echo esc_html__('Queue','gpt3-ai-content-generator')?></h1>
    <div class="nice-form-group">
        <label><?php echo esc_html__('Restart Failed Jobs After','gpt3-ai-content-generator')?></label>
        <select name="wpaicg_restart_queue" style="width: 120px;">
            <?php
            for($i = 20; $i <=60; $i+=10){
                echo '<option'.($wpaicg_restart_queue == $i ? ' selected':'').' value="'.esc_html($i).'">'.esc_html($i).'</option>';
            }
            ?>
        </select>
        <?php echo esc_html__('minutes','gpt3-ai-content-generator')?>
        <a href="https://docs.aipower.org/docs/AutoGPT/auto-content-writer/bulk-editor#auto-restart-failed-jobs" target="_blank">?</a>
    </div>
    <div class="nice-form-group">
        <label><?php echo esc_html__('Try Queue','gpt3-ai-content-generator')?></label>
        <select name="wpaicg_try_queue" style="width: 120px;">
            <?php
            for($i = 1; $i <=10; $i++){
                echo '<option'.($wpaicg_try_queue == $i ? ' selected':'').' value="'.esc_html($i).'">'.esc_html($i).'</option>';
            }
            ?>
        </select>
        <?php echo esc_html__('times','gpt3-ai-content-generator')?>
        <a href="https://docs.aipower.org/docs/AutoGPT/auto-content-writer/bulk-editor#auto-restart-failed-jobs" target="_blank">?</a>
    </div>

    <p></p>
    <h1><?php echo esc_html__('RSS','gpt3-ai-content-generator')?></h1>
    <div class="nice-form-group">
        <input 
            <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? '' : ' disabled'?>
            <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() && $wpaicg_rss_new_title ? ' checked':''?> 
            class="wpaicg_rss_new_title" 
            type="checkbox" 
            value="1" 
            name="wpaicg_rss_new_title"
        >
        <label><?php echo esc_html__('Generate New Title','gpt3-ai-content-generator')?></label>
        <?php if(!\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
            <!-- Display Pro label instead of "Available in Pro" text -->
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="pro-feature-label">
                <?php echo esc_html__('Pro','gpt3-ai-content-generator')?>
            </a>
        <?php endif; ?>
        <a href="https://docs.aipower.org/docs/AutoGPT/auto-content-writer/rss#generate-new-title" target="_blank">?</a>
    </div>
    <div class="nice-form-group">
        <label for="wpaicg_rss_keywords"><?php echo esc_html__('Keywords to Filter (comma separated)','gpt3-ai-content-generator')?></label>
        <input 
            type="text" 
            id="wpaicg_rss_keywords" 
            name="wpaicg_rss_keywords" 
            value="<?php echo esc_attr($wpaicg_rss_keywords); ?>" 
            style="width: 50%;" 
            <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? '' : ' disabled'?>
        >
        <?php if(!\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
            <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="pro-feature-label">
                <?php echo esc_html__('Pro','gpt3-ai-content-generator')?>
            </a>
        <?php endif; ?>
        <a href="https://docs.aipower.org/docs/AutoGPT/auto-content-writer/rss#keyword-filtering" target="_blank">?</a>
    </div>

    <p></p>
    <h1><?php echo esc_html__('Content Generation','gpt3-ai-content-generator')?></h1>

    <div class="nice-form-group">
        <input 
            <?php echo $wpaicg_custom_prompt_enable ? ' checked':''?> 
            class="wpaicg_custom_prompt_enable" 
            type="checkbox" 
            value="1" 
            name="wpaicg_custom_prompt_enable"
        >
        <label><?php echo esc_html__('Enable Custom Prompt','gpt3-ai-content-generator')?></label>
        <a href="https://docs.aipower.org/docs/AutoGPT/auto-content-writer/bulk-editor#using-custom-prompt" target="_blank">?</a>
    </div>
    <p></p>
    <!-- Custom Prompt Area (only the textarea is hidden/show, but we still show the main container for clarity) -->
    <div class="wpaicg_custom_prompt_wrapper">
        <div class="wpaicg_custom_prompt_auto" style="<?php echo $wpaicg_custom_prompt_enable ? '' : 'display:none'?>">
            <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <!-- Pro Users: Template selector just above the textarea, with a gap -->
                <div style="margin-bottom: 10px;">
                    <label><strong><?php echo esc_html__('Select a Template','gpt3-ai-content-generator'); ?></strong></label><br>
                    <select name="wpaicg_template_selector" class="wpaicg_template_selector" style="margin-top:5px;">
                        <optgroup label="<?php echo esc_html__('Default','gpt3-ai-content-generator'); ?>">
                            <option value="default_template"><?php echo esc_html__('Default Template','gpt3-ai-content-generator'); ?></option>
                        </optgroup>
                        <optgroup label="<?php echo esc_html__('RSS','gpt3-ai-content-generator'); ?>">
                            <option value="rss_short"><?php echo esc_html__('Title + Description (Short Article)','gpt3-ai-content-generator'); ?></option>
                            <option value="rss_long"><?php echo esc_html__('Title + Description (Long Article)','gpt3-ai-content-generator'); ?></option>
                            <option value="rss_deepdive"><?php echo esc_html__('Title + Description (Deep Dive)','gpt3-ai-content-generator'); ?></option>
                        </optgroup>
                        
                        <optgroup label="<?php echo esc_html__('Keyword Focus','gpt3-ai-content-generator'); ?>">
                            <option value="kw_basic"><?php echo esc_html__('Include & Avoid Keywords (Basic)','gpt3-ai-content-generator'); ?></option>
                            <option value="kw_detailed"><?php echo esc_html__('Include & Avoid Keywords (Detailed)','gpt3-ai-content-generator'); ?></option>
                        </optgroup>
                        
                        <optgroup label="<?php echo esc_html__('Advanced','gpt3-ai-content-generator'); ?>">
                            <option value="tech_expert"><?php echo esc_html__('Technical Expert Article','gpt3-ai-content-generator'); ?></option>
                            <option value="narrative_style"><?php echo esc_html__('Narrative Style Deep Analysis','gpt3-ai-content-generator'); ?></option>
                        </optgroup>
                    </select>
                </div>
            <?php endif; ?>

            <!-- Textarea -->
            <div class="nice-form-group" style="margin-bottom: 10px;">
                <textarea rows="15" class="wpaicg_custom_prompt_auto_text" name="wpaicg_custom_prompt_auto"><?php 
                    echo esc_html(str_replace("\\",'',$wpaicg_custom_prompt_auto));
                ?></textarea>
            </div>

            <!-- Info text (below textarea) -->
            <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <p style="margin-bottom: 10px;">
                    <strong><?php echo esc_html__('Variables you can use:','gpt3-ai-content-generator'); ?></strong>
                    <br>
                    &bull; <code>[title]</code> (<?php echo esc_html__('mandatory for all modules','gpt3-ai-content-generator'); ?>)
                    <br>
                    &bull; <code>[keywords_to_include]</code> (<?php echo esc_html__('Bulk Editor & Google Sheets','gpt3-ai-content-generator'); ?>)
                    <br>
                    &bull; <code>[keywords_to_avoid]</code> (<?php echo esc_html__('Bulk Editor & Google Sheets','gpt3-ai-content-generator'); ?>)
                    <br>
                    &bull; <code>[description]</code> (<?php echo esc_html__('RSS module','gpt3-ai-content-generator'); ?>)
                </p>
            <?php else: ?>
                <!-- Free Users: Only [title] is allowed -->
                <p style="margin-bottom: 10px;">
                    <?php echo esc_html__('Make sure to include [title] in your prompt.','gpt3-ai-content-generator'); ?>
                </p>
            <?php endif; ?>

            <!-- Error message -->
            <div class="wpaicg_custom_prompt_auto_error" style="margin-bottom: 10px;"></div>
        </div>

        <!-- Buttons (Reset + Save side-by-side) - Always visible for all users -->
        <div style="display: flex; gap: 10px; margin-top: 10px;">
            <button 
                style="color: #fff;background: #df0707;border-color: #df0707;" 
                data-prompt="<?php echo esc_html($wpaicg_default_custom_prompt)?>" 
                class="button wpaicg_custom_prompt_reset" 
                type="button"
            >
                <?php echo esc_html__('Reset','gpt3-ai-content-generator')?>
            </button>
            <button 
                class="button-primary button wpaicg_auto_settings_save" 
                name="save_bulk_setting"
            >
                <?php echo esc_html__('Save','gpt3-ai-content-generator')?>
            </button>
        </div>
    </div>
</form>

<script>
jQuery(document).ready(function ($){
    let wpaicg_ai_model = '<?php echo esc_html($wpaicg_ai_model)?>';

    // Show/hide custom prompt textarea
    $('.wpaicg_custom_prompt_enable').on('change', function (){
        if ($(this).is(':checked')) {
            $('.wpaicg_custom_prompt_auto').show();
        } else {
            $('.wpaicg_custom_prompt_auto').hide();
        }
    });

    <?php if(!\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
    // Free plan: disallow [keywords_to_include], [keywords_to_avoid], [description]
    $('.wpaicg_custom_prompt_auto_text').on('input', function (e){
        let prompt = $(e.currentTarget).val();
        if(
            prompt.indexOf('[keywords_to_include]') > -1 ||
            prompt.indexOf('[keywords_to_avoid]') > -1 ||
            prompt.indexOf('[description]') > -1
        ){
            $('.wpaicg_custom_prompt_auto_error').html(
                '<div style="color: #f00;"><p><?php echo esc_html__('Please remove restricted variables ([keywords_to_include], [keywords_to_avoid], [description]) – they are only available in Pro plan.','gpt3-ai-content-generator')?></p></div>'
            );
            $('.wpaicg_auto_settings_save').attr('disabled','disabled');
        }
        else{
            $('.wpaicg_custom_prompt_auto_error').empty();
            $('.wpaicg_auto_settings_save').removeAttr('disabled');
        }
    });
    <?php endif; ?>

    // Reset button
    $('.wpaicg_custom_prompt_reset').click(function (){
        let prompt = $(this).attr('data-prompt');
        $('textarea[name=wpaicg_custom_prompt_auto]').val(prompt);
        $('.wpaicg_custom_prompt_auto_error').empty();
        $('.wpaicg_auto_settings_save').removeAttr('disabled');
    });

    // Template dropdown (Pro only)
    <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
    let templateMap = {

        // -- DEFAULT TEMPLATE --
        "default_template":
`Create a compelling and well-researched article of at least 500 words on the topic of "[title]" in English.
- Structure the article with clear headings enclosed within the appropriate heading tags (e.g., <h1>, <h2>, etc.) and engaging subheadings.
- Ensure that the content is informative and provides valuable insights to the reader.
- Incorporate relevant examples, case studies, and statistics to support your points.
- Organize your ideas using unordered lists with <ul> and <li> tags where appropriate.
- Conclude with a strong summary that ties together the key takeaways of the article.
- Wrap each paragraph in <p> tags for improved readability.
- Do not start your response with \`\`\`html and do not return Markdown.
- Use only valid HTML for your response.`,

        // -- RSS TEMPLATES --
        "rss_short": 
`Use [title] and [description] to craft a concise article in English. 
- Please enclose headings in <h1>, <h2>, etc. 
- Use <p> for paragraphs and <ul>/<li> for short bullet points. 
- Provide a quick summary and key points. 
- Do not return Markdown; only use valid HTML. 
- Do not start your response with \`\`\`html.`,

        "rss_long": 
`Write a detailed article based on [title] and [description] in English. 
- Include multiple <h2> subheadings, <p> paragraphs, and relevant examples or data. 
- Provide a thorough analysis and a concluding <p> that summarizes. 
- Do not use Markdown; use HTML tags only. 
- Do not begin with \`\`\`html. 
- Keep the length around 800-1000 words.`,

        "rss_deepdive":
`Compose an in-depth, well-researched article in English using [title] and [description] as references. 
- Incorporate <h2> sub-sections, <h3> for subtopics, <p> paragraphs, <ul> or <ol> for lists. 
- Include case studies, stats, or relevant historical context. 
- End with a powerful summary in <p>. 
- Do not return any Markdown; only HTML. 
- Avoid starting with \`\`\`html. 
- Target 1000+ words.`,

        // -- KEYWORD FOCUS TEMPLATES --
        "kw_basic":
`Write an article for [title] in a straightforward manner in English. 
- Integrate keywords from [keywords_to_include] while excluding terms from [keywords_to_avoid]. 
- Use <h2> headings, <p> paragraphs, and basic lists (<ul>/<li>). 
- Provide an easy-to-follow structure, and conclude with a short summary. 
- Do not use Markdown; stick to HTML. 
- Do not begin the response with \`\`\`html.`,

        "kw_detailed":
`Create a comprehensive article around [title] in English, fully incorporating [keywords_to_include] and strictly avoiding [keywords_to_avoid]. 
- Utilize multiple <h2> and <h3> headings, <p> paragraphs, plus relevant data or statistics. 
- Summarize with a final <p>. 
- Return only valid HTML, no Markdown. 
- Do not start with \`\`\`html. 
- Aim for at least 1000 words.`,

        // -- ADVANCED TEMPLATES --
        "tech_expert":
`Write a highly technical, expert-level article on [title] in English. 
- Use proper HTML structure with <h1>, <h2>, <h3>, and <p>. 
- Include real-world examples, references to research, and complex data or stats. 
- If available, incorporate [keywords_to_include] and avoid [keywords_to_avoid]. 
- Provide a final <p> summarizing the key takeaways. 
- Do not provide your response in Markdown. 
- Do not open with \`\`\`html. 
- Ensure thorough explanations (1000+ words).`,

        "narrative_style":
`Create a narrative-driven analysis of [title] in English. 
- Use <h2> for chapter-like sections, <p> for storytelling paragraphs, and bullet points where applicable. 
- Blend storytelling with factual details and real anecdotes. 
- If needed, incorporate [keywords_to_include] and avoid [keywords_to_avoid]. 
- End with a reflective conclusion in <p>. 
- Return only HTML, not Markdown, and do not begin with \`\`\`html.`
    };

    $('.wpaicg_template_selector').change(function(){
        let selected = $(this).val();
        if (templateMap[selected]) {
            $('textarea[name=wpaicg_custom_prompt_auto]').val(templateMap[selected]);
        }
    });
    <?php endif; ?>
});
</script>