<?php
/**
 * wpaicg_forms_js_create.php
 *
 * Revised to handle new switches for "Header," "Copy Button," and "Feedback Buttons"
 * in the Style tab (Interface container),
 * AND to populate the Icon dropdown from icons.json.
 *
 * Updated to include support for the "fileupload" field type in the drag & drop builder.
 *
 * Also updated so that after the first “Save Changes” in create mode,
 * the admin is redirected back to the main AI Forms list (avoiding extra duplicates).
 *
 * Lastly, we've introduced a "Provider" select for choosing among
 * OpenAI / Google / OpenRouter / Azure. The chosen provider is stored
 * in meta as "wpaicg_model_provider". The model select is populated
 * dynamically based on that provider.
 */

declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Prepare Qdrant collections from the DB; if stored as JSON, decode them to an array.
$qdrant_collections_opt = get_option('wpaicg_qdrant_collections', []);
if ( ! is_array( $qdrant_collections_opt ) ) {
    $decoded_qdrant = json_decode( (string) $qdrant_collections_opt, true );
    if ( is_array( $decoded_qdrant ) ) {
        $qdrant_collections_opt = $decoded_qdrant;
    } else {
        $qdrant_collections_opt = [];
    }
}
$qdrant_default_collection = get_option('wpaicg_qdrant_default_collection', '');

// Prepare Pinecone indexes from the DB; if stored as JSON, decode them to an array.
$pinecone_indexes_opt = get_option('wpaicg_pinecone_indexes', '');
if ( ! is_array( $pinecone_indexes_opt ) ) {
    $decoded_pinecone = json_decode( (string) $pinecone_indexes_opt, true );
    if ( is_array( $decoded_pinecone ) ) {
        $pinecone_indexes_opt = $decoded_pinecone;
    } else {
        $pinecone_indexes_opt = [];
    }
}

// Embedding models from WPAICG_Util
use WPAICG\WPAICG_Util;
$embedding_models = WPAICG_Util::get_instance()->get_embedding_models();

// Also load icons array from wpaicg_forms.php
$wpaicg_icons_file = $wpaicg_plugin_dir . 'admin/data/icons.json';
$wpaicg_icons      = [];
if ( file_exists( $wpaicg_icons_file ) ) {
    $content = file_get_contents( $wpaicg_icons_file );
    $decoded = json_decode( $content, true );
    if ( is_array( $decoded ) ) {
        $wpaicg_icons = $decoded;
    }
}

// For providers
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

$current_google_api_key = get_option('wpaicg_google_api_key','');
$current_google_cse_id  = get_option('wpaicg_google_search_engine_id','');

