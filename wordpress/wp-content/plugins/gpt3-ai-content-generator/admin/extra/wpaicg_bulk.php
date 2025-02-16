<?php
if ( ! defined( 'ABSPATH' ) ) exit;
$wpaicg_all_categories = get_terms(array(
    'taxonomy' => 'category',
    'hide_empty' => false
));
// Fetch only 'administrator' and 'editor' users once
$wpaicg_users = get_users(array(
    'role__in' => array('administrator', 'editor', 'author', 'contributor'),
    'fields' => array('ID', 'display_name')
));
$wpaicg_track_id = isset($_GET['wpaicg_track']) && !empty($_GET['wpaicg_track']) ? sanitize_text_field($_GET['wpaicg_track']) : false;
$wpaicg_bulk_action = isset($_GET['wpaicg_action']) && !empty($_GET['wpaicg_action']) ? sanitize_text_field($_GET['wpaicg_action']) : false;
$wpaicg_track = false;
if($wpaicg_track_id){
    $wpaicg_track = get_post($wpaicg_track_id);
}
$wpaicg_cron_job_last_time = get_option('_wpaicg_crojob_bulk_last_time','');
$wpaicg_cron_job_confirm = get_option('_wpaicg_crojob_bulk_confirm','');
$wpaicg_number_title = 5;
?>

