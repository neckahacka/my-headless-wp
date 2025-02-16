<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

global $wpdb, $wp_roles;

/**
 * Helper function: Summation of free tokens for a specific module (chat, forms, etc.).
 * For 'chat', it sums all widgets, shortcodes, and custom chatbots into one total.
 */
function wpaicg_calculate_free_tokens_for_user( $user_id, $module ) {
    // Retrieve user roles
    $user_info  = get_userdata( $user_id );
    $user_roles = $user_info ? (array) $user_info->roles : [];

    $total_free = 0;

    // Helper: sum up any role-based limits
    $find_role_limit = function( $role_limited_array, $roles ) {
        $sum = 0;
        if ( is_array( $role_limited_array ) ) {
            foreach ( $roles as $r ) {
                if ( isset($role_limited_array[$r]) && $role_limited_array[$r] > 0 ) {
                    $sum += (float) $role_limited_array[$r];
                }
            }
        }
        return $sum;
    };

    // Handle 'chat' (widget + shortcode + custom chatbots) in a single sum
    if ( $module === 'chat' ) {
        // 1) Widget
        $widget_options = get_option('wpaicg_chat_widget', []);
        if ( $widget_options ) {
            $temp = 0;
            if ( ! empty($widget_options['user_limited']) && ! empty($widget_options['user_tokens']) ) {
                $temp += (float) $widget_options['user_tokens'];
            }
            if ( ! empty($widget_options['role_limited']) && ! empty($widget_options['limited_roles']) ) {
                $temp += $find_role_limit( $widget_options['limited_roles'], $user_roles );
            }
            $total_free += $temp;
        }

        // 2) Shortcode
        $shortcode_options = get_option('wpaicg_chat_shortcode_options', []);
        if ( $shortcode_options ) {
            $temp = 0;
            if ( ! empty($shortcode_options['user_limited']) && ! empty($shortcode_options['user_tokens']) ) {
                $temp += (float) $shortcode_options['user_tokens'];
            }
            if ( ! empty($shortcode_options['role_limited']) && ! empty($shortcode_options['limited_roles']) ) {
                $temp += $find_role_limit( $shortcode_options['limited_roles'], $user_roles );
            }
            $total_free += $temp;
        }

        // 3) Custom chatbots
        $bots = new WP_Query([
            'post_type'      => 'wpaicg_chatbot',
            'posts_per_page' => -1
        ]);
        if ( $bots->have_posts() ) {
            while ( $bots->have_posts() ) {
                $bots->the_post();
                $bot_id      = get_the_ID();
                $raw_content = get_post_field('post_content', $bot_id);
                if ( ! $raw_content ) {
                    continue;
                }
                $bot_settings = json_decode($raw_content, true);
                if ( ! is_array($bot_settings) ) {
                    continue;
                }
                $temp = 0;
                if ( ! empty($bot_settings['user_limited']) && ! empty($bot_settings['user_tokens']) ) {
                    $temp += (float) $bot_settings['user_tokens'];
                }
                if ( ! empty($bot_settings['role_limited']) && ! empty($bot_settings['limited_roles']) ) {
                    $temp += $find_role_limit( $bot_settings['limited_roles'], $user_roles );
                }
                $total_free += $temp;
            }
            wp_reset_postdata();
        }

        return $total_free;
    }

    // For 'forms'
    if ( $module === 'forms' ) {
        $limit_settings = get_option('wpaicg_limit_tokens_form', []);
        $temp = 0;
        if ( ! empty($limit_settings['user_limited']) && ! empty($limit_settings['user_tokens']) ) {
            $temp = (float) $limit_settings['user_tokens'];
        }
        if ( ! empty($limit_settings['role_limited']) && ! empty($limit_settings['limited_roles']) ) {
            $temp += $find_role_limit( $limit_settings['limited_roles'], $user_roles );
        }
        return $temp;
    }

    // For 'promptbase'
    if ( $module === 'promptbase' ) {
        $limit_settings = get_option('wpaicg_limit_tokens_promptbase', []);
        $temp = 0;
        if ( ! empty($limit_settings['user_limited']) && ! empty($limit_settings['user_tokens']) ) {
            $temp = (float) $limit_settings['user_tokens'];
        }
        if ( ! empty($limit_settings['role_limited']) && ! empty($limit_settings['limited_roles']) ) {
            $temp += $find_role_limit( $limit_settings['limited_roles'], $user_roles );
        }
        return $temp;
    }

    // For 'image'
    if ( $module === 'image' ) {
        $limit_settings = get_option('wpaicg_limit_tokens_image', []);
        $temp = 0;
        if ( ! empty($limit_settings['user_limited']) && ! empty($limit_settings['user_tokens']) ) {
            $temp = (float) $limit_settings['user_tokens'];
        }
        if ( ! empty($limit_settings['role_limited']) && ! empty($limit_settings['limited_roles']) ) {
            $temp += $find_role_limit( $limit_settings['limited_roles'], $user_roles );
        }
        return $temp;
    }

    return 0;
}

/**
 * Helper function: Summation of usage logs for a given user/module from wpaicg_token_logs.
 * For 'image', tokens in logs represent cost in dollars; for others, number of tokens used.
 */
