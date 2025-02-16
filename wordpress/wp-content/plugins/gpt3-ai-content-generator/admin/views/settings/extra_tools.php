<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

$wpaicg_default_comment_prompt = "Please generate a relevant and thoughtful response to [username]'s comment on the post titled '[post_title]' with the excerpt '[post_excerpt]'. The user's latest comment is: '[last_comment]'. If applicable, consider the context of the previous conversation: '[parent_comments]'. Keep the response focused on the topic and avoid creating any new information.";
add_option('wpaicg_comment_prompt', $wpaicg_default_comment_prompt, '', 'no');
$wpaicg_comment_prompt = get_option('wpaicg_comment_prompt', $wpaicg_default_comment_prompt);

// Add the option only if it doesn't already exist
add_option('wpaicg_order_status_token', 'completed', '', 'no');
$wpaicg_order_status_token = get_option('wpaicg_order_status_token', 'completed');

if(!get_option('wpaicg_editor_button_menus',false)){
    add_option('wpaicg_editor_button_menus',false,'','no');
}
$wpaicg_editor_button_menus = get_option('wpaicg_editor_button_menus', []);

if(!get_option('wpaicg_editor_change_action',false)){
    add_option('wpaicg_editor_change_action',false,'','no');
}
$wpaicg_editor_change_action = get_option('wpaicg_editor_change_action', 'below');

if(!is_array($wpaicg_editor_button_menus) || count($wpaicg_editor_button_menus) == 0){
    $wpaicg_editor_button_menus = \WPAICG\WPAICG_Editor::get_instance()->wpaicg_edit_default_menus;
}

// Semantic Search Settings Defaults
$wpaicg_search_placeholder = get_option('wpaicg_search_placeholder', esc_html__('Search anything...', 'gpt3-ai-content-generator'));
add_option('wpaicg_search_font_size', '13', '', 'no');
$wpaicg_search_font_size = get_option('wpaicg_search_font_size', '13');

add_option('wpaicg_search_font_color', '#000000', '', 'no');
$wpaicg_search_font_color = get_option('wpaicg_search_font_color', '#000000');

add_option('wpaicg_search_border_color', '#cccccc', '', 'no');
$wpaicg_search_border_color = get_option('wpaicg_search_border_color', '#cccccc');

add_option('wpaicg_search_bg_color', '#ffffff', '', 'no');
$wpaicg_search_bg_color = get_option('wpaicg_search_bg_color', '#ffffff');

add_option('wpaicg_search_width', '100%', '', 'no');
$wpaicg_search_width = get_option('wpaicg_search_width', '100%');

add_option('wpaicg_search_height', '45px', '', 'no');
$wpaicg_search_height = get_option('wpaicg_search_height', '45px');

add_option('wpaicg_search_no_result', '5', '', 'no');
$wpaicg_search_no_result = get_option('wpaicg_search_no_result', '5');

add_option('wpaicg_search_result_font_size', '13', '', 'no');
$wpaicg_search_result_font_size = get_option('wpaicg_search_result_font_size', '13');

add_option('wpaicg_search_result_font_color', '#000000', '', 'no');
$wpaicg_search_result_font_color = get_option('wpaicg_search_result_font_color', '#000000');

add_option('wpaicg_search_result_bg_color', '#ffffff', '', 'no');
$wpaicg_search_result_bg_color = get_option('wpaicg_search_result_bg_color', '#ffffff');

add_option('wpaicg_search_loading_color', '#cccccc', '', 'no');
$wpaicg_search_loading_color = get_option('wpaicg_search_loading_color', '#cccccc');

