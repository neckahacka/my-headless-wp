<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

$current_language = isset($settings_row['wpai_language']) ? $settings_row['wpai_language'] : 'en';
$current_writing_style = isset($settings_row['wpai_writing_style']) ? $settings_row['wpai_writing_style'] : 'informal';
$current_writing_tone = isset($settings_row['wpai_writing_tone']) ? $settings_row['wpai_writing_tone'] : 'formal';

// Options for the dropdowns
$languages = \WPAICG\WPAICG_Util::get_instance()->wpaicg_languages;
$writing_styles = \WPAICG\WPAICG_Util::get_instance()->wpaicg_writing_styles;
$writing_tones = \WPAICG\WPAICG_Util::get_instance()->wpaicg_writing_tones;

$current_number_of_heading = isset($settings_row['wpai_number_of_heading']) ? $settings_row['wpai_number_of_heading'] : 3;
$current_heading_tag = isset($settings_row['wpai_heading_tag']) ? $settings_row['wpai_heading_tag'] : 'h1';

$heading_tags = \WPAICG\WPAICG_Util::get_instance()->wpaicg_heading_tags;

$current_outline_editor = isset($settings_row['wpai_modify_headings']) ? $settings_row['wpai_modify_headings'] : 0;
$current_tagline = isset($settings_row['wpai_add_tagline']) ? $settings_row['wpai_add_tagline'] : 0;
$current_bold_keywords = isset($settings_row['wpai_add_keywords_bold']) ? $settings_row['wpai_add_keywords_bold'] : 0;
$current_qa = isset($settings_row['wpai_add_faq']) ? $settings_row['wpai_add_faq'] : 0;

$current_wpaicg_toc = get_option('wpaicg_toc', 0);
$current_toc_title = get_option('wpaicg_toc_title', 'Table of Contents');
$current_toc_title_tag = get_option('wpaicg_toc_title_tag', 'h2');

$current_wpaicg_intro = isset($settings_row['wpai_add_intro']) ? $settings_row['wpai_add_intro'] : 0;
$current_hide_introduction = get_option('wpaicg_hide_introduction', 0);
$current_intro_title_tag = get_option('wpaicg_intro_title_tag', 'h2');

$current_wpaicg_conclusion = isset($settings_row['wpai_add_conclusion']) ? $settings_row['wpai_add_conclusion'] : 0;
$current_hide_conclusion = get_option('wpaicg_hide_conclusion', 0);
$current_conclusion_title_tag = get_option('wpaicg_conclusion_title_tag', 'h2');

$current_custom_prompt_enable = get_option('wpaicg_content_custom_prompt_enable', false);
$current_custom_prompt = get_option('wpaicg_content_custom_prompt', '');
$default_custom_prompt = \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_prompt;

$current_custom_image_prompt_enable = get_option('wpaicg_custom_image_prompt_enable', false);
$current_custom_image_prompt = get_option('wpaicg_custom_image_prompt', '');
$default_custom_image_prompt = \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_image_prompt;

$current_custom_featured_image_prompt_enable = get_option('wpaicg_custom_featured_image_prompt_enable', false);
$current_custom_featured_image_prompt = get_option('wpaicg_custom_featured_image_prompt', '');
$default_custom_featured_image_prompt = \WPAICG\WPAICG_Custom_Prompt::get_instance()->wpaicg_default_custom_featured_image_prompt;

// If current custom prompt is empty, set it to default
if (empty($current_custom_prompt)) {
    $current_custom_prompt = $default_custom_prompt;
}

// If current custom image prompt is empty, set it to default
if (empty($current_custom_image_prompt)) {
    $current_custom_image_prompt = $default_custom_image_prompt;
}

// If current custom featured image prompt is empty, set it to default
if (empty($current_custom_featured_image_prompt)) {
    $current_custom_featured_image_prompt = $default_custom_featured_image_prompt;
}

// Retrieve CTA Position
$current_cta_pos = isset($settings_row['wpai_cta_pos']) ? $settings_row['wpai_cta_pos'] : 'beg';

