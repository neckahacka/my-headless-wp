<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * JS for the AI Forms Beta "Edit" interface.
 * 
 * - Revised to handle dynamic Provider -> Model population in Edit Mode,
 *   mirroring Create Mode.
 * - Uses delegated event binding for `.wpaicg_preview_edit` so that
 *   clicking "Edit" in preview mode reliably triggers the edit interface.
 * - Includes Embeddings toggle, file-upload field type, and other logic.
 */

// Prepare Qdrant and Pinecone arrays
$qdrant_collections_opt = get_option('wpaicg_qdrant_collections', []);
if ( ! is_array($qdrant_collections_opt) ) {
    $decoded_qdrant = json_decode((string) $qdrant_collections_opt, true);
    if ( is_array($decoded_qdrant) ) {
        $qdrant_collections_opt = $decoded_qdrant;
    } else {
        $qdrant_collections_opt = [];
    }
}
$qdrant_default_collection = get_option('wpaicg_qdrant_default_collection', '');

$pinecone_indexes_opt = get_option('wpaicg_pinecone_indexes','');
if ( ! is_array($pinecone_indexes_opt) ) {
    $decoded_pinecone = json_decode((string) $pinecone_indexes_opt, true);
    if ( is_array($decoded_pinecone) ) {
        $pinecone_indexes_opt = $decoded_pinecone;
    } else {
        $pinecone_indexes_opt = [];
    }
}

// Embedding models from WPAICG_Util
use WPAICG\WPAICG_Util;
$embedding_models = WPAICG_Util::get_instance()->get_embedding_models();

// Icons from icons.json
$wpaicg_plugin_dir = WPAICG_PLUGIN_DIR;
$wpaicg_icons_file = $wpaicg_plugin_dir . 'admin/data/icons.json';
$wpaicg_icons      = [];
if ( file_exists( $wpaicg_icons_file ) ) {
    $content = file_get_contents( $wpaicg_icons_file );
    $decoded = json_decode( $content, true );
    if ( is_array( $decoded ) ) {
        $wpaicg_icons = $decoded;
    }
}

// Also gather model lists for dynamic population (like create mode):
$wpaicg_provider     = get_option('wpaicg_provider','OpenAI');
$gpt4_models         = WPAICG_Util::get_instance()->openai_gpt4_models;
$gpt35_models        = WPAICG_Util::get_instance()->openai_gpt35_models;
$custom_models       = get_option('wpaicg_custom_models', []);
$google_models       = get_option('wpaicg_google_model_list', ['gemini-pro']);
$openrouter_raw      = get_option('wpaicg_openrouter_model_list', []);
$azure_deployment    = get_option('wpaicg_azure_deployment','');
$default_openai      = 'gpt-4o-mini';
$default_google      = get_option('wpaicg_google_default_model','gemini-pro');
$default_openrouter  = 'openrouter/auto';

// Group openrouter models
$openrouter_grouped = [];
if (is_array($openrouter_raw)) {
    foreach ($openrouter_raw as $entry) {
        $provSplit = explode('/', $entry['id'])[0];
        if (!isset($openrouter_grouped[$provSplit])) {
            $openrouter_grouped[$provSplit] = [];
        }
        $openrouter_grouped[$provSplit][] = $entry['id'];
    }
}
ksort($openrouter_grouped);

// Also retrieve the global Google API Key / CSE ID for controlling the toggle
$current_google_api_key = get_option('wpaicg_google_api_key','');
$current_google_cse_id  = get_option('wpaicg_google_search_engine_id','');

