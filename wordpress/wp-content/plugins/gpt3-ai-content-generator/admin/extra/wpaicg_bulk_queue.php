<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

// Adjust the limit and offset for initial display
// Fetch posts ID, post title, and status
$posts = $wpdb->get_results("
    SELECT ID, post_title, post_status, post_mime_type, post_type
    FROM {$wpdb->posts}
    WHERE post_type IN ('wpaicg_tracking', 'wpaicg_twitter')
    ORDER BY post_date DESC
    LIMIT 5
");


// Get total number of posts
$total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wpaicg_tracking' OR post_type = 'wpaicg_twitter'");

// Posts per page
$posts_per_page = 5;

// Calculate total pages
$total_pages = ceil($total_posts / $posts_per_page);
$nonce = wp_create_nonce('gpt3_ajax_pagination_nonce');

$wpaicg_cron_added = get_option('_wpaicg_cron_added', '');
$wpaicg_cron_sheets_added = get_option('wpaicg_cron_sheets_added', '');
$wpaicg_cron_rss_added = get_option('_wpaicg_cron_rss_added', '');
$wpaicg_cron_tweet_added = get_option('wpaicg_cron_tweet_added', '');
$is_pro_plan = \WPAICG\wpaicg_util_core()->wpaicg_is_pro();

$wpaicg_cronjob_last_run_queue = get_option('_wpaicg_crojob_bulk_last_time','');
$humanReadableQueue = (!empty($wpaicg_cronjob_last_run_queue)) ? date('y-m-d H:i', $wpaicg_cronjob_last_run_queue) : 'NA';

$wpaicg_cronjob_last_run_sheets = get_option('wpaicg_crojob_sheets_last_time','');
$humanReadableSheets = (!empty($wpaicg_cronjob_last_run_sheets)) ? date('y-m-d H:i', $wpaicg_cronjob_last_run_sheets) : 'NA';

$wpaicg_cronjob_last_run_rss = get_option('_wpaicg_crojob_rss_last_time','');
$humanReadableRss = (!empty($wpaicg_cronjob_last_run_rss)) ? date('y-m-d H:i', $wpaicg_cronjob_last_run_rss) : 'NA';

$wpaicg_cronjob_last_run_tweet = get_option('wpaicg_cron_tweet_last_time','');
$humanReadableTweet = (!empty($wpaicg_cronjob_last_run_tweet)) ? date('y-m-d H:i', $wpaicg_cronjob_last_run_tweet) : 'NA';

// Schedule options
$schedule_options = [
    'none' => 'None',
    '5minutes' => 'Every 5 Minutes',
    '15minutes' => 'Every 15 Minutes',
    '30minutes' => 'Every 30 Minutes',
    '1hour' => 'Every 1 Hour',
    '2hours' => 'Every 2 Hours',
    '6hours' => 'Every 6 Hours',
    '12hours' => 'Every 12 Hours',
    '1day' => 'Every Day',
    '1week' => 'Every Week'
];

$schedule_queue = get_option('wpaicg_cron_queue_schedule', 'none');
$schedule_sheets = get_option('wpaicg_cron_sheets_schedule', 'none');
$schedule_rss = get_option('wpaicg_cron_rss_schedule', 'none');
$schedule_tweet = get_option('wpaicg_cron_tweet_schedule', 'none');

?>
<table class="wp-list-table widefat fixed striped table-view-list comments">
<thead>
        <tr>
            <th style="width: 60px;"><?php echo esc_html__('#', 'gpt3-ai-content-generator'); ?></th>
            <th style="width: 60px;"><?php echo esc_html__('Status', 'gpt3-ai-content-generator'); ?></th>
            <th style="width: 120px;"><?php echo esc_html__('Last Run', 'gpt3-ai-content-generator'); ?></th>
            <th style="width: 60px;"><?php echo esc_html__('Manual', 'gpt3-ai-content-generator'); ?></th>
            <th><?php echo esc_html__('Schedule', 'gpt3-ai-content-generator'); ?></th>
            <th><?php echo esc_html__('Cron', 'gpt3-ai-content-generator'); ?></th>
        </tr>
        </thead>
    <tbody>
        <tr>
            <td>Queue</td>
            <td style="color: <?php echo empty($wpaicg_cron_added) ? '#ff0000' : '#008000'; ?>;"><?php echo empty($wpaicg_cron_added) ? 'OFF' : 'ON'; ?></td>
            <td><?php echo esc_html($humanReadableQueue); ?></td>
            <td>
                <button id="triggerQueue" class="button button-primary" title="Trigger Queue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-play"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </button>
            </td>
            <td>
                <select id="scheduleQueue" class="schedule-dropdown" data-task="queue" style="width: 120px;">
                    <?php foreach ($schedule_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $schedule_queue); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><a href="#" class="view-instructions" data-instruction="queue">Instructions</a></td>
        </tr>
        <?php if ($is_pro_plan): ?>
        <tr>
            <td>Sheets</td>
            <td style="color: <?php echo empty($wpaicg_cron_sheets_added) ? '#ff0000' : '#008000'; ?>;"><?php echo empty($wpaicg_cron_sheets_added) ? 'OFF' : 'ON'; ?></td>
            <td><?php echo esc_html($humanReadableSheets); ?></td>
            <td>
                <button id="triggerGoogle" class="button button-primary" title="Trigger Google">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-play"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </button>
            </td>
            <td>
                <select id="scheduleSheets" class="schedule-dropdown" data-task="sheets" style="width: 120px;">
                    <?php foreach ($schedule_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $schedule_sheets); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><a href="#" class="view-instructions" data-instruction="google">Instructions</a></td>
        </tr>
        <tr>
            <td>RSS</td>
            <td style="color: <?php echo empty($wpaicg_cron_rss_added) ? '#ff0000' : '#008000'; ?>;"><?php echo empty($wpaicg_cron_rss_added) ? 'OFF' : 'ON'; ?></td>
            <td><?php echo esc_html($humanReadableRss); ?></td>
            <td>
                <button id="triggerRSS" class="button button-primary" title="Trigger RSS">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-play"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </button>
            </td>
            <td>
                <select id="scheduleRss" class="schedule-dropdown" data-task="rss" style="width: 120px;">
                    <?php foreach ($schedule_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $schedule_rss); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><a href="#" class="view-instructions" data-instruction="rss">Instructions</a></td>
        </tr>
        <tr>
            <td>Twitter</td>
            <td style="color: <?php echo empty($wpaicg_cron_tweet_added) ? '#ff0000' : '#008000'; ?>;"><?php echo empty($wpaicg_cron_tweet_added) ? 'OFF' : 'ON'; ?></td>
            <td><?php echo esc_html($humanReadableRss); ?></td>
            <td>
                <button id="triggerTwitter" class="button button-primary" title="Trigger Twitter">
                    <svg xmlns="http://www.w3.org/2000/svg" width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-play"><polygon points="5 3 19 12 5 21 5 3"></polygon></svg>
                </button>
            </td>
            <td>
                <select id="scheduleTwitter" class="schedule-dropdown" data-task="tweet" style="width: 120px;">
                    <?php foreach ($schedule_options as $value => $label) : ?>
                        <option value="<?php echo esc_attr($value); ?>" <?php selected($value, $schedule_tweet); ?>><?php echo esc_html($label); ?></option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><a href="#" class="view-instructions" data-instruction="twitter">Instructions</a></td>
        </tr>
        <?php endif; ?>
    </tbody>
</table>
<p></p>
<div class="content-area">
    <input type="hidden" id="gpt3_pagination_nonce" value="<?php echo wp_create_nonce('gpt3_ajax_pagination_nonce'); ?>">
    <table id="paginated-table" class="wp-list-table widefat fixed striped table-view-list comments">
        <thead>
        <tr>
            <th class="column-id"><?php echo esc_html__('ID', 'gpt3-ai-content-generator'); ?></th>
            <th class="column-batch"><?php echo esc_html__('Batch', 'gpt3-ai-content-generator'); ?></th>
            <th class="column-source"><?php echo esc_html__('Source', 'gpt3-ai-content-generator'); ?></th>
            <th class="column-status"><?php echo esc_html__('Status', 'gpt3-ai-content-generator'); ?></th>
            <th class="column-action"><?php echo esc_html__('Action', 'gpt3-ai-content-generator'); ?></th>
        </tr>
        </thead>
        <tbody>
            <?php foreach ( $posts as $post ) : ?>
                
                <tr id="post-row-<?php echo esc_attr($post->ID); ?>">
                    <td class="column-id"><?php echo esc_html($post->ID); ?></td>
                    <td class="column-batch">
                        <a href="javascript:void(0)" class="show-details" data-id="<?php echo esc_attr($post->ID); ?>">
                            <?php echo esc_html(strlen($post->post_title) > 20 ? substr($post->post_title, 0, 20).'...' : $post->post_title); ?>
                        </a>
                    </td>
                    <td class="column-source">
                        <?php
                        // Source display logic based on post_mime_type
                        if ($post->post_type == 'wpaicg_twitter') {
                            echo esc_html__('Twitter', 'gpt3-ai-content-generator');
                        } elseif (empty($post->post_mime_type) || $post->post_mime_type == 'editor') {
                            echo esc_html__('Bulk Editor', 'gpt3-ai-content-generator');
                        } elseif ($post->post_mime_type == 'csv') {
                            echo esc_html__('CSV', 'gpt3-ai-content-generator');
                        } elseif ($post->post_mime_type == 'rss') {
                            echo esc_html__('RSS', 'gpt3-ai-content-generator');
                        } elseif ($post->post_mime_type == 'sheets') {
                            echo esc_html__('Google Sheets', 'gpt3-ai-content-generator');
                        } elseif ($post->post_mime_type == 'multi') {
                            echo esc_html__('Copy-Paste', 'gpt3-ai-content-generator');
                        }
                        ?>
                    </td>
                    <td class="column-status">
                        <?php 
                        switch ($post->post_status) {
                            case 'pending':
                                echo '<span style="color: #000000;background: #f2ff05;border-radius: 5px;padding: 0 0.3em 0.1em;">Pending</span>';
                                break;
                            case 'publish':
                                echo '<span style="color: #ffffff;background: #12b11a;border-radius: 5px;padding: 0 0.3em 0.1em;">Completed</span>';
                                break;
                            case 'draft':
                                echo '<span style="color: #e20000;">Cancelled</span>';
                                break;
                            case 'trash':
                                echo '<span style="color: #e20000;">Cancelled</span>';
                                break;
                        }
                        ?>
                    </td>
                    <td class="column-action">
                        <button class="button button-primary delete-post" data-postid="<?php echo esc_attr($post->ID); ?>">Delete</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div class="gpt3-pagination" style="margin-top: 0.5em;">
        <?php for($i = 1; $i <= $total_pages; $i++): ?>
            <a href="#" data-page="<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php endfor; ?>
    </div>
    <p></p>
    <div style="display: flex; gap: 8px; align-items: center;">
    <button id="reload-items" class="button button-secondary" title="Refresh" style="display: flex; align-items: center;">
        <svg id="reload-icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-refresh-ccw" style="vertical-align: middle; margin-right: 4px;">
            <polyline points="1 4 1 10 7 10"></polyline>
            <polyline points="23 20 23 14 17 14"></polyline>
            <path d="M20.49 9A9 9 0 0 0 5.64 5.64L1 10m22 4l-4.64 4.36A9 9 0 0 1 3.51 15"></path>
        </svg>
        <span><?php echo esc_html__('Refresh', 'gpt3-ai-content-generator'); ?></span>
    </button>

    <button id="clear-button" class="button button-primary" title="Clear Tasks" style="display: flex; align-items: center; color: #fff; background: #df0707; border-color: #df0707;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-trash-2" style="vertical-align: middle; margin-right: 4px;">
            <polyline points="3 6 5 6 21 6"></polyline>
            <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
            <line x1="10" y1="11" x2="10" y2="17"></line>
            <line x1="14" y1="11" x2="14" y2="17"></line>
        </svg>
        <span><?php echo esc_html__('Clear', 'gpt3-ai-content-generator'); ?></span>
    </button>

    <div id="clear-options" style="display: none; position: absolute; background: white; border: 1px solid #ccc; padding: 10px;">
        <button id="clear-all" class="button"><?php echo esc_html__('Clear All', 'gpt3-ai-content-generator'); ?></button>
        <button id="clear-completed" class="button"><?php echo esc_html__('Clear Completed', 'gpt3-ai-content-generator'); ?></button>
        <button id="clear-pending" class="button"><?php echo esc_html__('Clear Pending', 'gpt3-ai-content-generator'); ?></button>
        <button id="clear-cancelled" class="button"><?php echo esc_html__('Clear Cancelled', 'gpt3-ai-content-generator'); ?></button>
    </div>

    <button id="restart-queue" class="button button-primary" title="Restart the Queue" style="display: flex; align-items: center;">
        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-x-circle" style="vertical-align: middle; margin-right: 4px;">
            <circle cx="12" cy="12" r="10"></circle>
            <line x1="15" y1="9" x2="9" y2="15"></line>
            <line x1="9" y1="9" x2="15" y2="15"></line>
        </svg>
        <span><?php echo esc_html__('Restart', 'gpt3-ai-content-generator'); ?></span>
    </button>
</div>

</div>

<script>
    jQuery(document).ready(function($) {

        const clearButton = $('#clear-button');
        const clearOptions = $('#clear-options');

        clearButton.on('click', function(e) {
            e.stopPropagation();
            clearOptions.toggle();
        });

        $(document).on('click', function() {
            clearOptions.hide();
        });

        $('#clear-all').on('click', function() {
            if (confirm('Are you sure you want to delete all tasks? This action cannot be undone.')) {
                var nonce = $('#gpt3_pagination_nonce').val();
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'delete_all_posts_action',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('All tasks deleted successfully.');
                            $('#paginated-table tbody').empty();
                            // Optionally, refresh the page or make additional updates as needed
                        } else {
                            alert('Failed to delete tasks.');
                        }
                    }
                });
            }
            clearOptions.hide();
        });

        $('#clear-completed').on('click', function() {
            if (confirm('Are you sure you want to delete all completed tasks? This action cannot be undone.')) {
                var nonce = $('#gpt3_pagination_nonce').val();
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'delete_completed_posts_action',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('All completed tasks deleted successfully.');
                            $('#paginated-table tbody').empty();
                            $('#reload-items').trigger('click');
                        } else {
                            alert('Failed to delete tasks.');
                        }
                    }
                });
            }
            clearOptions.hide();
        });

        $('#clear-pending').on('click', function() {
            if (confirm('Are you sure you want to delete all pending tasks? This action cannot be undone.')) {
                var nonce = $('#gpt3_pagination_nonce').val();
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'delete_pending_posts_action',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('All pending tasks deleted successfully.');
                            $('#paginated-table tbody').empty();
                            $('#reload-items').trigger('click');
                        } else {
                            alert('Failed to delete tasks.');
                        }
                    }
                });
            }
            clearOptions.hide();
        });

        $('#clear-cancelled').on('click', function() {
            if (confirm('Are you sure you want to delete all cancelled tasks? This action cannot be undone.')) {
                var nonce = $('#gpt3_pagination_nonce').val();
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'delete_cancelled_posts_action',
                        nonce: nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('All cancelled tasks deleted successfully.');
                            $('#paginated-table tbody').empty();
                            $('#reload-items').trigger('click');
                        } else {
                            alert('Failed to delete tasks.');
                        }
                    }
                });
            }
            clearOptions.hide();
        });

        // Function to save schedule
        function saveSchedule(task, value) {
            $.ajax({
                url: '<?php echo admin_url('admin-ajax.php'); ?>',
                type: 'post',
                data: {
                    action: 'save_schedule',
                    task: task,
                    value: value,
                    nonce: '<?php echo wp_create_nonce('save_schedule_nonce'); ?>'
                },
                success: function(response) {
                    if (!response.success) {
                        alert('Failed to save schedule.');
                    }
                }
            });
        }

        // Handle schedule change
        $('.schedule-dropdown').on('change', function() {
            var task = $(this).data('task');
            var value = $(this).val();
            saveSchedule(task, value);
        });
        
        // Consolidate bindShowDetailsEvent function
        function bindShowDetailsEvent() {
            // Unbind any previous event handlers to avoid multiple bindings
            $('.show-details').off('click').on('click', function(e) {
                e.preventDefault();
                var batchId = $(this).data('id');
                var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
                var $clickedRow = $(this).closest('tr');

                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'fetch_batch_details',
                        batchId: batchId,
                        nonce: '<?php echo wp_create_nonce('fetch_batch_details_nonce'); ?>'
                    },
                    beforeSend: function() {
                        $('.details-row').remove();
                        $clickedRow.after('<tr class="details-row"><td colspan="5">Loading...</td></tr>');
                    },
                    success: function(response) {
                        if (response.success) {
                            $('.details-row').replaceWith('<tr class="details-row"><td colspan="5">' + response.data + '</td></tr>');
                        } else {
                            $('.details-row').remove();
                            alert('Failed to load details.');
                        }
                    },
                    error: function() {
                        $('.details-row').remove();
                        alert('Failed to load details.');
                    }
                });
            });
        }

        // Call bindShowDetailsEvent initially to bind event to any existing .show-details links
        bindShowDetailsEvent();

        // Handle pagination link clicks
        $(document).on('click', '.gpt3-pagination a', function(e){
            e.preventDefault();
            var page = $(this).data('page');
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#gpt3_pagination_nonce').val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'gpt3_pagination',
                    page: page,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-table tbody').html(response.data.content);
                        $('.gpt3-pagination').replaceWith(response.data.pagination);
                        bindShowDetailsEvent(); // Re-bind the show details event
                    }
                }
            });
        });

        // Handle reload items button click
        $('#reload-items').on('click', function(e) {
            e.preventDefault();
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#gpt3_pagination_nonce').val();
            $('#reload-icon').addClass('spinrefresh'); 

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'reload_items',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-table tbody').html(response.data.content);
                        bindShowDetailsEvent(); // Re-bind the show details event
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


        // Handle restart queue button click
        $('#restart-queue').on('click', function(e) {
            e.preventDefault();
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>'; // WordPress AJAX

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'restart_queue_process'
                },
                success: function(response) {
                    alert('Queue restarted successfully.');
                },
                error: function() {
                    alert('Error restarting the queue.');
                }
            });
        });

        // Handle delete button clicks
        $(document).on('click', '#paginated-table .delete-post', function(e) {
            e.preventDefault();
            var $this = $(this);
            var postid = $this.data('postid');
            var nonce = $('#gpt3_pagination_nonce').val();
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';

            if (confirm('Are you sure you want to delete this post?')) {
                $.ajax({
                    url: ajaxurl,
                    type: 'post',
                    data: {
                        action: 'delete_post_action',
                        nonce: nonce,
                        postid: postid
                    },
                    success: function(response) {
                        if (response.success) {
                            // Remove the row from the table
                            $('#post-row-' + postid).fadeOut(400, function() { $(this).remove(); });
                        } else {
                            alert('Failed to delete post.');
                        }
                    }
                });
            }
        });

        function triggerCron(task) {
            $.ajax({
                type: "POST",
                url: ajaxurl,
                data: {
                    action: "trigger_wpaicg_cron",
                    task: task
                },
                success: function(response) {
                    alert(response.data);
                },
                error: function() {
                    alert("Failed to trigger the task.");
                }
            });
        }

        $('#triggerQueue').click(function() { triggerCron("wpaicg_cron=yes"); });
        $('#triggerGoogle').click(function() { triggerCron("wpaicg_sheets=yes"); });
        $('#triggerRSS').click(function() { triggerCron("wpaicg_rss=yes"); });
        $('#triggerTwitter').click(function() { triggerCron("wpaicg_tweet=yes"); });

    });
