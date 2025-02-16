<?php
if (!defined('ABSPATH')) {
    exit;
}
?>
<?php
$fileTypes = array(
    'fine-tune' => esc_html__('Fine-Tune','gpt3-ai-content-generator'),
    'fine-tune-results' => esc_html__('Fine-Tune Results','gpt3-ai-content-generator'),
    'assistants' => esc_html__('Assistants','gpt3-ai-content-generator'),
    'assistants_output' => esc_html__('Assistants Output','gpt3-ai-content-generator')
);
$wpaicgMaxFileSize = wp_max_upload_size();
if($wpaicgMaxFileSize > 104857600){
    $wpaicgMaxFileSize = 104857600;
}
?>
<style>
    .wpaicg_form_upload_file{
        background: #e3e3e3;
        padding: 10px;
        border-radius: 4px;
        border: 1px solid #ccc;
        margin-bottom: 20px;
    }
    .wpaicg_form_upload_file table{
        max-width: 500px
    }
    .wpaicg_form_upload_file table th{
        padding: 5px;
    }
    .wpaicg_form_upload_file table td{
        padding: 5px;
    }
</style>
<style>
    #wpaicg_form_data{
        max-width: 900px;
    }
    .wpaicg_list_data{
        padding: 10px;
        background: #e1e1e1;
        border: 1px solid #ccc;
        border-radius: 5px;
    }
    .wpaicg_data_item:after{
        clear: both;
        display: block;
        content: '';
    }
    .wpaicg_data_item input{
        flex: 1;
    }
    .wpaicg_data_item > div{
        float: left;
        width: calc(50% - 2px);
        margin-right: 2px;
        margin-bottom: 5px;
        display: flex;
    }
    .gpt-turbo .wpaicg_data_item > div{
        width: calc(33% - 2px);
    }
    .wpaicg-convert-progress{
        height: 15px;
        background: #10b981;
        border-radius: 5px;
        color: #fff;
        padding: 2px 12px;
        position: relative;
        font-size: 12px;
        text-align: center;
        margin-bottom: 10px;
        display: none;
        margin-top: 10px;
    }
    .wpaicg-convert-progress.wpaicg_error span{
        background: #bb0505;
    }
    .wpaicg-convert-progress span{
        display: block;
        position: absolute;
        height: 100%;
        border-radius: 5px;
        background: #2271b1;
        top: 0;
        left: 0;
        transition: width .6s ease;
    }
    .wpaicg-convert-progress small{
        position: relative;
        font-size: 12px;
    }
    #wpaicg_form_data span.button-link-delete {
        display:none;
    }
</style>
<div class="nice-form-group">
    <label for="wpaicg_input_method"><?php echo esc_html__('Input Method', 'gpt3-ai-content-generator') ?></label>
    <select id="wpaicg_input_method" style="width: 30%;">
        <option value="manual_entry"><?php echo esc_html__('Manual Entry', 'gpt3-ai-content-generator') ?></option>
        <option value="upload"><?php echo esc_html__('Upload', 'gpt3-ai-content-generator') ?></option>
    </select>
</div>
<p>
<div id="wpaicg-finetune-success-message" class="wpaicg-finetune-success-message">
    <?php echo esc_html__('Upload successful.','gpt3-ai-content-generator')?>
</div>
<div id="wpaicg-finetune-delete-message" class="wpaicg-finetune-delete-message">
    <?php echo esc_html__('File deleted successfully.','gpt3-ai-content-generator')?>
</div>
<div id="wpaicg-finetune-sync-message" class="wpaicg-finetune-sync-message">
    <?php echo esc_html__('Sync successful.','gpt3-ai-content-generator')?>
