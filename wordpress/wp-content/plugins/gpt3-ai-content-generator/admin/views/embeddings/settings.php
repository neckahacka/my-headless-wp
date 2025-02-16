<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

// Retrieving the provider option
$wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');

$default_qdrant_collection = get_option('wpaicg_qdrant_default_collection', '');
// Retrieve the current default vector DB setting
$wpaicg_vector_db_provider = get_option('wpaicg_vector_db_provider', 'pinecone');
$wpaicg_qdrant_collections = get_option('wpaicg_qdrant_collections', []);
$wpaicg_pinecone_api = get_option('wpaicg_pinecone_api','');
$wpaicg_pinecone_environment = get_option('wpaicg_pinecone_environment','');
$wpaicg_builder_types = get_option('wpaicg_builder_types',[]);
$wpaicg_builder_enable = get_option('wpaicg_builder_enable','');
$wpaicg_instant_embedding = get_option('wpaicg_instant_embedding','yes');
$wpaicg_pinecone_indexes = get_option('wpaicg_pinecone_indexes','');
$wpaicg_pinecone_indexes = empty($wpaicg_pinecone_indexes) ? array() : json_decode($wpaicg_pinecone_indexes,true);

if($wpaicg_embeddings_settings_updated){
    ?>
    <div class="wpaicg-embedding-save-message">
        <p><?php echo esc_html__('Records updated successfully','gpt3-ai-content-generator')?></p>
    </div>
    <p></p>
    <?php
}
?>
<style>
    /* Modal Style */
    #wpaicg_emb_modal_overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 1000;
    }

    .wpaicg_emb_modal {
        background: #fff;
        border: 1px solid #ddd;
        border-radius: 4px;
        box-shadow: 0 1px 15px rgba(0, 0, 0, 0.2);
        position: fixed;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -50%);
        z-index: 1001;
        width: 40%;
        min-width: 300px;
        max-width: 500px;
        padding: 20px;
        box-sizing: border-box;
    }

    .wpaicg_emb_modal h2 {
        font-size: 20px;
        margin-top: 0;
    }

    .wpaicg_emb_modal_content p {
        font-size: 14px;
        line-height: 1.5;
        color: #333;
    }

    .wpaicg_assign_footer_emb {
        text-align: right;
        margin-top: 20px;
    }

    .wpaicg_assign_footer_emb button {
        margin-left: 10px;
    }

    /* Responsive adjustments */
    @media screen and (max-width: 600px) {
        .wpaicg_emb_modal {
            width: 80%;
        }
    }

</style>
<style>
    .wpaicg_modal {
        width: 600px;
        left: calc(50% - 300px);
        height: 40%;
    }
    .wpaicg_modal_content{
        height: calc(100% - 103px);
        overflow-y: auto;
    }
    .wpaicg_assign_footer{
        position: absolute;
        bottom: 0;
        display: flex;
        justify-content: space-between;
        width: calc(100% - 20px);
        align-items: center;
        border-top: 1px solid #ccc;
        left: 0;
        padding: 3px 10px;
    }