<div class="content-writer-master">
  <div class="content-writer-master-navigation">
    <nav>
      <ul>
        <li>
          <a href="#autogpt">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-tool">
              <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
            </svg>
            <?php echo esc_html__('AutoGPT','gpt3-ai-content-generator')?>
            </a>
        </li>
        <li>
          <a href="#queue">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers">
              <polygon points="12 2 2 7 12 12 22 7 12 2" />
              <polyline points="2 17 12 22 22 17" />
              <polyline points="2 12 12 17 22 12" />
            </svg>
            <?php echo esc_html__('Queue','gpt3-ai-content-generator')?>
        </a>
        </li>
        <li>
          <a href="#settings">
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-square">
              <polyline points="9 11 12 14 22 4" />
              <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
            <?php echo esc_html__('Settings','gpt3-ai-content-generator')?>
            </a>
        </li>
      </ul>
    </nav>
  </div>
  <main class="content-writer-master-content">
    <!-- AutoGPT -->
    <section>
        <div class="href-target" id="autogpt"></div>
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-tool">
            <path d="M14.7 6.3a1 1 0 0 0 0 1.4l1.6 1.6a1 1 0 0 0 1.4 0l3.77-3.77a6 6 0 0 1-7.94 7.94l-6.91 6.91a2.12 2.12 0 0 1-3-3l6.91-6.91a6 6 0 0 1 7.94-7.94l-3.76 3.76z" />
            </svg>
            <?php echo esc_html__('AutoGPT','gpt3-ai-content-generator')?>
        </h1>
        <div id="wpaicg-bulk-editor-success-message" class="wpaicg-bulk-editor-success-message" style="display: none;"></div>
        <div id="wpaicg-csv-success-message" class="wpaicg-csv-success-message"></div>
        <div id="wpaicg-copy-success-message" class="wpaicg-copy-success-message"></div>
        <div class="wpaicg_sheets_success_msg"></div>
        <!-- Data Source Selector -->
        <div class="nice-form-group">
            <label for="data-source-selector"><?php echo esc_html__('Data Source','gpt3-ai-content-generator')?></label>
            <select id="data-source-selector" class="data-source-select">
                <option value="copy-paste"><?php echo esc_html__('Copy & Paste','gpt3-ai-content-generator')?></option>
                <option value="csv"><?php echo esc_html__('CSV','gpt3-ai-content-generator')?></option>
                <option value="bulk-editor"><?php echo esc_html__('Bulk Editor','gpt3-ai-content-generator')?></option>
                <option value="sheets"><?php echo esc_html__('Google Sheets','gpt3-ai-content-generator')?></option>
                <option value="rss"><?php echo esc_html__('RSS','gpt3-ai-content-generator')?></option>
                <option value="twitter"><?php echo esc_html__('Twitter','gpt3-ai-content-generator')?></option>
            </select>
        </div>
        <!-- Bulk Editor -->
        <form action="" method="post" class="wpaicg-form-bulk">
        <?php wp_nonce_field('wpaicg_bulk_save'); ?>
            <div id="bulk-editor-container" class="data-source-container" style="display: none;">
                <div class="nice-form-group">
                    <input type="hidden" name="action" value="wpaicg_bulk_save_editor">
                    <div style="overflow-x:auto;">
                        <table class="wp-list-table widefat striped posts">
                            <thead>
                                <tr>
                                    <th><?php echo esc_html__('#','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Title','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Category','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Author','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Schedule','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Keywords to Include','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Keywords to Avoid','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Tags','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Anchor Text','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('Target URL','gpt3-ai-content-generator')?></th>
                                    <th><?php echo esc_html__('CTA','gpt3-ai-content-generator')?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php for ($i = 0; $i < 5; $i++): ?>
                                <tr>
                                    <td><?php echo esc_html($i+1); ?></td>
                                    <td><input style="width: 300px;" type="text" id="wpaicg_bulk_title" name="bulk[<?php echo esc_html($i); ?>][title]"></td>
                                    <td>
                                        <select name="bulk[<?php echo esc_html($i); ?>][category]">
                                            <?php
                                            foreach($wpaicg_all_categories as $wpaicg_all_category){
                                                echo '<option value="'.esc_html($wpaicg_all_category->term_id).'">'.esc_html($wpaicg_all_category->name).'</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td>
                                        <select name="bulk[<?php echo esc_html($i); ?>][author]">
                                            <?php
                                            foreach($wpaicg_users as $user){
                                                echo '<option'.($user->ID == get_current_user_id() ? ' selected':'').' value="'.esc_html($user->ID).'">'.esc_html($user->display_name).'</option>';
                                            }
                                            ?>
                                        </select>
                                    </td>
                                    <td><input <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? '' :' disabled placeholder="'.esc_html__('Schedule (Pro)','gpt3-ai-content-generator').'"'?> type="text" placeholder="Schedule" class="wpaicg-item-schedule" name="bulk[<?php echo esc_html($i);?>][schedule]"></td>
                                    <td><input <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? '' :' disabled placeholder="'.esc_html__('Keywords to Include (Pro)','gpt3-ai-content-generator').'"'?> type="text" placeholder="Keywords to Include" name="bulk[<?php echo esc_html($i);?>][keywords]"></td>
                                    <td><input <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? '' :' disabled placeholder="'.esc_html__('Keywords to Avoid (Pro)','gpt3-ai-content-generator').'"'?> type="text" placeholder="Keywords to Avoids" name="bulk[<?php echo esc_html($i);?>][avoid]"></td>
                                    <td><input type="text" placeholder="Tags" name="bulk[<?php echo esc_html($i);?>][tags]"></td>
                                    <td><input type="text" placeholder="Anchor Text" name="bulk[<?php echo esc_html($i);?>][anchor]"></td>
                                    <td><input type="text" placeholder="Target URL" name="bulk[<?php echo esc_html($i);?>][target]"></td>
                                    <td><input type="text" placeholder="CTA" name="bulk[<?php echo esc_html($i);?>][cta]"></td>
                                </tr>
                                <?php endfor; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="nice-form-group" style="display: flex;">
                        <button type="button" id="addMore" class="button">Add</button>
                        <select id="rowsToAdd" style="width: 100px;">
                            <option value="1">1</option>
                            <option value="10">10</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>
                    <div class="nice-form-group">
                        <input checked type="radio" name="post_status" value="draft">
                        <label>Draft</label>
                    </div>
                    <div class="nice-form-group">
                        <input type="radio" name="post_status" value="publish">
                        <label>Publish</label>
                    </div>
                    <div class="nice-form-group">
                        <button class="button button-primary wpaicg-bulk-button">
                                <?php echo esc_html__('Generate','gpt3-ai-content-generator')?>
                        </button>
                    </div>
                </div>
            </div>
        </form>
        <!-- CSV -->
        <div id="csv-container" class="data-source-container" style="display: none;">
            <div class="nice-form-group">
                <small>Please make sure your CSV delimiter is <b>comma</b>. Sample CSV file <a href="https://docs.google.com/spreadsheets/d/12wd8nuzjHOTuRi3zQyyx-3NNcSeDKEg24RyJLPUWmI8/edit?usp=sharing" target="_blank">here</a>.</small>
            </div>
            <p></p>
            <div class="nice-form-group">
                <input accept="text/csv" type="file" class="wpaicg-csv-file">
            </div>
            <div class="nice-form-group">
                <label><?php echo esc_html__('Category','gpt3-ai-content-generator')?></label>
                <select name="post_category" class="csv-category-select">
                    <option value=""><?php echo esc_html__('None','gpt3-ai-content-generator')?></option>
                    <?php
                    foreach($wpaicg_all_categories as $wpaicg_all_category){
                        echo '<option value="'.esc_html($wpaicg_all_category->term_id).'">'.esc_html($wpaicg_all_category->name).'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="nice-form-group">
                <label><?php echo esc_html__('Author','gpt3-ai-content-generator')?></label>
                <select name="post_author" class="csv-author-select">
                    <?php
                    foreach($wpaicg_users as $user){
                        echo '<option'.($user->ID == get_current_user_id() ? ' selected':'').' value="'.esc_html($user->ID).'">'.esc_html($user->display_name).'</option>';
                    }
                    ?>
                </select>
            </div>
            <fieldset class="nice-form-group">
                <legend><?php echo esc_html__('Status','gpt3-ai-content-generator')?></legend>
                <div class="nice-form-group">
                    <input type="radio" name="post_status_csv" value="draft" class="wpaicg-csv-status" checked />
                    <label><?php echo esc_html__('Draft', 'gpt3-ai-content-generator'); ?></label>
                </div>

                <div class="nice-form-group">
                    <input type="radio" value="publish" name="post_status_csv" class="wpaicg-csv-status" />
                    <label><?php echo esc_html__('Publish', 'gpt3-ai-content-generator'); ?></label>
                </div>
            </fieldset>
            <div class="nice-form-group">
                <button class="button button-primary wpaicg-import-csv-button">
                    <?php echo esc_html__('Import','gpt3-ai-content-generator')?>
                </button>
            </div>
        </div>
        <!-- Copy-Paste -->
        <div id="copy-paste-container" class="data-source-container">
            <div class="nice-form-group">
                <label><?php echo esc_html__('Title','gpt3-ai-content-generator')?></label>
                <small><?php echo esc_html__('Enter one title per line','gpt3-ai-content-generator')?></small>
                <textarea rows="15" class="wpaicg-multi-line"></textarea>
            </div>
            <div class="nice-form-group">
                <label><?php echo esc_html__('Category','gpt3-ai-content-generator')?></label>
                <select name="post_category_copy_paste" class="copy-paste-category-select">
                    <option value=""><?php echo esc_html__('None','gpt3-ai-content-generator')?></option>
                    <?php
                    foreach($wpaicg_all_categories as $wpaicg_all_category){
                        echo '<option value="'.esc_html($wpaicg_all_category->term_id).'">'.esc_html($wpaicg_all_category->name).'</option>';
                    }
                    ?>
                </select>
            </div>
            <div class="nice-form-group">
                <label><?php echo esc_html__('Author','gpt3-ai-content-generator')?></label>
                <select name="post_author_copy_paste" class="copy-paste-category-select">
                    <?php
                    foreach($wpaicg_users as $user){
                        echo '<option'.($user->ID == get_current_user_id() ? ' selected':'').' value="'.esc_html($user->ID).'">'.esc_html($user->display_name).'</option>';
                    }
                    ?>
                </select>
            </div>
            <fieldset class="nice-form-group">
                <legend><?php echo esc_html__('Status','gpt3-ai-content-generator')?></legend>
                <div class="nice-form-group">
                    <input type="radio" name="post_status" id="status-draft" value="draft" class="wpaicg-post-status" checked />
                    <label for="status-draft"><?php echo esc_html__('Draft', 'gpt3-ai-content-generator'); ?></label>
                </div>

                <div class="nice-form-group">
                    <input type="radio" name="post_status" id="status-publish" value="publish" class="wpaicg-post-status" />
                    <label for="status-publish"><?php echo esc_html__('Publish', 'gpt3-ai-content-generator'); ?></label>
                </div>
                
                <!-- Optionally, include a message area -->
                <p class="wpaicg-ajax-message"></p>
            </fieldset>

            <div class="nice-form-group">
                <button class="button button-primary wpaicg-multi-button"><?php echo esc_html__('Generate','gpt3-ai-content-generator')?></button>
            </div>
        </div>
        <!-- Google -->
        <div id="google-sheets-container" class="data-source-container" style="display: none;">
            <div class="nice-form-group">
                <?php
                if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()){
                    include WPAICG_PLUGIN_DIR.'lib/views/google-sheets/setting.php';
                } else {
                    echo '<a href="'.esc_url(admin_url('admin.php?page=wpaicg-pricing')).'"><img src="'.esc_url(WPAICG_PLUGIN_URL).'admin/images/google-sheet.png" width="100%"></a>';
                }
                ?>
            </div>
        </div>
        <!-- RSS -->
        <div id="rss-container" class="data-source-container" style="display: none;">
            <div class="nice-form-group">
                <?php
                if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                    include WPAICG_PLUGIN_DIR.'lib/views/rss/wpaicg_rss.php';
                }
                else{
                    echo '<a href="'.esc_url(admin_url('admin.php?page=wpaicg-pricing')).'"><img src="'.esc_url(WPAICG_PLUGIN_URL).'admin/images/compress_pro.png" width="100%"></a>';
                }
                ?>
            </div>
        </div>
        <!-- TWITTER -->
        <div id="twitter-container" class="data-source-container" style="display: none;">
            <div class="nice-form-group">
                <?php
                if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()){
                    include WPAICG_PLUGIN_DIR.'lib/views/twitter/index.php';
                }
                else{
                    echo '<a href="'.esc_url(admin_url('admin.php?page=wpaicg-pricing')).'"><img src="'.esc_url(WPAICG_PLUGIN_URL).'admin/images/twitter.png" width="100%"></a>';
                }
                ?>
            </div>
        </div>
    </section>
    <!-- Queue -->
    <section>
        <div class="href-target" id="queue"></div>
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-layers">
            <polygon points="12 2 2 7 12 12 22 7 12 2" />
            <polyline points="2 17 12 22 22 17" />
            <polyline points="2 12 12 17 22 12" />
            </svg>
            <?php echo esc_html__('Queue','gpt3-ai-content-generator')?>
        </h1>
        <div class="nice-form-group">
            <?php
            include __DIR__.'/wpaicg_bulk_queue.php';
            ?>
        </div>
    </section>

    <!-- Settings -->
    <section>
        <div class="href-target" id="settings"></div>
        <h1>
            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-check-square">
            <polyline points="9 11 12 14 22 4" />
            <path d="M21 12v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11" />
            </svg>
            <?php echo esc_html__('Settings','gpt3-ai-content-generator')?>
        </h1>
        <div class="nice-form-group">
        <?php
        include WPAICG_PLUGIN_DIR.'admin/extra/wpaicg_bulk_setting.php';
        ?>
        </div>
    </section>
  </main>
