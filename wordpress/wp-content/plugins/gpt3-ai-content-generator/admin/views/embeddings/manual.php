<?php
if ( ! defined( 'ABSPATH' ) ) exit;

?>

<form action="" method="post" id="wpaicg_embeddings_form">
    <?php wp_nonce_field('wpaicg_embeddings_save'); ?>
    <input type="hidden" name="action" value="wpaicg_embeddings">
    <div class="nice-form-group">
        <p><strong><?php echo esc_html__('Data','gpt3-ai-content-generator')?></strong></p>
        <textarea name="content" id="wpaicg-embeddings-content" rows="10"></textarea>
    </div>
    <div class="nice-form-group">
        <button class="button button-primary"><?php echo esc_html__('Save','gpt3-ai-content-generator')?></button>
    </div>
</form>