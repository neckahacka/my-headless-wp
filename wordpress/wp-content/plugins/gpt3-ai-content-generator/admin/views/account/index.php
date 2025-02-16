<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wp, $wpdb;

/**
 * We introduce a helper function for ChatBot breakdowns (only for free tokens).
 * This logic is similar to what's done in user management, but we adapt it
 * so normal users can see how their free tokens are distributed across each bot.
 */
if (!function_exists('wpaicg_get_chat_bot_breakdown_for_user')) {
    function wpaicg_get_chat_bot_breakdown_for_user($user_id) {
        global $wpdb;
        $breakdown = [];

        // Retrieve current user roles
        $user_data = get_userdata($user_id);
        if (!$user_data) {
            return $breakdown;
        }
        $user_roles = (array) $user_data->roles;

        // Helper to sum role-based limits
        $sum_role_limits = function($limited_array, $roles) {
            $acc = 0;
            if (is_array($limited_array)) {
                foreach ($roles as $r) {
                    if (isset($limited_array[$r]) && $limited_array[$r] > 0) {
                        $acc += (float) $limited_array[$r];
                    }
                }
            }
            return $acc;
        };

        // 1) The global Chat Widget
        $widget_options = get_option('wpaicg_chat_widget', []);
        $widget_alloc   = 0;
        if (!empty($widget_options['user_limited']) && !empty($widget_options['user_tokens'])) {
            $widget_alloc += (float) $widget_options['user_tokens'];
        }
        if (!empty($widget_options['role_limited']) && !empty($widget_options['limited_roles'])) {
            $widget_alloc += $sum_role_limits($widget_options['limited_roles'], $user_roles);
        }
        if ($widget_alloc > 0) {
            $usage = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(tokens) FROM {$wpdb->prefix}wpaicg_chattokens
                     WHERE user_id=%d AND source='widget'",
                    $user_id
                )
            );
            if (!$usage) {
                $usage = 0;
            }
            $remain   = max(0, $widget_alloc - $usage);
            $breakdown[] = [
                'type'      => esc_html__('Global Chat Widget', 'gpt3-ai-content-generator'),
                'allocated' => $widget_alloc,
                'used'      => $usage,
                'remain'    => $remain,
            ];
        }

        // 2) The global Chat Shortcode
        $shortcode_options = get_option('wpaicg_chat_shortcode_options', []);
        $shortcode_alloc   = 0;
        if (!empty($shortcode_options['user_limited']) && !empty($shortcode_options['user_tokens'])) {
            $shortcode_alloc += (float) $shortcode_options['user_tokens'];
        }
        if (!empty($shortcode_options['role_limited']) && !empty($shortcode_options['limited_roles'])) {
            $shortcode_alloc += $sum_role_limits($shortcode_options['limited_roles'], $user_roles);
        }
        if ($shortcode_alloc > 0) {
            $usage = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(tokens) FROM {$wpdb->prefix}wpaicg_chattokens
                     WHERE user_id=%d AND source='shortcode'",
                    $user_id
                )
            );
            if (!$usage) {
                $usage = 0;
            }
            $remain   = max(0, $shortcode_alloc - $usage);
            $breakdown[] = [
                'type'      => esc_html__('Global Chat Shortcode', 'gpt3-ai-content-generator'),
                'allocated' => $shortcode_alloc,
                'used'      => $usage,
                'remain'    => $remain,
            ];
        }

        // 3) Each custom chatbot
        $bots_query = new \WP_Query([
            'post_type'      => 'wpaicg_chatbot',
            'posts_per_page' => -1,
        ]);
        if ($bots_query->have_posts()) {
            while ($bots_query->have_posts()) {
                $bots_query->the_post();
                $bot_id     = get_the_ID();
                $bot_title  = get_the_title($bot_id) ?: ('ChatBot #'.$bot_id);
                $raw        = get_post_field('post_content', $bot_id);
                if (!$raw) {
                    continue;
                }
                $bot_settings = json_decode($raw, true);
                if (!is_array($bot_settings)) {
                    continue;
                }
                $bot_alloc = 0;
                if (!empty($bot_settings['user_limited']) && !empty($bot_settings['user_tokens'])) {
                    $bot_alloc += (float) $bot_settings['user_tokens'];
                }
                if (!empty($bot_settings['role_limited']) && !empty($bot_settings['limited_roles'])) {
                    $bot_alloc += $sum_role_limits($bot_settings['limited_roles'], $user_roles);
                }
                if ($bot_alloc > 0) {
                    $botType    = (!empty($bot_settings['type']) && strtolower($bot_settings['type']) === 'shortcode')
                                  ? 'Shortcode'
                                  : 'Widget';
                    $sourceKey  = $botType . ' ID: ' . $bot_id;
                    $usage = $wpdb->get_var(
                        $wpdb->prepare(
                            "SELECT SUM(tokens) FROM {$wpdb->prefix}wpaicg_chattokens
                             WHERE user_id=%d AND source=%s",
                            $user_id,
                            $sourceKey
                        )
                    );
                    if (!$usage) {
                        $usage = 0;
                    }
                    $remain = max(0, $bot_alloc - $usage);
                    $breakdown[] = [
                        'type'      => sprintf('%s (%s #%d)', $bot_title, $botType, $bot_id),
                        'allocated' => $bot_alloc,
                        'used'      => $usage,
                        'remain'    => $remain,
                    ];
                }
            }
            wp_reset_postdata();
        }
        return $breakdown;
    }
}

