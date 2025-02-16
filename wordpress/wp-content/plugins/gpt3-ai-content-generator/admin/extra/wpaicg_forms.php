<?php

declare(strict_types=1);

namespace WPAICG;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

wp_enqueue_editor();

// -----------------------------------------------------------------------------
// Enqueue external CSS or other dependencies if needed
// -----------------------------------------------------------------------------
$wpaicg_plugin_dir = WPAICG_PLUGIN_DIR;

// -----------------------------------------------------------------------------
// 1. Load JSON data: built-in forms, categories, icons
// -----------------------------------------------------------------------------

// Load icons
$wpaicg_icons_file = $wpaicg_plugin_dir . 'admin/data/icons.json';
$wpaicg_icons      = array();
if ( file_exists( $wpaicg_icons_file ) ) {
    $content = file_get_contents( $wpaicg_icons_file );
    $decoded = json_decode( $content, true );
    if ( is_array( $decoded ) ) {
        $wpaicg_icons = $decoded;
    }
}

// Load categories
global $wpaicg_categories;
$wpaicg_cats_file = $wpaicg_plugin_dir . 'admin/data/gptcategories.json';
$wpaicg_categories = array();

if ( file_exists( $wpaicg_cats_file ) ) {
    $content = file_get_contents( $wpaicg_cats_file );
    $decoded = json_decode( $content, true );
    if ( is_array( $decoded ) ) {
        foreach ( $decoded as $key => $name ) {
            $wpaicg_categories[ $key ] = $name;
        }
    }
}

// Load built-in AI Forms
$wpaicg_builtin_file = $wpaicg_plugin_dir . 'admin/data/gptforms.json';
$wpaicg_builtin_forms = array();
if ( file_exists( $wpaicg_builtin_file ) ) {
    $json_content = file_get_contents( $wpaicg_builtin_file );
    $parsed       = json_decode( $json_content, true );
    if ( is_array( $parsed ) ) {
        $wpaicg_builtin_forms = $parsed;
    }
}

// Keep track of authors in an associative array. 'default' => 'AI Power'.
$wpaicg_authors = array(
    'default' => array(
        'name'  => esc_html__( 'AI Power', 'gpt3-ai-content-generator' ),
        'count' => 0,
    ),
);

// -----------------------------------------------------------------------------
// 2. Load custom forms from DB
// -----------------------------------------------------------------------------
global $wpdb;
$sql = "SELECT p.ID, p.post_author, p.post_title
        FROM {$wpdb->posts} p
        WHERE p.post_type = 'wpaicg_form'
          AND p.post_status = 'publish'
        ORDER BY p.post_date DESC";
$wpaicg_custom_forms = $wpdb->get_results( $sql );

// -----------------------------------------------------------------------------
// 3. Combine built-in + custom forms into one array
// -----------------------------------------------------------------------------
$wpaicg_all_forms = array();

// Process built-in forms
if ( ! empty( $wpaicg_builtin_forms ) ) {
    foreach ( $wpaicg_builtin_forms as $form ) {
        $form_id    = isset( $form['id'] ) ? (int) $form['id'] : 0;
        $form_title = isset( $form['title'] ) ? $form['title'] : esc_html__( 'Untitled Form', 'gpt3-ai-content-generator' );
        $raw_icon   = isset( $form['icon'] )  ? $form['icon'] : '';
        $form_color = isset( $form['color'] ) ? $form['color'] : '#999999';

        // Map the raw icon key to a dashicons class if found:
        if ( ! empty( $raw_icon ) && isset( $wpaicg_icons[ $raw_icon ] ) ) {
            $final_icon = $wpaicg_icons[ $raw_icon ];
        } else {
            // fallback if no match
            $final_icon = 'dashicons dashicons-admin-generic';
        }

        $form_cat = array();
        if ( ! empty( $form['category'] ) ) {
            $raw_cat = explode( ',', $form['category'] );
            foreach ( $raw_cat as $c ) {
                $c_trim = trim( $c );
                $form_cat[] = $c_trim;
            }
        }
        $wpaicg_authors['default']['count']++;

        $wpaicg_all_forms[] = array(
            'type'       => 'builtin',
            'builtin_id' => $form_id,
            'db_id'      => 0,
            'title'      => $form_title,
            'author'     => 'default',
            'icon'       => $final_icon,
            'icon_key'   => '', // built-ins store the final dashicon in 'icon'
            'color'      => $form_color,
            'categories' => $form_cat,
            'data'       => $form,
        );
    }
}

