<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
?>
<div id="chatbot" class="aipower-tab-pane">
    <!-- Sub-Tab Navigation -->
    <div class="aipower-sub-tabs">
        <!-- Sub-tabs will be generated dynamically via JavaScript -->
    </div>

    <!-- Sub-Tab Content -->
    <div class="aipower-sub-tab-content">
        <!-- Chat Bots Tab -->
        <div id="chatbots-tab" class="aipower-sub-tab-pane active" data-tab-name="<?php echo esc_attr__('Chatbots', 'gpt3-ai-content-generator'); ?>">
            <?php include 'chatbots.php'; ?>
        </div>

        <!-- Logs Tab -->
        <div id="chatlogs-tab" class="aipower-sub-tab-pane" data-tab-name="<?php echo esc_attr__('Logs', 'gpt3-ai-content-generator'); ?>">
            <?php include 'chatlogs.php'; ?>
        </div>
        <!-- PDF Tab -->
        <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
            <div id="chatpdfs-tab" class="aipower-sub-tab-pane" data-tab-name="<?php echo esc_attr__('PDFs', 'gpt3-ai-content-generator'); ?>">
                <?php include 'user_pdfs.php'; ?>
            </div>
        <?php endif; ?>
        <!-- Settings Tab -->
        <div id="chat-common-settings-tab" class="aipower-sub-tab-pane" data-tab-name="<?php echo esc_attr__('Settings', 'gpt3-ai-content-generator'); ?>">
            <?php include 'chat-common-settings.php'; ?>
        </div>
    </div>
</div>