</div>
</p>
<div id="manual_data_entry_form">
    <form id="wpaicg_form_data" action="" method="post">

        <table class="form-table" style="display:none;">
            <tbody>
            <tr>
                <th scope="row"><?php echo esc_html__('Purpose', 'gpt3-ai-content-generator') ?></th>
                <td>
                    <select name="purpose">
                        <option value="fine-tune"><?php echo esc_html__('Fine-tune', 'gpt3-ai-content-generator') ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row"><?php echo esc_html__('Base Model', 'gpt3-ai-content-generator') ?></th>
                <td>
                    <select name="model">
                        <option value="gpt-3.5-turbo">GPT-3.5 Turbo</option>
                    </select>
                </td>
            </tr>
            <tr style="display:none;">
                <th scope="row"><?php echo esc_html__('Custom Name (Optional)', 'gpt3-ai-content-generator') ?></th>
                <td>
                    <input type="text" name="custom">
                </td>
            </tr>
            </tbody>
        </table>
        <p></p>
        <h1>Training data</h1>
        <small>Prepare and upload training data</small>
        <div class="wpaicg_list_data normal" style="display:none;">
            <div class="wpaicg_data_item">
                <div class="text-center"><strong><?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?></strong></div>
                <div class="text-center"><strong><?php echo esc_html__('Completion', 'gpt3-ai-content-generator') ?></strong></div>
            </div>
            <div class="wpaicg_data_list">
                <div class="wpaicg_data_item wpaicg_data">
                    <div>
                        <input type="text" name="data[0][prompt]" class="regular-text wpaicg_data_prompt" placeholder="<?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?>">
                    </div>
                    <div>
                        <input type="text" name="data[0][completion]" class="regular-text wpaicg_data_completion" placeholder="<?php echo esc_html__('Completion', 'gpt3-ai-content-generator') ?>">
                        <span class="button button-link-delete" style="display: flex;align-items: center;">&times;</span>
                    </div>
                </div>
            </div>
            <button class="button button-secondary wpaicg_add_data" type="button"><?php echo esc_html__('Add More', 'gpt3-ai-content-generator') ?></button>
        </div>

        <div class="wpaicg_list_data gpt-turbo">
            <div class="wpaicg_data_item">
                <div><strong><?php echo esc_html__('System', 'gpt3-ai-content-generator') ?></strong></div>
                <div><strong><?php echo esc_html__('User', 'gpt3-ai-content-generator') ?></strong></div>
                <div><strong><?php echo esc_html__('Assistant', 'gpt3-ai-content-generator') ?></strong></div>
            </div>
            <div class="wpaicg_data_list">
                <div class="wpaicg_data_item wpaicg_data">
                    <div class="nice-form-group">
                        <input type="text" name="data[0][system]" class="wpaicg_data_system" placeholder="<?php echo esc_html__('Lisa is a chatbot that is also sarcastic.', 'gpt3-ai-content-generator') ?>">
                    </div>
                    <div class="nice-form-group">
                        <input type="text" name="data[0][user]" class="wpaicg_data_user" placeholder="<?php echo esc_html__('What is the capital of France?', 'gpt3-ai-content-generator') ?>">
                    </div>
                    <div class="nice-form-group">
                        <input type="text" name="data[0][assistant]" class="wpaicg_data_assistant" placeholder="<?php echo esc_html__('Paris, as if everyone doesnt know that already.', 'gpt3-ai-content-generator') ?>">
                        <span class="button button-link-delete" style="display: flex;align-items: center;">&times;</span>
                    </div>
                </div>
            </div>
            <div class="nice-form-group">
                <button class="button button-secondary wpaicg_add_data" type="button"><?php echo esc_html__('Add More', 'gpt3-ai-content-generator') ?></button>
                <button class="button-primary button wpaicg_submit"><?php echo esc_html__('Save', 'gpt3-ai-content-generator') ?></button>
            </div>
        </div>

        <div class="wpaicg-convert-progress wpaicg-convert-bar">
            <span></span>
            <small>0%</small>
        </div>
        <div class="wpaicg-upload-message"></div>
    </form>
    <form id="wpaicg_upload_convert" style="display: none" action="" method="post">
        <?php wp_nonce_field('wpaicg-ajax-nonce', 'nonce'); ?>
        <input type="hidden" name="action" value="wpaicg_upload_convert">
        <input type="hidden" id="wpaicg_upload_convert_index" name="index" value="1">
        <input id="wpaicg_upload_convert_line" type="hidden" name="line" value="0">
        <input id="wpaicg_upload_convert_lines" type="hidden" value="0">
        <input type="hidden" name="file" value="">
        <input type="hidden" name="purpose" value="fine-tune">
        <input type="hidden" name="model" value="">
        <input type="hidden" name="custom" value="">
    </form>
