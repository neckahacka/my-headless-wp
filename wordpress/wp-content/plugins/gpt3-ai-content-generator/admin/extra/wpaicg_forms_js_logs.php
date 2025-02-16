<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Main JS logic for the AI Forms Logs page
 */
?>
<script>
(function($){
    "use strict";

    /*************************************************************
     * LOGS PAGE LOGIC
     *************************************************************/
    function loadLogs(page, searchTerm) {
        $('#wpaicg_logs_table').html('<tr><td colspan="4"><?php echo esc_js(__('Loading...','gpt3-ai-content-generator')); ?></td></tr>');
        $('#wpaicg_logs_pagination').html('');
        $('#wpaicg_logs_total').text('');

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_get_logs',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-ajax-nonce")); ?>',
                page: page,
                search: searchTerm
            },
            success: function(response) {
                if(response.success){
                    $('#wpaicg_logs_table').html(response.data.table_rows);
                    $('#wpaicg_logs_pagination').html(response.data.pagination);
                    $('#wpaicg_logs_total').text('<?php echo esc_js(__('Total logs:','gpt3-ai-content-generator')); ?> ' + response.data.total);
                } else {
                    var msg = (response.data && response.data.message)
                        ? response.data.message
                        : '<?php echo esc_js(__('Error','gpt3-ai-content-generator')); ?>';
                    $('#wpaicg_logs_table').html('<tr><td colspan="4" style="color:red;">'+msg+'</td></tr>');
                }
            },
            error: function(){
                $('#wpaicg_logs_table').html('<tr><td colspan="4" style="color:red;"><?php echo esc_js(__('Ajax error','gpt3-ai-content-generator')); ?></td></tr>');
            }
        });
    }

    // Pagination click
    $(document).on('click', '.wpaicg_logs_page_link', function(){
        if(!$(this).hasClass('active')){
            var page      = $(this).data('page');
            var searchVal = $('#wpaicg_logs_search').val() || '';
            loadLogs(page, searchVal);
        }
    });

    // Instant search
    $('#wpaicg_logs_search').on('keyup', function(){
        var val = $(this).val().trim();
        loadLogs(1, val);
    });

    // Delete all logs
    $('#wpaicg_logs_delete_all').on('click', function(){
        var conf = confirm('<?php echo esc_js(__('Are you sure you want to delete ALL logs?','gpt3-ai-content-generator')); ?>');
        if(!conf) return;
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_delete_all_logs',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg_delete_all_logs_nonce")); ?>'
            },
            success: function(resp){
                if(resp.success){
                    showGlobalMessage('success','<?php echo esc_js(__('All logs deleted successfully.','gpt3-ai-content-generator')); ?>');
                    loadLogs(1, '');
                } else {
                    showGlobalMessage('error', resp.data.message || '<?php echo esc_js(__('Failed to delete logs','gpt3-ai-content-generator')); ?>');
                }
            },
            error: function(){
                showGlobalMessage('error','<?php echo esc_js(__('Ajax error','gpt3-ai-content-generator')); ?>');
            }
        });
    });

    // Click handler for showing full prompt/response in a modal
    $(document).on('click', '.wpaicg_log_view', function(){
        var prompt   = $(this).data('fullprompt') || '';
        var response = $(this).data('fullresponse') || '';

        // Put prompt as plain text
        $('#wpaicg_log_modal_prompt').text(prompt);

        // Convert the response (Markdown) to HTML using marked.parse
        var parsedResponse = marked.parse(response);
        $('#wpaicg_log_modal_response').html(parsedResponse);

        $('#wpaicg_log_modal').show();
    });

    // Close logs modal
    $('#wpaicg_log_modal_close').on('click', function(){
        $('#wpaicg_log_modal').hide();
    });

    /*************************************************************
     * CLICK HANDLERS FOR SETTINGS ICON & LOGS ICON
     *************************************************************/
    $('#wpaicg_settings_icon').on('click', function() {
        // Hide any visible snippet
        hideShortcodeSnippet();

        // Hide all main containers
        $('#wpaicg_aiforms_container, #wpaicg_logs_container, #wpaicg_create_container, #wpaicg_edit_container, #wpaicg_preview_panel').hide();

        // Show the settings container
        $('#wpaicg_settings_container').show();

        // Hide top icons except "Back"
        $('#wpaicg_toggle_sidebar_icon, #wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form, #wpaicg_save_edited_form').hide();
        $('#wpaicg_return_main').show();
        // Return button is simply "Back" here, no confirmation needed
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Back','gpt3-ai-content-generator')); ?>');
    });

    $('#wpaicg_logs_icon').on('click', function() {
        // Hide any visible snippet
        hideShortcodeSnippet();

        // Hide all main containers
        $('#wpaicg_aiforms_container, #wpaicg_settings_container, #wpaicg_create_container, #wpaicg_edit_container, #wpaicg_preview_panel').hide();

        // Show logs container, load logs
        $('#wpaicg_logs_container').show();
        loadLogs(1, '');

        // Hide top icons except "Back"
        $('#wpaicg_toggle_sidebar_icon, #wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form, #wpaicg_save_edited_form').hide();
        $('#wpaicg_return_main').show();
        // Return button is simply "Back" here, no confirmation needed
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Back','gpt3-ai-content-generator')); ?>');
    });

})(jQuery);
</script>