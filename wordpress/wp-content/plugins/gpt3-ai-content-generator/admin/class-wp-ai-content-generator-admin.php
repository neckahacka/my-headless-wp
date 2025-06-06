<?php
if ( ! defined( 'ABSPATH' ) ) exit;
/**
 * The admin-specific functionality of the plugin.
 *
 * @link       https://aipower.org
 * @since      1.0.0
 *
 * @package    Wp_Ai_Content_Generator
 * @subpackage Wp_Ai_Content_Generator/admin
 */
/**
 * The admin-specific functionality of the plugin.
 *
 * Defines the plugin name, version, and two examples hooks for how to
 * enqueue the admin-specific stylesheet and JavaScript.
 *
 * @package    Wp_Ai_Content_Generator
 * @subpackage Wp_Ai_Content_Generator/admin
 * @author     Senol Sahin <senols@gmail.com>
 */
class Wp_Ai_Content_Generator_Admin
{
    /**
     * The ID of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $plugin_name    The ID of this plugin.
     */
    private  $plugin_name ;
    /**
     * The version of this plugin.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $version    The current version of this plugin.
     */
    private  $version ;
    /**
     * Initialize the class and set its properties.
     *
     * @since    1.0.0
     * @param      string    $plugin_name       The name of this plugin.
     * @param      string    $version    The version of this plugin.
     */
    public function __construct( $plugin_name, $version )
    {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    /**
     * Register the stylesheets for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_styles()
    {
        wp_enqueue_style(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/wp-ai-content-generator-admin.css',
            array(),
            $this->version,
            'all'
        );
        $screen = get_current_screen();
        if(strpos($screen->id, 'wpaicg') !== false) {
            wp_enqueue_style(
                'jquery-ui',
                plugin_dir_url(__FILE__) . 'css/jquery-ui.css',
                array(),
                $this->version,
                'all'
            );
            // Check for wpaicg_single_content_beta, wpaicg_bulk_content, or wpaicg_embeddings
            if (isset($_GET['page']) && ($_GET['page'] == 'wpaicg_single_content' || $_GET['page'] == 'wpaicg_bulk_content' || $_GET['page'] == 'wpaicg_embeddings')) {
                wp_enqueue_style(
                    'clean-formfull',
                    plugin_dir_url(__FILE__) . 'css/clean_extra.css',
                    array(),
                    $this->version,
                    'all'
                );
            }

            if (isset($_GET['page']) && ($_GET['page'] == 'wpaicg_forms')) {
                wp_enqueue_style(
                    'clean-formfull',
                    plugin_dir_url(__FILE__) . 'css/aiforms.css',
                    array(),
                    $this->version,
                    'all'
                );
            }

            //or wpaicg_single_content_beta or wpaicg_bulk_content or wpaicg_embeddings
            if (isset($_GET['page']) && $_GET['page'] == 'wpaicg_chatgpt' || isset($_GET['page']) && $_GET['page'] == 'wpaicg' || isset($_GET['page']) && $_GET['page'] == 'wpaicg_single_content' || isset($_GET['page']) && $_GET['page'] == 'wpaicg_bulk_content' || isset($_GET['page']) && $_GET['page'] == 'wpaicg_embeddings') {
                wp_enqueue_style(
                    'clean-form',
                    plugin_dir_url( __FILE__ ) . 'css/clean.css',
                    array(),
                    $this->version,
                    'all'
                );
            }
        }
        if (strpos($screen->id, 'wpaicg') !== false) {
            if (isset($_GET['page']) && $_GET['page'] == 'wpaicg') {
                wp_enqueue_style(
                    'wpaicg-dashboard',
                    plugin_dir_url(__FILE__) . 'css/dashboard.css',
                    array(),
                    $this->version,
                    'all'
                );
                wp_enqueue_style(
                    'wpaicg-chatbot',
                    plugin_dir_url(__FILE__) . 'css/chatbot.css',
                    array(),
                    $this->version,
                    'all'
                );
                wp_enqueue_style(
                    $this->plugin_name . '-public',
                    plugin_dir_url(dirname(__FILE__)) . 'public/css/wp-ai-content-generator-public.css',
                    array(),
                    $this->version,
                    'all'
                );
            }
        }
        wp_enqueue_style(
            'font-awesome',
            plugin_dir_url( __FILE__ ) . 'css/font-awesome.min.css',
            array(),
            $this->version,
            'all'
        );
    }

    /**
     * Register the JavaScript for the admin area.
     *
     * @since    1.0.0
     */
    public function enqueue_scripts()
    {
        wp_enqueue_script(
            $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/wp-ai-content-generator-admin.js',
            array( 'jquery' ),
            $this->version,
            true
        );
        // enqueue dashboard js
        $screen = get_current_screen();
        if(strpos($screen->id, 'wpaicg') !== false) {
            if (isset($_GET['page']) && $_GET['page'] == 'wpaicg') {
                wp_enqueue_script(
                    'wpaicg-chatbot',
                    plugin_dir_url(__FILE__) . 'js/chatbot.js',
                    array( 'jquery' ),
                    $this->version,
                    false
                );
            }
            if (isset($_GET['page']) && ($_GET['page'] == 'wpaicg' || $_GET['page'] == 'wpaicg_forms')) {
                // Enqueue the marked.js script for both 'wpaicg' and 'wpaicg_forms' pages
                wp_enqueue_script('wpaicg-markedjs', WPAICG_PLUGIN_URL . 'public/js/marked.js', array(), null, false);
                wp_enqueue_script('wpaicg-formshortcode', WPAICG_PLUGIN_URL . 'public/js/wpaicg-form-shortcode.js', array(), null, false);
            }
            
        }
        wp_enqueue_script( 'jquery' );
        wp_enqueue_script( 'jquery-ui-sortable' );
        wp_enqueue_script( 'jquery-ui-tabs' );
        wp_enqueue_script( 'jquery-ui-accordion' );
    }

    function wpaicg_load_db_vaule_js()
    {
        global  $post ;
        include WPAICG_PLUGIN_DIR.'admin/views/scripts.php';
    }

    public function wpaicg_options_page()
    {
        if (in_array('administrator', (array)wp_get_current_user()->roles)) {
            add_menu_page(
                __('AI Power', 'wp-ai-content-generator'),
                'AI Power',
                'manage_options',
                'wpaicg',
                array($this, 'wpaicg_dashboard_page'),  // Redirect to dashboard
                WPAICG_PLUGIN_URL . 'public/images/icon.png',
                6
            );
        } else {
            add_menu_page(
                __('AI Power', 'wp-ai-content-generator'),
                'AI Power',
                'wpaicg_settings',
                'wpaicg',
                array($this, 'wpaicg_dashboard_page'),  // Redirect to dashboard
                WPAICG_PLUGIN_URL . 'public/images/icon.png',
                6
            );
        }
    }
    
    
    public function wpaicg_dashboard_page()
    {
        include WPAICG_PLUGIN_DIR . 'admin/views/settings/dashboard.php';
    }

    public function wpaicg_set_post_content_()
    {
        wp_send_json( 'success' );
        die;
    }

    /**
     * Save the meta box selections.
     *
     * @param int $post_id  The post ID.
     */
    public static function save( int $post_id )
    {
        $wpaicg_keys = array(
            'wpaicg_settings',
            '_wporg_language',
            '_wporg_preview_title',
            '_wporg_number_of_heading',
            '_wporg_heading_tag',
            '_wporg_writing_style',
            '_wporg_writing_tone',
            '_wporg_modify_headings',
            '_wporg_add_img',
            'wpaicg_image_featured',
            '_wporg_add_tagline',
            '_wporg_add_intro',
            '_wporg_add_conclusion',
            '_wporg_anchor_text',
            '_wporg_target_url',
            '_wporg_generated_text',
            '_wporg_cta_pos',
            '_wporg_target_url_cta',
            'wpaicg_toc',
            'wpaicg_toc_title',
            'wpaicg_toc_title_tag',
            'wpaicg_intro_title_tag',
            'wpaicg_conclusion_title_tag'
        );
        foreach($wpaicg_keys as $wpaicg_key){
            if ( array_key_exists( $wpaicg_key, $_POST ) ) {
                update_post_meta($post_id,$wpaicg_key, \WPAICG\wpaicg_util_core()->sanitize_text_or_array_field($_POST[$wpaicg_key]));
            }
            else{
                delete_post_meta($post_id,$wpaicg_key);
            }
        }
    }

    /**
     * Display the meta box HTML to the user.
     *
     * @param WP_Post $post   Post object.
     */
    public static function html( $post )
    {
        include WPAICG_PLUGIN_DIR.'admin/views/metabox.php';
    }

}
// add_action( 'add_meta_boxes', [ 'Wp_Ai_Content_Generator_Admin', 'add_wp_ai_metabox' ] );
add_action( 'save_post', [ 'Wp_Ai_Content_Generator_Admin', 'save' ] );
