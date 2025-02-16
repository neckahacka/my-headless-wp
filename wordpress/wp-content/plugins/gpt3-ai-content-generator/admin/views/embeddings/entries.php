<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Get the posts per page option from the database or set default
$posts_per_page = get_option('wpaicg_knowledge_builder_page', 3);

// Ensure the page number is at least 1
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;

// Calculate the offset
$offset = ($page - 1) * $posts_per_page;

// Retrieve the embeddings, ensuring a valid, non-negative offset from wpaicg_embeddings and wpaicg_pdfadmin
global $wpdb;
$posts = $wpdb->get_results($wpdb->prepare("
    SELECT ID, post_title, post_status, post_mime_type, post_type, post_date
    FROM {$wpdb->posts}
    WHERE post_type IN ('wpaicg_embeddings', 'wpaicg_pdfadmin','wpaicg_builder')
    ORDER BY post_date DESC
    LIMIT %d OFFSET %d", $posts_per_page, $offset));

// Get the total number of posts to calculate total pages for pagination
$total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('wpaicg_embeddings', 'wpaicg_pdfadmin','wpaicg_builder')");

// Calculate total pages
$total_pages = ceil($total_posts / $posts_per_page);

// Nonce for AJAX requests
$nonce = wp_create_nonce('gpt4_ajax_pagination_nonce');
?>

<div class="custom-modal-embedding-overlay">
    <div class="custom-modal-embedding-window">
        <div class="custom-modal-embedding-close">X</div>
        <div class="custom-modal-embedding-title"></div>
        <div class="custom-modal-embedding-content"></div>
    </div>
</div>
<p>
<div id="wpaicg-embedding-success-message" class="wpaicg-embedding-success-message">
    <?php echo esc_html__('Record saved successfully','gpt3-ai-content-generator')?>
</div>
<div id="wpaicg-embedding-delete-message" class="wpaicg-embedding-delete-message">
    <?php echo esc_html__('Record deleted successfully','gpt3-ai-content-generator')?>
</div>
<div id="wpaicg-embedding-reindex-message" class="wpaicg-embedding-reindex-message">
    <?php echo esc_html__('Record queued successfully','gpt3-ai-content-generator')?>
</div>
<div id="wpaicg-embedding-delete-all-message" class="wpaicg-embedding-delete-all-message">
    <?php echo esc_html__('All records deleted successfully','gpt3-ai-content-generator')?>
</div>
<div id="wpaicg_embedding_error_msg" class="wpaicg_embedding_error_msg" style="display: none;"></div>
</p>
<p></p>
<div class="search-area" style="margin-bottom: 1em;display: flex;justify-content: space-between;">
    <input type="text" id="search-input" placeholder="<?php echo esc_attr__('Search...', 'gpt3-ai-content-generator'); ?>" style="width: 100%; max-width: 300px;">
    <select id="results-per-page" name="results-per-page">
        <?php
        $options = [3, 5, 10, 25, 50, 100, 500, 1000];
        foreach ($options as $option) {
            $selected = ($option == $posts_per_page) ? 'selected' : '';
            echo "<option value='$option' $selected>$option</option>";
        }
        ?>
    </select>
</div>

<div class="content-area">
    <input type="hidden" id="gpt4_pagination_nonce" value="<?php echo wp_create_nonce('gpt4_ajax_pagination_nonce'); ?>">
    <div class="wpaicg-table-responsive">
        <table id="paginated-table" class="wp-list-table widefat striped">
            <thead>
            <tr>
                <th class="column-id"><?php echo esc_html__('ID', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-content"><?php echo esc_html__('Content', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-details"><?php echo esc_html__('Details', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-source"><?php echo esc_html__('Source', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-date"><?php echo esc_html__('Date', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-action"><?php echo esc_html__('Action', 'gpt3-ai-content-generator'); ?></th>
            </tr>
            </thead>
            <tbody>
                <?php foreach ( $posts as $post ) : ?>
                    <?php echo \WPAICG\WPAICG_Embeddings::get_instance()->generate_table_row($post); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php echo \WPAICG\WPAICG_Embeddings::get_instance()->generate_smart_pagination($page, $total_pages); ?>
    <p></p>
    <button id="reload-items" class="button button-secondary" title="Refresh">
        <svg id="reload-icon" xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-ccw"><polyline points="1 4 1 10 7 10"></polyline><polyline points="23 20 23 14 17 14"></polyline><path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path></svg>
    </button>
    <button id="delete-all-posts" class="button button-primary" title="Delete All Data">
        <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path><line x1="10" y1="11" x2="10" y2="17"></line><line x1="14" y1="11" x2="14" y2="17"></line></svg>
    </button>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        function wpaicgLoading(btn) {
            btn.disabled = true;
            if (!btn.querySelector('.spinner')) {
                var spinnerSpan = document.createElement('span');
                spinnerSpan.className = 'spinner';
                btn.appendChild(spinnerSpan);
            }
            btn.querySelector('.spinner').style.visibility = 'unset';
        }

        function wpaicgRmLoading(btn) {
            btn.disabled = false;
            var spinner = btn.querySelector('.spinner');
            if (spinner) {
                spinner.parentNode.removeChild(spinner);
            }
        }

        document.getElementById('wpaicg_embeddings_form').addEventListener('submit', function(e) {
            e.preventDefault();
            var form = e.currentTarget;
            var btn = form.querySelector('button');
            var has_empty = false;
            var content = document.getElementById('wpaicg-embeddings-content').value.trim();
            if (content === '') {
                alert('Please insert content');
                return false; // Exit the function if content is empty
            }
            var data = new FormData(form);
            var xhr = new XMLHttpRequest();
            xhr.open('POST', 'admin-ajax.php', true); // Modify URL as needed
            xhr.onreadystatechange = function() {
                if (xhr.readyState == 4 && xhr.status == 200) {
                    var res = JSON.parse(xhr.responseText);
                    wpaicgRmLoading(btn);
                    if (res.status === 'success') {
                        // Display success message
                        document.querySelector('.wpaicg-embedding-success-message').style.display = 'block';
                        setTimeout(function() {
                            document.querySelector('.wpaicg-embedding-success-message').style.display = 'none';
                        }, 10000);

                        // Clear the content textarea
                        document.getElementById('wpaicg-embeddings-content').value = '';

                        // trigger reload
                        document.getElementById('reload-items').click();

                    } else {
                        var messageDiv = document.getElementById('wpaicg_embedding_error_msg');
                        messageDiv.textContent = res.msg; // Update the message text
                        messageDiv.style.display = 'block'; // Make the div visible
                        setTimeout(function() {
                            messageDiv.style.display = 'none'; // Hide the div after 2 seconds
                        }, 10000);
                    }
                }
            };
            xhr.onerror = function() {
                wpaicgRmLoading(btn);
                alert('Something went wrong');
            };
            xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
            xhr.send(data);

            wpaicgLoading(btn);
        });
    });
</script>
<script>
    jQuery(document).ready(function($) {
        
        // Handle pagination link clicks
        $(document).on('click', '.gpt4-pagination a', function(e){
            e.preventDefault();
            var page = $(this).data('page');
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#gpt4_pagination_nonce').val();
            var searchTerm = $('#search-input').val();
            var resultsPerPage = $('#results-per-page').val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'gpt4_pagination',
                    page: page,
                    nonce: nonce,
                    search_term: searchTerm,
                    results_per_page: resultsPerPage
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-table tbody').html(response.data.content);
                        $('.gpt4-pagination').replaceWith(response.data.pagination);
                    }
                }
            });
        });

        // Handle reload items button click
        $('#reload-items').on('click', function(e) {
            e.preventDefault();
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#gpt4_pagination_nonce').val();
            var searchTerm = $('#search-input').val();
            var resultsPerPage = $('#results-per-page').val();
            $('#reload-icon').addClass('spinrefresh'); 

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'reload_items_embeddings',
                    nonce: nonce,
                    search_term: searchTerm,
                    results_per_page: resultsPerPage
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-table tbody').html(response.data.content);
                        $('.gpt4-pagination').html(response.data.pagination);
                    } else {
                        alert('Failed to reload items.');
                    }
                    $('#reload-icon').removeClass('spinrefresh');
                },
                error: function() {
                    alert('Failed to reload items.');
                    $('#reload-icon').removeClass('spinrefresh'); // Ensure spinning stops on error
                }
            });
        });

        // Debounce function to limit the rate of execution
        function debounce(func, wait, immediate) {
            var timeout;
            return function() {
                var context = this, args = arguments;
                var later = function() {
                    timeout = null;
                    if (!immediate) func.apply(context, args);
                };
                var callNow = immediate && !timeout;
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
                if (callNow) func.apply(context, args);
            };
        }

        // Handle search input keyup event with debounce
        $('#search-input').on('keyup', debounce(function() {
            var searchTerm = $(this).val();
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>'; // Your AJAX handler URL
            var nonce = $('#gpt4_pagination_nonce').val(); // Use your existing nonce for security
            var resultsPerPage = $('#results-per-page').val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'search_embeddings_content', // This action needs to be handled in your PHP code
                    search_term: searchTerm,
                    nonce: nonce,
                    page: 1,
                    results_per_page: resultsPerPage
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-table tbody').html(response.data.content);
                        $('.gpt4-pagination').html(response.data.pagination); // Update pagination as well
                    } else {
                        alert('No results found.');
                    }
                },
                error: function() {
                    alert('Search failed. Please try again.');
                }
            });
        }, 250)); // 250ms debounce time

        // Handle results per page change
        $('#results-per-page').on('change', function() {
            var resultsPerPage = $(this).val();
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#gpt4_pagination_nonce').val();
            var searchTerm = $('#search-input').val();
            
            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'set_results_per_page',
                    results_per_page: resultsPerPage,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#reload-items').click(); // Reload items to apply the new results per page
                    } else {
                        alert('Failed to set results per page.');
                    }
                }
            });
        });
    });
