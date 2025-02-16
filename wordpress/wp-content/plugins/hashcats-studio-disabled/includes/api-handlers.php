<?php
// API handling functions
function handle_image_upload($file) {
    require_once(ABSPATH . 'wp-admin/includes/file.php');
    require_once(ABSPATH . 'wp-admin/includes/image.php');
    
    $upload = wp_handle_upload($file, array('test_form' => false));
    
    if (isset($upload['error'])) {
        return new WP_Error('upload_error', $upload['error']);
    }
    
    return $upload;
}

function save_generation_result($title, $content, $style, $image_url) {
    $post_data = array(
        'post_title'    => wp_strip_all_tags($title),
        'post_content'  => $content,
        'post_status'   => 'publish',
        'post_type'     => 'hashcat_creation'
    );
    
    $post_id = wp_insert_post($post_data);
    
    if ($post_id) {
        update_post_meta($post_id, 'generation_style', $style);
        update_post_meta($post_id, 'image_url', $image_url);
    }
    
    return $post_id;
}

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

add_action('rest_api_init', function () {
    // Register REST routes for saving and getting cat stories
    register_rest_route('hashcats/v1', '/save-story', array(
        'methods' => 'POST',
        'callback' => 'save_cat_story',
        'permission_callback' => '__return_true'
    ));

    register_rest_route('hashcats/v1', '/get-stories', array(
        'methods' => 'GET',
        'callback' => 'get_cat_stories',
        'permission_callback' => '__return_true'
    ));
});

// Save cat story function
function save_cat_story($request) {
    $params = $request->get_params();

    // Insert the story as a custom post type 'cat_story'
    $post_id = wp_insert_post([
        'post_title'   => sanitize_text_field($params['cat_name']),
        'post_content' => wp_kses_post($params['story']),
        'post_status'  => 'publish',
        'post_type'    => 'cat_story',
        'meta_input'   => [
            'traits' => maybe_serialize($params['traits']),
            'theme'  => sanitize_text_field($params['theme']),
        ],
    ]);

    return rest_ensure_response(['status' => 'success', 'post_id' => $post_id]);
}

// Get saved cat stories
function get_cat_stories() {
    $args = [
        'post_type'      => 'cat_story',
        'post_status'    => 'publish',
        'posts_per_page' => -1
    ];

    $query = new WP_Query($args);
    $stories = [];

    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();
            $stories[] = [
                'id'      => get_the_ID(),
                'title'   => get_the_title(),
                'story'   => get_the_content(),
                'traits'  => maybe_unserialize(get_post_meta(get_the_ID(), 'traits', true)),
                'theme'   => get_post_meta(get_the_ID(), 'theme', true),
            ];
        }
    }
    wp_reset_postdata();

    return rest_ensure_response($stories);
}

// Register API route for saving cat personalities
add_action('rest_api_init', function () {
    register_rest_route('hashcats/v1', '/personality', array(
        'methods' => 'POST',
        'callback' => 'save_cat_personality',
        'permission_callback' => '__return_true', // Adjust for authentication if needed
    ));
});

// Function to save cat personality story
function save_cat_personality(WP_REST_Request $request) {
    $params = $request->get_json_params();
    $cat_name = sanitize_text_field($params['cat_name'] ?? 'Unknown Cat');
    $traits = sanitize_text_field($params['traits'] ?? '');
    $theme = sanitize_text_field($params['theme'] ?? '');
    $story = sanitize_textarea_field($params['story'] ?? '');

    // Create a new WordPress post to store the personality data
    $post_id = wp_insert_post([
        'post_title'   => 'Cat Personality: ' . $cat_name,
        'post_content' => $story,
        'post_status'  => 'publish',
        'post_type'    => 'cat_personality',  // Ensure this custom post type exists
        'meta_input'   => [
            'traits' => $traits,
            'theme'  => $theme,
        ],
    ]);

    if (is_wp_error($post_id)) {
        return new WP_REST_Response(['error' => 'Failed to save story.'], 500);
    }

    return new WP_REST_Response(['success' => true, 'post_id' => $post_id], 200);
}