// Process custom forms
if ( ! empty( $wpaicg_custom_forms ) ) {
    foreach ( $wpaicg_custom_forms as $row ) {
        $author_id   = $row->post_author;
        $author_info = get_userdata( $author_id );
        $author_name = ( $author_info ) ? $author_info->display_name : esc_html__( 'Unknown', 'gpt3-ai-content-generator' );

        if ( ! isset( $wpaicg_authors[ $author_id ] ) ) {
            $wpaicg_authors[ $author_id ] = array(
                'name'  => $author_name,
                'count' => 0,
            );
        }
        $wpaicg_authors[ $author_id ]['count']++;

        // --- Fetch the real icon & color from post meta ---
        $raw_icon  = get_post_meta( $row->ID, 'wpaicg_form_icon', true );
        $raw_color = get_post_meta( $row->ID, 'wpaicg_form_color', true );

        $sanitized_icon  = sanitize_text_field( $raw_icon );
        $sanitized_color = $raw_color ? sanitize_hex_color( $raw_color ) : '';
        if ( empty( $sanitized_color ) ) {
            $sanitized_color = '#777777'; // fallback
        }

        // Attempt to map custom icon to dashicons if it's a known key:
        if ( ! empty( $sanitized_icon ) && isset( $wpaicg_icons[ $sanitized_icon ] ) ) {
            $final_icon = $wpaicg_icons[ $sanitized_icon ];
        } elseif ( ! empty( $sanitized_icon ) ) {
            // Possibly the user saved an entire dashicon class, so use that directly
            $final_icon = $sanitized_icon;
        } else {
            $final_icon = 'dashicons dashicons-admin-generic';
        }

        $form_cat = array();

        $wpaicg_all_forms[] = array(
            'type'       => 'custom',
            'builtin_id' => 0,
            'db_id'      => (int) $row->ID,
            'title'      => $row->post_title,
            'author'     => $author_id,
            'icon'       => $final_icon,
            'icon_key'   => '', // for custom, we've resolved final icon into 'icon'
            'color'      => $sanitized_color,
            'categories' => $form_cat,
        );
    }
}
?>
<h1>
    <?php echo esc_html__( 'AI Forms', 'gpt3-ai-content-generator' ); ?>
    <span
        class="wpaicg_icon small"
        id="wpaicg_settings_icon"
        style="cursor:pointer; margin-left:8px;"
        title="<?php echo esc_attr__( 'Settings', 'gpt3-ai-content-generator' ); ?>">
        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-settings">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.065 2.572c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.572 1.065c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 .826 -2.37 -2.37a1.724 1.724 0 0 0 -1.065 -2.572c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z" />
            <path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0" />
        </svg>
    </span>
    <span
        class="wpaicg_icon small"
        id="wpaicg_logs_icon"
        style="cursor:pointer; margin-left:4px;"
        title="<?php echo esc_attr__( 'Logs', 'gpt3-ai-content-generator' ); ?>">
        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-history">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
            <path d="M12 8l0 4l2 2" />
            <path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5" />
        </svg>
    </span>

    <!-- Menu icon + dropdown -->
    <span id="wpaicg_menu_wrapper" style="position: relative; margin-left:4px;">
        <span
            class="wpaicg_icon small"
            id="wpaicg_menu_icon"
            style="cursor:pointer;"
            title="<?php echo esc_attr__( 'Menu', 'gpt3-ai-content-generator' ); ?>">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-menu">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M4 8l16 0" />
                <path d="M4 16l16 0" />
            </svg>
        </span>
        <div id="wpaicg_menu_dropdown" style="display:none;">
            <button class="button button-primary" id="wpaicg_import_forms">
                <?php echo esc_html__( 'Import', 'gpt3-ai-content-generator' ); ?>
            </button>
            <button class="button button-primary" id="wpaicg_export_forms">
                <?php echo esc_html__( 'Export', 'gpt3-ai-content-generator' ); ?>
            </button>
            <button
                class="button"
                id="wpaicg_delete_all_forms"
                style="background:#a00000; color:#fff; border-color:#a00000;">
                <?php echo esc_html__( 'Delete', 'gpt3-ai-content-generator' ); ?>
            </button>
        </div>
    </span>
</h1>

<!-- Global message container for inline success/error -->
<div id="wpaicg_global_message"></div>

