<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
?>

<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Limit Registered Users -->
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-limit-registered-users"><?php echo esc_html__('Limit Registered Users', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-limit-registered-users" name="aipower-limit-registered-users">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <!-- Token Limit -->
    <div class="aipower-form-group">
        <label for="aipower-registered-users-token-limit"><?php echo esc_html__('Token Limit (0 for unlimited)', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-registered-users-token-limit" name="aipower-registered-users-token-limit" value="0"/>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Role Based Limit -->
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-role-based-limit"><?php echo esc_html__('Role Based Limit', 'gpt3-ai-content-generator'); ?></label>
            <div class="aipower-switch-icon-group">
                <label class="aipower-switch">
                    <input type="checkbox" id="aipower-role-based-limit" name="aipower-role-based-limit">
                    <span class="aipower-slider"></span>
                </label>
                <span id="aipower-bot-role-limits-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Role Based Limit', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </span>
            </div>
        </div>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Limit Non-Registered Users -->
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label class="aipower-switch-label" for="aipower-limit-non-registered-users"><?php echo esc_html__('Limit Guests', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-limit-non-registered-users" name="aipower-limit-non-registered-users">
                <span class="aipower-slider"></span>
            </label>
        </div>
    </div>
    <!-- Token Limit for Non-Registered Users-->
    <div class="aipower-form-group">
        <label for="aipower-non-registered-users-token-limit"><?php echo esc_html__('Token Limit (0 for unlimited)', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-non-registered-users-token-limit" name="aipower-non-registered-users-token-limit" value="0"/>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Reset Interval -->
    <div class="aipower-form-group">
        <div class="aipower-switch-container">
            <label for="aipower-reset-interval"><?php echo esc_html__('Reset Interval', 'gpt3-ai-content-generator'); ?></label>
            <select name="aipower-reset-interval" id="aipower-reset-interval">
                <option value="0"><?php echo esc_html__('Never','gpt3-ai-content-generator')?></option>
                <option value="1"><?php echo esc_html__('1 Day','gpt3-ai-content-generator')?></option>
                <option value="3"><?php echo esc_html__('3 Days','gpt3-ai-content-generator')?></option>
                <option value="7"><?php echo esc_html__('1 Week','gpt3-ai-content-generator')?></option>
                <option value="14"><?php echo esc_html__('2 Weeks','gpt3-ai-content-generator')?></option>
                <option value="30"><?php echo esc_html__('1 Month','gpt3-ai-content-generator')?></option>
                <option value="60"><?php echo esc_html__('2 Months','gpt3-ai-content-generator')?></option>
                <option value="90"><?php echo esc_html__('3 Months','gpt3-ai-content-generator')?></option>
                <option value="180"><?php echo esc_html__('6 Months','gpt3-ai-content-generator')?></option>
            </select>
        </div>
    </div>
    <!-- Token Limit for Non-Registered Users-->
    <div class="aipower-form-group">
        <label for="aipower-token-notification"><?php echo esc_html__('Limit Reached Notification', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-token-notification" name="aipower-token-notification"/>
    </div>
</div>