// Nonce for editing
$edit_nonce = wp_create_nonce('wpaicg_save_edited_form_nonce');
?>
<script>
(function($){
"use strict";

/*************************************************************
 * 1) GLOBAL ARRAYS FOR QDRANT / PINECONE / EMBEDDINGS / ICONS
 *************************************************************/
var qdrantCollectionsEdit = <?php echo json_encode($qdrant_collections_opt); ?>;
var qdrantDefaultEdit     = <?php echo json_encode($qdrant_default_collection); ?>;
var pineconeIndexesEdit   = <?php echo json_encode($pinecone_indexes_opt); ?>;
var embeddingModelsEdit   = <?php echo json_encode($embedding_models); ?>;
var wpaicgIconsEdit       = <?php echo json_encode($wpaicg_icons); ?>;

// For dynamic provider->model
var wpaicgDefaultProviderEdit = "<?php echo esc_js($wpaicg_provider); ?>";
var openaiGpt4Edit            = <?php echo json_encode($gpt4_models); ?>;
var openaiGpt35Edit           = <?php echo json_encode($gpt35_models); ?>;
var openaiCustomEdit          = <?php echo json_encode($custom_models); ?>;
var googleModelsEdit          = <?php echo json_encode($google_models); ?>;
var openrouterGroupEdit       = <?php echo json_encode($openrouter_grouped); ?>;
var azureDeploymentEdit       = "<?php echo esc_js($azure_deployment); ?>";
var defaultOpenaiEdit         = "<?php echo esc_js($default_openai); ?>";
var defaultGoogleEdit         = "<?php echo esc_js($default_google); ?>";
var defaultOpenRouterEdit     = "<?php echo esc_js($default_openrouter); ?>";

// For Internet toggling
var globalGoogleApiKey = "<?php echo esc_js($current_google_api_key); ?>";
var globalGoogleCseId  = "<?php echo esc_js($current_google_cse_id); ?>";

/*************************************************************
 * 2) PINECONE, QDRANT, EMBEDDING PROVIDERS, ICONS => Populate
 *************************************************************/
function populatePineconeIndexesEdit(){
    var $sel = $('#wpaicg_editform_pineconeindexes');
    $sel.empty();
    if(Array.isArray(pineconeIndexesEdit) && pineconeIndexesEdit.length){
        pineconeIndexesEdit.forEach(function(idx){
            var name = idx.name || 'unknown';
            var url  = idx.url || '';
            $sel.append('<option value="'+ url +'">'+ name +'</option>');
        });
    }
}

function populateQdrantCollectionsEdit(){
    var $sel = $('#wpaicg_editform_collections');
    $sel.empty();
    if(Array.isArray(qdrantCollectionsEdit) && qdrantCollectionsEdit.length){
        qdrantCollectionsEdit.forEach(function(c){
            var cname = c.name || 'unnamed';
            $sel.append('<option value="'+ cname +'">'+ cname +'</option>');
        });
    }
    if(qdrantDefaultEdit){
        $sel.val(qdrantDefaultEdit);
    }
}

function populateEmbeddingProvidersAndModelsEdit(){
    var $prov = $('#wpaicg_editform_selected_embedding_provider');
    var $modl = $('#wpaicg_editform_selected_embedding_model');
    $prov.empty();
    $modl.empty();

    $.each(embeddingModelsEdit, function(providerName, modelObj){
        $prov.append('<option value="'+providerName+'">'+providerName+'</option>');
    });

    $prov.on('change', function(){
        var selProv = $(this).val();
        $modl.empty();
        if(embeddingModelsEdit[selProv]){
            $.each(embeddingModelsEdit[selProv], function(mName, dim){
                $modl.append('<option value="'+mName+'">'+mName+' (dim:'+dim+')</option>');
            });
        }
    });
    $prov.trigger('change');
}

function populateIconKeysEdit(){
    var $sel = $('#wpaicg_editform_icon');
    $sel.empty();
    $sel.append('<option value=""><?php echo esc_js(__("(none)","gpt3-ai-content-generator")); ?></option>');
    $.each(wpaicgIconsEdit, function(iconKey, iconClass){
        $sel.append('<option value="'+ iconKey +'">'+ iconKey +'</option>');
    });
}

/*************************************************************
 * 2.1) DYNAMIC PROVIDER -> MODEL for Edit Mode
 *************************************************************/
function populateEditModelsByProvider(provider) {
    var $modelSelect = $('#wpaicg_editform_engine');
    $modelSelect.empty();

    if(provider === 'OpenAI') {
        if(Object.keys(openaiGpt35Edit).length) {
            $modelSelect.append('<optgroup label="GPT 3.5 Models"></optgroup>');
            $.each(openaiGpt35Edit, function(mKey, mLabel){
                $modelSelect.find('optgroup[label="GPT 3.5 Models"]').append(
                    '<option value="'+ mKey +'">'+ mLabel +'</option>'
                );
            });
        }
        if(Object.keys(openaiGpt4Edit).length) {
            $modelSelect.append('<optgroup label="GPT 4 Models"></optgroup>');
            $.each(openaiGpt4Edit, function(mKey, mLabel){
                $modelSelect.find('optgroup[label="GPT 4 Models"]').append(
                    '<option value="'+ mKey +'">'+ mLabel +'</option>'
                );
            });
        }
        if(Array.isArray(openaiCustomEdit) && openaiCustomEdit.length) {
            $modelSelect.append('<optgroup label="Custom Fine-Tuned"></optgroup>');
            openaiCustomEdit.forEach(function(cmodel){
                $modelSelect.find('optgroup[label="Custom Fine-Tuned"]').append(
                    '<option value="'+ cmodel +'">'+ cmodel +'</option>'
                );
            });
        }
        $modelSelect.val(defaultOpenaiEdit);

    } else if(provider === 'Google') {
        if(Array.isArray(googleModelsEdit) && googleModelsEdit.length) {
            googleModelsEdit.forEach(function(gm){
                $modelSelect.append('<option value="'+ gm +'">'+ gm +'</option>');
            });
        }
        $modelSelect.val(defaultGoogleEdit);

    } else if(provider === 'OpenRouter') {
        var keys = Object.keys(openrouterGroupEdit).sort();
        keys.forEach(function(k){
            $modelSelect.append('<optgroup label="'+ k +'"></optgroup>');
            openrouterGroupEdit[k].forEach(function(m){
                $modelSelect.find('optgroup[label="'+ k +'"]').append(
                    '<option value="'+ m +'">'+ m +'</option>'
                );
            });
        });
        $modelSelect.val(defaultOpenRouterEdit);

    } else if(provider === 'Azure') {
        if(azureDeploymentEdit) {
            $modelSelect.append('<option value="'+ azureDeploymentEdit +'">'+ azureDeploymentEdit +'</option>');
        } else {
            $modelSelect.append('<option value="">(No Azure deployment found)</option>');
        }
    } else {
        // fallback
        $modelSelect.append('<option value=""><?php echo esc_js(__("Select a model","gpt3-ai-content-generator")); ?></option>');
    }
}

$('#wpaicg_editform_provider').on('change', function(){
    var selectedProv = $(this).val();
    populateEditModelsByProvider(selectedProv);
});

/*************************************************************
 * 3) ON DOCUMENT READY -> INIT
 *************************************************************/
$(document).ready(function(){
    populatePineconeIndexesEdit();
    populateQdrantCollectionsEdit();
    populateEmbeddingProvidersAndModelsEdit();
    populateIconKeysEdit();

    // Hide advanced embeddings block by default
    $('#wpaicg_editform_embeddings_settings_wrapper').hide();
    $('#wpaicg_editform_pineconeindexes_wrap').hide();
    $('#wpaicg_editform_collections_wrap').hide();
});

/*************************************************************
 * 4) SWITCHES: Embeddings, Default Embedding Model, VectorDB
 *************************************************************/
$('#wpaicg_editform_embeddings_switch').on('change', function(){
    if($(this).is(':checked')){
        $('#wpaicg_editform_embeddings').val('yes');
    } else {
        $('#wpaicg_editform_embeddings').val('no');
    }
    $('#wpaicg_editform_embeddings').trigger('change');
});

$('#wpaicg_editform_embeddings').on('change', function(){
    var val = $(this).val();
    if(val === 'yes'){
        $('#wpaicg_editform_embeddings_settings_wrapper').show();
    } else {
        $('#wpaicg_editform_embeddings_settings_wrapper').hide();
        $('#wpaicg_editform_pineconeindexes_wrap').hide();
        $('#wpaicg_editform_collections_wrap').hide();
    }
});

$('#wpaicg_editform_vectordb').on('change', function(){
    var dbVal = $(this).val();
    if(dbVal === 'pinecone'){
        $('#wpaicg_editform_pineconeindexes_wrap').show();
        $('#wpaicg_editform_collections_wrap').hide();
    } else {
        $('#wpaicg_editform_pineconeindexes_wrap').hide();
        $('#wpaicg_editform_collections_wrap').show();
    }
});

$('#wpaicg_editform_default_embed_switch').on('change', function(){
    if($(this).is(':checked')){
        $('#wpaicg_editform_use_default_embedding_model').val('yes');
        $('#wpaicg_editform_selected_embedding_provider').prop('disabled', true);
        $('#wpaicg_editform_selected_embedding_model').prop('disabled', true);
    } else {
        $('#wpaicg_editform_use_default_embedding_model').val('no');
        $('#wpaicg_editform_selected_embedding_provider').prop('disabled', false);
        $('#wpaicg_editform_selected_embedding_model').prop('disabled', false);
    }
});

/*************************************************************
 * 5) SWITCHES for Header, Copy Button, Draft, etc.
 *************************************************************/
$('#wpaicg_editform_header_switch').on('change', function(){
    $('#wpaicg_editform_header').val($(this).is(':checked') ? 'yes' : 'no');
});
$('#wpaicg_editform_copy_button_switch').on('change', function(){
    $('#wpaicg_editform_copy_button').val($(this).is(':checked') ? 'yes' : 'no');
});
$('#wpaicg_editform_ddraft_switch').on('change', function(){
    $('#wpaicg_editform_ddraft').val($(this).is(':checked') ? 'yes' : 'no');
});
$('#wpaicg_editform_dclear_switch').on('change', function(){
    $('#wpaicg_editform_dclear').val($(this).is(':checked') ? 'yes' : 'no');
});
$('#wpaicg_editform_dnotice_switch').on('change', function(){
    $('#wpaicg_editform_dnotice').val($(this).is(':checked') ? 'yes' : 'no');
});
$('#wpaicg_editform_ddownload_switch').on('change', function(){
    $('#wpaicg_editform_ddownload').val($(this).is(':checked') ? 'yes' : 'no');
});
$('#wpaicg_editform_feedback_buttons_switch').on('change', function(){
    $('#wpaicg_editform_feedback_buttons').val($(this).is(':checked') ? 'yes' : 'no');
});

/*************************************************************
 * 6) Internet Browsing Toggle (Edit Mode)
 *************************************************************/
$('#wpaicg_editform_internet_toggle').on('click', function(){
    var currentVal = $('#wpaicg_editform_internet').val();
    // If no Google API key or no CSE, block
    if(!globalGoogleApiKey || !globalGoogleCseId){
        alert("Please configure your Google API Key and Search Engine ID first.");
        return;
    }
    if(currentVal==='no'){
        $('#wpaicg_editform_internet').val('yes');
        $(this).css('color','#2271b1');
    } else {
        $('#wpaicg_editform_internet').val('no');
        $(this).css('color','#808080');
    }
});

/*************************************************************
 * 6) INLINE TITLE EDITING
 *************************************************************/
var isEditingEditTitle = false;
var $editTitleHidden   = $('#wpaicg_editform_title');
var $editTitleDisplay  = $('#wpaicg_edit_tab1_title');
var $editTitleEditIcon = $('#wpaicg_edit_tab1_edit_icon');

function truncateEditTitle(fullTitle){
    if(!fullTitle){
        return '<?php echo esc_js(__("Design","gpt3-ai-content-generator")); ?>';
    }
    if(fullTitle.length > 10){
        return fullTitle.substring(0, 10) + '...';
    }
    return fullTitle;
}
function updateEditTabTitleDisplay(newTitle){
    $editTitleDisplay.text(truncateEditTitle(newTitle));
}
function startEditTitleEditing(){
    if(isEditingEditTitle) return;
    isEditingEditTitle = true;
    var currentVal = $editTitleHidden.val().trim();
    $editTitleDisplay.text(currentVal);
    $editTitleDisplay.attr('contenteditable','true').focus();
    document.execCommand('selectAll', false, null);
}
function finishEditTitleEditing(){
    if(!isEditingEditTitle) return;
    var newVal = $editTitleDisplay.text().trim();
    $editTitleHidden.val(newVal);
    $editTitleDisplay.removeAttr('contenteditable');
    isEditingEditTitle = false;
    updateEditTabTitleDisplay(newVal);
}
function cancelEditTitleEditing(){
    if(!isEditingEditTitle) return;
    $editTitleDisplay.removeAttr('contenteditable');
    var oldVal = $editTitleHidden.val().trim();
    updateEditTabTitleDisplay(oldVal);
    isEditingEditTitle = false;
}

$editTitleDisplay.on('dblclick', startEditTitleEditing);
$editTitleEditIcon.on('click', startEditTitleEditing);
$editTitleDisplay.on('keydown', function(e){
    if(e.key === 'Enter'){
        e.preventDefault();
        finishEditTitleEditing();
    } else if(e.key === 'Escape'){
        e.preventDefault();
        cancelEditTitleEditing();
    }
});
$editTitleDisplay.on('blur', function(){
    if(isEditingEditTitle){
        finishEditTitleEditing();
    }
});

/*************************************************************
 * 7) DELEGATED EVENT FOR "Edit" BUTTON IN PREVIEW
 *************************************************************/
$(document).on('click', '.wpaicg_preview_edit', function(){
    // Grab the form ID to edit
    var formID = $(this).attr('data-edit-id');
    if(!formID) {
        showGlobalMessage('error','No form ID found for edit');
        return;
    }

    // Hide main containers
    $('#wpaicg_aiforms_container, #wpaicg_logs_container, #wpaicg_settings_container, #wpaicg_preview_panel').hide();
    // Show edit container
    $('#wpaicg_edit_container').show();

    // Hide top icons except 'Back' + hide create
    $('#wpaicg_toggle_sidebar_icon, #wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form').hide();
    $('#wpaicg_return_main, #wpaicg_save_edited_form').show();

    // Rename the return button to "Exit Edit Mode"
    $('#wpaicg_return_main').text('<?php echo esc_js(__("Exit Edit Mode","gpt3-ai-content-generator")); ?>');

    // Clear old data
    $('#wpaicg_edit_dropzone').find('.builder_field_item').remove();
    $('#wpaicg_edit_dropzone .builder_placeholder').show();
    $('#wpaicg_editform_prompt').val('');
    $('#wpaicg_edit_validation_results').hide();
    $('#wpaicg_edit_status').hide().text('').css('color','green');
    $('#wpaicg_edit_form_id').val(formID);

    // Clear snippet
    $('#wpaicg_edit_snippets').empty();
    $('#wpaicg_edit_copied_msg').hide();

    // Clear interface fields
    $('#wpaicg_editform_category').val('');
    $('#wpaicg_editform_description').val('');
    $('#wpaicg_editform_color').val('#00BFFF');
    $('#wpaicg_editform_bgcolor').val('#f9f9f9');
    $('#wpaicg_editform_icon').val('');
    $('#wpaicg_editform_header').val('no');
    $('#wpaicg_editform_header_switch').prop('checked', false);
    $('#wpaicg_editform_copy_button').val('no');
    $('#wpaicg_editform_copy_button_switch').prop('checked', false);
    $('#wpaicg_editform_ddraft').val('no');
    $('#wpaicg_editform_ddraft_switch').prop('checked', false);
    $('#wpaicg_editform_dclear').val('no');
    $('#wpaicg_editform_dclear_switch').prop('checked', false);
    $('#wpaicg_editform_dnotice').val('no');
    $('#wpaicg_editform_dnotice_switch').prop('checked', false);
    $('#wpaicg_editform_ddownload').val('no');
    $('#wpaicg_editform_ddownload_switch').prop('checked', false);
    $('#wpaicg_editform_copy_text').val('');
    $('#wpaicg_editform_feedback_buttons').val('no');
    $('#wpaicg_editform_feedback_buttons_switch').prop('checked', false);
    $('#wpaicg_editform_generate_text').val('');
    $('#wpaicg_editform_noanswer_text').val('');
    $('#wpaicg_editform_draft_text').val('');
    $('#wpaicg_editform_clear_text').val('');
    $('#wpaicg_editform_stop_text').val('');
    $('#wpaicg_editform_cnotice_text').val('');
    $('#wpaicg_editform_download_text').val('');

    // Reset advanced model settings
    $('#wpaicg_editform_max_tokens').val('1500');
    $('#wpaicg_editform_top_p').val('1');
    $('#wpaicg_editform_top_p_value').text('1');
    $('#wpaicg_editform_frequency_penalty').val('0');
    $('#wpaicg_editform_frequency_penalty_value').text('0');
    $('#wpaicg_editform_presence_penalty').val('0');
    $('#wpaicg_editform_presence_penalty_value').text('0');
    $('#wpaicg_editform_stop').val('');
    $('#wpaicg_editform_best_of').val('1');

    // Reset the inline-editable form title to default
    $('#wpaicg_editform_title').val('');
    updateEditTabTitleDisplay('');

    // Show snippet placeholder for now
    showShortcodeSnippet('[wpaicg_form id=' + formID + ' settings="no" custom="yes"]');

    // Load existing data via AJAX
    $.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'wpaicg_get_form_data_for_editing',
            nonce: '<?php echo esc_js(wp_create_nonce("wpaicg_edit_form_nonce")); ?>',
            form_id: formID
        },
        success: function(response){
            if(response.success){
                // Set prompt
                $('#wpaicg_editform_prompt').val(response.data.prompt);

                // Fields
                if(response.data.fields && response.data.fields.length>0){
                    $('#wpaicg_edit_dropzone .builder_placeholder').hide();
                    response.data.fields.forEach(function(field){
                        createEditFieldItem(
                            field.type,
                            field.label,
                            field.id,
                            field.min,
                            field.max,
                            field.rows,
                            field.cols,
                            (field.options ? field.options.split('|') : [])
                        );
                    });
                }

                // Set engine, provider
                var metaProvider = response.data.model_provider || '';  // from meta
                var metaEngine   = response.data.engine || '';
                // We'll set provider dropdown first, then trigger change -> populate model
                if(metaProvider){
                    $('#wpaicg_editform_provider').val(metaProvider).trigger('change');
                } else {
                    // fallback to saved default
                    $('#wpaicg_editform_provider').val(wpaicgDefaultProviderEdit).trigger('change');
                }
                // now set model
                if(metaEngine){
                    $('#wpaicg_editform_engine').val(metaEngine);
                }

                // Title + Description
                $('#wpaicg_editform_title').val(response.data.title || '');
                updateEditTabTitleDisplay(response.data.title || '');
                $('#wpaicg_editform_description').val(response.data.description || '');

                // Interface
                if(response.data.interface){
                    var ifData = response.data.interface;
                    if(ifData.wpaicg_form_category){
                        $('#wpaicg_editform_category').val(ifData.wpaicg_form_category);
                    }
                    if(ifData.wpaicg_form_color){
                        $('#wpaicg_editform_color').val(ifData.wpaicg_form_color);
                    }
                    if(ifData.wpaicg_form_bgcolor){
                        $('#wpaicg_editform_bgcolor').val(ifData.wpaicg_form_bgcolor);
                    }
                    if(ifData.wpaicg_form_icon){
                        $('#wpaicg_editform_icon').val(ifData.wpaicg_form_icon);
                    }
                    if(ifData.wpaicg_form_editor){
                        $('#wpaicg_editform_editor').val(ifData.wpaicg_form_editor);
                    }

                    // Switches
                    if(ifData.wpaicg_form_header === 'yes'){
                        $('#wpaicg_editform_header_switch').prop('checked', true);
                        $('#wpaicg_editform_header').val('yes');
                    }
                    if(ifData.wpaicg_form_copy_button === 'yes'){
                        $('#wpaicg_editform_copy_button_switch').prop('checked', true);
                        $('#wpaicg_editform_copy_button').val('yes');
                    }
                    if(ifData.wpaicg_form_ddraft === 'yes'){
                        $('#wpaicg_editform_ddraft_switch').prop('checked', true);
                        $('#wpaicg_editform_ddraft').val('yes');
                    }
                    if(ifData.wpaicg_form_dclear === 'yes'){
                        $('#wpaicg_editform_dclear_switch').prop('checked', true);
                        $('#wpaicg_editform_dclear').val('yes');
                    }
                    if(ifData.wpaicg_form_dnotice === 'yes'){
                        $('#wpaicg_editform_dnotice_switch').prop('checked', true);
                        $('#wpaicg_editform_dnotice').val('yes');
                    }
                    if(ifData.wpaicg_form_ddownload === 'yes'){
                        $('#wpaicg_editform_ddownload_switch').prop('checked', true);
                        $('#wpaicg_editform_ddownload').val('yes');
                    }
                    if(ifData.wpaicg_form_feedback_buttons === 'yes'){
                        $('#wpaicg_editform_feedback_buttons_switch').prop('checked', true);
                        $('#wpaicg_editform_feedback_buttons').val('yes');
                    }
                    if(ifData.wpaicg_form_copy_text){
                        $('#wpaicg_editform_copy_text').val(ifData.wpaicg_form_copy_text);
                    }
                    if(ifData.wpaicg_form_generate_text){
                        $('#wpaicg_editform_generate_text').val(ifData.wpaicg_form_generate_text);
                    }
                    if(ifData.wpaicg_form_noanswer_text){
                        $('#wpaicg_editform_noanswer_text').val(ifData.wpaicg_form_noanswer_text);
                    }
                    if(ifData.wpaicg_form_draft_text){
                        $('#wpaicg_editform_draft_text').val(ifData.wpaicg_form_draft_text);
                    }
                    if(ifData.wpaicg_form_clear_text){
                        $('#wpaicg_editform_clear_text').val(ifData.wpaicg_form_clear_text);
                    }
                    if(ifData.wpaicg_form_stop_text){
                        $('#wpaicg_editform_stop_text').val(ifData.wpaicg_form_stop_text);
                    }
                    if(ifData.wpaicg_form_cnotice_text){
                        $('#wpaicg_editform_cnotice_text').val(ifData.wpaicg_form_cnotice_text);
                    }
                    if(ifData.wpaicg_form_download_text){
                        $('#wpaicg_editform_download_text').val(ifData.wpaicg_form_download_text);
                    }
                }

                // Advanced settings
                if(response.data.advanced_settings){
                    var adv = response.data.advanced_settings;
                    $('#wpaicg_editform_max_tokens').val(adv.max_tokens);
                    $('#wpaicg_editform_top_p').val(adv.top_p);
                    $('#wpaicg_editform_top_p_value').text(adv.top_p);
                    $('#wpaicg_editform_frequency_penalty').val(adv.frequency_penalty);
                    $('#wpaicg_editform_frequency_penalty_value').text(adv.frequency_penalty);
                    $('#wpaicg_editform_presence_penalty').val(adv.presence_penalty);
                    $('#wpaicg_editform_presence_penalty_value').text(adv.presence_penalty);
                    $('#wpaicg_editform_stop').val(adv.stop);
                    $('#wpaicg_editform_best_of').val(adv.best_of);
                }

                // Embeddings
                if(response.data.embedding_settings){
                    const emb = response.data.embedding_settings;
                    $('#wpaicg_editform_embeddings').val(emb.use_embeddings || 'no');
                    $('#wpaicg_editform_vectordb').val(emb.vectordb || 'pinecone');
                    $('#wpaicg_editform_collections').val(emb.collections || '');
                    $('#wpaicg_editform_pineconeindexes').val(emb.pineconeindexes || '');
                    $('#wpaicg_editform_suffix_text').val(emb.suffix_text || 'Context:');
                    $('#wpaicg_editform_suffix_position').val(emb.suffix_position || 'after');
                    $('#wpaicg_editform_use_default_embedding_model').val(emb.use_default_embedding_model || 'yes');
                    $('#wpaicg_editform_selected_embedding_provider').val(emb.selected_embedding_provider || '').trigger('change');
                    $('#wpaicg_editform_selected_embedding_model').val(emb.selected_embedding_model || '');
                    $('#wpaicg_editform_embeddings_limit').val(emb.embeddings_limit || '1');

                    if(emb.use_embeddings === 'yes'){
                        $('#wpaicg_editform_embeddings_switch').prop('checked', true);
                    } else {
                        $('#wpaicg_editform_embeddings_switch').prop('checked', false);
                    }
                    $('#wpaicg_editform_embeddings').trigger('change');

                    $('#wpaicg_editform_vectordb').trigger('change');

                    if(emb.use_default_embedding_model === 'yes'){
                        $('#wpaicg_editform_default_embed_switch').prop('checked', true);
                        $('#wpaicg_editform_selected_embedding_provider').prop('disabled', true);
                        $('#wpaicg_editform_selected_embedding_model').prop('disabled', true);
                    } else {
                        $('#wpaicg_editform_default_embed_switch').prop('checked', false);
                        $('#wpaicg_editform_selected_embedding_provider').prop('disabled', false);
                        $('#wpaicg_editform_selected_embedding_model').prop('disabled', false);
                    }
                }
                if(response.data.internet_browsing === 'yes'){
                    $('#wpaicg_editform_internet').val('yes');
                    $('#wpaicg_editform_internet_toggle').css('color','#2271b1');
                } else {
                    $('#wpaicg_editform_internet').val('no');
                    $('#wpaicg_editform_internet_toggle').css('color','#808080');
                }
            } else {
                $('#wpaicg_edit_status').show().css('color','red')
                    .text(response.data.message || '<?php echo esc_js(__("Error loading form","gpt3-ai-content-generator")); ?>');
            }

            // Check prompt validity automatically
            updateEditValidateButtonState();
        },
        error: function(){
            $('#wpaicg_edit_status').show().css('color','red')
                .text('<?php echo esc_js(__("Failed to load form data.","gpt3-ai-content-generator")); ?>');
        }
    });

    // Show Tab 1 by default
    $('.wpaicg_edit_tabs li').removeClass('active').first().addClass('active');
    $('.wpaicg_edit_tab_content').removeClass('active').hide();
    $('#wpaicg_edit_tab1').addClass('active').show();
});