</div>
<div id="wpaicg_form_upload_file">
    <p></p>
    <h1>Training data</h1>
    <small>Add a jsonl file to use for training.</small>
    <div class="wpaicg_form_upload_file">
        <div class="nice-form-group">
            <input type="file" id="wpaicg_file_upload">
        </div>
        <div class="nice-form-group" style="display: none;">
            <select id="wpaicg_file_purpose">
                <?php
                foreach ($fileTypes as $key=>$fileType){
                    echo '<option value="'.esc_html($key).'">'.esc_html($fileType).'</option>';
                }
                ?>
            </select>
        </div>
        <div class="nice-form-group" style="display: none;">
            <select id="wpaicg_file_model">
                <option value="gpt-3.5-turbo">gpt-3.5-turbo</option>
            </select>
        </div>
        <div class="nice-form-group" style="display: none;">
            <input type="text" id="wpaicg_file_name">
        </div>
        
        <div class="nice-form-group">
            <div class="wpaicg_progress" style="display: none">
                <span></span>
                <small>
                    <?php echo esc_html__('Uploading','gpt3-ai-content-generator')?>
                </small>
            </div>
            <div class="wpaicg-error-msg"></div>
        </div>
        <div class="nice-form-group" style="display: flex;align-items: center;">
            <button class="button button-primary" id="wpaicg_file_button"><?php echo esc_html__('Upload','gpt3-ai-content-generator')?></button>
            <span style="margin-left: 1em;"><?php echo esc_html__('Maximum upload file size:','gpt3-ai-content-generator')?><?php echo size_format($wpaicgMaxFileSize)?></span>
        </div>
    </div>
