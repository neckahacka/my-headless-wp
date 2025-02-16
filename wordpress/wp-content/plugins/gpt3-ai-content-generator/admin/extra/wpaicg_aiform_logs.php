<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div id="wpaicg_logs_container" style="display:none;">
    <h2><?php echo esc_html__( 'Logs', 'gpt3-ai-content-generator' ); ?></h2>

    <div class="wpaicg_logs_top" style="margin-bottom:10px;">
        <input
            type="search"
            id="wpaicg_logs_search"
            placeholder="<?php echo esc_attr__('Search logs...', 'gpt3-ai-content-generator'); ?>"
            style="margin-right:8px; padding:3px 6px;"
        />
        <button
            class="button"
            id="wpaicg_logs_delete_all"
            style="background:#a00; color:#fff; border:none; margin-right:12px;"
        >
            <?php echo esc_html__('Delete All','gpt3-ai-content-generator'); ?>
        </button>
        <span id="wpaicg_logs_total" style="font-weight:bold;"></span>
    </div>

    <table style="margin-top:10px;">
        <thead>
            <tr>
                <th><?php echo esc_html__( 'ID', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Name', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Model', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Duration', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Tokens', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Response', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Feedback', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Comment', 'gpt3-ai-content-generator' ); ?></th>
                <th><?php echo esc_html__( 'Date', 'gpt3-ai-content-generator' ); ?></th>
            </tr>
        </thead>
        <tbody id="wpaicg_logs_table" class="wpaicg_logs_table">
            <tr>
                <td colspan="9">
                    <?php echo esc_html__('Loading...', 'gpt3-ai-content-generator'); ?>
                </td>
            </tr>
        </tbody>
    </table>

    <div id="wpaicg_logs_pagination" style="margin-top:10px;"></div>
</div>

<!-- Modal for full prompt/response -->
<div id="wpaicg_log_modal" style="display:none;">
    <div class="wpaicg_log_modal_content">
        <span class="wpaicg_log_modal_close" id="wpaicg_log_modal_close">&times;</span>
        <h3><?php echo esc_html__('Prompt','gpt3-ai-content-generator'); ?></h3>
        <div id="wpaicg_log_modal_prompt"></div>
        <hr />
        <h3><?php echo esc_html__('Response','gpt3-ai-content-generator'); ?></h3>
        <div id="wpaicg_log_modal_response"></div>
    </div>
</div>