/*************************************************************
 * 8) DRAG & DROP FIELDS
 *************************************************************/
var $editDropZone = $('#wpaicg_edit_dropzone');
var $editPlaceholder = $editDropZone.find('.builder_placeholder');
var $editFieldsDraggedItem = null;
var editFieldCounter = 1;

function handleEditDragStart(e){
    e.originalEvent.dataTransfer.setData('text/plain', e.target.getAttribute('data-type'));
}
function handleEditDrop(e){
    e.preventDefault();
    if($editFieldsDraggedItem){
        return;
    }
    var dataType = e.originalEvent.dataTransfer.getData('text/plain');
    var allowed = ['text','textarea','email','number','checkbox','radio','select','url','fileupload'];
    if(allowed.indexOf(dataType)>=0){
        createEditFieldItem(dataType);
    }
}

$('.builder_left li').on('dragstart', handleEditDragStart);
$editDropZone.on('dragover', function(e){ e.preventDefault(); });
$editDropZone.on('drop', handleEditDrop);

function createEditFieldItem(dataType, labelVal, idVal, minVal, maxVal, rowsVal, colsVal, optionsArr){
    labelVal = labelVal || '';
    idVal    = idVal    || ('idE'+editFieldCounter++);
    minVal   = minVal   || '';
    maxVal   = maxVal   || '';
    rowsVal  = rowsVal  || '';
    colsVal  = colsVal  || '';
    optionsArr= Array.isArray(optionsArr)?optionsArr:[];

    var domID = 'edit-field-'+Date.now();
    var settingsHtml = getFieldSettingsHtml(dataType, minVal, maxVal, rowsVal, colsVal, optionsArr);

    var $fieldEl = $(
        '<div class="builder_field_item" draggable="true" data-type="'+dataType+'" id="'+domID+'">'+
          '<span class="remove_field">&times;</span>'+
          '<span class="builder_settings_icon">&#9881;</span>'+
          '<label><?php echo esc_js(__("Label","gpt3-ai-content-generator")); ?>:'+
             '<input type="text" class="builder_label_input" value="'+(labelVal||'')+'" placeholder="<?php echo esc_attr__("Field Label","gpt3-ai-content-generator"); ?>"/>'+
          '</label>'+
          '<label><?php echo esc_js(__("ID","gpt3-ai-content-generator")); ?>:'+
             '<input type="text" class="builder_id_input" value="'+(idVal||'')+'" placeholder="<?php echo esc_attr__("Short ID","gpt3-ai-content-generator"); ?>"/>'+
          '</label>'+
          settingsHtml+
          '<small style="display:block; color:#777;"><?php echo esc_js(__("Type","gpt3-ai-content-generator")); ?>: '+dataType+'</small>'+
        '</div>'
    );

    $editDropZone.append($fieldEl);
    $editPlaceholder.hide();
    initEditFieldItemDrag($fieldEl);

    // Add snippet
    addEditSnippet(idVal, domID);

    // Listen for ID changes
    var $idInput = $fieldEl.find('.builder_id_input');
    $idInput.data('oldid', idVal);
    $idInput.on('input', function(){
        var oldID = $(this).data('oldid') || idVal;
        var newIDVal = $(this).val().trim();
        if(!newIDVal){return;}
        replacePlaceholderInText($('#wpaicg_editform_prompt'), oldID, newIDVal);
        updateEditSnippet(oldID, newIDVal, domID);
        $(this).data('oldid', newIDVal);
        updateEditValidateButtonState();
    });

    updateEditValidateButtonState();
}