</div>
<script>
    jQuery(document).ready(function ($){

        function wpaicgSortData(){
            var selectedModel = $('select[name="model"]').val();
            // Check if the selected model is one of the chat models
            var isChatModel = selectedModel === 'gpt-3.5-turbo' || selectedModel.includes('gpt-3.5-turbo-') || selectedModel === 'gpt-4-0613';
            if(isChatModel) {
                $('.wpaicg_list_data.gpt-turbo .wpaicg_data').each(function (idx, item){
                    $(item).find('.wpaicg_data_system').attr('name','data['+idx+'][system]');
                    $(item).find('.wpaicg_data_user').attr('name','data['+idx+'][user]');
                    $(item).find('.wpaicg_data_assistant').attr('name','data['+idx+'][assistant]');
                })
            } else {
                $('.wpaicg_list_data.normal .wpaicg_data').each(function (idx, item){
                    $(item).find('.wpaicg_data_prompt').attr('name','data['+idx+'][prompt]');
                    $(item).find('.wpaicg_data_completion').attr('name','data['+idx+'][completion]');
                })
            }
        }
        function wpaicgLoading(btn){
            btn.attr('disabled','disabled');
            if(!btn.find('spinner').length){
                btn.append('<span class="spinner"></span>');
            }
            btn.find('.spinner').css('visibility','unset');
        }
        function wpaicgRmLoading(btn){
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }
        var progressBar = $('.wpaicg-convert-bar');
        var wpaicg_add_data = $('.normal .wpaicg_add_data');
        var wpaicg_add_gtp_turbo_data = $('.gpt-turbo .wpaicg_add_data');
        var wpaicg_ajax_url = '<?php echo admin_url('admin-ajax.php') ?>';
        var form = $('#wpaicg_form_data');
        var wpaicg_item = '<div class="wpaicg_data_item wpaicg_data"><div><input type="text" name="data[0][prompt]" class="wpaicg_data_prompt" placeholder="<?php echo esc_html__('Prompt', 'gpt3-ai-content-generator') ?>"> </div><div><input type="text" name="data[0][completion]" class="wpaicg_data_completion" placeholder="<?php echo esc_html__('Completion', 'gpt3-ai-content-generator') ?>"><span class="button button-link-delete" style="display: flex;align-items: center;">×</span></div></div>';
        var wpaicg_gpt_turbo_item = '<div class="wpaicg_data_item wpaicg_data"><div class="nice-form-group"><input type="text" name="data[0][system]" class="wpaicg_data_system"> </div><div class="nice-form-group"><input type="text" name="data[0][user]" class="wpaicg_data_user"> </div><div class="nice-form-group"><input type="text" name="data[0][assistant]" class="wpaicg_data_assistant"><span class="button button-link-delete" style="display: flex;align-items: center;">×</span></div></div>';
        wpaicg_add_data.click(function (){
            $('.wpaicg_list_data.normal').find('.wpaicg_data_list').append(wpaicg_item);
            if($('.wpaicg_list_data.normal').find('.wpaicg_data_list').find('.wpaicg_data').length > 1){
                $('.wpaicg_list_data.normal').find('span.button-link-delete').show();
            } else {
                $('.wpaicg_list_data.normal').find('span.button-link-delete').hide();
            }
            wpaicgSortData();
        });
        wpaicg_add_gtp_turbo_data.click(function (){
            $('.wpaicg_list_data.gpt-turbo').find('.wpaicg_data_list').append(wpaicg_gpt_turbo_item);
            if($('.wpaicg_list_data.gpt-turbo').find('.wpaicg_data_list').find('.wpaicg_data').length > 1){
                $('.wpaicg_list_data.gpt-turbo').find('span.button-link-delete').show();
            } else {
                $('.wpaicg_list_data.gpt-turbo').find('span.button-link-delete').hide();
            }
            wpaicgSortData();
        });
        $(document).on('click','.wpaicg_data span', function (e){
            if($(this).closest('.wpaicg_data_list').find('.wpaicg_data').length < 3){
                $(this).closest('.wpaicg_data_list').find('span.button-link-delete').hide();
            } else {
                $(this).closest('.wpaicg_data_list').find('span.button-link-delete').show();
            }
            $(e.currentTarget).parent().parent().remove();
            wpaicgSortData();
        });

        function wpaicgFileUpload(data, btn){
            var wpaicg_upload_convert_index = parseInt($('#wpaicg_upload_convert_index').val());
            $.ajax({
                url: wpaicg_ajax_url,
                data: data,
                type: 'POST',
                dataType: 'JSON',
                success: function (res){
                    if(res.status === 'success'){
                        if(res.next === 'DONE'){
                            var selectedModel = $('select[name=model]').val();
                            var isChatModel = selectedModel === 'gpt-3.5-turbo' || selectedModel.includes('gpt-3.5-turbo-') || selectedModel === 'gpt-4-0613';
                            if(isChatModel){
                                $('.wpaicg_list_data.gpt-turbo .wpaicg_data_list').html(wpaicg_gpt_turbo_item);
                            } else {
                                $('.wpaicg_list_data.normal .wpaicg_data_list').html(wpaicg_item);
                            }
                            // display success message with timeout. wpaicg-finetune-success-message
                            $('#wpaicg-finetune-success-message').show();
                            setTimeout(function() {
                                $('#wpaicg-finetune-success-message').hide();
                            }, 5000);
                            progressBar.find('small').html('100%');
                            progressBar.find('span').css('width','100%');
                            wpaicgRmLoading(btn);
                            setTimeout(function (){
                                $('#wpaicg_upload_convert_line').val('0');
                                $('#wpaicg_upload_convert_index').val('1');
                                progressBar.hide();
                                progressBar.removeClass('wpaicg_error')
                                progressBar.find('span').css('width',0);
                                progressBar.find('small').html('0%');
                            },2000);
                        }
                        else{
                            $('#wpaicg_upload_convert_line').val(res.next);
                            $('#wpaicg_upload_convert_index').val(wpaicg_upload_convert_index+1);
                            var data = $('#wpaicg_upload_convert').serialize();
                            wpaicgFileUpload(data,btn);
                        }
                    }
                    else{
                        progressBar.addClass('wpaicg_error');
                        wpaicgRmLoading(btn);
                        alert(res.msg);
                    }
                },
                error: function (){
                    progressBar.addClass('wpaicg_error');
                    wpaicgRmLoading(btn);
                    alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                }
            })
        }

        function wpaicgProcessData(lists,start,file,btn){
            var purpose = $('select[name=purpose]').val();
            var model = $('select[name=model]').val();
            var name = $('input[name=custom]').val();

            var data = {
                action: 'wpaicg_data_insert',
                model: model,
                file: file,
                nonce: '<?php echo wp_create_nonce('wpaicg-ajax-nonce') ?>'
            };

            var isChatModel = model === 'gpt-3.5-turbo' || model.includes('gpt-3.5-turbo-') || model === 'gpt-4-0613';

            if(isChatModel){
                data['messages'] = lists[start].messages;
            } else {
                data['prompt'] = lists[start].prompt;
                data['completion'] = lists[start].completion;
            }

            $.ajax({
                url: wpaicg_ajax_url,
                data: data,
                dataType: 'JSON',
                type: 'POST',
                success: function (res){
                    if(res.status === 'success'){
                        var percent = Math.ceil((start+1)*90/lists.length);
                        progressBar.find('small').html(percent+'%');
                        progressBar.find('span').css('width',percent+'%');
                        if((start+1) === lists.length){
                            /*Save file done*/
                            $('#wpaicg_upload_convert input[name=model]').val(model);
                            $('#wpaicg_upload_convert input[name=purpose]').val(purpose);
                            $('#wpaicg_upload_convert input[name=custom]').val(name);
                            $('#wpaicg_upload_convert input[name=file]').val(res.file);
                            var data = $('#wpaicg_upload_convert').serialize();
                            wpaicgFileUpload(data, btn);
                        }
                        else{
                            file = res.file;
                            wpaicgProcessData(lists,(start+1),file, btn);
                        }
                    }
                    else{
                        progressBar.addClass('wpaicg_error');
                        wpaicgRmLoading(btn);
                        alert(res.msg);
                    }
                },
                error: function (){
                    progressBar.addClass('wpaicg_error');
                    wpaicgRmLoading(btn);
                    alert('<?php echo esc_html__('Something went wrong', 'gpt3-ai-content-generator') ?>');
                }
            })
        }
        form.on('submit', function (){
            var total = 0;
            var lists = [];
            var btn = form.find('.wpaicg_submit');
            var modelValue = $('select[name="model"]').val();

            // Check if the selected model is a chat model
            var isChatModel = modelValue === 'gpt-3.5-turbo' || modelValue.includes('gpt-3.5-turbo-') || modelValue === 'gpt-4-0613';
            if(isChatModel) {
                $('.wpaicg_list_data.gpt-turbo .wpaicg_data').each(function (idx, item){
                    var item_system = $(item).find('.wpaicg_data_system').val();
                    var item_user = $(item).find('.wpaicg_data_user').val();
                    var item_assistant = $(item).find('.wpaicg_data_assistant').val();
                    if(item_system !== '' && item_user !== '' && item_assistant !== ''){
                        total += 1;
                        lists.push({"messages":[{role: "system", content: item_system }, {role: "user", content: item_user }, {role: "assistant", content: item_assistant }]})
                    }
                });

            } else {
                $('.wpaicg_list_data.normal .wpaicg_data').each(function (idx, item){
                    var item_prompt = $(item).find('.wpaicg_data_prompt').val();
                    var item_completion = $(item).find('.wpaicg_data_completion').val();
                    if(item_prompt !== '' && item_completion !== ''){
                        total += 1;
                        lists.push({prompt: item_prompt,completion: item_completion })
                    }
                });
            }

            if(total >= 10){
                $('#wpaicg_upload_convert_line').val('0');
                $('#wpaicg_upload_convert_index').val('1');
                $('.wpaicg-upload-message').empty();
                progressBar.show();
                progressBar.removeClass('wpaicg_error')
                progressBar.find('span').css('width',0);
                progressBar.find('small').html('0%');
                wpaicgLoading(btn)
                wpaicgProcessData(lists,0,'',btn);
            }
            else{
                alert('<?php echo esc_html__('Please insert least 10 rows', 'gpt3-ai-content-generator') ?>');
            }
            return false;
        })

        $(document).on('change','select[name="model"]', function (e){
            var modelValue = $(this).val();

            // Determine if the selected model is a chat model
            var isChatModel = modelValue.includes("gpt-3.5-turbo") || modelValue.includes("gpt-4-0613"); // Update this condition based on your model naming conventions

            if(isChatModel){
                // Show chat model specific UI
                $('.wpaicg_list_data.gpt-turbo').show();
                $('.wpaicg_list_data.normal').hide();
            } else {
                // Show completion model specific UI
                $('.wpaicg_list_data.normal').show();
                $('.wpaicg_list_data.gpt-turbo').hide();
            }
        });


    })
