<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

$assistants = get_option('wpaicg_assistants', []);

$assistants_json = json_encode($assistants);

?>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Bot Name Field -->
    <div class="aipower-form-group">
        <label for="aipower-bot-name"><?php echo esc_html__('Bot Name', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-bot-name" placeholder="<?php echo esc_attr__('Enter bot name...', 'gpt3-ai-content-generator'); ?>" />
    </div>

    <!-- Provider Select Field -->
    <div class="aipower-form-group">
        <label for="aipower-bot-provider"><?php echo esc_html__('Provider', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipower-bot-provider" name="aipower-bot-provider">
        <?php
        foreach ($engines as $key => $name) {
            $selected = ($key === $selected_engine) ? 'selected' : '';
            echo "<option value='$key' $selected>$name</option>";
        }
        ?>
        </select>
    </div>
    <!-- Advanced Settings Icon -->
    <span id="aipower-bot-advanced-settings-icon" style="margin-top: 10px;" class="aipower-settings-icon" title="<?php echo esc_attr__('Advanced Settings', 'gpt3-ai-content-generator'); ?>">
        <span class="dashicons dashicons-admin-generic"></span>
    </span>

    <!-- Model Select Field with Sync Icon -->
    <div class="aipower-form-group">
        <label for="aipower-bot-model"><?php echo esc_html__('Model & Assistant', 'gpt3-ai-content-generator'); ?></label>
        <select id="aipower-bot-model" name="aipower-bot-model" data-default="<?php echo esc_attr($selected_model); ?>">
            <!-- Options will be populated dynamically -->
        </select>
    </div>
    <!-- Sync Icon for OpenAI -->
    <div 
        id="aipower_sync_openai_models_bot" 
        class="aipower-bot-settings-icon aipower_sync_openai_models_bot" 
        style="max-width: 20px;margin-top: 10px;"
        data-target="#aipower-bot-model" 
        title="<?php echo esc_attr__('Syncs the latest models from OpenAI', 'gpt3-ai-content-generator'); ?>">
        <span class="dashicons dashicons-update"></span>
    </div>

    <!-- Sync Icon for OpenRouter -->
    <div 
        id="aipower_sync_openrouter_models_bot" 
        class="aipower-bot-settings-icon aipower_sync_openrouter_models_bot" 
        data-target="#aipower-bot-model" 
        style="display:none;max-width: 20px;margin-top: 10px;" 
        title="<?php echo esc_attr__('Syncs the latest models from OpenRouter', 'gpt3-ai-content-generator'); ?>">
        <span class="dashicons dashicons-update"></span>
    </div>

    <!-- Sync Icon for Google -->
    <div 
        id="aipower_sync_google_models_bot" 
        class="aipower-bot-settings-icon aipower_sync_google_models_bot" 
        data-target="#aipower-bot-model" 
        style="display:none;max-width: 20px;margin-top: 10px;" 
        title="<?php echo esc_attr__('Syncs the latest models from Google', 'gpt3-ai-content-generator'); ?>">
        <span class="dashicons dashicons-update"></span>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Enable Instruction Switch with Select Dropdown -->
    <div class="aipower-form-group" style="display: flex;align-items: center;">
        <div class="aipower-form-group" style="display: flex;justify-content: flex-start;gap: 10px;">
            <label for="aipower-chat-addition"><?php echo esc_html__('Instructions', 'gpt3-ai-content-generator'); ?></label>
            <label class="aipower-switch">
                <input type="checkbox" id="aipower-chat-addition" name="aipower-chat-addition">
                <span class="aipower-slider"></span>
            </label>
        </div>
        <div class="aipower-form-group">
            <select id="aipower-instruction-template" name="aipower-instruction-template">
                <option value=""><?php echo esc_html__('Select template', 'gpt3-ai-content-generator'); ?></option>
                <option value="customersupport"><?php echo esc_html__('Customer Support', 'gpt3-ai-content-generator'); ?></option>
                <option value="salesupport"><?php echo esc_html__('Sales Agent', 'gpt3-ai-content-generator'); ?></option>
                <option value="productsupport"><?php echo esc_html__('Product Support', 'gpt3-ai-content-generator'); ?></option>
                <option value="languagetutor"><?php echo esc_html__('Language Tutor', 'gpt3-ai-content-generator'); ?></option>
                <option value="lifecoach"><?php echo esc_html__('Life Coach', 'gpt3-ai-content-generator'); ?></option>
            </select>
        </div>
    </div>  
</div>

<!-- Instructions Textarea -->
<div class="aipower-form-group aipower-grouped-fields-bot">
    <div class="aipower-form-group">
        <textarea id="aipower-chat-addition-text" name="aipower-chat-addition-text" placeholder="<?php echo esc_attr__('Enter instructions...', 'gpt3-ai-content-generator'); ?>"></textarea>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- 1st Switch Group -->
    <div class="aipower-new-switch-container">
        <!-- Content Aware Switch -->
        <div class="aipower-form-group  aipower-content-aware-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-content-aware"><?php echo esc_html__('Knowledge', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-content-aware" name="aipower-content-aware">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Content Aware Settings Icon -->
                    <span id="aipower-bot-content-aware-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Knowledge Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Streaming Switch -->
        <div class="aipower-form-group  aipower-streaming-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-streaming"><?php echo esc_html__('Streaming', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-streaming" name="aipower-streaming">
                        <span class="aipower-slider"></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Memory Switch -->
        <div class="aipower-form-group  aipower-memory-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-memory">
                    <?php echo esc_html__('Memory', 'gpt3-ai-content-generator'); ?>
                </label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-memory" name="aipower-memory">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Memory Settings Icon -->
                    <span id="aipower-bot-memory-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Memory Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>


        <!-- Logs -->
        <div class="aipower-form-group aipower-logs-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-logs"><?php echo esc_html__('Security', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-logs" name="aipower-logs">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Logs Settings Icon -->
                    <span id="aipower-bot-logs-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Logs Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Internet Browsing Switch -->
        <div class="aipower-form-group aipower-internet-browsing-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-internet-browsing"><?php echo esc_html__('Internet', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-internet-browsing" name="aipower-internet-browsing">
                        <span class="aipower-slider"></span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- 2nd Switch Group -->
    <div class="aipower-new-switch-container">
        <!-- PDF Upload Switch -->
        <div class="aipower-form-group  aipower-pdf-upload-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-pdf-upload"><?php echo esc_html__('PDF Upload', 'gpt3-ai-content-generator'); ?></label>
                <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-pdf-upload" name="aipower-pdf-upload">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- PDF Upload Settings Icon -->
                    <span id="aipower-bot-pdf-upload-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('PDF Upload Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
                <?php else: ?>
                    <div class="aipower-switch-icon-group">
                        <label class="aipower-switch aipower-disabled-switch">
                            <input type="checkbox" id="aipower-pdf-upload" name="aipower-pdf-upload" disabled>
                            <span class="aipower-slider"></span>
                        </label>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- Image Upload Switch -->
        <div class="aipower-form-group  aipower-image-upload-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-image-upload"><?php echo esc_html__('Image Upload', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-image-upload" name="aipower-image-upload">
                        <span class="aipower-slider"></span>
                    </label>
                </div>
            </div>
        </div>
        <!-- Feedback -->
        <div class="aipower-form-group  aipower-feedback-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-feedback"><?php echo esc_html__('Feedback', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-feedback" name="aipower-feedback">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Feedback Settings Icon -->
                    <span id="aipower-bot-feedback-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Feedback Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>
        <!-- Conversation Starters -->
        <div class="aipower-form-group  aipower-starters-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-starters"><?php echo esc_html__('Starters', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch" id="aipower-starters-switch">
                        <input type="checkbox" id="aipower-starters" name="aipower-starters">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Conversation Starters Settings Icon -->
                    <span id="aipower-bot-starters-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Conversation Starters Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>

        <!-- Speech -->
        <div class="aipower-form-group aipower-speech-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-speech"><?php echo esc_html__('Speech', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-speech" name="aipower-speech">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Speech Settings Icon -->
                    <span id="aipower-bot-speech-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Speech Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- 3rd Switch Group -->
    <div class="aipower-new-switch-container">
        <!-- Lead Collection Switch -->
        <div class="aipower-form-group  aipower-lead-container">
            <div class="aipower-switch-container">
                <label class="aipower-switch-label" for="aipower-lead-collection"><?php echo esc_html__('Lead Collection', 'gpt3-ai-content-generator'); ?></label>
                <div class="aipower-switch-icon-group">
                    <label class="aipower-switch">
                        <input type="checkbox" id="aipower-lead-collection" name="aipower-lead-collection">
                        <span class="aipower-slider"></span>
                    </label>
                    <!-- Lead Settings Icon -->
                    <span id="aipower-bot-leads-settings-icon" class="aipower-settings-icon" title="<?php echo esc_attr__('Lead Collection Settings', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-admin-generic"></span>
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Bot Type Radio Buttons -->
    <div class="aipower-form-group" style="max-width: 20%;min-width: 100px;">
        <label for="aipower-bot-type"><?php echo esc_html__('Bot Type', 'gpt3-ai-content-generator'); ?></label>
        <div class="aipower-radio-group">
            <label for="aipower-bot-type-shortcode">
                <input type="radio" id="aipower-bot-type-shortcode" name="type" value="shortcode" checked />
                <?php echo esc_html__('Shortcode', 'gpt3-ai-content-generator'); ?>
            </label>
            <label for="aipower-bot-type-widget">
                <input type="radio" id="aipower-bot-type-widget" name="type" value="widget" />
                <?php echo esc_html__('Widget', 'gpt3-ai-content-generator'); ?>
            </label>
        </div>
    </div>
    <!-- Widget Position -->
    <div class="aipower-form-group" style="max-width: 20%;">
        <label for="aipower-widget-position"><?php echo esc_html__('Position', 'gpt3-ai-content-generator'); ?></label>
        <div class="aipower-radio-group">
            <label for="aipower-widget-position-left">
                <input type="radio" id="aipower-widget-position-left" name="position" value="left" checked />
                <?php echo esc_html__('Left', 'gpt3-ai-content-generator'); ?>
            </label>
            <label for="aipower-widget-position-right">
                <input type="radio" id="aipower-widget-position-right" name="position" value="right" />
                <?php echo esc_html__('Right', 'gpt3-ai-content-generator'); ?>
            </label>
        </div>
    </div>
    <!-- Page / Post ID -->
    <div class="aipower-form-group" style="max-width: 20%;min-width: 100px;">
        <label for="aipower-page-post-id"><?php echo esc_html__('Page / Post ID', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-page-post-id" placeholder="<?php echo esc_attr__('Example: 1,2,3', 'gpt3-ai-content-generator'); ?>" />
    </div>
    <!-- Delay Time -->
    <div class="aipower-form-group" style="max-width: 20%;min-width: 100px;">
        <label for="aipower-widget-delay-time"><?php echo esc_html__('Delay', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-widget-delay-time" name="aipower-widget-delay-time" placeholder="<?php echo esc_attr__('Example: 5', 'gpt3-ai-content-generator'); ?>" />
    </div>
</div>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- AI Icon Selection -->
    <div class="aipower-form-group">
        <label for="aipower-widget-icon-selection"><?php echo esc_html__('Widget Icon', 'gpt3-ai-content-generator'); ?></label>
        <div class="aipower-radio-group">
            <label for="aipower-ai-icon-default">
                <input type="radio" id="aipower-ai-icon-default" name="icon" value="default" checked />
                <?php echo esc_html__('Default', 'gpt3-ai-content-generator'); ?>
            </label>
            <label for="aipower-ai-icon-custom">
                <input type="radio" id="aipower-ai-icon-custom" name="icon" value="custom" />
                <?php echo esc_html__('Custom', 'gpt3-ai-content-generator'); ?>
            </label>
        </div>
    </div>
    <!-- Custom AI Icon Upload -->
    <div class="aipower-form-group">
        <label for="aipower-icon-upload"><?php echo esc_html__('Icon', 'gpt3-ai-content-generator'); ?></label>
        <button type="button" class="button" id="aipower-icon-upload-button"><?php echo esc_html__('Upload Icon', 'gpt3-ai-content-generator'); ?></button>
        <input type="hidden" id="aipower-icon-url" name="aipower-icon-url" value="">
        <div id="aipower-icon-preview" style="margin-top:10px;">
            <!-- Thumbnail will be displayed here when editing a custom icon -->
        </div>
    </div>
</div>
<!-- Hidden bot_id field -->
<input type="hidden" id="current-bot-id" value="" />
<!-- Hidden Divs to Store Models -->
<div id="openai-models" data-gpt4-models="<?php echo esc_attr(json_encode($gpt4_models)); ?>" data-gpt35-models="<?php echo esc_attr(json_encode($gpt35_models)); ?>" data-custom-models="<?php echo esc_attr(json_encode($custom_models)); ?>"></div>
<div id="openrouter-models" data-models="<?php echo esc_attr(json_encode(array_map(function($model) {
    return [
        'id' => $model['id'],
        'name' => $model['name']
    ];
}, $openrouter_models))); ?>"></div>

<div id="google-models" data-models="<?php echo esc_attr(json_encode($wpaicg_google_model_list)); ?>"></div>
<div id="default-models" data-openai-default="<?php echo esc_attr(get_option('wpaicg_ai_model', 'gpt-4o-mini')); ?>"
    data-google-default="<?php echo esc_attr(get_option('wpaicg_google_default_model', 'gemini-pro')); ?>"
    data-openrouter-default="<?php echo esc_attr(get_option('wpaicg_openrouter_default_model', 'anthropic/claude-3.5-sonnet')); ?>"
    data-azure-default="<?php echo esc_attr(get_option('wpaicg_azure_deployment', '')); ?>">
</div>
<div id="openai-assistants" data-assistants='<?php echo esc_attr($assistants_json); ?>'></div>