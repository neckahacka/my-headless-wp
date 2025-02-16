<?php
/*
Plugin Name: HashCats Studio
Description: Integration for HashCats AI art generation
Version: 1.0
Author: Your Name
*/

// Include the Style Transfer functionality
require_once plugin_dir_path(__FILE__) . 'includes/style-transfer.php';

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Register REST API endpoints
add_action('rest_api_init', function () {
    register_rest_route('hashcats/v1', '/generate', array(
        'methods' => 'POST',
        'callback' => 'hashcats_generate_art',
        'permission_callback' => function () {
            return current_user_can('edit_posts');
        }
    ));
});

function hashcats_generate_art($request) {
    // Get parameters from the request
    $params = $request->get_params();
    $prompt = sanitize_text_field($params['prompt']);
    $style = sanitize_text_field($params['style']);

    try {
        // Here you'll integrate with your AI service (e.g., call to a third-party API)
        $response = array(
            'success' => true,
            'imageUrl' => 'path_to_generated_image.jpg' // Example image URL
        );
        
        return rest_ensure_response($response);
    } catch (Exception $e) {
        return new WP_Error(
            'generation_failed',
            $e->getMessage(),
            array('status' => 500)
        );
    }
}

// Add shortcode for embedding studio
add_shortcode('hashcats_studio', function() {
    return '<div id="hashcats-studio-root"></div>';
});