function initEditFieldItemDrag($el){
    $el.on('dragstart', function(ev){
        ev.originalEvent.dataTransfer.setData('text/plain', $(this).attr('id'));
        $editFieldsDraggedItem = this;
    });
    $el.on('dragend', function(){
        $editFieldsDraggedItem = null;
    });
}

$editDropZone.on('dragover', '.builder_field_item', function(e){
    e.preventDefault();
    var bounding = this.getBoundingClientRect();
    var offset   = bounding.y + (bounding.height/2);
    if(e.originalEvent.clientY - offset > 0){
        $(this).addClass('drag-bottom').removeClass('drag-top');
    } else {
        $(this).addClass('drag-top').removeClass('drag-bottom');
    }
});
$editDropZone.on('dragleave', '.builder_field_item', function(){
    $(this).removeClass('drag-top drag-bottom');
});
$editDropZone.on('drop', '.builder_field_item', function(e){
    e.preventDefault();
    if(!$editFieldsDraggedItem) return;
    if($(this).hasClass('drag-top')){
        $(this).before($editFieldsDraggedItem);
    } else {
        $(this).after($editFieldsDraggedItem);
    }
    $(this).removeClass('drag-top drag-bottom');
    updateEditValidateButtonState();
});
$editDropZone.on('click', '.remove_field', function(){
    var $parent = $(this).parent();
    var oldID = $parent.find('.builder_id_input').val().trim();
    removePlaceholderFromText($('#wpaicg_editform_prompt'), oldID);
    removeEditSnippet(oldID, $parent.attr('id'));
    $parent.remove();
    if($editDropZone.find('.builder_field_item').length===0){
        $editPlaceholder.show();
    }
    updateEditValidateButtonState();
});
$editDropZone.on('click', '.builder_settings_icon', function(){
    $(this).siblings('.field_settings').slideToggle(150);
});
$editDropZone.on('click', '.add_option_btn', function(){
    var $ul = $(this).siblings('.options_list');
    if($ul.length){
        $ul.append('<li><input type="text" class="option_value" value="" /></li>');
    }
});