function wpaicg_calculate_usage_for_module( $user_id, $module ) {
    global $wpdb;
    $tbl = $wpdb->prefix . 'wpaicg_token_logs';
    $sum = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT SUM(tokens) FROM $tbl WHERE user_id = %d AND module = %s",
            $user_id,
            $module
        )
    );
    if ( ! $sum ) {
        $sum = 0;
    }
    return (float) $sum;
}

/**
 * Return a per-chatbot breakdown of allocated free tokens, usage, and remain
 * for the "chat" module. This displays how many free tokens come from the global
 * widget, global shortcode, and each custom chatbot. Purchased tokens are not
 * broken down by bot.
 */
function wpaicg_get_chat_bot_breakdown( $user_id ) {
    global $wpdb;
    $rows = [];
    $user_info  = get_userdata($user_id);
    $user_roles = $user_info ? (array) $user_info->roles : [];

    // Helper to sum role-based limits
    $sum_role_limit = function($limited_array, $roles) {
        $acc = 0;
        if(is_array($limited_array)) {
            foreach($roles as $r) {
                if(isset($limited_array[$r]) && $limited_array[$r] > 0) {
                    $acc += (float) $limited_array[$r];
                }
            }
        }
        return $acc;
    };

    // 1) Global widget
    $widget_options = get_option('wpaicg_chat_widget', []);
    $widget_alloc = 0;
    if(!empty($widget_options['user_limited']) && !empty($widget_options['user_tokens'])){
        $widget_alloc += (float)$widget_options['user_tokens'];
    }
    if(!empty($widget_options['role_limited']) && !empty($widget_options['limited_roles'])){
        $widget_alloc += $sum_role_limit($widget_options['limited_roles'], $user_roles);
    }
    if($widget_alloc > 0) {
        // usage from wpaicg_chattokens for source='widget'
        $usage = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(tokens) FROM {$wpdb->prefix}wpaicg_chattokens WHERE user_id=%d AND source=%s",
                $user_id,
                'widget'
            )
        );
        if(!$usage) { $usage = 0; }
        $remain = max(0, $widget_alloc - $usage);
        $rows[] = [
            'title'    => esc_html__('Global Chat Widget','gpt3-ai-content-generator'),
            'allocated'=> $widget_alloc,
            'used'     => $usage,
            'remain'   => $remain
        ];
    }

    // 2) Global shortcode
    $shortcode_options = get_option('wpaicg_chat_shortcode_options', []);
    $shortcode_alloc = 0;
    if(!empty($shortcode_options['user_limited']) && !empty($shortcode_options['user_tokens'])){
        $shortcode_alloc += (float)$shortcode_options['user_tokens'];
    }
    if(!empty($shortcode_options['role_limited']) && !empty($shortcode_options['limited_roles'])){
        $shortcode_alloc += $sum_role_limit($shortcode_options['limited_roles'], $user_roles);
    }
    if($shortcode_alloc > 0) {
        // usage from wpaicg_chattokens for source='shortcode'
        $usage = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(tokens) FROM {$wpdb->prefix}wpaicg_chattokens WHERE user_id=%d AND source=%s",
                $user_id,
                'shortcode'
            )
        );
        if(!$usage) { $usage = 0; }
        $remain = max(0, $shortcode_alloc - $usage);
        $rows[] = [
            'title'    => esc_html__('Global Chat Shortcode','gpt3-ai-content-generator'),
            'allocated'=> $shortcode_alloc,
            'used'     => $usage,
            'remain'   => $remain
        ];
    }

    // 3) Custom Chatbots
    $bots_query = new WP_Query([
        'post_type'      => 'wpaicg_chatbot',
        'posts_per_page' => -1
    ]);
    if($bots_query->have_posts()){
        while($bots_query->have_posts()){
            $bots_query->the_post();
            $bot_id = get_the_ID();
            $bot_title = get_the_title($bot_id);
            $raw_content = get_post_field('post_content', $bot_id);
            if(!$raw_content){ continue; }
            $bot_settings = json_decode($raw_content, true);
            if(!is_array($bot_settings)){ continue; }

            // determine if there's any free allocation
            $alloc = 0;
            if(!empty($bot_settings['user_limited']) && !empty($bot_settings['user_tokens'])){
                $alloc += (float)$bot_settings['user_tokens'];
            }
            if(!empty($bot_settings['role_limited']) && !empty($bot_settings['limited_roles'])){
                $alloc += $sum_role_limit($bot_settings['limited_roles'], $user_roles);
            }
            if($alloc > 0){
                // usage from wpaicg_chattokens for source='Widget ID: $bot_id' or 'Shortcode ID: $bot_id'
                $bot_type = isset($bot_settings['type']) && strtolower($bot_settings['type'])==='shortcode'
                    ? 'Shortcode'
                    : 'Widget';

                $source_str = $bot_type . ' ID: ' . $bot_id;
                $usage = $wpdb->get_var(
                    $wpdb->prepare(
                        "SELECT SUM(tokens) FROM {$wpdb->prefix}wpaicg_chattokens WHERE user_id=%d AND source=%s",
                        $user_id,
                        $source_str
                    )
                );
                if(!$usage) { $usage = 0; }
                $remain = max(0, $alloc - $usage);

                $rows[] = [
                    'title'    => $bot_type . ' #' . $bot_id . ': ' . $bot_title,
                    'allocated'=> $alloc,
                    'used'     => $usage,
                    'remain'   => $remain
                ];
            }
        }
        wp_reset_postdata();
    }

    return $rows;
}

