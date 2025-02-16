<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
// woocommerce settings
$wpaicg_woo_generate_title = get_option('wpaicg_woo_generate_title',false);
$wpaicg_woo_generate_description = get_option('wpaicg_woo_generate_description',false);
$wpaicg_woo_generate_short = get_option('wpaicg_woo_generate_short',false);
$wpaicg_woo_generate_tags = get_option('wpaicg_woo_generate_tags',false);
$wpaicg_woo_meta_description = get_option('wpaicg_woo_meta_description',false);
$_wpaicg_shorten_woo_url = get_option('_wpaicg_shorten_woo_url', false);
$wpaicg_generate_woo_focus_keyword = get_option('wpaicg_generate_woo_focus_keyword', false);
$wpaicg_enforce_woo_keyword_in_url = get_option('wpaicg_enforce_woo_keyword_in_url', false);
$wpaicg_woo_custom_prompt = get_option('wpaicg_woo_custom_prompt',false);
$wpaicg_woo_custom_prompt_title = get_option('wpaicg_woo_custom_prompt_title',esc_html__('Compose an SEO-optimized title in English for the following product: %s. Ensure it is engaging, concise, and includes relevant keywords to maximize its visibility on search engines.','gpt3-ai-content-generator'));
$wpaicg_woo_custom_prompt_short = get_option('wpaicg_woo_custom_prompt_short',esc_html__('Provide a compelling and concise summary in English for the following product: %s, highlighting its key features, benefits, and unique selling points.','gpt3-ai-content-generator'));
$wpaicg_woo_custom_prompt_description = get_option('wpaicg_woo_custom_prompt_description',esc_html__('Craft a comprehensive and engaging product description in English for: %s. Include specific details, features, and benefits, as well as the value it offers to the customer, thereby creating a compelling narrative around the product.','gpt3-ai-content-generator'));
$wpaicg_woo_custom_prompt_keywords = get_option('wpaicg_woo_custom_prompt_keywords',esc_html__('Propose a set of relevant keywords in English for the following product: %s. The keywords should be directly related to the product, enhancing its discoverability. Please present these keywords in a comma-separated format, avoiding the use of symbols such as -, #, etc.','gpt3-ai-content-generator'));
$wpaicg_woo_custom_prompt_meta = get_option('wpaicg_woo_custom_prompt_meta',esc_html__('Craft a compelling and concise meta description in English for: %s. Aim to highlight its key features and benefits within a limit of 155 characters, while incorporating relevant keywords for SEO effectiveness.','gpt3-ai-content-generator'));
$wpaicg_woo_custom_prompt_focus_keyword = get_option('wpaicg_woo_custom_prompt_focus_keyword', esc_html__('Identify the primary keyword for the following product: %s. Please respond in English. No additional comments, just the keyword.', 'gpt3-ai-content-generator'));
function get_shortcode_text() {
    return '
    <div class="aipower-copy-text">
        You can use these shortcodes in your custom prompt: 
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_short_description]">[current_short_description]</span>, 
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_full_description]">[current_full_description]</span>, 
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_attributes]">[current_attributes]</span>, 
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_categories]">[current_categories]</span>, 
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_price]">[current_price]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_weight]">[current_weight]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_length]">[current_length]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_width]">[current_width]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_height]">[current_height]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_sku]">[current_sku]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_purchase_note]">[current_purchase_note]</span>,
        <span class="aipower-woocommerce-shortcode" data-aipower-clipboard-text="[current_focus_keywords]">[current_focus_keywords]</span>.
    </div>';
}
?>
<!-- WooCommerce Settings -->
<div class="aipower-category-container woocommerce-settings-container">
    <h3><?php echo esc_html__('WooCommerce', 'gpt3-ai-content-generator'); ?></h3>
    <div id="aipower-woocommerce-settings" class="aipower-woocommerce-settings">
        <!-- Product Writer Section -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_woo_generate_title" name="wpaicg_woo_generate_title" value="1" <?php checked(1, $wpaicg_woo_generate_title); ?>>
            <label for="aipower_woo_generate_title"><?php echo esc_html__('Generate Product Title', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_woo_generate_description" name="wpaicg_woo_generate_description" value="1" <?php checked(1, $wpaicg_woo_generate_description); ?>>
            <label for="aipower_woo_generate_description"><?php echo esc_html__('Generate Full Product Description', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_woo_generate_short" name="wpaicg_woo_generate_short" value="1" <?php checked(1, $wpaicg_woo_generate_short); ?>>
            <label for="aipower_woo_generate_short"><?php echo esc_html__('Generate Short Product Description', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_woo_generate_tags" name="wpaicg_woo_generate_tags" value="1" <?php checked(1, $wpaicg_woo_generate_tags); ?>>
            <label for="aipower_woo_generate_tags"><?php echo esc_html__('Generate Product Tags', 'gpt3-ai-content-generator'); ?></label>
        </div>

        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_woo_meta_description" name="wpaicg_woo_meta_description" value="1" <?php checked(1, $wpaicg_woo_meta_description); ?>>
            <label for="aipower_woo_meta_description"><?php echo esc_html__('Generate Meta Description', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <div class="aipower-form-group">
            <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="aipower_shorten_woo_url" name="_wpaicg_shorten_woo_url" value="1" <?php checked(1, $_wpaicg_shorten_woo_url); ?>>
                <label for="aipower_shorten_woo_url"><?php echo esc_html__('Shorten Product URL', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="_wpaicg_shorten_woo_url">
                <label for="aipower_shorten_woo_url"><?php echo esc_html__('Shorten Product URL', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>
        <div class="aipower-form-group">
            <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="aipower_generate_woo_focus_keyword" name="wpaicg_generate_woo_focus_keyword" value="1" <?php checked(1, $wpaicg_generate_woo_focus_keyword); ?>>
                <label for="aipower_generate_woo_focus_keyword"><?php echo esc_html__('Generate Focus Keyword', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="wpaicg_generate_woo_focus_keyword">
                <label for="aipower_generate_woo_focus_keyword"><?php echo esc_html__('Generate Focus Keyword', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>
        <div class="aipower-form-group">
            <?php if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <input type="checkbox" id="aipower_enforce_woo_keyword_in_url" name="wpaicg_enforce_woo_keyword_in_url" value="1" <?php checked(1, $wpaicg_enforce_woo_keyword_in_url); ?>>
                <label for="aipower_enforce_woo_keyword_in_url"><?php echo esc_html__('Enforce Focus Keyword in URL', 'gpt3-ai-content-generator'); ?></label>
            <?php else: ?>
                <input type="checkbox" value="0" disabled name="wpaicg_enforce_woo_keyword_in_url">
                <label for="aipower_enforce_woo_keyword_in_url"><?php echo esc_html__('Enforce Focus Keyword in URL', 'gpt3-ai-content-generator'); ?></label>
                <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
            <?php endif; ?>
        </div>
        <!-- Custom Prompt Section -->
        <div class="aipower-form-group">
            <input type="checkbox" id="aipower_woo_custom_prompt_enable" name="wpaicg_woo_custom_prompt" value="1" <?php checked(1, $wpaicg_woo_custom_prompt); ?>>
            <label for="aipower_woo_custom_prompt_enable"><?php echo esc_html__('Use Custom Prompt', 'gpt3-ai-content-generator'); ?></label>

            <!-- Settings Icon -->
            <button type="button" class="aipower-settings-icon" id="aipower_woo_custom_prompt_settings_icon" <?php echo $wpaicg_woo_custom_prompt ? '' : 'disabled'; ?> title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
    </div>
</div>
<!-- Custom Prompt Modal -->
<div class="aipower-modal" id="aipower_woo_custom_prompt_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Custom Prompt Configuration', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <!-- Title Prompt -->
            <div class="aipower-collapsible-section">
                <button class="aipower-collapsible-toggle"><label for="aipower_woo_custom_prompt_title" class="aipower-woocommerce-labels"><?php echo esc_html__('Title Prompt', 'gpt3-ai-content-generator'); ?></label></button>
                <div class="aipower-collapsible-content">
                    <div class="aipower-dropdown-container">
                        <select id="aipower_woocommerce_title_dropdown" name="aipower_woocommerce_title_dropdown">
                            <option value=""><?php echo esc_html__('-- Select a Template --', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Create an SEO-friendly and eye-catching title for the product: %s. The title should emphasize its key features. Use the following for context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('Incorporate Key Features', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Devise a captivating and SEO-optimized title for the following product: %s that highlights its unique selling points. Use these details for reference: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('Highlight Unique Selling Points', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Craft a product title for %s that is not only SEO-optimized but also engages the customer and informs them why this is the product they’ve been looking for. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Engage and Inform', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Construct an SEO-optimized title for the product: %s that is rich in keywords relevant to the product. Use the following for additional context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('Keyword Rich', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Generate a concise yet comprehensive title for the product: %s that covers all the essential points customers are interested in. Make sure to utilize Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for a better context."><?php echo esc_html__('Concise Yet Comprehensive', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <textarea rows="5" id="aipower_woo_custom_prompt_title" name="wpaicg_woo_custom_prompt_title"><?php echo esc_textarea(wp_unslash($wpaicg_woo_custom_prompt_title)); ?></textarea>
                    <?php echo get_shortcode_text(); ?>
                </div>
            </div>

            <!-- Short Description Prompt -->
            <div class="aipower-collapsible-section">
                <button class="aipower-collapsible-toggle"><label for="aipower_custom_prompt_short" class="aipower-woocommerce-labels"><?php echo esc_html__('Short Description Prompt', 'gpt3-ai-content-generator'); ?></label></button>
                <div class="aipower-collapsible-content">
                    <div class="aipower-dropdown-container">
                        <select id="aipower_woocommerce_short_dropdown" name="aipower_woocommerce_short_dropdown">
                            <option value=""><?php echo esc_html__('-- Select a Template --', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Compose a short description for the product: %s that succinctly highlights its key features and benefits. Use the following attributes and description for context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('Highlight Features and Benefits', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Write a short description for the product: %s that clearly outlines how it solves a specific problem for the customer. Reference these details for a better understanding: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('Solve a Problem', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Craft a compelling short description for the product: %s that emphasizes what sets it apart from competitors. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Emphasize Uniqueness', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Create an emotive short description for the product: %s that aims to establish an emotional connection with potential buyers. Use the following for context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('Invoke Emotion', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Devise an SEO-optimized short description for the product: %s, incorporating relevant keywords without sacrificing readability. Use these details for reference: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories]."><?php echo esc_html__('SEO-Focused', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <textarea rows="5" id="aipower_custom_prompt_short" name="wpaicg_woo_custom_prompt_short"><?php echo esc_textarea(wp_unslash($wpaicg_woo_custom_prompt_short)); ?></textarea>
                    <?php echo get_shortcode_text(); ?>
                </div>
            </div>

            <!-- Full Description Prompt -->
            <div class="aipower-collapsible-section">
                <button class="aipower-collapsible-toggle"><label for="aipower_custom_prompt_desc" class="aipower-woocommerce-labels"><?php echo esc_html__('Full Description Prompt', 'gpt3-ai-content-generator'); ?></label></button>
                <div class="aipower-collapsible-content">
                    <div class="aipower-dropdown-container">
                        <select id="aipower_woocommerce_desc_dropdown" name="aipower_woocommerce_desc_dropdown">
                            <option value=""><?php echo esc_html__('-- Select a Template --', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Craft an extensive and captivating narrative around the product: %s. Dive deep into its key features, benefits, and value proposition. Use its Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] to enrich the narrative."><?php echo esc_html__('Highlight Key Features and Benefits', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Develop a comprehensive and detailed description for the product: %s that serves as a complete guide for the customer, detailing its functionality, features, and use-cases. Make sure to incorporate its Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories]."><?php echo esc_html__('Emphasize Practicality and Usability', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Write a compelling product description for %s that evokes an emotional connection, inspiring the customer to visualize themselves using the product. Leverage its Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] to add depth and context."><?php echo esc_html__('Evoke Emotional Connection', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Construct an in-depth description for the product: %s that focuses on its unique selling propositions. Distinguish it from competitors and highlight what makes it a must-have. Use Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] for a more detailed context."><?php echo esc_html__('Showcase Unique Selling Points', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Compose an SEO-optimized, yet customer-centric, description for the product: %s. Include relevant keywords naturally, and focus on answering any questions a customer might have about the product. Utilize Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] for richer context."><?php echo esc_html__('Concise and Direct', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <textarea rows="5" id="aipower_custom_prompt_desc" name="wpaicg_woo_custom_prompt_description"><?php echo esc_textarea(wp_unslash($wpaicg_woo_custom_prompt_description)); ?></textarea>
                    <?php echo get_shortcode_text(); ?>
                </div>
            </div>

            <!-- Meta Description Prompt -->
            <div class="aipower-collapsible-section">
                <button class="aipower-collapsible-toggle"><label for="aipower_custom_prompt_meta" class="aipower-woocommerce-labels"><?php echo esc_html__('Meta Description Prompt', 'gpt3-ai-content-generator'); ?></label></button>
                <div class="aipower-collapsible-content">
                    <div class="aipower-dropdown-container">
                        <select id="aipower_woocommerce_meta_dropdown" name="aipower_woocommerce_meta_dropdown">
                            <option value=""><?php echo esc_html__('-- Select a Template --', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Craft a meta description for the product: %s that succinctly highlights its key features and benefits. Aim to stay within 155 characters. Use Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Focused on Key Features and Benefits', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Compose a compelling 155-character meta description for the product: %s that illustrates how it solves a specific problem for the customer. Reference these details: Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description], Product Categories: [current_categories]."><?php echo esc_html__('Problem-Solving Angle', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Write a meta description for the product: %s that emotionally engages the potential customer, inspiring them to click and learn more. Limit to 155 characters. Use Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description] and Product Categories: [current_categories] for added depth."><?php echo esc_html__('Emotional Appeal', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Create an SEO-optimized meta description for the product: %s that is rich in keywords, yet readable and engaging. Keep it under 155 characters. Refer to these details: Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description], Product Categories: [current_categories]."><?php echo esc_html__('Urgency and Exclusivity', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Devise a straightforward, 155-character meta description for the product: %s that provides just the facts, appealing to a no-nonsense customer base. Use Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Direct and Informative', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <textarea rows="5" id="aipower_custom_prompt_meta" name="wpaicg_woo_custom_prompt_meta"><?php echo esc_textarea(wp_unslash($wpaicg_woo_custom_prompt_meta)); ?></textarea>
                    <?php echo get_shortcode_text(); ?>
                </div>
            </div>

            <!-- Tags Prompt -->
            <div class="aipower-collapsible-section">
                <button class="aipower-collapsible-toggle"><label for="aipower_custom_prompt_tags" class="aipower-woocommerce-labels"><?php echo esc_html__('Tags Prompt', 'gpt3-ai-content-generator'); ?></label></button>
                <div class="aipower-collapsible-content">
                    <div class="aipower-dropdown-container">
                        <select id="aipower_woocommerce_tags_dropdown" name="aipower_woocommerce_tags_dropdown">
                            <option value=""><?php echo esc_html__('-- Select a Template --', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Generate a set of highly relevant and SEO-optimized tags for the product: %s. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Highly Relevant and SEO-Optimized', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Create a list of tags for the product: %s that will increase its discoverability. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Increase Discoverability', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Craft a set of tags for the product: %s that describe its features and benefits. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Describe Features and Benefits', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Compile a group of keywords as tags for the product: %s that encompass its functionality and use-cases. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('Encompass Functionality and Use-Cases', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Develop an SEO-optimized list of tags for the product: %s, focusing on high-search-volume keywords. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context."><?php echo esc_html__('SEO-Optimized High-Search-Volume Keywords', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <textarea rows="5" id="aipower_custom_prompt_tags" name="wpaicg_woo_custom_prompt_keywords"><?php echo esc_textarea(wp_unslash($wpaicg_woo_custom_prompt_keywords)); ?></textarea>
                    <?php echo get_shortcode_text(); ?>
                </div>
            </div>
            <!-- Focus Keyword Prompt-->
            <div class="aipower-collapsible-section">
                <button class="aipower-collapsible-toggle">
                    <?php echo esc_html__('Focus Keyword Prompt', 'gpt3-ai-content-generator'); ?>
                    <?php if (!\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" target="_blank" style="margin-left: 5px;"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
                    <?php endif; ?>
                </button>
                <div class="aipower-collapsible-content">
                    <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                    <div class="aipower-dropdown-container">
                        <select id="aipower_woocommerce_focus_keyword_dropdown" name="aipower_woocommerce_focus_keyword_dropdown">
                            <option value=""><?php echo esc_html__('-- Select a Template --', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Identify the primary keyword for the following product: %s. Please respond in English. No additional comments, just the keyword."><?php echo esc_html__('Generate Single Keyword', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Generate SEO-optimized and high-volume focus keywords in English for the following product: %s. Keywords should be the main terms you aim to rank for. Avoid using symbols like -, #, etc. Results must be comma-separated."><?php echo esc_html__('Generate Multiple Keywords', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Generate niche-specific and unique focus keywords in English for the product: %s. Keywords should closely align with the product unique features or niche. Avoid using symbols like -, #, etc. Results must be comma-separated."><?php echo esc_html__('Niche-Specific and Unique', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Generate trending or seasonally relevant focus keywords in English for the following product: %s. Ensure they directly relate to the product and its features. Avoid using symbols like -, #, etc. Results must be comma-separated."><?php echo esc_html__('Trending or Seasonal', 'gpt3-ai-content-generator'); ?></option>
                            <option value="Generate focus keywords in English for the product: %s. Keywords should fill a gap or seize an opportunity that competitors might have missed but are still highly relevant to the product. Avoid using symbols like -, #, etc. Results must be comma-separated."><?php echo esc_html__('Competitor Analysis', 'gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <textarea rows="5" id="aipower_custom_prompt_focus_keyword" name="wpaicg_woo_custom_prompt_focus_keyword"><?php echo esc_textarea(wp_unslash($wpaicg_woo_custom_prompt_focus_keyword)); ?></textarea>
                    <?php echo get_shortcode_text(); ?>
                    <?php else: ?>
                        <p><?php echo esc_html__('This feature is available in the Pro plan.', 'gpt3-ai-content-generator'); ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>