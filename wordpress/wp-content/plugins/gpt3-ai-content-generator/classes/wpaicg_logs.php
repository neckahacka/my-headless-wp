<?php
namespace WPAICG;
if ( ! defined( 'ABSPATH' ) ) exit;

if ( !class_exists( '\\WPAICG\\WPAICG_Logs' ) ) {
    class WPAICG_Logs
    {
        private static $instance = null;

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action('wp_ajax_aipower_refresh_logs_table', array($this, 'aipower_refresh_logs_table'));
            add_action('wp_ajax_aipower_load_log_details', array($this, 'aipower_load_log_details'));
            add_action('wp_ajax_aipower_delete_log', array($this, 'aipower_delete_log'));
            add_action('wp_ajax_aipower_delete_all_logs', array($this, 'aipower_delete_all_logs'));
            // Add export-related AJAX actions
            add_action('wp_ajax_aipower_count_export_logs', array($this, 'aipower_count_export_logs'));
            add_action('wp_ajax_aipower_check_uploads_writable', array($this, 'aipower_check_uploads_writable'));
            add_action('wp_ajax_aipower_export_logs', array($this, 'aipower_export_logs'));
            add_action('wp_ajax_aipower_revise_answer', array($this, 'aipower_revise_answer'));
            add_action('wp_ajax_aipower_load_prompt_details', array($this, 'aipower_load_prompt_details'));
            add_action('wp_ajax_aipower_block_ip', array($this, 'aipower_block_ip'));
        }

        /**
         * Handle AJAX Request to Load Prompt Details
         */
        public function aipower_load_prompt_details() {
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Validate required parameters
            if (!isset($_POST['log_id']) || !is_numeric($_POST['log_id']) || !isset($_POST['message_date']) || !is_numeric($_POST['message_date'])) {
                wp_send_json_error(array('message' => esc_html__('Invalid log ID or message identifier.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            $log_id = intval($_POST['log_id']);
            $message_date = intval($_POST['message_date']);
            global $wpdb;

            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';

            // Retrieve the specific log entry
            $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $logs_table WHERE id = %d", $log_id));

            if (!$log) {
                wp_send_json_error(array('message' => esc_html__('Log not found.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Decode the log data
            $data = json_decode($log->data, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                wp_send_json_error(array('message' => esc_html__('Invalid log data.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Search for the AI message with the specified date
            $request_data = null;
            foreach ($data as $message) {
                if ($message['type'] === 'ai' && isset($message['date']) && intval($message['date']) === $message_date) {
                    if (isset($message['request']) && !empty($message['request'])) {
                        $request_data = $message['request'];
                    }
                    break;
                }
            }

            if ($request_data) {
                wp_send_json_success(array('request' => $request_data));
            } else {
                wp_send_json_success(array('request' => null));
            }

            wp_die();
        }

        /**
         * Get the latest user message and its corresponding AI response from the log data.
         *
         * @param string $data JSON-encoded log data.
         * @return array Associative array with 'user' and 'ai' keys.
         */
        private static function get_latest_conversation($data) {
            $messages = json_decode($data, true);
            $latest_user_message = '';
            $latest_ai_message = '';
            $max_length = 50; // Maximum characters to display

            if (is_array($messages)) {
                // Iterate in reverse to find the latest user message and its AI response
                for ($i = count($messages) - 1; $i >= 0; $i--) {
                    if (isset($messages[$i]['type']) && $messages[$i]['type'] === 'user') {
                        $latest_user_message = sanitize_text_field($messages[$i]['message']);
                        // Truncate the user message
                        if (strlen($latest_user_message) > $max_length) {
                            $latest_user_message = substr($latest_user_message, 0, $max_length) . '...';
                        }
                        // Check if the next message is AI
                        if (isset($messages[$i + 1]['type']) && $messages[$i + 1]['type'] === 'ai') {
                            $latest_ai_message = sanitize_text_field($messages[$i + 1]['message']);
                            // Truncate the AI message
                            if (strlen($latest_ai_message) > $max_length) {
                                $latest_ai_message = substr($latest_ai_message, 0, $max_length) . '...';
                            }
                        }
                        break;
                    }
                }
            }

            return array(
                'user' => $latest_user_message,
                'ai' => $latest_ai_message
            );
        }


        /**
         * Render the Logs Table with Optional Search
         *
         * @param int    $paged       Current page number.
         * @param string $search_term Search term for filtering logs.
         * @return string HTML of the logs table.
         */
        public static function aipower_render_logs_table($paged = 1, $search_term = '') {
            global $wpdb;
        
            $logs_per_page = 10;
            $offset = ($paged - 1) * $logs_per_page;
            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';
        
            // Check if the table exists, if not, create it
            if ($wpdb->get_var("SHOW TABLES LIKE '$logs_table'") != $logs_table) {
                $charset_collate = $wpdb->get_charset_collate();
                $sql = "CREATE TABLE $logs_table (
                    id mediumint(11) NOT NULL AUTO_INCREMENT,
                    log_session VARCHAR(255) NOT NULL,
                    data LONGTEXT NOT NULL,
                    page_title TEXT DEFAULT NULL,
                    source VARCHAR(255) DEFAULT NULL,
                    created_at VARCHAR(255) NOT NULL,
                    PRIMARY KEY (id)
                ) $charset_collate;";
        
                require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
                dbDelta($sql);
            }
        
            $query = "SELECT * FROM $logs_table";
            $query_params = array();
        
            if (!empty($search_term)) {
                $search_condition = " WHERE log_session LIKE %s OR page_title LIKE %s OR source LIKE %s OR data LIKE %s";
                $query .= $search_condition;
                $like_term = '%' . $wpdb->esc_like($search_term) . '%';
                $query_params = array($like_term, $like_term, $like_term, $like_term);
            }
        
            // Fetch all matching logs (no LIMIT yet)
            $query .= " ORDER BY created_at DESC";
            if (!empty($query_params)) {
                $all_logs = $wpdb->get_results($wpdb->prepare($query, $query_params));
            } else {
                $all_logs = $wpdb->get_results($query);
            }
        
            // Now we have all logs that match the search
            $total_logs = count($all_logs);
            $total_pages = ceil($total_logs / $logs_per_page);
        
            // Slice the array to get only the logs for the current page
            $logs = array_slice($all_logs, $offset, $logs_per_page);
        
            // Arrays to hold all leads and feedback from ALL matching logs (not just current page)
            $all_leads = array();
            $all_feedback = array();
        
            // Process ALL logs to gather all leads and feedback
            if ($all_logs) {
                foreach ($all_logs as $log) {
                    $data = json_decode($log->data, true);
        
                    if (is_array($data)) {
                        $num_messages = count($data);
                        for ($i = 0; $i < $num_messages; $i++) {
                            $message = $data[$i];
                            if ($message['type'] === 'user') {
                                $user_text = isset($message['message']) ? $message['message'] : '';
                                $user_date = isset($message['date']) ? intval($message['date']) : 0;
                                $user_date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $user_date);
        
                                // Attempt to find the next AI message
                                $ai_text = '';
                                $ai_date_formatted = '';
                                if (($i + 1) < $num_messages && isset($data[$i+1]['type']) && $data[$i+1]['type'] === 'ai') {
                                    $ai_msg = $data[$i+1];
                                    $ai_text = $ai_msg['message'];
                                    $ai_date = intval($ai_msg['date']);
                                    $ai_date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $ai_date);
                                }
        
                                // Check for user feedback
                                if (isset($message['userfeedback']) && is_array($message['userfeedback'])) {
                                    foreach ($message['userfeedback'] as $fb) {
                                        $all_feedback[] = array(
                                            'user_message' => $user_text,
                                            'user_timestamp' => $user_date_formatted,
                                            'ai_message' => $ai_text,
                                            'ai_timestamp' => $ai_date_formatted,
                                            'feedback_type' => $fb['type'], // 'up' or 'down'
                                            'feedback_details' => $fb['details']
                                        );
                                    }
                                }
        
                                // Leads
                                if (isset($message['lead_data']) && is_array($message['lead_data'])) {
                                    $all_leads[] = array(
                                        'user_message' => $user_text,
                                        'user_timestamp' => $user_date_formatted,
                                        'ai_message' => $ai_text,
                                        'ai_timestamp' => $ai_date_formatted,
                                        'name' => isset($message['lead_data']['name']) ? $message['lead_data']['name'] : '',
                                        'email' => isset($message['lead_data']['email']) ? $message['lead_data']['email'] : '',
                                        'phone' => isset($message['lead_data']['phone']) ? $message['lead_data']['phone'] : ''
                                    );
                                }
                            }
                        }
                    }
                }
            }
        
            ob_start();
        
            if ($logs) {
                ?>
                <div id="aipower-logs-table-container">
                    <table class="aipower-logs-table">
                        <colgroup>
                            <col style="width: 45%;">
                            <col style="width: 10%;">
                            <col style="width: 15%;">
                        </colgroup>
        
                        <thead>
                            <tr>
                                <th><?php echo esc_html__('Message', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php echo esc_html__('Token', 'gpt3-ai-content-generator'); ?></th>
                                <th><?php echo esc_html__('Actions', 'gpt3-ai-content-generator'); ?></th>
                            </tr>
                        </thead>
        
                        <tbody>
                            <?php
                            $pricing = \WPAICG\WPAICG_Util::get_instance()->model_pricing;
        
                            foreach ($logs as $log) {
                                $latest_conversation = self::get_latest_conversation($log->data);
                                $time_diff = human_time_diff(intval($log->created_at), current_time('timestamp')) . ' ' . __('ago', 'gpt3-ai-content-generator');
        
                                $data = json_decode($log->data, true);
                                $user_message_count = 0;
                                $lead_count = 0;
                                $feedback_count = 0;
                                $total_tokens = 0;
                                $has_flagged = false;
                                $flag_reasons = array();
                                $message_details = array();
                                $message_number = 1;
        
                                if (is_array($data)) {
                                    $num_messages = count($data);
                                    for ($i = 0; $i < $num_messages; $i++) {
                                        $message = $data[$i];
        
                                        if ($message['type'] === 'user') {
                                            $user_message_count++;
                                            $user_text = isset($message['message']) ? $message['message'] : '';
                                            $user_date = isset($message['date']) ? intval($message['date']) : 0;
                                            $user_date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $user_date);
        
                                            // Attempt to find the next AI message
                                            $ai_text = '';
                                            $ai_date_formatted = '';
                                            if (($i + 1) < $num_messages && isset($data[$i+1]['type']) && $data[$i+1]['type'] === 'ai') {
                                                $ai_msg = $data[$i+1];
                                                $ai_text = $ai_msg['message'];
                                                $ai_date = intval($ai_msg['date']);
                                                $ai_date_formatted = date_i18n(get_option('date_format') . ' ' . get_option('time_format'), $ai_date);
                                            }
        
                                            // Check for user feedback
                                            if (isset($message['userfeedback']) && is_array($message['userfeedback'])) {
                                                foreach ($message['userfeedback'] as $fb) {
                                                    $feedback_count++;
                                                }
                                            }
        
                                            // Leads
                                            if (isset($message['lead_data']) && is_array($message['lead_data'])) {
                                                $lead_count++;
                                            }
                                        }
        
                                        if (isset($message['type']) && $message['type'] === 'ai' && isset($message['token'])) {
                                            $total_tokens += intval($message['token']);
                                        }
        
                                        if (isset($message['type']) && $message['type'] === 'ai') {
                                            $model = isset($message['request']['model']) ? sanitize_text_field($message['request']['model']) : 'N/A';
                                            $token = isset($message['token']) ? intval($message['token']) : 0;
                                            $cost = isset($pricing[$model]) ? number_format(($token / 1000) * $pricing[$model], 8) : 0;
                                            $message_details[] = array(
                                                'number' => $message_number,
                                                'model'  => $model,
                                                'token'  => $token,
                                                'cost'   => $cost
                                            );
                                            $message_number++;
                                        }
        
                                        if (isset($message['flag']) && $message['flag'] !== false) {
                                            $has_flagged = true;
                                            $flag_reasons[] = sanitize_text_field($message['flag']);
                                        }
                                    }
                                }
        
                                $encoded_message_details = esc_attr(json_encode($message_details));
                                ?>
                                <tr>
                                    <td class="aipower-clickable-message" data-id="<?php echo esc_attr($log->id); ?>">
                                        <?php if ($latest_conversation['user']) : ?>
                                            <strong><?php echo esc_html($latest_conversation['user']); ?></strong>
                                        <?php endif; ?>
                                        <?php if ($latest_conversation['ai']) : ?>
                                            <?php echo esc_html($latest_conversation['ai']); ?>
                                        <?php endif; ?>
                                        <div class="aipower-message-badges">
                                            <?php if ($feedback_count > 0): ?>
                                                <span class="aipower-badge aipower-feedback-badge" title="<?php echo esc_attr__('Has Feedback', 'gpt3-ai-content-generator'); ?>"><?php echo esc_html__('Feedback', 'gpt3-ai-content-generator'); ?></span>
                                            <?php endif; ?>
                                            <?php if ($lead_count > 0): ?>
                                                <span class="aipower-badge aipower-lead-badge" title="<?php echo esc_attr__('Has Lead', 'gpt3-ai-content-generator'); ?>"><?php echo esc_html__('Lead', 'gpt3-ai-content-generator'); ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <div class="aipower-timing">
                                            <?php
                                            echo esc_html($time_diff) . ', ' . esc_html($user_message_count) . ' ' . esc_html__('messages', 'gpt3-ai-content-generator');
                                            if ($feedback_count > 0) {
                                                echo ', ' . esc_html($feedback_count) . ' ' . esc_html__('feedback', 'gpt3-ai-content-generator');
                                            }
                                            if ($lead_count === 1) {
                                                echo ', ' . esc_html($lead_count) . ' ' . esc_html__('lead', 'gpt3-ai-content-generator');
                                            } elseif ($lead_count > 1) {
                                                echo ', ' . esc_html($lead_count) . ' ' . esc_html__('leads', 'gpt3-ai-content-generator');
                                            }
                                            if ($has_flagged) {
                                                echo ', <span class="dashicons dashicons-flag aipower-flag-icon" title="' . esc_attr__('Flagged Message(s)', 'gpt3-ai-content-generator') . '"></span>';
                                            }
                                            ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="aipower-token-cell">
                                            <?php echo esc_html($total_tokens); ?>
                                            <span class="dashicons dashicons-info aipower-log-info-icon" data-details='<?php echo $encoded_message_details; ?>' title="<?php echo esc_attr__('View Token Details', 'gpt3-ai-content-generator'); ?>"></span>
                                        </div>
                                    </td>
                                    <td style="position: relative;">
                                        <span class="dashicons dashicons-trash aipower-delete-log-icon" data-id="<?php echo esc_attr($log->id); ?>" title="<?php echo esc_attr__('Delete Log', 'gpt3-ai-content-generator'); ?>"></span>
                                        <div class="aipower-single-delete-confirmation">
                                            <span><?php echo esc_html__('Sure?', 'gpt3-ai-content-generator'); ?></span>
                                            <span class="aipower-single-confirm-yes" data-id="<?php echo esc_attr($log->id); ?>"><?php echo esc_html__('Yes', 'gpt3-ai-content-generator'); ?></span>
                                        </div>
                                    </td>
                                </tr>
                                <?php
                            }
                            ?>
                        </tbody>
                    </table>
        
                    <?php if ($total_pages > 1): ?>
                    <div class="aipower-log-pagination">
                        <?php
                        $range = 2;
                        $first_last = 2;
        
                        if ($total_pages <= ($first_last * 2 + $range)) {
                            for ($i = 1; $i <= $total_pages; $i++) {
                                echo '<button class="aipower-log-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                            }
                        } else {
                            for ($i = 1; $i <= $first_last; $i++) {
                                echo '<button class="aipower-log-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                            }
        
                            if ($paged > $first_last + $range + 1) {
                                echo '<span>...</span>';
                            }
        
                            $start = max($first_last + 1, $paged - $range);
                            $end = min($paged + $range, $total_pages - $first_last);
        
                            for ($i = $start; $i <= $end; $i++) {
                                if ($i > $first_last && $i <= $total_pages - $first_last) {
                                    echo '<button class="aipower-log-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                                }
                            }
        
                            if ($paged < $total_pages - $first_last - $range) {
                                echo '<span>...</span>';
                            }
        
                            for ($i = $total_pages - $first_last + 1; $i <= $total_pages; $i++) {
                                if ($i > $end) {
                                    echo '<button class="aipower-log-page-btn" data-page="' . esc_attr($i) . '" ' . ($i == $paged ? 'disabled' : '') . '>' . esc_html($i) . '</button>';
                                }
                            }
                        }
                        ?>
                    </div>
                    <?php endif; ?>
        
                </div>
                <?php
            } else {
                echo '<p>' . esc_html__('No logs found.', 'gpt3-ai-content-generator') . '</p>';
            }
        
            $html = ob_get_clean();
        
            return array(
                'html' => $html,
                'leads' => $all_leads,
                'feedback' => $all_feedback
            );
        }

        /**
         * Handle AJAX Request to Refresh Logs Table
         */
        public function aipower_refresh_logs_table() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            $paged = isset($_POST['page']) ? intval($_POST['page']) : 1; // Get current page
            $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

            // Use the pagination and search term when rendering the table
            $table_html = self::aipower_render_logs_table($paged, $search_term);

            wp_send_json_success(array('table' => $table_html));
        }

        /**
         * Handle AJAX Request to Load Log Details
         */
        public function aipower_load_log_details() {

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['log_id']) || !is_numeric($_POST['log_id'])) {
                wp_send_json_error(array('message' => esc_html__('Invalid log ID.', 'gpt3-ai-content-generator')));
                return;
            }

            $log_id = intval($_POST['log_id']);
            global $wpdb;

            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';

            $log = $wpdb->get_row($wpdb->prepare("SELECT * FROM $logs_table WHERE id = %d", $log_id));

            if (!$log) {
                wp_send_json_error(array('message' => esc_html__('Log not found.', 'gpt3-ai-content-generator')));
                return;
            }

            // Decode the log data
            $data = json_decode($log->data, true);

            if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
                wp_send_json_error(array('message' => esc_html__('Invalid log data.', 'gpt3-ai-content-generator')));
                return;
            }

            // Initialize username and ip
            $username = 'Guest';
            $ip = '';

            // Iterate through messages to find username and ip from user messages
            foreach ($data as $message) {
                if ($message['type'] === 'user') {
                    if (!empty($message['username'])) {
                        $username = sanitize_text_field($message['username']);
                    }
                    if (!empty($message['ip'])) {
                        $ip = sanitize_text_field($message['ip']);
                    }
                    // Assuming the same username and ip for the entire log, break after first user message
                    break;
                }
            }

            // Calculate total tokens from AI messages
            $total_tokens = 0;
            foreach ($data as $message) {
                if ($message['type'] === 'ai' && isset($message['token'])) {
                    $total_tokens += intval($message['token']);
                }
            }

            // Build the log details HTML
            ob_start();
            ?>
            <div class="aipower-log-details">
                <!-- Source Section -->
                <h2><?php echo esc_html__('Source:', 'gpt3-ai-content-generator') . ' ' . (!empty($log->page_title) ? esc_html($log->page_title) : 'Dashboard') . ' - ' . esc_html($log->source); ?></h2>
                <!-- Username and IP Section -->
                <p>
                    <strong><?php echo esc_html__('User:', 'gpt3-ai-content-generator'); ?></strong>
                    <?php echo esc_html($username); ?>
                </p>
                <p>
                    <?php if (!empty($ip)): ?>
                        <strong><?php echo esc_html__('IP:', 'gpt3-ai-content-generator'); ?></strong>
                        <?php echo esc_html($ip); ?>
                        <!-- Block IP Icon -->
                        <span class="dashicons dashicons-no aipower-block-ip-icon" data-ip="<?php echo esc_attr($ip); ?>" title="<?php echo esc_attr__('Block IP', 'gpt3-ai-content-generator'); ?>"></span>
                    <?php endif; ?>
                    <!-- Inline confirmation prompt for Blocking IP -->
                    <div id="aipower-block-ip-confirmation" class="aipower-confirmation" style="display:none;">
                        <span><?php echo esc_html__('Are you sure you want to block this IP?', 'gpt3-ai-content-generator'); ?></span>
                        <span id="aipower-block-ip-confirm-yes" class="aipower-confirm-yes"><?php echo esc_html__('Yes', 'gpt3-ai-content-generator'); ?></span>
                    </div>
                </p>
                <p>
                    <?php if (!empty($ip)): ?>
                        <strong><?php echo esc_html__('Token:', 'gpt3-ai-content-generator'); ?></strong>
                        <?php echo esc_html($total_tokens); ?>
                    <?php endif; ?>
                </p>

                <!-- Log Messages Section -->
                <div class="aipower-log-messages">
                    <?php
                    $num_messages = count($data);
                    for ($i = 0; $i < $num_messages; $i++) {
                        $message = $data[$i];
                        if ($message['type'] === 'user') {
                            // Start a container for this conversation
                            ?>
                            <div class="aipower-conversation">
                                <div class="aipower-log-message user">
                                    <p>
                                        <strong><?php echo esc_html__('User:', 'gpt3-ai-content-generator'); ?></strong> 
                                        <?php echo esc_html($message['message']); ?>
                                        <?php if (isset($message['flag']) && $message['flag'] !== false): ?>
                                            <!-- Flag Icon with Tooltip -->
                                            <span class="dashicons dashicons-flag aipower-flag-icon" title="<?php echo esc_attr__('Flag Reason: ' . $message['flag']); ?>"></span>
                                            <span class="aipower-flag-reasons"><?php echo esc_html__('Flagged as', 'gpt3-ai-content-generator') . ' ' . esc_html($message['flag']); ?></span>
                                        <?php endif; ?>
                                    </p>
                                    <p><em><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($message['date']))); ?></em></p>
                                </div>

                                <?php
                                // **Add this block to display lead details**
                                if (isset($message['lead_data']) && is_array($message['lead_data'])) {
                                    echo '<div class="aipower-lead-details">';
                                    echo '<h4>' . esc_html__('Lead Details', 'gpt3-ai-content-generator') . '</h4>';
                                    if (!empty($message['lead_data']['name'])) {
                                        echo '<p><strong>' . esc_html__('Name:', 'gpt3-ai-content-generator') . '</strong> ' . esc_html($message['lead_data']['name']) . '</p>';
                                    }
                                    if (!empty($message['lead_data']['email'])) {
                                        echo '<p><strong>' . esc_html__('Email:', 'gpt3-ai-content-generator') . '</strong> ' . esc_html($message['lead_data']['email']) . '</p>';
                                    }
                                    if (!empty($message['lead_data']['phone'])) {
                                        echo '<p><strong>' . esc_html__('Phone:', 'gpt3-ai-content-generator') . '</strong> ' . esc_html($message['lead_data']['phone']) . '</p>';
                                    }
                                    echo '</div>';
                                }
                                ?>

                                <?php
                                // Check if there is a next message
                                if (($i + 1) < $num_messages) {
                                    $next_message = $data[$i + 1];
                                    if ($next_message['type'] === 'ai') {
                                        // Output the AI response with additional elements
                                        ?>
                                        <div class="aipower-log-message ai">
                                            <p><strong><?php echo esc_html__('AI:', 'gpt3-ai-content-generator'); ?></strong> <?php echo self::aipower_simple_markdown_parser($next_message['message']); ?></p>
                                            <p><em><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($next_message['date']))); ?></em></p>
                                            
                                            <?php
                                            // Check and display Confident Score if available
                                            if (isset($next_message['matches']) && !empty($next_message['matches'])) {
                                                // Convert the score to percentage and round to nearest whole number
                                                $score_float = floatval($next_message['matches'][0]['score']);
                                                $confident_score = round($score_float * 100) . '%';
                                                ?>
                                                <div class="aipower-additional-info">
                                                    <div class="aipower-confident-score"><?php echo esc_html__('Confidence:', 'gpt3-ai-content-generator') . ' ' . esc_html($confident_score); ?></div>
                                                    <div class="aipower-prompt-details" data-log-id="<?php echo esc_attr($log->id); ?>" data-message-date="<?php echo esc_attr($next_message['date']); ?>">
                                                        <?php echo esc_html__('Prompt Details', 'gpt3-ai-content-generator'); ?>
                                                    </div>
                                                    <div class="aipower-revise-answer" data-user-message="<?php echo esc_attr($message['message']); ?>" data-ai-message="<?php echo esc_attr($next_message['message']); ?>">
                                                        <?php echo esc_html__('Revise Answer', 'gpt3-ai-content-generator'); ?>
                                                    </div>
                                                </div>
                                                <?php
                                            } else {
                                                // Display placeholders without Confident Score
                                                ?>
                                                <div class="aipower-additional-info">
                                                    <div class="aipower-prompt-details" data-log-id="<?php echo esc_attr($log->id); ?>" data-message-date="<?php echo esc_attr($message['date']); ?>">
                                                        <?php echo esc_html__('Prompt Details', 'gpt3-ai-content-generator'); ?>
                                                    </div>
                                                    <div class="aipower-revise-answer" data-user-message="<?php echo esc_attr($message['message']); ?>" data-ai-message="<?php echo esc_attr($next_message['message']); ?>">
                                                        <?php echo esc_html__('Revise Answer', 'gpt3-ai-content-generator'); ?>
                                                    </div>
                                                </div>
                                                <?php
                                            }
                                            ?>
                                            <?php
                                            // Display Feedback Comments Below AI Date**
                                            if (isset($message['userfeedback']) && is_array($message['userfeedback'])) {
                                                echo '<div class="aipower-log-feedback">';
                                                echo '<strong>User Feedback: </strong>';
                                                foreach ($message['userfeedback'] as $feedback) {
                                                    if ($feedback['type'] === 'up') {
                                                        // Thumbs Up - Green
                                                        echo '<span class="dashicons dashicons-thumbs-up aipower-log-feedback-icon" title="' . esc_attr__('Thumbs Up', 'gpt3-ai-content-generator') . '"></span> ';
                                                        echo '<span class="aipower-log-feedback-details">' . esc_html($feedback['details']) . '</span><br>';
                                                    } elseif ($feedback['type'] === 'down') {
                                                        // Thumbs Down - Red
                                                        echo '<span class="dashicons dashicons-thumbs-down aipower-log-feedback-icon" title="' . esc_attr__('Thumbs Down', 'gpt3-ai-content-generator') . '"></span> ';
                                                        echo '<span class="aipower-log-feedback-details">' . esc_html($feedback['details']) . '</span><br>';
                                                    }
                                                }
                                                echo '</div>';
                                            }
                                            ?>
                                        </div>
                                        <?php
                                        // Increment the index since we've processed the AI message
                                        $i++;
                                    }
                                }
                                ?>
                            </div>
                            <?php
                        } elseif ($message['type'] === 'ai') {
                            // Handle AI messages without a preceding user message
                            ?>
                            <div class="aipower-conversation">
                                <div class="aipower-log-message ai">
                                    <p><strong><?php echo esc_html__('AI:', 'gpt3-ai-content-generator'); ?></strong> <?php echo esc_html($message['message']); ?></p>
                                    <p><em><?php echo esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), intval($message['date']))); ?></em></p>
                                    
                                    <?php
                                    // Check and display Confident Score if available
                                    if (isset($message['matches']) && !empty($message['matches'])) {
                                        // Convert the score to percentage and round to nearest whole number
                                        $score_float = floatval($message['matches'][0]['score']);
                                        $confident_score = round($score_float * 100) . '%';
                                        ?>
                                        <div class="aipower-additional-info">
                                            <div class="aipower-confident-score"><?php echo esc_html__('Confidence:', 'gpt3-ai-content-generator') . ' ' . esc_html($confident_score); ?></div>
                                            <div class="aipower-prompt-details"><?php echo esc_html__('Prompt Details', 'gpt3-ai-content-generator'); ?></div>
                                            <div class="aipower-revise-answer" data-user-message="" data-ai-message="<?php echo esc_attr($message['message']); ?>">
                                                <?php echo esc_html__('Revise Answer', 'gpt3-ai-content-generator'); ?>
                                            </div>
                                        </div>
                                        <?php
                                    } else {
                                        // Display placeholders without Confident Score
                                        ?>
                                        <div class="aipower-additional-info">
                                            <div class="aipower-prompt-details"><?php echo esc_html__('Prompt Details', 'gpt3-ai-content-generator'); ?></div>
                                            <div class="aipower-revise-answer" data-user-message="" data-ai-message="<?php echo esc_attr($message['message']); ?>">
                                                <?php echo esc_html__('Revise Answer', 'gpt3-ai-content-generator'); ?>
                                            </div>
                                        </div>
                                        <?php
                                    }
                                    ?>
                                </div>
                            </div>
                            <?php
                        }
                    }
                    ?>
                </div>
            </div>
            <?php
            $log_details = ob_get_clean();

            wp_send_json_success(array('log_details' => $log_details));
        }

        /**
         * Handle AJAX Request to Revise Answer
         */
        public function aipower_revise_answer() {
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Check if 'content' is set
            if (!isset($_POST['content']) || empty($_POST['content'])) {
                wp_send_json_error(array('message' => esc_html__('No content provided.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            $content = sanitize_text_field($_POST['content']);

            // Call the existing backend function to save the embedding
            $result = \WPAICG\WPAICG_Embeddings::get_instance()->wpaicg_save_embedding($content);

            if ($result['status'] === 'success') {
                wp_send_json_success(array('message' => esc_html__('Answer revised successfully. Try asking your bot the same question.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => $result['msg']));
            }

            wp_die();
        }


        /**
         * Handle AJAX Request to Delete a Single Log
         */
        public function aipower_delete_log() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['log_id']) || !is_numeric($_POST['log_id'])) {
                wp_send_json_error(array('message' => esc_html__('Invalid log ID.', 'gpt3-ai-content-generator')));
                return;
            }

            $log_id = intval($_POST['log_id']);
            global $wpdb;

            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';

            $deleted = $wpdb->delete($logs_table, array('id' => $log_id), array('%d'));

            if ($deleted) {
                wp_send_json_success(array('message' => esc_html__('Log deleted successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Failed to delete log.', 'gpt3-ai-content-generator')));
            }
        }

        /**
         * Handle AJAX Request to Delete All Logs
         */
        public function aipower_delete_all_logs() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            global $wpdb;

            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';

            // Check if there are any logs to delete
            $total_logs = $wpdb->get_var("SELECT COUNT(*) FROM $logs_table");

            if ($total_logs == 0) {
                wp_send_json_error(array('message' => esc_html__('There are no logs to delete.', 'gpt3-ai-content-generator')));
                return;
            }

            // Proceed to delete all logs
            $deleted = $wpdb->query("TRUNCATE TABLE $logs_table");

            if ($deleted !== false) {
                wp_send_json_success(array('message' => esc_html__('All logs deleted successfully.', 'gpt3-ai-content-generator')));
            } else {
                wp_send_json_error(array('message' => esc_html__('Failed to delete logs.', 'gpt3-ai-content-generator')));
            }
        }
        
        /**
         * Handle AJAX Request to Count Logs for Export
         */
        public function aipower_count_export_logs() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => __('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

            global $wpdb;
            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';

            $query = "SELECT COUNT(*) FROM $logs_table";
            $query_params = array();

            if (!empty($search_term)) {
                $like_term = '%' . $wpdb->esc_like($search_term) . '%';
                $query .= " WHERE log_session LIKE %s OR page_title LIKE %s OR source LIKE %s OR data LIKE %s";
                $query_params = array($like_term, $like_term, $like_term, $like_term);
            }

            $total_logs = $wpdb->get_var($wpdb->prepare($query, $query_params));

            wp_send_json_success(array('total_logs' => intval($total_logs)));
        }

        /**
         * Handle AJAX Request to Check if Uploads Directory is Writable
         */
        public function aipower_check_uploads_writable() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => __('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            $upload_dir = wp_upload_dir();

            if (is_writable($upload_dir['basedir'])) {
                wp_send_json_success();
            } else {
                wp_send_json_error(array('message' => __('The uploads folder is not writable. Please check the folder permissions.', 'gpt3-ai-content-generator')));
            }
        }

        /**
         * Handle AJAX Request to Export Logs to CSV
         */
        public function aipower_export_logs() {
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                return;
            }

            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => __('Nonce verification failed', 'gpt3-ai-content-generator')));
                return;
            }

            $search_term = isset($_POST['search_term']) ? sanitize_text_field($_POST['search_term']) : '';

            global $wpdb;
            $logs_table = $wpdb->prefix . 'wpaicg_chatlogs';

            // Prepare the query
            $query = "SELECT * FROM $logs_table";
            $query_params = array();

            if (!empty($search_term)) {
                $like_term = '%' . $wpdb->esc_like($search_term) . '%';
                $query .= " WHERE log_session LIKE %s OR page_title LIKE %s OR source LIKE %s OR data LIKE %s";
                $query_params = array($like_term, $like_term, $like_term, $like_term);
            }

            $query .= " ORDER BY created_at DESC";

            // Retrieve the logs
            $logs = $wpdb->get_results($wpdb->prepare($query, $query_params));

            if (!$logs) {
                wp_send_json_error(array('message' => __('No logs found to export.', 'gpt3-ai-content-generator')));
                return;
            }

            // Check uploads directory again for safety
            $upload_dir = wp_upload_dir();
            if (!is_writable($upload_dir['basedir'])) {
                wp_send_json_error(array('message' => __('The uploads folder is not writable. Please check the folder permissions.', 'gpt3-ai-content-generator')));
                return;
            }

            // Generate filename
            $timestamp = current_time('Ymd_His');
            $filename = "aipower_chat_logs_{$timestamp}.csv";
            $file_path = trailingslashit($upload_dir['basedir']) . $filename;

            // Open file for writing
            $file = fopen($file_path, 'w');
            if ($file === false) {
                wp_send_json_error(array('message' => __('Failed to create CSV file.', 'gpt3-ai-content-generator')));
                return;
            }

            // Write CSV headers
            fputcsv($file, array('ID', 'Session ID', 'Page Title', 'Source', 'Created At', 'Data'));

            // Iterate through logs and write to CSV
            foreach ($logs as $log) {
                // Optionally, you can format the data as needed
                fputcsv($file, array(
                    $log->id,
                    $log->log_session,
                    $log->page_title,
                    $log->source,
                    $log->created_at,
                    $log->data
                ));
            }

            fclose($file);

            // Generate file URL
            $file_url = trailingslashit($upload_dir['baseurl']) . $filename;

            wp_send_json_success(array('file_url' => esc_url($file_url)));
        }

        /**
         * Handle AJAX Request to Block an IP
         */
        public function aipower_block_ip() {
            // Check user permissions
            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => esc_html__('You do not have sufficient permissions to perform this action.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Verify nonce
            if (!isset($_POST['_wpnonce']) || !wp_verify_nonce($_POST['_wpnonce'], 'wpaicg_save_ai_engine_nonce')) {
                wp_send_json_error(array('message' => esc_html__('Nonce verification failed.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Validate the IP address
            if (!isset($_POST['ip']) || !filter_var($_POST['ip'], FILTER_VALIDATE_IP)) {
                wp_send_json_error(array('message' => esc_html__('Invalid IP address.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            $ip_to_block = sanitize_text_field($_POST['ip']);

            // Get the current list of banned IPs
            $banned_ips_option = get_option('wpaicg_banned_ips', '');
            $banned_ips = array_filter(array_map('trim', explode(',', $banned_ips_option)));

            // Check if the IP is already blocked
            if (in_array($ip_to_block, $banned_ips)) {
                wp_send_json_error(array('message' => esc_html__('This IP is already blocked.', 'gpt3-ai-content-generator')));
                wp_die();
            }

            // Add the new IP to the banned list
            $banned_ips[] = $ip_to_block;
            $new_banned_ips = implode(', ', $banned_ips);

            // Update the option in the database
            update_option('wpaicg_banned_ips', $new_banned_ips);

            wp_send_json_success(array('message' => esc_html__('IP has been successfully blocked.', 'gpt3-ai-content-generator')));
            wp_die();
        }

        /**
         * Simple Markdown Parser
         *
         * @param string $text The markdown text to parse.
         * @return string The parsed HTML.
         */
        private static function aipower_simple_markdown_parser($text) {
            if (empty($text)) {
                return '';
            }

            // Escape HTML to prevent XSS
            $text = esc_html($text);

            // Convert **bold** to <strong>
            $text = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $text);

            // Convert *italic* to <em>
            $text = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $text);

            // Convert URLs to clickable links
            $text = make_clickable($text);

            // Convert newlines to <br>
            $text = nl2br($text);

            return $text;
        }

    }
    WPAICG_Logs::get_instance();
}
?>
