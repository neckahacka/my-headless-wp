<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
/**
 * Cleaned main JS for the AI Forms Beta interface.
 * Updated to include pagination (100 forms per page) on the main list,
 * plus existing logic for logs, creation, editing, duplication, etc.
 *
 * Note:
 * - We rely on window.wpaicgAllForms (set in wpaicg_forms.php) for the
 *   combined list of built-in + custom forms.
 * - We do client-side filtering (authors, categories, search) and pagination.
 */

?>
<script>
(function($){
    "use strict";

    /*************************************************************
     * GLOBAL SHARED FUNCTIONS
     *************************************************************/
    /**
     * Displays a global message in #wpaicg_global_message.
     *
     * @param {string} type        'error', 'success', or 'info'
     * @param {string} message     Text or HTML to display
     * @param {bool}   persistent  If true, message will remain and NOT fade out.
     */
    window.showGlobalMessage = function(type, message, persistent) {
        var $msg = $('#wpaicg_global_message');
        $msg.removeClass('error success info').addClass(type);
        $msg.html(message).show();

        if (!persistent) {
            setTimeout(function(){
                $msg.fadeOut(400);
            }, 3000);
        }
    };

    window.hideShortcodeSnippet = function() {
        $('#wpaicg_shortcode_snippet').hide();
        $('#wpaicg_snippet_clickable').text('');
    };

    window.showShortcodeSnippet = function(text) {
        $('#wpaicg_snippet_clickable').text(text);
        $('#wpaicg_shortcode_snippet').show();
    };

    /**
     * Copy helper
     */
    window.copyToClipboard = function(text, $messageEl){
        if (navigator.clipboard && navigator.clipboard.writeText) {
            navigator.clipboard.writeText(text).then(function(){
                $messageEl.fadeIn(150).delay(1000).fadeOut(150);
            });
        } else {
            var temp = $('<input>');
            $('body').append(temp);
            temp.val(text).select();
            document.execCommand('copy');
            temp.remove();
            $messageEl.fadeIn(150).delay(1000).fadeOut(150);
        }
    };

    /**
     * Field placeholder replacements used in create/edit logic
     */
    window.replacePlaceholderInText = function($textarea, oldID, newID){
        if(!oldID || !newID) return;
        var val = $textarea.val();
        var oldPlaceholder = new RegExp('\\{'+oldID+'\\}', 'g');
        var newPlaceholder = '{'+newID+'}';
        var updated = val.replace(oldPlaceholder, newPlaceholder);
        $textarea.val(updated);
    };

    window.removePlaceholderFromText = function($textarea, oldID){
        if(!oldID) return;
        var val = $textarea.val();
        var oldPlaceholder = new RegExp('\\{'+oldID+'\\}', 'g');
        var updated = val.replace(oldPlaceholder, '');
        $textarea.val(updated);
    };

    /**
     * Field settings HTML builder (shared by create & edit).
     */
    window.getFieldSettingsHtml = function(type, minVal, maxVal, rowsVal, colsVal, optionsArr) {
        if(typeof minVal === 'undefined')  { minVal = ''; }
        if(typeof maxVal === 'undefined')  { maxVal = ''; }
        if(typeof rowsVal === 'undefined') { rowsVal = ''; }
        if(typeof colsVal === 'undefined') { colsVal = ''; }
        if(!Array.isArray(optionsArr))     { optionsArr = []; }

        var html = '<div class="field_settings">';

        // text/number
        if(type === 'text' || type === 'number') {
            html += '<label>Min: <input type="number" class="min_value" value="'+ minVal +'"/></label>';
            html += '<label>Max: <input type="number" class="max_value" value="'+ maxVal +'"/></label>';
        }
        // textarea
        if(type === 'textarea') {
            html += '<label>Min: <input type="number" class="min_value" value="'+ minVal +'"/></label>';
            html += '<label>Max: <input type="number" class="max_value" value="'+ maxVal +'"/></label>';
            html += '<label>Rows: <input type="number" class="rows_value" value="'+ rowsVal +'"/></label>';
            html += '<label>Cols: <input type="number" class="cols_value" value="'+ colsVal +'"/></label>';
        }
        // checkbox / radio / select
        if(type === 'checkbox' || type === 'radio' || type === 'select') {
            html += '<ul class="options_list">';
            optionsArr.forEach(function(opt){
                opt = opt.replace(/"/g, "&quot;");
                html += '<li><input type="text" class="option_value" value="'+ opt +'" /></li>';
            });
            html += '</ul>';
            html += '<button type="button" class="add_option_btn">+ Add Option</button>';
        }

        // NEW: fileupload
        if(type === 'fileupload') {
            html += '<label><?php echo esc_js(__("Allowed File Types (comma-separated):","gpt3-ai-content-generator")); ?><br/>';
            html += '<input type="text" class="file_types" value="txt,csv,doc,docx" /></label>';
        }

        html += '</div>';
        return html;
    };

    /*************************************************************
     * DOM References
     *************************************************************/
    var $grid    = $('#wpaicg_forms_grid');
    var $preview = $('#wpaicg_preview_panel');
    var $flex    = $('#wpaicg_flex');

    var $shortcodeClickable = $('#wpaicg_snippet_clickable');
    var $shortcodeCopiedMsg = $('#wpaicg_shortcode_copied_msg');

    $shortcodeClickable.on('click', function(){
        var snippetText = $(this).text();
        copyToClipboard(snippetText, $shortcodeCopiedMsg);
    });

    /*************************************************************
     * SHOW/HIDE MENU DROPDOWN
     *************************************************************/
    $('#wpaicg_menu_icon').on('click', function(e) {
        e.stopPropagation();
        $('#wpaicg_menu_dropdown').toggle();
    });
    $(document).on('click', function(e) {
        var $menu       = $('#wpaicg_menu_dropdown');
        var $menuIcon   = $('#wpaicg_menu_icon');
        var $search     = $('#wpaicg_search_container');
        var $searchIcon = $('#wpaicg_search_icon');

        if (
            !$menu.is(e.target) &&
            $menu.has(e.target).length === 0 &&
            !$menuIcon.is(e.target) &&
            $menuIcon.has(e.target).length === 0
        ) {
            $menu.hide();
        }

        if (
            $search.is(':visible') &&
            !$search.is(e.target) &&
            $search.has(e.target).length === 0 &&
            !$searchIcon.is(e.target) &&
            $searchIcon.has(e.target).length === 0
        ) {
            $search.hide();
            $('#wpaicg_search_forms').val('');
            wpaicg_refreshForms(1);
        }
    });

    /*************************************************************
     * MAGNIFIER ICON -> SHOW/HIDE SEARCH
     *************************************************************/
    $('#wpaicg_search_icon').on('click', function(e){
        e.stopPropagation();
        var $search = $('#wpaicg_search_container');
        if ($search.is(':visible')) {
            $search.hide();
            $('#wpaicg_search_forms').val('');
            wpaicg_refreshForms(1);
        } else {
            $search.show();
            $('#wpaicg_search_forms').focus();
        }
    });

    /*************************************************************
     * SIDEBAR TOGGLE (main list only)
     *************************************************************/
    $('#wpaicg_toggle_sidebar_icon').on('click', function(){
        $flex.toggleClass('wpaicg_sidebar_hidden');
    });

    /*************************************************************
     * PAGINATION + FILTERING LOGIC FOR MAIN LIST
     *************************************************************/
    /**
     * Return array of forms matching the active filters (authors, cats, search).
     */
    function wpaicg_filter_forms() {
        var forms = window.wpaicgAllForms || [];

        // Gather filters
        var checkedAuthors = [];
        $('.wpaicg_author_checkbox:checked').each(function(){
            checkedAuthors.push($(this).val());
        });
        var checkedCats = [];
        $('.wpaicg_cat_checkbox:checked').each(function(){
            checkedCats.push($(this).val());
        });

        var searchText = $('#wpaicg_search_forms').val() ? $('#wpaicg_search_forms').val().toLowerCase() : '';

        // Filter
        var filtered = forms.filter(function(item){
            var author = item.author.toString();
            var cats   = item.categories || [];
            var title  = item.title.toLowerCase();

            // Suffix label either description (builtin) or "Custom Form #id"
            var suffix = '';
            if (item.type === 'builtin' && item.data && item.data.description) {
                suffix = item.data.description.toLowerCase();
            } else if (item.type === 'custom') {
                suffix = ('custom form #' + item.db_id).toLowerCase();
            }

            // author match
            var authorMatch = (checkedAuthors.length === 0) || (checkedAuthors.indexOf(author) !== -1);
            // cat match
            var catMatch = (checkedCats.length === 0)
                || cats.some(function(c){ return checkedCats.indexOf(c) !== -1; });
            // search match
            var searchMatch = (searchText === '')
                || (title.indexOf(searchText) !== -1)
                || (suffix.indexOf(searchText) !== -1);

            return authorMatch && catMatch && searchMatch;
        });

        return filtered;
    }

    /**
     * Build HTML for the forms grid (subset) and append it into #wpaicg_forms_grid.
     */
    function wpaicg_renderForms(page, forms){
        $grid.empty();

        var total = forms.length;
        var perPage = 100;  // 100 forms per page
        var totalPages = Math.max(1, Math.ceil(total / perPage));

        if (page < 1) { page = 1; }
        if (page > totalPages) { page = totalPages; }

        var start = (page - 1) * perPage;
        var end   = start + perPage;
        var slice = forms.slice(start, end);

        if (slice.length === 0) {
            $grid.html('<p><?php echo esc_js(__("No AI Forms found.","gpt3-ai-content-generator")); ?></p>');
        } else {
            slice.forEach(function(f){
                var type       = f.type;
                var db_id      = f.db_id;
                var builtin_id = f.builtin_id;
                var title      = f.title;
                var color      = f.color || '#999999';

                // Evaluate the icon class
                var icon_class = f.icon || f.icon_key || 'dashicons dashicons-star-filled';
                // Suffix label
                var suffix_label = '';
                if (type === 'builtin' && f.data && f.data.description) {
                    suffix_label = f.data.description;
                } else if (type === 'custom') {
                    suffix_label = '<?php echo esc_js(__("Custom Form #","gpt3-ai-content-generator")); ?>' + db_id;
                }

                var catsAttr = (f.categories && Array.isArray(f.categories)) ? f.categories.join(',') : '';

                // Build item HTML
                var itemHtml = '<div class="wpaicg_form_item" ' +
                    'data-type="'+type+'" ' +
                    'data-db_id="'+db_id+'" ' +
                    'data-builtin_id="'+builtin_id+'" ' +
                    'data-author="'+f.author+'" ' +
                    'data-cats="'+catsAttr+'" ' +
                    'data-color="'+color+'" ' +
                    'data-title="'+title+'" ' +
                    'data-suffix="'+suffix_label+'" ' +
                    'data-icon_class="'+icon_class+'">' +
                        '<div class="wpaicg_form_icon_wrapper" style="background-color:'+color+';">' +
                            '<span class="'+icon_class+'"></span>' +
                        '</div>' +
                        '<div class="wpaicg_form_text">' +
                            '<h5>'+title+'</h5>' +
                            '<p>'+suffix_label+'</p>' +
                        '</div>' +
                    '</div>';
                $grid.append(itemHtml);
            });
        }

        // Build pagination
        var paginationHtml = wpaicg_build_forms_pagination(page, totalPages);
        // Append below the grid
        $grid.append(paginationHtml);
    }

    /**
     * Builds the pagination HTML (similar to logs pagination).
     */
    function wpaicg_build_forms_pagination(currentPage, totalPages) {
        if (totalPages <= 1) {
            return '';
        }
        var html = '<div class="wpaicg_forms_pagination">';

        // Page 1
        if (currentPage > 1) {
            html += '<span class="wpaicg_forms_page_link" data-page="1">1</span>';
        } else {
            html += '<span class="wpaicg_forms_page_link active" data-page="1">1</span>';
        }

        if (currentPage > 3) {
            html += '<span class="wpaicg_forms_page_ellipsis">…</span>';
        }

        var start = Math.max(2, currentPage - 1);
        var end   = Math.min(totalPages - 1, currentPage + 1);

        for (var i = start; i <= end; i++) {
            if (i === 1 || i === totalPages) {
                continue;
            }
            if (i === currentPage) {
                html += '<span class="wpaicg_forms_page_link active" data-page="'+i+'">'+i+'</span>';
            } else {
                html += '<span class="wpaicg_forms_page_link" data-page="'+i+'">'+i+'</span>';
            }
        }

        if (currentPage < totalPages - 2) {
            html += '<span class="wpaicg_forms_page_ellipsis">…</span>';
        }

        if (currentPage < totalPages) {
            html += '<span class="wpaicg_forms_page_link" data-page="'+totalPages+'">'+totalPages+'</span>';
        } else {
            if (totalPages > 1) {
                html += '<span class="wpaicg_forms_page_link active" data-page="'+totalPages+'">'+totalPages+'</span>';
            }
        }

        html += '</div>';
        return html;
    }

    /**
     * Re-filter and re-render forms at a given page.
     */
    function wpaicg_refreshForms(page) {
        if (!page) {
            page = 1;
        }
        var filtered = wpaicg_filter_forms();
        wpaicg_renderForms(page, filtered);
    }

    // On checkbox or search input => re-filter from page=1
    $('.wpaicg_author_checkbox, .wpaicg_cat_checkbox').on('change', function(){
        wpaicg_refreshForms(1);
    });
    $('#wpaicg_search_forms').on('keyup', function(){
        wpaicg_refreshForms(1);
    });

    // Listen for clicks on pagination links (live binding)
    $(document).on('click', '.wpaicg_forms_page_link', function(){
        if(!$(this).hasClass('active')) {
            var page = parseInt($(this).data('page'), 10);
            wpaicg_refreshForms(page);
        }
    });

    /*************************************************************
     * PREVIEW LOGIC - LOAD FORM SHORTCODE
     *************************************************************/
    function loadFormShortcodePreview(form_id, isCustom) {
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_get_form_preview',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-ajax-nonce")); ?>',
                form_id: form_id,
                custom: (isCustom ? 'yes' : 'no')
            },
            success: function(response) {
                if (response.success) {
                    $('.wpaicg_preview_form_container').html(response.data.html);
                    if (typeof wpaicgPlayGround !== 'undefined') {
                        wpaicgPlayGround.init();
                    }
                    if (typeof wp !== 'undefined' && wp.editor && wp.editor.initialize) {
                        $('.wpaicg_preview_form_container').find('textarea').each(function(){
                            var textID = $(this).attr('id');
                            if (textID && textID.indexOf('wpaicg-prompt-result-') === 0) {
                                wp.editor.initialize(textID, {
                                    tinymce: {
                                        wpautop: true,
                                        plugins: 'charmap colorpicker hr lists paste tabfocus textcolor fullscreen wordpress wpautoresize wpeditimage wpemoji wpgallery wplink wptextpattern',
                                        toolbar1: 'formatselect,bold,italic,bullist,numlist,blockquote,alignleft,aligncenter,alignright,link,wp_more,spellchecker,fullscreen,wp_adv,listbuttons',
                                        toolbar2: 'styleselect,strikethrough,hr,forecolor,pastetext,removeformat,charmap,outdent,indent,undo,redo,wp_help',
                                        height: 300
                                    },
                                    quicktags: {
                                        buttons: 'strong,em,link,block,del,ins,img,ul,ol,li,code,more,close'
                                    },
                                    mediaButtons: true
                                });
                            }
                        });
                    }
                } else {
                    var msg = (response.data && response.data.message)
                        ? response.data.message
                        : '<?php echo esc_js(__('Error loading form','gpt3-ai-content-generator')); ?>';
                    $('.wpaicg_preview_form_container').html('<p style="color:red;">'+ msg +'</p>');
                }
            },
            error: function() {
                $('.wpaicg_preview_form_container').html('<p style="color:red;">Ajax error</p>');
            }
        });
    }

    // Delegate click to dynamically rendered .wpaicg_form_item
    $(document).on('click', '.wpaicg_form_item', function(e){
        e.preventDefault();
        var $item      = $(this);
        var type       = $item.data('type');
        var builtin_id = parseInt($item.data('builtin_id'),10);
        var db_id      = parseInt($item.data('db_id'),10);

        $('#wpaicg_forms_grid').hide();
        $('#wpaicg_preview_panel').show();

        if( !$flex.hasClass('wpaicg_sidebar_hidden') ){
            $flex.addClass('wpaicg_sidebar_hidden');
        }

        $('#wpaicg_toggle_sidebar_icon, #wpaicg_search_icon, #wpaicg_search_container, #wpaicg_plus_icon').hide();
        $('.wpaicg_preview_back, .wpaicg_preview_duplicate').show();

        if(type === 'custom'){
            $('.wpaicg_preview_edit').show().attr('data-edit-id', db_id);
            $('.wpaicg_preview_delete').show().attr('data-delete-id', db_id);
        } else {
            $('.wpaicg_preview_edit').hide().attr('data-edit-id','');
            $('.wpaicg_preview_delete').hide().attr('data-delete-id','');
        }

        $('.wpaicg_preview_duplicate')
            .attr('data-type', type)
            .attr('data-builtin_id', builtin_id)
            .attr('data-db_id', db_id);

        if(type === 'custom'){
            showShortcodeSnippet('[wpaicg_form id=' + db_id + ' settings="no" custom="yes"]');
            loadFormShortcodePreview(db_id, true);
        } else {
            showShortcodeSnippet('[wpaicg_form id=' + builtin_id + ' settings="no"]');
            loadFormShortcodePreview(builtin_id, false);
        }
    });

    // "Back to List"
    $('.wpaicg_preview_back').on('click', function(e){
        e.preventDefault();
        $('#wpaicg_preview_panel').hide();
        $('#wpaicg_forms_grid').show();

        $('#wpaicg_toggle_sidebar_icon, #wpaicg_search_icon, #wpaicg_plus_icon').show();
        $('.wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete').hide();
        hideShortcodeSnippet();
    });

    // Duplicate
    $('.wpaicg_preview_duplicate').on('click', function(){
        var formType   = $(this).attr('data-type');
        var builtinID  = $(this).attr('data-builtin_id');
        var dbID       = $(this).attr('data-db_id');

        if(!formType){
            showGlobalMessage('error','Missing form type');
            return;
        }

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_form_duplicate',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-ajax-nonce")); ?>',
                type: formType,
                builtin_id: builtinID,
                db_id: dbID
            },
            success: function(response){
                if(response.status === 'success'){
                    showGlobalMessage('success','Form duplicated successfully!');
                    setTimeout(function(){
                        window.location.reload();
                    }, 1500);
                } else {
                    showGlobalMessage('error', response.msg || 'Error duplicating form');
                }
            },
            error: function(){
                showGlobalMessage('error','Ajax error duplicating form');
            }
        });
    });

    // Delete
    $('.wpaicg_preview_delete').on('click', function(){
        if(!confirm('<?php echo esc_js(__('Are you sure?','gpt3-ai-content-generator')); ?>')) {
            return;
        }
        var formID = $(this).attr('data-delete-id');
        if(!formID) return;

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_template_delete',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-ajax-nonce")); ?>',
                id: formID
            },
            success: function(response) {
                if(response.status === 'success') {
                    showGlobalMessage('success','Form deleted successfully!');
                    setTimeout(function(){
                        window.location.reload();
                    }, 1500);
                } else {
                    showGlobalMessage('error', response.msg || '<?php echo esc_js(__('Error deleting form','gpt3-ai-content-generator')); ?>');
                }
            },
            error: function(){
                showGlobalMessage('error','<?php echo esc_js(__('Ajax error','gpt3-ai-content-generator')); ?>');
            }
        });
    });

    /*************************************************************
     * NEW CHUNK-BASED EXPORT
     *************************************************************/
    $('#wpaicg_export_forms').on('click', function(){
        // Step 1: get the list of all custom form IDs
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_get_custom_forms_ids',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-get-forms-ids-nonce")); ?>'
            },
            success: function(resp){
                if(!resp.success){
                    showGlobalMessage('error', resp.data || 'Failed to get forms list');
                    return;
                }
                var formIDs = resp.data.forms || [];
                var count   = formIDs.length;
                if(count === 0){
                    alert('<?php echo esc_js(__('No custom forms found to export','gpt3-ai-content-generator')); ?>');
                    return;
                }
                var proceed = confirm('<?php echo esc_js(__("You are about to export","gpt3-ai-content-generator")); ?> ' + count + ' <?php echo esc_js(__("forms. Continue?","gpt3-ai-content-generator")); ?>');
                if(!proceed) return;

                var exportedSoFar = 0;
                var results = [];

                function exportNext(){
                    if(exportedSoFar >= count){
                        // done - create final JSON
                        var jsonStr = JSON.stringify(results, null, 2);
                        var blob = new Blob([jsonStr], { type: "application/json" });
                        var url  = URL.createObjectURL(blob);

                        var d = new Date();
                        var timestamp = d.getFullYear() + '-' +
                            ('0' + (d.getMonth()+1)).slice(-2) + '-' +
                            ('0' + d.getDate()).slice(-2) + '_' +
                            ('0' + d.getHours()).slice(-2) + '-' +
                            ('0' + d.getMinutes()).slice(-2) + '-' +
                            ('0' + d.getSeconds()).slice(-2);
                        var fileName = 'ai_forms_export_' + timestamp + '.json';

                        showGlobalMessage('success', '<?php echo esc_js(__("Export complete","gpt3-ai-content-generator")); ?>', true);

                        var linkHTML = '<br><a href="' + url + '" download="' + fileName + '" style="text-decoration:underline;color:#2271b1;">'
                            + '<?php echo esc_js(__("Download JSON","gpt3-ai-content-generator")); ?>'
                            + '</a>';
                        $('#wpaicg_global_message').append(linkHTML);
                        return;
                    }
                    var currentID = formIDs[exportedSoFar];
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'wpaicg_export_single_form',
                            nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-export-single-nonce")); ?>',
                            form_id: currentID
                        },
                        success: function(rsp){
                            if(rsp.success){
                                results.push(rsp.data);
                            } else {
                                results.push({ error: true, msg: rsp.data || 'Export failed for form ' + currentID });
                            }
                            exportedSoFar++;
                            showGlobalMessage('info', exportedSoFar + '/' + count + ' <?php echo esc_js(__("exported","gpt3-ai-content-generator")); ?>', true);
                            exportNext();
                        },
                        error: function(){
                            results.push({ error: true, msg: 'Ajax error for form ' + currentID });
                            exportedSoFar++;
                            showGlobalMessage('info', exportedSoFar + '/' + count + ' <?php echo esc_js(__("exported","gpt3-ai-content-generator")); ?>', true);
                            exportNext();
                        }
                    });
                }
                exportNext();
            },
            error: function(){
                showGlobalMessage('error','<?php echo esc_js(__("Failed to retrieve form IDs","gpt3-ai-content-generator")); ?>');
            }
        });
    });

    /*************************************************************
     * NEW CHUNK-BASED IMPORT
     *************************************************************/
    $('#wpaicg_import_forms').on('click', function(e){
        e.preventDefault();

        var $fileInput = $('#wpaicg_import_json_file');
        $fileInput.off('change').on('change', function(e2){
            var file = e2.target.files[0];
            if(!file) return;

            var reader = new FileReader();
            reader.onload = function(evt){
                try {
                    var content = evt.target.result;
                    var dataArr = JSON.parse(content);
                    if(!Array.isArray(dataArr)){
                        showGlobalMessage('error', '<?php echo esc_js(__("Invalid JSON format","gpt3-ai-content-generator")); ?>');
                        return;
                    }
                    var count = dataArr.length;
                    if(count === 0){
                        showGlobalMessage('info','<?php echo esc_js(__("No forms found in JSON","gpt3-ai-content-generator")); ?>');
                        return;
                    }
                    var proceed = confirm('<?php echo esc_js(__("You are about to import","gpt3-ai-content-generator")); ?> ' + count + ' <?php echo esc_js(__("forms. Continue?","gpt3-ai-content-generator")); ?>');
                    if(!proceed) return;

                    var importedSoFar = 0;
                    function importNext(){
                        if(importedSoFar >= count){
                            showGlobalMessage('success','<?php echo esc_js(__("Import complete","gpt3-ai-content-generator")); ?> ' + importedSoFar + '/' + count, true);
                            setTimeout(function(){
                                window.location.reload();
                            }, 1500);
                            return;
                        }
                        var formData = dataArr[importedSoFar];
                        $.ajax({
                            url: ajaxurl,
                            method: 'POST',
                            dataType: 'json',
                            data: {
                                action: 'wpaicg_import_single_form',
                                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-import-single-nonce")); ?>',
                                form_data: JSON.stringify(formData)
                            },
                            success: function(rsp){
                                importedSoFar++;
                                showGlobalMessage('info','<?php echo esc_js(__("Imported","gpt3-ai-content-generator")); ?> ' + importedSoFar + '/' + count, true);
                                importNext();
                            },
                            error: function(){
                                importedSoFar++;
                                showGlobalMessage('info','<?php echo esc_js(__("Imported","gpt3-ai-content-generator")); ?> ' + importedSoFar + '/' + count
                                    + ' <?php echo esc_js(__("(error)","gpt3-ai-content-generator")); ?>', true);
                                importNext();
                            }
                        });
                    }
                    importNext();

                } catch(err){
                    showGlobalMessage('error','<?php echo esc_js(__("Invalid JSON file","gpt3-ai-content-generator")); ?>');
                }
            };
            reader.readAsText(file);
        });

        $fileInput.click();
    });

    /*************************************************************
     * NEW CHUNK-BASED DELETE
     *************************************************************/
    $('#wpaicg_delete_all_forms').on('click', function(){
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_get_custom_forms_ids',
                nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-get-forms-ids-nonce")); ?>'
            },
            success: function(resp){
                if(!resp.success){
                    showGlobalMessage('error', resp.data || 'Failed to get forms list');
                    return;
                }
                var formIDs = resp.data.forms || [];
                var count   = formIDs.length;
                if(count === 0){
                    alert('<?php echo esc_js(__('No custom forms found to delete','gpt3-ai-content-generator')); ?>');
                    return;
                }
                var proceed = confirm('<?php echo esc_js(__("You are about to delete","gpt3-ai-content-generator")); ?> ' + count + ' <?php echo esc_js(__("forms. This cannot be undone. Continue?","gpt3-ai-content-generator")); ?>');
                if(!proceed) return;

                var deletedSoFar = 0;
                function deleteNext(){
                    if(deletedSoFar >= count){
                        showGlobalMessage('success','<?php echo esc_js(__("All done!","gpt3-ai-content-generator")); ?>', true);
                        setTimeout(function(){
                            window.location.reload();
                        }, 1500);
                        return;
                    }
                    var formID = formIDs[deletedSoFar];
                    $.ajax({
                        url: ajaxurl,
                        method: 'POST',
                        dataType: 'json',
                        data: {
                            action: 'wpaicg_delete_single_form',
                            nonce: '<?php echo esc_js(wp_create_nonce("wpaicg-delete-single-nonce")); ?>',
                            form_id: formID
                        },
                        success: function(rsp){
                            deletedSoFar++;
                            showGlobalMessage('info', deletedSoFar + '/' + count + ' <?php echo esc_js(__("deleted","gpt3-ai-content-generator")); ?>', true);
                            deleteNext();
                        },
                        error: function(){
                            deletedSoFar++;
                            showGlobalMessage('info', deletedSoFar + '/' + count + ' <?php echo esc_js(__("(error)","gpt3-ai-content-generator")); ?>', true);
                            deleteNext();
                        }
                    });
                }
                deleteNext();
            },
            error: function(){
                showGlobalMessage('error','<?php echo esc_js(__("Failed to retrieve form IDs","gpt3-ai-content-generator")); ?>');
            }
        });
    });

    /*************************************************************
     * SETTINGS ICON & LOGS ICON
     *************************************************************/
    $('#wpaicg_settings_icon').on('click', function() {
        hideShortcodeSnippet();

        $('#wpaicg_aiforms_container, #wpaicg_logs_container, #wpaicg_create_container, #wpaicg_edit_container, #wpaicg_preview_panel').hide();
        $('#wpaicg_settings_container').show();

        $('#wpaicg_toggle_sidebar_icon, #wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form, #wpaicg_save_edited_form').hide();
        $('#wpaicg_return_main').show();
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Back','gpt3-ai-content-generator')); ?>');

        // Show top Save for settings
        $('#wpaicg_settings_top_save').show();
    });

    $('#wpaicg_logs_icon').on('click', function() {
        hideShortcodeSnippet();

        $('#wpaicg_aiforms_container, #wpaicg_settings_container, #wpaicg_create_container, #wpaicg_edit_container, #wpaicg_preview_panel').hide();
        $('#wpaicg_logs_container').show();
        loadLogs(1, '');

        $('#wpaicg_toggle_sidebar_icon, #wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form, #wpaicg_save_edited_form').hide();
        $('#wpaicg_return_main').show();
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Back','gpt3-ai-content-generator')); ?>');
    });

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

    $(document).on('click', '.wpaicg_logs_page_link', function(){
        if(!$(this).hasClass('active')){
            var page      = $(this).data('page');
            var searchVal = $('#wpaicg_logs_search').val() || '';
            loadLogs(page, searchVal);
        }
    });

    $('#wpaicg_logs_search').on('keyup', function(){
        var val = $(this).val().trim();
        loadLogs(1, val);
    });

    $('#wpaicg_logs_delete_all').on('click', function(){
        var conf = confirm('<?php echo esc_js(__('Are you sure you want to delete ALL logs?','gpt3-ai-content-generator')); ?>');
        if(!conf) return;
        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_delete_all_logs',
                nonce: '<?php echo wp_create_nonce("wpaicg_delete_all_logs_nonce"); ?>'
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

    // Log view modal
    $(document).on('click', '.wpaicg_log_view', function(){
        var prompt   = $(this).data('fullprompt') || '';
        var response = $(this).data('fullresponse') || '';
        $('#wpaicg_log_modal_prompt').text(prompt);

        var parsedResponse;
        if(typeof marked !== 'undefined'){
            parsedResponse = marked.parse(response);
        } else {
            parsedResponse = $('<div/>').text(response).html();
        }
        $('#wpaicg_log_modal_response').html(parsedResponse);

        $('#wpaicg_log_modal').show();
    });
    $('#wpaicg_log_modal_close').on('click', function(){
        $('#wpaicg_log_modal').hide();
    });

    /*************************************************************
     * AJAX: Save “Token Management” form in settings without reloading
     *************************************************************/
    $('#wpaicg_settings_top_save').on('click', function(e){
        e.preventDefault();
        var formData = $('#wpaicg_settings_form').serializeArray();
        formData.push({ name: 'action', value: 'wpaicg_save_form_settings' });

        var securityVal = $('#wpaicg_limit_tokens_nonce').val() || '';
        formData.push({ name: 'security', value: securityVal });

        $.ajax({
            url: ajaxurl,
            method: 'POST',
            dataType: 'json',
            data: formData,
            success: function(resp){
                if(resp.success){
                    showGlobalMessage('success', resp.data.message);
                } else {
                    showGlobalMessage('error', resp.data.msg || '<?php echo esc_js(__('Error saving settings','gpt3-ai-content-generator')); ?>');
                }
            },
            error: function(){
                showGlobalMessage('error','<?php echo esc_js(__('Ajax error','gpt3-ai-content-generator')); ?>');
            }
        });
    });

    /*************************************************************
     * BACK/RETURN button -> exit logs or settings
     *************************************************************/
    $('#wpaicg_return_main').on('click', function(){
        var createVisible = $('#wpaicg_create_container').is(':visible');
        var editVisible   = $('#wpaicg_edit_container').is(':visible');

        if(createVisible) {
            if(!confirm('<?php echo esc_js(__('Are you sure? All changes will be lost if you exit Create Mode.','gpt3-ai-content-generator')); ?>')) {
                return;
            }
        }
        if(editVisible) {
            if(!confirm('<?php echo esc_js(__('Are you sure? All changes will be lost if you exit Edit Mode.','gpt3-ai-content-generator')); ?>')) {
                return;
            }
        }

        $('#wpaicg_logs_container, #wpaicg_settings_container, #wpaicg_create_container, #wpaicg_edit_container').hide();
        $('#wpaicg_aiforms_container').show();
        $('#wpaicg_preview_panel').hide();
        $('#wpaicg_forms_grid').show();

        $('#wpaicg_plus_icon, #wpaicg_search_icon, #wpaicg_menu_icon').show();
        $('#wpaicg_return_main, #wpaicg_settings_top_save, .wpaicg_preview_back, .wpaicg_preview_duplicate, .wpaicg_preview_edit, .wpaicg_preview_delete, #wpaicg_create_save_form, #wpaicg_save_edited_form').hide();

        hideShortcodeSnippet();
        $('#wpaicg_return_main').text('<?php echo esc_js(__('Back','gpt3-ai-content-generator')); ?>');
    });

    /*************************************************************
     * Finally, render the initial forms list at page=1
     *************************************************************/
    // On page load
    $(document).ready(function(){
        wpaicg_refreshForms(1);
    });

})(jQuery);
</script>