/*************************************************************
 * 9) SNIPPETS
 *************************************************************/
function addEditSnippet(idVal, domID){
    var snippet = '<span class="wpaicg_snippet" data-dom="'+domID+'" data-id="'+idVal+'">{'+idVal+'}</span>';
    $('#wpaicg_edit_snippets').append(snippet);
}
function updateEditSnippet(oldID, newID, domID){
    var $snip = $('#wpaicg_edit_snippets').find('[data-dom="'+domID+'"]');
    if($snip.length){
        $snip.attr('data-id', newID).data('id', newID).text('{'+newID+'}');
    }
}
function removeEditSnippet(idVal, domID){
    $('#wpaicg_edit_snippets').find('[data-dom="'+domID+'"]').remove();
}

$('#wpaicg_edit_snippets').on('click', '.wpaicg_snippet', function(){
    var snippetID = $(this).data('id');
    var toCopy    = '{' + snippetID + '}';
    copyToClipboard(toCopy, $('#wpaicg_edit_copied_msg'));
});

/*************************************************************
 * 10) VALIDATION
 *************************************************************/
var editPromptIsValid = false;

function updateEditValidateButtonState(){
    var fieldsCount = $editDropZone.find('.builder_field_item').length;
    var promptValue = $('#wpaicg_editform_prompt').val().trim();
    if(fieldsCount > 0 && promptValue.length > 0){
        $('#wpaicg_edit_validate_prompt').removeAttr('disabled');
    } else {
        $('#wpaicg_edit_validate_prompt').attr('disabled','disabled');
    }

    editPromptIsValid = checkEditPromptFields();
    if(editPromptIsValid){
        $('#wpaicg_save_edited_form').removeAttr('disabled');
    } else {
        $('#wpaicg_save_edited_form').attr('disabled','disabled');
    }
}

