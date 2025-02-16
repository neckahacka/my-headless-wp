<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
?>
<!-- Main container for logs and preview -->
<div class="aipower-chat-log-container">

    <!-- Logs table section -->
    <div class="aipower-log-section">
        <!-- Tools wrapper -->
        <div class="aipower-tools-wrapper">

            <!-- Search input field -->
            <div class="aipower-log-search-wrapper">
                <input type="text" id="aipower-log-search-input" placeholder="<?php echo esc_attr__('Search Logs...', 'gpt3-ai-content-generator'); ?>" />
            </div>
            <!-- Tools container -->
            <div class="aipower-tools-container">
                <!-- Inline confirmation prompt for Delete All -->
                <div id="aipower-log-confirmation" class="aipower-confirmation" style="display:none;">
                    <span><?php echo esc_html__('Sure?', 'gpt3-ai-content-generator'); ?></span>
                    <span id="aipower-log-confirm-yes" class="aipower-confirm-yes"><?php echo esc_html__('Yes', 'gpt3-ai-content-generator'); ?></span>
                </div>
                <!-- Inline confirmation prompt for Export All -->
                <div id="aipower-export-confirmation" class="aipower-confirmation" style="display:none;">
                    <span id="aipower-export-confirm-message"></span>
                    <span id="aipower-export-confirm-yes" class="aipower-confirm-yes"><?php echo esc_html__('Yes', 'gpt3-ai-content-generator'); ?></span>
                </div>

                <!-- Tool icon for actions -->
                <span id="aipower-log-tools-icon" class="dashicons dashicons-admin-tools" title="<?php echo esc_attr__('Tools to refresh, export, and delete all logs', 'gpt3-ai-content-generator'); ?>"></span>

                <!-- Hidden menu for actions -->
                <div id="aipower-log-tools-menu" class="aipower-log-tools-menu" style="display:none;">
                    <div id="aipower-refresh-logs" class="aipower-tools-action"><?php echo esc_html__('Refresh', 'gpt3-ai-content-generator'); ?></div>
                    <div id="aipower-export-all-logs" class="aipower-tools-action"><?php echo esc_html__('Export', 'gpt3-ai-content-generator'); ?></div>
                    <div id="aipower-delete-all-logs" class="aipower-tools-action"><?php echo esc_html__('Delete All', 'gpt3-ai-content-generator'); ?></div>
                </div>
            </div>
        </div>

        <!-- Hidden nonce field for AJAX security -->
        <input type="hidden" id="ai-engine-nonce" value="<?php echo wp_create_nonce('wpaicg_save_ai_engine_nonce'); ?>" />
        <?php
        $current_log_page = isset($_GET['aipower_log_page']) ? intval($_GET['aipower_log_page']) : 1;
        $table_data = WPAICG\WPAICG_Logs::aipower_render_logs_table($current_log_page);

        // Extract leads and feedback arrays
        $leads = $table_data['leads'];
        $feedback = $table_data['feedback'];

        $lead_count = count($leads);
        $feedback_count = count($feedback);

        // Determine singular/plural for leads
        $lead_word = ($lead_count === 1) ? __('lead', 'gpt3-ai-content-generator') : __('leads', 'gpt3-ai-content-generator');
        // Feedback is typically uncountable, we can leave it as 'feedback'
        $feedback_word = __('feedback', 'gpt3-ai-content-generator');

        $display_line = '';
        if ($lead_count > 0 && $feedback_count > 0) {
            // Both leads and feedback
            $display_line = sprintf(
                __('<p>You have <a href="#" id="aipower-leads-link">%d %s</a> and <a href="#" id="aipower-feedback-link">%d %s</a>.</p>', 'gpt3-ai-content-generator'),
                $lead_count,
                $lead_word,
                $feedback_count,
                $feedback_word
            );
        } elseif ($lead_count > 0 && $feedback_count == 0) {
            // Only leads
            $display_line = sprintf(
                __('<p>You have <a href="#" id="aipower-leads-link">%d %s</a>.</p>', 'gpt3-ai-content-generator'),
                $lead_count,
                $lead_word
            );
        } elseif ($lead_count == 0 && $feedback_count > 0) {
            // Only feedback
            $display_line = sprintf(
                __('<p>You have <a href="#" id="aipower-feedback-link">%d %s</a>.</p>', 'gpt3-ai-content-generator'),
                $feedback_count,
                $feedback_word
            );
        } else {
            // No leads and no feedback
            $display_line = '';
        }

        if (!empty($display_line)): ?>
            <div id="aipower-lead-feedback-summary" style="margin-top:10px;">
                <span id="aipower-lead-feedback-text"><?php echo wp_kses_post($display_line); ?></span>
            </div>
        <?php endif; ?>

        <!-- Render the logs table below the summary line -->
        <?php echo wp_kses_post($table_data['html']); ?>
    </div>


    <!-- Preview section -->
    <div class="aipower-log-preview-section">
        <div id="aipower-log-details-container"></div> <!-- Container for displaying the log details -->
    </div>
