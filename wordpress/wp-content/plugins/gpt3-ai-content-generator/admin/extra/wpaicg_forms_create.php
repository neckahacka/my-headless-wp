<?php
declare(strict_types=1);

use WPAICG\WPAICG_Util;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Drag & drop creation container for a brand-new form, with tabs.
 * The AI engine/model selection is on the first tab above the Prompt.
 *
 * Revised per request to put "Response Output, Color, Icon" in one row
 * under the "Interface" section, and replace "Header, Copy Button, Feedback Buttons"
 * checkboxes with switches, each in its own line.
 *
 * Also revised to make the Icon field a dropdown populated from icons.json.
 *
 * Additionally revised to add a "Provider" select above the "Model" select.
 * This provider is stored in meta as "wpaicg_model_provider" and the model
 * is stored as "wpaicg_form_engine".
 */
?>
<div id="wpaicg_create_container" style="display:none;">

    <!-- Tabs navigation -->
    <ul class="wpaicg_create_tabs">
        <!-- 
             The first tab displays an inline-editable form title. 
             Users can double-click the text or click the edit icon to enable editing in place. 
        -->
        <li data-tab="wpaicg_create_tab1" class="active">
            <span
                id="wpaicg_create_tab1_title"
                class="editable-tab-title"
                title="<?php echo esc_attr__('Double-click (or click edit icon) to rename','gpt3-ai-content-generator'); ?>"
            >
                <?php echo esc_html__('Form Name','gpt3-ai-content-generator'); ?>
            </span>
            <span
                class="dashicons dashicons-edit"
                id="wpaicg_create_tab1_edit_icon"
                style="cursor:pointer; margin-left:5px;"
                title="<?php echo esc_attr__('Rename Form','gpt3-ai-content-generator'); ?>"
            ></span>
            <!-- Hidden input to store the full form title -->
            <input type="hidden" id="wpaicg_createform_title" value="" />
        </li>
        <li data-tab="wpaicg_create_tab3"><?php echo esc_html__('Settings','gpt3-ai-content-generator'); ?></li>
    </ul>

    <!-- TAB 1: Form Elements + AI Model + Prompt Settings -->
    <div class="wpaicg_create_tab_content active" id="wpaicg_create_tab1">
        <div class="wpaicg_form_builder">
            <div class="builder_left">
                <h3><?php echo esc_html__('Form Elements','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Drag an element here to add to your new form.','gpt3-ai-content-generator'); ?></p>
                <ul>
                    <li draggable="true" data-type="text"><?php echo esc_html__('Single Line Text','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="textarea"><?php echo esc_html__('Multi-line Text','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="email"><?php echo esc_html__('Email','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="number"><?php echo esc_html__('Number','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="checkbox"><?php echo esc_html__('Checkbox','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="radio"><?php echo esc_html__('Radio','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="select"><?php echo esc_html__('Select','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="url"><?php echo esc_html__('URL','gpt3-ai-content-generator'); ?></li>
                    <!-- NEW: File Upload -->
                    <li draggable="true" data-type="fileupload"><?php echo esc_html__('File Upload','gpt3-ai-content-generator'); ?></li>
                </ul>
            </div>

            <div class="builder_center">
                <h3><?php echo esc_html__('New Form','gpt3-ai-content-generator'); ?></h3>
                <div class="builder_fields_dropzone" id="wpaicg_create_dropzone">
                    <p class="builder_placeholder"><?php echo esc_html__('Drop fields here','gpt3-ai-content-generator'); ?></p>
                </div>
            </div>

            <div class="builder_right">
                <h3><?php echo esc_html__('AI Settings','gpt3-ai-content-generator'); ?></h3>

                <?php
                // Detect a global provider (just to default the select)
                $wpaicg_provider = get_option('wpaicg_provider','OpenAI');
                // Gather model lists:
                $gpt4_models    = WPAICG_Util::get_instance()->openai_gpt4_models;
                $gpt35_models   = WPAICG_Util::get_instance()->openai_gpt35_models;
                $custom_models  = get_option('wpaicg_custom_models', []);
                $google_models  = get_option('wpaicg_google_model_list', ['gemini-pro']);
                $openrouter_raw = get_option('wpaicg_openrouter_model_list', []);
                $azure_deployment = get_option('wpaicg_azure_deployment','');

                // Group for OpenRouter
                $openrouter_grouped = [];
                foreach ($openrouter_raw as $entry) {
                    $prov = explode('/', $entry['id'])[0];
                    if (!isset($openrouter_grouped[$prov])) {
                        $openrouter_grouped[$prov] = [];
                    }
                    $openrouter_grouped[$prov][] = $entry['id'];
                }
                ksort($openrouter_grouped);

                // We'll keep default references
                $default_openai     = 'gpt-4o-mini';
                $default_google     = get_option('wpaicg_google_default_model','gemini-pro');
                $default_openrouter = 'openrouter/auto';
                ?>
                <div style="display:flex; gap:10px;margin-bottom:10px;">
                    <!-- Provider dropdown -->
                    <div>
                        <label for="wpaicg_createform_provider" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Provider','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_createform_provider">
                            <option value="OpenAI" <?php selected($wpaicg_provider, 'OpenAI'); ?>>OpenAI</option>
                            <option value="Google" <?php selected($wpaicg_provider, 'Google'); ?>>Google</option>
                            <option value="OpenRouter" <?php selected($wpaicg_provider, 'OpenRouter'); ?>>OpenRouter</option>
                            <option value="Azure" <?php selected($wpaicg_provider, 'Azure'); ?>>Azure</option>
                        </select>
                    </div>

                    <!-- Model dropdown + Gear Icon -->
                    <div>
                        <label for="wpaicg_createform_engine" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Model','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_createform_engine" style="max-width: 150px;">
                            <!-- Options are populated in JS based on chosen provider -->
                            <option value=""><?php echo esc_html__('Select a model','gpt3-ai-content-generator'); ?></option>
                        </select>
                        <!-- Click to open model settings modal -->
                        <span
                            class="dashicons dashicons-admin-generic"
                            style="cursor: pointer;"
                            id="wpaicg_createform_model_settings_icon"
                            title="<?php echo esc_attr__('Advanced Model Settings','gpt3-ai-content-generator'); ?>">
                        </span>
                        <span
                            class="dashicons dashicons-admin-site" 
                            id="wpaicg_createform_internet_toggle" 
                            style="cursor:pointer; color:#808080;"
                            title="<?php echo esc_attr__('Enable/Disable Internet Browsing','gpt3-ai-content-generator'); ?>"
                        ></span>
                        <!-- Hidden input storing "yes"/"no" for internet browsing -->
                        <input type="hidden" id="wpaicg_createform_internet" value="no" />
                    </div>
                </div>

                <!-- Prompt Settings -->
                <h3><?php echo esc_html__('Prompt Settings','gpt3-ai-content-generator'); ?></h3>
                <label for="wpaicg_createform_prompt"><?php echo esc_html__('Prompt','gpt3-ai-content-generator'); ?></label>
                <textarea
                    id="wpaicg_createform_prompt"
                    rows="5"
                    placeholder="<?php echo esc_attr__('Use {fieldID} placeholders in your prompt','gpt3-ai-content-generator'); ?>"
                    style="width:100%;"></textarea>

                <div class="wpaicg_id_snippets" id="wpaicg_create_snippets"></div>
                <div id="wpaicg_create_copied_msg" class="wpaicg_copied_msg" style="display:none;">
                    <?php echo esc_html__('Copied!','gpt3-ai-content-generator'); ?>
                </div>

                <button class="button" id="wpaicg_create_validate_prompt" style="margin-top:10px;" disabled>
                    <?php echo esc_html__('Validate My Prompt','gpt3-ai-content-generator'); ?>
                </button>
                <div id="wpaicg_create_validation_results" style="margin:8px 0; display:none;">
                    <div>
                        <span id="wpaicg_create_existence_result" style="margin-left:6px;"></span>
                    </div>
                </div>
            </div><!-- builder_right -->
        </div><!-- .wpaicg_form_builder -->
    </div><!-- #wpaicg_create_tab1 -->

    <!-- TAB 3: Interface/Settings -->
    <div class="wpaicg_create_tab_content" id="wpaicg_create_tab3">
        <div class="wpaicg_form_builder">

            <!-- Interface container (left column in the Style tab) -->
            <div class="builder_left">
                <h3><?php echo esc_html__('Interface','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Customize the look and feel of the form.','gpt3-ai-content-generator'); ?></p>

                <!-- Row with 3 equally distributed fields: Response Output, Color, BgColor, Icon -->
                <div style="display: flex; gap: 10px; margin-bottom:10px;">
                    <div style="flex:1;">
                        <label for="wpaicg_createform_editor" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Output','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_createform_editor" style="width:100%;">
                            <option value="div" selected><?php echo esc_html__('Inline','gpt3-ai-content-generator'); ?></option>
                            <option value="editor"><?php echo esc_html__('Text Editor','gpt3-ai-content-generator'); ?></option>
                        </select>
                    </div>
                    <!-- ICON is now a dropdown populated by icons.json keys -->
                    <div style="flex:1;">
                        <label for="wpaicg_createform_icon" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Icon','gpt3-ai-content-generator'); ?>
                        </label>
                        <select
                            id="wpaicg_createform_icon"
                            style="width:100%;"
                        >
                            <option value="">
                                <?php echo esc_html__('tag','gpt3-ai-content-generator'); ?>
                            </option>
                        </select>
                    </div>
                    <div style="flex:1;">
                        <label for="wpaicg_createform_color" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Icon Color','gpt3-ai-content-generator'); ?>
                        </label>
                        <input
                            type="color"
                            id="wpaicg_createform_color"
                            style="width:100%;"
                            value="#00BFFF"
                        />
                    </div>

                    <div style="flex:1;">
                        <label for="wpaicg_createform_bgcolor" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Background','gpt3-ai-content-generator'); ?>
                        </label>
                        <input
                            type="color"
                            id="wpaicg_createform_bgcolor"
                            style="width:100%;"
                            value="#f9f9f9"
                        />
                    </div>
                </div><!-- End 3-field row -->

                <!-- Switch for Header -->
                <div style="display:flex; gap:10px;">
                    <div style="margin-bottom:10px;">
                        <label style="margin-right:10px;">
                            <?php echo esc_html__('Display Header','gpt3-ai-content-generator'); ?>
                        </label>
                        <input type="hidden" id="wpaicg_createform_header" value="yes" />
                        <label class="wpaicg-switch">
                            <input type="checkbox" id="wpaicg_createform_header_switch" checked />
                            <span class="slider"></span>
                        </label>
                    </div>

                    <!-- Switch for Feedback Buttons -->
                    <div style="margin-bottom:10px;">
                        <label style="margin-right:10px;">
                            <?php echo esc_html__('Allow Feedback','gpt3-ai-content-generator'); ?>
                        </label>
                        <input type="hidden" id="wpaicg_createform_feedback_buttons" value="yes" />
                        <label class="wpaicg-switch">
                            <input type="checkbox" id="wpaicg_createform_feedback_buttons_switch" checked />
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div><!-- .builder_left -->

            <div class="builder_center">
                <h3><?php echo esc_html__('Custom Text','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Customize the text for various elements.','gpt3-ai-content-generator'); ?></p>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_createform_copy_text" style="margin-bottom:10px;width: 90%;" placeholder="Copy" />
                    <input type="hidden" id="wpaicg_createform_copy_button" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_createform_copy_button_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>

                <input type="hidden" id="wpaicg_createform_noanswer_text" style="width:100%; margin-bottom:10px;" placeholder="Number of Answers" />

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_createform_draft_text" style="margin-bottom:10px;width: 90%;" placeholder="Save Draft" />
                    <input type="hidden" id="wpaicg_createform_ddraft" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_createform_ddraft_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_createform_clear_text" style="margin-bottom:10px;width: 90%;" placeholder="Clear" />
                    <input type="hidden" id="wpaicg_createform_dclear" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_createform_dclear_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_createform_cnotice_text" style="margin-bottom:10px;width: 90%;" placeholder="Please register to save your result" />
                    <input type="hidden" id="wpaicg_createform_dnotice" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_createform_dnotice_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_createform_download_text" style="margin-bottom:10px;width: 90%;" placeholder="Download" />
                    <input type="hidden" id="wpaicg_createform_ddownload" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_createform_ddownload_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>

                <label for="wpaicg_createform_generate_text"><?php echo esc_html__('Generate Button','gpt3-ai-content-generator'); ?></label>
                <input type="text" id="wpaicg_createform_generate_text" style="width:100%; margin-bottom:10px;" placeholder="Generate" />

                <label for="wpaicg_createform_stop_text"><?php echo esc_html__('Stop Button','gpt3-ai-content-generator'); ?></label>
                <input type="text" id="wpaicg_createform_stop_text" style="width:100%; margin-bottom:10px;" placeholder="Stop" />
            </div>

            <div class="builder_right">
                <h3><?php echo esc_html__('Theme','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Coming Soon!','gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div><!-- #wpaicg_create_tab3 -->

    <div id="wpaicg_create_status" style="margin-top:10px; color:green; display:none;"></div>
</div>

<!-- Model Settings Modal (true overlay) -->
<div id="wpaicg_createform_model_settings_modal" class="wpaicg_model_settings_modal">
    <div class="wpaicg_model_settings_modal_content">
        <span class="wpaicg_modal_close" id="wpaicg_createform_model_settings_close">
            &times;
        </span>
        <h3><?php echo esc_html__('Form Settings','gpt3-ai-content-generator'); ?></h3>

        <hr style="margin:0px 0;" />

        <h4><?php echo esc_html__('AI Parameters','gpt3-ai-content-generator'); ?></h4>
        <!-- Grid container for advanced generation parameters to make the form shorter -->
        <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
            <!-- Max Tokens and Stop Words Section -->
            <div style="display:flex; gap:10px;">
                <label style="flex: 1;">
                    <?php echo esc_html__('Max Tokens','gpt3-ai-content-generator'); ?><br/>
                    <input type="number" id="wpaicg_createform_max_tokens" min="1" max="16384" value="1500" style="width: 100%;" />
                </label>
                <label style="flex: 1;">
                    <?php echo esc_html__('Stop Word(s)','gpt3-ai-content-generator'); ?><br/>
                    <input type="text" id="wpaicg_createform_stop" style="width: 100%;" />
                </label>
            </div>
            
            <!-- Sliders Section -->
            <div style="display: flex; gap: 10px;">
                <label style="flex: 1;">
                    <?php echo esc_html__('Top P','gpt3-ai-content-generator'); ?><br/>
                    <input type="range" id="wpaicg_createform_top_p" min="0" max="1" step="0.01" value="1" style="width: 100%;" />
                    <span id="wpaicg_createform_top_p_value">1</span>
                </label>
                <label style="flex: 1;">
                    <?php echo esc_html__('Frequency Penalty','gpt3-ai-content-generator'); ?><br/>
                    <input type="range" id="wpaicg_createform_frequency_penalty" min="0" max="2" step="0.1" value="0" style="width: 100%;" />
                    <span id="wpaicg_createform_frequency_penalty_value">0</span>
                </label>
                <label style="flex: 1;">
                    <?php echo esc_html__('Presence Penalty','gpt3-ai-content-generator'); ?><br/>
                    <input type="range" id="wpaicg_createform_presence_penalty" min="0" max="2" step="0.1" value="0" style="width: 100%;" />
                    <span id="wpaicg_createform_presence_penalty_value">0</span>
                </label>
                <!-- best_of always 1 and hidden -->
                <input type="hidden" id="wpaicg_createform_best_of" value="1" />
            </div>

        <hr style="margin:0px 0;" />

        <!-- ====== Embeddings Settings (3 lines) ====== -->
        <h4 style="margin-bottom:0px;margin-top:0px;"><?php echo esc_html__('Embeddings','gpt3-ai-content-generator'); ?></h4>

        <!-- Use Embeddings SWITCH -->
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <label style="margin:0;">
                <?php echo esc_html__('Use Embeddings?','gpt3-ai-content-generator'); ?>
            </label>
            <input type="hidden" id="wpaicg_createform_embeddings" value="no" />
            <label class="wpaicg-switch">
                <input type="checkbox" id="wpaicg_createform_embeddings_switch" />
                <span class="slider"></span>
            </label>
        </div>

        <!-- Entire embedding fields container, shown if "use embeddings" = yes -->
        <div id="wpaicg_createform_embeddings_settings_wrapper" style="display:none;">
            <!-- LINE 1: Vector DB + Pinecone Index or Qdrant Collection -->
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <!-- Vector DB selection -->
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Vector DB','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_createform_vectordb" style="width:100%;">
                        <option value="pinecone" selected><?php echo esc_html__('Pinecone','gpt3-ai-content-generator'); ?></option>
                        <option value="qdrant"><?php echo esc_html__('Qdrant','gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>

                <!-- Pinecone Index OR Qdrant Collection (shown conditionally) -->
                <div style="flex:1;">
                    <div id="wpaicg_createform_pineconeindexes_wrap" style="display:none;">
                        <label style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Pinecone Index','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_createform_pineconeindexes" style="width:100%;">
                            <!-- Dynamically populated by JS -->
                        </select>
                    </div>

                    <div id="wpaicg_createform_collections_wrap" style="display:none;">
                        <label style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Qdrant Collection','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_createform_collections" style="width:100%;">
                            <!-- Dynamically populated by JS -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- LINE 2: Context Label, Context Position, Embeddings Limit -->
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Context Label','gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="wpaicg_createform_suffix_text"
                        style="width:100%;"
                        placeholder="Context:"
                        value="Context:"
                    />
                </div>

                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Context Position','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_createform_suffix_position" style="width:100%;">
                        <option value="after" selected><?php echo esc_html__('After Prompt','gpt3-ai-content-generator'); ?></option>
                        <option value="before"><?php echo esc_html__('Before Prompt','gpt3-ai-content-generator'); ?></option>
                    </select>
                </div>

                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Embeddings Limit','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_createform_embeddings_limit" style="width:100%;">
                        <option value="1" selected>1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
            </div>

            <!-- LINE 3: Use Default Embedding Model Switch, Provider, Model -->
            <div style="display:flex; gap:15px; margin-bottom:10px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Use Default Model?','gpt3-ai-content-generator'); ?>
                    </label>
                    <input type="hidden" id="wpaicg_createform_use_default_embedding_model" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_createform_default_embed_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Embedding Provider','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_createform_selected_embedding_provider" style="width:100%;">
                        <!-- Dynamically populated by JS (OpenAI / Google / Azure etc.) -->
                    </select>
                </div>

                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Embedding Model','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_createform_selected_embedding_model" style="width:100%;">
                        <!-- Dynamically populated by JS based on selected provider -->
                    </select>
                </div>
            </div>
        </div><!-- #wpaicg_createform_embeddings_settings_wrapper -->

        <hr style="margin:0px 0;" />
        </div><!-- .wpaicg_model_settings_grid -->

        <h4 style="margin-top:0px;"><?php echo esc_html__('Form Info','gpt3-ai-content-generator'); ?></h4>
        <label for="wpaicg_createform_category" style="display:block; margin-bottom:4px;">
            <?php echo esc_html__('Category','gpt3-ai-content-generator'); ?>
        </label>
        <select id="wpaicg_createform_category" style="width:100%; margin-bottom:10px;">
            <option value=""><?php echo esc_html__('-- Select Category --','gpt3-ai-content-generator'); ?></option>
            <?php
            global $wpaicg_categories;
            if ( isset($wpaicg_categories) && is_array($wpaicg_categories) ) {
                foreach($wpaicg_categories as $catKey => $catLabel){
                    echo '<option value="'.esc_attr($catKey).'">'.esc_html($catLabel).'</option>';
                }
            }
            ?>
        </select>

        <label for="wpaicg_createform_description" style="display:block; margin-bottom:4px;">
            <?php echo esc_html__('Short Description','gpt3-ai-content-generator'); ?>
        </label>
        <textarea id="wpaicg_createform_description" rows="3" style="width:100%; margin-bottom:10px;"></textarea>

        <button class="button button-primary" id="wpaicg_createform_model_settings_save" style="margin-top:10px;">
            <?php echo esc_html__('Save','gpt3-ai-content-generator'); ?>
        </button>
    </div>
</div>