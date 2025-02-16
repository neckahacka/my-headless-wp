<?php
if ( ! defined( 'ABSPATH' ) ) exit;
    ?>
    <div id="tabs-7">
        <h3><?php echo esc_html__('Product Writer','gpt3-ai-content-generator')?></h3>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_woo_generate_title"><?php echo esc_html__('Write a product title','gpt3-ai-content-generator')?>:</label>
            <?php $wpaicg_woo_generate_title = get_option('wpaicg_woo_generate_title',false); ?>
            <input <?php echo $wpaicg_woo_generate_title ? ' checked':'';?> type="checkbox" name="wpaicg_woo_generate_title" value="1">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#woocommerce-product-writer" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_woo_generate_description"><?php echo esc_html__('Write a full product description','gpt3-ai-content-generator')?>:</label>
            <?php $wpaicg_woo_generate_description = get_option('wpaicg_woo_generate_description',false); ?>
            <input <?php echo $wpaicg_woo_generate_description ? ' checked':'';?> type="checkbox" name="wpaicg_woo_generate_description" value="1">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#woocommerce-product-writer" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_woo_generate_short"><?php echo esc_html__('Write a short product description','gpt3-ai-content-generator')?>:</label>
            <?php $wpaicg_woo_generate_short = get_option('wpaicg_woo_generate_short',false); ?>
            <input <?php echo $wpaicg_woo_generate_short ? ' checked':'';?> type="checkbox" name="wpaicg_woo_generate_short" value="1">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#woocommerce-product-writer" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_woo_generate_tags"><?php echo esc_html__('Generate product tags','gpt3-ai-content-generator')?>:</label>
            <?php $wpaicg_woo_generate_tags = get_option('wpaicg_woo_generate_tags',false); ?>
            <input <?php echo $wpaicg_woo_generate_tags ? ' checked':'';?> type="checkbox" name="wpaicg_woo_generate_tags" value="1">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#woocommerce-product-writer" target="_blank">?</a>
        </div>
        <h3><?php echo esc_html__('SEO Optimization','gpt3-ai-content-generator')?></h3>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_woo_meta_description"><?php echo esc_html__('Generate meta description','gpt3-ai-content-generator')?>:</label>
            <?php $wpaicg_woo_meta_description = get_option('wpaicg_woo_meta_description',false); ?>
            <input <?php echo $wpaicg_woo_meta_description ? ' checked':'';?> type="checkbox" name="wpaicg_woo_meta_description" value="1">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#woocommerce-product-writer" target="_blank">?</a>
        </div>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="_wpaicg_shorten_woo_url"><?php echo esc_html__('Shorten product URL', 'gpt3-ai-content-generator'); ?>:</label>
            
            <?php
            if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                $_wpaicg_shorten_woo_url = get_option('_wpaicg_shorten_woo_url', false);
                ?>
                <input <?php echo $_wpaicg_shorten_woo_url ? ' checked' : ''; ?> type="checkbox" name="_wpaicg_shorten_woo_url" value="1">
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#shorten-url" target="_blank">?</a>
            <?php
            } else {
                ?>
                <input type="checkbox" name="_wpaicg_shorten_woo_url" value="0" disabled>
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#shorten-url" target="_blank">?</a>
                <span style="color: grey;"><a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>">Upgrade to Pro</a></span>
            <?php
            }
            ?>
        </div>
        <!-- Generate Focus Keyword -->
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_generate_woo_focus_keyword"><?php echo esc_html__('Generate focus keyword', 'gpt3-ai-content-generator'); ?>:</label>
            <?php
            if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                $wpaicg_generate_woo_focus_keyword = get_option('wpaicg_generate_woo_focus_keyword', false);
                ?>
                <input <?php echo $wpaicg_generate_woo_focus_keyword ? ' checked' : ''; ?> type="checkbox" name="wpaicg_generate_woo_focus_keyword" value="1">
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#focus-keyword" target="_blank">?</a>
            <?php
            } else {
                ?>
                <input type="checkbox" name="wpaicg_generate_woo_focus_keyword" value="0" disabled>
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#focus-keyword" target="_blank">?</a>
                <span style="color: grey;"><a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>">Upgrade to Pro</a></span>
            <?php
            }
            ?>
        </div>
        <!-- Enforce focus keyword in URL -->
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_enforce_woo_keyword_in_url"><?php echo esc_html__('Enforce focus keyword in URL', 'gpt3-ai-content-generator'); ?>:</label>
            <?php
            if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()) {
                $wpaicg_enforce_woo_keyword_in_url = get_option('wpaicg_enforce_woo_keyword_in_url', false);
                ?>
                <input <?php echo $wpaicg_enforce_woo_keyword_in_url ? ' checked' : ''; ?> type="checkbox" name="wpaicg_enforce_woo_keyword_in_url" value="1">
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#enforce-focus-keyword-in-url" target="_blank">?</a>
            <?php
            } else {
                ?>
                <input type="checkbox" name="wpaicg_enforce_woo_keyword_in_url" value="0" disabled>
                <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#enforce-focus-keyword-in-url" target="_blank">?</a>
                <span style="color: grey;"><a href="<?php echo esc_url(admin_url('admin.php?page=wpaicg-pricing')); ?>">Upgrade to Pro</a></span>
            <?php
            }
            ?>
        </div>
        <h3><?php echo esc_html__('Prompt Design','gpt3-ai-content-generator')?></h3>
        <?php
        $wpaicg_woo_custom_prompt = get_option('wpaicg_woo_custom_prompt',false);
        $wpaicg_woo_custom_prompt_title = get_option('wpaicg_woo_custom_prompt_title',esc_html__('Compose an SEO-optimized title in English for the following product: %s. Ensure it is engaging, concise, and includes relevant keywords to maximize its visibility on search engines.','gpt3-ai-content-generator'));
        $wpaicg_woo_custom_prompt_short = get_option('wpaicg_woo_custom_prompt_short',esc_html__('Provide a compelling and concise summary in English for the following product: %s, highlighting its key features, benefits, and unique selling points.','gpt3-ai-content-generator'));
        $wpaicg_woo_custom_prompt_description = get_option('wpaicg_woo_custom_prompt_description',esc_html__('Craft a comprehensive and engaging product description in English for: %s. Include specific details, features, and benefits, as well as the value it offers to the customer, thereby creating a compelling narrative around the product.','gpt3-ai-content-generator'));
        $wpaicg_woo_custom_prompt_keywords = get_option('wpaicg_woo_custom_prompt_keywords',esc_html__('Propose a set of relevant keywords in English for the following product: %s. The keywords should be directly related to the product, enhancing its discoverability. Please present these keywords in a comma-separated format, avoiding the use of symbols such as -, #, etc.','gpt3-ai-content-generator'));
        $wpaicg_woo_custom_prompt_meta = get_option('wpaicg_woo_custom_prompt_meta',esc_html__('Craft a compelling and concise meta description in English for: %s. Aim to highlight its key features and benefits within a limit of 155 characters, while incorporating relevant keywords for SEO effectiveness.','gpt3-ai-content-generator'));
        $wpaicg_woo_custom_prompt_focus_keyword = get_option('wpaicg_woo_custom_prompt_focus_keyword', esc_html__('Identify the primary keyword for the following product: %s. Please respond in English. No additional comments, just the keyword.', 'gpt3-ai-content-generator'));
        $wpaicg_woo_custom_prompt_focus_keyword = str_replace("\\", '', $wpaicg_woo_custom_prompt_focus_keyword);
        $wpaicg_woo_custom_prompt_title = str_replace("\\",'',$wpaicg_woo_custom_prompt_title);
        $wpaicg_woo_custom_prompt_short = str_replace("\\",'',$wpaicg_woo_custom_prompt_short);
        $wpaicg_woo_custom_prompt_description = str_replace("\\",'',$wpaicg_woo_custom_prompt_description);
        $wpaicg_woo_custom_prompt_keywords = str_replace("\\",'',$wpaicg_woo_custom_prompt_keywords);
        $wpaicg_woo_custom_prompt_meta = str_replace("\\",'',$wpaicg_woo_custom_prompt_meta);
        ?>
        <div class="wpcgai_form_row">
            <label class="wpcgai_label" for="wpaicg_woo_custom_prompt"><?php echo esc_html__('Use Custom Prompt','gpt3-ai-content-generator')?>:</label>
            <input <?php echo $wpaicg_woo_custom_prompt ? ' checked':'';?> type="checkbox" class="wpaicg_woo_custom_prompt" name="wpaicg_woo_custom_prompt" value="1">
            <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/woocommerce#customizing-prompts" target="_blank">?</a>
        </div>
        <p></p>
        <div <?php echo $wpaicg_woo_custom_prompt ? '':' style="display:none"';?> class="wpaicg_woo_custom_prompts">
        <div class="wpcgai_form_row">
        <p>
            <?php echo esc_html__('You can use these shortcodes in your custom prompts:', 'gpt3-ai-content-generator');?> 
        </p>
        <ul>
            <li><code>[current_short_description]</code></li>
            <li><code>[current_full_description]</code></li>
            <li><code>[current_attributes]</code></li>
            <li><code>[current_categories]</code></li>
            <li><code>[current_price]</code></li>
            <li><code>[current_weight]</code></li>
            <li><code>[current_length]</code></li>
            <li><code>[current_width]</code></li>
            <li><code>[current_height]</code></li>
            <li><code>[current_sku]</code></li>
            <li><code>[current_purchase_note]</code></li>
            <li><code>[current_focus_keywords]</code></li>
        </ul>
        </div>
        <div class="wpcgai_form_row">
                <!-- Dropdown right below the textarea -->
                <label class="wpcgai_label" for="titlePromptTemplates">Title Prompt Templates:</label>
                <select id="titlePromptTemplates" class="regular-text">
                    <option value="0">--Select a Template--</option>
                    <option value="1">Incorporate Key Features</option>
                    <option value="2">Highlight Unique Selling Points</option>
                    <option value="3">Engage and Inform</option>
                    <option value="4">Keyword Rich</option>
                    <option value="5">Concise Yet Comprehensive</option>
                </select>
            </div>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="wpaicg_woo_custom_prompt_title"><?php echo esc_html__('Title Prompt','gpt3-ai-content-generator')?>:</label>
                <textarea style="width: 65%;" rows="5" type="text" name="wpaicg_woo_custom_prompt_title"><?php echo esc_html($wpaicg_woo_custom_prompt_title);?></textarea>
            </div>
            <p></p>
            <!-- Added Short Description Prompt Templates Dropdown -->
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="ShortDescriptionPromptTemplates">Short Description Prompt Templates:</label>
                <select id="ShortDescriptionPromptTemplates" class="regular-text">
                    <option value="0">--Select a Template--</option>
                    <option value="1">Highlight Features and Benefits</option>
                    <option value="2">Solve a Problem</option>
                    <option value="3">Emphasize Uniqueness</option>
                    <option value="4">Invoke Emotion</option>
                    <option value="5">SEO-Focused</option>
                </select>
            </div>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="wpaicg_woo_custom_prompt_short"><?php echo esc_html__('Short description prompt','gpt3-ai-content-generator')?>:</label>
                <textarea style="width: 65%;" rows="10" type="text" name="wpaicg_woo_custom_prompt_short"><?php echo esc_html($wpaicg_woo_custom_prompt_short);?></textarea>
            </div>
            <p></p>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="DescriptionPromptTemplates">Description Prompt Templates:</label>
                <select id="DescriptionPromptTemplates" class="regular-text">
                    <option value="0">--Select a Template--</option>
                    <option value="1">Highlight Key Features and Benefits</option>
                    <option value="2">Emphasize Practicality and Usability</option>
                    <option value="3">Evoke Emotional Connection</option>
                    <option value="4">Showcase Unique Selling Points</option>
                    <option value="5">Concise and Direct</option>
                </select>
            </div>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="wpaicg_woo_custom_prompt_description"><?php echo esc_html__('Description prompt','gpt3-ai-content-generator')?>:</label>
                <textarea style="width: 65%;" rows="10" type="text" name="wpaicg_woo_custom_prompt_description"><?php echo esc_html($wpaicg_woo_custom_prompt_description);?></textarea>
            </div>
            <p></p>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="MetaDescriptionPromptTemplates">Meta Description Prompt Templates:</label>
                <select id="MetaDescriptionPromptTemplates" class="regular-text">
                    <option value="0">--Select a Template--</option>
                    <option value="1">Focused on Key Features and Benefits</option>
                    <option value="2">Problem-Solving Angle</option>
                    <option value="3">Emotional Appeal</option>
                    <option value="4">Urgency and Exclusivity</option>
                    <option value="5">Direct and Informative</option>
                </select>
            </div>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="wpaicg_woo_custom_prompt_meta"><?php echo esc_html__('Meta Description prompt','gpt3-ai-content-generator')?>:</label>
                <textarea style="width: 65%;" rows="5" type="text" name="wpaicg_woo_custom_prompt_meta"><?php echo esc_html($wpaicg_woo_custom_prompt_meta);?></textarea>
            </div>
            <p></p>
            <!-- Added Tags Prompt Templates Dropdown -->
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="TagsPromptTemplates">Tag Prompt Templates:</label>
                <select id="TagsPromptTemplates" class="regular-text">
                    <option value="0">--Select a Template--</option>
                    <option value="1">Highly Relevant and SEO-Optimized</option>
                    <option value="2">Increase Discoverability</option>
                    <option value="3">Describe Features and Benefits</option>
                    <option value="4">Encompass Functionality and Use-Cases</option>
                    <option value="5">SEO-Optimized High-Search-Volume Keywords</option>
                </select>
            </div>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="wpaicg_woo_custom_prompt_keywords"><?php echo esc_html__('Tag prompt','gpt3-ai-content-generator')?>:</label>
                <textarea style="width: 65%;" rows="5" type="text" name="wpaicg_woo_custom_prompt_keywords"><?php echo esc_html($wpaicg_woo_custom_prompt_keywords);?></textarea>
            </div>
            <?php if (\WPAICG\wpaicg_util_core()->wpaicg_is_pro()): ?>
            <!-- Added Focus Keyword Prompt Templates Dropdown -->
            <p></p>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="FocusKeywordPromptTemplates">Focus Keyword Prompt Templates:</label>
                <select id="FocusKeywordPromptTemplates" class="regular-text">
                    <option value="0">--Select a Template--</option>
                    <option value="1">Generate Single Keyword</option>
                    <option value="2">Generate Multiple Keywords</option>
                    <option value="3">Niche-Specific and Unique</option>
                    <option value="4">Trending or Seasonal</option>
                    <option value="5">Competitor Analysis</option>
                </select>
            </div>
            <div class="wpcgai_form_row">
                <label class="wpcgai_label" for="wpaicg_woo_custom_prompt_focus_keyword"><?php echo esc_html__('Focus Keyword prompt','gpt3-ai-content-generator')?>:</label>
                <textarea style="width: 65%;" rows="5" type="text" name="wpaicg_woo_custom_prompt_focus_keyword"><?php echo esc_html($wpaicg_woo_custom_prompt_focus_keyword);?></textarea>
            </div>
            <?php endif; ?>

        </div>
        <h3><?php echo esc_html__('Token Sale','gpt3-ai-content-generator')?></h3>
        <?php
        $wpaicg_order_status_token = get_option('wpaicg_order_status_token','completed');
        ?>
        <div class="wpcgai_form_row wpaicg_woo_token_sale">
            <label class="wpcgai_label" for="wpaicg_order_status_token"><?php echo esc_html__('Add tokens to user account if order status is','gpt3-ai-content-generator')?>: </label>
            <select name="wpaicg_order_status_token">
                <option <?php echo $wpaicg_order_status_token == 'completed'? ' selected':''?> value="completed"><?php echo esc_html__('Completed','gpt3-ai-content-generator')?></option>
                <option <?php echo $wpaicg_order_status_token == 'processing'? ' selected':''?> value="processing"><?php echo esc_html__('Processing','gpt3-ai-content-generator')?></option>
            </select>
        <a class="wpcgai_help_link" href="https://docs.aipower.org/docs/user-management-token-sale" target="_blank">?</a>
        </div>
    </div>
