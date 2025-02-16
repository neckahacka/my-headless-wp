(function( $ ) {
	'use strict';

    // -------------------- NAVIGATION: Tab Navigation Logic --------------------
    $(".aipower-tab-btn").on('click', function () {
        $(".aipower-tab-btn").removeClass("active");
        $(".aipower-tab-pane").removeClass("active");
    
        $(this).addClass("active");
        $("#" + $(this).data("tab")).addClass("active");
    });
    
    $(".aipower-nav-link").on('click', function (e) {
        if ($(this).attr('href') === '#') {
            e.preventDefault(); // Prevent default link behavior only if href="#" (no navigation)
        }
    
        $(".aipower-nav-link").removeClass("active");
        $(this).addClass("active");
    });

    // -------------------- UI FEEDBACK: Spinner and Message Display Functions --------------------
    function showSpinner() {
        $('#aipower-spinner').show();
        $('#aipower-message').removeClass('error success').addClass('aipower-autosaving').text('Autosaving...').fadeIn();
    }
    
    function showSuccess(message) {
        $('#aipower-spinner').hide();
        $('#aipower-message').removeClass('error').addClass('success').text(message).fadeIn();
        setTimeout(function () {
            $('#aipower-message').fadeOut();
        }, 3000);
    }

    function showError(message) {
        $('#aipower-spinner').hide();
        $('#aipower-message').removeClass('success').addClass('error').text(message).fadeIn();
    }

    // -------------------- UI LOGIC: Show Provider-Specific Container --------------------
    function showProviderContainer(engine) {
        $('.aipower-provider-container').hide();

        // Show or hide the Safety Settings icon based on the selected engine
        if (engine === 'Google') {
            $('#aipower-safety-settings-icon').show();  // Show the icon if Google is selected
        } else {
            $('#aipower-safety-settings-icon').hide();  // Hide the icon if not Google
            $('#aipower_safety_settings_modal').hide(); // Also hide the modal if it's open
        }

        switch (engine) {
            case 'OpenAI':
                $('#aipower-openai-container').show();
                break;
            case 'OpenRouter':
                $('#aipower-openrouter-container').show();
                break;
            case 'Google':
                $('#aipower-google-container').show();
                break;
            case 'Azure':
                $('#aipower-azure-container').show();
                break;
        }
    }

    var initialEngine = $('#aipower-ai-engine-dropdown').val();
    showProviderContainer(initialEngine);

    // -------------------- LOGIC: Handle AI Engine Selection Change --------------------
    $('#aipower-ai-engine-dropdown').on('change', function () {
        var selectedEngine = $(this).val();
        var nonce = $('#ai-engine-nonce').val();

        showProviderContainer(selectedEngine);
        showSpinner();

        $.post(ajaxurl, {
            action: 'aipower_save_ai_engine',
            engine: selectedEngine,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while updating the AI engine.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });

    // -------------------- LOGIC: Save API Key --------------------
    function saveApiKey(engine, apiKey) {
        var nonce = $('#ai-engine-nonce').val();
        showSpinner();

        $.post(ajaxurl, {
            action: 'aipower_save_api_key',
            engine: engine,
            api_key: apiKey,  // Send the full, unmasked API key
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while updating the API key.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    }

    // -------------------- EVENTS: Handle Focus and Blur for API Keys --------------------
    ['OpenAI', 'OpenRouter', 'Google', 'Azure', 'Replicate', 'Pexels', 'Pixabay'].forEach(function (engine) {
        var apiKeyField = $('#' + engine + '-api-key');
        var fullApiKey = apiKeyField.data('full-api-key') || apiKeyField.val(); // Store the full API key

        // Focus: Reveal the full API key if it's currently masked
        apiKeyField.on('focus', function () {
            if ($(this).val() === maskApiKey(fullApiKey)) { 
                $(this).val(fullApiKey); // Show full key if masked
            }
        });

        // Blur: Re-mask the key if no changes are made
        apiKeyField.on('blur', function () {
            if ($(this).val() === fullApiKey) {
                $(this).val(maskApiKey(fullApiKey)); // Re-mask if unchanged
            } else {
                fullApiKey = $(this).val();  // Update full API key if user changed it
                saveApiKey(engine.charAt(0).toUpperCase() + engine.slice(1), fullApiKey); // Save new key
            }
        });
    });

    // Helper function to mask API key
    function maskApiKey(apiKey) {
        return apiKey.length > 4 ? apiKey.replace(/.(?=.{4})/g, '*') : apiKey;
    }

    // -------------------- LOGIC: Save Azure Fields (API Key, Endpoint, Deployment, Embeddings) --------------------
    var azureFields = {
        'Azure-api-key': 'wpaicg_azure_api_key',
        'Azure-endpoint': 'wpaicg_azure_endpoint',
        'Azure-deployment': 'wpaicg_azure_deployment',
        'Azure-embeddings': 'wpaicg_azure_embeddings'
    };

    // Store the initial value of the field on focus and compare it on blur
    $.each(azureFields, function(fieldId, optionName) {
        var initialValue;

        $('#' + fieldId).on('focus', function () {
            initialValue = $(this).val(); // Store the initial value when the field gains focus
        });

        $('#' + fieldId).on('blur', function () {
            var newValue = $(this).val();
            if (initialValue !== newValue) {
                saveAzureField(fieldId, optionName); // Save only if the value has changed
            }
        });
    });

    // -------------------- LOGIC: Save Azure Fields (API Key, Endpoint, Deployment, Embeddings) --------------------
    function saveAzureField(fieldId, optionName) {
        var fieldValue = $('#' + fieldId).val();
        var nonce = $('#ai-engine-nonce').val();
        showSpinner();

        $.post(ajaxurl, {
            action: 'aipower_save_azure_field',
            option_name: optionName,
            option_value: fieldValue,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while saving the Azure field.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    }

    // -------------------- LOGIC: Handle OpenAI Engine Selection Change --------------------
    // Handle OpenAI model selection change
    $('#aipower-openai-model-dropdown').on('change', function () {
        var selectedModel = $(this).val();
        var nonce = $('#ai-engine-nonce').val();
        
        showSpinner();
        
        $.post(ajaxurl, {
            action: 'aipower_save_openai_model',
            model: selectedModel,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while saving the model.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });

    // -------------------- LOGIC: Handle OpenRouter Engine Selection Change --------------------
    // Handle OpenRouter model selection change
    $('#aipower-openrouter-model-dropdown').on('change', function () {
        var selectedModel = $(this).val();
        var nonce = $('#ai-engine-nonce').val();
        
        showSpinner();
        
        $.post(ajaxurl, {
            action: 'aipower_save_openrouter_model',
            model: selectedModel,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while saving the model.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });

    // -------------------- LOGIC: Handle Google Model Selection Change --------------------
    $('#aipower-google-model-dropdown').on('change', function () {
        var selectedModel = $(this).val();
        var nonce = $('#ai-engine-nonce').val();

        showSpinner();

        $.post(ajaxurl, {
            action: 'aipower_save_google_model',
            model: selectedModel,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while saving the model.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });

    // -------------------- LOGIC: Handle OpenRouter Model Sync --------------------
    $('#syncOpenRouter, #aipower_sync_openrouter_models_bot').on('click', function() {
        var btn = $(this);
        var icon = btn.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val();
        var targetDropdownSelector = btn.data('target'); // Get the target from data-target

        if (!targetDropdownSelector) {
            // If no data-target is specified, you can set a default or exit
            console.error('No target dropdown specified for syncing.');
            return;
        }

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'aipower_sync_openrouter_models',
                _wpnonce: nonce
            },
            beforeSend: function() {
                icon.addClass('aipower-rotating'); // Apply the rotating class
            },
            success: function(response) {
                icon.removeClass('aipower-rotating'); // Remove the rotating class
                if (response.success) {
                    // Get the default model if exists
                    var defaultModel = response.data.default_model;
            
                    // Check if the target selector is the specific dropdown that needs a reload
                    if (targetDropdownSelector === '#aipower-openrouter-model-dropdown') {
                        // For the specific dropdown: Show success message and reload the page
                        showSuccess('Models synced successfully. Reloading the page...');
                        location.reload(); // Reload the current page
                    } else {
                        // Update the model dropdown with new models
                        var $modelDropdown = $(targetDropdownSelector);
                        $modelDropdown.empty(); // Clear the existing options
            
                        $.each(response.data.models, function(provider, models) {
                            var optgroup = $('<optgroup>').attr('label', provider);
                            $.each(models, function(index, model) {
                                var $option = $('<option>').val(model.id).text(model.name);
                                if (model.id === defaultModel) {
                                    $option.attr('selected', 'selected'); // Keep the default model selected
                                }
                                optgroup.append($option);
                            });
                            $modelDropdown.append(optgroup);
                        });
            
                        // Show success message
                        showSuccess('Models synced successfully.');
                    }
                } else {
                    showError(response.data || 'An error occurred.');
                }
            },            
            error: function(jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Remove the rotating class on error
                showError('Error: ' + errorThrown);
            }
        });
    });
    
    // -------------------- LOGIC: Handle OpenAI Model Sync --------------------
    $('#syncOpenAI, #aipower_sync_openai_models_bot').on('click', function() {
        var btn = $(this);
        var icon = btn.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val();

        // Retrieve the target selector from data-target attribute
        var targetSelector = btn.data('target');
        var $modelDropdown = targetSelector ? $(targetSelector) : btn.closest('.aipower-form-group').find('select');

        // Capture the currently selected model
        var currentSelectedModel = $modelDropdown.val();

        $.ajax({
            url: ajaxurl, // Ensure this is correctly defined in your WordPress setup
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'aipower_fetch_openai_models',
                _wpnonce: nonce
            },
            beforeSend: function() {
                icon.addClass('aipower-rotating'); // Add rotating animation
            },
            success: function(response) {
                icon.removeClass('aipower-rotating'); // Remove rotating animation
                if (response.success) {
                    // Check if the target selector is Page 1's dropdown
                    if (targetSelector === '#aipower-openai-model-dropdown') {
                        // For Page 1: Show success message and reload the page
                        showSuccess('Models and assistants synced successfully. Reloading the page...');
                        location.reload(); // Reload the current page
                    } else {
                        // For Page 2: Update the dropdown without reloading
                        $modelDropdown.empty();

                        // 1. Add Assistants Optgroup
                        var assistants = response.data.assistants;
                        var $assistantsGroup = $('<optgroup>').attr('label', 'Assistants');
                        if (assistants && assistants.length > 0) {
                            assistants.forEach(function(assistant) {
                                // Display assistant name if available, else display assistant_id
                                var displayName = assistant.name ? assistant.name : assistant.assistant_id;
                                $assistantsGroup.append('<option value="' + assistant.assistant_id + '">' + displayName + '</option>');
                            });
                        } else {
                            // If no assistants, add a disabled option
                            $assistantsGroup.append('<option disabled>Click sync button next to this model list to fetch your assistants</option>');
                        }
                        $modelDropdown.append($assistantsGroup);

                        // Populate GPT-3.5 Models
                        var gpt35Group = $('<optgroup>').attr('label', 'GPT-3.5 Models');
                        $.each(response.data.gpt35_models, function(key, label) {
                            gpt35Group.append($('<option>').val(key).text(label));
                        });
                        $modelDropdown.append(gpt35Group);

                        // Populate GPT-4 Models
                        var gpt4Group = $('<optgroup>').attr('label', 'GPT-4 Models');
                        $.each(response.data.gpt4_models, function(key, label) {
                            gpt4Group.append($('<option>').val(key).text(label));
                        });
                        $modelDropdown.append(gpt4Group);

                        // Populate Custom Models if available
                        if (response.data.custom_models && response.data.custom_models.length > 0) {
                            var customGroup = $('<optgroup>').attr('label', 'Custom Models');
                            $.each(response.data.custom_models, function(index, model) {
                                customGroup.append($('<option>').val(model).text(model));
                            });
                            $modelDropdown.append(customGroup);
                        }

                        // Retrieve the default selected model from data attribute
                        var defaultSelectedModel = $modelDropdown.data('default');

                        // Set back the selected model if it exists, otherwise fallback to default
                        if ($modelDropdown.find("option[value='" + currentSelectedModel + "']").length > 0) {
                            $modelDropdown.val(currentSelectedModel);
                        } else if (defaultSelectedModel) {
                            $modelDropdown.val(defaultSelectedModel);
                        } else {
                            // Optionally, set to the first available model
                            $modelDropdown.prop('selectedIndex', 0);
                        }

                        // Display a success message
                        showSuccess('Models and assistants synced successfully.');
                    }
                } else {
                    // Extract and display the error message
                    var errorMessage = 'An error occurred.';
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    showError(errorMessage);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Remove rotating animation
                // Extract and display the error message if available
                var errorMessage = 'Error: ' + errorThrown;
                if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    errorMessage = jqXHR.responseJSON.data.message;
                }
                showError(errorMessage);
            }
        });
    });

    // -------------------- LOGIC: Handle Google Model Sync --------------------
    $('#syncGoogle, #aipower_sync_google_models_bot').on('click', function() {
        var btn = $(this);
        var icon = btn.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val();

        // Retrieve the target selector from data-target attribute
        var targetSelector = btn.data('target');
        var $modelDropdown = targetSelector ? $(targetSelector) : btn.closest('.aipower-form-group').find('select');

        // Capture the currently selected model
        var currentSelectedModel = $modelDropdown.val();

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'aipower_sync_google_models',
                _wpnonce: nonce
            },
            beforeSend: function() {
                icon.addClass('aipower-rotating'); // Add rotating animation
            },
            success: function(response) {
                icon.removeClass('aipower-rotating'); // Remove rotating animation
                if (response.success) {
                    // Check if the target selector is Page 1's dropdown
                    if (targetSelector === '#aipower-google-model-dropdown') {
                        // For Page 1: Show success message and reload the page
                        showSuccess('Google models synced successfully. Reloading the page...');
                        location.reload(); // Reload the current page
                    } else {
                        // For Page 2: Update the dropdown without reloading
                        $modelDropdown.empty(); // Clear the existing options

                        // Define words that disable the option
                        var disabledWords = ['vision']; // Add specific words that disable the option

                        $.each(response.data.models, function(index, model) {
                            var shouldBeDisabled = false;

                            // Check if the model should be disabled
                            for (var i = 0; i < disabledWords.length; i++) {
                                if (model.indexOf(disabledWords[i]) !== -1) {
                                    shouldBeDisabled = true;
                                    break; // Disable the option if any word is found
                                }
                            }

                            // Create display name
                            var displayName = model.replace(/-/g, ' ').replace(/\b\w/g, function(l){ return l.toUpperCase(); });

                            var $option = $('<option>').val(model).text(displayName);

                            if (shouldBeDisabled) {
                                $option.prop('disabled', true);
                            }

                            $modelDropdown.append($option);
                        });

                        // Set back the selected model if it exists, otherwise leave as is
                        if ($modelDropdown.find("option[value='" + currentSelectedModel + "']").length > 0) {
                            $modelDropdown.val(currentSelectedModel);
                        }

                        // Show success message
                        showSuccess('Google models synced successfully.');
                    }
                } else {
                    // Extract the error message properly
                    var errorMessage = 'An error occurred.';
                    if (response.data && response.data.message) {
                        errorMessage = response.data.message;
                    }
                    showError(errorMessage);
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Remove rotating animation
                // Try to extract error message from the response if available
                var errorMessage = 'Error: ' + errorThrown;
                if (jqXHR.responseJSON && jqXHR.responseJSON.data && jqXHR.responseJSON.data.message) {
                    errorMessage = jqXHR.responseJSON.data.message;
                }
                showError(errorMessage);
            }
        });
    });


    // -------------------- LOGIC: Save Advanced Settings --------------------
    $('#aipower_advanced_settings_modal input').on('change', function () {
        var optionName = $(this).attr('name');
        var optionValue = $(this).val();
        var nonce = $('#ai-engine-nonce').val(); // Make sure this nonce is in your HTML

        showSpinner(); // Show the spinner when saving

        // Send the AJAX request to save the setting
        $.post(ajaxurl, {
            action: 'aipower_save_advanced_setting',
            option_name: optionName,
            option_value: optionValue,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message); // Show success message
            } else {
                showError(response.data.message || 'An error occurred while saving the setting.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });
    // Apply Modal Logic to Advanced Settings Modal
    setupModalLogic('#aipower_advanced_settings_modal', '#aipower-advanced-settings-icon');

    // -------------------- LOGIC: Save Google Safety Settings --------------------
    $('#aipower_safety_settings_modal select').on('change', function () {
        var settings = {};
        $('#aipower_safety_settings_modal select').each(function () {
            var category = $(this).attr('id');
            var threshold = $(this).val();
            settings[category] = threshold;
        });
        var nonce = $('#ai-engine-nonce').val();

        showSpinner(); // Show the spinner when saving

        $.post(ajaxurl, {
            action: 'aipower_save_google_safety_settings',
            settings: settings,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message); // Show success message
            } else {
                showError(response.data.message || 'An error occurred while saving the safety settings.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });

    // Apply Modal Logic to Safety Settings Modal
    setupModalLogic('#aipower_safety_settings_modal', '#aipower-safety-settings-icon');

    // -------------------- NAVIGATION: Sub-Tab Navigation Logic --------------------
    // Generate sub-tabs dynamically
    $('.aipower-tab-pane').each(function () {
        var $tabPane = $(this);
        var $subTabsContainer = $tabPane.find('.aipower-sub-tabs');
        var $subTabPanes = $tabPane.find('.aipower-sub-tab-pane');

        if ($subTabPanes.length > 0) {
            $subTabPanes.each(function (index) {
                var $subTabPane = $(this);
                var tabId = $subTabPane.attr('id');
                var tabName = $subTabPane.data('tab-name');

                var activeClass = '';
                if ($subTabPane.hasClass('active')) {
                    activeClass = 'active';
                }

                // Append separator as a separate span
                if (index > 0) {
                    $subTabsContainer.append('<span class="separator"> | </span>');
                }

                var $link = $('<a href="#" class="aipower-sub-tab-link ' + activeClass + '" data-sub-tab="' + tabId + '">' + tabName + '</a>');
                $subTabsContainer.append($link);
            });
        }
    });

    // Sub-tab click handler
    $(".aipower-sub-tabs").on('click', '.aipower-sub-tab-link', function (e) {
        if ($(this).hasClass('active')) {
            e.preventDefault();
            return; // Do nothing if the link is active
        }

        e.preventDefault();

        var parentTabPane = $(this).closest('.aipower-tab-pane');

        // Remove 'active' class from all links and panes
        parentTabPane.find(".aipower-sub-tab-link").removeClass("active");
        parentTabPane.find(".aipower-sub-tab-pane").removeClass("active");

        // Add 'active' class to the clicked link and corresponding pane
        $(this).addClass("active");
        parentTabPane.find("#" + $(this).data("sub-tab")).addClass("active");
    });


    // -------------------- LOGIC: Uncheck 'Generate Title from Keywords' if 'Include Original Title in the Prompt' is checked --------------------
    $('#_wpaicg_gen_title_from_keywords').on('change', function () {
        var isChecked = $(this).is(':checked');

        // Enable or disable 'Include Original Title in the Prompt' based on 'Generate Title from Keywords' checkbox
        if (!isChecked) {
            $('#_wpaicg_original_title_in_prompt').prop('checked', false).prop('disabled', true).val('0');
        } else {
            $('#_wpaicg_original_title_in_prompt').prop('disabled', false).val('1');
        }
    });

    // On page load, if 'Generate Title from Keywords' is unchecked, disable and uncheck the 'Include Original Title in the Prompt' checkbox and set its value to false
    if (!$('#_wpaicg_gen_title_from_keywords').is(':checked')) {
        $('#_wpaicg_original_title_in_prompt').prop('disabled', true).prop('checked', false).val('0');
    }

    // -------------------- LOGIC: Save Content, SEO and Image Settings --------------------
    // Assuming the nonce is correctly output in a hidden input with ID 'ai-engine-nonce'
    const nonce = $('#ai-engine-nonce').val();
    const ajaxAction = 'aipower_save_content_settings'; // Existing AJAX action

    /**
     * Save setting via AJAX.
     * @param {string} field 
     * @param {string} value 
     */
    const saveSetting = (field, value) => {
        showSpinner();

        $.post(ajaxurl, {
            action: ajaxAction,
            field,
            value,
            _wpnonce: nonce
        })
        .done(response => {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while saving the setting.');
            }
        })
        .fail(() => {
            showError('Failed to connect to the server. Please try again.');
        });
    };

    /**
     * Configuration for fields and their dependencies.
     */
    const fieldsConfig = [
        // Existing Content Settings
        { field: 'wpai_language', selector: '#aipower-language-dropdown', tooltip: '<p>If your language is not listed, you can enable custom prompt and instruct the AI to write in your preferred language.</p>' },
        { field: 'wpai_writing_style', selector: '#aipower-writing-style-dropdown', tooltip: '<p>If none of the writing styles match your requirements, you can enable custom prompt and instruct the AI to write in your preferred style.</p>' },
        { field: 'wpai_writing_tone', selector: '#aipower-writing-tone-dropdown', tooltip: '<p>If none of the writing tones match your requirements, you can enable custom prompt and instruct the AI to write in your preferred tone.</p>' },
        { field: 'wpai_number_of_heading', selector: '#aipower-number-of-heading-dropdown', tooltip: '<p>Choose the number of headings you want in your content.</p>' },
        { field: 'wpai_heading_tag', selector: '#aipower-heading-tag-dropdown', tooltip: '<p>Choose the heading tag you want to use for your headings.</p>' },
        { field: 'wpai_add_tagline', selector: '#aipower-add-tagline', tooltip: '<p>Generates a tagline at the beginning of the content.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpai_modify_headings', selector: '#aipower-modify-headings', tooltip: '<p>Enables you to edit the headings before generating content.</p><p>Available only in Express Mode.</p>' },
        { field: 'wpai_add_keywords_bold', selector: '#aipower-add-keywords-bold', tooltip: '<p>Makes the keywords bold in the content.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpai_add_faq', selector: '#aipower-add-faq', tooltip: '<p>Generates a FAQ section at the end of the content.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_toc', selector: '#aipower_toc', toggleGroups: ['#aipower_toc_settings'] },
        { field: 'wpaicg_toc_title', selector: '#aipower_toc_title', tooltip: '<p>You can specify a custom title for the Table of Contents.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_toc_title_tag', selector: '#aipower_toc_title_tag', tooltip: '<p>Choose the heading tag you want to use for the Table of Contents title.</p>' },
        { field: 'wpai_add_intro', selector: '#aipower_add_intro', toggleGroups: ['#aipower_intro_settings'] },
        { field: 'wpaicg_intro_title_tag', selector: '#aipower_intro_title_tag', tooltip: '<p>Choose the heading tag you want to use for the introduction title.</p>' },
        { field: 'wpaicg_hide_introduction', selector: '#aipower_hide_introduction', tooltip: '<p>Introduction will be generated but its title will not be displayed.</p>' },
        { field: 'wpai_add_conclusion', selector: '#aipower_add_conclusion', toggleGroups: ['#aipower_conclusion_settings'] },
        { field: 'wpaicg_hide_conclusion', selector: '#wpaicg_hide_conclusion', tooltip: '<p>Conclusion will be generated but its title will not be displayed.</p>' },
        { field: 'wpaicg_conclusion_title_tag', selector: '#wpaicg_conclusion_title_tag', tooltip: '<p>Choose the heading tag you want to use for the conclusion title.</p>' },
        { field: 'wpaicg_content_custom_prompt_enable', selector: '#aipower_custom_prompt_enable', toggleGroups: ['#aipower_custom_prompt_box'] },
        { field: 'wpaicg_content_custom_prompt', selector: '#aipower_custom_prompt' },
        { field: 'wpaicg_custom_image_prompt_enable', selector: '#aipower_custom_image_prompt_enable', toggleGroups: ['#aipower_custom_prompt_box'] },
        { field: 'wpaicg_custom_image_prompt', selector: '#aipower_custom_image_prompt' },
        { field: 'wpaicg_custom_featured_image_prompt_enable', selector: '#aipower_custom_featured_image_prompt_enable', toggleGroups: ['#aipower_custom_prompt_box'] },
        { field: 'wpaicg_custom_featured_image_prompt', selector: '#aipower_custom_featured_image_prompt' },
        { field: '_wpaicg_seo_meta_desc', selector: '#_wpaicg_seo_meta_desc', tooltip: '<p>Generates a meta description.</p><p>One of these plugins must be enabled (Yoast, All In One SEO, Rank Math, or The SEO Framework); otherwise, the meta will be generated but not included in the content.</p>'},
        { field: '_wpaicg_seo_meta_tag', selector: '#_wpaicg_seo_meta_tag', tooltip: '<p>Adds a meta tag directly to your content.</p><p>Enable this only if you are not using any SEO plugin.</p>'},
        { field: '_yoast_wpseo_metadesc', selector: '#_yoast_wpseo_metadesc', tooltip: '<p>Updates the meta description field in Yoast SEO plugin.</p>'},
        { field: '_aioseo_description', selector: '#_aioseo_description', tooltip: '<p>Updates the meta description field in All In One SEO plugin.</p>'},
        { field: 'rank_math_description', selector: '#rank_math_description', tooltip: '<p>Updates the meta description field in Rank Math SEO plugin.</p>'},
        { field: '_wpaicg_genesis_description', selector: '#_wpaicg_genesis_description', tooltip: '<p>Updates the meta description field in The SEO Framework plugin.</p>' },
        { field: '_wpaicg_gen_title_from_keywords', selector: '#_wpaicg_gen_title_from_keywords', tooltip: '<p>Generates title using provided keywords. </p><p>If no keywords are supplied, no title will be generated.</p><p> Works only in Express Mode, AutoGPT - Bulk Editor, and AutoGPT - Google Sheets.</p>' },
        { field: '_wpaicg_original_title_in_prompt', selector: '#_wpaicg_original_title_in_prompt', tooltip: '<p>Includes the original title in the prompt when generating a new title. Useful for maintaining context while refining the title.</p><p>This feature works only if keywords are provided.</p><p><b><u>Example:</u></b></p><p><b>Original Title:</b> How to Grow Indoor Plants</p><p><b>Keywords:</b> Houseplants, Growth, Tips</p><p><b>Generated Title without Original Title:</b> The Ultimate Guide to Houseplant Growth: Tips and Tricks</p><p><b>Generated Title with Original Title:</b> How to Grow Indoor Plants: Essential Tips for Thriving Houseplants</p>'},
        { field: '_wpaicg_focus_keyword_in_url', selector: '#_wpaicg_focus_keyword_in_url', tooltip: '<p>Ensures the focus keyword is included in the URL slug.</p><p>If multiple keywords are provided, only the first one will be used.</p><p>This feature works only when a focus keyword is set and the keyword is not already in the URL.</p>'},
        { field: '_wpaicg_sentiment_in_title', selector: '#_wpaicg_sentiment_in_title', tooltip: '<p>Adds emotions to the title, either positive or negative.</p>' },
        { field: '_wpaicg_power_word_in_title', selector: '#_wpaicg_power_word_in_title', tooltip: '<p>Adds a power word to the title to make it more engaging.</p>' },
        { field: '_wpaicg_shorten_url', selector: '#_wpaicg_shorten_url', tooltip: '<p>Shortens URL to stay within 70 characters, including your domain name and slug.</p>' },
        { field: 'wpai_cta_pos', selector: '#aipower-cta-position-dropdown', tooltip: '<p>Choose the position of the call-to-action link in your content.</p><p>The link will be added either at the beginning or end of the content.</p><p>Available only in Express Mode, AutoGPT - Bulk Editor, and AutoGPT - Google Sheets.</p>' },
        { field: 'img_size', selector: '#aipower-img-size', tooltip: '<p>Choose the size of the image to be generated.</p><p>Must be one of <b>256x256</b>, <b>512x512</b>, or <b>1024x1024</b> for Dall-E 2.</p><p>Must be one of <b>1024x1024</b>, <b>1792x1024</b>, or <b>1024x1792</b> for Dall-E 3 models.</p>' },
        { field: 'wpaicg_dalle_type', selector: '#aipower-dalle-type', tooltip: '<p>The style of the generated images.</p><p>Must be one of <b>Vivid</b> or <b>Natural</b>.</p><p>Vivid causes the model to lean towards generating hyper-real and dramatic images.</p><p>Natural causes the model to produce more natural, less hyper-real looking images.</p><p>This parameter is only supported for Dall-E 3.</p>' },
        { field: '_wpaicg_image_style', selector: '#aipower-image-style', tooltip: '<p>The style of the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[artist]', selector: '#aipower-artist', tooltip: '<p>Choose an artist style for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[photography_style]', selector: '#aipower-photography-style', tooltip: '<p>Choose a photography style for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[lighting]', selector: '#aipower-lighting', tooltip: '<p>Choose a lighting style for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[subject]', selector: '#aipower-subject', tooltip: '<p>Choose a subject for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[camera_settings]', selector: '#aipower-camera-settings', tooltip: '<p>Choose camera settings for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[composition]', selector: '#aipower-composition', tooltip: '<p>Choose a composition style for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[resolution]', selector: '#aipower-resolution', tooltip: '<p>Choose the resolution for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[color]', selector: '#aipower-color', tooltip: '<p>Choose a color scheme for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_custom_image_settings[special_effects]', selector: '#aipower-special-effects', tooltip: '<p>Choose special effects for the generated images.</p><p>It will be ignored if the custom prompt is enabled.</p>' },
        { field: 'wpaicg_sd_api_key', selector: '#aipower-replicate-api-key', tooltip: '<p>Enter your API key to use the Replicate feature.</p>' },
        { field: 'wpaicg_default_replicate_model', selector: '#aipower-replicate-model', tooltip: '<p>Choose the default model for the Replicate feature.</p><p>This model will be used in both Express Mode and AutoGPT module.</p><p>Hit Sync button on the right to fetch the latest models.</p>' },
        { field: 'wpaicg_sd_api_version', selector: '#aipower-replicate-version', tooltip: '<p>Choose the API version for the Replicate model.</p><p>By default, it is set to the latest version. However, you can enter a specific version if you want to use an older version.</p>' },
        { field: 'wpaicg_pexels_api', selector: '#aipower-pexels-api-key', tooltip: '<p>Enter your API key to use the Pexels.</p>' },
        { field: 'wpaicg_pexels_orientation', selector: '#aipower-pexels-orientation', tooltip: '<p>Desired photo orientation.</p><p>The current supported orientations are: <b>Landscape</b>, <b>Portrait</b> or <b>Square</b>.</p><p>Select <b>None</b> to use the default orientation.</p>' },
        { field: 'wpaicg_pexels_size', selector: '#aipower-pexels-size', tooltip: '<p>Desired photo size.</p><p>The current supported sizes are: <b>Small</b>, <b>Medium</b> or <b>Large</b>.</p><p>Select <b>None</b> to use the default size.</p>' },
        { field: 'wpaicg_pexels_enable_prompt', selector: '#aipower-pexels-enable-prompt', tooltip: '<p>Enabling this option allows the plugin to use AI (OpenAI, Google, OpenRouter, etc.) to extract a single keyword from your title for searching images on Pexels.</p><p>For example, if your title is “The Impact of Climate Change on Global Agriculture,” the plugin will send it to the AI for keyword extraction and receive something like “#climate” or “#agriculture.”</p><p>This keyword will then be used to search for relevant images.</p><p>If this option is disabled, the full title will be used for the search instead.</p>' },
        { field: 'wpaicg_pixabay_api', selector: '#aipower-pixabay-api-key', tooltip: '<p>Enter your API key to use the Pixabay.</p>' },
        { field: 'wpaicg_pixabay_language', selector: '#aipower-pixabay-language', tooltip: '<p>Choose the language to search for images.</p>' },
        { field: 'wpaicg_pixabay_type', selector: '#aipower-pixabay-type', tooltip: '<p>Filter results by image type.</p>' },
        { field: 'wpaicg_pixabay_orientation', selector: '#aipower-pixabay-orientation', tooltip: '<p>Filter results by image orientation.</p>' },
        { field: 'wpaicg_pixabay_order', selector: '#aipower-pixabay-order', tooltip: '<p>How the results should be ordered.</p>' },
        { field: 'wpaicg_pixabay_enable_prompt', selector: '#aipower-pixabay-enable-prompt', tooltip: '<p>Enabling this option allows the plugin to use AI (OpenAI, Google, OpenRouter, etc.) to extract a single keyword from your title for searching images on Pixabay.</p><p>For example, if your title is “The Impact of Climate Change on Global Agriculture,” the plugin will send it to the AI for keyword extraction and receive something like “#climate” or “#agriculture.”</p><p>This keyword will then be used to search for relevant images.</p><p>If this option is disabled, the full title will be used for the search instead.</p>' },
        { field: 'wpaicg_image_source', selector: 'input.aipower-image-source:checkbox' },
        { field: 'wpaicg_featured_image_source', selector: 'input.aipower-featured-image-source:checkbox' },
        { field: 'wpaicg_image_source', selector: '#aipower-dalle-variant', tooltip: '<p>Choose the variant of DALL-E model to generate images for the body of your content.</p><p>This will generate image based on your title and then add it to in the middle of your content.</p>' },
        { field: 'wpaicg_featured_image_source', selector: '#aipower-dalle-featured-variant', tooltip: '<p>Choose the variant of DALL-E model to generate featured images for your content.</p><p>This will generate image based on your title and then add it to the featured image section.</p>' },
        { field: 'wpaicg_woo_generate_title', selector: '#aipower_woo_generate_title',tooltip: '<p>Generates a title for your WooCommerce product.</p>' },
        { field: 'wpaicg_woo_generate_description', selector: '#aipower_woo_generate_description', tooltip: '<p>Generates a full description for your WooCommerce product.</p>' },
        { field: 'wpaicg_woo_generate_short', selector: '#aipower_woo_generate_short', tooltip: '<p>Generates a short description for your WooCommerce product.</p>' },
        { field: 'wpaicg_woo_generate_tags', selector: '#aipower_woo_generate_tags', tooltip: '<p>Generates tags for your WooCommerce product.</p><p>If you are using a custom prompt, make sure that you instruct the AI to present the keywords in a comma-separated format and avoid using symbols such as -, #, etc.</p>' },
        { field: 'wpaicg_woo_meta_description', selector: '#aipower_woo_meta_description', tooltip: '<p>Generates a meta description for your WooCommerce product.</p>' },
        { field: '_wpaicg_shorten_woo_url', selector: '#aipower_shorten_woo_url', tooltip: '<p>Shortens the URL to stay within 70 characters, including your domain name and slug.</p>' },
        { field: 'wpaicg_generate_woo_focus_keyword', selector: '#aipower_generate_woo_focus_keyword', tooltip: '<p>Generates a focus keyword for your WooCommerce product.</p><p>It will use the language you have selected in the Content tab to automatically generate focus keywords for your product listings based on built-in prompts.</p><p>You can enable custom prompt and instruct the AI to generate focus keywords that meet your requirements.</p>' },
        { field: 'wpaicg_enforce_woo_keyword_in_url', selector: '#aipower_enforce_woo_keyword_in_url', tooltip: '<p>Ensures the focus keyword is included in the URL slug.</p><p>If multiple keywords are provided, only the first one will be used.</p><p>This feature works only when a focus keyword is set and the keyword is not already in the URL.</p>' },
        { field: 'wpaicg_woo_custom_prompt', selector: '#aipower_woo_custom_prompt_enable', toggleGroups: ['#aipower_woo_custom_prompt_box'] },
        { field: 'wpaicg_woo_custom_prompt_title', selector: '#aipower_woo_custom_prompt_title', tooltip: '<p>You can write your own custom prompt to generate a title for your WooCommerce product.</p><p>Make sure to include %s variable in the prompt.</p>' },
        { field: 'wpaicg_woo_custom_prompt_short', selector: '#aipower_custom_prompt_short', tooltip: '<p>You can write your own custom prompt to generate a short description for your WooCommerce product.</p><p>Make sure to include %s variable in the prompt.</p>' },
        { field: 'wpaicg_woo_custom_prompt_description', selector: '#aipower_custom_prompt_desc', tooltip: '<p>You can write your own custom prompt to generate a full description for your WooCommerce product.</p><p>Make sure to include %s variable in the prompt.</p>' },
        { field: 'wpaicg_woo_custom_prompt_keywords', selector: '#aipower_custom_prompt_tags', tooltip: '<p>You can write your own custom prompt to generate tags for your WooCommerce product.</p><p>Make sure to include %s variable in the prompt.</p><p>Make sure that you instruct the AI to present the keywords in a comma-separated format and avoid using symbols such as -, #, etc.</p>' },
        { field: 'wpaicg_woo_custom_prompt_meta', selector: '#aipower_custom_prompt_meta', tooltip: '<p>You can write your own custom prompt to generate a meta description for your WooCommerce product.</p><p>Make sure to include %s variable in the prompt.</p>' },
        { field: 'wpaicg_woo_custom_prompt_focus_keyword', selector: '#aipower_custom_prompt_focus_keyword' },
        { field: 'wpaicg_comment_prompt', selector: '#aipower_comment_prompt' },
        { field: 'wpaicg_search_font_size', selector: '#aipower_search_font_size', tooltip: '<p>Set the font size for the search bar.</p>' },
        { field: 'wpaicg_search_placeholder', selector: '#aipower_search_placeholder', tooltip: '<p>Set the placeholder text for the search bar.</p>' },
        { field: 'wpaicg_search_font_color', selector: '#aipower_search_font_color', tooltip: '<p>Set the font color for the search bar.</p>' },
        { field: 'wpaicg_search_border_color', selector: '#aipower_search_border_color', tooltip: '<p>Set the border color for the search bar.</p>' },
        { field: 'wpaicg_search_bg_color', selector: '#aipower_search_bg_color', tooltip: '<p>Set the background color for the search bar.</p>' },
        { field: 'wpaicg_search_width', selector: '#aipower_search_width', tooltip: '<p>Set the width for the search bar.</p><p>You can either set it in pixels or percentage.</p>' },
        { field: 'wpaicg_search_height', selector: '#aipower_search_height', tooltip: '<p>Set the height for the search bar.</p><p>You can either set it in pixels or percentage.</p>' },
        { field: 'wpaicg_search_no_result', selector: '#aipower_search_no_result', tooltip: '<p>How many search results to show.</p>' },
        { field: 'wpaicg_search_result_font_size', selector: '#aipower_search_result_font_size', tooltip: '<p>Set the font size for the search results.</p>' },
        { field: 'wpaicg_search_result_font_color', selector: '#aipower_search_result_font_color', tooltip: '<p>Set the font color for the search results.</p>' },
        { field: 'wpaicg_search_result_bg_color', selector: '#aipower_search_result_bg_color', tooltip: '<p>Set the background color for the search results.</p>' },
        { field: 'wpaicg_search_loading_color', selector: '#aipower_search_loading_color', tooltip: '<p>Set the color for the loading spinner.</p>' },
        { field: 'wpaicg_order_status_token', selector: '#aipower_token_sale_status' },
        { field: 'wpaicg_editor_change_action', selector: '#aipower_editor_change_action' },
        { field: 'wpaicg_google_api_key', selector: '#aipower_google_common_api_key', tooltip: '<p>If you already have an API key for Internet Browsing, you can use that same API key for Text-to-Speech as well.</p><p>For more information, visit our <a href="https://docs.aipower.org/docs/Chatbot/tools#text-to-speech" target="_blank">documentation</a>.</p>' },
        { field: 'wpaicg_google_api_key', selector: '#aipower_google_common_api_key_for_internet', tooltip: '<p>If you already have an API key for Google Text-to-Speech, you can use that same API key for Internet Browsing as well.</p><p>For more information, visit our <a href="https://docs.aipower.org/docs/Chatbot/tools#internet-browsing" target="_blank">documentation</a>.</p>' },
        { field: 'wpaicg_elevenlabs_api', selector: '#aipower_elevenlabs_api_key', tooltip: '<p>You can get your API key from <a href="https://elevenlabs.io/app/settings/api-keys" target="_blank">here</a>.</p><p>For more information, visit our <a href="https://docs.aipower.org/docs/Chatbot/tools#text-to-speech" target="_blank">documentation</a>.</p>' },
        { field: 'wpaicg_google_search_engine_id', selector: '#aipower_google_custom_search_engine_id', tooltip: '<p>You can get your CSE ID from <a href="https://programmablesearchengine.google.com/" target="_blank">here</a>.</p><p>For more information, visit our <a href="https://docs.aipower.org/docs/Chatbot/tools#internet-browsing" target="_blank">documentation</a>.</p>' },
        { field: 'wpaicg_google_search_country', selector: '#aipower_google_cse_region', tooltip: '<p>Select the country to restrict search results to a specific region.</p><p> For example, selecting "Japan" will retrieve search results from the Japan only.</p><p>Useful for local search results.</p>' },
        { field: 'wpaicg_google_search_language', selector: '#aipower_google_cse_language', tooltip: '<p>Select the language to restrict search results to a specific language.</p><p> For example, selecting "Chinese" will retrieve search results in Chinese only.</p>' },
        { field: 'wpaicg_google_search_num', selector: '#aipower_google_cse_results', tooltip: '<p>Set the number of search results to feed the chat bot.</p><p>The more results, the more context the chat bot has to generate responses but it may feed irrelevant information as well.</p><p> Keep it 1 if you are sure the first search result is always the most relevant and enough for the chat bot.</p>' },
        { field: 'wpaicg_banned_words', selector: '#aipower_chat_banned_words', tooltip: '<p>You can add banned words that will be filtered out from the chat.</p><p>If a user sends a message containing any of these words, the message will be blocked.</p>' },
        { field: 'wpaicg_banned_ips', selector: '#aipower_chat_banned_ips', tooltip: '<p>You can add banned IPs that will be blocked from accessing the chat.</p><p>If a user with a banned IP tries to access the chat, they will be blocked.</p>' },
        { field: 'wpaicg_user_uploads', selector: '#aipower_chat_image_user_uploads', tooltip: '<p>Choose where to store uploaded images:</p> <p><b>WordPress Media Library</b>: Saves images in Media Library and adds their records to the database.</p><p><b>Upload Folder</b>: Stores images in /wp-content/uploads/ folder, without adding database records.</p>' },
        { field: 'wpaicg_img_processing_method', selector: '#aipower_chat_image_method', tooltip: '<p>Choose how to process images:</p> <p><b>Base64</b>: Converts images to Base64 format and sends them to the api.</p><p><b>URL</b>: Sends image URLs to the api. URL works only if the image is accessible from the internet.</p>' },
        { field: 'wpaicg_img_vision_quality', selector: '#aipower_chat_image_quality', tooltip: '<p>By default, the model will use the <b>auto</b> setting which will look at the image input size and decide if it should use the low or high setting.</p><p><b>Low</b> will enable the "low res" mode. The model will receive a low-res 512px x 512px version of the image, and represent the image with a budget of 85 tokens. This allows the API to return faster responses and consume fewer input tokens for use cases that do not require high detail.</p><p><b>High</b> will enable "high res" mode, which first allows the model to first see the low res image (using 85 tokens) and then creates detailed crops using 170 tokens for each 512px x 512px tile.</p>' },
        { field: 'wpaicg_delete_image', selector: '#aipower-delete-images-after-process', tooltip: '<p>Enable this option to delete the images after processing.</p><p>Useful for keeping your server clean.</p><p>Only works if the image processing method is set to Base64.</p>' },
        { field: 'wpaicg_chat_enable_sale', selector: '#aipower_enable_token_purchase',tooltip: '<p>Enable this option to allow users to purchase tokens for the chatbot. Requires WooCommerce.</p><p>For more information, visit our <a href="https://docs.aipower.org/docs/user-management-token-sale" target="_blank">documentation</a>.</p>' },
        { field: 'wpaicg_elevenlabs_hide_error', selector: '#aipower_elevenlabs_hide_error', tooltip: '<p>Enable this option to hide error messages from the ElevenLabs API.</p><p>Messages like "Not enough credits" or "Unable to generate a response" will not be shown to the user if this option is enabled.</p>' },
        { field: 'wpaicg_typewriter_effect', selector: '#aipower_chat_typewriter_effect', tooltip: '<p>Enable this option to display the chat messages with a typewriter effect.</p><p>1 is the fastest, 10 is the slowest.</p>' },
        { field: 'wpaicg_typewriter_speed', selector: '#aipower_chat_typewriter_speed'},
        { field: 'wpaicg_autoload_chat_conversations', selector: '#aipower_chat_dont_load_past_chats',tooltip: '<p>If enabled, the chat will not load past conversations on page load. </p><p>Useful if you want to keep the chat window clean on page load.</p>' },
        { field: 'wpaicg_ip_anonymization', selector: '#aipower-ip-anonymization', tooltip: '<p>Enable this option to anonymize user IP addresses in the chat logs for GDPR compliance.</p>' },

    ];

    /**
     * Attach event listeners based on fields configuration.
     */
    fieldsConfig.forEach(({ field, selector, toggleGroups }) => {
        const $elements = $(selector);
        if ($elements.length === 0) return; // Skip if element doesn't exist
    
        const isCheckbox = $elements.is(':checkbox');
    
        $elements.on('change', function () {
            const value = isCheckbox ? ($(this).is(':checked') ? '1' : '0') : $(this).val();
            saveSetting(field, value);
    
            // Handle toggle groups if defined
            if (toggleGroups && toggleGroups.length) {
                const condition = isCheckbox ? $(this).is(':checked') : true;
                toggleVisibility(toggleGroups, condition);
            }
        });
    });
    

    /**
     * Toggle visibility of specified groups based on condition.
     * @param {Array<string>} selectors 
     * @param {boolean} condition 
     */
    const toggleVisibility = (selectors, condition) => {
        selectors.forEach(selector => {
            $(selector).toggle(condition);
        });
    };

    /**
     * Initialize visibility for toggle groups.
     */
    const initializeVisibility = () => {
        fieldsConfig.forEach(({ selector, toggleGroups }) => {
            if (toggleGroups && toggleGroups.length) {
                const $trigger = $(selector);
                const condition = $trigger.is(':checkbox') ? $trigger.is(':checked') : !!$trigger.val();
                toggleVisibility(toggleGroups, condition);
            }
        });
    };

    // For image source checkboxes
    $('.aipower-image-source').on('change', function() {
        if ($(this).is(':checked')) {
            // Uncheck all other checkboxes in the same group
            $('.aipower-image-source').not(this).prop('checked', false);
            saveSetting('wpaicg_image_source', $(this).val());
        } else {
            // If unchecked, pass 'none' as the value
            saveSetting('wpaicg_image_source', 'none');
        }
    });

    // For featured image source checkboxes
    $('.aipower-featured-image-source').on('change', function() {
        if ($(this).is(':checked')) {
            // Uncheck all other checkboxes in the same group
            $('.aipower-featured-image-source').not(this).prop('checked', false);
            saveSetting('wpaicg_featured_image_source', $(this).val());
        } else {
            // If unchecked, pass 'none' as the value
            saveSetting('wpaicg_featured_image_source', 'none');
        }
    });
    
    // Initial visibility setup
    initializeVisibility();
    
    // -------------------- LOGIC: Reset Custom Prompt --------------------
    $('#reset_custom_prompt').on('click', function () {
        const defaultPrompt = $('#aipower_custom_prompt').data('default');
        $('#aipower_custom_prompt').val(defaultPrompt).trigger('change'); // Trigger change event to autosave
    });

    // -------------------- LOGIC: Reset Custom Image Prompt --------------------
    $('#reset_custom_image_prompt').on('click', function () {
        const defaultPrompt = $('#aipower_custom_image_prompt').data('default');
        $('#aipower_custom_image_prompt').val(defaultPrompt).trigger('change'); // Trigger change event to autosave
    });

    // -------------------- LOGIC: Reset Custom Featured Image Prompt --------------------
    $('#reset_custom_featured_image_prompt').on('click', function () {
        const defaultPrompt = $('#aipower_custom_featured_image_prompt').data('default');
        $('#aipower_custom_featured_image_prompt').val(defaultPrompt).trigger('change'); // Trigger change event to autosave
    });
    

    // -------------------- LOGIC: Reset Comment Replier Prompt --------------------
    $('#reset_comment_prompt').on('click', function () {
        const defaultCommentPrompt = $('#aipower_comment_prompt').data('default-prompt');
        $('#aipower_comment_prompt').val(defaultCommentPrompt).trigger('change'); // Trigger change event to autosave
    });
    
    // -------------------- LOGIC: Handle Replicate Model Sync --------------------
    $('.aipower_sync_replicate_models').on('click', function() {
        var btn = $(this);
        var icon = btn.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val(); // Reuse the nonce from the new setup
        var currentSelectedModel = $('#aipower-replicate-model').val(); // Capture the currently selected model

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            dataType: 'JSON',
            data: {
                action: 'wpaicg_fetch_replicate_models', // Updated action name
                _wpnonce: nonce // Use the nonce from the new setup
            },
            beforeSend: function() {
                icon.addClass('aipower-rotating'); // Add rotating class to show the spinner
            },
            success: function(res) {
                icon.removeClass('aipower-rotating'); // Remove rotating class after success

                if (res.status === 'success') {
                    // Populate the dropdown grouped by owner
                    var $modelDropdown = $('#aipower-replicate-model'); // Use the new ID
                    $modelDropdown.empty(); // Clear the dropdown

                    $.each(res.models, function(owner, models) {
                        // Add optgroup for the owner
                        var optgroup = $('<optgroup>').attr('label', owner);

                        // Add each model under the owner
                        $.each(models, function(index, model) {
                            var option = $('<option>', {
                                value: model.name,
                                text: model.name + ' (' + model.run_count + ' runs)',
                                'data-version': model.latest_version, // Attach the version as a data attribute
                                'data-schema': model.schema 
                            });

                            optgroup.append(option);
                        });

                        $modelDropdown.append(optgroup);
                    });

                    // Re-select the previously selected model after sync if it exists
                    if ($modelDropdown.find("option[value='" + currentSelectedModel + "']").length > 0) {
                        $modelDropdown.val(currentSelectedModel); // Re-select the previously selected model
                    }

                    // Trigger change event to update the version field with the default model's version
                    $modelDropdown.trigger('change');
                    updateModelFields();

                    // Show success message
                    showSuccess('Replicate models synced successfully.');
                } else {
                    showError(res.msg || 'An error occurred.');
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Remove rotating class on error
                showError('Error: ' + errorThrown);
            }
        });
    });

    // -------------------- LOGIC: Append Version to the Version Field on Model Selection --------------------
    $('#aipower-replicate-model').on('change', function() {
        var selectedVersion = $(this).find(':selected').data('version'); // Get the version from the selected option
        $('#aipower-replicate-version').val(selectedVersion).trigger('change'); // Set the version field with the selected version and trigger change event
    });

    // -------------------- LOGIC: Handle Image Tab Switching --------------------
    // Function to handle tab switching
    $('.aipower-image-tab-btn').on('click', function() {
        var tabName = $(this).data('tab');

        // Hide all tab content
        $('.aipower-image-tab-pane').hide();

        // Remove active class from all tab buttons
        $('.aipower-image-tab-btn').removeClass('active');

        // Show the clicked tab content and add active class to the clicked button
        $('#' + tabName).show();
        $(this).addClass('active');
    });

    // Show default tab (DALL-E) by default
    $('#dalle-tab').show();

    // Generalized Modal Logic Function
    function setupModalLogic(modalSelector, settingsIconSelector, checkboxSelector) {
        const modal = $(modalSelector);
        const settingsIcon = $(settingsIconSelector);
        const modalClose = modal.find('.aipower-close');
        const checkbox = $(checkboxSelector);

        // Enable/Disable settings icon based on checkbox state
        checkbox.on('change', function () {
            const isChecked = $(this).is(':checked');
            settingsIcon.prop('disabled', !isChecked);  // Enable or disable the settings icon
        });

        // Open Modal when Settings Icon is clicked
        settingsIcon.on('click', function () {
            if (!$(this).prop('disabled')) {
                modal.fadeIn(200);
            }
        });

        // Close Modal when 'x' is clicked
        modalClose.on('click', function () {
            modal.fadeOut(200);
        });

        // Close Modal when clicking outside the modal content
        $(window).on('click', function (event) {
            if ($(event.target).is(modal)) {
                modal.fadeOut(200);
            }
        });

        // Optional: Close modal with the Esc key
        $(document).on('keydown', function (event) {
            if (event.key === "Escape" && modal.is(':visible')) {
                modal.fadeOut(200);
            }
        });
    }

    // Apply Modal Logic to ToC, Introduction, and Conclusion
    setupModalLogic('#aipower_toc_modal', '#aipower_toc_settings_icon', '#aipower_toc');
    setupModalLogic('#aipower_intro_modal', '#aipower_intro_settings_icon', '#aipower_add_intro');
    setupModalLogic('#aipower_conclusion_modal', '#aipower_conclusion_settings_icon', '#aipower_add_conclusion');
    setupModalLogic('#aipower_custom_prompt_modal', '#aipower_custom_prompt_settings_icon', '#aipower_custom_prompt_enable');
    setupModalLogic('#aipower_custom_image_prompt_modal', '#aipower_custom_image_prompt_settings_icon', '#aipower_custom_image_prompt_enable');
    setupModalLogic('#aipower_custom_featured_image_prompt_modal', '#aipower_custom_featured_image_prompt_settings_icon', '#aipower_custom_featured_image_prompt_enable');
    setupModalLogic('#aipower_writing_settings_modal', '#aipower_writing_settings_icon', '#aipower_writing_settings_enable');
    setupModalLogic('#aipower_woo_custom_prompt_modal', '#aipower_woo_custom_prompt_settings_icon', '#aipower_woo_custom_prompt_enable');
    setupModalLogic('#aipower_comment_replier_modal', '#aipower_comment_replier_settings_icon');
    setupModalLogic('#aipower_semantic_search_modal', '#aipower_semantic_search_settings_icon', '#aipower_semantic_search_enable');
    setupModalLogic('#aipower_token_sale_modal', '#aipower_token_sale_settings_icon', '#aipower_token_sale_enable');
    setupModalLogic('#aipower_ai_assistant_modal', '#aipower_ai_assistant_settings_icon', '#aipower_ai_assistant_enable');
    setupModalLogic('#aipower_text_to_speech_modal', '#aipower_text_to_speech_settings_icon', '#aipower_text_to_speech_settings_icon');
    setupModalLogic('#aipower_common_internet_settings_modal', '#aipower_common_internet_settings_icon', '#aipower_common_internet_settings_icon');
    setupModalLogic('#aipower_chat_security_modal', '#aipower_chat_security_settings_icon', '#aipower_chat_security_settings_icon');
    setupModalLogic('#aipower_chat_image_modal', '#aipower_chat_image_settings_icon', '#aipower_chat_image_settings_icon');
    setupModalLogic('#aipower_chat_conversations_modal', '#aipower_conversations_settings_icon', '#aipower_conversations_settings_icon');

    // Initially update the state of the settings icon based on the checkbox status at page load
    $('#aipower_toc_settings_icon').prop('disabled', !$('#aipower_toc').is(':checked'));
    $('#aipower_intro_settings_icon').prop('disabled', !$('#aipower_add_intro').is(':checked'));
    $('#aipower_conclusion_settings_icon').prop('disabled', !$('#aipower_add_conclusion').is(':checked'));
    $('#aipower_custom_prompt_settings_icon').prop('disabled', !$('#aipower_custom_prompt_enable').is(':checked'));
    $('#aipower_custom_image_prompt_settings_icon').prop('disabled', !$('#aipower_custom_image_prompt_enable').is(':checked'));
    $('#aipower_custom_featured_image_prompt_settings_icon').prop('disabled', !$('#aipower_custom_featured_image_prompt_enable').is(':checked'));
    $('#aipower_woo_custom_prompt_settings_icon').prop('disabled', !$('#aipower_woo_custom_prompt_enable').is(':checked'));

    // -------------------- LOGIC: Handle Image Source Selection --------------------
    // Open modal when the configuration icon is clicked
    $('.aipower-settings-icon').on('click', function () {
        const provider = $(this).data('provider');

        // Check if provider is defined and handle only if it exists
        if (provider) {
            // Open the corresponding modal based on provider
            switch (provider) {
                case 'dalle':
                    $('#aipower-dalle-modal').show();
                    break;
                case 'replicate':
                    $('#aipower-replicate-modal').show();
                    break;
                case 'pexels':
                    $('#aipower-pexels-modal').show();
                    break;
                case 'pixabay':
                    $('#aipower-pixabay-modal').show();
                    break;
            }
        }
    });

    // Handle DALL-E image source selection
    $('#aipower-dalle-variant').on('change', function() {
        var selectedValue = $(this).val();
        // If 'none' is selected, uncheck the checkbox in the main table
        if (selectedValue === 'none') {
            $('input[name="wpaicg_image_source[]"][value="dalle3"]').prop('checked', false);
        } else {
            // If a DALL-E variant is selected, check the checkbox in the main table
            $('input[name="wpaicg_image_source[]"][value="dalle3"]').prop('checked', true);
        }
    });

    // Handle DALL-E featured image source selection
    $('#aipower-dalle-featured-variant').on('change', function() {
        var selectedValue = $(this).val();
        // If 'none' is selected, uncheck the checkbox in the main table
        if (selectedValue === 'none') {
            $('input[name="wpaicg_featured_image_source[]"][value="dalle3"]').prop('checked', false);
        } else {
            // If a DALL-E variant is selected, check the checkbox in the main table
            $('input[name="wpaicg_featured_image_source[]"][value="dalle3"]').prop('checked', true);
        }
    });

    // Close modal when the close button is clicked
    $('.aipower-close').on('click', function () {
        $(this).closest('.aipower-modal').hide();
    });

    // Close modal if user clicks outside the modal content
    $(window).on('click', function (e) {
        if ($(e.target).hasClass('aipower-modal')) {
            $('.aipower-modal').hide();
        }
    });

    // -------------------- LOGIC: Handle collapsible sections in woocommerce custom prompts --------------------
    $('.aipower-collapsible-toggle').on('click', function() {
        // Close all other open sections
        $('.aipower-collapsible-section').not($(this).parent()).removeClass('active');
        $('.aipower-collapsible-content').not($(this).next()).slideUp(200);

        // Toggle the clicked section
        $(this).next('.aipower-collapsible-content').slideToggle(200);
        $(this).parent('.aipower-collapsible-section').toggleClass('active');
    });

    // -------------------- LOGIC: Handle Copy to clipboard for woocommerce shortcodes --------------------
    $('.aipower-woocommerce-shortcode').on('click', function() {
        var $this = $(this);
        var textToCopy = $this.data('aipower-clipboard-text');
        navigator.clipboard.writeText(textToCopy).then(function() {
            // Create and show tooltip
            var $tooltip = $('<span class="aipower-tooltip">Copied!</span>');
            $this.append($tooltip);
            
            // Fade out and remove the tooltip
            $tooltip.fadeIn(200).delay(1000).fadeOut(200, function() {
                $(this).remove();
            });
        });
    });

    // -------------------- LOGIC: Handle WooCommerce Custom Prompt Templates --------------------
    // Define saveSetting function globally within this block
    const saveWooSetting = (field, value) => {
        // Show a spinner or some feedback (assume showSpinner is defined elsewhere)
        showSpinner();
        
        // Make the AJAX request to save the setting
        $.post(ajaxurl, {
            action: 'aipower_save_content_settings', // The existing AJAX action
            field,
            value,
            _wpnonce: $('#ai-engine-nonce').val() // Get nonce value
        })
        .done(response => {
            if (response.success) {
                // Show success message (assume showSuccess is defined elsewhere)
                showSuccess(response.data.message);
            } else {
                // Show error message if any (assume showError is defined elsewhere)
                showError(response.data.message || 'An error occurred while saving the setting.');
            }
        })
        .fail(() => {
            // Show a generic error message
            showError('Failed to connect to the server. Please try again.');
        });
    };

    // Function to handle dropdown change and update textarea
    function updateTextarea(dropdownId, textareaId, field) {
        $(dropdownId).on('change', function() {
            var selectedValue = $(this).val();
            var textarea = $(textareaId);
    
            // Clear the textarea content
            textarea.val('');
    
            // Append the selected value if it's not empty
            if (selectedValue) {
                textarea.val(selectedValue);

                // Trigger autosave after appending the value
                saveWooSetting(field, textarea.val());
            }
        });
    }
    
    // Bind dropdowns with their respective textareas and autosave fields
    updateTextarea('#aipower_woocommerce_title_dropdown', '#aipower_woo_custom_prompt_title', 'wpaicg_woo_custom_prompt_title');
    updateTextarea('#aipower_woocommerce_short_dropdown', '#aipower_custom_prompt_short', 'wpaicg_woo_custom_prompt_short');
    updateTextarea('#aipower_woocommerce_desc_dropdown', '#aipower_custom_prompt_desc', 'wpaicg_woo_custom_prompt_description');
    updateTextarea('#aipower_woocommerce_meta_dropdown', '#aipower_custom_prompt_meta', 'wpaicg_woo_custom_prompt_meta');
    updateTextarea('#aipower_woocommerce_tags_dropdown', '#aipower_custom_prompt_tags', 'wpaicg_woo_custom_prompt_keywords');
    updateTextarea('#aipower_woocommerce_focus_keyword_dropdown', '#aipower_custom_prompt_focus_keyword', 'wpaicg_woo_custom_prompt_focus_keyword');

    // -------------------- LOGIC: Handle Saving of Editor Button Menus for AI Assistant --------------------
    const ajaxWooAction = 'aipower_save_content_settings';

    // Parse the editor button menus from the hidden input
    let editorButtonMenus = $('#wpaicg-editor-button-menus').val();
    editorButtonMenus = editorButtonMenus ? JSON.parse(editorButtonMenus) : [];

    // Save setting via AJAX
    const saveEditorSetting = (field, value) => {
        showSpinner(); // Show spinner when saving
        $.post(ajaxurl, {
            action: ajaxWooAction,
            field,
            value,
            _wpnonce: nonce
        })
        .done(response => {
            if (response.success) {
                showSuccess(response.data.message); // Show success message
            } else {
                showError('An error occurred while saving the setting.');
            }
        })
        .fail(() => {
            showError('Failed to connect to the server. Please try again.');
        });
    };

    // Function to repopulate the dropdown with the updated menu list
    const refreshDropdown = () => {
        menuDropdown.empty(); // Clear current dropdown options
        editorButtonMenus.forEach((menu, index) => {
            menuDropdown.append(new Option(menu.name, index));
        });

        if (editorButtonMenus.length === 0) {
            menuDropdown.prop('disabled', true); // Disable dropdown if no items
            deleteButton.hide(); // Hide delete button if no menus left
            $('#assistant-menu-name, #assistant-menu-prompt').val('').prop('disabled', true); // Disable fields when no menus
        } else {
            menuDropdown.prop('disabled', false);
            menuDropdown.val(0).trigger('change'); // Select the first menu
            deleteButton.show(); // Show delete button if there are menus
            $('#assistant-menu-name, #assistant-menu-prompt').prop('disabled', false); // Enable fields when there are menus
        }
    };

    // Get the next "New Menu" number
    const getNextMenuNumber = () => {
        let maxNumber = 0;
        editorButtonMenus.forEach(menu => {
            const match = menu.name.match(/New Menu (\d+)/);
            if (match) {
                const number = parseInt(match[1], 10);
                if (number > maxNumber) {
                    maxNumber = number;
                }
            }
        });
        return maxNumber + 1; // Return the next available number
    };

    // Populate the menu dropdown
    const menuDropdown = $('#aipower-assistant-menu-select');
    const deleteButton = $('#aipower-delete-selected-menu');
    refreshDropdown();

    // Track whether the input has been changed
    let isChanged = false;

    // Function to load the selected menu details into the fields
    const loadSelectedMenuDetails = () => {
        const selectedMenuIndex = menuDropdown.val();
        const selectedMenu = editorButtonMenus[selectedMenuIndex];

        if (selectedMenu) {
            $('#assistant-menu-name').val(selectedMenu.name);
            $('#assistant-menu-prompt').val(selectedMenu.prompt);
            // Store initial values to track changes
            $('#assistant-menu-name').data('initial-value', selectedMenu.name);
            $('#assistant-menu-prompt').data('initial-value', selectedMenu.prompt);
        } else {
            $('#assistant-menu-name, #assistant-menu-prompt').val('').prop('disabled', true); // Disable fields if no menu selected
        }
    };

    // Load the selected menu details when a new menu is selected
    menuDropdown.on('change', loadSelectedMenuDetails);

    // Track changes when user types into the fields
    $('#assistant-menu-name, #assistant-menu-prompt').on('input', function () {
        isChanged = true; // Set isChanged to true when user types
    });

    // Save only if changes are made and field is not disabled
    const handleBlurSave = function () {
        const field = $(this);
        const initialValue = field.data('initial-value');
        const currentValue = field.val();

        // Only save if the value has changed (user has typed) and the field is enabled
        if (isChanged && currentValue !== initialValue && !field.prop('disabled')) {
            const selectedMenuIndex = $('#aipower-assistant-menu-select').val();
            editorButtonMenus[selectedMenuIndex][field.attr('id').replace('assistant-menu-', '')] = currentValue;

            // Save the updated menu
            saveEditorSetting('wpaicg_editor_button_menus', JSON.stringify(editorButtonMenus));
        }

        // Reset change tracking after save
        isChanged = false;
    };

    // Attach blur event to menu name and prompt fields
    $('#assistant-menu-name, #assistant-menu-prompt').on('blur', handleBlurSave);

    // Add new menu item with incremented name
    $('#aipower-add-new-menu').on('click', function () {
        const newMenuNumber = getNextMenuNumber(); // Get the next available number
        const newMenu = {
            name: `New Menu ${newMenuNumber}`, // Use the new number for the menu name
            prompt: ''
        };
        editorButtonMenus.push(newMenu);
        refreshDropdown();
        menuDropdown.val(editorButtonMenus.length - 1).trigger('change'); // Select the new menu
        saveEditorSetting('wpaicg_editor_button_menus', JSON.stringify(editorButtonMenus));
        $('#assistant-menu-name, #assistant-menu-prompt').prop('disabled', false); // Enable fields when a new menu is added
    });

    // Show confirmation message next to the delete button
    $('#aipower-delete-selected-menu').on('click', function () {
        $('#aipower-confirm-delete').show();
    });

    // Handle the confirmation of deletion when "Yes, Delete" is clicked
    $('#aipower-confirm-yes-delete').on('click', function () {
        const selectedMenuIndex = menuDropdown.val();

        if (selectedMenuIndex !== null) {
            editorButtonMenus.splice(selectedMenuIndex, 1); // Remove the selected menu
            refreshDropdown(); // Refresh the dropdown to reflect the deletion

            // Save the updated menu list
            saveEditorSetting('wpaicg_editor_button_menus', JSON.stringify(editorButtonMenus));

            $('#aipower-confirm-delete').hide(); // Hide the confirmation message after deletion
        }
    });

    // Hide confirmation if user clicks outside delete button or confirmation area
    $(document).on('click', function (event) {
        if (!$(event.target).closest('#aipower-delete-selected-menu, #aipower-confirm-delete').length) {
            $('#aipower-confirm-delete').hide(); // Hide the confirmation message
        }
    });

    // Function to update module navigation based on module settings
    function updateModuleNavigation(moduleSettings) {
        // Hide all navigation links
        $('.aipower-top-navigation li').hide();
        // Show navigation links for enabled modules
        $.each(moduleSettings, function(moduleKey, isEnabled) {
            if (isEnabled) {
                $('.aipower-top-navigation li[data-module="' + moduleKey + '"]').show();
            }
        });
    
        // Handle the 'chat_bot' module separately for the tab
        if (moduleSettings['chat_bot']) {
            $('.aipower-tab-btn[data-tab="chatbot"]').show();
        } else {
            $('.aipower-tab-btn[data-tab="chatbot"]').hide();
        }
    }
    

	// Check if moduleSettings is defined before initializing the navigation
	if (typeof moduleSettings !== 'undefined') {
		updateModuleNavigation(moduleSettings);
	}

    // Handle Module Settings checkbox changes
    $('[id^="module-"]').on('change', function() {
        var moduleKey = $(this).attr('id').replace('module-', '');
        var isEnabled = $(this).is(':checked');
        var nonce = $('#ai-engine-nonce').val();

        // Disable all checkboxes to prevent multiple changes
        $('[id^="module-"]').prop('disabled', true);

        // Show spinner
        showSpinner();

        // Send AJAX request to update module settings
        $.post(ajaxurl, {
            action: 'aipower_update_module_settings',
            module_key: moduleKey,
            enabled: isEnabled ? 1 : 0,
            _wpnonce: nonce
        }, function(response) {
            if (response.success) {
                // Update the moduleSettings variable with the updated settings
                moduleSettings = response.data.module_settings;

                // Update the module navigation and tabs based on updated settings
                updateModuleNavigation(moduleSettings);

                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while updating module settings.');
                // Revert the checkbox to its previous state
                $('[id="module-' + moduleKey + '"]').prop('checked', !isEnabled);
            }

            // Re-enable all checkboxes after the request completes
            $('[id^="module-"]').prop('disabled', false);
        }).fail(function() {
            showError('Failed to connect to the server. Please try again.');
            // Revert the checkbox to its previous state
            $('[id="module-' + moduleKey + '"]').prop('checked', !isEnabled);
            // Re-enable all checkboxes
            $('[id^="module-"]').prop('disabled', false);
        });
    });

    // -------------------- LOGIC: Sync ElevenLabs Voices --------------------
    $('#aipower_sync_voices_button').on('click', function () {
        var button = $(this);
        var icon = button.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val();
        // Disable the button to prevent multiple clicks
        button.prop('disabled', true);
        icon.addClass('aipower-rotating'); // Add a rotating animation class if defined in CSS

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_sync_voices', // The AJAX action to trigger the backend function
                nonce: nonce // The nonce value to verify the request
            },
            success: function (response) {
                icon.removeClass('aipower-rotating'); // Stop the rotating animation
                button.prop('disabled', false); // Re-enable the button

                if (response.status === 'success') {
                    showSuccess(response.message); // Display success message
                } else {
                    showError(response.message || 'An error occurred while syncing voices.');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Stop the rotating animation
                button.prop('disabled', false); // Re-enable the button
                showError('Error: ' + errorThrown); // Display error message
            }
        });
    });
    // -------------------- LOGIC: Sync ElevenLabs Models --------------------
    $('#aipower_sync_models_button').on('click', function () {
        var button = $(this);
        var icon = button.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val();

        // Disable the button to prevent multiple clicks
        button.prop('disabled', true);
        icon.addClass('aipower-rotating'); // Add a rotating animation class if defined in CSS

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_sync_models', // The AJAX action to trigger the backend function
                nonce: nonce // Pass the nonce for security
            },
            success: function (response) {
                icon.removeClass('aipower-rotating'); // Stop the rotating animation
                button.prop('disabled', false); // Re-enable the button

                if (response.status === 'success') {
                    showSuccess(response.message); // Display success message
                } else {
                    showError(response.message || 'An error occurred while syncing models.');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Stop the rotating animation
                button.prop('disabled', false); // Re-enable the button
                showError('Error: ' + errorThrown); // Display error message
            }
        });
    });

    // -------------------- LOGIC: Sync Google Voices --------------------
    $('#aipower_sync_google_voices_button').on('click', function () {
        var button = $(this);
        var icon = button.find('.dashicons');
        var nonce = $('#ai-engine-nonce').val();

        // Disable the button to prevent multiple clicks
        button.prop('disabled', true);
        icon.addClass('aipower-rotating'); // Add a rotating animation class if defined in CSS

        $.ajax({
            url: ajaxurl, // WordPress AJAX URL
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'wpaicg_sync_google_voices', // The AJAX action to trigger the backend function
                nonce: nonce // Pass the nonce for security
            },
            success: function (response) {
                icon.removeClass('aipower-rotating'); // Stop the rotating animation
                button.prop('disabled', false); // Re-enable the button

                if (response.status === 'success') {
                    showSuccess(response.msg); // Display success message (backend uses 'msg')
                } else {
                    showError(response.msg || 'An error occurred while syncing Google voices.');
                }
            },
            error: function (jqXHR, textStatus, errorThrown) {
                icon.removeClass('aipower-rotating'); // Stop the rotating animation
                button.prop('disabled', false); // Re-enable the button
                showError('Error: ' + errorThrown); // Display error message
            }
        });
    });
    // Handle toggle switch for default site-wide widget
    $(document).on('click', '.aipower-toggle-switch', function () {
        var $switch = $(this);
        var currentStatus = $switch.data('status'); // 'active' or ''
        var newStatus = currentStatus === 'active' ? '' : 'active';
        var nonce = $('#ai-engine-nonce').val();

        // Optional: Show spinner or some feedback
        showSpinner();

        // AJAX request to update the widget status
        $.post(ajaxurl, {
            action: 'aipower_toggle_default_widget_status',
            status: newStatus,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                // Update the switch's data-status attribute
                $switch.data('status', newStatus);
                
                // Toggle the active class for color change
                if (newStatus === 'active') {
                    $switch.removeClass('inactive').addClass('active');
                } else {
                    $switch.removeClass('active').addClass('inactive');
                }

                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'Failed to update widget status.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    });

	$( document ).on("click", "#wpcgai_load_draft_settings", function(event) {
		event.preventDefault();
		var wpai_preview_title = $("#wpai_preview_title").val();
		$(".editor-post-title").text(wpai_preview_title); 
		$("input#title").val(wpai_preview_title);  		
		
			jQuery('.editor-post-title').focus();
			
			setTimeout(function(){ 
				$("input#save-post").click();  
				$(".editor-post-save-draft").click(); 
				setTimeout(function(){
					if($('#editor').hasClass('block-editor__container')){
					   //location.reload(true); 
					   var post_id___ = $('#post_ID').val();
						var con__ = $("#wpcgai_preview_box").val()
						var data__ = {
							'action' : 'wpaicg_set_post_content_',
							'content':con__,
							'post_id':post_id___
						}
						$.post(ajaxurl, data__, function(response__) {

							location.reload(true);

						});
					}
					
				}, 1000); 
			}, 500);  
  
	});

    // -------------------- LOGIC: Handle Replicate Model Fields START--------------------
    const parseJsonFromHtmlAttribute = (htmlEncodedString) => {
        try {
            const textarea = document.createElement('textarea');
            textarea.innerHTML = htmlEncodedString;
            return JSON.parse(textarea.value);
        } catch (error) {
            console.error('Error parsing JSON from HTML attribute:', error, htmlEncodedString);
            return null;
        }
    };

    const updateModelFields = () => {
        const selectedOption = $('#aipower-replicate-model').find(':selected');
        const rawSchema = selectedOption.attr('data-schema') || '{}';
        const schema = parseJsonFromHtmlAttribute(rawSchema);

        if (!schema) {
            console.error('Failed to parse schema');
            return;
        }

        const inputSchema = schema?.Input?.properties || {};
        const fieldsContainer = $('#aipower-replicate-model-fields');
        fieldsContainer.empty(); // Clear existing fields

        const wrapper = $('<div>', { class: 'aipower-form-group aipower-grouped-fields' });

        $.each(inputSchema, function (key, config) {
            // Skip fields we want to hide
            if (key === 'num_outputs' || key === 'prompt') {
                return; // Do not render these fields
            }

            const field = $('<div>', { class: 'aipower-form-group', css: { position: 'relative' } });

            // Create label with a consistent space for the icon
            const label = $('<label>', {
                text: config.title || key
            });

            // Question mark icon, initially hidden
            const helpIcon = $('<span>', {
                class: 'dashicons dashicons-editor-help aipower-replicate-help-icon',
                title: 'Click for more info',
                css: { visibility: 'hidden', cursor: 'pointer' },
                tabindex: 0, // Make it focusable
                role: 'button',
                'aria-expanded': 'false',
            });

            // Tooltip element
            const tooltip = $('<div>', {
                class: 'aipower-replicate-tooltip',
                text: config.description || 'No description available.',
                css: {
                    position: 'absolute',
                    background: '#333',
                    color: '#fff',
                    padding: '5px 10px',
                    borderRadius: '4px',
                    fontSize: '12px',
                    display: 'none',
                    zIndex: 1000,
                    top: '30%', // Position below the icon
                    marginTop: '5px', // Space between icon and tooltip
                    wordWrap: 'break-word',
                },
            }).appendTo(field); // Append tooltip to the field container

            // Show help icon on label hover
            label.on('mouseenter', () => helpIcon.css('visibility', 'visible'));
            label.on('mouseleave', () => helpIcon.css('visibility', 'hidden'));

            // Toggle tooltip on icon click
            helpIcon.on('click', (event) => {
                event.stopPropagation(); // Prevent event from bubbling up
                $('.aipower-replicate-tooltip').not(tooltip).hide(); // Hide other tooltips
                tooltip.toggle(); // Toggle current tooltip
                const isVisible = tooltip.is(':visible');
                helpIcon.attr('aria-expanded', isVisible);
            });

            // Hide tooltip when clicking outside
            $(document).on('click', function () {
                tooltip.hide();
                helpIcon.attr('aria-expanded', 'false');
            });

            // Prevent hiding when clicking inside tooltip
            tooltip.on('click', function (event) {
                event.stopPropagation();
            });

            // **New:** Hide tooltip when mouse leaves the field container
            field.on('mouseleave', function () {
                tooltip.hide();
                helpIcon.attr('aria-expanded', 'false');
            });

            label.append(helpIcon).appendTo(field);

            let input;

            if (config.type === 'boolean') {
                input = $('<input>', {
                    type: 'checkbox',
                    checked: !!config.default
                });
            } else if (config.type === 'integer' || config.type === 'number') {
                input = $('<input>', {
                    type: 'number',
                    value: config.default || '',
                    min: config.minimum || undefined,
                    max: config.maximum || undefined,
                    step: config.type === 'integer' ? '1' : 'any', // Ensure integer fields have step=1
                });

                // **Updated:** Validate input values on blur instead of input
                input.on('blur', function () {
                    const valueStr = $(this).val();
                    const value = parseFloat(valueStr);
                    const min = parseFloat($(this).attr('min')) || 0;
                    const max = parseFloat($(this).attr('max')) || Infinity;

                    if (valueStr === '') {
                        showError(`This field is required.`);
                        return;
                    }

                    if (isNaN(value)) {
                        showError(`Please enter a valid number.`);
                        return;
                    }

                    if (value < min || value > max) {
                        showError(`Value must be between ${min} and ${max}.`);
                        // Optionally, you can highlight the field
                        $(this).addClass('input-error');
                    } else {
                        clearError();
                        $(this).removeClass('input-error');
                    }
                });

                // **Optional:** Prevent non-numeric input (excluding control keys)
                input.on('keypress', function (e) {
                    const charCode = e.which ? e.which : e.keyCode;
                    // Allow: backspace, delete, left arrow, right arrow, tab, etc.
                    if (
                        [8, 9, 37, 39, 46].includes(charCode) ||
                        // Allow numbers
                        (charCode >= 48 && charCode <= 57) ||
                        // Allow one decimal point for type 'number' (if not integer)
                        (config.type !== 'integer' && charCode === 46 && !$(this).val().includes('.'))
                    ) {
                        return;
                    }
                    e.preventDefault();
                });
            } else if (config.type === 'string' && config.enum) {
                input = $('<select>');
                $.each(config.enum, function (_, value) {
                    $('<option>', {
                        value: value,
                        text: value,
                        selected: config.default === value
                    }).appendTo(input);
                });
            } else {
                input = $('<input>', {
                    type: 'text',
                    value: config.default || ''
                });
            }

            input.attr('data-key', key); // Add a key to identify the field
            input.on('change', saveReplicateField); // Attach autosave logic
            field.append(input);
            wrapper.append(field);
        });

        fieldsContainer.append(wrapper);
    };

    // Prevent tooltips from hiding when clicking inside them
    $(document).on('click', '.aipower-replicate-tooltip', function (event) {
        event.stopPropagation();
    });

    // Initialize fields on page load
    $(document).ready(function () {
        updateModelFields();
    });

    // -------------------- LOGIC: Handle Replicate Model Fields END-------------------- 

    // Autosave logic
    const saveReplicateField = function () {
        const key = $(this).data('key');
        const input = $(this);
        const min = parseFloat(input.attr('min')) || 0;
        const max = parseFloat(input.attr('max')) || Infinity;
        const valueStr = input.val();
        const value = input.is(':checkbox') ? (input.is(':checked') ? 1 : 0) : parseFloat(valueStr);

        // Validate before saving
        if (input.is('input[type="number"]')) {
            if (isNaN(value) || value < min || value > max) {
                showError(`Cannot save. Value for ${key} must be between ${min} and ${max}.`);
                return; // Abort save
            }
        }

        let fieldValue;
        if (input.is(':checkbox')) {
            fieldValue = input.is(':checked') ? 1 : 0;
        } else {
            fieldValue = input.val();
        }

        const nonce = $('#ai-engine-nonce').val();
        const modelName = $('#aipower-replicate-model').val();

        showSpinner();

        $.post(ajaxurl, {
            action: 'aipower_save_replicate_field',
            model_name: modelName,
            field_key: key,
            field_value: fieldValue,
            _wpnonce: nonce
        }, function (response) {
            if (response.success) {
                showSuccess(response.data.message);
            } else {
                showError(response.data.message || 'An error occurred while saving the field.');
            }
        }).fail(function () {
            showError('Failed to connect to the server. Please try again.');
        });
    };

    // Helper function to clear error messages
    const clearError = () => {
        $('#error-message').hide().text('');
    };

    // Update fields on model change
    $('#aipower-replicate-model').on('change', updateModelFields);

    // Initialize fields on page load
    updateModelFields();
    // -------------------- LOGIC: Handle Replicate Model Fields END--------------------

    // -------------------- HANDLE SEO DEPENDENCIES --------------------

    const seoFields = [
        { field: '_wpaicg_seo_meta_tag', selector: '#_wpaicg_seo_meta_tag' },
        { field: '_yoast_wpseo_metadesc', selector: '#_yoast_wpseo_metadesc' },
        { field: '_aioseo_description', selector: '#_aioseo_description' },
        { field: 'rank_math_description', selector: '#rank_math_description' },
        { field: '_wpaicg_genesis_description', selector: '#_wpaicg_genesis_description' }
    ];

    /**
     * Disable other fields if one is selected
     * @param {string} activeField - The field that is currently selected
     */
    const manageDependencies = (activeField) => {
        seoFields.forEach(({ field, selector }) => {
            const $fieldElement = $(selector);

            if (field === activeField) {
                // Enable the selected field
                $fieldElement.prop('disabled', false);
            } else {
                // Disable other fields
                $fieldElement.prop('checked', false).prop('disabled', true);
            }
        });
    };

    /**
     * Re-enable all fields if no field is selected
     */
    const resetDependencies = () => {
        seoFields.forEach(({ selector }) => {
            $(selector).prop('disabled', false);
        });
    };

    // Attach change event listeners to all SEO fields
    seoFields.forEach(({ field, selector }) => {
        const $fieldElement = $(selector);

        $fieldElement.on('change', function() {
            if ($(this).is(':checked')) {
                // If a field is selected, manage dependencies
                manageDependencies(field);
            } else {
                // If no field is selected, reset all dependencies
                const anyFieldChecked = seoFields.some(({ selector }) => $(selector).is(':checked'));
                if (!anyFieldChecked) {
                    resetDependencies();
                }
            }
        });
    });

    // Applying tooltips to the fields
    fieldsConfig.forEach(({ selector, tooltip }) => {
        if (tooltip) {
            // Extract the ID from the selector (remove the '#' prefix)
            const id = selector.startsWith('#') ? selector.slice(1) : selector;

            // Find the label associated with this ID
            const $label = $(`label[for="${id}"]`);

            if ($label.length) {

                // Create the tooltip icon
                const $helpIcon = $('<span class="aipower-tooltip-icon" aria-label="Tooltip" tabindex="0"></span>');

                // Append the help icon after the label
                $label.after($helpIcon);

                // Create the tooltip content
                const $tooltipContent = $('<div class="aipower-tooltip-content"></div>').html(tooltip);
                $helpIcon.append($tooltipContent);

                // Toggle tooltip visibility on icon click
                $helpIcon.on('click', function () {
                    $tooltipContent.toggleClass('visible');
                });

                // Close the tooltip if clicked outside
                $(document).on('click', function (event) {
                    if (!$(event.target).closest($helpIcon).length) {
                        $tooltipContent.removeClass('visible');
                    }
                });
            }
        }
    });

})( jQuery );