$('#wpaicg_editform_prompt').on('input', updateEditValidateButtonState);

function checkEditPromptFields(){
    var $fields = $editDropZone.find('.builder_field_item');
    if($fields.length===0){ return false; }

    var missingID = false;
    var fieldIDs  = [];
    $fields.each(function(){
        var idVal = $(this).find('.builder_id_input').val().trim();
        if(!idVal){ missingID=true; }
        fieldIDs.push(idVal);
    });
    if(missingID){ return false; }

    var promptValue = $('#wpaicg_editform_prompt').val().trim();
    if(!promptValue) { return false; }

    var matches = promptValue.match(/\{([^}]+)\}/g) || [];
    var placeholderIDs = matches.map(m => m.replace(/[{}]/g,''));
    if(placeholderIDs.length !== fieldIDs.length){ return false; }

    // Check order
    for(var i=0;i<fieldIDs.length;i++){
        if(fieldIDs[i] !== placeholderIDs[i]){
            return false;
        }
    }
    // Check existence
    for(var j=0;j<fieldIDs.length;j++){
        if(!promptValue.includes('{'+fieldIDs[j]+'}')){
            return false;
        }
    }
    return true;
}

function validateEditPrompt(){
    var $fields = $editDropZone.find('.builder_field_item');
    var fieldIDs= [];
    var missingID = false;

    $fields.each(function(){
        var idVal = $(this).find('.builder_id_input').val().trim();
        if(!idVal){ missingID=true; }
        fieldIDs.push(idVal);
    });

    var promptValue = $('#wpaicg_editform_prompt').val().trim();
    var matches = promptValue.match(/\{([^}]+)\}/g) || [];
    var placeholderIDs = matches.map(m => m.replace(/[{}]/g,''));

    $('#wpaicg_edit_validation_results').show();

    if(missingID){
        $('#wpaicg_edit_validate_count_result').css('color','red')
            .text('✘ Some fields have no ID');
        $('#wpaicg_edit_validate_order_result').css('color','red')
            .text('✘ Not checked');
        $('#wpaicg_edit_validate_existence_result').css('color','red')
            .text('✘ Not checked');
        editPromptIsValid=false;
        $('#wpaicg_save_edited_form').attr('disabled','disabled');
        return;
    }

    // 3) Existence
    var missingIDs = [];
    for(var j=0;j<fieldIDs.length;j++){
        if(!promptValue.includes('{'+fieldIDs[j]+'}')){
            missingIDs.push(fieldIDs[j]);
        }
    }
    if(missingIDs.length===0){
        $('#wpaicg_edit_validate_existence_result').css('color','green')
            .text('✔ All field IDs exist in prompt');
    } else {
        $('#wpaicg_edit_validate_existence_result').css('color','red')
            .text('✘ Missing placeholders: '+missingIDs.join(', '));
    }

    // at the moment we will only validate existence
    editPromptIsValid = (missingIDs.length===0);
    if(editPromptIsValid){
        $('#wpaicg_save_edited_form').removeAttr('disabled');
    } else {
        $('#wpaicg_save_edited_form').attr('disabled','disabled');
    }
}
$('#wpaicg_edit_validate_prompt').on('click', validateEditPrompt);

