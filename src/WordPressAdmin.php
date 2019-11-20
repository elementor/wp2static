<?php
/*
    WordPressAdmin

    WP2Static's interface to WordPress Admin functions

    Used for registering hooks, Admin UI components, ...
*/

namespace WP2Static;

class WordPressAdmin {

    /**
     * WordPressAdmin constructor
     */
    public function __construct() {

    }

    /**
     * Register hooks for WordPress and WP2Static actions 
     *
     * @param string $bootstrap_file main plugin filepath
     */
    public static function registerHooks(string $bootstrap_file) {
        register_activation_hook(
            $bootstrap_file,
            [ 'WP2Static\Controller', 'activate' ]
        );

        add_action(
            'wp2static_headless_hook',
            [ 'WP2Static\Controller', 'wp2static_headless' ],
            10,
            0);

        add_action(
            'wp2static_process_html',
            [ 'WP2Static\SimpleRewriter', 'rewrite' ],
            10,
            1);

        add_action(
            'wp2static_process_css',
            [ 'WP2Static\SimpleRewriter', 'rewrite' ],
            10,
            1);

        add_action(
            'wp2static_process_js',
            [ 'WP2Static\SimpleRewriter', 'rewrite' ],
            10,
            1);

        add_action(
            'wp2static_process_xml',
            [ 'WP2Static\SimpleRewriter', 'rewrite' ],
            10,
            1);

        /*
         * Register actions for when we should invalidate cache for
         * a URL(s) or whole site
         *
         */
        $single_url_invalidation_events = [
            'save_post',
            'deleted_post',
        ];

        $full_site_invalidation_events = [
            'switch_theme',
        ];

        foreach ( $single_url_invalidation_events as $invalidation_events ) {
            add_action(
                $invalidation_events,
                [ 'WP2Static\Controller', 'invalidate_single_url_cache' ],
                0
            );
        }

        if ( ExportSettings::get('redeployOnPostUpdates') ) {
            add_action(
                'save_post',
                [ 'WP2Static\Controller', 'wp2static_headless' ],
                0
            );
        }

        add_action(
            'wp_dashboard_setup',
            [ 'WP2Static\Controller', 'wp2static_add_dashboard_widgets' ],
            0
        );

        add_action(
            'admin_enqueue_scripts',
            [ 'WP2Static\Controller', 'load_wp2static_admin_js' ]
        );

        add_action(
            'admin_enqueue_scripts',
            [ 'WP2Static\Controller', 'wp2static_enqueue_dashboard_scripts' ]
        );
    }

    /**
     * Add WP2Static elements to WordPress Admin UI 
     *
     */
    public static function addAdminUIElements() {
        if ( is_admin() ) {
            add_action(
                'admin_menu',
                [ 'WP2Static\Controller', 'registerOptionsPage' ]
            );
            add_filter( 'custom_menu_order', '__return_true' );
            add_filter( 'menu_order', [ 'WP2Static\Controller', 'set_menu_order' ] );
        }
    }
}