</div>
<!-- Revise Answer Modal -->
<div id="aipower-revise-answer-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Revise Answer', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Display User Message -->
                <div class="aipower-form-group">
                    <p><strong><?php echo esc_html__('User Message:', 'gpt3-ai-content-generator'); ?></strong></p>
                    <p id="aipower-revise-user-message"></p>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Display AI Response -->
                <div class="aipower-form-group">
                    <p><strong><?php echo esc_html__('AI Response:', 'gpt3-ai-content-generator'); ?></strong></p>
                    <p id="aipower-revise-ai-message"></p>
                </div>
            </div>
            <div class="aipower-form-group aipower-grouped-fields">
                <!-- Expected Response Label and Textarea -->
                <div class="aipower-form-group">
                    <p><strong><?php echo esc_html__('Expected Response:', 'gpt3-ai-content-generator'); ?></strong></p>
                    <textarea id="aipower-expected-response" rows="5" style="width: 100%;"></textarea>
                </div>
            </div>
            <div class="aipower-form-group">
                <button id="aipower-revise-cancel" class="button button-primary"><?php echo esc_html__('Cancel', 'gpt3-ai-content-generator'); ?></button>
                <button id="aipower-revise-update" class="button button-primary"><?php echo esc_html__('Update Answer', 'gpt3-ai-content-generator'); ?></button>
            </div>
            <div class="aipower-form-group">
                <!-- Spinner and Message -->
                <div id="aipower-revise-spinner" class="aipower-spinner" style="display: none;"></div>
                <div id="aipower-revise-message" class="aipower-message" style="display: none;"></div>
            </div>
        </div>
    </div>
