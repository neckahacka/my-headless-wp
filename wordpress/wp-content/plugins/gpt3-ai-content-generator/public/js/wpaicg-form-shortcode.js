var resetFeedbackButtons = function() {
    var thumbsUp = document.getElementById('wpaicg-prompt-thumbs_up');
    var thumbsDown = document.getElementById('wpaicg-prompt-thumbs_down');
    if (thumbsUp) {
        thumbsUp.disabled = false;
        thumbsUp.style.display = 'inline-block';
    }
    if (thumbsDown) {
        thumbsDown.disabled = false;
        thumbsDown.style.display = 'inline-block';
    }
};

var wpaicgPlayGround = {
    init: function(){
        var wpaicg_PlayGround = this;
        var wpaicgFormsShortcode = document.getElementsByClassName('wpaicg-playground-shortcode');
        var wpaicgClearButtons = document.getElementsByClassName('wpaicg-prompt-clear');
        var wpaicgStopButtons = document.getElementsByClassName('wpaicg-prompt-stop-generate');
        var wpaicgSaveButtons = document.getElementsByClassName('wpaicg-prompt-save-draft');
        var wpaicgDownloadButtons = document.getElementsByClassName('wpaicg-prompt-download');
        var wpaicgCopyButtons = document.getElementsByClassName('wpaicg-prompt-copy_button');
        var wpaicgThumbsUpButtons = document.getElementsByClassName('wpaicg-prompt-thumbs_up');
        var wpaicgThumbsDownButtons = document.getElementsByClassName('wpaicg-prompt-thumbs_down');

        if(wpaicgDownloadButtons && wpaicgDownloadButtons.length){
            for(var i=0;i < wpaicgDownloadButtons.length;i++) {
                var wpaicgDownloadButton = wpaicgDownloadButtons[i];
                wpaicgDownloadButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    var wpaicgDownloadButton = e.currentTarget;
                    var wpaicgForm = wpaicgDownloadButton.closest('.wpaicg-prompt-form');
                    var formID = wpaicgForm.getAttribute('data-id');
                    var wpaicgFormData = window['wpaicgForm'+formID];
                    var currentContent = wpaicg_PlayGround.getContent(wpaicgFormData.response,formID);

                    // Replace &nbsp; with space
                    currentContent = currentContent.replace(/&nbsp;/g, ' ');
                    currentContent = currentContent.replace(/<br>/g,"\n");
                    currentContent = currentContent.replace(/<br \/>/g,"\n");

                    var element = document.createElement('a');
                    element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(currentContent));
                    element.setAttribute('download', 'response.txt');

                    element.style.display = 'none';
                    document.body.appendChild(element);

                    element.click();
                    document.body.removeChild(element);
                });
            }
        }

        if(wpaicgCopyButtons && wpaicgCopyButtons.length){
            for(var i=0; i < wpaicgCopyButtons.length; i++){
                var wpaicgCopyButton = wpaicgCopyButtons[i];
                wpaicgCopyButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    var wpaicgCopyButton = e.currentTarget;
                    var originalText = wpaicgCopyButton.textContent;  // Store the original button text
                    wpaicgCopyButton.textContent = "👍";
                    setTimeout(function() {
                        wpaicgCopyButton.textContent = originalText;  // Restore original text after 2 seconds
                    }, 2000);

                    var wpaicgForm = wpaicgCopyButton.closest('.wpaicg-prompt-form');
                    var formID = wpaicgForm.getAttribute('data-id');
                    var wpaicgFormData = window['wpaicgForm'+formID];
                    var responseText = wpaicgPlayGround.getContent(wpaicgFormData.response, formID);

                    // Convert HTML to plain text (no markup)
                    var tmpDiv = document.createElement('div');
                    tmpDiv.innerHTML = responseText;
                    var plainText = tmpDiv.innerText || tmpDiv.textContent || '';
                    
                    navigator.clipboard.writeText(plainText).then(function() {
                        console.log('Text successfully copied to clipboard');
                    }).catch(function(err) {
                        console.error('Unable to copy text to clipboard', err);
                    });
                });
            }
        }

        if(wpaicgClearButtons && wpaicgClearButtons.length){
            for(var i=0;i < wpaicgClearButtons.length;i++){
                var wpaicgClearButton = wpaicgClearButtons[i];
                wpaicgClearButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    var wpaicgClearButton = e.currentTarget;
                    var wpaicgForm = wpaicgClearButton.closest('.wpaicg-prompt-form');
                    var formID = wpaicgForm.getAttribute('data-id');
                    var wpaicgFormData = window['wpaicgForm'+formID];
                    var wpaicgSaveResult = wpaicgForm.getElementsByClassName('wpaicg-prompt-save-result')[0];
                    wpaicg_PlayGround.setContent(wpaicgFormData.response,formID,'');
                    wpaicgSaveResult.style.display = 'none';
                });
            }
        }

        if(wpaicgStopButtons && wpaicgStopButtons.length){
            for(var i=0;i < wpaicgStopButtons.length;i++){
                var wpaicgStopButton = wpaicgStopButtons[i];
                wpaicgStopButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    var wpaicgStopButton = e.currentTarget;
                    var wpaicgForm = wpaicgStopButton.closest('.wpaicg-prompt-form');
                    var eventID = wpaicgStopButton.getAttribute('data-event');
                    var wpaicgSaveResult = wpaicgForm.getElementsByClassName('wpaicg-prompt-save-result')[0];
                    var wpaicgGenerateBtn = wpaicgForm.getElementsByClassName('wpaicg-generate-button')[0];
                    wpaicg_PlayGround.eventClose(eventID,wpaicgStopButton,wpaicgSaveResult,wpaicgGenerateBtn);
                });
            }
        }

        if(wpaicgSaveButtons && wpaicgSaveButtons.length){
            for(var i=0;i < wpaicgSaveButtons.length;i++){
                var wpaicgSaveButton = wpaicgSaveButtons[i];
                wpaicgSaveButton.addEventListener('click', function (e) {
                    e.preventDefault();
                    var wpaicgSaveButton = e.currentTarget;
                    var wpaicgForm = wpaicgSaveButton.closest('.wpaicg-prompt-form');
                    var formID = wpaicgForm.getAttribute('data-id');
                    var wpaicgFormData = window['wpaicgForm'+formID];
                    var title = wpaicgForm.getElementsByClassName('wpaicg-prompt-post_title')[0].value;
                    var content = wpaicgPlayGround.getContent(wpaicgFormData.response,formID);
                    if (title === '') {
                        alert('Please insert title');
                    } else if (content === '') {
                        alert('Please wait generate content');
                    } else {
                        const xhttp = new XMLHttpRequest();
                        xhttp.open('POST', wpaicgFormData.ajax);
                        xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                        var encodedContent = encodeURIComponent(content);
                        xhttp.send('action=wpaicg_save_draft_post_extra&title=' + encodeURIComponent(title) + '&content=' + encodedContent+'&save_source=promptbase&nonce='+wpaicgFormData.ajax_nonce);
                        wpaicgPlayGround.loading.add(wpaicgSaveButton);
                        xhttp.onreadystatechange = function (oEvent) {
                            if (xhttp.readyState === 4) {
                                wpaicgPlayGround.loading.remove(wpaicgSaveButton);
                                if (xhttp.status === 200) {
                                    var wpaicg_response = this.responseText;
                                    wpaicg_response = JSON.parse(wpaicg_response);
                                    if (wpaicg_response.status === 'success') {
                                        window.location.href = wpaicgFormData.post+'?post=' + wpaicg_response.id + '&action=edit';
                                    } else {
                                        alert(wpaicg_response.msg);
                                    }
                                } else {
                                    alert('Something went wrong');
                                }
                            }
                        };
                    }
                });
            }
        }

        // Initialize for each .wpaicg-playground-shortcode (the prompt forms)
        if(wpaicgFormsShortcode && wpaicgFormsShortcode.length){
            for(var i = 0;i< wpaicgFormsShortcode.length;i++){
                var wpaicgFormShortcode =  wpaicgFormsShortcode[i];
                var wpaicgForm = wpaicgFormShortcode.getElementsByClassName('wpaicg-prompt-form')[0];
                wpaicgForm.addEventListener('submit', function (e) {
                    e.preventDefault();

                    var wpaicgForm = e.currentTarget;
                    var formID = wpaicgForm.getAttribute('data-id');

                    var formSource = wpaicgForm.getAttribute('data-source');
                    var wpaicgFormData = window['wpaicgForm'+formID];

                    // If feedback buttons are enabled, reset them
                    if (wpaicgFormData && wpaicgFormData.feedback_buttons === 'yes') {
                        resetFeedbackButtons();
                    }

                    var wpaicgMaxToken = wpaicgForm.getElementsByClassName('wpaicg-prompt-max_tokens')[0];
                    var wpaicgTemperature = wpaicgForm.getElementsByClassName('wpaicg-prompt-temperature')[0];
                    var wpaicgTopP = wpaicgForm.getElementsByClassName('wpaicg-prompt-top_p')[0];
                    var wpaicgFP = wpaicgForm.getElementsByClassName('wpaicg-prompt-frequency_penalty')[0];
                    var wpaicgPP = wpaicgForm.getElementsByClassName('wpaicg-prompt-presence_penalty')[0];
                    var wpaicgMaxLines = wpaicgForm.getElementsByClassName('wpaicg-prompt-max-lines')[0];

                    var wpaicgGenerateBtn = wpaicgForm.getElementsByClassName('wpaicg-generate-button')[0];
                    var wpaicgSaveResult = wpaicgForm.getElementsByClassName('wpaicg-prompt-save-result')[0];
                    var wpaicgStop = wpaicgForm.getElementsByClassName('wpaicg-prompt-stop-generate')[0];
                    var max_tokens = wpaicgMaxToken.value;
                    var temperature = wpaicgTemperature.value;
                    var top_p = wpaicgTopP.value;
                    var frequency_penalty = wpaicgFP.value;
                    var presence_penalty = wpaicgPP.value;
                    var error_message = false;

                    if (max_tokens === '') {
                        error_message = 'Please enter max tokens';
                    } else if (parseFloat(max_tokens) < 1 || parseFloat(max_tokens) > 8000) {
                        error_message = 'Please enter a valid max tokens value between 1 and 8000';
                    } else if (temperature === '') {
                        error_message = 'Please enter temperature';
                    } else if (parseFloat(temperature) < 0 || parseFloat(temperature) > 1) {
                        error_message = 'Please enter a valid temperature value between 0 and 1';
                    } else if (top_p === '') {
                        error_message = 'Please enter Top P';
                    } else if (parseFloat(top_p) < 0 || parseFloat(top_p) > 1) {
                        error_message = 'Please enter a valid Top P value between 0 and 1';
                    }  else if (frequency_penalty === '') {
                        error_message = 'Please enter frequency penalty';
                    } else if (parseFloat(frequency_penalty) < 0 || parseFloat(frequency_penalty) > 2) {
                        error_message = 'Please enter a valid frequency penalty value between 0 and 2';
                    } else if (presence_penalty === '') {
                        error_message = 'Please enter presence penalty';
                    } else if (parseFloat(presence_penalty) < 0 || parseFloat(presence_penalty) > 2) {
                        error_message = 'Please enter a valid presence penalty value between 0 and 2';
                    }

                    if (error_message) {
                        alert(error_message);
                    } else {
                        // Validate form fields if any
                        if (typeof wpaicgFormData.fields === 'object') {
                            for (var i = 0; i < wpaicgFormData.fields.length; i++) {
                                var form_field = wpaicgFormData.fields[i];
                                var field = wpaicgForm.getElementsByClassName('wpaicg-form-field-' + i)[0];
                                var field_type = form_field['type'] !== undefined ? form_field['type'] : 'text';
                                var field_label = form_field['label'] !== undefined ? form_field['label'] : '';
                                var field_min = form_field['min'] !== undefined ? form_field['min'] : '';
                                var field_max = form_field['max'] !== undefined ? form_field['max'] : '';

                                if (field_type !== 'radio' && field_type !== 'checkbox' && field_type !== 'fileupload') {
                                    var field_value = field.value;
                                    if (field_type === 'text' || field_type === 'textarea' || field_type === 'email' || field_type === 'url') {
                                        if (field_min !== '' && field_value.length < parseInt(field_min)) {
                                            error_message = field_label + ' minimum ' + field_min + ' characters';
                                        } else if (field_max !== '' && field_value.length > parseInt(field_max)) {
                                            error_message = field_label + ' maximum ' + field_max + ' characters';
                                        } else if (field_type === 'email' && !wpaicgPlayGround.validate.email(field_value)) {
                                            error_message = field_label + ' must be email address';
                                        } else if (field_type === 'url' && !wpaicgPlayGround.validate.url(field_value)) {
                                            error_message = field_label + ' must be url';
                                        }
                                    } else if (field_type === 'number') {
                                        if (field_min !== '' && parseFloat(field_value) < parseInt(field_min)) {
                                            error_message = field_label + ' minimum ' + field_min;
                                        } else if (field_max !== '' && parseFloat(field_value) > parseInt(field_max)) {
                                            error_message = field_label + ' maximum ' + field_max;
                                        }
                                    }
                                } else if (field_type === 'fileupload') {
                                    // The file content is now stored in a transient. The hidden input
                                    // just has the transient key, so no length constraints needed here.
                                } else if (field_type === 'checkbox' || field_type === 'radio') {
                                    var field_inputs = field.getElementsByTagName('input');
                                    var field_checked = false;
                                    if (field_inputs && field_inputs.length) {
                                        for (var y = 0; y < field_inputs.length; y++) {
                                            var field_input = field_inputs[y];
                                            if (field_input.checked) {
                                                field_checked = true;
                                            }
                                        }
                                    }
                                    if (!field_checked) {
                                        error_message = field_label + ' is required';
                                    }
                                }
                            }
                        }

                        if(error_message){
                            alert(error_message);
                        } else {
                            // Build the query string
                            let queryString = new URLSearchParams(new FormData(wpaicgForm)).toString();
                            wpaicgPlayGround.loading.add(wpaicgGenerateBtn);
                            wpaicgSaveResult.style.display = 'none';
                            wpaicgStop.style.display = 'inline';
                            wpaicgPlayGround.setContent(wpaicgFormData.response,formID,'');
                            queryString += '&source_stream='+formSource+'&nonce='+wpaicgFormData.ajax_nonce;
                            var eventID = Math.ceil(Math.random()*1000000);

                            // Assign data-eventid to thumbs up/down for feedback tracking
                            for (var i = 0; i < wpaicgThumbsUpButtons.length; i++) {
                                wpaicgThumbsUpButtons[i].setAttribute('data-eventid', eventID);
                            }
                            for (var i = 0; i < wpaicgThumbsDownButtons.length; i++) {
                                wpaicgThumbsDownButtons[i].setAttribute('data-eventid', eventID);
                            }

                            wpaicgStop.setAttribute('data-event',eventID);
                            window['eventGenerator'+eventID] = new EventSource(wpaicgFormData.event + '&' + queryString);

                            if(formSource === 'form'){
                                queryString += '&action=wpaicg_form_log';
                            } else {
                                queryString += '&action=wpaicg_prompt_log';
                            }
                            wpaicgPlayGround.process(queryString,eventID,wpaicgFormData,formID,wpaicgStop,wpaicgSaveResult,wpaicgGenerateBtn,wpaicgMaxLines);
                        }
                    }
                });
            }
        }

        // Handle feedback button clicks
        var handleFeedbackButtonClick = function(e) {
            e.preventDefault();
            var button = e.currentTarget;
            var formID = button.getAttribute('data-id');
            var eventID = button.getAttribute('data-eventid');
            var feedbackType = button.id.replace('wpaicg-prompt-', ''); // "thumbs_up" or "thumbs_down"
            var wpaicgFormData = window['wpaicgForm' + formID];

            var modal = jQuery('#wpaicg_feedbackModal');
            var textareaID = wpaicgFormData.feedbackID;

            modal.fadeIn();
            jQuery('.wpaicg_feedbackModal-overlay').fadeIn();

            // Decide which AJAX action to call — always use wpaicg_form_feedback
            var myaction = 'wpaicg_save_feedback';

            // Set up the submit event for the feedback modal's "Submit" button
            jQuery('#wpaicg_submitFeedback').off('click').on('click', function() {
                modal.find('textarea').attr('id', textareaID);
                var comment = jQuery('#' + textareaID).val() || '';

                // Get the AI's response to store
                var responseText = wpaicgPlayGround.getContent(wpaicgFormData.response, formID);
                // Replace &nbsp; with space
                responseText = responseText.replace(/&nbsp;/g, ' ');
                // Convert <br> tags to new lines
                responseText = responseText.replace(/<br\s*\/?>/g, '\r\n');
                responseText = responseText.replace(/\r\n\r\n/g, '\r\n\r\n');

                const xhttp = new XMLHttpRequest();
                xhttp.open('POST', wpaicgFormData.ajax);
                xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                xhttp.send(
                    'action=' + myaction +
                    '&formID=' + encodeURIComponent(formID) +
                    '&feedback=' + encodeURIComponent(feedbackType) +
                    '&comment=' + encodeURIComponent(comment) +
                    '&nonce=' + wpaicgFormData.ajax_nonce +
                    '&formname=' + encodeURIComponent(wpaicgFormData.name) +
                    '&sourceID=' + encodeURIComponent(wpaicgFormData.sourceID) +
                    '&response=' + encodeURIComponent(responseText) +
                    '&eventID=' + encodeURIComponent(eventID)
                );

                xhttp.onreadystatechange = function(oEvent) {
                    if (xhttp.readyState === 4) {
                        if (xhttp.status === 200) {
                            var response = JSON.parse(xhttp.responseText);
                            if (response.status === 'success') {
                                // Disable the appropriate feedback button
                                if (feedbackType === 'thumbs_up') {
                                    var thumbsUpEl = document.getElementById('wpaicg-prompt-thumbs_up');
                                    if (thumbsUpEl) {
                                        thumbsUpEl.disabled = true;
                                    }
                                    var thumbsDownEl = document.getElementById('wpaicg-prompt-thumbs_down');
                                    if (thumbsDownEl) {
                                        thumbsDownEl.style.display = 'none';
                                    }
                                } else {
                                    var thumbsDownEl = document.getElementById('wpaicg-prompt-thumbs_down');
                                    if (thumbsDownEl) {
                                        thumbsDownEl.disabled = true;
                                    }
                                    var thumbsUpEl = document.getElementById('wpaicg-prompt-thumbs_up');
                                    if (thumbsUpEl) {
                                        thumbsUpEl.style.display = 'none';
                                    }
                                }
                                jQuery('#' + textareaID).val('');
                            } else {
                                alert(response.msg);
                            }
                        } else {
                            alert('Error: ' + xhttp.status + ' - ' + xhttp.statusText + '\n\n' + xhttp.responseText);
                        }
                        modal.fadeOut();
                        jQuery('.wpaicg_feedbackModal-overlay').fadeOut();
                    }
                };
            });

            // Close modal
            jQuery('#closeFeedbackModal').off('click').on('click', function() {
                modal.fadeOut();
                jQuery('.wpaicg_feedbackModal-overlay').fadeOut();
            });
        };

        for (var k = 0; k < wpaicgThumbsUpButtons.length; k++) {
            wpaicgThumbsUpButtons[k].addEventListener('click', handleFeedbackButtonClick);
        }
        for (var k = 0; k < wpaicgThumbsDownButtons.length; k++) {
            wpaicgThumbsDownButtons[k].addEventListener('click', handleFeedbackButtonClick);
        }

        // Handle fileupload fields: store in transient instead of entire content in hidden input
        var fileuploadFields = document.querySelectorAll('.wpaicg-fileupload-input');
        fileuploadFields.forEach(function(field){
            field.addEventListener('change', function(e){
                var files = e.target.files;
                if(!files.length) return;
                var file = files[0];
                // check extension
                var allowed = e.target.getAttribute('data-filetypes');
                if(allowed) {
                    var exts = allowed.split(',');
                    var ext = file.name.split('.').pop().toLowerCase();
                    if(!exts.includes(ext)){
                        alert('Invalid file type. Allowed: '+ exts.join(', '));
                        e.target.value = '';
                        return;
                    }
                }
                var reader = new FileReader();
                var ext = file.name.split('.').pop().toLowerCase();

                reader.onload = function(e2) {
                    var content;
                    // For docx (or doc), read as binary -> Base64
                    if (ext === 'docx' || ext === 'doc') {
                        // Convert array buffer to Base64
                        var bytes = new Uint8Array(e2.target.result);
                        var len = bytes.byteLength;
                        var binary = '';
                        for (var i = 0; i < len; i++) {
                            binary += String.fromCharCode(bytes[i]);
                        }
                        content = 'base64:' + btoa(binary);
                    } else {
                        // For txt, csv, etc., read as text
                        content = e2.target.result;
                    }

                    // Make an AJAX call to store the content in a transient
                    var wpaicgForm = field.closest('.wpaicg-prompt-form');
                    var formID = wpaicgForm.getAttribute('data-id');
                    var wpaicgFormData = window['wpaicgForm'+formID];

                    var formData = new FormData();
                    formData.append('action','wpaicg_store_file_content');
                    formData.append('nonce', wpaicgFormData.ajax_nonce);
                    formData.append('fileContent', content);

                    fetch(wpaicgFormData.ajax, {
                        method: 'POST',
                        body: formData
                    })
                    .then(function(resp){ return resp.json(); })
                    .then(function(data){
                        if(data.success && data.data && data.data.transient_key) {
                            // Set the hidden input to the transient key
                            var hiddenID = 'wpaicg-fileupload-hidden-' + field.id.replace('wpaicg-form-field-','');
                            var hiddenEl = document.getElementById(hiddenID);
                            if(hiddenEl) {
                                hiddenEl.value = data.data.transient_key;
                            }
                        } else {
                            alert('Failed to store file content in transient.');
                        }
                    })
                    .catch(function(err){
                        console.error('Error storing file content:', err);
                    });
                };

                // Decide how to read the file:
                if (ext === 'docx' || ext === 'doc') {
                    reader.readAsArrayBuffer(file);
                } else {
                    // For txt, csv, etc.
                    reader.readAsText(file);
                }
            });
        });
    },
    process: function(queryString, eventID, wpaicgFormData, formID, wpaicgStop, wpaicgSaveResult, wpaicgGenerateBtn, wpaicgMaxLines) {
        var wpaicg_PlayGround = this;
        var wpaicg_break_newline = wpaicgParams.logged_in === "1" ? '<br/><br/>' : '\n';
        var startTime = new Date();
        var wpaicg_response_events = 0;
        var wpaicg_newline_before = false;
        var prompt_response = '';
        var wpaicg_limited_token = false;
        var count_line = 0;
        var wpaicg_limitLines = parseFloat(wpaicgMaxLines.value);
        var currentContent = '';
    
        window['eventGenerator' + eventID].onmessage = function(e) {
            currentContent = wpaicg_PlayGround.getContent(wpaicgFormData.response, formID);
    
            if (e.data === "[LIMITED]") {
                console.log('Limited token');
                wpaicg_limited_token = true;
                count_line += 1;
                wpaicg_PlayGround.setContent(wpaicgFormData.response, formID, currentContent + wpaicg_break_newline);
                wpaicg_response_events = 0;
            } else if (e.data === "[DONE]") {
                count_line += 1;
                wpaicg_PlayGround.setContent(wpaicgFormData.response, formID, currentContent + wpaicg_break_newline);
                wpaicg_response_events = 0;
            } else {
                var result = JSON.parse(e.data);
                var hasFinishReason = result.choices &&
                    result.choices[0] &&
                    (
                        result.choices[0].finish_reason === "stop" ||
                        result.choices[0].finish_reason === "length" ||
                        (result.choices[0].finish_details && result.choices[0].finish_details.type === "stop")
                    );
                var content_generated = '';
                if (result.error !== undefined) {
                    content_generated = result.error.message;
                } else {
                    content_generated = (result.choices[0].delta !== undefined)
                        ? (result.choices[0].delta.content !== undefined ? result.choices[0].delta.content : '')
                        : result.choices[0].text;
                }
                prompt_response += content_generated;
    
                // Preprocess the prompt_response to convert math written between square brackets
                // into proper KaTeX delimiters.
                var convertedResponse = wpaicg_PlayGround.convertMathDelimiters(prompt_response);
    
                // Use marked.js to parse the (possibly mixed) markdown + math response.
                var parsedMarkdown = marked.parse(convertedResponse);
    
                // Place the HTML in the container
                if (wpaicgFormData.response === 'textarea') {
                    var basicEditor = wpaicg_PlayGround.editor(formID);
                    if (basicEditor) {
                        document.getElementById('wpaicg-prompt-result-' + formID).value = parsedMarkdown;
                    } else {
                        var editorInst = tinyMCE.get('wpaicg-prompt-result-' + formID);
                        editorInst.setContent(parsedMarkdown);
                    }
                } else {
                    var container = document.getElementById('wpaicg-prompt-result-' + formID);
                    container.innerHTML = parsedMarkdown;
                    // Render math (KaTeX) if available
                    if (typeof renderMathInElement === 'function') {
                        renderMathInElement(container, {
                            delimiters: [
                                { left: '$$', right: '$$', display: true },
                                // REMOVED SINGLE-DOLLAR DELIMITER:
                                // { left: '$',  right: '$',  display: false },
                                { left: '\\(', right: '\\)', display: false },
                                { left: '\\[', right: '\\]', display: true }
                            ],
                            throwOnError: false
                        });
                    }
                }
    
                if (hasFinishReason) {
                    count_line += 1;
                    wpaicg_response_events = 0;
                }
            }
    
            if (count_line === wpaicg_limitLines) {
                if (!wpaicg_limited_token) {
                    let endTime = new Date();
                    let timeDiff = endTime - startTime;
                    timeDiff = timeDiff / 1000;
                    queryString += '&prompt_id=' + wpaicgFormData.id +
                                   '&prompt_name=' + encodeURIComponent(wpaicgFormData.name) +
                                   '&prompt_response=' + encodeURIComponent(prompt_response) +
                                   '&duration=' + encodeURIComponent(timeDiff) +
                                   '&_wpnonce=' + encodeURIComponent(wpaicgFormData.nonce) +
                                   '&source_id=' + encodeURIComponent(wpaicgFormData.sourceID) +
                                   '&eventID=' + encodeURIComponent(eventID);
    
                    const xhttp = new XMLHttpRequest();
                    xhttp.open('POST', wpaicgFormData.ajax);
                    xhttp.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
                    xhttp.send(queryString);
                    xhttp.onreadystatechange = function(oEvent) {
                        if (xhttp.readyState === 4) {
                            // No special action needed here
                        }
                    };
                }
                wpaicg_PlayGround.eventClose(eventID, wpaicgStop, wpaicgSaveResult, wpaicgGenerateBtn, wpaicg_limited_token);
            }
        };
    },   
    convertMathDelimiters: function(text) {
        // Replace any text in square brackets that contains a backslash (a likely indicator of LaTeX)
        // with KaTeX display math delimiters.
        return text.replace(/\[\s*([^\[\]]+?)\s*\]/g, function(match, innerText) {
            if (innerText.indexOf('\\') !== -1) {
                return "$$" + innerText + "$$";
            }
            return match;
        });
    },
    
    editor: function (form_id){
        var basicEditor = true;
        if(wpaicg_prompt_logged){
            var editor = tinyMCE.get('wpaicg-prompt-result-'+form_id);
            if ( document.getElementById('wp-wpaicg-prompt-result-'+form_id+'-wrap') &&
                document.getElementById('wp-wpaicg-prompt-result-'+form_id+'-wrap').classList.contains('tmce-active') && editor ) {
                basicEditor = false;
            }
        }
        return basicEditor;
    },
    setContent: function (type,form_id,value){
        if(type === 'textarea') {
            value = value.replace(/&nbsp;/g, ' ');
            if (this.editor(form_id)) {
                document.getElementById('wpaicg-prompt-result-'+form_id).value = value;
            } else {
                var editor = tinyMCE.get('wpaicg-prompt-result-'+form_id);
                editor.setContent(value);
            }
        }
        else{
            document.getElementById('wpaicg-prompt-result-'+form_id).innerHTML = value;
        }
    },
    getContent: function (type,form_id){
        if(type === 'textarea') {
            if (this.editor(form_id)) {
                return document.getElementById('wpaicg-prompt-result-'+form_id).value;
            } else {
                var editor = tinyMCE.get('wpaicg-prompt-result-'+form_id);
                var content = editor.getContent();
                content = content.replace(/<\/?p(>|$)/g, "");
                return content;
            }
        }
        else {
            return document.getElementById('wpaicg-prompt-result-'+form_id).innerHTML;
        }
    },
    loading: {
        add: function (btn){
            btn.setAttribute('disabled','disabled');
            var spinner = document.createElement('span');
            spinner.classList.add('wpaicg-loader');
            btn.appendChild(spinner);
        },
        remove: function (btn){
            btn.removeAttribute('disabled');
            var spinners = btn.getElementsByClassName('wpaicg-loader');
            if(spinners.length){
                spinners[0].remove();
            }
        }
    },
    eventClose: function (eventID,btn,btnResult,btn_generator,wpaicg_limited_token){
        btn.style.display = 'none';
        if(!wpaicg_limited_token) {
            btnResult.style.display = 'block';
        }
        this.loading.remove(btn_generator);
        if(window['eventGenerator'+eventID]){
            window['eventGenerator'+eventID].close();
        }
    },
    validate: {
        email: function (email){
            return String(email)
                .toLowerCase()
                .match(
                    /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/
                );
        },
        url: function (url){
            try {
                new URL(url);
                return true;
            } catch (err) {
                return false;
            }
        }
    }
}
wpaicgPlayGround.init();