// Retrieve usage data for current user
$current_user_id = get_current_user_id();
if (!$current_user_id) {
    // Not logged in
    echo '<div class="notice notice-error"><p>'.esc_html__('You must be logged in to view this page.','gpt3-ai-content-generator').'</p></div>';
    return;
}

// 1) AI FORMS
$wpaicg_playground = \WPAICG\WPAICG_Playground::get_instance();
$wpaicg_form_tokens = $wpaicg_playground->wpaicg_token_handling('form');
$forms_limited      = $wpaicg_form_tokens['limited'];
$forms_left         = $wpaicg_form_tokens['left_tokens'] ?? 0;
if ($forms_limited) {
    // out of quota or 0 left
    $forms_left = ($forms_left <= 0) ? esc_html__('Out of Quota','gpt3-ai-content-generator') : $forms_left;
}

// 2) PROMPTBASE
$wpaicg_promptbase_tokens = $wpaicg_playground->wpaicg_token_handling('promptbase');
$promptbase_limited       = $wpaicg_promptbase_tokens['limited'];
$promptbase_left          = $wpaicg_promptbase_tokens['left_tokens'] ?? 0;
if ($promptbase_limited) {
    $promptbase_left = ($promptbase_left <= 0) ? esc_html__('Out of Quota','gpt3-ai-content-generator') : $promptbase_left;
}

// 3) IMAGE
$wpaicg_image_tokens = $wpaicg_playground->wpaicg_token_handling('image');
$image_limited       = $wpaicg_image_tokens['limited'];
$image_left          = $wpaicg_image_tokens['left_tokens'] ?? 0;
if ($image_limited) {
    $image_left = ($image_left <= 0) ? esc_html__('Out of Quota','gpt3-ai-content-generator') : $image_left;
}

// 4) CHAT
// The code below sums all chat widget, shortcodes, custom bots for free tokens, etc.
$wpaicg_chat_has_limit    = false;
$wpaicg_chat_total_token  = 0;
$wpaicg_user_roles        = wp_get_current_user()->roles;
$wpaicg_bots = new WP_Query([
    'post_type'      => 'wpaicg_chatbot',
    'posts_per_page' => -1
]);

