<?php if (!defined('ABSPATH')) exit; // Exit if accessed directly. 

$wpaicg_pinecone_indexes = get_option('wpaicg_pinecone_indexes','');
$wpaicg_pinecone_indexes = empty($wpaicg_pinecone_indexes) ? array() : json_decode($wpaicg_pinecone_indexes,true);
$wpaicg_qdrant_collections = get_option('wpaicg_qdrant_collections',[]);
$wpaicg_qdrant_collections = empty($wpaicg_qdrant_collections) ? array() : $wpaicg_qdrant_collections;
$embedding_models = \WPAICG\WPAICG_Util::get_instance()->get_embedding_models();
$embedding_model = '';

// Google Voice settings
$wpaicg_google_voices = get_option('wpaicg_google_voices', []); // Get the voices, default to empty array
$human_readable_languages = \WPAICG\WPAICG_Google_Speech::get_instance()->languages;
$languages = [];

// Check if the option is in the correct format (i.e., it's an array)
if (is_array($wpaicg_google_voices) && !empty($wpaicg_google_voices)) {
    // Prepare the voices array grouped alphabetically
    foreach ($wpaicg_google_voices as $language_code => $voices) {
        if (is_array($voices) && !empty($voices)) {
            $languages[$language_code] = $voices;
        }
    }

    ksort($languages); // Sort languages alphabetically
}

$default_language = 'en-US'; // Default language
$default_voice = 'en-US-Wavenet-A'; // Default voice

$wpaicg_voice_device = '';
$devices = \WPAICG\WPAICG_Google_Speech::get_instance()->devices();
$wpaicg_voice_speed = 1;
$wpaicg_voice_pitch = 0;

$wpaicg_roles = wp_roles()->get_names(); // Get all roles
?>

<!-- Main container for chatbot and preview -->
<div class="aipower-chat-preview-container">
    <!-- Chatbot table section -->
    <div class="aipower-chat-section">

        <!-- Container for tools icon and menu -->
        <div class="aipower-tools-wrapper">
            <button id="aipower-add-new-bot-btn" class="button button-primary">
                <?php echo esc_html__('New Bot', 'gpt3-ai-content-generator'); ?>
            </button>
            <button id="aipower-done-editing-btn" class="button button-primary" style="display: none;">
                <?php echo esc_html__('Close', 'gpt3-ai-content-generator'); ?>
            </button>

            <!-- Tools icon and menu section -->
            <div class="aipower-tools-container">
                <!-- Inline confirmation text -->
                <div id="aipower-confirmation" class="aipower-confirmation" style="display:none;">
                    <span><?php echo esc_html__('Sure?', 'gpt3-ai-content-generator'); ?></span>
                    <span id="aipower-confirm-yes" class="aipower-confirm-yes"><?php echo esc_html__('Yes', 'gpt3-ai-content-generator'); ?></span>
                </div>
                <!-- Tool icon for actions -->
                <span id="aipower-tools-icon" class="dashicons dashicons-admin-tools" title="<?php echo esc_attr__('Tools to delete, export and import chatbots', 'gpt3-ai-content-generator'); ?>"></span>

                <!-- Hidden menu for actions -->
                <div id="aipower-tools-menu" class="aipower-tools-menu">
                    <div id="aipower-delete-all-btn" class="aipower-tools-action aipower-delete-all"><?php echo esc_html__('Delete All Bots', 'gpt3-ai-content-generator'); ?></div>
                    <div id="aipower-export-all-btn" class="aipower-tools-action"><?php echo esc_html__('Export All Bots', 'gpt3-ai-content-generator'); ?></div>
                    <div id="aipower-import-btn" class="aipower-tools-action"><?php echo esc_html__('Import', 'gpt3-ai-content-generator'); ?></div>
                    <div id="aipower-reset-btn" class="aipower-tools-action"><?php echo esc_html__('Reset', 'gpt3-ai-content-generator'); ?></div>
                </div>
                <!-- Hidden File Input for Importing Chatbots -->
                <input type="file" id="aipower-import-file-input" accept=".json" style="display:none;" />
            </div>
        </div>

        <!-- Hidden nonce field for AJAX security -->
        <input type="hidden" id="ai-engine-nonce" value="<?php echo wp_create_nonce('wpaicg_save_ai_engine_nonce'); ?>" />
        <input type="hidden" id="embedding-nonce" value="<?php echo wp_create_nonce('wpaicg-ajax-nonce'); ?>" />

        <div id="aipower-create-bot-section" style="display:none;">
            <div class="aipower-accordion">
                <!-- General Settings -->
                <button class="aipower-accordion-btn active"><?php echo esc_html__('General Settings', 'gpt3-ai-content-generator'); ?></button>
                <div class="aipower-accordion-panel">
                    <?php include 'chat-general-settings.php'; ?>
                </div>

                <!-- Style Settings -->
                <button class="aipower-accordion-btn"><?php echo esc_html__('Style', 'gpt3-ai-content-generator'); ?></button>
                <div class="aipower-accordion-panel">
                    <?php include 'chat-style-settings.php'; ?>
                </div>
                <!-- Interface Settings -->
                <button class="aipower-accordion-btn"><?php echo esc_html__('Interface', 'gpt3-ai-content-generator'); ?></button>
                <div class="aipower-accordion-panel">
                    <?php include 'chat-interface-settings.php'; ?>
                </div>
                <!-- Token Management -->
                <button class="aipower-accordion-btn"><?php echo esc_html__('Token Management', 'gpt3-ai-content-generator'); ?></button>
                <div class="aipower-accordion-panel">
                    <?php include 'chat-token-settings.php'; ?>
                </div>
            </div>
        </div>
        <!-- Render the chatbot table including the default bot -->
        <?php
        // Determine the current page from a query parameter or default to 1
        $current_page = isset($_GET['aipower_page']) ? intval($_GET['aipower_page']) : 1;
        echo wp_kses_post(WPAICG\WPAICG_Dashboard::aipower_render_chatbot_table($current_page));
        ?>
    </div>

    <!-- Preview section -->
    <div class="aipower-preview-section">
        <div id="aipower-chatbox-container"></div> <!-- Container for displaying the chatbox -->
    </div>
