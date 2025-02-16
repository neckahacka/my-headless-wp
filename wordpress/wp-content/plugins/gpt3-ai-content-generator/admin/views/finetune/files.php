<?php
if ( ! defined( 'ABSPATH' ) ) exit;
global $wpdb;

// Define posts per page
$posts_per_page_finetune = 3;

// Ensure the page number is at least 1
$page_finetune = isset($_GET['page_finetune']) ? max(1, (int) $_GET['page_finetune']) : 1;

// Calculate the offset
$offset_finetune = ($page_finetune - 1) * $posts_per_page_finetune;

// Retrieve the embeddings, ensuring a valid, non-negative offset from wpaicg_embeddings and wpaicg_pdfadmin
global $wpdb;
$posts_finetune = $wpdb->get_results($wpdb->prepare("SELECT f.*
,(SELECT fn.meta_value FROM ".$wpdb->postmeta." fn WHERE fn.post_id=f.ID AND fn.meta_key='wpaicg_filename') as filename 
,(SELECT fp.meta_value FROM ".$wpdb->postmeta." fp WHERE fp.post_id=f.ID AND fp.meta_key='wpaicg_purpose') as purpose 
,(SELECT fm.meta_value FROM ".$wpdb->postmeta." fm WHERE fm.post_id=f.ID AND fm.meta_key='wpaicg_purpose') as model 
,(SELECT fc.meta_value FROM ".$wpdb->postmeta." fc WHERE fc.post_id=f.ID AND fc.meta_key='wpaicg_custom_name') as custom_name 
,(SELECT fs.meta_value FROM ".$wpdb->postmeta." fs WHERE fs.post_id=f.ID AND fs.meta_key='wpaicg_file_size') as file_size 
,(SELECT ft.meta_value FROM ".$wpdb->postmeta." ft WHERE ft.post_id=f.ID AND ft.meta_key='wpaicg_fine_tune') as finetune 
FROM ".$wpdb->posts." f WHERE f.post_type='wpaicg_file' AND (f.post_status='publish' OR f.post_status = 'future') ORDER BY f.post_date DESC LIMIT %d, %d", $offset_finetune, $posts_per_page_finetune));

$total_posts_finetune = $wpdb->get_var("SELECT COUNT(*) FROM ".$wpdb->posts." f WHERE f.post_type='wpaicg_file' AND (f.post_status='publish' OR f.post_status = 'future')");
$total_pages_finetune = ceil($total_posts_finetune / $posts_per_page_finetune);

// Nonce for AJAX requests
$nonce = wp_create_nonce('ajax_pagination_finetune_nonce');

$fileTypes = array(
    'fine-tune' => esc_html__('Fine-Tune','gpt3-ai-content-generator'),
    'fine-tune-results' => esc_html__('Fine-Tune Results','gpt3-ai-content-generator'),
    'assistants' => esc_html__('Assistants','gpt3-ai-content-generator'),
    'assistants_output' => esc_html__('Assistants Output','gpt3-ai-content-generator')
);
?>

<?php
$wpaicgMaxFileSize = wp_max_upload_size();
if($wpaicgMaxFileSize > 104857600){
    $wpaicgMaxFileSize = 104857600;
}
?>
<div class="custom-modal-finetune-overlay">
    <div class="custom-modal-finetune-window">
        <div class="custom-modal-finetune-close">X</div>
        <div class="custom-modal-finetune-title"></div>
        <div class="custom-modal-finetune-content"></div>
    </div>
</div>
<div class="custom-modal-create-finetune-overlay">
    <div class="custom-modal-create-finetune-window">
        <div class="custom-modal-create-finetune-close">X</div>
        <div class="custom-modal-create-finetune-title"><?php echo esc_html__('Choose Model', 'gpt3-ai-content-generator')?></div>
        <div class="custom-modal-create-finetune-content"></div>
    </div>
</div>
<p></p>
<!-- Tab links -->
<div class="wpaicg_finetune_tab">
  <button class="wpaicg_finetune_tablinks" onclick="openTab(event, 'files')" id="defaultOpen">Files</button>
  <button class="wpaicg_finetune_tablinks" onclick="openTab(event, 'trainings')">Fine-tunes</button>
</div>
<!-- Tab content for Files -->
<div id="files" class="wpaicg_finetune_tabcontent">
    <div class="nice-form-group">
        <button href="javascript:void(0)" class="button button-secondary wpaicg_sync_files"><?php echo esc_html__('Sync Files','gpt3-ai-content-generator')?></button>
    </div>
    <p></p>
    <div class="content-area">
        <input type="hidden" id="ajax_pagination_finetune_nonce" value="<?php echo wp_create_nonce('ajax_pagination_finetune_nonce'); ?>">
        <div class="wpaicg-table-responsive">
            <table id="paginated-finetune-table" class="wp-list-table widefat striped">
                <thead>
                <tr>
                    <th class="column-id"><?php echo esc_html__('ID', 'gpt3-ai-content-generator'); ?></th>
                    <th class="column-size"><?php echo esc_html__('Size', 'gpt3-ai-content-generator'); ?></th>
                    <th class="column-created"><?php echo esc_html__('Created', 'gpt3-ai-content-generator'); ?></th>
                    <th class="column-filename"><?php echo esc_html__('Filename', 'gpt3-ai-content-generator'); ?></th>
                    <th class="column-purpose"><?php echo esc_html__('Purpose', 'gpt3-ai-content-generator'); ?></th>
                    <th class="column-action"><?php echo esc_html__('Action', 'gpt3-ai-content-generator'); ?></th>
                </tr>
                </thead>
                <tbody>
                    <?php foreach ($posts_finetune as $post_finetune): ?>
                        <?php echo \WPAICG\WPAICG_FineTune::get_instance()->generate_table_row_files($post_finetune); ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php
        echo \WPAICG\WPAICG_FineTune::get_instance()->generate_smart_pagination_finetune($page_finetune, $total_pages_finetune);
        ?>
        <p></p>
    </div>
</div>
<!-- Tab content for Trainings -->
<div id="trainings" class="wpaicg_finetune_tabcontent">
    <?php
        include WPAICG_PLUGIN_DIR.'admin/views/finetune/fine-tunes.php';
        ?>
</div>
<script>
    jQuery(document).ready(function ($){
        function wpaicgLoading(btn){
            btn.attr('disabled','disabled');
            if(!btn.find('spinner').length){
                btn.append('<span class="spinner"></span>');
            }
            btn.find('.spinner').css('visibility','unset');
        }
        function wpaicgRmLoading(btn){
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }
        var wpaicg_max_file_size = <?php echo esc_html($wpaicgMaxFileSize)?>;
        var wpaicg_max_size_in_mb = '<?php echo size_format(esc_html($wpaicgMaxFileSize))?>';
        var wpaicg_file_button = $('#wpaicg_file_button');
        var wpaicg_file_upload = $('#wpaicg_file_upload');
        var wpaicg_file_purpose = $('#wpaicg_file_purpose');
        var wpaicg_file_name = $('#wpaicg_file_name');
        var wpaicg_file_model = $('#wpaicg_file_model');
        var wpaicg_progress = $('.wpaicg_progress');
        var wpaicg_error_message = $('.wpaicg-error-msg');
        var wpaicg_ajax_url = '<?php echo admin_url('admin-ajax.php')?>';
        var wpaicgAjaxRunning = false;
        $('.wpaicg_sync_files').click(function (){
            var btn = $(this);
            if(!wpaicgAjaxRunning) {
                $.ajax({
                    url: wpaicg_ajax_url,
                    data: {action: 'wpaicg_fetch_finetune_files','nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        wpaicgAjaxRunning = true;
                        wpaicgLoading(btn);
                    },
                    success: function (res) {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        if (res.status === 'success') {
                            $('#wpaicg-finetune-sync-message').show();
                            setTimeout(function() {
                                $('#wpaicg-finetune-sync-message').hide();
                            }, 5000);
                            location.reload();
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function () {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                    }
                })
            }
        })
        $(document).on('click', '.wpaicg_delete_file', function () {
            if(!wpaicgAjaxRunning) {
                var conf_finetune = confirm('<?php echo esc_html__('Are you sure?','gpt3-ai-content-generator')?>');
                if (conf_finetune) {
                    var btn = $(this);
                    var id = btn.attr('data-id');
                    $.ajax({
                        url: wpaicg_ajax_url,
                        data: {action: 'wpaicg_delete_finetune_file', id: id,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                        dataType: 'JSON',
                        type: 'POST',
                        beforeSend: function () {
                            wpaicgAjaxRunning = true;
                            wpaicgLoading(btn);
                        },
                        success: function (res) {
                            wpaicgAjaxRunning = false;
                            wpaicgRmLoading(btn);
                            if (res.status === 'success') {
                                // display success message with timeout. wpaicg-finetune-success-message
                                $('#wpaicg-finetune-delete-message').show();
                                setTimeout(function() {
                                    $('#wpaicg-finetune-delete-message').hide();
                                }, 5000);
                                // delete row with fade out effect
                                btn.closest('tr').fadeOut();
                            } else {
                                alert(res.msg);
                            }
                        },
                        error: function () {
                            wpaicgAjaxRunning = false;
                            wpaicgRmLoading(btn);
                            alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                        }
                    })
                }
                else{
                    wpaicgAjaxRunning = false;
                }
            }
        });
        $(document).on('click','#wpaicg_create_finetune_btn', function (e){
            if(!wpaicgAjaxRunning) {
                var btn = $(e.currentTarget);
                var id = $('#wpaicg_create_finetune_id').val();
                var model = $('#wpaicg_create_finetune_model').val();
                $.ajax({
                    url: wpaicg_ajax_url,
                    data: {action: 'wpaicg_create_finetune', id: id, model: model,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        wpaicgAjaxRunning = true;
                        wpaicgLoading(btn);
                    },
                    success: function (res) {
                        wpaicgRmLoading(btn);
                        wpaicgAjaxRunning = false;
                        if (res.status === 'success') {
                            // Use the custom modal prefix for displaying success message
                            $('.custom-modal-create-finetune-content').html('<?php echo esc_html__('Congratulations! Your fine-tuning was created successfully. You can track its progress in the "Trainings" tab.', 'gpt3-ai-content-generator')?>');
                            $('.custom-modal-create-finetune-overlay').show();

                            // Close modal functionality
                            $('.custom-modal-create-finetune-close').on('click', function () {
                                $('.custom-modal-create-finetune-overlay').hide();
                            });
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function () {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                    }
                });
            }
        });
        $(document).on('click', '.wpaicg_create_fine_tune', function () {
            if(!wpaicgAjaxRunning) {
                var btn = $(this);
                var id = btn.attr('data-id');
                $.ajax({
                    url: wpaicg_ajax_url,
                    data: {action: 'wpaicg_create_finetune_modal','nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        wpaicgAjaxRunning = true;
                        wpaicgLoading(btn);
                    },
                    success: function (res) {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        if (res.status === 'success') {
                            // Dynamically creating the modal content based on AJAX response
                            var content = '<input type="hidden" id="wpaicg_create_finetune_id" value="' + id + '">';
                            content += '<div class="nice-form-group"><select id="wpaicg_create_finetune_model">';
                            content += '<option value=""><?php echo esc_html__('New Model', 'gpt3-ai-content-generator')?></option>';
                            $.each(res.data, function (idx, item) {
                                content += '<option value="' + item + '">' + item + '</option>';
                            });
                            content += '</select></div>';
                            content += '<p><button class="button button-primary" id="wpaicg_create_finetune_btn"><?php echo esc_html__('Create', 'gpt3-ai-content-generator')?></button></p>';
                            $('.custom-modal-create-finetune-content').html(content);

                            // Show the modal
                            $('.custom-modal-create-finetune-overlay').show();

                            // Close modal functionality
                            $('.custom-modal-create-finetune-close').on('click', function () {
                                $('.custom-modal-create-finetune-overlay').hide();
                            });
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function () {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                    }
                })
            }
        });
        $(document).on('click', '.wpaicg_retrieve_content', function () {
            if(!wpaicgAjaxRunning) {
                var btn = $(this);
                var id = btn.attr('data-id');
                $.ajax({
                    url: wpaicg_ajax_url,
                    data: {action: 'wpaicg_get_finetune_file', id: id,'nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                    dataType: 'JSON',
                    type: 'POST',
                    beforeSend: function () {
                        wpaicgAjaxRunning = true;
                        wpaicgLoading(btn);
                    },
                    success: function (res) {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        if (res.status === 'success') {
                            var content = res.data;
                            content = content.replace(/\n/g, "<br />");
                            $('.custom-modal-finetune-title').html('Finetune Data');
                            $('.custom-modal-finetune-content').html(content);

                            // Show the modal
                            $('.custom-modal-finetune-overlay').show();

                            // Close the modal when clicking the close button
                            $('.custom-modal-finetune-close').on('click', function() {
                                $('.custom-modal-finetune-overlay').hide();
                            });
                            
                        } else {
                            alert(res.msg);
                        }
                    },
                    error: function () {
                        wpaicgAjaxRunning = false;
                        wpaicgRmLoading(btn);
                        alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                    }
                })
            }
        });

        // Handle pagination link clicks
        $(document).on('click', '.finetune-pagination a', function(e){
            e.preventDefault();
            var page = $(this).data('page_finetune');
            var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
            var nonce = $('#ajax_pagination_finetune_nonce').val();

            $.ajax({
                url: ajaxurl,
                type: 'post',
                data: {
                    action: 'ajax_pagination_finetune',
                    wpage_finetune: page,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#paginated-finetune-table tbody').html(response.data.content);
                        $('.finetune-pagination').replaceWith(response.data.pagination);
                    }
                }
            });
        });

    })
</script>
<script>
function openTab(evt, tabName) {
  var i, tabcontent, tablinks;
  tabcontent = document.getElementsByClassName("wpaicg_finetune_tabcontent");
  for (i = 0; i < tabcontent.length; i++) {
    tabcontent[i].style.display = "none";
  }
  tablinks = document.getElementsByClassName("wpaicg_finetune_tablinks");
  for (i = 0; i < tablinks.length; i++) {
    tablinks[i].className = tablinks[i].className.replace(" active", "");
  }
  document.getElementById(tabName).style.display = "block";
  evt.currentTarget.className += " active";
}

// Default open tab
document.getElementById("defaultOpen").click();
</script>