// Summation logic for custom bots
if ($wpaicg_bots->have_posts()) {
    while ($wpaicg_bots->have_posts()) {
        $wpaicg_bots->the_post();
        $bot_id = get_the_ID();
        $raw_content = get_post_field('post_content', $bot_id);
        if (!$raw_content) {
            continue;
        }
        $bot_settings = json_decode($raw_content, true);
        if (!is_array($bot_settings)) {
            continue;
        }
        if (!empty($bot_settings['user_limited']) && !empty($bot_settings['user_tokens'])) {
            $wpaicg_chat_has_limit = true;
            $wpaicg_chat_total_token += (float) $bot_settings['user_tokens'];
        } elseif (!empty($bot_settings['role_limited']) && !empty($bot_settings['limited_roles'])) {
            foreach ($wpaicg_user_roles as $r) {
                if (isset($bot_settings['limited_roles'][$r]) && $bot_settings['limited_roles'][$r] > 0) {
                    $wpaicg_chat_has_limit = true;
                    $wpaicg_chat_total_token += (float) $bot_settings['limited_roles'][$r];
                }
            }
        }
    }
    wp_reset_postdata();
}

// Sum from global chat widget
$chat_widget_option = get_option('wpaicg_chat_widget', []);
if (!empty($chat_widget_option['user_limited']) && !empty($chat_widget_option['user_tokens'])) {
    $wpaicg_chat_has_limit = true;
    $wpaicg_chat_total_token += (float)$chat_widget_option['user_tokens'];
} elseif (!empty($chat_widget_option['role_limited']) && !empty($chat_widget_option['limited_roles'])) {
    foreach ($wpaicg_user_roles as $r) {
        if (isset($chat_widget_option['limited_roles'][$r]) && $chat_widget_option['limited_roles'][$r] > 0) {
            $wpaicg_chat_has_limit = true;
            $wpaicg_chat_total_token += (float)$chat_widget_option['limited_roles'][$r];
        }
    }
}

// Sum from global chat shortcode
$chat_shortcode_option = get_option('wpaicg_chat_shortcode_options', []);
if (!empty($chat_shortcode_option['user_limited']) && !empty($chat_shortcode_option['user_tokens'])) {
    $wpaicg_chat_has_limit = true;
    $wpaicg_chat_total_token += (float)$chat_shortcode_option['user_tokens'];
} elseif (!empty($chat_shortcode_option['role_limited']) && !empty($chat_shortcode_option['limited_roles'])) {
    foreach ($wpaicg_user_roles as $r) {
        if (isset($chat_shortcode_option['limited_roles'][$r]) && $chat_shortcode_option['limited_roles'][$r] > 0) {
            $wpaicg_chat_has_limit = true;
            $wpaicg_chat_total_token += (float)$chat_shortcode_option['limited_roles'][$r];
        }
    }
}

// Also add purchased tokens
$purchased_chat_tokens = (float) get_user_meta($current_user_id, 'wpaicg_chat_tokens', true);
$wpaicg_chat_total_token += $purchased_chat_tokens;

// Now see how many used
$wpaicg_chat_token_log = $wpdb->get_row(
    $wpdb->prepare(
        "SELECT * FROM {$wpdb->prefix}wpaicg_chattokens WHERE user_id=%d",
        $current_user_id
    )
);
$wpaicg_token_usage_client = $wpaicg_chat_token_log ? (float)$wpaicg_chat_token_log->tokens : 0;

$chat_limited = false;
$chat_left    = $wpaicg_chat_total_token - $wpaicg_token_usage_client;
if ($chat_left <= 0 && $wpaicg_chat_has_limit) {
    $chat_limited = true;
    $chat_left    = esc_html__('Out of Quota','gpt3-ai-content-generator');
}