// Check if the token logs table exists
$wpaicgTokenLogsTable = $wpdb->prefix . 'wpaicg_token_logs';
$tokenLogsTableExists = ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpaicgTokenLogsTable)) == $wpaicgTokenLogsTable);

// Retrieve usage logs if we have ?view_logs
$view_logs_user_id = isset($_GET['view_logs']) ? intval($_GET['view_logs']) : 0;
$view_user    = null;
$logs = array();
if ($view_logs_user_id > 0) {
    $view_user = get_user_by('ID', $view_logs_user_id);
    if ($view_user && $tokenLogsTableExists) {
        $log_query = $wpdb->prepare(
            "SELECT * FROM $wpaicgTokenLogsTable WHERE user_id = %d ORDER BY created_at DESC",
            $view_logs_user_id
        );
        $logs = $wpdb->get_results($log_query);
    }
}

/**
 * We will fetch all users, then implement client-side searching + advanced (1 2 3 ... 789 790 791) pagination
 * for the main user listing.
 */
$all_users = get_users();
?>
<div class="wrap">
    <h1><?php echo esc_html__('User Management', 'gpt3-ai-content-generator'); ?></h1>
    <p><?php echo esc_html__('You can modify purchased credits by clicking on the number.', 'gpt3-ai-content-generator'); ?></p>
    <p><?php echo esc_html__('Click on the Free token badge to see a breakdown by each chatbot.', 'gpt3-ai-content-generator'); ?></p>

    <?php if (!$tokenLogsTableExists): ?>
        <div class="notice notice-info is-dismissible">
            <p>
                <?php echo esc_html__('The token logs table does not exist. Please deactivate and then reactivate the plugin to trigger the table creation.', 'gpt3-ai-content-generator'); ?>
            </p>
        </div>
    <?php else: ?>

        <!-- Legend (minimal styling) -->
        <div style="margin: 1em 0; font-size: 14px; display: flex; align-items: center; flex-wrap: wrap; gap: 20px;">
            <div style="display: flex; align-items: center;">
                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 7px; background: #007aff; margin-right: 6px;"></span>
                <?php echo esc_html__('Free', 'gpt3-ai-content-generator'); ?>
            </div>
            <div style="display: flex; align-items: center;">
                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 7px; background: #34c759; margin-right: 6px;"></span>
                <?php echo esc_html__('Purchased', 'gpt3-ai-content-generator'); ?>
            </div>
            <div style="display: flex; align-items: center;">
                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 7px; background: #ff9500; margin-right: 6px;"></span>
                <?php echo esc_html__('Used', 'gpt3-ai-content-generator'); ?>
            </div>
            <div style="display: flex; align-items: center;">
                <span style="display: inline-block; width: 14px; height: 14px; border-radius: 7px; background: #8e8e93; margin-right: 6px;"></span>
                <?php echo esc_html__('Remaining', 'gpt3-ai-content-generator'); ?>
            </div>
        </div>

        <!-- Search box -->
        <div style="margin-bottom: 16px;">
            <input
                type="text"
                id="wpaicg-user-search"
                placeholder="<?php echo esc_attr__('Search users...', 'gpt3-ai-content-generator'); ?>"
                style="padding: 6px; width: 250px; font-size: 14px;"
            />
        </div>

        <style>
            /* Table styling */
            #wpaicg-user-table {
                width: 100%;
                border-collapse: collapse;
                margin-bottom: 16px;
            }
            #wpaicg-user-table th, #wpaicg-user-table td {
                border: 1px solid #d2d2d7;
                padding: 8px;
                font-size: 13px;
                vertical-align: middle;
            }
            #wpaicg-user-table th {
                background: #f9f9f9;
                cursor: pointer; /* We'll use this to indicate sortable columns */
                user-select: none;
            }
            #wpaicg-user-table th.sortable:after {
                content: " \25B2\25BC";
                font-size: 10px;
                color: #999;
                margin-left: 4px;
            }
            #wpaicg-user-table tbody tr:nth-child(even) {
                background-color: #fafafa;
            }
            .wpaicg-token-badges {
                display: flex;
                gap: 4px;
                flex-wrap: wrap;
                justify-content: center;
            }
            .wpaicg-token-badge {
                display: inline-block;
                padding: 3px 7px;
                border-radius: 9999px;
                font-size: 12px;
                line-height: 1.3;
                color: #fff;
                min-width: 36px;
                text-align: center;
                user-select: none;
            }
            .wpaicg-token-badge.free {
                background: #007aff;
            }
            .wpaicg-token-badge.purchased {
                background: #34c759;
            }
            .wpaicg-token-badge.used {
                background: #ff9500;
            }
            .wpaicg-token-badge.remain {
                background: #8e8e93;
            }
            .wpaicg-token-badge.wpaicg-editable {
                cursor: pointer;
                text-decoration: underline dotted;
            }

            .wpaicg-chat-breakdown-toggle {
                cursor: pointer;
            }
            .wpaicg-chat-breakdown-container {
                margin-top: 8px;
                border: 1px solid #d2d2d7;
                border-radius: 8px;
                padding: 8px;
                background: #f9f9f9;
                display: none; /* hidden by default, toggled via JS */
            }
            .wpaicg-chat-breakdown-table {
                width: 100%;
                border-collapse: collapse;
            }
            .wpaicg-chat-breakdown-table thead th {
                text-align: left;
                background: #f0f0f0;
                padding: 6px;
                border-bottom: 1px solid #ccc;
                font-weight: 500;
                font-size: 13px;
            }
            .wpaicg-chat-breakdown-table tbody td {
                padding: 6px;
                border-bottom: 1px solid #eee;
                font-size: 13px;
            }

            /* Inline editor */
            .wpaicg-inline-edit {
                width: 60px;
                border: 1px solid #ccc;
                border-radius: 4px;
                text-align: center;
                font-size: 12px;
                padding: 2px;
            }

            /* Toast/notification */
            .wpaicg-toast {
                position: fixed;
                top: 80px;
                right: 20px;
                background-color: #222;
                color: #fff;
                padding: 10px 14px;
                border-radius: 8px;
                font-size: 14px;
                z-index: 9999;
                opacity: 0;
                transition: opacity 0.3s ease;
            }
            .wpaicg-toast.show {
                opacity: 0.95;
            }
            .wpaicg-toast.success {
                background-color: #34c759;
            }
            .wpaicg-toast.error {
                background-color: #ff3b30;
            }

            /* Pagination controls */
            #wpaicg-user-pagination {
                margin-top: 16px;
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
                flex-direction: row;
            }
            .wpaicg-page-link {
                display: inline-block;
                padding: 6px 10px;
                background: #f0f0f0;
                border-radius: 4px;
                cursor: pointer;
                color: #333;
                font-size: 13px;
                text-decoration: none;
            }
            .wpaicg-page-link.active {
                background: #007aff;
                color: #fff;
            }

            /* For ellipsis */
            .wpaicg-page-link.ellipsis {
                cursor: default;
                color: #999;
            }

            /* Logs container design */
            .wpaicg-usage-logs-wrapper {
                margin-top: 2em;
                border: 1px solid #d2d2d7;
                border-radius: 6px;
                padding: 14px;
                background-color: #fff;
                box-shadow: 0 1px 2px rgba(0,0,0,0.08);
            }
            .wpaicg-usage-logs-title {
                font-size: 18px;
                margin: 0 0 0.5em;
            }
            .wpaicg-usage-logs-note {
                font-size: 13px;
                margin-bottom: 1em;
                color: #666;
            }
            .wpaicg-usage-logs-table {
                width: 100%;
                border-collapse: collapse;
            }
            .wpaicg-usage-logs-table th {
                text-align: left;
                background: #f4f4f4;
                padding: 8px;
                border-bottom: 1px solid #ccc;
                font-size: 13px;
            }
            .wpaicg-usage-logs-table td {
                padding: 8px;
                border-bottom: 1px solid #eee;
                font-size: 13px;
            }
            .wpaicg-usage-logs-table .wpaicg-module-group {
                background-color: #f9f9f9;
                color: #000;
                font-weight: 600;
            }
            .wpaicg-usage-logs-pagination {
                margin-top: 14px;
                display: flex;
                gap: 6px;
                flex-wrap: wrap;
            }
            .wpaicg-usage-logs-page-link {
                display: inline-block;
                padding: 4px 8px;
                background: #f0f0f0;
                border-radius: 4px;
                cursor: pointer;
                color: #333;
                font-size: 12px;
                text-decoration: none;
            }
            .wpaicg-usage-logs-page-link.active {
                background: #007aff;
                color: #fff;
            }
            .wpaicg-usage-logs-page-link.ellipsis {
                cursor: default;
                color: #999;
            }
        </style>

        <!-- Main user table -->
        <table id="wpaicg-user-table">
            <thead>
            <tr>
                <th class="sortable" data-sort="username"><?php echo esc_html__('Username','gpt3-ai-content-generator'); ?></th>
                <th class="sortable" data-sort="email"><?php echo esc_html__('Email','gpt3-ai-content-generator'); ?></th>
                <th><?php echo esc_html__('Role(s)','gpt3-ai-content-generator'); ?></th>
                <th><?php echo esc_html__('Chat','gpt3-ai-content-generator'); ?></th>
                <th><?php echo esc_html__('AI Forms','gpt3-ai-content-generator'); ?></th>
                <th><?php echo esc_html__('Image','gpt3-ai-content-generator'); ?></th>
                <th><?php echo esc_html__('Actions','gpt3-ai-content-generator'); ?></th>
            </tr>
            </thead>
            <tbody id="wpaicg-user-tbody">
            <?php foreach ($all_users as $user) :
                // Calculate free, purchased, usage, remain for each module

                // 1) Chat
                $chat_free   = wpaicg_calculate_free_tokens_for_user($user->ID, 'chat');
                $chat_purch  = (float) get_user_meta($user->ID, 'wpaicg_chat_tokens', true);
                $chat_used   = wpaicg_calculate_usage_for_module($user->ID, 'chat');
                $chat_remain = max( 0, $chat_free + $chat_purch - $chat_used );

                // 2) Forms
                $forms_free   = wpaicg_calculate_free_tokens_for_user($user->ID, 'forms');
                $forms_purch  = (float) get_user_meta($user->ID, 'wpaicg_forms_tokens', true);
                $forms_used   = wpaicg_calculate_usage_for_module($user->ID, 'forms');
                $forms_remain = max( 0, $forms_free + $forms_purch - $forms_used );

                // 3) Promptbase
                $pbase_free   = wpaicg_calculate_free_tokens_for_user($user->ID, 'promptbase');
                $pbase_purch  = (float) get_user_meta($user->ID, 'wpaicg_promptbase_tokens', true);
                $pbase_used   = wpaicg_calculate_usage_for_module($user->ID, 'promptbase');
                $pbase_remain = max( 0, $pbase_free + $pbase_purch - $pbase_used );

                // 4) Image
                $image_free   = wpaicg_calculate_free_tokens_for_user($user->ID, 'image');
                $image_purch  = (float) get_user_meta($user->ID, 'wpaicg_image_tokens', true);
                $image_used   = wpaicg_calculate_usage_for_module($user->ID, 'image');
                $image_remain = max( 0, $image_free + $image_purch - $image_used );

                // Determine if the user has any usage at all
                $has_any_usage = (
                    $chat_used > 0 ||
                    $forms_used > 0 ||
                    $pbase_used > 0 ||
                    $image_used > 0
                );

                // Chat breakdown only if chat_free > 0
                $chatBreakdown = [];
                if($chat_free > 0) {
                    $chatBreakdown = wpaicg_get_chat_bot_breakdown($user->ID);
                }

                // Collect roles
                $user_roles_raw = $user->roles;
                $display_role_names = [];
                if(!empty($user_roles_raw)){
                    foreach($user_roles_raw as $r){
                        if(isset($wp_roles->role_names[$r])){
                            $display_role_names[] = translate_user_role($wp_roles->role_names[$r]);
                        }
                    }
                }
                $display_role_text = implode(', ', $display_role_names);
                ?>
                <tr
                    data-username="<?php echo esc_attr(strtolower($user->user_login . ' ' . $user->display_name)); ?>"
                    data-email="<?php echo esc_attr(strtolower($user->user_email)); ?>"
                    data-userid="<?php echo $user->ID; ?>"
                >
                    <!-- Username -->
                    <td><?php echo esc_html($user->display_name); ?></td>

                    <!-- Email -->
                    <td><?php echo esc_html($user->user_email); ?></td>

                    <!-- Role(s) -->
                    <td><?php echo esc_html($display_role_text); ?></td>

                    <!-- Chat module -->
                    <td>
                        <div class="wpaicg-token-badges">
                            <?php if(!empty($chatBreakdown)): ?>
                                <!-- Make the free token amount clickable to open Chatbot Breakdown -->
                                <span 
                                    class="wpaicg-token-badge free wpaicg-chat-breakdown-toggle"
                                    data-target="chat-breakdown-<?php echo $user->ID; ?>"
                                >
                                    <?php echo number_format($chat_free, 0); ?>
                                </span>
                            <?php else: ?>
                                <span class="wpaicg-token-badge free">
                                    <?php echo number_format($chat_free, 0); ?>
                                </span>
                            <?php endif; ?>

                            <span
                                class="wpaicg-token-badge purchased wpaicg-editable"
                                data-userid="<?php echo $user->ID; ?>"
                                data-module="chat"
                                data-remaining-id="remain-chat-<?php echo $user->ID; ?>"
                            >
                                <?php echo number_format($chat_purch, 0); ?>
                            </span>
                            <span class="wpaicg-token-badge used">
                                <?php echo number_format($chat_used, 0); ?>
                            </span>
                            <span
                                id="remain-chat-<?php echo $user->ID; ?>"
                                class="wpaicg-token-badge remain"
                            >
                                <?php echo number_format($chat_remain, 0); ?>
                            </span>
                        </div>

                        <?php if(!empty($chatBreakdown)): ?>
                            <div
                                class="wpaicg-chat-breakdown-container"
                                id="chat-breakdown-<?php echo $user->ID; ?>"
                            >
                                <table class="wpaicg-chat-breakdown-table">
                                    <thead>
                                        <tr>
                                            <th><?php echo esc_html__('Chatbot', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php echo esc_html__('Allocated', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php echo esc_html__('Used', 'gpt3-ai-content-generator'); ?></th>
                                            <th><?php echo esc_html__('Remaining', 'gpt3-ai-content-generator'); ?></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach($chatBreakdown as $cb): ?>
                                        <tr>
                                            <td><?php echo esc_html($cb['title']); ?></td>
                                            <td><?php echo number_format($cb['allocated'], 0); ?></td>
                                            <td><?php echo number_format($cb['used'], 0); ?></td>
                                            <td><?php echo number_format($cb['remain'], 0); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </td>

                    <!-- Forms module -->
                    <td>
                        <div class="wpaicg-token-badges">
                            <span class="wpaicg-token-badge free">
                                <?php echo number_format($forms_free, 0); ?>
                            </span>
                            <span
                                class="wpaicg-token-badge purchased wpaicg-editable"
                                data-userid="<?php echo $user->ID; ?>"
                                data-module="forms"
                                data-remaining-id="remain-forms-<?php echo $user->ID; ?>"
                            >
                                <?php echo number_format($forms_purch, 0); ?>
                            </span>
                            <span class="wpaicg-token-badge used">
                                <?php echo number_format($forms_used, 0); ?>
                            </span>
                            <span
                                id="remain-forms-<?php echo $user->ID; ?>"
                                class="wpaicg-token-badge remain"
                            >
                                <?php echo number_format($forms_remain, 0); ?>
                            </span>
                        </div>
                    </td>

                    <!-- Image module -->
                    <td>
                        <div class="wpaicg-token-badges">
                            <span class="wpaicg-token-badge free">
                                <?php echo number_format($image_free, 2); ?>
                            </span>
                            <span
                                class="wpaicg-token-badge purchased wpaicg-editable"
                                data-userid="<?php echo $user->ID; ?>"
                                data-module="image"
                                data-remaining-id="remain-image-<?php echo $user->ID; ?>"
                            >
                                <?php echo number_format($image_purch, 2); ?>
                            </span>
                            <span class="wpaicg-token-badge used">
                                <?php echo number_format($image_used, 2); ?>
                            </span>
                            <span
                                id="remain-image-<?php echo $user->ID; ?>"
                                class="wpaicg-token-badge remain"
                            >
                                <?php echo number_format($image_remain, 2); ?>
                            </span>
                        </div>
                    </td>

                    <!-- Actions -->
                    <td>
                        <?php if ($has_any_usage): ?>
                            <a
                                class="button"
                                href="<?php echo esc_url(add_query_arg(['view_logs' => $user->ID])); ?>"
                                style="font-size:12px;"
                            >
                                <?php echo esc_html__('View Usage', 'gpt3-ai-content-generator'); ?>
                            </a>
                        <?php else: ?>
                            <span style="font-size:12px;">
                                <?php echo esc_html__('No usage', 'gpt3-ai-content-generator'); ?>
                            </span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Pagination container -->
        <div id="wpaicg-user-pagination"></div>

        <?php
        // Usage logs for a specific user
        if ($view_user && $logs):
            // We'll group logs by module, then show them in new UI with client-side pagination
            $groupedLogs = [];
            foreach ($logs as $lg) {
                $moduleName = $lg->module;
                if (!isset($groupedLogs[$moduleName])) {
                    $groupedLogs[$moduleName] = [];
                }
                $groupedLogs[$moduleName][] = $lg;
            }
            // Flatten into a single array with module, tokens, created_at
            $jsonLogs = [];
            foreach ($groupedLogs as $mod => $arr) {
                // Sort each group by created_at descending (already sorted in query, but let's ensure)
                foreach ($arr as $row) {
                    // If image, treat "tokens" as cost with $
                    $displayTokens = ($row->module === 'image')
                        ? ('$' . $row->tokens)
                        : $row->tokens;
                    $jsonLogs[] = [
                        'module'    => $mod,
                        'tokens'    => $displayTokens,
                        'numeric_tokens' => floatval($row->tokens),
                        'created_at'=> $row->created_at,
                    ];
                }
            }
            // Sort by created_at descending
            usort($jsonLogs, function($a, $b){
                return $b['created_at'] <=> $a['created_at'];
            });
            ?>
            <div class="wpaicg-usage-logs-wrapper">
                <h2 class="wpaicg-usage-logs-title">
                    <?php
                        echo esc_html__(
                            'Usage Logs for',
                            'gpt3-ai-content-generator'
                        ) . ' ' . esc_html($view_user->display_name);
                    ?>
                </h2>
                <p class="wpaicg-usage-logs-note">
                    <?php echo esc_html__('Below are the token usage records grouped by module. Paginate to view more.', 'gpt3-ai-content-generator'); ?>
                </p>

                <div
                    id="wpaicg-usage-logs-container"
                    data-logs="<?php echo esc_attr(json_encode($jsonLogs)); ?>"
                ></div>

                <div class="wpaicg-usage-logs-pagination" id="wpaicg-usage-logs-pagination"></div>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Toast notification element -->
<div id="wpaicg-toast" class="wpaicg-toast"></div>

<script>
document.addEventListener('DOMContentLoaded', function(){
    const toastEl = document.getElementById('wpaicg-toast');

    function showToast(message, type='success') {
        toastEl.textContent = message;
        toastEl.className = 'wpaicg-toast show ' + type;
        setTimeout(() => {
            toastEl.className = 'wpaicg-toast';
            toastEl.textContent = '';
        }, 2500);
    }

    // Sorting
    const table = document.getElementById('wpaicg-user-table');
    const tbody = document.getElementById('wpaicg-user-tbody');
    const sortHeaders = table.querySelectorAll('th.sortable');

    // We keep track of current sort direction
    let currentSortKey = '';
    let sortDirection = 1; // 1 = ascending, -1 = descending

    sortHeaders.forEach(header => {
        header.addEventListener('click', () => {
            const sortKey = header.getAttribute('data-sort');
            if (currentSortKey === sortKey) {
                sortDirection *= -1; // flip direction
            } else {
                currentSortKey = sortKey;
                sortDirection = 1; // reset to ascending
            }
            sortTable(sortKey, sortDirection);
        });
    });

    function sortTable(key, direction) {
        let rows = Array.from(tbody.querySelectorAll('tr'));
        rows.sort((a, b) => {
            const aVal = a.getAttribute('data-' + key) || '';
            const bVal = b.getAttribute('data-' + key) || '';
            if (aVal < bVal) return -1 * direction;
            if (aVal > bVal) return 1 * direction;
            return 0;
        });
        rows.forEach(r => tbody.appendChild(r));
    }

    // Toggle for Chatbot Breakdown when user clicks on the free token badge
    const breakdownToggles = document.querySelectorAll('.wpaicg-chat-breakdown-toggle');
    breakdownToggles.forEach(function(toggle){
        toggle.addEventListener('click', function(ev){
            ev.stopPropagation();
            const targetId = toggle.getAttribute('data-target');
            const container = document.getElementById(targetId);
            if(container){
                if(container.style.display === 'none' || container.style.display === ''){
                    container.style.display = 'block';
                } else {
                    container.style.display = 'none';
                }
            }
        });
    });

    // Inline editing for purchased tokens
    const purchasedBadges = document.querySelectorAll('.wpaicg-editable');
    purchasedBadges.forEach(function(badge){
        badge.addEventListener('click', function(ev){
            ev.stopPropagation();

            const currentValue = badge.textContent.trim().replace(/,/g,'');
            const userId = badge.getAttribute('data-userid');
            const mod = badge.getAttribute('data-module');
            const remainId = badge.getAttribute('data-remaining-id');

            const input = document.createElement('input');
            input.type = 'text';
            input.value = currentValue;
            input.className = 'wpaicg-inline-edit';

            badge.textContent = '';
            badge.appendChild(input);
            input.focus();

            const finishEdit = () => {
                const newValueRaw = input.value.trim();
                if(newValueRaw === ''){
                    badge.textContent = currentValue;
                    return;
                }
                const newVal = parseFloat(newValueRaw);
                if(isNaN(newVal)){
                    badge.textContent = currentValue;
                    showToast('Invalid number', 'error');
                    return;
                }

                // AJAX
                const formData = new FormData();
                formData.append('action','wpaicg_update_purchased_tokens');
                formData.append('nonce','<?php echo wp_create_nonce("wpaicg_update_purchased_tokens"); ?>');
                formData.append('user_id', userId);
                formData.append('module', mod);
                formData.append('purchased_tokens', newVal);

                fetch('<?php echo esc_url(admin_url('admin-ajax.php')); ?>', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(json => {
                    if(json.success){
                        if(mod === 'image'){
                            badge.textContent = newVal.toFixed(2);
                        } else {
                            badge.textContent = newVal.toLocaleString();
                        }
                        if(remainId){
                            const remainEl = document.getElementById(remainId);
                            if(remainEl && json.data.remain !== undefined){
                                if(mod === 'image'){
                                    remainEl.textContent = parseFloat(json.data.remain).toFixed(2);
                                } else {
                                    remainEl.textContent = parseInt(json.data.remain).toLocaleString();
                                }
                            }
                        }
                        showToast(json.data.message || 'Updated!', 'success');
                    } else {
                        badge.textContent = currentValue;
                        showToast(json.data.message || 'Update failed!', 'error');
                    }
                })
                .catch(err => {
                    badge.textContent = currentValue;
                    showToast('Error: ' + err.message, 'error');
                });
            };

            input.addEventListener('blur', finishEdit);
            input.addEventListener('keydown', function(ev){
                if(ev.key === 'Enter'){
                    ev.preventDefault();
                    finishEdit();
                } else if(ev.key === 'Escape'){
                    badge.textContent = currentValue;
                }
            });
        });
    });

    // Advanced pagination logic
    function generatePaginationRange(currentPage, totalPages, maxLength = 7) {
        // This function returns an array of page numbers and/or "..." for rendering
        // We want something like 1 2 3 ... 9 10 11 if large
        // If totalPages <= maxLength, just return [1..totalPages].
        const range = [];

        if (totalPages <= maxLength) {
            for (let i = 1; i <= totalPages; i++) {
                range.push(i);
            }
            return range;
        }

        // Ensure currentPage is within bounds
        if (currentPage > totalPages) currentPage = totalPages;
        if (currentPage < 1) currentPage = 1;

        const halfway = Math.floor(maxLength / 2);
        const leftCount = maxLength - 3; // We'll keep 2 pages at the edges at minimum
        let left = currentPage - Math.floor(leftCount / 2);
        let right = currentPage + Math.floor(leftCount / 2);

        if (left < 2) {
            left = 2;
            right = left + leftCount - 1;
        }
        if (right > totalPages - 1) {
            right = totalPages - 1;
            left = right - leftCount + 1;
        }

        range.push(1);
        if (left > 2) {
            range.push('...');
        }

        for (let i = left; i <= right; i++) {
            range.push(i);
        }

        if (right < totalPages - 1) {
            range.push('...');
        }
        range.push(totalPages);

        return range;
    }

    // Client-side search + advanced pagination for the main user table
    const userSearch = document.getElementById('wpaicg-user-search');
    const paginationEl = document.getElementById('wpaicg-user-pagination');
    let currentPage = 1;
    const pageSize = 3; // show 10 users per page by default

    function filterAndPaginate() {
        const searchVal = userSearch.value.toLowerCase();

        // Only select rows that actually represent users
        const allRows = Array.from(tbody.querySelectorAll('tr')).filter(
            row => row.hasAttribute('data-userid')
        );

        // Filter
        const filtered = allRows.filter(row => {
            const rowUsername = row.getAttribute('data-username') || '';
            const rowEmail = row.getAttribute('data-email') || '';
            const combined = (rowUsername + ' ' + rowEmail).toLowerCase();
            return combined.includes(searchVal);
        });

        // Hide all
        allRows.forEach(r => r.style.display = 'none');

        // total pages
        const totalPages = Math.ceil(filtered.length / pageSize);

        // Adjust currentPage if it exceeds totalPages
        if (currentPage > totalPages && totalPages > 0) {
            currentPage = totalPages;
        } else if (totalPages === 0) {
            currentPage = 1;
        }

        // Show slice for current page
        const startIndex = (currentPage - 1) * pageSize;
        const endIndex = startIndex + pageSize;
        filtered.slice(startIndex, endIndex).forEach(r => r.style.display = '');

        // Build advanced pagination
        paginationEl.innerHTML = '';
        if (totalPages > 1) {
            const pagesToShow = generatePaginationRange(currentPage, totalPages, 7);
            pagesToShow.forEach(page => {
                const link = document.createElement('a');
                link.href = 'javascript:void(0)';
                if (page === '...') {
                    link.className = 'wpaicg-page-link ellipsis';
                    link.textContent = '...';
                } else {
                    link.className = 'wpaicg-page-link' + (page === currentPage ? ' active' : '');
                    link.textContent = page;
                    link.addEventListener('click', () => {
                        currentPage = page;
                        filterAndPaginate();
                    });
                }
                paginationEl.appendChild(link);
            });
        }
    }

    userSearch.addEventListener('input', () => {
        currentPage = 1;
        filterAndPaginate();
    });

    // Initial
    filterAndPaginate();

    // If usage logs exist on this page, we do a client-side grouping or advanced pagination
    const logsContainer = document.getElementById('wpaicg-usage-logs-container');
    if(logsContainer) {
        const logsDataAttr = logsContainer.getAttribute('data-logs');
        let logsData = [];
        try {
            logsData = JSON.parse(logsDataAttr);
        } catch(e){}

        let usageCurrentPage = 1;
        const usagePageSize = 10;

        // Same advanced pagination
        function generateUsagePages(cur, total, maxLen = 7) {
            // reuse the same function for usage logs
            const range = [];
            if (total <= maxLen) {
                for (let i = 1; i <= total; i++) {
                    range.push(i);
                }
                return range;
            }
            if (cur > total) cur = total;
            if (cur < 1) cur = 1;
            const leftCount = maxLen - 3;
            let left = cur - Math.floor(leftCount / 2);
            let right = cur + Math.floor(leftCount / 2);

            if (left < 2) {
                left = 2;
                right = left + leftCount - 1;
            }
            if (right > total - 1) {
                right = total - 1;
                left = right - leftCount + 1;
            }

            range.push(1);
            if (left > 2) {
                range.push('...');
            }
            for (let i = left; i <= right; i++) {
                range.push(i);
            }
            if (right < total - 1) {
                range.push('...');
            }
            range.push(total);
            return range;
        }

        function renderUsageLogs() {
            if(!Array.isArray(logsData)){ return; }
            const start = (usageCurrentPage - 1) * usagePageSize;
            const end = start + usagePageSize;
            const pageLogs = logsData.slice(start, end);

            // We'll group them by module in the order they appear
            let html = '<table class="wpaicg-usage-logs-table">';
            html += '<thead><tr><th><?php echo esc_html__("Module", "gpt3-ai-content-generator"); ?></th><th><?php echo esc_html__("Tokens/Price", "gpt3-ai-content-generator"); ?></th><th><?php echo esc_html__("Created At", "gpt3-ai-content-generator"); ?></th></tr></thead>';
            html += '<tbody>';

            let lastModule = null;
            pageLogs.forEach(log => {
                const mod = log.module;
                if(mod !== lastModule) {
                    html += `<tr class="wpaicg-module-group"><td colspan="3">${mod}</td></tr>`;
                    lastModule = mod;
                }
                const dateStr = new Date(log.created_at * 1000).toLocaleString();
                html += `
                  <tr>
                    <td>${mod}</td>
                    <td>${log.tokens}</td>
                    <td>${dateStr}</td>
                  </tr>
                `;
            });
            html += '</tbody></table>';

            logsContainer.innerHTML = html;
        }

        function renderUsagePagination() {
            const logsPaginationEl = document.getElementById('wpaicg-usage-logs-pagination');
            if(!logsPaginationEl){ return; }
            logsPaginationEl.innerHTML = '';

            const totalPages = Math.ceil(logsData.length / usagePageSize);
            if(totalPages <= 1) { return; }

            const pagesToShow = generateUsagePages(usageCurrentPage, totalPages, 7);
            pagesToShow.forEach(pg => {
                const link = document.createElement('a');
                link.href = 'javascript:void(0)';
                if(pg === '...') {
                    link.className = 'wpaicg-usage-logs-page-link ellipsis';
                    link.textContent = '...';
                } else {
                    link.className = 'wpaicg-usage-logs-page-link' + (pg === usageCurrentPage ? ' active' : '');
                    link.textContent = pg;
                    link.addEventListener('click', () => {
                        usageCurrentPage = pg;
                        renderUsageLogs();
                        renderUsagePagination();
                    });
                }
                logsPaginationEl.appendChild(link);
            });
        }

        if(logsData && logsData.length){
            renderUsageLogs();
            renderUsagePagination();
        }
    }

});
</script>