<script>
    jQuery(document).ready(function($){

        // Function to populate the text area based on the selected template
        function populateTitleTextArea() {
            var selectedTemplate = $(this).val();
            var textarea = $(this).closest('.wpaicg_woo_custom_prompts').find('textarea[name="wpaicg_woo_custom_prompt_title"]');
            
            // Prompt templates
            var templates = {
                '1': "<?php echo esc_js( esc_html__('Create an SEO-friendly and eye-catching title for the product: %s. The title should emphasize its key features. Use the following for context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '2': "<?php echo esc_js( esc_html__('Devise a captivating and SEO-optimized title for the following product: %s that highlights its unique selling points. Use these details for reference: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '3': "<?php echo esc_js( esc_html__('Craft a product title for %s that is not only SEO-optimized but also engages the customer and informs them why this is the product they’ve been looking for. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '4': "<?php echo esc_js( esc_html__('Construct an SEO-optimized title for the product: %s that is rich in keywords relevant to the product. Use the following for additional context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '5': "<?php echo esc_js( esc_html__('Generate a concise yet comprehensive title for the product: %s that covers all the essential points customers are interested in. Make sure to utilize Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for a better context.', 'gpt3-ai-content-generator') ); ?>"
            };

            if (templates[selectedTemplate]) {
                textarea.val(templates[selectedTemplate]);
            }
        }

        // Toggle custom prompts
        $('.wpaicg_woo_custom_prompt').click(function (){
            if($(this).prop('checked')){
                $('.wpaicg_woo_custom_prompts').show();
            } else {
                $('.wpaicg_woo_custom_prompts').hide();
            }
        });

        // Attach the change event to the dropdown
        $('#titlePromptTemplates').change(populateTitleTextArea);
        // Attach the change event to dynamically generated dropdowns in the modal
        $(document).on('change', '#titlePromptTemplates', populateTitleTextArea);

        // Function to populate the Description text area
        function populateDescriptionTextArea() {
            var selectedTemplate = $(this).val();
            var textarea = $(this).closest('.wpaicg_woo_custom_prompts').find('textarea[name="wpaicg_woo_custom_prompt_description"]');
            
            // Description prompt templates
            var DescriptionTemplates = {
                '1': "<?php echo esc_js( esc_html__('Craft an extensive and captivating narrative around the product: %s. Dive deep into its key features, benefits, and value proposition. Use its Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] to enrich the narrative.', 'gpt3-ai-content-generator') ); ?>",
                '2': "<?php echo esc_js( esc_html__('Develop a comprehensive and detailed description for the product: %s that serves as a complete guide for the customer, detailing its functionality, features, and use-cases. Make sure to incorporate its Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '3': "<?php echo esc_js( esc_html__('Write a compelling product description for %s that evokes an emotional connection, inspiring the customer to visualize themselves using the product. Leverage its Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] to add depth and context.', 'gpt3-ai-content-generator') ); ?>",
                '4': "<?php echo esc_js( esc_html__('Construct an in-depth description for the product: %s that focuses on its unique selling propositions. Distinguish it from competitors and highlight what makes it a must-have. Use Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] for a more detailed context.', 'gpt3-ai-content-generator') ); ?>",
                '5': "<?php echo esc_js( esc_html__('Compose an SEO-optimized, yet customer-centric, description for the product: %s. Include relevant keywords naturally, and focus on answering any questions a customer might have about the product. Utilize Attributes: [current_attributes], Short Description: [current_short_description] and Product Categories: [current_categories] for richer context.', 'gpt3-ai-content-generator') ); ?>"
            };


            if (DescriptionTemplates[selectedTemplate]) {
                textarea.val(DescriptionTemplates[selectedTemplate]);
            }
        }

         // Attach the change event to the Short Description dropdown
        $('#DescriptionPromptTemplates').change(populateDescriptionTextArea);
        $(document).on('change', '#DescriptionPromptTemplates', populateDescriptionTextArea);

        // Function to populate the Short Description text area
        function populateShortDescriptionTextArea() {
            var selectedTemplate = $(this).val();
            var textarea = $(this).closest('.wpaicg_woo_custom_prompts').find('textarea[name="wpaicg_woo_custom_prompt_short"]');
            
            // Short Description prompt templates
            var ShortDescriptionTemplates = {
                '1': "<?php echo esc_js( esc_html__('Compose a short description for the product: %s that succinctly highlights its key features and benefits. Use the following attributes and description for context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '2': "<?php echo esc_js( esc_html__('Write a short description for the product: %s that clearly outlines how it solves a specific problem for the customer. Reference these details for a better understanding: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '3': "<?php echo esc_js( esc_html__('Craft a compelling short description for the product: %s that emphasizes what sets it apart from competitors. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '4': "<?php echo esc_js( esc_html__('Create an emotive short description for the product: %s that aims to establish an emotional connection with potential buyers. Use the following for context: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '5': "<?php echo esc_js( esc_html__('Devise an SEO-optimized short description for the product: %s, incorporating relevant keywords without sacrificing readability. Use these details for reference: Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>"
            };

            if (ShortDescriptionTemplates[selectedTemplate]) {
                textarea.val(ShortDescriptionTemplates[selectedTemplate]);
            }
        }

        // Attach the change event to the Short Description dropdown
        $('#ShortDescriptionPromptTemplates').change(populateShortDescriptionTextArea);
        $(document).on('change', '#ShortDescriptionPromptTemplates', populateShortDescriptionTextArea);


        // Function to populate the Meta Description text area
        function populateMetaDescriptionTextArea() {
            var selectedTemplate = $(this).val();
            var textarea = $(this).closest('.wpaicg_woo_custom_prompts').find('textarea[name="wpaicg_woo_custom_prompt_meta"]');
            
            // Meta Description prompt templates
            var MetaDescriptionTemplates = {
                '1': "<?php echo esc_js( esc_html__('Craft a meta description for the product: %s that succinctly highlights its key features and benefits. Aim to stay within 155 characters. Use Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '2': "<?php echo esc_js( esc_html__('Compose a compelling 155-character meta description for the product: %s that illustrates how it solves a specific problem for the customer. Reference these details: Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '3': "<?php echo esc_js( esc_html__('Write a meta description for the product: %s that emotionally engages the potential customer, inspiring them to click and learn more. Limit to 155 characters. Use Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description] and Product Categories: [current_categories] for added depth.', 'gpt3-ai-content-generator') ); ?>",
                '4': "<?php echo esc_js( esc_html__('Create an SEO-optimized meta description for the product: %s that is rich in keywords, yet readable and engaging. Keep it under 155 characters. Refer to these details: Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description], Product Categories: [current_categories].', 'gpt3-ai-content-generator') ); ?>",
                '5': "<?php echo esc_js( esc_html__('Devise a straightforward, 155-character meta description for the product: %s that provides just the facts, appealing to a no-nonsense customer base. Use Attributes: [current_attributes], Full Description: [current_full_description], Short Description: [current_short_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>"
            };

            if (MetaDescriptionTemplates[selectedTemplate]) {
                textarea.val(MetaDescriptionTemplates[selectedTemplate]);
            }
        }

        // Attach the change event to the Meta Description dropdown
        $('#MetaDescriptionPromptTemplates').change(populateMetaDescriptionTextArea);
        $(document).on('change', '#MetaDescriptionPromptTemplates', populateMetaDescriptionTextArea);

        // Function to populate the Tags text area
        function populateTagsTextArea() {
            var selectedTemplate = $(this).val();
            var textarea = $(this).closest('.wpaicg_woo_custom_prompts').find('textarea[name="wpaicg_woo_custom_prompt_keywords"]');
            
            // Tags prompt templates
            var TagsTemplates = {
                '1': "<?php echo esc_js( esc_html__('Generate a set of highly relevant and SEO-optimized tags for the product: %s. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '2': "<?php echo esc_js( esc_html__('Create a list of tags for the product: %s that will increase its discoverability. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '3': "<?php echo esc_js( esc_html__('Craft a set of tags for the product: %s that describe its features and benefits. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '4': "<?php echo esc_js( esc_html__('Compile a group of keywords as tags for the product: %s that encompass its functionality and use-cases. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>",
                '5': "<?php echo esc_js( esc_html__('Develop an SEO-optimized list of tags for the product: %s, focusing on high-search-volume keywords. Use Attributes: [current_attributes], Short Description: [current_short_description], Full Description: [current_full_description] and Product Categories: [current_categories] for context.', 'gpt3-ai-content-generator') ); ?>"
            };

            if (TagsTemplates[selectedTemplate]) {
                textarea.val(TagsTemplates[selectedTemplate]);
            }
        }

        // Attach the change event to the Tags dropdown
        $('#TagsPromptTemplates').change(populateTagsTextArea);
        $(document).on('change', '#TagsPromptTemplates', populateTagsTextArea);

        // Function to populate the Focus Keyword text area
        function populateFocusKeywordTextArea() {
            var selectedTemplate = $(this).val();
            var textarea = $(this).closest('.wpaicg_woo_custom_prompts').find('textarea[name="wpaicg_woo_custom_prompt_focus_keyword"]');
            
            // Focus Keyword prompt templates
            var FocusKeywordTemplates = {
                '1': "<?php echo esc_js( esc_html__('Identify the primary keyword for the following product: %s. Please respond in English. No additional comments, just the keyword.', 'gpt3-ai-content-generator') ); ?>",
                '2': "<?php echo esc_js( esc_html__('Generate SEO-optimized and high-volume focus keywords in English for the following product: %s. Keywords should be the main terms you aim to rank for. Avoid using symbols like -, #, etc. Results must be comma-separated.', 'gpt3-ai-content-generator') ); ?>",
                '3': "<?php echo esc_js( esc_html__('Generate niche-specific and unique focus keywords in English for the product: %s. Keywords should closely align with the product unique features or niche. Avoid using symbols like -, #, etc. Results must be comma-separated.', 'gpt3-ai-content-generator') ); ?>",
                '4': "<?php echo esc_js( esc_html__('Generate trending or seasonally relevant focus keywords in English for the following product: %s. Ensure they directly relate to the product and its features. Avoid using symbols like -, #, etc. Results must be comma-separated.', 'gpt3-ai-content-generator') ); ?>",
                '5': "<?php echo esc_js( esc_html__('Generate focus keywords in English for the product: %s. Keywords should fill a gap or seize an opportunity that competitors might have missed but are still highly relevant to the product. Avoid using symbols like -, #, etc. Results must be comma-separated.', 'gpt3-ai-content-generator') ); ?>"
            };

            if (FocusKeywordTemplates[selectedTemplate]) {
                textarea.val(FocusKeywordTemplates[selectedTemplate]);
            }
        }

        // Attach the change event to the Focus Keyword dropdown
        $('#FocusKeywordPromptTemplates').change(populateFocusKeywordTextArea);
        $(document).on('change', '#FocusKeywordPromptTemplates', populateFocusKeywordTextArea);

    });
</script>