?>
<div class="aipower-category-container additional-settings-container">
    <h3><?php echo esc_html__('Additional Tools', 'gpt3-ai-content-generator'); ?></h3>
    <div id="aipower-additional-settings" class="aipower-additional-settings">

        <!-- AI Assistant Settings -->
        <div class="aipower-form-group">
            <label for="aipower-ai-assistant-selection"><?php echo esc_html__('AI Assistant', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipower-settings-icon" id="aipower_ai_assistant_settings_icon" title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>

        <!-- Comment Replier Settings -->
        <div class="aipower-form-group">
            <label for="aipower-comment-replier-selection"><?php echo esc_html__('Comment Replier', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipower-settings-icon" id="aipower_comment_replier_settings_icon" title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>

        <!-- Semantic Search Settings -->
        <div class="aipower-form-group">
            <label for="aipower-semantic-search-selection"><?php echo esc_html__('Semantic Search', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipower-settings-icon" id="aipower_semantic_search_settings_icon" title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>

        <!-- Token Sale Settings -->
        <div class="aipower-form-group">
            <label for="aipower-token-sale-selection"><?php echo esc_html__('Token Sale', 'gpt3-ai-content-generator'); ?></label>
            <button type="button" class="aipower-settings-icon" id="aipower_token_sale_settings_icon" title="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
                <span class="dashicons dashicons-admin-generic"></span>
            </button>
        </div>
    </div>
</div>

<!-- Comment Replier Modal -->
<div class="aipower-modal" id="aipower_comment_replier_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Custom Prompt for Comment Replier', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <p><?php echo esc_html__('You can customize the prompt that is used to generate a response to a comment.', 'gpt3-ai-content-generator'); ?></p>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Comment Prompt Section -->
                <div class="aipower-form-group">
                    <textarea rows="10" type="text" name="wpaicg_comment_prompt" id="aipower_comment_prompt" data-default-prompt="<?php echo esc_attr($wpaicg_default_comment_prompt); ?>"><?php echo esc_html(str_replace("\\",'',$wpaicg_comment_prompt));?></textarea>
                    <p><?php echo sprintf(esc_html__('Ensure %s, %s, %s, %s, and %s are included in your prompt.', 'gpt3-ai-content-generator'), '<code>[username]</code>', '<code>[post_title]</code>', '<code>[post_excerpt]</code>', '<code>[last_comment]</code>', '<code>[parent_comments]</code>');?></p>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Reset Button -->
                <div class="aipower-settings-buttons">
                    <button type="button" class="aipower-button reset-button" id="reset_comment_prompt"><?php echo esc_html__('Reset', 'gpt3-ai-content-generator'); ?></button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Semantic Search Modal -->
<div class="aipower-modal" id="aipower_semantic_search_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Settings for Semantic Search', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo wp_kses(__('Insert this code where you want the search box: <code>[wpaicg_search]</code>', 'gpt3-ai-content-generator'), array('code' => array())); ?></p>
            <!-- Search Settings Section -->
            <h3><?php echo esc_html__('Search Box', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <label for="aipower_search_placeholder"><?php echo esc_html__('Placeholder', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" name="wpaicg_search_placeholder" id="aipower_search_placeholder" value="<?php echo esc_html($wpaicg_search_placeholder); ?>">
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <label for="aipower_search_font_size"><?php echo esc_html__('Font Size', 'gpt3-ai-content-generator'); ?></label>
                    <select name="wpaicg_search_font_size" id="aipower_search_font_size">
                        <?php for($i = 10; $i <= 30; $i++) { ?>
                            <option value="<?php echo esc_html($i); ?>" <?php echo ($wpaicg_search_font_size == $i ? 'selected' : ''); ?>><?php echo esc_html($i); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_font_color"><?php echo esc_html__('Font Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" name="wpaicg_search_font_color" id="aipower_search_font_color" value="<?php echo esc_html($wpaicg_search_font_color); ?>">
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_border_color"><?php echo esc_html__('Border Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" name="wpaicg_search_border_color" id="aipower_search_border_color" value="<?php echo esc_html($wpaicg_search_border_color); ?>">
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_bg_color"><?php echo esc_html__('Background', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" name="wpaicg_search_bg_color" id="aipower_search_bg_color" value="<?php echo esc_html($wpaicg_search_bg_color); ?>">
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_width"><?php echo esc_html__('Width', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" name="wpaicg_search_width" id="aipower_search_width" value="<?php echo esc_html($wpaicg_search_width); ?>">
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_height"><?php echo esc_html__('Height', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" name="wpaicg_search_height" id="aipower_search_height" value="<?php echo esc_html($wpaicg_search_height); ?>">
                </div>
            </div>
            <!-- Search Results Style Header -->
            <h3><?php echo esc_html__('Search Results', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <label for="aipower_search_no_result"><?php echo esc_html__('Nr. of Results', 'gpt3-ai-content-generator'); ?></label>
                    <select name="wpaicg_search_no_result" id="aipower_search_no_result">
                        <?php for($i = 1; $i <= 5; $i++) { ?>
                            <option value="<?php echo esc_html($i); ?>" <?php echo ($wpaicg_search_no_result == $i ? 'selected' : ''); ?>><?php echo esc_html($i); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_result_font_size"><?php echo esc_html__('Font Size', 'gpt3-ai-content-generator'); ?></label>
                    <select name="wpaicg_search_result_font_size" id="aipower_search_result_font_size">
                        <?php for($i = 10; $i <= 30; $i++) { ?>
                            <option value="<?php echo esc_html($i); ?>" <?php echo ($wpaicg_search_result_font_size == $i ? 'selected' : ''); ?>><?php echo esc_html($i); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_result_font_color"><?php echo esc_html__('Font Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" name="wpaicg_search_result_font_color" id="aipower_search_result_font_color" value="<?php echo esc_html($wpaicg_search_result_font_color); ?>">
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_result_bg_color"><?php echo esc_html__('Background', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" name="wpaicg_search_result_bg_color" id="aipower_search_result_bg_color" value="<?php echo esc_html($wpaicg_search_result_bg_color); ?>">
                </div>
                <div class="aipower-form-group">
                    <label for="aipower_search_loading_color"><?php echo esc_html__('Progress Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" name="wpaicg_search_loading_color" id="aipower_search_loading_color" value="<?php echo esc_html($wpaicg_search_loading_color); ?>">
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Token Sale Modal -->
<div class="aipower-modal" id="aipower_token_sale_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Settings for Token Sale', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group">
                <p><?php echo esc_html__("Automatically credit tokens to the user's account based on the order status.", 'gpt3-ai-content-generator'); ?></p>
                <label for="aipower_token_sale_status"><?php echo esc_html__('Order Status', 'gpt3-ai-content-generator'); ?></label>
                <select name="wpaicg_order_status_token" id="aipower_token_sale_status">
                    <option value="completed" <?php selected($wpaicg_order_status_token, 'completed'); ?>>
                        <?php echo esc_html__('Completed', 'gpt3-ai-content-generator'); ?>
                    </option>
                    <option value="processing" <?php selected($wpaicg_order_status_token, 'processing'); ?>>
                        <?php echo esc_html__('Processing', 'gpt3-ai-content-generator'); ?>
                    </option>
                </select>
                <p><?php echo esc_html__("If the order status is 'Completed', the tokens will be credited to the user's account once the order is marked as completed. If the order status is 'Processing', the tokens will be credited to the user's account once the order is marked as processing.", 'gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- AI Assistant Modal -->
<div class="aipower-modal" id="aipower_ai_assistant_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('AI Assistant Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-menu-dropdown">
                <p><?php echo esc_html__('AI Assistant is a feature that allows you to add a button to the WordPress editor that will help you to create content.', 'gpt3-ai-content-generator'); ?></p>
                <p><?php echo esc_html__('It is compatible with both Gutenberg and Classic Editor.', 'gpt3-ai-content-generator'); ?></p>
                <p><?php echo esc_html__('Use the form below to add, modify, or remove menus as needed.', 'gpt3-ai-content-generator'); ?></p>
                <label for="aipower-assistant-menu-select"><?php echo esc_html__('Select a Menu', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-menu-container">
                    <input type="hidden" id="wpaicg-editor-button-menus" value="<?php echo esc_attr(json_encode($wpaicg_editor_button_menus)); ?>">
                    <select id="aipower-assistant-menu-select">
                        <?php foreach ($wpaicg_editor_button_menus as $index => $menu): ?>
                            <option value="<?php echo esc_attr($index); ?>"><?php echo esc_html($menu['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <!-- Add and Delete Icons -->
                    <span id="aipower-add-new-menu" class="dashicons dashicons-plus-alt"></span>
                    <span id="aipower-delete-selected-menu" class="dashicons dashicons-trash"></span>
                    <!-- Sure? and Yes, Delete confirmation -->
                    <span id="aipower-confirm-delete" class="aipower-confirm-delete" style="display:none;">
                        <?php echo esc_html__('Sure?', 'gpt3-ai-content-generator'); ?> 
                        <button id="aipower-confirm-yes-delete" class="aipower-yes-delete-btn">
                            <?php echo esc_html__('Yes', 'gpt3-ai-content-generator'); ?>
                        </button>
                    </span>
                </div>
            </div>
            <!-- Menu Details -->
            <div id="assistant-menu-details">
                <div class="aipower-form-group">
                    <label for="assistant-menu-name"><?php echo esc_html__('Menu Name', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="assistant-menu-name" value="">
                </div>
                <div class="aipower-form-group">
                    <label for="assistant-menu-prompt"><?php echo esc_html__('Menu Prompt', 'gpt3-ai-content-generator'); ?></label>
                    <textarea rows="4" id="assistant-menu-prompt"></textarea>
                    <small><?php echo esc_html__('Ensure to include [text] in your prompt.', 'gpt3-ai-content-generator'); ?></small>
                </div>
            </div>

            <!-- Content Position -->
            <div class="aipower-form-group">
                <label for="wpaicg_editor_change_action"><?php echo esc_html__('Content Position', 'gpt3-ai-content-generator'); ?></label>
                <select name="wpaicg_editor_change_action" id="aipower_editor_change_action">
                    <option value="below" <?php selected($wpaicg_editor_change_action, 'below'); ?>><?php echo esc_html__('Below', 'gpt3-ai-content-generator'); ?></option>
                    <option value="above" <?php selected($wpaicg_editor_change_action, 'above'); ?>><?php echo esc_html__('Above', 'gpt3-ai-content-generator'); ?></option>
                </select>
            </div>
        </div>
    </div>
</div>