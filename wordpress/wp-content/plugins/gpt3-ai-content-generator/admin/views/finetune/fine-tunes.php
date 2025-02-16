<?php
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$page_training = isset($_POST['wpage_training']) ? intval($_POST['wpage_training']) : 1;
$posts_per_page_training = 3; // Adjust as needed
$offset_training = ($page_training - 1) * $posts_per_page_training;

// Calculate total number of posts from wpaicg_embeddings
$total_posts_training = $wpdb->get_var("SELECT COUNT(*) FROM " . $wpdb->posts . " f WHERE f.post_type='wpaicg_finetune' AND (f.post_status='publish' OR f.post_status = 'future')");
$total_pages_training = ceil($total_posts_training / $posts_per_page_training);

$posts_trainings = $wpdb->get_results($wpdb->prepare("SELECT f.*
,(SELECT fn.meta_value FROM " . $wpdb->postmeta . " fn WHERE fn.post_id=f.ID AND fn.meta_key='wpaicg_model' LIMIT 1) as model
,(SELECT fp.meta_value FROM " . $wpdb->postmeta . " fp WHERE fp.post_id=f.ID AND fp.meta_key='wpaicg_updated_at' LIMIT 1) as updated_at
,(SELECT fm.meta_value FROM " . $wpdb->postmeta . " fm WHERE fm.post_id=f.ID AND fm.meta_key='wpaicg_name' LIMIT 1) as ft_model
,(SELECT fc.meta_value FROM " . $wpdb->postmeta . " fc WHERE fc.post_id=f.ID AND fc.meta_key='wpaicg_org' LIMIT 1) as org_id
,(SELECT fs.meta_value FROM " . $wpdb->postmeta . " fs WHERE fs.post_id=f.ID AND fs.meta_key='wpaicg_status' LIMIT 1) as ft_status
,(SELECT ft.meta_value FROM " . $wpdb->postmeta . " ft WHERE ft.post_id=f.ID AND ft.meta_key='wpaicg_fine_tune' LIMIT 1) as finetune
,(SELECT fd.meta_value FROM " . $wpdb->postmeta . " fd WHERE fd.post_id=f.ID AND fd.meta_key='wpaicg_deleted' LIMIT 1) as deleted
FROM " . $wpdb->posts . " f WHERE f.post_type='wpaicg_finetune' AND (f.post_status='publish' OR f.post_status = 'future') ORDER BY f.post_date DESC LIMIT %d,%d", $offset_training, $posts_per_page_training));
?>
<style>
    .wpaicg_delete_finetune,.wpaicg_cancel_finetune{
        color: #bb0505;
    }
</style>
<div class="custom-modal-training-overlay" style="display:none;">
    <div class="custom-modal-training-window">
        <div class="custom-modal-training-close">X</div>
        <div class="custom-modal-training-title"></div>
        <div class="custom-modal-training-content"></div>
    </div>
</div>

<p></p>
<div class="nice-form-group">
    <button href="javascript:void(0)" class="button button-secondary wpaicg_sync_finetunes"><?php echo esc_html__('Sync Fine-tunes', 'gpt3-ai-content-generator') ?></button>
</div>
<p></p>
<div class="content-area">
    <input type="hidden" id="ajax_pagination_training_nonce" value="<?php echo wp_create_nonce('ajax_pagination_training_nonce'); ?>">
    <div class="wpaicg-table-responsive">
        <table id="paginated-training-table" class="wp-list-table widefat striped">
            <thead>
            <tr>
                <th class="column-id"><?php echo esc_html__('ID', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-details"><?php echo esc_html__('Details', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-status"><?php echo esc_html__('Status', 'gpt3-ai-content-generator'); ?></th>
                <th class="column-training"><?php echo esc_html__('Training', 'gpt3-ai-content-generator'); ?></th>
            </tr>
            </thead>
            <tbody>
                <?php foreach ($posts_trainings as $posts_training): ?>
                    <?php echo \WPAICG\WPAICG_FineTune::get_instance()->generate_table_row_training($posts_training); ?>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php
    echo \WPAICG\WPAICG_FineTune::get_instance()->generate_smart_pagination_training($page_training, $total_pages_training);
    ?>
    <p></p>
</div>
<script>
    jQuery(document).ready(function ($){
        var wpaicgAjaxRunning = false;
        function wpaicgLoading(btn){
            btn.attr('disabled','disabled');
            if(btn.find('.spinner').length === 0){
                btn.append('<span class="wpaicg-spinner spinner"></span>');
            }
            btn.find('.spinner').css('visibility','unset');
        }
        function wpaicgRmLoading(btn){
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }

        // Function to show the training modal
        function showTrainingModal(title, content) {
            $('.custom-modal-training-title').html(title);
            $('.custom-modal-training-content').html(content);
            $('.custom-modal-training-overlay').show();
        }

        // Close the training modal on clicking the close button
        $(document).on('click', '.custom-modal-training-close', function() {
            $('.custom-modal-training-overlay').hide();
        });
        
        var wpaicg_get_other = $('.wpaicg_get_other');
        var wpaicg_get_finetune = $('.wpaicg_get_finetune');
        var wpaicg_cancel_finetune = $('.wpaicg_cancel_finetune');
        var wpaicg_delete_finetune = $('.wpaicg_delete_finetune');
        var wpaicg_ajax_url = '<?php echo admin_url('admin-ajax.php') ?>';
        
        $(document).on('click', '.wpaicg_cancel_finetune', function () {
            var conf = confirm('<?php echo esc_html__('Are you sure?', 'gpt3-ai-content-generator') ?>');
            if(conf) {
                var btn = $(this);
                var id = btn.attr('data-id');
                if (!wpaicgAjaxRunning) {
                    wpaicgAjaxRunning = true;
                    $.ajax({
                        url: wpaicg_ajax_url,
                        data: {action: 'wpaicg_cancel_finetune', id: id,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'},
                        dataType: 'JSON',
                        type: 'POST',
                        beforeSend: function () {
                            wpaicgLoading(btn);
                        },
                        success: function (res) {
                            wpaicgRmLoading(btn);
                            wpaicgAjaxRunning = false;
                            if (res.status === 'success') {
                                window.location.reload();
                            } else {
                                alert(res.msg);
                            }
                        },
                        error: function () {
                            wpaicgRmLoading(btn);
                            wpaicgAjaxRunning = false;
                            alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                        }
                    })
                }
            }
        });

        $(document).on('click', '.wpaicg_delete_finetune', function () {
            var conf = confirm('<?php echo esc_html__('Are you sure?', 'gpt3-ai-content-generator') ?>');
            if(conf) {
                var btn = $(this);
                var id = btn.attr('data-id');
                if (!wpaicgAjaxRunning) {
                    wpaicgAjaxRunning = true;
                    $.ajax({
                        url: wpaicg_ajax_url,
                        data: {action: 'wpaicg_delete_finetune', id: id,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'},
                        dataType: 'JSON',
                        type: 'POST',
                        beforeSend: function () {
                            wpaicgLoading(btn);
                        },
                        success: function (res) {
                            wpaicgRmLoading(btn);
                            wpaicgAjaxRunning = false;
                            if (res.status === 'success') {
                                window.location.reload();
                            } else {
                                alert(res.msg);
                            }
                        },
                        error: function () {
                            wpaicgRmLoading(btn);
                            wpaicgAjaxRunning = false;
                            alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                        }
                    })
                }
            }
        });
        $(document).on('click', '.wpaicg_get_other', function () {
            var btn = $(this);
            var id = btn.attr('data-id');
            var type = btn.attr('data-type');
            var wpaicgTitle = btn.text().trim();
            if(!wpaicgAjaxRunning){
                wpaicgAjaxRunning = true;
                $.ajax({
                    url: wpaicg_ajax_url,
                    data: {action: 'wpaicg_other_finetune', id: id, type: type,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function (){
                        wpaicgLoading(btn);
                    },
                    success: function (res){
                        wpaicgRmLoading(btn);
                        wpaicgAjaxRunning = false;
                        if(res.status === 'success'){
                            showTrainingModal(wpaicgTitle, res.html);
                        }
                        else{
                            alert(res.msg);
                        }
                    },
                    error: function (){
                        wpaicgRmLoading(btn);
                        wpaicgAjaxRunning = false;
                        alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                    }
                })
            }
        })
        $('.wpaicg_sync_finetunes').click(function (){
            var btn = $(this);
            $.ajax({
                url: wpaicg_ajax_url,
                data: {action: 'wpaicg_fetch_finetunes','nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'},
                dataType: 'JSON',
                type: 'POST',
                beforeSend: function (){
                    wpaicgLoading(btn);
                },
                success: function (res){
                    wpaicgRmLoading(btn);
                    if(res.status === 'success'){
                        $('#wpaicg-finetune-sync-message').show();
                        setTimeout(function() {
                            $('#wpaicg-finetune-sync-message').hide();
                        }, 5000);
                        window.location.reload();
                    }
                    else{
                        alert(res.msg);
                    }
                },
                error: function (){
                    wpaicgRmLoading(btn);
                    alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                }
            })
        })

        // Handle pagination link clicks
        $(document).on('click', '.training-pagination a', function(e){
            e.preventDefault();
            var page = $(this).data('page_training');
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#ajax_pagination_training_nonce').val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'ajax_pagination_training',
                    wpage_training: page,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-training-table tbody').html(response.data.content);
                        $('.training-pagination').replaceWith(response.data.pagination);
                    }
                }
            });
        });
    })
</script>
