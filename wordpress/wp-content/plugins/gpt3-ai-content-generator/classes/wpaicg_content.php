<?php

namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( '\\WPAICG\\WPAICG_Content' ) ) {
    final class WPAICG_Content
    {
        private static  $instance = null ;
        public  $wpaicg_token_price = 0.02 / 1000 ;
        public  $wpaicg_limit_titles = 5 ;
        public  $wpaicg_extra_titles = 15 ;

        public static function get_instance()
        {
            if ( is_null( self::$instance ) ) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            if(wpaicg_util_core()->wpaicg_is_pro()){
                $this->wpaicg_limit_titles = 100;
            }
            add_action( 'admin_menu', array( $this, 'wpaicg_content_menu' ) );
            add_action( 'wp_ajax_wpaicg_save_draft_post_extra', array( $this, 'wpaicg_save_draft_post' ) );
            add_action( 'wp_ajax_wpaicg_bulk_generator', array( $this, 'wpaicg_bulk_save' ) );
            add_action( 'wp_ajax_wpaicg_bulk_save_editor', array( $this, 'wpaicg_bulk_save_editor' ) );
            add_action( 'wp_ajax_wpaicg_bulk_cancel', array( $this, 'wpaicg_bulk_cancel' ) );
            add_action( 'wp_ajax_wpaicg_bulk_status', array( $this, 'wpaicg_bulk_status' ) );
            add_action( 'wp_ajax_wpaicg_read_csv', array( $this, 'wpaicg_read_csv' ) );
            add_action( 'wp_ajax_wpaicg_speech_record', array( $this, 'wpaicg_speech_record' ) );
            add_action( 'wp_ajax_gpt3_pagination', array( $this, 'gpt3_ajax_pagination' ) );
            add_action( 'wp_ajax_nopriv_gpt3_pagination', 'gpt3_ajax_pagination');
            add_action( 'wp_ajax_delete_post_action', array( $this, 'delete_post_by_ajax' ) );
            add_action( 'wp_ajax_delete_all_posts_action', array($this, 'delete_all_wpaicg_posts'));
            add_action('wp_ajax_delete_completed_posts_action', array($this, 'delete_completed_wpaicg_posts'));
            add_action('wp_ajax_delete_pending_posts_action', array($this, 'delete_pending_wpaicg_posts'));
            add_action('wp_ajax_delete_cancelled_posts_action', array($this, 'delete_cancelled_wpaicg_posts'));
            add_action('wp_ajax_reload_items', array($this, 'reload_items'));
            add_action('wp_ajax_fetch_batch_details', array($this, 'fetch_batch_details'));
            add_action('wp_ajax_trigger_wpaicg_cron', array($this, 'trigger_wpaicg_cron'));
            add_action('wp_ajax_restart_queue_process', array($this, 'restart_queue_process'));
            add_action('wp_ajax_save_schedule', array($this, 'wpaicg_save_schedule'));
            // Register cron hooks and schedule them
            add_filter('cron_schedules', array($this, 'wpaicg_custom_cron_schedules'));
            $this->wpaicg_register_cron_hooks();
        }

        public function wpaicg_custom_cron_schedules($schedules) {
            $schedules['every_5_minutes'] = array(
                'interval' => 300, // 5 minutes in seconds
                'display'  => __('Every 5 Minutes', 'wpaicg')
            );
            $schedules['every_15_minutes'] = array(
                'interval' => 900, // 15 minutes in seconds
                'display'  => __('Every 15 Minutes', 'wpaicg')
            );
            $schedules['every_30_minutes'] = array(
                'interval' => 1800, // 30 minutes in seconds
                'display'  => __('Every 30 Minutes', 'wpaicg')
            );
            $schedules['every_2_hours'] = array(
                'interval' => 7200, // 2 hours in seconds
                'display'  => __('Every 2 Hours', 'wpaicg')
            );
            $schedules['every_6_hours'] = array(
                'interval' => 21600, // 6 hours in seconds
                'display'  => __('Every 6 Hours', 'wpaicg')
            );
            $schedules['every_12_hours'] = array(
                'interval' => 43200, // 12 hours in seconds
                'display'  => __('Every 12 Hours', 'wpaicg')
            );

            return $schedules;
        }

        public function wpaicg_save_schedule() {
            check_ajax_referer('save_schedule_nonce', 'nonce');
        
            $task = sanitize_text_field($_POST['task']);
            $value = sanitize_text_field($_POST['value']);
            $option_name = 'wpaicg_cron_' . $task . '_schedule';
        
            if (update_option($option_name, $value)) {
                // Reschedule cron jobs when schedule is updated
                $this->wpaicg_clear_scheduled_cron_jobs();
                $this->wpaicg_schedule_cron_jobs();
                wp_send_json_success();
            } else {
                wp_send_json_error();
            }
        }

        public function wpaicg_trigger_cron_task($task) {
            $cron_url = $this->wpaicg_get_cron_url($task);

            wp_remote_get($cron_url, array(
                'timeout' => 1, // Wait for 1 second before aborting the request
                'blocking' => false, // Non-blocking mode
            ));
        }

        private function wpaicg_get_cron_url($task) {
            switch ($task) {
                case 'queue':
                    return home_url('/index.php?wpaicg_cron=yes');
                case 'sheets':
                    return home_url('/index.php?wpaicg_sheets=yes');
                case 'rss':
                    return home_url('/index.php?wpaicg_rss=yes');
                case 'tweet':
                    return home_url('/index.php?wpaicg_tweet=yes');
                case 'builder':
                    return home_url('/index.php?wpaicg_builder=yes');
                default:
                    return '';
            }
        }

        public function wpaicg_schedule_cron_jobs() {
            $tasks = ['queue', 'sheets', 'rss', 'tweet', 'builder'];
            $schedules = [
                'none' => null,
                '5minutes' => 'every_5_minutes',
                '15minutes' => 'every_15_minutes',
                '30minutes' => 'every_30_minutes',
                '2hours' => 'every_2_hours',
                '6hours' => 'every_6_hours',
                '12hours' => 'every_12_hours',
                '1hour' => 'hourly',
                '1day' => 'daily',
                '1week' => 'weekly'
            ];

            foreach ($tasks as $task) {
                $schedule = get_option('wpaicg_cron_' . $task . '_schedule', 'none');
                $wp_schedule = isset($schedules[$schedule]) ? $schedules[$schedule] : null;

                // Clear existing schedule first
                $this->wpaicg_clear_scheduled_cron_job($task);

                // Only schedule if a valid schedule is set and it's not 'none'
                if ($wp_schedule) {
                    wp_schedule_event(time(), $wp_schedule, 'wpaicg_cron_trigger_' . $task);
                }
            }
        }

        public function wpaicg_clear_scheduled_cron_jobs() {
            $tasks = ['queue', 'sheets', 'rss', 'tweet', 'builder'];

            foreach ($tasks as $task) {
                $this->wpaicg_clear_scheduled_cron_job($task);
            }
        }

        public function wpaicg_clear_scheduled_cron_job($task) {
            while ($timestamp = wp_next_scheduled('wpaicg_cron_trigger_' . $task)) {
                wp_unschedule_event($timestamp, 'wpaicg_cron_trigger_' . $task);
            }
        }

        public function wpaicg_register_cron_hooks() {
            $tasks = ['queue', 'sheets', 'rss', 'tweet', 'builder'];

            foreach ($tasks as $task) {
                add_action('wpaicg_cron_trigger_' . $task, function() use ($task) {
                    $this->wpaicg_trigger_cron_task($task);
                });
            }
        }

        public function restart_queue_process() {
            // Security check, ensure user has the capability to perform this action
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized operation');
                return;
            }

            $files = [
                WPAICG_PLUGIN_DIR . 'wpaicg_running.txt',
                WPAICG_PLUGIN_DIR . '/wpaicg_sheets.txt',
                WPAICG_PLUGIN_DIR . '/wpaicg_rss.txt',
                WPAICG_PLUGIN_DIR . 'wpaicg_tweet.txt',
            ];

            foreach ($files as $file) {
                if (file_exists($file)) {
                    @unlink($file);
                }
            }

            wp_send_json_success('Queue restarted successfully.');
        }

        public function trigger_wpaicg_cron() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error('Unauthorized');
                return;
            }
        
            // Retrieve the task parameter from the AJAX request
            $task = isset($_POST['task']) ? $_POST['task'] : '';
        
            // Construct the URL based on the task parameter
            $cron_url = home_url('/index.php?' . $task);
        
            // Make a non-blocking, timeout-limited request
            wp_remote_get($cron_url, array(
                'timeout' => 1, // Wait for 1 second before aborting the request
                'blocking' => false, // Non-blocking mode
            ));
        
            wp_send_json_success('Trigger initiated. Please refresh the page to see the updated status.');
        }


        public function fetch_batch_details() {
            check_ajax_referer('fetch_batch_details_nonce', 'nonce');
        
            $batch_id = isset($_POST['batchId']) ? intval($_POST['batchId']) : 0;
            if (!$batch_id) {
                wp_send_json_error('Invalid batch ID');
            }
        
            $batch_items = get_posts(array(
                'post_type' => 'wpaicg_bulk',
                'post_status' => array('publish', 'pending', 'draft', 'trash', 'inherit'),
                'post_parent' => $batch_id,
                'posts_per_page' => -1
            ));
            
            $pricing = \WPAICG\WPAICG_Util::get_instance()->model_pricing;
        
            $html = '';
            if ($batch_items) {
                foreach ($batch_items as $item) {
                    $status = esc_html($item->post_status);
                    $title = esc_html($item->post_title);
                    $run = get_post_meta($item->ID, '_wpaicg_generator_run', true);
                    $formatted_run = $this->format_duration($run);
                    $length = get_post_meta($item->ID, '_wpaicg_generator_length', true);
                    $token = get_post_meta($item->ID, '_wpaicg_generator_token', true);
                    $ai_model = get_post_meta($item->ID, 'wpaicg_ai_model', true);

                    // Calculate cost
                    $cost = 'N/A'; // Default value
                    if (!empty($token) && array_key_exists($ai_model, $pricing)) {
                        $cost_per_1k_tokens = $pricing[$ai_model];
                        $cost = '$' . number_format($token * $cost_per_1k_tokens / 1000, 5);
                    }
        
                    if ($status === 'publish') {
                        $published_post_id = get_post_meta($item->ID, '_wpaicg_generator_post', true);
                        $edit_post_link = get_edit_post_link($published_post_id);
                        $title = $edit_post_link ? "<a href='" . esc_url($edit_post_link) . "' target='_blank'>$title</a>" : $title;
                    }
        
                    // Assuming 'Pending', 'In Progress', 'Completed', 'Cancelled' as possible $status_text values
                    $status_text_map = [
                        'publish' => '<span style="color: #ffffff;background: #12b11a;border-radius: 5px;padding: 0 0.3em 0.1em;">Completed</span>',
                        'pending' => '<span style="color: #000000;background: #f2ff05;border-radius: 5px;padding: 0 0.3em 0.1em;">Pending</span>',
                        'draft' => '<span style="color: #ffffff;background: #ffc300;border-radius: 5px;padding: 0 0.3em 0.1em;">In Progress</span>',
                        'trash' => '<span style="color: #ffffff;background: #e20000;border-radius: 5px;padding: 0 0.3em 0.1em;">Cancelled</span>',
                        'inherit' => '<span style="color: #ffffff;background: #e20000;border-radius: 5px;padding: 0 0.3em 0.1em;">Cancelled</span>',
                    ];

                    $status_text = array_key_exists($status, $status_text_map) ? $status_text_map[$status] : 'Unknown';

                    // Show only Title and Status for non-completed items
                    if ($status !== 'publish') {
                        $html .= "<div><strong>Title:</strong> $title</div>";
                        $html .= "<div><strong>Status:</strong> $status_text</div>";
                        
                        // Check for cancelled status and display error if present
                        if ($status === 'trash' || $status === 'inherit') {
                            $error = get_post_meta($item->ID, '_wpaicg_error', true);
                            if ($error) {
                                $html .= "<div style='display: block;white-space: break-spaces;'><strong>Reason:</strong> <span style='color: #e20000;'>" . esc_html($error) . "</span></div>";
                            }
                        }
                        
                        $html .= "<br>";
                        continue; // Skip the rest of the details for non-completed items
                    }

                    // Continue to add other details for completed items
                    if ($status === 'publish' && $published_post_id = get_post_meta($item->ID, '_wpaicg_generator_post', true)) {
                        $edit_post_link = get_edit_post_link($published_post_id);
                        $title = $edit_post_link ? "<a href='" . esc_url($edit_post_link) . "' target='_blank'>$title</a>" : $title;
                    }


                    $html .= "<div><strong>Title:</strong> $title</div>";
                    $html .= "<div><strong>Status:</strong> $status_text</div>";
                    $html .= "<div><strong>Duration:</strong> $formatted_run</div>";
                    $html .= "<div><strong>Word Count:</strong> $length</div>";
                    $html .= "<div><strong>Token:</strong> $token</div>";
                    $html .= "<div><strong>Estimated Cost:</strong> $cost</div>";
                    $html .= "<div><strong>Model:</strong> $ai_model</div><br>";

                }
            } else {
                $html = "<div>No items found for this batch.</div>";
            }
        
            wp_send_json_success($html);
        }
        
        
        public function format_duration($seconds) {
            // Check if the run value is empty or not numeric and return a default message
            if (empty($seconds) || !is_numeric($seconds)) {
                return "Not available"; // Or return ""; for an empty string
            }
        
            // Explicitly convert the input to a float to handle fractional seconds
            $seconds = floatval($seconds);
        
            // If necessary, round to the nearest second
            $seconds = round($seconds);
        
            $hours = floor($seconds / 3600);
            $minutes = floor(($seconds % 3600) / 60);
            $seconds = $seconds % 60;
        
            $parts = [];
        
            if ($hours > 0) {
                $parts[] = $hours . ' hour' . ($hours == 1 ? '' : 's');
            }
            if ($minutes > 0) {
                $parts[] = $minutes . ' min' . ($minutes == 1 ? '' : 's');
            }
            if ($seconds > 0 || count($parts) === 0) {
                $parts[] = $seconds . ' sec' . ($seconds == 1 ? '' : 's');
            }
        
            return implode(' and ', $parts);
        }
        
        
        public function gpt3_ajax_pagination() {
            global $wpdb;
            // Check for nonce security
            if ( ! wp_verify_nonce( $_POST['nonce'], 'gpt3_ajax_pagination_nonce' ) ) {
                wp_send_json_error(['msg' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')]);
            }
        
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $posts_per_page = 5; // Adjust as needed
            $offset = ($page - 1) * $posts_per_page;
        
            // Calculate total number of posts from wpaicg_tracking or wpaicg_twitter
            $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type = 'wpaicg_tracking' OR post_type = 'wpaicg_twitter'");
            $total_pages = ceil($total_posts / $posts_per_page);
        
            $posts = $wpdb->get_results($wpdb->prepare("
                SELECT ID, post_title, post_status, post_mime_type, post_type
                FROM {$wpdb->posts} 
                WHERE post_type IN ('wpaicg_tracking', 'wpaicg_twitter')
                ORDER BY post_date DESC 
                LIMIT %d, %d", 
                $offset, $posts_per_page
            ));
        
            $output = '';
            foreach ( $posts as $post ) {
                $title = strlen($post->post_title) > 20 ? esc_html(substr($post->post_title, 0, 20)) . '...' : esc_html($post->post_title);
                $status = '';
                switch ($post->post_status) {
                    case 'pending':
                        $status = '<span style="color: #000000;background: #f2ff05;border-radius: 5px;padding: 0 0.3em 0.1em;">' . esc_html__('Pending', 'gpt3-ai-content-generator') . '</span>';
                        break;
                    case 'publish':
                        $status = '<span style="color: #ffffff;background: #12b11a;border-radius: 5px;padding: 0 0.3em 0.1em;">' . esc_html__('Completed', 'gpt3-ai-content-generator') . '</span>';
                        break;
                    case 'draft':
                        $status = '<span style="color: #e20000;">' . esc_html__('Error', 'gpt3-ai-content-generator') . '</span>';
                        break;
                    case 'trash':
                        $status = '<span style="color: #e20000;">' . esc_html__('Cancelled', 'gpt3-ai-content-generator') . '</span>';
                        break;
                }
                $source = ''; // Initialize source variable
                // Determine source based on post_mime_type and post_type
                if ($post->post_type == 'wpaicg_twitter') {
                    $source = esc_html__('Twitter', 'gpt3-ai-content-generator');
                } elseif (empty($post->post_mime_type) || $post->post_mime_type == 'editor') {
                    $source = esc_html__('Bulk Editor', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'csv') {
                    $source = esc_html__('CSV', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'rss') {
                    $source = esc_html__('RSS', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'sheets') {
                    $source = esc_html__('Google Sheets', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'multi') {
                    $source = esc_html__('Copy-Paste', 'gpt3-ai-content-generator');
                }
                // Append "Action" column HTML
                $output .= "<tr id='post-row-{$post->ID}'><td class='column-id'>" . esc_html($post->ID) . "</td><td class='column-batch'><a href='javascript:void(0)' class='show-details' data-id='{$post->ID}'>" . $title . "</a></td><td class='column-source'>" . $source . "</td><td class='column-status'>" . $status . "</td><td class='column-action'><button class='button button-primary delete-post' data-postid='{$post->ID}'>Delete</button></td></tr>";
            }
        
            // Generate and return pagination HTML as before
            $pagination_html = '<div class="gpt3-pagination">';
            for ($i = 1; $i <= $total_pages; $i++) {
                $pagination_html .= '<a href="#" data-page="' . $i . '">' . $i . '</a> ';
            }
            $pagination_html .= '</div>';
        
            // Send back both the table content and pagination HTML
            wp_send_json_success(['content' => $output, 'pagination' => $pagination_html]);
        
            die();
        }
        
        public function reload_items() {
            global $wpdb;
            // Check for nonce security
            if ( ! wp_verify_nonce( $_POST['nonce'], 'gpt3_ajax_pagination_nonce' ) ) {
                wp_send_json_error(['msg' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')]);
            }
        
            $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
            $posts_per_page = 5; // Adjust as needed
            $offset = ($page - 1) * $posts_per_page;
        
            // Calculate total number of posts
            $total_posts = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_type IN ('wpaicg_tracking', 'wpaicg_twitter')");
            $total_pages = ceil($total_posts / $posts_per_page);
        
            $posts = $wpdb->get_results($wpdb->prepare("
                SELECT ID, post_title, post_status, post_mime_type, post_type
                FROM {$wpdb->posts} 
                WHERE post_type IN ('wpaicg_tracking', 'wpaicg_twitter') 
                ORDER BY post_date DESC 
                LIMIT %d, %d", 
                $offset, $posts_per_page
            ));
    
            $output = '';
            foreach ( $posts as $post ) {
                $title = strlen($post->post_title) > 20 ? esc_html(substr($post->post_title, 0, 20)) . '...' : esc_html($post->post_title);
                $status = '';
                switch ($post->post_status) {
                    case 'pending':
                        $status = '<span style="color: #000000;background: #f2ff05;border-radius: 5px;padding: 0 0.3em 0.1em;">' . esc_html__('Pending', 'gpt3-ai-content-generator') . '</span>';
                        break;
                    case 'publish':
                        $status = '<span style="color: #ffffff;background: #12b11a;border-radius: 5px;padding: 0 0.3em 0.1em;">' . esc_html__('Completed', 'gpt3-ai-content-generator') . '</span>';
                        break;
                    case 'draft':
                        $status = '<span style="color: #e20000;">' . esc_html__('Error', 'gpt3-ai-content-generator') . '</span>';
                        break;
                    case 'trash':
                        $status = '<span style="color: #e20000;">' . esc_html__('Cancelled', 'gpt-3-ai-content-generator') . '</span>';
                        break;
                }
                $source = ''; // Initialize source variable
                // Source determination logic
                if ($post->post_type == 'wpaicg_twitter') {
                    $source = esc_html__('Twitter', 'gpt3-ai-content-generator');
                } elseif (empty($post->post_mime_type) || $post->post_mime_type == 'editor') {
                    $source = esc_html__('Bulk Editor', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'csv') {
                    $source = esc_html__('CSV', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'rss') {
                    $source = esc_html__('RSS', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'sheets') {
                    $source = esc_html__('Google Sheets', 'gpt3-ai-content-generator');
                } elseif ($post->post_mime_type == 'multi') {
                    $source = esc_html__('Copy-Paste', 'gpt3-ai-content-generator');
                }
                // Append "Action" column HTML
                $output .= "<tr id='post-row-{$post->ID}'><td class='column-id'>" . esc_html($post->ID) . "</td><td class='column-batch'><a href='javascript:void(0)' class='show-details' data-id='{$post->ID}'>" . $title . "</a></td><td class='column-source'>" . $source . "</td><td class='column-status'>" . $status . "</td><td class='column-action'><button class='button button-primary delete-post' data-postid='{$post->ID}'>Delete</button></td></tr>";
            }

            wp_send_json_success(['content' => $output]);

            die();
        }

        public function delete_post_by_ajax() {
            global $wpdb; // Make sure you have access to the global $wpdb object
        
            // Check for nonce security
            $nonce = $_POST['nonce'];
            if (!wp_verify_nonce($nonce, 'gpt3_ajax_pagination_nonce')) {
                wp_send_json_error(['msg' => 'Nonce verification failed']);
                return; // Early return to stop execution if the nonce check fails
            }
        
            $postid = isset($_POST['postid']) ? intval($_POST['postid']) : 0;
            if ($postid <= 0) {
                wp_send_json_error(['msg' => 'Invalid Post ID']);
                return; // Early return to stop execution if the post ID is invalid
            }
        
            // Delete the specified post and its related children posts
            wp_delete_post($postid, true); // Bypass trash and permanently delete
            $wpdb->delete($wpdb->posts, ['post_parent' => $postid, 'post_type' => 'wpaicg_bulk']); // Delete children
        
            // Clean up related post meta entries
            $related_meta = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_key=%s AND meta_value=%d", 'wpaicg_twitter_track', $postid));
            if ($related_meta) {
                delete_post_meta($related_meta->post_id, 'wpaicg_tweeted');
            }
        
            wp_send_json_success(['msg' => 'Post and related items deleted successfully']);
        }
        

        public function delete_all_wpaicg_posts() {

            global $wpdb;
            // Security check
            check_ajax_referer('gpt3_ajax_pagination_nonce', 'nonce');
        
            // Query to select all posts of the custom post types
            $tasks_sql = $wpdb->prepare(
                "SELECT ID FROM " . $wpdb->posts . " WHERE post_type IN ('wpaicg_bulk', 'wpaicg_tracking')"
            );
            $tasks = $wpdb->get_results($tasks_sql, ARRAY_A);
        
            // Loop through each task and delete it using wp_delete_post
            foreach ($tasks as $task) {
                wp_delete_post($task['ID'], true); // Set to true to bypass trash
            }
        
            wp_send_json_success(); // Return success
            
            die();
        }

        public function delete_completed_wpaicg_posts() {
            global $wpdb;
            // Security check
            check_ajax_referer('gpt3_ajax_pagination_nonce', 'nonce');
        
            // Query to select all completed posts (post_status = 'publish') of the custom post types
            $tasks_sql = $wpdb->prepare(
                "SELECT ID FROM " . $wpdb->posts . " WHERE post_type IN ('wpaicg_bulk', 'wpaicg_tracking') AND post_status = 'publish'"
            );
            $tasks = $wpdb->get_results($tasks_sql, ARRAY_A);
        
            // Loop through each task and delete it using wp_delete_post
            foreach ($tasks as $task) {
                wp_delete_post($task['ID'], true); // Set to true to bypass trash
            }
        
            wp_send_json_success(); // Return success
            
            die();
        }
        
        public function delete_pending_wpaicg_posts() {
            global $wpdb;
            // Security check
            check_ajax_referer('gpt3_ajax_pagination_nonce', 'nonce');
        
            // Select all pending tasks
            $pending_tasks_sql = $wpdb->prepare(
                "SELECT ID, post_parent FROM " . $wpdb->posts . " WHERE post_type = 'wpaicg_bulk' AND post_status = 'pending'"
            );
            $pending_tasks = $wpdb->get_results($pending_tasks_sql, ARRAY_A);
        
            // Array to keep track of the batch statuses
            $batch_status_updates = [];
        
            // Delete each pending task
            foreach ($pending_tasks as $task) {
                $task_id = $task['ID'];
                $parent_id = $task['post_parent'];
        
                wp_delete_post($task_id, true); // Set to true to bypass trash
        
                if (!isset($batch_status_updates[$parent_id])) {
                    $batch_status_updates[$parent_id] = ['total' => 0, 'completed' => 0, 'pending' => 0, 'draft' => 0, 'trash' => 0, 'inherit' => 0];
                }
        
                $batch_status_updates[$parent_id]['pending']++;
            }
        
            // Update batch statuses based on remaining tasks
            foreach ($batch_status_updates as $parent_id => $status) {
                // Get the remaining tasks in the batch
                $remaining_tasks_sql = $wpdb->prepare(
                    "SELECT ID, post_status FROM " . $wpdb->posts . " WHERE post_type = 'wpaicg_bulk' AND post_parent = %d",
                    $parent_id
                );
                $remaining_tasks = $wpdb->get_results($remaining_tasks_sql, ARRAY_A);
        
                $remaining_task_count = count($remaining_tasks);
                $completed_task_count = count(array_filter($remaining_tasks, function($task) {
                    return $task['post_status'] === 'publish';
                }));
                $pending_task_count = count(array_filter($remaining_tasks, function($task) {
                    return $task['post_status'] === 'pending';
                }));
                $draft_task_count = count(array_filter($remaining_tasks, function($task) {
                    return $task['post_status'] === 'draft';
                }));
                $trash_task_count = count(array_filter($remaining_tasks, function($task) {
                    return in_array($task['post_status'], ['trash', 'inherit']);
                }));
        
                if ($remaining_task_count === 0) {
                    // Delete the batch if no tasks are remaining
                    wp_delete_post($parent_id, true); // Set to true to bypass trash
                } else {
                    // Determine the new status for the batch
                    if ($pending_task_count > 0 || $draft_task_count > 0) {
                        $new_status = 'pending';
                    } elseif ($trash_task_count > 0) {
                        $new_status = 'trash';
                    } else {
                        $new_status = 'publish'; // Default to completed
                    }
        
                    // Update the batch status
                    wp_update_post([
                        'ID' => $parent_id,
                        'post_status' => $new_status
                    ]);
                }
            }
        
            wp_send_json_success(); // Return success
            
            die();
        }

        public function delete_cancelled_wpaicg_posts() {
            global $wpdb;
            // Security check
            check_ajax_referer('gpt3_ajax_pagination_nonce', 'nonce');
        
            // Select all cancelled tasks
            $cancelled_tasks_sql = $wpdb->prepare(
                "SELECT ID, post_parent FROM " . $wpdb->posts . " WHERE post_type = 'wpaicg_bulk' AND post_status IN ('trash', 'inherit')"
            );
            $cancelled_tasks = $wpdb->get_results($cancelled_tasks_sql, ARRAY_A);
        
            // Array to keep track of the batch statuses
            $batch_status_updates = [];
        
            // Delete each cancelled task
            foreach ($cancelled_tasks as $task) {
                $task_id = $task['ID'];
                $parent_id = $task['post_parent'];
        
                wp_delete_post($task_id, true); // Set to true to bypass trash
        
                if (!isset($batch_status_updates[$parent_id])) {
                    $batch_status_updates[$parent_id] = ['total' => 0, 'completed' => 0, 'pending' => 0, 'draft' => 0, 'trash' => 0, 'inherit' => 0];
                }
        
                $batch_status_updates[$parent_id]['trash']++;
            }
        
            // Update batch statuses based on remaining tasks
            foreach ($batch_status_updates as $parent_id => $status) {
                // Get the remaining tasks in the batch
                $remaining_tasks_sql = $wpdb->prepare(
                    "SELECT ID, post_status FROM " . $wpdb->posts . " WHERE post_type = 'wpaicg_bulk' AND post_parent = %d",
                    $parent_id
                );
                $remaining_tasks = $wpdb->get_results($remaining_tasks_sql, ARRAY_A);
        
                $remaining_task_count = count($remaining_tasks);
                $completed_task_count = count(array_filter($remaining_tasks, function($task) {
                    return $task['post_status'] === 'publish';
                }));
                $pending_task_count = count(array_filter($remaining_tasks, function($task) {
                    return $task['post_status'] === 'pending';
                }));
                $draft_task_count = count(array_filter($remaining_tasks, function($task) {
                    return $task['post_status'] === 'draft';
                }));
                $trash_task_count = count(array_filter($remaining_tasks, function($task) {
                    return in_array($task['post_status'], ['trash', 'inherit']);
                }));
        
                if ($remaining_task_count === 0) {
                    // Delete the batch if no tasks are remaining
                    wp_delete_post($parent_id, true); // Set to true to bypass trash
                } else {
                    // Determine the new status for the batch
                    if ($pending_task_count > 0 || $draft_task_count > 0) {
                        $new_status = 'pending';
                    } elseif ($trash_task_count > 0) {
                        $new_status = 'trash';
                    } else {
                        $new_status = 'publish'; // Default to completed
                    }
        
                    // Update the batch status
                    wp_update_post([
                        'ID' => $parent_id,
                        'post_status' => $new_status
                    ]);
                }
            }
        
            wp_send_json_success(); // Return success
            
            die();
        }

        public function wpaicg_speech_record()
        {
            $mime_types = ['mp3' => 'audio/mpeg','mp4' => 'video/mp4','mpeg' => 'video/mpeg','m4a' => 'audio/m4a','wav' => 'audio/wav','webm' => 'video/webm'];
            $wpaicg_result = array('status' => 'error', 'msg' => esc_html__('Something went wrong','gpt3-ai-content-generator'));
            if(!current_user_can('wpaicg_single_content_speech')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['status'] = 'error';
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $open_ai = WPAICG_OpenAI::get_instance()->openai();
            if (!$open_ai) {
                $wpaicg_result['msg'] = esc_html__('Missing API Setting','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
                exit;
            }
            $file = $_FILES['audio'];
            $file_name = sanitize_file_name(basename($file['name']));
            $filetype = wp_check_filetype($file_name);
            if(!in_array($filetype['type'], $mime_types)){
                $wpaicg_result['msg'] = esc_html__('We only accept mp3, mp4, mpeg, mpga, m4a, wav, or webm.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if($file['size'] > 26214400){
                $wpaicg_result['msg'] = esc_html__('Audio file maximum 25MB','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $tmp_file = $file['tmp_name'];
            $data_request = array(
                'audio' => array(
                    'filename' => $file_name,
                    'data' => file_get_contents($tmp_file)
                ),
                'model' => 'whisper-1',
                'response_format' => 'json'
            );
            $completion = $open_ai->transcriptions($data_request);
            $completion = json_decode($completion);
            if($completion && isset($completion->error)){
                $wpaicg_result['msg'] = $completion->error->message;
                if(empty($wpaicg_result['msg']) && isset($completion->error->code) && $completion->error->code == 'invalid_api_key'){
                    $wpaicg_result['msg'] = 'Incorrect API key provided. You can find your API key at https://platform.openai.com/account/api-keys.';
                }
                wp_send_json($wpaicg_result);
            }
            $text_generated = trim($completion->text);
            if(empty($text_generated)){
                $wpaicg_result['msg'] = esc_html__('Please speak louder or say more words for accurate recognition.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            $wpaicg_data_request = [
                'model' => 'gpt-4o-mini',
                'prompt' => $text_generated,
                'temperature' => 0.7,
                'max_tokens' => 2000,
                'frequency_penalty' => 0.01,
                'presence_penalty' => 0.01,
            ];
            $wpaicg_generator = WPAICG_Generator::get_instance();
            $wpaicg_generator->openai($open_ai);
            $result = $wpaicg_generator->wpaicg_request($wpaicg_data_request);
            if($result['status'] == 'error'){
                $wpaicg_result['msg'] = $result['msg'];
                wp_send_json($wpaicg_result);
            }
            $wpaicg_result['data'] = $result['data'];
            $wpaicg_result['status'] = 'success';
            $wpaicg_result['text'] = $text_generated;
            $wpaicg_result['tokens'] = $result['tokens'];
            $wpaicg_result['length'] = $result['length'];
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_read_csv()
        {
            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong','gpt3-ai-content-generator'),
            );
            if(!current_user_can('wpaicg_bulk_content_csv')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( !empty($_FILES['file']) && empty($_FILES['file']['error']) ) {
                $wpaicg_file = $_FILES['file'];
                $wpaicg_csv_lines = array();

                if ( ($handle = fopen( $wpaicg_file['tmp_name'], 'r' )) !== false ) {
                    while ( ($data = fgetcsv( $handle, 100, ',' )) !== false ) {
                        if ( isset( $data[0] ) && !empty($data[0]) ) {
                            $wpaicg_csv_lines[] = $data[0];
                        }
                    }
                    fclose( $handle );
                }


                if ( count( $wpaicg_csv_lines ) ) {
                    if ( count( $wpaicg_csv_lines ) > $this->wpaicg_limit_titles ) {

                        if ( wpaicg_util_core()->wpaicg_is_pro() ) {
                            $wpaicg_result['notice'] = sprintf(esc_html__('Your CSV was including more than %d lines so we are only processing first 10 lines','gpt3-ai-content-generator'),$this->wpaicg_limit_titles);
                        } else {
                            $wpaicg_result['notice'] = sprintf(esc_html__('Free users can only generate %d titles at a time. Please upgrade to the Pro plan to get access to more fields.','gpt3-ai-content-generator'),$this->wpaicg_limit_titles);
                        }

                    }
                    $wpaicg_result['status'] = 'success';
                    $wpaicg_result['data'] = implode( '|', array_splice( $wpaicg_csv_lines, 0, $this->wpaicg_limit_titles ) );
                } else {
                    $wpaicg_result['msg'] = esc_html__('Your CSV file is empty','gpt3-ai-content-generator');
                }

            }
            wp_send_json( $wpaicg_result );
        }


        public function wpaicg_content_menu()
        {
            $module_settings = get_option('wpaicg_module_settings');
            if ($module_settings === false) {
                $module_settings = array_map(function() { return true; }, \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules);
            }
        
            $modules = \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules;
            if (isset($module_settings['content_writer']) && $module_settings['content_writer']) {
                add_submenu_page(
                    'wpaicg',
                    esc_html__($modules['content_writer']['title'], 'gpt3-ai-content-generator'),
                    esc_html__($modules['content_writer']['title'], 'gpt3-ai-content-generator'),
                    $modules['content_writer']['capability'],
                    $modules['content_writer']['menu_slug'],
                    array($this, $modules['content_writer']['callback']),
                    $modules['content_writer']['position']
                );
                // Add the 'Generate New Post' submenu only if Content Writer is enabled
                add_submenu_page(
                    'edit.php', // Attach to the 'Posts' admin menu
                    esc_html__('Generate New Post', 'gpt3-ai-content-generator'),
                    esc_html__('Generate New Post', 'gpt3-ai-content-generator'),
                    $modules['content_writer']['capability'],
                    $modules['content_writer']['menu_slug'],
                    array($this, $modules['content_writer']['callback'])
                );
            }
            if (isset($module_settings['autogpt']) && $module_settings['autogpt']) {
                add_submenu_page(
                    'wpaicg',
                    esc_html__($modules['autogpt']['title'], 'gpt3-ai-content-generator'),
                    esc_html__($modules['autogpt']['title'], 'gpt3-ai-content-generator'),
                    $modules['autogpt']['capability'],
                    $modules['autogpt']['menu_slug'],
                    array($this, $modules['autogpt']['callback']),
                    $modules['autogpt']['position']
                );
            }
        
        }

        public function wpaicg_single_content()
        {
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_single.php';
        }
        public function wpaicg_bulk_content()
        {
            include WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_bulk.php';
        }

        public function wpaicg_bulk_cancel()
        {
            // Check nonce

            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong','gpt3-ai-content-generator'),
            );
            if(!current_user_can('wpaicg_bulk_content_editor')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (!isset($_POST['wpaicg_nonce']) || !wp_verify_nonce($_POST['wpaicg_nonce'], 'wpaicg_nonce_action')) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( isset( $_POST['ids'] ) && !empty($_POST['ids']) ) {
                $wpaicg_ids = wpaicg_util_core()->sanitize_text_or_array_field($_POST['ids']);
                $wpaicg_bulks = get_posts( array(
                    'post_type'      => 'wpaicg_bulk',
                    'post_status'    => array(
                        'publish',
                        'pending',
                        'draft',
                        'trash'
                    ),
                    'post__in'       => $wpaicg_ids,
                    'posts_per_page' => -1,
                ) );

                if ( $wpaicg_bulks && is_array( $wpaicg_bulks ) && count( $wpaicg_bulks ) ) {
                    $wpaicg_bulk_id = false;
                    foreach ( $wpaicg_bulks as $wpaicg_bulk ) {
                        $wpaicg_bulk_id = $wpaicg_bulk->post_parent;
                        wp_update_post( array(
                            'ID'          => $wpaicg_bulk->ID,
                            'post_status' => 'inherit',
                        ) );
                    }
                    if ( $wpaicg_bulk_id && !empty($wpaicg_bulk_id) ) {
                        wp_update_post( array(
                            'ID'          => $wpaicg_bulk_id,
                            'post_status' => 'trash',
                        ) );
                    }
                }

            }

            wp_send_json( $wpaicg_result );
        }

        public function wpaicg_valid_date( $date, $format = 'Y-m-d H:i:s' )
        {
            $d = \DateTime::createFromFormat( $format, $date );
            return $d && $d->format( $format ) == $date;
        }

        public function wpaicg_bulk_save()
        {
            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong','gpt3-ai-content-generator'),
            );
            if(!current_user_can('wpaicg_bulk_content_editor')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if (isset($_POST['wpaicg_titles']) && !empty($_POST['wpaicg_titles'])) {
                $wpaicg_titles = wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_titles']);
                $wpaicg_schedules = (isset($_POST['wpaicg_schedules']) && !empty($_POST['wpaicg_schedules']) ? wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_schedules']) : array());
                $wpaicg_category = (isset($_POST['wpaicg_category']) && !empty($_POST['wpaicg_category']) ? wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_category']) : array());

                if (is_array($wpaicg_titles)) {
                    $post_status = (isset($_POST['post_status']) && !empty($_POST['post_status']) ? sanitize_text_field($_POST['post_status']) : 'draft');
                    $waicg_track_title = '';
                    foreach ($wpaicg_titles as $wpaicg_title) {
                        if (!empty($wpaicg_title)) {
                            $waicg_track_title .= (empty($waicg_track_title) ? trim($wpaicg_title) : ', ' . $wpaicg_title);
                        }
                    }
                    $wpaicg_source = (isset($_POST['source']) && !empty($_POST['source']) ? sanitize_text_field($_POST['source']) : 'editor');

                    if (!empty($waicg_track_title)) {
                        $wpaicg_track_id = wp_insert_post(array(
                            'post_type' => 'wpaicg_tracking',
                            'post_title' => $waicg_track_title,
                            'post_status' => 'pending',
                            'post_mime_type' => $wpaicg_source,
                        ));

                        if (!is_wp_error($wpaicg_track_id)) {
                            foreach ($wpaicg_titles as $key => $wpaicg_title) {

                                if (!empty($wpaicg_title)) {
                                    $wpaicg_bulk_data = array(
                                        'post_type' => 'wpaicg_bulk',
                                        'post_title' => trim($wpaicg_title),
                                        'post_status' => 'pending',
                                        'post_parent' => $wpaicg_track_id,
                                        'post_password' => $post_status,
                                        'post_mime_type' => $wpaicg_source,
                                    );
                                    if(isset($_POST['post_author']) && !empty($_POST['post_author'])){
                                        $wpaicg_bulk_data['post_author'] = sanitize_text_field($_POST['post_author']);
                                    }
                                    if (isset($wpaicg_schedules[$key]) && !empty($wpaicg_schedules[$key])) {
                                        $wpaicg_item_schedule = $wpaicg_schedules[$key] . ':00';
                                        if ($this->wpaicg_valid_date($wpaicg_item_schedule)) {
                                            $wpaicg_bulk_data['post_excerpt'] = $wpaicg_item_schedule;
                                        }
                                    }

                                    if (isset($wpaicg_category[$key]) && !empty($wpaicg_category[$key])) {
                                        $wpaicg_bulk_data['menu_order'] = sanitize_text_field($wpaicg_category[$key]);
                                    }

                                    wp_insert_post($wpaicg_bulk_data);
                                }

                            }
                            $wpaicg_result['id'] = $wpaicg_track_id;
                            $wpaicg_result['status'] = 'success';
                        }

                    }

                }

            }
            wp_send_json($wpaicg_result);
        }

        public function wpaicg_bulk_save_editor()
        {
            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong','gpt3-ai-content-generator'),
            );
            if(!current_user_can('wpaicg_bulk_content_editor')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['_wpnonce'], 'wpaicg_bulk_save' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if(isset($_POST['bulk']) && is_array($_POST['bulk']) && count($_POST['bulk'])){
                $post_status = ( isset( $_POST['post_status'] ) && !empty($_POST['post_status']) ? sanitize_text_field( $_POST['post_status'] ) : 'draft' );
                $bulks = wpaicg_util_core()->sanitize_text_or_array_field($_POST['bulk']);
                $waicg_track_title = '';
                foreach($bulks as $bulk){
                    if (isset($bulk['title']) && !empty($bulk['title'])) {
                        $waicg_track_title .= ( empty($waicg_track_title) ? trim( $bulk['title'] ) : ', ' . $bulk['title'] );
                    }
                }
                $wpaicg_source = ( isset( $_POST['source'] ) && !empty($_POST['source']) ? sanitize_text_field( $_POST['source'] ) : 'editor' );
                if ( !empty($waicg_track_title) ) {
                    $wpaicg_track_id = wp_insert_post(array(
                        'post_type' => 'wpaicg_tracking',
                        'post_title' => $waicg_track_title,
                        'post_status' => 'pending',
                        'post_mime_type' => $wpaicg_source,
                        'post_content' => ' '
                    ),true);
                    if ( !is_wp_error( $wpaicg_track_id ) ) {
                        foreach ($bulks as $bulk) {
                            if (isset($bulk['title']) && !empty($bulk['title'])) {
                                $wpaicg_bulk_data = array(
                                    'post_type'      => 'wpaicg_bulk',
                                    'post_title'     => trim( $bulk['title'] ),
                                    'post_status'    => 'pending',
                                    'post_parent'    => $wpaicg_track_id,
                                    'post_password'  => $post_status,
                                    'post_mime_type' => $wpaicg_source,
                                    'post_content' => ' '
                                );
                                if(isset($bulk['schedule']) && !empty($bulk['schedule'])){
                                    $wpaicg_item_schedule = $bulk['schedule'] . ':00';
                                    if ( $this->wpaicg_valid_date( $wpaicg_item_schedule ) ) {
                                        $wpaicg_bulk_data['post_excerpt'] = $wpaicg_item_schedule;
                                    }
                                }
                                if(isset($bulk['category']) && !empty($bulk['category'])){
                                    $wpaicg_bulk_data['menu_order'] = sanitize_text_field($bulk['category']);
                                }
                                if(isset($bulk['author']) && !empty($bulk['author'])){
                                    $wpaicg_bulk_data['post_author'] = sanitize_text_field($bulk['author']);
                                }
                                $wpaicg_bulk_id = wp_insert_post( $wpaicg_bulk_data );
                                if(isset($bulk['tags']) && !empty($bulk['tags'])){
                                    update_post_meta($wpaicg_bulk_id, '_wpaicg_tags', sanitize_text_field($bulk['tags']));
                                }
                                if(isset($bulk['keywords']) && !empty($bulk['keywords'])){
                                    update_post_meta($wpaicg_bulk_id, '_wpaicg_keywords', sanitize_text_field($bulk['keywords']));
                                }
                                if(isset($bulk['avoid']) && !empty($bulk['avoid'])){
                                    update_post_meta($wpaicg_bulk_id, '_wpaicg_avoid', sanitize_text_field($bulk['avoid']));
                                }
                                if(isset($bulk['anchor']) && !empty($bulk['anchor'])){
                                    update_post_meta($wpaicg_bulk_id, '_wpaicg_anchor', sanitize_text_field($bulk['anchor']));
                                }
                                if(isset($bulk['target']) && !empty($bulk['target'])){
                                    update_post_meta($wpaicg_bulk_id, '_wpaicg_target', sanitize_text_field($bulk['target']));
                                }
                                if(isset($bulk['cta']) && !empty($bulk['cta'])){
                                    update_post_meta($wpaicg_bulk_id, '_wpaicg_cta', sanitize_text_field($bulk['cta']));
                                }
                            }
                        }
                        $wpaicg_result['id'] = $wpaicg_track_id;
                        $wpaicg_result['status'] = 'success';
                    }
                    else{
                        $wpaicg_result['msg'] = $wpaicg_track_id->get_error_message();
                    }
                }
            }
            wp_send_json( $wpaicg_result );
        }

        public function wpaicg_bulk_status()
        {
            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong','gpt3-ai-content-generator'),
            );
            if (!isset($_POST['wpaicg_nonce']) || !wp_verify_nonce($_POST['wpaicg_nonce'], 'wpaicg_nonce_action')) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( isset( $_POST['ids'] ) && !empty($_POST['ids']) ) {
                $wpaicg_ids = wpaicg_util_core()->sanitize_text_or_array_field($_POST['ids']);
                $wpaicg_bulks = get_posts( array(
                    'post_type'      => 'wpaicg_bulk',
                    'post_status'    => array(
                        'publish',
                        'pending',
                        'draft',
                        'trash',
                        'inherit'
                    ),
                    'post__in'       => $wpaicg_ids,
                    'posts_per_page' => -1,
                ) );

                if ( $wpaicg_bulks && is_array( $wpaicg_bulks ) && count( $wpaicg_bulks ) ) {
                    $wpaicg_result['data'] = array();
                    $wpaicg_result['status'] = 'success';
                    foreach ( $wpaicg_bulks as $wpaicg_bulk ) {
                        $wpaicg_generator_run = get_post_meta( $wpaicg_bulk->ID, '_wpaicg_generator_run', true );
                        $wpaicg_generator_length = get_post_meta( $wpaicg_bulk->ID, '_wpaicg_generator_length', true );
                        $wpaicg_generator_token = get_post_meta( $wpaicg_bulk->ID, '_wpaicg_generator_token', true );
                        $wpaicg_generator_post_id = get_post_meta( $wpaicg_bulk->ID, '_wpaicg_generator_post', true );
                        $wpaicg_cost = 0;
                        $wpaicg_ai_model = get_post_meta($wpaicg_bulk->ID,'wpaicg_ai_model',true);
                        // Define pricing per 1K tokens
                        $pricing = \WPAICG\WPAICG_Util::get_instance()->model_pricing;

                        if (!empty($wpaicg_generator_token)) {
                            if (array_key_exists($wpaicg_ai_model, $pricing)) {
                                $wpaicg_cost = '$' . esc_html(number_format($wpaicg_generator_token * $pricing[$wpaicg_ai_model] / 1000, 5));
                            } else {
                                // Default cost calculation if the model is not listed
                                $wpaicg_cost = '$' . esc_html(number_format($wpaicg_generator_token * $this->wpaicg_token_price, 5));
                            }
                        }

                        $wpaicg_result['data'][] = array(
                            'id'       => $wpaicg_bulk->ID,
                            'title'    => $wpaicg_bulk->post_title,
                            'status'   => $wpaicg_bulk->post_status,
                            'duration' => ( $wpaicg_generator_run ? $this->wpaicg_seconds_to_time( (int) $wpaicg_generator_run ) : '' ),
                            'word'     => $wpaicg_generator_length,
                            'token'    => $wpaicg_generator_token,
                            'cost'     => $wpaicg_cost,
                            'msg'      => get_post_meta( $wpaicg_bulk->ID, '_wpaicg_error', true ),
                            'url'      => ( empty($wpaicg_generator_post_id) ? '' : admin_url( 'post.php?post=' . $wpaicg_generator_post_id . '&action=edit' ) ),
                        );
                    }
                }

            }

            wp_send_json( $wpaicg_result );
        }

        public function wpaicg_save_description($post_id, $description)
        {
            global $wpdb;
            update_post_meta($post_id,'_wpaicg_meta_description',$description);
            $seo_option = get_option('_yoast_wpseo_metadesc',false);
            $seo_plugin_activated = wpaicg_util_core()->seo_plugin_activated();

            // Update the post excerpt
            wp_update_post(array(
                'ID' => $post_id,
                'post_excerpt' => $description
            ));


            if($seo_plugin_activated == '_yoast_wpseo_metadesc' && $seo_option){
                update_post_meta($post_id,$seo_plugin_activated,$description);
            }
            $seo_option = get_option('_aioseo_description',false);
            if($seo_plugin_activated == '_aioseo_description' && $seo_option){
                update_post_meta($post_id,$seo_plugin_activated,$description);
                $check = $wpdb->get_row($wpdb->prepare("SELECT * FROM ".$wpdb->prefix."aioseo_posts WHERE post_id=%d",$post_id));
                if($check){
                    $wpdb->update($wpdb->prefix.'aioseo_posts',array(
                        'description' => $description
                    ), array(
                        'post_id' => $post_id
                    ));
                }
                else{
                    $wpdb->insert($wpdb->prefix.'aioseo_posts',array(
                        'post_id' => $post_id,
                        'description' => $description,
                        'created' => gmdate('Y-m-d H:i:s'),
                        'updated' => gmdate('Y-m-d H:i:s')
                    ));
                }
            }
            $seo_option = get_option('rank_math_description',false);
            if($seo_plugin_activated == 'rank_math_description' && $seo_option){
                update_post_meta($post_id,$seo_plugin_activated,$description);
            }
            // The SEO Framework
            $seo_option = get_option('_wpaicg_genesis_description', false);
            if ($seo_plugin_activated == '_genesis_description' && $seo_option) {
                update_post_meta($post_id, '_genesis_description', $description);
            }
        }

        public function wpaicg_save_aioseo_focus_keyword($post_id, $first_keyword) {
            global $wpdb;
        
            // Define default AIOSEO format
            $default_aioseo_format = json_encode([
                "focus" => [
                    "keyphrase" => "",
                    "score" => 0,
                    "analysis" => []
                ],
                "additional" => []
            ]);
        
            // Get existing AIOSEO data if it exists
            $existing_aioseo_data = $wpdb->get_var($wpdb->prepare(
                "SELECT keyphrases FROM " . $wpdb->prefix . "aioseo_posts WHERE post_id = %d",
                $post_id
            ));
        
            // If existing data is not found, use the default structure
            if (null === $existing_aioseo_data) {
                $existing_aioseo_data = $default_aioseo_format;
            }
        
            // Decode to PHP array
            $existing_aioseo_array = json_decode($existing_aioseo_data, true);
        
            // Update only the keyphrase
            $existing_aioseo_array['focus']['keyphrase'] = $first_keyword;
        
            // Encode back to JSON
            $updated_aioseo_data = json_encode($existing_aioseo_array);
        
            // Update or Insert into wp_aioseo_posts table
            $check = $wpdb->get_row($wpdb->prepare("SELECT * FROM " . $wpdb->prefix . "aioseo_posts WHERE post_id = %d", $post_id));
            if ($check) {
                $wpdb->update(
                    $wpdb->prefix . 'aioseo_posts',
                    ['keyphrases' => $updated_aioseo_data],
                    ['post_id' => $post_id]
                );
            } else {
                $wpdb->insert(
                    $wpdb->prefix . 'aioseo_posts',
                    [
                        'post_id' => $post_id,
                        'keyphrases' => $updated_aioseo_data,
                        'created' => gmdate('Y-m-d H:i:s'),
                        'updated' => gmdate('Y-m-d H:i:s')
                    ]
                );
            }
        }
        
        

        public function wpaicg_bulk_error_log($id, $msg)
        {
            update_post_meta( $id, '_wpaicg_error', $msg );
            wp_update_post( array(
                'ID'          => $id,
                'post_status' => 'trash',
            ) );
        }

        public function wpaicg_bulk_generator()
        {
            global  $wpdb ;
            $wpaicg_cron_added = get_option( '_wpaicg_cron_added', '' );

            if ( empty($wpaicg_cron_added) ) {
                update_option( '_wpaicg_cron_added', time() );
            } else {
                $sql = "SELECT * FROM " . $wpdb->posts . " WHERE post_type='wpaicg_bulk' AND post_status='pending' ORDER BY post_date ASC";
                $wpaicg_single = $wpdb->get_row( $sql );
                update_option( '_wpaicg_crojob_bulk_last_time', time() );
                /* Fix in progress task stuck*/
                $wpaicg_restart_queue = get_option('wpaicg_restart_queue','');
                $wpaicg_try_queue = get_option('wpaicg_try_queue','');
                if(!empty($wpaicg_restart_queue) && !empty($wpaicg_try_queue)) {
                    $wpaicg_fix_sql = $wpdb->prepare("SELECT p.post_parent,p.ID,(SELECT m.meta_value FROM ".$wpdb->postmeta." m WHERE m.post_id=p.ID AND m.meta_key='wpaicg_try_queue_time') as try_time FROM ".$wpdb->posts." p WHERE (p.post_status='draft' OR p.post_status='trash') AND p.post_type='wpaicg_bulk' AND p.post_modified <  NOW() - INTERVAL %d MINUTE",$wpaicg_restart_queue);
                    $in_progress_posts = $wpdb->get_results($wpaicg_fix_sql);
                    if($in_progress_posts && is_array($in_progress_posts) && count($in_progress_posts)){
                        foreach($in_progress_posts as $in_progress_post){
                            if(!$in_progress_post->try_time || (int)$in_progress_post->try_time < $wpaicg_try_queue){
                                wp_update_post(array(
                                    'ID'          => $in_progress_post->ID,
                                    'post_status' => 'pending',
                                ));
                                wp_update_post(array(
                                    'ID'          => $in_progress_post->post_parent,
                                    'post_status' => 'pending',
                                ));
                                $next_time = (int)$in_progress_post->try_time + 1;
                                update_post_meta($in_progress_post->ID,'wpaicg_try_queue_time',$next_time);
                            }
                        }
                    }
                }
                /* END fix stuck */
                if ( $wpaicg_single ) {
                    $wpaicg_generator_start = microtime( true );
                    $wpaicg_generator_tokens = 0;
                    $wpaicg_generator_text_length = 0;
                    try {
                        wp_update_post( array(
                            'ID'          => $wpaicg_single->ID,
                            'post_status' => 'draft',
                            'post_modified' => gmdate('Y-m-d H:i:s')
                        ) );
                        $wpaicg_generator = WPAICG_Generator::get_instance();
                        $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');
                        $openai = WPAICG_OpenAI::get_instance()->openai();

                        // Get the AI engine.
                        try {
                            $openai = WPAICG_Util::get_instance()->initialize_ai_engine();
                        } catch (\Exception $e) {
                            $wpaicg_result['msg'] = $e->getMessage();
                            wp_send_json($wpaicg_result);
                        }
                        $wpaicg_generator_result = array();
                        if(!$openai){
                            $this->wpaicg_bulk_error_log($wpaicg_single->ID, esc_html__('Missing API Setting','gpt3-ai-content-generator'));
                        }
                        else{
                            
                            $steps = array('heading','content','intro','faq','conclusion','tagline','seo','generate_title','addition','image','featuredimage');
                            
                            $is_pro = \WPAICG\wpaicg_util_core()->wpaicg_is_pro();
                            $gen_title_from_keywords = get_option('_wpaicg_gen_title_from_keywords', false);

                            if ($is_pro && $gen_title_from_keywords) {
                                $steps = array('heading', 'content', 'intro', 'faq', 'conclusion', 'tagline', 'seo', 'generate_title', 'addition', 'image', 'featuredimage');
                            } else {
                                $steps = array('heading', 'content', 'intro', 'faq', 'conclusion', 'tagline', 'seo', 'addition', 'image', 'featuredimage');
                            }

                            $wpaicg_generator->init($openai,$wpaicg_single->post_title,true,$wpaicg_single->ID);
                            $wpaicg_has_error = false;
                            $break_step = '';
                            foreach ($steps as $step){
                                $wpaicg_generator->wpaicg_generator($step);
                                if($wpaicg_generator->error_msg){
                                    $break_step = $step;
                                    $wpaicg_has_error = $wpaicg_generator->error_msg;
                                    break;
                                }
                            }
                            if($wpaicg_has_error){
                                $this->wpaicg_bulk_error_log($wpaicg_single->ID, $wpaicg_has_error.'. '.esc_html__('Break at step','gpt3-ai-content-generator').' '.$break_step);
                                $wpaicg_running = WPAICG_PLUGIN_DIR.'/wpaicg_running.txt';
                                if(file_exists($wpaicg_running)){
                                    unlink($wpaicg_running);
                                }
                            }
                            else{
                                $wpaicg_generator_result = $wpaicg_generator->wpaicgResult();
                                $wpaicg_generator_text_length = $wpaicg_generator_result['length'];
                                $wpaicg_generator_tokens = $wpaicg_generator_result['tokens'];
                                
                                $generated_title = isset($wpaicg_generator_result['title']) ? $wpaicg_generator_result['title'] : null;
                                // Remove ' and " from the beginning and end of the string
                                $cleaned_generated_title = trim($generated_title, "'\"");

                                // Get the user's choice for URL shortening
                                $should_shorten_url = get_option('_wpaicg_shorten_url', true);
                                
                                // If the user has chosen to shorten the URL
                                if ($should_shorten_url) {
                                    // Define the maximum length for the URL (70 characters)
                                    $max_url_length = 70;
                            
                                    // Get the domain name dynamically from WordPress settings
                                    $domain_name = get_site_url();  // or use home_url() depending on your needs
                            
                                    // Calculate the maximum length for the slug by considering the domain name
                                    $max_slug_length = $max_url_length - strlen($domain_name);
                            
                                    // Generate initial slug from title using WordPress function to get a URL-friendly string
                                    $slug = sanitize_title($cleaned_generated_title);
                                    
                                    // If the slug is too long, truncate it intelligently
                                    if (strlen($slug) > $max_slug_length) {
                                        $slug_words = explode("-", $slug);
                                        $new_slug_words = array();
                                        $new_slug_length = 0;
                            
                                        foreach($slug_words as $word) {
                                            if ($new_slug_length + strlen($word) + 1 <= $max_slug_length) { // +1 for the hyphen
                                                $new_slug_words[] = $word;  // Add the word to the new slug
                                                $new_slug_length += strlen($word) + 1;  // Update the new slug length
                                            } else {
                                                break;  // Stop adding more words as it would exceed the maximum length
                                            }
                                        }
                                        $slug = implode("-", $new_slug_words);  // Create the new truncated slug
                                    }
                                    // Final check to ensure the slug doesn't exceed 70 characters
                                    if (strlen($slug) > 70) {
                                        $slug = substr($slug, 0, 70);  // Trim the slug to be exactly 70 characters
                                    }
                                } else {
                                    // Generate slug without shortening
                                    $slug = sanitize_title($cleaned_generated_title);
                                }

                                // Get focus keyword option status
                                $should_include_focus_keyword = get_option('_wpaicg_focus_keyword_in_url', false);

                                if ($should_include_focus_keyword) {
                                    // Step 1: Check if _wporg_keywords is set and not empty
                                    $focus_keywords = get_post_meta($wpaicg_single->ID, '_wpaicg_keywords', true);
                                    if (!empty($focus_keywords)) {
                                        // Step 2: If the focus keywords contain a comma, get the first one
                                        if (strpos($focus_keywords, ',') !== false) {
                                            $focus_keywords_array = explode(',', $focus_keywords);
                                            $focus_keyword = trim($focus_keywords_array[0]);  // Get the first keyword
                                        } else {
                                            $focus_keyword = $focus_keywords;
                                        }

                                        // Step 3: Check if the focus keyword is already in the slug
                                        if (strpos($slug, $focus_keyword) === false) {
                                            // Step 4: Trim the slug and prepend the focus keyword
                                            $keyword_length = strlen($focus_keyword);
                                            $slug = substr($slug, 0, -1 * $keyword_length);  // Trim last n characters
                                            $slug = $focus_keyword . '-' . $slug;  // Prepend keyword and hyphen
                                        }
                                    }
                                }
            
                                
                                $wpaicg_allowed_html_content_post = wp_kses_allowed_html( 'post' );
                                $wpaicg_content = wp_kses( $wpaicg_generator_result['content'], $wpaicg_allowed_html_content_post );
                                $wpaicg_post_status = ( $wpaicg_single->post_password == 'draft' ? 'draft' : 'publish' );
                                $wpaicg_image_attachment_id = false;

                                $alt_text = !empty($cleaned_generated_title) ? $cleaned_generated_title : $wpaicg_single->post_title;

                                if(isset($wpaicg_generator_result['img']) && !empty($wpaicg_generator_result['img'])){
                                    $wpaicg_image_url = sanitize_url($wpaicg_generator_result['img']);
                                    $wpaicg_image_attachment_id = $this->wpaicg_save_image($wpaicg_image_url,$alt_text, false);
                                    if($wpaicg_image_attachment_id['status'] == 'success'){
                                        $wpaicg_image_attachment_url = wp_get_attachment_url($wpaicg_image_attachment_id['id']);
                                        $wpaicg_content = str_replace("__WPAICG_IMAGE__", '<img src="'.$wpaicg_image_attachment_url.'" alt="'.$alt_text.'" />', $wpaicg_content);
                                    }
                                }
                                // Fix empty image
                                $wpaicg_content = str_replace("__WPAICG_IMAGE__", '', $wpaicg_content);
                                // change heading id
                                $wpaicg_content = str_replace('wpaicgheading',wpaicg_util_core()->wpaicg_random(),$wpaicg_content);
                                $wpaicg_post_data = array(
                                    'post_title'   => !empty($cleaned_generated_title) ? $cleaned_generated_title : $wpaicg_single->post_title,
                                    'post_author'  => $wpaicg_single->post_author,
                                    'post_content' => $wpaicg_content,
                                    'post_status'  => $wpaicg_post_status,
                                    'post_name'    => $slug,
                                );
                                if($wpaicg_single->menu_order && $wpaicg_single->menu_order > 0){
                                    $wpaicg_post_data['post_category'] = array($wpaicg_single->menu_order);
                                }

                                if ( !empty($wpaicg_single->post_excerpt) ) {
                                    $wpaicg_post_data['post_status'] = 'future';
                                    $wpaicg_post_data['post_date'] = $wpaicg_single->post_excerpt;
                                    $wpaicg_post_data['post_date_gmt'] = $wpaicg_single->post_excerpt;
                                }

                                $wpaicg_post_id = wp_insert_post( $wpaicg_post_data );

                                if ( is_wp_error( $wpaicg_post_id ) ) {
                                    update_post_meta( $wpaicg_single->ID, '_wpaicg_error', $wpaicg_post_id->get_error_message() );
                                    wp_update_post( array(
                                        'ID'          => $wpaicg_single->ID,
                                        'post_status' => 'trash',
                                    ) );
                                } else {
                                    $ai_provider_info = \WPAICG\WPAICG_Util::get_instance()->get_default_ai_provider();
                                    $wpaicg_provider = $ai_provider_info['provider'];
                                    $wpaicg_ai_model = $ai_provider_info['model'];

                                    add_post_meta($wpaicg_post_id,'wpaicg_ai_model',$wpaicg_ai_model);
                                    add_post_meta($wpaicg_single->ID,'wpaicg_ai_model',$wpaicg_ai_model);

                                    // Retrieve the focus keywords for this post
                                    $keywords = get_post_meta($wpaicg_single->ID, '_wpaicg_keywords', true);

                                    if (!empty($keywords)) {

                                        // Sanitize and update the _wporg_keywords meta field
                                        $keywords_sanitized = sanitize_text_field($keywords);
                                        update_post_meta($wpaicg_post_id, '_wporg_keywords', $keywords_sanitized);

                                        // Update Rank Math focus keyword
                                        update_post_meta($wpaicg_post_id, 'rank_math_focus_keyword', $keywords_sanitized);

                                        // Extract the first keyword for Yoast
                                        $keyword_array = explode(',', $keywords_sanitized);
                                        $first_keyword = trim($keyword_array[0]);

                                        if (!empty($first_keyword)) {
                                            // Update Yoast focus keyword
                                            update_post_meta($wpaicg_post_id, '_yoast_wpseo_focuskw', $first_keyword);

                                        // Check if 'All In One SEO Pack' or 'All In One SEO Pack Pro' is active
                                        if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                                            $this->wpaicg_save_aioseo_focus_keyword($wpaicg_post_id, $first_keyword);
                                        }

                                        }
                                    }

                                    if(isset($wpaicg_generator_result['description']) && !empty($wpaicg_generator_result['description'])){
                                        $this->wpaicg_save_description($wpaicg_post_id,sanitize_text_field($wpaicg_generator_result['description']));
                                    }

                                    if(isset($wpaicg_generator_result['featured_img']) && !empty($wpaicg_generator_result['featured_img'])){
                                        $wpaicg_featured_image_url = sanitize_url($wpaicg_generator_result['featured_img']);
                                        $wpaicg_image_attachment_id = $this->wpaicg_save_image($wpaicg_featured_image_url,$wpaicg_single->post_title, true);
                                        if($wpaicg_image_attachment_id['status'] == 'success'){
                                            update_post_meta( $wpaicg_post_id, '_thumbnail_id', $wpaicg_image_attachment_id['id']);
                                        }
                                    }

                                    $wpaicg_tags = get_post_meta($wpaicg_single->ID, '_wpaicg_tags',true);
                                    if(!empty($wpaicg_tags)){
                                        $wpaicg_tags = array_map('trim', explode(',', $wpaicg_tags));
                                        if($wpaicg_tags && is_array($wpaicg_tags) && count($wpaicg_tags)){
                                            wp_set_post_tags($wpaicg_post_id,$wpaicg_tags);
                                        }
                                    }
                                    update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_post', $wpaicg_post_id );
                                    wp_update_post( array(
                                        'ID'          => $wpaicg_single->ID,
                                        'post_status' => 'publish',
                                    ));
                                }
                                /*Save Last Content*/
                                if($wpaicg_single->post_mime_type == 'sheets'){
                                    update_option('wpaicg_cronjob_sheets_content',time());
                                }
                                elseif($wpaicg_single->post_mime_type == 'rss'){
                                    update_option('wpaicg_cronjob_rss_content',time());
                                }
                                else{
                                    update_option('wpaicg_cronjob_bulk_content',time());
                                }
                            }
                        }
                    } catch ( \Exception $exception ) {
                    }
                    $wpaicg_bulks = get_posts( array(
                        'post_type'      => 'wpaicg_bulk',
                        'post_status'    => array(
                            'publish',
                            'pending',
                            'draft',
                            'trash',
                            'inherit'
                        ),
                        'post_parent'    => $wpaicg_single->post_parent,
                        'posts_per_page' => -1,
                    ) );
                    $wpaicg_bulk_completed = true;
                    $wpaicg_bulk_error = false;
                    foreach ( $wpaicg_bulks as $wpaicg_bulk ) {
                        if ( $wpaicg_bulk->post_status == 'pending' || $wpaicg_bulk->post_status == 'draft' ) {
                            $wpaicg_bulk_completed = false;
                        }

                        if ( $wpaicg_bulk->post_status == 'trash' ) {
                            $wpaicg_bulk_error = true;
                            $wpaicg_bulk_completed = false;
                        }

                    }
                    if ( $wpaicg_bulk_completed ) {
                        wp_update_post( array(
                            'ID'          => $wpaicg_single->post_parent,
                            'post_status' => 'publish',
                        ) );
                    }
                    if ( $wpaicg_bulk_error ) {
                        wp_update_post( array(
                            'ID'          => $wpaicg_single->post_parent,
                            'post_status' => 'draft',
                        ) );
                    }
                    $wpaicg_generator_end = microtime( true ) - $wpaicg_generator_start;
                    update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_run', $wpaicg_generator_end );
                    update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_length', $wpaicg_generator_text_length );
                    update_post_meta( $wpaicg_single->ID, '_wpaicg_generator_token', $wpaicg_generator_tokens );
                }

            }

        }

        public function wpaicg_seconds_to_time( $seconds )
        {
            $dtF = new \DateTime( '@0' );
            $dtT = new \DateTime( "@{$seconds}" );
            return $dtF->diff( $dtT )->format( '%h hours, %i minutes and %s seconds' );
        }

        public function wpaicg_post_image($post_id, $wpaicg_title = '')
        {
            if(isset($_REQUEST['wpaicg_content_changed']) && !empty($_REQUEST['wpaicg_content_changed'])){
                $my_post = array(
                    'ID'          => $post_id,
                    'post_status' => 'draft',
                );
                if ( isset( $_REQUEST['_wporg_preview_title'] ) && $_REQUEST['_wporg_preview_title'] != '' ) {
                    $my_post['post_title'] = sanitize_text_field($_REQUEST['_wporg_preview_title']);
                }
                if ( isset( $_REQUEST['_wporg_generated_text'] ) && $_REQUEST['_wporg_generated_text'] != '' ) {
                    $my_post['post_content'] = wp_kses_post($_REQUEST['_wporg_generated_text']);
                }
                $wpaicg_content = $my_post['post_content'];
                $wpaicg_image_attachment_id = false;
                if(isset($_REQUEST['wpaicg_image_url']) && !empty($_REQUEST['wpaicg_image_url'])){
                    $wpaicg_image_url = sanitize_url($_REQUEST['wpaicg_image_url']);
                    $wpaicg_image_attachment_id = $this->wpaicg_save_image($wpaicg_image_url, $wpaicg_title, false);
                    if($wpaicg_image_attachment_id['status'] == 'success'){
                        $wpaicg_image_attachment_url = wp_get_attachment_url($wpaicg_image_attachment_id['id']);
                        $wpaicg_content = str_replace('<img />', '<img src="'.$wpaicg_image_attachment_url.'" alt="'.$wpaicg_title.'" />', $wpaicg_content);
                        $wpaicg_content = str_replace("<img src=\\'__WPAICG_IMAGE__\\' alt=\\'".$wpaicg_title."\\' />", '<img src="'.$wpaicg_image_attachment_url.'" alt="'.$wpaicg_title.'" />', $wpaicg_content);
                        $wpaicg_content = str_replace("<img src=\'__WPAICG_IMAGE__\' alt=\'".$wpaicg_title."\' />", '<img src="'.$wpaicg_image_attachment_url.'" alt="'.$wpaicg_title.'" />', $wpaicg_content);
                        $wpaicg_content = str_replace("__WPAICG_IMAGE__", '<img src="'.$wpaicg_image_attachment_url.'" alt="'.$wpaicg_title.'" />', $wpaicg_content);
                    }
                }
                // Fix empty image
                $wpaicg_content = str_replace("__WPAICG_IMAGE__", '', $wpaicg_content);
                $my_post['post_content'] = $wpaicg_content;
                if(isset($_REQUEST['wpaicg_featured_img_url']) && !empty($_REQUEST['wpaicg_featured_img_url'])){
                    $wpaicg_featured_img_url = sanitize_url($_REQUEST['wpaicg_featured_img_url']);
                    $wpaicg_image_attachment_id = $this->wpaicg_save_image($wpaicg_featured_img_url, $wpaicg_title, true);
                    if($wpaicg_image_attachment_id['status'] == 'success'){
                        update_post_meta( $post_id, '_thumbnail_id', $wpaicg_image_attachment_id['id']);
                    }
                }
                wp_update_post( $my_post );
            }
        }

        public function wpaicg_save_image($imageurl, $wpaicg_title = '', $is_featured = false)
        {
            global $wpdb;
            $result = array('status' => 'error', 'msg' => esc_html__('Can not save image to media','gpt3-ai-content-generator'));
            if(!function_exists('wp_generate_attachment_metadata')){
                include_once( ABSPATH . 'wp-admin/includes/image.php' );
            }
            if(!function_exists('download_url')){
                include_once( ABSPATH . 'wp-admin/includes/file.php' );
            }
            if(!function_exists('media_handle_sideload')){
                include_once( ABSPATH . 'wp-admin/includes/media.php' );
            }
            try {
                $array = explode('/', getimagesize($imageurl)['mime']);
                $imagetype = end($array);
                // Use the sanitized title as the base for the filename and append -2 if it's a featured image
                $filename_base = sanitize_title($wpaicg_title);
                $filename = $filename_base . ($is_featured ? '-2' : '') . '.' . $imagetype;
                $checkExist = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->postmeta} WHERE meta_value LIKE %s",'%/'.$wpdb->esc_like($filename)));
                if($checkExist){
                    $result['status'] = 'success';
                    $result['id'] = $checkExist->post_id;
                }
                else{
                    $tmp = download_url($imageurl);
                    if ( is_wp_error( $tmp ) ){
                        $result['msg'] = $tmp->get_error_message();
                        return $result;
                    }
                    $args = array(
                        'name' => $filename,
                        'tmp_name' => $tmp,
                    );
                    $attachment_id = media_handle_sideload( $args, 0, '',array(
                        'post_title'     => $wpaicg_title,
                        'post_content'   => $wpaicg_title,
                        'post_excerpt'   => $wpaicg_title
                    ));
                    if(!is_wp_error($attachment_id)){
                        update_post_meta($attachment_id,'_wp_attachment_image_alt', $wpaicg_title);
                        $imagenew = get_post( $attachment_id );
                        $fullsizepath = get_attached_file( $imagenew->ID );
                        $attach_data = wp_generate_attachment_metadata( $attachment_id, $fullsizepath );
                        wp_update_attachment_metadata( $attachment_id, $attach_data );
                        $result['status'] = 'success';
                        $result['id'] = $attachment_id;
                    }
                    else{
                        $result['msg'] = $attachment_id->get_error_message();
                        return $result;
                    }
                }
            }
            catch (\Exception $exception){
                $result['msg'] = $exception->getMessage();
            }
            return $result;
        }

        public function wpaicg_save_draft_post()
        {
            ini_set('max_execution_time', 1000);
            $wpaicg_result = array(
                'status' => 'error',
                'msg'    => esc_html__('Something went wrong','gpt3-ai-content-generator'),
            );
            if(!current_user_can('edit_posts')){
                $wpaicg_result['msg'] = esc_html__('You do not have permission for this action.','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( ! wp_verify_nonce( $_POST['nonce'], 'wpaicg-ajax-nonce' ) ) {
                $wpaicg_result['msg'] = esc_html__('Nonce verification failed','gpt3-ai-content-generator');
                wp_send_json($wpaicg_result);
            }
            if ( isset( $_POST['title'] ) && !empty($_POST['title']) && isset( $_POST['content'] ) && !empty($_POST['content']) ) {
                $wpaicg_allowed_html_content_post = wp_kses_allowed_html( 'post' );
                $wpaicg_title = sanitize_text_field( $_POST['title'] );

                // Get the user's choice for URL shortening
                $should_shorten_url = get_option('_wpaicg_shorten_url', true);
                
                // If the user has chosen to shorten the URL
                if ($should_shorten_url) {
                    // Define the maximum length for the URL (70 characters)
                    $max_url_length = 70;
            
                    // Get the domain name dynamically from WordPress settings
                    $domain_name = get_site_url();  // or use home_url() depending on your needs
            
                    // Calculate the maximum length for the slug by considering the domain name
                    $max_slug_length = $max_url_length - strlen($domain_name);
            
                    // Generate initial slug from title using WordPress function to get a URL-friendly string
                    $slug = sanitize_title($wpaicg_title);
                    
                    // If the slug is too long, truncate it intelligently
                    if (strlen($slug) > $max_slug_length) {
                        $slug_words = explode("-", $slug);
                        $new_slug_words = array();
                        $new_slug_length = 0;
            
                        foreach($slug_words as $word) {
                            if ($new_slug_length + strlen($word) + 1 <= $max_slug_length) { // +1 for the hyphen
                                $new_slug_words[] = $word;  // Add the word to the new slug
                                $new_slug_length += strlen($word) + 1;  // Update the new slug length
                            } else {
                                break;  // Stop adding more words as it would exceed the maximum length
                            }
                        }
                        $slug = implode("-", $new_slug_words);  // Create the new truncated slug
                    }
                    // Final check to ensure the slug doesn't exceed 70 characters
                    if (strlen($slug) > 70) {
                        $slug = substr($slug, 0, 70);  // Trim the slug to be exactly 70 characters
                    }
                } else {
                    // Generate slug without shortening
                    $slug = sanitize_title($wpaicg_title);
                }

                // Get focus keyword option status
                $should_include_focus_keyword = get_option('_wpaicg_focus_keyword_in_url', false);

                if ($should_include_focus_keyword) {
                    // Step 1: Check if _wporg_keywords is set and not empty
                    if (isset($_POST['_wporg_keywords']) && !empty($_POST['_wporg_keywords'])) {
                        $focus_keywords = sanitize_text_field($_POST['_wporg_keywords']);

                        // Step 2: If the focus keywords contain a comma, get the first one
                        if (strpos($focus_keywords, ',') !== false) {
                            $focus_keywords_array = explode(',', $focus_keywords);
                            $focus_keyword = trim($focus_keywords_array[0]);  // Get the first keyword
                        } else {
                            $focus_keyword = $focus_keywords;
                        }

                        // Step 3: Check if the focus keyword is already in the slug
                        if (strpos($slug, $focus_keyword) === false) {
                            // Step 4: Trim the slug and prepend the focus keyword
                            $keyword_length = strlen($focus_keyword);
                            $slug = substr($slug, 0, -1 * $keyword_length);  // Trim last n characters
                            $slug = $focus_keyword . '-' . $slug;  // Prepend keyword and hyphen
                        }
                    }
                }
            
                $wpaicg_content = wp_kses( $_POST['content'], $wpaicg_allowed_html_content_post );
                if (isset($_REQUEST['save_source']) && $_REQUEST['save_source'] == 'promptbase') {
                    $wpaicg_content = wp_kses(urldecode($_POST['content']), $wpaicg_allowed_html_content_post);
                }
                $wpaicg_content = str_replace("__WPAICG_IMAGE__", '', $wpaicg_content);
                
                if(isset($_POST['post_id']) && !empty($_POST['post_id'])){
                    $wpaicg_post_id = sanitize_text_field($_POST['post_id']);
                    wp_update_post(array(
                        'ID' => $wpaicg_post_id,
                        'post_title' => $wpaicg_title,
                        'post_content' => $wpaicg_content,
                        'post_name' => $slug  // Set the new slug
                    ));
                }
                else {
                    $wpaicg_post_id = wp_insert_post(array(
                        'post_title' => $wpaicg_title,
                        'post_content' => $wpaicg_content,
                        'post_name' => $slug  // Set the new slug
                    ));
                }

                if ( !is_wp_error( $wpaicg_post_id ) ) {
                    if ( array_key_exists( 'wpaicg_settings', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_meta_key', wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_settings']) );
                    }
                    if ( array_key_exists( '_wporg_language', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_language', sanitize_text_field($_POST['_wporg_language']) );
                    }
                    if ( array_key_exists( '_wporg_preview_title', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_preview_title', sanitize_text_field($_POST['_wporg_preview_title']) );
                    }

                    // _wporg_keywords
                    if ( array_key_exists( '_wporg_keywords', $_POST ) ) {
                        // Update the _wporg_keywords meta field
                        update_post_meta( $wpaicg_post_id, '_wporg_keywords', sanitize_text_field($_POST['_wporg_keywords']) );

                        // Directly use the sanitized keywords for rank_math_focus_keyword
                        $keywords = sanitize_text_field($_POST['_wporg_keywords']);
                        if (!empty($keywords)) {

                            // Update Rank Math focus keyword
                            update_post_meta( $wpaicg_post_id, 'rank_math_focus_keyword', $keywords );

                            // Extract the first keyword for Yoast
                            $keyword_array = explode(',', $keywords);
                            $first_keyword = trim($keyword_array[0]);

                            if (!empty($first_keyword)) {
                                // Update Yoast focus keyword
                                update_post_meta( $wpaicg_post_id, '_yoast_wpseo_focuskw', $first_keyword );

                            // Check if 'All In One SEO Pack' or 'All In One SEO Pack Pro' is active
                            if (is_plugin_active('all-in-one-seo-pack/all_in_one_seo_pack.php') || is_plugin_active('all-in-one-seo-pack-pro/all_in_one_seo_pack.php')) {
                                $this->wpaicg_save_aioseo_focus_keyword($wpaicg_post_id, $first_keyword);
                            }

                            }
                        }
                    }
                    if ( array_key_exists( '_wporg_number_of_heading', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_number_of_heading', sanitize_text_field($_POST['_wporg_number_of_heading']) );
                    }
                    if ( array_key_exists( '_wporg_heading_tag', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_heading_tag', sanitize_text_field($_POST['_wporg_heading_tag']) );
                    }
                    if ( array_key_exists( '_wporg_writing_style', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_writing_style', sanitize_text_field($_POST['_wporg_writing_style']) );
                    }
                    if ( array_key_exists( '_wporg_writing_tone', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_writing_tone', sanitize_text_field($_POST['_wporg_writing_tone']) );
                    }
                    if ( array_key_exists( '_wporg_modify_headings', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_modify_headings', sanitize_text_field($_POST['_wporg_modify_headings']) );
                    }
                    if ( array_key_exists( 'wpaicg_image_source', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_image_source', sanitize_text_field($_POST['wpaicg_image_source']) );
                    }
                    if ( array_key_exists( 'wpaicg_featured_image_source', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_featured_image_source', sanitize_text_field($_POST['wpaicg_featured_image_source']) );
                    }
                    if ( array_key_exists( 'wpaicg_pexels_orientation', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pexels_orientation', sanitize_text_field($_POST['wpaicg_pexels_orientation']) );
                    }
                    if ( array_key_exists( 'wpaicg_pexels_size', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pexels_size', sanitize_text_field($_POST['wpaicg_pexels_size']) );
                    }
                    if ( array_key_exists( 'wpaicg_pexels_enable_prompt', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pexels_enable_prompt', sanitize_text_field($_POST['wpaicg_pexels_enable_prompt']) );
                    }
                    if ( array_key_exists( 'wpaicg_pexels_custom_prompt', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pexels_custom_prompt', sanitize_text_field($_POST['wpaicg_pexels_custom_prompt']) );
                    }
                    if ( array_key_exists( 'wpaicg_pixabay_type', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pixabay_type', sanitize_text_field($_POST['wpaicg_pixabay_type']) );
                    }
                    if ( array_key_exists( 'wpaicg_pixabay_language', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pixabay_language', sanitize_text_field($_POST['wpaicg_pixabay_language']) );
                    }
                    if ( array_key_exists( 'wpaicg_pixabay_order', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pixabay_order', sanitize_text_field($_POST['wpaicg_pixabay_order']) );
                    }
                    if ( array_key_exists( 'wpaicg_pixabay_orientation', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pixabay_orientation', sanitize_text_field($_POST['wpaicg_pixabay_orientation']) );
                    }
                    if ( array_key_exists( 'wpaicg_pixabay_enable_prompt', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pixabay_enable_prompt', sanitize_text_field($_POST['wpaicg_pixabay_enable_prompt']) );
                    }
                    if ( array_key_exists( 'wpaicg_pixabay_custom_prompt', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_pixabay_custom_prompt', sanitize_text_field($_POST['wpaicg_pixabay_custom_prompt']) );
                    }
                    if ( array_key_exists( '_wporg_add_tagline', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_add_tagline', sanitize_text_field($_POST['_wporg_add_tagline']) );
                    }
                    if ( array_key_exists( '_wporg_add_intro', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_add_intro', sanitize_text_field($_POST['_wporg_add_intro']) );
                    }
                    if ( array_key_exists( '_wporg_add_conclusion', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_add_conclusion', sanitize_text_field($_POST['_wporg_add_conclusion']) );
                    }
                    if ( array_key_exists( '_wporg_anchor_text', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_anchor_text', sanitize_text_field($_POST['_wporg_anchor_text']) );
                    }
                    if ( array_key_exists( '_wporg_target_url', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_target_url', sanitize_text_field($_POST['_wporg_target_url']) );
                    }
                    if ( array_key_exists( '_wporg_generated_text', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_generated_text', sanitize_text_field($_POST['_wporg_generated_text']) );
                    }
                    // _wporg_cta_pos
                    if ( array_key_exists( '_wporg_cta_pos', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_cta_pos', sanitize_text_field($_POST['_wporg_cta_pos']) );
                    }
                    // _wporg_target_url_cta
                    if ( array_key_exists( '_wporg_target_url_cta', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_target_url_cta', sanitize_text_field($_POST['_wporg_target_url_cta']) );
                    }
                    if ( array_key_exists( '_wporg_img_size', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_img_size', sanitize_text_field($_POST['_wporg_img_size']) );
                    }
                    if ( array_key_exists( '_wporg_img_style', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wporg_img_style', sanitize_text_field($_POST['_wporg_img_style']) );
                    }
                    if ( array_key_exists( 'wpaicg_seo_meta_desc', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wpaicg_seo_meta_desc', 1 );
                    }
                    if ( array_key_exists( 'wpaicg_custom_image_settings', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_custom_image_settings', wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_custom_image_settings']) );
                    }
                    if ( array_key_exists( 'wpaicg_custom_prompt_enable', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_custom_prompt_enable', sanitize_text_field($_POST['wpaicg_custom_prompt_enable']));
                    }
                    if ( array_key_exists( 'wpaicg_custom_prompt', $_POST ) && array_key_exists( 'wpaicg_custom_prompt_enable', $_POST ) && $_POST['wpaicg_custom_prompt_enable'] ) {
                        update_post_meta( $wpaicg_post_id, 'wpaicg_custom_prompt', wp_kses_post($_POST['wpaicg_custom_prompt']));
                    }
                    if ( array_key_exists( 'wpaicg_post_tags', $_POST ) ) {
                        update_post_meta( $wpaicg_post_id, '_wpaicg_post_tags', sanitize_text_field($_POST['wpaicg_post_tags']) );
                        if(!empty($_POST['wpaicg_post_tags'])){
                            $wpaicg_tags = array_map('trim', explode(',', sanitize_text_field($_POST['wpaicg_post_tags'])));
                            if($wpaicg_tags && is_array($wpaicg_tags) && count($wpaicg_tags)){
                                wp_set_post_tags($wpaicg_post_id,$wpaicg_tags);
                            }
                        }
                    }
                    if ( array_key_exists( '_wpaicg_meta_description', $_POST ) ) {
                        $this->wpaicg_save_description($wpaicg_post_id,sanitize_text_field($_POST['_wpaicg_meta_description']));
                    }
                    $this->wpaicg_post_image($wpaicg_post_id,$wpaicg_title);
                    $wpaicg_post = get_post($wpaicg_post_id);
                    $wpaicg_content = str_replace("__WPAICG_IMAGE__", '', $wpaicg_post->post_content);
                    wp_update_post(array(
                        'ID' => $wpaicg_post_id,
                        'post_content' => $wpaicg_content
                    ));
                    $wpaicg_result['status'] = 'success';
                    $wpaicg_result['id'] = $wpaicg_post_id;
                    if(isset($_REQUEST['save_source']) && $_REQUEST['save_source'] == 'promptbase'){

                    }
                    else {
                        /*Save Single Content Log*/
                        $wpaicg_duration = isset($_REQUEST['duration']) && !empty($_REQUEST['duration']) ? sanitize_text_field($_REQUEST['duration']) : 0;
                        $wpaicg_usage_token = isset($_REQUEST['usage_token']) && !empty($_REQUEST['usage_token']) ? sanitize_text_field($_REQUEST['usage_token']) : 0;
                        $wpaicg_word_count = isset($_REQUEST['word_count']) && !empty($_REQUEST['word_count']) ? sanitize_text_field($_REQUEST['word_count']) : 0;
                        $wpaicg_log_id = wp_insert_post(array(
                            'post_title' => 'WPAICGLOG:' . $wpaicg_title,
                            'post_type' => 'wpaicg_slog',
                            'post_status' => 'publish'
                        ));

                        $wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');

                        if ($wpaicg_provider === 'OpenAI') {
                            if (isset($_REQUEST['model']) && !empty($_REQUEST['model'])) {
                                $wpaicg_ai_model = sanitize_text_field($_REQUEST['model']);
                            } else {
                                $wpaicg_ai_model = get_option('wpaicg_ai_model', 'gpt-3.5-turbo-instruct');
                            }
                        } elseif ($wpaicg_provider === 'Azure') {
                            $wpaicg_ai_model = get_option('wpaicg_azure_deployment', '');
                        } elseif ($wpaicg_provider === 'Google') {
                            if (isset($_REQUEST['model']) && !empty($_REQUEST['model'])) {
                                $wpaicg_ai_model = sanitize_text_field($_REQUEST['model']);
                            } else {
                                $wpaicg_ai_model = get_option('wpaicg_google_default_model', 'gemini-pro');
                            }
                        } elseif ($wpaicg_provider === 'OpenRouter') {
                            if (isset($_REQUEST['model']) && !empty($_REQUEST['model'])) {
                                $wpaicg_ai_model = sanitize_text_field($_REQUEST['model']);
                            } else {
                                $wpaicg_ai_model = get_option('wpaicg_openrouter_default_model', 'openai/gpt-4o');
                            }
                        } 
                        
                        $source_log = 'writer';
                        if (isset($_REQUEST['source_log']) && !empty($_REQUEST['source_log'])) {
                            $source_log = sanitize_text_field($_REQUEST['source_log']);
                        }
                        add_post_meta($wpaicg_log_id, 'wpaicg_source_log', $source_log);
                        add_post_meta($wpaicg_log_id, 'wpaicg_ai_model', $wpaicg_ai_model);
                        add_post_meta($wpaicg_log_id, 'wpaicg_duration', $wpaicg_duration);
                        add_post_meta($wpaicg_log_id, 'wpaicg_usage_token', $wpaicg_usage_token);
                        add_post_meta($wpaicg_log_id, 'wpaicg_word_count', $wpaicg_word_count);
                        add_post_meta($wpaicg_log_id, 'wpaicg_post_id', $wpaicg_post_id);
                        // add provider
                        add_post_meta($wpaicg_log_id, 'wpaicg_provider', $wpaicg_provider);

                    }
                }

            }

            wp_send_json( $wpaicg_result );
        }
    }
    WPAICG_Content::get_instance();
}
