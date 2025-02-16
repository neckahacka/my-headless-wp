<?php
declare(strict_types=1);

use WPAICG\WPAICG_Util;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Drag & drop editing container for an existing form, with 2 tabs.
 * Now includes a "Provider" dropdown, followed by the "Model" dropdown,
 * mirroring the Create Mode logic. The chosen provider is read from meta
 * (wpaicg_model_provider) and the model from wpaicg_form_engine.
 *
 * Revised to arrange the Embeddings settings on 3 lines, using switches
 * (like Create mode), and to incorporate the new dynamic provider selection.
 *
 * Also moved Category + Short Description into the Model Settings modal,
 * removing them from the "Basic Info" container in the Style tab.
 */
?>
<div id="wpaicg_edit_container" style="display:none;">

    <!-- Tabs navigation -->
    <ul class="wpaicg_edit_tabs">
        <!-- 
             The first tab has an inline-editable form title. 
             Users can double-click or click the pen icon to edit.
        -->
        <li data-tab="wpaicg_edit_tab1" class="active">
            <span
                id="wpaicg_edit_tab1_title"
                class="editable-tab-title"
                title="<?php echo esc_attr__('Double-click (or click edit icon) to rename','gpt3-ai-content-generator'); ?>"
            >
                <?php echo esc_html__('Design','gpt3-ai-content-generator'); ?>
            </span>
            <span
                class="dashicons dashicons-edit"
                id="wpaicg_edit_tab1_edit_icon"
                style="cursor:pointer; margin-left:5px;"
                title="<?php echo esc_attr__('Rename Form','gpt3-ai-content-generator'); ?>"
            ></span>
            <!-- Hidden input to store the full form title -->
            <input type="hidden" id="wpaicg_editform_title" value="" />
        </li>
        <li data-tab="wpaicg_edit_tab3"><?php echo esc_html__('Settings','gpt3-ai-content-generator'); ?></li>
    </ul>

    <!-- TAB 1: Form Elements + Prompt + Provider/Model Selection -->
    <div class="wpaicg_edit_tab_content active" id="wpaicg_edit_tab1">
        <div class="wpaicg_form_builder">
            <div class="builder_left">
                <h3><?php echo esc_html__('Form Elements','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Drag an element here to add to existing form.','gpt3-ai-content-generator'); ?></p>
                <ul>
                    <li draggable="true" data-type="text"><?php echo esc_html__('Single Line Text','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="textarea"><?php echo esc_html__('Multi-line Text','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="email"><?php echo esc_html__('Email','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="number"><?php echo esc_html__('Number','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="checkbox"><?php echo esc_html__('Checkbox','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="radio"><?php echo esc_html__('Radio','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="select"><?php echo esc_html__('Select','gpt3-ai-content-generator'); ?></li>
                    <li draggable="true" data-type="url"><?php echo esc_html__('URL','gpt3-ai-content-generator'); ?></li>

                    <!-- Newly added for Edit Mode as well -->
                    <li draggable="true" data-type="fileupload"><?php echo esc_html__('File Upload','gpt3-ai-content-generator'); ?></li>
                </ul>
            </div>

            <div class="builder_center">
                <h3><?php echo esc_html__('Edit Form','gpt3-ai-content-generator'); ?></h3>
                <div class="builder_fields_dropzone" id="wpaicg_edit_dropzone">
                    <p class="builder_placeholder"><?php echo esc_html__('Drop fields here','gpt3-ai-content-generator'); ?></p>
                </div>
            </div>

            <div class="builder_right">
                <h3><?php echo esc_html__('AI Settings','gpt3-ai-content-generator'); ?></h3>
                <!-- Hidden form_id field -->
                <input type="hidden" id="wpaicg_edit_form_id" value="" />

                <!-- Provider & Model Dropdowns (mirroring Create Mode) -->
                <div style="display:flex; gap:10px; margin-bottom:10px;">
                    <div>
                        <label for="wpaicg_editform_provider" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Provider','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_editform_provider">
                            <option value="OpenAI">OpenAI</option>
                            <option value="Google">Google</option>
                            <option value="OpenRouter">OpenRouter</option>
                            <option value="Azure">Azure</option>
                        </select>
                    </div>

                    <div>
                        <label for="wpaicg_editform_engine" style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Model','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_editform_engine" style="max-width: 150px;">
                            <option value="">
                                <?php echo esc_html__('Select a model','gpt3-ai-content-generator'); ?>
                            </option>
                        </select>
                        <!-- Click to open modal -->
                        <span
                            class="dashicons dashicons-admin-generic"
                            style="cursor: pointer;"
                            id="wpaicg_editform_model_settings_icon"
                            title="<?php echo esc_attr__('Advanced Model Settings','gpt3-ai-content-generator'); ?>">
                        </span>
                        <!-- Internet Browsing icon toggle -->
                        <span
                            class="dashicons dashicons-admin-site"
                            id="wpaicg_editform_internet_toggle"
                            style="cursor:pointer; color:#808080;"
                            title="<?php echo esc_attr__('Enable/Disable Internet Browsing','gpt3-ai-content-generator'); ?>"
                        ></span>
                        <!-- Hidden input storing "yes"/"no" for internet browsing -->
                        <input type="hidden" id="wpaicg_editform_internet" value="no" />
                    </div>
                </div>

                <!-- Prompt Settings -->
                <h3><?php echo esc_html__('Prompt Settings','gpt3-ai-content-generator'); ?></h3>
                <label for="wpaicg_editform_prompt"><?php echo esc_html__('Prompt','gpt3-ai-content-generator'); ?></label>
                <textarea
                    id="wpaicg_editform_prompt"
                    rows="10"
                    placeholder="<?php echo esc_attr__('Use {fieldID} placeholders in your prompt','gpt3-ai-content-generator'); ?>"
                    style="width:100%;"></textarea>

                <!-- ID snippets below the prompt -->
                <div class="wpaicg_id_snippets" id="wpaicg_edit_snippets"></div>
                <div id="wpaicg_edit_copied_msg" class="wpaicg_copied_msg" style="display:none;">
                    <?php echo esc_html__('Copied!','gpt3-ai-content-generator'); ?>
                </div>

                <button class="button" id="wpaicg_edit_validate_prompt" style="margin-top:10px;" disabled>
                    <?php echo esc_html__('Validate My Prompt','gpt3-ai-content-generator'); ?>
                </button>
                <div id="wpaicg_edit_validation_results" style="margin:8px 0; display:none;">
                    <!-- In code, we'll insert #wpaicg_edit_validate_count_result, #wpaicg_edit_validate_order_result, etc.
                         but we only show #wpaicg_edit_validate_existence_result in the default layout. -->
                    <div>
                        <span id="wpaicg_edit_validate_count_result"></span><br/>
                        <span id="wpaicg_edit_validate_order_result"></span><br/>
                        <span id="wpaicg_edit_validate_existence_result" style="margin-left:6px;"></span>
                    </div>
                </div>
            </div><!-- .builder_right -->
        </div><!-- .wpaicg_form_builder -->
    </div><!-- #wpaicg_edit_tab1 -->

    <!-- TAB 3: Interface/Settings -->
    <div class="wpaicg_edit_tab_content" id="wpaicg_edit_tab3">
        <div class="wpaicg_form_builder">

            <div class="builder_left">
                <h3><?php echo esc_html__('Interface','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Customize the look and feel of the form.','gpt3-ai-content-generator'); ?></p>

                <!-- Single line row for Output, Icon, Icon Color, Background -->
                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                    <div style="flex: 1;">
                        <label for="wpaicg_editform_editor">
                            <?php echo esc_html__('Output','gpt3-ai-content-generator'); ?>
                        </label><br/>
                        <select id="wpaicg_editform_editor" style="width:100%;">
                            <option value="div">
                                <?php echo esc_html__('Inline','gpt3-ai-content-generator'); ?>
                            </option>
                            <option value="editor">
                                <?php echo esc_html__('Text Editor','gpt3-ai-content-generator'); ?>
                            </option>
                        </select>
                    </div>
                    <!-- Icon is a dropdown from icons.json -->
                    <div style="flex: 1;">
                        <label for="wpaicg_editform_icon">
                            <?php echo esc_html__('Icon','gpt3-ai-content-generator'); ?>
                        </label><br/>
                        <select id="wpaicg_editform_icon" style="width:100%;">
                            <option value="">
                                <?php echo esc_html__('(none)','gpt3-ai-content-generator'); ?>
                            </option>
                        </select>
                    </div>
                    <div style="flex: 1;">
                        <label for="wpaicg_editform_color">
                            <?php echo esc_html__('Icon Color','gpt3-ai-content-generator'); ?>
                        </label><br/>
                        <input type="color" id="wpaicg_editform_color" style="width:100%;" value="#00BFFF" />
                    </div>
                    <div style="flex: 1;">
                        <label for="wpaicg_editform_bgcolor">
                            <?php echo esc_html__('Background','gpt3-ai-content-generator'); ?>
                        </label><br/>
                        <input type="color" id="wpaicg_editform_bgcolor" style="width:100%;" value="#f9f9f9" />
                    </div>
                </div>

                <!-- Switch for Header + Switch for Feedback -->
                <div style="display:flex; gap:10px;">
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <label style="margin:0;">
                            <?php echo esc_html__('Display Header','gpt3-ai-content-generator'); ?>
                        </label>
                        <input type="hidden" id="wpaicg_editform_header" value="no" />
                        <label class="wpaicg-switch">
                            <input type="checkbox" id="wpaicg_editform_header_switch" />
                            <span class="slider"></span>
                        </label>
                    </div>

                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
                        <label style="margin:0;">
                            <?php echo esc_html__('Allow Feedback','gpt3-ai-content-generator'); ?>
                        </label>
                        <input type="hidden" id="wpaicg_editform_feedback_buttons" value="no" />
                        <label class="wpaicg-switch">
                            <input type="checkbox" id="wpaicg_editform_feedback_buttons_switch" />
                            <span class="slider"></span>
                        </label>
                    </div>
                </div>
            </div><!-- .builder_left -->

            <div class="builder_center">
                <h3><?php echo esc_html__('Custom Text','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Customize the text for various elements.','gpt3-ai-content-generator'); ?></p>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_editform_copy_text" style="width:90%; margin-bottom:10px;" />
                    <input type="hidden" id="wpaicg_editform_copy_button" value="no" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_editform_copy_button_switch" />
                        <span class="slider"></span>
                    </label>
                </div>

                <!-- hidden noanswer_text -->
                <input type="hidden" id="wpaicg_editform_noanswer_text" style="width:100%; margin-bottom:10px;" />

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_editform_draft_text" style="width:90%; margin-bottom:10px;" />
                    <input type="hidden" id="wpaicg_editform_ddraft" value="no" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_editform_ddraft_switch" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_editform_clear_text" style="width:90%; margin-bottom:10px;" />
                    <input type="hidden" id="wpaicg_editform_dclear" value="no" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_editform_dclear_switch" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_editform_cnotice_text" style="width:90%; margin-bottom:10px;" />
                    <input type="hidden" id="wpaicg_editform_dnotice" value="no" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_editform_dnotice_switch" />
                        <span class="slider"></span>
                    </label>
                </div>

                <div style="display:flex; gap:10px;">
                    <input type="text" id="wpaicg_editform_download_text" style="margin-bottom:10px;width: 90%;" />
                    <input type="hidden" id="wpaicg_editform_ddownload" value="no" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_editform_ddownload_switch" />
                        <span class="slider"></span>
                    </label>
                </div>

                <label for="wpaicg_editform_generate_text">
                    <?php echo esc_html__('Generate Button','gpt3-ai-content-generator'); ?>
                </label>
                <input type="text" id="wpaicg_editform_generate_text" style="width:100%; margin-bottom:10px;" />

                <label for="wpaicg_editform_stop_text">
                    <?php echo esc_html__('Stop Button','gpt3-ai-content-generator'); ?>
                </label>
                <input type="text" id="wpaicg_editform_stop_text" style="width:100%; margin-bottom:10px;" />
            </div>

            <div class="builder_right">
                <h3><?php echo esc_html__('Theme','gpt3-ai-content-generator'); ?></h3>
                <p><?php echo esc_html__('Coming Soon!','gpt3-ai-content-generator'); ?></p>
            </div>
        </div>
    </div><!-- #wpaicg_edit_tab3 -->

    <!-- Bottom area: status message -->
    <div id="wpaicg_edit_status" style="margin-top:10px; color:green; display:none;"></div>
</div>

<!-- Model Settings Modal (true overlay) -->
<div id="wpaicg_editform_model_settings_modal" class="wpaicg_model_settings_modal">
    <div class="wpaicg_model_settings_modal_content">
        <span class="wpaicg_modal_close" id="wpaicg_editform_model_settings_close">
            &times;
        </span>
        <h3><?php echo esc_html__('Form Settings','gpt3-ai-content-generator'); ?></h3>

        <hr style="margin:20px 0;" />
        <h4 style="margin-bottom:8px;">
            <?php echo esc_html__('AI Parameters','gpt3-ai-content-generator'); ?>
        </h4>
        <!-- AI Parameter Settings in a grid layout -->
        <div style="display: flex; flex-direction: column; gap: 15px; margin-bottom: 20px;">
            <!-- Row 1: Max Tokens / Stop Words -->
            <div style="display:flex; gap:10px;">
                <label style="flex:1;">
                    <?php echo esc_html__('Max Tokens','gpt3-ai-content-generator'); ?><br/>
                    <input type="number" id="wpaicg_editform_max_tokens" min="1" max="16384" value="1500" style="width:100%;" />
                </label>
                <label style="flex:1;">
                    <?php echo esc_html__('Stop Word(s)','gpt3-ai-content-generator'); ?><br/>
                    <input
                        type="text"
                        id="wpaicg_editform_stop"
                        style="width:100%;"
                    />
                </label>
            </div>
            <!-- Row 2: Sliders (Top P, Frequency Penalty, Presence Penalty) -->
            <div style="display:flex; gap:10px;">
                <label style="flex:1;">
                    <?php echo esc_html__('Top P','gpt3-ai-content-generator'); ?><br/>
                    <input type="range" id="wpaicg_editform_top_p" min="0" max="1" step="0.01" value="1" style="width:100%;" />
                    <span id="wpaicg_editform_top_p_value">1</span>
                </label>
                <label style="flex:1;">
                    <?php echo esc_html__('Frequency Penalty','gpt3-ai-content-generator'); ?><br/>
                    <input type="range" id="wpaicg_editform_frequency_penalty" min="0" max="2" step="0.1" value="0" style="width:100%;" />
                    <span id="wpaicg_editform_frequency_penalty_value">0</span>
                </label>
                <label style="flex:1;">
                    <?php echo esc_html__('Presence Penalty','gpt3-ai-content-generator'); ?><br/>
                    <input type="range" id="wpaicg_editform_presence_penalty" min="0" max="2" step="0.1" value="0" style="width:100%;" />
                    <span id="wpaicg_editform_presence_penalty_value">0</span>
                </label>
            </div>
            <!-- best_of is hidden -->
            <input type="hidden" id="wpaicg_editform_best_of" value="1" />
        </div>

        <hr style="margin:20px 0;" />

        <!-- Embeddings Settings (3 lines), using switches for yes/no, etc. -->
        <h4 style="margin-bottom:8px;">
            <?php echo esc_html__('Embeddings','gpt3-ai-content-generator'); ?>
        </h4>

        <!-- 1) Use Embeddings SWITCH -->
        <div style="display:flex; align-items:center; gap:10px; margin-bottom:10px;">
            <label style="margin:0;">
                <?php echo esc_html__('Use Embeddings?','gpt3-ai-content-generator'); ?>
            </label>
            <input type="hidden" id="wpaicg_editform_embeddings" value="no" />
            <label class="wpaicg-switch">
                <input type="checkbox" id="wpaicg_editform_embeddings_switch" />
                <span class="slider"></span>
            </label>
        </div>

        <!-- Container that shows/hides the embeddings block -->
        <div id="wpaicg_editform_embeddings_settings_wrapper" style="display:none;">
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <!-- Vector DB select -->
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Vector DB','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_editform_vectordb" style="width:100%;">
                        <option value="pinecone">Pinecone</option>
                        <option value="qdrant">Qdrant</option>
                    </select>
                </div>

                <!-- Pinecone or Qdrant selection -->
                <div style="flex:1;">
                    <!-- Pinecone indexes -->
                    <div id="wpaicg_editform_pineconeindexes_wrap" style="display:none;">
                        <label style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Pinecone Index','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_editform_pineconeindexes" style="width:100%;">
                            <!-- Populated by JS -->
                        </select>
                    </div>
                    <!-- Qdrant collections -->
                    <div id="wpaicg_editform_collections_wrap" style="display:none;">
                        <label style="display:block; margin-bottom:4px;">
                            <?php echo esc_html__('Qdrant Collection','gpt3-ai-content-generator'); ?>
                        </label>
                        <select id="wpaicg_editform_collections" style="width:100%;">
                            <!-- Populated by JS -->
                        </select>
                    </div>
                </div>
            </div>

            <!-- 2) Context Label, Context Position, Embeddings Limit -->
            <div style="display:flex; gap:15px; margin-bottom:15px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Context Label','gpt3-ai-content-generator'); ?>
                    </label>
                    <input
                        type="text"
                        id="wpaicg_editform_suffix_text"
                        style="width:100%;"
                        placeholder="Context:"
                        value="Context:"
                    />
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Context Position','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_editform_suffix_position" style="width:100%;">
                        <option value="after">
                            <?php echo esc_html__('After Prompt','gpt3-ai-content-generator'); ?>
                        </option>
                        <option value="before">
                            <?php echo esc_html__('Before Prompt','gpt3-ai-content-generator'); ?>
                        </option>
                    </select>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Embeddings Limit','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_editform_embeddings_limit" style="width:100%;">
                        <option value="1">1</option>
                        <option value="2">2</option>
                        <option value="3">3</option>
                    </select>
                </div>
            </div>

            <!-- 3) Use default embedding model SWITCH, plus provider+model -->
            <div style="display:flex; gap:15px; margin-bottom:10px;">
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Use Default Model?','gpt3-ai-content-generator'); ?>
                    </label>
                    <input type="hidden" id="wpaicg_editform_use_default_embedding_model" value="yes" />
                    <label class="wpaicg-switch">
                        <input type="checkbox" id="wpaicg_editform_default_embed_switch" checked />
                        <span class="slider"></span>
                    </label>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Embedding Provider','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_editform_selected_embedding_provider" style="width:100%;">
                        <!-- Populated by JS -->
                    </select>
                </div>
                <div style="flex:1;">
                    <label style="display:block; margin-bottom:4px;">
                        <?php echo esc_html__('Embedding Model','gpt3-ai-content-generator'); ?>
                    </label>
                    <select id="wpaicg_editform_selected_embedding_model" style="width:100%;">
                        <!-- Populated by JS -->
                    </select>
                </div>
            </div>
        </div><!-- #wpaicg_editform_embeddings_settings_wrapper -->

        <hr style="margin:20px 0;" />
        <h4 style="margin-bottom:8px;">
            <?php echo esc_html__('Form Info','gpt3-ai-content-generator'); ?>
        </h4>
        <label for="wpaicg_editform_category" style="display:block; margin-bottom:4px;">
            <?php echo esc_html__('Category','gpt3-ai-content-generator'); ?>
        </label>
        <select id="wpaicg_editform_category" style="width:100%; margin-bottom:10px;">
            <option value="">
                <?php echo esc_html__('-- Select Category --','gpt3-ai-content-generator'); ?>
            </option>
            <?php
            global $wpaicg_categories;
            if ( isset($wpaicg_categories) && is_array($wpaicg_categories) ) {
                foreach($wpaicg_categories as $catKey => $catLabel){
                    echo '<option value="'.esc_attr($catKey).'">'.esc_html($catLabel).'</option>';
                }
            }
            ?>
        </select>

        <label for="wpaicg_editform_description" style="display:block; margin-bottom:4px;">
            <?php echo esc_html__('Short Description','gpt3-ai-content-generator'); ?>
        </label>
        <textarea id="wpaicg_editform_description" rows="3" style="width:100%; margin-bottom:10px;"></textarea>

        <button class="button button-primary" id="wpaicg_editform_model_settings_save" style="margin-top:10px;">
            <?php echo esc_html__('Save','gpt3-ai-content-generator'); ?>
        </button>
    </div>
</div>