// Nonce for form creation
$create_nonce = wp_create_nonce('wpaicg_create_form_nonce');
?>
<script>
(function($){
    "use strict";

    ////////////////////////////////////////////////////////////////////////////////
    // 1) Prepare global data for Qdrant, Pinecone, Embeddings, and Icons
    //////////////////////////////////////////////////////////////////////////////
    var qdrantCollections = <?php echo json_encode($qdrant_collections_opt); ?>;
    var qdrantDefault     = <?php echo json_encode($qdrant_default_collection); ?>;
    var pineconeIndexes   = <?php echo json_encode($pinecone_indexes_opt); ?>;
    var embeddingModels   = <?php echo json_encode($embedding_models); ?>;
    var wpaicgIcons       = <?php echo json_encode($wpaicg_icons); ?>;

    // For provider -> models
    var wpaicgDefaultProvider = "<?php echo esc_js($wpaicg_provider); ?>";
    var openaiGpt4      = <?php echo json_encode($gpt4_models); ?>;
    var openaiGpt35     = <?php echo json_encode($gpt35_models); ?>;
    var openaiCustom    = <?php echo json_encode($custom_models); ?>;
    var googleModels    = <?php echo json_encode($google_models); ?>;
    var openrouterGroup = <?php echo json_encode($openrouter_grouped); ?>;
    var azureDeployment = "<?php echo esc_js($azure_deployment); ?>";
    var defaultOpenai   = "<?php echo esc_js($default_openai); ?>";
    var defaultGoogle   = "<?php echo esc_js($default_google); ?>";
    var defaultOpenRouter= "<?php echo esc_js($default_openrouter); ?>";

    // For the Internet toggle
    var globalGoogleApiKey = "<?php echo esc_js($current_google_api_key); ?>";
    var globalGoogleCseId  = "<?php echo esc_js($current_google_cse_id); ?>";

    ////////////////////////////////////////////////////////////////////////////////
    // 2) Show/Hide logic for Embeddings in CREATE form's model settings modal
    //////////////////////////////////////////////////////////////////////////////
    $('#wpaicg_createform_embeddings_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_embeddings').val('yes');
            $('#wpaicg_createform_embeddings_settings_wrapper').show();
        } else {
            $('#wpaicg_createform_embeddings').val('no');
            $('#wpaicg_createform_embeddings_settings_wrapper').hide();
        }
    });

    // Vector DB toggles
    $('#wpaicg_createform_vectordb').on('change', function(){
        var dbVal = $(this).val();
        if(dbVal === 'pinecone'){
            $('#wpaicg_createform_pineconeindexes_wrap').show();
            $('#wpaicg_createform_collections_wrap').hide();
        } else {
            // qdrant
            $('#wpaicg_createform_pineconeindexes_wrap').hide();
            $('#wpaicg_createform_collections_wrap').show();
        }
    });

    // Switch for "Use Default Embedding Model?"
    $('#wpaicg_createform_default_embed_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_use_default_embedding_model').val('yes');
            // Hide custom provider + model
            $('#wpaicg_createform_selected_embedding_provider').prop('disabled', true);
            $('#wpaicg_createform_selected_embedding_model').prop('disabled', true);
        } else {
            $('#wpaicg_createform_use_default_embedding_model').val('no');
            // Show custom provider + model
            $('#wpaicg_createform_selected_embedding_provider').prop('disabled', false);
            $('#wpaicg_createform_selected_embedding_model').prop('disabled', false);
        }
    });

    ////////////////////////////////////////////////////////////////////////////////
    // 2.1) Provider -> Model population
    //////////////////////////////////////////////////////////////////////////////
    // Helper to populate the model dropdown (#wpaicg_createform_engine) 
    // based on selected provider (#wpaicg_createform_provider)
    function populateModelsByProvider(provider) {
        var $modelSelect = $('#wpaicg_createform_engine');
        $modelSelect.empty();

        if(provider === 'OpenAI') {
            // Build GPT3.5, GPT4, and custom
            if(Object.keys(openaiGpt35).length) {
                $modelSelect.append('<optgroup label="GPT 3.5 Models"></optgroup>');
                $.each(openaiGpt35, function(mKey, mLabel){
                    $modelSelect.find('optgroup[label="GPT 3.5 Models"]').append(
                        '<option value="'+ mKey +'">'+ mLabel +'</option>'
                    );
                });
            }
            if(Object.keys(openaiGpt4).length) {
                $modelSelect.append('<optgroup label="GPT 4 Models"></optgroup>');
                $.each(openaiGpt4, function(mKey, mLabel){
                    $modelSelect.find('optgroup[label="GPT 4 Models"]').append(
                        '<option value="'+ mKey +'">'+ mLabel +'</option>'
                    );
                });
            }
            if(Array.isArray(openaiCustom) && openaiCustom.length) {
                $modelSelect.append('<optgroup label="Custom Fine-Tuned"></optgroup>');
                openaiCustom.forEach(function(cmodel){
                    $modelSelect.find('optgroup[label="Custom Fine-Tuned"]').append(
                        '<option value="'+ cmodel +'">'+ cmodel +'</option>'
                    );
                });
            }
            // Set default
            $modelSelect.val(defaultOpenai);

        } else if(provider === 'Google') {
            // googleModels is an array
            if(Array.isArray(googleModels) && googleModels.length){
                googleModels.forEach(function(gm){
                    $modelSelect.append('<option value="'+ gm +'">'+ gm +'</option>');
                });
            }
            // Default
            $modelSelect.val('<?php echo esc_js($default_google); ?>');

        } else if(provider === 'OpenRouter') {
            // openrouterGroup is an object of { providerName: [modelIds...] }
            var keys = Object.keys(openrouterGroup).sort();
            keys.forEach(function(k){
                $modelSelect.append('<optgroup label="'+ k +'"></optgroup>');
                openrouterGroup[k].forEach(function(m){
                    $modelSelect.find('optgroup[label="'+ k +'"]').append(
                        '<option value="'+ m +'">'+ m +'</option>'
                    );
                });
            });
            // default
            $modelSelect.val('<?php echo esc_js($default_openrouter); ?>');

        } else if(provider === 'Azure') {
            // azureDeployment is a single
            if(azureDeployment) {
                $modelSelect.append('<option value="'+ azureDeployment +'">'+ azureDeployment +'</option>');
            } else {
                $modelSelect.append('<option value="">(No Azure deployment found)</option>');
            }
        } else {
            // fallback
            $modelSelect.append('<option value="">Select a model</option>');
        }
    }

    // On change of provider select => populate models
    $('#wpaicg_createform_provider').on('change', function(){
        var selectedProv = $(this).val();
        populateModelsByProvider(selectedProv);
    });


    ////////////////////////////////////////////////////////////////////////////////
    // 3) Populate Pinecone, Qdrant, Embedding Providers, Icons, etc.
    //////////////////////////////////////////////////////////////////////////////
    function populatePineconeIndexesCreate() {
        var $select = $('#wpaicg_createform_pineconeindexes');
        $select.empty();
        if (Array.isArray(pineconeIndexes) && pineconeIndexes.length) {
            pineconeIndexes.forEach(function (idx) {
                var name = idx.name || 'unknown';
                var url = idx.url || ''; 
                $select.append('<option value="' + url + '">' + name + '</option>');
            });
        }
    }

    function populateQdrantCollectionsCreate(){
        var $select = $('#wpaicg_createform_collections');
        $select.empty();
        if(Array.isArray(qdrantCollections) && qdrantCollections.length){
            qdrantCollections.forEach(function(c){
                var cname = c.name || 'unnamed';
                $select.append('<option value="'+ cname +'">'+ cname +'</option>');
            });
        }
        // Set default if available
        if(qdrantDefault){
            $select.val(qdrantDefault);
        }
    }

    function populateEmbeddingProvidersAndModelsCreate(){
        var $provider = $('#wpaicg_createform_selected_embedding_provider');
        var $model    = $('#wpaicg_createform_selected_embedding_model');
        $provider.empty();
        $model.empty();

        // embeddingModels is an object: { "OpenAI": { "modelA":1536, ... }, "Google": {...}, ... }
        $.each(embeddingModels, function(providerName, modelObj){
            $provider.append('<option value="'+providerName+'">'+providerName+'</option>');
        });

        // On provider change => fill models
        $provider.on('change', function(){
            var selectedProv = $(this).val();
            $model.empty();
            if(embeddingModels[selectedProv]){
                $.each(embeddingModels[selectedProv], function(mName, dimension){
                    $model.append('<option value="'+mName+'">'+ mName +' (dim:'+dimension+')</option>');
                });
            }
        });
        // Trigger once to load initial
        $provider.trigger('change');
    }

    // Populate the Icon dropdown with keys from wpaicgIcons
    function populateIconKeysCreate(){
        var $select = $('#wpaicg_createform_icon');
        $select.empty();
        // Add a default "none" option
        $select.append('<option value=""><?php echo esc_js(__('tag','gpt3-ai-content-generator')); ?></option>');
        // Icons are in the form { "tag":"dashicons dashicons-tag", "linkedin":"dashicons dashicons-linkedin", ... }
        $.each(wpaicgIcons, function(iconKey, iconClass){
            $select.append('<option value="'+ iconKey +'">'+ iconKey +'</option>');
        });
    }

    ////////////////////////////////////////////////////////////////////////////////
    // 4) Switches for Header, Copy Button, Feedback Buttons, etc.
    //////////////////////////////////////////////////////////////////////////////
    $('#wpaicg_createform_header_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_header').val('yes');
        } else {
            $('#wpaicg_createform_header').val('no');
        }
    });

    $('#wpaicg_createform_copy_button_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_copy_button').val('yes');
        } else {
            $('#wpaicg_createform_copy_button').val('no');
        }
    });

    $('#wpaicg_createform_ddraft_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_ddraft').val('yes');
        } else {
            $('#wpaicg_createform_ddraft').val('no');
        }
    });

    $('#wpaicg_createform_dclear_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_dclear').val('yes');
        } else {
            $('#wpaicg_createform_dclear').val('no');
        }
    });

    $('#wpaicg_createform_dnotice_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_dnotice').val('yes');
        } else {
            $('#wpaicg_createform_dnotice').val('no');
        }
    });

    $('#wpaicg_createform_ddownload_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_ddownload').val('yes');
        } else {
            $('#wpaicg_createform_ddownload').val('no');
        }
    });

    $('#wpaicg_createform_feedback_buttons_switch').on('change', function(){
        if($(this).is(':checked')){
            $('#wpaicg_createform_feedback_buttons').val('yes');
        } else {
            $('#wpaicg_createform_feedback_buttons').val('no');
        }
    });

    ////////////////////////////////////////////////////////////////////////////////
    // 5) Document Ready: Populate data on initial load
    //////////////////////////////////////////////////////////////////////////////
    $(document).ready(function(){
        populatePineconeIndexesCreate();
        populateQdrantCollectionsCreate();
        populateEmbeddingProvidersAndModelsCreate();
        populateIconKeysCreate();

        // Hide the embeddings advanced settings by default:
        $('#wpaicg_createform_embeddings_settings_wrapper').hide();
        $('#wpaicg_createform_pineconeindexes_wrap').hide();
        $('#wpaicg_createform_collections_wrap').hide();

        // Ensure custom provider fields start disabled if default switch is on
        if($('#wpaicg_createform_default_embed_switch').is(':checked')){
            $('#wpaicg_createform_selected_embedding_provider').prop('disabled', true);
            $('#wpaicg_createform_selected_embedding_model').prop('disabled', true);
        }

        // Populate the main "Model" select based on default provider
        // (Set the provider dropdown to the global wpaicgDefaultProvider,
        // then trigger change to populate models.)
        $('#wpaicg_createform_provider').val(wpaicgDefaultProvider).trigger('change');
    });

    /*************************************************************
     * CREATE NEW FORM BUTTON -> show create container
     * (Triggered externally in wpaicg_forms_js.php)
     *************************************************************/
    var $createContainer = $('#wpaicg_create_container');
    $('#wpaicg_plus_icon').on('click', function(){
        // Hide main stuff
        $('#wpaicg_aiforms_container, #wpaicg_logs_container, #wpaicg_settings_container, #wpaicg_edit_container, #wpaicg_preview_panel').hide();
        // Show create container
        $createContainer.show();

        // Hide top icons except 'Back' + hide "edit" save
        $('#wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_save_edited_form').hide();
        $('#wpaicg_return_main, #wpaicg_create_save_form').show();

        // Label the return button
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Exit Create Mode','gpt3-ai-content-generator')); ?>');

        // By default, show Tab 1
        $('.wpaicg_create_tabs li').removeClass('active').first().addClass('active');
        $('.wpaicg_create_tab_content').removeClass('active').hide();
        $('#wpaicg_create_tab1').addClass('active').show();

        // Reset form creation fields to defaults
        resetCreateFormFields();

        // Show snippet but no actual ID yet
        showShortcodeSnippet('<?php echo esc_js(__("[No ID yet. Save to see snippet]", "gpt3-ai-content-generator")); ?>');
    });

    /*************************************************************
     * BACK/RETURN button -> exit create mode (shared in main JS)
     *************************************************************/
    $('#wpaicg_return_main').on('click', function(){
        var createVisible = $('#wpaicg_create_container').is(':visible');
        // If user is in create mode, confirm
        if(createVisible) {
            if(!confirm('<?php echo esc_js(__('Are you sure? All changes will be lost if you exit Create Mode.','gpt3-ai-content-generator')); ?>')) {
                return; // do not proceed
            }
        }

        // Return to main list
        $('#wpaicg_logs_container, #wpaicg_settings_container, #wpaicg_create_container, #wpaicg_edit_container').hide();
        $('#wpaicg_aiforms_container').show();
        $('#wpaicg_preview_panel').hide();
        $('#wpaicg_forms_grid').show();

        // Restore main icons
        $('#wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon').show();

        // Hide secondary
        $('#wpaicg_return_main, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form, #wpaicg_save_edited_form').hide();
        // Hide snippet
        hideShortcodeSnippet();

        // Reset the return button label
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Back','gpt3-ai-content-generator')); ?>');
    });

    /*************************************************************
     * INLINE TITLE EDITING
     *************************************************************/
    var $titleHidden   = $('#wpaicg_createform_title');
    var $titleDisplay  = $('#wpaicg_create_tab1_title');
    var $titleEditIcon = $('#wpaicg_create_tab1_edit_icon');
    var isEditingTitle = false;

    function truncateTitle(fullTitle){
        if(!fullTitle){
            return '<?php echo esc_js(__('Form Name','gpt3-ai-content-generator')); ?>';
        }
        if(fullTitle.length > 10){
            return fullTitle.substring(0, 10) + '...';
        }
        return fullTitle;
    }
    function updateCreateTabTitleDisplay(newTitle){
        $titleDisplay.text( truncateTitle(newTitle) );
    }
    function startCreateTitleEditing() {
        if(isEditingTitle) return;
        isEditingTitle = true;
        var currentVal = $titleHidden.val().trim();
        $titleDisplay.text(currentVal);
        $titleDisplay.attr('contenteditable','true').focus();
        document.execCommand('selectAll', false, null);
    }
    function finishCreateTitleEditing() {
        if(!isEditingTitle) return;
        var newVal = $titleDisplay.text().trim();
        $titleHidden.val(newVal);
        $titleDisplay.removeAttr('contenteditable');
        isEditingTitle = false;
        updateCreateTabTitleDisplay(newVal);
    }
    function cancelCreateTitleEditing() {
        if(!isEditingTitle) return;
        $titleDisplay.removeAttr('contenteditable');
        var oldVal = $titleHidden.val().trim();
        $titleDisplay.text(truncateTitle(oldVal));
        isEditingTitle = false;
    }

    $titleDisplay.on('dblclick', function(){ startCreateTitleEditing(); });
    $titleEditIcon.on('click', function(){ startCreateTitleEditing(); });
    $titleDisplay.on('keydown', function(e){
        if(e.key === 'Enter'){
            e.preventDefault();
            finishCreateTitleEditing();
        } else if(e.key === 'Escape'){
            e.preventDefault();
            cancelCreateTitleEditing();
        }
    });
    $titleDisplay.on('blur', function(){
        if(isEditingTitle){
            finishCreateTitleEditing();
        }
    });

    /*************************************************************
     * RESETS FOR CREATE MODE
     *************************************************************/
    var createFieldCounter = 1;
    function resetCreateFormFields() {
        $('#wpaicg_create_dropzone').find('.builder_field_item').remove();
        $('#wpaicg_create_dropzone .builder_placeholder').show();
        $('#wpaicg_createform_prompt').val('');
        $('#wpaicg_create_validation_results').hide();
        $('#wpaicg_create_status').hide().text('').css('color','green');
        createFieldCounter = 1;

        // Reset snippet container
        $('#wpaicg_create_snippets').empty();
        $('#wpaicg_create_copied_msg').hide();

        // Defaults
        $('#wpaicg_createform_title').val('New Form');
        updateCreateTabTitleDisplay('New Form');
        $('#wpaicg_createform_category').val('generation');
        $('#wpaicg_createform_description').val('Custom Form');

        // Reset provider & model (will be re-initialized on .trigger('change') later)
        $('#wpaicg_createform_provider').val(wpaicgDefaultProvider);

        // Interface fields
        $('#wpaicg_createform_editor').val('div');
        $('#wpaicg_createform_color').val('#00BFFF');
        $('#wpaicg_createform_bgcolor').val('#f9f9f9');
        // Reset the Icon dropdown to (none)
        $('#wpaicg_createform_icon').val('');

        // Switches => default to yes
        $('#wpaicg_createform_header_switch').prop('checked', true);
        $('#wpaicg_createform_header').val('yes');

        $('#wpaicg_createform_copy_button_switch').prop('checked', true);
        $('#wpaicg_createform_copy_button').val('yes');

        $('#wpaicg_createform_ddraft_switch').prop('checked', true);
        $('#wpaicg_createform_ddraft').val('yes');

        $('#wpaicg_createform_dclear_switch').prop('checked', true);
        $('#wpaicg_createform_dclear').val('yes');

        $('#wpaicg_createform_dnotice_switch').prop('checked', true);
        $('#wpaicg_createform_dnotice').val('yes');

        $('#wpaicg_createform_ddownload_switch').prop('checked', true);
        $('#wpaicg_createform_ddownload').val('yes');

        $('#wpaicg_createform_feedback_buttons_switch').prop('checked', true);
        $('#wpaicg_createform_feedback_buttons').val('yes');

        // Custom text
        $('#wpaicg_createform_copy_text').val('Copy');
        $('#wpaicg_createform_generate_text').val('Generate');
        $('#wpaicg_createform_noanswer_text').val('Number of Answers');
        $('#wpaicg_createform_draft_text').val('Save Draft');
        $('#wpaicg_createform_clear_text').val('Clear');
        $('#wpaicg_createform_stop_text').val('Stop');
        $('#wpaicg_createform_cnotice_text').val('Please register to save your result');
        $('#wpaicg_createform_download_text').val('Download');

        // Advanced model settings
        $('#wpaicg_createform_max_tokens').val('1500');
        $('#wpaicg_createform_top_p').val('1');
        $('#wpaicg_createform_top_p_value').text('1');
        $('#wpaicg_createform_frequency_penalty').val('0');
        $('#wpaicg_createform_frequency_penalty_value').text('0');
        $('#wpaicg_createform_presence_penalty').val('0');
        $('#wpaicg_createform_presence_penalty_value').text('0');
        $('#wpaicg_createform_stop').val('');
        $('#wpaicg_createform_best_of').val('1');

        // Embeddings
        $('#wpaicg_createform_embeddings').val('no');
        $('#wpaicg_createform_embeddings_switch').prop('checked', false);
        $('#wpaicg_createform_embeddings_settings_wrapper').hide();
        $('#wpaicg_createform_vectordb').val('pinecone');
        $('#wpaicg_createform_pineconeindexes_wrap').hide();
        $('#wpaicg_createform_collections_wrap').hide();
        $('#wpaicg_createform_suffix_text').val('Context:');
        $('#wpaicg_createform_suffix_position').val('after');
        $('#wpaicg_createform_use_default_embedding_model').val('yes');
        $('#wpaicg_createform_default_embed_switch').prop('checked', true);
        $('#wpaicg_createform_selected_embedding_provider').prop('disabled', true);
        $('#wpaicg_createform_selected_embedding_model').prop('disabled', true);
        $('#wpaicg_createform_embeddings_limit').val('1');
    }

    /*************************************************************
     * CREATE MODE DRAG & DROP
     *************************************************************/
    var $createDropZone = $('#wpaicg_create_dropzone');
    var $createPlaceholder = $createDropZone.find('.builder_placeholder');
    var createFieldsDraggedItem = null;

    $('.builder_left li').on('dragstart', handleCreateDragStart);
    $createDropZone.on('dragover', handleCreateDragOver);
    $createDropZone.on('drop', handleCreateDrop);

    function handleCreateDragStart(e) {
        e.originalEvent.dataTransfer.setData('text/plain', e.target.getAttribute('data-type'));
    }
    function handleCreateDragOver(e) {
        e.preventDefault();
    }
    function handleCreateDrop(e) {
        e.preventDefault();
        if (createFieldsDraggedItem) {
            return;
        }
        var dataType = e.originalEvent.dataTransfer.getData('text/plain');
        var allowed = ['text','textarea','email','number','checkbox','radio','select','url','fileupload'];
        if (allowed.indexOf(dataType) >= 0) {
            createCreateFieldItem(dataType);
        }
    }

    function createCreateFieldItem(dataType) {
        var newID = 'id' + createFieldCounter;
        createFieldCounter++;

        var domID = 'create-field-' + Date.now();
        var settingsHtml = getFieldSettingsHtml(dataType);
        var $fieldEl = $(
            '<div class="builder_field_item" draggable="true" data-type="'+dataType+'" id="'+domID+'">'+
                '<span class="remove_field">&times;</span>'+
                '<span class="builder_settings_icon">&#9881;</span>'+
                '<label><?php echo esc_js(__('Label','gpt3-ai-content-generator')); ?>:'+
                    '<input type="text" class="builder_label_input" placeholder="<?php echo esc_attr__('Field Label','gpt3-ai-content-generator'); ?>" />'+
                '</label>'+
                '<label><?php echo esc_js(__('ID','gpt3-ai-content-generator')); ?>:'+
                    '<input type="text" class="builder_id_input" value="'+newID+'" placeholder="<?php echo esc_attr__('Short ID','gpt3-ai-content-generator'); ?>" />'+
                '</label>'+
                settingsHtml+
                '<small style="display:block; color:#777;"><?php echo esc_js(__('Type','gpt3-ai-content-generator')); ?>: '+dataType+'</small>'+
            '</div>'
        );

        $createDropZone.append($fieldEl);
        $createPlaceholder.hide();
        initCreateFieldItemDrag($fieldEl);

        // Add snippet for this field
        addCreateSnippet(newID, domID);

        // Listen for ID changes
        var $idInput = $fieldEl.find('.builder_id_input');
        $idInput.data('oldid', newID);
        $idInput.on('input', function(){
            var oldID = $(this).data('oldid');
            var newIDVal = $(this).val().trim();
            if(!newIDVal){return;}
            replacePlaceholderInText($('#wpaicg_createform_prompt'), oldID, newIDVal);
            updateCreateSnippet(oldID, newIDVal, domID);
            $(this).data('oldid', newIDVal);
        });

        // Re-check if we can enable "Validate My Prompt"
        updateCreateValidateButtonState();
    }

    function initCreateFieldItemDrag($el){
        $el.on('dragstart', function(ev){
            ev.originalEvent.dataTransfer.setData('text/plain', $(this).attr('id'));
            createFieldsDraggedItem = this;
        });
        $el.on('dragend', function(){
            createFieldsDraggedItem = null;
        });
    }

    $createDropZone.on('dragover', '.builder_field_item', function(e){
        e.preventDefault();
        var bounding = this.getBoundingClientRect();
        var offset   = bounding.y + (bounding.height / 2);
        if(e.originalEvent.clientY - offset > 0) {
            $(this).addClass('drag-bottom').removeClass('drag-top');
        } else {
            $(this).addClass('drag-top').removeClass('drag-bottom');
        }
    });
    $createDropZone.on('dragleave', '.builder_field_item', function(){
        $(this).removeClass('drag-top drag-bottom');
    });
    $createDropZone.on('drop', '.builder_field_item', function(e){
        e.preventDefault();
        if(!createFieldsDraggedItem) return;
        if($(this).hasClass('drag-top')) {
            $(this).before(createFieldsDraggedItem);
        } else {
            $(this).after(createFieldsDraggedItem);
        }
        $(this).removeClass('drag-top drag-bottom');
    });
    $createDropZone.on('click', '.remove_field', function(){
        var $parent = $(this).parent();
        var oldID = $parent.find('.builder_id_input').val().trim();
        removePlaceholderFromText($('#wpaicg_createform_prompt'), oldID);
        removeCreateSnippet(oldID, $parent.attr('id'));
        $parent.remove();
        if($createDropZone.find('.builder_field_item').length === 0){
            $createPlaceholder.show();
        }
        updateCreateValidateButtonState();
    });
    $createDropZone.on('click', '.builder_settings_icon', function(){
        $(this).siblings('.field_settings').slideToggle(150);
    });
    $createDropZone.on('click', '.add_option_btn', function(){
        var $ul = $(this).siblings('.options_list');
        if($ul.length) {
            $ul.append('<li><input type="text" class="option_value" value="" /></li>');
        }
    });

    window.getFieldSettingsHtml = function(type, minVal, maxVal, rowsVal, colsVal, optionsArr) {
        if(typeof minVal === 'undefined')  { minVal = ''; }
        if(typeof maxVal === 'undefined')  { maxVal = ''; }
        if(typeof rowsVal === 'undefined') { rowsVal = ''; }
        if(typeof colsVal === 'undefined') { colsVal = ''; }
        if(!Array.isArray(optionsArr))     { optionsArr = []; }

        var html = '<div class="field_settings">';

        // text/number
        if(type === 'text' || type === 'number') {
            html += '<label>Min: <input type="number" class="min_value" value="'+ minVal +'"/></label>';
            html += '<label>Max: <input type="number" class="max_value" value="'+ maxVal +'"/></label>';
        }
        // textarea
        if(type === 'textarea') {
            html += '<label>Min: <input type="number" class="min_value" value="'+ minVal +'"/></label>';
            html += '<label>Max: <input type="number" class="max_value" value="'+ maxVal +'"/></label>';
            html += '<label>Rows: <input type="number" class="rows_value" value="'+ rowsVal +'"/></label>';
            html += '<label>Cols: <input type="number" class="cols_value" value="'+ colsVal +'"/></label>';
        }
        // checkbox / radio / select
        if(type === 'checkbox' || type === 'radio' || type === 'select') {
            html += '<ul class="options_list">';
            optionsArr.forEach(function(opt){
                opt = opt.replace(/"/g, "&quot;");
                html += '<li><input type="text" class="option_value" value="'+ opt +'" /></li>';
            });
            html += '</ul>';
            html += '<button type="button" class="add_option_btn">+ Add Option</button>';
        }
        // NEW: fileupload
        if(type === 'fileupload') {
            html += '<label><?php echo esc_js(__("Allowed File Types (comma-separated):","gpt3-ai-content-generator")); ?><br/>';
            html += '<input type="text" class="file_types" value="txt,csv,doc,docx" /></label>';
        }

        html += '</div>';
        return html;
    };

    /*************************************************************
     * SNIPPETS HANDLING (CREATE)
     *************************************************************/
    function addCreateSnippet(idVal, domID){
        var snippet = '<span class="wpaicg_snippet" data-dom="'+domID+'" data-id="'+idVal+'">{'+idVal+'}</span>';
        $('#wpaicg_create_snippets').append(snippet);
    }
    function updateCreateSnippet(oldID, newID, domID){
        var $snip = $('#wpaicg_create_snippets').find('[data-dom="'+domID+'"]');
        if($snip.length){
            $snip.attr('data-id', newID).data('id', newID).text('{'+newID+'}');
        }
    }
    function removeCreateSnippet(idVal, domID){
        $('#wpaicg_create_snippets').find('[data-dom="'+domID+'"]').remove();
    }

    $('#wpaicg_create_snippets').on('click', '.wpaicg_snippet', function(){
        var snippetID = $(this).data('id');
        var toCopy = '{' + snippetID + '}';
        copyToClipboard(toCopy, $('#wpaicg_create_copied_msg'));
    });

    /*************************************************************
     * VALIDATION LOGIC
     *************************************************************/
    var createPromptIsValid = false;

    function updateCreateValidateButtonState() {
        var fieldsCount = $createDropZone.find('.builder_field_item').length;
        var promptValue = $('#wpaicg_createform_prompt').val().trim();
        if(fieldsCount > 0 && promptValue.length > 0) {
            $('#wpaicg_create_validate_prompt').removeAttr('disabled');
        } else {
            $('#wpaicg_create_validate_prompt').attr('disabled','disabled');
        }
    }

    $('#wpaicg_createform_prompt').on('input', updateCreateValidateButtonState);

    function wpaicg_create_validatePrompt() {
        var $fields   = $createDropZone.find('.builder_field_item');
        var fieldIDs  = [];
        var missingID = false;

        $fields.each(function(){
            var idVal = $(this).find('.builder_id_input').val().trim();
            if(!idVal){ missingID = true; }
            fieldIDs.push(idVal);
        });

        var promptValue  = $('#wpaicg_createform_prompt').val().trim();
        var matches      = promptValue.match(/\{([^}]+)\}/g) || [];
        var placeholderIDs = matches.map(function(m) {
            return m.replace(/[{}]/g, '');
        });

        $('#wpaicg_create_validation_results').show();

        if(missingID) {
            $('#wpaicg_create_count_result')
                .css('color','red')
                .text('✘ Some fields have no ID. Fill all IDs first.');
            $('#wpaicg_create_order_result').css('color','red').text('✘ Not checked');
            $('#wpaicg_create_existence_result').css('color','red').text('✘ Not checked');
            createPromptIsValid = false;
            return;
        }

        // 1) Count
        var countPass = (placeholderIDs.length === fieldIDs.length);
        if(countPass){
            $('#wpaicg_create_count_result')
                .css('color','green')
                .text('✔ ' + placeholderIDs.length + ' placeholder(s) found');
        } else {
            $('#wpaicg_create_count_result')
                .css('color','red')
                .text('✘ Expected ' + fieldIDs.length + ', found ' + placeholderIDs.length);
        }

        // 2) Order
        var orderPass = true;
        if(countPass) {
            for(var i=0; i<fieldIDs.length; i++){
                if(fieldIDs[i] !== placeholderIDs[i]){
                    orderPass = false;
                    break;
                }
            }
        } else {
            orderPass = false;
        }
        if(orderPass){
            $('#wpaicg_create_order_result').css('color','green').text('✔ Order matches');
        } else {
            $('#wpaicg_create_order_result').css('color','red').text('✘ Order mismatch');
        }

        // at the moment we will only validate existence
        // 3) Existence
        var missingIDs = [];
        for(var j=0; j<fieldIDs.length; j++){
            if(!promptValue.includes('{'+fieldIDs[j]+'}')){
                missingIDs.push(fieldIDs[j]);
            }
        }
        if(missingIDs.length === 0) {
            $('#wpaicg_create_existence_result')
                .css('color','green')
                .text('✔ All field IDs exist in prompt');
        } else {
            $('#wpaicg_create_existence_result')
                .css('color','red')
                .text('✘ Missing placeholder(s): ' + missingIDs.join(', '));
        }

        createPromptIsValid = (missingIDs.length === 0);
    }
    $('#wpaicg_create_validate_prompt').on('click', wpaicg_create_validatePrompt);

    /*************************************************************
     * INTERNET BROWSING TOGGLE
     *************************************************************/
    $('#wpaicg_createform_internet_toggle').on('click', function(){
        var currentVal = $('#wpaicg_createform_internet').val();
        // if user has no Google API key or no CSE ID, show alert
        if(!globalGoogleApiKey || !globalGoogleCseId){
            alert("Please configure your Google API Key and Search Engine ID under AI Form Settings first.");
            return;
        }
        // Otherwise, we can toggle
        if(currentVal === 'no'){
            // Turn on
            $('#wpaicg_createform_internet').val('yes');
            // Make icon blue
            $(this).css('color','#2271b1');
        } else {
            // Turn off
            $('#wpaicg_createform_internet').val('no');
            // Return icon to grey
            $(this).css('color','#808080');
        }
    });

    /*************************************************************
     * SAVE NEW FORM
     *************************************************************/
    $('#wpaicg_create_save_form').on('click', function(){
        if(!createPromptIsValid){
            showGlobalMessage('error','<?php echo esc_js(__('Your prompt did not pass validation.','gpt3-ai-content-generator')); ?>');
            return;
        }
        var title       = $('#wpaicg_createform_title').val().trim();
        var description = $('#wpaicg_createform_description').val().trim();
        var prompt      = $('#wpaicg_createform_prompt').val().trim();
        var provider    = $('#wpaicg_createform_provider').val();
        var engine      = $('#wpaicg_createform_engine').val();
        var $fields     = $('#wpaicg_create_dropzone').find('.builder_field_item');

        if(!title || !description || !prompt){
            showGlobalMessage('error','<?php echo esc_js(__('Please fill in title, description, and prompt','gpt3-ai-content-generator')); ?>');
            return;
        }

        var fieldsData = [];
        var missingID  = false;
        $fields.each(function(idx, el){
            var $el     = $(el);
            var type    = $el.data('type');
            var label   = $el.find('.builder_label_input').val().trim();
            var fieldID = $el.find('.builder_id_input').val().trim();
            if(!label || !fieldID){
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

            // For fileupload, we also read the "file_types" if present
            var fileTypes = "";
            if(type === 'fileupload'){
                var $fTypesInput = $el.find('.file_types');
                if($fTypesInput.length){
                    fileTypes = $fTypesInput.val().trim();
                }
            }

            fieldsData.push({
                type: type,
                label: label ? label : 'Field '+(idx+1),
                id: fieldID,
                min: minVal,
                max: maxVal,
                rows: rowsVal,
                cols: colsVal,
                options: optionValues.join('|'),
                file_types: fileTypes
            });
        });
        if(missingID){
            showGlobalMessage('error','<?php echo esc_js(__('Each field must have a Label and ID','gpt3-ai-content-generator')); ?>');
            return;
        }

        var interfaceData = {
            'wpaicg_form_editor':           $('#wpaicg_createform_editor').val().trim(),
            'wpaicg_form_category':         $('#wpaicg_createform_category').val().trim(),
            'wpaicg_form_color':            $('#wpaicg_createform_color').val().trim(),
            'wpaicg_form_icon':             $('#wpaicg_createform_icon').val().trim(),
            'wpaicg_form_header':           $('#wpaicg_createform_header').val(),
            'wpaicg_form_copy_button':      $('#wpaicg_createform_copy_button').val(),
            'wpaicg_form_copy_text':        $('#wpaicg_createform_copy_text').val().trim(),
            'wpaicg_form_feedback_buttons': $('#wpaicg_createform_feedback_buttons').val(),
            'wpaicg_form_generate_text':    $('#wpaicg_createform_generate_text').val().trim(),
            'wpaicg_form_noanswer_text':    $('#wpaicg_createform_noanswer_text').val().trim(),
            'wpaicg_form_draft_text':       $('#wpaicg_createform_draft_text').val().trim(),
            'wpaicg_createform_clear_text': $('#wpaicg_createform_clear_text').val().trim(),
            'wpaicg_form_stop_text':        $('#wpaicg_createform_stop_text').val().trim(),
            'wpaicg_form_cnotice_text':     $('#wpaicg_createform_cnotice_text').val().trim(),
            'wpaicg_form_download_text':    $('#wpaicg_createform_download_text').val().trim(),
            'wpaicg_form_bgcolor':          $('#wpaicg_createform_bgcolor').val().trim(),
            'wpaicg_form_ddraft':           $('#wpaicg_createform_ddraft').val().trim(),
            'wpaicg_form_dclear':           $('#wpaicg_createform_dclear').val().trim(),
            'wpaicg_form_dnotice':          $('#wpaicg_createform_dnotice').val().trim(),
            'wpaicg_form_ddownload':        $('#wpaicg_createform_ddownload').val().trim()
        };

        // Model settings
        var formSettingsData = {
            max_tokens: parseInt($('#wpaicg_createform_max_tokens').val()) || 1500,
            top_p: parseFloat($('#wpaicg_createform_top_p').val()) || 1,
            best_of: parseInt($('#wpaicg_createform_best_of').val()) || 1,
            frequency_penalty: parseFloat($('#wpaicg_createform_frequency_penalty').val()) || 0,
            presence_penalty: parseFloat($('#wpaicg_createform_presence_penalty').val()) || 0,
            stop: $('#wpaicg_createform_stop').val().trim()
        };

        // Embedding settings
        var embeddingSettings = {
            use_embeddings: $('#wpaicg_createform_embeddings').val(),
            vectordb: $('#wpaicg_createform_vectordb').val(),
            collections: $('#wpaicg_createform_collections').val(),
            pineconeindexes: $('#wpaicg_createform_pineconeindexes').val(),
            suffix_text: $('#wpaicg_createform_suffix_text').val(),
            suffix_position: $('#wpaicg_createform_suffix_position').val(),
            use_default_embedding_model: $('#wpaicg_createform_use_default_embedding_model').val(),
            selected_embedding_provider: $('#wpaicg_createform_selected_embedding_provider').val(),
            selected_embedding_model: $('#wpaicg_createform_selected_embedding_model').val(),
            embeddings_limit: $('#wpaicg_createform_embeddings_limit').val()
        };

        // NEW: gather internet toggled value
        var internetValue = $('#wpaicg_createform_internet').val();  // yes/no

        var $status = $('#wpaicg_create_status');
        $status.hide().css('color','green');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_create_new_form',
                nonce: '<?php echo esc_js($create_nonce); ?>',
                title: title,
                description: description,
                prompt: prompt,
                // New: provider => wpaicg_model_provider
                provider: provider,
                engine: engine,
                fields: JSON.stringify(fieldsData),
                interface: interfaceData,
                model_settings: formSettingsData,
                embedding_settings: embeddingSettings,
                internet_browsing: internetValue
            },
            success: function(response){
                if(response.success){
                    // Show success msg
                    showGlobalMessage('success', response.data.message);
                    $status.text(response.data.message).show();
                    
                    // Now that the form is created, redirect to the main list
                    setTimeout(function(){
                        window.location.href = 'admin.php?page=wpaicg_forms';
                    }, 1500);

                } else {
                    showGlobalMessage('error', response.data.message || 'Error creating form');
                    $status.css('color','red').text(response.data.message || 'Error').show();
                }
            },
            error: function(){
                showGlobalMessage('error','<?php echo esc_js(__('Ajax error','gpt3-ai-content-generator')); ?>');
                $status.css('color','red')
                    .text('<?php echo esc_js(__('Ajax error','gpt3-ai-content-generator')); ?>').show();
            }
        });
    });

    /*************************************************************
     * CREATE TABS NAVIGATION
     *************************************************************/
    $(document).on('click', '.wpaicg_create_tabs li', function(){
        var tab = $(this).data('tab');
        $('.wpaicg_create_tabs li').removeClass('active');
        $(this).addClass('active');
        $('.wpaicg_create_tab_content').removeClass('active').hide();
        $('#'+tab).addClass('active').show();
    });

    /*************************************************************
     * MODEL SETTINGS ICON + MODAL
     *************************************************************/
    // Show the modal
    $('#wpaicg_createform_model_settings_icon').on('click', function(e){
        e.preventDefault();
        $('#wpaicg_createform_model_settings_modal').show();

        // If embeddings switch is "yes", show wrapper. Otherwise hide
        var useEmbed = $('#wpaicg_createform_embeddings').val();
        if(useEmbed === 'yes'){
            $('#wpaicg_createform_embeddings_settings_wrapper').show();
        } else {
            $('#wpaicg_createform_embeddings_settings_wrapper').hide();
        }
        $('#wpaicg_createform_vectordb').trigger('change');

        // If default embedding model is "no", enable provider & model
        var useDef = $('#wpaicg_createform_use_default_embedding_model').val();
        if(useDef === 'yes'){
            $('#wpaicg_createform_selected_embedding_provider').prop('disabled', true);
            $('#wpaicg_createform_selected_embedding_model').prop('disabled', true);
        } else {
            $('#wpaicg_createform_selected_embedding_provider').prop('disabled', false);
            $('#wpaicg_createform_selected_embedding_model').prop('disabled', false);
        }
    });

    // Close modal
    $('#wpaicg_createform_model_settings_close').on('click', function(){
        $('#wpaicg_createform_model_settings_modal').hide();
    });

    // Range inputs => show current value
    $('#wpaicg_createform_top_p').on('input', function(){
        $('#wpaicg_createform_top_p_value').text($(this).val());
    });
    $('#wpaicg_createform_frequency_penalty').on('input', function(){
        $('#wpaicg_createform_frequency_penalty_value').text($(this).val());
    });
    $('#wpaicg_createform_presence_penalty').on('input', function(){
        $('#wpaicg_createform_presence_penalty_value').text($(this).val());
    });

    // Save button inside modal
    $('#wpaicg_createform_model_settings_save').on('click', function(){
        $('#wpaicg_createform_model_settings_modal').hide();
    });

})(jQuery);
</script>