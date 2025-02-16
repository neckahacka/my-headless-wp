<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Left Column: Authors & Categories
 * Right Column: Forms Grid & Preview Panel
 */
?>

<div class="wpaicg_flex" id="wpaicg_flex">
    <!-- Left Column (Authors & Categories) -->
    <div class="wpaicg_left_column">
        <h4><?php echo esc_html__( 'Author', 'gpt3-ai-content-generator' ); ?></h4>
        <ul class="wpaicg_author_list" id="wpaicg_author_filters">
        <?php
        // $wpaicg_authors is defined in wpaicg_forms.php
        // Here, we simply list the checkboxes
        if ( ! empty( $wpaicg_authors ) ) {
            foreach ( $wpaicg_authors as $author_key => $info ) {
                $display_name = $info['name'];
                $count        = $info['count'];
                ?>
                <li>
                    <label>
                        <input type="checkbox" class="wpaicg_author_checkbox" value="<?php echo esc_attr( $author_key ); ?>">
                        <?php
                        printf(
                            '%1$s (%2$d)',
                            esc_html( $display_name ),
                            absint( $count )
                        );
                        ?>
                    </label>
                </li>
                <?php
            }
        }
        ?>
        </ul>

        <h4><?php echo esc_html__( 'Category', 'gpt3-ai-content-generator' ); ?></h4>
        <ul class="wpaicg_cat_list" id="wpaicg_cat_filters">
        <?php
        // $wpaicg_categories is defined in wpaicg_forms.php
        if ( ! empty( $wpaicg_categories ) ) {
            foreach ( $wpaicg_categories as $cat_key => $cat_label ) {
                ?>
                <li>
                    <label>
                        <input type="checkbox" class="wpaicg_cat_checkbox" value="<?php echo esc_attr( $cat_key ); ?>">
                        <?php echo esc_html( $cat_label ); ?>
                    </label>
                </li>
                <?php
            }
        }
        ?>
        </ul>
    </div><!-- .wpaicg_left_column -->

    <!-- Right Column -->
    <div class="wpaicg_right_column">
        <!-- The forms grid (rendered dynamically in JS with pagination) -->
        <div class="wpaicg_forms_grid" id="wpaicg_forms_grid">
            <!-- Initially empty. JS will handle “No forms” or listing. -->
        </div><!-- .wpaicg_forms_grid -->

        <!-- The preview panel: shown when user clicks an item -->
        <div class="wpaicg_preview_panel" id="wpaicg_preview_panel">
            <div class="wpaicg_preview_form_container"></div>
        </div>
    </div><!-- .wpaicg_right_column -->
</div><!-- .wpaicg_flex -->