/*************************************************************
 * 11) SAVE CHANGES (EDIT)
 *************************************************************/
$('#wpaicg_save_edited_form').on('click', function(){
    if(!editPromptIsValid){
        showGlobalMessage('error','<?php echo esc_js(__("Prompt not valid.","gpt3-ai-content-generator")); ?>');
        return;
    }

    var formID      = $('#wpaicg_edit_form_id').val();
    var title       = $('#wpaicg_editform_title').val().trim();
    var description = $('#wpaicg_editform_description').val().trim();
    var prompt      = $('#wpaicg_editform_prompt').val().trim();
    var provider    = $('#wpaicg_editform_provider').val();
    var engine      = $('#wpaicg_editform_engine').val();
    var $fields     = $('#wpaicg_edit_dropzone').find('.builder_field_item');

    if(!formID){
        showGlobalMessage('error','<?php echo esc_js(__("Missing form ID.","gpt3-ai-content-generator")); ?>');
        return;
    }
    if(!title || !description || !prompt){
        showGlobalMessage('error','<?php echo esc_js(__("Please fill all required fields.","gpt3-ai-content-generator")); ?>');
        return;
    }

    var fieldsData = [];
    var missingID  = false;
    $fields.each(function(idx, el){
        var $el    = $(el);
        var type   = $el.data('type');
        var label  = $el.find('.builder_label_input').val().trim();
        var fid    = $el.find('.builder_id_input').val().trim();
        if(!label || !fid){
            missingID = true;
        }
        var minVal = $el.find('.min_value').val() || "";
        var maxVal = $el.find('.max_value').val() || "";
        var rowsVal= $el.find('.rows_value').val() || "";
        var colsVal= $el.find('.cols_value').val() || "";

        var optionValues = [];
        $el.find('.options_list .option_value').each(function(){
            var val = $(this).val().trim();
            if(val){ optionValues.push(val); }
        });
        var fileTypes = "";
        if(type === 'fileupload'){
            var $fTypes = $el.find('.file_types');
            if($fTypes.length){
                fileTypes = $fTypes.val().trim();
            }
        }

        fieldsData.push({
            type: type,
            label: label || 'Field'+(idx+1),
            id: fid,
            min: minVal,
            max: maxVal,
            rows: rowsVal,
            cols: colsVal,
            options: optionValues.join('|'),
            file_types: fileTypes
        });
    });
    if(missingID){
        showGlobalMessage('error','<?php echo esc_js(__("Each field must have a Label and ID","gpt3-ai-content-generator")); ?>');
        return;
    }

    // Interface
    var interfaceData = {
        'wpaicg_form_category':           $('#wpaicg_editform_category').val().trim(),
        'wpaicg_form_color':              $('#wpaicg_editform_color').val().trim(),
        'wpaicg_form_icon':               $('#wpaicg_editform_icon').val().trim(),
        'wpaicg_form_header':             $('#wpaicg_editform_header').val(),
        'wpaicg_form_editor':             $('#wpaicg_editform_editor').val().trim(),
        'wpaicg_form_copy_button':        $('#wpaicg_editform_copy_button').val(),
        'wpaicg_form_ddraft':            $('#wpaicg_editform_ddraft').val(),
        'wpaicg_form_dclear':            $('#wpaicg_editform_dclear').val(),
        'wpaicg_form_dnotice':           $('#wpaicg_editform_dnotice').val(),
        'wpaicg_form_ddownload':         $('#wpaicg_editform_ddownload').val(),
        'wpaicg_form_copy_text':         $('#wpaicg_editform_copy_text').val().trim(),
        'wpaicg_form_feedback_buttons':  $('#wpaicg_editform_feedback_buttons').val(),
        'wpaicg_form_generate_text':     $('#wpaicg_editform_generate_text').val().trim(),
        'wpaicg_form_noanswer_text':     $('#wpaicg_editform_noanswer_text').val().trim(),
        'wpaicg_form_draft_text':        $('#wpaicg_editform_draft_text').val().trim(),
        'wpaicg_form_clear_text':        $('#wpaicg_editform_clear_text').val().trim(),
        'wpaicg_form_stop_text':         $('#wpaicg_editform_stop_text').val().trim(),
        'wpaicg_form_cnotice_text':      $('#wpaicg_editform_cnotice_text').val().trim(),
        'wpaicg_form_download_text':     $('#wpaicg_editform_download_text').val().trim(),
        'wpaicg_form_bgcolor':           $('#wpaicg_editform_bgcolor').val().trim()
    };

    // Advanced model
    var modelSettingsData = {
        max_tokens: parseInt($('#wpaicg_editform_max_tokens').val()) || 1500,
        top_p: parseFloat($('#wpaicg_editform_top_p').val()) || 1,
        best_of: parseInt($('#wpaicg_editform_best_of').val()) || 1,
        frequency_penalty: parseFloat($('#wpaicg_editform_frequency_penalty').val()) || 0,
        presence_penalty: parseFloat($('#wpaicg_editform_presence_penalty').val()) || 0,
        stop: $('#wpaicg_editform_stop').val().trim()
    };

    // Embeddings
    var embeddingSettings = {
        use_embeddings: $('#wpaicg_editform_embeddings').val(),
        vectordb: $('#wpaicg_editform_vectordb').val(),
        collections: $('#wpaicg_editform_collections').val(),
        pineconeindexes: $('#wpaicg_editform_pineconeindexes').val(),
        suffix_text: $('#wpaicg_editform_suffix_text').val(),
        suffix_position: $('#wpaicg_editform_suffix_position').val(),
        use_default_embedding_model: $('#wpaicg_editform_use_default_embedding_model').val(),
        selected_embedding_provider: $('#wpaicg_editform_selected_embedding_provider').val(),
        selected_embedding_model: $('#wpaicg_editform_selected_embedding_model').val(),
        embeddings_limit: $('#wpaicg_editform_embeddings_limit').val()
    };

    // NEW: read internet toggle
    var internetValue = $('#wpaicg_editform_internet').val(); // "yes" or "no"

    var $status = $('#wpaicg_edit_status');
    $status.hide().css('color','green');

    $.ajax({
        url: ajaxurl,
        method: 'POST',
        dataType: 'json',
        data: {
            action: 'wpaicg_save_edited_form',
            nonce: '<?php echo esc_js($edit_nonce); ?>',
            form_id: formID,
            title: title,
            description: description,
            prompt: prompt,
            provider: provider,
            engine: engine,
            fields: JSON.stringify(fieldsData),
            interface: interfaceData,
            model_settings: modelSettingsData,
            embedding_settings: embeddingSettings,
            internet_browsing: internetValue
        },
        success: function(response){
            if(response.success){
                showGlobalMessage('success', response.data.message);
                $status.text(response.data.message).show();
            } else {
                showGlobalMessage('error', response.data.message || 'Error saving form');
                $status.css('color','red').text(response.data.message || 'Error').show();
            }
        },
        error: function(){
            showGlobalMessage('error','<?php echo esc_js(__("Ajax error saving form","gpt3-ai-content-generator")); ?>');
            $status.css('color','red')
                .text('<?php echo esc_js(__("Ajax error saving form","gpt3-ai-content-generator")); ?>').show();
        }
    });
});