</div>
<!-- Prompt Details Modal -->
<div id="aipower-prompt-details-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Prompt Details', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <!-- Spinner while loading -->
            <div id="aipower-prompt-details-spinner" class="aipower-spinner" style="display: none;"></div>
            <!-- Container to display prompt details -->
            <div class="aipower-form-group aipower-grouped-fields">
                <div class="aipower-form-group">
                    <div id="aipower-prompt-details-content" style="white-space: pre-wrap; word-wrap: break-word;"></div>
                </div>
            </div>
        </div>
        <div class="aipower-form-group">
            <button id="aipower-prompt-details-close" class="button button-primary"><?php echo esc_html__('Close', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
<!-- Token Details Modal -->
<div id="aipower-token-details-modal" class="aipower-modal" style="display: none;">
    <div class="aipower-modal-content aipower-token-details-modal">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Token Usage Details', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <table class="aipower-token-details-table">
                <thead>
                    <tr>
                        <th><?php echo esc_html__('#', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php echo esc_html__('Model', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php echo esc_html__('Token', 'gpt3-ai-content-generator'); ?></th>
                        <th><?php echo esc_html__('Cost', 'gpt3-ai-content-generator'); ?></th> <!-- New Cost Column -->
                    </tr>
                </thead>
                <tbody>
                    <!-- Dynamic Content Will Be Injected Here -->
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="2" style="text-align: right;"><strong><?php echo esc_html__('Total', 'gpt3-ai-content-generator'); ?></strong></td>
                        <td id="aipower-total-tokens">0</td>
                        <td id="aipower-total-cost">$0.0000</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Leads Modal -->
<div id="aipower-leads-modal" class="aipower-modal" style="display:none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Leads Details', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <ul id="aipower-leads-list"></ul>
        </div>
        <div class="aipower-form-group">
            <button id="aipower-leads-close" class="button button-primary"><?php echo esc_html__('Close', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>

<!-- Feedback Modal -->
<div id="aipower-feedback-modal" class="aipower-modal" style="display:none;">
    <div class="aipower-modal-content">
        <div class="aipower-modal-header">
            <h2><?php echo esc_html__('Feedback Details', 'gpt3-ai-content-generator'); ?></h2>
            <span class="aipower-close">&times;</span>
        </div>
        <div class="aipower-modal-body">
            <ul id="aipower-feedback-list"></ul>
        </div>
        <div class="aipower-form-group">
            <button id="aipower-feedback-close" class="button button-primary"><?php echo esc_html__('Close', 'gpt3-ai-content-generator'); ?></button>
        </div>
    </div>
</div>
<!-- Include JavaScript -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // -------------------- Cached jQuery Selectors --------------------
    const $spinner = $('#aipower-spinner');
    const $message = $('#aipower-message');
    const $logDetailsContainer = $('#aipower-log-details-container');
    const $logsTableContainer = $('#aipower-logs-table-container');
    const $nonceField = $('#ai-engine-nonce');
    const nonce = $nonceField.val();
    const $searchInput = $('#aipower-log-search-input');
    let searchTimer;

    // -------------------- UI FEEDBACK: Spinner and Message Display Functions --------------------
    const UI = {
        showSpinner: () => $spinner.show(),
        hideSpinner: () => $spinner.hide(),
        /**
         * Display a message to the user.
         * @param {string} type - The type of message ('error', 'success', etc.).
         * @param {string} text - The message content.
         * @param {boolean} autoHide - Whether the message should auto-hide after a delay.
         * @param {boolean} isHTML - Whether the message content contains HTML.
         */
        showMessage: (type, text, autoHide = false, isHTML = false) => {
            $message
                .removeClass('error success aipower-autosaving')
                .addClass(type)
                .fadeIn();

            if (isHTML) {
                $message.html(text);
            } else {
                $message.text(text);
            }

            if (autoHide) {
                setTimeout(() => $message.fadeOut(), 3000);
            }
        }
    };


    // -------------------- Event Handlers --------------------

    // Toggle the tools menu when the tools icon is clicked
    $('#aipower-log-tools-icon').on('click', function(event) {
        event.stopPropagation(); // Prevent the click from propagating to the document
        $('#aipower-log-tools-menu').toggle();
    });

    // Hide the tools menu when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#aipower-tools-container').length) {
            $('#aipower-log-tools-menu').hide();
        }
    });

    // Search Input Handler
    $searchInput.on('input', function() {
        clearTimeout(searchTimer);
        const searchTerm = $(this).val();

        // Delay the AJAX call to prevent too many requests
        searchTimer = setTimeout(() => {
            UI.showSpinner();
            // Send AJAX request to refresh logs table with search term
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'aipower_refresh_logs_table',
                    page: 1, // Reset to first page on new search
                    search_term: searchTerm,
                    _wpnonce: nonce
                },
                success: function(response) {
                    UI.hideSpinner();
                    if (response.success) {
                        $logsTableContainer.html(response.data.table.html);
                        UI.showMessage('success', response.data.message, true);
                    } else {
                        UI.showMessage('error', response.data.message, true);
                    }
                },
                error: function() {
                    UI.hideSpinner();
                    UI.showMessage('error', 'An error occurred while searching logs.', true);
                }
            });
        }, 300); // Adjust delay as needed
    });

    // Refresh Logs Table
    $(document).on('click', '#aipower-refresh-logs', function() {
        $('#aipower-log-search-input').val(''); // Clear search input
        $('#aipower-log-tools-menu').hide(); // Hide the menu after action
        UI.showSpinner();
        // Send AJAX request to refresh logs table
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_refresh_logs_table',
                page: 1, // Refresh to the first page
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    $logsTableContainer.html(response.data.table.html);
                    UI.showMessage('success', response.data.message || 'Logs refreshed successfully.', true);
                } else {
                    UI.showMessage('error', response.data.message || 'An error occurred while refreshing logs.', true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', 'An error occurred while refreshing logs.', true);
            }
        });
    });

    // View Log Details
    $(document).on('click', '.aipower-view-log-icon', function() {
        const log_id = $(this).data('id');

        UI.showSpinner();
        // Send AJAX request to load log details
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_load_log_details',
                log_id: log_id,
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    $logDetailsContainer.html(response.data.log_details);
                    UI.showMessage('success', response.data.message || 'Log details loaded successfully.', true);
                } else {
                    UI.showMessage('error', response.data.message || 'An error occurred while loading log details.', true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', 'An error occurred while loading log details.', true);
            }
        });
    });

    // Handle Pagination
    $(document).on('click', '.aipower-log-page-btn', function() {
        const page = $(this).data('page');
        const searchTerm = $searchInput.val();

        UI.showSpinner();
        // Send AJAX request to refresh logs table
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_refresh_logs_table',
                page: page,
                search_term: searchTerm,
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    $logsTableContainer.html(response.data.table.html);
                    UI.showMessage('success', response.data.message, true);
                } else {
                    UI.showMessage('error', response.data.message, true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', 'An error occurred while paginating logs.', true);
            }
        });
    });

    // Delete Log
    $(document).on('click', '.aipower-delete-log-icon', function(event) {
        event.stopPropagation(); // Prevent the click from propagating to the document

        const $currentRow = $(this).closest('tr');
        const $confirmation = $currentRow.find('.aipower-single-delete-confirmation');

        // Hide any other open confirmations
        $('.aipower-single-delete-confirmation').not($confirmation).removeClass('show');

        // Toggle the 'show' class for the confirmation prompt in this row
        $confirmation.toggleClass('show');

        if ($confirmation.hasClass('show')) {
            // Attach a click handler to the document to hide the confirmation prompt when clicking outside
            $(document).on('click.aipower-single-delete', function(e) {
                if (!$(e.target).closest('.aipower-single-delete-confirmation').length && !$(e.target).is('.aipower-delete-log-icon')) {
                    $confirmation.removeClass('show');
                    // Remove this click handler to prevent multiple bindings
                    $(document).off('click.aipower-single-delete');
                }
            });
        } else {
            // If the confirmation prompt is hidden, remove the document click handler
            $(document).off('click.aipower-single-delete');
        }
    });

    // Prevent clicks inside the single delete confirmation prompt from propagating to the document
    $(document).on('click', '.aipower-single-delete-confirmation', function(event) {
        event.stopPropagation();
    });

    // Handle single delete "Yes" click
    $(document).on('click', '.aipower-single-confirm-yes', function() {
        const log_id = $(this).data('id');
        const $row = $(this).closest('tr');

        // Hide the confirmation prompt
        $row.find('.aipower-single-delete-confirmation').removeClass('show');
        // Remove the document click handler
        $(document).off('click.aipower-single-delete');

        // Proceed with deleting the log
        UI.showSpinner();

        // Send AJAX request to delete the log
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_delete_log',
                log_id: log_id,
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    // Remove the log row from the table
                    $row.remove();
                    // Clear the log details if the deleted log was being viewed
                    $logDetailsContainer.empty();
                    UI.showMessage('success', response.data.message || 'Log deleted successfully.', true);
                } else {
                    UI.showMessage('error', response.data.message || 'An error occurred while deleting the log.', true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', 'An error occurred while deleting the log.', true);
            }
        });
    });

    // Show confirmation prompt when Delete All is clicked
    $(document).on('click', '#aipower-delete-all-logs', function(event) {
        event.stopPropagation(); // Prevent the click from propagating to the document
        // Toggle the visibility of the confirmation prompt
        $('#aipower-log-confirmation').toggle();
        // Hide the tools menu
        $('#aipower-log-tools-menu').hide();

        if ($('#aipower-log-confirmation').is(':visible')) {
            // Attach a click handler to the document to hide the confirmation prompt when clicking outside
            $(document).on('click.aipower', function(e) {
                if (!$(e.target).closest('#aipower-log-confirmation').length && !$(e.target).is('#aipower-log-tools-icon')) {
                    $('#aipower-log-confirmation').hide();
                    // Remove this click handler to prevent multiple bindings
                    $(document).off('click.aipower');
                }
            });
        } else {
            // If the confirmation prompt is hidden, remove the document click handler
            $(document).off('click.aipower');
        }
    });

    // Prevent clicks inside the confirmation prompt from propagating to the document
    $(document).on('click', '#aipower-log-confirmation', function(event) {
        event.stopPropagation();
    });

    // Handle confirmation "Yes" click for Delete All
    $(document).on('click', '#aipower-log-confirm-yes', function() {
        // Hide the confirmation prompt
        $('#aipower-log-confirmation').hide();
        // Remove the document click handler
        $(document).off('click.aipower');

        // Proceed with deleting all logs
        UI.showSpinner();

        // Send AJAX request to delete all logs
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_delete_all_logs',
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    // Refresh logs table
                    $logsTableContainer.html('<p>No logs found.</p>');
                    // Clear the log details
                    $logDetailsContainer.empty();
                    UI.showMessage('success', response.data.message || 'All logs deleted successfully.', true);
                } else {
                    UI.showMessage('error', response.data.message || 'An error occurred while deleting all logs.', true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', 'An error occurred while deleting all logs.', true);
            }
        });
    });

    // Export Logs
    $(document).on('click', '#aipower-export-all-logs', function() {
        $('#aipower-log-tools-menu').hide(); // Hide the menu after action

        const searchTerm = $('#aipower-log-search-input').val().trim();

        // Prepare data to send
        const exportData = {
            action: 'aipower_export_logs',
            search_term: searchTerm,
            _wpnonce: nonce
        };

        // Check if there are logs to export
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_count_export_logs',
                search_term: searchTerm,
                _wpnonce: nonce
            },
            success: function(response) {
                if (response.success) {
                    const totalLogs = response.data.total_logs;
                    if (totalLogs === 0) {
                        UI.showMessage('error', 'There are no logs to export.', true);
                        return;
                    }

                    // Prepare the confirmation message
                    const confirmMessage = searchTerm 
                        ? `Exporting ${totalLogs} logs. Proceed?`
                        : `Exporting ${totalLogs} logs. Proceed?`;

                    // Set the confirmation message
                    $('#aipower-export-confirm-message').text(confirmMessage);

                    // Show the export confirmation prompt
                    $('#aipower-export-confirmation').show();
                } else {
                    UI.showMessage('error', response.data.message || 'An error occurred while counting logs for export.', true);
                }
            },
            error: function() {
                UI.showMessage('error', 'An error occurred while counting logs for export.', true);
            }
        });
    });

    // Handle confirmation "Yes" click for Export
    $(document).on('click', '#aipower-export-confirm-yes', function() {
        const searchTerm = $('#aipower-log-search-input').val().trim();

        // Hide the export confirmation prompt
        $('#aipower-export-confirmation').hide();

        // Prepare data to send
        const exportData = {
            action: 'aipower_export_logs',
            search_term: searchTerm,
            _wpnonce: nonce
        };

        // Start export process
        UI.showSpinner();
        $('#aipower-delete-progress').show();
        $('#aipower-delete-progress-counter').text(`0/${searchTerm ? 'Filtered Logs' : 'All Logs'}`);

        // Initiate export
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: exportData,
            success: function(exportResponse) {
                UI.hideSpinner();
                $('#aipower-delete-progress').hide();

                if (exportResponse.success) {
                    // Generate the download link
                    const downloadUrl = exportResponse.data.file_url;
                    const downloadLink = `<a href="${downloadUrl}" target="_blank" rel="noopener noreferrer">Download CSV</a>`;
                    // Combine the success message and the download link
                    const successMessage = `Logs exported successfully. ${downloadLink}`;
                    // Display the combined message without auto-hiding and allow HTML content
                    UI.showMessage('success', successMessage, false, true);
                } else {
                    UI.showMessage('error', exportResponse.data.message || 'An error occurred while exporting logs.', true);
                }
            },
            error: function() {
                UI.hideSpinner();
                $('#aipower-delete-progress').hide();
                UI.showMessage('error', 'An error occurred while exporting logs.', true);
            }
        });
    });

    // Hide export confirmation when clicking outside
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#aipower-export-confirmation').length && !$(e.target).is('#aipower-export-all-logs')) {
            $('#aipower-export-confirmation').hide();
        }
    });

    // Handle click on the Message cell to view log details
    $(document).on('click', '.aipower-clickable-message', function() {
        const log_id = $(this).data('id');

        UI.showSpinner();
        // Send AJAX request to load log details
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_load_log_details',
                log_id: log_id,
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    $logDetailsContainer.html(response.data.log_details);
                    UI.showMessage('success', response.data.message || 'Log details loaded successfully.', true);
                } else {
                    UI.showMessage('error', response.data.message || 'An error occurred while loading log details.', true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', 'An error occurred while loading log details.', true);
            }
        });
    });

        // -------------------- Revise Answer Modal Functionality --------------------

    // Function to open the Revise Answer modal with populated data
    function openReviseAnswerModal(userMessage, aiMessage) {
        // Populate the modal with the messages
        $('#aipower-revise-user-message').text(userMessage);
        $('#aipower-revise-ai-message').text(aiMessage);
        $('#aipower-expected-response').val(''); // Clear any previous input
        $('#aipower-revise-message').hide().removeClass('success error').text('');
        $('#aipower-revise-spinner').hide();

        // Show the modal and focus the textarea after it's visible
        $('#aipower-revise-answer-modal').fadeIn(function() {
            $('#aipower-expected-response').focus();
        });
    }
    
    // Function to close the Revise Answer modal
    function closeReviseAnswerModal() {
        $('#aipower-revise-answer-modal').fadeOut();
    }

    // Event handler for closing the modal when the close button is clicked
    $('#aipower-revise-answer-modal .aipower-close').on('click', function() {
        closeReviseAnswerModal();
    });

    // Event handler for the "Cancel" button
    $('#aipower-revise-cancel').on('click', function() {
        closeReviseAnswerModal();
    });

    // Event handler for the "Revise Answer" button
    $(document).on('click', '.aipower-revise-answer', function() {
        const userMessage = $(this).data('user-message');
        const aiMessage = $(this).data('ai-message');

        openReviseAnswerModal(userMessage, aiMessage);
    });

    // Event handler for the "Update Answer" button
    $('#aipower-revise-update').on('click', function() {
        const revisedAnswer = $('#aipower-expected-response').val().trim();
        const userMessage = $('#aipower-revise-user-message').text();
        const aiMessage = $('#aipower-revise-ai-message').text();

        if (revisedAnswer === '') {
            $('#aipower-revise-message').removeClass('success').addClass('error').text('Please enter a revised answer.').fadeIn();
            return;
        }

        // Prepare the content by combining user message and revised answer
        let formatted_content = '';
        if (userMessage) {
            formatted_content = userMessage + "\n" + revisedAnswer;
        } else {
            formatted_content = revisedAnswer;
        }

        // Show the spinner and disable the Update button to prevent multiple submissions
        $('#aipower-revise-spinner').show();
        $('#aipower-revise-update').prop('disabled', true);

        // Send AJAX request to save the revised answer
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_revise_answer',
                content: formatted_content,
                _wpnonce: nonce
            },
            success: function(response) {
                $('#aipower-revise-spinner').hide();
                $('#aipower-revise-update').prop('disabled', false);
                if (response.success) {
                    $('#aipower-revise-message').removeClass('error').addClass('success').text(response.data.message || 'Answer revised successfully. Try asking your bot the same question.').fadeIn();
                    
                    // Optionally, you can refresh the log details to show the updated answer
                    // Assuming the backend updates the log details accordingly
                    // You may need to trigger the log details refresh here if necessary
                } else {
                    $('#aipower-revise-message').removeClass('success').addClass('error').text(response.data.message || 'An error occurred while revising the answer.').fadeIn();
                }
            },
            error: function() {
                $('#aipower-revise-spinner').hide();
                $('#aipower-revise-update').prop('disabled', false);
                $('#aipower-revise-message').removeClass('success').addClass('error').text('An unexpected error occurred.').fadeIn();
            }
        });
    });

    // -------------------- Prompt Details Modal Functionality --------------------

    // Function to open the Prompt Details modal with fetched data
    function openPromptDetailsModal(requestData) {
        if (requestData) {
            // Format the request data as a JSON string with indentation for readability
            const formattedData = JSON.stringify(requestData, null, 2);
            $('#aipower-prompt-details-content').text(formattedData);
        } else {
            $('#aipower-prompt-details-content').text('No prompt details available.');
        }
        // Show the modal
        $('#aipower-prompt-details-modal').fadeIn();
    }

    // Event handler for closing the Prompt Details modal when the close button or "Close" button is clicked
    $('#aipower-prompt-details-modal .aipower-close, #aipower-prompt-details-close').on('click', function() {
        $('#aipower-prompt-details-modal').fadeOut();
    });

    // Event handler for clicking on "Prompt Details"
    $(document).on('click', '.aipower-prompt-details', function() {
        const logId = $(this).data('log-id');
        const messageDate = $(this).data('message-date');

        if (!logId || !messageDate) {
            UI.showMessage('error', 'Invalid log or message identifier.', true);
            return;
        }

        // Show the spinner and clear previous content
        $('#aipower-prompt-details-content').text('');
        $('#aipower-prompt-details-spinner').show();

        // Send AJAX request to fetch 'request' data
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_load_prompt_details',
                log_id: logId,
                message_date: messageDate,
                _wpnonce: nonce
            },
            success: function(response) {
                $('#aipower-prompt-details-spinner').hide();
                if (response.success) {
                    const requestData = response.data.request;
                    openPromptDetailsModal(requestData);
                } else {
                    UI.showMessage('error', response.data.message || 'Failed to load prompt details.', true);
                }
            },
            error: function() {
                $('#aipower-prompt-details-spinner').hide();
                UI.showMessage('error', 'An error occurred while loading prompt details.', true);
            }
        });
    });

        // -------------------- New Event Handlers for Blocking IP --------------------

    // **a. Handle Block IP Icon Click**
    $(document).on('click', '.aipower-block-ip-icon', function(e) {
        e.preventDefault();
        var ip = $(this).data('ip');
        if (!ip) {
            UI.showMessage('error', '<?php echo esc_js(__('Invalid IP address.', 'gpt3-ai-content-generator')); ?>', true);
            return;
        }
        // Store the IP to block in the confirmation prompt's data attribute
        $('#aipower-block-ip-confirmation').data('ip', ip).fadeIn();
    });

    // **b. Handle Confirmation "Yes" Click for Blocking IP**
    $(document).on('click', '#aipower-block-ip-confirm-yes', function() {
        var ip = $('#aipower-block-ip-confirmation').data('ip');
        if (!ip) {
            UI.showMessage('error', '<?php echo esc_js(__('Invalid IP address.', 'gpt3-ai-content-generator')); ?>', true);
            $('#aipower-block-ip-confirmation').fadeOut();
            return;
        }

        // Hide the confirmation prompt
        $('#aipower-block-ip-confirmation').fadeOut();

        // Proceed with blocking the IP
        UI.showSpinner();

        // Send AJAX request to block the IP
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'aipower_block_ip',
                ip: ip,
                _wpnonce: nonce
            },
            success: function(response) {
                UI.hideSpinner();
                if (response.success) {
                    UI.showMessage('success', response.data.message, true);
                    // Optionally, disable or change the block icon to indicate the IP is blocked
                    $('.aipower-block-ip-icon[data-ip="' + ip + '"]')
                        .removeClass('dashicons-no')
                        .addClass('dashicons-yes')
                        .attr('title', '<?php echo esc_attr__('IP Blocked', 'gpt3-ai-content-generator'); ?>');
                } else {
                    UI.showMessage('error', response.data.message, true);
                }
            },
            error: function() {
                UI.hideSpinner();
                UI.showMessage('error', '<?php echo esc_js(__('An error occurred while blocking the IP.', 'gpt3-ai-content-generator')); ?>', true);
            }
        });
    });

    // **c. Hide Confirmation Prompt When Clicking Outside**
    $(document).on('click', function(e) {
        if (!$(e.target).closest('#aipower-block-ip-confirmation').length && !$(e.target).is('.aipower-block-ip-icon')) {
            $('#aipower-block-ip-confirmation').fadeOut();
        }
    });

    // **d. Prevent Clicks Inside the Confirmation Prompt from Propagating**
    $(document).on('click', '#aipower-block-ip-confirmation', function(event) {
        event.stopPropagation();
    });

    // Function to open the Token Details modal with populated data
    function openTokenDetailsModal(details) {
        // Reference to the modal's tbody and tfoot
        const $modalBody = $('#aipower-token-details-modal .aipower-token-details-table tbody');
        const $modalFooterTokens = $('#aipower-total-tokens');
        const $modalFooterCost = $('#aipower-total-cost');
        
        // Clear any existing content
        $modalBody.empty();
        $modalFooterTokens.text('0');
        $modalFooterCost.text('$0.0000');

        let totalTokens = 0;
        let totalCost = 0;

        if (details.length === 0) {
            $modalBody.append('<tr><td colspan="4"><?php echo esc_js(__('No AI messages to display.', 'gpt3-ai-content-generator')); ?></td></tr>');
        } else {
            // Iterate through the details and append rows
            details.forEach(function(detail) {
                // Only include entries with a valid model and token count
                if (detail.model !== 'N/A' && detail.token > 0) {
                    // Parse the cost to float for accurate summation
                    const costFloat = parseFloat(detail.cost);
                    const costFormatted = `$${costFloat.toFixed(8)}`;
                    
                    // Accumulate totals
                    totalTokens += detail.token;
                    totalCost += costFloat;

                    const row = `
                        <tr>
                            <td>${detail.number}</td>
                            <td>${detail.model}</td>
                            <td>${detail.token}</td>
                            <td>${costFormatted}</td> <!-- Display Cost -->
                        </tr>
                    `;
                    $modalBody.append(row);
                }
            });

            // If no valid entries were added, display a message
            if ($modalBody.children().length === 0) {
                $modalBody.append('<tr><td colspan="4"><?php echo esc_js(__('No AI messages with token data.', 'gpt3-ai-content-generator')); ?></td></tr>');
            }
        }

        // Update totals in the footer
        $modalFooterTokens.text(totalTokens);
        $modalFooterCost.text(`$${totalCost.toFixed(4)}`);

        // Show the modal
        $('#aipower-token-details-modal').fadeIn();
    }

    // Event handler for closing the modal when the close button is clicked
    $('#aipower-token-details-modal .aipower-close').on('click', function() {
        $('#aipower-token-details-modal').fadeOut();
    });

    // Event handler for clicking outside the modal content to close the modal
    $(window).on('click', function(event) {
        if ($(event.target).is('#aipower-token-details-modal')) {
            $('#aipower-token-details-modal').fadeOut();
        }
    });

    // Handle Click on Info Icon to Show Token Details
    $(document).on('click', '.aipower-log-info-icon', function() {
        const detailsJson = $(this).attr('data-details');
        if (detailsJson) {
            try {
                const details = JSON.parse(detailsJson);
                openTokenDetailsModal(details);
            } catch (e) {
                console.error('Invalid JSON data for token details:', e);
                UI.showMessage('error', 'Failed to load token details.', true);
            }
        }
    });

});
</script>

