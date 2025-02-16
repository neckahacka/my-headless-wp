<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Updated: This file now includes a switch-based UI for toggles,
 * plus conditional display of token allocation fields, and a
 * more scrollable modal window for role-limits.
 */

$success = false;
if ( isset($_POST['wpaicg_limit_tokens']) && ! empty($_POST['wpaicg_limit_tokens']) ) {
    // Check the nonce
    if ( ! isset($_POST['wpaicg_limit_tokens_nonce']) || 
         ! wp_verify_nonce($_POST['wpaicg_limit_tokens_nonce'], 'wpaicg_limit_tokens_action') ) 
    {
        wp_die(__('Nonce verification failed.', 'gpt3-ai-content-generator'));
    }

    // Mark successful update
    $success = true;

    // Sanitize and update the "limit tokens" form settings
    $wpaicg_limit_tokens = \WPAICG\wpaicg_util_core()->sanitize_text_or_array_field($_POST['wpaicg_limit_tokens']);
    update_option('wpaicg_limit_tokens_form', $wpaicg_limit_tokens);

    // Enable token purchasing
    if ( isset($_POST['wpaicg_forms_enable_sale']) && ! empty($_POST['wpaicg_forms_enable_sale']) ) {
        update_option('wpaicg_forms_enable_sale', sanitize_text_field($_POST['wpaicg_forms_enable_sale']));
    } else {
        delete_option('wpaicg_forms_enable_sale');
    }
}

// Retrieve existing settings
$wpaicg_settings          = get_option('wpaicg_limit_tokens_form', []);
$wpaicg_forms_enable_sale = get_option('wpaicg_forms_enable_sale', false);
$wpaicg_roles             = wp_roles()->get_names();

// For conditional display of rows (server-side):
$display_user_token_tr  = (!empty($wpaicg_settings['user_limited'])) ? '' : 'style="display:none;"';
$display_role_limit_tr  = (!empty($wpaicg_settings['role_limited'])) ? '' : 'style="display:none;"';
$display_guest_token_tr = (!empty($wpaicg_settings['guest_limited'])) ? '' : 'style="display:none;"';