/*************************************************************
 * 12) EDIT TABS NAV
 *************************************************************/
$(document).on('click', '.wpaicg_edit_tabs li', function(){
    var tab = $(this).data('tab');
    $('.wpaicg_edit_tabs li').removeClass('active');
    $(this).addClass('active');
    $('.wpaicg_edit_tab_content').removeClass('active').hide();
    $('#'+tab).addClass('active').show();
});

/*************************************************************
 * 13) MODEL SETTINGS MODAL
 *************************************************************/
$('#wpaicg_editform_model_settings_icon').on('click', function(e){
    e.preventDefault();
    $('#wpaicg_editform_model_settings_modal').show();
});
$('#wpaicg_editform_model_settings_close').on('click', function(){
    $('#wpaicg_editform_model_settings_modal').hide();
});
$('#wpaicg_editform_top_p').on('input', function(){
    $('#wpaicg_editform_top_p_value').text($(this).val());
});
$('#wpaicg_editform_frequency_penalty').on('input', function(){
    $('#wpaicg_editform_frequency_penalty_value').text($(this).val());
});
$('#wpaicg_editform_presence_penalty').on('input', function(){
    $('#wpaicg_editform_presence_penalty_value').text($(this).val());
});
$('#wpaicg_editform_model_settings_save').on('click', function(){
    $('#wpaicg_editform_model_settings_modal').hide();
});

/*************************************************************
 * SHARED UTILS
 *************************************************************/
window.showGlobalMessage = window.showGlobalMessage || function(){};
window.hideShortcodeSnippet = window.hideShortcodeSnippet || function(){};
window.showShortcodeSnippet = window.showShortcodeSnippet || function(){};
window.copyToClipboard = window.copyToClipboard || function(){};
window.replacePlaceholderInText = window.replacePlaceholderInText || function(){};
window.removePlaceholderFromText = window.removePlaceholderFromText || function(){};

/**
 * getFieldSettingsHtml (shared). If not defined, define it:
 */
window.getFieldSettingsHtml = window.getFieldSettingsHtml || function(type, minVal, maxVal, rowsVal, colsVal, optionsArr){
    if(typeof minVal === 'undefined'){ minVal = ''; }
    if(typeof maxVal === 'undefined'){ maxVal = ''; }
    if(typeof rowsVal=== 'undefined'){ rowsVal= ''; }
    if(typeof colsVal=== 'undefined'){ colsVal= ''; }
    if(!Array.isArray(optionsArr)){ optionsArr = []; }

    var html = '<div class="field_settings">';
    if(type==='text' || type==='number'){
        html += '<label>Min: <input type="number" class="min_value" value="'+ minVal +'"/></label>';
        html += '<label>Max: <input type="number" class="max_value" value="'+ maxVal +'"/></label>';
    }
    if(type==='textarea'){
        html += '<label>Min: <input type="number" class="min_value" value="'+ minVal +'"/></label>';
        html += '<label>Max: <input type="number" class="max_value" value="'+ maxVal +'"/></label>';
        html += '<label>Rows: <input type="number" class="rows_value" value="'+ rowsVal +'"/></label>';
        html += '<label>Cols: <input type="number" class="cols_value" value="'+ colsVal +'"/></label>';
    }
    if(type==='checkbox' || type==='radio' || type==='select'){
        html += '<ul class="options_list">';
        optionsArr.forEach(function(opt){
            opt = opt.replace(/"/g, "&quot;");
            html += '<li><input type="text" class="option_value" value="'+ opt +'" /></li>';
        });
        html += '</ul>';
        html += '<button type="button" class="add_option_btn">+ Add Option</button>';
    }
    if(type==='fileupload'){
        html += '<label><?php echo esc_js(__("Allowed File Types (comma-separated):","gpt3-ai-content-generator")); ?><br/>';
        html += '<input type="text" class="file_types" value="txt,csv,doc,docx" /></label>';
    }
    html += '</div>';
    return html;
};

})(jQuery);
</script>