// If there's a free allocation for chat, let's also show the breakdown
$chatBreakdownRows = [];
if ($wpaicg_chat_has_limit) {
    // We'll gather the user-specific breakdown from our function
    $breakdown = wpaicg_get_chat_bot_breakdown_for_user($current_user_id);
    // Then we add purchased tokens as a separate row if > 0
    // The logic is simpler: purchased tokens is not split across bots
    // The user might want to see that combined. So let's do it at the top or bottom:
    $purchased_usage = 0;  // usage from purchased tokens can be considered last in line
    // It's tricky to exactly separate usage from free vs purchased tokens,
    // but we'll show them as two separate lines for clarity: "Purchased tokens"
    if ($purchased_chat_tokens > 0) {
        $purchased_usage = 0; // We do not track usage specifically from purchased tokens
        $purchased_remain = $purchased_chat_tokens;  // purely an approximate
        // We'll just do a minimal row:
        $breakdown[] = [
            'type'      => esc_html__('Purchased Tokens','gpt3-ai-content-generator'),
            'allocated' => $purchased_chat_tokens,
            'used'      => $purchased_usage,
            'remain'    => $purchased_remain,
        ];
    }
    // We'll build $chatBreakdownRows from $breakdown
    $chatBreakdownRows = $breakdown;
}

// Next: let's retrieve the usage logs for each module from wpaicg_token_logs
// Because we have the user usage table at the bottom
$logs_page          = isset($_GET['wpage']) ? (int)$_GET['wpage'] : 1;
$items_per_page     = 10;
$offset             = ($logs_page - 1) * $items_per_page;
$logs_query         = $wpdb->prepare(
    "SELECT * FROM {$wpdb->prefix}wpaicg_token_logs WHERE user_id=%d ORDER BY created_at DESC",
    $current_user_id
);
$total_query        = "SELECT COUNT(1) FROM ({$logs_query}) AS combined_table";
$total_rows         = $wpdb->get_var($total_query);
$usage_logs         = $wpdb->get_results($logs_query . $wpdb->prepare(" LIMIT %d,%d", $offset, $items_per_page));
$total_pages        = ceil($total_rows / $items_per_page);
$current_url        = is_admin()
    ? admin_url('admin.php?page=wpaicg_myai_account')
    : home_url($wp->request);

/**
 * Apple-like Minimal/Elegant CSS styling inline
 */
?>
<style>
/* Apple-like minimal styling */
.wpaicg-account-wrapper {
  max-width: 980px;
  margin: 40px auto;
  font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
  color: #2c2c2c;
}

.wpaicg-section {
  background: #fefefe;
  border-radius: 14px;
  box-shadow: 0 2px 6px rgba(0,0,0,0.05);
  margin-bottom: 24px;
  padding: 20px 24px;
}

.wpaicg-header-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
  gap: 10px;
}

.wpaicg-header-grid-item {
  background: #fafafa;
  border-radius: 10px;
  padding: 12px;
  text-align: center;
  border: 1px solid #eee;
}

.wpaicg-header-grid-item h4 {
  margin: 0 0 4px;
  font-size: 14px;
  font-weight: 500;
  color: #666;
}

.wpaicg-header-grid-item strong {
  display: block;
  font-size: 18px;
  font-weight: 600;
  color: #111;
}

.wpaicg-limited {
  color: #ff3b30;
}
.wpaicg-available {
  color: #28a745;
}

.wpaicg-chat-breakdown-section {
  margin-top: 12px;
  display: none;
  background: #fafafa;
  border: 1px solid #eee;
  border-radius: 10px;
  padding: 12px;
}
.wpaicg-chat-breakdown-title {
  margin: 0;
  font-size: 16px;
  font-weight: 500;
  margin-bottom: 8px;
}
.wpaicg-chat-breakdown-table {
  width: 100%;
  border-collapse: collapse;
}
.wpaicg-chat-breakdown-table th {
  text-align: left;
  background: #f4f4f4;
  border-bottom: 1px solid #ddd;
  padding: 8px;
  font-size: 13px;
}
.wpaicg-chat-breakdown-table td {
  padding: 8px;
  border-bottom: 1px solid #f2f2f2;
  font-size: 13px;
}
.wpaicg-show-breakdown-btn {
  color: #007aff;
  text-decoration: underline;
  cursor: pointer;
  font-size: 13px;
  margin-top: 8px;
  display: inline-block;
}

