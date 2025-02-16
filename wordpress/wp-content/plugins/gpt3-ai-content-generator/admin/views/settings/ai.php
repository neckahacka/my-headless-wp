<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

// Function to mask the API key, showing only the last 4 characters
function mask_api_key($api_key) {
    if (strlen($api_key) > 4) {
        return str_repeat('*', strlen($api_key) - 4) . substr($api_key, -4);
    }
    return $api_key; // If API key is shorter than 4 characters, return as is
}

// Parameters for the AI settings
$current_temperature = isset($settings_row['temperature']) ? $settings_row['temperature'] : 1;
$current_max_tokens = isset($settings_row['max_tokens']) ? $settings_row['max_tokens'] : 1500;
$current_openai_api_key = isset($settings_row['api_key']) ? $settings_row['api_key'] : 'sk..';
$current_top_p = isset($settings_row['top_p']) ? $settings_row['top_p'] : 0.01;
$current_frequency = isset($settings_row['frequency_penalty']) ? $settings_row['frequency_penalty'] : 0;
$current_presence = isset($settings_row['presence_penalty']) ? $settings_row['presence_penalty'] : 0;
$wpaicg_sleep_time = get_option('wpaicg_sleep_time',1);

$current_openai_api_key = isset($settings_row['api_key']) ? $settings_row['api_key'] : '';

// Retrieve the OpenRouter, Google, and Azure API keys from the options table
$current_openrouter_api_key = get_option('wpaicg_openrouter_api_key', '');
$current_google_api_key = get_option('wpaicg_google_model_api_key', '');
$current_azure_api_key = get_option('wpaicg_azure_api_key', '');

// Mask the API keys for display
$masked_openai_api_key = mask_api_key($current_openai_api_key);
$masked_openrouter_api_key = mask_api_key($current_openrouter_api_key);
$masked_google_api_key = mask_api_key($current_google_api_key);
$masked_azure_api_key = mask_api_key($current_azure_api_key);

// Retrieve the selected OpenAI model or default to 'gpt-4o-mini'
$selected_model = get_option('wpaicg_ai_model', 'gpt-4o-mini');

// Retrieve available models from different sources
$gpt4_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt4_models;
$gpt35_models = \WPAICG\WPAICG_Util::get_instance()->openai_gpt35_models;
$custom_models = get_option('wpaicg_custom_models', []);

$safety_categories = [
    'HARM_CATEGORY_HARASSMENT' => 'Harassment',
    'HARM_CATEGORY_HATE_SPEECH' => 'Hate Speech',
    'HARM_CATEGORY_SEXUALLY_EXPLICIT' => 'Sexually Explicit',
    'HARM_CATEGORY_DANGEROUS_CONTENT' => 'Dangerous',
];
$thresholds = [
    'BLOCK_NONE' => 'Block None',
    'BLOCK_ONLY_HIGH' => 'Block Few',
    'BLOCK_MEDIUM_AND_ABOVE' => 'Block Some',
    'BLOCK_LOW_AND_ABOVE' => 'Block Most',
];
$google_safety_settings = get_option('wpaicg_google_safety_settings', [
    ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
    ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
    ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
    ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
    ['category' => 'HARM_CATEGORY_CIVIC_INTEGRITY', 'threshold' => 'BLOCK_NONE']
]);

$google_safety_settings = array_column($google_safety_settings, 'threshold', 'category');
?>

