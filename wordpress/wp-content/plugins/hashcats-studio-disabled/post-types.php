<?php
function register_hashcats_post_types() {
    register_post_type('hashcat_creation', array(
        'labels' => array(
            'name' => 'HashCat Creations',
            'singular_name' => 'HashCat Creation'
        ),
        'public' => true,
        'show_in_rest' => true,
        'supports' => array('title', 'editor', 'thumbnail'),
        'menu_icon' => 'dashicons-art'
    ));
}
add_action('init', 'register_hashcats_post_types');

function hashcats_register_custom_post_types() {
    register_post_type('cat_story',
        array(
            'labels'      => array(
                'name'          => __('Cat Stories', 'hashcats'),
                'singular_name' => __('Cat Story', 'hashcats'),
            ),
            'public'      => true,
            'has_archive' => false,
            'show_in_rest' => true,
            'supports'    => array('title', 'editor', 'custom-fields'),
        )
    );
}
add_action('init', 'hashcats_register_custom_post_types');
