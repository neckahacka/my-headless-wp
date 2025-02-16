<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;
$wpaicgTable = $wpdb->prefix . 'wpaicg';
$sql = $wpdb->prepare( 'SELECT * FROM ' . $wpaicgTable . ' where name=%s','wpaicg_settings' );
$wpaicg_settings = $wpdb->get_row( $sql, ARRAY_A );
$wpaicg_openai_api_key = '';
if($wpaicg_settings && isset($wpaicg_settings['api_key']) && !empty($wpaicg_settings['api_key'])){
    $wpaicg_openai_api_key = $wpaicg_settings['api_key'];
}
$wpaicg_pinecone_api = get_option('wpaicg_troubleshoot_pinecone_api',get_option('wpaicg_pinecone_api',''));
$wpaicg_openai_trouble_api = get_option('wpaicg_openai_trouble_api',$wpaicg_openai_api_key);
$wpaicg_provider = get_option('wpaicg_provider');
$wpaicg_vector_db_provider = get_option('wpaicg_vector_db_provider', 'pinecone'); // Default to Pinecone
$wpaicg_qdrant_api_key = get_option('wpaicg_qdrant_api_key', '');
$wpaicg_qdrant_endpoint = get_option('wpaicg_qdrant_endpoint', '');
?>
<!-- Vector DB Provider Selection -->
<div class="wpaicg_vector_db_provider_selection">
    <div class="nice-form-group">
        <label><?php esc_html_e('Vector DB Provider', 'gpt3-ai-content-generator'); ?></label>
        <select class="wpaicg_vector_db_provider" name="wpaicg_vector_db_provider" style="width: 50%;">
            <option value="pinecone" <?php echo $wpaicg_vector_db_provider === 'pinecone' ? 'selected' : ''; ?>>Pinecone</option>
            <option value="qdrant" <?php echo $wpaicg_vector_db_provider === 'qdrant' ? 'selected' : ''; ?>>Qdrant</option>
        </select>
    </div>
</div>
<p></p>
<!-- Pinecone specific fields -->
<div class="wpaicg_pinecone_specific" style="<?php echo $wpaicg_vector_db_provider === 'qdrant' ? 'display: none;' : ''; ?>">
    <div class="nice-form-group">
        <label><?php esc_html_e('Pinecone API Key', 'gpt3-ai-content-generator'); ?></label>
        <input style="width: 50%;" value="<?php echo esc_html($wpaicg_pinecone_api)?>" class="wpaicg_pinecone_api" type="text" placeholder="000000-000-000-0000-0000000" />
        <button class="button button-primary wpaicg_valid_pinecone_api" style="padding-top: 0.5em;padding-bottom: 0.5em;"><?php esc_html_e('Sync Indexes', 'gpt3-ai-content-generator'); ?></button>
        <button class="button wpaicg_start_pinecone_api" style="padding-top: 0.5em;padding-bottom: 0.5em;"><?php esc_html_e('Start Over', 'gpt3-ai-content-generator'); ?></button>
        <div class="wpaicg_valid_pinecone_result"></div>
    </div>
    <div class="wpaicg_pinecone_index_box" style="display: none">
        <div class="nice-form-group">
            <div class="wpaicg_pinecone_index_list"></div>
        </div>
    </div>
</div>

<!-- Qdrant specific fields -->
<div class="wpaicg_qdrant_specific" style="<?php echo $wpaicg_vector_db_provider === 'qdrant' ? '' : 'display: none;'; ?>">
    <div class="nice-form-group">
        <label><?php esc_html_e('Qdrant API Key', 'gpt3-ai-content-generator'); ?></label>
        <input style="width: 50%;" type="text" class="wpaicg_qdrant_api_key" value="<?php echo esc_attr($wpaicg_qdrant_api_key); ?>" placeholder="Your Qdrant API Key" />
    </div>
    <div class="nice-form-group">
        <label><?php esc_html_e('Qdrant Endpoint', 'gpt3-ai-content-generator'); ?></label>
        <input style="width: 50%;" type="text" class="wpaicg_qdrant_endpoint" value="<?php echo esc_attr($wpaicg_qdrant_endpoint); ?>" placeholder="Your Qdrant Endpoint" />
        <button style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button button-primary wpaicg_connect_qdrant">Connect</button>
        <button style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button wpaicg_start_qdrant">Start Over</button>
    </div>
    <p></p>
    <div class="wpaicg_qdrant_connection_result"></div>
    <p></p>
    <div class="wpaicg_qdrant_collections" style="display: none;">
        <div class="nice-form-group">
            <button class="button wpaicg_show_collections">Show Collections</button>
            <div class="wpaicg_collections_result"></div>
            <div id="collection_details"></div> <!-- Details will be shown here -->
        </div>
    </div>
    <div class="wpaicg_create_collection_form" style="display: none;">
        <div class="nice-form-group">
            <input style="width: 50%;" type="text" class="wpaicg_new_collection_name" placeholder="Enter new collection name" />
            <button style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button button-primary wpaicg_submit_new_collection_for_troubleshoot">Create</button>
        </div>
    </div>
    <input type="hidden" id="selected_qdrant_collection" value="">
    <div class="wpaicg_create_collection_response"></div>