</div>

<!-- Export Single Modal -->
<div id="aipower-export-modal" class="aipower-modal" style="display:none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Export Chatbot', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Are you sure you want to export this chatbot?', 'gpt3-ai-content-generator'); ?></p>
            <button id="aipower-confirm-export-btn" class="button button-primary"><?php echo esc_html__('Yes, Export', 'gpt3-ai-content-generator'); ?></button>
            <button id="aipower-cancel-export-btn" class="button"><?php echo esc_html__('Cancel', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
<!-- Delete Modal -->
<div id="aipower-delete-modal" class="aipower-modal" style="display:none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Delete Chatbot', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Are you sure you want to delete this chatbot?', 'gpt3-ai-content-generator'); ?></p>
            <button id="aipower-confirm-delete-btn" class="button button-primary"><?php echo esc_html__('Yes, Delete', 'gpt3-ai-content-generator'); ?></button>
            <button id="aipower-cancel-delete-btn" class="button"><?php echo esc_html__('Cancel', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
<!-- Duplicate Modal -->
<div id="aipower-duplicate-modal" class="aipower-modal" style="display:none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Duplicate Chatbot', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Are you sure you want to duplicate this chatbot?', 'gpt3-ai-content-generator'); ?></p>
            <button id="aipower-confirm-duplicate-btn" class="button button-primary"><?php echo esc_html__('Yes, Duplicate', 'gpt3-ai-content-generator'); ?></button>
            <button id="aipower-cancel-duplicate-btn" class="button"><?php echo esc_html__('Cancel', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
<!-- Modal Advance AI Parameters for Chatbot -->
<div id="bot-advanced-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content" style="width: 24%;">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Advanced Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Max Tokens Field -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-max-tokens"><?php echo esc_html__('Maximum Tokens', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-max-tokens" placeholder="<?php echo esc_attr__('Enter max tokens...', 'gpt3-ai-content-generator'); ?>" value="1500" />
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Temperature Field -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-temperature"><?php echo esc_html__('Temperature', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-bot-temperature" step="0.01" min="0" max="2" value="0" oninput="this.nextElementSibling.value = this.value">
                    <output>0</output>
                </div>
                <!-- FP Field -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-fp"><?php echo esc_html__('Frequency Penalty', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-bot-fp" step="0.01" min="0" max="2" oninput="this.nextElementSibling.value = this.value">
                    <output>0</output>
                </div>
                <!-- FP Field -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-pp"><?php echo esc_html__('Presence Penalty', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-bot-pp" step="0.01" min="0" max="2" oninput="this.nextElementSibling.value = this.value">
                    <output>0</output>
                </div>
                <!-- Top P Field -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-tp"><?php echo esc_html__('Top P', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-bot-tp" step="0.01" min="0" max="1" oninput="this.nextElementSibling.value = this.value">
                    <output>0</output>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Memory Parameters for Chatbot -->
<div id="bot-memory-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content" style="width: 24%;">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Memory Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('How many previous messages should the AI remember?', 'gpt3-ai-content-generator'); ?></p>
            <div class="aipower-form-group">
                <label for="aipower-memory-limit"><?php echo esc_html__('Memory Limit', 'gpt3-ai-content-generator'); ?></label>
                <input type="range" id="aipower-memory-limit" data-output="memory-limit-output" step="1" min="3" max="500" value="" oninput="this.nextElementSibling.value = this.value">
                <output id="memory-limit-output">100</output>
            </div>
        </div>
    </div>
</div>
<!-- Modal Content Awareness for Chatbot -->
<div id="bot-content-aware-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Knowledge Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('You can add knowledge to the chatbot by enabling below settings.', 'gpt3-ai-content-generator'); ?></p>
            <h3><?php echo esc_html__('User Awareness', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- User Aware Switch -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-user-aware"><?php echo esc_html__('User Aware', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-user-aware" name="aipower-user-aware">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <h3><?php echo esc_html__('Content Awareness', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Data Source Section -->
                <div class="aipower-form-group">
                    <label for="aipower-data-source-selection"><?php echo esc_html__('Data Source', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipower-radio-group">
                        <label for="aipower-bot-type-excerpt">
                            <input type="radio" id="aipower-bot-type-excerpt" name="embedding" value="0" checked />
                            <?php echo esc_html__('Post Excerpt', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <label for="aipower-bot-type-embedding">
                            <input type="radio" id="aipower-bot-type-embedding" name="embedding" value="1" />
                            <?php echo esc_html__('Embeddings', 'gpt3-ai-content-generator'); ?>
                        </label>
                    </div>
                </div>
                <!-- Vector DB Selection -->
                <div class="aipower-form-group">
                    <label for="aipower-vectordb-selection"><?php echo esc_html__('Vector DB', 'gpt3-ai-content-generator'); ?></label>
                    <div class="aipower-radio-group">
                        <label for="aipower-bot-type-pinecone">
                            <input type="radio" id="aipower-bot-type-pinecone" name="vectordb" value="pinecone" checked />
                            <?php echo esc_html__('Pinecone', 'gpt3-ai-content-generator'); ?>
                        </label>
                        <label for="aipower-bot-type-qdrant">
                            <input type="radio" id="aipower-bot-type-qdrant" name="vectordb" value="qdrant" />
                            <?php echo esc_html__('Qdrant', 'gpt3-ai-content-generator'); ?>
                        </label>
                    </div>
                </div>
                <!-- NEW: "Add my data" button -->
                <div class="aipower-form-group" style="margin-top:20px; display:none;" id="aipower-add-data-button-wrapper">
                    <button id="aipower-add-data-button" class="button button-primary">
                        <?php echo esc_html__('Add Data', 'gpt3-ai-content-generator'); ?>
                    </button>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Pinecone Index Selection -->
                <div class="aipower-form-group">
                    <label for="aipower-pinecone-index-selection"><?php echo esc_html__('Index', 'gpt3-ai-content-generator'); ?></label>
                    <select name="embedding_index" id="aipower-bot-pinecone-index">
                        <option value=""><?php echo esc_html__('Default','gpt3-ai-content-generator')?></option>
                        <?php
                        foreach($wpaicg_pinecone_indexes as $wpaicg_pinecone_index){
                            echo '<option value="'.esc_html($wpaicg_pinecone_index['url']).'">'.esc_html($wpaicg_pinecone_index['name']).'</option>';
                        }
                        ?>
                    </select>
                </div>
                <!-- Qdrant Collection Selection -->
                <div class="aipower-form-group">
                    <label for="aipower-qdrant-collection-selection"><?php echo esc_html__('Collection', 'gpt3-ai-content-generator'); ?></label>
                    <select name="qdrant_collection" id="aipower-bot-qdrant-collection">
                        <?php foreach ($wpaicg_qdrant_collections as $collection)
                            {
                                if (is_array($collection) && isset($collection['name'])) {
                                    // New structure: collection is an array with 'name' and possibly 'dimension'
                                    $name = $collection['name'];
                                    $dimension = isset($collection['dimension']) ? ' (' . $collection['dimension'] . ')' : '';
                                    $display_name = $name . $dimension;
                                } else {
                                    // Old structure: collection is a string
                                    $name = $collection;
                                    $display_name = $collection;
                                }
                                $selected = ($name === '') ? ' selected' : '';
                                echo '<option value="' . esc_attr($name) . '"' . $selected . '>' . esc_html($display_name) . '</option>';
                            } 
                        ?>
                    </select>
                </div>
                <!-- Query Limit Selection -->
                <div class="aipower-form-group">
                    <label for="aipower-query-limit-selection"><?php echo esc_html__('Query Limit', 'gpt3-ai-content-generator'); ?></label>
                    <select name="embedding_top" id="aipower-bot-embedding-top">
                        <?php
                            for($i = 1; $i <=5;$i++){
                                echo '<option value="'.esc_html($i).'">'.esc_html($i).'</option>';
                            }
                        ?>
                    </select>
                </div>
                <!-- Embedding Type -->
                <div class="aipower-form-group">
                    <label for="aipower-embedding-type-selection"><?php echo esc_html__('Bot Behaviour', 'gpt3-ai-content-generator'); ?></label>
                    <select name="embedding_type" id="aipower-bot-embedding-type">
                        <option value="openai"><?php echo esc_html__('Conversational','gpt3-ai-content-generator')?></option>
                        <option value=""><?php echo esc_html__('Non-Conversational','gpt3-ai-content-generator')?></option>
                    </select>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Confidence Score -->
                <div class="aipower-form-group">
                    <label for="aipower-confidence-score"><?php echo esc_html__('Confidence Score', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-confidence-score" data-output="confidence-score-output" step="1" min="1" max="100" value="" oninput="this.nextElementSibling.value = this.value">
                    <output id="confidence-score-output">20</output>
                </div>
                <!-- Use Default Emnbedding -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-use-default-embedding"><?php echo esc_html__('Use Default Embedding', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-use-default-embedding" name="aipower-use-default-embedding">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
                <!-- Custom Embedding Model Selection -->
                <div class="aipower-form-group">
                    <label for="aipower-embedding-model-selection"><?php echo esc_html__('Embedding Model', 'gpt3-ai-content-generator'); ?></label>
                    <select name="embedding_model" id="aipower-bot-embedding-model">
                        <?php
                            foreach ($embedding_models as $provider => $models) {
                                echo '<optgroup label="' . esc_attr($provider) . '">';
                                foreach ($models as $model => $dimension) {
                                    $selected = ($model === $embedding_model) ? 'selected' : '';
                                    echo '<option value="' . esc_attr($model) . '" data-provider="' . esc_attr($provider) . '" ' . $selected . '>' . esc_html($model) . ' (' . esc_html($dimension) . ')</option>';
                                }
                                echo '</optgroup>';
                            }
                        ?>
                    </select>
                </div>
            </div>
            <h3><?php echo esc_html__('Other Settings', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <label for="aipower-bot-language-selection"><?php echo esc_html__('Language', 'gpt3-ai-content-generator'); ?></label>
                    <?php $language_options = \WPAICG\WPAICG_Util::get_instance()->chat_language_options; ?>
                    <select name="aipower-bot-language" id="aipower-bot-language">
                        <option value=""><?php echo esc_html__('None','gpt3-ai-content-generator')?></option>
                        <?php foreach($language_options as $key => $value){?>
                            <option value="<?php echo esc_html($key)?>"><?php echo esc_html($value)?></option>
                        <?php }?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower-bot-tone-selection"><?php echo esc_html__('Tone', 'gpt3-ai-content-generator'); ?></label>
                    <?php $tone_options = \WPAICG\WPAICG_Util::get_instance()->chat_tone_options; ?>
                    <select name="aipower-bot-tone" id="aipower-bot-tone">
                        <?php foreach($tone_options as $key => $value){?>
                            <option value="<?php echo esc_html($key)?>"><?php echo esc_html($value)?></option>
                        <?php }?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower-bot-profession-selection"><?php echo esc_html__('Profession', 'gpt3-ai-content-generator'); ?></label>
                    <?php $proffesion_options = \WPAICG\WPAICG_Util::get_instance()->chat_profession_options; ?>
                    <select name="aipower-bot-proffesion" id="aipower-bot-proffesion">
                        <?php foreach($proffesion_options as $key => $value){?>
                            <option value="<?php echo esc_html($key)?>"><?php echo esc_html($value)?></option>
                        <?php }?>
                    </select>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- NEW: Modal to display all posts for embedding  -->
<div id="aipower-add-data-modal" class="aipower-modal" style="display:none;">
    <div class="aipower-modal-content" style="width: 70%;max-height:80vh;overflow:auto;">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Add Data to Vector DB', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Select the content you want to add to your Vector DB. Then click "Index Selected".', 'gpt3-ai-content-generator'); ?></p>
            <!-- The "Index Selected" button and "Stop Indexing" button -->
            <div style="margin-top:20px;">
                <button id="aipower-index-selected-btn" class="button button-primary">
                    <?php echo esc_html__('Index Selected', 'gpt3-ai-content-generator'); ?>
                </button>
                
                <!-- NEW: Initially hidden stop button -->
                <button id="aipower-stop-indexing-btn" class="button button-secondary" style="display:none;">
                    <?php echo esc_html__('Stop', 'gpt3-ai-content-generator'); ?>
                </button>
            </div>

            <!-- Container for progress updates -->
            <div id="aipower-index-progress" style="margin-top: 20px;"></div>
            <!-- Container for tabs or just a big list. We'll do tabs by post type. -->
            <div id="aipower-add-data-tabs" class="aipower-add-data-tabs">
                <!-- Will be populated by JS with a tab for each post type -->
            </div>

        </div>
    </div>
</div>
<!-- Modal Feedback for Chatbot -->
<div id="bot-feedback-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Feedback Collection Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Let users provide feedback on the AI responses. When enabled, a little thumbs up/down icon will appear after each response.', 'gpt3-ai-content-generator'); ?></p>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Feedback Title -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-feedback-title"><?php echo esc_html__('Feedback Title', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-feedback-title" value="Feedback" />
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Feedback Message -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-feedback-message"><?php echo esc_html__('Feedback Message', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-feedback-message" value="Please provide details: (optional)" />
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Feedback Confirmation Message -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-feedback-confirmation"><?php echo esc_html__('Feedback Confirmation Message', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-feedback-confirmation" value="Thank you for your feedback!" />
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal PDF Upload for Chatbot -->
<div id="bot-pdf-upload-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('PDF Upload Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('Enabling this allows users to upload PDF files to the chatbot.', 'gpt3-ai-content-generator'); ?></p>
            <!-- PDF Upload Confirmation Message. -->
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <label for="aipower-bot-pdf-upload-confirmation"><?php echo esc_html__('PDF Upload Confirmation Message', 'gpt3-ai-content-generator'); ?></label>
                    <textarea id="aipower-bot-pdf-upload-confirmation" rows="4" cols="50" name="aipower-bot-pdf-upload-confirmation"><?php echo esc_html__('Congrats! Your PDF is uploaded now! You can ask questions about your document. Example Questions:[questions]', 'gpt3-ai-content-generator'); ?></textarea>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- PDF Page Limit -->
                <div class="aipower-form-group">
                    <label for="aipower-pdf-page-limit-selection"><?php echo esc_html__('PDF Page Limit', 'gpt3-ai-content-generator'); ?></label>
                    <select name="pdf_pages" id="aipower-bot-pdf-page-limit">
                        <?php
                            $pdf_pages = 120;
                            for($i=1;$i <= 120;$i++){
                                echo '<option'.($pdf_pages == $i ? ' selected':'').' value="'.esc_html($i).'">'.esc_html($i).'</option>';
                            }
                        ?>
                    </select>
                </div>
                <!-- PDF Icon Color -->
                <div class="aipower-form-group">
                    <label for="aipower-pdf-icon-color"><?php echo esc_html__('PDF Icon Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" id="aipower-pdf-icon-color" name="pdf_color"/>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal: Conversation Starters for Chatbot -->
<div id="bot-conversation-starters-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Conversation Starters', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('These are initial messages that the chatbot can use to initiate conversations.', 'gpt3-ai-content-generator'); ?></p>
            <p><?php echo esc_html__('Enter up to 10 conversation starters, one per line.', 'gpt3-ai-content-generator'); ?></p>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Conversation Starters -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-conversation-starters"><?php echo esc_html__('Conversation Starters', 'gpt3-ai-content-generator'); ?></label>
                    <textarea id="aipower-bot-conversation-starters" rows="4" cols="50" name="conversation_starters"></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Log Settings for Chatbot -->
<div id="bot-logs-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Log & Security Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('You can enable or disable logging and set the moderation for the chatbot.', 'gpt3-ai-content-generator'); ?></p>
            <h3><?php echo esc_html__('Log Settings', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Save Prompt Details -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-save-prompt-details"><?php echo esc_html__('Save Prompt Details', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-save-prompt-details" name="aipower-save-prompt-details">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
                <!-- Display Notification -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-log-notification"><?php echo esc_html__('Display Notification', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-log-notification" name="aipower-log-notification">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Notification Message -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-log-notification-message"><?php echo esc_html__('Notification Message', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-log-notification-message" value="Please note that your conversations will be recorded." />
                </div>
            </div>
            <h3><?php echo esc_html__('Moderation Settings', 'gpt3-ai-content-generator'); ?></h3>
            <p><?php echo esc_html__('You can turn moderation on or off for the chatbot. When it is on, the chatbot will not respond to messages until they are approved by the OpenAI moderation model. This feature only works with OpenAI.', 'gpt3-ai-content-generator'); ?></p>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Enable Moderation -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-enable-moderation"><?php echo esc_html__('Enable Moderation', 'gpt3-ai-content-generator'); ?></label>
                        <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
                            <label class="aipower-switch">
                                <input type="checkbox" id="aipower-enable-moderation" name="aipower-enable-moderation">
                                <span class="aipower-slider"></span>
                            </label>
                        <?php else: ?>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="aipower-pro-feature-label"><?php echo esc_html__('Pro', 'gpt3-ai-content-generator'); ?></a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower-moderation_model"><?php echo esc_html__('Moderation Model', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-moderation_model" id="aipower-moderation_model">
                        <option value="text-moderation-latest"><?php echo esc_html__('text-moderation-latest','gpt3-ai-content-generator')?></option>
                        <option value="text-moderation-stable"><?php echo esc_html__('text-moderation-stable','gpt3-ai-content-generator')?></option>
                        <option value="omni-moderation-latest"><?php echo esc_html__('omni-moderation-latest','gpt3-ai-content-generator')?></option>
                    </select>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <label for="aipower-bot-moderation-notice"><?php echo esc_html__('Notification Message', 'gpt3-ai-content-generator'); ?></label>
                    <textarea id="aipower-bot-moderation-notice" rows="4" cols="50" name="aipower-bot-moderation-notice"><?php echo esc_html__('Your message has been flagged as potentially harmful or inappropriate. Please ensure that your messages are respectful and do not contain language or content that could be offensive or harmful to others. Thank you for your cooperation.', 'gpt3-ai-content-generator'); ?></textarea>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- Modal Speech Settings for Chatbot -->
<div id="bot-speech-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Speech Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('You can turn voice options on or off for the chatbot. Voice features do not work with Streaming, so make sure to turn off Streaming.', 'gpt3-ai-content-generator'); ?></p>
            <h3><?php echo esc_html__('Speech to Text', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Speech to Text Switch -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-speech-to-text"><?php echo esc_html__('Speech to Text', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-speech-to-text" name="aipower-speech-to-text">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
                <!-- Mic Icon Play Color -->
                <div class="aipower-form-group">
                    <label for="aipower-mic-icon-color"><?php echo esc_html__('Microphone Play Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" id="aipower-mic-icon-color" name="aipower-mic-icon-color"/>
                </div>
                <!-- Mic Icon Stop Color -->
                <div class="aipower-form-group">
                    <label for="aipower-mic-icon-stop-color"><?php echo esc_html__('Microphone Stop Color', 'gpt3-ai-content-generator'); ?></label>
                    <input type="color" id="aipower-mic-icon-stop-color" name="aipower-mic-icon-stop-color"/>
                </div>
            </div>
            <h3><?php echo esc_html__('Text to Speech', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Text to Speech Switch -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-text-to-speech"><?php echo esc_html__('Text to Speech', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-text-to-speech" name="aipower-text-to-speech">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
                <!-- Allow Users to Turn it On or Off -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-text-to-speech-allow-user"><?php echo esc_html__('Allow users to disable', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-text-to-speech-allow-user" name="aipower-text-to-speech-allow-user">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
                <!-- Voice is Muted by Default -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-text-to-speech-muted"><?php echo esc_html__('Autoplay', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-text-to-speech-muted" name="aipower-text-to-speech-muted">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Voice Provider -->
                <div class="aipower-form-group">
                    <label for="aipower-voice-provider"><?php echo esc_html__('Voice Provider', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-voice-provider" id="aipower-voice-provider">
                        <option value="openai"><?php echo esc_html__('OpenAI','gpt3-ai-content-generator')?></option>
                        <option value="elevenlabs"><?php echo esc_html__('ElevenLabs','gpt3-ai-content-generator')?></option>
                        <option value="google"><?php echo esc_html__('Google','gpt3-ai-content-generator')?></option>
                    </select>
                </div>
                <!-- OpenAI Voice Model -->
                <div class="aipower-form-group">
                    <label for="aipower-openai-voice-model"><?php echo esc_html__('Voice Model', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-openai-voice-model" id="aipower-openai-voice-model">
                        <option value="tts-1"><?php echo esc_html__('tts-1 (Fastest)','gpt3-ai-content-generator')?></option>
                        <option  value="tts-1-hd"><?php echo esc_html__('tts-1-hd (Highest Quality)','gpt3-ai-content-generator')?></option>
                    </select>
                </div>
                <!-- OpenAI Voice -->
                <div class="aipower-form-group">
                    <label for="aipower-openai-voice"><?php echo esc_html__('Voice', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-openai-voice" id="aipower-openai-voice">
                        <option value="alloy"><?php echo esc_html__('Alloy','gpt3-ai-content-generator')?></option>
                        <option value="echo"><?php echo esc_html__('Echo','gpt3-ai-content-generator')?></option>
                        <option value="fable"><?php echo esc_html__('Fable','gpt3-ai-content-generator')?></option>
                        <option value="onyx"><?php echo esc_html__('Onyx','gpt3-ai-content-generator')?></option>
                        <option value="nova"><?php echo esc_html__('Nova','gpt3-ai-content-generator')?></option>
                        <option value="shimmer"><?php echo esc_html__('Shimmer','gpt3-ai-content-generator')?></option>
                    </select>
                </div>
                <!-- OpenAI Output Format -->
                <div class="aipower-form-group">
                    <label for="aipower-openai-format"><?php echo esc_html__('Output Format', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-openai-format" id="aipower-openai-format">
                        <option value="mp3"><?php echo esc_html__('MP3','gpt3-ai-content-generator')?></option>
                        <option value="opus"><?php echo esc_html__('OPUS','gpt3-ai-content-generator')?></option>
                        <option value="aac"><?php echo esc_html__('AAC','gpt3-ai-content-generator')?></option>
                        <option value="flac"><?php echo esc_html__('FLAC','gpt3-ai-content-generator')?></option>
                        <option value="wav"><?php echo esc_html__('WAV','gpt3-ai-content-generator')?></option>
                        <option value="pcm"><?php echo esc_html__('PCM','gpt3-ai-content-generator')?></option>
                    </select>
                </div>
                <!-- OpenAI Voice Speed -->
                <div class="aipower-form-group">
                    <label for="aipower-openai-voice-speed"><?php echo esc_html__('Voice Speed', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-openai-voice-speed" step="0.01" min="0.25" max="4" value="1" oninput="this.nextElementSibling.value = this.value" style="width: 89%;">
                    <output>1</output>
                </div>
                <!-- ElevenLabs Voice Model -->
                <div class="aipower-form-group">
                    <label for="aipower-elevenlabs-voice-model"><?php echo esc_html__('Voice Model', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-elevenlabs-voice-model" id="aipower-elevenlabs-voice-model">
                        <?php
                            $wpaicg_elevenlabs_model = '';
                            $models = \WPAICG\WPAICG_ElevenLabs::get_instance()->models;

                            foreach ($models as $key => $model) {
                                $selected = ($wpaicg_elevenlabs_model === $key) ? ' selected' : '';
                                echo sprintf('<option value="%s"%s>%s</option>', esc_html($key), $selected, esc_html($model));
                            }
                        ?>
                    </select>
                </div>
                <!-- ElevenLabs Voice -->
                <div class="aipower-form-group">
                    <label for="aipower-elevenlabs-voice"><?php echo esc_html__('Voice', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-elevenlabs-voice" id="aipower-elevenlabs-voice">
                        <?php
                        $wpaicg_elevenlabs_voice = '';
                        $voices = \WPAICG\WPAICG_ElevenLabs::get_instance()->voices;

                        foreach ($voices as $key => $voice) {
                            $selected = ($wpaicg_elevenlabs_voice === $key) ? ' selected' : '';
                            echo sprintf('<option value="%s"%s>%s</option>', esc_html($key), $selected, esc_html($voice));
                        }
                        ?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower-google-language"><?php echo esc_html__('Language & Voice', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-google-language" id="aipower-google-language">
                        <?php
                        if (!empty($languages)) {
                            foreach ($languages as $language_code => $voices) {
                                // Get human-readable language name, default to the language code if not available
                                $language_name = isset($human_readable_languages[$language_code]) ? $human_readable_languages[$language_code] : $language_code;
                                
                                echo sprintf('<optgroup label="%s">', esc_html($language_name)); // Group by human-readable language name
                                
                                foreach ($voices as $voice) {
                                    if (isset($voice['name'], $voice['ssmlGender'])) {
                                        $value = $language_code . '|' . $voice['name']; // Value includes both language code and voice name
                                        // Set the default selected option for en-US / en-US-Wavenet-A
                                        $selected = ($language_code === $default_language && $voice['name'] === $default_voice) ? ' selected' : '';
                                        echo sprintf(
                                            '<option value="%s"%s>%s (%s)</option>',
                                            esc_html($value),
                                            $selected,
                                            esc_html($voice['name']),
                                            esc_html($voice['ssmlGender']) // Option includes voice name and gender
                                        );
                                    }
                                }

                                echo '</optgroup>'; // Close the optgroup
                            }
                        } else {
                            // Fallback if no valid languages or voices are found
                            echo '<option disabled>' . esc_html__('No voices available', 'gpt3-ai-content-generator') . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="aipower-form-group">
                    <label for="aipower-google-device"><?php echo esc_html__('Device', 'gpt3-ai-content-generator'); ?></label>
                    <select name="aipower-google-device" id="aipower-google-device">
                        <?php foreach ($devices as $key => $device) : ?>
                            <option value="<?php echo esc_html($key); ?>" <?php selected($wpaicg_voice_device, $key); ?>>
                                <?php echo esc_html($device); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <!-- Google Voice Speed -->
                <div class="aipower-form-group">
                    <label for="aipower-google-voice-speed"><?php echo esc_html__('Voice Speed', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-google-voice-speed" step="0.01" min="0.25" max="4" value="1" oninput="this.nextElementSibling.value = this.value">
                    <output>1</output>
                </div>
                <!-- Google Voice Speed -->
                <div class="aipower-form-group">
                    <label for="aipower-google-voice-pitch"><?php echo esc_html__('Voice Pitch', 'gpt3-ai-content-generator'); ?></label>
                    <input type="range" id="aipower-google-voice-pitch" step="1" min="-20" max="20" value="0" oninput="this.nextElementSibling.value = this.value">
                    <output>0</output>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Role Based Limit for Chatbot -->
<div id="bot-role-limits-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content" style="width: 24%;">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Role Based Token Limit', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('You can set the token limit for each user role. Empty and 0 means unlimited.', 'gpt3-ai-content-generator'); ?></p>
            <?php 
            $wpaicg_roles = wp_roles()->get_names(); // Get all roles
            foreach ($wpaicg_roles as $role_key => $role_name): ?>
                <div class="aipower-form-group">
                    <label for="role-limit-<?php echo esc_attr($role_key); ?>"><?php echo esc_html($role_name); ?></label>
                    <input type="number" min="0" name="role_limit[<?php echo esc_attr($role_key); ?>]" id="role-limit-<?php echo esc_attr($role_key); ?>" class="role-limit-input" data-role="<?php echo esc_attr($role_key); ?>" />
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<!-- Modal Lead Collection Settings for Chatbot -->
<div id="bot-leads-settings-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Lead Collection Settings', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <p><?php echo esc_html__('When enabled, the collection form will appear after the first AI response.', 'gpt3-ai-content-generator'); ?></p>
            <p><?php echo esc_html__('The form will only be shown once, unless the user clicks the "Clear" button to reset the chat.', 'gpt3-ai-content-generator'); ?></p>
            <p><?php echo esc_html__('You can view the collected leads in the "Logs" tab.', 'gpt3-ai-content-generator'); ?></p>
            <h3><?php echo esc_html__('Configuration', 'gpt3-ai-content-generator'); ?></h3>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Lead Title Customization -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-lead-title"><?php echo esc_html__('Title', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-lead-title" value="Let us know how to contact you" />
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Lead Name Customization -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-lead-name"><?php echo esc_html__('Name', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-lead-name" value="Name" />
                </div>
                <!-- Enable Lead Name -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-enable-lead-name"><?php echo esc_html__('Name', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-enable-lead-name" name="aipower-enable-lead-name">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Lead Email Customization -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-lead-email"><?php echo esc_html__('Email', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-lead-email" value="Email" />
                </div>
                <!-- Enable Lead Email -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-enable-lead-email"><?php echo esc_html__('Email', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-enable-lead-email" name="aipower-enable-lead-email">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Lead Phone Customization -->
                <div class="aipower-form-group">
                    <label for="aipower-bot-lead-phone"><?php echo esc_html__('Phone', 'gpt3-ai-content-generator'); ?></label>
                    <input type="text" id="aipower-bot-lead-phone" value="Phone" />
                </div>
                <!-- Enable Lead Phone -->
                <div class="aipower-form-group">
                    <div class="aipower-switch-container">
                        <label class="aipower-switch-label" for="aipower-enable-lead-phone"><?php echo esc_html__('Phone', 'gpt3-ai-content-generator'); ?></label>
                        <label class="aipower-switch">
                            <input type="checkbox" id="aipower-enable-lead-phone" name="aipower-enable-lead-phone">
                            <span class="aipower-slider"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>