</script>
<script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.wpaicg-embedding-content', function() {
            var content = $(this).attr('data-content');
            content = content.replace(/\n/g, "<br />");

            $('.custom-modal-embedding-title').html('Embedded Content');
            $('.custom-modal-embedding-content').html(content);

            $('.custom-modal-embedding-overlay').show();
        });

        // Close the modal when clicking on the overlay
        $('.custom-modal-embedding-overlay').on('click', function(e) {
            if (e.target !== this) {
                return;
            }
            $(this).hide();
        });

        // Close the modal when clicking the close button
        $('.custom-modal-embedding-close').on('click', function() {
            $('.custom-modal-embedding-overlay').hide();
        });
    });
</script>
<script>
    jQuery(document).ready(function($) {
        $(document).on('click', '.btn-delete-post', function() {
            var conf = confirm('<?php echo esc_js(__('Are you sure you want to delete this data?', 'gpt3-ai-content-generator')); ?>');
            if (conf) {
                var postId = $(this).data('post-id');
                var btn = $(this);
                var ids = [];
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: {
                        action: 'wpaicg_delete_embeddings',
                        ids: [postId],
                        nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce'); ?>'
                    },
                    beforeSend: function() {
                        btn.prop('disabled', true);
                    },
                    success: function(response) {
                        // Assuming the response is a stringified JSON, you might need to parse it
                        var res = typeof response === 'string' ? JSON.parse(response) : response;
                        
                        // Check if the response status is success
                        if (res.status === "success") {
                            $('#post-row-' + postId).fadeOut(400, function() {
                                $(this).remove();
                            });
                            // Display success message
                            document.querySelector('.wpaicg-embedding-delete-message').style.display = 'block';
                            setTimeout(function() {
                                document.querySelector('.wpaicg-embedding-delete-message').style.display = 'none';
                            }, 2000);

                            // trigger reload
                            document.getElementById('reload-items').click();
                        } else {
                            alert('<?php echo esc_js(__('Failed to delete post.', 'gpt3-ai-content-generator')); ?>');
                        }
                        btn.prop('disabled', false);
                    },
                    error: function() {
                        alert('<?php echo esc_js(__('An error occurred.', 'gpt3-ai-content-generator')); ?>');
                        btn.prop('disabled', false);
                    }
                });
            }
        });
});
</script>
<script>
    jQuery(document).ready(function($) {
        $('#delete-all-posts').click(function(e) {
            e.preventDefault();
            var confirmDeletion = confirm('Are you sure you want to delete all data?');
            if (!confirmDeletion) {
                return;
            }

            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'POST',
                dataType: 'json',
                data: {
                    action: 'wpaicg_delete_all_embeddings',
                    nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce'); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-table tbody').empty();
                        // Display success message
                        document.querySelector('.wpaicg-embedding-delete-all-message').style.display = 'block';
                            setTimeout(function() {
                                document.querySelector('.wpaicg-embedding-delete-all-message').style.display = 'none';
                            }, 2000);

                        // trigger reload
                        document.getElementById('reload-items').click();
                    } else {
                        alert('Failed to delete data: ' + response.data.message);
                    }
                },
                error: function() {
                    alert('An error occurred while trying to delete all data.');
                }
            });
        });
});
</script>