// Retrieve existing Google CSE settings
$current_google_api_key = get_option('wpaicg_google_api_key', '');
$current_google_search_engine_id = get_option('wpaicg_google_search_engine_id', '');
$current_google_search_country = get_option('wpaicg_google_search_country', '');
$current_google_search_language = get_option('wpaicg_google_search_language', '');
$current_google_search_num = get_option('wpaicg_google_search_num', 10);
// For the region/language dropdowns, re-use from WPAICG_Util (like the chatbot settings do)
$cse_countries  = \WPAICG\WPAICG_Util::get_instance()->wpaicg_countries;
$cse_languages  = \WPAICG\WPAICG_Util::get_instance()->search_languages;
?>
<!-- The container remains hidden by default; it is shown via JS in the AI Forms Beta UI -->
<div id="wpaicg_settings_container" style="display:none;">
    <?php if ( $success ) : ?>
        <div class="notice notice-success">
            <p><?php echo esc_html__('Settings updated successfully.', 'gpt3-ai-content-generator'); ?></p>
        </div>
    <?php endif; ?>

    <h2><?php echo esc_html__('Token Management', 'gpt3-ai-content-generator'); ?></h2>
    <form action="" method="post" id="wpaicg_settings_form">
        <?php wp_nonce_field('wpaicg_limit_tokens_action', 'wpaicg_limit_tokens_nonce'); ?>
        <table class="form-table">
            <!-- Limit Registered User -->
            <tr>
                <th><?php echo esc_html__('Limit Registered User', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <label class="wpaicg-switch">
                        <input
                            type="checkbox"
                            value="1"
                            class="wpaicg_user_token_limit"
                            name="wpaicg_limit_tokens[user_limited]"
                            <?php echo (isset($wpaicg_settings['user_limited']) && $wpaicg_settings['user_limited']) ? ' checked' : ''; ?>
                        />
                        <span class="slider"></span>
                    </label>
                </td>
            </tr>

            <!-- Token Allocation (for registered user) -->
            <tr class="wpaicg_user_token_tr" <?php echo $display_user_token_tr; ?>>
                <th><?php echo esc_html__('Token Allocation', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <input
                        type="number"
                        min="0"
                        style="width:150px"
                        class="wpaicg_user_token_limit_text"
                        name="wpaicg_limit_tokens[user_tokens]"
                        value="<?php echo isset($wpaicg_settings['user_tokens']) ? esc_html($wpaicg_settings['user_tokens']) : ''; ?>"
                        <?php echo (isset($wpaicg_settings['user_limited']) && $wpaicg_settings['user_limited']) ? '' : ' disabled'; ?>
                    />
                </td>
            </tr>
            <tr class="wpaicg_user_token_tr" <?php echo $display_user_token_tr; ?>>
                <th></th>
                <td>
                    <small><em><?php echo esc_html__('Leave empty for unlimited tokens. Specify a number for a set limit.', 'gpt3-ai-content-generator'); ?></em></small>
                </td>
            </tr>

            <!-- Token Allocation by Role -->
            <tr>
                <th><?php echo esc_html__('Token Allocation by Role', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <?php
                    // Hidden inputs for each role's token limit
                    foreach ($wpaicg_roles as $key => $wpaicg_role) {
                        $value = isset($wpaicg_settings['limited_roles'][$key]) ? $wpaicg_settings['limited_roles'][$key] : '';
                        echo '<input type="hidden" name="wpaicg_limit_tokens[limited_roles]['.esc_attr($key).']" value="'.esc_attr($value).'" class="wpaicg_role_'.esc_attr($key).'" />';
                    }
                    ?>
                    <label class="wpaicg-switch">
                        <input
                            type="checkbox"
                            value="1"
                            class="wpaicg_role_limited"
                            name="wpaicg_limit_tokens[role_limited]"
                            <?php echo (isset($wpaicg_settings['role_limited']) && $wpaicg_settings['role_limited']) ? ' checked' : ''; ?>
                        />
                        <span class="slider"></span>
                    </label>
                    <a
                        href="javascript:void(0)"
                        class="wpaicg_limit_set_role<?php echo ((!isset($wpaicg_settings['role_limited']) || !$wpaicg_settings['role_limited']) ? ' disabled' : ''); ?>"
                        style="margin-left:8px;"
                    >
                        <?php echo esc_html__('Configure Role Limits', 'gpt3-ai-content-generator'); ?>
                    </a>
                </td>
            </tr>

            <!-- Limit Non-Registered User -->
            <tr>
                <th><?php echo esc_html__('Limit Non-Registered User', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <label class="wpaicg-switch">
                        <input
                            type="checkbox"
                            value="1"
                            class="wpaicg_guest_token_limit"
                            name="wpaicg_limit_tokens[guest_limited]"
                            <?php echo (isset($wpaicg_settings['guest_limited']) && $wpaicg_settings['guest_limited']) ? ' checked' : ''; ?>
                        />
                        <span class="slider"></span>
                    </label>
                </td>
            </tr>

            <!-- Token Allocation (guest) -->
            <tr class="wpaicg_guest_token_tr" <?php echo $display_guest_token_tr; ?>>
                <th><?php echo esc_html__('Token Allocation', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <input
                        type="number"
                        min="0"
                        style="width:150px"
                        class="wpaicg_guest_token_limit_text"
                        name="wpaicg_limit_tokens[guest_tokens]"
                        value="<?php echo isset($wpaicg_settings['guest_tokens']) ? esc_html($wpaicg_settings['guest_tokens']) : ''; ?>"
                        <?php echo (isset($wpaicg_settings['guest_limited']) && $wpaicg_settings['guest_limited']) ? '' : ' disabled'; ?>
                    />
                </td>
            </tr>
            <tr class="wpaicg_guest_token_tr" <?php echo $display_guest_token_tr; ?>>
                <th></th>
                <td>
                    <small><em><?php echo esc_html__('Leave empty for unlimited tokens. Specify a number for a set limit.', 'gpt3-ai-content-generator'); ?></em></small>
                </td>
            </tr>

            <!-- Notification Message -->
            <tr>
                <th><?php echo esc_html__('Notification Message', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <textarea
                        style="width:300px; height:100px;"
                        name="wpaicg_limit_tokens[limited_message]"
                    ><?php echo isset($wpaicg_settings['limited_message']) ? esc_html(wp_unslash($wpaicg_settings['limited_message'])) : ''; ?></textarea>
                </td>
            </tr>

            <!-- Token Reset Interval -->
            <tr>
                <th><?php echo esc_html__('Token Reset Interval', 'gpt3-ai-content-generator'); ?>:</th>
                <td>
                    <?php
                    $options = [
                        0   => esc_html__('Never', 'gpt3-ai-content-generator'),
                        1   => esc_html__('1 Day', 'gpt3-ai-content-generator'),
                        3   => esc_html__('3 Days', 'gpt3-ai-content-generator'),
                        7   => esc_html__('1 Week', 'gpt3-ai-content-generator'),
                        14  => esc_html__('2 Weeks', 'gpt3-ai-content-generator'),
                        30  => esc_html__('1 Month', 'gpt3-ai-content-generator'),
                        60  => esc_html__('2 Months', 'gpt3-ai-content-generator'),
                        90  => esc_html__('3 Months', 'gpt3-ai-content-generator'),
                        180 => esc_html__('6 Months', 'gpt3-ai-content-generator'),
                    ];
                    ?>
                    <select name="wpaicg_limit_tokens[reset_limit]">
                        <?php foreach ($options as $value => $label): ?>
                            <option
                                value="<?php echo $value; ?>"
                                <?php echo (isset($wpaicg_settings['reset_limit']) && (int)$wpaicg_settings['reset_limit'] === $value) ? ' selected' : ''; ?>
                            >
                                <?php echo $label; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Enable Token Purchasing -->
            <tr>
                <th><?php echo esc_html__('Enable Token Purchasing', 'gpt3-ai-content-generator'); ?></th>
                <td>
                    <label class="wpaicg-switch">
                        <input
                            type="checkbox"
                            value="1"
                            class="wpaicg_forms_enable_sale"
                            name="wpaicg_forms_enable_sale"
                            <?php echo $wpaicg_forms_enable_sale ? ' checked' : ''; ?>
                        />
                        <span class="slider"></span>
                    </label>
                </td>
            </tr>
            </tr>
        </table>
                <!-- ============= INTERNET BROWSING SECTION ============= -->
                <h2 style="margin-top:30px;">
            <?php echo esc_html__('Internet Browsing', 'gpt3-ai-content-generator'); ?>
        </h2>
        <p><?php echo esc_html__('These settings are shared across all AI Forms and the Chatbot module.', 'gpt3-ai-content-generator'); ?></p>

        <table class="form-table">
            <!-- Google API Key -->
            <tr>
                <th><?php echo esc_html__('Google API Key', 'gpt3-ai-content-generator'); ?></th>
                <td>
                    <input
                        type="text"
                        name="wpaicg_google_api_key"
                        style="width:300px;"
                        value="<?php echo esc_attr($current_google_api_key); ?>"
                    />
                    <p>
                        <small>
                            <a href="https://console.cloud.google.com/" target="_blank">
                                <?php echo esc_html__('Get API Key', 'gpt3-ai-content-generator'); ?>
                            </a>
                        </small>
                    </p>
                </td>
            </tr>

            <!-- Google CSE ID -->
            <tr>
                <th><?php echo esc_html__('Google Custom Search Engine ID', 'gpt3-ai-content-generator'); ?></th>
                <td>
                    <input
                        type="text"
                        name="wpaicg_google_search_engine_id"
                        style="width:300px;"
                        value="<?php echo esc_attr($current_google_search_engine_id); ?>"
                    />
                    <p>
                        <small>
                            <a href="https://programmablesearchengine.google.com/" target="_blank">
                                <?php echo esc_html__('Get CSE ID', 'gpt3-ai-content-generator'); ?>
                            </a>
                        </small>
                    </p>
                </td>
            </tr>

            <!-- Region -->
            <tr>
                <th><?php echo esc_html__('Region', 'gpt3-ai-content-generator'); ?></th>
                <td>
                    <select name="wpaicg_google_search_country">
                        <?php foreach ($cse_countries as $value => $label): ?>
                            <option
                                value="<?php echo esc_attr($value); ?>"
                                <?php selected($current_google_search_country, $value); ?>
                            >
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Language -->
            <tr>
                <th><?php echo esc_html__('Language', 'gpt3-ai-content-generator'); ?></th>
                <td>
                    <select name="wpaicg_google_search_language">
                        <?php foreach ($cse_languages as $value => $label): ?>
                            <option
                                value="<?php echo esc_attr($value); ?>"
                                <?php selected($current_google_search_language, $value); ?>
                            >
                                <?php echo esc_html($label); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </td>
            </tr>

            <!-- Results -->
            <tr>
                <th><?php echo esc_html__('Results', 'gpt3-ai-content-generator'); ?></th>
                <td>
                    <select name="wpaicg_google_search_num">
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option
                                value="<?php echo $i; ?>"
                                <?php selected($current_google_search_num, $i); ?>
                            >
                                <?php echo $i; ?>
                            </option>
                        <?php endfor; ?>
                    </select>
                    <p><small><?php echo esc_html__('How many Google search results to retrieve.', 'gpt3-ai-content-generator'); ?></small></p>
                </td>
            </tr>
        </table>
    </form>

    <!-- Modal overlay for role-limit configuration -->
    <div class="wpaicg-overlay-second" style="display:none;">
        <div class="wpaicg_modal_second" style="display:none;">
            <span class="wpaicg_modal_close_second" style="cursor:pointer;position:absolute;top:8px;right:8px;font-size:20px;font-weight:bold;">&times;</span>
            <h3 class="wpaicg_modal_title_second"></h3>
            <div class="wpaicg_modal_content_second"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function ($){
    let wpaicg_roles = <?php echo wp_json_encode($wpaicg_roles); ?>;
    $('.wpaicg_modal_close_second').on('click', function () {
        $(this).closest('.wpaicg_modal_second').hide();
        $('.wpaicg-overlay-second').hide();
    });

    // Allow only numbers (and dot) in numeric fields
    $(document).on('keypress', '.wpaicg_user_token_limit_text, .wpaicg_update_role_limit, .wpaicg_guest_token_limit_text', function (e){
        var charCode = (e.which) ? e.which : e.keyCode;
        // 46 is "."
        if (charCode > 31 && (charCode < 48 || charCode > 57) && charCode !== 46) {
            return false;
        }
        return true;
    });

    // Role-limit modal
    $('.wpaicg_limit_set_role').on('click', function (){
        if (!$(this).hasClass('disabled')) {
            if ($('.wpaicg_role_limited').prop('checked')) {
                let html = '';
                $.each(wpaicg_roles, function (key, role) {
                    let valueRole = $('.wpaicg_role_' + key).val();
                    html += '<div style="padding: 5px; display:flex; justify-content:space-between; align-items:center;">'
                        + '<label><strong>' + role + '</strong></label>'
                        + '<input class="wpaicg_update_role_limit" '
                        + ' data-target="' + key + '" '
                        + ' value="' + valueRole + '" '
                        + ' placeholder="<?php echo esc_html__('Leave empty for unlimited tokens', 'gpt3-ai-content-generator'); ?>" '
                        + ' type="text" '
                        + ' style="width:120px;" />'
                        + '</div>';
                });
                html += '<div style="padding: 5px">'
                    + '<button class="button button-primary wpaicg_save_role_limit" style="width:100%;margin:5px 0;">'
                    + '<?php echo esc_html__('Save', 'gpt3-ai-content-generator'); ?>'
                    + '</button></div>';
                $('.wpaicg_modal_title_second').html('<?php echo esc_html__('Role Limits', 'gpt3-ai-content-generator'); ?>');
                $('.wpaicg_modal_content_second').html(html);
                $('.wpaicg-overlay-second').css('display','flex');
                $('.wpaicg_modal_second').show();
            } else {
                // If role-limited is not checked, then reset all role values
                $.each(wpaicg_roles, function (key, role) {
                    $('.wpaicg_role_' + key).val('');
                });
            }
        }
    });

    // Save role-limit from modal
    $(document).on('click', '.wpaicg_save_role_limit', function (e){
        e.preventDefault();
        $('.wpaicg_update_role_limit').each(function (){
            let input = $(this);
            let target = input.data('target');
            $('.wpaicg_role_' + target).val(input.val());
        });
        $('.wpaicg_modal_close_second').closest('.wpaicg_modal_second').hide();
        $('.wpaicg-overlay-second').hide();
    });

    // Guest limit toggle
    $('.wpaicg_guest_token_limit').on('click', function (){
        if ($(this).prop('checked')) {
            $('.wpaicg_guest_token_tr').show();
            $('.wpaicg_guest_token_limit_text').removeAttr('disabled');
        } else {
            $('.wpaicg_guest_token_tr').hide();
            $('.wpaicg_guest_token_limit_text').val('');
            $('.wpaicg_guest_token_limit_text').attr('disabled','disabled');
        }
    });

    // Role-limited toggle
    $('.wpaicg_role_limited').on('click', function (){
        if ($(this).prop('checked')) {
            // Disable user-limited
            $('.wpaicg_user_token_limit').prop('checked', false).trigger('change');
            // Enable role-limits link
            $('.wpaicg_limit_set_role').removeClass('disabled');
        } else {
            // If role-limited is off, disable the link
            $('.wpaicg_limit_set_role').addClass('disabled');
        }
    });

    // If user-limited is toggled, uncheck role-limited
    $('.wpaicg_user_token_limit').on('click', function (){
        if ($(this).prop('checked')) {
            // Turn off role-limited
            $('.wpaicg_role_limited').prop('checked', false);
            $('.wpaicg_limit_set_role').addClass('disabled');
        }
    });

    // Extra logic to show/hide the user token row
    $('.wpaicg_user_token_limit').on('change', function (){
        if ($(this).prop('checked')) {
            $('.wpaicg_user_token_tr').show();
            $('.wpaicg_user_token_limit_text').removeAttr('disabled');
        } else {
            $('.wpaicg_user_token_tr').hide();
            $('.wpaicg_user_token_limit_text').val('');
            $('.wpaicg_user_token_limit_text').attr('disabled','disabled');
        }
    });
});
</script>