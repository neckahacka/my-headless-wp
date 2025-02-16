<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.

?>
<div id="content-writer" class="aipower-tab-pane">
    <p>
        <?php echo esc_html__('You can configure the settings for the content writer and other tools here.', 'gpt3-ai-content-generator'); ?>
    </p>
    <div id="content-settings" class="aipower-content-wrapper">
        <!-- Include Express Mode & AutoGPT Settings -->
        <?php include 'content_settings.php'; ?>

        <!-- Include SEO Settings -->
        <?php include 'seo_settings.php'; ?>

        <!-- Include WooCommerce Settings -->
        <?php include 'woocommerce_settings.php'; ?>

        <!-- Include Image Settings -->
        <?php include 'image_settings.php'; ?>

        <!-- Include Image Settings -->
        <?php include 'extra_tools.php'; ?>
    </div>
</div>