</style>
<form action="" method="post">
    <?php
    wp_nonce_field('wpaicg_embeddings_settings');
    ?>
    <h1><?php echo esc_html__('Vector Database','gpt3-ai-content-generator')?></h1>
    <div class="nice-form-group">
        <label><?php echo esc_html__('Default Vector DB','gpt3-ai-content-generator')?></label>
        <select style="width: 50%;" name="wpaicg_vector_db_provider">
            <option value="pinecone" <?php selected($wpaicg_vector_db_provider, 'pinecone'); ?>>Pinecone</option>
            <option value="qdrant" <?php selected($wpaicg_vector_db_provider, 'qdrant'); ?>>Qdrant</option>
        </select>
    </div>
    <div class="nice-form-group">
    </div>
    <!-- Pinecone Settings -->
    <div id="wpaicg_pinecone_settings">
        <div class="nice-form-group">
            <label><?php echo esc_html__('Pinecone API Key','gpt3-ai-content-generator')?></label>
            <input type="text" style="width: 50%;" class="wpaicg_pinecone_api" name="wpaicg_pinecone_api" value="<?php echo esc_attr($wpaicg_pinecone_api)?>">
            <a href="https://www.pinecone.io" target="_blank" style="margin-left: 10px;"><?php echo esc_html__('Get your API key', 'gpt3-ai-content-generator'); ?></a>
        </div>
        <div class="nice-form-group">
            <label><?php echo esc_html__('Pinecone Index','gpt3-ai-content-generator')?></label>
            <select style="width: 50%;" class="wpaicg_pinecone_environment" name="wpaicg_pinecone_environment" old-value="<?php echo esc_attr($wpaicg_pinecone_environment); ?>">
                <option value=""><?php echo esc_html__('Select Index', 'gpt3-ai-content-generator'); ?></option>
                <?php
                $wpaicg_pinecone_indexes = get_option('wpaicg_pinecone_indexes', '[]');
                $wpaicg_pinecone_indexes = json_decode($wpaicg_pinecone_indexes, true);
                foreach ($wpaicg_pinecone_indexes as $wpaicg_pinecone_index) {
                    if (isset($wpaicg_pinecone_index['dimension'])) {
                        $dimensionText = ' (' . esc_html($wpaicg_pinecone_index['dimension']) . ')';
                    } else {
                        $dimensionText = ''; // or perhaps default to '(Unknown Dimension)' if preferred
                    }
                    $displayText = esc_html($wpaicg_pinecone_index['name']) . $dimensionText;
                    echo '<option' . ($wpaicg_pinecone_environment == $wpaicg_pinecone_index['url'] ? ' selected' : '') . ' value="' . esc_html($wpaicg_pinecone_index['url']) . '">' . $displayText . '</option>';
                }
                ?>
            </select>
            <button type="button" style="padding-top: 0.5em; padding-bottom: 0.5em;" class="button button-primary wpaicg_pinecone_indexes"><?php echo esc_html__('Sync Indexes','gpt3-ai-content-generator')?></button>
        </div>
    </div>
    <!-- Qdrant Settings -->
    <div id="wpaicg_qdrant_settings" style="display:none;">
        <div class="nice-form-group">
            <label><?php echo esc_html__('Qdrant API Key','gpt3-ai-content-generator')?></label>
            <input type="text" style="width: 50%;" name="wpaicg_qdrant_api_key" value="<?php echo esc_attr(get_option('wpaicg_qdrant_api_key', '')); ?>">
            <a href="https://qdrant.tech" target="_blank" style="margin-left: 10px;"><?php echo esc_html__('Get your API key', 'gpt3-ai-content-generator'); ?></a>
        </div>
        <div class="nice-form-group">
            <label><?php echo esc_html__('Qdrant Endpoint','gpt3-ai-content-generator')?></label>
            <input type="text" style="width: 50%;" name="wpaicg_qdrant_endpoint" value="<?php echo esc_attr(get_option('wpaicg_qdrant_endpoint', '')); ?>">
            <input type="checkbox" style="margin: 1em 0.5em;" name="wpaicg_qdrant_use_port" <?php checked(get_option('wpaicg_qdrant_use_port', 'yes'), 'yes'); ?> value="yes">
            <label><?php echo esc_html__('Use Default Port','gpt3-ai-content-generator')?></label>
        </div>
        <div class="nice-form-group">
            <label><?php echo esc_html__('Qdrant Collections','gpt3-ai-content-generator')?></label>
            <select style="width: 50%;" class="wpaicg_qdrant_collections_dropdown" name="wpaicg_qdrant_collections">
                <!-- PHP will dynamically fill this select based on the options retrieved -->
                <?php
                $default_qdrant_collection = get_option('wpaicg_qdrant_default_collection', '');
                $wpaicg_qdrant_collections = get_option('wpaicg_qdrant_collections', []);  // Make sure this is decoded correctly if stored as JSON
                foreach ($wpaicg_qdrant_collections as $collection):
                    if (is_array($collection) && isset($collection['name'])) {
                        // New structure with name and dimension
                        $name = $collection['name'];
                        $dimension = isset($collection['dimension']) ? ' (' . esc_html($collection['dimension']) . ')' : ' (Dimension missing)';
                        $selected = ($name === $default_qdrant_collection) ? ' selected' : '';
                        echo '<option value="'.esc_attr($name).'"'.$selected.'>'.esc_html($name) . $dimension .'</option>';
                    } else {
                        // Old structure where the collection itself is a string
                        $selected = ($collection === $default_qdrant_collection) ? ' selected' : '';
                        echo '<option value="'.esc_attr($collection).'"'.$selected.'>'.esc_html($collection).'</option>';
                    }
                endforeach;
                ?>
            </select>
            <button type="button" style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button button-primary wpaicg_sync_qdrant_collections"><?php echo esc_html__('Sync Collections','gpt3-ai-content-generator')?></button>
            <button type="button" style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button wpaicg_create_new_collection_btn"><?php echo esc_html__('Create New','gpt3-ai-content-generator')?></button>
        </div>
        <div class="wpaicg_new_collection_input" style="display:none; margin-top: 20px;">
            <div class="nice-form-group">
                <input type="text" style="width: 50%;" class="wpaicg_new_collection_name" placeholder="<?php echo esc_html__('Enter collection name','gpt3-ai-content-generator')?>">
                <input type="number" style="width: 20%;" class="wpaicg_new_collection_dimension" value="1536" placeholder="Dimension (e.g., 1536)">
                <button type="button" style="padding-top: 0.5em;padding-bottom: 0.5em;width: 12%;" class="button button-primary wpaicg_submit_new_collection"><?php echo esc_html__('Save','gpt3-ai-content-generator')?></button>
            </div>
        </div>
    </div>
    <p></p>
    <h1><?php echo esc_html__('Embedding Model', 'gpt3-ai-content-generator'); ?></h1>
    <div class="nice-form-group">
        <label><?php echo esc_html__('Embedding Model', 'gpt3-ai-content-generator'); ?></label>
        <select name="wpaicg_embedding_model" style="width: 50%;">
            <?php
            // Try to get the main embedding model option
            $main_embedding_model = get_option('wpaicg_main_embedding_model', '');

            // Retrieve the full list of models from the utility class for all providers
            $embedding_models = \WPAICG\WPAICG_Util::get_instance()->get_embedding_models();

            // Loop through all models grouped by providers
            foreach ($embedding_models as $provider => $models) {
                echo "<optgroup label='" . esc_attr($provider) . "'>"; // Group by provider
                foreach ($models as $model_key => $model_desc) {
                    $value = esc_attr($provider . ':' . $model_key);
                    $selected = ($value === $main_embedding_model) ? ' selected' : '';
                    echo "<option value='{$value}'{$selected}>"
                        . esc_html("{$model_key} ({$model_desc})", 'gpt3-ai-content-generator')
                        . "</option>";
                }
                echo "</optgroup>";
            }
            ?>
        </select>
    </div>
    <p></p>
    <h1><?php echo esc_html__('Instant Embedding','gpt3-ai-content-generator')?></h1>
    <p><?php echo esc_html__('Use this feature to feed your WordPress content to the vector database with one click. Go to Posts -> All Posts , select your content, and click Instant Embedding button to save your content in the vector database.','gpt3-ai-content-generator')?></p>
    <div class="nice-form-group">
        <input <?php echo $wpaicg_instant_embedding == 'yes' ? ' checked':'';?> type="checkbox" name="wpaicg_instant_embedding" value="yes">
        <label><?php echo esc_html__('Enable Instant Embedding','gpt3-ai-content-generator')?></label>
    </div>
    <p></p>
    <h1><?php echo esc_html__('Auto-Scan','gpt3-ai-content-generator')?></h1>
    <p><?php echo esc_html__('Use Auto-Scan to automatically feed your WordPress content to the vector database. With Auto-Scan, you can set up a cron job to automatically add new or updated content to the vector database, keeping your index up to date without manual intervention.','gpt3-ai-content-generator')?></p>
    <div class="nice-form-group">
        <label><?php echo esc_html__('Enable Auto-Scan','gpt3-ai-content-generator')?></label>
        <select name="wpaicg_builder_enable" style="width: 50%;">
            <option value=""><?php echo esc_html__('No','gpt3-ai-content-generator')?></option>
            <option <?php echo esc_html($wpaicg_builder_enable) == 'yes' ? ' selected':'';?> value="yes"><?php echo esc_html__('Yes','gpt3-ai-content-generator')?></option>
        </select>
    </div>
    <fieldset class="nice-form-group">
        <legend><?php echo esc_html__('Post Types to Scan','gpt3-ai-content-generator')?></legend>
        <div class="nice-form-group">
            <input type="checkbox" id="post" name="wpaicg_builder_types[]" value="post" <?php echo in_array('post', $wpaicg_builder_types) ? 'checked' : ''; ?> />
            <label for="post"><?php echo esc_html__('Posts', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <div class="nice-form-group">
            <input type="checkbox" id="page" name="wpaicg_builder_types[]" value="page" <?php echo in_array('page', $wpaicg_builder_types) ? 'checked' : ''; ?> />
            <label for="page"><?php echo esc_html__('Pages', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <?php if(class_exists('WooCommerce')): ?>
        <div class="nice-form-group">
            <input type="checkbox" id="product" name="wpaicg_builder_types[]" value="product" <?php echo in_array('product', $wpaicg_builder_types) ? 'checked' : ''; ?> />
            <label for="product"><?php echo esc_html__('Products', 'gpt3-ai-content-generator'); ?></label>
        </div>
        <?php endif; ?>
        <?php
        if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()){
            include WPAICG_LIBS_DIR.'views/builder/custom_post_type.php';
        }
        else{
            $wpaicg_all_post_types = get_post_types(array(
                'public'   => true,
                '_builtin' => false,
            ),'objects');
            $wpaicg_custom_types = [];
            foreach($wpaicg_all_post_types as $key=>$all_post_type) {
                if ($key != 'product') {
                    $wpaicg_custom_types[$key] = (array)$all_post_type;
                }
            }
            if(count($wpaicg_custom_types)){
                foreach($wpaicg_custom_types as $key=>$wpaicg_custom_type){
                    ?>
                    <div class="nice-form-group">
                        <input disabled type="checkbox" >&nbsp;<?php echo esc_html($wpaicg_custom_type['label'])?>
                        <input class="wpaicg_builder_custom_<?php echo esc_html($key)?>" type="hidden">
                        <a disabled href="javascript:void(0)">[<?php echo esc_html__('Select Fields','gpt3-ai-content-generator')?>]</a>
                        <a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>" class="pro-feature-label"><?php echo esc_html__('Pro','gpt3-ai-content-generator')?></a>
                    </div>
                    <?php
                }
            }
        }
        ?>
    </fieldset>
    <p></p>

    <div class="nice-form-group">
        <button class="button button-primary" name="wpaicg_save_builder_settings"><?php echo esc_html__('Save','gpt3-ai-content-generator')?></button>
    </div>
</form>
<!-- Modal HTML -->
<div id="embeddingModelChangeModal" style="display:none;">
    <div class="wpaicg_emb_modal">
        <div class="wpaicg_emb_modal_content">
            <p><strong><?php echo esc_html__('Important Notice', 'gpt3-ai-content-generator'); ?></strong></p>
            <p><?php echo esc_html__('Changing the embeddings model will require you to reindex all your content and delete old indexes. Make sure to reindex all your content after the model change.', 'gpt3-ai-content-generator'); ?></p>
            <p></p> <!-- Dimension and reindexing info will be dynamically inserted here -->
            <p><?php echo esc_html__('Do you want to proceed?', 'gpt3-ai-content-generator'); ?></p>
        </div>
        <div class="wpaicg_assign_footer_emb">
            <button id="confirmModelChange" class="button button-primary"><?php echo esc_html__('Yes, Proceed', 'gpt3-ai-content-generator'); ?></button>
            <button id="cancelModelChange" class="button"><?php echo esc_html__('Cancel', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>


<script>
    jQuery(document).ready(function($){

        function toggleVectorDBSettings() {
            var selectedDB = $('select[name="wpaicg_vector_db_provider"]').val();
            if (selectedDB === 'qdrant') {
                $('#wpaicg_pinecone_settings').hide();
                $('#wpaicg_qdrant_settings').show();
            } else {
                $('#wpaicg_pinecone_settings').show();
                $('#wpaicg_qdrant_settings').hide();
            }
        }

        // Run on page load
        toggleVectorDBSettings();

        // Run on selection change
        $('select[name="wpaicg_vector_db_provider"]').change(function() {
            toggleVectorDBSettings();
        });

        // Function to show modal
        function showModal() {
            $('#wpaicg_emb_modal_overlay').show();
            $('#embeddingModelChangeModal').show();
        }

        // Function to hide modal
        function hideModal() {
            $('#wpaicg_emb_modal_overlay').hide();
            $('#embeddingModelChangeModal').hide();
        }

        // Detect change in the embeddings model dropdown
        $('select[name="wpaicg_openai_embeddings"]').change(function() {
            // Show modal when the model is changed
            showModal();
        });

        // Handle modal confirmation
        $('#confirmModelChange').click(function() {
            hideModal();
            // Add any additional actions you want to perform after confirmation
        });

        // Handle modal cancellation
        $('#cancelModelChange').click(function() {
            hideModal();
            // Reset the dropdown to its original value if needed
        });
        
        function wpaicgLoading(btn){
            btn.attr('disabled','disabled');
            if(!btn.find('spinner').length){
                btn.append('<span class="spinner"></span>');
            }
            btn.find('.spinner').css('visibility','unset');
        }
        function wpaicgRmLoading(btn){
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }

        $('select[name="wpaicg_openai_embeddings"], select[name="wpaicg_google_embeddings"], select[name="wpaicg_azure_embeddings"]').change(function() {
            var selectedProvider = '<?php echo $wpaicg_provider; ?>';
            var selectedModel = $(this).val();
            var dimensionInfo = '';

            if (selectedProvider === 'OpenAI') {
                switch (selectedModel) {
                    case 'text-embedding-3-small':
                        dimensionInfo = 'You have selected text-embedding-3-small. Make sure your vector dimension is <strong>1536</strong>.';
                        break;
                    case 'text-embedding-3-large':
                        dimensionInfo = 'You have selected text-embedding-3-large. Make sure your vector dimension is <strong>3072</strong>.';
                        break;
                    case 'text-embedding-ada-002':
                        dimensionInfo = 'You have selected text-embedding-ada-002. Make sure your vector dimension is <strong>1536</strong>.';
                        break;
                }
            } else if (selectedProvider === 'Google') {
                dimensionInfo = 'Make sure your dimension is <strong>768</strong>.';
            }

            if (dimensionInfo !== '') {
                $('.wpaicg_emb_modal_content p:nth-of-type(3)').html(dimensionInfo);
            }
            
            showModal();
        });


        function updateCollectionsDropdown(collections) {
            var dropdown = $('.wpaicg_qdrant_collections_dropdown');
            if (collections.length > 0) {
                dropdown.empty().show();
                $.each(collections, function(index, collection) {
                    var displayText = collection.name + ' (' + collection.dimension + ')';
                    dropdown.append($('<option></option>').attr('value', collection.name).text(displayText));
                });
            } else {
                dropdown.hide();
            }
        }

        // Show the input field for new collection creation
        $('.wpaicg_create_new_collection_btn').click(function() {
            $('.wpaicg_new_collection_input').show();
        });

        // Handle the creation of new collection
        $('.wpaicg_submit_new_collection').click(function() {
            var collectionName = $('.wpaicg_new_collection_name').val().trim();
            var dimension = parseInt($('.wpaicg_new_collection_dimension').val().trim(), 10); // Parse as integer
            var apiKey = $('input[name="wpaicg_qdrant_api_key"]').val().trim();
            var endpoint = $('input[name="wpaicg_qdrant_endpoint"]').val().trim();
            if (!collectionName) {
                alert('Please enter a collection name.');
                return;
            }
            if (!dimension || isNaN(dimension)) {
                alert('Please enter a valid dimension as a number.');
                return;
            }
            if (dimension < 128 || dimension > 65536) {
                alert('Please enter a dimension between 128 and 65536.');
                return;
            }

            wpaicgLoading($('.wpaicg_submit_new_collection'));

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                type: 'POST',
                data: {
                    action: 'wpaicg_create_collection',
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                    collection_name: collectionName,
                    dimension: dimension,
                    api_key: apiKey,
                    endpoint: endpoint
                },
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.status && result.status.error) {
                        alert('Error: ' + result.status.error);
                    } else {
                        // Add the new collection to the dropdown
                        $('.wpaicg_qdrant_collections_dropdown').append($('<option>', {
                            value: collectionName,
                            text: collectionName + ' (' + dimension + ')'
                        })).val(collectionName);

                        // Update collections in the options table
                        var updatedCollections = $('.wpaicg_qdrant_collections_dropdown option').map(function() {
                            return $(this).val();
                        }).get();

                        $.post('<?php echo admin_url('admin-ajax.php') ?>', {
                            action: 'wpaicg_save_qdrant_collections',
                            nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                            collections: updatedCollections
                        });

                        $('.wpaicg_new_collection_input').hide();
                        $('.wpaicg_new_collection_name').val('');
                        $('.wpaicg_sync_qdrant_collections').click();
                    }
                    wpaicgRmLoading($('.wpaicg_submit_new_collection'));
                },
                error: function() {
                    alert('Error: Unable to create collection.');
                    wpaicgRmLoading($('.wpaicg_submit_new_collection'));
                }
            });
        });
        $('.wpaicg_sync_qdrant_collections').click(function() {
            var btn = $(this);
            // get api key
            var apiKey = $('input[name="wpaicg_qdrant_api_key"]').val().trim();
            if (!apiKey) {
                alert('Please enter a valid API key.');
                return;
            }
            // get endpoint
            var endpoint = $('input[name="wpaicg_qdrant_endpoint"]').val().trim();
            if (!endpoint) {
                alert('Please enter a valid endpoint.');
                return;
            }
            wpaicgLoading(btn);

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                type: 'POST',
                dataType: 'json', // Expecting JSON response
                data: {
                    action: 'wpaicg_show_collections',
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                    api_key: apiKey,
                    endpoint: endpoint
                },
                success: function(response) {
                    if (response.success) {
                        var collections = response.data;
                        updateCollectionsDropdown(collections);

                        // Save the collections to the options table
                        $.post('<?php echo admin_url('admin-ajax.php') ?>', {
                            action: 'wpaicg_save_qdrant_collections',
                            nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                            collections: collections
                        });
                    } else {
                        // Handle error response
                        alert('Error: ' + (response.data.error || 'Unable to sync collections.'));
                    }
                    wpaicgRmLoading(btn);
                },
                error: function(xhr, status, error) {
                    // Handle low-level HTTP error
                    alert('Error: ' + (error || 'Unable to sync collections.'));
                    wpaicgRmLoading(btn);
                }
            });
        });


        // Initially hide the dropdown if it's empty
        var initialCollections = $('.wpaicg_qdrant_collections_dropdown option').length;
        if (initialCollections === 0) {
            $('.wpaicg_qdrant_collections_dropdown').hide();
        }

        $('.wpaicg_pinecone_indexes').click(function (){
            var btn = $(this);
            var wpaicg_pinecone_api = $('.wpaicg_pinecone_api').val();
            var old_value = $('.wpaicg_pinecone_environment').attr('old-value');
            if(wpaicg_pinecone_api !== ''){
                $.ajax({
                    url: 'https://api.pinecone.io/indexes',
                    headers: {"Api-Key": wpaicg_pinecone_api},
                    dataType: 'json',
                    beforeSend: function (){
                        wpaicgLoading(btn);
                        btn.html('<?php echo esc_html__('Syncing...','gpt3-ai-content-generator')?>');
                    },
                    success: function (res){
                        if(res.indexes && res.indexes.length){
                            var selectList = '<option value=""><?php echo esc_html__('Select Index','gpt3-ai-content-generator')?></option>';
                            var formattedIndexes = [];

                            res.indexes.forEach(function(index){
                                var displayText = index.name + ' (' + index.dimension + ')';
                                selectList += '<option value="' + index.host + '"' + (old_value === index.host ? ' selected' : '') + '>' + displayText + '</option>';
                                formattedIndexes.push({ name: index.name, url: index.host, dimension: index.dimension });
                            });

                            $('.wpaicg_pinecone_environment').html(selectList);

                            // Save formatted indexes to the database
                            $.post('<?php echo admin_url('admin-ajax.php')?>', {
                                action: 'wpaicg_pinecone_indexes',
                                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',
                                indexes: JSON.stringify(formattedIndexes),
                                api_key: wpaicg_pinecone_api
                            });
                        }
                        btn.html('<?php echo esc_html__('Sync Indexes','gpt3-ai-content-generator')?>');
                        wpaicgRmLoading(btn);
                    },
                    error: function (e){
                        btn.html('<?php echo esc_html__('Sync Indexes','gpt3-ai-content-generator')?>');
                        wpaicgRmLoading(btn);
                        alert(e.responseText);
                    }
                });
            }
            else{
                alert('<?php echo esc_html__('Please add Pinecone API key before start sync','gpt3-ai-content-generator')?>')
            }
        })
    })
</script>
<script>
jQuery(document).ready(function($) {
    // Check if the message exists
    if ($('.wpaicg-embedding-save-message').length) {
        setTimeout(function() {
            // Add the 'hidden' class to fade and hide the message
            $('.wpaicg-embedding-save-message').addClass('wpaicg-embedding-save-message-hidden');
        }, 5000); // Hide after 5 seconds (5000 milliseconds)
    }
});
</script>
