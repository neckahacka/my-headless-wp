<?php
if (!defined('ABSPATH')) exit; // Exit if accessed directly.
?>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Themes -->
    <div class="aipower-form-group">
        <label for="aipower-themes"><?php echo esc_html__('Theme', 'gpt3-ai-content-generator'); ?></label>
        <select name="aipower-themes" id="aipower-themes">
            <option value="default"><?php echo esc_html__('Default', 'gpt3-ai-content-generator'); ?></option>
            <option value="dark"><?php echo esc_html__('Dark', 'gpt3-ai-content-generator'); ?></option>
            <option value="light"><?php echo esc_html__('Light', 'gpt3-ai-content-generator'); ?></option>
            <option value="whatsapp"><?php echo esc_html__('WhatsApp', 'gpt3-ai-content-generator'); ?></option>
            <option value="terminal"><?php echo esc_html__('Terminal', 'gpt3-ai-content-generator'); ?></option>
            <option value="sunset"><?php echo esc_html__('Sunset', 'gpt3-ai-content-generator'); ?></option>
            <option value="ocean"><?php echo esc_html__('Ocean', 'gpt3-ai-content-generator'); ?></option>
            <option value="forest"><?php echo esc_html__('Forest', 'gpt3-ai-content-generator'); ?></option>
            <option value="neon"><?php echo esc_html__('Neon', 'gpt3-ai-content-generator'); ?></option>
        </select>
    </div>
    <!-- Chat Window Width -->
    <div class="aipower-form-group">
        <label for="aipower-chat-window-width"><?php echo esc_html__('Width', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-chat-window-width" name="aipower-chat-window-width"/>
    </div>
    <!-- Chat Window Height -->
    <div class="aipower-form-group">
        <label for="aipower-chat-window-height"><?php echo esc_html__('Height', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-chat-window-height" name="aipower-chat-window-height"/>
    </div>
</div>
<h4 class="aipower-h3-chatbot-style"><?php echo esc_html__('Window', 'gpt3-ai-content-generator'); ?></h4>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Background Color -->
    <div class="aipower-form-group">
        <label for="aipower-bgcolor"><?php echo esc_html__('Background', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-bgcolor" name="aipower-bgcolor"/>
    </div>
    <!-- Font Color -->
    <div class="aipower-form-group">
        <label for="aipower-fontcolor"><?php echo esc_html__('Font', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-fontcolor" name="aipower-fontcolor"/>
    </div>
    <!-- AI Bubble Color -->
    <div class="aipower-form-group">
        <label for="aipower-aibgcolor"><?php echo esc_html__('AI', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-aibgcolor" name="aipower-aibgcolor"/>
    </div>
    <!-- User Bubble Color -->
    <div class="aipower-form-group">
        <label for="aipower-userbgcolor"><?php echo esc_html__('User', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-userbgcolor" name="aipower-userbgcolor"/>
    </div>
    <!-- Font Size -->
    <div class="aipower-form-group">
        <label for="aipower-fontsize"><?php echo esc_html__('Size', 'gpt3-ai-content-generator'); ?></label>
        <select name="aipower-fontsize" id="aipower-fontsize">
            <?php
            for($i = 10; $i <= 30; $i++){
                echo '<option value="' . $i . '">' . $i . '</option>';
            }
            ?>
        </select>
    </div>
    <!-- Corners -->
    <div class="aipower-form-group">
        <label for="aipower-chat-window-corners"><?php echo esc_html__('Corners', 'gpt3-ai-content-generator'); ?></label>
        <select name="aipower-chat-window-corners" id="aipower-chat-window-corners">
            <?php
            for($i = 1; $i <= 100; $i++){
                echo '<option value="' . $i . '">' . $i . '</option>';
            }
            ?>
        </select>
    </div>
</div>

<h4 class="aipower-h3-chatbot-style"><?php echo esc_html__('Input Field', 'gpt3-ai-content-generator'); ?></h4>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Input Field Background Color -->
    <div class="aipower-form-group">
        <label for="aipower-input-field-bgcolor"><?php echo esc_html__('Background', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-input-field-bgcolor" name="aipower-input-field-bgcolor"/>
    </div>
    <!-- Input Field Font Color -->
    <div class="aipower-form-group">
        <label for="aipower-input-field-fontcolor"><?php echo esc_html__('Font', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-input-field-fontcolor" name="aipower-input-field-fontcolor"/>
    </div>
    <!-- Border Color -->
    <div class="aipower-form-group">
        <label for="aipower-input-border-color"><?php echo esc_html__('Border', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-input-border-color" name="aipower-input-border-color"/>
    </div>
    <!-- Send Button Color -->
    <div class="aipower-form-group">
        <label for="aipower-send-button-color"><?php echo esc_html__('Button', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-send-button-color" name="aipower-send-button-color"/>
    </div>
    <!-- Input Field Height -->
    <div class="aipower-form-group">
        <label for="aipower-input-field-height"><?php echo esc_html__('Height', 'gpt3-ai-content-generator'); ?></label>
        <input type="text" id="aipower-input-field-height" name="aipower-input-field-height"/>
    </div>
    <!-- Text Field Corners -->
    <div class="aipower-form-group">
        <label for="aipower-input-field-corners"><?php echo esc_html__('Corners', 'gpt3-ai-content-generator'); ?></label>
        <select name="aipower-input-field-corners" id="aipower-input-field-corners">
            <?php
            for($i = 1; $i <= 100; $i++){
                echo '<option value="' . $i . '">' . $i . '</option>';
            }
            ?>
        </select>
    </div>
</div>

<h4 class="aipower-h3-chatbot-style"><?php echo esc_html__('Footer / Header', 'gpt3-ai-content-generator'); ?></h4>
<div class="aipower-form-group aipower-grouped-fields-bot">
    <!-- Footer Color -->
    <div class="aipower-form-group">
        <label for="aipower-footer-color"><?php echo esc_html__('Footer', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-footer-color" name="aipower-footer-color"/>
    </div>
    <!-- Footer Font Color -->
    <div class="aipower-form-group">
        <label for="aipower-footer-fontcolor"><?php echo esc_html__('Footer Font', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-footer-fontcolor" name="aipower-footer-fontcolor"/>
    </div>
    <!-- Header Icon Color -->
        <div class="aipower-form-group">
        <label for="aipower-header-iconcolor"><?php echo esc_html__('Header Icon', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-header-iconcolor" name="aipower-header-iconcolor"/>
    </div>
    <!-- Loading Color -->
        <div class="aipower-form-group">
        <label for="aipower-loading-response-color"><?php echo esc_html__('Loading Color', 'gpt3-ai-content-generator'); ?></label>
        <input type="color" id="aipower-loading-response-color" name="aipower-loading-response-color"/>
    </div>
</div>