/* Usage logs section */
.wpaicg-logs-title {
  font-size: 18px;
  font-weight: 600;
  margin-bottom: 12px;
}
.wpaicg-usage-table {
  width: 100%;
  border-collapse: collapse;
  margin-top: 8px;
}
.wpaicg-usage-table th {
  text-align: left;
  background: #f9f9f9;
  border-bottom: 1px solid #ddd;
  padding: 8px;
  font-size: 13px;
  color: #333;
}
.wpaicg-usage-table td {
  padding: 8px;
  border-bottom: 1px solid #f2f2f2;
  font-size: 13px;
  color: #555;
}
.wpaicg-no-usage {
  color: #888;
  font-size: 14px;
  margin-top: 8px;
}
.wpaicg-pagination {
  margin-top: 16px;
  display: flex;
  gap: 4px;
  flex-wrap: wrap;
}
.wpaicg-pagination a {
  display: inline-block;
  padding: 6px 10px;
  border-radius: 4px;
  background: #f1f1f1;
  color: #333;
  text-decoration: none;
  font-size: 13px;
}
.wpaicg-pagination a:hover {
  background: #e2e2e2;
}
.wpaicg-pagination .current {
  background: #007aff;
  color: #fff;
}

</style>

<div class="wpaicg-account-wrapper">
  <!-- Section: Tokens Overview -->
  <div class="wpaicg-section">
    <div class="wpaicg-header-grid">
      <!-- Chat Bot tokens -->
      <div class="wpaicg-header-grid-item">
        <h4><?php echo esc_html__('ChatGPT','gpt3-ai-content-generator'); ?></h4>
        <?php if ($chat_limited): ?>
          <strong class="wpaicg-limited"><?php echo esc_html($chat_left); ?></strong>
        <?php else: ?>
          <strong class="wpaicg-available"><?php echo is_numeric($chat_left) ? (int) $chat_left : esc_html($chat_left); ?></strong>
        <?php endif; ?>

        <?php if ($wpaicg_chat_has_limit): ?>
          <span class="wpaicg-show-breakdown-btn" id="wpaicg-show-chat-breakdown">
            <?php echo esc_html__('See breakdown','gpt3-ai-content-generator'); ?>
          </span>
        <?php endif; ?>
      </div>

      <!-- AI Forms tokens -->
      <div class="wpaicg-header-grid-item">
        <h4><?php echo esc_html__('AI Forms','gpt3-ai-content-generator'); ?></h4>
        <?php if ($forms_limited): ?>
          <strong class="wpaicg-limited"><?php echo esc_html($forms_left); ?></strong>
        <?php else: ?>
          <strong class="wpaicg-available"><?php echo is_numeric($forms_left) ? (int)$forms_left : esc_html($forms_left); ?></strong>
        <?php endif; ?>
      </div>

      <!-- Promptbase tokens -->
      <div class="wpaicg-header-grid-item">
        <h4><?php echo esc_html__('Promptbase','gpt3-ai-content-generator'); ?></h4>
        <?php if ($promptbase_limited): ?>
          <strong class="wpaicg-limited"><?php echo esc_html($promptbase_left); ?></strong>
        <?php else: ?>
          <strong class="wpaicg-available"><?php echo is_numeric($promptbase_left) ? (int)$promptbase_left : esc_html($promptbase_left); ?></strong>
        <?php endif; ?>
      </div>

      <!-- Image Generator tokens -->
      <div class="wpaicg-header-grid-item">
        <h4><?php echo esc_html__('Image Generator','gpt3-ai-content-generator'); ?></h4>
        <?php if ($image_limited): ?>
          <strong class="wpaicg-limited"><?php echo esc_html($image_left); ?></strong>
        <?php else: ?>
          <strong class="wpaicg-available"><?php echo is_numeric($image_left) ? $image_left : esc_html($image_left); ?></strong>
        <?php endif; ?>
      </div>

    </div>

    <!-- Chat breakdown container (if needed) -->
    <?php if ($wpaicg_chat_has_limit && !empty($chatBreakdownRows)): ?>
      <div class="wpaicg-chat-breakdown-section" id="wpaicg-chat-breakdown-container">
        <h4 class="wpaicg-chat-breakdown-title"><?php echo esc_html__('Chat Token Breakdown','gpt3-ai-content-generator'); ?></h4>
        <table class="wpaicg-chat-breakdown-table">
          <thead>
            <tr>
              <th><?php echo esc_html__('Bot','gpt3-ai-content-generator'); ?></th>
              <th><?php echo esc_html__('Allocated','gpt3-ai-content-generator'); ?></th>
              <th><?php echo esc_html__('Used','gpt3-ai-content-generator'); ?></th>
              <th><?php echo esc_html__('Remain','gpt3-ai-content-generator'); ?></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($chatBreakdownRows as $row): ?>
              <tr>
                <td><?php echo esc_html($row['type']); ?></td>
                <td><?php echo (int)$row['allocated']; ?></td>
                <td><?php echo (int)$row['used']; ?></td>
                <td><?php echo (int)$row['remain']; ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div><!-- /wpaicg-section -->

  <!-- Section: Usage Logs -->
  <div class="wpaicg-section">
    <div class="wpaicg-logs-title"><?php echo esc_html__('Token Usage','gpt3-ai-content-generator'); ?></div>
    <?php if ($usage_logs && count($usage_logs)): ?>
      <table class="wpaicg-usage-table">
        <thead>
          <tr>
            <th><?php echo esc_html__('Module','gpt3-ai-content-generator'); ?></th>
            <th><?php echo esc_html__('Token/Price','gpt3-ai-content-generator'); ?></th>
            <th><?php echo esc_html__('Created At','gpt3-ai-content-generator'); ?></th>
          </tr>
        </thead>
        <tbody>
          <?php
          foreach ($usage_logs as $lg) {
              $moduleLabel = esc_html__('AI Forms','gpt3-ai-content-generator');
              if ($lg->module === 'chat') {
                  $moduleLabel = esc_html__('Chatbot','gpt3-ai-content-generator');
              } elseif ($lg->module === 'image') {
                  $moduleLabel = esc_html__('Image Generator','gpt3-ai-content-generator');
              } elseif ($lg->module === 'promptbase') {
                  $moduleLabel = esc_html__('Promptbase','gpt3-ai-content-generator');
              }
              // For image, tokens are cost in $
              $value = $lg->module === 'image'
                       ? ('$' . $lg->tokens)
                       : $lg->tokens;
              ?>
              <tr>
                <td><?php echo $moduleLabel; ?></td>
                <td><?php echo esc_html($value); ?></td>
                <td><?php echo esc_html(gmdate('Y-m-d H:i', $lg->created_at)); ?></td>
              </tr>
              <?php
          }
          ?>
        </tbody>
      </table>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
        <div class="wpaicg-pagination">
          <?php
          for ($i = 1; $i <= $total_pages; $i++) {
              $cls = ($i == $logs_page) ? 'current' : '';
              $link = add_query_arg(['wpage' => $i], $current_url);
              echo '<a class="'.$cls.'" href="'.esc_url($link).'">'.(int)$i.'</a>';
          }
          ?>
        </div>
      <?php endif; ?>

    <?php else: ?>
      <div class="wpaicg-no-usage">
        <?php echo esc_html__('No usage data found.','gpt3-ai-content-generator'); ?>
      </div>
    <?php endif; ?>
  </div><!-- /wpaicg-section -->

</div><!-- /wpaicg-account-wrapper -->

<script>
(function(){
  // Toggle the chat breakdown
  var showBtn = document.getElementById('wpaicg-show-chat-breakdown');
  var container = document.getElementById('wpaicg-chat-breakdown-container');
  if (showBtn && container) {
    showBtn.addEventListener('click', function(){
      if (container.style.display === 'block') {
        container.style.display = 'none';
      } else {
        container.style.display = 'block';
      }
    });
  }
})();
</script>