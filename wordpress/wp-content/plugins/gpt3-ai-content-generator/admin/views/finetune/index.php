<?php
if ( ! defined( 'ABSPATH' ) ) exit;

$wpaicg_provider = get_option('wpaicg_provider', 'OpenAI');

if($wpaicg_provider == 'Azure' || $wpaicg_provider == 'Google'){
    ?>
    <div>
        <p></p>
        <p>Fine-tuning is not available in Azure or Google. Please go to Settings - AI Engine and switch to OpenAI to use this feature.</p>
    </div>
    <?php
} else {
    $wpaicg_action = isset($_GET['action']) && !empty($_GET['action']) && in_array(sanitize_text_field($_GET['action']), array('embeddings','fine-tunes','files','manual')) ? sanitize_text_field($_GET['action']) : 'manual';
    $checkRole = \WPAICG\wpaicg_roles()->user_can('wpaicg_finetune', empty($wpaicg_action) ? 'manual' : ($wpaicg_action == 'fine-tunes' ? 'file-tunes' : $wpaicg_action));
    if($checkRole){
        echo '<script>window.location.href="'.$checkRole.'"</script>';
        exit;
    }
    ?>

    <style>
    .wpaicg_notice_text_rw {
        padding: 10px;
        background-color: #F8DC6F;
        text-align: left;
        margin-bottom: 12px;
        color: #000;
        box-shadow: rgba(99, 99, 99, 0.2) 0px 2px 8px 0px;
    }
    </style>

    <div class="wrap fs-section">
        <h2 class="nav-tab-wrapper">
            <?php
            \WPAICG\wpaicg_util_core()->wpaicg_tabs('wpaicg_finetune', array(
                'manual' => esc_html__('Data Entry','gpt3-ai-content-generator'),
                'files' => esc_html__('Datasets','gpt3-ai-content-generator'),
                'fine-tunes' => esc_html__('Trainings','gpt3-ai-content-generator')
            ), $wpaicg_action);
            ?>
        </h2>
        <div id="poststuff">
            <?php
            include(WPAICG_PLUGIN_DIR.'admin/views/finetune/'.$wpaicg_action.'.php');
            ?>
        </div>
    </div>

    <?php
}
?>