</script>
<script>
    jQuery(document).ready(function ($){

        function wpaicgLoading(btn){
            btn.attr('disabled','disabled');
            if(!btn.find('spinner').length){
                btn.append('<span class="spinner"></span>');
            }
            btn.find('.spinner').css('visibility','unset');
        }
        function wpaicgRmLoading(btn){
            btn.removeAttr('disabled');
            btn.find('.spinner').remove();
        }
        var wpaicg_max_file_size = <?php echo esc_html($wpaicgMaxFileSize)?>;
        var wpaicg_max_size_in_mb = '<?php echo size_format(esc_html($wpaicgMaxFileSize))?>';
        var wpaicg_file_button = $('#wpaicg_file_button');
        var wpaicg_file_upload = $('#wpaicg_file_upload');
        var wpaicg_file_purpose = $('#wpaicg_file_purpose');
        var wpaicg_file_name = $('#wpaicg_file_name');
        var wpaicg_file_model = $('#wpaicg_file_model');
        var wpaicg_progress = $('.wpaicg_progress');
        var wpaicg_error_message = $('.wpaicg-error-msg');
        var wpaicg_create_fine_tune = $('.wpaicg_create_fine_tune');
        var wpaicg_retrieve_content = $('.wpaicg_retrieve_content');
        var wpaicg_delete_file = $('.wpaicg_delete_file');
        var wpaicg_ajax_url = '<?php echo admin_url('admin-ajax.php')?>';
        wpaicg_file_button.click(function (){
            if(wpaicg_file_upload[0].files.length === 0){
                alert('<?php echo esc_html__('Please select file','gpt3-ai-content-generator')?>');
            }
            else{
                var wpaicg_file = wpaicg_file_upload[0].files[0];
                var wpaicg_file_extension = wpaicg_file.name.substr( (wpaicg_file.name.lastIndexOf('.') +1) );
                if(wpaicg_file_extension !== 'jsonl'){
                    wpaicg_file_upload.val('');
                    alert('<?php echo esc_html__('Please upload only JSONL files.','gpt3-ai-content-generator')?>');
                }
                else if(wpaicg_file.size > wpaicg_max_file_size){
                    wpaicg_file_upload.val('');
                    alert('<?php echo esc_html__('Maximum file size: ','gpt3-ai-content-generator')?> '+wpaicg_max_size_in_mb)
                }
                else{
                    var formData = new FormData();
                    formData.append('action', 'wpaicg_finetune_upload');
                    formData.append('file', wpaicg_file);
                    formData.append('purpose', wpaicg_file_purpose.val());
                    formData.append('model', wpaicg_file_model.val());
                    formData.append('name', wpaicg_file_name.val());
                    formData.append('nonce','<?php echo wp_create_nonce('wpaicg-ajax-nonce')?>');
                    $.ajax({
                        url: wpaicg_ajax_url,
                        type: 'POST',
                        dataType: 'JSON',
                        data: formData,
                        beforeSend: function (){
                            wpaicg_progress.find('span').css('width','0');
                            wpaicg_progress.show();
                            wpaicgLoading(wpaicg_file_button);
                            wpaicg_error_message.hide();
                        },
                        xhr: function() {
                            var xhr = $.ajaxSettings.xhr();
                            xhr.upload.addEventListener("progress", function(evt) {
                                if (evt.lengthComputable) {
                                    var percentComplete = evt.loaded / evt.total;
                                    wpaicg_progress.find('span').css('width',(Math.round(percentComplete * 100))+'%');
                                }
                            }, false);
                            return xhr;
                        },
                        success: function(res) {
                            if(res.status === 'success'){
                                wpaicgRmLoading(wpaicg_file_button);
                                wpaicg_progress.hide();
                                wpaicg_file_upload.val('');
                                // display success message with timeout. wpaicg-finetune-success-message
                                $('#wpaicg-finetune-success-message').show();
                                setTimeout(function() {
                                    $('#wpaicg-finetune-success-message').hide();
                                }, 5000);
                            }
                            else{
                                wpaicgRmLoading(wpaicg_file_button);
                                wpaicg_progress.find('small').html('<?php echo esc_html__('Error','gpt3-ai-content-generator')?>');
                                wpaicg_progress.addClass('wpaicg_error');
                                wpaicg_error_message.html(res.msg);
                                wpaicg_error_message.show();
                            }
                        },
                        cache: false,
                        contentType: false,
                        processData: false,
                        error: function (){
                            wpaicg_file_upload.val('');
                            wpaicgRmLoading(wpaicg_file_button);
                            wpaicg_progress.addClass('wpaicg_error');
                            wpaicg_progress.find('small').html('Error');
                            wpaicg_error_message.html('<?php echo esc_html__('Something went wrong','gpt3-ai-content-generator')?>');
                            wpaicg_error_message.show();
                        }
                    });
                }
            }
        })
    })
</script>
<script>
    jQuery(document).ready(function ($) {
        // Event handler for changing the input method
        $('#wpaicg_input_method').change(function() {
            if($(this).val() === 'upload') {
                // Hide manual entry fields
                $('#manual_data_entry_form').hide();
                // Show upload fields
                $('#wpaicg_form_upload_file').show();
            } else {
                // Show manual entry fields
                $('#manual_data_entry_form').show();
                // Hide upload fields
                $('#wpaicg_form_upload_file').hide();
            }
        });

        // Trigger change event on page load to set the initial state correctly
        $('#wpaicg_input_method').trigger('change');
    });
</script>