</script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const viewInstructionsLinks = document.querySelectorAll('.view-instructions');

        viewInstructionsLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();

                // Check if the next sibling is an instruction row and toggle visibility
                const nextSibling = this.closest('tr').nextElementSibling;
                if (nextSibling && nextSibling.classList.contains('instruction-row')) {
                    nextSibling.remove(); // Remove the instruction row if it already exists
                    return; // Exit the function to not add it again
                }

                // Remove any existing instruction row from other "View Instructions" clicks
                document.querySelectorAll('.instruction-row').forEach(row => row.remove());

                // Identify which instruction to display
                const instructionType = this.dataset.instruction;
                let cronCommand = '';
                let instructionText = '<p>Use below command to set up your cron job on the server. Read the guide <a href="https://docs.aipower.org/docs/AutoGPT/gpt-agents#cron-job-setup" target="_blank">here</a>.</p>';
                switch (instructionType) {
                    case 'queue':
                        cronCommand = '* * * * * php <?php echo esc_html(ABSPATH) ?>index.php -- wpaicg_cron=yes';
                        break;
                    case 'google':
                        cronCommand = '* * * * * php <?php echo esc_html(ABSPATH) ?>index.php -- wpaicg_sheets=yes';
                        break;
                    case 'rss':
                        cronCommand = '* * * * * php <?php echo esc_html(ABSPATH) ?>index.php -- wpaicg_rss=yes';
                        break;
                    case 'twitter':
                        cronCommand = '* * * * * php <?php echo esc_html(ABSPATH) ?>index.php -- wpaicg_tweet=yes';
                        break;
                }

                // Create and insert the instruction row below the current row
                const instructionRow = document.createElement('tr');
                instructionRow.className = 'instruction-row';
                instructionRow.innerHTML = `<td colspan="6"><div class="wpaicg-code-container">${instructionText}<br><code>${cronCommand}</code></div></td>`;
                this.closest('tr').after(instructionRow);
            });
        });
    });
</script>


