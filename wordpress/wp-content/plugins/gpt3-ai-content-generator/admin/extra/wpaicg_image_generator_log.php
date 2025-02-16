<?php
if ( ! defined( 'ABSPATH' ) ) exit;

global $wpdb;

// Check if the log table exists
$wpaicgLogTable = $wpdb->prefix . 'wpaicg_image_logs';
if ($wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $wpaicgLogTable)) != $wpaicgLogTable) {
    echo '<div class="notice notice-info is-dismissible">
        <p>'. esc_html__('The log table does not exist. Please deactivate and then reactivate the plugin to trigger the table creation.', 'gpt3-ai-content-generator') .'</p>
    </div>';
    return; // Exit so that the rest of the code doesn't run
}

$wpaicg_log_page = isset($_GET['wpage']) && !empty($_GET['wpage']) ? sanitize_text_field($_GET['wpage']) : 1;
$search = isset($_GET['search']) && !empty($_GET['search']) ? sanitize_text_field($_GET['search']) : '';
$where = '';
if (isset($_GET['search']) && !empty($_GET['search']) && !wp_verify_nonce($_GET['wpaicg_nonce'], 'wpaicg_imagelog_search_nonce')) {
    die(esc_html__('Nonce verification failed','gpt3-ai-content-generator'));
}

if(!empty($search)) {
    $where .= $wpdb->prepare(" AND `prompt` LIKE %s", '%' . $wpdb->esc_like($search) . '%');
}

$query = "SELECT * FROM ".$wpdb->prefix."wpaicg_image_logs WHERE 1=1".$where;
$total_query = "SELECT COUNT(1) FROM ({$query}) AS combined_table";
$total = $wpdb->get_var( $total_query );
$items_per_page = 20;
$offset = ( $wpaicg_log_page * $items_per_page ) - $items_per_page;
$wpaicg_logs = $wpdb->get_results( $query . " ORDER BY created_at DESC LIMIT {$offset}, {$items_per_page}" );
$totalPage         = ceil($total / $items_per_page);
?>
<style>
</style>
<form action="" method="get">
    <?php wp_nonce_field('wpaicg_imagelog_search_nonce', 'wpaicg_nonce'); ?>
    <input type="hidden" name="page" value="wpaicg_image_generator">
    <input type="hidden" name="action" value="logs">
    <div class="wpaicg-d-flex mb-5">
        <input style="width: 100%" value="<?php echo esc_html($search)?>" class="regular-text" name="search" type="text" placeholder="<?php echo esc_html__('Type for search','gpt3-ai-content-generator')?>">
        <button class="button button-primary"><?php echo esc_html__('Search','gpt3-ai-content-generator')?></button>
        <?php if ($total > 0) : ?>
        <button id="delete-all" class="button button-secondary" style="color: white;background: #9d0000;border: #9d0000;margin-left: 5px;"><?php echo esc_html__('Delete All','gpt3-ai-content-generator')?></button>
        <?php endif; ?>
    </div>
</form>
<table class="wp-list-table widefat fixed striped table-view-list posts">
    <thead>
    <tr>
        <th><?php echo esc_html__('Prompt','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Size','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Total Images','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Page','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Shortcode','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Duration','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Estimated','gpt3-ai-content-generator')?></th>
        <th><?php echo esc_html__('Created At','gpt3-ai-content-generator')?></th>
    </tr>
    </thead>
    <tbody class="wpaicg-builder-list">
    <?php
    if($wpaicg_logs && is_array($wpaicg_logs) && count($wpaicg_logs)){
        foreach ($wpaicg_logs as $wpaicg_log) {
            $source = '';
            if($wpaicg_log->source > 0){
                $source = get_the_title($wpaicg_log->source);
            }
            ?>
            <tr>
                <td><?php echo esc_html($wpaicg_log->prompt)?></td>
                <td><?php echo esc_html($wpaicg_log->size)?></td>
                <td><?php echo esc_html($wpaicg_log->total)?></td>
                <td><?php echo esc_html($source)?></td>
                <td><code><?php echo esc_html($wpaicg_log->shortcode)?></code></td>
                <td><?php echo esc_html(WPAICG\WPAICG_Content::get_instance()->wpaicg_seconds_to_time((int)$wpaicg_log->duration))?></td>
                <td>$<?php echo esc_html($wpaicg_log->price)?></td>
                <td><?php echo esc_html(gmdate('d.m.Y H:i',$wpaicg_log->created_at))?></td>
            </tr>
            <?php
        }
    }
    ?>
    </tbody>
</table>
<div class="wpaicg-paginate">
    <?php
    if($totalPage > 1){
        echo paginate_links( array(
            'base'         => admin_url('admin.php?page=wpaicg_image_generator&action=logs&wpage=%#%'),
            'total'        => $totalPage,
            'current'      => $wpaicg_log_page,
            'format'       => '?wpage=%#%',
            'show_all'     => false,
            'prev_next'    => false,
            'add_args'     => false,
        ));
    }
    ?>
</div>
<script>
    jQuery(document).ready(function ($){
    })
</script>
<script>
jQuery(document).ready(function($) {
    $('#delete-all').click(function() {
        if (confirm('Are you sure you want to delete all logs? This action cannot be undone.')) {
            $.ajax({
                url: ajaxurl, // Make sure ajaxurl is defined globally
                type: 'POST',
                data: {
                    action: 'wpaicg_delete_all_image_logs', // The action hook for backend
                    nonce: '<?php echo wp_create_nonce("wpaicg_delete_all_image_logs_nonce"); ?>'
                },
                success: function(response) {
                    alert(response.data.message);
                    if (response.success) {
                        location.reload(); // Reload the page to update the log table
                    }
                }
            });
        }
    });
});
</script>