</div>
<script>
    // Right navigation menu
    document.addEventListener("DOMContentLoaded", function () {
    // Get all left navigation links
    var leftNavLinks = document.querySelectorAll('.content-writer-master-navigation a');

    // Function to hide all right navigation menus
    function hideAllRightNavs() {
        document.querySelectorAll('.content-writer-right-navigation').forEach(function (nav) {
        nav.style.display = 'none';
        });
    }

    // Initialize by hiding all right navs and showing the first one
    hideAllRightNavs();

    // Add click event to all left navigation links
    leftNavLinks.forEach(function (link) {
        link.addEventListener('click', function (e) {
        e.preventDefault();
        var targetId = this.getAttribute('href').replace('#', '');
        // Hide all right navs
        hideAllRightNavs();
        });
    });
    });

</script>

<script>
    // Tab navigation
    document.addEventListener("DOMContentLoaded", function () {

        // 1. TAB NAVIGATION
        const tabs = document.querySelectorAll('.content-writer-master-navigation ul li a');
        const contentSections = document.querySelectorAll('.content-writer-master-content section');

        // Initially hide all sections (This part is handled by CSS now, you can choose to keep or remove these lines)
        contentSections.forEach((section, index) => {
            section.style.display = 'none';
        });

        // Explicitly show the first tab content and set the first tab as active
        if (contentSections.length > 0) {
            contentSections[0].style.display = 'block';
        }
        if (tabs.length > 0) {
            tabs[0].parentElement.classList.add('active');
        }

        // Tab click event
        tabs.forEach(tab => {
            tab.addEventListener('click', function (e) {
                e.preventDefault();

                const targetId = this.getAttribute('href').replace('#', '');
                const targetContent = document.getElementById(targetId);

                contentSections.forEach(section => {
                    section.style.display = 'none';
                });

                targetContent.parentElement.style.display = 'block';

                tabs.forEach(t => {
                    t.parentElement.classList.remove('active');
                });
                this.parentElement.classList.add('active');
            });
        });
    });
