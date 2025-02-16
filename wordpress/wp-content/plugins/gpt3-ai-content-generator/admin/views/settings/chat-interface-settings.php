<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
?>

<div class="aipower-form-group aipower-grouped-fields-bot">
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-sidebar"><?php echo esc_html__('Conversations', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-sidebar" name="aipower-sidebar">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-fullscreen"><?php echo esc_html__('Fullscreen', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-fullscreen" name="aipower-fullscreen">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-download"><?php echo esc_html__('Download', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-download" name="aipower-download">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-clear"><?php echo esc_html__('Clear', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-clear" name="aipower-clear">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-copy"><?php echo esc_html__('Copy', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-copy" name="aipower-copy">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-close-button"><?php echo esc_html__('Close', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-close-button" name="aipower-close-button">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
</div>

<div class="aipower-form-group aipower-grouped-fields-bot">
    <div class="aipower-form-group">
        <label for="aipower-welcome-message"><?php echo esc_html__('Welcome Message', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-welcome-message" name="aipower-welcome-message"/>
    </div>
    <div class="aipower-form-group">
        <label for="aipower-new-chat"><?php echo esc_html__('New Chat', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-new-chat" name="aipower-new-chat"/>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <div class="aipower-form-group">
        <label for="aipower-response-wait-message"><?php echo esc_html__('Response Wait Message', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-response-wait-message" name="aipower-response-wait-message"/>
    </div>
    <div class="aipower-form-group">
        <label for="aipower-placeholder-message"><?php echo esc_html__('Placeholder', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-placeholder-message" name="aipower-placeholder-message"/>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <div class="aipower-form-group">
        <label for="aipower-footer-note"><?php echo esc_html__('Footer Note', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-footer-note" name="aipower-footer-note"/>
    </div>
</div>