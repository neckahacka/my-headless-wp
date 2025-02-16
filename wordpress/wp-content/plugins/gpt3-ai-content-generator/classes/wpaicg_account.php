<?php
namespace WPAICG;

if ( ! defined( 'ABSPATH' ) ) exit;

if(!class_exists('\\WPAICG\\WPAICG_Account')) {
    class WPAICG_Account
    {
        private static $instance = null;
        public $promptbase_sale = false;
        public $form_sale = false;
        public $image_sale = false;
        public $chat_sale = false;
        public $table_name = 'wpaicg_token_logs';

        public static function get_instance()
        {
            if (is_null(self::$instance)) {
                self::$instance = new self();
            }
            return self::$instance;
        }

        public function __construct()
        {
            add_action( 'admin_menu', array( $this, 'wpaicg_menu' ) );
            add_action('add_meta_boxes_product', array($this,'wpaicg_register_meta_box'));
            add_action('save_post_product',[$this,'wpaicg_save_product'],10,3);
            add_action('woocommerce_order_status_changed',[$this,'wpaicg_order_completed'],10,3);
            add_shortcode('wpaicg_my_account',[$this,'my_account']);

            // New AJAX handler for editing purchased tokens via user management page
            add_action('wp_ajax_wpaicg_update_purchased_tokens', [$this, 'wpaicg_update_purchased_tokens']);
        }

        public function wpaicg_init()
        {
            $wpaicg_my_account_page_id = get_option('wpaicg_my_account_page_id','');
            if(empty($wpaicg_my_account_page_id)){
                $wpaicg_my_account_page_id = wp_insert_post(array(
                    'post_title' => esc_html__('My AI Account','gpt3-ai-content-generator'),
                    'post_name' => 'myai-account',
                    'post_content' => '[wpaicg_my_account]',
                    'post_type' => 'page',
                    'post_status' => 'publish'
                ));
                update_option('wpaicg_my_account_page_id',$wpaicg_my_account_page_id);
            }
        }

        public function save_log($module, $tokens)
        {
            global $wpdb;
            if(is_user_logged_in()) {
                $user_meta_key = 'wpaicg_' . $module . '_tokens';
                $user_tokens = get_user_meta(get_current_user_id(), $user_meta_key, true);
                $new_tokens = floatval($user_tokens) - floatval($tokens);
                $new_tokens = $new_tokens > 0 ? $new_tokens : 0;
                update_user_meta(get_current_user_id(), $user_meta_key, $new_tokens);
                $wpdb->insert($wpdb->prefix . $this->table_name, array(
                    'module' => $module,
                    'tokens' => $tokens,
                    'created_at' => time(),
                    'user_id' => get_current_user_id()
                ));
            }
        }

        public function wpaicg_order_completed($order_id, $old_status, $new_status)
        {
            $order = wc_get_order($order_id);
            $wpaicg_order_status_token = get_option('wpaicg_order_status_token', 'completed');
            if($order && $new_status == $wpaicg_order_status_token){
                $items = $order->get_items();
                $user_id = $order->get_user_id();
                foreach($items as $item){
                    $product_id = $item->get_product_id();
                    $quantity = $item->get_quantity();
                    $wpaicg_product_sale_type = get_post_meta($product_id,'wpaicg_product_sale_type',true);
                    $wpaicg_product_sale_tokens = get_post_meta($product_id,'wpaicg_product_sale_tokens',true);
                    if(
                        !empty($wpaicg_product_sale_type)
                        && in_array($wpaicg_product_sale_type, array('chat','forms','promptbase','image'))
                        && !empty($wpaicg_product_sale_tokens)
                        && $wpaicg_product_sale_tokens > 0
                    ){
                        $wpaicg_service_enable = get_option('wpaicg_'.$wpaicg_product_sale_type.'_enable_sale',false);
                        if($wpaicg_service_enable) {
                            $user_meta_key = 'wpaicg_' . $wpaicg_product_sale_type . '_tokens';
                            $old_tokens = get_user_meta($user_id, $user_meta_key, true);
                            if (empty($old_tokens)) {
                                $old_tokens = 0;
                            }
                            $new_tokens = $old_tokens + ($quantity * $wpaicg_product_sale_tokens);
                            update_user_meta($user_id, $user_meta_key, $new_tokens);
                        }
                    }
                }
            }
        }

        public function wpaicg_save_product($post_id, $post, $update)
        {
            if(isset($_POST['wpaicg_product_sale_type']) && !empty($_POST['wpaicg_product_sale_type'])){
                update_post_meta($post_id,'wpaicg_product_sale_type',sanitize_text_field($_POST['wpaicg_product_sale_type']));
            }
            else{
                delete_post_meta($post_id,'wpaicg_product_sale_type');
            }
            if(isset($_POST['wpaicg_product_sale_tokens']) && !empty($_POST['wpaicg_product_sale_tokens'])){
                update_post_meta($post_id,'wpaicg_product_sale_tokens',sanitize_text_field($_POST['wpaicg_product_sale_tokens']));
            }
            else{
                delete_post_meta($post_id,'wpaicg_product_sale_tokens');
            }
        }

        public function wpaicg_register_meta_box()
        {
            $this->promptbase_sale = get_option('wpaicg_promptbase_enable_sale', false);
            $this->form_sale = get_option('wpaicg_forms_enable_sale', false);
            $this->image_sale = get_option('wpaicg_image_enable_sale', false);
            $this->chat_sale = get_option('wpaicg_chat_enable_sale', false);
            if((!$this->promptbase_sale || $this->image_sale || $this->chat_sale || $this->form_sale) && current_user_can('wpaicg_woocommerce_meta_box')){
                add_meta_box('wpaicg-sale-tokens', esc_html__('AI Power Token Sale','gpt3-ai-content-generator'), [$this, 'wpaicg_meta_box']);
            }
        }

        public function wpaicg_meta_box($post)
        {
            include WPAICG_PLUGIN_DIR . 'admin/views/account/metabox.php';
        }

        public function wpaicg_menu()
        {
            // Retrieve module settings
            $module_settings = get_option('wpaicg_module_settings');
            if ($module_settings === false) {
                $module_settings = array_map(function() { return true; }, \WPAICG\WPAICG_Util::get_instance()->wpaicg_modules);
            }
        
            // Check if the 'ai_account' module is enabled
            if (isset($module_settings['ai_account']) && $module_settings['ai_account']) {
                // Check user role: administrator or other users
                if (in_array('administrator', (array) wp_get_current_user()->roles)) {
                    add_submenu_page(
                        'wpaicg',
                        __('User Credits', 'gpt3-ai-content-generator'),
                        __('User Credits', 'gpt3-ai-content-generator'),
                        'manage_options',
                        'wpaicg_myai_account',
                        array($this, 'my_account_page'),
                        11
                    );
                } else {
                    add_submenu_page(
                        'wpaicg',
                        __('User Credits', 'gpt3-ai-content-generator'),
                        __('User Credits', 'gpt3-ai-content-generator'),
                        'wpaicg_myai_account',
                        'wpaicg_myai_account',
                        array($this, 'my_account_page'),
                        11
                    );
                }
            }
        }
        
        public function my_account_page()
        {
            echo do_shortcode('[wpaicg_my_account]');
        }

        public function my_account()
        {
            if ( ! is_user_logged_in() ) {
                ?>
                <script>window.location.href='<?php echo esc_url(site_url()); ?>';</script>
                <?php
                return;
            }

            // If user is admin or has manage_options
            if ( current_user_can('manage_options') ) {
                // Load user management page
                ob_start();
                include WPAICG_PLUGIN_DIR . 'admin/views/account/usermanagement.php';
                $content = ob_get_clean();
                return $content;
            } else {
                // Normal user page
                ob_start();
                include WPAICG_PLUGIN_DIR . 'admin/views/account/index.php';
                $content = ob_get_clean();
                return $content;
            }
        }

        /**
         * AJAX handler to update a user's purchased tokens for a given module.
         */
        public function wpaicg_update_purchased_tokens() 
        {
            check_ajax_referer('wpaicg_update_purchased_tokens', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => 'You do not have sufficient permissions.']);
            }

            $user_id         = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
            $module          = isset($_POST['module']) ? sanitize_text_field($_POST['module']) : '';
            $purchased_tokens= isset($_POST['purchased_tokens']) ? floatval($_POST['purchased_tokens']) : 0.0;

            if (!$user_id || empty($module)) {
                wp_send_json_error(['message' => 'Invalid input.']);
            }

            // Update user meta
            $meta_key = 'wpaicg_' . $module . '_tokens';
            update_user_meta($user_id, $meta_key, $purchased_tokens);

            // Recalculate
            $free   = $this->calculate_free_tokens_for_user($user_id, $module);
            $used   = $this->calculate_usage_for_module($user_id, $module);
            $remain = max(0, $free + $purchased_tokens - $used);

            wp_send_json_success([
                'message' => 'Purchased tokens updated successfully.',
                'remain'  => $remain
            ]);
        }

        /**
         * Internal helper to replicate 'free tokens' logic from usermanagement.
         * Summation logic for user-limited, role-limited, etc.
         */
        private function calculate_free_tokens_for_user($user_id, $module) 
        {
            // Retrieve user roles
            $user_info  = get_userdata($user_id);
            $user_roles = $user_info ? (array) $user_info->roles : [];

            // Sum up role-based limit if needed
            $find_role_limit = function($role_limited_array, $roles){
                $sum = 0;
                if(is_array($role_limited_array)){
                    foreach ($roles as $r) {
                        if(isset($role_limited_array[$r]) && $role_limited_array[$r] > 0) {
                            $sum += (float) $role_limited_array[$r];
                        }
                    }
                }
                return $sum;
            };

            $total_free = 0.0;

            // Chat
            if($module === 'chat'){
                // 1) widget
                $widget_options = get_option('wpaicg_chat_widget', []);
                if($widget_options){
                    $temp = 0;
                    if(!empty($widget_options['user_limited']) && !empty($widget_options['user_tokens'])){
                        $temp = (float) $widget_options['user_tokens'];
                    }
                    if(!empty($widget_options['role_limited']) && !empty($widget_options['limited_roles'])){
                        $temp += $find_role_limit($widget_options['limited_roles'], $user_roles);
                    }
                    $total_free += $temp;
                }
                // 2) shortcode
                $shortcode_options = get_option('wpaicg_chat_shortcode_options', []);
                if($shortcode_options){
                    $temp = 0;
                    if(!empty($shortcode_options['user_limited']) && !empty($shortcode_options['user_tokens'])){
                        $temp = (float) $shortcode_options['user_tokens'];
                    }
                    if(!empty($shortcode_options['role_limited']) && !empty($shortcode_options['limited_roles'])){
                        $temp += $find_role_limit($shortcode_options['limited_roles'], $user_roles);
                    }
                    $total_free += $temp;
                }
                // 3) custom chatbots
                $bots = new \WP_Query([
                    'post_type' => 'wpaicg_chatbot',
                    'posts_per_page' => -1
                ]);
                if($bots->have_posts()){
                    while($bots->have_posts()){
                        $bots->the_post();
                        $bot_id = get_the_ID();
                        $raw_content = get_post_field('post_content', $bot_id);
                        if(!$raw_content) {
                            continue;
                        }
                        $bot_settings = json_decode($raw_content, true);
                        if(!is_array($bot_settings)) {
                            continue;
                        }
                        $temp = 0;
                        if(!empty($bot_settings['user_limited']) && !empty($bot_settings['user_tokens'])){
                            $temp = (float) $bot_settings['user_tokens'];
                        }
                        if(!empty($bot_settings['role_limited']) && !empty($bot_settings['limited_roles'])){
                            $temp += $find_role_limit($bot_settings['limited_roles'], $user_roles);
                        }
                        $total_free += $temp;
                    }
                    wp_reset_postdata();
                }
                return $total_free;
            }

            // forms
            if($module === 'forms'){
                $limit_settings = get_option('wpaicg_limit_tokens_form', []);
                $temp = 0;
                if(!empty($limit_settings['user_limited']) && !empty($limit_settings['user_tokens'])){
                    $temp = (float) $limit_settings['user_tokens'];
                }
                if(!empty($limit_settings['role_limited']) && !empty($limit_settings['limited_roles'])){
                    $temp += $find_role_limit($limit_settings['limited_roles'], $user_roles);
                }
                return $temp;
            }

            // promptbase
            if($module === 'promptbase'){
                $limit_settings = get_option('wpaicg_limit_tokens_promptbase', []);
                $temp = 0;
                if(!empty($limit_settings['user_limited']) && !empty($limit_settings['user_tokens'])){
                    $temp = (float) $limit_settings['user_tokens'];
                }
                if(!empty($limit_settings['role_limited']) && !empty($limit_settings['limited_roles'])){
                    $temp += $find_role_limit($limit_settings['limited_roles'], $user_roles);
                }
                return $temp;
            }

            // image
            if($module === 'image'){
                $limit_settings = get_option('wpaicg_limit_tokens_image', []);
                $temp = 0;
                if(!empty($limit_settings['user_limited']) && !empty($limit_settings['user_tokens'])){
                    $temp = (float) $limit_settings['user_tokens'];
                }
                if(!empty($limit_settings['role_limited']) && !empty($limit_settings['limited_roles'])){
                    $temp += $find_role_limit($limit_settings['limited_roles'], $user_roles);
                }
                return $temp;
            }

            // default
            return 0.0;
        }

        /**
         * Internal helper to replicate usage logs logic.
         */
        private function calculate_usage_for_module($user_id, $module)
        {
            global $wpdb;
            $tbl = $wpdb->prefix . 'wpaicg_token_logs';
            $sum = $wpdb->get_var(
                $wpdb->prepare(
                    "SELECT SUM(tokens) FROM $tbl WHERE user_id=%d AND module=%s",
                    $user_id,
                    $module
                )
            );
            if(!$sum){
                $sum = 0;
            }
            return (float) $sum;
        }
    }
    WPAICG_Account::get_instance();
}