<script type="text/javascript">
jQuery(document).ready(function($) {
    const leads = <?php echo wp_json_encode($leads); ?>;
    const feedback = <?php echo wp_json_encode($feedback); ?>;

    // Handle Leads link click
    $(document).on('click', '#aipower-leads-link', function(e) {
        e.preventDefault();
        const $leadsList = $('#aipower-leads-list');
        $leadsList.empty();

        if (leads.length === 0) {
            $leadsList.append('<li><?php echo esc_js(__('No leads available.', 'gpt3-ai-content-generator')); ?></li>');
        } else {
            leads.forEach(function(lead) {
                const leadItem = `
                    <li>
                        <p><strong><?php echo esc_js(__('User Message:', 'gpt3-ai-content-generator')); ?></strong> ${lead.user_message}</p>
                        <p><strong><?php echo esc_js(__('AI Response:', 'gpt3-ai-content-generator')); ?></strong> ${lead.ai_message ? lead.ai_message : '<?php echo esc_js(__('No AI response', 'gpt3-ai-content-generator')); ?>'}</p>
                        <p class="aipower-timestamp">${lead.user_timestamp}</p>
                        <p><strong><?php echo esc_js(__('Lead Info:', 'gpt3-ai-content-generator')); ?></strong></p>
                        <p><span class="aipower-label"><?php echo esc_js(__('Name:', 'gpt3-ai-content-generator')); ?></span> ${lead.name ? lead.name : 'N/A'}</p>
                        <p><span class="aipower-label"><?php echo esc_js(__('Email:', 'gpt3-ai-content-generator')); ?></span> ${lead.email ? lead.email : 'N/A'}</p>
                        <p><span class="aipower-label"><?php echo esc_js(__('Phone:', 'gpt3-ai-content-generator')); ?></span> ${lead.phone ? lead.phone : 'N/A'}</p>
                    </li>
                `;
                $leadsList.append(leadItem);
            });
        }

        $('#aipower-leads-modal').fadeIn();
    });

    // Handle Feedback link click
    $(document).on('click', '#aipower-feedback-link', function(e) {
        e.preventDefault();
        const $feedbackList = $('#aipower-feedback-list');
        $feedbackList.empty();

        if (feedback.length === 0) {
            $feedbackList.append('<li><?php echo esc_js(__('No feedback available.', 'gpt3-ai-content-generator')); ?></li>');
        } else {
            feedback.forEach(function(fb) {
                const icon = fb.feedback_type === 'up' ? '👍' : '👎';
                const feedbackItem = `
                    <li>
                        <p><strong><?php echo esc_js(__('User Message:', 'gpt3-ai-content-generator')); ?></strong> ${fb.user_message}</p>
                        <p class="aipower-timestamp"><?php echo esc_js(__('Sent at:', 'gpt3-ai-content-generator')); ?> ${fb.user_timestamp}</p>
                        <p><strong><?php echo esc_js(__('AI Response:', 'gpt3-ai-content-generator')); ?></strong> ${fb.ai_message ? fb.ai_message : '<?php echo esc_js(__('No AI response', 'gpt3-ai-content-generator')); ?>'}</p>
                        <p class="aipower-timestamp"><?php echo esc_js(__('AI Response at:', 'gpt3-ai-content-generator')); ?> ${fb.ai_timestamp}</p>
                        <p><strong><?php echo esc_js(__('Feedback:', 'gpt3-ai-content-generator')); ?></strong> <span class="aipower-feedback-icon">${icon}</span> ${fb.feedback_details}</p>
                    </li>
                `;
                $feedbackList.append(feedbackItem);
            });
        }

        $('#aipower-feedback-modal').fadeIn();
    });

    // Close modals
    $('#aipower-leads-close, #aipower-leads-modal .aipower-close').on('click', function() {
        $('#aipower-leads-modal').fadeOut();
    });

    $('#aipower-feedback-close, #aipower-feedback-modal .aipower-close').on('click', function() {
        $('#aipower-feedback-modal').fadeOut();
    });
});
</script>