</script>
<script>
    jQuery(document).ready(function ($){
        if($('.wpaicg-item-schedule').length) {
            $('.wpaicg-item-schedule').datetimepicker({
                format: 'Y-m-d H:i',
                startDate: new Date()
            })
        }
        var wpaicg_number_title = <?php echo esc_html($wpaicg_number_title)?>;
        $('.wpaicg-form-bulk').on('submit', function (e) {
            e.preventDefault();
            var wpaicg_button = $('.wpaicg-bulk-button');
            var wpaicg_form = $(this);

            var hasTitle = false; // Flag to check if at least one title is entered

            // Iterate through each title input to check if any has been filled
            $('input[name^="bulk["][name$="[title]"]').each(function () {
                if ($(this).val().trim() !== '') {
                    hasTitle = true;
                    return false; // Break the loop if a title is found
                }
            });

            if (!hasTitle) {
                alert('<?php echo esc_html__('Please enter at least one title', 'gpt3-ai-content-generator') ?>');
                return false; // Stop form submission
            }
            else{
                var wpaicg_data = wpaicg_form.serialize();
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php')?>',
                    data: wpaicg_data,
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function (){
                        wpaicg_button.attr('disabled','disabled');
                    },
                    success: function (res){
                        wpaicg_button.removeAttr('disabled');
                        if(res.status === 'success'){
                            $('#wpaicg-bulk-editor-success-message').html('<strong>Success:</strong> Your content has been successfully queued! Go to Queue tab to view.').show();
                            // set timeout to hide the message
                            setTimeout(function (){
                                $('#wpaicg-bulk-editor-success-message').hide();
                            },5000);
                        }
                        else{
                            alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                        }
                    },
                    error: function (){
                        wpaicg_button.removeAttr('disabled');
                        alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                    }
                })
            }
            return false;
        })
    })