<div id="ai-settings" class="aipower-tab-pane active">
    <div id="content-settings" class="aipower-content-wrapper">
        <div class="aipower-category-container ai-settings-container">
            <h3><?php echo esc_html__('AI Settings', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group">
                <label for="aipower-ai-engine-dropdown" class="aipower-input-label">
                    <?php echo esc_html__('Engine', 'gpt3-ai-content-generator'); ?>
                </label>
                <select id="aipower-ai-engine-dropdown" style="width: 250px;">
                    <?php
                    $selected_engine = get_option('wpaicg_provider', 'OpenAI'); // Default to OpenAI if not set
                    $engines = array(
                        'OpenAI' => esc_html__('OpenAI', 'gpt3-ai-content-generator'),
                        'OpenRouter' => esc_html__('OpenRouter', 'gpt3-ai-content-generator'),
                        'Google' => esc_html__('Google', 'gpt3-ai-content-generator'),
                        'Azure' => esc_html__('Microsoft', 'gpt3-ai-content-generator')
                    );
                    foreach ($engines as $key => $name) {
                        $selected = ($key === $selected_engine) ? 'selected' : '';
                        echo "<option value='$key' $selected>$name</option>";
                    }
                    ?>
                </select>
                <!-- Advanced Settings Icon -->
                <span id="aipower-advanced-settings-icon" class="aipower-settings-icon" style="margin-top:-10px;" title="<?php echo esc_attr__('Advanced Settings', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-admin-generic"></span>
                </span>

                <!-- Safety Settings Icon (visible only for Google) -->
                <span id="aipower-safety-settings-icon" class="aipower-settings-icon" style="margin-top:-10px;" title="<?php echo esc_attr__('Safety Settings', 'gpt3-ai-content-generator'); ?>" style="display: none;">
                    <span class="dashicons dashicons-shield"></span>
                </span>
                <!-- Info Icon Link -->
                <a href="https://docs.aipower.org/docs/category/ai-engines" target="_blank" class="aipower-info-icon" title="<?php echo esc_attr__('AI Engine Documentation', 'gpt3-ai-content-generator'); ?>">
                    <span class="dashicons dashicons-info"></span>
                </a>
            </div>
            <!-- Provider Specific Containers -->
            <div id="aipower-openai-container" class="aipower-provider-container" style="display: none;">
                <div class="aipower-form-group">
                    <label for="OpenAI-api-key"><?php echo esc_html__('API Key', 'gpt3-ai-content-generator'); ?></label>
                </div>
                <div class="aipower-form-group">
                    <input style="width: 250px;" type="text" id="OpenAI-api-key" value="<?php echo esc_attr($masked_openai_api_key); ?>">
                    <a href="https://platform.openai.com/api-keys" target="_blank"><?php echo esc_html__('Get API Key','gpt3-ai-content-generator')?></a>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower-openai-model-dropdown">
                        <?php echo esc_html__('Model', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="aipower-openai-model-dropdown" style="width: 250px;">
                        <optgroup label="<?php echo esc_html__('GPT-3.5 Models', 'gpt3-ai-content-generator'); ?>">
                            <?php foreach ($gpt35_models as $model_key => $model_label) : ?>
                                <option value="<?php echo esc_attr($model_key); ?>" <?php selected($model_key, $selected_model); ?>>
                                    <?php echo esc_html($model_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <optgroup label="<?php echo esc_html__('GPT-4 Models', 'gpt3-ai-content-generator'); ?>">
                            <?php foreach ($gpt4_models as $model_key => $model_label) : ?>
                                <option value="<?php echo esc_attr($model_key); ?>" <?php selected($model_key, $selected_model); ?>>
                                    <?php echo esc_html($model_label); ?>
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                        <?php if (!empty($custom_models)) : ?>
                            <optgroup label="<?php echo esc_html__('Custom Models', 'gpt3-ai-content-generator'); ?>">
                                <?php foreach ($custom_models as $model) : ?>
                                    <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $selected_model); ?>>
                                        <?php echo esc_html($model); ?>
                                    </option>
                                <?php endforeach; ?>
                            </optgroup>
                        <?php endif; ?>
                    </select>
                    <div id="syncOpenAI" data-target="#aipower-openai-model-dropdown" class="aipower-settings-icon aipower_sync_openai_models" style="margin-top:-10px;" title="<?php echo esc_attr__('Syncs the latest models from OpenAI', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </div>
                    <!-- Info Icon Link -->
                    <a href="https://docs.aipower.org/docs/ai-engine/openai/gpt-models" target="_blank" class="aipower-info-icon" title="<?php echo esc_attr__('OpenAI Documentation', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-info"></span>
                    </a>
                </div>
            </div>

            <div id="aipower-openrouter-container" class="aipower-provider-container" style="display: none;">
                <div class="aipower-form-group">
                    <label for="OpenRouter-api-key">
                        <?php echo esc_html__('API Key', 'gpt3-ai-content-generator'); ?>
                    </label>
                </div>
                <div class="aipower-form-group">
                    <input style="width: 250px;" type="text" id="OpenRouter-api-key" value="<?php echo esc_attr($masked_openrouter_api_key); ?>">
                    <a href="https://openrouter.ai/keys" target="_blank"><?php echo esc_html__('Get API Key','gpt3-ai-content-generator')?></a>
                </div>

                <div class="aipower-form-group">
                    <label for="aipower-openrouter-model-dropdown">
                        <?php echo esc_html__('Model', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="aipower-openrouter-model-dropdown" style="width: 250px;">
                        <?php
                        // Retrieve the OpenRouter models from the option
                        $openrouter_models = get_option('wpaicg_openrouter_model_list', []);
                        
                        // Group the models by provider
                        $grouped_models = [];
                        foreach ($openrouter_models as $model) {
                            $provider = explode('/', $model['id'])[0]; // Extract the provider name from the ID
                            if (!isset($grouped_models[$provider])) {
                                $grouped_models[$provider] = [];
                            }
                            $grouped_models[$provider][] = $model;
                        }
                        
                        // Sort the providers alphabetically
                        ksort($grouped_models);
                        
                        // Display models grouped by provider
                        foreach ($grouped_models as $provider => $models) {
                            echo '<optgroup label="' . esc_attr($provider) . '">';
                            
                            // Sort the models alphabetically by name within each provider
                            usort($models, function($a, $b) {
                                return strcmp($a['name'], $b['name']);
                            });
                            
                            // Display the models as options in the dropdown
                            foreach ($models as $model) {
                                echo '<option value="' . esc_attr($model['id']) . '" ' . selected($model['id'], get_option('wpaicg_openrouter_default_model'), false) . '>' . esc_html($model['name']) . '</option>';
                            }
                            echo '</optgroup>';
                        }
                        ?>
                    </select>
                    
                    <span id="syncOpenRouter" data-target="#aipower-openrouter-model-dropdown" class="aipower-settings-icon aipower_sync_openrouter_models" style="margin-top:-10px;" title="<?php echo esc_attr__('Syncs the latest models from OpenRouter', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </span>
                    <!-- Info Icon Link -->
                    <a href="https://docs.aipower.org/docs/ai-engine/openrouter" target="_blank" class="aipower-info-icon" title="<?php echo esc_attr__('OpenRouter Documentation', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-info"></span>
                    </a>
                </div>
            </div>

            <div id="aipower-google-container" class="aipower-provider-container" style="display: none;">
                <div class="aipower-form-group">
                    <label for="Google-api-key"><?php echo esc_html__('API Key', 'gpt3-ai-content-generator'); ?></label>
                </div>
                <div class="aipower-form-group">
                    <input style="width: 250px;" type="text" id="Google-api-key" value="<?php echo esc_attr($masked_google_api_key); ?>">
                    <a href="https://aistudio.google.com/app/apikey" target="_blank"><?php echo esc_html__('Get API Key','gpt3-ai-content-generator')?></a>
                </div>

                <div class="aipower-form-group">
                    <label for="aipower-google-model-dropdown">
                        <?php echo esc_html__('Model', 'gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="aipower-google-model-dropdown" style="width: 250px;">
                        <?php
                        // Retrieve Google models from a list
                        $wpaicg_google_model_list = get_option('wpaicg_google_model_list', []); // Replace with actual retrieval logic
                        $wpaicg_google_default_model = get_option('wpaicg_google_default_model', 'gemini-pro'); // Default model

                        if (!empty($wpaicg_google_model_list) && is_array($wpaicg_google_model_list)) :
                            foreach ($wpaicg_google_model_list as $model) :
                                // Define words that disable the option
                                $disabledWords = ['vision']; // Add specific words that disable the option
                                $shouldBeDisabled = false;

                                foreach ($disabledWords as $word) {
                                    if (strpos($model, $word) !== false) {
                                        $shouldBeDisabled = true;
                                        break; // Disable the option if any word is found
                                    }
                                }
                        ?>
                            <option value="<?php echo esc_attr($model); ?>" <?php selected($model, $wpaicg_google_default_model); ?> <?php echo $shouldBeDisabled ? 'disabled' : ''; ?>>
                                <?php echo esc_html(ucwords(str_replace('-', ' ', $model))); ?>
                            </option>
                        <?php endforeach; else : ?>
                            <option value="gemini-pro" <?php selected('gemini-pro', $wpaicg_google_default_model); ?>><?php echo esc_html__('Gemini Pro', 'gpt3-ai-content-generator'); ?></option>
                        <?php endif; ?>
                    </select>
                    <span id="syncGoogle" data-target="#aipower-google-model-dropdown"  class="aipower-settings-icon aipower_sync_google_models" style="margin-top:-10px;" title="<?php echo esc_attr__('Syncs the latest models from Google', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-update"></span>
                    </span>
                    <!-- Info Icon Link -->
                    <a href="https://docs.aipower.org/docs/ai-engine/google" target="_blank" class="aipower-info-icon" title="<?php echo esc_attr__('Google Documentation', 'gpt3-ai-content-generator'); ?>">
                        <span class="dashicons dashicons-info"></span>
                    </a>
                </div>
            </div>

            <div id="aipower-azure-container" class="aipower-provider-container" style="display: none;">
                <!-- Azure API Key -->
                <div class="aipower-form-group">
                    <label for="Azure-api-key"><?php echo esc_html__('API Key', 'gpt3-ai-content-generator'); ?></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input style="width: 250px;" type="text" id="Azure-api-key" value="<?php echo esc_attr($masked_azure_api_key); ?>" data-full-api-key="<?php echo esc_attr($current_azure_api_key); ?>">
                        <a href="https://azure.microsoft.com/en-us/products/ai-services/openai-service" target="_blank"><?php echo esc_html__('Get API Key', 'gpt3-ai-content-generator'); ?></a>
                    </div>
                </div>

                <!-- Azure Endpoint -->
                <div class="aipower-form-group">
                    <label for="Azure-endpoint"><?php echo esc_html__('Endpoint', 'gpt3-ai-content-generator'); ?></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input style="width: 250px;" type="text" id="Azure-endpoint" value="<?php echo esc_attr(get_option('wpaicg_azure_endpoint', '')); ?>">
                    </div>
                </div>

                <!-- Azure Deployment -->
                <div class="aipower-form-group">
                    <label for="Azure-deployment"><?php echo esc_html__('Deployment', 'gpt3-ai-content-generator'); ?></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input style="width: 250px;" type="text" id="Azure-deployment" value="<?php echo esc_attr(get_option('wpaicg_azure_deployment', '')); ?>">
                    </div>
                </div>

                <!-- Azure Embeddings -->
                <div class="aipower-form-group">
                    <label for="Azure-embeddings"><?php echo esc_html__('Embeddings', 'gpt3-ai-content-generator'); ?></label>
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <input style="width: 250px;" type="text" id="Azure-embeddings" value="<?php echo esc_attr(get_option('wpaicg_azure_embeddings', '')); ?>">
                    </div>
                </div>
            </div>
        </div>
        <!-- Module Settings Container -->
        <div class="aipower-category-container module-settings-container">
            <h3><?php echo esc_html__('Module Settings', 'gpt3-ai-content-generator'); ?></h3>
            <p><?php echo esc_html__('To avoid cluttering your WP, disable the modules that you do not need.', 'gpt3-ai-content-generator'); ?></p>
            <?php foreach ($available_modules as $module_key => $module_data): ?>
                <div class="aipower-form-group">
                    <label for="module-<?php echo esc_attr($module_key); ?>">
                        <input type="checkbox" id="module-<?php echo esc_attr($module_key); ?>" name="modules[<?php echo esc_attr($module_key); ?>]" <?php checked($module_settings[$module_key], true); ?>>
                        <?php echo esc_html($module_data['title']); ?>
                    </label>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- Advanced Settings Modal -->
<div class="aipower-modal" id="aipower_advanced_settings_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Advanced Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <h3><?php echo esc_html__('AI Parameters', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- MAX TOKENS -->
                <div class="aipower-form-group">
                    <label for="aipower-max-tokens"><?php echo esc_html__('Maximum Tokens', 'gpt3-ai-content-generator'); ?></label>
                    <input 
                        id="aipower-max-tokens" 
                        name="wpaicg_max_tokens" 
                        type="range" 
                        min="1" 
                        max="16383" 
                        value="<?php echo esc_attr($current_max_tokens); ?>" 
                        oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo esc_attr($current_max_tokens); ?></output>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- TEMPERATURE -->
                <div class="aipower-form-group">
                    <label for="aipower-temperature"><?php echo esc_html__('Temperature', 'gpt3-ai-content-generator'); ?></label>
                    <input id="aipower-temperature" name="wpaicg_temperature" type="range" step="0.01" min="0" max="2" value="<?php echo esc_attr($current_temperature); ?>" oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo esc_attr($current_temperature); ?></output>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- FREQUENCY PENALTY -->
                <div class="aipower-form-group">
                    <label for="aipower-frequency-penalty"><?php echo esc_html__('Frequency Penalty', 'gpt3-ai-content-generator'); ?></label>
                    <input id="aipower-frequency-penalty" name="wpaicg_frequency" type="range" step="0.01" min="0" max="2" value="<?php echo esc_attr($current_frequency); ?>" oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo esc_attr($current_frequency); ?></output>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- PRESENCE PENALTY -->
                <div class="aipower-form-group">
                    <label for="aipower-presence-penalty"><?php echo esc_html__('Presence Penalty', 'gpt3-ai-content-generator'); ?></label>
                    <input id="aipower-presence-penalty" name="wpaicg_presence" type="range" step="0.01" min="0" max="2" value="<?php echo esc_attr($current_presence); ?>" oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo esc_attr($current_presence); ?></output>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- TOP_P -->
                <div class="aipower-form-group">
                    <label for="aipower-top-p"><?php echo esc_html__('Top P', 'gpt3-ai-content-generator'); ?></label>
                    <input id="aipower-top-p" name="wpaicg_top_p" type="range" step="0.01" min="0" max="1" value="<?php echo esc_attr($current_top_p); ?>" oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo esc_attr($current_top_p); ?></output>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- RATE LIMIT -->
                <div class="aipower-form-group">
                    <label for="aipower-rate-limit"><?php echo esc_html__('Buffer (in seconds)', 'gpt3-ai-content-generator'); ?></label>
                    <input id="aipower-rate-limit" name="wpaicg_sleep_time" type="range" min="1" max="30" value="<?php echo esc_attr($wpaicg_sleep_time); ?>" oninput="this.nextElementSibling.value = this.value">
                    <output><?php echo esc_attr($wpaicg_sleep_time); ?></output>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Safety Settings Modal -->
<div class="aipower-modal" id="aipower_safety_settings_modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Safety Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Adjust how likely you are to see responses that could be harmful. Content is blocked based on the probability that it is harmful.', 'gpt3-ai-content-generator'); ?></p>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Harassment -->
                <div class="aipower-form-group">
                    <label for="HARM_CATEGORY_HARASSMENT"><?php echo esc_html__('Harassment', 'gpt3-ai-content-generator'); ?></label>
                    <select id="HARM_CATEGORY_HARASSMENT" name="google_safety_settings[HARM_CATEGORY_HARASSMENT]">
                        <option value="BLOCK_NONE" <?php selected($google_safety_settings['HARM_CATEGORY_HARASSMENT'] ?? 'BLOCK_NONE', 'BLOCK_NONE'); ?>>
                            <?php echo esc_html__('Block None', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_ONLY_HIGH" <?php selected($google_safety_settings['HARM_CATEGORY_HARASSMENT'] ?? 'BLOCK_NONE', 'BLOCK_ONLY_HIGH'); ?>>
                            <?php echo esc_html__('Block Few', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_MEDIUM_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_HARASSMENT'] ?? 'BLOCK_NONE', 'BLOCK_MEDIUM_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Some', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_LOW_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_HARASSMENT'] ?? 'BLOCK_NONE', 'BLOCK_LOW_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Most', 'gpt3-ai-content-generator'); ?>
                        </option>
                    </select>
                </div>

                <!-- Hate Speech -->
                <div class="aipower-form-group">
                    <label for="HARM_CATEGORY_HATE_SPEECH"><?php echo esc_html__('Hate Speech', 'gpt3-ai-content-generator'); ?></label>
                    <select id="HARM_CATEGORY_HATE_SPEECH" name="google_safety_settings[HARM_CATEGORY_HATE_SPEECH]">
                        <option value="BLOCK_NONE" <?php selected($google_safety_settings['HARM_CATEGORY_HATE_SPEECH'] ?? 'BLOCK_NONE', 'BLOCK_NONE'); ?>>
                            <?php echo esc_html__('Block None', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_ONLY_HIGH" <?php selected($google_safety_settings['HARM_CATEGORY_HATE_SPEECH'] ?? 'BLOCK_NONE', 'BLOCK_ONLY_HIGH'); ?>>
                            <?php echo esc_html__('Block Few', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_MEDIUM_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_HATE_SPEECH'] ?? 'BLOCK_NONE', 'BLOCK_MEDIUM_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Some', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_LOW_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_HATE_SPEECH'] ?? 'BLOCK_NONE', 'BLOCK_LOW_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Most', 'gpt3-ai-content-generator'); ?>
                        </option>
                    </select>
                </div>
                <!-- Sexually Explicit -->
                <div class="aipower-form-group">
                    <label for="HARM_CATEGORY_SEXUALLY_EXPLICIT"><?php echo esc_html__('Sexually Explicit', 'gpt3-ai-content-generator'); ?></label>
                    <select id="HARM_CATEGORY_SEXUALLY_EXPLICIT" name="google_safety_settings[HARM_CATEGORY_SEXUALLY_EXPLICIT]">
                        <option value="BLOCK_NONE" <?php selected($google_safety_settings['HARM_CATEGORY_SEXUALLY_EXPLICIT'] ?? 'BLOCK_NONE', 'BLOCK_NONE'); ?>>
                            <?php echo esc_html__('Block None', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_ONLY_HIGH" <?php selected($google_safety_settings['HARM_CATEGORY_SEXUALLY_EXPLICIT'] ?? 'BLOCK_NONE', 'BLOCK_ONLY_HIGH'); ?>>
                            <?php echo esc_html__('Block Few', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_MEDIUM_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_SEXUALLY_EXPLICIT'] ?? 'BLOCK_NONE', 'BLOCK_MEDIUM_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Some', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_LOW_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_SEXUALLY_EXPLICIT'] ?? 'BLOCK_NONE', 'BLOCK_LOW_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Most', 'gpt3-ai-content-generator'); ?>
                        </option>
                    </select>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Dangerous Content -->
                <div class="aipower-form-group">
                    <label for="HARM_CATEGORY_DANGEROUS_CONTENT"><?php echo esc_html__('Dangerous', 'gpt3-ai-content-generator'); ?></label>
                    <select id="HARM_CATEGORY_DANGEROUS_CONTENT" name="google_safety_settings[HARM_CATEGORY_DANGEROUS_CONTENT]">
                        <option value="BLOCK_NONE" <?php selected($google_safety_settings['HARM_CATEGORY_DANGEROUS_CONTENT'] ?? 'BLOCK_NONE', 'BLOCK_NONE'); ?>>
                            <?php echo esc_html__('Block None', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_ONLY_HIGH" <?php selected($google_safety_settings['HARM_CATEGORY_DANGEROUS_CONTENT'] ?? 'BLOCK_NONE', 'BLOCK_ONLY_HIGH'); ?>>
                            <?php echo esc_html__('Block Few', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_MEDIUM_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_DANGEROUS_CONTENT'] ?? 'BLOCK_NONE', 'BLOCK_MEDIUM_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Some', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_LOW_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_DANGEROUS_CONTENT'] ?? 'BLOCK_NONE', 'BLOCK_LOW_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Most', 'gpt3-ai-content-generator'); ?>
                        </option>
                    </select>
                </div>

                <!-- Civic Integrity -->
                <div class="aipower-form-group">
                    <label for="HARM_CATEGORY_CIVIC_INTEGRITY"><?php echo esc_html__('Civic Integrity', 'gpt3-ai-content-generator'); ?></label>
                    <select id="HARM_CATEGORY_CIVIC_INTEGRITY" name="google_safety_settings[HARM_CATEGORY_CIVIC_INTEGRITY]">
                        <option value="BLOCK_NONE" <?php selected($google_safety_settings['HARM_CATEGORY_CIVIC_INTEGRITY'] ?? 'BLOCK_NONE', 'BLOCK_NONE'); ?>>
                            <?php echo esc_html__('Block None', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_ONLY_HIGH" <?php selected($google_safety_settings['HARM_CATEGORY_CIVIC_INTEGRITY'] ?? 'BLOCK_NONE', 'BLOCK_ONLY_HIGH'); ?>>
                            <?php echo esc_html__('Block Few', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_MEDIUM_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_CIVIC_INTEGRITY'] ?? 'BLOCK_NONE', 'BLOCK_MEDIUM_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Some', 'gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="BLOCK_LOW_AND_ABOVE" <?php selected($google_safety_settings['HARM_CATEGORY_CIVIC_INTEGRITY'] ?? 'BLOCK_NONE', 'BLOCK_LOW_AND_ABOVE'); ?>>
                            <?php echo esc_html__('Block Most', 'gpt3-ai-content-generator'); ?>
                        </option>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>