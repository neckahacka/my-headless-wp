<?php
if ( ! defined( 'ABSPATH' ) ) exit;
define( 'WPAICG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'WPAICG_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once __DIR__.'/classes/wpaicg_util.php';
require_once __DIR__.'/classes/wpaicg_generator.php';
require_once __DIR__.'/classes/wpaicg_content.php';
require_once __DIR__.'/classes/wpaicg_custom_prompt.php';
require_once __DIR__.'/classes/wpaicg_cron.php';
require_once __DIR__.'/classes/wpaicg_chat.php';
require_once __DIR__.'/classes/wpaicg_image.php';
require_once __DIR__.'/classes/wpaicg_forms.php';
require_once __DIR__.'/classes/wpaicg_promptbase.php';
require_once __DIR__.'/classes/wpaicg_playground.php';
require_once __DIR__.'/classes/wpaicg_embeddings.php';
require_once __DIR__.'/classes/wpaicg_finetune.php';
require_once __DIR__.'/classes/wpaicg_audio.php';
require_once __DIR__.'/classes/wpaicg_roles.php';
require_once __DIR__.'/classes/wpaicg_account.php';
require_once __DIR__.'/classes/wpaicg_frontend.php';
require_once __DIR__.'/classes/wpaicg_woocommerce.php';
require_once __DIR__.'/classes/wpaicg_regenerate_title.php';
require_once __DIR__.'/classes/wpaicg_comment.php';
require_once __DIR__.'/classes/wpaicg_hook.php';
require_once __DIR__.'/classes/wpaicg_search.php';
require_once __DIR__.'/classes/wpaicg_template.php';
require_once __DIR__.'/classes/wpaicg_editor.php';
require_once __DIR__.'/classes/wpaicg_elevenlabs.php';
require_once __DIR__.'/classes/wpaicg_google_speech.php';
require_once __DIR__.'/classes/wpaicg_openai_speech.php';
require_once __DIR__.'/classes/wpaicg_troubleshoot.php';
require_once __DIR__.'/classes/wpaicg_assistants.php';
require_once __DIR__.'/classes/wpaicg_qdrant.php';
require_once __DIR__.'/classes/wpaicg_openroutermethod.php';
require_once __DIR__.'/classes/wpaicg_dashboard.php';
require_once __DIR__.'/classes/wpaicg_logs.php';
if(\WPAICG\wpaicg_util_core()->wpaicg_is_pro()){
    if(file_exists(__DIR__.'/lib/wpaicg__premium_only.php')){
        require_once __DIR__.'/lib/wpaicg__premium_only.php';
    }
}