</script>
<script>
    document.getElementById('data-source-selector').addEventListener('change', function() {
        // Hide all containers
        document.querySelectorAll('.data-source-container').forEach(function(container) {
            container.style.display = 'none';
        });
        
        // Show the selected container
        var selectedValue = this.value;
        if (selectedValue === 'bulk-editor') {
            document.getElementById('bulk-editor-container').style.display = 'block';
        } else if (selectedValue === 'csv') {
            document.getElementById('csv-container').style.display = 'block';
        } else if (selectedValue === 'copy-paste') {
            document.getElementById('copy-paste-container').style.display = 'block';
        } else if (selectedValue === 'sheets') {
            document.getElementById('google-sheets-container').style.display = 'block';
        } else if (selectedValue === 'rss') {
            document.getElementById('rss-container').style.display = 'block';
        } else if (selectedValue === 'twitter') {
            document.getElementById('twitter-container').style.display = 'block';
        }
    });
</script>
<script>
    var isPro = <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? 'true' : 'false'; ?>;

    document.getElementById('addMore').addEventListener('click', function() {
        if (!isPro) {
            var messageDiv = document.getElementById('wpaicg-bulk-editor-success-message');
            messageDiv.style.display = 'block';
            messageDiv.innerHTML = 'Upgrade to <a href="' + '<?php echo esc_url(admin_url("admin.php?page=wpaicg-pricing")); ?>' + '">Pro</a> to unlock more fields.';
            setTimeout(function (){
                messageDiv.style.display = 'none';
            }, 5000);
            return;
        }

        var numberOfRowsToAdd = parseInt(document.getElementById('rowsToAdd').value); // Get the number of rows to add
        var container = document.querySelector('.wp-list-table tbody');
        for (let i = 0; i < numberOfRowsToAdd; i++) {
            var newRow = container.lastElementChild.cloneNode(true);
            var newIndex = container.querySelectorAll('tr').length;

            newRow.querySelectorAll('input, select').forEach(function(element) {
                var name = element.name.replace(/\[\d+\]/, '[' + newIndex + ']');
                if (element.tagName.toLowerCase() === 'input' && element.type === 'text') {
                    element.value = ''; // Clear the value for text inputs
                }
                element.name = name;
                // Update ID to keep it unique if it's an input and has an ID
                if (element.id) element.id += '_' + newIndex;
            });

            newRow.querySelector('td:first-child').textContent = newIndex + 1; // Update the row number
            container.appendChild(newRow);
            
            // Reinitialize datetimepicker for the new schedule input, if necessary
            jQuery(newRow).find('.wpaicg-item-schedule').datetimepicker({
                format: 'Y-m-d H:i',
                startDate: new Date()
            });
        }
    });