</div>
<!-- OpenAI specific fields -->
<?php if ($wpaicg_provider !== 'Azure'): ?>
<div class="wpaicg_openai_api_box" style="display: none">
    <div class="nice-form-group">
        <label><?php esc_html_e('OpenAI API Key', 'gpt3-ai-content-generator'); ?></label>
        <input style="width: 50%;" value="<?php echo esc_html($wpaicg_openai_trouble_api)?>" class="wpaicg_openai_api" type="text" placeholder="sk-..." />
        <button style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button button-primary wpaicg_valid_openai_api"><?php esc_html_e('Validate', 'gpt3-ai-content-generator'); ?></button>
    </div>
</div>
<p></p>
<?php endif; ?>
<div id="accordion_troubleshoot" style="display: none">
    <ul>
        <li><a href="#tab-embeddings">Embeddings</a></li>
        <li><a href="#tab-query">Query</a></li>
    </ul>
    <div id="tab-embeddings">
        <div class="wpaicg_valid_openai_api_result"></div>
        <p></p>
        <div class="wpaicg_pinecone_test_vectors" style="display: none">
            <h1>Store Sample Data</h1>
            <div class="nice-form-group">
                <textarea style="width: 50%;" rows="5"></textarea>
                <button style="padding-top: 0.5em;padding-bottom: 0.5em;" class="button button-primary wpaicg_send_content_vectors">Send</button>
            </div>
            <div class="wpaicg_pinecone_vectors_result"></div>
        </div>
        <div class="wpaicg_pinecone_send_vectors" style="display: none">
            <br>
            <button class="button button-primary wpaicg_send_vectors_btn">Add to Vector DB</button>
            <div class="wpaicg_pinecone_send_vectors_result"></div>
            <div class="wpaicg_pinecone_delete_vectors_result"></div>
            <div class="wpaicg_qdrant_send_vectors_result"></div>
            <div class="wpaicg_qdrant_delete_vectors_result"></div>
        </div>
    </div>
    <div id="tab-query">
        <label>Search: </label>
        <input type="text" class="wpaicg_pinecone_query">
        <label>Nearest Answers: </label>
        <input type="number" class="wpaicg_pinecone_topk" value="3" min="1" max="1000">
        <button class="button button-primary wpaicg_pinecone_search">Search</button>
        <div class="wpaicg_pinecone_search_embeddings_result"></div>
        <div class="wpaicg_pinecone_search_result"></div>
    </div>
