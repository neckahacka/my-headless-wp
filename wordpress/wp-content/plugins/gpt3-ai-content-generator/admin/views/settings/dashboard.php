<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
global $wpdb;

// Define the table name
$table_name = $wpdb->prefix . 'wpaicg';

// Check if the table exists and has any data
if ($wpdb->get_var("SHOW TABLES LIKE '{$table_name}'") !== $table_name || $wpdb->get_var("SELECT COUNT(*) FROM {$table_name}") == 0) {
    // Initialize the table
    \WPAICG\WPAICG_Dashboard::get_instance()->aipower_initialize_settings_table();
}

// Retrieve the modules from the utility class
$available_modules = \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules;

// Retrieve module settings
$module_settings = get_option('wpaicg_module_settings');

if ($module_settings === false) {
    // Option doesn't exist, create it with default values
    $module_settings = [];
    foreach ($available_modules as $module_key => $module_data) {
        // Enable all modules by default except 'ai_account' and 'audio_converter'
        $module_settings[$module_key] = ($module_key !== 'ai_account' && $module_key !== 'audio_converter');
    }
    // Also enable the chat_bot by default
    $module_settings['chat_bot'] = true;
    update_option('wpaicg_module_settings', $module_settings);
}

// Retrieve the OpenAI API key from the wpaicg_settings table
$settings_row = $wpdb->get_row("SELECT * FROM " . esc_sql($wpdb->prefix . 'wpaicg') . " WHERE id = 1", ARRAY_A);

$new_default_fields = [
    'lead_collection'    => '0',
    'lead_title'         => 'Let us know how to contact you',
    'enable_lead_name'   => '1',
    'lead_name'          => 'Name',
    'enable_lead_email'  => '1',
    'lead_email'         => 'Email',
    'enable_lead_phone'  => '1',
    'lead_phone'         => 'Phone',
];

function update_chat_options($option_name, $new_fields) {
    if (get_option($option_name)) {
        $options = get_option($option_name);
        foreach ($new_fields as $key => $value) {
            if (!isset($options[$key])) {
                $options[$key] = $value;
            }
        }
        update_option($option_name, $options);
    }
}

update_chat_options('wpaicg_chat_shortcode_options', $new_default_fields);
update_chat_options('wpaicg_chat_widget', $new_default_fields);

?>
<div class="aipower-dashboard-container">
    <!-- Top Navigation -->
    <nav class="aipower-top-navigation">
        <ul>
            <?php foreach ($available_modules as $module_key => $module_data): ?>
                <?php
                // Skip 'chat_bot' module in top navigation as per your requirement
                if ($module_key === 'chat_bot') {
                    continue;
                }
                ?>
                <li data-module="<?php echo esc_attr($module_key); ?>" style="<?php echo (isset($module_settings[$module_key]) && $module_settings[$module_key]) ? '' : 'display: none;'; ?>">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=' . $module_data['href'])); ?>" class="aipower-nav-link">
                        <?php echo $module_data['icon']; ?>
                        <?php echo esc_html($module_data['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </nav>

    
    <header class="aipower-header">
        <h1 class="aipower-title"><?php echo esc_html__('AI Power', 'gpt3-ai-content-generator'); ?></h1>
        <div class="aipower-status-container">
            <div class="aipower-spinner" id="aipower-spinner" style="display: none;"></div>
            <div class="aipower-message" id="aipower-message"></div>
            <!-- Progress display -->
            <div id="aipower-delete-progress" style="display:none;">
                <p id="aipower-delete-progress-counter">0/0</p>
            </div>
        </div>
        <input type="hidden" id="ai-engine-nonce" value="<?php echo wp_create_nonce('wpaicg_save_ai_engine_nonce'); ?>">
    </header>

    <!-- Tab Navigation -->
    <div class="aipower-tabs">
        <button class="aipower-tab-btn active" data-tab="ai-settings"><?php echo esc_html__('Dashboard', 'gpt3-ai-content-generator'); ?></button>
        
        <!-- Check if Chat Bot module is enabled, and show the tab if it is -->
        <?php if (isset($module_settings['chat_bot']) && $module_settings['chat_bot']): ?>
            <button class="aipower-tab-btn" data-tab="chatbot"><?php echo esc_html__('Chatbot', 'gpt3-ai-content-generator'); ?></button>
        <?php endif; ?>
        
        <button class="aipower-tab-btn" data-tab="content-writer"><?php echo esc_html__('Tools', 'gpt3-ai-content-generator'); ?></button>
    </div>


    <!-- Tab Content -->
    <div class="aipower-tab-content">
        <?php include 'ai.php'; ?>
        <?php include 'chatbot.php'; ?>
        <?php include 'settings.php'; ?>
    </div>
</div>
<script>
    var moduleSettings = <?php echo json_encode($module_settings); ?>;
</script>