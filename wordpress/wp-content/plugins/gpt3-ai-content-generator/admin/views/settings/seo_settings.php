<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
// Retrieve current SEO settings
$_wpaicg_seo_meta_desc = get_option('_wpaicg_seo_meta_desc', false);
$_wpaicg_seo_meta_tag = get_option('_wpaicg_seo_meta_tag', false);
$_wpaicg_gen_title_from_keywords = get_option('_wpaicg_gen_title_from_keywords', false);
$_wpaicg_original_title_in_prompt = get_option('_wpaicg_original_title_in_prompt', false);
$_wpaicg_focus_keyword_in_url = get_option('_wpaicg_focus_keyword_in_url', false);
$_wpaicg_sentiment_in_title = get_option('_wpaicg_sentiment_in_title', false);
$_wpaicg_power_word_in_title = get_option('_wpaicg_power_word_in_title', false);
$_wpaicg_shorten_url = get_option('_wpaicg_shorten_url', false);

// SEO Plugins Options
$seo_plugins_options = [
    [
        'plugin' => 'wordpress-seo/wp-seo.php',
        'option_name' => '_yoast_wpseo_metadesc',
        'label' => esc_html__('Update Yoast Meta', 'gpt3-ai-content-generator')
    ],
    [
        'plugin' => 'all-in-one-seo-pack/all_in_one_seo_pack.php',
        'option_name' => '_aioseo_description',
        'label' => esc_html__('Update All In One SEO Meta', 'gpt3-ai-content-generator')
    ],
    [
        'plugin' => 'seo-by-rank-math/rank-math.php',
        'option_name' => 'rank_math_description',
        'label' => esc_html__('Update Rank Math Meta', 'gpt3-ai-content-generator')
    ],
    [
        'plugin' => 'autodescription/autodescription.php',
        'option_name' => '_wpaicg_genesis_description',
        'label' => esc_html__('Update The SEO Framework Meta', 'gpt3-ai-content-generator')
    ],
];
?>
<div class="aipower-category-container seo-settings-container">
    <h3><?php echo esc_html__('SEO Optimization', 'gpt3-ai-content-generator'); ?></h3>
    <div id="aipower-seo-settings" class="aipower-seo-settings">
        <!-- Generate Meta Description Checkbox -->
        <div class="aipower-form-group">
            <input type="checkbox" id="_wpaicg_seo_meta_desc" name="_wpaicg_seo_meta_desc" value="1" <?php checked(1, $_wpaicg_seo_meta_desc); ?> />
            <label for="_wpaicg_seo_meta_desc"><?php echo esc_html__('Generate Meta Description', 'gpt3-ai-content-generator'); ?></label>
        </div>

        <!-- Include Meta in the Header Checkbox -->
        <div class="aipower-form-group">
            <input type="checkbox" id="_wpaicg_seo_meta_tag" name="_wpaicg_seo_meta_tag" value="1" <?php checked(1, $_wpaicg_seo_meta_tag); ?> />
            <label for="_wpaicg_seo_meta_tag"><?php echo esc_html__('Add Meta Tag to Head', 'gpt3-ai-content-generator'); ?></label>
        </div>

        <!-- Plugin-specific SEO Settings -->
        <?php
        foreach ($seo_plugins_options as $seo_plugin) {
            if (is_plugin_active($seo_plugin['plugin'])) {
                $option_value = get_option($seo_plugin['option_name'], false);
                ?>
                <div class="aipower-form-group">
                    <input type="checkbox" id="<?php echo esc_attr($seo_plugin['option_name']); ?>" name="<?php echo esc_attr($seo_plugin['option_name']); ?>" value="1" <?php checked(1, $option_value); ?> />
                    <label for="<?php echo esc_attr($seo_plugin['option_name']); ?>"><?php echo esc_html($seo_plugin['label']); ?></label>
                </div>
                <?php
            }
        }
        ?>
        <!-- Shorten URL Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="_wpaicg_shorten_url" name="_wpaicg_shorten_url" value="1" <?php checked(1, $_wpaicg_shorten_url); ?> />
                <label for="_wpaicg_shorten_url"><?php echo esc_html__('Shorten URL', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_shorten_url_disabled">
                <label for="_wpaicg_shorten_url"><?php echo esc_html__('Shorten URL', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>

        <!-- Generate Title from Keywords Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="_wpaicg_gen_title_from_keywords" name="_wpaicg_gen_title_from_keywords" value="1" <?php checked(1, $_wpaicg_gen_title_from_keywords); ?>/>
                <label for="_wpaicg_gen_title_from_keywords"><?php echo esc_html__('Generate Title from Keywords', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_gen_title_from_keywords_disabled" />
                <label for="_wpaicg_gen_title_from_keywords"><?php echo esc_html__('Generate Title from Keywords', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>

        <!-- Include Original Title in the Prompt Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="_wpaicg_original_title_in_prompt" name="_wpaicg_original_title_in_prompt" value="1" <?php checked(1, $_wpaicg_original_title_in_prompt); ?> <?php if (!$_wpaicg_gen_title_from_keywords) echo 'disabled'; ?> />
                <label for="_wpaicg_original_title_in_prompt"><?php echo esc_html__('Include Original Title in the Prompt', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_original_title_in_prompt_disabled" />
                <label for="_wpaicg_original_title_in_prompt"><?php echo esc_html__('Include Original Title in the Prompt', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>

        <!-- Enforce Focus Keyword in URL Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="_wpaicg_focus_keyword_in_url" name="_wpaicg_focus_keyword_in_url" value="1" <?php checked(1, $_wpaicg_focus_keyword_in_url); ?> />
                <label for="_wpaicg_focus_keyword_in_url"><?php echo esc_html__('Enforce Focus Keyword in URL', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_focus_keyword_in_url_disabled" />
                <label for="_wpaicg_focus_keyword_in_url"><?php echo esc_html__('Enforce Focus Keyword in URL', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>

        <!-- Use Sentiment in Title Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="_wpaicg_sentiment_in_title" name="_wpaicg_sentiment_in_title" value="1" <?php checked(1, $_wpaicg_sentiment_in_title); ?> />
                <label for="_wpaicg_sentiment_in_title"><?php echo esc_html__('Use Sentiment in Title', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_sentiment_in_title_disabled" />
                <label for="_wpaicg_sentiment_in_title"><?php echo esc_html__('Use Sentiment in Title', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>

        <!-- Use Power Word in Title Checkbox -->
        <div class="aipower-form-group">
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="_wpaicg_power_word_in_title" name="_wpaicg_power_word_in_title" value="1" <?php checked(1, $_wpaicg_power_word_in_title); ?> />
                <label for="_wpaicg_power_word_in_title"><?php echo esc_html__('Use Power Word in Title', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_power_word_in_title_disabled" />
                <label for="_wpaicg_power_word_in_title"><?php echo esc_html__('Use Power Word in Title', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>
    </div>
</div>