</div>
<script>
    jQuery( function($) {
        $( "#accordion_troubleshoot" ).tabs();

        // Update button label based on the selected vector DB provider
        function updateAddVectorButtonLabel() {
            var provider = $('.wpaicg_vector_db_provider').val();
            var buttonLabel = provider === 'qdrant' ? 'Add to Qdrant' : 'Add to Pinecone';
            $('.wpaicg_send_vectors_btn').text(buttonLabel);
        }

        // Handle vector DB provider selection change
        $('.wpaicg_vector_db_provider').change(function() {
            var provider = $(this).val();
            if(provider === 'pinecone') {
                $('.wpaicg_pinecone_specific').show();
                $('.wpaicg_qdrant_specific').hide();
            } else if(provider === 'qdrant') {
                $('.wpaicg_pinecone_specific').hide();
                $('.wpaicg_qdrant_specific').show();
            }
            updateAddVectorButtonLabel();
        });

        updateAddVectorButtonLabel();

        // Function to show loading spinner
        function wpaicgLoadingSpinner(element, show) {
            var spinnerHtml = '<span class="spinner" style="visibility: ' + (show ? 'visible' : 'hidden') + ';"></span>';
            if(show) {
                element.attr('disabled', 'disabled').append(spinnerHtml);
            } else {
                element.removeAttr('disabled').find('.spinner').remove();
            }
        }

        // Function to toggle OpenAI Authentication and tabs
        function toggleOpenAIAuthAndTabs(display) {
            if (display) {
                $('.wpaicg_openai_api_box').show();
                $('#accordion_troubleshoot').show();
            } else {
                $('.wpaicg_openai_api_box').hide();
                $('#accordion_troubleshoot').hide();
            }
        }

        $('.wpaicg_start_qdrant').click(function() {
            // Hide Qdrant specific dynamic sections
            $('.wpaicg_qdrant_connection_result').empty();
            $('.wpaicg_qdrant_collections').hide();
            $('#collection_details').empty();

            // Optionally, you can reset the Vector DB Provider selection to 'pinecone' or keep it as is
            // $('.wpaicg_vector_db_provider').val('pinecone').change();
        });

        $('.wpaicg_connect_qdrant').click(function() {
            var button = $(this);
            var apiKey = $('.wpaicg_qdrant_api_key').val();
            var endpoint = $('.wpaicg_qdrant_endpoint').val();
            var data = {
                action: 'wpaicg_troubleshoot_connect_qdrant',
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                api_key: apiKey,
                endpoint: endpoint
            };

            // Show loading spinner
            wpaicgLoadingSpinner(button, true);

            $.post('<?php echo admin_url('admin-ajax.php') ?>', data, function(res) {
                $('.wpaicg_qdrant_connection_result').html('<pre>' + res + '</pre>');

                // Hide loading spinner
                wpaicgLoadingSpinner(button, false);
                // Show "Show Collections" button if the response includes 'qdrant - vector search engine'
                if(res.includes('"title":"qdrant - vector search engine"')) {
                    $('.wpaicg_qdrant_collections').show(); // Ensure that the div containing the button is shown
                    $('.wpaicg_show_collections').click();
                }
            }).fail(function() {
                // In case of error, hide loading spinner
                wpaicgLoadingSpinner(button, false);
            });
        });


        $('.wpaicg_show_collections').click(function() {
            var button = $(this); // Get the button element
            var data = {
                action: 'wpaicg_troubleshoot_show_collections',
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'
            };

            // Show loading spinner
            wpaicgLoadingSpinner(button, true);

            $.post('<?php echo admin_url('admin-ajax.php') ?>', data, function(res) {
                var response = JSON.parse(res);

                // Check if the collections array is empty
                if (response.length === 0) {
                    $('.wpaicg_collections_result').html('<p>No collections available. Please create one.</p>');
                } else {
                    var tableHtml = '<p><table class="wp-list-table widefat striped"><thead><tr><th>Collection Name</th><th>Actions</th></tr></thead><tbody>';

                    response.forEach(function(collection) {
                        tableHtml += '<tr><td>' + collection + '</td><td>';
                        tableHtml += '<button class="button detail-btn">Details</button>';
                        tableHtml += '<button class="button delete-btn">Delete</button>';
                        tableHtml += '<button class="button use-this-btn">Use</button>'; // Add "Use This" button
                        tableHtml += '</td></tr>';
                    });

                    tableHtml += '</tbody></table></p>';
                    $('.wpaicg_collections_result').html(tableHtml);
                }

                // Hide loading spinner
                wpaicgLoadingSpinner(button, false);
                $('.wpaicg_create_collection_response').html(''); // Clear any existing messages


            }).fail(function() {
                $('.wpaicg_collections_result').html('<p>Error: Unable to fetch collections.</p>');
                // Hide loading spinner
                wpaicgLoadingSpinner(button, false);
            });
        });

        // Event handler for the Details button
        $('.wpaicg_collections_result').on('click', '.detail-btn', function() {
            var button = $(this); // Get the button element
            var collectionName = button.closest('tr').find('td:first').text();
            var data = {
                action: 'wpaicg_troubleshoot_get_collection_details',
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                collection_name: collectionName
            };

            // Show loading spinner
            wpaicgLoadingSpinner(button, true);

            $.post('<?php echo admin_url('admin-ajax.php') ?>', data, function(res) {
                var details = JSON.parse(res);
                // Formatting the response in a user-friendly way
                var detailHtml = '<h3>Details for Collection: ' + collectionName + '</h3>';
                detailHtml += '<pre>' + JSON.stringify(details, null, 4) + '</pre>';
                $('#collection_details').html(detailHtml);

                // Hide loading spinner
                wpaicgLoadingSpinner(button, false);
            }).fail(function() {
                $('#collection_details').html('<p>Error: Unable to fetch collection details.</p>');
                // Hide loading spinner
                wpaicgLoadingSpinner(button, false);
            });
        });


        // Event handler for the Delete button
        $('.wpaicg_collections_result').on('click', '.delete-btn', function() {
            var button = $(this);
            var collectionName = button.closest('tr').find('td:first').text();
            var confirmation = confirm("Are you sure you want to delete the collection: " + collectionName + "?");

            if (confirmation) {
                var data = {
                    action: 'wpaicg_troubleshoot_delete_collection',
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                    collection_name: collectionName
                };

                // Show loading spinner
                wpaicgLoadingSpinner(button, true);

                $.post('<?php echo admin_url('admin-ajax.php') ?>', data, function(res) {
                    var response = JSON.parse(res);
                    if(response.error) {
                        alert('Error: ' + response.error);
                    } else {
                        alert('Collection deleted successfully.');
                        // Refresh the collections list
                        $('.wpaicg_show_collections').click();
                        // Clear or hide the collection details
                        $('#collection_details').html(''); // or $('#collection_details').hide();
                    }

                    // Hide loading spinner
                    wpaicgLoadingSpinner(button, false);
                }).fail(function() {
                    alert('Error: Unable to delete collection.');
                    // Hide loading spinner
                    wpaicgLoadingSpinner(button, false);
                });
            }
        });

        var selectedQdrantCollection = '';

        // Event handler for "Use This" button
        $('.wpaicg_collections_result').on('click', '.use-this-btn', function() {
            $('#collection_details').html(''); // Clear or hide the collection details
            var button = $(this);

            // Disable the clicked button and enable all others
            $('.use-this-btn').not(button).prop('disabled', false); // Enable all other buttons
            button.prop('disabled', true); // Disable the clicked button

            var collectionName = button.closest('tr').find('td:first').text();

            // Store the selected collection name in a global variable or hidden input field
            selectedQdrantCollection = collectionName;
            $('#selected_qdrant_collection').val(collectionName);

            // Show the OpenAI box and the accordion tabs
            toggleOpenAIAuthAndTabs(true);
        });

        // Add Create New button next to Show Collections
        $('.wpaicg_qdrant_collections').prepend('<button class="button wpaicg_create_new_collection">Create New</button>');

        // Event handler for the Create New button
        $('.wpaicg_create_new_collection').click(function() {
            // Clear any existing messages
            $('.wpaicg_create_collection_response').html('');
            // Toggle the visibility of the create collection form
            $('.wpaicg_create_collection_form').toggle();
        });

        // Event handler for the submit button in the new collection form
        $('.wpaicg_submit_new_collection_for_troubleshoot').click(function() {
            var button = $(this);
            var collectionName = $('.wpaicg_new_collection_name').val();
            if (!collectionName) {
                $('.wpaicg_create_collection_response').html('<p>Please enter a collection name.</p>');
                return;
            }

            var data = {
                action: 'wpaicg_troubleshoot_create_collection',
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                collection_name: collectionName
            };

            // Show loading spinner
            wpaicgLoadingSpinner(button, true);

            $.post('<?php echo admin_url('admin-ajax.php') ?>', data, function(res) {
                var response = JSON.parse(res);
                console.log(response);
                if(response.error) {
                    $('.wpaicg_create_collection_response').html('<p>Error: ' + response.error + '</p>');
                } else {
                    $('.wpaicg_create_collection_response').html('<p>Collection created successfully.</p>');
                    $('.wpaicg_create_collection_form').hide();
                    $('.wpaicg_new_collection_name').val('');
                    $('.wpaicg_show_collections').click();
                }
                wpaicgLoadingSpinner(button, false);
            }).fail(function() {
                $('.wpaicg_create_collection_response').html('<p>Error: Unable to create collection.</p>');
                wpaicgLoadingSpinner(button, false);
            });
        });


        var wpaicg_pinecone_api = $('.wpaicg_pinecone_api');
        var accordion_troubleshoot = $('#accordion_troubleshoot');
        var wpaicg_openai_api = $('.wpaicg_openai_api');
        var wpaicg_valid_openai_api = $('.wpaicg_valid_openai_api');
        var wpaicg_openai_api_box = $('.wpaicg_openai_api_box');
        var wpaicg_valid_openai_api_result = $('.wpaicg_valid_openai_api_result');
        var wpaicg_valid_pinecone_result = $('.wpaicg_valid_pinecone_result');
        var wpaicg_pinecone_index_list = $('.wpaicg_pinecone_index_list');
        var wpaicg_pinecone_delete_vectors_result = $('.wpaicg_pinecone_delete_vectors_result');
        var wpaicg_valid_pinecone_api = $('.wpaicg_valid_pinecone_api');
        var wpaicg_pinone_index_selected = $('.wpaicg_pinone_index_selected');
        var wpaicg_pinecone_index_box = $('.wpaicg_pinecone_index_box');
        var wpaicg_send_content_vectors = $('.wpaicg_send_content_vectors');
        var wpaicg_pinecone_vectors_result = $('.wpaicg_pinecone_vectors_result');
        var wpaicg_pinecone_send_vectors = $('.wpaicg_pinecone_send_vectors');
        var wpaicg_send_vectors_btn = $('.wpaicg_send_vectors_btn');
        var wpaicg_pinecone_send_vectors_result = $('.wpaicg_pinecone_send_vectors_result');
        var wpaicg_qdrant_send_vectors_result = $('.wpaicg_qdrant_send_vectors_result');
        var wpaicg_pinecone_test_vectors = $('.wpaicg_pinecone_test_vectors');
        var wpaicg_start_pinecone_api = $('.wpaicg_start_pinecone_api');
        var wpaicg_pinecone_search = $('.wpaicg_pinecone_search');
        var wpaicg_pinecone_topk = $('.wpaicg_pinecone_topk');
        var wpaicg_pinecone_query = $('.wpaicg_pinecone_query');
        var wpaicg_pinecone_search_result = $('.wpaicg_pinecone_search_result');
        var wpaicg_pinecone_search_embeddings_result = $('.wpaicg_pinecone_search_embeddings_result');
        var pinecone_api,openai_api,openai_vectors,pinecone_id,search_vectors;
        var qdrant_id;
        wpaicg_pinecone_search.click(function (){
            var text = wpaicg_pinecone_query.val();
            wpaicg_pinecone_search_result.empty();
            wpaicg_pinecone_search_embeddings_result.empty();
            var topk = wpaicg_pinecone_topk.val();
            var provider = $('.wpaicg_vector_db_provider').val();
            if(text !== ''){
                var data = {input: text, model: 'text-embedding-ada-002'};
                $.ajax({
                    url: 'https://api.openai.com/v1/embeddings',
                    data: JSON.stringify(data),
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                    type: 'POST',
                    headers: {"Authorization": 'Bearer '+openai_api},
                    beforeSend: function (){
                        wpaicgLoading(wpaicg_pinecone_search)
                    },
                    success: function (res){
                        if(provider === 'pinecone') {
                            wpaicg_pinecone_search_result.html('<p><strong>Response (Vectors):</strong></p><pre>'+JSON.stringify(res,undefined, 4)+'</pre>');
                            search_vectors = res.data[0].embedding;
                            var data = {vector: search_vectors,topK: topk};
                            var pineconeindexSelected = wpaicg_pinecone_index_list.find('select').val();
                            $.ajax({
                                url: '<?php echo admin_url('admin-ajax.php')?>',
                                data: {
                                    action: 'wpaicg_troubleshoot_search',
                                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',
                                    data: JSON.stringify(data),
                                    api_key: pinecone_api,
                                    environment: 'https://'+pineconeindexSelected+'/query'
                                },
                                dataType: 'json',
                                type: 'POST',
                                success: function (res){
                                    wpaicgRmLoading(wpaicg_pinecone_search);
                                    wpaicg_pinecone_search_embeddings_result.html('<p><strong>Response:</strong></p><pre>'+JSON.stringify(res,undefined, 4)+'</pre>');
                                },
                                error: function (e){
                                    wpaicgRmLoading(wpaicg_pinecone_search)
                                    wpaicg_pinecone_search_embeddings_result.html('<p><strong>Response:</strong></p><pre>'+e.responseText+'</pre>')
                                }
                            })
                        } else if(provider === 'qdrant') {
                            
                            wpaicg_qdrant_search(text, topk, res.data[0].embedding);
                        }
                    },
                    error: function (e){
                        wpaicgRmLoading(wpaicg_pinecone_search)
                        wpaicg_pinecone_search_result.html('<p><strong>Response:</strong></p><pre>'+e.responseText+'</pre>')
                    }
                })
            }
            else{
                alert('Please enter text for query');
            }
        })
        wpaicg_start_pinecone_api.click(function (){
            wpaicg_pinecone_index_list.empty();
            wpaicg_pinone_index_selected.empty();
            wpaicg_pinecone_delete_vectors_result.empty();
            wpaicg_valid_openai_api_result.empty();
            wpaicg_valid_pinecone_result.empty();
            wpaicg_pinecone_search_result.empty();
            wpaicg_pinecone_search_embeddings_result.empty();
            wpaicg_pinecone_test_vectors.hide();
            wpaicg_pinecone_send_vectors.hide();
            wpaicg_pinecone_api.removeAttr('disabled');
        })
        wpaicg_valid_pinecone_api.click(function(){
            wpaicg_pinecone_index_list.empty();
            wpaicg_pinone_index_selected.empty();
            wpaicg_pinecone_delete_vectors_result.empty();
            wpaicg_valid_openai_api_result.empty();
            wpaicg_valid_pinecone_result.empty();
            wpaicg_pinecone_test_vectors.hide();
            wpaicg_pinecone_send_vectors.hide();
            wpaicg_pinecone_search_result.empty();
            wpaicg_pinecone_search_embeddings_result.empty();
            pinecone_api = wpaicg_pinecone_api.val();
            if(pinecone_api !== ''){
                $.post('<?php echo admin_url('admin-ajax.php')?>',{action: 'wpaicg_troubleshoot_save',nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',key: 'wpaicg_troubleshoot_pinecone_api',value:pinecone_api});
                var apiUrl = '';
                var apiHeaders = {
                    "Api-Key": pinecone_api,
                    "accept": "application/json"
                };

                apiUrl = 'https://api.pinecone.io/indexes';

                $.ajax({
                    url: apiUrl,
                    headers: apiHeaders,
                    dataType: 'json',
                    beforeSend: function (){
                        wpaicgLoading(wpaicg_valid_pinecone_api)
                    },
                    success: function (res) {
                            if (res.indexes && res.indexes.length > 0) {
                                
                                var selectList = '<div class="nice-form-group">';
                                selectList += '<label>Pinecone Index</label>';
                                selectList += '<select id="pineconeIndexSelect" style="width: 50%;">';
                                
                                res.indexes.forEach(function(index) {
                                    selectList += '<option value="' + index.host + '">' + index.name + ' (' + (index.spec.pod ? 'Pod' : 'Serverless') + ')</option>';
                                });

                                selectList += '</select></div>';
                                wpaicg_pinecone_index_list.html(selectList);
                                wpaicgRmLoading(wpaicg_valid_pinecone_api);
                                wpaicg_pinecone_index_box.show();
                                toggleOpenAIAuthAndTabs(true);
                            } else {
                                wpaicg_valid_pinecone_result.html('<p>No indexes found.</p>');
                                toggleOpenAIAuthAndTabs(false);
                            }

                    },
                    error: function (e){
                        wpaicgRmLoading(wpaicg_valid_pinecone_api)
                        wpaicg_valid_pinecone_result.html('<p><strong>Response:</strong></p><pre>'+e.responseText+'</pre>')
                        toggleOpenAIAuthAndTabs(false);
                    }
                });
                wpaicg_valid_pinecone_result.show();
            }
            else{
                alert('Please enter your Pinecone API key')
            }
        });
        wpaicg_valid_openai_api.click(function (){
            openai_api = wpaicg_openai_api.val();
            wpaicg_valid_openai_api_result.empty();
            wpaicg_pinecone_vectors_result.empty();
            wpaicg_pinecone_test_vectors.hide();
            wpaicg_pinecone_test_vectors.hide();
            wpaicg_pinecone_send_vectors.hide();
            if(openai_api !== ''){
                $.post('<?php echo admin_url('admin-ajax.php')?>',{action: 'wpaicg_troubleshoot_save',nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',key: 'wpaicg_openai_trouble_api',value:openai_api});
                $.ajax({
                    url: 'https://api.openai.com/v1/models',
                    headers: {"Authorization": 'Bearer '+openai_api},
                    dataType: 'json',
                    beforeSend: function (){
                        wpaicgLoading(wpaicg_valid_openai_api)
                    },
                    success: function (res){
                        wpaicgRmLoading(wpaicg_valid_openai_api)
                        wpaicg_valid_openai_api_result.html('<p><strong>Response:</strong></p><pre>'+JSON.stringify(res,undefined, 4)+'</pre>');
                        wpaicg_pinecone_test_vectors.show();
                        toggleOpenAIAuthAndTabs(true);
                    },
                    error: function (e){
                        wpaicgRmLoading(wpaicg_valid_openai_api)
                        toggleOpenAIAuthAndTabs(true);
                        wpaicg_valid_openai_api_result.html('<p><strong>Response:</strong></p><pre>'+e.responseText+'</pre>')
                    }
                });
                wpaicg_valid_openai_api_result.show();
            }
            else{
                alert('Please enter OpenAI API key');
            }
        });
        wpaicg_send_content_vectors.click(function (){
            var text = wpaicg_pinecone_test_vectors.find('textarea').val();
            wpaicg_pinecone_vectors_result.empty();
            wpaicg_pinecone_send_vectors_result.empty();
            wpaicg_qdrant_send_vectors_result.empty();
            wpaicg_pinecone_send_vectors.hide();
            if(text !== ''){
                var data = {input: text, model: 'text-embedding-ada-002'};
                $.ajax({
                    url: 'https://api.openai.com/v1/embeddings',
                    data: JSON.stringify(data),
                    dataType: 'json',
                    contentType: "application/json; charset=utf-8",
                    type: 'POST',
                    headers: {"Authorization": 'Bearer '+openai_api},
                    beforeSend: function (){
                        wpaicgLoading(wpaicg_send_content_vectors)
                    },
                    success: function (res){
                        wpaicgRmLoading(wpaicg_send_content_vectors)
                        wpaicg_pinecone_vectors_result.html('<p><strong>Response:</strong></p><pre>'+JSON.stringify(res,undefined, 4)+'</pre>');
                        wpaicg_pinecone_send_vectors.show();
                        openai_vectors = res.data[0].embedding;
                    },
                    error: function (e){
                        wpaicgRmLoading(wpaicg_send_content_vectors)
                        wpaicg_pinecone_vectors_result.html('<p><strong>Response:</strong></p><pre>'+e.responseText+'</pre>')
                    }
                })
            }
            else{
                alert('Please insert content for get vectors')
            }
        });
        wpaicg_send_vectors_btn.click(function (){
            var provider = $('.wpaicg_vector_db_provider').val();
            if(provider === 'pinecone') {
                wpaicg_pinecone_send_vectors_result.empty();
                wpaicg_qdrant_send_vectors_result.empty();
                var pineconeindexSelected = wpaicg_pinecone_index_list.find('select').val();
                if(openai_vectors !== ''){
                    pinecone_id = 'test_'+Math.ceil(Math.random()*10000);
                    var data = {vectors: [{id: pinecone_id,values: openai_vectors}]};
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php')?>',
                        data: {
                            action: 'wpaicg_troubleshoot_add_vector',
                            nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',
                            data: JSON.stringify(data),
                            api_key: pinecone_api,
                            environment: 'https://'+pineconeindexSelected+'/vectors/upsert'
                        },
                        dataType: 'json',
                        type: 'POST',
                        beforeSend: function (){
                            wpaicgLoading(wpaicg_send_vectors_btn)
                        },
                        success: function (res){
                            wpaicgRmLoading(wpaicg_send_vectors_btn);
                            wpaicg_pinecone_send_vectors_result.html('<div class="nice-form-group"><p><strong>Response:</strong></p><pre>'+JSON.stringify(res,undefined, 4)+'</pre><button class="button wpaicg_pinecone_delete_vectors_btn" style="background: #cc0000;color: #fff;border-color: #cb0404;">Delete Vector</button></div>');
                        },
                        error: function (e){
                            wpaicgRmLoading(wpaicg_send_vectors_btn)
                            wpaicg_pinecone_send_vectors_result.html('<p><strong>Response:</strong></p><pre>'+e.responseText+'</pre>')
                        }
                    })
                }
                else{
                    alert('Please get vectors from OpenAI first')
                }
            } else if(provider === 'qdrant') {
                wpaicg_pinecone_send_vectors_result.empty();
                wpaicg_qdrant_send_vectors_result.empty();
                var qdrantEndpoint = $('.wpaicg_qdrant_endpoint').val();
                var collectionName = $('#selected_qdrant_collection').val();
                console.log(collectionName);
                var qdrantApiKey = $('.wpaicg_qdrant_api_key').val();
                if(openai_vectors !== ''){
                    qdrant_id = new Date().getTime(); // Unique ID generation as a number
                    var data = {
                        points: [
                            {
                                id: qdrant_id,
                                vector: openai_vectors,
                                payload: {} 
                            }
                        ]
                    };

                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php')?>',
                        data: {
                            action: 'wpaicg_troubleshoot_add_vector_qdrant',
                            nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',
                            data: JSON.stringify(data),
                            collection_name: collectionName,
                            endpoint: qdrantEndpoint
                        },
                        dataType: 'json',
                        type: 'POST',
                        beforeSend: function (){
                            wpaicgLoading($('.wpaicg_send_vectors_btn'))
                        },
                        success: function (res){
                            wpaicgRmLoading($('.wpaicg_send_vectors_btn'));
                            if(res && res.status && res.status.error) {
                                alert('Error: ' + res.status.error);
                            } else {
                                $('.wpaicg_qdrant_send_vectors_result').html('<p><strong>Response:</strong></p><pre>' + JSON.stringify(res, undefined, 4) + '</pre><button class="button wpaicg_qdrant_delete_vectors_btn" style="background: #cc0000;color: #fff;border-color: #cb0404;">Delete Vector</button>');
                            }
                        },
                        error: function (e){
                            wpaicgRmLoading($('.wpaicg_send_vectors_btn'))
                            $('.wpaicg_qdrant_send_vectors_result').html('<p><strong>Response:</strong></p><pre>' + e.responseText + '</pre>');
                        }
                    });
                }
                else{
                    alert('Please get vectors from OpenAI first')
                }
            }

        });
        $(document).on('click','.wpaicg_pinecone_delete_vectors_btn',function(ev){
            var btn = $(ev.currentTarget);
            var pineconeindexSelected = wpaicg_pinecone_index_list.find('select').val();
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php')?>',
                data: {
                    action: 'wpaicg_troubleshoot_delete_vector',
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>',
                    data: JSON.stringify({ids: [pinecone_id]}),
                    environment: 'https://' + pineconeindexSelected + '/vectors/delete'
                },
                dataType: 'json',
                type: 'POST',
                beforeSend: function () {
                    wpaicgLoading(btn)
                },
                success: function (res) {
                    wpaicgRmLoading(btn);
                    wpaicg_pinecone_delete_vectors_result.html('<p><strong>Response:</strong></p><pre>' + JSON.stringify(res, undefined, 4) + '</pre>');
                },
                error: function (e) {
                    wpaicgRmLoading(btn)
                    wpaicg_pinecone_delete_vectors_result.html('<p><strong>Response:</strong></p><pre>' + e.responseText + '</pre>')
                }
            })
        })
        $(document).on('click', '.wpaicg_qdrant_delete_vectors_btn', function(ev) {
            var btn = $(ev.currentTarget);
            var qdrantEndpoint = $('.wpaicg_qdrant_endpoint').val();
            var collectionName = $('#selected_qdrant_collection').val();
            console.log(qdrant_id);
            var deleteData = {
                points: [qdrant_id]
            };
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php') ?>',
                data: {
                    action: 'wpaicg_troubleshoot_delete_vector_qdrant',
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>',
                    data: JSON.stringify(deleteData),
                    collection_name: collectionName,
                    endpoint: qdrantEndpoint
                },
                dataType: 'json',
                type: 'POST',
                beforeSend: function() {
                    wpaicgLoading(btn);
                },
                success: function(res) {
                    wpaicgRmLoading(btn);
                    $('.wpaicg_qdrant_delete_vectors_result').html('<p><strong>Response:</strong></p><pre>' + JSON.stringify(res, undefined, 4) + '</pre>');
                },
                error: function(e) {
                    wpaicgRmLoading(btn);
                    $('.wpaicg_qdrant_delete_vectors_result').html('<p><strong>Error:</strong></p><pre>' + e.responseText + '</pre>');
                }
            });
        });
        // New function for Qdrant search
        function wpaicg_qdrant_search(query, topk, vectors) {
            var endpoint = '<?php echo admin_url('admin-ajax.php'); ?>';
            var collectionName = $('#selected_qdrant_collection').val();
            var qdrantEndpoint = $('.wpaicg_qdrant_endpoint').val();
            var topkForQdrant = parseInt(topk, 10);
            var data = {
                action: 'wpaicg_troubleshoot_search_qdrant',
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce'); ?>',
                query: JSON.stringify({ vector: vectors, limit: topkForQdrant }),
                collection_name: collectionName,
                endpoint: qdrantEndpoint
            };

            // Show loading spinner
            wpaicgLoadingSpinner($('.wpaicg_pinecone_search'), true);

            $.post(endpoint, data, function(res) {
                // Process response
                $('.wpaicg_pinecone_search_result').html('<pre>' + res + '</pre>');

                // Hide loading spinner
                wpaicgLoadingSpinner($('.wpaicg_pinecone_search'), false);
            }).fail(function(err) {
                $('.wpaicg_pinecone_search_result').html('<pre>Error: ' + err.responseText + '</pre>');

                // Hide loading spinner
                wpaicgLoadingSpinner($('.wpaicg_pinecone_search'), false);
            });
        }

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
    } );
</script>