</script>
<script>
    (function ($){
        var wpaicg_import_btn = $('.wpaicg-import-csv-button');
        var wpaicg_import_file = $('.wpaicg-csv-file');
        $('.wpaicg-schedule-csv').datetimepicker({
            format: 'Y-m-d H:i',
            startDate: new Date()
        });
        wpaicg_import_btn.click(function (){
            var wpaicg_file = wpaicg_import_file[0].files[0];
            if(wpaicg_file === undefined){
                alert('Please select CSV file')
            }
            else{
                if(wpaicg_file.type !== 'text/csv'){
                    alert('Wrong file type. We only accept CSV file')
                }
                else{
                    var data = new FormData();
                    data.append('action', 'wpaicg_read_csv');
                    data.append('file', wpaicg_file);
                    data.append('nonce','<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>');
                    $.ajax({
                        url: '<?php echo admin_url('admin-ajax.php')?>',
                        data: data,
                        cache: false,
                        contentType: false,
                        processData: false,
                        type: 'POST',
                        beforeSend: function (){
                            wpaicg_import_btn.attr('disabled','disabled');
                            wpaicg_import_btn.append('<span class="spinner"></span>');
                            wpaicg_import_btn.find('.spinner').css('visibility','unset');
                        },
                        success: function (res){
                            if(res.status === 'success'){
                                if(res.notice !== undefined){
                                    $('.wpaicg-ajax-message').html(res.notice);
                                }
                                if(res.data !== ''){
                                    var wpaicg_titles = res.data.split('|');
                                    var wpaicg_schedules = [];
                                    var wpaicg_post_status = $('.wpaicg-csv-status:checked').val();
                                    var wpaicg_schedule = $('.wpaicg-schedule-csv').val();
                                    var wpaicg_category = $('select[name=post_category]').val();
                                    var wpaicg_author = $('select[name=post_author]').val();
                                    var wpaicg_categories = [];
                                    $.each(wpaicg_titles, function (idx,item){
                                        wpaicg_schedules.push(wpaicg_schedule);
                                        wpaicg_categories.push(wpaicg_category);
                                    });
                                    $.ajax({
                                        url: '<?php echo admin_url('admin-ajax.php')?>',
                                        data: {wpaicg_titles: wpaicg_titles,wpaicg_schedules: wpaicg_schedules,post_author: wpaicg_author,post_status: wpaicg_post_status,wpaicg_category: wpaicg_categories, action: 'wpaicg_bulk_generator',source: 'csv','nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                                        type: 'POST',
                                        dataType: 'JSON',
                                        success: function (res){
                                            wpaicg_import_btn.removeAttr('disabled');
                                            wpaicg_import_btn.find('.spinner').remove();
                                            if(res.status === 'success'){
                                                $('#wpaicg-csv-success-message').html('<strong>Success:</strong> Your content has been successfully queued! Go to Queue tab to view.').show();
                                                // set timeout to hide the message
                                                setTimeout(function (){
                                                    $('#wpaicg-csv-success-message').hide();
                                                },5000);
                                            }
                                            else{
                                                alert(res.msg);
                                            }
                                        },
                                        error: function (){
                                            wpaicg_import_btn.removeAttr('disabled');
                                            wpaicg_import_btn.find('.spinner').remove();
                                            alert('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                                        }
                                    })
                                }
                                else{
                                    alert('No data for import');
                                }
                            }
                            else {
                                wpaicg_import_btn.removeAttr('disabled');
                                wpaicg_import_btn.find('.spinner').remove();
                                alert(res.msg)
                            }
                        },
                        error: function (){
                            wpaicg_import_btn.removeAttr('disabled');
                            wpaicg_import_btn.find('.spinner').remove();
                        }
                    })
                }
            }
        })
    })(jQuery)
</script>
<script>
    (function ($){
        $('.wpaicg-schedule-post').datetimepicker({
            format: 'Y-m-d H:i',
            startDate: new Date()
        });
        var wpaicg_button = $('.wpaicg-multi-button');
        var wpaicg_multi_line = $('.wpaicg-multi-line');
        wpaicg_button.click(function (){
            var wpaicg_multi_line_value = wpaicg_multi_line.val();
            if(wpaicg_multi_line_value === ''){
                alert('<?php echo esc_html__('Please enter at least one line','gpt3-ai-content-generator')?>');
            }
            else{
                var wpaicg_lines = wpaicg_multi_line_value.split("\n");
                var copyPasteLimit = <?php echo \WPAICG\wpaicg_util_core()->wpaicg_is_pro() ? 100 : 5; ?>;
                if(wpaicg_lines.length > copyPasteLimit){
                    var message;
                    if(copyPasteLimit == 100) { // Pro user message
                        message = '<?php echo esc_html__('You can generate 100 lines at a time. We are processing first 100 lines.', 'gpt3-ai-content-generator'); ?>';
                    } else { // Free user message
                        message = '<?php echo esc_html__('You can generate 5 lines at a time in Free plan. We are processing first 5 lines. Upgrade to Pro to unlock more.', 'gpt3-ai-content-generator'); ?>';
                    }

                    $('.wpaicg-ajax-message').html(message);
                }
                var wpaicg_titles = wpaicg_lines.slice(0, copyPasteLimit);
                var wpaicg_schedules = [];
                var wpaicg_post_status = $('.wpaicg-post-status:checked').val();
                var wpaicg_schedule = $('.wpaicg-schedule-post').val();
                var wpaicg_category = $('select[name=post_category_copy_paste]').val();
                var wpaicg_author = $('select[name=post_author_copy_paste]').val();
                var wpaicg_categories = [];
                $.each(wpaicg_titles, function (idx,item){
                    wpaicg_schedules.push(wpaicg_schedule);
                    wpaicg_categories.push(wpaicg_category);
                });
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php')?>',
                    data: {wpaicg_titles: wpaicg_titles,wpaicg_schedules: wpaicg_schedules,post_author: wpaicg_author,post_status: wpaicg_post_status,wpaicg_category: wpaicg_categories, action: 'wpaicg_bulk_generator',source: 'multi','nonce': '<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>'},
                    type: 'POST',
                    dataType: 'JSON',
                    beforeSend: function(){
                        wpaicg_button.attr('disabled','disabled');
                        wpaicg_button.append('<span class="spinner"></span>');
                        wpaicg_button.find('.spinner').css('visibility','unset');
                    },
                    success: function (res){
                        wpaicg_button.removeAttr('disabled');
                        wpaicg_button.find('.spinner').remove();
                        if(res.status === 'success'){
                            $('#wpaicg-copy-success-message').html('<strong>Success:</strong> Your content has been successfully queued! Go to Queue tab to view.').show();
                            // set timeout to hide the message
                            setTimeout(function (){
                                $('#wpaicg-copy-success-message').hide();
                            },5000);
                        }
                        else{
                            alert(res.msg);
                        }
                    },
                    error: function (){
                        wpaicg_button.removeAttr('disabled');
                        wpaicg_button.find('.spinner').remove();
                        alert('Something went wrong');
                    }
                })
            }
        })
    })(jQuery)
</script>
