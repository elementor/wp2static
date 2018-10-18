<?php

function cots_enqueue_styles() {
    wp_enqueue_style(
            'parent-style',
            get_template_directory_uri() . '/style.css'
            );

    wp_enqueue_style( 'child-style',
            get_stylesheet_directory_uri() . '/style.css',
            array( 'parent-style' )
            );
}

add_action( 'wp_enqueue_scripts', 'cots_enqueue_styles' ); 

add_filter('bloginfo', 'custom_get_bloginfo', 10, 2);

function custom_get_bloginfo($output, $show) {
    switch( $show ) {
        case 'description':
            $output = 'childoftwentyseventeen';
            break;
        case 'name':
            $output = 'Child Theme Activated!';
            break;
    }

    return $output;
}

?>