// Define CTA Position Options
$cta_positions = [
    'beg' => esc_html__('Beginning', 'gpt3-ai-content-generator'),
    'end' => esc_html__('End', 'gpt3-ai-content-generator'),
    // You can add more options here if needed
];
?>
<!-- Express Mode  & AutoGPT Settings -->
<div class="aipower-category-container content-settings-container">
    <h3><?php echo esc_html__('Content Writer', 'gpt3-ai-content-generator'); ?></h3>
    <div id="aipower-extra-settings" class="aipower-extra-settings">
        <div class="aipower-form-group">
            <!-- Visible Dummy Checkbox (Always Checked) -->
            <input type="checkbox" id="aipower_dummy_checkbox" name="aipower_dummy_checkbox" value="1" checked disabled>
            
            <!-- Writing Settings Icon -->
            <label for="aipower_writing_settings_icon"><?php echo esc_html__('Language and Headings', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipower-settings-icon" id="aipower_writing_settings_icon" title="<?php echo esc_attr__('Writing Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
        <!-- Outline Editor Checkbox -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower-modify-headings" name="wpai_modify_headings" value="1" <?php checked(1, $current_outline_editor); ?>>
            <label for="aipower-modify-headings"><?php echo esc_html__('Use Outline Editor', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <!-- Tagline Checkbox -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower-add-tagline" name="wpai_add_tagline" value="1" <?php checked(1, $current_tagline); ?>>
            <label for="aipower-add-tagline"><?php echo esc_html__('Generate Tagline', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <!-- Table of Contents Section -->
        <div class="aipower-form-group">
            <!-- Table of Contents Checkbox -->
            <input type="checkbox" id="aipower_toc" name="aipower_toc" value="1" <?php checked(1, $current_wpaicg_toc); ?>>
            <label for="aipower_toc">
                <?php echo esc_html__('Generate Table of Contents', 'gpt3-ai-content-generator'); ?>
            </label>
            
            <!-- Settings Icon -->
            <button type="button" class="aipower-settings-icon" id="aipower_toc_settings_icon" <?php echo $current_wpaicg_toc ? '' : 'disabled'; ?> title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
        <!-- Introduction Section -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_add_intro" name="aipower_add_intro" value="1" <?php checked(1, $current_wpaicg_intro); ?>>
            <label for="aipower_add_intro"><?php echo esc_html__('Generate Introduction', 'gpt3-ai-content-generator'); ?></label>

            <!-- Settings Icon -->
            <button type="button" class="aipower-settings-icon" id="aipower_intro_settings_icon" <?php echo $current_wpaicg_intro ? '' : 'disabled'; ?> title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
        <!-- Conclusion Section -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_add_conclusion" name="aipower_add_conclusion" value="1" <?php checked(1, $current_wpaicg_conclusion); ?>>
            <label for="aipower_add_conclusion"><?php echo esc_html__('Generate Conclusion', 'gpt3-ai-content-generator'); ?></label>

            <!-- Settings Icon -->
            <button type="button" class="aipower-settings-icon" id="aipower_conclusion_settings_icon" <?php echo $current_wpaicg_conclusion ? '' : 'disabled'; ?> title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
        <!-- Bold Keywords Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="aipower-add-keywords-bold" name="wpai_add_keywords_bold" value="1" <?php checked(1, $current_bold_keywords); ?>>
                <label for="aipower-add-keywords-bold"><?php echo esc_html__('Bold Keywords', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="wpai_add_keywords_bold_disabled">
                <label for="aipower-add-keywords-bold"><?php echo esc_html__('Bold Keywords', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>
        <!-- Q&A Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="aipower-add-faq" name="wpai_add_faq" value="1" <?php checked(1, $current_qa); ?>>
                <label for="aipower-add-faq"><?php echo esc_html__('Generate Q & A', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="wpai_add_faq_disabled">
                <label for="aipower-add-faq"><?php echo esc_html__('Generate Q & A', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>
        <!-- Custom Prompt Section -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_custom_prompt_enable" name="wpaicg_content_custom_prompt_enable" value="1" <?php checked(1, $current_custom_prompt_enable); ?>>
            <label for="aipower_custom_prompt_enable"><?php echo esc_html__('Use Custom Prompt', 'gpt3-ai-content-generator'); ?></label>

            <!-- Settings Icon -->
            <button type="button" class="aipower-settings-icon" id="aipower_custom_prompt_settings_icon" <?php echo $current_custom_prompt_enable ? '' : 'disabled'; ?> title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
    </div>
</div>
<!-- Writing Settings Modal -->
<div class="aipower-modal" id="aipower_writing_settings_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Writing Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <p><?php echo esc_html__('If Custom Prompt is enabled, the settings below will be ignored.', 'gpt3-ai-content-generator'); ?></p>
        <div class="aipower-form-group aipower-grouped-fields">
            <!-- Language Dropdown -->
            <div class="aipower-form-group">
                <label for="aipower-language-dropdown"><?php echo esc_html__('Language', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipower-language-dropdown">
                    <?php foreach ($languages as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $current_language); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Writing Style Dropdown -->
            <div class="aipower-form-group">
                <label for="aipower-writing-style-dropdown"><?php echo esc_html__('Writing Style', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipower-writing-style-dropdown">
                    <?php foreach ($writing_styles as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $current_writing_style); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Writing Tone Dropdown -->
            <div class="aipower-form-group">
                <label for="aipower-writing-tone-dropdown"><?php echo esc_html__('Writing Tone', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipower-writing-tone-dropdown">
                    <?php foreach ($writing_tones as $key => $label) : ?>
                        <option value="<?php echo esc_attr($key); ?>" <?php selected($key, $current_writing_tone); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Number of Headings Dropdown -->
            <div class="aipower-form-group">
                <label for="aipower-number-of-heading-dropdown"><?php echo esc_html__('Headings', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipower-number-of-heading-dropdown" name="wpai_number_of_heading">
                    <?php for ($i = 1; $i <= 15; $i++) : ?>
                        <option value="<?php echo esc_attr($i); ?>" <?php selected($i, $current_number_of_heading); ?>>
                            <?php echo esc_html($i); ?>
                        </option>
                    <?php endfor; ?>
                </select>
            </div>

            <!-- Heading Tag Dropdown -->
            <div class="aipower-form-group">
                <label for="aipower-heading-tag-dropdown"><?php echo esc_html__('Heading Tag', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipower-heading-tag-dropdown" name="wpai_heading_tag">
                    <?php foreach ($heading_tags as $tag) : ?>
                        <option value="<?php echo esc_attr($tag); ?>" <?php selected($tag, $current_heading_tag); ?>>
                            <?php echo esc_html($tag); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- CTA Position Dropdown -->
            <div class="aipower-form-group">
                <label for="aipower-cta-position-dropdown"><?php echo esc_html__('CTA Position', 'gpt3-ai-content-generator'); ?></label>
                <select id="aipower-cta-position-dropdown" name="wpai_cta_pos">
                    <?php foreach ($cta_positions as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $current_cta_pos); ?>>
                            <?php echo esc_html($label); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>
</div>
<!-- ToC Settings Modal -->
<div class="aipower-modal" id="aipower_toc_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Table of Contents Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- ToC Title -->
                <div class="aipower-form-group">
                    <label for="aipower_toc_title"><?php echo esc_html__('Title', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower_toc_title" name="aipower_toc_title" value="<?php echo esc_attr($current_toc_title); ?>">
                </div>

                <!-- ToC Tag -->
                <div class="aipower-form-group">
                    <label for="aipower_toc_title_tag"><?php echo esc_html__('Tag', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipower_toc_title_tag" name="aipower_toc_title_tag">
                        <?php foreach ($heading_tags as $tag): ?>
                            <option value="<?php echo esc_attr($tag); ?>" <?php echo $tag === $current_toc_title_tag ? 'selected' : ''; ?>>
                                <?php echo esc_html($tag); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Introduction Settings Modal -->
<div class="aipower-modal" id="aipower_intro_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Introduction Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Hide Title Option -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch">
                            <input 
                                type="checkbox" 
                                id="aipower_hide_introduction" 
                                name="aipower_hide_introduction" 
                                value="1" 
                                <?php checked(1, $current_hide_introduction); ?>
                            >
                            <span class="aipower-slider"></span>
                        </label>
                        <label class="aipower-general-switch-label" for="aipower_hide_introduction"><?php echo esc_html__('Hide Introduction Title', 'gpt3-ai-content-generator'); ?></label>
                    </div>
                </div>
                <!-- Introduction Title Tag -->
                <div class="aipower-form-group">
                    <label for="aipower_intro_title_tag"><?php echo esc_html__('Introduction Tag', 'gpt3-ai-content-generator'); ?></label>
                    <select id="aipower_intro_title_tag" name="aipower_intro_title_tag">
                        <?php foreach ($heading_tags as $tag): ?>
                            <option value="<?php echo esc_attr($tag); ?>" <?php echo $tag === $current_intro_title_tag ? 'selected' : ''; ?>>
                                <?php echo esc_html($tag); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Conclusion Settings Modal -->
<div class="aipower-modal" id="aipower_conclusion_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Conclusion Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Hide Conclusion Option -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch">
                            <input 
                                type="checkbox" 
                                id="wpaicg_hide_conclusion" 
                                name="wpaicg_hide_conclusion" 
                                value="1" 
                                <?php checked(1, $current_hide_conclusion); ?>
                            >
                            <span class="aipower-slider"></span>
                        </label>
                        <label class="aipower-general-switch-label" for="wpaicg_hide_conclusion"><?php echo esc_html__('Hide Conclusion Title', 'gpt3-ai-content-generator'); ?></label>
                    </div>
                </div>
                <!-- Conclusion Title Tag -->
                <div class="aipower-form-group">
                    <label for="wpaicg_conclusion_title_tag"><?php echo esc_html__('Conclusion Tag', 'gpt3-ai-content-generator'); ?></label>
                    <select id="wpaicg_conclusion_title_tag" name="wpaicg_conclusion_title_tag">
                        <?php foreach ($heading_tags as $tag): ?>
                            <option value="<?php echo esc_attr($tag); ?>" <?php selected($tag, $current_conclusion_title_tag); ?>>
                                <?php echo esc_html($tag); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Custom Prompt Modal -->
<div class="aipower-modal" id="aipower_custom_prompt_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Custom Prompt for Express Mode', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <p><?php echo esc_html__('You can customize the prompt used for generating content in Express Mode.', 'gpt3-ai-content-generator'); ?></p>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Custom Prompt Textarea -->
                <div class="aipower-form-group">
                    <textarea
                        rows="15"
                        id="aipower_custom_prompt"
                        name="wpaicg_content_custom_prompt"
                        data-default="<?php echo esc_attr($default_custom_prompt); ?>"
                        placeholder="<?php echo esc_attr__('Enter your custom prompt here...', 'gpt3-ai-content-generator'); ?>"
                    ><?php echo esc_textarea(wp_unslash($current_custom_prompt)); ?></textarea>
                </div>
            </div>

            <!-- Explanation Text and Reset Button -->
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                        <?php
                            echo sprintf(
                                esc_html__(
                                    'Make sure to include %s in your prompt. You can also add %s and %s to further customize your prompt.',
                                    'gpt3-ai-content-generator'
                                ),
                                '<code>[title]</code>',
                                '<code>[keywords_to_include]</code>',
                                '<code>[keywords_to_avoid]</code>'
                            );
                        ?>
                    <?php else: ?>
                        <?php
                            echo sprintf(
                                esc_html__(
                                    'Make sure to include %s in your prompt.',
                                    'gpt3-ai-content-generator'
                                ),
                                '<code>[title]</code>'
                            );
                        ?>
                    <?php endif; ?>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <button type="button" id="reset_custom_prompt" class="aipower-button reset-button">
                        <?php echo esc_html__('Reset', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>