<!-- Top icons container -->
<div class="wpaicg_button_container apple-like" id="wpaicg_top_icons_container">
    <div class="wpaicg_top_icons_left">
        <!-- Preview/Details mode buttons (initially hidden) -->
        <button class="button wpaicg_preview_back" style="display:none;">
            <?php echo esc_html__( 'Back', 'gpt3-ai-content-generator' ); ?>
        </button>
        <!-- Create button -->
        <button
            class="button button-primary"
            id="wpaicg_plus_icon"
            title="<?php echo esc_attr__( 'Create New Form', 'gpt3-ai-content-generator' ); ?>">
            <?php echo esc_html__( 'Create New', 'gpt3-ai-content-generator' ); ?>
        </button>

        <button class="button wpaicg_preview_duplicate" style="display:none;">
            <?php echo esc_html__( 'Duplicate', 'gpt3-ai-content-generator' ); ?>
        </button>
        <button class="button wpaicg_preview_edit" style="display:none;">
            <?php echo esc_html__( 'Edit', 'gpt3-ai-content-generator' ); ?>
        </button>
        <!-- New DELETE button -->
        <button class="button wpaicg_preview_delete" style="display:none;">
            <?php echo esc_html__( 'Delete', 'gpt3-ai-content-generator' ); ?>
        </button>

        <!-- NEW: Save buttons (hidden by default) -->
        <button class="button button-primary" id="wpaicg_create_save_form" style="display:none;">
            <?php echo esc_html__('Save Form','gpt3-ai-content-generator'); ?>
        </button>
        <button class="button button-primary" id="wpaicg_save_edited_form" style="display:none;">
            <?php echo esc_html__('Save Changes','gpt3-ai-content-generator'); ?>
        </button>

        <!-- 'Return' button used for logs/settings/edit/create screens -->
        <button
            class="button"
            id="wpaicg_return_main"
            style="display:none;"
            title="<?php echo esc_attr__( 'Back to AI Forms', 'gpt3-ai-content-generator' ); ?>">
            <?php echo esc_html__( 'Back', 'gpt3-ai-content-generator' ); ?>
        </button>

        <!-- NEW Save button for Settings page -->
        <button class="button button-primary" id="wpaicg_settings_top_save" style="display:none;">
            <?php echo esc_html__( 'Save', 'gpt3-ai-content-generator' ); ?>
        </button>

        <!-- SHORTCODE SNIPPET DISPLAY -->
        <span id="wpaicg_shortcode_snippet" style="display:none; margin-left:6px;">
            <span id="wpaicg_snippet_clickable" class="wpaicg_snippet" style="cursor:pointer;"></span>
        </span>
        <span
            id="wpaicg_shortcode_copied_msg"
            class="wpaicg_copied_msg"
            style="display:none; margin-left:6px;">
            <?php echo esc_html__('Copied!','gpt3-ai-content-generator'); ?>
        </span>

    </div>

    <!-- Right side: search (shown only on main list) -->
    <div class="wpaicg_top_icons_right">
        <div id="wpaicg_search_container" style="display:none;">
            <input
                type="search"
                id="wpaicg_search_forms"
                style="max-height: 30px;"
                placeholder="<?php echo esc_attr__( 'Search forms...', 'gpt3-ai-content-generator' ); ?>" />
        </div>
        <span
            class="wpaicg_icon"
            id="wpaicg_search_icon"
            title="<?php echo esc_attr__( 'Search Forms', 'gpt3-ai-content-generator' ); ?>">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-search">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"/>
                <path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" />
                <path d="M21 21l-6 -6" />
            </svg>
        </span>
    </div>
</div>

<!-- Main container for the forms list (includes the left col, forms grid, preview) -->
<div id="wpaicg_aiforms_container">
    <?php
    // This partial includes the left column (authors/cats) and the right column
    // (forms grid + preview panel).
    include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_list.php' );
    ?>
</div>

<!-- Embed the merged array as a JS variable for client-side pagination/filtering -->
<script>
window.wpaicgAllForms = <?php echo wp_json_encode( $wpaicg_all_forms ); ?>;
</script>

<!-- NEW: Drag & drop form creation container -->
<?php include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_create.php' ); ?>

<!-- NEW: Drag & drop form editing container -->
<?php include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_edit.php' ); ?>

<?php
// Include the placeholders for settings and logs
include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_aiform_settings.php' );
include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_aiform_logs.php' );

// Include the main JS for AI Forms Beta
include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_js.php' );
include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_js_logs.php' );
include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_js_edit.php' );
include_once( WPAICG_PLUGIN_DIR . 'admin/extra/wpaicg_forms_js_create.php' );
?>

<!-- Hidden file input for importing AI Forms JSON -->
<input
    type="file"
    id="wpaicg_import_json_file"
    style="display:none;"
    accept=".json" />